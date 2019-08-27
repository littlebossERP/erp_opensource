<?php
namespace common\api\paypalinterface;

class PaypalInterface_Abstract {
	//public $environment='sandbox';
	public $verb;
	public $api_username;
	public $api_password;
	public $api_signature;
	public $production=0;


	/**
	 * 最后一次请求xml
	 *
	 * @var string
	 */
	public $_last_request;
	/**
	 * 最后一次返回xml
	 *
	 * @var string
	 */
	public $_last_response;
	/**
	 * 用户的 paypal
	 */
	public $paypalEmail;
	function __construct($paypalEmail=null){
		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production'){
			$this->production = 1;
		}else{
			$this->production = 0;
		}
		$this->loadconfig($paypalEmail);
		
	}
	/**
	 * config
	 * TODO paypal dev account @XXX@
	 *
	 */
	function loadconfig($paypalEmail=null){
		//ebayInterface_Config::$production=false;
		if($this->production){
			$this->API_Endpoint='https://api-3t.paypal.com/nvp';
			
			$this->api_username='@XXX@';
			$this->api_password='@XXX@';
			$this->api_signature='@XXX@';
		}else{
			$this->API_Endpoint='https://api-3t.sandbox.paypal.com/nvp';
			$this->api_username='@XXX@';
			$this->api_password='@XXX@';
			$this->api_signature='@XXX@';
		}
		if($paypalEmail){
			$this->paypalEmail=$paypalEmail;
		}
	}
	
	
	function doNvpRequest($nvpArr){
		if (is_null($this->verb)){
			exit('$this->verb 指定请求方法');
		}
		$nvpStr_=null;
		foreach ($nvpArr as $k => $v){
			$nvpStr_.='&'.$k.'='.$v;
		}
		
		$methodName_=$this->verb;
		// Set up your API credentials, PayPal end point, and API version.
		$API_UserName = urlencode($this->api_username);
		$API_Password = urlencode($this->api_password);
		$API_Signature = urlencode($this->api_signature);
		//$API_Endpoint = "https://api-3t.paypal.com/nvp";
		$API_Endpoint=$this->API_Endpoint;
		$version = urlencode('51.0');
		
		
		// Set the API operation, version, and API signature in the request.
		$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
		if($this->paypalEmail){
			$nvpreq.='&SUBJECT='.urlencode($this->paypalEmail);
		}
		
		
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT , 15);
	
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		
		// Set the request as a POST FIELD for curl.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
	
		// Get response from the server.
		$httpResponse = curl_exec($ch);
		$this->_last_request=$nvpreq;
		if(!$httpResponse) {
            if(curl_errno($ch) == 28){echo 28;exit;}
			throw new \Exception("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
		}
		$this->_last_response=$httpResponse;
		
		// Extract the response details.
		$httpResponseAr = explode("&", $httpResponse);
	
		$httpParsedResponseAr = array();
		foreach ($httpResponseAr as $i => $value) {
			list($k,$v) = explode("=", $value);
			$httpParsedResponseAr[$k] = urldecode($v);
		}
	
		if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
			throw new \Exception("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
		}
		
		// 组织数据结构
		if(is_array($httpParsedResponseAr)){
			$r_arr=array();
			foreach($httpParsedResponseAr as $k=>$v){
				$i=preg_match('/L_([A-Z]+)(\d+)/',$k,$m);
				if($i){
					$k1=$m[1];
					$k2=$m[2];
					$r_arr['detail'][$k2][$k1]=$v;
				}else{
					$r_arr[$k]=$v;
				}
			}
			return $r_arr;
		}
		
		return $httpParsedResponseAr;
	}
	function isodate($timestamp){
		return gmdate('Y-m-d\TH:i:s.Z\Z',$timestamp);
	}
	
	
}
