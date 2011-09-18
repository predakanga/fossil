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
 * @subpackage Renderers
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil\Renderers;

use Fossil\Responses\BaseResponse,
    Fossil\OM;

/**
 * Description of SmartyRenderer
 *
 * @author predakanga
 * @F:Object(type = "Renderer", name = "Smarty")
 */
class SmartyRenderer extends BaseRenderer {
    /**
     *
     * @var \Smarty
     */
    private $smarty;
    
    public static function getName() { return "Smarty"; }
    public static function getVersion() { return 1.0; }
    public static function usable() { /* TODO: Real test here */ return true; }
    public static function getForm() { return OM::Form("SmartyConfig"); }
    
    protected function getDefaultConfig() {
        $config = parent::getDefaultConfig();
        if(!$config) {
            return array('useTidy' => false);
        }
        
        return $config;
    }
    
    public function __construct($config = null) {
        if(!$config)
            $config = $this->getDefaultConfig();
        
        parent::__construct($config);
        require_once(OM::FS()->fossilRoot() . D_S . "libs/smarty/distribution/libs/Smarty.class.php");
        $this->smarty = new \Smarty();
        foreach(OM::FS()->roots() as $root) {
            $this->smarty->addTemplateDir($root . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "smarty");
        }
        $this->smarty->compile_dir = OM::FS()->tempDir() . D_S . "templates_c";
        $this->smarty->registerPlugin('function', 'form', array($this, 'formFunction'));
        $this->smarty->registerPlugin('block', 'link', array($this, 'linkFunction'));
        $this->smarty->registerPlugin('block', 'multiform', array($this, 'multiformFunction'));
        if($config['useTidy'])
            $this->smarty->registerFilter('output', array($this, "smarty_outputfilter_tidyrepairhtml"));
        $this->smarty->registerResource("fossil", array(array($this, "smarty_resource_get_template"),
                                                        array($this, "smarty_resource_get_timestamp"),
                                                        array($this, "smarty_resource_get_secure"),
                                                        array($this, "smarty_resource_get_trusted")));
    }
    
    protected function setDefaultVariables($tpl) {
        $tpl->assign('errors', OM::Error()->getLog());
    }
    
    public function render($templateName, $templateData) {
        if(strpos($templateName, "fossil:") !== 0)
            $templateName = $templateName . ".tpl";
        
        $tpl = $this->smarty->createTemplate($templateName);
        $tpl->assign('title', $templateName);
        $this->setDefaultVariables($tpl);
        foreach($templateData as $key => $val) {
            $tpl->assign($key, $val);
        }
        $tpl->display();
    }
    
    
    function formFunction($params, $smarty) {
        // First of all, set up an array with the appropriate data
        $data = array();
        // Check whether we're in a multiform tag
        $data['multiform'] = false;
        foreach($smarty->_tag_stack as $tag) {
            if($tag[0] == "multiform")
                $data['multiform'] = true;
        }
        $form = OM::Form($params['name']);
        
        $data['method'] = "post";
        if(isset($params['method']))
            $data['method'] = $params['method'];
        
        $data['action'] = htmlentities($_SERVER['REQUEST_URI']);
        if(isset($params['action']))
            $data['action'] = htmlentities($params['action']);
        
        $data['form_id'] = $form->getIdentifier();
        
        $data['fields'] = array();
        foreach($form->getFields() as $name => $settings) {
            $fieldData = array();
            $fieldData['type'] = $settings['type'];
            $fieldData['name'] = $settings['fieldName'];
            $fieldData['label'] = $settings['label'];
            if(isset($form->$name))
                $fieldData['value'] = $form->$name;
            $fieldData['options'] = array();
            if($settings['type'] == 'select')
                foreach($settings['options'] as $value => $label) {
                    $fieldData['options'][] = array('value' => $value, 'label' => $label);
                }
            $data['fields'][] = $fieldData;
        }
        
        $formTpl = $this->smarty->createTemplate("fossil:forms" . DIRECTORY_SEPARATOR . $form->getTemplate(), $data);
        return $formTpl->fetch();
    }
    
    function multiformFunction($params, $content, $smarty, &$repeat) {
        // Only process on the closing tag
        if($repeat)
            return;
        $method = "post";
        if(isset($params['method']))
            $method = $params['method'];
        
        $action = htmlentities($_SERVER['REQUEST_URI']);
        if(isset($params['action'])) {
            $action = htmlentities($params['action']);
        }
        
        $preamble = "<form method=\"$method\" action=\"$action\">";
        $postamble = "<input type=\"submit\" value=\"Submit\" />\n</form>";
        return $preamble . "\n" .
               $content . "\n" .
               $postamble;
    }
    
    function linkFunction($params, $content, $smarty, &$repeat) {
        // Set up default params
        $curRequest = OM::Dispatcher()->getCurrentRequest();
        $target = array('controller' => $curRequest->controller);
        $classStr = "";
        
        if(isset($params['controller'])) {
            $target['controller'] = $params['controller'];
            unset($params['controller']);
        }
        if(isset($params['action'])) {
            $target['action'] = $params['action'];
            unset($params['action']);
        } else {
            $target['action'] = OM::Controller($target['controller'])->indexAction();
        }
        if(isset($params['cssClass'])) {
            $classStr = " class=\"" . $params['cssClass'] . "\"";
            unset($params['cssClass']);
        }
        
        // TODO: Use a router-supplied mapping for this
        $url = $_SERVER['PHP_SELF'] . "?";
        $url .= "controller=" . urlencode($target['controller']);
        $url .= "&amp;action=" . urlencode($target['action']);
        foreach($params as $key => $value) {
            $url .= "&amp;" . urlencode($key) . "=" . urlencode($value);
        }
        
        return "<a href=\"$url\"$classStr>" . $content . "</a>";
    }
    
    function smarty_outputfilter_tidyrepairhtml ($source, $smarty)
    {
        if(extension_loaded('tidy'))
        {
            /*
            $tidyoptions = array("indent-spaces" => 4, 
                                 "wrap" => 120, 
                                 "indent" =>  auto,
                                 "tidy-mark" => true, 
                                 "show-body-only" => true, 
                                 "force-output" => true,
                                 "output-xhtml", true,
                                 "clean" => true,
                                 "drop-proprietary-attributes" => true,
                                 "drop-font-tags" => true,
                                 "drop-empty-paras" => true,
                                 "hide-comments" => false,
                                 "join-classes" => false,
                                 "join-styles" => false);   
            */                     
            $opts = array("output-xhtml" => true,
                          "indent-spaces" => 4, 
                          "wrap" => 200, 
                          "indent" => true);                    

            
            $tidy = new \tidy();
            $tidy->parseString($source, $opts);
            $tidy->cleanRepair();
            $source = (string)$tidy;

        }
        return $source;
    }
    
    function resolve_template_name($name) {
        $pluginName = NULL;
        if(strpos($name, ":") !== FALSE) {
            $pluginName = substr($name, 0, strpos($name, ":"));
            $name = substr($name, strpos($name, ":")+1);
        }
        $suffix = "views" . D_S . "smarty" . D_S . implode(D_S, explode("\\", $name)) . ".tpl";
        
        if($pluginName) {
            $plugin = OM::Plugins($pluginName);
            return $plugin['root'] . D_S . $suffix;
        } else {
            // Check real roots first
            foreach(array_reverse(OM::FS()->roots(false)) as $root) {
                if(file_exists($root . D_S . $suffix))
                    return $root . D_S . $suffix;
            }
            // Then plugin roots as a fallback
            foreach(OM::FS()->pluginRoots() as $root) {
                if(file_exists($root . D_S . $suffix))
                    return $root . D_S . $suffix;
            }
        }
        // TODO: Throw exception
    }
    
    function smarty_resource_get_template($tpl_name, &$tpl_source, $smarty) {
        $filePath = $this->resolve_template_name($tpl_name);
        
        if(!file_exists($filePath))
            return false;
        
        $tpl_source = file_get_contents($filePath);
        return true;
    }
    
    function smarty_resource_get_timestamp($tpl_name, &$tpl_timestamp, $smarty) {
        $filePath = $this->resolve_template_name($tpl_name);
        
        if(!file_exists($filePath))
            return false;
        
        $tpl_timestamp = filemtime($filePath);
        return true;
    }
    
    function smarty_resource_get_secure($tpl_name, $smarty) {
        return true;
    }
    
    function smarty_resource_get_trusted($tpl_name, $smarty) {
        return;
    }
}

?>
