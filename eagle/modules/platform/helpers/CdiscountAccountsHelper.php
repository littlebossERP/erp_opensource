<?php
namespace eagle\modules\platform\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\order\helpers\CdiscountOrderHelper;
use eagle\modules\listing\helpers\CdiscountProxyConnectHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
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
class CdiscountAccountsHelper
{
    //用户修改Cdiscount账号--api和email    
    public static function updateCdiscountAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId == 0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Cdiscount的绑定!"));
    	}
		
    	foreach ($params as $key=>&$val){
    		$val = trim($val);
    	}
    	
    	$CdiscountData = SaasCdiscountUser::findOne($params["cdiscount_id"]);   	
    	if ($CdiscountData==null)  return array(false,TranslateHelper::t("该Cdiscount账户不存在"));
		
    	#检查Cdiscount信息的合法性(name,username是否被占用)
    	if(empty($params["auth_type"])){//auth_type=0时，为api账号验证
			$params['password']='';
			$params['username'] = $params['platform_account'];
			unset($params['platform_account']);
			if (empty($params["api_username"]) or empty($params["api_password"]) or empty($params['username'])){
				return array(false,TranslateHelper::t("api账号、密码、cd平台账号  都不能为空"));
			}
			//api_username为平台唯一
			$filteData=SaasCdiscountUser::find()->where(array('api_username' => $params['api_username']))->andwhere(['not',['site_id'=>$params['cdiscount_id']]])->one();
			if ($filteData!==null){
				return array(false,TranslateHelper::t("api账号名 已存在（不区分大小写），不能重复使用!"));
			}
		}else{//auth_type=0时，为CD账号验证
			$params['api_username']='';
			$params['api_password']='';
			unset($params['platform_account']);
			if (empty($params["username"]) or empty($params["password"])){
				return array(false,TranslateHelper::t("Cdiscount平台账号、密码  都不能为空"));
			}
			//username为平台唯一
			$filteData=SaasCdiscountUser::find()->where(array('username' => $params['username']))->andwhere(['not',['site_id'=>$params['cdiscount_id']]])->one();
			if ($filteData!==null){
				return array(false,TranslateHelper::t("Cdiscount平台账号 已存在（不区分大小写），不能重复使用!"));
			}
		}	
    	if (empty($params["store_name"])){
    		return array(false,TranslateHelper::t("自定义店铺名  不能为空"));
    	}
		//store_name为用户唯一
		$filteData = SaasCdiscountUser::find()->where(['store_name' => $params['store_name'],'uid'=>$saasId])->andwhere(['not',['site_id'=>$params['cdiscount_id']]])->one();
		if ($filteData!==null){
			return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
		}
		
    	//保存Cdiscount api的变化信息

		$CdiscountData->store_name = $params["store_name"];
		$CdiscountData->username = $params["username"];
		$CdiscountData->password = $params["password"];
		$CdiscountData->api_username = $params['api_username'];
		$CdiscountData->api_password = $params['api_password'];
		$CdiscountData->auth_type = empty($params['auth_type'])?0:$params['auth_type'];
		//$CdiscountData->token = '';
		
		if(!empty($params['auth_type']))
			$tokenInfo = self::getCdiscountToken($params["username"],$params["password"]);
		else 
			$tokenInfo = self::getCdiscountToken($params["api_username"],$params["api_password"]);
		
		if($tokenInfo['success']){
			$CdiscountData->token = $tokenInfo['token'];
			if(!empty($CdiscountData->addi_info)){
				$addi_info = json_decode($CdiscountData->addi_info,true);
				if(!empty($addi_info)){
					$addi_info['token_expired'] = false;
					$addi_info['token_fetch_times'] = 0;
					$CdiscountData->addi_info = json_encode($addi_info);
				}
			}
		}else{
    		if($tokenInfo['message']=='302')
    			$tokenInfo['message']='1.账号或密码错误,<br>2.cdiscount官方接口出现问题,<br>3.获取token操作过于频繁';
    		else {
    		    \Yii::info(__FUNCTION__.',getCdiscountToken:'.print_r($tokenInfo, true),'file');
    			$tokenInfo['message']='发生了未知错误';
			}
    			
    		
    		$CdiscountData->update_time = GetControlData::getNowDateTime_str();
    		if(!empty($CdiscountData->addi_info)){
    			$addi_info = json_decode($CdiscountData->addi_info,true);
    			if(empty($addi_info))
    				$addi_info = [];
    		}else 
    			$addi_info = [];
    		$addi_info['token_expired'] = true;
    		$addi_info['token_fetch_times'] = 1;
    		$CdiscountData->addi_info = json_encode($addi_info);
    		
    		$CdiscountData->save(false);
    		
    		return array(false,TranslateHelper::t("获取token失败!可能原因:<br>").$tokenInfo['message']);
    	}
		
		$CdiscountData->is_active = $params["is_active"];
		$CdiscountData->update_time = GetControlData::getNowDateTime_str();
		
		
		if ($CdiscountData->save()){
			return array(true,"");
		}else{
			$message = '';
            foreach ($CdiscountData->errors as $k => $anError){
				$message .= "Update Cdiscount user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    //用户绑定Cdiscount账号
    public static function createCdiscountAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,"请退出小老板并重新登录，再进行Cdiscount的绑定!");
    	}
    	$info=array();
    	 
    	
    	foreach ($params as $key=>&$val){
    		$val = trim($val);
    	}
    	
		#检查Cdiscount信息的合法性(store_name,username是否被占用)
		if(empty($params["auth_type"])){//auth_type=0时，为api账号验证
			$params['password']='';
			$params['username'] = $params['platform_account'];
			unset($params['platform_account']);
			if (empty($params["api_username"]) or empty($params["api_password"]) or empty($params['username'])){
				return array(false,TranslateHelper::t("api账号、密码、cd平台账号  都不能为空"));
			}
			$filteData = SaasCdiscountUser::find()->where(['api_username' => $params['api_username']])->orWhere(['username'=>$params['username']])->one();
			if ($filteData!==null){
				return array(false, TranslateHelper::t("绑定店铺的api登录名 已存在（不区分大小写），不能重复使用!"));
			}
		}else{//auth_type=1时，为CD账号验证
			$params['api_username']='';
			$params['api_password']='';
			unset($params['platform_account']);
			if (empty($params["username"]) or empty($params["password"])){
				return array(false,TranslateHelper::t("Cdiscount平台账号、密码  都不能为空"));
			}
			$filteData = SaasCdiscountUser::find()->where(array('username' => $params['username']))->one();
			if ($filteData!==null){
				return array(false, TranslateHelper::t("绑定店铺的登录名 已存在（不区分大小写），不能重复使用!"));
			}
		}
			
    	if (empty($params["store_name"])){
    		return array(false,TranslateHelper::t("自定义店铺名  不能为空"));
    	}
    	//store_name为用户唯一
    	$filteData = SaasCdiscountUser::find()->where(['store_name' => $params['store_name'],'uid'=>$saasId])->one();
    	if ($filteData!==null){
    		return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
    	}
    	
    	$info['token'] = '';
    	if(!empty($params["auth_type"]))
    		$tokenInfo = self::getCdiscountToken($params["username"],$params["password"]);
    	else 
    		$tokenInfo = self::getCdiscountToken($params["api_username"],$params["api_password"]);
    	if($tokenInfo['success']){
    		$info['token'] = $tokenInfo['token'];
    	}else{
    	    \Yii::info("getCdiscountToken result:".print_r($tokenInfo, true).PHP_EOL."params:".json_encode($params), "file");
    		if($tokenInfo['message']=='302')
    			$tokenInfo['message']='1.账号或密码错误,<br>2.cdiscount官方接口出现问题,<br>3.获取token操作过于频繁';
    		return array(false,TranslateHelper::t("获取token失败!可能原因:<br>").$tokenInfo['message']);
    	}
    	
    	#4. 保存Cdiscount信息到db
    	$info['store_name'] = $params['store_name'];
    	$info['username'] = $params['username'];
    	$info['password'] = $params['password'];
    	$info['api_username'] = $params['api_username'];
    	$info['api_password'] = $params['api_password'];
    	$info['auth_type'] = empty($params["auth_type"])?0:$params["auth_type"];
    	$info['create_time'] = GetControlData::getNowDateTime_str();    	
    	$info['is_active']=$params['is_active'];    	
    	
    	$ret=self::insertCdiscountUserInfo($info,$saasId);
    	if ($ret !==true ){
    		$cdAccount = SaasCdiscountUser::find()->where(['uid'=>$saasId,'username'=>$info['username']])->one();
    		MessageApiHelper::setSaasMsgAutosync($saasId, $cdAccount->site_id, $cdAccount->username, 'cdiscount');
    		return array(false,$ret);
    	}else{
    		$cdAccount = SaasCdiscountUser::find()->where(['uid'=>$saasId,'username'=>$info['username']])->one();
    		MessageApiHelper::setSaasMsgAutosync($saasId, $cdAccount->site_id, $cdAccount->username, 'cdiscount');
    		
    		return array(true,TranslateHelper::t('绑定成功。'));
    	}
    }
	
    //$info---'store_name','merchant_id','marketplace_id','access_key_id','secret_access_key','is_active'
    public static function insertCdiscountUserInfo($info,$saasId){
    	//1. 保存Cdiscount 账号级别（marchant_id）的信息
    	$now = GetControlData::getNowDateTime_str();
    	//time();
    	
    	$user = new SaasCdiscountUser();
    	$user->token = $info['token'];
    	$user->username = $info['username'];
    	$user->password = $info['password'];
    	$user->store_name = $info['store_name'];
    	
    	$user->api_username = $info['api_username'];
    	$user->api_password = $info['api_password'];
    	$user->auth_type = empty($info["auth_type"])?0:$info["auth_type"];
    	
    	$user->create_time = $info['create_time']; 	 
    	$user->update_time = $now;
    	$user->is_active = $info['is_active'];
    	$user->uid = $saasId;
    	
    	if (!$user->save()) {
			$message = '';
            foreach ($user->errors as $k => $anError){
				$message .= "Insert Cdiscount user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
			return $message;
    	}
    	PlatformAccountApi::callbackAfterRegisterAccount('cdiscount', $saasId, $info);
    	return true;
    }    
    
    
	//用户删除Cdiscount账号
    public static function deleteCdiscountAccount($id) 
    {
		$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Cdiscount的绑定!"));
    	}
		$model = SaasCdiscountUser::findOne($id);
		$accountID = $model->username;
        if ($model->delete()){
        	//消除相关redis数据
        	//PlatformAccountApi::delOnePlatformAccountSyncControlData('cdiscount', $accountID);
        	
        	try{
        		//重置账号绑定情况到redis
        		PlatformAccountApi::callbackAfterDeleteAccount('cdiscount',$saasId, ['selleruserid'=>$accountID]);
        	}catch (\Exception $e){
        		$message = "Delete Cdiscount account successed but some callback error:".print_r($e->getMessage());
        		return array(false,$message);
        	}
        	
        	return true;
        }else{
            $message = '';
            foreach ($model->errors as $k => $anError){
				$message .= "Delete Cdiscount user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    /**
     * get cdiscount store token when it's create or update info
     * @param	string	$username,$password
     * return	array	array(success=>true/false,'message'=>$message,'token'=>token)
     */
    private static function getCdiscountToken($username,$password){
    	$result['success']=false;
    	$result['message']='';
    	if(!empty($username) && !empty($password)){
    		$config = array();
    		$config['username']=$username;
    		$config['password']=$password;
    		$get_param['config'] = json_encode($config);
			
    		$reqInfo=CdiscountProxyConnectHelper::call_Cdiscount_api("getTokenID",$get_param,$post_params=array() );
    		SysLogHelper::SysLog_Create('Platform',__CLASS__, __FUNCTION__,'info',json_encode($reqInfo));//test liang
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
