<?php
set_time_limit(0);
$fd=@fopen("test_amazon.log", "a");


function call_amazon_api($action , $get_params = array()  ,$return_type='json' ){
	global $fd;
	$post_params=array();

	$url = 'http://localhost/eagle2_Amazon_Proxy_Server/ApiEntry.php';
	$url .= "?action=$action";
	$rtn['success'] = true;
	$rtn['message'] = '';
	
	foreach($get_params  as $key=>$value){
		$url .= "&$key=".urlencode(trim($value));
	}

	$handle = curl_init($url);
	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($handle,  CURLOPT_TIMEOUT, 700);
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
		if ($return_type == 'xml'){
			$rtn['response'] = $response;
		}
		else{
			fputs($fd, $response." \n");
			$rtn['response'] = json_decode($response , true);
			
		}
	}else{
		$rtn['message'] .= "Failed for $action , Got error respond code $httpCode from Proxy";
		$rtn['success'] = false ;
		$rtn['response'] = "";
	}
 
	curl_close($handle);
	return $rtn;
}//end of call_amazon_api by proxy
	
							
		 
		$config=array(
			'merchant_id' => "",
			'marketplace_id' => "",
			'access_key_id' => "",
			'secret_access_key' => "",		
	    ); 

		// $Timestamp = new DateTime("2015-12-22T10:00:00+0800");
		$Timestamp = new DateTime();
		$Timestamp->setTimestamp(time() - 1 * 86400);
		$Timestamp->setTimezone ( new DateTimeZone('UTC'));
		
		// $beforeTime = new DateTime("2015-12-23T09:00:00+0800");
		$beforeTime = new DateTime();
		$beforeTime->setTimestamp(time() - 60*5);
		$beforeTime->setTimezone ( new DateTimeZone('UTC'));		
		
	
		$reqParams=array();
	
		$reqParams["config"]=json_encode($config);
		// $report_type = "_GET_MERCHANT_LISTINGS_DATA_BACK_COMPAT_";
		$report_type = "_GET_FLAT_FILE_OPEN_LISTINGS_DATA_";
		$report_type = "_GET_MERCHANT_LISTINGS_DATA_";
		// $report_type = "_GET_MERCHANT_LISTINGS_DEFECT_DATA_";
		$report_type = "_GET_MERCHANT_LISTINGS_ALL_DATA_";
		// $report_type = "_GET_XML_BROWSE_TREE_DATA_"; // browser node
		
		
		$parms = [
			'report_type'=>$report_type,
			// 'start_date'=>$start_date,
			// 'end_date'=>$end_date,
		];
		
		// $parms["start_date"] = $Timestamp->format(DateTime::ISO8601);
		// $parms["end_date"] = $beforeTime->format(DateTime::ISO8601);
		
		// $parms["start_date"] = date('Y-m-d\TH:i:s',time()- 1*86400);
		// $parms["end_date"] = date('Y-m-d\TH:i:s',time());
		
		$parms["start_date"] = '';
		$parms["end_date"] = '';
		

		$reqParams['parms'] = json_encode($parms);
		
		echo "reqParams:".print_r($reqParams,true);
		
		$ret=call_amazon_api("RequestAmazonReport",$reqParams);
		print_r($ret);
		
	


?>




