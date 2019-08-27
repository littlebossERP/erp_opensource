<?php namespace eagle\modules\assistant\models;

use Yii;
use \eagle\modules\order\models\OdOrder;


class DpInfo extends \eagle\models\assistant\DpInfo
{

	const STATUS_PAY = 2;
	const STATUS_NOPAY = 1;


	const TYPE_DUE_MSG = 1; 		// 催款消息
	const TYPE_ORDER_MSG = 2; 		// 订单留言

	function getSource_id(){
		return $this->hasOne(OdOrder::className(),[
			'order_id'=>'order_id'
		]);
	}


}