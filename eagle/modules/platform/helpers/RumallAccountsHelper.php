<?php
namespace eagle\modules\platform\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasRumallUser;
use eagle\modules\util\helpers\GetControlData;
//use eagle\modules\order\helpers\RumallOrderHelper;
use eagle\modules\listing\helpers\RumallProxyConnectHelper;
//use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\DynamicSqlHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\helpers\RumallOrderHelper;

/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lzhl
+----------------------------------------------------------------------
| Create Date: 2014-01-26
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 物流方式模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/method
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class RumallAccountsHelper
{
    //用户修改Rumall账号--api和email    
    public static function updateRumallAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId == 0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Rumall的绑定!"));
    	}

    	$RumallData = SaasRumallUser::findOne($params["rumall_id"]);   	
    	if ($RumallData==null)  return array(false,TranslateHelper::t("该Rumall账户不存在"));
		
		//store_name为用户唯一
		$filteData = SaasRumallUser::find()->where(['store_name' => $params['store_name'],'uid'=>$saasId,'company_code' => $params['company_code']])->andwhere(['not',['site_id'=>$params['rumall_id']]])->one();
		if ($filteData!==null){
			return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
		}
		
    	//保存Rumall api的变化信息

// 		$RumallData->store_name = $params["store_name"];
		$RumallData->token = $params['token'];
		
		
		$RumallData->is_active = $params["is_active"];
		$RumallData->update_time = GetControlData::getNowDateTime_str();
		
		
		if ($RumallData->save()){
			return array(true,"");
		}else{
			$message = '';
            foreach ($RumallData->errors as $k => $anError){
				$message .= "Update Rumall user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    //用户绑定Rumall账号
    public static function createRumallAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,"请退出小老板并重新登录，再进行Rumall的绑定!");
    	}
    	$info=array();
    	
		#检查Rumall信息的合法性(store_name,username是否被占用)
		if (empty($params["store_name"]) or empty($params["token"]) or empty($params["company_code"])){
			return array(false,TranslateHelper::t("店铺名、Rumall 货主编码、token 都不能为空"));
		}
		$filteData = SaasRumallUser::find()->where(['store_name' => $params['store_name']])->orWhere(['company_code'=>$params["company_code"]])->one();
		if ($filteData!==null){
			return array(false, TranslateHelper::t("绑定店铺名 或Rumall 货主编码已存在（不区分大小写），不能重复使用!"));
		}
		
		
		$orderObj = new RumallOrderHelper();
		$returnResult = $orderObj->getNewOrder($params["company_code"], $params["token"], 1);
		if(!$returnResult['success']){
		    return array(false,$returnResult['message']);
		}
		    
    	
    	$info['token'] = '';
    	$info['token'] = $params["token"];
    	
    	#4. 保存Rumall信息到db
    	$info['store_name'] = $params['store_name'];
    	$info['company_code'] = $params["company_code"];
    	$info['create_time'] = GetControlData::getNowDateTime_str();    	
    	$info['is_active']=$params['is_active'];    	
    	
    	$sql_run = DynamicSqlHelper::run("rumall");//注册成功后新建rumall相关的user表
    	if(!$sql_run){
    	    SysLogHelper::SysLog_Create('Platform',__CLASS__, __FUNCTION__,'error','DynamicSqlHelper::run("Rumall") return false!');//test liang
    	    return array(false,TranslateHelper::t('绑定失败：数据库创建Rumall相关数据失败！'));
    	}
    	
    	$ret=self::insertRumallUserInfo($info,$saasId);
    	if ($ret !==true ){
//     		$cdAccount = SaasRumallUser::find()->where(['uid'=>$saasId,'store_name'=>$info['store_name']])->one();
//     		MessageApiHelper::setSaasMsgAutosync($saasId, $cdAccount->site_id, $cdAccount->store_name, 'rumall');//站内信，暂时不需要爱
    		return array(false,$ret);
    	}else{
//     		$cdAccount = SaasRumallUser::find()->where(['uid'=>$saasId,'store_name'=>$info['store_name']])->one();
//     		MessageApiHelper::setSaasMsgAutosync($saasId, $cdAccount->site_id, $cdAccount->store_name, 'rumall');
    		return array(true,TranslateHelper::t('绑定成功。'));
    	}
    }
	
    //$info---'store_name','merchant_id','marketplace_id','access_key_id','secret_access_key','is_active'
    public static function insertRumallUserInfo($info,$saasId){
    	//1. 保存Rumall 账号级别（marchant_id）的信息
    	$now = GetControlData::getNowDateTime_str();
    	//time();
    	
    	$user = new SaasRumallUser();
    	$user->token = $info['token'];
    	$user->store_name = $info['store_name'];
    	$user->company_code = $info["company_code"];
    	
    	
    	$user->create_time = $info['create_time']; 	 
    	$user->update_time = $now;
    	$user->is_active = $info['is_active'];
    	$user->uid = $saasId;
    	
    	if (!$user->save()) {
			$message = '';
            foreach ($user->errors as $k => $anError){
				$message .= "Insert Rumall user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
			return $message;
    	}
    	PlatformAccountApi::callbackAfterRegisterAccount('rumall',$saasId);
    	return true;
    }    
    
    
	//用户删除Rumall账号
    public static function deleteRumallAccount($id) 
    {
		$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Rumall的绑定!"));
    	}
		$model = SaasRumallUser::findOne($id);
		$sellerid = $model->store_name;
// 		$accountID = $model->username;
        if ($model->delete()){
        	//消除相关redis数据
        	//PlatformAccountApi::delOnePlatformAccountSyncControlData('rumall', $accountID);
        	
        	//重置账号绑定情况到redis
        	PlatformAccountApi::callbackAfterDeleteAccount('rumall',$saasId,['selleruserid'=>$sellerid]);
            return true;
        }else{
            $message = '';
            foreach ($model->errors as $k => $anError){
				$message .= "Delete Rumall user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
}
