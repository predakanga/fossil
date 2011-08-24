<?php

namespace Fossil\Renderers;

use Fossil\OM;

/**
 * Description of RendererFactory
 *
 * @author predakanga
 * @F:Object("Renderer")
 */
class RendererFactory {
    public function __construct() {
        // Find the correct render layer, select it, and throw a SelectionChangedException
        $rendererName = OM::Settings("Fossil", "renderer");
        if(!$rendererName) {
            $rendererName = "Development";
        } else {
            $rendererName = $rendererName["driver"];
        }
        OM::select("Renderer", $rendererName);
        throw new \Fossil\Exceptions\SelectionChangedException();
    }
}

?>
