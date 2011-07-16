<?php
/**
 * Defines the object manager and the logic for discovering classes
 * 
 * @author predakanga
 * @since 0.1
 * @package Fossil
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
 * @method Fossil\Filesystem FS() FS() Returns the Fossile filesystem layer
 */
class OM {
	/**
	 * Maps type to current instance
	 * 
	 * @var array
	 */
	private static $instances = array();
        
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
	private static $classes = array(
            'Core' => array('default' => array('fqcn' => '\\Fossil\\Core', 'takesContext' => false)),
            'FS' => array('default' => array('fqcn' => '\\Fossil\\Filesystem', 'takesContext' => false))
        );
	
	/**
	 * Initialize the object manager without cache
	 * 
	 * Scans the codebase immediately to discover classes for
	 * the object manager to manage
	 * 
	 * @return void
	 */
	public static function init() {
		
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
		return false;
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
	public static function select($type, $name) {
		// Pre-condition: $name must be known as a $type
		// Post-condition: There will be an instance of $type stored in the OM
		$newInstance = NULL;
		$oldInstance = NULL;
		// Remove the current instance before instantiating the new one
		if(isset(static::$instances[$type])) {
			$oldInstance = static::$instances[$type];
			self::$instances[$type] = NULL;
		}
		// Then create the new instance, giving it context if it wants it
		$typeInfo = self::$classes[$type][$name];
		if($typeInfo['takesContext']) {
			$newInstance = new $typeInfo['fqcn']($oldInstance);
		} else {
			$newInstance = new $typeInfo['fqcn'];
		}
		self::$instances[$type] = $newInstance;
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
			// First off, check that we know about this type
			if(!isset(self::$classes[$type])) {
				// TODO: Throw an exception if the type isn't known
			}
			// By default, just use the element named default, or the first if none exists
			if(isset(self::$classes[$type]['default']))
				$typeInfo = self::$classes[$type]['default'];
			else
				$typeInfo = reset(self::$classes[$type]);
			// Instantiate it
			$newInstance = new $typeInfo['fqcn'];
			// And store it
			self::$instances[$type] = $newInstance;
		}
		return self::$instances[$type];
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
		if(!isset(self::$classes[$type]))
			return false;
		if($name && !isset(self::$classes[$type][$name]))
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
		if(isset(self::$instances[$type]) || isset(self::$classes[$type])) {
			return self::get($type);
		}
		throw new BadMethodCallException("Method '$type' does not exist");
	}
}