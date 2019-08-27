<?php

namespace common\mongo\log;
use common\mongo\dao\DAO;

/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/28
 * Time: 下午12:42
 */
class LogDAO extends DAO
{
    protected static $db=null;
    public function __construct()
    {
        parent::__construct();
        self::$db=self::$MONGO_CLIENT->selectDB("log");
    }
}