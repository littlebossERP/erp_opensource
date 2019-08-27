<?php

namespace console\controllers;

use yii;
use yii\console\Controller;
use eagle\models\UserBase;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ExcelHelper;

class ExcelController extends Controller
{
	/**
	 +---------------------------------------------------------------------------------------------
	 * 导出Excel 处理。
	 * 由cron call 起来，会对db_queue库export_excel_queue表中状态为S的uid作导出Excel
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2016/12/19				初始化
	 +----------------------------------------------------------
	 *
	 *./yii excel/cron-export-excel
	 **/
	public function actionCronExportExcel($use_module = '') {
	    $start_time = date('Y-m-d H:i:s');
	    echo "background service runnning for ExportExcel $use_module at $start_time";
	    $seed = rand(0,99999);
	    global $CACHE;
	    $CACHE['JOBID'] = "MS".$seed."N";
	    $JOBID=$CACHE['JOBID'];
	    
	    do{
    		$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);
    		
	 		$rtn = ExcelHelper::queueExportExcel($use_module);
	        if ($rtn['success'] and $rtn['message']=="n/a"){
			 	sleep(6);
 			}
	 				 
	 		$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
	        $half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
	 		        	 
	 		$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
	 		
		}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
		
	}//end
	
	
}
