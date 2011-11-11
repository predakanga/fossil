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

use Fossil\OM;

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
     * @F:Inject("Dispatcher")
     * @var Fossil\Dispatcher
     */
    protected $dispatcher;
    /**
     * @F:Inject(type = "ORM", lazy = true)
     * @var Fossil\ORM
     */
    protected $orm;
    
    // Because this has no dependencies, it's guaranteed to be instantiated first
    public function __construct($container) {
        parent::__construct($container);
    }
    
	public function run() {
        // Main loop process:
        $this->dispatcher->run();
        // fastcgi_finish_request() if available
        if(function_exists("fastcgi_finish_request"))
            fastcgi_finish_request();
        // Then flush the DB (note: might want to do this beforehand, in case of errors)
        $this->orm->flush();
        // Run any registered background tasks
        return;
    }
    
    public function setAppDetails($details) {
        // Details should only be set *before* the Filesystem is created
        assert(!$this->container->has("Filesystem"));
        $this->appDetails = $details;
        $this->instanceName = null;
    }
    
    public function getAppDetails() {
        return $this->appDetails;
    }
    
    public function setOverlayDetails($details) {
        // Details should only be set *before* the Filesystem is created
        assert(!$this->container->has("Filesystem"));
        $this->overlayDetails = $details;
        $this->instanceName = null;
    }
    
    public function getOverlayDetails() {
        return $this->overlayDetails;
    }
    
    public function getInstanceID() {
        if(!$this->instanceName) {
            $this->instanceName = "fossil";
            if($this->appDetails) {
                $this->instanceName .= "_" . $this->appDetails['name'];
            }
            if($this->overlayDetails) {
                $this->instanceName .= "_" . $this->overlayDetails['name'];
            }
        }
        return $this->instanceName;
    }
    
    public function getInstanceHash() {
        // If in dev mode, return a random hash per boot
        static $devHash;
        
        if(!$devHash) {
            $devHash = md5(uniqid());
        }
        return $devHash;
    }
    
    public static function create() {
        $newContainer = new ObjectContainer;
        return $newContainer->get("Core");
    }
}