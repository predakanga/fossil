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
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Proxy\Proxy,
    Doctrine\ORM\PersistentCollection,
    Doctrine\Common\Collections\ArrayCollection;

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
    protected $unattached = false;
    public static $unattachedDirty = array();
    
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
    
    public function __sleep() {
        // Don't worry about it if we're not already set up
        if(!$this->unattached) {
            // First, check whether this entity is dirty
            $uow = $this->orm->getEM()->getUnitOfWork();
            if($uow->getEntityState($this) == \Doctrine\ORM\UnitOfWork::STATE_MANAGED) {
                $uow->recomputeSingleEntityChangeSet($this->getMetadata(), $this);
                if(count($uow->getEntityChangeSet($this))) {
                    trigger_error("Serializing dirty entity " . get_class($this) . " can have dangerous side effects", E_USER_WARNING);
                }
            }
            // Replace all associations with custom serialization
            $md = $this->getMetadata();
            foreach($md->getAssociationMappings() as $field => $mapping) {
                if($md->isSingleValuedAssociation($field)) {
                    // Wrap the field in a PersistedAssoc
                    if($this->$field) {
                        $this->$field = new PersistedAssoc($this->$field->id());
                    }
                } elseif($md->isCollectionValuedAssociation($field)) {
                    $this->$field = new PersistedAssoc();
                }
            }
        }
        return parent::__sleep();
    }
    
    public function __wakeup() {
        if($this->id) {
            $this->unattached = true;
        }
    }
    
    private function _getData() {
        // Because Doctrine filters changeset data by the classMD's current representation of data,
        // we can simply return all object vars here (filtering out those that are injected)
        // Conveniently, this is exactly what Object::__sleep does
        $keys = parent::__sleep();
        $actualData = array();
        foreach($keys as $field) {
            $actualData[$field] = $this->{$field};
        }
        return $actualData;
    }
    
    public function reattach($container) {
        // TODO: Implementation details follow
        // In __sleep, store UOW->getOriginalEntityData($this) internally
        // Upon reattach, use registerManaged() with said data
        // Alternately, refuse to serialize unless all data is flushed (use UOW->recomputeSingleEntityChangeSet
        // then recompute OED on wakeup (it's simply an assoc array of managed fields)
        // To consider: Warn user that reattaching unversioned models can cause data stomping
        // Ideally, with the new working merge code, we should be able to just manually reattach with
        // registerManaged, then EntityManager::refresh($this)
        $this->restoreObjects($container);
        $md = $this->getMetadata();
        $em = $this->orm->getEM();
        // First, restore all associations
        foreach($md->getAssociationMappings() as $fieldName => $mapping) {
            $fieldValue = $this->{$fieldName};
            if($fieldValue) {
                if($fieldValue instanceof PersistedAssoc) {
                    $targetClass = $md->getAssociationTargetClass($fieldName);
                    if($md->isSingleValuedAssociation($fieldName)) {
                        // Get a reference to the associated entity
                        $this->$fieldName = $em->getReference($targetClass, $fieldValue->getID());
                    } elseif($md->isCollectionValuedAssociation($fieldName)) {
                        // Create a new PersistentCollection, uninitialized
                        $targetMD = $em->getClassMetadata($targetClass);
                        $coll = new PersistentCollection($em, $targetMD, new ArrayCollection);
                        $coll->setOwner($this, $md->getAssociationMapping($fieldName));
                        $coll->setInitialized(false);
                        $this->$fieldName = $coll;
                    }
                } else {
                    trigger_error("Reattaching a model with improperly persisted associations, of type " .
                                  get_class($this) . ".", E_USER_WARNING);
                }
            }
        }
        $this->orm->getEM()->getUnitOfWork()->registerManaged($this, $md->getIdentifierValues($this),
                                                              $this->_getData());
        if(!$md->isVersioned) {
            trigger_error("Reattaching an unversioned model, of type " . get_class($this) . ". " .
                          "This can stomp on DB data.", E_USER_WARNING);
        }
        $this->unattached = false;
        $key = array_search($this, self::$unattachedDirty);
        if($key !== false) {
            unset(self::$unattachedDirty[$key]);
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
    
    public function get($key, $direct = false) {
        if($direct) {
            return $this->$key;
        }
        $methodName = "get" . ucfirst($key);
        if(method_exists($this, $methodName)) {
            return $this->$methodName();
        }
        
        return $this->$key;
    }
    
    public function set($key, $value, $direct = false) {
        if($direct) {
            $this->$key = $value;
            return;
        }
        if($this->unattached) {
            if(!in_array($this, self::$unattachedDirty)) {
                self::$unattachedDirty[] = $this;
            }
        }
        // First, validate the value
        if(!$this->validate($key, $value)) {
            throw new ValidationFailedException($this, $key, $value);
        }
        
        $methodName = "set" . ucfirst($key);
        if(method_exists($this, $methodName)) {
            $this->$methodName($value);
        } else {
            $this->$key = $value;
        }
    }
    
    public function has($key) {
        return property_exists($this, $key);
    }
    
    public function validate($key, $newValue) {
        $methodName = "validate" . ucfirst($key);
        if(method_exists($this, $methodName)) {
            return $this->$methodName($newValue);
        } else {
            return true;
        }
    }
    
    public static function find($container, $id) {
        $args = func_get_args();
        
        return self::__callStatic("find", $args);
    }
    
    public function __call($method, $arguments) {
        $func = substr($method, 0, 3);
        $fieldName = substr($method, 3);
        $fieldName = lcfirst($fieldName);

        if($func == 'get') {
            return $this->get($fieldName);
        } else if($func == 'set') {
            $this->set($fieldName, $arguments[0]);
        } else if($func == 'has') {
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
    
    public static function __callStatic($method, $arguments) {
        // First argument must be container
        $container = array_shift($arguments);
        assert($container instanceof \Fossil\ObjectContainer);
        $orm = $container->get("ORM");
        $repo = $orm->getEM()->getRepository(get_called_class());
        if(property_exists($repo, "container")) {
            $repo->container = $container;
        }
        return call_user_func_array(array($repo, $method), $arguments);
    }
    
    public static function createFromArray($diContainer, $data) {
        $model = static::create($diContainer);
        $orm = $diContainer->get("ORM");
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
                            // TODO: Switch to specific exception
                            throw new \Exception("Required entity not found: $targetClass (" . var_export($targetData, true) . ")");
                        }
                        $collection->add($targetEntity);
                    }

                    $model->set($key, $collection);
                } else {
                    $targetEntity = $targetClass::findOneBy($diContainer, $value);
                    if(!$targetEntity) {
                        // TODO: Switch to specific exception
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
        if(!$this->getMetadata()->isCollectionValuedAssociation($field)) {
            throw new \Exception("Attempted to paginate a single entity");
        }
        return new PaginationProxy($this->get($field), $fieldsPerPage);
    }
    
    public function toArray($depth = 2, $fetchedOnly = true, &$visited = array()) {
        $toRet = array();
        $uow = $this->orm->getEM()->getUnitOfWork();
        $entityID = $uow->getEntityIdentifier($this);
        if(in_array($entityID, $visited)) {
            return "*VISITED*";
        } else {
            $visited[] = $entityID;
        }
        foreach($this->getMetadata()->getFieldNames() as $field) {
            $toRet[$field] = $this->get($field);
        }
        if($depth != 0) {
            foreach($this->getMetadata()->getAssociationNames() as $assoc) {
                if($this->getMetadata()->isSingleValuedAssociation($assoc)) {
                    $value = $this->get($assoc);
                    if($value instanceof Proxy && $fetchedOnly) {
                        $toRet[$assoc] = "*NOT FETCHED*";
                    } else {
                        if($value) {
                            $toRet[$assoc] = $value->toArray($depth-1, $fetchedOnly, $visited);
                        } else {
                            $toRet[$assoc] = null;
                        }
                    }
                } elseif($this->getMetadata()->isCollectionValuedAssociation($assoc)) {
                    $assocArray = array();
                    foreach($this->get($assoc) as $subModel) {
                        if($value instanceof Proxy && $fetchedOnly) {
                            $toRet[$assoc] = "*NOT FETCHED*";
                        } else {
                            if($subModel) {
                                $assocArray[] = $subModel->toArray($depth-1, $fetchedOnly, $visited);
                            } else {
                                $assocArray[] = null;
                            }
                        }
                    }
                    $toRet[$assoc] = $assocArray;
                } else {
                    throw new \Exception("Unsupported association");
                }
            }
        }
        return $toRet;
    }
}
