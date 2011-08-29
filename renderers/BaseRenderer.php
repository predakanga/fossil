<?php

namespace Fossil\Renderers;

use Fossil\OM,
    Fossil\Interfaces\IDriver;

/**
 * Description of BaseRenderer
 *
 * @author predakanga
 */
abstract class BaseRenderer implements IDriver {
    private $config;
    
    public function __construct($config = null) {
        return $this->config;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    protected function getDefaultConfig() {
        $cacheOpts = OM::Settings("Fossil", "renderer", NULL);
        if($cacheOpts && isset($cacheOpts['config'])) {
            return $cacheOpts['config'];
        }
        return NULL;
    }
    
    abstract public function render($templateName, $templateData);
}

?>
