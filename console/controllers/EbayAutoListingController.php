<?php
namespace console\controllers;

use yii;
use yii\db\Query;
use yii\console\Controller;
use console\helpers\EbayAutoCommonHelper;
use console\helpers\EbayAutoInventoryHelper;
use console\helpers\EbayAutoTimerListingCsoleHelper;
use eagle\modules\listing\models\EbayAutoInventory;
use eagle\modules\listing\models\EbayAutoTimerListing;
use eagle\models\SaasEbayUser;
use eagle\models\SaasEbayVip;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\modules\platform\helpers\EbayAccountsHelper;

class EbayAutoListingController extends Controller
{
    //状态
    const PROCESS_PENDING=0;
    const PROCESS_RUNNING=1;
    const PROCESS_FINISH=2;
    const PROCESS_ERROR=3;
    /**
     * [actionCheckItemController 检查item库存数量]
     * @author willage 2017-03-06T09:11:49+0800
     * @editor willage 2017-03-06T09:11:49+0800
     * No.1-抢saas记录
     * No.2-获取自动补货记录
     * No.3-批量检查数量
     * No.4-成功,保存saas记录
     * ./yii ebay-auto-listing/check-inventory
     */
    public function actionCheckInventory(){
        $syncRs = EbayAutoCommonHelper::getSaasAutoSync(10,'check');
        if (empty($syncRs)) {
            echo "No saas records\n";
            return;
        }
        foreach ($syncRs as $skey => $syncR) {
            /**
             * No.1-抢saas记录
             */
            echo "Locked saas record\n";
            $syncR=EbayAutoCommonHelper::lockSaasRecord($syncR->id,'check');
            if (empty($syncR)) {
                echo "-No saas locked record\n";
                continue;
            }
            /**
             * No.2-获取自动补货记录
             */
            echo "Get inventory record\n";
            $invRs=EbayAutoInventoryHelper::getEbayInventory($syncR->selleruserid,'check');
            if (empty($invRs)) {
                echo "-No inventory record\n";
                $syncR->last_first_finish_time=time();
                $syncR->next_execute_time=time()+30*60;//下次检查时间30分钟后
                $syncR->status_process=self::PROCESS_PENDING;//设定为pending
                $syncR->save(false);
                continue;
            }
            /**
             * No.3-批量检查数量
             */
            echo "Batch check inventory\n";
            list($ret,$invCnt)=EbayAutoInventoryHelper::checkQuantity($invRs);
            if ($ret!=EbayAutoInventory::CODE_SUCCESS) {
                echo "-Check quantity error!\n";
                $syncR->last_first_finish_time=time();
                $syncR->next_execute_time=time()+30*60;//下次检查时间30分钟后
                $syncR->status_process=self::PROCESS_ERROR;//设定为error
                $syncR->save(false);
                continue;
            }
            /**
             * No.4-成功,保存saas记录
             */
            echo "Check inventory success\n";
            $syncR->last_first_finish_time=time();
            $syncR->next_execute_time=($invCnt)?(time()+5*60):(time()+30*60);//wilage-20170816有要补货就5分钟后执行,其他30分钟
            $syncR->status_process=self::PROCESS_FINISH;//设定为finish
            $syncR->save(false);
        }

    }
    /**
     * [actionInventoryController 自动补货]
     * 说明：每个job对10个用户进行自动补货
     * 补货要补多少就补多少
     * @author willage 2017-02-24T09:39:30+0800
     * @editor willage 2017-02-24T09:39:30+0800
     * No.1-抢saas记录
     * No.2-获取type0记录--type0批量补货--获取type1记录--type1补货
     * No.3-处理结果,保存saas记录
     *
     * ./yii ebay-auto-listing/auto-inventory
     */
    public function actionAutoInventory(){
        $syncRs = EbayAutoCommonHelper::getSaasAutoSync(10,'inventory');
        if (empty($syncRs)) {
            echo "No saas records\n";
            return;
        }
        foreach ($syncRs as $skey => $syncR) {
            /**
             * No.1-抢saas记录
             */
            echo "Locked saas record\n";
            $syncR=EbayAutoCommonHelper::lockSaasRecord($syncR->id,'inventory');
            if (empty($syncR)) {
                echo "-No saas locked record\n";
                continue;
            }
            $syncStatus=self::PROCESS_FINISH;;//默认结果为FINISH
            /**
             * No.2.0-type0-获取自动补货记录(除了没有sku的various商品)
             */
            echo "Get inventory record\n";
            $invRs=EbayAutoInventoryHelper::getEbayInventory($syncR->selleruserid,'inventory');
            if (empty($invRs)) {
                echo "-No inventory record\n";
                $syncStatus=self::PROCESS_PENDING;
            }else{
            /**
             * No.2.1-type0-批量补货
             */
                echo "Batch auto inventory\n";
                $ret=EbayAutoInventoryHelper::reviseInventory($invRs);
                if ($ret!=EbayAutoInventory::CODE_SUCCESS) {
                    echo "-Inventory error!\n";
                    $syncStatus=self::PROCESS_ERROR;
                }
            }
            /**
             * No.2.2-type1-获取自动补货记录(没有sku的various)
             */
            echo "Get no-sku-var inventory record\n";
            $noskuRs=EbayAutoInventoryHelper::getEbayInventory($syncR->selleruserid,'varnosku');
            if (empty($noskuRs)) {
                echo "-No no-sku-var inventory record\n";
                if ($syncStatus==self::PROCESS_PENDING) {//当前如果非pending,保留当前状态
                    $syncStatus=self::PROCESS_PENDING;
                }
            }else{
            /**
             * No.2.3-type1-补货
             */
                echo "Auto inventory no-sku-var\n";
                $ret=EbayAutoInventoryHelper::reviseNoskuVarInventory($noskuRs);
                if ($ret!=EbayAutoInventory::CODE_SUCCESS) {
                    echo "-Inventory error!\n";
                    $syncStatus=self::PROCESS_ERROR;//设定为error
                }
            }
            /**
             * No.3-处理结果,保存saas记录
             */
            self::_handleInvResult($syncR,$syncStatus);

        }
    }
    /**
     * [_handleInvResult description]
     * @author willage 2017-03-13T17:13:50+0800
     * @editor willage 2017-03-13T17:13:50+0800
     */
    public function _handleInvResult($syncR,$syncStatus){
        $syncR->last_first_finish_time=time();
        $syncR->next_execute_time=time()+10*60;////wilage-20170816间隔由30分钟改为10分钟
        $syncR->status_process=$syncStatus;
        $syncR->save(false);
    }

    /**
     * [actionTimingController 定时刊登]
     * 说明：verify在添加timer队列时候立即做
     * @author willage 2017-02-24T09:39:51+0800
     * @editor willage 2017-02-24T09:39:51+0800
     * No.1-抢saas记录
     * No.2-获取定时刊登记录
     * No.3-处理结果,保存saas记录
     * ./yii ebay-auto-listing/timer-listing
     */
    public function actionTimerListing(){
        $syncRs = EbayAutoCommonHelper::getSaasAutoSync(10,'timer_listing');
        if (empty($syncRs)) {
            echo "No saas records\n";
            return;
        }
        foreach ($syncRs as $skey => $syncR) {
        /**
         * No.1-抢saas记录
         */
            echo "Locked saas record\n";
            $syncR=EbayAutoCommonHelper::lockSaasRecord($syncR->id,'timer_listing');
            if (empty($syncR)) {
                echo "-No saas locked record\n";
                continue;
            }
        /**
         * No.2-删除EbayAutoTimerListing旧记录
         */
            EbayAutoTimerListingCsoleHelper::deleteOldRecord($syncR->selleruserid);
        /**
         * No.3-获取定时刊登记录
         */
            echo "Get timer_listing record\n";
            $timingRs=EbayAutoTimerListingCsoleHelper::getEbayTimerListing($syncR->selleruserid,'timer_listing');
            if (empty($timingRs)) {
                echo "-No timer_listing record\n";
                $syncR->last_first_finish_time=time();
                $syncR->next_execute_time=time()+30*60;//下次检查时间30分钟后
                $syncR->status_process=self::PROCESS_PENDING;//设定为pending
                $syncR->save(false);
                continue;
            }
        /**
         * No.4-定时刊登
         */
            echo "Go timing additems\n";
            $ret=EbayAutoTimerListingCsoleHelper::goAddItems($timingRs);
            if ($ret!=EbayAutoTimerListing::CODE_SUCCESS) {
                echo "-timing additems error!\n";
                $syncR->last_first_finish_time=time();
                $syncR->next_execute_time=time()+30*60;//下次检查时间30分钟后
                $syncR->status_process=self::PROCESS_ERROR;//设定为error
                $syncR->save(false);
                continue;
            }
        /**
         * No.5-成功,保存saas记录
         */
            echo "Timing additems success\n";
            $syncR->last_first_finish_time=time();
            $syncR->next_execute_time=time()+30*60;//下次执行时间30分钟后
            $syncR->status_process=self::PROCESS_FINISH;//设定为finish
            $syncR->save(false);
        }

    }



    /**
     * [actionKeepLisingController 持续刊登]
     * @author willage 2017-02-24T09:39:47+0800
     * @editor willage 2017-02-24T09:39:47+0800
     */
    public function actionKeepLising(){
        //No.1-
    }
    /**
     * [actionOlduserHandle 处理旧数据]
     * @author willage 2017-03-27T14:36:16+0800
     * @update willage 2017-03-27T14:36:16+0800
     * ./yii ebay-auto-listing/olduser-handle
     */
    public function actionOlduserHandle(){
        $query = (new Query())
            ->from('saas_ebay_user')
            ->orderBy('ebay_uid');

        // foreach ($query->batch() as $users) {
        //     // $users is an array of 100 or fewer rows from the user table
        // }

        // or if you want to iterate the row one by one
        foreach ($query->each() as $user) {
            // $user represents one row of data from the user table
        /**
         * No.1-saas autosync增加job
         */
        $autoSync=SaasEbayAutosyncstatus::find()
                    ->where(['ebay_uid'=>$user['ebay_uid']])
                    ->andwhere(['type'=>11])
                    ->one();
        if (empty($autoSync)) {
            $autoSync=new SaasEbayAutosyncstatus();
        }
            $autoSync->selleruserid=$user['selleruserid'];
            $autoSync->ebay_uid=$user['ebay_uid'];
            $autoSync->type=11;
            $autoSync->status=$user['listing_status'];
            $autoSync->status_process=0;
            $autoSync->lastrequestedtime=0;
            $autoSync->lastprocessedtime=0;
            $autoSync->created=time();
            $autoSync->updated=time();
            $autoSync->save(false);

        /**
         * No.2-vip 增加记录
         */
            $vip=new SaasEbayVip();
            $vip->puid=$user['uid'];
            $vip->ebay_uid=$user['ebay_uid'];
            $vip->selleruserid=$user['selleruserid'];
            $vip->vip_type='timer_listing';
            $vip->vip_rank=0;//默认等级0
            $vip->vip_status=$user['listing_status'];
            $vip->valid_period=time()+10*365*24*3600;//有效期10年
            $vip->create_time=time();
            $vip->update_time=time();
            $vip->save(false);
        }
    }
    /**
     * [actionAutoSyncHandle 重新添加,因异常,在绑定期间没有添加autosync记录的用户]
     * @author willage 2017-04-17T09:49:58+0800
     * @update willage 2017-04-17T09:49:58+0800
     * ./yii ebay-auto-listing/autosync-handle
     */
    public function actionAutosyncHandle($ebayUser, $selleruserid){
        EbayAccountsHelper::AddNewEbayUser($ebayUser, $selleruserid);
    }

}//end class