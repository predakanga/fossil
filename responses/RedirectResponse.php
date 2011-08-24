<?php

namespace Fossil\Responses;

/**
 * Description of RedirectResponse
 *
 * @author predakanga
 */
class RedirectResponse extends BaseResponse {
    public function __construct($url) {
        $this->template = null;
        $this->data = null;
        $this->nextRequest = null;
        $this->url = $url;
    }
    
    public function runAction() {
        header("Location: " . $this->url);
    }
}

?>
