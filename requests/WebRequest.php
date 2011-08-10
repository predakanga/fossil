<?php

namespace Fossil\Requests;

/**
 * Description of WebRequest
 *
 * @author lachlan
 */
class WebRequest extends BaseRequest {
    public function __construct() {
        if(isset($_REQUEST['controller']))
            $this->controller = $_REQUEST['controller'];
        if(isset($_REQUEST['action']))
            $this->action = $_REQUEST['action'];
        $this->args = array();
        foreach($_REQUEST as $key => $value) {
            if($key == 'module' || $key == 'action')
                continue;
            $this->args[$key] = $value;
        }
    }
}

?>
