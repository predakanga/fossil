<?php

namespace Fossil\Renderers;

use Fossil\Responses\BaseResponse;

/**
 * Description of DevelRenderer
 *
 * @author predakanga
 * @F:Object(type = "Renderer", name = "Development")
 */
class DevelRenderer {
    public function render($templateName, $templateData) {
        echo "Page name: $templateName\n\n";
        var_dump($templateData);
    }
}

?>
