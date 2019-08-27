<?php

namespace PayPal\Test\Api;

use PayPal\Api\RefundRequest;

/**
 * Class RefundRequest
 *
 * @package PayPal\Test\Api
 */
class RefundRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Gets Json String of Object RefundRequest
     * @return string
     */
    public static function getJson()
    {
        return '{"amount":' .AmountTest::getJson() . ',"description":"TestSample","refund_type":"TestSample","refund_source":"TestSample","reason":"TestSample","invoice_number":"TestSample","refund_advice":true,"is_non_platform_transaction":"TestSample"}';
    }

    /**
     * Gets Object Instance with Json data filled in
     * @return RefundRequest
     */
    public static function getObject()
    {
        return new RefundRequest(self::getJson());
    }


    /**
     * Tests for Serialization and Deserialization Issues
     * @return RefundRequest
     */
    public function testSerializationDeserialization()
    {
        $obj = new RefundRequest(self::getJson());
        $this->assertNotNull($obj);
        $this->assertNotNull($obj->getAmount());
        $this->assertNotNull($obj->getDescription());
        $this->assertNotNull($obj->getRefundSource());
        $this->assertNotNull($obj->getReason());
        $this->assertNotNull($obj->getInvoiceNumber());
        $this->assertNotNull($obj->getRefundAdvice());
        $this->assertEquals(self::getJson(), $obj->toJson());
        return $obj;
    }

    /**
     * @depends testSerializationDeserialization
     * @param RefundRequest $obj
     */
    public function testGetters($obj)
    {
        $this->assertEquals($obj->getAmount(), AmountTest::getObject());
        $this->assertEquals($obj->getDescription(), "TestSample");
        $this->assertEquals($obj->getRefundSource(), "TestSample");
        $this->assertEquals($obj->getReason(), "TestSample");
        $this->assertEquals($obj->getInvoiceNumber(), "TestSample");
        $this->assertEquals($obj->getRefundAdvice(), true);
    }


}
