<?php
/**
 * Defines the Fossil Autoloader and provides means to configure it
 * 
 * @author predakanga
 * @since 0.1
 * @package Fossil
 */

namespace Fossil;

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
	
	/**
	 * Bootstrap function for the autoloader
	 * 
	 * Registers the autoloader at the end of the SPL autoload stack
	 * 
	 * @return void
	 */
	static public function registerAutoloader() {
		// First, add our loading function to the SPL autoload stack
		spl_autoload_register(array("\Fossil\Autoloader", "autoload"));
		// Then add in our own namespace
		self::addNamespacePath("Fossil", dirname(__FILE__));
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
                    array_push($resolvePath, array_pop($classPaths));
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
		assert("file_exists('$fullClassPath')");
		require_once($fullClassPath);
	}
}

?>