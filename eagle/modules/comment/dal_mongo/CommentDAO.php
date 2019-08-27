<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 上午11:03
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\DAO;

class CommentDAO extends DAO
{
    protected static $DB = null;

    public function __construct()
    {
        if (empty(self::$DB)) {
            parent::__construct();
            self::$DB = self::$MONGO_CLIENT->selectDB($mongoConfig= \Yii::$app->params['mongodb']['commentDB']);
        }
    }
}