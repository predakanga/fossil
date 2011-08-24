<?php

namespace Fossil\Controllers;

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
        // And try to call it on ourselves
        return call_user_func(array($this, $actionMethod), $req);
    }
    
    abstract public function indexAction();
}

?>
