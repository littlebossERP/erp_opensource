<?php
namespace console\controllers;

use yii\console\Controller;
use \eagle\modules\purchase\helpers\PurchaseHelper;
use \eagle\modules\tracking\helpers\TrackingHelper;
use \eagle\modules\util\helpers\ImageHelper;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;
use eagle\modules\amazon\apihelpers\AmazonSyncFetchOrderV2ApiHelper;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\models\SaasAmazonUser;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\models\SaasAmazonAutosyncV2;

//use eagle\models\SaasAmazonAutosyncV2;
class Amazonv2Controller extends Controller {
	/**
	 * [actionAutoFetchOrderListHeaderOne fetch amzOldUnshippedAll list header]
	 * @Author   willage
	 * @DateTime 2016-07-26T15:10:19+0800
	 * @return   [type]                   [description]
	 * @command	./yii amazonv2/auto-fetch-order-list-header-one
	 */
	public function actionAutoFetchOrderListHeaderOne(){
		echo __FUNCTION__." start\n";
		/**
		 * [$cronJobId 设置job号]
		 */
		$startRunTime=time();
		$keepRunningMins=30+rand(1,10);//分钟为单位,
		$seed = rand(0,99999);
		$cronJobId = "AF".$seed."HEADERONE";
		AmazonSyncFetchOrderV2ApiHelper::setCronJobId($cronJobId);
		\Yii::info(__FUNCTION__."start afol_jobid=$cronJobId","file");
		/**
		 * [$rtn 拉取订单header]
		 */
		$rtn = false;
		do {
			$tmptime = explode(" ",microtime());
			$starttime = round($tmptime[0]*1000+$tmptime[1]*1000);
			$rtn = AmazonSyncFetchOrderV2ApiHelper::fetchOrderList("amzOldUnshippedAll");

			//如果没有需要handle的request了,sleep 4s后再试
			if ($rtn===false){
			   \Yii::info(__FUNCTION__." afol_jobid=$cronJobId sleep4","file");
			   sleep(4);
			}
			$tmptime = explode(" ",microtime());
			$stoptime = round($tmptime[0]*1000+$tmptime[1]*1000);
			\Yii::info(__FUNCTION__." end afol_jobid=$cronJobId,tt=".($stoptime-$starttime),"file");
			$nowTime=time();
		}while (($startRunTime+60*$keepRunningMins > $nowTime));

	}

	/**
	 * [actionAutoFetchOrderListHeaderTwo fetch amzNewNotFba list header]
	 * @Author   willage
	 * @DateTime 2016-08-12T09:47:51+0800
	 * @return   [type]                   [description]
	 * @command	./yii amazonv2/auto-fetch-order-list-header-two
	 */
	public function actionAutoFetchOrderListHeaderTwo(){
		echo __FUNCTION__." start\n";
		/**
		 * [$cronJobId 设置job号]
		 */
		$startRunTime=time();
		$keepRunningMins=30+rand(1,10);//分钟为单位,
		$seed = rand(0,99999);
		$cronJobId = "AF".$seed."HEADERTWO";
		AmazonSyncFetchOrderV2ApiHelper::setCronJobId($cronJobId);
		\Yii::info(__FUNCTION__."start afol_jobid=$cronJobId","file");
		/**
		 * [$rtn 拉取订单header]
		 */
		$rtn = false;
		do {
			$tmptime = explode(" ",microtime());
			$starttime = round($tmptime[0]*1000+$tmptime[1]*1000);
			$rtn = AmazonSyncFetchOrderV2ApiHelper::fetchOrderList("amzNewNotFba");

			//如果没有需要handle的request了,sleep 4s后再试
			if ($rtn===false){
			   \Yii::info(__FUNCTION__." afol_jobid=$cronJobId sleep4","file");
			   sleep(4);
			}
			$tmptime = explode(" ",microtime());
			$stoptime = round($tmptime[0]*1000+$tmptime[1]*1000);
			\Yii::info(__FUNCTION__." end afol_jobid=$cronJobId,tt=".($stoptime-$starttime),"file");
			$nowTime=time();
		}while (($startRunTime+60*$keepRunningMins > $nowTime));

	}
	/**
	 * [actionAutoFetchOrderListHeaderThree fetch amzNewFba list header]
	 * @Author   willage
	 * @DateTime 2016-08-12T09:58:01+0800
	 * @return   [type]                   [description]
	 * @command	./yii amazonv2/auto-fetch-order-list-header-three
	 */
	public function actionAutoFetchOrderListHeaderThree(){
		// /**
		//  * [$cronJobId 设置job号]
		//  * @var string
		//  */
		// $seed = rand(0,99999);
		// $cronJobId = "AF".$seed."A";
		// AmazonSyncFetchOrderV2ApiHelper::setCronJobId($cronJobId);
		// /**
		//  * [$rtn 拉取订单header]
		//  * @var boolean
		//  */
		// $rtn = false;
		// $rtn = AmazonSyncFetchOrderV2ApiHelper::fetchOrderList("amzNewFba");
		// $tmptime = explode(" ",microtime());
		// $starttime = round($tmptime[0]*1000+$tmptime[1]*1000);
		// //如果没有需要handle的request了,sleep 4s后再试
		// if ($rtn===false){
		//    \Yii::info("amazon_fetch_order_list afol_jobid=$cronJobId sleep4");
		//    sleep(4);
		// }
		// $tmptime = explode(" ",microtime());
		// $stoptime = round($tmptime[0]*1000+$tmptime[1]*1000);

		// \Yii::info("-----------amazon_fetch_order_list end afol_jobid=$cronJobId,tt=".($stoptime-$starttime),"file");

		echo __FUNCTION__." start\n";
		/**
		 * [$cronJobId 设置job号]
		 */
		$startRunTime=time();
		$keepRunningMins=30+rand(1,10);//分钟为单位,
		$seed = rand(0,99999);
		$cronJobId = "AF".$seed."HEADERTHREE";
		AmazonSyncFetchOrderV2ApiHelper::setCronJobId($cronJobId);
		\Yii::info(__FUNCTION__."start afol_jobid=$cronJobId","file");
		/**
		 * [$rtn 拉取订单header]
		 */
		$rtn = false;
		do {
			$tmptime = explode(" ",microtime());
			$starttime = round($tmptime[0]*1000+$tmptime[1]*1000);
			$rtn = AmazonSyncFetchOrderV2ApiHelper::fetchOrderList("amzNewFba");

			//如果没有需要handle的request了,sleep 4s后再试
			if ($rtn===false){
			   \Yii::info(__FUNCTION__." afol_jobid=$cronJobId sleep4\n","file");
			   echo __FUNCTION__." afol_jobid=$cronJobId sleep4\n";
			   sleep(4);
			}
			$tmptime = explode(" ",microtime());
			$stoptime = round($tmptime[0]*1000+$tmptime[1]*1000);
			\Yii::info(__FUNCTION__." end afol_jobid=$cronJobId,tt=".($stoptime-$starttime),"file");
			$nowTime=time();
		}while (($startRunTime+60*$keepRunningMins > $nowTime));
	}
	/**
	 * [actionAutoFetchOrderListHeaderFour fetch amzOldNotFbaNotUnshipped list header]
	 * @Author   willage
	 * @DateTime 2016-08-12T10:25:30+0800
	 * @return   [type]                   [description]
	 * @command	./yii amazonv2/auto-fetch-order-list-header-four
	 */
	public function actionAutoFetchOrderListHeaderFour(){
		echo __FUNCTION__." start\n";
		/**
		 * [$cronJobId 设置job号]
		 */
		$startRunTime=time();
		$keepRunningMins=30+rand(1,10);//分钟为单位,
		$seed = rand(0,99999);
		$cronJobId = "AF".$seed."HEADERFOUR";
		AmazonSyncFetchOrderV2ApiHelper::setCronJobId($cronJobId);
		\Yii::info(__FUNCTION__."start afol_jobid=$cronJobId","file");
		/**
		 * [$rtn 拉取订单header]
		 */
		$rtn = false;
		do {
			$tmptime = explode(" ",microtime());
			$starttime = round($tmptime[0]*1000+$tmptime[1]*1000);
			$rtn = AmazonSyncFetchOrderV2ApiHelper::fetchOrderList("amzOldNotFbaNotUnshipped");

			//如果没有需要handle的request了,sleep 4s后再试
			if ($rtn===false){
			   \Yii::info(__FUNCTION__." afol_jobid=$cronJobId sleep4\n","file");
			   echo __FUNCTION__." afol_jobid=$cronJobId sleep4\n";
			   sleep(4);
			}
			$tmptime = explode(" ",microtime());
			$stoptime = round($tmptime[0]*1000+$tmptime[1]*1000);
			\Yii::info(__FUNCTION__." end afol_jobid=$cronJobId,tt=".($stoptime-$starttime),"file");
			$nowTime=time();
		}while (($startRunTime+60*$keepRunningMins > $nowTime));
	}

	/**
	 * [actionAutoFetchOrderListHeaderFive fetch amzOldFbaNotUnshipped list header]
	 * @Author   willage
	 * @DateTime 2016-08-12T10:26:08+0800
	 * @return   [type]                   [description]
	 * @command	./yii amazonv2/auto-fetch-order-list-header-five
	 */
	public function actionAutoFetchOrderListHeaderFive(){
		echo __FUNCTION__." start\n";
		/**
		 * [$cronJobId 设置job号]
		 */
		$startRunTime=time();
		$keepRunningMins=30+rand(1,10);//分钟为单位,
		$seed = rand(0,99999);
		$cronJobId = "AF".$seed."HEADERFIVE";
		AmazonSyncFetchOrderV2ApiHelper::setCronJobId($cronJobId);
		\Yii::info(__FUNCTION__."start afol_jobid=$cronJobId","file");
		/**
		 * [$rtn 拉取订单header]
		 */
		$rtn = false;
		do {
			$tmptime = explode(" ",microtime());
			$starttime = round($tmptime[0]*1000+$tmptime[1]*1000);
			$rtn = AmazonSyncFetchOrderV2ApiHelper::fetchOrderList("amzOldFbaNotUnshipped");

			//如果没有需要handle的request了,sleep 4s后再试
			if ($rtn===false){
			   \Yii::info(__FUNCTION__." afol_jobid=$cronJobId sleep4\n","file");
			   echo __FUNCTION__." afol_jobid=$cronJobId sleep4\n";
			   sleep(4);
			}
			$tmptime = explode(" ",microtime());
			$stoptime = round($tmptime[0]*1000+$tmptime[1]*1000);
			\Yii::info(__FUNCTION__." end afol_jobid=$cronJobId,tt=".($stoptime-$starttime),"file");
			$nowTime=time();
		}while (($startRunTime+60*$keepRunningMins > $nowTime));
	}
	/**
	 * [actionAutoFetchOrderItemsHighpriority description]
	 * @Author   willage
	 * @DateTime 2016-08-08T14:28:01+0800
	 * @return   [type]                   [description]
	 * @command	./yii amazonv2/auto-fetch-order-items-highpriority
	 */
	public static function actionAutoFetchOrderItemsHighpriority(){
		echo __FUNCTION__." start\n";
		$startRunTime=time();
		$keepRunningMins=30+rand(1,10);//分钟为单位,为了多进程同时推出而导致不能提供服务，这里加了个随机数。
		$seed = rand(0,99999);
		$cronJobId = "AFI".$seed."ITEMSHIGHPRIORITY";
		AmazonSyncFetchOrderV2ApiHelper::setCronJobId($cronJobId);
		do{
			$rtn =AmazonSyncFetchOrderV2ApiHelper::fetchOrderItems("highpriority");
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				\Yii::info(__FUNCTION__." afol_jobid=$cronJobId sleep10\n","file");
				echo __FUNCTION__." afol_jobid=$cronJobId sleep10\n";
				sleep(10);
			}
			$nowTime=time();
		}while (($startRunTime+60*$keepRunningMins > $nowTime));//如果运行了超过$keepRunningMins分钟，就退出
	}

	/**
	 * [actionAutoFetchOrderItemsLowpriority description]
	 * @Author   willage
	 * @DateTime 2016-08-08T14:27:12+0800
	 * @return   [type]                   [description]
	 * @command	./yii amazonv2/auto-fetch-order-items-lowpriority
	 */
	public static function actionAutoFetchOrderItemsLowpriority(){
		echo __FUNCTION__." start\n";
		$startRunTime=time();
		$keepRunningMins=30+rand(1,10);//分钟为单位,为了多进程同时推出而导致不能提供服务，这里加了个随机数。
		$seed = rand(0,99999);
		$cronJobId = "AFI".$seed."ITEMSLOWPRIORITY";
		AmazonSyncFetchOrderV2ApiHelper::setCronJobId($cronJobId);
		do{
			$rtn =AmazonSyncFetchOrderV2ApiHelper::fetchOrderItems("lowpriority");
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn===false){
				\Yii::info(__FUNCTION__." afol_jobid=$cronJobId sleep10\n","file");
				echo __FUNCTION__." afol_jobid=$cronJobId sleep10\n";
				sleep(10);
			}
			$nowTime=time();
		}while (($startRunTime+60*$keepRunningMins > $nowTime));//如果运行了超过$keepRunningMins分钟，就退出
	}

	/**
	 * [actionConverToSaasamazonautosyncv2 description]
	 * @Author   willage
	 * @DateTime 2016-08-08T16:23:57+0800
	 * @return   [type]                   [description]
	 * 对于2天内绑定的用户,旧订单就重新拉取
	 * 对于2天外绑定的用户,旧订单就当已经拉取完毕
	 * ./yii amazonv2/conver-to-saasamazonautosyncv2
	 */
	public static function actionConverToSaasamazonautosyncv2(){
		/**
		 * [No.1 找到激活的用户]//应该做限制数量
		 */
		$limit_time=time()-2*24*3600;
		$SAU_objs = SaasAmazonUser::find()->where("is_active=1 or is_active=0")->all();
		echo("SaasAmazonUser : ".count($SAU_objs)."\n");
		foreach ($SAU_objs as  $SAU_obj_one) {
			/**
			 * [No.2 找到激活的用户对应站点]
			 */
			$SAMP_objs=SaasAmazonUserMarketplace::find()->where("amazon_uid=".$SAU_obj_one->amazon_uid)->all();
			echo("SaasAmazonUserMarketplace : ".count($SAMP_objs)."\n");
			/**
			 * [No.3 找到激活的用户对应站点]
			 */
			foreach ($SAMP_objs as $SAMP_objone) {
				//对于2天内绑定的用户,旧订单就重新拉取
				if ($SAMP_objone->create_time>$limit_time) {
					AmazonSyncFetchOrderV2ApiHelper::ConverToSaasAmazonAutosyncV2($SAMP_objone,$SAU_obj_one->merchant_id,false);
				}else{//对于2天外绑定的用户,旧订单就当已经拉取完毕
					AmazonSyncFetchOrderV2ApiHelper::ConverToSaasAmazonAutosyncV2($SAMP_objone,$SAU_obj_one->merchant_id,true);
				}
			}

		}

	}

/**
 * [actionAddFbainventoryTosyncv2 增加amzFbaInventory type]
 * @Author willage 2016-12-06T16:40:15+0800
 * @Editor willage 2016-12-06T16:40:15+0800
 * @return [type]  [description]
 * ./yii amazonv2/add-fbainventory-tosyncv2
 */
	public static function actionAddFbainventoryTosyncv2(){
		$SQL_my="select * from `saas_amazon_autosync_v2` where `status`!=2 and CONCAT(`platform_user_id`,`site_id`) not in (select CONCAT(`platform_user_id`,`site_id`) from `saas_amazon_autosync_v2` where `type` ='amzFbaInventory' ) group by `platform_user_id` , `site_id`";

		$amzSyncV2=SaasAmazonAutosyncV2::findBySql($SQL_my)->asArray()->all();
		// print_r($amzSyncV2,false);

		foreach ($amzSyncV2 as $valOne) {
			if ($valOne['status']=='amzFbaInventory') {
				echo $valOne['id']."\n";
				continue;
			}
			$newOne = new SaasAmazonAutosyncV2();
			$newOne->eagle_platform_user_id = $valOne['eagle_platform_user_id'];
			$newOne->platform_user_id = $valOne['platform_user_id'];
			$newOne->site_id = $valOne['site_id'];
			$newOne->status = $valOne['status'];
			$newOne->process_status = 0; //没同步
			$newOne->create_time = time() ;
			$newOne->update_time = time() ;
			$newOne->type = "amzFbaInventory";
			$newOne->execution_interval=43200+rand(0,200);
			if (!$newOne->save(false)){
				echo "save error\n";
				continue;//出现异常，请联系小老板的相关客服
			}
			echo "ok \n";
		}

	}
	/**
	 * [actionClearLowpriorityQueue 用于清除LowpriorityQueue完成后30天的记录]
	 * @author willage 2017-02-24T16:15:29+0800
	 * @editor willage 2017-02-24T16:15:29+0800
	 * @return [type]  [description]
	 * ./yii amazonv2/clear-amazon-lowpriority-queue
	 */
	public static function actionClearAmazonLowpriorityQueue(){
		try{
			$sql="DELETE FROM `amazon_temp_orderid_queue_lowpriority` WHERE `process_status`=2 and `update_time` <".(time()-2592000);
			echo "running sql:".$sql."\n";
			$connection=\Yii::$app->db_queue;
			$command=$connection->createCommand($sql);
			$affectRows = $command->execute();
			echo "affect rows:".$affectRows."\n";
		}catch (\Exception $ex){
			echo __FUNCTION__." running error\n";
		}

	}

	/**
	 * [actionClearLowpriorityQueue 用于清除HighpriorityQueue完成后30天的记录]
	 * @author willage 2017-02-24T16:15:29+0800
	 * @editor willage 2017-02-24T16:15:29+0800
	 * @return [type]  [description]
	 * ./yii amazonv2/clear-amazon-highpriority-queue
	 */
	public static function actionClearAmazonHighpriorityQueue(){
		try{
			$sql="DELETE FROM `amazon_temp_orderid_queue_highpriority` WHERE `process_status`=2 and `update_time`<".(time()-2592000);
			echo "running sql:".$sql."\n";
			$connection=\Yii::$app->db_queue;
			$command=$connection->createCommand($sql);
			$affectRows = $command->execute();
			echo "affect rows:".$affectRows."\n";
		}catch (\Exception $ex){
			echo __FUNCTION__." running error\n";
		}

	}
	/**
	 * [actionClearAmazonOrderSubmitQueue 每日清除order提交记录,14天前已完成]
	 * @author willage 2017-10-09T17:17:30+0800
	 * @update willage 2017-10-09T17:17:30+0800
	 * @return [type]
	 * ./yii amazonv2/clear-amazon-order-submit-queue
	 */
	public static function actionClearAmazonOrderSubmitQueue(){
		try{
			$time=time()-3600*24*14;
			$sql="DELETE FROM `amazon_order_submit_queue` WHERE `process_status`=5 and `update_time` < ".$time;
			echo "running sql:".$sql."\n";
			$connection=\Yii::$app->db;
			$command=$connection->createCommand($sql);
			$affectRows = $command->execute();
			echo "affect rows:".$affectRows."\n";
		}catch (\Exception $ex){
			echo __FUNCTION__." running error\n";
		}

	}
}
?>