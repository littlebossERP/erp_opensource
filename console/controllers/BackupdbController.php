<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use \eagle\modules\purchase\helpers\PurchaseHelper;
use \eagle\modules\tracking\helpers\TrackingHelper;
use \eagle\modules\util\helpers\ImageHelper;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\helpers\UserHelper;
/**
 * Backupdb controller
 */
class BackupdbController extends Controller {
	/**
	 *  备份user数据库及manage数据库。备份的user数据库是有账号（包括子账号）在 20 天内登录过的
	 *  
	 *  ./yii backupdb/auto-backup alluser 跳过登录检测，备份所有user数据库
	 *  ./yii backupdb/auto-backup dumpManagedbOnly 仅备份managedb
	 */
	public function actionAutoBackup(){
		define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
		$interval = 20 * 24 * 3600;// 20天登录时间间隔
		
		$backup_all_user_db = false;
		$dump_managedb_only = false;
		if(!empty($_SERVER['argv'][2])){
			if('alluser' == $_SERVER['argv'][2])  // 0 为 yii , 1为backupdb/auto-backup , 2为传入参数 
				$backup_all_user_db = true; // $backup_all_user_db 为true 时，忽略登录情况备份所有user数据库
			if('dumpManagedbOnly' == $_SERVER['argv'][2])
				$dump_managedb_only = true;
		}
			
		
		// 不备份的user db 的表
		$userDbIgnoreTable = array(
			"sys_invoke_jrn",
			"sys_log",
			"amazon_open_list_report",
			"dely_carrier",
			"dely_shipping_method",
		);
		
		// 不备份的manage db 的表
		$managedbIgnoreTable = array(
			"global_log",
			"ebay_categoryfeature",
			"aliexpress_category",
			"ebay_specific",
			"ebay_category",
			"ut_global_log",
			"tracker_api_sub_queue",
			"tracker_api_queue",
		);
		
		// 备份目录
		$saveSqlPath = \Yii::getAlias(\Yii::$app->params['mysql_bak_path']).DIRECTORY_SEPARATOR.date("Ymd");
		$backupDbTimeReport = array();
		$successBackupDb = array();
		$backupErrorInfo = array();
		
		list($msec,$sec) = explode(' ', microtime());
		$backupAllDbStarttime = $sec + $msec ; // 备份所有数据库开始时间点。 单位：毫秒
		
		$bgm = new BackgroundMonitor();
		$bgm->create_time = date("Y-m-d H:i:s",(int)$backupAllDbStarttime);
		
		// 判断保存备份sql的路径是否存在，不存在则创建
		if (! file_exists ( $saveSqlPath )) {
			if (! mkdir ( $saveSqlPath, 0764, true )) {
				\Yii::error("Can not mkdir: $saveSqlPath , backup db interrup.","file");
				$bgm->last_end_time = date("Y-m-d H:i:s",time());
				$bgm->last_total_time = (time() - (int)$backupAllDbStarttime);
				$bgm->job_name = "backup all db";
				$bgm->status = "备份失败";
				$bgm->json_params = json_encode(array('备份成功'=>'' , '备份失败'=>'备份sql路径创建失败。' , '备份时间报告'=>'') , JSON_HEX_APOS);
				$bgm->save(false);
				return false;
			}
		}
		
		if(empty(\Yii::$app->params['backupdb'])){// 检查config 有否配置账号
			\Yii::error("Please set up the backup db info in params.php , backup db interrup.","file");
			$bgm->last_end_time = date("Y-m-d H:i:s",time());
			$bgm->last_total_time = (time() - (int)$backupAllDbStarttime);
			$bgm->job_name = "backup all db";
			$bgm->status = "备份失败";
			$bgm->json_params = json_encode(array('备份成功'=>'' , '备份失败'=>'备份数据库信息未设置。' , '备份时间报告'=>'') , JSON_HEX_APOS);
			$bgm->save(false);
			return false;
		}
			
		// 1. backup managedb
		\Yii::info("start to backup managedb","file");
		list($msec,$sec) = explode(' ', microtime());
		$backupManagedbStarttime = $sec + $msec ; // 备份manage数据库开始时间点。 单位：毫秒
		$doBackup = true;
		
		if (($pos = strpos(\Yii::$app->params['backupdb']['db']['dsn'], ':')) !== false) {
			$hostAndDbNameStr = strtolower(substr(\Yii::$app->db->dsn, $pos+1 ));
			list($hostStr , $dbNameStr) = explode(";", $hostAndDbNameStr);
				
			if (($pos = strpos($hostStr, '=')) !== false) {
				$db_host = substr( $hostStr , $pos+1 );
			}
				
			if (($pos = strpos($dbNameStr, '=')) !== false) {
				$db_name = substr( $dbNameStr , $pos+1 );
			}
		}else{
			$doBackup = false;
			\Yii::error("fail to get managedb host and name.","file");
		}
		
		$db_user =  \Yii::$app->db->username;
		$db_pwd = \Yii::$app->db->password;
		$manage_sql_file_name = "backup_".date("YmdH" , time())."_".$db_name.".sql";
		$manage_gz_file_name = "backup_".date("YmdH" , time())."_".$db_name.".tar.gz";
		$manage_sql_file_path = $saveSqlPath.DIRECTORY_SEPARATOR."backup_".date("YmdH" , time())."_".$db_name.".sql";
		
		if($doBackup){
			$ignoreTableStrArr = array();
			foreach ($managedbIgnoreTable as $table){
				// 检查被ignore 的table是否存在
// 				$isExist = \Yii::$app->db->createCommand('SHOW TABLES LIKE "'.$table.'" ')->queryAll();// dzt20150525 忽略检查
// 				if(!empty($isExist))
				$ignoreTableStrArr[] = "--ignore-table=$db_name.$table ";
			}
		
			$output = null;
			$status = null;
			exec("mysqldump  -h$db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")."  $db_name  --single-transaction ".implode("\\", $ignoreTableStrArr)." 2>&1 > $manage_sql_file_path" , $output , $status );
			if( 0 != $status ){
				$backupErrorInfo[] = "$db_name : ".implode(EOL, $output);
				\Yii::error("shell exec fail: fail to backup db: $db_name to $manage_sql_file_path . Shell output: ".implode(EOL, $output),"file");
			}else{
				$output = null;
				$status = null;
				exec("cd $saveSqlPath ; tar -zcvf $manage_gz_file_name $manage_sql_file_name" , $output , $status);
					
				if( 0 != $status ){
					\Yii::error("shell exec fail: fail to backup db: $db_name to $manage_sql_file_path . Shell output: ".implode(EOL, $output),"file");
				}
				$successBackupDb[] = "$db_name : ".implode(EOL, $output);
				\Yii::info("shell exec success: backup db: $db_name to $manage_sql_file_path success.  Shell output: ".implode(EOL, $output),"file");
			}
			
			$rmoutput = null;
			$rmstatus = null;
			exec("rm $manage_sql_file_path" , $rmoutput , $rmstatus);
		}
		
		list($msec,$sec) = explode(' ', microtime());
		$backupManagedbEndtime = $sec + $msec ; // 备份manage数据库结束时间点。 单位：毫秒
		$backupDbTimeReport[] = "backup managedb use time mt=".(int)(($backupManagedbEndtime - $backupManagedbStarttime) * 1000)." ms ";
		\Yii::info("backup managedb finish","file");
		
		// 2. backup user db 
		if(!$dump_managedb_only){ // 命令行参数控制 ，跳过备份数据库
			$db_host = \Yii::$app->params['backupdb']['subdb']['host'];
			$db_user =  \Yii::$app->params['backupdb']['subdb']['username'];
			$db_pwd = \Yii::$app->params['backupdb']['subdb']['password'];
			$testUsers = UserHelper::getTestPuidArr();
			
			// 先获取主账号，后面再判断子账号登录情况。 可以加上uids filter ↓ 。 这里不从sql添加时间过滤 ， 后面
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
			list($msec,$sec) = explode(' ', microtime());
			$backupAllUserDbStarttime = $sec + $msec ; // 备份所有user数据库开始时间点。 单位：毫秒
			\Yii::info("start to backup user db","file");
			
			// 备份20天内登录(包括子用户)的user db ， 但当$backup_all_user_db 为true 时，忽略登录情况备份所有user数据库
			foreach($mainUsers as $puser){
				if(in_array($puser['uid'] , $testUsers)) // 跳过测试用户
					continue;
					
				$db_name = \Yii::$app->params['backupdb']['subdb']['dbPrefix'].$puser['uid'];
				$user_sql_file_path = $saveSqlPath.DIRECTORY_SEPARATOR."backup_".date("YmdH" , time())."_".$db_name.".sql";
				$user_sql_file_name = "backup_".date("YmdH" , time())."_".$db_name.".sql";
				$user_sql_gz_name = "backup_".date("YmdH" , time())."_".$db_name.".tar.gz";
				$doBackup = false;
				list($msec,$sec) = explode(' ', microtime());
				$backupSubUserDbStarttime = $sec + $msec ; // 备份user数据库开始时间点。 单位：毫秒
					
				// 主账号登录情况
				if(!empty($puser['last_login_date']) && ( time() - $puser['last_login_date']) < $interval ){
					$doBackup = true;
				}
					
				// 子账号登录情况
				if(!$doBackup){
					$subUsers = UserBase::find()->where(['puid'=>$puser['uid']])->andWhere(['>=', 'last_login_date', time() - $interval])->asArray()->all();
					if(!empty($subUsers)){
						$doBackup = true;
						\Yii::info("user_".$puser['uid']." backup by sub account.","file");
					}
				}
					
				
				if($doBackup || $backup_all_user_db){
					$ignoreTableStrArr = array();
			
					foreach ($userDbIgnoreTable as $table){
// 						// 检查被ignore 的table是否存在
// 						$isExist = \Yii::$app->subdb->createCommand('SHOW TABLES LIKE "'.$table.'" ')->queryAll();// dzt20150525 忽略检查
// 						if(!empty($isExist))
						$ignoreTableStrArr[] = "--ignore-table=$db_name.$table ";
					}
			
					$output = null;
					$status = null;
					exec("mysqldump  -h$db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name  --single-transaction ".implode("\\", $ignoreTableStrArr)." 2>&1 > $user_sql_file_path " , $output , $status );
					if( 0 != $status ){
						$backupErrorInfo[] = "$db_name : ".implode(EOL, $output);
						\Yii::error("shell exec fail: fail to backup db: $db_name to $user_sql_file_path . Shell output: ".implode(EOL, $output),"file");
					}else{
						$output = null;
						$status = null;
						exec("cd $saveSqlPath ; tar -zcvf $user_sql_gz_name $user_sql_file_name" , $output , $status);
						
						if( 0 != $status ){
							\Yii::error("shell exec fail: fail to backup db: $db_name to $user_sql_file_path . Shell output: ".implode(EOL, $output),"file");
						}
						$successBackupDb[] = "$db_name : ".implode(EOL, $output);
						\Yii::info("shell exec success: backup db: $db_name to $user_sql_file_path success. Shell output: ".implode(EOL, $output),"file");
						list($msec,$sec) = explode(' ', microtime());
						$backupSubUserDbEndtime = $sec + $msec ; // 备份user数据库结束时间点。 单位：毫秒
						$backupDbTimeReport[] = "backup db: user_".$puser['uid']." use time ut=".(int)(($backupSubUserDbEndtime - $backupSubUserDbStarttime) * 1000)." ms ";
					}
					
					$rmoutput = null;
					$rmstatus = null;
					exec("rm $user_sql_file_path" , $rmoutput , $rmstatus);
				}
			}
			
			list($msec,$sec) = explode(' ', microtime());
			$backupAllUserDbEndtime = $sec + $msec ; // 备份所有user数据库结束时间点。 单位：毫秒
			$backupDbTimeReport[] = "backup all user db use time aut=".(int)(($backupAllUserDbEndtime-$backupAllUserDbStarttime) * 1000)." ms ";
			
			\Yii::info("backup user db finish","file");
		}
		
		list($msec,$sec) = explode(' ', microtime());
		$backupAlldbEndtime = $sec + $msec ; // 备份所有数据库结束时间点。 单位：毫秒
		$backupDbTimeReport[] = "backup all db use time at=".(int)(($backupAlldbEndtime - $backupAllDbStarttime) * 1000)." ms ";
		
		\Yii::info("backup db time report:".EOL.implode(EOL, $backupDbTimeReport),"file");
		
		// 删除本次备份目录$saveSqlPath下所有 .sql文件
		exec("rm ".$saveSqlPath.DIRECTORY_SEPARATOR."*.sql" , $output , $status);
		
		$bgm->last_end_time = date("Y-m-d H:i:s",(int)$backupAlldbEndtime);
		$bgm->last_total_time = ($backupAlldbEndtime - $backupAllDbStarttime);
		$bgm->job_name = "backup all db";
		$bgm->status = "";
		if(!empty($backupErrorInfo) && !empty($successBackupDb))
			$bgm->status = "部分备份成功";
		else if(empty($successBackupDb))
			$bgm->status = "全部备份失败";
		else if(empty($backupErrorInfo))
			$bgm->status = "全部备份成功";
		else 
			$bgm->status = "无备份记录";
		
		$bgm->json_params = json_encode(array('备份成功'=>$successBackupDb , '备份失败'=>$backupErrorInfo , '备份时间报告'=>implode(';', $backupDbTimeReport)) , JSON_HEX_APOS);
		if(!$bgm->save()){
			\Yii::error("Fail saving BackgroundMonitor object.".print_r($bgm->getErrors()),"file");
		}
	}
	
}