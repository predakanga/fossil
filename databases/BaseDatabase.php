<?php

namespace Fossil\Databases;

use Fossil\Interfaces\IDriver;

/**
 * Description of BaseDatabase
 *
 * @author predakanga
 */
abstract class BaseDatabase implements IDriver {
    private $config;
    
    public function __construct($config = null) {
        $this->config = $config;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    protected function getDefaultConfig() {
        $cacheOpts = OM::Settings("Fossil", "database", NULL);
        if($cacheOpts && isset($cacheOpts['config'])) {
            return $cacheOpts['config'];
        }
        return NULL;
    }
    
    abstract public function getPDO();
}

?>
