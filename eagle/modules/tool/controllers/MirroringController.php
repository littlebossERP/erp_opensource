<?php
namespace eagle\modules\tool\controllers;

use eagle\modules\util\helpers\TranslateHelper;
use Yii;
use yii\filters\VerbFilter;
use eagle\modules\tool\models;
use eagle\modules\tool\models\ToolDbMirroring;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\tool\helpers\MirroringHelper;
use eagle\models\UserBase;
use eagle\models\UserDatabase;

class MirroringController extends \eagle\components\Controller{
	
	public $enableCsrfValidation = FALSE;
	
	public function behaviors()
	{
		return [
		'access' => [
		'class' => \yii\filters\AccessControl::className(),
		'rules' => [
		[
		'allow' => true,
		'roles' => ['@'],
		],
		],
		],
		'verbs' => [
		'class' => VerbFilter::className(),
		'actions' => [
		'delete' => ['post'],
		],
		],
		];
	}
	
	/**
	 +----------------------------------------------------------
	 * 页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/04/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionIndex(){
		$query = ToolDbMirroring::find()->where('status=1')->asArray()->all();
		
		$commit=array();
		if(!empty($query)){
			foreach ($query as $queryone){
				$commit[$queryone['id']]=$queryone['commit'];
			}
		}
	
		return $this->render('mirroring',['mirroringlist'=>$commit]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 生成镜像
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/04/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionCreate(){
		try{
			$textpuid_uid=empty($_POST['textpuid'])?'':$_POST['textpuid'];
			$mirr_name=empty($_POST['mirr_name'])?'Default':$_POST['mirr_name'];
			
			//查找机
			$textpuid_arr = UserDatabase::find()->select('did')->where(['uid'=>$textpuid_uid])->asArray()->all();
			 
	
			$dumpFileName= \Yii::getAlias('@eagle/web/tmp_export_file/dbimage/');
// 			$dumpFileName= \Yii::getAlias('@eagle/web/attachment/dbimage/');
	// 		$dumpFileName= \Yii::getAlias(\Yii::$app->params['mysql_bak_path']);
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
	// 		exec("D:\\wamp\\bin\\mysql\\mysql5.6.12\\bin\\mysqldump.exe  -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name > $user_sql_file_path " , $output , $status );
			exec("/usr/bin/mysqldump -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name --single-transaction --skip-lock-tables --skip-add-locks 2>&1 > $user_sql_file_path " , $output , $status );
// 			exec("mysqldump -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." --databases $db_name --tables aliexpress_freight_template --skip-lock-tables --skip-add-locks 2>&1 > $user_sql_file_path " , $output , $status );
			
			if( 0 != $status ){
	// 			print_r($db_host.'-'.$db_name.'-'.$db_user.'-'.$db_pwd);
				print_r("<xmp>");
				print_r($output);
				print_r("</xmp>");
				unlink ($user_sql_file_path);
				return json_encode(['code'=>false,'message'=>'生成镜像失败，数据库不存在或未知错误']);
			}
			else{
				$result = \yii::$app->db->createCommand("INSERT INTO `tool_db_mirroring`(`commit`, `createtime`, `alerttime`, `old_puid`,`status`) VALUES ('".$save_file_name."','".strtotime(date('Y-m-d H:i:s'))."','',".$textpuid.",1)")->execute();
				if(!$result)
					return json_encode(['code'=>false,'message'=>'数据库插入失败']);
			}
			
			return json_encode(['code'=>true,'message'=>'']);

			
// 			$textpuid_uid=empty($_POST['textpuid'])?'':$_POST['textpuid'];
// 			$mirr_name=empty($_POST['mirr_name'])?'Default':$_POST['mirr_name'];
// 			//写入Redis运行
// 			$arr=array(
// 					'textpuid_uid'=>$textpuid_uid,
// 					'mirr_name'=>$mirr_name,
// 			);
// 			RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,json_encode($arr));
// 			RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true1');
				
// 			return json_encode(['code'=>true,'message'=>'']);
		
		}
		catch (\Exception $err){
			return json_encode(['code'=>false,'message'=>$err->getMessage()]);
		}

	}
	
	/**
	 +----------------------------------------------------------
	 * 还原镜像
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/04/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionCopy(){
		try{
			$textpuid_uid=empty($_POST['textpuid'])?'':$_POST['textpuid'];
			$selectid=empty($_POST['selectid'])?'':$_POST['selectid'][0];
			
			//限制还原的权限
			$test_userid=MirroringHelper::$test_userid;
			if (!in_array($textpuid_uid,$test_userid['yifeng'])){
				return json_encode(['code'=>false,'message'=>'没有权限还原'.$textpuid_uid.'的库']);
			}
	
			//查找机
			$textpuid_arr = UserDatabase::find()->select('did')->where(['uid'=>$textpuid_uid])->asArray()->all();
			 
	
			$query=ToolDbMirroring::find()->where('id='.$selectid)->asArray()->one();
	
			$dumpFileName= \Yii::getAlias('@eagle/web/tmp_export_file/dbimage/');
// 			$dumpFileName= \Yii::getAlias('@eagle/web/attachment/dbimage/');
	// 		$dumpFileName= \Yii::getAlias(\Yii::$app->params['mysql_bak_path']);
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
			
			exec("/usr/bin/mysqldump -h $db_host -u$db_user ".(empty($db_pwd)?"":"-p$db_pwd ")." $db_name --single-transaction --skip-lock-tables --skip-add-locks 2>&1 > $db_save_tempname " , $output , $status );
			if( 0 != $status ){
				print_r("<xmp>");
				print_r($output);
				print_r("</xmp>");
				unlink ($db_save_tempname);
				return json_encode(['code'=>false,'message'=>'备份镜像失败，数据库不存在或未知错误']);
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
				print_r("<xmp>");
				print_r($output);
	// 			print_r("mysql -h $db_host -u$db_user -p$db_pwd $db_name  2>&1 < ".$user_sql_file_path);
				print_r("</xmp>");
				return json_encode(['code'=>false,'message'=>'导入镜像失败，数据库不存在或未知错误']);
			}
			
			return json_encode(['code'=>true,'message'=>'']);
			
			
// 			$textpuid_uid=empty($_POST['textpuid'])?'':$_POST['textpuid'];
// 			$selectid=empty($_POST['selectid'])?'':$_POST['selectid'][0];
// 			//写入Redis运行
// 			$arr=array(
// 					'textpuid_uid'=>$textpuid_uid,
// 					'selectid'=>$selectid,
// 			);
// 			RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,json_encode($arr));
// 			RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true2');
			
// 			return json_encode(['code'=>true,'message'=>'']);
			
			
		}
		catch (\Exception $err){
			return json_encode(['code'=>false,'message'=>$err->getMessage()]);
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * run sql
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/04/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionRun(){				
		$Sentence=empty($_POST['Sentence'])?'':$_POST['Sentence'];
		if(empty($Sentence)){
			if(isset($_FILES['file'])){
				$_sql = file_get_contents($_FILES['file']['tmp_name']);
				$parm=$_sql;
			}
			else{
				return json_encode(['code'=>false,'message'=>'没有语句运行']);
			}
		}
		else{
			$parm=$Sentence;
		}

		$query=ToolDbMirroring::find()->where('status=1')->asArray()->all();
		//组织语句
		foreach ($query as $queryone){
			$save_file_name=$queryone['commit'];
			$request[$save_file_name]=array(
					'id'=>$queryone['id'],
					'parm'=>$parm,
			);
		}
		//写入Redis运行
		RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,json_encode($request));
		//写入Redis判断是否运行完毕
		RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'false');
			
		return json_encode(['code'=>true,'message'=>'']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 判断是否运行完毕
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/04/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetmsg(){
// 		RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
// 		$rtn=RedisHelper::RedisDel('Utility',"DbImageSQLToRun");die;
		$rtn='';
		try{
			$rtn=RedisHelper::RedisGet('Utility',"DbImageSQLToRunResult");
		}
		catch (\Exception $err){$rtn='';}
		if($rtn=='false')
			return json_encode(['code'=>false,'message'=>'']);
		else 
			return json_encode(['code'=>true,'message'=>'']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 清空
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/04/24				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDeleteall(){
		try{
			 
			$result = \yii::$app->db->createCommand("DELETE FROM tool_db_mirroring;")->execute();
			if(!$result)
				return json_encode(['code'=>false,'message'=>'失败']);
			
			$dumpFileName= \Yii::getAlias('@eagle/web/attachment/dbimage/');
			$dh=opendir($dumpFileName);
			while ($file=readdir($dh))
			{
				if($file!="." && $file!="..")
				{
					$fullpath=$dumpFileName."/".$file;
			
					if(!is_dir($fullpath))
					{
						unlink($fullpath);
					}
					else
					{
						deldir($fullpath);
					}
				}
			}
			
			closedir($dh);
			
			return json_encode(['code'=>true,'message'=>'']);
		}
		catch (\Exception $err){
			return json_encode(['code'=>false,'message'=>$err->getMessage()]);
		}
	}
	
	
	public function actionDie(){
		try{
			RedisHelper::RedisSet('Utility',"DbImageSQLToRun" ,'');
			RedisHelper::RedisSet('Utility',"DbImageSQLToRunResult" ,'true');
				
			return json_encode(['code'=>true,'message'=>'']);
		}
		catch (\Exception $err){
			return json_encode(['code'=>false,'message'=>$err->getMessage()]);
		}
	}

}

?>