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

use Fossil\Models\Setting;

/**
 * Description of Settings
 *
 * @author predakanga
 * @F:Provides("Settings")
 * @F:DefaultProvider
 */
class Settings extends Object {
    private $store = array();
    private $fossilHash = "";
    private $backingFile = "";
    /**
     * @F:Inject(type = "ORM", lazy = true)
     * @var Fossil\ORM
     */
    protected $orm;
    /**
     * @F:Inject("Filesystem")
     * @var Fossil\Filesystem 
     */
    protected $fs;
    
    public function __construct($container) {
        parent::__construct($container);
        
        $this->backingFile = $this->fs->execDir() . D_S . "settings.yml";
        if(file_exists($backingFile)) {
            $this->store['Fossil'] = yaml_parse_file($backingFile);
            $this->fossilHash = md5(serialize($this->store['Fossil']));
        }
    }
    
    public function __destruct() {
        if(isset($this->store['Fossil'])) {
            $fossilHash = md5(serialize($this->store['Fossil']));
            if($fossilHash != $this->fossilHash)
                file_put_contents($this->backingFile, yaml_emit($this->store['Fossil']));
        }
    }
    
    public function isBootstrapped() {
        if(!isset($this->store['Fossil']))
            return false;
        return true;
    }
    
    public function loadCoreSettings() {
        $this->loadSectionSettings("Fossil");
    }
    
    protected function loadSectionSettings($section) {
        if(!$this->orm->_isReady())
            return;
        $settings = Setting::findBySection($this->orm, $section);
        if(!isset($this->store[$section]))
            $this->store[$section] = array();
        foreach($settings as $setting) {
            $this->store[$section][$setting->name] = $setting->value;
        }
    }
    
    public function get($section, $setting, $default = null) {
        if(!isset($this->store[$section]))
            $this->loadSectionSettings($section);
        if(isset($this->store[$section][$setting]))
            return $this->store[$section][$setting];
        return $default;
    }
    
    public function set($section, $setting, $value) {
        if(!isset($this->store[$section]))
            $this->loadSectionSettings($section);
        $this->store[$section][$setting] = $value;
        // Persist to the DB as well
        $settingModel = Setting::findOneBy($this->orm, array('section' => $section, 'name' => $setting));
        if(!$settingModel) {
            $settingModel = new Setting($this->container);
            $settingModel->section = $section;
            $settingModel->name = $setting;
            $settingModel->save();
        }
        $settingModel->value = $value;
    }
}

?>
