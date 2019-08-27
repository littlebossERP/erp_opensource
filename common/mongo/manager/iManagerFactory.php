<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午2:22
 */

namespace common\mongo\manager;



interface iManagerFactory
{
    public function getManagerByStr($type);
}