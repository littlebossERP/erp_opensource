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
 * MarketplaceWebService_Model_GetReportRequestCountResult
 * 
 * Properties:
 * <ul>
 * 
 * <li>Count: int</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_GetReportRequestCountResult extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_GetReportRequestCountResult
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Count: int</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'Count' => array('FieldValue' => null, 'FieldType' => 'int'),
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
     * @return MarketplaceWebService_Model_GetReportRequestCountResult instance
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




}