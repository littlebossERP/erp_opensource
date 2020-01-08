<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use \eagle\modules\listing\helpers\CdiscountHelper;
use \eagle\modules\listing\helpers\CdiscountOfferTerminatorHelper;
use \eagle\modules\order\helpers\CdiscountOrderHelper;
use \eagle\modules\util\helpers\SysLogHelper;
use common\api\cdiscountinterface\CdiscountInterface_Helper;
use eagle\modules\order\models\CdiscountOrder;
use eagle\modules\listing\helpers\CdiscountOfferSyncHelper;
use eagle\modules\html_catcher\helpers\HtmlCatcherHelper;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\listing\models\CdiscountOfferList;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;

/**
 * Test controller
 */
class CdiscountController extends Controller {
	/**
	 * @invoking					./yii cdiscount/test
	 */
	public function actionTest() {
		$uid=1;
		$userStoreName='xxx@gmail.com';
		$orderId=['1509991421CBV78'];
		$createtime= date('Y-m-d\TH:i:s',strtotime("-20 days"));
		$endtime= date('Y-m-d\TH:i:s');
		$status=['Open','Closed'];
		$result_1=CdiscountOrderHelper::getOrderClaimList($uid, $userStoreName,$orderId,$createtime,$endtime,$status);
		$result_2=CdiscountOrderHelper::getOrderQuestionList($uid, $userStoreName,$orderId,$createtime,$endtime,$status);
		echo "\n getOrderClaimList result:\n";
		print_r($result_1);
		echo "\n getOrderQuestionList result:\n";
		print_r($result_2);
		exit();
	}

	/**
	 * @invoking					./yii cdiscount/test_2
	 */
	public function actionTest_2() {
		$uid=1;
		$userStoreName='xxx@gmail.com';
		$orderId='1509991421CBV78';
		$result_1=CdiscountOrderHelper::getDiscussionMailList($uid, $userStoreName,$orderId);
		echo "\n GenerateDiscussionMailGuid result:\n";
		print_r($result_1);

		exit();
	}
	
	/**
	 * @invoking					./yii cdiscount/test_3
	 */
	public function actionTest_3() {
		//$SellerProductIdList=json_encode( ['B12000000011259','B009CQO4EG','B00CAL71GS'] );
		$SellerProductIdList='B009CQO4EG';
		//$SellerProductIdList = '';
		$result_1=CdiscountOfferSyncHelper::GetOfferList($SellerProductIdList);
		echo "\n GetOfferList result:\n";
		print_r($result_1);
		exit();
	}
	/**
	 * @invoking					./yii cdiscount/test_4
	 */
	public function actionTest_4() {
		echo "\n start cronGetOfferList \n";
		CdiscountOfferSyncHelper::cronGetOfferList();
	}
	
	/**
	 * @invoking					./yii cdiscount/test_5
	 */
	public function actionTest_5() {
		echo "\n start AccepteOrRefuseOrders \n";
		$uid = 1;
		$storeName='';
		$orderIds=[];
		$state='Accept';
		$rtn = CdiscountOrderHelper::AccepteOrRefuseOrders($uid, $storeName , $orderIds , $state);
	}
	
	/**
	 * @invoking					./yii cdiscount/set-rank
	 */
	public function actionSetRank() {
	    echo "\n start AccepteOrRefuseOrders \n";
	    $puid = 1;
	    $rank = 6;
	    $rtn = CdiscountAccountsApiHelper::setCdTerminatorVipRank($puid, $rank);
	    
	    print_r($rtn) ;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * cd offer 跟卖终结者，提交manager， 有High Priority 和 Low Priority 2个job
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					./yii cdiscount/cd-offer-terminator-commit-h-p
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCdOfferTerminatorCommitHP() {
		$start_time = date('Y-m-d H:i:s');
		echo "start service runnning for action CdOfferTerminaterCommitHP at $start_time";
	
		$rtn = CdiscountOfferTerminatorHelper::commitQueueHandler("HP");
	
		$start_time = date('Y-m-d H:i:s');
		echo "end service runnning for action CdOfferTerminaterCommitHP at $start_time";
	}//end of function 
	
	
	// @invoking					./yii cdiscount/cd-offer-terminator-commit-l-p
	public function actionCdOfferTerminatorCommitLP() {
		$start_time = date('Y-m-d H:i:s');
		echo "start service runnning for action CdOfferTerminaterCommitLP at $start_time";
	
		$rtn = CdiscountOfferTerminatorHelper::commitQueueHandler("LP");
	
		$start_time = date('Y-m-d H:i:s');
				echo "end service runnning for action CdOfferTerminaterCommitLP at $start_time";
	}//end of function	
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * cd offer 跟卖终结者，大概每个小时起来看看有没有已关注的需要刷新的
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					./yii cdiscount/cd-offer-terminator-followed-refresh
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCdOfferTerminatorFollowedRefresh() {
		$start_time = date('Y-m-d H:i:s');
		echo "start service runnning for action CdOfferTerminaterCommitHP at $start_time";
	
		$rtn = CdiscountOfferTerminatorHelper::CheckFollowedProducts();
		$rtn = CdiscountOfferTerminatorHelper::CheckHotsaleProducts();
		$start_time = date('Y-m-d H:i:s');
				echo "end service runnning for action CdOfferTerminaterCommitHP at $start_time";
	}//end of function	

// @invoking					./yii cdiscount/cd-offer-terminator-normal-refresh	
	public function actionCdOfferTerminatorNormalRefresh() {
		$start_time = date('Y-m-d H:i:s');
		echo "start service runnning for action CdOfferTerminaterCommitHP at $start_time";
	
		$rtn = CdiscountOfferTerminatorHelper::CheckNormalProducts();
	
		$start_time = date('Y-m-d H:i:s');
				echo "end service runnning for action CdOfferTerminaterCommitHP at $start_time";
	}//end of function
	
	
	/*
	 * 跟卖情况 每日统计
	 * @invoking					./yii cdiscount/terminator-daily-statistics	
	 */
	public function actionTerminatorDailyStatistics(){
		$start_time = date('Y-m-d H:i:s');
		echo "\n start service runnning for action TerminatorDailyStatistics at $start_time";
		
		$rtn = CdiscountOfferTerminatorHelper::TerminatorDailyStatistics();
		
		$end_time = date('Y-m-d H:i:s');
		echo "\n end service runnning for action CdOfferTerminaterCommitHP at $end_time";
	}
	
    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount API Request 处理器。支持多进程一起工作
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
     * @invoking					./yii cdiscount/do-api-queue-request-order-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/2/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionDoApiQueueRequestOrderEagle2() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "cron service runnning for Cdiscount DoQueueReqeustOrder at $start_time";
    	 
    	do{
	    	$rtn = CdiscountOrderHelper::cronQueueHandlerExecuteCdiscountOrderOp();
	    
	    	//如果没有需要handle的request了，退出
	    	if ($rtn['success'] and $rtn['message']=="n/a"){
	    	sleep(10);
	    	//echo "cron service Cdiscount DoApiQueueRequestOrderEagle2,no reqeust pending, sleep for 10 sec"; 
	    	}
	    	 
	    	$auto_exit_time = 25 + rand(1,10); // 25 - 35 minutes to leave
	    	$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
    
    	}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
    	TrackingAgentHelper::extCallSum("");
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount DoApiQueueRequestOrder at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        \Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }//end of function actionDo17TrackQuery
    
    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job0。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job00
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
	public function actionFetchRecentOrderListEagle2Job00() {
    	$start_time = date('Y-m-d H:i:s');
		$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=0;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);

    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
			$error_message = $record['error_message'];
	    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
	    	}else{
	    		$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
	    		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
	    	}
			$command = \Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
    	}

    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	TrackingAgentHelper::extCallSum("");
        \Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job1。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job01
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job01() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=1;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
			$error_message = $record['error_message'];
	    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
	    	}else{
	    		$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
	    		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
	    	}
			$command = \Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
    	}
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    		echo $comment;
    	TrackingAgentHelper::extCallSum("");
        \Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job2。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job02
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job02() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=2;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
   		$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
			$error_message = $record['error_message'];
	    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
	    	}else{
	    		$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
	    		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
	    	}
			$command = \Yii::$app->db_queue->createCommand( $sql );
			$affect_record = $command->execute();
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
    	}
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
        	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job3。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job03
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job03() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=3;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
    		$error_message = $record['error_message'];
    		if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    		}else{
    			$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
	    	}
			$command = \Yii::$app->db_queue->createCommand( $sql );
    		$affect_record = $command->execute();
    		if(empty($affect_record))
    			echo "\n set runtime log falied, sql:".$sql;
    	}
    			 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
       TrackingAgentHelper::extCallSum("");
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job4。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job04
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job04() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=4;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
    		$error_message = $record['error_message'];
    		if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    		}else{
    			$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
	    	}
			$command = \Yii::$app->db_queue->createCommand( $sql );
    		$affect_record = $command->execute();
    		if(empty($affect_record))
    			echo "\n set runtime log falied, sql:".$sql;
    	}
    			 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job5。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job05
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job05() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=5;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
    		$error_message = $record['error_message'];
    		if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    		}else{
    			$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    		$command = \Yii::$app->db_queue->createCommand( $sql );
    		$affect_record = $command->execute();
    		if(empty($affect_record))
    			echo "\n set runtime log falied, sql:".$sql;
		}
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job6。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job06
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job06() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=6;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
    		$error_message = $record['error_message'];
    		if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    		}else{
    			$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    		$command = \Yii::$app->db_queue->createCommand( $sql );
    		$affect_record = $command->execute();
    		if(empty($affect_record))
    			echo "\n set runtime log falied, sql:".$sql;
    	}
    			 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job7。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job07
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job07() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=7;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    		$run_times = [];
    		$error_message = $record['error_message'];
    		if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    		}else{
    			$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    		$command = \Yii::$app->db_queue->createCommand( $sql );
    		$affect_record = $command->execute();
    		if(empty($affect_record))
    			echo "\n set runtime log falied, sql:".$sql;
    	}
    			 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job8。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job08
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job08() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=8;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
    		$error_message = $record['error_message'];
    		if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    		}else{
    			$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    		$command = \Yii::$app->db_queue->createCommand( $sql );
    		$affect_record = $command->execute();
    		if(empty($affect_record))
    			echo "\n set runtime log falied, sql:".$sql;
    	}
    			 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job9。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job09
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job09() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=9;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    	$record = $command->queryOne();
    	if(!empty($record)){
    		$run_times = json_decode($record['addinfo2'],true);
    		if(!is_array($run_times))
    			$run_times = [];
    		$error_message = $record['error_message'];
    		if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        		$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    		}else{
    			$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    		$command = \Yii::$app->db_queue->createCommand( $sql );
    		$affect_record = $command->execute();
    		if(empty($affect_record))
    			echo "\n set runtime log falied, sql:".$sql;
    	}
    			 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job10。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job10
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job10() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=10;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    		$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job11。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job11
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job11() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=11;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    		$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job12。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job12
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job12() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=12;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    			$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job13
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job13
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job13() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=13;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    			$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job14
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job14
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job14() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=14;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    			$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job15。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job15
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job15() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=15;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    			$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job16。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job16
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job16() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=16;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    			$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job17。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job17
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job17() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=17;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    			$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job18。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job18
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job18() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=18;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    			$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    

    /**
     +---------------------------------------------------------------------------------------------
     * Cdiscount 平台订单获取 job19。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii cdiscount/fetch-recent-order-list-eagle2-job19
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2016/7/28	初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentOrderListEagle2Job19() {
    	$start_time = date('Y-m-d H:i:s');
    	$the_day = date('Y-m-d');
    	echo "\n cron service runnning for Cdiscount cronAutoFetchUnFulfilledOrderList at $start_time";
    	$job_id=19;
    	$rtn = CdiscountOrderHelper::cronAutoFetchRecentOrderList($job_id,$the_day);
    
    	$command = \Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' "  );
    			$record = $command->queryOne();
    			if(!empty($record)){
    			$run_times = json_decode($record['addinfo2'],true);
    			if(!is_array($run_times))
    	$run_times = [];
    	$error_message = $record['error_message'];
    	if (!$rtn['success']){
    			$run_times['end_times'] = empty($run_times['end_times'])?0 : $run_times['end_times'];
        			$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."',`error_message`='".$error_message.$rtn['message'].' @ '.date("Y-m-d H:i:s",time())."; '  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."' ";
    	}else{
    	$run_times['end_times'] = empty($run_times['end_times'])?1 : $run_times['end_times']+1;
    	$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."', `addinfo2`='".json_encode($run_times)."'  where app='CDOMS' and info_type='runtime-GetOrder' and `addinfo`='job_id=".$job_id."' and `the_day`='".$the_day."'  ";
    		}
    			$command = \Yii::$app->db_queue->createCommand( $sql );
    	$affect_record = $command->execute();
    	if(empty($affect_record))
    		echo "\n set runtime log falied, sql:".$sql;
    			}
    
    			//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    			$comment =  "cron service stops for Cdiscount cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    					\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    					TrackingAgentHelper::extCallSum("");
    }
    
	/**
	 +---------------------------------------------------------------------------------------------
	 * Cdiscount 平台产品(offer)获取。
	 +---------------------------------------------------------------------------------------------
	 * @invoking					./yii cdiscount/get-cdiscount-offer-list-eagle2
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015/7/2	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetCdiscountOfferListEagle2() {
		//return true;//占用较多数据库io，用其他方式替换此function
		$runtimes=0;
		 
		$rtn = CdiscountOfferSyncHelper::cronGetOfferList();
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "cron service stops for cdiscount GetCdiscountOfferListEagle2 at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		\Yii::info(['cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
		TrackingAgentHelper::extCallSum("");
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * @invoking					./yii cdiscount/get-cdiscount-offer-list-paginated
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetCdiscountOfferListPaginated() {
		$start_time = date('Y-m-d H:i:s');
		echo "cron service runnning for cdiscount GetCdiscountOfferListPaginated at $start_time";
			
		CdiscountOfferSyncHelper::cronGetOfferListPaginated();
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "cron service stops for cdiscount GetCdiscountOfferListPaginated at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		\Yii::info(['cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
		TrackingAgentHelper::extCallSum("");
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * cdiscount 平台新绑定的帐号订单获取。
	 * 由cron call 起来，会对所有绑定的cdiscount账号进行轮询，获取order
	 +---------------------------------------------------------------------------------------------
	 * @invoking					./yii cdiscount/fetch-new-account-orders-eagle2
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/7/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionFetchNewAccountOrdersEagle2() {
	
		$start_time = date('Y-m-d H:i:s');
		echo "cron service runnning for cdiscount FetchNewAccountOrders at $start_time";
	
		$rtn = CdiscountOrderHelper::cronAutoFetchNewAccountOrderList();
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "cron service stops for cdiscount FetchNewAccountOrders at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
	    	echo $comment;
	    	\Yii::info(['cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
	    	TrackingAgentHelper::extCallSum("");
    }//end of function FetchNewAccountOrdersEagle2

    /**
     * Cdiscount 平台订单自动检测并添加小老板平台tag。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询
     * @access static
     * @invoking					./yii cdiscount/auto-add-tag-to-cdiscount-order-job0
     * @author	lzhl	2015/12/25	初始化
     **/
    public function actionAutoAddTagToCdiscountOrderJob0() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Cdiscount autoAddTagToCdiscountOrder Job0 at $start_time";
    	CdiscountOrderHelper::cronAutoAddTagToCdiscountOrder($job_id=0);
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Cdiscount autoAddTagToCdiscountOrder Job0 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagToCdiscountOrderJob0
    
    /**
     * Cdiscount 平台订单自动检测并添加小老板平台tag。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询
     * @access static
     * @invoking					./yii cdiscount/auto-add-tag-to-cdiscount-order-job1
     * @author	lzhl	2015/12/25	初始化
     **/
    public function actionAutoAddTagToCdiscountOrderJob1() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Cdiscount autoAddTagToCdiscountOrder Job1 at $start_time";
    	CdiscountOrderHelper::cronAutoAddTagToCdiscountOrder($job_id=1);
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Cdiscount autoAddTagToCdiscountOrder Job1 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagToCdiscountOrderJob1
    
    /**
     * Cdiscount 平台订单自动检测并添加小老板平台tag。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询
     * @access static
     * @invoking					./yii cdiscount/auto-add-tag-to-cdiscount-order-job2
     * @author	lzhl	2015/12/25	初始化
     **/
    public function actionAutoAddTagToCdiscountOrderJob2() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Cdiscount autoAddTagToCdiscountOrder Job2 at $start_time";
    	CdiscountOrderHelper::cronAutoAddTagToCdiscountOrder($job_id=2);
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Cdiscount autoAddTagToCdiscountOrder Job2 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagToCdiscountOrderJob2
    
    /**
     * Cdiscount 后台统计用户每日CD订单数。
     * 由cron call 起来，会对所有绑定的Cdiscount账号进行轮询
     * @access static
     * @invoking					./yii cdiscount/user-cdiscount-order-daily-summary
     * @author	lzhl	2016/01/15	初始化
     **/
    public function actionUserCdiscountOrderDailySummary() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Cdiscount UserCdiscountOrderDailySummary at $start_time";
    	CdiscountOrderInterface::cronCdiscountOrderDailySummary($start_time);
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Cdiscount UserCdiscountOrderDailySummary at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        	TrackingAgentHelper::extCallSum("");
    }//end of function AutoAddTagToCdiscountOrderJob2
    
    /**
     * Cdiscount 平台订单自动检测标记发货是否成功。
     * 由cron call 起来，会对所有绑定的Cdiscount 用户进行轮询
     * @access static
     * @invoking					./yii cdiscount/hc-cdiscount-order-sing-shipped
     * @author	lzhl	2016/3/22	初始化
     **/
    public function actionHcCdiscountOrderSingShipped() {
    	$start_time = date('Y-m-d H:i:s');
    	//每20分钟执行一次就可以   2015-06-05 11:55:00
    	$miniutes = substr ( $start_time, 14, 2 );
    	if ($miniutes % 30 <> 0)
    		return;
    	
    	echo "\n cron service runnning for Hc Cdiscount Order Sing Shipped at $start_time";
    	CdiscountInterface_Helper::hcCdiscountOrderSingShipped();
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Hc Cdiscount Order Sing Shipped at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        	TrackingAgentHelper::extCallSum("");
    }//end of function HcCdiscountOrderSingShipped
    
    
    /**
     * Cdiscount data conversion job
     * @invoking					./yii cdiscount/conversion-job
     * @author	lzhl	2016/3/22	初始化
     **/
    public function actionConversionJob(){
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Hc Cdiscount Order Sing Shipped at $start_time";
    	//商品图片和url conversion:
    	CdiscountOrderInterface::prodInfoConversion();
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Hc Cdiscount Order Sing Shipped at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }
    
    
    //./yii cdiscount/move-job
    public function actionMoveJob(){
    	$start_time = date('Y-m-d H:i:s');
    	
    	CdiscountOfferTerminatorHelper::moveHcQueue();
    }
    
    /**
     * @invoking					./yii cdiscount/count-offer
     **/
    public function actionCountOffer(){
    	$totla_offer = 0;
    	
    	$uids = SaasCdiscountUser::find()->distinct(true)->select("uid")->all();
    	foreach ($uids as $uid){
    		$uid = $uid->uid;
    		if(empty($uid))
    			continue;
    		 
    		echo "\n start to move data for uid=$uid;";
    		 
    		$offers = CdiscountOfferList::find()->where("1=1")->asArray()->count();
    		echo "\n uid:$uid have $offers offers;";
    		$totla_offer +=$offers;
    	}
    	echo "\n total offers :$totla_offer";
    }
    
    /**
     * @invoking					./yii cdiscount/data-fix
     **/
    public function actionDataFix(){
    	$accounts = SaasCdiscountUser::find()->where("1")->orderBy("uid ASC")->all();
    	echo "\n get ".count($accounts)." accounts;";
    	$user_sellers=[];
    	foreach ($accounts as $account){
    		if(empty($account->uid) || empty($account->username))
    			continue;
    		$user_sellers[(string)$account->uid][] = $account->username;
    	}
    	
    	foreach ($user_sellers as $uid=>$sellers){
    		
    		if(empty($uid))
    			continue;
    		 
    		echo "\n start to do data fix uid=$uid;";
    		//CdiscountOfferSyncHelper::DataFix_TerminatorHistory($uid);
    		CdiscountOfferSyncHelper::DataFix_OfferList($uid,$sellers);
    	}
    	echo "\n end foreach uid;";
    }
    
    //./yii cdiscount/data-fix-ys
    public function actionDataFixYs(){
    	$command = \Yii::$app->db->createCommand("select distinct uid from saas_cdiscount_user  ");
    	$rows = $command->queryAll();
    	foreach ($rows as $row){
	    	echo "\n start to do data fix  ;".date('Y-m-d H:i:s')."\n";
	    	CdiscountOfferSyncHelper::purgeRedundantCdiscountOfferList($row['uid']);
	    	echo "\n finished doing data fix  ;".date('Y-m-d H:i:s')."\n";
    	}
    }
    
    /*
     * @invoking					./yii cdiscount/get-non-img-offer-info
     */
    public function actionGetNonImgOfferInfo(){
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for cdiscount/get-non-img-offer-info at $start_time";
    	
    	$uids = SaasCdiscountUser::find()->distinct(true)->select("uid")->all();
    	foreach ($uids as $uid){
    		$uid = $uid->uid;
    		if(empty($uid))
    			continue;
    		 
    		echo "\n start to get-non-img-offer-info for uid=$uid;";
    		
    		$message='';
    		$nonImgOffer = CdiscountOfferList::find()->select("product_id")->where(['img'=>null])->andwhere("`parent_product_id` is null or `parent_product_id` like '%@attributes%' ")->asArray()->all();
    		$count=0;
    		$scuuessCount = 0;
    		foreach ($nonImgOffer as $offer){
    			$prod_id = $offer['product_id'];
    		
    			$rtn = CdiscountOfferSyncHelper::syncProdInfoByAdmin($uid,$prod_id,$priority=1);
    			if(empty($rtn['success']))
    				$message.=empty($rtn['message'])?'':$rtn['message'];
    			else {
    				$scuuessCount++;
    				print_r($rtn);
    			}
    			$count++;
    		}
    		echo '<br>find '.$count.'products,successed put to queue count '.$scuuessCount.';';
    		if(is_string($message))
    			echo $message;
    		else
    			print_r($message,true);
    		 
    	}
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for cdiscount/get-non-img-offer-info at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    }
    
    /*
     * @invoking					./yii cdiscount/manual-fetch-order-eagle2
    */
    public function actionManualFetchOrderEagle2(){
    	$start_time = date('Y-m-d H:i:s');
    	echo "background service runnning for cdiscount/manual-fetch-order-eagle2 at $start_time";

    	do{
    		//$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);
    		$rtn = CdiscountOrderHelper::cronManualFetchOrderByCreationdate();
    		
    		//如果没有需要handle的request了，退出
    		if ($rtn=="n/a"){
    			sleep(4);
    			//echo "cron service Do17TrackQuery,no reqeust pending, sleep for 4 sec";
    		}
    		
    		$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
    		$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
    		
    		//$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000); 	
    	}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n background service stops for cdiscount/manual-fetch-order-eagle2 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	//submit 使用的external call 统计数
    	TrackingAgentHelper::extCallSum('',0,true);
    }
    
    /**
     * Cdiscount 跟卖终结者 自动定期发送bestseller被抢提醒。
     * 由cron call 起来，会对所有绑定的Cdiscount 用户进行轮询
     * @access static
     * @invoking					./yii cdiscount/auto-send-terminator-announce
     * @author	lzhl	2016/11/29	初始化
     **/
    public function actionAutoSendTerminatorAnnounce() {
    	$start_time = date('Y-m-d H:i:s');
    	//每20分钟执行一次就可以   2015-06-05 11:55:00
    	$miniutes = substr ( $start_time, 14, 2 );
    	if ($miniutes % 20 <> 0){
    		echo "\n not in the target time,skip";
    		return;
    	}
    	echo "\n cron service runnning for Auto Send Terminator Announce at $start_time";
    	\eagle\modules\listing\helpers\CdiscountOfferTerminatorHelper::cronSendTerminatorAnnounceToVip();
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Auto Send Terminator Announce at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        \Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
        TrackingAgentHelper::extCallSum("");
    }//end of function HcCdiscountOrderSingShipped
    
    
    /*
     * @invoking					./yii cdiscount/conversion-job-by-uid
    */
    public function actionConversionJobByUid(){
    	$start_time = date('Y-m-d H:i:s');
    	
    	$uid=1;
    	echo "\n cron service runnning for  actionConversionJobByUid for uid:$uid at $start_time";
    	 
    	DashBoardHelper::initCountPlatformOrderStatus($uid);
    	DashBoardHelper::initSalesCount($uid,365);
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Hc Cdiscount Order Sing Shipped at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Cdiscount',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }
    
    /*
     * @invoking					./yii cdiscount/conversion-job2
    */
    public function actionConversionJob2(){
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for ConversionJob2 for all user at $start_time";
    	
    	$sql = "select uid from user_base where 1 ";
    	$command = \Yii::$app->db->createCommand( $sql );
    	$rows = $command->queryAll();
    	foreach ($rows as $row){
    		DashBoardHelper::initCountPlatformOrderStatus((int)$row['uid']);
    		DashBoardHelper::initSalesCount((int)$row['uid'], 365);
    	}
    	
    	echo "\n stop runnning for ConversionJob2";
    }
    
    /*
     * 统计最近15日所有用户订单平均售出数据
     * @invoking					./yii cdiscount/init-job
    */
    public function actionInitJob(){
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Init Job for all user at $start_time";
    	 
    	//step 1: Load 进来这15天每一个puid在不同app的是否活跃
    	
    	$redisData =  RedisHelper::RedisGet('tempJob',"puidAppKey20170328" );
    	if (empty($redisData)){
	    	$query_start_time = date("Y-m-d",strtotime("-17 days"));
	    	$query_end_time = date("Y-m-d",strtotime("-2 days"));
	    	
	    	$sql = "SELECT distinct puid, app_key  FROM `app_user_action_log` WHERE  `log_time` between 
	    			'$query_start_time' and '$query_end_time' ";
	    	
	    	$command = \Yii::$app->db->createCommand( $sql );
	    	$rows = $command->queryAll();
	    	$hasActionForPuidVsApp=[];
	    	foreach ($rows as $row){
	    		$hasActionForPuidVsApp[strtolower($row['app_key']) ]['p'.$row['puid']] = 1;
	    	}
	    	RedisHelper::RedisSet('tempJob',"puidAppKey20170328",json_encode($hasActionForPuidVsApp) );
    	}else
    		$hasActionForPuidVsApp = json_decode($redisData,true);
    	
    	//step 2:对每一个puid库进行订单统计并写入结果表
    	$sql = "select uid from user_base where puid=0 ";
    	$command = \Yii::$app->db->createCommand( $sql );
        $rows = $command->queryAll();
        foreach ($rows as $row){
        	$rtn = DashBoardHelper::Last2_to_17DaysSalesCount($row['uid'] , $hasActionForPuidVsApp);
        	if(!$rtn['success']){
        		echo "\n ".$rtn['message'];
        	}
    	}
    	 
    	echo "\n stop runnning for Init Job";
    }
    
    /*
    * 检查哪些CD用户有 有问题 订单
    * @invoking					./yii cdiscount/cd-problem-order-check
    */
    public function actionCdProblemOrderCheck(){
    	$users = SaasCdiscountUser::find()->distinct(true)->select('uid')->where(['is_active'=>1])
    		->andWhere("`token_expired_date` >'".date("Y-m-d H:i:s",time()-3600*3*24 )."'" )->asArray()->all();
    	echo "\n total ".count($users)." active CD users.";
    	foreach ($users as $uid){
    		$uid = (int)$uid['uid'];
    		echo "\n $uid check start;";
    		 
    		
    		//检查某时段后的订单
    		
    		$command_0 = \Yii::$app->subdb->createCommand("delete  FROM `cdiscount_order` WHERE `ordernumber` not in (select order_source_order_id from od_order_v2 where order_source='cdiscount') ");
    		$del_0 = $command_0->execute();
    		continue;
    		if(!empty($del_0))
    			echo "\n user:$uid has problem src order, del $del_0 src orders;";
    		$command = \Yii::$app->subdb->createCommand("delete  FROM `od_order_v2` WHERE `order_id` not in (select order_id from od_order_item_v2 where 1) and order_source='cdiscount' and create_time>1490760000 ");
			$del = $command->execute();
			if($del>0){
				echo "\n user:$uid has $del problem order ,";
				$accounts = SaasCdiscountUser::find()->where(['is_active'=>1,'uid'=>$uid])
    				->andWhere("`token_expired_date` >'".date("Y-m-d H:i:s",time()-3600*3*24 )."'" )->all();
				foreach ($accounts as $account){
					if($account->sync_status=='R'){
						echo "\n ##### id=".$account->site_id." is running fetch,need to update manual!" ;
					}else{
						$account->sync_status = 'C';
						$account->last_order_success_retrieve_time = '2017-03-29 12:00:00';
						$account->save(false);
					}
				}
			}
    	}
    }
	
	//./yii cdiscount/data-fix-liang
    public function actionDataFixLiang(){
    	$command = \Yii::$app->db->createCommand("select distinct uid from saas_cdiscount_user  ");
    	$rows = $command->queryAll();
    	foreach ($rows as $row){
	    	echo "\n start to do data fix  ;".date('Y-m-d H:i:s')."\n";
	    	CdiscountOfferSyncHelper::purgeUnbindedCdiscountOfferList($row['uid']);
	    	echo "\n finished doing data fix  ;".date('Y-m-d H:i:s')."\n";
    	}
    }
    
    //./yii cdiscount/check-call-open-api-state
    public function actionCheckCallOpenApiState(){
    	\eagle\modules\listing\helpers\CdiscountOfferTerminatorHelper::checkLastHourCallOpenApiState();
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}