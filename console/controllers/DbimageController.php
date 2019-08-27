<?php

namespace console\controllers;

use yii;
use yii\console\Controller;
use yii\filters\VerbFilter;
use eagle\modules\util\helpers\RedisHelper;
use Qiniu\json_decode;
use eagle\models\UserDatabase;
use eagle\modules\tool\helpers\MirroringHelper;
use eagle\modules\tool\models\ToolDbMirroring;

class DbimageController extends Controller
{
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台运行sql
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/04/13				初始化
	 +----------------------------------------------------------
	 *
	 *./yii dbimage/dbimagerun
	 **/
	public function actionDbimagerun($use_module = '') {
		$start_time = date('Y-m-d H:i:s');
		do{
		
		$type=RedisHelper::RedisGet('Utility',"DbImageSQLToRunResult");
		if($type=='true1'){
			echo 'start1';
			//生成镜像======================================================================================================================
			RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'false');
			$rtn=RedisHelper::RedisGet('Utility',"DbImageSQLToRun");
			$rtn_arr=json_decode($rtn,true);
			$textpuid_uid=$rtn_arr['textpuid_uid'];
			$mirr_name=$rtn_arr['mirr_name'];
			
			//查找机
			$command = \Yii::$app->get('db')->createCommand('use managedb')->execute();
			$textpuid_arr = UserDatabase::find()->select('did')->where(['uid'=>$textpuid_uid])->asArray()->all();
			 
				
			$dumpFileName= \Yii::getAlias('@eagle/web/attachment/dbimage/');
			// 判断保存备份sql的路径是否存在，不存在则创建
			if (!file_exists($dumpFileName)) {
				mkdir ("$dumpFileName");
			}
			
			$pos = strpos(\yii::$app->subdb->dsn, ':');
			$hostAndDbNameStr = strtolower(substr(\Yii::$app->subdb->dsn, $pos+1 ));
			list($hostStr , $dbNameStr) = explode(";", $hostAndDbNameStr);
			
			if (($pos = strpos($hostStr, '=')) !== false) {
				$db_host = substr( $hostStr , $pos+1 );
			}
			
			if (($pos = strpos($dbNameStr, '=')) !== false) {
				$db_name = substr( $dbNameStr , $pos+1 );
			}
				
			$db_user =  \yii::$app->subdb->username;
			$db_pwd = \yii::$app->subdb->password;
				
			$save_file_name=$mirr_name."_".date("YmdHms" , time())."_".$db_name;
			$save_file_name=trim($save_file_name);
			$user_sql_file_path = $dumpFileName.DIRECTORY_SEPARATOR.$save_file_name.".sql";
			$user_sql_file_name = "backup_".date("YmdH" , time())."_".$db_name.".sql";
			$user_sql_gz_name = "backup_".date("YmdH" , time())."_".$db_name.".tar.gz";
			$doBackup = false;
			
			$output = null;
			$status = null;
			set_time_limit(0);
			exec("/usr/bin/mysqldump -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name --skip-lock-tables --skip-add-locks 2>&1 > $user_sql_file_path " , $output , $status );
			
			if( 0 != $status ){
				\Yii::info('dbimage puid:,order_id:2  '.json_encode($output), "file");
				print_r("<xmp>");
				print_r($output);
				print_r("</xmp>");
				unlink ($user_sql_file_path);
			}
			else{
				$result = \yii::$app->db->createCommand("INSERT INTO `tool_db_mirroring`(`commit`, `createtime`, `alerttime`, `old_puid`,`status`) VALUES ('".$save_file_name."','".strtotime(date('Y-m-d H:i:s'))."','',".$textpuid.",1)")->execute();
				if(!$result)
					\Yii::info('dbimage puid:,order_id:3  数据库插入失败', "file");
			}
			
			echo 'success1';
			RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,'');
			RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
			
		}
		else if($type=='true2'){
			echo 'start2';
			//还原=====================================================================================================================
			RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'false');
			$rtn=RedisHelper::RedisGet('Utility',"DbImageSQLToRun");
			$rtn_arr=json_decode($rtn,true);
			$textpuid_uid=$rtn_arr['textpuid_uid'];
			$selectid=$rtn_arr['selectid'];
			
			//限制还原的权限
			$test_userid=MirroringHelper::$test_userid;
			if (!in_array($textpuid_uid,$test_userid['yifeng'])){
				\Yii::info('dbimage puid:,order_id:1  '.'没有权限还原'.$textpuid_uid.'的库', "file");
			}
			
			//查找机
			$command = \Yii::$app->get('db')->createCommand('use managedb')->execute();
			$textpuid_arr = UserDatabase::find()->select('did')->where(['uid'=>$textpuid_uid])->asArray()->all();
			 
			
			$query=ToolDbMirroring::find()->where('id='.$selectid)->asArray()->one();
			
			$dumpFileName= \Yii::getAlias('@eagle/web/attachment/dbimage/');
			// 判断保存备份sql的路径是否存在，不存在则创建
			if (!file_exists($dumpFileName)) {
				mkdir ("$dumpFileName");
			}
				
			$pos = strpos(\yii::$app->subdb->dsn, ':');
			$hostAndDbNameStr = strtolower(substr(\Yii::$app->subdb->dsn, $pos+1 ));
			list($hostStr , $dbNameStr) = explode(";", $hostAndDbNameStr);
				
			if (($pos = strpos($hostStr, '=')) !== false) {
				$db_host = substr( $hostStr , $pos+1 );
			}
				
			if (($pos = strpos($dbNameStr, '=')) !== false) {
				$db_name = substr( $dbNameStr , $pos+1 );
			}
				
			$db_user =  \yii::$app->subdb->username;
			$db_pwd = \yii::$app->subdb->password;
				
			$save_file_name=$query['commit'];
			$user_sql_file_path = $dumpFileName.DIRECTORY_SEPARATOR.$save_file_name.".sql";
			$db_save_tempname=$dumpFileName.DIRECTORY_SEPARATOR.$db_name.'_backup_'.date("YmdHms" , time())."sql";
			$doBackup = false;
				
			exec("/usr/bin/mysqldump -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name --skip-lock-tables --skip-add-locks 2>&1 > $db_save_tempname " , $output , $status );
			if( 0 != $status ){
				\Yii::info('dbimage puid:,order_id:3  '.'备份镜像失败，数据库不存在或未知错误', "file");
				print_r("<xmp>");
				print_r($output);
				print_r("</xmp>");
				unlink ($db_save_tempname);
			}
				
			//还原前清空库
			$connect = mysql_connect($db_host,$db_user,$db_pwd);
			mysql_select_db($db_name);
			$result = mysql_query("show table status from ".$db_name,$connect);
			while($data=mysql_fetch_array($result)) {
				mysql_query("drop table ".$data['Name']);
			}
				
			$output = null;
			$status = null;
			set_time_limit(0);
			// 		exec("D:\\wamp\\bin\\mysql\\mysql5.6.12\\bin\\mysql.exe -h $db_host -u$db_user -p$db_pwd $db_name < ".$user_sql_file_path,$output,$status);
			exec("/usr/bin/mysql -h $db_host -u$db_user -p$db_pwd $db_name  2>&1 < ".$user_sql_file_path,$output,$status);
			
			if( 0 != $status ){
				\Yii::info('dbimage puid:,order_id:4  '.'导入镜像失败，数据库不存在或未知错误', "file");
				print_r("<xmp>");
				print_r($output);
				print_r("</xmp>");
			}
			
			echo 'success2';
			RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,'');
			RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
			
		}
		else if($type=='false'){
			//后台=====================================================================================================================
			
					$dumpFileName= \Yii::getAlias('@eagle/web/tmp_export_file/dbimage/');
// 			$dumpFileName= \Yii::getAlias('@eagle/web/attachment/dbimage/');
			// 		$dumpFileName2= 'v2-test.littleboss.cn/attachment/dbimage';
			// 判断保存备份sql的路径是否存在，不存在则创建
			if (!file_exists($dumpFileName)) {
				mkdir ("$dumpFileName");
			}
			
			try{
			
				try {
					$db_host_2='';
					$db_name_2='';
					 
					$pos = strpos(\yii::$app->db->dsn, ':');
					$hostAndDbNameStr = strtolower(substr(\Yii::$app->db->dsn, $pos+1 ));
					list($hostStr , $dbNameStr) = explode(";", $hostAndDbNameStr);
			
					if (($pos = strpos($hostStr, '=')) !== false) {
						$db_host_2 = substr( $hostStr , $pos+1 );
					}
			
					if (($pos = strpos($dbNameStr, '=')) !== false) {
						$db_name_2 = substr( $dbNameStr , $pos+1 );
					}
				} catch (\Exception $e) {
					\Yii::info('dbimage puid:,order_id:1  '.$e->getMessage(), "file");
				}
					
				$db_host = \Yii::$app->params['db_queue2']['host'];
				$db_user =  \Yii::$app->params['db_queue2']['username'];
				$db_pwd = \Yii::$app->params['db_queue2']['password'];
				$db_name = 'usertemp';
				// 		$db_name=\Yii::$app->params['db_queue2']['dbPrefix'];
			
				$msg='';
				$code=true;
					
				//获取managedb
				// 			$db_name='';
				// 			$pos = strpos(\Yii::$app->params['db_queue2']['dsn'], ':');
				// 			$hostAndDbNameStr = strtolower(substr(\Yii::$app->params['db_queue2']['dsn'], $pos+1 ));
				// 			list($hostStr , $dbNameStr) = explode(";", $hostAndDbNameStr);
					
				// 			if (($pos = strpos($hostStr, '=')) !== false) {
				// 				$db_host_ma = substr( $hostStr , $pos+1 );
				// 			}
					
				// 			if (($pos = strpos($dbNameStr, '=')) !== false) {
				// 				$db_name = substr( $dbNameStr , $pos+1 );
				// 			}
			
				$start_time = date('Y-m-d H:i:s');
				echo "\nbackground service runnning for DbImage at $start_time \n";
				set_time_limit(0);
					
// 				do{
					try{
						
					$rtn=RedisHelper::RedisGet('Utility',"DbImageSQLToRun");
						print_r($rtn);
					if(!empty($rtn)){
					$rtn_arr=json_decode($rtn,true);
					print_r($rtn_arr);
					if(!empty($rtn_arr)){
					$errmsg=0;
					foreach ($rtn_arr as $keys=>$rtn_arrone){
					$start_time = date('Y-m-d H:i:s');
					echo "DB:".$keys." start runnning for DbImage at $start_time ...\n";
				
					$save_file_name=$keys;
					$user_sql_file_path = $dumpFileName.DIRECTORY_SEPARATOR.$save_file_name.".sql";
					print_r('url:'.$user_sql_file_path);
					//还原数据库到临时库
					$output = null;
					$status = null;
							// 							exec("D:\\wamp\\bin\\mysql\\mysql5.6.12\\bin\\mysql.exe -h $db_host -u$db_user -p$db_pwd $db_name < ".$user_sql_file_path,$output,$status);
							exec("/usr/bin/mysql -h $db_host -u$db_user -p$db_pwd $db_name 2>&1 < ".$user_sql_file_path,$output,$status);
							print_r('status:'.$status);
							print_r($output);
							if( 0 != $status ){
							$msg.=$save_file_name.'导入镜像失败，数据库不存在或未知错误\n';
							$code=false;
									\Yii::info('dbimage puid:,order_id:2导入镜像失败  '.json_encode($output), "file");
										continue;
									}
				
									try{
												//运行
												$result =\yii::$app->db->createCommand('use '.$db_name.';'.$rtn_arrone['parm'])->execute();
												print_r('re:'.$result);
					}
					catch (\Exception $err){echo "DB:".$keys." run statement fail. \n";}
				
					$end_time = date('Y-m-d H:i:s');
					echo "DB:".$keys." run statement success at $end_time. \n";
						
					//语句执行成功备份出来并删除原有的
					// 					if($rtn=='true'){
					$index=strpos($save_file_name,"_modify");
					$save_file_name_t=$save_file_name;
					if($index>0)
						$save_file_name_t=substr($save_file_name,0,$index);
					$save_file_name_new=$save_file_name_t.'_modify'.date("YmdHis" , time()).'';
					$user_sql_file_path_new = $dumpFileName.DIRECTORY_SEPARATOR.$save_file_name_new.".sql";
				
					$output = null;
					$status = null;
// 								exec("D:\\wamp\\bin\\mysql\\mysql5.6.12\\bin\\mysqldump.exe  -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name > $user_sql_file_path_new " , $output , $status );
					exec("/usr/bin/mysqldump -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name --skip-lock-tables --skip-add-locks 2>&1 > $user_sql_file_path_new " , $output , $status );
				
					if( 0 != $status ){
						unlink ($user_sql_file_path_new);
						$code=false;
						$msg.=$save_file_name.'生成镜像失败，数据库不存在或未知错误<br/>';
						\Yii::info('dbimage puid:,order_id:3生成镜像失败  '.json_encode($output), "file");
					}
					else{
	// 					echo "UPDATE `".$db_name_2."`.`tool_db_mirroring` SET `commit` =  '".$save_file_name_new."', `alerttime` =  '".strtotime(date('Y-m-d H:i:s'))."' WHERE  `tool_db_mirroring`.`id` ='".$rtn_arrone['id']."';";
						$result = \yii::$app->db->createCommand("UPDATE `".$db_name_2."`.`tool_db_mirroring` SET `commit` =  '".$save_file_name_new."', `alerttime` =  '".strtotime(date('Y-m-d H:i:s'))."' WHERE  `tool_db_mirroring`.`id` ='".$rtn_arrone['id']."';")->execute();
						if(!$result){
							$code=false;
							$msg.=$save_file_name.'数据库修改失败<br/>';
						}
						unlink ($dumpFileName.DIRECTORY_SEPARATOR.$save_file_name.".sql");
					}
				// 					}
				
									//清空临时库的表
									// 								$connect = mysql_connect($db_host,$db_user,$db_pwd);
	// 								mysql_select_db($db_name);
				// 								$result = mysql_query("show table status from ".$db_name,$connect);
				// 								while($data=mysql_fetch_array($result)) {
				// 									mysql_query("drop table ".$data['Name']);
				// 								}
				
							}
							if($errmsg==0){
							$end_time = date('Y-m-d H:i:s');
							echo "All DB run statement success at $end_time. \n";
							RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
							RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,'');
					}
					}
					}
					}
					catch (\Exception $err){
					echo $err->getMessage();
						\Yii::info('dbimage puid:,order_id:4  '.$err->getMessage(), "file");
						RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,'');
						RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
						}
						
// 					$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
// 					$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
// 				}while (($start_time > $half_hour_ago));
			}
			catch(\Exception $err){
				echo $err->getMessage();
				\Yii::info('dbimage puid:,order_id:5  '.$err->getMessage(), "file");
				RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,'');
				RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
			}
		}
		
		
		$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
		$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
		}while (($start_time > $half_hour_ago));

	}
	
	
	
	
	
	
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台运行分前端后端时有问题
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/04/13				初始化
	 +----------------------------------------------------------
	 *
	 *./yii dbimage/dbimagerun
	 **/
	public function actionDbimagerun2($use_module = '') {
			// 		$dumpFileName= \Yii::getAlias('@eagle/web/tmp_export_file/dbimage/');
			$dumpFileName= \Yii::getAlias('@eagle/web/attachment/dbimage/');
			// 		$dumpFileName2= 'v2-test.littleboss.cn/attachment/dbimage';
			// 判断保存备份sql的路径是否存在，不存在则创建
			if (!file_exists($dumpFileName)) {
				mkdir ("$dumpFileName");
			}
		
			// 		$url='v2-test.littleboss.cn/attachment/dbimage/v2-test-smart_20170424170437_user_297.sql';
			// 		$filename='v2-test-smart_20170424170437_user_297.sql';
			// 		$dir=\Yii::getAlias('@eagle/web/attachment/dbimage/');
			// 		$dir = realpath($dir);
			// 		//目录+文件
			// 		$filename = $dir . $filename;
			// 		//开始捕捉
			// // 		ob_start();
			// // 		readfile($url);
			// // 		$img = ob_get_contents();
			// // 		ob_end_clean();
		
			// 		$ch=curl_init();
			// 		$timeout=5;
			// 		curl_setopt($ch,CURLOPT_URL,$url);
			// 		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			// 		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
			// 		$img=curl_exec($ch);
			// 		curl_close($ch);
		
			// 		$size = strlen($img);
			// 		$fp2 = fopen($filename , "a");
			// 		fwrite($fp2, $img);
			// 		fclose($fp2);
			// 		unset($img,$url);
		
		
			try{
		
				try {
					$db_host_2='';
					$db_name_2='';
					 
					$pos = strpos(\yii::$app->db->dsn, ':');
					$hostAndDbNameStr = strtolower(substr(\Yii::$app->db->dsn, $pos+1 ));
					list($hostStr , $dbNameStr) = explode(";", $hostAndDbNameStr);
		
					if (($pos = strpos($hostStr, '=')) !== false) {
						$db_host_2 = substr( $hostStr , $pos+1 );
					}
		
					if (($pos = strpos($dbNameStr, '=')) !== false) {
						$db_name_2 = substr( $dbNameStr , $pos+1 );
					}
				} catch (\Exception $e) {
					\Yii::info('dbimage puid:,order_id:1  '.$e->getMessage(), "file");
				}
					
				$db_host = \Yii::$app->params['db_queue2']['host'];
				$db_user =  \Yii::$app->params['db_queue2']['username'];
				$db_pwd = \Yii::$app->params['db_queue2']['password'];
				$db_name = 'usertemp';
				// 		$db_name=\Yii::$app->params['db_queue2']['dbPrefix'];
		
				$msg='';
				$code=true;
					
				//获取managedb
				// 			$db_name='';
				// 			$pos = strpos(\Yii::$app->params['db_queue2']['dsn'], ':');
				// 			$hostAndDbNameStr = strtolower(substr(\Yii::$app->params['db_queue2']['dsn'], $pos+1 ));
				// 			list($hostStr , $dbNameStr) = explode(";", $hostAndDbNameStr);
					
				// 			if (($pos = strpos($hostStr, '=')) !== false) {
				// 				$db_host_ma = substr( $hostStr , $pos+1 );
				// 			}
					
				// 			if (($pos = strpos($dbNameStr, '=')) !== false) {
				// 				$db_name = substr( $dbNameStr , $pos+1 );
				// 			}
		
				$start_time = date('Y-m-d H:i:s');
				echo "\nbackground service runnning for DbImage at $start_time \n";
				set_time_limit(0);
					
				do{
				try{
						
					$rtn=RedisHelper::RedisGet('Utility',"DbImageSQLToRun");
						print_r($rtn);
							if(!empty($rtn)){
							$rtn_arr=json_decode($rtn,true);
							print_r($rtn_arr);
							if(!empty($rtn_arr)){
							$errmsg=0;
								foreach ($rtn_arr as $keys=>$rtn_arrone){
								$start_time = date('Y-m-d H:i:s');
								echo "DB:".$keys." start runnning for DbImage at $start_time ...\n";
		
								$save_file_name=$keys;
								$user_sql_file_path = $dumpFileName.DIRECTORY_SEPARATOR.$save_file_name.".sql";
								print_r('url:'.$user_sql_file_path);
								//还原数据库到临时库
								$output = null;
								$status = null;
								// 							exec("D:\\wamp\\bin\\mysql\\mysql5.6.12\\bin\\mysql.exe -h $db_host -u$db_user -p$db_pwd $db_name < ".$user_sql_file_path,$output,$status);
								exec("mysql -h $db_host -u$db_user -p$db_pwd $db_name 2>&1 < ".$user_sql_file_path,$output,$status);
								print_r('status:'.$status);
								print_r($output);
										if( 0 != $status ){
										$msg.=$save_file_name.'导入镜像失败，数据库不存在或未知错误\n';
											$code=false;
											\Yii::info('dbimage puid:,order_id:2导入镜像失败  '.json_encode($output), "file");
										continue;
										}
		
													try{
													//运行
													$result =\yii::$app->db->createCommand('use '.$db_name.';'.$rtn_arrone['parm'])->execute();
															print_r('re:'.$result);
													}
													catch (\Exception $err){echo "DB:".$keys." run statement fail. \n";}
		
															$end_time = date('Y-m-d H:i:s');
													echo "DB:".$keys." run statement success at $end_time. \n";
														
													//语句执行成功备份出来并删除原有的
													// 					if($rtn=='true'){
					$index=strpos($save_file_name,"_modify");
					$save_file_name_t=$save_file_name;
					if($index>0)
						$save_file_name_t=substr($save_file_name,0,$index);
						$save_file_name_new=$save_file_name_t.'_modify'.date("YmdHis" , time()).'';
						$user_sql_file_path_new = $dumpFileName.DIRECTORY_SEPARATOR.$save_file_name_new.".sql";
		
						$output = null;
						$status = null;
						// 								exec("D:\\wamp\\bin\\mysql\\mysql5.6.12\\bin\\mysqldump.exe  -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name > $user_sql_file_path_new " , $output , $status );
					exec("mysqldump -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name --skip-lock-tables --skip-add-locks 2>&1 > $user_sql_file_path_new " , $output , $status );
		
					if( 0 != $status ){
					unlink ($user_sql_file_path_new);
					$code=false;
					$msg.=$save_file_name.'生成镜像失败，数据库不存在或未知错误<br/>';
						\Yii::info('dbimage puid:,order_id:3生成镜像失败  '.json_encode($output), "file");
							}
										else{
	// 										echo "UPDATE `".$db_name_2."`.`tool_db_mirroring` SET `commit` =  '".$save_file_name_new."', `alerttime` =  '".strtotime(date('Y-m-d H:i:s'))."' WHERE  `tool_db_mirroring`.`id` ='".$rtn_arrone['id']."';";
		$result = \yii::$app->db->createCommand("UPDATE `".$db_name_2."`.`tool_db_mirroring` SET `commit` =  '".$save_file_name_new."', `alerttime` =  '".strtotime(date('Y-m-d H:i:s'))."' WHERE  `tool_db_mirroring`.`id` ='".$rtn_arrone['id']."';")->execute();
		if(!$result){
												$code=false;
													$msg.=$save_file_name.'数据库修改失败<br/>';
					}
															unlink ($dumpFileName.DIRECTORY_SEPARATOR.$save_file_name.".sql");
															}
															// 					}
		
															//清空临时库的表
															// 								$connect = mysql_connect($db_host,$db_user,$db_pwd);
	// 								mysql_select_db($db_name);
															// 								$result = mysql_query("show table status from ".$db_name,$connect);
															// 								while($data=mysql_fetch_array($result)) {
															// 									mysql_query("drop table ".$data['Name']);
															// 								}
		
							}
							if($errmsg==0){
							$end_time = date('Y-m-d H:i:s');
							echo "All DB run statement success at $end_time. \n";
							RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
							RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,'');
								}
							}
							}
							}
							catch (\Exception $err){
						echo $err->getMessage();
							\Yii::info('dbimage puid:,order_id:4  '.$err->getMessage(), "file");
							RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,'');
									RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
					}
			
				$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
				$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
		}while (($start_time > $half_hour_ago));
			}
			catch(\Exception $err){
				echo $err->getMessage();
				\Yii::info('dbimage puid:,order_id:5  '.$err->getMessage(), "file");
					RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,'');
				RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
			}
	}
	
	
}