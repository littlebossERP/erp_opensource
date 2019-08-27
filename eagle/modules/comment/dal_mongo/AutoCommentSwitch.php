<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午1:07
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\Model;

class AutoCommentSwitch extends Model
{
    public $uid;
    public $subShop;

    public function getCollectionName()
    {
        return "auto_comment_switch";
    }
}