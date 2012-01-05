<?php

/**
 * Copyright (c) 2011, predakanga
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @author predakanga
 * @since 0.1
 * @category Fossil Core
 * @package Fossil
 * @subpackage Controllers
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil\Controllers;

use Fossil\OM,
    Fossil\Settings,
    Fossil\Requests\BaseRequest,
    Fossil\Responses\TemplateResponse,
    Fossil\Responses\RedirectResponse;

/**
 * Description of Setup
 *
 * @author predakanga
 */
class Setup extends AutoController {
    /**
     * @F:Inject("Dispatcher")
     * @var Fossil\Dispatcher
     */
    protected $dispatcher;
    /**
     * @F:Inject("Filesystem")
     * @var Fossil\Filesystem
     */
    protected $fs;
    
    private $steps = array(array('action' => 'index', 'desc' => 'Introduction'),
                           array('action' => 'checkCompatibility', 'desc' => 'Check compatibility'),
                           array('action' => 'selectDrivers', 'desc' => 'Select drivers'),
                           array('action' => 'configureDrivers', 'desc' => 'Configure drivers'),
                           array('action' => 'selectPlugins', 'desc' => 'Select plugins (optional)'),
                           array('action' => 'runTests', 'desc' => 'Run tests (optional)'),
                           array('action' => 'finished', 'desc' => 'Start coding'));
    
    protected function getSteps() {
        return $this->steps;
    }
    
    protected function getDependencies() {
        $deps = array();
        $fs = $this->fs;
        
        // @codingStandardsIgnoreStart
        $deps['Required'] = array(
            'PHP' => array('Version' => '>= 5.3', 'URL' => 'http://www.php.net', 'Type' => 'Runtime', 'Test' => function() {
                if(!defined('PHP_VERSION_ID')) {
                    return false;
                }
                if(PHP_VERSION_ID < 50300) {
                    return false;
                }
                return true;
            }),
            'Smarty' => array('Version' => '>= 3', 'URL' => 'http://www.smarty.net', 'Type' => 'Templating Engine', 'Test' => function() use($fs) {
                // TODO: Looser coupling for Smarty
                if(!file_exists($fs->fossilRoot() . D_S . 'libs/smarty/distribution/libs/Smarty.class.php')) {
                    return false;
                }
                require_once $fs->fossilRoot() . D_S . 'libs/smarty/distribution/libs/Smarty.class.php';
                
                if(!defined('\Smarty::SMARTY_VERSION')) {
                    return false;
                }
                // Use this test to account for SVN versions of Smarty using Smarty3 as opposed to Smarty-3
                $placement = strpos(\Smarty::SMARTY_VERSION, '3');
                if($placement && $placement <= 8) {
                        return true;
                }
                return false;
            })
        );
        $deps['Optional'] = array(
            'PHPUnit' => array('Version' => '>= 3.5', 'URL' => 'http://www.phpunit.de', 'Type' => 'Testing', 'Test' => function() {
                if(!file_exists(stream_resolve_include_path('PHPUnit/Autoload.php'))) {
                    return false;
                }
                require_once 'PHPUnit/Autoload.php';
                $version = \PHPUnit_Runner_Version::id();
                $version_comp = explode(".", $version);
                if($version_comp[0] == 3 && $version_comp[1] >= 5) {
                    return true;
                }
                return false;
            })
        );
        // @codingStandardsIgnoreEnd
            
        return $deps;
    }
    
    protected function defaultTemplateData() {
        $steps = $this->getSteps();
        // Calculate the current step
        $curReq = $this->dispatcher->getCurrentRequest();
        $curAct = $curReq->action ?: $this->indexAction();
        $curStep = 0;
        foreach($steps as $stepIdx => $stepArr) {
            if($stepArr['action'] == $curAct) {
                $curStep = $stepIdx;
                break;
            }
        }
        $tempArr = array('currentStep' => $curStep,
                         'nextStep' => $curStep+1,
                         'steps' => $steps);
        return $tempArr;
    }
    
    public function runIndex() {
        return $this->templateResponse("setup/index");
    }
    
    public function runCheckCompatibility() {
        // Dependency data
        $data = array();
        $deps = $this->getDependencies();
        
        // Simple var to keep track of whether all required dependencies were met
        $result = true;
        // Compile the above dependency arrays into tested instances
        foreach($deps as $typeKey => &$typeArr) {
            foreach($typeArr as &$dep) {
                $dep['Result'] = $dep['Test']();
                if($typeKey == 'Required' && !$dep['Result']) {
                    $result = false;
                }
            }
        }
        $data = array_merge($data, $deps);
        
        $data['allOK'] = $result;
        return $this->templateResponse("setup/checkCompat", $data);
    }

    private function getClassDrivers($type) {
        $toRet = array();
        
        $typeProviders = $this->container->getAllSingleton($type);
        foreach($typeProviders as $class) {
            if(!$class::usable()) {
                continue;
            }
            $name = $class::getName();
            if(!array_search($name, $toRet)) {
                $toRet[$class] = $name;
            }
        }
        
        return $toRet;
    }
    
    public function runSelectDrivers(\Fossil\Forms\DriverSelection $driverForm) {
        if(!file_exists("temp_settings.yml") || (filemtime("settings.yml") > filemtime("temp_settings.yml"))) {
            copy("settings.yml", "temp_settings.yml");
        }
        $sideSet = new Settings($this->container, "temp_settings.yml");

        if(!$driverForm->isSubmitted()) {
            // Build the list of possible drivers
            $driverForm->setFieldOptions('cacheDriver', $this->getClassDrivers('Cache'));
            $driverForm->setFieldOptions('templateDriver', $this->getClassDrivers('Renderer'));
            $driverForm->setFieldOptions('dbDriver', $this->getClassDrivers('Database'));
            // Prepopulate the form with our existings values if temp_settings.yml exists
            if($sideSet->isBootstrapped()) {
                $driverSet = $sideSet->get('Fossil', 'Drivers', array());
                if(isset($driverSet['Cache'])) {
                    $driverForm->cacheDriver = $driverSet['Cache']['Class'];
                }
                if(isset($driverSet['Renderer'])) {
                    $driverForm->templateDriver = $driverSet['Renderer']['Class'];
                }
                if(isset($driverSet['Database'])) {
                    $driverForm->dbDriver = $driverSet['Database']['Class'];
                }
            }
            // And render
            return $this->templateResponse("setup/selectDrivers");
        }
        
        // Otherwise, set the drivers in the temp file
        $drivers = $sideSet->get('Fossil', 'Drivers', array());
        $drivers['Cache'] = array('Class' => $driverForm->cacheDriver);
        $drivers['Database'] = array('Class' => $driverForm->dbDriver);
        $drivers['Renderer'] = array('Class' => $driverForm->templateDriver);
        $sideSet->set('Fossil', 'Drivers', $drivers);
        
        // And forward to configureDrivers
        return $this->redirectResponse("?controller=setup&action=configureDrivers");
    }
    
    public function runConfigureDrivers() {
        $sideSet = new Settings($this->container, "temp_settings.yml");
        
        $drivers = $sideSet->get('Fossil', 'Drivers', array());
        
        if(!isset($drivers['Cache']) || !isset($drivers['Database']) || !isset($drivers['Renderer'])) {
            return $this->redirectResponse("?ocntroller=setup&action=selectDrivers");
        }
        
        $cacheFormName = $drivers['Cache']['Class']::getFormName();
        $rendererFormName = $drivers['Renderer']['Class']::getFormName();
        $dbFormName = $drivers['Database']['Class']::getFormName();
        
        $dbForm = $dbFormName ? $this->forms->get($dbFormName) : null;
        $cacheForm = $cacheFormName ? $this->forms->get($cacheFormName) : null;
        $rendererForm = $rendererFormName ? $this->forms->get($rendererFormName) : null;
        
        $submitted = true;
        if($dbForm && !$dbForm->isSubmitted()) {
            $submitted = false;
        }
        if($cacheForm && !$cacheForm->isSubmitted()) {
            $submitted = false;
        }
        if($rendererForm && !$rendererForm->isSubmitted()) {
            $submitted = false;
        }
        
        if($submitted) {
            // Save settings
            if($dbForm) {
                $drivers['Database']['Config'] = $dbForm->toConfig();
            }
            if($cacheForm) {
                $drivers['Cache']['Config'] = $cacheForm->toConfig();
            }
            if($rendererForm) {
                $drivers['Renderer']['Config'] = $rendererForm->toConfig();
            }
            $sideSet->set('Fossil', 'Drivers', $drivers);
            // And push on to the next step
            // TODO: Edit this to automatically jump to the next step
            return $this->redirectResponse("?controller=setup&action=finished");
        }
        
        // Otherwise, render the form
        $data = array('dbForm' => $dbForm ? $dbForm->getIdentifier() : null,
                      'cacheForm' => $cacheForm ? $cacheForm->getIdentifier() : null,
                      'rendererForm' => $rendererForm ? $rendererForm->getIdentifier() : null);
        
        return $this->templateResponse("setup/configDrivers", $data);
    }
    
    public function runFinished() {
        rename('temp_settings.yml', 'settings.yml');
        return $this->templateResponse("setup/finished");
    }
}
