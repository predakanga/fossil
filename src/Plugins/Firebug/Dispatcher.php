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

namespace Fossil\Plugins\Firebug;

/**
 * Description of Dispatcher
 *
 * @author predakanga
 * @F:ExtensionClass
 */
class Dispatcher extends \Fossil\Dispatcher {
    protected $firephp;
    
    public function setFirephp($firephp) {
        $this->firephp = $firephp;
    }
    
    public function run() {
        // Set up an output buffer
        ob_start();
        // Run the normal dispatcher
        parent::run();
        // Send our FirePHP headers
        if($this->firephp && $this->firephp->isActive()) {
            // Output the time taken/memory usage info
            $core = $this->container->get("Core");
            $deltaTime = microtime(TRUE) - $core->startTime;
            $deltaMem = memory_get_usage() - $core->startMem;
            $maxDeltaMem = memory_get_peak_usage() - $core->startMem;
            $deltaTime *= 1000;
            $deltaMem /= (1024*1024);
            $maxDeltaMem /= (1024*1024);
            $messageFmt = "Finished in %0.4f ms, memory usage was %0.2f MB (max was %0.2f MB)";
            $this->firephp->info(sprintf($messageFmt, $deltaTime, $deltaMem, $maxDeltaMem));
            // Output the SQL log
            $this->container->get("ORM")->getLogger()->printTable();
            // And flush to the browser
            $this->firephp->sendHeaders();
            // Then un-register FireFossil
            restore_exception_handler();
        }
        // And flush the output buffer
        ob_end_flush();
    }
}

?>
