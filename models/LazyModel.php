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
 * Description of LazyModel
 *
 * @author predakanga
 */
class LazyModel {
    protected $targetEntity;
    protected $srcEntity;
    protected $srcField;
    protected $loaded = false;
    protected $model;
    
    public function __construct($targetEntity, $srcEntity, $srcField) {
        $this->targetEntity = $targetEntity;
        $this->srcEntity = $srcEntity;
        $this->srcField = $srcField;
    }
    
    protected function __load() {
        $q = OM::ORM()->getEM()->createQuery("SELECT src FROM {$this->srcEntity} src
                                              JOIN src.{$srcField} dst WHERE dst = ?1");
        $q->setParameter(1, $this->targetEntity);
        $this->model = $q->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
    }
    
    public function __get($key) {
        if(!$this->loaded)
            $this->__load();
        return $this->model->$key;
    }
    
    public function __set($key, $value) {
        if(!$this->loaded)
            $this->__load();
        $this->model->$key = $value;
    }
    
    public function __isset($key) {
        if(!$this->loaded)
            $this->__load();
        return isset($this->model->$key);
    }
    
    public function __unset($key) {
        if(!$this->loaded)
            $this->__load();
        unset($this->model->$key);
    }
    
    public function __call($funcname, $args) {
        if(!$this->loaded)
            $this->__load();
        return call_user_func_array(array($this->model, $funcname), $args);
    }
    
    public static function __callStatic($funcname, $args) {
        throw new \Exception("Can't call static methods on a LazyModel.");
    }
}

?>
