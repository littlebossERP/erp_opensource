<?php namespace eagle\modules\listing\models;

use eagle\models\SaasWishUser;
use common\helpers\ProxyHelper;

class WishProxy extends Proxy 
{
	protected $proxyHost = 'us';

	function getPath(){
		return 'Wish_Proxy_Server/v3.php';
	}

	function getToken(){
		$user = SaasWishUser::findOne($this->site_id);
		if( $user->expiry_time < date('Y-m-d H:i:s',strtotime('+2 days')) ){
			// var_dump('token need refresh');
			$this->refreshToken($user);
		}
		return $user->token;
	}

	function call($action,$get=[],$post=[],$noToken=false){
		$result = call_user_func_array('parent::call', func_get_args());
		if(!$result['success']){
			throw new \Exception('proxy error: '.$result['message'], $result['httpCode']);
		}
		if($result['wishReturn']['code']){
			throw new \Exception('wish error: '.$result['wishReturn']['message'], $result['wishReturn']['code']);
		}
		return $result['wishReturn']['data'];
	}

	/**
	 * 刷新token
	 * 不返回内容而是直接保存到数据库中
	 * @author huaqingfeng
	 * @version 2016-05-06
	 * @param  [\eagle\models\SaasWishUser] $user
	 * @return void
	 */
	function refreshToken($user){
		ProxyHelper::$host = $this->proxyHost;
		$result = ProxyHelper::send($this->getPath(),[
			'refresh_token'=>$user->refresh_token,
			'action'=>'refreshtoken',
			'token'=>1
		])['wishReturn'];
		if($result['code']){
			throw new \Exception("refreshToken failed: ".$result['message'], $result['code']);
		}
		$data = $result['data'];
		$user->token 			= $data['access_token'];
		$user->refresh_token 	= $data['refresh_token'];
		$user->expires_in 		= $data['expires_in'];
		$user->expiry_time 		= date('Y-m-d H:i:s',$data['expiry_time']);
		if(!$user->save()){
			throw new \Exception(var_export($user->getErrors(),true), 500);
		}
	}


}