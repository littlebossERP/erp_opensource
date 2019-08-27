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
 * MarketplaceWebService_Model_GetFeedSubmissionResultRequest
 * 
 * Properties:
 * <ul>
 * 
 * <li>Marketplace: string</li>
 * <li>Merchant: string</li>
 * <li>FeedSubmissionId: string</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_GetFeedSubmissionResultRequest extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_GetFeedSubmissionResultRequest
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Marketplace: string</li>
     * <li>Merchant: string</li>
     * <li>FeedSubmissionId: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'Marketplace' => array('FieldValue' => null, 'FieldType' => 'string'),
        'Merchant' => array('FieldValue' => null, 'FieldType' => 'string'),
        'FeedSubmissionId' => array('FieldValue' => null, 'FieldType' => 'string'),
        'FeedSubmissionResult' => array ('FieldValue' => null, 'FieldType' => 'string'),
        );
        parent::__construct($data);
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
     * @return MarketplaceWebService_Model_GetFeedSubmissionResultRequest instance
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
     * @return MarketplaceWebService_Model_GetFeedSubmissionResultRequest instance
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
     * Gets the value of the FeedSubmissionId property.
     * 
     * @return string FeedSubmissionId
     */
    public function getFeedSubmissionId() 
    {
        return $this->fields['FeedSubmissionId']['FieldValue'];
    }

    /**
     * Sets the value of the FeedSubmissionId property.
     * 
     * @param string FeedSubmissionId
     * @return this instance
     */
    public function setFeedSubmissionId($value) 
    {
        $this->fields['FeedSubmissionId']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the FeedSubmissionId and returns this instance
     * 
     * @param string $value FeedSubmissionId
     * @return MarketplaceWebService_Model_GetFeedSubmissionResultRequest instance
     */
    public function withFeedSubmissionId($value)
    {
        $this->setFeedSubmissionId($value);
        return $this;
    }


    /**
     * Checks if FeedSubmissionId is set
     * 
     * @return bool true if FeedSubmissionId  is set
     */
    public function isSetFeedSubmissionId()
    {
        return !is_null($this->fields['FeedSubmissionId']['FieldValue']);
    }

   /**
     * Gets the value of the FeedSubmissionResult property.
     * 
     * @return string FeedSubmissionResult
     */
    public function getFeedSubmissionResult() 
    {
        return $this->fields['FeedSubmissionResult']['FieldValue'];
    }

    /**
     * Sets the value of the FeedSubmissionResult property.
     * 
     * @param string FeedSubmissionResult
     * @return this instance
     */
    public function setFeedSubmissionResult($value) 
    {
        $this->fields['FeedSubmissionResult']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the FeedSubmissionResult and returns this instance
     * 
     * @param string $value FeedSubmissionResult
     * @return MarketplaceWebService_Model_GetFeedSubmissionResultRequest instance
     */
    public function withFeedSubmissionResult($value)
    {
        $this->setFeedSubmissionResult($value);
        return $this;
    }


    /**
     * Checks if FeedSubmissionResult is set
     * 
     * @return bool true if FeedSubmissionResult  is set
     */
    public function isFeedSubmissionResult()
    {
        return !is_null($this->fields['FeedSubmissionResult']['FieldValue']);
    }


}