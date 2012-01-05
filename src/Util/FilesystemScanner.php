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

namespace Fossil\Util;

/**
 * Description of FilesystemScanner
 *
 * @author predakanga
 */
class FilesystemScanner {
    public static function sourceFiles($root, $includePlugins = false) {
        $dirIter = new \RecursiveDirectoryIterator($root);
        $filterIter = new SourceDirectoryFilter($dirIter, array($root), $includePlugins);
        $iterIter = new \RecursiveIteratorIterator($filterIter);
        $regexIter = new \RegexIterator($iterIter, '/\\.php$/');
        
        return array_map(function($fileInfo) {
            return $fileInfo->getPathname();
        }, iterator_to_array($regexIter, false));
    }
}

class SourceDirectoryFilter extends \RecursiveFilterIterator {
    public static $DIR_FILTERS = array('.git',
                                       'libs',
                                       'Compiled',
                                       'templates_c',
                                       'tests',
                                       'scratch');

    public static $FILE_FILTERS = array('cli-config.php');
    
    public static $ROOT_FILE_FILTERS = array('index.php',
                                             'quickstart.php');
    
    protected $roots;
    protected $usedDirFilters = array();
    
    public function __construct($iter, $roots = array(), $includePlugins = false) {
        $this->roots = $roots;
        $this->usedDirFilters = self::$DIR_FILTERS;
        if(!$includePlugins) {
            $usedDirFilters[] = "Plugins";
        }
        parent::__construct($iter);
    }
    
    public function accept() {
        if($this->current()->isDir())
            return !in_array($this->current()->getFilename(),
                             $this->usedDirFilters,
                             true);
        else {
            if(in_array($this->current()->getFilename(),
                        self::$FILE_FILTERS,
                        true))
                return false;
            elseif(in_array($this->current()->getFilename(),
                            self::$ROOT_FILE_FILTERS, true)
                    && in_array($this->current()->getPath(),
                            $this->roots, true))
                return false;
        }
        return true;
    }
}

?>
