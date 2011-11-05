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
 * Description of ObjectContainer
 *
 * @author predakanga
 */
class ObjectContainer {
    private $instances = array();
    private $createStack = array();
    private $registrations = array();
    private $classMap = array();
    
    public function __construct($cachedRegistrations = null) {
        
    }
    
    public function get($objectType) {
        if(!isset($this->instances[$objectType])) {
            $this->createObject($objectType);
        }
        return $this->instances[$objectType];
    }
    
    public function getByParams($objectParams) {
        $lazy = false;
        if(isset($objectParams['lazy'])) {
            $lazy = (bool)$objectParams['lazy'];
        }
        $type = $objectParams['type'];
        
        if($lazy) {
            return $this->getLazyObject($type);
        } else {
            return $this->getObject($type);
        }
    }
    
    public function getFactory($type) {
        if(!isset($this->registrations[$type]))
            $this->registrations[$type] = new ObjectFactory();
        return $this->registrations[$type];
    }
    
    protected function getLazyObject($objectType) {
        return new LazyObject($this, $objectType);
    }
    
    protected function createObject($type) {
        if(isset($this->createStack[$type]) && $this->createStack[$type]) {
            // TODO: Use a real exception
            throw new \Exception("Circular dependency detected while creating $type");
        }
        if(!isset($this->registrations[$type])) {
            // TODO: Use a real exception
            throw new \Exception("Unknown type requested: $type");
        }
        
        // Start creating the object
        array_push($this->createStack, $type);
        
        // Decide what class to use
        $class = null;
        if($this->registrations[$type] instanceof ObjectFactory) {
            $class = $this->registrations[$type]->getConcreteClass();
        } else {
            $class = $this->registrations[$type]['fqcn'];
        }
        
        // Instantiate it, taking into account the class map
        $this->instances[$type] = $this->instantiateClass($class);
        
        // And unset it on the creation stack
        array_pop($this->createStack);
    }
    
    protected function instantiateClass($fqcn) {
        if(isset($this->classMap[$fqcn])) {
            return $this->classMap[$fqcn];
        }
        return $fqcn;
    }
    
    public function __sleep() {
        return array('registrations', 'classmap');
    }
}

?>
