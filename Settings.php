<?php

namespace Fossil;

/**
 * Description of Settings
 *
 * @author predakanga
 * @F:Object("Settings")
 */
class Settings {
    private $store;
    private $backingFile;
    
    public function __construct($backingFile = 'settings.yml') {
        $this->backingFile = $backingFile;
        $this->store = array();
        if(file_exists($backingFile))
            $this->store['Fossil'] = yaml_parse_file($backingFile);
    }
    
    public function bootstrapped() {
        if(!isset($this->store['Fossil']))
            return false;
        return true;
    }
    
    public function get($section, $setting, $default = null) {
        if(isset($this->store[$section]))
            if(isset($this->store[$section][$setting]))
                return $this->store[$section][$setting];
        return $default;
    }
    
    public function set($section, $setting, $value) {
        if(!isset($this->store[$section]))
            $this->store[$section] = array();
        $this->store[$section][$setting] = $value;
        // If it's a Fossil setting, store it
        if($section == "Fossil")
            file_put_contents($this->backingFile, yaml_emit($this->store['Fossil']));
    }
}

?>
