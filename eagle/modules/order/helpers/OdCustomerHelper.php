<?php
namespace eagle\modules\order\helpers;

use eagle\modules\order\models\OdCustomer;
use common\helpers\Helper_Currency;

class OdCustomerHelper
{

	/**
	 * 增加用户的 订单数量 和购买总金额
	 */
	static function AddOrderNumAmount(&$MC,$amount,$currency='USD',$num=1){
		//统计订单数量和金额
		$MC->accumulated_order_amount = $MC->accumulated_order_amount + $num;
		$all2usd = Helper_Currency::convert($amount, "USD", $currency);	
		$MC->accumulated_trading_amount = $MC->accumulated_trading_amount + $all2usd;
		return $MC;
	}

	/**
	 * 清除无效
	 * 
	 */
	static function removeInvalid(&$inputArr){
		// 清除  Invalid Request
		foreach(array('email','consignee_email','consignee_phone') as $k){ 
			if(empty($inputArr[$k]) || $inputArr[$k]=='Invalid Request'){
				$inputArr[$k]=null;
			}
		}

		//  清除空项 
		foreach($inputArr as $k=>$v){
			if(empty($v)){
				unset($inputArr[$k]);	
			}
		}
	}

	/***
	 * @author lxqun
	 * 保存 买家信息
	 *
	 */
	static function saveCustomer($buyeruserid, $selleruserid, &$MMO){
		if($MMO->customer_id){ // 跳过不必重复保存
			return true;
		}
		//Queue_Getbuyeruser::Add($selleruserid,$buyeruserid);
		$MC = OdCustomer::find()->where('user_source = :us AND seller_platform_uid = :spu AND customer_platform_uid = :cpu',array(':us'=>$MMO->order_source,':spu'=>$selleruserid,':cpu'=>$buyeruserid))->one();
		if (is_null($MC)){
			$MC=new OdCustomer();
		}
		$C_v = array();
		if($MC->isNewRecord){
			$C_v = array(
				'seller_platform_uid' => $selleruserid,
				'customer_platform_uid' => $buyeruserid,
				'user_source' => $MMO['order_source'],
			);
		}
		if (empty($MC->create_time)) {
			$C_v += array(
				'create_time' => time()
			);
		}
		if($MC->isNewRecord || empty($MC->consignee) || empty($MC->email)) {
			$C_v += array(  // 从订单中取出 地址信息 .
				'customer_email' => $MMO['consignee_email'],
			);
			OdCustomerHelper::removeInvalid($C_v);
			$MC->setAttributes($C_v,false);
			$MC = OdCustomerHelper::AddOrderNumAmount($MC,$MMO->grand_total,$MMO->currency);
			$MC->update_time = time();
			$MC->save(false);
		}
		// 增加用户的 订单数量 和购买总金额

		if($MMO->customer_id != $MC->id){
			$MMO->customer_id = $MC->id;
			$MMO->save(false);
		}
	}
}
