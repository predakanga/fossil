<?php
/**
 * @author predakanga
 * @since 0.1
 * @package Fossil
 */

namespace Fossil;

/**
 * Filesystem Helper
 * 
 * Provides functions to get provide access to a virtual filesystem
 * as defined by the various loaded plugins
 *
 * @author predakanga
 * @since 0.1
 */
class Filesystem {
    /**
     * 
     * @return array List of roots in which to look for classes, templates, etc
     */
    public function roots() {
        return array(dirname(__FILE__));
    }
}

?>
