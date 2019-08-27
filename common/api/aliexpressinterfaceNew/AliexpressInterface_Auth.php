<?php 
namespace common\api\aliexpressinterfaceNew;

use common\helpers\Helper_Curl;
use eagle\models\SaasAliexpressUser;
class AliexpressInterface_Auth extends AliexpressInterface_Base{

    /**
     * 第一步请求 授权
     */         
    function startAuthUrl($state = 'state', $redirect_uri = ''){
    	/* 参数说明
    	a) client_id：app注册时，分配给app的唯一标示，又称appKey
    	b) site:site参数标识当前授权的站点，直接填写aliexpress
    	c) redirect_uri: app的入口地址，授权临时令牌会以queryString的形式跟在该url后返回
    	d) state：可选，app自定义参数，回跳到redirect_uri时，会原样返回
    	e) aop_signature：签名
    	 */
    	$param1 = '/authorize?';
    	$param = [
    		'client_id' => $this->AppKey,
    		'response_type' => 'code',
    		'redirect_uri'=> $redirect_uri,
    		'sp' => 'ae',
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
    function getToken($code='', $redirect_uri=''){
    	/* getToken接口参数说明：
    	a) grant_type为授权类型，使用authorization_code即可
    	b) need_refresh_token为是否需要返回refresh_token，如果返回了refresh_token，原来获取的refresh_token也不会失效，除非超过半年有效期
    	c) client_id为app唯一标识，即appKey
    	d) client_secret为app密钥
    	e) redirect_uri为app入口地址
    	f) code为授权完成后返回的一次性令牌
    	g) 调用getToken接口不需要签名
    	 */
    	$param1='/token?';
    	
    	$param = [
	    	'client_id' => $this->AppKey,
	    	'client_secret' => $this->appSecret,
	    	'grant_type' => 'authorization_code',
	    	'code'=> $code,
	    	'redirect_uri' => $redirect_uri,
	    	'sp' => 'ae'
    	];
    	$param_str = "";
    	foreach($param as $k=>$v){
    		$param_str .= "&$k=$v" ;
    	}
    	$param_str = ltrim($param_str,"&");
    	$url = $this->host.$param1;
    	$d = Helper_Curl::post($url, $param_str);
    	\Yii::info(print_r($d,true),"file");
//     	preg_match ("/\".*\"/", $d,$match);
//     	$result = "{".$match[0]."}";
    	return json_decode($d,1);
    }
#####################################################################################    
    /**
     * 第三步根据长时令牌RefreshToken重新获取访问令牌AccessToken
     * million 2014/07/18
     */
    function refreshTokentoAccessToken($refresh_token=''){
    	/*$param1='param2/1/system.oauth2/getToken/'.$this->AppKey;
    	$param=array(
    			'grant_type'=>'refresh_token',
    			'client_id'=> $this->AppKey ,
    			'client_secret'=>$this->appSecret,
    			'refresh_token'=>$refresh_token,
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
    	//\Yii::info("debug_refresh_token:".$param_str,"file");
//     	preg_match ("/\".*\"/", $d,$match);
//     	$result = "{".$match[0]."}";
    	return json_decode($d,1);*/
    
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
     * $aliexpress_uid 主键
     * million 2014/07/18
     */
    function getAccessToken($sellerloginid,$force=FALSE){
        //print_r ($_SERVER);exit;
        //暂时屏蔽,2017-03-20  有问题找:hqw 因为被人攻击
    	/*
        if( isset( $_SERVER['SERVER_ADDR'] ) ){
            $nowip= $_SERVER['SERVER_ADDR'];
            //访问这个接口的
            $ip_arr= $this->_allow_ip;
            if( in_array( $nowip,$ip_arr )===false ){
                return false;
            }
        }
        */

    	$this->selleruserid = $sellerloginid;
    		$SAU_obj=SaasAliexpressUser::findOne(['sellerloginid'=>$sellerloginid]);
    		if(strlen($SAU_obj->access_token)>0 && time()<$SAU_obj->access_token_timeout && $force==FALSE){
                //植入速卖通绑定key到session中
                    	$_SESSION['aliexpress_dev_account'] = ['app_key'=>$SAU_obj->app_key, 'app_secret'=>$SAU_obj->app_secret];
			$this->AppKey = $SAU_obj->app_key;
                    	$this->appSecret = $SAU_obj->app_secret;
    			return $SAU_obj->access_token;
    		}

		$lock_result = \Yii::$app->redis->executeCommand('exists', ['ali_token_lock_'.$SAU_obj->refresh_token]);
		
                if($lock_result === '1') {
                    sleep(10);
                    $_SESSION['aliexpress_dev_account'] = ['app_key'=>$SAU_obj->app_key, 'app_secret'=>$SAU_obj->app_secret];
                    $SAU_obj=SaasAliexpressUser::findOne(['sellerloginid'=>$sellerloginid]);
                    $this->AppKey = $SAU_obj->app_key;
                    $this->appSecret = $SAU_obj->app_secret;
                    return $SAU_obj->access_token;
                }


    		if($SAU_obj->refresh_token_timeout > time()+36000){
                    \Yii::$app->redis->executeCommand ('setex', ['ali_token_lock_'.$SAU_obj->refresh_token, 30, 'locked']);
                    //植入速卖通绑定key到session中
                    $_SESSION['aliexpress_dev_account'] = ['app_key'=>$SAU_obj->app_key, 'app_secret'=>$SAU_obj->app_secret];
		    $this->AppKey = $SAU_obj->app_key;
                    $this->appSecret = $SAU_obj->app_secret;

    			$r = $this->refreshTokentoAccessToken($SAU_obj->refresh_token);
    			if(isset($r['access_token'])){
    				$SAU_obj->access_token= $r['access_token'];
    				$SAU_obj->access_token_timeout=time() + 28800;// 8 小时过期
                    		$SAU_obj->save();
                                \Yii::$app->redis->executeCommand ('del', ['ali_token_lock_'.$SAU_obj->refresh_token]);
    				return $r['access_token'];
    			}else {
                                \Yii::$app->redis->executeCommand ('del', ['ali_token_lock_'.$SAU_obj->refresh_token]);
    				return false;
    			}
    		}
    		return false;
    }


    /**
     * 检测是否绑定token并且是否过期
     *	million 2014/07/18
     */
    static function checkToken($sellerloginid){
    	$SAU_obj=SaasAliexpressUser::findOne(['sellerloginid'=>$sellerloginid]);
    	/* $api = new self();
    	$r = $api->postponeToken($SAU_obj->refresh_token,$SAU_obj->access_token);
    	print_r($r);die; */
    	if (isset($SAU_obj)){
	    	if($SAU_obj->refresh_token_timeout > time()){
	    		return true;
	    	}else{
	    		return false;
	    	}
    	}else{
    		return false;
    	}
    }

	/**
	 * 检查用户token情况
	 * @param $sellerloginid
	 * @return int 0 过期, 1 可用, 2 用户不存在
	 */
	static function checkTokenMoreDetail($sellerloginid){
		$SAU_obj=SaasAliexpressUser::findOne(['sellerloginid'=>$sellerloginid]);
		/* $api = new self();
        $r = $api->postponeToken($SAU_obj->refresh_token,$SAU_obj->access_token);
        print_r($r);die; */
		if (isset($SAU_obj)){
			if($SAU_obj->refresh_token_timeout > time()){
				return 1;
			}else{
				return 0;
			}
		}else{
			return 2;
		}
	}
	
	function getAccessToken_t1($sellerloginid,$force=FALSE){
		return false;
		//print_r ($_SERVER);exit;
		print_r($_SERVER);
		
		if( isset( $_SERVER['SERVER_ADDR'] ) ){
            $nowip= $_SERVER['SERVER_ADDR'];
            //访问这个接口的
            $ip_arr= $this->_allow_ip;
            if( in_array( $nowip,$ip_arr )===false ){
                return false;
            }
        }
	
		$this->selleruserid = $sellerloginid;
		$SAU_obj=SaasAliexpressUser::findOne(['sellerloginid'=>$sellerloginid]);
	
		echo '1111111111';
		print_r($SAU_obj);
	
	
		if(strlen($SAU_obj->access_token)>0 && time()<$SAU_obj->access_token_timeout && $force==FALSE){
			//植入速卖通绑定key到session中
			$_SESSION['aliexpress_dev_account'] = ['app_key'=>$SAU_obj->app_key, 'app_secret'=>$SAU_obj->app_secret];
			$this->AppKey = $SAU_obj->app_key;
			$this->appSecret = $SAU_obj->app_secret;
			return $SAU_obj->access_token;
		}
	
		echo '1111111111';
		$lock_result = \Yii::$app->redis->executeCommand('exists', ['ali_token_lock_'.$SAU_obj->refresh_token]);
		print_r($lock_result);
	
	
		if($lock_result === '1') {
			sleep(10);
			$_SESSION['aliexpress_dev_account'] = ['app_key'=>$SAU_obj->app_key, 'app_secret'=>$SAU_obj->app_secret];
			$SAU_obj=SaasAliexpressUser::findOne(['sellerloginid'=>$sellerloginid]);
			$this->AppKey = $SAU_obj->app_key;
			$this->appSecret = $SAU_obj->app_secret;
			return $SAU_obj->access_token;
		}
	
	
		if($SAU_obj->refresh_token_timeout > time()+36000){
			\Yii::$app->redis->executeCommand ('setex', ['ali_token_lock_'.$SAU_obj->refresh_token, 30, 'locked']);
			//植入速卖通绑定key到session中
			$_SESSION['aliexpress_dev_account'] = ['app_key'=>$SAU_obj->app_key, 'app_secret'=>$SAU_obj->app_secret];
			$this->AppKey = $SAU_obj->app_key;
			$this->appSecret = $SAU_obj->app_secret;
	
			$r = $this->refreshTokentoAccessToken($SAU_obj->refresh_token);
				
				
			echo '1111111111';
			print_r($r);
				
			if(isset($r['access_token'])){
				$SAU_obj->access_token= $r['access_token'];
				$SAU_obj->access_token_timeout=time() + 28800;// 8 小时过期
				$SAU_obj->save();
				\Yii::$app->redis->executeCommand ('del', ['ali_token_lock_'.$SAU_obj->refresh_token]);
				return $r['access_token'];
			}else {
				\Yii::$app->redis->executeCommand ('del', ['ali_token_lock_'.$SAU_obj->refresh_token]);
				return false;
			}
		}
		return false;
	}
}
