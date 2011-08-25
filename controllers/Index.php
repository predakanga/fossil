<?php

namespace Fossil\Controllers;

use Fossil\OM,
    Fossil\Responses\TemplateResponse,
    Fossil\Responses\RedirectResponse;

/**
 * Description of Default
 *
 * @author predakanga
 */
class Index extends AutoController {
    public function indexAction() {
        return "index";
    }
    
    public function runIndex() {
        return new TemplateResponse("index", array("label" => "default"));
    }
    
    public function runOther() {
        return new RedirectResponse("index.php");
    }
    
    public function runStore(\Fossil\Requests\BaseRequest $req) {
        OM::Cache()->set("test", $req->args['value']);
        return NULL;
    }
    
    public function runRetrieve() {
        return new TemplateResponse("retrieve", array("item" => OM::Cache("test")));
    }
}
?>
