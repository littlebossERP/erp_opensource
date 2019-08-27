<?php
namespace common\api\ebayinterface\shopping;

use common\api\ebayinterface\config;
use common\helpers\Helper_xml;
use common\api\ebayinterface\ebayapi_proxy;
class base
{
	public $responseIsFail=false;
	public $error=null;
	public $callname=null;
	public static $connecttimeout=60;
	public static $timeout=300;
	function __construct(){
		$this->production=config::$production;//ebayInterface_Config::$production;
		$this->_loadconfig();
	}
	function _loadconfig(){
        $this->config["compatabilityLevel"] = 865;
        $this->config+=config::getConfig($this->production);
        
    }
    
	/**
	 * 在外调用时 用的 请求
	 * 如 $if=new EbayInterface_shopping_GetMultipleItems();
     *    $data=$if->request(array('ItemID'=>array('130608438898','260903933717')));
	 */
	function request($requestData,$IncludeSelector=null){
		$this->responseIsFail=false;
		$this->error=null;
		
		if(is_array($requestData)){
			foreach($requestData as $k=>$v){
				if($k=='ItemID'){
					if(is_array($v)){
						$v=implode(',',$v);
					}
					$requestData[$k]=$v;
				}
			}
		}
        $request=$requestData;
        if($IncludeSelector){
            $request['IncludeSelector']=$IncludeSelector;
        }
        
        $response=$this->sendRequest($this->callname,$request);
        
        if (isset($response['Errors'])){
        	$this->error=$response['Errors'];
        }
        
		if(isset($response[$this->responseFieldName])){
			return $response[$this->responseFieldName];
		}else{
			return $response;
		}
        return null;
    }
	
    /**
     * 简单请求 
     */         
    function sendRequest($callname,$requestData,$responseencoding='JSON'){
        $request=array(
        	'callname'=>$callname ,
            'appid'=> $this->config['appID'],
            'version'=>$this->config["compatabilityLevel"],
            'responseencoding'=>$responseencoding,
        	);
        if(strlen($this->siteid)){
        	$request+=array('siteid'=>$this->siteid);
        }
        if(is_array($requestData)){
            $request+=$requestData;
        }
        $requestStr= $this->config['shoppingUrl'].$this->formatpoststr($request);
        // var_dump($requestStr);die;
//        $response=$this->cget($requestStr);
        $response = ebayapi_proxy::shoppingpost($requestStr);
		
        if($responseencoding=='JSON'){
			$response=json_decode($response,1);
            if($response['Ack']=='Failure') $this->responseIsFail=true;
			
			return $response;
        }elseif($responseencoding=='XML'){
            $response= Helper_xml::xmlparse($response);
			if($response['Ack']=='Failure') $this->responseIsFail=true;
			return $response;
        }
        return $response;
    }
    
    //将 post字段 组成 字符串
    function formatpoststr($postDataArr,$leftAnd=0){
    	$postfieldstr="";
    	foreach($postDataArr as $k=>$v){
    		if (is_array($v) || is_object($v)){
    			while (list($cur_key, $cur_val) = each($v)) {
    				$postfieldstr.="&".urlencode($k)."[]=".urlencode($cur_val);
    			}
    		}else{
    			$postfieldstr.="&".urlencode($k)."=".urlencode(stripslashes($v));
    		}
    	}
    	if(!$leftAnd){
    		$postfieldstr=ltrim($postfieldstr,'&');
    	}
    	return $postfieldstr;
    }
    
    /****
     * Get 用curl()函数 使用
    */
    public function cget($url,$timeout=10){
    	// create a new cURL resource
    	$ch = curl_init();
    	// set URL and other appropriate options
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	// grab URL and pass it to the browser
    	
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,self::$connecttimeout);
    	curl_setopt($ch, CURLOPT_TIMEOUT,self::$timeout);
    	
    	$return=curl_exec($ch);
    	
    	$error=curl_error($ch);
    	curl_close($ch);
    	if ($error){
    		echo "timeout\n";
    		throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url);
    	}
    	return trim($return);
    }
}
class CurlExcpetion_Connection_Timeout extends \Exception {}