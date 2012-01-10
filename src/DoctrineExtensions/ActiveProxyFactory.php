<?php

/*
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
 */

namespace Fossil\DoctrineExtensions;

use Fossil\ObjectContainer,
    Fossil\Models\Model,
    Doctrine\ORM\Proxy\ProxyFactory;

/**
 * Description of ActiveProxyFactory
 *
 * @author predakanga
 */
class ActiveProxyFactory extends ProxyFactory {
    /**
     *
     * @var ProxyFactory
     */
    private $factory;
    /**
     *
     * @var ObjectContainer
     */
    private $container;
    
    public function __construct(ObjectContainer $container, ProxyFactory $internalProxyFactory) {
        $this->container = $container;
        $this->factory = $internalProxyFactory;
    }
    
    public function getProxy($className, $identifier) {
        $toRet = $this->factory->getProxy($className, $identifier);
        if($toRet instanceof Model) {
            // HACKHACK: To stop restoreObjects() from loading the proxy immediately
            // we have to make it think it's already initialized
            $oldInit = $toRet->__isInitialized__;
            $toRet->__isInitialized__ = true;
            $toRet->restoreObjects($this->container);
            $toRet->__isInitialized__ = $oldInit;
        }
        return $toRet;
    }
    
    public function generateProxyClasses(array $classes, $toDir = null) {
        return $this->factory->generateProxyClasses($classes, $toDir);
    }
}
