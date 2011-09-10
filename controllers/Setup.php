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
    public function runIndex(BaseRequest $req) {
        return new TemplateResponse("setup/index");
    }
    
    public function runCheckCompatibility(BaseRequest $req) {
        // Dependency data
        $data = array();
        // TODO: Have some way of genericizing this
        $deps['Required'] = array(
            'PHP' => array('Version' => '>= 5.3', 'URL' => 'http://www.php.net', 'Type' => 'Runtime', 'Test' => function() {
                if(!defined('PHP_VERSION_ID'))
                    return false;
                if(PHP_VERSION_ID < 50300)
                    return false;
                return true;
            }),
            'Smarty' => array('Version' => '>= 3', 'URL' => 'http://www.smarty.net', 'Type' => 'Templating Engine', 'Test' => function() {
                // TODO: Looser coupling for Smarty
                if(!file_exists('libs/smarty/distribution/libs/Smarty.class.php'))
                    return false;
                require_once('libs/smarty/distribution/libs/Smarty.class.php');
                
                if(!defined('\Smarty::SMARTY_VERSION'))
                    return false;
                // Use this test to account for SVN versions of Smarty using Smarty3 as opposed to Smarty-3
                $placement = strpos(\Smarty::SMARTY_VERSION, '3');
                if($placement && $placement <= 8)
                        return true;
                return false;
            })
        );
        $deps['Optional'] = array(
            'PHPUnit' => array('Version' => '>= 3.5', 'URL' => 'http://www.phpunit.de', 'Type' => 'Testing', 'Test' => function() {
                if(!file_exists(stream_resolve_include_path('PHPUnit/Autoload.php')))
                    return false;
                require_once('PHPUnit/Autoload.php');
                $version = \PHPUnit_Runner_Version::id();
                $version_comp = explode(".", $version);
                if($version_comp[0] == 3 && $version_comp[1] >= 5)
                    return true;
                return false;
            })
        );
        
        // Simple var to keep track of whether all required dependencies were met
        $result = true;
        // Compile the above dependency arrays into tested instances
        foreach($deps as $typeKey => &$typeArr) {
            foreach($typeArr as &$dep) {
                $dep['Result'] = $dep['Test']();
                if($typeKey == 'Required' && !$dep['Result'])
                    $result = false;
            }
        }
        $data = array_merge($data, $deps);
        
        $data['allOK'] = $result;
        
        return new TemplateResponse("setup/checkCompat", $data);
    }

    private function getClassDrivers($type) {
        $toRet = array();
        foreach(OM::getAll($type) as $classArr) {
            $class = $classArr['fqcn'];
            if(\is_subclass_of($class, '\\Fossil\\Interfaces\\IDriver')) {
                // Check that it's usable
                if(!$class::usable())
                    continue;
                // Grab it's name
                $nameAnno = OM::Annotations()->getClassAnnotations($class, 'F:Object');
                $name = $nameAnno[0]->name;
                $toRet[$name] = $class::getName();
            }
        }
        return $toRet;
    }
    
    public function runSelectDrivers(BaseRequest $req) {
        $driverForm = OM::Form("DriverSelection");
        $sideSet = new Settings("temp_settings.yml");
        
        if(!$driverForm->isSubmitted()) {
            // Build the list of possible drivers
            $driverForm->setFieldOptions('cacheDriver', $this->getClassDrivers('Cache'));
            $driverForm->setFieldOptions('templateDriver', $this->getClassDrivers('Renderer'));
            $driverForm->setFieldOptions('dbDriver', $this->getClassDrivers('Database'));
            // Prepopulate the form with our existings values if temp_settings.yml exists
            if($sideSet->bootstrapped()) {
                $cacheSet = $sideSet->get('Fossil', 'cache');
                if($cacheSet) {
                    $driverForm->cacheDriver = $cacheSet['driver'];
                }
                $tmplSet = $sideSet->get('Fossil', 'renderer');
                if($tmplSet) {
                    $driverForm->templateDriver = $tmplSet['driver'];
                }
                $dbSet = $sideSet->get('Fossil', 'database');
                if($tmplSet) {
                    $driverForm->dbDriver = $dbSet['driver'];
                }
            }
            // And render
            return new TemplateResponse("setup/selectDrivers");
        }
        
        // Otherwise, set the drivers in the temp file
        $sideSet->set('Fossil', 'cache', array('driver' => $driverForm->cacheDriver));
        $sideSet->set('Fossil', 'database', array('driver' => $driverForm->dbDriver));
        $sideSet->set('Fossil', 'renderer', array('driver' => $driverForm->templateDriver));
        
        // And forward to configureDrivers
        return new RedirectResponse("?controller=setup&action=configureDrivers");
    }
    
    public function runConfigureDrivers(BaseRequest $req) {
        $sideSet = new Settings("temp_settings.yml");
        
        $cacheSet = $sideSet->get('Fossil', 'cache');
        $rendererSet = $sideSet->get('Fossil', 'renderer');
        $dbSet = $sideSet->get('Fossil', 'database');
        
        if(!$cacheSet || !$rendererSet || !$dbSet)
            return new RedirectResponse("?controller=setup&action=selectDrivers");
        
        $cacheDriver = OM::getSpecific("Cache", $cacheSet['driver']);
        $rendererDriver = OM::getSpecific("Renderer", $rendererSet['driver']);
        $dbDriver = OM::getSpecific("Database", $dbSet['driver']);
        
        $dbForm = $dbDriver['fqcn']::getForm();
        $cacheForm = $cacheDriver['fqcn']::getForm();
        $rendererForm = $rendererDriver['fqcn']::getForm();
        
        $submitted = true;
        if($dbForm && !$dbForm->isSubmitted())
            $submitted = false;
        if($cacheForm && !$cacheForm->isSubmitted())
            $submitted = false;
        if($rendererForm && !$rendererForm->isSubmitted())
            $submitted = false;
        
        if($submitted) {
            // Save settings
            if($dbForm) {
                $sideSet['Fossil']['database']['config'] = $dbForm->toConfig();
            }
            if($cacheForm) {
                $sideSet['Fossil']['cache']['config'] = $cacheForm->toConfig();
            }
            if($rendererForm) {
                $sideSet['Fossil']['renderer']['config'] = $rendererForm->toConfig();
            }
            // And push on to the next step
            return new RedirectResponse("?controller=setup&action=finished");
        }
        
        // Otherwise, render the form
        $data = array('dbForm' => $dbForm ? $dbForm->getIdentifier() : null,
                      'cacheForm' => $cacheForm ? $cacheForm->getIdentifier() : null,
                      'rendererForm' => $rendererForm ? $rendererForm->getIdentifier() : null);
        
        return new TemplateResponse("setup/configDrivers", $data);
    }
    
    public function runFinished(BaseRequest $req) {
        rename('temp_settings.yml', 'settings.yml');
        return new TemplateResponse("setup/finished");
    }
}

?>
