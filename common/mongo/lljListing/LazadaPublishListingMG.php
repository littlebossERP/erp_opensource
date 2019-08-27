<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/7/6
 * Time: 下午1:11
 */

namespace common\mongo\lljListing;

/**
 * 刊登产品信息
 * 一条数据是一个变体,有可能是父变体,有可能是子类变体
 * Class LazadaPublishListingMG
 * @package common\mongo\lljListing
 */
class LazadaPublishListingMG
{
//    public $parentId;//父产品的id
    public $uid;//index
    public $lazadaUid;//index
    public $platform;
    public $site;
    public $product;
    public $mainImage;
    public $images;//array
    public $mainImageThumbnail;
    public $imagesThumbnail;
    public $state;//刊登产品的状态,draft,product_upload,product_uploaded,image_upload,image_uploaded,complete,fail
    public $status;//产品状态描述
    public $feedId;//index,请求成功的feedId;如果为父产品,且图片上传成功,则为图片上传后的feedId,子产品不变
    public $feedInfo;//请求失败原因
    public $uploadedProduct;//array,在lazada的产品sku,如果是父类产品则包含所有子类产品sku
    public $createTime;
    public $updateTime;
    public $sellerSku;//index
    public $isParentSku;//标识是否为父变体
    /**
     * [
     *  {
     *      sku:
     *      state:
     *      status:
     *  }
     * ]
     * @var childSku
     */
    public $childSku;//不包含parentSku,所有子产品的状态及状态描述
}