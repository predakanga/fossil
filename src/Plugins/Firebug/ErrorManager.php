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
 * Description of ErrorManager
 *
 * @author predakanga
 */
class ErrorManager extends \Fossil\ErrorManager {
    /**
     * @var FireFossil
     */
    protected $firephp;
    
    public function setFirephp($firephp) {
        $this->firephp = $firephp;
        $this->firephp->registerExceptionHandler();
    }
    
    public function logException($exception) {
        $this->firephp->error($exception, "Unhandled exception");
    }
    
    public function logError($error) {
        if(!is_array($error)) {
            $this->firephp->error($error);
        } else {
            $this->firephp->error($error, "Error occurred: " . $error['errstr']);
        }
    }
    
    public function logWarning($warning) {
        die("Handling warning\n");
        if(!is_array($warning)) {
            $this->firephp->warn($warning);
        } else {
            $this->firephp->warn($warning, "Warning occurred: " . $warning['errstr']);
        }
    }
    
    public function logInfo($info) {
        if(!is_array($info)) {
            $this->firephp->info($info);
        } else {
            $this->firephp->info($info, "Notice occurred: " . $info['errstr']);
        }
    }
    
    public function logHandledException(\Exception $exception) {
        $this->firephp->info($exception, "Handled exception");
    }
}

?>
