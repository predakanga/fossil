<?php
/**
 * @author predakanga
 * @since 0.1
 * @package Fossil
 */

namespace Fossil;

/**
 * The core Fossil class, provides lifecycle management
 * 
 * @author predakanga
 * @since 0.1
 * @F:Object("Core")
 */
class Core {
	public function run() {
            // Start by collecting the request info
            $req = OM::Request()->getEntryRequest();
            // Run the loop of requests
            do
            {
                $resp = OM::Controller($req->controller)->run($req);
                if($resp->nextRequest)
                    $req = $resp->nextRequest;
                else
                    $req = null;
                
                $resp->runAction();
            } while($req);
            // Then with our final data, select the appropriate adaptor and output
            if($resp->data)
                var_dump($resp->data);
        }
}