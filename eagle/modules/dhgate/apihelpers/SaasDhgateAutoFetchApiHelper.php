<?php
namespace eagle\modules\dhgate\apihelpers;

use \Yii;
use eagle\models\SaasDhgateUser;
use eagle\models\QueueDhgateGetorder;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use common\api\dhgateinterface\Dhgateinterface_Api;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\util\models\GlobalConfigData;
use common\helpers\Helper_Array;

/**
 +------------------------------------------------------------------------------
 * Dhgate 同步类(处理非 get order list 的同步任务)
 +------------------------------------------------------------------------------
 */
class SaasDhgateAutoFetchApiHelper {
	public static $cronJobId=0;
	private static $dhgateGetOrderDetailVersion = null;
	/**
	 * @return the $cronJobId
	 */
	public static function getCronJobId() {
		return self::$cronJobId;
	}
	
	/**
	 * @param number $cronJobId
	 */
	public static function setCronJobId($cronJobId) {
		self::$cronJobId = $cronJobId;
	}
	
	/**
	 获取活跃用户的puid列表-----目前是7天内没有登录的就算不活跃用户。
	 */
	public static function getActiveUsersList(){
		$activeUsersPuidArr = UserLastActionTimeHelper::getPuidArrByInterval(7*24);
		return $activeUsersPuidArr;
	
	}
	
	/**
	 * 该进程判断是否需要退出
	 * 通过配置全局配置数据表ut_global_config_data的Order/dhgateGetOrderDetailVersion 对应数值
	 *
	 * @return  true or false
	 */
	private static function checkNeedExitNot(){
		$dhgateGetOrderVersionFromConfig = ConfigHelper::getGlobalConfig("Order/dhgateGetOrderVersion",'NO_CACHE');
		if (empty($dhgateGetOrderVersionFromConfig))  {
			//数据表没有定义该字段，不退出。
			return false;
		}
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (self::$dhgateGetOrderDetailVersion===null)	self::$dhgateGetOrderDetailVersion = $dhgateGetOrderVersionFromConfig;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$dhgateGetOrderDetailVersion <> $dhgateGetOrderVersionFromConfig){
			echo "Version new $dhgateGetOrderVersionFromConfig , this job ver ".self::$dhgateGetOrderDetailVersion." exits \n";
			return true;
		}
	
		return false;
	}
	
	/**
	 * 同步订单详情2.0
	 * @author dzt 2015-06-23
	 */
	static function getOrderDetail( $type , $orderBy="id" , $time_interval=1800 ){
		// dzt20151229 敦煌订单详情降低拉取优先级，所有订单延迟 X2 时间
		$time_interval = $time_interval * 2;
		
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret = self::checkNeedExitNot();
		if ( true === $ret ) 
			exit;		
		
		$backgroundJobId=self::getCronJobId();
		// 同步订单
		$connection = Yii::$app->db;
		$t = time() - $time_interval;
		if ($type == QueueDhgateGetorder::NOFINISH){
			// 敦煌暂时全部以 2小时间隔找所有状态订单，@todo 后面再分离这两种情况
// 			if ($time_interval == 1800){
// 				$order_status = array('"PLACE_ORDER_SUCCESS"','"IN_CANCEL"','"WAIT_SELLER_SEND_GOODS"','"IN_ISSUE"','"IN_FROZEN"','"WAIT_SELLER_EXAMINE_MONEY"');
// 				$order_status_str = implode(',', $order_status);
// 				$command=$connection->createCommand('select `id`,`dhgate_uid`,`orderid`,`last_time`,`order_status` from  `queue_dhgate_getorder` where `status` <> 1 AND `is_active`=1 AND `times` < 10 AND type='.$type.' AND order_status in ('.$order_status_str.') AND last_time < '.$t.' order by `'.$orderBy.'`,`order_status` ASC limit 100');
// 			}else {
// 				$order_status = array('"SELLER_PART_SEND_GOODS"','"WAIT_BUYER_ACCEPT_GOODS"','"FUND_PROCESSING"','"FINISH"','"RISK_CONTROL"');
// 				$order_status_str = implode(',', $order_status);
// 				$command=$connection->createCommand('select `id`,`dhgate_uid`,`orderid`,`last_time`,`order_status` from  `queue_dhgate_getorder` where `status` <> 1 AND `is_active`=1 AND `times` < 10 AND type='.$type.' AND order_status in ('.$order_status_str.') AND last_time < '.$t.' order by `'.$orderBy.'`,`order_status` ASC limit 100');
// 			}

			// dzt20160503 由于前面筛选的是last_time 100个 ，如果大部分是非活跃用户订单的话了，其他客户的的订单就一直没办法更新，这里lasttime计算下次运行时间比直接写入next_execution_time 的来的复杂
			// 不改数剧库的快速解决方法是 通过global config记下这次执行的最后的sync id 为下次执行的offset，直到get到的数量为0是 重置offset为0
			$syncOffset = ConfigHelper::getGlobalConfig("dhgate/detailSyncOffset");
			$newSyncOffset = $syncOffset;
			if(empty($syncOffset))$syncOffset = 0;
			
			$order_status = QueueDhgateGetorder::$orderStatus;
			unset($order_status['111000']);
			unset($order_status['111111']);
			Helper_Array::removeEmpty($order_status);
			$order_status_str = implode(',', array_keys($order_status));
			$command=$connection->createCommand('select * from  `queue_dhgate_getorder` where `status` <> 1 AND `is_active`=1 AND `times` < 10 AND type='.$type.' AND order_status in ('.$order_status_str.') AND last_time < '.$t.' order by `'.$orderBy.'`,`order_status` ASC limit '.$syncOffset.',100 ');
		}else{
			$command=$connection->createCommand('select * from  `queue_dhgate_getorder` where `status` <> 1 AND `is_active`=1 AND `times` < 10 AND type='.$type.' limit 100');
		}
		$dataReader=$command->query();
		$hasGotRecord=false;
		while(($row=$dataReader->read())!==false) {
			if ($type == QueueDhgateGetorder::NOFINISH){
				$newSyncOffset++;
			}
			
			$puid=$row['uid'];
			 
			
			$logTimeMS1=TimeUtil::getCurrentTimestampMS(); //获取当前时间戳，毫秒为单位，该数值只有比较意义。
			$last_time = time();
			echo $row['orderid']."\n";
			//1. 先判断是否可以正常抢到该记录
			$command = $connection->createCommand("update queue_dhgate_getorder set status=1 where id =". $row['id']." and status<>1 ") ;
			$affectRows = $command->execute();
			if ($affectRows <= 0)	continue; //抢不到
			//	\Yii::info("aliexress_get_order_".$type." gotit jobid=$backgroundJobId start");
				
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
				
			//2. 抢到记录，设置同步需要的参数
			$hasGotRecord=true;
			$QDG_obj = QueueDhgateGetorder::findOne($row['id']);

			$api = new Dhgateinterface_Api();
			
			// 接口传入参数敦煌订单号
			$appParams = array (
				'orderNo' => $row['orderid']
			);
			// 调用接口获取订单列表
			
			$order_detail = $api->getOrderDetail($row['dhgate_uid'], $appParams);
			$order_items = $api->getOrderItems($row['dhgate_uid'], $appParams);
			
			$logTimeMS3=TimeUtil::getCurrentTimestampMS();

			//echo print_r ( $result, 1 );
			// 是否同步成功
			if ($order_detail['success'] !== true ) {
				$QDG_obj->message = $order_detail ['error_message'];
				$QDG_obj->status = 3;
				$QDG_obj->times += 1;
				$QDG_obj->last_time = $last_time;
				$QDG_obj->update_time = time ();
				$QDG_obj->save (false);
			} elseif ($order_items['success'] !== true){
				$QDG_obj->message = $order_items ['error_message'];
				$QDG_obj->status = 3;
				$QDG_obj->times += 1;
				$QDG_obj->last_time = $last_time;
				$QDG_obj->update_time = time ();
				$QDG_obj->save (false);
			} else {
				
				// 同步成功保存数据到订单表
				if (true){
					$r = DhgateApiHelper::saveDhgateOrder($QDG_obj, $order_detail['response'] , $order_items['response']);
					$logTimeMS4=TimeUtil::getCurrentTimestampMS();
					
					$finishStatus = DhgateApiHelper::getOrderCompleteStatus();
					$finishStatusArr = explode(',', $finishStatus);
					
					// 判断是否付款并且保存成功,是则删除数据，否则继续同步
					if ( $r['success'] == true && isset($order_detail['response']['orderStatus']) && in_array($order_detail['response']['orderStatus'], $finishStatusArr)) {
						$QDG_obj->delete ();
					} else {
						if ( $r['success'] == false ) {
							$QDG_obj->status = 3;
							$QDG_obj->times += 1;
							$QDG_obj->message = "敦煌订单" . $QDG_obj->orderid . "保存失败".$r ['message'];
						} else {
							$QDG_obj->status = 2;
							$QDG_obj->type = QueueDhgateGetorder::NOFINISH;
							$QDG_obj->times = 0;
							$QDG_obj->message = '';
						}
						$QDG_obj->order_status = isset($order_detail ['orderStatus'])?$order_detail ['orderStatus']:$QDG_obj->order_status;
						$QDG_obj->last_time = $last_time;
						$QDG_obj->update_time = time ();
						$QDG_obj->save (false);
					}

					$logTimeMS5=TimeUtil::getCurrentTimestampMS();

					\Yii::info("queue_dhgate_getorder_".$type." saveok jobid=$backgroundJobId t2_1=".($logTimeMS2-$logTimeMS1).
							",t3_2=".($logTimeMS3-$logTimeMS2).",t4_3=".($logTimeMS4-$logTimeMS3).",t5_4=".($logTimeMS5-$logTimeMS4).
							",t5_1=".($logTimeMS5-$logTimeMS1).",puid=".$QDG_obj->uid,"file");

				}
			}
		}
		
		if ($type == QueueDhgateGetorder::NOFINISH){
			if($newSyncOffset == $syncOffset){// offset太大没有get到一条记录，重置offset
				$newSyncOffset = 0;
			}
			echo "new offset:".$newSyncOffset." \n";
			ConfigHelper::setGlobalConfig('dhgate/detailSyncOffset' , $newSyncOffset);
		}
		
			
		return $hasGotRecord;
	}
	
}