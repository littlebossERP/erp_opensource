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
 * MarketplaceWebService_Model_SubmitFeedResult
 * 
 * Properties:
 * <ul>
 * 
 * <li>FeedSubmissionInfo: MarketplaceWebService_Model_FeedSubmissionInfo</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_SubmitFeedResult extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_SubmitFeedResult
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>FeedSubmissionInfo: MarketplaceWebService_Model_FeedSubmissionInfo</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'FeedSubmissionInfo' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebService_Model_FeedSubmissionInfo'),
        );
        parent::__construct($data);
    }

        /**
     * Gets the value of the FeedSubmissionInfo.
     * 
     * @return FeedSubmissionInfo FeedSubmissionInfo
     */
    public function getFeedSubmissionInfo() 
    {
        return $this->fields['FeedSubmissionInfo']['FieldValue'];
    }

    /**
     * Sets the value of the FeedSubmissionInfo.
     * 
     * @param FeedSubmissionInfo FeedSubmissionInfo
     * @return void
     */
    public function setFeedSubmissionInfo($value) 
    {
        $this->fields['FeedSubmissionInfo']['FieldValue'] = $value;
        return;
    }

    /**
     * Sets the value of the FeedSubmissionInfo  and returns this instance
     * 
     * @param FeedSubmissionInfo $value FeedSubmissionInfo
     * @return MarketplaceWebService_Model_SubmitFeedResult instance
     */
    public function withFeedSubmissionInfo($value)
    {
        $this->setFeedSubmissionInfo($value);
        return $this;
    }


    /**
     * Checks if FeedSubmissionInfo  is set
     * 
     * @return bool true if FeedSubmissionInfo property is set
     */
    public function isSetFeedSubmissionInfo()
    {
        return !is_null($this->fields['FeedSubmissionInfo']['FieldValue']);

    }




}