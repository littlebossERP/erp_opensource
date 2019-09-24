<?php
namespace eagle\modules\order\helpers;
use yii;
use yii\data\Pagination;

use eagle\models\SaasWishUser;
use eagle\modules\listing\models\WishApiQueue;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\order\models\WishOrder;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\WishOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\models\SysCountry;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\platform\apihelpers\WishAccountsApiHelper;
use eagle\modules\util\helpers\RedisHelper;

/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lkh
+----------------------------------------------------------------------
| Create Date: 2014-12-08
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * wish订单模板业务
 +------------------------------------------------------------------------------
 * @category	order
 * @package		Helper/order
 * @subpackage  Exception
 * @author		lkh
 +------------------------------------------------------------------------------
 */
class WishOrderHelper {
	private static $syncOrderTotalJob = 5;
	
	public static $CAN_SHIP_ORDERITEM_STATUS = array("APPROVED","SHIPPED");
	
	public static $CANNOT_SHIP_ORDERITEM_STATUS = array("REFUNDED","REQUIRE_REVIEW");
	/**
	 +---------------------------------------------------------------------------------------------
	 * 发送对wish订单（发货， 退货/取消 ， 修改发货信息三类）操作的请求
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $order_id		需要调用wish api 的订单号（eagle订单号） 
	 * @param $action_type		wish api 的类型 ：order_ship ， order_cancel ， order_modify 暂时三种
	 * @param $site_id		wish 账号 id
	 * @param $params		对应api需要的参数
	 +---------------------------------------------------------------------------------------------
	 * @description			$params 格式说明
	 * 						order_ship : array('order_id'=>平台订单号 (require)， 
	 * 											'tracking_provider'=>发货方式 (require)
	 * 											'tracking_number'=>快递号码(optional)
	 * 											'ship_note'=>备注(optional))
	 * 
	 * 						order_cancel: array('order_id'=>平台订单号 (require)， 
	 * 											'reason_code'=>退货代码  ， 
	 * 											'reason_note'=>(optional) 当reason_code = -1  为require)
	 * 
	 * 						order_modify ： array('order_id'=>平台订单号 (require)， 
	 * 											'tracking_provider'=>发货方式 (require)
	 * 											'tracking_number'=>快递号码(optional)
	 * 											'ship_note'=>备注(optional))
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
	 * @author		lkh		2014/12/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function appendAddOrderOpToQueue($order_id , $action_type , $site_id ,$params){
		$rtn['message']="";
		$rtn['success'] = true;
		$now_str = GetControlData::getNowDateTime_str();
		
		$user = \Yii::$app->user->identity->getFullName();
		$uid = \Yii::$app->user->id;
		//If failed to get valid uid, prompt error
		if ($uid=='0' or $uid =='' ){
			$rtn['success']=false;
			$rtn['message'] .= "EWOD.000 UID got invalid value:".$uid;
			return $rtn;
		}
		
		//check wew site order id existing 
		if(empty($params['order_id'])){
			$rtn['success']=false;
			$rtn['message'] .= "EWOD.001 eagle order id : $order_id  and site order id is empty:".$params['order_id'];
			return $rtn;
		} 
		
		//check the require params whether existing 
		if ($action_type == 'order_ship') {
			//tracking_provider are required
			if(empty($params['tracking_provider'])){
				$rtn['success']=false;
				$rtn['message'] .= "EWOD.002 eagle order id : $order_id  and tracking_provider is empty:".$params['tracking_provider'];
				return $rtn;
			} 
			
		}else if ($action_type == 'order_cancel') {
			//reason_code is required , if  reason_code==-1 then reason_note also required
			if(! isset($params['reason_code'])){
				$rtn['success']=false;
				$rtn['message'] .= "EWOD.003 eagle order id : $order_id  and reason_code is empty:".$params['reason_code'];
				return $rtn;
			}else{
				if ($params['reason_code']==-1){
					if(empty($params['reason_note'])){
						$rtn['success']=false;
						$rtn['message'] .= "EWOD.003.1 eagle order id : $order_id  and reason_note is empty:".$params['reason_note'];
						return $rtn;
					}
				}
			} 
		}else if ($action_type == 'order_modify') {
			//tracking_provider are required
			if(empty($params['tracking_provider'])){
				$rtn['success']=false;
				$rtn['message'] .= "EWOD.004 eagle order id : $order_id  and tracking_provider is empty:".$params['tracking_provider'];
				return $rtn;
			} 
		}
		
		//set up $action_type_filter
		$action_type_filter = array();
		if ($action_type == 'order_ship') {
			//if existing order ship , then cancel it 
			$action_type_filter = array('order_ship'); 
		}else if ($action_type == 'order_cancel') {
			//if existing order cancel  , then cancel all in queue 
			$action_type_filter =array('order_ship' , 'order_cancel' , 'order_modify');
		}else if ($action_type == 'order_modify') {
			//if existing order modify  , then cancel them, use new one
			$action_type_filter =array('order_modify');
		}
		
		$inQueueItemModels = WishApiQueue::find()->andWhere(['uid'=>$uid,
													'action_type'=>$action_type_filter,
													'status'=>'pending',
													"fanben_order_id"=>$order_id ])
												->all();
		//if existing, cancel them, use new one
		if ($inQueueItemModels <> null){
			$message_mapping = array(
				'order_ship'=>'因为订单发货再次触发，之前的发货作废',
				'order_cancel'=>'因为触发退货或者取消订单 ， 之前的订单所有操作都作废',
				'order_modify'=>'因为订单被修改，以最后的修改为准，之前的修改作废',
			);
			foreach ($inQueueItemModels as $anQueueItem ){
				$anQueueItem->status='canceled';
				$anQueueItem->message=$message_mapping[$action_type];
				$anQueueItem->save();
			}
		}
			
		//create one new				
		$inQueueItem = new WishApiQueue();

		//even use the previous pending one, modify the create time to be now
		$inQueueItem->create_time = $now_str;
		$inQueueItem->update_time = $now_str;
		$inQueueItem->fanben_order_id = $order_id;
		$inQueueItem->uid = $uid;
		
		//put more details to $inQueueItem
		$inQueueItem->site_id = $site_id;
		$inQueueItem->action_type = $action_type;
		
		//put the Fan Ben snapshot to posting data as a copy.
		 
		$inQueueItem->params = json_encode($params);
		
		if ( $inQueueItem->save() ){
			//save successfull		
			$rtn['message'] = "提交成功， 请等待队列执行！";
			$opetaionLeablMapping = array(
				'order_ship'=>'发货',
				'order_cancel'=>'取消',
				'order_modify'=>'修改',
			);
			OperationLogHelper::saveOperationLog('order',$order_id,"Wish  ".$params['order_id']." 已经".$opetaionLeablMapping[$action_type]);
			
		}else{
			$rtn['success']=false;
			$rtn['message'] = "提交失败！：".print_r($inQueueItem->getErrors(),true);
		}//end of save failed
		
		return $rtn;
	}//end of appendAddOrderOpToQueue
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 取消某订单在后台的某类型的排队请求
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $order_id		需要调用wish api 的订单号（eagle订单号） 
	 * @param $action_type		wish api 的类型 ：order_ship ， order_cancel ， order_modify 暂时三种
	 +--------------------------------------------------------------------------------------------- 
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function cancelAddOrderOpToQueue($order_id , $action_type){
		$rtn['message']="";
		$rtn['success']=true;
		$user = \Yii::$app->user->identity->getFullName();
		$uid = \Yii::$app->user->id;
		
		//set up action type
		if ($action_type == 'order_ship') {
			//if existing order ship , then cancel it 
			$action_type_filter = array('order_ship'); 
		}else if ($action_type == 'order_cancel') {
			//if existing order cancel  , then cancel all in queue 
			$action_type_filter =array('order_ship' , 'order_cancel' , 'order_modify');
		}else if ($action_type == 'order_modify') {
			//if existing order modify  , then cancel them, use new one
			$action_type_filter =array('order_modify');
		}

		$inQueueItems = WishApiQueue::find()->andWhere(['uid'=>$uid,'status'=>'pending','action_type'=>$action_type_filter,'fanben_order_id' =>$order_id])
											->all();
		//if  existing, update it to canceled		
		if ( !empty($inQueueItems) and count($inQueueItems) > 0){
			foreach ($inQueueItems as $inQueueItem){						 
				$inQueueItem->update_time = GetControlData::getNowDateTime_str();
				$inQueueItem->status = 'canceled';
				if ( $inQueueItem->save() ){//save successfull
					$rtn['success']=true;					
					$opetaionLeablMapping = array(
						'order_ship'=>'发货',
						'order_cancel'=>'取消',
						'order_modify'=>'修改',
					);
					OperationLogHelper::saveOperationLog('order',$order_id,"eagle 订单号  ".$order_id." 已经".$opetaionLeablMapping[$action_type]);
				}else{
					//save queue item failed
					$rtn['success']=false;
					foreach ($inQueueItem->errors as $k => $anError){
						$rtn['message'] .= "EWOD.103 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
					}
				}				
			}//end of each in Queue Item
		}//end of found pending in queue item request
		return $rtn;
	}//end of cancelAddOrderOpToQueue
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台执行wish 订单操作类型
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * @return				array('message'=>"",'success'=true)
	 * 						当return 的message =n/a 的时候，外层的job 会理解为没有request
	 *                      排队，然后job 会sleep 10 秒钟，然后继续看有没有req 排队  
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronQueueHandlerExecuteWishOrderOp(){
		$rtn['message']="";
		$rtn['success'] = true;
		$action_type_filter =array('order_ship' , 'order_cancel' , 'order_modify');	

		$SAAS_api_requests = WishApiQueue::find()->andWhere([ 'status'=>'pending','action_type'=>$action_type_filter ])
												->limit(30)->orderBy('timerid  asc')->all();
		
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"Step 0 starting Wish ,Queue Depth:".count($SAAS_api_requests)],"edb\global");
				
		if(!empty($SAAS_api_requests) and count($SAAS_api_requests)){
			foreach($SAAS_api_requests as $SAAS_api_request){
				//purge the error msg written before
				$SAAS_api_request->message = '';
				
				$uid = $SAAS_api_request->uid;
				$fanben_order_id = $SAAS_api_request->fanben_order_id;
				$SAAS_api_request->update_time = GetControlData::getNowDateTime_str();
				$request_action = $SAAS_api_request->action_type;
				//step 1: Load the fanben detail by the snapshot
				$params = json_encode($SAAS_api_request->params, true); 
			 
				//step 2: Load the Wish access info, token, etc
				$WishShopModel = SaasWishUser::findOne($SAAS_api_request->site_id);
				if ($WishShopModel == null){					
					\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"Step 1.5","Failed to Load wish token for ".$SAAS_api_request->site_id],"edb\global");
					//异常情况
					$SAAS_api_request->status= "failed";
					$SAAS_api_request->message="Failed to Load wish token for ".$SAAS_api_request->site_id;
					$SAAS_api_request->save();
					continue;
				}
				 

				//step 3: Load the FanBen in User X db
 
		
				//Step 4: call Proxy to do the request
				$rtn ['token'] = $WishShopModel->token;
				$rtn ['action_type'] = $SAAS_api_request->action_type;
				$rtn ['params'] = $params;
				//return $rtn;
				$rtn =self::_CallWishApiByActionType($WishShopModel->token,$SAAS_api_request->action_type ,$params);
				//echo print_r($rtn,true);
				
				//1) If proxy do not return any success flag
				//2) If proxy returns success = false
				//3) if no having success flag from proxy
				//4) if got success = false from proxy
				if (!isset($rtn['success']) or 
					$rtn['success'] === false or 
					!isset($rtn['proxyResponse']['success']) or 
					( isset($rtn['proxyResponse']['success']) and $rtn['proxyResponse']['success'] === false) ){
					
					$SAAS_api_request->status= "failed";

					if (isset($rtn['proxyResponse']['success']) and $rtn['proxyResponse']['success'] === false )
						$SAAS_api_request->message = $rtn['proxyResponse']['message'];
					else 
						$SAAS_api_request->message = "Failed to call Proxy to crt/upd product:".$rtn['message'];
					
					$SAAS_api_request->save();
					
					continue;
				}
				
				 
				$SAAS_api_request->status= 'complete';
				$SAAS_api_request->update_time = GetControlData::getNowDateTime_str();;
				$SAAS_api_request->save();
			}//end of each SAA api request
			
		}else{//if nothing in the queue, idle
			$rtn['message']="n/a";
		}
		
		return $rtn;
	}// end of cronAutoExecuteWishOrderOp
	
	 
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取新绑定帐号一个月内的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			获取一个月内的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015-7-28			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronAutoFetchNewAccountOrderList(){
		GLOBAL $HANDLED_WISH_ORDER;
		try {
			
			$SAASWISHUSERLIST = SaasWishUser::find()->where("is_active='1' and initial_fetched_changed_order_since is null 
						or initial_fetched_changed_order_since='0000-00-00 00:00:00'")->all();
				
			//retrieve orders  by  each wish account
			foreach($SAASWISHUSERLIST as $wishAccount ){
				$uid = $wishAccount['uid'];
				//flush this remember tool first
				$HANDLED_WISH_ORDER = array();
		
				echo " YS1 start to fetch for unfuilled uid=$uid ... \n";
				if (empty($uid)){
				//异常情况
					$message = "site id :".$wishAccount['site_id']." uid:0";
					echo $message;
							\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
					return false;
				}
		
 
		
					$updateTime =  TimeUtil::getNow();
					//update this wish account as last order retrieve time
					$wishAccount->last_order_retrieve_time = $updateTime;

					//************************************************************
				//初始化该用户30天内所有unchanged order 拿下来
				//1) 如果现在是 00:00 - 7:59 ，获取30天内的changed order，而且循环获取，每次7天
				//************************************************************
				// get wish order which state has changed
				$nowHour = substr(TimeUtil::getNow(),11,2); //2014-01-20 20:10:20
				$wishAccount1 = $wishAccount;
				$wishAccount1['initial_fetched_changed_order_since'] = date("Y-m-d\TH:i:s" ,strtotime(TimeUtil::getNow())-3600*8);
						//$sinceTimeUTC = date("Y-m-d\TH:i:s" ,strtotime(TimeUtil::getNow())-3600*8);//UTC time is -8 hours
							
						$sinceTimeUTC = date("Y-m-d\TH:i:s" ,strtotime(TimeUtil::getNow())-3600*24 * 30);//UTC time is -8 hours
						$sinceTimeUTC = substr($sinceTimeUTC,0,10);
							
						/*对于获取近期改动过的订单，saas user 表需要记录以下字段
						* 1. initial_fetched_changed_order_since: 2015-7-24
						* 表示从 1 到 2 这些时间 （整个date）的order changed order 都已经拿回来并且inserted OMS
						* 2. routine_fetched_changed_order_from:2015-7-14T14:25:25
						* 表示上次日常是从这个时间拿到现在的，changed order
						*/
						//
						//因为wish sdk 做了分页获取然后所有order 一起发下来，所以我们不需要做分页了
						//如果下载要继续获取的since time 是 该注册帐号的 注册日期 30天内，就获取，否则不需要了
						//if ($sinceTimeUTC >=  substr(date('Y-m-d H:i:s',strtotime($wishAccount['create_time'] )-3600*24*30 ),0,10 ) ){
						echo TimeUtil::getNow()." start to initial $uid changed order for ".$wishAccount['store_name']." since $sinceTimeUTC \n"; //ystest
						$getOrderCount = 0;
						$starttime = TimeUtil::getNow();
						$starti = 0; $limit = 200;
						$next = '';
						//因为wish sdk 做了分页获取然后所有order 一起发下来，所以我们不需要做分页了
						do {//this api get proxy, using pagination
						$insertWishReturn['updated_count'] = 0;
						$insertWishReturn['inserted_count'] = 0;
							if (!empty($orders['proxyResponse']['wishReturn']['paging']['next']))
								$next = $orders['proxyResponse']['wishReturn']['paging']['next'];
									
								$orders = self::_getAllChangeOrdersSince($wishAccount['token'] , $sinceTimeUTC ,$starti,$limit,$next);//UTC time is -8 hours
									
							//fail to connect proxy
							if (empty($orders['success']) or !$orders['success']){
									\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"Error 1.01 fail to connect proxy  :".$orders['message']],"edb\global");
									$wishAccount->save();
									continue;
							}
							echo TimeUtil::getNow()." got results, start to insert oms \n"; //ystest
							//accroding to api respone  , update the last retrieve order time and last retrieve order success time
							if (!empty ($orders['proxyResponse']['success'])){
							//sync wish info to wish order table
							if(!empty($orders['proxyResponse']['wishReturn'])){
								//insert wish order
								$insertWishReturn = self::_InsertWishOrder($orders , $wishAccount);
								}//end of wishReturn empty or not
								$wishAccount->last_order_success_retrieve_time = $updateTime;
							}else{
							if (!empty ($orders['proxyResponse']['message'])){
							\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"Proxy Error 1.0 proxy error  :".$orders['proxyResponse']['message']],"edb\global");
							}else{
							\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"Proxy Error 1.0 proxy error  : not any respone message"],"edb\global");
							}
							}

							if (!empty($orders['proxyResponse']['wishReturn']['paging']['next']))
								echo "ready to get next page ". print_r($orders['proxyResponse']['wishReturn']['paging']['next'],true) ." \n";
							else
								echo "No next page \n";

							if ($insertWishReturn['updated_count'] ==0 and $insertWishReturn['inserted_count'] ==0)
									echo "updated count and inserted cout is ZERO, so not to have next page \n";
							else
								echo "updated count = ".$insertWishReturn['updated_count']." and inserted cout is ".
							$insertWishReturn['inserted_count'].", so will have next page \n";

								//如果没有返回next ， 或者 inserted 和 updated order 都为0，就不要继续做下去了，因为已经到了 上次获取的边界值，继续获取下去就重复了
				}while( !empty($orders['proxyResponse']['wishReturn']['paging']['next']) and isset($insertWishReturn['success']) and $insertWishReturn['success']==true);
					//do for all orders returns, use cache to see whether has been integrated.
					//and ($insertWishReturn['updated_count'] + $insertWishReturn['inserted_count']) > 0
					$wishAccount->initial_fetched_changed_order_since = $sinceTimeUTC;

					if (empty($wishAccount->routine_fetched_changed_order_from) or $wishAccount->routine_fetched_changed_order_from == '0000-00-00 00:00:00')
						$wishAccount->routine_fetched_changed_order_from = $sinceTimeUTC;

					if (!empty ($orders['proxyResponse']['message']) and 'Done With http Code 200' <>$orders['proxyResponse']['message']){
						$wishAccount->order_retrieve_message = $orders['proxyResponse']['message'];
					}else{
						$wishAccount->order_retrieve_message = '';//to clear the error msg if last attemption got issue
							
						if (!empty($insertWishReturn['message']))
							$wishAccount->order_retrieve_message = $insertWishReturn['message'];
						else{
							$wishAccount->last_order_success_retrieve_time = $sinceTimeUTC; //NOT　UTC time
							$wishAccount->last_order_retrieve_time = $sinceTimeUTC; //NOT　UTC time
						}
					}
					if (!$wishAccount->save()){
						\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"failure to save wish operation info ,uid:".$wishAccount['uid']."error:". print_r($wishAccount->getErrors(),true)],"edb\global");
					}
					self::_summaryWishOrderByNation();
				}//end of each wish user account
			} catch (\Exception $e) {
				echo "Failed to retrieve order :".$e->getMessage();
				\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
			}
		
	}//end of 新帐号绑定 初始化
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 手动同步订单(wish订单抓取的插队队列)
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			手动同步订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015-11-02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function cronManualRetrieveWishOrder(){
		try {
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_wish_user set last_order_manual_retrieve_time='0000-00-00 00:00:00'
										where last_order_manual_retrieve_time is null is null"  );
			$affectRows = $command->execute();
			
			//1.账号启用;2.手动同步标示开启;3.没有上锁
			$SAASWISHUSERLIST = SaasWishUser::find()->where("is_active='1' and order_manual_retrieve='Y'   and
				 ifnull(oq_status,'') <>'S' ")->orderBy("last_order_success_retrieve_time asc")->all();

			//retrieve orders  by  each wish account
			self::_retrieveOrderInfoByAccount($SAASWISHUSERLIST,'MJQ');
		} catch (\Exception $e) {
			echo "Failed to  retrieve order :".$e->getMessage();
			\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
	}//end of cronManualRetrieveWishOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取未发货的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $dateStart		获取订单状态变化的起始时间 
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			获取某个时间之后状态变化的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronAutoFetchRecentChangedOrder($thisJobId=0){
		GLOBAL $HANDLED_WISH_ORDER;
		try {
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
			$oneHoursAgo = date('Y-m-d H:i:s',strtotime('-1 hour'));
			
			//这些data conversion 只需要一个job行就可以 了
			if ($thisJobId ==0){
				//update the new accounts first
				$command = Yii::$app->db->createCommand("update saas_wish_user set last_order_success_retrieve_time='0000-00-00 00:00:00',last_order_retrieve_time='0000-00-00 00:00:00'
										where last_order_success_retrieve_time is null or last_order_retrieve_time is null"  );
				$affectRows = $command->execute();
					
				// 如果同步 订单的上锁状态 超过1小时 视为失败， 将改为状态P
				$sql = "update saas_wish_user set oq_status = 'P'  where last_order_success_retrieve_time <'$oneHoursAgo' and  oq_status='S' and is_active = 1";
					
				$command = Yii::$app->db->createCommand($sql);
				$affectRows = $command->execute();
			}//end of if $thisJobId
			
			
			$coreCriteria = '';
			
			if (self::$syncOrderTotalJob > 0){
				$coreCriteria .= " and uid % ".self::$syncOrderTotalJob." = $thisJobId ";
			}
			$coreCriteria = '';// TODO 需要开多进程拉取再把这个去掉
			/*
			echo SaasWishUser::find()->where("is_active='1' and initial_fetched_changed_order_since is not null and
					last_order_success_retrieve_time<'$half_hours_ago'".$coreCriteria)->orderBy("last_order_success_retrieve_time asc")->createCommand()->getRawSql();
			exit();
			*/
			$SAASWISHUSERLIST = SaasWishUser::find()->where("is_active='1' and initial_fetched_changed_order_since is not null and
					last_order_success_retrieve_time<'$half_hours_ago'".$coreCriteria)->orderBy("last_order_success_retrieve_time asc")->all();
			
			self::_retrieveOrderInfoByAccount($SAASWISHUSERLIST,'OQ');
		} catch (\Exception $e) {
			echo "Failed to  retrieve order :".$e->getMessage()." line no ".$e->getLine();
			\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
			
	} // end of cronAutoFetchUnFulfilledOrderList
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 将获取订单的逻辑封装， 方便自动同步队列与手动同步队列
	 * +---------------------------------------------------------------------------------------------
	 * ription			wish order 调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 * +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * 
	 * @access static
	 *+---------------------------------------------------------------------------------------------
	 * @param $QueueName wish
	 * 抓取队列名称 （OQ为正常订单队列，MJQ为手动同步队列）
	 * @param $SAASWISHUSERLIST wish
	 *        	model
	 *        	+---------------------------------------------------------------------------------------------
	 * @return array
	 * @author lkh		2015/11/04				初始化
	 * +---------------------------------------------------------------------------------------------
	 *        
	 */
	private static function _retrieveOrderInfoByAccount(&$SAASWISHUSERLIST, $QueueName = 'OQ') {
		foreach ( $SAASWISHUSERLIST as $wishAccount ) {
			// ************************************************************
			// ********* start to check account locked or not ********
			// ************************************************************
			// 检查wish 该账号是否上锁了
			$affectRows = 0; // 重置影响数量
			                 // OQ Order Queue
			$command = Yii::$app->db->createCommand ( "update saas_wish_user set oq_status='S',oq_lockedby='$QueueName'
					where site_id = '" . $wishAccount ['site_id'] . "' and ifnull(oq_status,'') <>'S' " );
			$affectRows = $command->execute ();
			echo '\n ' . $wishAccount ['site_id'] . "=" . $affectRows; // testkh
			if (empty ( $affectRows )) {
				continue; // 没有成功修改的则修改下一条
			}
			
			// ************************************************************
			// ********* end of check account locked or not **********
			// ************************************************************
			$uid = $wishAccount ['uid'];
			// flush this remember tool first
			$HANDLED_WISH_ORDER = array ();
			
			echo " YS1 start to fetch for unfuilled uid=$uid ... \n";
			if (empty ( $uid )) {
				// 异常情况
				$message = "site id :" . $wishAccount ['site_id'] . " uid:0";
				echo $message;
				\Yii::error ( [ 
						'wish',
						__CLASS__,
						__FUNCTION__,
						'Background',
						$message 
				], "edb\global" );
				// 解锁账号
				self::unlockWishOrderQueue ( $wishAccount ['site_id'], $message ,'F');
				return false;
			}
			
			 
			// ************************************************************
			// ********* start to check token expiry or not **********
			// ************************************************************
			try {
				$ExpiryOrNot = WishProxyConnectHelper::checkWishTokenExpiryOrNot ( $wishAccount ['site_id'] );
			} catch (\Exception $e) {
				echo "Failed to  check wish token:".$e->getMessage();
				\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"uid check wish token  :".$e->getMessage()],"edb\global");
				// 解锁账号
				self::unlockWishOrderQueue ( $wishAccount ['site_id'] , 'token Expiry Exception' ,'F');
			}
			
			if ($ExpiryOrNot ['success'] == false) {
				// manual input token
				if (!empty($ExpiryOrNot['message']))
					$wishAccount->order_retrieve_message = $ExpiryOrNot['message'];
				else
					$wishAccount->order_retrieve_message = '授权失败';
				
				$wishAccount->save();
				// 解锁账号
				self::unlockWishOrderQueue ( $wishAccount ['site_id'], 'token Expiry' ,'F');
				continue;
			} else {
				// check access token
				if (! empty ( $ExpiryOrNot ['access_token'] )) {
					// refresh access token
					$wishAccount ['token'] = $ExpiryOrNot ['access_token'];
				}
			}
			// ************************************************************
			// ********* end of check token expiry or not ***********
			// ************************************************************
			
			$updateTime = TimeUtil::getNow ();
			
			if ($QueueName == "OQ") {
				$wishAccount->last_order_retrieve_time = $updateTime;
				$wishAccount->order_retrieve_message = ''; // to clear the error msg if last attemption got issue
			} elseif ($QueueName == "MJQ") {
				$wishAccount->last_order_manual_retrieve_time = $updateTime;
				$wishAccount->order_manual_retrieve_message = ''; // to clear the error msg if last attemption got issue
			}
			
			$getOrderCount = 0;
			
			$starti = 0;
			$limit = 200; // 因为wish sdk 做了
			              
			// get wish order which state has changed
			if (empty ( $wishAccount ['last_order_success_retrieve_time'] ) or $wishAccount ['last_order_success_retrieve_time'] == '0000-00-00 00:00:00') {
				// 如果还没有初始化完毕，就什么都不do
				// 解锁账号
				self::unlockWishOrderQueue ( $wishAccount ['site_id'], 'Waiting for init' ,'F');
			} else {
				$nextTimeDateSince = date ( "Y-m-d\TH:i:s", strtotime ( $updateTime ) - 3600 * 8 ); // now, UTC time, is next fetch from
				
				$sinceTimeUTC = date ( "Y-m-d\TH:i:s", strtotime ( $wishAccount ['last_order_success_retrieve_time'] ) - 3600 * 8 ); // convert to UTC time
				$sinceTimeUTC30DaysAgo = date ( "Y-m-d\TH:i:s", strtotime ( $wishAccount ['last_order_success_retrieve_time'] ) - 3600 * 24 * 30 ); // convert to UTC time
				                                                                                                                                    
				// ************************************************************
				                                                                                                                                    // ************************************************************
				echo TimeUtil::getNow () . " start to get $uid all unfuilled order for " . $wishAccount ['store_name'] . " since $sinceTimeUTC30DaysAgo \n"; // ystest
				
				try {
					$orders = self::_getAllUnfulfilledOrdersSince ( $wishAccount ['token'], $sinceTimeUTC30DaysAgo, 0, - 1 ); // UTC time is -8 hours
				} catch (\Exception $e) {
					echo "Failed to  get $uid all unfuilled order :".$e->getMessage();
					\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"uid $uid all unfuilled order  :".$e->getMessage()],"edb\global");
					// 解锁账号
					self::unlockWishOrderQueue ( $wishAccount ['site_id'] , 'get unfuilled order Exception' ,'F');
				}
				
				if (! empty ( $orders ['proxyResponse'] ['success'] )) {
					// sync wish info to wish order table
					if (! empty ( $orders ['proxyResponse'] ['wishReturn'] )) {
						
						if (isset($orders['proxyResponse']['wishReturn']['data'])){
							echo "\n uid=$uid site id=". $wishAccount ['site_id']." has unfulfill index data is ok !";
						}else{
							echo "\n uid=$uid site id=". $wishAccount ['site_id']." not unfulfill index data is ok !";
						}
						// insert wish order
						$insertWishReturn = self::_InsertWishOrder ( $orders, $wishAccount );
						echo "\n uid=$uid site id=". $wishAccount ['site_id']." insert unfulfill order is ok!";
					} // end of wishReturn empty or not
					if (! empty ( $insertWishReturn ['message'] )){
						$wishAccount->order_retrieve_message = $insertWishReturn ['message'];
						echo TimeUtil::getNow () . "  get $uid all unfuilled message : " . $wishAccount->order_retrieve_message. "  \n"; //  testkh
					}
						
				}
				
				// ************************************************************
				// start to get changed order, 日常周期调用的逻辑
				// ************************************************************
				/*
				 * 对于获取近期改动过的订单，saas user 表需要记录以下字段 1. initial_fetched_changed_order_since: 2015-7-24 表示从 1 到 2 这些时间 （整个date）的order changed order 都已经拿回来并且inserted OMS 2. routine_fetched_changed_order_from:2015-7-14T14:25:25 表示上次日常是从这个时间拿到现在的，changed order
				 */
				//
				// 因为wish sdk 做了分页获取然后所有order 一起发下来，所以我们不需要做分页了
				
				echo TimeUtil::getNow () . " start to get $uid changed order for " . $wishAccount ['store_name'] . " since $sinceTimeUTC \n"; // ystest
				$getOrderCount = 0;
				
				$starti = 0;
				$limit = 100;
				
				// 因为wish sdk 做了分页获取然后所有order 一起发下来，所以我们不需要做分页了
				try {
					$orders = self::_getAllChangeOrdersSince ( $wishAccount ['token'], $sinceTimeUTC, $starti, - 1 ); // UTC time is -8 hours
				} catch (\Exception $e) {
					echo "Failed to  get $uid all Change order :".$e->getMessage();
					\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"uid $uid all Change order  :".$e->getMessage()],"edb\global");
							// 解锁账号
					self::unlockWishOrderQueue ( $wishAccount ['site_id'] , 'get Change order Exception','F' );
				}
				                                                                                                  
				// fail to connect proxy
				if (empty ( $orders ['success'] ) or ! $orders ['success']) {
					self::unlockWishOrderQueue ( $wishAccount ['site_id'], "Error 1.01 fail to connect proxy  :" . $orders ['message'] ,'F');
					\Yii::error ( [ 
							'wish',
							__CLASS__,
							__FUNCTION__,
							'Background',
							"Error 1.01 fail to connect proxy  :" . $orders ['message'] 
					], "edb\global" );
					$wishAccount->save ();
					// 解锁账号
					
					continue;
				}
				echo TimeUtil::getNow () . " got results, start to insert oms \n"; // ystest
				                                                                   // accroding to api respone , update the last retrieve order time and last retrieve order success time
				if (! empty ( $orders ['proxyResponse'] ['success'] )) {
					// sync wish info to wish order table
					if (! empty ( $orders ['proxyResponse'] ['wishReturn'] )) {
						if (isset($orders['proxyResponse']['wishReturn']['data'])){
							echo "\n uid=$uid site id=". $wishAccount ['site_id']." has change index data is ok !";
						}else{
							echo "\n uid=$uid site id=". $wishAccount ['site_id']." not change index data is ok !";
						}
						// insert wish order
						$insertWishReturn = self::_InsertWishOrder ( $orders, $wishAccount );
						echo "\n uid=$uid site id=". $wishAccount ['site_id']." insert change order is ok!";
						//echo TimeUtil::getNow () . " start to get $uid all change order count= " . count($orders['proxyResponse']['wishReturn']['data']). " since $sinceTimeUTC30DaysAgo \n"; // testkh
					} // end of wishReturn empty or not
				} else {
					if (! empty ( $orders ['proxyResponse'] ['message'] )) {
						\Yii::error ( [ 
								'wish',
								__CLASS__,
								__FUNCTION__,
								'Background',
								"Proxy Error 1.0 proxy error  :" . $orders ['proxyResponse'] ['message'] 
						], "edb\global" );
					} else {
						\Yii::error ( [ 
								'wish',
								__CLASS__,
								__FUNCTION__,
								'Background',
								"Proxy Error 1.0 proxy error  : not any respone message" 
						], "edb\global" );
					}
				}
			} // end of routine fetch changed order from last update time
			
			if (! empty ( $orders ['proxyResponse'] ['message'] ) and 'Done With http Code 200' != $orders ['proxyResponse'] ['message']) {
				//mark proxy error
				$wishAccount->order_retrieve_message = $orders ['proxyResponse'] ['message'];
				echo TimeUtil::getNow () . "  get $uid all unfuilled message : " . $wishAccount->order_retrieve_message. "  \n"; //  testkh
			} else {
				
				if (! empty ( $insertWishReturn ['message'] ))
					$wishAccount->order_retrieve_message = $insertWishReturn ['message'];//mark insert error
				else 
					$wishAccount->order_retrieve_message = ''; //clear last error
				
				$wishAccount->last_order_success_retrieve_time = $updateTime;
			}
			
			if (! $wishAccount->save ()) {
				\Yii::error ( [ 
						'wish',
						__CLASS__,
						__FUNCTION__,
						'Background',
						"failure to save wish operation info ,uid:" . $wishAccount ['uid'] . "error:" . print_r ( $wishAccount->getErrors (), true ) 
				], "edb\global" );
			}
			self::_summaryWishOrderByNation ();
			
			// 解锁账号
			self::unlockWishOrderQueue ( $wishAccount ['site_id']);
		}//end of each wish user account
	}//end of _retrieveOrderInfoByAccount
	

	/**
	 * 根据action type 调用 对应 的wish api
	 * lkh 2014-12-09
	 */
	private static function _CallWishApiByActionType($wish_token,$action_type , $params){
		$timeout=120; //s
		\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"Step 1 start to call proxy for crt/upd prod,token $wish_token"],"edb\global");
		
		if (is_array($params)) 
			$params =json_encode($params);
		
		$reqParams['parms']  = $params;
		$reqParams['token'] = $wish_token;
		//the proxy will auto do update if the wish product id is existing
		$actionTypeToApiMapping = array(
				'order_ship'=>'fulfillOrderById',
				'order_cancel'=>'refundOrderById',
				'order_modify'=>'updateTrackingInfoById',
			);
			
		if (empty($actionTypeToApiMapping[$action_type])){
			$retInfo['success'] = false; 
			$retInfo['message'] = "$action_type 找不到对应的 api 映射！";
			return $retInfo;
		}
		$retInfo=WishProxyConnectHelper::call_WISH_api($actionTypeToApiMapping[$action_type],$reqParams );
		\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"Step 2 complete calling proxy for  action type $action_type ,token $wish_token"],"edb\global");
		//check the return info
		return $retInfo;	
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * wish api 获取的订单数组 保存到订单模块中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $orders		wish api 返回的结果
	 * @param $wishAccount		wish user model 
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 * @description			wish order  调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _InsertWishOrder($orders , $wishAccount,$isInitial = false){
		GLOBAL $HANDLED_WISH_ORDER,$INTEGRATED_ORDER_IDS,$INTEGRATED_ORDER_LAST_UPDATE;
		$rtn['inserted_count'] = 0;
		$rtn['updated_count'] = 0;
		$rtn['skipped_count'] = 0;
		try {
			//如果是初始化，会一个进程里面insert好多次订单，做这个cache处理加快速度
			if ($isInitial){
				$puid = \Yii::$app->subdb->getCurrentPuid();
				//Load all wish order id to cache， for all have been integrated before
				if (!isset($INTEGRATED_ORDER_IDS[$puid.''])){
					$integrated_orders = WishOrder::find()->select(['order_id','last_updated'])->asArray()->all();
					$INTEGRATED_ORDER_IDS[$puid.''] = 1;
					foreach($integrated_orders as $anWishOrder){
						$HANDLED_WISH_ORDER[$anWishOrder['order_id']] = 1;
						$INTEGRATED_ORDER_LAST_UPDATE[$anWishOrder['order_id']] = $anWishOrder['last_updated'];
					}
				}
			}
			
			echo "YS1.0 start to insert orders ...\n";
			$ImportOrderArr = array();
			$tempWishOrderModel = new WishOrder();
			$tempWishOrderDetailModel = new WishOrderDetail();
			$WishOrderModelAttr = $tempWishOrderModel->getAttributes();
			$WishOrderDetailModelAttr = $tempWishOrderDetailModel->getAttributes();
			$rtn['message'] ='';
			$rtn['success'] = true;
			
			$allOrders = $orders['proxyResponse']['wishReturn'];
			if (isset($orders['proxyResponse']['wishReturn']['data']))
				$allOrders = $orders['proxyResponse']['wishReturn']['data'];
			
			foreach($allOrders as $anOrder){
				$anOrder1 = $anOrder;	

				//兼容curl 格式的返回，可能还爆了一层 Order array
				if (isset($anOrder1['Order']) and is_array($anOrder1['Order']))
					$anOrder = $anOrder1['Order'];
					
				echo "get order ".$anOrder['order_id']."...";
				
				if (!empty($anOrder['buyer_id']))
					$anOrder['source_buyer_user_id'] = $anOrder['buyer_id'];
				
			 	
				//It is possible to do this again when we do fetch chagned order after we fetch unfufilled orders, so ignore this order id
				if ($isInitial and isset($anOrder['order_id']) and isset($HANDLED_WISH_ORDER[$anOrder['order_id']])
						and $INTEGRATED_ORDER_LAST_UPDATE[$anOrder['order_id']] == $anOrder['last_updated'] ){
					//echo " YS0.0a Order is handled, skip it: ".$anOrder['order_id']."\n";
					continue;
				}
				
				
				if ($anOrder['state'] == 'REQUIRE_REVIEW'){
					continue;
				}
				echo " YS0.0 Start to insert order ".$anOrder['order_id']." in state ".$anOrder['state']."\n";
				$OrderParms = array();
				if ($isInitial){
					if (isset($HANDLED_WISH_ORDER[$anOrder['order_id']])){
						$orderModel = WishOrder::find()->where(['order_id'=>$anOrder['order_id'] ])->one(); 
						$newCreateOrder = false;
						$rtn['updated_count'] ++;
					}else{
						//new order
						$orderModel = new WishOrder();
						$newCreateOrder = true;
						$rtn['inserted_count'] ++;
					}
				}else{//not intial
					$orderModel = WishOrder::find()->where(['order_id'=>$anOrder['order_id'] ])->one();
					if (!empty($orderModel)){
						$newCreateOrder = false;
						$rtn['updated_count'] ++;
					}else{
						//new order
						$orderModel = new WishOrder();
						$newCreateOrder = true;
						$rtn['inserted_count'] ++;
					}
				}//end of not initial

				//set order info 
				$orderInfo = array();
				$orderDetailInfo = array();
			//	echo " YS0.2   "; //ystest
				foreach($anOrder as $key=>$value){
					if ($key=='state'){
						$orderInfo['status'] = $value;
						continue;
					}
				 
					if ($key=='ShippingDetail'){
						foreach($value as $subkey=>$subvalue){
							if (array_key_exists($subkey, $WishOrderModelAttr)){
								$orderInfo[$subkey] = $subvalue;
							}
						}//end of each ShippingDetail
						continue;
					}
				 
					if (array_key_exists($key, $WishOrderModelAttr)){
						$orderInfo[$key] = $value;
					}
					
				 
					if (array_key_exists($key, $WishOrderDetailModelAttr)){
						if (strtolower($key) == 'price' ) $value = round($value,2);
						$orderDetailInfo[$key] = $value;
					}
				}
			//	echo " YS0.3   "; //ystest
				if (!empty($orderInfo)){
					//unset($orderInfo['order_id']);
					$orderModel->setAttributes ($orderInfo);
					if (! $orderModel->save()){
						\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"failure to save wish order,uid:".$wishAccount['uid']."error:". print_r($orderModel->getErrors(),true)],"edb\global");
					}
				}
			//	echo " YS0.4   "; //ystest
				//save order detail 
				if ($newCreateOrder)
					$orderDetails = new WishOrderDetail();
				else{
					$orderDetails = WishOrderDetail::find()->where(['order_id'=>$anOrder['order_id'] ])->one(); 
					if (empty($orderDetails)){
						$orderDetails = new WishOrderDetail();
					}
				}
				
				//remember this order id, so do not do this orderid again during this job, 
				//It is possible to do this again when we do fetch chagned order after we fetch unfufilled orders
				if (isset($anOrder['order_id']) and !isset($HANDLED_WISH_ORDER[$anOrder['order_id']]))
					$HANDLED_WISH_ORDER[$anOrder['order_id']] = 1;
			
				if (!empty($orderDetailInfo)){
					$orderDetailInfo['order_id'] = $anOrder['order_id'];
					$orderDetails->setAttributes ($orderDetailInfo);
					if (! $orderDetails->save()){
						\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"failure to save wish order details ,uid:".$wishAccount['uid']."error:". print_r($orderDetails->getErrors(),true)],"edb\global");										
					}
				}
			   // echo " YS0.7   ".$anOrder['order_id']."\n"; //ystest
				//set up default value  
				$anOrder['order_source_site_id'] = 'US';
				$anOrder['selleruserid'] = $wishAccount['store_name'];
				$anOrder['saas_platform_user_id'] = $wishAccount['site_id']; 
				//format import order data
				
				$formated_order_data = self::_formatImportOrderData( $anOrder );

				//echo " eagleOrderId:$eagleOrderRecordId start to duplicate it to eagle 2 oms \n";
		 
				//	Step 2: save this order to eagle OMS 2.0, using the same record ID		
				$importOrderResult=self::_saveWishOrderToEagle($formated_order_data);
				if (!isset($importOrderResult['success']) or $importOrderResult['success']==1){
					echo json_encode($importOrderResult);
				}else{
					//echo "Success insert an order rec id $eagleOrderRecordId to oms 2 \n";
				}
			}//end of each order 
			
			return $rtn;
		} catch (\Exception $e) {
			\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"insert wish order :".$e->getMessage() ],"edb\global");
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage() ." error line ".$e->getLine();
			echo "YSc001 error: ".$rtn['message']." \n try to handle order ";//.print_r($orders,true);
			return $rtn;
		}				
	}//end of _InsertWishOrder
	
	public static function test_formatImportOrderData(){
		$wishOrderData = '{"success":true,"message":"","wishReturn":[{"sku":"CS8614090215B","shipping_provider":"EMS","last_updated":"2016-03-10T03:29:47","product_id":"549269aa5384966c49cca950","order_time":"2015-01-30T03:42:09","order_id":"54cafd9197b82f117908bd4f","price":"4.99","shipping_cost":"1.69","tracking_number":"12100000000136","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"上海","name":"邵真","country":"CN","zipcode":"200060","street_address1":"上海市普陀区胶州路1077弄5号楼201室"},"shipped_date":"2015-02-02","state":"SHIPPED","cost":"4.24","variant_id":"549269aa5384966c49cca952","order_total":"5.93","buyer_id":"54b87b8cdf7b87158a56bb98","product_image_url":"https://contestimg.wish.com/api/webimage/549269aa5384966c49cca950-normal.jpg","product_name":"Fashion Stips Design iPhone 6 Case with Buckle Stand Function and Card Holder - Blue","transaction_id":"54cafd91133c2d0c35ea183f","quantity":"1","hours_to_fulfill":49},{"sku":"CS8614110603A","shipping_provider":"EMS","last_updated":"2016-03-10T03:29:46","product_id":"547d3f06b7b817420dff5974","order_time":"2015-01-29T15:03:55","order_id":"54ca4bdb8a03ba098cea44f9","price":"7.69","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"上海","name":"邵真","country":"CN","zipcode":"200060","street_address1":"上海市普陀区胶州路1077弄5号楼201室"},"shipped_date":"2015-01-30","state":"SHIPPED","cost":"6.54","variant_id":"547d3f06b7b817420dff5976","order_total":"8.23","buyer_id":"54b87b8cdf7b87158a56bb98","product_image_url":"https://contestimg.wish.com/api/webimage/547d3f06b7b817420dff5974-normal.jpg","product_name":"Two Fold Design PU Leather Flip Stand Tablet Full Body Protector Case Cover Skin with Lichee Pattern For iPad Air 2 - Rose","transaction_id":"54ca4bda9f10723f48029c40","quantity":"1"},{"sku":"CS8614103009E","shipping_provider":"SingaporePost","last_updated":"2016-03-10T03:29:44","product_id":"5487bd458ecdd40bace9b94a","order_time":"2015-01-27T09:07:35","order_id":"54c75557d17c20120af09d8a","price":"4.99","shipping_cost":"1.69","tracking_number":"RF172499314SG","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"上海","name":"邵真","country":"CN","zipcode":"200060","street_address1":"上海市普陀区胶州路1077弄5号楼201室"},"shipped_date":"2015-01-29","state":"SHIPPED","cost":"4.24","variant_id":"5487bd458ecdd40bace9b94c","order_total":"5.93","buyer_id":"54b87b8cdf7b87158a56bb98","product_image_url":"https://contestimg.wish.com/api/webimage/5487bd458ecdd40bace9b94a-normal.jpg","product_name":"Acrylic Two-in-one Case for Apple iPhone 6 plus(5.5\") - Golden","transaction_id":"54c75556ba0cc52aa27aa9d1","quantity":"1"},{"sku":"CS8614110603K","last_updated":"2016-03-04T12:10:10","product_id":"547d3cefd21cd410d1d698b5","order_time":"2016-03-04T11:54:37","order_id":"56d9777da04fb1131892d2aa","refunded_reason":"Customer cancelled the order","price":"7.69","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"4803527518","city":"San Tan Valley","state":"Arizona","name":"Beverly A Vanderberg","country":"US","zipcode":"85140","street_address1":"4610 E Shapinsay Dr"},"refunded_by":"CANCELLED BY CUSTOMER","state":"REFUNDED","cost":"6.54","refunded_time":"2016-03-04","variant_id":"547d3cefd21cd410d1d698b7","order_total":"8.23","buyer_id":"55b7955d250754416bbc2a97","product_image_url":"https://contestimg.wish.com/api/webimage/547d3cefd21cd410d1d698b5-normal.jpg","product_name":"Two Fold Design PU Leather Flip Stand Tablet Full Body Protector Case Cover Skin with Lichee Pattern For iPad Air 2 - Orange","transaction_id":"56d9777dd06b3729b1715669","quantity":"1"},{"last_updated":"2016-01-23T19:21:52","order_time":"2015-12-23T08:08:08","order_id":"567a5668c527e2545c5e4de8","price":"5.99","variant_id":"5653c3b155e4d07e9f3db5d4","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-12-24","cost":"5.09","shipping_cost":"1.69","product_image_url":"https://contestimg.wish.com/api/webimage/5653c3b155e4d07e9f3db5d2-normal.jpg","size":"6.4cm by 3.5cm by 0.3cm","sku":"GPGD15092401A","shipping_provider":"RussianPost","order_total":"6.78","product_id":"5653c3b155e4d07e9f3db5d2","state":"SHIPPED","shipping":"1.99","tracking_number":"RQ997185501CN","quantity":"1","product_name":"Socks for Pet Cat Dog Snowman Deer Christmas tree Snowflakes Patterned Christmas New Year Red + Green Socks 4 PCS","transaction_id":"567a56686a87ca417224eb28","buyer_id":"559a27b9e3806d41166b3026"},{"sku":"ssa2055","shipping_provider":"RussianPost","last_updated":"2016-01-23T19:11:19","product_id":"54a0b626ffff651e9f9fd7fc","order_time":"2015-12-23T08:11:38","order_id":"567a573a59094953885f5200","price":"5.99","shipping_cost":"1.69","tracking_number":"RQ997185500CN","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-12-24","state":"SHIPPED","cost":"5.09","variant_id":"54a0b626ffff651e9f9fd7fe","order_total":"6.78","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/54a0b626ffff651e9f9fd7fc-normal.jpg","product_name":"Handmade Crystal Bracelet With Chinese Symbol on Crystal bracelets bangles for women","transaction_id":"567a573a9bd7b59bc63fcb12","quantity":"1"},{"last_updated":"2016-01-23T18:48:19","order_time":"2015-12-23T08:12:20","order_id":"567a5764ce7b3e5378fe1217","price":"6.99","variant_id":"54754bd21280fa6d17144ed1","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-12-24","cost":"5.94","shipping_cost":"2.54","product_image_url":"https://contestimg.wish.com/api/webimage/54754bd21280fa6d17144ecf-normal.jpg","size":"Cubic","sku":"ACCUSBVEN001A","shipping_provider":"RussianPost","order_total":"8.48","product_id":"54754bd21280fa6d17144ecf","state":"SHIPPED","shipping":"2.99","tracking_number":"RQ997185501CN","quantity":"1","product_name":"Mini USB Fan Fan cute little computer fan mute shipping","transaction_id":"567a5764abe9c74a7065cdd9","buyer_id":"559a27b9e3806d41166b3026"},{"last_updated":"2016-01-23T18:48:14","order_time":"2015-12-23T08:12:29","order_id":"567a576d3e61274eccdcecbc","price":"6.99","variant_id":"54754bd21280fa6d17144ed1","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-12-24","cost":"5.94","shipping_cost":"2.54","product_image_url":"https://contestimg.wish.com/api/webimage/54754bd21280fa6d17144ecf-normal.jpg","size":"Cubic","sku":"ACCUSBVEN001A","shipping_provider":"RussianPost","order_total":"8.48","product_id":"54754bd21280fa6d17144ecf","state":"SHIPPED","shipping":"2.99","tracking_number":"RQ997185500CN","quantity":"1","product_name":"Mini USB Fan Fan cute little computer fan mute shipping","transaction_id":"567a576dd8e03d6bc9d6e984","buyer_id":"559a27b9e3806d41166b3026"},{"last_updated":"2016-01-12T06:33:42","order_time":"2016-01-06T23:25:57","order_id":"568da2855a74357142a7d963","price":"7.69","variant_id":"547d3cefd21cd410d1d698b7","ShippingDetail":{"phone_number":"17097697623","city":"Holyrood","state":"Newfoundland and Labrador","name":"Julia Claire Kieley","country":"CA","zipcode":"A0A 2R0","street_address1":"295 Conception Bay Highway"},"shipped_date":"2016-01-10","refunded_time":"2016-01-12","cost":"6.54","shipping_cost":"1.69","product_image_url":"https://contestimg.wish.com/api/webimage/547d3cefd21cd410d1d698b5-normal.jpg","sku":"CS8614110603K","refunded_by":"REFUNDED BY MERCHANT","shipping_provider":"ChinaAirPost","order_total":"8.23","product_id":"547d3cefd21cd410d1d698b5","state":"REFUNDED","shipping":"1.99","tracking_number":"RX833132638DE","refunded_reason":"Unable to fulfill order","quantity":"1","product_name":"Two Fold Design PU Leather Flip Stand Tablet Full Body Protector Case Cover Skin with Lichee Pattern For iPad Air 2 - Orange","transaction_id":"568da2839e003b5fabc34143","buyer_id":"5633e9717be5bad712704cf4"},{"sku":"CS8614110604A","last_updated":"2016-01-09T07:02:23","product_id":"547d46a4dbb0d64ac14db3c2","order_time":"2016-01-08T04:42:01","order_id":"568f3e1931c3f75bbafe862c","refunded_reason":"Customer cancelled the order","price":"6.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"2606671506","city":"Angola","state":"Indiana","name":"Justin Mashione","country":"US","zipcode":"46703","street_address1":"1305 W 275 N"},"refunded_by":"CANCELLED BY CUSTOMER","state":"REFUNDED","cost":"5.94","refunded_time":"2016-01-09","variant_id":"547d46a4dbb0d64ac14db3c4","order_total":"7.63","buyer_id":"562f25f8d62e7c1426a587f3","product_image_url":"https://contestimg.wish.com/api/webimage/547d46a4dbb0d64ac14db3c2-normal.jpg","product_name":"High Quality 360 Degree Rotating Stand Leather Case Smart Cover For iPad Air 2 , With Automatic Wake/Sleep Function - White","transaction_id":"568f3e197dfda5e7be4af41b","quantity":"1"},{"last_updated":"2015-12-31T13:34:02","order_time":"2015-12-29T03:42:59","order_id":"568201439377b5464488b1ca","price":"6.99","variant_id":"54754bd21280fa6d17144ed1","ShippingDetail":{"phone_number":"8508329950","city":"lynn haven","state":"Florida","name":"christian","country":"US","zipcode":"32444","street_address1":"3514 pleasant hill rd"},"shipped_date":"2015-12-31","cost":"5.94","shipping_cost":"2.54","product_image_url":"https://contestimg.wish.com/api/webimage/54754bd21280fa6d17144ecf-normal.jpg","size":"Cubic","sku":"ACCUSBVEN001A","shipping_provider":"USPS","order_total":"8.48","product_id":"54754bd21280fa6d17144ecf","state":"SHIPPED","shipping":"2.99","tracking_number":"LN245713568SG","quantity":"1","product_name":"Mini USB Fan Fan cute little computer fan mute shipping","transaction_id":"568201437e8ae02ec59e81de","buyer_id":"5681fd1245dda40a9574f0ed"},{"sku":"LSAM14102301A","shipping_provider":"WishPost","last_updated":"2015-12-08T16:06:49","product_id":"547565d08edcfa11a2218448","order_time":"2015-10-24T19:39:39","order_id":"562bde7b553121470a39793d","price":"9.99","shipping_cost":"2.54","tracking_number":"RI709804004CN","shipping":"2.99","ShippingDetail":{"phone_number":"604-351-9629","city":"PORT COQUITLAM","state":"BC","name":"Alex Pesusich","country":"CA","zipcode":"V3C 5J2","street_address1":"2314 COLONIAL DR"},"shipped_date":"2015-10-27","state":"SHIPPED","cost":"8.49","variant_id":"547565d08edcfa11a221844a","order_total":"11.03","buyer_id":"55ed480ff2d320436d470282","product_image_url":"https://contestimg.wish.com/api/webimage/547565d08edcfa11a2218448-normal.jpg","product_name":"Music Starry Star Sky Projection Alarm Clock Calendar Thermometer For Best gift,freeshipping","transaction_id":"562bde73bbcc4b1d285cafdb","quantity":"1"},{"sku":"CS8614090217A","shipping_provider":"RussianPost","last_updated":"2015-12-01T15:20:06","product_id":"549269a4da081c069bf8a6ef","order_time":"2015-11-02T07:14:52","order_id":"56370d6c1357e52ff18175ec","price":"4.99","shipping_cost":"1.69","tracking_number":"RQ997185551CN","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-11-03","state":"SHIPPED","cost":"4.24","variant_id":"549269a4da081c069bf8a6f1","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/549269a4da081c069bf8a6ef-normal.jpg","product_name":"Silk PU Leather iPhone 6 Case with Strip - Pink","transaction_id":"56370d6b98c867141601719a","quantity":"1"},{"sku":"CS8614090217A","shipping_provider":"USPS","last_updated":"2015-11-23T12:08:11","product_id":"549269a4da081c069bf8a6ef","order_time":"2015-10-27T07:55:48","order_id":"562f2e045f36e32fed4b3a59","price":"4.99","shipping_cost":"1.69","tracking_number":"LN243459735SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-10-29","state":"SHIPPED","cost":"4.24","variant_id":"549269a4da081c069bf8a6f1","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/549269a4da081c069bf8a6ef-normal.jpg","product_name":"Silk PU Leather iPhone 6 Case with Strip - Pink","transaction_id":"562f2e047de6811791a37cc1","quantity":"1"},{"sku":"CS8614090217A","shipping_provider":"USPS","last_updated":"2015-11-23T11:42:42","product_id":"549269a4da081c069bf8a6ef","order_time":"2015-10-27T07:41:06","order_id":"562f2a921dd92b3010c27686","price":"4.99","shipping_cost":"1.69","tracking_number":"LN243510341SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-10-30","state":"SHIPPED","cost":"4.24","variant_id":"549269a4da081c069bf8a6f1","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/549269a4da081c069bf8a6ef-normal.jpg","product_name":"Silk PU Leather iPhone 6 Case with Strip - Pink","transaction_id":"562f2a92f1187334d08fbab3","quantity":"1"},{"sku":"CS8614090217A","shipping_provider":"USPS","last_updated":"2015-11-23T11:42:39","product_id":"549269a4da081c069bf8a6ef","order_time":"2015-10-27T07:57:33","order_id":"562f2e6dfb3ee33116f00b34","price":"4.99","shipping_cost":"1.69","tracking_number":"LN243459735SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-10-29","state":"SHIPPED","cost":"4.24","variant_id":"549269a4da081c069bf8a6f1","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/549269a4da081c069bf8a6ef-normal.jpg","product_name":"Silk PU Leather iPhone 6 Case with Strip - Pink","transaction_id":"562f2e6df5f254170e3c673a","quantity":"1"},{"sku":"CS8614090217A","shipping_provider":"USPS","last_updated":"2015-10-29T15:37:39","product_id":"549269a4da081c069bf8a6ef","order_time":"2015-10-27T08:02:06","order_id":"562f2f7efb3ee32fc4f00b3d","price":"4.99","shipping_cost":"1.69","tracking_number":"LN243474627SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-10-28","state":"SHIPPED","cost":"4.24","variant_id":"549269a4da081c069bf8a6f1","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/549269a4da081c069bf8a6ef-normal.jpg","product_name":"Silk PU Leather iPhone 6 Case with Strip - Pink","transaction_id":"562f2f7eaf1a7934140eee9a","quantity":"1"},{"sku":"CS8614110603K","last_updated":"2015-10-03T19:51:47","product_id":"547d3cefd21cd410d1d698b5","order_time":"2015-10-03T19:50:12","order_id":"561031745c72301e70766a9d","refunded_reason":"Customer cancelled the order","price":"7.69","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"94367868","city":"Singapore","name":"Sandy Tan","country":"SG","street_address2":"#06-19","street_address1":"Yung Ping Road Block 162","zipcode":"610162"},"refunded_by":"CANCELLED BY CUSTOMER","state":"REFUNDED","cost":"6.54","refunded_time":"2015-10-03","variant_id":"547d3cefd21cd410d1d698b7","order_total":"8.23","buyer_id":"55eaaee10423bfbe8aa84bb5","product_image_url":"https://contestimg.wish.com/api/webimage/547d3cefd21cd410d1d698b5-normal.jpg","product_name":"Two Fold Design PU Leather Flip Stand Tablet Full Body Protector Case Cover Skin with Lichee Pattern For iPad Air 2 - Orange","transaction_id":"56103172981c6e0f455b3155","quantity":"1"},{"sku":"CS8614103009E","shipping_provider":"WishPost","last_updated":"2015-10-01T15:23:32","product_id":"5487bd458ecdd40bace9b94a","order_time":"2015-08-16T04:30:26","order_id":"55d011e23b9c8742638d8446","price":"4.99","shipping_cost":"1.69","tracking_number":"RI526566817CN","shipping":"1.99","ShippingDetail":{"phone_number":"6475452666","city":"Toronto","state":"Ontario","name":"DAEVD,NOAIL A BQTR","country":"CA","zipcode":"M9V 4N9","street_address1":"2667 KIPLING AVE NORTH YORK"},"shipped_date":"2015-08-17","state":"SHIPPED","cost":"4.24","variant_id":"5487bd458ecdd40bace9b94c","order_total":"5.93","buyer_id":"55aeecd8b9dd354170bffb4b","product_image_url":"https://contestimg.wish.com/api/webimage/5487bd458ecdd40bace9b94a-normal.jpg","product_name":"Acrylic Two-in-one Case for Apple iPhone 6 plus(5.5\") - Golden","transaction_id":"55d011e25c8e3b4385e7c1c6","quantity":"1"},{"sku":"LSAM14102301A","shipping_provider":"EPacket","last_updated":"2015-09-22T01:10:05","product_id":"547565d08edcfa11a2218448","order_time":"2015-09-19T23:21:50","order_id":"55fdee0e3c5c5b6ee7d928ed","price":"9.99","shipping_cost":"2.54","tracking_number":"RS207641154GB","shipping":"2.99","ShippingDetail":{"phone_number":"9059308874","city":"Stoney creek","state":"Ontario","name":"Risa","country":"CA","zipcode":"L8E 4Z1","street_address1":"58-2 royalwood court"},"shipped_date":"2015-09-22","state":"SHIPPED","cost":"8.49","variant_id":"547565d08edcfa11a221844a","order_total":"22.06","buyer_id":"552fcac4442dfb0cbe3b7127","product_image_url":"https://contestimg.wish.com/api/webimage/547565d08edcfa11a2218448-normal.jpg","product_name":"Music Starry Star Sky Projection Alarm Clock Calendar Thermometer For Best gift,freeshipping","transaction_id":"55fdee0ee111e369a7186ddb","quantity":"2"},{"sku":"C1565a","shipping_provider":"SingaporePost","last_updated":"2015-09-16T05:10:21","product_id":"548555a4e293c90f9acf7353","order_time":"2015-08-07T10:41:36","order_id":"55c48b60305a5c5b1a71bb38","price":"10.99","shipping_cost":"1.69","tracking_number":"RF344910547SG","shipping":"1.99","ShippingDetail":{"phone_number":"015218975190","city":"Ummern","name":"Musa Kajtazi","country":"DE","zipcode":"29369","street_address1":"Steinberg 14"},"shipped_date":"2015-08-10","state":"SHIPPED","cost":"9.34","variant_id":"548555a4e293c90f9acf7355","order_total":"11.03","buyer_id":"55138ad4d0ee2b12c4301e86","product_image_url":"https://contestimg.wish.com/api/webimage/548555a4e293c90f9acf7353-normal.jpg","product_name":"High Quality 360 Degree Rotating Stand Leather Case Smart Cover For iPad Air 2 iPad 6 case, With Automatic Wake/Sleep Function - White","transaction_id":"55c48b591d1791a9e4d2207f","quantity":"1"},{"sku":"CS8614103009E","last_updated":"2015-08-18T08:27:39","product_id":"5487bd458ecdd40bace9b94a","order_time":"2015-08-18T05:28:44","order_id":"55d2c28c4a4ce676e60d4e84","refunded_reason":"Customer cancelled the order","price":"4.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"4253436465","city":"Everett","state":"Washington","name":"Yuriy","country":"US","zipcode":"98204","street_address1":"12119 30th ave w"},"refunded_by":"CANCELLED BY CUSTOMER","state":"REFUNDED","cost":"4.24","refunded_time":"2015-08-18","variant_id":"5487bd458ecdd40bace9b94c","order_total":"5.93","buyer_id":"55a5e88420e7ce6b677645ca","product_image_url":"https://contestimg.wish.com/api/webimage/5487bd458ecdd40bace9b94a-normal.jpg","product_name":"Acrylic Two-in-one Case for Apple iPhone 6 plus(5.5\") - Golden","transaction_id":"55d2c26e54a72a4f0a92fe83","quantity":"1"},{"last_updated":"2015-08-18T07:07:46","order_time":"2015-07-15T09:09:12","order_id":"55a623386ce88e0c0cf3adfc","price":"4.99","variant_id":"549269b84bb5007ec6581d81","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-07-16","refunded_time":"2015-08-18","cost":"4.24","shipping_cost":"1.69","product_image_url":"https://contestimg.wish.com/api/webimage/549269b84bb5007ec6581d7f-normal.jpg","sku":"CS8614090213C","refunded_by":"REFUNDED BY WISH FOR MERCHANT","shipping_provider":"SingaporePost","order_total":"5.93","product_id":"549269b84bb5007ec6581d7f","state":"REFUNDED","shipping":"1.99","tracking_number":"RF340850264SG","refunded_reason":"Item did not arrive on time","quantity":"1","product_name":"Leopard iPhone 6 Case with Stand Function and Card Holder - White","transaction_id":"55a6232680e2f66aca6086d9","buyer_id":"559a27b9e3806d41166b3026"},{"sku":"CS8614103009E","shipping_provider":"USPS","last_updated":"2015-08-10T01:40:06","product_id":"5487bd458ecdd40bace9b94a","order_time":"2015-08-08T03:37:30","order_id":"55c5797abdf7e634a4501b24","price":"4.99","shipping_cost":"1.69","tracking_number":"LN241764502SG","shipping":"1.99","ShippingDetail":{"phone_number":"4078026029","city":"Orlando","state":"Florida","name":"Tiffany R Colon","country":"US","zipcode":"32825","street_address1":"10066 doriath circle"},"shipped_date":"2015-08-10","state":"SHIPPED","cost":"4.24","variant_id":"5487bd458ecdd40bace9b94c","order_total":"5.93","buyer_id":"5517a74aab4fba4fb671433e","product_image_url":"https://contestimg.wish.com/api/webimage/5487bd458ecdd40bace9b94a-normal.jpg","product_name":"Acrylic Two-in-one Case for Apple iPhone 6 plus(5.5\") - Golden","transaction_id":"55c57917c6f0ba5c3f0bce69","quantity":"1"},{"sku":"CS8614103009E","shipping_provider":"SingaporePost","last_updated":"2015-08-07T01:55:22","product_id":"5487bd458ecdd40bace9b94a","order_time":"2015-07-15T09:11:48","order_id":"55a623d4ea3f735d1ba4e7f8","price":"4.99","shipping_cost":"1.69","tracking_number":"RF340850261SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-07-16","state":"SHIPPED","cost":"4.24","variant_id":"5487bd458ecdd40bace9b94c","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/5487bd458ecdd40bace9b94a-normal.jpg","product_name":"Acrylic Two-in-one Case for Apple iPhone 6 plus(5.5\") - Golden","transaction_id":"55a623d279a5ed42fb3a4019","quantity":"1"},{"sku":"CS8614110604A","shipping_provider":"ChinaAirPost","last_updated":"2015-08-07T01:13:33","product_id":"547d46a4dbb0d64ac14db3c2","order_time":"2015-08-06T15:05:29","order_id":"55c377b98932cc4399361e64","price":"6.99","shipping_cost":"1.69","tracking_number":"RI570358495CN","shipping":"1.99","ShippingDetail":{"phone_number":"26298727","city":"Silkeborg","name":"Christian Hovde","country":"DK","zipcode":"8600","street_address1":"Gøteborgvej 1"},"shipped_date":"2015-08-07","state":"SHIPPED","cost":"5.94","variant_id":"547d46a4dbb0d64ac14db3c4","order_total":"7.63","buyer_id":"55856b8350c1cc6cf6f87cbb","product_image_url":"https://contestimg.wish.com/api/webimage/547d46a4dbb0d64ac14db3c2-normal.jpg","product_name":"High Quality 360 Degree Rotating Stand Leather Case Smart Cover For iPad Air 2 , With Automatic Wake/Sleep Function - White","transaction_id":"55c377b946013e42c7442fe8","quantity":"1"},{"sku":"CS8614090217A","shipping_provider":"SingaporePost","last_updated":"2015-08-04T23:16:38","product_id":"549269a4da081c069bf8a6ef","order_time":"2015-07-11T06:04:30","order_id":"55a0b1ee01538c0fbc521192","price":"4.99","shipping_cost":"1.69","tracking_number":"RF340850264SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-07-13","state":"SHIPPED","cost":"4.24","variant_id":"549269a4da081c069bf8a6f1","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/549269a4da081c069bf8a6ef-normal.jpg","product_name":"Silk PU Leather iPhone 6 Case with Strip - Pink","transaction_id":"55a0b1ec438e4c564ca03bd0","quantity":"1"},{"sku":"CS8614090213C","shipping_provider":"SingaporePost","last_updated":"2015-08-04T23:16:38","product_id":"549269b84bb5007ec6581d7f","order_time":"2015-07-11T06:02:30","order_id":"55a0b1764693f419409b194b","price":"4.99","shipping_cost":"1.69","tracking_number":"RF340850264SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-07-13","state":"SHIPPED","cost":"4.24","variant_id":"549269b84bb5007ec6581d81","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/549269b84bb5007ec6581d7f-normal.jpg","product_name":"Leopard iPhone 6 Case with Stand Function and Card Holder - White","transaction_id":"55a0b15d00087b8e70041d84","quantity":"1"},{"sku":"CS8614111202F","shipping_provider":"SingaporePost","last_updated":"2015-08-03T23:01:19","product_id":"547ca39f403d5743e332e85e","order_time":"2015-07-10T04:05:49","order_id":"559f449d3cc89a4c48067115","price":"6.69","shipping_cost":"1.69","tracking_number":"RF340655089SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-07-11","state":"SHIPPED","cost":"5.69","variant_id":"547ca39f403d5743e332e860","order_total":"7.38","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/547ca39f403d5743e332e85e-normal.jpg","product_name":"Craquelure PU Case for Apple iPhone 6 (5.5\")withInteractiveViewWindowandStandFeature-DarkBlue","transaction_id":"559f44956c1aa145a256a0d5","quantity":"1"},{"sku":"CS8614110603K","shipping_provider":"SingaporePost","last_updated":"2015-08-03T22: 59: 02","product_id":"547d3cefd21cd410d1d698b5","order_time":"2015-07-10T04: 09: 38","order_id":"559f4582300ec6574ec2f492","price":"7.69","shipping_cost":"1.69","tracking_number":"548651562","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"UnitedStates","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647sidonieave"},"shipped_date":"2015-07-15","state":"SHIPPED","cost":"6.54","variant_id":"547d3cefd21cd410d1d698b7","order_total":"8.23","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https: //contestimg.wish.com/api/webimage/547d3cefd21cd410d1d698b5-normal.jpg","product_name":"TwoFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithLicheePatternForiPadAir2-Orange","transaction_id":"559f4581ca1f70410488bb37","quantity":"1"},{"sku":"CS8614111202F","last_updated":"2015-08-02T23: 11: 41","product_id":"547ca39f403d5743e332e85e","order_time":"2015-07-09T07: 37: 07","order_id":"559e24a37a20a369964924ae","price":"6.69","shipping_cost":"1.69","tracking_number":"RF172499314SG_SHEN","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"UnitedStates","state":"Michigan","name":"shen","country":"US","zipcode":"48089","street_address1":"12645sidonieave"},"shipped_date":"2015-07-10","state":"SHIPPED","cost":"5.69","variant_id":"547ca39f403d5743e332e860","order_total":"7.38","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https: //contestimg.wish.com/api/webimage/547ca39f403d5743e332e85e-normal.jpg","product_name":"CraquelurePUCaseforAppleiPhone6(5.5\")withInteractiveViewWindowandStandFeature-DarkBlue","transaction_id":"559e247c2b46d641280c7aed","quantity":"1"},{"sku":"CS8614103009E","shipping_provider":"SingaporePost","last_updated":"2015-08-02T23: 10: 11","product_id":"5487bd458ecdd40bace9b94a","order_time":"2015-07-09T07: 38: 52","order_id":"559e250c710916512cb621c4","price":"4.99","shipping_cost":"1.69","tracking_number":"RF340627998SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"UnitedStates","state":"Michigan","name":"shen001","country":"US","zipcode":"48089","street_address1":"12646sidonieave"},"shipped_date":"2015-07-10","state":"SHIPPED","cost":"4.24","variant_id":"5487bd458ecdd40bace9b94c","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https: //contestimg.wish.com/api/webimage/5487bd458ecdd40bace9b94a-normal.jpg","product_name":"AcrylicTwo-in-oneCaseforAppleiPhone6plus(5.5\")-Golden","transaction_id":"559e24e5298ba04110a8f395","quantity":"1"},{"sku":"CS8614111202F","shipping_provider":"SingaporePost","last_updated":"2015-07-29T23: 26: 08","product_id":"547ca39f403d5743e332e85e","order_time":"2015-07-06T08: 46: 38","order_id":"559a406effe98c402f3c2dbf","price":"6.69","shipping_cost":"1.69","tracking_number":"RF313330397SG1","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"UnitedStates","state":"Michigan","name":"shen","country":"US","zipcode":"48089","street_address1":"12645sidonieave"},"shipped_date":"2015-07-10","state":"SHIPPED","cost":"5.69","variant_id":"547ca39f403d5743e332e860","order_total":"7.38","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https: //contestimg.wish.com/api/webimage/547ca39f403d5743e332e85e-normal.jpg","product_name":"CraquelurePUCaseforAppleiPhone6(5.5\")withInteractiveViewWindowandStandFeature-DarkBlue","transaction_id":"559a406ae3806d41186b3eab","quantity":"1"},{"sku":"CS8614090215D","shipping_provider":"ChinaAirPost","last_updated":"2015-07-27T20: 53: 06","product_id":"549269c8a4644f7769f629a8","order_time":"2015-07-10T01: 52: 31","order_id":"559f255fc50867700af40183","price":"4.99","shipping_cost":"1.69","tracking_number":"LN241437167SG","shipping":"1.99","ShippingDetail":{"phone_number":"4175934664","city":"Branson","state":"Missouri","name":"SeanLovellIves","country":"US","zipcode":"65616","street_address1":"810MockingbirdLane"},"shipped_date":"2015-07-13","state":"SHIPPED","cost":"4.24","variant_id":"549269c8a4644f7769f629aa","order_total":"5.93","buyer_id":"54d813ddeee48f0a3844492a","product_image_url":"https: //contestimg.wish.com/api/webimage/549269c8a4644f7769f629a8-normal.jpg","product_name":"FashionStipsDesigniPhone6CasewithBuckleStandFunctionandCardHolder-Blue","transaction_id":"559f255054317a46b959c951","quantity":"1"},{"sku":"CS8614090213C","shipping_provider":"SingaporePost","last_updated":"2015-07-27T07: 00: 00","product_id":"549269b84bb5007ec6581d7f","order_time":"2015-07-10T04: 08: 32","order_id":"559f4540fb162f636d17ecff","price":"4.99","shipping_cost":"1.69","tracking_number":"RF341132242SG_V1","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"UnitedStates","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647sidonieave"},"shipped_date":"2015-07-14","state":"SHIPPED","cost":"4.24","variant_id":"549269b84bb5007ec6581d81","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https: //contestimg.wish.com/api/webimage/549269b84bb5007ec6581d7f-normal.jpg","product_name":"LeopardiPhone6CasewithStandFunctionandCardHolder-White","transaction_id":"559f453c59628046b96727d6","quantity":"1"},{"last_updated":"2015-07-26T04: 35: 23","order_time":"2015-06-25T23: 09: 40","order_id":"558c8a34bdcaee66a5a20daf","price":"9.99","variant_id":"548fb95f687f3f1051032e1f","ShippingDetail":{"phone_number":"8472975099","city":"DesPlaines","state":"Illinois","name":"KatherineLazzara","country":"US","zipcode":"60016","street_address1":"573N3rdAve"},"shipped_date":"2015-06-29","refunded_time":"2015-07-26","cost":"8.49","shipping_cost":"1.69","product_image_url":"https: //contestimg.wish.com/api/webimage/548fb95f687f3f1051032e1d-normal.jpg","sku":"CS861410212A","refunded_by":"REFUNDEDBYWISHFORMERCHANT","shipping_provider":"ChinaAirPost","order_total":"10.18","product_id":"548fb95f687f3f1051032e1d","state":"REFUNDED","shipping":"1.99","tracking_number":"LN241291475SG","refunded_reason":"Customerreceivedthewrongitem","quantity":"1","product_name":"LeatherWalletCaseforAppleiPhone6(5.5)withCreditCardIDHoldersandMapPattern-Nigger-Brown\"","transaction_id":"558c8a346a098c71952d66c4","buyer_id":"556d165cefa661277608975e"},{"sku":"CS8614090213C","shipping_provider":"ChinaAirPost","last_updated":"2015-07-24T06:43:37","product_id":"549269b84bb5007ec6581d7f","order_time":"2015-07-21T17:49:07","order_id":"55ae8613535292473e66213e","price":"4.99","shipping_cost":"1.69","tracking_number":"RI997919990CN","shipping":"1.99","ShippingDetail":{"phone_number":"41799077733","city":"Dietikon","state":"Zürich","name":"Selvija Pulja","country":"CH","zipcode":"8953","street_address1":"Altbergstrasse 2"},"shipped_date":"2015-07-24","state":"SHIPPED","cost":"4.24","variant_id":"549269b84bb5007ec6581d81","order_total":"5.93","buyer_id":"53e23d5cfb56f82d3ac05f14","product_image_url":"https://contestimg.wish.com/api/webimage/549269b84bb5007ec6581d7f-normal.jpg","product_name":"Leopard iPhone 6 Case with Stand Function and Card Holder - White","transaction_id":"55ae860d4aee59416548557d","quantity":"1"},{"sku":"CS8614103009E","last_updated":"2015-07-21T15:13:10","product_id":"5487bd458ecdd40bace9b94a","order_time":"2015-07-17T08:59:57","order_id":"55a8c40d2ca915404455c681","refunded_reason":"Order is suspected fraud","price":"4.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"jiangchao","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"refunded_by":"REFUNDED BY WISH","state":"REFUNDED","cost":"4.24","refunded_time":"2015-07-21","variant_id":"5487bd458ecdd40bace9b94c","order_total":"5.93","buyer_id":"559a26887f6aa0412389933f","product_image_url":"https://contestimg.wish.com/api/webimage/5487bd458ecdd40bace9b94a-normal.jpg","product_name":"Acrylic Two-in-one Case for Apple iPhone 6 plus(5.5\") - Golden","transaction_id":"55a8c4098601614178be519c","quantity":"1"},{"sku":"CS8414082214H","shipping_provider":"SingaporePost","last_updated":"2015-07-20T01:09:52","product_id":"5498d3665c0a25103ea662c2","order_time":"2015-07-17T14:44:22","order_id":"55a914c6e88f1140252b32cd","price":"6.99","shipping_cost":"1.69","tracking_number":"LN241517599SG","shipping":"1.99","ShippingDetail":{"phone_number":"4248889512","city":"Los Angeles","state":"California","name":"Derrick Jones","country":"US","zipcode":"90014","street_address1":"601 south san pedro"},"shipped_date":"2015-07-20","state":"SHIPPED","cost":"5.94","variant_id":"5498d3665c0a25103ea662c4","order_total":"7.63","buyer_id":"558709897da0a0405debcaba","product_image_url":"https://contestimg.wish.com/api/webimage/5498d3665c0a25103ea662c2-normal.jpg","product_name":"Contrast color Leather case for LG G3 D855-black","transaction_id":"55a914b66557cc4130b8c5ee","quantity":"1"},{"sku":"CS8614090217E","last_updated":"2015-07-17T21:17:06","product_id":"549269a3ff7e7d103adf3971","order_time":"2015-07-17T21:11:16","order_id":"55a96f74cd37ab6da93b5cde","refunded_reason":"Customer cancelled the order","price":"4.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"5126807436","city":"Colorado Springs","state":"Colorado","name":"Lyric Olivarez","country":"US","zipcode":"80918","street_address1":"1710 Seclusion Pt. Unit J"},"refunded_by":"CANCELLED BY CUSTOMER","state":"REFUNDED","cost":"4.24","refunded_time":"2015-07-17","variant_id":"549269a3ff7e7d103adf3973","order_total":"5.93","buyer_id":"559cb15b478d3d8be000b2c1","product_image_url":"https://contestimg.wish.com/api/webimage/549269a3ff7e7d103adf3971-normal.jpg","product_name":"Silk PU Leather iPhone 6 Case with Strip - Pink","transaction_id":"55a96f744175866803710fc9","quantity":"1"},{"sku":"CS8614111202F","shipping_provider":"ChinaAirPost","last_updated":"2015-07-14T07:10:17","product_id":"547ca39f403d5743e332e85e","order_time":"2015-07-06T08:02:52","order_id":"559a362ccdc0be401f2a418c","price":"6.69","shipping_cost":"1.69","tracking_number":"15466448","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen","country":"US","zipcode":"48089","street_address1":"12645 sidonie ave"},"shipped_date":"2015-07-07","state":"SHIPPED","cost":"5.69","variant_id":"547ca39f403d5743e332e860","order_total":"7.38","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https://contestimg.wish.com/api/webimage/547ca39f403d5743e332e85e-normal.jpg","product_name":"Craquelure PU Case for Apple iPhone 6 (5.5\")withInteractiveViewWindowandStandFeature-DarkBlue","transaction_id":"559a361a6e6a684131508f08","quantity":"1"},{"sku":"CS8614090217A","shipping_provider":"SingaporePost","last_updated":"2015-07-10T14: 28: 02","product_id":"549269a4da081c069bf8a6ef","order_time":"2015-07-10T04: 09: 38","order_id":"559f4582300ec6574ec2f491","price":"4.99","shipping_cost":"1.69","tracking_number":"RF340646609SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"UnitedStates","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647sidonieave"},"shipped_date":"2015-07-10","state":"SHIPPED","cost":"4.24","variant_id":"549269a4da081c069bf8a6f1","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https: //contestimg.wish.com/api/webimage/549269a4da081c069bf8a6ef-normal.jpg","product_name":"SilkPULeatheriPhone6CasewithStrip-Pink","transaction_id":"559f4581ca1f70410488bb37","quantity":"1"},{"sku":"CS8614103008D","shipping_provider":"SingaporePost","last_updated":"2015-07-10T09: 31: 02","product_id":"5487bdfdc27fdc6aceafac90","order_time":"2015-07-09T07: 40: 25","order_id":"559e25692835292ebc7045ff","price":"4.99","shipping_cost":"1.69","tracking_number":"RF340580045SG","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"UnitedStates","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647sidonieave"},"shipped_date":"2015-07-10","state":"SHIPPED","cost":"4.24","variant_id":"5487bdfdc27fdc6aceafac92","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https: //contestimg.wish.com/api/webimage/5487bdfdc27fdc6aceafac90-normal.jpg","product_name":"AcrylicTwo-in-oneCaseforAppleiPhone6(4.7\")-Purple","transaction_id":"559e25684fc3cd411405c4a2","quantity":"1"},{"sku":"CS8614110603K","shipping_provider":"PX4","last_updated":"2015-07-10T02: 39: 27","product_id":"547d3cefd21cd410d1d698b5","order_time":"2015-07-06T08: 46: 38","order_id":"559a406effe98c402f3c2dbe","price":"7.69","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"UnitedStates","state":"Michigan","name":"shen","country":"US","zipcode":"48089","street_address1":"12645sidonieave"},"shipped_date":"2015-07-09","state":"SHIPPED","cost":"6.54","variant_id":"547d3cefd21cd410d1d698b7","order_total":"8.23","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https: //contestimg.wish.com/api/webimage/547d3cefd21cd410d1d698b5-normal.jpg","product_name":"TwoFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithLicheePatternForiPadAir2-Orange","transaction_id":"559a406ae3806d41186b3eab","quantity":"1"},{"last_updated":"2015-06-30T08: 51: 10","order_time":"2015-06-28T04: 37: 35","order_id":"558f7a0fb61bc61230c9c2dd","price":"9.69","variant_id":"548695581c628f0f49db383c","ShippingDetail":{"phone_number":"6503914338","city":"RedwoodCity","state":"California","name":"RogelioFernandez","country":"US","street_address2":"redwoodcity","street_address1":"3125pagest","zipcode":"94063"},"shipped_date":"2015-06-29","refunded_time":"2015-06-30","cost":"8.24","shipping_cost":"1.69","product_image_url":"https: //contestimg.wish.com/api/webimage/548695581c628f0f49db383a-normal.jpg","sku":"S8614110605C","refunded_by":"REFUNDEDBYMERCHANT","shipping_provider":"ChinaAirPost","order_total":"9.93","product_id":"548695581c628f0f49db383a","state":"REFUNDED","shipping":"1.99","tracking_number":"LN241291569SG","refunded_reason":"Itemdoesnotfitcustomer","quantity":"1","product_name":"FourFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithThreadsofGoldForiPadAir2-Blue","transaction_id":"558f7a0fedc5c1427bf5d23b","buyer_id":"55601c49b5ea8c1d135f3f3e"},{"sku":"CS8614090217A","shipping_provider":"ChinaAirPost","last_updated":"2015-06-29T01: 18: 24","product_id":"549269a4da081c069bf8a6ef","order_time":"2015-06-26T22: 04: 43","order_id":"558dcc7b35f5875e986535bd","price":"4.99","shipping_cost":"1.69","tracking_number":"RI524188905CN","shipping":"1.99","ShippingDetail":{"phone_number":"41504890","city":"TROMSØ","name":"BeateKristinJohansen","country":"NO","zipcode":"9019","street_address1":"Breiviklia7"},"shipped_date":"2015-06-29","state":"SHIPPED","cost":"4.24","variant_id":"549269a4da081c069bf8a6f1","order_total":"5.93","buyer_id":"554aefef6e08ac12fbfadbd5","product_image_url":"https: //contestimg.wish.com/api/webimage/549269a4da081c069bf8a6ef-normal.jpg","product_name":"SilkPULeatheriPhone6CasewithStrip-Pink","transaction_id":"558dbb9e4e4f5640d11c90fd","quantity":"1"},{"sku":"CS8614110604A","shipping_provider":"ChinaAirPost","last_updated":"2015-06-16T00: 49: 00","product_id":"547d46a4dbb0d64ac14db3c2","order_time":"2015-04-27T18: 05: 08","order_id":"553e7a545299490c24332a7c","price":"6.99","shipping_cost":"1.69","tracking_number":"RI439659948CN","shipping":"1.99","ShippingDetail":{"phone_number":"7808623549","city":"Edmonton","state":"Alberta","name":"Bantishaw","country":"CA","zipcode":"T5T3E9","street_address1":"7132178st"},"shipped_date":"2015-04-29","state":"SHIPPED","cost":"5.94","variant_id":"547d46a4dbb0d64ac14db3c4","order_total":"7.63","buyer_id":"553e40ce42686e0cc8556a18","product_image_url":"https: //contestimg.wish.com/api/webimage/547d46a4dbb0d64ac14db3c2-normal.jpg","product_name":"HighQuality360DegreeRotatingStandLeatherCaseSmartCoverForiPadAir2 WithAutomaticWake SleepFunction-White","transaction_id":"553e7a54f535ad0cb8cd4b0b","quantity":"1"},{"sku":"CS8614103008D","shipping_provider":"ChinaAirPost","last_updated":"2015-06-08T01: 11: 12","product_id":"5487bdfdc27fdc6aceafac90","order_time":"2015-06-06T04: 11: 09","order_id":"557272dde9270569eeaa36de","price":"4.99","shipping_cost":"1.69","tracking_number":"LN241092364SG","shipping":"1.99","ShippingDetail":{"phone_number":"8476680419","city":"Vernonhills","state":"Illinois","name":"Nicanorhescanilla","country":"US","zipcode":"60061","street_address1":"740northaspendrive"},"shipped_date":"2015-06-08","state":"SHIPPED","cost":"4.24","variant_id":"5487bdfdc27fdc6aceafac92","order_total":"5.93","buyer_id":"552a6e428903581c601dd492","product_image_url":"https: //contestimg.wish.com/api/webimage/5487bdfdc27fdc6aceafac90-normal.jpg","product_name":"AcrylicTwo-in-oneCaseforAppleiPhone6(4.7\")-Purple","transaction_id":"557272d60695711e43744485","quantity":"1"},{"sku":"CS8414082214D","last_updated":"2015-05-27T08: 00: 00","product_id":"5498d3475c0a25103aa662c3","order_time":"2015-05-27T01: 36: 25","order_id":"55651f993836a441079269bb","refunded_reason":"Customercancelledorder","price":"6.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"5105169996","city":"Orem","state":"Utah","name":"WilliamRBoyd","country":"US","zipcode":"84058","street_address1":"1545south240east"},"refunded_by":"REFUNDEDBYWISH","state":"REFUNDED","cost":"5.94","refunded_time":"2015-05-27","variant_id":"5498d3485c0a25103aa662c5","order_total":"7.63","buyer_id":"5485e8919398892578d317e2","product_image_url":"https: //contestimg.wish.com/api/webimage/5498d3475c0a25103aa662c3-normal.jpg","product_name":"ContrastcolorLeathercaseforLGG3D855-blue","transaction_id":"55651f972bcaad191bec90e4","quantity":"1"},{"sku":"CS8614102205D-1","shipping_provider":"ChinaAirPost","last_updated":"2015-05-26T01: 11: 24","product_id":"548e8a711e4a6e5ab52203da","order_time":"2015-05-22T11: 59: 49","order_id":"555f1a358f3e830e75448357","price":"5.99","shipping_cost":"1.69","tracking_number":"RI996769035CN","shipping":"1.99","ShippingDetail":{"phone_number":"0457632972","city":"Coolumbeach","state":"Qld","name":"DionGlasgow","country":"AU","zipcode":"4573","street_address1":"15tritoniadrive"},"shipped_date":"2015-05-26","state":"SHIPPED","cost":"5.09","variant_id":"548e8a711e4a6e5ab52203dc","order_total":"6.78","buyer_id":"54c7ceecd20c600a2190faea","product_image_url":"https: //contestimg.wish.com/api/webimage/548e8a711e4a6e5ab52203da-normal.jpg","product_name":"LeatherCaseforAppleiPhone6(4.7)withPinchecksandStandFeature-White\"","transaction_id":"555f1a353529c60f45c8a869","quantity":"1"},{"sku":"CS8514102612F","last_updated":"2015-05-10T19: 56: 44","product_id":"547fda2b205fea0f1e12e126","order_time":"2015-05-10T19: 55: 48","order_id":"554fb7c40d10960c1ee6461d","refunded_reason":"Customercancelledtheorder","price":"12.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"7729246396","city":"FortPierce","state":"Florida","name":"AaronMishler","country":"US","street_address2":"b4","street_address1":"606ixoriaave","zipcode":"34982"},"refunded_by":"CANCELLEDBYCUSTOMER","state":"REFUNDED","cost":"11.04","refunded_time":"2015-05-10","variant_id":"547fda2b205fea0f1e12e128","order_total":"12.73","buyer_id":"550ac0bae991aa0e5d7122a1","product_image_url":"https: //contestimg.wish.com/api/webimage/547fda2b205fea0f1e12e126-normal.jpg","product_name":"NewfashionedPolkaDotLeatherCaseforAppleiPhone6pluswithInteractiveViewWindowandStandFeature-Rose","transaction_id":"554fb7c4272d620cd906d2f4","quantity":"1"},{"sku":"CS8414090203E","last_updated":"2015-05-08T10: 21: 06","product_id":"549269bd18b89d7888dcdc27","order_time":"2015-05-08T09: 29: 16","order_id":"554c81ec31b276173d7aa46d","refunded_reason":"Customercancelledorder","price":"12.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"46681267","city":"ItapeciricaDaSerra","state":"SãoPaulo","name":"KrauseWGambarini","country":"BR","zipcode":"06855100","street_address1":"estradadotipiti751"},"refunded_by":"REFUNDEDBYWISH","state":"REFUNDED","cost":"11.04","refunded_time":"2015-05-08","variant_id":"549269bd18b89d7888dcdc29","order_total":"12.73","buyer_id":"554c26272ce41311e19e21b7","product_image_url":"https: //contestimg.wish.com/api/webimage/549269bd18b89d7888dcdc27-normal.jpg","product_name":"HOCOVerticalOpeniPhone6Case-Brown","transaction_id":"554c81eba599930cc9145957","quantity":"1"},{"last_updated":"2015-03-10T23: 30: 03","order_time":"2015-02-26T06: 27: 58","order_id":"54eebcee24f43817b241d17b","price":"9.99","variant_id":"548fb95f687f3f1051032e1f","ShippingDetail":{"phone_number":"3106005479","city":"SanDiego","state":"California","name":"ChristinaKantzavelos","country":"US","street_address2":"212","street_address1":"3950Cleveland","zipcode":"92103"},"shipped_date":"2015-02-27","refunded_time":"2015-03-10","cost":"8.49","shipping_cost":"1.69","product_image_url":"https: //contestimg.wish.com/api/webimage/548fb95f687f3f1051032e1d-normal.jpg","sku":"CS861410212A","refunded_by":"REFUNDEDBYWISHFORMERCHANT","shipping_provider":"USPS","order_total":"10.18","product_id":"548fb95f687f3f1051032e1d","ship_note":"eub","state":"REFUNDED","shipping":"1.99","tracking_number":"LK381594755CN","refunded_reason":"Itemdoesnotmatchthelisting","quantity":"1","product_name":"LeatherWalletCaseforAppleiPhone6(5.5)withCreditCardIDHoldersandMapPattern-Nigger-Brown\"","transaction_id":"54eebcedd9ed5e15c1fcf09d","buyer_id":"5445eaefdbf89a56f34d9a50"},{"sku":"CS8614110604E","shipping_provider":"SingaporePost","last_updated":"2015-03-10T01: 25: 53","product_id":"547d3f0591b1514439e6159a","order_time":"2015-03-07T18: 55: 35","order_id":"54fb49a7ea93bd0bfd65b955","price":"6.99","shipping_cost":"1.69","tracking_number":"RF321238607SG","shipping":"1.99","ShippingDetail":{"phone_number":"3386785584","city":"Milano","name":"SilveriAndrea","country":"IT","street_address2":"ScalaE","street_address1":"ViaPinerolo40","zipcode":"20151"},"shipped_date":"2015-03-10","state":"SHIPPED","cost":"5.94","variant_id":"547d3f0591b1514439e6159c","order_total":"7.63","buyer_id":"53d46d2db4fbc70efb71c031","product_image_url":"https: //contestimg.wish.com/api/webimage/547d3f0591b1514439e6159a-normal.jpg","product_name":"HighQuality360DegreeRotatingStandLeatherCaseSmartCoverForiPadAir6  WithAutomaticWake SleepFunction-Orange","transaction_id":"54fb49a7cfffe30cb1492cc8","quantity":"1"},{"sku":"CS8614110604C","shipping_provider":"ChinaAirPost","last_updated":"2015-03-03T03: 49: 28","product_id":"547d45fec009fc049802c0a8","order_time":"2015-03-01T15: 01: 56","order_id":"54f329e49c7a5b0c0791ac89","price":"6.99","shipping_cost":"1.69","tracking_number":"RI350192655CN","shipping":"1.99","ShippingDetail":{"phone_number":"5143712377","city":"Laval","state":"Quebec","name":"Lailalaribi","country":"CA","zipcode":"H7m5m5","street_address1":"1856delugano"},"shipped_date":"2015-03-03","state":"SHIPPED","cost":"5.94","variant_id":"547d45fec009fc049802c0aa","order_total":"7.63","buyer_id":"54786935e3e3160f1bb86005","product_image_url":"https: //contestimg.wish.com/api/webimage/547d45fec009fc049802c0a8-normal.jpg","product_name":"HighQuality360DegreeRotatingStandLeatherCaseSmartCoverForiPadAir4       WithAutomaticWake SleepFunction-Black","transaction_id":"54f329e2de20280cc90460aa","quantity":"1"},{"sku":"CS8614102005E","shipping_provider":"ChinaAirPost","last_updated":"2015-02-09T02: 21: 24","product_id":"548fb959687f3f1025032dff","order_time":"2015-02-07T23: 56: 47","order_id":"54d6a63fd9b72d09e9a07b0b","price":"9.99","shipping_cost":"1.69","tracking_number":"RI335918043CN","shipping":"1.99","ShippingDetail":{"phone_number":"0737618254","city":"VÄSTRAFRÖLUNDA","name":"RimonAskar","country":"SE","zipcode":"42148","street_address1":"Topasgatan16Lgh1302"},"shipped_date":"2015-02-09","state":"SHIPPED","cost":"8.49","variant_id":"548fb959687f3f1025032e01","order_total":"10.18","buyer_id":"54b1278ba3e48718ee94ba90","product_image_url":"https: //contestimg.wish.com/api/webimage/548fb959687f3f1025032dff-normal.jpg","product_name":"LeatherCaseforAppleiPhone6(5.5)WithStandandslideFeature-White\"","transaction_id":"54d6a63f028f040a6d7433cc","quantity":"1"},{"last_updated":"2015-02-05T03: 12: 41","order_time":"2015-02-04T00: 20: 28","order_id":"54d165cc98d24f2bfb23efb5","price":"7.69","variant_id":"547d3cefd21cd410d1d698b7","ShippingDetail":{"phone_number":"5868726801","city":"Warren","state":"Michigan","name":"DaquishaMay","country":"US","zipcode":"48089","street_address1":"12465sidonieave"},"shipped_date":"2015-02-05","cost":"6.54","shipping_cost":"1.69","product_image_url":"https: //contestimg.wish.com/api/webimage/547d3cefd21cd410d1d698b5-normal.jpg","sku":"CS8614110603K","shipping_provider":"USPS","order_total":"8.23","product_id":"547d3cefd21cd410d1d698b5","ship_note":"eub","state":"SHIPPED","shipping":"1.99","tracking_number":"LK359997482CN","quantity":"1","product_name":"TwoFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithLicheePatternForiPadAir2-Orange","transaction_id":"54d165cc2ebe260f8a47d9c4","buyer_id":"54d1513b7f6b8d1010eeb07f"},{"sku":"CS8614090213C","shipping_provider":"ChinaAirPost","last_updated":"2015-02-05T02: 24: 04","product_id":"549269b84bb5007ec6581d7f","order_time":"2015-01-30T03: 40: 28","order_id":"54cafd2c9c3dc40e017dfd55","price":"4.99","shipping_cost":"1.69","tracking_number":"RI326044262CN","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"上海","name":"邵真","country":"CN","zipcode":"200060","street_address1":"上海市普陀区胶州路1077弄5号楼201室"},"shipped_date":"2015-02-05","state":"SHIPPED","cost":"4.24","variant_id":"549269b84bb5007ec6581d81","order_total":"5.93","buyer_id":"54b87b8cdf7b87158a56bb98","product_image_url":"https: //contestimg.wish.com/api/webimage/549269b84bb5007ec6581d7f-normal.jpg","product_name":"LeopardiPhone6CasewithStandFunctionandCardHolder-White","transaction_id":"54cafd2a3548ef0edd0ccc6d","quantity":"1"},{"sku":"CS8614090212D","shipping_provider":"SingaporePost","last_updated":"2015-01-27T03: 14: 18","product_id":"549269bd18b89d788cdcdbe5","order_time":"2015-01-26T07: 11: 30","order_id":"54c5e8a273d1f3098044b20a","price":"4.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"上海","name":"邵真","country":"CN","zipcode":"200060","street_address1":"上海市普陀区胶州路1077弄5号楼201室"},"shipped_date":"2015-01-27","state":"SHIPPED","cost":"4.24","variant_id":"549269bd18b89d788cdcdbe7","order_total":"5.93","buyer_id":"54b87b8cdf7b87158a56bb98","product_image_url":"https: //contestimg.wish.com/api/webimage/549269bd18b89d788cdcdbe5-normal.jpg","product_name":"CuteiPhone6CasewithStandFunctionandCardHolder-Green","transaction_id":"54c5e8a1d34e0c4954942aea","quantity":"1"},{"last_updated":"2015-01-23T08: 42: 25","order_time":"2015-01-23T01: 40: 00","color":"red","price":"8.0","variant_id":"54869e5d223ebf6cab5d9bac","ShippingDetail":{"phone_number":"13301808059","city":"上海","name":"邵真","country":"CN","zipcode":"200060","street_address1":"上海市普陀区胶州路1077弄5号楼201室"},"refunded_by":"CANCELLEDBYCUSTOMER","cost":"6.8","shipping_cost":"1.7","product_image_url":"https: //contestimg.wish.com/api/webimage/547d40b63e46123f2ece4c8f-normal.jpg","sku":"CS86D","order_total":"8.5","product_id":"547d40b63e46123f2ece4c8f","shipping":"2.0","product_name":"TwoFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithLicheePatternForiPadAir2-Black","order_id":"54c1a6709f122e0983890bcf","state":"REFUNDED","refunded_reason":"Customercancelledtheorder","quantity":"1","refunded_time":"2015-01-23","transaction_id":"54c1a6705405c40e844e7aab","buyer_id":"54b87b8cdf7b87158a56bb98"},{"sku":"CS8614111202F","shipping_provider":"ChinaAirPost","last_updated":"2015-01-21T01:06:29","product_id":"547ca39f403d5743e332e85e","order_time":"2015-01-16T03:28:28","order_id":"54b8855cbabb19096fcc11b9","price":"6.69","shipping_cost":"1.69","tracking_number":"RF313330397SG","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"上海","name":"邵真","country":"CN","zipcode":"200060","street_address1":"上海市普陀区胶州路1077弄5号楼201室"},"shipped_date":"2015-01-21","state":"SHIPPED","cost":"5.69","variant_id":"547ca39f403d5743e332e860","order_total":"7.38","buyer_id":"54b87b8cdf7b87158a56bb98","product_image_url":"https://contestimg.wish.com/api/webimage/547ca39f403d5743e332e85e-normal.jpg","product_name":"Craquelure PU Case for Apple iPhone 6 (5.5\")withInteractiveViewWindowandStandFeature-DarkBlue","transaction_id":"54b8855bdc39aa15151d6d74","quantity":"1"},{"last_updated":"2015-01-19T03: 00: 59","order_time":"2015-01-16T05: 34: 12","order_id":"54b8a2d46217760b0a8d4ab8","price":"9.69","variant_id":"5486955812a87e2ce37e4bc3","ShippingDetail":{"phone_number":"8572666737","city":"Somerville","state":"Massachusetts","name":"ConySilva","country":"US","street_address2":"#2","street_address1":"35rossmorest","zipcode":"02143"},"shipped_date":"2015-01-19","cost":"8.24","shipping_cost":"1.69","product_image_url":"https: //contestimg.wish.com/api/webimage/5486955812a87e2ce37e4bc1-normal.jpg","sku":"S8614110603J","shipping_provider":"ChinaAirPost","order_total":"9.93","product_id":"5486955812a87e2ce37e4bc1","ship_note":"eub","state":"SHIPPED","shipping":"1.99","tracking_number":"LK338925454CN","quantity":"1","product_name":"TwoFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithLicheePatternForiPadAir2-Brown","transaction_id":"54b8a2d43c9bfa355f76638b","buyer_id":"54b16ea5763b140ffafeaea7"},{"sku":"CS8614102205A-1","last_updated":"2015-01-16T16: 19: 19","product_id":"548e8a75a5b3a01025d59244","order_time":"2015-01-15T03: 18: 05","order_id":"54b7316da47f391b8192647a","refunded_reason":"Orderissuspectedfraud","price":"5.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"上海","name":"邵真","country":"CN","zipcode":"200060","street_address1":"上海市普陀区胶州路1077弄宝华雅苑5号楼201"},"refunded_by":"REFUNDEDBYWISH","state":"REFUNDED","cost":"5.09","refunded_time":"2015-01-16","variant_id":"548e8a75a5b3a01025d59246","order_total":"6.78","buyer_id":"5472e3eb4545c40f22db07c6","product_image_url":"https: //contestimg.wish.com/api/webimage/548e8a75a5b3a01025d59244-normal.jpg","product_name":"LeatherCaseforAppleiPhone6(4.7)withPinchecksandStandFeature-Blue\"","transaction_id":"54b7316cd816e90fe49c586c","quantity":"1"},{"sku":"CS8614103009E","last_updated":"2015-01-15T16: 23: 43","product_id":"5487bd458ecdd40bace9b94a","order_time":"2015-01-13T09: 29: 48","order_id":"54b4e58c65b44510097598d0","refunded_reason":"Orderissuspectedfraud","price":"4.99","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"上海","name":"邵真","country":"CN","zipcode":"200060","street_address1":"上海市普陀区胶州路1077弄宝华雅苑5号楼201室"},"refunded_by":"REFUNDEDBYWISH","state":"REFUNDED","cost":"4.24","refunded_time":"2015-01-15","variant_id":"5487bd458ecdd40bace9b94c","order_total":"5.93","buyer_id":"54b4ba57756f8c4df9a4b13c","product_image_url":"https: //contestimg.wish.com/api/webimage/5487bd458ecdd40bace9b94a-normal.jpg","product_name":"AcrylicTwo-in-oneCaseforAppleiPhone6plus(5.5\")-Golden","transaction_id":"54b4e58c8411a7331cbedf82","quantity":"1"},{"last_updated":"2014-12-22T02: 31: 30","order_time":"2014-12-18T05: 49: 45","color":"red","price":"8.0","variant_id":"54869e5d223ebf6cab5d9bac","ShippingDetail":{"phone_number":"13928118604","city":"中山","state":"广东","name":"杨增强","country":"CN","zipcode":"528415","street_address1":"小榄镇向明大厦15楼b"},"shipped_date":"2014-12-22","cost":"6.8","shipping_cost":"1.7","product_image_url":"https: //contestimg.wish.com/api/webimage/547d40b63e46123f2ece4c8f-normal.jpg","sku":"CS86D","shipping_provider":"USPS","order_total":"17.0","product_id":"547d40b63e46123f2ece4c8f","state":"SHIPPED","shipping":"2.0","order_id":"54926af96c9c9610262eb5ed","tracking_number":"RI231499390CN","quantity":"2","product_name":"TwoFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithLicheePatternForiPadAir2-Black","transaction_id":"54926af934a56e0ff27487ca","buyer_id":"545aec5cd7e2fd5bda8ef9fa"},{"last_updated":"2014-12-22T02: 31: 29","order_time":"2014-12-18T05: 49: 45","color":"black","price":"8.0","variant_id":"5486a03fb9cb9276f9f04669","ShippingDetail":{"phone_number":"13928118604","city":"中山","state":"广东","name":"杨增强","country":"CN","zipcode":"528415","street_address1":"小榄镇向明大厦15楼b"},"shipped_date":"2014-12-22","cost":"6.8","shipping_cost":"1.7","product_image_url":"https: //contestimg.wish.com/api/webimage/547d40b63e46123f2ece4c8f-normal.jpg","sku":"CS8666F","shipping_provider":"USPS","order_total":"8.5","product_id":"547d40b63e46123f2ece4c8f","state":"SHIPPED","shipping":"2.0","order_id":"54926af96c9c9610262eb5ec","tracking_number":"RI231499388CN","quantity":"1","product_name":"TwoFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithLicheePatternForiPadAir2-Black","transaction_id":"54926af934a56e0ff27487ca","buyer_id":"545aec5cd7e2fd5bda8ef9fa"},{"last_updated":"2014-12-10T07: 15: 19","order_time":"2014-12-09T07: 49: 10","color":"black","price":"8.0","variant_id":"5486a03fb9cb9276f9f04669","ShippingDetail":{"phone_number":"13928118604","city":"中山","state":"广东","name":"杨增强","country":"CN","zipcode":"528415","street_address1":"小榄镇向明大厦15楼b"},"shipped_date":"2014-12-10","refunded_time":"2014-12-10","cost":"6.8","shipping_cost":"1.7","product_image_url":"https: //contestimg.wish.com/api/webimage/547d40b63e46123f2ece4c8f-normal.jpg","sku":"CS8666F","refunded_by":"REFUNDEDBYMERCHANT","shipping_provider":"UPS","order_total":"8.5","product_id":"547d40b63e46123f2ece4c8f","shipping":"2.0","order_id":"5486a9760df39e101eaa32e5","state":"REFUNDED","refunded_reason":"InsufficientInventory","quantity":"1","product_name":"TwoFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithLicheePatternForiPadAir2-Black","transaction_id":"5486a9746a67e014ef94a103","buyer_id":"545aec5cd7e2fd5bda8ef9fa"},{"last_updated":"2014-12-10T05: 47: 48","order_time":"2014-12-09T07: 49: 10","color":"red","price":"8.0","variant_id":"54869e5d223ebf6cab5d9bac","ShippingDetail":{"phone_number":"13928118604","city":"中山","state":"广东","name":"杨增强","country":"CN","zipcode":"528415","street_address1":"小榄镇向明大厦15楼b"},"refunded_by":"REFUNDEDBYMERCHANT","cost":"6.8","shipping_cost":"1.7","product_image_url":"https: //contestimg.wish.com/api/webimage/547d40b63e46123f2ece4c8f-normal.jpg","sku":"CS86D","order_total":"8.5","product_id":"547d40b63e46123f2ece4c8f","shipping":"2.0","product_name":"TwoFoldDesignPULeatherFlipStandTabletFullBodyProtectorCaseCoverSkinwithLicheePatternForiPadAir2-Black","order_id":"5486a9760df39e101eaa32e6","state":"REFUNDED","refunded_reason":"InsufficientInventory","quantity":"1","refunded_time":"2014-12-10","transaction_id":"5486a9746a67e014ef94a103","buyer_id":"545aec5cd7e2fd5bda8ef9fa"}]}';
		$wishOrderData = json_decode($wishOrderData,true);
		
		return self::_formatImportOrderData($wishOrderData['wishReturn'][0]);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * wish api 获取的数组 赋值到  eagle order 接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $importOrderData		eagle order 接口 的数组
	 * @param $wishOrderData		wish 数据
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 * @description			wish order  调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderData($wishOrderData){
		$importOrderData = array();
		if (!empty($wishOrderData['state'])){
			/*
			 * 	wish order state 
			 *  State 'APPROVED' means that the order is ready to ship.
			 *  State 'SHIPPED' means that the order has been marked as shipped by you.
			 *  State 'REFUNDED' means that the order has been refunded to the user and should not be fulfilled.
			 *  State 'REQUIRE_REVIEW' means that the order is under review for fraudulent activity and should not be fulfilled.
			 *  
			 *  eagle order status
			 *  100（未付款）、200（已付款）、500（已发货）、600（已取消）
			 * */
			
			$WishStateToEagleStatusMapping = array(
				'APPROVED'=> 200 , 
				'SHIPPED'=>500 , 
				'REFUNDED'=>600 , 
				'REQUIRE_REVIEW'=>100,
			);
			$importOrderData['order_status'] = $WishStateToEagleStatusMapping[$wishOrderData['state']]; 
			$importOrderData['order_source_status'] = $wishOrderData['state'];
		}
		if (isset($importOrderData['order_status'] )){
			//发货状态：1.已发货；0.未发货 #非已发货状态暂时定义为未发
			$importOrderData['shipping_status'] = (($importOrderData['order_status'] == 500)?1:0); 
		}
		
		$importOrderData['order_source'] = 'wish'; //订单来源，比如‘Wish’
		$importOrderData['order_type'] = 'wish'; //订单类型 如Wish FBA订单
		if (isset($wishOrderData['order_id'])){
			$importOrderData['order_source_order_id'] = $wishOrderData['order_id']; //订单来源的订单id
			if (empty($item['order_source_order_id']) || $item['order_source_order_id']!=  $wishOrderData['order_id']){
				$item['order_source_order_id'] = $wishOrderData['order_id']; // item 的平台 订单号用于eagle 2.0 的发货
			}
		}
		
		//wish 最后 发货期限
		//20160819 老范指出最后发货期限与wish后台不一样 由 order_time 改为 last_updated 并只有 APPROVED 的订单才同步 最后发货时间
		if (isset($wishOrderData['last_updated']) && $wishOrderData['state']== 'APPROVED' ){
			if (isset($wishOrderData['hours_to_fulfill']) ){
				$importOrderData['fulfill_deadline'] = strtotime($wishOrderData['last_updated'])+3600*$wishOrderData['hours_to_fulfill'];
			}else if (isset($wishOrderData['days_to_fulfill'])){
				$importOrderData['fulfill_deadline'] = strtotime($wishOrderData['last_updated'])+3600*24*$wishOrderData['days_to_fulfill'];
			}
		}
		
		if (isset($wishOrderData['order_source_site_id']))
			$importOrderData['order_source_site_id'] = $wishOrderData['order_source_site_id']; //订单来源平台下的站点
		
		if (isset($wishOrderData['selleruserid']))
			$importOrderData['selleruserid'] = $wishOrderData['selleruserid']; //订单来源平台卖家用户名(下单时候的用户名)
			
		if (isset($wishOrderData['saas_platform_user_id']))
			$importOrderData['saas_platform_user_id'] = $wishOrderData['saas_platform_user_id']; //订单来源平台卖家用户名(下单时候的用户名)
		
		if (isset($wishOrderData['source_buyer_user_id']))
			$importOrderData['source_buyer_user_id'] = $wishOrderData['source_buyer_user_id']; //来源买家用户名
		
		if (isset($wishOrderData['order_time']))
			$importOrderData['order_source_create_time'] = strtotime($wishOrderData['order_time']); //订单在来源平台的下单时间
		
		
		
		if (isset($wishOrderData['ShippingDetail']['name'])){
			$importOrderData['consignee'] = $wishOrderData['ShippingDetail']['name']; //收货人
			if (empty($importOrderData['source_buyer_user_id']))
				$importOrderData['source_buyer_user_id'] = $wishOrderData['ShippingDetail']['name']; //收货人
		}
		
		if (isset($wishOrderData['ShippingDetail']['zipcode']))
		$importOrderData['consignee_postal_code'] = $wishOrderData['ShippingDetail']['zipcode']; //收货人邮编
		
		if (isset($wishOrderData['ShippingDetail']['phone_number']))
		$importOrderData['consignee_phone'] = $wishOrderData['ShippingDetail']['phone_number']; //收货人电话
		
		//$importOrderData['consignee_email'] = 100; //收货人Email
		//$importOrderData['consignee_company'] = 100; //收货人公司
		if (isset($wishOrderData['ShippingDetail']['country'])){
			$importOrderData['consignee_country'] = $wishOrderData['ShippingDetail']['country']; //收货人国家名
			$importOrderData['consignee_country_code'] = $wishOrderData['ShippingDetail']['country']; //收货人国家代码
		}
		
		if (isset($wishOrderData['ShippingDetail']['city']))
		$importOrderData['consignee_city'] = $wishOrderData['ShippingDetail']['city']; //收货人城市
		if (isset($wishOrderData['ShippingDetail']['state']))
		$importOrderData['consignee_province'] = $wishOrderData['ShippingDetail']['state']; //收货人省
		
		if (isset($wishOrderData['ShippingDetail']['street_address2']))
		$importOrderData['consignee_address_line2'] = $wishOrderData['ShippingDetail']['street_address2']; //收货人地址1
		
		if (isset($wishOrderData['ShippingDetail']['street_address1']))
			$importOrderData['consignee_address_line1'] = $wishOrderData['ShippingDetail']['street_address1'];
		
			if (isset($importOrderData['order_source_create_time'] ))
		$importOrderData['paid_time'] = $importOrderData['order_source_create_time'] ; //订单付款时间
		if (isset($wishOrderData['shipped_date']))
			$importOrderData['delivery_time'] = strtotime($wishOrderData['shipped_date']); //订单发货时间
			
		
		if (isset($wishOrderData['buyer_id']))
			$importOrderData['buyer_id'] = $wishOrderData['buyer_id']; //买家id
		
		if (isset($wishOrderData['shipping_provider']))
			$importOrderData['shipping_provider'] = $wishOrderData['shipping_provider']; //物流商
		
		if (isset($wishOrderData['tracking_number']))
			$importOrderData['tracking_number'] = $wishOrderData['tracking_number']; //物流跟踪号
		
		if (isset($wishOrderData['transaction_id'])){
			$item['order_source_transactionid'] = $wishOrderData['transaction_id']; // 订单交易号
		}
		
		if (isset($wishOrderData['product_id'])){	
			$item['order_source_order_item_id']	= $wishOrderData['product_id'];
			$item['order_source_itemid']	= $wishOrderData['product_id'];
		}
		
		$item['promotion_discount'] = 0;
		if (isset($wishOrderData['shipping']))
			$item['shipping_price'] = $wishOrderData['shipping'];
			
		$item['shipping_discount'] = 0;
		if (isset($wishOrderData['sku']))
			$item['sku'] = $wishOrderData['sku'];
		if (isset($wishOrderData['price']))
			$item['price'] = $wishOrderData['price'];
		
		if (isset($wishOrderData['quantity'])){
			$item['quantity'] = $wishOrderData['quantity'];
			$item['ordered_quantity'] = $wishOrderData['quantity'];
		}else{
			$item['quantity'] = 0;
		}
		
		if (isset($item['quantity'])&&isset($item['price']) )
			$importOrderData['subtotal'] = $item['quantity']*$item['price']; //产品总价格
		else 
			$importOrderData['subtotal'] = 0;
		
		if (isset($item['quantity'])&& isset($wishOrderData['shipping']))
			$importOrderData['shipping_cost'] = $item['quantity'] * $wishOrderData['shipping']; //运费
			
		$importOrderData['discount_amount'] = 0; //折扣
		$importOrderData['commission_total'] = 0;
		if (isset($importOrderData['subtotal']) && isset($importOrderData['shipping_cost'])){
		    $importOrderData['grand_total'] = $importOrderData['subtotal'] + $importOrderData['shipping_cost'] - $importOrderData['discount_amount']; //合计金额(产品总价格 + 运费 - 折扣 = 合计金额)
		    if(isset($item['quantity'])){
		        $importOrderData['commission_total'] = $importOrderData['subtotal'] + $importOrderData['shipping_cost'] - $item['quantity'] * $wishOrderData['cost'] - $item['quantity'] * $wishOrderData['shipping_cost'];//平台佣金
		    }
		}
		
		if(!empty($wishOrderData['currency_code'])){
		    $importOrderData['currency'] = $wishOrderData['currency_code']; //货币
		}else{
			$importOrderData['currency'] = 'USD'; //货币
		}
		
		if (isset($wishOrderData['state']))
		$item['sent_quantity'] = ((strtoupper($wishOrderData['state']) == 'SHIPPED')?$item['quantity']:0);	
		if (isset($wishOrderData['product_name']))	
		$item['product_name'] = $wishOrderData['product_name'];	
		
		if (isset($wishOrderData['product_image_url']))
		$item['photo_primary'] = $wishOrderData['product_image_url'];
		
		//product_attributes  start
		$product_attributes = [];
		if (isset($wishOrderData['size'])){
			$product_attributes['size'] = $wishOrderData['size'];
		}
		
		if (isset($wishOrderData['color'])){
			$product_attributes['color'] = $wishOrderData['color'];
		}
		
		if (!empty($product_attributes)){
			$item['product_attributes'] = json_encode($product_attributes);
		}
		//product_attributes  end
		$importOrderData['items'][] = $item;
			
		if (!empty($wishOrderData['tracking_number'])){
			if (isset($wishOrderData['tracking_number']))
			$importOrderData['orderShipped'][0]['tracking_number'] = $wishOrderData['tracking_number'];
			if (isset($wishOrderData['order_id']))
			$importOrderData['orderShipped'][0]['order_source_order_id'] = $wishOrderData['order_id'];
			$importOrderData['orderShipped'][0]['order_source'] = 'wish';
			if (isset($wishOrderData['selleruserid']))
			$importOrderData['orderShipped'][0]['selleruserid'] = $wishOrderData['selleruserid'];
			$importOrderData['orderShipped'][0]['tracking_link'] = "";
			if (isset($wishOrderData['shipping_provider']))
			$importOrderData['orderShipped'][0]['shipping_method_name'] = $wishOrderData['shipping_provider'];
			$importOrderData['orderShipped'][0]['addtype'] = '平台API';
			echo " LW echo wish order tracking_number:".$wishOrderData['tracking_number']."\n";
		}
		/*
		public static $order_shipped_demo=
		[
		'order_source_order_id'=>'',//平台订单号
		'order_source'=>'',//订单来源
		'selleruserid'=>'',//卖家账号
		'tracking_number'=>'',
		'tracking_link'=>'',
		'shipping_method_name'=>'',//平台物流服务名
		'addtype'=>'平台API',//物流号来源
		];
		*/
		return $importOrderData;
	}//end of _formatImportOrderData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取某个时间之后状态变化的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $dateStart		获取订单状态变化的起始时间 
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			获取某个时间之后状态变化的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _getAllChangeOrdersSince($wish_token , $dateStart, $start=0, $limit=100, $next=''){
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $wish_token"],"edb\global");
		$reqParams['token'] = $wish_token;
		//为了避免传递过程中，url 里面的 & 符号烂了，先做url encode
		if (!empty($next))
			$next = urlencode($next);
		
		$reqParams['parms']=json_encode(array('dateSince'=>$dateStart , 'start'=>$start, 'limit'=>$limit, 'next'=>$next));
		//the proxy will auto do update if the wish product id is existing
		//default acton, using sdk for all
		$action = "getAllChangedOrdersSince";
		if ($limit > 0)
			$action = 'getChangedOrdersSinceByPagination';
		
		if (!empty($next))
			$action = 'getNextData';
		
		$retInfo= WishProxyConnectHelper::call_WISH_api($action,$reqParams );
		//check the return info
		return $retInfo;
	}//end of _getAllChangeOrdersSince
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取某个时间之后未发货的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $dateStart		获取未发货订单的起始时间 
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			获取某个时间之后未发货的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _getAllUnfulfilledOrdersSince($wish_token , $dateStart, $start=0, $limit=100){
		$timeout=120; //s
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $wish_token"],"edb\global");
		try{
			$reqParams['token'] = $wish_token;

			$reqParams['parms']=json_encode(array('dateSince'=>$dateStart , 'start'=>$start, 'limit'=>$limit));
			
			$retInfo=WishProxyConnectHelper::call_WISH_api("getAllUnfulfilledOrdersSince",$reqParams );
			//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $wish_token"],"edb\global");
		}catch(\Exception $e){
			$message = "_getAllUnfulfilledOrdersSince , $wish_token , $dateStart, $start , $limit Exception error:".$e->getMessage();
			echo $message."\n";
		}
		//check the return info
		return $retInfo;
	}//end of _getAllChangeOrdersSince

	/**
	 * 把Wish的订单信息header和items 同步到eagle1系统中user_库的od_order和od_order_item。
	 * 这里主要是通过eagle1提供的 http api的方式
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveWishOrderToOldEagle1($oneOrderReq){
 
		//1. 总的请求信息
		$reqInfo=array();
		$ordersReq=array();
		
		//yzq, 20150721, fjs 建议，e1 oms insert 不可以传入 order_source_status
		if (isset(  $oneOrderReq['order_source_status']))
			unset($oneOrderReq['order_source_status']);
		
		$ordersReq[]=$oneOrderReq;
		//$uid=$merchantidUidMap[$orderHeaderInfo["merchant_id"]];
		//其中Uid是saas_Wish_user中的uid，这里为了便于eagle的api找到合适的数据库。
		$uid=\Yii::$app->subdb->getCurrentPuid();
		
		$reqInfo[$uid]=$ordersReq;
		$reqInfoJson=json_encode($reqInfo,true);
	
		//echo "YSa before OrderHelper::importPlatformOrder info:".json_encode($reqInfo,true)."\n";
		$postParams=array("orderinfo"=>$reqInfoJson);
		//$journal_id = SysLogHelper::InvokeJrn_Create("WISH", __CLASS__, __FUNCTION__ , array('sendOrderInfoToEagle',$postParams));
		$result=EagleOneHttpApiHelper::sendOrderInfoToEagle($postParams);
		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
		//echo "YSb result:".print_r($result,true);
	
		return $result;
	}
	
	/**Auth : yzq  2015-6-2
	 * 把Wish的订单信息header和items 同步到eagle系统中user_库的od_order和od_order_item
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveWishOrderToEagle( $oneOrderReq, $eagleOrderId=-1){
		$result = ['success'=>1,'message'=>''];
		$uid=\Yii::$app->subdb->getCurrentPuid();
		
		$reqInfo[$uid]=array_merge(OrderHelper::$order_demo,$oneOrderReq);
		
		try{
			/*
			echo "<br>**********************************<br>";
			echo json_encode($reqInfo);
			echo "<br>**********************************<br>";
			*/
			$result =  OrderHelper::importPlatformOrder($reqInfo,$eagleOrderId);
		}catch(\Exception $e){
			$message = "importPlatformOrder fails.  WishId=$eagleOrderId  Exception error:".$e->getMessage();
			\Yii::error(['wish',__CLASS__,__FUNCTION__,'Background',"Step 1.5a ". $message ],"edb\global");
			echo "YS2.1 _saveWishOrderToEagle failed ".$message."\n";
			return ['success'=>1,'message'=>$message];
		}
	
		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重新统计 wish 各个订单的历史数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param na
	 +---------------------------------------------------------------------------------------------
	 * @return				na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/07/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _summaryWishOrderByNation(){
		$sql = "select country , count(1) as ct from wish_order group by country order by count(1) desc";
		$command = Yii::$app->subdb->createCommand($sql);
		
		$nationList = $command->queryAll();
		$result = [];
		foreach($nationList as &$row){
			$result[$row['country']] = $row['ct']; 
		}
		ConfigHelper::setConfig('WishOMS/nations', json_encode($result));
	}//end of _summaryWishOrderByNation
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 解锁 已经被上锁的账号
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param site_id		账号ID
	 * 		  msg			信息
	 +---------------------------------------------------------------------------------------------
	 * @return				na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/07/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function unlockWishOrderQueue($site_id , $msg='', $status='C'){
		$command = Yii::$app->db->createCommand("update saas_wish_user set order_manual_retrieve='N' ,  oq_status=:oq_status,order_manual_retrieve_message=:msg
										where site_id =:site_id  "  );
		$command->bindValue(':oq_status', $status , \PDO::PARAM_STR);
		$command->bindValue(':msg', $msg , \PDO::PARAM_STR);
		$command->bindValue(':site_id', $site_id , \PDO::PARAM_STR);
		$affectRows = $command->execute();
		return $affectRows;
	}//end of unlockWishOrderQueue
	
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
	 * log			name	date					note
	 * @author		lkh		2016/07/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMenuStatisticData($params=[],$platform='wish'){
	    $counter = OrderHelper::getMenuStatisticData($platform,$params);
	    // $OrderQuery = OdOrder::find()->where('order_source = "'.$platform.'"');
	    // if (!empty($params)){
	    // 	$OrderQuery->andWhere($params);
	    // }
	
	    // $QueryConditionList = [
	    // ////////////////
	    // //通知平台发货统计，暂时 //
	    // ////////////////
	    // //等待通知平台发货
	    // 'shipping_status_0'=>[['order_source_status'=>'WAIT_SELLER_SEND_GOODS'],['shipping_status'=>OdOrder::NO_INFORM_DELIVERY ]],
	    // //通知平台发货中
	    // 'shipping_status_2'=>['shipping_status'=>OdOrder::PROCESS_INFORM_DELIVERY],
	    // //已通知平台发货
	    // 'shipping_status_1'=>['order_source_status'=>['SELLER_PART_SEND_GOODS' , 'WAIT_BUYER_ACCEPT_GOODS']],
	    // ];
	    // foreach($QueryConditionList as $key =>$QueryCondition){
	    // 	$cloneQuery = clone $OrderQuery;
	    // 	// 查询条件只有一个 ， 或者 查询条件的第一个键值是字符串 ‘in’
	    // 	if(count($QueryCondition) == 1 ||  in_array($QueryCondition[0], ['in'])){
	    // 		$counter[$key] = $cloneQuery->andWhere($QueryCondition)->count();
	    // 	}else{
	    // 		foreach($QueryCondition as $tmpCondition){
	    // 			$cloneQuery->andWhere($tmpCondition);
	    // 		}
	    // 		//echo $cloneQuery->createCommand()->getRawSql();
	    // 		$counter[$key] =$cloneQuery->count();
	    // 	}
	    // }
	
// 	    $_SESSION['wish_oms_left_menu'] = $counter;//要改redis
	
	    return $counter;
	}//end getMenuStatisticData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 wish dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid		uid
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					WishOrderHelper::getOmsDashBoardCache($uid);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/07/22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOmsDashBoardCache($uid){
	    $platform = 'wish';
	
	    //$cacheData = \Yii::$app->redis->hget(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
		$cacheData = RedisHelper::RedisGet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData" );
	    if (!empty($cacheData))
	        return json_decode($cacheData,true);
	    else
	        return[];
	
	}//end of getOmsDashBoardCache
	
	static public function getWishCurrentOperationList($code , $type="s"){
	    $OpList = OrderHelper::getCurrentOperationList($code,$type);
	    if (isset($OpList['givefeedback'])) {
	        unset($OpList['givefeedback']);//去掉“给买家好评”
	    }
	    if (isset($OpList['signpayed'])) {
	        unset($OpList['signpayed']);//去掉“标记已付款”
	    }
	    
	    switch ($code){
	        case OdOrder::STATUS_PAY:
	            $OpList += [ 'signshipped'=>'虚拟发货(标记发货)'];
	            if (isset($OpList['signcomplete'])) {
	                unset($OpList['signcomplete']);//去掉“标记为已完成”
	            }
	            break;
// 	        case OdOrder::STATUS_WAITSEND:
// 	            $OpList += ['extendsBuyerAcceptGoodsTime'=>'延长买家收货时间', 'signshipped'=>'虚拟发货(标记发货)'];
// 	            break;
// 	        case OdOrder::STATUS_SHIPPED:
// 	            $OpList += ['extendsBuyerAcceptGoodsTime'=>'延长买家收货时间' , 'signshipped'=>'虚拟发货(标记发货)'];
// 	            break;
	    }
	    if ($type =='s')
	        $OpList += ['invoiced' => '发票'];
	    return $OpList;
	}//end of getWishCurrentOperationList
	
	/**
	 * 获取用户绑定的速卖通账号的异常情况，如token过期之类
	 * @param int $uid
	 */
	public static function getUserAccountProblems($uid){
	    if(empty($uid))
	        return [];
	
	    $WishAccounts = SaasWishUser::find()->where(['uid'=>$uid])->asArray()->all();
	    if(empty($WishAccounts))
	        return [];
	
	    $problems = [];
	    //         $accountUnActive = [];//未开启同步的账号
	    //         $tokenExpired = [];//授权失败的账号
	    //         $order_retrieve_errors = [];//获取订单失败
	    //         $initial_order_failed = [];//首次绑定时，获取订单失败
	    foreach ($WishAccounts as $account){
	        $accountProblems = [];
	        if(!empty($account["expiry_time"])&&$account["expiry_time"] < date('Y-m-d H:i:s',time())){
	            $accountProblems["store_name"] = $account["store_name"];
	            $accountProblems["is_timeout"] = true;
	        }
	        if($account['order_retrieve_message'] != ''){
	            $accountProblems["store_name"] = $account["store_name"];
	            $accountProblems["order_retrieve_message"] = $account['order_retrieve_message'];
	        }
	        if($account['is_active'] == 0){
	            $accountProblems["store_name"] = $account["store_name"];
	            $accountProblems["is_active"] = $account['is_active'];
	        }
	        if(!empty($accountProblems)){
	            $problems[] = $accountProblems;
	        }
	    }
	    //         $problems=[
	    //             'unActive'=>$accountUnActive,
	    //             'token_expired'=>$tokenExpired,
	    //             'initial_failed'=>$initial_order_failed,
	    //             'order_retrieve_failed'=>$order_retrieve_errors,
	    //         ];
	    
	    return $problems;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 wish dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					WishOrderHelper::UserAliexpressOrderDailySummary($start_time);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/08/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOmsDashBoardData($uid,$isRefresh =false){
	    $platform = 'wish';
	
	    //检查是否有缓存 数据， 有则直接读取缓存数据， 没有则重新生成 oms dash board 数据并保存到缓存中
	    //$cacheData = \Yii::$app->redis->hget(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
		$cacheData = RedisHelper::RedisGet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
	    $createTime = time();
	
	    if (!empty($cacheData)) $cacheData = json_decode($cacheData,true);
	
	    if (!empty($cacheData['createTime'])){
	        //若cache 数据超过4小时，则清除cache
	        if (($cacheData['createTime']+60*60*4 )<$createTime){
	            $cacheData = [];
	        }
	    }
	
	    if (empty($cacheData) || $isRefresh){
	        $cacheData =[];
	        $cacheData['order_count'] = WishOrderHelper::getChartDataByUid_Order_Wish($uid, 10);//订单数量统计
	        //$chartData['profit_count'] = CdiscountOrderInterface::getChartDataByUid_Profit($uid,10);//oms 利润统计 aliexpress 没有先屏蔽
	        $cacheData['advertData'] = OrderBackgroundHelper::getAdvertDataByUid($uid,2,$platform); // 获取OMS dashboard广告
	        $cacheData['reminderData'] = OrderBackgroundHelper::getOMSReminder($platform,$uid);
	        $cacheData['createTime'] = $createTime;
	        	
	        $cacheData_json = json_encode($cacheData);
	        	
	        //\Yii::$app->redis->hset(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData",$cacheData_json);
	        RedisHelper::RedisSet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData",$cacheData_json);
	    }
	    return $cacheData;
	}//end of getOmsDashBoardData
	
	static public function getChartDataByUid_Order_Wish($uid ,$days ){
	    //获取所有aliexpress 绑定有效的账号
	    $accounts = SaasWishUser::find()->select(['uid', 'username'=>'store_name'])->where(['is_active'=>1 ,'uid'=>$uid ])->asArray()->all();
	    $platform = 'wish';
	    return OrderBackgroundHelper::getChartDataByUid_Order($uid ,$days , $platform, $accounts);
	}//end of UserAliexpressOrderDailySummary
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * wish 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $status					指定状态筛选 （可选）
	 * @param $lasttime 				指定时间筛选 （可选）
	 +---------------------------------------------------------------------------------------------
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderSyncInfoDataList($status = '' , $lasttime =''){
	    $AccountList = WishAccountsApiHelper::listActiveAccounts();
	    $syncList = [];
// 	    $model = new SaasWishAutosync();
	    foreach($AccountList as $account){
	        if ($account['last_order_success_retrieve_time'] !='0000-00-00 00:00:00'||$account['last_order_success_retrieve_time'] != ''){
// 	            $detail = WishAccountsApiHelper::getLastOrderSyncDetail($account['store_name']);
	                //状态过滤
// 	                if ($status !=''){
// 	                    //如果status 是有效的值， 则表示用户使用了过滤
// 	                    if ( $account['status'] != $status) {
// 	                        continue;
// 	                    }
// 	                }//状态过滤
	                	
	                //时间 过滤
	                if  ($lasttime != ""){
	
	                    if ( $account['last_order_success_retrieve_time'] > $lasttime) {
	                        continue;
	                    }
	                }//时间 过滤
	                	
	                $syncList[$account['store_name']]['last_order_success_retrieve_time'] = $account['last_order_success_retrieve_time'];
	                $syncList[$account['store_name']]['oq_status'] = $account['oq_status'];
	                $syncList[$account['store_name']]['order_retrieve_message'] = $account['order_retrieve_message'];
	        }else{
	             if ($status == '' )
// 	                    $syncList[$account['store_name']] = $model->attributes;
	                 $syncList[$account['store_name']]['last_order_success_retrieve_time'] = '';
	                 $syncList[$account['store_name']]['oq_status'] = '';
	                 $syncList[$account['store_name']]['order_retrieve_message'] = '';
	        }
	
	    }
	    return $syncList;
	}//end of getOrderSyncInfoDataList
	
}
