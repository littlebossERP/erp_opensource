<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/7/6
 * Time: 上午11:25
 */

namespace common\mongo\lljListing;

/**
 * Class LazadaListing
 * @package common\mongo\lljListing
 * 产品,一条记录表示一个sku,有可能是子sku,也有可能是parentSKU
 */
class LazadaListingMG
{
    public $uid;//index
    public $lazadaUid;//saas_lazada_user主键,index
    public $product;//array 产品信息
    public $creatTime;
    public $updateTime;
    /*
     * 字符串,index;表示产品状态,从右往左依次表示all,live,inactive,deleted,image-missing,pending,rejected,sold-out;
     * example:0000110=6,表示状态为live,inactive
     */
    public $subStatus;//index
//    public $subStatusDesc;//array,产品状态描述
    /**
     * @var isEditing
     * 0 没有在修改
     * 1 上架中
     * 2 下架中
     * 3 正在修改库存
     * 4 正在修改价格
     * 5 正在修改促销信息
     */
    public $isEditing;
    public $feedId;//api请求成功的feedId
    public $errorMsg;
    public $operationLog;//记录客户修改记录
    public $sellerSku;//index
    public $isParent;//是否为parentSku
    public $childSku;//array,如果为parentSku,则表示子sku,不包括父sku
}