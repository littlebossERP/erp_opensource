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
 * MarketplaceWebService_Model_GetReportRequest
 * 
 * Properties:
 * <ul>
 * 
 * <li>Marketplace: string</li>
 * <li>Merchant: string</li>
 * <li>ReportId: string</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_GetReportRequest extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_GetReportRequest
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Marketplace: string</li>
     * <li>Merchant: string</li>
     * <li>ReportId: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'Marketplace' => array('FieldValue' => null, 'FieldType' => 'string'),
        'Merchant' => array('FieldValue' => null, 'FieldType' => 'string'),
        'ReportId' => array('FieldValue' => null, 'FieldType' => 'string'),
        'Report' => array('FieldValue' => null, 'FieldType' => 'string'),
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
     * @return MarketplaceWebService_Model_GetReportRequest instance
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
     * @return MarketplaceWebService_Model_GetReportRequest instance
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
     * Gets the value of the ReportId property.
     * 
     * @return string ReportId
     */
    public function getReportId() 
    {
        return $this->fields['ReportId']['FieldValue'];
    }

    /**
     * Sets the value of the ReportId property.
     * 
     * @param string ReportId
     * @return this instance
     */
    public function setReportId($value) 
    {
        $this->fields['ReportId']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the ReportId and returns this instance
     * 
     * @param string $value ReportId
     * @return MarketplaceWebService_Model_GetReportRequest instance
     */
    public function withReportId($value)
    {
        $this->setReportId($value);
        return $this;
    }


    /**
     * Checks if ReportId is set
     * 
     * @return bool true if ReportId  is set
     */
    public function isSetReportId()
    {
        return !is_null($this->fields['ReportId']['FieldValue']);
    }

/* -0------------------------------------------------- */

    /**
     * Gets the value of the Report property.
     * 
     * @return string Report
     */
    public function getReport() 
    {
        return $this->fields['Report']['FieldValue'];
    }

    /**
     * Sets the value of the Report property.
     * 
     * @param string Report
     * @return this instance
     */
    public function setReport($value) 
    {
        $this->fields['Report']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the Report and returns this instance
     * 
     * @param string $value Report
     * @return MarketplaceWebService_Model_GetReportRequest instance
     */
    public function withReport($value)
    {
        $this->setReport($value);
        return $this;
    }


    /**
     * Checks if Report is set
     * 
     * @return bool true if Report  is set
     */
    public function isSetReport()
    {
        return !is_null($this->fields['Report']['FieldValue']);
    }
    


}