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
 * @F:Provides("Compiler")
 * @F:DefaultProvider
 */
class Compiler extends Object {
    protected $baseNamespace = "Fossil\\Compiled\\";
    protected $baseDir;
    protected $classMap = array();
    protected $reflClassMap = array();
    
    /**
     * @F:Inject(type = "Reflection", lazy = true)
     * @var Fossil\ReflectionBroker
     */
    protected $broker;
    /**
     * @F:Inject("AnnotationManager")
     * @var Fossil\Annotations\AnnotationManager
     */
    protected $annotations;
    /**
     * @F:Inject("Core")
     * @var Fossil\Core
     */
    protected $core;
    /**
     * @F:Inject("Filesystem")
     * @var Fossil\Filesystem
     */
    protected $fs;
    protected $compiled = array();
    
    protected function baseClassName($fqn) {
        $lastSlash = strrpos($fqn, '\\');
        if($lastSlash === FALSE) {
            return $fqn;
        }
        return substr($fqn, $lastSlash+1);
    }
    
    protected function baseNamespaceName($fqn) {
        if(strlen($fqn) && $fqn[0] == '\\') {
            $fqn = substr($fqn, 1);
        }
        $lastSlash = strrpos($fqn, '\\');
        if($lastSlash === FALSE) {
            return '';
        }
        return substr($fqn, 0, $lastSlash);
    }
    
    public function mapCompiledClassNameToOriginal($className) {
        $className = ltrim($className, '\\');
        $details = $this->core->getFossilDetails();
        $fossilNS = $details['ns'] . '\Compiled';
        if(strpos($className, $fossilNS) !== 0) {
            return $className;
        }
        if(strpos($className, $fossilNS . '\Fossil') === 0) {
            if($details) {
                return str_replace($fossilNS . '\Fossil', $details['ns'], $className);
            }
        } elseif(strpos($className, $fossilNS . '\App') === 0) {
            $details = $this->core->getAppDetails();
            if($details) {
                return str_replace($fossilNS . '\App', $details['ns'], $className);
            }
        } elseif(strpos($className, $fossilNS . '\Overlay') === 0) {
            $details = $this->core->getOverlayDetails();
            if($details) {
                return str_replace($fossilNS . '\Overlay', $details['ns'], $className);
            }
        }
        return $className;
    }
    
    protected function transformNamespace($inputNamespace) {
        // Determine which namespace we live in - root, app, or overlay
        $fossilDetails = $this->core->getFossilDetails();
        $appDetails = $this->core->getAppDetails();
        $overlayDetails = $this->core->getOverlayDetails();
        
        $baseNamespace = '';
        $outputNamespace = $fossilDetails['ns'] . '\\' . 'Compiled' . '\\';
        $finalNamespace = $outputNamespace;

        if($fossilDetails) {
            if(strpos($inputNamespace, $fossilDetails['ns']) === 0) {
                if(strlen($fossilDetails['ns']) > strlen($baseNamespace)) {
                    $baseNamespace = $fossilDetails['ns'];
                    $finalNamespace = $outputNamespace . 'Fossil';
                }
            }
        }
        if($appDetails) {
            if(strpos($inputNamespace, $appDetails['ns']) === 0) {
                if(strlen($appDetails['ns']) > strlen($baseNamespace)) {
                    $baseNamespace = $appDetails['ns'];
                    $finalNamespace = $outputNamespace . 'App';
                }
            }
        }
        if($overlayDetails) {
            if(strpos($inputNamespace, $overlayDetails['ns']) === 0) {
                if(strlen($overlayDetails['ns']) > strlen($baseNamespace)) {
                    $baseNamespace = $overlayDetails['ns'];
                    $finalNamespace = $outputNamespace . 'Overlay';
                }
            }
        }
        // Strip baseNamespace off inputNamespace, and replace with outputNamespace
        return str_replace($baseNamespace, $finalNamespace, $inputNamespace);
    }
    
    protected function saveClass($className, $namespaceName, $source) {
        // Save the class to disk
        // First, determine the path, and ensure it exists
        $fossilDetails = $this->core->getFossilDetails();
        $basePath = $this->fs->tempDir() . D_S . 'Compiled' . D_S;
        $baseNS = $fossilDetails['ns'] . '\\' . 'Compiled' . '\\';
        // Remove the baseNS from the nsName
        $trimmedNS = str_replace($baseNS, '', $namespaceName);
        $middlePath = $basePath . implode(D_S, explode('\\', $trimmedNS));
        // Make sure that the middle path exists
        if(!is_dir($middlePath)) {
            mkdir($middlePath, 0755, true);
        }
        // And finally, save the new class
        $classPath = $middlePath . D_S . $className . ".php";
        file_put_contents($classPath, $source);
        // And tell the broker about the new class
        $this->broker->scanFile($classPath);
    }
    
    public function compileAllClasses() {
        $classList = $this->container->getAllKnownClasses();
        foreach($classList as $class) {
            $this->compileClass($class);
        }
        // And set the class map
        $this->container->setClassMap($this->classMap);
    }
    
    protected function compileClass($class) {
        $reflClass = $this->broker->getClass($class);
        $workingClass = $class;
        $parentClass = $reflClass->getParentClassName();
        if($parentClass != null) {
            if(empty($this->compiled[$parentClass])) {
                $this->compileClass($parentClass);
            }
        }
        // If we were picked up in the extension class pass, return
        if(isset($this->compiled[$class]) && $this->compiled[$class]) {
            return;
        }
        if(isset($this->classMap[$parentClass]) && $this->classMap[$parentClass] != $parentClass) {
            $workingClass = $this->reparentClass($workingClass, $this->classMap[$parentClass]);
        }
        if($this->needsExtension($class)) {
            $workingClass = $this->compileExtensions($class, $workingClass);
        }
        // After compilation, check all direct descendants to see whether they're
        // extension classes - if so, descend and compile them before returning
        // to the parent
        // Behaviour when there are multiple extension classes on a particular
        // parent is undefined
        if($this->isExtensionClass($class)) {
            $this->classMap[$parentClass] = $workingClass;
        }
        $this->compiled[$class] = true;
        $this->classMap[$class] = $workingClass;
        foreach($reflClass->getDirectSubclassNames() as $subclass) {
            if($this->isExtensionClass($subclass)) {
                $this->compileClass($subclass);
            }
        }
    }
    
    protected function reparentClass($originalClass, $newParent) {
        $baseClassName = $this->baseClassName($originalClass);
        // @codingStandardsIgnoreStart
        $sourceTpl = <<<'EOT'
<?php

namespace %s;

%s

%s
EOT;
        // @codingStandardsIgnoreEnd
        $oldReflClass = $this->broker->getClass($originalClass);
        // First param, new namespace
        $oldNamespace = $oldReflClass->getNamespaceName();
        $newNamespace = $this->transformNamespace($oldNamespace);
        // Second param, use list - contains all existing use declarations, and
        // extra use declarations for every class in the current namespace
        $useList = $oldReflClass->getNamespaceAliases();
        foreach($this->broker->getNamespace($oldNamespace)->getClassNames() as $className) {
            $useList[$this->baseClassName($className)] = $className;
        }
        $useListStr = "";
        if(count($useList)) {
            $useListStr = "use ";
            foreach($useList as $alias => $fqn) {
                // Skip ourselves, for obvious reasons
                if($alias != $baseClassName) {
                    $useListStr .= $fqn . " as " . $alias . ",\n\t";
                }
            }
            $useListStr = trim($useListStr, ",\n\t") . ";";
        }
        // Third param, the class source, with the "extends" clause rewritten
        $origSource = $this->broker->getClass($originalClass)->getSource();
        $origSource = preg_replace("/^(.*?\s+extends\s+)[\S]+?\s*{/s",
                                   '\1\\' . $newParent . ' {', $origSource);
        
        // Print the new source
        $newSource = sprintf($sourceTpl, $newNamespace, $useListStr, $origSource);
        // And save the new class
        $this->saveClass($baseClassName, $newNamespace, $newSource);
        // And return the new class name
        return $newNamespace . '\\' . $baseClassName;
    }
    
    protected function isExtensionClass($class) {
        return $this->annotations->classHasAnnotation($class, "F:ExtensionClass", false);
    }
    
    protected function needsExtension($originalClass) {
        return $this->annotations->classHasMethodAnnotation($originalClass, "F:Compilation");
    }
    
    protected function compileExtensions($originalClass, $currentClass) {
        $workingClass = $currentClass;
        
        // Gather the types of extensions that we're going to use, one class per extension
        $reflClass = $this->broker->getClass($originalClass);
        $allAnnos = array();
        foreach($reflClass->getMethods() as $method) {
            // Ignore inherited methods
            if($method->getDeclaringClassName() != $originalClass) {
                continue;
            }
            $annos = $this->annotations->getMethodAnnotations($method, "F:Compilation");
            foreach($annos as $anno) {
                $allAnnos[get_class($anno)] = $anno;
            }
        }
        
        foreach($allAnnos as $compilationAnno) {
            $workingClass = $this->compileExtension($originalClass, $workingClass, $compilationAnno);
        }
        return $workingClass;
    }
    
    protected function compileExtension($originalClass, $currentClass, $compilationAnnotation) {
        // Determine final class name
        $newFQN = $this->transformNamespace($originalClass) . "_" .
                  $this->baseClassName(get_class($compilationAnnotation));
        $newClassName = $this->baseClassName($newFQN);
        $reflOrigClass = $this->broker->getClass($originalClass);
        $reflCompileClass = $this->broker->getClass(get_class($compilationAnnotation));
        $reflCompileMethod = $reflCompileClass->getMEthod('call');
        
        // @codingStandardsIgnoreStart
        $classTpl = <<<'EOC'
<?php

namespace %s;

%s

class %s extends \%s {
    private function completeCall($funcname, $args) {
        return call_user_func_array("parent::$funcname", $args);
    }

%s
}
EOC;
        $methodTpl = <<<'EOM'
    %s {
        $args = func_get_args();
        static $compileArgs = null;
        if(!$compileArgs)
            $compileArgs = unserialize('%s');
        
        $funcname = __FUNCTION__;

        %s
    }

EOM;
        // @codingStandardsIgnoreEnd
        
        // Figure out our target namespace
        $newNS = $this->baseNamespaceName($newFQN);
        // And the namespace aliases
        $useList = $reflOrigClass->getNamespaceAliases();
        // Use a second list for namespace aliases in the compile class
        // This will cause alias collisions to cause a PHP error instead of
        // failing silently
        $useList2 = $reflCompileClass->getNamespaceAliases();
        $useListStr = "";
        if(count($useList)) {
            $useListStr = "use ";
            foreach($useList as $alias => $fqn) {
                $useListStr .= $fqn . " as " . $alias . ",\n\t";
            }
            foreach($useList2 as $alias => $fqn) {
                $useListStr .= $fqn . " as " . $alias . ",\n\t";
            }
            $useListStr = trim($useListStr, ",\n\t") . ";";
        }

        // Next, build up the list of methods to extend
        $overriddenMethodSource = "";
        $compilationAnnoClass = get_class($compilationAnnotation);
        foreach($reflOrigClass->getMethods() as $reflMethod) {
            if($reflMethod->getDeclaringClassName() != $originalClass) {
                continue;
            }
            if(count($this->annotations->getMethodAnnotations($reflMethod, $compilationAnnoClass))) {
                $annos = $this->annotations->getMethodAnnotations($reflMethod, $compilationAnnoClass);
                $anno = reset($annos);
                // This method needs to be overridden
                $fullMethodSource = $reflMethod->getSource();
                $methodPreamble = substr($fullMethodSource, 0, strpos($fullMethodSource, "{")-1);
                // TODO: Strip annotations from doc-comments
                
                $fullCompileSource = $reflCompileMethod->getSource();
                $methodPostamble = substr($fullCompileSource, strpos($fullCompileSource, "{")+1);
                $methodPostamble = trim(substr($methodPostamble, 0, strrpos($methodPostamble, "}")));
                
                $overriddenMethodSource .= sprintf($methodTpl, $methodPreamble,
                                                   serialize($anno->getArgs()), $methodPostamble);
            }
        }
        $source = sprintf($classTpl, $newNS, $useListStr,
                          $newClassName, $currentClass, $overriddenMethodSource);
        
        $this->saveClass($newClassName, $newNS, $source);
        return $newFQN;
    }
}