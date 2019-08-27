<?php
namespace common\api\ebayinterface\product;

use common\api\ebayinterface\config;
use common\api\ebayinterface\ebayapi_proxy;
use common\api\ebayinterface\CurlExcpetion_Connection_Timeout;
use common\helpers\Helper_xml;
use common\helpers\Helper_Filesys;
use common\helpers\Helper_Curl;
class base 
{
	/**
	 * 用户 token
	 *
	 * @var long string
	 */
	public $eBayAuthToken; 
	/**
	 * API请求类型名 
	 *
	 * @var string
	 */
	public $verb;
	public $siteID;
	public $site;
	public $_last_request_header_array;	
	/**
	 * 最后一次请求结果
	 *
	 * @var unknown_type
	 */
	public $_last_response_array;
	/**
	 * 是否生产模式 ebayInterface_Config::$production
	 *
	 * @var bool  
	 */
	public $production;
	function __construct(){
		$this->production=config::$production;
		$this->_loadconfig();
	}
	function _loadconfig(){
	    // these keys can be obtained by registering at http://developer.ebay.com	    
        $this->config["compatabilityLevel"] = '1.3.0';    // eBay API version
	    $this->config['request_xmlprops']='xmlns="http://www.ebay.com/marketplace/marketplacecatalog/v1/services"';
	    if ($this->production) {
	        //set the Server to use (Sandbox or Production)
	        $this->config["serverUrl"] = 'http://svcs.ebay.com/services/marketplacecatalog/ProductMetadataService/v1';      // server URL different for prod and sandbox
			$this->config["host"]='svcs.ebay.com';
			$this->config["port"]='443';
			$this->config["path"]='/services/marketplacecatalog/ProductMetadataService/v1';
			$this->config['appID']='goomax298-13f5-4fa4-b4d5-d6b54481d4a';
		} else {
	        // sandbox (test) environment
	        $this->config["serverUrl"] = 'http://svcs.sandbox.ebay.com/services/marketplacecatalog/ProductMetadataService/v1';
			$this->config["host"]='svcs.sandbox.ebay.com';
			$this->config["port"]='443';
			$this->config["path"]='/services/marketplacecatalog/ProductMetadataService/v1'; 
			$this->config['appID']='goomax32b-e90a-4af5-a2da-c983219363c';              
	    }
	}
	    
	/***
	 * 发送类 
	 */
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
		$headers =$this->_last_request_header_array = array (
			'X-EBAY-SOA-SERVICE-VERSION: ' .$this->config["compatabilityLevel"],
//			'X-EBAY-SOA-SECURITY-TOKEN: ' . $this->eBayAuthToken,
			'X-EBAY-SOA-SECURITY-APPNAME: ' .$this->config['appID'],
			'X-EBAY-SOA-OPERATION-NAME: ' . $this->verb,		
//			'X-EBAY-SOA-SERVICE-NAME:ResolutionCaseManagementService',
			'X-EBAY-SOA-REQUEST-DATA-FORMAT: XML',
			'X-EBAY-SOA-GLOBAL-ID: ' . $this->site,
//			'Content-type: text/xml',
		);
				
		try {
			set_time_limit($timeout);
			//$response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
			//使用post
			$response = ebayapi_proxy::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
		}catch (CurlExcpetion_Connection_Timeout $ex){
			\Yii::info('EBAY API CONNECTED TIMEOUT');
		}
		
		if (is_null($responseSaveToFileName) && (!isset($response) || !simplexml_load_string($response))){
			try {
				set_time_limit($timeout);
				//$response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
				$response = ebayapi_proxy::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
			}catch (CurlExcpetion_Connection_Timeout $ex){
				\Yii::info('EBAY API CONNECTED TIMEOUT');
			}
		}
		if (is_null($responseSaveToFileName) && (!isset($response) || !simplexml_load_string($response))){
			try {
				set_time_limit($timeout);
				//$response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
				$response = ebayapi_proxy::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
			}catch (CurlExcpetion_Connection_Timeout $ex){
				\Yii::info('EBAY API CONNECTED TIMEOUT');
				throw new \Exception('EBAY API CONNECTED TIMEOUT');
			}
		}
		if (is_null($responseSaveToFileName) && (!isset($response) || !simplexml_load_string($response))){
			\Yii::info('INVALID RETURN XML');
			throw new \Exception('INVALID RETURN XML');
		}
		
		#记录请求结果
		\Yii::info($response);
		
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
			return self::xmlparse($response);
		}
		
		
	}
	/**
	 * 从数组生成 xml
	 *  @ $arr 是数组与 simplexml 混合 
	 *  以3个空格 区分 属性	 
	 */
	static function simpleArr2xml($arr,$header=1){
		if($header){
			$str='<?xml version="1.0" encoding="utf-8" ?>';
		}else{
			$str='';
		}
		if(is_array($arr)){
			$str.="\r\n";
			foreach($arr as $k=>$v){
				$n=$k;
				if(($b=strpos($k,' '))>0){
					$f=substr($k,0,$b);
				}else{
					$f=$k;
				}
				if(is_array($v)&&is_numeric(implode('',array_keys($v)))){ 
				// 就是为 Array 为适应 Xml 的可以同时有多个键 所做的 变通
						foreach($v as $cv){
							$str.="<$n>".self::simpleArr2xml($cv,0)."</$f>\r\n";
						}
				}elseif ($v instanceof \SimpleXMLElement){
				    $xml = $v->asXML();/*<?xml version="1.0"?>*/
				    $xml =preg_replace('/\<\?xml(.*?)\?\>/is','',$xml);
				    $str.=$xml;
				}else{
					$str.="<$n>".self::simpleArr2xml($v,0)."</$f>\r\n";
				}
			}
		}else{
			$str.=$arr;
		}
		return $str;
	}
	
	/****
	 * 用 simplexml_load_string 解晰 xml 字符串
	 * 变为 数组	 
	 */
	static function xmlparse($xmlString,$notUseArray=false){
		$XA=Helper_xml::xmlparse($xmlString);
		if(Helper_xml::isArray($XA)&&(!$notUseArray)) return $XA->toArray();
		return $XA;
	}

	/**
	 * 设置请求方法
	 *
	 * @param string $method 方法
	 * @return EbayInterface_base
	 */
	function setRequestMethod($method){
	    $this->verb=$method;
	    $this->config['requestMethod']=$method.'Request';
	    return $this;
	}
	
	/**
	 * 设置请求内容
	 *
	 * @param mixed $dataArr 内容数组
	 * @return EbayInterface_base
	 */
	function setRequestBody($dataArr){
	    $this->config['requestBody']=$dataArr;
	    return $this;
	}
	
	/**
     * 生成时间串
     * 32 位
     */
    static function dateTime($timestamp=null){
        return gmdate('Y-m-d\TH:i:s.uP',$timestamp);
    }
    
/**
	 * 缓存函数
	 *
	 * 
	 * @param string $cacheid
	 * @param xmlstring $data 要写入缓存的数据
	 * @return unknown
	 */
	function xmlCache($cacheid,$data = null){
	    $xmlDir=dirname(__FILE__).'/xmlcache/'.strtolower($this->verb).'/'.$this->siteID.'/';
	    $xmlFile=$xmlDir.$cacheid.'.xml';
	    if (!is_dir($xmlDir)){
	        Helper_Filesys::mkdirs($xmlDir);
	    }
	    if (is_null($data)) {
    	    if (is_file($xmlFile) && filemtime($xmlFile) > CURRENT_TIMESTAMP-30*ONEDAY){
    	        return file_get_contents($xmlFile);
    	    }
    	    return false;
	    }else {
	        return file_put_contents($xmlFile,$data);
	    }
	}
}
