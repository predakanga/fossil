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
        // TODO: Implement some real logic here
        $controllerName = $controllerName ?: "index";
        // Load the controller
        try
        {
            $ctrlClass = OM::_("Controllers", ucfirst(strtolower($controllerName)));
        } catch(NoSuchClassException $e) {
            throw new NoSuchControllerException($controllerName);
        }
        
        return new $ctrlClass;
    }
}

?>
