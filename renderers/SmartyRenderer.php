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
class SmartyRenderer {
    /**
     *
     * @var \Smarty
     */
    private $smarty;
    
    public function __construct() {
        require_once("libs/smarty/distribution/libs/Smarty.class.php");
        $this->smarty = new \Smarty();
        foreach(OM::FS()->roots() as $root) {
            $this->smarty->addTemplateDir($root . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "smarty");
        }
        $this->smarty->registerPlugin('function', 'form', array($this, 'formFunction'));
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
        foreach($form->getFields() as $name => $type) {
            $fieldData = array();
            $fieldData['type'] = $type;
            $fieldData['name'] = $name;
            if(isset($form->$name))
                $fieldData['value'] = $form->$name;
            $data['fields'][] = $fieldData;
        }
        
        $formTpl = $this->smarty->createTemplate("forms" . DIRECTORY_SEPARATOR . $form->getTemplate() . ".tpl", $data);
        return $formTpl->fetch();
    }
}

?>
