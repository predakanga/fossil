<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Fossil\DoctrineExtensions;

use Fossil\Models\Model;

class ActiveClassMetadata extends \Doctrine\ORM\Mapping\ClassMetadata {
    // As with parent class, this will be serialized, so leave it's scope public
    /**
     * @var Fossil\ObjectContainer
     */
    public $diContainer = null;
    public $origClassName = null;
    protected $_prototype;
    
    public function __construct($entityName, $diContainer, $origName) {
        $this->origClassName = $origName;
        $this->diContainer = $diContainer;
        parent::__construct($entityName);
        $this->reflClass = new ActiveEntityReflectionClass($entityName);
        
        $this->namespace = $this->reflClass->getNamespaceName();
        $this->table['name'] = $this->reflClass->getShortName();
    }

    public function setDIContainer($container) {
        $this->diContainer = $container;
    }
    
    public function __sleep() {
        return array_merge(parent::__sleep(), array("origClassName"));
    }
    
    /**
     * Restores some state that can not be serialized/unserialized.
     *
     * @return void
     */
    public function __wakeup() {
        // Restore ReflectionClass and properties
        $this->reflClass = new ActiveEntityReflectionClass($this->name);

        foreach($this->fieldMappings as $field => $mapping) {
            if(isset($mapping['declared'])) {
                $reflField = new ActiveEntityReflectionProperty($mapping['declared'], $field);
            } else {
                $reflField = $this->reflClass->getProperty($field);
            }
            $reflField->setAccessible(true);
            $this->reflFields[$field] = $reflField;
        }
        
        foreach($this->associationMappings as $field => $mapping) {
            if(isset($mapping['declared'])) {
                $reflField = new ActiveEntityReflectionProperty($mapping['declared'], $field);
            } else {
                $reflField = $this->reflClass->getProperty($field);
            }
            $reflField->setAccessible(true);
            $this->reflFields[$field] = $reflField;
        }
    }
    
    public function newInstance() {
        if ($this->_prototype === null) {
            // Look up the correct name to use
            $realClassName = $this->name;
            $realClassName = $this->diContainer->mapClass($this->name);
            $this->_prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($realClassName), $realClassName));
        }
        $newInst = clone $this->_prototype;
        
        // If we have diProp, put the container in place
        if($newInst instanceof \Fossil\Models\Model) {
            $newInst->restoreObjects($this->diContainer);
        }
        return $newInst;
    }
}
