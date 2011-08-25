<?php

namespace Fossil\Requests;

use Fossil\OM;

/**
 * Description of BaseRequest
 *
 * @author predakanga
 */
abstract class BaseRequest {
    public $controller;
    public $action;
    public $args;
    
    public function run() {
        return OM::Controller($this->controller)->run($this);
    }
}

?>
