<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午1:59
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\Model;

class CommentTemplate extends Model
{
    public $uid;
    public $content;
    public $createTime;
    public $isUse;
    public $platform;

    public function getCollectionName()
    {
        return CommentConstances::COMMENT_TEMPLATE;
    }
}