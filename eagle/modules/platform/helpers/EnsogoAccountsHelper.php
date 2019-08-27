<?php
namespace eagle\modules\platform\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasEnsogoUser;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\message\apihelpers\MessageApiHelper;
use Qiniu\json_decode;
use eagle\models\SaasEnsogoStore;
use common\api\ensogointerface\EnsogoProxyConnectHelper;
use eagle\modules\util\helpers\SMSHelper;
use eagle\modules\platform\apihelpers\EnsogoAccountsApiHelper;
use eagle\modules\listing\helpers\EnsogoHelper;
use eagle\modules\util\helpers\RedisHelper;

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
class EnsogoAccountsHelper
{
    
	public static $DefaultOpenSiteList = [
	'hk', 'th' , 'id' , 'ph' , 'sg' , 'my','us'
	];
    
    //用户修改Ensogo账号--api和email    
    public static function updateEnsogoAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId == 0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Ensogo的绑定!"));
    	}
    	if (empty($params["store_name"]) or empty($params["token"])){
			return array(false,TranslateHelper::t("store_name 和 token 都不能为空"));
		}
		
    	$EnsogoData = SaasEnsogoUser::findOne($params["Ensogo_id"]);    	
    	if ($EnsogoData==null)  return array(false,TranslateHelper::t("该Ensogo账户不存在"));
		
		#检查Ensogo信息的合法性(name,token是否被占用)
		$filteData = SaasEnsogoUser::find()->where(array('store_name' => $params['store_name']))->one();
		if ($filteData!==null){
			$id = $filteData->site_id;
			if ($id !== $params['Ensogo_id']){
				return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
			}
			
		}
		$filteData=SaasEnsogoUser::find()->where(array('token' => $params['token']))->one();
		if ($filteData!==null){
			$id = $filteData->site_id;
			if ($id !== $params['Ensogo_id']){
				return array(false,TranslateHelper::t("token 已存在（不区分大小写），不能重复使用!"));
			}
			
		}
		
    	//保存Ensogo api的变化信息

		$EnsogoData->store_name = $params["store_name"];
		$EnsogoData->token = $params["token"];
		$EnsogoData->is_active = $params["is_active"];
		$EnsogoData->update_time = GetControlData::getNowDateTime_str();
		if ($EnsogoData->save()){
			return array(true,"");
		}else{
			$message = '';
            foreach ($EnsogoData->errors as $k => $anError){
				$message .= "Update Ensogo user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    //用户绑定Ensogo账号
    public static function createEnsogoAccount($params){
    	// #1.检查Ensogo信息的合法性，连接测试是否ok
    	// list($ret,$message)=self::_checkEnsogoUserInfo($params);
    	// if ($ret===false) return array($ret,$message);
    	
    	// #2. Ensogo email的连接检查
    	// list($ret,$message)=self::_checkEnsogoEmailInfo($params);
    	// if ($ret===false) return array($ret,$message);
		
		#3.检查Ensogo信息的合法性(name,token是否被占用)
		#检查Ensogo信息的合法性(name,token是否被占用)
		$filteData = SaasEnsogoUser::find()->where(array('store_name' => $params['store_name']))->one();
		if ($filteData!==null){
			return ['success'=>false , 'message'=>TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!") , 'site_id'=>0];
			//return array(false, TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
		}
		
		//2015-10-20 v2 授权token 隨后才能获取
		if (!empty($params['token'])){
			$filteData = SaasEnsogoUser::find()->where(array('token' => $params['token']))->one();
			if ($filteData!==null){
				return ['success'=>false , 'message'=>TranslateHelper::t("token 已存在（不区分大小写），不能重复使用!") , 'site_id'=>0];
				//return array(false, TranslateHelper::t("token 已存在（不区分大小写），不能重复使用!"));
			}
		}
    	
    	#4. 保存Ensogo信息到db
    	$info=array();
    	$info['store_name'] = $params['store_name'];
    	if (!empty($params['token']))
    		$info['token'] = $params['token'];
    	else
    		$info['token'] = '';
    	
    	if (!empty($params['refresh_token']))
    		$info['refresh_token'] = $params['refresh_token'];
    	else
    		$info['refresh_token'] = '';
    	
    	if (!empty($params['register_by']))
    		$info['register_by'] = $params['register_by'];
    	
    	if (!empty($params['code']))
    		$info['code'] = $params['code'];
    	
    	if (!empty($params['seller_id']))
    		$info['seller_id'] = $params['seller_id'];
    	
    	$info['create_time'] = GetControlData::getNowDateTime_str();    	
    	$info['is_active']=$params['is_active'];    	
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return ['success'=>false , 'message'=>TranslateHelper::t("请退出小老板并重新登录，再进行Ensogo的绑定!") , 'site_id'=>0];
    		//return array(false,"请退出小老板并重新登录，再进行Ensogo的绑定!");
    	}
    	$ret=self::insertEnsogoUserInfo($info,$saasId);
    	if ($ret['success'] !==true ){
    		return ['success'=>false , 'message'=>$ret['message'], 'site_id'=>0];
    		//return array(false,$ret);
    	}else{
			EnsogoHelper::addEnsogoTagsQueue($saasId);
    		return ['success'=>true,'message'=>'' , 'site_id'=>$ret['site_id']];
    	}
    }
	
    //$info---'store_name','merchant_id','marketplace_id','access_key_id','secret_access_key','is_active'
    public static function insertEnsogoUserInfo($info,$saasId){
    	//1. 保存Ensogo 账号级别（marchant_id）的信息
    	$now = GetControlData::getNowDateTime_str();
    	//time();
    	
    	$user = new SaasEnsogoUser();
    	$user->token = $info['token'];
    	$user->store_name = $info['store_name'];
    	$user->create_time = $now;    	 
    	$user->update_time = $now;
    	$user->is_active = $info['is_active'];
    	$user->uid = $saasId;
    	$user->refresh_token = $info['refresh_token'];
    	if (!empty($info['seller_id']))
    		$user->seller_id = $info['seller_id'];
    	if (empty($info['open_site']))
    		$user->open_site = implode (',',self::$DefaultOpenSiteList);
    	else
    		$user->open_site = $info['open_site'];
    	
    	if (!empty($info['register_by'])){
    		$user->register_by = $info['register_by'];
    	}
    	if (!empty($info['code'])){
    		$user->code = $info['code'];
    	}
		
		if (empty($info['created_at'])){
			//过期时间 120分钟， 首次绑定提前半小时
    		$user->created_at = strtotime('+90 minute');
    	}
    	
    	if (!$user->save()) {
			$message = '';
            foreach ($user->errors as $k => $anError){
				$message .= "Insert Ensogo user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
			return ['success'=>false , 'message'=>$message , 'site_id'=>0];
			//return $message;
    	}
    	
    	//绑定账号时，将拉取站内信的app数据一并生成
    	/*暂时没有支持ensogo 
    	$rtn = MessageApiHelper::setSaasMsgAutosync($saasId, $user->site_id, $info['store_name'], 'Ensogo');
    	if('fail' == $rtn['code']){
    		return ['success'=>false , 'message'=>$rtn['message'] , 'site_id'=>0];
    		//return $rtn['message'];
    	}
    	*/
    	return ['success'=>true , 'message'=>'' , 'site_id'=>$user->site_id];
    	//return true;
    }    
    
    
	//用户删除Ensogo账号
    public static function deleteEnsogoAccount($id) 
    {
		$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Ensogo的绑定!"));
    	}
		$model = SaasEnsogoUser::findOne($id);
        if ($model->delete()){
            return true;
        }else{
            $message = '';
            foreach ($model->errors as $k => $anError){
				$message .= "Delete Ensogo user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 获取Ensogo授权信息
     +----------------------------------------------------------------------------------------------------------------------------
     * @access static
     +----------------------------------------------------------------------------------------------------------------------------
     * @param 	$client_id			string		客户端ID
     * 			$client_secret		string		客户端密钥
     * 			$code				string		授权code
     * 			$redirect_uri		string		回调地址
     +----------------------------------------------------------------------------------------------------------------------------
     * @return			array
     * 	boolean				success   	执行结果
     * 	string/array		message   	执行失败的提示信息
     * 	array				EnsogoReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		EnsogoProxyConnectHelper::getEnsogoToken($client_id , $client_secret , $code , $redirect_uri)
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2014/08/26				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    public static function getEnsogoToken( $code ){
    	//littleboss public app info start  
		// TODO proxy dev account @XXX@
    	$client_id = '@XXX@';
    	$client_secret = '@XXX@';
		$tempu = parse_url(\Yii::$app->request->hostInfo);
		$host = $tempu['host'];
    	$redirect_uri  = 'https://'.$host.'/platform/Ensogo-accounts-v2/get-Ensogo-authorization-code';
    	//littleboss public app info end
    	$get_params = [ 
    	'parms'=>json_encode(['client_id'=>$client_id,
    	'client_secret'=>$client_secret,
    	'code'=>$code,
    	'redirect_uri'=>$redirect_uri,
    	]),
    	];
    	return EnsogoProxyConnectHelper::call_Ensogo_api('GetAccessToken',$get_params);
    }//end of getEnsogoToken
    
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 更新 Ensogo授权
     +----------------------------------------------------------------------------------------------------------------------------
     * @access static
     +----------------------------------------------------------------------------------------------------------------------------
     * @param 	$client_id			string		客户端ID
     * 			$client_secret		string		客户端密钥
     * 			$resfresh_token		string		刷新授权
     +----------------------------------------------------------------------------------------------------------------------------
     * @return			array
     * 	boolean				success   	执行结果
     * 	string/array		message   	执行失败的提示信息
     * 	array				EnsogoReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		EnsogoProxyConnectHelper::refreshEnsogoToken($client_id , $client_secret , $resfresh_token)
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2014/08/26				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    public static function refreshEnsogoToken( $refresh_token){
    	
    	//littleboss public app info start 
		// TODO proxy dev account @XXX@ 		
    	//$client_id = '@XXX@ ';
    	//$client_secret = '@XXX@ ';
		// $tempu = parse_url(\Yii::$app->request->hostInfo);
		// $host = $tempu['host'];
    	//$redirect_uri  = 'https://'.$host.'/platform/Ensogo-accounts-v2/get-Ensogo-authorization-code';
    	//littleboss public app info end
    	$post_params = [ 
    	'refresh_token'=>$refresh_token ,
    	];
    	return EnsogoProxyConnectHelper::call_Ensogo_api('getAccessToken',$post_params);
    }//end of refreshEnsogoToken
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 更新 Ensogo授权
     +----------------------------------------------------------------------------------------------------------------------------
     * @access static
     +----------------------------------------------------------------------------------------------------------------------------
     * @param 	$model				model		user表的model
     * 			$result				array		refresh token 的结果 
     +----------------------------------------------------------------------------------------------------------------------------
     * @return			array
     * 	boolean				success   	执行结果
     * 	string/array		message   	执行失败的提示信息
     * 	array				EnsogoReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		EnsogoProxyConnectHelper::saveEnsogoToken($model , $result )
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/01/20				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    static public function saveEnsogoToken(&$model , &$result , $lb_auth=''){
    	if (!empty($result) && !empty($model)){
    		
				if ($result['success']){
					//proxy 层成功 取出proxyResponse 结果
	
					if(!empty($result['proxyResponse']) && $result['proxyResponse']['success']){
						//Ensogo 数据返回成功
						
						if (!empty($result['proxyResponse'])){
							//Ensogo api 调用 成功 
							if (is_string($result['proxyResponse'])){
								// json 字符
								$EnsogoReturn = json_decode($result['proxyResponse'],true);
							}else if (is_array($result['proxyResponse'])){
								//array
								$EnsogoReturn = $result['proxyResponse'];
							}else{
								//其他格式  todo
								$EnsogoReturn = [];
							}
						}
							
						if (!empty($EnsogoReturn['data'])){
							if (!empty( $_REQUEST['code'])){
								$model->code = $_REQUEST['code'];
							}
							if (!empty($EnsogoReturn['data']['access_token']))
								$model->token = $EnsogoReturn['data']['access_token'];
							if (!empty($EnsogoReturn['data']['refresh_token']))
								$model->refresh_token = $EnsogoReturn['data']['refresh_token'];
							if (!empty($EnsogoReturn['data']['expires_in']))
								$model->expires_in = $EnsogoReturn['data']['expires_in'];
							if (!empty($EnsogoReturn['data']['created_at']))
								$model->created_at = $EnsogoReturn['data']['created_at'];
							
							if (!empty($EnsogoReturn['data']['user_id']))
								$model->user_id = $EnsogoReturn['data']['user_id'];
							
							if (!empty($model->token)){
								//get lb_auth
								$lbAuthData = self::getEnsogoLBAUTH($model->token, $model->create_time);
								if ($lbAuthData['success'] ==true){
									$model->lb_auth = $lbAuthData['lb_auth'];
								}
									
							}
							
							if (! $model->save()) {
								
								if (is_array($model->errors)){
									$error_msg = json_encode($model->errors);
								}else if (is_string($model->errors)){
									$error_msg = $model->errors;
								}else{
									$error_msg = "未知原因导致保存失败";
								}
								return ['success'=>false, 'message'=>$error_msg,'access_token'=>''];
							}
						}
							
					}//end of if(!empty($result['proxyResponse']) && $result['proxyResponse']['success']) Ensogo api 返回结果处理
					else{
						//Ensogo 返回失败
						return ['success'=>false, 'message'=>'Ensogo api发生未知错误！','access_token'=>''];
					}
				}//end of if ($result['success']) proxy 返回结果
				else {
					//proxy 返回失败
					return ['success'=>false, 'message'=>'proxy 发生未知错误！','access_token'=>''];
				}//end of else
			return ['success'=>true, 'message'=>'','access_token'=>$model->token];
    	}//end of if (!empty($code) && !empty($client_id)) code 和 client id 不能为空
    	return ['success'=>false, 'message'=>'参数格式不正确！','access_token'=>''];
    }//end of saveEnsogoToken
    
    /**
     +---------------------------------------------------------------------------------------------
     * 设置Ensogo order 手动同步订单
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param $site_id		saas_Ensogo_user.site_id
     +---------------------------------------------------------------------------------------------
     * @return array ('message'=>执行详细结果
     * 				  'success'=> true 成功 false 失败	)
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2015/10/30				初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function setManualRetrieveOrder($site_id){
    	$model = SaasEnsogoUser::findOne(['site_id'=>$site_id]);
    	if (empty($model)){
    		return ['success'=>false,"message"=>'没有找到对应的账号！'];
    	}
    	//check the last manual sync time
    	$limitHours = "1"; // default one hours
    	$limitTime = date('Y-m-d H:i:s' , strtotime("-".$limitHours." hours"));
    	$now = date('Y-m-d H:i:s');
    	
    	// 请求频率过密就返回失败
    	if (!empty($model->last_order_manual_retrieve_time ) &&  $model->last_order_manual_retrieve_time > $limitTime ){
    		return ['success'=>false,"message"=>''.$model->last_order_manual_retrieve_time.'已经手工同步一次，请 '.$limitHours.'小时后重试 ！'];
    	}
    	
    	//开启手动同步订单的标志
    	$model->order_manual_retrieve = "Y";
    	$model->last_order_manual_retrieve_time = $now;
    	
    	//保存失败返回相关错误信息
    	if (! $model->save()){
    		return ['success'=>false,"message"=>json_encode($model->errors)];
    	}
    	return ['success'=>true,"message"=>''];
    }//end of setManualRetrieveOrder
    
    
    /**
     +----------------------------------------------------------
     * 注册 Ensogo 账号
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @invoking
     *	EnsogoAccountsApiHelper::checkAccountBindingOrNot('1');
     +----------------------------------------------------------
     * @param $contact_name			联系人
     * @param $phone				手机
     * @param $email				邮箱
     * @param $store_name			店铺名称
     * @param $country_code			中国大陆cn /tw 中国台湾
     +----------------------------------------------------------
     * @return
     *		 success        boolean true成功 false失败 注册  是否 成功
     *		 message		string 失败信息
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/01/16				初始化
     +----------------------------------------------------------
     **/
    static public function registerEnsogoAccount($contact_name , $phone , $email , $store_name , $country_code='cn'){
    	/*
    	//dummy data
    	$returnStr = '{"code":0,"message":"Seller created","data":{"seller_id":"643c9683-e682-4199-b298-921d85bf1f74","access_token":"e566783ab22b97a4d091ca88f4842128e8245bff35afffd50badd5addf804641","refresh_token":"67b6bc94b44416d45a0fbdaec604dd6cfc934b1290fd013dee16d212eccc67f0"}}';
    	return ['success'=>true , 'message'=>'' , 'ensogoreturn'=>json_decode($returnStr,true)];
    	*/
		//获取当前 用户的puid
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$params = [
    		'contact_name'=>$contact_name,
    		'phone'=>$phone,
    		'email'=>$email,
    		'store_name'=>$store_name,
			'puid'=>$puid,
			'country_code'=>$country_code,
    	];
    	
    	return EnsogoProxyConnectHelper::call_ENSOGO_api('registerAccount',$params);
    	
    	
    }//end of registerEnsogoAccount
    
    
    /**
     +----------------------------------------------------------
     * 新建 /更新  Ensogo 店铺信息
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @invoking
     *	EnsogoAccountsApiHelper::checkAccountBindingOrNot('1');
     +----------------------------------------------------------
     * @param $EnsogoStoreInfo
     * @param $contact_name			联系人
     * @param $phone				手机
     * @param $email				邮箱
     * @param $store_name			店铺名称
     +----------------------------------------------------------
     * @return
     *		 success        boolean true成功 false失败 注册  是否 成功
     *		 message		string 失败信息
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/01/16				初始化
     +----------------------------------------------------------
     **/
    static public function saveEnsogoStoreInfo($EnsogoStoreInfo){
    	//check create or update 
    	if (!empty($EnsogoStoreInfo['id'])){
    		$model = SaasEnsogoStore::findOne($EnsogoStoreInfo['id']);
    	}else{
    		$model = new SaasEnsogoStore();
    		$model->create_time = date('Y-m-d H:i:s');
    	}
    	
    	//set value
    	foreach($EnsogoStoreInfo as $key=>$value){
    		$model->$key = $value;
    	}
    	
    	if ($model->save()){
    		return ['success'=>true,'message'=>''];
    	}else{
    		return ['success'=>false,'message'=>'ERR MSG:'.(__FUNCTION__).":".json_encode($model->errors)];
    	}
    }//end of saveEnsogoStoreInfo
    
    /**
     +----------------------------------------------------------
     * 获取  Ensogo proxy lb_auth信息
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @invoking
     *	EnsogoAccountsHelper::getEnsogoLBAUTH('xxxxxxxx', '2016-01-20');
     +----------------------------------------------------------
     * @param $access_token			token
     * @param $create_time			用户创建时间
     +----------------------------------------------------------
     * @return
     *		 success        boolean true成功 false失败 注册  是否 成功
     *		 message		string 失败信息
     *		 code			lb_auth
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/01/16				初始化
     +----------------------------------------------------------
     **/
    static public function getEnsogoLBAUTH($access_token , $create_time){
    	//@todo 
    	return ['success'=>true, 'message'=>'' , 'lb_auth'=>'123'];
    }//end of getEnsogoLBAUTH
    
    /**
     +----------------------------------------------------------
     * 发送  Ensogo 短信验证码
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @invoking
     *	EnsogoAccountsHelper::sendVerifyCode('xxxxxxxx', 'xxxx');
     +----------------------------------------------------------
     * @param $phoneNumber			手机号码
     * @param $max					最大发送次数
     +----------------------------------------------------------
     * @return
     *		 success        boolean true成功 false失败 注册  是否 成功
     *		 message		string 失败信息
     *		 code			lb_auth
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/01/23				初始化
     +----------------------------------------------------------
     **/
    static public function sendVerifyCode($phoneNumber ,$verifyCode ='' , $max=20){
    	// 检查当前 uid  发送的次数 
    	
    	//获取当前 用户的puid
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$key = 'ensogo_'.$puid.'_'.(__FUNCTION__);
    	$sentCount = self::getPlatformTempDataFromRedis($key);
    	if ($sentCount >= $max  ){
    		return ['success'=>false , 'msg'=>'重试了'.$max.'次,若未收到短信，请寻求客服帮助， 谢谢合作！'];
    	}
    	
    	//生成 6位验证码
    	if (empty($verifyCode))
    		$verifyCode = EnsogoAccountsApiHelper::getRandChar(6);
    	
    	//发送 短信
    	list($success , $msg) = SMSHelper::sendVerifyCode($phoneNumber, $verifyCode);
    	
    	if ($success){
    		//发送成功， 则记录redis
    		self::setPlatformTempDataToRedis($key, $sentCount+1);
    		//写文件日志 
    		\Yii::info($key." at ".date("Y-m-d H:i:s")." use $phoneNumber send a VerifyCode :".$verifyCode,'file');
    	}
    	
    	return ['success'=>$success , 'msg'=>$msg,'verifycode'=>$verifyCode];
    	
    }
    
    
    private static function getPlatformTempDataFromRedis($key){
    	//获取当前 用户的puid
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$classification = "Platform_AppTempData";
    	//return \Yii::$app->redis->hget($classification,"user_$puid".".".$key);
		return RedisHelper::RedisGet($classification,"user_$puid".".".$key );
    }
    
    private static function setPlatformTempDataToRedis($key,$val){
    	//获取当前 用户的puid
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$classification = "Platform_AppTempData";
    	//return \Yii::$app->redis->hset($classification,"user_$puid".".".$key,$val);
		return RedisHelper::RedisSet($classification,"user_$puid".".".$key,$val);
    }
    
}
