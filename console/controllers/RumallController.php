<?php

namespace console\controllers;

use yii\console\Controller;
use \eagle\modules\listing\helpers\RumallHelper;
use \eagle\modules\order\helpers\RumallOrderHelper;
use \eagle\modules\util\helpers\SysLogHelper;
use common\api\rumallinterface\RumallInterface_Helper;
use eagle\modules\order\models\RumallOrder;
use eagle\modules\listing\helpers\RumallOfferSyncHelper;
use eagle\modules\order\helpers\RumallOrderInterface;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
/**
 * Test controller
 */
class RumallController extends Controller {
    /**
     +---------------------------------------------------------------------------------------------
     * Rumall API Request 处理器。支持多进程一起工作
     * 读取一条request，然后执行，然后继续读取下一条。一直到没有读取到任何pending 的request
     * 如果执行了超过30分钟，自动退出。Job Monitor会自动重新generate一个进程
     * 此队列处理器可以处理wish 的范本刊登的修改，创建，以及order的获取和修改。
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return						array('success'=true,'message'='')
     *
     * @invoking					./yii rumall/do-api-queue-request-order-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/2/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionDoApiQueueRequestOrderEagle2() {
        $start_time = date('Y-m-d H:i:s');
        echo "cron service runnning for Rumall DoQueueReqeustOrder at $start_time";

        do{
            $rtn = RumallOrderHelper::cronQueueHandlerExecuteRumallOrderOp();
             
            //如果没有需要handle的request了，退出
            if ($rtn['success'] and $rtn['message']=="n/a"){
                sleep(10);
                //echo "cron service Rumall DoApiQueueRequestOrderEagle2,no reqeust pending, sleep for 10 sec";
            }
             
            $auto_exit_time = 25 + rand(1,10); // 25 - 35 minutes to leave
            $half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));

        }while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
        TrackingAgentHelper::extCallSum("");
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "cron service stops for Rumall DoApiQueueRequestOrder at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }//end of function actionDo17TrackQuery

    /**
     +---------------------------------------------------------------------------------------------
     * Rumall 平台订单获取 job0。
     * 由cron call 起来，会对所有绑定的Rumall账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii rumall/fetch-recent-order-list-eagle2-job0
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job0() {
        $start_time = date('Y-m-d H:i:s');
        $the_day = date('Y-m-d');
        echo "\n cron service runnning for Rumall cronAutoFetchUnFulfilledOrderList at $start_time";
        $job_id=0;
        $rtn = RumallOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
        $command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
        $record = $command->queryOne();
        if(!empty($record)){
            $run_times = json_decode($record['addinfo2'],true);
            if(!is_array($run_times))
                $run_times = [];
            $error_message = $record['error_message'];
            if (!$rtn['success']){
                $run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
                $sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
            }else{
                $run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
                $sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
            }
        }
        $command = \Yii::$app->db_queue->createCommand($sql);
        $affect_record = $command->execute();
        if(empty($affect_record))
            echo "\n set runtime log falied, sql:".$sql;

        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "cron service stops for Rumall cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        TrackingAgentHelper::extCallSum("");
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }

    /**
     +---------------------------------------------------------------------------------------------
     * Rumall 平台订单获取 job1。
     * 由cron call 起来，会对所有绑定的Rumall账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii rumall/fetch-recent-order-list-eagle2-job1
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job1() {
        $start_time = date('Y-m-d H:i:s');
        $the_day = date('Y-m-d');
        echo "\n cron service runnning for Rumall cronAutoFetchUnFulfilledOrderList at $start_time";
        $job_id=1;
        $rtn = RumallOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);

        $command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
        $record = $command->queryOne();
        if(!empty($record)){
            $run_times = json_decode($record['addinfo2'],true);
            if(!is_array($run_times))
                $run_times = [];
            $error_message = $record['error_message'];
            if (!$rtn['success']){
                $run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
                $sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
            }else{
                $run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
                $sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
            }
        }
        $command = \Yii::$app->db_queue->createCommand( $sql );
        $affect_record = $command->execute();
        if(empty($affect_record))
            echo "\n set runtime log falied, sql:".$sql;
         
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "cron service stops for Rumall cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        TrackingAgentHelper::extCallSum("");
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }

    /**
     +---------------------------------------------------------------------------------------------
     * Rumall 平台订单获取 job2。
     * 由cron call 起来，会对所有绑定的Rumall账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii rumall/fetch-recent-order-list-eagle2-job2
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job2() {
        $start_time = date('Y-m-d H:i:s');
        $the_day = date('Y-m-d');
        echo "\n cron service runnning for Rumall cronAutoFetchUnFulfilledOrderList at $start_time";
        $job_id=2;
        $rtn = RumallOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);

        $command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
        $record = $command->queryOne();
        if(!empty($record)){
            $run_times = json_decode($record['addinfo2'],true);
            if(!is_array($run_times))
                $run_times = [];
            $error_message = $record['error_message'];
            if (!$rtn['success']){
                $run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
                $sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
            }else{
                $run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
                $sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='RMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
            }
        }
        $command = \Yii::$app->db_queue->createCommand( $sql );
        $affect_record = $command->execute();
        if(empty($affect_record))
            echo "\n set runtime log falied, sql:".$sql;
         
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "cron service stops for Rumall cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }

    /**
     +---------------------------------------------------------------------------------------------
     * Rumall 平台产品(offer)获取。
     +---------------------------------------------------------------------------------------------
     * @invoking					./yii rumall/get-rumall-offer-list-eagle2
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionGetRumallOfferListEagle2() {
        //return true;//占用较多数据库io，用其他方式替换此function
        $runtimes=0;
        	
        $rtn = RumallOfferSyncHelper::cronGetOfferList();

        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "cron service stops for rumall GetRumallOfferListEagle2 at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }

    /**
     +---------------------------------------------------------------------------------------------
     * rumall 平台新绑定的帐号订单获取。
     * 由cron call 起来，会对所有绑定的rumall账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @invoking					./yii rumall/fetch-new-account-orders-eagle2
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/7/29				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchNewAccountOrdersEagle2() {

        $start_time = date('Y-m-d H:i:s');
        echo "cron service runnning for rumall FetchNewAccountOrders at $start_time";

        $rtn = RumallOrderHelper::cronAutoFetchNewAccountOrderList();

        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "cron service stops for rumall FetchNewAccountOrders at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }//end of function FetchNewAccountOrdersEagle2

    /**
     * Rumall 平台订单自动检测并添加小老板平台tag。
     * 由cron call 起来，会对所有绑定的Rumall账号进行轮询
     * @access static
     * @invoking					./yii rumall/auto-add-tag-to-rumall-order-job0
     * @author	lzhl	2015/12/25	初始化
     **/
    public function actionAutoAddTagToRumallOrderJob0() {
        $start_time = date('Y-m-d H:i:s');
        echo "\n cron service runnning for Rumall autoAddTagToRumallOrder Job0 at $start_time";
        RumallOrderHelper::cronAutoAddTagToRumallOrder($job_id=0);
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "\n cron service stops for Rumall autoAddTagToRumallOrder Job0 at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagToRumallOrderJob0

    /**
     * Rumall 平台订单自动检测并添加小老板平台tag。
     * 由cron call 起来，会对所有绑定的Rumall账号进行轮询
     * @access static
     * @invoking					./yii rumall/auto-add-tag-to-rumall-order-job1
     * @author	lzhl	2015/12/25	初始化
     **/
    public function actionAutoAddTagToRumallOrderJob1() {
        $start_time = date('Y-m-d H:i:s');
        echo "\n cron service runnning for Rumall autoAddTagToRumallOrder Job1 at $start_time";
        RumallOrderHelper::cronAutoAddTagToRumallOrder($job_id=1);
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "\n cron service stops for Rumall autoAddTagToRumallOrder Job1 at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagToRumallOrderJob1

    /**
     * Rumall 平台订单自动检测并添加小老板平台tag。
     * 由cron call 起来，会对所有绑定的Rumall账号进行轮询
     * @access static
     * @invoking					./yii rumall/auto-add-tag-to-rumall-order-job2
     * @author	lzhl	2015/12/25	初始化
     **/
    public function actionAutoAddTagToRumallOrderJob2() {
        $start_time = date('Y-m-d H:i:s');
        echo "\n cron service runnning for Rumall autoAddTagToRumallOrder Job2 at $start_time";
        RumallOrderHelper::cronAutoAddTagToRumallOrder($job_id=2);
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "\n cron service stops for Rumall autoAddTagToRumallOrder Job2 at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagToRumallOrderJob2

    /**
     * Rumall 后台统计用户每日CD订单数。
     * 由cron call 起来，会对所有绑定的Rumall账号进行轮询
     * @access static
     * @invoking					./yii rumall/user-rumall-order-daily-summary
     * @author	lzhl	2016/01/15	初始化
     **/
    public function actionUserRumallOrderDailySummary() {
        $start_time = date('Y-m-d H:i:s');
        echo "\n cron service runnning for Rumall UserRumallOrderDailySummary at $start_time";
        RumallOrderInterface::cronRumallOrderDailySummary($start_time);
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "\n cron service stops for Rumall UserRumallOrderDailySummary at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagToRumallOrderJob2

    /**
     * Rumall 平台订单自动检测标记发货是否成功。
     * 由cron call 起来，会对所有绑定的Rumall 用户进行轮询
     * @access static
     * @invoking					./yii rumall/hc-rumall-order-sing-shipped
     * @author	lzhl	2016/3/22	初始化
     **/
    public function actionHcRumallOrderSingShipped() {
        $start_time = date('Y-m-d H:i:s');
        //每20分钟执行一次就可以   2015-06-05 11:55:00
        $miniutes = substr ( $start_time, 14, 2 );
        if ($miniutes % 30 <> 0)
            return;
         
        echo "\n cron service runnning for Hc Rumall Order Sing Shipped at $start_time";
        RumallInterface_Helper::hcRumallOrderSingShipped();
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "\n cron service stops for Hc Rumall Order Sing Shipped at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }//end of function HcRumallOrderSingShipped


    /**
     * Rumall data conversion job
     * @invoking					./yii rumall/conversion-job
     * @author	lzhl	2016/3/22	初始化
     **/
    public function actionConversionJob(){
        $start_time = date('Y-m-d H:i:s');
        echo "\n cron service runnning for Hc Rumall Order Sing Shipped at $start_time";
        //商品图片和url conversion:
        RumallOrderInterface::prodInfoConversion();
         
         
         
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "\n cron service stops for Hc Rumall Order Sing Shipped at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }
    
    /**
     * Rumall 的sync表把type=2的unshipped获取任务，重置为0，让系统重新获取所有unshippped的，避免获取new的job有遗漏
     * 由cron call 起来，会对所有绑定的Rumall账号进行轮询
     * @access static
     * @invoking					./yii rumall/active-all-shop-to-get-unshipped-order
     * @author	yzq	2016/10/19	初始化
     **/
    public function actionActiveAllShopToGetUnshippedOrder() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Rumall active-all-shop-to-get-unshipped-order at $start_time";
    	RumallOrderHelper::activeAllShopToGetUnshippedOrder();
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Rumall active-all-shop-to-get-unshipped-order at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
            echo $comment;
            \Yii::info(['Rumall',__CLASS__,__FUNCTION__,'Background',$comment],"file");
            TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagToRumallOrderJob2
    
}