<?php

use PayPal\Rest\ApiContext;

/**
 * Test class for ApiContextTest.
 *
 */
class ApiContextTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var ApiContext
     */
    public $apiContext;

    public function setUp()
    {
        $this->apiContext = new ApiContext();
    }

    public function testGetRequestId()
    {
        $requestId = $this->apiContext->getRequestId();
        $this->assertNull($requestId);
    }

    public function testSetRequestId()
    {
        $this->assertNull($this->apiContext->getRequestId());

        $expectedRequestId = 'random-value';
        $this->apiContext->setRequestId($expectedRequestId);
        $requestId = $this->apiContext->getRequestId();
        $this->assertEquals($expectedRequestId, $requestId);
    }

    public function testResetRequestId()
    {
        $this->assertNull($this->apiContext->getRequestId());

        $requestId = $this->apiContext->resetRequestId();
        $this->assertNotNull($requestId);

        // Tests that another resetRequestId call will generate a new ID
        $newRequestId = $this->apiContext->resetRequestId();
        $this->assertNotNull($newRequestId);
        $this->assertNotEquals($newRequestId, $requestId);
    }
}
