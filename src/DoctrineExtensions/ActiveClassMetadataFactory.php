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
    
    public function getMetadataFor($className) {
        // Map className on non-entities to the closest entity parent
        if(!$this->diContainer) {
            throw new \Exception("ActiveClassMetadata::setDIContainer must be called before getMetadataFor");
        }
        if(isset($this->metadataNameMap[$className])) {
            $realClassName = $this->metadataNameMap[$className];
        } else {
            $annoMgr = $this->diContainer->get("AnnotationManager");
            $realClassName = $className;
            while(!$annoMgr->getClassAnnotation($realClassName, 'Doctrine\ORM\Mapping\Entity', false)) {
                // Walk up to the nearest entity
                $parent = get_parent_class($realClassName);
                if(!$parent) {
                    throw new \Exception("Metadata could not be loaded for $className; it is not an Entity or a direct descendant of an Entity");
                }
                $realClassName = $parent;
            }
            // But warn unless the mapping is occurring on a compiled class
            if($realClassName != $className && !strstr($className, "\\Compiled\\")) {
                trigger_error("Model $realClassName was subclassed without the resultant class, $className, being marked as an entity", E_USER_WARNING);
            }
            $this->metadataNameMap[$className] = $realClassName;
        }
        if($realClassName != $className) {
            if(!isset($this->modifiedMetadataCache[$className])) {
                $retval = parent::getMetadataFor($realClassName);
                $proxiedMD = new \Fossil\DoctrineExtensions\ProxiedClassMetadata($retval, $className);
                
                $this->modifiedMetadataCache[$className] = $proxiedMD;
                $retval = $proxiedMD;
            } else {
                $retval = $this->modifiedMetadataCache[$className];
            }
        } else {
            $retval = parent::getMetadataFor($realClassName);
        }
        
        if($retval instanceof ActiveClassMetadata) {
            $retval->setDIContainer($this->diContainer);
        }
        return $retval;
    }
    
    public function setDIContainer($diContainer) {
        $this->diContainer = $diContainer;
    }
    
    protected function newClassMetadataInstance($className) {
        return new ActiveClassMetadata($className, $this->diContainer);
    }
}