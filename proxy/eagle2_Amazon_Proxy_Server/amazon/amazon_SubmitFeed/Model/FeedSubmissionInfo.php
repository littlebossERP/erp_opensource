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
 * MarketplaceWebService_Model_FeedSubmissionInfo
 * 
 * Properties:
 * <ul>
 * 
 * <li>FeedSubmissionId: string</li>
 * <li>FeedType: string</li>
 * <li>SubmittedDate: string</li>
 * <li>FeedProcessingStatus: string</li>
 * <li>StartedProcessingDate: string</li>
 * <li>CompletedProcessingDate: string</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_FeedSubmissionInfo extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_FeedSubmissionInfo
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>FeedSubmissionId: string</li>
     * <li>FeedType: string</li>
     * <li>SubmittedDate: string</li>
     * <li>FeedProcessingStatus: string</li>
     * <li>StartedProcessingDate: string</li>
     * <li>CompletedProcessingDate: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'FeedSubmissionId' => array('FieldValue' => null, 'FieldType' => 'string'),
        'FeedType' => array('FieldValue' => null, 'FieldType' => 'string'),
        'SubmittedDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        'FeedProcessingStatus' => array('FieldValue' => null, 'FieldType' => 'string'),
        'StartedProcessingDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        'CompletedProcessingDate' => array('FieldValue' => null, 'FieldType' => 'DateTime'),
        );
        parent::__construct($data);
    }

        /**
     * Gets the value of the FeedSubmissionId property.
     * 
     * @return string FeedSubmissionId
     */
    public function getFeedSubmissionId() 
    {
        return $this->fields['FeedSubmissionId']['FieldValue'];
    }

    /**
     * Sets the value of the FeedSubmissionId property.
     * 
     * @param string FeedSubmissionId
     * @return this instance
     */
    public function setFeedSubmissionId($value) 
    {
        $this->fields['FeedSubmissionId']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the FeedSubmissionId and returns this instance
     * 
     * @param string $value FeedSubmissionId
     * @return MarketplaceWebService_Model_FeedSubmissionInfo instance
     */
    public function withFeedSubmissionId($value)
    {
        $this->setFeedSubmissionId($value);
        return $this;
    }


    /**
     * Checks if FeedSubmissionId is set
     * 
     * @return bool true if FeedSubmissionId  is set
     */
    public function isSetFeedSubmissionId()
    {
        return !is_null($this->fields['FeedSubmissionId']['FieldValue']);
    }

    /**
     * Gets the value of the FeedType property.
     * 
     * @return string FeedType
     */
    public function getFeedType() 
    {
        return $this->fields['FeedType']['FieldValue'];
    }

    /**
     * Sets the value of the FeedType property.
     * 
     * @param string FeedType
     * @return this instance
     */
    public function setFeedType($value) 
    {
        $this->fields['FeedType']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the FeedType and returns this instance
     * 
     * @param string $value FeedType
     * @return MarketplaceWebService_Model_FeedSubmissionInfo instance
     */
    public function withFeedType($value)
    {
        $this->setFeedType($value);
        return $this;
    }


    /**
     * Checks if FeedType is set
     * 
     * @return bool true if FeedType  is set
     */
    public function isSetFeedType()
    {
        return !is_null($this->fields['FeedType']['FieldValue']);
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
     * @return MarketplaceWebService_Model_FeedSubmissionInfo instance
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
     * Gets the value of the FeedProcessingStatus property.
     * 
     * @return string FeedProcessingStatus
     */
    public function getFeedProcessingStatus() 
    {
        return $this->fields['FeedProcessingStatus']['FieldValue'];
    }

    /**
     * Sets the value of the FeedProcessingStatus property.
     * 
     * @param string FeedProcessingStatus
     * @return this instance
     */
    public function setFeedProcessingStatus($value) 
    {
        $this->fields['FeedProcessingStatus']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the FeedProcessingStatus and returns this instance
     * 
     * @param string $value FeedProcessingStatus
     * @return MarketplaceWebService_Model_FeedSubmissionInfo instance
     */
    public function withFeedProcessingStatus($value)
    {
        $this->setFeedProcessingStatus($value);
        return $this;
    }


    /**
     * Checks if FeedProcessingStatus is set
     * 
     * @return bool true if FeedProcessingStatus  is set
     */
    public function isSetFeedProcessingStatus()
    {
        return !is_null($this->fields['FeedProcessingStatus']['FieldValue']);
    }

    /**
     * Gets the value of the StartedProcessingDate property.
     * 
     * @return string StartedProcessingDate
     */
    public function getStartedProcessingDate() 
    {
        return $this->fields['StartedProcessingDate']['FieldValue'];
    }

    /**
     * Sets the value of the StartedProcessingDate property.
     * 
     * @param string StartedProcessingDate
     * @return this instance
     */
    public function setStartedProcessingDate($value) 
    {
        $this->fields['StartedProcessingDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the StartedProcessingDate and returns this instance
     * 
     * @param string $value StartedProcessingDate
     * @return MarketplaceWebService_Model_FeedSubmissionInfo instance
     */
    public function withStartedProcessingDate($value)
    {
        $this->setStartedProcessingDate($value);
        return $this;
    }


    /**
     * Checks if StartedProcessingDate is set
     * 
     * @return bool true if StartedProcessingDate  is set
     */
    public function isSetStartedProcessingDate()
    {
        return !is_null($this->fields['StartedProcessingDate']['FieldValue']);
    }

    /**
     * Gets the value of the CompletedProcessingDate property.
     * 
     * @return string CompletedProcessingDate
     */
    public function getCompletedProcessingDate() 
    {
        return $this->fields['CompletedProcessingDate']['FieldValue'];
    }

    /**
     * Sets the value of the CompletedProcessingDate property.
     * 
     * @param string CompletedProcessingDate
     * @return this instance
     */
    public function setCompletedProcessingDate($value) 
    {
        $this->fields['CompletedProcessingDate']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the CompletedProcessingDate and returns this instance
     * 
     * @param string $value CompletedProcessingDate
     * @return MarketplaceWebService_Model_FeedSubmissionInfo instance
     */
    public function withCompletedProcessingDate($value)
    {
        $this->setCompletedProcessingDate($value);
        return $this;
    }


    /**
     * Checks if CompletedProcessingDate is set
     * 
     * @return bool true if CompletedProcessingDate  is set
     */
    public function isSetCompletedProcessingDate()
    {
        return !is_null($this->fields['CompletedProcessingDate']['FieldValue']);
    }




}