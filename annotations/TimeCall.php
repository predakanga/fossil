<?php

namespace Fossil\Annotations;

class TimeCall extends Compilation {
    public function call($funcname, $args) {
        $start = microtime(true);
        $retval = $this->completeCall($funcname, $args);
        $end = microtime(true);
        
        echo "$funcname on " . get_class($this) . " took " . ($end-$start) . "\n";
        return $retval;
    }
}

?>
