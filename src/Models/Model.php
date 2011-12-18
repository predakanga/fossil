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
 * @subpackage Models
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil\Models;

use Fossil\Object,
    Fossil\Exceptions\ValidationFailedException,
    Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Description of Model
 *
 * @F:InstancedType("Model")
 * @author predakanga
 */
abstract class Model extends Object {
    /**
     * @F:Inject("ORM")
     * @var Fossil\ORM
     */
    protected $orm;
    
    private function getMetadata() {
        return $this->orm->getEM()->getClassMetadata(get_class($this));
    }
    
    public function __construct($container) {
        parent::__construct($container);
        
        // Automatically create ArrayCollections for associations
        foreach($this->getMetadata()->getAssociationMappings() as $mapping) {
            if($mapping['type'] & ClassMetadataInfo::TO_MANY) {
                $field = $mapping['fieldName'];
                $this->$field = new \Doctrine\Common\Collections\ArrayCollection();
            }
        }
    }
    
    public function save() {
        $this->orm->getEM()->persist($this);
    }
    
    public function delete() {
        $this->orm->getEM()->remove($this);
    }
    
    public function __toString() {
        return htmlentities("<Entity (" . get_class($this) . ") " . var_export($this->id(), true) . ">");
    }
    
    public function id() {
        return $this->orm->getEM()->getUnitOfWork()->getEntityIdentifier($this);
    }
    
    public function get($key) {
        $methodName = "get" . ucfirst($key);
        if(method_exists($this, $methodName))
            return $this->$methodName();
        
        return $this->$key;
    }
    
    public function set($key, $value) {
        // First, validate the value
        if(!$this->validate($key, $value))
            throw new ValidationFailedException($this, $key, $value);
        
        $methodName = "set" . ucfirst($key);
        if(method_exists($this, $methodName))
            $this->$methodName($value);
        else
            $this->$key = $value;
    }
    
    public function has($key) {
        return property_exists($this, $key);
    }
    
    public function validate($key, $newValue) {
        $methodName = "validate" . ucfirst($key);
        if(method_exists($this, $methodName))
            return $this->$methodName($newValue);
        else
            return true;
    }
    
    public function __call($method, $arguments)
    {
        $func = substr($method, 0, 3);
        $fieldName = substr($method, 3);
        $fieldName = lcfirst($fieldName);

        if ($func == 'get') {
            return $this->get($fieldName);
        } else if ($func == 'set') {
            $this->set($fieldName, $arguments[0]);
        } else if ($func == 'has') {
            return $this->has($fieldName);
        } else {
            throw new \BadMethodCallException('Method ' . $method . ' does not exist on model ' . get_class($this));
        }
    }

    public function __set($key, $value) {
        $this->set($key, $value);
    }
    
    public function __get($key) {
        return $this->get($key);
    }
    
    public function __isset($key) {
        return $this->has($key) && isset($this->$key);
    }
    
    public function __unset($key) {
        unset($this->$key);
    }
    
    public static function __callStatic($method, $arguments)
    {
        // First argument must be container
        $container = array_shift($arguments);
        assert($container instanceof \Fossil\ObjectManager);
        $orm = $container->get("ORM");
        return call_user_func_array(
            array($orm->getEM()->getRepository(get_called_class()), $method),
            $arguments
        );
    }
    
    public static function createFromArray($diContainer, $data) {
        $model = new static($diContainer);
        $classMetadata = $orm->getEM()->getClassMetadata(get_called_class());
        
        foreach($data as $key => $value) {
            if($classMetadata->hasAssociation($key)) {
                $mapping = $classMetadata->getAssociationMapping($key);
                $targetClass = $classMetadata->getAssociationTargetClass($key);
                
                if($mapping['type'] & ClassMetadataInfo::TO_MANY) {
                    $collection = new \Doctrine\Common\Collections\ArrayCollection();

                    foreach($value as $targetData) {
                        $targetEntity = $targetClass::findOneBy($diContainer, $targetData);
                        if(!$targetEntity) {
                            throw new \Exception("Required entity not found: $targetClass (" . var_export($targetData, true) . ")");
                        }
                        $collection->add($targetEntity);
                    }

                    $model->set($key, $collection);
                } else {
                    $targetEntity = $targetClass::findOneBy($diContainer, $value);
                    if(!$targetEntity) {
                        throw new \Exception("Required entity not found: $targetClass (" . var_export($targetData, true) . ")");
                    }
                    $model->set($key, $targetEntity);
                }
            } else {
                $model->set($key, $value);
            }
        }
        return $model;
    }
    
    /**
     *
     * @param type $field
     * @param type $fieldsPerPage
     * @return PaginationProxy
     */
    public function paginate($field, $fieldsPerPage = 10) {
        if(!$this->getMetadata()->isCollectionValuedAssociation($field))
            throw new \Exception("Attempted to paginate a single entity");
        return new PaginationProxy($this->get($field), $fieldsPerPage);
    }
    
    public function toArray() {
        // TODO: Decide how to handle associations
        $toRet = array();
        foreach($this->getMetadata()->getFieldNames() as $field) {
            $toRet[$field] = $this->get($field);
        }
        return $toRet;
    }
}

?>
