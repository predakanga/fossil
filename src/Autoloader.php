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

if(!defined('D_S')) {
    define('D_S', DIRECTORY_SEPARATOR);
}

/**
 * The Fossil autoloader class
 * 
 * @author predakanga
 * @since 0.1
 */
class Autoloader {
    /**
     * A map of namespace names to classpaths
     * 
     * @var array
     */
    static $classPaths = array();
    static $doctrineClassLoader;
    
    /**
     * Bootstrap function for the autoloader
     * 
     * Registers the autoloader at the end of the SPL autoload stack
     * 
     * @return void
     */
    static public function registerAutoloader() {
        // First, add our loading function to the SPL autoload stack
        spl_autoload_register(array("\\Fossil\\Autoloader", "autoload"));
        // Then add in our own namespace
        self::addNamespacePath("Fossil", __DIR__);
        self::addNamespacePath("TokenReflection", stream_resolve_include_path("TokenReflection"));
        // Also add in the default Doctrine classloader, to pass off responsibility
        require_once 'Doctrine/ORM/Tools/Setup.php';
        \Doctrine\ORM\Tools\Setup::registerAutoloadPEAR();
    }
    
    /**
     * Adds a namespace to Fossil's autoloader
     * 
     * Configures Fossil's autoloader to either load or ignore a given namespace.
     * Specify NULL for $classPath to ignore the namespace. Otherwise, specify the
     * include directory, relative to the include path.
     * 
     * @param string $namespace The namespace to be autoloaded
     * @param mixed $classPath String containing the prefix for the namespace, or NULL
     * @return void
     */
    static public function addNamespacePath($namespace, $classPath) {
        assert('!array_key_exists("' . $classPath . '", Fossil\\Autoloader::$classPaths)');
        // When storing the path, store it lowercased, for compatibility reasons
        self::$classPaths[$namespace] = $classPath;
    }
    
    /**
     * Autoload function
     * 
     * Loads a class, given the current configuration data
     * 
     * @param string $classname The fully qualified class name to be loaded
     * @internal Only really checks whether we *should* load this class
     * @return void
     */
    static public function autoload($classname) {
        // If it begins with a \, kill that
        if($classname[0] == '\\')
            $classname = substr($classname, 1);
        // First, determine the namespace
        $classPaths = explode("\\", $classname);
        $resolvePath = array();
        $actualClass = array_pop($classPaths);
        // Then, check each successive part of the namespace for a path
        do
        {
            $classPath = implode("\\", $classPaths);
            if(array_key_exists($classPath, self::$classPaths)) {
                    if(self::$classPaths[$classPath]) {
                        array_push($resolvePath, $actualClass);
                        self::loadClass($classPath, implode(DIRECTORY_SEPARATOR, $resolvePath));
                        return;
                    }
            }
            array_unshift($resolvePath, array_pop($classPaths));
        } while(count($classPaths) > 0);
    }
    
    /**
     * Loads a class from a given namespace
     * 
     * Uses the namespace and name of a class to
     * require the appropriate class file
     * 
     * @param string $namespace
     * @param string $class
     * @return void
     */
    static private function loadClass($namespace, $class) {
        // Fold the class name into the path
        $fullClassPath = self::$classPaths[$namespace] . DIRECTORY_SEPARATOR . $class . ".php";
        // And require it
        if(file_exists($fullClassPath))
            include_once($fullClassPath);
    }
}

?>