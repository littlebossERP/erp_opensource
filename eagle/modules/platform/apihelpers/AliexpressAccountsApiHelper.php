<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasAliexpressUser;
use eagle\models\SaasAliexpressAutosync;
use eagle\models\SaasAliexpressAutosyncV2;
/**
 +------------------------------------------------------------------------------
 * 速卖通平台管理模块业务接口类
 +------------------------------------------------------------------------------
 * @category	platform
 * @author		dzt <zhitian.deng@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

class AliexpressAccountsApiHelper {
	
	/**
	 * 返回本用户速卖通账号数
	 * @author dzt
	 * @date 2014-3-31
	 */
	static function countBindingAccounts(){
		// 获取速卖通 user list
    	$userInfo = \Yii::$app->user->identity;
    	if ($userInfo['puid']==0){
    		$uid = $userInfo['uid'];
    	}else {
    		$uid = $userInfo['puid'];
    	}
    	
		return SaasAliexpressUser::find()->where('uid ='.$uid)->count();
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取指定uid 的aliexpress 账号 当uid为空的时候则为返回的是全部账号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param uid			uid (int)
	 +----------------------------------------------------------
	 * @return				aliexpress user 表的详细数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/08				初始化
	 +----------------------------------------------------------
	 **/
	static public function getAllAccounts($uid = 0){
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		
		return SaasAliexpressUser::find()->where(['uid'=>$uid ])->asArray()->all();
	}//end of getAllAccounts
	
	/**
	 +----------------------------------------------------------
	 * 获取指定uid 的aliexpress 账号 当uid为空的时候则为返回的是全部账号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param uid			uid (int)
	 +----------------------------------------------------------
	 * @return				aliexpress user 表的详细数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
	 +----------------------------------------------------------
	 **/
	static public function listActiveAccounts($uid = 0){
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		 
		return SaasAliexpressUser::find()->where(['uid'=>$uid , 'is_active'=>1])->asArray()->all();
	}//end of listAccounts
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	重置 订单同步的错误次数
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform					平台
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function resetSyncOrderInfo($account_key,$uid){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		
		$model = SaasAliexpressAutosyncV2::find()->where(['uid'=>$uid , 'sellerloginid'=>$account_key , 'type'=>'time'])->one();
		$model->times = 0;
		if ($model->save()){
			return ['success' =>true , 'message'=>''];
		}else{
			return ['success'=>false , 'message'=>json_decode($model->errors)];
		}
	}//end of resetSyncOrderInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * aliexpress 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=>同步表的最新数据
	 * 					'message'=>执行详细结果
	 * 				    'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
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
	
		//where  type 为time 定时抓取新单
		$result = SaasAliexpressAutosyncV2::find()->where(['uid'=>$uid , 'sellerloginid'=>$account_key , 'type'=>'time'])->orderBy(' last_time desc ')->asArray()->one();
		if(empty($result)){
			$result = SaasAliexpressAutosync::find()->where(['uid'=>$uid , 'sellerloginid'=>$account_key , 'type'=>'time'])->orderBy(' last_time desc ')->asArray()->one();
		}
		/*
			$tmpCommand = SaasAliexpressAutosync::find()->where(['uid'=>$uid , 'sellerloginid'=>$account_key , 'type'=>'time'])->orderBy(' last_time desc ')->createCommand();
		echo "<br>".$tmpCommand->getRawSql();
		*/
		if (!empty($result)){
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
		}else{
				
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
	
	}//end of getLastOrderSyncDetail
	
	/**
	 * 获取用户绑定的CD账号的异常情况，如token过期之类
	 * @param int $uid
	 */
	public static function getUserAccountProblems($uid){
		if(empty($uid)){
			$uid=\Yii::$app->user->id;
		}
			
	
		$accounts = SaasAliexpressUser::find()->where(['uid'=>$uid])->asArray()->all();
		if(empty($accounts))
			return [];
	
		
		$accountUnActive = [];//未开启同步的账号
		$tokenExpired = [];//授权过期的账号
		$order_retrieve_errors = [];//获取订单失败
		$initial_order_failed = [];//首次绑定时，获取订单失败
		foreach ($accounts as $account){
			if(empty($account['version'])){
				$tokenExpired[] = $account;
				continue;
			}
			if(empty($account['is_active'])){
				$accountUnActive[] = $account;
				continue;
			}
			if( $account['refresh_token_timeout'] < time() ){
				$tokenExpired[] = $account;
				continue;
			}
			$autoSyncList = SaasAliexpressAutosyncV2::find()->where(['uid'=>$uid, 'sellerloginid'=>$account['sellerloginid'], 'type'=>['time','day120']])->asArray()->all();
			foreach($autoSyncList as $row ){
				if(empty($autoSync['last_time']) && $row['type']=='day120'){
					$initial_order_failed[] = $account;
					continue;
				}
				//同步状态  0-等待同步 1-同步中 2-商品同步成功 3-同步失败
				if(  $row['type']=='time' && $row['status'] ==3){
					$order_retrieve_errors[] = $account;
					continue;
				}
			}
			
			
		}
		$problems=[
		'unActive'=>$accountUnActive,
		'token_expired'=>$tokenExpired,
		'initial_failed'=>$initial_order_failed,
		'order_retrieve_failed'=>$order_retrieve_errors,
		];
		return $problems;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	重置拉取订单失败的记录，可重新拉取
	 +---------------------------------------------------------------------------------------------
	 * @param $puids	array	平台
	 * @param $time		int		指定开始时间
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 ['success' => true, 'msg' => '']
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/02		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function resetSyncOrderErr($puids, $time){
	    //拉取订单job
	    $connection_db = \Yii::$app->db;
	    $sql = "update `saas_aliexpress_autosync_v2` set end_time=".$time." where type='time' and is_active=1";
	    if(!empty($puids)){
	    	$sql .= ' and uid in ('.implode($puids, ',').')';
	    }
	    $count = $connection_db->CreateCommand($sql)->execute();
	    //更新订单job
		$connection = \Yii::$app->db_queue;
		$sql = "update `queue_aliexpress_getorder_v2` set times=0 where create_time>".$time;
		if(!empty($puids)){
			$sql .= ' and uid in ('.implode($puids, ',').')';
		}
		$count = $connection->CreateCommand($sql)->execute();
		
		return ['success' => true, 'msg' => ''];
	}
}
?>