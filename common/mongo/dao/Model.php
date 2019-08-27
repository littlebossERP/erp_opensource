<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午2:07
 */

namespace common\mongo\dao;


abstract class Model
{
  abstract public function getCollectionName(); 
}