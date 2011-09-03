<?php

namespace Fossil\Exceptions;

/**
 * Description of NoSuchControllerException
 *
 * @author predakanga
 */
class NoSuchControllerException extends \Exception {
    public function __construct($name) {
        parent::__construct("The controller {$name} does not exist.");
    }
}

?>
