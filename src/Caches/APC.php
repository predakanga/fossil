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
 * @subpackage Caches
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil\Caches;

/**
 * Description of APC
 *
 * @author predakanga
 * @F:Provides("Cache")
 */
class APC extends BaseCache {
    public static function usable() {
        return (extension_loaded('apc') && (ini_get('apc.enabled') == 1));
    }
    public static function getName() {
        return "APC";
    }
    public static function getVersion() {
        return 1.0;
    }
    
    public static function getFormName() {
        return null;
    }
    
    protected function _has($key) {
        $toRet = apc_fetch($key);
        return ($toRet !== false);
    }
    
    protected function _get($key) {
        return apc_fetch($key);
    }
    
    protected function _set($key, $value) {
        apc_store($key, $value);
    }
    
    protected function _update($key, $update_cb) {
        $cas = 0;
        $success = false;
        
        // TODO: Add extra checking, so that on other error conditions, it ends
        do
        {
            $value = apc_fetch($key);
            $success = apc_cas($key, $value, $update_cb($value));
        } while(!$success);
    }
}

?>
