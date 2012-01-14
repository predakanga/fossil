<?php

namespace Fossil\Tests;

/**
 * Test class for Compiler.
 * Generated by PHPUnit on 2012-01-13 at 23:14:33.
 */
class CompilerTest extends FossilTestCase {
    /**
     * @var Fossil\Compiler
     */
    protected $object;
    /**
     * @var Fossil\Core
     */
    protected $coreObj;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = self::$container->get("Compiler");
        $this->coreObj = self::$container->get("Core");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }

    /**
     * @covers Fossil\Compiler::transformNamespace
     * @dataProvider dataForDisjunctTransformNamespace
     */
    public function testTransformNamespaceWithDisjunctNamespaces($input, $expected) {
        // Set up the core with disjunct namespaces
        $overlayDetails = array("ns" => 'TestCaseOverlay', "path" => "ignoredPath");
        $appDetails = array("ns" => 'TestCaseApplication', "path" => "ignoredPath");
        $this->coreObj->setOverlayDetails($overlayDetails);
        $this->coreObj->setAppDetails($appDetails);
        
        // Grab a method reflection, since it's protected
        $method = new \ReflectionMethod('Fossil\Compiler', "transformNamespace");
        $method->setAccessible(true);
        
        $result = @$method->invoke($this->object, $input);
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @covers Fossil\Compiler::transformNamespace
     * @dataProvider dataForPartialDisjunctTransformNamespace
     */
    public function testTransformNamespaceWithPartialDisjunctNamespaces($input, $expected) {
        // Set up the core with partially disjunct namespaces
        $overlayDetails = array("ns" => 'TestCaseApplication\SampleOverlay', "path" => "ignoredPath");
        $appDetails = array("ns" => 'TestCaseApplication', "path" => "ignoredPath");
        $this->coreObj->setOverlayDetails($overlayDetails);
        $this->coreObj->setAppDetails($appDetails);
        
        // Grab a method reflection, since it's protected
        $method = new \ReflectionMethod('Fossil\Compiler', "transformNamespace");
        $method->setAccessible(true);
        
        $result = @$method->invoke($this->object, $input);
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @covers Fossil\Compiler::transformNamespace
     * @dataProvider dataForConjunctTransformNamespace
     */
    public function testTransformNamespaceWithConjunctNamespaces($input, $expected) {
        // Set up the core with conjunct namespaces
        $overlayDetails = array("ns" => 'Fossil\SomeOverlay', "path" => "ignoredPath");
        $appDetails = array("ns" => 'Fossil\SomeApplication', "path" => "ignoredPath");
        $this->coreObj->setOverlayDetails($overlayDetails);
        $this->coreObj->setAppDetails($appDetails);
        
        // Grab a method reflection, since it's protected
        $method = new \ReflectionMethod('Fossil\Compiler', "transformNamespace");
        $method->setAccessible(true);
        
        $result = @$method->invoke($this->object, $input);
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @covers Fossil\Compiler::baseClassName
     * @dataProvider dataForBaseClassName
     */
    public function testBaseClassName($input, $expected) {
        $method = new \ReflectionMethod('Fossil\Compiler', "baseClassName");
        $method->setAccessible(true);
        
        $result = $method->invoke($this->object, $input);
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @covers Fossil\Compiler::baseClassName
     * @dataProvider dataForBaseNamespaceName
     */
    public function testBaseNamespaceName($input, $expected) {
        $method = new \ReflectionMethod('Fossil\Compiler', "baseNamespaceName");
        $method->setAccessible(true);
        
        $result = $method->invoke($this->object, $input);
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @covers {className}::{origMethodName}
     * @todo Implement testCompileAllClasses().
     */
    public function testCompileAllClasses() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }
    
    public function dataForDisjunctTransformNamespace() {
        $overlayDetails = array("ns" => 'TestCaseOverlay', "path" => "ignoredPath");
        $appDetails = array("ns" => 'TestCaseApplication', "path" => "ignoredPath");
        
        return array(array('Fossil\ExtendedCompiler', 'Fossil\Compiled\Fossil\ExtendedCompiler'),
                     array('Fossil\SubNS\AnotherClass', 'Fossil\Compiled\Fossil\SubNS\AnotherClass'),
                     array('TestCaseOverlay\OverlayClass', 'Fossil\Compiled\Overlay\OverlayClass'),
                     array('TestCaseApplication\AppClass', 'Fossil\Compiled\App\AppClass'),
                     array('ExternalNS\SomeClass', 'ExternalNS\SomeClass'));
    }
    
    public function dataForPartialDisjunctTransformNamespace() {
        return array(array('Fossil\ExtendedCompiler', 'Fossil\Compiled\Fossil\ExtendedCompiler'),
                     array('Fossil\SubNS\AnotherClass', 'Fossil\Compiled\Fossil\SubNS\AnotherClass'),
                     array('TestCaseApplication\SampleOverlay\OverlayClass', 'Fossil\Compiled\Overlay\OverlayClass'),
                     array('TestCaseApplication\AppClass', 'Fossil\Compiled\App\AppClass'),
                     array('ExternalNS\SomeClass', 'ExternalNS\SomeClass'));
    }
    
    public function dataForConjunctTransformNamespace() {
        return array(array('Fossil\ExtendedCompiler', 'Fossil\Compiled\Fossil\ExtendedCompiler'),
                     array('Fossil\SubNS\AnotherClass', 'Fossil\Compiled\Fossil\SubNS\AnotherClass'),
                     array('Fossil\SomeOverlay\OverlayClass', 'Fossil\Compiled\Overlay\OverlayClass'),
                     array('Fossil\SomeApplication\AppClass', 'Fossil\Compiled\App\AppClass'),
                     array('ExternalNS\SomeClass', 'ExternalNS\SomeClass'));
    }
    
    public function dataForBaseNamespaceName() {
        return array(array('NamespacelessClass', ""),
                     array('\NamespacelessClass', ""),
                     array('Fossil\Compiler', "Fossil"),
                     array('Fossil\SubNS\Some\Other\NS', 'Fossil\SubNS\Some\Other'),
                     array('\A\B\C\D\E\F\G\H\U', 'A\B\C\D\E\F\G\H'),
                     array('', ""));
    }
    
    public function dataForBaseClassName() {
        return array(array('NamespacelessClass', 'NamespacelessClass'),
                     array('\NamespacelessClass', "NamespacelessClass"),
                     array('Fossil\Compiler', "Compiler"),
                     array('Fossil\SubNS\Some\Other\NS', 'NS'),
                     array('\A\B\C\D\E\F\G\H\U', 'U'),
                     array('', ""));
    }
}

?>
