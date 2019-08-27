<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/7/5
 * Time: 下午1:43
 */

namespace common\mongo\lljListing;


class TaskStatus
{
    const PROCESS_SUCCESS = "PROCESS_SUCCESS";
    const READY = "READY";
    const PROCESSING="PROCESSING";
    const PARTIAL_FAILED="PARTIAL_FAILED";
    const RETRIEVING="RETRIEVING";
    const RETRIEVE_SUCCESS="RETRIEVE_SUCCESS";
    const RETRY_END="RETRY_END";
}