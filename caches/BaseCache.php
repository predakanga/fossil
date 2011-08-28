<?php

namespace Fossil\Caches;

use Fossil\Interfaces\IDriver;

/**
 * Description of BaseCache
 *
 * @author predakanga
 */
abstract class BaseCache implements IDriver {
    protected $options;
    
    public function __construct($args = NULL) {
        $this->options = $args;
    }
    
    public function getSetup() {
        return array('fqcn' => get_class($this),
                     'options' => $this->options);
    }
    
    abstract public function has($key);
    abstract public function get($key);
    abstract public function set($key, $value);
    abstract public function update($key, $update_cb);
}

?>
