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
            $class = OM::_("Requests", "CliRequest");
        } else {
            $class = OM::_("Requests", "WebRequest");
        }
        return new $class;
    }
}

?>
