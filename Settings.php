<?php

/**
 * Copyright (c) 2011, predakanga
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @author predakanga
 * @since 0.1
 * @category Fossil Core
 * @package Fossil
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil;

/**
 * Description of Settings
 *
 * @author predakanga
 * @F:Object("Settings")
 */
class Settings implements \ArrayAccess {
    private $store;
    private $fossilHash;
    private $backingFile;
    
    public function __construct($backingFile = null) {
        if(!$backingFile)
            $backingFile = OM::FS()->execDir() . D_S . "settings.yml";
        $this->backingFile = $backingFile;
        $this->store = array();
        if(file_exists($backingFile)) {
            $this->store['Fossil'] = yaml_parse_file($backingFile);
            $this->fossilHash = md5(serialize($this->store['Fossil']));
        }
    }
    
    public function __destruct() {
        // Because with offsetGet-by-ref we don't know when Fossil changes...
        if(isset($this->store['Fossil'])) {
            $fossilHash = md5(serialize($this->store['Fossil']));
            if($fossilHash != $this->fossilHash)
                file_put_contents($this->backingFile, yaml_emit($this->store['Fossil']));
        }
    }
    
    public function bootstrapped() {
        if(!isset($this->store['Fossil']))
            return false;
        return true;
    }
    
    public function offsetExists($key) {
        return isset($this->store[$key]);
    }
    
    public function &offsetGet($key) {
        return $this->store[$key];
    }
    
    public function offsetSet($key, $value) {
        $this->store[$key] = $value;
        if($key == "Fossil")
            file_put_contents($this->backingFile, yaml_emit($this->store['Fossil']));
    }
    
    public function offsetUnset($key) {
        unset($this->store[$key]);
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
