<?php
class CdiscountService{
	/**
	* 调用 设置config 的方法
	**/
	static function SetupCdiscountConfig($config){
		CdiscountAPI::set_cdiscount_config($config);
	}//end of SetupcdiscountConfig
	
	/**
	* 调用 获取cdiscount 订单的方法
	**/
	static function getOrderList($params){
		return CdiscountAPI::GetOrderList($params);
	}//end of getOrderList
	/**
	* 调用 cdiscount tokenID的方法
	**/
	static function getTokenID($username , $password){
		$token = CdiscountAPI::AUTH_VALIDATION($username , $password);
		return CdiscountAPI::AUTH_VALIDATION($username , $password);
	}//end of getTokenID
	/**
	* 调用 cdiscount order customer emaiul 的方法
	**/
	static function getEmailByOrderID($params){
		return CdiscountAPI::GenerateDiscussionMailGuid($params);
	}//end of getEmailByOrderID
	/**
	* 调用 cdiscount product 的方法
	**/
	static function getProductList($params){
		return CdiscountAPI::GetProductList($params);
	}//end of getProductList
	
	/*
	* 调用 cdiscount order 改为发货 的方法
	*	params = array(
	*		'items'=>your shipping items (array) , 
	*		'orderid'=> your order id (string) , 
	*		'TrackingNumber'=> your tracking number (string),
	*		'TrackingUrl'=>your tracking url (string) , 
	*		'OrderState'=>your order state (string) = shipped
	*		'AcceptationState' => your Acceptation State (string) = ShippedBySeller
	*		'CarrierName'=>your  carrier name(string) optional , 
	*	)
	*/
	static function ShipCdiscountOrder($params){
		$params['orderstate'] = "Shipped";
		$params['AcceptationState'] = "ShippedBySeller";
		return CdiscountAPI::ShippedOrderList($params);
	}//end of ShipCdiscountOrder

	static function GetOrderClaimList($params){
		return CdiscountAPI::GetOrderClaimList($params);
	}//end of GetOrderClaimList
	
	static function GetOrderQuestionList($params){
		return CdiscountAPI::GetOrderQuestionList($params);
	}//end of GetOrderQuestionList
	static function GenerateMailDiscussionGuid($params){
		return CdiscountAPI::GenerateMailDiscussionGuid($params);
	}//end of GenerateMailDiscussionGuid
	
	static function getDiscussionMailList($params){
		return CdiscountAPI::GetDiscussionMailList($params);
	}//end of getDiscussionMailList
	
	static function getOfferList($params){
		return CdiscountAPI::GetOfferList($params);
	}//end of getOfferList

	static function getOfferListPaginated($params){
		return CdiscountAPI::GetOfferListPaginated($params);
	}//end of getOfferListPaginated
	
	static function AccepteOrRefuseOrders($params){
		return CdiscountAPI::AccepteOrRefuseOrders($params);
	}//end of ValidateCdiscountOrder
	
	static function GetSellerInformation($params){
		return CdiscountAPI::GetSellerInformation($params);
	}//end of ValidateCdiscountOrder


	static function GetAllowedCategoryTree(){
		return CdiscountAPI::GetAllowedCategoryTree();
	}//end of ValidateCdiscountOrder
	
}//end of class CdiscountService