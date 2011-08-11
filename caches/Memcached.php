<?php

namespace Fossil\Caches;

/**
 * Description of Memcached
 *
 * @author lachlan
 * @F:Object("Cache", name = "Memcached")
 */
class Memcached implements ICache {
    /**
     * @var Memcached
     */
    private $mc;
    
    public function __construct($args) {
        // Conditionally use a persistent connection
        if(isset($args['id'])) {
            $this->mc = new \Memcached($args['id']);
        } else {
            $this->mc = new \Memcached();
        }
        // And if we don't have any servers (i.e. not persistent), add them
        if(!count($this->mc->getServerList()))
            $this->mc->addServers($args['servers']);
    }
    
    public function has($key) {
        if(!$this->mc->get($key)) {
            if($this->mc->getResultCode() != Memcached::RES_NOT_FOUND)
                return true;
            return false;
        }
        return true;
    }
    
    public function get($key) {
        return $this->mc->get($key);
    }
    
    public function set($key, $value) {
        $this->mc->set($key, $value);
    }
    
    public function update($key, $update_cb) {
        $cas = 0;
        $success = false;
        
        // TODO: Add extra checking, so that on other error conditions, it ends
        do
        {
            $value = $this->mc->get($key, NULL, $cas);
            $success = $this->mc->cas($cas, $key, $update_cb($value));
        } while(!$success);
    }
}

?>
