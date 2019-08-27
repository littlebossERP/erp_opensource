<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/7/15
 * Time: 下午2:37
 */

namespace common\mongo\lljListing;


class LazadaCategoriesLeafs
{
    public $site;//站点
    public $platform;//所属平台
    public $name;//叶子名称
    public $categoryId;
    public $globalIdentifier;
    /**
     * @var $route 从根到叶子的路径
     * {level:{name,categoryId,globalIdentifier}}
     */
    public $route;
    public $updateTime;
}