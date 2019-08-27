<?php namespace eagle\modules\assistant\services\rule;

use eagle\modules\order\models\OdOrder;
use eagle\models\assistant\DpRule;
use eagle\modules\assistant\models\DpInfo;

class Message extends Due
{
	public $success_count = 0;
	const CS_ORDERMSG = 4;				// 

	protected $msgType = 'message';

	// 设置规则判断的优先级
	protected $ruleFunc = [
		'ruleOrderMsg', 					// 规则是否启用留言
		'hasSendMessage', 					// 是否已经发送过留言
		'rulePayStatus',					// 是否是已付款订单
		'updateDpInfo',
		'hasDueLog', 						// 是否是催过款的订单
	];

	public function ruleOrderMsg(DpRule $rule, OdOrder $order){
		if($rule->order_message){
			$rule->message_content = $rule->order_message;
			return true;
		}else{
			return false;
		}
	}

	public function rulePayStatus(DpRule $rule, OdOrder $order){
		if($order->order_status == OdOrder::STATUS_PAY){
			return true;
		}
		return false;
	}

	public function hasDueLog(DpRule $rule, OdOrder $order){
		$count = $this->getOrderDueTimes($order);
		return !!$count;
	}

	public function hasSendMessage(DpRule $rule, OdOrder $order){
		$count = DpInfo::find()
			->where([
				'source_id'=>$order->order_source_order_id,
				'contacted'=>0,
				'msg_type'=>DpInfo::TYPE_ORDER_MSG
			])
			->count();
		return !$count;
	}

	/**
	 * 更新dpinfo状态
	 * @return [type] [description]
	 */
	public function updateDpInfo(DpRule $rule, OdOrder $order){
		$dpInfo = DpInfo::find()
			->where([
				'source_id'=>$order->order_source_order_id,
				'due_status'=>DpInfo::STATUS_NOPAY
			])
			->all();
		foreach($dpInfo as $info){
			$info->due_status = DpInfo::STATUS_PAY;
			$info->save();
		}
		return true;
	}

}
