<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\dash_board\helpers;

use yii;
use yii\base\Exception;
use eagle\modules\order\models\OdOrder;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\MailHelper;
use eagle\modules\permission\helpers\UserHelper;

class DashBoardHelper{
	

	//标记发货失败订单数redis key,不全可继续增加
	public static $SIGNSHIPPED_ORDER_ERR_EBAY = 'signshipped_order_err_ebay';
	public static $SIGNSHIPPED_ORDER_ERR_AMAZON = 'signshipped_order_err_amazon';
	public static $SIGNSHIPPED_ORDER_ERR_ALIEXPRESS = 'signshipped_order_err_aliexpress';
	public static $SIGNSHIPPED_ORDER_ERR_WISH = 'signshipped_order_err_wish';
	public static $SIGNSHIPPED_ORDER_ERR_DHGATE = 'signshipped_order_err_dhgate';
	public static $SIGNSHIPPED_ORDER_ERR_CDISCOUNT = 'signshipped_order_err_cdiscount';
	public static $SIGNSHIPPED_ORDER_ERR_LAZADA = 'signshipped_order_err_lazada';
	public static $SIGNSHIPPED_ORDER_ERR_LINIO = 'signshipped_order_err_linio';
	public static $SIGNSHIPPED_ORDER_ERR_JUMIA = 'signshipped_order_err_jumia';
	public static $SIGNSHIPPED_ORDER_ERR_BONANZA = 'signshipped_order_err_bonanza';
	public static $SIGNSHIPPED_ORDER_ERR_PRICEMINISTER= 'signshipped_order_err_priceminister';
	public static $SIGNSHIPPED_ORDER_ERR_SHOPEE = 'signshipped_order_err_shopee';
	
	
	public static $PLATFORM_PAIED_ORDER_URL = [
		'EBAY'=>'/order/ebay-order/list?order_status=200&pay_order_type=all',
		'AMAZON'=>'/order/amazon-order/list?order_status=200&pay_order_type=all',
		'ALIEXPRESS'=>'/order/aliexpressorder/aliexpresslist?order_status=200&pay_order_type=all',
		'WISH'=>'/order/wish-order/list?order_status=200',
		'DHGATE'=>'/order/dhgate-order/list?order_status=200',
		'CDISCOUNT'=>'/order/cdiscount-order/list?order_status=200',
		'LAZADA'=>'/order/lazada-order/list?order_status=200&pay_order_type=all',
		'LINIO'=>'/order/linio-order/list?order_status=200&pay_order_type=all',
		'JUMIA'=>'/order/jumia-order/list?order_status=200&pay_order_type=all',
		'BONANZA'=>'/order/bonanza-order/list?order_status=200',
		'PRICEMINISTER'=>'/order/priceminister-order/list?order_status=200',
		'NEWEGG'=>'/order/newegg-order/list?order_status=200',
		'SHOPEE'=>'/order/shopee-order/list?order_status=200&pay_order_type=all',
	];
	
	public static $PLATFORM_SIGNSHIPPED_ERR_ORDER_URL = [
	'EBAY'=>'/order/ebay-order/list?order_sync_ship_status=F',
	'AMAZON'=>'/order/amazon-order/list?order_sync_ship_status=F',
	'ALIEXPRESS'=>'/order/aliexpressorder/aliexpresslist?order_sync_ship_status=F',
	'WISH'=>'/order/wish-order/list?order_sync_ship_status=F',
	'DHGATE'=>'/order/dhgate-order/list?order_sync_ship_status=F',
	'CDISCOUNT'=>'/order/cdiscount-order/list?order_sync_ship_status=F',
	'LAZADA'=>'/order/lazada-order/list?order_sync_ship_status=F',
	'LINIO'=>'/order/linio-order/list?order_sync_ship_status=F',
	'JUMIA'=>'/order/jumia-order/list?order_sync_ship_status=F',
	'BONANZA'=>'/order/bonanza-order/list?order_sync_ship_status=F',
	'PRICEMINISTER'=>'/order/priceminister-order/list?order_sync_ship_status=F',
	'NEWEGG'=>'/order/newegg-order/list?order_sync_ship_status=F',
	'SHOPEE'=>'/order/shopee-order/list?order_sync_ship_status=F',
	];
	
	public static $PLATFORM_PENDING_TO_SHIP_ORDER_URL = [
		'EBAY'=>'/order/ebay-order/list?order_status=300',
		'AMAZON'=>'/order/amazon-order/list?order_status=300',
		'ALIEXPRESS'=>'/order/aliexpressorder/aliexpresslist?order_status=300',
		'WISH'=>'/order/wish-order/list?order_status=300',
		'DHGATE'=>'/order/dhgate-order/list?order_status=300',
		'CDISCOUNT'=>'/order/cdiscount-order/list?order_status=300',
		'LAZADA'=>'/order/lazada-order/list?order_status=300',
		'LINIO'=>'/order/linio-order/list?order_status=300',
		'JUMIA'=>'/order/jumia-order/list?order_status=300',
		'BONANZA'=>'/order/bonanza-order/list?order_status=300',
		'PRICEMINISTER'=>'/order/priceminister-order/list?order_status=300',
		'NEWEGG'=>'/order/newegg-order/list?order_status=300',
		'SHOPEE'=>'/order/shopee-order/list?order_status=300',
	];
	public static $PLATFORM_SHIPMENT_SUSPEND_ORDER_URL = [
		'EBAY'=>'/order/ebay-order/list?order_status=601',
		'AMAZON'=>'/order/amazon-order/list?order_status=601',
		'ALIEXPRESS'=>'/order/aliexpressorder/aliexpresslist?order_status=601',
		'WISH'=>'/order/wish-order/list?order_status=601',
		'DHGATE'=>'/order/dhgate-order/list?order_status=601',
		'CDISCOUNT'=>'/order/cdiscount-order/list?order_status=601',
		'LAZADA'=>'/order/lazada-order/list?order_status=601',
		'LINIO'=>'/order/linio-order/list?order_status=601',
		'JUMIA'=>'/order/jumia-order/list?order_status=601',
		'BONANZA'=>'/order/bonanza-order/list?order_status=601',
		'PRICEMINISTER'=>'/order/priceminister-order/list?order_status=601',
		'NEWEGG'=>'/order/newegg-order/list?order_status=601',
		'SHOPEE'=>'/order/shopee-order/list?order_status=601',
	];
	public static $PLATFORM_PENDING_PURCHASE_ORDER_URL = [
		'EBAY'=>'/order/ebay-order/list?order_status=602',
		'AMAZON'=>'/order/amazon-order/list?order_status=602',
		'ALIEXPRESS'=>'/order/aliexpressorder/aliexpresslist?order_status=602',
		'WISH'=>'/order/wish-order/list?order_status=602',
		'DHGATE'=>'/order/dhgate-order/list?order_status=602',
		'CDISCOUNT'=>'/order/cdiscount-order/list?order_status=602',
		'LAZADA'=>'/order/lazada-order/list?order_status=601',
		'LINIO'=>'/order/linio-order/list?order_status=602',
		'JUMIA'=>'/order/jumia-order/list?order_status=602',
		'BONANZA'=>'/order/bonanza-order/list?order_status=602',
		'PRICEMINISTER'=>'/order/priceminister-order/list?order_status=602',
		'NEWEGG'=>'/order/newegg-order/list?order_status=602',
		'SHOPEE'=>'/order/shopee-order/list?order_status=602',
	];
	public static $PLATFORM_CUSTOMER_MESSAGE_URL = [
	'EBAY'=>'/message/all-customer/show-letter?select_platform=ebay&selected_type=所有信息(eBay)',
	'AMAZON'=>'/message/all-customer/customer-list',//未开启客服模块
	'ALIEXPRESS'=>'/message/all-customer/show-letter?select_platform=aliexpress&selected_type=所有信息(速卖通)',
	'WISH'=>'/message/all-customer/show-letter?select_platform=wish&selected_type=所有信息(wish)',
	'DHGATE'=>'/message/all-customer/customer-list',//未开启客服模块
	'CDISCOUNT'=>'/message/all-customer/show-letter?select_platform=cdiscount&selected_type=所有信息',
	'LAZADA'=>'/message/all-customer/customer-list',//未开启客服模块
	'LINIO'=>'/message/all-customer/customer-list',//未开启客服模块
	'JUMIA'=>'/message/all-customer/customer-list',//未开启客服模块
	'BONANZA'=>'/message/all-customer/customer-list',//未开启客服模块
	'PRICEMINISTER'=>'/message/all-customer/show-letter?select_platform=priceminister&selected_type=所有信息(PM)',
	'SHOPEE'=>'/message/all-customer/customer-list',//未开启客服模块
	];
	
	//初始化计算平台左侧菜单数据 	//引入账号级别权限前	2017-4-5 lzhl
	public static function initCountPlatformOrderStatus_0($puid,$background='true'){
 
		$PlatformBindingSituation = PlatformAccountApi::getPlatformInfoInRedis($puid);
		$PlatformBindingSituation = json_decode($PlatformBindingSituation,true);
		if(empty($PlatformBindingSituation))
			return array('success'=>true,'message'=>"non platform binding");
		$bingingPlatforms = [];
		foreach ($PlatformBindingSituation as $platform=>$active){
			if($active)
				$bingingPlatforms[] = $platform;
		}
		
		if(empty($bingingPlatforms))
			return array('success'=>true,'message'=>"non platform account binding");
		
		//echo "<br>bingingPlatforms:";
		//print_r($bingingPlatforms);
		//echo "<br><--start foreach-->";
		try{
			foreach ($bingingPlatforms as $platform){
				if($background=='true') echo "<br>  $platform:";
				$accounts = [];
				$tmp = PlatformAccountApi::getPlatformAllAccount($puid, $platform);
				$tmp_data = empty($tmp['data'])?'':$tmp['data'];
				if(!empty($tmp_data))
					$accounts = array_keys($tmp_data);
				
				if(empty($accounts)){
					if($background=='true') echo "\n platform $platform : none account pass";
					foreach ([100,200,300,500,600,601,602] as $s){
						DashBoardStatisticHelper::OMSStatusDelete($puid, $platform, $s);
					}
					continue;
				}
				//echo "<br>  accounts:";print_r($accounts);
				$countData = [];
				$rows = OdOrder::find()
					->select("COUNT( * ) as count, order_status")
					->where(['order_source'=>$platform,'selleruserid'=>$accounts])
					->andWhere(['order_status'=>[100,200,300,500,600,601,602]])
					->andWhere(['order_relation'=>['normal','sm','ss']])
					->andWhere(['isshow'=>'Y'])
					->groupBy("order_status")
					->asArray()->all();
				//清空旧数据
				foreach ([100,200,300,500,600,601,602] as $s){
					DashBoardStatisticHelper::OMSStatusDelete($puid, $platform, $s);
				}
				//set新数据
				foreach ($rows as $row){
					$status = $row['order_status'];
					$increment = $row['count'];
					
					DashBoardStatisticHelper::OMSStatusSet($puid, $platform, $status, $increment);
				}
				$issueOrderCount = [];
				switch ($platform){
					
					
					default:
						$issueOrderCount = OdOrder::find()
							->select("COUNT( * ) as count")
							->where(['order_source'=>$platform,'selleruserid'=>$accounts])
							->andWhere(['order_relation'=>['normal','sm','ss']])
							->andWhere(['issuestatus'=>'IN_ISSUE'])
							->andWhere(['isshow'=>'Y'])
							->groupBy("selleruserid")
							->asArray()->one();
						break;
				}
				if(!empty($issueOrderCount['count'])){
					DashBoardStatisticHelper::CounterSet($puid, $platform.'_issueorder', $issueOrderCount['count']);
				}
				
			}
			//echo "<br><--end foreach-->";
		}catch (\Exception $e) {
			return array('success'=>false,'message'=>$e->getMessage());
		}
		
		return array('success'=>true,'message'=>'');
	}
	
	//初始化计算平台左侧菜单数据 	//引入账号级别权限后	2017-4-5 lzhl
	public static function initCountPlatformOrderStatus($puid,$background='true'){
		$PlatformBindingSituation = PlatformAccountApi::getPlatformInfoInRedis($puid);
		$PlatformBindingSituation = json_decode($PlatformBindingSituation,true);
		if(empty($PlatformBindingSituation))
			return array('success'=>true,'message'=>"non platform binding");
		$bingingPlatforms = [];
		foreach ($PlatformBindingSituation as $platform=>$active){
			if($active)
				$bingingPlatforms[] = $platform;
		}
	
		if(empty($bingingPlatforms))
			return array('success'=>true,'message'=>"non platform account binding");
	
		//echo "<br>bingingPlatforms:";
		//print_r($bingingPlatforms);
		//echo "<br><--start foreach-->";
		try{
			foreach ($bingingPlatforms as $platform){
				if($background=='true') echo "<br>  $platform:";
				$accounts = [];
				$tmp = PlatformAccountApi::getPlatformAllAccount($puid, $platform);
				$tmp_data = empty($tmp['data'])?'':$tmp['data'];
				if(!empty($tmp_data))
					$accounts = array_keys($tmp_data);
	
				if(empty($accounts)){
					if($background=='true') echo "\n platform $platform : none account pass";
					foreach ([100,200,300,500,600,601,602] as $s){
						DashBoardStatisticHelper::OMSStatusDelete($puid, $platform, $s);
					}
					continue;
				}
				if($background=='true') {echo "<br> accounts:".print_r($accounts);}
				$countData = [];
				$rows = OdOrder::find()
				->select("COUNT( * ) as count, order_status, selleruserid")
				->where(['order_source'=>$platform,'selleruserid'=>$accounts])
				->andWhere(['order_status'=>[100,200,300,500,600,601,602]])
				->andWhere(['order_relation'=>['normal','sm','ss']])
				->andWhere( " order_source_status IS NULL OR order_source_status != 'RISK_CONTROL'" ) // 速卖通 风控中不算入已付款的统计中
				->andWhere(['isshow'=>'Y'])
				->groupBy("order_status, selleruserid")
				->asArray()->all();
				//清空旧数据
				foreach ([100,200,300,500,600,601,602] as $s){
					DashBoardStatisticHelper::OMSStatusDelete($puid, $platform, $s);
				}
				//set新数据
				foreach ($rows as $row){
					$status = $row['order_status'];
					$seller = $row['selleruserid'];
					$increment = $row['count'];
						
					DashBoardStatisticHelper::OMSStatusSet2($puid, $platform, $seller, $status, $increment);
				}
				$issueOrderCount = [];
				switch ($platform){
						
						
					default:
						$issueOrderCount = OdOrder::find()
						->select("COUNT( * ) as count, selleruserid")
						->where(['order_source'=>$platform,'selleruserid'=>$accounts])
						->andWhere(['order_relation'=>['normal','sm','ss']])
						->andWhere(['issuestatus'=>'IN_ISSUE'])
						->andWhere(['isshow'=>'Y'])
						->groupBy("selleruserid")
						->asArray()->all();
						break;
				}
				if(!empty($issueOrderCount)){
					foreach ($issueOrderCount as $row){
						DashBoardStatisticHelper::CounterSet2($puid, $platform.'_issueorder', $row['selleruserid'], $row['count']);
					}
				}else{
					DashBoardStatisticHelper::CounterDelete2($puid, $platform.'_issueorder');
				}
	
			}
			//echo "<br><--end foreach-->";
		}catch (\Exception $e) {
			return array('success'=>false,'message'=>$e->getMessage());
		}
		
		return array('success'=>true,'message'=>'');
	}
	
	/*
	 * 某些操作后，对redis的订单状态数进行加减之后，对比redis的计数和数据库实际计数，如果计数不一样，则记录log
	 * @params	$puid
	 * @params	$platform
	 * @params	$old_status		//操作前的状态
	 * @params	$new_status		//操作后的状态
	 * @params	$operation		//做了什么操作
	 * 
	 */
	public static function checkOrderStatusRedisCompareWithDbCount($puid,$platform,$old_status,$new_status,$operation=''){
		return ;
		try{
			$old_status = (int)$old_status;
			$new_status = (int)$new_status;
			
			$old_status_count = DashBoardStatisticHelper::OMSStatusGet($puid, $platform, $old_status);
			$new_status_count = DashBoardStatisticHelper::OMSStatusGet($puid, $platform, $new_status);
			
			$old_status_count = empty($old_status_count)?0:(int)$old_status_count;
			$new_status_count = empty($new_status_count)?0:(int)$new_status_count;
			
			$redisCount = ['old_status_count'=>$old_status_count,'new_status_count'=>$new_status_count];
			
			$accounts = [];
			$tmp = PlatformAccountApi::getPlatformAllAccount($puid, $platform);
			$tmp_data = empty($tmp['data'])?'':$tmp['data'];
			if(!empty($tmp_data))
				$accounts = array_keys($tmp_data);
			
			if(empty($accounts)){
				return false;
			}
			
			$countData = [];
			$rows = OdOrder::find()
				->select("COUNT( * ) as count, order_status")
				->where(['order_source'=>$platform,'selleruserid'=>$accounts])
				->andWhere(['order_status'=>[100,200,300,500,600,601,602]])
				->andWhere(['order_relation'=>['normal','sm','ss']])
				->andWhere(['isshow'=>'Y'])
				->groupBy("order_status")
				->asArray()->all();
			//数据对比
			$mark_log = false;
			foreach ($rows as $row){
				$status = (int)$row['order_status'];
				$db_status_count = (int)$row['count'];
				
				if($status==$old_status){
					if($old_status_count!==$db_status_count)
						$mark_log = true;
				}
				if($status==$new_status){
					if($new_status_count!==$db_status_count)
						$mark_log = true;
				}
				
				$countData[$status] = $db_status_count;
			}
			
			if($mark_log){
				$journal_id = SysLogHelper::InvokeJrn_Create("Dashboard",__CLASS__, __FUNCTION__ , array($puid,$platform,$old_status,$new_status,json_encode($redisCount),json_encode($countData),$operation));
				//有不对应时就立即初始化一下
				//清空旧数据
				foreach ([100,200,300,500,600,601,602] as $s){
					DashBoardStatisticHelper::OMSStatusDelete($puid, $platform, $s);
				}
				//set新数据
				foreach ($countData as $status=>$increment){	
					DashBoardStatisticHelper::OMSStatusSet($puid, $platform, $status, $increment);
				}
			}
		}catch (\Exception $e) {
			$journal_id = SysLogHelper::InvokeJrn_Create("Dashboard",__CLASS__, __FUNCTION__ , array($puid,$platform,$old_status,$new_status,'',$e->getMessage()));
		}
	}
	
	
	
	//初始化某时段内订单数量统计及销售额统计
	//默认计算当前时间至14日前的日数据、8周前的周数据、3个月前的月数据
	public static function initSalesCount($puid,$count_days=30){
		if(!isset($count_days)) $count_days=30;
		
		$PlatformBindingSituation = PlatformAccountApi::getPlatformInfoInRedis($puid);
		$PlatformBindingSituation = json_decode($PlatformBindingSituation,true);
		if(empty($PlatformBindingSituation)){
			$PlatformBindingSituation=PlatformAccountApi::getAllPlatformBindingSituation([],$puid);
			if (isset($PlatformBindingSituation['ensogo']))
				unset($PlatformBindingSituation['ensogo']);
			//return array('success'=>true,'message'=>"non platform binding");
		}
		
		$bingingPlatforms = [];
		foreach ($PlatformBindingSituation as $platform=>$active){
			if($active)
				$bingingPlatforms[] = $platform;
		}
		
		if(empty($bingingPlatforms))
			return array('success'=>true,'message'=>"non platform account binding");
		
		//echo "<br>bingingPlatforms:";
		//print_r($bingingPlatforms);
		//echo "<br><--start foreach-->";
		
		//统计时间参数--开始
		$theDay = date("Y-m-d",time());
		$weekDay = date("w",strtotime($theDay));//周第几日，周日为第0日,周六为第6日
		$month = (int)date("m",strtotime($theDay));//当前月
		$monthDay = (int)date("d",strtotime($theDay));//当前月第几日
		$year = (int)date("Y",strtotime($theDay));//当前年
		
		$daily_params = [];
		//往前统计30日
		for($i=$count_days;$i>0;$i--){
			$start = strtotime($theDay)-3600*24*$i;
			$end = strtotime($theDay)-3600*24*($i-1);
			$daily_params[$i] = ['start'=>$start,'end'=>$end];
		}
		$daily_params[0] = ['start'=>strtotime($theDay),'end'=>time()];//加上当日
		//统计时间参数--结束
		
		//for全局查询用额数据
		$global_order_counter = [];
		
		
		try{
			foreach ($bingingPlatforms as $platform){
				$accounts = PlatformAccountApi::getPlatformAllAccount($puid, $platform);
				$sellerids=[];
				if($accounts['success'] && !empty($accounts['data'])){
					//if (in_array($platform,['amazon','cdiscount','priceminister','rumall','customized']) ){
					//	$accounts['data'] = array_flip($accounts['data']);
					//	$sellerids = array_keys($accounts['data']);
					//}else{
						$sellerids = array_keys($accounts['data']);
					//}
					//echo "<br>$platform accounts Array:";
					//print_r($sellerids);
				}else {
					//echo "<br>$platform accounts empty";
					//continue;
				}
				$global_order_counter[$platform] = 0;
				//删除区间内的 所有 
				$initSellerIdSql = "delete from `db_sales_daily`
				where platform=:platform  and seller_id not in (:sellers) and thedate >= '".date("Y-m-d",strtotime("-$count_days days"))."'";
				$command = Yii::$app->subdb->createCommand($initSellerIdSql ) ;
				$command->bindValue(':platform', $platform, \PDO::PARAM_STR);
				$command->bindValue(':sellers', '\''.implode('\',\'', $sellerids).'\'', \PDO::PARAM_STR);
				$affectRows = $command->execute();
				
				foreach ($sellerids as $sellerid){
					foreach ($daily_params as $daysago=>$parma){
						$statisticDay = '';
						$statisticDay = date("Y-m-d",strtotime($theDay)-3600*24*(int)$daysago);
						$rows = OdOrder::find()
							->select("sum( grand_total  ) as total, count(*) as order_count, order_type, currency, order_status ")
							->where(['order_source'=>$platform,'selleruserid'=>$sellerid])
							//->andWhere(['order_status'=>[200,300,500,601,602]])//只计算正常付款订单
							->andWhere(['order_relation'=>['normal','sm','ss']])
							->andWhere(['isshow'=>'Y'])
							->andWhere("order_source_create_time>=".$parma['start']." and order_source_create_time<".$parma['end'])
							->groupBy("currency , order_type, order_status")
							->asArray()->all();
						
						//删除可能存在的旧数据
						DashBoardStatisticHelper::SalesStatisticDelete($statisticDay, $platform, $sellerid);
						
						if(!empty($rows)){
							$merge = false;
							$currency_arr = [];
							foreach ($rows as $row){
								if(!in_array($row['currency'], $currency_arr))
									$currency_arr[] = $row['currency'];
							}
							
							if(count($currency_arr)>1 || $platform=='lazada'){
								//多币种的情况下
								$merge = true;
							}
							foreach ($rows as $row){
								DashBoardStatisticHelper::SalesStatisticAdd($statisticDay, $platform, $row['order_type'], $sellerid, $row['currency'], $row['total'], $row['order_count'],0,$merge, $row['order_status']);
								$global_order_counter[$platform] += (int)$row['order_count'];
							}
						}
					}
				}
				if(!empty($global_order_counter[$platform]))
					$global_order_counter[$platform] = round($global_order_counter[$platform] / 30 ,2);
			}
			//echo "<br><--end foreach-->";
		}catch (\Exception $e) {
			return array('success'=>false,'message'=>$e->getMessage());
		}
		
		return array('success'=>true,'message'=>'','counter'=>$global_order_counter);
	}
	
	public static function Last2_to_17DaysSalesCount($puid,$hasActionForPuidVsApp = array() ){
 
		
		$platform_source = odorder::$orderSource;
		$insertData = [];
		foreach ($platform_source as $platform=>$name){
			$insertData[$platform] = ['order_avg'=>0,'accounts'=>0];
		}
		
		$theDay = date("Y-m-d",time());
		$query_start_time = strtotime("-17 days");
		$query_end_time = strtotime("-2 days");
		
		$sql = "SELECT count(1) /15  AS avg , `order_source` as  platform  FROM `od_order_v2` 
				WHERE `create_time` BETWEEN '$query_start_time' AND '$query_end_time' 
				GROUP BY `order_source` ";
		
		echo "work for $puid \n ".$sql;//liang test
		
		try{
			$command = \Yii::$app->subdb->createCommand($sql);
			$count_avg = $command->queryAll();
		}catch (\Exception $e) {
			return array('success'=>false,'message'=>$puid.' select `db_sales_daily` Exception:\n'.$e->getMessage());
		}
		
		//print_r($count_avg);//liang test
		
		foreach ($count_avg as $platform_avg){
			if(isset($insertData[$platform_avg['platform']]))
				$platform_avg['avg'] = floatval($platform_avg['avg']);
				$platform_str = strtolower($platform_avg['platform']);
				$insertData[$platform_str]['order_avg'] = round($platform_avg['avg'],2);
		}
		
		$platform_accounts = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($puid);
		foreach ($platform_accounts as $platform=>$accounts){
			if(isset($insertData[$platform]))
				$insertData[$platform]['accounts'] = count($accounts);
		}
		//print_r($insertData);//liang test
		$insertSql = "REPLACE INTO `user_sales_count_last_2_to_17_days` 
					(`puid`,`the_day`,`aliexpress`,`amazon`,`cdiscount`,`customized`,`dhgate`,`bonanza`,`ebay`,`jumia`,`lazada`,`linio`,`newegg`,`priceminister`,`rumall`,`wish`,
					 `aliexpress_accounts`,`amazon_accounts`,`bonanza_accounts`,`cdiscount_accounts`,`customized_accounts`,`dhgate_accounts`,`ebay_accounts`,`jumia_accounts`,`lazada_accounts`,`linio_accounts`,`newegg_accounts`,`priceminister_accounts`,`rumall_accounts`,`wish_accounts` ) 
					VALUES 
					($puid,'$theDay', "
					.$insertData['aliexpress']['order_avg'].","
					.$insertData['amazon']['order_avg'].","
					.$insertData['cdiscount']['order_avg'].","
					.$insertData['customized']['order_avg'].","
					.$insertData['dhgate']['order_avg'].","
					.$insertData['bonanza']['order_avg'].","
					.$insertData['ebay']['order_avg'].","
					.$insertData['jumia']['order_avg'].","
					.$insertData['lazada']['order_avg'].","
					.$insertData['linio']['order_avg'].","
					.$insertData['newegg']['order_avg'].","
					.$insertData['priceminister']['order_avg'].","
					.$insertData['rumall']['order_avg'].","
					.$insertData['wish']['order_avg'].","
					.$insertData['aliexpress']['accounts'].","
					.$insertData['amazon']['accounts'].","
					.$insertData['bonanza']['accounts'].","
					.$insertData['cdiscount']['accounts'].","
					.$insertData['customized']['accounts'].","
					.$insertData['dhgate']['accounts'].","
					.$insertData['ebay']['accounts'].","
					.$insertData['jumia']['accounts'].","
					.$insertData['lazada']['accounts'].","
					.$insertData['linio']['accounts'].","
					.$insertData['newegg']['accounts'].","
					.$insertData['priceminister']['accounts'].","
					.$insertData['rumall']['accounts'].","
					.$insertData['wish']['accounts']." ) ";
		
		 echo "\n ".$insertSql."\n ";//liang test
		try{
			$command = \Yii::$app->db_queue2->createCommand($insertSql);
			$rtn = $command->execute();
			
			$toUpdateField = '';

			foreach ($hasActionForPuidVsApp as $appName =>$appUserIds){

				$toUpdateField .= ($toUpdateField==''?'':',')." `$appName` = 0". 			 
				      !empty($hasActionForPuidVsApp[$appName]['p'.$puid]);
			}
			
			//update app 活跃field 
			if ($toUpdateField <> ''){
				$updateSql = "update user_sales_count_last_2_to_17_days set  $toUpdateField  where puid= $puid ";
				$command = \Yii::$app->db_queue2->createCommand($updateSql);
				 echo "\n ".$updateSql;//liang test
				$rtn = $command->execute();
			}
			
		}catch (\Exception $e) {
			return array('success'=>false,'message'=>$puid.' insert into `user_sales_count_last_2_to_17_days` Exception:\n'.$e->getMessage());
		}
		
		return array('success'=>true,'message'=>'');
		
	}
	
	
	
	/*
	 * 获取用户绑定的平台所有待处理订单汇总
	 * 纠纷中,已付款，发货中，暂停发货，缺货
	 * @return	array	 ['success'=>boolean,'message'=>string, 'date'=>[ platform1=>[status1=>number1,...], platform2=>[status1=>number1,..],...]]
	 */
	public static function getPlatformPendingOrderNumber($puid,$platforms){
		$rtn = [ 'success'=>true, 'message'=>'', 'data'=>[] ];
		try{
			$pendingData = [];
			//var_dump($platforms);
			foreach ($platforms as $platform){
				$authorizeAcctouns = UserHelper::getUserAuthorizePlatformAccounts($platform);
				$authorizeAcctouns = empty($authorizeAcctouns[$platform])?[]:array_keys($authorizeAcctouns[$platform]);
				//var_dump($authorizeAcctouns);
				if(empty($authorizeAcctouns)){
					$pendingData[$platform]=[
					'issue_order' => $issue_order,
					'pending_paied' => $pending_paied,
					'pending_to_ship' => $pending_to_ship,
					'shipment_suspend' => $shipment_suspend,
					'pending_purchase' => $pending_purchase,
					];
					continue;
				}
				//纠纷中
				$issue_order = DashBoardStatisticHelper::CounterGetSum2($puid, $platform.'_issueorder',$authorizeAcctouns);
				if(empty($issue_order)) $issue_order = 0;
				//已付款
				$pending_paied = DashBoardStatisticHelper::OMSStatusGetSum2($puid, $platform, $authorizeAcctouns, 200);
				if(empty($pending_paied)) $pending_paied = 0;
				//发货中
				$pending_to_ship = DashBoardStatisticHelper::OMSStatusGetSum2($puid, $platform, $authorizeAcctouns, 300);
				if(empty($pending_to_ship)) $pending_to_ship = 0;
				//暂停发货
				$shipment_suspend = DashBoardStatisticHelper::OMSStatusGetSum2($puid, $platform, $authorizeAcctouns, 601);
				if(empty($shipment_suspend)) $shipment_suspend = 0;
				//缺货
				$pending_purchase = DashBoardStatisticHelper::OMSStatusGetSum2($puid, $platform, $authorizeAcctouns, 602);
				if(empty($pending_purchase)) $pending_purchase = 0;
				
				$pendingData[$platform]=[
					'issue_order' => $issue_order,
					'pending_paied' => $pending_paied,
					'pending_to_ship' => $pending_to_ship,
					'shipment_suspend' => $shipment_suspend,
					'pending_purchase' => $pending_purchase,
				];
			}
			$rtn['data'] = $pendingData;
		}catch (\Exception $e) {
			$rtn =  [ 'success'=>false, 'message'=>$e->getMessage(), 'date'=>[] ];
		}
		return $rtn;
	}
	
	
	public static function getPlatformMessagePendingNumber($puid,$platforms){
		$rtn = [ 'success'=>true, 'message'=>'', 'data'=>[] ];
		try{
			$pendingData = [];
			$allMessagePending = MessageApiHelper::getDashBoardMessageData($puid);
			if(isset($allMessagePending['success'])){
				$rtn['message'] = isset($allMessagePending['message'])?$allMessagePending['message']:'';
			}else{
				foreach ($platforms as $platform){
					if(empty($allMessagePending[$platform]))
						continue;
					$pendingData[$platform] = $allMessagePending[$platform];
				}
				$rtn['data'] = $pendingData;
			}
		}catch (\Exception $e) {
			$rtn =  [ 'success'=>false, 'message'=>$e->getMessage(), 'date'=>[] ];
		}
		return $rtn;
		
	}
	
	//返回订单数量和销售额
	//分三种日期，每日、每周、每月，所有会都显示当前(当日，当周，当月)
	public static function getPlatformOrderStatisticsData($puid,$platforms=[],$select_all=true, $sellerid='',$theDay='',$periodic='daily',$order_type='all',$columns=14){
		if(empty($theDay))
			$theDay = date("Y-m-d",time());
		
		$chartData = [];
		//var_dump($platforms);
		try{
			//统计时间参数--开始
			$date_between_arr = [];
			switch ($periodic){
				case 'weekly' :
					//echo "weekly";
					$weekDay = (int)date("w",strtotime($theDay));//周第几日，周日为第0日,周六为第6日
					$this_week_start = date("Y-m-d",strtotime($theDay)-3600*24*$weekDay);//本周开始时间
					
					//由于当期占用一次展示，所以for 次数要减1
					for ($w=$columns-1;$w>=1;$w--){
						$tmp_week_start =  date("Y-m-d",strtotime($this_week_start)- 3600*24*7*$w );//周开始时间
						$tmp_week_end =  date("Y-m-d",strtotime($this_week_start)- 3600*24* (7*($w-1)+1) );//周开始时间
						$arr_key = date("m-d",strtotime($tmp_week_start))."至".date("m-d",strtotime($tmp_week_end));
						$date_between_arr[$arr_key] = ['order_date_from'=>$tmp_week_start, 'order_date_to'=>$tmp_week_end];
					}
					//当期加到最后
					$date_between_arr["本周"] = ['order_date_from'=>$this_week_start, 'order_date_to'=>$theDay];
					break;
				case 'monthly' :
					//echo "monthly";
					$month = (int)date("m",strtotime($theDay));//当前月
					$monthDay = (int)date("d",strtotime($theDay));//当前月第几日
					$year = (int)date("Y",strtotime($theDay));//当前年
					$this_month_start = date("Y-m-d",strtotime($theDay)-3600*24*($monthDay-1) );//本周开始时间
					//由于当期占用一次展示，所以for 次数要减1
					for ($m=$columns-1;$m>=1;$m--){
						//展示月数不大于12的情况下
						if($month-$m<=0){//跨年
							$tmp_month_start = ($year-1).'-'.($month-$m+12).'-1';//XXXX年X月1日
							if( $month-$m==0 ){
								$mp_next_month_start = $year.'-1-1';
							}else{
								$mp_next_month_start = ($year-1).'-'.($month-$m+12+1).'-1';
							}
						}else{
							$tmp_month_start = $year.'-'.($month-$m).'-1';//XXXX年X月1日
							$mp_next_month_start = $year.'-'.($month-$m+1).'-1';
						}
						$tmp_month_end = date("Y-m-d",strtotime($mp_next_month_start)-3600*24);//月结束日取下月开始-1的date，应对30/31/闰年的变化
						$tmp_month_end = date("Y-m-d",strtotime($tmp_month_end));//转为标准化
						$arr_key = date("Y-m",strtotime($tmp_month_start));
						$date_between_arr[$arr_key] = ['order_date_from'=>$tmp_month_start, 'order_date_to'=>$tmp_month_end];
					}
					//当期加到最后
					$this_key = date("Y-m",strtotime($theDay));
					$date_between_arr[$this_key] = ['order_date_from'=>$this_month_start, 'order_date_to'=>$theDay];
					break;
				default://默认按每日
					
					for($d=$columns-1;$d>=1;$d--){
						$tmp_date = date("Y-m-d",strtotime($theDay)-3600*24*$d);
						$date_between_arr[$tmp_date] = ['order_date_from'=>$tmp_date, 'order_date_to'=>$tmp_date];
					}
					$date_between_arr[$theDay] = ['order_date_from'=>$theDay, 'order_date_to'=>$theDay];
					break;
			}
			//统计时间参数--结束
			//print_r($date_between_arr);
			$rtn = [];
			if(!$select_all && count($platforms)==1){//指定平台/账号查询	//key为店铺账号
				$platform = $platforms[0];
				
				$allPlatformAccounts=[];
				if(!empty($sellerid)){
					if(is_string($sellerid))
						$allPlatformAccounts[] = $sellerid;
					if(is_array($sellerid))
						$allPlatformAccounts = $sellerid;
				}else{
					//$tmp_account_info = PlatformAccountApi::getPlatformAllAccount($puid, $platform);
					//$allPlatformAccounts = empty($tmp_account_info['data'])?[]:$tmp_account_info['data'];
					
					$tmp_account_info = UserHelper::getUserAuthorizePlatformAccounts($platform);
					$allPlatformAccounts = empty($tmp_account_info[$platform])?[]:$tmp_account_info[$platform];
				}
				//print_r($allPlatformAccounts);
				if(count($allPlatformAccounts)>1){//当有效账号大于1个时，才计算合计数
					$rtn['全部'] = [];
					//先生成total的default数据
					foreach ($date_between_arr as $key=>$from_to){
						$rtn['全部'][$key] = [
						'total_sales_count'=>0,
						'total_sales_amount_original_currency'=>0,
						'total_sales_amount_USD'=>0,
						'currency'=>'',
						'total_profit_cny'=>0,
						];
					}
				}
				//按平台渠道获取销售数据
				//echo "<br><br>";
				//var_dump($allPlatformAccounts);
				//echo "<br><br>";
				foreach ($allPlatformAccounts as $sellerid=>$store){
					$sellerid = strval($sellerid);
					$rtn[$sellerid] = [];
					foreach ($date_between_arr as $key=>$from_to){
						$order_date_from = $from_to['order_date_from'];
						$order_date_to = $from_to['order_date_to'];
						//var_dump($sellerid);
						$records = DashBoardStatisticHelper::SalesStatisticGet($order_date_from, $order_date_to, true, strtoupper($platform), $sellerid, $order_type, $currency='');
						//var_dump($records);
						if(count($records)>0){
							$rtn[$sellerid][$key] = $records[0];
							if(empty($rtn[$sellerid][$key]['total_sales_count'])) $rtn[$sellerid][$key]['total_sales_count'] = 0;
							if(empty($rtn[$sellerid][$key]['total_sales_amount_original_currency'])) $rtn[$sellerid][$key]['total_sales_amount_original_currency'] = 0;
							if(empty($rtn[$sellerid][$key]['total_sales_amount_USD'])) $rtn[$sellerid][$key]['total_sales_amount_USD'] = 0;
							//if(empty($rtn[$sellerid][$key]['currency'])) $rtn[$sellerid][$key]['currency'] = 'USD';
							if(empty($rtn[$sellerid][$key]['total_profit_cny'])) $rtn[$sellerid][$key]['total_profit_cny'] = 0;
							if(count($allPlatformAccounts)>1){
								$rtn['全部'][$key]['total_sales_count'] += (empty($rtn[$sellerid][$key]['total_sales_count'])?0:round($rtn[$sellerid][$key]['total_sales_count'],2) );
								$rtn['全部'][$key]['total_sales_amount_original_currency'] += (empty($rtn[$sellerid][$key]['total_sales_amount_original_currency'])?0:round($rtn[$sellerid][$key]['total_sales_amount_original_currency'],2) );
								$rtn['全部'][$key]['total_sales_amount_USD'] += (empty($rtn[$sellerid][$key]['total_sales_amount_USD'])?0:round($rtn[$sellerid][$key]['total_sales_amount_USD'],2) );
								if(!empty($rtn[$sellerid][$key]['currency']))
									$rtn['全部'][$key]['currency'] = $rtn[$sellerid][$key]['currency'];
								$rtn['全部'][$key]['total_profit_cny'] += (empty($rtn[$sellerid][$key]['total_profit_cny'])?0:round($rtn[$sellerid][$key]['total_profit_cny'],2) );
							}
						}else{
							$rtn[$sellerid][$key] = [
							'total_sales_count'=>0,
							'total_sales_amount_original_currency'=>0,
							'total_sales_amount_USD'=>0,
							//'currency'=>'',
							'total_profit_cny'=>0,
							];
						}
					}
				}
				//print_r($rtn);
			}else{//混合查询	//key为平台
				//先生成total的default数据
				foreach ($date_between_arr as $key=>$from_to){
					$rtn['全部'][$key] = [
					'total_sales_count'=>0,
					'total_sales_amount_original_currency'=>0,
					'total_sales_amount_USD'=>0,
					'currency'=>'USD',
					'total_profit_cny'=>0,
					];
				}
				//var_dump($platforms);
				foreach ($platforms as $platform){
					$tmp_rtn[$platform] = [];
					
					$sellerid = '';
					$tmp_account_info = UserHelper::getUserAuthorizePlatformAccounts($platform);
					$allPlatformAccounts = empty($tmp_account_info[$platform])?[]:$tmp_account_info[$platform];
					
					//var_dump($allPlatformAccounts);
					
					if(empty($allPlatformAccounts))
						continue;
					$sellerid = array_keys($allPlatformAccounts);
					//var_dump($sellerid);
					
					foreach ($date_between_arr as $key=>$from_to){
						$order_date_from = $from_to['order_date_from'];
						$order_date_to = $from_to['order_date_to'];
						$tmp_rtn = [];//合计展示的时候，不单独展示渠道数据，临时记录
							
						$records = DashBoardStatisticHelper::SalesStatisticGet($order_date_from, $order_date_to, true, strtoupper($platform), $sellerid, $order_type, $currency='');
						if(count($records)>0){
							$tmp_rtn = $records[0];
							if(empty($tmp_rtn['total_sales_count'])) $tmp_rtn['total_sales_count'] = 0;
							if(empty($tmp_rtn['total_sales_amount_original_currency'])) $tmp_rtn['total_sales_amount_original_currency'] = 0;
							if(empty($tmp_rtn['total_sales_amount_USD'])) $tmp_rtn['total_sales_amount_USD'] = 0;
							//if(empty($tmp_rtn['currency'])) $tmp_rtn['currency'] = 'USD';
							if(empty($tmp_rtn['total_profit_cny'])) $tmp_rtn['total_profit_cny'] = 0;
							
							$rtn['全部'][$key]['total_sales_count'] += (empty($tmp_rtn['total_sales_count'])?0:round($tmp_rtn['total_sales_count'],2) );
							$rtn['全部'][$key]['total_sales_amount_original_currency'] += (empty($tmp_rtn['total_sales_amount_original_currency'])?0:round($tmp_rtn['total_sales_amount_original_currency'],2) );
							$rtn['全部'][$key]['total_sales_amount_USD'] += (empty($tmp_rtn['total_sales_amount_USD'])?0:round($tmp_rtn['total_sales_amount_USD'],2) );
							$rtn['全部'][$key]['total_profit_cny'] += (empty($tmp_rtn['total_profit_cny'])?0:round($tmp_rtn['total_profit_cny'],2) );
						}
					}
				}
			}
			//print_r($rtn);
			//print_r($date_between_arr);
			$chartData['xAxis'] = array_keys($date_between_arr);//X轴column
			$chartData['title'] = '订单业绩统计';
			if($periodic=='daily')
				$chartData['title'] = '近 '.$columns.'日 订单统计';
			if($periodic=='weekly')
				$chartData['title'] = '近 '.$columns.'周 订单统计';
			if($periodic=='monthly')
				$chartData['title'] = '近 '.$columns.'个月 订单统计';
			
			$currency = '';
			if(!empty($rtn)){
				foreach ($rtn as $keyName=>$datas){//$keyName为平台或账号
					//订单数统计
					$tmp_order_data = [];
					$tmp_order_data['name'] = $keyName.'(订单)';
					//$tmp_order_data['color'] = '#4572A7';
					$tmp_order_data['type'] = 'column';//column or spline
					//$tmp_order_data['tooltip'] = ['valueSuffix'=>'单'];
					
					//订单销售额统计
					$tmp_sales_data = [];
					$tmp_sales_data['name'] = $keyName.'(销售额)';
					//$tmp_sales_data['color'] = '#89A54E';
					$tmp_sales_data['type'] = 'spline';//column or spline
					$tmp_sales_data['yAxis'] = 1;
					
					//订单利润统计
					$tmp_profit_data = [];
					$tmp_profit_data['name'] = $keyName.'(利润)';
					//$tmp_profit_data['color'] = '#89A54E';
					$tmp_profit_data['type'] = 'spline';//column or spline
					$tmp_profit_data['yAxis'] = 2;
					
					$tmp_order_amount = [];
					$tmp_sales_amount_USD = [];
					$tmp_sales_amount_ORG = [];
					$tmp_profit_amount_CNY = [];
					
					$currency_arr = [];
					foreach ($datas as $date=>$amount_data){
						$tmp_order_amount[] = round($amount_data['total_sales_count'],2);
						$tmp_sales_amount_USD[] =round($amount_data['total_sales_amount_USD'],2);
						$tmp_sales_amount_ORG[] =round($amount_data['total_sales_amount_original_currency'],2);
						$tmp_profit_amount_CNY[] = round($amount_data['total_profit_cny'],2);
						if($select_all){
							$currency_arr[] = 'USD';
						}else{
							if(!empty($amount_data['currency']))
								$currency_arr[] = $amount_data['currency'];
						}
					}
					$tmp_order_data['data'] = $tmp_order_amount;
					
					//特定平台可以确定统一的原始货币时
					if(count($platforms)==1){
						$platform = $platforms[0];
						if($platform=='cdiscount' || $platform=='priceminister'){
							$currency = 'EUR';
							$tmp_sales_data['data'] = $tmp_sales_amount_ORG;
						}else{
							$currency = 'USD';
							$tmp_sales_data['data'] = $tmp_sales_amount_USD;
						}
					}else{
						$currency = 'USD';
						$tmp_sales_data['data'] = $tmp_sales_amount_USD;
					}
					//$tmp_sales_data['data'] = ($currency=='USD')?$tmp_sales_amount_USD:$tmp_sales_amount_ORG;
					//$tmp_sales_data['data'] = $tmp_sales_amount_USD;
					$tmp_sales_data['currency'] = $currency;
					//$tmp_sales_data['currency'] = 'USD';
					
					
					$tmp_profit_data['data'] = $tmp_profit_amount_CNY;
					
					
					//$tmp_sales_data['tooltip'] = ['valueSuffix'=>' '.$currency];
					
					$chartData['series'][] = $tmp_order_data;
					$chartData['series'][] = $tmp_sales_data;
					$chartData['series'][] = $tmp_profit_data;
				}
				
			}
			if(empty($currency)) $currency = 'USD';
			$chartData['title'] = $chartData['title'].'('.$currency.')';
			$chartData['currency'] = $currency;
		}catch (\Exception $e) {
			return array('success'=>false,'message'=>$e->getMessage(),'date'=>[]);
		}
		
		return array('success'=>true,'message'=>'','data'=>$chartData);
	}
	
	public static function getAnnounces(){
		$data = [];
		$announces = \Yii::$app->cmsdb->createCommand('SELECT * FROM `cms_help_announce_management` WHERE help_announce_status = 1 ORDER By create_time DESC LIMIT 6')->queryAll();
		$result = [];
		foreach ($announces as $key => $announce) {
// 			$title = strip_tags(mb_substr($announce['help_announce_title'], 0, 30, 'utf-8'));
		    $title = strip_tags(mb_substr($announce['help_announce_title'], 0, 60, 'utf-8'));
			$time = date('Y-m-d', strtotime($announce['create_time']));
// 			if (strlen($announce['help_announce_title']) >= 70) $title .= '...';
			if (mb_strlen($announce['help_announce_title'],'UTF8') > 60) $title .= '...';
			array_push($result, [
				'id' => $announce['id'],
				'title' => $title,
				'keywords' => $announce['help_announce_keywords'],
				'description' => $announce['help_announce_description'],
				'status' => $announce['help_announce_status'],
				'content' => $announce['help_announce_content'],
				'update_time' => $announce['update_time'],
				'create_time' => $announce['create_time'],
				'time' => $time
			]);
		}
		if (!empty($result)) {
			$data['success'] = true;
			$data['data'] = $result;
		}
		
		return $data;
	}
	
	public static function generateAnnouncesListHtml(){
		$currentEnv=\Yii::$app->params["currentEnv"]; //当前环境--production或者test
		if ($currentEnv=="test"){
			return '<ul style="font-size: 14px;"><li style="width: 100%;"><a href="http://www.littleboss.com/announce_info_62.html" target="_blank" style="width: 100%;"><span>2016-09-03 </span>速卖通线上发货新增是否退件设置</a></li><li><a href="http://www.littleboss.com/announce_info_61.html" target="_blank"><span>2016-09-03 </span>&#8203;wish邮启用了V2接口</a></li><li><a href="http://www.littleboss.com/announce_info_60.html" target="_blank"><span>2016-08-25 </span>速卖通、CD、PM平台增加手工立即拉取平台订单功能</a></li><li><a href="http://www.littleboss.com/announce_info_59.html" target="_blank"><span>2016-08-23 </span>"物流"模块整合到"发货"模块</a></li><li><a href="http://www.littleboss.com/announce_info_58.html" target="_blank"><span>2016-08-19 </span>扫描分拣和扫描发货的功能发布了</a></li><li><a href="http://www.littleboss.com/announce_info_57.html" target="_blank"><span>2016-08-12 </span>小老板【手工订单】正式上线</a></li></ul>';
		}
		//echo "<br>getAnnounces:";
		$result = self::getAnnounces();
		//print_r($result);
		if($result['success']){
			$announces = $result['data'];
			$str = '<ul style="font-size: 14px;">';
			foreach ($announces as $announc){
				$str .='<li><a href="http://help.littleboss.com/announce_info_'.$announc['id'].'.html" target="_blank"><span style="margin-right:10px;margin-left:5px;">'.$announc['time'].' </span>'.$announc['title'].'</a></li>';
			}
			$str .= '</ul>';
			return $str;
		}else{
			return "";
		}
	}
	
	public static function getSignShippedErrorNumber($puid,$platforms){
		$rtn = [ 'success'=>true, 'message'=>'', 'data'=>[] ];
		try{
			foreach ($platforms as $platform){
				$key='';
				switch ($platform){
					case 'ebay':
						$key = self::$SIGNSHIPPED_ORDER_ERR_EBAY;
						break;
					case 'amazon':
						$key = self::$SIGNSHIPPED_ORDER_ERR_AMAZON;
						break;
					case 'aliexpress':
						$key = self::$SIGNSHIPPED_ORDER_ERR_ALIEXPRESS;
						break;
					case 'wish':
						$key = self::$SIGNSHIPPED_ORDER_ERR_WISH;
						break;
					case 'dhgate':
						$key = self::$SIGNSHIPPED_ORDER_ERR_DHGATE;
						break;
					case 'cdiscount':
						$key = self::$SIGNSHIPPED_ORDER_ERR_CDISCOUNT;
						break;
					case 'lazada':
						$key = self::$SIGNSHIPPED_ORDER_ERR_LAZADA;
						break;
					case 'jumia':
						$key = self::$SIGNSHIPPED_ORDER_ERR_JUMIA;
						break;
					case 'linio':
						$key = self::$SIGNSHIPPED_ORDER_ERR_LINIO;
						break;
					case 'bonanza':
						$key = self::$SIGNSHIPPED_ORDER_ERR_BONANZA;
						break;
					case 'priceminister':
						$key = self::$SIGNSHIPPED_ORDER_ERR_PRICEMINISTER;
						break;
					default:
						break;
				}
				
				if(!empty($key)){
					$counter = DashBoardStatisticHelper::CounterGet($puid, $key);
					$rtn['data'][$platform] = empty($counter)?0:(int)$counter;
				}
			}
		}catch (\Exception $e) {
			$rtn =  [ 'success'=>false, 'message'=>$e->getMessage(), 'date'=>[] ];
		}
		return $rtn;
	}
	
	public static function getUserPlatformAccountsErr($puid, $platforms){
		$rtn = [ 'success'=>true, 'message'=>'', 'data'=>[] ];
		try{
			foreach ($platforms as $platform){
				switch ($platform){
					case 'ebay':
						$rtn['data'][$platform] = PlatformAccountApi::getUserEbayAccountProblems($puid);
						break;
					case 'amazon':
						$rtn['data'][$platform] = PlatformAccountApi::getUserAmazonAccountProblems($puid);
						break;
					case 'aliexpress':
						$rtn['data'][$platform] = PlatformAccountApi::getUserAliexpressAccountProblems($puid);
						break;
					case 'wish':
						$rtn['data'][$platform] = PlatformAccountApi::getUserWishAccountProblems($puid);
						break;
					case 'dhgate':
						$rtn['data'][$platform] = PlatformAccountApi::getUserDhgateAccountProblems($puid);
						break;
					case 'cdiscount':
						$rtn['data'][$platform] = PlatformAccountApi::getUserCdiscountProblemAccounts($puid);
						break;
					case 'lazada':
						$rtn['data'][$platform] = PlatformAccountApi::getUserLazadaAccountProblems($puid);
						break;;
					case 'linio':
						$rtn['data'][$platform] = PlatformAccountApi::getUserLinioAccountProblems($puid);
						break;
					case 'jumia':
						$rtn['data'][$platform] = PlatformAccountApi::getUserJumiaAccountProblems($puid);
						break;
					case 'bonanza':
						$rtn['data'][$platform] = PlatformAccountApi::getUserBonanzaAccountProblems($puid);
						break;
					case 'priceminister':
						$rtn['data'][$platform] = PlatformAccountApi::getUserPriceministerProblemAccounts($puid);
						break;
					case 'newegg': 
						$rtn['data'][$platform] = PlatformAccountApi::getUserNeweggProblemAccounts($puid);
						break;
					//未完待续
					default:
						break;
				}
			}
		}catch (\Exception $e) {
			$rtn =  [ 'success'=>false, 'message'=>$e->getMessage(), 'date'=>[] ];
		}
		return $rtn;
	}
	
	/*Below is IT dashboard, to check for IT specifications
	 * 1. OMS fetching orders count check, e.g. if 10 minites got less than 3 new orders, alert!
	 * 2. Job WatchMe, any job can send request to watchme, default life-time 60 minutes, if after life time, there is no gisnal to IamDone, judge this job got exception.
	 *  
	 * */
	public static function checkTasksDefined($sendEmail='N'){
		$command = Yii::$app->db_queue2->createCommand("select * from db_it_check_task where enabled='Y' " );
		$Tasks = $command->queryAll();
		$now_str = date('Y-m-d H:i:s');
		foreach ($Tasks as $aTask){
			//check the last_run_time '2016-12-23 15:11:16', and get the last check time CheckingSlots, decide whether now we should active a new check
			$lastCheckMinute = substr($aTask['last_run_time'],14,2); //=11
			$lastCheckLastSlotTimeMaxEdge = floor($lastCheckMinute / 5) * 5;   //=10,也就是上次次check目标是 区间[x,10分)  
			$lastCheckSlotEndTime = substr($aTask['last_run_time'],0,14). substr((100+$lastCheckLastSlotTimeMaxEdge),1,2).":00:00";//'2016-12-23 10:00:00'
			//那么现在到了该next check的时间了吗？
			//echo "For ".$aTask['job_name'].", Last run time is ".$aTask['last_run_time']." so last check slot end time is $lastCheckSlotEndTime \n";
			if ( $aTask['last_run_time'] <>'0000-00-00 00:00:00' and date('Y-m-d H:i:s',strtotime('-'.($aTask['period_slots'] * 5).' minutes')) < $lastCheckSlotEndTime  ){
				//还不需要做下一次的，skip it
				echo "Period is ".$aTask['period_slots']."slots , so can ignore this time \n";
				continue;
			}
				
			$getDoneCount = self::getJobGetDataCount($aTask['job_name'],$aTask['period_slots']);
			
			if ($getDoneCount < $aTask['min_count'])
				$check_result = "E";
			else 
				$check_result = "";
			
			$slot_days = floor($aTask['period_slots'] / 12/24);
			$slot_hours =  floor($aTask['period_slots'] / 12 ) % 24;
			$slot_minutes =  floor($aTask['period_slots']*5   ) % 60;
			
			$time1='';
			if ($slot_days > 0)
				$time1 .= $slot_days."天";
			
			if ($slot_hours > 0)
				$time1 .= $slot_hours."小时";
			
			if ($slot_minutes > 0)
				$time1 .= $slot_minutes."分钟";
			
			$msg =  "Job ".$aTask['job_name']." 期待最少获取新订单 ".$aTask['min_count']."个，在最近的 $time1 , 实际获取到 $getDoneCount 个 , 所以结果是 |$check_result| <br>\n";
			echo $msg;
			$command = Yii::$app->db_queue2->createCommand("update db_it_check_task set check_result='$check_result', last_run_time='$now_str' where id=". $aTask['id'] );
			//$command->bindValue(':addi_info', $track_obj['addi_info'], \PDO::PARAM_STR);
			//$command->bindValue(':order_id', $order_id, \PDO::PARAM_STR);
			$affectRows = $command->execute();
			
			if ($check_result == "E" and $sendEmail=='Y'){
				$emais_str = str_replace(",",";",$aTask['email_to']);
				$emails = explode(";",$emais_str);
				foreach ($emails as $anEmailAddr){
					if (empty($anEmailAddr)) continue;
					$rtn1 = MailHelper::sendMailBySQ("IT_department@littleboss.com", "技术部自动监测", $anEmailAddr, "技术部自动监测有Job可能失效 ".$aTask['job_name'], "技术部自动监测有Job预定期间获取的订单数量过少  $msg <br> check time: $now_str ");
					echo "Sent email to $anEmailAddr , sent return ".print_r($rtn1,true)." <br>";
					//MessageApiHelper::insertEdmQueue($datas);
				}
			}
			
		}//end of each task
	}
	
	public static function getJobGetDataCount($jobName,$slots=1){
	 
		$totalCount=0;
		for ($i=1; $i<=$slots; $i++){
			$targetSlotTime =  date('Y-m-d H:i:s',strtotime('-'.($i * 5).' minutes'));//if now is 15:12:50, targetSlottime is 15:07:50
			$slotNo =  floor( (substr($targetSlotTime,14,2))/5) ; //15:07:50 =slot 1 
			$thisCount  = RedisHelper::RedisGet2("IT_DASHBOARD", $jobName.substr($targetSlotTime,0,14)."SLOT".$slotNo);
			//echo "<br>Get count $thisCount from slot ".$jobName.substr($targetSlotTime,0,14)."SLOT".$slotNo."<br>";
			$totalCount +=  (empty($thisCount)?0:$thisCount);
		}
		return $totalCount;
	}
	
	public static function AddJobDataCount($jobName,$increament=1){
		$now_str = date('Y-m-d H:i:s'); //'2016-12-23 15:11:16'
		
		$nowMinute = substr($now_str,14,2); //=11
		$nowSlot = floor($nowMinute / 5); //e.g. 2,  possible value:0-11
		
		//echo "Now server date time is $now_str , write to slot $nowSlot<br>";// 因为这个啊公调用 ，加上个会搞烂格式
		return RedisHelper::RedisAdd2("IT_DASHBOARD", $jobName.substr($now_str,0,14)."SLOT".$nowSlot,$increament);
	}
	
	/*
	 * Param1: jobName, freeText, 多个同name的进程，可以使用同一个job name，本函数会加上调用的时间进去区分多个进程同一个name
	 * maxLife: integer, minutes, e.g. 60, if 60 minutes elapsed after watchMeUp, auto check job will treat it as dead job and alert
	 * $sendEmail can be multiple email address, use ',' to split
	 * */
	public static function WatchMeUp($jobName,$maxLife=60,$sendEmail=''){
		global $DASHBOARD;
		$now_str = date('Y-m-d H:i:s'); //'2016-12-23 15:11:16'
		$DASHBOARD['JOBNAME'] =  $jobName."_UP_".$now_str;
		return RedisHelper::RedisSet2("IT_DASHBOARD", $DASHBOARD['JOBNAME'] ,  $maxLife .'/'.$sendEmail);
	}
	
	/*
	不需要输入job name，会本进程记住用过的watch me up 的job name
	* */
	public static function WatchMeDown( ){ 
		global $DASHBOARD;
		if (empty($DASHBOARD['JOBNAME'])) return;
		return RedisHelper::RedisDel2("IT_DASHBOARD", $DASHBOARD['JOBNAME'] );
	}
	
	/*会根据报告up的job的definition，找到超时没有down的，报告email，然后删除之
	 * */
	public static function checkJobDead($sendEmail='N'){
		$now_str = date('Y-m-d H:i:s'); //'2016-12-23 15:11:16'
		$keys = RedisHelper::RedisExe2('hkeys',array('IT_DASHBOARD'));

		if (!empty($keys)){
			foreach ($keys as $keyName){ //keyName example:TrackerMainJob_UP_2016-12-31:12:15:22
				$slotPos = strpos($keyName, "_UP_");
				if ( $slotPos !== false and $slotPos > 0){
					 
					$d1 = explode( "_UP_",$keyName);
					$theJobName = $d1[0];
					$theStartTime = $d1[1];
					
					$handling= RedisHelper::RedisGet2("IT_DASHBOARD", $keyName  );
					$d2 = explode( "/",$handling);
					$maxMinutes = $d2[0];
					$emails1 = (empty($d2[1])?'':$d2[1]);
					 
					if ($theStartTime < date('Y-m-d H:i:s',strtotime('-'.$maxMinutes.' minutes')) ){
						echo "$theJobName up at $theStartTime,$now_str found died after $maxMinutes minutes<br>\n";
						RedisHelper::RedisExe2 ('hdel',array('IT_DASHBOARD',$keyName));

						if ($sendEmail == 'Y' ){
							$emais_str = str_replace(",",";",$emails1);
							$emails = explode(";",$emais_str);
							foreach ($emails as $anEmailAddr){
								if (empty($anEmailAddr)) continue;
								$rtn1 = MailHelper::sendMailBySQ("IT_department@littleboss.com", "后台job监测", $anEmailAddr, "监测有后台Job失效 ","$theJobName up at $theStartTime, found died after $maxMinutes minutes<br> check time: $now_str ");
								echo "Sent email to $anEmailAddr , sent return ".print_r($rtn1,true)." <br>";
										//MessageApiHelper::insertEdmQueue($datas);
							}
						}
					}
				}//end if found SLOT
			}//end of each key name, like user_1.carrier_frequency
		}
	}
	
	
	/*默认会吧3天前的删除掉
	 * */
	public static function houseKeepingJobData(){
		$keys = RedisHelper::RedisExe2 ('hkeys',array('IT_DASHBOARD'));
			
		if (!empty($keys)){
			foreach ($keys as $keyName){
				$slotPos = strpos($keyName, "SLOT");
				if ( $slotPos !== false and $slotPos > 14){
					$theDate = substr($keyName,$slotPos-14, 10);
					if ($theDate < substr(date('Y-m-d H:i:s',strtotime('-3 days')),0,10)){
						echo "$theDate is 3 days ago, try to delete the old $keyName <br>\n";
						RedisHelper::RedisExe2 ('hdel',array('IT_DASHBOARD',$keyName));
					}
				}//end if found SLOT
			}//end of each key name, like user_1.carrier_frequency
		}
	}
	
	
	
	
}//end of class
?>