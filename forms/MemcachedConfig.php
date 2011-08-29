<?php

namespace Fossil\Forms;

/**
 * Description of MemcachedConfig
 *
 * @author predakanga
 * @F:Form(name = "MemcachedConfig")
 */
class MemcachedConfig extends BaseDriverForm {
    /** @F:FormField(label = "Host") */
    public $memcached_host = "localhost";
    /** @F:FormField(label = "Port") */
    public $memcached_port = 11211;
}

?>
