<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 上午11:29
 */

namespace eagle\modules\comment\dal_mongo;


use eagle\modules\comment\helpers\IssueStatus;

class CommentConstances
{
    
    const COMMENT_LOG = "comment_log";
    const COMMENT_RULE = "comment_rule";
    const COMMENT_TEMPLATE = "comment_template";
    const QUEUE_ALIEXPRESS_COMMENT = "queue_aliexpress_comment";
    const QUEUE_ALIEXPRESS_COMMENT_LOG = "queue_aliexpress_comment_log";
    const COMMENT_BATCH_SIZE = 100;//评价时的批大小
    const DEFAULT_SCORE = 5;
    const ALL = "ALL";
    const IS_DEBUG = false;
    const ALIEXPRESS='aliexpress';
    const DEFAULT_INFO="default_info";
    const TOTAL_COUNTRY_QUANTITY=225;
    public static $NEED_COMMENT_ORDER_SOURCE_STATUS=array('FINISH', 'FUND_PROCESSING','WAIT_BUYER_ACCEPT_GOODS');
    public static $NEED_COMMENT_ISSUESTATUS=array(IssueStatus::NO_ISSUE);
    const QUEUE_COMMENT_STATUS="queue_comment_status";
    const COMMENT_ERROR_LOG="comment_error_log";
}