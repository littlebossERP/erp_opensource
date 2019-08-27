<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\Saas1688Autosync;
use eagle\models\Saas1688User;
/**
 +------------------------------------------------------------------------------
 * 1688平台管理模块业务接口类
 +------------------------------------------------------------------------------
 * @category	platform
 * @author		dzt <zhitian.deng@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

class Al1688AccountsApiHelper {
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 1688 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/13		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getLastOrderSyncDetail($account_key , $uid=0){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
	
		$result = Saas1688Autosync::find()->where(['uid'=>$uid , 'aliId'=>$account_key , 'type'=>'time'])->orderBy(' last_time desc ')->asArray()->one();

		if (!empty($result)){
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
		}else{
	
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
	
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 1688 账号信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/16		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function get1688UserInfo($uid = 0){
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid'] == 0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		$users = array();
		$user_queue = Saas1688User::find()->where(['uid' => $uid, 'is_active' => '1'])->asArray()->all();
		foreach($user_queue as $user){
			$users[$user['aliId']] = [
				'name' => $user['store_name'],
			];
		}
	
		return $users;
	
	}
	
	
	
}
?>