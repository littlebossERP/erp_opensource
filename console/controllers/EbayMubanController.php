<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\models\UserDatabase;

/**
 * SqlExecution controller
 */

error_reporting(0);

class EbayMubanController extends Controller
{
	

    public function actionChangeUserdbBySqlFile()
    {
    	$sqlFilePath = dirname(dirname(dirname(__FILE__)))."/eagle/doc/sql/altersql/ebay_muban_detail_2016322_yht.sql";
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
    	

    	$sqlUserdbStarttime = microtime(true); // 所有数据库Sql运行开始时间点。 单位：毫秒
    	

    	//获取数据
    	$mainUsers = UserBase::find()->where(['puid'=>0])->orderBy(["uid"=>SORT_DESC])->asArray()->all();
    	$tmpRecords = 0;
    	
    	foreach ($mainUsers as $puser){
    		$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
    		
    		$tmpRecords++;
    		
 
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
    	

    }


}
