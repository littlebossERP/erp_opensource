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
 * MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse
 * 
 * Properties:
 * <ul>
 * 
 * <li>GetFeedSubmissionListByNextTokenResult: MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResult</li>
 * <li>ResponseMetadata: MarketplaceWebService_Model_ResponseMetadata</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>GetFeedSubmissionListByNextTokenResult: MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResult</li>
     * <li>ResponseMetadata: MarketplaceWebService_Model_ResponseMetadata</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'GetFeedSubmissionListByNextTokenResult' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResult'),
        'ResponseMetadata' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_ResponseMetadata'),
        );
        parent::__construct($data);
    }

       
    /**
     * Construct MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse from XML string
     * 
     * @param string $xml XML string to construct from
     * @return MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse 
     */
    public static function fromXML($xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
    	$xpath->registerNamespace('a', 'http://mws.amazonaws.com/doc/2009-01-01/');
        $response = $xpath->query('//a:GetFeedSubmissionListByNextTokenResponse');
        if ($response->length == 1) {
            return new MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse(($response->item(0))); 
        } else {
            throw new Exception ("Unable to construct MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse from provided XML. 
                                  Make sure that GetFeedSubmissionListByNextTokenResponse is a root element");
        }
          
    }
    
    /**
     * Gets the value of the GetFeedSubmissionListByNextTokenResult.
     * 
     * @return GetFeedSubmissionListByNextTokenResult GetFeedSubmissionListByNextTokenResult
     */
    public function getGetFeedSubmissionListByNextTokenResult() 
    {
        return $this->fields['GetFeedSubmissionListByNextTokenResult']['FieldValue'];
    }

    /**
     * Sets the value of the GetFeedSubmissionListByNextTokenResult.
     * 
     * @param GetFeedSubmissionListByNextTokenResult GetFeedSubmissionListByNextTokenResult
     * @return void
     */
    public function setGetFeedSubmissionListByNextTokenResult($value) 
    {
        $this->fields['GetFeedSubmissionListByNextTokenResult']['FieldValue'] = $value;
        return;
    }

    /**
     * Sets the value of the GetFeedSubmissionListByNextTokenResult  and returns this instance
     * 
     * @param GetFeedSubmissionListByNextTokenResult $value GetFeedSubmissionListByNextTokenResult
     * @return MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse instance
     */
    public function withGetFeedSubmissionListByNextTokenResult($value)
    {
        $this->setGetFeedSubmissionListByNextTokenResult($value);
        return $this;
    }


    /**
     * Checks if GetFeedSubmissionListByNextTokenResult  is set
     * 
     * @return bool true if GetFeedSubmissionListByNextTokenResult property is set
     */
    public function isSetGetFeedSubmissionListByNextTokenResult()
    {
        return !is_null($this->fields['GetFeedSubmissionListByNextTokenResult']['FieldValue']);

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
     * @return MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse instance
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
        $xml .= "<GetFeedSubmissionListByNextTokenResponse xmlns=\"http://mws.amazonaws.com/doc/2009-01-01/\">";
        $xml .= $this->_toXMLFragment();
        $xml .= "</GetFeedSubmissionListByNextTokenResponse>";
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
