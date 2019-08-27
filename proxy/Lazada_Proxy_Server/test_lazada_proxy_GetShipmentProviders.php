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
		 
		 
		$config=array(
			'userId' => "",
			'apiKey' => "",
			'countryCode' => "my"
	    );	 
		
		
		$apiReqParams = array();
		
		$reqParams=array(
				"config"=>json_encode($config),
				"action"=>"getShipmentProviders",
				"reqParams"=>json_encode($apiReqParams)
		);
		
		echo print_r($reqParams,true);
		$ret=call_api($reqParams);
		echo print_r($ret,true);
		
	
?>




