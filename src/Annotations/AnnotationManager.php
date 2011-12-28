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
 * @subpackage Annotations
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil\Annotations;

use Fossil\Autoloader,
    Fossil\Object,
    \Doctrine\Common\Annotations\AnnotationReader,
    \Doctrine\Common\Annotations\CachedReader,
    \Doctrine\Common\Annotations\AnnotationRegistry,
    \ReflectionClass;

/**
 * @F:Provides("AnnotationManager")
 * @F:DefaultProvider
 */
class AnnotationManager extends Object {
    /**
     * @var AnnotationReader
     */
    private $reader;
    private $realReader;
    /**
     * @var string[]
     */
    private $namespaces;
    private $annotations = array();

    /**
     * @F:Inject("Cache")
     * @var Fossil\Caches\BaseCache
     */
    protected $cache;
    /**
     * @F:Inject("Reflection")
     * @var Fossil\Reflection
     */
    protected $reflection;
    
    private function registerNamespaceAlias($namespace, $alias) {
        $this->namespaces[$alias] = $namespace;
        $this->realReader->setAnnotationNamespaceAlias($namespace, $alias);
    }
    
    private function resolveName($annoName) {
        // TODO: Throw an exception if the name couldn't be resolved to an annotation
        if(strpos($annoName, ":") !== false) {
            $parts = explode(":", $annoName, 2);
            return $this->namespaces[$parts[0]] . $parts[1];
        }
        return $annoName;
    }
    
    public function __construct($container) {
        parent::__construct($container);
        
        // Register our own class loader with the annotation registry
        AnnotationRegistry::registerLoader(function($class) {
            Autoloader::autoload($class);
            return class_exists($class, false);
        });
        AnnotationReader::addGlobalIgnoredName('since');
        $this->ensureAnnotations();
    }
    
    protected function determineObjects() {
        // As a special case, we don't do auto-discovery on this object
        return array(array('type' => 'Cache', 'destination' => 'cache',
                           'required' => true, 'lazy' => true),
                     array('type' => 'Reflection', 'destination' => 'reflection',
                           'required' => true, 'lazy' => true));
    }
    
    protected function ensureAnnotations() {
        if($this->annotations) {
            return;
        }
        
        // Ask for a copy of the annotations from the cache
        if($this->cache->_isReady()) {
            $this->annotations = $this->cache->get("annotations", true);
        }
        // If there's no cached copy, read the annotations in anew
        if(!$this->annotations) {
            $this->readAnnotations();
        } else {
            $this->namespaces = array('F' => "\\Fossil\\Annotations\\");
        }
    }
    
    public function rescanAnnotations() {
        $this->reflection->rescan();
        $this->readAnnotations();
    }
    
    protected function readAnnotations() {
        $this->annotations = array();
        
        // Collect a list of classes to inspect
        $classes = $this->reflection->getAllClasses();
        
        // Set up a temporary error handler, to bypass Doctrine's type hinting
        $origErrorHandler = set_error_handler(array($this, "tempErrorHandler"));
        
        // Read annotations from all of the classes
        foreach($classes as $reflClass) {
            $this->readClassAnnotations($reflClass);
        }
        
        // And restore the original error handler
        restore_error_handler();

        // After all of the annotations have been built, we want to cull all those who don't have any annotations
        $culledAnnos = array();
        foreach(array_keys($this->annotations) as $class) {
            if($this->hasAnnotations($class))
                $culledAnnos[$class] = $this->annotations[$class];
        }
        
        // And store the annotations, both in the cache and locally
        // TODO: Store in cache
        $this->annotations = $culledAnnos;
    }
    
    protected function readClassAnnotations($reflClass) {
            $classData = array();
            $classData['methods'] = array();
            $classData['properties'] = array();
            
            if($reflClass->getParentClass())
                $classData['parent'] = $reflClass->getParentClass()->getName();
            $classData['annos'] = $this->getReader()->getClassAnnotations($reflClass);
            
            foreach($reflClass->getMethods() as $method) {
                $methodAnnos = $this->getReader()->getMethodAnnotations($method);
                
                if(count($methodAnnos))
                    $classData['methods'][$method->name] = $methodAnnos;
            }
            foreach($reflClass->getProperties() as $prop) {
                $propAnnos = $this->getReader()->getPropertyAnnotations($prop);
                
                if(count($propAnnos))
                    $classData['properties'][$prop->name] = $propAnnos;
            }
            $this->annotations[$reflClass->getName()] = $classData;
    }
    
    private function getReader() {
        if(!$this->reader) {
            $this->realReader = new AnnotationReader();
        
            // Tweak the reader to use our own implementation of PhpParser
            $reflClass = new \ReflectionClass($this->realReader);
            $reflProp = $reflClass->getProperty("phpParser");
            $reflProp->setAccessible(true);
            $reflProp->setValue($this->realReader, new \Fossil\DoctrineExtensions\TokenizedPhpParser());
        
            $this->realReader->setIgnoreNotImportedAnnotations(true);
            
            $this->reader = new CachedReader($this->realReader, new \Doctrine\Common\Cache\ArrayCache());
            $this->registerNamespaceAlias("\\Fossil\\Annotations\\", "F");
        }
        return $this->reader;
    }
    
    public function tempErrorHandler($errno, $errstr, $errfile, $errline, $errctxt) {
        if($errno == E_RECOVERABLE_ERROR && strpos($errstr, " must be an instance of ")) {
                return true;
        }
        // Otherwise, pass on to our old error handler, or the PHP error handler as appropriate
        if(is_callable($this->origErrorHandler))
            return call_user_func_array($this->origErrorHandler, func_get_args());
        return false;
    }
    
    private function hasAnnotations($class) {
        if(!isset($this->annotations[$class]))
            return false;
        $classData = $this->annotations[$class];
        
        if(count($classData['annos']) || count($classData['methods']) || count($classData['properties']))
            return true;
        if(isset($classData['parent']))
            return $this->hasAnnotations($classData['parent']);
        return false;
    }
    
    /**
     * @param string $class
     * @param string $annotation
     * @return \AddendumPP\Annotation[]
     */
    public function getClassAnnotations($class, $annotation = false, $recursive = true) {
        $startAnnos = array();
        
        if($class[0] == '\\')
            $class = substr($class, 1);
        
        if(!isset($this->annotations[$class]))
            return array();
        
        $classData = $this->annotations[$class];
        if(isset($classData['parent']) && $recursive) {
            $startAnnos = $this->getClassAnnotations($classData['parent'], $annotation, $recursive);
        }
        
        if(!$annotation)
            return array_merge($startAnnos, $classData['annos']);
        
        $annotation = $this->resolveName($annotation);
        return array_merge($startAnnos,
               array_filter($classData['annos'], function($thisAnno) use($annotation) {
            return is_a($thisAnno, $annotation);
        }));
    }
    
    public function getClassAnnotation($class, $annotation, $recursive = true) {
        assert($annotation != null);
        $annos = $this->getClassAnnotations($class, $annotation, $recursive);
        
        if(!count($annos)) {
            return null;
        } else {
            return reset($annos);
        }
    }
    
    public function getPropertyAnnotations(\ReflectionProperty $reflProp, $annotation = false) {
        $class = $reflProp->getDeclaringClass()->name;
        $prop = $reflProp->name;
        
        if(!isset($this->annotations[$class]))
            return array();
        if(!isset($this->annotations[$class]['properties'][$prop]))
            return array();
        
        if(!$annotation)
            return $this->annotations[$class]['properties'][$prop];
        
        $annotation = $this->resolveName($annotation);
        return array_filter($this->annotations[$class]['properties'][$prop], function($thisAnno) use($annotation) {
            return is_a($thisAnno, $annotation);
        });
    }
    
    public function getPropertyAnnotation(\ReflectionProperty $reflProp, $annotation) {
        assert($annotation != null);
        $annos = $this->getPropertyAnnotations($reflProp, $annotation);
        
        if(!count($annos)) {
            return null;
        } else {
            return reset($annos);
        }
    }
    
    public function getMethodAnnotations($reflMethod, $annotation = false) {
        $class = $reflMethod->getDeclaringClass()->name;
        $method = $reflMethod->name;
        
        if(!isset($this->annotations[$class]))
            return array();
        if(!isset($this->annotations[$class]['methods'][$method]))
            return array();
        
        if(!$annotation)
            return $this->annotations[$class]['methods'][$method];
        
        $annotation = $this->resolveName($annotation);
        return array_filter($this->annotations[$class]['methods'][$method], function ($thisAnno) use ($annotation) {
            // Check inheritance
            return is_a($thisAnno, $annotation);
        });
    }
    
    public function classHasAnnotation($class, $annotation, $recursive = true) {
        return (count($this->getClassAnnotations($class, $annotation, $recursive)) != 0);
    }
    
    public function classHasMethodAnnotation($class, $annotation = false) {
        if($annotation) {
            $annotation = $this->resolveName($annotation);
        }
        
        if(!isset($this->annotations[$class])) {
            return array();
        }
        foreach($this->annotations[$class]['methods'] as $method => $methodAnnos) {
            foreach($methodAnnos as $anno) {
                if(!$annotation || is_a($anno, $annotation)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * @param string[] $classes
     * @param string $annotation
     * @return string[]
     */
    public function filterClassesByAnnotation($classes, $annotation, $negativeFilter = false, $recursive = true) {
        $annotation = $this->resolveName($annotation);
        $self = $this;
        return array_filter($classes, function($class) use($annotation, $negativeFilter, $recursive, $self) {
            if($negativeFilter)
                return !$self->classHasAnnotation($class, $annotation, $recursive);
            else
                return $self->classHasAnnotation($class, $annotation, $recursive);
        });
    }
    
    public function getClassesWithAnnotation($annotation, $recursive = true) {
        $classes = array_keys($this->annotations);
        
        return $this->filterClassesByAnnotation($classes, $annotation, false, $recursive);
    }
    
    public function getClassesWithPropertyAnnotation($annotation) {
        $annotation = $this->resolveName($annotation);
        $toRet = array();
        foreach($this->annotations as $class => $key) {
            foreach($key['properties'] as $method => $annos) {
                foreach($annos as $anno) {
                    if(is_a($anno, $annotation))
                        $toRet[] = $class;
                }
            }
        }
        return array_unique($toRet);
    }
    
    public function getClassesWithMethodAnnotation($annotation) {
        $annotation = $this->resolveName($annotation);
        
        $toRet = array();
        foreach($this->annotations as $class => $key) {
            foreach($key['methods'] as $method => $annos) {
                foreach($annos as $anno) {
                    if(is_a($anno, $annotation))
                        $toRet[] = $class;
                }
            }
        }
        return array_unique($toRet);
    }
}

?>