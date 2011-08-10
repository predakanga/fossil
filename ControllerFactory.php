<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

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
