<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\models\OrderHistoryStatisticsData;
use common\helpers\Helper_Currency;
use eagle\models\OdOrder;
use eagle\models\UserInfo;

error_reporting(0);

class OrderUserStatisticNewController extends Controller
{
	/**
	 +----------------------------------------------------------
	 * 订单渠道统计
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2016/02/03				初始化
	 +----------------------------------------------------------
	 * 1)订单处理统计平台生成订单数组
	 *
	 * 调用方法:  .yii order-user-statistic-new/order-process-statistics-new
	 */
	public function actionOrderProcessStatisticsNew()
	{
 
			
		//现时月份，年份
		$nowMonth = 2015;
		$nowYear = 12;
			
// 		//获取数据
		$mainUsers = UserBase::find()->select('uid')->where(['puid'=>0])->asArray()->all();
			
		$platformResult = array();//平台订单生成时间统计
		$userSumAmount = array();
			
		foreach ($mainUsers as $puser){
			//	break;//ystest
			$puid = $puser['uid'];
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
			 
				
			echo "\n".$db_name." Running ...";
	
			$subdbConn=\yii::$app->subdb;
	
			//1)订单处理统计平台生成订单数组$platformResult赋值
			self::setPlatformResult($platformResult, $nowYear, $nowMonth);
			self::setUserSumAmount($userSumAmount , $puid);
			
		}//end of each user
			
		$result = \yii::$app->db->createCommand("delete from order_history_statistics_data where type='platform2015' ")->execute();
		$result = \yii::$app->db->createCommand("delete from order_history_statistics_data where type='usersumamount2015' ")->execute();
		
// 		$tmpSaveArr = array();
		$tmpSaveArr[] = array('type'=>'platform2015', 'history_date'=>2015,'json_params'=>json_encode($platformResult));
		$tmpSaveArr[] = array('type'=>'usersumamount2015', 'history_date'=>2015,'json_params'=>json_encode($userSumAmount));
		
		if (count($tmpSaveArr) > 0){
			$model = new OrderHistoryStatisticsData();
			foreach($tmpSaveArr as $attributes)
			{
				$_model = clone $model;
				$_model->setAttributes($attributes);
				$_model->save(false);
			}
		}
		//批量保存 end
	
// 		$backgroundMonitor->status = "End";
// 		$backgroundMonitor->last_end_time = date('Y-m-d H:i:s');
// 		$sqlUserdbEndtime = microtime(true); // 所有数据库Sql运行开始时间点。 单位：毫秒
// 		$backgroundMonitor->last_total_time = round($sqlUserdbEndtime-$sqlUserdbStarttime,3);
	
// 		$backgroundMonitor->save(false);
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单渠道统计按平台月份
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2016/02/03				初始化
	 +----------------------------------------------------------
	 */
	public static function setPlatformResult(& $platformResult, $nowYear, $nowMonth) {
		$subdbConn=\yii::$app->subdb;
	
		//因为是统计各个平台的订单生成时间，所以每次都要从2015-01-01开始统计重新统计一次
		$maxDate = '2015-01-01';
		$maxMonth = (int)date('m',strtotime($maxDate));
		$maxYear = (int)date('Y',strtotime($maxDate));
	
		do{
			for ($month = $maxMonth; $month <= ($maxYear==$nowYear ? $nowMonth : 12); $month++){
	
				$tmpDay = strtotime(date($maxYear."-".$month."-1"));
				$mdays=date('t',$tmpDay);
				$endDate=strtotime(date('Y-m-'.$mdays.' 23:59:59',$tmpDay));
	
				//相同的查询条件，所以只用一个AndSql来记录
				$sourceAndSql = '';
	
				//统计订单数据现在分开两张表来做统计，因为订单数据做了分割
				$sourcesql='select order_source,currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_v2 where 1 ';
					
				//统计历史订单数据
				$sourceOldsql='select order_source,currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_old_v2 where 1 ';
	
				$sourceAndSql.=" AND currency != '' ";
				$sourceAndSql.=' AND order_source_create_time >= '.strtotime($maxYear."-".$month."-1").'  ';
				$sourceAndSql.=' AND order_source_create_time < '.$endDate." and order_relation in ('normal','sm') and order_capture='N' group by order_source,currency ";
	
				$rows=$subdbConn->createCommand($sourcesql.$sourceAndSql)->queryAll();
				$rowOlds=$subdbConn->createCommand($sourceOldsql.$sourceAndSql)->queryAll();
					
				//合并两个数组
				$rows=array_merge($rows,$rowOlds);
	
				foreach ($rows as $row){
					$order_source = $row['order_source'];
					$totalAmount = Helper_Currency::convert($row['totalAmount'], 'CNY', $row['currency']);
					$totalCount = $row['totalCount'];
	
					if (!isset($platformResult[$maxYear."-".$month][$order_source])) $platformResult[$maxYear."-".$month][$order_source] = 0;
					if (!isset($platformResult[$maxYear."-".$month][$order_source.'TotalCount'])) $platformResult[$maxYear."-".$month][$order_source.'TotalCount'] = 0;
	
					$platformResult[$maxYear."-".$month][$order_source] += $totalAmount;
					$platformResult[$maxYear."-".$month][$order_source.'TotalCount'] += $totalCount;
				}
	
			}//each month
	
			//因为假如年份相同代表已经执行过，可以直接退出即可
			if ($maxYear==$nowYear)
				$maxYear = $maxYear+2;
			else
				$maxYear++;
	
			$maxMonth = 1;
		}while($maxYear <= $nowYear);
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单渠道统计按2015年统计
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2016/02/03				初始化
	 +----------------------------------------------------------
	 */
	public static function setUserSumAmount(& $platformResult,$puid) {
		$subdbConn=\yii::$app->subdb;
		
		//统计订单数据现在分开两张表来做统计，因为订单数据做了分割
		$sourcesql='select currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_v2 where 1 ';
			
		//统计历史订单数据
		$sourceOldsql='select currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_old_v2 where 1 ';
		
		$sourceAndSql.=" AND currency != '' ";
		$sourceAndSql.=' AND order_source_create_time >= '.strtotime('2015-01-01').'  ';
		$sourceAndSql.=' AND order_source_create_time < '.strtotime('2015-12-31 23:59:59')." and order_relation in ('normal','sm') and order_capture='N' group by currency";
		
		$rows=$subdbConn->createCommand($sourcesql.$sourceAndSql)->queryAll();
		$rowOlds=$subdbConn->createCommand($sourceOldsql.$sourceAndSql)->queryAll();
			
		//合并两个数组
		$rows=array_merge($rows,$rowOlds);
		
		foreach ($rows as $row){
			$totalAmount = Helper_Currency::convert($row['totalAmount'], 'CNY', $row['currency']);
			$totalCount = $row['totalCount'];
			
			if (!isset($platformResult[$puid])) $platformResult[$puid] = 0;
// 			if (!isset($platformResult[$puid]['totalAmount'])) $platformResult[$puid]['totalAmount'] = 0;
// 			if (!isset($platformResult[$puid]['totalCount'])) $platformResult[$puid]['totalCount'] = 0;
		
			$platformResult[$puid] += $totalAmount;
// 			$platformResult[$puid]['totalAmount'] += $totalAmount;
// 			$platformResult[$puid]['totalCount'] += $totalCount;
		}
	}
	
	/*
	 * ./yii order-user-statistic-new/order-user-count
	*/
	public function actionOrderUserCount()
	{
		//获取数据
		$mainUsers = UserBase::find()->select('uid')->where(['puid'=>0])->asArray()->all();
			
		$puidArr = array();
	
		foreach ($mainUsers as $puser){
				
			$puid = $puser['uid'];
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
			 
	
			echo "\n".$db_name." Running ...";
	
			$order = OdOrder::find()->one();
				
			if($order != null){
				$puidArr[$puid] = $puid;
			}
		}
	
		echo 'The total number of sellers with the order: '.count($puidArr);
	}
	
	/*
	 * ./yii order-user-statistic-new/get-customer-email
	 * lrq20170929
	*/
	public function actionGetCustomerEmail(){
		//查询所有测试账号
		$pro_start_time = time();
		$test_uid = array();
		$userInfo = UserInfo::find()->select(['uid'])->where(['is_test_user' => 1])->asArray()->all();
		foreach($userInfo as $use){
			$test_uid[] = $use['uid'];
		}
		//获取puid对应的活跃时间
		$puid_activitys = array();
		$dbConn = \yii::$app->db;
		$puidArr = $dbConn->createCommand('SELECT puid, last_activity_time FROM user_last_activity_time order by puid, last_activity_time')->queryAll();
		$dbConn->close();
		if(!empty($puidArr)){
			foreach($puidArr as $activity){
				$puid_activitys[$activity['puid']] = $activity['last_activity_time']; 
			}
		}
		
		//创建临时表
		$dbqueue2Conn = \yii::$app->db_queue2;
		$dbqueue2Conn->createCommand("CREATE TEMPORARY TABLE tmp_table_cus_email (ID int(8), email varchar(50))")->execute();
		
		//获取数据
		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		foreach ($mainUsers as $puser){
			try{
				$puid = $puser['uid'];
				
				//跳过测试账号
				if(in_array($puid, $test_uid)){
					continue;
				}
				//跳过未活跃过的账号
				// if(!array_key_exists($puid, $puid_activitys)){
					// continue;
				// }
				
				$uid_start_time = time();
				$cus_list = array();
				 
		
				$subdbConn = \yii::$app->subdb;
				echo "\n p".$puid." Running ............................................";
				
				//***************获取订单相关信息    start
				//删除重复信息语句
				$create_tmp_sql = "
					Insert into tmp_table_cus_email select ID, email from customer_email where email in (search_email_list);
					delete from tmp_table_cus_email where ID in (select a.ID from (select min(ID) ID from tmp_table_cus_email group by email) a);";
				//od_order_v2
				$sql = "select distinct consignee_email, order_source, source_buyer_user_id, grand_total, consignee_country_code, consignee_city, consignee_province, create_time, consignee_phone, consignee_mobile, currency
							 from od_order_v2 where consignee_email!='' and consignee_email is not null ";
				$conditon = "";
				//忽略某些平台
				$conditon .= " and order_source!='amazon' and order_source!='cdiscount'";
				//分页循环
				$page = 1;
				$page_size = 5000;
				$count = 0;
				$new_insert_count = 0;
				$start_time = time();
				while(1){
					$email_str = '';
					$cus_list = array();
					$start_row = ($page - 1) * $page_size;
					$orders = $subdbConn->createCommand($sql.$conditon.' group by consignee_email limit '.$start_row.','.$page_size)->queryAll();
					if(!empty($orders)){
						$insert_time = time();
						$count += count($orders);
						foreach($orders as $order){
							$email = str_replace("'", "", $order['consignee_email']);
							if(!array_key_exists($email, $cus_list)){
								$cus_list[$email] = [
									'puid' => $puid,
									'email' => $email,
									'cus_name' => $order['source_buyer_user_id'],
									'platform_source' => $order['order_source'],
									'country_code' => $order['consignee_country_code'],
									'create_time' => time(),
									'email_create_time' => $order['create_time'],
									'last_active_time' => strtotime($puid_activitys[$puid]),
									'phone' => empty($order['consignee_phone']) ? $order['consignee_mobile'] : $order['consignee_phone'].' \ '.$order['consignee_mobile'],
									'city' => empty($order['consignee_province']) ? $order['consignee_province'] : $order['consignee_city'],
									'grand_total' => $order['grand_total'],
									'currency' => $order['currency'],
								];
								
								$email_str .= "'$email',";
							}
						}
						
						if($email_str != ''){
							$email_str = rtrim($email_str, ",");
							//查询已存在的记录
							$rows = $dbqueue2Conn->createCommand("select email from customer_email where email in ($email_str)")->queryAll();
							if(!empty($rows)){
								foreach($rows as $row){
									if(array_key_exists($row['email'], $cus_list)){
										unset($cus_list[$row['email']]);
									}
								}
							}
						}
						
						$new_insert_count += count($cus_list);
						\eagle\modules\util\helpers\SQLHelper::groupInsertToDb('customer_email', $cus_list, 'db_queue2');
						$page++;
						echo "\n count: ".count($orders).", insert: ".count($cus_list).", consuming time: ".(time() - $insert_time);
						unset($cus_list);
						unset($orders);
					}
					else{
						break;
					}
					
					//超过20分钟则退出
					if(time() - $start_time > 1200){
						echo "\n search od_order_v2 timeout";
						break;
					}
				}
				echo "\n -----order_count: ". $count.", new count: $new_insert_count";
				$new_insert_count = 0;
				//od_order_old_v2
				$page = 1;
				$count = 0;
				$sql = str_replace('od_order_v2', 'od_order_old_v2', $sql);
				$start_time = time();
				while(1){
					$email_str = '';
					$cus_list = array();
					$start_row = ($page - 1) * $page_size;
					$orders_old = $subdbConn->createCommand($sql.$conditon.' limit '.$start_row.','.$page_size)->queryAll();
					if(!empty($orders_old)){
						$insert_time = time();
						$count += count($orders_old);
						foreach($orders_old as $order){
							$email = str_replace("'", "", $order['consignee_email']);
							if(!array_key_exists($email, $cus_list)){
								$cus_list[$email] = [
									'puid' => $puid,
									'email' => $email,
									'cus_name' => $order['source_buyer_user_id'],
									'platform_source' => $order['order_source'],
									'country_code' => $order['consignee_country_code'],
									'create_time' => time(),
									'email_create_time' => $order['create_time'],
									'last_active_time' => strtotime($puid_activitys[$puid]),
									'phone' => empty($order['consignee_phone']) ? $order['consignee_mobile'] : $order['consignee_phone'].' \ '.$order['consignee_mobile'],
									'city' => empty($order['consignee_province']) ? $order['consignee_province'] : $order['consignee_city'],
									'grand_total' => $order['grand_total'],
									'currency' => $order['currency'],
								];
								
								$email_str .= "'$email',";
							}
						}
						$email_str = rtrim($email_str, ",");
						
						if($email_str != ''){
							$email_str = rtrim($email_str, ",");
							//查询已存在的记录
							$rows = $dbqueue2Conn->createCommand("select email from customer_email where email in ($email_str)")->queryAll();
							if(!empty($rows)){
								foreach($rows as $row){
									if(array_key_exists($row['email'], $cus_list)){
										unset($cus_list[$row['email']]);
									}
								}
							}
						}
						
						$new_insert_count += count($cus_list);
						\eagle\modules\util\helpers\SQLHelper::groupInsertToDb('customer_email', $cus_list, 'db_queue2');
						$page++;
						echo "\n count: ".count($orders_old).", insert: ".count($cus_list).", consuming time: ".(time() - $insert_time);
						unset($cus_list);
						unset($orders_old);
					}
					else{
						break;
					}
					
					//超过20分钟则退出
					if(time() - $start_time > 1200){
						echo "\n search od_order_old_v2 timeout ";
						break;
					}
				}
				echo "\n -----order_old_count: ". $count.", new count: $new_insert_count";
				
				//***************获取订单相关信息    end
				
				echo "\n p".$puid." end ------consuming: ". (time() - $uid_start_time)." \n";
			}
			catch (\Exception $e){
				echo "\n errer:".$e->getMessage()."\n";
			}
		}
		
		echo "\n\n ------job consuming: ". (time() - $pro_start_time) ." \n";
	}
	
	/*
	 * ./yii order-user-statistic-new/statistics-customer-email
	* lrq20170929
	*/
	public function actionStatisticsCustomerEmail(){
		$dbqueue2Conn = \yii::$app->db_queue2;
		$allcount = $dbqueue2Conn->createCommand('SELECT count(1) count FROM customer_email')->queryAll();
		echo "\n allCount: ".$allcount[0]['count']."\n";
		
		$platform_arr = $dbqueue2Conn->createCommand('SELECT platform_source, count(1) count FROM customer_email group by platform_source')->queryAll();
		if(!empty($platform_arr)){
			echo "\n platform group statistics";
			foreach($platform_arr as $val){
				echo "\n ".$val['platform_source'].": ".$val['count'];
			}
			echo "\n ";
		}
	}
}