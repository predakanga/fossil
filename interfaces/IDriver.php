<?php

namespace Fossil\Interfaces;

/**
 *
 * @author predakanga
 */
interface IDriver {
    static function usable();
    static function getName();
    static function getVersion();
    static function getForm();
    
    function getConfig();
    function __construct($config);
}

?>
