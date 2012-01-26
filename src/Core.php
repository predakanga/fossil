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
 * The core Fossil class, provides lifecycle management
 * 
 * @author predakanga
 * @since 0.1
 * @F:Provides("Core")
 * @F:DefaultProvider
 */
class Core extends Object {
    public $version = "1.0.0";
    protected $instanceName = null;
    protected $appDetails = null;
    protected $overlayDetails = null;
    /**
     * @F:Inject(type = "Dispatcher", lazy = true)
     * @var Fossil\Dispatcher
     */
    protected $dispatcher;
    /**
     * @F:Inject(type = "ORM", lazy = true)
     * @var Fossil\ORM
     */
    protected $orm;
    
    public $startTime;
    public $startMem;
    
    // Because this has no dependencies, it's guaranteed to be instantiated first
    public function __construct($container) {
        parent::__construct($container);
        $this->startTime = microtime(TRUE);
        $this->startMem = memory_get_usage();
    }
    
    protected function determineObjects() {
        // As a special case, we don't do auto-discovery on this object
        return array(array('type' => 'Dispatcher', 'destination' => 'dispatcher',
                           'required' => true, 'lazy' => true),
                     array('type' => 'ORM', 'destination' => 'orm',
                           'required' => true, 'lazy' => true));
    }
    
    public function run() {
        // Main loop process:
        $this->dispatcher->run();
        // fastcgi_finish_request() if available
        if(function_exists("fastcgi_finish_request")) {
            fastcgi_finish_request();
        }
        // Run any registered background tasks
        return;
    }
    
    public function setAppDetails($details) {
        // Details should only be set *before* the Filesystem is created
        if($this->container->has("Filesystem")) {
//            trigger_error("Core::setAppDetails should only be called when Filesystem has not yet been used", E_USER_WARNING);
        }
        $this->appDetails = $details;
        $this->instanceName = null;
    }
    
    public function getAppDetails() {
        return $this->appDetails;
    }
    
    public function setOverlayDetails($details) {
        // Details should only be set *before* the Filesystem is created
        if($this->container->has("Filesystem")) {
//            trigger_error("Core::setOverlayDetails should only be called when Filesystem has not yet been used", E_USER_WARNING);
        }
        $this->overlayDetails = $details;
        $this->instanceName = null;
    }
    
    public function getOverlayDetails() {
        return $this->overlayDetails;
    }
    
    public function getFossilDetails() {
        return array('ns' => 'Fossil', 'path' => __DIR__);
    }
    
    public function getInstanceID() {
        if(!$this->instanceName) {
            $this->instanceName = "fossil";
            if($this->appDetails) {
                $this->instanceName .= "_" . $this->appDetails['ns'];
            }
            if($this->overlayDetails) {
                $this->instanceName .= "_" . $this->overlayDetails['ns'];
            }
        }
        return $this->instanceName;
    }
    
    protected $hash;
    
    public function getInstanceHash() {
        if(!$this->hash) {
            $this->hash = md5($this->getMtime());
        }
        return $this->hash;
    }
    
    public function setInstanceHash($hash) {
        $this->hash = $hash;
    }
    
    public function getMtime() {
        static $finalTime;
        
        if($finalTime) {
            return $finalTime;
        }
        
        $fs = $this->container->get("Filesystem");
        
        $maxMtimes = array();
        $maxMtimes[] = $fs->getRecursiveMtime($fs->fossilRoot());
        if($this->appDetails) {
            $maxMtimes[] = $fs->getRecursiveMtime($fs->appRoot());
        }
        if($this->overlayDetails) {
            $maxMtimes[] = $fs->getRecursiveMtime($fs->overlayRoot());
        }
        $finalTime = max($maxMtimes);
        return $finalTime;
    }
    
    public function getIsModified($oldMtime) {
        $fs = $this->container->get("Filesystem");
        
        if($fs->getRecursiveMtimeGreaterThan($fs->fossilRoot(), $oldMtime)) {
            return true;
        }
        if($this->appDetails && $fs->getRecursiveMtimeGreaterThan($fs->appRoot(), $oldMtime)) {
            return true;
        }
        if($this->overlayDetails && $fs->getRecursiveMtimeGreaterThan($fs->overlayRoot(), $oldMtime)) {
            return true;
        }
        return false;
    }
    
    public static function create($appNS = null, $appPath = null, $setDefaultInstance = true) {
        global $overlayNamespace, $overlayPath;
        
        $appDetails = null;
        $overlayDetails = null;
        if($appNS) {
            $appDetails = array('ns' => $appNS, 'path' => $appPath);
            // Add the target to the autoloader
            Autoloader::addNamespacePath($appNS, $appPath);
        }
        if($overlayNamespace) {
            $overlayDetails = array('ns' => $overlayNamespace, 'path' => $overlayPath);
            // Add the target to the autoloader
            Autoloader::addNamespacePath($overlayNamespace, $overlayPath);
        }
        
        $newContainer = new ObjectContainer($appDetails, $overlayDetails);
        $newContainer->setDefaultInstance($setDefaultInstance);
        return $newContainer->get("Core");
    }
}