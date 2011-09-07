<?php

namespace Fossil\Databases;

use Fossil\Interfaces\IDriver,
    Fossil\OM;

/**
 * Description of BaseDatabase
 *
 * @author predakanga
 */
abstract class BaseDatabase implements IDriver {
    protected $config;
    
    public function __construct($config = null) {
        if(!$config)
            $config = $this->getDefaultConfig();
        
        $this->config = $config;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    protected function getDefaultConfig() {
        $dbOpts = OM::Settings("Fossil", "database", NULL);
        
        if($dbOpts && isset($dbOpts['config'])) {
            return $dbOpts['config'];
        }
        return NULL;
    }
    
    abstract public function getPDO();
}

?>
