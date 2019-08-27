<?php
 
 
require_once(dirname(__FILE__)."/HttpHelper.php");
require_once(dirname(__FILE__)."/TimeUtil.php");
require_once(dirname(__FILE__)."/Utility.php"); 

//17 Track provided access url for the query
$url='http://v5-api.17track.net:8044/handlertrack.ashx?nums=';
$expected_token ="HEHEyssdfWERSDF,werSDFJIYfghg,ddctYAYA";

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


	if (isset($track_code) and trim($track_code) <>'' and isset($token) and $token==$expected_token){
		$url = $url . $track_code  ;
		$url .= (isset($fc)?"&fc=$fc":"").(isset($sc)?"&sc=$sc":"") ;
		
		$httpRequest = new HttpHelper($url);
		
		$httpRequest->addRequestHeader('17token: 10FA5EB83300E5F592B9B35A0E07FC3F');
		
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

