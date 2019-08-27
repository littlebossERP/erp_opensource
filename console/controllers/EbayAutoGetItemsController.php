<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\models\UserDatabase;
use common\api\ebayinterface\shopping\getsingleitem;
use common\api\ebayinterface\getitem;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\modules\listing\models\EbayItem;
use eagle\models\SaasEbayUser;
use console\helpers\EbayAutoCommonHelper;
use console\helpers\EbayAutoGetItemsCsolHelper;
use eagle\modules\listing\apihelpers\EbayItemApiHelper;
/**
 *----------------------------------------------------------------
 *<------------
 *|           |
 *|     3<----|
 *|     |     |
 *0---->----->1------>2(2:执行完FIRST,AUTO待执行)
 *     |              |
 *     <----20<------10<---
 *                   |    |
 *                  30--->
 *----------------------------------------------------------------
 *   //状态机
 *   const FIRST_PENDING=0;
 *   const FIRST_RUNNING=1;
 *   const FIRST_EXECEPT=3;
 *   const AUTO_PENDING=2;
 *   const AUTO_RUNNING=10;
 *   const AUTO_FINISH=20;
 *   const AUTO_EXECEPT=30;
 *----------------------------------------------------------------
 *  EbayAutoGetItemsController.php ---- 总crontab(第一级)
 *  EbayAutoGetItemsCsolHelper.php ---- item拉取console helper文件(第二级)
 *  EbayAutoCommonHelper.php ---- ebay console公共helper文件
 *----------------------------------------------------------------
 */
class EbayAutoGetItemsController extends Controller {
    /**
     * [actionCronFirstGetitem description]
     * @author willage 2016-10-20T11:30:52+0800
     * @update willage 2017-05-05T10:56:52+0800
     * $command ./yii ebay-auto-get-items/cron-first-getitems
     */
    public function actionCronFirstGetitems(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "EBAY-".$seed."-FIRSTGETITEM";
        EbayAutoGetItemsCsolHelper::setCronJobId($cronJobId);
        echo __FUNCTION__." jobid=$cronJobId start"."\n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
        do{
            $rtn = self::_firstGetitems();
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
     * [actionCronAutoGetitem 自动方式更新item信息,使用GetSellerEvents API,获取发生变化的item]
     * @author willage 2016-10-13T17:22:29+0800
     * @update willage 2017-04-19T10:03:38+0800
     * $command ./yii ebay-auto-get-items/cron-auto-getitems
     */
    public function actionCronAutoGetitems(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "EBAY-".$seed."-AUTOGETITEM";
        EbayAutoGetItemsCsolHelper::setCronJobId($cronJobId);
        echo __FUNCTION__." jobid=$cronJobId start \n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
        do{
            $rtn = self::_autoGetitems();
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
     * [_autoGetitems actionCronAutoGetitem的主体功能]
     * @author willage 2017-04-19T15:30:12+0800
     * @update willage 2017-04-19T15:30:12+0800
     */
    public static function _autoGetitems(){
    /**
     * No.0-[优雅退出]
     */
        $ret=EbayAutoGetItemsCsolHelper::checkNeedExitNot();
        if ($ret===true) exit;
    /**
     * No.1-提取saas任务
     */
        $syncRs = EbayAutoCommonHelper::getSaasAutoSync(10,'auto_getitems');
        if (count ( $syncRs ) == 0) {
            echo "No saas records\n";
            return false;
        }
        $bgJobId=EbayAutoGetItemsCsolHelper::getCronJobId(); //获取进程id号，主要是为了打印log
        $activeUsers=EbayItemApiHelper::getEbayActiveUsersList();//以前的公共接口
        foreach ( $syncRs as $syncR ) {
            /**
             * No.2-[抢saas记录]
             */
            echo "Locked saas record\n";
            $syncR=EbayAutoCommonHelper::lockSaasRecord($syncR->id,'auto_getitems');
            if (empty($syncR)) {
                echo __FUNCTION__." $bgJobId ".$syncR->selleruserid."\n";
                echo "-No saas locked record\n";
                continue;
            }
            /**
             * No.3-[执行autoGetitems]
             */
            $eu=SaasEbayUser::findOne(['selleruserid'=>$syncR->selleruserid]);
            $ret=EbayAutoGetItemsCsolHelper::goAutoGetitems($syncR,$eu,$activeUsers);
            if ($ret!=true) {
                echo "-do autoGetitems error!\n";
                continue;
            }

        }//foreach syncRs
        return true;
    }

    public static function _firstGetitems(){
    /**
     * No.0-[优雅退出]
     */
        $ret=EbayAutoGetItemsCsolHelper::checkNeedExitNot();
        if ($ret===true) exit;
        $resultof=true;
    /**
     * No.1-提取saas任务
     */
        $syncRs = EbayAutoCommonHelper::getSaasAutoSync(10,'first_getitems');
        if (count ( $syncRs ) == 0) {
            echo "No saas records\n";
            return false;
        }
        $bgJobId=EbayAutoGetItemsCsolHelper::getCronJobId(); //获取进程id号，主要是为了打印log
        $activeUsers=EbayItemApiHelper::getEbayActiveUsersList();//以前的公共接口
        foreach ( $syncRs as $syncR ) {
            /**
             * No.2-[抢saas记录]
             */
            echo "Locked saas record\n";
            $syncR=EbayAutoCommonHelper::lockSaasRecord($syncR->id,'first_getitems');
            if (empty($syncR)) {
                echo __FUNCTION__." $bgJobId ".$syncR->selleruserid."\n";
                echo "-No saas locked record\n";
                continue;
            }
            /**
             * No.3-[执行获取item]
             */
            $eu=SaasEbayUser::findOne(['selleruserid'=>$syncR->selleruserid]);
            $ret=EbayAutoGetItemsCsolHelper::goFirstGetitems($syncR,$eu,$activeUsers);
            if ($ret!=true) {
                echo "-do autoGetitems error!\n";
                continue;
            }
        }//foreach syncRs
        return true;
    }

/**
 * ===================================================================================================
 */
    /**
     * [actionCronBgGetitem 用于命令行指定用户/时间段,获取item]
     * @Author   willage
     * @DateTime 2016-10-21T10:51:06+0800
     * $command ./yii ebay-items/cron-bg-getitem
     */
    public function actionCronBgGetitem($ebayUid,$sDayTime,$eDayTime){
        //判断是否未纯数字，负号也认为非数字
        if (ctype_digit($ebayUid)&&ctype_digit($sDayTime)&&ctype_digit($eDayTime)) {
            if (($sDayTime>0) &&($eDayTime>0) &&($ebayUid>0)) {
                if ($sDayTime+$eDayTime <120) {
                    $rtn = EbayAutoGetItemsCsolHelper::bgGetitem($ebayUid,$sDayTime,$eDayTime);
                    if ($rtn===false) {
                        echo "no record find\n";
                    }
                }else{
                    echo "param is out of range\n";
                }
            }else{
                echo "=param is not correct\n";
            }
        }else{
            echo "-param is not correct\n";
        }

    }
    /**
     * [acitonCronAssignGetitem 用于命令行指定ItemID拉取]
     * @Author   willage
     * @DateTime 2016-10-21T14:08:26+0800
     * @return   [type]                   [description]
     * 162220151191
     * $command ./yii ebay-items/cron-assign-singleitem
     */
    public function actionCronAssignGetsingleitem($itemID){
        $getitem_api = new getsingleitem();
        try {
            $_r = $getitem_api->apiItem($itemID);
        } catch ( \Exception $ex ) {
            \Yii::error(print_r($ex->getMessage()));
        }
        print_r($_r,false);
    }
    /**
     * [actionCronAssignGetitem 用于命令行指定ItemID拉取]
     * @author willage 2017-05-05T09:20:39+0800
     * @update willage 2017-05-05T09:20:39+0800
     * $command ./yii ebay-auto-get-items/cron-assign-getitem
     */
    public function actionCronAssignGetitem($ebayUid,$itemID,$siteID=0){
        $eu = SaasEbayUser::find()->where ( ['ebay_uid'=>$ebayUid] )->andWhere('expiration_time>='.time())->one();
        if (empty($eu)){
            echo  __FUNCTION__." cant be found or token timeout expiration_time\n";
        }
        $getitem_api = new getitem();
        try {
            $rsp = $getitem_api->getOne($eu->token,$itemID,$siteID);
        } catch ( \Exception $ex ) {
            print_r($ex->getMessage());
            \Yii::error(print_r($ex->getMessage()));
        }
        print_r($rsp,false);
    }
    /**
     * [actionEbayitemSaveto description]
     * @Author   willage
     * @DateTime 2016-10-24T16:50:30+0800
     * @return   [type]                   [description]
     * $command ./yii ebay-items/ebayitem-saveto
     */
    public function actionEbayitemSaveto()
    {
        //find user
        $EAUs=SaasEbayAutosyncstatus::find()->where(['type'=>7])->all();
        foreach ($EAUs as $EAUkey => $EAU) {
            echo "selleruserid=".$EAU->selleruserid."\n";
            //提取ebay user
            $eu=SaasEbayUser::find()->where(['selleruserid'=>$EAU->selleruserid])->one();
            if (empty($eu)){
                echo  $EAU->selleruserid." cant be found or token timeout\n";
                continue;
            }
             

            //找到itemdetail，转存数据到item
            $EIDs=EbayItemDetail::find()->all();
            echo "count EIDs=".count($EIDs)."\n";
            foreach ($EIDs as $EIDkey => $EID) {
                $ei=EbayItem::find()->where(['itemid'=>$EID->itemid])->one();
                if (empty($ei)) {
                    echo "find no ebay item\n";
                    continue;
                }
                $ei->storecategoryid=$EID->storecategoryid;
                $ei->primarycategory=$EID->primarycategory;
                $ei->save(false);
            }
        }

    }//



}//end class