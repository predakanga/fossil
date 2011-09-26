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
    \Doctrine\Common\Annotations\AnnotationReader,
    \Doctrine\Common\Annotations\AnnotationRegistry,
    \ReflectionClass;

class AnnotationManager {
    /**
     * @var AnnotationReader
     */
    private $reader;
    /**
     * @var string[]
     */
    private $namespaces;
    private $annotationCache = array();
    private $origErrorHandler;
    
    private function registerNamespaceAlias($namespace, $alias) {
        $this->namespaces[$alias] = $namespace;
        $this->reader->setAnnotationNamespaceAlias($namespace, $alias);
    }
    
    private function resolveName($annoName) {
        // TODO: Throw an exception if the name couldn't be resolved to an annotation
        if(strpos($annoName, ":") !== false) {
            $parts = explode(":", $annoName, 2);
            return $this->namespaces[$parts[0]] . $parts[1];
        }
        return $annoName;
    }
    
    public function __construct($annotations = null) {
        // Register our own class loader with the annotation registry
        AnnotationRegistry::registerLoader(function($class) {
            Autoloader::autoload($class);
            return class_exists($class, false);
        });
        AnnotationReader::addGlobalIgnoredName('since');
    }
    
    private function getReader() {
        if(!$this->reader) {
            $this->reader = new AnnotationReader();
        
            // Tweak the reader to use our own implementation of PhpParser
            $reflClass = new \ReflectionClass($this->reader);
            $reflProp = $reflClass->getProperty("phpParser");
            $reflProp->setAccessible(true);
            $reflProp->setValue($this->reader, new \Fossil\DoctrineExtensions\TokenizedPhpParser());
        
            $this->reader->setIgnoreNotImportedAnnotations(true);
            $this->registerNamespaceAlias("\\Fossil\\Annotations\\", "F");
        }
        return $this->reader;
    }
    
    public function loadFromCache($annotations) {
        $this->namespaces = array('F' => "\\Fossil\\Annotations\\");
        $this->annotationCache = $annotations;
    }
    
    public function dumpForCache() {
        return $this->annotationCache;
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
    
    public function updateAnnotations($classes) {
        $this->annotationCache = array();
        
        // Because the Doctrine annotation reader uses strong type hinting and
        // we want to use duck typing, we have to use a temporary error handler
        $this->origErrorHandler = set_error_handler(array($this, "tempErrorHandler"));
        foreach($classes as $reflClass) {
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
            $this->annotationCache[$reflClass->getName()] = $classData;
        }
        // Restore the original error handler
        restore_error_handler();
        $this->origErrorHandler = null;
        // After all of the annotations have been built, we want to cull all those who don't have any annotations
        $culledAnnos = array();
        foreach(array_keys($this->annotationCache) as $class) {
            if($this->hasAnnotations($class))
                $culledAnnos[$class] = $this->annotationCache[$class];
        }
        $this->annotationCache = $culledAnnos;
    }
    
    private function hasAnnotations($class) {
        if(!isset($this->annotationCache[$class]))
            return false;
        $classData = $this->annotationCache[$class];
        
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
    public function getClassAnnotations($class, $annotation = false) {
        $startAnnos = array();
        
        if($class[0] == '\\')
            $class = substr($class, 1);
        
        if(!isset($this->annotationCache[$class]))
            return array();
        
        $classData = $this->annotationCache[$class];
        if(isset($classData['parent'])) {
            $startAnnos = $this->getClassAnnotations($classData['parent'], $annotation);
        }
        
        if(!$annotation)
            return array_merge($startAnnos, $classData['annos']);
        
        $annotation = $this->resolveName($annotation);
        return array_merge($startAnnos,
               array_filter($classData['annos'], function($thisAnno) use($annotation) {
            return is_a($thisAnno, $annotation);
        }));
    }
    
    public function getPropertyAnnotations(\ReflectionProperty $reflProp, $annotation = false) {
        $class = $reflProp->getDeclaringClass()->name;
        $prop = $reflProp->name;
        
        if(!isset($this->annotationCache[$class]))
            return array();
        if(!isset($this->annotationCache[$class]['properties'][$prop]))
            return array();
        
        if(!$annotation)
            return $this->annotationCache[$class]['properties'][$prop];
        
        $annotation = $this->resolveName($annotation);
        return array_filter($this->annotationCache[$class]['properties'][$prop], function($thisAnno) use($annotation) {
            return is_a($thisAnno, $annotation);
        });
    }
    
    public function getMethodAnnotations(\ReflectionMethod $reflMethod, $annotation = false) {
        $class = $reflMethod->getDeclaringClass()->name;
        $method = $reflMethod->name;
        
        if(!isset($this->annotationCache[$class]))
            return array();
        if(!isset($this->annotationCache[$class]['methods'][$method]))
            return array();
        
        if(!$annotation)
            return $this->annotationCache[$class]['methods'][$method];
        
        $annotation = $this->resolveName($annotation);
        return array_filter($this->annotationCache[$class]['methods'][$method], function ($thisAnno) use ($annotation) {
            // Check inheritance
            return is_a($thisAnno, $annotation);
        });
    }
    
    public function classHasAnnotation($class, $annotation, $recursive = true) {
        $annotation = $this->resolveName($annotation);
        
        foreach($this->getClassAnnotations($class) as $anno) {
            if(is_a($anno, $annotation))
                return true;
        }
        
        return false;
    }
    
    /**
     * @param string[] $classes
     * @param string $annotation
     * @return string[]
     */
    public function filterClassesByAnnotation($classes, $annotation, $negativeFilter = false) {
        $annotation = $this->resolveName($annotation);
        $self = $this;
        return array_filter($classes, function($class) use($annotation, $negativeFilter, $self) {
            if($negativeFilter)
                return !$self->classHasAnnotation($class, $annotation);
            else
                return $self->classHasAnnotation($class, $annotation);
        });
    }
    
    public function getClassesWithAnnotation($annotation) {
        $classes = array_keys($this->annotationCache);
        
        return $this->filterClassesByAnnotation($classes, $annotation);
    }
    
    public function getClassesWithPropertyAnnotation($annotation) {
        $annotation = $this->resolveName($annotation);
        $toRet = array();
        foreach($this->annotationCache as $class => $key) {
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
        foreach($this->annotationCache as $class => $key) {
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