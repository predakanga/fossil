<?php

namespace Fossil\Caches;

use Fossil\Interfaces\IDriver,
    Fossil\OM;

/**
 * Description of BaseCache
 *
 * @author predakanga
 */
abstract class BaseCache implements IDriver {
    protected $config;
    
    public function __construct($config = NULL) {
        $this->config = $config;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    protected function getDefaultConfig() {
        $cacheOpts = OM::Settings("Fossil", "cache", NULL);
        if($cacheOpts && isset($cacheOpts['config'])) {
            return $cacheOpts['config'];
        }
        return NULL;
    }
    
    abstract public function has($key);
    abstract public function get($key);
    abstract public function set($key, $value);
    abstract public function update($key, $update_cb);
}

?>
