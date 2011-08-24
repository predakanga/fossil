<?php

namespace Fossil\Exceptions;

/**
 * Description of ValidationFailedException
 *
 * @author lachlan
 */
class ValidationFailedException extends \Exception {
    public function __construct($class, $property, $value) {
        parent::__construct("Validation failed, setting $property to '$value' on $class");
    }
}

?>
