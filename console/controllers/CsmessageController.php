<?php
 
namespace console\controllers;
 
use yii;
use yii\console\Controller;
use \eagle\modules\tracking\helpers\TrackingAgentHelper;
use \eagle\modules\tracking\helpers\TrackingHelper;
use \eagle\modules\message\helpers\MessageBGJHelper;
use \eagle\modules\util\helpers\ImageHelper;
use eagle\modules\message\helpers\SaasMessageAutoSyncApiHelper;
 
/**
 * Test controller
 */
class CsmessageController extends Controller {
	/*
	* @invoking					./yii csmessage/test
	* */	
	public function actionTest() {
		echo "sdfsdf";
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 17Track API队列处理器。支持多进程一起工作
	 * 读取一条request，然后执行，然后继续读取下一条。一直到没有读取到任何pending 的request
	 * 如果执行了超过30分钟，自动退出。Job Monitor会自动重新generate一个进程
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					./yii csmessage/send-message-ebay-eagle2
	 * @invoking					./yii csmessage/send-message-aliexpress-eagle2
	 * @invoking					./yii csmessage/send-message-dhgate-eagle2 
	 * @invoking					./yii csmessage/send-message-priceminister-eagle2
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
    public function actionSendMessageEbayEagle2() {
    	self::sendMessage('ebay');  
    }//end of function actionDo17TrackQuery
  
    public function actionSendMessageAliexpressEagle2() {    
    	return; 	
     	self::sendMessage('aliexpress');
    }//end of function actionDo17TrackQuery
    
    public function actionSendMessageAliexpressEagle2Job0() {
    	self::sendMessage('aliexpress','tracker',0,4);
    }//end of function actionDo17TrackQuery

    public function actionSendMessageAliexpressEagle2Job1() {
    	self::sendMessage('aliexpress','tracker',1,4);
    }//end of function actionDo17TrackQuery

    public function actionSendMessageAliexpressEagle2Job2() {
    	self::sendMessage('aliexpress','tracker',2,4);
    }//end of function actionDo17TrackQuery

    public function actionSendMessageAliexpressEagle2Job3() {
    	self::sendMessage('aliexpress','tracker',3,4);
    }//end of function actionDo17TrackQuery
    
    public function actionSendMessageDhgateEagle2() {
    	self::sendMessage('dhgate');
    }//end of function actionDo17TrackQuery
    
    
    /**
     * @param $platform	平台类型
     * @param $app_source	app来源
     * @param $msg_type	订单留言或站内信 1:订单留言, 2:站内信
     * @param $jobTail, 如果totalProcess大于0，那么这个job就是做 id % totalProcess = $jobTail 的任务,
     * @param totalProcess,  如果是 0，就是不分开多个job并发，如果是大于0的,就是一共做成n个job并发的
     */
 	private static function sendMessage($platform,$app_source='tracker',$jobTail=-1,$totalProcess=0){
 		$start_time = date('Y-m-d H:i:s');
 		echo "background service runnning for MessageSending $platform at $start_time";
 		$seed = rand(0,99999);
 		global $CACHE;
 		$CACHE['JOBID'] = "MS".$seed."N";
 		$JOBID=$CACHE['JOBID'];
 		
 		do{
	 		$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);
	 		$rtn = MessageBGJHelper::queueHandlerProcessing1('',$platform, $app_source,$jobTail ,$totalProcess);
	 		
	 		if ($platform == 'aliexpress'){
	 			$rtn1 = MessageBGJHelper::queueHandlerProcessing1('',$platform,'cs_ticket',$jobTail ,$totalProcess);
	 			if ($rtn['success'] and $rtn['message']=="n/a" and $rtn1['success'] and $rtn1['message']=="n/a"){
	 				sleep(4);
	 			}
	 		}else{
		 		//如果没有需要handle的request了，退出
		 		if ($rtn['success'] and $rtn['message']=="n/a"){
			 		sleep(4);
			 		//echo "cron service Do17TrackQuery,no reqeust pending, sleep for 4 sec";
		 		}
	 		}
	 				 
	 		$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
	        $half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
	 		        	 
	 		$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
	 		 
 		}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
 		
 		//submit 使用的external call 统计数
 		TrackingAgentHelper::extCallSum('',0,true);
 	}
 	
 	/**
 	 * 速卖通根据队列表message_api_queue状态为P的订单留言进行回复
 	 */
 	public function actionSendOrderMsgAliexpressEagle2() {
 		return false;
//  		self::sendMessage('aliexpress','cs_ticket','1');	//调用一个actionSendMessageAliexpressEagle2足够-20150925 hqw
 	}
 	
 	/**
 	 * 速卖通根据队列表message_api_queue状态为P的站内信进行回复
 	 */
 	public function actionSendLetterMsgAliexpressEagle2() {
 		return false;
//  		self::sendMessage('aliexpress','cs_ticket','2');	//调用一个actionSendMessageAliexpressEagle2足够-20150925 hqw
 	}
 	
 	/**
 	 * 敦煌根据队列表message_api_queue状态为P的订单留言进行回复
 	 */
 	public function actionReplyOrderMsgDhgateEagle2(){
 		self::sendMessage('dhgate','cs_ticket');
 	}
 	
 	/**
 	 * 敦煌根据队列表message_api_queue状态为P的站内信进行回复
 	 */
 	public function actionReplyLetterMsgDhgateEagle2(){
 		self::sendMessage('dhgate','cs_ticket');
 	}
 	/**
 	 * PM根据队列表message_api_queue状态为P的站内信进行回复
 	 */
 	// ./yii csmessage/reply-letter-msg-priceminister-eagle2
 	public function actionReplyLetterMsgPriceministerEagle2(){
 	    self::sendMessage('priceminister','cs_ticket');
 	}
 	
 	/**
 	 * 同步速卖通新产生的站内信     所有平台都以时间滑动方式来拉取站内信
 	 * 主要作用：及时更新最新产生的站内信
 	 * log			name	date					note
 	 * @author		hqw 	2015/07/03				初始化
 	 *
 	 */
 	public function actionAliexpressGetMsgByTimeEagle2(){
 		$startRunTime=time();
 		$seed = rand(0,99999);
 		$cronJobId = 'AliexpressMsgGOL'.$seed.'Time';
 		SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 		echo "Aliexpressmsg_get_list_by_time jobid=$cronJobId start \n";
 		\Yii::info("Aliexpressmsg_get_list_by_time jobid=$cronJobId start");
  
 			$rtn = SaasMessageAutoSyncApiHelper::getMsgListByTime( 'aliexpress' );
 			if ($rtn===false){
 			echo "Aliexpressmsg_get_list_by_time jobid=$cronJobId sleep10 \n";
 			\Yii::info("Aliexpressmsg_get_list_by_time jobid=$cronJobId sleep10");
 		 
 		}
  
 		echo "Aliexpressmsg_get_list_by_time jobid=$cronJobId end \n";
 		\Yii::info("Aliexpressmsg_get_list_by_time jobid=$cronJobId end");
 	}
 	
 	/**
 	 * 同步速卖通新产生的订单留言
 	 * 主要作用：及时更新最新产生的订单留言
 	 * log			name	date					note
 	 * @author		hqw 	2015/07/03				初始化
 	 *
 	 */
 	public function actionAliexpressGetOrdermsgByTimeEagle2(){
 		$startRunTime=time();
 		$seed = rand(0,99999);
 		$cronJobId = 'AliexpressOrdermsgGOL'.$seed.'Time';
		SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 		echo "AliexpressOrdermsg_get_list_by_time jobid=$cronJobId start \n";
 		\Yii::info("AliexpressOrdermsg_get_list_by_time jobid=$cronJobId start");
 		do{
 			$rtn = SaasMessageAutoSyncApiHelper::getOrderMsgListByTime( 'aliexpress' );
 			if ($rtn===false){
	 			echo "AliexpressOrdermsg_get_list_by_time jobid=$cronJobId sleep30 \n";
	 			\Yii::info("AliexpressOrdermsg_get_list_by_time jobid=$cronJobId sleep30");
	 			sleep(30);
 			}
 		}while (time() < $startRunTime+3600);
 		echo "AliexpressOrdermsg_get_list_by_time jobid=$cronJobId end \n";
 		\Yii::info("AliexpressOrdermsg_get_list_by_time jobid=$cronJobId end");
 	}
 	
 	/**
 	 * 同步敦煌所有类型的站内信
 	 * 主要作用：及时更新最新产生的站内信
 	 * log			name	date					note
 	 * @author		hqw 	2015/07/11				初始化
 	 *
 	 */
 	 public function actionDhgateAllMsgEagle2(){
 		$startRunTime=time();
 		$seed = rand(0,99999);
 		$cronJobId = 'DhgateAllMsgGOL'.$seed.'Time';
 		SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 		echo "DhgateAllMsg_get_list_by_time jobid=$cronJobId start \n";
 		\Yii::info("DhgateAllMsg_get_list_by_time jobid=$cronJobId start");
 		do{
 			$rtn = SaasMessageAutoSyncApiHelper::getMsgListByTime( 'dhgate' );
 			if ($rtn===false){
	 			echo "DhgateAllMsg_get_list_by_time jobid=$cronJobId sleep50 \n";
	 			\Yii::info("DhgateAllMsg_get_list_by_time jobid=$cronJobId sleep50");
	 			sleep(50);
 			}
 		}while (time() < $startRunTime+3600);
 		echo "DhgateAllMsg_get_list_by_time jobid=$cronJobId end \n";
 		\Yii::info("DhgateAllMsg_get_list_by_time jobid=$cronJobId end");
 	}
 	
 	/**
 	 * 同步ebay所有类型的站内信
 	 * 主要作用：及时更新最新产生的站内信
 	 * log			name	date					note
 	 * @author		dzt 	2015/07/21				初始化
 	 *				./yii csmessage/ebay-all-msg-eagle2
 	 */
 	public function actionEbayAllMsgEagle2(){
//  		return false;
 		
 		$startRunTime=time();
 		$seed = rand(0,99999);
 		$cronJobId = 'EbayAllMsgGOL'.$seed.'Time';
 		SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 		echo "EbayAllMsg_get_list_by_time jobid=$cronJobId start \n";
 		\Yii::info("EbayAllMsg_get_list_by_time jobid=$cronJobId start");
 		do{
 		$rtn = SaasMessageAutoSyncApiHelper::getMsgListByTime( 'ebay' );
 		if ($rtn===false){
 		echo "EbayAllMsg_get_list_by_time jobid=$cronJobId sleep30 \n";
 		\Yii::info("EbayAllMsg_get_list_by_time jobid=$cronJobId sleep30");
 		sleep(30);
 		}
 		}while (time() < $startRunTime+3600);
 		echo "EbayAllMsg_get_list_by_time jobid=$cronJobId end \n";
 		\Yii::info("EbayAllMsg_get_list_by_time jobid=$cronJobId end");
 	}
 	
 	/**
 	 * Ebay根据队列表message_api_queue状态为P的信进行回复
 	 * @invoking					./yii csmessage/reply-msg-ebay-eagle2
 	 */
 	public function actionReplyMsgEbayEagle2(){
 		self::sendMessage('ebay','cs_ticket');
 	}
 	
 	/**
 	 * 同步wish未处理过的的站内信
 	 * 主要作用：及时更新最新产生的站内信
 	 * log			name	date					note
 	 * @author		hqw 	2015/07/23				初始化
 	 *
 	 */
 	public function actionWishAwaitingMsgEagle2(){
 		$startRunTime=time();
 		$seed = rand(0,99999);
 		$cronJobId = 'WishAwaitingMsg'.$seed.'Time';
 		SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 		echo "WishAwaitingMsg_get_list_by_time jobid=$cronJobId start \n";
 		\Yii::info("WishAwaitingMsg_get_list_by_time jobid=$cronJobId start");
 		
 		$rtn = SaasMessageAutoSyncApiHelper::getMsgListByTime( 'wish' );
 		if ($rtn===false){
	 		echo "WishAwaitingMsg_get_list_by_time jobid=$cronJobId sleep30 \n";
	 		\Yii::info("WishAwaitingMsg_get_list_by_time jobid=$cronJobId sleep30");
 		}
 		
 		echo "WishAwaitingMsg_get_list_by_time jobid=$cronJobId end \n";
 		\Yii::info("WishAwaitingMsg_get_list_by_time jobid=$cronJobId end");
 	}
 	
 	/**
 	* Wish根据队列表message_api_queue状态为P的信进行回复
 	* 
 	*/
 	public function actionReplyMsgWishEagle2(){
 		self::sendMessage('wish','cs_ticket');
 	}
 	
 	/**
 	 * 同步ebay指定Puid的站内信拉取
 	 * 主要作用：及时更新最新产生的站内信
 	 * log			name	date					note
 	 * @author		hqw 	2015/08/27				初始化
 	 *
 	 */
 	public function actionEbayMsgByPuid(){
 		$startRunTime=time();
 		$seed = rand(0,99999);
 		$cronJobId = 'EbayAllMsgGOL'.$seed.'Time';
 		SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 		echo "EbayAllMsg_get_list_by_time jobid=$cronJobId start \n";
 		\Yii::info("EbayAllMsg_get_list_by_time jobid=$cronJobId start");
//  		do{
 		$rtn = SaasMessageAutoSyncApiHelper::getMsgListByTime( 'ebay', '1227' );
 		if ($rtn===false){
 	 		echo "EbayAllMsg_get_list_by_time jobid=$cronJobId sleep10 \n";
 	 		\Yii::info("EbayAllMsg_get_list_by_time jobid=$cronJobId sleep10");
//  	 				sleep(10);
 		}
//  		}while (time() < $startRunTime+3600);
 		echo "EbayAllMsg_get_list_by_time jobid=$cronJobId end \n";
 		\Yii::info("EbayAllMsg_get_list_by_time jobid=$cronJobId end");
 	}
 	
 	
 	/**
 	 * 同步速卖通新产生的订单留言  最新接口20151015
 	 * 主要作用：及时更新最新产生的订单留言
 	 * log			name	date					note
 	 * @author		hqw 	2015/09/15				初始化
 	 *
 	 *	./yii csmessage/aliexpress-get-ordermsg-by-time-relation-eagle2
 	 */
 	public function actionAliexpressGetOrdermsgByTimeRelationEagle2(){
 		$startRunTime=time();
 		$seed = rand(0,99999);
 		$cronJobId = 'AliexpressOrdermsgRelationGOL'.$seed.'Time';
 		SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 		echo "AliexpressOrdermsgRelation_get_list_by_time jobid=$cronJobId start \n";
 		\Yii::info("AliexpressOrdermsgRelation_get_list_by_time jobid=$cronJobId start");
 		do{
 			$rtn = SaasMessageAutoSyncApiHelper::getOrderMsgListByTime('aliexpress','Relation1.0');
 			if ($rtn===false){
 	 			echo "AliexpressOrdermsgRelation_get_list_by_time jobid=$cronJobId sleep30 \n";
 	 			\Yii::info("AliexpressOrdermsgRelation_get_list_by_time jobid=$cronJobId sleep30");
 	 			sleep(30);
 			}
 		}while (time() < $startRunTime+3600);
 		echo "AliexpressOrdermsgRelation_get_list_by_time jobid=$cronJobId end \n";
 		\Yii::info("AliexpressOrdermsgRelation_get_list_by_time jobid=$cronJobId end");
 	}
 	
 	/**
 	 * 同步速卖通新产生的站内信  最新接口20151015
 	 * 主要作用：及时更新最新产生的站内信
 	 * log			name	date					note
 	 * @author		hqw 	2015/09/17				初始化
 	 *
 	 *	./yii csmessage/aliexpress-get-centermsg-by-time-relation-eagle2
 	 */
 	public function actionAliexpressGetCentermsgByTimeRelationEagle2(){
 		$startRunTime=time();
 		$seed = rand(0,99999);
 		$cronJobId = 'AliexpressCentermsgRelationGOL'.$seed.'Time';
 		SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 		echo "AliexpressCentermsgRelation_get_list_by_time jobid=$cronJobId start \n";
 		\Yii::info("AliexpressCentermsgRelation_get_list_by_time jobid=$cronJobId start");
 		do{
 			$rtn = SaasMessageAutoSyncApiHelper::getMsgListByTime('aliexpress', '', 'Relation1.0');
 			if ($rtn===false){
 				echo "AliexpressCentermsgRelation_get_list_by_time jobid=$cronJobId sleep30 \n";
 				\Yii::info("AliexpressCentermsgRelation_get_list_by_time jobid=$cronJobId sleep30");
 				sleep(30);
 			}
 		}while (time() < $startRunTime+3600);
 		echo "AliexpressCentermsgRelation_get_list_by_time jobid=$cronJobId end \n";
 		\Yii::info("AliexpressCentermsgRelation_get_list_by_time jobid=$cronJobId end");
 	}
 	
 	/**
 	 * 同步cdiscount所有类型的订单留言
 	 * 主要作用：及时更新最新产生的站内信
 	 * log			name	date					note
 	 * @author		lwj 	2016/03/14				初始化
 	 * ./yii csmessage/cdiscount-all-msg-eagle2
 	 */
 	public function actionCdiscountAllMsgEagle2(){
 	
 	    $startRunTime=time();
 	    $seed = rand(0,99999);
 	    $cronJobId = 'CdiscountAllMsgGOL'.$seed.'Time';
 	    SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 	    echo "CdiscountAllMsg_get_list_by_time jobid=$cronJobId start \n";
 	    $noWorkToDo = false;
 	    \Yii::info("CdiscountAllMsg_get_list_by_time jobid=$cronJobId start");
 	    do{
 	        $rtn = SaasMessageAutoSyncApiHelper::getMsgListByTime( 'cdiscount' );
 	        if ($rtn===false){
 	            echo "CdiscountAll Msg_get_list_by_time jobid=$cronJobId sleep 30 due to Nothing to do\n";
 	           // \Yii::info("CdiscountAllMsg_get_list_by_time jobid=$cronJobId sleep30");
 	          //  sleep(30);
 	            $noWorkToDo = true;
 	        }
 	    }while (time() < $startRunTime+3600 and !$noWorkToDo);
 	    echo "CdiscountAllMsg_get_list_by_time jobid=$cronJobId end \n";
 	    \Yii::info("CdiscountAllMsg_get_list_by_time jobid=$cronJobId end");
 	}
 	
 	/**
 	 * 同步priceminister所有类型的订单留言
 	 * 主要作用：及时更新最新产生的站内信
 	 * log			name	date					note
 	 * @author		lwj 	2016/06/06				初始化
 	 * ./yii csmessage/priceminister-all-msg-eagle2
 	 */
 	public function actionPriceministerAllMsgEagle2(){
 	
 	    $startRunTime=time();
 	    $seed = rand(0,99999);
 	    $cronJobId = 'PriceministerAllMsgGOL'.$seed.'Time';
 	    SaasMessageAutoSyncApiHelper::setCronJobId($cronJobId);
 	    echo "PriceministerAllMsg_get_list_by_time jobid=$cronJobId start \n";
 	    $noWorkToDo = false;
 	    \Yii::info("PriceministerAllMsg_get_list_by_time jobid=$cronJobId start");
 	    do{
 	        $rtn = SaasMessageAutoSyncApiHelper::getMsgListByTime( 'priceminister' );
 	        if ($rtn===false){
 	            echo "PriceministerAll Msg_get_list_by_time jobid=$cronJobId sleep 30 due to Nothing to do\n";
 	            // \Yii::info("PriceministerAllMsg_get_list_by_time jobid=$cronJobId sleep30");
 	 	          //  sleep(30);
 	            $noWorkToDo = true;
 	        }
 	    }while (time() < $startRunTime+3600 and !$noWorkToDo);
 	    echo "PriceministerAllMsg_get_list_by_time jobid=$cronJobId end \n";
 	    \Yii::info("PriceministerAllMsg_get_list_by_time jobid=$cronJobId end");
 	}
 	
}