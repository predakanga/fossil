<?php

/*
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
 */

namespace Fossil\Logging\Debug;

use Fossil\Object;

/**
 * Description of BaseDebugLogger
 *
 * @author predakanga
 * @F:Provides("DebugLogger")
 */
abstract class BaseDebugLogger extends Object {
    const DEBUG = 0;
    const INFO = 3;
    const WARNING = 6;
    const ERROR = 9;
    
    protected $level = self::WARNING;
    
    public function setLevel($level) {
        $this->level = $level;
    }
    
    abstract public function _outputLogMessage($message);
    
    protected function formatMessage($message, $object) {
        $msg = rtrim($message);
        if($object) {
            $msg .= ": Object provided, type of " . get_class($object);
            if(method_exists($object, "__toString")) {
                $msg .= ", value " . $object->__toString();
            } else {
                $msg .= ", hash " . spl_object_hash($object);
            }
        }
        return $msg;
    }
    
    public function log($level, $message, $object = null) {
        if($level >= $this->level) {
            $this->_outputLogMessage($this->formatMessage($message, $object));
        }
    }
    public function debug($message, $object = null) {
        $this->log(self::DEBUG, $message, $object);
    }
    public function info($message, $object = null) {
        $this->log(self::INFO, $message, $object);
    }
    public function warning($message, $object = null) {
        $this->log(self::WARNING, $message, $object);
    }
    public function error($message, $object = null) {
        $this->log(self::ERROR, $message, $object);
    }
}

?>
