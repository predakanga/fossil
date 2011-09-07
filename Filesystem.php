<?php
/**
 * @author predakanga
 * @since 0.1
 * @package Fossil
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
                                       'scratch');
    // Use of require_once filters index.php, so this is all we need to worry about
    public static $FILE_FILTERS = array('cli-config.php');
	
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
    /**
     * 
     * @return array List of roots in which to look for classes, templates, etc
     */
    public function roots() {
        return array_merge(array($this->fossilRoot()), $this->pluginRoots());
    }
    
    public function fossilRoot() {
        return __DIR__;
    }
    
    public function overlayRoot() {
        return null;
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
