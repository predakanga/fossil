<?php
/**
 * @author predakanga
 * @since 0.1
 * @package Fossil
 */

namespace Fossil;

use Fossil\OM;

/**
 * The core Fossil class, provides lifecycle management
 * 
 * @author predakanga
 * @since 0.1
 * @F:Object("Core")
 */
class Core {
	public function run() {
        // Main loop process:
        // Parse out the main request
        $req = OM::Request()->getEntryRequest();
        // Dispatch the request
        OM::Dispatcher()->runRequest($req);
        // fastcgi_finish_request() if available
        if(function_exists("fastcgi_finish_request"))
            fastcgi_finish_request();
        // Run any registered background tasks
        return;
    }
}