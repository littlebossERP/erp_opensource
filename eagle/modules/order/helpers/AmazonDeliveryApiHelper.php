<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\order\helpers;



use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\models\OdOrder;
use eagle\models\OdOrderShipped;
/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
class AmazonDeliveryApiHelper {
	
	
	
	/**
	 * 标志amazon平台发货。  插入发货队列之前调用该函数来获取该订单更多的信息 
	 * @param unknown $orderId
	 * @param unknown $logisticInfoList
	 * 
	 *
	 */
	public static function getParams($orderId,$logisticInfoList){
		return array("aaa"=>334,"bbb"=>3444);
	}

	
	
}
