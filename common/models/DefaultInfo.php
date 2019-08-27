<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/2
 * Time: 上午10:11
 */

namespace common\models;




class DefaultInfo 
{
    public $uid;
    public $infoType;
    /**
     * @var content
     * 当infoType==2时
     * {
     *      platform:
     *      site:
     *      token:
     *      userId:
     * }
     * 当infoType==3时
     * {
     *      url:
     *      quantity:
     * }
     */
    public $content;
    public $updateTime;
}