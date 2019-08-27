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
 * Marketplace Web Service  Exception provides details of errors 
 * returned by Marketplace Web Service  service
 *
 */
class MarketplaceWebService_Exception extends Exception

{
    /** @var string */
    protected $message = null;
    /** @var int */
    private $statusCode = -1;
    /** @var string */
    private $errorCode = null;
    /** @var string */
    private $errorType = null;
    /** @var string */
    private $requestId = null;
    /** @var string */
    private $xml = null;

    private $responseHeaderMetadata = null;
   

    /**
     * Constructs MarketplaceWebService_Exception
     * @param array $errorInfo details of exception.
     * Keys are:
     * <ul>
     * <li>Message - (string) text message for an exception</li>
     * <li>StatusCode - (int) HTTP status code at the time of exception</li>
     * <li>ErrorCode - (string) specific error code returned by the service</li>
     * <li>ErrorType - (string) Possible types:  Sender, Receiver or Unknown</li>
     * <li>RequestId - (string) request id returned by the service</li>
     * <li>XML - (string) compete xml response at the time of exception</li>
     * <li>Exception - (Exception) inner exception if any</li>
     * </ul>
     *         
     */
    public function __construct(array $errorInfo = array())
    {
        $this->message = $errorInfo["Message"];
        parent::__construct($this->message);
        if (array_key_exists("Exception", $errorInfo)) {
            $exception = $errorInfo["Exception"];
            if ($exception instanceof MarketplaceWebService_Exception) {
                $this->statusCode = $exception->getStatusCode();
                $this->errorCode = $exception->getErrorCode();
                $this->errorType = $exception->getErrorType();
                $this->requestId = $exception->getRequestId();
                $this->xml= $exception->getXML();
                $this->responseHeaderMetadata = $exception->getResponseHeaderMetadata();
            } 
        } else {
            if(isset($errorInfo["StatusCode"])) $this->statusCode = $errorInfo["StatusCode"];
            if(isset($errorInfo["ErrorCode"])) $this->errorCode = $errorInfo["ErrorCode"];
            if(isset($errorInfo["ErrorType"])) $this->errorType = $errorInfo["ErrorType"];
            if(isset($errorInfo["RequestId"])) $this->requestId = $errorInfo["RequestId"];
            if(isset($errorInfo["XML"])) $this->xml= $errorInfo["XML"];
            if(isset($errorInfo["ResponseHeaderMetadata"])) $this->xml= $errorInfo["ResponseHeaderMetadata"];
        }
    }

    /**
     * Gets error type returned by the service if available.
     *
     * @return string Error Code returned by the service
     */
    public function getErrorCode(){
        return $this->errorCode;
    }
   
    /**
     * Gets error type returned by the service.
     *
     * @return string Error Type returned by the service.
     * Possible types:  Sender, Receiver or Unknown
     */
    public function getErrorType(){
        return $this->errorType;
    }
    
    
    /**
     * Gets error message
     *
     * @return string Error message
     */
    public function getErrorMessage() {
        return $this->message;
    }
    
    /**
     * Gets status code returned by the service if available. If status
     * code is set to -1, it means that status code was unavailable at the
     * time exception was thrown
     *
     * @return int status code returned by the service
     */
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    /**
     * Gets XML returned by the service if available.
     *
     * @return string XML returned by the service
     */
    public function getXML() {
        return $this->xml;
    }
    
    /**
     * Gets Request ID returned by the service if available.
     *
     * @return string Request ID returned by the service
     */
    public function getRequestId() {
        return $this->requestId;
    }

    public function getResponseHeaderMetadata() {
        return $this->responseHeaderMetadata;
    }
}
