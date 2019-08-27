<?php
require_once(dirname(__FILE__) . "/Utility.php");
require_once(dirname(__FILE__) . "/LazadaApi.php");

//3个参数都是必须的,reqParam可以是array()
// array(
//   "config"=>array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
//   "action"=>"GetOrderList"
//   "reqParams"=>array("UpdatedAfter"=>"")
//)
function handleRequest()
{
    $return = array();
    $return["success"] = true;
    $return["message"] = "";
    $data = file_get_contents("php://input");
    $dataArr = json_decode($data, true);
//    var_dump($dataArr);
    if (!isset($_REQUEST["config"]) or !isset($_REQUEST["action"]) or !isset($_REQUEST["reqParams"])) {
        if ((!isset($dataArr["config"]) or !isset($dataArr["action"]) or !isset($dataArr["reqParams"]))) {
            write_log("request data: " . json_encode($_REQUEST));
            write_log("post data: " . print_r($_POST, true));
            $return["message"] = "Parameter config,reqParams and action should be included";
            $return["path"] = dirname(__FILE__);
            echo json_encode($return);
            return;
        }

    }

    write_log("handleRequest get:" . print_r($_REQUEST, true), "info");
    write_log("handleRequest get raw data:" . print_r($dataArr, true), "info");
    if (!isset($_REQUEST["config"])) {
        $config = $dataArr["config"];
        $reqParams = $dataArr["reqParams"];
        $reqAction = strtolower($dataArr["action"]);
    } else {
        $reqParams = json_decode($_REQUEST["reqParams"], true);
        $reqAction = strtolower($_REQUEST["action"]);
        $config = json_decode($_REQUEST["config"], true);
    }
    if (!isset($config["userId"]) or !isset($config["apiKey"]) or !isset($config["countryCode"])) {
        $return["message"] = "Parameter userId,apiKey,countryCode should be included";
        var_dump($config);
        echo json_encode($return);
        return;
    }

    LazadaApi::setConfig($config["userId"], $config["countryCode"], $config["apiKey"]);


    // $retData 返回的格式 必须与上面的数组 array("success"=>??? , "message"=>??? , )
    if ($reqAction == strtolower("GetOrderList")) {
        $retData = LazadaApi::getLazadaOrderList($reqParams);
    }

    if ($reqAction == strtolower("GetOrderDetail")) {
        $retData = LazadaApi::getLazadaOrderItems($reqParams);
    }

    if ($reqAction == strtolower("shipOrder")) {
        $retData = LazadaApi::shipLazadaOrder($reqParams);
    }

    if ($reqAction == strtolower("getProducts")) {
        $retData = LazadaApi::getLazadaProducts($reqParams);
    }

    if ($reqAction == strtolower("getFilterProducts")) {
        $retData = LazadaApi::getLazadaFilterProducts($reqParams);
    }

    if ($reqAction == strtolower("getBrands")) {
        $retData = LazadaApi::getLazadaBrands($reqParams);
    }

    if ($reqAction == strtolower("productCreate")) {
        $retData = LazadaApi::lazadaProductCreate($reqParams);
    }

    if ($reqAction == strtolower("productUpdate")) {
        $retData = LazadaApi::lazadaProductUpdate($reqParams);
    }

    if ($reqAction == strtolower("productDelete")) {
        $retData = LazadaApi::lazadaProductDelete($reqParams);
    }

    if ($reqAction == strtolower("productImage")) {
        $retData = LazadaApi::uploadLazadaProductImage($reqParams);
    }

    if ($reqAction == strtolower("FeedList")) {
        $retData = LazadaApi::getLazadaFeedList($reqParams);
    }

    if ($reqAction == strtolower("getFeedOffsetList")) {
        $retData = LazadaApi::getLazadaFeedOffsetList($reqParams);
    }

    if ($reqAction == strtolower("getFeedDetail")) {
        $retData = LazadaApi::getLazadaFeedDetail($reqParams);
    }

    if ($reqAction == strtolower("getCategoryAttributes")) {
        $retData = LazadaApi::getLazadaCategoryAttributes($reqParams);
    }

    if ($reqAction == strtolower("getCategoryTree")) {
        $retData = LazadaApi::getLazadaCategoryTree($reqParams);
    }

    if ($reqAction == strtolower("getShipmentProviders")) {
        $retData = LazadaApi::getLazadaShipmentProviders($reqParams);
    }

    if ($reqAction == "") {
        echo "Action is required ! <br>";
    } else {
        if (!empty($retData)) {
            echo json_encode($retData);

        } else {
            echo "result is empty !";
        }
    }
}

//write_log("234234","info");

handleRequest();


?>

