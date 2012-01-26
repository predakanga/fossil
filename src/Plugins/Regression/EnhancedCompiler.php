<?php

namespace Fossil\Plugins\Regression;

use Fossil\Annotations\Compilation\LogCall,
    Fossil\Annotations\Compilation\TimeCall;

/**
 * Description of EnhancedCompiler
 *
 * @author predakanga
 * @F:ExtensionClass()
 */
class EnhancedCompiler extends SimpleCompiler {
    /** @LogCall() */
    public function someCall($a) {
        return;
    }

    /** @TimeCall() */
    public function compileAll() {
        return parent::compileAll();
    }
}
