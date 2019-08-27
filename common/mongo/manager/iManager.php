<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 上午11:38
 */

namespace common\mongo\manager;


interface iManager
{
    public function batchInsert(array $ar);
    public function count(array $ar);
    public function createIndex(array $ar);
    public function find(array $ar);
    public function findAndModify(array $query, array $update = NULL, array $fields = NULL, array $options = NULL);
    public function findOne(array $ar = array(), array $fields = array());
    public function getName();
    public function insert($obj,array $opt=array());
    public function save($obj,array $opt);
    public function update(array $criteria,array $new_obj,array $opt);
    public function remove(array $criteria=array(),array $options=array());
}