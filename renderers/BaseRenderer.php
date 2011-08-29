<?php

namespace Fossil\Renderers;

use Fossil\Interfaces\IDriver;

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
    
    abstract public function render($templateName, $templateData);
}

?>
