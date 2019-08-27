<?php
namespace common\api\ebayinterface;

use Exception;
use common\api\ebayinterface\config;
use common\helpers\Helper_Curl;
use common\helpers\Helper_xml;
use common\helpers\Helper_Filesys;
use SimpleXMLElement;
use common\api\ebayinterface\ebayapi_proxy;
/**
 * eBay接口基础模型
 * @package interface.ebay.tradingapi
 *
 */
class base 
{
	/**
	 * 常规接口调用用户设定
	 * TODO ebay user account @XXX@
	 */
	const DEFAULT_REQUEST_USER= 'testuser_hsseller1';
 
	/**
	 * 用户 token
	 *
	 * @var long string
	 */
	public $eBayAuthToken; 
	/**
	 * ebay 平台ID
	 *
	 * @var int
	 */
	public $siteID=0;
	/**
	 * API请求类型名 
	 *
	 * @var string
	 */
	public $verb;
	/**
	 * 接口连通开关
	 *
	 * @var bool
	 */
	public $_not_real_send=false;
	/**
	 * 最后一次请求xml数组
	 *
	 * @var array
	 */
	public $_last_request_header_array;
	/**
	 * 最后一次请求xml
	 *
	 * @var string
	 */
	public $_last_request_xml;
	/**
	 * 最后一次请求响应结果数组
	 *
	 * @var array
	 */
	public $_last_response_array;
	/**
	 * 最后一次请求相应结构xml
	 *
	 * @var string
	 */
	public $_last_response_xmlarray;
	/**
	 * 是否生产模式 
	 *
	 * @var bool  
	 */
	public $production;
	
	/**
	 * 映射开发者账号的关键字
	 *
	 * @var string
	 */
	public $devAccountID;
	
	/**
	 * 映射开发者账号的关键字
	 *
	 * @var string
	 */
	public $sellerUserID;
	/**
	 * 生成请求的数据组
	 * 并不定要求 与 请求的格式完全一致,而只是 在内部或外部程序中对 数据的组织用。 
     */
	public $_before_request_xmlarray;
	function __construct(){
		/*
		$this->production=config::$production;
		$this->_loadconfig();
		*/
	}
	/**
	 * 根据不同的情景载入设置
	 *
	 */
	function _loadconfig(){
	    // these keys can be obtained by registering at http://developer.ebay.com	    
        $this->config["compatabilityLevel"] = 767;    // eBay API version
	    $this->config['request_xmlprops']='xmlns="urn:ebay:apis:eBLBaseComponents"';
	    $this->config+=config::getConfig(null);
	}
	
	/**
	 *  重置config 专用的根据devAccountID读取config
	 *
	 */
	function _loadconfigByDevAccountID($devAccountID){
		// these keys can be obtained by registering at http://developer.ebay.com
		if (empty($devAccountID)){
		    self::_loadconfig();
		}else{
		    $this->config["compatabilityLevel"] = 767;    // eBay API version
		    $this->config['request_xmlprops']='xmlns="urn:ebay:apis:eBLBaseComponents"';
		    $this->config+=config::getConfigByDevAccountID(null,$devAccountID);
		}
		
	}
	
	/**
	 *  重置config
	 *
	 */
	function resetConfig($devAccountID =null){
		$this->production=config::$production;
		$this->_loadconfigByDevAccountID($devAccountID);
		$this->devAccountID = $devAccountID;
	}
	/**
	 * 发送类 
	 * 	 $sendXmlArr : 数据 数组 ,会被组织成 Xml 
	 * 	 	如果 本来就是 字符串的 ,就认为本来就是  Xml 	 
	 * 	 $returnXml : 返回 值是 数组 或 原生 xml .默认 是 数组 
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
		
	//判断代理服务器的链接
// 		$array = get_headers(ebayapi_proxy::$proxyurl,1);
		
		# 连接api 接口，超时或返回数据有问题重试2次
		$timeout_back=ini_get('max_execution_time');
		try {
			set_time_limit($timeout);
			
// 			if (preg_match('/200/',$array[0])){
				$response = ebayapi_proxy::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
// 			}else{
// 				$response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
// 			}
			set_time_limit($timeout_back);
		}catch (CurlExcpetion_Connection_Timeout $ex){
			\Yii::info('EBAY API CONNECTED TIMEOUT');
		}
		$isXML=$this->is_xml($response);
		if (!$isXML) {
			\Yii::info("XML not format1:".print_r($response,1),"ebayapi");
		}
		if (is_null($responseSaveToFileName) && (!isset($response) ||(!$isXML) || ( $isXML && !simplexml_load_string($response)))){
			try {
				set_time_limit($timeout);
// 				if (preg_match('/200/',$array[0])){
					$response = ebayapi_proxy::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
// 				}else{
// 					$response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
// 				}
				set_time_limit($timeout_back);
			}catch (CurlExcpetion_Connection_Timeout $ex){
				\Yii::info('EBAY API CONNECTED TIMEOUT');
			}
		}
		$isXML=$this->is_xml($response);
		if (!$isXML) {
			\Yii::info("XML not format2:".print_r($response,1),"ebayapi");
		}
		if (is_null($responseSaveToFileName) && (!isset($response) ||(!$isXML) || ( $isXML && !simplexml_load_string($response)))){
			try {
				set_time_limit($timeout);
// 				if (preg_match('/200/',$array[0])){
					$response = ebayapi_proxy::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
// 				}else{
// 					$response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers,false,$responseSaveToFileName);
// 				}
				set_time_limit($timeout_back);
			}catch (CurlExcpetion_Connection_Timeout $ex){
				\Yii::info('EBAY API CONNECTED TIMEOUT');
				throw new EbayInterfaceException_Connection_Timeout('EBAY API CONNECTED TIMEOUT');
			}
		}
		$isXML=$this->is_xml($response);
		if (!$isXML) {
			\Yii::info("XML not format3:".print_r($response,1),"ebayapi");
		}
		if (is_null($responseSaveToFileName) && (!isset($response)||(!$isXML) || ( $isXML && !simplexml_load_string($response)))){
			\Yii::info('INVALID RETURN XML');
			throw new EbayInterfaceException_InvalidReturnXml('INVALID RETURN XML');
		}
		
		#记录请求结果
		\Yii::info('ebay api response: ');
		
		$FinalValueFeeSignString = 'FinalValueFee currencyID="';
		$FinalValueFeeSignPOS = strpos($response,$FinalValueFeeSignString);
		$FinalValueFeeSignPOS += strlen($FinalValueFeeSignString);
		$FinalValueFeeCurrency = substr($response,$FinalValueFeeSignPOS,3);
		$FeeOrCreditAmountSignString = 'FeeOrCreditAmount currencyID="';
		$FeeOrCreditAmountPOS = strpos($response,$FeeOrCreditAmountSignString);
		$FeeOrCreditAmountPOS += strlen($FeeOrCreditAmountSignString);
		$FeeOrCreditAmountCurrency = substr($response,$FeeOrCreditAmountPOS,3);
		
		//返回 数组
		if (!is_null($responseSaveToFileName)){
			return $response;
		}

		//返回 数组
		// resultXmlArray , xmlArray 格式的对象 
		$this->_last_response_xmlarray=self::xmlparse($response,1);
		$this->_last_response_array=self::xmlparse($response);
		//add willage 20170122,用于记录执行记录
		$logRecord="[base_data] siteID: ".@$this->siteID.", devAccountID: ".@$this->devAccountID." api:".@$this->verb." [Ack]: ".@$this->_last_response_array['Ack'].", [Errors]: ".print_r(@$this->_last_response_array['Errors'],1);
		\Yii::info(print_r($logRecord,1),"ebayapi");
		if($isreturnXml){
			return $response;
		}else{
		    $response=self::xmlparse($response);
		    if (isset($response['OrderArray']['Order']['TransactionArray']['Transaction']['FinalValueFee'])){
		    	$response['OrderArray']['Order']['TransactionArray']['Transaction']['FinalValueFeeCurrency'] = $FinalValueFeeCurrency;
		    	$this->_last_response_xmlarray['OrderArray']['Order']['TransactionArray']['Transaction']['FinalValueFeeCurrency'] = $FinalValueFeeCurrency;
		    }
		    
		    if (isset($response['OrderArray']['Order']['ExternalTransaction']['FeeOrCreditAmount'])){
		    	$response['OrderArray']['Order']['ExternalTransaction']['FeeOrCreditAmountCurrency'] = $FeeOrCreditAmountCurrency;
		    	$this->_last_response_xmlarray['OrderArray']['Order']['ExternalTransaction']['FeeOrCreditAmountCurrency'] = $FeeOrCreditAmountCurrency;
		    }
		    
		    //\Yii::info(print_r($response,1)); //kh20160809 文件log太食资源暂时屏蔽
			return $response;
		}
	}
	
	/**
	 * 用 simplexml_load_string 解晰 xml 字符串
	 * 变为 数组	 
	 */
	static function xmlparse($xmlString,$notUseArray=false){
		set_time_limit(0);
		$XA=Helper_xml::xmlparse($xmlString);
		if(Helper_xml::isArray($XA)&&(!$notUseArray)) return $XA->toArray();
		return $XA;
	}

	static function xmlParseWithAttr($xmlString,$notUseArray=false){
		set_time_limit(0);
		// $XA=Helper_xml::xmlparse($xmlString);
		$xmlString =Helper_xml::simplexml_load_string($xmlString);
		$XA=Helper_xml::xmlToArr($xmlString,false);
		// print_r($XA,false);
		if(Helper_xml::isArray($XA)&&(!$notUseArray)) return $XA->toArray();
		return $XA;
	}
	/**
	 * 递归 运算, 解析 xml simplexml 对象
	 *
	 */
	static function simplexml2a($o,$notUseArray=false){
		$XA=Helper_xml::simplexml2XA($o);
        if(Helper_xml::isArray($XA)&&(!$notUseArray)) return $XA->toArray();
        return $XA;
	}
	/**
	 * 从数组生成 xml
	 *  @ $arr 是数组与 simplexml 混合 
	 *  以3个空格 区分 属性	 
	 *  $xmlstr=self::simpleArr2xml(
    array(
    	//' '后 设置 GetItemTransactionsRequest 的属性,因为 数组键名,让人吃惊的强壮
		'GetItemTransactionsRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>array( 
			'IncludeContainingOrder'=>'true',
			'ItemID testK="testV"' =>array( // 设置 ItemId
				$itemid,
			),
			'ListingEnhancement'=>array( // 转成 Xml 时 , 
				'Border',
				'CustomCode',
				'Featured',
				'Highlight'
			),
		)
	));
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
				}elseif ($v instanceof SimpleXMLElement ){
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
	
	/**
	 * 对Dom document 进行解晰 . 
	 * 接收 	 $DOMNodeList
	 * 如:	 
	 * $response = $DOM -> getElementsByTagName('GetItemResponse');
	 * 	 
	 */ 
	static function domxml2a($DOMNodeList,$notUseArray=false){
		$XA=Helper_xml::domxml2XA($DOMNodeList);
        if(Helper_xml::isArray($XA)&&(!$notUseArray)) return $XA->toArray();
        return $XA;
	}
	static function dateTime($timestamp=null){
		return gmdate('Y-m-d\TH:i:s.Z\Z',$timestamp);
	}
	/**
	 * 设置请求方法
	 *
	 * @param string $method 方法，例如 AddItem
	 * @return EbayInterface_base
	 */
	function setRequestMethod($method){
	    $this->verb=$method;
	    $this->config['requestMethod']=$method.'Request';
	    return $this;
	}
	/**
	 * 设置请求方法附加属性
	 *
	 * @param array request_xmlprops 属性数组
	 * @return EbayInterface_base
	 */
	function setRequestXmlns($request_xmlprops){
	    $this->config['request_xmlprops']=$request_xmlprops;
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
	 * 发送请求
	 *
	 * @param boolean $returnXml 是否返回源xml，默认返回数组
	 * @return array|xml
	 */
	function sendRequest($returnXml=0,$timeout=40,$responseSaveToFileName=null){
	    if (empty($this->config['requestMethod'])){
	        $this->setRequestMethod($this->verb);
	    }
	    if (empty($this->config['requestMethod']) || !isset($this->config['requestBody'])){
	        throw new EbayInterfaceException_RequestFormatInvalidate(__t('请确认已经设置requestMethod 和requestBody'));
	    }
	    $xmlArr=array(
	        $this->config['requestMethod'].' '.$this->config['request_xmlprops']=>array(
	            'RequesterCredentials'=>array(
					'eBayAuthToken'=>$this->eBayAuthToken,
				),
	        )
	    );
	    if ($this->config['requestBody'] instanceof SimpleXMLElement ){
	        $xmlArr['requestXML']=$this->config['requestBody'];
	    }else{
    	    foreach ($this->config['requestBody'] as $domKey => $domValue){
    	        $xmlArr[$this->config['requestMethod'].' '.$this->config['request_xmlprops']][$domKey]=$domValue;
    	    }
	    }
	    \Yii::info(print_r($xmlArr,1));

	    return $this->sendHttpRequest($xmlArr,$returnXml,$timeout,$responseSaveToFileName);
	}
	/**
	 * 最后一次接口请求得到的回复类型是 Warming的
	 *
	 * @param string $responseArr 设置分析数组
	 * @return bool
	 */
	function responseIsWarming($responseArr=null){
	    if (is_null($responseArr)){
	        $responseArr=$this->_last_response_array;
	    }
	    return $responseArr['Ack'] =='Warming';
	}
	/**
	 * 最后一次接口请求得到的回复类型是 Failure的
	 *
	 * @param string $responseArr 设置分析数组
	 * @return bool
	 */
	function responseIsFailure($responseArr=null){
	    if (is_null($responseArr)){
	        $responseArr=$this->_last_response_array;
	    }
	    return $responseArr['Ack'] =='Failure';
	}
	/**
	 * 最后一次接口请求得到的回复类型是 Success的
	 *
	 * @param string $responseArr 设置分析数组
	 * @return bool
	 */
	function responseIsSuccess($responseArr=null){
	    if (is_null($responseArr)){
	        $responseArr=$this->_last_response_array;
	    }
	    return $responseArr['Ack'] =='Success';
	}
	function responseErrorMsg(){
	    $errors=$this->_last_response_array['Errors'];
	    if (isset($errors['LongMessage'])) {
            return $errors['LongMessage'];
        } else {
            $errorMsg='';
            foreach ($errors as $e) {
                if ($e['SeverityCode'] == 'Error') {
                    $errorMsg .= $e['LongMessage'] . ',';
                }
            }
            return $errorMsg;
        }
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
	    	// if (is_file($xmlFile) && filemtime($xmlFile) > CURRENT_TIMESTAMP-30*ONEDAY){
    	    if (is_file($xmlFile) && filemtime($xmlFile) > time()-30*86400){
    	        return file_get_contents($xmlFile);
    	    }
    	    return false;
	    }else {
	        return file_put_contents($xmlFile,$data);
	    }
	}
	/**
	 * [is_xml 用于判断是否为XML格式,如果错误]
	 * 说明：simplexml_load_string()函数参数要为XML格式,否则系统报错
	 * @Author willage 2017-02-17T09:30:31+0800
	 * @Editor willage 2017-02-17T09:30:31+0800
	 * @param  [type]  $str                     [description]
	 * @return [type]                           [description]
	 */
	function is_xml($str){
		$xml_parser = xml_parser_create();
		if(!xml_parse($xml_parser,$str,true)){
			xml_parser_free($xml_parser);
			return false;
		}else {
			xml_parser_free($xml_parser);
			return true;
		}
    }
}

class EbayInterfaceException_RequestFormatInvalidate extends Exception {} 
class EbayInterfaceException extends Exception {}
class EbayInterfaceException_NotRealSend extends EbayInterfaceException {}
class EbayInterfaceException_Connection_Timeout extends EbayInterfaceException {}
class EbayInterfaceException_InvalidReturnXml extends EbayInterfaceException {}
