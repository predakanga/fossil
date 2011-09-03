<?php

namespace Fossil\Requests;

/**
 * Description of InternalRequest
 *
 * @author predakanga
 */
class InternalRequest extends BaseRequest {
    public function __construct($controller, $action, $args = array()) {
        $this->controller = $controller;
        $this->action = $action;
        $this->args = $args;
    }
}

?>
