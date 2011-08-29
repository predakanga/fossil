<?php

namespace Fossil\Caches;

use Fossil\OM;

/**
 * Description of Memcached
 *
 * @author predakanga
 * @F:Object(type = "Cache", name = "Memcached")
 */
class Memcached extends BaseCache {
    /**
     * @var Memcached
     */
    private $mc;
    
    private function getDefaultOptions() {
        // Grab options from the settings
        $cacheOpts = OM::Settings("Fossil", "cache", NULL);
        if(!$cacheOpts || !isset($cacheOpts['options'])) {
            // Default settings
            $cacheOpts['options']['id'] = "fossil";
            $cacheOpts['options']['servers'] = array();
            $cacheOpts['options']['servers'][] = array("host" => "localhost",
                                                       "port" => 11211);
        }
        return $cacheOpts['options']; 
    }
    
    public static function usable() {
        return extension_loaded('memcached');
    }
    public static function getName() {
        return "Memcached";
    }
    public static function getVersion() {
        return 1.0;
    }
    
    public static function getForm() {
        return null;
    }
    
    public function __construct($config = NULL) {
        // If args == NULL, we're doing a default construction, load default options
        if($config === NULL)
            $config = $this->getDefaultOptions();
        
        parent::__construct($config);
        
        // Conditionally use a persistent connection
        if(isset($config['id'])) {
            $this->mc = new \Memcached($config['id']);
        } else {
            $this->mc = new \Memcached();
        }
        // And if we don't have any servers (i.e. not persistent), add them
        if(!count($this->mc->getServerList()))
            $this->mc->addServers($config['servers']);
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
