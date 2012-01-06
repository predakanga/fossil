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
    Fossil\OM,
    Fossil\Interfaces\ITemplated,
    Fossil\Models\Model,
    Fossil\Models\PaginationProxy,
    Doctrine\Common\Collections\Collection;

/**
 * Description of SmartyRenderer
 *
 * @author predakanga
 * @F:DefaultProvider
 */
class SmartyRenderer extends BaseRenderer {
    /**
     *
     * @var \Smarty
     */
    private $smarty;
    /**
     * @F:Inject("Filesystem")
     * @var Fossil\Filesystem
     */
    protected $fs;
    /**
     * @F:Inject("Dispatcher")
     * @var Fossil\Dispatcher
     */
    protected $dispatcher;
    /**
     * @F:Inject("FormManager")
     * @var Fossil\Forms\FormManager
     */
    protected $forms;
    /**
     * @F:Inject("ErrorManager")
     * @var Fossil\ErrorManager
     */
    protected $errorMgr;
    
    public static function getName() { return "Smarty"; }
    public static function getVersion() { return 1.0; }
    public static function usable() { /* TODO: Real test here */ return true; }
    public static function getFormName() { return "SmartyConfig"; }
    
    protected function getDefaultConfig() {
        return array('useTidy' => false);
    }
    
    public function __construct($container) {
        parent::__construct($container);
        
        require_once $this->fs->fossilRoot() . D_S . "libs/smarty/distribution/libs/Smarty.class.php";
        $this->smarty = new \Smarty();
        foreach($this->fs->roots() as $root) {
            $this->smarty->addTemplateDir($root . D_S . "views" . D_S . "smarty");
        }
        $this->smarty->compile_dir = $this->fs->tempDir() . D_S . "templates_c";
        $this->smarty->registerPlugin('function', 'form', array($this, 'formFunction'));
        $this->smarty->registerPlugin('function', 'display', array($this, 'displayFunction'));
        $this->smarty->registerPlugin('function', 'paginate', array($this, 'paginateFunction'));
        $this->smarty->registerPlugin('modifier', 'bbdecode', array($this, 'bbdecodeModifier'));
        $this->smarty->registerPlugin('modifier', 'date_interval_format', array($this, 'dateIntervalFmtModifier'));
        $this->smarty->registerPlugin('compiler', 'use', array($this, 'useFunction'));
        $this->smarty->registerPlugin('block', 'link', array($this, 'linkFunction'));
        $this->smarty->registerPlugin('block', 'link_page', array($this, 'linkPageFunction'));
        $this->smarty->registerPlugin('block', 'multiform', array($this, 'multiformFunction'));
        if($this->config['useTidy']) {
            $this->smarty->registerFilter('output', array($this, "smarty_outputfilter_tidyrepairhtml"));
        }
        $this->smarty->registerResource("fossil", array(array($this, "smarty_resource_get_template"),
                                                        array($this, "smarty_resource_get_timestamp"),
                                                        array($this, "smarty_resource_get_secure"),
                                                        array($this, "smarty_resource_get_trusted")));
    }
    
    protected function setDefaultVariables($tpl) {
        $errorLog = $this->errorMgr->getLog();
        $tpl->assign('errors', $errorLog['errors']);
        $tpl->assign('now', new \DateTime());
    }
    
    public function render($templateName, $templateData) {
        if(strpos($templateName, "fossil:") !== 0) {
            $templateName = $templateName . ".tpl";
        }
        
        $tpl = $this->smarty->createTemplate($templateName);
        $tpl->assign('title', $templateName);
        $this->setDefaultVariables($tpl);
        foreach($templateData as $key => $val) {
            $tpl->assign($key, $val);
        }
        $tpl->display();
    }
    
    function useFunction($params, $smarty) {
        if(!isset($params['fqcn'])) {
            throw new \Exception("{use} requires a model");
        }
        if(!isset($params['as'])) {
            throw new \Exception("{use} requires an 'as'");
        }
        
        $params['fqcn'] = trim($params['fqcn'], "'\"");
        $params['as'] = trim($params['as'], "'\"");
        
        return "<?php
            use {$params['fqcn']} as {$params['as']};
        ?>";
    }
    
    function formFunction($params, $smarty) {
        // First of all, set up an array with the appropriate data
        $data = array();
        // Check whether we're in a multiform tag
        $data['multiform'] = false;
        foreach($smarty->_tag_stack as $tag) {
            if($tag[0] == "multiform") {
                $data['multiform'] = true;
            }
        }
        $form = $this->forms->get($params['name']);
        
        $data['method'] = "post";
        if(isset($params['method'])) {
            $data['method'] = $params['method'];
        }
        
        $data['action'] = htmlentities($_SERVER['REQUEST_URI']);
        if(isset($params['action'])) {
            $data['action'] = htmlentities($params['action']);
        } else {
            $curRequest = $this->dispatcher->getCurrentRequest();
            $controller = $curRequest->controller;
            $f_action = $curRequest->action;
            if(isset($params['fossil_controller'])) {
                $controller = $params['fossil_controller'];
                $f_action = NULL;
            }
            if(isset($params['fossil_action'])) {
                $f_action = $params['fossil_action'];
            }
            
            // Build the query string
            $action = "?controller=" . $controller;
            if($f_action) {
                $action .= "&amp;action=" . $f_action;
            }
            $data['action'] = $action;
        }
        
        $data['form_id'] = $form->getIdentifier();
        
        $data['fields'] = array();
        $data['has_file'] = false;
        foreach($form->getFields() as $name => $settings) {
            $fieldData = array();
            $fieldData['type'] = $settings['type'];
            if($fieldData['type'] == "file") {
                $data['has_file'] = true;
            }
            $fieldData['name'] = $settings['fieldName'];
            $fieldData['label'] = $settings['label'];
            if(isset($form->$name)) {
                $fieldData['value'] = $form->$name;
            }
            $fieldData['options'] = array();
            if($settings['type'] == 'select') {
                foreach($settings['options'] as $value => $label) {
                    $fieldData['options'][] = array('value' => $value, 'label' => $label);
                }
            }
            $data['fields'][] = $fieldData;
        }
        
        $formTpl = $this->smarty->createTemplate("fossil:forms" . D_S . $form->getTemplate(), $data);
        return $formTpl->fetch();
    }
    
    function bbdecodeModifier($input) {
        return $this->container->get("BBCode")->decode($input);
    }
    
    function dateIntervalFmtModifier(\DateInterval $interval) {
        $doPlural = function($nb,$str){return $nb>1?$str.'s':$str;}; // adds plurals
   
        $format = array();
        if($interval->y !== 0) {
            $format[] = "%y ".$doPlural($interval->y, "year");
        }
        if($interval->m !== 0) {
            $format[] = "%m ".$doPlural($interval->m, "month");
        }
        if($interval->d !== 0) {
            $weeks = (int)($interval->d / 7);
            $days = $interval % 7;
            
            if($weeks) {
                $format[] = "$weeks " . $doPlural($weeks, "week");
                if($days) {
                    $format[] = "$days " . $doPlural($days, "day");
                }
            } else {
                $format[] = "%d ".$doPlural($interval->d, "day");
            }
        }
        if($interval->h !== 0) {
            $format[] = "%h ".$doPlural($interval->h, "hour");
        }
        if($interval->i !== 0) {
            $format[] = "%i ".$doPlural($interval->i, "minute");
        }
        if($interval->s !== 0) {
            if(!count($format)) {
                return "less than a minute ago";
            } else {
                $format[] = "%s ".$doPlural($interval->s, "second");
            }
        }

        // We use the two biggest parts
        if(count($format) > 1) {
            $format = array_shift($format)." and ".array_shift($format);
        } else {
            $format = array_pop($format);
        }

        $retStr = $interval->format($format); 
        if($interval->invert) {
            return $retStr . " ago";
        } else {
            return "In " . $retStr;
        }
    }
    
    function multiformFunction($params, $content, $smarty, &$repeat) {
        // Only process on the closing tag
        if($repeat) {
            return;
        }
        $method = "post";
        if(isset($params['method'])) {
            $method = $params['method'];
        }
        
        $action = htmlentities($_SERVER['REQUEST_URI']);
        if(isset($params['action'])) {
            $action = htmlentities($params['action']);
        } else {
            $curRequest = $this->dispatcher->getCurrentRequest();
            $controller = $curRequest->controller;
            $f_action = $curRequest->action;
            if(isset($params['fossil_controller'])) {
                $controller = $params['fossil_controller'];
                $f_action = NULL;
            }
            if(isset($params['fossil_action'])) {
                $f_action = $params['fossil_action'];
            }
            
            // Build the query string
            $action = "?controller=" . $controller;
            if($f_action) {
                $action .= "&amp;action=" . $f_action;
            }
        }
        
        $has_file = false;
        if(strstr($content, "type=\"file\"")) {
            $has_file = true;
        }
        
        if(!$has_file) {
            $preamble = "<form method=\"$method\" action=\"$action\">";
        } else {
            $preamble = "<form method=\"$method\" action=\"$action\" enctype=\"multipart/form-data\">";
        }
        
        $postamble = "<input type=\"submit\" value=\"Submit\" />\n</form>";
        return $preamble . "\n" .
               $content . "\n" .
               $postamble;
    }
    
    function linkFunction($params, $content, $smarty, &$repeat) {
        // Set up default params
        $curRequest = $this->dispatcher->getCurrentRequest();
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
            $target['action'] = $this->_new("Controller", $target['controller'])->indexAction();
        }
        if(isset($params['cssClass'])) {
            $classStr = " class=\"" . $params['cssClass'] . "\"";
            unset($params['cssClass']);
        }
        $fragment = "";
        if(isset($params['fragment'])) {
            $fragment = "#" . $params['fragment'];
            unset($params['fragment']);
        }
        
        // TODO: Use a router-supplied mapping for this
        $url = $_SERVER['PHP_SELF'] . "?";
        $url .= "controller=" . urlencode($target['controller']);
        $url .= "&amp;action=" . urlencode($target['action']);
        foreach($params as $key => $value) {
            $url .= "&amp;" . urlencode($key) . "=" . urlencode($value);
        }
        $url .= $fragment;
        
        return "<a href=\"$url\"$classStr>" . $content . "</a>";
    }
    
    function linkPageFunction($params, $content, $smarty, &$repeat) {
        // Generate our current arguments
        $req = $this->dispatcher->getCurrentRequest();
        if($req->controller) {
            $toAdd['controller'] = $req->controller;
        }
        if($req->action) {
            $toAdd['action'] = $req->action;
        }
        $toAdd += $req->args;
        
        // Add the page parameter
        if(isset($params['page'])) {
            $toAdd['page'] = $params['page'];
        } else {
            $toAdd['page'] = 1;
        }
        
        // And call the normal link function
        return $this->linkFunction($toAdd, $content, $smarty, $repeat);
    }
    
    function displayFunction($params, $smarty) {
        assert(isset($params['source']));
        $content = "";
        $mode = "default";
        if(isset($params['mode'])) {
            $mode = $params['mode'];
        }
        $index = 0;
        if(isset($params['indexFrom'])) {
            $index = $params['indexFrom'];
        }
        
        if(isset($params['header'])) {
            $tpl = $smarty->createTemplate($params['header'], array());
            $tpl->assign($smarty->getTemplateVars());
            $content .= $tpl->fetch();
        }
        
        $source = null;
        if($params['source'] instanceof Model) {
            $source = array($params['source']);
        } elseif(is_array($params['source']) || $params['source'] instanceof Collection) {
            $source = $params['source'];
        }
        
        $curTpl = null;
        $curTplName = null;
        
        foreach($source as $item) {
            $index++;
            $tplName = null;
            if($item instanceof ITemplated) {
                $tplName = $item->getTemplateName($mode);
            }
            if(!$tplName) {
                $tplName = $params['template'];
            }
            if(!$tplName) {
                throw new \Exception("Display called with no template on an object of type " . get_class($item) . ", which doesn't implement ITemplated");
            }
            if($tplName != $curTplName) {
                $curTpl = $smarty->createTemplate($tplName);
                // Copy in vars from the current template
                $curTpl->assign($smarty->getTemplateVars());
                $curTplName = $tplName;
            }
            $curTpl->assign(array('item' => $item, 'index' => $index));
            $content .= $curTpl->fetch();
        }
        
        if(isset($params['footer'])) {
            $tpl = $smarty->createTemplate($params['footer'], array());
            $tpl->assign($smarty->getTemplateVars());
            $content .= $tpl->fetch();
        }
        
        return $content;
    }
    
    function paginateFunction($params, $smarty) {
        $pageSize = 10;
        if(isset($params['pageSize'])) {
            $pageSize = $params['pageSize'];
        }
        $page = 1;
        // Get the page from the request by default
        $req = $this->dispatcher->getCurrentRequest();
        if(isset($req->args['page'])) {
            $page = $req->args['page'];
        }
        if(isset($params['page'])) {
            $page = $params['page'];
        }
        $showPageList = true;
        if(isset($params['pageList'])) {
            $showPageList = $params['pageList'];
        }
        $pageListTpl = "fossil:pagination/pageList";
        if(isset($params['pageListTpl'])) {
            $pageListTpl = $params['pageListTpl'];
        }
        $template = null;
        if(isset($params['template'])) {
            $template = $params['template'];
        }
        $src = $params['source'];
        
        if($src instanceof Model) {
            $entity = $params['source'];
            $field = $params['field'];
            $paginatedProxy = $entity->paginate($field, $pageSize);
        } else {
            $paginatedProxy = new PaginationProxy($src);
        }

        $items = $paginatedProxy->getPage($page);
        
        $params['source'] = $items;
        $index = $pageSize * ($page - 1);
        $params['indexFrom'] = $index;
        
        $content = $this->displayFunction($params, $smarty);
        
        if($showPageList) {
            // Print the page list
            $args = array('itemCount' => $paginatedProxy->getItemCount(),
                          'pageCount' => $paginatedProxy->getPageCount(),
                          'curPage' => $page);
            $tpl = $smarty->createTemplate($pageListTpl, $args);
            $content .= $tpl->fetch();
        }
        
        return $content;
    }
    
    function smarty_outputfilter_tidyrepairhtml ($source, $smarty) {
        if(extension_loaded('tidy')) {                    
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
            $plugin = $this->container->get("Plugins")->get($pluginName);
            return $plugin['root'] . D_S . $suffix;
        } else {
            // Check real roots first
            foreach(array_reverse($this->fs->roots(false)) as $root) {
                if(file_exists($root . D_S . $suffix)) {
                    return $root . D_S . $suffix;
                }
            }
            // Then plugin roots as a fallback
            foreach($this->fs->pluginRoots() as $root) {
                if(file_exists($root . D_S . $suffix)) {
                    return $root . D_S . $suffix;
                }
            }
        }
        // TODO: Throw exception
    }
    
    function smarty_resource_get_template($tpl_name, &$tpl_source, $smarty) {
        $filePath = $this->resolve_template_name($tpl_name);
        
        if(!file_exists($filePath)) {
            return false;
        }
        
        $tpl_source = file_get_contents($filePath);
        return true;
    }
    
    function smarty_resource_get_timestamp($tpl_name, &$tpl_timestamp, $smarty) {
        $filePath = $this->resolve_template_name($tpl_name);
        
        if(!file_exists($filePath)) {
            return false;
        }
        
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
