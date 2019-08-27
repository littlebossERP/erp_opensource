<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/7/15
 * Time: 下午3:42
 */

namespace common\mongo\lljListing;


class LazadaCategoriesSingleNodes
{
    public $site;//站点
    public $platform;//所属平台
    public $categoryId;
    public $parentCategoryId;
    public $name;
    public $globalIdentifier;
    public $isLeaf;
    public $updateTime;
    public $level;
}