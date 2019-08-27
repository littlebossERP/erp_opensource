<?php
require_once(dirname(__FILE__)."/HttpHelper.php");
require_once(dirname(__FILE__)."/TimeUtil.php");
require_once(dirname(__FILE__)."/Utility.php"); 

$URLS = [];
$URLS['getnewsales'] ="https://ws.fr.shopping.rakuten.com/sales_ws?action=getnewsales&version=2014-02-11";
$URLS['getcurrentsales'] ="https://ws.fr.shopping.rakuten.com/sales_ws?action=getcurrentsales&version=2014-02-11";
$URLS['settrackingpackageinfos']="https://ws.fr.shopping.rakuten.com/sales_ws?action=settrackingpackageinfos&version=2012-11-06";
$URLS['acceptsale']="https://ws.fr.shopping.rakuten.com/sales_ws?action=acceptsale&version=2010-09-20";
$URLS['refusesale']="https://ws.fr.shopping.rakuten.com/sales_ws?action=refusesale&version=2010-09-20";
$URLS['listing']="https://ws.fr.shopping.rakuten.com/listing_ssl_ws?action=listing&version=2015-07-05";
$URLS['getiteminfos']="https://ws.fr.shopping.rakuten.com/sales_ws?action=getiteminfos&version=2011-06-01";
$URLS['getitemtodolist']="https://ws.fr.shopping.rakuten.com/sales_ws?action=getitemtodolist&version=2011-09-01";
$URLS['contactuseraboutitem'] = "https://ws.fr.shopping.rakuten.com/sales_ws?action=contactuseraboutitem&&version=2011-02-02";

$expected_token ="HE654HRYR,,SDFEdfsaaoi";

foreach (  $_GET  as $key => $value ){
	if (! isset ( ${$key} ))
		${$key} = $value;
}

foreach (  $_POST  as $key => $value ){
	if (! isset ( ${$key} ))
		${$key} = $value;
}
	if (! isset($action)) $action = "";
	

 
//pharse the parameters for this api call
	if (!empty($params))
		$params = json_decode($params,true);
	else
		$params = array();

	$results = array();
	$results['success'] = false;
	$results['message'] = '';
	
//write_log("Step start: action = $action ", "info");	
//default carrier type = 0
	if (!isset($carrier_type) or $carrier_type=='')
		$carrier_type = '0';
	
	if (!empty($action) and !empty($URLS[$action])){
	
	
	$url = $URLS[$action] . "&login=@username@&pwd=@pwd@";
	$url = str_replace("@username@",urlencode($username), $url);
	$url = str_replace("@pwd@",urlencode($pwd), $url);
	
	if (!empty($params)){
		foreach ($params as $key=>$val){
			//$url.= "&".$key."=".urlencode($val);
			if($action =='contactuseraboutitem'&&$key == 'content'){
		        $change_language = mb_convert_encoding($val, "ISO-8859-1", "auto");
		        $url.= "&".$key."=".urlencode($change_language);
		    }else{
		        $url.= "&".$key."=".urlencode($val);
		    }
		}
	}
	
	}else{
		$results['success'] = false;
		$results['message'] = 'action is not supported or empty'.(!empty($action)?" - $action":"");
	}
		
	if (!empty($username) and isset($token) and $token==$expected_token and !empty($action) and !empty($URLS[$action])){
	//below use curl
	//start of curl version
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url  );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$TIME_OUT = 80;
		$Connection_TIME_OUT = 60;
		curl_setopt($ch, CURLOPT_TIMEOUT, $TIME_OUT);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $Connection_TIME_OUT);
		
		$output = curl_exec($ch);
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		
		if ($curl_errno > 0) { // network error
			//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"ExtTrack Curl ReTry after error $curl_errno , $Ext_Call" ],"edb\global");
			$results['message']='net work error ' . $curl_errno;
			$output='';
		}	 
		 

		
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$results['httpCode'] = $httpCode;
		
		if (!empty($output) and $httpCode == '200'){
			//$output = Utility::xml_to_array($output);
		}else{ // network error
			$output ='' ;
		}
		
		curl_close($ch);
		$rs = $output;
	//end of curl
		
		
		$results['success'] = ! empty($output);
	 
		$results['rtn'] = urlencode($rs);
		$results['proxyURL'] = $url ;
	}
	
	//write_log("Step Done: Done action $action . ", "info");

 
	echo json_encode($results);
 


?>

