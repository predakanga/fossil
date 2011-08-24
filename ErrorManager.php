<?php

namespace Fossil;

/**
 * Description of ErrorManager
 *
 * @author predakanga
 * @F:Object("Error")
 */
class ErrorManager {
    private $logMask = 11; // E_ERROR | E_WARNING | E_NOTICE
    private $showMask = 3; // E_ERROR | E_WARNING
    private $dieMask = 1; // E_ERROR
    private $log = array();
    
    public function __construct() {
        error_reporting(E_ALL | E_STRICT);
        // Set up an error and exception handler
        set_error_handler(array($this, "errorHandler"));
        //set_exception_handler(array($this, "exceptionHandler"));
    }
    
    public function init($logMask = 11, $showMask = 3, $dieMask = 1) {
        $this->logMask = $logMask;
        $this->showMask = $showMask;
        $this->dieMask = $dieMask;
    }
    
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
        if($errno & $this->logMask) {
            // Store to a log here
            // TODO: Only store the backtrace on specific occasions
            $bt = debug_backtrace();
            array_shift($bt);
            $this->log[] = array('errno' => $errno, 'errstr' => $errstr, 'errfile' => $errfile, 'errline' => $errline, 'backtrace' => $bt);
        }
        if($errno & $this->showMask) {
            echo "Encountered an error at $errfile:$errline\n";
            echo "$errstr\n\n";
        }
        if($errno & $this->dieMask) {
            die();
        }
    }
    
    public function exceptionHandler($exception) {
        
    }
    
    public function getLog() {
        return $this->log;
    }
}

?>
