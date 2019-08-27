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
 * MarketplaceWebService_Model_GetFeedSubmissionCountRequest
 * 
 * Properties:
 * <ul>
 * 
 * <li>Marketplace: string</li>
 * <li>Merchant: string</li>
 * <li>FeedTypeList: MarketplaceWebService_Model_TypeList</li>
 * <li>FeedProcessingStatusList: MarketplaceWebService_Model_StatusList</li>
 * <li>SubmittedFromDate: string</li>
 * <li>SubmittedToDate: string</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_GetFeedSubmissionCountRequest extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_GetFeedSubmissionCountRequest
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Marketplace: string</li>
     * <li>Merchant: string</li>
     * <li>FeedTypeList: MarketplaceWebService_Model_TypeList</li>
     * <li>FeedProcessingStatusList: MarketplaceWebService_Model_StatusList</li>
     * <li>SubmittedFromDate: string</li>
     * <li>SubmittedToDate: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'Marketplace' => array('FieldValue' => null, 'FieldType' => 'string'),
        'Merchant' => array('FieldValue' => null, 'FieldType' => 'string'),
        'FeedTypeList' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_TypeList'),
        'FeedProcessingStatusList' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_StatusList'),
        'SubmittedFromDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        'SubmittedToDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
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
     * @return MarketplaceWebService_Model_GetFeedSubmissionCountRequest instance
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
     * @return MarketplaceWebService_Model_GetFeedSubmissionCountRequest instance
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
     * Gets the value of the FeedTypeList.
     * 
     * @return TypeList FeedTypeList
     */
    public function getFeedTypeList() 
    {
        return $this->fields['FeedTypeList']['FieldValue'];
    }

    /**
     * Sets the value of the FeedTypeList.
     * 
     * @param TypeList FeedTypeList
     * @return void
     */
    public function setFeedTypeList($value) 
    {
        $this->fields['FeedTypeList']['FieldValue'] = $value;
        return;
    }

    /**
     * Sets the value of the FeedTypeList  and returns this instance
     * 
     * @param TypeList $value FeedTypeList
     * @return MarketplaceWebService_Model_GetFeedSubmissionCountRequest instance
     */
    public function withFeedTypeList($value)
    {
        $this->setFeedTypeList($value);
        return $this;
    }


    /**
     * Checks if FeedTypeList  is set
     * 
     * @return bool true if FeedTypeList property is set
     */
    public function isSetFeedTypeList()
    {
        return !is_null($this->fields['FeedTypeList']['FieldValue']);

    }

    /**
     * Gets the value of the FeedProcessingStatusList.
     * 
     * @return StatusList FeedProcessingStatusList
     */
    public function getFeedProcessingStatusList() 
    {
        return $this->fields['FeedProcessingStatusList']['FieldValue'];
    }

    /**
     * Sets the value of the FeedProcessingStatusList.
     * 
     * @param StatusList FeedProcessingStatusList
     * @return void
     */
    public function setFeedProcessingStatusList($value) 
    {
        $this->fields['FeedProcessingStatusList']['FieldValue'] = $value;
        return;
    }

    /**
     * Sets the value of the FeedProcessingStatusList  and returns this instance
     * 
     * @param StatusList $value FeedProcessingStatusList
     * @return MarketplaceWebService_Model_GetFeedSubmissionCountRequest instance
     */
    public function withFeedProcessingStatusList($value)
    {
        $this->setFeedProcessingStatusList($value);
        return $this;
    }


    /**
     * Checks if FeedProcessingStatusList  is set
     * 
     * @return bool true if FeedProcessingStatusList property is set
     */
    public function isSetFeedProcessingStatusList()
    {
        return !is_null($this->fields['FeedProcessingStatusList']['FieldValue']);

    }

    /**
     * Gets the value of the SubmittedFromDate property.
     * 
     * @return string SubmittedFromDate
     */
    public function getSubmittedFromDate() 
    {
        return $this->fields['SubmittedFromDate']['FieldValue'];
    }

    /**
     * Sets the value of the SubmittedFromDate property.
     * 
     * @param string SubmittedFromDate
     * @return this instance
     */
    public function setSubmittedFromDate($value) 
    {
        $this->fields['SubmittedFromDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the SubmittedFromDate and returns this instance
     * 
     * @param string $value SubmittedFromDate
     * @return MarketplaceWebService_Model_GetFeedSubmissionCountRequest instance
     */
    public function withSubmittedFromDate($value)
    {
        $this->setSubmittedFromDate($value);
        return $this;
    }


    /**
     * Checks if SubmittedFromDate is set
     * 
     * @return bool true if SubmittedFromDate  is set
     */
    public function isSetSubmittedFromDate()
    {
        return !is_null($this->fields['SubmittedFromDate']['FieldValue']);
    }

    /**
     * Gets the value of the SubmittedToDate property.
     * 
     * @return string SubmittedToDate
     */
    public function getSubmittedToDate() 
    {
        return $this->fields['SubmittedToDate']['FieldValue'];
    }

    /**
     * Sets the value of the SubmittedToDate property.
     * 
     * @param string SubmittedToDate
     * @return this instance
     */
    public function setSubmittedToDate($value) 
    {
        $this->fields['SubmittedToDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the SubmittedToDate and returns this instance
     * 
     * @param string $value SubmittedToDate
     * @return MarketplaceWebService_Model_GetFeedSubmissionCountRequest instance
     */
    public function withSubmittedToDate($value)
    {
        $this->setSubmittedToDate($value);
        return $this;
    }


    /**
     * Checks if SubmittedToDate is set
     * 
     * @return bool true if SubmittedToDate  is set
     */
    public function isSetSubmittedToDate()
    {
        return !is_null($this->fields['SubmittedToDate']['FieldValue']);
    }




}