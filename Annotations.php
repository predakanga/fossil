<?php

namespace Fossil;

use \AddendumPP\AddendumPP,
    \AddendumPP\AnnotationResolver;

require_once("libs/AddendumPP/annotations.php");

class FossilResolver extends AnnotationResolver {
    private $namespaces = array();
    
    public function match($className) {
        $namespace = "";
        $key = $className;
        if(strpos($className, ":") !== FALSE)
        {
            $parts = explode(":", $className, 2);
            $namespace = $parts[0];
            $key = $parts[1];
        }
        
        // Check whether we have a cached result
        if(isset($namespaces[$namespace])) {
            if(isset($namespaces[$namespace][$key]))
                return $namespaces[$namespace][$key];
        }
        
        // If not, and there's no namespace, scan the declared annotation list
        if($namespace == "")
        {
            foreach($this->addendum->getDeclaredAnnotations() as $annotation) {
                if($annotation == $className) {
                    if(!isset($namespaces[""]))
                        $namespaces[""] = array();
                    $namespaces[""][$className] = $annotation;
                    return $annotation;
                }
            }
        }
        
        // If we didn't find one, check for an aliased one
        // If there is a namespace, go through the list of annotations
        // and stack them into the cached array
        foreach($this->addendum->getDeclaredAnnotations() as $annotation) {
            $reflClass = $this->addendum->reflect($annotation);
            if($reflClass->hasAnnotation("Fossil\\Annotations\\AliasAnnotation")) {
                $targetNamespace = "";
                $targetAlias = $reflClass->getAnnotation("Fossil\\Annotations\\AliasAnnotation")->value;
                if($reflClass->hasAnnotation("Fossil\\Annotations\\NamespaceAnnotation")) {
                    $targetNamespace = $reflClass->getAnnotation("Fossil\\Annotations\\NamespaceAnnotation")->value;
                }
                if(!isset($namespaces[$targetNamespace]))
                    $namespaces[$targetNamespace] = array();
                $namespaces[$targetNamespace][$targetAlias] = $annotation;
                if($namespace == $targetNamespace && $targetAlias == $key)
                    return $annotation;
            }
        }
        throw new AddendumPP\UnresolvedAnnotationException($className);
    }
}

class FossilAddendumPP extends AddendumPP {
    public function __construct()
    {
        parent::__construct();
        $this->resolver = new FossilResolver($this);
    }
}

class Annotations {
    /**
     *
     * @var AddendumPP
     */
    private $addendum;
    
    public function __construct() {
        $this->addendum = new FossilAddendumPP();
    }
    
    public function getAnnotations($class) {
        return $this->addendum->reflect($class)->getAnnotations();
    }
    
    public function filterClassesByAnnotation($classes, $annotation) {
        return array_filter($classes, function($class) {
            return $this->addendum->reflect($class)->getAnnotations() instanceof $annotation;
        });
    }
}

?>