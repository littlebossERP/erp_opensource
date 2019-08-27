<?php
require_once(dirname(__FILE__)."/Utility.php");
require_once(dirname(__FILE__)."/LazadaApiV2.php"); 

//3个参数都是必须的,reqParam可以是array()
// array(
//   "config"=>array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
//   "action"=>"GetOrderList"
//   "reqParams"=>array("UpdatedAfter"=>"")
//)
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
	
	write_log("handleRequest get:".json_encode($_REQUEST),"info");
	$config=json_decode($_REQUEST["config"],true);	
	if (!isset($config["userId"]) or !isset($config["apiKey"]) or !isset($config["countryCode"])){
		$return["message"] = "Parameter userId,apiKey,countryCode should be included";
		echo json_encode($return);
		return;
	}
		
	LazadaApiV2::setConfig($config["userId"], $config["countryCode"], $config["apiKey"]);
	$reqParams=json_decode($_REQUEST["reqParams"],true);
	$reqAction = strtolower($_REQUEST["action"]);	
	
	// $retData 返回的格式 必须与上面的数组 array("success"=>??? , "message"=>??? , )
	if ($reqAction==strtolower("GetOneOrder")){
	    $retData = LazadaApiV2::getLazadaOneOrder($reqParams);
	}
	
	if ($reqAction==strtolower("GetOrderList")){		
		$retData = LazadaApiV2::getLazadaOrderList($reqParams);
	}
	
	if ($reqAction==strtolower("GetOrderDetail")){
		$retData = LazadaApiV2::getLazadaOrderItems($reqParams);
	}
	
	if ($reqAction==strtolower("packedByMarketplace")){
	    $retData = LazadaApiV2::packedByMarketplace($reqParams);
	}
	
	if ($reqAction==strtolower("shipOrder")){
		$retData = LazadaApiV2::shipLazadaOrder($reqParams);
	}
	
	if ($reqAction==strtolower("GetDocument")){
		$retData = LazadaApiV2::getDocument($reqParams);
	}
	
	if ($reqAction==strtolower("GetPayoutStatus")){
	    $retData = LazadaApiV2::getPayoutStatus($reqParams);
	}
	
	if ($reqAction==strtolower("GetMetrics")){
	    $retData = LazadaApiV2::getMetrics($reqParams);
	}
	
	if ($reqAction==strtolower("getProducts")){
        $retData = LazadaApiV2::getLazadaProducts($reqParams);
	}
	
	if ($reqAction==strtolower("getFilterProducts")){
		$retData = LazadaApiV2::getLazadaFilterProducts($reqParams);
	}
	
	if ($reqAction==strtolower("searchSPUs")){
	    $retData = LazadaApiV2::searchSPUs($reqParams);
	}
	
	if ($reqAction==strtolower("getQcStatus")){
	    $retData = LazadaApiV2::getLazadaQcStatus($reqParams);
	}
	
	if ($reqAction==strtolower("getStatistics")){
	    $retData = LazadaApiV2::getLazadaStatistics($reqParams);
	}
	
	if ($reqAction==strtolower("getBrands")){
		$retData = LazadaApiV2::getLazadaBrands($reqParams);
	}
	
	if ($reqAction==strtolower("productCreate")){
		$retData = LazadaApiV2::lazadaProductCreate($reqParams);
	}
	
	if ($reqAction==strtolower("productUpdate")){
		$retData = LazadaApiV2::lazadaProductUpdate($reqParams);
	}
	
	if ($reqAction==strtolower("productUpdatePriceQuantity")){
	    $retData = LazadaApiV2::lazadaProductUpdatePriceQuantity($reqParams);
	}
	
	if ($reqAction==strtolower("productDelete")){
		$retData = LazadaApiV2::lazadaProductDelete($reqParams);
	}
	
	if ($reqAction==strtolower("migrateImage")){
	    $retData = LazadaApiV2::migrateLazadaImage($reqParams);
	}
	
	if ($reqAction==strtolower("setImage")){
	    $retData = LazadaApiV2::setLazadaImage($reqParams);
	}
	
	if (strtolower($reqAction) == strtolower("getOrderItemImage")){
		$retData = LazadaApiV2::getLazadaOrderItemImage($reqParams);
	}
	
	if ($reqAction==strtolower("FeedList")){
		$retData = LazadaApiV2::getLazadaFeedList($reqParams);
	}
	
	if ($reqAction==strtolower("getFeedOffsetList")){
		$retData = LazadaApiV2::getLazadaFeedOffsetList($reqParams);
	}
	
	if ($reqAction==strtolower("getFeedDetail")){
		$retData = LazadaApiV2::getLazadaFeedDetail($reqParams);
	}
	
	if ($reqAction==strtolower("getCategoryAttributes")){
		$retData = LazadaApiV2::getLazadaCategoryAttributes($reqParams);
	}
	
	if ($reqAction==strtolower("getCategoryTree")){
		$retData = LazadaApiV2::getLazadaCategoryTree($reqParams);
	}
	
	if ($reqAction==strtolower("getShipmentProviders")){
		$retData = LazadaApiV2::getLazadaShipmentProviders($reqParams);
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

