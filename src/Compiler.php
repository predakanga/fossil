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
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil;

/**
 * @F:Object("Compiler")
 */
class Compiler {
    protected $baseNamespace = "Fossil\\Compiled\\";
    protected $baseDir;
    protected $classMap = array();
    protected $reflClassMap = array();
    protected $broker;

    public function __construct() {
        $this->baseDir = OM::FS()->tempDir() . D_S . "compiled";
    }
    
    public function registerAutoloadPath() {
        Autoloader::addNamespacePath(rtrim($this->baseNamespace, "\\"), $this->baseDir);
    }
    
    protected function getBroker() {
        if(!$this->broker) {
            $this->broker = new \TokenReflection\Broker(new \TokenReflection\Broker\Backend\Memory());
        }
        return $this->broker;
    }
    
    protected function saveClass($fqcn, $source) {
        // Work out the file path
        $parts = explode("\\", $fqcn);
        $real_parts = array_slice($parts, 2);
        $filename = array_pop($real_parts);
        $dirpath = $this->baseDir . D_S . implode(D_S, array_map(function($a) { return lcfirst($a); }, $real_parts));

        if(!is_dir($dirpath))
            mkdir($dirpath, 0755, true);

        file_put_contents($dirpath . DIRECTORY_SEPARATOR . $filename . ".php", $source);
    }

    protected function basename($fqcn) {
        return substr($fqcn, strrpos($fqcn, "\\")+1);
    }
    protected function nsname($fqcn) {
        return substr($fqcn, 0, strrpos($fqcn, "\\"));
    }

    protected function getAccessStr($access) {
        return implode(' ', \Reflection::getModifierNames($access));
    }

    protected function transformNamespace($inputNamespace) {
        if(strpos($inputNamespace, "Fossil\\") === 0) {
            return $this->baseNamespace .
                    substr($inputNamespace, strlen("Fossil\\"));
        } elseif(strpos($inputNamespace, OM::appNamespace()) === 0) {
            return $this->baseNamespace . "App\\" .
                    substr($inputNamespace, strlen(OM::appNamespace()) + 1);
        } elseif(strpos($inputNamespace, OM::overlayNamespace()) === 0) {
            return $this->baseNamespace . "Overlay\\" .
                    substr($inputNamespace, strlen(OM::overlayNamespace()) + 1);
        } else {
            throw new \Exception("Trying to compile a namespace beyond our scope: $inputNamespace (not in " . OM::appNamespace() . "," . OM::overlayNamespace() . ")");
        }
    }

    protected function launchCompile($class) {
        // If it's already been compiled, just return
        if(isset($this->classMap[$class]))
            return;

        // Gather the class tree for this class
        $classTree = $this->getClassExtensionTree($class);
        return $this->compileTree($class, $classTree);
    }
    
    public function getClassExtensionTree($class) {
        if($class[0] == '\\')
            $class = substr($class, 1);
        $retArr = array();
        // First, check if there are any compilation attributes on this class
        $reflClass = new \ReflectionClass($class);
        $compileClasses = array();
        foreach($reflClass->getMethods() as $reflMethod) {
            if($reflMethod->class != $class)
                continue;
            $annotations = OM::Annotations()->getMethodAnnotations($reflMethod, "F:Compilation");
            foreach($annotations as $annotation) {
                if(!in_array($annotation, $compileClasses))
                    $compileClasses[] = $annotation;
            }
        }
        foreach($compileClasses as $compileClass) {
            $retArr[] = array($class, $compileClass);
        }
        // Next, visit all applicable extension classes, but only immediate children
        foreach(OM::getExtensionClasses() as $extClass) {
            if(get_parent_class($extClass) == $class) {
                $retArr[] = $extClass;
                $retArr = array_merge($retArr, $this->getClassExtensionTree($extClass));
            }
        }
        // Finally, check non-extension classes
        $nonExtClasses = OM::Annotations()->filterClassesByAnnotation(get_declared_classes(), "F:ExtensionClass", true);
        foreach($nonExtClasses as $nonExtClass) {
            if(get_parent_class($nonExtClass) != $class)
                continue;
            $subTree = array("*", $nonExtClass);
            $subTree = array_merge($subTree, $this->getClassExtensionTree($nonExtClass));
            $retArr[] = $subTree;
        }
        return $retArr;
    }

    protected function compileTree($base, $tree, $followBase = true) {
        if(count($tree) == 0)
            return $base;
        
        $current_objects = array();
        if($followBase)
            $current_objects[] = $base;

        $compilationNeeded = false;

        $new_base = $base;
        foreach($tree as $leaf) {
            if(is_array($leaf) && $leaf[0] != "*") {
                // Compilation leaf
                $new_base = $this->compileClass($leaf[0], $leaf[1], $new_base);
                $compilationNeeded = true;
            } elseif(!is_array($leaf)) {
                // Reparent leaf
                $new_base = $this->reparentClass($leaf, $new_base);
                $compilationNeeded = true;
                $current_objects[] = $leaf;
            } else {
                // Reparent, without affecting the new base
                if($compilationNeeded)
                    $this->compileTree($new_base, array_slice($leaf, 1), false);
                else
                    $this->compileTree($leaf[1], array_slice($leaf, 2));
            }
        }
        foreach($current_objects as $class)
            $this->classMap[$class] = $new_base;
        return $new_base;
    }

    protected function findClass($filename, $class) {
        if(isset($this->reflClassMap[$class])) {
            return $this->reflClassMap[$class];
        }
        $fileInfo = $this->getBroker()->processFile($filename, true);
        foreach($fileInfo->getNamespaces() as $ns) {
            foreach($ns->getClasses() as $reflClass) {
                if($reflClass->getName() == $class) {
                    $this->reflClassMap[$class] = $reflClass;
                    return $reflClass;
                }
            }
        }
    }
    
    protected function reparentClass($class, $targetParent) {
        $newName = $this->transformNamespace($class);
        $newClassName = $this->basename($class);
        $targetNamespace = $this->transformNamespace($this->nsname($class));

        // Grab the original class's source
        $reflClass = new \ReflectionClass($class);
        $fileSource = file($reflClass->getFileName());
        
        // Use reflClass to get the FQCN
        $classInfo = $this->findClass($reflClass->getFileName(), $reflClass->getName());
        $classSource = $classInfo->getSource();
        
        // TODO: Decide whether to bring in the rest of the file too - probably
        //       safest to, but needs the implementation of a tokenizer first,
        //       to properly grab the reparented class

        // Use a regex to replace the name and parent
        $newParent = "\\" . $targetParent;
        $classSource = preg_replace('/^([^{]+extends\s+)\S+/S', "\\1$newParent", $classSource);

        $pageSource = <<<'EOT'
<?php

namespace %s;

%s
EOT;
        $this->saveClass($newName, sprintf($pageSource, $targetNamespace, $classSource));

        return $this->transformNamespace($class);
    }

    protected function compileClass($class, $compileClass, $targetParent) {
        $newName = $this->transformNamespace($class) . "_" . $this->basename(get_class($compileClass));
        $newClassName = $this->basename($class) . "_" . $this->basename(get_class($compileClass));
        $targetNamespace = $this->transformNamespace($this->nsname($class));

        // Grab the source...
        $compiledMethods = array();
        $reflClass = new \ReflectionClass($class);
        $reflMethods = $reflClass->getMethods();

        $reflAnno = new \ReflectionClass($compileClass);
        $compileClassInfo = $this->findClass($reflAnno->getFileName(), $reflAnno->getName());
        
        $classInfo = $this->findClass($reflClass->getFileName(), $reflClass->getName());
        
        $nsAliases = $compileClassInfo->getNamespaceAliases();
        $useText = "";
        
        if(count($nsAliases)) {
            $useText = "use ";
            foreach($nsAliases as $alias => $ns) {
                $useText .= "$ns as $alias, ";
            }
            $useText = rtrim($useText, ", ") . ";";
        }
        
        foreach($reflMethods as $reflMethod) {
            // First, make sure it's locally implemented
            if($reflMethod->class != $class)
                continue;
            // Next, check for our desired annotation
            if(OM::Annotations()->getMethodAnnotations($reflMethod, get_class($compileClass))) {
                $newMethod = array();
                $newMethod['name'] = $reflMethod->name;
                $newMethod['access'] = $this->getAccessStr($reflMethod->getModifiers());
                
                // Simple solution to get the preamble
                $methodInfo = $classInfo->getMethod($reflMethod->name);
                $fullMethodSource = $methodInfo->getSource();
                $methodPreamble = substr($fullMethodSource, 0, strpos($fullMethodSource, "{")-1);
                
                $compileMethodInfo = $compileClassInfo->getMethod("call");
                $fullCompileSource = $compileMethodInfo->getSource();
                $methodPostamble = substr($fullCompileSource, strpos($fullCompileSource, "{")+1);
                
                $newMethod['preamble'] = $methodPreamble;
                $newMethod['postamble'] = trim($methodPostamble, " \r\n\r\0\x0B}");
                
                $compiledMethods[] = $newMethod;
            }
        }

        // Construct the new class
        $classText = <<<'EOT'
<?php
namespace %s;

%s

class %s extends \%s {
    private function completeCall($funcname, $args) {
        return call_user_func_array("parent::$funcname", $args);
    }

%s
}
EOT;
    $methodText = <<<'EOT'
    %s {
        $args = func_get_args();
        static $compileArgs = null;
        if(!$compileArgs)
            $compileArgs = unserialize('%s');
        
        $funcname = __FUNCTION__;

        %s
    }

EOT;

        $compiledMethodText = "";
        $compiledClassText = "";
        foreach($compiledMethods as $method) {
            $compiledMethodText .= sprintf($methodText, $method['preamble'], serialize($compileClass->getArgs()), $method['postamble']);
        }
        if($targetParent[0] == '\\')
            $targetParent = substr($targetParent, 1);
        
        $compiledClassText = sprintf($classText, $targetNamespace, $useText, $newClassName, $targetParent, $compiledMethodText);

        $this->saveClass($targetNamespace . "\\" . $newClassName, $compiledClassText);

        return $newName;
    }

    public function compileAll() {
        // Get a list of all objects for compilation
        foreach (OM::getObjectsToCompile() as $class) {
            $this->launchCompile($class);
        }
        return $this->classMap;
    }

    public function bootstrap() {
        // The bootstrapping simply compiles itself, so that an enhanced compiler can be used as provided
        $newTopClass = $this->launchCompile("\\" . __CLASS__);
        if($newTopClass != get_class($this)) {
            OM::setSingleton("Compiler", new $newTopClass);
        }
        return $this->classMap;
    }
    
    public function setClassMap($classMap) {
        $this->classMap = $classMap;
    }
}

?>