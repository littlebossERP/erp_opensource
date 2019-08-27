<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use eagle\models\QueueAliexpressGetorder;
use yii\base\Exception;
use console\helpers\AliexpressHelper;
use eagle\models\SaasAliexpressUser;
use eagle\modules\util\models\UserBackgroundJobControll;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\helpers\AliexpressOrderHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;

/**
 * Aliexpress 后台脚本
 * @author million 88028624@qq.com
 * 2015-05-21
 */
class AliexpressController extends Controller
{
###################################################################################################################
    /**
     * 按时间同步（type=day120）
     * 同步速卖通120天内未完成订单的订单列表
     * 主要作用：新绑定账号，重新绑定，重新开启同步的情况下需要一次性同步120天所有未完成的订单，提高新账号同步订单速度
     * last_time 就是绑定，重新绑定，重新开启的时间
     * @author million 88028624@qq.com
     * 2015-05-21
     */
    function actionGetOrderListByDay120()
    {
        $startRunTime = time();
        do {
            $rtn = AliexpressHelper::getOrderListByDay120();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
    }
###################################################################################################################	
    /**
     * 按时间和状态同步 （type=finish）
     * 同步速卖通所有已完成订单的订单列表
     * 主要作用：新绑定账号，重新绑定，重新开启同步的情况下需要一次性同步所有已完成的订单，拿用户所有订单数据供日后分析
     * @author million 88028624@qq.com
     * 2015-05-21
     */
    function actionGetOrderListByFinish()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ALIGOL" . $seed . "FINISH";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_get_order_list_by_finish jobid=$cronJobId start \n";
        \Yii::info("aliexress_get_order_list_by_finish jobid=$cronJobId start");
        do {
            $rtn = AliexpressHelper::getOrderListByFinish();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                echo "aliexress_get_order_list_by_finish jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_get_order_list_by_finish jobid=$cronJobId sleep10");
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
        echo "aliexress_get_order_list_by_finish jobid=$cronJobId end \n";
        \Yii::info("aliexress_get_order_list_by_finish jobid=$cronJobId end");
    }
    ###################################################################################################################
    /**
     * 按时间和状态同步 （type=finish）
     * 同步速卖通所有已完成订单的订单列表
     * 主要作用：新绑定账号，重新绑定，重新开启同步的情况下需要一次性同步所有已完成的订单，拿用户所有订单数据供日后分析
     * @author million 88028624@qq.com
     * 2015-05-21
     */
    function actionGetOrderListByFinishDay30()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ALIGOL" . $seed . "FINISHDAY30";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_get_order_list_by_finish30 jobid=$cronJobId start \n";
        \Yii::info("aliexress_get_order_list_by_finish30 jobid=$cronJobId start");
        do {
            $rtn = AliexpressHelper::getOrderListByFinishDay30();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                echo "aliexress_get_order_list_by_finish30 jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_get_order_list_by_finish30 jobid=$cronJobId sleep10");
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
        echo "aliexress_get_order_list_by_finish30 jobid=$cronJobId end \n";
        \Yii::info("aliexress_get_order_list_by_finish30 jobid=$cronJobId end");
    }
###################################################################################################################	
    /**
     * 按时间同步 （type=time）
     * 同步速卖通新产生订单的订单列表
     * 主要作用：及时更新最新产生的订单
     * @author million 88028624@qq.com
     * 2015-05-21
     */
    public function actionGetOrderListByTime()
    {
        $startRunTime = time();
        do {
            $rtn = AliexpressHelper::getOrderListByTime();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
    }
###################################################################################################################	
    /**
     * 同步队列订单中新订单到小老板库
     * rice
     * 2015-11-10
     */
    public function actionFirstToDb()
    {
        $startRunTime = time();
        do {
            $rtn = AliexpressHelper::firstToDb();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
    }
##################################################################################################################	
    /**
     * 新绑定账户，重新开启，重新绑定第一次同步订单详情（已完成的）（type = 2 , id asc）
     * 主要作用：及时同步新绑定账户订单
     * @author million 88028624@qq.com
     * 2015-05-21
     */
    function actionGetOrderFinish()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ALIGOL" . $seed . "FINISH";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_get_order_finish jobid=$cronJobId start \n";
        \Yii::info("aliexress_get_order_finish jobid=$cronJobId start", "file");
        do {
            $rtn = AliexpressHelper::getOrderFinish(QueueAliexpressGetorder::FINISH);
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                echo "aliexress_get_order_finish jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_get_order_finish jobid=$cronJobId sleep10");
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
        echo "aliexress_get_order_finish jobid=$cronJobId end \n";
        \Yii::info("aliexress_get_order_finish jobid=$cronJobId end", "file");
    }
##################################################################################################################
    /**
     * 订单状态更新
     * 主要作用：同步所有订单数据
     * @author million 88028624@qq.com
     * 2015-05-21
     */
    public function actionGetOrderHalf()
    {
        $startRunTime = time();
        do {
            $rtn = AliexpressHelper::getOrder2(QueueAliexpressGetorder::NOFINISH);
            if ($rtn === false) {
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
    }
###################################################################################################################
##################################################################################################################
    /**
     * 订单状态更新
     * 主要作用：同步所有订单数据
     * @author million 88028624@qq.com
     * 2015-05-21
     */
    public function actionGetOrderHalf2()
    {
        $startRunTime = time();
        do {
            $rtn = AliexpressHelper::getOrder3(QueueAliexpressGetorder::NOFINISH);
            if ($rtn === false) {
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
    }
###################################################################################################################
##################################################################################################################
    /**
     * 订单状态更新
     * 主要作用：同步所有订单数据
     * @author million 88028624@qq.com
     * 2015-05-21
     */
    public function actionGetOrderHalf3()
    {
        $startRunTime = time();
        do {
            $rtn = AliexpressHelper::getOrder4(QueueAliexpressGetorder::NOFINISH);
            if ($rtn === false) {
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
    }
###################################################################################################################
    /**
     * 获取在线商品上架状态的商品
     * 主要作用：同步上架状态的在线商品
     * @author million 88028624@qq.com
     * 2015-05-21
     */
    function actionGetListingOnSelling()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ALIGOL" . $seed . "ONSELLING";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_get_listing_onselling jobid=$cronJobId start \n";
        \Yii::info("aliexress_get_listing_onselling jobid=$cronJobId start");
        do {
            $rtn = AliexpressHelper::getListing('onSelling');
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                echo "aliexress_get_listing_onselling jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_get_listing_onselling jobid=$cronJobId sleep10");
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
        echo "aliexress_get_listing_onselling jobid=$cronJobId end \n";
        \Yii::info("aliexress_get_listing_onselling jobid=$cronJobId end");
    }

    /**
     * 即时将saas_aliexpress_autosync的last_time=0的处理
     * 获取在线商品上架状态的商品
     * 主要作用：同步上架状态的在线商品
     * @author hqw
     * 2015-09-23
     * ./yii aliexpress/get-listing-on-selling-immediate
     */
    function actionGetListingOnSellingImmediate()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ALIGOL" . $seed . "ONSELLINGImmediate";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_get_listing_onselling_immediate jobid=$cronJobId start \n";
        \Yii::info("aliexress_get_listing_onselling_immediate jobid=$cronJobId start");
        do {
            $rtn = AliexpressHelper::getListing('onSelling', 'last_time', 43200, 'Y');
            //如果没有需要handle的request了，sleep 2s后再试
            if ($rtn === false) {
                echo "aliexress_get_listing_onselling_immediate jobid=$cronJobId sleep2 \n";
                \Yii::info("aliexress_get_listing_onselling_immediate jobid=$cronJobId sleep2");
                sleep(2);
            }
        } while (time() < $startRunTime + 3600);
        echo "aliexress_get_listing_onselling_immediate jobid=$cronJobId end \n";
        \Yii::info("aliexress_get_listing_onselling_immediate jobid=$cronJobId end");
    }
###################################################################################################################
    /**
     * 用户账号刷新refresh_token
     * @author dzt 2015-07-13
     */
    function actionPostponeToken()
    {
        echo "aliexress_postpone_token  start \n";
        \Yii::info("aliexress_postpone_token  start");

        $rtn = AliexpressHelper::postponeToken();

        echo "aliexress_postpone_token  end \n";
        \Yii::info("aliexress_postpone_token  end");
    }
#####################################################################################################################
    /**
     * 获取需要同步的订单
     * 主要作用：无需排序，只需查找订单，插入queue_aliexpress_getorder表即可
     * @author yangjun
     * 2015-08-24
     */
    function actionGetOrderInsertQueue()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ALIGOL" . $seed . "SYNC";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_get_listing_sync jobid=$cronJobId start \n";
        \Yii::info("aliexress_get_listing_sync jobid=$cronJobId start");
        do {
            $rtn = AliexpressHelper::getOrderInsertQueue();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                echo "aliexress_get_listing_sync jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_get_listing_sync jobid=$cronJobId sleep10");
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
        echo "aliexress_get_listing_sync jobid=$cronJobId end \n";
        \Yii::info("aliexress_get_listing_sync jobid=$cronJobId end");
    }


    public function actionRefreshToken()
    {
        $auth = new \common\api\aliexpressinterface\AliexpressInterface_Auth();
        var_dump($auth->getAccessToken('cn1510671045', true));
    }


    /**
     * 获取需要发送好评的队列
     * 主要作用：通过好评助手发送好评
     * @author kincenyang
     * 2015-08-26
     */
    function actionGetListingPraise()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ALIGOL" . $seed . "PRAISE";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_get_listing_PRAISE jobid=$cronJobId start \n";
        \Yii::info("aliexress_get_listing_PRAISE jobid=$cronJobId start");
        do {
            $rtn = AliexpressHelper::getListingPraiseV2();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                echo "aliexress_get_listing_PRAISE jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_get_listing_PRAISE jobid=$cronJobId sleep10");
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
        echo "aliexress_get_listing_PRAISE jobid=$cronJobId end \n";
        \Yii::info("aliexress_get_listing_PRAISE jobid=$cronJobId end");
    }


    /*
     * 获取listing的详细信息
    * ./yii aliexpress/get-listing-detail
    */
    function actionGetListingDetail()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ALIGL" . $seed . "DETAIL";

        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_get_listing_DETAIL jobid=$cronJobId start \n";
        \Yii::info("aliexress_get_listing_DETAIL jobid=$cronJobId start");

// 		do{
        $job_name = "getAllAliListingDetail";
        $nowTime = time();
        $userBgControl = UserBackgroundJobControll::find()->where(["job_name" => $job_name])->one();
        if ($userBgControl === null) {
            $userBgControl = new UserBackgroundJobControll;
            $userBgControl->job_name = $job_name;
            $userBgControl->create_time = $nowTime;
            $userBgControl->status = 0;
            $userBgControl->error_count = 0;
            $userBgControl->is_active = "Y";// 运行完之后，关闭
            $userBgControl->next_execution_time = $nowTime;
            $userBgControl->update_time = $nowTime;
            $userBgControl->save(false);
        }

        if ($userBgControl->is_active != "Y") {// 下次需要重新更新时候，再开启这个。
// 				break;
            echo "aliexress_get_listing_DETAIL jobid=$cronJobId end \n";
            \Yii::info("aliexress_get_listing_DETAIL jobid=$cronJobId end");
            return;
        }

        if ($userBgControl->status == 1 || time() < $userBgControl->next_execution_time) {//status： 0 未处理 ，1处理中 ，2完成 ，3失败
            echo "aliexress_get_listing_DETAIL jobid=$cronJobId sleep10 \n";
            \Yii::info("aliexress_get_listing_DETAIL jobid=$cronJobId sleep10");
// 				sleep(10);
// 				continue;
            echo "aliexress_get_listing_DETAIL jobid=$cronJobId end \n";
            \Yii::info("aliexress_get_listing_DETAIL jobid=$cronJobId end");
            return;
        }

        $userBgControl->status = 1;
        $userBgControl->save(false);

        $additionalInfo = json_decode($userBgControl->additional_info, true);

        $startId = 0;
        if (!empty($additionalInfo) && !empty($additionalInfo['startId'])) {
            $startId = $additionalInfo['startId'];
        }

        $SAUs = SaasAliexpressUser::find()->where("aliexpress_uid>" . $startId)->limit(10)->asArray()->all();
        if (empty($SAUs)) {// 全部搞定
            $userBgControl->status = 2;
// 				$userBgControl->is_active = "N";// 不再关闭，改为修改next_execution_time
            $userBgControl->next_execution_time = $nowTime + 60 * 60;
            $userBgControl->save(false);
// 				break;
            echo "aliexress_get_listing_DETAIL jobid=$cronJobId end \n";
            \Yii::info("aliexress_get_listing_DETAIL jobid=$cronJobId end");
            return;
        }
        try {
            foreach ($SAUs as $SAU) {
                echo 'puid:' . $SAU['uid'] . ',sellerloginid:' . $SAU['sellerloginid'] . PHP_EOL;
                AliexpressHelper::getListingDetail($SAU['uid'], $SAU['sellerloginid']);

                //     				AliexpressHelper::getListingDetail();

                $userBgControl->additional_info = json_encode(array('startId' => $SAU['aliexpress_uid']));
                $userBgControl->save(false);
            }

            $userBgControl->status = 2;
            $userBgControl->save(false);
        } catch (\Exception $e) {
            $errorMessage = "file:" . $e->getFile() . " line:" . $e->getLine() . " message:" . $e->getMessage();
            \Yii::error("aliexress_get_listing_DETAIL " . $errorMessage, "file");

            $nowTime = time();
            $userBgControl->status = 3;
            $userBgControl->error_count = $userBgControl->error_count + 1;
            $userBgControl->error_message = $errorMessage;
            $userBgControl->last_finish_time = $nowTime;
            $userBgControl->update_time = $nowTime;
            $userBgControl->next_execution_time = $nowTime + 5 * 60;//5分钟后重试
            $userBgControl->save(false);
        }
// 		}while (time() < $startRunTime+3600);

        echo "aliexress_get_listing_DETAIL jobid=$cronJobId end \n";
        \Yii::info("aliexress_get_listing_DETAIL jobid=$cronJobId end");
    }


    /*
     * 导出listing的信息 for ensogo 导入
    * ./yii aliexpress/export-listing-for-ensogo
    */
    function actionExportListingForEnsogo()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ALIGL" . $seed . "EXPORT";

        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_get_listing_EXPORT jobid=$cronJobId start \n";
        \Yii::info("aliexress_get_listing_EXPORT jobid=$cronJobId start");

// 		do{
        AliexpressHelper::cronExportListing();
// 		}while (time() < $startRunTime+3600);

        echo "aliexress_get_listing_EXPORT jobid=$cronJobId end \n";
        \Yii::info("aliexress_get_listing_EXPORT jobid=$cronJobId end");
    }

    /*
    * 从excel 获取 listing 信息导入数据库 等待异步job 处理
    * ./yii aliexpress/get-listing-info-from-excel
    */
    function actionGetListingInfoFromExcel()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "EXCEL" . $seed . "IMPORT";

        AliexpressHelper::setCronJobId($cronJobId);
        echo "excel_listing_IMPORT jobid=$cronJobId start \n";
        \Yii::info("excel_listing_IMPORT jobid=$cronJobId start");

// 		do{
        AliexpressHelper::cronGetEnsogoListingFromExcel();
// 		}while (time() < $startRunTime+3600);

        echo "excel_listing_IMPORT jobid=$cronJobId end \n";
        \Yii::info("excel_listing_IMPORT jobid=$cronJobId end");
    }

    /*
    * 导入listing的信息 到ensogo
    * ./yii aliexpress/import-listing-to-ensogo
    */
    function actionImportListingToEnsogo()
    {
        $startRunTime = time();
        $seed = rand(0, 99999);
        $cronJobId = "ENSOGOLISTING" . $seed . "IMPORT";

        AliexpressHelper::setCronJobId($cronJobId);
        echo "ensogo_excel_listing_IMPORT jobid=$cronJobId start \n";
        \Yii::info("ensogo_excel_listing_IMPORT jobid=$cronJobId start");

// 		do{
        AliexpressHelper::importListingToEnsogo();
// 		}while (time() < $startRunTime+3600);

        echo "ensogo_excel_listing_IMPORT jobid=$cronJobId end \n";
        \Yii::info("ensogo_excel_listing_IMPORT jobid=$cronJobId end");
    }

###################################################################################################################
    /**
     * +---------------------------------------------------------------------------------------------
     * aliexpress 删除后重新同步订单队列
     * +---------------------------------------------------------------------------------------------
     * @access static
     * ./yii aliexpress/get-order-manual-queue
     * +---------------------------------------------------------------------------------------------
     * @param na
    +---------------------------------------------------------------------------------------------
     * @return                na
    +---------------------------------------------------------------------------------------------
     * log            name    date                    note
     * @author        lkh        2015/12/09                初始化
     * +---------------------------------------------------------------------------------------------
     **/
    function actionGetOrderManualQueue()
    {
        $startRunTime = time();
        do {
            echo "\n" . date('Y-m-d H:i:s');
            $rtn = AliexpressHelper::refreshOrderInfoByManualQueue();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn === false) {
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
    }//end of actionGetOrderMaualQueue
    ###################################################################################################################


    ###################################################################################################################
    /**
     * 监控表-saas_aliexpress_autosync 中的status状态,超出设置时间还是1的情况,就需要发邮件报警了
     * last_time在当前检测时间的1天后 && type=time
     * @author akirametero
     *
     */

    function actionGetAlisysErrorList()
    {
        $return = AliexpressHelper::getAliexpresssysErrorList(1, 'time', time() - 86400);


    }
    //end function
    ###################################################################################################################
    /**
     * +---------------------------------------------------------------------------------------------
     * aliexpress 统计每个账号 一天的订单数量
     * +---------------------------------------------------------------------------------------------
     * @access static
     * ./yii aliexpress/cron-aliexpress-order-summary
     * +---------------------------------------------------------------------------------------------
     * @param na
     +---------------------------------------------------------------------------------------------
     * @return                na
     +---------------------------------------------------------------------------------------------
     * log            name    date                    note
     * @author        lkh        2016/05/17                初始化
     * +---------------------------------------------------------------------------------------------
     **/
    function actionCronAliexpressOrderSummary(){
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Aliexpress UserAliexpressOrderDailySummary at $start_time";
    	AliexpressOrderHelper::UserAliexpressOrderDailySummary($start_time);
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service start at $start_time , then stops for Aliexpress UserAliexpressOrderDailySummary at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['aliexpress',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }
    ###################################################################################################################
    


}
