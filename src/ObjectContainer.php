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
    private $instancedTypes = array();
    private $overwritten = array();
    private $classMap = array();
    /** Dependencies are stored in the container, to isolate instances */
    public $dependencies = array();
    /** @var Fossil\Annotations\AnnotationManager */
    private $annotationMgr;
    
    public function __construct($overlayDetails = null, $appDetails = null) {
        // Set up the defaults
        $this->setupDefaults();

        // Set up overlay and app details immediately, so that they affect the instance ID
        if($overlayDetails) {
            $this->get("Core")->setOverlayDetails($overlayDetails);
        }
        if($appDetails) {
            $this->get("Core")->setAppDetails($appDetails);
        }
        
        // Then check for local DI cache
        if(!$this->cachedInit()) {
            $this->uncachedInit();
        }
    }
    
    protected function setupDefaults() {
        $this->registerType("Filesystem", 'Fossil\Filesystem');
        $this->registerType("AnnotationManager", 'Fossil\Annotations\AnnotationManager');
        $this->registerType("Cache", 'Fossil\Caches\NoCache');
        $this->registerType("Reflection", 'Fossil\ReflectionBroker');
        $this->registerType("Core", 'Fossil\Core');
        $this->registerType("Settings", 'Fossil\Settings');
    }
    
    protected function getCacheFile() {
        $fs = $this->get("Filesystem");
        $diCache = $fs->execDir() . D_S . "dicache.yml";
        return $diCache;
    }
    
    /**
     * If any objects modify the registrations outside of their Init functions,
     * it's their responsibility to call updateCache
     */
    public function updateCache() {
        $filename = $this->getCacheFile();
        $cacheObj = array('registrations' => $this->registrations,
                          'instancedTypes' => $this->instancedTypes);
        file_put_contents($filename, yaml_emit($cacheObj));
    }
    
    protected function cachedInit() {
        return false;
        $filename = $this->getCacheFile();
        if(!file_exists($filename)) {
            return false;
        }
        $cache = yaml_parse_file($filename);
        @$this->registrations = $cache['registrations'];
        @$this->instancedTypes = $cache['instancedTypes'];
        return ($this->registrations && $this->instancedTypes);
    }
    
    protected function uncachedInit() {
        $this->annotationMgr = $this->getLazyObject("AnnotationManager");
        $this->discoverTypes();
    }
    
    protected function discoverTypes() {
        $providers = $this->discoverProviders();
        foreach($providers as $providerType => $providerArray) {
            if(count($providerArray) == 1) {
                $this->registerType($providerType, reset($providerArray));
            } else {
                // If we have multiple providers, check for a default
                if(isset($providerArray['default'])) {
                    $this->registerType($providerType, $providerArray['default']);
                }
            }
        }
        
        // Integrate instanced classes
        $this->discoverInstanced();
        
        // Overlay our selected registrations from the settings class
         
        // Explore all classes for annotations, they needn't be Objects
        // Provider objects will be tagged with @F:Provides("Type")
        // If a type has multiple providers registered, one can be tagged with
        // @F:DefaultProvider, which will cause it to be used by default
        // Otherwise, an exception will be thrown if the type is asked for
        $this->updateCache();
    }
    
    protected function discoverProviders() {
        // Discover classes
        // Use @F:Provides($type) to denote providers
        // Use @F:DefaultProvider to set the default provider, when there's more than one
        $retArray = array();
        
        $providerClasses = $this->annotationMgr->getClassesWithAnnotation("F:Provides");
        foreach($providerClasses as $class) {
            $reflClass = new \ReflectionClass($class);
            if($reflClass->isAbstract()) {
                continue;
            }
            $providesAnno = $this->annotationMgr->getClassAnnotation($class, "F:Provides");
            $type = strtolower($providesAnno->value);
            
            if(!isset($retArray[$type])) {
                $retArray[$type] = array();
            }
            if($this->annotationMgr->classHasAnnotation($class, "F:DefaultProvider")) {
                $retArray[$type]['default'] = $class;
            } else {
                $retArray[$type][] = $class;
            }
        }
        return $retArray;
    }
    
    protected function discoverInstanced() {
        // Discover InstancedTypes first
        $typeClasses = $this->annotationMgr->getClassesWithAnnotation("F:InstancedType", false);
        foreach($typeClasses as $class) {
            $typeAnno = $this->annotationMgr->getClassAnnotation($class, "F:InstancedType", false);
            $typeName = strtolower($typeAnno->value);
            $impls = $this->discoverInstancedImplementations($class);
            $this->instancedTypes[$typeName] = $impls;
        }
    }
    
    protected function discoverInstancedImplementations($parentTypeClass) {
        /** @var Fossil\ReflectionBroker */
        $broker = $this->get("Reflection");
        $retArray = array();
        // Every Instanced class must inherit from a class tagged with InstancedType
        // This enforces semi-strict inheritance
        
        $reflClass = $broker->getClass($parentTypeClass);
        // Gather the implementations of this class
        $implementors = $reflClass->getDirectSubclasses();
        $implementors += $reflClass->getIndirectSubclasses();
        foreach($implementors as $implClass => $implReflClass) {
            // Make sure that the class is concrete
            if($implReflClass->isAbstract()) {
                continue;
            }
            // Determine the name
            $name = $implReflClass->getShortName();
            // Don't allow recursive annotations to affect the name
            $instancedAnno = $this->annotationMgr->getClassAnnotation($implClass, "F:Instanced", false);
            if($instancedAnno) {
                $name = $instancedAnno->value;
            }
            $name = strtolower($name);
            // Ensure we don't already have an instance by this name for this type
            if(isset($retArray[$name])) {
                throw new \Exception("Instanced name collision between " . $retArray[$name] .
                                     " and " . $implClass . ". Assign one of them a different name with @F:Instanced");
            }
            // Otherwise, store it
            $retArray[$name] = $implClass;
        }
        // And return the list of implementations
        return $retArray;
    }
    
    public function registerType($type, $fqcn, $overwrite = true, $noConflict = false) {
        $type = strtolower($type);
        if(isset($this->registrations[$type]) && !$overwrite) {
            return;
        }
        if(isset($this->overwritten[$type]) && !$noConflict) {
            // TODO: Use a concrete exception, warn of conflicting plugins
            throw new \Exception("Multiple classes were registered for type $type");
        }
        if(isset($this->registrations[$type])) {
            $this->overwritten[$type] = true;
        }
        $this->registrations[$type] = $fqcn;
    }
    
    public function has($objectType) {
        $objectType = strtolower($objectType);
        return isset($this->instances[$objectType]);
    }
    
    public function getAllInstanced($type) {
        $type = strtolower($type);
        if(!isset($this->instancedTypes[$type])) {
            return array();
        }
        return array_keys($this->instancedTypes[$type]);
    }
    
    public function get($objectType) {
        $objectType = strtolower($objectType);
        if(!isset($this->instances[$objectType])) {
            $this->instantiateDependency($objectType);
        }
        return $this->instances[$objectType];
    }
    
    public function getByParams($objectParams) {
        $lazy = false;
        if(isset($objectParams['lazy'])) {
            $lazy = (bool)$objectParams['lazy'];
        }
        $type = $objectParams['type'];
        $type = strtolower($type);
        
        if($lazy) {
            return $this->getLazyObject($type);
        } else {
            return $this->get($type);
        }
    }
    
    protected function getLazyObject($objectType) {
        $objectType = strtolower($objectType);
        return new LazyObject($this, $objectType);
    }
    
    protected function instantiateDependency($type) {
        if(isset($this->createStack[$type])) {
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
        $class = $this->registrations[$type];
        
        // Instantiate it, taking into account the class map
        $this->instances[$type] = $this->instantiateClass($class);
        
        // And unset it on the creation stack
        array_pop($this->createStack);
    }
    
    public function createObject($type, $name, $argList = array()) {
        $type = strtolower($type);
        $name = strtolower($name);
        if(!isset($this->instancedTypes[$type])) {
            throw new \Exception("Unknown instanced type: $type");
        }
        if(!isset($this->instancedTypes[$type][$name])) {
            throw new \Exception("Unknown instanced type implementor: $name for $type");
        }
        $fqcn = $this->instancedTypes[$type][$name];
        if(isset($this->classMap[$fqcn])) {
            $fqcn = $this->classMap[$fqcn];
        }
        
        if(count($argList) == 0) {
            return new $fqcn($this);
        } else {
            $reflClass = new \ReflectionClass($fqcn);
            // Unshift this back on to the arg list
            array_unshift($argList, $this);
            // And call the constructor
            return $reflClass->newInstanceArgs($argList);
        }
    }
    
    protected function instantiateClass($fqcn) {
        if(isset($this->classMap[$fqcn])) {
            return new $this->classMap[$fqcn]($this);
        }
        return new $fqcn($this);
    }
    
    public function __sleep() {
        return array('registrations', 'instancedTypes', 'classmap');
    }
}

?>