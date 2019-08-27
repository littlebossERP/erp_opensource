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

	// print_r($response);
	
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
		
		// 最多5个
		$idList = array(
			'',
		);
		$idType = 'SellerSKU';
		
	

		


		$reqParams=array();
		$reqParams["idType"] = $idType;// ASIN、GCID、SellerSKU、UPC、EAN、ISBN 和 JAN。
		$reqParams["idList"] = json_encode($idList);
		$reqParams["return_type"] = 'json';
		$reqParams["config"]=json_encode($config);
		
		echo "reqParams:".print_r($reqParams,true);
		$ret=call_amazon_api("GetMatchingProductForId2",$reqParams);
	
		
		echo print_r($ret,true);
 

?>




