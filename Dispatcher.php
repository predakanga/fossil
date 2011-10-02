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

use Fossil\Requests\BaseRequest,
    Fossil\Responses\RenderableResponse,
    Fossil\Responses\ActionableResponse,
    Fossil\Exceptions\NoSuchTargetException;

/**
 * Description of Dispatcher
 *
 * @author predakanga
 * 
 * @F:Object("Dispatcher")
 */
class Dispatcher {
    private $reqStack = array();
    
    public function runRequest(BaseRequest $req, $react = true) {
        array_push($this->reqStack, $req);
        
        ob_start();
        try {
            $response = $this->_run($req, $react);
        } catch(\Exception $e) {
            $this->handleRequestException($e, $req, $react);
        }
        ob_end_flush();
        
        array_pop($this->reqStack);
        return $response;
    }
    
    protected function handleRequestException(\Exception $e, BaseRequest $req, $react) {
        // Handle 404 errors
        if($e instanceof NoSuchTargetException) {
            $fourohfourReq = OM::obj("Requests", "InternalRequest")->create("error", "404");
            ob_clean();
            $this->_run($fourohfourReq);
        } else {
            // Base request handling - provides nothing useful
            $errorReq = OM::obj("Requests", "InternalRequest")->create("error", "show", array('e' => $e));
            ob_clean();
            $this->_run($errorReq);
        }
    }
    
    protected function _run(BaseRequest $req, $react = true) {
        // To allow HMVC style requests, return the response early if we're not to react
        $response = $req->run();

        if(!$react)
            return $response;

        if($response instanceof RenderableResponse) {
            $response->render();
        } else if($response instanceof ActionableResponse) {
            $response->runAction();
        }
    }
    
    public function getTopRequest() {
        return $this->reqStack[0];
    }
    
    public function getCurrentRequest() {
        return $this->reqStack[count($this->reqStack)-1];
    }
}

?>
