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

namespace Fossil\DoctrineExtensions;

use Doctrine\Common\Cache\AbstractCache,
    Fossil\ObjectContainer,
    Fossil\Caches\BaseCache;

/**
 * Description of FossilCache
 *
 * @author predakanga
 */
class FossilCache extends AbstractCache {
    /**
     * @var Fossil\Caches\BaseCache
     */
    protected $fossilCache;
    
    protected function __construct(BaseCache $cache) {
        $this->fossilCache = $cache;
    }
    
    public function getIds() {
        return array();
    }
    
    protected function _doFetch($id) {
        return $this->fossilCache->get($id, true);
    }
    
    protected function _doContains($id) {
        return $this->fossilCache->has($id, true);
    }
    
    protected function _doSave($id, $data, $lifetime = 0) {
        $this->fossilCache->set($id, $data, true);
    }
    
    protected function _doDelete($id) {
        $this->fossilCache->delete($id, true);
    }
    
    public static function create(ObjectContainer $container) {
        // Because NoCache breaks Doctrine components somehow, return an ArrayCache
        // when we're using that
        $cache = $container->get("Cache");
        if($cache->getName() == "None") {
            return new \Doctrine\Common\Cache\ArrayCache();
        } else {
            return new self($cache);
        }
    }
}
