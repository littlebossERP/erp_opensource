<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午1:13
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\Model;

class CommentLog extends Model
{
    public $uid;
    public $orderId;
    public $orderSourceOrderId;
    public $sellerUserId;
    public $platform;
    public $sourceBuyerUserId;
    public $subTotal;
    public $currency;
    public $paidTime;
    public $content;
    /*
            * isSuccess:
            * 0:尚未好评
            * 1:好评成功
            * 2:好评中
            * 3:好评失败
            * 4:好评失败,重试有可能成功
            */
    public $isSuccess;

    public $errorMsg;
    public $createTime;
    public $ruleId;
    public $orderSourceCreateTime;

    public function getCollectionName()
    {
        return CommentConstances::COMMENT_LOG;
    }
}