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
        if($mode & self::CacheInstances) {
            if(!OM::cachedInit())
                OM::init();
        } else {
            OM::init();
        }

        // And get the core object from it
        return OM::Core();
    }
}

?>