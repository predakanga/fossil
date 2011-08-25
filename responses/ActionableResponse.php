<?php

namespace Fossil\Responses;

/**
 * Description of ActionableResponse
 *
 * @author predakanga
 */
abstract class ActionableResponse implements IResponse {
    abstract public function runAction();
}

?>
