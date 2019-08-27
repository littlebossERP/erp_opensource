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
// require_once ('MarketplaceWebService/Model/ContentType.php');
    

/**
 * MarketplaceWebService_Model_SubmitFeedRequest
 * 
 * Properties:
 * <ul>
 * 
 * <li>Marketplace: string</li>
 * <li>Merchant: string</li>
 * <li>MarketplaceIdList: MarketplaceWebService_Model_IdList</li>
 * <li>FeedContent: string</li>
 * <li>FeedType: string</li>
 * <li>PurgeAndReplace: bool</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_SubmitFeedRequest extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_SubmitFeedRequest
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Marketplace: string</li>
     * <li>Merchant: string</li>
     * <li>MarketplaceIdList: MarketplaceWebService_Model_IdList</li>
     * <li>FeedContent: string</li>
     * <li>FeedType: string</li>
     * <li>PurgeAndReplace: bool</li>
     *
     * </ul>
     */
	
    private static $DEFAULT_CONTENT_TYPE;
	
    public function __construct($data = null)
    {
    	self::$DEFAULT_CONTENT_TYPE = new MarketplaceWebService_Model_ContentType(
    		array('ContentType' => 'application/octet-stream'));
    		
        // Here we're setting the content-type field directly to the object, but beware the actual 
        // method of construction from associative arrays from the client interface would do something like:
        // $parameters = array ('ContentType' => array('ContentType' => 'application/octet-stream'));

        $this->fields = array (
        'Marketplace' => array('FieldValue' => null, 'FieldType' => 'string'),
        'Merchant' => array('FieldValue' => null, 'FieldType' => 'string'),
        'MarketplaceIdList' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_IdList'),
        'FeedContent' => array ('FieldValue' => null, 'FieldType' => 'string'),
        'FeedType' => array('FieldValue' => null, 'FieldType' => 'string'),
        'PurgeAndReplace' => array('FieldValue' => null, 'FieldType' => 'bool'),
        'ContentMd5' => array ('FieldValue' => null, 'FieldType' => 'string'),
 	'ContentType' => array ('FieldValue' => self::$DEFAULT_CONTENT_TYPE, 'FieldType' => 'MarketplaceWebService_Model_ContentType')      
        );
        parent::__construct($data);
        if (!is_null($this->fields['ContentType']['FieldValue'])) {
        	$this->verifySupportedContentType($this->fields['ContentType']['FieldValue']);	
        }
        
    }
    
    private function verifySupportedContentType($supplied) {
    if (!($supplied == self::$DEFAULT_CONTENT_TYPE)) {
    		throw new MarketplaceWebService_Exception(array('Message' =>
    			"Unsupported ContentType " .  $supplied->getContentType() . 
    			" ContentType must be " . self::$DEFAULT_CONTENT_TYPE->getContentType()));	
    	}
    }
    
    /**
     * Gets the value of the content type
     *
     * @return ContentType instance
     */

    public function getContentType() 
    {
        return $this->fields['ContentType']['FieldValue'];
    }
    
    public function setContentType($value) {
    	$this->verifySupportedContentType($value);
    	$this->fields['ContentType']['FieldValue'] = $value;
        return $this;
    }
    
    public function isSetContentType() {
    	return !is_null($this->fields['ContentType']['FieldValue']);
    }

    /**
     * Gets the value of the Marketplace property.
     * 
     * @return string Marketplace
     */
    public function getMarketplace() 
    {
        return $this->fields['Marketplace']['FieldValue'];
    }

    /**
     * Sets the value of the Marketplace property.
     * 
     * @param string Marketplace
     * @return this instance
     */
    public function setMarketplace($value) 
    {
        $this->fields['Marketplace']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the Marketplace and returns this instance
     * 
     * @param string $value Marketplace
     * @return MarketplaceWebService_Model_SubmitFeedRequest instance
     */
    public function withMarketplace($value)
    {
        $this->setMarketplace($value);
        return $this;
    }


    /**
     * Checks if Marketplace is set
     * 
     * @return bool true if Marketplace  is set
     */
    public function isSetMarketplace()
    {
        return !is_null($this->fields['Marketplace']['FieldValue']);
    }

    /**
     * Gets the value of the Merchant property.
     * 
     * @return string Merchant
     */
    public function getMerchant() 
    {
        return $this->fields['Merchant']['FieldValue'];
    }

    /**
     * Sets the value of the Merchant property.
     * 
     * @param string Merchant
     * @return this instance
     */
    public function setMerchant($value) 
    {
        $this->fields['Merchant']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the Merchant and returns this instance
     * 
     * @param string $value Merchant
     * @return MarketplaceWebService_Model_SubmitFeedRequest instance
     */
    public function withMerchant($value)
    {
        $this->setMerchant($value);
        return $this;
    }


    /**
     * Checks if Merchant is set
     * 
     * @return bool true if Merchant  is set
     */
    public function isSetMerchant()
    {
        return !is_null($this->fields['Merchant']['FieldValue']);
    }

    /**
     * Gets the value of the MarketplaceIdList.
     * 
     * @return IdList MarketplaceIdList
     */
    public function getMarketplaceIdList() 
    {
        return $this->fields['MarketplaceIdList']['FieldValue'];
    }

    /**
     * Sets the value of the MarketplaceIdList.
     * 
     * @param IdList MarketplaceIdList
     * @return void
     */
    public function setMarketplaceIdList($value) 
    {
	$marketplaceIdList = new MarketplaceWebService_Model_IdList();
	$marketplaceIdList->setId($value['Id']);
        $this->fields['MarketplaceIdList']['FieldValue'] = $marketplaceIdList;
        return;
    }

    /**
     * Sets the value of the MarketplaceIdList  and returns this instance
     * 
     * @param IdList $value MarketplaceIdList
     * @return MarketplaceWebService_Model_SubmitFeedRequest instance
     */
    public function withMarketplaceIdList($value)
    {
        $this->setMarketplaceIdList($value);
        return $this;
    }


    /**
     * Checks if MarketplaceIdList  is set
     * 
     * @return bool true if MarketplaceIdList property is set
     */
    public function isSetMarketplaceIdList()
    {
        return !is_null($this->fields['MarketplaceIdList']['FieldValue']);

    }

    /**
     * Gets the value of the FeedContent property.
     * 
     * @return string FeedContent
     */
    public function getFeedContent() 
    {
        return $this->fields['FeedContent']['FieldValue'];
    }

    /**
     * Sets the value of the FeedContent property.
     * 
     * @param string FeedContent
     * @return this instance
     */
    public function setFeedContent($value) 
    {
        $this->fields['FeedContent']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the FeedContent and returns this instance
     * 
     * @param string $value FeedContent
     * @return MarketplaceWebService_Model_SubmitFeedRequest instance
     */
    public function withFeedContent($value)
    {
        $this->setFeedContent($value);
        return $this;
    }


    /**
     * Checks if FeedContent is set
     * 
     * @return bool true if FeedContent  is set
     */
    public function isSetFeedContent()
    {
        return !is_null($this->fields['FeedContent']['FieldValue']);
    }

    /**
     * Gets the value of the FeedType property.
     * 
     * @return string FeedType
     */
    public function getFeedType() 
    {
        return $this->fields['FeedType']['FieldValue'];
    }

    /**
     * Sets the value of the FeedType property.
     * 
     * @param string FeedType
     * @return this instance
     */
    public function setFeedType($value) 
    {
        $this->fields['FeedType']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the FeedType and returns this instance
     * 
     * @param string $value FeedType
     * @return MarketplaceWebService_Model_SubmitFeedRequest instance
     */
    public function withFeedType($value)
    {
        $this->setFeedType($value);
        return $this;
    }


    /**
     * Checks if FeedType is set
     * 
     * @return bool true if FeedType  is set
     */
    public function isSetFeedType()
    {
        return !is_null($this->fields['FeedType']['FieldValue']);
    }

    /**
     * Gets the value of the PurgeAndReplace property.
     * 
     * @return bool PurgeAndReplace
     */
    public function getPurgeAndReplace() 
    {
        return $this->fields['PurgeAndReplace']['FieldValue'];
    }

    /**
     * Sets the value of the PurgeAndReplace property.
     * 
     * @param bool PurgeAndReplace
     * @return this instance
     */
    public function setPurgeAndReplace($value) 
    {
        $this->fields['PurgeAndReplace']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the PurgeAndReplace and returns this instance
     * 
     * @param bool $value PurgeAndReplace
     * @return MarketplaceWebService_Model_SubmitFeedRequest instance
     */
    public function withPurgeAndReplace($value)
    {
        $this->setPurgeAndReplace($value);
        return $this;
    }


    /**
     * Checks if PurgeAndReplace is set
     * 
     * @return bool true if PurgeAndReplace  is set
     */
    public function isSetPurgeAndReplace()
    {
        return !is_null($this->fields['PurgeAndReplace']['FieldValue']);
    }

    /**
     * Gets the value of the ContentMd5 property.
     * 
     * @return bool ContentMd5
     */
    public function getContentMd5() 
    {
        return $this->fields['ContentMd5']['FieldValue'];
    }

    /**
     * Sets the value of the ContentMd5 property.
     * 
     * @param bool ContentMd5
     * @return this instance
     */
    public function setContentMd5($value) 
    {
        $this->fields['ContentMd5']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the ContentMd5 and returns this instance
     * 
     * @param bool $value ContentMd5
     * @return MarketplaceWebService_Model_SubmitFeedRequest instance
     */
    public function withContentMd5($value)
    {
        $this->setContentMd5($value);
        return $this;
    }


    /**
     * Checks if ContentMd5 is set
     * 
     * @return bool true if ContentMd5  is set
     */
    public function isSetContentMd5()
    {
        return !is_null($this->fields['ContentMd5']['FieldValue']);
    }

}
