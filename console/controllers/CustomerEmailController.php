<?php

namespace console\controllers;

use common\helpers\Helper_Currency;
use console\helpers\CustomerEmailHelper;
use eagle\models\BackgroundMonitor;
use eagle\models\OdOrder;
use eagle\models\OrderHistoryStatisticsData;
use eagle\models\UserBase;
use eagle\models\UserInfo;
use yii\console\Controller;

class CustomerEmailController extends Controller
{
	/**
	 * ./yii customer-email/get-customer-email
	 * 查询所有user库email
	 * lrq20170929
	*/
	public function actionGetCustomerEmail(){
		echo "\n start job ".date("Y-m-d H:i:s", time())." \n";
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
				 
		
				echo "\n p".$puid." Running ............................................";
				
				//***************获取订单相关信息    start
				//od_order_v2
				CustomerEmailHelper::insertCustomerEmail('', $puid, $puid_activitys);
				//od_order_old_v2
				CustomerEmailHelper::insertCustomerEmail('_old', $puid, $puid_activitys);
				
				//***************获取订单相关信息    end
				
				echo "\n p".$puid." end ------consuming: ". (time() - $uid_start_time)." \n";
			}
			catch (\Exception $e){
				echo "\n errer:".$e->getMessage()."\n";
			}
		}
		
		echo "\n\n ------job consuming: ". (time() - $pro_start_time);
		echo "\n end job ".date("Y-m-d H:i:s", time())." \n";
	}
	
	/**
	 * ./yii customer-email/statistics-customer-email
	* 统计总email数、每个平台的email数 
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
	
	/**
	 * ./yii customer-email/update-ebay-category
	 * 根据已获取的ebay 类别更新email主表
	* lrq20170929
	*/
	public function actionUpdateEbayCategory(){
		echo "\n start job ".date("Y-m-d H:i:s", time())." \n";
		$pro_start_time = time();
		
		CustomerEmailHelper::UpdateEbayCategory();
		
		echo "\n\n ------job consuming: ". (time() - $pro_start_time);
		echo "\n end job ".date("Y-m-d H:i:s", time())." \n";
	}
	
	/**
	 * ./yii customer-email/customer-aliexpress-item
	* 获取aliexpress的类目
	* lrq20170929
	*/
	public function actionCustomerAliexpressItem(){
		echo "\n start job ".date("Y-m-d H:i:s", time())." \n";
		$pro_start_time = time();
	
		CustomerEmailHelper::CustomerAliexpressItem();
	
		echo "\n\n ------job consuming: ". (time() - $pro_start_time);
		echo "\n end job ".date("Y-m-d H:i:s", time())." \n";
	}

	/**
	 * [actionCustomerEbayItem description]
	 * @author willage 2017-10-24T18:25:44+0800
	 * @update willage 2017-10-24T18:25:44+0800
	 * ./yii customer-email/customer-ebay-item
	 */
	public function actionCustomerEbayItem(){
		echo __FUNCTION__."-".time()." start\n";
		$rtn=CustomerEmailHelper::getCustomerEbayItem();
		// if ($rtn===false){
		//     sleep(30);
		// }
		echo __FUNCTION__."-".time()." end cycle\n";

	}
	
	/**
	 * ./yii customer-email/export-email
	 * 导出email
	 * lrq20170929
	 */
	public function actionExportEmail(){
		echo "\n start job ".date("Y-m-d H:i:s", time())." \n";
		$pro_start_time = time();
	
		CustomerEmailHelper::exportEmail();
	
		echo "\n\n ------job consuming: ". (time() - $pro_start_time);
		echo "\n end job ".date("Y-m-d H:i:s", time())." \n";
	}
	
	/**
	 * ./yii customer-email/update-active-time
	 * 更新最新活跃时间
	* lrq20170929
	*/
	public function actionUpdateActiveTime(){
		echo "\n start job ".date("Y-m-d H:i:s", time())." \n";
		$pro_start_time = time();
		
		CustomerEmailHelper::UpdateActiveTime();
		
		echo "\n\n ------job consuming: ". (time() - $pro_start_time);
		echo "\n end job ".date("Y-m-d H:i:s", time())." \n";
	}
	
	/**
	 * ./yii customer-email/get-customer-email-cn
	 * 查询所有user库email，只查询包含qq、163
	 * lrq20170929
	 */
	public function actionGetCustomerEmailCn(){
		echo "\n start job ".date("Y-m-d H:i:s", time())." \n";
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
				 
				echo "\n p".$puid." Running ............................................";
	
				//***************获取订单相关信息    start
				//od_order_v2
				CustomerEmailHelper::insertCustomerEmailCn('', $puid, $puid_activitys);
				//od_order_old_v2
				CustomerEmailHelper::insertCustomerEmailCn('_old', $puid, $puid_activitys);
	
				//***************获取订单相关信息    end
	
				echo "\n p".$puid." end ------consuming: ". (time() - $uid_start_time)." \n";
			}
			catch (\Exception $e){
				echo "\n errer:".$e->getMessage()."\n";
			}
		}
	
		echo "\n\n ------job consuming: ". (time() - $pro_start_time);
		echo "\n end job ".date("Y-m-d H:i:s", time())." \n";
	}
}