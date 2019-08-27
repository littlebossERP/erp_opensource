<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\order\helpers;

use \Yii;
use eagle\models\SaasAliexpressAutosync;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\models\QueueAliexpressGetorder;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TranslateHelper;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\modules\manual_sync\models\Queue;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use eagle\models\QueueAliexpressGetorder4;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\models\SaasAliexpressUser;
use eagle\modules\tracking\helpers\TrackingHelper;
use yii\helpers\Url;
use eagle\modules\delivery\helpers\DeliveryHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\order\models\OdOrderItem;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;

class ShopeeOrderHelper{
    
    static $shopeeStatus=[
        ''=>'',
        'UNPAID'=>'UNPAID',
        'READY_TO_SHIP'=>'READY_TO_SHIP',
        'SHIPPED'=>'SHIPPED',
        'TO_CONFIRM_RECEIVE'=>'TO_CONFIRM_RECEIVE',
        'CANCELLED'=>'CANCELLED',
        'INVALID'=>'INVALID',
        'TO_RETURN'=>'TO_RETURN',
        'COMPLETED'=>'COMPLETED',
        'IN_CANCEL'=>'IN_CANCEL',
        'RETRY_SHIP'=>'RETRY_SHIP',
    ];
    
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 shopee dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid		uid
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/04		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOmsDashBoardCache($uid){
		$platform = 'shopee';
	
		$cacheData = RedisHelper::RedisGet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
		if (!empty($cacheData))
			return json_decode($cacheData,true);
		else
			return[];
	
	}//end of getOmsDashBoardCache
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据订单的流程生成 操作列表数组
	 +---------------------------------------------------------------------------------------------
	 * @param $code		当前操作的订单流程关键值
	 * @param $type		s = single 单独操作 ， b = batch 批量操作
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/04		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getCurrentOperationList($code , $type="s"){
		$OpList = OrderHelper::getCurrentOperationList($code,$type);
	
		switch ($code){
			case OdOrder::STATUS_PAY:
				$OpList += [ 'signshipped'=>'虚拟发货(标记发货)'];
				break;
			case OdOrder::STATUS_WAITSEND:
				$OpList += ['signshipped'=>'虚拟发货(标记发货)'];
				break;
			case OdOrder::STATUS_SHIPPED:
				$OpList += ['signshipped'=>'虚拟发货(标记发货)'];
				break;
		}
		if ($type =='s')
			$OpList += ['invoiced' => '发票'];
		return $OpList;
	}//end of getAliexpressCurrentOperationList
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计Left menu 上的order 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform					平台
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/04		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMenuStatisticData($params=[],$platform='shopee'){
		/*
			if (!empty($_SESSION['ali_oms_left_menu']) ){
		return $_SESSION['ali_oms_left_menu'];
		}
		*/
		$counter = OrderHelper::getMenuStatisticData($platform,$params);
	
		$OrderQuery = OdOrder::find()->where('order_source = "'.$platform.'"');
	
		$OrderQuery->andWhere(['isshow'=>'Y']);
		if (!empty($params)){
			$OrderQuery->andWhere($params);
		}
	
		$QueryConditionList = [
		OdOrder::STATUS_PAY=>[['order_status'=>OdOrder::STATUS_PAY] , ['not in' , "ifnull(order_source_status,'')", 'RISK_CONTROL'] ,['order_relation'=>['normal','sm','fs','ss']]],

		###############################通知平台发货统计，暂时################################################################################################
		//等待通知平台发货
		'shipping_status_0'=>[['order_source_status'=>'WAIT_SELLER_SEND_GOODS'],['shipping_status'=>OdOrder::NO_INFORM_DELIVERY ]],
		//通知平台发货中
		'shipping_status_2'=>['shipping_status'=>OdOrder::PROCESS_INFORM_DELIVERY],
		//已通知平台发货
		'shipping_status_1'=>['order_source_status'=>['SELLER_PART_SEND_GOODS' , 'WAIT_BUYER_ACCEPT_GOODS']],
		###############################通知平台发货统计，暂时################################################################################################
	
		];
	
		foreach($QueryConditionList as $key =>$QueryCondition){
			$cloneQuery = clone $OrderQuery;
				
			if (isset($params)){
				$cloneQuery->andWhere($params);
			}
				
			// 查询条件只有一个 ， 或者 查询条件的第一个键值是字符串 ‘in’
			if(count($QueryCondition) == 1 ||  in_array($QueryCondition[0], ['in'])){
				$counter[$key] = $cloneQuery->andWhere($QueryCondition)->count();
			}else{
				foreach($QueryCondition as $tmpCondition){
					$cloneQuery->andWhere($tmpCondition);
				}
				//echo $cloneQuery->createCommand()->getRawSql();
				$counter[$key] =$cloneQuery->count();
			}
				
		}
	
		return $counter;
	}
	

}