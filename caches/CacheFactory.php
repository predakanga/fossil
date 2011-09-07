<?php

namespace Fossil\Caches;

use Fossil\OM;

/**
 * Description of CacheFactory
 *
 * @author predakanga
 * @F:Object("Cache")
 */
class CacheFactory {
    public function __construct() {
        // Find the correct cache layer, select it, and throw a SelectionChangedException
        $cacheName = OM::Settings("Fossil", "cache");
        if(!$cacheName) {
            $cacheName = "NoCache";
            if(extension_loaded("apc")) {
                if(ini_get("apc.enabled") == 1)
                    $cacheName = "APC";
            }
        } else {
            $cacheName = $cacheName["driver"];
        }
        OM::select("Cache", $cacheName);
        throw new \Fossil\Exceptions\SelectionChangedException();
    }
}

?>
