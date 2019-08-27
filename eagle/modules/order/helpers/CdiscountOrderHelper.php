<?php
namespace eagle\modules\order\helpers;
use yii;
use yii\data\Pagination;

use yii\helpers\Url;
use eagle\modules\listing\models\CdiscountApiQueue;
use eagle\modules\order\models\CdiscountOrder;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\CdiscountOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\models\SysCountry;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\listing\helpers\CdiscountProxyConnectHelper;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\listing\helpers\CdiscountOfferSyncHelper;
use eagle\modules\listing\models\CdiscountOfferList;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\HttpHelper;
use common\helpers\Helper_Array;
use eagle\models\OdOrderShipped;
use eagle\models\QueueSyncshipped;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;
use eagle\modules\delivery\helpers\DeliveryHelper;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\dash_board\helpers\DashBoardHelper;
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
 * Cdiscount订单模板业务
 +------------------------------------------------------------------------------
 * @category	item
 * @package		Helper/item
 * @subpackage  Exception
 * @author		lzhl
 +------------------------------------------------------------------------------
 */
class CdiscountOrderHelper {
	private static $manualQueueVersion = '';
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $status					指定状态筛选 （可选）
	 * @param $lasttime 				指定时间筛选 （可选）
	 +---------------------------------------------------------------------------------------------
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016/04/26			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderSyncInfoDataList($status = '' , $lasttime =''){
		$userInfo = \Yii::$app->user->identity;
		if ($userInfo['puid']==0){
			$uid = $userInfo['uid'];
		}else {
			$uid = $userInfo['puid'];
		}
		$AccountList = CdiscountAccountsApiHelper::ListAccounts($uid);
		$syncList = [];
		foreach($AccountList as $account){
			$orderSyncInfo = CdiscountAccountsApiHelper::getCdiscountOrderSyncInfo($account['site_id'],$account['uid']);
			if($orderSyncInfo['success']){
				$syncList[$account['username']] = $orderSyncInfo['result'];//is_active;last_time;message;status
				$syncList[$account['username']]['store_name'] = $account['store_name'];
			}else{
				$syncList[$account['username']]['store_name'] = $account['store_name'];
				$syncList[$account['username']]['is_active'] = '--';
				$syncList[$account['username']]['last_time'] = '--';
				$syncList[$account['username']]['message'] = '无该账号信息';
				$syncList[$account['username']]['status'] = '';
			}
		}
		return $syncList;
	}//end of getOrderSyncInfoDataList
	
	public static $orderStatus_Unshipped='WaitingForShipmentAcceptation';
	public static $orderStatus = array(
			'WaitingForShipmentAcceptation',
			'CancelledByCustomer',
			//'WaitingForSellerAcceptation',
			//'AcceptedBySeller',
			//'PaymentInProgress',
			'Shipped',
			'RefusedBySeller',
			'AutomaticCancellation',
			//'PaymentRefused',
			'ShipmentRefusedBySeller',
			'RefusedNoShipment',
	);
	
	public static $cd_source_status_mapping = [
		'WaitingForShipmentAcceptation'=>'Waiting For Shipment Acceptation',
		'CancelledByCustomer'=>'Cancelled By Customer',
		'WaitingForSellerAcceptation'=>'Waiting For Seller Acceptation',
		'AcceptedBySeller'=>'Accepted By Seller',
		'PaymentInProgress'=>'Payment In Progress',
		'Shipped'=>'Shipped',
		'RefusedBySeller'=>'Refused By Seller',
		'AutomaticCancellation'=>'Automatic Cancellation',
		'PaymentRefused'=>'Payment Refused',
		'ShipmentRefusedBySeller'=>'Shipment Refused By Seller',
		'RefusedNoShipment'=>'Refused No Shipment',
	];
	
	public static $ShippingCode = array(
			'STD'=>'标准',//Standard
			'TRK'=>'跟踪',//tracking
			'REG'=>'挂号',//registered
			'COL'=>'Collissimo',
			'RCO'=>'Relay colis',
			'REL'=>'Mondial Relay',
			'SO1'=>'So Colissimo',
			'MAG'=>'in shop',
			'LV1'=>'Eco(>30kg)',
			'LV2'=>'Standard(>30kg)',
			'LV3'=>'Confort(>30kg)',
			'EXP'=>'Express(Express)',
			'FST'=>'Rapide(Express)',
	);
	
	public static function getCdiscountOrderShippingCode(){
		return self::$ShippingCode;
	}
	
	public static function test(){
		try {
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));//600 m is test ,real value is 30
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_cdiscount_user set last_order_success_retrieve_time='0000-00-00 00:00:00',last_order_retrieve_time='0000-00-00 00:00:00'
										where last_order_success_retrieve_time is null or last_order_retrieve_time is null"  );
			$affectRows = $command->execute();
				
			$SAASCDISCOUNTUSERLIST = SaasCdiscountUser::find()->where("is_active='1' ")->orderBy("last_order_success_retrieve_time asc")->all();
				
			//retrieve orders  by  each wish account
			foreach($SAASCDISCOUNTUSERLIST as $cdiscountAccount ){
				$uid = $cdiscountAccount['uid'];
		
				echo "<br>YS1 start to fetch for unfuilled uid=$uid ... ";
				if (empty($uid)){
				//异常情况
					$message = "site id :".$cdiscountAccount['site_id']." uid:0";
					echo $message;
					return false;
				}
		
		
				$updateTime =  TimeUtil::getNow();
				//update this wish account as last order retrieve time
				$cdiscountAccount->last_order_retrieve_time = $updateTime;
		
				$dateSince = date("Y-m-d\TH:i:s" ,strtotime($updateTime)-3600*24);// 1day前
					
				//start to get unfulfilled orders
				echo "<br>".TimeUtil::getNow()." start to get $uid unfufilled order for ".$cdiscountAccount['store_name']." since $dateSince \n"; //ystest
				$getOrderCount = 0;
				$sinceTimeUTC = date("Y-m-d\TH:i:s" ,strtotime($dateSince)-3600*8);//UTC time is -8 hours

				$orders = self::_getAllUnfulfilledOrdersSince($cdiscountAccount['token'] , $sinceTimeUTC );
					
				//fail to connect proxy
				if (empty($orders['success'])){
				echo "<br>fail to connect proxy  :".$orders['message'];
				$cdiscountAccount->save();
				continue;
				}
											
				echo "<br>".TimeUtil::getNow()." got results, start to insert oms \n"; //ystest
				//accroding to api respone  , update the last retrieve order time and last retrieve order success time
				//print_r($orders);

			}//end of each wish user account
		} catch (\Exception $e) {
			echo "<br>uid retrieve order :".$e->getMessage();
		}
		
	}
	
	
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
	 * log			name		date			note
	 * @author		lzhl		2016/04/16		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMenuStatisticData($params=[],$platform='cdiscount'){
		$counter = OrderHelper::getMenuStatisticData($platform,$params);
	
		$OrderQuery = OdOrder::find()->where('order_source = "'.$platform.'" and (`order_relation`="normal" or `order_relation`="sm")');
		if (!empty($params)){
			$OrderQuery->andWhere($params);
		}
	
		$QueryConditionList = [
		//今日新订单
		'todayorder'=>[['>=','paid_time',strtotime(date('Y-m-d'))],['<','paid_time',strtotime('+1 day')] ],
		//等待您发货
		'sendgood'=>['order_source_status'=>'WaitingForShipmentAcceptation'],
		//有纠纷的订单
		'issueorder'=>['order_source_status'=>'IN_ISSUE'],
		//未读留言
		'newmessage'=>['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => 'new_msg_tag'])],

		//等待您留评
		'waitcomment'=>['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => 'favourable_tag'])],
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
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 自动 生成 top menu
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $keyword				关键字， 来控制选中的样式  '' , 100 , 200 , 300 , 400 , 500
	 +---------------------------------------------------------------------------------------------
	 * @return						string  html 代码
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhil		2016/04/16		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getCdiscountOmsNav($key_word){
		$order_nav_list = [
		'同步订单'=>'/order/cdiscount-order/order-sync-info' ,
		'已付款'=>'/order/cdiscount-order/list?order_status=200&pay_order_type=pending' ,
		'发货中'=>'/order/cdiscount-order/list?order_status=300' ,
		'已完成'=>'/order/cdiscount-order/list?order_status=500' ,
		];
	
		$order_nav_active_list = [
		'同步订单'=>'' ,
		'已付款'=>'200' ,
		'发货中'=>'300' ,
		'已完成'=>'500' ,
		];
	
		$NavHtmlStr = '<ul class="main-tab">';
	
		$mappingOrderNav = array_flip($order_nav_active_list);
		foreach($order_nav_list as $label=>$thisUrl){
			$NavActive='';
	
			//$_REQUEST['order_status']
			if (isset($key_word)){
				if (empty($key_word) &&  !empty($mappingOrderNav[$key_word]) &&$mappingOrderNav[$key_word] == $label && \yii::$app->controller->action->id == 'order-sync-info'){
					$NavActive = " active ";
				}else
				if (!empty($key_word) &&  !empty($mappingOrderNav[$key_word]) &&$mappingOrderNav[$key_word] == $label && \yii::$app->controller->action->id != 'order-sync-info' ) {
					$NavActive = " active ";
				}
			}else{
				//$NavActive = " active ";
			}
			$NavHtmlStr .= '<li class="'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></li>';
			/* $NavHtmlStr .= '<div class="pull-left col-md-2">
			 <div class="rectangle-content'.$NavActive.'"><p class="p-rectangle-content'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></p></div>
			<div class="triangle-right'.$NavActive.'"></div>
			</div>';
			*/
		}
		$NavHtmlStr.='</ul>';
	
	
		return $NavHtmlStr;
	
	}//end of getOrderNav
	
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取新绑定帐号一个月内的订单
	 +---------------------------------------------------------------------------------------------
	 * @description			获取一个月内的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015-7-28			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronAutoFetchNewAccountOrderList(){
		try {
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));//600 m is test ,real value is 30
			$days_ago = date('Y-m-d H:i:s',strtotime('-180 days'));
				
			$SAASCDISCOUNTUSERLIST = SaasCdiscountUser::find()->where("is_active='1' and initial_fetched_changed_order_since is null or initial_fetched_changed_order_since='0000-00-00 00:00:00' or last_order_success_retrieve_time='0000-00-00 00:00:00' ")->all();
				
			//retrieve orders  by  each cdiscount account
			foreach($SAASCDISCOUNTUSERLIST as $cdiscountAccount ){
				$uid = $cdiscountAccount['uid'];
				//update token //liang 2015-07-14
				$cdiscountAccount = self::setCdiscountUserToken($cdiscountAccount);
				//清除上次的错误log
				$cdiscountAccount->order_retrieve_message = '';
				
				if(empty($cdiscountAccount->token)){
					//获取token出了问题
					echo "\n get token failed;";
					$cdiscountAccount->order_retrieve_message = '获取token出现问题，请等待一段时间，或联系客服';
					$cdiscountAccount->save(false);
					continue;
				}
				//获取店铺名
				if(empty($cdiscountAccount->shopname)){
					$shop = CdiscountOfferSyncHelper::getSellerInfo($cdiscountAccount->token);
					if(isset($shop['proxyResponse']['success']) && isset($shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName']))
						$cdiscountAccount->shopname = $shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName'];
					else 
						echo "\n *****   call api getSellerInfo false!!!!  ******";
					$cdiscountAccount->save(false);
				}
				
				echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
				if (empty($uid)){
				//异常情况
					$message = "site id :".$cdiscountAccount['site_id']." uid:0";
					echo "\n ".$message;
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
					continue;
				}
		

				$updateTime = TimeUtil::getNow();
				//update this cdiscount account as last order retrieve time
				$cdiscountAccount->last_order_retrieve_time = $updateTime;
		
				
				// for 中断再get
				if(!empty($cdiscountAccount->last_order_success_retrieve_time) && $cdiscountAccount->last_order_success_retrieve_time != '0000-00-00 00:00:00'){
				    // initial_fetched_changed_order_since , routine_fetched_changed_order_from两个字段都可以，
				    // 但由于initial_fetched_changed_order_since 需要设置null才能进入这里，所以重启的第一次不能用这个字段
				    $dateSince = date("Y-m-d\TH:i:s" ,strtotime($cdiscountAccount->routine_fetched_changed_order_from)-1800);
				}else{
				    $dateSince = date("Y-m-d\TH:i:s" ,$days_ago);// test 8hours //1个月前
				}
				
				
				//start to get unfulfilled orders
				echo "\n".$updateTime." start to get $uid unfufilled order for ".$cdiscountAccount['store_name']." since $dateSince \n"; //ystest
				
				$getOrderCount = 0;
				
				$recentGetOrderTime = $dateSince;
				
				//账号订单同步锁
				$mark = CdiscountOrderInterface::markSaasAccountOrderSynching($cdiscountAccount, 'N');
				if(!$mark['success'])
					echo "\n mark account synching error:".$mark['message'];
				$has_break = false;//是否跳出了循环
				$break_msg = '';//跳出循环时的信息
				
				do{
					$startTime = $recentGetOrderTime;
					$recentGetOrderTime = date("Y-m-d\TH:i:s" ,strtotime($recentGetOrderTime)+3600*24*1);//get 2 day one time
					$endTime = $recentGetOrderTime;
					
					$orders = self::_getAllOrdersSince($cdiscountAccount['token'], $startTime,$endTime,$newbinding =true);
					
					if (empty($orders['success'])){
						$has_break = true;
						$break_msg = "fail to connect proxy :".$orders['message'];
						echo "\n $break_msg";
						//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',$break_msg,"edb\global");
						$cdiscountAccount->save();
						//账号订单同步解锁
						$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount, 'F', 0, $orders['message']);
						if(!$mark['success'])
							echo "\n mark account sync finished error:".$mark['message'];
						
						break;
					}
					
					/*
					 if(is_array($orders['proxyResponse']['xml'])) print_r($orders['proxyResponse']['xml']);
					else echo $orders['proxyResponse']['xml'];
					*/
					
					echo "\n".TimeUtil::getNow()." got results, start to insert oms \n"; //ystest
					//accroding to api respone  , update the last retrieve order time and last retrieve order success time
					if(isset($orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'])){
						$has_break = true;
						$break_msg = "api return faultstring:".$orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
						echo "\n $break_msg";
						//账号订单同步解锁
						$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount, 'F', 0, $orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring']);
						if(!$mark['success'])
							echo "\n mark account sync finished error:".$mark['message'];
						
						break;
					}
					if (!empty ($orders['proxyResponse']['success'])){
						//print_r($orders);//liang test
	
						//sync cdiscount info to cdiscount order table
						if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']))
						{
							$rtn=self::_InsertCdiscountOrder($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'] , $cdiscountAccount);
							
							echo "\n uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$cdiscountAccount['token'];
							$getOrderCount += count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']);
							
							//\Yii::info(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$cdiscountAccount['token']],"edb\global");
							
							if(!empty($rtn['success'])){
								$cdiscountAccount->initial_fetched_changed_order_since = $endTime;
								$cdiscountAccount->routine_fetched_changed_order_from = $endTime;
// 								$cdiscountAccount->last_order_success_retrieve_time = $endTime;
							}else{
								$cdiscountAccount->initial_fetched_changed_order_since = $endTime;
								$cdiscountAccount->routine_fetched_changed_order_from = $endTime;
								$cdiscountAccount->order_retrieve_message = $rtn['message'];
							}
						
						}else{
							if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OperationSuccess']) &&
							$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OperationSuccess']=='true'
									){
								echo "\n get none order";
								//update last order success retrieve time of this cdiscount account
								$cdiscountAccount->initial_fetched_changed_order_since = $endTime;
								$cdiscountAccount->routine_fetched_changed_order_from = $endTime;
// 								$cdiscountAccount->last_order_success_retrieve_time = $endTime;
								$cdiscountAccount->order_retrieve_message = 'get non order';
							}
							else{
								$has_break = true;
								$break_msg = "api OperationSuccess=false: ".
								(empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])
									?"ErrorMessage lost,api may have problem!"
									:$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage']
								);
								echo "\n $break_msg";
								
								if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])){
									if(stripos($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'],'Vendeur')===false){
										
									}else 
										$cdiscountAccount->is_active = 3;//店铺被封
								}
								
								$cdiscountAccount->order_retrieve_message = $break_msg;
								//如果有错误,就将order_retrieve_message记录为最近,使下次更新时优先级降低
								$cdiscountAccount->last_order_retrieve_time = TimeUtil::getNow();
								
								//账号订单同步解锁
								$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount, 'F', 0, $break_msg);
								if(!$mark['success'])
									echo "\n mark account sync finished error:".$mark['message'];
								
								break;
							}
						}//end of GetOrderListResult empty or not
						//update last order success retrieve time of this cdiscount account
					}else{
						//print_r($orders);
						$has_break = true;
						$break_msg = empty($orders['proxyResponse']['message'])?'proxy error : any respone message':'proxy error :'.$orders['proxyResponse']['message'];
						echo "\n $break_msg";
						//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  :".$break_msg,"edb\global");
						
						//账号订单同步解锁
						$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount, 'F', 0, $break_msg);
						if(!$mark['success'])
							echo "\n mark account sync finished error:".$mark['message'];
						
						break;
					}
					//end of getting orders from cdiscount server
					
					if (!empty ($orders['proxyResponse']['message'])){
						$cdiscountAccount->order_retrieve_message .= $orders['proxyResponse']['message'];
					}
					
					if (!$cdiscountAccount->save()){
						echo "\n failure to save cdiscount account info ,errors:";
						echo "\n uid:".$cdiscountAccount['uid']." error:". print_r($cdiscountAccount->getErrors(),true);
						//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"failure to save cdiscount operation info ,uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true)],"edb\global");
						
						$has_break = true;
						$break_msg = "failure to save cdiscount account info";
						$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount, 'F', 0, $break_msg);
						if(!$mark['success'])
							echo "\n mark account sync finished error:".$mark['message'];
						
						break;
					}else{
						echo "\n CdiscountAccount model save !";
					}
				}while ($recentGetOrderTime < $updateTime );
				
				if(!$has_break){
					$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'C', $getOrderCount);
					if(!$mark['success'])
						echo "\n mark account sync finished error:".$mark['message'];
				}
				DashBoardHelper::initCountPlatformOrderStatus($uid);
			}//end of each cdiscount user account
		}
		catch (\Exception $e) {
			echo "\n cronAutoFetchNewAccountOrderList Exception:".$e->getMessage();
			//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取最近订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $job_id		job action number
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			获取某个时间之后状态变化的订单
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2014/12/03	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronAutoFetchRecentOrderList($job_id,$the_day=''){
		//set runtime log
		if(empty($the_day))
			$the_day=date("Y-m-d",time());
		$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
		$record = $command->queryOne();
		if(!empty($record)){
			$run_times = json_decode($record['addinfo2'],true);
			if(!is_array($run_times))
				$run_times = [];
			$run_times['enter_times'] = empty($run_times['enter_times'])?1 : $run_times['enter_times']+1;
			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'   where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
			$command = Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
		}else{
			$run_times = ['enter_times'=>1,'end_times'=>0];
			$sql = "INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('CDOMS','runtime-GetOrder','normal','','job_id=".$job_id."','".json_encode($run_times)."','".$the_day."','".date("Y-m-d H:i:s",time())."')";
			$command = Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
		}//set runtime log end
		if(empty($affect_record))
			echo "\n set runtime log falied, sql:".$sql;
		
		try {				
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));//600 m is test ,real value is 30
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_cdiscount_user set last_order_success_retrieve_time='0000-00-00 00:00:00',last_order_retrieve_time='0000-00-00 00:00:00'  
									where last_order_success_retrieve_time is null or last_order_retrieve_time is null"  );
			$affectRows = $command->execute();
			
			
			$saas_query = SaasCdiscountUser::find()
				->where("is_active='1' and initial_fetched_changed_order_since is not null and last_order_success_retrieve_time<'$half_hours_ago'")
				// ->andwhere(" uid%20=$job_id ") // TODO 开启CD多进程时候做相应去注释
				->andWhere(" sync_status<>'R' or sync_status is null");
			
			$account_count = $saas_query->orderBy("last_order_success_retrieve_time asc")->count();
			
			$handled_account_count = 0;
			//retrieve orders  by  each cdiscount account
			do{
				$account_query =  SaasCdiscountUser::find()
					->where("is_active='1' and initial_fetched_changed_order_since is not null and last_order_success_retrieve_time<'$half_hours_ago'")
					// ->andwhere(" uid%20=$job_id ") // TODO 开启CD多进程时候做相应去注释
					->andWhere(" sync_status<>'R' or sync_status is null");
				
				//$sqlSyntax = $account_query->createCommand()->getRawSql();
				//echo "\n".$sqlSyntax."\n";
				$cdiscountAccount = $account_query->orderBy("last_order_retrieve_time asc")->one();
				
				$handled_account_count ++;
				
				if(empty($cdiscountAccount))
					continue;
				try{
					//清除上次的错误log
					$cdiscountAccount->order_retrieve_message = '';
					
					$uid = $cdiscountAccount['uid'];
					//获取token次数太多会被CD列入黑名单，因此只尝试几次。
					$addi_info = $cdiscountAccount['addi_info'];
					if(!empty($addi_info))
						$addi_info = json_decode($addi_info,true);
					else 
						$addi_info = [];
					
					if(!empty($addi_info['token_expired'])){
						if(empty($addi_info['token_fetch_times']) || $addi_info['token_fetch_times']<3){
							$cdiscountAccount = self::setCdiscountUserToken($cdiscountAccount);
							if(empty($cdiscountAccount->token)){
								//获取token出了问题
								echo "\n get token failed;";
								$cdiscountAccount->order_retrieve_message = '获取token出现问题，请等待一段时间，或联系客服';
								$cdiscountAccount->last_order_retrieve_time = TimeUtil::getNow();
								$addi_info['token_expired'] = true;
								if(empty($addi_info['token_fetch_times']))
									$addi_info['token_fetch_times'] = 1;
								else 
									$addi_info['token_fetch_times'] += 1;
								$cdiscountAccount->addi_info = json_encode($addi_info);
								if(!$cdiscountAccount->save()){
									echo "\n CdiscountOrderHelper::cronAutoFetchRecentOrderList model save false:";
									print_r($cdiscountAccount->getErrors(),true);
								}
							}
						}else{
							echo "\n uid =$uid, store_name=".$cdiscountAccount['store_name']." token has expired, and fetch token time > 3, skip this account;";
							$cdiscountAccount->last_order_retrieve_time = TimeUtil::getNow();
							$cdiscountAccount->order_retrieve_message = 'token已过期，请检测绑定信息中的账号，密码是否正确。';
							if(!$cdiscountAccount->save()){
								echo "\n CdiscountOrderHelper::cronAutoFetchRecentOrderList model save false:";
								print_r($cdiscountAccount->getErrors(),true);
							}
						}
					}else{
						$cdiscountAccount = self::setCdiscountUserToken($cdiscountAccount);
					}
					
					
					
					//update token //liang 2015-07-14
					/*
					$cdiscountAccount = self::setCdiscountUserToken($cdiscountAccount);
					
					if(empty($cdiscountAccount->token)){
						//获取token出了问题
						echo "\n get token failed;";
						$cdiscountAccount->order_retrieve_message = '获取token出现问题，请等待一段时间，或联系客服';
						$cdiscountAccount->last_order_retrieve_time = TimeUtil::getNow();
						$cdiscountAccount->save(false);
					}
					*/
					if( strtotime($cdiscountAccount->token_expired_date) < strtotime("-1 days") ){
						$cdiscountAccount->order_retrieve_message = 'token已过期，请检测绑定信息中的 账号，密码是否正确。';
						$cdiscountAccount->last_order_retrieve_time = TimeUtil::getNow();
						if (!$cdiscountAccount->save()){
							echo "\n failure to save cdiscount account info ,error:";
							echo "\n uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true);
							//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"failure to save cdiscount operation info ,uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true)],"edb\global");
						}else{
							echo "\n CdiscountAccount model save(token expired) !";
						}
						continue;
					}
					
					//获取店铺名
					if(empty($cdiscountAccount->shopname)){
						$shop = CdiscountOfferSyncHelper::getSellerInfo($cdiscountAccount->token);
						if(isset($shop['proxyResponse']['success']) && isset($shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName']))
							$cdiscountAccount->shopname = $shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName'];
						$cdiscountAccount->save(false);
					}
					
					$updateTime =  TimeUtil::getNow();
					
					echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
					if (empty($uid)){
						//异常情况 
						$message = "site id :".$cdiscountAccount['site_id']." uid:0";
						echo "\n ".$message;
						//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");					
						continue;
					}

					
					$onwTimeUTC = date("Y-m-d\TH:i:s");// not UTC time, local time(UTC+8) can get more order
					$sinceTimeUTC = date("Y-m-d\TH:i:s" ,strtotime($cdiscountAccount->last_order_success_retrieve_time)-1800);
					//统计15日内产品售出数量
					echo "\n Start to calculateSalesWithin15Days: ";
					self::calculateSalesWithin15Days($cdiscountAccount['store_name'], $cdiscountAccount['username'],$updateTime);
					
					//由于bug或其他原因导致账号长时间(超过2日)没有成功获取到订单的情况
					if( strtotime($cdiscountAccount->last_order_success_retrieve_time) < strtotime("-2 days")){
						// $updateTime =  date("Y-m-d H:i:s" ,strtotime($cdiscountAccount->last_order_success_retrieve_time)+3600*6); //上次成功获取订单后一天
						$onwTimeUTC = date("Y-m-d\TH:i:s" ,strtotime($cdiscountAccount->last_order_success_retrieve_time)+3600*6);
						$sinceTimeUTC = date("Y-m-d\TH:i:s" ,strtotime($cdiscountAccount->last_order_success_retrieve_time)-1800);
					}
					
					
					$getOrderCount = 0;
					//update this cdiscount account as last order retrieve time
					$cdiscountAccount->last_order_retrieve_time = $updateTime;	
								
					if (empty($cdiscountAccount->last_order_success_retrieve_time) or $cdiscountAccount->last_order_success_retrieve_time=='0000-00-00 00:00:00'){
						//如果还没有初始化完毕，就什么都不do
						echo "\n uid=$uid haven't initial_fetched !";
					}else{
						
						$mark = CdiscountOrderInterface::markSaasAccountOrderSynching($cdiscountAccount,'R');
						if(!$mark['success'])
							echo "\n mark account sync finished error:".$mark['message'];
						
						//start to get unfulfilled orders
						echo "\n".TimeUtil::getNow()." start to get $uid unfufilled order for ".$cdiscountAccount['store_name']." since $sinceTimeUTC \n"; //ystest
						
						$sinceTime_FR = date("Y-m-d\TH:i:s" , strtotime($sinceTimeUTC)-3600*7 );
						$onwTime_FR = date("Y-m-d\TH:i:s" , strtotime($onwTimeUTC)-3600*6 );
						$orders = self::_getAllOrdersSince($cdiscountAccount['token'], $sinceTime_FR, $onwTime_FR,$newbinding =false);
						
						if (empty($orders['success'])){
							echo "\n fail to connect proxy  :".print_r($orders['message'],true);
							//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
							$cdiscountAccount->save();
							
							$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,"fail to connect proxy  :".print_r($orders['message'],true));
							if(!$mark['success'])
								echo "\n mark account sync finished error:".$mark['message'];
							
							continue;
						}
						/*
						 if(is_array($orders['proxyResponse']['xml'])) print_r($orders['proxyResponse']['xml']);
						else echo $orders['proxyResponse']['xml'];
						*/
						
						//echo "\n".TimeUtil::getNow()." got results, start to insert oms \n"; //ys test
						//accroding to api respone  , update the last retrieve order time and last retrieve order success time
						if(isset($orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'])){
							echo "\n".$orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
							$cdiscountAccount->order_retrieve_message = $orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
						}
						if (!empty ($orders['proxyResponse']['success'])){
							//print_r($orders); //liang test
							if(isset($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']))
								echo "\n isset ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
							//sync cdiscount info to cdiscount order table
							if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])){
								echo "\n !empty ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
								//print_r($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'][0]);
								
								$getOrderCount = count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']);
								
								$rtn = self::_InsertCdiscountOrder($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'] , $cdiscountAccount,$the_day);
								//\Yii::info(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$cdiscountAccount['token']],"edb\global");
								
								if($rtn['success']){
									//update last order success retrieve time of this cdiscount account
									$cdiscountAccount->last_order_success_retrieve_time = $updateTime;
								}
							}else{
								if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OperationSuccess']) && 
									($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OperationSuccess']=='true' || 
									$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OperationSuccess']==true)
								){
									echo "\n get none order";
									//update last order success retrieve time of this cdiscount account
									$cdiscountAccount->last_order_success_retrieve_time = $updateTime;
								}
								else{
									echo "\n OperationSuccess=false:".
									(empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])
										?"ErrorMessage lost, api may have problem"
										:$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage']
									);
									
								if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])){
									if(stripos($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'],'Vendeur')===false){
										
									}else 
										$cdiscountAccount->is_active = 3;//店铺被封
								}
									
									$cdiscountAccount->order_retrieve_message = "OperationSuccess=false:".(empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])?'':$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage']);
								}
								
							}//end of GetOrderListResult empty or not
								
						}else{
							//print_r($orders);
							if (!empty ($orders['proxyResponse']['message'])){
								echo "\n uid = $uid proxy error  :".$orders['proxyResponse']['message'].$cdiscountAccount['token'];
								//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  :".$orders['proxyResponse']['message'].$cdiscountAccount['token'] ],"edb\global");
							}else{
								echo "\n uid = $uid proxy error : any respone message ".$cdiscountAccount['token'];
								//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  : not any respone message".$cdiscountAccount['token']],"edb\global");
							}
							
						}
						//end of getting orders from cdiscount server
						
						if (!empty ($orders['proxyResponse']['message'])){
							$cdiscountAccount->order_retrieve_message .= $orders['proxyResponse']['message'];
						}
						
						//如果有错误,就将order_retrieve_message记录为最近,使下次更新时优先级降低
						if(!empty($cdiscountAccount->order_retrieve_message))
							$cdiscountAccount->last_order_retrieve_time = TimeUtil::getNow();
						
						if (!$cdiscountAccount->save()){
							echo "\n failure to save cdiscount account info ,error:";
							echo "\n uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true);
							//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"failure to save cdiscount operation info ,uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true)],"edb\global");
						}else{
							echo "\n CdiscountAccount model save !";
						}
						if(empty($cdiscountAccount->order_retrieve_message)){
							$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'C',$getOrderCount);
						}else{
							$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,$cdiscountAccount->order_retrieve_message);
						}
						if(!$mark['success'])
							echo "\n mark account sync finished error:".$mark['message'];
						
						DashBoardHelper::initCountPlatformOrderStatus($uid);
					}
				}catch (\Exception $e) {
					echo "\n do while Exception:".$e->getMessage();
					$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,$e->getMessage());
					if(!$mark['success'])
						echo "\n mark account sync finished error:".$mark['message'];
				}
			}while ($handled_account_count < $account_count);//end of each cdiscount user account
			
			
			return array('success'=>true,'message'=>''); 
		} catch (\Exception $e) {
			echo "\n cronAutoFetchRecentOrderList Exception:".$e->getMessage();
			return array('success'=>false,'message'=>"cronAutoFetchRecentOrderList Exception:".$e->getMessage());
			//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
			
	}//end of cronAutoFetchUnFulfilledOrderList
	
	
	private static function _syncOrder($cdiscountAccount,$sinceTime,$toTime){
		$rtn = ['success'=>true,'message'=>''];
		$uid = $cdiscountAccount['uid'];
	
		$cdiscountAccount = self::setCdiscountUserToken($cdiscountAccount);
		if(empty($cdiscountAccount->token)){
			//获取token出了问题
			echo "\n get token failed;";
			$cdiscountAccount->order_retrieve_message = '获取token出现问题，请等待一段时间，或联系客服';
			$cdiscountAccount->save(false);
			return ['success'=>false,'message'=>'get token failed'];
		}
		if( strtotime($cdiscountAccount->token_expired_date) < strtotime("-1 days") ){
			$cdiscountAccount->order_retrieve_message = 'token已过期，请检测绑定信息中的 账号，密码是否正确。';
			if (!$cdiscountAccount->save()){
				echo "\n failure to save cdiscount account info ,error:";
				echo "\n uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true);
				return ['success'=>false,'message'=>'token expired! failure to save cdiscountAccount'];
				//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"failure to save cdiscount operation info ,uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true)],"edb\global");
			}else{
				echo "\n CdiscountAccount model save(token expired) !";
				return ['success'=>false,'message'=>'token expired! cdiscountAccount model save'];
			}
		}
		
		//获取店铺名
		if(empty($cdiscountAccount->shopname)){
			$shop = CdiscountOfferSyncHelper::getSellerInfo($cdiscountAccount->token);
			if(isset($shop['proxyResponse']['success']) && isset($shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName']))
				$cdiscountAccount->shopname = $shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName'];
			$cdiscountAccount->save(false);
		}
		
		$updateTime =  TimeUtil::getNow();
		
		echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
		if (empty($uid)){
			//异常情况
			$message = "site id :".$cdiscountAccount['site_id']." uid:0";
			echo "\n ".$message;
			//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			return ['success'=>false,'message'=>$message];
		}

		
		$toTimeUTC = date("Y-m-d\TH:i:s",strtotime($toTime));// not UTC time, local time(UTC+8) can get more order
		$sinceTimeUTC = date("Y-m-d\TH:i:s" ,strtotime($sinceTime)-3600*8);
		//统计15日内产品售出数量
		echo "\n Start to calculateSalesWithin15Days: ";
		self::calculateSalesWithin15Days($cdiscountAccount['store_name'], $cdiscountAccount['username'],$updateTime);

		$getOrderCount = 0;
		//update this cdiscount account as last order retrieve time
		$cdiscountAccount->last_order_retrieve_time = $updateTime;
			
		if (empty($cdiscountAccount->last_order_success_retrieve_time) or $cdiscountAccount->last_order_success_retrieve_time=='0000-00-00 00:00:00'){
			//如果还没有初始化完毕，就什么都不do
			echo "\n uid=$uid haven't initial_fetched !";
			$cdiscountAccount->order_retrieve_message = "account haven't initial_fetched";
			$cdiscountAccount->save();
			return ['success'=>false,'message'=>"account haven't initial_fetched"];
		}else{
			
			$mark = CdiscountOrderInterface::markSaasAccountOrderSynching($cdiscountAccount,'R');
			if(!$mark['success']){
				echo "\n mark account synching error:".$mark['message'];
				return ['success'=>false,'message'=>"mark account synching error"];
			}
			//start to get unfulfilled orders
			echo "\n".TimeUtil::getNow()." start to get $uid unfufilled order for ".$cdiscountAccount['store_name']." since $sinceTimeUTC \n"; //ystest
						
			$orders = self::_getAllOrdersSince($cdiscountAccount['token'], $sinceTimeUTC, $toTimeUTC, $newbinding =false);
					
			if (empty($orders['success'])){
				echo "\n fail to connect proxy :".$orders['message'];
				//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
				$cdiscountAccount->order_retrieve_message = 'fail to connect proxy';
				$cdiscountAccount->save();

				$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,"fail to connect proxy  :".$orders['message']);
				if(!$mark['success']){
					echo "\n mark account sync finished error:".$mark['message'];
					return ['success'=>false,'message'=>"mark account synching error"];
				}
			}
							
			//echo "\n".TimeUtil::getNow()." got results, start to insert oms \n"; //ys test
			//accroding to api respone  , update the last retrieve order time and last retrieve order success time
			if(isset($orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'])){
				echo "\n".$orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
				$cdiscountAccount->order_retrieve_message = $orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
				$cdiscountAccount->save();
				$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,$cdiscountAccount->order_retrieve_message);
				if(!$mark['success']){
					echo "\n mark account sync finished error:".$mark['message'];
					return ['success'=>false,'message'=>"mark account synching error"];
				}
				return ['success'=>false,'message'=>$cdiscountAccount->order_retrieve_message];
			}
			if (!empty ($orders['proxyResponse']['success'])){
				//print_r($orders); //liang test
				if(isset($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']))
					echo "\n isset ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
					//sync cdiscount info to cdiscount order table
				if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])){
					echo "\n !empty ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
					//print_r($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'][0]);
			
					$getOrderCount = count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']);
				
					$rtn = self::_InsertCdiscountOrder($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'] , $cdiscountAccount);
					//\Yii::info(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$cdiscountAccount['token']],"edb\global");
				
					if($rtn['success']){
						//update last order success retrieve time of this cdiscount account
						$cdiscountAccount->last_order_success_retrieve_time = $updateTime;
					}
				}else{
					if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OperationSuccess']) &&
						$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OperationSuccess']=='true'
					){
						echo "\n get none order";
						//update last order success retrieve time of this cdiscount account
						$cdiscountAccount->last_order_success_retrieve_time = $updateTime;
					}
					else{
						echo "\n OperationSuccess=false:".$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'];
						
						if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])){
							if(stripos($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'],'Vendeur')===false){
								
							}else 
								$cdiscountAccount->is_active = 3;//店铺被封
						}
						
						$cdiscountAccount->order_retrieve_message = "OperationSuccess=false:".(empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])?'':$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage']);
					}
				}//end of GetOrderListResult empty or not
							
			}else{
				//print_r($orders);
				if (!empty ($orders['proxyResponse']['message'])){
					echo "\n uid = $uid proxy error  :".$orders['proxyResponse']['message'].$cdiscountAccount['token'];
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  :".$orders['proxyResponse']['message'].$cdiscountAccount['token'] ],"edb\global");
				}else{
					echo "\n uid = $uid proxy error : any respone message ".$cdiscountAccount['token'];
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  : not any respone message".$cdiscountAccount['token']],"edb\global");
				}
		
			}
			//end of getting orders from cdiscount server
				
			if (!empty ($orders['proxyResponse']['message'])){
				$cdiscountAccount->order_retrieve_message = $orders['proxyResponse']['message'];
			}

			//如果有错误,就将order_retrieve_message记录为最近,使下次更新时优先级降低
			if(!empty($cdiscountAccount->order_retrieve_message))
				$cdiscountAccount->last_order_retrieve_time = TimeUtil::getNow();
			
			if (!$cdiscountAccount->save()){
				echo "\n failure to save cdiscount account info ,error:";
				echo "\n uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true);
				//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"failure to save cdiscount operation info ,uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true)],"edb\global");
			}else{
				echo "\n CdiscountAccount model save !";
			}
			if(empty($cdiscountAccount->order_retrieve_message)){
				$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'C',$getOrderCount);
			}else{
				$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,$cdiscountAccount->order_retrieve_message);
			}
			if(!$mark['success'])
				echo "\n mark account sync finished error:".$mark['message'];
		}
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * cdiscount api 获取的订单数组 保存到订单模块中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $orders				cdiscount api 返回的结果
	 * @param $cdiscountAccount		cdiscount user model 
	 * @param $the_day				job启动当前日期
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 * @description			cdiscount order  调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl	2014/12/09		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function _InsertCdiscountOrder($orders , $cdiscountAccount , $the_day=''){
		//echo "\n YS0.0 Start to insert order "; //ystest
		try {
			
			$today = date("Y-m-d",time());
			$currentHour = substr(TimeUtil::getNow(),11,2);
			//删除v2表里面不存在的原始订单
			//比较耗资源，改为每日只于0-1点之间进行
			if($today=='2016-12-26' && $currentHour<=15){
				echo "\n conversion : delete source order that have no order_v2 record;";
				$command = Yii::$app->subdb->createCommand("delete  FROM `cdiscount_order` WHERE `ordernumber` not in (select order_source_order_id from od_order_v2 where order_source='cdiscount') ");
				$command->execute();
			}
			
			//删除只写了单头烂单
			//比较耗资源，改为制定日期和钟点之间进行
			if($today=='2016-12-22' && $currentHour<=23){
				echo "\n conversion : delete order_v2 that have no order_item_v2 record;";
				$command = Yii::$app->subdb->createCommand("delete  FROM `od_order_v2` WHERE `order_id` not in (select order_id from od_order_item_v2 where 1) and order_source='cdiscount'");
				$command->execute();
			}
			
			$src_insert_success=0;
			$src_insert_failed=0;
			$src_detail_insert_success=0;
			$src_detail_insert_failed=0;
			
			$src_update_success=0;
			$src_update_failed=0;
			$src_detail_update_success=0;
			$src_detail_update_failed=0;
			
			$oms_insert_success=0;
			$oms_update_success=0;
			$oms_insert_failed=0;
			$oms_update_failed=0;
			
			$err_type=[
				'1'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>''],//保存原单失败
				'2'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>''],//保存原单商品详情失败
				'3'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>''],//写入OMS失败
				'4'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>''],//update原单失败
				'5'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>''],//update原单商品详情失败
			];

			$src_insert_failed_happend_site = '';
			$src_update_failed_happend_site = '';
			$src_detail_insert_failed_happend_site ='';
			$src_detail_update_failed_happend_site = '';
			
			$oms_insert_failed_happend_site ='';
			$oms_update_failed_happend_site ='';
			
			$ImportOrderArr = array();
			$tempCdiscountOrderModel = new CdiscountOrder();
			$tempCdiscountOrderDetailModel = new CdiscountOrderDetail();
			$CdiscountOrderModelAttr = $tempCdiscountOrderModel->getAttributes();
			$CdiscountOrderDetailModelAttr = $tempCdiscountOrderDetailModel->getAttributes();
			//print_r($CdiscountOrderModelAttr);
			$rtn['message'] ='';
			$rtn['success'] = true;
			
			//单订单判断
			if(isset($orders['OrderNumber'])){
				$o=$orders;
				$orders=[];
				$orders[]=$o;
			}
			//记录保存失败的订单号
			$insertErrOrder['srcTbale']=[];
			$insertErrOrder['omsTbale']=[];
			$insertErrOrder['uid']=$cdiscountAccount['uid'];
			$insertErrOrder['store_name']=$cdiscountAccount['store_name'];
			
			//同步商品信息原始数组
			$syncProdInfoData=[];
			
			//对这个子库下面的进行所有 平台原始订单，找出来和这次api返回相关的记录，缓存起来，这样1次IO就可以判断n个记录是否已经存在了
			$inCdiscountOrderTable_keyed = array();
			$ids = array();
			foreach($orders as $anOrder){//$anOrder is an object
				//CD出现一些没有订单号的奇怪订单，且OrderState为Filed....这种订单我们不需要
				if(empty($anOrder['OrderNumber']))
					continue;
				$ids[] = (string)$anOrder['OrderNumber'] ;
			}
			
			$inCdiscountOrderTable = CdiscountOrder::find()->where(['ordernumber'=>$ids])->all();
			
			foreach ($inCdiscountOrderTable as $aRec){
				$inCdiscountOrderTable_keyed[(string)$aRec->ordernumber ] = $aRec;
			}
			
			$used_to_od_attrs = [
				'billing_address1',
				'billing_address2',
				'billing_building',
				'billing_transaction_id',
				'billing_city',
				'billing_civility',
				'billing_companyname',
				'billing_country',
				'billing_firstname',
				'billing_instructions',
				'billing_lastname',
				'billing_placename',
				'billing_street',
				'billing_zipcode',
				'customer_customerid',
				'customer_mobilephone',
				'customer_phone',
				'customer_civility',
				'customer_firstname',
				'customer_lastname',
				'hasclaims',
				'lastupdateddate',
				'modifieddate',
				'orderstate',
				'shippedtotalamount',
				'shipping_address1',
				'shipping_address2',
				'shipping_apartmentnumber',
				'shipping_building',
				'shipping_city',
				'shipping_civility',
				'shipping_companyname',
				'shipping_country',
				'shipping_county',
				'shipping_firstname',
				'shipping_instructions',
				'shipping_lastname',
				'shipping_placename',
				'shipping_relayid',
				'shipping_street',
				'shipping_zipcode',
				'shippingcode',
				'status',
				'validatedtotalshippingcharges',
				'validationstatus',
			];
			
			foreach($orders as $anOrder){
				//CD出现一些没有订单号的奇怪订单，且OrderState为Filed....这种订单我们不需要
				if(empty($anOrder['OrderNumber']))
					continue;
				//echo "\n YS0.1 Start to insert order "; //ystest	
				/*
				if ($anOrder['Status'] == 'Completed') //忽略已完成订单
					continue;
				*/
				$OrderParms = array();
				//$orderModel = CdiscountOrder::findOne($anOrder['OrderNumber']);
				//不要对每一个订单进行数据库IO，看内存数据就ok
				$orderModel = isset($inCdiscountOrderTable_keyed[(string)$anOrder['OrderNumber'] ])? $inCdiscountOrderTable_keyed[(string)$anOrder['OrderNumber'] ] : null;
				
				$newCreateOrder = false;
				//echo "\n YS0.2-1   "; //ystest
				if (empty($orderModel)){
					//new order 
					$orderModel = new CdiscountOrder();
					$newCreateOrder = true;
				}else{// not change and order_v2 existing, then skip it
					//$existingOrder = OdOrder::find()->where(['order_source_order_id'=>$anOrder['OrderNumber']])->one();
					//if(!empty($existingOrder)){
						//if (strtotime($anOrder['LastUpdatedDate']) == strtotime($orderModel->lastupdateddate) && 
						//    strtotime($anOrder['ModifiedDate']) == strtotime($orderModel->modifieddate) && 
						//    $anOrder['OrderState']==$orderModel->orderstate ){
						//	continue;
						//}
					//}
				}
		 
				//set order info 
				$orderInfo = array();
				$orderDetailInfo = array();
				//echo "\n YS0.2   "; //ystest
				foreach($anOrder as $key=>$value){
					$key = strtolower($key);
					if ($key=='billingaddress'){
						foreach($value as $subkey=>$subvalue){
							$subkey = strtolower($subkey);
							if (array_key_exists("billing_".$subkey, $CdiscountOrderModelAttr)){
								if(is_array($subvalue)){
									if(!empty($subvalue)) $subvalue=json_encode($subvalue);
									else $subvalue='';
								}
								$orderInfo["billing_".$subkey] = $subvalue;
							}
						}//end of each BillingAddress Detail
						continue;
					}
					
					if ($key=='customer'){
						foreach($value as $subkey=>$subvalue){
							$subkey = strtolower($subkey);
							if (array_key_exists("customer_".$subkey, $CdiscountOrderModelAttr)){
								if(is_array($subvalue)){
									if(!empty($subvalue)) $subvalue=json_encode($subvalue);
									else $subvalue='';
								}
								$orderInfo["customer_".$subkey] = $subvalue;
							}
						}//end of each Customer Detail
						continue;
					}
				 
					if ($key=='shippingaddress'){
						foreach($value as $subkey=>$subvalue){
							$subkey = strtolower($subkey);
							if (array_key_exists("shipping_".$subkey, $CdiscountOrderModelAttr)){
								if(is_array($subvalue)){
									if(!empty($subvalue)) $subvalue=json_encode($subvalue);
									else $subvalue='';
								}
								$orderInfo["shipping_".$subkey] = $subvalue;
							}
						}//end of each ShippingAddress Detail
						continue;
					}
					
					if (array_key_exists($key, $CdiscountOrderModelAttr)){
						if(is_array($value)){
							if(!empty($value)) $value=json_encode($value);
							else $value='';
						}
						$orderInfo[$key] = $value;
						continue;
					}
					
					if ($key=='orderlinelist'){
						if(!empty($value['OrderLine'])){
							if(isset($value['OrderLine']['Sku'])){//单个产品情况
								foreach($value['OrderLine'] as $subkey=>$subvalue){
									$subkey = strtolower($subkey);
									if (array_key_exists($subkey, $CdiscountOrderDetailModelAttr)){
										if($subkey=='name' && is_array($subvalue)) continue;//无效prod name
										if(is_array($subvalue)){
											if(!empty($subvalue)) $subvalue=json_encode($subvalue);
											else $subvalue='';
										}
										$orderDetailInfo[0][$subkey] = $subvalue;
									}
								}
							}else{//订单多个产品
								$i=0;
								foreach($value['OrderLine'] as $orderline){
									foreach($orderline as $subkey=>$subvalue){
										$subkey = strtolower($subkey);
										if (array_key_exists($subkey, $CdiscountOrderDetailModelAttr)){
											if($subkey=='name' && is_array($subvalue)) continue;//无效prod name
											if(is_array($subvalue)){
												if(!empty($subvalue)) $subvalue=json_encode($subvalue);
												else $subvalue='';
											}
											$orderDetailInfo[$i][$subkey] = $subvalue;
										}
									}
									$i++;
								}
							}
						}//end of each Product Detail
						continue;
					}
				}
				//新加字段，记录卖家id liang 2015-12-21
				$orderInfo['seller_id'] = $cdiscountAccount['username'];
				$addinfo = [];
				$addinfo['FBC'] = false;
				if(isset($anOrder['IsCLogistiqueOrder']) && $anOrder['IsCLogistiqueOrder']!=='false')
					$addinfo['FBC'] = true;
				
				//echo "\n YS0.3   "; //ystest
				if(!empty($orderInfo['creationdate']))
					$orderInfo['creationdate'] = date('Y-m-d H:i:s',strtotime($orderInfo['creationdate']));
				if(!empty($orderInfo['lastupdateddate']))
					$orderInfo['lastupdateddate'] = date('Y-m-d H:i:s',strtotime($orderInfo['lastupdateddate']));
				if(!empty($orderInfo['modifieddate']))
					$orderInfo['modifieddate'] = date('Y-m-d H:i:s',strtotime($orderInfo['modifieddate']));
				
				$old_attrs = $orderModel->getAttributes();
				$diff_attrs = array_diff($orderInfo,$old_attrs);
				$toUpdate = false;
				if(!empty($diff_attrs)){
					echo "\n diff_attrs: \n".print_r($diff_attrs,true);
					foreach ($diff_attrs as $k=>$v){
						if(in_array($k, $used_to_od_attrs))
							$toUpdate = true;
					}
				}
				if(empty($toUpdate))
					continue;
				if (!empty($orderInfo)){
					$orderModel->setAttributes($orderInfo);
					$orderModel->updated_time = TimeUtil::getNow();
					echo " \n YS1 try to inset cd order for ".$orderModel->ordernumber;
					//SQLHelper::groupInsertToDb($orderModel->tableName(), array($orderModel->getAttributes()));
					if (  $orderModel->save() ){
						echo "\n save cdiscount order success!";
						if($newCreateOrder)
							$src_insert_success ++;
						else
							$src_update_success ++;
					}else{
						echo "\n failure to save cdiscount order,uid:".$cdiscountAccount['uid']."error:".print_r($orderModel->getErrors(),true);
						$rtn['message'].=(empty($orderModel->ordernumber)?'':$orderModel->ordernumber)."原始订单保存失败,";
						$rtn['success']=false;
						$insertErrOrder['srcTbale'][]=empty($orderModel->ordernumber)?'':$orderModel->ordernumber;
						if($newCreateOrder){
							$src_insert_failed ++;
							$src_insert_failed_happend_site = $cdiscountAccount['site_id'];
							$err_type['1']['times'] +=1;
							$err_type['1']['site_id']=$cdiscountAccount['site_id'];
							$err_type['1']['last_msg']="failure to insert cdiscount order,site_id:".$cdiscountAccount['site_id']."error:".print_r($orderModel->getErrors(),true);
						}
						else{
							$src_update_failed ++;
							$src_update_failed_happend_site =  $cdiscountAccount['site_id'];
							$err_type['4']['times'] +=1;
							$err_type['4']['site_id']=$cdiscountAccount['site_id'];
							$err_type['4']['last_msg']="failure to update cdiscount order,site_id:".$cdiscountAccount['site_id']."error:".print_r($orderModel->getErrors(),true);
						}
						continue;
						//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"failure to save cdiscount order,uid:".$cdiscountAccount['uid']."error:". print_r($orderModel->getErrors(),true)],"edb\global");
					}
				}else{
					echo 'failure to save cdiscount order,orderInfo lost!';
					$rtn['message'].="原始订单信息缺失";
					$rtn['success']=false;
					if($newCreateOrder){
						$src_insert_failed++;
						$src_insert_failed_happend_site=$cdiscountAccount['site_id'];
						$err_type['1']['times'] +=1;
						$err_type['1']['site_id']=$cdiscountAccount['site_id'];
						$err_type['1']['last_msg']='failure to insert cdiscount order,orderInfo lost!';
					}
					else{
						$src_update_failed ++;
						$src_update_failed_happend_site =  $cdiscountAccount['site_id'];
						$err_type['4']['times'] +=1;
						$err_type['4']['site_id']=$cdiscountAccount['site_id'];
						$err_type['4']['last_msg']='failure to update cdiscount order,orderInfo lost!';
					}
					
					continue;
				}
			 	
			 	$delivery_date_min = 0;
			 	$NonDeliverySku = CdiscountOrderInterface::getNonDeliverySku();
				//save order detail 
				if (!empty($orderDetailInfo)){
					//save order details's prod to offer list table if details have new prod that table hadn't,
					//self::saveOrderDetailToOfferListIfIsNew($orderDetailInfo,$cdiscountAccount);
					$syncProdInfoData[]=$orderDetailInfo;
					
					if ($newCreateOrder){
						foreach ($orderDetailInfo as $aDelail){
							if(!in_array($aDelail['productid'],$NonDeliverySku)){
								if(empty($delivery_date_min) || $delivery_date_min>strtotime($aDelail['deliverydatemin']))
									$delivery_date_min = strtotime($aDelail['deliverydatemin']);
							}
								
							$aDelail['ordernumber'] = $anOrder['OrderNumber'];
							$orderDetails = new CdiscountOrderDetail();
							$orderDetails->setAttributes ($aDelail);
							if (!$orderDetails->save()){
								echo "\n failure to save cdiscount order details ,uid:".$cdiscountAccount['uid']."error:". print_r($orderDetails->getErrors(),true);
								$rtn['message'].=$anOrder['OrderNumber']."保存原始订单商品失败;";
								$rtn['success']=false;
								$insertErrOrder['srcTbale'][]=empty($anOrder['OrderNumber'])?'':$anOrder['OrderNumber'];
								
								$src_detail_insert_failed ++;
								$src_detail_insert_failed_happend_site= $cdiscountAccount['site_id'];
								$err_type['2']['times'] +=1;
								$err_type['2']['site_id']=$cdiscountAccount['site_id'];
								$err_type['2']['last_msg']='failure to insert cdiscount order detail ,insert to db failed!';
								
								continue;
							}else{
								echo "\n save cdiscount order detail success!";
								$src_detail_insert_success ++;
							}
						}
					}
					else{
						foreach ($orderDetailInfo as $aDelail){
							if(!in_array($aDelail['productid'],$NonDeliverySku)){
								if(empty($delivery_date_min) || $delivery_date_min>strtotime($aDelail['deliverydatemin']))
									$delivery_date_min = strtotime($aDelail['deliverydatemin']);
							}
							
							$orderDetails = CdiscountOrderDetail::find()->where(['ordernumber'=>$anOrder['OrderNumber'],'sku'=>$aDelail['sku'] ])->one();
							if (empty($orderDetails)){
								$orderDetails = new CdiscountOrderDetail();
							}
							$orderDetails->setAttributes ($aDelail);
							if (!$orderDetails->save()){
								echo "\n failure to save cdiscount order details ,uid:".$cdiscountAccount['uid']."error:". print_r($orderDetails->getErrors());
								$rtn['message'].=$anOrder['OrderNumber']."更新原始订单商品失败;";
								$rtn['success']=false;
								$insertErrOrder['srcTbale'][]=empty($anOrder['OrderNumber'])?'':$anOrder['OrderNumber'];
								
								$src_detail_update_failed++;
								$src_detail_update_failed_happend_site=$cdiscountAccount['site_id'];
								$err_type['5']['times'] +=1;
								$err_type['5']['site_id']=$cdiscountAccount['site_id'];
								$err_type['5']['last_msg']='failure to update cdiscount order detail ,update to db failed!';
								
								continue;
							}else{
								echo "\n save cdiscount order detail success!";
								$src_detail_update_success++;
							}
						}
					}
				}else{
					echo 'failure to save cdiscount order detail, orderDetailInfo lost!';
					$rtn['message'].="原始订单商品信息丢失;";
					$rtn['success']=false;
					$insertErrOrder['srcTbale'][]=empty($orderModel->ordernumber)?'':$orderModel->ordernumber;
					if ($newCreateOrder){
						$src_detail_insert_failed ++;
						$src_detail_insert_failed_happend_site = $cdiscountAccount['site_id'];
						$err_type['2']['times'] +=1;
						$err_type['2']['site_id']=$cdiscountAccount['site_id'];
						$err_type['2']['last_msg']='failure to insert cdiscount order detail, detail info lost!';
					}
					else{
						$src_detail_update_failed++;
						$src_detail_update_failed_happend_site = $cdiscountAccount['site_id'];
						$err_type['5']['times'] +=1;
						$err_type['5']['site_id']=$cdiscountAccount['site_id'];
						$err_type['5']['last_msg']='failure to update cdiscount order detail, detail info lost!';
					}
					continue;
				}

				//format Order Data
				$anOrder['selleruserid'] = $cdiscountAccount['username'];
				$anOrder['saas_platform_user_id'] = $cdiscountAccount['site_id'];
				
				//format import order data
				//echo "\n YS0.5 start to formated order data";
				$formated_order_data = self::_formatImportOrderData( $anOrder , $cdiscountAccount, $getEmail=true);
				$formated_order_detail_data = self::_formatImportOrderItemsData( $anOrder['OrderLineList']['OrderLine'],$anOrder['OrderNumber'],$cdiscountAccount);
			
				/*面向不确定类型的用户，需要屏蔽此判断
				if(floatval($total_amount)<=2)//cd有时会生成一些价格低于2欧的问题订单，需要忽略
					continue;
				*/
				if(empty($formated_order_data['shipping_cost']))
					$formated_order_data['shipping_cost']=0;
				if(isset($formated_order_data['total_amount']) && isset($formated_order_data['shipping_cost']) ){
					$total_amount = $formated_order_data['total_amount'] - $formated_order_data['shipping_cost'];
				}else 
					$total_amount=@$formated_order_detail_data['total_amount'];
				
				$formated_order_data['subtotal'] = $total_amount; //产品总价格
				
				//if(empty($formated_order_data['shipping_cost']))
				//	$formated_order_data['shipping_cost'] = $formated_order_detail_data['delivery_amonut']; //若订单获得的运费empty,则取由items计算出的运费
				
				$formated_order_data['grand_total'] = $formated_order_data['subtotal'] + $formated_order_data['shipping_cost'] - $formated_order_data['discount_amount'] ;//合计金额
				$formated_order_data['fulfill_deadline'] = $delivery_date_min;
				
				$weird_FBC = false;
				$weird_FBC_order_level= false;
				$weird_FBC_item_level = false;
				if(isset($formated_order_data['weird_FBC'])){
					$weird_FBC_order_level = true;
					unset($formated_order_data['weird_FBC']);
				}
				if(isset($formated_order_detail_data['all_item_price_not_higher_than_1_EUR'])){
					$weird_FBC_item_level = true;
				}
				if(!empty($weird_FBC_order_level) && !empty($weird_FBC_item_level))
					$weird_FBC = true;
				if(!empty($weird_FBC))
					$formated_order_data['addi_info'] = json_encode(['weird_FBC'=>true]);
				//print_r($formated_order_data);
				//print_r($formated_order_detail_data);
				//echo "\n YS0.5 end of formated order data";
				// call eagle order api to sync order information
				//$importOrderResult = OrderHelper::importPlatformOrder($OrderParms);
/**************** cdisciunt have not to need to do this**********************
			//	Step 1: save this order to eagle OMS 1.0, and get the record ID
 
				$importOrderResult = self::_saveCdiscountOrderToOldEagle1($formated_order_data);
				//echo "\n YS0.6 end of _saveCdiscountOrderToOldEagle1";
				if ($importOrderResult['success']==="fail"){
				 	$message = "Call Eagle1 order insert api fails.  error:".$importOrderResult['message'];
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',$message,"edb\global"]);
					$rtn['success'] = false;
					if (!empty($rtn['message']))
						$rtn['message'] .="<br>";
					
					$rtn['message'] .= $message;
					
					continue;
				}
				$resultInfo=json_decode($importOrderResult["responseStr"],true);
				if (!isset($resultInfo['success']) or $resultInfo['success'] == 1){
					//eagle1 获取了request但是执行失败
					$message = "Eagle 1 Insert order fails.  error:".$resultInfo['message'];
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',$message,"edb\global"]);
					if (!empty($rtn['message']))
						$rtn['message'] .="<br>";
						
					$rtn['message'] .= $message;
				}
				
				$eagleOrderRecordId=$resultInfo["eagle_order_id"];
				//echo " eagleOrderId:$eagleOrderRecordId start to duplicate it to eagle 2 oms \n";
*/
				//echo "\n YS0.7 start to _saveCdiscountOrderToEagle";
				//	Step 2: save this order to eagle OMS 2.0, using the same record ID	
				$formated_order_data['items']=$formated_order_detail_data['items'];
				$importOrderResult=self::_saveCdiscountOrderToEagle($formated_order_data,$eagleOrderRecordId=-1);
				//print_r($importOrderResult,true);
				if (!isset($importOrderResult['success']) or $importOrderResult['success']==1){
					echo "\n failure insert an order to oms 2,result:";
					print_r($importOrderResult);
					$insertErrOrder['omsTbale'][]=empty($anOrder['OrderNumber'])?'':$anOrder['OrderNumber'];
					$rtn['message'].=empty($anOrder['OrderNumber'])?'':$anOrder['OrderNumber']."订单保存到oms失败;";
					$addinfo['oms_auto_inserted'] = 0;
					$addinfo['errors'] = '订单保存到oms失败';
					$rtn['success']=false;
					
					if($newCreateOrder){
						$oms_insert_failed ++;
						$oms_insert_failed_happend_site =  $cdiscountAccount['site_id'];
						$err_type['3']['times'] +=1;
						$err_type['3']['site_id']=$cdiscountAccount['site_id'];
						$err_type['3']['last_msg']='failure to insert cdiscount order to oms!';
					}else{
						$oms_update_failed ++;
						$oms_update_failed_happend_site =  $cdiscountAccount['site_id'];
						$err_type['3']['times'] +=1;
						$err_type['3']['site_id']=$cdiscountAccount['site_id'];
						$err_type['3']['last_msg']='failure to update cdiscount order to oms!';
					}
					continue;
				}else{
					$addinfo['oms_auto_inserted'] = 1;
					echo "\n Success insert an order to oms 2.";
					//when oms inster or update order don't checkorderstatus, do it in cd helper
					$order = OdOrder::find()->where(['order_source_order_id'=>$formated_order_data['order_source_order_id']])->one();
					if($newCreateOrder && !empty($order)){
						$oms_insert_success ++;
					}
					if($newCreateOrder && empty($order) && !isset($importOrderResult['message'])){
						echo "\n error:oms insert success but order model not find!";
						$oms_insert_failed ++;
						$oms_insert_failed_happend_site =  $cdiscountAccount['site_id'];
					}
					if($newCreateOrder && $order==null && isset($importOrderResult['message'])){
						if( stripos($importOrderResult['message'], 'E009')===false){
							$oms_insert_failed ++;
							$oms_insert_failed_happend_site =  $cdiscountAccount['site_id'];
						}else{
							$oms_insert_success ++;//合并过的订单
						}
					}
					if(!$newCreateOrder){
						if(empty($order)){
							$oms_insert_failed ++;
							$oms_insert_failed_happend_site =  $cdiscountAccount['site_id'];
						}else{
							$oms_update_success ++;
						}
					}
					/* 爆Exception，暂时屏蔽
					 if($order<>null && floatval($order->order_status) >=200 && empty($order->default_shipping_method_code)){
					echo "\n checkorderstatus after order_v2 update";
					$checkorderstatus = $order->checkorderstatus('System');
					if($checkorderstatus)
						echo "\n checkorderstatus return true";
					else
						echo "\n checkorderstatus return false";
					}
					*/
				}
				if(!empty($addinfo)){
					$orderModel->addinfo = json_encode($addinfo);
					$orderModel->save(false);
				}
			}//end of each order 
			
			//sync Product info from offer list
			self::saveOrderDetailToOfferListIfIsNew($syncProdInfoData, $cdiscountAccount);
			
			if(!empty($insertErrOrder['srcTbale']) || !empty($insertErrOrder['omsTbale'])){
				\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',date('Y-m-d H:i:s')." CdInsertOrderError:".json_encode($insertErrOrder) ],"edb\global");
			}
			
			//echo "\n Start to calculateSalesWithin15Days: ";
			$nowTimeStr = TimeUtil::getNow();
			//self::calculateSalesWithin15Days($cdiscountAccount['store_name'], $cdiscountAccount['username'],$nowTimeStr);
			
			//set dash-broad log
			if(empty($the_day))
				$the_day=date("Y-m-d",time());
			if(empty($src_insert_success) && empty($src_detail_insert_success) && empty($src_detail_insert_failed) && 
			   empty($src_update_success) && empty($src_update_failed) && empty($src_detail_update_success) && 
			   empty($src_detail_update_failed) ) {
				//无insert无update,则不记录
				echo "\n no insert and on update! \n";
			}else{
				$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='orders' and `the_day`='".$the_day."' "  );
				$record = $command->queryOne();
				if(!empty($record)){
					//echo "\n liang test log 1";
					$orders_count_str = $record['addinfo'];
					$order_count = json_decode($orders_count_str,true);
					$order_count['src_insert_success'] = empty($order_count['src_insert_success'])?$src_insert_success:$order_count['src_insert_success']+$src_insert_success;
					$order_count['src_insert_failed'] = empty($order_count['src_insert_failed'])?$src_insert_failed:$order_count['src_insert_failed']+$src_insert_failed;
					$order_count['src_detail_insert_success'] = empty($order_count['src_detail_insert_success'])?$src_detail_insert_success:$order_count['src_detail_insert_success']+$src_detail_insert_success;
					$order_count['src_detail_insert_failed'] = empty($order_count['src_detail_insert_failed'])?$src_detail_insert_failed:$order_count['src_detail_insert_failed']+$src_detail_insert_failed;
					$order_count['src_update_success'] = empty($order_count['src_update_success'])?$src_update_success:$order_count['src_update_success']+$src_update_success;
					$order_count['src_update_failed'] = empty($order_count['src_update_failed'])?$src_update_failed:$order_count['src_update_failed']+$src_update_failed;
					$order_count['src_detail_update_success'] = empty($order_count['src_detail_update_success'])?$src_detail_update_success:$order_count['src_detail_update_success']+$src_detail_update_success;
					$order_count['src_detail_update_failed'] = empty($order_count['src_detail_update_failed'])?$src_detail_update_failed:$order_count['src_detail_update_failed']+$src_detail_update_failed;
					
					$order_count['oms_insert_success'] = empty($order_count['oms_insert_success'])?$oms_insert_success:$order_count['oms_insert_success']+$oms_insert_success;
					$order_count['oms_insert_failed'] = empty($order_count['oms_insert_failed'])?$oms_insert_failed:$order_count['oms_insert_failed']+$oms_insert_failed;
					$order_count['oms_update_success'] = empty($order_count['oms_update_success'])?$oms_update_success:$order_count['oms_update_success']+$oms_update_success;
					$order_count['oms_update_failed'] = empty($order_count['oms_update_failed'])?$oms_update_failed:$order_count['oms_update_failed']+$oms_update_failed;	
					
					$failed_happend_site_str = $record['addinfo2'];
					$failed_happend_site = json_decode($failed_happend_site_str,true);
					if(!empty($failed_happend_site['src_insert_failed_happend_site'])){
						if(!in_array($src_insert_failed_happend_site,$failed_happend_site['src_insert_failed_happend_site']))
							$failed_happend_site['src_insert_failed_happend_site'][] = $src_insert_failed_happend_site;
					}else {
						if(!empty($src_insert_failed_happend_site))
							$failed_happend_site['src_insert_failed_happend_site'] = array($src_insert_failed_happend_site);
					}
					if(!empty($failed_happend_site['src_update_failed_happend_site'])){
						if(!in_array($src_update_failed_happend_site,$failed_happend_site['src_update_failed_happend_site']))
							$failed_happend_site['src_update_failed_happend_site'][] = $src_update_failed_happend_site;
					}else{
						if(!empty($src_update_failed_happend_site))
							$failed_happend_site['src_update_failed_happend_site'] = array($src_update_failed_happend_site);
					}
					if(!empty($failed_happend_site['src_detail_insert_failed_happend_site'])){
						if(!in_array($src_detail_insert_failed_happend_site,$failed_happend_site['src_detail_insert_failed_happend_site']))
							$failed_happend_site['src_detail_insert_failed_happend_site'][] = $src_detail_insert_failed_happend_site;
					}else{
						if(!empty($src_detail_insert_failed_happend_site))
							$failed_happend_site['src_detail_insert_failed_happend_site'] = array($src_detail_insert_failed_happend_site);
					}
					if(!empty($failed_happend_site['src_detail_update_failed_happend_site'])){
						if(!in_array($src_detail_update_failed_happend_site,$failed_happend_site['src_detail_update_failed_happend_site']))
							$failed_happend_site['src_detail_update_failed_happend_site'][] = $src_detail_update_failed_happend_site;
					}else{
						if(!empty($src_detail_update_failed_happend_site))
							$failed_happend_site['src_detail_update_failed_happend_site'] = array($src_detail_update_failed_happend_site);
					}
					if(!empty($failed_happend_site['oms_insert_failed_happend_site'])){
						if(!in_array($oms_insert_failed_happend_site,$failed_happend_site['oms_insert_failed_happend_site']))
							$failed_happend_site['oms_insert_failed_happend_site'][] = $oms_insert_failed_happend_site;
					}else{
						if(!empty($oms_insert_failed_happend_site))
						$failed_happend_site['oms_insert_failed_happend_site'] = array($oms_insert_failed_happend_site);
					}
					if(!empty($failed_happend_site['oms_update_failed_happend_site'])){
						if(!in_array($oms_update_failed_happend_site,$failed_happend_site['oms_update_failed_happend_site']))
							$failed_happend_site['oms_update_failed_happend_site'][] = $oms_update_failed_happend_site;
					}else{
						if(!empty($oms_update_failed_happend_site))
							$failed_happend_site['oms_update_failed_happend_site'] = array($oms_update_failed_happend_site);
					}

					$command = Yii::$app->db_queue->createCommand("update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`addinfo`='".json_encode($order_count)."',`addinfo2`='".json_encode($failed_happend_site)."'  where app='CDOMS' and info_type='orders' and `the_day`='".$the_day."' "  );
					$affect_record = $command->execute();
				}else{
					//echo "\n liang test log 2";
					$order_count=[];
					$order_count['src_insert_success'] = $src_insert_success;
					$order_count['src_insert_failed'] = $src_insert_failed;
					$order_count['src_detail_insert_success'] = $src_detail_insert_success;
					$order_count['src_detail_insert_failed'] = $src_detail_insert_failed;
					$order_count['src_update_success'] = $src_update_success;
					$order_count['src_update_failed'] = $src_update_failed;
					$order_count['src_detail_update_success'] = +$src_detail_update_success;
					$order_count['src_detail_update_failed'] = $src_detail_update_failed;
					$order_count['oms_insert_success'] = $oms_insert_success;
					$order_count['oms_insert_failed'] = $oms_insert_failed;
					$order_count['oms_update_success'] = $oms_update_success;
					$order_count['oms_update_failed'] = $oms_update_failed;
						
					$failed_happend_site = [];
					$failed_happend_site['src_insert_failed_happend_site'] = empty($src_insert_failed_happend_site)?[]:array($src_insert_failed_happend_site);
					$failed_happend_site['src_update_failed_happend_site'] = empty($src_update_failed_happend_site)?[]:array($src_update_failed_happend_site);
					$failed_happend_site['src_detail_insert_failed_happend_site'] = empty($src_detail_insert_failed_happend_site)?[]:array($src_detail_insert_failed_happend_site);
					$failed_happend_site['src_detail_update_failed_happend_site'] = empty($src_detail_update_failed_happend_site)?[]:array($src_detail_update_failed_happend_site);
					$failed_happend_site['oms_insert_failed_happend_site'] = empty($oms_insert_failed_happend_site)?[]:array($oms_insert_failed_happend_site);
					$failed_happend_site['oms_update_failed_happend_site'] = empty($oms_update_failed_happend_site)?[]:array($oms_update_failed_happend_site);
					
					$command = Yii::$app->db_queue->createCommand("INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('CDOMS','orders','normal','','".json_encode($order_count)."','".json_encode($failed_happend_site)."','".$the_day."','".date("Y-m-d H:i:s",time())."')"  );
					$affect_record = $command->execute();
				}//set runtime log end
				
				$need_mark_log = false;
				foreach ($err_type as $code=>$v){
					if($v['times']!==0)
						$need_mark_log=true;
				}
				if($need_mark_log){
					$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='err_type' and `the_day`='".$the_day."' "  );
					$record = $command->queryOne();
					echo "\n select from app_it_dash_board , info_type='err_type' : ";
					if(!empty($record)){
						//echo "\n liang test log 3";
						$err_type_info_str = $record['addinfo'];
						$err_type_info = json_decode($err_type_info_str,true);
						foreach ($err_type as $t=>$v){
							$err_type_info[$t]['times'] += $v['times'];
							$err_type_info[$t]['last_msg'] = $v['last_msg'];
							if(!in_array($v['site_id'],$err_type_info[$t]['site_id']))
								$err_type_info[$t]['site_id'][] = $v['site_id'];
							$err_type_info[$t]['time'] = $the_day;
						}
						$command = Yii::$app->db_queue->createCommand("update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`addinfo`='".json_encode($err_type_info)."',`addinfo2`=' '  where app='CDOMS' and info_type='err_type' and `the_day`='".$the_day."' "  );
						$affect_record = $command->execute();
					}
					else{
						//echo "\n liang test log 4";
						$err_type_info = [];
						foreach ($err_type as $t=>$v){
							$err_type_info[$t]['times'] = $v['times'];
							$err_type_info[$t]['last_msg'] = $v['last_msg'];
							$err_type_info[$t]['site_id'][] = $v['site_id'];
							$err_type_info[$t]['time'] = $the_day;
						}
						$command = Yii::$app->db_queue->createCommand("INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('CDOMS','err_type','error','','".json_encode($err_type_info)."',' ','".$the_day."','".date("Y-m-d H:i:s",time())."')"  );
						$affect_record = $command->execute();
					}
				}
			}
			//记录用户订单数
			if(!empty($src_insert_success)){
				$uid = $cdiscountAccount['uid'];
				$sellerid=$cdiscountAccount['username'];
				$classification = "CdiscountOms_TempData";
				
				$temp_count = RedisHelper::RedisGet($classification,"user_$uid".".".$the_day );
				$seller_temp_count = RedisHelper::RedisGet($classification,"user_$uid".".".$sellerid.".".$the_day );
				if(empty($temp_count))
					$temp_count = $src_insert_success;
				else 
					$temp_count = (int)$temp_count + $src_insert_success;
				
				if(empty($seller_temp_count))
					$seller_temp_count = $src_insert_success;
				else 
					$seller_temp_count = (int)$seller_temp_count + $src_insert_success;
				
				$set_redis = RedisHelper::RedisSet($classification,"user_$uid".".".$the_day,$temp_count);
				$set_seller_redis = RedisHelper::RedisSet($classification,"user_$uid".".".$sellerid.".".$the_day,$seller_temp_count);
				echo "\n set redis return : $set_redis;$set_seller_redis";
			}
			//set dash-broad log end
			
			return $rtn;
		} catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
			echo "\n error: ".$rtn['message'];
			return $rtn;
		}				
	}//end of _InsertCdiscountOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * cdiscount api 获取的数组 赋值到  eagle order 接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $cdiscountOrderData		cdiscount 数据
	 * @param $cdiscountAccount			账号 数据
	 * @param $getEmail					是否需要获取买家email
	 +---------------------------------------------------------------------------------------------
	 * @return		$importOrderData	eagle order 接口 的数组
	 * 									调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015-07-07	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderData($cdiscountOrderData,$cdiscountAccount,$getEmail=false){
		$importOrderData = array();

		//$importOrderData['order_id'] = $cdiscountOrderData['OrderNumber'];
		if (!empty($cdiscountOrderData['OrderState'])){
			/*
			 * 	cdiscount order state 
			 *  State 'WaitingForShipmentAcceptation' means that the order is waiting to ship.
			 *  State 'Shipped' means that the order has been marked as shipped by you.
			 *  State 'CancelledByCustomer' means that the order has been refunded by customer and should not be fulfilled.
			 *  State 'PaymentInProgress' means that the order is waiting customer to pay
			 *  
		R	 *  eagle order status
			 *  100（未付款）、200（已付款）、300（发货处理中）、400（已发货）、500（已完成）、600（已取消）
			 * */
			$CdiscountStateToEagleStatusMapping = array(
				'WaitingForShipmentAcceptation'=> 200 , 
				'Shipped'=>500, 
				'CancelledByCustomer'=>600, 
				'AcceptedBySeller'=>200,
				'PaymentInProgress'=>100,
				'RefusedBySeller'=>600,
				'AutomaticCancellation'=>600,
				'PaymentRefused'=>600,
				'ShipmentRefusedBySeller'=>600,
				'RefusedNoShipment'=>600,
			);
			$importOrderData['order_status'] = $CdiscountStateToEagleStatusMapping[$cdiscountOrderData['OrderState']]; 
			$importOrderData['order_source_status'] = $cdiscountOrderData['OrderState'];
		}

		if (isset($importOrderData['order_status'] )){
			//发货状态：1.已发货；0.未发货 #非已发货状态暂时定义为未发
			$importOrderData['shipping_status'] = (($importOrderData['order_status'] == 500)?1:0); 
		}

		$importOrderData['pay_status'] = ($importOrderData['order_status']<200)?0:1;
		
		$importOrderData['order_source'] = 'cdiscount';//订单来源
		
		$importOrderData['order_type'] = '';//订单类型
		if($cdiscountOrderData['IsCLogistiqueOrder']!=='false')
			$importOrderData['order_type'] = 'FBC';
		
		if($importOrderData['order_type']=='FBC'){//如果是FBC订单，则认为是已发货。
			$importOrderData['order_status']=500;
			$importOrderData['shipping_status']=1;
		}
		
		if(isset($cdiscountOrderData['ShippingCode']) && is_string($cdiscountOrderData['ShippingCode']))
			$importOrderData['order_source_shipping_method'] = $cdiscountOrderData['ShippingCode'];
		
		if (isset($cdiscountOrderData['OrderNumber'])){
			$importOrderData['order_source_order_id'] = $cdiscountOrderData['OrderNumber']; //订单来源的订单id
		}

		$importOrderData['order_source_site_id'] = 'FR'; //订单来源平台下的站点,Cdiscount暂无分站点,只有法国站
		
		
		if (isset($cdiscountOrderData['selleruserid']))
			$importOrderData['selleruserid'] = $cdiscountOrderData['selleruserid']; //订单来源平台卖家用户名(下单时候的用户名)

		if (isset($cdiscountOrderData['saas_platform_user_id']))
			$importOrderData['saas_platform_user_id'] = $cdiscountOrderData['saas_platform_user_id']; //订单来源平台卖家用户名(下单时候的用户名)

		//$Civility['MR'] = "M.";
		//$Civility['MRS'] = "Mme";
		//$Civility['MISS'] = "Mlle";
		$buyer_name='';
		if (isset($cdiscountOrderData['Customer'])){
			//if(isset($cdiscountOrderData['Customer']['Civility']) && is_string($cdiscountOrderData['Customer']['Civility']))
				//$buyer_name.=$Civility[$cdiscountOrderData['Customer']['Civility']]." ";
				$buyer_name.=$cdiscountOrderData['Customer']['Civility']." ";
			if(isset($cdiscountOrderData['Customer']['FirstName']) && is_string($cdiscountOrderData['Customer']['FirstName']))
				$buyer_name.=$cdiscountOrderData['Customer']['FirstName']." ";
			if(isset($cdiscountOrderData['Customer']['LastName']) && is_string($cdiscountOrderData['Customer']['LastName']))
				$buyer_name.=$cdiscountOrderData['Customer']['LastName'];	
		}
		$importOrderData['source_buyer_user_id'] = $buyer_name; //买家名称
		
		if (isset($cdiscountOrderData['CreationDate']))
			$importOrderData['order_source_create_time'] = strtotime($cdiscountOrderData['CreationDate']); //订单在来源平台的下单时间
		
		if (isset($cdiscountOrderData['ModifiedDate'])){
			//var_dump($cdiscountOrderData['ModifiedDate']);
			$cdiscountOrderData['ModifiedDate'] = substr($cdiscountOrderData['ModifiedDate'],0,19);
			$importOrderData['last_modify_time'] = date("Y-m-d H:i:s",strtotime($cdiscountOrderData['ModifiedDate']) ); //订单在来源平台的最后修改时间
			//var_dump($importOrderData['last_modify_time']);
		}
		
		$shippingConsignee = '';
		if (isset($cdiscountOrderData['ShippingAddress'])){
			//echo "\n src ShippingAddress data:";
			//var_dump($cdiscountOrderData['ShippingAddress']);//liang test,屏蔽后部分订单地址信息转到eagle2订单格式是可能丢失(原因不明)
			if(isset($cdiscountOrderData['ShippingAddress']['Civility']) && is_string($cdiscountOrderData['ShippingAddress']['Civility']))
				$shippingConsignee.=$cdiscountOrderData['ShippingAddress']['Civility']." ";
			if(isset($cdiscountOrderData['ShippingAddress']['FirstName']) && is_string($cdiscountOrderData['ShippingAddress']['FirstName']))
				$shippingConsignee.=$cdiscountOrderData['ShippingAddress']['FirstName']." ";
			if(isset($cdiscountOrderData['ShippingAddress']['LastName']) && is_string($cdiscountOrderData['ShippingAddress']['LastName']))
				$shippingConsignee.=$cdiscountOrderData['ShippingAddress']['LastName']." ";
			if(isset($cdiscountOrderData['ShippingAddress']['Instructions']) && is_string($cdiscountOrderData['ShippingAddress']['Instructions']))
				$shippingConsignee.=$cdiscountOrderData['ShippingAddress']['Instructions'];
		}
		$importOrderData['consignee'] = $shippingConsignee; //收货人

		if (isset($cdiscountOrderData['ShippingAddress']['ZipCode']) && is_string($cdiscountOrderData['ShippingAddress']['ZipCode']))
			$importOrderData['consignee_postal_code'] = $cdiscountOrderData['ShippingAddress']['ZipCode']; //收货人邮编
		
		if (isset($cdiscountOrderData['Customer']['Phone']) && is_string($cdiscountOrderData['Customer']['Phone']))
			$importOrderData['consignee_phone'] =$cdiscountOrderData['Customer']['Phone']; //收货人电话
		
		if (isset($cdiscountOrderData['Customer']['MobilePhone']) && is_string($cdiscountOrderData['Customer']['MobilePhone']))
			$importOrderData['consignee_mobile'] =$cdiscountOrderData['Customer']['MobilePhone']; //收货移动电话
		
		if (isset($cdiscountOrderData['Customer']['EncryptedEmail']) && is_string($cdiscountOrderData['Customer']['EncryptedEmail']))
			$importOrderData['consignee_email'] =$cdiscountOrderData['Customer']['EncryptedEmail']; //收货人Email
		
		
		$getEmail_retry = 0;
		while (empty($importOrderData['consignee_email']) && $getEmail && $getEmail_retry<2){
			$importOrderData['consignee_email'] = CdiscountOrderInterface::getEmailByOrderID($cdiscountAccount, $cdiscountOrderData['OrderNumber']);
			$getEmail_retry++;
		}
		
		
		if(empty($importOrderData['consignee_email']) || !is_string($importOrderData['consignee_email']))
			$importOrderData['consignee_email'] = 'N/A';
		
		if (isset($cdiscountOrderData['ShippingAddress']['CompanyName']) && is_string($cdiscountOrderData['ShippingAddress']['CompanyName']))
			$importOrderData['consignee_company'] =$cdiscountOrderData['ShippingAddress']['CompanyName']; //收货人公司
		
		if (!empty($cdiscountOrderData['ShippingAddress']['Country']) && is_string(($cdiscountOrderData['ShippingAddress']['Country'])) ){
			$importOrderData['consignee_country'] = $cdiscountOrderData['ShippingAddress']['Country']; //收货人国家名
			$importOrderData['consignee_country_code'] = $cdiscountOrderData['ShippingAddress']['Country']; //收货人国家代码
		}else{
			$importOrderData['consignee_country'] = 'FR'; //收货人国家名
			$importOrderData['consignee_country_code'] = 'FR'; //收货人国家代码
		}
		
		if (isset($cdiscountOrderData['ShippingAddress']['City']) && is_string($cdiscountOrderData['ShippingAddress']['City']))
			$importOrderData['consignee_city'] = $cdiscountOrderData['ShippingAddress']['City']; //收货人城市
		
		if (isset($cdiscountOrderData['ShippingAddress']['PlaceName']) && is_string($cdiscountOrderData['ShippingAddress']['PlaceName']))
			$importOrderData['consignee_district'] = $cdiscountOrderData['ShippingAddress']['PlaceName']; //收货人地区
		
		$importOrderData['consignee_address_line1']='';
		if (isset($cdiscountOrderData['ShippingAddress']['Street']) && is_string($cdiscountOrderData['ShippingAddress']['Street']))
			$importOrderData['consignee_address_line1'] = $cdiscountOrderData['ShippingAddress']['Street']; //收货人地址1
		//Apartment
		if (isset($cdiscountOrderData['ShippingAddress']['ApartmentNumber']) and is_string($cdiscountOrderData['ShippingAddress']['ApartmentNumber']) ){
			if(stripos($cdiscountOrderData['ShippingAddress']['ApartmentNumber'],"apt")===false && stripos($cdiscountOrderData['ShippingAddress']['ApartmentNumber'],"app")===false && stripos($cdiscountOrderData['ShippingAddress']['ApartmentNumber'],"Apartment")===false)
				$Apartment = "Apt.".$cdiscountOrderData['ShippingAddress']['ApartmentNumber'];
			else
				$Apartment = $cdiscountOrderData['ShippingAddress']['ApartmentNumber'];
				
			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = "Apt.".$cdiscountOrderData['ShippingAddress']['ApartmentNumber'];
			else
				$importOrderData['consignee_address_line1'] = $Apartment.";".$importOrderData['consignee_address_line1'];
		}
		//Building
		if(isset($cdiscountOrderData['ShippingAddress']['Building']) && is_string($cdiscountOrderData['ShippingAddress']['Building'])){
			if(stripos($cdiscountOrderData['ShippingAddress']['Building'],"bât")===false && stripos($cdiscountOrderData['ShippingAddress']['Building'],"BTMENT")===false && stripos($cdiscountOrderData['ShippingAddress']['Building'],"Bâtiment")===false)
				// $Btment = "bât.".$cdiscountOrderData['ShippingAddress']['Building'];// dzt20190531 客户3921 要求不要缩写 
			    $Btment = "Bâtiment ".$cdiscountOrderData['ShippingAddress']['Building'];
			else
				$Btment = $cdiscountOrderData['ShippingAddress']['Building'];

			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = $Btment;
			else
				$importOrderData['consignee_address_line1'] = $Btment.';'.$importOrderData['consignee_address_line1'];
		}
		
		//echo "\n fianl consignee_address_line1 is:".$importOrderData['consignee_address_line1'];//liang test
		if (isset($cdiscountOrderData['ShippingAddress']['Address1']) && is_string($cdiscountOrderData['ShippingAddress']['Address1']))
			$importOrderData['consignee_address_line2'] = $cdiscountOrderData['ShippingAddress']['Address1']; //收货人地址2
		
		if (isset($cdiscountOrderData['ShippingAddress']['Address2']) && is_string($cdiscountOrderData['ShippingAddress']['Address2']))
			$importOrderData['consignee_address_line3'] = $cdiscountOrderData['ShippingAddress']['Address2'];
		
		if (isset($importOrderData['order_source_create_time'] ))
			$importOrderData['paid_time'] = $importOrderData['order_source_create_time'] ; //订单付款时间

		if(!empty($cdiscountOrderData['ValidatedTotalAmount']))
			$importOrderData['total_amount'] = $cdiscountOrderData['ValidatedTotalAmount'];
			
		if(!empty($cdiscountOrderData['ValidatedTotalShippingCharges']))
			$importOrderData['shipping_cost'] = $cdiscountOrderData['ValidatedTotalShippingCharges'];
		
		if (isset($cdiscountOrderData['SiteCommissionValidatedAmount'] ))
			$importOrderData['commission_total'] = $cdiscountOrderData['SiteCommissionValidatedAmount']; //平台佣金
		else
			$importOrderData['commission_total'] = 0;
		
		//yzq 20151220, 因为外面的其他地方还是会读取这个field，还需要这个field应付外面的读取，否则会出错
		$importOrderData['discount_amount'] = $importOrderData['commission_total']; //
		
		//if (isset($importOrderData['subtotal']) && isset($importOrderData['shipping_cost']) && isset($importOrderData['discount_amount']))
		//	$importOrderData['grand_total'] = $importOrderData['subtotal'] + $importOrderData['shipping_cost'] - $importOrderData['discount_amount']; //合计金额(产品总价格 + 运费 - 折扣 = 合计金额)
		$importOrderData['currency'] = 'EUR'; //货币
		
		if( $importOrderData['order_type']=='FBC' && isset($importOrderData['total_amount']) && (int)$importOrderData['total_amount']<2)
			$importOrderData['weird_FBC'] = true;
		return $importOrderData;
	}//end of _formatImportOrderData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * cdiscount api 获取的item数组 赋值到  eagle order item接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access 		static
	 +---------------------------------------------------------------------------------------------
	 * @param 		$cdiscountOrderLine		cdiscount 数据
	 +---------------------------------------------------------------------------------------------
	 * @return		$importOrderItems		eagle order item接口 的数组
	 * 										调用eagle order item接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015-07-07	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderItemsData($cdiscountOrderLine,$order_number,$cdiscountAccount=[]){
		$importOrderItems = array();
		$total_amount=0;
		$delivery_amonut=0;
		$total_qty=0;
		$all_item_price_not_higher_than_1_EUR = true;//是否所有商品都不高于1欧，用于判断是否是有问题的订单
		$nonDeliverySku = CdiscountOrderInterface::getNonDeliverySku();
		// only one item
		if (isset($cdiscountOrderLine['Sku'])){
			$row=array();
			$listing = CdiscountOfferList::find()->where(['seller_product_id'=>$cdiscountOrderLine['SellerProductId'],'seller_id'=>$cdiscountAccount['username']])->asArray()->one();
			$row['order_source_order_id'] = $order_number; // item 的平台 订单号用于eagle 2.0 的发货
			
			$row['sku'] = '';
			if(is_string($cdiscountOrderLine['SellerProductId']) && !empty($cdiscountOrderLine['SellerProductId']))
				$row['sku']=$cdiscountOrderLine['SellerProductId'];
			if (empty($row['sku']))
				$row['sku'] = $cdiscountOrderLine['ProductId'];
			
			$row['source_item_id'] = $cdiscountOrderLine['Sku'];
			//$row['order_source_order_item_id'] = $cdiscountOrderLine['Sku'];//order_source_order_item_id为int类型，CD的item id为varchar，暂时不能设置该值
			if(is_string($cdiscountOrderLine['Name']) && $cdiscountOrderLine['Name']!==''){
				$row['product_name']=$cdiscountOrderLine['Name'];
			}else{
				if($listing<>null)
					$row['product_name'] = $listing['name'];
			}
			if(empty($row['product_name'])) $row['product_name']=$row['sku'];

			$row['order_source_itemid']=$cdiscountOrderLine['Sku'];
			$row['order_source_order_item_id'] = $cdiscountOrderLine['Sku'];
			//$cdiscountOrderLine['CategoryCode']='';
			//$cdiscountOrderLine['DeliveryDateMax']='';
			//$cdiscountOrderLine['DeliveryDateMin']='';
			//$cdiscountOrderLine['HasClaim']=false;
			//$cdiscountOrderLine['OrderLineChildList']=[];
			//$cdiscountOrderLine['ProductCondition']='New';
			//$cdiscountOrderLine['ProductEan']=0123456789123;
			if(empty($cdiscountOrderLine['Quantity']))
				$row['price'] = $cdiscountOrderLine['PurchasePrice'];
			else
				$row['price'] = $cdiscountOrderLine['PurchasePrice'] / $cdiscountOrderLine['Quantity'];
			
			if(floatval($row['price']) > 1)
				$all_item_price_not_higher_than_1_EUR = false;
			
			$row['ordered_quantity'] = $cdiscountOrderLine['Quantity'];
			$row['quantity']=$cdiscountOrderLine['Quantity'];
			//$cdiscountOrderLine['RowId']=0;
			
			$row['platform_status'] = $cdiscountOrderLine['AcceptationState'];
			if(in_array($row['source_item_id'],$nonDeliverySku))
				$row['platform_status'] = 'ShippedBySeller';
			
			if($cdiscountOrderLine['AcceptationState']=='ShippedBySeller'){
				$row['sent_quantity']=$cdiscountOrderLine['Quantity'];
				$row['packed_quantity']=$cdiscountOrderLine['Quantity'];
			}
			//有退款订单
			if(!stripos($cdiscountOrderLine['AcceptationState'],'refunded')===false){
				$row['returned_quantity']=$cdiscountOrderLine['Quantity'];
				$row['remark'] = $cdiscountOrderLine['AcceptationState'];
			}else 
				$row['returned_quantity']=0;
			//$cdiscountOrderLine['ShippingDateMax']='0001-01-01T00:00:00';
			//$cdiscountOrderLine['ShippingDateMin']='0001-01-01T00:00:00';
			//$cdiscountOrderLine['SkuParent']=[];
			//$cdiscountOrderLine['UnitAdditionalShippingCharges']=0.15;
			$row['shipping_price']=$cdiscountOrderLine['UnitAdditionalShippingCharges'];
			
			if(!empty($listing) && !empty($listing['product_url'])){
				$row['product_url'] = $listing['product_url'];
			}
			$row['photo_primary']='';//cdiscount response none product photo info
			
			if(!empty($listing) && !empty($listing['img'])){
				$imgs = json_decode($listing['img'],true);
				if(!empty($imgs) && !empty($imgs[0]))
					$row['photo_primary']=$imgs[0];
			}
			if(empty($row['photo_primary'])){
				$tmp_photo_parma = substr($cdiscountOrderLine['ProductId'], -3);
				$tmp_par = [];
				for($i=0; $i<strlen($tmp_photo_parma); $i++)
				{
				$tmp_par[]=substr($tmp_photo_parma,$i,1); //将单个字符存到数组当中
				}
				$tmp_photo_str = implode('/', $tmp_par);
				$row['photo_primary'] = 'http://i2.cdscdn.com/pdt2/'.$tmp_photo_str.'/1/300x300/'.$cdiscountOrderLine['ProductId'].'.jpg';
				//echo "\n ".$row['photo_primary']."\n";
			}
			// $item['discount_amount'] =  $row['OrderLineList']['OrderLine']['ProductId'];
			$total_amount += $cdiscountOrderLine['PurchasePrice'];
			$delivery_amonut += $row['shipping_price']*$row['quantity'];
			$total_qty += $row['quantity'];
			$items[]=$row;
		}
		else{
		//multi items
			foreach ($cdiscountOrderLine as $orderline){
				$row=array();
				$listing = CdiscountOfferList::find()->where(['seller_product_id'=>$orderline['SellerProductId'],'seller_id'=>$cdiscountAccount['username']])->asArray()->one();
				$row['order_source_order_id'] = $order_number;
				
				$row['sku'] = '';
				if(is_string($orderline['SellerProductId']) && !empty($orderline['SellerProductId']))
					$row['sku']=$orderline['SellerProductId'];
				if (empty($row['sku']))
					$row['sku'] = $orderline['ProductId'];
				
				$row['source_item_id'] = $orderline['Sku'];
				$row['order_source_order_item_id'] = $orderline['Sku'];

				if(is_string($orderline['Name']) && $orderline['Name']!==''){
					$row['product_name']=$orderline['Name'];
				}else{
					if($listing<>null)
						$row['product_name'] = $listing['name'];
				}
				if(empty($row['product_name'])) $row['product_name']=$row['sku'];
				
				$row['order_source_itemid']=$orderline['Sku'];
				
				if(empty($orderline['Quantity']))
					$row['price'] = $orderline['PurchasePrice'];
				else
					$row['price'] = $orderline['PurchasePrice'] / $orderline['Quantity'];
				if(floatval($row['price']) > 1)
					$all_item_price_not_higher_than_1_EUR = false;
				
				$row['ordered_quantity'] = $orderline['Quantity'];
				$row['quantity']=$orderline['Quantity'];
				//$cdiscountOrderLine['RowId']=0;
				
				$row['platform_status'] = $orderline['AcceptationState'];
				if(in_array($row['source_item_id'],$nonDeliverySku))
					$row['platform_status'] = 'ShippedBySeller';
				
				if($orderline['AcceptationState']=='ShippedBySeller'){
					$row['sent_quantity']=$orderline['Quantity'];
					$row['packed_quantity']=$orderline['Quantity'];
				}
				
				//有退款订单
				if(!stripos($orderline['AcceptationState'],'refunded')===false){
					$row['returned_quantity']=$orderline['Quantity'];
					$row['remark'] = $orderline['AcceptationState'];
				}else{
					$row['returned_quantity']=0;
				}
				
				$row['shipping_price']=$orderline['UnitAdditionalShippingCharges'];

				if(!empty($listing) && !empty($listing['product_url'])){
					$row['product_url'] = $listing['product_url'];
				}
				$row['photo_primary']='';//cdiscount response none product photo info
				if(!empty($listing) && !empty($listing['img'])){
					$imgs = json_decode($listing['img'],true);
					if(!empty($imgs) && !empty($imgs[0]))
						$row['photo_primary']=$imgs[0];
				}
				
				// $item['discount_amount'] =  $row['OrderLineList']['OrderLine']['ProductId'];
				$total_amount += $orderline['PurchasePrice'];
				if($row['shipping_price'] > $delivery_amonut)
					$delivery_amonut = $row['shipping_price']*$row['quantity'];
				$total_qty += $row['ordered_quantity'];
				$items[]=$row;
			}
		}
		$importOrderItems['items']=$items;
		$importOrderItems['total_amount']=$total_amount;
		$importOrderItems['delivery_amonut']=$delivery_amonut;
		$importOrderItems['total_qty']=$total_qty;
		
		if($all_item_price_not_higher_than_1_EUR)
			$importOrderItems['all_item_price_not_higher_than_1_EUR'] = true;
		return $importOrderItems;
	}
	
	/**
	 * 获取某个时间之间的所有的订单
	 * @access static
	 * @param		$cdiscount_token		saas model
	 * @param		$createtime				begincreationdate/beginmodificationdate
	 * @param		$endtime				endcreationdate/endmodificationdate
	 * @param		$newBinding				布尔值,决定使用什么日期类型,true：use creationdate,false:use cationdate
	 * @return		orders
	 * @author		lzhl	2014/12/08		初始化
	 **/
	private static function _getAllOrdersSince($cdiscount_token , $createtime='', $endtime='',$newBinding =false,$timeout=180){
		//$timeout=60; //s
		echo "\n enter function : _getAllOrdersSince";
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $wish_token"],"edb\global");
		$config = array('tokenid' => $cdiscount_token);
		$get_param['config'] = json_encode($config);
		
		if($newBinding){
			if ($createtime !== ''){
				$params['begincreationdate'] = $createtime;
			}
			if ($endtime !== ''){
				$params['endcreationdate'] = $endtime;
			}
		}else{
			if ($createtime !== ''){
				$params['beginmodificationdate'] = $createtime;
			}
			if ($endtime !== ''){
				$params['endmodificationdate'] = $endtime;
			}
		}
	
		//$params['state'] = array();
		$params['state'] = self::$orderStatus;
		
		/*
		//如果拉取截止时间超过10日前，则只拉取未完成的订单
		if(!empty($endtime)){
			$endtimeStamp = strtotime($endtime);
			if( time()-$endtimeStamp > 3600*24*10){
				$params['state'] = array(
					'WaitingForShipmentAcceptation',
					'AcceptedBySeller',
				);
			}
		}
		*/
		$get_param['query_params'] = json_encode($params);
	
		$retInfo=CdiscountProxyConnectHelper::call_Cdiscount_api("getOrderList",$get_param,$post_params=array(),$timeout );
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $wish_token"],"edb\global");

		return $retInfo;
	}//end of _getAllChangeOrdersSince
	
	/**
	 * 通过前端手动触发获取CD订单
	 * @param unknown $cdiscount_token
	 * @param string $createtime
	 * @param string $endtime
	 * @param string $newBinding
	 * @param unknown $state
	 * @return string
	 */
	public static function getOrdersByCondition($cdiscount_token , $createtime='', $endtime='',$newBinding=false,$state=[]){
		$timeout=240; //s
		echo "\n enter function : _getAllOrdersSince";
		$config = array('tokenid' => $cdiscount_token);
		$get_param['config'] = json_encode($config);
	
		if($newBinding){
			if ($createtime !== ''){
				$params['begincreationdate'] = $createtime;
			}
			if ($endtime !== ''){
				$params['endcreationdate'] = $endtime;
			}
		}else{
			if ($createtime !== ''){
				$params['beginmodificationdate'] = $createtime;
			}
			if ($endtime !== ''){
				$params['endmodificationdate'] = $endtime;
			}
		}
				
		if(!empty($state))
			$params['state'] = $state;
		else 
			$params['state'] = self::$orderStatus;
	
		$get_param['query_params'] = json_encode($params);
	
		$retInfo=CdiscountProxyConnectHelper::call_Cdiscount_api("getOrderList",$get_param,$post_params=array(),$timeout );
		
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
	private static function _getAllUnfulfilledOrdersSince($cdiscount_token , $createtime='', $endtime=''){
		$timeout=240; //s
		echo "\n enter function : _getAllUnfulfilledOrdersSince";
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $wish_token"],"edb\global");
		$config = array('tokenid' => $cdiscount_token);
		$get_param['config'] = json_encode($config);
		if ($createtime !== ''){
			$params['begincreationdate'] = $createtime;
			$params['beginmodificationdate'] = $createtime;
		}
		if ($endtime !== ''){
			$params['endcreationdate'] = $endtime;
			$params['endmodificationdate'] = $endtime;	
		}
		
		//$params['state'] = array();
		$params['state'] = array(self::$orderStatus_Unshipped);
	 
		$get_param['query_params'] = json_encode($params);
		
		$retInfo=CdiscountProxyConnectHelper::call_Cdiscount_api("getOrderList",$get_param,$post_params=array(),$timeout );
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $wish_token"],"edb\global");

		//check the return info
		return $retInfo;
	}//end of _getAllChangeOrdersSince
	


	/**
	 * 把Cdiscount的订单信息header和items 同步到eagle1系统中user_库的od_order和od_order_item。
	 * 这里主要是通过eagle1提供的 http api的方式
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveCdiscountOrderToOldEagle1($oneOrderReq){
 		return ['success'=>0 , 'responseStr'=>'{"success": 0,"eagle_order_id": -1}'];
		//1. 总的请求信息
		$reqInfo=array();
		$ordersReq=array();
		$ordersReq[]=$oneOrderReq;
		//$uid=$merchantidUidMap[$orderHeaderInfo["merchant_id"]];
		//其中Uid是saas_Wish_user中的uid，这里为了便于eagle的api找到合适的数据库。
		$uid=\Yii::$app->subdb->getCurrentPuid();

		$reqInfo[$uid]=$ordersReq;
		$reqInfoJson=json_encode($reqInfo,true);
	
		//echo "YSa before OrderHelper::importPlatformOrder info:".json_encode($reqInfo,true)."\n";
		$postParams=array("orderinfo"=>$reqInfoJson);
		$result=EagleOneHttpApiHelper::sendOrderInfoToEagle($postParams);
		//echo "YSb result:".print_r($result,true);
		return $result;
	}
	
	
	/**
	 * 把Cdiscount的订单信息header和items 同步到eagle系统中user_库的od_order和od_order_item
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveCdiscountOrderToEagle( $oneOrderReq, $eagleOrderId=-1){
		$result = ['success'=>1,'message'=>''];
		$uid=\Yii::$app->subdb->getCurrentPuid();
		
		$reqInfo[$uid]=array_merge(OrderHelper::$order_demo,$oneOrderReq);
		
		try{
			$result =  OrderHelper::importPlatformOrder($reqInfo,$eagleOrderId);
			//SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'info','data='.json_encode($reqInfo));//test liang
		}catch(\Exception $e){
			$message = "importPlatformOrder fails.  CdiscountId=$eagleOrderId  Exception error:".$e->getMessage()."data: \n ".print_r($reqInfo,true);
			//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"Step 1.5a ". $message ],"edb\global");
			echo $message;
			return ['success'=>1,'message'=>$message];
		}
	
		return $result;
	}
	
	/**
	 * set cdiscount store token,when it's token is null or token was expired
	 * @param	object	$cdiscountUser
	 * return	array	$cdiscountUser
	 */
	Public static function setCdiscountUserToken($cdiscountUser){
		echo "\n setCdiscountUserToken 0";
		if(!empty($cdiscountUser->auth_type)){
			$A_username = $cdiscountUser->username;
			$A_password = $cdiscountUser->password;
		}else{
			$A_username = $cdiscountUser->api_username;
			$A_password = $cdiscountUser->api_password;
		}
			
		if(!empty($A_username) && !empty($A_password)){
			$one_day_ago = date('Y-m-d H:i:s',strtotime("-1 days"));
			if($one_day_ago >= $cdiscountUser->token_expired_date){
				$config = array();
				$config['username']=$A_username;
				$config['password']=$A_password;
				$get_param['config'] = json_encode($config);
				$reqInfo=CdiscountProxyConnectHelper::call_Cdiscount_api("getTokenID",$get_param,$post_params=array() );
				print_r($reqInfo,true);
				echo "\n setCdiscountUserToken 0-1";
				if($reqInfo['success']){
					echo "\n setCdiscountUserToken 1";
					if(!empty($reqInfo['proxyResponse']['success']) && stripos($reqInfo['proxyResponse']['tokenMessage'],'Object moved to')===false){
						echo "\n setCdiscountUserToken 2";
						$tokenid = $reqInfo['proxyResponse']['tokenMessage'];
						$cdiscountUser->token = $tokenid;
						$cdiscountUser->token_expired_date = date('Y-m-d H:i:s');
						$cdiscountUser->update_time = date('Y-m-d H:i:s');
						
						if(!empty($cdiscountUser->addi_info)){
							$addi_info = json_decode($cdiscountUser->addi_info,true);
							if(!empty($addi_info)){
								$addi_info['token_expired'] = false;
								$addi_info['token_fetch_times'] = 0;
								$cdiscountUser->addi_info = json_encode($addi_info);
							}
						}
						
						if(!$cdiscountUser->save()){
							echo "\n CdiscountOrderHelper::setCdiscountUserToken model save false:";
							print_r($cdiscountUser->getErrors(),true);
						}
					}else{
						echo "\n".$reqInfo['proxyResponse']['message'];
						$cdiscountUser->order_retrieve_message = "token同步失败,最后尝试同步时间为".date('Y-m-d H:i:s');
						if(!empty($cdiscountUser->addi_info)){
							$addi_info = json_decode($cdiscountUser->addi_info,true);
							if(empty($addi_info))
								$addi_info=[];
						}else
							$addi_info = [];
							
						$addi_info['token_expired'] = true;
						if(empty($addi_info['token_fetch_times']))
							$addi_info['token_fetch_times'] = 1;
						else 
							$addi_info['token_fetch_times'] += 1;
						$cdiscountUser->addi_info = json_encode($addi_info);
						if(!$cdiscountUser->save()){
							echo "\n CdiscountOrderHelper::setCdiscountUserToken model save false:";
							print_r($cdiscountUser->getErrors(),true);
						}
						echo "\n ".print_r($reqInfo);
					}
				}
				else{
					echo "\n".$reqInfo['message'];
					if(!empty($cdiscountUser->addi_info)){
						$addi_info = json_decode($cdiscountUser->addi_info,true);
						if(empty($addi_info))
							$addi_info=[];
					}else 
						$addi_info = [];
					
					$addi_info['token_expired'] = true;
					if(empty($addi_info['token_fetch_times']))
							$addi_info['token_fetch_times'] = 1;
					else 
						$addi_info['token_fetch_times'] += 1;
					$cdiscountUser->addi_info = json_encode($addi_info);
					if(!$cdiscountUser->save()){
						echo "\n CdiscountOrderHelper::setCdiscountUserToken model save false:";
						print_r($cdiscountUser->getErrors(),true);
					}
				}
			}
		}
		return $cdiscountUser;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取CD订单的Claim
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$token		              平台账号 token
	 * @param 	$orderId		平台订单号
	 * @param	$createtime		起始时间
	 * @param	$endtime		结束时间
	 * @param	$status			状态
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/08/13			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOrderClaimList($token,$orderId=[],$createtime='',$endtime='',$status=[]){
		$timeout=240; //s
		$rtn = [];
// 		echo "\n enter CdiscountOrderHelper function : getOrderClaimList";
		
// 		$cdiscountAccount = SaasCdiscountUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
// 		$cdiscount_token = $cdiscountAccount['token'];
		$cdiscount_token = $token;
		$config = array('tokenid' => $cdiscount_token);
		$get_param['config'] = json_encode($config);
		
		if ($createtime !== ''){
			$params['begincreationdate'] = $createtime;
			//$params['beginmodificationdate'] = $createtime;
		}
		if ($endtime !== ''){
			$params['endcreationdate'] = $endtime;
			//$params['endmodificationdate'] = $endtime;
		}
		if(!empty($orderId))
			$params['orderlist']=$orderId;
		
		$params['state'] = $status;
		
		$get_param['query_params'] = json_encode($params);
		
		$rtn=CdiscountProxyConnectHelper::call_Cdiscount_api("GetOrderClaimList",$get_param,$post_params=array(),$timeout );
		//echo "\n ClaimList return: ".print_r($rtn,true)." \n";
		//print_r($rtn);

		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取CD订单的Question
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$uid
	 * @param 	$storeName		平台账号
	 * @param 	$orderId		平台订单号
	 * @param	$createtime		起始时间
	 * @param	$endtime		结束时间
	 * @param	$status			状态
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/08/13			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOrderQuestionList($uid,$storeName,$orderId=[],$createtime='',$endtime='',$status=[]){
		$timeout=240; //s
		$rtn = [];
		echo "\n enter CdiscountOrderHelper function : getOrderQuestionList";
	
		$cdiscountAccount = SaasCdiscountUser::find()->where(["uid"=>$uid])->asArray()->one();
		$cdiscount_token = $cdiscountAccount['token'];
	
		$config = array('tokenid' => $cdiscount_token);
		$get_param['config'] = json_encode($config);
	
		if ($createtime !== ''){
			//$params['begincreationdate'] = $createtime;
			$params['beginmodificationdate'] = $createtime;
		}
		if ($endtime !== ''){
			//$params['endcreationdate'] = $endtime;
			$params['endmodificationdate'] = $endtime;
		}
		if(!empty($orderId))
			$params['orderlist']=$orderId;
	
		$params['state'] = $status;
	
		$get_param['query_params'] = json_encode($params);
	
		$rtn=CdiscountProxyConnectHelper::call_Cdiscount_api("GetOrderQuestionList",$get_param,$post_params=array(),$timeout );
		echo "\n ProductList return: \n";
		//print_r($rtn);
	
		return $rtn;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * getting an encrypted mail address to contact a customer about an order.
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$uid
	 * @param 	$storeName		平台账号
	 * @param 	$orderId		平台订单号,string
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/08/13			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function generateDiscussionMailGuid($uid,$storeName,$orderId){
		$timeout=240; //s
		$rtn = [];
		echo "\n enter CdiscountOrderHelper function : generateDiscussionMailGuid,orderid=$orderId";
	
		$cdiscountAccount = SaasCdiscountUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($cdiscountAccount)){
			$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$cdiscount_token = $cdiscountAccount['token'];
	
		$config = array('tokenid' => $cdiscount_token);
		$get_param['config'] = json_encode($config);
		
		$params['orderid']= $orderId;
		$get_param['query_params'] = json_encode($params);
		$rtn=CdiscountProxyConnectHelper::call_Cdiscount_api("generateDiscussionMailGuid",$get_param,$post_params=array(),$timeout );
		echo "\n generateDiscussionMailGuid return: \n";

		//print_r($rtn);
	
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * getting an encrypted mail address to contact a customer about a discussion (claim, retraction, questions).
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$uid
	 * @param 	$storeName		平台账号
	 * @param 	$orderId		平台订单号,string
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/08/13			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getDiscussionMailList($uid,$storeName,$orderId){
		$timeout=240; //s
		$rtn = [];
		echo "\n enter CdiscountOrderHelper function : getDiscussionMailList,orderid=$orderId";
	
		$cdiscountAccount = SaasCdiscountUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($cdiscountAccount)){
			$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$cdiscount_token = $cdiscountAccount['token'];
	
		$config = array('tokenid' => $cdiscount_token);
		$get_param['config'] = json_encode($config);
		
		//try to get order's claim, retraction, questions ids
	
	
		//
		$discussionIds = '12487210';
		$params['discussionIds'][]= $discussionIds;
		$get_param['query_params'] = json_encode($params);
		$rtn=CdiscountProxyConnectHelper::call_Cdiscount_api("getDiscussionMailList",$get_param,$post_params=array(),$timeout );
		echo "\n getDiscussionMailList return: \n";
	
		//print_r($rtn);
	
		return $rtn;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * to update the state of orders
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$uid			int
	 * @param 	$storeName		平台账号 string
	 * @param 	$orderIds		平台订单号
	 *								array like : array('aaa','bbb','ccc')
	 * @param 	$status			string : 'Accepte' or 'Refuse'
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/08/31			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function AccepteOrRefuseOrders($uid , $storeName , $orderIds=[], $state='Accepte'){
		$timeout=240; //s
		$rtn = [];
		echo "\n enter CdiscountOrderHelper function : AccepteOrRefuseOrders ,uid=$uid ,storeName=$storeName;";
		echo "\n orderId=".print_r($orderIds);
		
		$cdiscountAccount = SaasCdiscountUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($cdiscountAccount)){
		$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$cdiscount_token = $cdiscountAccount['token'];
		
		$config = array('tokenid' => $cdiscount_token);
		$get_param['config'] = json_encode($config);
		$params=[];
		if($state=='Accepte'){
			$state='AcceptedBySeller';
		}elseif($state=='Accepte'){
			$state='RefusedBySeller';
		}
		$params['state'] = $state;
		if (!empty($orderIds)){
			$params['orderIds'] = $orderIds;	
		}

		$get_param['query_params'] = json_encode($params);
		$rtn=CdiscountProxyConnectHelper::call_Cdiscount_api("AccepteOrRefuseOrders",$get_param,$post_params=array(),$timeout );
		echo "\n getDiscussionMailList return: \n";
		
		//print_r($rtn);

		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Calculate sales within 15 days by one cdiscount account.
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$store_name		eagle2平台cd账号名	string
	 * @param 	$user_name		cd平台登录账号 string
	 * @param 	$nowTimeStr		调用时间 （like:"2015-11-03 15:20:56"）string
	 +---------------------------------------------------------------------------------------------
	 * @return	array(product_id=>sales_count)
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/03			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function calculateSalesWithin15Days($store_name,$user_name,$nowTimeStr){
		$date= substr($nowTimeStr, 0, 10);
		$datetime = $date." 00:00:00";
		$datetime = strtotime($datetime);
		$fifteenDaysAgo = $datetime-3600*24*15;
		$oneDayAgo = strtotime($date)-3600;
		
		$calculateInDaysAgo = false;//是否统计与1日前
		
		$calculateSales = ConfigHelper::getConfig("CdiscountOrder/CalculateSalesDate",'NO_CACHE');
		
		/**
		 $calculateSalesInfo=>[
		 	'user_name1'=>$date1,
	  		'user_name2'=>$date2,
	  		.
	  		.
	  		.
		  ]
		 */
		if($calculateSales != null){
			$calculateSalesInfo = json_decode($calculateSales,true);
			if(isset($calculateSalesInfo[$user_name])){
				$calculateDate = $calculateSalesInfo[$user_name];
				if( strtotime($calculateDate) < $oneDayAgo )
					$calculateInDaysAgo = true;
			}
			else 
				$calculateInDaysAgo = true;
		}else{
			$calculateInDaysAgo = true;
		}
		
		//上次统计时间在一天以内，跳过统计
		if (!$calculateInDaysAgo) {
			echo "need not to calculate.";
			return true;
		}
		//开始统计
		try{
			$sql="SELECT o.selleruserid, d.sku, d.order_source_itemid, sum(d.quantity) quantity
			FROM `od_order_v2` o,`od_order_item_v2` d
			WHERE o.`order_source_order_id`=d.`order_source_order_id` 
				AND d.sku<>'' 
				AND o.`selleruserid`=:store_name 
				AND (o.`create_time`>=$fifteenDaysAgo AND o.`create_time`<=$datetime) 
			GROUP BY o.`selleruserid`, d.`sku`, d.`order_source_itemid` ";
			
			$command = \Yii::$app->get('subdb')->createCommand($sql);
			$command->bindValue(":store_name",$user_name,\PDO::PARAM_STR);
			$finalSql = $command->getRawSql();
			echo "\n calculateSalesWithin15Days sql:".$finalSql;//liang test
			$rows = $command->queryAll();
			
			$update_offer_id = [];
			foreach ($rows as $index=>$row){
				if(!empty($row['quantity'])){
					$offer = CdiscountOfferList::find()
								->where(['product_id'=>$row['order_source_itemid'],'seller_product_id'=>$row['sku'],'seller_id'=>$user_name])
								->One();
					if($offer<>null){
						$offer->last_15_days_sold = $row['quantity'];
						echo "\n find offer need to update product_id:".$row['order_source_itemid'].',seller_product_id:'.$row['sku'].',seller_id:'.$user_name.',quantity:'.$row['quantity']; //liang test
						if($offer->save(false))
							$update_offer_id[]=$offer->id;
						else 
							echo '\n product_id:'.$row['order_source_itemid'].',seller_product_id:'.$row['sku'].',seller_id:'.$user_name.' update calculate data fails :'.print_r($offer->getErrors(),true);
					}
				}
			}
			
			if(!empty($update_offer_id)){
				$id_str = implode(',', $update_offer_id);
				$resetCount = CdiscountOfferList::updateAll(['last_15_days_sold'=>0],"id not in ($id_str)");
				echo "\n update have no sold in 15 days prods,count=$resetCount";
			}
		
			$calculateSalesInfo[$user_name] = TimeUtil::getNow();
			ConfigHelper::setConfig("CdiscountOrder/CalculateSalesDate",json_encode($calculateSalesInfo));
		}catch (\Exception $e) {
			echo "\n calculateSalesWithin15Days fails :".$e->getMessage();
		}
		
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * if order details have ever prod that offer list hadn't ,create it.
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$orderDetailInfo		array
	 * @param 	$cdiscountAccount		array
	 +---------------------------------------------------------------------------------------------
	 * @return	createRecordCount		int
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/11			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveOrderDetailToOfferListIfIsNew($orderDetailInfo,$cdiscountAccount){
		echo '\n enter saveOrderDetailToOfferListIfIsNew';
		$create = 0;
		$product_info_arr = [];
		$product_sku_list = [];
		//echo "<br>orderDetailInfo:".print_r($orderDetailInfo);
		foreach ($orderDetailInfo as $details){
			foreach ($details as $detail){
				
				if( in_array($detail['productid'],CdiscountOrderInterface::getNonDeliverySku()) )
					continue;
				if( in_array($detail['sku'],CdiscountOrderInterface::getNonDeliverySku()) )
					continue;
				
				if(in_array($detail['sku'], $product_sku_list)){
					continue;
				}else{
					$product_sku_list[] = $detail['sku'];
					//$product_info_arr 以cd order detail的sku为index；sku值为比较准确的product_id
					$product_info_arr[$detail['sku']] = array(
							'sku'=>empty($detail['sku'])?'':$detail['sku'],
							'productid'=>empty($detail['productid'])?'':$detail['productid'],
							'sellerproductid'=>empty($detail['sellerproductid'])?'':$detail['sellerproductid'],
							'productean'=>$detail['productean'],
					);	
				}
			}	
		}
		//echo "<br>product_sku_list:<br>";
		//print_r($product_sku_list);
		
		if(empty($product_info_arr))
			return $create;
		
		try{
			$parent_prod_id_arr = [];
			$prod_id_arr = [];
			$prod_sku_arr = [];//以cd order detail的sku为index；sku值为比较准确的product_id
			foreach ($product_info_arr as &$info){
				$productid = $info['productid'];
				if($info['productid']!==$info['sku'] && stripos($info['sku'], $info['productid'].'-')!==false ){//is a variant child
					$productIdStr = explode('-', $info['sku']);
					$parent_prod_id = $productIdStr[0];
					$parentModel = CdiscountOfferList::find()->where(['product_id'=>$parent_prod_id])->andWhere("parent_product_id is null")->one();
					if($parentModel==null){
						echo "\n productid:".$info['productid']." is a variant child , insert parent:".$parent_prod_id;
						$parentModel = new CdiscountOfferList();
						$parentModel->product_id = $parent_prod_id;
						$parentModel->product_ean = '';
						$parentModel->seller_product_id = '';
						$parentModel->sku = $parent_prod_id;
						$parentModel->seller_id = $cdiscountAccount['username'];
						$parentModel->concerned_status = "I";
						$parentModel->save(false);
						if(!in_array($parent_prod_id,$parent_prod_id_arr))
							$parent_prod_id_arr[] = $parent_prod_id;
						
					}
					$productid = $info['sku'];
					$info['productid'] = $info['sku'];
					$info['parent_product_id'] = $parent_prod_id;
				}
				if(!in_array($productid,$prod_id_arr)){
					$prod_id_arr[] = $productid;
					$prod_sku_arr[] = $info['sku'];//以cd order detail的sku为index；sku值为比较准确的product_id
				}
			}
			//先抓取父产品，以便子产品有信息
			if(!empty($parent_prod_id_arr)){
				CdiscountOfferSyncHelper::syncProdInfoWhenGetOrderDetail($cdiscountAccount['uid'],$parent_prod_id_arr,1,$cdiscountAccount['username']);
			}
			//echo "<br>prod_id_arr:".print_r($prod_id_arr);
			if(!empty($prod_sku_arr)){
				//找出offer表里面没有的productid，保存他们
				$offerList = CdiscountOfferList::find()->select(['product_id','sku','seller_product_id'])
					->where(['in','product_id',$prod_sku_arr])
					->andWhere(['seller_id'=>$cdiscountAccount['username']])
					->asArray()->all();
				
				$not_insert_sku_list = [];
				foreach ($offerList as $offer){
					$not_insert_sku_list[] = $offer['product_id'];
				}
				$insert_sku_list = array_diff($prod_sku_arr,$not_insert_sku_list);
				
				$insert_prod_info_arr = [];
				foreach ($insert_sku_list as $sku){
					if(!empty($product_info_arr[$sku])){
						$insert_prod_info_arr[]=array(
							'product_id'=>$product_info_arr[$sku]['sku'],
							'product_ean'=>$product_info_arr[$sku]['productean'],
							'seller_product_id'=>$product_info_arr[$sku]['sellerproductid'],
							'sku'=>$product_info_arr[$sku]['sku'],
							'seller_id'=>$cdiscountAccount['username'],
							'parent_product_id'=>!empty($product_info_arr[$sku]['parent_product_id'])?$product_info_arr[$sku]['parent_product_id']:null,
							'concerned_status'=>"I",
						);
					}
				}
				//echo "<br>insert_prod_info_arr:<br>";
				//print_r($insert_prod_info_arr);
				
				if(!empty($insert_prod_info_arr)){
					$offerInsert = SQLHelper::groupInsertToDb('cdiscount_offer_list', $insert_prod_info_arr);
					//mark error log
					if($offerInsert!=count($insert_prod_info_arr)){
						\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',date('Y-m-d H:i:s')." CdInsertOfferError:".count($insert_prod_info_arr)."need to insert, only ".$offerInsert." inserted" ],"edb\global");
					}else 
						$create = $offerInsert;
				}
			
				//抓取商品信息
				CdiscountOfferSyncHelper::syncProdInfoWhenGetOrderDetail($cdiscountAccount['uid'],$prod_id_arr,3,$cdiscountAccount['username']);
			}
			return $create;
		}catch (\Exception $e) {
			echo "\n saveOrderDetailToOfferListIfIsNew error :".$e->getMessage();
			return $create;
		}
		
		
		/*旧版
		try{
			foreach ($orderDetailInfo as $detail){
				if( in_array($detail['productid'],CdiscountOrderInterface::getNonDeliverySku()) )
					continue;
				
				$productid = $detail['productid'];
				if($detail['productid']!==$detail['sku'] && stripos($detail['sku'], $detail['productid'].'-')!==false ){//is a variant child
					$productIdStr = explode('-', $detail['sku']);
					$parent_prod_id = $productIdStr[0];
					$parentModel = CdiscountOfferList::find()->where(['product_id'=>$parent_prod_id])->one();
					if($parentModel==null){
						echo "\n productid:".$detail['productid']." is a variant child , insert parent::".$parent_prod_id;
						$parentModel = new CdiscountOfferList();
						$parentModel->product_id = $parent_prod_id;
						$parentModel->product_ean = '';
						$parentModel->seller_product_id = '';
						$parentModel->sku = $parent_prod_id;
						$parentModel->seller_id = $cdiscountAccount['username'];
							
						$parentModel->save(false);
						CdiscountOfferSyncHelper::syncProdInfoWhenGetOrderDetail($cdiscountAccount['uid'],$parent_prod_id);
					}
					$productid = $detail['sku'];
				}
				
				$offer = CdiscountOfferList::find()->where(['product_id'=>$productid])->one();
				if($offer <> null)
					continue;
				else {
					echo "\n productid:".$productid.",sellerproductid:".$detail['sellerproductid'];
					$offer = new CdiscountOfferList();
					$offer->product_id = $productid;
					$offer->product_ean = $detail['productean'];
					$offer->seller_product_id = $detail['sellerproductid'];
					$offer->sku = $detail['sku'];
					$offer->seller_id = $cdiscountAccount['username'];
					
					if($offer->save(false)){
						$create++;
						CdiscountOfferSyncHelper::syncProdInfoWhenGetOrderDetail($cdiscountAccount['uid'],$productid);
					}
				}
			}
		}catch (\Exception $e) {
			echo "\n saveOrderDetailToOfferListIfIsNew error :".$e->getMessage();
		}
		
		return $create;
		*/
	}
	
	/**
	 * 将Cdisocount原始表信息转换成od_order_v2表的信息
	 */
	public static function formatSrcOrderDataToOmsOrder($cdiscountOrderData,$cdiscountAccount,$getEmail=false){
		$importOrderData = array();
	
		//$importOrderData['order_id'] = $cdiscountOrderData['OrderNumber'];
		if (!empty($cdiscountOrderData['orderstate'])){
			/**
			 * 	cdiscount order state
			 *  State 'WaitingForShipmentAcceptation' means that the order is waiting to ship.
			 *  State 'Shipped' means that the order has been marked as shipped by you.
			 *  State 'CancelledByCustomer' means that the order has been refunded by customer and should not be fulfilled.
			 *  State 'PaymentInProgress' means that the order is waiting customer to pay
			 *
			 *  eagle order status
			 *  100（未付款）、200（已付款）、300（发货处理中）、400（已发货）、500（已完成）、600（已取消）
			 * */
			$CdiscountStateToEagleStatusMapping = array(
					'WaitingForShipmentAcceptation'=> 200 ,
					'Shipped'=>500,
					'CancelledByCustomer'=>600,
					'AcceptedBySeller'=>200,
					'PaymentInProgress'=>100,
					'RefusedBySeller'=>600,
					'AutomaticCancellation'=>600,
					'PaymentRefused'=>600,
					'ShipmentRefusedBySeller'=>400,
					'RefusedNoShipment'=>400,
			);
			$importOrderData['order_status'] = $CdiscountStateToEagleStatusMapping[$cdiscountOrderData['orderstate']];
			$importOrderData['order_source_status'] = $cdiscountOrderData['orderstate'];
		}
	
		if (isset($importOrderData['order_status'] )){
			//发货状态：1.已发货；0.未发货 #非已发货状态暂时定义为未发
			$importOrderData['shipping_status'] = (($importOrderData['order_status'] == 500)?1:0);
		}
	
		$importOrderData['pay_status'] = ($importOrderData['order_status']<200)?0:1;
	
		$importOrderData['order_source'] = 'cdiscount';//订单来源
	
		$addinfo = json_decode($cdiscountOrderData['addinfo'],true);
		
		$importOrderData['order_type'] = '';//订单类型
		if(isset($addinfo['FBC']) && $addinfo['FBC'])
			$importOrderData['order_type'] = 'FBC';
	
		if(isset($addinfo['FBC']) && $addinfo['FBC']){//如果是FBC订单，则认为是已发货。
			$importOrderData['order_status']=500;
			$importOrderData['shipping_status']=1;
		}
	
		if (!empty($cdiscountOrderData['ordernumber'])){
			$importOrderData['order_source_order_id'] = $cdiscountOrderData['ordernumber']; //订单来源的订单id
		}
	
		$importOrderData['order_source_site_id'] = 'FR'; //订单来源平台下的站点,Cdiscount暂无分站点,只有法国站
	
		if (isset($cdiscountOrderData['selleruserid']))
			$importOrderData['selleruserid'] = $cdiscountOrderData['selleruserid']; //订单来源平台卖家用户名(下单时候的用户名)
		if (isset($cdiscountOrderData['saas_platform_user_id']))
			$importOrderData['saas_platform_user_id'] = $cdiscountOrderData['saas_platform_user_id']; //订单来源平台卖家用户名(下单时候的用户名)
	
		$buyer_name='';
		if (!empty($cdiscountOrderData['customer_civility']) && !is_array(json_decode($cdiscountOrderData['customer_civility'],true)))
			$buyer_name.=$cdiscountOrderData['customer_civility']." ";
		if(!empty($cdiscountOrderData['customer_firstname']) && !is_array(json_decode($cdiscountOrderData['customer_firstname'],true)))
			$buyer_name.=$cdiscountOrderData['customer_firstname']." ";
		if(!empty($cdiscountOrderData['customer_lastname']) && !is_array(json_decode($cdiscountOrderData['customer_lastname'],true)))
			$buyer_name.=$cdiscountOrderData['customer_lastname'];
		
		$importOrderData['source_buyer_user_id'] = $buyer_name; //买家名称
	
		if (!empty($cdiscountOrderData['creationdate']))
			$importOrderData['order_source_create_time'] = strtotime($cdiscountOrderData['creationdate']); //订单在来源平台的下单时间
	
		$shippingConsignee = '';
		if(!empty($cdiscountOrderData['shipping_civility']) && !is_array(json_decode($cdiscountOrderData['shipping_civility'],true)))
			$shippingConsignee.=$cdiscountOrderData['shipping_civility']." ";
		if(!empty($cdiscountOrderData['shipping_firstname']) && !is_array(json_decode($cdiscountOrderData['shipping_firstname'],true)))
				$shippingConsignee.=$cdiscountOrderData['shipping_firstname']." ";
		if(isset($cdiscountOrderData['shipping_lastname']) && !is_array(json_decode($cdiscountOrderData['shipping_lastname'],true)))
				$shippingConsignee.=$cdiscountOrderData['shipping_lastname']." ";
		
		$importOrderData['consignee'] = $shippingConsignee; //收货人
	
		if (!empty($cdiscountOrderData['shipping_zipcode']) && !is_array(json_decode($cdiscountOrderData['shipping_zipcode'],true)))
			$importOrderData['consignee_postal_code'] = $cdiscountOrderData['shipping_zipcode']; //收货人邮编
	
		if (isset($cdiscountOrderData['customer_phone']) && !is_array(json_decode($cdiscountOrderData['customer_phone'],true)))
			$importOrderData['consignee_phone'] =$cdiscountOrderData['customer_phone']; //收货人电话
	
		if (isset($cdiscountOrderData['customer_mobilephone']) && !is_array(json_decode($cdiscountOrderData['customer_mobilephone'],true)))
			$importOrderData['consignee_mobile'] =$cdiscountOrderData['customer_mobilephone']; //收货移动电话
	
		if(empty($importOrderData['consignee_email']) && $getEmail)
			$importOrderData['consignee_email'] = CdiscountOrderInterface::getEmailByOrderID($cdiscountAccount, $cdiscountOrderData['orderuumber']);
	
		if($importOrderData['consignee_email']=='')
			unset($importOrderData['consignee_email']);
	
		if (isset($cdiscountOrderData['shipping_companyname']) && !is_array(json_decode($cdiscountOrderData['shipping_companyname'],true)))
			$importOrderData['consignee_company'] =$cdiscountOrderData['shipping_companyname']; //收货人公司
	
		if (!empty($cdiscountOrderData['shipping_country']) && !is_array(json_decode($cdiscountOrderData['shipping_country'],true))){
			$importOrderData['consignee_country'] = $cdiscountOrderData['shipping_country']; //收货人国家名
			$importOrderData['consignee_country_code'] = $cdiscountOrderData['shipping_country']; //收货人国家代码
		}else{
			$importOrderData['consignee_country'] = 'FR'; //收货人国家名
			$importOrderData['consignee_country_code'] = 'FR'; //收货人国家代码
		}
	
		if (isset($cdiscountOrderData['shipping_city']) && !is_array(json_decode($cdiscountOrderData['shipping_city'],true)))
			$importOrderData['consignee_city'] = $cdiscountOrderData['shipping_city']; //收货人城市
	
		if (isset($cdiscountOrderData['shipping_placename']) && !is_array(json_decode($cdiscountOrderData['shipping_placename'],true)))
			$importOrderData['consignee_district'] = $cdiscountOrderData['shipping_placename']; //收货人地区
	
		$importOrderData['consignee_address_line1']='';
		if (isset($cdiscountOrderData['shipping_street']) && !is_array(json_decode($cdiscountOrderData['shipping_street'],true)))
			$importOrderData['consignee_address_line1'] = $cdiscountOrderData['shipping_street']; //收货人地址1
		//Apartment
		if (isset($cdiscountOrderData['shipping_apartmentnumber']) and !is_array(json_decode($cdiscountOrderData['shipping_apartmentnumber'],true))){
			if(stripos($cdiscountOrderData['shipping_apartmentnumber'],"apt")===false && stripos($cdiscountOrderData['shipping_apartmentnumber'],"app")===false && stripos($cdiscountOrderData['shipping_apartmentnumber'],"Apartment")===false)
				$Apartment = "Apt.".$cdiscountOrderData['shipping_apartmentnumber'];
			else
				$Apartment = $cdiscountOrderData['shipping_apartmentnumber'];
	
			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = "Apt.".$Apartment;
			else
				$importOrderData['consignee_address_line1'] = $Apartment.";".$importOrderData['consignee_address_line1'];
		}
		//Building
		if(isset($cdiscountOrderData['shipping_building']) && !is_array(json_decode($cdiscountOrderData['shipping_building'],true))){
			if(stripos($cdiscountOrderData['shipping_building'],"bât")===false && stripos($cdiscountOrderData['shipping_building'],"BTMENT")===false && stripos($cdiscountOrderData['shipping_building'],"Bâtiment")===false)
				$Btment = "bât.".$cdiscountOrderData['shipping_building'];
			else
				$Btment = $cdiscountOrderData['shipping_building'];
	
			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = $Btment;
			else
				$importOrderData['consignee_address_line1'] = $Btment.';'.$importOrderData['consignee_address_line1'];
		}
	
		//echo "\n fianl consignee_address_line1 is:".$importOrderData['consignee_address_line1'];//liang test
		if (isset($cdiscountOrderData['shipping_address1']) && !is_array(json_decode($cdiscountOrderData['shipping_address1'],true)))
			$importOrderData['consignee_address_line2'] = $cdiscountOrderData['shipping_address1']; //收货人地址2
	
		if (isset($cdiscountOrderData['shipping_address2']) && !is_array(json_decode($cdiscountOrderData['shipping_address2'],true)))
			$importOrderData['consignee_address_line3'] = $cdiscountOrderData['shipping_address2'];
	
		if (!empty($importOrderData['order_source_create_time'] ))
			$importOrderData['paid_time'] = $importOrderData['order_source_create_time'] ; //订单付款时间
	
		if(!empty($cdiscountOrderData['ValidatedTotalAmount']))
			$importOrderData['total_amount'] = $cdiscountOrderData['ValidatedTotalAmount'];
			
		if(!empty($cdiscountOrderData['ValidatedTotalShippingCharges']))
			$importOrderData['shipping_cost'] = $cdiscountOrderData['ValidatedTotalShippingCharges'];
	
		if (isset($cdiscountOrderData['SiteCommissionValidatedAmount'] ))
			$importOrderData['commission_total'] = $cdiscountOrderData['SiteCommissionValidatedAmount']; //平台佣金
		else
			$importOrderData['commission_total'] = 0;
	
		//yzq 20151220, 因为外面的其他地方还是会读取这个field，还需要这个field应付外面的读取，否则会出错
		$importOrderData['discount_amount'] = 0; //
	
		//if (isset($importOrderData['subtotal']) && isset($importOrderData['shipping_cost']) && isset($importOrderData['discount_amount']))
		//	$importOrderData['grand_total'] = $importOrderData['subtotal'] + $importOrderData['shipping_cost'] - $importOrderData['discount_amount']; //合计金额(产品总价格 + 运费 - 折扣 = 合计金额)
		$importOrderData['currency'] = 'EUR'; //货币
	
		return $importOrderData;
	}

	/**
	 * 将Cdisocount原始detail表信息转换成od_order_item_v2表的信息
	 */
	public static function formatSrcOrderItemsDataToOmsOrder($cdiscountOrderItems,$order_number){
		$importOrderItems = array();
		$total_amount=0;
		$delivery_amonut=0;
		$total_qty=0;
		
		foreach ($cdiscountOrderItems as $item){
			$row=array();
			$row['order_source_order_id'] = $order_number;

			$row['sku'] = '';
			if(is_string($item['sellerproductid']) && !is_array(json_decode($item['sellerproductid'],true)))
				$row['sku']=$item['sellerproductid'];
			if (empty($row['sku']))
				$row['sku'] = $item['productid'];

			$row['source_item_id'] = $item['productid'];
			
			//$row['order_source_order_item_id'] = $item['productid'];

			if(is_string($item['name']) && !is_array(json_decode($item['name'],true))){
				$row['product_name']=$item['name'];
			}else{
				$srcOffer = CdiscountOfferList::find()->where(['seller_product_id'=>$row['sku']])->one();
				if($srcOffer<>null)
					$row['product_name'] = $srcOffer->name;
			}
			if(empty($row['product_name'])) $row['product_name']=$row['sku'];

			$row['order_source_itemid']=$item['Sku'];

			$row['price'] = $item['purchaseprice'] / $item['quantity'];
			$row['ordered_quantity'] = $item['quantity'];
			$row['quantity']=$item['quantity'];
			
			$row['platform_status'] = $item['AcceptationState'];
			
			if($item['acceptationstate']=='ShippedBySeller'){
				$row['sent_quantity']=$item['quantity'];
				$row['packed_quantity']=$item['quantity'];
			}

			$row['shipping_price']=$item['unitadditionalshippingcharges'];

			$row['photo_primary']='';//cdiscount response none product photo info

			$total_amount += $item['purchaseprice'];
			if($row['shipping_price'] > $delivery_amonut)
				$delivery_amonut = $row['shipping_price']*$row['quantity'];
			$total_qty += $row['ordered_quantity'];
			$items[]=$row;
		}
		
		$importOrderItems['items']=$items;
		$importOrderItems['total_amount']=$total_amount;
		$importOrderItems['delivery_amonut']=$delivery_amonut;
		$importOrderItems['total_qty']=$total_qty;
		return $importOrderItems;
	}
	
	
	public static $OMS_STATUS_CD_STATUS_MAPPING = [
		'100'=>['PaymentInProgress' ],
		'200'=>['AcceptedBySeller', 'WaitingForShipmentAcceptation' ],
		'300'=>['WaitingForShipmentAcceptation' ],
		'400'=>['WaitingForShipmentAcceptation' ],//CD平台如果已发货，将会同步回来Shipped状态，订单自动转到已完成，否则就是同步出现问题。因此这里发货中状态还是设置为WaitingForShipmentAcceptation
		'500'=>['Shipped' ],
		'600'=>['CancelledByCustomer','RefusedBySeller','AutomaticCancellation','RefusedNoShipment','ShipmentRefusedBySeller','PaymentRefused',],
	];
	
	public static $CD_OMS_WEIRD_STATUS = [
		'sus'=>'CD后台状态和小老板状态不同步',//satatus unSync 状态不同步
		'wfs'=>'提交发货或提交物流',//waiting for shipment
		'wfd'=>'交运至物流商',//waiting for dispatch
		'wfss'=>'等待手动标记发货，或物流模块"确认已发货"',//waiting for sing shipped ,or confirm dispatch
		'tuol'=>'物流未上网',//track unOnLine
	];
	
	
	/**
	 * @description			定期自动检测用户的CD订单，如果检测到订单有可能异常，自动add tag
	 * @access static
	 * @param $job_id		job action number
	 * @return				array
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015/12/25	初始化
	 */
	public static function cronAutoAddTagToCdiscountOrder($job_id){
		try {
			//set runtime log
			$the_day=date("Y-m-d",time());
			$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-CheckOrder' and `addinfo`='job_id=".$job_id."' "  );
			$record = $command->queryOne();
			if(!empty($record)){
				$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`the_day`='".$the_day."'  where app='CDOMS' and info_type='runtime-CheckOrder' and `addinfo`='job_id=".$job_id."' ";
				$command = Yii::$app->db_queue->createCommand( $sql );
				$affect_record = $command->execute();
			}else{
				$sql = "INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('CDOMS','runtime-CheckOrder','normal','','job_id=".$job_id."','','".$the_day."','".date("Y-m-d H:i:s",time())."')";
				$command = Yii::$app->db_queue->createCommand( $sql );
				$affect_record = $command->execute();
			}//set runtime log end
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
	
			$two_days_ago = strtotime('-2 days');
			$five_days_ago = strtotime('-5 days');
	
			$SAASCDISCOUNTUSERLIST = SaasCdiscountUser::find()->select(" distinct `uid` ")->where("is_active='1' and initial_fetched_changed_order_since is not null")
				// ->andwhere(" uid%3=$job_id ")// TODO 开启CD多进程时候做相应去注释
				->orderBy("last_order_success_retrieve_time asc")->asArray()->all();
						
			foreach($SAASCDISCOUNTUSERLIST as $cdiscountAccount ){
				$uid = $cdiscountAccount['uid'];
				if (empty($uid)){
					//异常情况
					$message = "site id :".$cdiscountAccount['site_id']." uid:0";
					echo "\n ".$message;
					continue;
				}
 
				$updateTime =  TimeUtil::getNow();
				
				//删除已完成订单的weird_status
				OdOrder::updateAll(['weird_status'=>'']," order_status in (500,600) and (weird_status is not null or weird_status<>'') and order_source='cdiscount' ");
				echo "\n cleared all complete order's weird_status";
				
				$ot_orders = OdOrder::find()
					->where(['order_source'=>'cdiscount'])
					->andWhere(" update_time<='$two_days_ago' ")
					->andWhere(['in','order_status',[200,300,400]])
					->asArray()->all();
				echo "\n has ot_order :".count($ot_orders);
				
				$status_mapping = self::$OMS_STATUS_CD_STATUS_MAPPING;
				$updateOrders=[];
				$updateOrders['sus']=[];
				$updateOrders['wfs']=[];
				$updateOrders['wfd']=[];
				$updateOrders['wfss']=[];
				$updateOrders['tuol']=[];
				foreach ($ot_orders as $ot){
					//状态对应不上,weird_status暂时设置其优先级比操作超时优先级高(覆盖)
					if(isset( $status_mapping[$ot['order_status']]) && !in_array($ot['order_source_status'], $status_mapping[$ot['order_status']])){
						$updateOrders['sus'][] = $ot['order_id'];
						continue;
					}
					if($ot['order_status']==200){
						$updateOrders['wfs'][] = $ot['order_id'];
						continue;
					}
					if($ot['order_status']==300){
						$updateOrders['wfd'][] = $ot['order_id'];
						continue;
					}
					if($ot['order_status']==400){
						$updateOrders['wfss'][] = $ot['order_id'];
						continue;
					}
					//5天未上网
					if($ot['delivery_time']<$five_days_ago){
						$untrackStatus = array('checking','no_info','suspend','untrackable');//未上网状态mapping
						if(in_array($ot['logistic_status'],$untrackStatus)){
							$updateOrders['tuol'][] = $ot['order_id'];
							continue;
						}
					}
				}
				
				foreach ($updateOrders as $s=>$id_arr){
					if(!empty($id_arr)){
						OdOrder::updateAll(['weird_status'=>$s],['order_source'=>'cdiscount','order_id'=>$id_arr]);
					}
				}
			}//end of each cdiscount user uid
		} catch (\Exception $e) {
			echo "\n cronAutoFetchRecentOrderList Exception:".$e->getMessage();
		}
	}//end of cronAutoFetchUnFulfilledOrderList
	
	
	public static function cronManualFetchOrderByCreationdate(){
		global $CACHE;
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
		
		//echo '\n background service enter, job id:'.$CACHE['JOBID'];
		
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit
		
		$JOBID=$CACHE['JOBID'];
		$current_time=explode(" ",microtime());	$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
		
		$currentManualQueueVersion = ConfigHelper::getGlobalConfig("CdiscountOrder/manualQueueVersion",'NO_CACHE');
		if (empty($currentManualQueueVersion))
			$currentManualQueueVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$manualQueueVersion))
			self::$manualQueueVersion = $currentManualQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$manualQueueVersion <> $currentManualQueueVersion){
			TrackingAgentHelper::extCallSum("",0,true);
			exit("Version new $currentManualQueueVersion , this job ver ".self::$manualQueueVersion." exits for using new version $currentManualQueueVersion.");
		}
		
		$cdiscountAccount = SaasCdiscountUser::find()
			->where("is_active='1' and initial_fetched_changed_order_since is not null ")
			->andWhere(" sync_status='R' and sync_type='M' ")
			->orderBy("last_order_success_retrieve_time asc")->one();
		if(empty($cdiscountAccount)){//没有需要处理的账号
			echo "\n none account to handel;";
			return 'n/a';
		}
		$uid = $cdiscountAccount->uid;
		
		$cdiscountAccount = self::setCdiscountUserToken($cdiscountAccount);
		if(empty($cdiscountAccount->token)){
			//获取token出了问题
			echo "\n get token failed;";
			
			$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,'获取token出现问题');
			if(!$mark['success']){
				echo "\n mark account synching error:".$mark['message'];
				return false;
			}
			
			$cdiscountAccount->order_retrieve_message = '获取token出现问题，请等待一段时间，或联系客服';
			$cdiscountAccount->save(false);
			return false;
		}
		
		if( strtotime($cdiscountAccount->token_expired_date) < strtotime("-1 days") ){
			$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,'token已过期');
			if(!$mark['success']){
				echo "\n mark account synching error:".$mark['message'];
				return false;
			}
			
			$cdiscountAccount->order_retrieve_message = 'token已过期，请检测绑定信息中的 账号，密码是否正确。';
			if (!$cdiscountAccount->save()){
				echo "\n failure to save cdiscount account info ,error:";
				echo "\n uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true);
				return false;
				//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"failure to save cdiscount operation info ,uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true)],"edb\global");
			}else{
				echo "\n CdiscountAccount model save(token expired) !";
				return false;
			}
		}
		
		//获取店铺名
		if(empty($cdiscountAccount->shopname)){
			$shop = CdiscountOfferSyncHelper::getSellerInfo($cdiscountAccount->token);
			if(isset($shop['proxyResponse']['success']) && isset($shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName']))
				$cdiscountAccount->shopname = $shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName'];
			$cdiscountAccount->save(false);
		}
		
		$sync_info = json_decode($cdiscountAccount->sync_info,true);
		if(empty($sync_info) || empty($sync_info['begincreationdate']) || empty($sync_info['endcreationdate'])){
			echo "\n account sync param lost";
			return false;
		}

		$begincreationdate =  date("Y-m-d\TH:i:s",strtotime($sync_info['begincreationdate']));
		$endcreationdate = date("Y-m-d\TH:i:s",strtotime($sync_info['endcreationdate']));
		$updateTime =  TimeUtil::getNow();
		$the_day = date("Y-m-d");
		
		echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
		if (empty($uid)){
		//异常情况
			echo "\n site id :".$cdiscountAccount['site_id']." uid:0";
			//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			return false;
		}
		
 
		
		//统计15日内产品售出数量
		echo "\n Start to calculateSalesWithin15Days: ";
		self::calculateSalesWithin15Days($cdiscountAccount['store_name'], $cdiscountAccount['username'],$updateTime);
		
		$getOrderCount = 0;
		
		if (empty($cdiscountAccount->last_order_success_retrieve_time) or $cdiscountAccount->last_order_success_retrieve_time=='0000-00-00 00:00:00'){
			//如果还没有初始化完毕，就什么都不do
			echo "\n uid=$uid haven't initial_fetched !";
			$cdiscountAccount->order_retrieve_message = "account haven't initial_fetched";
			$cdiscountAccount->save();
			return false;
		}else{
			//同步锁定
			$mark = CdiscountOrderInterface::markSaasAccountOrderSynching($cdiscountAccount,'M');
			if(!$mark['success']){
				echo "\n mark account synching error:".$mark['message'];
				return false;
			}
			//start to get unfulfilled orders
			echo "\n".TimeUtil::getNow()." start to get $uid order for ".$cdiscountAccount['store_name']." since $begincreationdate \n"; //ystest
			
			//大客户链接时间延长
			$time_out=120;
			if((int)$cdiscountAccount['uid']==8088) $time_out=300;
			
			$orders = self::_getAllOrdersSince($cdiscountAccount['token'], $begincreationdate, $endcreationdate, true, $time_out);
							
			if (empty($orders['success'])){
				echo "\n fail to connect proxy :".$orders['message'];
				//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
				$cdiscountAccount->order_retrieve_message = 'fail to connect proxy';
				$cdiscountAccount->save();
				
				$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,"fail to connect proxy  :".$orders['message']);
				if(!$mark['success']){
					echo "\n mark account sync finished error:".$mark['message'];
					return false;
				}
			}
				
			//echo "\n".TimeUtil::getNow()." got results, start to insert oms \n"; //ys test
			//accroding to api respone  , update the last retrieve order time and last retrieve order success time
			if(isset($orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'])){
				echo "\n".$orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
				$cdiscountAccount->order_retrieve_message = $orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
				$cdiscountAccount->save();
				$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,$cdiscountAccount->order_retrieve_message);
				if(!$mark['success'])
					echo "\n mark account sync finished error:".$mark['message'];
					
				return false;
			}
			if (!empty ($orders['proxyResponse']['success'])){
				//print_r($orders); //liang test
				if(isset($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']))
					echo "\n isset ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
				//sync cdiscount info to cdiscount order table
				if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])){
					echo "\n !empty ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
					//print_r($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'][0]);
								
					$getOrderCount = count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']);
		
					$rtn = self::_InsertCdiscountOrder($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'] , $cdiscountAccount,$the_day);
					//\Yii::info(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$cdiscountAccount['token']],"edb\global");
		
					if($rtn['success']){
						
					}else{
						echo "\n ".$rtn['message'];
						$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',$getOrderCount,"order insert to oms failed");
						if(!$mark['success'])
							echo "\n mark account sync finished error:".$mark['message'];

						return false;
					}
				}else{
					if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OperationSuccess']) &&
					$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OperationSuccess']=='true'
					){
						echo "\n get none order";
					}else{
						echo "\n OperationSuccess=false:".(empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])?'':$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage']);
						if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])){
							if(stripos($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'],'Vendeur')===false){
								
							}else 
								$cdiscountAccount->is_active = 3;//店铺被封
						}
						
						$cdiscountAccount->order_retrieve_message = "OperationSuccess=false:".(empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage'])?'':$orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['ErrorMessage']);
						$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,$cdiscountAccount->order_retrieve_message);
						if(!$mark['success'])
							echo "\n mark account sync finished error:".$mark['message'];
						
						return false;
					}
				}//end of GetOrderListResult empty or not
				
				$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'C',$getOrderCount);
				if(!$mark['success'])
					echo "\n mark account sync finished error:".$mark['message'];
			}else{
				//print_r($orders);
				$proxy_error = '';
				if (!empty ($orders['proxyResponse']['message'])){
					$proxy_error = "uid = $uid proxy error  :".$orders['proxyResponse']['message'];
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  :".$orders['proxyResponse']['message'].$cdiscountAccount['token'] ],"edb\global");
				}else{
					$proxy_error = "uid = $uid proxy error : any respone message ";
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  : not any respone message".$cdiscountAccount['token']],"edb\global");
				}
				echo $proxy_error;
				$mark = CdiscountOrderInterface::markSaasAccountOrderSyncFinished($cdiscountAccount,'F',0,$proxy_error);
				if(!$mark['success'])
					echo "\n mark account sync finished error:".$mark['message'];
				
				return false;
			}
			//end of getting orders from cdiscount server
		
			if (empty ($orders['proxyResponse']['message']))
				$cdiscountAccount->order_retrieve_message = '';//to clear the error msg if this sync ok 
		
			if (!$cdiscountAccount->save()){
				echo "\n failure to save cdiscount account info ,error:";
				echo "\n uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true);
				//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"failure to save cdiscount operation info ,uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true)],"edb\global");
			}else
				echo "\n CdiscountAccount model save !";
		}//end of sync order
	}
	
	/**
	 * 出现故障导致拉单失败，故障恢复后，要重置失败开始时间后所有拉单失败的用户的 拉单状态 和 last success time，使得拉单job帮他们重新拉取
	 * @param	dateTime/stamp	$time		//故障开始时间
	 * @param	array			$puids		//可选，指定修复的puid
	 * @return	array('success'=>boolean ,'msg'=>string)
	 * @author	lzhl	2018-05-03
	 */
	public static function resetSyncOrderErr($time,$puids=[]){
		if(!is_numeric($time)){
			$stampTime = strtotime($time);
			$dateTime = $time;
		}else{
			$stampTime = $time;
			$dateTime = date("Y-m-d H:i:s",$time);
		}
		$toDateTime = date("Y-m-d H:i:s", ($stampTime-3600*12) );//重新拉取的目标时间为故障前的12小时
		
		
		if(!empty($puids)){
			SaasCdiscountUser::updateAll(["sync_status"=>"F","last_order_retrieve_time"=>$toDateTime,"last_order_success_retrieve_time"=>$toDateTime]," `is_active`=1 and (`sync_status`='R' or `sync_status`='P') and `last_order_retrieve_time`>'$toDateTime' and uid in (".implode(',', $puids).") ");
		}else{
			SaasCdiscountUser::updateAll(["sync_status"=>"F","last_order_retrieve_time"=>$toDateTime,"last_order_success_retrieve_time"=>$toDateTime]," `is_active`=1 and (`sync_status`='R' or `sync_status`='P') and `last_order_retrieve_time`>'$toDateTime' ");
		}
	}
}
