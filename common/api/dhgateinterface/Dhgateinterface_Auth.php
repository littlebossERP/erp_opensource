<?php 
namespace common\api\dhgateinterface;

use eagle\models\SaasDhgateUser;
class Dhgateinterface_Auth extends Dhgateinterface_Base{
	
	/**
	 * 第一步请求 授权
	 * dzt 2015/06/19
	 */ 
	function getAuthUrl($state='state',$redirect_uri=''){
		$params = array(
				'client_id' => $this->appKey, // 创建应用时获得的App Key
				'response_type' => 'code', // 此值固定为“code”
				'redirect_uri' => $redirect_uri, // 授权后要回调的URI，即接收Authorization Code的URI。
				'state' => $state, // 用于保持请求和回调的状态，防止第三方应用受到CSRF攻击。授权服务器在回调时（重定向用户浏览器到“redirect_uri”时），会原样回传该参数
				'force_login' => 0, // 是否强制登录；force_login=1 强制登陆；不强制登录，无需此参数，默认为0	
				'display' => 'page', // 登录和授权页面的展现样式，默认为page；page: 适用于web应用；mobile: 适用于手机等智能移动终端应用
				'scope' => 'basic'
		);
			
		$url =  'https://'.$this->authUrl . "/authorize?";
		foreach($params  as $key=>$value){
			$url .= "&$key=".urlencode(trim($value));
		}
		
		return $url;
	}
#####################################################################################
	/**
	 * 第二步使用authorization_code获取refresh_token 和 access_token 
	 * dzt 2015/06/19
	 */
	function getToken($code='',$redirect_uri=''){
		$params = array(
				'grant_type' => 'authorization_code', // 此值固定为“authorization_code”
				'code' => $code, // 通过上面第一步所获得的Authorization Code
				'client_id' => $this->appKey ,
				'client_secret'=>$this->appSecret,
				'redirect_uri'=>$redirect_uri
		);
		
		$url = $this->authUrl.'/access_token' ;
		$response = $this->call_dh_api($url , $params , [] , true);
		
		if(!empty($response['access_token'])){
			$this->access_token = $response['access_token'];
		}
		
		return $response;
	}
#####################################################################################
	/**
	 * 第三步根据长时令牌RefreshToken重新获取访问令牌AccessToken
	 * million 2014/07/18
	 */
	function refreshTokentoAccessToken($refresh_token=''){
		$params = array(
				'grant_type' => 'refresh_token', // 此值固定为“refresh_token”
				'refresh_token' => $refresh_token , // 用于刷新Access Token用的Refresh Token
				'scope' => 'basic', // 以空格分隔的权限列表，若不传递此参数，代表请求默认的basic权限。
				'client_id' => $this->appKey ,
				'client_secret'=>$this->appSecret,
		);
		
		$url = $this->authUrl.'/access_token' ;
		$response = $this->call_dh_api($url , $params , [] , true);
		return $response;
	}
	
#####################################################################################
	/**
	 * 获取accessToken
	 * dzt 2015/06/19
	 */
	function getAccessToken($dhgate_uid , $force_refresh_atoken = false){
		$SDU_obj = SaasDhgateUser::findOne(['dhgate_uid'=>$dhgate_uid]);
		if(!$force_refresh_atoken && strlen( $SDU_obj->access_token ) > 0 && time() < $SDU_obj->access_token_timeout ){
			return $SDU_obj->access_token;
		}
		if( $SDU_obj->refresh_token_timeout > time() + 36000 ){ // @todo要不要提前 认为 refresh token 过期 而不获取access token?
			$r = $this->refreshTokentoAccessToken($SDU_obj->refresh_token);
			if(isset($r['access_token'])){
				$SDU_obj->access_token = $r['access_token'];
				$SDU_obj->access_token_timeout = floor($r['expires_in'] / 1000);// 敦煌是24小时过期 ，@todo 这里写要控制更短的时间过期？
				$SDU_obj->save();
				return $r['access_token'];
			}else {
				return false;
			}
		}
		return false;
	}

#####################################################################################
	/**
	 * 检测是否绑定token并且是否过期
	 *	dzt 2015/06/19
	 */
	static function checkToken($dhgate_uid){
		$SDU_obj = SaasDhgateUser::findOne(['dhgate_uid'=>$dhgate_uid]);
		if (isset($SDU_obj)){
			if($SDU_obj->refresh_token_timeout > time()){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}