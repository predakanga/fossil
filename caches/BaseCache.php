<?php

namespace Fossil\Caches;

use Fossil\Interfaces\IDriver;

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
    
    abstract public function has($key);
    abstract public function get($key);
    abstract public function set($key, $value);
    abstract public function update($key, $update_cb);
}

?>
