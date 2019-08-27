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
 * MarketplaceWebService_Model_ReportSchedule
 * 
 * Properties:
 * <ul>
 * 
 * <li>ReportType: string</li>
 * <li>Schedule: string</li>
 * <li>ScheduledDate: string</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_ReportSchedule extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_ReportSchedule
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>ReportType: string</li>
     * <li>Schedule: string</li>
     * <li>ScheduledDate: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'ReportType' => array('FieldValue' => null, 'FieldType' => 'string'),
        'Schedule' => array('FieldValue' => null, 'FieldType' => 'string'),
        'ScheduledDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        );
        parent::__construct($data);
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
     * @return MarketplaceWebService_Model_ReportSchedule instance
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
     * Gets the value of the Schedule property.
     * 
     * @return string Schedule
     */
    public function getSchedule() 
    {
        return $this->fields['Schedule']['FieldValue'];
    }

    /**
     * Sets the value of the Schedule property.
     * 
     * @param string Schedule
     * @return this instance
     */
    public function setSchedule($value) 
    {
        $this->fields['Schedule']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the Schedule and returns this instance
     * 
     * @param string $value Schedule
     * @return MarketplaceWebService_Model_ReportSchedule instance
     */
    public function withSchedule($value)
    {
        $this->setSchedule($value);
        return $this;
    }


    /**
     * Checks if Schedule is set
     * 
     * @return bool true if Schedule  is set
     */
    public function isSetSchedule()
    {
        return !is_null($this->fields['Schedule']['FieldValue']);
    }

    /**
     * Gets the value of the ScheduledDate property.
     * 
     * @return string ScheduledDate
     */
    public function getScheduledDate() 
    {
        return $this->fields['ScheduledDate']['FieldValue'];
    }

    /**
     * Sets the value of the ScheduledDate property.
     * 
     * @param string ScheduledDate
     * @return this instance
     */
    public function setScheduledDate($value) 
    {
        $this->fields['ScheduledDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the ScheduledDate and returns this instance
     * 
     * @param string $value ScheduledDate
     * @return MarketplaceWebService_Model_ReportSchedule instance
     */
    public function withScheduledDate($value)
    {
        $this->setScheduledDate($value);
        return $this;
    }


    /**
     * Checks if ScheduledDate is set
     * 
     * @return bool true if ScheduledDate  is set
     */
    public function isSetScheduledDate()
    {
        return !is_null($this->fields['ScheduledDate']['FieldValue']);
    }




}