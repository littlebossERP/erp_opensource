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
 *  @see MarketplaceWebService_Interface
 */
 require_once (dirname(__FILE__).'/Interface.php'); 

/**
 * The Amazon Marketplace Web Service contain APIs for inventory and order management.
 * 
 */
class  MarketplaceWebService_Mock implements MarketplaceWebService_Interface
{
    // Public API ------------------------------------------------------------//

            
    /**
     * Get Report 
     * The GetReport operation returns the contents of a report. Reports can potentially be
     * very large (>100MB) which is why we only return one report at a time, and in a
     * streaming fashion.
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReport.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReport request or MarketplaceWebService_Model_GetReport object itself
     * @see MarketplaceWebService_Model_GetReport
     * @return MarketplaceWebService_Model_GetReportResponse MarketplaceWebService_Model_GetReportResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReport($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportResponse.php');
        return MarketplaceWebService_Model_GetReportResponse::fromXML($this->invoke('GetReport'));
    }


            
    /**
     * Get Report Schedule Count 
     * returns the number of report schedules
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReportScheduleCount.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReportScheduleCount request or MarketplaceWebService_Model_GetReportScheduleCount object itself
     * @see MarketplaceWebService_Model_GetReportScheduleCount
     * @return MarketplaceWebService_Model_GetReportScheduleCountResponse MarketplaceWebService_Model_GetReportScheduleCountResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReportScheduleCount($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportScheduleCountResponse.php');
        return MarketplaceWebService_Model_GetReportScheduleCountResponse::fromXML($this->invoke('GetReportScheduleCount'));
    }


            
    /**
     * Get Report Request List By Next Token 
     * retrieve the next batch of list items and if there are more items to retrieve
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReportRequestListByNextToken.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReportRequestListByNextToken request or MarketplaceWebService_Model_GetReportRequestListByNextToken object itself
     * @see MarketplaceWebService_Model_GetReportRequestListByNextToken
     * @return MarketplaceWebService_Model_GetReportRequestListByNextTokenResponse MarketplaceWebService_Model_GetReportRequestListByNextTokenResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReportRequestListByNextToken($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportRequestListByNextTokenResponse.php');
        return MarketplaceWebService_Model_GetReportRequestListByNextTokenResponse::fromXML($this->invoke('GetReportRequestListByNextToken'));
    }


            
    /**
     * Update Report Acknowledgements 
     * The UpdateReportAcknowledgements operation updates the acknowledged status of one or more reports.
     *   
     * @see http://docs.amazonwebservices.com/${docPath}UpdateReportAcknowledgements.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_UpdateReportAcknowledgements request or MarketplaceWebService_Model_UpdateReportAcknowledgements object itself
     * @see MarketplaceWebService_Model_UpdateReportAcknowledgements
     * @return MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function updateReportAcknowledgements($request) 
    {
        // require_once ('MarketplaceWebService/Model/UpdateReportAcknowledgementsResponse.php');
        return MarketplaceWebService_Model_UpdateReportAcknowledgementsResponse::fromXML($this->invoke('UpdateReportAcknowledgements'));
    }


            
    /**
     * Submit Feed 
     * Uploads a file for processing together with the necessary
     * metadata to process the file, such as which type of feed it is.
     * PurgeAndReplace if true means that your existing e.g. inventory is
     * wiped out and replace with the contents of this feed - use with
     * caution (the default is false).
     *   
     * @see http://docs.amazonwebservices.com/${docPath}SubmitFeed.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_SubmitFeed request or MarketplaceWebService_Model_SubmitFeed object itself
     * @see MarketplaceWebService_Model_SubmitFeed
     * @return MarketplaceWebService_Model_SubmitFeedResponse MarketplaceWebService_Model_SubmitFeedResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function submitFeed($request) 
    {
        // require_once ('MarketplaceWebService/Model/SubmitFeedResponse.php');
        return MarketplaceWebService_Model_SubmitFeedResponse::fromXML($this->invoke('SubmitFeed'));
    }


            
    /**
     * Get Report Count 
     * returns a count of reports matching your criteria;
     * by default, the number of reports generated in the last 90 days,
     * regardless of acknowledgement status
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReportCount.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReportCount request or MarketplaceWebService_Model_GetReportCount object itself
     * @see MarketplaceWebService_Model_GetReportCount
     * @return MarketplaceWebService_Model_GetReportCountResponse MarketplaceWebService_Model_GetReportCountResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReportCount($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportCountResponse.php');
        return MarketplaceWebService_Model_GetReportCountResponse::fromXML($this->invoke('GetReportCount'));
    }


            
    /**
     * Get Feed Submission List By Next Token 
     * retrieve the next batch of list items and if there are more items to retrieve
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetFeedSubmissionListByNextToken.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetFeedSubmissionListByNextToken request or MarketplaceWebService_Model_GetFeedSubmissionListByNextToken object itself
     * @see MarketplaceWebService_Model_GetFeedSubmissionListByNextToken
     * @return MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getFeedSubmissionListByNextToken($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetFeedSubmissionListByNextTokenResponse.php');
        return MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenResponse::fromXML($this->invoke('GetFeedSubmissionListByNextToken'));
    }


            
    /**
     * Cancel Feed Submissions 
     * cancels feed submissions - by default all of the submissions of the
     * last 30 days that have not started processing
     *   
     * @see http://docs.amazonwebservices.com/${docPath}CancelFeedSubmissions.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_CancelFeedSubmissions request or MarketplaceWebService_Model_CancelFeedSubmissions object itself
     * @see MarketplaceWebService_Model_CancelFeedSubmissions
     * @return MarketplaceWebService_Model_CancelFeedSubmissionsResponse MarketplaceWebService_Model_CancelFeedSubmissionsResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function cancelFeedSubmissions($request) 
    {
        // require_once ('MarketplaceWebService/Model/CancelFeedSubmissionsResponse.php');
        return MarketplaceWebService_Model_CancelFeedSubmissionsResponse::fromXML($this->invoke('CancelFeedSubmissions'));
    }


            
    /**
     * Request Report 
     * requests the generation of a report
     *   
     * @see http://docs.amazonwebservices.com/${docPath}RequestReport.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_RequestReport request or MarketplaceWebService_Model_RequestReport object itself
     * @see MarketplaceWebService_Model_RequestReport
     * @return MarketplaceWebService_Model_RequestReportResponse MarketplaceWebService_Model_RequestReportResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function requestReport($request) 
    {
        // require_once ('MarketplaceWebService/Model/RequestReportResponse.php');
        return MarketplaceWebService_Model_RequestReportResponse::fromXML($this->invoke('RequestReport'));
    }


            
    /**
     * Get Feed Submission Count 
     * returns the number of feeds matching all of the specified criteria
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetFeedSubmissionCount.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetFeedSubmissionCount request or MarketplaceWebService_Model_GetFeedSubmissionCount object itself
     * @see MarketplaceWebService_Model_GetFeedSubmissionCount
     * @return MarketplaceWebService_Model_GetFeedSubmissionCountResponse MarketplaceWebService_Model_GetFeedSubmissionCountResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getFeedSubmissionCount($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetFeedSubmissionCountResponse.php');
        return MarketplaceWebService_Model_GetFeedSubmissionCountResponse::fromXML($this->invoke('GetFeedSubmissionCount'));
    }


            
    /**
     * Cancel Report Requests 
     * cancels report requests that have not yet started processing,
     * by default all those within the last 90 days
     *   
     * @see http://docs.amazonwebservices.com/${docPath}CancelReportRequests.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_CancelReportRequests request or MarketplaceWebService_Model_CancelReportRequests object itself
     * @see MarketplaceWebService_Model_CancelReportRequests
     * @return MarketplaceWebService_Model_CancelReportRequestsResponse MarketplaceWebService_Model_CancelReportRequestsResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function cancelReportRequests($request) 
    {
        // require_once ('MarketplaceWebService/Model/CancelReportRequestsResponse.php');
        return MarketplaceWebService_Model_CancelReportRequestsResponse::fromXML($this->invoke('CancelReportRequests'));
    }


            
    /**
     * Get Report List 
     * returns a list of reports; by default the most recent ten reports,
     * regardless of their acknowledgement status
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReportList.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReportList request or MarketplaceWebService_Model_GetReportList object itself
     * @see MarketplaceWebService_Model_GetReportList
     * @return MarketplaceWebService_Model_GetReportListResponse MarketplaceWebService_Model_GetReportListResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReportList($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportListResponse.php');
        return MarketplaceWebService_Model_GetReportListResponse::fromXML($this->invoke('GetReportList'));
    }


            
    /**
     * Get Feed Submission Result 
     * retrieves the feed processing report
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetFeedSubmissionResult.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetFeedSubmissionResult request or MarketplaceWebService_Model_GetFeedSubmissionResult object itself
     * @see MarketplaceWebService_Model_GetFeedSubmissionResult
     * @return MarketplaceWebService_Model_GetFeedSubmissionResultResponse MarketplaceWebService_Model_GetFeedSubmissionResultResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getFeedSubmissionResult($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetFeedSubmissionResultResponse.php');
        return MarketplaceWebService_Model_GetFeedSubmissionResultResponse::fromXML($this->invoke('GetFeedSubmissionResult'));
    }


            
    /**
     * Get Feed Submission List 
     * returns a list of feed submission identifiers and their associated metadata
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetFeedSubmissionList.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetFeedSubmissionList request or MarketplaceWebService_Model_GetFeedSubmissionList object itself
     * @see MarketplaceWebService_Model_GetFeedSubmissionList
     * @return MarketplaceWebService_Model_GetFeedSubmissionListResponse MarketplaceWebService_Model_GetFeedSubmissionListResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getFeedSubmissionList($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetFeedSubmissionListResponse.php');
        return MarketplaceWebService_Model_GetFeedSubmissionListResponse::fromXML($this->invoke('GetFeedSubmissionList'));
    }


            
    /**
     * Get Report Request List 
     * returns a list of report requests ids and their associated metadata
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReportRequestList.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReportRequestList request or MarketplaceWebService_Model_GetReportRequestList object itself
     * @see MarketplaceWebService_Model_GetReportRequestList
     * @return MarketplaceWebService_Model_GetReportRequestListResponse MarketplaceWebService_Model_GetReportRequestListResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReportRequestList($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportRequestListResponse.php');
        return MarketplaceWebService_Model_GetReportRequestListResponse::fromXML($this->invoke('GetReportRequestList'));
    }


            
    /**
     * Get Report Schedule List By Next Token 
     * retrieve the next batch of list items and if there are more items to retrieve
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReportScheduleListByNextToken.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReportScheduleListByNextToken request or MarketplaceWebService_Model_GetReportScheduleListByNextToken object itself
     * @see MarketplaceWebService_Model_GetReportScheduleListByNextToken
     * @return MarketplaceWebService_Model_GetReportScheduleListByNextTokenResponse MarketplaceWebService_Model_GetReportScheduleListByNextTokenResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReportScheduleListByNextToken($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportScheduleListByNextTokenResponse.php');
        return MarketplaceWebService_Model_GetReportScheduleListByNextTokenResponse::fromXML($this->invoke('GetReportScheduleListByNextToken'));
    }


            
    /**
     * Get Report List By Next Token 
     * retrieve the next batch of list items and if there are more items to retrieve
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReportListByNextToken.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReportListByNextToken request or MarketplaceWebService_Model_GetReportListByNextToken object itself
     * @see MarketplaceWebService_Model_GetReportListByNextToken
     * @return MarketplaceWebService_Model_GetReportListByNextTokenResponse MarketplaceWebService_Model_GetReportListByNextTokenResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReportListByNextToken($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportListByNextTokenResponse.php');
        return MarketplaceWebService_Model_GetReportListByNextTokenResponse::fromXML($this->invoke('GetReportListByNextToken'));
    }


            
    /**
     * Manage Report Schedule 
     * Creates, updates, or deletes a report schedule
     * for a given report type, such as order reports in particular.
     *   
     * @see http://docs.amazonwebservices.com/${docPath}ManageReportSchedule.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_ManageReportSchedule request or MarketplaceWebService_Model_ManageReportSchedule object itself
     * @see MarketplaceWebService_Model_ManageReportSchedule
     * @return MarketplaceWebService_Model_ManageReportScheduleResponse MarketplaceWebService_Model_ManageReportScheduleResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function manageReportSchedule($request) 
    {
        // require_once ('MarketplaceWebService/Model/ManageReportScheduleResponse.php');
        return MarketplaceWebService_Model_ManageReportScheduleResponse::fromXML($this->invoke('ManageReportSchedule'));
    }


            
    /**
     * Get Report Request Count 
     * returns a count of report requests; by default all the report
     * requests in the last 90 days
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReportRequestCount.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReportRequestCount request or MarketplaceWebService_Model_GetReportRequestCount object itself
     * @see MarketplaceWebService_Model_GetReportRequestCount
     * @return MarketplaceWebService_Model_GetReportRequestCountResponse MarketplaceWebService_Model_GetReportRequestCountResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReportRequestCount($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportRequestCountResponse.php');
        return MarketplaceWebService_Model_GetReportRequestCountResponse::fromXML($this->invoke('GetReportRequestCount'));
    }


            
    /**
     * Get Report Schedule List 
     * returns the list of report schedules
     *   
     * @see http://docs.amazonwebservices.com/${docPath}GetReportScheduleList.html      
     * @param mixed $request array of parameters for MarketplaceWebService_Model_GetReportScheduleList request or MarketplaceWebService_Model_GetReportScheduleList object itself
     * @see MarketplaceWebService_Model_GetReportScheduleList
     * @return MarketplaceWebService_Model_GetReportScheduleListResponse MarketplaceWebService_Model_GetReportScheduleListResponse
     *
     * @throws MarketplaceWebService_Exception
     */
    public function getReportScheduleList($request) 
    {
        // require_once ('MarketplaceWebService/Model/GetReportScheduleListResponse.php');
        return MarketplaceWebService_Model_GetReportScheduleListResponse::fromXML($this->invoke('GetReportScheduleList'));
    }

    // Private API ------------------------------------------------------------//

    private function invoke($actionName)
    {
        return $xml = file_get_contents('MarketplaceWebService/Mock/' . $actionName . 'Response.xml', /** search include path */ TRUE);
    }
}