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
        $inputForm = OM::Form("ItemStorage");
        
        // Show the form if it's not been submitted or is invalid
        if(!$inputForm->isSubmitted() || !$inputForm->isValidSubmission())
            return new TemplateResponse("item_input");
        
        // Save it if it has
        OM::Cache()->set("test", $inputForm->item);
        
        // And redirect to the visibility page
        return new RedirectResponse("index.php?controller=index&action=retrieve");
    }
    
    public function runRetrieve() {
        return new TemplateResponse("retrieve", array("item" => OM::Cache("test")));
    }
}
?>
