<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\models\UserDatabase;
use console\helpers\SqlExecuteByUserSqlFileHelper;

/**
 * SqlExecution controller
 */

error_reporting(0);

class SqlexecutionController extends Controller
{
 
	
	
	/**
	 +----------------------------------------------------------
	 * 云途临时统计
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/11/03				初始化
	 +----------------------------------------------------------
	 *
	 *./yii sqlexecution/account-yuntutest
	 **/
	public function actionAccountYuntutest(){
		$tmpCky = array('lb_yuntu' => 0);
	
		//获取数据
		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
			
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
	
			  
			try{
				$sql = "select a.carrier_code,count(1) as account
					from sys_carrier_account a
					where a.carrier_code in ('lb_yuntu')
					group by a.carrier_code";
	
				$accountCountArr = \yii::$app->subdb->createCommand($sql)->queryAll();
	
				if(count($accountCountArr) > 0){
					echo 'puid:'.$puser['uid']."\n";
						
					$tmpCky['lb_yuntu'] += 1;
				}
	
			} catch(\Exception $e){
			}
		}
	
		print_r($tmpCky);
	}
	
	/**
	 +----------------------------------------------------------
	 * 出口易临时统计
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/10/16				初始化
	 +----------------------------------------------------------
	 *
	 *./yii sqlexecution/accountcounttest
	 **/
	public function actionAccountcounttest(){
		$tmpCky = array('lb_chukouyi' => 0, 'lb_chukouyiOversea' => 0);
	
		//获取数据
		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
			
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
	 
			try{
				$sql = "select a.carrier_code,count(1) as account
					from sys_carrier_account a
					where a.carrier_code in ('lb_chukouyi','lb_chukouyiOversea')
					group by a.carrier_code";
	
				$accountCountArr = \yii::$app->subdb->createCommand($sql)->queryAll();
	
				if(count($accountCountArr) == 1){
					$tmpCky[$accountCountArr[0]['carrier_code']] +=  $accountCountArr[0]['account'];
				}
	
				if(count($accountCountArr) == 2){
					$tmpCky[$accountCountArr[0]['carrier_code']] +=  $accountCountArr[0]['account'];
					$tmpCky[$accountCountArr[1]['carrier_code']] +=  $accountCountArr[1]['account'];
				}
	
				if(count($accountCountArr) > 0){
					echo 'puid:'.$puser['uid']."\n";
					print_r($accountCountArr);
				}
	
			} catch(\Exception $e){
			}
		}
	
		echo "\n".'ckyAccountcount:'."\n";
	
		print_r($tmpCky);
	}
	
	/**
	 +----------------------------------------------------------
	 * 批量运行SQL脚本到每个UserDb
	 * 可能每个user库要跑多条sql,每条sql都独立，  失败的话，会往下跑
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/05/25				初始化
	 +----------------------------------------------------------
	 * @Param
	 * 
	 * @Notice 文件中假如存在 SET FOREIGN_KEY_CHECKS=0; 语句时当SQL文件有问题时捕捉不了错误。
	 * 
	 * 
	 * 
	 **/
    public function actionChangeUserdbBySqlFile()
    {
    	$continueIfSqlFail=true;
    	SqlExecuteByUserSqlFileHelper::SqlExecuteByUserSqlFile($continueIfSqlFail);
    }
    
    /**
     +----------------------------------------------------------
     * 批量运行SQL脚本到每个UserDb
     * 每条sql语句不独立，一旦 失败的话，其他sql语句不跑，直接跳到下一个user库再跑
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/05/25				初始化
     +----------------------------------------------------------
     * @Param
     *
     * @Notice 文件中假如存在 SET FOREIGN_KEY_CHECKS=0; 语句时当SQL文件有问题时捕捉不了错误。
     *
     *
     *
     **/
    public function actionChangeUserdbBySqlFileSkip()
    {
    	$continueIfSqlFail=false;
    	SqlExecuteByUserSqlFileHelper::SqlExecuteByUserSqlFile($continueIfSqlFail);
    }    
    
    
    /**
     +----------------------------------------------------------
     * 批量运行SQL脚本 默认连接在managedb上
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/05/30				初始化
     +----------------------------------------------------------
     * @Param
     *
     **/
    public function actionManagedbBySqlFile()
    {
    	//默认操作Managedb数据库
    	try{
    		\yii::$app->subdb->createCommand("use managedb;")->execute();
    	}catch (\Exception $e){
    		
    		return "Switching managedb Failure";
    	}
    	
    	$sqlFilePath = dirname(dirname(dirname(__FILE__)))."/eagle/doc/sql/altersql/defaultManageSql.sql";
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
    	
    	$tmpRecords = 0;
    	
    	foreach($segmentArr as $sql){
    		try
    		{
    			if($sql=="") continue;
    			
    			$tmpRecords++;
    			
    			if(($tmpRecords % 10) == 0){
    				$backgroundMonitor->status = $sql;
    				$backgroundMonitor->save(false);
    			}
    			
    			\yii::$app->subdb->createCommand($sql)->execute();
    		} catch(\Exception $e)
    		{
    			$currentDb = \yii::$app->subdb->createCommand("select database() as dataname;")->queryAll();
    			
    			\Yii::error("sqlManagedbRunFail managedb ".$currentDb[0]['dataname']." Exception:".$e->getMessage(),"file");
    			
    			echo "sqlManagedbRunFail managedb ".$currentDb[0]['dataname']." Exception:".$e->getMessage();
    		}
    	}
    	
    	$backgroundMonitor->status = "End";
    	 
    	$backgroundMonitor->last_end_time = date('Y-m-d H:i:s');
    	$sqlUserdbEndtime = microtime(true); // 所有数据库Sql运行开始时间点。 单位：毫秒
    	$backgroundMonitor->last_total_time = round($sqlUserdbEndtime-$sqlUserdbStarttime,3);
    	 
    	$backgroundMonitor->save(false);
    	 
    	echo "\nTime total ".$backgroundMonitor->last_total_time." Sec\n";
    }
    
    
    /**
     * ./yii sqlexecution/change-userdb-by-sql-file0
     */
    public function actionChangeUserdbBySqlFile0(){
    	self::publicChangeUserdbBySqlFile(0);
    }
    
    /**
     * ./yii sqlexecution/change-userdb-by-sql-file1
     */
    public function actionChangeUserdbBySqlFile1(){
    	self::publicChangeUserdbBySqlFile(1);
    }
    
    /**
     * ./yii sqlexecution/change-userdb-by-sql-file2
     */
    public function actionChangeUserdbBySqlFile2(){
    	self::publicChangeUserdbBySqlFile(2);
    }
    
    /**
     * ./yii sqlexecution/change-userdb-by-sql-file3
     */
    public function actionChangeUserdbBySqlFile3(){
    	self::publicChangeUserdbBySqlFile(3);
    }
    
    /**
     * ./yii sqlexecution/change-userdb-by-sql-file4
     */
    public function actionChangeUserdbBySqlFile4(){
    	self::publicChangeUserdbBySqlFile(4);
    }
    
    /**
     * ./yii sqlexecution/change-userdb-by-sql-file5
     */
    public function actionChangeUserdbBySqlFile5(){
    	self::publicChangeUserdbBySqlFile(5);
    }
    
    /**
     * ./yii sqlexecution/change-userdb-by-sql-file6
     */
    public function actionChangeUserdbBySqlFile6(){
    	self::publicChangeUserdbBySqlFile(6);
    }
    
    
    /**
     * ./yii sqlexecution/change-userdb-by-sql-file7
     */
    public function actionChangeUserdbBySqlFile7(){
    	self::publicChangeUserdbBySqlFile(7);
    }
    
    /**
     * 公用方法
     * @param $dbserverid	用户对应的数据库机器的id
     */
    public static function publicChangeUserdbBySqlFile($dbserverid){
    	
    	if(self::getIsNewDbServer()){
    		echo "There is a new machine ,Please confirm and try again. "."\n";
    		exit;
    	}
    	
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
    	
    	$backgroundMonitor = BackgroundMonitor::findOne(['job_name'=>'SqlScriptRun'.$dbserverid]);
    	if ($backgroundMonitor === null){
    		$backgroundMonitor = new BackgroundMonitor ();
    	
    		$backgroundMonitor->job_name = "SqlScriptRun".$dbserverid;
    	}
    	
    	$backgroundMonitor->last_end_time = null;
    	$backgroundMonitor->last_total_time = 0;
    	$backgroundMonitor->status = "Start";
    	$backgroundMonitor->json_params = "{\"success\":[\"\"],\"failure\":[\"\"],\"notExists\":[\"\"]}";
    	
    	$backgroundMonitor->create_time = date('Y-m-d H:i:s');
    	$sqlUserdbStarttime = microtime(true); // 所有数据库Sql运行开始时间点。 单位：毫秒
    	
    	$backgroundMonitor->save(false);
    	
    	//获取数据
    	$mainUsers = UserDatabase::find()->where(['dbserverid'=>$dbserverid])->orderBy(["uid"=>SORT_DESC])->asArray()->all();
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
     * 获取是否有新机出现
     *
     * @author		hqw		2016/07/07				初始化
     * @return boolean true 表示有新机，false 表示暂时没有新机
     */
    public static function getIsNewDbServer(){
    	$dbServerCount = UserDatabase::find()->groupBy(['dbserverid'])->count();
    		
    	//表示现时的机器数，假如添加了机器这里需要添加加了几部机器，一部机器就要$nowCount + 1
    	$nowCount = 8;
    		
    	if($dbServerCount > $nowCount){
    		return true;
    	}else{
    		return false;
    	}
    }
}
