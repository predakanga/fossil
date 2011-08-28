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
}

?>
