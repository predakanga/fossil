<?php

if (class_exists('PHP_CodeSniffer_Standards_AbstractPatternSniff', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractPatternSniff not found');
}

class FossilCodingStandard_Sniffs_ControlStructures_ControlSignatureSniff extends PHP_CodeSniffer_Standards_AbstractPatternSniff {
    public function __construct() {
        parent::__construct(true);
    }
    
    protected function getPatterns() {
        return array('do {EOL..} while(...);EOL',
                     'while(...) {EOL',
                     'for(...) {EOL',
                     'if(...) {EOL',
                     'foreach(...) {EOL',
                     '} else if(...) {EOL',
                     '} elseif(...) {EOL',
                     '} else {EOL',
                     'do {EOL');
    }
}
