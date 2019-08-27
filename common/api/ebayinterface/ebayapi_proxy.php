<?php

namespace common\api\ebayinterface;

use Exception;
/**
 * curl请求助手
 * @package helper
 */
class ebayapi_proxy {
	static $connecttimeout=60;
	static $timeout=500;
	static $last_post_info=null;
	static $last_error =null;
	
	// TODO proxy host
	static $proxyurl = 'http://localhost/ebay_proxy_server/ebayproxyapi.php';
	static $shoppingproxyurl = 'http://localhost/ebay_proxy_server/ebayproxyshoppingapi.php';

	
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
		$content+=$requestHeader;
		$content['realurl']=$url;
		$content['content']=$requestBody;
		$content = self::arrtocurlstr($content);
// 		curl_setopt($connection, CURLOPT_URL,$url);
		curl_setopt($connection, CURLOPT_URL,self::$proxyurl);
// 		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
// 		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
// 		if (!is_null($requestHeader)){
// 			curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
// 		}
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
		    throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url.'DATA:'.$requestBody);
		}
		return $response;
	}
	
	/**
	 * 发起请求
	 * @param string $proxyurl 代理服务器的呼叫入口地址,服务于shipping接口调用
	 * @param string $url 真正呼叫的地址
	 * @param string $requestBody
	 * @param string $requestHeader
	 * @param bool $justInit	是否只是初始化，用于并发请求
	 * @param string $responseSaveToFileName	结果保存到文件，函数只返回true|false
	 * @return bool|string
	 * @author fanjs
	 */
	static function shoppingpost($url){
		$connection = curl_init();
	
		$content['realurl']=$url;
		curl_setopt($connection, CURLOPT_URL,self::$shoppingproxyurl);
		curl_setopt($connection, CURLOPT_POST, 1);
		curl_setopt($connection, CURLOPT_POSTFIELDS, $content);
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($connection, CURLOPT_CONNECTTIMEOUT,self::$connecttimeout);
		curl_setopt($connection, CURLOPT_TIMEOUT,self::$timeout);
	
		$response = curl_exec($connection);
		$error=curl_error($connection);
		curl_close($connection);
		if ($error){
			throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url.'DATA:');
		}
		return $response;
	}

	/**
	 * 发起请求
	 *
	 * @param string $url
	 * @param string $requestBody
	 * @param string $requestHeader
	 * @param bool $justInit	是否只是初始化，用于并发请求
	 * @param string $responseSaveToFileName	结果保存到文件，函数只返回true|false
	 * @return bool|string
	 */
	static function get($url,$requestBody=null,$requestHeader=null,$justInit=false,$responseSaveToFileName=null,$http_version=null){
		$connection = curl_init();
		
		curl_setopt($connection, CURLOPT_URL,$url);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		if (!is_null($requestHeader)){
			curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
		}
		if (!is_null($http_version)){
			curl_setopt($connection, CURLOPT_HTTP_VERSION, $http_version);
		}
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
		    throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url.'DATA:'.$requestBody);
		}
		return $response;
	}
	
	static function delete($url,$requestBody=null,$requestHeader=null){
		$curl_handle = curl_init ();
// 		var_dump($url);die;
		// Set default options.
		curl_setopt ( $curl_handle, CURLOPT_URL, $url);
		curl_setopt ( $curl_handle, CURLOPT_FILETIME, true );
		curl_setopt ( $curl_handle, CURLOPT_FRESH_CONNECT, false );
		if (!is_null($requestHeader)){
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $requestHeader);
		}
		curl_setopt ( $curl_handle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $curl_handle, CURLOPT_CONNECTTIMEOUT,self::$connecttimeout);
		curl_setopt ( $curl_handle, CURLOPT_TIMEOUT,self::$timeout);
		curl_setopt ( $curl_handle, CURLOPT_NOSIGNAL, true );
		curl_setopt ( $curl_handle, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		
		$response = curl_exec ( $curl_handle );
		$error=curl_error($curl_handle);
		curl_close($curl_handle);
		if ($error){
			throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url.'DATA:'.$requestBody);
		}
		return $response;
	}

	static function multiPost($curlHandles){
		self::$last_error=array();
		self::$last_post_info=array();
		$mh=curl_multi_init();
		foreach ($curlHandles as $ch){
			curl_multi_add_handle($mh,$ch);
		}
		$still_running=1;
		do {
			usleep(500);
			curl_multi_exec($mh,$still_running);
		}while ($still_running > 0);
		$results=array();
		foreach ($curlHandles as $id=> $ch){
			$results[$id]=curl_multi_getcontent($ch);
			self::$last_post_info[$id]=curl_getinfo($ch);
			self::$last_error[$id]=curl_error($ch);
			curl_multi_remove_handle($mh,$ch);
		}
		curl_multi_close($mh);
		return $results;
	}
	static function downloadFile($remote, $local, $timeout=10) {
		$cp = curl_init($remote);
		$fp = fopen($local, "w");
		
		curl_setopt($cp, CURLOPT_CONNECTTIMEOUT,$timeout);
		curl_setopt($cp, CURLOPT_TIMEOUT,3600);
		curl_setopt($cp, CURLOPT_FILE, $fp);
		curl_setopt($cp, CURLOPT_HEADER, 0);
		
		$r=curl_exec($cp);
		curl_close($cp);
		fclose($fp);
		return $r;
	}
	
	static public function arrtocurlstr($request){
		if (is_array($request))
		{
			$sets = array();
			foreach ($request AS $key => $val)
			{
				$sets[] = $key . '=' . urlencode($val);
			}
			$requeststr = implode('&',$sets);
		}
		return $requeststr;
	}
}
class CurlExcpetion_Connection_Timeout extends Exception {} 