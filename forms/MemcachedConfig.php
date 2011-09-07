<?php

namespace Fossil\Forms;

/**
 * Description of MemcachedConfig
 *
 * @author predakanga
 * @F:Form(name = "MemcachedConfig")
 */
class MemcachedConfig extends BaseDriverForm {
    /** @F:FormField(label = "Host", fieldName="memcached_host") */
    public $host = "localhost";
    /** @F:FormField(label = "Port", fieldName="memcached_port") */
    public $port = 11211;
    
    public function toConfig() {
        $toRet = array();
        $toRet['id'] = "fossil";
        $toRet['servers'] = array();
        $toRet['servers'][] = array('host' => $this->host,
                                    'port' => $this->port);
        
        return $toRet;
    }
}

?>
