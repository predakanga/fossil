<?php

namespace Fossil\Plugins\Regression;

/**
 * Description of EnhancedCompiler
 *
 * @author predakanga
 * @F:ExtensionClass()
 */
class EnhancedCompiler extends SimpleCompiler {
    /** @F:LogCall() */
    public function someCall($a) {
        return;
    }

    /** @F:TimeCall() */
    public function compileAll() {
        return parent::compileAll();
    }
}

?>
