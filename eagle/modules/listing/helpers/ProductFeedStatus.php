<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/25
 * Time: 下午1:16
 */

namespace eagle\modules\listing\helpers;


class ProductFeedStatus
{
    const QUEUED = "Queued";
    const FINISHED = "Finished";
    const ERROR = "Error";
    const PROCESSING="Processing";
    const CANCELED="Canceled";
}