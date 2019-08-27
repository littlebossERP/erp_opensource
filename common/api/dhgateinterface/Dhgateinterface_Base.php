<?php 
namespace common\api\dhgateinterface;
use \Yii;

class Dhgateinterface_Base{
	public $access_token=''; // 访问 token .
	
	function __construct(){
		$this->_config();
	}

	/**
	* 配置  
	*/ 
	function _config(){
	    // TODO dhgate dev account @XXX@
		$this->appKey= '@XXX@';
		$this->appSecret= '@XXX@';
		$this->authUrl = 'secure.dhgate.com/dop/oauth2';
		$this->apiUrl = 'api.dhgate.com/dop/router';
	}

	/***
	 * 基础的api请求方法
	*/
	function call_dh_api($url , $getParams=array() , $postParams=array() , $auth = false ){
		$port = null;
		$scheme = "";
		if($auth){
			$scheme = 'https://';
			$port = 443;
		} else {
			$scheme = 'http://';
			$port = 80;
		}
	
		$handle = curl_init();
	
		$getKeyValue = array();
		foreach($getParams as $key=>$value){
			$getKeyValue[] =  "$key=".urlencode(trim($value));
		}
		$url .= '?'.implode("&", $getKeyValue);
	
		if(!empty($postParams)){
			curl_setopt($handle, CURLOPT_POST, 1);
			curl_setopt($handle, CURLOPT_POSTFIELDS, $postParams );
		}
	
		if($auth){
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
		}
	
		// 		echo $scheme . $url;
		curl_setopt($handle, CURLOPT_URL, $scheme . $url);
		curl_setopt($handle, CURLOPT_PORT, $port);
		curl_setopt($handle, CURLOPT_HEADER, false);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($handle,  CURLOPT_TIMEOUT, 700);
	
		//  output  header information
		// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
			
		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($handle);
		
		// 		var_dump($response);
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		
		$curl_errno = curl_errno($handle);
		$curl_error = curl_error($handle);
		
		if ($curl_errno > 0) { // network error
			\Yii::error("cURL Error $curl_errno : $curl_error","file");
			curl_close($handle);
			return false;
		}
		
		$rtn = null;
		if ($httpCode <> '200' ){			
			\Yii::error("  Got error respond code $httpCode from Proxy", "file");
			curl_close($handle);
			return false;
		}
		
		$rtn = json_decode($response , true);
		curl_close($handle);
		
		
		return $rtn;
	}
	

	function recordApiCall($data){
		//记录请求次数
		$date = date("YmdH",time());
		$counter = Counter::findOne(['id'=>$date]);
		if ($counter===NULL){
		$counter = new Counter();
		}
		$counter->id = $date;
		$counter->times = $counter->times+1;
		$counter->save();
		$arr = json_decode($data,true);
		return $arr;
	}



}