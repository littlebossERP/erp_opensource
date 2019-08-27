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
 * MarketplaceWebService_Model_ManageReportScheduleResult
 * 
 * Properties:
 * <ul>
 * 
 * <li>Count: int</li>
 * <li>ReportSchedule: MarketplaceWebService_Model_ReportSchedule</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_ManageReportScheduleResult extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_ManageReportScheduleResult
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Count: int</li>
     * <li>ReportSchedule: MarketplaceWebService_Model_ReportSchedule</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'Count' => array('FieldValue' => null, 'FieldType' => 'int'),
        'ReportSchedule' => array('FieldValue' => array(), 'FieldType' => array('MarketplaceWebService_Model_ReportSchedule')),
        );
        parent::__construct($data);
    }

        /**
     * Gets the value of the Count property.
     * 
     * @return int Count
     */
    public function getCount() 
    {
        return $this->fields['Count']['FieldValue'];
    }

    /**
     * Sets the value of the Count property.
     * 
     * @param int Count
     * @return this instance
     */
    public function setCount($value) 
    {
        $this->fields['Count']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the Count and returns this instance
     * 
     * @param int $value Count
     * @return MarketplaceWebService_Model_ManageReportScheduleResult instance
     */
    public function withCount($value)
    {
        $this->setCount($value);
        return $this;
    }


    /**
     * Checks if Count is set
     * 
     * @return bool true if Count  is set
     */
    public function isSetCount()
    {
        return !is_null($this->fields['Count']['FieldValue']);
    }

    /**
     * Gets the value of the ReportSchedule.
     * 
     * @return array of ReportSchedule ReportSchedule
     */
    public function getReportScheduleList() 
    {
        return $this->fields['ReportSchedule']['FieldValue'];
    }

    /**
     * Sets the value of the ReportSchedule.
     * 
     * @param mixed ReportSchedule or an array of ReportSchedule ReportSchedule
     * @return this instance
     */
    public function setReportScheduleList($reportSchedule) 
    {
        if (!$this->_isNumericArray($reportSchedule)) {
            $reportSchedule =  array ($reportSchedule);    
        }
        $this->fields['ReportSchedule']['FieldValue'] = $reportSchedule;
        return $this;
    }


    /**
     * Sets single or multiple values of ReportSchedule list via variable number of arguments. 
     * For example, to set the list with two elements, simply pass two values as arguments to this function
     * <code>withReportSchedule($reportSchedule1, $reportSchedule2)</code>
     * 
     * @param ReportSchedule  $reportScheduleArgs one or more ReportSchedule
     * @return MarketplaceWebService_Model_ManageReportScheduleResult  instance
     */
    public function withReportSchedule($reportScheduleArgs)
    {
        foreach (func_get_args() as $reportSchedule) {
            $this->fields['ReportSchedule']['FieldValue'][] = $reportSchedule;
        }
        return $this;
    }   



    /**
     * Checks if ReportSchedule list is non-empty
     * 
     * @return bool true if ReportSchedule list is non-empty
     */
    public function isSetReportSchedule()
    {
        return count ($this->fields['ReportSchedule']['FieldValue']) > 0;
    }




}