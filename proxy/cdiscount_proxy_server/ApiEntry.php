<?php

require_once(dirname(__FILE__)."/Utility.php");
require_once(dirname(__FILE__)."/cdiscountAPI.php"); 
require_once(dirname(__FILE__)."/cdiscountService.php");


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
	if (isset($config))
	$config = json_decode($config,true);
	if (isset($query_params))
	$params = json_decode($query_params,true);
	
	if (isset($config))
	CdiscountService::SetupCdiscountConfig($config);
	$act = strtolower($action);
	
	// auth
	if ($act == strtolower('getTokenID')){
		$result = CdiscountService::getTokenID($config['username'] , $config['password']);
	}
	
	
	// order 
	if ($act == strtolower('getOrderList')){
		/*
			params = array(
				'BeginCreationDate' =>(string)optional
				'BeginModificationDate'=> (string)optional
				'EndCreationDate'=> (string)optional
				'EndModificationDate'=> (string)optional
				'state'=> (array)optional
			)
			
		*/
		$result = CdiscountService::getOrderList($params);
	}
	
	
	
	if ($act == strtolower('getEmailByOrderID')){
		$result = CdiscountService::getEmailByOrderID($params['orderid']);
	}
	
	if ($act == strtolower('getProductList')){
		$result = CdiscountService::getProductList($params);
	}
	
	
	
	if ($act == strtolower('shipCDiscountOrder')){
		/*
			params = array(
				'items'=>your shipping items (array) *require , 
				'orderid'=> your order id (string) *require , 
				'TrackingNumber'=> your tracking number (string) optional,
				'TrackingUrl'=>your tracking url (string) optional, 
				'CarrierName'=>your  carrier name(string) optional , 
			)
		*/
		$result = CdiscountService::ShipCdiscountOrder($params);
	}
	
	if ($act == strtolower('GetOrderClaimList')){
		/*
			params = array(
				'BeginCreationDate' =>(string)optional
				'BeginModificationDate'=> (string)optional
				'EndCreationDate'=> (string)optional
				'EndModificationDate'=> (string)optional
				'StatusList'=> (array)optional /like: <cdis:DiscussionStateFilter>Open</cdis:DiscussionStateFilter>
				'OrderNumberList'=>
				'OnlyWithMessageFromCdsCustomerService'=>
			)
			
		*/
		$result = CdiscountService::GetOrderClaimList($params);
	}
	
	if ($act == strtolower('GetOrderQuestionList')){
		/*
			params = array(
				'BeginCreationDate' =>(string)optional
				'BeginModificationDate'=> (string)optional
				'EndCreationDate'=> (string)optional
				'EndModificationDate'=> (string)optional
				'StatusList'=> (array)optional /like: <cdis:DiscussionStateFilter>Open</cdis:DiscussionStateFilter>
				'OrderNumberList'=>
				'OnlyWithMessageFromCdsCustomerService'=>
			)
			
		*/
		$result = CdiscountService::GetOrderQuestionList($params);
	}
	/*
	if ($act == strtolower('generateMailDiscussionGuid')){
			#params = array(
			#	'OrderId'=>
			#)

		$result = CdiscountService::GenerateMailDiscussionGuid($params);
	}*/
	
	//getting an encrypted mail address to contact a customer about an order.
	if ($act == strtolower('GenerateDiscussionMailGuid')){
			#params = array(
			#	'OrderId'=>
			#)

		$result = CdiscountService::generateDiscussionMailGuid($params);
	}
	
	//getting an encrypted mail address to contact a customer about a discussion (claim, retraction, questions).
	if ($act == strtolower('GetDiscussionMailList')){
			#params = array(
			#	'OrderId'=>
			#)

		$result = CdiscountService::getDiscussionMailList($params);
	}
	
	//This operation seeks offers according to several criteria
	if ($act == strtolower('GetOfferList')){
			#params = array(
			#	'SellerProductIdList'=>
			#)

		$result = CdiscountService::getOfferList($params);
	}
	
	//This operation seeks offers according to several criteria
	if ($act == strtolower('GetOfferListPaginated')){
			#params = array(
			#	'OfferFilterCriterion'=>
			#	'OfferSortOrder'=>
			#	'PageNumber'=>
			#)

		$result = CdiscountService::getOfferListPaginated($params);
	}
	
	//This operation makes it possible to update the state of an order
	if ($act == strtolower('AccepteOrRefuseOrders')){
			#params = array(
			#	'orderId'=>
			#)
		$result = CdiscountService::AccepteOrRefuseOrders($params);
	}
	
	if ($act == strtolower('GetSellerInfo')){
			#params = array(
			#	'orderId'=>
			#)
		$result = CdiscountService::GetSellerInformation(array());
	}


	if ($act == strtolower('GetAllowedCategoryTree')){
		#params = array(
		#	'orderId'=>
		#)
		$result = CdiscountService::GetAllowedCategoryTree();
	}

//***************************************** end **************************************/	
if ($action == "")
	echo " no api action is required ! <br>";
else
	echo json_encode($result);
	
	