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
        $respCls = OM::_("Responses", "Template");
        
        return new $respCls("error", $req->args);
    }
}

?>
