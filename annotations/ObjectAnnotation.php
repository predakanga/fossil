<?php

namespace Fossil\Annotations;

/**
 * @F:Namespace("F")
 * @F:Alias("Object")
 */
final class ObjectAnnotation extends Annotation {
    public $type;
	public $name = "default";
    public $takesContext = false;
}

?>