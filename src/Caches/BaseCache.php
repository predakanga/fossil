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

use Fossil\BaseDriver,
    Fossil\OM;

/**
 * Description of BaseCache
 *
 * @author predakanga
 */
abstract class BaseCache extends BaseDriver {
    protected $prefix;
    /**
     * @F:Inject("Core")
     * @var Fossil\Core
     */
    protected $core;
    
    public function __construct($container) {
        $this->driverType = "Cache";
        
        parent::__construct($container);
        $this->prefix = $this->core->getInstanceID();
    }
    
    protected function versionKey($key) {
        return $key . "_" . $this->core->getInstanceHash();
    }
    
    public function has($key, $versioned_key = false) {
        if($versioned_key)
            $key = $this->versionKey($key);
        return $this->_has($this->prefix . $key);
    }
    public function get($key, $versioned_key = false) {
        if($versioned_key)
            $key = $this->versionKey($key);
        return $this->_get($this->prefix . $key);
    }
    public function set($key, $value, $versioned_key = false) {
        if($versioned_key)
            $key = $this->versionKey($key);
        $this->_set($this->prefix . $key, $value);
    }
    public function update($key, $update_cb, $versioned_key = false) {
        if($versioned_key)
            $key = $this->versionKey($key);
        $this->_update($this->prefix . $key, $update_cb);
    }
    
    abstract protected function _has($key);
    abstract protected function _get($key);
    abstract protected function _set($key, $value);
    abstract protected function _update($key, $update_cb);
}

?>
