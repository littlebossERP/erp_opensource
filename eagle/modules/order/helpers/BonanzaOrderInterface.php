<?php
namespace eagle\modules\order\helpers;
use yii;
use yii\data\Pagination;

use eagle\models\SaasBonanzaUser;
use eagle\modules\order\models\BonanzaOrder;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\SysLogHelper ;
use yii\base\Exception;
use eagle\modules\listing\helpers\BonanzaProxyConnectHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\util\helpers\RedisHelper;

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
class BonanzaOrderInterface{
    // TODO proxy host
    CONST PROXY_URL ="http://localhost/Bonanza_Proxy_Server/ApiEntry.php?token=HE654HRYR,,SDFEdfsaaoi&BonanzaToken=@BonanzaToken@";
	public static $BonanzaToken='';
 
	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回Bonanza订单不需要发货的item 的sku，例如INTERETBCA
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array(0=>'INTERETBCA',1=>'INTERETBCA1',.....)
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/09/17			初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public static function getNonDeliverySku(){
		return array(	
			'INTERETBCA',
			'FRAISTRAITEMENT',
		);
	}
	
	public static function apiReturnMessageZhMapping($message){
		$messageContent = [
			'M01'=>'Unknown user or password.Incorrect login or password',
		
		];
		$Zh_Mapping = [
			'M01'=>'账号或token错误',
		];
		
		foreach($messageContent as $code=>$content){
			if(!stripos($message, $content)==false)
				return $Zh_Mapping[$code];
		}
		return $message;
	}
	
	/**已废弃
	 +---------------------------------------------------------------------------------------------
	 * Bonanza订单发货
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
	static public function shipBonanzaOrder($orderid , $tracking_provider , $tracking_number='' , $ship_note=''){
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
			$journal_id = SysLogHelper::InvokeJrn_Create("Bonanza",__CLASS__, __FUNCTION__ , array($orderid , $tracking_provider , $tracking_number , $ship_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['Bonanza',__CLASS__,__FUNCTION__,'Background',"can not find the orderid:".$orderid],"edb\global");
				return array(
					'success' => false,
					'message' => 'BonanzaOD010: Not found such internal order id: '.$orderid
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
			
			$rtn = BonanzaOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['Bonanza',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage() ],"edb\global");
			return array(
				'success' => false,
				'message' => 'BonanzaOD500: : '.$e->getMessage(),
			);
		}
	}//end of shipBonanzaOrder
	
	/**已废弃/未启用
	 +---------------------------------------------------------------------------------------------
	 * Bonanza订单退货/退款/取消
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
	static function cancelBonanzaOrder($orderid , $reason_code , $reason_note=''){
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
			$journal_id = SysLogHelper::InvokeJrn_Create("Bonanza",__CLASS__, __FUNCTION__ , array($orderid , $reason_code , $reason_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['Bonanza',__CLASS__,__FUNCTION__,'Background', "can not find the orderid:".$orderid ],"edb\global");
				return array(
					'success' => false,
					'message' => 'BonanzaOD010: Not found such internal order id: '.$orderid
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
			
			$rtn = BonanzaOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['Bonanza',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage() ],"edb\global");
			return array(
				'success' => false,
				'message' => 'BonanzaOD500: : '.$e->getMessage(),
			);
		}
	}//end of cancelBonanzaOrder
	
	
	/**已废弃/未启用
	 +---------------------------------------------------------------------------------------------
	 * 修改订单Bonanza订单发货信息
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
	static public function modifyBonanzaOrderShippedInfo($orderid , $tracking_provider , $tracking_number='' , $ship_note=''){
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
			$journal_id = SysLogHelper::InvokeJrn_Create("Bonanza",__CLASS__, __FUNCTION__ , array($orderid , $tracking_provider , $tracking_number , $ship_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['Bonanza',__CLASS__,__FUNCTION__,'Background',"can not find the orderid:".$orderid ],"edb\global");
				return array(
					'success' => false,
					'message' => 'BonanzaOD010: Not found such internal order id: '.$orderid
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
			
			$rtn = BonanzaOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['Bonanza',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage()  ],"edb\global");
			return array(
				'success' => false,
				'message' => 'BonanzaOD500: : '.$e->getMessage(),
			);
		}
		
	}//end of modifyBonanzaOrderShippedInfo
	
	
	public static function getShippingCodeNameMap(){
        return array(
            'USPS'=>'USPS',
            'UPS'=>'UPS',
            'FedEx'=>'FedEx',
            'International'=>'International',
            'Other'=>'Other',
        );
	}
	
	
	/* 返回该 platform 允许的 默认 carrier name
	 * yzq @ 2015-7-9
	 * */
	public static function getDefaultShippingCode(){
		return 'OTHER';
	}
	
	/**
	 * 根据Bonanza orderid获取买家邮箱(前端触发)
	 * @param 		array 		$uid
	 * @param 		str 		$orderIds
	 * @return 		string
	 */
	public static function getOrdersEmail($uid , $orderIds){
		if(empty($uid))
			return array('success'=>false,'message'=>"未登录!");
		
		 		
		if(empty($orderIds))
			return array('success'=>false,'message'=>"没有传入要处理的订单号!");
		
		if(is_string($orderIds))
			$orderIds[] = $orderIds;
		
		$accounts = [];
		$result=array('success'=>true,'message'=>'');
		try{
			foreach ($orderIds as $orderid){
				if(empty($orderid))
					continue;
				$od = OdOrder::findOne($orderid);
				if($od==null){
					$result['success'] = false;
					$result['message'] .= $orderid.'：无该订单;';
					continue;
				}
				if(!empty($od->consignee_email))
					continue;
				
				$seller = $od->selleruserid;
				if(array_key_exists($seller, $accounts)){
					$cdAccount = $accounts[$seller];
				}
				else{
					$cdAccount = SaasBonanzaUser::find()->where(['username'=>$seller,'uid'=>$uid])->asArray()->one();
					if($cdAccount==null){
						$result['success'] = false;
						$result['message'] .= $orderid.'：找不到对应卖家账号信息;';
						continue;
					}else{
						$accounts[$seller] = $cdAccount;
					}	
				}
				if($od->order_source!=='cidscount'){
					$result['success'] = false;
					$result['message'] .= $orderid.'：不是Bonanza订单;';
					continue;
				}
				
				$cdOrderId = $od->order_source_order_id;
				$email = self::getEmailByOrderID($cdAccount , $cdOrderId);
				if(!empty($email)){
					$od->consignee_email = $email;
					$od->save(false);
				}
			}
		}catch (\Exception $e){
			$result['success'] = false;
			$result['message'] .= $e->getMessage;
		}
		return $result;
	}//end of getOrdersEmail	
	
	/**
	 * 根据Bonanza orderid 获取买家billing info
	 * @param 		str 		$orderid
	 * @return 		array
	 */
	public static function getBillingInfoByOrder($orderid){
		$billingInfo = [];
		$cdOrder = BonanzaOrder::findOne($orderid);
		if($cdOrder<>null){
			$billing_name='';
			if(is_string($cdOrder->billing_civility) && $cdOrder->billing_civility!=='[]' && json_decode($cdOrder->billing_civility,true)===null)
				$billing_name.=$cdOrder->billing_civility;
			
			$billing_firstname='';
			if(is_string($cdOrder->billing_firstname) && $cdOrder->billing_firstname!=='[]' && json_decode($cdOrder->billing_firstname,true)===null)
				$billing_firstname=$cdOrder->billing_firstname;
			if(empty($billing_name))
				$billing_name = $billing_firstname;
			else 
				$billing_name .=' '. $billing_firstname;
			
			$billing_lastname='';
			if(is_string($cdOrder->billing_lastname) && $cdOrder->billing_lastname!=='[]' && json_decode($cdOrder->billing_lastname,true)===null)
				$billing_lastname=$cdOrder->billing_lastname;
			if(empty($billing_name))
				$billing_name = $billing_lastname;
			else
				$billing_name .=' '. $billing_lastname;

			$billingInfo['name'] = $billing_name;
			$billingInfo['post_code'] = $cdOrder->billing_zipcode;
			$billingInfo['phone'] = empty($cdOrder->customer_mobilephone)?$cdOrder->customer_phone:$cdOrder->customer_mobilephone;
			
			$street = '';
			$billing_address1 = '';
			if(is_string($cdOrder->billing_address1) && $cdOrder->billing_address1!=='[]' && json_decode($cdOrder->billing_address1,true)===null)
				$billing_address1=$cdOrder->billing_address1;
			$billing_address2 = '';
			if(is_string($cdOrder->billing_address2) && $cdOrder->billing_address2!=='[]' && json_decode($cdOrder->billing_address2,true)===null)
				$billing_address2=$cdOrder->billing_address2;
			if(!empty($billing_address2))
				$street .= $billing_address1.' '.$billing_address2;
			else 
				$street .= $billing_address1.$billing_address2;
			
			
			$billing_building = '';
			if(is_string($cdOrder->billing_building) && $cdOrder->billing_building!=='[]' && json_decode($cdOrder->billing_building,true)===null)
				$billing_building=$cdOrder->billing_building;
			if(!empty($billing_building))
				$street .= $billing_building.';'.$street;
			
			$billing_companyname = '';
			if(is_string($cdOrder->billing_companyname) && $cdOrder->billing_companyname!=='[]' && json_decode($cdOrder->billing_companyname,true)===null)
				$billing_companyname=$cdOrder->billing_companyname;
			if(!empty($billing_companyname))
				$street .= $billing_companyname.';'.$street;
			
			$billing_placename = '';
			if(is_string($cdOrder->billing_placename) && $cdOrder->billing_placename!=='[]' && json_decode($cdOrder->billing_placename,true)===null)
				$billing_placename=$cdOrder->billing_placename;
			if(!empty($billing_placename))
				$street = empty($street)?$billing_placename:$street.';'.$billing_placename;
			
			$billing_street = '';
			if(is_string($cdOrder->billing_street) && $cdOrder->billing_street!=='[]' && json_decode($cdOrder->billing_street,true)===null)
				$billing_street=$cdOrder->billing_street;
			if(!empty($billing_street))
				$street = empty($street)?$billing_street:$street.';'.$billing_street;

			if(is_string($cdOrder->billing_city) && $cdOrder->billing_city!=='[]' && json_decode($cdOrder->billing_city,true)===null)
				$billing_city = $cdOrder->billing_city;
			else 
				$billing_city = '';
			if(is_string($cdOrder->billing_country) && $cdOrder->billing_country!=='[]' && json_decode($cdOrder->billing_country,true)===null)
				$billing_country = $cdOrder->billing_country;
			else 
				$billing_country='';
			
			$billingInfo['address'] = $street;
			$billingInfo['city'] = $billing_city;
			$billingInfo['country'] = $billing_country;
			$billingInfo['company'] = $billing_companyname;
			
		}
		
		return $billingInfo;
		
	}
	
	/**
	 * 根据Bonanza orderid 获取买家邮箱
	 * @param 		array 		$BonanzaAccount
	 * @param 		str 		$orderid
	 * @return 		string
	 */
	/*
	public static function getEmailByOrderID($BonanzaAccount , $orderid){
		$timeout=240; //s

		$params['orderid'] = $orderid;
		$get_param['query_params'] = json_encode($params);

		$config = array('tokenid' => $BonanzaAccount['token']);
		$get_param['config'] = json_encode($config);
		$emailMessage=BonanzaProxyConnectHelper::call_Bonanza_api("getEmailByOrderID",$get_param,$post_params=array(),$timeout );

		$customer_email = "";
		
		if (isset($emailMessage['success']) && ($emailMessage['success'] == true || $emailMessage['success'] == 1) ){
			if(!empty($emailMessage['proxyResponse']['success'])){
				if(!empty($emailMessage['proxyResponse']['emailMessage']['s_Body']['GenerateDiscussionMailGuidResponse']['GenerateDiscussionMailGuidResult']['a_MailGuid']))
					$customer_email = $emailMessage['proxyResponse']['emailMessage']['s_Body']['GenerateDiscussionMailGuidResponse']['GenerateDiscussionMailGuidResult']['a_MailGuid'];
			}
		}
		
		return $customer_email;
	}//end of GetEmailByOrderID
	*/
	
	/**
	 * 根据uid 获取该用户需要提醒的OMS信息
	 * @return 		array
	 * @author		lzhl		2014/12/14
	 */
	public static function getBonanzaReminder($uid){
		$rtn['success'] = true;
		$rtn['message'] = '';
		$rtn['remind'] = [];
		if (empty($uid)){
			//异常情况
			$rtn['message'] = "请先登录!";
			$rtn['success'] = false;
			return $rtn;
		}
		 
		
		//$status = OdOrder::$status;
		//foreach ($status as $s=>$sV){
		//	$targetStatus[$sV] = $s;
		//}
		
		$remindYiFuKuan = true;//是否提示已付款订单
		$remindFaHuoZhong = true;//是否提示发货中订单
		$remindYiFaHuo = true;//是否提示已发货订单
		$remindZhuangTaiYiChang = true;//是否提示状态异常订单
		$remindWuTianWeiShangWang = true;//是否提示五天未上网订单
		
		//@todo
		//全局设置是否提示，如果设置了否，则不提示
		
		/* config 结构
		 * array(
		 		* 		Reminder1=>array(
		 				* 			'date'=>'2015-12-15'.e.g,
		 				* 			'user_close'=>true/false,
		 				* 			),
		 		* 		Reminder2=>array(...),
		 		* 		...
		 		* 		)
		*/
		$oldReminder = ConfigHelper::getConfig("BonanzaOMS/ReminderInfo",'NO_CACHE');
		
		$today = date("Y-m-d",time());
		if(is_string($oldReminder))
			$oldReminder = json_decode($oldReminder,true);
		if(!empty($oldReminder)){
			//user_close:用户关闭了提示
			if(!empty($oldReminder['YiFuKuan_Order']) && (!empty($oldReminder['YiFuKuan_Order']['date']) && strtotime($oldReminder['YiFuKuan_Order']['date'])==strtotime($today)) ){
				if(!empty($oldReminder['YiFuKuan_Order']['user_close']))
					$remindYiFuKuan = false;
			}
			if(!empty($oldReminder['FaHuoZhong_Order']) && (!empty($oldReminder['FaHuoZhong_Order']['date']) && strtotime($oldReminder['FaHuoZhong_Order']['date'])==strtotime($today)) ){
				if(!empty($oldReminder['FaHuoZhong_Order']['user_close']))
					$remindFaHuoZhong = false;
			}
			if(!empty($oldReminder['YiFaHuo_Order']) && (!empty($oldReminder['YiFaHuo_Order']['date']) && strtotime($oldReminder['YiFaHuo_Order']['date'])==strtotime($today))){
				if(!empty($oldReminder['YiFaHuo_Order']['user_close']))
					$remindYiFaHuo = false;
			}
			if(!empty($oldReminder['ZhuangTaiYiChang_Order']) && (!empty($oldReminder['ZhuangTaiYiChang_Order']['date']) && strtotime($oldReminder['ZhuangTaiYiChang_Order']['date'])==strtotime($today))){
				if(!empty($oldReminder['ZhuangTaiYiChang_Order']['user_close']))
					$remindZhuangTaiYiChang = false;
			}
			if(!empty($oldReminder['WuTianWeiShangWang_Order']) && (!empty($oldReminder['WuTianWeiShangWang_Order']['date']) && strtotime($oldReminder['WuTianWeiShangWang_Order']['date'])==strtotime($today))){
				if(!empty($oldReminder['WuTianWeiShangWang_Order']['user_close']))
					$remindZhuangTaiYiChang = false;
			}
			
		}else{
			//无config记录则提示全部
		}
		
		$twoDaysAgo = time()-3600*48;
		/*
		BonanzaOrderHelper::$CD_OMS_WEIRD_STATUS;
		'sus'=>'PM后台状态和小老板状态不同步',//satatus unSync 状态不同步
		'wfs'=>'提交发货或提交物流',//waiting for shipment
		'wfd'=>'交运至物流商',//waiting for dispatch
		'wfss'=>'等待手动标记发货，或物流模块"确认已发货"',//waiting for sing shipped ,or confirm dispatch
		 */
		
		//$yifukuan_status = $targetStatus['已付款'];
		//$fahuozhong_status = $targetStatus['发货中'];
		//$yifahuo_status = $targetStatus['已发货'];
		if($remindYiFuKuan){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'Bonanza'])->andWhere(['weird_status'=>'wfs'])->count();
			if($ot_Orders>0)
				$rtn['remind']['YiFuKuan'] = $ot_Orders;
		}
		if($remindFaHuoZhong){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'Bonanza'])->andWhere(['weird_status'=>'wfd'])->count();
			if($ot_Orders>0)
				$rtn['remind']['FaHuoZhong'] = $ot_Orders;
		}
		if($remindYiFaHuo){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'Bonanza'])->andWhere(['weird_status'=>'wfss'])->count();
			if($ot_Orders>0)
				$rtn['remind']['YiFaHuo'] = $ot_Orders;
		}
		if($remindZhuangTaiYiChang){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'Bonanza'])->andWhere(['weird_status'=>'sus'])->count();
			if($ot_Orders>0)
				$rtn['remind']['ZhuangTaiYiChang'] = $ot_Orders;
		}
		if($remindWuTianWeiShangWang){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'Bonanza'])->andWhere(['weird_status'=>'tuol'])->count();
			if($ot_Orders>0)
				$rtn['remind']['WuTianWeiShangWang'] = $ot_Orders;
		}
		return $rtn;
	}
	
	/**
	 * 根据uid 获取该用户需要提醒的OMS信息
	 * @return 		array
	 * @author		lzhl		2014/12/14
	 */
	public static function CloseReminder($uid){
		if(empty($uid))
			return "未登录!";
		 
		
		$oldReminder = ConfigHelper::getConfig("BonanzaOMS/ReminderInfo",'NO_CACHE');
		if(is_string($oldReminder))
			$oldReminder = json_decode($oldReminder,true);
		
		/* config 结构
		 * array(
		 * 		Reminder1=>array(
		 * 			'date'=>'2015-12-15'.e.g,
		 * 			'user_close'=>true/false,
		 * 			),
		 * 		Reminder2=>array(...),
		 * 		...
		 * 		)
		 */
		$day = date("Y-m-d",time()); 
		if(empty($oldReminder)){
			$oldReminder = [];
		}
		
		$oldReminder['YiFuKuan_Order']['user_close'] = true;
		$oldReminder['YiFuKuan_Order']['date'] = $day;
		
		$oldReminder['FaHuoZhong_Order']['user_close'] = true;
		$oldReminder['FaHuoZhong_Order']['date'] = $day;
		
		$oldReminder['YiFaHuo_Order']['user_close'] = true;
		$oldReminder['YiFaHuo_Order']['date'] = $day;
		
		$oldReminder['ZhuangTaiYiChang_Order']['user_close'] = true;
		$oldReminder['ZhuangTaiYiChang_Order']['date'] = $day;
		
		$oldReminder['WuTianWeiShangWang_Order']['user_close'] = true;
		$oldReminder['WuTianWeiShangWang_Order']['date'] = $day;
		
		ConfigHelper::setConfig("BonanzaOMS/ReminderInfo", json_encode($oldReminder));
		return "success";
	}
	
	
	/**
	 * 
	 */
	public static function getMonitorData(){
		$the_day = date("Y-m-d",time());
		$nowTime = date("Y-m-d H:i:s",time());
		$data=[];
		$data['runtime']=[];
		$has_job=['job0','job1','job2'];
		$runtime_command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `the_day`='".$the_day."'");
		$runtime_record = $runtime_command->queryAll();
		foreach ($has_job as $i=>$job){
			$data['runtime'][$job]=['time'=>'',];
			foreach ($runtime_record as $row){
				if($row['addinfo']=='job_id='.$i){
					$data['runtime'][$job]['time']=$row['update_time'];
					$data['runtime'][$job]['error_message']=$row['error_message'];
					$run_times = json_decode($row['addinfo2'],true);
					if(!is_array($run_times)){
						$enter_times = 0;
						$end_times = 0;
					}
					else{
						$enter_times = empty($run_times['enter_times'])?0:$run_times['enter_times'];
						$end_times = empty($run_times['end_times'])?0:$run_times['end_times'];
					}
					$data['runtime'][$job]['enter_times']=$enter_times;
					$data['runtime'][$job]['end_times']=$end_times;
				}
			}
		}
		
		//近5日订单情况
		$last_5_days = [
			$the_day,
			date("Y-m-d",strtotime($the_day)-3600*24),
			date("Y-m-d",strtotime($the_day)-3600*24*2),
			date("Y-m-d",strtotime($the_day)-3600*24*3),
			date("Y-m-d",strtotime($the_day)-3600*24*4)
		];
		foreach ($last_5_days as $each_day){
			$data['orders'][$each_day]=[];
			$order_count_command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='orders' and `the_day`='".$each_day."' "  );
			$order_count = $order_count_command->queryOne();
			if(empty($order_count)){
				$data['orders'][$each_day]['count']=[
					'src_insert_success'=>0,
					'src_insert_failed'=>0,
					'src_detail_insert_success'=>0,
					'src_detail_insert_failed'=>0,
					'src_update_success'=>0,
					'src_update_failed'=>0,
					'src_detail_update_success'=>0,
					'src_detail_update_failed'=>0,
					'oms_insert_success'=>0,
					'oms_insert_failed'=>0,
					'oms_update_success'=>0,
					'oms_update_failed'=>0,
				];
				$data['orders'][$each_day]['failed_happend_site']=[
					'src_insert_failed_happend_site' => '',
					'src_update_failed_happend_site' => '',
					'src_detail_insert_failed_happend_site' => '',
					'src_detail_update_failed_happend_site' => '',
					'oms_insert_failed_happend_site' => '',
					'oms_update_failed_happend_site' => '',
				];
				
			}else{
				$count_str = $order_count['addinfo'];
				$count_arr = json_decode($count_str,true);
				$data['orders'][$each_day]['count']=[
					'src_insert_success'=>empty($count_arr['src_insert_success'])?0:$count_arr['src_insert_success'],
					'src_insert_failed'=>empty($count_arr['src_insert_failed'])?0:$count_arr['src_insert_failed'],
					'src_detail_insert_success'=>empty($count_arr['src_detail_insert_success'])?0:$count_arr['src_detail_insert_success'],
					'src_detail_insert_failed'=>empty($count_arr['src_detail_insert_failed'])?0:$count_arr['src_detail_insert_failed'],
					'src_update_success'=>empty($count_arr['src_update_success'])?0:$count_arr['src_update_success'],
					'src_update_failed'=>empty($count_arr['src_update_failed'])?0:$count_arr['src_update_failed'],
					'src_detail_update_success'=>empty($count_arr['src_detail_update_success'])?0:$count_arr['src_detail_update_success'],
					'src_detail_update_failed'=>empty($count_arr['src_detail_update_failed'])?0:$count_arr['src_detail_update_failed'],
					'oms_insert_success'=>empty($count_arr['oms_insert_success'])?0:$count_arr['oms_insert_success'],
					'oms_insert_failed'=>empty($count_arr['oms_insert_failed'])?0:$count_arr['oms_insert_failed'],
					'oms_update_success'=>empty($count_arr['oms_update_success'])?0:$count_arr['oms_update_success'],
					'oms_update_failed'=>empty($count_arr['oms_update_failed'])?0:$count_arr['oms_update_failed'],
				];
				
				$failed_happend_site_str = $order_count['addinfo2'];
				$site_arr = json_decode($failed_happend_site_str,true);
				$data['orders'][$each_day]['failed_happend_site']=[
					'src_insert_failed_happend_site'=>empty($site_arr['src_insert_failed_happend_site'])?'':implode(',',$site_arr['src_insert_failed_happend_site']),
					'src_update_failed_happend_site'=>empty($site_arr['src_update_failed_happend_site'])?'':implode(',',$site_arr['src_update_failed_happend_site']),
					'src_detail_insert_failed_happend_site'=>empty($site_arr['src_detail_insert_failed_happend_site'])?'':implode(',',$site_arr['src_detail_insert_failed_happend_site']),
					'src_detail_update_failed_happend_site'=>empty($site_arr['src_detail_update_failed_happend_site'])?'':implode(',',$site_arr['src_detail_update_failed_happend_site']),
					'oms_insert_failed_happend_site'=>empty($site_arr['oms_insert_failed_happend_site'])?'':implode(',',$site_arr['oms_insert_failed_happend_site']),
					'oms_update_failed_happend_site'=>empty($site_arr['oms_update_failed_happend_site'])?'':implode(',',$site_arr['oms_update_failed_happend_site']),
				];
				
			}
		}
		
		$data['errors']=[];
		$error_type=['1','2','3','4','5'];
		$error_command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='err_type' and `the_day`='".$the_day."' "  );
		$error_record = $error_command->queryOne();
		if(empty($error_record)){
			$data['errors']=[
				'1'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>$nowTime],//保存原单失败
				'2'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>$nowTime],//保存原单商品详情失败
				'3'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>$nowTime],//写入OMS失败
				'4'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>$nowTime],//update原单失败
				'5'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>$nowTime],//update原单商品详情失败
			];
		}else{
			$error_str = $error_record['addinfo'];
			$error_arr = json_decode($error_str,true);
			foreach ($error_type as $type){
				$data['errors'][$type] = ['times'=>0,'last_msg'=>'','site_id'=>'','time'=>$nowTime];
				foreach ($error_arr as $t=>$v){
					if($t==$type){
						$data['errors'][$type] = [
							'times'=>empty($v['times'])?0:$v['times'],
							'last_msg'=>empty($v['last_msg'])?'':$v['last_msg'],
							'site_id'=>empty($v['site_id'])?'':implode(',', $v['site_id']),
							'time'=>empty($v['time'])?'':$v['time'],
						];
					}
				}
			}
		}
		
		//近5天用户情况
			//用户统计
		$sql_1 =SaasBonanzaUser::find();
		$all_accounts = $sql_1->count();
		
		
		//Yii::$app->db_queue->createCommand("SELECT COUNT( * ) AS  'accounts', COUNT( DISTINCT  `uid` ) AS  'uids' FROM  `saas_Bonanza_user` WHERE 1 ");
		$all_uids = $sql_1->select('DISTINCT  `uid`')->count();
		
		//$rows_1 = $sql_1->queryOne();
		//$all_accounts = $rows_1['accounts'];
		//$all_uids = $rows_1['uids'];
			//启用账号统计
		$sql_2 =SaasBonanzaUser::find()->where(['is_active'=>1]);
		//$sql_2 = Yii::$app->db_queue->createCommand("SELECT COUNT( * ) AS  'active' FROM  `saas_Bonanza_user` WHERE  `is_active` =1 ");
		$active_accounts = $sql_2->count();
		
		//$rows_2 = $sql_2->queryOne();
		//$active_accounts = $rows_2['active'];
		$unActive_accounts =$all_accounts - $active_accounts;
			//token过期用户统计
		$sql_3 =SaasBonanzaUser::find()->where(" `token_expired_date` <= '".date("Y-m-d H:i:s",strtotime($the_day)-3600*24)."' " );
		$rows_3 = $sql_3->asArray()->all();
		//$sql_3 = Yii::$app->db_queue->createCommand("SELECT * FROM  `saas_Bonanza_user` WHERE  `token_expired_date` <=  '".date("Y-m-d H:i:s",strtotime($the_day)-3600*24)."' ");
		//$rows_3 = $sql_3->queryAll();
		$token_expired_accounts = count($rows_3);
		$toekn_expired_site = [];
		foreach ($rows_3 as $row){
			$toekn_expired_site[] = $row['site_id'];
		}
			//未初始化订单账号
		$sql_4 =SaasBonanzaUser::find()->where(" `initial_fetched_changed_order_since` IS NULL OR `initial_fetched_changed_order_since` = '0000-00-00 00:00:00' ");
		$rows_4 = $sql_4->asArray()->all();
		
		//$sql_4 = Yii::$app->db_queue->createCommand("SELECT * FROM  `saas_Bonanza_user` WHERE `initial_fetched_changed_order_since` IS NULL OR `initial_fetched_changed_order_since` =  '0000-00-00 00:00:00' ");
		//$rows_4 = $sql_4->queryAll();
		$un_initial_accounts = count($rows_4);
		$un_initial_site = [];
		foreach ($rows_4 as $row){
			$un_initial_site[] = $row['site_id'];
		}
		
		
		//先记录/更新 当天的数据
		$user_count_command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='user_count' and `the_day`='".$the_day."' "  );
		$user_count_record = $user_count_command->queryOne();
		
		$user_count = [
			'all_accounts'=>$all_accounts,
			'all_uids' => $all_uids,
			'active_accounts'=>$active_accounts,
			'unActive_accounts'=>$unActive_accounts,
			'token_expired_accounts'=>$token_expired_accounts,
			'toekn_expired_site'=>implode(',', $toekn_expired_site),
			'un_initial_accounts'=>$un_initial_accounts,
			'un_initial_site'=>implode(',', $un_initial_site),
		];
		if(empty($user_count_record)){
			$command = Yii::$app->db_queue->createCommand("INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('CDOMS','user_count','normal','','".json_encode($user_count)."','--','".$the_day."','".date("Y-m-d H:i:s",time())."')"  );
			$affect_record = $command->execute();
		}else{
			$command = Yii::$app->db_queue->createCommand("update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo`='".json_encode($user_count)."'  where app='CDOMS' and info_type='user_count' and the_day='".$the_day."' "  );
			$affect_record = $command->execute();
		}
		
		//最近有记录的5天
		$u_command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='user_count' order by `the_day` desc limit 0,5 ");
		$u_record = $u_command->queryAll();
		foreach ($u_record as $u){
			$u_date = $u['the_day'];
			$u_str = $u['addinfo'];
			$u_info = json_decode($u_str,true);
			
			$data['user_count'][$u_date]=[
				'all_accounts'=>$u_info['all_accounts'],
				'all_uids'=>$u_info['all_uids'],
				'active_accounts'=>$u_info['active_accounts'],
				'unActive_accounts'=>$u_info['unActive_accounts'],
				'token_expired_accounts'=>$u_info['token_expired_accounts'],
				'toekn_expired_site'=>$u_info['toekn_expired_site'],
				'un_initial_accounts'=>$u_info['un_initial_accounts'],
				'un_initial_site'=>$u_info['un_initial_site'],
			];
		}
		return $data;
	}
	/**
	 * 30日内用户订单数据
	 */
	public static function getUserOrderCountDatas(){
		$the_day = date("Y-m-d",time());
		$user_order_count = [];
		
		$sql = "SELECT saas_Bonanza_user.`uid` , MIN( saas_Bonanza_user.`create_time` ) create_time, user_base.user_name
				FROM  `saas_Bonanza_user` 
				LEFT JOIN user_base ON ( saas_Bonanza_user.uid = user_base.uid ) 
				GROUP BY saas_Bonanza_user.uid";
		$command = Yii::$app->db->createCommand($sql);
		$all_rows = $command->queryAll();
		
		$pagination = new Pagination([
				'totalCount' => count($all_rows),
				'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:50,
				'pageSizeLimit'=>[50,500],//每页显示条数范围
				]);
		$sql .= " LIMIT ".$pagination->offset.",".$pagination->limit;
		$command = Yii::$app->db->createCommand($sql);
		$all_accounts = $command->queryAll();
		
		foreach ($all_accounts as $row){
			$one_user = [];
			$last_30_days_total = 0;
			$last_10_days_total = 0;
			$create_day = date("Y-m-d",strtotime($row['create_time']));
			$start_day = strtotime("2016-01-09 00:00:00");//统计引入时间
			$bind_days = 0;
			for($i=1;$i<=30;$i++){
				$targetDay = date("Y-m-d",strtotime($the_day)-3600*24*$i);
				if(strtotime($targetDay) - $start_day<0){
					$one_user[$targetDay] = 0;
					continue;
				}
				if( strtotime($create_day)-strtotime($targetDay)<=0 ){
					$bind_days ++;
					//$day_count = \Yii::$app->redis->hget('BonanzaOms_TempData',"user_".$row['uid'].".".$targetDay);
					$day_count = RedisHelper::RedisGet('BonanzaOms_TempData',"user_".$row['uid'].".".$targetDay );
					if(!empty($day_count)){
						$one_user[$targetDay] = (int)$day_count;
						$last_30_days_total += (int)$day_count;
						$last_10_days_total += (int)$day_count;
					}
					else
						$one_user[$targetDay] = 0;
				}
				else{
					$one_user[$targetDay] = 0;
				}
			}
			$one_user['user_name'] = $row['user_name'];
			$one_user['last_30_days_total'] = $last_30_days_total;
			$one_user['last_10_days_total'] = $last_10_days_total;
			$one_user['avg_pre_day'] = empty($bind_days)?0:round($last_30_days_total/$bind_days,2);
			//$set_avg = \Yii::$app->redis->hset('BonanzaOms_TempData',"user_".$row['uid'].'avg_pre_day',$one_user['avg_pre_day'].':'.$the_day);
			$user_order_count[$row['uid']] = $one_user;
		}
		//获取平均排名
		//$avgSomeOne = \Yii::$app->redis->hget('BonanzaOms_TempData',"user_297_avg_pre_day");//获取其中一个user的平均
		$avgSomeOne = RedisHelper::RedisGet('BonanzaOms_TempData',"user_297_avg_pre_day" );
		if(!empty($avgSomeOne)){
			if(stripos($avgSomeOne, $the_day)==false){//当天还没有统计过
				self::generateUserPreDayAvg();
			}
		}else{//没有任何统计
			self::generateUserPreDayAvg();
		}
		$tops = [];
		$tops_datas_all = [];
		$sql = "SELECT distinct saas_Bonanza_user.`uid` ,user_base.`user_name`
					FROM  `saas_Bonanza_user`
					LEFT JOIN user_base
					ON ( saas_Bonanza_user.uid = user_base.uid ) ";
		$command = Yii::$app->db->createCommand($sql);
		$accounts = $command->queryAll();
		foreach ($accounts as $a){
			$tops[$a['uid']]=0;
			//$avg = \Yii::$app->redis->hget('BonanzaOms_TempData',"user_".$a['uid']."_avg_pre_day");
			$avg = RedisHelper::RedisGet('BonanzaOms_TempData',"user_".$a['uid']."_avg_pre_day");
			if(!empty($avg)){
				$avg_arr = explode(':', $avg);
				$tops[$a['uid']]=$avg_arr[0];
				$tops_datas_all[$a['uid']] = ['avg'=>$avg_arr[0],'date'=>$avg_arr[1],'user_name'=>$a['user_name']];
			}else{
				$tops_datas_all[$a['uid']] = ['avg'=>0,'date'=>'--','user_name'=>$a['user_name']];
			}			
		}
		arsort($tops);
		$tops_datas=[];
		$j=1;
		foreach ($tops as $uid=>$avg){
			if($j>10) break;
			$tops_datas[$uid] = $tops_datas_all[$uid];
			$j++;
		}
		
		return array('count_datas'=>$user_order_count,'pagination'=>$pagination,'tops'=>$tops_datas);
	}
	/**
	 * 生成30日内用户平均日订单数
	 */
	public static function generateUserPreDayAvg(){
		$the_day = date("Y-m-d",time());
		try{
			$all_accounts_query = SaasBonanzaUser::find()->select("uid, create_time")->distinct(true);
			$all_accounts = $all_accounts_query->asArray()->all();
		
			foreach ($all_accounts as $row){
				$last_30_days_total = 0;
				$last_10_days_total = 0;
				$create_day = date("Y-m-d",strtotime($row['create_time']));
				$start_day = strtotime("2016-01-09 00:00:00");//统计引入时间
				$bind_days = 0;
				for($i=1;$i<=30;$i++){
					$targetDay = date("Y-m-d",strtotime($the_day)-3600*24*$i);
					if(strtotime($targetDay) - $start_day<0){
						continue;
					}
					if( strtotime($create_day)-strtotime($targetDay)<=0 ){
						$bind_days ++;
						//$day_count = \Yii::$app->redis->hget('BonanzaOms_TempData',"user_".$row['uid'].".".$targetDay);
						$day_count = RedisHelper::RedisGet('BonanzaOms_TempData',"user_".$row['uid'].".".$targetDay );
						if(!empty($day_count)){
							$last_30_days_total += (int)$day_count;
							$last_10_days_total += (int)$day_count;
						}
					}
				}
				$avg_pre_day = empty($bind_days)?0:round($last_30_days_total/$bind_days,2);
				//$set_avg = \Yii::$app->redis->hset('BonanzaOms_TempData',"user_".$row['uid'].'_avg_pre_day',$avg_pre_day.':'.$the_day);
				$set_avg = RedisHelper::RedisSet('BonanzaOms_TempData',"user_".$row['uid'].'_avg_pre_day',$avg_pre_day.':'.$the_day);
			}
			return true;
		}catch (\Exception $e){
			return false;
		}
	}
	
	/**
	 * 每日统计用户订单数量
	 * @param string $start_time e.g.'2016-01-01',//任务开始时间
	 */
	public static function cronBonanzaOrderDailySummary($start_time=''){
		try{
			if(empty($start_time))
				$start_time = date('Y-m-d H:i:s');
			$summary_end_date = $start_time;
			$summary_start_date = date('Y-m-d H:i:s',strtotime($start_time)-3600*24);
			$accounts = SaasBonanzaUser::find()->where(['is_active'=>1])->asArray()->all();
			foreach ($accounts as $account){
				$uid = $account['uid'];
	 
				$orders_yestday = OdOrder::find()
									->where(['between','create_time',strtotime($summary_start_date),strtotime($summary_end_date)])
									->orWhere(['create_time'=>strtotime($summary_start_date)])
									->andWhere(['selleruserid'=>$account['store_name'],'order_source'=>'Bonanza'])
									->asArray()->all();
				$yestday_count = count($orders_yestday);
				
				$query = "SELECT * FROM `od_order_summary` WHERE `platform`='Bonanza' and `puid`=$uid and `seller_id`='".$account['store_name']."' and `thedate`='".$summary_start_date."'";
				$command = Yii::$app->db->createCommand($query);
				$record = $command->queryOne();
	
				if(!empty($record)){
					$old_count = $record['create_order_count'];
					$addi_info = $record['addi_info'].'update at '.TimeUtil::getNow().',count:'.$old_count.'->'.$yestday_count.';';
					$sql = "UPDATE `od_order_summary` SET 
							`create_order_count`=$yestday_count,`addi_info`='$addi_info' 
							WHERE `platform`='Bonanza' and `puid`=$uid and `seller_id`='".$account['store_name']."' and `thedate`='".$summary_start_date."'";
				}else{
					$sql = "INSERT INTO `od_order_summary`
							(`platform`, `puid`, `seller_id`, `thedate`, `create_order_count`, `addi_info`) VALUES 
							('Bonanza',$uid,'".$account['store_name']."','".$summary_start_date."',$yestday_count,'')";
				}
				$command = Yii::$app->db->createCommand($sql);
				$affect_record = $command->execute();
				//$del_redis_record = \Yii::$app->redis->hdel('BonanzaOms_TempData',"user_$uid".".".$account['store_name'].".".$summary_start_date);
				$del_redis_record = RedisHelper::RedisDel('BonanzaOms_TempData',"user_$uid".".".$account['store_name'].".".$summary_start_date);
			}
		}catch (\Exception $e){
			echo "\n".$e ;
		}
	}//end of cronBonanzaOrderDailySummary
	
	public static function getOneUserSellerIdNowDateOrderCount($uid,$sellerid,$platform='Bonanza'){
		$nowDay = date('Y-m-d');
		//$day_count = \Yii::$app->redis->hget('BonanzaOms_TempData',"user_$uid".".".$sellerid.".".$nowDay);
		$day_count = RedisHelper::RedisGet('BonanzaOms_TempData',"user_$uid".".".$sellerid.".".$nowDay);
		if(empty($day_count))
			return $day_count=0;
		else
			return $day_count;
	}//end of getOneUserSellerIdNowDateOrderCount
	
	/**
	 * 获取用户绑定的bonanza账号的最近X日订单获取数量chart data
	 * @param int $uid
	 * @param int $days 	获取天数
	 */
	public static function getChartDataByUid_Order($uid,$days=7){
		$today = date('Y-m-d');
		$daysAgo = date('Y-m-d', strtotime($today)-3600*24*$days);
		//用户近目标日期内所有数据
		$query = "SELECT * FROM `od_order_summary` WHERE `platform`='Bonanza' and `puid`=$uid and `thedate`>='".$daysAgo."' and `thedate`<'".$today."'";
		$command = Yii::$app->db->createCommand($query);
		$summary_records = $command->queryAll();
		//用户BN账号数据
		$BN_Accounts = SaasBonanzaUser::find()->where(['uid'=>$uid,'is_active'=>1])->asArray()->all();
		//没有账号信息或者订单信息，直接return
		if(empty($summary_records) && empty($BN_Accounts))
			return [];

		$chart['type'] = 'column';
		$chart['title'] = '近'.$days.'日Bonanza订单数';
		$chart['subtitle'] = '基于启用的账号统计';
		$chart['xAxis'] = [];
		$chart['yAxis'] = '订单数';
		$chart['series'] = [];
		$series = [];
		$account_total = [];
		for ($i=$days;$i>=0;$i--){
			$total=0;
			$theday = date('Y-m-d', strtotime($today)-3600*24*$i);
			if($i==0)
				$chart['xAxis'][] = '今日';//只显示月，日
			else 
				$chart['xAxis'][] = date('m-d', strtotime($theday));//只显示月，日
			
			foreach ($BN_Accounts as $account){
				//print_r($account);
				$name = $account['store_name'];
				//echo "<br>".$name;
				$create_order_count = 0;
				if($i==0){
					$dateStartStamp = strtotime($theday);
					$dateEndStamp = $dateStartStamp+3600*24;
// 					$dateStartStamp = strtotime($theday)-3600*24*35;
// 					$dateEndStamp = strtotime($theday)+3600*24;
					$query = "SELECT * FROM `od_order_v2` WHERE `order_source`='Bonanza' AND 
							`paid_time`>$dateStartStamp AND `paid_time`<$dateEndStamp AND 
							`selleruserid`=:seller";
					$command = Yii::$app->subdb->createCommand($query);
					$command->bindValue(":seller",$account['store_name'],\PDO::PARAM_STR);
					$records = $command->queryAll();
					$create_order_count = count($records);
					
					$total +=$create_order_count;
				}else{
					foreach ($summary_records as $record){
						if($record['seller_id']==$account['store_name'] && $record['thedate']==$theday){
							$create_order_count = (int)$record['create_order_count'];
							$total += (int)$record['create_order_count'];
							break;
						}
					}
				}
				$series[$name][] = $create_order_count;
			}
			$account_total[] = $total;
		}
		$chart['series'][] = ['name'=>'total(全部合计)','data'=>$account_total];
		foreach ($series as $name=>$data){
			$chart['series'][] = ['name'=>$name,'data'=>$data];
			
		}
		return $chart;
	}

	/**
	 * 获取用户绑定的BN账号的最近X日订单利润(已计算的)情况
	 * @param int $uid
	 * @param int $days 	获取天数
	 */
	public static function getChartDataByUid_Profit($uid,$days=7){
		$today = date('Y-m-d');
		$daysAgo = date('Y-m-d', strtotime($today)-3600*24*$days);
		//用户近目标日期内所有数据
		$daysOrders = OdOrder::find()
		->where(" `paid_time`>=".strtotime($daysAgo)." and `paid_time`<=".time()." and `profit` IS NOT NULL ")
		->andWhere(['order_source'=>'Bonanza'])
		->asArray()->all();
	
		//用户CD账号数据
		$BN_Accounts = SaasBonanzaUser::find()->where(['uid'=>$uid,'is_active'=>1])->asArray()->all();
		//没有账号信息或者订单信息，直接return
		if(empty($daysOrders) && empty($BN_Accounts))
			return [];
	
		$orderDatas=[];
		foreach ($daysOrders as $order_p){
			$order_date = $order_p['paid_time'];
			$order_date = date('Y-m-d',$order_date);
			$orderDatas[$order_p['selleruserid']][$order_date][] = $order_p;
		}
	
		$char = [];
		$chart['xAxis'] = [];
		//$chart['yAxis'] = [];
		$chart['series'] = [];
		$chart['title'] = '近'.$days.'日Bonanza订单已统计的利润';
		$chart['subtitle'] = '基于启用的账号统计';
		$account_profit_count_data = [];//基于账号的订单利润数据
		$account_order_avg_data = [];//基于账号的参与利润统计的订单数量数据
		for ($i=$days;$i>=0;$i--){
			$day_profit_total_all = 0;//所有账号某天利润总额
			$day_order_total_all = 0;//所有账号某天参与利润统计的总单数
			$theday = date('Y-m-d', strtotime($today)-3600*24*$i);
			if($i==0)
				$chart['xAxis'][] = '今日';//只显示月，日
			else
				$chart['xAxis'][] = date('m-d', strtotime($theday));//只显示月，日
	
			foreach ($BN_Accounts as $account){
				//$name = $account['store_name']."(". $account['username'].")";
				$account_theday_profit = 0;
				$account_theday_order = 0;
				$thedayAccountOrderData = empty($orderDatas[$account['store_name']][$theday])?[]:$orderDatas[$account['store_name']][$theday];
				foreach ($thedayAccountOrderData as $od){
					$account_theday_order++;
					$day_order_total_all++;
						
					$profit = floatval($od['profit']);
					$account_theday_profit += $profit;
					$day_profit_total_all += $profit;
				}
	
				$account_profit_count_data[$account['store_name']][$theday] = $account_theday_profit;
				$account_order_avg_data[$account['store_name']][$theday] = empty($account_theday_order)?0:$account_theday_profit / $account_theday_order;
			}
			$account_profit_count_data['全部合计'][$theday] = $day_profit_total_all;
			$account_order_avg_data['全部合计'][$theday] = empty($day_order_total_all)?0:$day_profit_total_all / $day_order_total_all;
		}
	
		//将全部账号账号total项放在series 总利润类 的开头
		$chart['series'][] = [
		'name' => '全部账号(合计)',
		'color' => '#4572A7',
		'type' => 'column',
		'tooltip' => ['valueSuffix'=>'rmb'],
		'data' => array_values($account_profit_count_data['全部合计']),
		];
		unset($account_profit_count_data['全部合计']);
		foreach ($account_profit_count_data as $accountName=>$dateData){
			$serie = [];
			$serie['name'] = $accountName.'(合计)';
			$serie['color'] = '#4572A7';
			$serie['type'] = 'column';
			$serie['tooltip'] = ['valueSuffix'=>'rmb'];
			foreach ($dateData as $date=>$profit){
				$serie['data'][] = $profit;
			}
			$chart['series'][] = $serie;
		}
		//将全部账号账号avg 项放在series 平均利润类 的开头
		$chart['series'][] = [
		'name' => '全部账号(平均)',
		'color' => '#89A54E',
		'type' => 'spline',
		'yAxis' => 1,
		'tooltip' => ['valueSuffix'=>'rmb'],
		'data' => array_values($account_order_avg_data['全部合计']),
		];
		unset($account_order_avg_data['全部合计']);
		foreach ($account_order_avg_data as $accountName=>$dateData){
			$serie = [];
			$serie['name'] = $accountName.'(平均)';
			$serie['color'] = '#89A54E';
			$serie['type'] = 'spline';
			$serie['yAxis'] = 1;
			$serie['tooltip'] = ['valueSuffix'=>'rmb'];
			foreach ($dateData as $date=>$profit){
				$serie['data'][] = $profit;
			}
			$chart['series'][] = $serie;
		}
		return $chart;
	}
	
	
	/**
	 * 获取PM OMS dashboard广告
	 * @param unknown	$uid
	 * @param number 	$every_time_shows	每次展示广告数
	 */
	public static function getAdvertDataByUid($uid,$every_time_shows=2){
		$advertData = [];
		//$last_advert_id = \Yii::$app->redis->hget('BonanzaOms_DashBoard',"user_$uid".".last_advert");
		$last_advert_id = RedisHelper::RedisGet('BonanzaOms_DashBoard',"user_$uid".".last_advert" );
		if(empty($last_advert_id))
			$last_advert_id=0;
		$new_last_advert_id = $last_advert_id;
		if(!empty($last_advert_id)){
			$query = "SELECT * FROM `od_dash_advert` WHERE (`app`='Bonanza_Oms' or `app`='All_Oms') and `id`>".(int)$last_advert_id."  ORDER BY `id` ASC limit 0,$every_time_shows ";
			$command = Yii::$app->db->createCommand($query);
			$advert_records = $command->queryAll();
			foreach ($advert_records as $advert){
				$advertData[$advert['id']] = $advert;
				$new_last_advert_id = $advert['id'];
			}
			if(count($advertData)<$every_time_shows){
				$reLimit = $every_time_shows - count($advertData);
				$query_r = "SELECT * FROM `od_dash_advert` WHERE `app`='Bonanza_Oms' or `app`='All_Oms' ORDER BY `id` ASC limit 0,$reLimit ";
				$command = Yii::$app->db->createCommand($query_r);
				$advert_records_r = $command->queryAll();
				foreach ($advert_records_r as $advert_r){
					if(in_array($advert_r['id'],array_keys($advertData)))
						continue;
					$advertData[$advert_r['id']] = $advert_r;
					$new_last_advert_id = $advert_r['id'];
				}
			}
		}else{
			$query = "SELECT * FROM `od_dash_advert` WHERE `app`='Bonanza_Oms' or `app`='All_Oms' ORDER BY `id` ASC limit 0,$every_time_shows ";
			$command = Yii::$app->db->createCommand($query);
			$advert_records = $command->queryAll();
			foreach ($advert_records as $advert){
				$advertData[$advert['id']] = $advert;
				$new_last_advert_id = $advert['id'];
			}
		}
		
		//$set_advert_redis = \Yii::$app->redis->hset('BonanzaOms_DashBoard',"user_$uid".".last_advert",$new_last_advert_id);
		$set_advert_redis = RedisHelper::RedisSet('BonanzaOms_DashBoard',"user_$uid".".last_advert",$new_last_advert_id);
		return $advertData;
	}
	
	/**
	 * 获取用户绑定的PM账号的异常情况，如token过期之类
	 * @param int $uid
	 */
	public static function getUserAccountProblems($uid){
		if(empty($uid))
			return [];
		
		$BonanzaAccounts = SaasBonanzaUser::find()->where(['uid'=>$uid])->asArray()->all();
		if(empty($BonanzaAccounts))
			return [];
		
		$accountUnActive = [];//未开启同步的账号
		$tokenExpired = [];//授权失败的账号
		$order_retrieve_errors = [];//获取订单失败
		$initial_order_failed = [];//首次绑定时，获取订单失败
		foreach ($BonanzaAccounts as $account){
			if(empty($account['is_active'])){
				$accountUnActive[] = $account;
				continue;
			}
			if( $account['order_retrieve_message']=='账号或token错误'){
				$tokenExpired[] = $account;
				continue;
			}
			if(empty($account['initial_fetched_changed_order_since']) || $account['initial_fetched_changed_order_since']=='0000-00-00 00:00:00'){
				$initial_order_failed[] = $account;
				continue;
			}
			if($account['order_retrieve_message']!=='账号或token错误' && $account['order_retrieve_message']!=='get non order' && !empty($account['order_retrieve_message'])){
				$order_retrieve_errors[] = $account;
				continue;
			}
		}
		$problems=[
			'unActive'=>$accountUnActive,
			'token_expired'=>$tokenExpired,
			'initial_failed'=>$initial_order_failed,
			'order_retrieve_failed'=>$order_retrieve_errors,
		];
		return $problems;
	}
//@todo	
/************************************
 * Bonanza 
 * 
 * 
 ************************************/
	
	 
	/**
	 * 获取新订单，按照订单的create time 来获取
	 * @param1 : 订单创建时间从  xxx 开始，datetime 格式的北京时间例如 2016-04-15 15:30:21
	 * @param2 : 订单创建时间 结束xxx ，datetime 格式的北京时间例如 2016-04-15 15:40:21
	 * returns: success:
	 *          message:
	 *          content:
	 *           ....

	 */
	public static function fetchToken(){
		$rtn['success'] = false;
		$rtn['message'] = '';
		$rtn['content'] = '';
		 
		
		$proxyReturn = self::callProxyToDo('fetchToken');
		
		if (!$proxyReturn['success'] or empty($proxyReturn['result'])){
			$rtn['success'] = false;
			$rtn['message'] = 'proxy 执行失败'.$proxyReturn['message'];
			return $rtn;
		}
		
		$rtn['content'] = json_decode($proxyReturn['result'],true);
		if (empty($rtn['content']['rtn'])){
			$rtn['success'] = false;
			$rtn['message'] = 'E004 API 无返回'.$proxyReturn['message'];
			return $rtn;
		}
		
		$apiResult = json_decode($rtn['content']['rtn'],true);
			
		if (empty($apiResult['ack']) or $apiResult['ack']<>'Success' or empty($apiResult['fetchTokenResponse'])){
			$rtn['success'] = false;
			$rtn['message'] = 'E005A API returns error:';
				
			if (empty($apiResult['ack']))
				$rtn['message'].= 'Ack is empty.';
			
			if (empty($apiResult['fetchTokenResponse']))
				$rtn['message'].= 'Token response is empty.';
				
			if (!empty($apiResult['errorMessage']['message']))
				$rtn['message'] .= (string)$apiResult['errorMessage']['message'];
				
			return $rtn;
		}else{
			$rtn['success'] = true;
			$rtn['authToken'] = $apiResult['fetchTokenResponse']['authToken'];
			$rtn['expirationTime'] = $apiResult['fetchTokenResponse']['hardExpirationTime'];
			$rtn['authenticationURL'] = $apiResult['fetchTokenResponse']['authenticationURL'];
			//执行成功了，例如
			//{"success":true,"message":"","rtn":"{\"ack\":\"Success\",\"version\":\"1.0beta\",\"timestamp\":\"2016-04-20T02:06:43.000Z\",\"completeSaleResponse\":{}}","proxyURL":null}
		}
		
		$rtn['run_time'] = $proxyReturn['run_time'];
	
		return $rtn;
	}
	
	/**
	 * 获取新订单，按照订单的create time 来获取
	 * http://api.bonanza.com/docs/reference/get_orders
	 * http://api.bonanza.com/docs/reference/complete_sale
	 * @param1 : 订单创建时间从  xxx 开始，datetime 格式的北京时间例如 2016-04-15 15:30:21
	 * @param2 : 订单创建时间 结束xxx ，datetime 格式的北京时间例如 2016-04-15 15:40:21
	 * returns: success:
	 *          message:
	 *          content:
	 *          orders: array()
	 */
	public static function GetNewSalesWithInTime($fromBJTime,$toBJTime){
		return self::GetSalesWithInTime($fromBJTime, $toBJTime,'create');
	}
	
	/**
	 * 获取新订单，按照订单的 last modify time 来获取
	 * @param1 : 订单修改时间从  xxx 开始，datetime 格式的北京时间例如 2016-04-15 15:30:21
	 * @param2 : 订单修改时间 结束xxx ，datetime 格式的北京时间例如 2016-04-15 15:40:21
	 * returns: success:
	 *          message:
	 *          content:
	 *          orders: array()
	 */
	public static function GetModifiedSalesWithInTime($fromBJTime,$toBJTime){
		return self::GetSalesWithInTime($fromBJTime, $toBJTime,'mod');
	}
	
	/**
	 * 获取新订单，按照订单的create time 来获取
	 * @param1 : 订单创建/修改时间从  xxx 开始，datetime 格式的北京时间例如 2016-04-15 15:30:21
	 * @param2 : 订单创建/修改时间 结束xxx ，datetime 格式的北京时间例如 2016-04-15 15:40:21
	 * @param3 : create 表示是新建的订单， mod 表示获取这个时间段修改过的订单
	 * returns: success:
	 *          message:
	 *          content:
	 *          orders: array()
	 */
	private static function GetSalesWithInTime($fromBJTime,$toBJTime,$type='create'){
		$rtn['success'] = false;
		$rtn['message'] = '';
		$rtn['content'] = '';
		$rtn['orders'] = array();
		$rtn['seller_id']=0;	
		
		$action = "getOrders";
	 
	 	//北京时间比 UTC 是 +8 hours，所以要先减去 8 hours 得到UTC时间
		$proxyReturn = self::callProxyToDo($action ,[$type.'TimeFrom'=>TimeUtil::getUTCTimeFormat(date('Y-m-d H:i:s',strtotime($fromBJTime))),
													 $type.'TimeTo'=>TimeUtil::getUTCTimeFormat(date('Y-m-d H:i:s',strtotime($toBJTime))) ] );
		$rtn['url'] = $proxyReturn['url'];
		if (!$proxyReturn['success'] or empty($proxyReturn['result'])){
			$rtn['success'] = false;
			$rtn['message'] = 'proxy failed:'.$proxyReturn['message'];
			return $rtn;
		}
		
		$rtn['content'] = json_decode($proxyReturn['result'],true);
		
		if (empty($rtn['content']['success'])){
			$rtn['success'] = false;
			$rtn['message'] = 'API returns error:'.$proxyReturn['message'];
			return $rtn;
		}
		$rtn['success'] = true;
		$rtn['orders'] = $rtn['content']['orders'];
		$rtn['content'] = '';
		$rtn['run_time'] = $proxyReturn['run_time'];
		return $rtn;
	}

	public static function shipOrder($orderid,$carrier='',$trackNo='',$noteMessage=''){
		return self::CompleteSale($orderid,'ship',$carrier ,$trackNo,$noteMessage);
	}
	
	public static function acceptOrder($orderid ){
		return self::CompleteSale($orderid,'accept' );
	}
	
	public static function refuseOrder($orderid ){
		return self::CompleteSale($orderid,'refuse' );
	}
	
	/**
	 * 获取新订单，按照订单的create time 来获取
	 * @param1 : $transactionId orderid,e.g. 39964773
	 * @param2 : $operation, support 'accpet'/'refuse'/'ship'
	 * @param3 : $noteMessage
	 * returns: success:
	 *          message:
	 *          content:
	 *          orders: array()
	 */
	private static function CompleteSale($transactionId,$operation,$carrier='',$trackNo='',$noteMessage=''){
		$rtn['success'] = false;
		$rtn['message'] = '';
		$rtn['content'] = '';

		$action = "completeSale";
		
		$validActions['accept']=1;
		$validActions['refuse']=1;
		$validActions['ship']=1;
		
		if (empty($validActions[$operation])){
			$rtn['success'] = false;
			$rtn['message'] = 'Invalid operation '.$operation;
			return $rtn;
		}
			
		$params = array();
		$params['operation'] = $operation;
		
		//把应该有的参数，format一下给proxy	
		if (!empty($noteMessage))
			$params['noteMessage'] = $noteMessage;
		
		if (!empty($carrier))
			$params['carrier'] = $carrier;
		
		if (!empty($trackNo))
			$params['trackNo'] = $trackNo;
		
		if (!empty($transactionId))
			$params['transactionId'] = $transactionId;
		
		$proxyReturn = self::callProxyToDo($action ,$params );
		$rtn['url'] = $proxyReturn['url'];
		if (!$proxyReturn['success'] or empty($proxyReturn['result'])){
			$rtn['success'] = false;
			$rtn['message'] = 'proxy failed:'.$proxyReturn['message'];
			return $rtn;
		}
	
		$rtn['content'] = json_decode($proxyReturn['result'],true);
	
		if (empty($rtn['content']['success']) or empty($rtn['content']['rtn'])){
			$rtn['success'] = false;
			$rtn['message'] = 'E005 API returns error:'.$proxyReturn['message'];
			return $rtn;
		}

		$apiResult = json_decode($rtn['content']['rtn'],true);
		 
		if (empty($apiResult['ack']) or $apiResult['ack']<>'Success'){
			$rtn['success'] = false;
			$rtn['message'] = 'E006 API returns error:';
			
			if (empty($apiResult['ack']))
				$rtn['message'].= 'Ack is empty.';
			
			if (!empty($apiResult['errorMessage']['message']))
				$rtn['message'] .= (string)$apiResult['errorMessage']['message'];
			
			return $rtn;
		}else{
			$rtn['success'] = true;
			//执行成功了，例如 
			//{"success":true,"message":"","rtn":"{\"ack\":\"Success\",\"version\":\"1.0beta\",\"timestamp\":\"2016-04-20T02:06:43.000Z\",\"completeSaleResponse\":{}}","proxyURL":null}
		}

		//$rtn['content'] = $proxyReturn['result'];
		$rtn['run_time'] = $proxyReturn['run_time'];
		return $rtn;
	}
	
	public function GetItemInfos($itemid){
		$rtn['success'] = false;
		$rtn['message'] = '';
		$rtn['content'] = '';
		$rtn['iteminfo'] = array();
		$rtn['seller_id']=0;
		
		$proxyReturn = self::callProxyToDo('getSingleItem',array('ids'=>$itemid));
		
		$rtn['url'] = $proxyReturn['url'];
		
		if (!$proxyReturn['success'] or empty($proxyReturn['result'])){
		    $rtn['success'] = false;
		    $rtn['message'] = 'proxy failed:'.$proxyReturn['message'];
		    return $rtn;
		}
		
		if($proxyReturn['success']){
		    $html_result = json_decode($proxyReturn["result"],true);
// 		    print_r($html_result);exit();
		    $html_result_rtn = $html_result["rtn"];
// 		    print_r($html_result_rtn["getSingleItemResponse"]["item"]);exit();
		    if(empty($html_result_rtn)){
		        $rtn['success'] = false;
    			$rtn['message'] = 'API returns empty content.rtn:'.$proxyReturn['message'];
    			return $rtn;
		    }
		    if($html_result_rtn["ack"] == "Success"&&!empty($html_result_rtn["getSingleItemResponse"]["item"])){
		        $rtn['message'] = "Done,got item infos ";
		        $rtn['success'] = true;
		        $rtn['iteminfo'] = $html_result_rtn["getSingleItemResponse"]["item"];
// 		        print_r($html_result_rtn["getSingleItemResponse"]["item"]["galleryURL"]);
		    }
		}
		
// 		$rtn['content'] = json_decode($proxyReturn['result'],true);
		
// 		if (empty($rtn['content']['rtn'])){
// 			$rtn['success'] = false;
// 			$rtn['message'] = 'API returns empty content.rtn:'.$proxyReturn['message'];
// 			return $rtn;
// 		}
// 		$rtn['content']['rtn'] = urldecode($rtn['content']['rtn']);
		
		//echo "got proxy result:".print_r($rtn['content']['rtn'],true)."\n";
// 		$BonanzaReturns = $rtn['content']['rtn'];
		
// 		$BonanzaReturns = simplexml_load_string($BonanzaReturns, 'SimpleXMLElement', LIBXML_NOCDATA);
		//现在判断是否return了正常的结果
// 		if (!empty($BonanzaReturns->response->item)){
// 			$rtn['message'] = "Done,got item infos ";
// 			$rtn['success'] = true;
		
// 			//echo  print_r($BonanzaReturns->response , true);
// 			//解释获取的order，放到 $rtn['orders'] 中
// 			if (!empty($BonanzaReturns->response->item)){
// 				$rtn['iteminfo'] = $BonanzaReturns->response->item;
// 			}
// 		}
		return $rtn;
	}
	
	
	/**
	 * 获取商品信息，这个可以被异步调用，用来完善order detail里面的商品信息
	 * @param1 : eans    ['11111','22222','33333']
	 * returns: success:
	 *          message:
	 *          listing:['11111'=> array(),'22222','33333']
	 */
	public function GetProductDetails($eans=array()){
		$rtn['success'] = false;
		$rtn['message'] = '';
		$rtn['content'] = '';
		$rtn['listing'] = array();
		$rtn['seller_id']=0;		
	
		$proxyReturn = self::callProxyToDo('listing',array('refs'=>implode(',', $eans)));
		$rtn['url'] = $proxyReturn['url'];
		if (!$proxyReturn['success'] or empty($proxyReturn['result'])){
			$rtn['success'] = false;
			$rtn['message'] = 'proxy failed:'.$proxyReturn['message'];
			return $rtn;
		}
	
		$rtn['content'] = json_decode($proxyReturn['result'],true);
	
		if (empty($rtn['content']['rtn'])){
			$rtn['success'] = false;
			$rtn['message'] = 'API returns empty content.rtn:'.$proxyReturn['message'];
			return $rtn;
		}
		$rtn['content']['rtn'] = urldecode($rtn['content']['rtn']);
		//echo "got proxy result:".print_r($rtn['content']['rtn'],true)."\n";
		$BonanzaReturns = $rtn['content']['rtn'];
	
		$BonanzaReturns = simplexml_load_string($BonanzaReturns, 'SimpleXMLElement', LIBXML_NOCDATA);
		
		//现在判断是否return了正常的结果
		if (!empty($BonanzaReturns->response->status) and strtolower((String)$BonanzaReturns->response->status)=='ok' ){
			$rtn['message'] = "Done,got total count:".$BonanzaReturns->response->resultcount;
			$rtn['success'] = true;
			 
			//echo  print_r($BonanzaReturns->response , true);
			//解释获取的order，放到 $rtn['orders'] 中
			if (!empty($BonanzaReturns->response->products)){
				foreach( $BonanzaReturns->response->products->product as $aProd){
					/*
					*返回就结构 ：
					* https://developer.Bonanza.com/blog/en/documentation/product-data/product-listing-secure
					* */
			   		//print_r($aProd);
			   		$ean = empty($aProd->references->barcode)?'':(string)$aProd->references->barcode;
			   		if(!empty($ean)){//由于订单获取不到priductid，改为用ean作为index
						$rtn['listing'][$ean] = $aProd;
			   		}
				}//end of each order returned
			}
		}
		//$rtn['content']='';
		//print_r($rtn['listing']);
		return $rtn;
	}
	
	/**
	 * 设置 order item 的递送信息，track url 可以为空白，但如果有value的话，必须填写正确的格式。
	 * @param1 : itemid    45987799
	 * @param2 : carrier   TNT
	 * @param3 : track_no	RN36546846CN
	 * @param4 : track url	blank / http://17trk.net， 空白或者 完整的格式，前面是 http 结尾是 .xxx
	 * returns: success:
	 *          message:
	 *          listing:
	 */
	public function SetTrackNumber($itemid,$carrier ,$track_no,$track_url=''){
		$rtn['success'] = false;
		$rtn['message'] = '';
		$rtn['content'] = '';
		$rtn['seller_id']=0;
	
		$proxyReturn = self::callProxyToDo('settrackingpackageinfos',array('itemid'=>$itemid,'transporter_name'=>$carrier, 'tracking_number'=>$track_no,'tracking_url'=>$track_url));
		$rtn['url'] = $proxyReturn['url'];
		if (!$proxyReturn['success'] or empty($proxyReturn['result'])){
			$rtn['success'] = false;
			$rtn['message'] = 'proxy failed:'.$proxyReturn['message'];
			return $rtn;
		}
	
		$rtn['content'] = json_decode($proxyReturn['result'],true);
	
		if (empty($rtn['content']['rtn'])){
			$rtn['success'] = false;
			$rtn['message'] = 'API returns empty content.rtn:'.$proxyReturn['message'];
			return $rtn;
		}
		$rtn['content']['rtn'] = urldecode($rtn['content']['rtn']);
		//echo "got proxy result:".print_r($rtn['content']['rtn'],true)."\n";
		$BonanzaReturns = $rtn['content']['rtn'];
	
		$BonanzaReturns = simplexml_load_string($BonanzaReturns, 'SimpleXMLElement', LIBXML_NOCDATA);
	
		if (!empty($BonanzaReturns->error->message)){
			$rtn['message'] =  "API return error:".$BonanzaReturns->error->message;
			if (!empty($BonanzaReturns->error->details)){
				foreach ($BonanzaReturns->error->details->detail as $aDetail)
					$rtn['message'] .= $aDetail;
			}
			return $rtn;
		}
	
		//现在判断是否return了正常的结果
		if (!empty($BonanzaReturns->response->status) and strtolower((String)$BonanzaReturns->response->status)=='ok' ){
			$rtn['message'] = "Done" ;
			$rtn['success'] = true;
		}
		//$rtn['content']='';
		return $rtn;
	}
	
	
	public static function setBonanzaToken($BonanzaToken1){
		self::$BonanzaToken = $BonanzaToken1;
		 
	}
	
	public static function callProxyToDo($action,$param=array()){
		$result['success'] = true;
		$result['message'] = '';
		$result['result'] = '';
		$result['url'] = '';
		$Ext_Call = "BonanzaProxy";
		$url = self::PROXY_URL;
		$url = str_replace("@BonanzaToken@",self::$BonanzaToken,$url);
		 
		$current_time=explode(" ",microtime());
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
		
		$ch = curl_init();
		//echo "using parameters.".print_r($param,true)."\n";
		$url .= "&action=$action"."&".http_build_query(['params'=>json_encode($param)]);
		$result['url'] = $url;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	//	echo " curl try to :$url <br>";
		$output = curl_exec($ch);
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($curl_errno > 0) { // network error
			$result['success'] = false;
			$result['message'] = "can not connect proxy,network error $httpCode";
			curl_close($ch);
			return $result;
		}
	 
		
		$current_time=explode(" ",microtime());
		$time2=round($current_time[0]*1000+$current_time[1]*1000);
		
		//计算累计做了多少次external 的调用以及耗时
		
		$run_time = $time2 - $time1; //这个得到的$time是以 ms 为单位的
		
		TrackingAgentHelper::extCallSum($Ext_Call,$run_time);
		//echo " curl output:".print_r($output,true)."\n";
		$result['result'] = $output;
		$result['run_time'] = $run_time;
		//	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ Ubi.4 ".$JOBID],"file");
		if ($httpCode == '200'){
		}else{ // network error
			$result['success'] = false ;
			$result['message'] = "can not connect proxy,network error $httpCode";
		}

		curl_close($ch);
		//print_r($result);
		//exit();
		return $result;
	}
	
	
}
?>