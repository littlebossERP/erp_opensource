<?php
namespace eagle\modules\order\helpers;
use yii;
use yii\data\Pagination;

use eagle\models\SaasWishUser;
use eagle\modules\listing\models\WishApiQueue;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\order\models\WishOrder;
use eagle\modules\order\models\WishOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\SysLogHelper ;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;

/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: fanjs
+----------------------------------------------------------------------
| Create Date: 2014-08-01
+----------------------------------------------------------------------
 */
/**
 +------------------------------------------------------------------------------
 * wish api 接口模块 
 +------------------------------------------------------------------------------
 * @category	item
 * @package		Helper/item
 * @subpackage  Exception
 * @author		lkh
 +------------------------------------------------------------------------------
 */
class WishOrderInterface{
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * wish订单发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $order_id					eagle订单号 （必需）
	 * @param $tracking_provider 		发货方式 （必需）
	 * @param $tracking_number 			快递号
	 * @param $ship_note 				发货备注
	 +--------------------------------------------------------------------------------------------- 
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function shipWishOrder($orderid , $tracking_provider , $tracking_number='' , $ship_note=''){
		//check required param
		if (empty($orderid)){
			return array(
				'success' => false,
				'message' => "order id 不能为空"
			);
		}
		
		if (empty($tracking_provider)){
			return array(
				'success' => false,
				'message' => "发货方式 不能为空"
			);
		}
		
		try {
			//make invoking journal
			//$journal_id = SysLogHelper::InvokeJrn_Create("Wish",__CLASS__, __FUNCTION__ , array($orderid , $tracking_provider , $tracking_number , $ship_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"can not find the orderid:".$orderid],"edb\global");
				return array(
					'success' => false,
					'message' => 'WISHOD010: Not found such internal order id: '.$orderid
				);
			}
			
			//step 2: insert the data into request as parameters
			$action_type = 'order_ship';
			$site_id = $order_model->saas_platform_user_id;
			$params = array(
				'order_id'=>$order_model->order_source_order_id , 
				'tracking_provider'=> $tracking_provider, 
				'tracking_number'=> $tracking_number , 
				'ship_note'=> $ship_note,
			);
			
			$rtn = WishOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			//SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['wish',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage() ],"edb\global");
			return array(
				'success' => false,
				'message' => 'WISHOD500: : '.$e->getMessage(),
			);
		}
	}//end of shipWishOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * wish订单退货/退款/取消
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $order_id					eagle订单号 （必需）
	 * @param $reason_code 				退货/退款/取消原因代号 （必需）
	 * @param $reason_note 				原因备注
	 +---------------------------------------------------------------------------------------------
	 * @description			reason_code 列表
	 * 					  code     meaning
	 * 						0	No More Inventory
	 * 						1	Unable to Ship
	 *						2	Customer Requested Refund
	 * 						3	Item Damaged
	 * 						7	Received Wrong Item
	 * 						8	Item does not Fit
	 * 						9	Arrived Late or Missing
	 * 						-1	Other, if none of the reasons above apply. reason_note is required if this is used as reason_code
	 +--------------------------------------------------------------------------------------------- 
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function cancelWishOrder($orderid , $reason_code , $reason_note=''){
		//check required param
		if (empty($orderid)){
			return array(
				'success' => false,
				'message' => "order id 不能为空"
			);
		}
		
		$reason_code_active = array(0,1,2,3,7,8,9,-1);
		
		if (! in_array($reason_code, $reason_code_active)){
			return array(
				'success' => false,
				'message' => "退货/取消原因代号无效"
			);
		}
		
		if ($reason_code == -1){
			if (empty($reason_note)){
				return array(
					'success' => false,
					'message' => "当退货/取消原因代号等于-1时必须说明原因"
				);
			}
		}
		
		try {
			//make invoking journal
			//$journal_id = SysLogHelper::InvokeJrn_Create("Wish",__CLASS__, __FUNCTION__ , array($orderid , $reason_code , $reason_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['wish',__CLASS__,__FUNCTION__,'Background', "can not find the orderid:".$orderid ],"edb\global");
				return array(
					'success' => false,
					'message' => 'WISHOD010: Not found such internal order id: '.$orderid
				);
			}
			
			//step 2: insert the data into request as parameters
			$action_type = 'order_cancel';
			$site_id = $order_model->saas_platform_user_id;
			$params = array(
				'order_id'=>$order_model->order_source_order_id , 
				'reason_code'=> $reason_code, 
				'reason_note'=> $reason_note , 
			);
			
			$rtn = WishOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			//SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['wish',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage() ],"edb\global");
			return array(
				'success' => false,
				'message' => 'WISHOD500: : '.$e->getMessage(),
			);
		}
	}//end of cancelWishOrder
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改订单wish订单发货信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $order_id					eagle订单号 （必需）
	 * @param $tracking_provider 		发货方式 （必需）
	 * @param $tracking_number 			快递号
	 * @param $ship_note 				发货备注
	 +--------------------------------------------------------------------------------------------- 
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function modifyWishOrderShippedInfo($orderid , $tracking_provider , $tracking_number='' , $ship_note=''){
	//check required param
		if (empty($orderid)){
			return array(
				'success' => false,
				'message' => "order id 不能为空"
			);
		}
		
		if (empty($tracking_provider)){
			return array(
				'success' => false,
				'message' => "发货方式 不能为空"
			);
		}
		
		try {
			//make invoking journal
			//$journal_id = SysLogHelper::InvokeJrn_Create("Wish",__CLASS__, __FUNCTION__ , array($orderid , $tracking_provider , $tracking_number , $ship_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"can not find the orderid:".$orderid ],"edb\global");
				return array(
					'success' => false,
					'message' => 'WISHOD010: Not found such internal order id: '.$orderid
				);
			}
			
			//step 2: insert the data into request as parameters
			$action_type = 'order_modify';
			$site_id = $order_model->saas_platform_user_id;
			$params = array(
				'order_id'=>$order_model->order_source_order_id , 
				'tracking_provider'=> $tracking_provider, 
				'tracking_number'=> $tracking_number , 
				'ship_note'=> $ship_note,
			);
			
			$rtn = WishOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			//SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['wish',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage()  ],"edb\global");
			return array(
				'success' => false,
				'message' => 'WISHOD500: : '.$e->getMessage(),
			);
		}
		
	}//end of modifyWishOrderShippedInfo
	
	
	public static function getShippingCodeNameMap(){
		return  array(
				'4PX'=>'4PX',
'AirpakExpress'=>'AirpakExpress',
'Aramex'=>'Aramex',
'AsendiaGermany'=>'AsendiaGermany',
'AsendiaUK'=>'AsendiaUK',
'AsendiaUSA'=>'AsendiaUSA',
'AuPostChina'=>'AuPostChina',
'AustraliaPost'=>'AustraliaPost',
'AustrianPost'=>'AustrianPost',
'AustrianPostRegistered'=>'AustrianPostRegistered',
'BPost'=>'BPost',
'BPostInternational'=>'BPostInternational',
'BRTBartolini'=>'BRTBartolini',
'Belpost'=>'Belpost',
'BrazilCorreios'=>'BrazilCorreios',
'CNEExpress'=>'CNEExpress',
'CanadaPost'=>'CanadaPost',
'CeskaPosta'=>'CeskaPosta',
'ChinaAirPost'=>'ChinaAirPost',
'Chukou1'=>'Chukou1',
'ColisPrive'=>'ColisPrive',
'Colissimo'=>'Colissimo',
'CorreosChile'=>'CorreosChile',
'CorreosCostaRica'=>'CorreosCostaRica',
'CorreosDeEspana'=>'CorreosDeEspana',
'CorreosDeMexico'=>'CorreosDeMexico',
'CyprusPost'=>'CyprusPost',
'DHL'=>'DHL',
'DHL2MannHandling'=>'DHL2MannHandling',
'DHLBenelux'=>'DHLBenelux',
'DHLExpress'=>'DHLExpress',
'DHLGermany'=>'DHLGermany',
'DHLGlobalMail'=>'DHLGlobalMail',
'DHLGlobalMailAsia'=>'DHLGlobalMailAsia',
'DHLNetherlands'=>'DHLNetherlands',
'DHLParcelNL'=>'DHLParcelNL',
'DHLPoland'=>'DHLPoland',
'DHLSpainDomestic'=>'DHLSpainDomestic',
'DPD'=>'DPD',
'DPDGermany'=>'DPDGermany',
'DPDIreland'=>'DPDIreland',
'DPDPoland'=>'DPDPoland',
'DPDUK'=>'DPDUK',
'DPEXChina'=>'DPEXChina',
'DanmarkPost'=>'DanmarkPost',
'DeutschePost'=>'DeutschePost',
'DirectLink'=>'DirectLink',
'ECFirstClass'=>'ECFirstClass',
'EMPSExpress'=>'EMPSExpress',
'EMS'=>'EMS',
'EPacket'=>'EPacket',
'EPostG'=>'EPostG',
'Envialia'=>'Envialia',
'EquickChina'=>'EquickChina',
'FastwayAustralia'=>'FastwayAustralia',
'FedEx'=>'FedEx',
'FedExApex'=>'FedExApex',
'FedExUK'=>'FedExUK',
'FlytExpress'=>'FlytExpress',
'Freipost'=>'Freipost',
'GLS'=>'GLS',
'GLSItaly'=>'GLSItaly',
'GLSNetherlands'=>'GLSNetherlands',
'GlobegisticsInc'=>'GlobegisticsInc',
'Hermes'=>'Hermes',
'HermesGermany'=>'HermesGermany',
'HongKongPost'=>'HongKongPost',
'IndiaPost'=>'IndiaPost',
'IndiaPostInternational'=>'IndiaPostInternational',
'IndonesiaPost'=>'IndonesiaPost',
'JapanPost'=>'JapanPost',
'KoreaPost'=>'KoreaPost',
'LaPosteColissimo'=>'LaPosteColissimo',
'LaserShip'=>'LaserShip',
'LatviaPost'=>'LatviaPost',
'LithuaniaPost'=>'LithuaniaPost',
'MagyarPosta'=>'MagyarPosta',
'MalaysiaPost'=>'MalaysiaPost',
'MalaysiaPostPosDaftar'=>'MalaysiaPostPosDaftar',
'NewZealandPost'=>'NewZealandPost',
'OnTrac'=>'OnTrac',
'OneWorldExpress'=>'OneWorldExpress',
'PTTPosta'=>'PTTPosta',
'PX4'=>'PX4',
'PocztaPolska'=>'PocztaPolska',
'PortugalCTT'=>'PortugalCTT',
'PostNL'=>'PostNL',
'PostNLInternational'=>'PostNLInternational',
'PostNLInternational3S'=>'PostNLInternational3S',
'RRDonnelley'=>'RRDonnelley',
'RoyalMail'=>'RoyalMail',
'RussianPost'=>'RussianPost',
'SFCService'=>'SFCService',
'SFExpress'=>'SFExpress',
'SFInternational'=>'SFInternational',
'SimplyPost'=>'SimplyPost',
'SingaporePost'=>'SingaporePost',
'SingaporeSpeedpost'=>'SingaporeSpeedpost',
'SprintPack'=>'SprintPack',
'SwedenPosten'=>'SwedenPosten',
'SwissPost'=>'SwissPost',
'TNT'=>'TNT',
'TNTAustralia'=>'TNTAustralia',
'TNTClickItaly'=>'TNTClickItaly',
'TNTFrance'=>'TNTFrance',
'TNTItaly'=>'TNTItaly',
'TNTPostItaly'=>'TNTPostItaly',
'TNTUK'=>'TNTUK',
'TaiwanPost'=>'TaiwanPost',
'ThailandThaiPost'=>'ThailandThaiPost',
'TollIPEC'=>'TollIPEC',
'TollPriority'=>'TollPriority',
'TrakPak'=>'TrakPak',
'TurkishPost'=>'TurkishPost',
'UBILogisticsAustralia'=>'UBILogisticsAustralia',
'UKMail'=>'UKMail',
'UPS'=>'UPS',
'UPSFreight'=>'UPSFreight',
'UPSMailInnovations'=>'UPSMailInnovations',
'UPSSurePost'=>'UPSSurePost',
'USPS'=>'USPS',
'UkrPoshta'=>'UkrPoshta',
'VietnamPost'=>'VietnamPost',
'VietnamPostEMS'=>'VietnamPostEMS',
'WishPost'=>'WishPost',
'XPO'=>'XPO',
'YODEL'=>'YODEL',
'Yanwen'=>'Yanwen',
'YodelInternational'=>'YodelInternational',
'YunExpress'=>'YunExpress',
'UBISmartParcel'=>'UBISmartParcel',
		);
	}
	
	
	/* 返回该 platform 允许的 默认 carrier name
	 * yzq @ 2015-7-9
	 * */
	public static function getDefaultShippingCode(){
		return 'USPS';
	}
}
?>