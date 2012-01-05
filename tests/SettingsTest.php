<?php

namespace Fossil\Tests;

require_once 'vfsStream/vfsStream.php';
require_once 'vfsStreamPermissionsVisitor.php';
require_once "mocks/MockContainer.php";

use Fossil\Settings;

/**
 * Test class for Settings.
 * Generated by PHPUnit on 2011-09-10 at 17:21:42.
 */
class SettingsTest extends FossilTestCase {
    /**
     * @var Fossil\Settings
     */
    protected $object;
    /**
     * @var Fossil\ORM
     */
    protected $orm;
    
    public static function freshenVirtualFilesystem() {
        // Copy data in from fixture
        \vfsStream::copyFromFileSystem(__DIR__ . DIRECTORY_SEPARATOR . "fixtures" . DIRECTORY_SEPARATOR . "SettingsTest", self::$vfsRoot);
        // And fix broken permissions
        \vfsStream::inspect(new \vfsStreamPermissionsVisitor());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        self::freshenVirtualFilesystem();
        $this->object = self::$container->get("Settings");
        $this->orm = self::$container->get("ORM");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }

    /**
     * @covers Fossil\Settings::__destruct
     */
    public function test__destruct() {
        $origHash = md5_file(\vfsStream::url("sampleSettings.yml"));
        // Test destruction with no changes doesn't change mtime
        $sampleSet = new Settings(self::$container, \vfsStream::url("sampleSettings.yml"));
        $sampleSet->__destruct();
        $sampleSet = null;
        $this->assertEquals($origHash, md5_file(\vfsStream::url("sampleSettings.yml")));
        // Test destruction with changes to sections other than fossil doesn't change mtime
        $sampleSet = new Settings(self::$container, \vfsStream::url("sampleSettings.yml"));
        $sampleSet->set("test__destruct", "someName", "someValue");
        $sampleSet->__destruct();
        $sampleSet = null;
        $this->assertEquals($origHash, md5_file(\vfsStream::url("sampleSettings.yml")));
        // Test destruction with changes to fossil section does change mtime
        $sampleSet = new Settings(self::$container, \vfsStream::url("sampleSettings.yml"));
        $sampleSet->set("Fossil", "someOtherName", "someOtherValue");
        $sampleSet->__destruct();
        $sampleSet = null;
        $this->assertNotEquals($origHash, md5_file(\vfsStream::url("sampleSettings.yml")));
    }

    /**
     * @covers Fossil\Settings::loadCoreSettings
     */
    public function testLoadCoreSettings() {
        $runStub = $this->getMockBuilder('Fossil\Settings')->disableOriginalConstructor()->setMethods(array('loadSectionSettings'))->getMock();
        $runStub->expects($this->once())
                ->method('loadSectionSettings')
                ->with($this->equalTo("Fossil"));
        $runStub->loadCoreSettings();
    }
    
    /**
     * @covers Fossil\Settings::__construct
     * @covers Fossil\Settings::isBootstrapped
     */
    public function testConstructionAndIsBootstrapped() {
        // Test that the default settings (basic, for unit testing) are not bootstrapped
        $this->assertTrue($this->object->isBootstrapped());
        // And recreate it, to have code coverage for the default case
        $defaultSet = new Settings(self::$container);
        $this->assertTrue($defaultSet->isBootstrapped());
        // Test that the sample settings are bootstrapped
        $sampleSet = new Settings(self::$container, \vfsStream::url("sampleSettings.yml"));
        $this->assertTrue($sampleSet->isBootstrapped());
        // And that the empty settings are not bootstrapped
        $emptySet = new Settings(self::$container, \vfsStream::url("emptySettings.yml"));
        $this->assertFalse($emptySet->isBootstrapped());
        // And that non-existant settings are not bootstrapped
        $nonExtSet = new Settings(self::$container, \vfsStream::url("nonExistantSettings.yml"));
        $this->assertFalse($nonExtSet->isBootstrapped());
    }

    /**
     * @covers Fossil\Settings::get
     */
    public function testGetReturnsDefault() {
        $this->object->set("testGet", "hasValue", "something");
        
        $this->assertNotEquals("nothing", $this->object->get("testGet", "hasValue", "nothing"));
        $this->assertEquals("nothing", $this->object->get("testGet", "doesntHaveValue", "nothing"));
    }

    /**
     * @dataProvider dataForSetAndGet
     * @covers Fossil\Settings::get
     * @covers Fossil\Settings::set
     * @covers Fossil\Settings::dbValueToValue
     * @covers Fossil\Settings::valueToDbValue
     * @covers Fossil\Settings::loadSectionSettings
     */
    public function testSetAndGet($testName, $testVal) {
        $this->object->set("testSetAndGet", $testName, $testVal);
        $this->orm->flush();

        $newSettings = new Settings(self::$container);
        $this->assertEquals($testVal, $this->object->get("testSetAndGet", $testName));
        $this->assertEquals($testVal, $newSettings->get("testSetAndGet", $testName));
    }
    
    public function dataForSetAndGet() {
        return array(array("dataset1", "simple string"),
                     array("dataset2", 43),
                     array("dataset3", 73.2451),
                     array("dataset4", true),
                     array("dataset4", false),
                     array("dataset4", "overwriting test"),
                     array("dataset5", array("Complex", 32)),
                     array("dataset6", array("Simple" => "Dict")),
                     array("dataset7", new \stdClass()),
                     array("dataset8", str_repeat("9", 540)));
    }

}
