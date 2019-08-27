<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午1:19
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\Model;

class CommentRule extends Model
{
    public $uid;
    public $score;
    public $sellerIdList;
    public $content;
    public $isCommentIssue;
    public $countryList;
    public $isUse;
    public $platform;
    public $createTime;
    public $updateTime;

    public function getCollectionName()
    {
        return CommentConstances::COMMENT_RULE;
    }
}