<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/12
 * Time: 下午6:16
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\Model;

class QueueCommentStatus extends Model
{
    public $orderId;
    public $uid;
    public $status;

    public function getCollectionName()
    {
        return CommentConstances::QUEUE_COMMENT_STATUS;
    }
}