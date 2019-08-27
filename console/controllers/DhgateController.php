<?php
 
namespace console\controllers;
use \Yii;
use yii\console\Controller;
use yii\base\Exception;
use eagle\modules\dhgate\apihelpers\SaasDhgateAutoSyncApiHelper;
use eagle\models\QueueDhgateGetorder;
use eagle\modules\dhgate\apihelpers\SaasDhgateAutoFetchApiHelper;

/**
 * Aliexpress 后台脚本
 * @author million 88028624@qq.com
 * 2015-05-21
 */
class DhgateController extends Controller {
	
###################################################################################################################
	/**
	 * 订单列表拉取----后台job，目前只要单进程
	 * 按时间同步（type=day120）
	 * 同步120天内未完成订单的订单列表
	 * 主要作用：新绑定账号，重新绑定，重新开启同步的情况下需要一次性同步120天所有未完成的订单，提高新账号同步订单速度
	 * last_time 就是绑定，重新绑定，重新开启的时间
	 * 
	 * 2015-06-17
	 * ./yii dhgate/get-order-list-by-day120
	 */
	function actionGetOrderListByDay120(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."DAY120";
		SaasDhgateAutoSyncApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_list_by_day120 jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_list_by_day120 jobid=$cronJobId start");
		do{
			$rtn = SaasDhgateAutoSyncApiHelper::getOrderListByDay120();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo "dhgate_get_order_list_by_day120 jobid=$cronJobId sleep10 \n";
			  	\Yii::info("dhgate_get_order_list_by_day120 jobid=$cronJobId sleep10");
			   	sleep(10);
			}
		}while (time() < $startRunTime+3600); 
		echo "dhgate_get_order_list_by_day120 jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_list_by_day120 jobid=$cronJobId end");
	}
###################################################################################################################	
	/**
	 * 按时间和状态同步 （type=finish）
	 * 同步敦煌所有已完成订单的订单列表
	 * 主要作用：新绑定账号，重新绑定，重新开启同步的情况下需要一次性同步所有已完成的订单，拿用户所有订单数据供日后分析
	 * @author million 88028624@qq.com
	 * 2015-05-21
	 * ./yii dhgate/get-order-list-by-finish
	 */
	function actionGetOrderListByFinish(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."FINISH";
		SaasDhgateAutoSyncApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_list_by_finish jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_list_by_finish jobid=$cronJobId start");
		do{
			$rtn = SaasDhgateAutoSyncApiHelper::getOrderListByFinish();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo "dhgate_get_order_list_by_finish jobid=$cronJobId sleep10 \n";
				\Yii::info("dhgate_get_order_list_by_finish jobid=$cronJobId sleep10");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo "dhgate_get_order_list_by_finish jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_list_by_finish jobid=$cronJobId end");
	}
###################################################################################################################	
	/**
	 * 按时间同步 （type=time）
	 * 同步敦煌新产生订单的订单列表
	 * 主要作用：及时更新最新产生的订单
	 * ./yii dhgate/get-order-list-by-time
	 */
	function actionGetOrderListByTime(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."TIME";
		SaasDhgateAutoSyncApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_list_by_time jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_list_by_time jobid=$cronJobId start");
		do{
			$rtn = SaasDhgateAutoSyncApiHelper::getOrderListByTime();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo "dhgate_get_order_list_by_time jobid=$cronJobId sleep10 \n";
				\Yii::info("dhgate_get_order_list_by_time jobid=$cronJobId sleep10");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo "dhgate_get_order_list_by_time jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_list_by_time jobid=$cronJobId end");
	}
	
###################################################################################################################	
	/**
	 * 新绑定账户，重新开启，重新绑定第一次同步订单详情（未完成的）（type = 1 and last_time=0）
	 * 主要作用：及时同步新绑定账户订单
	 * @author dzt 2015-06-24
	 * ./yii dhgate/get-order-first
	 */
	function actionGetOrderFirst(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."FIRST";
		SaasDhgateAutoFetchApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_first jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_first jobid=$cronJobId start");
		do{
			$rtn = SaasDhgateAutoFetchApiHelper::getOrderDetail(QueueDhgateGetorder::FIRST);
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo "dhgate_get_order_first jobid=$cronJobId sleep10 \n";
				\Yii::info("dhgate_get_order_first jobid=$cronJobId sleep10");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo "dhgate_get_order_first jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_first jobid=$cronJobId end");
	}
###################################################################################################################	
	/**
	 * 新绑定账户，重新开启，重新绑定第一次同步订单详情（已完成的）（type = 2 , id asc）
	 * 主要作用：及时同步新绑定账户订单
	 * @author dzt 2015-06-24
	 * ./yii dhgate/get-order-finish
	 */
	function actionGetOrderFinish(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."FINISH";
		SaasDhgateAutoFetchApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_finish jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_finish jobid=$cronJobId start","file");
		do{
			$rtn = SaasDhgateAutoFetchApiHelper::getOrderDetail(QueueDhgateGetorder::FINISH);
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo "dhgate_get_order_finish jobid=$cronJobId sleep10 \n";
				\Yii::info("dhgate_get_order_finish jobid=$cronJobId sleep10");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo "dhgate_get_order_finish jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_finish jobid=$cronJobId end","file");
	}
###################################################################################################################	
	/**
	 * 新产生订单第一次同步订单详情（type = 3 ,id asc）
	 * 主要作用：及时同步新产生的订单
	 * @author dzt 2015-06-24
	 * ./yii dhgate/get-order-new
	 */
	function actionGetOrderNew(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."NEWORDER";
		SaasDhgateAutoFetchApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_neworder jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_neworder jobid=$cronJobId start");
		do{
			$rtn = SaasDhgateAutoFetchApiHelper::getOrderDetail(QueueDhgateGetorder::NEWORDER);
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo "dhgate_get_order_neworder jobid=$cronJobId sleep10 \n";
				\Yii::info("dhgate_get_order_neworder jobid=$cronJobId sleep10");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo "dhgate_get_order_neworder jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_neworder jobid=$cronJobId end");
	}
###################################################################################################################
	/**
	 * 订单状态发生变化之后需要及时跟新订单数据（type = 4 and last_time>0, last_time asc）
	 * 主要作用：及时在订单状态发生变化时更新订单数据
	 * @author dzt 2015-06-24
	 */
	function actionGetOrderOld(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."UPDATEORDER";
		SaasDhgateAutoFetchApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_updateorder jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_updateorder jobid=$cronJobId start");
		do{
			$rtn = SaasDhgateAutoFetchApiHelper::getOrderDetail(QueueDhgateGetorder::UPDATEORDER,'last_time');
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo "dhgate_get_order_updateorder jobid=$cronJobId sleep10 \n";
				\Yii::info("dhgate_get_order_updateorder jobid=$cronJobId sleep10");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo "dhgate_get_order_updateorder jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_updateorder jobid=$cronJobId end");
	}
###################################################################################################################
	/**
	 * 常规订单数据更新,同一个订单间隔半小时（type = 5 last_time asc）
	 * 主要作用：同步所有订单数据
	 * @author dzt 2015-06-24
	 */
	function actionGetOrderHalf(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."NOFINISH";
		SaasDhgateAutoFetchApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_nofinish_half jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_nofinish_half jobid=$cronJobId start");
		do{
			$rtn = SaasDhgateAutoFetchApiHelper::getOrderDetail(QueueDhgateGetorder::NOFINISH,'last_time',1800);
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo "dhgate_get_order_nofinish_half jobid=$cronJobId sleep10 \n";
				\Yii::info("dhgate_get_order_nofinish_half jobid=$cronJobId sleep10");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo "dhgate_get_order_nofinish_half jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_nofinish_half jobid=$cronJobId end");
	}
###################################################################################################################
	/**
	 * 常规订单数据更新,同一个订单间隔一天（type =5,last_time asc）
	 * 主要作用：同步所有订单数据
	 * @author dzt 2015-06-24
	 */
	function actionGetOrderDay(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."NOFINISH";
		SaasDhgateAutoFetchApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_nofinish jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_nofinish jobid=$cronJobId start");
		do{
			$rtn = SaasDhgateAutoFetchApiHelper::getOrderDetail(QueueDhgateGetorder::NOFINISH,'last_time',86400);
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo "dhgate_get_order_nofinish jobid=$cronJobId sleep10 \n";
				\Yii::info("dhgate_get_order_nofinish jobid=$cronJobId sleep10");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo "dhgate_get_order_nofinish jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_nofinish jobid=$cronJobId end");
	}
	
###################################################################################################################
	/**
	 * 常规订单数据更新,同一个订单间隔2小时（type = 5,last_time asc）
	 * 主要作用：同步所有订单数据
	 * @author dzt 2015-06-24
	 * ./yii dhgate/get-order-two-hours
	 */
	function actionGetOrderTwoHours(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."NOFINISH";
		SaasDhgateAutoFetchApiHelper::setCronJobId($cronJobId);
		echo "dhgate_get_order_nofinish jobid=$cronJobId start \n";
		\Yii::info("dhgate_get_order_nofinish jobid=$cronJobId start");
		do{
		$rtn = SaasDhgateAutoFetchApiHelper::getOrderDetail(QueueDhgateGetorder::NOFINISH,'last_time',7200);
		//如果没有需要handle的request了，sleep 10s后再试
		if ($rtn===false){
		echo "dhgate_get_order_nofinish jobid=$cronJobId sleep10 \n";
		\Yii::info("dhgate_get_order_nofinish jobid=$cronJobId sleep10");
		sleep(10);
		}
		}while (time() < $startRunTime+3600);
		echo "dhgate_get_order_nofinish jobid=$cronJobId end \n";
		\Yii::info("dhgate_get_order_nofinish jobid=$cronJobId end");
	}
}