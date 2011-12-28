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

use TokenReflection\Broker,
    Fossil\Util\FilesystemScanner;

/**
 * Description of ReflectionBroker
 *
 * @author predakanga
 * @F:Provides("Reflection")
 * @F:DefaultProvider
 */
class ReflectionBroker extends Object {
    /**
     * @F:Inject("Filesystem")
     * @var Fossil\Filesystem
     */
    protected $fs;
    /** @var TokenReflection\Broker */
    protected $broker;
    
    public function __construct($container) {
        parent::__construct($container);
        
        $this->broker = new Broker(new \TokenReflection\Broker\Backend\Memory());
        $this->rescan();
    }
    
    protected function determineObjects() {
        // As a special case, we don't do auto-discovery on this object
        return array(array('type' => 'Filesystem', 'destination' => 'fs',
                           'required' => true, 'lazy' => true));
    }
    
    public function rescan() {
        // If we're bootstrapping the container, just return the current file's dir
        if(!$this->fs->_isReady()) {
            foreach(FilesystemScanner::sourceFiles(__DIR__) as $sourceFile) {
                $this->broker->processFile($sourceFile);
            }
        } else {
            foreach($this->fs->allSourceFiles() as $sourceFile) {
                $this->broker->processFile($sourceFile);
            }
        }
    }
    
    public function getAllClasses() {
        return $this->broker->getClasses();
    }
    
    public function getClass($className) {
        return $this->broker->getClass($className);
    }
    
    public function getNamespace($namespaceName) {
        return $this->broker->getNamespace($namespaceName);
    }
    
    public function scanFile($filename) {
        $this->broker->processFile($filename);
    }
}

?>
