<?php
namespace eagle\modules\platform\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasBonanzaUser;
use eagle\modules\util\helpers\GetControlData;
//use eagle\modules\order\helpers\BonanzaOrderHelper;
use eagle\modules\listing\helpers\BonanzaProxyConnectHelper;
//use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\DynamicSqlHelper;
use eagle\modules\util\helpers\SysLogHelper;

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
class BonanzaAccountsHelper
{
    //用户修改Bonanza账号--api和email    
    public static function updateBonanzaAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId == 0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Bonanza的绑定!"));
    	}

    	$BonanzaData = SaasBonanzaUser::findOne($params["bonanza_id"]);   	
    	if ($BonanzaData==null)  return array(false,TranslateHelper::t("该Bonanza账户不存在"));
		
    	#检查Bonanza信息的合法性(name,username是否被占用)
// 		if (empty($params["store_name"]) or empty($params["token"])){
// 			return array(false,TranslateHelper::t("店铺名、token 都不能为空"));
// 		}
// 		//api_username为平台唯一
// 		$filteData=SaasBonanzaUser::find()->where(array('store_name' => $params['store_name']))->andwhere(['not',['site_id'=>$params['bonanza_id']]])->one();
// 		if ($filteData!==null){
// 			return array(false,TranslateHelper::t("店铺名 已存在（不区分大小写），不能重复使用!"));
// 		}
//     	if (empty($params["store_name"])){
//     		return array(false,TranslateHelper::t("自定义店铺名  不能为空"));
//     	}
		//store_name为用户唯一
		$filteData = SaasBonanzaUser::find()->where(['store_name' => $params['store_name'],'uid'=>$saasId])->andwhere(['not',['site_id'=>$params['bonanza_id']]])->one();
		if ($filteData!==null){
			return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
		}
		
    	//保存Bonanza api的变化信息

		$BonanzaData->store_name = $params["store_name"];
// 		$BonanzaData->username = $params["username"];
// 		$BonanzaData->password = $params["password"];
// 		$BonanzaData->api_username = $params['api_username'];
// 		$BonanzaData->api_password = $params['api_password'];
// 		$BonanzaData->auth_type = $params['auth_type'];
		$BonanzaData->token = $params['token'];
		$BonanzaData->is_auto_accept = $params['is_auto_accept'];
		
// 		$tokenInfo = self::getBonanzaToken($params["api_username"],$params["api_password"]);
		
// 		if($tokenInfo['success']){
// 			$BonanzaData->token = $tokenInfo['token'];
// 		}else{
//     		if($tokenInfo['message']=='302')
//     			$tokenInfo['message']='1.账号或密码错误,<br>2.bonanza官方接口出现问题,<br>3.获取token操作过于频繁';
//     		return array(false,TranslateHelper::t("获取token失败!可能原因:<br>").$tokenInfo['message']);
//     	}
		
		$BonanzaData->is_active = $params["is_active"];
		$BonanzaData->update_time = GetControlData::getNowDateTime_str();
		
		
		if ($BonanzaData->save()){
			return array(true,"");
		}else{
			$message = '';
            foreach ($BonanzaData->errors as $k => $anError){
				$message .= "Update Bonanza user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    //用户绑定Bonanza账号
    public static function createBonanzaAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,"请退出小老板并重新登录，再进行Bonanza的绑定!");
    	}
    	$info=array();
    	
		#检查Bonanza信息的合法性(store_name,username是否被占用)
		if (empty($params["store_name"]) or empty($params["token"])){
			return array(false,TranslateHelper::t("店铺名、token 都不能为空"));
		}
		$filteData = SaasBonanzaUser::find()->where(array('store_name' => $params['store_name']))->one();
		if ($filteData!==null){
			return array(false, TranslateHelper::t("绑定店铺名 已存在（不区分大小写），不能重复使用!"));
		}
			
//     	if (empty($params["store_name"])){
//     		return array(false,TranslateHelper::t("自定义店铺名  不能为空"));
//     	}
    	//store_name为用户唯一
//     	$filteData = SaasBonanzaUser::find()->where(['store_name' => $params['store_name'],'uid'=>$saasId])->one();
//     	if ($filteData!==null){
//     		return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
//     	}
    	
    	$info['token'] = '';
    	$info['token'] = $params["token"];
// 		$tokenInfo = self::getBonanzaToken($params["api_username"],$params["api_password"]);
//     	if($tokenInfo['success']){
//     		$info['token'] = $tokenInfo['token'];
//     	}else{
//     		if($tokenInfo['message']=='302')
//     			$tokenInfo['message']='1.账号或密码错误,<br>2.bonanza官方接口出现问题,<br>3.获取token操作过于频繁';
//     		return array(false,TranslateHelper::t("获取token失败!可能原因:<br>").$tokenInfo['message']);
//     	}
    	
    	#4. 保存Bonanza信息到db
    	$info['store_name'] = $params['store_name'];
    	$info['is_auto_accept'] = $params['is_auto_accept'];//是否自动接受订单
//     	$info['username'] = $params['username'];
//     	$info['password'] = $params['password'];
//     	$info['api_username'] = $params['api_username'];
//     	$info['api_password'] = $params['api_password'];
//     	$info['auth_type'] = $params["auth_type"];
    	$info['create_time'] = GetControlData::getNowDateTime_str();    	
    	$info['is_active']=$params['is_active'];    	
    	
    	$sql_run = DynamicSqlHelper::run("bonanza");//注册成功后新建bonanza相关的user表
    	if(!$sql_run){
    	    SysLogHelper::SysLog_Create('Platform',__CLASS__, __FUNCTION__,'error','DynamicSqlHelper::run("Bonanza") return false!');//test liang
    	    return array(false,TranslateHelper::t('绑定失败：数据库创建Bonanza相关数据失败！'));
    	}
    	
    	$ret=self::insertBonanzaUserInfo($info,$saasId);
    	if ($ret !==true ){
//     		$cdAccount = SaasBonanzaUser::find()->where(['uid'=>$saasId,'store_name'=>$info['store_name']])->one();
//     		MessageApiHelper::setSaasMsgAutosync($saasId, $cdAccount->site_id, $cdAccount->store_name, 'bonanza');//站内信，暂时不需要爱
    		return array(false,$ret);
    	}else{
//     		$cdAccount = SaasBonanzaUser::find()->where(['uid'=>$saasId,'store_name'=>$info['store_name']])->one();
//     		MessageApiHelper::setSaasMsgAutosync($saasId, $cdAccount->site_id, $cdAccount->store_name, 'bonanza');
    		return array(true,TranslateHelper::t('绑定成功。'));
    	}
    }
	
    //$info---'store_name','merchant_id','marketplace_id','access_key_id','secret_access_key','is_active'
    public static function insertBonanzaUserInfo($info,$saasId){
    	//1. 保存Bonanza 账号级别（marchant_id）的信息
    	$now = GetControlData::getNowDateTime_str();
    	//time();
    	
    	$user = new SaasBonanzaUser();
    	$user->token = $info['token'];
//     	$user->username = $info['username'];
//     	$user->password = $info['password'];
    	$user->store_name = $info['store_name'];
    	$user->is_auto_accept = $info['is_auto_accept'];
    	
//     	$user->api_username = $info['api_username'];
//     	$user->api_password = $info['api_password'];
//     	$user->auth_type = $info["auth_type"];
    	
    	$user->create_time = $info['create_time']; 	 
    	$user->update_time = $now;
    	$user->is_active = $info['is_active'];
    	$user->uid = $saasId;
    	
    	if (!$user->save()) {
			$message = '';
            foreach ($user->errors as $k => $anError){
				$message .= "Insert Bonanza user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
			return $message;
    	}
    	PlatformAccountApi::callbackAfterRegisterAccount('bonanza',$saasId);
    	return true;
    }    
    
    
	//用户删除Bonanza账号
    public static function deleteBonanzaAccount($id) 
    {
		$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Bonanza的绑定!"));
    	}
		$model = SaasBonanzaUser::findOne($id);
		$sellerid = $model->store_name;
// 		$accountID = $model->username;
        if ($model->delete()){
        	//消除相关redis数据
        	//PlatformAccountApi::delOnePlatformAccountSyncControlData('bonanza', $accountID);
        	
        	//重置账号绑定情况到redis
        	PlatformAccountApi::callbackAfterDeleteAccount('bonanza',$saasId,['selleruserid'=>$sellerid]);
            return true;
        }else{
            $message = '';
            foreach ($model->errors as $k => $anError){
				$message .= "Delete Bonanza user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    /**
     * get bonanza store token when it's create or update info
     * @param	string	$username,$password
     * return	array	array(success=>true/false,'message'=>$message,'token'=>token)
     */
    private static function getBonanzaToken($username,$password){
    	$result['success']=false;
    	$result['message']='';
    	if(!empty($username) && !empty($password)){
    		$config = array();
    		$config['username']=$username;
    		$config['password']=$password;
    		$get_param['config'] = json_encode($config);
			
    		$reqInfo = BonanzaProxyConnectHelper::call_Bonanza_api("getTokenID",$get_param,$post_params=array() );
    			
    		if($reqInfo['success']){
    			if($reqInfo['proxyResponse']['success']){
    				$result['success']=true;
    				$result['token'] = $reqInfo['proxyResponse']['tokenMessage'];
    			}else{
    				$result['message']=$reqInfo['proxyResponse']['message'];
    			}
    		}
    		else{
    			$result['message']=$reqInfo['message'];
    		}
    	}
    	return $result;
    }
}
