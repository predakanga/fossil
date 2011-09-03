<?php

namespace Fossil\Exceptions;

/**
 * Description of NoSuchClassException
 *
 * @author predakanga
 */
class NoSuchClassException extends \Exception {
    public function __construct($type, $name) {
        parent::__construct("Instanced class {$name} does not exist in {$type}");
    }
}

?>
