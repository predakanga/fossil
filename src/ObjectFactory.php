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
 * 
 * @author predakanga
 * @since 0.1
 * @category Fossil Core
 * @package Fossil
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil;

/**
 * Description of ObjectFactory
 *
 * @author predakanga
 */
class ObjectFactory {
    protected static $knownTypes = array();
    
    protected function defaultFromSettings($type) {
        $driverName = OM::Settings("Fossil", strtolower($type));
        if(!$driverName)
            return null;
        return $driverName['driver'];
    }
    
    public function registerType($type, $default = null, $loadCB = array(__CLASS__, "defaultFromSettings")) {
        self::$knownTypes[$type] = array('default' => $default, 'cb' => $loadCB);
    }
    
    public function getConcreteClass($type) {
        $driverName = null;
        if(isset(self::$knownTypes[$type])) {
            $typeData = self::$knownTypes[$type];
            if(is_callable($typeData['cb'])) {
                $driverName = call_user_func($typeData['cb'], $type);
            }
            if(!$driverName) {
                if(is_callable($typeData['default']))
                    $driverName = call_user_func($typeData['default']);
                else
                    $driverName = $typeData['default'];
            }
        }
        
        if(!$driverName) {   
        }
        
        return $driverName;
    }
}

// Default factories for the three core drivers
ObjectFactory::registerType("Database", "SQLite");
ObjectFactory::registerType("Renderer", "Smarty");
ObjectFactory::registerType("Cache", function() {
    if(extension_loaded("apc")) {
        if(ini_get("apc.enabled") == 1)
            return "APC";
    }
    return "NoCache";
});

?>
