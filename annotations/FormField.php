<?php

namespace Fossil\Annotations;

/**
 * Description of FormField
 *
 * @author predakanga
 */
class FormField extends Annotation {
    public $type = "text";
    public $fieldName = null;
    public $options = "";
    public $label = null;
    public $default = null;
}

?>
