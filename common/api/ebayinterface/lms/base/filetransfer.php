<?php
namespace common\api\ebayinterface\lms\base;

use common\api\ebayinterface\config;
use Yii;
use common\helpers\Helper_Curl;
use common\helpers\Helper_Filesys;
use common\helpers\Helper_xml;
use SimpleXMLElement;
use Exception;
/**
 * Large Merchant Service
 *
 */
class filetransfer
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
    /**
     * X-EBAY-SOA-SERVICE-NAME
     *
     * @example FileTransferService
     */
    public $service_name;
    
    public $_not_real_send=false;
    public $_last_request_header_array;
    public $_last_request_xml;
    public $config;
    /****
     * eBay Api Version
     */
    public $version='1.1.0';
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
    function __construct($service_name){
        $this->service_name=$service_name;
        $this->production=config::$production;
        $this->_loadconfig();
    }
    function _loadconfig(){
        // these keys can be obtained by registering at http://developer.ebay.com       
        $this->config["compatabilityLevel"] = '1.1.0';    // eBay API version
        $this->config['request_xmlprops']='xmlns:sct="http://www.ebay.com/soaframework/common/types" xmlns="http://www.ebay.com/marketplace/services"';
        if ($this->production) {
            //set the Server to use (Sandbox or Production)
            $this->config["serverUrl"] = 'https://storage.ebay.com/'.$this->service_name;      // server URL different for prod and sandbox
            $this->config["host"]='storage.ebay.com';
            $this->config["port"]='700';
            $this->config["path"]='/FileTransferService';
            
        } else {
            // sandbox (test) environment
            $this->config["serverUrl"] = 'https://storage.sandbox.ebay.com/'.$this->service_name;
            $this->config["host"]='storage.sandbox.ebay.com';
            $this->config["port"]='700';
            $this->config["path"]='/FileTransferService';                 
        }
    }
    
    function getHeaders($init=null){
        $header=array (
            'X-EBAY-SOA-SERVICE-VERSION: ' .$this->config["compatabilityLevel"],
            'X-EBAY-SOA-SECURITY-TOKEN: ' . $this->eBayAuthToken,
            'X-EBAY-SOA-OPERATION-NAME: ' . $this->verb,
            'X-EBAY-SOA-REQUEST-DATA-FORMAT: XML',
            'X-EBAY-SOA-RESPONSE-DATA-FORMAT: XML',
            'X-EBAY-SOA-SERVICE-NAME: ' .$this->service_name,
        );
        $default=array(
            'CONTENT-TYPE'=>'text/xml'
        );
        if(is_array($init)){
            $default=array_merge($default,$init);
        }
        foreach($default as $k=>$v){
            array_push($header,$k.': '.$v);
        }
        return $header;
    }
    /***
     * 发送类 
     *   $sendXmlArr : 数据 数组 ,会被组织成 Xml 
     *      如果 本来就是 字符串的 ,就认为本来就是  Xml      
     *   $returnXml : 返回 值是 数组 或 原生 xml .默认 是 数组 
     */
    function sendHttpRequest($sendXmlArr,$isreturnXml=0,$timeout=40){
        // 1,body 部分  
        if(is_array($sendXmlArr)){
            $requestBody=Helper_xml::simpleArr2xml($sendXmlArr);//dump($requestBody);die;
        }elseif(is_string($sendXmlArr)){
            $requestBody=$sendXmlArr;//dump($requestBody);die;
        }else{
            return false;
        }
        $this->_last_request_xml=$requestBody;
        $headers =$this->_last_request_header_array=$this->getHeaders(); 
        #not_really
        if ($this->_not_real_send){
            throw new EbayInterfaceException_NotRealSend($requestBody); 
        }
        
        #统计api请求次数
//         $countCacheID='ebay_interface_request_count_'.SHANGHAI_DATE;
//         $count=Q::cache($countCacheID);
//         if (!$count){
//             $count=0;
//         }
//         $count++;
        //Q::writeCache($countCacheID,$count,array('life_time'=>ONEDAY));
        #记录请求内容
//        Yii::log('EbayInterface Request Count: '.$count);
        Yii::info($requestBody);
                
        # 连接api 接口，超时或返回数据有问题重试2次
        try {
            set_time_limit($timeout);
            $response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers);
        }catch (CurlExcpetion_Connection_Timeout $ex){
            Yii::info('EBAY API CONNECTED TIMEOUT');
        }
//         if (!isset($response) ){
//             try {
//                 set_time_limit($timeout);
//                 $response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers);
//             }catch (CurlExcpetion_Connection_Timeout $ex){
//                 Yii::log('EBAY API CONNECTED TIMEOUT');
//             }
//         }
//         if (!isset($response)){
//             try {
//                 set_time_limit($timeout);
//                 $response = Helper_Curl::post($this->config["serverUrl"],$requestBody,$headers);
//             }catch (CurlExcpetion_Connection_Timeout $ex){
//                 Yii::log('EBAY API CONNECTED TIMEOUT');
//                 throw new EbayInterfaceException_Connection_Timeout('EBAY API CONNECTED TIMEOUT');
//             }
//         }
        if (!isset($response)){
            Yii::info('INVALID RETURN XML');
            throw new EbayInterfaceException_InvalidReturnXml('INVALID RETURN XML');
        }
        
        #记录请求结果
        Yii::info($response);
        return $response;
        
        
        //返回 数组
        if($isreturnXml){
            return $response;
        }else{
            return Helper_xml::xmlparse($response);
        }
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
    function sendRequest($returnXml=0,$timeout=40){
        if (empty($this->config['requestMethod'])){
            $this->setRequestMethod($this->verb);
        }
        if (empty($this->config['requestMethod']) || !isset($this->config['requestBody'])){
            throw new EbayInterfaceException_RequestFormatInvalidate('请确认已经设置requestMethod 和requestBody');
        }
        $xmlArr=array(
            $this->config['requestMethod'].' '.$this->config['request_xmlprops']=>array(
            )
        );
        if ($this->config['requestBody'] instanceof SimpleXMLElement ){
            $xmlArr['requestXML']=$this->config['requestBody'];
        }else{
            foreach ($this->config['requestBody'] as $domKey => $domValue){
                $xmlArr[$this->config['requestMethod'].' '.$this->config['request_xmlprops']][$domKey]=$domValue;
            }
        }
        $xmlArr=Helper_xml::simpleArr2xml($xmlArr);
        
//      if ($this->verb !='SetNotificationPreferences'){var_dump($xmlArr);exit;}
        return $this->sendHttpRequest($xmlArr,$returnXml,$timeout);
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
            if (is_file($xmlFile) && filemtime($xmlFile) > CURRENT_TIMESTAMP-30*ONEDAY){
                return file_get_contents($xmlFile);
            }
            return false;
        }else {
            return file_put_contents($xmlFile,$data);
        }
    }
    
    /****
     * 
     * sock open post 上传文件 , 这只是 把参数组织一下,再调用 socket_post      
     *
     * $url : url str  or uri arr ('host'=> , 'path'=> , 'port'=> )
     * $xmlUuid
     * $xmlfileUuid
     * 
     */
    public function uploadHttpRequest($xmlUuid='',$xmlData='',$xmlfileUuid='',$xmlfileData='',$timeout=10){
        
        $this->_mime_boundary = "MIMEBoundaryurn_uuid_".md5(uniqid(microtime()));
        // xml 参数
        $data='';
        $data .= "--".$this->_mime_boundary."\r\n";
        $data .= 'Content-Type: application/xop+xml; charset=UTF-8; type="text/xml; charset=UTF-8"'."\r\n";
        $data .= 'Content-Transfer-Encoding: binary'."\r\n";
        $data .= 'Content-ID: <0.urn:uuid:'.$xmlUuid.'>'."\r\n\r\n\r\n";
        
        $data .= $xmlData;
        
        // xml 附件
        $data .="\r\n";
        $data .= "--".$this->_mime_boundary."\r\n";
        $data .= 'Content-Type: application/octet-stream'."\r\n";
        $data .= 'Content-Transfer-Encoding: binary'."\r\n";
        $data .= 'Content-ID: <urn:uuid:'.$xmlfileUuid.'>'."\r\n\r\n";
        $data .= $xmlfileData."\r\n";
        $data .= "--".$this->_mime_boundary."--\r\n";
        
        // Post Http Header
        $sheader  = "POST ".$this->config["path"]." HTTP/1.1\r\n";
        
        $sheader .= 'Content-Type: multipart/related; boundary='.$this->_mime_boundary.';type="application/xop+xml";';
        $sheader .= 'start="<0.urn:uuid:'.$xmlUuid.'>";start-info="text/xml"'."\r\n"; 
            // Ebay Header
            $sheader .= "X-EBAY-SOA-SERVICE-NAME: FileTransferService\n";
            $sheader .= "X-EBAY-SOA-OPERATION-NAME: ".$this->verb."\n";
            $sheader .= "X-EBAY-SOA-SECURITY-TOKEN: ".$this->eBayAuthToken."\n";
            $sheader .= "X-EBAY-SOA-REQUEST-DATA-FORMAT: XML\n";
            $sheader .= "X-EBAY-SOA-RESPONSE-DATA-FORMAT: XML\n";
            //$sheader .= "X-EBAY-SOA-SERVICE-VERSION: ".$this->version."\n";
        $sheader .= "User-Agent: Jakarta Commons-HttpClient/3.1\r\n";   
        $sheader .= "Host: ".$this->config["host"].":".$this->config["port"]."\r\n";
        
        $sheader .= "Content-length: ".strlen($data)."\r\n";
        $sheader .= "Connection: Close\r\n\r\n";
         //close
        
        if($this->config['port'] == 443){
            $host = "ssl://".$this->config['host'];
        }else{
            $host = $this->config['host'];
        }

        $fp = fsockopen($host, $this->config['port'], $fetch['errno'], $fetch['errstr'], $timeout);
        if($fp == false)
        {
            return $fetch;
        }

        //发送
        $j=fwrite($fp, $sheader.$data);
        var_dump($j);
        $fetch['header']='';
        while($str = trim(fgets($fp)))
        {
            $fetch['header'] .= $str."\r\n";
        }
        $fetch['body']='';
        while(!feof($fp))
        {
            $str=fgets($fp);
            $fetch['body'].=$str;
        }
        fclose($fp);
        return $fetch;
    }
    

    /***
     * 组织 提交 数据格式 
     */
    function buildXmlBodyFile($xmlData,$xmlfileData){
        $this->_mime_boundary = "MIMEBoundaryurn_uuid_".md5(uniqid(microtime()));
        // xml 参数
        $data='';
        $data .= "--".$this->_mime_boundary."\r\n";
        $data .= 'Content-Type: application/xop+xml; charset=UTF-8; type="text/xml; charset=UTF-8"'."\r\n";
        $data .= 'Content-Transfer-Encoding: binary'."\r\n";
        $data .= 'Content-ID: <0.urn:uuid:'.$this->xmlUuid.'>'."\r\n\r\n";
        
        $data .= $xmlData."\r\n";
        
        // xml 附件
        $data .= "--".$this->_mime_boundary."\r\n";
        $data .= 'Content-Type: application/octet-stream'."\r\n";
        $data .= 'Content-Transfer-Encoding: binary'."\r\n";
        $data .= 'Content-ID: <urn:uuid:'.$this->fileUuid.'>'."\r\n\r\n";
        $data .= $xmlfileData."\r\n";
        $data .= "\r\n";
        $data .= "--".$this->_mime_boundary."--\r\n";
        return $data;
    }
}
if(!class_exists('EbayInterfaceException')){
class EbayInterfaceException_RequestFormatInvalidate extends Exception {}
class EbayInterfaceException extends Exception {}
class EbayInterfaceException_NotRealSend extends EbayInterfaceException {}
class EbayInterfaceException_Connection_Timeout extends EbayInterfaceException {}
class CurlExcpetion_Connection_Timeout extends Exception {};
class EbayInterfaceException_InvalidReturnXml extends Exception {};
}