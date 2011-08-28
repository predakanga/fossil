<?php

namespace Fossil\Renderers;

use Fossil\Interfaces\IDriver;

/**
 * Description of BaseRenderer
 *
 * @author predakanga
 */
abstract class BaseRenderer implements IDriver {
    abstract public function render($templateName, $templateData);
}

?>
