<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use common\helpers\Helper_Curl;
use common\helpers\CurlExcpetion_Connection_Timeout;

/**
 * 获取SessionID和Token
 * @package interface.ebay.tradingapi
 */
class token extends base{
	function getSessionId(){
		$this->verb = 'GetSessionID';
		$xmlArr=array(
			'GetSessionIDRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>array(
				'Version'=>$this->config['compatabilityLevel'],
				'RuName'=>"<![CDATA[".$this->config['runame']."]]>",
			)
		);
		$requestArr=$this->sendHttpRequest($xmlArr);
		if($requestArr['Ack']=='Success'){
			$this->sessionid=$requestArr['SessionID'];
            \Yii::$app->session['ebay_sessionid']=$this->sessionid;
			return $this->sessionid;
		}else{
			return false;
		}
	}
	
	function getToken(){
        if(!isset(\Yii::$app->session['ebay_sessionid'])) {
            return false;
        }
		if(empty($this->sessionid)){
			$this->sessionid=$_SESSION['ebay_sessionid'];
		}
		$this->verb='FetchToken';
		$xmlArr=array(
			'FetchTokenRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>array(
				'Version'=>$this->config['compatabilityLevel'],
				'SessionID'=>"<![CDATA[".$this->sessionid."]]>",
			)
		);
		$requestArr=$this->sendHttpRequest($xmlArr);
		if($requestArr['Ack']=='Success'){
			$eBayAuthToken=$requestArr['eBayAuthToken'];
			$this->HardExpirationTime=$requestArr['HardExpirationTime'];
			return $eBayAuthToken;
		}else{
			return false;
		}
	}
	function getConfirmIdentity(){
        if(!isset($_SESSION['ebay_sessionid'])) {
            return false;
        }
		if(empty($this->sessionid)){
			$this->sessionid=$_SESSION['ebay_sessionid'];
		}
		$this->verb='ConfirmIdentity';
		$xmlArr=array(
			'ConfirmIdentityRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>array(
				'Version'=>$this->config['compatabilityLevel'],
				'SessionID'=>"<![CDATA[".$this->sessionid."]]>",
			)
		);
		$requestArr=$this->sendHttpRequest($xmlArr);

		if($requestArr['Ack']=='Success'){
			return $requestArr["UserID"];
		}else{
			return false;
		}
	}

    function sendHttpRequest($sendXmlArr,$isreturnXml=0,$timeout=40,$responseSaveToFileName=null){

        // 1,body 部分
        if(is_array($sendXmlArr)){
            $requestBody=self::simpleArr2xml($sendXmlArr);
        }elseif(is_string($sendXmlArr)){
            $requestBody=$sendXmlArr;
        }else{
            return false;
        }
        $this->_last_request_xml=$requestBody;

        $headers =$this->_last_request_header_array= array (
            'X-EBAY-API-COMPATIBILITY-LEVEL: ' .$this->config["compatabilityLevel"],
            'X-EBAY-API-DEV-NAME: ' .$this->config["devID"],
            'X-EBAY-API-APP-NAME: ' . $this->config["appID"],
            'X-EBAY-API-CERT-NAME: ' . $this->config["certID"],
            'X-EBAY-API-CALL-NAME: ' . $this->verb,
            'X-EBAY-API-SITEID: ' . $this->siteID,
            'Content-type: text/xml',
        );

        #not_really
        if ($this->_not_real_send){
            throw new EbayInterfaceException_NotRealSend($requestBody);
        }

        # 连接api 接口，超时或返回数据有问题重试2次
        $timeout_back=ini_get('max_execution_time');
        try {
            set_time_limit($timeout);
//             $response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
            $response = ebayapi_proxy::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
            set_time_limit($timeout_back);
        }catch (CurlExcpetion_Connection_Timeout $ex){
//             \Yii::log('EBAY API CONNECTED TIMEOUT');
        	\Yii::info('EBAY API CONNECTED TIMEOUT',"file");
        }
        if (is_null($responseSaveToFileName) && (!isset($response) || !simplexml_load_string($response))){
            try {
                set_time_limit($timeout);
//                 $response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
                $response = ebayapi_proxy::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
                set_time_limit($timeout_back);
            }catch (CurlExcpetion_Connection_Timeout $ex){
//                 Yii::log('EBAY API CONNECTED TIMEOUT');
            	\Yii::info('EBAY API CONNECTED TIMEOUT',"file");
            }
        }
        if (is_null($responseSaveToFileName) && (!isset($response) || !simplexml_load_string($response))){
            try {
                set_time_limit($timeout);
//                 $response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
                $response = ebayapi_proxy::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
                set_time_limit($timeout_back);
            }catch (CurlExcpetion_Connection_Timeout $ex){
//                 Yii::log('EBAY API CONNECTED TIMEOUT');
                \Yii::info('EBAY API CONNECTED TIMEOUT',"file");
                //throw new EbayInterfaceException_Connection_Timeout('EBAY API CONNECTED TIMEOUT');
            }
        }
        if (is_null($responseSaveToFileName) && (!isset($response) || !simplexml_load_string($response))){
        	\Yii::info('INVALID RETURN XML',"file");
//             Yii::log('INVALID RETURN XML');
            throw new EbayInterfaceException_InvalidReturnXml('INVALID RETURN XML');
        }

        #记录请求结果
//         Yii::log('ebay api response: ');
        \Yii::info('ebay api response: ',"file");

        //返回 数组
        if (!is_null($responseSaveToFileName)){
            return $response;
        }

        //返回 数组
        // resultXmlArray , xmlArray 格式的对象
        $this->_last_response_xmlarray=self::xmlparse($response,1);
        $this->_last_response_array=self::xmlparse($response);
        if($isreturnXml){
            return $response;
        }else{
            $response=self::xmlparse($response);
//             Yii::log(print_r($response,1));
            \Yii::info(print_r($response,1),"file");
            return $response;
        }
    }
}
?>