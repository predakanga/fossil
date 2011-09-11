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

if(!defined('D_S'))
    define('D_S', DIRECTORY_SEPARATOR);

class SourceDirectoryFilter extends \RecursiveFilterIterator {
	public static $DIR_FILTERS = array('.git',
                                       'libs',
                                       'plugins',
                                       'compiled',
                                       'templates_c',
                                       'tests',
                                       'scratch');
    // Use of require_once filters index.php, so this is all we need to worry about
    public static $FILE_FILTERS = array('cli-config.php',
                                        'index.php',
                                        'quickstart.php');
	
	public function accept() {
		if($this->current()->isDir())
            return !in_array($this->current()->getFilename(),
                             self::$DIR_FILTERS,
                             true);
        else
            return !in_array($this->current()->getFilename(),
                             self::$FILE_FILTERS,
                             true);
	}
}
/**
 * Filesystem Helper
 * 
 * Provides functions to get provide access to a virtual filesystem
 * as defined by the various loaded plugins
 *
 * @author predakanga
 * @since 0.1
 */
class Filesystem {
    protected $overlayRoot;
    protected $appRoot;
    protected $tempDir;
    
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
        if(!$this->overlayRoot === 0) {
            $overlayRoot = $this->execDir();
            if($overlayRoot == $this->fossilRoot() || $overlayRoot == $this->appRoot())
                $this->overlayRoot = null;
            else
                $this->overlayRoot = $overlayRoot;
        }
        return $this->overlayRoot;
    }
    
    public function tempDir() {
        if(!$this->tempDir) {
            $tempDir = OM::Settings("Fossil", "temp_dir", sys_get_temp_dir());
            $tempDir .= D_S . "Fossil";
            if(OM::appNamespace())
                $tempDir .= "_" . basename(OM::appNamespace());
            if(OM::overlayNamespace())
                $tempDir .= "_" . basename(OM::overlayNamespace());
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
        return dirname($_SERVER['SCRIPT_FILENAME']);
    }
    
    public function pluginRoots() {
        $toRet = array();
        
        if(!OM::knows("Plugins"))
            return $toRet;
        $enabledPlugins = OM::Plugins()->getEnabledPlugins();
        foreach($enabledPlugins as $pluginName) {
            $plugin = OM::Plugins($pluginName);
            $toRet[] = $plugin['root'];
        }
        return $toRet;
    }
    
    public function sourceFiles($root) {
        $dirIter = new \RecursiveDirectoryIterator($root);
        $filterIter = new SourceDirectoryFilter($dirIter);
        $iterIter = new \RecursiveIteratorIterator($filterIter);
        $regexIter = new \RegexIterator($iterIter, '/\\.php$/');
        
        return array_map(function($fileInfo) {
            return $fileInfo->getPathname();
        }, iterator_to_array($regexIter, false));
    }
    
    public function allSourceFiles() {
        $sourceFiles = array();
        
        foreach($this->roots() as $root) {
            $sourceFiles = array_merge($sourceFiles, $this->sourceFiles($root));
        }
        return $sourceFiles;
    }
}

?>
