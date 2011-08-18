<?php

namespace Fossil\Renderers;

use Fossil\Responses\BaseResponse,
    Fossil\OM;

/**
 * Description of SmartyRenderer
 *
 * @author lachlan
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
            $this->smarty->addTemplateDir($root . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "smarty");
        }
    }
    
    public function render(BaseResponse $resp) {
        $tpl = $this->smarty->createTemplate($resp->template . ".tpl");
        $tpl->assign('errors', OM::Error()->getLog());
        $tpl->assign('title', $resp->template);
        foreach($resp->data as $key => $val) {
            $tpl->assign($key, $val);
        }
        $tpl->display();
    }
}

?>
