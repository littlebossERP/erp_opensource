<?php
namespace eagle\modules\order\helpers;
use yii;
use yii\data\Pagination;

use eagle\modules\listing\models\RumallApiQueue;
use eagle\modules\order\models\RumallOrder;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\RumallOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\models\SysCountry;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\listing\helpers\RumallProxyConnectHelper;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\models\SaasRumallUser;
use eagle\modules\listing\helpers\RumallOfferSyncHelper;
use eagle\modules\listing\models\RumallOfferList;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\HttpHelper;
use common\helpers\Helper_Array;
use eagle\models\OdOrderShipped;
use eagle\models\QueueSyncshipped;
use eagle\modules\platform\apihelpers\RumallAccountsApiHelper;
use eagle\models\QueueRumallOrders;
use eagle\modules\order\models\RumallOrdersHistory;
use eagle\modules\platform\helpers\RumallAccountsHelper;
use eagle\modules\util\helpers\CountryHelper;
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
 * Rumall订单模板业务
 +------------------------------------------------------------------------------
 * @category	item
 * @package		Helper/item
 * @subpackage  Exception
 * @author		lzhl
 +------------------------------------------------------------------------------
 */
class RumallOrderHelper {
    
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
        
        $query = SaasRumallUser::find();
        if (!empty($uid)){
            $query->where(['uid'=>$uid]);
        }
        $AccountList = RumallAccountsApiHelper::ListAccounts($uid);
        $syncList = [];
        foreach($AccountList as $account){
            $orderSyncInfo = RumallAccountsApiHelper::getRumallOrderSyncInfo($account['site_id'],$account['uid']);
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
	
//     public static $getOrderUrl = "http://47.88.2.1:8080/rumall/services/rumallInter?wsdl";//测试地址
    public static $getOrderUrl = "http://rumall.com:8080/rumall/services/rumallInter?wsdl";//正式地址
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
	
	public static function getShippingCodeNameMap(){
	    return array(
	        '9'=>'顺丰国际小包（平邮）',
	        '10'=>'顺丰国际小包（挂号）',
	        '29'=>'顺丰国际电商专递',
	        '101'=>'顺丰国际标准快递',
	       ' 202'=>'顺丰国际特惠-包裹',
	    );
	}
	
	
	public static function getRumallOrderShippingCode(){
		return self::$ShippingCode;
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
	static public function getRumallOmsNav($key_word){
	    $order_nav_list = [
	        '同步订单'=>'/order/rumall-order/order-sync-info' ,
	        '待接受'=>'/order/rumall-order/list?order_status=50',
	        '已付款'=>'/order/rumall-order/list?order_status=200&pay_order_type=pending' ,
	        '发货中'=>'/order/rumall-order/list?order_status=300' ,
	        '已完成'=>'/order/rumall-order/list?order_status=500' ,
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
// 			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));//600 m is test ,real value is 30
// 			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
				
			$saasRumallUserList = SaasRumallUser::find()->where("is_active='1' and initial_fetched_changed_order_since is null or initial_fetched_changed_order_since='0000-00-00 00:00:00' or last_order_success_retrieve_time='0000-00-00 00:00:00' ")->all();
				
			//retrieve orders  by  each rumall account
			foreach($saasRumallUserList as $rumallAccount ){
				$uid = $rumallAccount['uid'];
				//update token //liang 2015-07-14
// 				$rumallAccount = self::setRumallUserToken($rumallAccount);
				if(empty($rumallAccount->token)){
					//获取token出了问题
					echo "\n get token failed;";
					$rumallAccount->order_retrieve_message = '获取token出现问题，请等待一段时间，或联系客服';
					$rumallAccount->save(false);
					continue;
				}
				
				if(empty($rumallAccount->company_code)){
				    //获取token出了问题
				    echo "\n get company_code failed;";
				    $rumallAccount->order_retrieve_message = '获取company_code出现问题，请等待一段时间，或联系客服';
				    $rumallAccount->save(false);
				    continue;
				}
				
				echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
				if (empty($uid)){
				//异常情况
					$message = "site id :".$rumallAccount['site_id']." uid:0";
					echo "\n ".$message;
					//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
					continue;
				}

				$updateTime = TimeUtil::getNow();
				//update this rumall account as last order retrieve time
				$rumallAccount->last_order_retrieve_time = $updateTime;
		
				$sinceTimeUTC = date('Y-m-d H:i:s' ,strtotime($updateTime)-3600*24*30);//UTC time is -8 hours
				$sinceTimeUTC = substr($sinceTimeUTC,0,10);
				
				$dateSince = date('Y-m-d H:i:s' ,strtotime($updateTime)-3600*24*30);// test 8hours //1个月前
				
				//start to get unfulfilled orders
				echo "\n".$updateTime." start to get $uid unfufilled order for ".$rumallAccount['store_name']." since $dateSince \n"; //ystest
				
				$getOrderCount = 0;
				
				$recentGetOrderTime = $dateSince;
				do{
				    $create_orders = '';
				    $Modified_orders = '';
					$startTime = $recentGetOrderTime;
					$recentGetOrderTime = date("Y-m-d H:i:s" ,strtotime($recentGetOrderTime)+3600*24*3);//get 3 day one time
					$endTime = $recentGetOrderTime;
					//rumall获取订单没有时间滑动窗口，只有未出库的订单
// 					$create_orders = RumallOrderInterface::GetNewSalesWithInTime($startTime,$endTime);
					$orders = array();
					$one_page_num = 1; 
                    do{//多次取完所有订单
                        $newInterface = new RumallOrderHelper();
                        $create_orders = $newInterface->getNewOrder($rumallAccount->company_code, $rumallAccount->token,$one_page_num);//默认pagenum为1
                        	
                        if (empty($create_orders['success'])){
                            echo "\n GetNewSales fail :".$create_orders['message'].",time:".date("Y-m-d H:i:s",time());
                            $rumallAccount->order_retrieve_message = " GetNewSales fail :".$create_orders['message'].",time:".date("Y-m-d H:i:s",time());
                            //\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
                            $rumallAccount->save();
                            return array('success'=>false,'message'=>"uid:".$rumallAccount->uid." cronAutoFetchNewOrderList GetNewSales Exception:".$create_orders['message']);
                            // 						break;
                        }else if(empty($create_orders['data']['SaleOrders'])){
                            $rumallAccount->order_retrieve_message = "get non order";
                            $rumallAccount->save();
                        }else{
                            $rumallAccount->order_retrieve_message = "";
                            $rumallAccount->save();
                        }
                        	
                        
                        if(!empty($create_orders['data']['SaleOrders']))
                        {
                            if(isset($create_orders['data']['SaleOrders']['SaleOrder'][0])){
                                foreach ($create_orders['data']['SaleOrders']['SaleOrder'] as $key=>$val){
					                $orders[] = $val;
					            }
                            }else{
                                $orders[] = $create_orders['data']['SaleOrders']['SaleOrder'];
                            }
                        }
                        $one_page_num = $one_page_num + 1;
                    }while(!empty($create_orders['data']['SaleOrders']));
					
                    echo "\n".TimeUtil::getNow()." got results, start to insert oms \n";
//                     print_r($orders);
                    if(!empty($orders)){
                        $rtn = self::_InsertRumallOrder($orders, $rumallAccount);
                        echo "\n uid = $uid handled orders count ".count($orders)." for ".$rumallAccount['token'];
                        //\Yii::info(['rumall',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$rumallAccount['token']],"edb\global");
                        	
                        if(!empty($rtn['success'])){
                            $rumallAccount->initial_fetched_changed_order_since = $endTime;
                            $rumallAccount->routine_fetched_changed_order_from = $endTime;
                            $rumallAccount->last_order_success_retrieve_time = $endTime;
                        }else{
                            $rumallAccount->initial_fetched_changed_order_since = $endTime;
                            $rumallAccount->routine_fetched_changed_order_from = $endTime;
                            $rumallAccount->order_retrieve_message = $rtn['message'];
                        }
                    }else{
                        //没有订单，则视为全部出库
                        $empty_had_out_order = array();
                        $empty_all_queue_record = QueueRumallOrders::find()->where(['uid'=>$rumallAccount['uid'],'store_name'=>$rumallAccount['store_name']])->asArray()->all();
                        if(!empty($empty_all_queue_record)){
                            foreach ($empty_all_queue_record as $empty_value){
                                $empty_had_out_order[] = $empty_value['orderId'];
                            }
                        }
                        if(!empty($empty_had_out_order)){
                            QueueRumallOrders::deleteAll(['orderId'=>$empty_had_out_order]);
                            $empty_new_rumall_order = RumallOrder::find()->where(['ErpOrder'=>$empty_had_out_order])->all();
                            if(!empty($empty_new_rumall_order)){
                                foreach ($empty_new_rumall_order as $new_rumall_order_detail){
                                    $empty_order_history = new RumallOrdersHistory();
                                    $empty_order_history->store_name = $rumallAccount['store_name'];
                                    $empty_order_history->orderId = $new_rumall_order_detail->ErpOrder;
                                    $empty_order_history->order_detail = $new_rumall_order_detail->order_detail;
                                    if(!$empty_order_history->save(false)){
                                        \Yii::error("Fail to insert rumall_orders_history".$rumallAccount['uid']." orderId ".$new_rumall_order_detail->ErpOrder.".","file");
                                    }
                                }
                            }
                            //改变小老板平台订单状态
                            $getChangedRecord = OdOrder::find()->where(['order_source_order_id'=>$empty_had_out_order])->andWhere(['order_source'=>'rumall'])->all();
                            if(!empty($getChangedRecord)){
                                foreach ($getChangedRecord as $v){
                                    $v->order_status = 500;
                                    $v->order_source_status = '已完成';
                                    if(!$v->save(false)){
                                        \Yii::error("Fail to change order_v2 status , orderId ".$getChangedRecord->order_source_order_id.".","file");
                                    }
                                }
                            }
                        }
                        
                        $rumallAccount->initial_fetched_changed_order_since = $endTime;
                        $rumallAccount->routine_fetched_changed_order_from = $endTime;
                        $rumallAccount->last_order_success_retrieve_time = $endTime;
                        $rumallAccount->order_retrieve_message = 'get non order';
                    }					

// 					if (!empty ($orders['proxyResponse']['message'])){
// 						$rumallAccount->order_retrieve_message = $orders['proxyResponse']['message'];
// 					}else
// 						$rumallAccount->order_retrieve_message = '';//to clear the error msg if last attemption got issue
					
					if (!$rumallAccount->save(false)){
						echo "\n failure to save rumall account info ,errors:";
						echo "\n uid:".$rumallAccount['uid']." error:". print_r($rumallAccount->getErrors(),true);
						//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"failure to save rumall operation info ,uid:".$rumallAccount['uid']."error:". print_r($rumallAccount->getErrors(),true)],"edb\global");
						break;
					}else{
						echo "\n RumallAccount model save !";
					}
				}while ($recentGetOrderTime < $updateTime );
		
			}//end of each rumall user account
		}
		catch (\Exception $e) {
			echo "\n cronAutoFetchNewAccountOrderList Exception:".$e->getMessage();
			//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
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
		$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
		$record = $command->queryOne();
		if(!empty($record)){
			$run_times = json_decode($record['addinfo2'],true);
			if(!is_array($run_times))
				$run_times = [];
			$run_times['enter_times'] = empty($run_times['enter_times'])?1 : $run_times['enter_times']+1;
			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'   where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
			$command = Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
		}else{
			$run_times = ['enter_times'=>1,'end_times'=>0];
			$sql = "INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('RMOMS','runtime-GetOrder','normal','','job_id=".$job_id."','".json_encode($run_times)."','".$the_day."','".date("Y-m-d H:i:s",time())."')";
			$command = Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
		}//set runtime log end
		if(empty($affect_record))
			echo "\n set runtime log falied, sql:".$sql;
		
		try {				
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));//600 m is test ,real value is 30
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_rumall_user set last_order_success_retrieve_time='0000-00-00 00:00:00',last_order_retrieve_time='0000-00-00 00:00:00'  
									where last_order_success_retrieve_time is null or last_order_retrieve_time is null"  );
			$affectRows = $command->execute();
			
// 			$saasRumallUserList = SaasRumallUser::find()->where("is_active='1' and initial_fetched_changed_order_since is not null 
// 					and last_order_success_retrieve_time<'$half_hours_ago'")->andwhere(" uid%3=$job_id ")->orderBy("last_order_success_retrieve_time asc")->all();
			$saasRumallUserList = SaasRumallUser::find()->where("is_active='1' and initial_fetched_changed_order_since is not null
			    and last_order_success_retrieve_time<'$half_hours_ago'")->orderBy("last_order_success_retrieve_time asc")->all();
			//retrieve orders  by  each rumall account 
			foreach($saasRumallUserList as $rumallAccount ){
				$uid = $rumallAccount->uid;
				//update token //liang 2015-07-14
// 				$rumallAccount = self::setRumallUserToken($rumallAccount);
			   if(empty($rumallAccount->token)){
					//获取token出了问题
					echo "\n get token failed;";
					$rumallAccount->order_retrieve_message = '获取token出现问题，请等待一段时间，或联系客服';
					$rumallAccount->save(false);
					continue;
				}
				
				if(empty($rumallAccount->company_code)){
				    //获取token出了问题
				    echo "\n get company_code failed;";
				    $rumallAccount->order_retrieve_message = '获取company_code出现问题，请等待一段时间，或联系客服';
				    $rumallAccount->save(false);
				    continue;
				}
// 				if( strtotime($rumallAccount->token_expired_date) < strtotime("-1 days") ){
// 					$rumallAccount->order_retrieve_message = 'token已过期，请检测绑定信息中的 账号，密码是否正确。';
// 					if (!$rumallAccount->save(false)){
// 						echo "\n failure to save rumall account info ,error:";
// 						echo "\n uid:".$rumallAccount['uid']."error:". print_r($rumallAccount->getErrors(),true);
// 						//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"failure to save rumall operation info ,uid:".$rumallAccount['uid']."error:". print_r($rumallAccount->getErrors(),true)],"edb\global");
// 					}else{
// 						echo "\n RumallAccount model save(token expired) !";
// 					}
// 					continue;
// 				}
				echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
				if (empty($uid)){
					//异常情况 
					$message = "site id :".$rumallAccount['site_id']." uid:0";
					echo "\n ".$message;
					//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");					
					continue;
				}
				

				$updateTime =  TimeUtil::getNow();
				$onwTimeUTC = date("Y-m-d H:i:s");// not UTC time, local time(UTC+8) can get more order
				$sinceTimeUTC = date("Y-m-d H:i:s" ,strtotime($rumallAccount->last_order_success_retrieve_time)-3600*24*2);
				
				
				//由于bug或其他原因导致账号长时间(超过5日)没有成功获取到订单的情况
				if( strtotime($rumallAccount->last_order_success_retrieve_time) < strtotime("-2 days")){
					$updateTime =  date("Y-m-d H:i:s" ,strtotime($rumallAccount->last_order_success_retrieve_time)+3600*24); //上次成功获取订单后一天
					$onwTimeUTC = date("Y-m-d H:i:s" ,strtotime($rumallAccount->last_order_success_retrieve_time)+3600*24);
					$sinceTimeUTC = date("Y-m-d H:i:s" ,strtotime($rumallAccount->last_order_success_retrieve_time)-3600*24*2);
				}
				
				$getOrderCount = 0;
				//update this rumall account as last order retrieve time
				$rumallAccount->last_order_retrieve_time = $updateTime;	
							
				if (empty($rumallAccount->last_order_success_retrieve_time) or $rumallAccount->last_order_success_retrieve_time=='0000-00-00 00:00:00'){
					//如果还没有初始化完毕，就什么都不do
					echo "\n uid=$uid haven't initial_fetched !";
				}else{
					//start to get unfulfilled orders
					echo "\n".TimeUtil::getNow()." start to get $uid unfufilled order for ".$rumallAccount['store_name']." since $sinceTimeUTC \n"; //ystest
					
					$orders = array();
					$one_page_num = 1;
					do{
					    $newInterface = new RumallOrderHelper();
					    $create_orders = $newInterface->getNewOrder($rumallAccount->company_code, $rumallAccount->token,$one_page_num);//默认pagenum为1
					    	
					    if (empty($create_orders['success'])){
					        echo "\n GetNewSales fail :".$create_orders['message'].",time:".date("Y-m-d H:i:s",time());
					        $rumallAccount->order_retrieve_message = " GetNewSales fail :".$create_orders['message'].",time:".date("Y-m-d H:i:s",time());
					        //\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
					        $rumallAccount->save();
					        return array('success'=>false,'message'=>"uid:".$rumallAccount->uid." cronAutoFetchRecentOrderList GetNewSales Exception:".$create_orders['message']);
					        // 						break;
					    }else if(empty($create_orders['data']['SaleOrders'])){
					        $rumallAccount->order_retrieve_message = "get non order";
					        $rumallAccount->save();
					    }else{
					        $rumallAccount->order_retrieve_message = "";
					        $rumallAccount->save();
					    }
					    	
					    if(!empty($create_orders['data']['SaleOrders']))
					    {
					        if(isset($create_orders['data']['SaleOrders']['SaleOrder'][0])){
					            foreach ($create_orders['data']['SaleOrders']['SaleOrder'] as $key=>$val){
					                $orders[] = $val;
					            }
					        }else{
					            $orders[] = $create_orders['data']['SaleOrders']['SaleOrder'];
					        }
					    }
					    
					    $one_page_num = $one_page_num + 1;
					    
					}while(!empty($create_orders['data']['SaleOrders']));
                    
                    if(!empty($orders)){     
                        $rtn=self::_InsertRumallOrder($orders, $rumallAccount);
                        echo "\n uid = $uid handled orders count ".count($orders)." for ".$rumallAccount['token'];
                        //\Yii::info(['rumall',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled orders count ".count($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])." for ".$rumallAccount['token']],"edb\global");
                         
                        if(!empty($rtn['success'])){
                            $rumallAccount->last_order_success_retrieve_time = $updateTime;
                        }else{
                            $rumallAccount->order_retrieve_message = $rtn['message'];
                        }
                    
                    }else{
                        //没有订单，则视为全部出库
                        $empty_had_out_order = array();
                        $empty_all_queue_record = QueueRumallOrders::find()->where(['uid'=>$rumallAccount['uid'],'store_name'=>$rumallAccount['store_name']])->asArray()->all();
                        if(!empty($empty_all_queue_record)){
                            foreach ($empty_all_queue_record as $empty_value){
                                $empty_had_out_order[] = $empty_value['orderId'];
                            }
                        }
                        if(!empty($empty_had_out_order)){
                            QueueRumallOrders::deleteAll(['orderId'=>$empty_had_out_order]);
                            $empty_new_rumall_order = RumallOrder::find()->where(['ErpOrder'=>$empty_had_out_order])->all();
                            if(!empty($empty_new_rumall_order)){
                                foreach ($empty_new_rumall_order as $new_rumall_order_detail){
                                    $empty_order_history = new RumallOrdersHistory();
                                    $empty_order_history->store_name = $rumallAccount['store_name'];
                                    $empty_order_history->orderId = $new_rumall_order_detail->ErpOrder;
                                    $empty_order_history->order_detail = $new_rumall_order_detail->order_detail;
                                    if(!$empty_order_history->save(false)){
                                        \Yii::error("Fail to insert rumall_orders_history".$rumallAccount['uid']." orderId ".$new_rumall_order_detail->ErpOrder.".","file");
                                    }
                                }
                            }
                            //改变小老板平台订单状态
                            $getChangedRecord = OdOrder::find()->where(['order_source_order_id'=>$empty_had_out_order])->andWhere(['order_source'=>'rumall'])->all();
                            if(!empty($getChangedRecord)){
                                foreach ($getChangedRecord as $v){
                                    $v->order_status = 500;
                                    $v->order_source_status = '已完成';
                                    if(!$v->save(false)){
                                        \Yii::error("Fail to change order_v2 status , orderId ".$getChangedRecord->order_source_order_id.".","file");
                                    }
                                }
                            }
                        }
                        echo "\n get none order";
                        $rumallAccount->last_order_success_retrieve_time = $updateTime;
                    }					

					if (!$rumallAccount->save(false)){
						echo "\n failure to save rumall account info ,error:";
						echo "\n uid:".$rumallAccount['uid']."error:". print_r($rumallAccount->getErrors(),true);
						//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"failure to save rumall operation info ,uid:".$rumallAccount['uid']."error:". print_r($rumallAccount->getErrors(),true)],"edb\global");
					}else{
						echo "\n RumallAccount model save !";
					}	
				}
			}//end of each rumall user account
			return array('success'=>true,'message'=>''); 
		} catch (\Exception $e) {
			echo "\n cronAutoFetchRecentOrderList Exception2:".$e->getMessage();
			return array('success'=>false,'message'=>"cronAutoFetchRecentOrderList Exception:".$e->getMessage());
			//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
			
	}//end of cronAutoFetchUnFulfilledOrderList
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * rumall api 获取的订单数组 保存到订单模块中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $orders				rumall api 返回的结果
	 * @param $rumallAccount		rumall user model 
	 * @param $the_day				job启动当前日期
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 * @description			rumall order  调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl	2014/12/09		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function _InsertRumallOrder($orders , $rumallAccount , $the_day=''){
		//echo "\n YS0.0 Start to insert order "; //ystest
		try {
			//删除v2表里面不存在的原始订单
			$command = Yii::$app->subdb->createCommand("delete  FROM `rumall_order` WHERE `ErpOrder` not in (select order_source_order_id from od_order_v2 where order_source='rumall') ");
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
			$tempRumallOrderModel = new RumallOrder();
			$tempRumallOrderDetailModel = new RumallOrderDetail();
			$RumallOrderModelAttr = $tempRumallOrderModel->getAttributes();
			$RumallOrderDetailModelAttr = $tempRumallOrderDetailModel->getAttributes();
// 			print_r($RumallOrderModelAttr);
// 			print_r($RumallOrderDetailModelAttr);exit();
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
			$insertErrOrder['uid']=$rumallAccount['uid'];
			$insertErrOrder['store_name']=$rumallAccount['store_name'];
			
			//同步商品信息原始数组
			$syncProdInfoData=[];
			//保存获取所有订单信息的所有原始订单号
			$all_original_orderIds = [];
			
			foreach($orders as $anOrder){
// 			    $all_original_orderIds[] = $anOrder['ErpOrder'];
				//echo "\n YS0.1 Start to insert order "; //ystest	
				/*
				if ($anOrder['Status'] == 'Completed') //忽略已完成订单
					continue;
				*/
				$OrderParms = array();
				$orderModel = RumallOrder::find()->where(['ErpOrder'=>$anOrder['ErpOrder']])->one(); 
				$newCreateOrder = false;
				//echo "\n YS0.2-1   "; //ystest
				if (empty($orderModel)){
					//new order 
					$orderModel = new RumallOrder();
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
                    
				    //压缩整条订单记录
				    $original_order = $anOrder;
				    $orderInfo['order_detail'] = json_encode($original_order);
				    $orderInfo['order_detail'] = gzcompress($orderInfo['order_detail'], 9);//压缩级别为9
				    $orderInfo['order_detail'] = base64_encode($orderInfo['order_detail']);
				    $orderInfo['order_detail'] = addslashes($orderInfo['order_detail']);
				    
				    
					
					if($key == 'TradeOrderDateTime'||$key == 'PayDateTime'||$key == 'DeliveryDate'){
// 					    $all_time = strtotime($value);
// 					    $orderInfo[$key] = date("Y-m-d H:i:s",$all_time);
					    $orderInfo[$key] = empty($value)?'':$value;
// 					    $anOrder[$key] = $orderInfo[$key];//将xml为空array的都转为
					    continue;
					}
					
					if($key == 'OtherCharge'||$key == 'balance_amount'||$key == 'ActualAmount'||$key == 'OrderDiscount'||$key == 'OrderTotalAmount'||$key == 'Freight'||$key=='DiscountRate'){
					    $orderInfo[$key] = empty($value)?'0.00':$value;
// 					    $anOrder[$key] = $orderInfo[$key];//将xml为空array的都转为
					    continue;
					}
						
// 					if ($key=='transactionArray'){
// 					    if(isset($value['transaction'])){
// 					        foreach($value['transaction'] as $subkey=>$subvalue){
// 					            if ($subkey == 'buyer'){
// 					                $orderInfo["email"] = $subvalue['email'];
// 					            }else{
// 					                $orderInfo[$subkey] = $subvalue;
// 					            }
// 					        }//end of each Customer Detail
// 					    }
// 						continue;
// 					}
				 
					if ($key=='OrderReceiverInfo'){
						foreach($value as $subkey=>$subvalue){
						    $orderInfo[$subkey] = empty($subvalue)?'':$subvalue;
						}//end of each ShippingAddress Detail
						continue;
					}
					
					if ($key=='OrderSenderInfo'){
					    $orderInfo[$key] = json_encode($value);
					    continue;
					}
					
					if (array_key_exists($key, $RumallOrderModelAttr)){
						if(is_array($value)){
							if(!empty($value)) $value=json_encode($value);
							else $value='';
						}
						$orderInfo[$key] = $value;
						continue;
					}
					
					if ($key=='OrderItems'){//item
					    if(!empty($value)){
					        $ItemsArray = array();
					        if(isset($value['OrderItem'][0])){
					            $ItemsArray = $value['OrderItem'];
					        }else{
					            $ItemsArray[] = $value['OrderItem'];
					        }
					        foreach ($ItemsArray as $v_key=>$v){
					            if(!empty($v)){
					                foreach ($v as $subkey=>$subvalue){
					                    if (array_key_exists($subkey, $RumallOrderDetailModelAttr)){
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
// 						if(!empty($value['OrderLine'])){
// 							if(isset($value['OrderLine']['Sku'])){//单个产品情况
// 								foreach($value['OrderLine'] as $subkey=>$subvalue){
// 									$subkey = strtolower($subkey);
// 									if (array_key_exists($subkey, $RumallOrderDetailModelAttr)){
// 										if($subkey=='name' && is_array($subvalue)) continue;//无效prod name
// 										if(is_array($subvalue)){
// 											if(!empty($subvalue)) $subvalue=json_encode($subvalue);
// 											else $subvalue='';
// 										}
// 										$orderDetailInfo[0][$subkey] = $subvalue;
// 									}
// 								}
// 							}else{//订单多个产品
// 								$i=0;
// 								foreach($value['OrderLine'] as $orderline){
// 									foreach($orderline as $subkey=>$subvalue){
// 										$subkey = strtolower($subkey);
// 										if (array_key_exists($subkey, $RumallOrderDetailModelAttr)){
// 											if($subkey=='name' && is_array($subvalue)) continue;//无效prod name
// 											if(is_array($subvalue)){
// 												if(!empty($subvalue)) $subvalue=json_encode($subvalue);
// 												else $subvalue='';
// 											}
// 											$orderDetailInfo[$i][$subkey] = $subvalue;
// 										}
// 									}
// 									$i++;
// 								}
// 							}
// 						}
					}
				}
				//新加字段，记录卖家id liang 2015-12-21
// 				$orderInfo['seller_id'] = $rumallAccount['username'];
				$addinfo = [];
// 				$addinfo['FBC'] = false;
// 				if(isset($anOrder['IsCLogistiqueOrder']) && $anOrder['IsCLogistiqueOrder']!=='false')
// 					$addinfo['FBC'] = true;
				//echo "\n YS0.3   "; //ystest
				if (!empty($orderInfo)){
					$orderModel->setAttributes($orderInfo);
// 					print_r($orderModel->getAttributes());
					echo " \n YS1 try to inset rumall order for ".$orderModel->ErpOrder;
					//SQLHelper::groupInsertToDb($orderModel->tableName(), array($orderModel->getAttributes()));
					if ($orderModel->save(false)){
						echo "\n save rumall order success!";
						if($newCreateOrder)
							$src_insert_success ++;
						else
							$src_update_success ++;
					}else{
						echo "\n failure to save rumall order,uid:".$rumallAccount['uid']."error:".print_r($orderModel->getErrors(),true);
						$rtn['message'].=(empty($orderModel->ErpOrder)?'':$orderModel->ErpOrder)."原始订单保存失败,";
						$rtn['success']=false;
						$insertErrOrder['srcTbale'][]=empty($orderModel->ErpOrder)?'':$orderModel->ErpOrder;
						if($newCreateOrder){
							$src_insert_failed ++;
							$src_insert_failed_happend_site = $rumallAccount['site_id'];
							$err_type['1']['times'] +=1;
							$err_type['1']['site_id']=$rumallAccount['site_id'];
							$err_type['1']['last_msg']="failure to insert rumall order,site_id:".$rumallAccount['site_id']."error:".print_r($orderModel->getErrors(),true);
						}
						else{
							$src_update_failed ++;
							$src_update_failed_happend_site =  $rumallAccount['site_id'];
							$err_type['4']['times'] +=1;
							$err_type['4']['site_id']=$rumallAccount['site_id'];
							$err_type['4']['last_msg']="failure to update rumall order,site_id:".$rumallAccount['site_id']."error:".print_r($orderModel->getErrors(),true);
						}
						continue;
						//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"failure to save rumall order,uid:".$rumallAccount['uid']."error:". print_r($orderModel->getErrors(),true)],"edb\global");
					}
				}else{
					echo 'failure to save rumall order,orderInfo lost!';
					$rtn['message'].="原始订单信息缺失";
					$rtn['success']=false;
					if($newCreateOrder){
						$src_insert_failed++;
						$src_insert_failed_happend_site=$rumallAccount['site_id'];
						$err_type['1']['times'] +=1;
						$err_type['1']['site_id']=$rumallAccount['site_id'];
						$err_type['1']['last_msg']='failure to insert rumall order,orderInfo lost!';
					}
					else{
						$src_update_failed ++;
						$src_update_failed_happend_site =  $rumallAccount['site_id'];
						$err_type['4']['times'] +=1;
						$err_type['4']['site_id']=$rumallAccount['site_id'];
						$err_type['4']['last_msg']='failure to update rumall order,orderInfo lost!';
					}
					
					continue;
				}
			 	//echo "\n YS0.4   "; //ystest
				//save order detail 
				if (!empty($orderDetailInfo)){
					//save order details's prod to offer list table if details have new prod that table hadn't,
					//self::saveOrderDetailToOfferListIfIsNew($orderDetailInfo,$rumallAccount);
// 					$syncProdInfoData[]=$orderDetailInfo;
					
					if ($newCreateOrder){
						foreach ($orderDetailInfo as $aDelail){
						    //防止重复插入detial
						    $orderDetails = RumallOrderDetail::find()->where(['orderID'=>$anOrder['ErpOrder'],'SkuNo'=>$aDelail['SkuNo']])->one();
						    if (empty($orderDetails)){
						        $orderDetails = new RumallOrderDetail();
						    }
							$aDelail['orderID'] = $anOrder['ErpOrder'];
// 							$orderDetails = new RumallOrderDetail();
							$orderDetails->setAttributes ($aDelail);
							if (!$orderDetails->save(false)){
								echo "\n failure to save rumall order details ,uid:".$rumallAccount['uid']."error:". print_r($orderDetails->getErrors(),true);
								$rtn['message'].=$anOrder['ErpOrder']."保存原始订单商品失败;";
								$rtn['success']=false;
								$insertErrOrder['srcTbale'][]=empty($anOrder['ErpOrder'])?'':$anOrder['ErpOrder'];
								
								$src_detail_insert_failed ++;
								$src_detail_insert_failed_happend_site= $rumallAccount['site_id'];
								$err_type['2']['times'] +=1;
								$err_type['2']['site_id']=$rumallAccount['site_id'];
								$err_type['2']['last_msg']='failure to insert rumall order detail ,insert to db failed!';
								
								continue;
							}else{
								echo "\n save rumall order detail success!";
								$src_detail_insert_success ++;
								$syncProdInfoData[]=$orderDetailInfo;
							}
						}
					}else{
						foreach ($orderDetailInfo as $aDelail){
							$orderDetails = RumallOrderDetail::find()->where(['orderID'=>$anOrder['ErpOrder'],'SkuNo'=>$aDelail['SkuNo']])->one();
							if (empty($orderDetails)){
								$orderDetails = new RumallOrderDetail();
							}
							$aDelail['orderID'] = $anOrder['ErpOrder'];
							$orderDetails->setAttributes ($aDelail);
							if (!$orderDetails->save(false)){
								echo "\n failure to save rumall order details ,uid:".$rumallAccount['uid']."error:". print_r($orderDetails->getErrors());
								$rtn['message'].=$anOrder['ErpOrder']."更新原始订单商品失败;";
								$rtn['success']=false;
								$insertErrOrder['srcTbale'][]=empty($anOrder['ErpOrder'])?'':$anOrder['ErpOrder'];
								
								$src_detail_update_failed++;
								$src_detail_update_failed_happend_site=$rumallAccount['site_id'];
								$err_type['5']['times'] +=1;
								$err_type['5']['site_id']=$rumallAccount['site_id'];
								$err_type['5']['last_msg']='failure to update rumall order detail ,update to db failed!';
								
								continue;
							}else{
								echo "\n save rumall order detail success!";
								$src_detail_update_success++;
								$syncProdInfoData[]=$orderDetailInfo;
							}
						}
					}
				}else{
					echo 'failure to save rumall order detail, orderDetailInfo lost!';
					$rtn['message'].="原始订单商品信息丢失;";
					$rtn['success']=false;
					$insertErrOrder['srcTbale'][]=empty($orderModel->ErpOrder)?'':$orderModel->ErpOrder;
					if ($newCreateOrder){
						$src_detail_insert_failed ++;
						$src_detail_insert_failed_happend_site = $rumallAccount['site_id'];
						$err_type['2']['times'] +=1;
						$err_type['2']['site_id']=$rumallAccount['site_id'];
						$err_type['2']['last_msg']='failure to insert rumall order detail, detail info lost!';
					}
					else{
						$src_detail_update_failed++;
						$src_detail_update_failed_happend_site = $rumallAccount['site_id'];
						$err_type['5']['times'] +=1;
						$err_type['5']['site_id']=$rumallAccount['site_id'];
						$err_type['5']['last_msg']='failure to update rumall order detail, detail info lost!';
					}
					continue;
				}

				//format Order Data
				$anOrder['selleruserid'] = $rumallAccount['company_code'];
				$anOrder['saas_platform_user_id'] = $rumallAccount['site_id'];
				
				//format import order data
				//echo "\n YS0.5 start to formated order data";
				$formated_order_data = self::_formatImportOrderData( $anOrder , $rumallAccount, $getEmail=true);
				$formated_order_detail_data = self::_formatImportOrderItemsData( $anOrder['OrderItems'],$anOrder['ErpOrder'],$anOrder['order_url']);
			
				/*面向不确定类型的用户，需要屏蔽此判断
				if(floatval($total_amount)<=2)//cd有时会生成一些价格低于2欧的问题订单，需要忽略
					continue;
				*/
				if(empty($formated_order_data['shipping_cost']))
					$formated_order_data['shipping_cost']=0;
				
				if(empty($formated_order_data['discount_amount']))
				    $formated_order_data['discount_amount']=0;
				
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
/**************** cdisciunt have not to need to do this**********************
			//	Step 1: save this order to eagle OMS 1.0, and get the record ID
 
				$importOrderResult = self::_saveRumallOrderToOldEagle1($formated_order_data);
				//echo "\n YS0.6 end of _saveRumallOrderToOldEagle1";
				if ($importOrderResult['success']==="fail"){
				 	$message = "Call Eagle1 order insert api fails.  error:".$importOrderResult['message'];
					//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',$message,"edb\global"]);
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
					//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',$message,"edb\global"]);
					if (!empty($rtn['message']))
						$rtn['message'] .="<br>";
						
					$rtn['message'] .= $message;
				}
				
				$eagleOrderRecordId=$resultInfo["eagle_order_id"];
				//echo " eagleOrderId:$eagleOrderRecordId start to duplicate it to eagle 2 oms \n";
*/
				//echo "\n YS0.7 start to _saveRumallOrderToEagle";
				//	Step 2: save this order to eagle OMS 2.0, using the same record ID	
				$formated_order_data['items']=$formated_order_detail_data['items'];
				$importOrderResult=self::_saveRumallOrderToEagle($formated_order_data,$eagleOrderRecordId=-1);
				if (!isset($importOrderResult['success']) or $importOrderResult['success']==1){
					echo "\n failure insert an order to oms 2,result:";
					print_r($importOrderResult);
					$insertErrOrder['omsTbale'][]=empty($anOrder['ErpOrder'])?'':$anOrder['ErpOrder'];
					$rtn['message'].=empty($anOrder['ErpOrder'])?'':$anOrder['ErpOrder']."订单保存到oms失败;";
					$addinfo['oms_auto_inserted'] = 0;
					$addinfo['errors'] = '订单保存到oms失败';
					$rtn['success']=false;
					
					if($newCreateOrder){
						$oms_insert_failed ++;
						$oms_insert_failed_happend_site =  $rumallAccount['site_id'];
						$err_type['3']['times'] +=1;
						$err_type['3']['site_id']=$rumallAccount['site_id'];
						$err_type['3']['last_msg']='failure to insert rumall order to oms!';
					}else{
						$oms_update_failed ++;
						$oms_update_failed_happend_site =  $rumallAccount['site_id'];
						$err_type['3']['times'] +=1;
						$err_type['3']['site_id']=$rumallAccount['site_id'];
						$err_type['3']['last_msg']='failure to update rumall order to oms!';
					}
					continue;
				}else{
					$addinfo['oms_auto_inserted'] = 1;
					echo "\n Success insert an order to oms 2.";
					//when oms inster or update order don't checkorderstatus, do it in cd helper
					//丰卖网在保存到OMS成功时需要将订单号保存到到queue_rumall_orders表中
					$queue_order = QueueRumallOrders::find()->where(['orderId'=>$formated_order_data['order_source_order_id'],'uid'=>$rumallAccount['uid'],'store_name'=>$rumallAccount['store_name']])->one();
					if(empty($queue_order)){//没有记录的时候插入一条记录
					  $new_queue_order = new QueueRumallOrders();
					  $new_queue_order->orderId = $formated_order_data['order_source_order_id'];
					  $new_queue_order->uid = $rumallAccount['uid'];
					  $new_queue_order->store_name = $rumallAccount['store_name'];
					  if(!$new_queue_order->save(false)){
					      echo "\n Fail insert an order to queue_rumall_orders, order_source_order_id: ".$formated_order_data['order_source_order_id'];
					  }else{
					      echo "\n Success insert an order to queue_rumall_orders, order_source_order_id: ".$formated_order_data['order_source_order_id'];
					  }
					}
					//保存获取所有订单信息的所有原始订单号
					$all_original_orderIds[] = $formated_order_data['order_source_order_id'];
					
					$order = OdOrder::find()->where(['order_source_order_id'=>$formated_order_data['order_source_order_id']])->one();
					if($newCreateOrder && !empty($order)){
						$oms_insert_success ++;
					}
					if($newCreateOrder && empty($order) && !isset($importOrderResult['message'])){
						echo "\n error:oms insert success but order model not find!";
						$oms_insert_failed ++;
						$oms_insert_failed_happend_site =  $rumallAccount['site_id'];
					}
					if($newCreateOrder && $order==null && isset($importOrderResult['message'])){
						if( stripos($importOrderResult['message'], 'E009')===false){
							$oms_insert_failed ++;
							$oms_insert_failed_happend_site =  $rumallAccount['site_id'];
						}else{
							$oms_insert_success ++;//合并过的订单
						}
					}
					if(!$newCreateOrder){
						if(empty($order)){
							$oms_insert_failed ++;
							$oms_insert_failed_happend_site =  $rumallAccount['site_id'];
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
			//丰卖网第二步比较总表，缺少的当是非未出库
			$all_queue_record = QueueRumallOrders::find()->where(['uid'=>$rumallAccount['uid'],'store_name'=>$rumallAccount['store_name']])->asArray()->all();
			$had_out_order = array();//非出库订单号
			if(!empty($all_queue_record)){
			    foreach ($all_queue_record as $queue_detail){
			        if(!in_array($queue_detail['orderId'],$all_original_orderIds)){//假如总订单表中的订单不在本次获取的订单中，视为非未出库
			            $had_out_order[] = $queue_detail['orderId'];
			        }
			    }
			}
			
			if(!empty($had_out_order)){//将改变状态的订单从总表删除，加入历史订单
			    QueueRumallOrders::deleteAll(['orderId'=>$had_out_order]);
			    $new_rumall_order = RumallOrder::find()->where(['ErpOrder'=>$had_out_order])->all();
			    if(!empty($new_rumall_order)){
			        foreach ($new_rumall_order as $new_rumall_order_detail){
			            $order_history = new RumallOrdersHistory();
			            $order_history->store_name = $rumallAccount['store_name'];
			            $order_history->orderId = $new_rumall_order_detail->ErpOrder;
			            $order_history->order_detail = $new_rumall_order_detail->order_detail;
			            if(!$order_history->save(false)){
			                \Yii::error("Fail to insert rumall_orders_history".$rumallAccount['uid']." orderId ".$new_rumall_order_detail->ErpOrder.".","file");
			            }
			        }
			    }
			    //改变小老板平台订单状态
			    $getChangedRecord = OdOrder::find()->where(['order_source_order_id'=>$had_out_order])->andWhere(['order_source'=>'rumall'])->all();
			    if(!empty($getChangedRecord)){
			        foreach ($getChangedRecord as $v){
			            $v->order_status = 500;
			            $v->order_source_status = '已完成';
			            if(!$v->save(false)){
			                \Yii::error("Fail to change order_v2 status , orderId ".$getChangedRecord->order_source_order_id.".","file");
			            }
			        }
			    }
			    
			}
			
			//sync Product info from offer list
// 			if(!empty($syncProdInfoData))
// 				self::saveOrderDetailToOfferListIfIsNew($syncProdInfoData, $rumallAccount);
			
			if(!empty($insertErrOrder['srcTbale']) || !empty($insertErrOrder['omsTbale'])){
				\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',date('Y-m-d H:i:s')." CdInsertOrderError:".json_encode($insertErrOrder) ],"edb\global");
			}
			
// 			echo "\n Start to calculateSalesWithin15Days: ";
// 			$nowTimeStr = TimeUtil::getNow();
// 			self::calculateSalesWithin15Days($rumallAccount['store_name'], $rumallAccount['username'],$nowTimeStr);
			
			//set dash-broad log
			if(empty($the_day))
				$the_day=date("Y-m-d",time());
			if(empty($src_insert_success) && empty($src_detail_insert_success) && empty($src_detail_insert_failed) && 
			   empty($src_update_success) && empty($src_update_failed) && empty($src_detail_update_success) && 
			   empty($src_detail_update_failed) ) {
				//无insert无update,则不记录
				echo "\n no insert and on update! \n";
			}else{
				$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='RMOMS' and info_type='orders' and `the_day`='".$the_day."' "  );
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

					$command = Yii::$app->db_queue->createCommand("update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`addinfo`='".json_encode($order_count)."',`addinfo2`='".json_encode($failed_happend_site)."'  where app='RMOMS' and info_type='orders' and `the_day`='".$the_day."' "  );
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
					
					$command = Yii::$app->db_queue->createCommand("INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('RMOMS','orders','normal','','".json_encode($order_count)."','".json_encode($failed_happend_site)."','".$the_day."','".date("Y-m-d H:i:s",time())."')"  );
					$affect_record = $command->execute();
				}//set runtime log end
				
				$need_mark_log = false;
				foreach ($err_type as $code=>$v){
					if($v['times']!==0)
						$need_mark_log=true;
				}
				if($need_mark_log){
					$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='RMOMS' and info_type='err_type' and `the_day`='".$the_day."' "  );
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
						$command = Yii::$app->db_queue->createCommand("update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`addinfo`='".json_encode($err_type_info)."',`addinfo2`=' '  where app='RMOMS' and info_type='err_type' and `the_day`='".$the_day."' "  );
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
						$command = Yii::$app->db_queue->createCommand("INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('RMOMS','err_type','error','','".json_encode($err_type_info)."',' ','".$the_day."','".date("Y-m-d H:i:s",time())."')"  );
						$affect_record = $command->execute();
					}
				}
			}
			//记录用户订单数
			if(!empty($src_insert_success)){
				$uid = $rumallAccount['uid'];
				$sellerid=$rumallAccount['store_name'];
				$classification = "RumallOms_TempData";
				
				//$temp_count = \Yii::$app->redis->hget($classification,"user_$uid".".".$the_day);
				//$seller_temp_count = \Yii::$app->redis->hget($classification,"user_$uid".".".$sellerid.".".$the_day);
				$temp_count = RedisHelper::RedisGet($classification,"user_$uid".".".$the_day);
				$seller_temp_count = RedisHelper::RedisGet($classification,"user_$uid".".".$sellerid.".".$the_day);
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
			//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"insert rumall order :".$e->getMessage() ],"edb\global");
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
			echo "\n error: ".$rtn['message']." ".$e->getLine();
// 			echo "\n all:".print_r($e,true);
			return $rtn;
		}				
	}//end of _InsertRumallOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * rumall api 获取的数组 赋值到  eagle order 接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $rumallOrderData		rumall 数据
	 * @param $rumallAccount			账号 数据
	 * @param $getEmail					是否需要获取买家email
	 +---------------------------------------------------------------------------------------------
	 * @return		$importOrderData	eagle order 接口 的数组
	 * 									调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015-07-07	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderData($rumallOrderData,$rumallAccount,$getEmail=false){
		$importOrderData = array();
		//$importOrderData['order_id'] = $rumallOrderData['OrderNumber'];
// 		if (!empty($rumallOrderData['checkoutStatus']['status'])&&!empty($rumallOrderData['orderStatus'])){
			/*
			 * 	rumall order state 
			 *  State 'WaitingForShipmentAcceptation' means that the order is waiting to ship.
			 *  State 'Shipped' means that the order has been marked as shipped by you.
			 *  State 'CancelledByCustomer' means that the order has been refunded by customer and should not be fulfilled.
			 *  State 'PaymentInProgress' means that the order is waiting customer to pay
			 *  
		R	 *  eagle order status
			 *  100（未付款）、200（已付款）、300（发货处理中）、400（已发货）、500（已完成）、600（已取消）、50（待接受/拒绝）
			 * */
// 			$RumallStateToEagleStatusMapping = array();
// 			$RumallStateToEagleStatusMapping = [
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
			
// 			$importOrderData['order_status'] = isset(self::$RumallStateToEagleStatusMapping[$rumallOrderData['checkoutStatus']['status']][$rumallOrderData['orderStatus']])?self::$RumallStateToEagleStatusMapping[$rumallOrderData['checkoutStatus']['status']][$rumallOrderData['orderStatus']]:50;
// 			$importOrderData['order_source_status'] = $rumallOrderData['checkoutStatus']['status'].','.$rumallOrderData['orderStatus'];
// 		}

		$importOrderData['order_status'] = 200;
		$importOrderData['order_source_status'] = '未出库';
				
		if (isset($importOrderData['order_status'] )){
			//发货状态：1.已发货；0.未发货 #非已发货状态暂时定义为未发
			$importOrderData['shipping_status'] = (($importOrderData['order_status'] == 500)?1:0); 
		}

		$importOrderData['pay_status'] = ($importOrderData['order_status']<200)?0:1;
		
		$importOrderData['order_source'] = 'rumall';//订单来源
		
		$importOrderData['order_type'] = '';//订单类型
// 		if($rumallOrderData['IsCLogistiqueOrder']!=='false')
// 			$importOrderData['order_type'] = 'FBC';
		
// 		if($importOrderData['order_type']=='FBC'){//如果是FBC订单，则认为是已发货。
// 			$importOrderData['order_status']=500;
// 			$importOrderData['shipping_status']=1;
// 		}
		
// 		if(isset($rumallOrderData['ShippingCode']) && is_string($rumallOrderData['ShippingCode']))
// 			$importOrderData['order_source_shipping_method'] = $rumallOrderData['ShippingCode'];
		
		if (isset($rumallOrderData['ErpOrder'])){
			$importOrderData['order_source_order_id'] = $rumallOrderData['ErpOrder']; //订单来源的订单id
		}

		$importOrderData['order_source_site_id'] = 'RU'; //订单来源平台下的站点,Rumall暂无分站点,只有法国站
		
		
		if (isset($rumallOrderData['selleruserid']))
			$importOrderData['selleruserid'] = $rumallOrderData['selleruserid']; //订单来源平台卖家用户名(下单时候的用户名)

		if (isset($rumallOrderData['saas_platform_user_id']))
			$importOrderData['saas_platform_user_id'] = $rumallOrderData['saas_platform_user_id']; //订单来源平台卖家用户名(下单时候的用户名)
		//$Civility['MR'] = "M.";
		//$Civility['MRS'] = "Mme";
		//$Civility['MISS'] = "Mlle";
		$buyer_name='';
		if (isset($rumallOrderData['BuyerId'])){
			//if(isset($rumallOrderData['Customer']['Civility']) && is_string($rumallOrderData['Customer']['Civility']))
				//$buyer_name.=$Civility[$rumallOrderData['Customer']['Civility']]." ";
				$buyer_name = $rumallOrderData['BuyerId'];
// 			if(isset($rumallOrderData['Customer']['FirstName']) && is_string($rumallOrderData['Customer']['FirstName']))
// 				$buyer_name.=$rumallOrderData['Customer']['FirstName']." ";
// 			if(isset($rumallOrderData['Customer']['LastName']) && is_string($rumallOrderData['Customer']['LastName']))
// 				$buyer_name.=$rumallOrderData['Customer']['LastName'];	
		}
		$importOrderData['source_buyer_user_id'] = $buyer_name; //买家名称
		
		if (isset($rumallOrderData['TradeOrderDateTime']))
			$importOrderData['order_source_create_time'] = strtotime($rumallOrderData['TradeOrderDateTime']); //订单在来源平台的下单时间

// 		if (isset($rumallOrderData['ShippingAddress'])){
// 			//echo "\n src ShippingAddress data:";
// 			//var_dump($rumallOrderData['ShippingAddress']);//liang test,屏蔽后部分订单地址信息转到eagle2订单格式是可能丢失(原因不明)
// 			if(isset($rumallOrderData['ShippingAddress']['Civility']) && is_string($rumallOrderData['ShippingAddress']['Civility']))
// 				$shippingConsignee.=$rumallOrderData['ShippingAddress']['Civility']." ";
// 			if(isset($rumallOrderData['ShippingAddress']['FirstName']) && is_string($rumallOrderData['ShippingAddress']['FirstName']))
// 				$shippingConsignee.=$rumallOrderData['ShippingAddress']['FirstName']." ";
// 			if(isset($rumallOrderData['ShippingAddress']['LastName']) && is_string($rumallOrderData['ShippingAddress']['LastName']))
// 				$shippingConsignee.=$rumallOrderData['ShippingAddress']['LastName']." ";
// 			if(isset($rumallOrderData['ShippingAddress']['Instructions']) && is_string($rumallOrderData['ShippingAddress']['Instructions']))
// 				$shippingConsignee.=$rumallOrderData['ShippingAddress']['Instructions'];
// 		}
		$shippingConsignee = '';
		if(isset($rumallOrderData['OrderReceiverInfo']['ConsigneeFullname'])){
		    $shippingConsignee = $rumallOrderData['OrderReceiverInfo']['ConsigneeFullname'];
		}
		$importOrderData['consignee'] = $shippingConsignee; //收货人

		if (isset($rumallOrderData['OrderReceiverInfo']['ReceiverZipCode']) && is_string($rumallOrderData['OrderReceiverInfo']['ReceiverZipCode']))
			$importOrderData['consignee_postal_code'] = $rumallOrderData['OrderReceiverInfo']['ReceiverZipCode']; //收货人邮编
		
// 		if (isset($rumallOrderData['Customer']['Phone']) && is_string($rumallOrderData['Customer']['Phone']))
// 			$importOrderData['consignee_phone'] =$rumallOrderData['Customer']['Phone']; //收货人电话
		
// 		if (isset($rumallOrderData['Customer']['MobilePhone']) && is_string($rumallOrderData['Customer']['MobilePhone']))
// 			$importOrderData['consignee_mobile'] =$rumallOrderData['Customer']['MobilePhone']; //收货移动电话
        
		$importOrderData['consignee_phone'] = $rumallOrderData['OrderReceiverInfo']['ReceiverPhone'];
		$importOrderData['consignee_mobile'] = $rumallOrderData['OrderReceiverInfo']['ReceiverMobile'];
		
		if (isset($rumallOrderData['OrderReceiverInfo']['ReceiverEmail']) && is_string($rumallOrderData['OrderReceiverInfo']['ReceiverEmail']))
			$importOrderData['consignee_email'] = $rumallOrderData['OrderReceiverInfo']['ReceiverEmail']; //收货人Email
// 		$getEmail_retry = 0;
// 		while (empty($importOrderData['consignee_email']) && $getEmail && $getEmail_retry<2){
// 			$importOrderData['consignee_email'] = RumallOrderInterface::getEmailByOrderID($rumallAccount, $rumallOrderData['OrderNumber']);
// 			$getEmail_retry++;
// 		}
		if(empty($importOrderData['consignee_email']) || !is_string($importOrderData['consignee_email']))
			$importOrderData['consignee_email'] = 'N/A';
		
// 		if (isset($rumallOrderData['ShippingAddress']['CompanyName']) && is_string($rumallOrderData['ShippingAddress']['CompanyName'])){}
// 			$importOrderData['consignee_company'] =$rumallOrderData['ShippingAddress']['CompanyName']; //收货人公司
		if(!empty($rumallOrderData['OrderReceiverInfo']['ReceiverCompany'])){
		    $importOrderData['consignee_company'] = $rumallOrderData['OrderReceiverInfo']['ReceiverCompany'];
		}else{
		    $importOrderData['consignee_company'] = 'N/A';
		}
		
		
		if(!empty($rumallOrderData['OrderReceiverInfo']['CountryCode']) && is_string($rumallOrderData['OrderReceiverInfo']['CountryCode'])){
            $receiver_countryCode = CountryHelper::countryList($rumallOrderData['OrderReceiverInfo']['CountryCode']);
            if($receiver_countryCode == ''){
                if($rumallOrderData['OrderReceiverInfo']['CountryCode'] == 'MOW'){//rumall收件国家代码是MOW，莫斯科
                    $receiver_countryCode = 'RU';
                }else if(!empty($rumallOrderData['OrderReceiverInfo']['ReceiverCountry'])){
                    $receiver_countryCode = $rumallOrderData['OrderReceiverInfo']['ReceiverCountry'];
                }else{
                    $receiver_countryCode = 'N/A';
                }
            }
		    $importOrderData['consignee_country_code'] = $receiver_countryCode;
		}else{
		    $importOrderData['consignee_country_code'] = 'N/A'; //收货人国家代码
		}
		
		if (!empty($rumallOrderData['OrderReceiverInfo']['ReceiverCountry'])){
			$importOrderData['consignee_country'] = $rumallOrderData['OrderReceiverInfo']['ReceiverCountry']; //收货人国家名
			
		}else{
			$importOrderData['consignee_country'] = $rumallOrderData['OrderReceiverInfo']['CountryCode']; //收货人国家名
		}
		
		
		if (isset($rumallOrderData['OrderReceiverInfo']['ReceiverCity']) && is_string($rumallOrderData['OrderReceiverInfo']['ReceiverCity']))
			$importOrderData['consignee_city'] = $rumallOrderData['OrderReceiverInfo']['ReceiverCity']; //收货人城市
		
		if (isset($rumallOrderData['OrderReceiverInfo']['ReceiverProvince']) && is_string($rumallOrderData['OrderReceiverInfo']['ReceiverProvince']))
			$importOrderData['consignee_district'] = $rumallOrderData['OrderReceiverInfo']['ReceiverProvince']; //收货人地区
		
		$importOrderData['consignee_address_line1']='';
// 		if (isset($rumallOrderData['shippingAddress']['street1']) && is_string($rumallOrderData['shippingAddress']['street1']))
		$importOrderData['consignee_address_line1'] = $rumallOrderData['OrderReceiverInfo']['ConsigneeDoorplate'].' '.$rumallOrderData['OrderReceiverInfo']['ConsigneeStreet'].' '.$rumallOrderData['OrderReceiverInfo']['ReceiverAddress'].' '.$rumallOrderData['OrderReceiverInfo']['ReceiverArea']; //收货人地址1
		//Apartment
// 		if (isset($rumallOrderData['ShippingAddress']['ApartmentNumber']) and is_string($rumallOrderData['ShippingAddress']['ApartmentNumber']) ){
// 			if(stripos($rumallOrderData['ShippingAddress']['ApartmentNumber'],"apt")===false && stripos($rumallOrderData['ShippingAddress']['ApartmentNumber'],"app")===false && stripos($rumallOrderData['ShippingAddress']['ApartmentNumber'],"Apartment")===false)
// 				$Apartment = "Apt.".$rumallOrderData['ShippingAddress']['ApartmentNumber'];
// 			else
// 				$Apartment = $rumallOrderData['ShippingAddress']['ApartmentNumber'];
				
// 			if(empty($importOrderData['consignee_address_line1']))
// 				$importOrderData['consignee_address_line1'] = "Apt.".$rumallOrderData['ShippingAddress']['ApartmentNumber'];
// 			else
// 				$importOrderData['consignee_address_line1'] = $Apartment.";".$importOrderData['consignee_address_line1'];
// 		}
		//Building
// 		if(isset($rumallOrderData['ShippingAddress']['Building']) && is_string($rumallOrderData['ShippingAddress']['Building'])){
// 			if(stripos($rumallOrderData['ShippingAddress']['Building'],"bât")===false && stripos($rumallOrderData['ShippingAddress']['Building'],"BTMENT")===false && stripos($rumallOrderData['ShippingAddress']['Building'],"Bâtiment")===false)
// 				$Btment = "bât.".$rumallOrderData['ShippingAddress']['Building'];
// 			else
// 				$Btment = $rumallOrderData['ShippingAddress']['Building'];

// 			if(empty($importOrderData['consignee_address_line1']))
// 				$importOrderData['consignee_address_line1'] = $Btment;
// 			else
// 				$importOrderData['consignee_address_line1'] = $Btment.';'.$importOrderData['consignee_address_line1'];
// 		}
		
		//echo "\n fianl consignee_address_line1 is:".$importOrderData['consignee_address_line1'];//liang test
// 		if (isset($rumallOrderData['shippingAddress']['street2']))
		$importOrderData['consignee_address_line2'] = ''; //收货人地址2
		
		
		if (isset($rumallOrderData['PayDateTime'] ))
			$importOrderData['paid_time'] = strtotime($rumallOrderData['PayDateTime']); //订单付款时间

		if(!empty($rumallOrderData['OrderTotalAmount']))
			$importOrderData['total_amount'] = $rumallOrderData['OrderTotalAmount'];
			
// 		if(!empty($rumallOrderData['Freight']))
// 			$importOrderData['shipping_cost'] = $rumallOrderData['Freight'];
		
		if (isset($rumallOrderData['SiteCommissionValidatedAmount'] ))
			$importOrderData['commission_total'] = $rumallOrderData['SiteCommissionValidatedAmount']; //平台佣金
		else
			$importOrderData['commission_total'] = 0;
		
		//yzq 20151220, 因为外面的其他地方还是会读取这个field，还需要这个field应付外面的读取，否则会出错
// 		$importOrderData['discount_amount'] = $importOrderData['commission_total']; //
		
		//if (isset($importOrderData['subtotal']) && isset($importOrderData['shipping_cost']) && isset($importOrderData['discount_amount']))
		//	$importOrderData['grand_total'] = $importOrderData['subtotal'] + $importOrderData['shipping_cost'] - $importOrderData['discount_amount']; //合计金额(产品总价格 + 运费 - 折扣 = 合计金额)
		$importOrderData['currency'] = $rumallOrderData['CurrencyCode']; //货币

		return $importOrderData;
	}//end of _formatImportOrderData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * rumall api 获取的item数组 赋值到  eagle order item接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access 		static
	 +---------------------------------------------------------------------------------------------
	 * @param 		$rumallOrderLine		rumall 数据
	 +---------------------------------------------------------------------------------------------
	 * @return		$importOrderItems		eagle order item接口 的数组
	 * 										调用eagle order item接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015-07-07	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderItemsData($rumallOrderLine,$order_number,$order_url){
		$importOrderItems = array();
		$total_amount=0;
		$delivery_amonut=0;
		$total_qty=0;
		$itemDetail = array();
		if(isset($rumallOrderLine['OrderItem'][0])){
		    $itemDetail = $rumallOrderLine['OrderItem'];
		}else{
		    $itemDetail[] = $rumallOrderLine['OrderItem'];
		}
			foreach ($itemDetail as $detail){
			    $orderline = $detail;
				$row=array();
				$row['order_source_order_id'] = $order_number;
				
				$row['sku'] = '';
				$row['product_url'] = '';
				
				if(!empty($order_url)){
				    $row['product_url'] = $order_url;
				}
				
				if(isset($orderline['SkuNo']))
					$row['sku'] = $orderline['SkuNo'];
				if(isset($orderline['ItemSpecifications'])){
// 				    $row['source_item_id'] = $orderline['ItemSpecifications'];
// 				    $row['order_source_order_item_id'] = $orderline['ItemSpecifications'];
				    $row['source_item_id'] = empty($orderline['ItemSpecifications'])?$row['sku']:$orderline['ItemSpecifications'];
				    $row['order_source_order_item_id'] = empty($orderline['ItemSpecifications'])?$row['sku']:$orderline['ItemSpecifications'];
				}
				
				if(is_string($orderline['ItemOldName']) && $orderline['ItemOldName']!==''){
					$row['product_name']=$orderline['ItemOldName'];
				}
// 				else{
// 					$srcOffer = RumallOfferList::find()->where(['seller_product_id'=>$row['sku']])->one();
// 					if($srcOffer<>null)
// 						$row['product_name'] = $srcOffer->name;
// 				}
				if(empty($row['product_name'])) $row['product_name']=$row['sku'];
				
// 				$row['order_source_itemid'] = $orderline['ItemSpecifications'];
				$row['order_source_itemid'] = empty($orderline['ItemSpecifications'])?$row['sku']:$orderline['ItemSpecifications'];
				if(!empty($orderline['ItemPrice']))
					$row['price'] = $orderline['ItemPrice'];
				if(isset($orderline['ItemQuantity'])){
				    $row['ordered_quantity'] = $orderline['ItemQuantity'];
				    $row['quantity']=$orderline['ItemQuantity'];
				}
				if(!empty($orderline['ItemImage'])){
				    $row['photo_primary'] = $orderline['ItemImage'];
				}
				//$rumallOrderLine['RowId']=0;

// 				if($orderline['AcceptationState']=='ShippedBySeller'){
// 					$row['sent_quantity']=$orderline['Quantity'];
// 					$row['packed_quantity']=$orderline['Quantity'];
// 				}

				$row['shipping_price'] = 0;

				$row['photo_primary']='';//rumall response none product photo info
				
				// $item['discount_amount'] =  $row['OrderLineList']['OrderLine']['ProductId'];
				$total_amount += $orderline['ItemPrice']*$orderline['ItemQuantity'];
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
	private static function _getAllOrdersSince($rumall_token , $createtime='', $endtime='',$newBinding =false){
		$timeout=240; //s
		echo "\n enter function : _getAllOrdersSince";
		//\Yii::info(['rumall',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $rumall_token"],"edb\global");
		$config = array('tokenid' => $rumall_token);
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
	
		$retInfo=RumallProxyConnectHelper::call_Rumall_api("getOrderList",$get_param,$post_params=array(),$timeout );
		//\Yii::info(['rumall',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $rumall_token"],"edb\global");

		return $retInfo;
	}//end of _getAllChangeOrdersSince
	
	/**
	 * 通过前端手动触发获取CD订单
	 * @param unknown $rumall_token
	 * @param string $createtime
	 * @param string $endtime
	 * @param string $newBinding
	 * @param unknown $state
	 * @return string
	 */
	public static function getOrdersByCondition($rumall_token , $createtime='', $endtime='',$newBinding=false,$state=[]){
		$timeout=240; //s
		echo "\n enter function : _getAllOrdersSince";
		$config = array('tokenid' => $rumall_token);
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
	
		$retInfo=RumallProxyConnectHelper::call_Rumall_api("getOrderList",$get_param,$post_params=array(),$timeout );
		
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
	private static function _getAllUnfulfilledOrdersSince($rumall_token , $createtime='', $endtime=''){
		$timeout=240; //s
		echo "\n enter function : _getAllUnfulfilledOrdersSince";
		//\Yii::info(['rumall',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $rumall_token"],"edb\global");
		$config = array('tokenid' => $rumall_token);
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
		
		$retInfo=RumallProxyConnectHelper::call_Rumall_api("getOrderList",$get_param,$post_params=array(),$timeout );
		//\Yii::info(['rumall',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $rumall_token"],"edb\global");

		//check the return info
		return $retInfo;
	}//end of _getAllChangeOrdersSince
	


	/**
	 * 把Rumall的订单信息header和items 同步到eagle1系统中user_库的od_order和od_order_item。
	 * 这里主要是通过eagle1提供的 http api的方式
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveRumallOrderToOldEagle1($oneOrderReq){
 		return ['success'=>0 , 'responseStr'=>'{"success": 0,"eagle_order_id": -1}'];
		//1. 总的请求信息
		$reqInfo=array();
		$ordersReq=array();
		$ordersReq[]=$oneOrderReq;
		//$uid=$merchantidUidMap[$orderHeaderInfo["merchant_id"]];
		//其中Uid是saas_Rumall_user中的uid，这里为了便于eagle的api找到合适的数据库。
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
	 * 把Rumall的订单信息header和items 同步到eagle系统中user_库的od_order和od_order_item
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveRumallOrderToEagle( $oneOrderReq, $eagleOrderId=-1){
		$result = ['success'=>1,'message'=>''];
		$uid=\Yii::$app->subdb->getCurrentPuid();
		
		$reqInfo[$uid]=array_merge(OrderHelper::$order_demo,$oneOrderReq);
		
		try{
			$result =  OrderHelper::importPlatformOrder($reqInfo,$eagleOrderId);
			//SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'info','data='.json_encode($reqInfo));//test liang
		}catch(\Exception $e){
			$message = "importPlatformOrder fails.  RumallId=$eagleOrderId  Exception error:".$e->getMessage()."data: \n ".print_r($reqInfo,true);
			//\Yii::error(['rumall',__CLASS__,__FUNCTION__,'Background',"Step 1.5a ". $message ],"edb\global");
			echo $message;
			return ['success'=>1,'message'=>$message];
		}
	
		return $result;
	}
	
	/**
	 * set rumall store token,when it's token is null or token was expired
	 * @param	object	$rumallUser
	 * return	array	$rumallUser
	 */
	Public static function setRumallUserToken($rumallUser){
		echo "\n setRumallUserToken 0";
		if(!empty($rumallUser->auth_type)){
			$A_username = $rumallUser->username;
			$A_password = $rumallUser->password;
		}else{
			$A_username = $rumallUser->api_username;
			$A_password = $rumallUser->api_password;
		}
			
		if(!empty($A_username) && !empty($A_password)){
			$one_day_ago = date('Y-m-d H:i:s',strtotime("-1 days"));
			if($one_day_ago >= $rumallUser->token_expired_date){
				$config = array();
				$config['username']=$A_username;
				$config['password']=$A_password;
				$get_param['config'] = json_encode($config);
				$reqInfo=RumallProxyConnectHelper::call_Rumall_api("getTokenID",$get_param,$post_params=array() );
				print_r($reqInfo,true);
				echo "\n setRumallUserToken 0-1";
				if($reqInfo['success']){
					echo "\n setRumallUserToken 1";
					if(!empty($reqInfo['proxyResponse']['success']) && stripos($reqInfo['proxyResponse']['tokenMessage'],'Object moved to')===false){
						echo "\n setRumallUserToken 2";
						$tokenid = $reqInfo['proxyResponse']['tokenMessage'];
						$rumallUser->token = $tokenid;
						$rumallUser->token_expired_date = date('Y-m-d H:i:s');
						$rumallUser->update_time = date('Y-m-d H:i:s');

						if(!$rumallUser->save()){
							print_r($rumallUser->getErrors(),true);
						}
					}else{
						echo "\n".$reqInfo['proxyResponse']['message'];
						$rumallUser->order_retrieve_message = "token同步失败,最后尝试同步时间为".date('Y-m-d H:i:s');
						if($rumallUser->save()){
							print_r($rumallUser->getErrors(),true);
						}
						echo "\n ".print_r($reqInfo);
					}
				}
				else{
					echo "\n".$reqInfo['message'];
				}
			}
		}
		return $rumallUser;
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
// 		echo "\n enter RumallOrderHelper function : getOrderClaimList";
		
// 		$rumallAccount = SaasRumallUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
// 		$rumall_token = $rumallAccount['token'];
		$rumall_token = $token;
		$config = array('tokenid' => $rumall_token);
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
		
		$rtn=RumallProxyConnectHelper::call_Rumall_api("GetOrderClaimList",$get_param,$post_params=array(),$timeout );
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
		echo "\n enter RumallOrderHelper function : getOrderQuestionList";
	
		$rumallAccount = SaasRumallUser::find()->where(["uid"=>$uid])->asArray()->one();
		$rumall_token = $rumallAccount['token'];
	
		$config = array('tokenid' => $rumall_token);
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
	
		$rtn=RumallProxyConnectHelper::call_Rumall_api("GetOrderQuestionList",$get_param,$post_params=array(),$timeout );
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
		echo "\n enter RumallOrderHelper function : generateDiscussionMailGuid,orderid=$orderId";
	
		$rumallAccount = SaasRumallUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($rumallAccount)){
			$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$rumall_token = $rumallAccount['token'];
	
		$config = array('tokenid' => $rumall_token);
		$get_param['config'] = json_encode($config);
		
		$params['orderid']= $orderId;
		$get_param['query_params'] = json_encode($params);
		$rtn=RumallProxyConnectHelper::call_Rumall_api("generateDiscussionMailGuid",$get_param,$post_params=array(),$timeout );
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
		echo "\n enter RumallOrderHelper function : getDiscussionMailList,orderid=$orderId";
	
		$rumallAccount = SaasRumallUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($rumallAccount)){
			$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$rumall_token = $rumallAccount['token'];
	
		$config = array('tokenid' => $rumall_token);
		$get_param['config'] = json_encode($config);
		
		//try to get order's claim, retraction, questions ids
	
	
		//
		$discussionIds = '12487210';
		$params['discussionIds'][]= $discussionIds;
		$get_param['query_params'] = json_encode($params);
		$rtn=RumallProxyConnectHelper::call_Rumall_api("getDiscussionMailList",$get_param,$post_params=array(),$timeout );
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
		echo "\n enter RumallOrderHelper function : AccepteOrRefuseOrders ,uid=$uid ,storeName=$storeName;";
		echo "\n orderId=".print_r($orderIds);
		
		$rumallAccount = SaasRumallUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($rumallAccount)){
		$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$rumall_token = $rumallAccount['token'];
		
		$config = array('tokenid' => $rumall_token);
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
		$rtn=RumallProxyConnectHelper::call_Rumall_api("AccepteOrRefuseOrders",$get_param,$post_params=array(),$timeout );
		echo "\n getDiscussionMailList return: \n";
		
		//print_r($rtn);

		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Calculate sales within 15 days by one rumall account.
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
		
		$calculateSales = ConfigHelper::getConfig("RumallOrder/CalculateSalesDate",'NO_CACHE');
		
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
					$offer = RumallOfferList::find()
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
				$resetCount = RumallOfferList::updateAll(['last_15_days_sold'=>0],"id not in ($id_str)");
				echo "\n update have no sold in 15 days prods,count=$resetCount";
			}
		
			$calculateSalesInfo[$user_name] = TimeUtil::getNow();
			ConfigHelper::setConfig("RumallOrder/CalculateSalesDate",json_encode($calculateSalesInfo));
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
	 * @param 	$rumallAccount		array
	 +---------------------------------------------------------------------------------------------
	 * @return	createRecordCount		int
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/11			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function saveOrderDetailToOfferListIfIsNew($orderDetailInfo,$rumallAccount){
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
		        }else{//无sku
		            $item_id_arr[] = $detail['itemID'];
		        }
		    }
		}
		//echo "<br>product_sku_list:<br>";
		//print_r($product_sku_list);
		if(!empty($item_id_arr))
			RumallOfferSyncHelper::syncProdInfoWhenGetOrderDetail($rumallAccount['uid'],$rumallAccount['store_name'], $sku_arr=[], $item_id_arr,2,$type='itemID');
		
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
// 				$offerList = RumallProductList::find()->select(['barcode'])
// 					->where(['barcode'=>$prod_sku_arr])
// 					->andWhere(['seller_id'=>$rumallAccount['username']])
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
// 							'seller_id'=>$rumallAccount['username'],
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
// 				RumallOfferSyncHelper::syncProdInfoWhenGetOrderDetail($rumallAccount['uid'],$rumallAccount['username'], $syn_sku_list, $syn_itemid_list, 2, $type='sku');
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
	public static function formatSrcOrderDataToOmsOrder($rumallOrderData,$rumallAccount,$getEmail=false){
		$importOrderData = array();
	
		//$importOrderData['order_id'] = $rumallOrderData['OrderNumber'];
		if (!empty($rumallOrderData['orderstate'])){
			/**
			 * 	rumall order state
			 *  State 'WaitingForShipmentAcceptation' means that the order is waiting to ship.
			 *  State 'Shipped' means that the order has been marked as shipped by you.
			 *  State 'CancelledByCustomer' means that the order has been refunded by customer and should not be fulfilled.
			 *  State 'PaymentInProgress' means that the order is waiting customer to pay
			 *
			 *  eagle order status
			 *  100（未付款）、200（已付款）、300（发货处理中）、400（已发货）、500（已完成）、600（已取消）
			 * */
			$RumallStateToEagleStatusMapping = array(
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
			$importOrderData['order_status'] = $RumallStateToEagleStatusMapping[$rumallOrderData['orderstate']];
			$importOrderData['order_source_status'] = $rumallOrderData['orderstate'];
		}
	
		if (isset($importOrderData['order_status'] )){
			//发货状态：1.已发货；0.未发货 #非已发货状态暂时定义为未发
			$importOrderData['shipping_status'] = (($importOrderData['order_status'] == 500)?1:0);
		}
	
		$importOrderData['pay_status'] = ($importOrderData['order_status']<200)?0:1;
	
		$importOrderData['order_source'] = 'rumall';//订单来源
	
		$addinfo = json_decode($rumallOrderData['addinfo'],true);
		
		$importOrderData['order_type'] = '';//订单类型
		if(isset($addinfo['FBC']) && $addinfo['FBC'])
			$importOrderData['order_type'] = 'FBC';
	
		if(isset($addinfo['FBC']) && $addinfo['FBC']){//如果是FBC订单，则认为是已发货。
			$importOrderData['order_status']=500;
			$importOrderData['shipping_status']=1;
		}
	
		if (!empty($rumallOrderData['ordernumber'])){
			$importOrderData['order_source_order_id'] = $rumallOrderData['ordernumber']; //订单来源的订单id
		}
	
		$importOrderData['order_source_site_id'] = 'FR'; //订单来源平台下的站点,Rumall暂无分站点,只有法国站
	
		if (isset($rumallOrderData['selleruserid']))
			$importOrderData['selleruserid'] = $rumallOrderData['selleruserid']; //订单来源平台卖家用户名(下单时候的用户名)
		if (isset($rumallOrderData['saas_platform_user_id']))
			$importOrderData['saas_platform_user_id'] = $rumallOrderData['saas_platform_user_id']; //订单来源平台卖家用户名(下单时候的用户名)
	
		$buyer_name='';
		if (!empty($rumallOrderData['customer_civility']) && !is_array(json_decode($rumallOrderData['customer_civility'],true)))
			$buyer_name.=$rumallOrderData['customer_civility']." ";
		if(!empty($rumallOrderData['customer_firstname']) && !is_array(json_decode($rumallOrderData['customer_firstname'],true)))
			$buyer_name.=$rumallOrderData['customer_firstname']." ";
		if(!empty($rumallOrderData['customer_lastname']) && !is_array(json_decode($rumallOrderData['customer_lastname'],true)))
			$buyer_name.=$rumallOrderData['customer_lastname'];
		
		$importOrderData['source_buyer_user_id'] = $buyer_name; //买家名称
	
		if (!empty($rumallOrderData['creationdate']))
			$importOrderData['order_source_create_time'] = strtotime($rumallOrderData['creationdate']); //订单在来源平台的下单时间
	
		$shippingConsignee = '';
		if(!empty($rumallOrderData['shipping_civility']) && !is_array(json_decode($rumallOrderData['shipping_civility'],true)))
			$shippingConsignee.=$rumallOrderData['shipping_civility']." ";
		if(!empty($rumallOrderData['shipping_firstname']) && !is_array(json_decode($rumallOrderData['shipping_firstname'],true)))
				$shippingConsignee.=$rumallOrderData['shipping_firstname']." ";
		if(isset($rumallOrderData['shipping_lastname']) && !is_array(json_decode($rumallOrderData['shipping_lastname'],true)))
				$shippingConsignee.=$rumallOrderData['shipping_lastname']." ";
		
		$importOrderData['consignee'] = $shippingConsignee; //收货人
	
		if (!empty($rumallOrderData['shipping_zipcode']) && !is_array(json_decode($rumallOrderData['shipping_zipcode'],true)))
			$importOrderData['consignee_postal_code'] = $rumallOrderData['shipping_zipcode']; //收货人邮编
	
		if (isset($rumallOrderData['customer_phone']) && !is_array(json_decode($rumallOrderData['customer_phone'],true)))
			$importOrderData['consignee_phone'] =$rumallOrderData['customer_phone']; //收货人电话
	
		if (isset($rumallOrderData['customer_mobilephone']) && !is_array(json_decode($rumallOrderData['customer_mobilephone'],true)))
			$importOrderData['consignee_mobile'] =$rumallOrderData['customer_mobilephone']; //收货移动电话
	
		if(empty($importOrderData['consignee_email']) && $getEmail)
			$importOrderData['consignee_email'] = RumallOrderInterface::getEmailByOrderID($rumallAccount, $rumallOrderData['orderuumber']);
	
		if($importOrderData['consignee_email']=='')
			unset($importOrderData['consignee_email']);
	
		if (isset($rumallOrderData['shipping_companyname']) && !is_array(json_decode($rumallOrderData['shipping_companyname'],true)))
			$importOrderData['consignee_company'] =$rumallOrderData['shipping_companyname']; //收货人公司
	
		if (!empty($rumallOrderData['shipping_country']) && !is_array(json_decode($rumallOrderData['shipping_country'],true))){
			$importOrderData['consignee_country'] = $rumallOrderData['shipping_country']; //收货人国家名
			$importOrderData['consignee_country_code'] = $rumallOrderData['shipping_country']; //收货人国家代码
		}else{
			$importOrderData['consignee_country'] = 'FR'; //收货人国家名
			$importOrderData['consignee_country_code'] = 'FR'; //收货人国家代码
		}
	
		if (isset($rumallOrderData['shipping_city']) && !is_array(json_decode($rumallOrderData['shipping_city'],true)))
			$importOrderData['consignee_city'] = $rumallOrderData['shipping_city']; //收货人城市
	
		if (isset($rumallOrderData['shipping_placename']) && !is_array(json_decode($rumallOrderData['shipping_placename'],true)))
			$importOrderData['consignee_district'] = $rumallOrderData['shipping_placename']; //收货人地区
	
		$importOrderData['consignee_address_line1']='';
		if (isset($rumallOrderData['shipping_street']) && !is_array(json_decode($rumallOrderData['shipping_street'],true)))
			$importOrderData['consignee_address_line1'] = $rumallOrderData['shipping_street']; //收货人地址1
		//Apartment
		if (isset($rumallOrderData['shipping_apartmentnumber']) and !is_array(json_decode($rumallOrderData['shipping_apartmentnumber'],true))){
			if(stripos($rumallOrderData['shipping_apartmentnumber'],"apt")===false && stripos($rumallOrderData['shipping_apartmentnumber'],"app")===false && stripos($rumallOrderData['shipping_apartmentnumber'],"Apartment")===false)
				$Apartment = "Apt.".$rumallOrderData['shipping_apartmentnumber'];
			else
				$Apartment = $rumallOrderData['shipping_apartmentnumber'];
	
			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = "Apt.".$Apartment;
			else
				$importOrderData['consignee_address_line1'] = $Apartment.";".$importOrderData['consignee_address_line1'];
		}
		//Building
		if(isset($rumallOrderData['shipping_building']) && !is_array(json_decode($rumallOrderData['shipping_building'],true))){
			if(stripos($rumallOrderData['shipping_building'],"bât")===false && stripos($rumallOrderData['shipping_building'],"BTMENT")===false && stripos($rumallOrderData['shipping_building'],"Bâtiment")===false)
				$Btment = "bât.".$rumallOrderData['shipping_building'];
			else
				$Btment = $rumallOrderData['shipping_building'];
	
			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = $Btment;
			else
				$importOrderData['consignee_address_line1'] = $Btment.';'.$importOrderData['consignee_address_line1'];
		}
	
		//echo "\n fianl consignee_address_line1 is:".$importOrderData['consignee_address_line1'];//liang test
		if (isset($rumallOrderData['shipping_address1']) && !is_array(json_decode($rumallOrderData['shipping_address1'],true)))
			$importOrderData['consignee_address_line2'] = $rumallOrderData['shipping_address1']; //收货人地址2
	
		if (isset($rumallOrderData['shipping_address2']) && !is_array(json_decode($rumallOrderData['shipping_address2'],true)))
			$importOrderData['consignee_address_line3'] = $rumallOrderData['shipping_address2'];
	
		if (!empty($importOrderData['order_source_create_time'] ))
			$importOrderData['paid_time'] = $importOrderData['order_source_create_time'] ; //订单付款时间
	
		if(!empty($rumallOrderData['ValidatedTotalAmount']))
			$importOrderData['total_amount'] = $rumallOrderData['ValidatedTotalAmount'];
			
		if(!empty($rumallOrderData['ValidatedTotalShippingCharges']))
			$importOrderData['shipping_cost'] = $rumallOrderData['ValidatedTotalShippingCharges'];
	
		if (isset($rumallOrderData['SiteCommissionValidatedAmount'] ))
			$importOrderData['commission_total'] = $rumallOrderData['SiteCommissionValidatedAmount']; //平台佣金
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
	public static function formatSrcOrderItemsDataToOmsOrder($rumallOrderItems,$order_number){
		$importOrderItems = array();
		$total_amount=0;
		$delivery_amonut=0;
		$total_qty=0;
		
		foreach ($rumallOrderItems as $item){
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
				$srcOffer = RumallOfferList::find()->where(['seller_product_id'=>$row['sku']])->one();
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

			$row['photo_primary']='';//rumall response none product photo info

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
		'sus'=>'Rumall后台状态和小老板状态不同步',//satatus unSync 状态不同步
		'wfs'=>'提交发货或提交物流',//waiting for shipment
		'wfd'=>'交运至物流商',//waiting for dispatch
		'wfss'=>'等待手动标记发货，或物流模块"确认已发货"',//waiting for sing shipped ,or confirm dispatch
		'tuol'=>'物流未上网',//track unOnLine
	];
	
	/**
	 * @description			rumall需要两个接口（获取新订单以及获取修改的订单）才能获取时间段内的所有订单，可以能有重复订单，需要合并
	 * @access static
	 * @param $create_order		新订单
	 *        $modified_order   修改订单
	 * @return	$orders			array
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lwj	2016/4/26	初始化
	 */
	public static function merageRumallOrder($create_order,$modified_order){
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
	 * @description			定期自动检测用户的CD订单，如果检测到订单有可能异常，自动add tag
	 * @access static
	 * @param $job_id		job action number
	 * @return				array
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015/12/25	初始化
	 */
	public static function cronAutoAddTagToRumallOrder($job_id){
		try {
			//set runtime log
			$the_day=date("Y-m-d",time());
			$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='RMOMS' and info_type='runtime-CheckOrder' and `addinfo`='job_id=".$job_id."' "  );
			$record = $command->queryOne();
			if(!empty($record)){
				$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`the_day`='".$the_day."'  where app='RMOMS' and info_type='runtime-CheckOrder' and `addinfo`='job_id=".$job_id."' ";
				$command = Yii::$app->db_queue->createCommand( $sql );
				$affect_record = $command->execute();
			}else{
				$sql = "INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('RMOMS','runtime-CheckOrder','normal','','job_id=".$job_id."','','".$the_day."','".date("Y-m-d H:i:s",time())."')";
				$command = Yii::$app->db_queue->createCommand( $sql );
				$affect_record = $command->execute();
			}//set runtime log end
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
	
			$two_days_ago = strtotime('-2 days');
			$five_days_ago = strtotime('-5 days');
	
// 			$saasRumallUserList = SaasRumallUser::find()->select(" distinct `uid` ")->where("is_active='1' and initial_fetched_changed_order_since is not null")
// 				->andwhere(" uid=$job_id ")->orderBy("last_order_success_retrieve_time asc")->asArray()->all();
			$saasRumallUserList = SaasRumallUser::find()->select(" distinct `uid` ")->where("is_active='1' and initial_fetched_changed_order_since is not null")
			->orderBy("last_order_success_retrieve_time asc")->asArray()->all();
			foreach($saasRumallUserList as $rumallAccount ){
				$uid = $rumallAccount['uid'];
				if (empty($uid)){
					//异常情况
					$message = "site id :".$rumallAccount['site_id']." uid:0";
					echo "\n ".$message;
					continue;
				}
 
	
				$updateTime =  TimeUtil::getNow();
				
				//删除已完成订单的weird_status
				OdOrder::updateAll(['weird_status'=>'']," order_status in (500,600) and (weird_status is not null or weird_status<>'') and order_source='rumall' ");
				echo "\n cleared all complete order's weird_status";
				
				$ot_orders = OdOrder::find()
					->where(['order_source'=>'rumall'])
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
						OdOrder::updateAll(['weird_status'=>$s],['order_source'=>'rumall','order_id'=>$id_arr]);
					}
				}
			}//end of each rumall user uid
		} catch (\Exception $e) {
			echo "\n cronAutoFetchRecentOrderList Exception1:".$e->getMessage();
		}
	}//end of cronAutoFetchUnFulfilledOrderList
	
	public function getNewOrder($CompanyCode,$Checkword,$PageNum = 1){
	    if(empty($CompanyCode)||empty($Checkword)){
	        return array("success"=>false,"message"=>"货主代码或Checkword为空，获取订单失败","data"=>"");
	    }else{
	       try{
    	        $client = new \SoapClient(self::$getOrderUrl);
    	         
    	        $request_xml2="<RumallRequest>
    		<Checkword>".$Checkword."</Checkword>
    		<CompanyCode>".$CompanyCode."</CompanyCode>
    		<PageNum>".$PageNum."</PageNum>
    </RumallRequest>";
    	        $response = $client->getOrders($request_xml2);
    	        $xml = simplexml_load_string($response,'SimpleXMLElement', LIBXML_NOCDATA);
    	        $xml_to_array = self::obj2ar($xml);
	            if(isset($xml_to_array['Head'])&&$xml_to_array['Head'] == 'ERROR'){
	                if(isset($xml_to_array['Remark'])){
	                    return array("success"=>false,"message"=>$xml_to_array['Remark'],"data"=>"");
	                }else{
	                    return array("success"=>false,"message"=>'绑定的货主代码、Checkword有误，绑定失败',"data"=>"");
	                }
	            }else{
	                return array("success"=>true,"message"=>"","data"=>$xml_to_array);
	            }
    	        
	       }catch (\Exception $e) {
	            echo "<br>get Rumall New order :".$e->getMessage();
	       }
	        
	    }
	}
	
	public static function shipOrder($carrier='',\eagle\modules\order\models\OdOrder $orderRecord,$params1=[]){
	    try{
	        //
// 	        print_r($orderRecord);
	        $uid = \Yii::$app->subdb->getCurrentPuid();
 
	        $rumallUser = SaasRumallUser::find()->where(['uid'=>$uid,'company_code'=>$orderRecord->selleruserid])->one();
	        $rumallOrderRecord = RumallOrder::find()->where(['ErpOrder'=>$orderRecord->order_source_order_id])->one();
	        
	        if(empty($rumallUser)){
	            return array("success"=>false,"message"=>"没有找到相关用户","data"=>'');
	        }
	        $params = [
	          'SingKey'=>$rumallUser->company_code,
	          'CheckWord'=>$rumallUser->token,
	          'SenderInfo'=>$rumallOrderRecord->OrderSenderInfo,
	          'TradeOrderNum'=>$params1['customer_orderid'],
	          'DeclaredValue1'=>$params1['DeclaredValue1'],
	          'DeclaredValue2'=>$params1['DeclaredValue1'],
	        ];
	        if(empty($params['SingKey'])||empty($params['SenderInfo'])||empty($params['TradeOrderNum'])){
	            return array("success"=>false,"message"=>"检验码、发件人信息、平台交易号不能为空","data"=>'');
	        }
	        if(empty($orderRecord)){
	            return array("success"=>false,"message"=>"订单信息不能为空,数据出错","data"=>'');
	        }
	        
	        $senderInfo = json_decode($params['SenderInfo'],true);
	        
	        $client = new \SoapClient(self::$getOrderUrl);
	    
	        $request_xml2="<Request>
<Head>
    <SingKey>".$params['SingKey']."</SingKey>
    <CheckWord>".$params['CheckWord']."</CheckWord>
    <OrderNum>".$params['TradeOrderNum']."</OrderNum>
    <TradeOrderNum>".$orderRecord->order_source_order_id."</TradeOrderNum>
    <LogisticsType>".$carrier."</LogisticsType>
    <DeclaredValue1>".$params['DeclaredValue1']."</DeclaredValue1>
    <DeclaredValue2>".$params['DeclaredValue1']."</DeclaredValue2>
</Head>
<OrderSenderInfo>
    <SenderCompany>".$senderInfo['SenderCompany']."</SenderCompany>
    <SenderName>".$senderInfo['SenderName']."</SenderName>
    <SenderEmail>".$senderInfo['SenderEmail']."</SenderEmail>
    <SenderZipCode>".$senderInfo['SenderZipCode']."</SenderZipCode>
    <SenderMobile>".$senderInfo['SenderMobile']."</SenderMobile>
    <SenderPhone>".$senderInfo['SenderPhone']."</SenderPhone>
    <SenderProvince>".$senderInfo['SenderProvince']."</SenderProvince>
    <SenderCity>".$senderInfo['SenderCity']."</SenderCity>
    <SenderAddress>".$senderInfo['SenderAddress']."</SenderAddress>
</OrderSenderInfo>
<OrderReceiverInfo>
    <ReceiverCompany>".$orderRecord->consignee_company."</ReceiverCompany>
    <ReceiverName>".$orderRecord->consignee."</ReceiverName>
    <ReceiverPhone>".$orderRecord->consignee_phone."</ReceiverPhone>
    <ReceiverMobile>".$orderRecord->consignee_mobile."</ReceiverMobile>
    <ReceiverEmail>".$orderRecord->consignee_email."</ReceiverEmail>
    <ReceiverZipCode>".$orderRecord->consignee_postal_code."</ReceiverZipCode>
    <ReceiverProvince>".$orderRecord->consignee_province."</ReceiverProvince>
    <ReceiverCity>".$orderRecord->consignee_city."</ReceiverCity>
    <ReceiverAddress>".$orderRecord->consignee_address_line1."</ReceiverAddress>
</OrderReceiverInfo>
</Request>";
	        $response = $client->confirmSenderInfo($request_xml2);
	        $xml = simplexml_load_string($response,'SimpleXMLElement', LIBXML_NOCDATA);
	        $myself = new RumallOrderHelper();
	        $xml_to_array = $myself->obj2ar($xml);
// 	        print_r($xml_to_array);
	        if(isset($xml_to_array['Head'])&&$xml_to_array['Head'] == 'ERROR'){
	            if(isset($xml_to_array['Remark'])){
	                return array("success"=>false,"message"=>$xml_to_array['Remark'],"data"=>"");
	            }else if(isset($xml_to_array['Error'])){
	                return array("success"=>false,"message"=>$xml_to_array['Error'],"data"=>"");
	            }else{
	                return array("success"=>false,"message"=>'绑定的货主代码、Checkword有误，绑定失败',"data"=>"");
	            }
	        }else if(isset($xml_to_array['Head'])&&$xml_to_array['Head'] == 'OK'){
	            return array("success"=>true,"message"=>"","data"=>$xml_to_array);
	        }else if(isset($xml_to_array['Head'])&&$xml_to_array['Head'] == 'ERR'){
	            if(isset($xml_to_array['Remark'])){
	                return array("success"=>false,"message"=>$xml_to_array['Remark'],"data"=>"");
	            }else if(isset($xml_to_array['Error'])){
	                return array("success"=>false,"message"=>$xml_to_array['Error'],"data"=>"");
	            }else {
	                return array("success"=>false,"message"=>'rumall ship 接口未知错误',"data"=>$xml_to_array);
	            }
	        }else{
	            return array("success"=>false,"message"=>'rumall ship 接口未知错误',"data"=>$xml_to_array);
	        }
	         
	    }catch (\Exception $e) {
	        echo "<br>fail ship Rumall order :".$e->getMessage();
	    }
	}
	
	public function obj2ar($obj) {
	    if(is_object($obj)) {
	        $obj = (array)$obj;
	        $obj = self::obj2ar($obj);
	    } elseif(is_array($obj)) {
	        foreach($obj as $key => $value) {
	            $aa = self::obj2ar($value);
	            if(empty($aa)){
	                $obj[$key] = '';
	            }else{
	                $obj[$key] = self::obj2ar($value);
	            }
// 	            $obj[$key] = self::obj2ar($value);
	        }
	    }
	    return $obj;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 rumall dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid		uid
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					RumallOrderHelper::getOmsDashBoardCache($uid);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/07/22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOmsDashBoardCache($uid){
	    $platform = 'rumall';
	
	    //$cacheData = \Yii::$app->redis->hget(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
		$cacheData = RedisHelper::RedisGet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
	    if (!empty($cacheData))
	        return json_decode($cacheData,true);
	    else
	        return[];
	
	}//end of getOmsDashBoardCache
	
	static public function getRumallCurrentOperationList($code , $type="s"){
	    $OpList = OrderHelper::getCurrentOperationList($code,$type);
	    if (isset($OpList['givefeedback'])) {
	        unset($OpList['givefeedback']);//去掉“给买家好评”
	    }
	    if (isset($OpList['signpayed'])) {
	        unset($OpList['signpayed']);//去掉“标记已付款”
	    }
	    if(isset($OpList['signshipped'])){
	        unset($OpList['signshipped']);//rumall上传物流就相当于标记发货
	    } 
// 	    switch ($code){
// 	        case OdOrder::STATUS_PAY:
// 	            $OpList += [ 'signshipped'=>'虚拟发货(标记发货)'];
// 	            if (isset($OpList['signcomplete'])) {
// 	                unset($OpList['signcomplete']);//去掉“标记为已完成”
// 	            }
// 	            break;
// 	        case OdOrder::STATUS_WAITSEND:
// 	            $OpList += ['extendsBuyerAcceptGoodsTime'=>'延长买家收货时间', 'signshipped'=>'虚拟发货(标记发货)'];
// 	            break;
// 	        case OdOrder::STATUS_SHIPPED:
// 	            $OpList += ['extendsBuyerAcceptGoodsTime'=>'延长买家收货时间' , 'signshipped'=>'虚拟发货(标记发货)'];
// 	            break;
// 	    }
	    if ($type =='s')
	        $OpList += ['invoiced' => '发票'];
	    return $OpList;
	}//end of getRumallCurrentOperationList
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 rumall dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					RumallOrderHelper::UserAliexpressOrderDailySummary($start_time);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/08/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOmsDashBoardData($uid,$isRefresh =false){
	    $platform = 'rumall';
	
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
	        $cacheData['order_count'] = RumallOrderHelper::getChartDataByUid_Order_Rumall($uid, 10);//订单数量统计
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
	
	static public function getChartDataByUid_Order_Rumall($uid ,$days ){
	    //获取所有aliexpress 绑定有效的账号
	    $accounts = SaasRumallUser::find()->select(['uid', 'username'=>'store_name'])->where(['is_active'=>1 ,'uid'=>$uid ])->asArray()->all();
	    $platform = 'rumall';
	    return OrderBackgroundHelper::getChartDataByUid_Order($uid ,$days , $platform, $accounts);
	}//end of UserAliexpressOrderDailySummary
	
	public static function getUserAccountProblems($uid){
	    if(empty($uid))
	        return [];
	
	    $RumallAccounts = SaasRumallUser::find()->where(['uid'=>$uid])->asArray()->all();
	    if(empty($RumallAccounts))
	        return [];
	
	    $accountUnActive = [];//未开启同步的账号
	    $tokenExpired = [];//授权失败的账号
	    $order_retrieve_errors = [];//获取订单失败
	    $initial_order_failed = [];//首次绑定时，获取订单失败
	    foreach ($RumallAccounts as $account){
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
	
	/**
	 * Rumall 的sync表把type=2的unshipped获取任务，重置为0，让系统重新获取所有unshippped的，避免获取new的job有遗漏
	 * 由cron call 起来，会对所有绑定的Rumall账号进行轮询
	 * @access static
	 * @author	yzq	2016/10/19	初始化
	 **/
	static public function activeAllShopToGetUnshippedOrder() {
		// dzt20190522 注释了，rumall修改了 newegg的表，TODO 找到rumall的值修复功能
		// $command = Yii::$app->db->createCommand("update saas_newegg_autosync  SET  `status` =  '0' WHERE  type=2 and  status = 2  "  );
		// $affectRows = $command->execute();
	}
}
