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

namespace Fossil;

/**
 * Description of Init
 *
 * @author predakanga
 */
class Init extends BaseInit {
    /**
     * @F:Inject("Settings")
     * @var Fossil\Settings
     */
    protected $settings;
    /**
     * @F:Inject(type = "Plugins", lazy = true)
     * @var Fossil\PluginManager
     */
    protected $plugins;
    
    public function registerObjects() {
        if(!$this->settings->isBootstrapped()) {
            return;
        }
        // Look up the chosen drivers for this instance
        $drivers = $this->settings->get("Fossil", "Drivers", array());
        if(isset($drivers['database'])) {
            $this->container->registerType("Database", $drivers['database']['class']);
        }
        if(isset($drivers['cache'])) {
            $this->container->registerType("Cache", $drivers['cache']['class']);
        }
        if(isset($drivers['renderer'])) {
            $this->container->registerType("Renderer", $drivers['renderer']['class']);
        }
        // And load the plugins that are enabled
        $this->plugins->loadEnabledPlugins();
    }
}

?>