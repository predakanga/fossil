<?php

namespace Fossil\Caches;

/**
 * Description of NoCache
 *
 * @author lachlan
 * @F:Object("Cache")
 */
class NoCache implements ICache {
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
