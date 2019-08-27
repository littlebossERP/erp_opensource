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
 * MarketplaceWebService_Model_GetReportListByNextTokenResult
 * 
 * Properties:
 * <ul>
 * 
 * <li>NextToken: string</li>
 * <li>HasNext: bool</li>
 * <li>ReportInfo: MarketplaceWebService_Model_ReportInfo</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_GetReportListByNextTokenResult extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_GetReportListByNextTokenResult
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>NextToken: string</li>
     * <li>HasNext: bool</li>
     * <li>ReportInfo: MarketplaceWebService_Model_ReportInfo</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'NextToken' => array('FieldValue' => null, 'FieldType' => 'string'),
        'HasNext' => array('FieldValue' => null, 'FieldType' => 'bool'),
        'ReportInfo' => array('FieldValue' => array(), 'FieldType' => array('MarketplaceWebService_Model_ReportInfo')),
        );
        parent::__construct($data);
    }

        /**
     * Gets the value of the NextToken property.
     * 
     * @return string NextToken
     */
    public function getNextToken() 
    {
        return $this->fields['NextToken']['FieldValue'];
    }

    /**
     * Sets the value of the NextToken property.
     * 
     * @param string NextToken
     * @return this instance
     */
    public function setNextToken($value) 
    {
        $this->fields['NextToken']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the NextToken and returns this instance
     * 
     * @param string $value NextToken
     * @return MarketplaceWebService_Model_GetReportListByNextTokenResult instance
     */
    public function withNextToken($value)
    {
        $this->setNextToken($value);
        return $this;
    }


    /**
     * Checks if NextToken is set
     * 
     * @return bool true if NextToken  is set
     */
    public function isSetNextToken()
    {
        return !is_null($this->fields['NextToken']['FieldValue']);
    }

    /**
     * Gets the value of the HasNext property.
     * 
     * @return bool HasNext
     */
    public function getHasNext() 
    {
        return $this->fields['HasNext']['FieldValue'];
    }

    /**
     * Sets the value of the HasNext property.
     * 
     * @param bool HasNext
     * @return this instance
     */
    public function setHasNext($value) 
    {
        $this->fields['HasNext']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the HasNext and returns this instance
     * 
     * @param bool $value HasNext
     * @return MarketplaceWebService_Model_GetReportListByNextTokenResult instance
     */
    public function withHasNext($value)
    {
        $this->setHasNext($value);
        return $this;
    }


    /**
     * Checks if HasNext is set
     * 
     * @return bool true if HasNext  is set
     */
    public function isSetHasNext()
    {
        return !is_null($this->fields['HasNext']['FieldValue']);
    }

    /**
     * Gets the value of the ReportInfo.
     * 
     * @return array of ReportInfo ReportInfo
     */
    public function getReportInfoList() 
    {
        return $this->fields['ReportInfo']['FieldValue'];
    }

    /**
     * Sets the value of the ReportInfo.
     * 
     * @param mixed ReportInfo or an array of ReportInfo ReportInfo
     * @return this instance
     */
    public function setReportInfoList($reportInfo) 
    {
        if (!$this->_isNumericArray($reportInfo)) {
            $reportInfo =  array ($reportInfo);    
        }
        $this->fields['ReportInfo']['FieldValue'] = $reportInfo;
        return $this;
    }


    /**
     * Sets single or multiple values of ReportInfo list via variable number of arguments. 
     * For example, to set the list with two elements, simply pass two values as arguments to this function
     * <code>withReportInfo($reportInfo1, $reportInfo2)</code>
     * 
     * @param ReportInfo  $reportInfoArgs one or more ReportInfo
     * @return MarketplaceWebService_Model_GetReportListByNextTokenResult  instance
     */
    public function withReportInfo($reportInfoArgs)
    {
        foreach (func_get_args() as $reportInfo) {
            $this->fields['ReportInfo']['FieldValue'][] = $reportInfo;
        }
        return $this;
    }   



    /**
     * Checks if ReportInfo list is non-empty
     * 
     * @return bool true if ReportInfo list is non-empty
     */
    public function isSetReportInfo()
    {
        return count ($this->fields['ReportInfo']['FieldValue']) > 0;
    }




}