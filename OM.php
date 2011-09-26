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
     *
     * @var ObjectRepository
     */
    private static $objectRepo;

    private static $startupTime = 0;

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

    public static function setup($considerMtimes = true) {
        self::$startupTime = microtime(true);
        // Start with a basic object repo
        self::$objectRepo = new ObjectRepository();
        
//        self::Error()->init(E_ALL | E_STRICT);
        
        // Load the basic settings from 'quickstart.yml'
        if(!file_exists(self::FS()->tempDir() . D_S . '.quickstart.yml'))
            return;
        // Ignore the quickstart if it's newer than settings.yml
        if($considerMtimes && file_exists(self::FS()->execDir() . D_S . 'settings.yml')) {
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
            self::$objectRepo->setSingleton("Cache", new $basics['cache']['fqcn']($basics['cache']['config']));
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
    public static function cachedInit($considerMtimes = true) {
        if(!self::has("Cache"))
            return false;
        
        $cachedData = OM::Cache("fossil_state");
        if(!$cachedData)
            return false;
        
        // Check the mtimes of basic classes
        if($considerMtimes)
            if(self::getFossilMtime() > $cachedData['mtime'])
                return false;
        
        self::$objectRepo = $cachedData['objects'];
        
        // Get the compiler, to set up the namespace path
        self::Compiler()->registerAutoloadPath();
        
        self::Annotations()->loadFromCache($cachedData['annotations']);
        
        // Then after loading the plugin manager etc, check mtimes again, just in case
        self::Plugins()->loadEnabledPlugins();
        if($considerMtimes)
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
        self::$objectRepo->scanForObjects(false);
        // Load settings up, set up drivers
        self::Compiler()->registerAutoloadPath();
        self::$objectRepo->getSingleton('Cache');
        self::ORM()->ensureSchema(true);
        // Register plugins
        // TODO: Move to auto-plugin loader
        OM::Plugins()->loadEnabledPlugins();
        // Scan plugin namespaces for objects
        self::$objectRepo->scanForObjects(true);
        // Do compilation
        $tempClassMap = OM::Compiler()->bootstrap();
        OM::Compiler()->setClassMap($tempClassMap);
        self::$objectRepo->setClassMap(OM::Compiler()->compileAll());
    }

    public static function saveCache() {
        $cachedData = array();
        $cachedData['objects'] = self::$objectRepo;
        $cachedData['annotations'] = self::Annotations()->dumpForCache();
        $cachedData['mtime'] = self::getFossilMtime();
        
        OM::Cache()->set("fossil_state", $cachedData);
    }

    public static function obj($typeOrFqcn, $subtype = null) {
        return self::$objectRepo->getInstanceWrapper($typeOrFqcn, $subtype);
    }
    
    public static function getAllInstanced($type) {
        return self::$objectRepo->getAllInstanceClasses($type);
    }
    
    public static function getSpecificSingleton($type, $name) {
        return self::$objectRepo->getSpecificSingleton($type, $name);
    }

    public static function getAllSingletons($type) {
        return self::$objectRepo->getAllSingletonClasses($type);
    }

    public static function getObjectsToCompile() {
        // TODO: Determine whether to only compile in-use objects or not
        $classList = array();

        $singletonClasses = self::$objectRepo->getAllSingletonClasses();
        foreach($singletonClasses as $classArr) {
            foreach($classArr as $classDat) {
                // Don't return already compiled objects - that way, madness lies
                if(strpos($classDat['fqcn'], '\\Fossil\\Compiled') !== 0) {
                    $classList[] = $classDat['fqcn'];
                }
            }
        }
        $instancedClasses = self::$objectRepo->getAllInstanceClasses();
        foreach($instancedClasses as $typeArr) {
            foreach($typeArr as $type)
                $classList[] = $type;
        }

        return $classList;
    }

    public static function getExtensionClasses() {
        return self::$objectRepo->getExtensionClasses();
    }

    /**
     * Checks whether the manager has an instance of a given type
     * 
     * @param string $type The type to check for
     * @return string TRUE if the type has been instantiated
     */
    public static function has($type) {
        return self::$objectRepo->hasSingleton($type);
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
    public static function knows($type, $name = null) {
        return self::$objectRepo->knowsSingleton($type, $name);
    }

    public static function select($type, $name) {
        self::$objectRepo->selectSingleton($type, $name);
    }
    
    public static function setSingleton($type, $instance) {
        self::$objectRepo->setSingleton($type, $instance);
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
        try
        {
            $obj = self::$objectRepo->getSingleton($type);
            if(count($args) > 0)
                return call_user_func_array(array($obj, 'get'), $args);
            else
                return $obj;
        }
        catch(Exception $e) {
            throw new \BadMethodCallException("Method '$type' does not exist");
        }
    }
    
    protected static $appNS;
    public static function setApp($namespace, $path) {
        // Strip the trailing \ if any is given
        $namespace = rtrim($namespace, "\\");
        Autoloader::addNamespacePath($namespace, $path);
        self::FS()->setAppRoot($path);
        self::$appNS = $namespace;
    }
    public static function appNamespace() {
        return self::$appNS;
    }
    
    protected static $overlayNS;
    public static function setOverlay($namespace, $path) {
        // Strip the trailing \ if any is given
        $namespace = rtrim($namespace, "\\");
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
    
    public static function getFossilName() {
        $name = "Fossil";
        if(isset(self::$overlayNS))
            return basename(self::$overlayNS);
        else if(isset(self::$appNS))
            return basename(self::$appNS);
        else
            return $name;
    }
}