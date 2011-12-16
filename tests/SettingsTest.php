<?php

namespace Fossil;

/**
 * Test class for Settings.
 * Generated by PHPUnit on 2011-09-10 at 17:21:42.
 */
class SettingsTest extends \PHPUnit_Framework_TestCase {
    /** @var Fossil\ObjectContainer */
    protected static $container;
    /**
     * @var Fossil\Settings
     */
    protected $object;

    public static function setUpBeforeClass() {
        self::$container = new ObjectContainer();
    }
    
    public static function tearDownAfterClass() {
        self::$container = null;
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = self::$container->get("Settings");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }

    /**
     * @todo Implement test__destruct().
     */
    public function test__destruct() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testBootstrapped().
     */
    public function testIsBootstrapped() {
        $this->assertFalse($this->object->isBootstrapped());
    }

    /**
     * @todo Implement testGet().
     */
    public function testGet() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testSet().
     */
    public function testSet() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

}

?>
