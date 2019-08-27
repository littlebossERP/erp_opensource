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
 * MarketplaceWebService_Model_CancelReportRequestsResult
 * 
 * Properties:
 * <ul>
 * 
 * <li>Count: int</li>
 * <li>ReportRequestInfo: MarketplaceWebService_Model_ReportRequestInfo</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_CancelReportRequestsResult extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_CancelReportRequestsResult
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Count: int</li>
     * <li>ReportRequestInfo: MarketplaceWebService_Model_ReportRequestInfo</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'Count' => array('FieldValue' => null, 'FieldType' => 'int'),
        'ReportRequestInfo' => array('FieldValue' => array(), 'FieldType' => array('MarketplaceWebService_Model_ReportRequestInfo')),
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
     * @return MarketplaceWebService_Model_CancelReportRequestsResult instance
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
     * Gets the value of the ReportRequestInfo.
     * 
     * @return array of ReportRequestInfo ReportRequestInfo
     */
    public function getReportRequestInfoList() 
    {
        return $this->fields['ReportRequestInfo']['FieldValue'];
    }

    /**
     * Sets the value of the ReportRequestInfo.
     * 
     * @param mixed ReportRequestInfo or an array of ReportRequestInfo ReportRequestInfo
     * @return this instance
     */
    public function setReportRequestInfoList($reportRequestInfo) 
    {
        if (!$this->_isNumericArray($reportRequestInfo)) {
            $reportRequestInfo =  array ($reportRequestInfo);    
        }
        $this->fields['ReportRequestInfo']['FieldValue'] = $reportRequestInfo;
        return $this;
    }


    /**
     * Sets single or multiple values of ReportRequestInfo list via variable number of arguments. 
     * For example, to set the list with two elements, simply pass two values as arguments to this function
     * <code>withReportRequestInfo($reportRequestInfo1, $reportRequestInfo2)</code>
     * 
     * @param ReportRequestInfo  $reportRequestInfoArgs one or more ReportRequestInfo
     * @return MarketplaceWebService_Model_CancelReportRequestsResult  instance
     */
    public function withReportRequestInfo($reportRequestInfoArgs)
    {
        foreach (func_get_args() as $reportRequestInfo) {
            $this->fields['ReportRequestInfo']['FieldValue'][] = $reportRequestInfo;
        }
        return $this;
    }   



    /**
     * Checks if ReportRequestInfo list is non-empty
     * 
     * @return bool true if ReportRequestInfo list is non-empty
     */
    public function isSetReportRequestInfo()
    {
        return count ($this->fields['ReportRequestInfo']['FieldValue']) > 0;
    }




}