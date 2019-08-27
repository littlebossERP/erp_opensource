<?php
namespace eagle\modules\order\helpers;
use yii;
use yii\data\Pagination;

use eagle\models\SaasCdiscountUser;
use eagle\modules\listing\models\WishApiQueue;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\order\models\CdiscountOrder;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\CdiscountOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\SysLogHelper ;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use yii\base\Exception;
use eagle\modules\listing\helpers\CdiscountProxyConnectHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\listing\models\CdiscountOfferList;
use eagle\modules\order\models\OdOrderItem;
use eagle\assets\PublicAsset;
use eagle\modules\util\helpers\ImageCacherHelper;
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
 * Cdiscount api 接口模块 
 +------------------------------------------------------------------------------
 * @category	item
 * @package		Helper/item
 * @subpackage  Exception
 * @author		lkh
 +------------------------------------------------------------------------------
 */
class CdiscountOrderInterface{
	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回Cdiscount订单不需要发货的item 的sku，例如INTERETBCA
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
	
	//Cdiscount可发货的item状态(AcceptationState)
	public static $CAN_SHIP_ORDERITEM_STATUS = array('AcceptedBySeller','ShippedBySeller');
	//Cdiscount 不可发货的item状态(AcceptationState)
	public static $CANNOT_SHIP_ORDERITEM_STATUS = array('RefusedBySeller','ShipmentRefusedBySeller','CancelledBeforeNotificationByCustomer','CancelledBeforePaymentByCustomer','CancellationRequestPending');
	/**已废弃
	 +---------------------------------------------------------------------------------------------
	 * Cdiscount订单发货
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
	static public function shipCdiscountOrder($orderid , $tracking_provider , $tracking_number='' , $ship_note=''){
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
			$journal_id = SysLogHelper::InvokeJrn_Create("Cdiscount",__CLASS__, __FUNCTION__ , array($orderid , $tracking_provider , $tracking_number , $ship_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['Cdiscount',__CLASS__,__FUNCTION__,'Background',"can not find the orderid:".$orderid],"edb\global");
				return array(
					'success' => false,
					'message' => 'CdiscountOD010: Not found such internal order id: '.$orderid
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
			
			$rtn = CdiscountOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['Cdiscount',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage() ],"edb\global");
			return array(
				'success' => false,
				'message' => 'CdiscountOD500: : '.$e->getMessage(),
			);
		}
	}//end of shipCdiscountOrder
	
	/**已废弃/未启用
	 +---------------------------------------------------------------------------------------------
	 * Cdiscount订单退货/退款/取消
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
	static function cancelCdiscountOrder($orderid , $reason_code , $reason_note=''){
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
			$journal_id = SysLogHelper::InvokeJrn_Create("Cdiscount",__CLASS__, __FUNCTION__ , array($orderid , $reason_code , $reason_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['Cdiscount',__CLASS__,__FUNCTION__,'Background', "can not find the orderid:".$orderid ],"edb\global");
				return array(
					'success' => false,
					'message' => 'CdiscountOD010: Not found such internal order id: '.$orderid
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
			
			$rtn = CdiscountOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['Cdiscount',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage() ],"edb\global");
			return array(
				'success' => false,
				'message' => 'CdiscountOD500: : '.$e->getMessage(),
			);
		}
	}//end of cancelCdiscountOrder
	
	
	/**已废弃/未启用
	 +---------------------------------------------------------------------------------------------
	 * 修改订单Cdiscount订单发货信息
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
	static public function modifyCdiscountOrderShippedInfo($orderid , $tracking_provider , $tracking_number='' , $ship_note=''){
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
			$journal_id = SysLogHelper::InvokeJrn_Create("Cdiscount",__CLASS__, __FUNCTION__ , array($orderid , $tracking_provider , $tracking_number , $ship_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['Cdiscount',__CLASS__,__FUNCTION__,'Background',"can not find the orderid:".$orderid ],"edb\global");
				return array(
					'success' => false,
					'message' => 'CdiscountOD010: Not found such internal order id: '.$orderid
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
			
			$rtn = CdiscountOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['Cdiscount',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage()  ],"edb\global");
			return array(
				'success' => false,
				'message' => 'CdiscountOD500: : '.$e->getMessage(),
			);
		}
		
	}//end of modifyCdiscountOrderShippedInfo
	
	public static  $ShippingMethods=array(
		'China Post'=>[
			'CarrierId'=>12,
			'DefaultURL'=>'http://www.17track.net/fr',
			'Name'=>'China Post',
		],
		'Chronopost'=>[
			'CarrierId'=>13,
			'DefaultURL'=>'http://www.chronopost.fr/fr/particulier/suivez-votre-colis',
			'Name'=>'Chronopost',
		],
		'Colis Privé'=>[
			'CarrierId'=>15,
			'DefaultURL'=>'https://www.colisprive.fr',
			'Name'=>'Colis Privé',
		],
		'Deutsche Post'=>[
			'CarrierId'=>11,
			'DefaultURL'=>'https://www.deutschepost.de/sendung/simpleQuery.html?locale=en_GB',
			'Name'=>'Deutsche Post',
		],
		'DHL'=>[
			'CarrierId'=>10,
			'DefaultURL'=>'http://www.dhl.fr/fr/dhl_express/suivi_expedition.html',
			'Name'=>'DHL',
		],
		'DPD'=>[
			'CarrierId'=>9,
			'DefaultURL'=>'http://www.dpd.fr/trace',
			'Name'=>'DPD',
		],
		'GLS'=>[
		'CarrierId'=>8,
			'DefaultURL'=>'https://gls-group.eu/FR/fr/suivi-colis',
			'Name'=>'GLS',
		],
		'La Poste - Colissimo / Coliposte'=>[
			'CarrierId'=>14,
			'DefaultURL'=>'http://www.colissimo.fr/portail_colissimo/suivre.do?language=fr_FR',
			'Name'=>'La Poste - Colissimo / Coliposte',
		],
		'La Poste - Courrier'=>[
			'CarrierId'=>7,
			'DefaultURL'=>'https://www.laposte.fr/particulier/outils/suivre-vos-envois',
			'Name'=>'La Poste - Courrier',
		],
		'Mondial Relay'=>[
			'CarrierId'=>6,
			'DefaultURL'=>'http://www.mondialrelay.fr/suivi-de-colis',
			'Name'=>'Mondial Relay',
		],
		'PostNL'=>[
			'CarrierId'=>5,
			'DefaultURL'=>'https://jouw.postnl.be',
			'Name'=>'PostNL',
		],
		'Relais Colis'=>[
			'CarrierId'=>1,
			'DefaultURL'=>'http://www.relaiscolis.com/index.php/application-suivi-colis',
			'Name'=>'Relais Colis',
		],
		'Royal Mail'=>[
			'CarrierId'=>4,
			'DefaultURL'=>'https://www.aftership.com/fr/courier/royal-mail',
			'Name'=>'Royal Mail',
		],
		'TNT'=>[
			'CarrierId'=>3,
			'DefaultURL'=>'http://www.tnt.fr/public/suivi_colis/recherche/index.do',
			'Name'=>'TNT',
		],
		'UPS'=>[
			'CarrierId'=>2,
			'DefaultURL'=>'https://www.ups.com/WebTracking/track?loc=fr_fr',
			'Name'=>'UPS',
		],
		'Bpost'=>[
			'CarrierId'=>16,
			'DefaultURL'=>'https://www.aftership.com/fr/couriers/bpost-international',
			'Name'=>'Bpost',
		],
		'China EMS (ePacket)'=>[
			'CarrierId'=>17,
			'DefaultURL'=>'http://www.ems.com.cn/english.html',
			'Name'=>'China EMS (ePacket)',
		],
		'FedEx'=>[
			'CarrierId'=>18,
			'DefaultURL'=>'https://www.fedex.com/en-us/home.html',
			'Name'=>'FedEx',
		],
		'SF Express'=>[
			'CarrierId'=>19,
			'DefaultURL'=>'http://www.sf-express.com/gb/en/',
			'Name'=>'SF Express',
		],
		'USPS'=>[
			'CarrierId'=>20,
			'DefaultURL'=>'https://tools.usps.com/go/TrackConfirmAction_input',
			'Name'=>'USPS',
		],
		'Singapore Post'=>[
			'CarrierId'=>21,
			'DefaultURL'=>'https://www.aftership.com/fr/couriers/singapore-post',
			'Name'=>'Singapore Post',
		],
		'4PX'=>[
			'CarrierId'=>22,
			'DefaultURL'=>'https://www.aftership.com/fr/couriers/4px',
			'Name'=>'4PX',
		],
		'Malaysia Post'=>[
			'CarrierId'=>23,
			'DefaultURL'=>'https://www.aftership.com/fr/couriers/malaysia-post-posdaftar',
			'Name'=>'Malaysia Post',
		],
		'Yanwen'=>[
			'CarrierId'=>24,
			'DefaultURL'=>'http://track.yw56.com.cn/en-US/',
			'Name'=>'Yanwen',
		],
		'CNE Express'=>[
			'CarrierId'=>25,
			'DefaultURL'=>'https://www.aftership.com/fr/couriers/cnexps',
			'Name'=>'CNE Express',
		],
		'SFC Service'=>[
			'CarrierId'=>26,
			'DefaultURL'=>'https://www.aftership.com/fr/couriers/sfcservice',
			'Name'=>'SFC Service',
		],
		'GEODIS'=>[
			'CarrierId'=>27,
			'DefaultURL'=>'https://www.aftership.com/fr/couriers/geodis-calberson-fr',
			'Name'=>'GEODIS',
		],
		'Other'=>[
			'CarrierId'=>'SelectedOtherCarrierId',
			'DefaultURL'=>'',
			'Name'=>'Other',
		],
	);
	
	public static function getShippingCodeNameMap(){
		$ShippingMethods = self::$ShippingMethods;
		$mapping = [];
		foreach ($ShippingMethods as $name=>$info){
			if(!empty($info['Name']))
				$mapping[$name] = $info['Name'];
		}
		return $mapping;
	}
	
	
	public static function getShippingMethodDefaultURL($method){
		$ShippingMethods = self::$ShippingMethods;
		if(isset($ShippingMethods[$method]['DefaultURL']))
			return $ShippingMethods[$method]['DefaultURL'];
		else
			return '';
	}
	
	
	/* 返回该 platform 允许的 默认 carrier name
	 * yzq @ 2015-7-9
	 * */
	public static function getDefaultShippingCode(){
		return 'OTHER';
	}
	
	/**
	 * 根据cdiscount orderid获取买家邮箱(前端触发)
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
					$cdAccount = SaasCdiscountUser::find()->where(['username'=>$seller,'uid'=>$uid])->asArray()->one();
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
					$result['message'] .= $orderid.'：不是cdiscount订单;';
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
	 * 根据cdiscount orderid 获取买家billing info
	 * @param 		str 		$orderid
	 * @return 		array
	 */
	public static function getBillingInfoByOrder($orderid){
		$billingInfo = [];
		$cdOrder = CdiscountOrder::findOne($orderid);
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
			
		}else{
			$billingInfo['address'] = '';
			$billingInfo['post_code'] = '';
			$billingInfo['phone'] = '';
			$billingInfo['company'] = '';
			$billingInfo['city'] = '';
			$billingInfo['country'] = '';
			$billingInfo['name'] = '';
		}
		
		return $billingInfo;
		
	}
	/**
	 * 根据cdiscount orderid 获取买家邮箱
	 * @param 		array 		$cdiscountAccount
	 * @param 		str 		$orderid
	 * @return 		string
	 */
	public static function getEmailByOrderID($cdiscountAccount , $orderid){
		$timeout=240; //s

		$params['orderid'] = $orderid;
		$get_param['query_params'] = json_encode($params);

		$config = array('tokenid' => $cdiscountAccount['token']);
		$get_param['config'] = json_encode($config);
		$emailMessage=CdiscountProxyConnectHelper::call_Cdiscount_api("getEmailByOrderID",$get_param,$post_params=array(),$timeout );

		$customer_email = "";
		
		if (isset($emailMessage['success']) && ($emailMessage['success'] == true || $emailMessage['success'] == 1) ){
			if(!empty($emailMessage['proxyResponse']['success'])){
				if(!empty($emailMessage['proxyResponse']['emailMessage']['s_Body']['GenerateDiscussionMailGuidResponse']['GenerateDiscussionMailGuidResult']['a_MailGuid']))
					$customer_email = $emailMessage['proxyResponse']['emailMessage']['s_Body']['GenerateDiscussionMailGuidResponse']['GenerateDiscussionMailGuidResult']['a_MailGuid'];
			}
		}
		
		return $customer_email;
	}//end of GetEmailByOrderID
	
	/**
	 * 根据uid 获取该用户需要提醒的OMS信息
	 * @return 		array
	 * @author		lzhl		2014/12/14
	 */
	public static function getCdiscountReminder($uid){
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
		$oldReminder = ConfigHelper::getConfig("CdiscountOMS/ReminderInfo",'NO_CACHE');
		
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
		CdiscountOrderHelper::$CD_OMS_WEIRD_STATUS;
		'sus'=>'CD后台状态和小老板状态不同步',//satatus unSync 状态不同步
		'wfs'=>'提交发货或提交物流',//waiting for shipment
		'wfd'=>'交运至物流商',//waiting for dispatch
		'wfss'=>'等待手动标记发货，或物流模块"确认已发货"',//waiting for sing shipped ,or confirm dispatch
		 */
		
		//$yifukuan_status = $targetStatus['已付款'];
		//$fahuozhong_status = $targetStatus['发货中'];
		//$yifahuo_status = $targetStatus['已发货'];
		if($remindYiFuKuan){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'cdiscount'])->andWhere(['weird_status'=>'wfs'])->count();
			if($ot_Orders>0)
				$rtn['remind']['YiFuKuan'] = $ot_Orders;
		}
		if($remindFaHuoZhong){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'cdiscount'])->andWhere(['weird_status'=>'wfd'])->count();
			if($ot_Orders>0)
				$rtn['remind']['FaHuoZhong'] = $ot_Orders;
		}
		if($remindYiFaHuo){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'cdiscount'])->andWhere(['weird_status'=>'wfss'])->count();
			if($ot_Orders>0)
				$rtn['remind']['YiFaHuo'] = $ot_Orders;
		}
		if($remindZhuangTaiYiChang){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'cdiscount'])->andWhere(['weird_status'=>'sus'])->count();
			if($ot_Orders>0)
				$rtn['remind']['ZhuangTaiYiChang'] = $ot_Orders;
		}
		if($remindWuTianWeiShangWang){
			$ot_Orders = OdOrder::find()->where(['order_source'=>'cdiscount'])->andWhere(['weird_status'=>'tuol'])->count();
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
		 
		
		$oldReminder = ConfigHelper::getConfig("CdiscountOMS/ReminderInfo",'NO_CACHE');
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
		
		ConfigHelper::setConfig("CdiscountOMS/ReminderInfo", json_encode($oldReminder));
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
		$sql_1 =SaasCdiscountUser::find();
		$all_accounts = $sql_1->count();
		
		
		//Yii::$app->db_queue->createCommand("SELECT COUNT( * ) AS  'accounts', COUNT( DISTINCT  `uid` ) AS  'uids' FROM  `saas_cdiscount_user` WHERE 1 ");
		$all_uids = $sql_1->select('DISTINCT  `uid`')->count();
		
		//$rows_1 = $sql_1->queryOne();
		//$all_accounts = $rows_1['accounts'];
		//$all_uids = $rows_1['uids'];
			//启用账号统计
		$sql_2 =SaasCdiscountUser::find()->where(['is_active'=>1]);
		//$sql_2 = Yii::$app->db_queue->createCommand("SELECT COUNT( * ) AS  'active' FROM  `saas_cdiscount_user` WHERE  `is_active` =1 ");
		$active_accounts = $sql_2->count();
		
		//$rows_2 = $sql_2->queryOne();
		//$active_accounts = $rows_2['active'];
		$unActive_accounts =$all_accounts - $active_accounts;
			//token过期用户统计
		$sql_3 =SaasCdiscountUser::find()->where(" `token_expired_date` <= '".date("Y-m-d H:i:s",strtotime($the_day)-3600*24)."' " );
		$rows_3 = $sql_3->asArray()->all();
		//$sql_3 = Yii::$app->db_queue->createCommand("SELECT * FROM  `saas_cdiscount_user` WHERE  `token_expired_date` <=  '".date("Y-m-d H:i:s",strtotime($the_day)-3600*24)."' ");
		//$rows_3 = $sql_3->queryAll();
		$token_expired_accounts = count($rows_3);
		$toekn_expired_site = [];
		foreach ($rows_3 as $row){
			$toekn_expired_site[] = $row['site_id'];
		}
			//未初始化订单账号
		$sql_4 =SaasCdiscountUser::find()->where(" `initial_fetched_changed_order_since` IS NULL OR `initial_fetched_changed_order_since` = '0000-00-00 00:00:00' ");
		$rows_4 = $sql_4->asArray()->all();
		
		//$sql_4 = Yii::$app->db_queue->createCommand("SELECT * FROM  `saas_cdiscount_user` WHERE `initial_fetched_changed_order_since` IS NULL OR `initial_fetched_changed_order_since` =  '0000-00-00 00:00:00' ");
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
		
		$sql = "SELECT saas_cdiscount_user.`uid` , MIN( saas_cdiscount_user.`create_time` ) create_time, user_base.user_name
				FROM  `saas_cdiscount_user` 
				LEFT JOIN user_base ON ( saas_cdiscount_user.uid = user_base.uid ) 
				GROUP BY saas_cdiscount_user.uid";
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
					//$day_count = \Yii::$app->redis->hget('CdiscountOms_TempData',"user_".$row['uid'].".".$targetDay);
					$day_count = RedisHelper::RedisGet('CdiscountOms_TempData',"user_".$row['uid'].".".$targetDay );
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
			//$set_avg = \Yii::$app->redis->hset('CdiscountOms_TempData',"user_".$row['uid'].'avg_pre_day',$one_user['avg_pre_day'].':'.$the_day);
			$user_order_count[$row['uid']] = $one_user;
		}
		//获取平均排名
		//$avgSomeOne = \Yii::$app->redis->hget('CdiscountOms_TempData',"user_297_avg_pre_day");//获取其中一个user的平均
		$avgSomeOne = RedisHelper::RedisGet('CdiscountOms_TempData',"user_297_avg_pre_day" );
		if(!empty($avgSomeOne)){
			if(stripos($avgSomeOne, $the_day)==false){//当天还没有统计过
				self::generateUserPreDayAvg();
			}
		}else{//没有任何统计
			self::generateUserPreDayAvg();
		}
		$tops = [];
		$tops_datas_all = [];
		$sql = "SELECT distinct saas_cdiscount_user.`uid` ,user_base.`user_name`
					FROM  `saas_cdiscount_user`
					LEFT JOIN user_base
					ON ( saas_cdiscount_user.uid = user_base.uid ) ";
		$command = Yii::$app->db->createCommand($sql);
		$accounts = $command->queryAll();
		foreach ($accounts as $a){
			$tops[$a['uid']]=0;
			//$avg = \Yii::$app->redis->hget('CdiscountOms_TempData',"user_".$a['uid']."_avg_pre_day");
			$avg = RedisHelper::RedisGet('CdiscountOms_TempData',"user_".$a['uid']."_avg_pre_day");
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
			$all_accounts_query = SaasCdiscountUser::find()->select("uid, create_time")->distinct(true);
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
						//$day_count = \Yii::$app->redis->hget('CdiscountOms_TempData',"user_".$row['uid'].".".$targetDay);
						$day_count = RedisHelper::RedisGet('CdiscountOms_TempData',"user_".$row['uid'].".".$targetDay);
						if(!empty($day_count)){
							$last_30_days_total += (int)$day_count;
							$last_10_days_total += (int)$day_count;
						}
					}
				}
				$avg_pre_day = empty($bind_days)?0:round($last_30_days_total/$bind_days,2);
				//$set_avg = \Yii::$app->redis->hset('CdiscountOms_TempData',"user_".$row['uid'].'_avg_pre_day',$avg_pre_day.':'.$the_day);
				$set_avg = RedisHelper::RedisSet('CdiscountOms_TempData',"user_".$row['uid'].'_avg_pre_day',$avg_pre_day.':'.$the_day);
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
	public static function cronCdiscountOrderDailySummary($start_time=''){
		try{
			if(empty($start_time))
				$start_time = date('Y-m-d H:i:s');
			$summary_end_date = date('Y-m-d');
			$summary_start_date = date('Y-m-d',strtotime($start_time)-3600*24);
			$summary_start_dateTime = date('Y-m-d H:i:s',strtotime($start_time)-3600*24);
			$accounts = SaasCdiscountUser::find()->where(['is_active'=>1])->asArray()->all();
			foreach ($accounts as $account){
				$uid = $account['uid'];
				 
				echo "\n start to summary uid:$uid, seller_id`='".$account['username'];
				$orders_yestday = OdOrder::find()
									->where('paid_time>='.strtotime($summary_start_date).' and paid_time<'.strtotime($summary_end_date))
									->andWhere(['selleruserid'=>$account['username'],'order_source'=>'cdiscount'])
									->asArray()->all();
				$yestday_count = count($orders_yestday);
				echo "\n yestday_count:$yestday_count";
				$query = "SELECT * FROM `od_order_summary` WHERE `platform`='cdiscount' and `puid`=$uid and `seller_id`='".$account['username']."' and `thedate`='".$summary_start_date."'";
				$command = Yii::$app->db->createCommand($query);
				$record = $command->queryOne();
	
				if(!empty($record)){
					$old_count = $record['create_order_count'];
					$addi_info = $record['addi_info'].'update at '.TimeUtil::getNow().',count:'.$old_count.'->'.$yestday_count.';';
					echo "UPDATE : ".$addi_info;
					$sql = "UPDATE `od_order_summary` SET 
							`create_order_count`=$yestday_count,`addi_info`='$addi_info' 
							WHERE `platform`='cdiscount' and `puid`=$uid and `seller_id`='".$account['username']."' and `thedate`='".$summary_start_date."'";
				}else{
					echo "INSERT.";
					$sql = "INSERT INTO `od_order_summary`
							(`platform`, `puid`, `seller_id`, `thedate`, `create_order_count`, `addi_info`) VALUES 
							('cdiscount',$uid,'".$account['username']."','".$summary_start_date."',$yestday_count,'')";
				}
				$command = Yii::$app->db->createCommand($sql);
				$affect_record = $command->execute();
				//$del_redis_record = \Yii::$app->redis->hdel('CdiscountOms_TempData',"user_$uid".".".$account['username'].".".$summary_start_date);
				$del_redis_record = RedisHelper::RedisDel('CdiscountOms_TempData',"user_$uid".".".$account['username'].".".$summary_start_date );
			}
		}catch (\Exception $e){
			echo "\n".$e ;
		}
	}//end of cronCdiscountOrderDailySummary
	
	public static function getOneUserSellerIdNowDateOrderCount($uid,$sellerid,$platform='cdiscount'){
		$nowDay = date('Y-m-d');
		//$day_count = \Yii::$app->redis->hget('CdiscountOms_TempData',"user_$uid".".".$sellerid.".".$nowDay);
		$day_count = RedisHelper::RedisGet('CdiscountOms_TempData',"user_$uid".".".$sellerid.".".$nowDay);
		if(empty($day_count))
			return $day_count=0;
		else
			return $day_count;
	}//end of getOneUserSellerIdNowDateOrderCount
	
	/**
	 * 获取用户绑定的CD账号的最近X日订单获取数量chart data
	 * @param int $uid
	 * @param int $days 	获取天数
	 */
	public static function getChartDataByUid_Order($uid,$days=7){
		$today = date('Y-m-d');
		$daysAgo = date('Y-m-d', strtotime($today)-3600*24*$days);
		//用户近目标日期内所有数据
		$query = "SELECT * FROM `od_order_summary` WHERE `platform`='cdiscount' and `puid`=$uid and `thedate`>='".$daysAgo."' and `thedate`<'".$today."'";
		$command = Yii::$app->db->createCommand($query);
		$records = $command->queryAll();
		//用户CD账号数据
		$cdiscountAccounts = SaasCdiscountUser::find()->where(['uid'=>$uid,'is_active'=>1])->asArray()->all();
		//没有账号信息或者订单信息，直接return
		if(empty($records) && empty($cdiscountAccounts))
			return [];

		$chart['type'] = 'column';
		$chart['title'] = '近'.$days.'日Cdiscount订单数';
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
			
			foreach ($cdiscountAccounts as $account){
				//print_r($account);
				$name = $account['store_name']."(". $account['username'].")";
				//echo "<br>".$name;
				$create_order_count = 0;
				if($i==0){
					$dateStartStamp = strtotime($theday);
					$dateEndStamp = $dateStartStamp+3600*24;
					$query = "SELECT * FROM `od_order_v2` WHERE `order_source`='cdiscount' AND 
							`paid_time`>$dateStartStamp AND `paid_time`<$dateEndStamp AND 
							`selleruserid`=:seller";
					$command = Yii::$app->subdb->createCommand($query);
					$command->bindValue(":seller",$account['username'],\PDO::PARAM_STR);
					$records = $command->queryAll();
					$create_order_count = count($records);
					
					/*
					//$temp_count = \Yii::$app->redis->hget('CdiscountOms_TempData',"user_$uid".".".$account['username'].".".$today);
					if(empty($temp_count)){
						$create_order_count=0;
					}
					else 
						$create_order_count=(int)$temp_count;
					*/
					$total +=$create_order_count;
				}else{
					foreach ($records as $record){
						if($record['seller_id']==$account['username'] && $record['thedate']==$theday){
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
	 * 获取CD OMS dashboard广告
	 * @param unknown	$uid
	 * @param number 	$every_time_shows	每次展示广告数
	 */
	public static function getAdvertDataByUid($uid,$every_time_shows=2){
		$advertData = [];
		//$last_advert_id = \Yii::$app->redis->hget('CdiscountOms_DashBoard',"user_$uid".".last_advert");
		$last_advert_id = RedisHelper::RedisGet('CdiscountOms_DashBoard',"user_$uid".".last_advert");
		if(empty($last_advert_id))
			$last_advert_id=0;
		$new_last_advert_id = $last_advert_id;
		if(!empty($last_advert_id)){
			$query = "SELECT * FROM `od_dash_advert` WHERE (`app`='Cdiscount_Oms' or `app`='All_Oms') and `id`>".(int)$last_advert_id."  ORDER BY `id` ASC limit 0,$every_time_shows ";
			$command = Yii::$app->db->createCommand($query);
			$advert_records = $command->queryAll();
			foreach ($advert_records as $advert){
				$advertData[$advert['id']] = $advert;
				$new_last_advert_id = $advert['id'];
			}
			if(count($advertData)<$every_time_shows){
				$reLimit = $every_time_shows - count($advertData);
				$query_r = "SELECT * FROM `od_dash_advert` WHERE `app`='Cdiscount_Oms' or `app`='All_Oms' ORDER BY `id` ASC limit 0,$reLimit ";
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
			$query = "SELECT * FROM `od_dash_advert` WHERE `app`='Cdiscount_Oms' or `app`='All_Oms' ORDER BY `id` ASC limit 0,$every_time_shows ";
			$command = Yii::$app->db->createCommand($query);
			$advert_records = $command->queryAll();
			foreach ($advert_records as $advert){
				$advertData[$advert['id']] = $advert;
				$new_last_advert_id = $advert['id'];
			}
		}
		
		//$set_advert_redis = \Yii::$app->redis->hset("DASHBOARD_STS", $puid."_".$key,  $val);
		$set_advert_redis = RedisHelper::RedisSet("DASHBOARD_STS", $puid."_".$key,  $val);
		return $advertData;
	}
	
	/**
	 * 获取用户绑定的CD账号的异常情况，如token过期之类
	 * @param int $uid
	 */
	public static function getUserAccountProblems($uid){
		if(empty($uid))
			return [];
		
		$cdiscountAccounts = SaasCdiscountUser::find()->where(['uid'=>$uid])->asArray()->all();
		if(empty($cdiscountAccounts))
			return [];
		
		$accountUnActive = [];//未开启同步的账号
		$tokenExpired = [];//授权过期的账号
		$order_retrieve_errors = [];//获取订单失败
		$initial_order_failed = [];//首次绑定时，获取订单失败
		foreach ($cdiscountAccounts as $account){
			if(empty($account['is_active'])){
				$accountUnActive[] = $account;
				continue;
			}
			if( /*strtotime($account['token_expired_date'])< strtotime("-2 days") ||*/ $account['order_retrieve_message']=='token已过期，请检测绑定信息中的 账号，密码是否正确。'){
				$tokenExpired[] = $account;
				continue;
			}
			if(empty($account['initial_fetched_changed_order_since']) || $account['initial_fetched_changed_order_since']=='0000-00-00 00:00:00'){
				$initial_order_failed[] = $account;
				continue;
			}
			if($account['order_retrieve_message']!=='token已过期，请检测绑定信息中的 账号，密码是否正确。' && !empty($account['order_retrieve_message'])){
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
	
	/**
	 * 获取用户绑定的CD账号的最近X日订单利润(已计算的)情况
	 * @param int $uid
	 * @param int $days 	获取天数
	 */
	public static function getChartDataByUid_Profit($uid,$days=7){
		$today = date('Y-m-d');
		$daysAgo = date('Y-m-d', strtotime($today)-3600*24*$days);
		//用户近目标日期内所有数据
		$daysOrders = OdOrder::find()
			->where(" `paid_time`>=".strtotime($daysAgo)." and `paid_time`<=".time()." and `profit` IS NOT NULL ")
			->andWhere(['order_source'=>'cdiscount'])
			->asArray()->all();

		//用户CD账号数据
		$cdiscountAccounts = SaasCdiscountUser::find()->where(['uid'=>$uid,'is_active'=>1])->asArray()->all();
		//没有账号信息或者订单信息，直接return
		if(empty($daysOrders) && empty($cdiscountAccounts))
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
		$chart['title'] = '近'.$days.'日Cdiscount订单已统计的利润';
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
				
			foreach ($cdiscountAccounts as $account){
				//$name = $account['store_name']."(". $account['username'].")";
				$account_theday_profit = 0;
				$account_theday_order = 0;
				$thedayAccountOrderData = empty($orderDatas[$account['username']][$theday])?[]:$orderDatas[$account['username']][$theday];
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
	
	
	/*
	 * 对CD订单商品有图片或商品url缺失的数据补全信息到order_item_v2
	 */
	public static function prodInfoConversion(){
		try {
			$accounts = SaasCdiscountUser::find()->where(['is_active'=>1])->asArray()->all();
			foreach ($accounts as $account){
				$uid = $account['uid'];
				$seller = $account['username'];
				 
				echo "\n ---uid:$uid conversion start---";
				$query = "SELECT distinct `sku` FROM `od_order_item_v2` WHERE
						((`product_url` is null or `product_url`='')
						or (`photo_primary` is null or `photo_primary`=''))
						and `order_source_order_id` in ( select `order_source_order_id` from `od_order_v2` where `order_source`='cdiscount' and `selleruserid`='$seller')
						and `sku`<>'INTERETBCA' and `sku`<>'FRAISTRAITEMENT'";
				$command = Yii::$app->subdb->createCommand($query);
				$skuList = $command->queryAll();
				
				$needInsertOffer = [];
				foreach ($skuList as $row){
					$sku = trim($row['sku']);
					if(empty($sku))
					continue;
					$offer = CdiscountOfferList::find()->where(['seller_product_id'=>$sku])
						->andWhere('img is not null')
						->andWhere('product_url is not null')
						->one();
					$needCatch = false;
					if(empty($offer))
						$needCatch = true;
					if(!$needCatch){
						$imgs = json_decode($offer->img,true);
						if(empty($imgs) || empty($imgs[0]))
							$needCatch = true;
					}
					if($needCatch){
						$srcDetail = CdiscountOrderDetail::find()->where(['sellerproductid'=>$sku])->orderBy("id  DESC")->one();
						if(!empty($srcDetail)){
							$OfferInfo[] = [
							'productid'=>($srcDetail->sku==$srcDetail->productid)?$srcDetail->productid:$srcDetail->sku,
							//'product_id'=>$srcDetail->sku,
							'productean'=>'',
							//'product_ean'=>''
							'sellerproductid'=>$srcDetail->sellerproductid,
							//'seller_product_id'=>$sku,
							'sku'=>$srcDetail->sku,
							'seller_id'=>$seller,
							];
							$needInsertOffer[] = $OfferInfo;
						}
						continue;
					}
					$photo_primary = $imgs[0];
					$product_url = $offer->product_url;
					OdOrderItem::updateAll(
						['photo_primary'=>$photo_primary,'product_url'=>$product_url],
						"`sku`=:sku and `order_source_order_id` in ( select `order_source_order_id` from `od_order_v2` where `order_source`='cdiscount')",
						[':sku'=>$sku]
					);
				}
				if(!empty($needInsertOffer)){
					CdiscountOrderHelper::saveOrderDetailToOfferListIfIsNew($needInsertOffer,$account);
				}
			}
		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}
	
	
	/**
	 * CD订单消息状态设置
	 * @param string $order_id
	 * @param string $status		纠纷状态("NO_ISSUE"无纠纷；"IN_ISSUE"纠纷中；“END_ISSUE”纠纷结束)
	 * @author	lzhl	2016/5/9	初始化
	 */
	public static function setOrderIssueStatus($order_source_order_id,$status){
		if(!in_array(strtoupper($status),['NO_ISSUE','IN_ISSUE','END_ISSUE'])){
			return ['success'=>false,'message'=>'不支持的纠纷状态'];
		}
		$order = OdOrder::find()->where(['order_source_order_id'=>$order_source_order_id,'order_source'=>'cdiscount'])->one();
		if(!empty($order)){
			$order->issuestatus = strtoupper($status);
			if(!$order->save()){
				return ['success'=>false,'message'=>print_r($order->errors)];
			}
		}else{
			return ['success'=>false,'message'=>'订单不存在'];
		}
	}
	
	
	/*
	 * 查询CD账号的同步进行情况
	 * @param int 		$puid
	 * @param string 	$seller
	 * @return	array	[sync_status,sync_type,sync_info]
	 * @author	lzhl	2016/5/9	初始化
	 */
	public static function checkSyncJobState($puid,$seller=[]){
		if(empty($seller))
			$saasAccount = SaasCdiscountUser::find()->where(['uid'=>$puid,'is_active'=>1])->all();
		else 
			$saasAccount = SaasCdiscountUser::find()->where(['uid'=>$puid,'username'=>$seller,'is_active'=>1])->all();
		
		$sync_info = [];
		foreach ($saasAccount as $account){
			$tmp = [];
			$tmp['username'] = $account->username;
			if(empty($account)){		
				$tmp['sync_status'] = '';
				$tmp['sync_type'] = 'N';
				$tmp['sync_info'] = [];
			}else{
				$tmp['sync_status'] = $account->sync_status;
				$tmp['sync_type'] = $account->sync_type;
				$addi_info = json_decode($account->sync_info,true);
				$tmp['sync_info'] = empty($addi_info)?[]:$addi_info;
			}
			$sync_info[$account->site_id] = $tmp;
		}
		return $sync_info;
	}
	
	/*
	 * 将cd账号标记为正在同步
	 * @param	model		$account
	 * @param	string		$sync_type
	 * @param	datetime	$begincreationdate
	 * @param	datetime	$endcreationdate
	 * @return	array[success,message]
	 * @author	lzhl	2016/8/18	初始化
	 */
	public static function markSaasAccountOrderSynching($account,$sync_type,$begincreationdate='',$endcreationdate=''){
		$addi_info = json_decode($account->sync_info,true);
		if(empty($addi_info)) $addi_info = [];
		$account->sync_type = $sync_type;
		$account->sync_status = 'R';
		if(!empty($begincreationdate))
			$addi_info['begincreationdate'] = $begincreationdate;
		if(!empty($endcreationdate))
			$addi_info['endcreationdate'] = $endcreationdate;
		$addi_info['start_time'] = TimeUtil::getNow();
		$addi_info['end_time'] = '';
		$account->sync_info = json_encode($addi_info);
		
		if(!$account->save()){
			return ['success'=>false,'message'=>print_r($account->errors)];
		}else{
			return ['success'=>true,'message'=>''];
		}
	}
	
	/*
	 * 同步完成后更改同步状态信息
	 * @param	model		$account
	 * @param	string		$status		'C' or 'F'
	 * @param	int			$sync_order_count
	 * @param	string		$error_log
	 * @return	array[success,message]
	 * @author	lzhl	2016/8/18	初始化
	 */
	public static function markSaasAccountOrderSyncFinished($account,$status,$sync_order_count,$error_log=''){
		$addi_info = json_decode($account->sync_info,true);
		if(empty($addi_info)) $addi_info = [];
		
		$account->sync_status = $status;
		$addi_info['end_time'] = TimeUtil::getNow();
		$addi_info['order_count'] = $sync_order_count;
		$addi_info['error_log'] = $error_log;
		$account->sync_info = json_encode($addi_info);
		
		if(!$account->save()){
			return ['success'=>false,'message'=>print_r($account->errors)];
		}else{
			return ['success'=>true,'message'=>''];
		}
	}
	
	/**
	 *标记发货前，提前检查一下提交的信息是否符合CD要求
	 *@param	$tracknum
	 *@param	$order_source_shipping_method
	 *@param	$shipping_method_code
	 *@param	$shipping_method_name
	 *@param	$tracking_link
	 *@return	array=>('success'=>boolean,'message'=>string)
	 *@author	lzhl	2017/1/10	初始化
	 */
	public static function preCheckSignShippedInfo($tracknum,$order_source_shipping_method,$shipping_method_code,$shipping_method_name='',$tracking_link=''){
		$success=true;
		$message='';
		//平邮不做限制
		if($order_source_shipping_method=='STD')
			return ['success'=>$success,'message'=>$message];
		
		if(empty($tracknum)){
			$success=false;
			$message.= '物流号不能为空;';
		}
		if(empty($shipping_method_code)){
			$success=false;
			$message.= '运输服务不能为空;';
		}
		if(strtolower($shipping_method_code)=='other' && empty($shipping_method_name)){
			$success=false;
			$message.= '运输服务为Other时，物流名称不能为空;';
		}
		if(strtolower($shipping_method_code)=='other' && empty($tracking_link)){
			$success=false;
			$message.= '运输服务为Other时，物流查询网址不能为空;';
		}
		if(strtolower($shipping_method_code)=='other' && !empty($tracking_link)){
			if(!preg_match('/^(http|https):\/\//i', $tracking_link)){
				$success=false;
				$message.= '物流查询网址必须以http://或https://开头;';
			}
			if(!strpos($tracking_link,'?')===false){
				$success=false;
				$message.= '物流查询网址不能含有 ? 号;';
			}
		}
		return ['success'=>$success,'message'=>$message];
	}
	
	/**
	 * 根据CD订单item的model，获取该item的图片url，仅用于前端显示（获取的url尽量优先七牛缓存过的）
	 * @author	lzhl	2017/2/17	初始化
	 */
	public static function getCdiscountOrderItemPhotoForBrowseShow($itemModel,$puid=0){
		global $CACHE; //this is to cache the result for this session,yzq 20170222
		if (isset($CACHE['getCdiscountOrderItemPhotoForBrowseShow'][$itemModel->sku]))
			return $CACHE['getCdiscountOrderItemPhotoForBrowseShow'][$itemModel->sku];
		
		$photo_primary = $itemModel->photo_primary;
		if(empty($photo_primary)){
		    // dzt20190711 几个上百万offer数据的客户 用条件"`img`<>'' and `img` is not null" 会扫描全表非常慢，而且seller_product_id 也没有index
			// $cdOfferInfo = CdiscountOfferList::find()->where(['seller_product_id'=>$itemModel->sku])->andWhere("`img`<>'' and `img` is not null")->one();
		    $cdOfferInfos = CdiscountOfferList::find()->where(['product_id'=>$itemModel->source_item_id])->all();
		    $cdOfferInfo = null;
		    foreach ($cdOfferInfos as $tmpOffer){
		        if(!empty($tmpOffer->img)){
		            $cdOfferInfo = $tmpOffer;
		            break;
		        }
		    }
		    
			if(!empty($cdOfferInfo) && !empty($cdOfferInfo->img)){
				$photos = json_decode($cdOfferInfo->img,true);
				$photo_primary = empty($photos[0])?'':$photos[0];
				if(!empty($photo_primary)){
					$thisItem = OdOrderItem::findOne($itemModel->order_item_id);
					if(!empty($thisItem)){
						$thisItem->photo_primary = $photo_primary;
						if(empty($thisItem->product_url) && !empty($cdOfferInfo->product_url))
							$thisItem->product_url =$cdOfferInfo->product_url;
						$thisItem->save(false);
					}
				}
			}
		}
		if(empty($puid))
			$puid = \Yii::$app->subdb->getCurrentPuid();
		$photo_primary = ImageCacherHelper::getImageCacheUrl($photo_primary,$puid,1);
		$CACHE['getCdiscountOrderItemPhotoForBrowseShow'][$itemModel->sku] =$photo_primary; 
		return $photo_primary;
	}
}
?>