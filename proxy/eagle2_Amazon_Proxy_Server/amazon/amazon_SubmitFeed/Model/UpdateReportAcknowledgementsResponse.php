<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     MarketplaceWebService
 *  @copyright   Copyright 2009 Amazon Technologies, Inc.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2009-01-01
 */
/******************************************************************************* 

 *  Marketplace Web Service PHP5 Library
 *  Generated: Thu May 07 13:07:36 PDT 2009
 * 
 */

/**
 *  @see MarketplaceWebService_Model
 */
// require_once ('MarketplaceWebService/Model.php');  

    

/**
 * MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse
 * 
 * Properties:
 * <ul>
 * 
 * <li>UpdateReportAcknowledgementsResult: MarketplaceWebService_Model_UpdateReportAcknowledgementsResult</li>
 * <li>ResponseMetadata: MarketplaceWebService_Model_ResponseMetadata</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>UpdateReportAcknowledgementsResult: MarketplaceWebService_Model_UpdateReportAcknowledgementsResult</li>
     * <li>ResponseMetadata: MarketplaceWebService_Model_ResponseMetadata</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'UpdateReportAcknowledgementsResult' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_UpdateReportAcknowledgementsResult'),
        'ResponseMetadata' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_ResponseMetadata'),
        );
        parent::__construct($data);
    }

       
    /**
     * Construct MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse from XML string
     * 
     * @param string $xml XML string to construct from
     * @return MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse 
     */
    public static function fromXML($xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
    	$xpath->registerNamespace('a', 'http://mws.amazonaws.com/doc/2009-01-01/');
        $response = $xpath->query('//a:UpdateReportAcknowledgementsResponse');
        if ($response->length == 1) {
            return new MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse(($response->item(0))); 
        } else {
            throw new Exception ("Unable to construct MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse from provided XML. 
                                  Make sure that UpdateReportAcknowledgementsResponse is a root element");
        }
          
    }
    
    /**
     * Gets the value of the UpdateReportAcknowledgementsResult.
     * 
     * @return UpdateReportAcknowledgementsResult UpdateReportAcknowledgementsResult
     */
    public function getUpdateReportAcknowledgementsResult() 
    {
        return $this->fields['UpdateReportAcknowledgementsResult']['FieldValue'];
    }

    /**
     * Sets the value of the UpdateReportAcknowledgementsResult.
     * 
     * @param UpdateReportAcknowledgementsResult UpdateReportAcknowledgementsResult
     * @return void
     */
    public function setUpdateReportAcknowledgementsResult($value) 
    {
        $this->fields['UpdateReportAcknowledgementsResult']['FieldValue'] = $value;
        return;
    }

    /**
     * Sets the value of the UpdateReportAcknowledgementsResult  and returns this instance
     * 
     * @param UpdateReportAcknowledgementsResult $value UpdateReportAcknowledgementsResult
     * @return MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse instance
     */
    public function withUpdateReportAcknowledgementsResult($value)
    {
        $this->setUpdateReportAcknowledgementsResult($value);
        return $this;
    }


    /**
     * Checks if UpdateReportAcknowledgementsResult  is set
     * 
     * @return bool true if UpdateReportAcknowledgementsResult property is set
     */
    public function isSetUpdateReportAcknowledgementsResult()
    {
        return !is_null($this->fields['UpdateReportAcknowledgementsResult']['FieldValue']);

    }

    /**
     * Gets the value of the ResponseMetadata.
     * 
     * @return ResponseMetadata ResponseMetadata
     */
    public function getResponseMetadata() 
    {
        return $this->fields['ResponseMetadata']['FieldValue'];
    }

    /**
     * Sets the value of the ResponseMetadata.
     * 
     * @param ResponseMetadata ResponseMetadata
     * @return void
     */
    public function setResponseMetadata($value) 
    {
        $this->fields['ResponseMetadata']['FieldValue'] = $value;
        return;
    }

    /**
     * Sets the value of the ResponseMetadata  and returns this instance
     * 
     * @param ResponseMetadata $value ResponseMetadata
     * @return MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse instance
     */
    public function withResponseMetadata($value)
    {
        $this->setResponseMetadata($value);
        return $this;
    }


    /**
     * Checks if ResponseMetadata  is set
     * 
     * @return bool true if ResponseMetadata property is set
     */
    public function isSetResponseMetadata()
    {
        return !is_null($this->fields['ResponseMetadata']['FieldValue']);

    }



    /**
     * XML Representation for this object
     * 
     * @return string XML for this object
     */
    public function toXML() 
    {
        $xml = "";
        $xml .= "<UpdateReportAcknowledgementsResponse xmlns=\"http://mws.amazonaws.com/doc/2009-01-01/\">";
        $xml .= $this->_toXMLFragment();
        $xml .= "</UpdateReportAcknowledgementsResponse>";
        return $xml;
    }

    private $_responseHeaderMetadata = null;

    public function getResponseHeaderMetadata() {
      return $this->_responseHeaderMetadata;
    }

    public function setResponseHeaderMetadata($responseHeaderMetadata) {
      return $this->_responseHeaderMetadata = $responseHeaderMetadata;
    }
}
