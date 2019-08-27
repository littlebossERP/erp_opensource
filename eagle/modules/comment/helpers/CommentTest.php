<?php namespace eagle\modules\comment\helpers;

use eagle\models\SaasAliexpressUser;
use eagle\modules\order\models\OdOrder;
use eagle\modules\comment\models\CmCommentLog;

class CommentTest 
{


	static function getSiteId($order_source_order_id){
		return self::getOrderInfo($order_source_order_id)->selleruserid;
	}


	static function getNonCmOrders($selleruserid){
		$orders = CommentHelper::aliexpressNonHaopingOrders($selleruserid);
		return $orders;
	}

	static function getOrderInfo($order_source_order_id){
		return OdOrder::find()->where([
			'order_source_order_id'=>$order_source_order_id
		])->one();
	}


	static function test($order_source_order_id){
		$order = self::getOrderInfo($order_source_order_id);
		// 是否在nonCm中
		$nonCmOrders = self::getNonCmOrders($order->selleruserid);

		$rs = in_array($order_source_order_id, $nonCmOrders);
		echo '是否在平台接口列表中？'.var_export($rs,true).PHP_EOL;
		echo '订单的好评状态? is_comment_status:'.$order->is_comment_status.',is_comment_ignore:'.$order->is_comment_ignore.PHP_EOL;

		echo '好评日志查询结果:'.PHP_EOL;

		$logs = CmCommentLog::find()->where([
			'order_source_order_id'=>$order_source_order_id
		])->asArray()->all();

		var_dump($logs);

	}


}