<?php

namespace Fossil\Annotations;

class LogCall extends Compilation {
    public function call($funcname, $args) {
        print "$funcname called on " . get_class($this) . "\n";
        
        return $this->completeCall($funcname, $args);
    }
}

?>
