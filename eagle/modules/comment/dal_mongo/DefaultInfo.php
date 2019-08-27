<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/2
 * Time: 上午10:11
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\Model;

class DefaultInfo extends Model
{
    public $uid;
    public $infoType;
    public $content;

    public function getCollectionName()
    {
        return "default-info";
    }
}