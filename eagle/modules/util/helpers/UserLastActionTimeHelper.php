<?php

namespace eagle\modules\util\helpers;
use eagle\modules\util\models;
use eagle\modules\util\models\ConfigData;
use eagle\modules\util\models\GlobalConfigData;
use eagle\modules\util\models\UserLastActivityTime;

/**
 +------------------------------------------------------------------------------
 * 帮助类----用户最近一次活跃时间
 +------------------------------------------------------------------------------
 * @package		Helper
 * @subpackage  Exception
 * @author		xjq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class UserLastActionTimeHelper {
	
	/**
	 * 获取指定时间段内活跃的puid列表。  如：  获取3个小时之内有活跃的用户的puid列表
	 * @params 
	 *   $interval---- 时间段。单位是小时
	 *   
	 *  @return------ 元素是puid的array  如：    array("2","4".....)
	 *                没有结果的情况下返回空的array()
	 */
	public static function getPuidArrByInterval($interval){
		$connection = \Yii::$app->db;		
		$rows = $connection->createCommand('SELECT puid FROM user_last_activity_time where last_activity_time>=:last_time',[":last_time"=>date("Y-m-d H:i:s",time()-$interval*3600)])->queryAll();
		$puidArr=array();
		foreach($rows as $row){
			$puidArr[]=$row["puid"];
		}
		return $puidArr;
	//	$posts = $command->queryAll();
	}

	/**
	 * 获取该puid的最后活跃时间
	 * @params
	 *   $puid ----  puid
	 *
	 *  @return------ 2014-6-10 16:20:00
	 */
	public static function getLastTouchTimeByPuid($puid){
		$connection = \Yii::$app->db;
		$lastTouch = $connection->createCommand('SELECT last_activity_time FROM user_last_activity_time where puid='.$puid)->queryScalar();
		return $lastTouch;
		//	$posts = $command->queryAll();
	}
	
	public static function saveLastActionTime(){
		$uid=\Yii::$app->user->id;
		if ($uid=="") return false; //没登陆
		
		$puid=\Yii::$app->user->identity->getParentUid();
		
		$userLastTimeObject=UserLastActivityTime::find()->where(["puid"=>$puid])->one();
		if ($userLastTimeObject===null){
			$userLastTimeObject=new UserLastActivityTime();
			$userLastTimeObject->puid=$puid;
		}
		$userLastTimeObject->last_activity_time=date("Y-m-d H:i:s");
		$userLastTimeObject->save(false);
		
		return true;
	}
	
	/**
	 * 批量获取该$puidArr数组的最后活跃时间
	 * @params
	 *   $puidArr ----  puid
	 *
	 *  @return Array
	 */
	public static function getLastTouchTimeByPuidArr($puidArr){
		$connection = \Yii::$app->db;
		$lastTouch = $connection->createCommand('SELECT puid,last_activity_time FROM user_last_activity_time where puid in ('.implode(',',$puidArr).')')->queryAll();
		
		$lastTouch = \common\helpers\Helper_Array::toHashmap($lastTouch, 'puid','last_activity_time');
		return $lastTouch;
	}

}