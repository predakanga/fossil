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
 * @subpackage Controllers
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil\Controllers;

use Fossil\Exceptions\NoSuchActionException;

/**
 * Description of AutoController
 *
 * @author predakanga
 */
abstract class AutoController extends BaseController {
    /**
     * @F:Inject(type = "Reflection", lazy = true)
     * @var Fossil\ReflectionBroker
     */
    protected $reflections;
    /**
     * @F:Inject("FormManager")
     * @var Fossil\Forms\FormManager
     */
    protected $forms;
    
    public function run(\Fossil\Requests\BaseRequest $req) {
        // Decide what action to use
        $action = $req->action ?: $this->indexAction();
        // Compute the method name
        $actionMethod = "run" . ucfirst($action);
        
        if(!method_exists($this, $actionMethod)) {
            throw new NoSuchActionException($req->controller, $action);
        }
        
        // And try to call it on ourselves
        $realArgs = $this->resolveArguments($actionMethod, $req->args);
        return call_user_func_array(array($this, $actionMethod), $realArgs);
    }
    
    public function indexAction() {
        return "index";
    }
    
    protected function getArguments() {
        $dispatcher = $this->container->get("Dispatcher");
        $req = $dispatcher->getCurrentRequest();
        return $req->args;
    }
    
    protected function getArgument($argName) {
        $args = $this->getArguments();
        if(isset($args[$argName])) {
            return $args[$argName];
        }
        return null;
    }
    
    protected function resolveArguments($method, $args) {
        // TODO: Cache param info
        $args = array_change_key_case($args, CASE_LOWER);
        $outputArgs = array();
        
        // For each of the method's arguments, grab the input from the args
        $reflClass = new \ReflectionClass(get_class($this));
        $reflMethod = $reflClass->getMethod($method);
        foreach($reflMethod->getParameters() as $reflParam) {
            $paramType = $reflParam->getClass();
            $name = strtolower($reflParam->getName());
            $finalArg = null;
            
            if($paramType && $paramType->isSubclassOf("Fossil\Forms\BaseForm")) {
                $finalArg = $this->resolveForm($reflParam);
            } elseif(isset($args[$name])) {
                $finalArg = $this->resolveArgument($reflParam, $args[$name]);
            }
            if($finalArg == null) {
                // If the argument isn't optional, throw an exception immediately
                if(!$reflParam->isOptional()) {
                    throw new \Exception("Required parameter was not provided: " . $name);
                } else {
                    $finalArg = $reflParam->getDefaultValue();
                }
            }
            $outputArgs[] = $finalArg;
        }
        return $outputArgs;
    }
    
    protected function resolveArgument($reflParam, $value) {
        $paramType = $reflParam->getClass();
        
        // If there's no type hint, return the value un-modified
        if($paramType == null) {
            return $value;
        }
        
        $paramName = $reflParam->getName();
        $paramTypeName = $reflParam->getClass()->name;
        
        // If the type hint is a model...
        if($paramType->isSubclassOf("Fossil\Models\Model")) {
            // Look it up by primary key, which the value should be the value of
            $method = array($paramTypeName, "find");
            $retval = call_user_func_array($method, array($this->container, $value));
            if(!$retval && !$reflParam->isOptional()) {
                throw new \Fossil\Exceptions\NoSuchInstanceException("Unknown $paramName specified");
            }
            return $retval;
        }
        return $value;
    }
    
    protected function resolveForm($reflParam) {
        return $this->forms->get($reflParam->getClass()->name);
    }
}
