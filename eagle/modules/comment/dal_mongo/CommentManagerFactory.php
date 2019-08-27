<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午2:02
 */

namespace eagle\modules\comment\dal_mongo;


use common\mongo\dao\Model;
use common\mongo\manager\iManagerFactory;
use common\mongo\manager\Manager;

class CommentManagerFactory extends CommentDAO implements iManagerFactory
{
    private static $daoInstance;
    private static $managers = array();

    public function getManager(Model $type)
    {
        $collectionName = $type->getCollectionName();
        return $this->getManagerByStr($collectionName);
    }

    public function getManagerByStr($collectionName)
    {
        if (empty(self::$managers[$collectionName])) {
            self::$managers[$collectionName] = new Manager(self::$DB->createCollection($collectionName));
        }
        return self::$managers[$collectionName];
    }

    public static function getManagerByStatic($collectionName)
    {
        if (empty(self::$daoInstance)) {
            self::$daoInstance = new self();
        }
        return self::$daoInstance->getManagerByStr($collectionName);
    }
}