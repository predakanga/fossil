<?php

namespace Fossil\Caches;

/**
 * Description of NoCache
 *
 * @author lachlan
 * @F:Object(type = "Cache", name = "NoCache")
 */
class NoCache extends BaseCache {
    public function __construct($args = NULL) {
        // Empty options for the bit bucket
        parent::__construct(array());
    }
    
    public function has($key) {
        return false;
    }
    
    public function get($key) {
        return NULL;
    }
    
    public function set($key, $value) {
        return;
    }
    
    public function update($key, $update_cb) {
        return;
    }
}

?>
