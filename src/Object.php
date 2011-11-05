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
 * Description of Object
 *
 * @author predakanga
 */
class Object {
    protected $_container;
    
    public function __construct(ObjectContainer $container) {
        $this->_container = $container;
        $this->setupObjects();
    }
    
    public function __sleep() {
        // Pre-sleep, discard all the objects managed by the ObjectContainer
        foreach($this->determineObjects() as $obj) {
            $this->unstoreObject($location);
        }
    }
    
    public function __wakeup() {
        // After wakeup, set up the object again
        $this->setupObjects();
    }
    
    protected function determineObjects() {
        // Default strategy is to use annotations
        $annoMgr = $this->_container->get("Annotations");
    }
    
    protected function unstoreObject($location) {
        unset($this->{$location});
    }
    
    protected function storeObject($destination, $object) {
        $this->{$destination} = $object;
    }
    
    protected function setupObjects() {
        $requestObjs = $this->determineObjects();
        
        foreach($requestObjs as $requestParams) {
            // Split off the details that we only need locally
            $destination = $requestParams['destination'];
            $required = $requestParams['required'];
            unset($requestParams['destination']);
            unset($requestParams['required']);
            
            // Retrieve the object
            $obj = $this->_container->getByParams($requestParams);
            
            // Throw an exception if it's required
            if($required && $obj === null) {
                throw new \Exception("Required object could not be retrieved");
            }
            
            // Otherwise, store it
            $this->storeObject($destination, $obj);
        }
    }
}

?>
