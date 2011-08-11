<?php
/**
 * Defines the Fossil class, which provides startup methods
 * 
 * @author predakanga
 * @since 0.1
 * @package Fossil
 */

namespace Fossil;

/**
 * The Fossil startup class
 * 
 * Perhaps misleadingly, the Fossil class is only a
 * shell of a class, providing startup functions, for
 * subprojects and direct users of Fossil, but no real
 * functionality
 * 
 * @author predakanga
 * @since 0.1
 */
class Fossil {
        const AssertionsAndChecks       =   1; // 1 >> 0
        const LogWarnings               =   2; // 1 >> 1
        const DisplayWarnings           =   4; // 1 >> 2
        const LogErrors                 =   8; // 1 >> 3
        const DisplayErrors             =  16; // 1 >> 4
        const CacheData                 =  32; // 1 >> 5
        const CacheCode                 =  64; // 1 >> 6
        const CacheInstances            = 128; // 1 >> 7
        const FileTimesInvalidateCache  = 256; // 1 >> 8
        
        /**
         * Mode constant - Production Mode
         * 
         * Specifies that Fossil should run in production mode.
         * Enables all caches, and doesn't check for file modifications.
         * 
         * @var int
         */
        const PRODUCTION = 232;
        // CacheData & CacheCode & CacheInstances & LogErrors
        
	/**
	 * Mode constant - Release Mode
	 * 
	 * Specifies that Fossil should run in release mode.
	 * Enables all caches and disables assertions and tests
	 * 
	 * @var int
	 */
	const RELEASE = 488;
        // CacheData & CacheCode & CacheInstances & FileTimesInvalidateCache & LogErrors
        
	/**
	 * Mode constant - Testing Mode
	 * 
	 * Specifies that Fossil should run in testing mode.
	 * Enables all caches and enables assertions, logs all errors and warnings
	 * 
	 * @var int
	 */
	const TESTING = 490;
        // CacheData & CacheCode & CacheInstances & FileTimesInvalidateCache & LogErrors & LogWarnings
        
	/**
	 * Mode constant - Debug Mode
	 * 
	 * Specifies that Fossil should run in debug mode.
	 * Enables most caches and disables assertions, but displays all errors and logs warnings
	 * 
	 * @var int
	 */
	const DEBUG = 498;
        // CacheData & CacheCode & CacheInstances & FileTimesInvalidateCache & DisplayErrors & LogWarnings
        
	/**
	 * Mode constant - Development Mode
	 * 
	 * Specifies that Fossil should run in development mode.
	 * Disables all caches and makes all assertions and tests run and display
	 * 
	 * @var int
	 */
	const DEVELOPMENT = 21;
        // AssertionsAndChecks & DisplayErrors & DisplayWarnings
	
	/**
	 * Checks whether Fossil can run in the current environment
	 * 
	 * Tests for various core dependencies, such as PHP >=5.3 and
	 * valid settings that Fossil requires to run
	 * 
	 * @param string $errorStr Reference to a string in which to place the error string, if any
	 * @return bool TRUE if the environment is suitable for Fossil
	 */
	static function checkEnvironment(&$errorStr = NULL) {
		// To begin with, just check for the right version of PHP
		if(!version_compare(PHP_VERSION, "5.3.0", ">=")) {
			if($errorStr)
				$errorStr = "PHP must be at least version 5.3 - yours is " . PHP_VERSION;
			return false;
		}
		return true;
	}
	
	/**
	 * Bootstraps Fossil and returns a FossilCore instance, ready to use
	 * 
	 * Bootstraps Fossil by determining various pieces of information such
	 * as the install directory, etc, sets up the autoloader, and finally
	 * instantiates Fossil
	 * 
	 * Note: Files are required from within this function to keep the relevant
	 * code compacted, and to avoid any requires in the header.
	 * 
	 * @param int $mode Mode to run Fossil in - defaults to PRODUCTION
	 * @return Fossil\Core A Fossil instance ready to serve requests, or NULL
	 */
	static function bootstrap($mode=PRODUCTION) {
		// First thing's first, check that the environment is okay for Fossil
		if(($mode & self::AssertionsAndChecks) && !self::checkEnvironment())
			return NULL;
		
		// Next, set up the autoloaders
		require_once("Autoloader.php");
		Autoloader::registerAutoloader();
		
		// Then, perform one-time initialization on the object manager
                OM::setup();
		// Use a cached initialization if possible and not in debug mode
		if($mode & self::CacheInstances)
			OM::cachedInit() or OM::init();
		else
			OM::init();
		
		// And get the core object from it
		return OM::Core();
	}
}

?>