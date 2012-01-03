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
    /**
     * @var Fossil\ObjectContainer
     */
    public $container;
    
    public function __construct(ObjectContainer $container) {
        // TODO: When PHP 5.4 is standard, replace this constructor
        // Use code similar to the following in ObjectContainer instead
        // http://au.php.net/manual/en/reflectionclass.newinstancewithoutconstructor.php#105670
        $this->container = $container;
        $this->setupObjects();
    }
    
    protected function determineObjects() {
        $classname = get_class($this);
        if(!isset($this->container->dependencies[$classname])) {
            // Collect dependencies
            $deps = array();
            // Default strategy is to use annotations
            /** @var Fossil\Annotations\AnnotationManager */
            $annoMgr = $this->container->get("AnnotationManager");
            
            $reflClass = new \ReflectionClass($this);
            foreach($reflClass->getProperties() as $reflProp) {
                $propAnno = $annoMgr->getPropertyAnnotation($reflProp, "F:Inject");
                if($propAnno) {
                    $type = $propAnno->value;
                    if(isset($propAnno->type)) {
                        $type = $propAnno->type;
                    }
                    $deps[] = array('type' => $type, 'destination' => $reflProp->name,
                                    'lazy' => $propAnno->lazy, 'required' => $propAnno->required);
                }
            }
            
            // And store them
            $this->container->dependencies[$classname] = $deps;
        }
        
        return $this->container->dependencies[$classname];
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
            $obj = $this->container->getByParams($requestParams);
            
            // Throw an exception if it's required
            if($required && $obj === null) {
                throw new \Exception("Required object could not be retrieved");
            }
            
            // Otherwise, store it
            $this->storeObject($destination, $obj);
        }
    }
    
    protected function _new($type, $name) {
        $argList = func_get_args();
        // Shift off the type and name
        array_shift($argList);
        array_shift($argList);
        // Create the object
        return $this->container->createObject($type, $name, $argList);
    }
}

?>
