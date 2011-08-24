<?php

namespace Fossil;

/**
 * Description of ControllerFactory
 *
 * @author lachlan
 * @F:Object("Controller")
 */
class ControllerFactory {
    public function get($controllerName = NULL) {
        // TODO: Implement some real logic here
        $controllerName = $controllerName ?: "index";
        // Load the controller
        $controllerClass = "Fossil\\Controllers\\" . ucfirst(strtolower($controllerName));
        return new $controllerClass;
    }
}

?>
