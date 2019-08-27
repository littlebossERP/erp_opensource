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
 * MarketplaceWebService_Model_ReportInfo
 * 
 * Properties:
 * <ul>
 * 
 * <li>ReportId: string</li>
 * <li>ReportType: string</li>
 * <li>ReportRequestId: string</li>
 * <li>AvailableDate: string</li>
 * <li>Acknowledged: bool</li>
 * <li>AcknowledgedDate: string</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_ReportInfo extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_ReportInfo
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>ReportId: string</li>
     * <li>ReportType: string</li>
     * <li>ReportRequestId: string</li>
     * <li>AvailableDate: string</li>
     * <li>Acknowledged: bool</li>
     * <li>AcknowledgedDate: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'ReportId' => array('FieldValue' => null, 'FieldType' => 'string'),
        'ReportType' => array('FieldValue' => null, 'FieldType' => 'string'),
        'ReportRequestId' => array('FieldValue' => null, 'FieldType' => 'string'),
        'AvailableDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        'Acknowledged' => array('FieldValue' => null, 'FieldType' => 'bool'),
        'AcknowledgedDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        );
        parent::__construct($data);
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
     * @return MarketplaceWebService_Model_ReportInfo instance
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

    /**
     * Gets the value of the ReportType property.
     * 
     * @return string ReportType
     */
    public function getReportType() 
    {
        return $this->fields['ReportType']['FieldValue'];
    }

    /**
     * Sets the value of the ReportType property.
     * 
     * @param string ReportType
     * @return this instance
     */
    public function setReportType($value) 
    {
        $this->fields['ReportType']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the ReportType and returns this instance
     * 
     * @param string $value ReportType
     * @return MarketplaceWebService_Model_ReportInfo instance
     */
    public function withReportType($value)
    {
        $this->setReportType($value);
        return $this;
    }


    /**
     * Checks if ReportType is set
     * 
     * @return bool true if ReportType  is set
     */
    public function isSetReportType()
    {
        return !is_null($this->fields['ReportType']['FieldValue']);
    }

    /**
     * Gets the value of the ReportRequestId property.
     * 
     * @return string ReportRequestId
     */
    public function getReportRequestId() 
    {
        return $this->fields['ReportRequestId']['FieldValue'];
    }

    /**
     * Sets the value of the ReportRequestId property.
     * 
     * @param string ReportRequestId
     * @return this instance
     */
    public function setReportRequestId($value) 
    {
        $this->fields['ReportRequestId']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the ReportRequestId and returns this instance
     * 
     * @param string $value ReportRequestId
     * @return MarketplaceWebService_Model_ReportInfo instance
     */
    public function withReportRequestId($value)
    {
        $this->setReportRequestId($value);
        return $this;
    }


    /**
     * Checks if ReportRequestId is set
     * 
     * @return bool true if ReportRequestId  is set
     */
    public function isSetReportRequestId()
    {
        return !is_null($this->fields['ReportRequestId']['FieldValue']);
    }

    /**
     * Gets the value of the AvailableDate property.
     * 
     * @return string AvailableDate
     */
    public function getAvailableDate() 
    {
        return $this->fields['AvailableDate']['FieldValue'];
    }

    /**
     * Sets the value of the AvailableDate property.
     * 
     * @param string AvailableDate
     * @return this instance
     */
    public function setAvailableDate($value) 
    {
        $this->fields['AvailableDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the AvailableDate and returns this instance
     * 
     * @param string $value AvailableDate
     * @return MarketplaceWebService_Model_ReportInfo instance
     */
    public function withAvailableDate($value)
    {
        $this->setAvailableDate($value);
        return $this;
    }


    /**
     * Checks if AvailableDate is set
     * 
     * @return bool true if AvailableDate  is set
     */
    public function isSetAvailableDate()
    {
        return !is_null($this->fields['AvailableDate']['FieldValue']);
    }

    /**
     * Gets the value of the Acknowledged property.
     * 
     * @return bool Acknowledged
     */
    public function getAcknowledged() 
    {
        return $this->fields['Acknowledged']['FieldValue'];
    }

    /**
     * Sets the value of the Acknowledged property.
     * 
     * @param bool Acknowledged
     * @return this instance
     */
    public function setAcknowledged($value) 
    {
        $this->fields['Acknowledged']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the Acknowledged and returns this instance
     * 
     * @param bool $value Acknowledged
     * @return MarketplaceWebService_Model_ReportInfo instance
     */
    public function withAcknowledged($value)
    {
        $this->setAcknowledged($value);
        return $this;
    }


    /**
     * Checks if Acknowledged is set
     * 
     * @return bool true if Acknowledged  is set
     */
    public function isSetAcknowledged()
    {
        return !is_null($this->fields['Acknowledged']['FieldValue']);
    }

    /**
     * Gets the value of the AcknowledgedDate property.
     * 
     * @return string AcknowledgedDate
     */
    public function getAcknowledgedDate() 
    {
        return $this->fields['AcknowledgedDate']['FieldValue'];
    }

    /**
     * Sets the value of the AcknowledgedDate property.
     * 
     * @param string AcknowledgedDate
     * @return this instance
     */
    public function setAcknowledgedDate($value) 
    {
        $this->fields['AcknowledgedDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the AcknowledgedDate and returns this instance
     * 
     * @param string $value AcknowledgedDate
     * @return MarketplaceWebService_Model_ReportInfo instance
     */
    public function withAcknowledgedDate($value)
    {
        $this->setAcknowledgedDate($value);
        return $this;
    }


    /**
     * Checks if AcknowledgedDate is set
     * 
     * @return bool true if AcknowledgedDate  is set
     */
    public function isSetAcknowledgedDate()
    {
        return !is_null($this->fields['AcknowledgedDate']['FieldValue']);
    }




}