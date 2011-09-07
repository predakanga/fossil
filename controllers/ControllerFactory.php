<?php

namespace Fossil\Controllers;

use Fossil\OM,
    Fossil\Exceptions\NoSuchClassException,
    Fossil\Exceptions\NoSuchControllerException;

/**
 * Description of ControllerFactory
 *
 * @author predakanga
 * @F:Object("Controller")
 */
class ControllerFactory {
    public function get($controllerName = NULL) {
        $controllerName = ucfirst(strtolower($controllerName ?: "index"));
        // Load the controller
        try
        {
            return OM::obj("Controllers", $controllerName)->create();
        } catch(NoSuchClassException $e) {
            throw new NoSuchControllerException($controllerName);
        }
        
        return new $ctrlClass;
    }
}

?>
