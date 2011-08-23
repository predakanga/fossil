<?php

namespace Fossil;

use \AddendumPP\AddendumPP,
    \AddendumPP\AnnotationResolver;

require_once("libs/AddendumPP/annotations.php");

class FossilAddendumPP extends AddendumPP {
    private $namespaces = array("" => array("Target" => "AddendumPP\Annotation_Target"));
    
    public function resolveClassName($className) {
        $namespace = "";
        $key = $className;
        if(strpos($className, ":") !== FALSE)
        {
            $parts = explode(":", $className, 2);
            $namespace = $parts[0];
            $key = $parts[1];
        }
        
        // Check whether we have a cached result
        if(isset($this->namespaces[$namespace])) {
            if(isset($this->namespaces[$namespace][$key]))
                return $this->namespaces[$namespace][$key];
        }
        
        // If not, and there's no namespace, scan the declared annotation list
        if($namespace == "")
        {
            foreach($this->getDeclaredAnnotations() as $annotation) {
                if($annotation == $className) {
                    if(!isset($this->namespaces[""]))
                        $this->namespaces[""] = array();
                    $this->namespaces[""][$className] = $annotation;
                    return $annotation;
                }
            }
        }
        
        // If we didn't find one, check for an aliased one
        // If there is a namespace, go through the list of annotations
        // and stack them into the cached array
        foreach($this->getDeclaredAnnotations() as $annotation) {
            // To avoid a circular reference exception, skip over any annotations already on the creation stack
            if(isset($this->creationStack[$annotation]) && $this->creationStack[$annotation])
                continue;
            
            $reflClass = $this->reflect($annotation);
            if($reflClass->hasAnnotation("Fossil\\Annotations\\AliasAnnotation")) {
                $targetNamespace = "";
                $targetAlias = $reflClass->getAnnotation("Fossil\\Annotations\\AliasAnnotation")->value;
                if($reflClass->hasAnnotation("Fossil\\Annotations\\NamespaceAnnotation")) {
                    $targetNamespace = $reflClass->getAnnotation("Fossil\\Annotations\\NamespaceAnnotation")->value;
                }
                if(!isset($this->namespaces[$targetNamespace]))
                    $this->namespaces[$targetNamespace] = array();
                $this->namespaces[$targetNamespace][$targetAlias] = $annotation;
                if($namespace == $targetNamespace && $targetAlias == $key)
                    return $annotation;
            }
        }
        throw new AddendumPP\UnresolvedAnnotationException($className);
    }
}

class Annotations {
    /**
     * @var FossilAddendumPP
     */
    private $addendum;
    
    public function __construct() {
        require_once(__DIR__."/annotations/AliasAnnotation.php");
        require_once(__DIR__."/annotations/NamespaceAnnotation.php");
        require_once(__DIR__."/annotations/ObjectAnnotation.php");
        $this->addendum = new FossilAddendumPP();
    }
    
    /**
     * @param string $class
     * @param string $annotation
     * @return \AddendumPP\Annotation[]
     */
    public function getAnnotations($class, $annotation = false) {
        return $this->addendum->reflect($class)->getAllAnnotations($annotation);
    }
    
    /**
     * @param string[] $classes
     * @param string $annotation
     * @return string[]
     */
    public function filterClassesByAnnotation($classes, $annotation) {
        $addendum = $this->addendum;
        return array_filter($classes, function($class) use($addendum, $annotation) {
            return $addendum->reflect($class)->hasAnnotation($annotation);
        });
    }
}

?>