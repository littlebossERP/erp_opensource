<?php

namespace eagle\helpers;
use eagle\modules\util\models;
use eagle\modules\util\models\ConfigData;
use eagle\models\UserInfo;
use eagle\models\UserBase;

/**
 +------------------------------------------------------------------------------
 * 用户管理helper
 +------------------------------------------------------------------------------
 * @package		Helper
 * @subpackage  Exception
 * @author		xjq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class UserHelper {

	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取uid对应的用户的全名	 
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/03/10				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public  static function getFullNameByUid($uid){
    	$userInfo=UserInfo::findOne(['uid' => $uid]);
    	if ($userInfo===null) return "";
    	return $userInfo->familyname;
		
	}
	
	/**
	 * 获取测试用的puid 列表
	 * 
	 * return array("34","223","2323")
	 */
	public static function getTestPuidArr(){
		//自己人使用的puid
		$testPuidArr=[0,1,128,678,663,480,635,469,666,72,9,4,135,129,11,2,664,680,388,297,212,104,62,820,3461];
		return $testPuidArr; 
	}

	/**
	 * 获取指定时间段内注册的puid列表。  如：  获取3个小时之内有注册的用户的puid列表
	 * @params
	 *   $interval---- 时间段。单位是小时
	 *
	 *  @return------ 元素是puid的array  如：    array("2","4".....)
	 *                没有结果的情况下返回空的array()
	 */
	public static function getRegistedPuidArrByInterval($interval){
		$connection = \Yii::$app->db;
		$rows = $connection->createCommand('SELECT uid,register_date FROM user_base where register_date>=:last_time and puid=0 and user_app_type=2 and is_active=1',[":last_time"=>time()-$interval*3600])->queryAll();
		$puidArr=array();
		foreach($rows as $row){			
			$puidArr[]=$row["uid"];				
		}
		return $puidArr;
		//	$posts = $command->queryAll();
	
	
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取指定puid下的所有uid和username
	 * 包括puid用户
	 * 如果用户有设置账户名，  就返回用户账户名
	 * 如果没有，就返回email
	 +---------------------------------------------------------------------------------------------
	 * @param int $puid
	 +---------------------------------------------------------------------------------------------
	 * @return array(
	 * 		'uid1'=> array('uid'=>'uid1' , 'username'=>'username1' , 'puid'=>0) , //puid 等于0为主账号 
	 * 		'uid2'=> array('uid'=>'uid2' , 'username'=>'username2' , 'puid'=>uid1) , 	
	 * 		'uid3'=> array('uid'=>'uid3' , 'username'=>'username3' , 'puid'=>uid1) , 		
	 * 		...
	 * 	);
	 */
	public static function getUsersNameByPuid($puid){
		$users = UserBase::find()->where(['uid'=>$puid])->orWhere(['puid'=>$puid])->asArray()->all();
		$usersNameArr = array();
		foreach ($users as $user){
			$userInfo = UserInfo::findOne(['uid'=>$user['uid']]);
			if(!empty($userInfo) && $userInfo->familyname <> ''){
				$username = $userInfo->familyname;
			}else{
				$username = $user['email'];
			}
			
			$usersNameArr[$user['uid']] = ['uid'=>$user['uid'] , 'username'=>$username , 'puid'=>$user['puid']];
		}
		
		return $usersNameArr;
	}
	

}