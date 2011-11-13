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

use Fossil\Util\FilesystemScanner;

/**
 * Filesystem Helper
 * 
 * Provides functions to get provide access to a virtual filesystem
 * as defined by the various loaded plugins
 *
 * @F:Provides("Filesystem")
 * @F:DefaultProvider
 * @author predakanga
 * @since 0.1
 */
class Filesystem extends Object {
    protected $overlayRoot = false;
    protected $appRoot = false;
    protected $tempDir;
    /**
     * @F:Inject("Core")
     * @var Fossil\Core
     */
    protected $core;
    /**
     * @F:Inject(type = "Settings", lazy = true)
     * @var Fossil\Settings
     */
    protected $settings;
    /**
     * @F:Inject(type = "Plugins", lazy = true)
     * @var Fossil\PluginManager
     */
    protected $plugins;
    
    public function __construct($container) {
        parent::__construct($container);
        
        $appDetails = $this->core->getAppDetails();
        if($appDetails) {
            $this->appRoot = $appDetails['path'];
        }
        $overlayDetails = $this->core->getOverlayDetails();
        if($overlayDetails && isset($overlayDetails['path'])) {
            $this->overlayRoot = $overlayDetails['path'];
        }
    }
    
    /**
     * 
     * @return array List of roots in which to look for classes, templates, etc
     */
    public function roots($includePlugins = true) {
        $roots = array($this->fossilRoot());
        if($this->appRoot())
            $roots[] = $this->appRoot();
        if($includePlugins)
            $roots = array_merge($roots, $this->pluginRoots());
        if($this->overlayRoot())
            $roots[] = $this->overlayRoot();
        
        return $roots;
    }
    
    public function fossilRoot() {
        return __DIR__;
    }
    
    public function setAppRoot($appRoot) {
        $this->appRoot = $appRoot;
    }
    
    public function appRoot() {
        return $this->appRoot;
    }
    
    public function setOverlayRoot($overlayRoot) {
        $this->overlayRoot = $overlayRoot;
    }
    
    public function overlayRoot() {
        if(!$this->overlayRoot === false) {
            $overlayRoot = $this->execDir();
            if($overlayRoot == $this->fossilRoot() || $overlayRoot == $this->appRoot())
                $this->overlayRoot = false;
            else
                $this->overlayRoot = $overlayRoot;
        }
        return $this->overlayRoot;
    }
    
    public function tempDir() {
        if(!$this->settings->isBootstrapped()) {
            $tempDir = sys_get_temp_dir() . D_S . $this->core->getInstanceID();
            // Ensure that it exists
            if(!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            return $tempDir;
        } else if(!$this->tempDir) {
            $tempDir = $this->settings->get("Fossil", "temp_dir", sys_get_temp_dir());
            $tempDir .= D_S . $this->core->getInstanceID();
            
            // Ensure that it exists
            if(!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $this->tempDir = $tempDir;
        }
        return $this->tempDir;
    }
    
    public function execDir() {
        // TODO: Make sure this works with CLI
        if(isset($_ENV['phpunit']) && $_ENV['phpunit'] == "true")
            return __DIR__;
        return dirname($_SERVER['SCRIPT_FILENAME']);
    }
    
    public function pluginRoots() {
        $toRet = array();
        
        if(!$this->plugins->_isReady()) {
            return $toRet;
        }
        $enabledPlugins = $this->plugins->getEnabledPlugins();
        foreach($enabledPlugins as $pluginName) {
            $plugin = $this->plugins->get($pluginName);
            $toRet[] = $plugin['root'];
        }
        return $toRet;
    }
    
    public function allSourceFiles() {
        $sourceFiles = array();
        
        foreach($this->roots() as $root) {
            $sourceFiles = array_merge($sourceFiles, FilesystemScanner::sourceFiles($root));
        }
        return $sourceFiles;
    }
}

?>
