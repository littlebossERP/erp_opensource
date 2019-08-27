<?php
class MockDataHelper {
	
	const ItemsNumber=2;
	
	public static function getReturnData($action){
		//if
	}
	
	
	public function getFileContent($filename){
		$handle = fopen($filename, 'r');
		$content = '';
		while(false != ($a = fread($handle, 8080))){//返回false表示已经读取到文件末尾
			$content .= $a;
		}
		return $content;
	}
	
	private function _getOrderIds($number){
		$orderIdsArr=array();
		$randomCode=time()*1000+rand(1,100);
		for($i=0;$i<$number;$i++){
			$orderId=$randomCode+$i;
			$orderIdsArr[]=$orderId;
		}		
		return $orderIdsArr;		
	}
	private function _getOneOrderHeaderMockData($orderId,$status="Unshipped"){
		$baseOrderHeader=array("AmazonOrderId"=>"028-8281395-6794762",
				"PurchaseDate"=>"2014-06-18T08:00:32Z",
				"LastUpdateDate"=>"2014-06-20T08:22:38Z",
				"Status"=>"Shipped",
				"SalesChannel"=>"Amazon.de",
				"ShipServiceLevel"=>"Std DE Dom",
				"Name"=>"Petra Horstmann",
				"AddressLine1"=>"I.d. Hummelkn e4ppen 31",
				"City"=>"fcnen",
				"State"=>"NRW",
				"PostalCode"=>"44534",
				"CountryCode"=>"DE",
				"Phone"=>"0230661337",
				"Currency"=>"EUR",
				"Amount"=>"6.99",
				"PaymentMethod"=>"Other",
				"BuyerEmail"=>"kqvrk3tnx80r3yn@marketplace.amazon.de"
		);
		$randomCode=time()."-".rand(1,5000);
		$baseOrderHeader["AmazonOrderId"]=$orderId;
		$baseOrderHeader["Status"]=$status;
		$baseOrderHeader["BuyerEmail"]="em".$randomCode."@marketplace.amazon.de";
		
		return $baseOrderHeader;
		
	}
	//获取订单列表的mock数据
	//{"order":[{"AmazonOrderId":"028-8281395-6794762","PurchaseDate":"2014-06-18T08:00:32Z","LastUpdateDate":"2014-06-20T08:22:38Z","Status":"Shipped","SalesChannel":"Amazon.de","ShipServiceLevel":"Std DE Dom","Name":"Petra Horstmann","AddressLine1":"I.d. Hummelkn\u00e4ppen 31","City":"L\u00fcnen","State":"NRW","PostalCode":"44534","CountryCode":"DE","Phone":"0230661337","Currency":"EUR","Amount":"6.99","PaymentMethod":"Other","BuyerEmail":"kqvrk3tnx80r3yn@marketplace.amazon.de"}],"message":"","success":true,"retryCount":0}
	public function getOrderListMockData(){
	//	return $this->getFileContent("getorder_mockdata.json");
	    $ordersHeaderInfo=array();
	    $orderNumber=9;
		$orderIdsArr=$this->_getOrderIds($orderNumber);
		foreach($orderIdsArr as $orderId){
			$oneOrderHeader=$this->_getOneOrderHeaderMockData($orderId);
			$ordersHeaderInfo[]=$oneOrderHeader;
		}

		$result=array();
		$result["order"]=$ordersHeaderInfo;
		$result["message"]="";
		$result["success"]=true;
		$result["retryCount"]=0;
		return json_encode($result);
		
	}
	
	private function _getOneItemMockData($sku){
		$baseItem=array("ASIN"=>"B00H6XIV78",
				        "SellerSKU"=>"SKAMSSHCPU9808A",
				        "OrderItemId"=>"55358211296267",
						"Title"=>"Samsung Galaxy S3 Stil",
						"QuantityOrdered"=>"1",
						"QuantityShipped"=>"1",
						"ItemPrice"=>8.49,
						"ShippingPrice"=>"0.00",
						"GiftWrapPrice"=>"0.00",
						"ItemTax"=>"0.00",
						"ShippingTax"=>"0.00",
						"GiftWrapTax"=>"0.00",
						"ShippingDiscount"=>"0.00",
						"PromotionDiscount"=>"0.00");
		
		$baseItem["SellerSKU"]=$sku;
		$randomCode=time()."".rand(1,1000);
		$baseItem["OrderItemId"]=$randomCode;
		$baseItem["Title"]="iphone case ".$randomCode;
		return $baseItem;
		
	}
	//需要在eagle中预先创建好 sku001~~sku009的商品
	private function _getSkuList($itemsNumber){
		$skuList=array();
		for($i=0;$i<$itemsNumber;$i++){
			$sku="sku00".rand(1,9);
			while (in_array($sku,$skuList)){
				$sku="sku00".rand(1,9);
				if (!in_array($sku,$skuList)) break;
			}
			$skuList[]=$sku;
		}		
		return $skuList;
	}
	
	//get order items
	//'{"item":[{"ASIN":"B00H6XIV78","SellerSKU":"SKAMSSHCPU9808A","OrderItemId":"55358211296267","Title":"Samsung Galaxy S3 H\u00fclle mit Kamelie im Luxus Stil","QuantityOrdered":"1","QuantityShipped":"1","ItemPrice":8.49,"ShippingPrice":"0.00","GiftWrapPrice":"0.00","ItemTax":"0.00","ShippingTax":"0.00","GiftWrapTax":"0.00","ShippingDiscount":"0.00","PromotionDiscount":"0.00"}],"message":"","success":true,"retryCount":0}';
	public function getOrderItemsMockData(){
		//$baseItemInfoJson='{"item":[{"ASIN":"B00H6XIV78","SellerSKU":"SKAMSSHCPU9808A","OrderItemId":"55358211296267","Title":"Samsung Galaxy S3 H\u00fclle mit Kamelie im Luxus Stil","QuantityOrdered":"1","QuantityShipped":"1","ItemPrice":8.49,"ShippingPrice":"0.00","GiftWrapPrice":"0.00","ItemTax":"0.00","ShippingTax":"0.00","GiftWrapTax":"0.00","ShippingDiscount":"0.00","PromotionDiscount":"0.00"}],"message":"","success":true,"retryCount":0}';
	   // $randomCode=time();  "message":"","success":true,"retryCount":0}'
        $itemsInfo=array();
	    $itemsNumber=self::ItemsNumber;
	    $skuList=$this->_getSkuList($itemsNumber);
	    foreach($skuList as $sku){
	    	$oneItem=$this->_getOneItemMockData($sku);
	    	$itemsInfo[]=$oneItem;
	    }
	    
	    $result=array();
	    $result["item"]=$itemsInfo;
	    $result["message"]="";
	    $result["success"]=true;
	    $result["retryCount"]=0;
	    return json_encode($result);
		
	}
	
	//添加amazon要求修改订单状态为已发货
	public function shipOrder(){
		//{"message":"","exception":0,"success":true,"submit_id":"10279935216","retryCount":0,"XMLFeedData":"<Header>mock data returns</Header>"}
		$randomCode=time().rand(1,5000);
		$result=array("exception"=>0,
				"submit_id"=>$randomCode,			
				"XMLFeedData"=>"<Header>mock data returns</Header>");
		$result["message"]="";
		$result["success"]=true;
		$result["retryCount"]=0;
		return json_encode($result);
		
	}
	
	//检查指定sumbit id的amazon作业的进度
	public function getSubmitFeedResult(){
		$result=array();
		$result['success'] = true;
		$result['ProcessingReport']=array();
		$result['ProcessingReport']['status'] ="Complete";
		return json_encode($result);
	}
}

?>

