<?php

namespace Fossil\Responses;

use Fossil\Interfaces\IResponse;

/**
 * Description of RenderableResponse
 *
 * @author predakanga
 */
abstract class RenderableResponse implements IResponse {
    protected $outputType = "text/html";
    protected $responseCode = 200;

    public function render() {
        header("Content-Type: {$this->outputType}", true, $this->responseCode);
    }
}

?>
