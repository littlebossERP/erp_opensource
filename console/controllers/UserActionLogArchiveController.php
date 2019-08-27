<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\models\OrderHistoryStatisticsData;
use console\helpers\OrderUserStatisticHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderOld;
use eagle\modules\order\models\OdOrderItemOld;
use eagle\modules\order\models\OdOrderShippedOld;
use eagle\modules\util\models\UserBackgroundJobControll;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\util\helpers\TimeUtil;

/**
 * SqlExecution controller
 */

//error_reporting(0);

class UserActionLogArchiveController extends Controller
{

	/**
	 +----------------------------------------------------------
	 * 初始化第一步---- 旧的订单copy到 旧的订单表
	 +----------------------------------------------------------
	 *  ./yii user-action-log-archive/clear-data
	 **/
    public function actionClearData()
    {
    	//$mainUserPuidArr=array(297);
    	
   
    		
    		$dbConn=\yii::$app->db;
    		echo "puid:".$puid." Running ... \n";
    		
    		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
    		//1431187200---2015-05-10 0:0:0
    		
    		$dateArr=array("2015-09-28");
    		foreach($dateArr as $dateStr){
    		    $idArr=array();
    	    	$rows = $dbConn->createCommand('SELECT id,puid FROM  app_user_action_log WHERE DATE_FORMAT( log_time,  "%Y-%m-%d" ) =  "'.$dateStr.'" and url_path="/tracker/loginsuccess" group by puid')->queryAll();
    	    	foreach($rows as $row){
    	    		$idArr[]=$row["id"];//需要保留的id    	    		
    	    	}
    	    	
    	    	$rows = $dbConn->createCommand('select count(*)  FROM  app_user_action_log WHERE DATE_FORMAT( log_time,  "%Y-%m-%d" ) =  "'.$dateStr.'" and url_path="/tracker/loginsuccess" and id not in ('.implode(",", $idArr).')')->queryAll();
    	    	print_r($rows);
    		
    		
    		}
    	
	}
		
}
