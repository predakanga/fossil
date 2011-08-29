<?php

namespace Fossil\Renderers;

use Fossil\Responses\BaseResponse;

/**
 * Description of DevelRenderer
 *
 * @author predakanga
 * @F:Object(type = "Renderer", name = "Development")
 */
class DevelRenderer extends BaseRenderer {
    public static function getName() { return "Development"; }
    public static function getVersion() { return 1.0; }
    public static function usable() { return true; }
    public static function getForm() { return null; }
    
    public function render($templateName, $templateData) {
        echo "Page name: $templateName\n\n";
        var_dump($templateData);
    }
}

?>
