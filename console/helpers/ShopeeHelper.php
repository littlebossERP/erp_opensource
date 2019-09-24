<?php
namespace console\helpers;

use \Yii;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\models\SaasShopeeAutosync;
use eagle\modules\util\helpers\TimeUtil;
use common\api\shopeeinterface\ShopeeInterface_Api;
use common\api\shopeeinterface\ShopeeInterface_Helper;
use eagle\models\QueueShopeeGetorder;
use eagle\models\SaasShopeeUser;

/**
 * +------------------------------------------------------------------------------
 * Shopee 数据同步类
 * +------------------------------------------------------------------------------
 */
class ShopeeHelper{
	public static $cronJobId = 0;
	private static $shopeeGetOrderListVersion = null;
	protected static $active_users;
	
	//判断是否活跃用户，72小时内有操作则是
	public static function isActiveUser($uid){
		if (empty(self::$active_users)) {
			self::$active_users = \eagle\modules\util\helpers\UserLastActionTimeHelper::getPuidArrByInterval(72);
		}
		if (in_array($uid, self::$active_users)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return the $cronJobId
	 */
	public static function getCronJobId(){
		return self::$cronJobId;
	}
	
	/**
	 * @param number $cronJobId
	 */
	public static function setCronJobId($cronJobId){
		self::$cronJobId = $cronJobId;
	}
	
	public static function getTime(){
		return date('Y-m-d H:i:s');
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  同步Shopee新产生 / 有变更的订单 到队列中，等待更新到db，时间从绑定，重新绑定或者重新开启时间开始
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/27		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOrderListByTime(){
		//检查当前job版本，如果和自己的版本不一样，就自动exit
		$currentShopeeGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/shopeeGetOrderListVersion", 'NO_CACHE');
		if(empty($currentShopeeGetOrderListVersion)){
			$currentShopeeGetOrderListVersion = 0;
		}
		//如果自己还没有订单，去使用global config初始
		if(empty(self::$shopeeGetOrderListVersion)){
			self::$shopeeGetOrderListVersion = $currentShopeeGetOrderListVersion;
		}
		//如果版本不一样，则退出
		if(self::$shopeeGetOrderListVersion <>$currentShopeeGetOrderListVersion){
			exit("Version new $currentShopeeGetOrderListVersion , this job ver " . self::$shopeeGetOrderListVersion . " exits for using new version $currentShopeeGetOrderListVersion.");
		}
		
		self::$cronJobId++;
		echo self::getTime().' time script start '.self::$cronJobId.PHP_EOL;
	
		//*************Step 0, 获取需同步的店铺列表，并循环获取
		$connection = Yii::$app->db;
		$nowTime = time();
		$hasGetRecord = false;
		//查询同步表所有item队列，取前五条，并抢记录
		$sql = "select id from saas_shopee_autosync where is_active=1 and status<>1 and times<10 and type='time' and next_time<".$nowTime." order by next_time asc limit 5";
		$dataReader = $connection->createCommand($sql)->query();
		echo self::getTime().' select count '.$dataReader->count().PHP_EOL;
		while(false !== ($row = $dataReader->read())){
			//判断是否可以正常抢到该记录
			$nowTime = time();
			$affectRows = $connection->createCommand("update saas_shopee_autosync set status=1, last_time=".$nowTime." where id=".$row['id']." and status<>1")->execute();
			if($affectRows <= 0){
				continue;   //当条记录抢不到
			}
			$hasGetRecord = true;
			$SAA_obj = SaasShopeeAutosync::findOne($row['id']);
			if(empty($SAA_obj)){
				echo self::getTime().' Exception '.$row['id'].PHP_EOL;
				continue;
			}
			$puid = $SAA_obj->puid;
			$shop_id = $SAA_obj->shop_id;
			$timeMS1 = TimeUtil::getCurrentTimestampMS();
			echo self::getTime().' step 1 puid='.$puid.'shop_id='.$shop_id.PHP_EOL;
			
			//*************Step 1, 检测是否活跃用户，如不活跃，则放到晚上执行新订单的同步，减少白天负载
			if(!self::isActiveUser($puid)){
				$next_time = date('G');
				if($next_time < 23 && $next_time > 8){
					$next_time = strtotime(date('Y-m-d 23:20:00'));
					$SAA_obj->next_time = $next_time;
					$SAA_obj->last_time = time();
					$SAA_obj->status = 2;
					if(!$SAA_obj->save(false)){
						echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors, true);
					}
					echo self::getTime() . " not_active_skip puid=".$puid.",shop_id=".$shop_id.",next_time:".$next_time.PHP_EOL;
					continue;
				}
			}
			echo self::getTime().' step2 puid='.$puid.',shop_id='.$shop_id.PHP_EOL;
			
			//*************Step 2, 调用接口获取订单列表信息
			$timeMS2 = TimeUtil::getCurrentTimestampMS();
			//是否全部同步完成
			$success = true;
			
			$time = time();
			if($SAA_obj->end_time == 0){
				//初始同步
				$start_time = $SAA_obj->binding_time;
				$end_time = $time;
			}
			else{
				//增量同步
				$start_time = $SAA_obj->end_time;
				$end_time = $time;
			}
			
			//dzt20190611 shopee提示Difference between create/update_time_to and create/update_time_from should be less than 15 days and larger than 0
			// 控制拉单间隔不大于10天
			if(($end_time - $start_time) > 10 * 86400){
			    $end_time = $start_time + 10 * 86400;
			}
			
			echo self::getTime()." ,start_time=".$start_time.",end_time=".$end_time.PHP_EOL;
			//调用拉取列表信息到队列
			$ret = ShopeeInterface_Helper::getOrderListToQueue($SAA_obj->shopee_uid, $start_time, $end_time, true);
			
			echo self::getTime().' step3 puid='.$puid.',shop_id='.$shop_id.PHP_EOL;
			$timeMS3 = TimeUtil::getCurrentTimestampMS();
			if($ret['success']){
				$SAA_obj->start_time = $start_time;
				$SAA_obj->end_time = $end_time;
				$SAA_obj->order_item = $ret['result'];
				$SAA_obj->status = 2;
				$SAA_obj->times = 0;
				$SAA_obj->message = '';
				$SAA_obj->next_time = time() + 3600;
			}
			else{
				$SAA_obj->status = 3;
				$SAA_obj->times += 1;
				$SAA_obj->next_time = time() + 1800;
				$SAA_obj->message = $ret['msg'];
			}
			$SAA_obj->last_time = time();
			if(!$SAA_obj->save(false)){
				$err = "step3 error puid='.$puid.',shop_id='.$shop_id.', error_msg : " . var_export($SAA_obj->errors, true);
				echo $err;
				\Yii::info($err, "file");
			}
			$timeMS4 = TimeUtil::getCurrentTimestampMS();
			
			$timeStr = "t4_t3=" . ($timeMS4 - $timeMS3) . ",t3_t2=" . ($timeMS3 - $timeMS2) . ",t2_t1=" . ($timeMS2 - $timeMS1) . ",t4_t1=" . ($timeMS4 - $timeMS1);
			echo self::getTime().' step6 one shop end puid='.$puid.',shop_id='.$shop_id.' '.$timeStr.PHP_EOL;
		}
		echo self::getTime().' time script end ' . self::$cronJobId . PHP_EOL;
		return $hasGetRecord;
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  根据queue_shopee_getorder队列，同步订单信息到db
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/28		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function updateToDb(){
		//检查当前job版本，如果和自己的版本不一样，就自动exit
		$currentShopeeGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/shopeeGetOrderListVersion", 'NO_CACHE');
		if(empty($currentShopeeGetOrderListVersion)){
			$currentShopeeGetOrderListVersion = 0;
		}
		//如果自己还没有订单，去使用global config初始
		if(empty(self::$shopeeGetOrderListVersion)){
			self::$shopeeGetOrderListVersion = $currentShopeeGetOrderListVersion;
		}
		//如果版本不一样，则退出
		if(self::$shopeeGetOrderListVersion <>$currentShopeeGetOrderListVersion){
			exit("Version new $currentShopeeGetOrderListVersion , this job ver " . self::$shopeeGetOrderListVersion . " exits for using new version $currentShopeeGetOrderListVersion.");
		}
		
		self::$cronJobId++;
		echo self::getTime().' update to db script start '.self::$cronJobId.PHP_EOL;
		
		//*************Step 0, 获取需同步的订单列表，并抢记录
		$connection = Yii::$app->db_queue;
		$now = time();
		$hasGetRecord = false;
		
		// 一个小时前 status依然是1的 重置状态
		$affectRows = $connection->createCommand("update queue_shopee_getorder set status=3, update_time=".time().", next_time=".(time() + 300)." where status=1 and update_time<".(time()-3600) )->execute();
		echo self::getTime()." fix status 1 records:$affectRows ,".self::$cronJobId.PHP_EOL;
		
		
		//查询需同步同步详情的订单
		$sql = "select id from queue_shopee_getorder where is_active=1 and status<>1 and times<10 and next_time<{$now} limit 100";
		
// 		$sql = "select id from queue_shopee_getorder where is_active=1 and status=3 and times<10 and next_time<{$now} and message like '%批量获取订单明细失%' limit 100 ";
		
		$dataReader = $connection->createCommand($sql)->query();
		echo self::getTime().' select count '.$dataReader->count().",sql:$sql".PHP_EOL;
		
		$queue_ids = array();   //抢到的记录
		while(false !== ($row = $dataReader->read())){
		    $now = time();
		    $timeMS1 = timeutil::getCurrentTimestampMS();
		    //判断是否能抢到记录，单条记录，没5分钟只执行一次
// 		    $affectRows = $connection->createCommand("update queue_shopee_getorder set status=1, update_time=".time().", next_time=".(time() + 300)." where id=".$row['id']." and status<>1")->execute();
// 		    if($affectRows > 0){
// 		        //抢到记录
// 		        $queue_ids[] = $row['id'];
// 		    }
		        $queue_ids[] = $row['id'];
		}
		//当没有抢到记录时，跳出
		if(empty($queue_ids)){
		    return $hasGetRecord;
		}
		//*************Step 1, 获取需更新的order_id列表
		$shop_orders = array();
		$rows = QueueShopeeGetorder::find()->where(['id' => $queue_ids])->all();
		foreach($rows as $row){
			$key = $row['shopee_uid'];
			$count = 0;
			//根据shop_id拆分订单组，并单次最多20张订单批量获取，接口说明是50个上限
			// dzt20190829 最近获取20个经常出现获取不到的情况，改为上限5个，抢占记录改成分组时才抢记录
			if(array_key_exists($key, $shop_orders) && count($shop_orders[$key]) >= 5){
			    continue;
			}
			
			//判断是否能抢到记录，单条记录，没5分钟只执行一次
			$affectRows = $connection->createCommand("update queue_shopee_getorder set status=1, update_time=".time().", next_time=".(time() + 300)." where id=".$row['id']." and status<>1")->execute();
			if($affectRows > 0){
			    //抢到记录
			    $shop_orders[$key][] = $row['orderid'];
			}
		}
		
		foreach($shop_orders as $shopee_uid => $one){
			$shopee_uid = rtrim($shopee_uid, '_');
			echo self::getTime().' shopee_uid='.$shopee_uid.' orderids='.json_encode($one).PHP_EOL;
			//更新订单信息到db
			$ret = ShopeeInterface_Helper::getOrderListToDb($shopee_uid, $one, true);
			echo print_r($ret, true).PHP_EOL;
			
			//处理队列信息
		    foreach($one as $order_id){
		        $mode = QueueShopeeGetorder::findOne(['shopee_uid' => $shopee_uid, 'orderid' => $order_id]);
		        if(!empty($mode)){
		            //当成功更新，则删除记录
		            if($ret['success'] && !empty($ret['result']['success_orderids']) && in_array($order_id, $ret['result']['success_orderids'])){
		                $mode->delete();
		                echo self::getTime().' GetorderDelete, shopee_uid='.$shopee_uid.', order_id='.$order_id.PHP_EOL;
		            }
		            else{
		                $msg = '';
		                if(!$ret['success']){
		                    $msg = $ret['msg'];
		                }
		                else{
		                	if(!empty($ret['result']['errors']) && array_key_exists($order_id, $ret['result']['errors'])){
		                		$msg = $ret['result']['errors'][$order_id];
		                	}
		                }
		                $mode->status = 3;
		                $mode->times += 1;
		                $mode->message = $msg;
		                $mode->last_time = $now;
		                $mode->next_time = $now + 1800;
		                if(!$mode->save(false)){
		                	echo "QueueShopeeGetorder save : ".var_export($QAG_obj->errors, true).PHP_EOL;
		                }
		                
		                echo self::getTime().' GetorderERR, shopee_uid='.$shopee_uid.', order_id='.$order_id.",msg:$msg".PHP_EOL;
		            }
		        }
		    }
		}
		
		echo self::getTime().' update to db script end '.self::$cronJobId.PHP_EOL;
		return true;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  同步Shopee订单15天内，未完成的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/08		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function getOrderListByUnFinish(){
		//检查当前job版本，如果和自己的版本不一样，就自动exit
		$currentShopeeGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/shopeeGetOrderListVersion", 'NO_CACHE');
		if(empty($currentShopeeGetOrderListVersion)){
			$currentShopeeGetOrderListVersion = 0;
		}
		//如果自己还没有订单，去使用global config初始
		if(empty(self::$shopeeGetOrderListVersion)){
			self::$shopeeGetOrderListVersion = $currentShopeeGetOrderListVersion;
		}
		//如果版本不一样，则退出
		if(self::$shopeeGetOrderListVersion <>$currentShopeeGetOrderListVersion){
			exit("Version new $currentShopeeGetOrderListVersion , this job ver " . self::$shopeeGetOrderListVersion . " exits for using new version $currentShopeeGetOrderListVersion.");
		}
		
		self::$cronJobId++;
		echo self::getTime().' un-finish script start '.self::$cronJobId.PHP_EOL;
		
		$connection = Yii::$app->db;
		$hasGotRecord = false;
		$nowTime = time();
	
		//查询同步表所有item队列，取前五条，并抢记录
		$sql = "select id from saas_shopee_autosync where is_active=1 and status in (0,3) and times<10 and type='unFinish' and next_time<".$nowTime." order by next_time asc limit 5";
		$dataReader = $connection->createCommand($sql)->query();
		echo self::getTime().' select count '.$dataReader->count().PHP_EOL;
		while(false !== ($row = $dataReader->read())){
			//判断是否可以正常抢到该记录
			$nowTime = time();
			$affectRows = $connection->createCommand("update saas_shopee_autosync set status=1, last_time=".$nowTime." where id=".$row['id']." and status<>1")->execute();
			if($affectRows <= 0){
				continue;   //当条记录抢不到
			}
			$hasGetRecord = true;
			$SAA_obj = SaasShopeeAutosync::findOne($row['id']);
			if(empty($SAA_obj)){
				echo self::getTime().' Exception '.$row['id'].PHP_EOL;
				continue;
			}
			$puid = $SAA_obj->puid;
			$shop_id = $SAA_obj->shop_id;
			$shopee_uid = $SAA_obj->shopee_uid;
			$timeMS1 = TimeUtil::getCurrentTimestampMS();
			echo self::getTime().' step 1 puid='.$puid.'shop_id='.$shop_id.PHP_EOL;
			
			//*************Step 1, 调用接口获取订单列表信息
			$timeMS2 = TimeUtil::getCurrentTimestampMS();
			$success = true;   //是否全部同步完成
			$msg = '';
			$order_ids = array();
			$total_item = 0;
			//循环根据创建时间、更新时间读取订单
			$per_page = 20;
			//初始授权信息
			$user = SaasShopeeUser::find()->where(['shopee_uid' => $shopee_uid, 'status' => 1])->one();
			if(empty($user)){
				$success = false;
				$msg = $shopee_uid.' 此店铺信息不存在！E1';
				echo $shopee_uid.' 此店铺信息不存在！E1';
			}
			else{
				$start_time = $SAA_obj->binding_time - (86400 * 15);
				$end_time = $SAA_obj->binding_time;
				
				$api = new ShopeeInterface_Api();
				$api->shop_id = $user->shop_id;
				$api->partner_id = $user->partner_id;
				$api->secret_key = $user->secret_key;
				
				$types = ['UNPAID', 'READY_TO_SHIP', 'SHIPPED'];
				foreach($types as $order_status){
					$next_row = 0;
					do{
						$is_next_page = false;
						$api_time = time();//接口调用时间
						//接口传入参数
						$param = ['order_status' => $order_status, 'create_time_from' => $start_time, 'create_time_to' => $end_time, 'pagination_entries_per_page' => $per_page, 'pagination_offset' => $next_row];
						//调用接口获取订单列表
						$result = $api->GetOrdersList($param);
						\Yii::info("shopee--GetOrdersList--$order_status--$shopee_uid--".json_encode($result), "file");
						if(!isset($result['orders'])){
							echo "shopee--findOrderListQuery--$order_status--$shopee_uid--err--".PHP_EOL;
							echo json_encode($result).PHP_EOL;
							
							$success = false;
							if(!empty($result['msg'])){
								\Yii::info("shopee---GetOrdersList--$order_status--$shopee_uid--err ".json_encode($result), "file");
								$msg = $result['msg'];
							}
							break;
						}
						else if(count($result['orders']) == 0){
							break;
						}
						echo "$order_status, orders: ".count($result['orders']).PHP_EOL;
						print_r ($result ['orders']);
							
						foreach($result['orders'] as $order){
							$orderid = $order['ordersn'];
							//排除重复
							if(in_array($orderid, $order_ids)){
								continue;
							}
							$order_ids[] = $orderid;
							echo "order_id: $orderid".PHP_EOL;
							
							$QAG_obj = QueueShopeeGetorder::findOne(['orderid' => $orderid]);
							if(empty($QAG_obj)){
								$QAG_obj = new QueueShopeeGetorder();
								$QAG_obj->uid = $user->puid;
								$QAG_obj->shop_id = $user->shop_id;
								$QAG_obj->site = $user->site;
								$QAG_obj->shopee_uid = $user->shopee_uid;
								$QAG_obj->status = 0;
								$QAG_obj->orderid = $orderid;
								$QAG_obj->last_time = 0;
								$QAG_obj->create_time = time();
							}
							$QAG_obj->type = 3;   //新订单标识
							$QAG_obj->times = 0;
							$QAG_obj->order_status = $order['order_status'];
							$QAG_obj->gmtupdate = $order['update_time'];
							$QAG_obj->update_time = time();
							if(!$QAG_obj->save(false)){
								echo "step2 error puid=".$user->puid.",shop_id=".$user->shop_id.",totalOrderNum=".count($result['orders'])." error_msg : " . var_export($QAG_obj->errors, true).PHP_EOL;
								$success = false;
								$msg = $shopee_uid.' 保存队列信息失败！';
								
								break;
							}
							$total_item++;
							
						}
						
						//判断是否需要下一页
						if(!empty($result['more'])){
							$is_next_page = true;
						}
						$next_row += $per_page;
					}while($is_next_page);
				}
				if(!$success){
					break;
				}
			}
			
			echo self::getTime().' step3 puid='.$puid.',shop_id='.$shop_id.PHP_EOL;
			$timeMS3 = TimeUtil::getCurrentTimestampMS();
			if($success){
				$SAA_obj->start_time = $start_time;
				$SAA_obj->end_time = $end_time;
				$SAA_obj->order_item = $total_item;
				$SAA_obj->status = 4;
				$SAA_obj->times = 0;
				$SAA_obj->message = '';
			}
			else{
				$hasGotRecord = false;
				$SAA_obj->status = 3;
				$SAA_obj->times += 1;
				$SAA_obj->message = $ret['msg'];
			}
			$SAA_obj->last_time = time();
			if(!$SAA_obj->save(false)){
				$err = "step3 error puid='.$puid.',shop_id='.$shop_id.', error_msg : " . var_export($SAA_obj->errors, true);
				echo $err;
			}
		}
		return $hasGotRecord;
	}
	
	
	
}




?>