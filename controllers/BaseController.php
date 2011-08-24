<?php

namespace Fossil\Controllers;

/**
 * Description of BaseController
 *
 * @author predakanga
 */
abstract class BaseController {
    public abstract function run(\Fossil\Requests\BaseRequest $req);
}

?>
