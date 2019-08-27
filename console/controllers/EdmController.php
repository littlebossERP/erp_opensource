<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use \eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\message\helpers\EdmHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;

/**
 * Edm controller
 */
class EdmController extends Controller {
    /**
     * edm队列处理器。
     * 由cron call 起来，会对未处理状态的发送队列尝试进行发送处理
     * @invoking					./yii edm/queue-handler
     * @author	lzhl	2015/7/2	初始化
     **/
	public function actionQueueHandler() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Edm QueueHandler at $start_time";
    	$rtn = EdmHelper::queueHandlerProcessing();

    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Edm QueueHandler at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	TrackingAgentHelper::extCallSum("");
        \Yii::info(['Edm',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }

    
    /**
     * edm-ses队列处理器。
     * 由cron call 起来，会对未处理状态的发送队列尝试进行发送处理
     * @invoking					./yii edm/queue-ses-handler
     * @author	lzhl	2017/3/6	初始化
     **/
    public function actionQueueSesHandler() {
    	$start_time = date('Y-m-d H:i:s');
    	echo "\n cron service runnning for Edm actionQueueSesHandler at $start_time";
    	$rtn = EdmHelper::queueSesHandlerProcessing();
    
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Edm actionQueueSesHandler at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        	TrackingAgentHelper::extCallSum("");
        	\Yii::info(['Edm',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }
}