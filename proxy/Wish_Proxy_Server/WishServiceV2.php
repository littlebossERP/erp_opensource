<?php

class WishService{
	public static $token = null;
	public static $wish_url_mapping = [
		'product'=>'https://merchant.wish.com/api/v2/product/multi-get',
		'createProduct'=>'https://merchant.wish.com/api/v2/product/add',
		'updateProduct'=>'https://merchant.wish.com/api/v2/product/update',
		'createVariant'=>'https://merchant.wish.com/api/v2/variant/add',
		'updateVariant'=>'https://merchant.wish.com/api/v2/variant/update',
		'getProduct'=>'https://merchant.wish.com/api/v2/product',
		'getVariant'=>'https://merchant.wish.com/api/v2/variant',
        'enable_product' => 'https://merchant.wish.com/api/v2/product/enable',
        'disable_product' => 'https://merchant.wish.com/api/v2/product/disable',
        'varitation_enable' => 'https://merchant.wish.com/api/v2/variant/enable',
        'varitation_disable' => 'https://merchant.wish.com/api/v2/variant/disable',
		'variant'=>'https://merchant.wish.com/api/v2/variant/multi-get',
		'fulfillOne'=>'https://merchant.wish.com/api/v2/order/fulfill-one',
		'modifyTracking'=>'https://merchant.wish.com/api/v2/order/modify-tracking',
		'GetChangedOrder'=>'https://merchant.wish.com/api/v2/order/multi-get',
		'GetUnfulfillOrder'=>'https://merchant.wish.com/api/v2/order/get-fulfill',
		'getAwaitingTickets'=>'https://merchant.wish.com/api/v2/ticket/get-action-required',
		'replyTickets'=>'https://merchant.wish.com/api/v2/ticket/reply',
		'closeTicket'=>'https://merchant.wish.com/api/v2/ticket/close',
		// 'GetAccessToken'=>'https://merchant.wish.com/api/v2/oauth/access_token',
		// 'RefreshAccessToken'=>'https://merchant.wish.com/api/v2/oauth/refresh_token',
		// dzt20191122 wish授权端口升级
		'GetAccessToken'=>'https://merchant.wish.com/api/v3/oauth/access_token',
		'RefreshAccessToken'=>'https://merchant.wish.com/api/v3/oauth/refresh_token',
		
		'authTest'=>'https://merchant.wish.com/api/v2/auth_test',
	];
	
	public static function setupToken($token){
		self::$token = $token;		
	}
	
	public static function call_WISH_api($action , $get_params = array()  , $post_params=array(),$TIME_OUT=180 ,$debug_model=false){
		// error_log(var_export($get_params,true),3,"/tmp/wishapitest.log");
		try {
			if (!empty(self::$wish_url_mapping[$action])){
				$url = self::$wish_url_mapping[$action];
			}else{
				return ['success'=>false , 'message'=>$action.'不是有效的action!'];
			}
			
			if (!empty($get_params))
				$url .= "?".http_build_query($get_params);
				
			$handle = curl_init($url);
			//echo $url;//test kh
			
			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
			//echo "time out : ".$TIME_OUT;
			
			if (count($post_params)>0){
				curl_setopt($handle, CURLOPT_POST, true);
				$postdata = http_build_query($post_params);
				if($debug_model){
					$rtn['postData'] = $postdata;
				}
				curl_setopt($handle, CURLOPT_POSTFIELDS, $postdata );
			}
			//  output  header information
			// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
			
			/* Get the HTML or whatever is linked in $url. */
			$response = curl_exec($handle);
			$curl_errno = curl_errno($handle);
			$curl_error = curl_error($handle);
			//echo "<br> cURL Error $curl_errno : $curl_error";//test kh
			if ($curl_errno > 0) { // network error
				$rtn['message']="cURL Error $curl_errno : $curl_error";
				$rtn['success'] = false ;
				curl_close($handle);
				return $rtn;
			}
			
			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);//最后一个收到的HTTP代码 
			$httpUrl = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);//最后一个有效的URL地址 
			//echo "<br>last : $httpUrl"; //test kh
			//echo $httpCode.$response."\n";
			
			if ($httpCode == '200' ){
			
				$rtn['wishReturn'] = json_decode($response , true);
					
				if ($rtn['wishReturn']==null){
					// json_decode fails
					$rtn['message'] = "content return from proxy is not in json format, content:".$response;
					$rtn['success'] = false ;
				}else{
					$rtn['message'] = "Done With http Code 200";
					$rtn['success'] = true ;
					
				}
					
			}else{ // network error
				if (!empty($response)){
					$rtn['wishReturn'] = json_decode($response , true);
				}
				
				if (!empty($rtn['wishReturn']['message'])){
					
					$rtn['message'] = $rtn['wishReturn']['message'];
				}else
					$rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy";
				$rtn['success'] = false ;
				if(!$debug_model){
					$rtn['wishReturn'] = "";
				}
			}
			curl_close($handle);
			$rtn['httpCode'] = $httpCode;
			
		} catch (Exception $e) {
			$rtn['success'] = false;  //跟proxy之间的网站是否ok
			$rtn['message'] = $e->getMessage();
			curl_close($handle);
		}
		return $rtn;
		
	}//end of call_WISH_api by proxy
	
	public static function get_next_data($next,$TIME_OUT=180){
		try {
			$handle = curl_init($next);
			//echo $url;//test kh
			
			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
			//echo "time out : ".$TIME_OUT;
			
			//  output  header information
			// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
			
			/* Get the HTML or whatever is linked in $url. */
			$response = curl_exec($handle);
			$curl_errno = curl_errno($handle);
			$curl_error = curl_error($handle);
			if ($curl_errno > 0) { // network error
				$rtn['message']="cURL Error $curl_errno : $curl_error";
				$rtn['success'] = false ;
				curl_close($handle);
				return $rtn;
			}
			
			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			//echo $httpCode.$response."\n";
			if ($httpCode == '200' ){
			
				$rtn['wishReturn'] = json_decode($response , true);
					
				if ($rtn['wishReturn']==null){
					// json_decode fails
					$rtn['message'] = "content return from proxy is not in json format, content:".$response;
					$rtn['success'] = false ;
				}else{
					$rtn['message'] = "Done With http Code 200";
					$rtn['success'] = true ;
					
				}
					
			}else{ // network error
				if (!empty($response)){
					$rtn['wishReturn'] = json_decode($response , true);
				}
				
				if (!empty($rtn['wishReturn']['message'])){
					//echo $rtn['wishReturn']['message'];
					$rtn['message'] = $rtn['wishReturn']['message'];
				}else
					$rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy";
				$rtn['success'] = false ;
				$rtn['wishReturn'] = "";
			}
			curl_close($handle);
			
		} catch (Exception $e) {
			$rtn['success'] = false;  //跟proxy之间的网站是否ok
			$rtn['message'] = $e->getMessage();
			curl_close($handle);
		}
		return $rtn;
	}
}//end of class


	function updateProduct($data){	
		
	$client = new WishClient(WishService::$token);
	$results['success'] = false;
	$results['message'] = '';
	try{
		//Get your product by its ID
		$product = $client->getProductById($data['wish_product_id']);
		//print_r( $product);
		
		_formatProductValues($product, $data);

		$client->updateProduct($product);

		$results['success'] = true;
		
	}catch(ServiceResponsException $e){
		$results['success'] = false;;
		$results['message'] = "Failed to update Product for ".$data['unique_id'];
	}
	return $results;
}
 


function createProduct($data){
	$client = new WishClient(WishService::$token);
	$results['success'] = false;
	$results['message'] = '';
	
	$product = array();
	_formatProductValues($product, $data);

	try {
  	$prod_res = $client->createProduct($product);
  	$results['success'] = true;
//  print_r($prod_res);
/*
  	$product_var = array(
    	'parent_sku'=>$product['parent_sku'],
    	'color'=>'black',
    	'sku'=>'var 8',
    	'inventory'=>100,
    	'price'=>10.8,
    	'shipping'=>10
    );

  	$prod_var = $client->createProductVariation($product_var);
  	print_r($prod_var);
*/
	}catch(ServiceResponsException $e){
  		$results['success'] = false;;
  		$results['message'] = "Failed to create Product for ".$product['name'];
	}

	return $results;
}



function getAllProduct(){
		$client = new WishClient(WishService::$token);
		$results['success'] = false;
		$results['message'] = '';
		try {
			//	Get an array of all your products
			$products = $client->getAllProducts();
			//	print(count($products));

			//	echo "<br>".print_r($products,true)."<br>";

			//		Get an array of all product variations
			$product_vars = $client->getAllProductVariations();
			//	print(count($product_vars));

			$results['product'] = $products;
			$results['product_vars'] = $product_vars;
	
		}catch(ServiceResponsException $e){
  			$results['success'] = false;;
  			$results['message'] = "Failed to get All Product";
  			$results['product'] = array();
  			$results['product_vars'] = array();
		}

	return $results;
}

/*****************************************************************************	
Below is Private Method
******************************************************************************/
function _makeJoinedImages($data){
		//You can specify one or more additional images separated by the character '|'
		$joinedImages = '';
		for ($i=1; $i<=10; $i++){
			if ($data['extra_image_'.$i] <>'')
				$joinedImages .= ($joinedImages==''?'':"|").$data['extra_image_'.$i] ;
		}	
		return $joinedImages;
	}
	
function _formatProductValues(&$product , &$data){			
		//fix if user inputs chinese common for multi-tags
		if (isset($data['tags']))
			$data['tags'] = str_replace('，',',',$data['tags']);
		
		if (is_array($product)){
			$product['sku'] = $data['sku'];
			
			if (!empty($data['parent_sku']))
				$product['parent_sku'] = $data['parent_sku'];
			
			$product['name'] = $data['name'];
			
			if (!empty($data['msrp']))
				$product['msrp'] = $data['msrp'];
			
			$product['description'] = $data['description'];
			$product['tags'] = $data['tags'];
			$product['brand'] = $data['brand'];
			$product['upc'] = $data['upc'];
			$product['main_image'] = $data['main_image'];
			//	You can specify one or more additional images separated by the character '|'
			$product['extra_images'] = _makeJoinedImages($data);
			$product['shipping_time'] = $data['shipping_time'];
			$product['shipping'] = $data['shipping'];
			$product['price'] = $data['price'];
			$product['inventory'] = $data['inventory'];
			$product['color'] = $data['color'];
			$product['size'] = $data['size'];
			$product['landing_page_url'] = $data['landing_page_url'];
		}else{
			$product->name = $data['name'];
			
			if (!empty($data['msrp']))
				$product->msrp = $data['msrp'];
			
			$product->description = $data['description'];
			$product->tags = $data['tags'];
			$product->brand = $data['brand'];
			$product->upc = $data['upc'];
			$product->main_image = $data['main_image'];
		//	You can specify one or more additional images separated by the character '|'
			$product->extra_images = _makeJoinedImages($data);
			$product->shipping_time = $data['shipping_time'];
			$product->shipping = $data['shipping'];
			$product->price = $data['price'];
			$product->inventory = $data['inventory'];
			
			if (!empty($data['color']))
				$product->color = $data['color'];
			
			if (!empty($data['size']))
				$product->size = $data['size'];
			
			$product->landing_page_url = $data['landing_page_url'];
		}
	}






?>

