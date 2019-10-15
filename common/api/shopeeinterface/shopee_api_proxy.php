<?php

namespace common\api\shopeeinterface;

use Exception;
/**
 * curl请求助手
 * @package helper
 */
class shopee_api_proxy {
	static $connecttimeout=60;
	static $timeout=500;
	static $last_post_info=null;
	static $last_error =null;
	
	// TODO proxy host 
	static $proxyurl = 'http://localhost/shopee_proxy_server/shopeeproxyapi.php';
	
	
	/**
	 * 发起请求
	 * @param string $proxyurl 代理服务器的呼叫入口地址
	 * @param string $url 真正呼叫的地址
	 * @param string $requestBody
	 * @param string $requestHeader
	 * @param bool $justInit	是否只是初始化，用于并发请求
	 * @param string $responseSaveToFileName	结果保存到文件，函数只返回true|false
	 * @return bool|string
	 * @author fanjs
	 */
	static function post($url,$requestBody=null,$requestHeader=null,$justInit=false,$responseSaveToFileName=null){
		$connection = curl_init();
		
		$content=[];
		if(!empty($requestHeader))
            $content = array_merge($content, $requestHeader);
		
		$content['realurl'] = $url;
		$content['content'] = $requestBody;
		curl_setopt($connection, CURLOPT_URL, self::$proxyurl);
		
		curl_setopt($connection, CURLOPT_POST, 1);
		curl_setopt($connection, CURLOPT_POSTFIELDS, $content);
		if (!is_null($responseSaveToFileName)){
			$fp=fopen($responseSaveToFileName,'w');
			curl_setopt($connection, CURLOPT_FILE, $fp);
		}else {
			curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		}
		curl_setopt($connection, CURLOPT_CONNECTTIMEOUT,self::$connecttimeout);
		curl_setopt($connection, CURLOPT_TIMEOUT,self::$timeout);
		if ($justInit){
			return $connection;
		}
		
		$response = curl_exec($connection);
		
		self::$last_post_info=curl_getinfo($connection);
		$error=curl_error($connection);
		curl_close($connection);
		if (!is_null($responseSaveToFileName)){
			fclose($fp);
		}
		if ($error){
		    throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error, true)).'URL:'.$url.'DATA:'.print_r($requestBody, true));
		}
		return $response;
	}
	
}
class CurlExcpetion_Connection_Timeout extends Exception {} 