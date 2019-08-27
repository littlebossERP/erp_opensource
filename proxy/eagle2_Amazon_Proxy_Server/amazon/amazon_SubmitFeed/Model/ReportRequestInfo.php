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
 * MarketplaceWebService_Model_ReportRequestInfo
 * 
 * Properties:
 * <ul>
 * 
 * <li>ReportRequestId: string</li>
 * <li>ReportType: string</li>
 * <li>StartDate: string</li>
 * <li>EndDate: string</li>
 * <li>SubmittedDate: string</li>
 * <li>ReportProcessingStatus: string</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_ReportRequestInfo extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_ReportRequestInfo
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>ReportRequestId: string</li>
     * <li>ReportType: string</li>
     * <li>StartDate: string</li>
     * <li>EndDate: string</li>
     * <li>SubmittedDate: string</li>
     * <li>ReportProcessingStatus: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'ReportRequestId' => array('FieldValue' => null, 'FieldType' => 'string'),
        'ReportType' => array('FieldValue' => null, 'FieldType' => 'string'),
        'StartDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        'EndDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        'Scheduled' => array('FieldValue' => null, 'FieldType' => 'bool'),
        'SubmittedDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        'ReportProcessingStatus' => array('FieldValue' => null, 'FieldType' => 'string'),
        );
        parent::__construct($data);
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
     * @return MarketplaceWebService_Model_ReportRequestInfo instance
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
     * @return MarketplaceWebService_Model_ReportRequestInfo instance
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
     * Gets the value of the StartDate property.
     * 
     * @return string StartDate
     */
    public function getStartDate() 
    {
        return $this->fields['StartDate']['FieldValue'];
    }

    /**
     * Sets the value of the StartDate property.
     * 
     * @param string StartDate
     * @return this instance
     */
    public function setStartDate($value) 
    {
        $this->fields['StartDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the StartDate and returns this instance
     * 
     * @param string $value StartDate
     * @return MarketplaceWebService_Model_ReportRequestInfo instance
     */
    public function withStartDate($value)
    {
        $this->setStartDate($value);
        return $this;
    }


    /**
     * Checks if StartDate is set
     * 
     * @return bool true if StartDate  is set
     */
    public function isSetStartDate()
    {
        return !is_null($this->fields['StartDate']['FieldValue']);
    }

    /**
     * Gets the value of the EndDate property.
     * 
     * @return string EndDate
     */
    public function getEndDate() 
    {
        return $this->fields['EndDate']['FieldValue'];
    }

    /**
     * Sets the value of the EndDate property.
     * 
     * @param string EndDate
     * @return this instance
     */
    public function setEndDate($value) 
    {
        $this->fields['EndDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the EndDate and returns this instance
     * 
     * @param string $value EndDate
     * @return MarketplaceWebService_Model_ReportRequestInfo instance
     */
    public function withEndDate($value)
    {
        $this->setEndDate($value);
        return $this;
    }


    /**
     * Checks if EndDate is set
     * 
     * @return bool true if EndDate  is set
     */
    public function isSetEndDate()
    {
        return !is_null($this->fields['EndDate']['FieldValue']);
    }

    /**
     * Gets the value of the Scheduled property.
     * 
     * @return string Scheduled
     */
    public function getScheduled() 
    {
        return $this->fields['Scheduled']['FieldValue'];
    }

    /**
     * Sets the value of the Scheduled property.
     * 
     * @param string Scheduled
     * @return this instance
     */
    public function setScheduled($value) 
    {
        $this->fields['Scheduled']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the Scheduled and returns this instance
     * 
     * @param string $value Scheduled
     * @return MarketplaceWebService_Model_ReportRequestInfo instance
     */
    public function withScheduled($value)
    {
        $this->setScheduled($value);
        return $this;
    }


    /**
     * Checks if Scheduled is set
     * 
     * @return bool true if Scheduled  is set
     */
    public function isSetScheduled()
    {
        return !is_null($this->fields['Scheduled']['FieldValue']);
    }
    

    /**
     * Gets the value of the SubmittedDate property.
     * 
     * @return string SubmittedDate
     */
    public function getSubmittedDate() 
    {
        return $this->fields['SubmittedDate']['FieldValue'];
    }

    /**
     * Sets the value of the SubmittedDate property.
     * 
     * @param string SubmittedDate
     * @return this instance
     */
    public function setSubmittedDate($value) 
    {
        $this->fields['SubmittedDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the SubmittedDate and returns this instance
     * 
     * @param string $value SubmittedDate
     * @return MarketplaceWebService_Model_ReportRequestInfo instance
     */
    public function withSubmittedDate($value)
    {
        $this->setSubmittedDate($value);
        return $this;
    }


    /**
     * Checks if SubmittedDate is set
     * 
     * @return bool true if SubmittedDate  is set
     */
    public function isSetSubmittedDate()
    {
        return !is_null($this->fields['SubmittedDate']['FieldValue']);
    }

    /**
     * Gets the value of the ReportProcessingStatus property.
     * 
     * @return string ReportProcessingStatus
     */
    public function getReportProcessingStatus() 
    {
        return $this->fields['ReportProcessingStatus']['FieldValue'];
    }

    /**
     * Sets the value of the ReportProcessingStatus property.
     * 
     * @param string ReportProcessingStatus
     * @return this instance
     */
    public function setReportProcessingStatus($value) 
    {
        $this->fields['ReportProcessingStatus']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the ReportProcessingStatus and returns this instance
     * 
     * @param string $value ReportProcessingStatus
     * @return MarketplaceWebService_Model_ReportRequestInfo instance
     */
    public function withReportProcessingStatus($value)
    {
        $this->setReportProcessingStatus($value);
        return $this;
    }


    /**
     * Checks if ReportProcessingStatus is set
     * 
     * @return bool true if ReportProcessingStatus  is set
     */
    public function isSetReportProcessingStatus()
    {
        return !is_null($this->fields['ReportProcessingStatus']['FieldValue']);
    }




}
