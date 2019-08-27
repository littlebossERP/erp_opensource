<?php
namespace common\api\aliexpressinterfacev2;

use \Yii;
use common\helpers\Helper_Array;
use common\helpers\Helper_Curl;
use eagle\models\Counter;
use eagle\modules\util\helpers\RedisHelper;

class AliexpressInterface_Base_Qimen
{
    protected $access_token = '';
    protected $selleruserid = '';
    protected $AppKey;
    protected $appSecret;
    protected $host;
    protected $hosturl_officialApi = '';
    protected $hosturl_customApi = '';

    public function __get($name){
        if ('access_token' === $name) {
            return $this->access_token;
        } else {
            return false;
        }
    }
    
    public function __set($name, $value){
    	if ('selleruserid' === $name) {
    		$this->selleruserid = $value;
    	}
    }

    function __construct(){
        $this->_config();
    }
    
    public function getDevAccount()
    {
    	$account['app_key'] = $this->AppKey;
    	$account['app_secret'] = $this->appSecret;
    	return $account;
    }

    /**
     * 配置
     *
     */
    function _config()
    {
		// TODO aliexpress dev account @XXX@
        $this->AppKey = '@XXX@';
        $this->appSecret = '@XXX@';
        
        //测试环境--官方场景
        //$this->hosturl_officialApi = 'http://qimen.api.taobao.com/router/qmtest';
        //正式环境--官方场景
        $this->hosturl_officialApi = 'http://qimen.api.taobao.com/router/qm';
        
        // 测试环境--自定义场景
        // $this->hosturl_customApi = 'http://@XXX@.api.taobao.com/router/qmtest';
        // 正式环境--自定义场景
        $this->hosturl_customApi = 'http://@XXX@.api.taobao.com/router/qm';
    }

    /**
     *  签名 signature 算法
     */
    function signature($appParamArr1 = null, $appParamArr = null)
    {
        ksort($appParamArr);

        $sign = '';
        foreach ($appParamArr as $key => $val) {
            if ($key != '' && $val != '') {
                $sign .= $key . $val;
            }
        }
        $sign = strtoupper(md5($appParamArr1.$sign.$appParamArr1));
        //print_r($sign);die;
        return $sign;
    }
    
    /***
     * 基础的api请求方法，奇门
    */
    function request($apiName, $param, $is_signature = 1, $api_path = '', $api_type = 'official')
    {
    	//统计api调用情况
    	//RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
    	//RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
    	//每小时统计次数信息
    	//RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
    	//RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));
    
    	$apiName = $api_path.strtolower($apiName);
    	//各 api的 参数
    	$param_base = [
	    	'method' => $apiName,
	    	'app_key' => $this->AppKey,
	    	'timestamp' => date("Y-m-d H:i:s"),
	    	'format' => 'json',
	    	'v' => '2.0',
	    	'sign_method' => 'md5',
	    	'target_app_key' => $this->AppKey,
    	];
    	if (is_array($param)) {
    		$param = $param_base + $param;
    	} else {
    		$param = $param_base;
    	}
    	Helper_Array::removeEmpty($param);
    
    	$signature = $this->signature($this->appSecret, $param);
    	if ($is_signature) {
    		$param['sign'] = $signature;
    	}
    	$param_request = $param;

		//echo json_encode($param_request);

    	$bodyUrl = '';
    	foreach ($param_request as $sysParamKey => $sysParamValue){
    		$bodyUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
    	}
    	
    	$bodyUrl = substr($bodyUrl, 0, -1);
    	
    	$url_name = 'hosturl_'.$api_type.'Api';
    	$response = self::curl($this->$url_name.'?', $bodyUrl);
    	
    	$r = $this->response($response, $apiName);
    
    	return $r;
    }
    
    /***
     * 基础的api请求方法，奇门
    */
    function request2($apiName, $api_param, $is_signature = 1, $api_path = '', $api_type = 'official')
    {
    	//统计api调用情况
    	//RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
    	//RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
    	//每小时统计次数信息
    	//RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
    	//RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));
    
    	$apiName = $api_path.strtolower($apiName);
    	//各 api的 参数
    	$param_base = [
	    	'method' => $apiName,
	    	'app_key' => $this->AppKey,
	    	'timestamp' => date("Y-m-d H:i:s"),
	    	'format' => 'json',
	    	'v' => '2.0',
	    	'sign_method' => 'md5',
	    	'target_app_key' => $this->AppKey,
    	];
    	if (is_array($api_param)) {
    		$param = $param_base + $api_param;
    	} else {
    		$param = $param_base;
    	}
    	Helper_Array::removeEmpty($param);
    	Helper_Array::removeEmpty($api_param);
    
    	$signature = $this->signature($this->appSecret, $param);
    	if ($is_signature) {
    		$param_base['sign'] = $signature;
    	}
    	 
    	$bodyUrl = '';
    	foreach ($param_base as $sysParamKey => $sysParamValue){
    		$bodyUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
    	}
    	$bodyUrl = substr($bodyUrl, 0, -1);
    	 
    	$url_name = 'hosturl_'.$api_type.'Api';
    	$response = Helper_Curl::post($this->$url_name.'?'.$bodyUrl, $api_param);
    	 
    	$r = $this->response($response, $apiName);
    
    	return $r;
    }

    public function curl($url, $bodyUrl)
    {
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url.$bodyUrl);
    	curl_setopt($ch, CURLOPT_FAILONERROR, false);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 500);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    	//curl_setopt ( $ch, CURLOPT_USERAGENT, "top-sdk-php" );
    	//https 请求
    	if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	}
    	$response = curl_exec($ch);
    	
    	$error = curl_error($ch);
    	curl_close($ch);
    	if ($error){
    		$e = 'curl_error:'.(print_r($error, true)).'URL:'.$url.'DATA:'.$requestBody;
    		\Yii::error($e, "file");
    		return false;
    	}
    	return $response;
    }
    
    public function response($data, $apiName){
    	$r = json_decode($data, true);
    	$result = $r;
    	
    	if(isset($result['response'])){
    		$result = $result['response'];
    		
    		if(!empty($r['request_id'])){
    			$result['request_id'] = $r['request_id'];
    		}
    	}
    	
    	if(strpos($apiName, 'getpdfsbycloudprint') !== false){
    		$result = $result['result'];
    	}
    	if(!empty($result['result_success']) ){
    		if(!empty($result['result'])){
    			$result = $result['result'];
    		}
    		else if(!empty($result['result_list'])){
    			$result = $result['result_list'];
    		}
    	}
    	
    	//兼容旧模式参数
    	if(!empty($result['error_response'])){
    		$result['result_success'] = false;
    		$result['success'] = false;
    		$result['error_code'] = empty($result['error_response']['sub_code']) ? $result['error_response']['code'] : $result['error_response']['sub_code'];
    		$result['error_message'] = empty($result['error_response']['sub_msg']) ? $result['error_response']['msg'] : $result['error_response']['sub_msg'];
    	}
    	else if(isset($result['flag'])){
    		$result['success'] = 	$result['flag'] == 'failure' ? false : true;
    		$result['result_success'] = $result['success'];
    		$result['error_code'] = empty($result['sub_code']) ? $result['code'] : $result['sub_code'];
    		$result['error_message'] = empty($result['sub_message']) ? $result['message'] : $result['sub_message'];
    		$result['error_desc'] = $result['error_message'];
    	}
    	else if(isset($result['result_success'])){
    		$result['success'] = 	$result['result_success'];
    		$result['error_code'] = empty($result['result_error_code']) ? '' : $result['result_error_code'];
    		$result['error_message'] = empty($result['result_error_desc']) ? '' : $result['result_error_desc'];
    	}
    	else if(!empty($result['message'])){
    		$result['success'] = false;
    		$result['result_success'] = false;
    		$result['error_code'] = empty($result['code']) ? '' : $result['code'];
    		$result['error_message'] = empty($result['message']) ? '' : $result['message'];
    	}
    	
    	return $result;
    }
}
