<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/7/5
 * Time: 下午1:35
 */

namespace common\mongo\lljListing;


use common\mongo\dao\DAO;
use common\mongo\manager\iManagerFactory;
use common\mongo\manager\Manager;

class LLJListingManagerFactory extends DAO implements iManagerFactory
{
    private static $daoInstance;
    private static $managers = array();
    public function getManagerByStr($collectionName)
    {
        if (empty(self::$managers[$collectionName])) {
            self::$managers[$collectionName] = new Manager(self::$MONGO_CLIENT->selectDB(LLJListingDBConfig::DB_NAME)->createCollection($collectionName));
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