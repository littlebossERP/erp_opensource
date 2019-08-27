<?php namespace eagle\modules\listing\models;

use eagle\models\SaasEnsogoUser;
use common\helpers\ProxyHelper;

class EnsogoProxy extends Proxy 
{
	const TOKEN_EXPIRE = 600;  		// 提前10分钟刷新

	protected $proxyHost = 'us';

	function getPath(){
		return (\Yii::$app->params['currentEnv'] == 'production' || !isset(\Yii::$app->params['currentEnv']) ? 'Ensogo_Proxy_Server_Online':'Ensogo_Proxy_Server').'/ApiEntry.php';
	}

	function getToken($refresh = false){
		$user = SaasEnsogoUser::findOne($this->site_id);
		if($refresh || $user->created_at < time() - $user->expires_in + self::TOKEN_EXPIRE){
			$this->refreshToken($user);
		}
		return $user->token;
	}

	/**
	 * 强刷refresh_token
	 * @return [type] [description]
	 */
	function refreshUser(){
		
		$user = SaasEnsogoUser::findOne($this->site_id);
		$result = $this->call('getAccountInfo',[
			'id'=>$user->seller_id
		],[],true);
		$user->refresh_token = $result['data']['refresh_token'];
		$user->store_name = $result['data']['store_name'];
		if(!$user->save()){
			throw new \Exception(var_export($user->getErrors(),true), 500);
		}
		$this->refreshToken($user,true);
		return $user;
	}

	function call($action,$get=[],$post=[],$noToken=false){
		$result = call_user_func_array('parent::call', func_get_args());
		if(!$result['success']){
			throw new \Exception('proxy error: '.$result['message'], isset($result['httpCode'])?$result['httpCode']:500 );
		}
		if($result['data']['code']){
			throw new \Exception('ensogo error: '.$result['data']['message'], $result['data']['code']);
		}
		return $result['data'];
	}

	function refreshToken($user=NULL,$new_refresh_token = false){
		ProxyHelper::$host = $this->proxyHost;
		if(!$user){
			$user = SaasEnsogoUser::findOne($this->site_id);
		}
		$param = [
			'refresh_token'=>$user->refresh_token,
			'action'=>'getAccessToken',
			'puid'=>1,
			'lb_auth'=>123
		];
		if($user->refresh_token_number >= 2200 || $new_refresh_token === true){
			$param['new_refresh_token'] = 1;
		}
		$result = ProxyHelper::send($this->getPath(),$param);
		if(!$result['success']){
			if($result['data']['error'] == 'invalid_grant'){
				return $this->refreshUser();
			}
			throw new \Exception($result['message'].$result['data']['error_description'],401);
		}
		$user->created_at = $result['data']['created_at'];
		$user->expires_in = $result['data']['expires_in'];
		$user->token = $result['data']['access_token'];
		$user->refresh_token = $result['data']['refresh_token'];
		$user->seller_id = $result['data']['user_id'];
		if($user->refresh_token_number >= 2200 || $new_refresh_token === true){
			$user->refresh_token_number = $user->refresh_token_number >= 2200 ? 0:$user->refresh_token_number + 1;
			$user->refresh_expires_time = date('Y-m-d H:i:s',$result['data']['created_at'] + (86400*180));
		}
		if(!$user->save()){
			throw new \Exception(var_export($user->getErrors(),true), 500);
			// var_dump($result);
		}
		return $result['success'];
	}

}