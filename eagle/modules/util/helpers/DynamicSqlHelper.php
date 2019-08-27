<?php

namespace eagle\modules\util\helpers;

use eagle\modules\util\models\EDBManager;
use eagle\models\UserDatabase;
class DynamicSqlHelper {
	
	static $KeyFileMapping = [
// 		'user_20160316_dzt'=>'user_20160316_dzt.sql',
	];// key ,sql文件mapping
	
	/**
	 * 支持客户通过 前端 操作，动态创建数据表。
	 * $key值先通过查找静态数组$KeyFileMapping 是否存在与$key值匹配的sql文件，如果没有就执行 $key.sql
	 * 
	 * @param string $key
	 * @param int $puid   // 前端使用不传入puid,后台任务使用则要传入puid
	 * @return boolean
	 */
	public static function run($key="",$puid=false){
		\Yii::info("DynamicSqlHelper::run key:$key","file");
		
		if(empty($key)){
			\Yii::error("DynamicSqlHelper::run key is needed:$key .","file");
			return false;
		}
		
		$sqlFileName = "";
		if(isset(self::$KeyFileMapping[$key])){
			$sqlFileName = self::$KeyFileMapping[$key];
		}else{// 如果不存在mapping则自动补上sql后缀
			$sqlFileName = $key.".sql";
		}
		
		if(false === $puid)
			$puid = \Yii::$app->user->identity->getParentUid();
		
		if(empty($puid)){
			\Yii::error("DynamicSqlHelper::run puid cannot be empty","file");
			return false;
		}

		 
		$userDatabase = UserDatabase::findOne(['uid'=>$puid]);
		if(empty($userDatabase)){
			\Yii::error("DynamicSqlHelper::run UserDatabase record not exists","file");
			return false;
		}
		\Yii::info("DynamicSqlHelper::run got userDatabase record","file");
		try{
			\Yii::info("DynamicSqlHelper::run before run sql:$sqlFileName","file");
			$dbM = new EDBManager($userDatabase->ip,$userDatabase->dbusername,$userDatabase->password,"user_{$userDatabase->did}");
			return $dbM->createFromFile(\Yii::getAlias('@eagle').'/doc/sql/dynamic_user_sql/'.$sqlFileName,null,'');
		} catch (\Exception $e) {
			\Yii::error("DynamicSqlHelper::run Exception:".print_r($e,true),"file");
			return false;
		}
	}
}

?>