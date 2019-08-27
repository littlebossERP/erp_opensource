<?php
namespace eagle\modules\amazon\apihelpers;
/**
 +---------------------------------------------------------------------------------------------
 * eagle的web代码(非amazon的后台程序)需要跟amazon proxy进行交互，这里的helper提供了通信的基础函数
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		xjq		2014/07/21				初始化
 +---------------------------------------------------------------------------------------------
 **/


class AmazonProxyConnectApiHelper{

	// TODO proxy host
	public static $AMAZON_PROXY_URL= 'http://localhost/eagle2_Amazon_Proxy_Server/ApiEntry.php';
	
	// $TIME_OUT  s 单位
    // communicate to the amazon proxy to get the information through http
	public static function call_amazon_api($action , $get_params = array()  ,$TIME_OUT=60, $return_type='json', $post_params=array() ){
		$url = self::$AMAZON_PROXY_URL;
		$url .= "?action=$action";
		$rtn['success'] = true;  //跟proxy之间的网站是否ok
		$rtn['message'] = '';
	
	//	write_to_log("Line ".__LINE__." call_amazon_api reqParam:".json_encode($get_params),"debug",__FUNCTION__,basename(__FILE__));
	//	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","  reqParam:".json_encode($get_params),"Debug");
		foreach($get_params  as $key=>$value){
			$url .= "&$key=".urlencode(trim($value));
		}
	
		$handle = curl_init($url);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30); //连接超时
		
	
		if (count($post_params)>0){
			curl_setopt($handle, CURLOPT_POST, true);
			curl_setopt($handle, CURLOPT_POSTFIELDS, $post_params );
		}
		//  output  header information
		// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
	
		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($handle);
		$curl_errno = curl_errno($handle);
		$curl_error = curl_error($handle);
		if ($curl_errno > 0) { // network error		
			$rtn['message']="cURL Error $curl_errno : $curl_error";
			$rtn['success'] = false ;
			$rtn['response'] = "";
			curl_close($handle);
			return $rtn;
		}
	
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		//echo $httpCode.$response."\n";
		if ($httpCode == '200' ){
			if ($return_type == 'xml'){	$rtn['response'] = $response; }
			else $rtn['response'] = json_decode($response , true);
			
			//check submit的请求，返回的response 的message比较特殊，是个数组！！！！！！ 需要先把数组转成字符串
			if (isset($rtn['response']) and isset($rtn['response']["message"]) and is_array($rtn['response']["message"] )){
				$rtn['response']["message"]=print_r($rtn['response']["message"],true);				
			}
			
			if ($rtn['response']==null){
			   	// json_decode fails
			   	$rtn['message'] = "content return from proxy is not in json format, content:".$response;
			   	//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
		//	   	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
			   	$rtn['success'] = false ;		    	 
			}
		}else{ // network error
			$rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy";
			//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
		//	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
			$rtn['success'] = false ;
			$rtn['response'] = "";
		}
	
		curl_close($handle);
		return $rtn;
	}//end of call_amazon_api by proxy

	
	/**
	 * 通过获取订单list的方式来测试用户想要绑定的amazon的api信息是否ok
	 * @param $config=array('merchant_id'=>??,'marketplace_id'=>??,'access_key_id'=>??,'secret_access_key'=>??);
	 * @return true or false
	**/
	public static function testAmazonAccount($config){
		$currentTime=time(); //获取档期gmt时间
		$fromDateTime= gmdate("Y-m-d H:i:s",$currentTime-7200);  //需要获取的订单list的开始时间，注意：如果这个超过当前时间的话，amazon会请求失败,这里保险起见时间往前推了2个小时!!!!
		
		$reqParams=array("fromDateTime"=>$fromDateTime);
		$reqParams["config"]=json_encode($config);
		$reqParams["status"]="shipped";		
		$ret=self::call_amazon_api("getorder",$reqParams);
		
		\Yii::info('Amazon,' . __CLASS__ . ',' . __FUNCTION__ .',ret:'.print_r($ret,true) ,"file");
// 		SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","ret:".print_r($ret,true),"Debug");
		if (!isset($ret["success"]) || $ret["success"] != true) return false;
		
		return $ret["response"]["success"];
	}

}
?>