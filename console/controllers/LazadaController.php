<?php

namespace console\controllers;

use common\api\lazadainterface\LazadaInterface_Helper;
use console\helpers\CommonHelper;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoFetchApiHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoFetchApiHelperV2;
use eagle\modules\listing\helpers\LazadaFeedHelper;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\listing\models\LazadaPublishListing;
use eagle\modules\util\helpers\TimeUtil;
use MongoId;
use Qiniu\json_decode;
use yii;
use yii\console\Controller;
use common\api\lazadainterface\LazadaInterface_Helper_V2;
use eagle\models\QueueLazadaGetorder;

/**
 * Test controller
 */
class LazadaController extends Controller
{

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

    //./yii lazada/get-order-list-new-create
    // 新订单的拉取。 用户绑定的时间点之后create的订单
    // 已废弃
    public function actionGetOrderListNewCreate()
    {
        $startRunTime = time();
        $logIDStr = "lazada_get_order_list_new_create";
        $seed = rand(0, 99999);
        $cronJobId = "LAGOL" . $seed . "NC";
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start", "file");
        do{
            $rtn = SaasLazadaAutoFetchApiHelper::getOrderListNewCreate('lazada');
            if ($rtn===false){
                echo "$logIDStr jobid=$cronJobId sleep10 \n";
                \Yii::info("$logIDStr jobid=$cronJobId sleep10");
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        echo "$logIDStr jobid=$cronJobId end \n";
        \Yii::info("$logIDStr jobid=$cronJobId end", "file");
    }
    
    //./yii lazada/get-order-list-new-create-v2
    // 新授权 新订单的拉取。 用户绑定的时间点之后create的订单
    public function actionGetOrderListNewCreateV2()
    {
        $startRunTime = time();
        $logIDStr = "lazada_get_order_list_new_create_v2";
        $seed = rand(0, 99999);
        $cronJobId = "LAGOL" . $seed . "NC";
        SaasLazadaAutoFetchApiHelperV2::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start", "file");
        do{
            $rtn = SaasLazadaAutoFetchApiHelperV2::getOrderListNewCreate('lazada');
            if ($rtn===false){
                echo "$logIDStr jobid=$cronJobId sleep10 \n";
                \Yii::info("$logIDStr jobid=$cronJobId sleep10");
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        echo "$logIDStr jobid=$cronJobId end \n";
        \Yii::info("$logIDStr jobid=$cronJobId end", "file");
    }

    //./yii lazada/get-order-list-new-update
    // 新订单的创建之后，更新信息的拉取。 用户绑定的时间点之后update的订单
    // 已废弃
    public function actionGetOrderListNewUpdate()
    {
        $startRunTime = time();
        $logIDStr = "lazada_get_order_list_new_update";
        $seed = rand(0, 99999);
        $cronJobId = "LAGOL" . $seed . "NU";
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start", "file");
        do{
            $rtn = SaasLazadaAutoFetchApiHelper::getOrderListNewUpdate('lazada');
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "$logIDStr jobid=$cronJobId sleep10 \n";
                \Yii::info("$logIDStr jobid=$cronJobId sleep10");
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        echo "$logIDStr jobid=$cronJobId end \n";
        \Yii::info("$logIDStr jobid=$cronJobId end", "file");
    }
    
    //./yii lazada/get-order-list-new-update-v2
    // 新授权 新订单的创建之后，更新信息的拉取。 用户绑定的时间点之后update的订单
    public function actionGetOrderListNewUpdateV2()
    {
        $startRunTime = time();
        $logIDStr = "lazada_get_order_list_new_update_v2";
        $seed = rand(0, 99999);
        $cronJobId = "LAGOL" . $seed . "NU";
        SaasLazadaAutoFetchApiHelperV2::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start", "file");
        do{
            $rtn = SaasLazadaAutoFetchApiHelperV2::getOrderListNewUpdate('lazada');
            //如果没有需要handle的request了，sleep 10s后再试
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
    // ./yii lazada/get-order-list-old-first
    // 已废弃
    public function actionGetOrderListOldFirst()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LAGOL" . $seed . "TIME";
        $logIDStr = "lazada_get_order_list_old_first";

        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
    	do{
            $rtn = SaasLazadaAutoFetchApiHelper::getOrderListOldFirst('lazada');
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
    
    //旧订单的第一次拉取。绑定的时间点之前，n天之内update的订单。
    //新授权  ./yii lazada/get-order-list-old-first-v2
    public function actionGetOrderListOldFirstV2()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LAGOL" . $seed . "TIME";
        $logIDStr = "lazada_get_order_list_old_first_v2";
    
        SaasLazadaAutoFetchApiHelperV2::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
        do{
            $rtn = SaasLazadaAutoFetchApiHelperV2::getOrderListOldFirst('lazada');
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
    // ./yii lazada/get-order-list-old-second
    // 已废弃
    public function actionGetOrderListOldSecond()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LAGOL" . $seed . "TIME";
        $logIDStr = "lazada_get_order_list_old_second";

        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
    	do{
            $rtn = SaasLazadaAutoFetchApiHelper::getOrderListOldSecond('lazada');
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
    // ./yii lazada/get-order-list-by-day2
    // 已废弃
    public function actionGetOrderListByDay2()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LAGOL" . $seed . "TIME";
        $logIDStr = "lazada_get_order_list_old_second";

        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
        //	do{
        $rtn = SaasLazadaAutoFetchApiHelper::getOrderListByDay2('lazada');
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
    
    // 拉取近两日订单
    //新授权 ./yii lazada/get-order-list-by-day2-v2
    public function actionGetOrderListByDay2V2()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LAGOL" . $seed . "TIME";
        $logIDStr = "lazada_get_order_list_by_day2_v2";
    
        SaasLazadaAutoFetchApiHelperV2::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
        //	do{
        $rtn = SaasLazadaAutoFetchApiHelperV2::getOrderListByDay2('lazada');
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


    //后台crontab触发， lazada的订单items拉取
    // 这里才会把订单信息传递到eagle中！！！！
    // 已废弃
    // ./yii lazada/auto-fetch-order-items
    public function actionAutoFetchOrderItems()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LAFOI" . $seed . "A";
        $logIDStr = "lazada_get_order_item";
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
        
        do{
            $rtn=SaasLazadaAutoFetchApiHelper::cronAutoFetchOrderItems('lazada');
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

    //后台crontab触发， lazada的订单items拉取
    // 已废弃
    // ./yii lazada/batch-auto-fetch-order-items
    public function actionBatchAutoFetchOrderItems() {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LBAFOI" . $seed . "A";
        $logIDStr = "lazada_batch_get_order_item";
        SaasLazadaAutoFetchApiHelper::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
        
        do{
            $rtn = SaasLazadaAutoFetchApiHelper::cronBatchAutoFetchOrderItems('lazada');
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "$logIDStr jobid=$cronJobId sleep10 \n";
                \Yii::info("$logIDStr jobid=$cronJobId sleep10");
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        
        $endRunTime=date("Y-m-d H:i:s");
        echo "========cronJobId:$cronJobId,cron service runnning for actionBatchAutoFetchOrderItems at $endRunTime \n";
        \Yii::info("========cronJobId:$cronJobId,cron service runnning for actionBatchAutoFetchOrderItems at $endRunTime \n","file");
    }
    
    //后台crontab触发， 新授权 lazada的订单items拉取
    // ./yii lazada/batch-auto-fetch-order-items-v2
    public function actionBatchAutoFetchOrderItemsV2() {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "LBAFOI" . $seed . "A";
        $logIDStr = "lazada_batch_get_order_item_v2";
        SaasLazadaAutoFetchApiHelperV2::setCronJobId($cronJobId);
        echo "$logIDStr jobid=$cronJobId start \n";
        \Yii::info("$logIDStr jobid=$cronJobId start");
    
        do{
            $rtn = SaasLazadaAutoFetchApiHelperV2::cronBatchAutoFetchOrderItems('lazada');
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "$logIDStr jobid=$cronJobId sleep10 \n";
                \Yii::info("$logIDStr jobid=$cronJobId sleep10");
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
    
        $endRunTime=date("Y-m-d H:i:s");
        echo "========cronJobId:$cronJobId,cron service runnning for actionBatchAutoFetchOrderItemsV2 at $endRunTime \n";
        \Yii::info("========cronJobId:$cronJobId,cron service runnning for actionBatchAutoFetchOrderItemsV2 at $endRunTime \n","file");
    }


    // 自动上传照片
    // ./yii lazada/auto-upload-images
    public static function actionAutoUploadImages() {
        // dzt20170113 lazada 图片上传走新接口，从此与linio分开后台命令触发
        CommonHelper::startJob("eagle.modules.lazada.apihelpers.SaasLazadaAutoSyncApiHelperV4.ImageUpload", 10, "auto-upload-images", "1.0", self::$nativeVersion, "lazada", array("lazada"));
    }

    /**
     * 获取lazada在线商品---获取指定时间内所有账户的新增或者有修改的listing，第一次就是获取所有listing
     * 已废弃
     * ./yii lazada/get-updated-listing
     */
    function actionGetUpdatedListing()
    {
        CommonHelper::startJob("eagle.modules.listing.helpers.LazadaAutoFetchListingHelperV3.getUpdatedisting", 10, "get-updated-listing", "1.0", self::$nativeVersion, "lazada", array("lazada"));
    }
    
    /**
     * 获取lazada在线商品---获取指定时间内所有账户的新增或者有修改的listing，第一次就是获取所有listing(新接口)
     * ./yii lazada/get-updated-listing-v2
     */
    function actionGetUpdatedListingV2()
    {
        CommonHelper::startJob("eagle.modules.listing.helpers.LazadaAutoFetchListingHelperV3.getUpdatedistingV2", 30, "get-updated-listing-v2", "1.0", self::$nativeVersion, "lazada", array("lazada"));
    }


    // 更新Lazada所有站点目录树
    //./yii lazada/refresh-all-site-category-tree-v2
    public function actionRefreshAllSiteCategoryTreeV2()
    {
        LazadaApiHelper::refreshCategoryTreeV2();
    }

    // 更新Lazada目录属性
    //./yii lazada/refresh-category-attrs-v2
    public function actionRefreshCategoryAttrsV2()
    {
        LazadaApiHelper::refreshCategoryAttrsV2();
    }

    // 更新Lazada站点品牌
    //./yii lazada/refresh-site-brands-v2
    public function actionRefreshSiteBrandsV2()
    {
        LazadaApiHelper::refreshBrandsV2();
    }

    // 更新Lazada token
    //./yii lazada/refresh-token
    public function actionRefreshToken() {
        $nowTime = time();
        $lazadaUsers = SaasLazadaUser::find()
        ->where("status=1 and platform='lazada' and version='v2' and token_timeout<=".($nowTime+86400)." and refresh_token_timeout>=$nowTime")
//         ->where("lazada_uid=10")
        ->all();
        echo "actionRefreshToken job start , total:".count($lazadaUsers)." users.\n";
//         print_r($lazadaUsers[0]->attributes);
//         exit();
        foreach($lazadaUsers as $lazadaUser){
    
            $laApi = new LazadaInterface_Helper_V2();
            $nowTime = time();
            $response = $laApi->refreshTokentoAccessToken($lazadaUser->refresh_token);
            if(!empty($response['response']['data']['access_token'])){
                $return = $response['response']['data'];
                $lazadaUser->update_time = $nowTime;
                $lazadaUser->platform_userid = $return['account'];
                $lazadaUser->access_token = $return['access_token'];
                $lazadaUser->refresh_token = $return['refresh_token'];
                $lazadaUser->lazada_site = $return['country'];
                $lazadaUser->token_timeout = ($nowTime + $return['expires_in'] - 24 * 3600);// access_token 30天过期，最好提前1天刷新
                $lazadaUser->refresh_token_timeout = ($nowTime + $return['refresh_expires_in']); // refresh_token 180天过期
                $lazadaUser->country_user_info = json_encode($return['country_user_info_list']);
                $lazadaUser->account_platform = $return['account_platform'];
                if(!$lazadaUser->save()){
                    \Yii::error("lazada refresh token save error:" . print_r($lazadaUser->getErrors(), true), "file");
                    echo "lazada_uid:".$lazadaUser->lazada_uid." refresh fail. ERROR:".print_r($lazadaUser->getErrors(), true)."\n";
                }else{
                    echo "lazada_uid:".$lazadaUser->lazada_uid." refresh success. \n";
                }
    
            }else{
                \Yii::error("lazada refresh token V2 error:" . print_r($response['response'], true), "file");
                echo "lazada_uid:".$lazadaUser->lazada_uid." refresh fail. ERROR2:".print_r($response['response'], true)."\n";
            }
        }
    }
    
    // 由于拉取问题导致或者其他问题导致feed无法正常拉取，要通知listing 
    // ./yii lazada/handle-error-feed
    public function actionHandleErrorFeed() {
    	LazadaFeedHelper::handleErrorFeed();
    }
    
    // 更新客户运输方式 依然lazada linio jumia共用
    //./yii lazada/refresh-shipment-providers
    public function actionRefreshShipmentProviders()
    {
        $allUsers = SaasLazadaUser::find()->where("status<>3")->all();
        foreach ($allUsers as $slu) {
            $config = array(
                "userId" => $slu->platform_userid,
                "apiKey" => $slu->token,
                "countryCode" => $slu->lazada_site
            );
            $apiParams = array();
            if(!empty($slu->version)){//新账号
                $config['apiKey'] = $slu->access_token;//新的授权token
                $ret = LazadaInterface_Helper_V2::getShipmentProviders($config, $apiParams);
            }else{//旧帐号
                $ret = LazadaInterface_Helper::getShipmentProviders($config, $apiParams);
            }
            

            $shipments = array();
            if ($ret["success"] == false) {
                echo $slu->platform_userid . " " . $slu->lazada_site . " get shipment providers fail:message:".$ret['message'].".\n";
                continue;
            } else {
                $shipments = $ret['response']['shipments'];
            }

            $allShipments = array();
            if (!empty($shipments)) {
                if(!empty($slu->version)){//新账号
                    foreach ($shipments as $shipment) {
                        $allShipments[] = $shipment['name'];
                    }
                }else{//旧帐号
                    foreach ($shipments as $shipment) {
                        $allShipments[] = $shipment['Name'];
                    }
                }
            }

            $slu->shipment_providers = json_encode($allShipments);
            if (!$slu->save()) {
                echo $slu->platform_userid . " " . $slu->lazada_site . " save shipment providers fail.".".\n";
            }else{
                echo $slu->platform_userid . " " . $slu->lazada_site . " update shipment providers success.".".\n";
            }
        }
    }

    // 统计lazada 刊登字段Brand Condition  WarrantyType  WarrantyPeriod这几个字段用户都填了什么和比例
    //./yii lazada/statistics-job
    public function actionStatisticsJob()
    {
        $platform = "lazada";

        $allUsers = SaasLazadaUser::find()->where(['platform' => $platform])->AsArray()->all();
        $result = array();
        foreach ($allUsers as $allUser) {
            $puid = $allUser['puid'];
             

            $publishListings = LazadaPublishListing::find()->where(['platform' => $platform])->all();
            foreach ($publishListings as $publishListing) {
                $base_info = json_decode($publishListing->base_info, true);// Brand Condition
                $warranty_info = json_decode($publishListing->warranty_info, true);// WarrantyType  WarrantyPeriod

                self::countColumnVal($base_info, "Brand", $result);
                self::countColumnVal($base_info, "Condition", $result);
                self::countColumnVal($warranty_info, "WarrantyType", $result);
                self::countColumnVal($warranty_info, "Warranty", $result);// WarrantyPeriod
            }
        }

        \Yii::info(json_encode($result), "file");
    }

    static function countColumnVal($info, $column, &$result)
    {
        $value = "";
        if (isset($info[$column])) {
            $value = $info[$column];
        } else {
            $value = 'undefined';
        }

        if (empty($result[$column])) $result[$column] = array();

        if (empty($result[$column]["total"]))
            $result[$column]["total"] = 1;
        else
            $result[$column]["total"]++;

        if (empty($result[$column][$value]))
            $result[$column][$value] = 1;
        else
            $result[$column][$value]++;
    }

    /**
     * 监控表-saas_aliexpress_autosync 中的status状态,超出设置时间还是1的情况,就需要发邮件报警了
     * last_time在当前检测时间的两小时后
     * @author akirametero
     *
     */
    //./yii lazada/get-order-sys-error-list
    function actionGetOrderSysErrorList()
    {
        $return = LazadaApiHelper::getAutoSynErrorList(1, [1, 2, 3], time() - 7200);

    }
    //end function

    /**
     * 监控表-queue_lazada_getorder 中的status状态,超出设置时间还是1的情况,就需要发邮件报警了
     * last_time在当前检测时间的两小时后
     * @author zwd
     *
     */
    //./yii lazada/get-item-sys-error-list
    function actionGetItemSysErrorList()
    {
        $return = LazadaApiHelper::getItemSysErrorList(1, time() - 7200);

    }
    //end function
    
    // 删除lzada旧订单表的数据
    //./yii lazada/delete-lazada-orders
    function actionDeleteLazadaOrders(){
        echo " delete-lazada-orders job start \n";
        $count = 0;
        while ($count < 150){
            $count++;
            $ids = QueueLazadaGetorder::find()->where(['is_active'=>0])->select('id')->limit(5000)->all();
            if(empty($ids)){
                break;
            }
            $deleteIds = [];
            foreach ($ids as $id){
                $deleteIds[] = $id->id;
            }
            
            QueueLazadaGetorder::deleteAll(['id' => $deleteIds]);
        }
        
        
        echo " delete-lazada-orders job end \n";
    }
}