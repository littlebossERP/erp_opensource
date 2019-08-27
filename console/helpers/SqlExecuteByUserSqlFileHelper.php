<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午3:25
 */

namespace console\helpers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\models\UserDatabase;

class SqlExecuteByUserSqlFileHelper
{
	/**
	 * 切换所有用户的user库跑sql，  可能每个user库要跑多条sql。
	 * 若 $continueIfSqlFail=true，   每条sql都独立，  失败的话，会往下跑
	 * 若 $continueIfSqlFail=false，     每条sql语句不独立，一旦 失败的话，其他sql语句不跑，直接跳到下一个user库再跑
	 * 
	 */
    public static function SqlExecuteByUserSqlFile($continueIfSqlFail=true)
    {
    	$sqlFilePath = dirname(dirname(dirname(__FILE__)))."/eagle/doc/sql/altersql/sql01.sql";
    	$sqlContent = file_get_contents($sqlFilePath);
    	
    	if (strlen($sqlContent) > 1023272){
    		\Yii::error("sqlExceedSize Exception:fileSize > 1023272 ","file");
    		echo " sqlExceedSize Exception:fileSize > 1023272 \n";
    		exit;
    	}
    	
    	//过滤条件
    	$filtrationArr = array('#','--');
    	$sqlContent = eregi_replace("\/\*.*\*\/","", $sqlContent);
    	
    	//win文件需要做转换
    	$sqlContent = str_replace("\r\n", "\n", $sqlContent);
    	
    	//根据SQL语句分号和回车符来拆分执行命令语句
    	$segmentArr = explode(";\n",trim($sqlContent));
    	
    	//去掉注释和多余的空行
    	foreach($segmentArr as & $statement)
    	{
    		$sentence = explode("\n",$statement);
    	
    		$newStatement = array();
    		foreach($sentence as $subSentence)
    		{
    			if('' != trim($subSentence))
    			{
    				//判断是会否是注释
    				$isComment = false;
    				foreach($filtrationArr as $filtrations)
    				{
    					if(eregi("^(".$filtrations.")",trim($subSentence)))
    					{
    						$isComment = true;
    						break;
    					}
    				}
    				//如果不是注释，则认为是sql语句
    				if(!$isComment)
    					$newStatement[] = $subSentence;
    			}
    		}
    	
    		$statement = $newStatement;
    	}
    	
    	//组合sql语句
    	foreach($segmentArr as & $statement)
    	{
    		$newStmt = '';
    		foreach($statement as $sentence)
    		{
    			$newStmt = $newStmt.trim($sentence)."\n";
    		}
    		$statement = $newStmt;
    	}
    	
    	$successArr = array();
    	$failureArr = array();
    	$notExistsArr = array();
    	
    	$backgroundMonitor = BackgroundMonitor::findOne(['job_name'=>'SqlScriptRun']);
    	if ($backgroundMonitor === null){
    		$backgroundMonitor = new BackgroundMonitor ();
    		
    		$backgroundMonitor->job_name = "SqlScriptRun";
    	}
    	
    	$backgroundMonitor->last_end_time = null;
    	$backgroundMonitor->last_total_time = 0;
    	$backgroundMonitor->status = "Start";
    	$backgroundMonitor->json_params = "{\"success\":[\"\"],\"failure\":[\"\"],\"notExists\":[\"\"]}";
    	
    	$backgroundMonitor->create_time = date('Y-m-d H:i:s');
    	$sqlUserdbStarttime = microtime(true); // 所有数据库Sql运行开始时间点。 单位：毫秒
    	
    	$backgroundMonitor->save(false);
    	
    	//获取数据
    	$mainUsers = UserBase::find()->where(['puid'=>0])->orderBy(["uid"=>SORT_DESC])->asArray()->all();
    	$tmpRecords = 0;
    	
    	foreach ($mainUsers as $puser){
    		$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
    		
    		$backgroundMonitor->status = $db_name;
    		$tmpRecords++;
    		
    		if(($tmpRecords % 10) == 0){
    			$backgroundMonitor->json_params = "{\"success\":[\"".implode("\",\"", $successArr)."\"],".
	    			"\"failure\":[\"".implode("\",\"", $failureArr)."\"],".
	    			"\"notExists\":[\"".implode("\",\"", $notExistsArr)."\"]}";
    			
    			$backgroundMonitor->save(false);
    		}
    		  
    		
    		echo "\n".$db_name." Running ...";
    		
//     		//执行SQL脚本    		
    		$isError = false;
    		
    		foreach($segmentArr as $sql){
    			try
    			{
    				if($sql=="") continue;
    				\yii::$app->subdb->createCommand($sql)->execute();
    			} catch(\Exception $e)
    			{
    				\Yii::error("sqlRunFail user_".$puser['uid']." subdb Exception:".$e->getMessage(),"file");
    				$isError = true;
    				
    				$backgroundMonitor->messages = $e->getMessage();
    				if ($continueIfSqlFail===false) break;
    				
    			}
    		}
    		
    		if($isError == false){
    			$successArr[] = $db_name;
    			echo "\n".$db_name." Success Running. \n";
    		}else{
    			$failureArr[] = $db_name;
    			
    			echo "\n".$db_name." Failure Running. \n";
    		}
    		
    	}
    	
    	$backgroundMonitor->status = "End";
    	
    	$backgroundMonitor->last_end_time = date('Y-m-d H:i:s');
    	$sqlUserdbEndtime = microtime(true); // 所有数据库Sql运行开始时间点。 单位：毫秒
    	$backgroundMonitor->last_total_time = round($sqlUserdbEndtime-$sqlUserdbStarttime,3);
    	
    	$backgroundMonitor->json_params = "{\"success\":[\"".implode("\",\"", $successArr)."\"],".
    			"\"failure\":[\"".implode("\",\"", $failureArr)."\"],".
    			"\"notExists\":[\"".implode("\",\"", $notExistsArr)."\"]}";

    	
    	$backgroundMonitor->save(false);
    	
    	echo "Time total ".$backgroundMonitor->last_total_time." Sec\n";
    }

    /**
     * 检查job在redis中是否已经存在版本号,如果没有则生成新的版本号 0,如果redis中存在的版本好与现有版本好不一致则停止运行
     * @param $controllerId
     * @param $actionId
     * @param $jobId
     * @param $jobVersion 现在运行job的版本
     */
    public static function checkVersion($controllerId, $actionId, $jobId,$jobVersion=null)
    {
        $redisKey = $controllerId.":" . $actionId . ":" . $jobId;
        $currentVersion = ConfigHelper::getGlobalConfig($redisKey, 'NO_CACHE');
        if (empty($currentVersion)) {
            $currentVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty($jobVersion)) {
            $jobVersion = $currentVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if ($jobVersion <> $currentVersion) {
            exit("Version new $currentVersion , this job ver " . $jobVersion . " exits for using new version $currentVersion.");
        }
        return $jobVersion;
    }
}