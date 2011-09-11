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

use Fossil\Exceptions\NoSuchClassException;

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
 * The object manager class
 * 
 * The object manager is designed to be used as static.
 * Provides magic methods to get a singleton instance of
 * a given type, by calling OM::theType()
 * 
 * @author predakanga
 * @since 0.1
 * @method Fossil\Core Core() Core() Returns the Fossil core
 * @method Fossil\Filesystem FS() FS() Returns the Fossil filesystem layer
 * @method Fossil\Annotations Annotations() Annotations() Returns the Fossil annotation layer
 * @method Fossil\Requests\RequestFactory Request() Request() Returns the Fossil request factory
 * @method Fossil\Caches\BaseCache Cache() Cache() Returns the current cache driver
 * @method Fossil\ORM ORM() ORM() Returns the ORM layer
 * 
 */
class OM {
    /**
     * Maps type to current instance
     * 
     * @var array
     */
    private static $instances = array();
    private static $extensionClasses = array();
    private static $classMap = array();
    private static $scannedClasses = array();
    private static $instanceWrappers = array();

    private static $startupTime = 0;

    /**
     * Maps type to potential provider name
     * 
     * Provider keys:
     * ['fqcn']         => Fully Qualified Class Name
     * ['default']      => Whether this provider is to be used by default
     * ['takesContext'] => Whether this provider has a constructor which
     *                     takes the previous provider instance for context
     * 
     * @var array
     */
    private static $singletonClasses = array(
        'FS' => array('default' => array('fqcn' => '\\Fossil\\Filesystem', 'takesContext' => false)),
        'Annotations' => array('default' => array('fqcn' => '\\Fossil\\Annotations\\AnnotationManager', 'takesContext' => false)),
        'Error' => array('default' => array('fqcn' => '\\Fossil\\ErrorManager', 'takesContext' => false)),
        'Settings' => array('default' => array('fqcn' => '\\Fossil\\Settings', 'takesContext' => false))
    );
    private static $instancedClasses = array();
    private static $dirty = false;
    
    protected static function makeDirty() {
        self::$dirty = true;
        register_shutdown_function(array(__CLASS__, "shutdown"));
    }
    
    // Destructor, to update the quickstart file
    public static function shutdown() {
        if(file_exists(self::FS()->tempDir() . D_S . ".quickstart.yml") && !self::$dirty) {
            // If we don't have anything to update, return
            return;
        }
        // Otherwise, build up a document with everything we need, and emit it
        $quickstart = array();
        $quickstart['cache'] = array('fqcn' => get_class(self::Cache()),
                                     'config' => self::Cache()->getConfig());

        // And output the document
        // TODO: Output to the correct directory
        file_put_contents(self::FS()->tempDir() . D_S . '.quickstart.yml', yaml_emit($quickstart));
    }

    private static function scanForSingletonObjects() {
        $allClasses = self::Annotations()->getClassesWithAnnotation("F:Object");
        foreach($allClasses as $class) {
            if(isset(self::$scannedClasses[$class]))
                continue;
            self::$scannedClasses[$class] = true;

            $annotations = self::Annotations()->getClassAnnotations($class, "F:Object");
            foreach($annotations as $objAnno) {
                $type = $objAnno->value ?: $objAnno->type;
                if(!isset(self::$singletonClasses[$type]))
                    self::$singletonClasses[$type] = array();
                self::$singletonClasses[$type][$objAnno->name] = array('fqcn' => '\\' . $class, 'takesContext' => $objAnno->takesContext);
            }
        }
    }

    private static function scanForInstancedObjects() {
        $allClasses = self::Annotations()->getClassesWithAnnotation("F:Instanced");

        foreach($allClasses as $class) {
            if(isset(self::$scannedClasses[$class]))
                continue;
            self::$scannedClasses[$class] = true;

            $annotations = self::Annotations()->getClassAnnotations($class, "F:Instanced");
            foreach($annotations as $objAnno) {
                if(!isset($objAnno->type)) {
                    // Check the class's namespace
                    $reflClass = new \ReflectionClass($class);
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

                if(!isset(self::$instancedClasses[$type]))
                    self::$instancedClasses[$type] = array();
                self::$instancedClasses[$type][$name] = $class;
            }
        }
    }

    private static function scanForObjects($root) {
        // Get all php files below this directory, excluding libs and static
        $files = OM::FS()->sourceFiles($root);
        
        foreach($files as $file) {
            try
            {
                include_once($file);
            }
            catch(\Exception $e)
            {
            }
        }
        self::Annotations()->updateAnnotations();
        self::scanForSingletonObjects();
        self::scanForInstancedObjects();
        self::$extensionClasses = self::Annotations()->getClassesWithAnnotation("F:ExtensionClass");
    }

    public static function setup() {
        self::$startupTime = microtime(true);
        self::Error()->init(E_ALL | E_STRICT);
        
        // Load the basic settings from 'quickstart.yml'
        if(!file_exists(self::FS()->tempDir() . D_S . '.quickstart.yml'))
            return;
        // Ignore the quickstart if it's newer than settings.yml
        if(file_exists(self::FS()->execDir() . D_S . 'settings.yml')) {
            if(filemtime(self::FS()->tempDir() . D_S . '.quickstart.yml') <
               filemtime(self::FS()->execDir() . D_S . 'settings.yml')) {
                unlink(self::FS()->tempDir() . D_S . '.quickstart.yml');
                return;
            }
        }
        $basics = yaml_parse_file(self::FS()->tempDir() . D_S . '.quickstart.yml');
        // Return if we have no quickstart settings
        if(!$basics)
            return;
        // If we have settings, grab the cache
        if(isset($basics['cache'])) {
            // Bypass the setTypeInstance function so as not to dirty the context
            self::$instances['Cache'] = new $basics['cache']['fqcn']($basics['cache']['config']);
        }
    }

    public static function getRuntime() {
        return microtime(true) - self::$startupTime;
    }
    /**
     * Initialize the object manager without cache
     * 
     * Scans the codebase immediately to discover classes for
     * the object manager to manage
     * 
     * @return void
     */
    public static function init() {
        self::primeCache();
        // TODO: Only save cache conditionally
        self::saveCache();
        // Finally, register plugins with the ORM
        self::ORM()->registerPaths();
        // And ensure that we have them
        self::ORM()->ensureSchema();
    }

    /**
     * Initialize the object manager with cache
     * 
     * Reads the configuration minimally to establish a
     * connection to the cache, which is used to store
     * information on what classes are available to the OM
     * 
     *  @return bool Whether cached state could be loaded
     */
    public static function cachedInit() {
        if(!self::has("Cache"))
            return false;
        
        $cachedData = OM::Cache("fossil_state");
        if(!$cachedData)
            return false;
        
        // Check the mtimes of basic classes
        if(self::getFossilMtime() > $cachedData['mtime'])
            return false;
        
        self::$singletonClasses = $cachedData['singleton'];
        self::$instancedClasses = $cachedData['instanced'];
        
        // Get the compiler, to set up the namespace path
        self::Compiler()->registerAutoloadPath();
        
        self::$classMap = $cachedData['classMap'];
        self::$instances['Annotations'] = new Annotations\AnnotationManager($cachedData['annotations']);
        
        // Then after loading the plugin manager etc, check mtimes again, just in case
        self::Plugins()->loadEnabledPlugins();
        if(self::getFossilMtime() > $cachedData['mtime'])
            return false;
        
        // Finally, register plugins with the ORM
        self::ORM()->registerPaths();
        
        return true;
    }

    public static function getFossilMtime() {
        // Check the source files, and settings.yml
        $mtimes = array_map(function($file) { return filemtime($file); }, self::FS()->allSourceFiles());
        if(file_exists(self::FS()->execDir() . D_S . 'settings.yml'))
            $mtimes[] = filemtime(self::FS()->execDir() . D_S . 'settings.yml');
        return max($mtimes);
    }
    
    /**
     * Scans the codebase and stores all the found objects in the cache
     * 
     * Used by the deployment tool to ensure that there's
     * a primed cache ready for use in release mode
     * 
     * @return void
     */
    public static function primeCache() {
        // Regular functionality:
        // Scan local namespace for objects
        self::scanForObjects(self::FS()->fossilRoot());
        foreach(OM::FS()->roots(false) as $root)
            self::scanForObjects($root);
        // Load settings up, set up drivers
        self::Compiler()->registerAutoloadPath();
        self::get('Cache');
        self::ORM()->ensureSchema();
        // Register plugins
        // TODO: Move to auto-plugin loader
        OM::Plugins()->loadEnabledPlugins();
        // Scan plugin namespaces for objects
        foreach(self::FS()->pluginRoots() as $root)
            self::scanForObjects($root);
        // Do compilation
        $tempClassMap = OM::Compiler()->bootstrap();
        OM::Compiler()->setClassMap($tempClassMap);
        self::$classMap = OM::Compiler()->compileAll();
    }

    public static function saveCache() {
        $cachedData = array();
        $cachedData['singleton'] = self::$singletonClasses;
        $cachedData['instanced'] = self::$instancedClasses;
        $cachedData['classMap'] = self::$classMap;
        $cachedData['annotations'] = self::Annotations()->gatherAnnotations();
        $cachedData['mtime'] = self::getFossilMtime();
        
        OM::Cache()->set("fossil_state", $cachedData);
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
    public static function select($type, $name, $fqcn = null) {
        if($fqcn) {
            self::setTypeInstance($type, new $fqcn);
            return;
        }

        // Pre-condition: $name must be known as a $type
        // Post-condition: There will be an instance of $type stored in the OM
        $newInstance = NULL;
        $oldInstance = NULL;
        // Remove the current instance before instantiating the new one
        if(isset(self::$instances[$type])) {
            $oldInstance = self::$instances[$type];
            self::$instances[$type] = NULL;
        }
        // Then create the new instance, giving it context if it wants it
        $typeInfo = self::$singletonClasses[$type][$name];
        $class = $typeInfo['fqcn'];
        if(isset(self::$classMap[$class]))
            $class = self::$classMap[$class];
        if($typeInfo['takesContext']) {
            $newInstance = new $class($oldInstance);
        } else {
            $newInstance = new $class;
        }
        self::setTypeInstance($type, $newInstance);
    }

    private static function setTypeInstance($type, $instance) {
        // We only care about cache when setting the dirty status
        if($type == "Cache") {
            self::makeDirty();
        }

        self::$instances[$type] = $instance;
    }

    private static function selectDefault($type) {
        // First off, check that we know about this type
        if(!isset(self::$singletonClasses[$type]) || !isset(self::$singletonClasses[$type]['default'])) {
            $typeName = ObjectFactory::getObjectName($type);
            if(!$typeName) {
                throw new \Exception("No default object found for type $type");
            }
            self::select($type, $typeName);
            return;
        }
        
        // By default, just use the element named default
        $typeInfo = self::$singletonClasses[$type]['default'];
        $class = $typeInfo['fqcn'];
        if(isset(self::$classMap[$class]))
            $class = self::$classMap[$class];

        // Instantiate it
        try
        {
            $newInstance = new $class;
        }
        catch(\Fossil\Exceptions\SelectionChangedException $e) {
            return;
        }
        // And store it
        self::setTypeInstance($type, $newInstance);
    }

    private static function resolveInstanceClass($typeOrFqcn, $subtype = null) {
        if($subtype) {
            if(!isset(self::$instancedClasses[$typeOrFqcn]) || !isset(self::$instancedClasses[$typeOrFqcn][$subtype]))
                throw new NoSuchClassException($typeOrFqcn, $subtype);

            $typeOrFqcn = self::$instancedClasses[$typeOrFqcn][$subtype];
        }
        if(isset(self::$classMap[$typeOrFqcn]))
            return self::$classMap[$typeOrFqcn];
        // Fallback, if none was found
        return $typeOrFqcn;
    }

    public static function obj($typeOrFqcn, $subtype = null) {
        $actualClass = self::resolveInstanceClass($typeOrFqcn, $subtype);

        if(!isset(self::$instanceWrappers[$actualClass]))
            self::$instanceWrappers[$actualClass] = new InstanceWrapper($actualClass);

        return self::$instanceWrappers[$actualClass];
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
    public static function get($type) {
        if(!isset(self::$instances[$type])) {
            self::selectDefault($type);
        }
        return self::$instances[$type];
    }
    
    public static function getSpecific($type, $name) {
        if(!isset(self::$singletonClasses[$type]))
            throw new Exception("Unknown type: $type");
        if(!isset(self::$singletonClasses[$type][$name]))
            throw new Exception("Unknown class of type $type: $name");
        return self::$singletonClasses[$type][$name];
    }

    public static function getAll($type) {
        return self::$singletonClasses[$type];
    }

    public static function getBaseObjects() {
        // TODO: Determine whether to only compile in-use objects or not
        $classList = array();

        foreach(self::$singletonClasses as $classArr) {
            foreach($classArr as $classDat) {
                // Don't return already compiled objects - that way, madness lies
                if(strpos($classDat['fqcn'], '\\Fossil\\Compiled') !== 0) {
                    $classList[] = $classDat['fqcn'];
                }
            }
        }
        foreach(self::$instancedClasses as $typeArr) {
            foreach($typeArr as $type)
                $classList[] = $type;
        }

        return $classList;
    }

    public static function getExtensionClasses() {
        return self::$extensionClasses;
    }

    /**
     * Checks whether the manager has an instance of a given type
     * 
     * @param string $type The type to check for
     * @return string TRUE if the type has been instantiated
     */
    public static function has($type) {
        return isset(self::$instances[$type]);
    }

    /**
     * Checks whether the manager knows about a given type
     * 
     * Can also be used to check for a specific provider's
     * availability by passing it's name as the second parameter
     * 
     * @param string $type The name of the type to check for
     * @param mixed $name The name of the provider to check for, or NULL
     */
    public static function knows($type, $name=NULL) {
        if(!isset(self::$singletonClasses[$type]))
            return false;
        if($name && !isset(self::$singletonClasses[$type][$name]))
            return false;
        return true;
    }

    /**
     * Static magic method calls
     * 
     * Used to implement singleton access functions
     * 
     * @param string $type The type to access
     * @param mixed $args Should always be array()
     * @throws BadMethodCallException
     * @return mixed A singleton instance of the requested type
     */
    public static function __callStatic($type, $args) {
        if(isset(self::$instances[$type]) || isset(self::$singletonClasses[$type])) {
            $obj = self::get($type);
            if(count($args) > 0)
                return call_user_func_array(array($obj, 'get'), $args);
            else
                return self::get($type);
        }
        throw new \BadMethodCallException("Method '$type' does not exist");
    }
    
    protected static $appNS;
    public static function setApp($namespace, $path) {
        Autoloader::addNamespacePath($namespace, $path);
        self::FS()->setAppRoot($path);
        self::$appNS = $namespace;
    }
    public static function appNamespace() {
        return self::$appNS;
    }
    
    protected static $overlayNS;
    public static function setOverlay($namespace, $path) {
        Autoloader::addNamespacePath($namespace, $path);
        self::FS()->setOverlayRoot($path);
        self::$overlayNS = $namespace;
    }
    public static function overlayNamespace() {
        return self::$overlayNS;
    }
    
    public static function getFossilID() {
        $name = "Fossil";
        if(self::$appNS) {
            $name .= "_" . basename(self::$appNS);
        }
        if(self::$overlayNS) {
            $name .= "_" . basename(self::$overlayNS);
        }
        return $name;
    }
}