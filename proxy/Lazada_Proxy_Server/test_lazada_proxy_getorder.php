<?php

$fd=@fopen("test.log", "a");


function call_api( $get_params = array()  ,$return_type='json' ){
	global $fd;
	$post_params=array();
	
	 
	$url = 'http://localhost/Lazada_Proxy_Server/ApiEntryV2.php';
	 
	
	$url .= "?";
	$rtn['success'] = true;
	$rtn['message'] = '';
	
	foreach($get_params  as $key=>$value){
		$url .= "&$key=".urlencode(trim($value));
	}

	$handle = curl_init($url);
	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

	 // 改ip
    // curl_setopt($handle, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:8.8.8.8', 'CLIENT-IP:8.8.8.8'));
	
	if (count($post_params)>0){
		curl_setopt($handle, CURLOPT_POST, true);
		curl_setopt($handle, CURLOPT_POSTFIELDS, $post_params );
	}
	//  output  header information
	// curl_setopt($handle, CURLINFO_HEADER_OUT , true);

	/* Get the HTML or whatever is linked in $url. */
	$response = curl_exec($handle);

	/* Check for 404 (file not found). */
	$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

	if ($httpCode == '200' ){
			fputs($fd, $response." \n");
			$rtn['response'] = $response;
	}else{
		$rtn['message'] .= "Failed for  , Got error respond code $httpCode from Proxy";
		$rtn['success'] = false ;
		$rtn['response'] = "";
	}
 
	curl_close($handle);
	return $rtn;
}//end of call_api by proxy

$dsn = "mysql:host=localhost;dbname=user_1;charset=utf8";
//$db = new PDO($dsn, 'root', '123456');
set_time_limit(0);
		 
		$config=array(
			'userId' => "",
			'apiKey' => "",
			'countryCode' => ""
	    );
					
		
		
		// $Timestamp = new DateTime("2015-12-22T10:00:00+0800");
		$Timestamp = new DateTime("2016-12-30T01:10:08+0700");
		// $Timestamp->setTimestamp(time() -  1 * 86400);
		// $Timestamp->setTimestamp(1479310084);
		$Timestamp->setTimezone ( new DateTimeZone('UTC'));
		

	
		// $beforeTime = new DateTime("2015-12-23T09:00:00+0800");
		$beforeTime = new DateTime("2016-12-30T01:12:10+0700");
		// $beforeTime->setTimestamp(time() - 60*5);
		// $beforeTime->setTimestamp(1479310086);
		$beforeTime->setTimezone ( new DateTimeZone('UTC'));
		
		$apiReqParams = array("UpdatedAfter"=>$Timestamp->format(DateTime::ISO8601) , 'UpdatedBefore'=>$beforeTime->format(DateTime::ISO8601));
		// $apiReqParams = array("CreatedAfter"=>$Timestamp->format(DateTime::ISO8601) , 'CreatedBefore'=>$beforeTime->format(DateTime::ISO8601));
		

		// $apiReqParams['offset'] = 3000;
		
		//gmdate("Y-m-d H:i:s",gmmktime()-150)
		$reqParams=array(
				"config"=>json_encode($config),
				"action"=>"GetOrderList",
				// "reqParams"=>json_encode(array("CreatedAfter"=>$Timestamp->format(DateTime::ISO8601) , 'CreatedBefore'=>$beforeTime->format(DateTime::ISO8601)))
				"reqParams"=>json_encode($apiReqParams)
				
		);
		
		echo print_r($reqParams,true);
		$ret=call_api($reqParams);
		echo print_r($ret,true);
		
		 

?>




