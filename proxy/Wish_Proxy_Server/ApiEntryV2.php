<?php
require_once 'vendor/autoload.php';
use Wish\WishClient;
use Wish\Exception\ServiceResponseException;
use Wish\Model\WishTracker;

require_once(dirname(__FILE__)."/TimeUtil.php");
require_once(dirname(__FILE__)."/Utility.php"); 
require_once(dirname(__FILE__)."/WishServiceV2.php");

foreach (  $_GET  as $key => $value ){
	if (! isset ( ${$key} ))
		${$key} = $value;
}

foreach (  $_POST  as $key => $value ){
	if (! isset ( ${$key} ))
		${$key} = $value;
}
	if (! isset($action)) $action = "";
	

//start to setup the amazon helper configuration according to parmaeters passed input via http	
	 //$token is is initilized
    if(!empty($token))
        WishService::setupToken(str_replace('@@@','=',$token));
	
//pharse the parameters for this api call
	if (!empty($parms))
		$parms = json_decode($parms,true);
	else
		$parms = array();

	if (isset($parms['data']))
		$data = $parms['data'];
	
	//write_log( "YS1: got data:".print_r($data,true));
	
	//check if there is wish product id in data array, if yes, do not use UpdateProduct, but CreateProduct
	if (strtolower($action) == strtolower("CreateProduct") and isset($data['wish_product_id']) and $data['wish_product_id']<>'' and $data['wish_product_id']<>'0' )
		$action = "UpdateProduct";
	
	//if action is create or update, but no product name is given, then update variation only
	if (($action == "UpdateProduct" or $action == "CreateProduct") and (!isset($data['name']) or $data['name']=='') )
		$action = "UpdateProductVariation";
		
//initilize the result
	//$results = "Ready to do action ".$action;

	write_log("Step 0: recieve action $action", "info");
	
	/******************   Token start      **********************/
	if (strtolower($action) == strtolower("GetAccessToken")) {
		$results = array('success'=>true  , 'message'=>'' );
		//var_dump($parms);
		try{
			//把需要 的field从 data数组移到get_params中
			$require_field_list = array('client_id' , 'client_secret' ,  'code' , 'redirect_uri');
			foreach($require_field_list as $field_name){
				if (!empty($parms[$field_name])){
					$get_params[$field_name] = $parms[$field_name];
				}else{
					$results = array('success'=>false  , 'message'=>$field_name.' 为空或者不存在!');
				}
				
			}
			//假如必须的field缺失则不需要调用wish api 
			if ($results['success']){
				$TIME_OUT=180;
				
				write_log("info: ".$get_params['client_id']." start get access token" , "info");
				
				$get_params['grant_type'] = 'authorization_code';
				$results = WishService::call_WISH_api('GetAccessToken',$get_params );
			}
		}catch(ServiceResponseException $e){
			$results['success'] = false;	  		
  			$results['message'] = $e->getErrorMessage()." Client Id:".$data['client_id'];
  			write_log("error:".$data['client_id']." got Access Token: " , "info");

		}
	}//end of GetAccessToken
	
	if (strtolower($action) == strtolower("refreshAccessToken")) {
		$results = array('success'=>true  , 'message'=>'');
		
		try{
			//把需要 的field从 data数组移到get_params中
			$require_field_list = array('client_id' , 'client_secret' , 'refresh_token');
			foreach($require_field_list as $field_name){
				if (!empty($parms[$field_name])){
					$get_params[$field_name] = $parms[$field_name];
				}else{
					$results = array('success'=>false  , 'message'=>$field_name.' 为空或者不存在!');
				}
				
			}
			//假如必须的field缺失则不需要调用wish api 
			if ($results['success']){
				$TIME_OUT=180;
				
				write_log("info: ".$get_params['client_id']." start refresh access token" , "info");
				
				$get_params['grant_type'] = 'refresh_token';
				
				$results = WishService::call_WISH_api('RefreshAccessToken',$get_params );
			}
		}catch(ServiceResponseException $e){
			$results['success'] = false;	  		
  			$results['message'] = $e->getErrorMessage()." Client Id:".$data['client_id'];
  			write_log("error:".$data['client_id']." refresh Access Token: " , "info");

		}
	}//end of refreshAccessToken
	
	if (strtolower($action) == strtolower("AuthTest")) {
		$results = array('success'=>true  , 'message'=>'');
		
		try{
			if (!empty($parms['access_token']))
				$get_params['access_token'] = $parms['access_token'];
			else
				$get_params['access_token'] = '';
			$results = WishService::call_WISH_api('authTest',$get_params );
		}catch(ServiceResponseException $e){
			$results['success'] = false;	  		
  			$results['message'] = $e->getErrorMessage()." Client Id:".$data['client_id'];
  			write_log("error:".$parms['access_token']." Auth Test: " , "info");

		}
	}//end of AuthTest
	
/******************   Token end      **********************/

/******************   Product Listing start      **********************/
	/**************************************************************************
	 * Update Product
	*************************************************************************/	
	if (strtolower($action) == strtolower("UpdateProduct")) {
		
		$client = new WishClient(WishService::$token,'prod');
		$results['success'] = false;
		$results['message'] = '';
		try{
			//	Get your product by its ID
			write_log("info: start to UpdateProduct: ".$data['wish_product_id']  , "info");
			$product = $client->getProductById($data['wish_product_id']);

		//	$product->name = "Awesome name";
		//	$product->description = "This shoe is the best on Wish";
		//	$product->tags = "shoe, awesome, size 11";

			$product->parent_sku = $data['parent_sku']; //This is a mandatory field in API
			_formatProductValues($product, $data);
			$product->id = $data['wish_product_id'];//This is a mandatory field in API
			//write_log("info: start to do: ".$product->id." PRODUCT:".print_r($product,true) , "info");
			//ystest starts
			$prodData = $product->getParams(array(
					'id',
					'name',
					'description',
					'tags',
					'brand',
					'landing_page_url',
					'upc',
					'main_image', 'extra_images'));
			write_log("info: start to do update prod for : " .print_r($prodData,true) , "info");
			//ystest ends
			
			$results['wishReturn'] = $client->updateProduct($product ); //ystest $product1 is testing
			write_log("info: Got return:".print_r($results['wishReturn'],true) , "info");
			$results['success'] = true;		
		}catch(ServiceResponseException $e){ //ServiceResponsException
	  		$results['success'] = false;	  		
  			$results['message'] = $e->getErrorMessage()." Product Id:".$data['wish_product_id'];
  			write_log("error: got product id returned: ".$product->id." PRODUCT:".print_r($product,true) , "info");

		}
	}
	
	/**************************************************************************
	 * Create Product
	*************************************************************************/	
	if (strtolower($action) == strtolower("CreateProduct")) {
		$client = new WishClient(WishService::$token);
		$results['success'] = false;
		$results['message'] = '';
		$results['wishReturn'] ='';
		$product = array();
		_formatProductValues($product, $data);
		
		//ystest write_log("Ready to do create product".print_r($product,true), "info");
		try {
			write_log("info: start to CreateProduct for : " .print_r($product,true) , "info");
  			$prod_res = $client->createProduct($product);
  			$results['success'] = true;
  			$results['wishReturn'] = $prod_res;
  			
  			//if there is nothing returns, it is not success
  			if (!isset($prod_res->id)){
  				$results['success'] = false;
  				$results['message'] = "Wish服务器拒绝了不正确的数据录入";
  			}
  			
  			write_log("Done to create product".print_r($prod_res,true), "info");
		//  	print_r($prod_res);
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
		}catch(ServiceResponseException $e){ //ServiceResponsException
			write_log("failed to CreateProduct for : " .print_r($product,true) . $e->getErrorMessage()  , "info");
	  		$results['success'] = false;	  		
  			$results['message'] = $e->getErrorMessage();
		}
	
	}	
	

/**************************************************************************
* Update Product Inventory
*************************************************************************/	
	if (strtolower($action) == strtolower("UpdateProductVariation")) {
		$client = new WishClient(WishService::$token,'prod');
		$results['success'] = false;
		$results['message'] = '';
		$results['wishReturn'] ='';
		$sku = $data['sku'];

        error_log(var_export($data,true)."\r\n",3,"/tmp/chenbin_{$action}.log");
		write_log("Ready to do do prod variance".print_r($data,true), "info");
		//when there is product id comes, do the update is fine.
		if (isset($data['variance_product_id']) && !empty($data['variance_product_id'])){
			write_log("variance has variance product id ".$data['variance_product_id']." so update it", "info");
			try {
				$var = $client->getProductVariationBySKU($sku);
				$var->price = $data['price'];
				$var->shipping = $data['shipping'];
				$var->inventory = $data['inventory'];
                $var->enabled = $data['enable'] == 'Y' ? 'True' : 'False';
                error_log(var_export($var,true)."\r\n",3,"/tmp/chenbin_{$action}.log");
				$client->updateProductVariation($var);
				$results['success'] = true;
				//format the wishReturn value
				$results['wishReturn']['parent_product_id'] = $var->product_id;
				$results['wishReturn']['variance_product_id'] = $var->id;
				$results['wishReturn']['variance_sku'] = $var->sku;
			}catch(ServiceResponseException $e){ //ServiceResponsException
				$results['success'] = false;
				$results['message'] = $e->getErrorMessage();
			}
		}else{
			//write_log("variance has NO variance product id,so create " , "info");
			//there is no product id for variance, do a create variance
			$product_var = array(
					'parent_sku'=>$data['parent_sku'],
					'color'=>$data['color'],
					'sku'=>$data['sku'],
					'inventory'=>$data['inventory'],
					'price'=>$data['price'],
					'shipping'=>$data['shipping'],
					'size'=>$data['size'],
			);
			try {
				//write_log("try to create variance ".print_r($product_var,true) , "info");
				
				$var = $client->createProductVariation($product_var);
				            
				
				//write_log("Done to create variance ".json_encode($var) , "info");
				$results['success'] = true;
				$results['wishReturn']['parent_product_id'] = $var->product_id;
				$results['wishReturn']['variance_product_id'] = $var->id;
				$results['wishReturn']['variance_sku'] = $var->sku;
				
			}catch(ServiceResponseException $e){ //ServiceResponsException
				$results['success'] = false;
				$results['message'] = $e->getErrorMessage();
			}
		}//end of creating a variance
		
		//write_log("start to set variance enabled =".$data['enabled'] , "info");
		//start to set the product variance is enabled or not
		if ($results['success'] and isset($data['enabled']) and $data['enabled']=='Y'){	
			try {
				$client->enableProductVariationBySKU($sku);
				$results['success'] = true;
			}catch(ServiceResponseException $e){ //ServiceResponsException
				$results['success'] = false;
				$results['message'] = $e->getErrorMessage();
			}	
		}
		
		if ($results['success'] and isset($data['enabled']) and $data['enabled']=='N'){	
			try {
				$client->disableProductVariationBySKU($sku);
				$results['success'] = true;
			}catch(ServiceResponseException $e){ //ServiceResponsException
				$results['success'] = false;
				$results['message'] = $e->getErrorMessage();
			}	
		}
			
			
		
	} 
	
	
/**************************************************************************
 * Get All Product
 *************************************************************************/
 
	
	if (strtolower($action) == strtolower("GetAllProduct")) {
		$client = new WishClient(WishService::$token);
		$results['success'] = true;
		$results['message'] = '';
		try {
			//	Get an array of all your products
			$products = $client->getAllProducts();
			//	print(count($products));

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
	}

/**************************************************************************
 * Change Products Status
 *	@Parameters			$action = "changeProductStatus"
+---------------------------------------------------------------------------------------------
 *
 *	log			name	date					note
 * @author		lkh		2015/07/03				初始化
+---------------------------------------------------------------------------------------------
 *************************************************************************/
if(strtolower($action) == strtolower("changeProductStatus")){

    $get_params = array('access_token'=>WishService::$token,
        'parent_sku' => $data['parent_sku']);

    if($data['enable'] == true){
        $model = 'enable_product';
    } else {
        $model = 'disable_product';
    }

    $TIME_OUT=180;

    write_log("info: start to change product status ,param =".json_encode($post_params), "info");
    $results = WishService::call_WISH_api($model,$get_params );
}
/**
 *
 */
if(strtolower($action) == strtolower("changeProductVaritationStatus")){

    $get_params = array(
        'access_token'=>WishService::$token,
        'sku' => $data['sku']
    );

    if($data['enable'] == true){
        $model = 'varitation_enable';
    } else {
        $model = 'varitation_disable';
    }

    $TIME_OUT=180;

    write_log("info: start to change product status ,param =".json_encode($post_params), "info");
    $results = WishService::call_WISH_api($model,$get_params );
}
/**************************************************************************
 * Get Products by Pagination
 *	@Parameters			$action = "getProductsByPagination"
 +---------------------------------------------------------------------------------------------
 *
 *	log			name	date					note
 * @author		lkh		2015/07/03				初始化
 +---------------------------------------------------------------------------------------------
 *************************************************************************/
 if (strtolower($action) == strtolower("getProductsByPagination")) {

	$get_params = array(
		'access_token'=>WishService::$token,
	);
     $limit = $data['limit'];
     $start = $data['start'];
     $since = $data['since'];

     if(!empty($start)){
         $get_params['start'] = $start;
     }
     if(!empty($limit)){
         $get_params['limit'] = $limit;
     }
     if(!empty($since)){
         $get_params['since'] = $since;
     }


     $TIME_OUT=180;
	
	write_log("info: start to get product ,param =".json_encode($get_params), "info");
	$results = WishService::call_WISH_api('product',$get_params );
		
 }//end of getProductsByPagination

/******************     refresh order  start      **********************/
	/**
	 +---------------------------------------------------------------------------------------------
	 * getAllChangedOrdersSince list according to the from Date time
	 +---------------------------------------------------------------------------------------------
	 * @Parameters			$action = "getAllChangedOrdersSince"
	 +---------------------------------------------------------------------------------------------
	 * @dateSince		UTC format date time, orders modified after this time will be retrieved
	 * 						
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/04/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
if (strtolower($action) == strtolower("getAllChangedOrdersSince")) { 
	$client = new WishClient(WishService::$token);
	$results['success'] = false;
	$results['message'] = '';
	try{
		if (!empty($parms['dateSince'])){
			$dateSince = $parms['dateSince'];
		}else{
			$dateSince = '';
		}
		//echo 'parms : '. print_r($parms,true);
		//	retrieve change orders
		$orders = $client->getAllChangedOrdersSince($dateSince);
		//echo print_r($orders,true).' and token : '.WishService::$token;
		$results['wishReturn'] = $orders;
		write_log("info: start to get all change orders since: ".$parms['dateSince']." orders:".print_r($orders,true) , "info");
		$results['success'] = true;		
	}catch(ServiceResponseException $e){ //ServiceResponsException
		$results['success'] = false;	  		
		$results['message'] = $e->getErrorMessage();
		if (!empty ($dateSince))
		$results['message'].= " since date : ".$dateSince;
		write_log("error: got all change orders since: ".$dateSince." error :".$e->getErrorMessage() , "info");
	}
}


if (strtolower($action) == strtolower("getAllUnfulfilledOrdersSince")) { 
	//$client = new WishClient(WishService::$token,'sandbox');
	$client = new WishClient(WishService::$token);
	$results['success'] = false;
	$results['message'] = '';
	try{
		if (!empty($parms['dateSince'])){
			$dateSince = $parms['dateSince'];
		}else{
			$dateSince = '';
		}
		//echo 'parms : '. print_r($parms,true);
		//	retrieve change orders
		$orders = $client->getAllUnfulfilledOrdersSince($dateSince);
		//echo print_r($orders,true).' and token : '.WishService::$token;
		$results['wishReturn'] = $orders;
		write_log("info: start to get all Unfulfilled orders since: ".$dateSince." orders:".print_r($orders,true) , "info");
		$results['success'] = true;		
	}catch(ServiceResponseException $e){ //ServiceResponsException
		$results['success'] = false;	  		
		$results['message'] = $e->getErrorMessage();
		if (!empty ($dateSince))
		$results['message'].= " since date : ".$dateSince;
		write_log("error: got all Unfulfilled orders since: ".$dateSince." error :".$e->getErrorMessage() , "info");
	}
}

if (strtolower($action) == strtolower("getChangedOrdersSinceByPagination")) {
	if (empty($parms['limit'])){
		$limit = 100;
	}else{
		$limit = $parms['limit'];
	}
	
	if (empty($parms['start'])){
		$start = 0;
	}else{
		$start =$parms['start'];
	}
	$get_params = array(
		'start'=>$start , 
		'limit'=>$limit , 
		 
		'access_token'=>WishService::$token,
	); 
	
	if (!empty($dateSince)){
		$get_params['since'] = $dateSince;
	}
	
	$TIME_OUT=180;
	
	write_log("info: start to get Recently Changed Orders ,param =".json_encode($get_params), "info");
	$results = WishService::call_WISH_api('GetChangedOrder',$get_params );
}

if (strtolower($action) == strtolower("getUnfulfilledOrdersSinceByPagination")) {
	if (empty($parms['limit'])){
		$limit = 100;
	}else{
		$limit = $parms['limit'];
	}
	
	if (empty($parms['start'])){
		$start = 0;
	}else{
		$start =$parms['start'];
	}
	$get_params = array(
		'start'=>$start , 
		'limit'=>$limit , 
		 
		'access_token'=>WishService::$token,
	); 
	
	if (!empty($dateSince)){
		$get_params['since'] = $dateSince;
	}
	
	$TIME_OUT=180;
	
	write_log("info: start to get unfulfill Orders ,param =".json_encode($get_params), "info");
	$results = WishService::call_WISH_api('GetUnfulfillOrder',$get_params );
}

/******************     refresh order  end      **********************/

/****************************************** Fulfill an order start ******************************************/

if (strtolower($action) == strtolower("fulfillOrderById")) { 
	$client = new WishClient(WishService::$token);
	$results['success'] = false;
	$results['message'] = '';
	try{
		if (!empty($parms['tracking_number'])){
			$tracking_number = $parms['tracking_number'];
			
		}else{
			$tracking_number = '';
		}
		
		if (!empty($parms['ship_note'])){
			$ship_note = $parms['ship_note'];
			
		}else{
			$ship_note = '';
		}

		if (!empty($parms['origin_country_code'])){
			$origin_country_code = $parms['origin_country_code'];

		}else{
			$origin_country_code = '';
		}
		
		$TIME_OUT=180;
		$get_params = array(
			'id' => $parms['order_id'] , 
			'tracking_number' => $tracking_number,
			'tracking_provider'=>$parms['tracking_provider'],
			'access_token'=>WishService::$token,
		);
		if( $origin_country_code!='' ){
			$get_params['origin_country_code']= $origin_country_code;
		}
		write_log("info: start to fulfill order by id: ".$parms['order_id']." delivery info : tracking provider ".$parms['tracking_provider']." and tracking number ".$tracking_number ." and ship note".$ship_note, "info");
		$results = WishService::call_WISH_api('fulfillOne',$get_params );
		//echo print_r($orders,true).' and token : '.WishService::$token;
		/*
		$results['wishReturn'] = $orders;
		$results['params'] = $get_params;
		$results['success'] = true;		
		*/
	}catch(ServiceResponseException $e){ //ServiceResponsException
		$results['success'] = false;	  		
		$results['message'] = $e->getErrorMessage()." order id : ".$parms['order_id'];
		write_log("error: start to fulfill order by id: ".$parms['order_id']." delivery info : tracking provider ".$parms['tracking_provider']." and tracking number ".$tracking_number ." and ship note".$ship_note , "info");
	}
}

/****************************************** Fulfill an order end  ******************************************/

/****************************************** Modify Tracking of a Shipped Order start ******************************************/

if (strtolower($action) == strtolower("updateTrackingInfoById")) { 
	//$client = new WishClient(WishService::$token);
	$results['success'] = false;
	$results['message'] = '';
	try{
		if (!empty($parms['tracking_number'])){
			$tracking_number = $parms['tracking_number'];
			
		}else{
			$tracking_number = '';
		}
		
		if (!empty($parms['ship_note'])){
			$ship_note = $parms['ship_note'];
			
		}else{
			$ship_note = '';
		}
		if (!empty($parms['origin_country_code'])){
			$origin_country_code = $parms['origin_country_code'];

		}else{
			$origin_country_code = '';
		}
			
		$TIME_OUT=180;
		$get_params = array(
			'id' => $parms['order_id'] , 
			'tracking_number' => $tracking_number,
			'tracking_provider'=>$parms['tracking_provider'],
			'access_token'=>WishService::$token,
		);
		if( $origin_country_code!='' ){
			$get_params['origin_country_code']= $origin_country_code;
		}
		write_log("info: start to Modify Tracking of a Shipped Order  by id: ".$parms['order_id']." delivery info : tracking provider ".$parms['tracking_provider']." and tracking number ".$tracking_number ." and ship note".$ship_note , "info");
		$results = WishService::call_WISH_api('modifyTracking',$get_params );
		//echo print_r($orders,true).' and token : '.WishService::$token;
		
		/*
		$results['wishReturn'] = $orders;
		$results['params'] = $get_params;
		$results['success'] = true;	
		 */	
	}catch(ServiceResponseException $e){ //ServiceResponsException
		$results['success'] = false;	  		
		$results['message'] = $e->getErrorMessage()."  order id : ".$parms['order_id'];
		write_log("error: start to Modify Tracking of a Shipped Order  by id: ".$parms['order_id']." delivery info : tracking provider ".$parms['tracking_provider']." and tracking number ".$tracking_number ." and ship note".$ship_note  , "info");
	}
}

/****************************************** Modify Tracking of a Shipped Order end  ******************************************/


/******************************************  Refund/Cancel an order start ******************************************/
/**
 * 
 * Refund Reason Codes
	0	No More Inventory
	1	Unable to Ship
	2	Customer Requested Refund
	3	Item Damaged
	7	Received Wrong Item
	8	Item does not Fit
	9	Arrived Late or Missing
	-1	Other, if none of the reasons above apply. reason_note is required if this is used as reason_code
 */
if (strtolower($action) == strtolower("refundOrderById")) { 
	$client = new WishClient(WishService::$token);
	$results['success'] = false;
	$results['message'] = '';
	$wish_reason_mapping = array(
		'0'=>'No More Inventory',
		'1'=>'Unable to Ship',
		'2'=>'Customer Requested Refund',
		'3'=>'Item Damaged',
		'7'=>'Received Wrong Item',
		'8'=>'Item does not Fit',
		'9'=>'Arrived Late or Missing',
		'-1'=>'Other, if none of the reasons above apply. reason_note is required if this is used as reason_code',
	);
	try{
		//echo 'parms : '. print_r($parms,true);
		//	Refund/Cancel an order
		if (!empty($parms['reason_note'])){
			$reason_note = $parms['reason_note'];
		}else{
			$reason_note = '';
		}
		$orders = $client->refundOrderById($parms['order_id'],$parms['reason_code'],$reason_note);
		//echo print_r($orders,true).' and token : '.WishService::$token;
		$results['wishReturn'] = $orders;
		write_log("info: start to Refund/Cancel order by id: ".$parms['order_id']." Refund/Cancel info : reason code ".$parms['reason_code']." andreason ".$wish_reason_mapping[$parms['reason_code']]." and reason note : $reason_note", "info");
		$results['success'] = true;		
	}catch(ServiceResponseException $e){ //ServiceResponsException
		$results['success'] = false;	  		
		$results['message'] = $e->getErrorMessage()."  order id : ".$parms['order_id'];
		write_log("error: Refund/Cancel order by id: ".$parms['order_id']." Refund/Cancel info : reason code ".$parms['reason_code']." andreason ".$wish_reason_mapping[$parms['reason_code']]." and reason note : $reason_note", "info");
	}
}

/****************************************** Refund/Cancel an order end ******************************************/

/****************************************** paginateion get next data start ******************************************/

if (strtolower($action) == strtolower("getNextData")) {
	//var_dump($parms);
	if (empty($parms['next'])){
		exit('no next page!');
	}else{
		$parms['next'] = urldecode($parms['next']);
	}
	
	$TIME_OUT=180;
	
	write_log("info: start to get Recently Changed Orders ,param =".json_encode($get_params), "info");
	$results = WishService::get_next_data($parms['next'],$TIME_OUT);
}
/****************************************** paginateion get next data end ******************************************/



/**
 +----------------------------------------------------------
 * wish List all Tickets Awaiting You
 +----------------------------------------------------------
 * @access static
 +----------------------------------------------------------
 * @param		$parms['start'] //从第几条开始
 * 				$parms['limit'] //每页显示多小条记录
 *
 +----------------------------------------------------------
 是否执行成功         错误信息
 * @return			    Array 'success'=>true,'message'=>''
 *
 +----------------------------------------------------------
 * log			name	date					note
 * @author		hqw		2015-07-24   			初始化
 +----------------------------------------------------------
 **/
/****************************************** List all Tickets Awaiting You start ******************************************/
if (strtolower($action) == strtolower("getAllTicketsAwaiting")) {
	$results['success'] = false;
	$results['message'] = '';
	try{
		if (!empty($parms['start'])){
			$start = $parms['start'];
		}else{
			$start = '0';
		}

		if (!empty($parms['limit'])){
			$limit = $parms['limit'];
		}else{
			$limit = '200';
		}
			
		$TIME_OUT=180;
		$get_params = array(
		'start' => $start,
		'limit' => $limit,
		'access_token'=>WishService::$token,
		);
		write_log("info: start to List all Tickets Awaiting You" , "info");
		$results = WishService::call_WISH_api('getAwaitingTickets',$get_params );
// 		$results['success'] = true;
	}catch(ServiceResponseException $e){ //ServiceResponsException
		$results['success'] = false;
		$results['message'] = $e->getErrorMessage();
		write_log("error: start to List all Tickets Awaiting You" , "info");
	}
}

/****************************************** List all Tickets Awaiting You end  ******************************************/

/**
 +----------------------------------------------------------
 * wish Reply to a Ticket
 +----------------------------------------------------------
 * @access static
 +----------------------------------------------------------
 * @param		$parms['id'] //wish平台的Ticket_id
 * 				$parms['reply'] //回复的内容
 *
 +----------------------------------------------------------
 是否执行成功         错误信息
 * @return			    Array 'success'=>true,'message'=>''
 *
 +----------------------------------------------------------
 * log			name	date					note
 * @author		hqw		2015-07-24   			初始化
 +----------------------------------------------------------
 **/
/****************************************** Reply to a Ticket start ******************************************/
if (strtolower($action) == strtolower("replyOneTickets")) {
	$results['success'] = false;
	$results['message'] = '';
	try{
		if (!empty($parms['id'])){
			$wishTicketid = $parms['id'];
		}

		if (!empty($parms['reply'])){
			$reply = $parms['reply'];
		}
			
		$TIME_OUT=180;
		$get_params = array(
		'id' => $wishTicketid,
		'reply' => $reply,
		'access_token'=>WishService::$token,
		);

		write_log("info: start Reply to a Ticket" , "info");
		$results = WishService::call_WISH_api('replyTickets',$get_params,$get_params );
// 		$results['success'] = true;
	}catch(ServiceResponseException $e){ //ServiceResponsException
		$results['success'] = false;
		$results['message'] = $e->getErrorMessage();
		write_log("error: start Reply to a Ticket" , "info");
	}
}

/****************************************** Reply to a Ticket end  ******************************************/


/**
 +----------------------------------------------------------
 * wish Close a Ticket
 +----------------------------------------------------------
 * @access static
 +----------------------------------------------------------
 * @param		$parms['id'] //wish平台的Ticket_id
 *
 +----------------------------------------------------------
 是否执行成功         错误信息
 * @return			    Array 'success'=>true,'message'=>''
 *
 +----------------------------------------------------------
 * log			name	date					note
 * @author		hqw		2015-07-24   			初始化
 +----------------------------------------------------------
 **/
/****************************************** Close a Ticket start ******************************************/
if (strtolower($action) == strtolower("closeOneTickets")) {
	$results['success'] = false;
	$results['message'] = '';
	try{
		if (!empty($parms['id'])){
			$wishTicketid = $parms['id'];
		}
			
		$TIME_OUT=180;
		$get_params = array(
		'id' => $wishTicketid,
		'access_token'=>WishService::$token,
		);

		write_log("info: start Close a Ticket" , "info");
		$results = WishService::call_WISH_api('closeTicket',$get_params );
// 		$results['success'] = true;
	}catch(ServiceResponseException $e){ //ServiceResponsException
		$results['success'] = false;
		$results['message'] = $e->getErrorMessage();
		write_log("error: start Close a Ticket" , "info");
	}
}

/****************************************** Close a Ticket end  ******************************************/





write_log("Step Done: Done action $action ", "info");
 
if ($action == "")
	echo " no api action is required ! <br>";
else
	echo json_encode($results);
 

 

?>

