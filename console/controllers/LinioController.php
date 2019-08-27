<?php

namespace console\controllers;

use common\api\lazadainterface\LazadaInterface_Helper;
use common\models\DefaultInfo;
use common\models\DefaultInfoType;
use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use console\helpers\CommonHelper;
use eagle\models\SaasLazadaAutosync;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoFetchApiHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoFetchListingApiHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelperV2;
use eagle\modules\listing\helpers\LazadaAutoFetchListingHelper;
use eagle\modules\listing\helpers\LazadaAutoFetchListingHelperV2;
use eagle\modules\listing\helpers\LazadaFeedHelper;
use eagle\modules\listing\helpers\LazadaLinioJumiaProductFeedHelper;
use eagle\modules\listing\helpers\LazadaLinioJumiaProductFeedHelperV2;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\listing\models\LazadaPublishListing;
use eagle\modules\util\helpers\TimeUtil;
use MongoId;
use Qiniu\json_decode;
use yii;
use yii\console\Controller;

/**
 * 负责linio jumia的后台任务
 * dzt 2016/12/23 
 * 
 */
class LinioController extends Controller {
    
    private static $nativeVersion = 0;
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //+++++++++++++++++++++++++++++++++++++++++++++订单列表拉取++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    /**
     * 这里订单拉取分成3个进程
     * 1. 新订单的拉取。 用户绑定的时间点之后create的订单
     * 2. 新订单的创建之后，更新信息的拉取。 用户绑定的时间点之后update的订单
     * (由于新创建的订单，updatedafter的接口获取不了，所以这里需要进程1， 通过createdafter接口来获取)
     * 3. 旧订单的第一次拉取。绑定的时间点之前，n天之内create的订单。
     * 4. 旧订单的后续拉取。绑定的时间点之前，n天之外create的订单。
     * 5. 为防止漏单，每隔m小时拉  n天订单，若发现 订单不存在或者 订单 update time较大，则更新订单（m,n不时在调整）
     */
    //+++++++++++++++++++++++++++++++++++++++++++++订单列表拉取+++++++++++++++++++++++++++++++++++++++++
    
    //./yii linio/get-order-list-new-create
    // 新订单的拉取。 用户绑定的时间点之后create的订单
    public function actionGetOrderListNewCreate() {
        $startRunTime = time();
        $logIDStr = "linio_get_order_list_new_create";
        $seed = rand(0, 99999);
        $cronJobId = "LJAGOL" . $seed . "NC";
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start", "file");
        do{
            $rtn = SaasLazadaAutoFetchApiHelper::getOrderListNewCreate(array('linio','jumia'));
            // 如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "$logIDStr jobid=$cronJobId sleep10 \n";
                \Yii::info("$logIDStr jobid=$cronJobId sleep10");
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        echo "$logIDStr jobid=$cronJobId end \n";
        \Yii::info("$logIDStr jobid=$cronJobId end", "file");
    }
    
    //./yii linio/get-order-list-new-update
    // 新订单的创建之后，更新信息的拉取。 用户绑定的时间点之后update的订单
    public function actionGetOrderListNewUpdate() {
        $startRunTime = time();
        $logIDStr = "linio_get_order_list_new_update";
        $seed = rand(0, 99999);
        $cronJobId = "LJAGOL" . $seed . "NU";
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start", "file");
    	do{
    	    $rtn = SaasLazadaAutoFetchApiHelper::getOrderListNewUpdate(array('linio','jumia'));
            // 如果没有需要handle的request了，sleep 10s后再试
          	if ($rtn===false){
        	   	echo "$logIDStr jobid=$cronJobId sleep10 \n";
        	   	\Yii::info("$logIDStr jobid=$cronJobId sleep10");
        	   	sleep(10);
         	}
        }while (time() < $startRunTime+3600);
        
        echo "$logIDStr jobid=$cronJobId end \n";
        \Yii::info("$logIDStr jobid=$cronJobId end", "file");
    }
    
    //旧订单的第一次拉取。绑定的时间点之前，n天之内update的订单。
    // ./yii linio/get-order-list-old-first
    public function actionGetOrderListOldFirst() {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LJAGOL" . $seed . "TIME";
        $logIDStr = "linio_get_order_list_old_first";
    
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
    	do{
            $rtn = SaasLazadaAutoFetchApiHelper::getOrderListOldFirst(array('linio','jumia'));
            //如果没有需要handle的request了，sleep 10s后再试
          	if ($rtn===false){
        	   	echo "$logIDStr jobid=$cronJobId sleep10 \n";
        	   	\Yii::info("$logIDStr jobid=$cronJobId sleep10");
        	   	sleep(10);
         	}
        }while (time() < $startRunTime+3600);
        echo "$logIDStr jobid=$cronJobId end \n";
        \Yii::info("$logIDStr jobid=$cronJobId end");
    }
    
    //旧订单的第一次拉取。绑定的时间点之前，n天之外update的订单。
    // ./yii linio/get-order-list-old-second
    public function actionGetOrderListOldSecond() {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LJAGOL" . $seed . "TIME";
        $logIDStr = "linio_get_order_list_old_second";
    
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
    	do{
            $rtn = SaasLazadaAutoFetchApiHelper::getOrderListOldSecond(array('linio','jumia'));
            //如果没有需要handle的request了，sleep 10s后再试
          	if ($rtn===false){
            	echo "$logIDStr jobid=$cronJobId sleep10 \n";
            	\Yii::info("$logIDStr jobid=$cronJobId sleep10");
        	   	sleep(10);
         	}
        }while (time() < $startRunTime+3600);
        echo "$logIDStr jobid=$cronJobId end \n";
        \Yii::info("$logIDStr jobid=$cronJobId end");
    }
    
    
    // 拉取近两日订单
    // ./yii linio/get-order-list-by-day2
    public function actionGetOrderListByDay2() {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LJAGOL" . $seed . "TIME";
        $logIDStr = "linio_get_order_list_old_second";
    
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
        //	do{
        $rtn = SaasLazadaAutoFetchApiHelper::getOrderListByDay2(array('linio','jumia'));
        //如果没有需要handle的request了，sleep 10s后再试
        //	if ($rtn===false){
        //	echo "$logIDStr jobid=$cronJobId sleep10 \n";
        //	\Yii::info("$logIDStr jobid=$cronJobId sleep10");
        //		sleep(10);
        //  	}
        //}while (time() < $startRunTime+3600);
        echo "$logIDStr jobid=$cronJobId end \n";
        \Yii::info("$logIDStr jobid=$cronJobId end");
    }
    
    
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //++++++++++++++++++++++++++++++++++++++订单列表拉取 end +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    
    
    //后台crontab触发， linio的订单items拉取
    // 这里才会把订单信息传递到eagle中！！！！
    // ./yii linio/auto-fetch-order-items
    public function actionAutoFetchOrderItems() {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LJAGOI" . $seed . "TIME";
        $logIDStr = "linio_get_order_item";
        
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
        do{
            $rtn = SaasLazadaAutoFetchApiHelper::cronAutoFetchOrderItems(array('linio','jumia'));
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "$logIDStr jobid=$cronJobId sleep10 \n";
                \Yii::info("$logIDStr jobid=$cronJobId sleep10");
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        $endRunTime=date("Y-m-d H:i:s");
        echo "========cronJobId:$cronJobId,cron service runnning for actionAutoFetchOrderItems at $endRunTime \n";
        \Yii::info("========cronJobId:$cronJobId,cron service runnning for actionAutoFetchOrderItems at $endRunTime \n","file");
    }
    
    //后台crontab触发， linio的订单items拉取
    // ./yii linio/batch-auto-fetch-order-items
    public function actionBatchAutoFetchOrderItems() {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LJBAGOI" . $seed . "TIME";
        $logIDStr = "linio_batch_get_order_item";
    
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
        do{
            $rtn = SaasLazadaAutoFetchApiHelper::cronBatchAutoFetchOrderItems(array('linio','jumia'));
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "$logIDStr jobid=$cronJobId sleep10 \n";
                \Yii::info("$logIDStr jobid=$cronJobId sleep10");
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        $endRunTime=date("Y-m-d H:i:s");
        echo "========cronJobId:$cronJobId,cron service runnning for actionAutoFetchOrderItems at $endRunTime \n";
        \Yii::info("========cronJobId:$cronJobId,cron service runnning for actionAutoFetchOrderItems at $endRunTime \n","file");
    }
    
    // 自动上传照片
    // ./yii linio/auto-upload-images
    public static function actionAutoUploadImages() {
        // lazada 三兄弟共用一个 优雅退出key，所以第六个参数是lazada
        CommonHelper::startJob("eagle.modules.listing.helpers.LazadaLinioJumiaProductFeedHelper.ImageUpload", 
                10, "auto-upload-images", "1.0", self::$nativeVersion, "linio", array('linio','jumia'));
        
    }
    
    /**
     * 获取lazada在线商品---获取指定时间内所有账户的新增或者有修改的listing，第一次就是获取所有listing
     * ./yii linio/get-updated-listing
     */
    function actionGetUpdatedListing() {
        CommonHelper::startJob("eagle.modules.listing.helpers.LazadaAutoFetchListingHelperV2.getUpdatedisting",
                10, "get-updated-listing", "1.0", self::$nativeVersion, "linio", array('linio','jumia'));
        
    }
    
    /**
     * 检查所有由小老板提交的但没有完成的feed的status
     * ./yii linio/check-feed-status
     */
    function actionCheckFeedStatus()
    {
        CommonHelper::startJob("eagle.modules.listing.helpers.LazadaLinioJumiaProductFeedHelper.checkAllFeedStatus", 10, "check-feed-status", "1.0", self::$nativeVersion, "lazada");
    }
    
    // 更新所有站点目录树
    //./yii linio/refresh-all-site-category-tree
    public function actionRefreshAllSiteCategoryTree()
    {
        LazadaApiHelper::refreshCategoryTree();
    }
    
    // 更新目录属性
    //./yii linio/refresh-category-attrs
    public function actionRefreshCategoryAttrs()
    {
        LazadaApiHelper::refreshCategoryAttrs();
    }
    
    // 更新站点品牌
    //./yii linio/refresh-site-brands
    public function actionRefreshSiteBrands()
    {
        LazadaApiHelper::refreshBrands();
    }
    
    /**
     *  执行处理导入excel任务
     * ./yii linio/handle-import-job1
     */
    public function actionHandleImportJob1() {
//         CommonHelper::startJob("eagle.modules.listing.helpers.LazadaLinioJumiaProductFeedHelper.handleImportJob",
//                 10, "handle-import-job", "1.0", self::$nativeVersion, "linio");
        
        \eagle\modules\listing\helpers\LazadaLinioJumiaProductFeedHelper::handleImportJob();
    }
    
    /**
     *  执行处理导入excel任务
     * ./yii linio/handle-import-job2
     */
    public function actionHandleImportJob2() {
//         CommonHelper::startJob("eagle.modules.listing.helpers.LazadaLinioJumiaProductFeedHelper.handleImportJob",
//                 10, "handle-import-job", "1.0", self::$nativeVersion, "linio");
        
        \eagle\modules\listing\helpers\LazadaLinioJumiaProductFeedHelper::handleImportJob();
    }
    
    /**
     *  执行处理导入excel任务
     * ./yii linio/handle-import-job3
     */
    public function actionHandleImportJob3() {
        //         CommonHelper::startJob("eagle.modules.listing.helpers.LazadaLinioJumiaProductFeedHelper.handleImportJob",
        //                 10, "handle-import-job", "1.0", self::$nativeVersion, "linio");
    
        \eagle\modules\listing\helpers\LazadaLinioJumiaProductFeedHelper::handleImportJob();
    }
    
    /**
     *  执行处理导入excel任务
     * ./yii linio/check-import-feed
     */
    public function actionCheckImportFeed() {
//         CommonHelper::startJob("eagle.modules.listing.helpers.LazadaFeedHelper.checkImportFeed",
//                 10, "check-import-feed", "1.0", self::$nativeVersion, "linio");
    
        \eagle\modules\listing\helpers\LazadaFeedHelper::checkImportFeed();
    }
    

    
    
    
    
}