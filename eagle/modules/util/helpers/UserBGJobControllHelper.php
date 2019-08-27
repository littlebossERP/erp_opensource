<?php

namespace eagle\modules\util\helpers;
use eagle\modules\util\models;
use eagle\modules\util\models\ConfigData;
use eagle\modules\util\models\GlobalConfigData;
use eagle\modules\util\models\UserBackgroundJobControll;

/**
 +------------------------------------------------------------------------------
 * 后台脚本控制helper---目前并不是控制所有的后台脚本，只是把有共性的脚本在这里控制
 +------------------------------------------------------------------------------
 * @package		Helper
 * @subpackage  Exception
 * @author		xjq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class UserBGJobControllHelper {
	

	/**
	 * 添加指定用户，到某个在运行的后台job服务的用户列表中。
	 * 其实某个后台job服务是一直在运行（如：推荐商品的计算）， 这个函数的意思就是，让该后台job也为指定的用户服务。
	 * @param  $puid
	 * @param  $backgroundJobName
	 * 
	 * @return true or false
	 */
	public static function createOrActivateBgJobForUser($puid,$backgroundJobName){
		$userBgControll=UserBackgroundJobControll::find()->where(["puid"=>$puid,"job_name"=>$backgroundJobName])->one();
		
		$nowTime=time();
		if ($userBgControll===null){
			$userBgControll=new UserBackgroundJobControll;
			$userBgControll->puid=$puid;
			$userBgControll->job_name=$backgroundJobName;
			$userBgControll->create_time=$nowTime;
			$userBgControll->update_time=$nowTime;
			return $userBgControll->save(false);
		}
		
		if ($userBgControll->is_active=='Y') return true;
		
		$userBgControll->is_active='Y';
		$userBgControll->update_time=$nowTime;
		return $userBgControll->save(false);
	}


	/**
	 * 停止指定的后台job再为指定的用户服务
	 * 
	 * @param  $puid
	 * @param  $backgroundJobName
	 *
	 * @return true or false
	 */
	public static function unactivateBgJobForUser($puid,$backgroundJobName){
		$userBgControll=UserBackgroundJobControll::find()->where(["puid"=>$puid,"job_name"=>$backgroundJobName])->one();
	
		if ($userBgControll===null) return true;
		
		$nowTime=time();
		
		if ($userBgControll->is_active=='N') return true;
	
		$userBgControll->is_active='N';
		$userBgControll->update_time=$nowTime;
		return $userBgControll->save(false);
	}	
	

}