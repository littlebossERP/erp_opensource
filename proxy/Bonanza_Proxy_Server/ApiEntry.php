<?php

require_once(dirname(__FILE__)."/HttpHelper.php");
require_once(dirname(__FILE__)."/TimeUtil.php");
require_once(dirname(__FILE__)."/Utility.php"); 


// TODO proxy dev account @XXX@
// Bonaza开发者账号信息
$dev_name = "@XXX@";
$cert_name = "@XXX@";

$expected_token ="HE654HRYR,,SDFEdfsaaoi";

foreach (  $_GET  as $key => $value ){
	if (! isset ( ${$key} ))
		${$key} = $value;
}

foreach (  $_POST  as $key => $value ){
	if (! isset ( ${$key} ))
		${$key} = $value;
}
	if (! isset($action)) $action = "";
	

//pharse the parameters for this api call
	if (!empty($params))
		$params = json_decode($params,true);
	else
		$params = array();

	foreach (  $params  as $key => $value ){
		${$key} = $value;
	}

	$results = array();
	$results['success'] = false;
	$results['message'] = '';

	

	if (!isset($token) or $token<>$expected_token){
		$results['success'] = false;
		$results['success'] = "Token |$token| invalid";
		$results['rtn'] = '';
		$results['proxyURL'] = $url ;
		echo json_encode($results);
		return;		
	}
	
	if ($action=='fetchToken'){
		$urlAPI = "https://api.bonanza.com/api_requests/secure_request";
		$headers = array("X-BONANZLE-API-DEV-NAME: " . $dev_name, "X-BONANZLE-API-CERT-NAME: " . $cert_name);
		$args = array();
		$post_fields = "fetchTokenRequest";
		$url =$urlAPI;
		$connection = curl_init($url);
		$curl_options = array(CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$post_fields,
						CURLOPT_POST=>1, CURLOPT_RETURNTRANSFER=>1);  # data will be returned as a string
		curl_setopt_array($connection, $curl_options);
		$json_response = curl_exec($connection);
		if (curl_errno($connection) > 0) {
		  $results['success'] = false;
		  $results['message'] = curl_error($connection);
		  echo json_encode($results);
		  return;
		}
		curl_close($connection);
		//$response = json_decode($json_response,true);
		//$token = $response['fetchTokenResponse']['authToken'];
		 
		$results['success'] = true;
		$results['rtn'] = $json_response;
		$results['proxyURL'] = $url ;
	}
	
	/* For more details, please check article in 
	http://api.bonanza.com/docs/reference/get_orders
	如果想要执行 时间段的创建的订单，那么需要同时 specify createTimeFrom and createTimeTo,
	并且需要填写 国际标准时间 例如 2016-04-18T02:45:27Z ， which is 中国时间的 -15 个小时的
	invoken by e.g.: http://192.168.1.250/eagle_proxy_code/Bonanza_Proxy_Server/ApiEntry.php?token=HE654HRYR,,dgfas,,SDFEdfsaaoi&action=getOrders&BonanzaToken=ITSi9xpRfy&createTimeFrom=2016-04-18T02:45:27Z&createTimeTo=2016-04-19T23:45:27Z
	
	状态说明：
	Active: The order is in the cart, hasn't yet been checked out
	Cancelled: The order is cancelled
	Completed: The order is sold (payment has been successfully processed) but not yet marked as shipped
	Incomplete: There was an error processing payment for offer
	InProcess: The order has been checked out by the buyer and we're awaiting payment verification from the payment processor
	Invoiced: Seller has sent buyer an invoice for sold offer, offer is awaiting payment from buyer
	Proposed: Offer has been proposed by buyer and is awaiting acceptance
	Shipped: The order has been marked as shipped
	
	
	付款状态
	Complete: Checkout is complete for this order
	Incomplete: Order has not been through checkout, or order has some other status besides the two above.
	InProcess: Seller has completed checkout, we're currently awaiting payment information from the processor.
	Invoiced: Seller has accepted offer, invoice buyer, and checkout is pending buyer action.
	Pending: 
	
	
	*/
		if ($action=='getOrders'){
		$api_url = "https://api.bonanza.com/api_requests/secure_request";
		$headers = array("X-BONANZLE-API-DEV-NAME: " . $dev_name, "X-BONANZLE-API-CERT-NAME: " . $cert_name);
		$url =$urlAPI;
		$args = array();
		$orders = array();
		$args['requesterCredentials']['bonanzleAuthToken']=$BonanzaToken;
		if (!empty($modTimeFrom))
			$args['modTimeFrom']=$modTimeFrom;
		
		if (!empty($modTimeTo))
			$args['modTimeTo']=$modTimeTo;
		
		if (!empty($createTimeFrom))
			$args['createTimeFrom']=$createTimeFrom;
		
		if (!empty($createTimeTo))
			$args['createTimeTo']=$createTimeTo;
		
		if (!empty($pageNumber))
			$args['paginationInput']['pageNumber']=$pageNumber;
	 
	 
		do {
			if (!empty($resultArray['getOrdersResponse']['pageNumber']))
				$args['paginationInput']['pageNumber']=$resultArray['getOrdersResponse']['pageNumber'] + 1;
			
			$post_fields = "getOrdersRequest=" .  json_encode($args, JSON_HEX_AMP);
			//echo "Request: $post_fields \n";
			$connection = curl_init($api_url);
			$curl_options = array(CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$post_fields,
							CURLOPT_POST=>1, CURLOPT_RETURNTRANSFER=>1);  # data will be returned as a string
			curl_setopt_array($connection, $curl_options);
			$json_response = curl_exec($connection);
			if (curl_errno($connection) > 0) {
			  $results['success'] = false;
			  $results['message'] = curl_error($connection);
			  echo json_encode($results);
			  return;
			}
			curl_close($connection);

			$resultArray = json_decode($json_response,true);
			if (!empty($resultArray['getOrdersResponse']['orderArray'])){
				foreach ($resultArray['getOrdersResponse']['orderArray'] as $anOrder){
					$orders[] = $anOrder['order'];
				}
			}
			
		} while (!empty($resultArray['getOrdersResponse']['pageNumber']) and !empty($resultArray['getOrdersResponse']['paginationResult']['totalNumberOfPages'])
				and $resultArray['getOrdersResponse']['paginationResult']['totalNumberOfPages'] <> $resultArray['getOrdersResponse']['pageNumber']);
		
		 /*CURL api return sample:
{
  "ack": "Success",
  "version": "1.0beta",
  "timestamp": "2016-04-19T00:39:23.000Z",
  "getOrdersResponse": {
    "orderArray": [
      {
        "order": {
          "amountPaid": "7.48",
          "amountSaved": 0,
          "buyerCheckoutMessage": "",
          "buyerUserID": 18352505,
          "buyerUserName": "lbts",
          "checkoutStatus": {
            "status": "Complete"
          },
          "createdTime": "2016-04-19T02:06:38Z",
          "creatingUserRole": "Buyer",
          "itemArray": [
            {
              "item": {
                "itemID": 343462171,
                "sellerInventoryID": null,
                "sku": null,
                "title": "OZiO KFZ 1A Auto Ladegeru00e4t Zigarettenanzu00fcnder USB Adapter iPhone4/4s 30Pin Kable",
                "price": "7.48",
                "quantity": 1,
                "ebayId": "151607791023"
              }
            }
          ],
          "orderID": 39950298,
          "orderStatus": "Completed",
          "subtotal": 7.48,
          "taxAmount": 0,
          "total": "7.48",
          "transactionArray": {
            "transaction": {
              "buyer": {
                "email": "469604654@qq.com"
              },
              "providerName": "Paypal",
              "providerID": "8784941500650030N",
              "finalValueFee": "0.5"
            }
          },
          "paidTime": "2016-04-19T02:24:51Z",
          "shippingAddress": {
            "addressID": 21478023,
            "cityName": "london",
            "country": "GB",
            "countryName": null,
            "name": "minyan shen",
            "postalCode": "w10 5ds",
            "stateOrProvince": "LND",
            "street1": "flat f,17 sunbeam crescent",
            "street2": null
          },
          "shippingDetails": {
            "insuranceFee": 0,
            "amount": 0,
            "servicesArray": [],
            "shippingService": "Standard shipping",
            "notes": null
          }
        },
		  {
        "order": {
			......
		}
      }
    ],
    "hasMoreOrders": "false",
    "paginationResult": {
      "totalNumberOfEntries": 1,
      "totalNumberOfPages": 1
    },
    "pageNumber": 1
  }

*/
		$results['success'] = true;
		//$results['rtn'] = $json_response;
		$results['orders']=$orders;
		$results['proxyURL'] = $url ;
	}


	/* For more details, please check article in 
	http://api.bonanza.com/docs/reference/complete_sale
	completeSale allows feedback and tracking information to be left following a completed Bonanza order.
	parameter   action=accept/refuse/ship
	parameter   transactionId : order id, e.g. 39950298
	如果是mark ship，以下field需要提供
	parameter:  noteMessage , mark ship 的时候告诉客户的一句话, 需要先做url encode 才发过来
	parameter:  carrier: usps
						 ups
						 fedex
	                     international
						 other
	parameter:  trackNo
	
	*/
		if ($action=='completeSale'){
		$api_url = "https://api.bonanza.com/api_requests/secure_request";
		$headers = array("X-BONANZLE-API-DEV-NAME: " . $dev_name, "X-BONANZLE-API-CERT-NAME: " . $cert_name);
		$url =$urlAPI;
		$args = array();
		$args['requesterCredentials']['bonanzleAuthToken']=$BonanzaToken;
		if (!empty($operation) and $operation=="accept")
			$args['accept']=true;
		
		if (!empty($operation) and $operation=="refuse")
			$args['deny']=true;
		
		if (!empty($operation) and $operation=="ship")
			$args['shipped']=true;
		
		if (!empty($transactionId))
			$args['transactionID']=$transactionId;
		
		if (!empty($trackNo))
			$args['shipment']['shippingTrackingNumber']=$trackNo;
		
		if (!empty($noteMessage))
			$args['shipment']['notes']=trim($noteMessage);
		
		if (!empty($carrier))
			$args['shipment']['shippingCarrierUsed']=$carrier;

		$post_fields = "completeSale =" .  json_encode($args, JSON_HEX_AMP);
		//echo "Request: $post_fields \n";
		$connection = curl_init($api_url);
		$curl_options = array(CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$post_fields,
						CURLOPT_POST=>1, CURLOPT_RETURNTRANSFER=>1);  # data will be returned as a string
		curl_setopt_array($connection, $curl_options);
		$json_response = curl_exec($connection);
		if (curl_errno($connection) > 0) {
		  $results['success'] = false;
		  $results['message'] = curl_error($connection);
		  echo json_encode($results);
		  return;
		}
		curl_close($connection);
	
		$results['success'] = true;
		$results['rtn'] = $json_response;
		$results['proxyURL'] = $url ;
	}	
	
	
	
	
	if ($action=='getBooth'){
		$api_url = "http://api.bonanza.com/api_requests/standard_request";
		$headers = array("X-BONANZLE-API-DEV-NAME: " . $dev_name);
		$args = array("userId" => $userId);
		$url =$urlAPI;
		$post_fields = "getBoothRequest=" .  json_encode($args, JSON_HEX_AMP);
		//echo "Request: $post_fields \n";
		$connection = curl_init($api_url);
		$curl_options = array(CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$post_fields,
						CURLOPT_POST=>1, CURLOPT_RETURNTRANSFER=>1);  # data will be returned as a string
		curl_setopt_array($connection, $curl_options);
		$json_response = curl_exec($connection);
		if (curl_errno($connection) > 0) {
		  $results['success'] = false;
		  $results['message'] = curl_error($connection);
		  echo json_encode($results);
		  return;
		}
		curl_close($connection);
		 
		$results['success'] = true;
		$results['rtn'] = $json_response;
		$results['proxyURL'] = $url ;
	}
	
	if ($action =='getMultipleItems'){
		$api_url = "http://api.bonanza.com/api_requests/standard_request";
		$headers = array("X-BONANZLE-API-DEV-NAME: " . $dev_name);
		if (is_string($ids)){
			$ids = json_decode($ids);
		}
		$args = array("itemId" =>  $ids);
	
		//$args['requesterCredentials']['bonanzleAuthToken']=$BonanzaToken;
		$post_fields = "getMultipleItemsRequest=" .  json_encode($args, JSON_HEX_AMP);
		echo "Request: $post_fields \n";
		$connection = curl_init($api_url);
		$curl_options = array(CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$post_fields,
						CURLOPT_POST=>1, CURLOPT_RETURNTRANSFER=>1);  # data will be returned as a string
		curl_setopt_array($connection, $curl_options);
		$json_response = curl_exec($connection);
		if (curl_errno($connection) > 0) {
		  $results['success'] = false;
		  $results['message'] = curl_error($connection);
		  echo json_encode($results);
		  return;
		}
		curl_close($connection);
		
		$results['success'] = true;
		$results['rtn'] = $json_response;
		$results['proxyURL'] = $url ;
		$response = json_decode($json_response,true);
		/**/
		echo "  Loop thru the data \n";
		while (list(,$item) = each($response['getMultipleItemsResponse']['item'])) {
		  echo "  " . $item['currentPrice'] . " - " . $item['title'] . "\n";
		}
		echo "Response: \n";
		print_r($response);
	}
	
	
	if ($action =='getSingleItem'){
		$api_url = "http://api.bonanza.com/api_requests/standard_request";
		$headers = array("X-BONANZLE-API-DEV-NAME: " . $dev_name);
		if (is_string($ids)){
			$ids = json_decode($ids);
		}
		$args = array("itemId" => $ids);
		$post_fields = "getSingleItemRequest=" .  json_encode($args, JSON_HEX_AMP);
		//echo "Request: $post_fields \n";
		$connection = curl_init($api_url);
		$curl_options = array(CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$post_fields,
						CURLOPT_POST=>1, CURLOPT_RETURNTRANSFER=>1);  # data will be returned as a string
		curl_setopt_array($connection, $curl_options);
		$json_response = curl_exec($connection);
		if (curl_errno($connection) > 0) {
			$results['success'] = false;
			$results['message'] = curl_error($connection);
			echo json_encode($results);
			return;
		}
		curl_close($connection);
		
		$results['success'] = true;
		$results['rtn'] = json_decode($json_response,true);
		$results['proxyURL'] = $url ;
		$response = json_decode($json_response,true);
		
		//echo "Response: \n";
		//print_r($response);
	}
	
	//write_log("Step Done: Done action $action . ", "info");
	
	echo json_encode($results);

?>

