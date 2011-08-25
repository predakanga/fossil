<?php

namespace Fossil\Responses;

/**
 * Description of RedirectResponse
 *
 * @author predakanga
 */
class RedirectResponse extends ActionableResponse {
    public function __construct($url) {
        $this->url = $url;
    }
    
    public function runAction() {
        header("Location: " . $this->url);
    }
}

?>
