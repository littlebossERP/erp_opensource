<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/25
 * Time: 下午1:51
 */

namespace eagle\modules\listing\helpers;

/**
 * 产品刊登所处的状态
 * 
 * Class ProductListingProcessStatus
 * @package eagle\modules\listing\helpers
 */
class ProductListingProcessStatus
{
    const STATUS_INITIAL=0;//初始状态
    const STATUS_CHECKED=1;//已检查,待调用回调函数
    const STATUS_CHECKED_CALLED=2;//已检查,已调用回调函数
    const STATUS_PENDING=3;//已经进入人工待审核队列
    const STATUS_FAIL=7;//运行有异常,需要后续重试
}