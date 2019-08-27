<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use \eagle\modules\purchase\helpers\PurchaseHelper;
use \eagle\modules\tracking\helpers\TrackingHelper;
use \eagle\modules\util\helpers\ImageHelper;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;
use eagle\modules\amazon\apihelpers\AmazonSyncFetchOrderApiHelper;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use console\helpers\AmazonHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use prodaffiliate\helpers\AmazonListingHelper;
use eagle\modules\amazoncs\helpers\AmazoncsHelper;
/**
 * Test controller
 */
class AmazonController extends Controller {
	
	//后台crontab触发， amazon的订单拉取 
	//信息只有记录到临时表，  获取items的后台程序才会把订单信息传递到eagle中！！！！
	//已废弃，新逻辑在amazonv2Controller，拆分成多个进程拉取
	// ./yii amazon/auto-fetch-order-list-header
	// public function actionAutoFetchOrderListHeader(){
	// 	$start_time = date('Y-m-d H:i:s');
	// 	$startRunTime=time();
	// //	echo "cronJobId:$cronJobId,cron service runnning for actionAutoFetchOrderListHeader at $start_time \n";
	// 	$seed = rand(0,99999);
	// 	$cronJobId = "AF".$seed."A";				
	// 	AmazonSyncFetchOrderApiHelper::setCronJobId($cronJobId);
	// 	//$keepRunningMins=30+ rand(1,10);// 分钟为单位,为了多进程同时推出而导致不能提供服务，这里加了个随机数。
	// 	$keepRunningMins=1;
	// 	\Yii::info("amazon_fetch_order_list afol_jobid=$cronJobId start","file");
		
	// //	do{
	// 		$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);
			 
	// 		$rtn = AmazonSyncFetchOrderApiHelper::cronAutoFetchOrderList();
			
	// 		//如果没有需要handle的request了，sleep 4s后再试
	// 		if ($rtn===false){
	// 		   \Yii::info("amazon_fetch_order_list afol_jobid=$cronJobId sleep4");
	// 		   sleep(4);
	// 		}

	// 	    $nowTime=time();
	// 		$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
	// 		\Yii::info("-----------amazon_fetch_order_list end afol_jobid=$cronJobId,tt=".($start2_time-$start1_time),"file");
	// 	//	break;
	// //	}while (($startRunTime+60*$keepRunningMins < $nowTime)); //如果运行了超过30分钟，退出
	// 	//submit 使用的external call 统计数
	// 	//TrackingAgentHelper::extCallSum('',0,true);
	// }
	
	//后台crontab触发， amazon的订单items拉取
	// 这里才会把订单信息传递到eagle中！！！！
    //已废弃，新逻辑在amazonv2Controller，拆分成多个进程拉取
	// public static function actionAutoFetchOrderItems(){
	// 	// yii amazon/auto-fetch-order-items
		
	// 	//	echo "cronJobId:$cronJobId,cron service runnning for actionAutoFetchOrderListHeader at $start_time \n";
	// 	$startRunTime=time();
	// 	$keepRunningMins=30+rand(1,10);// 分钟为单位,为了多进程同时推出而导致不能提供服务，这里加了个随机数。
		
	// 	$seed = rand(0,99999);
	// 	$cronJobId = "AFI".$seed."A";
	// 	do{
	// 		AmazonSyncFetchOrderApiHelper::setCronJobId($cronJobId);
	// 		\Yii::info("amazon_fetch_order_items afoi_jobid=$cronJobId start","file");
	// 		$rtn =AmazonSyncFetchOrderApiHelper::cronAutoFetchOrderItems();
	// 		//如果没有需要handle的request了，sleep 10s后再试
	// 		if ($rtn===false){
	// 			\Yii::info("amazon_fetch_order_items afol_jobid=$cronJobId sleep10","file");
	// 			sleep(10);
	// 		}
				
	// 		$nowTime=time();
	// 	}while (($startRunTime+60*$keepRunningMins < $nowTime)); //如果运行了超过$keepRunningMins分钟，就退出
	// }

	// eagle订单状态(设置已发货，取消等等)submit to amazon.
	// ./yii amazon/cron-submit-amazon-order
	function actionCronSubmitAmazonOrder() {
		SaasAmazonAutoSyncApiHelper::cronAutoOrderSubmit();
	}
	
	// eagle订单状态(批量设置已发货，取消等等)submit to amazon.
	// ./yii amazon/cron-batch-submit-amazon-order
	function actionCronBatchSubmitAmazonOrder() {
		SaasAmazonAutoSyncApiHelper::cronBatchAutoOrderSubmit();
	}
	
	// 检查之前提交到eagle的submit job的执行情况
	// ./yii amazon/cron-check-amazon-submit
	function actionCronCheckAmazonSubmit() {
		SaasAmazonAutoSyncApiHelper::cronCheckAutoOrderSubmit ();
	}
	
	// 检查之前提交到eagle的submit job的执行情况
	// ./yii amazon/cron-batch-check-amazon-submit
	function actionCronBatchCheckAmazonSubmit() {
		SaasAmazonAutoSyncApiHelper::cronBatchCheckAutoOrderSubmit ();
	}
	
	// 解绑并删除账号相关数据
	// ./yii amazon/unbind
	function actionUnbind() {
		$merchandId = "";
// 		$merchandId = "A2EUNMH9TUNV33";// puid 1974
		list($ret,$message) = AmazonApiHelper::unbind($merchandId);
		if($ret == false){
			echo $message;
		}else{
			echo "sunccess";
		}
	}
	
	/**
	 * 监控表-amazon_temp_orderid_queue 中的status状态,超出设置时间还是1的情况,或者statu为4的情况就需要发邮件报警了
	 * last_time在当前检测时间的两小时后
	 * @author dzt
	 *
	 */
	//./yii amazon/send-warning-mail-1
	function actionSendWarningMail1()
	{
		$return = AmazonApiHelper::warnGetOrderItemAbnormalStatus1(time()-7200);
	}
	
	/**
	 * 监控表-amazon_temp_orderid_queue 中的status状态,出现大量status 为 0或3的时候或者 是大量status为1的时候发邮件报警了
	 * last_time在当前检测时间的两小时后
	 * @author dzt
	 *
	 */
	//./yii amazon/send-warning-mail-2
	function actionSendWarningMail2()
	{
		$return = AmazonApiHelper::warnGetOrderItemAbnormalStatus2();
	
	}
	
	
	/**
	 * 定期对所有符合条件的amazon绑定账号发起FBA库存report请求
	 * @invoking					./yii amazon/cron-request-fba-inventory-report
	 **/
	public function actionCronRequestFbaInventoryReport(){
		echo "background service runnning for amazon/cron-request-fba-inventory-report at ". date('Y-m-d H:i:s');
		
		$rtn = AmazonApiHelper::cronRequestAmazonReport('_GET_AFN_INVENTORY_DATA_');
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/cron-request-fba-inventory-report at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		TrackingAgentHelper::extCallSum('',0,true);
	}
	
	/**
	 * 获取report request 的 report id
	 * @invoking					./yii amazon/cron-get-request-report-id
	 **/
	public function actionCronGetRequestReportId(){
		echo "background service runnning for amazon/cron-get-request-report-id at ". date('Y-m-d H:i:s');
		
		$try_times = 0;//单次执行次数，大于100就停一下
		do{
			$rtn = AmazonApiHelper::cronGetAmazonRequestReportId();
			$try_times ++;
		}while ($try_times<100 && $rtn!=='N/A');
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/cron-get-request-report-id at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		TrackingAgentHelper::extCallSum('',0,true);
	}
	
	/**
	 * 根据repuest获得的report id,读取report的具体内容
	 * @invoking					./yii amazon/cron-get-report-data
	 **/
	public function actionCronGetReportData(){
		echo "background service runnning for amazon/cron-get-report-data at ". date('Y-m-d H:i:s');
		
		$try_times = 0;//单次执行次数，大于100就停一下
		do{
			$rtn = AmazonApiHelper::crongetAmazonReportContent();
			$try_times ++;
		}while ($try_times<100 && $rtn!=='N/A');
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/cron-get-report-data at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		TrackingAgentHelper::extCallSum('',0,true);
	}
	
	/**
	 * 定期对所有符合条件的amazon绑定账号发起 active listing产品拉取 report请求
	 * @invoking					./yii amazon/cron-request-active-listings-report
	 **/
	public function actionCronRequestActiveListingsReport(){
	    echo "background service runnning for amazon/cron-request-active-listings-report at ". date('Y-m-d H:i:s');
		//网红拉取商品，每个账号只拉一次
	    $rtn = AmazonApiHelper::cronRequestAmazonReport('_GET_MERCHANT_LISTINGS_DATA_');
	    
	    //write the memery used into it as well.
	    $memUsed = floor (memory_get_usage() / 1024 / 1024);
	    $comment =  "\n background service stops for amazon/cron-request-active-listings-report at ".date('Y-m-d H:i:s');
	    $comment .= " - RAM Used: ".$memUsed."M";
	    echo $comment;
	    //submit 使用的external call 统计数
	    TrackingAgentHelper::extCallSum('',0,true);
	}
		
	/**
	 * 对active listing产品拉取产品详情
	 * @invoking					./yii amazon/cron-get-listings-detail
	 **/
	public function actionCronGetListingsDetail(){
		echo "background service runnning for amazon/cron-get-listings-detail at ". date('Y-m-d H:i:s');
		$startRunTime = time();
	
		do{
			$rtn = AmazonApiHelper::cronGetListingsDetail();
	
			if ($rtn===false){
				echo "amazon/cron-get-listings-detail sleep10 \n";
				\Yii::info("amazon/cron-get-listings-detail sleep10");
				sleep(10);
			}
		}while (time() < $startRunTime+3600);
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/cron-get-listings-detail at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		TrackingAgentHelper::extCallSum('',0,true);
	}
	
	/**
	 * 对active listing产品拉取产品详情
	 * @invoking					./yii amazon/cron-get-prod-cat-info
	 **/
	public function actionCronGetProdCatInfo(){
	    echo "background service runnning for amazon/cron-get-prod-cat-info at ". date('Y-m-d H:i:s');
	    $startRunTime = time();
	
	    do{
	        $rtn = AmazonApiHelper::conGetAmzProdCatInfo();
	
	        if ($rtn===false){
	            echo "amazon/cron-get-prod-cat-info sleep10 \n";
	            \Yii::info("amazon/cron-get-prod-cat-info sleep10");
	            sleep(10);
	        }
	    }while (time() < $startRunTime+3600);
	
	    //write the memery used into it as well.
	    $memUsed = floor (memory_get_usage() / 1024 / 1024);
	    $comment =  "\n background service stops for amazon/cron-get-prod-cat-info at ".date('Y-m-d H:i:s');
	    $comment .= " - RAM Used: ".$memUsed."M";
	    echo $comment;
	    //submit 使用的external call 统计数
	    TrackingAgentHelper::extCallSum('',0,true);
	}
	
	
	/**
	 * 拉取完信息的listing 搬到各个站点的表里面
	 * @invoking					./yii amazon/cron-move-temp-listing
	 **/
	public function actionCronMoveTempListing(){
	    echo "background service runnning for amazon/cron-move-temp-listing at ". date('Y-m-d H:i:s');
	    $startRunTime = time();
	
	    do{
	        $rtn = AmazonApiHelper::cronMoveTempListingToTempListingSite();
	
	        if ($rtn===false){
	            echo "amazon/amazon/cron-move-temp-listing sleep10 \n";
	            \Yii::info("amazon/amazon/cron-move-temp-listing sleep10");
	            sleep(10);
	        }
	    }while (time() < $startRunTime+3600);
	
	    //write the memery used into it as well.
	    $memUsed = floor (memory_get_usage() / 1024 / 1024);
	    $comment =  "\n background service stops for amazon/cron-move-temp-listing at ".date('Y-m-d H:i:s');
	    $comment .= " - RAM Used: ".$memUsed."M";
	    echo $comment;
	    //submit 使用的external call 统计数
	    TrackingAgentHelper::extCallSum('',0,true);
	}
	
	/**
	 * 拉取完信息的listing 搬到各个站点的表里面
	 * @invoking					./yii amazon/gen-showing-cat
	 **/
	public function actionGenShowingCat(){
	    echo "background service runnning for amazon/gen-showing-cat at ". date('Y-m-d H:i:s');
	    $startRunTime = time();
	
	    foreach (AmazonListingHelper::$marketplaceList as $maketplace){
	        $rtn = AmazonListingHelper::genAmazonShowingCat($maketplace);
	    }

	    //write the memery used into it as well.
	    $memUsed = floor (memory_get_usage() / 1024 / 1024);
	    $comment =  "\n background service stops for amazon/gen-showing-cat at ".date('Y-m-d H:i:s');
	    $comment .= " - RAM Used: ".$memUsed."M";
	    echo $comment;
	    //submit 使用的external call 统计数
	    TrackingAgentHelper::extCallSum('',0,true);
	}
	
	/**
	 * 拉取完信息的listing 搬到各个站点的表里面
	 * @invoking					./yii amazon/auto-generate-amz-cs-template-quest
	 **/
	public function actionAutoGenerateAmzCsTemplateQuest(){
		echo "background service runnning for amazon/auto-generate-amz-cs-template-quest at ". date('Y-m-d H:i:s');
		
		AmazoncsHelper::cronAutoGenerateAmzCsTemplateQuest();
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/auto-generate-amz-cs-template-quest at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		TrackingAgentHelper::extCallSum('',0,true);
	}
	

	###############################    用户 amazon listing 拉取 start // lzhl 2017-07    #######################################
	/**
	 * 对所有申请同步listing的amazon绑定账号发起 active listing产品拉取 report请求
	 * @invoking					./yii amazon/manual-request-active-listings-report
	 **/
	public function actionManualRequestActiveListingsReport(){
		echo "background service runnning for amazon/manual-request-active-listings-report at ". date('Y-m-d H:i:s');
		//用户手动申请拉取，可能用于amazon商品搬家或者其他app，可以重复拉取
		$startRunTime = time();
		
		do{
			$rtn = AmazonApiHelper::cronRequestAmazonReport('_GET_MERCHANT_LISTINGS_DATA_',$app='sync_listing');
			sleep(10);
		}while (time() < $startRunTime+1800);
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/manual-request-active-listings-report at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		TrackingAgentHelper::extCallSum('',0,true);
	}
	
	/**
	 * 对active listing产品拉取产品详情
	 * 区别于actionCronGetListingsDetail，这个是为用户做的，原理一样，但queue来源和获取到的数据保存的表不一样
	 * @invoking					./yii amazon/cron-get-listings-detail-for-user
	 **/
	public function actionCronGetListingsDetailForUser(){
		echo "background service runnning for amazon/cron-get-listings-detail-for-user at ". date('Y-m-d H:i:s');
		$startRunTime = time();
	
		do{
			$rtn = AmazonApiHelper::cronGetListingsDetailForUser();
	
			if ($rtn===false){
				echo "amazon/cron-get-listings-detail-for-user sleep 10 \n";
				\Yii::info("amazon/cron-get-listings-detail-for-user sleep 10");
			}
			sleep(10);
		}while (time() < $startRunTime+1800);
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/cron-get-listings-detail at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		//TrackingAgentHelper::extCallSum('',0,true);
	}
	
	/**
	 * 对已经获取了原始图片url的listing产品，下载图片到本地
	 * @invoking					./yii amazon/cron-download-listings-images
	 **/
	public function actionCronDownloadListingsImages(){
		echo "background service runnning for amazon/cron-download-listings-images at ". date('Y-m-d H:i:s');
		$startRunTime = time();
	
		do{
			$rtn = AmazonApiHelper::downloadAmzListingImages();
	
			if ($rtn===false){
				echo "amazon/amazon/cron-download-listings-images sleep 10 \n";
				\Yii::info("amazon/cron-download-listings-images sleep 10");
			}
			sleep(10);
		}while (time() < $startRunTime+1800);
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/cron-download-listings-images at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		//TrackingAgentHelper::extCallSum('',0,true);
	}
	
	/**
	 * 对已经下载了原始图片到本地的listing产品，上传到阿里服务器
	 * @invoking					./yii amazon/cron-upload-listings-images-to-ali-oss
	 **/
	public function actionCronUploadListingsImagesToAliOss(){
		echo "background service runnning for amazon/cron-download-listings-images at ". date('Y-m-d H:i:s');
		$startRunTime = time();
	
		do{
			$rtn = AmazonApiHelper::amazonListingFetchAddiInfoQueueCallBackHandler('upload_images_to_AliOss');
	
			if ($rtn===false){
				echo "amazon/amazon/cron-download-listings-images sleep 10 \n";
				\Yii::info("amazon/cron-download-listings-images sleep 10");
			}
			sleep(10);
		}while (time() < $startRunTime+1800);
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/cron-download-listings-images at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		//TrackingAgentHelper::extCallSum('',0,true);
	}
	
	
	/**
	 * 已经废弃
	 * 对用户的active listing产品拉取产品图片url,这些url需要等待进一步处理
	 * @invoking					./yii amazon/cron-get-listing-src-images-to-user
	 **/
	public function actionCronGetListingSrcImagesToUser(){
		echo "\n background service runnning for  amazon/cron-get-listing-cat-info-to-user-db at ". date('Y-m-d H:i:s');
		$startRunTime = time();
	
		do{
			$rtn = AmazonApiHelper::cronGetListingsImagesToUser();
			sleep(10);
		}while (time() < $startRunTime+1800);
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n background service stops for amazon/cron-get-listing-cat-info-to-user-db at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		//submit 使用的external call 统计数
		TrackingAgentHelper::extCallSum('',0,true);
	}
	
	
	###############################    用户 amazon listing 拉取 end //lzhl 2017-07    #######################################
}