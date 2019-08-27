<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\models\UserDatabase;
use eagle\modules\listing\apihelpers\EbayGetItemApiHelper;
use common\api\ebayinterface\shopping\getsingleitem;
use common\api\ebayinterface\getitem;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\modules\listing\models\EbayItem;
use eagle\models\SaasEbayUser;
use eagle\modules\ebay\apihelpers\EbayCommonInfoApiHelper;
/**
 * ebay 常用的公共信息获取
 */
class EbayCommonInfoController extends Controller {
    /**
     * [actionGetUserInfo description]
     * @Author   willage
     * @DateTime 2016-11-11T14:53:55+0800
     * @return   [type]                   [description]
     * command ./yii ebay-common-info/get-user-info
     */
    public function actionGetUserInfo(){
        $seed = rand(0,99999);
        $cronJobId = "EBAY-".$seed."-GETUSER";
        EbayCommonInfoApiHelper::setCronJobId($cronJobId);
        echo __FUNCTION__." jobid=$cronJobId start \n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId start","file");

        $rtn = EbayCommonInfoApiHelper::getAllUserinfo();
        //如果没有需要handle的request了，sleep 10s后再试
        if ($rtn===false){
            echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
            \Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
            sleep(10);
        }

        echo __FUNCTION__." jobid=$cronJobId end \n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
    }

    /**
     * [actionGetUserInfo description]
     * @Author   willage
     * @DateTime 2016-11-11T17:24:06+0800
     * @return   [type]                   [description]
     */
    public function actionGetStoreInfo(){
        $seed = rand(0,99999);
        $cronJobId = "EBAY-".$seed."-GETUSER";
        EbayCommonInfoApiHelper::setCronJobId($cronJobId);
        echo __FUNCTION__." jobid=$cronJobId start \n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId start","file");

            $rtn = EbayCommonInfoApiHelper::getAllStoreInfo();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
                \Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
                sleep(10);
            }

        echo __FUNCTION__." jobid=$cronJobId end \n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
    }
    /**
     * [actionCronSyncEbayBaseInfo description]
     * @Author willage 2017-02-15T14:02:05+0800
     * @Editor willage 2017-02-15T14:02:05+0800
     * @return [type]  [description]
     * command ./yii ebay-common-info/cron-sync-ebay-base-info
     */
    function actionCronSyncEbayBaseInfo() {
        try {
            // 同步eBay的Base信息
            echo "start sync eBay base!>>>>>>>>>>>>>>>\n";
            EbayCommonInfoApiHelper::AutoSyncEbayBase ();
            sleep(1800);
        } catch ( Exception $ex ) {
            echo 'Error Message:' . $ex->getMessage () . "\n";
            \Yii::error(["ItemInfo",__CLASS__,__FUNCTION__,"Background","cron SyncEbayBaseInfo failure:".print_r($ex->getMessage(),true)],"edb\global");
        }
    }
    /**
     * [actionCronSyncBaseEbayInfo 用于同步eBay刊登时所用的site，category等基本数据信息]
     * 说明：这里依次同步sysbase、site、category、categoryfeature、categoryspecific。
     *      因为都要依据site和category，所以以前更新(分开job更新，可能会有不同步)
     * @Author fanjs
     * @Editor willage 2016-11-15T15:30:33+0800
     * @return [type]  [description]
     * 原来在queueController.php,移到这里
     * command ./yii ebay-common-info/cron-sync-ebay-site-base-info
     */
    function actionCronSyncEbaySiteBaseInfo() {
        try {
            // $shellCmdA='ps -e | grep "cron-sync-ebay-feature"';
            // $shellCmdB='ps -e | grep "cron-sync-ebay-specific"';
            // if ( (empty(exec($shellCmdA,$res))) && (empty(exec($shellCmdB,$res))) ) {
                // 同步eBay的Base信息
                // echo "start sync eBay site base!>>>>>>>>>>>>>>>\n";
                // EbayCommonInfoApiHelper::AutoSyncEbayBase ();
                echo "start sync eBay site details and category!>>>>>>>>>>>>>>>\n";
                EbayCommonInfoApiHelper::AutoSyncEbaySiteBaseInfo();

                // 同步eBay的Site detail信息
                // echo "start sync eBay site details!>>>>>>>>>>>>>>>\n";
                // EbayCommonInfoApiHelper::AutoSyncEbaySiteDetails ();

                // 同步eBay的刊登类目信息
                // echo "start sync eBay category!>>>>>>>>>>>>>>>\n";
                // EbayCommonInfoApiHelper::AutoSyncEbayCategory ();

                // 同步eBay的feature信息
                // echo "start sync eBay feature!>>>>>>>>>>>>>>>\n";
                // EbayCommonInfoApiHelper::AutoSyncEbayFeature ();

                // 同步eBay的specifics信息
                // echo "start sync eBay specifics!>>>>>>>>>>>>>>>\n";
                // EbayCommonInfoApiHelper::AutoSyncEbaySpecific ();

                sleep(3600);
            // }else{
            //        echo "baseinfo>>> feature or specific is going\n";
            // }
        } catch ( Exception $ex ) {
            echo 'Error Message:' . $ex->getMessage () . "\n";
            \Yii::error(["ItemInfo",__CLASS__,__FUNCTION__,"Background","cron ebay info failure:".print_r($ex->getMessage(),true)],"edb\global");
        }
    }

    /**
     * [actionCronSyncEbayFeature 同步feature信息]
     * 说明：将feature拉取从actionCronSyncBaseEbayInfo独立出来，方面进行多进程
     * @Author willage 2017-02-08T15:24:10+0800
     * @Editor willage 2017-02-08T15:24:10+0800
     * @return [type]  [description]
     * command ./yii ebay-common-info/cron-sync-ebay-feature
     */
    function actionCronSyncEbayFeature() {
        //由于feature拉取依赖base ebay info,要等该进程执行完
        try {
            // $shellCmd='ps -e | grep "cron-sync-base-ebay-info"';
            // if (empty(exec($shellCmd,$res))) {
                // 同步eBay的feature信息
                echo "feature>>> start sync eBay feature!\n";
                EbayCommonInfoApiHelper::AutoSyncEbayFeature ();
                sleep(3600);//多进程脚本会每分钟调用一次,防止过度调用 
            // }else{
            //    echo "feature>>> base ebay info is going\n";
            // }
        }catch ( Exception $ex ) {
            echo 'Feature Error Message:' . $ex->getMessage () . "\n";
            \Yii::error(["ItemInfo",__CLASS__,__FUNCTION__,"Background","cron ebay info failure:".print_r($ex->getMessage(),true)],"edb\global");
        }
    }

    /**
     * [actionCronSyncEbaySpecific 同步Specific信息]
     * 说明：将Specific拉取从actionCronSyncBaseEbayInfo独立出来，方面进行多进程
     * @Author willage 2017-02-08T15:36:46+0800
     * @Editor willage 2017-02-08T15:36:46+0800
     * @return [type]  [description]
     * command ./yii ebay-common-info/cron-sync-ebay-specific
     */
    function actionCronSyncEbaySpecific() {
        //由于specifics拉取依赖base ebay info,要等该进程执行完
        try {
            // $shellCmd='ps -e | grep "cron-sync-base-ebay-info"';
            // if (empty(exec($shellCmd,$res))) {
                // 同步eBay的specifics信息
                echo "specifics>>> start sync eBay specifics!\n";
                EbayCommonInfoApiHelper::AutoSyncEbaySpecific ();
                sleep(3600);//多进程脚本会每分钟调用一次,防止过度调用 
            // }else{
            //     echo "specifics>>> base ebay info is going\n";
            // }
        }catch ( Exception $ex ) {
            echo 'Specific Error Message:' . $ex->getMessage () . "\n";
            \Yii::error(["ItemInfo",__CLASS__,__FUNCTION__,"Background","cron ebay info failure:".print_r($ex->getMessage(),true)],"edb\global");
        }
    }
    /**
     * [actionCronSyncBaseEbayInfoByCategory 用于同步eBay刊登时所用的site，category等基本数据信息]
     * @Author   fanjs willage
     * @DateTime 2016-11-15T15:16:00+0800
     * @return   [type]                   [description]
     * 原来在queueController.php,移到这里
     */
    function actionCronSyncBaseEbayInfoByCategory() {
        try {
            // 同步eBay的specifics信息
            echo "start sync eBay specifics!------>\n";
            SaasEbayAutosyncstatusHelper::AutoSyncEbaySpecificByCategoryID('0','177022');
            // sleep(7*24*3600);
        } catch ( Exception $ex ) {
            echo 'Error Message:' . $ex->getMessage () . "\n";
            \Yii::error(["ItemInfo",__CLASS__,__FUNCTION__,"Background","cron ebay info failure:".print_r($ex->getMessage(),true)],"edb\global");
        }
    }



}//end class