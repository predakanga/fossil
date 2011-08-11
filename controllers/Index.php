<?php

namespace Fossil\Controllers;

use Fossil\OM;

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
    
    public function runStore(\Fossil\Requests\BaseRequest $req) {
        OM::Cache()->set("test", $req->args['value']);
        return NULL;
    }
    
    public function runRetrieve() {
        return new \Fossil\Responses\DataResponse(array("item" => OM::Cache("test")));
    }
}
?>
