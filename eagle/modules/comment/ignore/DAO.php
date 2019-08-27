<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 上午11:12
 */

//namespace eagle\modules\comment\dal_mongo;


use MongoClient;

class DAO
{
    protected static $MONGO_CLIENT = null;//共用一个连接
    public function __construct()
    {
        if(empty(self::$MONGO_CLIENT)){
           $mongoConfig= \Yii::$app->params['mongodb'];
//            var_dump($mongoConfig);
           self::$MONGO_CLIENT= new MongoClient("mongodb://".$mongoConfig['instance1']['ip'].":".$mongoConfig['instance1']['port'], array("username" => $mongoConfig['instance1']['user'], "password" => $mongoConfig['instance1']['pwd']));
        }
    }
}