<?php
namespace eagle\modules\order\helpers;
use yii;
use yii\data\Pagination;

use eagle\modules\listing\models\BonanzaApiQueue;
use eagle\modules\order\models\BonanzaOrder;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\BonanzaOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\models\SysCountry;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\listing\helpers\BonanzaProxyConnectHelper;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\models\SaasBonanzaUser;
use eagle\modules\listing\helpers\BonanzaOfferSyncHelper;
use eagle\modules\listing\models\BonanzaOfferList;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\HttpHelper;
use common\helpers\Helper_Array;
use eagle\models\OdOrderShipped;
use eagle\models\QueueSyncshipped;
use eagle\modules\platform\apihelpers\BonanzaAccountsApiHelper;
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
 * Bonanza订单模板业务
 +------------------------------------------------------------------------------
 * @category	item
 * @package		Helper/item
 * @subpackage  Exception
 * @author		lzhl
 +------------------------------------------------------------------------------
 */
class BonanzaOrderHelper {
    
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
        
        $query = SaasBonanzaUser::find();
        if (!empty($uid)){
            $query->where(['uid'=>$uid]);
        }
        $AccountList = BonanzaAccountsApiHelper::ListAccounts($uid);
        $syncList = [];
        foreach($AccountList as $account){
            $orderSyncInfo = BonanzaAccountsApiHelper::getBonanzaOrderSyncInfo($account['site_id'],$account['uid']);
            if($orderSyncInfo['success']){
                $syncList[$account['store_name']] = $orderSyncInfo['result'];//is_active;last_time;message;status
                $syncList[$account['store_name']]['store_name'] = $account['store_name'];
            }else{
                $syncList[$account['store_name']]['store_name'] = $account['store_name'];
                $syncList[$account['store_name']]['is_active'] = '--';
                $syncList[$account['store_name']]['last_time'] = '--';
                $syncList[$account['store_name']]['message'] = '无该账号信息';
                $syncList[$account['store_name']]['status'] = '';
            }
        }
        return $syncList;
    }//end of getOrderSyncInfoDataList
	
	public static $orderStatus_Unshipped='WaitingForShipmentAcceptation';
	public static $orderStatus = array(
			'WaitingForShipmentAcceptation',
			'CancelledByCustomer',
			//'WaitingForSellerAcceptation',
			'AcceptedBySeller',
			//'PaymentInProgress',
			'Shipped',
			'RefusedBySeller',
			'AutomaticCancellation',
			//'PaymentRefused',
			'ShipmentRefusedBySeller',
			'RefusedNoShipment',
	);
	
	public static $ShippingCode = array(
			'STD'=>'标准',//Standard
			'TRK'=>'跟踪',//tracking
			'REG'=>'挂号',//registered
			'COL'=>'Collissimo',
			'RCO'=>'Relay colis',
			'REL'=>'Mondial Relay',
			'SO1'=>'So Colissimo',
			'MAG'=>'in shop',
	);
	
	public static $CAN_SHIP_ORDERITEM_STATUS = array("Complete,Active","Complete,Completed","Complete,Shipped");
	
	public static $bn_source_status_mapping = [
	    'checkoutStatus'=>[
	        'Complete'=>'Complete（付款完成）',
	        'Incomplete'=>'Incomplete（订单失败）',
	        'InProcess'=>'InProcess（付款中）',
	        'Invoiced'=>'Invoiced（卖家已接受订单）',
	        'Pending'=>'Pending（待接受）',
	    ],
	    'orderStatus'=>[
	        'Active'=>'Active（订单尚未检出）',
	        'Cancelled'=>'Cancelled（订单取消）',
	        'Completed'=>'Completed（发货中）',
	        'Incomplete'=>'Incomplete（付款失败）',
	        'InProcess'=>'InProcess（付款中）',
	        'Invoiced'=>'Invoiced（等待买家付款）',
	        'Proposed'=>'Proposed（等待卖家接受）',
	        'Shipped'=>'Shipped（已完成）',
	    ],
	];
	
	public static $BonanzaStateToEagleStatusMapping = [
	    'Complete'=>[
	        'Active'=>200,
	        'Cancelled'=>600,
	        'Completed'=>200,
	        'Incomplete'=>100,
	        'Inprocess'=>100,
	        'Invoiced'=>100,
	        'Proposed'=>50,
	        'Shipped'=>500,
	    ],
	        'Incomplete'=>[
	            'Active'=>100,
	            'Cancelled'=>600,
	            'Completed'=>100,
	            'Incomplete'=>100,
	            'Inprocess'=>100,
	            'Invoiced'=>100,
	            'Proposed'=>50,
	            'Shipped'=>100,
	        ],
	            'InProcess'=>[
	            'Active'=>100,
	            'Cancelled'=>600,
	            'Completed'=>100,
	            'Incomplete'=>100,
	            'Inprocess'=>100,
	            'Invoiced'=>100,
	            'Proposed'=>100,
	            'Shipped'=>100,
	        ],
	        'Invoiced'=>[
	        'Active'=>100,
	        'Cancelled'=>600,
	        'Completed'=>100,
	        'Incomplete'=>100,
	        'Inprocess'=>100,
	        'Invoiced'=>100,
	        'Proposed'=>100,
	        'Shipped'=>100,
	    ],
	    'Pending'=>[
	        'Active'=>50,//待接受
	        'Cancelled'=>50,
	        'Completed'=>50,
	        'Incomplete'=>50,
	        'Inprocess'=>50,
	        'Invoiced'=>50,
	        'Proposed'=>50,
	        'Shipped'=>50,
	    ],
	];
	
	public static function getBonanzaOrderShippingCode(){
		return self::$ShippingCode;
	}
	
	public static function test(){
		try {
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));//600 m is test ,real value is 30
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_bonanza_user set last_order_success_retrieve_time='0000-00-00 00:00:00',last_order_retrieve_time='0000-00-00 00:00:00'
										where last_order_success_retrieve_time is null or last_order_retrieve_time is null"  );
			$affectRows = $command->execute();
				
			$SAASCDISCOUNTUSERLIST = SaasBonanzaUser::find()->where("is_active='1' ")->orderBy("last_order_success_retrieve_time asc")->all();
				
			//retrieve orders  by  each wish account
			foreach($SAASCDISCOUNTUSERLIST as $bonanzaAccount ){
				$uid = $bonanzaAccount['uid'];
		
				echo "<br>YS1 start to fetch for unfuilled uid=$uid ... ";
				if (empty($uid)){
				//异常情况
					$message = "site id :".$bonanzaAccount['site_id']." uid:0";
					echo $message;
					return false;
				}
 
		
				$updateTime =  TimeUtil::getNow();
				//update this wish account as last order retrieve time
				$bonanzaAccount->last_order_retrieve_time = $updateTime;
		
				$dateSince = date("Y-m-d\TH:i:s" ,strtotime($updateTime)-3600*24);// 1day前
					
				//start to get unfulfilled orders
				echo "<br>".TimeUtil::getNow()." start to get $uid unfufilled order for ".$bonanzaAccount['store_name']." since $dateSince \n"; //ystest
				$getOrderCount = 0;
				$sinceTimeUTC = date("Y-m-d\TH:i:s" ,strtotime($dateSince)-3600*8);//UTC time is -8 hours

				$orders = self::_getAllUnfulfilledOrdersSince($bonanzaAccount['token'] , $sinceTimeUTC );
					
				//fail to connect proxy
				if (empty($orders['success'])){
				echo "<br>fail to connect proxy  :".$orders['message'];
				$bonanzaAccount->save();
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
	 * @author		lwj		2016/05/16		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getBonanzaOmsNav($key_word){
	    $order_nav_list = [
	        '同步订单'=>'/order/bonanza-order/order-sync-info' ,
	        '待接受'=>'/order/bonanza-order/list?order_status=50',
	        '已付款'=>'/order/bonanza-order/list?order_status=200&pay_order_type=pending' ,
	        '发货中'=>'/order/bonanza-order/list?order_status=300' ,
	        '已完成'=>'/order/bonanza-order/list?order_status=500' ,
	    ];
	
	    $order_nav_active_list = [
	        '同步订单'=>'' ,
	        '待接受'=>'50',
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
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
				
			$SAASCDISCOUNTUSERLIST = SaasBonanzaUser::find()->where("is_active='1' and initial_fetched_changed_order_since is null or initial_fetched_changed_order_since='0000-00-00 00:00:00' or last_order_success_retrieve_time='0000-00-00 00:00:00' ")->all();
				
			//retrieve orders  by  each bonanza account
			foreach($SAASCDISCOUNTUSERLIST as $bonanzaAccount ){
				$uid = $bonanzaAccount['uid'];
				//update token //liang 2015-07-14
// 				$bonanzaAccount = self::setBonanzaUserToken($bonanzaAccount);
				if(empty($bonanzaAccount->token)){
					//获取token出了问题
					echo "\n get token failed;";
					$bonanzaAccount->order_retrieve_message = '获取token出现问题，请等待一段时间，或联系客服';
					$bonanzaAccount->save(false);
					continue;
				}else{
				    BonanzaOrderInterface::setBonanzaToken($bonanzaAccount->token);
				}
				echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
				if (empty($uid)){
				//异常情况
					$message = "site id :".$bonanzaAccount['site_id']." uid:0";
					echo "\n ".$message;
					//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
					continue;
				}
		
 
		
				$updateTime = TimeUtil::getNow();
				//update this bonanza account as last order retrieve time
				$bonanzaAccount->last_order_retrieve_time = $updateTime;
		
				$sinceTimeUTC = date('Y-m-d H:i:s' ,strtotime($updateTime)-3600*24*30);//UTC time is -8 hours
				$sinceTimeUTC = substr($sinceTimeUTC,0,10);
				
				$dateSince = date('Y-m-d H:i:s' ,strtotime($updateTime)-3600*24*30);// test 8hours //1个月前
				
				//start to get unfulfilled orders
				echo "\n".$updateTime." start to get $uid unfufilled order for ".$bonanzaAccount['store_name']." since $dateSince \n"; //ystest
				
				$getOrderCount = 0;
				
				$recentGetOrderTime = $dateSince;
				do{
				    $create_orders = '';
				    $Modified_orders = '';
				    $orders = '';
					$startTime = $recentGetOrderTime;
					$recentGetOrderTime = date("Y-m-d H:i:s" ,strtotime($recentGetOrderTime)+3600*24*3);//get 3 day one time
					$endTime = $recentGetOrderTime;
					//需要两个api才能获取时间段内的所有订单
					$create_orders = BonanzaOrderInterface::GetNewSalesWithInTime($startTime,$endTime);
					if(!empty($create_orders['orders'])&&$bonanzaAccount->is_auto_accept==1){
					    foreach ($create_orders['orders'] as $createOrder){
					        if(isset(self::$BonanzaStateToEagleStatusMapping[$createOrder['checkoutStatus']['status']][$createOrder['orderStatus']])&&self::$BonanzaStateToEagleStatusMapping[$createOrder['checkoutStatus']['status']][$createOrder['orderStatus']] == 50){
					            $accept_result = BonanzaOrderInterface::acceptOrder($createOrder['orderID']);
					            if($accept_result['success']){
					                echo "\n".$createOrder['orderID']."accept success!";
					            }else{
					                echo "\n".$createOrder['orderID']."accept fail! ErrorMessage:".$accept_result['message'];
					            }
					        }
					    }
					    $create_orders = BonanzaOrderInterface::GetNewSalesWithInTime($startTime,$endTime);
					    $Modified_orders = BonanzaOrderInterface::GetModifiedSalesWithInTime($startTime,$endTime);
					}else{
					    $Modified_orders = BonanzaOrderInterface::GetModifiedSalesWithInTime($startTime,$endTime);
					}
					
					if (empty($create_orders['success'])){
						echo "\n GetNewSalesWithInTime fail to connect proxy  :".$create_orders['message'].",time:".date("Y-m-d H:i:s",time());
					    $bonanzaAccount->order_retrieve_message = " GetNewSalesWithInTime fail to connect proxy  :".$create_orders['message'].",time:".date("Y-m-d H:i:s",time());
						//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
						$bonanzaAccount->save();
						return array('success'=>false,'message'=>"uid:".$bonanzaAccount->uid." cronAutoFetchRecentOrderList GetNewSalesWithInTime Exception:".$create_orders['message']);
// 						break;
					}else{
					    $bonanzaAccount->order_retrieve_message = "";
					    $bonanzaAccount->save();
					}
					
					if (empty($Modified_orders['success'])){
					    echo "\n GetModifiedSales fail to connect proxy  :".$Modified_orders['message'].",time:".date("Y-m-d H:i:s",time());
					    //\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
				        $bonanzaAccount->order_retrieve_message = " GetModifiedSales fail to connect proxy  :".$Modified_orders['message'].",time:".date("Y-m-d H:i:s",time());
					    $bonanzaAccount->save();
					    return array('success'=>false,'message'=>"uid:".$bonanzaAccount->uid." cronAutoFetchRecentOrderList GetModifiedSalesWithInTime Exception:".$Modified_orders['message']);
// 					    break;
					}else{
					    $bonanzaAccount->order_retrieve_message = "";
					    $bonanzaAccount->save();
					}
					
					$orders  = self::merageBonanzaOrder($create_orders['orders'], $Modified_orders['orders']);
					/*
					 if(is_array($orders['proxyResponse']['xml'])) print_r($orders['proxyResponse']['xml']);
					else echo $orders['proxyResponse']['xml'];
					*/
					
					echo "\n".TimeUtil::getNow()." got results, start to insert oms \n"; //ystest
					//end of getting orders from bonanza server
					
					//accroding to api respone  , update the last retrieve order time and last retrieve order success time
					
                    if(!empty($orders))
                    {
                        $rtn=self::_InsertBonanzaOrder($orders, $bonanzaAccount);
                        echo "\n uid = $uid handled orders count ".count($orders)." for ".$bonanzaAccount['token'];
                        //\Yii::info(['bonanza',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$bonanzaAccount['token']],"edb\global");
                        	
                        if(!empty($rtn['success'])){
                            $bonanzaAccount->initial_fetched_changed_order_since = $endTime;
                            $bonanzaAccount->routine_fetched_changed_order_from = $endTime;
                            $bonanzaAccount->last_order_success_retrieve_time = $endTime;
                        }else{
                            $bonanzaAccount->initial_fetched_changed_order_since = $endTime;
                            $bonanzaAccount->routine_fetched_changed_order_from = $endTime;
                            $bonanzaAccount->order_retrieve_message = $rtn['message'];
                        }
                    
                    }else{
                        $bonanzaAccount->initial_fetched_changed_order_since = $endTime;
                        $bonanzaAccount->routine_fetched_changed_order_from = $endTime;
                        $bonanzaAccount->last_order_success_retrieve_time = $endTime;
                        $bonanzaAccount->order_retrieve_message = 'get non order';
                    }					
					
					if (!$bonanzaAccount->save(false)){
						echo "\n failure to save bonanza account info ,errors:";
						echo "\n uid:".$bonanzaAccount['uid']." error:". print_r($bonanzaAccount->getErrors(),true);
						//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"failure to save bonanza operation info ,uid:".$bonanzaAccount['uid']."error:". print_r($bonanzaAccount->getErrors(),true)],"edb\global");
						break;
					}else{
						echo "\n BonanzaAccount model save !";
					}
				}while ($recentGetOrderTime < $updateTime );
		
			}//end of each bonanza user account
		}
		catch (\Exception $e) {
			echo "\n cronAutoFetchNewAccountOrderList Exception:".$e->getMessage();
			//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
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
		$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='BNOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
		$record = $command->queryOne();
		if(!empty($record)){
			$run_times = json_decode($record['addinfo2'],true);
			if(!is_array($run_times))
				$run_times = [];
			$run_times['enter_times'] = empty($run_times['enter_times'])?1 : $run_times['enter_times']+1;
			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'   where app='BNOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
			$command = Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
		}else{
			$run_times = ['enter_times'=>1,'end_times'=>0];
			$sql = "INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('BNOMS','runtime-GetOrder','normal','','job_id=".$job_id."','".json_encode($run_times)."','".$the_day."','".date("Y-m-d H:i:s",time())."')";
			$command = Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
		}//set runtime log end
		if(empty($affect_record))
			echo "\n set runtime log falied, sql:".$sql;
		
		try {				
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));//600 m is test ,real value is 30
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_bonanza_user set last_order_success_retrieve_time='0000-00-00 00:00:00',last_order_retrieve_time='0000-00-00 00:00:00'  
									where last_order_success_retrieve_time is null or last_order_retrieve_time is null"  );
			$affectRows = $command->execute();
			
// 			$SAASCDISCOUNTUSERLIST = SaasBonanzaUser::find()->where("is_active='1' and initial_fetched_changed_order_since is not null 
// 					and last_order_success_retrieve_time<'$half_hours_ago'")->andwhere(" uid%3=$job_id ")->orderBy("last_order_success_retrieve_time asc")->all();
			$SAASCDISCOUNTUSERLIST = SaasBonanzaUser::find()->where("is_active='1' and initial_fetched_changed_order_since is not null
			    and last_order_success_retrieve_time<'$half_hours_ago'")->orderBy("last_order_success_retrieve_time asc")->all();
			//retrieve orders  by  each bonanza account 
			foreach($SAASCDISCOUNTUSERLIST as $bonanzaAccount ){
				$uid = $bonanzaAccount->uid;
				//update token //liang 2015-07-14
// 				$bonanzaAccount = self::setBonanzaUserToken($bonanzaAccount);
				if(empty($bonanzaAccount->token)){
					//获取token出了问题
					echo "\n get token failed;";
					$bonanzaAccount->order_retrieve_message = '获取token出现问题，请等待一段时间，或联系客服';
					$bonanzaAccount->save(false);
				}else{
				    BonanzaOrderInterface::setBonanzaToken($bonanzaAccount->token);
				}
// 				if( strtotime($bonanzaAccount->token_expired_date) < strtotime("-1 days") ){
// 					$bonanzaAccount->order_retrieve_message = 'token已过期，请检测绑定信息中的 账号，密码是否正确。';
// 					if (!$bonanzaAccount->save(false)){
// 						echo "\n failure to save bonanza account info ,error:";
// 						echo "\n uid:".$bonanzaAccount['uid']."error:". print_r($bonanzaAccount->getErrors(),true);
// 						//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"failure to save bonanza operation info ,uid:".$bonanzaAccount['uid']."error:". print_r($bonanzaAccount->getErrors(),true)],"edb\global");
// 					}else{
// 						echo "\n BonanzaAccount model save(token expired) !";
// 					}
// 					continue;
// 				}
				echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
				if (empty($uid)){
					//异常情况 
					$message = "site id :".$bonanzaAccount['site_id']." uid:0";
					echo "\n ".$message;
					//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");					
					continue;
				}
				
 
				$updateTime =  TimeUtil::getNow();
				$onwTimeUTC = date("Y-m-d H:i:s");// not UTC time, local time(UTC+8) can get more order
				$sinceTimeUTC = date("Y-m-d H:i:s" ,strtotime($bonanzaAccount->last_order_success_retrieve_time)-3600*24*2);
				
				
				//由于bug或其他原因导致账号长时间(超过5日)没有成功获取到订单的情况
				if( strtotime($bonanzaAccount->last_order_success_retrieve_time) < strtotime("-2 days")){
					$updateTime =  date("Y-m-d H:i:s" ,strtotime($bonanzaAccount->last_order_success_retrieve_time)+3600*24); //上次成功获取订单后一天
					$onwTimeUTC = date("Y-m-d H:i:s" ,strtotime($bonanzaAccount->last_order_success_retrieve_time)+3600*24);
					$sinceTimeUTC = date("Y-m-d H:i:s" ,strtotime($bonanzaAccount->last_order_success_retrieve_time)-3600*24*2);
				}
				
				$getOrderCount = 0;
				//update this bonanza account as last order retrieve time
				$bonanzaAccount->last_order_retrieve_time = $updateTime;	
							
				if (empty($bonanzaAccount->last_order_success_retrieve_time) or $bonanzaAccount->last_order_success_retrieve_time=='0000-00-00 00:00:00'){
					//如果还没有初始化完毕，就什么都不do
					echo "\n uid=$uid haven't initial_fetched !";
				}else{
					//start to get unfulfilled orders
					echo "\n".TimeUtil::getNow()." start to get $uid unfufilled order for ".$bonanzaAccount['store_name']." since $sinceTimeUTC \n"; //ystest
					
					//需要两个api才能获取时间段内的所有订单
					$create_orders = BonanzaOrderInterface::GetNewSalesWithInTime($sinceTimeUTC,$onwTimeUTC);
				    if(!empty($create_orders['orders'])&&$bonanzaAccount->is_auto_accept==1){
					    foreach ($create_orders['orders'] as $createOrder){
					        if(isset(self::$BonanzaStateToEagleStatusMapping[$createOrder['checkoutStatus']['status']][$createOrder['orderStatus']])&&self::$BonanzaStateToEagleStatusMapping[$createOrder['checkoutStatus']['status']][$createOrder['orderStatus']] == 50){
					            $accept_result = BonanzaOrderInterface::acceptOrder($createOrder['orderID']);
					            if($accept_result['success']){
					                echo "\n".$createOrder['orderID']."accept success!";
					            }else{
					                echo "\n".$createOrder['orderID']."accept fail! ErrorMessage:".$accept_result['message'];
					            }
					        }
					    }
					    $create_orders = BonanzaOrderInterface::GetNewSalesWithInTime($sinceTimeUTC,$onwTimeUTC);
					    $Modified_orders = BonanzaOrderInterface::GetModifiedSalesWithInTime($sinceTimeUTC,$onwTimeUTC);
					}else{
					    $Modified_orders = BonanzaOrderInterface::GetModifiedSalesWithInTime($sinceTimeUTC,$onwTimeUTC);
					}
					
					if (empty($create_orders['success'])){
						echo "\n GetNewSalesWithInTime fail to connect proxy  :".$create_orders['message'].",time:".date("Y-m-d H:i:s",time());
					    $bonanzaAccount->order_retrieve_message = " GetNewSalesWithInTime fail to connect proxy  :".$create_orders['message'].",time:".date("Y-m-d H:i:s",time());
// 						\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"GetNewSalesWithInTime fail to connect proxy  :".$create_orders['message']],"edb\global");
						$bonanzaAccount->save();
						return array('success'=>false,'message'=>"uid:".$bonanzaAccount->uid." cronAutoFetchRecentOrderList GetNewSalesWithInTime Exception:".$create_orders['message']);
// 						break;
					}else{
					    $bonanzaAccount->order_retrieve_message = "";
					    $bonanzaAccount->save();
					}
					
					if (empty($Modified_orders['success'])){
					    echo "\n GetModifiedSales fail to connect proxy  :".$Modified_orders['message'].",time:".date("Y-m-d H:i:s",time());
// 					    \Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"GetModifiedSalesWithInTime fail to connect proxy  :".$Modified_orders['message']],"edb\global");
				        $bonanzaAccount->order_retrieve_message = " GetModifiedSales fail to connect proxy  :".$Modified_orders['message'].",time:".date("Y-m-d H:i:s",time());
					    $bonanzaAccount->save();
					    return array('success'=>false,'message'=>"uid:".$bonanzaAccount->uid." cronAutoFetchRecentOrderList GetModifiedSalesWithInTime Exception:".$Modified_orders['message']);
// 					    break;
					}else{
					    $bonanzaAccount->order_retrieve_message = "";
					    $bonanzaAccount->save();
					}
					
					$orders  = self::merageBonanzaOrder($create_orders['orders'], $Modified_orders['orders']);
					/*
					 if(is_array($orders['proxyResponse']['xml'])) print_r($orders['proxyResponse']['xml']);
					else echo $orders['proxyResponse']['xml'];
					*/
					
					//echo "\n".TimeUtil::getNow()." got results, start to insert oms \n"; //ys test
					//accroding to api respone  , update the last retrieve order time and last retrieve order success time
// 					if(isset($orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'])){
// 						echo "\n".$orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
// 						$bonanzaAccount->order_retrieve_message = $orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
// 					}
// 					if (!empty ($orders['proxyResponse']['success'])){
// 						//print_r($orders); //liang test
// 						if(isset($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']))
// 							echo "\n isset ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
// 						//sync bonanza info to bonanza order table
// 						if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])){
// 							echo "\n !empty ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
// 							//print_r($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'][0]);
// 							$rtn = self::_InsertBonanzaOrder($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'] , $bonanzaAccount,$the_day);
// 							//\Yii::info(['bonanza',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$bonanzaAccount['token']],"edb\global");
// 							if($rtn['success']){
// 								//update last order success retrieve time of this bonanza account
// 								$bonanzaAccount->last_order_success_retrieve_time = $updateTime;
// 							}
// 						}else{
// 							echo "\n get none order";
// 							//update last order success retrieve time of this bonanza account
// 							$bonanzaAccount->last_order_success_retrieve_time = $updateTime;
// 						}//end of GetOrderListResult empty or not
							
// 					}else{
// 						//print_r($orders);
// 						if (!empty ($orders['proxyResponse']['message'])){
// 							echo "\n uid = $uid proxy error  :".$orders['proxyResponse']['message'].$bonanzaAccount['token'];
// 							//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  :".$orders['proxyResponse']['message'].$bonanzaAccount['token'] ],"edb\global");
// 						}else{
// 							echo "\n uid = $uid proxy error  : not any respone message".$bonanzaAccount['token'];
// 							//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  : not any respone message".$bonanzaAccount['token']],"edb\global");
// 						}
// 					}
					//end of getting orders from bonanza server
					
// 					if (!empty ($orders['proxyResponse']['message'])){
// 						$bonanzaAccount->order_retrieve_message = $orders['proxyResponse']['message'];
// 					}else
// 						$bonanzaAccount->order_retrieve_message = '';//to clear the error msg if last attemption got issue
                    if(!empty($orders))
                    {
                        $rtn=self::_InsertBonanzaOrder($orders, $bonanzaAccount);
                        echo "\n uid = $uid handled orders count ".count($orders)." for ".$bonanzaAccount['token'];
                        //\Yii::info(['bonanza',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$bonanzaAccount['token']],"edb\global");
                         
                        if(!empty($rtn['success'])){
                            $bonanzaAccount->last_order_success_retrieve_time = $updateTime;
                        }else{
                            $bonanzaAccount->order_retrieve_message = $rtn['message'];
                        }
                    
                    }else{
                        echo "\n get none order";
                        $bonanzaAccount->last_order_success_retrieve_time = $updateTime;
                    }					

					if (!$bonanzaAccount->save(false)){
						echo "\n failure to save bonanza account info ,error:";
						echo "\n uid:".$bonanzaAccount['uid']."error:". print_r($bonanzaAccount->getErrors(),true);
						//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"failure to save bonanza operation info ,uid:".$bonanzaAccount['uid']."error:". print_r($bonanzaAccount->getErrors(),true)],"edb\global");
					}else{
						echo "\n BonanzaAccount model save !";
					}	
				}
			}//end of each bonanza user account
			return array('success'=>true,'message'=>''); 
		} catch (\Exception $e) {
			echo "\n cronAutoFetchRecentOrderList Exception2:".$e->getMessage();
			return array('success'=>false,'message'=>"cronAutoFetchRecentOrderList Exception:".$e->getMessage());
			//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
			
	}//end of cronAutoFetchUnFulfilledOrderList
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * bonanza api 获取的订单数组 保存到订单模块中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $orders				bonanza api 返回的结果
	 * @param $bonanzaAccount		bonanza user model 
	 * @param $the_day				job启动当前日期
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 * @description			bonanza order  调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl	2014/12/09		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function _InsertBonanzaOrder($orders , $bonanzaAccount , $the_day=''){
		//echo "\n YS0.0 Start to insert order "; //ystest
		try {
			//删除v2表里面不存在的原始订单
			$command = Yii::$app->subdb->createCommand("delete  FROM `bonanza_order` WHERE `orderID` not in (select order_source_order_id from od_order_v2 where order_source='bonanza') ");
			$command->execute();
			
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
			$tempBonanzaOrderModel = new BonanzaOrder();
			$tempBonanzaOrderDetailModel = new BonanzaOrderDetail();
			$BonanzaOrderModelAttr = $tempBonanzaOrderModel->getAttributes();
			$BonanzaOrderDetailModelAttr = $tempBonanzaOrderDetailModel->getAttributes();
// 			print_r($BonanzaOrderModelAttr);
// 			print_r($BonanzaOrderDetailModelAttr);exit();
			$rtn['message'] ='';
			$rtn['success'] = true;
			
			//单订单判断
// 			if(isset($orders['OrderNumber'])){
// 				$o=$orders;
// 				$orders=[];
// 				$orders[]=$o;
// 			}
			//记录保存失败的订单号
			$insertErrOrder['srcTbale']=[];
			$insertErrOrder['omsTbale']=[];
			$insertErrOrder['uid']=$bonanzaAccount['uid'];
			$insertErrOrder['store_name']=$bonanzaAccount['store_name'];
			
			//同步商品信息原始数组
			$syncProdInfoData=[];
			
			foreach($orders as $anOrder){
				$OrderParms = array();
				$orderModel = BonanzaOrder::findOne($anOrder['orderID']); 
				$newCreateOrder = false;
				//echo "\n YS0.2-1   "; //ystest
				if (empty($orderModel)){
					//new order 
					$orderModel = new BonanzaOrder();
					$newCreateOrder = true;
				}else{// not change and order_v2 existing, then skip it
					//$existingOrder = OdOrder::find()->where(['order_source_order_id'=>$anOrder['OrderNumber']])->one();
					//if(!empty($existingOrder)){
// 						if (strtotime($anOrder['LastUpdatedDate']) == strtotime($orderModel->lastupdateddate) && strtotime($anOrder['ModifiedDate']) == strtotime($orderModel->modifieddate) ){
// 							continue;
// 						}
// 					//}
				}
		 
				//set order info 
				$orderInfo = array();
				$orderDetailInfo = array();
				//echo "\n YS0.2   "; //ystest
				foreach($anOrder as $key=>$value){
// 					$key = strtolower($key);
					if ($key=='checkoutStatus'){
						foreach($value as $subkey=>$subvalue){
						    $orderInfo[$key] = $subvalue;
						}//end of each BillingAddress Detail
						continue;
					}
					
					if($key == 'createdTime'||$key == 'paidTime'||$key == 'shippedTime'){
					    $all_time = strtotime($value);
					    $orderInfo[$key] = date("Y-m-d H:i:s",$all_time);
					    continue;
					}
					
					if ($key=='transactionArray'){
					    if(isset($value['transaction'])){
					        foreach($value['transaction'] as $subkey=>$subvalue){
					            if ($subkey == 'buyer'){
					                $orderInfo["email"] = $subvalue['email'];
					            }else{
					                $orderInfo[$subkey] = $subvalue;
					            }
					        }//end of each Customer Detail
					    }
						continue;
					}
				 
					if ($key=='shippingAddress'){
						foreach($value as $subkey=>$subvalue){
						    $orderInfo[$subkey] = $subvalue;
						}//end of each ShippingAddress Detail
						continue;
					}
					
					if ($key=='shippingDetails'){
					    foreach($value as $subkey=>$subvalue){
							if (array_key_exists($subkey, $BonanzaOrderModelAttr)){
								if(is_array($subvalue)){
									if(!empty($subvalue)) $subvalue=json_encode($subvalue);
									else $subvalue='';
								}
								$orderInfo[$subkey] = $subvalue;
							}
					        
					    }//end of each ShippingAddress Detail
					    continue;
					}
					
					if (array_key_exists($key, $BonanzaOrderModelAttr)){
						if(is_array($value)){
							if(!empty($value)) $value=json_encode($value);
							else $value='';
						}
						$orderInfo[$key] = $value;
						continue;
					}
					
					if ($key=='itemArray'){//item
					    if(!empty($value)){
					        foreach ($value as $v_key=>$v){
					            if(!empty($v['item'])){
					                foreach ($v['item'] as $subkey=>$subvalue){
					                    if (array_key_exists($subkey, $BonanzaOrderDetailModelAttr)){
// 					                        if($subkey=='name' && is_array($subvalue)) continue;//无效prod name
					                        if(is_array($subvalue)){
					                            if(!empty($subvalue)) $subvalue=json_encode($subvalue);
					                            else $subvalue='';
					                        }
					                        $orderDetailInfo[$v_key][$subkey] = $subvalue;
					                    }
					                }
					            }
					            
					        }
					    }//end of each Product Detail
					    continue;
					}
				}
				
				$addinfo = [];
				
				if (!empty($orderInfo)){
					$orderModel->setAttributes($orderInfo);
					echo " \n YS1 try to inset bonanza order for ".$orderModel->orderID;
					if (  $orderModel->save(false) ){
						echo "\n save bonanza order success!";
						if($newCreateOrder)
							$src_insert_success ++;
						else
							$src_update_success ++;
					}else{
						echo "\n failure to save bonanza order,uid:".$bonanzaAccount['uid']."error:".print_r($orderModel->getErrors(),true);
						$rtn['message'].=(empty($orderModel->orderID)?'':$orderModel->orderID)."原始订单保存失败,";
						$rtn['success']=false;
						$insertErrOrder['srcTbale'][]=empty($orderModel->orderID)?'':$orderModel->orderID;
						if($newCreateOrder){
							$src_insert_failed ++;
							$src_insert_failed_happend_site = $bonanzaAccount['site_id'];
							$err_type['1']['times'] +=1;
							$err_type['1']['site_id']=$bonanzaAccount['site_id'];
							$err_type['1']['last_msg']="failure to insert bonanza order,site_id:".$bonanzaAccount['site_id']."error:".print_r($orderModel->getErrors(),true);
						}
						else{
							$src_update_failed ++;
							$src_update_failed_happend_site =  $bonanzaAccount['site_id'];
							$err_type['4']['times'] +=1;
							$err_type['4']['site_id']=$bonanzaAccount['site_id'];
							$err_type['4']['last_msg']="failure to update bonanza order,site_id:".$bonanzaAccount['site_id']."error:".print_r($orderModel->getErrors(),true);
						}
						continue;
					}
				}else{
					echo 'failure to save bonanza order,orderInfo lost!';
					$rtn['message'].="原始订单信息缺失";
					$rtn['success']=false;
					if($newCreateOrder){
						$src_insert_failed++;
						$src_insert_failed_happend_site=$bonanzaAccount['site_id'];
						$err_type['1']['times'] +=1;
						$err_type['1']['site_id']=$bonanzaAccount['site_id'];
						$err_type['1']['last_msg']='failure to insert bonanza order,orderInfo lost!';
					}
					else{
						$src_update_failed ++;
						$src_update_failed_happend_site =  $bonanzaAccount['site_id'];
						$err_type['4']['times'] +=1;
						$err_type['4']['site_id']=$bonanzaAccount['site_id'];
						$err_type['4']['last_msg']='failure to update bonanza order,orderInfo lost!';
					}
					
					continue;
				}
			 	//echo "\n YS0.4   "; //ystest
				//save order detail 
				if (!empty($orderDetailInfo)){
					
					if ($newCreateOrder){
						foreach ($orderDetailInfo as $aDelail){
							$aDelail['orderID'] = $anOrder['orderID'];
							$orderDetails = new BonanzaOrderDetail();
							$orderDetails->setAttributes ($aDelail);
							if (!$orderDetails->save(false)){
								echo "\n failure to save bonanza order details ,uid:".$bonanzaAccount['uid']."error:". print_r($orderDetails->getErrors(),true);
								$rtn['message'].=$anOrder['orderID']."保存原始订单商品失败;";
								$rtn['success']=false;
								$insertErrOrder['srcTbale'][]=empty($anOrder['orderID'])?'':$anOrder['orderID'];
								
								$src_detail_insert_failed ++;
								$src_detail_insert_failed_happend_site= $bonanzaAccount['site_id'];
								$err_type['2']['times'] +=1;
								$err_type['2']['site_id']=$bonanzaAccount['site_id'];
								$err_type['2']['last_msg']='failure to insert bonanza order detail ,insert to db failed!';
								
								continue;
							}else{
								echo "\n save bonanza order detail success!";
								$src_detail_insert_success ++;
								$syncProdInfoData[]=$orderDetailInfo;
							}
						}
					}else{
						foreach ($orderDetailInfo as $aDelail){
							$orderDetails = BonanzaOrderDetail::find()->where(['orderID'=>$anOrder['orderID'],'itemID'=>$aDelail['itemID'] ])->one();
							if (empty($orderDetails)){
								$orderDetails = new BonanzaOrderDetail();
							}
							$orderDetails->setAttributes ($aDelail);
							if (!$orderDetails->save(false)){
								echo "\n failure to save bonanza order details ,uid:".$bonanzaAccount['uid']."error:". print_r($orderDetails->getErrors());
								$rtn['message'].=$anOrder['orderID']."更新原始订单商品失败;";
								$rtn['success']=false;
								$insertErrOrder['srcTbale'][]=empty($anOrder['orderID'])?'':$anOrder['orderID'];
								
								$src_detail_update_failed++;
								$src_detail_update_failed_happend_site=$bonanzaAccount['site_id'];
								$err_type['5']['times'] +=1;
								$err_type['5']['site_id']=$bonanzaAccount['site_id'];
								$err_type['5']['last_msg']='failure to update bonanza order detail ,update to db failed!';
								
								continue;
							}else{
								echo "\n save bonanza order detail success!";
								$src_detail_update_success++;
								$syncProdInfoData[]=$orderDetailInfo;
							}
						}
					}
				}else{
					echo 'failure to save bonanza order detail, orderDetailInfo lost!';
					$rtn['message'].="原始订单商品信息丢失;";
					$rtn['success']=false;
					$insertErrOrder['srcTbale'][]=empty($orderModel->orderID)?'':$orderModel->orderID;
					if ($newCreateOrder){
						$src_detail_insert_failed ++;
						$src_detail_insert_failed_happend_site = $bonanzaAccount['site_id'];
						$err_type['2']['times'] +=1;
						$err_type['2']['site_id']=$bonanzaAccount['site_id'];
						$err_type['2']['last_msg']='failure to insert bonanza order detail, detail info lost!';
					}
					else{
						$src_detail_update_failed++;
						$src_detail_update_failed_happend_site = $bonanzaAccount['site_id'];
						$err_type['5']['times'] +=1;
						$err_type['5']['site_id']=$bonanzaAccount['site_id'];
						$err_type['5']['last_msg']='failure to update bonanza order detail, detail info lost!';
					}
					continue;
				}

				//format Order Data
				$anOrder['selleruserid'] = $bonanzaAccount['store_name'];
				$anOrder['saas_platform_user_id'] = $bonanzaAccount['site_id'];
				
				//format import order data
				//echo "\n YS0.5 start to formated order data";
				$formated_order_data = self::_formatImportOrderData( $anOrder , $bonanzaAccount, $getEmail=true);
				$formated_order_detail_data = self::_formatImportOrderItemsData( $anOrder['itemArray'],$anOrder['orderID']);
			
				if(empty($formated_order_data['shipping_cost']))
					$formated_order_data['shipping_cost']=0;
				if(isset($formated_order_data['total_amount']) && isset($formated_order_data['shipping_cost']) ){
					$total_amount = $formated_order_data['total_amount'] - $formated_order_data['shipping_cost'];
				}else 
					$total_amount=$formated_order_detail_data['total_amount'];
				
				$formated_order_data['subtotal'] = $total_amount; //产品总价格
				
				//if(empty($formated_order_data['shipping_cost']))
				//	$formated_order_data['shipping_cost'] = $formated_order_detail_data['delivery_amonut']; //若订单获得的运费empty,则取由items计算出的运费
				
				$formated_order_data['grand_total'] = $formated_order_data['subtotal'] + $formated_order_data['shipping_cost'] - $formated_order_data['discount_amount'] ;//合计金额
				
				//print_r($formated_order_data);
				//print_r($formated_order_detail_data);
				//echo "\n YS0.5 end of formated order data";
				// call eagle order api to sync order information
				//$importOrderResult = OrderHelper::importPlatformOrder($OrderParms);
				//echo "\n YS0.7 start to _saveBonanzaOrderToEagle";
				//	Step 2: save this order to eagle OMS 2.0, using the same record ID	
				$formated_order_data['items']=$formated_order_detail_data['items'];
				$importOrderResult=self::_saveBonanzaOrderToEagle($formated_order_data,$eagleOrderRecordId=-1);
				if (!isset($importOrderResult['success']) or $importOrderResult['success']==1){
					echo "\n failure insert an order to oms 2,result:";
					print_r($importOrderResult);
					$insertErrOrder['omsTbale'][]=empty($anOrder['orderID'])?'':$anOrder['orderID'];
					$rtn['message'].=empty($anOrder['orderID'])?'':$anOrder['orderID']."订单保存到oms失败;";
					$addinfo['oms_auto_inserted'] = 0;
					$addinfo['errors'] = '订单保存到oms失败';
					$rtn['success']=false;
					
					if($newCreateOrder){
						$oms_insert_failed ++;
						$oms_insert_failed_happend_site =  $bonanzaAccount['site_id'];
						$err_type['3']['times'] +=1;
						$err_type['3']['site_id']=$bonanzaAccount['site_id'];
						$err_type['3']['last_msg']='failure to insert bonanza order to oms!';
					}else{
						$oms_update_failed ++;
						$oms_update_failed_happend_site =  $bonanzaAccount['site_id'];
						$err_type['3']['times'] +=1;
						$err_type['3']['site_id']=$bonanzaAccount['site_id'];
						$err_type['3']['last_msg']='failure to update bonanza order to oms!';
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
						$oms_insert_failed_happend_site =  $bonanzaAccount['site_id'];
					}
					if($newCreateOrder && $order==null && isset($importOrderResult['message'])){
						if( stripos($importOrderResult['message'], 'E009')===false){
							$oms_insert_failed ++;
							$oms_insert_failed_happend_site =  $bonanzaAccount['site_id'];
						}else{
							$oms_insert_success ++;//合并过的订单
						}
					}
					if(!$newCreateOrder){
						if(empty($order)){
							$oms_insert_failed ++;
							$oms_insert_failed_happend_site =  $bonanzaAccount['site_id'];
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
			if(!empty($syncProdInfoData))
				self::saveOrderDetailToOfferListIfIsNew($syncProdInfoData, $bonanzaAccount);
			
			if(!empty($insertErrOrder['srcTbale']) || !empty($insertErrOrder['omsTbale'])){
				\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',date('Y-m-d H:i:s')." CdInsertOrderError:".json_encode($insertErrOrder) ],"edb\global");
			}
			
// 			echo "\n Start to calculateSalesWithin15Days: ";
// 			$nowTimeStr = TimeUtil::getNow();
// 			self::calculateSalesWithin15Days($bonanzaAccount['store_name'], $bonanzaAccount['username'],$nowTimeStr);
			
			//set dash-broad log
			if(empty($the_day))
				$the_day=date("Y-m-d",time());
			if(empty($src_insert_success) && empty($src_detail_insert_success) && empty($src_detail_insert_failed) && 
			   empty($src_update_success) && empty($src_update_failed) && empty($src_detail_update_success) && 
			   empty($src_detail_update_failed) ) {
				//无insert无update,则不记录
				echo "\n no insert and on update! \n";
			}else{
				$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='BNOMS' and info_type='orders' and `the_day`='".$the_day."' "  );
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

					$command = Yii::$app->db_queue->createCommand("update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`addinfo`='".json_encode($order_count)."',`addinfo2`='".json_encode($failed_happend_site)."'  where app='BNOMS' and info_type='orders' and `the_day`='".$the_day."' "  );
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
					
					$command = Yii::$app->db_queue->createCommand("INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('BNOMS','orders','normal','','".json_encode($order_count)."','".json_encode($failed_happend_site)."','".$the_day."','".date("Y-m-d H:i:s",time())."')"  );
					$affect_record = $command->execute();
				}//set runtime log end
				
				$need_mark_log = false;
				foreach ($err_type as $code=>$v){
					if($v['times']!==0)
						$need_mark_log=true;
				}
				if($need_mark_log){
					$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='BNOMS' and info_type='err_type' and `the_day`='".$the_day."' "  );
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
						$command = Yii::$app->db_queue->createCommand("update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`addinfo`='".json_encode($err_type_info)."',`addinfo2`=' '  where app='BNOMS' and info_type='err_type' and `the_day`='".$the_day."' "  );
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
						$command = Yii::$app->db_queue->createCommand("INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('BNOMS','err_type','error','','".json_encode($err_type_info)."',' ','".$the_day."','".date("Y-m-d H:i:s",time())."')"  );
						$affect_record = $command->execute();
					}
				}
			}
			//记录用户订单数
			if(!empty($src_insert_success)){
				$uid = $bonanzaAccount['uid'];
				$sellerid=$bonanzaAccount['store_name'];
				$classification = "BonanzaOms_TempData";
				
				//$temp_count = \Yii::$app->redis->hget($classification,"user_$uid".".".$the_day);
				//$seller_temp_count = \Yii::$app->redis->hget($classification,"user_$uid".".".$sellerid.".".$the_day);
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
				
				//$set_redis = \Yii::$app->redis->hset($classification,"user_$uid".".".$the_day,$temp_count);
				//$set_seller_redis = \Yii::$app->redis->hset($classification,"user_$uid".".".$sellerid.".".$the_day,$seller_temp_count);
				$set_redis = RedisHelper::RedisSet($classification,"user_$uid".".".$the_day,$temp_count);
				$set_seller_redis = RedisHelper::RedisSet($classification,"user_$uid".".".$sellerid.".".$the_day,$seller_temp_count);
				echo "\n set redis return : $set_redis;$set_seller_redis";
			}
			//set dash-broad log end
			
			return $rtn;
		} catch (\Exception $e) {
			//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"insert bonanza order :".$e->getMessage() ],"edb\global");
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
			echo "\n error: ".$rtn['message'];
			return $rtn;
		}				
	}//end of _InsertBonanzaOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * bonanza api 获取的数组 赋值到  eagle order 接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $bonanzaOrderData		bonanza 数据
	 * @param $bonanzaAccount			账号 数据
	 * @param $getEmail					是否需要获取买家email
	 +---------------------------------------------------------------------------------------------
	 * @return		$importOrderData	eagle order 接口 的数组
	 * 									调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015-07-07	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderData($bonanzaOrderData,$bonanzaAccount,$getEmail=false){
		$importOrderData = array();

		//$importOrderData['order_id'] = $bonanzaOrderData['OrderNumber'];
		if (!empty($bonanzaOrderData['checkoutStatus']['status'])&&!empty($bonanzaOrderData['orderStatus'])){
			/*
			 * 	bonanza order state 
			 *  State 'WaitingForShipmentAcceptation' means that the order is waiting to ship.
			 *  State 'Shipped' means that the order has been marked as shipped by you.
			 *  State 'CancelledByCustomer' means that the order has been refunded by customer and should not be fulfilled.
			 *  State 'PaymentInProgress' means that the order is waiting customer to pay
			 *  
		R	 *  eagle order status
			 *  100（未付款）、200（已付款）、300（发货处理中）、400（已发货）、500（已完成）、600（已取消）、50（待接受/拒绝）
			 * */
// 			$BonanzaStateToEagleStatusMapping = array();
// 			$BonanzaStateToEagleStatusMapping = [
// 			    'Complete'=>[
// 			        'Active'=>200,
// 			        'Cancelled'=>600,
// 			        'Completed'=>300,
// 			        'Incomplete'=>100,
// 			        'Inprocess'=>100,
// 			        'Invoiced'=>100,
// 			        'Proposed'=>50,
// 			        'Shipped'=>500,
// 			    ],
// 			    'Incomplete'=>[
// 			        'Active'=>100,
// 			        'Cancelled'=>600,
// 			        'Completed'=>100,
// 			        'Incomplete'=>100,
// 			        'Inprocess'=>100,
// 			        'Invoiced'=>100,
// 			        'Proposed'=>50,
// 			        'Shipped'=>100,
// 			    ],
// 			    'InProcess'=>[
// 			        'Active'=>100,
// 			        'Cancelled'=>600,
// 			        'Completed'=>100,
// 			        'Incomplete'=>100,
// 			        'Inprocess'=>100,
// 			        'Invoiced'=>100,
// 			        'Proposed'=>100,
// 			        'Shipped'=>100,
// 			    ],
// 			    'Invoiced'=>[
// 			        'Active'=>100,
// 			        'Cancelled'=>600,
// 			        'Completed'=>100,
// 			        'Incomplete'=>100,
// 			        'Inprocess'=>100,
// 			        'Invoiced'=>100,
// 			        'Proposed'=>100,
// 			        'Shipped'=>100,
// 			    ],
// 			    'Pending'=>[
// 			        'Active'=>50,
// 			        'Cancelled'=>50,
// 			        'Completed'=>50,
// 			        'Incomplete'=>50,
// 			        'Inprocess'=>50,
// 			        'Invoiced'=>50,
// 			        'Proposed'=>50,
// 			        'Shipped'=>50,
// 			    ],
// 			];
			
			$importOrderData['order_status'] = isset(self::$BonanzaStateToEagleStatusMapping[$bonanzaOrderData['checkoutStatus']['status']][$bonanzaOrderData['orderStatus']])?self::$BonanzaStateToEagleStatusMapping[$bonanzaOrderData['checkoutStatus']['status']][$bonanzaOrderData['orderStatus']]:50;
			$importOrderData['order_source_status'] = $bonanzaOrderData['checkoutStatus']['status'].','.$bonanzaOrderData['orderStatus'];
		}

		if (isset($importOrderData['order_status'] )){
			//发货状态：1.已发货；0.未发货 #非已发货状态暂时定义为未发
			$importOrderData['shipping_status'] = (($importOrderData['order_status'] == 500)?1:0); 
		}

		$importOrderData['pay_status'] = ($importOrderData['order_status']<200)?0:1;
		
		$importOrderData['order_source'] = 'bonanza';//订单来源
		
		$importOrderData['order_type'] = '';//订单类型
// 		if($bonanzaOrderData['IsCLogistiqueOrder']!=='false')
// 			$importOrderData['order_type'] = 'FBC';
		
// 		if($importOrderData['order_type']=='FBC'){//如果是FBC订单，则认为是已发货。
// 			$importOrderData['order_status']=500;
// 			$importOrderData['shipping_status']=1;
// 		}
		
// 		if(isset($bonanzaOrderData['ShippingCode']) && is_string($bonanzaOrderData['ShippingCode']))
// 			$importOrderData['order_source_shipping_method'] = $bonanzaOrderData['ShippingCode'];
		
		if (isset($bonanzaOrderData['orderID'])){
			$importOrderData['order_source_order_id'] = $bonanzaOrderData['orderID']; //订单来源的订单id
		}

		$importOrderData['order_source_site_id'] = 'US'; //订单来源平台下的站点,Bonanza暂无分站点,只有法国站
		
		
		if (isset($bonanzaOrderData['selleruserid']))
			$importOrderData['selleruserid'] = $bonanzaOrderData['selleruserid']; //订单来源平台卖家用户名(下单时候的用户名)

		if (isset($bonanzaOrderData['saas_platform_user_id']))
			$importOrderData['saas_platform_user_id'] = $bonanzaOrderData['saas_platform_user_id']; //订单来源平台卖家用户名(下单时候的用户名)
		//$Civility['MR'] = "M.";
		//$Civility['MRS'] = "Mme";
		//$Civility['MISS'] = "Mlle";
		$buyer_name='';
		if (isset($bonanzaOrderData['buyerUserName'])){
			//if(isset($bonanzaOrderData['Customer']['Civility']) && is_string($bonanzaOrderData['Customer']['Civility']))
				//$buyer_name.=$Civility[$bonanzaOrderData['Customer']['Civility']]." ";
				$buyer_name = $bonanzaOrderData['buyerUserName'];
// 			if(isset($bonanzaOrderData['Customer']['FirstName']) && is_string($bonanzaOrderData['Customer']['FirstName']))
// 				$buyer_name.=$bonanzaOrderData['Customer']['FirstName']." ";
// 			if(isset($bonanzaOrderData['Customer']['LastName']) && is_string($bonanzaOrderData['Customer']['LastName']))
// 				$buyer_name.=$bonanzaOrderData['Customer']['LastName'];	
		}
		$importOrderData['source_buyer_user_id'] = $buyer_name; //买家名称
		
		if (isset($bonanzaOrderData['createdTime']))
			$importOrderData['order_source_create_time'] = strtotime($bonanzaOrderData['createdTime']); //订单在来源平台的下单时间

// 		if (isset($bonanzaOrderData['ShippingAddress'])){
// 			//echo "\n src ShippingAddress data:";
// 			//var_dump($bonanzaOrderData['ShippingAddress']);//liang test,屏蔽后部分订单地址信息转到eagle2订单格式是可能丢失(原因不明)
// 			if(isset($bonanzaOrderData['ShippingAddress']['Civility']) && is_string($bonanzaOrderData['ShippingAddress']['Civility']))
// 				$shippingConsignee.=$bonanzaOrderData['ShippingAddress']['Civility']." ";
// 			if(isset($bonanzaOrderData['ShippingAddress']['FirstName']) && is_string($bonanzaOrderData['ShippingAddress']['FirstName']))
// 				$shippingConsignee.=$bonanzaOrderData['ShippingAddress']['FirstName']." ";
// 			if(isset($bonanzaOrderData['ShippingAddress']['LastName']) && is_string($bonanzaOrderData['ShippingAddress']['LastName']))
// 				$shippingConsignee.=$bonanzaOrderData['ShippingAddress']['LastName']." ";
// 			if(isset($bonanzaOrderData['ShippingAddress']['Instructions']) && is_string($bonanzaOrderData['ShippingAddress']['Instructions']))
// 				$shippingConsignee.=$bonanzaOrderData['ShippingAddress']['Instructions'];
// 		}
		$shippingConsignee = '';
		if(isset($bonanzaOrderData['shippingAddress']['name'])){
		    $shippingConsignee = $bonanzaOrderData['shippingAddress']['name'];
		}
		$importOrderData['consignee'] = $shippingConsignee; //收货人

		if (isset($bonanzaOrderData['shippingAddress']['postalCode']) && is_string($bonanzaOrderData['shippingAddress']['postalCode']))
			$importOrderData['consignee_postal_code'] = $bonanzaOrderData['shippingAddress']['postalCode']; //收货人邮编
		
// 		if (isset($bonanzaOrderData['Customer']['Phone']) && is_string($bonanzaOrderData['Customer']['Phone']))
// 			$importOrderData['consignee_phone'] =$bonanzaOrderData['Customer']['Phone']; //收货人电话
		
// 		if (isset($bonanzaOrderData['Customer']['MobilePhone']) && is_string($bonanzaOrderData['Customer']['MobilePhone']))
// 			$importOrderData['consignee_mobile'] =$bonanzaOrderData['Customer']['MobilePhone']; //收货移动电话
        
		$importOrderData['consignee_phone'] = 'N/A';
		$importOrderData['consignee_mobile'] = 'N/A';
		
		if (isset($bonanzaOrderData['transactionArray']['transaction']['buyer']['email']) && is_string($bonanzaOrderData['transactionArray']['transaction']['buyer']['email']))
			$importOrderData['consignee_email'] = $bonanzaOrderData['transactionArray']['transaction']['buyer']['email']; //收货人Email
		$getEmail_retry = 0;
		while (empty($importOrderData['consignee_email']) && $getEmail && $getEmail_retry<2){
			$importOrderData['consignee_email'] = BonanzaOrderInterface::getEmailByOrderID($bonanzaAccount, $bonanzaOrderData['OrderNumber']);
			$getEmail_retry++;
		}
		if(empty($importOrderData['consignee_email']) || !is_string($importOrderData['consignee_email']))
			$importOrderData['consignee_email'] = 'N/A';
		
// 		if (isset($bonanzaOrderData['ShippingAddress']['CompanyName']) && is_string($bonanzaOrderData['ShippingAddress']['CompanyName'])){}
// 			$importOrderData['consignee_company'] =$bonanzaOrderData['ShippingAddress']['CompanyName']; //收货人公司
		$importOrderData['consignee_company'] = 'N/A';
		
		if(!empty($bonanzaOrderData['shippingAddress']['country']) && is_string($bonanzaOrderData['shippingAddress']['country'])){
		    $importOrderData['consignee_country_code'] = $bonanzaOrderData['shippingAddress']['country'];
		}else{
		    $importOrderData['consignee_country_code'] = 'US'; //收货人国家代码
		}
		
		if (!empty($bonanzaOrderData['shippingAddress']['countryName'])){
			$importOrderData['consignee_country'] = $bonanzaOrderData['shippingAddress']['countryName']; //收货人国家名
			
		}else if(!empty($bonanzaOrderData['shippingAddress']['country'])){
			$importOrderData['consignee_country'] = $bonanzaOrderData['shippingAddress']['country']; //收货人国家名
		}else{
		    $importOrderData['consignee_country'] = 'US'; 
		}
		
		
		if (isset($bonanzaOrderData['shippingAddress']['cityName']) && is_string($bonanzaOrderData['shippingAddress']['cityName']))
			$importOrderData['consignee_city'] = $bonanzaOrderData['shippingAddress']['cityName']; //收货人城市
		
		if (isset($bonanzaOrderData['shippingAddress']['stateOrProvince']) && is_string($bonanzaOrderData['shippingAddress']['stateOrProvince']))
			$importOrderData['consignee_district'] = $bonanzaOrderData['shippingAddress']['stateOrProvince']; //收货人地区
		
		$importOrderData['consignee_address_line1']='';
		if (isset($bonanzaOrderData['shippingAddress']['street1']) && is_string($bonanzaOrderData['shippingAddress']['street1']))
			$importOrderData['consignee_address_line1'] = $bonanzaOrderData['shippingAddress']['street1']; //收货人地址1
		//Apartment
// 		if (isset($bonanzaOrderData['ShippingAddress']['ApartmentNumber']) and is_string($bonanzaOrderData['ShippingAddress']['ApartmentNumber']) ){
// 			if(stripos($bonanzaOrderData['ShippingAddress']['ApartmentNumber'],"apt")===false && stripos($bonanzaOrderData['ShippingAddress']['ApartmentNumber'],"app")===false && stripos($bonanzaOrderData['ShippingAddress']['ApartmentNumber'],"Apartment")===false)
// 				$Apartment = "Apt.".$bonanzaOrderData['ShippingAddress']['ApartmentNumber'];
// 			else
// 				$Apartment = $bonanzaOrderData['ShippingAddress']['ApartmentNumber'];
				
// 			if(empty($importOrderData['consignee_address_line1']))
// 				$importOrderData['consignee_address_line1'] = "Apt.".$bonanzaOrderData['ShippingAddress']['ApartmentNumber'];
// 			else
// 				$importOrderData['consignee_address_line1'] = $Apartment.";".$importOrderData['consignee_address_line1'];
// 		}
		//Building
// 		if(isset($bonanzaOrderData['ShippingAddress']['Building']) && is_string($bonanzaOrderData['ShippingAddress']['Building'])){
// 			if(stripos($bonanzaOrderData['ShippingAddress']['Building'],"bât")===false && stripos($bonanzaOrderData['ShippingAddress']['Building'],"BTMENT")===false && stripos($bonanzaOrderData['ShippingAddress']['Building'],"Bâtiment")===false)
// 				$Btment = "bât.".$bonanzaOrderData['ShippingAddress']['Building'];
// 			else
// 				$Btment = $bonanzaOrderData['ShippingAddress']['Building'];

// 			if(empty($importOrderData['consignee_address_line1']))
// 				$importOrderData['consignee_address_line1'] = $Btment;
// 			else
// 				$importOrderData['consignee_address_line1'] = $Btment.';'.$importOrderData['consignee_address_line1'];
// 		}
		
		//echo "\n fianl consignee_address_line1 is:".$importOrderData['consignee_address_line1'];//liang test
		if (isset($bonanzaOrderData['shippingAddress']['street2']))
			$importOrderData['consignee_address_line2'] = $bonanzaOrderData['shippingAddress']['street2']; //收货人地址2
		
		
		if (isset($bonanzaOrderData['paidTime'] ))
			$importOrderData['paid_time'] = strtotime($bonanzaOrderData['paidTime']); //订单付款时间

		if(!empty($bonanzaOrderData['amountPaid']))
			$importOrderData['total_amount'] = $bonanzaOrderData['amountPaid'];
			
// 		if(!empty($bonanzaOrderData['ValidatedTotalShippingCharges']))
// 			$importOrderData['shipping_cost'] = $bonanzaOrderData['ValidatedTotalShippingCharges'];
		
		if (isset($bonanzaOrderData['SiteCommissionValidatedAmount'] ))
			$importOrderData['commission_total'] = $bonanzaOrderData['SiteCommissionValidatedAmount']; //平台佣金
		else
			$importOrderData['commission_total'] = 0;
		
		//yzq 20151220, 因为外面的其他地方还是会读取这个field，还需要这个field应付外面的读取，否则会出错
		$importOrderData['discount_amount'] = $importOrderData['commission_total']; //
		
		//if (isset($importOrderData['subtotal']) && isset($importOrderData['shipping_cost']) && isset($importOrderData['discount_amount']))
		//	$importOrderData['grand_total'] = $importOrderData['subtotal'] + $importOrderData['shipping_cost'] - $importOrderData['discount_amount']; //合计金额(产品总价格 + 运费 - 折扣 = 合计金额)
		$importOrderData['currency'] = 'USD'; //货币

		return $importOrderData;
	}//end of _formatImportOrderData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * bonanza api 获取的item数组 赋值到  eagle order item接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access 		static
	 +---------------------------------------------------------------------------------------------
	 * @param 		$bonanzaOrderLine		bonanza 数据
	 +---------------------------------------------------------------------------------------------
	 * @return		$importOrderItems		eagle order item接口 的数组
	 * 										调用eagle order item接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015-07-07	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderItemsData($bonanzaOrderLine,$order_number){
		$importOrderItems = array();
		$total_amount=0;
		$delivery_amonut=0;
		$total_qty=0;
			foreach ($bonanzaOrderLine as $detail){
			    $orderline = $detail['item'];
				$row=array();
				$row['order_source_order_id'] = $order_number;
				
				$row['sku'] = '';
				if(isset($orderline['sku']))
					$row['sku'] = $orderline['sku'];
				if(isset($orderline['itemID'])){
				    $row['source_item_id'] = $orderline['itemID'];
				    $row['order_source_order_item_id'] = $orderline['itemID'];
				}
				
				if(is_string($orderline['title']) && $orderline['title']!==''){
					$row['product_name']=$orderline['title'];
				}
// 				else{
// 					$srcOffer = BonanzaOfferList::find()->where(['seller_product_id'=>$row['sku']])->one();
// 					if($srcOffer<>null)
// 						$row['product_name'] = $srcOffer->name;
// 				}
				if(empty($row['product_name'])) $row['product_name']=$row['sku'];
				
				$row['order_source_itemid'] = $orderline['itemID'];
				
				if(empty($orderline['price']))
					$row['price'] = $orderline['price'];
				if(isset($orderline['quantity'])){
				    $row['ordered_quantity'] = $orderline['quantity'];
				    $row['quantity']=$orderline['quantity'];
				}
				
				//$bonanzaOrderLine['RowId']=0;

// 				if($orderline['AcceptationState']=='ShippedBySeller'){
// 					$row['sent_quantity']=$orderline['Quantity'];
// 					$row['packed_quantity']=$orderline['Quantity'];
// 				}

				$row['shipping_price'] = 0;

				$row['photo_primary']='';//bonanza response none product photo info
				
				// $item['discount_amount'] =  $row['OrderLineList']['OrderLine']['ProductId'];
				$total_amount += $orderline['price']*$orderline['quantity'];
// 				if($row['shipping_price'] > $delivery_amonut)
// 					$delivery_amonut = $row['shipping_price']*$row['quantity'];
				$total_qty += $row['ordered_quantity'];
				$items[]=$row;
			}
		$importOrderItems['items']=$items;
		$importOrderItems['total_amount']=$total_amount;
		$importOrderItems['delivery_amonut']=$delivery_amonut;
		$importOrderItems['total_qty']=$total_qty;
		return $importOrderItems;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取某个时间之间的所有的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $dateStart		获取订单的起始时间
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			获取某个时间之间的所有的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl	2014/12/08		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _getAllOrdersSince($bonanza_token , $createtime='', $endtime='',$newBinding =false){
		$timeout=240; //s
		echo "\n enter function : _getAllOrdersSince";
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $wish_token"],"edb\global");
		$config = array('tokenid' => $bonanza_token);
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
		$get_param['query_params'] = json_encode($params);
	
		$retInfo=BonanzaProxyConnectHelper::call_Bonanza_api("getOrderList",$get_param,$post_params=array(),$timeout );
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $wish_token"],"edb\global");

		return $retInfo;
	}//end of _getAllChangeOrdersSince
	
	/**
	 * 通过前端手动触发获取CD订单
	 * @param unknown $bonanza_token
	 * @param string $createtime
	 * @param string $endtime
	 * @param string $newBinding
	 * @param unknown $state
	 * @return string
	 */
	public static function getOrdersByCondition($bonanza_token , $createtime='', $endtime='',$newBinding=false,$state=[]){
		$timeout=240; //s
		echo "\n enter function : _getAllOrdersSince";
		$config = array('tokenid' => $bonanza_token);
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
	
		$retInfo=BonanzaProxyConnectHelper::call_Bonanza_api("getOrderList",$get_param,$post_params=array(),$timeout );
		
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
	private static function _getAllUnfulfilledOrdersSince($bonanza_token , $createtime='', $endtime=''){
		$timeout=240; //s
		echo "\n enter function : _getAllUnfulfilledOrdersSince";
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $wish_token"],"edb\global");
		$config = array('tokenid' => $bonanza_token);
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
		
		$retInfo=BonanzaProxyConnectHelper::call_Bonanza_api("getOrderList",$get_param,$post_params=array(),$timeout );
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $wish_token"],"edb\global");

		//check the return info
		return $retInfo;
	}//end of _getAllChangeOrdersSince
	


	/**
	 * 把Bonanza的订单信息header和items 同步到eagle1系统中user_库的od_order和od_order_item。
	 * 这里主要是通过eagle1提供的 http api的方式
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveBonanzaOrderToOldEagle1($oneOrderReq){
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
	 * 把Bonanza的订单信息header和items 同步到eagle系统中user_库的od_order和od_order_item
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveBonanzaOrderToEagle( $oneOrderReq, $eagleOrderId=-1){
		$result = ['success'=>1,'message'=>''];
		$uid=\Yii::$app->subdb->getCurrentPuid();
		
		$reqInfo[$uid]=array_merge(OrderHelper::$order_demo,$oneOrderReq);
		
		try{
			$result =  OrderHelper::importPlatformOrder($reqInfo,$eagleOrderId);
			//SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'info','data='.json_encode($reqInfo));//test liang
		}catch(\Exception $e){
			$message = "importPlatformOrder fails.  BonanzaId=$eagleOrderId  Exception error:".$e->getMessage()."data: \n ".print_r($reqInfo,true);
			//\Yii::error(['bonanza',__CLASS__,__FUNCTION__,'Background',"Step 1.5a ". $message ],"edb\global");
			echo $message;
			return ['success'=>1,'message'=>$message];
		}
	
		return $result;
	}
	
	/**
	 * set bonanza store token,when it's token is null or token was expired
	 * @param	object	$bonanzaUser
	 * return	array	$bonanzaUser
	 */
	Public static function setBonanzaUserToken($bonanzaUser){
		echo "\n setBonanzaUserToken 0";
		if(!empty($bonanzaUser->auth_type)){
			$A_username = $bonanzaUser->username;
			$A_password = $bonanzaUser->password;
		}else{
			$A_username = $bonanzaUser->api_username;
			$A_password = $bonanzaUser->api_password;
		}
			
		if(!empty($A_username) && !empty($A_password)){
			$one_day_ago = date('Y-m-d H:i:s',strtotime("-1 days"));
			if($one_day_ago >= $bonanzaUser->token_expired_date){
				$config = array();
				$config['username']=$A_username;
				$config['password']=$A_password;
				$get_param['config'] = json_encode($config);
				$reqInfo=BonanzaProxyConnectHelper::call_Bonanza_api("getTokenID",$get_param,$post_params=array() );
				print_r($reqInfo,true);
				echo "\n setBonanzaUserToken 0-1";
				if($reqInfo['success']){
					echo "\n setBonanzaUserToken 1";
					if(!empty($reqInfo['proxyResponse']['success']) && stripos($reqInfo['proxyResponse']['tokenMessage'],'Object moved to')===false){
						echo "\n setBonanzaUserToken 2";
						$tokenid = $reqInfo['proxyResponse']['tokenMessage'];
						$bonanzaUser->token = $tokenid;
						$bonanzaUser->token_expired_date = date('Y-m-d H:i:s');
						$bonanzaUser->update_time = date('Y-m-d H:i:s');

						if(!$bonanzaUser->save()){
							print_r($bonanzaUser->getErrors(),true);
						}
					}else{
						echo "\n".$reqInfo['proxyResponse']['message'];
						$bonanzaUser->order_retrieve_message = "token同步失败,最后尝试同步时间为".date('Y-m-d H:i:s');
						if($bonanzaUser->save()){
							print_r($bonanzaUser->getErrors(),true);
						}
						echo "\n ".print_r($reqInfo);
					}
				}
				else{
					echo "\n".$reqInfo['message'];
				}
			}
		}
		return $bonanzaUser;
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
// 		echo "\n enter BonanzaOrderHelper function : getOrderClaimList";
		
// 		$bonanzaAccount = SaasBonanzaUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
// 		$bonanza_token = $bonanzaAccount['token'];
		$bonanza_token = $token;
		$config = array('tokenid' => $bonanza_token);
		$get_param['config'] = json_encode($config);
		
		if ($createtime !== ''){
			$params['begincreationdate'] = $createtime;
			$params['beginmodificationdate'] = $createtime;
		}
		if ($endtime !== ''){
			$params['endcreationdate'] = $endtime;
			$params['endmodificationdate'] = $endtime;
		}
		if(!empty($orderId))
			$params['orderlist']=$orderId;
		
		$params['state'] = $status;
		
		$get_param['query_params'] = json_encode($params);
		
		$rtn=BonanzaProxyConnectHelper::call_Bonanza_api("GetOrderClaimList",$get_param,$post_params=array(),$timeout );
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
		echo "\n enter BonanzaOrderHelper function : getOrderQuestionList";
	
		$bonanzaAccount = SaasBonanzaUser::find()->where(["uid"=>$uid])->asArray()->one();
		$bonanza_token = $bonanzaAccount['token'];
	
		$config = array('tokenid' => $bonanza_token);
		$get_param['config'] = json_encode($config);
	
		if ($createtime !== ''){
			$params['begincreationdate'] = $createtime;
			$params['beginmodificationdate'] = $createtime;
		}
		if ($endtime !== ''){
			$params['endcreationdate'] = $endtime;
			$params['endmodificationdate'] = $endtime;
		}
		if(!empty($orderId))
			$params['orderlist']=$orderId;
	
		$params['state'] = $status;
	
		$get_param['query_params'] = json_encode($params);
	
		$rtn=BonanzaProxyConnectHelper::call_Bonanza_api("GetOrderQuestionList",$get_param,$post_params=array(),$timeout );
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
		echo "\n enter BonanzaOrderHelper function : generateDiscussionMailGuid,orderid=$orderId";
	
		$bonanzaAccount = SaasBonanzaUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($bonanzaAccount)){
			$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$bonanza_token = $bonanzaAccount['token'];
	
		$config = array('tokenid' => $bonanza_token);
		$get_param['config'] = json_encode($config);
		
		$params['orderid']= $orderId;
		$get_param['query_params'] = json_encode($params);
		$rtn=BonanzaProxyConnectHelper::call_Bonanza_api("generateDiscussionMailGuid",$get_param,$post_params=array(),$timeout );
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
		echo "\n enter BonanzaOrderHelper function : getDiscussionMailList,orderid=$orderId";
	
		$bonanzaAccount = SaasBonanzaUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($bonanzaAccount)){
			$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$bonanza_token = $bonanzaAccount['token'];
	
		$config = array('tokenid' => $bonanza_token);
		$get_param['config'] = json_encode($config);
		
		//try to get order's claim, retraction, questions ids
	
	
		//
		$discussionIds = '12487210';
		$params['discussionIds'][]= $discussionIds;
		$get_param['query_params'] = json_encode($params);
		$rtn=BonanzaProxyConnectHelper::call_Bonanza_api("getDiscussionMailList",$get_param,$post_params=array(),$timeout );
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
		echo "\n enter BonanzaOrderHelper function : AccepteOrRefuseOrders ,uid=$uid ,storeName=$storeName;";
		echo "\n orderId=".print_r($orderIds);
		
		$bonanzaAccount = SaasBonanzaUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($bonanzaAccount)){
		$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$bonanza_token = $bonanzaAccount['token'];
		
		$config = array('tokenid' => $bonanza_token);
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
		$rtn=BonanzaProxyConnectHelper::call_Bonanza_api("AccepteOrRefuseOrders",$get_param,$post_params=array(),$timeout );
		echo "\n getDiscussionMailList return: \n";
		
		//print_r($rtn);

		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Calculate sales within 15 days by one bonanza account.
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
		
		$calculateSales = ConfigHelper::getConfig("BonanzaOrder/CalculateSalesDate",'NO_CACHE');
		
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
			$command->bindValue(":store_name",$store_name,\PDO::PARAM_STR);
			$finalSql = $command->getRawSql();
			echo "\n calculateSalesWithin15Days sql:".$finalSql;//liang test
			$rows = $command->queryAll();
			
			$update_offer_id = [];
			foreach ($rows as $index=>$row){
				if(!empty($row['quantity'])){
					$offer = BonanzaOfferList::find()
								->where(['product_id'=>$row['order_source_itemid'],'seller_product_id'=>$row['sku'],'seller_id'=>$user_name])
								->One();
					if($offer<>null){
						$offer->last_15_days_sold = $row['quantity'];
						echo "\n find offer need to update product_id:".$row['order_source_itemid'].',seller_product_id:'.$row['sku'].',seller_id:'.$user_name.',quantity:'.$row['quantity']; //liang test
						if($offer->save(false))
							$update_offer_id[]=$offer->id;
					}
				}
			}
			
			if(!empty($update_offer_id)){
				$id_str = implode(',', $update_offer_id);
				$resetCount = BonanzaOfferList::updateAll(['last_15_days_sold'=>0],"id not in ($id_str)");
				echo "\n update have no sold in 15 days prods,count=$resetCount";
			}
		
			$calculateSalesInfo[$user_name] = TimeUtil::getNow();
			ConfigHelper::setConfig("BonanzaOrder/CalculateSalesDate",json_encode($calculateSalesInfo));
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
	 * @param 	$bonanzaAccount		array
	 +---------------------------------------------------------------------------------------------
	 * @return	createRecordCount		int
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/11			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function saveOrderDetailToOfferListIfIsNew($orderDetailInfo,$bonanzaAccount){
		echo '\n enter saveOrderDetailToOfferListIfIsNew';
		$create = 0;
		$product_info_arr = [];
		$item_id_arr=[];
		$product_sku_list = [];
		//echo "<br>orderDetailInfo:".print_r($orderDetailInfo);
		foreach ($orderDetailInfo as $order_detail){
		    foreach ($order_detail as $detail){
		        if(empty($detail['sku']) && empty($detail['itemID']))//无任何商品标识
		            continue;
		        if(!empty($detail['sku'])){//有sku
		            if(in_array($detail['sku'], $product_sku_list)){
		                continue;
		            }else{
		                $product_sku_list[] = $detail['sku'];
		                $product_info_arr[$detail['sku']] = array(
		                    'sku'=>empty($detail['sku'])?'':$detail['sku'],
		                    'ean'=>empty($detail['ean'])?'':$detail['ean'],
		                    'itemID'=>empty($detail['itemID'])?'':$detail['itemID'],
		                );
		            }
		            if(!empty($detail['itemID'])){
		                $item_id_arr[] = $detail['itemID'];
		            }
		        }else{//无sku
		            $item_id_arr[] = $detail['itemID'];
		        }
		    }
		}
		//echo "<br>product_sku_list:<br>";
		//print_r($product_sku_list);
		if(!empty($item_id_arr))
			BonanzaOfferSyncHelper::syncProdInfoWhenGetOrderDetail($bonanzaAccount['uid'],$bonanzaAccount['store_name'], $sku_arr=[], $item_id_arr,2,$type='itemID');
		
		if(empty($product_info_arr))
			return $create;
		
// 		try{
// 			$prod_sku_arr = [];
// 			foreach ($product_info_arr as $info){
// 				$sku = $info['sku'];
// 				if(!in_array($sku,$prod_sku_arr)){
// 					$prod_sku_arr[] = $sku;
// 					//$prod_sku_arr[] = $info['sku'];//Ean在以前已经排除了重复，这里的Ean没有重复值
// 				}
// 			}
// 			//echo "<br>prod_id_arr:".print_r($prod_id_arr);
// 			if(!empty($prod_sku_arr)){
// 				//找出offer表里面没有的ean，保存他们
// 				$offerList = BonanzaProductList::find()->select(['barcode'])
// 					->where(['barcode'=>$prod_sku_arr])
// 					->andWhere(['seller_id'=>$bonanzaAccount['username']])
// 					->asArray()->all();
				
// 				$not_insert_sku_list = [];
// 				foreach ($offerList as $offer){
// 					$not_insert_sku_list[] = $offer['partnumber'];
// 				}
// 				$insert_sku_list = array_diff($prod_sku_arr,$not_insert_sku_list);
// 				$syn_itemid_list = [];
// 				$syn_sku_list = [];
// 				$insert_prod_info_arr = [];
// 				foreach ($insert_sku_list as $sku){
// 					if(!empty($product_info_arr[$sku])){
// 						$insert_prod_info_arr[]=array(
// 							'barcode'=>$product_info_arr[$sku]['ean'],
// 							'partnumber'=>$product_info_arr[$sku]['sku'],
// 							'seller_id'=>$bonanzaAccount['username'],
// 						);
// 						$syn_sku_list[] = $sku;
// 						$syn_itemid_list[] = $product_info_arr[$sku]['itemid'];
// 					}
// 				}
// 				//echo "<br>insert_prod_info_arr:<br>";
// 				//print_r($insert_prod_info_arr);
				
// 				if(!empty($insert_prod_info_arr)){
// 					$offerInsert = SQLHelper::groupInsertToDb('priceminister_product_list', $insert_prod_info_arr);
// 					//mark error log
// 					if($offerInsert!=count($insert_prod_info_arr)){
// 						\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',date('Y-m-d H:i:s')." PmInsertOfferError:".count($insert_prod_info_arr)."need to insert, only ".$offerInsert." inserted" ],"edb\global");
// 					}else 
// 						$create = $offerInsert;
// 				}
				
// 				//抓取商品信息
// 				BonanzaOfferSyncHelper::syncProdInfoWhenGetOrderDetail($bonanzaAccount['uid'],$bonanzaAccount['username'], $syn_sku_list, $syn_itemid_list, 2, $type='sku');
// 			}
// 			return $create;
// 		}catch (\Exception $e) {
// 			echo "\n saveOrderDetailToOfferListIfIsNew error :".$e->getMessage();
// 			return $create;
// 		}
	}
	
	/**
	 * 将Cdisocount原始表信息转换成od_order_v2表的信息
	 */
	public static function formatSrcOrderDataToOmsOrder($bonanzaOrderData,$bonanzaAccount,$getEmail=false){
		$importOrderData = array();
	
		//$importOrderData['order_id'] = $bonanzaOrderData['OrderNumber'];
		if (!empty($bonanzaOrderData['orderstate'])){
			/**
			 * 	bonanza order state
			 *  State 'WaitingForShipmentAcceptation' means that the order is waiting to ship.
			 *  State 'Shipped' means that the order has been marked as shipped by you.
			 *  State 'CancelledByCustomer' means that the order has been refunded by customer and should not be fulfilled.
			 *  State 'PaymentInProgress' means that the order is waiting customer to pay
			 *
			 *  eagle order status
			 *  100（未付款）、200（已付款）、300（发货处理中）、400（已发货）、500（已完成）、600（已取消）
			 * */
			$BonanzaStateToEagleStatusMapping = array(
					'WaitingForShipmentAcceptation'=> 200 ,
					'Shipped'=>500,
					'CancelledByCustomer'=>600,
					'AcceptedBySeller'=>100,
					'PaymentInProgress'=>100,
					'RefusedBySeller'=>600,
					'AutomaticCancellation'=>600,
					'PaymentRefused'=>100,
					'ShipmentRefusedBySeller'=>400,
					'RefusedNoShipment'=>400,
			);
			$importOrderData['order_status'] = $BonanzaStateToEagleStatusMapping[$bonanzaOrderData['orderstate']];
			$importOrderData['order_source_status'] = $bonanzaOrderData['orderstate'];
		}
	
		if (isset($importOrderData['order_status'] )){
			//发货状态：1.已发货；0.未发货 #非已发货状态暂时定义为未发
			$importOrderData['shipping_status'] = (($importOrderData['order_status'] == 500)?1:0);
		}
	
		$importOrderData['pay_status'] = ($importOrderData['order_status']<200)?0:1;
	
		$importOrderData['order_source'] = 'bonanza';//订单来源
	
		$addinfo = json_decode($bonanzaOrderData['addinfo'],true);
		
		$importOrderData['order_type'] = '';//订单类型
		if(isset($addinfo['FBC']) && $addinfo['FBC'])
			$importOrderData['order_type'] = 'FBC';
	
		if(isset($addinfo['FBC']) && $addinfo['FBC']){//如果是FBC订单，则认为是已发货。
			$importOrderData['order_status']=500;
			$importOrderData['shipping_status']=1;
		}
	
		if (!empty($bonanzaOrderData['ordernumber'])){
			$importOrderData['order_source_order_id'] = $bonanzaOrderData['ordernumber']; //订单来源的订单id
		}
	
		$importOrderData['order_source_site_id'] = 'FR'; //订单来源平台下的站点,Bonanza暂无分站点,只有法国站
	
		if (isset($bonanzaOrderData['selleruserid']))
			$importOrderData['selleruserid'] = $bonanzaOrderData['selleruserid']; //订单来源平台卖家用户名(下单时候的用户名)
		if (isset($bonanzaOrderData['saas_platform_user_id']))
			$importOrderData['saas_platform_user_id'] = $bonanzaOrderData['saas_platform_user_id']; //订单来源平台卖家用户名(下单时候的用户名)
	
		$buyer_name='';
		if (!empty($bonanzaOrderData['customer_civility']) && !is_array(json_decode($bonanzaOrderData['customer_civility'],true)))
			$buyer_name.=$bonanzaOrderData['customer_civility']." ";
		if(!empty($bonanzaOrderData['customer_firstname']) && !is_array(json_decode($bonanzaOrderData['customer_firstname'],true)))
			$buyer_name.=$bonanzaOrderData['customer_firstname']." ";
		if(!empty($bonanzaOrderData['customer_lastname']) && !is_array(json_decode($bonanzaOrderData['customer_lastname'],true)))
			$buyer_name.=$bonanzaOrderData['customer_lastname'];
		
		$importOrderData['source_buyer_user_id'] = $buyer_name; //买家名称
	
		if (!empty($bonanzaOrderData['creationdate']))
			$importOrderData['order_source_create_time'] = strtotime($bonanzaOrderData['creationdate']); //订单在来源平台的下单时间
	
		$shippingConsignee = '';
		if(!empty($bonanzaOrderData['shipping_civility']) && !is_array(json_decode($bonanzaOrderData['shipping_civility'],true)))
			$shippingConsignee.=$bonanzaOrderData['shipping_civility']." ";
		if(!empty($bonanzaOrderData['shipping_firstname']) && !is_array(json_decode($bonanzaOrderData['shipping_firstname'],true)))
				$shippingConsignee.=$bonanzaOrderData['shipping_firstname']." ";
		if(isset($bonanzaOrderData['shipping_lastname']) && !is_array(json_decode($bonanzaOrderData['shipping_lastname'],true)))
				$shippingConsignee.=$bonanzaOrderData['shipping_lastname']." ";
		
		$importOrderData['consignee'] = $shippingConsignee; //收货人
	
		if (!empty($bonanzaOrderData['shipping_zipcode']) && !is_array(json_decode($bonanzaOrderData['shipping_zipcode'],true)))
			$importOrderData['consignee_postal_code'] = $bonanzaOrderData['shipping_zipcode']; //收货人邮编
	
		if (isset($bonanzaOrderData['customer_phone']) && !is_array(json_decode($bonanzaOrderData['customer_phone'],true)))
			$importOrderData['consignee_phone'] =$bonanzaOrderData['customer_phone']; //收货人电话
	
		if (isset($bonanzaOrderData['customer_mobilephone']) && !is_array(json_decode($bonanzaOrderData['customer_mobilephone'],true)))
			$importOrderData['consignee_mobile'] =$bonanzaOrderData['customer_mobilephone']; //收货移动电话
	
		if(empty($importOrderData['consignee_email']) && $getEmail)
			$importOrderData['consignee_email'] = BonanzaOrderInterface::getEmailByOrderID($bonanzaAccount, $bonanzaOrderData['orderuumber']);
	
		if($importOrderData['consignee_email']=='')
			unset($importOrderData['consignee_email']);
	
		if (isset($bonanzaOrderData['shipping_companyname']) && !is_array(json_decode($bonanzaOrderData['shipping_companyname'],true)))
			$importOrderData['consignee_company'] =$bonanzaOrderData['shipping_companyname']; //收货人公司
	
		if (!empty($bonanzaOrderData['shipping_country']) && !is_array(json_decode($bonanzaOrderData['shipping_country'],true))){
			$importOrderData['consignee_country'] = $bonanzaOrderData['shipping_country']; //收货人国家名
			$importOrderData['consignee_country_code'] = $bonanzaOrderData['shipping_country']; //收货人国家代码
		}else{
			$importOrderData['consignee_country'] = 'FR'; //收货人国家名
			$importOrderData['consignee_country_code'] = 'FR'; //收货人国家代码
		}
	
		if (isset($bonanzaOrderData['shipping_city']) && !is_array(json_decode($bonanzaOrderData['shipping_city'],true)))
			$importOrderData['consignee_city'] = $bonanzaOrderData['shipping_city']; //收货人城市
	
		if (isset($bonanzaOrderData['shipping_placename']) && !is_array(json_decode($bonanzaOrderData['shipping_placename'],true)))
			$importOrderData['consignee_district'] = $bonanzaOrderData['shipping_placename']; //收货人地区
	
		$importOrderData['consignee_address_line1']='';
		if (isset($bonanzaOrderData['shipping_street']) && !is_array(json_decode($bonanzaOrderData['shipping_street'],true)))
			$importOrderData['consignee_address_line1'] = $bonanzaOrderData['shipping_street']; //收货人地址1
		//Apartment
		if (isset($bonanzaOrderData['shipping_apartmentnumber']) and !is_array(json_decode($bonanzaOrderData['shipping_apartmentnumber'],true))){
			if(stripos($bonanzaOrderData['shipping_apartmentnumber'],"apt")===false && stripos($bonanzaOrderData['shipping_apartmentnumber'],"app")===false && stripos($bonanzaOrderData['shipping_apartmentnumber'],"Apartment")===false)
				$Apartment = "Apt.".$bonanzaOrderData['shipping_apartmentnumber'];
			else
				$Apartment = $bonanzaOrderData['shipping_apartmentnumber'];
	
			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = "Apt.".$Apartment;
			else
				$importOrderData['consignee_address_line1'] = $Apartment.";".$importOrderData['consignee_address_line1'];
		}
		//Building
		if(isset($bonanzaOrderData['shipping_building']) && !is_array(json_decode($bonanzaOrderData['shipping_building'],true))){
			if(stripos($bonanzaOrderData['shipping_building'],"bât")===false && stripos($bonanzaOrderData['shipping_building'],"BTMENT")===false && stripos($bonanzaOrderData['shipping_building'],"Bâtiment")===false)
				$Btment = "bât.".$bonanzaOrderData['shipping_building'];
			else
				$Btment = $bonanzaOrderData['shipping_building'];
	
			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = $Btment;
			else
				$importOrderData['consignee_address_line1'] = $Btment.';'.$importOrderData['consignee_address_line1'];
		}
	
		//echo "\n fianl consignee_address_line1 is:".$importOrderData['consignee_address_line1'];//liang test
		if (isset($bonanzaOrderData['shipping_address1']) && !is_array(json_decode($bonanzaOrderData['shipping_address1'],true)))
			$importOrderData['consignee_address_line2'] = $bonanzaOrderData['shipping_address1']; //收货人地址2
	
		if (isset($bonanzaOrderData['shipping_address2']) && !is_array(json_decode($bonanzaOrderData['shipping_address2'],true)))
			$importOrderData['consignee_address_line3'] = $bonanzaOrderData['shipping_address2'];
	
		if (!empty($importOrderData['order_source_create_time'] ))
			$importOrderData['paid_time'] = $importOrderData['order_source_create_time'] ; //订单付款时间
	
		if(!empty($bonanzaOrderData['ValidatedTotalAmount']))
			$importOrderData['total_amount'] = $bonanzaOrderData['ValidatedTotalAmount'];
			
		if(!empty($bonanzaOrderData['ValidatedTotalShippingCharges']))
			$importOrderData['shipping_cost'] = $bonanzaOrderData['ValidatedTotalShippingCharges'];
	
		if (isset($bonanzaOrderData['SiteCommissionValidatedAmount'] ))
			$importOrderData['commission_total'] = $bonanzaOrderData['SiteCommissionValidatedAmount']; //平台佣金
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
	public static function formatSrcOrderItemsDataToOmsOrder($bonanzaOrderItems,$order_number){
		$importOrderItems = array();
		$total_amount=0;
		$delivery_amonut=0;
		$total_qty=0;
		
		foreach ($bonanzaOrderItems as $item){
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
				$srcOffer = BonanzaOfferList::find()->where(['seller_product_id'=>$row['sku']])->one();
				if($srcOffer<>null)
					$row['product_name'] = $srcOffer->name;
			}
			if(empty($row['product_name'])) $row['product_name']=$row['sku'];

			$row['order_source_itemid']=$item['Sku'];

			$row['price'] = $item['purchaseprice'] / $item['quantity'];
			$row['ordered_quantity'] = $item['quantity'];
			$row['quantity']=$item['quantity'];

			if($item['acceptationstate']=='ShippedBySeller'){
				$row['sent_quantity']=$item['quantity'];
				$row['packed_quantity']=$item['quantity'];
			}

			$row['shipping_price']=$item['unitadditionalshippingcharges'];

			$row['photo_primary']='';//bonanza response none product photo info

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
	
	
	public static $OMS_STATUS_BN_STATUS_MAPPING = [
		'100'=>['Complete,Incomplete', 'Complete,Inprocess', 'Complete,Invoiced', 'Incomplete,Active', 'Incomplete,Completed', 'Incomplete,Incomplete', 'Incomplete,Inprocess', 'Incomplete,Invoiced', 'Incomplete,Shipped', 'InProcess,Active', 'InProcess,Completed', 'InProcess,Incomplete', 'InProcess,InProcess', 'InProcess,Invoiced', 'InProcess,Proposed', 'InProcess,Shipped', 
		    'Invoiced,Active', 'Invoiced,Completed', 'Invoiced,Incomplete', 'Invoiced,Inprocess', 'Invoiced,Invoiced', 'Invoiced,Proposed', 'Invoiced,Shipped', ],
		'200'=>['Complete,Active','Complete,Completed'],
		'300'=>[],
		'400'=>[],//CD平台如果已发货，将会同步回来Shipped状态，订单自动转到已完成，否则就是同步出现问题。因此这里发货中状态还是设置为WaitingForShipmentAcceptation
		'500'=>['Complete,Shipped' ],
		'600'=>['Complete,Cancelled', 'Incomplete,Cancelled', 'InProcess,Cancelled', 'Invoiced,Cancelled'],
	];
	
	public static $CD_OMS_WEIRD_STATUS = [
		'sus'=>'Bonanza后台状态和小老板状态不同步',//satatus unSync 状态不同步
		'wfs'=>'提交发货或提交物流',//waiting for shipment
		'wfd'=>'交运至物流商',//waiting for dispatch
		'wfss'=>'等待手动标记发货，或物流模块"确认已发货"',//waiting for sing shipped ,or confirm dispatch
		'tuol'=>'物流未上网',//track unOnLine
	];
	
	/**
	 * @description			bonanza需要两个接口（获取新订单以及获取修改的订单）才能获取时间段内的所有订单，可以能有重复订单，需要合并
	 * @access static
	 * @param $create_order		新订单
	 *        $modified_order   修改订单
	 * @return	$orders			array
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lwj	2016/4/26	初始化
	 */
	public static function merageBonanzaOrder($create_order,$modified_order){
	    $orders = array();
	    if(!empty($create_order)&&!empty($modified_order)){//合并重复订单
	        $merage_orders = array();
	        foreach ($create_order as $create){
	            $merage_orders[$create['orderID']] = $create;
	        }
	        
	        foreach ($modified_order as $modified){
	            if(isset($merage_orders[$modified['orderID']])){
	                $merage_orders[$modified['orderID']] = $modified;//重复覆盖
	            }else{
	                $merage_orders[$modified['orderID']] = $modified;//添加
	            }
	        }
	        
	        foreach ($merage_orders as $key =>$val){
	            $orders[] = $val;
	        }
	    }else if(!empty($create_order)&&empty($modified_order)){
	        $orders = $create_order;
	    }else if(empty($create_order)&&!empty($modified_order)){
	        $orders = $modified_order;
	    }
	    return $orders;
	}
	
	
	/**
	 * @description			定期自动检测用户的Bonanza订单，如果检测到订单有可能异常，自动add tag
	 * @access static
	 * @param $job_id		job action number
	 * @return				array
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015/12/25	初始化
	 */
	public static function cronAutoAddTagToBonanzaOrder($job_id){
		try {
			//set runtime log
			$the_day=date("Y-m-d",time());
			$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='BNOMS' and info_type='runtime-CheckOrder' and `addinfo`='job_id=".$job_id."' "  );
			$record = $command->queryOne();
			if(!empty($record)){
				$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`the_day`='".$the_day."'  where app='BNOMS' and info_type='runtime-CheckOrder' and `addinfo`='job_id=".$job_id."' ";
				$command = Yii::$app->db_queue->createCommand( $sql );
				$affect_record = $command->execute();
			}else{
				$sql = "INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('BNOMS','runtime-CheckOrder','normal','','job_id=".$job_id."','','".$the_day."','".date("Y-m-d H:i:s",time())."')";
				$command = Yii::$app->db_queue->createCommand( $sql );
				$affect_record = $command->execute();
			}//set runtime log end
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
	
			$two_days_ago = strtotime('-2 days');
			$five_days_ago = strtotime('-5 days');
	
// 			$SAASCDISCOUNTUSERLIST = SaasBonanzaUser::find()->select(" distinct `uid` ")->where("is_active='1' and initial_fetched_changed_order_since is not null")
// 				->andwhere(" uid=$job_id ")->orderBy("last_order_success_retrieve_time asc")->asArray()->all();
			$SAASCDISCOUNTUSERLIST = SaasBonanzaUser::find()->select(" distinct `uid` ")->where("is_active='1' and initial_fetched_changed_order_since is not null")
			->orderBy("last_order_success_retrieve_time asc")->asArray()->all();
			foreach($SAASCDISCOUNTUSERLIST as $bonanzaAccount ){
				$uid = $bonanzaAccount['uid'];
				if (empty($uid)){
					//异常情况
					$message = "site id :".$bonanzaAccount['site_id']." uid:0";
					echo "\n ".$message;
					continue;
				}
 
	
				$updateTime =  TimeUtil::getNow();
				
				//删除已完成订单的weird_status
				OdOrder::updateAll(['weird_status'=>'']," order_status in (500,600) and (weird_status is not null or weird_status<>'') and order_source='bonanza' ");
				echo "\n cleared all complete order's weird_status";
				
				$ot_orders = OdOrder::find()
					->where(['order_source'=>'bonanza'])
					->andWhere(" update_time<='$two_days_ago' ")
					->andWhere(['in','order_status',[200,300,400]])
					->asArray()->all();
				echo "\n has ot_order :".count($ot_orders);
				
				$status_mapping = self::$OMS_STATUS_BN_STATUS_MAPPING;
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
						OdOrder::updateAll(['weird_status'=>$s],['order_source'=>'bonanza','order_id'=>$id_arr]);
					}
				}
			}//end of each bonanza user uid
		} catch (\Exception $e) {
			echo "\n cronAutoFetchRecentOrderList Exception1:".$e->getMessage();
		}
	}//end of cronAutoFetchUnFulfilledOrderList
}
