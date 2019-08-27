<?php
class PriceministerService{
	/**
	* 调用 设置config 的方法
	**/
	static function SetupPriceministerConfig($config){
		PriceministerAPI::set_priceminister_config($config);
	}//end of SetuppriceministerConfig
	
	/**
	* 调用 获取priceminister 订单的方法
	**/
	static function getOrderList($params){
		return PriceministerAPI::GetOrderList($params);
	}//end of getOrderList
	/**
	* 调用 priceminister tokenID的方法
	**/
	static function getTokenID($username , $password){
		$token = PriceministerAPI::AUTH_VALIDATION($username , $password);
		return PriceministerAPI::AUTH_VALIDATION($username , $password);
	}//end of getTokenID
	/**
	* 调用 priceminister order customer emaiul 的方法
	**/
	static function getEmailByOrderID($params){
		return PriceministerAPI::GenerateDiscussionMailGuid($params);
	}//end of getEmailByOrderID
	/**
	* 调用 priceminister product 的方法
	**/
	static function getProductList($params){
		return PriceministerAPI::GetProductList($params);
	}//end of getProductList
	
	/*
	* 调用 priceminister order 改为发货 的方法
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
	static function ShipPriceministerOrder($params){
		$params['orderstate'] = "Shipped";
		$params['AcceptationState'] = "ShippedBySeller";
		return PriceministerAPI::ShippedOrderList($params);
	}//end of ShipPriceministerOrder

	static function GetOrderClaimList($params){
		return PriceministerAPI::GetOrderClaimList($params);
	}//end of GetOrderClaimList
	
	static function GetOrderQuestionList($params){
		return PriceministerAPI::GetOrderQuestionList($params);
	}//end of GetOrderQuestionList
	static function GenerateMailDiscussionGuid($params){
		return PriceministerAPI::GenerateMailDiscussionGuid($params);
	}//end of GenerateMailDiscussionGuid
	
	static function getDiscussionMailList($params){
		return PriceministerAPI::GetDiscussionMailList($params);
	}//end of getDiscussionMailList
	
	static function getOfferList($params){
		return PriceministerAPI::GetOfferList($params);
	}//end of getOfferList

	static function AccepteOrRefuseOrders($params){
		return PriceministerAPI::AccepteOrRefuseOrders($params);
	}//end of ValidatePriceministerOrder
	
	static function GetSellerInformation($params){
		return PriceministerAPI::GetSellerInformation($params);
	}//end of ValidatePriceministerOrder
	
}//end of class PriceministerService