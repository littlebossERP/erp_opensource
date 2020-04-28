<?php
namespace console\helpers;

use \Yii;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\SaasAliexpressAutosyncV2;
use eagle\models\QueueAliexpressGetorderV2;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;
use eagle\modules\util\helpers\RedisHelper;
use eagle\models\QueueAliexpressGetorder4V2;
use Qiniu\json_decode;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderTagHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;
use eagle\models\listing\AliexpressListing;
use eagle\models\listing\AliexpressListingDetail;
use eagle\modules\util\helpers\SQLHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_V2;
use eagle\models\UserDatabase;
use eagle\models\QueueAliexpressAutoOrderV2;
use eagle\models\QueueAliexpressAutoOrderError;
use eagle\models\SaasAliexpressUser;

/**
 * +------------------------------------------------------------------------------
 * Aliexpress 数据同步类
 * +------------------------------------------------------------------------------
 */
class AliexpressV2Helper
{
    public static $cronJobId = 0;
    private static $aliexpressGetOrderListV2Version = null;
    private static $version = null;


    protected static $active_users;

    const LOG_ID = "console_helpers_aliexpressV2Helper";

    private static function localLog($msg){
        console::log($msg, self::LOG_ID);
    }

    public static function isActiveUser($uid){
        return true;
    }

    /**
     * @return the $cronJobId
     */
    public static function getCronJobId()
    {
        return self::$cronJobId;
    }

    /**
     * @param number $cronJobId
     */
    public static function setCronJobId($cronJobId)
    {
        self::$cronJobId = $cronJobId;
    }
    
    /**
     * @param string $format . output time string format
     * @param timestamp $timestamp
     * @return America/Los_Angeles formatted time string
     */
    static function getLaFormatTime($format, $timestamp)
    {
    	$dt = new \DateTime();
    	$dt->setTimestamp($timestamp);
    	$dt->setTimezone(new \DateTimeZone('America/Los_Angeles'));
    	return $dt->format($format);
    
    
    }

    /**
     +---------------------------------------------------------------------------------------------
     *  同步Aliexpress新产生 / 有变更的订单 到队列中，等待更新到db，时间从绑定，重新绑定或者重新开启时间开始
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/01/05		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public static function getOrderListByTime(){
    	
    	//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
    	$currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListV2Version", 'NO_CACHE');
    	if (empty($currentAliexpressGetOrderListVersion)) {
    		$currentAliexpressGetOrderListVersion = 0;
    	}
    	//如果自己还没有定义，去使用global config来初始化自己
    	if (empty(self::$aliexpressGetOrderListV2Version)) {
    		self::$aliexpressGetOrderListV2Version = $currentAliexpressGetOrderListVersion;
    	}
    	//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
    	if (self::$aliexpressGetOrderListV2Version <> $currentAliexpressGetOrderListVersion) {
    		exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListV2Version . " exits for using new version $currentAliexpressGetOrderListVersion.");
    	}
    
    	self::$cronJobId++;
    	echo date('Y-m-d H:i:s') . ' time script start ' . self::$cronJobId . PHP_EOL;
    	$connection = Yii::$app->db;
    
    	$nowTime = time();
    	$hasGotRecord = false;
    	//查询同步控制表所有time队列，倒序取前五条
    	$sql = "select `id` from  `saas_aliexpress_autosync_v2` where `is_active` = 1 AND `status` <>1 AND `times` < 10 AND `type`='time' AND next_time < {$nowTime}  order by `next_time` ASC limit 5 ";
    	$dataReader = $connection->createCommand($sql)->query();
    	echo date('Y-m-d H:i:s') . ' select count ' . $dataReader->count() . PHP_EOL;
    	while (false !== ($row = $dataReader->read())) {
    		//1. 先判断是否可以正常抢到该记录
    		$nowTime = time();
    		$affectRows = $connection->createCommand("update saas_aliexpress_autosync_v2 set status=1,last_time={$nowTime} where id ={$row['id']} and status<>1 ")->execute();
    		if ($affectRows <= 0) {
    			continue; //当前这条抢不到
    		}
    
    		//2. 抢到记录，设置同步需要的参数
    		$hasGotRecord = true;
    		$SAA_obj = SaasAliexpressAutosyncV2::findOne($row['id']);
    		if (empty($SAA_obj)) {
    			echo date('Y-m-d H:i:s') . ' Exception ' . $row['id'] . PHP_EOL;
    			continue;
    		}
    		$puid = $SAA_obj->uid;
    		$sellerloginid = $SAA_obj->sellerloginid;
    		$timeMS1 = TimeUtil::getCurrentTimestampMS();
    		echo date('Y-m-d H:i:s') . " step1 puid=$puid,sellerloginid=$sellerloginid" . PHP_EOL;
    
    		if (!self::isActiveUser($SAA_obj->uid)) {
    			$next_time = date('G');
    			if ($next_time < 23 && $next_time > 8) {
    				$next_time = strtotime(date('Y-m-d 23:20:00'));
    				$SAA_obj->next_time = $next_time;
    				$SAA_obj->last_time = time();
    				$SAA_obj->status = 2;
    				$bool = $SAA_obj->save(false);
    				if (!$bool) {
    					echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors, true);
    				}
    				echo date('Y-m-d H:i:s') . " not_active_skip puid={$puid},sellerloginid={$sellerloginid},next_time:{$next_time}" . PHP_EOL;
    				continue;
    			}
    		}
    		echo date('Y-m-d H:i:s') . " step2 puid=$puid,sellerloginid=$sellerloginid" . PHP_EOL;
    
    		// 检查授权是否过期或者是否授权,返回true，false
    		if (!AliexpressInterface_Helper_Qimen::checkToken($SAA_obj->sellerloginid)) {
    			$SAA_obj->message .= " {$SAA_obj->sellerloginid} Unauthorized or expired!";
    			$SAA_obj->last_time = time();
    			$SAA_obj->next_time = time() + 1200;
    			$SAA_obj->status = 3;
    			$SAA_obj->times += 1;
    			$bool = $SAA_obj->save(false);
    			if (!$bool) {
    				echo __FUNCTION__ . "STEP 2 : " . var_export($SAA_obj->errors, true);
    			}
    			continue;
    		}
    
    		$api = new AliexpressInterface_Api_Qimen();
    		
    		echo date('Y-m-d H:i:s') . " step3 puid=$puid,sellerloginid=$sellerloginid" . PHP_EOL;
    		$timeMS2 = TimeUtil::getCurrentTimestampMS();
    
    		//分页设置
    		$page = 1;
    		$pageSize = 50;
    		// 是否全部同步完成
    		$success = true;
    		#####################################
    		$time = time();
    		if ($SAA_obj->end_time == 0) {
    			//初始同步
    			$start_time = $SAA_obj->binding_time;
    			$end_time = $time;
    		} elseif($time-$SAA_obj->end_time> (10*86400)) {// 有时候拉一个月订单会报错isp.50001 服务器错误，设置间隔超10天则一次只拉10天
    			//增量同步
    			$start_time = $SAA_obj->end_time;
    			$end_time = $SAA_obj->end_time + 10*86400;
    		} else {
    			//增量同步
    			$start_time = $SAA_obj->end_time;
    			$end_time = $time;
    		}
    
    		$format_start_time = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
    		$format_end_time = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
    		echo date('Y-m-d H:i:s') . " step4 puid=$puid,sellerloginid=$sellerloginid,start_time=$start_time,end_time=$end_time,format_start_time=$format_start_time,format_end_time=$format_end_time" . PHP_EOL;
    		//获取最后发货时间
    		$order_ids = [];
    		$total_item = 0;
    		//$api_types = ['create', 'modified'];

			// dzt20190606 测试过后全部转接口
    		$api_types = ['create_v2', 'modified_v2', 'modified_finish'];
    		
    		// $api_types = ['create', 'create_v2', 'modified_v2'];
    	 
    		foreach($api_types as $api_type){
    			$page = 1;
	    		do {
	    			$api_time = time();//接口调用时间
	    			// 接口传入参数
// 	    			if(strpos($api_type, '_v2') !== false){
// 	    			    $param = ['id' => $sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, rtrim($api_type, '_v2').'_date_start' => $format_start_time, rtrim($api_type, '_v2').'_date_end' => $format_end_time, 'buyer_login_id' => 'new'])];
// 	    			}
// 	    			else{
// 	    				$param = ['id' => $sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, $api_type.'_date_start' => $format_start_time, $api_type.'_date_end' => $format_end_time])];
// 	    			}

	    			$param1 = ['page' => $page, 'page_size' => $pageSize, 'buyer_login_id' => 'new'];
	    			// dzt20190909 FINISH:已结束的订单，需单独查询
	    			if($api_type == 'modified_finish'){
	    			    $param1['order_status'] = "FINISH";
	    			    $param1['modified_date_start'] = $format_start_time;
	    			    $param1['modified_date_end'] = $format_end_time;
	    			}else{
	    			    $param1[rtrim($api_type, '_v2').'_date_start'] = $format_start_time;
	    			    $param1[rtrim($api_type, '_v2').'_date_end'] = $format_end_time;
	    			}
	    			
	    			$param = ['id' => $sellerloginid, 'param1' => json_encode($param1)];
	    			
					echo json_encode($param).PHP_EOL;
	    			// 调用接口获取订单列表
	    			$result = $api->findOrderListQuery($param);
	    			\Yii::info("getOrderListByTime--findOrderListQuery--$api_type--".json_encode($result), "file");
	    			// 判断是否有订单
	    			if (!isset ($result['total_item'])) {
	    				$success = false;
	    				echo "getOrderListByTime--findOrderListQuery--$api_type--err1--".PHP_EOL;
	    				echo json_encode($result).PHP_EOL;
	    				break;
	    			}
	    			else if($result['total_item'] > 0 && empty($result['order_list'])) {
	    				$success = false;
	    				echo "getOrderListByTime--findOrderListQuery--$api_type--err2--".PHP_EOL;
	    				echo json_encode($result).PHP_EOL;
	    				break;
	    			}
	    			else if(empty($result['order_list'])) {
	    				break;
	    			}
	    			echo "$api_type, total_item: ".$result['total_item'].", order_list: ".count($result['order_list']).PHP_EOL;
	    			
	    			print_r ($result ['order_list']);
	    			foreach ($result ['order_list'] as $order) {
	    				$order_info = array();
						$orderid = number_format($order['order_id'], 0, '', '');
						//排除重复的
						if(in_array($orderid, $order_ids)){
							continue;
						}
						//新版接口返回的是美国时间，需转换
						if(strpos($api_type, '_v2') !== false){
							$order['gmt_create'] = date("Y-m-d H:i:s", AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create'], 'US'));
						}
						$order_ids[] = $orderid;
						echo "order_id: $orderid".PHP_EOL;
						$order_info = [
							'biz_type' => $order['biz_type'],
							'gmt_create' => $order['gmt_create'],
							'memo' => '',
							'order_id' => $order['order_id'],
							'order_status' => $order['order_status'],
							'product_list' => [],
							'day' => isset($order['left_send_good_day']) ? $order['left_send_good_day'] : 0,
							'hour' => isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] : 0,
							'min' => isset($order['left_send_good_min']) ? $order['left_send_good_min'] : 0,
							'api_time' => $api_time,
						];
	
						$logisticsServiceName_arr= array();     //买家物流信息
						$memo_arr= array();                     //买家备注
						$sendGoodsOperator_arr = array();       //发货类型
						if (isset($order['product_list'])) {
							foreach ($order['product_list'] as $pl) {
								$productid = strval($pl['product_id']);
								
								$order_info['product_list'][] = [
									'child_id' => number_format($pl['child_id'], 0, '', ''),
									'money_back3x' => $pl['money_back3x'],
									'product_count' => $pl['product_count'],
									'product_id' => $productid,
									'product_img_url' => empty($pl['product_img_url'])?"":$pl['product_img_url'],//dzt20191018 最近有些产品没有图片，和URL
									'product_name' => $pl['product_name'],
									'product_snap_url' => empty($pl['product_snap_url'])?"":$pl['product_snap_url'],//dzt20191018
									'product_unit' => $pl['product_unit'],
									'product_unit_price' => empty($pl['product_unit_price']['cent_factor']) ? $pl['product_unit_price']['amount'] : $pl['product_unit_price']['cent'] / $pl['product_unit_price']['cent_factor'],
									'product_unit_price_cur' => $pl['product_unit_price']['currency_code'],
									'sku_code' => empty($pl['sku_code']) ? '' : $pl['sku_code'],
									'son_order_status' => $pl['son_order_status'],
								];
								
								//客选物流
								if (isset($pl['logistics_service_name'])) {
									$logisticsServiceName = $pl['logistics_service_name'];
									$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
								}
								//买家备注
								if( isset($pl['memo']) ){
									$pmemo= str_replace("'","",$pl['memo']);
									if( $pmemo=='' ){
										$pmemo= '无';
									}
									$memo_arr[]= $pmemo;
									//$logisticsServiceName_arr["user_message"][$productid] = $memo;
								}
								//发货类型
								if (isset($pl['send_goods_operator'])) {
									$sendGoodsOperator = $pl['send_goods_operator'];
									$sendGoodsOperator_arr[$productid] = $sendGoodsOperator;
								}
							}
						}
	    				//客选物流
	    				if(!empty($logisticsServiceName_arr)){
	    					$order_info['logisticsServiceName_arr'] = $logisticsServiceName_arr;
	    				}
	    				//买家备注
	    				if(!empty($memo_arr)){
	    					$order_info['memo_arr'] = $memo_arr;
	    				}
	    				//发货类型
	    				if(!empty($sendGoodsOperator_arr)){
	    					$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr;
	    				}
						print_r ($order_info);
						
						$QAG_obj = QueueAliexpressGetorderV2::findOne(['orderid' => $orderid]);
						if (isset ($QAG_obj)) {
							$QAG_obj->type = 3;
							$QAG_obj->times = 0;
							$QAG_obj->order_status = $order['order_status'];
							$QAG_obj->order_info = json_encode($order_info);
							$QAG_obj->update_time = $time;
							$bool = $QAG_obj->save(false);
							if (!$bool) {
								\Yii::info("step4 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=".$result['total_item']." error_msg : " . var_export($QAG_obj->errors, true), "file");
								$success = false;
								echo "step4 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=".$result['total_item']." error_msg : " . var_export($QAG_obj->errors, true).PHP_EOL;
								break;
							}
						} else {
							$QAG_obj = new QueueAliexpressGetorderV2 ();
							$QAG_obj->uid = $SAA_obj->uid;
							$QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
							$QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
							$QAG_obj->status = 0;
							$QAG_obj->type = 3;  //新增订单标识
							$QAG_obj->order_status = $order['order_status'];
							$QAG_obj->orderid = $orderid;
							$QAG_obj->times = 0;
							$QAG_obj->order_info = json_encode($order_info);
							$QAG_obj->last_time = 0;
							$QAG_obj->gmtcreate = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create']);
							$QAG_obj->create_time = $time;
							$QAG_obj->update_time = $time;
							$bool = $QAG_obj->save(false);
							if (!$bool) {
								\Yii::info("step5 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=".$result['total_item']." error_msg : " . var_export($QAG_obj->errors, true), "file");
								$success = false;
								echo "step5 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=".$result['total_item']." error_msg : " . var_export($QAG_obj->errors, true).PHP_EOL;
								break;
							}
						}
						$total_item++;
	    
	    			}
	    			$page++;
	    			$p = ceil($result['total_item'] / 50);
	    		} while ($page <= $p);
	    		
	    		if(!$success){
	    			break;
	    		}
    		}
    
    		echo date('Y-m-d H:i:s') . " step5 puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$total_item" . PHP_EOL;
    		$timeMS3 = TimeUtil::getCurrentTimestampMS();
    		// 是否全部同步成功
    		if ($success) {
    			$SAA_obj->start_time = $start_time;
    			$SAA_obj->end_time = $end_time;
    			$SAA_obj->order_item = $total_item;
    			$SAA_obj->status = 2;
    			$SAA_obj->times = 0;
    			$SAA_obj->message = '';
    			$SAA_obj->next_time = time() + 3600;
    			
    			//记录最后同步成功的时间段
    			RedisHelper::RedisSet('Aliexpress_getOrderListByTime_redis','puid_'.$puid.'_sellerloginid_'.$sellerloginid,$end_time);
    
    		} else {
    			echo date('Y-m-d H:i:s') . ' time script end ' . self::$cronJobId . PHP_EOL;
    			
    			$SAA_obj->message .= isset($result['error_message']) ? $result['error_message'] : '接口返回结果错误V2';
    			echo " puid=$puid,sellerloginid=$sellerloginid,getOrderListByTime err".print_r($result, true).PHP_EOL;
    				
    			$SAA_obj->status = 3;
    			$SAA_obj->times += 1;
    			$SAA_obj->next_time = time() + 1800;
    		}
    		$SAA_obj->last_time = $time;
    		$bool = $SAA_obj->save(false);
    		if (!$bool) {
    			\Yii::info("step6 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$total_item error_msg : " . var_export($SAA_obj->errors, true), "file");
    		}
    		$timeMS4 = TimeUtil::getCurrentTimestampMS();
    
    		$timeStr = "t4_t3=" . ($timeMS4 - $timeMS3) . ",t3_t2=" . ($timeMS3 - $timeMS2) . ",t2_t1=" . ($timeMS2 - $timeMS1) . ",t4_t1=" . ($timeMS4 - $timeMS1);
    
    		echo date('Y-m-d H:i:s') . " step6 one shop end puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$total_item " . $timeStr . PHP_EOL;
    
    		\Yii::info("step6 one shop end puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$total_item " . $timeStr, "file");
    
    	}
    	echo date('Y-m-d H:i:s') . ' time script end ' . self::$cronJobId . PHP_EOL;
    	return $hasGotRecord;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *  根据queue_aliexpress_getorder_v2队列，同步订单信息到db
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/01/05		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public static function updateToDb()
    {
    	//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
    	$currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListV2Version", 'NO_CACHE');
    	if (empty($currentAliexpressGetOrderListVersion)) {
    		$currentAliexpressGetOrderListVersion = 0;
    	}
    	//如果自己还没有定义，去使用global config来初始化自己
    	if (empty(self::$aliexpressGetOrderListV2Version)) {
    		self::$aliexpressGetOrderListV2Version = $currentAliexpressGetOrderListVersion;
    	}
    	//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
    	if (self::$aliexpressGetOrderListV2Version <> $currentAliexpressGetOrderListVersion) {
    		exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListV2Version . " exits for using new version $currentAliexpressGetOrderListVersion.");
    	}
    
    	self::$cronJobId++;
    	echo date('Y-m-d H:i:s') . ' update to db script start ' . self::$cronJobId . PHP_EOL;
    
    	// 同步订单
    	$connection = Yii::$app->db_queue;
    	$now = time();
    	$hasGotRecord = false;
    	//查新订单
    	$sql = 'select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status`, `type` from  `queue_aliexpress_getorder_v2` where `status` <> 1 and `type` = 3 AND `times` < 10 and next_time<'.$now.' limit 100';
    	$dataReader = $connection->createCommand($sql)->query();
    	echo date('Y-m-d H:i:s') . ' select count ' . $dataReader->count() . PHP_EOL;
    	while (false !== ($row = $dataReader->read())) {
    		$now = time();
    		$timeMS1 = TimeUtil::getCurrentTimestampMS();
    		//1. 先判断是否可以正常抢到该记录
    		$affectRows = $connection->createCommand("update `queue_aliexpress_getorder_v2` set status=1,update_time={$now} where id ={$row['id']} and status<>1 ")->execute();
    		if ($affectRows <= 0) {
    			continue; //抢不到
    		}
    
    		//2. 抢到记录，设置同步需要的参数
    		$hasGotRecord = true;
    		$QAG_obj = QueueAliexpressGetorderV2::findOne($row['id']);
    		if (empty($QAG_obj)) {
    			echo date('Y-m-d H:i:s') . ' exception ' . $row['orderid'] . PHP_EOL;
    			continue;
    		}
    		
    		//2018-03-28 8点db5维护，先放过这些订单
    		/*try{
    			$user = UserDatabase::findOne(['uid' => $QAG_obj->uid]);
    			if($user->dbserverid == 5 && $now > 1522238400 && $now < 1522252800){
    				$QAG_obj->last_time = $now;
    				$QAG_obj->status = 0;
    				$QAG_obj->next_time = 1522252800;
    				$QAG_obj->save(false);
    				continue;
    			}
    		}
    		catch(\Exception $ex){}
    		*/
    		echo date('Y-m-d H:i:s') . ' api start ' . $QAG_obj->orderid . PHP_EOL;
    		$timeMS2 = TimeUtil::getCurrentTimestampMS();
    		// 检查授权是否过期或者是否授权,返回true，false
    		if (!AliexpressInterface_Helper_Qimen::checkToken($QAG_obj->sellerloginid)) {
    			$QAG_obj->message .= " {$QAG_obj->sellerloginid} Unauthorized or expired!";
    			$QAG_obj->last_time = $now;
    			$QAG_obj->status = 3;
    			$QAG_obj->times += 1;
    			$bool = $QAG_obj->save(false);
    			if (!$bool) {
    				echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
    			}
    			continue;
    		}
    
    		$api = new AliexpressInterface_Api_Qimen ();
    
    		// 接口传入参数速卖通订单号
    		$param = ['id' => $QAG_obj->sellerloginid, 'param1' => json_encode(['order_id' => $row['orderid']])];
    		// 调用接口获取订单列表
    		$result = $api->findOrderById($param);
    		echo date('Y-m-d H:i:s') . ' api end ' . $QAG_obj->orderid . PHP_EOL;
    		//是否同步成功
    		if (!empty($result['error_message']) || empty ($result)) {
    			$QAG_obj->message .= isset ($result['error_message']) ? $result['error_message'] . " findOrderById " : 'findOrderById接口返回错误，在新订单入库时';
    			$QAG_obj->status = 3;
    			$QAG_obj->times += 1;
    			$QAG_obj->last_time = $now;
    			$bool = $QAG_obj->save(false);
    			if (!$bool) {
    				echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
    			}
    			continue;
    		}
    
    		echo date('Y-m-d H:i:s') . ' save start ' . $QAG_obj->uid . ' ' . $QAG_obj->orderid . PHP_EOL;
    		$timeMS3 = TimeUtil::getCurrentTimestampMS();
    		$uid = $QAG_obj->uid;
    		 
    
    		//平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
    		$result['id'] = strval($QAG_obj->orderid);
    
    		//速卖通返回的sellerOperatorLoginId是子账号的loginid（就算账号绑定的是主账号）
    		//这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
    		$result["sellerOperatorLoginId"] = $QAG_obj->sellerloginid;
    		print_r ($QAG_obj);
    		print_r ($result);
    		$r = AliexpressInterface_Helper_V2::saveAliexpressOrder($QAG_obj, $result);
    		// 判断是否付款并且保存成功,是则删除数据，否则继续同步
    		if ($r['success'] != 0 || !isset($result['order_status'])) {
    			$QAG_obj->status = 3;
    			$QAG_obj->times += 1;
    			$QAG_obj->message .= "速卖通订单saveAliexpressOrder " . $QAG_obj->orderid . "保存失败" . $r ['message'];
    			$bool = $QAG_obj->save(false);
    			if (!$bool) {
    				echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
    			}
    			continue;
    		}
    
    		$timeMS4 = TimeUtil::getCurrentTimestampMS();
    		
    		//更新客选物流、买家备注、剩余发货时间
    		if(!empty($QAG_obj->order_info)){
    			$order_info = json_decode($QAG_obj->order_info, true);
    			if(!empty($order_info)){
    				//设置客选物流
					if (!empty($order_info['logisticsServiceName_arr'])) {
						list( $order_status, $msg)= OrderHelper::updateOrderAddiInfoByOrderID( $QAG_obj->orderid, $QAG_obj->sellerloginid, 'aliexpress', $order_info['logisticsServiceName_arr']);
						if( $order_status === false ){
							echo $QAG_obj->orderid.'可选物流更新失败--'.$msg.PHP_EOL;
						}
					}
					//如果都是0的话,不处理最后发货时间,有一个不是0,才去处理
					if(isset($order_info['day'])){
						if( $order_info['day'] > 0 || $order_info['hour'] > 0 || $order_info['min'] > 0 ){
							//在接口调用时间上,加上秒数就是最后发货时间啦
							$fulfill_deadline = ceil($order_info['day'] * 86400 + $order_info['hour'] * 3600 + $order_info['min'] * 60 + $order_info['api_time']);
							//更新掉字段
							Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_source_order_id='".$QAG_obj->orderid."'")->execute();
						}
					}
					//设置买家备注
					if (!empty($order_info['memo_arr'])) {
						$memo_arr = $order_info['memo_arr'];
						$memo= '';
						if( !empty( $memo_arr ) ){
							$memo_eof = false;
							foreach( $memo_arr as $memo_vss ){
								if( $memo_vss!='无' ){
									$memo_eof= true;
									break;
								}
							}
							if( $memo_eof===true ){
								foreach( $memo_arr as $key=>$memo_vss ){
									$count= $key+1;
									$memo.= "商品{$count}:{$memo_vss};";
								}
							}
						}
						if( $memo != '' ) {
							//需要获取自增id
							$ro = OdOrder::findOne(['order_source_order_id' => $QAG_obj->orderid]);
							if (!empty($ro)) {
								$sysTagRt = OrderTagHelper::setOrderSysTag($ro->order_id, 'pay_memo');
								if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400') {
									echo '\n' . $ro->order_id . ' insert pay_memo failure :' . $sysTagRt['message'];
								}
							}
							Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET user_message='".$memo."' WHERE order_source_order_id='".$QAG_obj->orderid."'")->execute();
						}
					}
    			}
    		}
    
    		if ($result ['order_status'] == 'FINISH') {
    			$bool = $QAG_obj->delete();
    			if (!$bool) {
    				echo __FUNCTION__ . "STEP 6 : " . var_export($QAG_obj->errors, true);
    			}
    		} else {
    			//写入队列4
    			$QAG_four = QueueAliexpressGetorder4V2::findOne(['orderid' => $QAG_obj->orderid]);
    			if(empty($QAG_four)){
    				$QAG_four = new QueueAliexpressGetorder4V2();
    				$QAG_four->create_time = time();
    			}
    			$QAG_four->uid = $QAG_obj->uid;
    			$QAG_four->sellerloginid = $QAG_obj->sellerloginid;
    			$QAG_four->aliexpress_uid = $QAG_obj->aliexpress_uid;
    			$QAG_four->order_status = $result ['order_status'];
    			$QAG_four->orderid = $QAG_obj->orderid;
    			$QAG_four->order_info = $QAG_obj->order_info;
    			$QAG_four->gmtcreate = $QAG_obj->gmtcreate;
    			$QAG_four->update_time = time();
    			$boolfour = $QAG_four->save();
    
    			if ($boolfour) {
    				$bool = $QAG_obj->delete();
    				if (!$bool) {
    					echo __FUNCTION__ . "STEP 8 : " . var_export($QAG_obj->errors, true);
    				}
    			} else {
    				$QAG_obj->status = 3;
    				$QAG_obj->times += 1;
    				//$QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败".$r ['message'];
    				$QAG_obj->message .= "QAG_four->save fails ---" . print_r($QAG_four->errors, true);
    
    				$bool = $QAG_obj->save(false);
    				if (!$bool) {
    					echo __FUNCTION__ . "STEP 9 : " . var_export($QAG_obj->errors, true);
    				}
    			}
    		}
    		echo date('Y-m-d H:i:s') . ' save end ' . $uid . ' ' . $result['id'] . PHP_EOL;
    		$timeMS5 = TimeUtil::getCurrentTimestampMS();
    
    		$logStr = "aliexpress_updatetodb_finish puid=$uid,t2_1=" . ($timeMS2 - $timeMS1) .
    		",t3_2=" . ($timeMS3 - $timeMS2) . ",t4_3=" . ($timeMS4 - $timeMS3) . ",t5_4=" . ($timeMS5 - $timeMS4) . ",t5_1=" . ($timeMS5 - $timeMS1);
    
    		echo $logStr . "\n";
    		\Yii::info($logStr, "file");
    
    	}
    	echo date('Y-m-d H:i:s') . ' update to db script end ' . self::$cronJobId . PHP_EOL;
    	return $hasGotRecord;
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     *  同步Aliexpress订单120天内，未完成的订单
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/01/05		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static function getOrderListByDay120(){
    	
    	//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
    	$currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListV2Version", 'NO_CACHE');
    	if (empty($currentAliexpressGetOrderListVersion)) {
    		$currentAliexpressGetOrderListVersion = 0;
    	}
    	//如果自己还没有定义，去使用global config来初始化自己
    	if (empty(self::$aliexpressGetOrderListV2Version)) {
    		self::$aliexpressGetOrderListV2Version = $currentAliexpressGetOrderListVersion;
    	}
    	//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
    	if (self::$aliexpressGetOrderListV2Version <> $currentAliexpressGetOrderListVersion) {
    		exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListV2Version . " exits for using new version $currentAliexpressGetOrderListVersion.");
    	}
    
    	self::$cronJobId++;
    	echo date('Y-m-d H:i:s') . ' day120 script start ' . self::$cronJobId . PHP_EOL;
    	$connection = Yii::$app->db;
    	$hasGotRecord = false;
    	$now = time();
    
    	$dataReader = $connection->createCommand('select `id` from  `saas_aliexpress_autosync_v2` where `is_active` = 1 AND `status` in (0,3) AND `times` < 10 AND `type`="day120" order by `last_time` ASC limit 5')->query();
    	while (false !== ($row = $dataReader->read())) {
    		//1. 先判断是否可以正常抢到该记录
    		$affectRows = $connection->createCommand("update saas_aliexpress_autosync_v2 set status=1 where id =" . $row['id'] . " and status<>1 ")->execute();
    
    		if ($affectRows <= 0) {
    			continue; //抢不到
    		}
    
    		//2. 抢到记录
    		$hasGotRecord = true;
    		$SAA_obj = SaasAliexpressAutosyncV2::findOne($row['id']);
    		if (empty($SAA_obj)) {
    			echo 'exception' . $row['id'] . PHP_EOL;
    			continue;
    		}
    
    		// 检查授权是否过期或者是否授权,返回true，false
    		if (!AliexpressInterface_Helper_Qimen::checkToken($SAA_obj->sellerloginid)) {
    			$SAA_obj->message = $SAA_obj->sellerloginid . ' Unauthorized or expired!';
    			$SAA_obj->status = 3;
    			$SAA_obj->times += 1;
    			$SAA_obj->last_time = $now;
    			$SAA_obj->update_time = $now;
    			$bool = $SAA_obj->save(false);
    			if (!$bool) {
    				echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors, true);
    			}
    			continue;
    		}
    
    		$api = new AliexpressInterface_Api_Qimen ();
    		$pageSize = 50;
    		$total_item = 0;
    		// 是否全部同步完成
    		$success = true;
    		for($n = 0; $n < 2; $n++){
    			$page = 1;
    			
    			if($n == 1){
    				//获取3天内有修改的订单
    				$start_time = $SAA_obj->binding_time - (86400 * 5);
    				$end_time = $SAA_obj->binding_time;
    				$order_status_list = ['WAIT_BUYER_ACCEPT_GOODS', 'FUND_PROCESSING', 'FINISH', 'WAIT_SELLER_EXAMINE_MONEY', 'IN_CANCEL'];
    			}
    			else{
    				//获取120天内未完成的订单
    				$start_time = $SAA_obj->binding_time - (86400 * 120);
    				$end_time = $SAA_obj->binding_time;
    				$order_status_list = ['PLACE_ORDER_SUCCESS', 'IN_ISSUE', 'IN_FROZEN', 'WAIT_SELLER_SEND_GOODS', 'RISK_CONTROL', 'SELLER_PART_SEND_GOODS'];
    			}
    			$format_start_time = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
    			$format_end_time = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
	    		
	    		do {
	    			$api_time = time();//接口调用时间
	    			// 接口传入参数	    			
	    			$param = ['id' => $SAA_obj->sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, 'create_date_start' => $format_start_time, 'create_date_end' => $format_end_time, 'order_status_list' => $order_status_list, 'buyer_login_id' => 'new' ])];
	    			// 调用接口获取订单列表
	    			$result = $api->findOrderListQuery($param);
	    			\Yii::info("getOrderListByDay120--findOrderListQuery--".json_encode($result), "file");
	    			// 判断是否有订单
	    			if (!isset ($result['total_item'])) {
	    				$success = false;
	    				echo "getOrderListByDay120--findOrderListQuery--err--".PHP_EOL;
	    				echo json_encode($result).PHP_EOL;
	    				break;
	    			}
	    			else if($result['total_item'] > 0 && empty($result['order_list'])) {
	    				$success = false;
	    				echo "getOrderListByDay120--findOrderListQuery--$api_type--err--".PHP_EOL;
	    				echo json_encode($result).PHP_EOL;
	    				break;
	    			}
	    			else if(empty($result['order_list'])) {
	    				break;
	    			}
	    			echo "total_item: ".$result['total_item'].", order_list: ".count($result['order_list']).PHP_EOL;
	    			
	    			// 判断是否有订单
	    			if (isset ($result['total_item']) && isset($result['order_list'])) {
	    				if ($result ['total_item'] > 0) {
	    					if(empty($total_item)){
	    						$total_item = $result['total_item'];
	    					}
	    					// 保存数据到同步订单详情队列
	    					foreach ($result ['order_list'] as $order) {
	    						$order_info = array();
	    						$orderid = number_format($order['order_id'], 0, '', '');
	    						echo "order_id: $orderid".PHP_EOL;
	    						$order_info = [
		    						'biz_type' => $order['biz_type'],
		    						'gmt_create' => $order['gmt_create'],
		    						'memo' => '',
		    						'order_id' => $order['order_id'],
		    						'order_status' => $order['order_status'],
		    						'product_list' => [],
		    						'day' => isset($order['left_send_good_day']) ? $order['left_send_good_day'] : 0,
		    						'hour' => isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] : 0,
		    						'min' => isset($order['left_send_good_min']) ? $order['left_send_good_min'] : 0,
		    						'api_time' => $api_time,
	    						];
	    						
	    						$logisticsServiceName_arr= array();     //买家物流信息
	    						$memo_arr= array();                     //买家备注
	    						$sendGoodsOperator_arr = array();       //发货类型
	    						if (isset($order['product_list'])) {
	    							foreach ($order['product_list'] as $pl) {
	    								$productid = strval($pl['product_id']);
	    									
	    								$order_info['product_list'][] = [
		    								'child_id' => number_format($pl['child_id'], 0, '', ''),
		    								'money_back3x' => $pl['money_back3x'],
		    								'product_count' => $pl['product_count'],
		    								'product_id' => $productid,
		    								'product_img_url' => empty($pl['product_img_url'])?"":$pl['product_img_url'],//dzt20191018
		    								'product_name' => $pl['product_name'],
		    								'product_snap_url' => empty($pl['product_snap_url'])?"":$pl['product_snap_url'],//dzt20191018
		    								'product_unit' => $pl['product_unit'],
		    								'product_unit_price' => empty($pl['product_unit_price']['cent_factor']) ? $pl['product_unit_price']['amount'] : $pl['product_unit_price']['cent'] / $pl['product_unit_price']['cent_factor'],
		    								'product_unit_price_cur' => $pl['product_unit_price']['currency_code'],
		    								'sku_code' => empty($pl['sku_code']) ? '' : $pl['sku_code'],
		    								'son_order_status' => $pl['son_order_status'],
	    								];
	    									
	    								//客选物流
	    								if (isset($pl['logistics_service_name'])) {
	    									$logisticsServiceName = $pl['logistics_service_name'];
	    									$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
	    								}
	    								//买家备注
	    								if( isset($pl['memo']) ){
	    									$pmemo= str_replace("'","",$pl['memo']);
	    									if( $pmemo=='' ){
	    										$pmemo= '无';
	    									}
	    									$memo_arr[]= $pmemo;
	    									//$logisticsServiceName_arr["user_message"][$productid] = $memo;
	    								}
	    								//发货类型
	    								if (isset($pl['send_goods_operator'])) {
	    									$sendGoodsOperator = $pl['send_goods_operator'];
	    									$sendGoodsOperator_arr[$productid] = $sendGoodsOperator;
	    								}
	    							}
	    						}
	    						//客选物流
	    						if(!empty($logisticsServiceName_arr)){
	    							$order_info['logisticsServiceName_arr'] = $logisticsServiceName_arr;
	    						}
	    						//买家备注
	    						if(!empty($memo_arr)){
	    							$order_info['memo_arr'] = $memo_arr;
	    						}
	    						//发货类型
	    						if(!empty($sendGoodsOperator_arr)){
	    							$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr;
	    						}
	    						print_r ($order_info);
	    						
	    						$QAG_obj = QueueAliexpressGetorderV2::findOne(['orderid' => $orderid]);
	    						if (isset ($QAG_obj)) {
	    							$QAG_obj->type = 3;
	    							$QAG_obj->order_status = $order['order_status'];
	    							$QAG_obj->order_info = json_encode($order_info);
	    							$QAG_obj->update_time = $now;
	    							$QAG_obj->last_time = $now;
	    							$bool = $QAG_obj->save(false);
	    							if (!$bool) {
	    								echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
	    							}
	    						} else {
	    							$QAG_obj = new QueueAliexpressGetorderV2 ();
	    							$QAG_obj->uid = $SAA_obj->uid;
	    							$QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
	    							$QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
	    							$QAG_obj->status = 0;
	    							$QAG_obj->type = 3;
	    							$QAG_obj->order_status = $order['order_status'];
	    							$QAG_obj->orderid = $orderid;
	    							$QAG_obj->times = 0;
	    							$QAG_obj->order_info = json_encode($order_info);
	    							$QAG_obj->last_time = 0;
	    							$QAG_obj->gmtcreate = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create']);
	    							$QAG_obj->create_time = $now;
	    							$QAG_obj->update_time = $now;
	    							$bool = $QAG_obj->save(false);
	    							if (!$bool) {
	    								echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_obj->errors, true);
	    							}
	    						}
	    					}
	    				}
	    			} else {
	    				$success = false;
	    			}
	    
	    			$page++;
	    			$total = isset($result ['total_item']) ? $result ['total_item'] : 0;
	    			$p = ceil($total / 50);
	    		} while ($page <= $p);
    		}
    		
    		// 是否全部同步成功
    		if ($success) {
    			$SAA_obj->start_time = $start_time;
    			$SAA_obj->end_time = $end_time;
    			$SAA_obj->last_time = $now;
    			$SAA_obj->order_item = $total_item;
    			$SAA_obj->status = 4;
    			$SAA_obj->times = 0;
    			$bool = $SAA_obj->save(false);
    			if (!$bool) {
    				echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
    			}
    		} else {
    			$SAA_obj->message = isset($result['error_message']) ? $result['error_message'] : '接口返回结果错误V2';
    			$SAA_obj->last_time = $now;
    			$SAA_obj->status = 3;
    			$SAA_obj->times += 1;
    			$bool = $SAA_obj->save(false);
    			if (!$bool) {
    				echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
    			}
    		}
    	}
    	return $hasGotRecord;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *  同步在线商品
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/02/24		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static function getListing($type, $orderBy = "last_time", $time_interval = 86400, $isImmediate = 'N',$sellerloginid =''){
    	//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
    	$version = ConfigHelper::getGlobalConfig("Listing/aliexpressGetOrderListV2Version", 'NO_CACHE');
    	if (empty($version))
    		$version = 0;
    
    	//如果自己还没有定义，去使用global config来初始化自己
    	if (empty(self::$version))
    		self::$version = $version;
    
    	//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
    	if (self::$version <> $version) {
    		exit("Version new $version , this job ver " . self::$version . " exits for using new version $version.");
    	}
    
    
    	$backgroundJobId = self::getCronJobId();
    	$queue = Yii::$app->db_queue;
    	$connection = Yii::$app->db;
    	#########################
    	$hasGotRecord = false;//是否抢到账号
    	$t = time() - $time_interval;
    
    	if( $sellerloginid=='' ){
	    	$sqlStr = 'select `id`,`uid` from `saas_aliexpress_autosync_v2`
				where `last_time` < ' . $t . ' AND `is_active` = 1 AND `status` <> 1 AND `times` < 10 AND `type`="' . $type . '" order by `last_time` ASC limit 50';
    	}else{
	    	$sqlStr = 'select `id`,`uid` from `saas_aliexpress_autosync_v2`
				where  `is_active` = 1 AND `status` <> 1 AND `times` < 10 AND `type`="' . $type . '" AND sellerloginid="'.$sellerloginid.'"  ';
    	}
    	
    	echo $sqlStr . " \n";
    	$command = $connection->createCommand($sqlStr);
    
    	#################################
    	$dataReader = $command->query();
    	//	$activeUsersPuidArr = UserLastActionTimeHelper::getPuidArrByInterval(7*24);
    	while (($row = $dataReader->read()) !== false) {
    		//检查是否开启同步商品
    		/*	$ret = AppApiHelper::getFunctionstatusByPuidKey($row['uid'], "tracker_recommend");
    		 if($ret == 0){
    		//没有开启，更新，跳过当前循环
    		$command = $connection->createCommand("update saas_aliexpress_autosync set status=4 ,last_time=".time()." where id =". $row['id']) ;
    		$command->execute();
    		continue;
    		}*/
    		/*	if ($isImmediate === 'N' and !in_array($row['uid'],$activeUsersPuidArr)){
    		 //非活跃用户，更新，跳过当前循环
    		$command = $connection->createCommand("update saas_aliexpress_autosync set status=4 ,last_time=".time()." where id =". $row['id']) ;
    		$command->execute();
    		continue;
    		}*/
    
    
    		$puid = $row['uid'];
    		$autoSyncId = $row['id'];
    		echo "puid:$puid autoSyncId:$autoSyncId \n";
    		$logTimeMS1 = TimeUtil::getCurrentTimestampMS(); //获取当前时间戳，毫秒为单位，该数值只有比较意义。
    		//1. 先判断是否可以正常抢到该记录
    		$command = $connection->createCommand("update saas_aliexpress_autosync_v2 set status=1 where id =" . $row['id'] . " and status<>1 ");
    		$affectRows = $command->execute();
    		if ($affectRows <= 0) continue; //抢不到
    		$logTimeMS2 = TimeUtil::getCurrentTimestampMS();
    		echo "aliexpress_get_listing_onselling gotit puid=$puid start \n";
    		\Yii::info("aliexpress_get_listing_onselling gotit id=$autoSyncId,puid=$puid start", "file");
    		$logPuidTimeMS1 = TimeUtil::getCurrentTimestampMS();
    		//2. 抢到记录
    		$hasGotRecord = true;
    		$SAA_obj = SaasAliexpressAutosyncV2::findOne($autoSyncId);
    		$sellerloginid = $SAA_obj->sellerloginid;
     
    
    		// 检查授权是否过期或者是否授权,返回true，false
    		$a = AliexpressInterface_Helper_Qimen::checkToken($sellerloginid);
    		if ($a) {
    			echo $SAA_obj->sellerloginid . "\n";
    			$api = new AliexpressInterface_Api_Qimen();
    			$page = 1;
    			$pageSize = 50;
    			// 是否全部同步完成
    			$success = true;
    			$hasDeleted = false;
    			$tool_item = 0;
    			do {
    				// 接口传入参数
    				$param = ['id' => $sellerloginid, 'aeop_a_e_product_list_query' => json_encode(['current_page' => $page, 'page_size' => $pageSize, 'product_status_type' => $type])];
	   				echo "page:$page pageSize:$pageSize type:$type " . "\n";
    				// 调用接口获取订单列表
    				$logTimeMS3 = TimeUtil::getCurrentTimestampMS();
    				try {
    					$result = $api->findProductInfoListQuery($param);
    				} catch (\Exception $exApi) {
    					$result = array();
    					$success = false;
    					$result['error_message'] = print_r($exApi, true);
    				}
    				$logTimeMS4 = TimeUtil::getCurrentTimestampMS();
    				// 判断是否有订单
    				if (isset ($result['product_count']) && isset($result ['aeop_a_e_product_display_d_t_o_list']) ) {
    					echo $result ['product_count'] . "\n";
    					if ($result ['product_count'] > 0) {
    						echo "page:$page pageSize:$pageSize type:$type product_count:" . count($result ['aeop_a_e_product_display_d_t_o_list']) . "\n";
    						$tool_item += count($result ['aeop_a_e_product_display_d_t_o_list']);
    						$batchInsertDatas = array();
    						$nowTime = time();
    						foreach ($result ['aeop_a_e_product_display_d_t_o_list'] as $one) {
								$gmtCreate = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($one ['gmt_create']);
	   							if(empty($one['gmt_modified'])){
	   							    $gmtModified = 0;
	   							}else{
	   								$gmtModified = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($one ['gmt_modified']);
	   							}
	   							if(empty($one['ws_offline_date'])){
	   							    $WOD = 0;
	   							}else{
	   								$WOD = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($one ['ws_offline_date']);
	   							}
	   							
	   							if ($one['image_u_r_ls']!= '') {
	    							$photo_arr = explode( ';',$one['image_u_r_ls'] );
	    							$photo_primary = $photo_arr[0];
	   								if( count( $photo_arr )==1 ){
	   									$imageurls= '';
	   								}else{
	   									unset($photo_arr[0]);
	   									$imageurls= implode(";",$photo_arr);
									}
								} else {
	   								$photo_primary= '';
	   								$imageurls= '';
								}
    							$batchInsertData = array();
								$batchInsertData["productid"] = $one['product_id'];
								//$batchInsertData["freight_template_id"] = isset($one['freightTemplateId']) ? $one['freightTemplateId'] : '';
								$batchInsertData["owner_member_seq"] = $one['owner_member_seq'];
	    						$batchInsertData["subject"] = $one['subject'];
	    						$batchInsertData["photo_primary"] = $photo_primary;
	    						$batchInsertData["imageurls"] = $imageurls;
	    						$batchInsertData["selleruserid"] = $SAA_obj->sellerloginid;
	    						$batchInsertData["ws_offline_date"] = $WOD;
	    						$batchInsertData["product_min_price"] = $one['product_min_price'];
	    						$batchInsertData["ws_display"] = empty($one['ws_display'])?"":$one['ws_display'];
	    						$batchInsertData["product_max_price"] = $one['product_max_price'];
	    						$batchInsertData["gmt_modified"] = $gmtModified;
    							$batchInsertData["gmt_create"] = $gmtCreate;
    							$batchInsertData["sku_stock"] = 0;
    							$batchInsertData["created"] = $nowTime;
    							$batchInsertData["updated"] = $nowTime;
    							$batchInsertData["product_status"]= 1;
       							//当前productid的商品状态和数据库中的对比,如果不一致,则删除listing和detail中的
    							$listingsx = AliexpressListing::find()->where(['productid' => $one['product_id']])->asArray()->one();
    							if( !empty( $listingsx ) ){
    								if( $listingsx['product_status']!=1 ){
    									//删除
    									AliexpressListing::deleteAll(['productid' => $one['product_id']]);
    									AliexpressListingDetail::deleteAll(['productid' => $one['product_id']]);
    								}
    							}
    
    							//json
    							$md5_json_data= md5( json_encode( $one ) );
    							//save queue_product_info_md5
    							$query = $queue->createCommand("SELECT * FROM queue_product_info_md5 WHERE product_id= '".$one['product_id']."' ")->query();
    							$re= $query->read();
    							if( empty( $re ) ){
    								AliexpressListing::deleteAll(['productid' => $one['product_id']]);
    								AliexpressListingDetail::deleteAll(['productid' => $one['product_id']]);
    								//insert
    								$insert= "INSERT INTO queue_product_info_md5( `product_id`,`listen_md5`,`selleruserid` )VALUES( '".$one['product_id']."','{$md5_json_data}','{$sellerloginid}' )";
    								$queue->createCommand( $insert )->execute();
    								$batchInsertDatas[] = $batchInsertData;
	    						}else{
	    							//当保存的md5 和 现在的md5 不一致,才修改等操作
	    							// TODO 私下重新拉取要注意去掉这个判断
	    							$alerdy_save_md5= $re['listen_md5'];
	    							if( $alerdy_save_md5!=$md5_json_data ) {
		    							//update
		    							$update = "UPDATE queue_product_info_md5 SET listen_md5='{$md5_json_data}',listen_detail_md5 ='' WHERE id=" . $re['id'];
	    								$queue->createCommand($update)->execute();
	    								//delete
	    								AliexpressListing::deleteAll( ['productid'=>$one['product_id']] );
	    								AliexpressListingDetail::deleteAll( ['productid'=>$one['product_id']] );
	    								$batchInsertDatas[] = $batchInsertData;
    								}
    							}
    						}
    						echo "insert listing count:".count($batchInsertDatas) . "\n";
    						SQLHelper::groupInsertToDb("aliexpress_listing", $batchInsertDatas);
   
    					}
    				} else {
    					$success = false;
    				}
    				$logTimeMS5 = TimeUtil::getCurrentTimestampMS();
    				$page++;
    				$p = isset($result['total_page']) ? $result['total_page'] : 0;
      			} while ($page <= $p);
    			// 是否全部同步成功
    			if ($success) {
    				$SAA_obj->last_time = time();
    				$SAA_obj->status = 2;
    				$SAA_obj->times = 0;
    				$SAA_obj->order_item = $tool_item;
    				$bool = $SAA_obj->save(false);
    				if (!$bool) {
    					echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
    				}
    			} else {
    				$SAA_obj->message = isset($result ['error_message']) ? $result ['error_message'] : '接口返回结果错误V2' . print_r($result, true);
    				$SAA_obj->last_time = time();
	    			$SAA_obj->status = 3;
    				$SAA_obj->times += 1;
    				$SAA_obj->order_item = 0;
    				$bool = $SAA_obj->save(false);
    				if (!$bool) {
    					echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
               		}
    			}
    			echo 'start sync detail'.PHP_EOL;
   				self::getListingDetail( $puid, $sellerloginid );
   				echo 'end sync detail'.PHP_EOL;
	    	} else {
	    		echo $SAA_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
	    		$SAA_obj->message = $SAA_obj->sellerloginid . ' Unauthorized or expired!';
	    		$SAA_obj->last_time = time();
	    		$SAA_obj->status = 3;
 		   		$SAA_obj->times += 1;
 		   		$SAA_obj->order_item = 0;
 		   		$bool = $SAA_obj->save(false);
 	           if (!$bool) {
      	          echo __FUNCTION__ . "STEP 7 : " . var_export($SAA_obj->errors, true);
    			}
    		}
 	   }
  	  return $hasGotRecord;
    }
    
    public static function getListingDetail($puid, $sellerloginid){
    	$queue = Yii::$app->db_queue;
    	$db= Yii::$app->db;
    	$api = new AliexpressInterface_Api_Qimen ();
    	 
    
    	$listings = AliexpressListing::find()->where(['selleruserid' => $sellerloginid])->asArray()->all();
    	echo "aliexpress_listing count:" . count($listings) . PHP_EOL;
    
    	//开启验证修改后,以下的删除功能注释
    	//AliexpressListingDetail::deleteAll(['selleruserid' => $sellerloginid]);
    
    	foreach ($listings as $row) {
    		$productid = $row["productid"];
    		if( $productid==0 ){
    			continue;
    		}
    		$productInfo = $api->findAeProductById(array('id' => $sellerloginid, 'product_id' => $productid));
    		if (empty($productInfo['success']) || $productInfo['success'] != 1) {
    			//没有获取到任何信息,删除listing表中的数据
    			AliexpressListing::deleteAll(['productid' => $productid]);
    			AliexpressListingDetail::deleteAll(['productid' => $productid]);
    			echo "puid:$puid,sellerloginid:$sellerloginid,productid:$productid not get detail. message:" . $productInfo['error_message'] . PHP_EOL;
    			continue;
    		}
    		//加密详细信息
    		$listen_detail_md5= md5( json_encode( $productInfo ) );
    		//通过商品ID ,获取加密表数据
    		$sql= "SELECT * FROM queue_product_info_md5 WHERE product_id='{$productid}' ";
    		$query= $queue->createCommand( $sql )->query();
    		$rs= $query->read();
    		if( empty( $rs )  ){
	    		$insert= "INSERT INTO queue_product_info_md5 (`product_id`,`listen_detail_md5`,`selleruserid`)VALUES ('{$productid}','{$listen_detail_md5}','{$sellerloginid}')";
	    		$queue->createCommand( $sql )->execute();
    		}else{
	    		$alerdy_listen_detail_md5= $rs['listen_detail_md5'];
	    		if( $alerdy_listen_detail_md5!=$listen_detail_md5 ){
		    		//update
		    		$sql= "UPDATE queue_product_info_md5 SET listen_detail_md5='{$listen_detail_md5}' WHERE id= ".$rs['id'];
		    
		    		$queue->createCommand( $sql )->execute();
		    		//delete
		    			AliexpressListingDetail::deleteAll(['productid' => $productid]);
	    		}else{
	    			continue;
	    		}
    		}
    		
    		$aliexpressListDetail = new AliexpressListingDetail;
    		$aliexpressListDetail->productid = $productid;
    
    		if (!empty($productInfo["category_id"])) {
    			$aliexpressListDetail->categoryid = $productInfo["category_id"];
    		}
    		$aliexpressListDetail->selleruserid = $sellerloginid;
    		if (!empty($productInfo["product_price"])) {
    			$aliexpressListDetail->product_price = $productInfo["product_price"];
    		}
    		if (!empty($productInfo["gross_weight"])) {
    			$aliexpressListDetail->product_gross_weight = $productInfo["gross_weight"];
    		}
    		if (!empty($productInfo["package_length"])) {
    			$aliexpressListDetail->product_length = $productInfo["package_length"];
    		}
	    	if (!empty($productInfo["package_width"])) {
	    		$aliexpressListDetail->product_width = $productInfo["package_width"];
	    	}
	    	if (!empty($productInfo["package_height"])) {
	    		$aliexpressListDetail->product_height = $productInfo["package_height"];
	    	}
	    	if (!empty($productInfo["currency_code"])) {
	    		$aliexpressListDetail->currencyCode = $productInfo["currency_code"];
	    	}
    		if (!empty($productInfo["aeop_ae_product_propertys"])) {
    			//兼容旧版本
    			foreach($productInfo["aeop_ae_product_propertys"] as &$item){
    				$item['attrNameId'] = empty($item['attr_name_id']) ? '' : $item['attr_name_id'];
    				$item['attrName'] = empty($item['attr_name']) ? '' : $item['attr_name'];
    				$item['attrValueId'] = empty($item['attr_value_id']) ? '' : $item['attr_value_id'];
    				$item['attrValue'] = empty($item['attr_value']) ? '' : $item['attr_value'];
    			}
    			
    			$aliexpressListDetail->aeopAeProductPropertys = json_encode($productInfo["aeop_ae_product_propertys"]);
    		} else {
    			$aliexpressListDetail->aeopAeProductPropertys = json_encode(array());
    		}
    
	    	if (!empty($productInfo["aeop_ae_product_s_k_us"])) {
	    		//兼容旧版本
	    		foreach($productInfo["aeop_ae_product_s_k_us"] as &$item){
	    			if(!empty($item['aeop_s_k_u_property_list'])){
	    				foreach($item["aeop_s_k_u_property_list"] as $i){
	    					$item["aeopSKUProperty"][] = [
	    					'skuPropertyId' => empty($i['sku_property_id']) ? '' : $i['sku_property_id'],
	    					'propertyValueId' => empty($i['property_value_id']) ? '' : $i['property_value_id'],
	    					'propertyValueDefinitionName' => empty($i['property_value_definition_name']) ? '' : $i['property_value_definition_name'],
	    					'skuImage' => empty($i['sku_image']) ? '' : $i['sku_image'],
	    					];
	    				}
	    			}
	    			else{
	    				$item["aeopSKUProperty"] = [];
	    			}
	    			$item['skuPrice'] = empty($item['sku_price']) ? '' : $item['sku_price'];
	    			$item['skuCode'] = empty($item['sku_code']) ? '' : $item['sku_code'];
	    			$item['skuStock'] = empty($item['sku_stock']) ? '' : $item['sku_stock'];
	    			$item['ipmSkuStock'] = empty($item['ipm_sku_stock']) ? '' : $item['ipm_sku_stock'];
	    			$item['currencyCode'] = empty($item['currency_code']) ? '' : $item['currency_code'];
	    		}
	    		
	    		$aliexpressListDetail->aeopAeProductSKUs = json_encode($productInfo["aeop_ae_product_s_k_us"]);
	    		$arr_sku= $productInfo['aeop_ae_product_s_k_us'];
	    		$skucode_arr= array();
	    		foreach( $arr_sku as $vss_sku ){
	    			if( isset($vss_sku['sku_code']) && $vss_sku['sku_code']!='' ){
	    				$skucode_arr[]= $vss_sku['sku_code'];
	   				}
	    		}
	   	 		if( !empty( $skucode_arr ) ){
	    			$skucode_str= implode(';',$skucode_arr);
			    }else{
			    	$skucode_str= '';
			    }
    			$aliexpressListDetail->sku_code= $skucode_str;
    
		    } else {
		   		$aliexpressListDetail->aeopAeProductSKUs = json_encode(array());
		    }
		    if (!empty($productInfo["detail"])) {
		   		$aliexpressListDetail->detail = $productInfo["detail"];
		    }
    		$aliexpressListDetail->listen_id = $row['id'];
    		if( !empty( $productInfo['delivery_time'] ) ){
		    	$aliexpressListDetail->delivery_time = $productInfo['delivery_time'];
		    }
    		if( !empty( $productInfo['package_type'] ) ){
		   	 	$aliexpressListDetail->package_type = $productInfo['package_type'];
		    }
  			if( !empty( $productInfo['lot_num'] ) ){
    			$aliexpressListDetail->lot_num = $productInfo['lot_num'];
    		}
		    if( !empty( $productInfo['is_pack_sell'] ) ){
		  		$aliexpressListDetail->isPackSell = $productInfo['is_pack_sell'];
		    }
	    	if( !empty( $productInfo['reduce_strategy'] ) ){
	    		$aliexpressListDetail->reduce_strategy = $productInfo['reduce_strategy'];
	    	}
	    	if( !empty( $productInfo['product_unit'] ) ){
	    		$aliexpressListDetail->product_unit = $productInfo['product_unit'];
	    	}
	    	if( !empty( $productInfo['ws_valid_num'] ) ){
	    		$aliexpressListDetail->wsValidNum = $productInfo['ws_valid_num'];
		    }
	    	if( !empty( $productInfo['currency_code'] ) ){
	    		$aliexpressListDetail->currencyCode = $productInfo['currency_code'];
	 	   }
		    //if( !empty( $productInfo['promiseTemplateId'] ) ){
		    $aliexpressListDetail->promise_templateid = $productInfo['promise_template_id'];
		    //}
		    if( !empty( $productInfo['group_Ids'] ) ){
		    	$aliexpressListDetail->product_groups = implode(',',$productInfo['group_Ids']);
		    }
    		$aliexpressListDetail->save(false);
    
	    	if( !empty( $productInfo['freight_template_id'] ) ){
	    		AliexpressListing::updateAll( ['freight_template_id'=>$productInfo['freight_template_id']],'id='.$row['id'] );
	    	}
    	}
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *  部分puid每天同步两天内的订单
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/05/18		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public static function getOrderListManualByUid(){
    	echo date('Y-m-d H:i:s') . ' manual script start '.PHP_EOL;
    	$connection = Yii::$app->db;
    
    	$nowTime = time();
    	//查询同步控制表所有manual队列
    	$sql = "select `id` from  `saas_aliexpress_autosync_v2` where `is_active` = 1 AND `status` <>1 AND `times` < 10 AND `type`='manual' AND next_time < {$nowTime}  order by `next_time` ASC limit 100 ";
    	$dataReader = $connection->createCommand($sql)->query();
    	echo date('Y-m-d H:i:s') . ' select count ' . $dataReader->count() . PHP_EOL;
    	while (false !== ($row = $dataReader->read())) {
    		//1. 先判断是否可以正常抢到该记录
    		$nowTime = time();
    		$affectRows = $connection->createCommand("update saas_aliexpress_autosync_v2 set status=1,last_time={$nowTime} where id ={$row['id']} and status<>1 ")->execute();
    		if ($affectRows <= 0) {
    			continue; //当前这条抢不到
    		}
    
    		//2. 抢到记录，设置同步需要的参数
    		$hasGotRecord = true;
    		$SAA_obj = SaasAliexpressAutosyncV2::findOne($row['id']);
    		if (empty($SAA_obj)) {
    			echo date('Y-m-d H:i:s') . ' Exception ' . $row['id'] . PHP_EOL;
    			continue;
    		}
    		$puid = $SAA_obj->uid;
    		$sellerloginid = $SAA_obj->sellerloginid;
    		$timeMS1 = TimeUtil::getCurrentTimestampMS();
    		echo date('Y-m-d H:i:s') . " step1 puid=$puid,sellerloginid=$sellerloginid" . PHP_EOL;
    
    		// 检查授权是否过期或者是否授权,返回true，false
    		if (!AliexpressInterface_Helper_Qimen::checkToken($SAA_obj->sellerloginid)) {
    			$SAA_obj->message = " {$SAA_obj->sellerloginid} Unauthorized or expired!";
    			$SAA_obj->last_time = time();
    			$SAA_obj->next_time = time() + 1200;
    			$SAA_obj->status = 3;
    			$SAA_obj->times += 1;
    			$bool = $SAA_obj->save(false);
    			if (!$bool) {
    				echo __FUNCTION__ . "STEP 2 : " . var_export($SAA_obj->errors, true);
    			}
    			continue;
    		}
    
    		$api = new AliexpressInterface_Api_Qimen();
    		
    		echo date('Y-m-d H:i:s') . " step3 puid=$puid,sellerloginid=$sellerloginid" . PHP_EOL;
    		$timeMS2 = TimeUtil::getCurrentTimestampMS();
    
    		//分页设置
    		$page = 1;
    		$pageSize = 50;
    		// 是否全部同步完成
    		$success = true;
    		 
    		#####################################
    		$time = time();
    		$start_time = $time - 86400 * 2;   //同步两天内的订单
    		$end_time = $time;
    
    		$format_start_time = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
    		$format_end_time = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
    		echo date('Y-m-d H:i:s') . " step4 puid=$puid,sellerloginid=$sellerloginid,start_time=$start_time,end_time=$end_time,format_start_time=$format_start_time,format_end_time=$format_end_time" . PHP_EOL;
    		//获取最后发货时间
    		$order_ids = [];
    		$total_item = 0;
    		//$api_types = ['create', 'modified'];
    		$api_types = ['create'];

    		foreach($api_types as $api_type){
    			$page = 1;
	    		do {
	    			$api_time = time();//接口调用时间
	    			// 接口传入参数
	    			$param = ['id' => $sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, $api_type.'_date_start' => $format_start_time, $api_type.'_date_end' => $format_end_time])];
	    			// 调用接口获取订单列表
	    			$result = $api->findOrderListQuery($param);
	    			//echo "getOrderListManualByUid--findOrderListQuery--$api_type--".json_encode($result);
	    			// 判断是否有订单
	    			if (!isset ($result['total_item'])) {
	    				$success = false;
	    				echo PHP_EOL."getOrderListByTime--findOrderListQuery--$api_type--err1--".PHP_EOL;
	    				echo json_encode($result).PHP_EOL;
	    				break;
	    			}
	    			else if($result['total_item'] > 0 && empty($result['order_list'])) {
	    				$success = false;
	    				echo PHP_EOL."getOrderListByTime--findOrderListQuery--$api_type--err2--".PHP_EOL;
	    				echo json_encode($result).PHP_EOL;
	    				break;
	    			}
	    			else if(empty($result['order_list'])) {
	    				break;
	    			}
	    			echo "$api_type, total_item: ".$result['total_item'].", order_list: ".count($result['order_list']).PHP_EOL;
	    			
	    			print_r ($result ['order_list']);
	    			foreach ($result ['order_list'] as $order) {
	    				$order_info = array();
						$orderid = number_format($order['order_id'], 0, '', '');
						//排除重复的
						if(in_array($orderid, $order_ids)){
							continue;
						}
						$order_ids[] = $orderid;
						echo "order_id: $orderid".PHP_EOL;
						$order_info = [
							'biz_type' => $order['biz_type'],
							'gmt_create' => $order['gmt_create'],
							'memo' => '',
							'order_id' => $order['order_id'],
							'order_status' => $order['order_status'],
							'product_list' => [],
							'day' => isset($order['left_send_good_day']) ? $order['left_send_good_day'] : 0,
							'hour' => isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] : 0,
							'min' => isset($order['left_send_good_min']) ? $order['left_send_good_min'] : 0,
							'api_time' => $api_time,
						];
	
						$logisticsServiceName_arr= array();     //买家物流信息
						$memo_arr= array();                     //买家备注
						$sendGoodsOperator_arr = array();       //发货类型
						if (isset($order['product_list'])) {
							foreach ($order['product_list'] as $pl) {
								$productid = strval($pl['product_id']);
								
								$order_info['product_list'][] = [
									'child_id' => number_format($pl['child_id'], 0, '', ''),
									'money_back3x' => $pl['money_back3x'],
									'product_count' => $pl['product_count'],
									'product_id' => $productid,
									'product_img_url' => empty($pl['product_img_url'])?"":$pl['product_img_url'],//dzt20191018
									'product_name' => $pl['product_name'],
									'product_snap_url' => empty($pl['product_snap_url'])?"":$pl['product_snap_url'],//dzt20191018
									'product_unit' => $pl['product_unit'],
									'product_unit_price' => empty($pl['product_unit_price']['cent_factor']) ? $pl['product_unit_price']['amount'] : $pl['product_unit_price']['cent'] / $pl['product_unit_price']['cent_factor'],
									'product_unit_price_cur' => $pl['product_unit_price']['currency_code'],
									'sku_code' => empty($pl['sku_code']) ? '' : $pl['sku_code'],
									'son_order_status' => $pl['son_order_status'],
								];
								
								//客选物流
								if (isset($pl['logistics_service_name'])) {
									$logisticsServiceName = $pl['logistics_service_name'];
									$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
								}
								//买家备注
								if( isset($pl['memo']) ){
									$pmemo= str_replace("'","",$pl['memo']);
									if( $pmemo=='' ){
										$pmemo= '无';
									}
									$memo_arr[]= $pmemo;
									//$logisticsServiceName_arr["user_message"][$productid] = $memo;
								}
								//发货类型
								if (isset($pl['send_goods_operator'])) {
									$sendGoodsOperator = $pl['send_goods_operator'];
									$sendGoodsOperator_arr[$productid] = $sendGoodsOperator;
								}
							}
						}
	    				//客选物流
	    				if(!empty($logisticsServiceName_arr)){
	    					$order_info['logisticsServiceName_arr'] = $logisticsServiceName_arr;
	    				}
	    				//买家备注
	    				if(!empty($memo_arr)){
	    					$order_info['memo_arr'] = $memo_arr;
	    				}
	    				//发货类型
	    				if(!empty($sendGoodsOperator_arr)){
	    					$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr;
	    				}
						print_r ($order_info);
						
						$newid = 0;
						$QAG_obj = QueueAliexpressGetorder4V2::findOne(['orderid' => $orderid]);
						if (isset ($QAG_obj)) {
							$newid= $QAG_obj->id;
						} else {
							$QAG_obj = new QueueAliexpressGetorder4V2 ();
							$QAG_obj->uid = $SAA_obj->uid;
							$QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
							$QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
							$QAG_obj->order_status = $order['order_status'];
							$QAG_obj->orderid = $orderid;
							$QAG_obj->order_info = json_encode($order_info);
							$QAG_obj->gmtcreate = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create']);
							$QAG_obj->create_time = $time;
							$QAG_obj->update_time = $time;
							$bool = $QAG_obj->save(false);
							if (!$bool) {
								$success = false;
								echo "step5 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=".$result['total_item']." error_msg : " . var_export($QAG_obj->errors, true).PHP_EOL;
								break;
							}
							$newid = $QAG_obj->primaryKey;
						}
						
						//同步数据到用户表的小老板订单中
						$getorder4_obj= QueueAliexpressGetorder4V2::findOne( $newid );
						$param = ['id' => $sellerloginid, 'param1' => json_encode(['order_id' => $orderid])];
						$res = $api->findOrderById($param );
						if (!empty($res['error_response']) || empty ($res)) {
							$success = false;
							echo "getOrderListManual--findOrderById--$api_type--err--".json_encode($res);
							break;
						}
						$res['id'] = strval($getorder4_obj->orderid);
						$res["sellerOperatorLoginId"]= $getorder4_obj->sellerloginid;
						$r = AliexpressInterface_Helper_V2::saveAliexpressOrder ( $getorder4_obj, $res );
						if ($r['success'] != 0 || !isset($result['order_status'])) {
							$success = false;
							echo PHP_EOL."getOrderListManual--update--order--$api_type--err--".$r['success'].'--'.$r['message'].PHP_EOL;
							continue;
						}
					
						//设置客选物流
						if (!empty($logisticsServiceName_arr)) {
							list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
						}
							
						//判断是否存在剩余发货时间的3个属性
						$leftSendGoodDay = isset($order['left_send_good_day']) ? $order['left_send_good_day'] * 86400 : 0;
						$leftSendGoodHour = isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] * 3600 : 0;
						$leftSendGoodMin = isset($order['left_send_good_min']) ? $order['left_send_good_min'] * 60 : 0;
							
						//如果都是0的话,不处理最后发货时间,有一个不是0,才去处理
						if( $leftSendGoodDay > 0 || $leftSendGoodHour > 0 || $leftSendGoodMin > 0 ){
							//在接口调用时间上,加上秒数就是最后发货时间啦
							$fulfill_deadline = ceil($leftSendGoodDay + $leftSendGoodHour + $leftSendGoodMin + $api_time);
							//更新掉字段
							Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_source_order_id='{$orderid}'")->execute();
						}
							
						//设置买家备注
						$memo= '';
						if( !empty( $memo_arr ) ){
							$memo_eof= false;
							foreach( $memo_arr as $memo_vss ){
								if( $memo_vss!='无' ){
									$memo_eof= true;
									break;
								}
							}
							if( $memo_eof===true ){
								foreach( $memo_arr as $key=>$memo_vss ){
									$count= $key+1;
									$memo.= "商品{$count}:{$memo_vss};";
								}
							}
						}
						if( $memo != '' ) {
							//需要获取自增id
							$ro = OdOrder::findOne(['order_source_order_id' => $orderid]);
							if (!empty($ro)) {
								$sysTagRt = OrderTagHelper::setOrderSysTag($ro->order_id, 'pay_memo');
								if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400') {
									//echo '\n' . $ro->order_id . ' insert pay_memo failure :' . $sysTagRt['message'];
								}
							}
							$rof= Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET user_message='{$memo}' WHERE order_source_order_id='{$orderid}'")->execute();
						}
						$total_item++;
	    			}
	    			$page++;
	    			$p = ceil($result['total_item'] / 50);
	    		} while ($page <= $p);
	    		
	    		if(!$success){
	    			break;
	    		}
    		}
    
    		echo date('Y-m-d H:i:s') . " step5 puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$total_item" . PHP_EOL;
    		$timeMS3 = TimeUtil::getCurrentTimestampMS();
    		// 是否全部同步成功
    		if ($success) {
    			$SAA_obj->start_time = $start_time;
    			$SAA_obj->end_time = $end_time;
    			$SAA_obj->order_item = $total_item;
    			$SAA_obj->status = 2;
    			$SAA_obj->times = 0;
    			$SAA_obj->message = '';
    			$SAA_obj->next_time = time() + 3600 * 10;
    
    		} else {
    			$SAA_obj->status = 3;
    			$SAA_obj->times += 1;
    			$SAA_obj->next_time = time() + 1800;
    		}
    		$SAA_obj->last_time = $time;
    		$bool = $SAA_obj->save(false);
    		if (!$bool) {
    			echo "step6 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$total_item error_msg : " . var_export($SAA_obj->errors, true);
    		}
    		$timeMS4 = TimeUtil::getCurrentTimestampMS();
    
    		$timeStr = "t4_t3=" . ($timeMS4 - $timeMS3) . ",t3_t2=" . ($timeMS3 - $timeMS2) . ",t2_t1=" . ($timeMS2 - $timeMS1) . ",t4_t1=" . ($timeMS4 - $timeMS1);
    
    		echo date('Y-m-d H:i:s') . " step6 one shop end puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$total_item " . $timeStr . PHP_EOL;
    
    	}
    	echo date('Y-m-d H:i:s') . ' getOrderListManual script end '.PHP_EOL;
    }
    
    /**
     +------------------------------------------------------------------
     *  接收ali的推送信息
     +------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/05/31		初始化
     +------------------------------------------------------------------
     **/
    public static function receiveAliOrderPush(){
    	 
    	//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
    	$currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListV2Version", 'NO_CACHE');
    	if (empty($currentAliexpressGetOrderListVersion)) {
    		$currentAliexpressGetOrderListVersion = 0;
    	}
    	//如果自己还没有定义，去使用global config来初始化自己
    	if (empty(self::$aliexpressGetOrderListV2Version)) {
    		self::$aliexpressGetOrderListV2Version = $currentAliexpressGetOrderListVersion;
    	}
    	//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
    	if (self::$aliexpressGetOrderListV2Version <> $currentAliexpressGetOrderListVersion) {
    		exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListV2Version . " exits for using new version $currentAliexpressGetOrderListVersion.");
    	}
    
    	self::$cronJobId++;
    	echo self::getTime().' receiveAliOrderPush script start '.self::$cronJobId.PHP_EOL;
    	
    	$api = new AliexpressInterface_Api_Qimen();
    	$response = $api->tmcMessagesConsume(['id' => 'cn1510671045', 'group_name' => 'default', 'quantity' => 100]);
    	if(empty($response) || !empty($response['error_message'])){
    		echo self::getTime().' get message err: '.json_encode($response).PHP_EOL.PHP_EOL;
    		//self::insertAutoOrderDb($response, 'get message err');
    		return false;
    	}
    	$msg_ids = '';
    	echo 'search count: '.count($response).PHP_EOL;
    	foreach($response as $one){
    	    try{
        	    if(empty($one['content'])){
        	        echo self::getTime().' return info err: '.json_encode($one).PHP_EOL.PHP_EOL;
        	        self::insertAutoOrderDb($one, 'content is null');
        	        return false;
        	    }
        	    $content = json_decode($one['content'], true);
        	    if(empty($content) || empty($content['order_id'])){
        	        echo self::getTime().' return content err: '.json_encode($one).PHP_EOL.PHP_EOL;
        	        self::insertAutoOrderDb($one, 'order_id is null');
        	        return false;
        	    }
        	    $order_change_time = strtotime($content['order_change_time']);
        	    //判断是否已存在非同步中
        	    $obj = QueueAliexpressAutoOrderV2::findOne("order_id='".$content['order_id']."' and status<>1");
        	    if(!empty($obj)){
        	        //当消息订单时间小于上次拉取的订单时间，则跳过
        	        if($order_change_time < $obj->order_change_time){
        	            continue;
        	        }
        	    }
        	    else{
        	        $obj = new QueueAliexpressAutoOrderV2();
        	        $obj->sellerloginid = $one['user_nick'];
        	        $obj->order_id = $content['order_id'];
        	        $obj->create_time = time();
        	    }
        	    $obj->order_status = $content['current_status'];
        	    $obj->last_status = empty($content['last_status']) ? '' : $content['last_status'];
        	    $obj->order_change_time = $order_change_time;
        	    $obj->msg_id = number_format($one['id'], 0, '', '');
        	    $obj->order_type = $one['topic'];
        	    $obj->gmtBorn = strtotime($one['pub_time']);
        	    $obj->ajax_message = $one['content'];
        	    $obj->status = 0;
        	    $obj->message = '';
        	    $obj->update_time = time();
        	    $obj->times = 0;
        	    if(!$obj->save(false)){
        	        echo self::getTime().' save err: '.json_encode($obj->errors).PHP_EOL.PHP_EOL;
        	        self::insertAutoOrderDb($one, 'save error');
        	    }
        	    
        	    $msg_ids .= $obj->msg_id.',';
    	    }
    	    catch(\Exception $ex){
    	    	echo self::getTime().' get message catch: '.$ex->getMessage().PHP_EOL.PHP_EOL;
    	    	self::insertAutoOrderDb($one, $ex->getMessage());
    	    }
    	}
    	
    	//确认消息接收
    	if(!empty($msg_ids)){
    	    $msg_ids = rtrim($msg_ids, ',');
    	    $response = $api->tmcMessagesConfirm(['id' => 'cn1510671045', 'group_name' => 'default', 's_message_ids' => $msg_ids]);
    	}
    	
    	echo self::getTime().' receiveAliOrderPush script end '.PHP_EOL.PHP_EOL;
    	
    	return true;
    }
    
    /**
     +------------------------------------------------------------------
     *  从queue_aliexpress_auto_order推送结果表中,处理几个状态的订单
     +------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/06/01		初始化
     +------------------------------------------------------------------
     **/
    public static function getAliAutoOrder(){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListV2Version", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion)) {
        	$currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListV2Version)) {
        	self::$aliexpressGetOrderListV2Version = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListV2Version <> $currentAliexpressGetOrderListVersion) {
        	exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListV2Version . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        
        self::$cronJobId++;
        echo self::getTime().' getAliAutoOrder script start '.self::$cronJobId.PHP_EOL;
        
        $connection = Yii::$app->db_queue;
        $now = time();
        $hasGotRecord = false;
        //查新推送记录
        $sql = 'select id, order_id from  `queue_aliexpress_auto_order_v2` where `status` <> 1 AND `times` < 10 and next_time<'.$now.' limit 20';
        $dataReader = $connection->createCommand($sql)->query();
        echo self::getTime().' select count '.$dataReader->count().PHP_EOL;
        while (false !== ($row = $dataReader->read())) {
        	$now = time();
        	//1. 先判断是否可以正常抢到该记录
        	$affectRows = $connection->createCommand("update `queue_aliexpress_auto_order_v2` set status=1,update_time={$now} where id ={$row['id']} and status<>1 ")->execute();
        	if ($affectRows <= 0) {
        		continue; //抢不到
        	}
        
        	//2. 抢到记录，设置同步需要的参数
        	$hasGotRecord = true;
        	$AUTO_obj = QueueAliexpressAutoOrderV2::findOne($row['id']);
        	if (empty($AUTO_obj)) {
        		echo self::getTime().' exception '.$row['order_id'].PHP_EOL.PHP_EOL;
        		continue;
        	}
        
        	echo self::getTime().' api start '.$AUTO_obj->order_id.PHP_EOL;
        	// 检查授权是否过期或者是否授权,返回true，false
        	if (!AliexpressInterface_Helper_Qimen::checkToken($AUTO_obj->sellerloginid)) {
        		echo self::getTime()." {$AUTO_obj->sellerloginid} Unauthorized or expired!".PHP_EOL.PHP_EOL;
        		$AUTO_obj->message .= " {$AUTO_obj->sellerloginid} Unauthorized or expired!";
        		$AUTO_obj->last_time = $now;
        		$AUTO_obj->status = 3;
        		$AUTO_obj->times += 1;
        		if (!$AUTO_obj->save(false)) {
        			echo self::getTime()." STEP 1 err: ".var_export($AUTO_obj->errors, true).PHP_EOL;
        		}
        		continue;
        	}
        	
        	//判断是否已解绑，或关闭同步
        	$user = SaasAliexpressUser::findOne(['sellerloginid' => $AUTO_obj->sellerloginid, 'is_active' => 1]);
        	if(empty($user)){
        		echo self::getTime()." {$AUTO_obj->sellerloginid} Unauthorized or expired!".PHP_EOL.PHP_EOL;
        		$AUTO_obj->message = " {$AUTO_obj->sellerloginid} Unauthorized or expired!";
        		$AUTO_obj->last_time = $now;
        		$AUTO_obj->status = 3;
        		$AUTO_obj->times = 10;
        		if (!$AUTO_obj->save(false)) {
        			echo self::getTime()." STEP 2 err: ".var_export($AUTO_obj->errors, true).PHP_EOL;
        		}
        		continue;
        	}
        	//当不活跃用户，延迟到晚上才执行
        	if( $AUTO_obj->next_time == 0 ){
        		if (self::isActiveUser( $user->uid )===false) {
        			echo self::getTime()." {$AUTO_obj->sellerloginid} isNotActiveUser!".PHP_EOL.PHP_EOL;
	        		$AUTO_obj->status = 0;
	        		$AUTO_obj->update_time = time();
	        		$AUTO_obj->next_time = strtotime(date('Y-m-d', time()).' 23:59:59');
	        		if (!$AUTO_obj->save(false)) {
	        			echo self::getTime()." STEP 3 : ".var_export($AUTO_obj->errors, true).PHP_EOL;
	        		}
	        		continue;
        		}
        	}
        	//判断queue_aliexpress_getorder_v2是否存在，如果状态一样，则跳过
        	$getorder = QueueAliexpressGetorderV2::findOne(['sellerloginid' => $AUTO_obj->sellerloginid, 'orderid' => $AUTO_obj->order_id, 'order_status' => $AUTO_obj->order_status]);
        	if(!empty($getorder)){
        		$AUTO_obj->delete();
        		echo self::getTime()." delete orderid:".$AUTO_obj->order_id.PHP_EOL.PHP_EOL;
        		continue;
        	}
        	//判断queue_aliexpress_getorder4_v2是否存在，如果状态一样，则跳过
        	$getorder4 = QueueAliexpressGetorder4V2::findOne(['sellerloginid' => $AUTO_obj->sellerloginid, 'orderid' => $AUTO_obj->order_id, 'order_status' => $AUTO_obj->order_status]);
        	if(!empty($getorder4)){
        		$AUTO_obj->delete();
        		echo self::getTime()." delete orderid:".$AUTO_obj->order_id.PHP_EOL.PHP_EOL;
        		continue;
        	}
        
        	$api = new AliexpressInterface_Api_Qimen ();
        	//获取订单创建时间
        	$res = $api->findOrderById(['id' => $AUTO_obj->sellerloginid, 'param1' => json_encode(['order_id' => $AUTO_obj->order_id])]);
        	if(empty($res['gmt_create'])){
        		$AUTO_obj->message = " findOrderById can not find gmt_create";
        		$AUTO_obj->last_time = $now;
        		$AUTO_obj->status = 3;
        		$AUTO_obj->times += 1;
        		$bool = $AUTO_obj->save(false);
        		if (!$bool) {
        			echo self::getTime()." STEP 4 err: ".var_export($AUTO_obj->errors, true).PHP_EOL.PHP_EOL;
        		}
        		continue;
        	}
        	//获取列表信息
        	$success = false;
        	$msg = '';
        	$page = 1;
        	$pageSize = 50;
        	do {
	        	$format_start_time = self::getLaFormatTime("m/d/Y H:i:s", AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($res['gmt_create']) -10);
	        	$format_end_time = self::getLaFormatTime("m/d/Y H:i:s", AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($res['gmt_create']) + 10);
	        	$order_status_list = [$AUTO_obj->order_status];
	        	$param = ['id' => $AUTO_obj->sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, 'create_date_start' => $format_start_time, 'create_date_end' => $format_end_time ])];
	    		$result = $api->findOrderListQuery($param);
	    		if (empty($result['total_item']) || empty($result['order_list'])) {
	    			break;
	    		}
	    		
    			foreach ( $result ['order_list'] as $order ) {
    				$orderid = number_format($order['order_id'], 0, '', '');
    				if( $orderid == $AUTO_obj->order_id ){
    					$order_info = [
							'biz_type' => $order['biz_type'],
							'gmt_create' => $order['gmt_create'],
							'memo' => '',
							'order_id' => $order['order_id'],
							'order_status' => $order['order_status'],
							'product_list' => [],
							'day' => isset($order['left_send_good_day']) ? $order['left_send_good_day'] : 0,
							'hour' => isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] : 0,
							'min' => isset($order['left_send_good_min']) ? $order['left_send_good_min'] : 0,
							'api_time' => time(),
						];
	
						$logisticsServiceName_arr= array();     //买家物流信息
						$memo_arr= array();                     //买家备注
						$sendGoodsOperator_arr = array();       //发货类型
						if (isset($order['product_list'])) {
							foreach ($order['product_list'] as $pl) {
								$productid = strval($pl['product_id']);
								
								$order_info['product_list'][] = [
									'child_id' => number_format($pl['child_id'], 0, '', ''),
									'money_back3x' => $pl['money_back3x'],
									'product_count' => $pl['product_count'],
									'product_id' => $productid,
									'product_img_url' => empty($pl['product_img_url'])?"":$pl['product_img_url'],//dzt20191018
									'product_name' => $pl['product_name'],
									'product_snap_url' => empty($pl['product_snap_url'])?"":$pl['product_snap_url'],//dzt20191018
									'product_unit' => $pl['product_unit'],
									'product_unit_price' => empty($pl['product_unit_price']['cent_factor']) ? $pl['product_unit_price']['amount'] : $pl['product_unit_price']['cent'] / $pl['product_unit_price']['cent_factor'],
									'product_unit_price_cur' => $pl['product_unit_price']['currency_code'],
									'sku_code' => empty($pl['sku_code']) ? '' : $pl['sku_code'],
									'son_order_status' => $pl['son_order_status'],
								];
								
								//客选物流
								if (isset($pl['logistics_service_name'])) {
									$logisticsServiceName = $pl['logistics_service_name'];
									$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
								}
								//买家备注
								if( isset($pl['memo']) ){
									$pmemo= str_replace("'","",$pl['memo']);
									if( $pmemo=='' ){
										$pmemo= '无';
									}
									$memo_arr[]= $pmemo;
									//$logisticsServiceName_arr["user_message"][$productid] = $memo;
								}
								//发货类型
								if (isset($pl['send_goods_operator'])) {
									$sendGoodsOperator = $pl['send_goods_operator'];
									$sendGoodsOperator_arr[$productid] = $sendGoodsOperator;
								}
							}
						}
	    				//客选物流
	    				if(!empty($logisticsServiceName_arr)){
	    					$order_info['logisticsServiceName_arr'] = $logisticsServiceName_arr;
	    				}
	    				//买家备注
	    				if(!empty($memo_arr)){
	    					$order_info['memo_arr'] = $memo_arr;
	    				}
	    				//发货类型
	    				if(!empty($sendGoodsOperator_arr)){
	    					$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr;
	    				}
						
						$QAG_obj = QueueAliexpressGetorderV2::findOne(['orderid' => $orderid]);
						if(empty($QAG_obj)){
							$QAG_obj = new QueueAliexpressGetorderV2 ();
							$QAG_obj->uid = $user->uid;
							$QAG_obj->sellerloginid = $user->sellerloginid;
							$QAG_obj->aliexpress_uid = $user->aliexpress_uid;
							$QAG_obj->orderid = $orderid;
							$QAG_obj->gmtcreate = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create']);
							$QAG_obj->create_time = $now;
						}
						$QAG_obj->type = 3;
						$QAG_obj->times = 0;
						$QAG_obj->order_status = $order['order_status'];
						$QAG_obj->order_info = json_encode($order_info);
						$QAG_obj->update_time = $now;
						if($QAG_obj->save(false)){
							$success = true;
						}
						
    					echo 'insertid--'.$orderid.PHP_EOL;
    					break;
    				}
    			}
	    		
	    		$page++;
	    		$p = ceil($result['total_item'] / 50);
	    	} while ($page <= $p);
	    	
    		if($success){
    			$AUTO_obj->delete();
    			echo self::getTime()." delete orderid:".$AUTO_obj->order_id.PHP_EOL.PHP_EOL;
    			continue;
    		}
    		else{
    			$AUTO_obj->message = $msg == '' ? 'find not order' : $msg;
    			$AUTO_obj->last_time = $now;
    			$AUTO_obj->status = 3;
    			$AUTO_obj->times += 1;
    			$bool = $AUTO_obj->save(false);
    			if (!$bool) {
    				echo self::getTime()." STEP 5 err: ".var_export($AUTO_obj->errors, true).PHP_EOL;
    			}
    			continue;
    		}
        }
        echo self::getTime().' getAliAutoOrder script end '.PHP_EOL;
        return $hasGotRecord;
    }
    
    /**
     +------------------------------------------------------------------
     *  速卖通用户开通推送消息
     +------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/06/01		初始化
     +------------------------------------------------------------------
     **/
    public static function SetAliPush(){
    	echo self::getTime().' SetAliPush script start '.PHP_EOL;
    	
    	$api = new AliexpressInterface_Api_Qimen(); 
    	$users = SaasAliexpressUser::find()->where(['version' => 'v2', 'is_active' => 1])->all();
    	foreach($users as $user){
    		echo 'uid: '.$user->uid.' sellerloginid: '.$user->sellerloginid.' start'.PHP_EOL;
    		// 检查授权是否过期或者是否授权,返回true，false
    		if (!AliexpressInterface_Helper_Qimen::checkToken($user->sellerloginid)) {
    			echo " Unauthorized or expired!".PHP_EOL.PHP_EOL;
    			continue;
    		}
    		$response = $api->tmcUserPermit(['id' => $user->sellerloginid, 'topics' => 'aliexpress_order_PlaceOrderSuccess,aliexpress_order_RiskControl,aliexpress_order_WaitSellerSendGoods,aliexpress_order_SellerPartSendGoods,aliexpress_order_WaitBuyerAcceptGoods,aliexpress_order_InCancel']);
    		if($response['result_success']){
    			$user->is_Push = 1;
    			if(!$user->save(false)){
    				echo print_r($user->errors, true).PHP_EOL;
    			}
    		}
    		else{
    			echo print_r($response, true).PHP_EOL;
    		}
    	}
    }
    
    //插入推送错误信息到db
    public static function insertAutoOrderDb($content, $msg){
        $err_obj = new QueueAliexpressAutoOrderError();
        $err_obj->create_time = time();
        $err_obj->ajax_message = json_encode($content);
        $err_obj->message = $msg;
        $err_obj->save(false);
    }
    	
    public static function getTime(){
    	return date('Y-m-d H:i:s');
    }
    
}
