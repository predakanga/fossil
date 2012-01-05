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
 * Description of ErrorManager
 *
 * @author predakanga
 * @F:Provides("ErrorManager")
 * @F:DefaultProvider
 */
class ErrorManager {
    private $logMask = 11; // E_ERROR | E_WARNING | E_NOTICE
    private $showMask = 3; // E_ERROR | E_WARNING
    private $dieMask = 1; // E_ERROR
    private $log = array('errors' => array(), 'exceptions' => array());
    
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
            $this->log['errors'][] = array('errno' => $errno, 'errstr' => $errstr, 'errfile' => $errfile, 'errline' => $errline, 'backtrace' => $bt);
        }
        if($errno & $this->showMask) {
            echo "Encountered an error at $errfile:$errline\n";
            echo "$errstr\n\n";
        }
        if($errno & $this->dieMask) {
            die();
        }
    }
    
    public function exceptionHandler(\Exception $exception) {
        $exception->handled = false;
        $this->log['exceptions'][] = $exception;
    }
    
    public function logHandledException(\Exception $exception) {
        $exception->handled = true;
        $this->log['exceptions'][] = $exception;
    }
    
    public function getLog() {
        return $this->log;
    }
}
