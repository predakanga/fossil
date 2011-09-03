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
	private static $classes = array(
        'FS' => array('default' => array('fqcn' => '\\Fossil\\Filesystem', 'takesContext' => false)),
        'Annotations' => array('default' => array('fqcn' => '\\Fossil\\Annotations\\AnnotationManager', 'takesContext' => false)),
        'Error' => array('default' => array('fqcn' => '\\Fossil\\ErrorManager', 'takesContext' => false))
    );
    private static $dirty = false;

    // Destructor, to update the quickstart file
    public static function shutdown() {
        if(file_exists(".quickstart.yml") && !self::$dirty) {
            // If we don't have anything to update, return
            return;
        }
        // Otherwise, build up a document with everything we need, and emit it
        $quickstart = array();
        //$quickstart['cache'] = self::Cache()->getSetup();

        // And output the document
        file_put_contents(__DIR__ . '/.quickstart.yml', yaml_emit($quickstart));
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
        // Once we've loaded all files, grab the list of objects
        $allObjects = self::Annotations()->getClassesWithAnnotation("F:Object");
        foreach($allObjects as $object) {
            $annotations = self::Annotations()->getClassAnnotations($object, "F:Object");
            foreach($annotations as $objAnno) {
                $type = $objAnno->value ?: $objAnno->type;
                if(!isset(self::$classes[$type]))
                    self::$classes[$type] = array();
                self::$classes[$type][$objAnno->name] = array('fqcn' => '\\' . $object, 'takesContext' => $objAnno->takesContext);
            }
        }
        static::$extensionClasses = self::Annotations()->getClassesWithAnnotation("F:ExtensionClass");
    }

    public static function setup() {
        self::$startupTime = microtime(true);
        // Set up a shutdown function to handle writing out the quickstart file
        // TODO: Probably register the shutdown func only when dirty is set
        register_shutdown_function(array(__CLASS__, "shutdown"));
        self::Error()->init(E_ALL | E_STRICT);
        /*
        // Load the basic settings from 'quickstart.yml'
        $basics = yaml_parse_file(__DIR__ . '/.quickstart.yml');
        // Return if we have no quickstart settings
        if(!$basics)
            return;
        // If we have settings, grab the cache
        if(isset($basics['cache'])) {
            // Bypass the setTypeInstance function so as not to dirty the context
            self::$instances['Cache'] = new $basics['cache']['fqcn']($basics['cache']['options']);
        }*/
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
            // Regular functionality:
            // Scan local namespace for objects
            self::scanForObjects(self::FS()->fossilRoot());
            // Load settings up, set up drivers
            // Scan plugin namespaces for objects
            // Do compilation
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
        self::setTypeInstance($type, $newInstance);
	}
    
    private static function setTypeInstance($type, $instance) {
        // We only care about cache when setting the dirty status
        if($type == "Cache") {
            self::$dirty = true;
        }
        
        self::$instances[$type] = $instance;
    }
    
    private static function selectDefault($type) {
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
        try
        {
            $newInstance = new $typeInfo['fqcn'];
        }
        catch(\Fossil\Exceptions\SelectionChangedException $e) {
            return;
        }
        // And store it
        self::setTypeInstance($type, $newInstance);
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
	
    public static function getAll($type) {
        return self::$classes[$type];
    }
    
    public static function getBaseObjects() {
        // TODO: Determine whether to only compile in-use objects or not
        $classList = array();
        
        foreach(self::$classes as $classArr) {
            foreach($classArr as $classDat) {
                // Don't return already compiled objects - that way, madness lies
                if(!strpos($classDat['fqcn'], 'Fossil\\Compiled') === 0)
                    $classList[] = $classDat['fqcn'];
            }
        }
        
        return $classList;
    }
    
    public static function getExtensionClasses() {
        return static::$extensionClasses;
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
            $obj = self::get($type);
            if(count($args) > 0)
                return call_user_func_array(array($obj, 'get'), $args);
            else
                return self::get($type);
		}
		throw new \BadMethodCallException("Method '$type' does not exist");
	}
}