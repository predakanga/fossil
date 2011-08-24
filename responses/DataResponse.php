<?php

namespace Fossil\Responses;

/**
 * Description of DataResponse
 *
 * @author predakanga
 */
class DataResponse extends BaseResponse {
    public function __construct($template, $args) {
        $this->template = $template;
        $this->data = $args;
        $this->nextRequest = null;
    }
}

?>
