<?php
namespace common\api\aliexpressinterfacev2;

use \Yii;
use common\helpers\Helper_Array;
use common\helpers\Helper_Curl;
use eagle\models\Counter;
use eagle\modules\util\helpers\RedisHelper;

class AliexpressInterface_Base_V2
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
            if (!empty($_SESSION['aliexpress_dev_account'])) {
                $this->AppKey = $_SESSION['aliexpress_dev_account']['app_key'];
                $this->appSecret = $_SESSION['aliexpress_dev_account']['app_secret'];
            }
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
     *
     * 【速卖通API|重要通知】
     * 由于服务机房迁移，目前请各位将域名绑定
     * 如：原来绑定 110.75.216.110 gw.api.alibaba.com
     * 切换成新的绑定：110.75.69.81 gw.api.alibaba.com
     * 请相互通知！谢谢合作请尽快迁移
     * 如未做绑定，可取消绑定 直接访问网页，如遇503类似错误，IP地址访问美国了，可设置成新的绑定地址。
     * 14-6-24
     */
    function _config()
    {
        // TODO aliexpress dev account @XXX@
        $this->AppKey = '@XXX@';
        $this->appSecret = '@XXX@';
        $this->host = 'https://oauth.aliexpress.com';
        $this->hosturl = 'http://gw.api.taobao.com/router/rest';//'110.75.69.81/openapi/';//gw.api.alibaba.com域名访问暂时改成IP：110.75.69.81 访问
        $this->site = 'aliexpress';
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

    /**
     *  签名 signature 算法 速卖通官网示例
     */
    function signature2($appParamArr1 = null, $appParamArr = null)
    {
        $sign = $appParamArr1;
        $tmp = [];
        foreach ($appParamArr as $key => $val) {
            $tmp[] = $key . $val;
        }

        sort($tmp);
        $sign_str = join('', $tmp);
        $sign .= $sign_str;

        $sign = strtoupper(bin2hex(hash_hmac("sha1", $sign, $this->appSecret, true)));
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

        $apiName = $api_path.strtolower($apiName);
        //各 api的 参数
        $param_base = [
        	'method' => $apiName,
        	'app_key' => $this->AppKey,
        	'session' => $this->access_token,
        	'timestamp' => date("Y-m-d H:00:00", time()),
        	'format' => 'json',
        	'v' => '2.0',
        	'sign_method' => 'md5',
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

        $response = Helper_Curl::post($this->hosturl, $param_request);
        $result = $this->response($response, $apiName);
        
        return $result;
    }

    //上传图片专用的请求
    function request2($apiName, $param, $is_signature = 1, $api_path = '')
    {
       $apiName = $api_path.strtolower($apiName);
        //各 api的 参数
        $param_base = [
        	'method' => $apiName,
        	'app_key' => $this->AppKey,
        	'session' => $this->access_token,
        	'timestamp' => date("Y-m-d H:00:00", time()),
        	'format' => 'json',
        	'v' => '2.0',
        	'sign_method' => 'md5',
        ];
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }

        Helper_Array::removeEmpty($param);
        unset($param['image_bytes']);// 签名不包含sign 参数和bytes[]类型的数据
        
        $signature = $this->signature($this->appSecret, $param);
        if ($is_signature) {
            $param['sign'] = $signature;
        }
        $param_request = $param;
        $headers = [];
        $http_entity_type = 'application/x-www-from-urlencoded';
        $headers = array("Content-type: " . $http_entity_type);
        $response = Helper_Curl::post($this->hosturl, $param_request, $headers);
        $result = $this->response($response, $apiName);
        
        return $result;
    }

    /**
     * 该request3跟request主要差别在于调用Helper_Curl::post2
     */
    function request3($apiName, $param, $is_signature = 1)
    {
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));

        // 基本的 url
        $baseUrlParam = 'param2/1/aliexpress.open/' . 'api.' . $apiName . '/' . $this->AppKey;

        $baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');

        $signature = $this->signature2($baseUrlParam, $param);

        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $signature;
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;

        $d = Helper_Curl::post2($baseUrl, $param_request, null, false, null, true);
        $r = $this->response($d);
        return $r;
    }

    function response($data, $apiName){
        $r = json_decode($data, true);
        $result = $r;
        
        //推送接口踢掉前缀
        $apiName = str_replace("taobao.", "", $apiName);
        
        $key = str_replace(".", "_", $apiName)."_response";
        if(isset($r[$key]['result'])){
        	$result = $r[$key]['result'];
        }
        else if(isset($r[$key]['target'])){
        	$result = $r[$key]['target'];
        }
        else if(isset($r[$key]['results'])){
        	$result = $r[$key]['results'];
        }
        else if(isset($r[$key]['messages'])){
        	$result = $r[$key]['messages'];
        }
        else if(isset($r[$key])){
        	$result =  $r[$key];
        }
        
        //兼容旧模式参数
        if(!empty($result['error_response'])){
        	$result['success'] = false;
        	$result['result_success'] = false;
        	$result['error_code'] = empty($result['error_response']['sub_code']) ? $result['error_response']['code'] : $result['error_response']['sub_code'];
        	$result['error_message'] = empty($result['error_response']['sub_msg']) ? $result['error_response']['msg'] : $result['error_response']['sub_msg'];
        	$result['result_error_code'] = $result['error_code'];
        	$result['result_error_desc'] = $result['error_message'];
        	unset($result['error_response']);
        }
        else if(isset($result['result_success'])){
        	$result['success'] = $result['result_success'];
        	$result['error_code'] = empty($result['result_error_code']) ? 0 : $result['result_error_code'];
        	$result['error_message'] = empty($result['result_error_desc']) ? '' : $result['result_error_desc'];
        	$result['result_error_code'] = $result['error_code'];
        	$result['result_error_desc'] = $result['error_message'];
        }
        else if(isset($result['success'])){
        	$result['result_success'] = $result['success'];
        	$result['error_code'] = empty($result['error_code']) ? 0 : $result['error_code'];
        	$result['error_desc'] = empty($result['error_desc']) ? '' : $result['error_desc'];
        	$result['result_error_code'] = $result['error_code'];
        	$result['error_message'] = empty($result['error_message']) ? '' : $result['error_message'];
        	$result['result_error_desc'] = $result['error_desc'];
        }
        else if(isset($result['is_success'])){
        	$result['result_success'] = $result['is_success'];
        	$result['error_code'] = empty($result['sub_error_code']) ? 0 : $result['sub_error_code'];
        	$result['error_message'] = !empty($result['error_message']) ? $result['error_message'] : (empty($result['memo']) ? '' : $result['memo']);
        }
        //if(!in_array($apiName, ['getprintinfo', 'getpdfsbycloudprint'])){
        if(strpos($apiName, 'getprintinfo') === false && strpos($apiName, 'getpdfsbycloudprint') === false){
	        if(empty($result['error_desc'])){
	        	$result['error_desc'] = empty($result['error_message']) ? '' : $result['error_message'];
	        }
	        if(empty($result['error_message'])){
	        	$result['error_message'] = empty($result['error_desc']) ? '' : $result['error_desc'];
	        }
        }
        
        if(strpos($apiName, 'findorderlistquery') !== false){
            if(!empty($r['request_id'])){
            	$result['request_id'] = $r['request_id'];
            }
        }
        
        return $result;
    }

    /***
     * 基础的api请求方法
     */
    function request4($apiName,$param,$is_signature=1){
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));


        // 基本的 url
        $baseUrlParam='param2/1/aliexpress.open/'.$apiName.'/'.$this->AppKey;

        $baseUrl='http://'.$this->hosturl.$baseUrlParam;

        //各 api的 参数
        $param_base=array(
                'access_token'=>$this->access_token,
        );
        if(is_array($param)){
            $param=$param_base+$param;
        }else{
            $param=$param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str='';
        foreach($param as $k=>$v){
            $param_str.="&$k=".$v;
        }
        $param_str=ltrim($param_str,'&');

        $signature = $this->signature2($apiName, $param);

//       $signature = $this->signature($baseUrlParam,$param);
        $url=$baseUrl.'?'.$param_str.'&_aop_signature='.$signature;
        if($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request=$param;

        $d=Helper_Curl::post($baseUrl,$param_request);
        $r = $this->response($d);

        return $r;
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


    /***
 * 基础的api请求方法
 */
    function request5($apiName, $param, $is_signature = 1)
    {
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));


        // 基本的 url
        $baseUrlParam = 'param2/1/aliexpress.open/' . 'alibaba.ae.api.' . $apiName . '/' . $this->AppKey;

        $baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');

        $signature = $this->signature2($baseUrlParam, $param);

//       $signature = $this->signature($baseUrlParam,$param);
        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $signature;
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;

        $d = Helper_Curl::post($baseUrl, $param_request);
        $r = $this->response($d);
        /*
                if (isset($r['error_code']) && $r['error_message'] == 'Request need user authorized'){
                    //重新获取token
                    $auth_api = new AliexpressInterface_Auth();
                    $this->access_token = $auth_api->getAccessToken($this->selleruserid,TRUE);
                    $param['access_token'] = $this->access_token;
                        unset($param['_aop_signature']);
                    Helper_Array::removeEmpty($param);
                    $param_str='';
                    foreach($param as $k=>$v){
                        $param_str.="&$k=".$v;
                    }
                    $param_str=ltrim($param_str,'&');
                    $signature = $this->signature($baseUrlParam,$param);
                    $url=$baseUrl.'?'.$param_str.'&_aop_signature='.$signature;
                    if($is_signature)$param['_aop_signature'] = $signature;
                    $param_request=$param;
                    $d=Helper_Curl::post($baseUrl,$param_request);
                    $r = $this->response($d);
                }
        */
        return $r;
    }






    /***
     * 基础的api请求方法
     */
    function request6($apiName, $param, $is_signature = 1)
    {
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));


        // 基本的 url
        $baseUrlParam = 'param2/1/aliexpress.open/' . 'alibaba.ae.seller.' . $apiName . '/' . $this->AppKey;

        $baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');

        $signature = $this->signature2($baseUrlParam, $param);

//       $signature = $this->signature($baseUrlParam,$param);
        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $signature;
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;

        $d = Helper_Curl::post($baseUrl, $param_request);
        $r = $this->response($d);

        return $r;
    }
    
    /***
     * 基础的api请求方法
    */
    function request7($apiName, $param, $is_signature = 1)
    {
    	//统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));
    
    
    	// 基本的 url
    	$baseUrlParam = 'param2/1/aliexpress.open/' . 'alibaba.ae.logistics.' . $apiName . '/' . $this->AppKey;
    
    	$baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');

        $signature = $this->signature2($baseUrlParam, $param);

//       $signature = $this->signature($baseUrlParam,$param);
        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $signature;
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;

        $d = Helper_Curl::post($baseUrl, $param_request);
        $r = $this->response($d);
    
    	return $r;
    }
    
    /***
     * 基础的api请求方法，奇门
    */
    function qimen_request($apiName, $param, $is_signature = 1, $api_path = '')
    {
    	//统计api调用情况
    	RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
    	RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
    	//每小时统计次数信息
    	RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
    	RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));
    
    	$apiName = $api_path.strtolower($apiName);
    	//各 api的 参数
    	$param_base = [
	    	'method' => $apiName,
	    	'app_key' => $this->AppKey,
	    	//'session' => $this->access_token,
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
    	/*$param = [
    	 'method' => "aliexpress.logistics.redefining.getonlinelogisticsinfo",
    	'app_key' => "12345678",
    	'session' => "test",
    	'timestamp' => "2016-01-01 12:00:00",
    	'format' => "json",
    	'v' => "2.0",
    	'sign_method' => "md5",
    	'international_logistics_id' => "LP00038357949881 ",
    	'logistics_status' => "INIT",
    	];*/
    	print_r($param);die;
    	$signature = $this->signature($this->appSecret, $param);
    	if ($is_signature) {
    		$param['sign'] = $signature;
    	}
    	$param_request = $param;
    //print_r($param_request);die;
    	//$d = Helper_Curl::post($this->hosturl, $param_request);
    	print_r($param_request);die;
    	$requestUrl = 'http://qimen.api.taobao.com/router/qmtest?';
    	foreach ($param_request as $sysParamKey => $sysParamValue)
    	{
    		// if(strcmp($sysParamKey,"timestamp") != 0)
    		$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
    	}
    	
    	$requestUrl = substr($requestUrl, 0, -1);
    	//发起HTTP请求
    	try
    	{
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $requestUrl);
    		curl_setopt($ch, CURLOPT_FAILONERROR, false);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		/*if ($this->readTimeout) {
    			curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
    		}
    		if ($this->connectTimeout) {
    			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
    		}*/
    		//curl_setopt ( $ch, CURLOPT_USERAGENT, "top-sdk-php" );
    		//https 请求
    		if(strlen($requestUrl) > 5 && strtolower(substr($requestUrl,0,5)) == "https" ) {
    			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    		}
    		$d = curl_exec($ch);
    		
    		//$d = $this->curl($requestUrl, $apiParams);
    	}
    	catch (\Exception $e)
    	{
    	}
    	
    	
    	
    	
    	
    	
    	print_r($d);die;
    	$r = $this->response($d);
    
    	$key = str_replace(".", "_", $apiName)."_response";
    	if(isset($r[$key]['result'])){
    		return $r[$key]['result'];
    	}
    	else if(isset($r[$key])){
    		return $r[$key];
    	}
    	else if(isset($r[$key]['results'])){
    		return $r[$key]['results'];
    	}
    	else if(isset($r[$key])){
    		return $r[$key];
    	}
    
    	return $r;
    }

}
