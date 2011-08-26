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
    public function runIndex() {
        // Redirect to the setup controller if we have no settings
        if(!OM::Settings()->bootstrapped())
            return new RedirectResponse("index.php?controller=setup");
        else
            // Otherwise, redirect to the dev panel
            return new RedirectResponse("index.php?controller=dev");
    }
}
?>
