<?php

namespace Fossil;

use Fossil\Requests\BaseRequest,
    Fossil\Responses\RenderableResponse,
    Fossil\Responses\ActiunableResponse;

/**
 * Description of Dispatcher
 *
 * @author lachlan
 * 
 * @F:Object("Dispatcher")
 */
class Dispatcher {
    private $topReq;
    
    public function runRequest(BaseRequest $req, $react = true) {
        if($this->topReq === NULL)
            $this->topReq = $req;
        
        // To allow HMVC style requests, return the response early if we're not to react
        $response = $req->run();
        
        if(!$react)
            return $response;
        
        if($response instanceof RenderableResponse) {
            $response->render();
        } else if($response instanceof ActionableResponse) {
            $response->runAction();
        }
        
        return $response;
    }
    
    public function getTopRequest() {
        return $this->topReq;
    }
}

?>
