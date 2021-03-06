<?php

namespace Fossil\Tests;

/**
 * Test class for Dispatcher.
 * Generated by PHPUnit on 2011-12-16 at 23:27:24.
 */
class DispatcherTest extends FossilTestCase {
    /**
     * @var Fossil\Dispatcher
     */
    protected $object;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = self::$container->get("Dispatcher");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }

    /**
     * @covers Fossil\Dispatcher::runRequest
     * @covers Fossil\Dispatcher::_run
     */
    public function testRunRequest() {
        // First, test that run is called
        $runStub = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $runStub->expects($this->once())
                ->method('run');
        
        $this->object->runRequest($runStub, false);
    }
    
    /**
     * @covers Fossil\Dispatcher::runRequest
     * @covers Fossil\Dispatcher::_run
     * @covers Fossil\Dispatcher::handleRequestException
     */
    public function testRunRequest404Exception() {
        $exception = new \Fossil\Exceptions\NoSuchControllerException("Test");
        
        $exceptStub = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $exceptStub->expects($this->any())
                ->method('run')
                ->will($this->throwException($exception));
        
        $resp = $this->object->runRequest($exceptStub, false);
        
        $this->assertEquals("fossil:error/404", $resp->getTemplateName());
    }
    
    /**
     * @covers Fossil\Dispatcher::runRequest
     * @covers Fossil\Dispatcher::_run
     * @covers Fossil\Dispatcher::handleRequestException
     */
    public function testRunRequestDBException() {
        $exception = new \PDOException("Test");
        
        $exceptStub = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $exceptStub->expects($this->any())
                ->method('run')
                ->will($this->throwException($exception));
        
        $resp = $this->object->runRequest($exceptStub, false);
        
        $this->assertEquals("fossil:error/db", $resp->getTemplateName());
        
    }

    /**
     * @covers Fossil\Dispatcher::runRequest
     * @covers Fossil\Dispatcher::_run
     * @covers Fossil\Dispatcher::handleRequestException
     */
    public function testRunRequestGenericException() {
        $exception = new \Exception("Test");
        
        $exceptStub = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $exceptStub->expects($this->any())
                ->method('run')
                ->will($this->throwException($exception));
        
        $resp = $this->object->runRequest($exceptStub, false);
        
        $this->assertEquals("fossil:error/generic", $resp->getTemplateName());
    }
    
    /**
     * @covers Fossil\Dispatcher::runRequest
     * @covers Fossil\Dispatcher::_run
     * @covers Fossil\Dispatcher::handleRequestException
     */
    public function testRunRequestLogsException() {
        $exception = new \Exception("Test");
        
        $exceptStub = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $exceptStub->expects($this->any())
                ->method('run')
                ->will($this->throwException($exception));
        
        $errorMgr = self::$container->get("ErrorManager");
        $errorLog = $errorMgr->getLog();
        $errorCount = count($errorLog['exceptions']);
        // Ensure that the exception count only increases by one
        $resp = $this->object->runRequest($exceptStub, false);
        $errorLog = $errorMgr->getLog();
        $this->assertEquals($errorCount+1, count($errorLog['exceptions']));
    }
    
    protected function getMockRequest($payloadCB) {
        $dispatcher = $this->object;
        
        $requestCB = function() use($payloadCB) {
            return $payloadCB();
        };
        
        $requestMock = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->any())
                    ->method('run')
                    ->will($this->returnCallback($requestCB));
        return $requestMock;
    }
    
    /**
     * @covers Fossil\Dispatcher::runRequest
     * @covers Fossil\Dispatcher::_run
     * @covers Fossil\Dispatcher::getTopRequest
     */
    public function testGetTopRequest() {
        // TODO: Add some layers of requests, to actually stress the stack
        $obj = $this->object;
        
        // Use a closure to return the current request during the call
        $gtrCB = function() use($obj) {
            return $obj->getTopRequest();
        };
        $runStub = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $runStub->expects($this->any())
                ->method('run')
                ->will($this->returnCallback($gtrCB));
        
        $result = $this->object->runRequest($runStub, false);
        $this->assertEquals($runStub, $result);
    }

    /**
     * @covers Fossil\Dispatcher::runRequest
     * @covers Fossil\Dispatcher::_run
     * @covers Fossil\Dispatcher::getCurrentRequest
     */
    public function testGetCurrentRequest() {
        // TODO: Add some layers of requests, to actually stress the stack
        $obj = $this->object;
        
        // Use a closure to return the current request during the call
        $gcrCB = function() use($obj) {
            return $obj->getCurrentRequest();
        };
        $runStub = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $runStub->expects($this->any())
                ->method('run')
                ->will($this->returnCallback($gcrCB));
        
        $result = $this->object->runRequest($runStub, false);
        $this->assertEquals($runStub, $result);
    }

    /**
     * @covers Fossil\Dispatcher::runRequest
     * @covers Fossil\Dispatcher::_run
     */
    public function testRendersRenderableResponse() {
        $responseStub = $this->getMockBuilder('Fossil\Responses\RenderableResponse')->disableOriginalConstructor()->getMock();
        $responseStub->expects($this->once())
                     ->method('render');
        
        $reqStub = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $reqStub->expects($this->any())
                ->method('run')
                ->will($this->returnValue($responseStub));
        
        $this->object->runRequest($reqStub);
    }
    
    /**
     * @covers Fossil\Dispatcher::runRequest
     * @covers Fossil\Dispatcher::_run
     */
    public function testActionsActionableResponse() {
        $responseStub = $this->getMockBuilder('Fossil\Responses\ActionableResponse')->disableOriginalConstructor()->getMock();
        $responseStub->expects($this->once())
                     ->method('runAction');
        
        $reqStub = $this->getMockBuilder('Fossil\Requests\BaseRequest')->disableOriginalConstructor()->getMock();
        $reqStub->expects($this->any())
                ->method('run')
                ->will($this->returnValue($responseStub));
        
        $this->object->runRequest($reqStub);
    }
}
