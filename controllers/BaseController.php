<?php

namespace Fossil\Controllers;

/**
 * Description of BaseController
 *
 * @author predakanga
 * @F:Instanced
 */
abstract class BaseController {
    public abstract function run(\Fossil\Requests\BaseRequest $req);
}

?>
