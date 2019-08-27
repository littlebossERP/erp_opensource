<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasAmazonUser;
use eagle\models\SaasAmazonAutosyncV2;

class AmazonAccountsApiHelper {

/**
 * [listActiveAccounts 找到小老板账号下的，amazon启用了同步的账号（相当于活动账号）]
 * @Author   willage
 * @DateTime 2016-06-29T14:35:09+0800
 * @param    integer                  $uid [小老板ID，默认值 = 0]
 * @return   array                        [SaasAmazonUser表 'is_active' 的详细数据]
 */
 //////////////////////////////////////////////////////////////////////////////////
 // 根据UID  ----> from SaasAmazonUser  ----> 获取 'is_active'=>1 的 user array //
 ////////////////////////////////////////////////////////////////////////////////
	static public function listActiveAccounts($uid = 0){
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		return SaasAmazonUser::find()->where(['uid'=>$uid , 'is_active'=>[0,1]])->asArray()->all();
	}//end listActiveAccounts

/**
 * [getAllAccounts 根据小老板账号，提取其账号下的amazon用户表]
 * @Author   willage
 * @DateTime 2016-06-28T14:52:45+0800
 * @param    integer                  $uid [小老板ID，默认值 = 0]
 * @return   array                        [SaasAmazonUser user 表的详细数据]
 */
 ////////////////////////////////////////////////////////////////////
 // 根据UID  ----> from SaasAmazonUser  ----> 获取 the user array //
 //////////////////////////////////////////////////////////////////
	static public function getAllAccounts($uid = 0){
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		return SaasAmazonUser::find()->where(['uid'=>$uid])->asArray()->all();
	}//end getAllAccounts

/**
 * [getLastOrderSyncDetail 根据$amazon_uid，获取最新订单同步信息]
 * @Author   willage
 * @DateTime 2016-06-29T15:03:20+0800
 * @param    integer                   $amazon_uid [amazon的user id(一个小老板账号可以有多个)]
 * @return   array                                [array (  'result'=>同步表的最新数据
 * 					                                        'message'=>执行详细结果
 * 				                                          	'success'=> true 成功 false 失败	)]
 */
////////////////////////////////////////////////////////////////////////////////////////////////////
//根据amazon的$amazon_uid(从SaasAmazonuser列表获取) ----> from SaasAmazonsync ----> 获取订单信息 //
//////////////////////////////////////////////////////////////////////////////////////////////////
	static public function getLastOrderSyncDetail($amazon_uid){
		//where  amazon_user_id,amazon账号会有多个站点(marketplace_id)记录
		$result = SaasAmazonAutosyncV2::find()->where(['eagle_platform_user_id'=>$amazon_uid ])->orderBy(' last_finish_time desc ')->asArray()->all();

		if (!empty($result)){
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
		}else{
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}

	}//end getLastOrderSyncDetail

	
	/**
	 * 根据用户uid 和 merchant_id获得对应店铺名
	 * @param 	int 	$uid
	 * @param 	string 	$merchant_id
	 * @return 	string
	 * @author	lzhl	2016/07/26		初始化
	 */
	public static function getStoreNameNameByMerchantId($uid,$merchant_id){
		$account = SaasAmazonUser::find()->where(['uid'=>$uid,'merchant_id'=>$merchant_id])->asArray()->one();
		if(!empty($account))
			return $account['store_name'];
		else 
			return '';
	}






}













?>