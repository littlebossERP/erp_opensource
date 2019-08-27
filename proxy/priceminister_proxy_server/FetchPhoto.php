<?php

require_once(dirname(__FILE__)."/HttpHelper.php");
require_once(dirname(__FILE__)."/TimeUtil.php");
require_once(dirname(__FILE__)."/Utility.php"); 

//17 Track provided access url for the query


$expected_token ="HE654HRYR,,dgfas,,SDFEdfsaaoi";

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
	$results['success'] = true;
	$results['message'] = '';

	
   // $url = 'http://mf1905.com/upload/video_img/df3074c98ec5124ad47c52ff59f74e04_middle.jpeg';  
  
    function http_get_data($url) { 
          
        $ch = curl_init ();  
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );  
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );  
        curl_setopt ( $ch, CURLOPT_URL, $url );  
        ob_start ();  
        curl_exec ( $ch );  
        $return_content = ob_get_contents ();  
        ob_end_clean ();  
          
        $return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );  
        return $return_content;  
    }  
     
	if (empty($url ) ){
		$url='';
		$results['success'] = false;
		$results['message'] = 'URL is empty';
		echo json_encode ($results);
	}
	
	$url = trim($url);
    $return_content = http_get_data($url);  
    $filename = $url;
	
	
	//get extersion of the file original
	$pics = explode('.' , $url); 
	$num = count($pics); 
	$extName = $pics[$num-1]; 
	
	$savedLocalName = $url;
	
	$savedLocalName = str_replace("http://","",$savedLocalName);
	$savedLocalName = str_replace("https://","",$savedLocalName);
	$savedLocalName = str_replace("/","_",$savedLocalName);
	
    $fp= @fopen("/var/www/priceminister_proxy_server/photocache/".$savedLocalName,"a"); //将文件绑定到流    
    fwrite($fp,$return_content); //写入文件  
    
	$results['local_name'] = $savedLocalName;
	
	echo json_encode($results); 
?>
