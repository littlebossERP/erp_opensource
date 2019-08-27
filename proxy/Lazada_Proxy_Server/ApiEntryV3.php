<?php
require_once(dirname(__FILE__)."/Utility.php");
require_once(dirname(__FILE__)."/LazadaApiV3.php"); 

function handleRequest(){
	$return = array();
	$return["success"] = true;
	$return["message"] = "";
	
	if (!isset($_REQUEST["config"]) or !isset($_REQUEST["action"]) or !isset($_REQUEST["reqParams"])){
		$return["message"] = "Parameter config,reqParams  and action should be included";
		$return["path"]=dirname(__FILE__);
		echo json_encode($return);
		return;
	}
	
	// write_log("handleRequest get:".json_encode($_REQUEST),"info");
	$config=json_decode($_REQUEST["config"],true);	
// 	if (!isset($config["userId"]) or !isset($config["apiKey"]) or !isset($config["countryCode"])){
	if (!isset($config["userId"]) or !isset($config["APP_KEY"]) or !isset($config["countryCode"]) or !isset($config["APP_SECRET"])  or !isset($config["apiKey"])){
		$return["message"] = "Parameter userId,APP_KEY,countryCode,APP_SECRET,apiKey should be included";
		echo json_encode($return);
		return;
	}
		
	LazadaApiV3::setConfig($config["userId"], $config["countryCode"], $config["APP_KEY"],$config["APP_SECRET"],$config["apiKey"]);
	$reqParams=json_decode($_REQUEST["reqParams"],true);
	$reqAction = strtolower($_REQUEST["action"]);	
	
	// $retData 返回的格式 必须与上面的数组 array("success"=>??? , "message"=>??? , )
	if ($reqAction == '/order/get'){
	    $retData = LazadaApiV3::getLazadaOneOrder($reqParams);
	}
	
	if ($reqAction=='/orders/get'){		
		$retData = LazadaApiV3::getLazadaOrderList($reqParams);
	}
	
	if ($reqAction=='/orders/items/get'){
		$retData = LazadaApiV3::getLazadaOrderItems($reqParams);
	}
	
	if ($reqAction=='/order/pack'){
	    $retData = LazadaApiV3::packedByMarketplace($reqParams);
	}
	
	if ($reqAction=="/order/rts"){
		$retData = LazadaApiV3::shipLazadaOrder($reqParams);
	}
	
	if ($reqAction=="/order/document/get"){
		$retData = LazadaApiV3::getDocument($reqParams);
	}
	
	if ($reqAction=="/finance/payout/status/get"){
	    $retData = LazadaApiV3::getPayoutStatus($reqParams);
	}
	
	if ($reqAction==strtolower("GetMetrics")){
	    $retData = LazadaApiV3::getMetrics($reqParams);
	}
	
	if ($reqAction=="/products/get"){
        $retData = LazadaApiV3::getLazadaProducts($reqParams);
	}
	
	if ($reqAction=="/filter/products/get"){//自定义的action，api没有该接口，都系用"/products/get"接口
		$retData = LazadaApiV3::getLazadaFilterProducts($reqParams);
	}
	
	if ($reqAction==strtolower("searchSPUs")){
	    $retData = LazadaApiV3::searchSPUs($reqParams);
	}
	
	if ($reqAction=="/product/qc/status/get"){
	    $retData = LazadaApiV3::getLazadaQcStatus($reqParams);
	}
	
	if ($reqAction==strtolower("getStatistics")){
	    $retData = LazadaApiV3::getLazadaStatistics($reqParams);
	}
	
	if ($reqAction=="/brands/get"){
		$retData = LazadaApiV3::getLazadaBrands($reqParams);
	}
	
	if ($reqAction=="/product/create"){
		$retData = LazadaApiV3::lazadaProductCreate($reqParams);
	}
	
	if ($reqAction=="/product/update"){
		$retData = LazadaApiV3::lazadaProductUpdate($reqParams);
	}
	
	if ($reqAction=="/product/price_quantity/update"){
	    $retData = LazadaApiV3::lazadaProductUpdatePriceQuantity($reqParams);
	}
	
	if ($reqAction=="/product/remove"){
		$retData = LazadaApiV3::lazadaProductDelete($reqParams);
	}
	
	if ($reqAction=="/image/migrate"){
	    $retData = LazadaApiV3::migrateLazadaImage($reqParams);
	}
	
	if ($reqAction=="/images/set"){
	    $retData = LazadaApiV3::setLazadaImage($reqParams);
	}
	
	if (strtolower($reqAction) == strtolower("getOrderItemImage")){
		$retData = LazadaApiV3::getLazadaOrderItemImage($reqParams);
	}
	
	if ($reqAction==strtolower("FeedList")){
		$retData = LazadaApiV3::getLazadaFeedList($reqParams);
	}
	
	if ($reqAction==strtolower("getFeedOffsetList")){
		$retData = LazadaApiV3::getLazadaFeedOffsetList($reqParams);
	}
	
	if ($reqAction==strtolower("getFeedDetail")){
		$retData = LazadaApiV3::getLazadaFeedDetail($reqParams);
	}
	
	if ($reqAction=='/category/attributes/get'){
		$retData = LazadaApiV3::getLazadaCategoryAttributes($reqParams);
	}
	
	if ($reqAction=='/category/tree/get'){
		$retData = LazadaApiV3::getLazadaCategoryTree($reqParams);
	}
	
	if ($reqAction=="/shipment/providers/get"){
		$retData = LazadaApiV3::getLazadaShipmentProviders($reqParams);
	}
	
	if ($reqAction == "/auth/token/create"){
	    $retData = LazadaApiV3::getAccessToken($reqParams);
	}
	
	if ($reqAction == "/auth/token/refresh"){
	    $retData = LazadaApiV3::refreshAccessToken($reqParams);
	}
	
	
	if ($reqAction == ""){
		echo "Action is required ! <br>";
	}
	else{
		if (!empty($retData)){
			echo json_encode($retData);
	
		}else{
			echo "result is empty !";
		}
	}
}
//write_log("234234","info");

handleRequest();


?>

