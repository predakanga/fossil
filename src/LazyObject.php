<?php

/*
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
 */

namespace Fossil;

/**
 * Description of LazyObject
 *
 * @author predakanga
 */
class LazyObject {
    private $_objectType;
    private $_container;
    private $_object = null;
    
    public function __construct(ObjectContainer $container, $type) {
        $this->_objectType = $type;
        $this->_container = $container;
    }
    
    private function _init() {
        $this->_object = $this->_container->get($this->_objectType);
    }
    
    public function __set($name, $value) {
        if($this->_object === null) {
            $this->_init();
        }
        // Unsure whether the return is required here
        // Could potentially break LHS of assignations without it, so leave it
        return $this->_object->{$name} = $value;
    }
    
    public function &__get($name) {
        if($this->_object === null) {
            $this->_init();
        }
        return $this->_object->{$name};
    }
    
    public function __isset($name) {
        if($this->_object === null) {
            $this->_init();
        }
        return isset($this->_object->{$name});
    }
    
    public function __unset($name) {
        if($this->_object === null) {
            $this->_init();
        }
        unset($this->_object->{$name});
    }
    
    public function __call($name, $args) {
        if($this->_object === null) {
            $this->_init();
        }
        return call_user_func_array(array($this->_object, $name), $args);
    }
    
    public function __toString() {
        if($this->_object === null) {
            return "LazyObject for type " . $this->_objectType;
        }
        return (string)$this->_object;
    }
    
    public function __set_state() {
        if($this->_object === null) {
            return $this;
        }
        return $this->_object;
    }
    
    function __clone() {
        if($this->_object === null) {
            $this->_object = clone $this->_object;
        }
    }
}

?>
