<?php

namespace Fossil\Controllers;

use Fossil\Exceptions\NoSuchActionException;

/**
 * Description of AutoController
 *
 * @author predakanga
 */
abstract class AutoController extends BaseController {
    public function run(\Fossil\Requests\BaseRequest $req) {
        // Decide what action to use
        $action = $req->action ?: $this->indexAction();
        // Compute the method name
        $actionMethod = "run" . ucfirst(strtolower($action));
        
        if(!method_exists($this, $actionMethod))
            throw new NoSuchActionException($req->controller, $action);
        
        // And try to call it on ourselves
        return call_user_func(array($this, $actionMethod), $req);
    }
    
    public function indexAction() {
        return "index";
    }
}

?>
