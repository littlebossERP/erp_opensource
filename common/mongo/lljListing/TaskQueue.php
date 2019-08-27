<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/7/5
 * Time: 下午1:41
 */

namespace common\mongo\lljListing;


class TaskQueue
{
    public $config;
    public $reqParams;
    public $action;
    public $isRoot;
    public $taskStatus;
    public $foreignTaskId;
    public $url;
}