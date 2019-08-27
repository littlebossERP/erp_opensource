<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\listing\helpers\PriceministerOfferSyncHelper;
use eagle\modules\order\helpers\PriceministerOrderHelper;
use eagle\modules\order\helpers\PriceministerOrderInterface;
use eagle\models\SaasPriceministerUser;
use eagle\modules\order\models\PriceministerOrderDetail;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\SQLHelper;
/**
 * Test controller
 */
class PriceministerController extends Controller {
	/**
	 * @invoking					./yii priceminister/test
	 */
	public function actionTest() {
	}

	/**
	 * @invoking					./yii priceminister/test_2
	 */
	public function actionTest_2() {
	}
    
    /**
     +---------------------------------------------------------------------------------------------
     * priceminister 平台订单获取 job0。
     * 由cron call 起来，会对所有绑定的priceminister账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii priceminister/fetch-recent-order-list-eagle2-job0
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/04/07	初始化
     +---------------------------------------------------------------------------------------------
     **/
	public function actionFetchRecentOrderListEagle2Job0() {
    	$start_time = date('Y-m-d H:i:s');
		$the_day = date('Y-m-d');
    	echo "\n cron service runnning for priceminister cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=0;
    	$rtn = PriceministerOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    	
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='PMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
			$error_message = $record['error_message'];
	    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='PMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
	    	}else{
	    		$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
	    		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='PMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
	    	}
			$command = \Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
    	}

    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for priceminister cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	TrackingAgentHelper::extCallSum("");
        \Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        echo "\n TrackingAgentHelper::extCallSum()";
    }

    /**
     +---------------------------------------------------------------------------------------------
     * priceminister 平台订单获取 job1。
     * 由cron call 起来，会对所有绑定的priceminister账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii priceminister/fetch-recent-order-list-eagle2-job1
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job1() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for priceminister cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=1;
    	$rtn = PriceministerOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='PMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
			$error_message = $record['error_message'];
	    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='PMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
	    	}else{
	    		$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
	    		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='PMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
	    	}
			$command = \Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
    	}
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for priceminister cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	TrackingAgentHelper::extCallSum("");
        \Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        echo "\n TrackingAgentHelper::extCallSum()";
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * priceminister 平台订单获取 job2。
     * 由cron call 起来，会对所有绑定的priceminister账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii priceminister/fetch-recent-order-list-eagle2-job2
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job2() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for priceminister cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=2;
    	$rtn = PriceministerOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
   		$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='PMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
			$error_message = $record['error_message'];
	    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='PMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
	    	}else{
	    		$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
	    		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='PMOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
	    	}
			$command = \Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
    	}
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for priceminister cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
    	\Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    	echo "\n TrackingAgentHelper::extCallSum()";
    }
    
	/**
	 +---------------------------------------------------------------------------------------------
	 * priceminister 平台产品(offer)获取。
	 +---------------------------------------------------------------------------------------------
	 * @invoking					./yii priceminister/get-priceminister-offer-list-eagle2
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015/7/2	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetpriceministerOfferListEagle2() {
		//return true;//占用较多数据库io，用其他方式替换此function
		$runtimes=0;
		 
		$rtn = PriceministerOfferSyncHelper::cronGetOfferList();
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "cron service stops for priceminister GetpriceministerOfferListEagle2 at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		\Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
		TrackingAgentHelper::extCallSum("");
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * priceminister 平台新绑定的帐号订单获取。
	 * 由cron call 起来，会对所有绑定的priceminister账号进行轮询，获取order
	 +---------------------------------------------------------------------------------------------
	 * @invoking					./yii priceminister/fetch-new-account-orders-eagle2
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/7/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionFetchNewAccountOrdersEagle2() {
	
		$start_time = date('Y-m-d H:i:s');
		echo "cron service runnning for priceminister FetchNewAccountOrders at $start_time";
	
		$rtn = PriceministerOrderHelper::cronAutoFetchNewAccountOrderList();
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "cron service stops for priceminister FetchNewAccountOrders at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
	    	echo $comment;
	    	\Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
	    	TrackingAgentHelper::extCallSum("");
    }//end of function FetchNewAccountOrdersEagle2

    /**
     * priceminister 平台订单自动检测并添加小老板平台tag。
     * 由cron call 起来，会对所有绑定的priceminister账号进行轮询
     * @access static
     * @invoking					./yii priceminister/auto-add-tag-to-priceminister-order-job0
     * @author	lzhl	2015/12/25	初始化
     **/
    public function actionAutoAddTagToPriceministerOrderJob0() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for priceminister autoAddTagToPriceministerOrder Job0 at $start_time";
    	PriceministerOrderHelper::cronAutoAddTagToPriceministerOrder($job_id=0);
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for priceminister autoAddTagToPriceministerOrder Job0 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagTopriceministerOrderJob0
    
    /**
     * priceminister 平台订单自动检测并添加小老板平台tag。
     * 由cron call 起来，会对所有绑定的priceminister账号进行轮询
     * @access static
     * @invoking					./yii priceminister/auto-add-tag-to-priceminister-order-job1
     * @author	lzhl	2015/12/25	初始化
     **/
    public function actionAutoAddTagToPriceministerOrderJob1() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for priceminister autoAddTagToPriceministerOrder Job1 at $start_time";
    	PriceministerOrderHelper::cronAutoAddTagToPriceministerOrder($job_id=1);
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for priceminister autoAddTagToPriceministerOrder Job1 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagTopriceministerOrderJob1
    
    /**
     * priceminister 平台订单自动检测并添加小老板平台tag。
     * 由cron call 起来，会对所有绑定的priceminister账号进行轮询
     * @access static
     * @invoking					./yii priceminister/auto-add-tag-to-priceminister-order-job2
     * @author	lzhl	2015/12/25	初始化
     **/
    public function actionAutoAddTagToPriceministerOrderJob2() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for priceminister autoAddTagToPriceministerOrder Job2 at $start_time";
    	PriceministerOrderHelper::cronAutoAddTagToPriceministerOrder($job_id=2);
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for priceminister autoAddTagToPriceministerOrder Job2 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagTopriceministerOrderJob2
    
    /**
     * priceminister 后台统计用户每日订单数。
     * 由cron call 起来，会对所有绑定的priceminister账号进行轮询
     * @access static
     * @invoking					./yii priceminister/user-priceminister-order-daily-summary
     * @author	lzhl	2016/01/15	初始化
     **/
    public function actionUserPriceministerOrderDailySummary() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for priceminister UserPriceministerOrderDailySummary at $start_time";
    	PriceministerOrderInterface::cronPriceministerOrderDailySummary($start_time);
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for priceminister UserPriceministerOrderDailySummary at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        	\Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        	TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagTopriceministerOrderJob2
    
    /**
     * priceminister 平台订单自动同步item状态。
     * @access static
     * @invoking					./yii priceminister/sync-priceminister-order-item-status-job0
     * @author	lzhl	2016/4/22	初始化
     **/
    public function actionSyncPriceministerOrderItemStatusJob0() {
    	$start_time = date('Y-m-d H:i:s');
    	
    	echo "\n cron service runnning for SyncPriceministerOrderItemStatus Job 0 at $start_time";
    	//先run手动同步
    	PriceministerOrderHelper::cronSyncOrderItemStatus($job_id=0,'U');
    	//再run自动同步，自动同步有6小时间隔
    	PriceministerOrderHelper::cronSyncOrderItemStatus($job_id=0,'A');
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for SyncPriceministerOrderItemStatus Job 0 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        	\Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        	TrackingAgentHelper::extCallSum("");
    }//end of function SyncPriceministerOrderItemStatusJob0
    
    /**
     * priceminister 平台订单自动同步item状态。
     * @access static
     * @invoking					./yii priceminister/sync-priceminister-order-item-status-job1
     * @author	lzhl	2016/4/22	初始化
     **/
    public function actionSyncPriceministerOrderItemStatusJob1() {
    	$start_time = date('Y-m-d H:i:s');
    	 
    	echo "\n cron service runnning for SyncPriceministerOrderItemStatus Job 1 at $start_time";
    	//先run手动同步
    	PriceministerOrderHelper::cronSyncOrderItemStatus($job_id=1,'U');
    	//再run自动同步，自动同步有6小时间隔
    	PriceministerOrderHelper::cronSyncOrderItemStatus($job_id=1,'A');
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for SyncPriceministerOrderItemStatus Job 1 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
        	echo $comment;
        	\Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        	TrackingAgentHelper::extCallSum("");
    }//end of function SyncPriceministerOrderItemStatusJob0
    
    /**
     * priceminister 平台订单自动同步item状态。
     * @access static
     * @invoking					./yii priceminister/sync-priceminister-order-item-status-job2
     * @author	lzhl	2016/4/22	初始化
     **/
    public function actionSyncPriceministerOrderItemStatusJob2() {
    	$start_time = date('Y-m-d H:i:s');
    	 
    	echo "\n cron service runnning for SyncPriceministerOrderItemStatus Job 2 at $start_time";
    	//先run手动同步
    	PriceministerOrderHelper::cronSyncOrderItemStatus($job_id=2,'U');
    	//再run自动同步，自动同步有6小时间隔
    	PriceministerOrderHelper::cronSyncOrderItemStatus($job_id=2,'A');
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for SyncPriceministerOrderItemStatus Job 2 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
        	echo $comment;
        	\Yii::info(['priceminister',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        	TrackingAgentHelper::extCallSum("");
    }//end of function SyncPriceministerOrderItemStatusJob0
    
    /**
     * @invoking					./yii priceminister/hc-order-status
     **/
    public function actionHcOrderStatus(){
    	$uids = SaasPriceministerUser::find()->distinct(true)->select("uid")->where(['is_active'=>1])->all();
    	foreach ($uids as $uid){
    		$uid = $uid->uid;
    		if(empty($uid))
    			continue;
    		 
    		echo "\n start to hc order status for uid=$uid;";
    		
    		$orders = OdOrder::find()->where(['order_source'=>'priceminister'])->andWhere("order_status<500")->all();
    		echo "\n query ".count($orders)." orders;";
    		$counter = 0;
    		foreach ($orders as $od){
    			$rtn = PriceministerOrderHelper::SyncOrderItemStatusByOrder($od->order_id,$uid);
    			if($rtn['success'])
    				$counter++;
    		}
    		echo "\n hc $counter orders;";
    	}
    }
    
    /**转移数据
     * @invoking					./yii priceminister/move-data
     **/
    public function actionMoveData(){
    	$uids = SaasPriceministerUser::find()->distinct(true)->select("uid")->all();
    	foreach ($uids as $uid){
    		$uid = $uid->uid;
    		if(empty($uid))
    			continue;
    		 
    		echo "\n start to move data for uid=$uid;";
    	
    		$command = \Yii::$app->subdb->createCommand("select * from priceminister_sync_order_item where 1"  );
    		$record = $command->queryAll();
    		$to_move_data = [];
    		foreach ($record as $r){
    			
    			unset($r['id']);
    			unset($r['create']);
    			unset($r['update']);
    			
    			$r['puid'] = $uid;
    			$to_move_data[] = $r;
    		}
    		SQLHelper::groupInsertToDb('priceminister_sync_order_item',$to_move_data,'db_queue' );
    	}
    }
    
    /*
     * @invoking					./yii priceminister/manual-fetch-order-eagle2
    */
    public function actionManualFetchOrderEagle2(){
    	$start_time = date('Y-m-d H:i:s');
    	echo "background service runnning for priceminister/manual-fetch-order-eagle2 at $start_time";
    
    	do{
    	//$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);
    		$rtn = PriceministerOrderHelper::cronManualFetchOrder();
    
    		//如果没有需要handle的request了，退出
    		if ($rtn=="n/a"){
	    		sleep(6);
	    		//echo "cron service Do17TrackQuery,no reqeust pending, sleep for 4 sec";
    		}
    		$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
    		$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
    
    		//$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
    	}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
    	 
    		//write the memery used into it as well.
    		$memUsed = floor (memory_get_usage() / 1024 / 1024);
    		$comment =  "\n background service stops for priceminister/manual-fetch-order-eagle2 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	//submit 使用的external call 统计数
    	TrackingAgentHelper::extCallSum('',0,true);
    }
}