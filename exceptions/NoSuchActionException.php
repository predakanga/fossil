<?php

namespace Fossil\Exceptions;

/**
 * Description of NoSuchActionException
 *
 * @author predakanga
 */
class NoSuchActionException extends \Exception {
    public function __construct($controller, $action) {
        parent::__construct("Action {$action} does not exist on controller {$controller}");
    }
}

?>
