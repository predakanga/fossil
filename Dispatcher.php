<?php

namespace Fossil;

use Fossil\Requests\BaseRequest,
    Fossil\Responses\RenderableResponse,
    Fossil\Responses\ActionableResponse;

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
        
        // To allow HMVC style requests, return the response early if we're not to react
        $response = $req->run();
        
        if(!$react)
            return $response;
        
        if($response instanceof RenderableResponse) {
            $response->render();
        } else if($response instanceof ActionableResponse) {
            $response->runAction();
        }
        
        array_pop($this->reqStack);
        return $response;
    }
    
    public function getTopRequest() {
        return $this->reqStack[0];
    }
    
    public function getCurrentRequest() {
        return $this->reqStack[count($this->reqStack)-1];
    }
}

?>
