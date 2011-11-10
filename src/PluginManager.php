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
 * 
 * @author predakanga
 * @since 0.1
 * @category Fossil Core
 * @package Fossil
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil;

/**
 * Description of PluginManager
 *
 * @author predakanga
 * @F:Provides("Plugins")
 * @F:DefaultProvider
 */
class PluginManager extends Object {
    protected $availablePlugins = array();
    protected $enabledPlugins = array();
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
    /**
     * @F:Inject("Settings")
     * @var Fossil\Settings
     */
    protected $settings;
    
    public function __construct($container) {
        parent::__construct($container);
        
        $this->discoverPlugins();
    }
    
    public function discoverPlugins() {
        // Look for available plugins
        foreach($this->fs->roots(false) as $root) {
            $this->findAvailablePlugins($root);
        }
    }
    
    public function get($pluginName) {
        if($this->has($pluginName))
            return $this->availablePlugins[$pluginName];
        throw new \Exception("Plugin not found: $pluginName");
    }
    
    public function findAvailablePlugins($root) {
        // Reuse the Filesystem filter
        $globIter = new \GlobIterator($root . D_S . "Plugins" . D_S . "*" . D_S . "plugin.yml");
        foreach($globIter as $pluginFile) {
            $pluginInfo = yaml_parse_file($pluginFile->getRealPath());
            $pluginInfo['root'] = $pluginFile->getPath();
            $this->availablePlugins[$pluginInfo['name']] = $pluginInfo;
        }
    }
    
    public function getAvailablePlugins() {
        return $this->availablePlugins;
    }
    
    public function getEnabledPlugins() {
        return $this->enabledPlugins;
    }
    
    public function has($pluginName) {
        return isset($this->availablePlugins[$pluginName]);
    }
    
    public function enabled($pluginName) {
        return in_array($pluginName, $this->enabledPlugins);
    }
    
    public function satisfyDependency($pluginName, $version = null) {
        // Check that we have the plugin
        // Special case for Fossil
        if($pluginName == "fossil") {
            $plugin = array("fossil", $this->core->version);
        } else {
            if(!$this->has($pluginName))
                throw new \Exception("Unsatisfied dependency: $pluginName");
            $plugin = $this->get($pluginName);
        }
        // And next, if a version is provided, compare it
        if($version) {
            if(!isset($plugin['version'])) {
                throw new \Exception("Unsatisfied dependency: $pluginName - need $version");
            }
            if(!version_compare($plugin['version'], $version, '>=')) {
                throw new \Exception("Unsatisfied dependency: $pluginName - need $version, have {$plugin['version']}");
            }
        }
        // Finally, load the plugin if it isn't already
        if($pluginName != "fossil" && !$this->enabled($pluginName))
            $this->enablePlugin($pluginName);
    }
    
    public function enablePlugin($pluginName) {
        if(!$this->has($pluginName))
            throw new \Exception("Plugin not found: $pluginName");
        if($this->enabled($pluginName))
            return;
        
        // Check any dependencies and enable them as necessary
        $plugin = $this->availablePlugins[$pluginName];
        if(isset($plugin['dependencies'])) {
            foreach($plugin['dependencies'] as $dep) {
                if(!isset($dep['version']))
                    $this->satisfyDependency($dep['name']);
                else
                    $this->satisfyDependency($dep['name'], $dep['version']);
            }
        }
        // Finally, store it
        $this->enabledPlugins[] = $pluginName;
        
        // And call it's initializer, if one exists
        $initClass = "Fossil\\Plugins\\" . ucfirst($pluginName) . "\\Init";
        if(class_exists($initClass)) {
            $initInst = new $initClass;
            $initInst->initialize();
        }
    }
    
    public function disablePlugin($pluginName) {
        if(!isset($this->enabledPlugins[$pluginName]))
            return;
        unset($this->enabledPlugins[$pluginName]);
    }
    
    public function loadEnabledPlugins() {
        $plugins = $this->settings->get("Fossil", "plugins", "");
        if($plugins != "") {
            foreach(explode(",", $plugins) as $plugin)
                $this->enablePlugin($plugin);
        }
    }
}

?>
