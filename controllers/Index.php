<?php

namespace Fossil\Controllers;

/**
 * Description of Default
 *
 * @author lachlan
 */
class Index extends AutoController {
    public function indexAction() {
        return "index";
    }
    
    public function runIndex() {
        return new \Fossil\Responses\DataResponse(array("label" => "default"));
    }
    
    public function runOther() {
        return new \Fossil\Responses\RedirectResponse("index.php");
    }
}
?>
