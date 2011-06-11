<?php

/**
 * Implements extensions to Addendum to allow for the
 * definition and use of annotations to be accessed
 * by a namespaced/aliased identifier
 * 
 * @author predakanga
 * @since 0.1
 */
namespace Fossil;

require_once("lib/addendum/annotations.php");

class Addendum_PlusPlus extends \Addendum {
	private $aliases;
	private $namespaces;
	
	/**
	 * Override to 
	 * Enter description here ...
	 * @param unknown_type $class
	 */
	public static function resolveClassName($class) {
	}
}

?>