<?php

namespace Fossil\Responses;

use Fossil\Interfaces\IResponse;

/**
 * Description of ActionableResponse
 *
 * @author predakanga
 */
abstract class ActionableResponse implements IResponse {
    abstract public function runAction();
}

?>
