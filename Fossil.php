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
	/**
	 * Mode constant - Release Mode
	 * 
	 * Specifies that Fossil should run in release mode.
	 * Enables all caches and disables assertions and tests
	 * 
	 * @var int
	 */
	const RELEASE = 0;
	/**
	 * Mode constant - Testing Mode
	 * 
	 * Specifies that Fossil should run in testing mode.
	 * Enables all caches and enables assertions, displays all errors and warnings
	 * 
	 * @var int
	 */
	const TESTING = 1;
	/**
	 * Mode constant - Debug Mode
	 * 
	 * Specifies that Fossil should run in debug mode.
	 * Enables most caches and disables assertions, but displays all errors and logs warnings
	 * 
	 * @var int
	 */
	const DEBUG = 2;
	/**
	 * Mode constant - Development Mode
	 * 
	 * Specifies that Fossil should run in development mode.
	 * Disables all caches and makes all assertions and tests run and display
	 * 
	 * @var int
	 */
	const DEVELOPMENT = 3;
	
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
	 * @return Fossil\FossilCore A Fossil instance ready to serve requests, or NULL
	 */
	static function bootstrap($mode=PRODUCTION) {
		// First thing's first, check that the environment is okay for Fossil
		if($mode > RELEASE && !self::checkEnvironment())
			return NULL;
		
		// Next, set up the autoloaders
		require_once("Autoloader.php");
		Autoloader::registerAutoloader();
		
		// Then, perform one-time initialization on the object manager
		// Use a cached initialization if possible and not in debug mode
		if($mode >= DEBUG)
			OM::init();
		else
			OM::cachedInit() or OM::init();
		
		// And get the core object from it
		return OM::core();
	}
}

?>