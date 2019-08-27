<?php

namespace console\controllers;
use \Yii;
use yii\console\Controller;
use yii\base\Exception;
//use eagle\modules\dhgate\apihelpers\SaasDhgateAutoSyncApiHelper;
use eagle\models\QueueDhgateGetorder;
use eagle\models\QueueDhgatePendingorder;
use eagle\models\SaasDhgateAutosync;
//use eagle\modules\dhgate\apihelpers\SaasDhgateAutoFetchApiHelper;
use eagle\modules\dhgate\apihelpers\DhgateSyncFetchOrderApiHelper;
/**
 * DHgate 后台控制程序 v2
 * @Author   willage
 * @DateTime 2016-08-16T11:38:45+0800
 */
class Dhgatev2Controller extends Controller {
	/**
	 * [actionGetOrderListByDay120 拉取绑定时间点前120日未完成订单(除了已完成)]
	 * @Author   willage
	 * @DateTime 2016-08-16T11:38:45+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/get-order-list-by-day120
	 */
	public function actionGetOrderListByDay120(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."DAY120";
		DhgateSyncFetchOrderApiHelper::setCronJobId($cronJobId);
		echo __FUNCTION__." jobid=$cronJobId start \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
		do{
			$rtn = DhgateSyncFetchOrderApiHelper::fetchOrderList('day120');
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
				\Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
			   	sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo __FUNCTION__." jobid=$cronJobId end \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
	}
	/**
	 * [actionGetOrderListByFinish description]
	 * @Author   willage
	 * @DateTime 2016-08-17T17:45:53+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/get-order-list-by-finish
	 */
	public function actionGetOrderListByFinish(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."FINISH";
		DhgateSyncFetchOrderApiHelper::setCronJobId($cronJobId);
		echo __FUNCTION__." jobid=$cronJobId start \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
		do{
			$rtn = DhgateSyncFetchOrderApiHelper::fetchOrderList('finish');
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
				\Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);//3600
		echo __FUNCTION__." jobid=$cronJobId end \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
	}
	/**
	 * [actionGetOrderListByTime description]
	 * @Author   willage
	 * @DateTime 2016-08-17T18:06:55+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/get-order-list-by-time
	 */
	public function actionGetOrderListByTime(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."TIME";
		DhgateSyncFetchOrderApiHelper::setCronJobId($cronJobId);
		echo __FUNCTION__." jobid=$cronJobId start \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
		do{
			$rtn = DhgateSyncFetchOrderApiHelper::fetchOrderList('time');
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
				\Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo __FUNCTION__." jobid=$cronJobId end \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
	}
	/**
	 * [actionGetOrderDetailByDay120 description]
	 * @Author   willage
	 * @DateTime 2016-08-17T18:08:01+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/get-order-detail-by-day120
	 */
	public function actionGetOrderDetailByDay120(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."OLD_UNFININSHED";
		DhgateSyncFetchOrderApiHelper::setCronJobId($cronJobId);
		echo __FUNCTION__." jobid=$cronJobId start \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
		do{
			$rtn = DhgateSyncFetchOrderApiHelper::fetchOrderDetail(QueueDhgateGetorder::OLD_UNFININSHED);
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
				\Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);//3600
		echo __FUNCTION__." jobid=$cronJobId end \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
	}
	/**
	 * [actionGetOrderDetailByFinish description]
	 * @Author   willage
	 * @DateTime 2016-08-18T11:48:40+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/get-order-detail-by-finish
	 */
	public function actionGetOrderDetailByFinish(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."FINISH";
		DhgateSyncFetchOrderApiHelper::setCronJobId($cronJobId);
		echo __FUNCTION__." jobid=$cronJobId start \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
		do{
			$rtn = DhgateSyncFetchOrderApiHelper::fetchOrderDetail(QueueDhgateGetorder::OLD_FINISH);
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
				\Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo __FUNCTION__." jobid=$cronJobId end \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
	}
	/**
	 * [actionGetOrderDetailByTime description]
	 * @Author   willage
	 * @DateTime 2016-08-17T18:23:20+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/get-order-detail-by-time
	 */
	public function actionGetOrderDetailByTime(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."NEW_ORDER";
		DhgateSyncFetchOrderApiHelper::setCronJobId($cronJobId);
		echo __FUNCTION__." jobid=$cronJobId start \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
		do{
			$rtn = DhgateSyncFetchOrderApiHelper::fetchOrderDetail(QueueDhgateGetorder::NEW_ORDER);
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
				\Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo __FUNCTION__." jobid=$cronJobId end \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
	}
	/**
	 * [actionGetOrderDetailByDaily description]
	 * @Author   willage
	 * @DateTime 2016-08-17T18:22:41+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/get-order-detail-by-daily
	 */
	public function actionGetOrderDetailByDaily(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."DAILY";
		DhgateSyncFetchOrderApiHelper::setCronJobId($cronJobId);
		echo __FUNCTION__." jobid=$cronJobId start \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
		do{
			$rtn = DhgateSyncFetchOrderApiHelper::fetchOrderDetail(QueueDhgateGetorder::DAILY_QUERY);
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
				\Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo __FUNCTION__." jobid=$cronJobId end \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
	}//end

	/**
	 * [actionMovePendingorderqueueToGetorderqueue description]
	 * @Author   willage
	 * @DateTime 2016-08-18T11:39:15+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/move-pendingorderqueue-to-getorderqueue
	 */
	public function actionMovePendingorderqueueToGetorderqueue(){
		$startRunTime=time();
		$seed = rand(0,99999);
		$cronJobId = "DHGOL".$seed."MOVEQUEUE";
		DhgateSyncFetchOrderApiHelper::setCronJobId($cronJobId);
		echo __FUNCTION__." jobid=$cronJobId start \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
		do{
			$rtn = DhgateSyncFetchOrderApiHelper::moveQueue();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
				\Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
		echo __FUNCTION__." jobid=$cronJobId end \n";
		\Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
	}//end

	/**
	 * [actionMoveAutosyncToNew description]
	 * @Author   willage
	 * @DateTime 2016-08-23T17:11:10+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/move-autosync-to-new
	 */
	public function actionMoveAutosyncToNew(){
		echo __FUNCTION__."\n";
		/**
		 * No.1 修改day120 status=2为status=4
		 */
		$moveRows=SaasDhgateAutosync::updateAll(['status'=>4] , ['type'=>'day120','status'=>2]);
		echo __FUNCTION__." rows=".$moveRows."\n";
		\Yii::info(__FUNCTION__." moveRows=$moveRows end","file");


	}//end

	/**
	 * [actionMoveGetorderqueueToNew description]
	 * @Author   willage
	 * @DateTime 2016-08-23T18:08:45+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/move-getorderqueue-to-new
	 */
	public function actionMoveGetorderqueueToNew(){
		echo __FUNCTION__."\n";
		/**
		 * No.1 修改type=2为type=4
		 */
		$moveRows=QueueDhgateGetorder::updateAll(['type'=>4] , ['type'=>5]);
		echo __FUNCTION__." rows=".$moveRows."\n";
		\Yii::info(__FUNCTION__." moveRows=$moveRows end","file");
		/**
		 * No.2 如果is_active=0移到queuen_pendingorder
		 * TBD
		 */
	}//end

	/**
	 * [actionTest1 description]
	 * @Author   willage
	 * @DateTime 2016-08-23T18:08:45+0800
	 * @return   [type]                   [description]
	 * @command ./yii dhgatev2/test1
	 */
	public function actionTest1(){
		echo __FUNCTION__."\n";
		/**
		 * No.1 修改type=2为type=4
		 */
		$moveRows=QueueDhgatePendingorder::updateAll(['is_active'=>1] , ['is_active'=>0]);
		echo __FUNCTION__." rows=".$moveRows."\n";
		\Yii::info(__FUNCTION__." moveRows=$moveRows end","file");
		/**
		 * No.2 如果is_active=0移到queuen_pendingorder
		 * TBD
		 */
	}//end

}
?>