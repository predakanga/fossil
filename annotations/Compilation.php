<?php

namespace Fossil\Annotations;

abstract class Compilation extends Annotation {
    protected function completeCall($funcname, $args) {}
    abstract public function call($funcname, $args);
}

?>
