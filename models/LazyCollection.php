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

namespace Fossil\Models;

use Fossil\OM;

/**
 * Description of LazyCollection
 *
 * @author predakanga
 */
class LazyCollection implements \Doctrine\Common\Collections\Collection {
    protected $loaded = false;
    protected $query;
    protected $collection;
    
    public function  __construct($query) {
        $this->query = $query;
    }
    
    protected function __load() {
        $result = $this->query->getResult();
        $this->collection = new \Doctrine\Common\Collections\ArrayCollection($result);
        $this->loaded = true;
    }
    
    public function __call($name, $arguments) {
        if(!$this->loaded)
            $this->__load();
        return call_user_func_array(array($this->collection, $name), $arguments);
    }
    
    function add($element) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->add($element);
    }
    
    function clear() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->clear();
    }
    
    function contains($element) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->contains($element);
    }
    
    function isEmpty() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->isEmpty();
    }
    
    function remove($key) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->remove($key);
    }
    
    function removeElement($element) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->removeElement($element);
    }
    
    function containsKey($key) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->containsKey($key);
    }
    
    function get($key) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->get($key);
    }
    
    function getKeys() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->getKeys();
    }
    
    function getValues() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->getValues();
    }
    
    function set($key, $value) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->set($key, $value);
    }
    
    function toArray() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->toArray();
    }
    
    function first() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->first();
    }
    
    function last() {
        if(!$this->loaded)
            $this->__load();
       return $this->collection->last(); 
    }
    
    function key() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->key();
    }
    
    function current() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->current();
    }
    
    function next() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->next();
    }
    
    function exists(Closure $p) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->exists($p);
    }
    
    function filter(Closure $p) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->filter($p);
    }
    
    function forAll(Closure $p) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->forAll($p);
    }
    
    function map(Closure $func) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->map($func);
    }
    
    function partition(Closure $p) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->partition($p);
    }
    
    function indexOf($element) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->indexOf($element);
    }

    public function slice($offset, $length = null) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->slice($offset, $length);
    }
    
    public function offsetGet($offset) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->offsetGet($offset);
    }
    
    public function offsetSet($offset, $value) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->offsetSet($offset, $value);
    }
    
    public function offsetExists($offset) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->offsetExists($offset);
    }
    
    public function offsetUnset($offset) {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->offsetUnset($offset);
    }
    
    public function getIterator() {
        if(!$this->loaded)
            $this->__load();
        return $this->collection->getIterator();
    }
    
    public function count() {
        // TODO: Use an AST walker to do this cheaply
        if(!$this->loaded)
            $this->__load();
        return $this->collection->count();
    }
}

?>
