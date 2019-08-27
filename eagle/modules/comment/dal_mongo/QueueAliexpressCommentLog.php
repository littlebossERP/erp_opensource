<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午1:00
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\Model;

class QueueAliexpressCommentLog extends Model
{
    public $orderId;
    public $score;
    public $feedbackContent;
    public $sellerLoginId;
    public $errorCode;
    public $errorMessage;
    public $success;
    public $updateTime;

    public function getCollectionName()
    {
        return CommentConstances::QUEUE_ALIEXPRESS_COMMENT_LOG;
    }
}