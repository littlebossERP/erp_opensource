<?php
namespace eagle\modules\platform\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasWishUser;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
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
class WishAccountsV2Helper
{
    
    
    //用户修改Wish账号--api和email    
    public static function updateWishAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId == 0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Wish的绑定!"));
    	}
    	if (empty($params["store_name"]) or empty($params["token"])){
			return array(false,TranslateHelper::t("store_name 和 token 都不能为空"));
		}
		
    	$WishData = SaasWishUser::findOne($params["Wish_id"]);    	
    	if ($WishData==null)  return array(false,TranslateHelper::t("该Wish账户不存在"));
		
		#检查Wish信息的合法性(name,token是否被占用)
		$filteData = SaasWishUser::find()->where(array('store_name' => $params['store_name'] ,'uid'=>$saasId ))->one();
		if ($filteData!==null){
			$id = $filteData->site_id;
			if ($id !== $params['Wish_id']){
				return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
			}
			
		}
		$filteData=SaasWishUser::find()->where(array('token' => $params['token']))->one();
		if ($filteData!==null){
			$id = $filteData->site_id;
			if ($id !== $params['Wish_id']){
				return array(false,TranslateHelper::t("token 已存在（不区分大小写），不能重复使用!"));
			}
			
		}
		
    	//保存Wish api的变化信息

		$WishData->store_name = $params["store_name"];
		$WishData->token = $params["token"];
		$WishData->is_active = $params["is_active"];
		$WishData->update_time = GetControlData::getNowDateTime_str();
		 
		if ($WishData->save()){
			return array(true,"");
		}else{
			$message = '';
            foreach ($WishData->errors as $k => $anError){
				$message .= "Update Wish user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    //用户绑定Wish账号
    public static function createWishAccount($params){
    	// #1.检查Wish信息的合法性，连接测试是否ok
    	// list($ret,$message)=self::_checkWishUserInfo($params);
    	// if ($ret===false) return array($ret,$message);
    	
    	// #2. Wish email的连接检查
    	// list($ret,$message)=self::_checkWishEmailInfo($params);
    	// if ($ret===false) return array($ret,$message);
		
		#3.检查Wish信息的合法性(name,token是否被占用)
		#检查Wish信息的合法性(name,token是否被占用)
		$filteData = SaasWishUser::find()->where(array('store_name' => $params['store_name']))->one();
		if ($filteData!==null){
			return array(false, TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
		}
		
		//2015-10-20 v2 授权token 隨后才能获取
		if (!empty($params['token'])){
			$filteData = SaasWishUser::find()->where(array('token' => $params['token']))->one();
			if ($filteData!==null){
				return array(false, TranslateHelper::t("token 已存在（不区分大小写），不能重复使用!"));
			}
		}
			
    	/* 2015-08-26 token 已经改变了授权方式 
    	if (empty($params["store_name"]) or empty($params["client_id"]) or  empty($params['client_secret']) or empty($params['redirect_uri'])){
			return array(false, TranslateHelper::t("店铺名称  ,   客户id  , 客户端密钥和 重新载入URI 都不能为空"));
		}
		*/
    	#4. 保存Wish信息到db
    	$info=array();
    	$info['store_name'] = $params['store_name'];
    	if (!empty($params['token']))
    		$info['token'] = $params['token'];
    	else
    		$info['token'] = '';
		/*
    	$info['client_id'] = $params['client_id'];
    	$info['client_secret'] = $params['client_secret'];
    	$info['redirect_uri'] = $params['redirect_uri'];
		*/
    	$info['create_time'] = GetControlData::getNowDateTime_str();    	
    	$info['is_active']=$params['is_active'];

    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,"请退出小老板并重新登录，再进行Wish的绑定!");
    	}
    	$ret=self::insertWishUserInfo($info,$saasId);
    	if ($ret !==true ){
    		return array(false,$ret);
    	}
    }
	
    //$info---'store_name','merchant_id','marketplace_id','access_key_id','secret_access_key','is_active'
    public static function insertWishUserInfo($info,$saasId){
    	//1. 保存Wish 账号级别（marchant_id）的信息
    	$now = GetControlData::getNowDateTime_str();
    	//time();
    	
    	$user = new SaasWishUser();
    	$user->token = $info['token'];
    	$user->store_name = $info['store_name'];
    	$user->create_time = $now;    	 
    	$user->update_time = $now;
    	$user->is_active = $info['is_active'];
    	$user->uid = $saasId;
    
    	
    	
    	if (!$user->save()) {
			$message = '';
            foreach ($user->errors as $k => $anError){
				$message .= "Insert Wish user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
			return $message;
    	}
    	//重置账号绑定情况到redis
    	PlatformAccountApi::callbackAfterRegisterAccount('wish',$saasId);
    	//绑定账号时，将拉取站内信的app数据一并生成
    	
    	
    	/*
    	$rtn = MessageApiHelper::setSaasMsgAutosync($saasId, $user->site_id, $info['store_name'], 'wish');
    	if('fail' == $rtn['code']){
    		return $rtn['message'];
    	}
    	*/
    	return true;
    }    
    
    
	//用户删除Wish账号
    public static function deleteWishAccount($id) 
    {
		$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Wish的绑定!"));
    	}
		$model = SaasWishUser::findOne($id);
        if ($model->delete()){
        	//重置账号绑定情况到redis
        	PlatformAccountApi::callbackAfterDeleteAccount('wish',$saasId,['site_id'=>$id]);
            return true;
        }else{
            $message = '';
            foreach ($model->errors as $k => $anError){
				$message .= "Delete Wish user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 获取wish授权账号的信息
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
     * 	array				wishReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		WishAccountsV2Helper::getWishAccountInfo($accessToken)
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2014/08/26				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    public static function getWishAccountInfo($accessToken){
    	$get_params = [
    	'parms'=>json_encode(['access_token'=>$accessToken,
    			]),
    		];
    	return WishProxyConnectHelper::call_WISH_api('GetAccessToken',$get_params);
    }//end of function getWishAccountInfo
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 获取wish授权信息
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
     * 	array				wishReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		WishProxyConnectHelper::getWishToken($client_id , $client_secret , $code , $redirect_uri)
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2014/08/26				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    public static function getWishToken( $code ){
    	//littleboss public app info start  
		// TODO proxy dev account @XXX@ 
    	$client_id = '@XXX@';
    	$client_secret = '@XXX@';
		
		$tempu = parse_url(\Yii::$app->request->hostInfo);
		$host = $tempu['host'];
    	$redirect_uri  = 'https://'.$host.'/platform/wish-accounts-v2/get-wish-authorization-code';
    	//littleboss public app info end
    	$get_params = [ 
    	'parms'=>json_encode(['client_id'=>$client_id,
    	'client_secret'=>$client_secret,
    	'code'=>$code,
    	'redirect_uri'=>$redirect_uri,
    	]),
    	];
    	return WishProxyConnectHelper::call_WISH_api('GetAccessToken',$get_params);
    }//end of getWishToken
    
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 更新 wish授权
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
     * 	array				wishReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		WishProxyConnectHelper::refreshWishToken($client_id , $client_secret , $resfresh_token)
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2014/08/26				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    public static function refreshWishToken( $refresh_token){
    	
    	//littleboss public app info start    
		// TODO proxy dev account @XXX@ 
    	$client_id = '@XXX@';
    	$client_secret = '@XXX@';
		
		$tempu = parse_url(\Yii::$app->request->hostInfo);
		$host = $tempu['host'];
    	$redirect_uri  = 'https://'.$host.'/platform/wish-accounts-v2/get-wish-authorization-code';
    	//littleboss public app info end
    	$get_params = [ 
    	'parms'=>json_encode(['client_id'=>$client_id,
    	'client_secret'=>$client_secret,
    	'redirect_uri'=>$redirect_uri,
    	'refresh_token'=>$refresh_token,
    	]),
    	];
    	return WishProxyConnectHelper::call_WISH_api('refreshAccessToken',$get_params);
    }//end of refreshWishToken
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 更新 wish授权
     +----------------------------------------------------------------------------------------------------------------------------
     * @access static
     +----------------------------------------------------------------------------------------------------------------------------
     * @param 	$client_id			string		客户端ID
     * 			$code				string		wish redirect出来 的code
     +----------------------------------------------------------------------------------------------------------------------------
     * @return			array
     * 	boolean				success   	执行结果
     * 	string/array		message   	执行失败的提示信息
     * 	array				wishReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		WishProxyConnectHelper::refreshWishToken($client_id , $client_secret , $resfresh_token)
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2014/08/26				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    static public function saveWishToken(&$model , &$result){
    	if (!empty($result) && !empty($model)){
    		
				if ($result['success']){
					//proxy 层成功 取出proxyResponse 结果
	
					if(!empty($result['proxyResponse']) && $result['proxyResponse']['success']){
						//wish 数据返回成功
						
						if (!empty($result['proxyResponse']['wishReturn'])){
							//WISH api 调用 成功 
							if (is_string($result['proxyResponse']['wishReturn'])){
								// json 字符
								$wishReturn = json_decode($result['proxyResponse']['wishReturn'],true);
							}else if (is_array($result['proxyResponse']['wishReturn'])){
								//array
								$wishReturn = $result['proxyResponse']['wishReturn'];
							}else{
								//其他格式  todo
								$wishReturn = [];
							}
						}
							
						if (!empty($wishReturn['data'])){
							if (!empty( $_REQUEST['code'])){
								$model->code = $_REQUEST['code'];
							}
							if (!empty($wishReturn['data']['access_token']))
								$model->token = $wishReturn['data']['access_token'];
							if (!empty($wishReturn['data']['refresh_token']))
								$model->refresh_token = $wishReturn['data']['refresh_token'];
							if (!empty($wishReturn['data']['expires_in']))
								$model->expires_in = $wishReturn['data']['expires_in'];
							if (!empty($wishReturn['data']['expiry_time']))
								$model->expiry_time = date('Y-m-d H:i:s',$wishReturn['data']['expiry_time']);
							
							//20170814 验证access token 与当前的账号信息是否一致
							if (!empty($wishReturn['data']['access_token'])){
								$tmpRT = self::getWishAccountInfo($wishReturn['data']['access_token']);
								\Yii::info(" file=".__file__.' line='.__line__." result=".json_encode($tmpRT),"file");
								if (!empty($model->merchant_id) && !empty( $tmpRT['data']['merchant_id']) && $model->merchant_id != $tmpRT['data']['merchant_id']){
									return ['success'=>false, 'message'=>'授权失败：新授权的账号与当前账号不相符'];
								}
								if (isset($tmpRT['data']['merchant_id'])){
									$model->merchant_id = @$tmpRT['data']['merchant_id'];
								}
								
								if (isset($tmpRT['data']['merchant_username'])){
									$model->merchant_username = @$tmpRT['data']['merchant_username'];
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
							}else{
								//保存成功
							}
						}
							
					}//end of if(!empty($result['proxyResponse']) && $result['proxyResponse']['success']) wish api 返回结果处理
					else{
						//wish 返回失败
						return ['success'=>false, 'message'=>'wish api发生未知错误！','access_token'=>''];
					}
				}//end of if ($result['success']) proxy 返回结果
				else {
					//proxy 返回失败
					return ['success'=>false, 'message'=>'proxy 发生未知错误！','access_token'=>''];
				}//end of else
			return ['success'=>true, 'message'=>'','access_token'=>$model->token];
    	}//end of if (!empty($code) && !empty($client_id)) code 和 client id 不能为空
    	return ['success'=>false, 'message'=>'参数格式不正确！','access_token'=>''];
    }//end of saveWishToken
    
    /**
     +---------------------------------------------------------------------------------------------
     * 设置wish order 手动同步订单
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param $site_id		saas_wish_user.site_id
     +---------------------------------------------------------------------------------------------
     * @return array ('message'=>执行详细结果
     * 				  'success'=> true 成功 false 失败	)
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2015/10/30				初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function setManualRetrieveOrder($site_id){
    	$model = SaasWishUser::findOne(['site_id'=>$site_id]);
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
}
