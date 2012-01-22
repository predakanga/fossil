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

namespace Fossil\Plugins\Firebug;

require_once("libs/FirePHP.class.php");

/**
 * Description of FossilFirePHP
 *
 * @author predakanga
 */
class FireFossil extends \FirePHP {
    protected $headers = array();
    
    public function __construct() {
        $this->setOption('maxArrayDepth', 3);
        $this->setOption('maxObjectDepth', 1);
    }
    
    protected function setHeader($Name, $Value) {
        $this->headers[$Name] = $Value;
    }
    
    public function sendHeaders() {
        foreach($this->headers as $name => $value) {
            header($name . ": " . $value);
        }
    }
    
    function exceptionHandler($Exception) {
        parent::exceptionHandler($Exception);
        $this->sendHeaders();
    }
    
    // FirePHP's library lacks late static binding...
    // <editor-fold defaultstate="collapsed" desc="So we took care of it ourselves">
    /**
     * Gets singleton instance of FirePHP
     *
     * @param boolean $AutoCreate
     * @return FireFossil
     */
    public static function getInstance($AutoCreate = true)     {
        if($AutoCreate === true && !self::$instance) {
            static::init();
        }
        return static::$instance;
    }


    /**
     * Creates FirePHP object and stores it for singleton access
     *
     * @return FireFossil
     */
    public static function init()     {
        return static::setInstance(new static());
    }

    /**
     * Set the instance of the FirePHP singleton
     * 
     * @param FireFossil $instance The FirePHP object instance
     * @return FireFossil
     */
    public static function setInstance($instance)     {
        return static::$instance = $instance;
    }
// </editor-fold>
}

?>
