<?php
 
namespace console\controllers;
 
use yii;
use yii\console\Controller;
use \eagle\modules\tracking\helpers\TrackingAgentHelper;
use \eagle\modules\tracking\helpers\TrackingHelper;
use \eagle\modules\util\helpers\ImageHelper;
use \eagle\modules\util\helpers\SQLHelper;
use eagle\modules\carrier\apihelpers\PDFQueueHelper;
use eagle\modules\tracking\helpers\TrackingQueueHelper;
use yii\base\Exception;
use eagle\modules\dash_board\helpers\DashBoardHelper;
/**
 * Test controller
 */
class FulfillController extends Controller {
	/*
	* @invoking					./yii tracking/test
	* */	
	public function actionTest() {
		echo "sdfsdf";
	}

    /**
     +---------------------------------------------------------------------------------------------
     *  此进程会获取批量MainQueueTask，然后判断分配给哪些 mainQueueHandler。
     *  同时这个job负责load所有user库的信息到redis，让mainQueueHandler直接resig获取
		1 个active的进程，2个standby
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return						array('success'=true,'message'='')
     *
     * @invoking					./yii fulfill/route-manager
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/12/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionRouteManager() {
    	global $CACHE;
    	$start_time = date('Y-m-d H:i:s');
    	$CACHE['jobStartTime'] = $start_time;
    	echo "cron service runnning for pdf RouteManager at $start_time \n";
    	
    	DashBoardHelper::WatchMeUp('PDFRouteManager',45,'akirametero@vip.qq.com');
    	do{
    	$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);
    	 
    	PDFQueueHelper::routeManager();
    	 
    	$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
    	$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
    
    	$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
    	//\Yii::info("-----------multiple_process_sub end subjobid=$JOBID,tt=".($start2_time-$start1_time));
    	usleep(300*1000);
    	}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
    	//submit 使用的external call 统计数
   
    	//还需要在queue 里面注册自己要下班了
    	PDFQueueHelper::routeManagerResign();
    	DashBoardHelper::WatchMeDown();
    	echo "cron service stops for pdf RouteManager at ".date('Y-m-d H:i:s');
    }//end of function 
    
    /**
     +---------------------------------------------------------------------------------------------
     *  此进程会获取批量MainQueueTask，然后判断分配给哪些 mainQueueHandler。
     *  同时这个job负责load所有user库的信息到redis，让mainQueueHandler直接resig获取
     1 个active的进程，2个standby
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return						array('success'=true,'message'='')
     *
     * @invoking					./yii fulfill/pdf-handler
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/12/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionPdfHandler() {
    	global $CACHE;
    	$start_time = date('Y-m-d H:i:s');
    	$CACHE['jobStartTime'] = $start_time;
    	echo "cron service runnning for PdfHandler at $start_time \n";
    	 
    	DashBoardHelper::WatchMeUp('PdfHandler',45,'akirametero@vip.qq.com');
    	do{
        	$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);
    
        	PDFQueueHelper::pdfJobHandler();
    
        	$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
        	$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
    
        	//$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
        	//\Yii::info("-----------multiple_process_sub end subjobid=$JOBID,tt=".($start2_time-$start1_time));
        	///echo "sleep 600 ms and wait for next task \n";
        	usleep(300*1000);
        	
        	if ($start_time < $half_hour_ago) //如果运行了超过30分钟，退出,执行的job会进行优雅退出
        		PDFQueueHelper::$HANDLER_SHOULD_EXIT = true;
        	
        	}while (1); 
        	//submit 使用的external call 统计数
        	 
        	 
        	echo "this should not shown cron service stops for pdf Handler at ".date('Y-m-d H:i:s');
    }//end of function
    
    
    
}