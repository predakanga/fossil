<?php

namespace Fossil\Annotations;

/**
 * Object annotation
 *
 * @author lachlan
 */
class Object extends Annotation {
    public $type;
	public $name = "default";
    public $takesContext = false;
}

?>
