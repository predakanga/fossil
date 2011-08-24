<?php

namespace Fossil;

use \Doctrine\Common\Annotations\AnnotationReader,
    \Doctrine\Common\Annotations\AnnotationRegistry,
    \ReflectionClass;

class Annotations {
    /**
     * @var AnnotationReader
     */
    private $reader;
    /**
     * @var string[]
     */
    private $namespaces;
    
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
    
    public function __construct() {
        // Register our own class loader with the annotation registry
        AnnotationRegistry::registerLoader(function($class) {
            Autoloader::autoload($class);
            return class_exists($class, false);
        });
        AnnotationReader::addGlobalIgnoredName('since');
        
        $this->reader = new AnnotationReader();
        $this->reader->setIgnoreNotImportedAnnotations(true);
        $this->registerNamespaceAlias("\\Fossil\\Annotations\\", "F");
    }
    
    /**
     * @param string $class
     * @param string $annotation
     * @return \AddendumPP\Annotation[]
     */
    public function getAnnotations($class, $annotation = false) {
        return $this->reader->getClassAnnotations(new ReflectionClass($class));
    }
    
    /**
     * @param string[] $classes
     * @param string $annotation
     * @return string[]
     */
    public function filterClassesByAnnotation($classes, $annotation) {
        $annotation = $this->resolveName($annotation);
        $reader = $this->reader;
        return array_filter($classes, function($class) use($reader, $annotation) {
            return ($reader->getClassAnnotation(new ReflectionClass($class), $annotation) != null);
        });
    }
}

?>