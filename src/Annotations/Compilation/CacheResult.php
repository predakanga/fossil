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

namespace Fossil\Annotations\Compilation;

/**
 * Description of CacheResult
 *
 * @author predakanga
 * @todo Add support for copying arbitrary methods into the final class, use that to provide
 *       a method to retrieve the keyname for cached results
 */
class CacheResult extends BaseCompilation {
    public $versionedKey = false;
    
    public function call($funcname, $args, $compileArgs) {
        if(count($args)) {
            throw new \Exception("CacheResult currently doesn't support methods with arguments");
        }
        
        static $cachedResult = null;
        
        if($cachedResult) {
            return $cachedResult;
        }
        
        if($this->container->has("Cache")) {
            $cache = $this->container->get("Cache");
            $keyName = get_class($this) . "_{$funcname}_cachedresult";
            if($cache->has($keyName, $compileArgs['versionedKey'])) {
                $cachedResult = $cache->get($keyName, $compileArgs['versionedKey']);
                return $cachedResult;
            } else {
                $result = $this->completeCall($funcname, $args);
                $cache->set($keyName, $result, $compileArgs['versionedKey']);
                return $result;
            }
        } else {
            // Don't memoize the result if we have no cache, fits the user's intentions better
            return $this->completeCall($funcname, $args);
        }
    }
}

?>
