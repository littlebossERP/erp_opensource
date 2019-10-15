<?php
namespace common\api\shopeeinterface;

use \Yii;
use common\helpers\Helper_Array;
use common\helpers\Helper_Curl;
use eagle\models\Counter;
use eagle\modules\util\helpers\RedisHelper;

class ShopeeInterface_Base
{
    // 请求走proxy
    static $goproxy = 0;
    
    // TODO shopee dev account @XXX@
    protected $partner_id = '@XXX@';
    protected $secret_key = '@XXX@';
   
    protected $Shop_id = '';
    protected $host;
    protected $hosturl = '';

    public function __set($name, $value){
    	$this->$name = $value;
    }
    
    function __construct($shop_id = ''){
        $this->_config($shop_id);
    }

    /**
     * 配置
     */
    function _config($shop_id){
        $this->hosturl = 'https://partner.shopeemobile.com/api/v1/';
//     	$this->hosturl = 'https://partner.uat.shopeemobile.com/api/v1/';
    	
        //初始授权信息
        if(!empty($shop_id)){
        	$user = \eagle\models\SaasShopeeUser::findOne(['shop_id' => $shop_id]);
        	if(!empty($user)){
        		$this->partner_id = $user->partner_id;
        		$this->shop_id = $user->shop_id;
        		$this->secret_key = $user->secret_key;
        	}
        }
    }


    /***
     * 基础的api请求方法
     */
    function request($apiName, $param, $is_signature = 1, $api_path = '')
    {
        //统计api调用情况
        //RedisHelper::RedisAdd('shopee_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        //RedisHelper::RedisAdd('shopee_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        //RedisHelper::RedisAdd('shopee_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        //RedisHelper::RedisAdd('shopee_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));

        //各 api的 参数
        $param_base = [
	        'timestamp' => time(),
	        'partner_id' => intval($this->partner_id),
	        'shopid' => intval($this->shop_id),
        ];
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        //Helper_Array::removeEmpty($param);
        
        
        // dzt20191012 shopee丢包严重，改走proxy
        if(empty(self::$goproxy)){
            $param_request = json_encode($param);
            //签名
            $sign = hash_hmac("sha256", $this->hosturl.$api_path.'|'.$param_request, $this->secret_key);
            
            $headers[] = "Content-Type: application/json";
            $headers[] = "Authorization: ".$sign;
            Helper_Curl::$timeout = 60;
            Helper_Curl::$connecttimeout = 10;
            $response = Helper_Curl::post($this->hosturl.$api_path, $param_request, $headers);
        }else{
            $param['secret_key'] = $this->secret_key;
            $param_request = json_encode($param);
            shopee_api_proxy::$timeout = 60;
            shopee_api_proxy::$connecttimeout = 10;
            $response = shopee_api_proxy::post($this->hosturl.$api_path, $param_request);
            
        }
        
        $result = $this->response($response);
        
        return $result;
    }

    function response($data){
        $result = json_decode($data, true);
        
        //整理信息
        if(empty($result['msg']) && !empty($result['error'])){
        	$result['msg'] = $result['error'];
        }
        
        return $result;
    }

    /**
     * 日期格式
     */
    function datetime($time = null)
    {
        if (empty($time)) {
            $time = time();
        }
        return date('m/d/Y', $time);
    }

    
    /**
	 * 第一步请求 授权
	 */ 
	function getAuthUrl($state='state',$redirect_uri=''){
		$params = array(
	        'id' => intval($this->partner_id),
            'redirect' => $redirect_uri, // 授权后要回调的URI，即接收Authorization Code的URI。
		    'state' => $state, // 用于保持请求和回调的状态，防止第三方应用受到CSRF攻击。授权服务器在回调时（重定向用户浏览器到“redirect_uri”时），会原样回传该参数
		);
		
		// token
		$token = hash("sha256", $this->secret_key.$redirect_uri);
		$params['token'] = $token;
		
		$action = 'shop/auth_partner?';
		$url = $this->hosturl . $action;
		
		$temp = '';
		foreach($params as $key=>$value){
		    if(empty($temp)){
		        $temp .= $key."=".urlencode(trim($value));
		    }else{
		        $temp .= "&".$key."=".urlencode(trim($value));
		    }
		}
		
		return $url.$temp;
	}

}
