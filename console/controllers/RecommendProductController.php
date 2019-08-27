<?php
 
namespace console\controllers;
 
use yii;
use yii\console\Controller;
use \eagle\modules\tracking\helpers\TrackingAgentHelper;
use \eagle\modules\tracking\helpers\TrackingHelper;
use \eagle\modules\util\helpers\ImageHelper;
use eagle\models\AliexpressChildorderlist;
use eagle\modules\tracking\models\TrackerRecommendProduct;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\tracking\helpers\TrackingRecommendProductHelper;
use eagle\modules\listing\apihelpers\ListingAliexpressApiHelper;
use eagle\modules\app\apihelpers\AppApiHelper;
/**
 * Test controller
 */
class RecommendProductController extends Controller {
	
 
	/**
	 * ./yii recommend-product/generate-recommend-products-update
	 */
	public function actionGenerateRecommendProductsUpdate(){
		//	echo "cronJobId:$cronJobId,cron service runnning for actionAutoFetchOrderListHeader at $start_time \n";
		$seed = rand(0,99999);
		$cronJobId = "RPFT".$seed;
		TrackingRecommendProductHelper::setCronJobId($cronJobId);
		
		$type="Update";
		\Yii::info("generate_tracker_recommend_products_$type afoi_jobid=$cronJobId start","file");
		
		TrackingRecommendProductHelper::generateTrackerRecommendProducts($type);
		
		//$keepRunningMins=30+ rand(1,10);// 分钟为单位,为了多进程同时推出而导致不能提供服务，这里加了个随机数。
		//$keepRunningMins=1;
		\Yii::info("generate_tracker_recommend_products_$type afoi_jobid=$cronJobId end","file");	}

	
	//./yii recommend-product/generate-recommend-products-firsttime
	// 对于刚刚开启了推荐商品功能的用户，应该需要更高的优先级，所以开了单独的进程服务这些用户。 目前是单进程，后面根据需要可以开启多进程
	public static function actionGenerateRecommendProductsFirsttime(){
		//	echo "cronJobId:$cronJobId,cron service runnning for actionAutoFetchOrderListHeader at $start_time \n";
		$seed = rand(0,99999);
		$cronJobId = "RPFT".$seed;
		TrackingRecommendProductHelper::setCronJobId($cronJobId);
		
		$type="FirstTime";
		\Yii::info("generate_tracker_recommend_products_$type afoi_jobid=$cronJobId start","file");
		
		TrackingRecommendProductHelper::generateTrackerRecommendProducts($type);
		
		//$keepRunningMins=30+ rand(1,10);// 分钟为单位,为了多进程同时推出而导致不能提供服务，这里加了个随机数。
		//$keepRunningMins=1;
		\Yii::info("generate_tracker_recommend_products_$type afoi_jobid=$cronJobId end","file");
	}	
	 
	    
}