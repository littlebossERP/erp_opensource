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
 * MarketplaceWebService_Model_CancelFeedSubmissionsResult
 * 
 * Properties:
 * <ul>
 * 
 * <li>Count: int</li>
 * <li>FeedSubmissionInfo: MarketplaceWebService_Model_FeedSubmissionInfo</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_CancelFeedSubmissionsResult extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_CancelFeedSubmissionsResult
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Count: int</li>
     * <li>FeedSubmissionInfo: MarketplaceWebService_Model_FeedSubmissionInfo</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'Count' => array('FieldValue' => null, 'FieldType' => 'int'),
        'FeedSubmissionInfo' => array('FieldValue' => array(), 'FieldType' => array('MarketplaceWebService_Model_FeedSubmissionInfo')),
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
     * @return MarketplaceWebService_Model_CancelFeedSubmissionsResult instance
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
     * Gets the value of the FeedSubmissionInfo.
     * 
     * @return array of FeedSubmissionInfo FeedSubmissionInfo
     */
    public function getFeedSubmissionInfoList() 
    {
        return $this->fields['FeedSubmissionInfo']['FieldValue'];
    }

    /**
     * Sets the value of the FeedSubmissionInfo.
     * 
     * @param mixed FeedSubmissionInfo or an array of FeedSubmissionInfo FeedSubmissionInfo
     * @return this instance
     */
    public function setFeedSubmissionInfoList($feedSubmissionInfo) 
    {
        if (!$this->_isNumericArray($feedSubmissionInfo)) {
            $feedSubmissionInfo =  array ($feedSubmissionInfo);    
        }
        $this->fields['FeedSubmissionInfo']['FieldValue'] = $feedSubmissionInfo;
        return $this;
    }


    /**
     * Sets single or multiple values of FeedSubmissionInfo list via variable number of arguments. 
     * For example, to set the list with two elements, simply pass two values as arguments to this function
     * <code>withFeedSubmissionInfo($feedSubmissionInfo1, $feedSubmissionInfo2)</code>
     * 
     * @param FeedSubmissionInfo  $feedSubmissionInfoArgs one or more FeedSubmissionInfo
     * @return MarketplaceWebService_Model_CancelFeedSubmissionsResult  instance
     */
    public function withFeedSubmissionInfo($feedSubmissionInfoArgs)
    {
        foreach (func_get_args() as $feedSubmissionInfo) {
            $this->fields['FeedSubmissionInfo']['FieldValue'][] = $feedSubmissionInfo;
        }
        return $this;
    }   



    /**
     * Checks if FeedSubmissionInfo list is non-empty
     * 
     * @return bool true if FeedSubmissionInfo list is non-empty
     */
    public function isSetFeedSubmissionInfo()
    {
        return count ($this->fields['FeedSubmissionInfo']['FieldValue']) > 0;
    }




}