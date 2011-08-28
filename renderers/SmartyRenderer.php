<?php

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
    
    public function __construct() {
        require_once("libs/smarty/distribution/libs/Smarty.class.php");
        $this->smarty = new \Smarty();
        foreach(OM::FS()->roots() as $root) {
            $this->smarty->addTemplateDir($root . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "smarty");
        }
        $this->smarty->registerPlugin('function', 'form', array($this, 'formFunction'));
        $this->smarty->registerPlugin('block', 'link', array($this, 'linkFunction'));
        $this->smarty->registerFilter('output', array($this, "smarty_outputfilter_tidyrepairhtml"));
    }
    
    public function render($templateName, $templateData) {
        $tpl = $this->smarty->createTemplate($templateName . ".tpl");
        $tpl->assign('errors', OM::Error()->getLog());
        $tpl->assign('title', $templateName);
        foreach($templateData as $key => $val) {
            $tpl->assign($key, $val);
        }
        $tpl->display();
    }
    
    
    function formFunction($params, $smarty) {
        // First of all, set up an array with the appropriate data
        $data = array();
        $form = OM::Form($params['name']);
        
        $data['method'] = "POST";
        if(isset($params['method']))
            $data['method'] = $params['method'];
        
        if(isset($params['action']))
            $data['action'] = $params['action'];
        
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
        
        $formTpl = $this->smarty->createTemplate("forms" . DIRECTORY_SEPARATOR . $form->getTemplate() . ".tpl", $data);
        return $formTpl->fetch();
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
        $url .= "&action=" . urlencode($target['action']);
        foreach($params as $key => $value) {
            $url .= "&" . urlencode($key) . "=" . urlencode($value);
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
}

?>
