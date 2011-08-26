<?php

namespace Fossil\Controllers;

use Fossil\OM,
    Fossil\Requests\BaseRequest,
    Fossil\Responses\TemplateResponse,
    Fossil\Responses\RedirectResponse;

/**
 * Description of Setup
 *
 * @author predakanga
 */
class Setup extends AutoController {
    public function runIndex(BaseRequest $req) {
        return new TemplateResponse("setup/index");
    }
    
    public function runCheckCompatibility(BaseRequest $req) {
        return new TemplateResponse("setup/checkCompat");
    }
}

?>
