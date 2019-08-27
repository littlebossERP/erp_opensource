<?php
 
 
require_once(dirname(__FILE__)."/HttpHelper.php");
require_once(dirname(__FILE__)."/TimeUtil.php");
require_once(dirname(__FILE__)."/Utility.php"); 

//17 Track provided access url for the query

$url_get_sales="https://ws.fr.shopping.rakuten.com/sales_ws?action=getnewsales&login=@username@&pwd=@pwd@&version=2014-02-11";
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
	if (!empty($parms))
		$parms = json_decode($parms,true);
	else
		$parms = array();

	if (isset($parms['data']))
		$data = $parms['data'];

	$results = array();
	$results['success'] = false;
	$results['message'] = '';
	
//write_log("Step start: action = $action ", "info");	
//default carrier type = 0
	if (!isset($carrier_type) or $carrier_type=='')
		$carrier_type = '0';
	
	$url = str_replace("@username@",$username, $url_get_sales);
	$url = str_replace("@pwd@",$pwd, $url);
	
	if (isset($track_code) and trim($track_code) <>'' and isset($token) and $token==$expected_token){
		$httpRequest = new HttpHelper($url     );
		$httpRequest->sendRequest();
		//file_put_contents('temp.txt',$httpRequest->getResponse());
		$rs = $httpRequest->getResponseBody();
		
		$results['success'] = true;
		$results['rtn'] = $rs;
		$results['proxyURL'] = $url ;
	}
	
	//write_log("Step Done: Done action $action . ", "info");
	
	echo json_encode($results);
 


?>

