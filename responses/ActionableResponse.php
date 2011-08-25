<?php

namespace Fossil\Responses;

/**
 * Description of ActionableResponse
 *
 * @author lachlan
 */
abstract class ActionableResponse implements IResponse {
    abstract public function runAction();
}

?>
