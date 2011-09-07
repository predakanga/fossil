<?php

namespace Fossil\Annotations;

use \Fossil\Autoloader,
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
    private $annotationCache;
    
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
        
        $this->reader = new AnnotationReader();
        $this->reader->setIgnoreNotImportedAnnotations(true);
        $this->registerNamespaceAlias("\\Fossil\\Annotations\\", "F");
        
        if($annotations) {
            $this->annotationCache = $annotations;
        } else {
            $this->updateAnnotations();
        }
    }
    
    public function updateAnnotations() {
        $this->annotationCache = $this->gatherAnnotations();
    }
    
    public function gatherAnnotations() {
        $allAnnotations = array();
        foreach(get_declared_classes() as $class) {
            $classData = array();
            $classData['methods'] = array();
            $classData['properties'] = array();
            
            $reflClass = new \ReflectionClass($class);
            if($reflClass->getParentClass())
                $classData['parent'] = $reflClass->getParentClass()->name;
            $classData['annos'] = $this->reader->getClassAnnotations($reflClass);
            
            foreach($reflClass->getMethods() as $method) {
                $methodAnnos = $this->reader->getMethodAnnotations($method);
                
                if(count($methodAnnos))
                    $classData['methods'][$method->name] = $methodAnnos;
            }
            foreach($reflClass->getProperties() as $prop) {
                $propAnnos = $this->reader->getPropertyAnnotations($prop);
                
                if(count($propAnnos))
                    $classData['properties'][$prop->name] = $propAnnos;
            }
            // Special logic... we only want to store classes that actually contain or inherit annotations
            if(!count($classData['annos']) && !count($classData['methods']) && !count($classData['properties'])) {
                // Check that the parent isn't stored
                if(!(isset($classData['parent']) && isset($allAnnotations[$classData['parent']])))
                    continue;
            }
            $allAnnotations[$class] = $classData;
        }
        return $allAnnotations;
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
}

?>