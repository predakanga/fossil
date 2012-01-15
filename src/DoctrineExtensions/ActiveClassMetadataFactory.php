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

class ActiveClassMetadataFactory extends \Doctrine\ORM\Mapping\ClassMetadataFactory
{
    private $metadataNameMap = array();
    private $modifiedMetadataCache = array();
    
    /**
     * @var Fossil\ObjectContainer
     */
    private $diContainer = null;
    private $compiler = null;
    private $annoMgr = null;
    
    private function isDeclaredEntity($className, &$outClassName) {
        $outClassName = $className;
        if(strpos($className, 'Fossil\Compiled') === 0) {
            if(!$this->compiler) {
                $this->compiler = $this->diContainer->get("Compiler");
            }
            
            // Map class to original class, in case it's a reparenting and not a compilation
            $origClassName = $this->compiler->mapCompiledClassNameToOriginal($className);
            if(class_exists($origClassName)) {
                $outClassName = $origClassName;
            }
        }
        if(!$this->annoMgr) {
            $this->annoMgr = $this->diContainer->get("AnnotationManager");
        }
        return $this->annoMgr->getClassAnnotation($outClassName, 'Doctrine\ORM\Mapping\Entity', false);
    }
    
    public function getRealEntityClass($className) {
        if(!isset($this->metadataNameMap[$className])) {
            // Look up the class's real metadata name, if it's not an entity itself
            $realClassName = $className;
            $outClassName = "";
            while(!$this->isDeclaredEntity($realClassName, $outClassName)) {
                // Walk up to the nearest entity
                $parent = get_parent_class($realClassName);
                if(!$parent) {
                    throw new \Exception("Metadata could not be loaded for $className; " .
                                         "it is not an Entity or a direct descendant of an Entity");
                }
                $realClassName = $parent;
            }
            $realClassName = $outClassName;
            // But warn unless the mapping is occurring on a compiled class
            if($realClassName != $className && !strstr($className, "\\Compiled\\")
                                            && !strstr($className, "\\Proxies\\")) {
                trigger_error("Model $realClassName was subclassed without the resultant class, " .
                              "$className, being marked as an entity", E_USER_WARNING);
            }
            $this->metadataNameMap[$className] = $realClassName;
        }
        return $this->metadataNameMap[$className];
    }
    
    public function getMetadataFor($className) {
        if(isset($this->modifiedMetadataCache[$className])) {
            return $this->modifiedMetadataCache[$className];
        }
        // Map className on non-entities to the closest entity parent
        if(!$this->diContainer) {
            throw new \Exception("ActiveClassMetadata::setDIContainer must be called before getMetadataFor");
        }
        
        // Look up the real class name
        $realClassName = $this->getRealEntityClass($className);
        
        // Then get the metadata for it
        $retval = parent::getMetadataFor($realClassName);
        // Add the DI container if need be
        if($retval instanceof ActiveClassMetadata) {
            $retval->setDIContainer($this->diContainer);
        }
        // And store it in the metadata cache
        $this->modifiedMetadataCache[$className] = $retval;
        return $retval;
    }
    
    public function setDIContainer($diContainer) {
        $this->diContainer = $diContainer;
    }
    
    protected function newClassMetadataInstance($className) {
        // Look up the real class name
        $realClassName = $this->getRealEntityClass($className);
        // And pass it to a new ACMD instance
        return new ActiveClassMetadata($className, $this->diContainer, $realClassName);
    }
}