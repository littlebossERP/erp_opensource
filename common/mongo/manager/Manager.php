<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 上午11:52
 */

namespace common\mongo\manager;


use MongoCollection;

class Manager implements iManager
{
    protected $collection = null;

    public function __construct(MongoCollection $mc)
    {
        $this->collection = $mc;
    }

    public function batchInsert(array $ar=array())
    {
        if(!isset($ar)||count($ar)==0){
            return "empty array";
        }
        return $this->collection->batchInsert($ar);
    }

    public function count(array $ar)
    {
        return $this->collection->count($ar);
    }

    public function createIndex(array $ar)
    {
        return $this->collection->createIndex($ar);
    }

    public function find(array $ar = array(), array $rtnFields = array())
    {
        return $this->collection->find($ar, $rtnFields);
    }

    public function findAndModify(array $query, array $update = NULL, array $fields = NULL, array $options = NULL)
    {
        return $this->collection->findAndModify($query, $update, $fields, $options);
    }

    public function findOne(array $ar = array(), array $fields = array())
    {
        return $this->collection->findOne($ar, $fields);
    }

    public function getName()
    {
        return $this->collection->getName();
    }

    public function insert($obj, array $opt = array())
    {
        return $this->collection->insert($obj, $opt);
    }

    public function save($obj, array $opt)
    {
        return $this->collection->save($obj, $opt);
    }

    public function update(array $criteria, array $new_obj, array $opt=array())
    {
        return $this->collection->update($criteria, $new_obj, $opt);
    }

    public function remove(array $criteria = array(), array $options = array())
    {
        return $this->collection->remove($criteria, $options);
    }
}