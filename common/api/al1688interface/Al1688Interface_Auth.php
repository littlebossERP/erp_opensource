<?php 
namespace common\api\al1688interface;

use common\helpers\Helper_Curl;
use eagle\models\SaasAliexpressUser;
use eagle\models\Saas1688User;
class Al1688Interface_Auth extends Al1688Interface_Base{

	private static $config_path = "../ali_config/";
    /**
     * 第一步请求 授权
     */         
    function startAuthUrl($state = 'state', $redirect_uri = ''){
    	$param1 = '/authorize?';
    	$param = [
    		'client_id' => $this->AppKey,
    		'redirect_uri'=> $redirect_uri,
    		'site' => '1688',
    		'state' => $state
    	];
    	$param_str = '';
    	foreach($param as $k => $v){
    		$param_str .= "&$k=$v" ;
    	}
    	$param_str = ltrim($param_str, '&');
    	$url = $this->host.$param1.$param_str;
    	return $url;
    }
    #####################################################################################    
    
    /**
     * 第二步使用code获取长时令牌
     * refresh_token 和 access_token 
     */
    function codeToToken($code='', $redirect_uri=''){
    	$url = 'https://'.$this->hosturl.'http/1/system.oauth2/getToken/'.$this->AppKey.'?';
    	
    	$param = [
	    	'client_id' => $this->AppKey,
	    	'client_secret' => $this->appSecret,
	    	'grant_type' => 'authorization_code',
	    	'need_refresh_token' => 'true',
	    	'code'=> $code,
	    	'redirect_uri' => $redirect_uri,
    	];
    	$param_str = "";
    	foreach($param as $k=>$v){
    		$param_str .= "&$k=$v" ;
    	}
    	$param_str = ltrim($param_str,"&");
    	$d = Helper_Curl::post($url, $param_str);
    	\Yii::info('1688Interface_Auth, getToken: '.print_r($d,true),"file");
    	
    	return json_decode($d,1);
    }
    
    /**
     * 通过RefreshToken 刷新 AccessToken
     */
    function refreshTokentoAccessToken($refresh_token = ''){
        $url = 'https://'.$this->hosturl.'param2/1/system.oauth2/getToken/'.$this->AppKey.'?';
    	$param = [
	    	'client_id' => $this->AppKey,
	    	'client_secret' => $this->appSecret,
	    	'grant_type' => 'refresh_token',
	    	'refresh_token'=> $refresh_token,
    	];
    	$param_str='';
    	foreach($param as $k=>$v){
    		$param_str.="&$k=$v" ;
    	}
    	$param_str = ltrim($param_str,'&');
    	$d = Helper_Curl::post($url, $param_str);
    	return json_decode($d,1);
    
    }
    
    /**
     * 换取长时令牌RefreshToken
     * million 2014/07/18
     */
    function postponeToken($refresh_token='',$access_token=''){
    	$param1='param2/1/system.oauth2/postponeToken/'.$this->AppKey;
    	$param=array(
    			'client_id'=> $this->AppKey ,
    			'client_secret'=>$this->appSecret,
    			'refresh_token'=>$refresh_token,
    			'access_token'=>$access_token,
    	);
    
    	$param_str='';
    	foreach($param as $k=>$v){
    		$param_str.="&$k=$v" ;
    	}
    	$param_str=ltrim($param_str,'&');
    
    	$url='https://'.$this->hosturl.$param1;
    	$headers=array(
    			"X-Sequence: seq-".time()
    	);
    
    	$d=Helper_Curl::post($url, $param_str,$headers);
    	\Yii::info("postponeToken:".print_r($d,true),"file");
    	//     	preg_match ("/\".*\"/", $d,$match);
    	//     	$result = "{".$match[0]."}";
    	return json_decode($d,1);
    
    }
    
    #####################################################################################

    function setAppInfo($app_key,$app_secret){
        $this->AppKey = $app_key;
        $this->appSecret = $app_secret;
    }

    /**
     * 获取accessToken
     * lrq 2018/04/13
     */
    function getAccessToken($aliId, $force=FALSE){
        $SAU_obj = Saas1688User::findOne(['aliId' => $aliId]);
		if(strlen($SAU_obj->access_token) > 0 && time() < $SAU_obj->access_token_timeout && $force == FALSE){
			return $SAU_obj->access_token;
    	}

		$lock_result = \Yii::$app->redis->executeCommand('exists', ['al1688_token_lock_'.$SAU_obj->refresh_token]);
     	if($lock_result === '1') {
        	sleep(10);
       		$SAU_obj=Saas1688User::findOne(['aliId' => $aliId]);
         	return $SAU_obj->access_token;
    	}

    	if($SAU_obj->refresh_token_timeout > time() + 3600 * 24){
      		\Yii::$app->redis->executeCommand ('setex', ['al1688_token_lock_'.$SAU_obj->refresh_token, 30, 'locked']);

    		$r = $this->refreshTokentoAccessToken($SAU_obj->refresh_token);
    		if(!empty($r['access_token'])){
    			$SAU_obj->access_token = $r['access_token'];
    			$SAU_obj->access_token_timeout = time() + 28800;// 8 小时过期
              	$SAU_obj->save();
             	\Yii::$app->redis->executeCommand ('del', ['al1688_token_lock_'.$SAU_obj->refresh_token]);
    			return $r['access_token'];
    		}else {
              	\Yii::$app->redis->executeCommand ('del', ['al1688_token_lock_'.$SAU_obj->refresh_token]);
    			return false;
    		}
    	}
    	
		return false;
    }
	
	
}
