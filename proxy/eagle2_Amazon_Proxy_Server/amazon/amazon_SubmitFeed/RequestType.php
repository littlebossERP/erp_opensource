<?php
/**
 * There are three types of requests to MWS to configure:
 *   1. Requests containing feed data. SubmitFeed is the only example of this request type.
 *   2. Requests expecting report data. GetFeedSubmissionResult and GetReport are the only functions
 *      matching the request type.
 *   3. 'Regular' POST requests. This represents the multitude of MWS requests.
 */
final class RequestType {
  const POST_UPLOAD = 'POST_UPLOAD';
  const POST_DOWNLOAD = 'POST_DOWNLOAD';
  const POST_DEFAULT = 'POST_DEFAULT';
  const UNKNOWN = 'UNKNOWN';

  public static function getRequestType($action) {
    $requestType = null;
    switch ($action) {
      case 'SubmitFeed':
        $requestType = self::POST_UPLOAD;
        break;
      case 'GetFeedSubmissionResult':
      case 'GetReport':
        $requestType = self::POST_DOWNLOAD;
        break;
      case 'GetFeedSubmissionList':
      case 'GetFeedSubmissionListByNextToken':
      case 'GetFeedSubmissionCount':
      case 'CancelFeedSubmissions':
      case 'RequestReport':
      case 'GetReportRequestList':
      case 'GetReportRequestListByNextToken':
      case 'GetReportRequestCount':
      case 'CancelReportRequests':
      case 'GetReportList':
      case 'GetReportListByNextToken':
      case 'GetReportCount':
      case 'ManageReportSchedule':
      case 'GetReportScheduleList':
      case 'GetReportScheduleListByNextToken':
      case 'GetReportScheduleCount':
      case 'UpdateReportAcknowledgements':
        $requestType = self::POST_DEFAULT;
        break;
      default:
        $requestType = self::UNKNOWN;
        break;
    }
    
    return $requestType;
  }
}
?>
