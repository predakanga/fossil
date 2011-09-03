<?php

namespace Fossil\Controllers;

use Fossil\OM,
    Fossil\Requests\BaseRequest;

/**
 * Description of Error
 *
 * @author predakanga
 */
class Error extends AutoController {
    public function runShow(BaseRequest $req) {
        return OM::obj("Responses", "Template")->create("error", $req->args);
    }
}

?>
