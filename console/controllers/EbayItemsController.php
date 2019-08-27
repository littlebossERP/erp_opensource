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

class EbayItemsController extends Controller {
    /**
     * [actionCronAutoGetitem ：
     * 自动方式更新item信息
     * 使用GetSellerEvents API,获取发生变化的item]
     * @Author   willage
     * @DateTime 2016-10-13T17:22:29+0800
     * @return   [type]                   [description]
     * $command ./yii ebay-items/cron-auto-getitem
     */
    public function actionCronAutoGetitem(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "EBAY-".$seed."-AUTOGETITEM";
        EbayGetItemApiHelper::setCronJobId($cronJobId);
        echo __FUNCTION__." jobid=$cronJobId start \n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
        do{
            $rtn = EbayGetItemApiHelper::autoGetitem();
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
     * [actionCronFirstGetitem description]
     * @Author   willage
     * @DateTime 2016-10-20T11:30:52+0800
     * @return   [type]                   [description]
     * $command ./yii ebay-items/cron-first-getitem
     */
    public function actionCronFirstGetitem(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "EBAY-".$seed."-FIRSTGETITEM";
        EbayGetItemApiHelper::setCronJobId($cronJobId);
        echo __FUNCTION__." jobid=$cronJobId start"."\n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
        do{
            $rtn = EbayGetItemApiHelper::firstGetitem();
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
     * [actionCronManualGetitem ：
     * 手动方式获取item信息
     * 使用GetSellerEvents API，获取用户的item]
     * @Author   willage
     * @DateTime 2016-10-13T17:36:49+0800
     * @return   [type]                   [description]
     */
    public function actionCronManualGetitem(){

    }
    /**
     * [actionCronBgGetitem description]
     * @Author   willage
     * @DateTime 2016-10-21T10:51:06+0800
     * @param    [type]                   $ebayUid  [description]
     * @param    [type]                   $sDayTime [description]
     * @param    [type]                   $eDayTime [description]
     * @return   [type]                             [description]
     * $command ./yii ebay-items/cron-bg-getitem
     */
    public function actionCronBgGetitem($ebayUid,$sDayTime,$eDayTime){
        //判断是否未纯数字，负号也认为非数字
        if (ctype_digit($ebayUid)&&ctype_digit($sDayTime)&&ctype_digit($eDayTime)) {
            if (($sDayTime>0) &&($eDayTime>0) &&($ebayUid>0)) {
                if ($sDayTime+$eDayTime <120) {
                    $rtn = EbayGetItemApiHelper::bgGetitem($ebayUid,$sDayTime,$eDayTime);
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
     * [acitonCronAssignGetitem 用于指定ItemID拉取]
     * @Author   willage
     * @DateTime 2016-10-21T14:08:26+0800
     * @return   [type]                   [description]
     * 162220151191
     * $command ./yii ebay-items/cron-assign-getsingleitem
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

    public function actionCronAssignGetitem($ebayUid,$itemID,$siteID=0){
        $eu = SaasEbayUser::find()->where ( ['ebay_uid'=>$ebayUid] )->andWhere('expiration_time>='.time())->one();
        if (empty($eu)){
            echo  __FUNCTION__." cant be found or token timeout expiration_time\n";
        }
        $getitem_api = new getitem();
        try {
            $rsp = $getitem_api->_getone($eu->token,$itemID,$siteID);
        } catch ( \Exception $ex ) {
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