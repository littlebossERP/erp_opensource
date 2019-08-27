<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午12:54
 */

namespace eagle\modules\comment\dal_mongo;



use common\mongo\dao\Model;

class QueueAliexpressComment extends Model
{
    public $orderId;
    public $score;
    public $feedbackContent;
    public $sellerLoginId;
    public $updateTime;
    public $uid;
    public function getCollectionName()
    {
        return CommentConstances::QUEUE_ALIEXPRESS_COMMENT;
    }
}