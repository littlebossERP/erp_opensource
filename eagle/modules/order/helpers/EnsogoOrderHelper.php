<?php
namespace eagle\modules\order\helpers;
use yii;
use yii\data\Pagination;

use eagle\models\SaasEnsogoUser;
use eagle\modules\listing\models\EnsogoApiQueue;
use eagle\modules\listing\models\EnsogoFanben;
use eagle\modules\listing\models\EnsogoFanbenVariance;
use eagle\modules\order\models\EnsogoOrder;
use eagle\modules\order\helpers\OrderHelper;

use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\models\SysCountry;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\GetControlData;

use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\SysLogHelper;
use common\api\ensogointerface\EnsogoProxyConnectHelper;
use Qiniu\json_decode;
use eagle\modules\order\models\OdOrder;



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
 * ensogo订单模板业务
 +------------------------------------------------------------------------------
 * @category	order
 * @package		Helper/order
 * @subpackage  Exception
 * @author		lkh
 +------------------------------------------------------------------------------
 */
class EnsogoOrderHelper {
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 发送对ensogo订单（发货， 退货/取消 ， 修改发货信息三类）操作的请求
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $order_id		需要调用ensogo api 的订单号（eagle订单号） 
	 * @param $action_type		ensogo api 的类型 ：order_ship ， order_cancel ， order_modify 暂时三种
	 * @param $site_id		ensogo 账号 id
	 * @param $params		对应api需要的参数
	 +---------------------------------------------------------------------------------------------
	 * @description			$params 格式说明
	 * 						order_ship : array('id'=>平台订单号 (require)， 
	 * 											'shipping_provider'=>发货方式 (require)
	 * 											'tracking_number'=>快递号码(optional)
	 * 											'ship_note'=>备注(optional))
	 * 
	 * 						order_cancel: array('id'=>平台订单号 (require)， 
	 * 											'reason_code'=>退货代码  ， 
	 * 											'reason_note'=>(optional) 当reason_code = -1  为require)
	 * 
	 * 						order_modify ： array('id'=>平台订单号 (require)， 
	 * 											'shipping_provider'=>发货方式 (require)
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
		
		$inQueueItemModels = EnsogoApiQueue::find()->andWhere(['uid'=>$uid,
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
		$inQueueItem = new EnsogoApiQueue();

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
			OperationLogHelper::saveOperationLog('order',$order_id,"Ensogo  ".$params['order_id']." 已经".$opetaionLeablMapping[$action_type]);
			
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
	 * @param $order_id		需要调用ensogo api 的订单号（eagle订单号） 
	 * @param $action_type		ensogo api 的类型 ：order_ship ， order_cancel ， order_modify 暂时三种
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

		$inQueueItems = EnsogoApiQueue::find()->andWhere(['uid'=>$uid,'status'=>'pending','action_type'=>$action_type_filter,'fanben_order_id' =>$order_id])
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
	 * 后台执行ensogo 订单操作类型
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
	public static function cronQueueHandlerExecuteEnsogoOrderOp(){
		$rtn['message']="";
		$rtn['success'] = true;
		$action_type_filter =array('order_ship' , 'order_cancel' , 'order_modify');	

		$SAAS_api_requests = EnsogoApiQueue::find()->andWhere([ 'status'=>'pending','action_type'=>$action_type_filter ])
						->limit(30)->orderBy('timerid  asc')->all();
		
		//\Yii::info(['ensogo',__CLASS__,__FUNCTION__,'Background',"Step 0 starting Ensogo ,Queue Depth:".count($SAAS_api_requests)],"edb\global");
				
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
			 
				//step 2: Load the Ensogo access info, token, etc
				$EnsogoShopModel = SaasEnsogoUser::findOne($SAAS_api_request->site_id);
				if ($EnsogoShopModel == null){					
					\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"Step 1.5","Failed to Load ensogo token for ".$SAAS_api_request->site_id],"edb\global");
					//异常情况
					$SAAS_api_request->status= "failed";
					$SAAS_api_request->message="Failed to Load ensogo token for ".$SAAS_api_request->site_id;
					$SAAS_api_request->save();
					continue;
				}
				 

				//step 3: Load the FanBen in User X db
				 
		
				//Step 4: call Proxy to do the request
				$rtn ['token'] = $EnsogoShopModel->token;
				$rtn ['action_type'] = $SAAS_api_request->action_type;
				$rtn ['params'] = $params;
				//return $rtn;
				$rtn =self::_CallEnsogoApiByActionType($EnsogoShopModel->token,$SAAS_api_request->action_type ,$params);
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
	}// end of cronAutoExecuteEnsogoOrderOp
	
	 
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
		GLOBAL $HANDLED_ENSOGO_ORDER;
		try {
			
			$SAASENSOGOUSERLIST = SaasEnsogoUser::find()->where("is_active='1' and initial_fetched_changed_order_since is null 
						or initial_fetched_changed_order_since='0000-00-00 00:00:00'")->all();
				
			//retrieve orders  by  each ensogo account
			foreach($SAASENSOGOUSERLIST as $ensogoAccount ){
				$uid = $ensogoAccount['uid'];
				//flush this remember tool first
				$HANDLED_ENSOGO_ORDER = array();
		
				echo " YS1 start to fetch for unfuilled uid=$uid ... \n";
				if (empty($uid)){
				//异常情况
					$message = "site id :".$ensogoAccount['site_id']." uid:0";
					echo $message;
							\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
					return false;
				}
		
		 
		
				$updateTime =  TimeUtil::getNow();
				//update this ensogo account as last order retrieve time
				$ensogoAccount->last_order_retrieve_time = $updateTime;

				//************************************************************
				//初始化该用户30天内所有unchanged order 拿下来
				//1) 如果现在是 00:00 - 7:59 ，获取30天内的changed order，而且循环获取，每次7天
				//************************************************************
				// get ensogo order which state has changed
				$nowHour = substr(TimeUtil::getNow(),11,2); //2014-01-20 20:10:20
				$ensogoAccount1 = $ensogoAccount;
				$ensogoAccount1['initial_fetched_changed_order_since'] = date("Y-m-d\TH:i:s" ,strtotime(TimeUtil::getNow())-3600*8);
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
				//因为ensogo sdk 做了分页获取然后所有order 一起发下来，所以我们不需要做分页了
				//如果下载要继续获取的since time 是 该注册帐号的 注册日期 30天内，就获取，否则不需要了
				//if ($sinceTimeUTC >=  substr(date('Y-m-d H:i:s',strtotime($ensogoAccount['create_time'] )-3600*24*30 ),0,10 ) ){
				echo TimeUtil::getNow()." start to initial $uid changed order for ".$ensogoAccount['store_name']." since $sinceTimeUTC \n"; //ystest
				$getOrderCount = 0;
				$starttime = TimeUtil::getNow();
				$starti = 0;
				$limit = 200;
				$next = '';
				//因为ensogo sdk 做了分页获取然后所有order 一起发下来，所以我们不需要做分页了
				do {//this api get proxy, using pagination
					$insertEnsogoReturn['updated_count'] = 0;
					$insertEnsogoReturn['inserted_count'] = 0;
					if (!empty($orders['proxyResponse']['data']['paging']['next']))
						$next = $orders['proxyResponse']['data']['paging']['next'];
							
					$orders = self::_getAllChangeOrdersSince($ensogoAccount['token'] , $sinceTimeUTC ,$starti,$limit,$next);//UTC time is -8 hours
						
					
					echo "<br>*************************************************<br>";
					echo json_encode($orders);
					echo "<br>*************************************************<br>";
					
					//fail to connect proxy
					if (empty($orders['success']) or !$orders['success']){
						\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"Error 1.01 fail to connect proxy  :".$orders['message']],"edb\global");
						$ensogoAccount->save();
						
						continue;
					}
					echo TimeUtil::getNow()." got results, start to insert oms \n"; //ystest
					//accroding to api respone  , update the last retrieve order time and last retrieve order success time
					if (!empty ($orders['proxyResponse']['success'])){
						//sync ensogo info to ensogo order table
						if(!empty($orders['proxyResponse']['data'])){
							//insert ensogo order
							$insertEnsogoReturn = self::_InsertEnsogoOrder($orders , $ensogoAccount);
						}//end of ensogoReturn empty or not
						$ensogoAccount->last_order_success_retrieve_time = $updateTime;
					}else{
						if (!empty ($orders['proxyResponse']['message'])){
							\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"Proxy Error 1.0 proxy error  :".$orders['proxyResponse']['message']],"edb\global");
						}else{
							\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"Proxy Error 1.0 proxy error  : not any respone message"],"edb\global");
						}
					}

					if (!empty($orders['proxyResponse']['data']['paging']['next']))
						echo "ready to get next page ". print_r($orders['proxyResponse']['data']['paging']['next'],true) ." \n";
					else
						echo "No next page \n";

					if ($insertEnsogoReturn['updated_count'] ==0 and $insertEnsogoReturn['inserted_count'] ==0)
							echo "updated count and inserted cout is ZERO, so not to have next page \n";
					else
						echo "updated count = ".$insertEnsogoReturn['updated_count']." and inserted cout is ".
					$insertEnsogoReturn['inserted_count'].", so will have next page \n";

				//如果没有返回next ， 或者 inserted 和 updated order 都为0，就不要继续做下去了，因为已经到了 上次获取的边界值，继续获取下去就重复了
				}while( !empty($orders['proxyResponse']['data']['paging']['next']) and isset($insertEnsogoReturn['success']) and $insertEnsogoReturn['success']==true);
					//do for all orders returns, use cache to see whether has been integrated.
					//and ($insertEnsogoReturn['updated_count'] + $insertEnsogoReturn['inserted_count']) > 0
					$ensogoAccount->initial_fetched_changed_order_since = $sinceTimeUTC;

					if (empty($ensogoAccount->routine_fetched_changed_order_from) or $ensogoAccount->routine_fetched_changed_order_from == '0000-00-00 00:00:00')
						$ensogoAccount->routine_fetched_changed_order_from = $sinceTimeUTC;

					if (!empty ($orders['proxyResponse']['message']) and 'Done With http Code 200' <>$orders['proxyResponse']['message']){
						$ensogoAccount->order_retrieve_message = $orders['proxyResponse']['message'];
					}else{
						$ensogoAccount->order_retrieve_message = '';//to clear the error msg if last attemption got issue
							
						if (!empty($insertEnsogoReturn['message'])){
							$ensogoAccount->order_retrieve_message = $insertEnsogoReturn['message'];
							$ensogoAccount->initial_fetched_changed_order_since = '0000-00-00 00:00:00';
						}
						else{
							$ensogoAccount->last_order_success_retrieve_time = $sinceTimeUTC; //NOT　UTC time
							$ensogoAccount->last_order_retrieve_time = $sinceTimeUTC; //NOT　UTC time
						}
					}
					if (!$ensogoAccount->save()){
						\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"failure to save ensogo operation info ,uid:".$ensogoAccount['uid']."error:". print_r($ensogoAccount->getErrors(),true)],"edb\global");
					}
					self::_summaryEnsogoOrderByNation();
				}//end of each ensogo user account
			} catch (\Exception $e) {
				echo "Failed to retrieve order :".$e->getMessage();
				\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
			}
		
	}//end of 新帐号绑定 初始化
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 手动同步订单(ensogo订单抓取的插队队列)
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
	static public function cronManualRetrieveEnsogoOrder(){
		try {
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_ensogo_user set last_order_manual_retrieve_time='0000-00-00 00:00:00'
										where last_order_manual_retrieve_time is null is null"  );
			$affectRows = $command->execute();
			
			//1.账号启用;2.手动同步标示开启;3.没有上锁
			$SAASENSOGOUSERLIST = SaasEnsogoUser::find()->where("is_active='1' and order_manual_retrieve='Y'   and
				 ifnull(oq_status,'') <>'S' ")->orderBy("last_order_success_retrieve_time asc")->all();

			//retrieve orders  by  each ensogo account
			self::_retrieveOrderInfoByAccount($SAASENSOGOUSERLIST,'MJQ');
		} catch (\Exception $e) {
			echo "Failed to  retrieve order :".$e->getMessage();
			\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
	}//end of cronManualRetrieveEnsogoOrder
	
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
	public static function cronAutoFetchRecentChangedOrder(){
		GLOBAL $HANDLED_ENSOGO_ORDER;
		try {
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_ensogo_user set last_order_success_retrieve_time='0000-00-00 00:00:00',last_order_retrieve_time='0000-00-00 00:00:00'  
										where last_order_success_retrieve_time is null or last_order_retrieve_time is null"  );
			$affectRows = $command->execute();
			
			$SAASENSOGOUSERLIST = SaasEnsogoUser::find()->where("is_active='1' and initial_fetched_changed_order_since is not null and
					last_order_success_retrieve_time<'$half_hours_ago'")->orderBy("last_order_success_retrieve_time asc")->all();
			
			echo SaasEnsogoUser::find()->where("is_active='1' and initial_fetched_changed_order_since is not null and last_order_success_retrieve_time<'$half_hours_ago'")->orderBy("last_order_success_retrieve_time asc")->createCommand()->getRawSql();
			
			self::_retrieveOrderInfoByAccount($SAASENSOGOUSERLIST,'OQ');
		} catch (\Exception $e) {
			echo "Failed to  retrieve order :".$e->getMessage();
			\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
			
	} // end of cronAutoFetchUnFulfilledOrderList
	
	/*
	 * 这个是遍历所有user，看看他们在 specified period 里面，所有Ensogo订单总数以及销售总金额
	*
	*  Author: yzq
	*  date: 2016-3-11
	*  
	* */	
	public static function calcuateSales($fromDate, $endDate){
		$rtn['message'] = '';
		$rtn['success'] = true;
		$rtn['total_order_count'] = 0;
		$rtn['total_order_amount'] = 0;
		$rtn['detail'] = [];
		$SAASENSOGOUSERLIST = SaasEnsogoUser::find()->orderBy("uid")->all();
		$puid0 = 0;
		foreach ($SAASENSOGOUSERLIST as $aEnsogoShop){
			$puid = $aEnsogoShop->uid;
			 
			
			$puid0 = $puid;
			$query = "SELECT count(*) ct, sum(order_total) total_amount FROM `ensogo_order` ";
			$command = Yii::$app->subdb->createCommand($query);
			$rec = $command->queryOne();
			$rtn['total_order_count'] += $rec['ct'];
			$rtn['total_order_amount'] += $rec['total_amount'];
			
			if ($rec['ct'] > 0){
				$rtn['detail']['puid '.$puid]['order_count'] =  $rec['ct'];
				$rtn['detail']['puid '.$puid]['order_amount'] =  $rec['total_amount'];
			}
		}//end of each ensogo shop	
		return $rtn;
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 将获取订单的逻辑封装， 方便自动同步队列与手动同步队列
	 * +---------------------------------------------------------------------------------------------
	 * ription			ensogo order 调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 * +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * 
	 * @access static
	 *+---------------------------------------------------------------------------------------------
	 * @param $QueueName ensogo
	 * 抓取队列名称 （OQ为正常订单队列，MJQ为手动同步队列）
	 * @param $SAASENSOGOUSERLIST ensogo
	 *        	model
	 *+---------------------------------------------------------------------------------------------
	 * @return array
	 * @author lkh		2015/11/04				初始化
	 * +---------------------------------------------------------------------------------------------
	 *        
	 */
	private static function _retrieveOrderInfoByAccount(&$SAASENSOGOUSERLIST, $QueueName = 'OQ') {
		foreach ( $SAASENSOGOUSERLIST as $ensogoAccount ) {
			// ************************************************************
			// ********* start to check account locked or not ********
			// ************************************************************
			// 检查ensogo 该账号是否上锁了
			$affectRows = 0; // 重置影响数量
			                 // OQ Order Queue
			$command = Yii::$app->db->createCommand ( "update saas_ensogo_user set oq_status='S',oq_lockedby='$QueueName'
					where site_id = '" . $ensogoAccount ['site_id'] . "' and ifnull(oq_status,'') <>'S' " );
			$affectRows = $command->execute ();
			echo '\n ' . $ensogoAccount ['site_id'] . "=" . $affectRows; // testkh
			if (empty ( $affectRows )) {
				continue; // 没有成功修改的则修改下一条
			}
			
			// ************************************************************
			// ********* end of check account locked or not **********
			// ************************************************************
			$uid = $ensogoAccount ['uid'];
			// flush this remember tool first
			$HANDLED_ENSOGO_ORDER = array ();
			
			echo " YS1 start to fetch for unfuilled uid=$uid ... \n";
			if (empty ( $uid )) {
				// 异常情况
				$message = "site id :" . $ensogoAccount ['site_id'] . " uid:0";
				echo $message;
				\Yii::error ( [ 
						'ensogo',
						__CLASS__,
						__FUNCTION__,
						'Background',
						$message 
				], "edb\global" );
				// 解锁账号
				self::unlockEnsogoOrderQueue ( $ensogoAccount ['site_id'], $message ,'F');
				return false;
			}
			
			 
			
			// ************************************************************
			// ********* start to check token expiry or not **********
			// ************************************************************
			/*test kh
			try {
				
				$ExpiryOrNot = EnsogoProxyConnectHelper::checkEnsogoTokenExpiryOrNot ( $ensogoAccount ['site_id'] );
			} catch (\Exception $e) {
				echo "Failed to  check ensogo token:".$e->getMessage();
				\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"uid check ensogo token  :".$e->getMessage()],"edb\global");
				// 解锁账号
				self::unlockEnsogoOrderQueue ( $ensogoAccount ['site_id'] , 'token Expiry Exception' ,'F');
			}
			
			if ($ExpiryOrNot ['success'] == false) {
				// manual input token
				if (!empty($ExpiryOrNot['message']))
					$ensogoAccount->order_retrieve_message = $ExpiryOrNot['message'];
				else
					$ensogoAccount->order_retrieve_message = '授权失败';
				
				$ensogoAccount->save();
				// 解锁账号
				self::unlockEnsogoOrderQueue ( $ensogoAccount ['site_id'], 'token Expiry' ,'F');
				continue;
			} else {
				// check access token
				if (! empty ( $ExpiryOrNot ['access_token'] )) {
					// refresh access token
					$ensogoAccount ['token'] = $ExpiryOrNot ['access_token'];
				}
			}
			test kh*/
			// ************************************************************
			// ********* end of check token expiry or not ***********
			// ************************************************************
			echo " YS2 before get order ... \n";
			$updateTime = TimeUtil::getNow ();
			
			if ($QueueName == "OQ") {
				$ensogoAccount->last_order_retrieve_time = $updateTime;
				$ensogoAccount->order_retrieve_message = ''; // to clear the error msg if last attemption got issue
				self::unlockEnsogoOrderQueue ( $ensogoAccount ['site_id']);//后台自动同步订单的情况，提前unlock，避免后面中途出现问题导致最后unlock被忽略执行
			} elseif ($QueueName == "MJQ") {
				$ensogoAccount->last_order_manual_retrieve_time = $updateTime;
				$ensogoAccount->order_manual_retrieve_message = ''; // to clear the error msg if last attemption got issue
			}
			
			$getOrderCount = 0;
			
			$starti = 0;
			$limit = 200; // 因为ensogo sdk 做了
			              
			// get ensogo order which state has changed
			if (empty ( $ensogoAccount ['last_order_success_retrieve_time'] ) or $ensogoAccount ['last_order_success_retrieve_time'] == '0000-00-00 00:00:00') {
				// 如果还没有初始化完毕，就什么都不do(重置init时间，让new account job可以重新执行)
				$ensogoAccount->order_retrieve_message = 'Waiting for init';
				$ensogoAccount->initial_fetched_changed_order_since = '0000-00-00 00:00:00';
				$ensogoAccount->save();
				// 解锁账号
				self::unlockEnsogoOrderQueue ( $ensogoAccount ['site_id'], 'Waiting for init' ,'F');
				continue;
			} else {
				$nextTimeDateSince = date ( "Y-m-d\TH:i:s", strtotime ( $updateTime ) - 3600 * 8 ); // now, UTC time, is next fetch from
				
				$sinceTimeUTC = date ( "Y-m-d\TH:i:s", strtotime ( $ensogoAccount ['last_order_success_retrieve_time'] ) - 3600 * 8 ); // convert to UTC time
				$sinceTimeUTC30DaysAgo = date ( "Y-m-d\TH:i:s", strtotime ( $ensogoAccount ['last_order_success_retrieve_time'] ) - 3600 * 24 * 30 ); // convert to UTC time
				                                                                                                                                    
				// ************************************************************
				/* 2016-01-20 不建议做这个 start                                                                                                                                 // ************************************************************
				echo TimeUtil::getNow () . " start to get $uid all unfuilled order for " . $ensogoAccount ['store_name'] . " since $sinceTimeUTC30DaysAgo \n"; // ystest
				
				try {
					$orders = self::_getAllUnfulfilledOrdersSince ( $ensogoAccount ['token'], $sinceTimeUTC30DaysAgo, 0, - 1 ); // UTC time is -8 hours
				} catch (\Exception $e) {
					echo "Failed to  get $uid all unfuilled order :".$e->getMessage();
					\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"uid $uid all unfuilled order  :".$e->getMessage()],"edb\global");
					// 解锁账号
					self::unlockEnsogoOrderQueue ( $ensogoAccount ['site_id'] , 'get unfuilled order Exception' ,'F');
				}
				
				if (! empty ( $orders ['proxyResponse'] ['success'] )) {
					// sync ensogo info to ensogo order table
					if (! empty ( $orders ['proxyResponse'] ['data'] )) {
						// insert ensogo order
						$insertEnsogoReturn = self::_InsertEnsogoOrder ( $orders, $ensogoAccount );
					} // end of ensogoReturn empty or not
					if (! empty ( $insertEnsogoReturn ['message'] ))
						$ensogoAccount->order_retrieve_message = $insertEnsogoReturn ['message'];
				}
				2016-01-20 不建议做这个 end*/
				// ************************************************************
				// start to get changed order, 日常周期调用的逻辑
				// ************************************************************
				/*
				 * 对于获取近期改动过的订单，saas user 表需要记录以下字段 1. initial_fetched_changed_order_since: 2015-7-24 表示从 1 到 2 这些时间 （整个date）的order changed order 都已经拿回来并且inserted OMS 2. routine_fetched_changed_order_from:2015-7-14T14:25:25 表示上次日常是从这个时间拿到现在的，changed order
				 */
				//
				// 因为ensogo sdk 做了分页获取然后所有order 一起发下来，所以我们不需要做分页了
				/*20160121分页拿单start 
				echo TimeUtil::getNow () . " start to get $uid changed order for " . $ensogoAccount ['store_name'] . " since $sinceTimeUTC \n"; // ystest
				$getOrderCount = 0;
				
				$starti = 0;
				$limit = 100;
				
				// 因为ensogo sdk 做了分页获取然后所有order 一起发下来，所以我们不需要做分页了
				try {
					$orders = self::_getAllChangeOrdersSince ( $ensogoAccount ['token'], $sinceTimeUTC, $starti, - 1 ); // UTC time is -8 hours
				} catch (\Exception $e) {
					echo "Failed to  get $uid all Change order :".$e->getMessage();
					\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"uid $uid all Change order  :".$e->getMessage()],"edb\global");
							// 解锁账号
					self::unlockEnsogoOrderQueue ( $ensogoAccount ['site_id'] , 'get Change order Exception','F' );
				}
				
									
					                                                                                           
				// fail to connect proxy
				if (empty ( $orders ['success'] ) or ! $orders ['success']) {
					self::unlockEnsogoOrderQueue ( $ensogoAccount ['site_id'], "Error 1.01 fail to connect proxy  :" . $orders ['message'] ,'F');
					\Yii::error ( [ 
							'ensogo',
							__CLASS__,
							__FUNCTION__,
							'Background',
							"Error 1.01 fail to connect proxy  :" . $orders ['message'] 
					], "edb\global" );
					$ensogoAccount->save ();
					// 解锁账号
					
					continue;
				}
				echo TimeUtil::getNow () . " got results, start to insert oms \n"; // ystest
				                                                                   // accroding to api respone , update the last retrieve order time and last retrieve order success time
				if (! empty ( $orders ['proxyResponse'] ['success'] )) {
					// sync ensogo info to ensogo order table
					if (! empty ( $orders ['proxyResponse'] ['data'] )) {
						// insert ensogo order
						$insertEnsogoReturn = self::_InsertEnsogoOrder ( $orders, $ensogoAccount );
					} // end of ensogoReturn empty or not
				} else {
					if (! empty ( $orders ['proxyResponse'] ['message'] )) {
						\Yii::error ( [ 
								'ensogo',
								__CLASS__,
								__FUNCTION__,
								'Background',
								"Proxy Error 1.0 proxy error  :" . $orders ['proxyResponse'] ['message'] 
						], "edb\global" );
					} else {
						\Yii::error ( [ 
								'ensogo',
								__CLASS__,
								__FUNCTION__,
								'Background',
								"Proxy Error 1.0 proxy error  : not any respone message" 
						], "edb\global" );
					}
				}
				
				20160121分页拿单end*/
				
				
				
			} // end of routine fetch changed order from last update time
			
			
			//ensogo api 没有sdk 所以 需要手动分页获取全部订单
			self::_getEnsogoOrderByPagination($ensogoAccount,$sinceTimeUTC);
			
			self::_summaryEnsogoOrderByNation ();
			
			// 解锁账号
			self::unlockEnsogoOrderQueue ( $ensogoAccount ['site_id']);
		}//end of each ensogo user account
	}//end of _retrieveOrderInfoByAccount
	
	private static function _getEnsogoOrderByPagination(&$ensogoAccount,$sinceTimeUTC){
		$getOrderCount = 0;
		$starttime = TimeUtil::getNow();
		$starti = 0; $limit = 100;
		$next = '';
		//因为ensogo 分页获取然后所有order 一起发下来
		do {//this api get proxy, using pagination
			$updateTime = TimeUtil::getNow ();
			$insertEnsogoReturn['updated_count'] = 0;
			$insertEnsogoReturn['inserted_count'] = 0;
			if (!empty($orders['proxyResponse']['data']['paging']['next']))
				$next = $orders['proxyResponse']['data']['paging']['next'];
				
			$orders = self::_getAllChangeOrdersSince($ensogoAccount['token'] , $sinceTimeUTC ,$starti,$limit,$next);//UTC time is -8 hours
				
				
			echo "<br>*************************************************<br>";
			echo json_encode($orders);
			echo "<br>*************************************************<br>";
				
			//fail to connect proxy
			if (empty($orders['success']) or !$orders['success']){
				\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"Error 1.01 fail to connect proxy  :".$orders['message']],"edb\global");
				$ensogoAccount->save();
		
				continue;
			}
			echo TimeUtil::getNow()." got results, start to insert oms \n"; //ystest
			//accroding to api respone  , update the last retrieve order time and last retrieve order success time
			if (!empty ($orders['proxyResponse']['success'])){
				//sync ensogo info to ensogo order table
				if(!empty($orders['proxyResponse']['data'])){
					//insert ensogo order
					$insertEnsogoReturn = self::_InsertEnsogoOrder($orders , $ensogoAccount);
				}//end of ensogoReturn empty or not
				$ensogoAccount->last_order_success_retrieve_time = $updateTime;
				$ensogoAccount->order_retrieve_message = '';
			}else{
				
				echo '\n'.$starttime." error return :".json_encode($orders).'\n';
				if (!empty ($orders['proxyResponse']['message'])){
					$ensogoAccount->order_retrieve_message = $orders['proxyResponse']['message'];
					\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"Proxy Error 1.0 proxy error($starttime)  :".$orders['proxyResponse']['message']],"edb\global");
				}else{
					\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"Proxy Error 1.0 proxy error($starttime)  : not any respone message"],"edb\global");
				}
			}
			
			if (! $ensogoAccount->save ()) {
				\Yii::error ( ['ensogo',__CLASS__,__FUNCTION__,'Background',"failure to save ensogo operation info ,uid:" . $ensogoAccount ['uid'] . "error:" . json_encode($ensogoAccount->getErrors ())], "edb\global" );
			}
				
			if (!empty($orders['proxyResponse']['data']['paging']['next']))
				echo "ready to get next page ". json_encode($orders['proxyResponse']['data']['paging']['next']) ." \n";
			else
				echo "No next page \n";
				
			if ($insertEnsogoReturn['updated_count'] ==0 and $insertEnsogoReturn['inserted_count'] ==0)
				echo "updated count and inserted cout is ZERO, so not to have next page \n";
			else
				echo "updated count = ".$insertEnsogoReturn['updated_count']." and inserted cout is ".
				$insertEnsogoReturn['inserted_count'].", so will have next page \n";
				
			//如果没有返回next ， 或者 inserted 和 updated order 都为0，就不要继续做下去了，因为已经到了 上次获取的边界值，继续获取下去就重复了
		}while( !empty($orders['proxyResponse']['data']['paging']['next']) and isset($insertEnsogoReturn['success']) and $insertEnsogoReturn['success']==true);
			
	}//end of _getEnsogoOrderByPagination
	

	/**
	 * 根据action type 调用 对应 的ensogo api
	 * lkh 2014-12-09
	 */
	private static function _CallEnsogoApiByActionType($ensogo_token,$action_type , $params){
		$timeout=120; //s
		\Yii::info(['ensogo',__CLASS__,__FUNCTION__,'Background',"Step 1 start to call proxy for crt/upd prod,token $ensogo_token"],"edb\global");
		
		if (is_array($params)) 
			$params =json_encode($params);
		
		$reqParams['parms']  = $params;
		$reqParams['token'] = $ensogo_token;
		//the proxy will auto do update if the ensogo product id is existing
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
		$retInfo=EnsogoProxyConnectHelper::call_ENSOGO_api($actionTypeToApiMapping[$action_type],$reqParams );
		\Yii::info(['ensogo',__CLASS__,__FUNCTION__,'Background',"Step 2 complete calling proxy for  action type $action_type ,token $ensogo_token"],"edb\global");
		//check the return info
		return $retInfo;	
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ensogo api 获取的订单数组 保存到订单模块中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $orders		ensogo api 返回的结果
	 * @param $ensogoAccount		ensogo user model 
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 * @description			ensogo order  调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _InsertEnsogoOrder($orders , $ensogoAccount,$isInitial = false){
		GLOBAL $HANDLED_ENSOGO_ORDER,$INTEGRATED_ORDER_IDS,$INTEGRATED_ORDER_LAST_UPDATE;
		$rtn['inserted_count'] = 0;
		$rtn['updated_count'] = 0;
		$rtn['skipped_count'] = 0;
		try {
			//如果是初始化，会一个进程里面insert好多次订单，做这个cache处理加快速度
			if ($isInitial){
				$puid = \Yii::$app->subdb->getCurrentPuid();
				//Load all ensogo order id to cache， for all have been integrated before
				if (!isset($INTEGRATED_ORDER_IDS[$puid.''])){
					$integrated_orders = EnsogoOrder::find()->select(['order_id','last_updated'])->asArray()->all();
					$INTEGRATED_ORDER_IDS[$puid.''] = 1;
					foreach($integrated_orders as $anEnsogoOrder){
						$HANDLED_ENSOGO_ORDER[$anEnsogoOrder['order_id']] = 1;
						$INTEGRATED_ORDER_LAST_UPDATE[$anEnsogoOrder['order_id']] = $anEnsogoOrder['last_updated'];
					}
				}
			}
			
			echo "YS1.0 start to insert orders ...\n";
			$ImportOrderArr = array();
			$tempEnsogoOrderModel = new EnsogoOrder();
			//$tempEnsogoOrderDetailModel = new EnsogoOrderDetail();
			$EnsogoOrderModelAttr = $tempEnsogoOrderModel->getAttributes();
			//$EnsogoOrderDetailModelAttr = $tempEnsogoOrderDetailModel->getAttributes();
			$rtn['message'] ='';
			$rtn['success'] = true;
			
			$allOrders = $orders['proxyResponse']['data'];
			if (isset($orders['proxyResponse']['data']['data']))
				$allOrders = $orders['proxyResponse']['data']['data'];
			
			foreach($allOrders as $anOrder){
				$anOrder1 = $anOrder;	

				//兼容curl 格式的返回，可能还爆了一层 Order array
				if (isset($anOrder1['Order']) and is_array($anOrder1['Order']))
					$anOrder = $anOrder1['Order'];
					
				//ensogo 没有 order id 只有id 
				if (empty($anOrder['order_id']) && !empty($anOrder['id'])){
					$anOrder['order_id'] = $anOrder['id'];
				}
				
				echo "get order ".$anOrder['order_id']."...";
				
				if (!empty($anOrder['buyer_id']))
					$anOrder['source_buyer_user_id'] = $anOrder['buyer_id'];
				
			 	
				//It is possible to do this again when we do fetch chagned order after we fetch unfufilled orders, so ignore this order id
				if ($isInitial and isset($anOrder['order_id']) and isset($HANDLED_ENSOGO_ORDER[$anOrder['order_id']])
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
					if (isset($HANDLED_ENSOGO_ORDER[$anOrder['order_id']])){
						$orderModel = EnsogoOrder::find()->where(['order_id'=>$anOrder['order_id'] ])->one(); 
						$newCreateOrder = false;
						$rtn['updated_count'] ++;
					}else{
						//new order
						$orderModel = new EnsogoOrder();
						$newCreateOrder = true;
						$rtn['inserted_count'] ++;
					}
				}else{//not intial
					$orderModel = EnsogoOrder::find()->where(['order_id'=>$anOrder['order_id'] ])->one();
					if (!empty($orderModel)){
						$newCreateOrder = false;
						$rtn['updated_count'] ++;
					}else{
						//new order
						$orderModel = new EnsogoOrder();
						$newCreateOrder = true;
						$rtn['inserted_count'] ++;
					}
				}//end of not initial

				//set order info 
				$orderInfo = array();
				$orderDetailInfo = array();
			//	echo " YS0.2   "; //ystest
				foreach($anOrder as $key=>$value){
					if ($key=="id"){
						$orderInfo['order_id'] = $value;
						continue;
					}
					
					if ($key=="country"){
						$orderInfo['website'] = $value;
						continue;
					}
					
					if ($key=='state'){
						$orderInfo['status'] = $value;
						continue;
					}
				 
					if ($key=='shipping_detail'){
						foreach($value as $subkey=>$subvalue){
							if (array_key_exists($subkey, $EnsogoOrderModelAttr)){
								$orderInfo[$subkey] = $subvalue;
							}
						}//end of each ShippingDetail
						continue;
					}
				 
					if (array_key_exists($key, $EnsogoOrderModelAttr)){
						$orderInfo[$key] = $value;
					}
					
				 	/*
					if (array_key_exists($key, $EnsogoOrderDetailModelAttr)){
						if (strtolower($key) == 'price' ) $value = round($value,2);
						$orderDetailInfo[$key] = $value;
					}
					*/
				}
			//	echo " YS0.3   "; //ystest
				if (!empty($orderInfo)){
					//unset($orderInfo['order_id']);
					$orderModel->setAttributes ($orderInfo);
					if (! $orderModel->save()){
						\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"failure to save ensogo order,uid:".$ensogoAccount['uid']."error:". print_r($orderModel->getErrors(),true)],"edb\global");
					}
				}
			//	echo " YS0.4   "; //ystest
				//save order detail 
				/*
				if ($newCreateOrder)
					$orderDetails = new EnsogoOrderDetail();
				else{
					$orderDetails = EnsogoOrderDetail::find()->where(['order_id'=>$anOrder['order_id'] ])->one(); 
					if (empty($orderDetails)){
						$orderDetails = new EnsogoOrderDetail();
					}
				}
				*/
				//remember this order id, so do not do this orderid again during this job, 
				//It is possible to do this again when we do fetch chagned order after we fetch unfufilled orders
				if (isset($anOrder['order_id']) and !isset($HANDLED_ENSOGO_ORDER[$anOrder['order_id']]))
					$HANDLED_ENSOGO_ORDER[$anOrder['order_id']] = 1;
			/*
				if (!empty($orderDetailInfo)){
					$orderDetailInfo['order_id'] = $anOrder['order_id'];
					$orderDetails->setAttributes ($orderDetailInfo);
					if (! $orderDetails->save()){
						\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"failure to save ensogo order details ,uid:".$ensogoAccount['uid']."error:". print_r($orderDetails->getErrors(),true)],"edb\global");										
					}
				}
				*/
			   // echo " YS0.7   ".$anOrder['order_id']."\n"; //ystest
				//set up default value  
				$anOrder['order_source_site_id'] = $orderModel->website;
				$anOrder['selleruserid'] = $ensogoAccount['store_name'];
				$anOrder['saas_platform_user_id'] = $ensogoAccount['site_id']; 
				//format import order data
				
				$formated_order_data = self::_formatImportOrderData( $anOrder );

				// call eagle order api to sync order information
				//$importOrderResult = OrderHelper::importPlatformOrder($OrderParms);
			//	Step 1: save this order to eagle OMS 1.0, and get the record ID
			/*20151103kh 不再插入eagle 1 start 
				$importOrderResult = self::_saveEnsogoOrderToOldEagle1($formated_order_data);
					
				if ($importOrderResult['success']==="fail"){
					$message = "Call Eagle1 order insert api fails.  error:".$importOrderResult['message'];
					\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',$message,"edb\global"]);
					$rtn['success'] = false;
					if (!empty($rtn['message']))
						$rtn['message'] .="<br>";
						
					$rtn['message'] .= $message;
					echo "_saveEnsogoOrderToOldEagle1 failed ".$rtn['message']."\n";
					continue;
				}
				$resultInfo=json_decode($importOrderResult["responseStr"],true);
				if (!isset($resultInfo['success']) or $resultInfo['success'] == 1){
					//eagle1 获取了request但是执行失败
					$message = "Eagle 1 Insert order fails.  error:".$resultInfo['message'];
					\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',$message,"edb\global"]);
					
					if (!empty($rtn['message']))
						$rtn['message'] .="<br>";
				
					echo $message."\n";
					$rtn['message'] .= $message;
				}
				
				$eagleOrderRecordId=$resultInfo["eagle_order_id"];
				20151103kh 不再插入eagle 1 end */
				//echo " eagleOrderId:$eagleOrderRecordId start to duplicate it to eagle 2 oms \n";
		 
				//	Step 2: save this order to eagle OMS 2.0, using the same record ID		
				$importOrderResult=self::_saveEnsogoOrderToEagle($formated_order_data);
				if (!isset($importOrderResult['success']) or $importOrderResult['success']==1){
					//echo $anOrder['order_id'] . $importOrderResult['message'];
				}else{
					//echo "Success insert an order rec id $eagleOrderRecordId to oms 2 \n";
				}
			}//end of each order 
			
			return $rtn;
		} catch (\Exception $e) {
			\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"insert ensogo order :".$e->getMessage() ],"edb\global");
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
			echo "YSc001 error: ".$rtn['message']." \n try to handle order ";//.print_r($orders,true);
			return $rtn;
		}				
	}//end of _InsertEnsogoOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ensogo api 获取的数组 赋值到  eagle order 接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $importOrderData		eagle order 接口 的数组
	 * @param $ensogoOrderData		ensogo 数据
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 * @description			ensogo order  调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderData($ensogoOrderData){
		$importOrderData = array();
		$item = array();
		if (!empty($ensogoOrderData['state'])){
			/*
			 * 	ensogo order state 
			 *  State 'APPROVED' means that the order is ready to ship.
			 *  State 'SHIPPED' means that the order has been marked as shipped by you.
			 *  State 'REFUNDED' means that the order has been refunded to the user and should not be fulfilled.
			 *  State 'REQUIRE_REVIEW' means that the order is under review for fraudulent activity and should not be fulfilled.
			 *  
			 *  eagle order status
			 *  100（未付款）、200（已付款）、500（已发货）、600（已取消）
			 * */
			
			$EnsogoStateToEagleStatusMapping = array(
				'PAID'=> OdOrder::STATUS_PAY, 
				'SHIPPED'=>OdOrder::STATUS_SHIPPING , 
				'DELIVERED'=>OdOrder::STATUS_SHIPPED,
				'REFUNDED'=>OdOrder::STATUS_CANCEL , 
				'REQUIRE_REVIEW'=>OdOrder::STATUS_NOPAY,
			);
			$importOrderData['order_status'] = $EnsogoStateToEagleStatusMapping[$ensogoOrderData['state']]; 
			$importOrderData['order_source_status'] = $ensogoOrderData['state'];
		}
		if (isset($importOrderData['order_status'] )){
			//发货状态：1.已发货；0.未发货 #非已发货状态暂时定义为未发
			$importOrderData['shipping_status'] = (($importOrderData['order_status'] == 500)?1:0); 
		}
		
		$importOrderData['order_source'] = 'ensogo'; //订单来源，比如‘Ensogo’
		$importOrderData['order_type'] = 'ensogo'; //订单类型 如Ensogo FBA订单
		if (isset($ensogoOrderData['order_id'])){
			$importOrderData['order_source_order_id'] = $ensogoOrderData['order_id']; //订单来源的订单id
			if (empty($item['order_source_order_id']) || $item['order_source_order_id']!=  $ensogoOrderData['order_id']){
				$item['order_source_order_id'] = $ensogoOrderData['order_id']; // item 的平台 订单号用于eagle 2.0 的发货
			}
		}
		
		if (isset($ensogoOrderData['order_source_site_id']))
			$importOrderData['order_source_site_id'] = $ensogoOrderData['order_source_site_id']; //订单来源平台下的站点
		
		if (isset($ensogoOrderData['selleruserid']))
			$importOrderData['selleruserid'] = $ensogoOrderData['selleruserid']; //订单来源平台卖家用户名(下单时候的用户名)
			
		if (isset($ensogoOrderData['saas_platform_user_id']))
			$importOrderData['saas_platform_user_id'] = $ensogoOrderData['saas_platform_user_id']; //订单来源平台卖家用户名(下单时候的用户名)
		
		if (isset($ensogoOrderData['source_buyer_user_id']))
			$importOrderData['source_buyer_user_id'] = $ensogoOrderData['source_buyer_user_id']; //来源买家用户名
		
		if (isset($ensogoOrderData['order_time']))
			$importOrderData['order_source_create_time'] = strtotime($ensogoOrderData['order_time']); //订单在来源平台的下单时间
		
		
		
		if (isset($ensogoOrderData['shipping_detail']['name'])){
			$importOrderData['consignee'] = $ensogoOrderData['shipping_detail']['name']; //收货人
			if (empty($importOrderData['source_buyer_user_id']))
				$importOrderData['source_buyer_user_id'] = $ensogoOrderData['shipping_detail']['name']; //收货人
		}
		
		if (isset($ensogoOrderData['shipping_detail']['zipcode']))
		$importOrderData['consignee_postal_code'] = $ensogoOrderData['shipping_detail']['zipcode']; //收货人邮编
		
		if (isset($ensogoOrderData['shipping_detail']['phone_number']))
		$importOrderData['consignee_phone'] = $ensogoOrderData['shipping_detail']['phone_number']; //收货人电话
		
		//$importOrderData['consignee_email'] = 100; //收货人Email
		//$importOrderData['consignee_company'] = 100; //收货人公司
		if (!empty($ensogoOrderData['shipping_detail']['country'])){
			$importOrderData['consignee_country'] = $ensogoOrderData['shipping_detail']['country']; //收货人国家名
			$importOrderData['consignee_country_code'] = $ensogoOrderData['shipping_detail']['country']; //收货人国家代码
		}else{
			$importOrderData['consignee_country'] = $ensogoOrderData['country'];
			$importOrderData['consignee_country_code'] = $ensogoOrderData['country'];
		}
		
		if (isset($ensogoOrderData['shipping_detail']['city']))
		$importOrderData['consignee_city'] = $ensogoOrderData['shipping_detail']['city']; //收货人城市
		if (isset($ensogoOrderData['shipping_detail']['state']))
		$importOrderData['consignee_province'] = $ensogoOrderData['shipping_detail']['state']; //收货人省
		
		if (isset($ensogoOrderData['shipping_detail']['street_address2']))
		$importOrderData['consignee_address_line2'] = $ensogoOrderData['shipping_detail']['street_address2']; //收货人地址1
		
		if (isset($ensogoOrderData['shipping_detail']['street_address1']))
			$importOrderData['consignee_address_line1'] = $ensogoOrderData['shipping_detail']['street_address1'];
		
			if (isset($importOrderData['order_source_create_time'] ))
		$importOrderData['paid_time'] = $importOrderData['order_source_create_time'] ; //订单付款时间
		if (isset($ensogoOrderData['shipped_date']))
			$importOrderData['delivery_time'] = strtotime($ensogoOrderData['shipped_date']); //订单发货时间
			
		
		if (isset($ensogoOrderData['buyer_id']))
			$importOrderData['buyer_id'] = $ensogoOrderData['buyer_id']; //买家id
		
		if (isset($ensogoOrderData['shipping_provider']))
			$importOrderData['shipping_provider'] = $ensogoOrderData['shipping_provider']; //物流商
		
		if (isset($ensogoOrderData['tracking_number']))
			$importOrderData['tracking_number'] = $ensogoOrderData['tracking_number']; //物流跟踪号
		
		if (isset($ensogoOrderData['transaction_id'])){
			$item['order_source_transactionid'] = $ensogoOrderData['transaction_id']; // 订单交易号
		}
		
		if (isset($ensogoOrderData['product_id'])){	
			$item['order_source_order_item_id']	= $ensogoOrderData['product_id'];
			$item['order_source_itemid']	= $ensogoOrderData['product_id'];
		}
		
		$item['promotion_discount'] = 0;
		if (isset($ensogoOrderData['shipping']))
			$item['shipping_price'] = $ensogoOrderData['shipping'];
			
		$item['shipping_discount'] = 0;
		if (isset($ensogoOrderData['sku']))
			$item['sku'] = $ensogoOrderData['sku'];
		if (isset($ensogoOrderData['price']))
			$item['price'] = $ensogoOrderData['price'];
		
		if (isset($ensogoOrderData['quantity'])){
			$item['quantity'] = $ensogoOrderData['quantity'];
			$item['ordered_quantity'] = $ensogoOrderData['quantity'];
		}else{
			$item['quantity'] = 0;
		}
		
		if (isset($item['quantity'])&&isset($item['price']) )
			$importOrderData['subtotal'] = $item['quantity']*$item['price']; //产品总价格
		else 
			$importOrderData['subtotal'] = 0;
		
		if (isset($item['quantity'])&& isset($ensogoOrderData['shipping']))
			$importOrderData['shipping_cost'] = $item['quantity'] * $ensogoOrderData['shipping']; //运费
			
		$importOrderData['discount_amount'] = 0; //折扣
		if (isset($importOrderData['subtotal']) && isset($importOrderData['shipping_cost']))
		$importOrderData['grand_total'] = $importOrderData['subtotal'] + $importOrderData['shipping_cost'] - $importOrderData['discount_amount']; //合计金额(产品总价格 + 运费 - 折扣 = 合计金额)
		$importOrderData['currency'] = 'USD'; //货币
		
		if (isset($ensogoOrderData['state']))
		$item['sent_quantity'] = ((strtoupper($ensogoOrderData['state']) == 'SHIPPED')?$item['quantity']:0);	
		if (isset($ensogoOrderData['product_name']))	
		$item['product_name'] = $ensogoOrderData['product_name'];	
		
		if (isset($ensogoOrderData['product_image_url']))
		$item['photo_primary'] = $ensogoOrderData['product_image_url'];
		
		//product_attributes  start
		$product_attributes = [];
		if (isset($ensogoOrderData['size'])){
			$product_attributes['size'] = $ensogoOrderData['size'];
		}
		
		if (isset($ensogoOrderData['color'])){
			$product_attributes['color'] = $ensogoOrderData['color'];
		}
		
		if (!empty($product_attributes)){
			$item['product_attributes'] = json_encode($product_attributes);
		}
		//product_attributes  end
		
		$importOrderData['items'][] = $item;
			
		if (!empty($ensogoOrderData['tracking_number'])){
			if (isset($ensogoOrderData['tracking_number']))
			$importOrderData['orderShipped'][0]['tracking_number'] = $ensogoOrderData['tracking_number'];
			if (isset($ensogoOrderData['order_id']))
			$importOrderData['orderShipped'][0]['order_source_order_id'] = $ensogoOrderData['order_id'];
			$importOrderData['orderShipped'][0]['order_source'] = 'ensogo';
			if (isset($ensogoOrderData['selleruserid']))
			$importOrderData['orderShipped'][0]['selleruserid'] = $ensogoOrderData['selleruserid'];
			$importOrderData['orderShipped'][0]['tracking_link'] = "";
			if (isset($ensogoOrderData['shipping_provider']))
			$importOrderData['orderShipped'][0]['shipping_method_name'] = $ensogoOrderData['shipping_provider'];
			$importOrderData['orderShipped'][0]['addtype'] = '平台API';
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
	private static function _getAllChangeOrdersSince($ensogo_token , $dateStart, $start=0, $limit=100, $next=''){
		//\Yii::info(['ensogo',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $ensogo_token"],"edb\global");
		/*
		$dummydata =  '{"success":true,"message":"","proxyResponse":{"success":true,"message":"","ensogoReturn":[{"sku":"CS8614110603A","shipping_provider":"EMS","last_updated":"2016-01-18T08:07:00","product_id":"547d3f06b7b817420dff5974","order_time":"2015-01-29T15:03:55","order_id":"54ca4bdb8a03ba098cea44f9","price":"7.69","variant_id":"547d3f06b7b817420dff5976","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"\u4e0a\u6d77","name":"\u90b5\u771f","country":"CN","zipcode":"200060","street_address1":"\u4e0a\u6d77\u5e02\u666e\u9640\u533a\u80f6\u5dde\u8def1077\u5f045\u53f7\u697c201\u5ba4"},"shipped_date":"2015-01-30","state":"SHIPPED","cost":"6.54","shipping_cost":"1.69","order_total":"8.23","buyer_id":"54b87b8cdf7b87158a56bb98","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/547d3f06b7b817420dff5974-normal.jpg","product_name":"Two Fold Design PU Leather Flip Stand Tablet Full Body Protector Case Cover Skin with Lichee Pattern For iPad Air 2 - Rose","transaction_id":"54ca4bda9f10723f48029c40","quantity":"1"},{"sku":"CS8614103009E","shipping_provider":"SingaporePost","last_updated":"2016-01-16T09:19:21","product_id":"5487bd458ecdd40bace9b94a","order_time":"2015-01-27T09:07:35","order_id":"54c75557d17c20120af09d8a","price":"4.99","variant_id":"5487bd458ecdd40bace9b94c","tracking_number":"RF172499314SG","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"\u4e0a\u6d77","name":"\u90b5\u771f","country":"CN","zipcode":"200060","street_address1":"\u4e0a\u6d77\u5e02\u666e\u9640\u533a\u80f6\u5dde\u8def1077\u5f045\u53f7\u697c201\u5ba4"},"shipped_date":"2015-01-29","state":"SHIPPED","cost":"4.24","shipping_cost":"1.69","order_total":"5.93","buyer_id":"54b87b8cdf7b87158a56bb98","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/5487bd458ecdd40bace9b94a-normal.jpg","product_name":"Acrylic Two-in-one Case for Apple iPhone 6 plus(5.5\") - Golden","transaction_id":"54c75556ba0cc52aa27aa9d1","quantity":"1"},{"sku":"CS8614090215B","shipping_provider":"EMS","last_updated":"2016-01-16T09:17:56","product_id":"549269aa5384966c49cca950","order_time":"2015-01-30T03:42:09","order_id":"54cafd9197b82f117908bd4f","price":"4.99","variant_id":"549269aa5384966c49cca952","tracking_number":"12100000000136","shipping":"1.99","ShippingDetail":{"phone_number":"13301808059","city":"\u4e0a\u6d77","name":"\u90b5\u771f","country":"CN","zipcode":"200060","street_address1":"\u4e0a\u6d77\u5e02\u666e\u9640\u533a\u80f6\u5dde\u8def1077\u5f045\u53f7\u697c201\u5ba4"},"shipped_date":"2015-02-02","state":"SHIPPED","cost":"4.24","shipping_cost":"1.69","order_total":"5.93","buyer_id":"54b87b8cdf7b87158a56bb98","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/549269aa5384966c49cca950-normal.jpg","product_name":"Fashion Stips Design iPhone 6 Case with Buckle Stand Function and Card Holder - Blue","transaction_id":"54cafd91133c2d0c35ea183f","quantity":"1"},{"last_updated":"2016-01-12T06:33:42","order_time":"2016-01-06T23:25:57","order_id":"568da2855a74357142a7d963","refunded_time":"2016-01-12","shipping_cost":"1.69","ShippingDetail":{"phone_number":"17097697623","city":"Holyrood","state":"Newfoundland and Labrador","name":"Julia Claire Kieley","country":"CA","zipcode":"A0A 2R0","street_address1":"295 Conception Bay Highway"},"refunded_by":"REFUNDED BY MERCHANT","cost":"6.54","variant_id":"547d3cefd21cd410d1d698b7","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/547d3cefd21cd410d1d698b5-normal.jpg","sku":"CS8614110603K","shipped_date":"2016-01-10","shipping_provider":"ChinaAirPost","order_total":"8.23","product_id":"547d3cefd21cd410d1d698b5","tracking_number":"RX833132638DE","shipping":"1.99","product_name":"Two Fold Design PU Leather Flip Stand Tablet Full Body Protector Case Cover Skin with Lichee Pattern For iPad Air 2 - Orange","state":"REFUNDED","refunded_reason":"Unable to fulfill order","buyer_id":"5633e9717be5bad712704cf4","price":"7.69","transaction_id":"568da2839e003b5fabc34143","quantity":"1"},{"sku":"CS8614110604A","refunded_by":"CANCELLED BY CUSTOMER","last_updated":"2016-01-09T07:02:23","product_id":"547d46a4dbb0d64ac14db3c2","order_time":"2016-01-08T04:42:01","variant_id":"547d46a4dbb0d64ac14db3c4","refunded_time":"2016-01-09","shipping_cost":"1.69","shipping":"1.99","ShippingDetail":{"phone_number":"2606671506","city":"Angola","state":"Indiana","name":"Justin Mashione","country":"US","zipcode":"46703","street_address1":"1305 W 275 N"},"price":"6.99","order_id":"568f3e1931c3f75bbafe862c","state":"REFUNDED","cost":"5.94","refunded_reason":"Customer cancelled the order","order_total":"7.63","buyer_id":"562f25f8d62e7c1426a587f3","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/547d46a4dbb0d64ac14db3c2-normal.jpg","product_name":"High Quality 360 Degree Rotating Stand Leather Case Smart Cover For iPad Air 2 , With Automatic Wake\/Sleep Function - White","transaction_id":"568f3e197dfda5e7be4af41b","quantity":"1"},{"last_updated":"2015-12-31T13:34:02","order_time":"2015-12-29T03:42:59","order_id":"568201439377b5464488b1ca","price":"6.99","shipping_cost":"2.54","ShippingDetail":{"phone_number":"8508329950","city":"lynn haven","state":"Florida","name":"christian","country":"US","zipcode":"32444","street_address1":"3514 pleasant hill rd"},"shipped_date":"2015-12-31","cost":"5.94","variant_id":"54754bd21280fa6d17144ed1","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/54754bd21280fa6d17144ecf-normal.jpg","size":"Cubic","sku":"ACCUSBVEN001A","shipping_provider":"USPS","order_total":"8.48","product_id":"54754bd21280fa6d17144ecf","state":"SHIPPED","shipping":"2.99","tracking_number":"LN245713568SG","buyer_id":"5681fd1245dda40a9574f0ed","product_name":"Mini USB Fan Fan cute little computer fan mute shipping","transaction_id":"568201437e8ae02ec59e81de","quantity":"1"},{"last_updated":"2015-12-24T03:08:59","order_time":"2015-12-23T08:08:08","order_id":"567a5668c527e2545c5e4de8","price":"5.99","shipping_cost":"1.69","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-12-24","cost":"5.09","variant_id":"5653c3b155e4d07e9f3db5d4","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/5653c3b155e4d07e9f3db5d2-normal.jpg","size":"6.4cm by 3.5cm by 0.3cm","sku":"GPGD15092401A","shipping_provider":"RussianPost","order_total":"6.78","product_id":"5653c3b155e4d07e9f3db5d2","state":"SHIPPED","shipping":"1.99","tracking_number":"RQ997185501CN","buyer_id":"559a27b9e3806d41166b3026","product_name":"Socks for Pet Cat Dog Snowman Deer Christmas tree Snowflakes Patterned Christmas New Year Red + Green Socks 4 PCS","transaction_id":"567a56686a87ca417224eb28","quantity":"1"},{"last_updated":"2015-12-24T03:08:29","order_time":"2015-12-23T08:12:20","order_id":"567a5764ce7b3e5378fe1217","price":"6.99","shipping_cost":"2.54","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-12-24","cost":"5.94","variant_id":"54754bd21280fa6d17144ed1","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/54754bd21280fa6d17144ecf-normal.jpg","size":"Cubic","sku":"ACCUSBVEN001A","shipping_provider":"RussianPost","order_total":"8.48","product_id":"54754bd21280fa6d17144ecf","state":"SHIPPED","shipping":"2.99","tracking_number":"RQ997185501CN","buyer_id":"559a27b9e3806d41166b3026","product_name":"Mini USB Fan Fan cute little computer fan mute shipping","transaction_id":"567a5764abe9c74a7065cdd9","quantity":"1"},{"sku":"ssa2055","shipping_provider":"RussianPost","last_updated":"2015-12-24T03:08:07","product_id":"54a0b626ffff651e9f9fd7fc","order_time":"2015-12-23T08:11:38","order_id":"567a573a59094953885f5200","price":"5.99","variant_id":"54a0b626ffff651e9f9fd7fe","tracking_number":"RQ997185500CN","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-12-24","state":"SHIPPED","cost":"5.09","shipping_cost":"1.69","order_total":"6.78","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/54a0b626ffff651e9f9fd7fc-normal.jpg","product_name":"Handmade Crystal Bracelet With Chinese Symbol on Crystal bracelets bangles for women","transaction_id":"567a573a9bd7b59bc63fcb12","quantity":"1"},{"last_updated":"2015-12-24T03:07:37","order_time":"2015-12-23T08:12:29","order_id":"567a576d3e61274eccdcecbc","price":"6.99","shipping_cost":"2.54","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-12-24","cost":"5.94","variant_id":"54754bd21280fa6d17144ed1","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/54754bd21280fa6d17144ecf-normal.jpg","size":"Cubic","sku":"ACCUSBVEN001A","shipping_provider":"RussianPost","order_total":"8.48","product_id":"54754bd21280fa6d17144ecf","state":"SHIPPED","shipping":"2.99","tracking_number":"RQ997185500CN","buyer_id":"559a27b9e3806d41166b3026","product_name":"Mini USB Fan Fan cute little computer fan mute shipping","transaction_id":"567a576dd8e03d6bc9d6e984","quantity":"1"},{"sku":"LSAM14102301A","shipping_provider":"WishPost","last_updated":"2015-12-08T16:06:49","product_id":"547565d08edcfa11a2218448","order_time":"2015-10-24T19:39:39","order_id":"562bde7b553121470a39793d","price":"9.99","variant_id":"547565d08edcfa11a221844a","tracking_number":"RI709804004CN","shipping":"2.99","ShippingDetail":{"phone_number":"604-351-9629","city":"PORT COQUITLAM","state":"BC","name":"Alex Pesusich","country":"CA","zipcode":"V3C 5J2","street_address1":"2314 COLONIAL DR"},"shipped_date":"2015-10-27","state":"SHIPPED","cost":"8.49","shipping_cost":"2.54","order_total":"11.03","buyer_id":"55ed480ff2d320436d470282","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/547565d08edcfa11a2218448-normal.jpg","product_name":"Music Starry Star Sky Projection Alarm Clock Calendar Thermometer For Best gift,freeshipping","transaction_id":"562bde73bbcc4b1d285cafdb","quantity":"1"},{"sku":"CS8614090217A","shipping_provider":"RussianPost","last_updated":"2015-12-01T15:20:06","product_id":"549269a4da081c069bf8a6ef","order_time":"2015-11-02T07:14:52","order_id":"56370d6c1357e52ff18175ec","price":"4.99","variant_id":"549269a4da081c069bf8a6f1","tracking_number":"RQ997185551CN","shipping":"1.99","ShippingDetail":{"phone_number":"65343623","city":"United States","state":"Michigan","name":"shen002","country":"US","zipcode":"48089","street_address1":"12647 sidonie ave"},"shipped_date":"2015-11-03","state":"SHIPPED","cost":"4.24","shipping_cost":"1.69","order_total":"5.93","buyer_id":"559a27b9e3806d41166b3026","product_image_url":"https:\/\/contestimg.wish.com\/api\/webimage\/549269a4da081c069bf8a6ef-normal.jpg","product_name":"Silk PU Leather iPhone 6 Case with Strip - Pink","transaction_id":"56370d6b98c867141601719a","quantity":"1"}]}}';
		return json_decode($dummydata,true);
		*/
		$reqParams['token'] = $ensogo_token;
		/*
		//为了避免传递过程中，url 里面的 & 符号烂了，先做url encode
		if (!empty($next))
			$next = urlencode($next);
		*/
		//$reqParams['parms']=json_encode(array('dateSince'=>$dateStart , 'start'=>$start, 'limit'=>$limit, 'next'=>$next));
		$reqParams = array('dateSince'=>$dateStart , 'start'=>$start, 'limit'=>$limit, 'next'=>$next , 'access_token'=>$ensogo_token);
		
		//the proxy will auto do update if the ensogo product id is existing
		//default acton, using sdk for all
		$action = "getOrderList";
		if ($limit > 0)
			$action = 'getOrderList';
		
		if (!empty($next))
			$action = 'getNextData';
		
		$retInfo= EnsogoProxyConnectHelper::call_ENSOGO_api($action,$reqParams );
		//check the return info
		return $retInfo;
	}//end of _getAllChangeOrdersSince
	
	
	private static function _getNextData($ensogo_token , $next, $action){
		$next = urlencode($next);
		$reqParams = ['next'=>$next , 'access_token'=>$ensogo_token];
		
		$retInfo= EnsogoProxyConnectHelper::call_ENSOGO_api($action,$reqParams );
		return $retInfo;
	}
	
	
	
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
	private static function _getAllUnfulfilledOrdersSince($ensogo_token , $dateStart, $start=0, $limit=100){
		$dummydata = '{"success":true,"message":"","proxyResponse":{"success":true,"message":"","ensogoReturn":[]}}';
		return json_decode($dummydata,true);
		$timeout=120; //s
		//\Yii::info(['ensogo',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $ensogo_token"],"edb\global");
		try{
			$reqParams['token'] = $ensogo_token;

			$reqParams['parms']=json_encode(array('dateSince'=>$dateStart , 'start'=>$start, 'limit'=>$limit));
			
			$retInfo=EnsogoProxyConnectHelper::call_ENSOGO_api("getAllUnfulfilledOrdersSince",$reqParams );
			//\Yii::info(['ensogo',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $ensogo_token"],"edb\global");
		}catch(\Exception $e){
			$message = "_getAllUnfulfilledOrdersSince , $ensogo_token , $dateStart, $start , $limit Exception error:".$e->getMessage();
			echo $message."\n";
		}
		//check the return info
		return $retInfo;
	}//end of _getAllChangeOrdersSince

	/**
	 * 把Ensogo的订单信息header和items 同步到eagle1系统中user_库的od_order和od_order_item。
	 * 这里主要是通过eagle1提供的 http api的方式
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveEnsogoOrderToOldEagle1($oneOrderReq){
 
		//1. 总的请求信息
		$reqInfo=array();
		$ordersReq=array();
		
		//yzq, 20150721, fjs 建议，e1 oms insert 不可以传入 order_source_status
		if (isset(  $oneOrderReq['order_source_status']))
			unset($oneOrderReq['order_source_status']);
		
		$ordersReq[]=$oneOrderReq;
		//$uid=$merchantidUidMap[$orderHeaderInfo["merchant_id"]];
		//其中Uid是saas_Ensogo_user中的uid，这里为了便于eagle的api找到合适的数据库。
		$uid=\Yii::$app->subdb->getCurrentPuid();
		
		$reqInfo[$uid]=$ordersReq;
		$reqInfoJson=json_encode($reqInfo,true);
	
		//echo "YSa before OrderHelper::importPlatformOrder info:".json_encode($reqInfo,true)."\n";
		$postParams=array("orderinfo"=>$reqInfoJson);
		//$journal_id = SysLogHelper::InvokeJrn_Create("ENSOGO", __CLASS__, __FUNCTION__ , array('sendOrderInfoToEagle',$postParams));
		$result=EagleOneHttpApiHelper::sendOrderInfoToEagle($postParams);
		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
		//echo "YSb result:".print_r($result,true);
	
		return $result;
	}
	
	/**Auth : yzq  2015-6-2
	 * 把Ensogo的订单信息header和items 同步到eagle系统中user_库的od_order和od_order_item
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveEnsogoOrderToEagle( $oneOrderReq, $eagleOrderId=-1){
		$result = ['success'=>1,'message'=>''];
		$uid=\Yii::$app->subdb->getCurrentPuid();
		
		$reqInfo[$uid]=array_merge(OrderHelper::$order_demo,$oneOrderReq);
		
		try{
			$result =  OrderHelper::importPlatformOrder($reqInfo,$eagleOrderId);
		}catch(\Exception $e){
			$message = "importPlatformOrder fails.  EnsogoId=$eagleOrderId  Exception error:".$e->getMessage();
			\Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"Step 1.5a ". $message ],"edb\global");
			echo "YS2.1 _saveEnsogoOrderToEagle failed ".$message."\n";
			return ['success'=>1,'message'=>$message];
		}
	
		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重新统计 ensogo 各个订单的历史数据
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
	private static function _summaryEnsogoOrderByNation(){
		$sql = "select country , count(1) as ct from ensogo_order group by country order by count(1) desc";
		$command = Yii::$app->subdb->createCommand($sql);
		
		$nationList = $command->queryAll();
		$result = [];
		foreach($nationList as &$row){
			$result[$row['country']] = $row['ct']; 
		}
		ConfigHelper::setConfig('EnsogoOMS/nations', json_encode($result));
	}//end of _summaryEnsogoOrderByNation
	
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
	public static function unlockEnsogoOrderQueue($site_id , $msg='', $status='C'){
		$command = Yii::$app->db->createCommand("update saas_ensogo_user set order_manual_retrieve='N' ,  oq_status=:oq_status,order_manual_retrieve_message=:msg
										where site_id =:site_id  "  );
		$command->bindValue(':oq_status', $status , \PDO::PARAM_STR);
		$command->bindValue(':msg', $msg , \PDO::PARAM_STR);
		$command->bindValue(':site_id', $site_id , \PDO::PARAM_STR);
		$affectRows = $command->execute();
		return $affectRows;
	}//end of unlockEnsogoOrderQueue
	
	/**
	 * 通过request_id，查询ensogo操作处理结果，并同步到相关db记录
	 * @return boolean
	 */
	public static function cronSyncRequestStatuses(){
		//1次处理10条记录
		$command = Yii::$app->db->createCommand("SELECT * FROM `ensogo_request_status` WHERE `status`=0 ORDER BY `id` DESC limit 0,10");
		$Rows = $command->queryAll();
		
		if(empty($Rows)) return true;
		
		$requests=[];
		$seller_id_arr = [];
		foreach ($Rows as $row){
			$requests[$row['uid']][] = $row;
			if(!in_array($row['seller_id'], $seller_id_arr))
				$seller_id_arr[] = $row['seller_id'];
		}
		$uid_arr = array_keys($requests);
		$saasEnsogoUsers = SaasEnsogoUser::find()->select(['uid','store_name','token'])->where(['uid'=>$uid_arr,'store_name'=>$seller_id_arr])->asArray()->all();
		$userTokens = [];
		foreach ($saasEnsogoUsers as $userInfo){
			$userTokens[$userInfo['uid']][$userInfo['store_name']] = $userInfo['token'];
		}

		try{
			foreach ($uid_arr as $uid){
				 
				if(empty($requests[$uid]))
					continue;
				
				foreach ($requests[$uid] as &$request){
					$params=[];
					if(empty($userTokens[$uid][$request['seller_id']])){
						echo "\n request:".$request['id']." get token failure;";
						continue;
					}else 
						$params['access_token'] = $userTokens[$uid][$request['seller_id']];
					
					if(!empty($request['request_id'])){
						$params['request_id'] = $request['request_id'];
					}else 
						continue;

					//must be 'get'
					$rtn = EnsogoProxyConnectHelper::call_ENSOGO_api('getRequestStatus',$params);
					//print_r($rtn);
					
					if(!empty($rtn['success']) && !empty($rtn['proxyResponse']['success'])){//proxy链接，返回信息成功
						$table = $request['table_name'];
						$table_id = $request['table_id'];
						//处理成功
						if(isset($rtn['proxyResponse']['data']['code']) && $rtn['proxyResponse']['data']['code']==0 
							&& isset($rtn['proxyResponse']['data']['data'][0]['result']) && $rtn['proxyResponse']['data']['data'][0]['result']=='ok'){
							switch ($table){
								case 'od_order_shipped_v2':
									$command_s = Yii::$app->subdb->createCommand("UPDATE `od_order_shipped_v2` SET
										`status`=1,`result`='true',`errors`='',`updated`=".time()." WHERE id=$table_id");
									$s=$command_s->execute();
									
									$command_r = Yii::$app->db->createCommand("UPDATE `ensogo_request_status` SET `status`=1,`result`='',`update`='".date("Y-m-d H:i:s")."' WHERE `id`=".$request['id']);
									$r=$command_r->execute();
									unset($request);
									break;
								default:
									break;	
							}	
						}
						//处理失败
						if( (isset($result['proxyResponse']['data']['code']) && $rtn['proxyResponse']['data']['code']!==0) || 
							(isset($rtn['proxyResponse']['data']['data'][0]['result']) && $rtn['proxyResponse']['data']['data'][0]['result']!=='ok')
						){
							switch ($table){
								case 'od_order_shipped_v2':
									$command_s = Yii::$app->subdb->createCommand("UPDATE `od_order_shipped_v2` SET
										`status`=2,`result`='false',`errors`='平台处理失败',`updated`=".time()." WHERE id=$table_id");
									$s=$command_s->execute();
										
									$command_r = Yii::$app->db->createCommand("UPDATE `ensogo_request_status` SET `status`=1,`result`='".json_encode($rtn['proxyResponse']['data'])."',`update`='".date("Y-m-d H:i:s")."' WHERE `id`=".$request['id']);
									$r=$command_r->execute();
									unset($request);
									break;
								default:
									break;
							}
						}
					}else{//调用proxy失败
						echo "\n proxy error: ".json_encode($rtn);
						continue;
					}
				}
			}
		}catch (\Exception $e){
			echo "cronSyncRequestStatuses failed ,Exception error:".$e->getMessage();
		}
	}
}
