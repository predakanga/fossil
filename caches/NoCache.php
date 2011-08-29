<?php

namespace Fossil\Caches;

/**
 * Description of NoCache
 *
 * @author predakanga
 * @F:Object(type = "Cache", name = "NoCache")
 */
class NoCache extends BaseCache {
    public static function usable() {
        return true;
    }
    public static function getName() {
        return "None";
    }
    public static function getVersion() {
        return 1.0;
    }
    public static function getForm() {
        return null;
    }
    
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
