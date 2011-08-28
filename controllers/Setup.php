<?php

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
}

?>
