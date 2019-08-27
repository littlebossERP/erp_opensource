<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/28
 * Time: 下午12:46
 */

namespace common\mongo\log;


use common\helpers\Helper_Util;
use common\mongo\manager\Manager;
use MongoDate;

class LogModel extends LogDAO
{
    public $email;
    public $type = "info";
    public $creatTime;
    private $id;
    private static $manager;

    public function __construct($collectionName)
    {
        parent::__construct();
        $this->creatTime = new MongoDate();
        if (!isset(self::$manager))
            self::$manager = new Manager(self::$db->createCollection($collectionName));
    }

    public function batchInsert(array $ar = array())
    {
        if (!isset($ar) || count($ar) == 0) {
            return "empty array";
        }
        return self::$manager->batchInsert($ar);
    }

    public function count(array $ar)
    {
        return self::$manager->count($ar);
    }

    public function createIndex(array $ar)
    {
        return self::$manager->createIndex($ar);
    }

    public function find(array $ar = array(), array $rtnFields = array())
    {
        if(!empty($ar)){
            return self::$manager->find($ar, $rtnFields);
        }else{
            return self::$manager->find(array("_id"=>$this->id),$rtnFields);
        }
    }
    public function findAll(array $rtnFields = array()){
        return self::$manager->find(array(),$rtnFields);
    }

    public function findAndModify(array $query = array(), array $update = NULL, array $fields = NULL, array $options = NULL)
    {
        if (isset($query)) {
            return self::$manager->findAndModify($query, $update, $fields, $options);
        } else {
            return self::$manager->findAndModify(array("_id"=>$this->id), $update, $fields, $options);
        }
    }

    public function findOne(array $ar = array(), array $fields = array())
    {
        if(!empty($ar)){
            return self::$manager->findOne($ar, $fields);
        }else{
            return self::$manager->findOne(array("_id"=>$this->id),$fields);
        }
    }

    public function getName()
    {
        return self::$manager->getName();
    }

    public function insert(array $opt = array())
    {
        $arr = Helper_Util::getInstancePublicProperties($this);
        $rlt=self::$manager->insert($arr, $opt);
        if($rlt['ok']==1){
            $this->id=$arr["_id"];
        }
        var_dump($this->id);
        return $rlt;
    }

    public function save(array $opt = array())
    {
        $arr = Helper_Util::getInstancePublicProperties($this);
        $rlt=self::$manager->save($arr, $opt);
        if($rlt['ok']==1){
            $this->id=$arr["_id"];
        }
        return $rlt;
    }

    public function update(array $criteria, array $new_obj, array $opt)
    {
        if(!empty($criteria)){
            return self::$manager->update($criteria, $new_obj, $opt);
        }else{
            return self::$manager->update(array("_id"=>$this->id), $new_obj, $opt);
        }
    }

    public function remove(array $criteria = array(), array $options = array())
    {
        if(!empty($criteria)){
            return self::$manager->remove($criteria, $options);
        }else{
            return self::$manager->remove(array("_id"=>$this->id),$options);
        }
    }
    public function getMongoId(){
        return $this->id;
    }
}