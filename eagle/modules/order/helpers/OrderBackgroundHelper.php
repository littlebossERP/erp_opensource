<?php
namespace eagle\modules\order\helpers;

use yii;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\ConfigHelper;
use console\helpers\AliexpressHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\models\QueueOrderAutoCheck;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\catalog\models\ProductAliases;
use eagle\modules\catalog\models\Product;
use eagle\modules\util\helpers\OperationLogHelper;
use yii\db\Query;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\catalog\models\ProductConfigRelationship;
use eagle\modules\order\models\OrderSystagsMapping;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\order\models\OrderRelation;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\listing\models\EbayItem;
use common\api\ebayinterface\shopping\getsingleitem;
use eagle\modules\util\helpers\RedisHelper;
use eagle\models\OdOrderShipped;
use eagle\modules\permission\helpers\UserHelper;
use Qiniu\json_decode;

class OrderBackgroundHelper {
	static public $OrderCheckLog = false;
	static public $OrderAutoCheckQueueVersion = '';
	static public $OrderAutoCheckQueueTotalJob = 2;
	
	static public function test(){
		echo "ok";
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 生成 订单日常统计缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $start_time			string 
	 * 			$platform
	 * 			$accounts
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderBackgroundHelper::OrderDailySummary();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function OrderDailySummary($start_time ,$platform , $accounts , $isShowLog =false, $interval =7){
		try{
			$platform= strtolower($platform);
			if(empty($start_time))
				$start_time = date('Y-m-d H:i:s');
			$summary_end_date = date('Y-m-d');
			$summary_start_date = date('Y-m-d',strtotime($start_time)-3600*24*$interval);
			$summary_start_dateTime = date('Y-m-d H:i:s',strtotime($start_time)-3600*24*$interval);
			echo "start $summary_start_date";
			//性能测试
			if ($isShowLog){
				$logTimeMS1=TimeUtil::getCurrentTimestampMS();
				$logMemoryMS1 = (memory_get_usage()/1024/1024);
				echo "\n".__FUNCTION__.' step 1 ready to check '.$platform .' ('.count($accounts).') :'.(memory_get_usage()/1024/1024). 'M '; 
			}
			
			$uid =-1;//设置uid 初始值 
			foreach ($accounts as $account){
				if ($uid==-1 ||$uid !=$account['uid']){
					//初始化 和前后两个账号的uid 不同的情况下 ，需要切换数据库
					$uid = $account['uid'];
					//检查是否活跃用户
					$isActive = true;
					
					if ($isActive ==false){
						echo "\n uid=$uid is not active user";
						continue;
					}
					//性能测试
					if ($isShowLog){
						$logTimeMS2_A=TimeUtil::getCurrentTimestampMS();
						$logMemoryMS2_A = (memory_get_usage()/1024/1024);
						
					}
					 
					//性能测试
					if ($isShowLog){
						$logTimeMS2_B=TimeUtil::getCurrentTimestampMS();
						$logMemoryMS2_B = (memory_get_usage()/1024/1024);
						echo "\n uid=".$uid.' '.__FUNCTION__.' ->  changeUserDataBase '.$platform .' spend  T=('.($logTimeMS2_B-$logTimeMS2_A).') and M='.($logMemoryMS2_B-$logMemoryMS2_A).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M'; //test kh
					
					}
					
					//统计各个账号的订单数量
					$tmp_yestday_list = OdOrder::find()->select(['selleruserid','sum'=>'count(1)' ,'theday'=>"from_unixtime(`paid_time`,'%Y-%m-%d')"])
					->where(['between','paid_time',strtotime($summary_start_date),strtotime($summary_end_date)])
					->orWhere(['paid_time'=>strtotime($summary_start_date)])
					->andWhere(['order_source'=>$platform])
					->groupBy(['selleruserid','theday'])
					->asArray()->All();
					$yestday_list = [];
					
					//性能测试
					if ($isShowLog){
						$logTimeMS2_C=TimeUtil::getCurrentTimestampMS();
						$logMemoryMS2_C = (memory_get_usage()/1024/1024);
						echo "\n uid=".$uid.' '.__FUNCTION__.' ->  get order data '.$platform .' spend  T=('.($logTimeMS2_C-$logTimeMS2_B).') and M='.($logMemoryMS2_C-$logMemoryMS2_B).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and total account ='.count($tmp_yestday_list).''; //test kh
								
					}
					//整理根据账号统计订单数量的数据格式 
					foreach($tmp_yestday_list as $row){
						$yestday_list[$row['selleruserid']][$row['theday']] = $row['sum'];
					}
					
				}//end of first time or change database get data
				
				//性能测试
				if ($isShowLog){
					$logTimeMS3=TimeUtil::getCurrentTimestampMS();
					$logMemoryMS3 = (memory_get_usage()/1024/1024);
					//echo "\n uid=".$uid.' '.__FUNCTION__.' ->  get order data '.$platform .' spend  T=('.($logTimeMS2_C-$logTimeMS2_B).') and M='.($logMemoryMS2_C-$logMemoryMS2_B).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and total account ='.count($tmp_yestday_list).''; //test kh
				
				}
				
				for ($i=1;$i<=$interval;$i++ ){
					$currentDate = date('Y-m-d',strtotime($start_time)-3600*24*$i);
					
					//读取 账号对应
					if (!empty($yestday_list[$account['username']][$currentDate])){
						$yestday_count = $yestday_list[$account['username']][$currentDate];
					}else{
						//没有对应的数据表示当天没有订单
						$yestday_count=0;
					}
					
					
					$query = "SELECT * FROM `od_order_summary` WHERE `platform`='$platform' and `puid`=$uid and `seller_id`='".$account['username']."' and `thedate`='".$currentDate."'";
					$command = Yii::$app->db->createCommand($query);
					$record = $command->queryOne();
					//性能测试
					if ($isShowLog){
						$logTimeMS4=TimeUtil::getCurrentTimestampMS();
						$logMemoryMS4 = (memory_get_usage()/1024/1024);
						echo "\n uid=".$uid.' '.__FUNCTION__.' ->  get ['.$account['username'].'] summary  data '.$platform .' spend  T=('.($logTimeMS4-$logTimeMS3).') and M='.($logMemoryMS4-$logMemoryMS3).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M '; //test kh
							
					}
					if(!empty($record)){
						$old_count = $record['create_order_count'];
						$addi_info = $record['addi_info'].'update at '.TimeUtil::getNow().',count:'.$old_count.'->'.$yestday_count.';';
						$sql = "UPDATE `od_order_summary` SET
						`create_order_count`=$yestday_count,`addi_info`='$addi_info'
						WHERE `platform`='$platform' and `puid`=$uid and `seller_id`='".$account['username']."' and `thedate`='".$currentDate."'";
						if ($isShowLog) $op_type = "update";
							
					}else{
						$sql = "INSERT INTO `od_order_summary`
						(`platform`, `puid`, `seller_id`, `thedate`, `create_order_count`, `addi_info`) VALUES
						('$platform',$uid,'".$account['username']."','".$currentDate."',$yestday_count,'')";
						if ($isShowLog) $op_type = "insert";
					}
					$command = \Yii::$app->db->createCommand($sql);
					$affect_record = $command->execute();
					echo "\n ".$account['username']."-[".$currentDate."] =".$yestday_count;
				}
				
				//$del_redis_record = \Yii::$app->redis->hdel(ucfirst($platform).'Oms_TempData',"user_$uid".".".$account['username'].".".$summary_start_date);
				$del_redis_record = RedisHelper::RedisDel(ucfirst($platform).'Oms_TempData',"user_$uid".".".$account['username'].".".$summary_start_date );
				//性能测试
				if ($isShowLog){
					$logTimeMS5=TimeUtil::getCurrentTimestampMS();
					$logMemoryMS5 = (memory_get_usage()/1024/1024);
					echo "\n uid=".$uid.' '.__FUNCTION__.' ->  '.$op_type.' ['.$account['username'].']-['.$summary_start_date.'] summary  data '.$platform .' spend  T=('.($logTimeMS5-$logTimeMS4).') and M='.($logMemoryMS5-$logMemoryMS4).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and effort='.$affect_record; //test kh
				
				}
			}//end of each acount
		}catch (\Exception $e){
			echo "\n".$e ;
		}
	}//end of OrderDailySummary
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 获取用户绑定的aliexpress账号的最近X日订单获取数量chart data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid			
	 * 			$days 	获取天数
	 * 			$platform	平台
	 * 			$accounts	账户
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderBackgroundHelper::getChartDataByUid_Order(1,7,'aliexpress',$accounts);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getChartDataByUid_Order($uid,$days=7,$platform , $accounts){
		$today = date('Y-m-d');
		$daysAgo = date('Y-m-d', strtotime($today)-3600*24*$days);
		//用户近目标日期内所有数据
		$query = "SELECT * FROM `od_order_summary` WHERE `platform`='$platform' and `puid`=$uid and `thedate`>='".$daysAgo."' and `thedate`<'".$today."'";
		$command = Yii::$app->db->createCommand($query);
		$records = $command->queryAll();
		
		//没有账号信息或者订单信息，直接return
		if(empty($records) && empty($accounts))
			return [];
		
		
		$dateStartStamp = strtotime($today);
		$dateEndStamp = $dateStartStamp+3600*24;
		$query = "SELECT `selleruserid` , count(1) as sum_count FROM `od_order_v2` WHERE `order_source`='$platform' AND
		`paid_time`>$dateStartStamp AND `paid_time`<$dateEndStamp group by  `selleruserid`";
		$command = Yii::$app->subdb->createCommand($query);
		
		$tmpTodayOrderData = $command->queryAll();
		$TodayOrderData =[];
		foreach($tmpTodayOrderData as $tmpData){
			$TodayOrderData[$tmpData['selleruserid']] = $tmpData['sum_count'];
		}
		//$create_order_count = count($records);
		//echo  $command->getRawSql();
	
		$chart['type'] = 'column';
		$chart['title'] = '近'.$days.'日'.ucfirst($platform).'订单数';
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
				
			foreach ($accounts as $account){
				//print_r($account);
				if (!empty($account['store_name']))
					$name = $account['store_name']."(". $account['username'].")";
				else 
					$name = $account['username'];
				//echo "<br>".$name;
				$create_order_count = 0;
				if($i==0){
					
						
					/*
					//$temp_count = \Yii::$app->redis->hget('CdiscountOms_TempData',"user_$uid".".".$account['username'].".".$today);
						if(empty($temp_count)){
					$create_order_count=0;
					}
					else
						$create_order_count=(int)$temp_count;
					*/
					if (!empty($TodayOrderData[$account['username']])){
						$total +=$TodayOrderData[$account['username']];
						$create_order_count = $TodayOrderData[$account['username']];
					}
						
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
			
			//echo json_encode($chart);
	
		return $chart;
	}//end of function 
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 获取用户绑定的aliexpress 账号的最近X日订单利润(已计算的)情况
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid
	 * 			$days 	获取天数
	 * 			$platform	平台
	 * 			$accounts	账户
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderBackgroundHelper::getChartDataByUid_Profit(1,7,'aliexpress',$accounts);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getChartDataByUid_Profit($uid,$days=7,$platform , $accounts){
		$today = date('Y-m-d');
		$daysAgo = date('Y-m-d', strtotime($today)-3600*24*$days);
		//用户近目标日期内所有数据
		$daysOrders = OdOrder::find()
		->where(" `paid_time`>=".strtotime($daysAgo)." and `paid_time`<=".time()." and `profit` IS NOT NULL ")
		->andWhere(['order_source'=>$platform])
		->asArray()->all();
		
		//没有账号信息或者订单信息，直接return
		if(empty($daysOrders) && empty($accounts))
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
		$chart['title'] = '近'.$days.'日'.ucfirst($platform).'订单已统计的利润';
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
	
			foreach ($accounts as $account){
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
	}//end of function 
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 获取OMS dashboard广告
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid
	 * 			$every_time_shows 	每次展示广告数
	 * 			$platform	平台
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderBackgroundHelper::getAdvertDataByUid(1,2,'aliexpress');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getAdvertDataByUid($uid,$every_time_shows=2,$platform){
		$advertData = [];
		//$last_advert_id = \Yii::$app->redis->hget(ucfirst($platform).'Oms_DashBoard',"user_$uid".".last_advert");
		$last_advert_id = RedisHelper::RedisGet(ucfirst($platform).'Oms_DashBoard',"user_$uid".".last_advert" );
		if(empty($last_advert_id))
			$last_advert_id=0;
		$new_last_advert_id = $last_advert_id;
		if(!empty($last_advert_id)){
			$query = "SELECT * FROM `od_dash_advert` WHERE (`app`='".ucfirst($platform)."_Oms' or `app`='All_Oms') and `id`>".(int)$last_advert_id."  ORDER BY `id` ASC limit 0,$every_time_shows ";
			$command = Yii::$app->db->createCommand($query);
			$advert_records = $command->queryAll();
			foreach ($advert_records as $advert){
				$advertData[$advert['id']] = $advert;
				$new_last_advert_id = $advert['id'];
			}
			if(count($advertData)<$every_time_shows){
				$reLimit = $every_time_shows - count($advertData);
				$query_r = "SELECT * FROM `od_dash_advert` WHERE `app`='".ucfirst($platform)."_Oms' or `app`='All_Oms' ORDER BY `id` ASC limit 0,$reLimit ";
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
			$query = "SELECT * FROM `od_dash_advert` WHERE `app`='".ucfirst($platform)."_Oms' or `app`='All_Oms' ORDER BY `id` ASC limit 0,$every_time_shows ";
			$command = Yii::$app->db->createCommand($query);
			$advert_records = $command->queryAll();
			foreach ($advert_records as $advert){
				$advertData[$advert['id']] = $advert;
				$new_last_advert_id = $advert['id'];
			}
		}
	
		//$set_advert_redis = \Yii::$app->redis->hset(ucfirst($platform).'Oms_DashBoard',"user_$uid".".last_advert",$new_last_advert_id);
		$set_advert_redis = RedisHelper::RedisSet(ucfirst($platform).'Oms_DashBoard',"user_$uid".".last_advert",$new_last_advert_id);
		return $advertData;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据cd oms 封装 根据uid 获取该用户需要提醒的OMS信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::getOMSReminder();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOMSReminder($platform , $uid=''){
		if (empty($uid)){
			$uid = \Yii::$app->user->id;
		}
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
		$oldReminder = ConfigHelper::getConfig(ucfirst($platform)."OMS/ReminderInfo",'NO_CACHE');
	
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
			$ot_Orders = OdOrder::find()->where(['order_source'=>$platform])->andWhere(['weird_status'=>'wfs'])->count();
			if($ot_Orders>0)
				$rtn['remind']['YiFuKuan'] = $ot_Orders;
		}
		if($remindFaHuoZhong){
			$ot_Orders = OdOrder::find()->where(['order_source'=>$platform])->andWhere(['weird_status'=>'wfd'])->count();
			if($ot_Orders>0)
				$rtn['remind']['FaHuoZhong'] = $ot_Orders;
		}
		if($remindYiFaHuo){
			$ot_Orders = OdOrder::find()->where(['order_source'=>$platform])->andWhere(['weird_status'=>'wfss'])->count();
			if($ot_Orders>0)
				$rtn['remind']['YiFaHuo'] = $ot_Orders;
		}
		if($remindZhuangTaiYiChang){
			$ot_Orders = OdOrder::find()->where(['order_source'=>$platform])->andWhere(['weird_status'=>'sus'])->count();
			if($ot_Orders>0)
				$rtn['remind']['ZhuangTaiYiChang'] = $ot_Orders;
		}
		if($remindWuTianWeiShangWang){
			$ot_Orders = OdOrder::find()->where(['order_source'=>$platform])->andWhere(['weird_status'=>'tuol'])->count();
			if($ot_Orders>0)
				$rtn['remind']['WuTianWeiShangWang'] = $ot_Orders;
		}
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取当前 订单的商品是否生成到商品库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						array 
	 *
	 * @invoking					OrderBackgroundHelper::getExistProductRuslt($order);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getExistProductRuslt($Models){
		$existProductResult = [];
		$needToCheckKey=[];
		foreach($Models as $tmpOrder){
			$itmes = $tmpOrder->getItemsPT();
			foreach($itmes as $tmpItem){
				if (!empty($tmpItem['sku'])){
					//sku为空的情况 下以product name 为sku
					$exist_product_key = $tmpItem['sku'];
				}else{
					$exist_product_key = $tmpItem['product_name'];
				}
				 
				$needToCheckKey [$exist_product_key] = $exist_product_key;
			}
		}
		
		$results = ProductApiHelper::hasProductArray($needToCheckKey);
		foreach ($needToCheckKey as $aKey)
			$existProductResult[strtolower($aKey)] = ! empty($results[strtolower($aKey)]);
		
		return $existProductResult;
	} //end of function getExistProductRuslt
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取当前 订单的商品是否生成到商品库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 *
	 * @invoking					OrderBackgroundHelper::getExistProductRuslt($order);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getExitProductRootSKU($Models){
		$existProductResult = [];
		foreach($Models as $tmpOrder){
		
			foreach($tmpOrder->items as $tmpItem){
				if (!empty($tmpItem['sku'])){
					//sku为空的情况 下以product name 为sku
					$exist_product_key = $tmpItem['sku'];
				}else{
					$exist_product_key = $tmpItem['product_name'];
				}
					
				if (isset($existProductResult[$exist_product_key])){
					continue;
				}
				$existProductResult[$exist_product_key] = ProductApiHelper::getRootSkuByAlias($exist_product_key,$tmpOrder->order_source , $tmpOrder->selleruserid);
			}
		}
		return $existProductResult;
	}//end of function getExitProductInfo
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 在订单自动检测队列增加一条记录
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid					uid
	 * 			  $orderId				小老板订单号
	 * 			  $priority				优先级
	 * 			  $orderInfo			订单信息
	 +---------------------------------------------------------------------------------------------
	 * @return						array 
	 * 										success   插入队列是否成功
	 * 										message	     插入失败的相关信息
	 * 										code	    错误代号
	 *
	 * @invoking					OrderBackgroundHelper::insertOrderAutoCheckQueue(1，0001，3 );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function insertOrderAutoCheckQueue($uid , $orderId ,$priority='3'  ){
		try {
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n  ".(__function__).' ready insert queue uid='.$uid." and  $orderId and $priority";
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
			
			$queueData = \eagle\models\QueueOrderAutoCheck::findOne(['puid'=>$uid , 'order_id'=>$orderId]);
			if (!empty($queueData)){
				//log 日志 ， 调试相关信息start
				$GlobalCacheLogMsg = "\n  ".(__function__).' already insert queue uid='.$uid." and  $orderId and $priority";
				\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
				//log 日志 ， 调试相关信息end
				return ['success'=>false,'message'=>'uid= '.$uid.' order id='.$orderId.' error mesg :已经检测请不要重复插入！','code'=>'E001'];
			}
			
			$model = new \eagle\models\QueueOrderAutoCheck();
			$model->puid = $uid;
			$model->order_id = $orderId;
			$model->status = 'P'; // Pending
			
			
			$model->priority = $priority; //优先级
			$model->create_time = time();
			$model->update_time = time();
			if ($model->save()){
				//log 日志 ， 调试相关信息start
				$GlobalCacheLogMsg = "\n  ".(__function__).' success insert queue uid='.$uid." and  $orderId and $priority";
				\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
				//log 日志 ， 调试相关信息end
				return ['success'=>true,'message'=>'','code'=>''];
			}else{
				$errors = $model->getErrors();
				//log 日志 ， 调试相关信息start
				$GlobalCacheLogMsg = "\n  ".(__function__).' error uid= '.$uid.' order id='.$orderId.' error mesg :'.json_encode($errors);
				\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
				\Yii::info($GlobalCacheLogMsg,'file');
				//log 日志 ， 调试相关信息end
				return ['success'=>false,'message'=>'uid= '.$uid.' order id='.$orderId.' error mesg :'.json_encode($errors),'code'=>'E002'];
			}
		} catch (\Exception $e) {
			$errors = $e->getMessage();
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n  ".(__function__).' error uid= '.$uid.' order id='.$orderId.' error mesg :'.json_encode($errors);
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			\Yii::info($GlobalCacheLogMsg,'file');
			//log 日志 ， 调试相关信息end
			return ['success'=>false,'message'=>'uid= '.$uid.' order id='.$orderId.' error mesg :'.json_encode($errors),'code'=>'E003'];
		}
		
	}//end of insertOrderAutoCheckQueue
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 在订单自动检测队列 中根据 uid 来批量检测订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $sub_id					这个变量暂时没有用
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 										success   插入队列是否成功
	 * 										message	     插入失败的相关信息
	 * 										code	    错误代号
	 *
	 * @invoking					OrderBackgroundHelper::insertOrderAutoCheckQueue(1，0001，3 );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function handleOrderAutoCheckQueue($sub_id=''){
		global $CACHE;
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
		
		$JOBID = $CACHE['JOBID'];
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 0 ".$JOBID],"file");
		$current_time=explode(" ",microtime());		
		$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
		if (self::$OrderCheckLog)
			\Yii::info("multiple_process_sub step1 subjobid=$JOBID");
		
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$currentOrderAutoCheckQueueVersion = ConfigHelper::getGlobalConfig("Order/OrderAutoCheckQueueVersion",'NO_CACHE');
		if (empty($currentOrderAutoCheckQueueVersion))
			$currentOrderAutoCheckQueueVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$OrderAutoCheckQueueVersion))
			self::$OrderAutoCheckQueueVersion = $currentOrderAutoCheckQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$OrderAutoCheckQueueVersion <> $currentOrderAutoCheckQueueVersion){
			$msg = "Version new $currentOrderAutoCheckQueueVersion , this job ver ".self::$OrderAutoCheckQueueVersion." exits for using new version $currentOrderAutoCheckQueueVersion.";
			if (self::$OrderCheckLog)
				\Yii::info($msg);
			exit($msg);
		}
		
		$start_time = time();
		
		//根据sub id 来 选取 puid 
		/**/
		$sub_id = '';// TODO 需要开多进程拉取再把这个去掉
		if ($sub_id !== ''){
			$modSql = " puid % ".self::$OrderAutoCheckQueueTotalJob." = ".$sub_id;
		}else{
			$modSql = ' 1=1 ';//只是为简化查询条件的写法
		}
		
		
		$condition = ['status'=>'P'];
		
		/*-----------------------------------------------------------------------------------------*/
		//QueueOrderAutoCheck::updateAll(['status'=>'P'],['puid'=>5]);// testkh 
		/*-----------------------------------------------------------------------------------------*/
		
		$Data = QueueOrderAutoCheck::find()->where($modSql)->andwhere($condition)->andWhere("next_time is null or next_time<".time())->orderBy("priority desc");
		//echo "\n".$Data->createCommand()->getRawSql();
		//exit();
		//获取puid
		$oneData = $Data->asArray()->one();
		//var_dump($oneData);
		$uid = $oneData['puid'];
		if (empty($uid)){
			return ['success'=>false,'message'=>'队列没有数据！'];
		}
		echo "\n uid=".$uid." start ".date("Y-m-d H:i:s")." v2.0";
		//为队列数据上锁
		$effect = QueueOrderAutoCheck::updateAll(['status'=>'S'],['puid'=>$uid, 'status'=>'P']);
		
 
		
		//获取需要检测的订单号
		$targetOrder = QueueOrderAutoCheck::find()->where(['puid'=>$uid, 'status'=>'S']);
		$orderIdList = [];
		foreach($targetOrder->asArray()->each(1) as $row){
			//判断是否速卖通订单，并且还没同步客选物流的话，延迟10秒，lrq20180411
			$order = OdOrder::findOne(['order_id' => $row['order_id']]);
			if($order->order_status == 200 && $order->order_source == 'aliexpress'){
				$check_status = false;
				if(!empty($order->addi_info)){
					$addi_info = json_decode($order->addi_info, true);
					if(!empty($addi_info) && !empty($addi_info['shipping_service'])){
						$check_status = true;
					}
				}
				if(!$check_status){
					//如果超过半个钟还没同步客选物流，则继续
					if($row['update_time'] - $row['create_time'] < 1800){
						//延迟10秒执行
						QueueOrderAutoCheck::updateAll(['status'=>'P' , 'update_time'=>time() , 'next_time'=>time() + 10],['order_id' => $row['order_id']]);
						continue;
					}
				}
			}
			
			$orderIdList[] = $row['order_id'];
		}
		//echo "current uid ".\Yii::$app->subdb->getCurrentPuid();
		
		//if (empty($orderIdList)) return ['success'=>false,'message'=>'队列没有数据！'];
		
		//echo QueueOrderAutoCheck::find()->where(['puid'=>$uid, 'status'=>'S'])->createCommand()->getRawSql();
		
		/*****************************************    设置全局缓存逻辑   start  *****************************************************/
		
		// 性能检测 start
		$OPType = "OrderCheck";
		$PerformanceLogMsg = $OPType. " start mark performance ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
			
		//获取 cache
		self::setOrderCheckGlobalCache($uid, $orderIdList);
		
		// 性能检测 start
		$OPType = "OrderCheck";
		$PerformanceLogMsg = $OPType. " set order global cache ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		/*****************************************    设置全局缓存逻辑    end  *****************************************************/
		
		/*****************************************    判断合并订单逻辑   start  *****************************************************/
		try {
			//结合 global cache 找出当前批次订单是否存在可以合并的订单
			$fullName = 'System';
			if (!empty($CACHE[$uid]['order_address'])){
					
				$canMergeOrderList = []; // 当前 批次可以合并的订单
				//确定是否存在可以合并的订单， 地址缓存中 订单数大干1为可以合并的订单
				foreach($CACHE[$uid]['order_address'] as $SameAddressOrderIdList){
					
					if (count($SameAddressOrderIdList)>1){
						//echo "\n same address :";
						$tmpOrderIdList =[];
						foreach($SameAddressOrderIdList as $_tmpMOrderID){
							//当然订单是否需要合并
							if (isset($CACHE[$uid]['wait_merge'][(int)$_tmpMOrderID])){
								//由于 放在order id 作为 key　变成了int　所以　前缀的0都已经去除了，　所以需要先转int类型后再比较
								$tmpOrderIdList[] = (int)$_tmpMOrderID;
							}
							//echo ",".$_tmpMOrderID;
							
							/*
							if (isset($CACHE[$uid]['order'][$_tmpMOrderID]['order_status']))
								echo "\n". $_tmpMOrderID." ".$CACHE[$uid]['order'][$_tmpMOrderID]['order_status'];
								*/
						}//end of each $SameAddressOrderIdList
						
						/*
						//存在相同地址的订单号，再与当前批次的订单数据对比
						$currentMergeOrderIdList = array_intersect($tmpOrderIdList , $orderIdList);
						*/
						$currentMergeOrderIdList = $tmpOrderIdList;//假如不是待合并订单都改为待合并
						//记录下需要打上合并标志的订单
						$canMergeOrderList = array_merge($canMergeOrderList, $currentMergeOrderIdList);
					}//end of if count $SameAddressOrderIdList > 1
				}//end of each $CACHE[$uid]['order_address']
				//标记指定需要合并的订单 start
				if (!empty($canMergeOrderList)){
					//批量更新 可合并的订单
					echo "\n puid=$uid order merge before update relation orderid:".json_encode($canMergeOrderList);
					$thisRt = OdOrder::updateAll(['pay_order_type'=>OdOrder::PAY_CAN_SHIP , 'exception_status'=>OdOrder::EXCEP_WAITMERGE ],['order_id'=>$canMergeOrderList , 'order_status'=>OdOrder::STATUS_PAY]);
					echo "\n puid=$uid order merge effect ".$thisRt." and relation orderid:".json_encode($canMergeOrderList);
					//start to write log
					//由于 批量update的原因，不能确定修改了哪张订单， 所以假如有修改成功的，都为所有都写上一个操作日志
					if ($thisRt >0){
						$delredisPlatformList = [];
						foreach($canMergeOrderList as $orderid){
							OperationLogHelper::log('order',$orderid,'检测订单','添加异常标签:['.OdOrder::$exceptionstatus[OdOrder::EXCEP_WAITMERGE].']',$fullName);
							
							
							if (isset($CACHE[$uid]['platform'][$orderid])){
								//echo "\n  ============= $orderid=".$CACHE[$uid]['platform'][$orderid];
								if (in_array($CACHE[$uid]['platform'][$orderid] , $delredisPlatformList) ==false){
									$delredisPlatformList[] = $CACHE[$uid]['platform'][$orderid];
								}
							}
						}
						try {
							foreach($delredisPlatformList as $delRedisPlatform){
								echo "\n puid=$uid  $delRedisPlatform redis is delete! ";
								RedisHelper::delOrderCache($uid,$delRedisPlatform,'Menu StatisticData');
							}
						} catch (Exception $e) {
						}
					}//end of write log
				}//标记指定需要合并的订单 end
			}//end of global cache order_address empty check
		} catch (\Exception $e) {
			echo " \n order address exception E001 line no:".$e->getLine()." Message :".$e->getMessage();
		}
		
		
		
		// 性能检测 start
		$OPType = "OrderCheck";
		$PerformanceLogMsg = $OPType. " check merge order  ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		/*****************************************    判断合并订单逻辑   end  *****************************************************/
		
		/*****************************************    商品检测逻辑   start  *****************************************************/
		try {
			if ($CACHE[$uid]['sku_toproduct']) {
				echo "\n puid=$uid open auto create product :".print_r($CACHE[$uid]['sku_toproduct'],true);
				foreach ($orderIdList as $orderid){
					//sku是否存在
					//$skus=OrderHelper::getorderskuswithbundle($orderid);
					//开启自动生成商品模式
					OrderHelper::_autoCompleteProductInfo($orderid,'order','检测订单',$fullName);
					
				}
			}else{
				echo "\n puid=$uid close auto create product :".$CACHE[$uid]['sku_toproduct'];
			}
			//刷新商品
			if (!empty($CACHE[$uid]['newSKU'])){
				
				$tmpNewProductCount= 0;
				$updateSKUList = [];
				foreach(Product::find()->where(['sku'=>$CACHE[$uid]['newSKU']])->asArray()->each(1) as $row){
					$CACHE[$uid]['product'][$row['sku']] = $row;
					$updateSKUList[] = $row['sku'];
					$tmpNewProductCount++;
				}
				echo "\n puid=$uid create new  product :".count($CACHE[$uid]['newSKU']);
				if (!empty($updateSKUList)){
					$updateSql = "update od_order_item_v2 set root_sku=sku  where order_id in('".implode("','",$orderIdList)."') and sku in ('".implode("','",$updateSKUList)."')";
					$updateEffect = Yii::$app->subdb->createCommand($updateSql)->execute();
					echo "\n puid=$uid  update effect=$updateEffect  sql=".$updateSql;
				}
			}else{
				echo "\n puid=$uid no new  product !";
			}
		} catch (\Exception $e) {
			echo " \n check product info exception E002 line no:".$e->getLine()." Message :".$e->getMessage();
		}
		
		
		// 性能检测 start
		$OPType = "OrderCheck";
		$PerformanceLogMsg = $OPType. " check product info ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		/*****************************************    商品检测逻辑   end  *****************************************************/
		
		/*****************************************    匹配运输服务 检测逻辑   start  *****************************************************/
		$canShipOrder = [];//匹配成功 ， 而且不是待合并的订单设置为可发货
		if (!empty($canMergeOrderList)){
			$MergeCheckOrderList = array_flip($canMergeOrderList); //把order id 放在key 位， 可以加快对比速度
		}else{
			$MergeCheckOrderList = [];
		}
		
		try {
			list ($updateAllMapping ,$updateAllAttr  , $ServiceIdList) = self::batchMatchShippingService($uid, $orderIdList, $fullName);
			
			$serviceNameList =\eagle\modules\carrier\helpers\CarrierOpenHelper::getServiceName($ServiceIdList);
			
			//批量更新物流方式和仓库
			if (!empty($updateAllMapping)){
				foreach($updateAllMapping as $roleId =>$orderIds){
					if (!empty($orderIds) && !empty($updateAllAttr[$roleId])){
						$currentOrderIdNoModify = []; // 用于检查订单是否有手动修改的记录
						foreach($orderIds as $orderid){
							if (empty($CACHE[$uid]['order'][(int)$orderid]['default_carrier_code'])  && empty($CACHE[$uid]['order'][(int)$orderid]['default_shipping_method_code'])){
								$currentOrderIdNoModify[] = $orderid;
							}else{
								echo "\n uid=$uid order_id=".$orderid." already match default_carrier_code=".$CACHE[$uid]['order'][(int)$orderid]['default_carrier_code']." default_shipping_method_code=".$CACHE[$uid]['order'][(int)$orderid]['default_shipping_method_code']." warehouse id=".$CACHE[$uid]['order'][(int)$orderid]['default_warehouse_id'];
							}
						}
						//没有 指定的过运输方式各和仓库的使用匹配规则
						if (!empty($currentOrderIdNoModify)){
							$rt = OdOrder::updateAll($updateAllAttr[$roleId] , ['order_id'=>$currentOrderIdNoModify]);
							echo "\n puid=$uid role match ".$rt;
								
							//获取物流方式的名称
							if (!empty($serviceNameList[$updateAllAttr[$roleId]['default_shipping_method_code']])){
								$serviceName = $serviceNameList[$updateAllAttr[$roleId]['default_shipping_method_code']];
							}else{
								$serviceName = '';
							}
							
							//逐张订单写上日志
							foreach($currentOrderIdNoModify as $orderid){
									
								//检查是否合并的过的订单， 不是可合并， 而且匹配上物流的话， 放入可发货的数组
								if (!array_key_exists((int)$orderid,$MergeCheckOrderList)){
									$canShipOrder[] = $orderid;
								}
								OperationLogHelper::log('order',$orderid,'检测订单','匹配运输服务:['.$updateAllAttr[$roleId]['default_shipping_method_code'].'-'.$serviceName.']',$fullName);
							}//end of each array  $orderIds
						}//end of if $currentOrderIdNoModify empty or not 
						
					}//end of variant empty check
				}//end of each array $updateAllMapping
			}//end of if $updateAllMapping empty check 
		} catch (\Exception $e) {
			echo " \n order rule match  exception E003 line no:".$e->getLine()." Message :".$e->getMessage();
		}
		
		
		
		// 性能检测 start
		$OPType = "OrderCheck";
		$PerformanceLogMsg = $OPType. " order rule match ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		
		// 性能检测 end
		/*****************************************    匹配运输服务 检测逻辑   end  *****************************************************/
			
		/*****************************************    设置 订单可发货逻辑   start  *****************************************************/
		try {
			if (!empty($canShipOrder)){
					
				$changeOrderList = [];
				foreach($canShipOrder as $orderid){
					//发现了变化才update和写log
					if (($CACHE[$uid]['order'][(int)$orderid]['exception_status'] != OdOrder::EXCEP_WAITSEND) ||  ($CACHE[$uid]['order'][(int)$orderid]['pay_order_type'] != OdOrder::PAY_CAN_SHIP) ){
						$changeOrderList[] =  $orderid;
					}
				}
				//update 订单相关的信息和写log
				self::setOrderCanShip($changeOrderList,$fullName);
			}
		} catch (\Exception $e) {
			echo " \n set order can ship  exception E004 line no:".$e->getLine()." Message :".$e->getMessage();
		}
		
		
		// 性能检测 start
		$OPType = "OrderCheck";
		$PerformanceLogMsg = $OPType. "set order can ship ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		/*****************************************    设置 订单可发货逻辑   end  *****************************************************/

		/*****************************************    更新 待发货数量逻辑   start  *****************************************************/
		//更新 待发货数量
		/* doProductImport 上有加入 ， 此处不需要添加
		try {
			$currentItemQty = [];
			//目前订单都为默认仓库
			
			foreach($CACHE[$uid]['orderItem'] as $tmpItemList){
				foreach($tmpItemList as $tmpItem){
					//获取 root sku
					list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($tmpItem));
					if (empty($ack)) {
						echo "\n uid=$uid orderid=".$tmpItem['order_id']." order_item_id".$tmpItem['order_item_id']." update unship qty Error:$message , Error Code:$code , RootSKU:$rootSKU";
					}
						
					if (isset($currentItemQty[$rootSKU]) == false) $currentItemQty[$rootSKU] = 0;
					$currentItemQty[$rootSKU] += $tmpItem['quantity'];
				}
				
			}
			
			echo "\n";
			foreach($currentItemQty as $sku =>$qty){
				echo " $sku ($qty),";
				//新建的订单统一为默认仓库（0）
				\eagle\modules\inventory\apihelpers\InventoryApiHelper::updateQtyOrdered('0', $sku, $qty);
			}
			
		} catch (\Exception $ex) {
			echo "\n".(__function__).'-updateQtyOrdered Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
		}
		
		
		// 性能检测 start
		$OPType = "OrderCheck";
		$PerformanceLogMsg = $OPType. "set order unship qty ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		*/
		// 性能检测 end
		/*****************************************    更新 待发货数量逻辑   end  *****************************************************/
		
		
		
		unset($CACHE[$uid]); //释放全局变量
		$now = time();
		//设置队列数据为完成
		$rt = QueueOrderAutoCheck::updateAll(['status'=>'C' , 'update_time'=>$now , 'run_time'=>$now-$start_time],['order_id'=>$orderIdList]);
		
		return ['success'=>true , 'message'=>" puid=$uid order status check finish ($rt)"];
		
	}//end of handleOrderAutoCheckQueue
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置指定的订单为可发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $canShipOrder						小老板订单号数组
	 * 			  $fullName							操作者
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					OrderBackgroundHelper::setOrderCanShip(['0001']，'Tom');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderCanShip($canShipOrder , $fullName){
		$rt = OdOrder::updateAll(['exception_status'=>OdOrder::EXCEP_WAITSEND,'pay_order_type'=>OdOrder::PAY_CAN_SHIP] , ['order_id'=>$canShipOrder]);
		//echo "\n ship tag ".$rt;
		
		//log 日志 ， 调试相关信息start
		$GlobalCacheLogMsg = "\n  ".(__function__).' set order ship '.$rt;
		\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
		//log 日志 ， 调试相关信息end
		
		foreach($canShipOrder as $orderid){
			//发现了变化才写log
			OperationLogHelper::log('order',$orderid,'检测订单','修改异常标签:['.OdOrder::$exceptionstatus[OdOrder::EXCEP_WAITSEND].']',$fullName);
		}
	}//end of setOrderCanShip
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 生成 订单检测的 数据缓存
	 * 1.获取指定订单 的数据缓存
	 * 2.获取指定订单item 的数据缓存
	 * 3.获取指定订单item 相关的别名数据缓存
	 * 4.获取指定订单相关的商品数据缓存
	 * 5.获取指定订单相关的商品捆绑关系数据缓存
	 * 6.获取指定订单相关的商品变参关系数据缓存
	 * 7.获取指定订单相关的目录数据缓存
	 * 8.获取相关的sku解释数据缓存
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid						uid
	 * 			  $orderIdList				小老板订单号数组
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					OrderBackgroundHelper::setOrderCheckGlobalCache(1，['0001']);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/7/1				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderCheckGlobalCache($uid , $orderIdList){
		global $CACHE;
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." start mark performance ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		//获取已付款订单 的地址， 储存到全局变量， 方便对比
		$CACHE[$uid]['order_address'] = []; //重置全局变量
		$CACHE[$uid]['wait_merge'] = [];
		$CACHE[$uid]['platform'] = [];
		
		/* 取消不合并订单 操作， 没有必要查询 od_order_systags_mapping 这样影响性能
		$order_address_sql = "SELECT a.order_id,a.selleruserid ,a.source_buyer_user_id,a.consignee,a.consignee_address_line1,a.consignee_address_line2,a.consignee_address_line3,a.order_source,a.currency,a.order_type,a.exception_status , a.order_relation, b.tag_code
FROM od_order_v2 a
LEFT JOIN od_order_systags_mapping b ON a.order_id = b.order_id
WHERE a.order_status ='".OdOrder::STATUS_PAY."'";
		*/
		$order_address_sql = "SELECT a.order_id,a.selleruserid ,a.source_buyer_user_id,a.consignee,a.consignee_address_line1,a.consignee_address_line2,a.consignee_address_line3,a.order_source,a.currency,a.order_type,a.exception_status , a.order_relation, '' as tag_code
FROM od_order_v2 a
WHERE a.order_status ='".OdOrder::STATUS_PAY."' and a.order_source not in ('lazada' ,'linio' , 'jumia')";
		
		$AllOrderAddressInfoList = \yii::$app->subdb->createCommand($order_address_sql)->query();
		
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." order_address ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		$currentPayOrderIdList = [];
		$noMergeOrderIdList = []; //不合并订单集
		foreach($AllOrderAddressInfoList as $row){
			//'lazada' ,'linio' , 'jumia'  不列入合并检查     sql 控制
			/*
			if (in_array(strtoupper($row['order_source']),['lazada' ,'linio' , 'jumia'])){
				continue;
			}
			*/
			//amazon 的fba ， cdiscount的fbc 不列入合并检查
			if (in_array(strtoupper($row['order_type']),['AFN' ,'FBC'])){
				continue;
			}
			
			//合并过的原始订单 不列入待合并的范围
			if (in_array(strtolower($row['order_relation']) , ['fm','ss','fs'])){
				continue;
			}
			
			//找出不合并订单，并保存起来
			if ($row['tag_code'] == 'skip_merge'){
				$noMergeOrderIdList[(int)$row['order_id']] = $row['order_id'];
			}
			
			//去除重复的订单
			if (! isset($currentPayOrderIdList[(int)$row['order_id']])){
				//不存在的情况 下才加入
				$currentPayOrderIdList[(int)$row['order_id']] = $row;
			}
			
			//找出当前需要合并的订单
			if ($row['exception_status'] != OdOrder::EXCEP_WAITMERGE){
				$CACHE[$uid]['wait_merge'][(int)$row['order_id']] = $row['order_id'];
			}
			
			$CACHE[$uid]['platform'][(int)$row['order_id']] = $row['order_source'];
		}
		echo "\n $uid  current Pay Order Id List :".count($currentPayOrderIdList);
		/*
		if (!empty($CACHE[$uid]['wait_merge']))
			echo "\n $uid  wait merge Order Id List :".json_encode($CACHE[$uid]['wait_merge']);
			*/
		foreach($currentPayOrderIdList as $row){
			if (isset($noMergeOrderIdList[(int)$row['order_id']])){
				echo "\n $uid no merge order :".$row['order_id'];
				//该订单为不合并订单，则跳过
				continue;
			}
			
			//保存md5格式 下的
			$thisAddress = $row['selleruserid'].$row['source_buyer_user_id'].$row['consignee'].$row['consignee_address_line1'].$row['consignee_address_line2'].$row['consignee_address_line3'].$row['order_source'].$row['currency'];
			$md5Address = md5($thisAddress);
			//若没有定义 该变量 没有定义， 必须定义 ，防止报错
			if (! isset($CACHE[$uid]['order_address'][$md5Address])) $CACHE[$uid]['order_address'][$md5Address]=[];
			$CACHE[$uid]['order_address'][$md5Address][] = $row['order_id'];
			
		}
		
		/* 性能 差， 弃用 2016-07-13 start 
		$currentPayOrderIdList = [];
		foreach(OdOrder::find()->where(['order_status'=>OdOrder::STATUS_PAY])->asArray()->each(1) as $row){
			$currentPayOrderIdList[] = $row['order_id']; 
			//保存md5格式 下的
			$thisAddress = $row['selleruserid'].$row['source_buyer_user_id'].$row['consignee'].$row['consignee_address_line1'].$row['consignee_address_line2'].$row['consignee_address_line3'].$row['order_source'].$row['currency'];
			$md5Address = md5($thisAddress);
			//若没有定义 该变量 没有定义， 必须定义 ，防止报错
			if (! isset($CACHE[$uid]['order_address'][$md5Address])) $CACHE[$uid]['order_address'][$md5Address]=[];
			$CACHE[$uid]['order_address'][$md5Address][] = $row['order_id'];
		
		}
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." order_address ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		//检查 订单
		$noMergeOrderIdList = []; //不合并订单集
		if (!empty($currentPayOrderIdList)){
			//OrderSystagsMapping::find()->where(['order_id'=>$currentPayOrderIdList , 'tag_code'=>'skip_merge'])->asArray()->each(1)
			
			$SysTagObj = OrderSystagsMapping::find()->where(['order_id'=>$currentPayOrderIdList , 'tag_code'=>'skip_merge'])->select(['order_id']);
			
			$SystagsMappingList = $SysTagObj ->asArray()->all();
			// 性能检测 start
			$OPType = "OrderCheckGlobalCache";
			$PerformanceLogMsg = $OPType." get order system tag  data  and \n sql :";
			self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
			// 性能检测 end
			
			foreach($SystagsMappingList as $row){
				// 性能检测 start
				$OPType = "OrderCheckGlobalCache_SystemOrderTag";
				$PerformanceLogMsg = $OPType." system order tag str_pad 1 ";
				self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
				// 性能检测 end
				//echo "\n no merge order id:". $row['order_id'];
				if (!empty($row['order_id'])){
					$noMergeOrderIdList[] = str_pad($row['order_id'],11,"0",STR_PAD_LEFT);
				}
				
				// 性能检测 start
				$OPType = "OrderCheckGlobalCache_SystemOrderTag";
				$PerformanceLogMsg = $OPType." system order tag str_pad 2 ";
				self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
				// 性能检测 end
					
			}
		}
		
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." order system tag ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		//var_dump($CACHE[$uid]['order_address']);
		//var_dump($currentPayOrderIdList);
		//当前批次中， 存在不合并的订单， 需要把不合并的订单挑出来
		if (!empty($noMergeOrderIdList)){
			
			foreach($CACHE[$uid]['order_address'] as &$row ){
				$beforeData = $row;
				//echo "\n no merge before ".json_encode($row);
				$row = array_diff($row , $noMergeOrderIdList);
				if (count($row) != count($beforeData)){
					echo "\n puid filter no merge order before".json_encode($beforeData);
					echo "\n puid filter no merge order after".json_encode($row);
				}
				//echo "\n no merge  after ".json_encode($row);
			}
		}
		
		性能 差， 弃用 2016-07-13 end */
		//echo "\n order_address(".count($CACHE[$uid]['order_address'])."):".json_encode($CACHE[$uid]['order_address']);
		
		//设置订单全局变量
		$CACHE[$uid]['order'] = []; //重置全局变量
		//设置订单商品全局变量
		$CACHE[$uid]['orderItem'] = []; //重置全局变量
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." no merge order mapping ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		self::setOrderInfoGlobalCache($uid, $orderIdList);
		
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." order and item  ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		$skuList = []; //订单中出现过的sku ， 是后续product cache 和 alias cache 的依据
		
		foreach($CACHE[$uid]['orderItem'] as $orderId=>$itemList){
			foreach($itemList as $row){
				//订单中空的sku 则取商品标题作为 sku
				$sku = empty($row['sku'])?$row['product_name']:$row['sku'];
				//新出现 的sku 记录在变量$skuList 中
				if (array_key_exists($sku, $skuList) ==false){
					$skuList[$sku] = $sku;
				}
			}
		}
		
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." sku mapping  ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		//设置 别名的全局变量
		$CACHE[$uid]['alias'] = []; //重置全局变量
		foreach(ProductAliases::find()->where(['alias_sku'=>$skuList])->asArray()->each(1) as $row){
			$CACHE[$uid]['alias'][$row['alias_sku']] = $row;
			
			//订单中出现的是别名， 就将skuList的对应sku 改为root sku
			$skuList[$row['alias_sku']] = $row['sku'];
		}
		
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." get alias  ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		//$skuList 是经过 alias 处理过的， 假如再查商品库还没有的话， 表示该商品为新产品 
		$CACHE[$uid]['product'] = []; //重置全局变量
		$bundleSKUList = [];
		$configSKUList = [];
		$childrenSKUList = [];
		foreach(Product::find()->where(['sku'=>$skuList])->asArray()->each(1) as $row){
			$CACHE[$uid]['product'][$row['sku']] = $row;
			//设置  捆绑商品  的数据集
			switch (strtoupper($row['type'])){
				case "B" :
					if ( array_key_exists($row['sku'],$bundleSKUList) == false){
						$bundleSKUList[$row['sku']] = $row['sku'];
					}
					break;
				case "C" :
					if ( array_key_exists($row['sku'],$configSKUList) == false){
						$configSKUList[$row['sku']] = $row['sku'];
					}
					break;
			}
		}
		
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." recheck alias product ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		//设置  捆绑商品的  数据缓存
		$CACHE[$uid]['bundleRelation'] = []; //重置全局变量
		
		if (!empty($bundleSKUList)){
			// 捆绑商品 
			foreach(ProductBundleRelationship::find()->where(['bdsku'=>$bundleSKUList])->asArray()->each(1) as $row){
				//初始化 
				if (isset($CACHE[$uid]['bundleRelation'][$row['bdsku']]) == false) 
					$CACHE[$uid]['bundleRelation'][$row['bdsku']] = [];
				$CACHE[$uid]['bundleRelation'][$row['bdsku']][] = $row;
				
				
				//没有缓存， 需要再获取一次商品信息
				if (isset($CACHE[$uid]['product'][$row['assku']]) == false){
					if ( array_key_exists($row['assku'],$childrenSKUList) == false){
						$childrenSKUList[$row['assku']] = $row['assku'];
					}
				}
			}
		}
		
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." bundle product ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		//设置 变参商品  的全局变量
		$CACHE[$uid]['configRelation'] = []; //重置全局变量
		if (!empty($configSKUList)){
			foreach(ProductConfigRelationship::find()->where(['cfsku'=>$configSKUList])->asArray()->each(1) as $row){
				//初始化
				if (isset($CACHE[$uid]['configRelation'][$row['cfsku']]) == false)
					$CACHE[$uid]['configRelation'][$row['cfsku']] = [];
				
				$CACHE[$uid]['configRelation'][$row['cfsku']][] = $row;
				
				//没有缓存， 需要再获取一次商品信息
				if (isset($CACHE[$uid]['product'][$row['assku']]) == false){
					if ( array_key_exists($row['assku'],$childrenSKUList) == false){
						$childrenSKUList[$row['assku']] = $row['assku'];
					}
				}
			}
		}
		
		//子商品再获取一次商品库
		if (!empty($childrenSKUList)){
			foreach(Product::find()->where(['sku'=>$childrenSKUList])->asArray()->each(1) as $row){
				$CACHE[$uid]['product'][$row['sku']] = $row;
			}
		}
		
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." config and child product ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		//获取解析规则
		$CACHE[$uid]['skurule'] = ConfigHelper::getConfig("skurule",'NO_CACHE');
		echo "\n puid=$uid skurule (no cache):".print_r($CACHE[$uid]['skurule'],true);
		//是否开启sku 解释
		$CACHE[$uid]['analysis_rule_active'] = ConfigHelper::getConfig('configuration/productconfig/analysis_rule_active','NO_CACHE');
		echo "\n puid=$uid analysis_rule_active (no cache):".print_r($CACHE[$uid]['analysis_rule_active'],true);
		
		//自动 生成sku
		$CACHE[$uid]['sku_toproduct'] = ConfigHelper::getConfig('order/sku_toproduct','NO_CACHE');
		echo "\n puid=$uid sku_toproduct (no cache):".print_r($CACHE[$uid]['sku_toproduct'],true);
		
		//获取订单商品的类目中英文做报关名使用 (只有在新建商品时候才会用到， 假如不用新建商品， 缓存这个只会浪费资源)
		$aliItemIdList = [];
		$ebayItemIdList = [];
		foreach($CACHE[$uid]['orderItem'] as $orderId=>$itemList){
			foreach($itemList as $row){
				//目前只有ebay 和 aliexpress 的检测 start
				if (in_array($CACHE[$uid]['order'][(int)$row['order_id']]['order_source'] , ['ebay','aliexpress'])){
					//检查 该商品是否新产品 ， 新商品才需要读取其相关的目录信息
					$sku = empty($row['sku'])?$row['product_name']:$row['sku'];
				
					//新商品检测 start
					if (!array_key_exists($sku , $CACHE[$uid]['product'])){
						switch ($CACHE[$uid]['order'][(int)$row['order_id']]['order_source']){
							case 'aliexpress':
								$aliItemIdList[] = $row['order_source_itemid'];
								break;
							case 'ebay':
								$ebayItemIdList[] = $row['order_source_itemid'];
								break;
						}//end of switch
					}//新商品检测 end
				}//目前只有ebay 和 aliexpress 的检测 end
			}//end of foreach item detail
			
		}//end of foreach 
		
		//缓存 速卖通订单中的新商品 目录相关信息 start 
		$aliCategoryIdList = [];
		if (!empty($aliItemIdList)){
			//新建商品时， 只用到categoryid,productid ，所以暂时只获取categoryid ,productid， 如日后需要再增加其他字段
			foreach(\eagle\models\AliexpressListingDetail::find()->select(['categoryid','productid'])->where(['productid'=>$aliItemIdList])->asArray()->each(1) as $row){
				$CACHE[$uid]['listing']['aliexpress'][$row['productid']] = $row;
				$aliCategoryIdList[$row['categoryid']] = $row['categoryid'];
			}//end of foreach 
			
			//获取对应的目录信息
			foreach(\eagle\models\AliexpressCategory::find()->where(['cateid'=>$aliCategoryIdList])->asArray()->each(1) as $row){
				$CACHE[$uid]['Category']['aliexpress'][$row['cateid']] = $row;
			}
			
		}
		//缓存 速卖通订单中的新商品 目录相关信息  end 
		
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." aliexpress category info ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		//缓存ebay订单中的新商品 目录相关信息 start
		$ebayCategoryIdList = [];
		if (!empty($ebayItemIdList)){
			//新建商品时， 只用到primarycategory,itemid ，所以暂时只获取primarycategory ,itemid， 如日后需要再增加其他字段
			foreach(\eagle\modules\listing\models\EbayItemDetail::find()->select(['primarycategory','itemid'])->where(['itemid'=>$ebayItemIdList])->asArray()->each(1) as $row){
				$CACHE[$uid]['listing']['ebay'][$row['itemid']] = $row;
				$ebayCategoryIdList[$row['primarycategory']] = $row['primarycategory'];
			}//enf of foreach
			
			//获取对应的目录信息
			foreach(\eagle\models\EbayCategory::find()->where(['categoryid'=>$ebayCategoryIdList])->asArray()->each(1) as $row){
				$CACHE[$uid]['Category']['ebay'][$row['categoryid']] = $row;
			}//end of foreach
		}
		//缓存 ebay订单中的新商品 目录相关信息 end
		// 性能检测 start
		$OPType = "OrderCheckGlobalCache";
		$PerformanceLogMsg = $OPType." ebay category info ";
		self::setPerformanceRecord($uid, $OPType, $PerformanceLogMsg);
		// 性能检测 end
		
		
		//获取不存在的sku 列表
		if ($CACHE[$uid]['sku_toproduct']){
			
			foreach($skuList as $sku){
				//定义新sku 假如开启自动生成商品的情况 下需要刷新商品信息
				if (empty($CACHE[$uid]['newSKU'])) $CACHE[$uid]['newSKU'] = [];
				
				//新商品
				if (isset($CACHE[$uid]['product'][$sku]) ==false) $CACHE[$uid]['newSKU'][$sku] = $sku;
				
			}
		}
	}//end of setOrderCheckGlobalCache
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置订单相关的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid						uid
	 * 			  $orderIdList				小老板订单号数组
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					OrderBackgroundHelper::setOrderInfoGlobalCache(1，['0001']);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderInfoGlobalCache($uid , $orderIdList){
		global $CACHE;
		//设置订单全局变量
		$CACHE[$uid]['order'] = []; //重置全局变量
		
		foreach(OdOrder::find()->where(['order_id'=>$orderIdList])->asArray()->each(1) as $row){
			$CACHE[$uid]['order'][(int)$row['order_id']] = $row;
		}
		
		//设置订单商品全局变量
		$CACHE[$uid]['orderItem'] = []; //重置全局变量
		$skuList = []; //订单中出现过的sku ， 是后续product cache 和 alias cache 的依据
		foreach(OdOrderItem::find()->where(['order_id'=>$orderIdList])->asArray()->each(1) as $row){
			$CACHE[$uid]['orderItem'][(int)$row['order_id']][] = $row;
		}
		
	}//end of setOrderInfoGlobalCache
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单检测写log的封装， 方式 调用和代码上的阅读
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $GlobalCacheLogMsg					日志内容
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					OrderBackgroundHelper::OrderCheckGlobalCacheLog('log msg');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function OrderCheckGlobalCacheLog($GlobalCacheLogMsg){
		//log 日志 ， 调试相关信息start
		//$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' listing '.(($type == 'Y')?'has':'no').' cache';
		if (self::$OrderCheckLog){
			\Yii::info($GlobalCacheLogMsg,"file");
		}
		if (isset(\Yii::$app->params["currentEnv"]) && \Yii::$app->params["currentEnv"] == 'test'){
			//echo '<br>'.$GlobalCacheLogMsg;//test kh
		}
		//log 日志 ， 调试相关信息end
	}//end of OrderCheckGlobalLog
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单批量匹配物流规则
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid					日志内容
	 * @param	  $orderIdList			小老板订单号
	 * @param	  $FullName				日志操作者
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									roleMatch			记录每个规则 匹配中的订单
	 * 									roleAttr			记录规则 的相关信息
	 * 									serviceIdList		记录当前批次出现过的物流方式id
	 *
	 * @invoking					OrderBackgroundHelper::batchMatchShippingService('1',['1'],'user1');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function batchMatchShippingService($uid , $orderIdList , $FullName){
		global $CACHE;
		
		//检测是否有全局的订单缓存，没有的话就再获取一次订单缓存
		if (isset($CACHE[$uid]['order']) ==false){
			self::setOrderInfoGlobalCache($uid, $orderIdList);
		}
		
		$open_carriers = \eagle\modules\carrier\helpers\CarrierOpenHelper::getOpenCarrierArr(2, 1);
			
		$open_carriers = array_keys($open_carriers);
			
		$conn=\Yii::$app->subdb;
			
		$queryTmp = new Query();
		$queryTmp->select("a.*,"."`c`.`is_del` as `as_is_del`,`b`.`carrier_code`")
		->from("matching_rule a")
		->leftJoin("sys_shipping_service b", "b.id = a.transportation_service_id")
		->leftJoin("sys_carrier_account c", "c.id = b.carrier_account_id")
		->leftJoin("sys_carrier_custom d", "d.carrier_code = b.carrier_code");
			
		$queryTmp->andWhere('a.created > 0');
		$queryTmp->andWhere(['b.is_used'=>1]);
		$queryTmp->andWhere(['b.is_del'=>0]);
		$queryTmp->andWhere(['a.is_active'=>1]);
		$queryTmp->andWhere(['IFNULL(`c`.`is_used`,`d`.`is_used`)'=>1]);
		$queryTmp->andwhere(['in','b.carrier_code',$open_carriers]);
			
		$sort_arr = array('is_active'=>'is_active desc','priority'=>'priority asc','transportation_service_id'=>'transportation_service_id asc','rule_name'=>'rule_name asc');
		$str = implode(',', $sort_arr);
			
		$queryTmp->orderBy($str);
			
		$matching_ruleArr = $queryTmp->createCommand($conn)->queryAll();
			
		if (count($matching_ruleArr) == 0){
			return false;//没有启用任何规则
		}
		
		$updateAllMapping = []; 	//记录每个规则 匹配中的订单
		$updateAllAttr = [];		//记录规则 的相关信息
		$ServiceIdList = [];		//记录当前批次出现过的物流方式id
		
		foreach($orderIdList as $orderid){
			//格式 化 缓存 为对象， 重用 match 
			$order = (Object) $CACHE[$uid]['order'][(int)$orderid];
			
			//如果是速卖通订单，并且客选物流为空，则跳过，lrq20180411
			if($order->order_source == 'aliexpress'){
				if(empty($order->addi_info)){
					continue;
				}
				$addi_info = json_decode($order->addi_info, true);
				if(empty($addi_info) || empty($addi_info['shipping_service'])){
					continue;
				}
			}
			
			$order->items = (Object)  $CACHE[$uid]['orderItem'][(int)$orderid];
			foreach($order->items as &$item){
				//新商品自动生成与配对补全关系
				if (isset($CACHE[$uid]['newSKU'])){
					if (in_array($item['sku'] ,$CACHE[$uid]['newSKU'] )){
						$item['root_sku'] = $item['sku'];
					}
				}
				
				$item = (Object)$item;
			}
			
			
			foreach ($matching_ruleArr as $matching_ruleKey => $matching_ruleVal){
				if($matching_ruleVal['as_is_del'] == 1){
					continue;
				}
					
				$matching_ruleVal['rules'] = unserialize($matching_ruleVal['rules']);
				$matching_ruleVal['source'] = unserialize($matching_ruleVal['source']);
				$matching_ruleVal['site'] = unserialize($matching_ruleVal['site']);
				$matching_ruleVal['selleruserid'] = unserialize($matching_ruleVal['selleruserid']);
				$matching_ruleVal['buyer_transportation_service'] = unserialize($matching_ruleVal['buyer_transportation_service']);
				$matching_ruleVal['receiving_country'] = unserialize($matching_ruleVal['receiving_country']);
				$matching_ruleVal['total_amount'] = unserialize($matching_ruleVal['total_amount']);
				$matching_ruleVal['freight_amount'] = unserialize($matching_ruleVal['freight_amount']);
				$matching_ruleVal['total_weight'] = unserialize($matching_ruleVal['total_weight']);
				$matching_ruleVal['product_tag'] = unserialize($matching_ruleVal['product_tag']);
				$matching_ruleVal['items_location_country'] = unserialize($matching_ruleVal['items_location_country']);
				
				//物流匹配规则 匹配结果很保存起来， 后面批量更新相关的订单
				if (\eagle\modules\carrier\apihelpers\CarrierApiHelper::matching($order,$matching_ruleVal, $FullName)){
					$updateAllMapping[$matching_ruleVal['id']][] = $orderid;
					$updateAllAttr[$matching_ruleVal['id']] = [
						'default_carrier_code'=>$matching_ruleVal['carrier_code'] , 
						'default_shipping_method_code'=> $matching_ruleVal['transportation_service_id'] ,
						'default_warehouse_id'=>$matching_ruleVal['proprietary_warehouse_id'],
						'rule_id'=>$matching_ruleVal['id'],
					];
					
					if (! array_key_exists($matching_ruleVal['transportation_service_id'],$ServiceIdList)){
						$ServiceIdList[$matching_ruleVal['transportation_service_id']] = $matching_ruleVal['transportation_service_id'];
					}
					break; //  匹配成功后退出循环
				}
			}//end of array roles
			
			
		}//end of each array order
		
		//return ['roleMatch'=>$updateAllMapping , 'roleAttr'=>$updateAllAttr , 'serviceIdList'=> $ServiceIdList];
		return [$updateAllMapping , $updateAllAttr ,  $ServiceIdList];
	}//end of function batchMatchShippingService
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 记录 性能 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid					uid
	 * @param	  $type					性能内容
	 * @param	  $LogMsg				日志内容
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									roleMatch			记录每个规则 匹配中的订单
	 * 									roleAttr			记录规则 的相关信息
	 * 									serviceIdList		记录当前批次出现过的物流方式id
	 *
	 * @invoking					OrderBackgroundHelper::batchMatchShippingService('1',['1'],'user1');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setPerformanceRecord ($uid ,$type, $LogMsg){
		global $CACHE;
		if (empty($CACHE[$uid][$type])){
			$CACHE[$uid][$type]['logTimeMS']=TimeUtil::getCurrentTimestampMS();
			$CACHE[$uid][$type]['logMemoryMS'] = (memory_get_usage()/1024/1024);
			$CACHE[$uid][$type]['step'] = 1;
			echo "\n step 1 puid=$uid ".$LogMsg.' current  memory:'.$CACHE[$uid][$type]['logMemoryMS']. 'M '; 
		}else{
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS2 = (memory_get_usage()/1024/1024);
			$CACHE[$uid][$type]['step']++;
			echo "\n step ".$CACHE[$uid][$type]['step']." puid=$uid ".$LogMsg.' T=('.($logTimeMS2-$CACHE[$uid][$type]['logTimeMS']).') and M='.($logMemoryMS2-$CACHE[$uid][$type]['logMemoryMS']).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M'; //test kh
			$CACHE[$uid][$type]['logTimeMS']=$logTimeMS2;
			$CACHE[$uid][$type]['logMemoryMS'] = $logMemoryMS2;
		}
	}//end of function setPerformanceRecord
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	定时删除已完成的检查的订单数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $interval					保留多少 天的队列数据
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									roleMatch			记录每个规则 匹配中的订单
	 * 									roleAttr			记录规则 的相关信息
	 * 									serviceIdList		记录当前批次出现过的物流方式id
	 *
	 * @invoking					OrderBackgroundHelper::deleteOrderCheckQueue();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function deleteOrderCheckQueue($interval = 7){
		$time = strtotime("-$interval day");
		return QueueOrderAutoCheck::deleteAll(" status='C' and create_time<='$time'");
	}//end of function deleteOrderCheckQueue
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据虚拟发货结果， 1.自动 设置订单标记发货状态 ；2. 更新更新dashboard 数据  3.合并订单处理
	 * 
	 * 合并订单 虚拟发货结果处理原则 ：
	 * 	优先级：未提交 > 提交失败 > 提交中 > 提交成功
	 * 	有一张订单未提交，则合并订单为未提交
	 * 	再检测有一张订单失败， 合并的订单也是失败
	 * 	再检测有一张订单提交中， 合并的订单也是提交中
	 * 	所有订单是成功，才能算是成功
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderId						string 	小老板订单号 
	 * @param	  $syncShippedStatus			string  标记发货是否成功'P'为待提交,'S'为提交中,'C'为提交成功（小老板）,'Y'为提交成功（非小老板） ,'F'为提交失败 , 
	 * @param	  $delivery_time				int  	发货时间-1为默认值 ， 会使用服务器时间 ， 时间戳表示 使用该参数时间（暂时主要 用于amazon） 
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack				接口调用是否成功 true 为正常， false 为异常
	 * 									Message			失败，异常的原因 
	 * 									Code			成功2000，失败，异常的（40000+） 
	 * 									data			接口返回的数据
	 *
	 * @invoking					OrderApiHelper::setOrderSyncShippedStatus('1' ,'P',-1);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/9/03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderSyncShippedStatus($orderId , $syncShippedStatus,$delivery_time=-1){
		// 检查 虚拟发货状态是否有效
		$syncShippedStatus = strtoupper($syncShippedStatus);
		if (!in_array($syncShippedStatus , ['P','S','C','Y','F'])){
			return ['ack'=>false,'message'=>'参数虚拟发货状态无效','code'=>'4001','data'=>[]];
		}
	
		//check $orderId
		if ($orderId instanceof OdOrder){
			$order = $orderId;
		}else if (is_string($orderId) || is_int($orderId)){
			$order = OdOrder::findOne($orderId);
			//var_dump($order);
		}else{
			return ['ack'=>false,'message'=>'参数订单无效','code'=>'4002','data'=>[]];
		}
	
		//order
		if (empty($order)){
			return ['ack'=>false,'message'=>'订单无效','code'=>'4003','data'=>[]];
		}
	
		//
		try {
			$oldSyncShipStatus = $order->sync_shipped_status; // 旧的提交状态
			$order->sync_shipped_status = $syncShippedStatus;
			
			//只有小老板更新状态为C的时候, 为小老板发货时间， 才需要更新这个时间
			if ( in_array($syncShippedStatus , ['C'])){
				if ($delivery_time == -1 ){
					$order->delivery_time = time();
				}else{
					$order->delivery_time = $delivery_time;
				}
				
				$order->shipping_status=1; //ebay 前台显示用到
			}
			//ebay 前台显示用到
			if ($order->delivery_time >0){
				$order->shipping_status=1;
			}else{
				$order->shipping_status=0;
			}
	
	
			if ($order->save()){
				return self::setRelationOrderSyncShippedStatus($order , $syncShippedStatus , $oldSyncShipStatus);
				
			}else{
				return ['ack'=>false,'message'=>$order->getErrors(),'code'=>'4004','data'=>[]];
			}//end of failure to save order
		} catch (\Exception $e) {
			return ['ack'=>false,'message'=>$e->getMessage(),'code'=>'4005','data'=>[]];
		}//end of try catch
	
	}//end of function setOrderSyncShippedStatus
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据虚拟发货结果， 1.自动 设置订单标记发货状态 ；2. 更新更新dashboard 数据  3.合并订单处理
	 *
	 * 合并订单 虚拟发货结果处理原则 ：
	 * 	优先级：未提交 > 提交失败 > 提交中 > 提交成功
	 * 	有一张订单未提交，则合并订单为未提交
	 * 	再检测有一张订单失败， 合并的订单也是失败
	 * 	再检测有一张订单提交中， 合并的订单也是提交中
	 * 	所有订单是成功，才能算是成功
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderId						string 	小老板订单号
	 * @param	  $syncShippedStatus			string  标记发货是否成功'P'为待提交,'S'为提交中,'C'为提交成功（小老板）,'Y'为提交成功（非小老板） ,'F'为提交失败 ,
	 * @param	  $syncShippedStatus			string  标记发货的原始状态，因为更新订单哪边 会先保存这个状态'P'为待提交,'S'为提交中,'C'为提交成功（小老板）,'Y'为提交成功（非小老板） ,'F'为提交失败 ,
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack				接口调用是否成功 true 为正常， false 为异常
	 * 									Message			失败，异常的原因
	 * 									Code			成功2000，失败，异常的（40000+）
	 * 									data			接口返回的数据
	 *
	 * @invoking					OrderApiHelper::setRelationOrderSyncShippedStatus('1' ,'P');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/9/05				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setRelationOrderSyncShippedStatus($orderId ,$syncShippedStatus,$oldSyncShipStatus){
		
		if (!in_array($syncShippedStatus , ['P','S','C','Y','F'])){
			return ['ack'=>false,'message'=>'参数虚拟发货状态无效','code'=>'4001','data'=>[]];
		}
		
		//check $orderId
		if ($orderId instanceof OdOrder){
			$order = $orderId;
		}else if (is_string($orderId) || is_int($orderId)){
			$order = OdOrder::findOne($orderId);
			//var_dump($order);
		}else{
			return ['ack'=>false,'message'=>'参数订单无效','code'=>'4002','data'=>[]];
		}
		
		//order
		if (empty($order)){
			return ['ack'=>false,'message'=>'订单无效','code'=>'4003','data'=>[]];
		}
		
		
		//*******************************      dashboard 计数器  start    *******************************//
		// 由失败变成非失败， 则 计数器-1
		try {
			if ($oldSyncShipStatus == 'F'  && $syncShippedStatus != "F"){
				$puid = \Yii::$app->subdb->getCurrentPuid();
				if (isset(DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($order->order_source)})){
					DashBoardStatisticHelper::CounterAdd($puid, DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($order->order_source)},-1);
				}
			}
				
			// 由非失败变成失败， 则 计数器+1
			if ($oldSyncShipStatus != 'F'  && $syncShippedStatus == "F"){
				$puid = \Yii::$app->subdb->getCurrentPuid();
				if (isset(DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($order->order_source)})){
					DashBoardStatisticHelper::CounterAdd($puid, DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($order->order_source)},1);
				}
			}
		} catch (\Exception $e) {
			//防止 redis 连接失败导致虚拟发货队列报错 ， 崩溃
			return ['ack'=>false,'message'=>$e->getMessage(),'code'=>'4006','data'=>[]];
		}
		
		//*******************************      dashboard 计数器  end    *******************************//
		
		$msg = '保存成功';
		//echo $order->order_relation;
		//*******************************      合并订单 回写逻辑 start    *******************************//
		try {
			if ($order->order_relation=='fm'){
				/* 优先级未提交 > 提交失败 > 提交中 > 提交成功
				 * 有一张订单未提交，则合并订单为未提交
				* 再检测有一张订单失败， 合并的订单也是失败
				* 再检测有一张订单提交中， 合并的订单也是提交中
				* 所有订单是成功，才能算是成功
				*/
				$orderRelation = OrderRelation::findOne(['father_orderid'=>$order->order_id]);
				if ($syncShippedStatus == "P"){
					$effect = OdOrder::updateAll(['sync_shipped_status'=>$syncShippedStatus],['order_id'=>$orderRelation->son_orderid]);
				}else if ($syncShippedStatus == "F"){
					//找出所有的原始订单
					$ALLRelationList = OrderRelation::find()->where(['son_orderid'=>$orderRelation->son_orderid])->asArray()->all();
					$ALLRelationOrderIDList = [];
					foreach($ALLRelationList as $row){
						if ($order->order_id != $row['father_orderid'] ){
							$ALLRelationOrderIDList = $row['father_orderid'];
						}
					}
					//检查 原始订单 没有 待检查了，就可以把合并订单更新 为 提交失败
					$tmp_count = OdOrder::find()->where(['order_id'=>$ALLRelationOrderIDList , 'sync_shipped_status'=>'P'])->count();
						
					if (empty($tmp_count)){
						$effect = OdOrder::updateAll(['sync_shipped_status'=>$syncShippedStatus],['order_id'=>$orderRelation->son_orderid]);
					}
				}else if ($syncShippedStatus == "S"){
					//找出所有的原始订单
					$ALLRelationList = OrderRelation::find()->where(['son_orderid'=>$orderRelation->son_orderid])->asArray()->all();
					$ALLRelationOrderIDList = [];
					foreach($ALLRelationList as $row){
						if ($order->order_id != $row['father_orderid'] ){
							$ALLRelationOrderIDList = $row['father_orderid'];
						}
					}
					//检查 原始订单 没有 待检查,提交失败了，就可以把合并订单更新 为 提交中
					$tmp_count = OdOrder::find()->where(['order_id'=>$ALLRelationOrderIDList , 'sync_shipped_status'=>['P','F']])->count();
						
						
					if (empty($tmp_count)){
						$effect = OdOrder::updateAll(['sync_shipped_status'=>$syncShippedStatus],['order_id'=>$orderRelation->son_orderid]);
					}
				}else{
					//找出所有的原始订单
					$ALLRelationList = OrderRelation::find()->where(['son_orderid'=>$orderRelation->son_orderid])->asArray()->all();
					$ALLRelationOrderIDList = [];
					foreach($ALLRelationList as $row){
						if ($order->order_id != $row['father_orderid'] ){
							$ALLRelationOrderIDList = $row['father_orderid'];
						}
					}
					//检查 原始订单 没有 待检查,提交失败,提交中了，就可以把合并订单更新 为 提交中
					$tmp_count = OdOrder::find()->where(['order_id'=>$ALLRelationOrderIDList , 'sync_shipped_status'=>['P','F','S']])->count();
		
					if (empty($tmp_count)){
						$effect = OdOrder::updateAll(['sync_shipped_status'=>$syncShippedStatus],['order_id'=>$orderRelation->son_orderid]);
					}
				}
					
			}else{
				$msg = '保存成功';
			}//end of merged order check
		} catch (\Exception $e) {
			return ['ack'=>false,'message'=>$e->getMessage(),'code'=>'4007','data'=>[]];
		}
		
		//*******************************      合并订单 回写逻辑 end    *******************************//
		
		return ['ack'=>true,'message'=>$msg,'code'=>'2000','data'=>[]];
	}//end of function setRelationOrderSyncShippedStatus
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 方便测试  importPlatformOrder是否存在bug 
	 *
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderStr						string 	json 格式 的订单数据 （假如在test-api界面使用请使用html解析的，否则会报错）
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack				接口调用是否成功 true 为正常， false 为异常
	 * 									Message			失败，异常的原因
	 * 									Code			成功2000，失败，异常的（4000+）
	 * 									data			接口返回的数据
	 *
	 * @invoking					OrderBackgroundHelper::DebugImportPlatformOrder('{}');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/9/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function DebugImportPlatformOrder($orderStr){
		//just for test importPlatformOrder
		try{
			$uid = \Yii::$app->subdb->getCurrentPuid();
			
			//$tmp = '{"'.$uid .'":'.$orderStr.'}';
			
			$orderArr = json_decode($orderStr,true);
			$order = [$uid => $orderArr];
			
			echo json_encode($order);
			//exit();
			$rt = OrderHelper::importPlatformOrder($order);
			return ['ack'=>true,'message'=>'','code'=>'2000','data'=>$rt];
		}catch(\Exception $e){
			return ['ack'=>false,'message'=>$e->getMessage()." line no ".$e->getLine(),'code'=>'4001','data'=>[]]; 
		}
	}//end of function OrderBackgroundHelper
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 初始化 虚拟发货的数据
	 *
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $isForce						string 	json 格式 的订单数据 （假如在test-api界面使用请使用html解析的，否则会报错）
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack				接口调用是否成功 true 为正常， false 为异常
	 * 									Message			失败，异常的原因
	 * 									Code			成功2000，失败，异常的（4000+）
	 * 									data			接口返回的数据
	 *
	 * @invoking					OrderBackgroundHelper::initOrderSyncShipStatusCount();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/9/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function initOrderSyncShipStatusCount($isForce = false){
		try {
			$sql = [];
			//默认为不需要虚拟发货
			$sql['default_pay'] = "update od_order_v2  set sync_shipped_status = 'N' where order_status <= '".OdOrder::STATUS_PAY."' ";
			// ebay 没有 使用 order source status 所以 要独立出来 做判断
			$sql['ebay_setting'] = "update od_order_v2  set sync_shipped_status = 'P' where order_source ='ebay' and order_status = '".OdOrder::STATUS_PAY."' and delivery_time = 0 ";
			
			/* false 的情况 会读取redis 的绑定记录， 减少 update 的次数， 当true 的情况 则为全平台刷新*/
			if ($isForce){
				//强制刷新全平台
				$allPlatfromList = array_fill_keys(PlatformAccountApi::$platformList,'1');
			}else{
				//获取当前客户所有绑定 的平台
				$allPlatfromList = PlatformAccountApi::getPlatformInfo('all');
			}
			
			$Fcount = [];
			foreach($allPlatfromList as $platform=>$bindingCount){
				$platformLower = strtolower($platform);
				//有绑定账号才更新对应平台的订单
				if (!empty($bindingCount)){
					//rumall 不需要标记发货的
					if ($platformLower == 'rumall'){
						$sql['rumall_setting'] = "update od_order_v2  set sync_shipped_status = 'N' where order_source = 'rumall' ";
					}
			
					if (isset(OrderHelper::$waitSignShipStaus[$platformLower] )){
						$sql[$platformLower.'_setting'] = "update od_order_v2  set sync_shipped_status = 'P' where order_source = '$platform'  and order_source_status in ( '".implode("','",OrderHelper::$waitSignShipStaus[$platformLower])."') and order_status = '".OdOrder::STATUS_PAY."'   ";
					}
				}else{
					//没有 绑定 的情况 下设置  redis 数据为0
					$Fcount[$platform] = 0;
				}
			}
			
			//最后 一次同步 失败 的订单
			$sql['failure_setting'] = " update od_order_v2 o  , ( select MAX(id) , order_id , status from od_order_shipped_v2  GROUP by order_id ) s set sync_shipped_status = 'F'  where s.order_id = o.order_id and s.status = 2";
			//最后 一次同步 成功 的订单
			$sql['success_setting'] = " update od_order_v2 o  , ( select MAX(id) , order_id , status from od_order_shipped_v2  GROUP by order_id ) s set sync_shipped_status = 'C'  where s.order_id = o.order_id and s.status = 1";
			//最后 一次同步 还没有 完成   的订单
			$sql['submit_setting'] = " update od_order_v2 o  , ( select MAX(id) , order_id , status from od_order_shipped_v2  GROUP by order_id ) s set sync_shipped_status = 'S'  where s.order_id = o.order_id and s.status = 0";
			
			$rt = [];
			if (!empty($sql )){
				foreach ($sql as $key =>$query){
					//echo "<br> $query";
					$rt[$key] = \Yii::$app->subdb->createCommand($query)->execute();
				}
			}
			
			//统计各个平台
			$query = "select order_source , count(1) as fc from od_order_v2 where sync_shipped_status = 'F' and isshow='Y' and order_relation <>'fm' group by order_source";
			$Flist = \Yii::$app->subdb->createCommand($query)->queryAll();
			
			$FailureList = Helper_Array::toHashmap($Flist, 'order_source' , 'fc');
			//重置 redis 数据
			$puid = \Yii::$app->subdb->getCurrentPuid();
			foreach(PlatformAccountApi::$platformList as $pf){
				if (isset(DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($pf)})){
			
					if (isset($FailureList[$pf])){
						$tmpValue = $FailureList[$pf];
					}else{
						//没有 绑定 的情况 下设置  redis 数据为0
						$tmpValue = 0;
					}
					//echo "\n".'SIGNSHIPPED_ORDER_ERR_'.strtoupper($pf) ." (".$tmpValue.")";
					DashBoardStatisticHelper::CounterSet($puid, DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($pf)},$tmpValue);
				}
			}
			
			return $rt;
		} catch (\Exception $e) {
			echo "Exception Error Message:".$e->getMessage()." line no :".$e->getLine();
		}
	}//end of function initOrderSyncShipStatusCount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单设置 为提交成功
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 								$orderIdList						小老板订单号
	 * 								$module								模块
	 * 								$action								操作
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack								执行结果是还成功
	 * 									message							执行的相关信息
	 * 									code							执行的代码
	 * 									data							执行结果返回的数据
	 * @invoking					OrderApiHelper::setOrderSyncShipStatusComplete($orderIdList);
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/09/22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderSyncShipStatusComplete($orderIdList,$module , $action){
		$fullName = \Yii::$app->user->identity->getFullName();
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$errorMsg = '';
		$result = ['ack'=>true,'message'=>'' ,'code'=>'2000','data'=>[]];
		$edit_log = array();
		try {
			foreach(OdOrder::find()->where(['order_id'=>$orderIdList])->each(1) as $order){
				if (in_array($order->sync_shipped_status, ['S','P','F'])){
					$OriginStaus = $order->sync_shipped_status;
					$order->sync_shipped_status = 'C';
					if ($order->save()){
						OdOrderShipped::updateAll(['status'=>1],['order_id'=>$order->order_id,'status'=>2]); //提交失败 记录改成为提交成功
						OperationLogHelper::log($module,$order->order_id,$action,'虚拟发货状态由:['.$OriginStaus.']变成['.$order->sync_shipped_status.']',$fullName);
						$edit_log[] = $order->order_id;
						if ($OriginStaus == 'F'){
							if (isset(DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($order->order_source)})){
								DashBoardStatisticHelper::CounterAdd($puid, DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($order->order_source)},-1);
							}
						}
						
					}else{
						$errors = $order->getErrors();
						$errorMsg .= $order->order_id.' 失败原因：'.json_encode($errors);
					}
				}
			}
			$result['message'] = $errorMsg;
		} catch (\Exception $e) {
			$errorMsg .= '内部错误：'.$e->getMessage();
			$result['ack'] = false;
			$result['message'] = $errorMsg;
			$result['code'] = '4001';
		}
		
		if(!empty($edit_log)){
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "标记为提交成功, 订单号: ".implode(', ', $edit_log));
		}
		
		return $result;
	}//end of function setOrderSyncShipStatusComplete
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	更新待发货数量
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $sku							商品sku
	 * @param     $OriginWHID					原来 的仓库ID
	 * @param     $CurrentWHID					当前的仓库ID
	 * @param     $OriginQty					原来的数量
	 * @param     $CurrentQty					当前的数量
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function updateUnshippedQtyOMS($sku ,  $OriginWHID , $CurrentWHID , $OriginQty , $CurrentQty){
		$rt = ['ack'=>true , 'code'=>'2000' , 'message'=>''];
		//$sku = (String) $sku;
		try {
			if($OriginWHID <> $CurrentWHID ){
				//换仓库
				$rt1 = \eagle\modules\inventory\apihelpers\InventoryApiHelper::updateQtyOrdered($OriginWHID, $sku, -$OriginQty);
				$rt2 = \eagle\modules\inventory\apihelpers\InventoryApiHelper::updateQtyOrdered($CurrentWHID, $sku, $CurrentQty);
				
				
				if (empty($rt1['status'])){
					$rt['ack'] = false;
					$rt['code'] = '40002';
					$rt['message'] .= "仓库"." $OriginWHID ($sku) -$OriginQty 失败,原因：".$rt1['msg'];
				}
				
				if (empty($rt2['status'])){
					$rt['ack'] = false;
					$rt['code'] = '40003';
					$rt['message'] .= "仓库 "."$OriginWHID ($sku) +$CurrentQty 失败,原因：".$rt2['msg'];
				}
			}else{
				//相同仓库时减少一次api 接口调用
				$subQty = $CurrentQty-$OriginQty; 
				$rt1 = \eagle\modules\inventory\apihelpers\InventoryApiHelper::updateQtyOrdered($OriginWHID, $sku,$subQty );
				
				if (empty($rt1['status'])){
					$rt['ack'] = false;
					$rt['code'] = '40004';
					$rt['message'] = "仓库 "." $OriginWHID $sku +".$subQty." 失败,原因：".$rt1['msg'];
				}
			}
			return $rt;
		} catch (\Exception $e) {
			return ['ack'=>false , 'code'=>'40001' , 'message'=>(__FUNCTION__)." ".$e->getMessage()." line no ".$e->getLine()];
		}
	}//end of function updateUnshippedQtyOMS
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	更换仓库时修改待发货数量的接口
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderIdList					小老板订单号
	 * @param     $OriginWHID					原来 的仓库ID
	 * @param     $CurrentWHID					当前的仓库ID
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function updateUnshipedQtyByChangeWarehouse($orderIdList , $OriginWHID , $CurrentWHID){
		$errorMsg = '';
		try{
			//找出 当前更改仓库的订单
			$changeWarehouseOrder = OdOrder::find()->where(['order_id'=>$orderIdList])->andWhere(['<>','default_warehouse_id',$CurrentWHID])->all();
			$updateQtyItemList = [];
			//计算 item 总数
			foreach($changeWarehouseOrder as $tmpOrder){
				$updateQtyItemList [$tmpOrder->default_warehouse_id] = [];
				foreach($tmpOrder->items as $item){
					//$sku = empty($item['sku'])?$item['product_name']:$item['sku'];
					/*20170321start
					list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
					if (empty($ack)) $errorMsg .= " ".$message ;
					20170321end*/
					if (!empty($item->root_sku)){
						$rootSKU = $item->root_sku;
						$updateQtyItemList [$tmpOrder->default_warehouse_id] [$rootSKU] = $item['quantity'];
					}
					
				}
			}
		
			//更新 待发货数量
			if (!empty($updateQtyItemList)){
				foreach($updateQtyItemList as $OriginWHID=>$tmpItemList){
					foreach($tmpItemList as $sku=>$qty){
						list($ack , $code , $message  )  = array_values(OrderBackgroundHelper::updateUnshippedQtyOMS($sku, $OriginWHID, $_REQUEST['warehouse'], $qty, $qty));
						if (empty($ack)) $errorMsg .= " ".$message ;
					}
				}
			}
		
		}catch(\Exception $e){
			$errorMsg .= " 内部错误";
			\Yii::error(__FUNCTION__." Error :".$e->getMessage()." line no ".$e->getLine(),'file');
		}
	}//end of updateUnshipedQtyByChangeWarehouse
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单设置 为验证通过 ，  ebay 订单为paypal地址已同步
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 								$orderIdList						小老板订单号
	 * 								$module								模块
	 * 								$action								操作
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack								执行结果是还成功
	 * 									message							执行的相关信息
	 * 									code							执行的代码
	 * 									data							执行结果返回的数据
	 * @invoking					OrderBackgroundHelper::setOrderVerifyPass($orderIdList);
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderVerifyPass($orderIdList ,$module='order',$action='通过验证'){
		$fullName = \Yii::$app->user->identity->getFullName();
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$errorMsg = '';
		$result = ['ack'=>true,'message'=>'' ,'code'=>'2000','data'=>[]];
		try {
			foreach(OdOrder::find()->where(['order_id'=>$orderIdList])->each(1) as $order){
				$newAttr = ['order_verify'=>OdOrder::ORDER_VERIFY_VERIFIED];
				$urt = OrderUpdateHelper::updateOrder($order, $newAttr,false,$fullName  , $action , $module);
				if ($urt['ack'] == false){
					$errorMsg .= $order->order_id.' 失败原因：'.$urt['message'];
					$result['code'] = $urt['code'];
				}
			}//end of foreach order
			$result['message'] = $errorMsg;
		} catch (\Exception $e) {
			$errorMsg .= '内部错误：'.$e->getMessage();
			$result['ack'] = false;
			$result['message'] = $errorMsg;
			$result['code'] = '4001';
		}
		return $result;
	}//end of function setOrderVerifyPass
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 空sku 的data conversion 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 								$puid						uid
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 * @invoking					OrderBackgroundHelper::OrderItemSKUDataCoversion($puid);
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function OrderItemSKUDataCoversion($puid){
		try {
			echo "\n ".(__function__)." entry \n";
			
			//更新 没有多属性 并且使用 order_source_itemid  为product id 的平台
			$sql = "update od_order_item_v2 set sku =  order_source_itemid , is_sys_create_sku = 'Y'  where sku = '' and ifnull(product_attributes,'') = '' and order_id in (select order_id from od_order_v2 where order_source not in ('lazada','linio','jumia','priceminister'))";
			$rt = \Yii::$app->subdb->createCommand($sql)->execute();
			echo "$sql \n puid=$puid update od_order_item_v2 step 1 effect = $rt \n";
			
			//更新 没有多属性 并且使用 order_source_order_item_id  为product id 的平台
			$sql = "update od_order_item_v2 set sku =  order_source_order_item_id , is_sys_create_sku = 'Y'  where sku = '' and ifnull(product_attributes,'') = '' and order_id in (select order_id from od_order_v2 where order_source in ('lazada','linio','jumia','priceminister'))";
		$rt = \Yii::$app->subdb->createCommand($sql)->execute();
					echo "$sql \n puid=$puid update od_order_item_v2 step 2  effect = $rt \n";
			
					//aliexpress 多属性 生成sku
			$sql ="select product_attributes , product_name , order_source_itemid from od_order_item_v2 where sku = '' and ifnull(product_attributes,'') <> '' and order_id in (select order_id from od_order_v2 where order_source in ('aliexpress')) group by product_attributes , product_name , order_source_itemid ";
		$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
					foreach($rts as $v){
					$currentAttr =  explode('+' ,$v['product_attributes'] );
			//以attribute label 的升序排列
			ksort($currentAttr);
			$tmpStep = 'a0-1';
			foreach($currentAttr as $tmp){
			$suffix = '';
			$tmpStep = 'a0';
			$itemOneAttr = explode(':' ,$tmp);
			if (isset($itemOneAttr[1])){
			$suffix .= @$itemOneAttr[1];
			$tmpStep = 'a1';
			}else{
			$tmpStep = 'a2';
			}
			}
			$systemSKU = $v['order_source_itemid'].$suffix;
			$effect = OdOrderItem::updateAll(['sku'=>$systemSKU , 'is_sys_create_sku'=>'Y'] , ['product_attributes'=>$v['product_attributes'] , 'product_name'=> $v['product_name'] , 'order_source_itemid' =>$v['order_source_itemid'] ]);
			echo "puid=$puid update sku = $systemSKU effect =  $effect ";
			}
			
			//ebay 多属性 生成 sku
			$sql ="select product_attributes , product_name , order_source_itemid from od_order_item_v2 where sku = '' and ifnull(product_attributes,'') <> '' and order_id in (select order_id from od_order_v2 where order_source in ('ebay')) group by product_attributes , product_name , order_source_itemid ";
		$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
		foreach($rts as $v){
					$currentAttr = json_decode($v['product_attributes'],true);
					//以attribute label 的升序排列
					ksort($currentAttr);
			$tmpStep = 'a0-2';
			foreach($currentAttr as $tmp){
			if (is_string($tmp)){
			$suffix .= @$tmp;
			$tmpStep = 'a4';
			}elseif (is_array($tmp)){
			foreach($tmp as $_subTmp){
			$suffix .= @$_subTmp;
			$tmpStep = 'a5';
			}
			}
				
			}
			$systemSKU = $v['order_source_itemid'].$suffix;
				$effect = OdOrderItem::updateAll(['sku'=>$systemSKU , 'is_sys_create_sku'=>'Y'] , ['product_attributes'=>$v['product_attributes'] , 'product_name'=> $v['product_name'] , 'order_source_itemid' =>$v['order_source_itemid'] ]);
			echo "puid=$puid update sku = $systemSKU effect =  $effect \n";
		}
			
				/* 需要更新的表 由于 商品修改title 的原因 ， 所以 除了product表外其他表都不改
				* pd_product
				* wh_product_stock
				* wh_stock_change_detail
				* wh_stock_take_detail
				* pc_purchase_items
				* pc_purchase_suggestion
				*/
				//先检查sku 与alias 是否存在， 存在则打印这个puid 与sku
				//$updateTableList = ['pd_product' , 'wh_product_stock' , 'wh_stock_change_detail' , 'wh_stock_take_detail' , 'pc_purchase_items' , 'pc_purchase_suggestion'];
				$updateTableList = ['pd_product' ];
				$total = [];
				foreach($updateTableList as $tableName){
				$sql = " update
				$tableName  a ,
				(select left(product_name,255) as oldsku , sku from od_order_item_v2
				where  left(product_name,255) in (select sku from $tableName) and sku not in (select sku from $tableName) and is_sys_create_sku = 'Y' group by product_name , sku ) b
				set  a.sku = b.sku
				where a.sku = b.oldsku ";
					
				// 			$effect = \Yii::$app->subdb->createCommand ( $sql )->execute ();
				// 			echo "puid=$puid update $tableName effect = $effect \n";
					
				// 异常数据检查
				$sql = "select count(1) as ct from $tableName   where sku in (select sku from od_order_item_v2 where  left(product_name,255) in (select sku from $tableName) and sku  in (select sku from $tableName)  and is_sys_create_sku = 'Y')";
				$rts = \Yii::$app->subdb->createCommand ( $sql )->queryAll ();
				foreach ( $rts as $row ) {
				if (! empty ( $row ['ct'] )) {
				echo " \n puid=" . $puid . ' table=' . $tableName . ' -warning (' . $row ['ct'] . ')';
				if (isset ( $total [$tableName] )) {
				$total [$tableName] ++;
				} else {
				$total [$tableName] = 0;
				}
				} else {
				echo " \n puid=" . $puid . ' table=' . $tableName . ' is normal!';
				}
				}//end of each rts
				}//end of table
			
				//清除 无商品的root sku
				$sql = "update od_order_item_v2 set root_sku = '' where root_sku not in (select sku from pd_product) and order_id in (select order_id from od_order_v2 where order_status = '".OdOrder::STATUS_PAY."');";
				$rt = \Yii::$app->subdb->createCommand($sql)->execute();
				echo "$sql \n puid=$puid update od_order_item_v2 step 3 clear inactive root sku effect = $rt \n";
		} catch (\Exception $e) {
			echo "\n ".(__FUNCTION__)." error message:".$e->getMessage()." line no:".$e->getLine();
		}
		
		
	}//end of function OrderItemSKUDataCoversion
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay item 重新获取
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 								$puid						uid
	 * 								$orderID					小老板订单号
	 * 								$order_source_site_id		站点
	 * 								$order_status				订单状态
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 * @invoking					OrderBackgroundHelper::refreshEbayOrderItem($uid , $orderID , $order_source_site_id , $order_status);
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function refreshEbayOrderItem($uid , $orderID , $order_source_site_id , $order_status , $platform='' , $selleruserid='') {
		
		
		$MTs = OdEbayTransaction::find ()->where ( [ 
				'=',
				'order_id',
				$orderID 
		] )->all ();
		echo "\n v1.2  order_id=$orderID mts count = ".count($MTs);
		foreach ( $MTs as $MT ) {
			// 生成 订单商品
			// $MMOI=OdOrderItem::find()->where('order_id=:oi And order_source_order_item_id=:osoii',[':oi'=>$os['order_id'],':osoii'=>$MT->id])->one();
			$MMOI = OdOrderItem::find ()->where ( 'order_source_order_item_id=:osoii', [ 
					':osoii' => $MT->id 
			] )->one ();
			if (is_null ( $MMOI )) {
				$MMOI = new OdOrderItem ();
			}
			// print_r($MT);
			
			// 获取对应item的主图地址
			$photo = '';
			if (isset ( $MT->variation ['VariationSpecifics'] ['NameValueList'] ) && is_array ( $MT->variation ['VariationSpecifics'] ['NameValueList'] )) {
				$ebayVarList = [ ];
				if (isset ( $MT->variation ['VariationSpecifics'] ['NameValueList'] ['Name'] )) {
					// 多个属性时与单个不一样
					$ebayVarList [] = [ 
							$MT->variation ['VariationSpecifics'] ['NameValueList'] ['Name'] => $MT->variation ['VariationSpecifics'] ['NameValueList'] ['Value'] 
					];
				} else {
					// 多个属性时与单个不一样
					foreach ( $MT->variation ['VariationSpecifics'] ['NameValueList'] as $tmpEbayVarList ) {
						
						$ebayVarList [] = [ 
								$tmpEbayVarList ['Name'] => $tmpEbayVarList ['Value'] 
						];
					}
				}
			}
			$ebay_product_attributes = '';
			if (! empty ( $ebayVarList )) {
				$ebay_product_attributes = json_encode ( $ebayVarList );
			}
			
			$item = EbayItem::find ()->where ( [ 
					'itemid' => $MT->itemid 
			] )->one ();
			if (! empty ( $item )) {
				$photo = $item->mainimg;
			} else {
				
				// 以为使用异步队列
				echo "\n **** photo queue :" . $MT->itemid . " @$ebay_product_attributes, $uid";
				$photo = EbayOrderHelper::getItemPhoto ( $MT->itemid, @$ebay_product_attributes, $uid );
				echo "\n photo: $photo ";
				/* 20161128kh start 20161128kh end */
				if (empty ( $photo )) {
					// 即时的获取EbayItem
					$getitem_api = new getsingleitem ();
					try {
						set_time_limit ( 0 );
						$r = $getitem_api->apiItem ( $MT->itemid );
						echo "start 317" . "\n";
					} catch ( \Exception $ex ) {
						\Yii::error ( print_r ( $ex->getMessage () ) );
					}
					if (! $getitem_api->responseIsFail) {
						echo "start 321" . "\n";
						\Yii::info ( 'get success' );
						if (! empty ( $r ['PictureURL'] )) {
							$pic = $r ['PictureURL'];
							if (is_array ( $pic )) {
								$pic_arr = $pic;
							} else {
								$pic_arr = array (
										$pic 
								);
							}
							
							$photo = $pic_arr ['0'];
						}
					}
				} // end of photo 即时的获取EbayItem
			}
			// listing 站点前缀
			try {
				$siteUrl = \common\helpers\Helper_Siteinfo::getSiteViewUrl ();
				$siteList = \common\helpers\Helper_Siteinfo::getSite ();
				$currentProudctUrl = 'http://www.ebay.com/itm/' . $MT->id;
				$currentProudctUrl = in_array ( $order_source_site_id, $siteList ) ? $siteUrl [$order_source_site_id] . $MT->id : 'http://www.ebay.com/itm/' . $MT->id;
			} catch ( \Exception $ex ) {
				echo "\n" . (__function__) . ' set ebay Product Url Error Message:' . $ex->getMessage () . " Line no " . $ex->getLine () . "\n";
			}
			
			$AMMOI = array (
					'order_id' => $orderID,
					'order_source_order_item_id' => $MT->id,
					'source_item_id' => $MT->itemid,
					'sku' => $MT->sku,
					'platform_sku' => $MT->sku,
					'product_name' => $MT->title,
					'photo_primary' => $photo,
					'price' => $MT->transactionprice,
					'shipping_price' => empty($MT->shippingservicecost)?0:$MT->shippingservicecost,
					'title' => $MT->title,
					'order_source_srn' => $MT->salesrecordnum,
					'quantity' => $MT->quantitypurchased,
					'ordered_quantity' => $MT->quantitypurchased,
					'order_source_order_id' => $MT->orderid,
					'order_source_transactionid' => $MT->transactionid,
					'order_source_itemid' => $MT->itemid,
					'is_bundle' => 0,
					'product_url' => $currentProudctUrl,
					'platform_status' => @$order_status, // ebay 新订单平台状态保存
					'create_time' => time (),
					'product_attributes' => @$ebay_product_attributes,
					'delivery_status'=>'allow',
					'update_time' => time () 
			);
			// 20170228 ebay 空sku 处理方案 根据 order_source_itemid 与商品属性生成 sku
			if (empty ( $AMMOI ['sku'] )) {
				try {
					if (isset ( $AMMOI ['product_attributes'] )) {
						$itemAttrList = [ ];
						$suffix = '';
						$currentAttr = json_decode ( $AMMOI ['product_attributes'], true );
						// 以attribute label 的升序排列
						if (! empty ( $currentAttr )) {
							ksort ( $currentAttr );
							
							foreach ( $currentAttr as $tmp ) {
								if (is_string ( $tmp )) {
									$suffix .= @$tmp;
								} elseif (is_array($tmp)){
									foreach($tmp as $_subTmp){
									$suffix .= @$_subTmp;
									}
								}
							}
						}
																
					}else{
						$suffix = '';
					}
					$AMMOI['sku'] = $AMMOI['order_source_itemid'].$suffix;
					$AMMOI['is_sys_create_sku'] = 'Y';
																
				} catch (\Exception $e) {
						\yii::error((__FUNCTION__).' error message : '.$e->getMessage()." Line no:".$e->getLine().' sku empty ', 'file');
									
				}
			}
		
			$MMOI->setAttributes($AMMOI,0);
			if(!$MMOI->save(false)){
				//echo $MMOI->getErrors();
						$result['success'] = 1;
						$result['message'] = 'E007eBay订单写入订单商品信息失败';
				return $result;
				//SysLogHelper::SysLog_Create("order",__CLASS__, __FUNCTION__,"Error 5.1","Save the order commodity information failure,odOrderItemParams:".print_r($AMMOI,true)."error:".$MMOI->getErrors(), "Error");
			}else{
				try {
					
					if ($MMOI->delivery_status != 'disable'){
						$errorMsg = '';
						list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($MMOI, false ,$platform,$selleruserid ));
						//新订单保存root sku
						if (!empty($rootSKU)){
							$MMOI->root_sku = $rootSKU;
							$MMOI->save(false);
						}
							
						if (!empty($rootSKU)){
							//新订单  默认仓库为0 原始数量为0 ，新数量为订单的数量
							list($ack , $code , $message  )  = array_values(self::updateUnshippedQtyOMS($rootSKU, 0, 0, 0, $MMOI->quantity));
							//echo "$rootSKU , $ack , $code , $message ";
							if (empty($ack)) $errorMsg .= " order_source_order_id=".$MMOI->order_source_order_id." Error Message:".$message ;
							if (!empty($errorMsg)) echo $errorMsg;
						}
						
					}
					
				} catch (\Exception $e) {
					\yii::error('error message : '.$e->getMessage()." Line no:".$e->getLine().' and item data: '.json_encode($MMOI->attributes), 'file');
				}
			}
		} //end of each $MTs
	}//end of function refreshEbayOrderItem
	
}

?>