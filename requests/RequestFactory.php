<?php

namespace Fossil\Requests;

use Fossil\OM;

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
            $class = OM::obj("Requests", "CliRequest")->create();
        } else {
            $class = OM::obj("Requests", "WebRequest")->create();
        }
        return new $class;
    }
}

?>
