<?php
namespace common\api\newegginterface;

use yii\base\Exception;
use eagle\modules\util\helpers\SysLogHelper;
class NeweggInterface_Helper
{
    // 测试/公共接口使用的用户账号信息
    //  TODO newegg user account @XXX@
	static $token = [
		'SellerID' => '@XXX@',
		'Authorization' => '@XXX@',
		'SecretKey' => '@XXX@'
	];
	
	// dzt20200402 for 请求卡死，导致同步卡死
	public static $connecttimeout = 10;
	public static $timeout = 60;
	
	/**
	 * 标记订单已经下载了
	 */
	public static function orderConfirm($token, $params){
		$config = [
			'addUrl' => 'ordermgmt/orderstatus/orders/confirmation',
			'type' => 'post',
		];
		
		$orderList = '';
		if(isset($params['orderList']) && !empty($params['orderList'])){
			foreach ($params['orderList'] as $o){
				$orderList .= '<OrderNumber>'.$o.'</OrderNumber>';
			}
		}
		if(empty($orderList)){
			return false;
		}
		$reqParams = [
			'body' => '	<NeweggAPIRequest >
							<OperationType>OrderConfirmationRequest</OperationType>
							<RequestBody>
								<DownloadedOrderList>
									'.$orderList.'
								</DownloadedOrderList>
							</RequestBody>
						</NeweggAPIRequest>
					'
		];
		
		return self::callAPI($token, $config, $reqParams);
	}
	
	/**
	 * 标记发货
	 */
	public static function shipOrder($token, $params){
		//标记发货
		$config = [
			'addUrl' => 'ordermgmt/orderstatus/orders/'.$params['orderNumber'],
			'type' => 'put',
		];
		
		$items = '';
		foreach ($params['items'] as $i){
			$items .= '
					<Item>
						<SellerPartNumber>'.$i['SellerPartNumber'].'</SellerPartNumber>
						<ShippedQty>'.$i['ShippedQty'].'</ShippedQty>
					</Item>';
		}
		
		$reqParams = [
			'body' => '<UpdateOrderStatus>
						<Action>2</Action>
						<Value>
							<![CDATA[
							<Shipment>
								<Header>
									<SellerID>'.$token['SellerID'].'</SellerID>
									<SONumber>'.$params['orderNumber'].'</SONumber>
								</Header>
							<PackageList>
								<Package>
									<TrackingNumber>'.$params['tracking_number'].'</TrackingNumber>
									<ShipCarrier>'.$params['shipping_method_code'].'</ShipCarrier>
									<ShipService>Other Service</ShipService>
									<ItemList>'.$items.'</ItemList>
								</Package>
							</PackageList>
							</Shipment>
							]]>
						</Value>
						</UpdateOrderStatus>
					'
		
		];
		
// 		print_r($reqParams);
		
		return self::callAPI($token, $config, $reqParams);
	}
	
	/**
	 * 拉取订单信息
	 */
	public static function orderInfo($token, $req_params){
		//订单
		$config = [
			'addUrl' => 'ordermgmt/order/orderinfo',
			'type' => 'put',
		];
		//组织指定订单
		$orderList = '';
		if(isset($req_params['order_list']) && !empty($req_params['order_list'])){
			foreach ($req_params['order_list'] as $o){
				$orderList .= '<OrderNumber>'.$o.'</OrderNumber>';
			}
			if(!empty($orderList)){
				$orderList = '<OrderNumberList>'.$orderList.'</OrderNumberList>';
			}
		}
		
		$reqParams = [
			'body' => '<NeweggAPIRequest>
				<OperationType>GetOrderInfoRequest</OperationType>
				<RequestBody>
					<PageIndex>'.$req_params['PageIndex'].'</PageIndex>
					<RequestCriteria>'.
						$orderList.
						(($req_params['Status'] != '' && in_array($req_params['Status'], [0,1,2,3,4]))?'<Status>'.@$req_params['Status'].'</Status>':'').
						'<OrderDateFrom>'.$req_params['start_time'].'</OrderDateFrom>
						<OrderDateTo>'.$req_params['end_time'].'</OrderDateTo>
						<OrderDownloaded>'.$req_params['downloaded'].'</OrderDownloaded>
					</RequestCriteria>
				</RequestBody>
				</NeweggAPIRequest>'
		];
		print_r($req_params);
		return self::callAPI($token, $config, $reqParams);
	}
	
	public static function warehouseInfo(){
		//仓库
		$config = [
			'addUrl' => 'sbnmgmt/inboundshipment/warehouse',
			'type' => 'put',
		];
		$reqParams = [
			'body' => '<NeweggAPIRequest>
				<OperationType>GetWarehouseRequest</OperationType>
				<RequestBody>
   
				</RequestBody>
				</NeweggAPIRequest>'
		];
		
		return self::callAPI(self::$token, $config, $reqParams);
	}
	
	
	/**
	 * 获取账号状态
	 */
	public static function accountStatus($token){
		//账号状态
		$config = [
			'addUrl' => 'sellermgmt/seller/accountstatus',
			'type' => 'get',
		];
		$reqParams = '';
		
		return self::callAPI($token, $config, $reqParams);
	}
	
	public static function getItem(){
		
		$config = [
			'addUrl' => 'contentmgmt/item/inventory',
			'type' => 'post',
		];
		$reqParams = [
			'body' => '<ContentQueryCriteria>
				<Type>1</Type>
				<Value>CBGD16072202A</Value>
				</ContentQueryCriteria>'
		];
		
		return self::callAPI(self::$token, $config, $reqParams);
	}
	
	public static function callAPI($token, $config, $reqParams = array(), $timeout = 60){
		//echo "\n callAPI params:";
		//print_r($token);
		//print_r($config);
		//print_r($reqParams);
		if(!empty($timeout)){// dzt20200402
		    self::$timeout = $timeout;
		}
		
		$SellerID = @$token['SellerID'];
		$Authorization = @$token['Authorization'];
		$Secretkey = @$token['SecretKey'];
		
		error_reporting(E_ALL);
		
		// request URL
		$request = 'https://api.newegg.com/marketplace/'.$config['addUrl'].'?sellerid='.$SellerID;
		$header_array =array('Content-Type:application/xml',
				'Accept:application/json',
				'Authorization: '.$Authorization,
				'SecretKey: '.$Secretkey);
		try
		{
			// Get the curl session object
			//echo "\n curl URL:";
			//print_r($request);
			//echo "\n curl header_array:";
			//print_r($header_array);
			$session = curl_init($request);
			/*Post*/
			if(isset($config['type']) && ( $config['type'] == 'post' || $config['type'] == 'put') ){
				$putString = stripslashes($reqParams['body']);
				$putData = tmpfile();
				fwrite($putData, $putString);
				fseek($putData, 0);
				
				if($config['type'] == 'post'){
					curl_setopt($session, CURLOPT_POST, true);
				}
				else if($config['type'] == 'put'){
					curl_setopt($session, CURLOPT_PUT, true);
				}
				//echo "\n curl CURLOPT_POSTFIELDS:";
				//print_r($reqParams['body']);
				curl_setopt($session, CURLOPT_POSTFIELDS, $reqParams['body']);
				curl_setopt($session, CURLOPT_INFILE, $putData);
				curl_setopt($session, CURLOPT_INFILESIZE, strlen($putString));
			}
			// Set options.
			curl_setopt($session, CURLOPT_HEADER, 1);
			curl_setopt($session,CURLOPT_HTTPHEADER,$header_array);
			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);
			
			// dzt20200402 
			curl_setopt($session, CURLOPT_CONNECTTIMEOUT, self::$connecttimeout);
			curl_setopt($session, CURLOPT_TIMEOUT, self::$timeout);
			
			// Do the POST/GET and then close the session
			$response = curl_exec($session);
			//echo "\n curl response:";
			//print_r($response);
			SysLogHelper::SysLog_Create('Newegg',__CLASS__, __FUNCTION__,'info',is_string($response)?$response:json_encode($response));
			curl_close($session);
			//解析
			$response = json_decode(trim($response,chr(239).chr(187).chr(191)),true);
			
			return $response;
		}
		catch (\InvalidArgumentException $e)
		{
			curl_close($session);
			return $e;
		}
		catch (Exception $e)
		{
			curl_close($session);
			return $e;
		}
	}
}

?>