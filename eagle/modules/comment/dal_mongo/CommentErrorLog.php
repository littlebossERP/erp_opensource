<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/20
 * Time: 上午9:52
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\Model;

class CommentErrorLog extends Model
{
    public $uid;
    public $orderId;
    public $msg;
    public $createTime;
    public function getCollectionName()
    {
        return CommentConstances::COMMENT_ERROR_LOG;
    }
}