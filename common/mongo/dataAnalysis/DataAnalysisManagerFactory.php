<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/29
 * Time: 上午10:49
 */

namespace common\mongo\dataAnalysis;



use common\mongo\dao\DAO;
use common\mongo\manager\iManagerFactory;
use common\mongo\manager\Manager;

class DataAnalysisManagerFactory extends DAO implements iManagerFactory 
{
    private static $daoInstance;
    private static $managers = array();
    

    public function getManagerByStr($collectionName)
    {
        if (empty(self::$managers[$collectionName])) {
            self::$managers[$collectionName] = new Manager(self::$MONGO_CLIENT->selectDB(Config::DB_NAME)->createCollection($collectionName));
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