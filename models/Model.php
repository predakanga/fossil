<?php

namespace Fossil\Models;

use Fossil\OM,
    Fossil\Exceptions\ValidationFailedException;

/**
 * Description of Model
 *
 * @author predakanga
 */
abstract class Model {
    private function getMetadata() {
        return OM::ORM()->getEM()->getClassMetadata(get_class($this));
    }
    
    public function __construct() {
        // Automatically create ArrayCollections for relations
        foreach($this->getMetadata()->getAssociationNames() as $field)
            $this->$field = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    public function save() {
        OM::ORM()->getEM()->persist($this);
    }
    
    public function delete() {
        OM::ORM()->getEM()->remove($this);
    }
    
    public function __toString() {
        return "<Entity (" . get_class($this) . ") " . var_export($this->id(), true) . ">";
    }
    
    public function id() {
        return OM::ORM()->getEM()->getUnitOfWork()->getEntityIdentifier($this);
    }
    
    public function get($key) {
        $methodName = "get" . ucfirst($key);
        if(method_exists($this, $methodName))
            return $this->$methodName();
        return $this->$key;
    }
    
    public function set($key, $value) {
        // First, validate the code
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
        return $this->$key;
    }
    
    public function __isset($key) {
        return isset($this->$key);
    }
    
    public function __unset($key) {
        unset($this->$key);
    }
    
    public static function __callStatic($method, $arguments)
    {
        return call_user_func_array(
            array(OM::ORM()->getEM()->getRepository(get_called_class()), $method),
            $arguments
        );
    }
    
    public static function createFromArray($data) {
        $model = new static();
        $classMetadata = OM::ORM()->getEM()->getClassMetadata(get_called_class());
        
        foreach($data as $key => $value) {
            if($classMetadata->hasAssociation($key)) {
                $targetClass = $classMetadata->getAssociationTargetClass($key);
                $collection = new \Doctrine\Common\Collections\ArrayCollection();
                
                foreach($value as $targetData) {
                    $targetEntity = $targetClass::findOneBy($targetData);
                    if(!$targetEntity) {
                        throw new Exception("Required entity not found: $targetClass (" . var_export($this->targetData) . ")");
                    }
                    $collection->add($targetEntity);
                }
                
                $model->set($key, $collection);
            } else {
                $model->set($key, $value);
            }
        }
        return $model;
    }
}

?>
