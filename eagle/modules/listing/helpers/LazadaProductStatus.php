<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/22
 * Time: 上午11:30
 */

namespace eagle\modules\listing\helpers;

/**
 * Class LazadaProductStatus
 * @package eagle\modules\listing\helpers
 * lazada产品刊登状态,对应GetProducts接口的Filter字段
 */
class LazadaProductStatus
{
//产品所有可能的状态all, live, inactive, deleted, image-missing, pending, rejected, sold-out
    /**
     * all 包含live,rejected,pending,inactive,sold-out,image-missing,不包含deleted
     * live 不包含inactive,deleted,sold-out,rejected,包含pending,image-missing
     *产品上传完成:image-missing,图片上传完成:pending,live的产品才算发布成功
     * 后台看到image-missing状态的有可能是已经被删除了的
     */
    const ALL = "all";
    const LIVE = "live";
    const INACTIVE = "inactive";
    const DELETED = "deleted";
    const IMAGE_MISSING = "image-missing";
    const PENDING = "pending";
    const REJECTED = "rejected";
    const SOLD_OUT = "sold-out";
    public static $STATUS_POSITION=array(self::ALL=>0,self::LIVE=>1,self::INACTIVE=>2,self::DELETED=>3,self::IMAGE_MISSING=>4,self::PENDING=>5,self::REJECTED=>6,self::SOLD_OUT=>7);

    // 在线商品的修改状态
    const PUTING_ON = 1;
    const PUTING_OFF = 2;
    const EDITING_QUANTITY = 3;
    const EDITING_PRICE = 4;
    const EDITING_SALESINFO = 5;
    const EDITING_SUCCESS = 6;
    const EDITING_FAIL = 7;
    public static $LISTING_EDITING_TYPE_MAP = array(
        1 => "上架中",
        2 => "下架中",
        3 => "正在修改库存",
        4 => "正在修改价格",
        5 => "正在修改促销信息",
        6 => "修改成功，等待产品同步更新产品信息",
        7 => "修改失败",
    );
}