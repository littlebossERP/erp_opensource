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
 * MarketplaceWebService_Model_CancelReportRequestsRequest
 * 
 * Properties:
 * <ul>
 * 
 * <li>Marketplace: string</li>
 * <li>Merchant: string</li>
 * <li>ReportRequestIdList: MarketplaceWebService_Model_IdList</li>
 * <li>ReportTypeList: MarketplaceWebService_Model_TypeList</li>
 * <li>ReportProcessingStatusList: MarketplaceWebService_Model_StatusList</li>
 * <li>RequestedFromDate: string</li>
 * <li>RequestedToDate: string</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_CancelReportRequestsRequest extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_CancelReportRequestsRequest
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Marketplace: string</li>
     * <li>Merchant: string</li>
     * <li>ReportRequestIdList: MarketplaceWebService_Model_IdList</li>
     * <li>ReportTypeList: MarketplaceWebService_Model_TypeList</li>
     * <li>ReportProcessingStatusList: MarketplaceWebService_Model_StatusList</li>
     * <li>RequestedFromDate: string</li>
     * <li>RequestedToDate: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'Marketplace' => array('FieldValue' => null, 'FieldType' => 'string'),
        'Merchant' => array('FieldValue' => null, 'FieldType' => 'string'),
        'ReportRequestIdList' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_IdList'),
        'ReportTypeList' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_TypeList'),
        'ReportProcessingStatusList' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_StatusList'),
        'RequestedFromDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        'RequestedToDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
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
     * @return MarketplaceWebService_Model_CancelReportRequestsRequest instance
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
     * @return MarketplaceWebService_Model_CancelReportRequestsRequest instance
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
     * Gets the value of the ReportRequestIdList.
     * 
     * @return IdList ReportRequestIdList
     */
    public function getReportRequestIdList() 
    {
        return $this->fields['ReportRequestIdList']['FieldValue'];
    }

    /**
     * Sets the value of the ReportRequestIdList.
     * 
     * @param IdList ReportRequestIdList
     * @return void
     */
    public function setReportRequestIdList($value) 
    {
        $this->fields['ReportRequestIdList']['FieldValue'] = $value;
        return;
    }

    /**
     * Sets the value of the ReportRequestIdList  and returns this instance
     * 
     * @param IdList $value ReportRequestIdList
     * @return MarketplaceWebService_Model_CancelReportRequestsRequest instance
     */
    public function withReportRequestIdList($value)
    {
        $this->setReportRequestIdList($value);
        return $this;
    }


    /**
     * Checks if ReportRequestIdList  is set
     * 
     * @return bool true if ReportRequestIdList property is set
     */
    public function isSetReportRequestIdList()
    {
        return !is_null($this->fields['ReportRequestIdList']['FieldValue']);

    }

    /**
     * Gets the value of the ReportTypeList.
     * 
     * @return TypeList ReportTypeList
     */
    public function getReportTypeList() 
    {
        return $this->fields['ReportTypeList']['FieldValue'];
    }

    /**
     * Sets the value of the ReportTypeList.
     * 
     * @param TypeList ReportTypeList
     * @return void
     */
    public function setReportTypeList($value) 
    {
        $this->fields['ReportTypeList']['FieldValue'] = $value;
        return;
    }

    /**
     * Sets the value of the ReportTypeList  and returns this instance
     * 
     * @param TypeList $value ReportTypeList
     * @return MarketplaceWebService_Model_CancelReportRequestsRequest instance
     */
    public function withReportTypeList($value)
    {
        $this->setReportTypeList($value);
        return $this;
    }


    /**
     * Checks if ReportTypeList  is set
     * 
     * @return bool true if ReportTypeList property is set
     */
    public function isSetReportTypeList()
    {
        return !is_null($this->fields['ReportTypeList']['FieldValue']);

    }

    /**
     * Gets the value of the ReportProcessingStatusList.
     * 
     * @return StatusList ReportProcessingStatusList
     */
    public function getReportProcessingStatusList() 
    {
        return $this->fields['ReportProcessingStatusList']['FieldValue'];
    }

    /**
     * Sets the value of the ReportProcessingStatusList.
     * 
     * @param StatusList ReportProcessingStatusList
     * @return void
     */
    public function setReportProcessingStatusList($value) 
    {
        $this->fields['ReportProcessingStatusList']['FieldValue'] = $value;
        return;
    }

    /**
     * Sets the value of the ReportProcessingStatusList  and returns this instance
     * 
     * @param StatusList $value ReportProcessingStatusList
     * @return MarketplaceWebService_Model_CancelReportRequestsRequest instance
     */
    public function withReportProcessingStatusList($value)
    {
        $this->setReportProcessingStatusList($value);
        return $this;
    }


    /**
     * Checks if ReportProcessingStatusList  is set
     * 
     * @return bool true if ReportProcessingStatusList property is set
     */
    public function isSetReportProcessingStatusList()
    {
        return !is_null($this->fields['ReportProcessingStatusList']['FieldValue']);

    }

    /**
     * Gets the value of the RequestedFromDate property.
     * 
     * @return string RequestedFromDate
     */
    public function getRequestedFromDate() 
    {
        return $this->fields['RequestedFromDate']['FieldValue'];
    }

    /**
     * Sets the value of the RequestedFromDate property.
     * 
     * @param string RequestedFromDate
     * @return this instance
     */
    public function setRequestedFromDate($value) 
    {
        $this->fields['RequestedFromDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the RequestedFromDate and returns this instance
     * 
     * @param string $value RequestedFromDate
     * @return MarketplaceWebService_Model_CancelReportRequestsRequest instance
     */
    public function withRequestedFromDate($value)
    {
        $this->setRequestedFromDate($value);
        return $this;
    }


    /**
     * Checks if RequestedFromDate is set
     * 
     * @return bool true if RequestedFromDate  is set
     */
    public function isSetRequestedFromDate()
    {
        return !is_null($this->fields['RequestedFromDate']['FieldValue']);
    }

    /**
     * Gets the value of the RequestedToDate property.
     * 
     * @return string RequestedToDate
     */
    public function getRequestedToDate() 
    {
        return $this->fields['RequestedToDate']['FieldValue'];
    }

    /**
     * Sets the value of the RequestedToDate property.
     * 
     * @param string RequestedToDate
     * @return this instance
     */
    public function setRequestedToDate($value) 
    {
        $this->fields['RequestedToDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the RequestedToDate and returns this instance
     * 
     * @param string $value RequestedToDate
     * @return MarketplaceWebService_Model_CancelReportRequestsRequest instance
     */
    public function withRequestedToDate($value)
    {
        $this->setRequestedToDate($value);
        return $this;
    }


    /**
     * Checks if RequestedToDate is set
     * 
     * @return bool true if RequestedToDate  is set
     */
    public function isSetRequestedToDate()
    {
        return !is_null($this->fields['RequestedToDate']['FieldValue']);
    }




}