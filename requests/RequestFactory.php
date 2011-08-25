<?php

namespace Fossil\Requests;

/**
 * Description of RequestFactory
 *
 * @author predakanga
 * @F:Object("Request")
 */
class RequestFactory {
    /**
     * @return Fossil\Requests\BaseRequest
     */
    public function getEntryRequest() {
        if(PHP_SAPI == "cli") {
            return new CliRequest();
        } else {
            return new WebRequest();
        }
    }
}

?>
