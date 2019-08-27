<?php

namespace eagle\modules\util\helpers;
use yii;
use yii\data\Pagination;

use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\models\AppPushQueue;
use eagle\modules\util\helpers\GetControlData;
use yii\helpers\StringHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\message\apihelpers\MessageAliexpressApiHelper;
use eagle\modules\message\models\Message;
use eagle\modules\message\apihelpers\MessageEbayApiHelper;
use yii\base\Exception;

/**
 +------------------------------------------------------------------------------
 * config模块---- 读取各个模块配置的参数数值
 +------------------------------------------------------------------------------
 * @package		Helper
 * @subpackage  Exception
 * @author		xjq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class AppPushDataHelper {
	//priority = 1-10; 5 = normal
	static public function insertOneRequest($from_app , $to_app,$puid, $command_line ,$priority=5){
		
		$anAppPushData = new AppPushQueue();
		$anAppPushData->from_app = $from_app;
		$anAppPushData->to_app = $to_app;
		$anAppPushData->command_line = $command_line;
		$anAppPushData->puid = $puid;
		$anAppPushData->priority = $priority;
		$anAppPushData->status = 'P';
		$anAppPushData->create_time = date('Y-m-d H:i:s');

		return $anAppPushData->save(false);
	}//end of function postTrackingBufferToDb

	
	/*从队列拉取 app 之间需要push的异步请求，然后通过 eval 执行，执行的 时间里面，自己判断 puid 是否等于 db current，不等就自己change user db*/
	static public function executeAppPushRequests($totalJobs=0,$thisJobId=0,$taskId=0){
	    // TODO 需要开多进程拉取再把这个去掉
	    $totalJobs = 0;
	    
		$anAppPushDatas = AppPushQueue::find()->where(
		        ( $taskId<=0 ? "status='P'":" id = $taskId").
				( $totalJobs<=0 ? "":" and id % $totalJobs = $thisJobId ") 
							
		)->orderBy(['id'=>"ASC"])->limit(300)->all();
		
		$doneIds=[];
		$lastPuid = -1;
		foreach ($anAppPushDatas as $aRequest){
			$current_time=explode(" ",microtime());
			$time1=round($current_time[0]*1000+$current_time[1]*1000);
			$puid = $aRequest->puid;

			//echo "try to do ".$aRequest->id ." from app ".$aRequest->from_app."\n";
			//执行回调函数
			try {		
				 
				//echo "try to invoke ".$aRequest->command_line ."\n";		 
				eval($aRequest->command_line .";");
				$doneIds[] = $aRequest->id;
				$current_time=explode(" ",microtime());
				$time2=round($current_time[0]*1000+$current_time[1]*1000);
				
				//echo "\n execute this request ".$aRequest->id." used time in ms:".($time2 - $time1)."\n";
				\Yii::info("execute this request ".$aRequest->id." used time in ms:".($time2 - $time1)."\n");
			} catch (\Exception $e) {
				if (is_string($e->getMessage()))
					echo $e->getMessage();
				$error_message = $e->getMessage();
				$aRequest->result = $error_message;
				$aRequest->status = "F";
				$aRequest->update_time = date('Y-m-d H:i:s');
				$aRequest->save(false);
				//echo "\n execute this request ".$aRequest->id." failed:".$error_message."\n";
				\Yii::info("execute this request ".$aRequest->id." failed:".$error_message."\n");
			}
			
			$current_time=explode(" ",microtime());
			$time2=round($current_time[0]*1000+$current_time[1]*1000);
				
			//计算累计做了多少次external 的调用以及耗时	
			$run_time = $time2 - $time1; //这个得到的$time是以 ms 为单位的
			//echo "done , time elapsed: $run_time \n";
		}//end of each reqeust
		
		AppPushQueue::updateAll(['status'=>'C','update_time'=>date('Y-m-d H:i:s')],['id'=>$doneIds]);
	
		return count($anAppPushDatas);
	}//end of function postTrackingBufferToDb

}