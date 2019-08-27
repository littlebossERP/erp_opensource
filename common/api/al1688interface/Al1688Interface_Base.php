<?php
namespace common\api\al1688interface;

use \Yii;
use common\helpers\Helper_Array;
use common\helpers\Helper_Curl;
use eagle\models\Counter;
use eagle\modules\util\helpers\RedisHelper;

class Al1688Interface_Base
{
    protected $access_token = ''; // 访问 token .
    protected $selleruserid = '';
    protected $AppKey;
    protected $appSecret;
    protected $host;
    protected $hosturl = '';
    protected $site = 'aliexpress';

    public function __get($name)
    {
        if ('access_token' === $name) {
            return $this->access_token;
        } else {
            return false;
        }
    }


    public function __set($name, $value)
    {
        //hack 用来定位用户对应的速卖通开发者账号
        if ('access_token' === $name) {
            $this->access_token = $value;
        }
    }

    function __construct()
    {
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
     */
    function _config(){
    	// TODO 1688 dev account @XXX@
        $this->AppKey = '@XXX@';
        $this->appSecret = '@XXX@';
        
        $this->host = 'https://auth.1688.com/oauth';
        $this->hosturl = 'gw.open.1688.com/openapi/';
    }


    /**
     *  签名 signature 算法
     */
    function signature($appParamArr1 = null, $appParamArr = null){
        ksort($appParamArr);

        $sign = '';
        foreach ($appParamArr as $key => $val) {
            if ($key != '' && $val != '') {
                $sign .= $key . $val;
            }
        }
        $sign = strtoupper(bin2hex(hash_hmac("sha1", $appParamArr1.$sign, $this->appSecret, true)));
        //print_r($sign);die;
        return $sign;
    }


    /***
     * 基础的api请求方法
     */
    function request($apiName, $param, $is_signature = 1, $api_path = '')
    {
        //统计api调用情况
        //RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        //RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        //RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        //RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));

    	// 基本的 url
        $baseUrlParam = 'param2/1/'.$api_path.'/'.$this->AppKey;
        $baseUrl = 'http://' . $this->hosturl . $baseUrlParam;
        //各 api的 参数
        $param_base = [
	        //'_aop_timestamp' => time(),
	        'access_token' => $this->access_token,
        ];
        
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        
        $signature = $this->signature($baseUrlParam, $param);
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;
        $response = Helper_Curl::post($baseUrl, $param_request);
        $result = $this->response($response, $apiName);
        
        return $result;
    }

    function response($data, $apiName){
        $r = json_decode($data, true);
        $result = $r;
        
        $key = str_replace(".", "_", $apiName)."_response";
        if(isset($r[$key]['result'])){
        	$result = $r[$key]['result'];
        }
        else if(isset($r[$key]['results'])){
        	$result = $r[$key]['results'];
        }
        else if(isset($r[$key])){
        	$result =  $r[$key];
        }
        
        //兼容旧模式参数
        if(!empty($result['error_response'])){
        	$result['success'] = false;
        	$result['error_code'] = empty($result['error_response']['sub_code']) ? $result['error_response']['code'] : $result['error_response']['sub_code'];
        	$result['error_message'] = empty($result['error_response']['sub_msg']) ? $result['error_response']['msg'] : $result['error_response']['sub_msg'];
        	unset($result['error_response']);
        }
        if(isset($result['result_success'])){
        	$result['success'] = 	$result['result_success'];
        	$result['error_code'] = empty($result['result_error_code']) ? '' : $result['result_error_code'];
        	$result['error_message'] = empty($result['result_error_desc']) ? '' : $result['result_error_desc'];
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


}
