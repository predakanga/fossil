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

use Fossil\OM,
    Fossil\Exceptions\NoSuchClassException,
    TokenReflection\Broker;

/**
 * @author predakanga
 */
class InstanceWrapper {
    private $reflClass;

    public function __construct($fqcn) {
        $this->reflClass = new \ReflectionClass($fqcn);
    }

    public function create() {
        return $this->reflClass->newInstanceArgs(func_get_args());
    }
}

/**
 * Description of ObjectRepository
 *
 * @author predakanga
 */
class ObjectRepository {
    protected $scannedClasses = array();
    protected $staticClasses = array();
    protected $staticInstances = array();
    
    protected $instancedClasses = array();
    protected $instancedWrappers = array();
    
    protected $compiledClassMap = array();
    protected $reflBroker = NULL;
    
    protected function setupDefaults() {
        $this->staticClasses['FS'] = array('default' => array('fqcn' => '\\Fossil\\Filesystem'));
        $this->staticClasses['Annotations'] = array('default' => array('fqcn' => '\\Fossil\\Annotations\\AnnotationManager'));
        $this->staticClasses['Error'] = array('default' => array('fqcn' => '\\Fossil\\ErrorManager'));
        $this->staticClasses['Settings'] = array('default' => array('fqcn' => '\\Fossil\\Settings'));
    }
    
    public function __construct() {
        $this->setupDefaults();
        $this->initBroker();
    }
    
    public function __sleep() {
        return array('staticClasses', 'instancedClasses', 'compiledClassMap');
    }
    
    protected function initBroker() {
        $this->reflBroker = new Broker(new \TokenReflection\Broker\Backend\Memory());
    }
    
    public function scanForObjects($includePlugins = false) {
        if(!$this->reflBroker)
            $this->initBroker();
        foreach(OM::FS()->roots($includePlugins) as $root) {
            $this->processRoot($root);
        }
        $this->updateAnnotations();
        $this->scanForSingletonObjects();
        $this->scanForInstancedObjects();
        $this->scanForExtensionClasses();
    }
    
    protected function processRoot($root) {
        // Build up our list of files to scan
        $files = OM::FS()->sourceFiles($root);
        foreach($files as $file) {
            $this->reflBroker->process($file);
        }
    }
    
    public function updateAnnotations() {
        if(!$this->reflBroker)
            $this->initBroker();
        
        OM::Annotations()->updateAnnotations($this->reflBroker->getClasses());
    }
    
    protected function scanForSingletonObjects() {
        $usefulClasses = OM::Annotations()->getClassesWithAnnotation("F:Object");
        foreach($usefulClasses as $class) {
            if(isset($this->scannedClasses[$class]))
                    continue;
            $this->scannedClasses[$class] = true;

            $annotations = OM::Annotations()->getClassAnnotations($class, "F:Object");
            foreach($annotations as $objAnno) {
                $type = $objAnno->value ?: $objAnno->type;
                if(!isset($this->staticClasses[$type]))
                    $this->staticClasses[$type] = array();
                $this->staticClasses[$type][$objAnno->name] = array('fqcn' => '\\' . $class);
            }
        }
    }
    
    protected function scanForInstancedObjects() {
        $usefulClasses = OM::Annotations()->getClassesWithAnnotation("F:Instanced");

        foreach($usefulClasses as $class) {
            if(isset($this->scannedClasses[$class]))
                continue;
            $this->scannedClasses[$class] = true;

            $reflClass = new \ReflectionClass($class);
            // Skip the class if it's abstract
            if($reflClass->isAbstract())
                continue;
            $annotations = OM::Annotations()->getClassAnnotations($class, "F:Instanced");
            foreach($annotations as $objAnno) {
                if(!isset($objAnno->type)) {
                    // Check the class's namespace
                    $namespace = $reflClass->getNamespaceName();
                    $type = substr($namespace, strrpos($namespace, '\\')+1);
                } else {
                    $type = $objAnno->type;
                }

                if(isset($objAnno->name)) {
                    $name = $objAnno->name;
                } elseif(isset($objAnno->value)) {
                    $name = $objAnno->value;
                } else {
                    $name = substr($class, strrpos($class, "\\")+1);
                }
                // Normalize the name before storing it
                $type = ucfirst(strtolower($type));
                $name = ucfirst(strtolower($name));

                if(!isset($this->instancedClasses[$type]))
                    $this->instancedClasses[$type] = array();
                $this->instancedClasses[$type][$name] = $class;
            }
        }
    }
    
    protected function scanForExtensionClasses() {
        $this->extensionClasses = OM::Annotations()->getClassesWithAnnotation("F:ExtensionClass");
    }
    
    public function getExtensionClasses() {
        return $this->extensionClasses;
    }
    
    public function setClassMap($classMap) {
        $this->compiledClassMap = $classMap;
    }
    
    public function resolveClassname($fqcn) {
        if(isset($this->compiledClassMap[$fqcn]))
            $fqcn = $this->compiledClassMap[$fqcn];
        return $fqcn;
    }
    
    /**
     * Select which provider to use for a type
     * 
     * Creates an instance of the new provider immediately,
     * passing the old provider to the new one as required
     * 
     * Pre-condition: $name must be known as a $type
     * 
     * @param string $type The type which is being managed
     * @param string $name The name of the provider to use
     * @return void
     */
    public function selectSingleton($type, $name) {
        if(!isset($this->staticClasses[$type]) || !isset($this->staticClasses[$type][$name])) {
            throw new NoSuchClassException($type, $name, "singleton");
        }
        
        $typeInfo = $this->staticClasses[$type][$name];
        $class = $this->resolveClassname($typeInfo['fqcn']);

        // Instantiate it
        $newInstance = new $class;
        // And store it
        $this->_setSingleton($type, $newInstance);
    }
    
    protected function selectDefaultSingleton($type) {
        // First off, check that we know about this type
        if(!isset($this->staticClasses[$type]) || !isset($this->staticClasses[$type]['default'])) {
            $typeName = ObjectFactory::getObjectName($type);
            if(!$typeName) {
                throw new NoSuchClassException($type, "default", "singleton");
            }
            $this->selectSingleton($type, $typeName);
            return;
        }
        
        // By default, just use the element named default
        $this->selectSingleton($type, "default");
    }

    protected function _setSingleton($type, $instance) {
        // We only care about cache when setting the dirty status
        if($type == "Cache") {
//            $this->makeDirty();
        }

        $this->staticInstances[$type] = $instance;
    }
    
    public function setSingleton($type, $instance) {
        static $allowableTypes = array("Cache", "Annotations", "Compiler");
        // For public consumption, limit the types that can be set
        if(!in_array($type, $allowableTypes)) {
            throw new \Exception("Singletons of type $type may only be set with selectSingleton");
        }
        $this->_setSingleton($type, $instance);
    }
    
    /**
     * Get an object of the requested type
     * 
     * Instantiates the object if none yet exists.
     * Throws an exception if the object manager doesn't
     * know about the requested type
     * 
     * @param string $type The type to retrieve
     * @return mixed An instance of the requested type
     */
    public function getSingleton($type) {
        if(!isset($this->staticInstances[$type]))
            $this->selectDefaultSingleton($type);
        
        return $this->staticInstances[$type];
    }
    
    public function getSpecificSingleton($type, $name) {
        if(!isset($this->staticClasses[$type]) || !isset($this->staticClasses[$type][$name]))
            throw new NoSuchClassException($type, $name, "singleton");
        
        return $this->staticClasses[$type][$name];
    }
    
    public function getAllSingletonClasses($type = null) {
        if(!$type)
            return $this->staticClasses;
        if(isset($this->staticClasses[$type]))
            return $this->staticClasses[$type];
        return array();
    }
    
    public function hasSingleton($type) {
        return isset($this->staticInstances[$type]);
    }
    
    public function knowsSingleton($type, $name = null) {
        if(!isset($this->staticClasses[$type]))
            return false;
        if($name && !isset($this->staticClasses[$type][$name]))
            return false;
        return true;
    }
    
    protected function createInstanceWrapper($type, $name) {
        // Ensure that type and name are normalised first
        $properType = ucfirst(strtolower($type));
        $properName = ucfirst(strtolower($name));
        // Then make sure that the first dimension of the array is available
        if(!isset($this->instancedWrappers[$type]))
                $this->instancedWrappers[$type] = array();
        if(!isset($this->instancedWrappers[$properType]))
                $this->instancedWrappers[$properType] = array();
        // Then check whether we already have a normalised wrapper
        if(isset($this->instancedWrappers[$properType][$properName])) {
            $this->instancedWrappers[$type][$name] = $this->instancedWrappers[$properType][$properName];
            return;
        } else {
            // If not, create one and store it under both the normalised and un-normalised names
            // First, though, we must check that the class exists
            if(!isset($this->instancedClasses[$properType]) || !isset($this->instancedClasses[$properType][$properName])) {
                var_dump($this->instancedClasses);
                throw new NoSuchClassException($type, $name, "instance");
            }
            $fqcn = $this->instancedClasses[$properType][$properName];
            $newInstWrapper = new InstanceWrapper($this->resolveClassname($fqcn));
            $this->instancedWrappers[$properType][$properName] = $newInstWrapper;
            $this->instancedWrappers[$type][$name] = $newInstWrapper;
        }
    }
    
    public function getInstanceWrapper($type, $name) {
        if(!isset($this->instancedWrappers[$type]) || !isset($this->instancedWrappers[$type][$name])) {
            $this->createInstanceWrapper($type, $name);
        }
        
        return $this->instancedWrappers[$type][$name];
    }
    
    public function getAllInstanceClasses($type = null) {
        if(!$type)
            return $this->instancedClasses;
        
        if(isset($this->instancedClasses[$type]))
            return $this->instancedClasses[$type];
        return array();
    }
}

?>
