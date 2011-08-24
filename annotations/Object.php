<?php

namespace Fossil\Annotations;

/**
 * Object annotation
 *
 * @author predakanga
 */
class Object extends Annotation {
    public $type;
	public $name = "default";
    public $takesContext = false;
}

?>
