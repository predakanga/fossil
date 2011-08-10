<?php

namespace Fossil;

/**
 * Description of RequestFactory
 *
 * @author lachlan
 * @F:Object("Request")
 */
class RequestFactory {
    /**
     * @return Fossil\Requests\BaseRequest
     */
    public function getEntryRequest() {
        if(PHP_SAPI == "cli") {
            return new Requests\CliRequest();
        } else {
            return new Requests\WebRequest();
        }
    }
}

?>
