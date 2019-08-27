<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use eagle\modules\html_catcher\helpers\HtmlCatcherHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;

/**
 * HtmlCatcher controller
			
 */
class HtmlCatcherController extends Controller {
	//测试controller 是否成功
	public function actionTest() {
		echo " this controller is ready !";
	}
 	
	private static $totalJobs = 1;
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info0
	public function actionCronCatchCdiscountProductInfo00(){ 
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=0);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info1
	public function actionCronCatchCdiscountProductInfo01(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=1);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info2
	public function actionCronCatchCdiscountProductInfo02(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=2);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info3
	public function actionCronCatchCdiscountProductInfo03(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=3);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info4
	public function actionCronCatchCdiscountProductInfo04(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=4);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info5
	public function actionCronCatchCdiscountProductInfo05(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=5);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info6
	public function actionCronCatchCdiscountProductInfo06(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=6);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info7
	public function actionCronCatchCdiscountProductInfo07(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=7);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info8
	public function actionCronCatchCdiscountProductInfo08(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=8);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info9
	public function actionCronCatchCdiscountProductInfo09(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=9);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info10
	public function actionCronCatchCdiscountProductInfo10(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=10);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info11
	public function actionCronCatchCdiscountProductInfo11(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=11);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info12
	public function actionCronCatchCdiscountProductInfo12(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=12);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info13
	public function actionCronCatchCdiscountProductInfo13(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=13);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info14
	public function actionCronCatchCdiscountProductInfo14(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=14);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info15
	public function actionCronCatchCdiscountProductInfo15(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=15);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info16
	public function actionCronCatchCdiscountProductInfo16(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=16);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info17
	public function actionCronCatchCdiscountProductInfo17(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=17);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info18
	public function actionCronCatchCdiscountProductInfo18(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=18);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info19
	public function actionCronCatchCdiscountProductInfo19(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=19);
	}

	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info20
	public function actionCronCatchCdiscountProductInfo20(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=20);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info21
	public function actionCronCatchCdiscountProductInfo21(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=21);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info22
	public function actionCronCatchCdiscountProductInfo22(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=22);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info23
	public function actionCronCatchCdiscountProductInfo23(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=23);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info24
	public function actionCronCatchCdiscountProductInfo24(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=24);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info25
	public function actionCronCatchCdiscountProductInfo25(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=25);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info26
	public function actionCronCatchCdiscountProductInfo26(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=26);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info27
	public function actionCronCatchCdiscountProductInfo27(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=27);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info28
	public function actionCronCatchCdiscountProductInfo28(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=28);
	}
	
	//@invoking					./yii html-catcher/cron-catch-cdiscount-product-info29
	public function actionCronCatchCdiscountProductInfo29(){
		self::CronCatchCdiscountProductInfoEagle2(self::$totalJobs,$thisJobId=29);
	}
	
 	/**
 	 +---------------------------------------------------------------------------------------------
 	 * 根据队列抓取 cdiscount 的商品数据 并进行回调
 	 +---------------------------------------------------------------------------------------------
 	 * @access static
 	 +---------------------------------------------------------------------------------------------
 	 * @param
 	 +---------------------------------------------------------------------------------------------
 	 * @return
 	 *
 	 * @invoking					./yii html-catcher/cron-catch-cdiscount-product-info-eagle2
 	 +---------------------------------------------------------------------------------------------
 	 * log			name	date					note
 	 * @author		lkh		2015/9/1				初始化
 	 +---------------------------------------------------------------------------------------------
 	 **/
 	private function CronCatchCdiscountProductInfoEagle2($totalJobs=0,$thisJobId=0){ 		
 		$start_time = date('Y-m-d H:i:s');
 		//ystest starts
 		/*
 		while (substr( $start_time,11,2)<>'03'){ // 2016-10-10 03:00:00只有凌晨3点钟才动
 			sleep(20*60); //20 minutes
 			$start_time = date('Y-m-d H:i:s');
 		}
 		
 		$start_time = date('Y-m-d H:i:s');*/
 		//ystest ends
 		
 		$comment = "\n cron service runnning for ".(__CLASS__)." ".(__FUNCTION__)." at $start_time";
 		echo $comment;
 		\Yii::info(['HtmlCatcher',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
 		
 		$seed = rand(0,99999);
 		global $CACHE;
 		$CACHE['JOBID'] = "MS".$seed."N";
 		$JOBID=$CACHE['JOBID'];
 		do{
	 		//$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);
 		    // $rtn1 = HtmlCatcherHelper::queueHandlerProcessing1('','cdiscount',$totalJobs ,$thisJobId,5 );
 			$rtn1 = HtmlCatcherHelper::queueHandlerProcessing1('', 'cdiscount', $totalJobs, $thisJobId, 300);
 			$rtn2 = HtmlCatcherHelper::queueHandlerProcessing1('','priceminister',$totalJobs ,$thisJobId );
 			$rtn3 = HtmlCatcherHelper::queueHandlerProcessing1('','bonanza',$totalJobs ,$thisJobId );
 			$rtn3 = HtmlCatcherHelper::queueHandlerProcessing1('','newegg',$totalJobs ,$thisJobId );
			if ($rtn1['success'] and $rtn1['message']=="n/a" and 
				$rtn2['success'] and $rtn2['message']=="n/a" and
				$rtn3['success'] and $rtn3['message']=="n/a"){
				//如果没有需要handle的request了，休息4秒
 			 	sleep(4);
 			 	echo "\n no pending , then sleep 4s ! ";
 			}
 				
 			$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
 			$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
 		
 			
 			//write the memery used into it as well.
 			$memUsed = floor (memory_get_usage() / 1024 / 1024);
 			$comment =  "\n cron service stops for ".(__CLASS__)." ".(__FUNCTION__)." at ".date('Y-m-d H:i:s');
 			$comment .= " - RAM Used: ".$memUsed."M";
 			echo $comment;
 				
 		}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
 		
 		
 		TrackingAgentHelper::extCallSum("");
 		\Yii::info(['HtmlCatcher',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
 	}//end of CronCatchCdiscountProductInfoEagle2

        
    
}