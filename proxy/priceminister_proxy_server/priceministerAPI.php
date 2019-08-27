<?php
class PriceministerAPI{
	static $PRICEMINISTER_LOCATION_URL = "https://wsvc.priceminister.com/MarketplaceAPIService.svc";
	static $PRICEMINISTER_ACTION_PREFIX_URL = "http://www.priceminister.com/IMarketplaceAPIService/";
	static $PRICEMINISTER_SOAP_URL = "https://wsvc.priceminister.com/MarketplaceAPIService.svc?wsdl";
	
	static $PRICEMINISTER_TOKEN_ID;
	
	static function set_priceminister_config($config){
		self::$PRICEMINISTER_TOKEN_ID = $config['tokenid'];
	}//end of set_priceminister_config
	
	static function AUTH_VALIDATION($username , $password){
		$success = true;
		$message = "";
		$url =  "https://sts.priceminister.com/users/httpIssue.svc/?realm=https://wsvc.priceminister.com/MarketplaceAPIService.svc";
		
		//create a URL-encode string
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);// 获取的输出的文本流
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Authorization: Basic '.base64_encode($username.":".$password))); 
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		
		curl_setopt($ch, CURLOPT_HEADER, FALSE);    //not show response header
		curl_setopt($ch, CURLOPT_NOBODY, FALSE); //show response body
		// cancel validate ssl  start 
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		// cancel validate ssl  end 
		
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);//set up timeout 

		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($ch);
		// print curl_error($ch);
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if ($httpCode != 200){
			//@todo makelog 
			$message .= $httpCode;
			$message .=  curl_error($ch);
			$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$body = substr($response, $headerSize);
			// var_dump($body);
			$success = false;
		}else{
			$response = Utility::getUserTokenIDFromXMLString($response );
		}
		
		// echo "<br> $httpCode url($username , $password) :  <br> $url <br> ".print_r($response ,true);//test kh
		curl_close($ch);
		//var_dump($response);
		//exit();
		return array('tokenMessage'=>$response , 'message'=>$message , 'success'=>$success);
	}//end of AUTH_VALIDATION
		
	
	static function GetOrderList($params = array()){
		$success = true;
		$message = "";
		try
		{
			
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			
			
			$orderfilterFieldName  = array(
				'begincreationdate' => 'BeginCreationDate' , 
				'beginmodificationdate' => 'BeginModificationDate' , 
				'endcreationdate' => 'EndCreationDate' , 
				'endmodificationdate' => 'EndModificationDate' , 
			);
			$orderFilterXML="";
			
			foreach($params as $key => $value){
				
				if (array_key_exists(strtolower($key) ,$orderfilterFieldName)){
					if ( ($orderfilterFieldName [strtolower($key) ] != "") )
						$orderFilterXML .= "<".$orderfilterFieldName [strtolower($key) ] .">$value</".$orderfilterFieldName [strtolower($key) ] .">";
				}
			}
			$orderFilterXML .="<FetchOrderLines>true</FetchOrderLines>";
			foreach($params as $key => $value){
				$state_xml = "";
				if (strtolower($key) == 'state'){
					foreach($value as $Astate){
						if (trim($Astate) != "")
						$state_xml .= "<OrderStateEnum>$Astate</OrderStateEnum>";
					}
					
					if (strlen($state_xml) > 0 ){
						$orderFilterXML .= "<States>$state_xml</States>";
					}
				}
			}
			
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			$xmldata = <<<SoapDocument
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
	<s:Body>
		<GetOrderList xmlns="http://www.priceminister.com">
			<headerMessage xmlns:a="http://schemas.datacontract.org/2004/07/Priceminister.Framework.Core.Communication.Messages" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
				<a:Context>
					<a:CatalogID>1</a:CatalogID>
					<a:CustomerPoolID>1</a:CustomerPoolID>
					<a:SiteID>100</a:SiteID>
				</a:Context>
				<a:Localization>
					<a:Country>Fr</a:Country>
					<a:Currency>Eur</a:Currency>
					<a:DecimalPosition>2</a:DecimalPosition>
					<a:Language>Fr</a:Language>
				</a:Localization>
				<a:Security>
					<a:DomainRightsList i:nil="true" />
					<a:IssuerID i:nil="true" />
					<a:SessionID i:nil="true" />
					<a:SubjectLocality i:nil="true" />
					<a:TokenId>$tokenID</a:TokenId>
					<a:UserName i:nil="true" />
				</a:Security>
				<a:Version>1.0</a:Version>
			</headerMessage>
			<orderFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
				$orderFilterXML
			</orderFilter>
		</GetOrderList>
	</s:Body>
</s:Envelope>
SoapDocument;


			$location = self::$PRICEMINISTER_LOCATION_URL;
			$action = self::$PRICEMINISTER_ACTION_PREFIX_URL.__function__;
			$response = $soap_client->__doRequest($xmldata ,$location , $action , 0);
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
			
		}catch (Exception $e)
		{
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('orderList'=>$response , 'message'=>$message , 'success'=>$success);
	}//end of GetOrderList
	
	static function AccepteOrRefuseOrders($params){
		try{
			$message = "";
			$success = true;
			
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			//set tokenID
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			//set params start 

			$validateOrderListMessage=array();
			foreach($params as $key => $value){
				if (strtolower($key) == 'orderids'){
					if (!empty($value)){
						if(!empty($params['state']))
							$state = $params['state'];
						else
							$state = 'AcceptedBySeller';
						
						foreach($value as $orderid){
							$ValidateOrder=array();
							if(!empty($orderid)){
								$ValidateOrder['OrderNumber'] = $orderid;
								$ValidateOrder['OrderState'] = $state;
								$validateOrderListMessage['OrderList']['ValidateOrder'] = $ValidateOrder;
							}
						}
						
					}
				}
			}
			
			//generate the whole params
			$params=array(
				"headerMessage"=>array(
						'Context' => array(
								'CatalogID'      => 1,
								'CustomerPoolID' => 1,
								'SiteID'         => 100,
						),
						'Localization' => array(
								'Country'         => 'Fr',
								'Currency'        => 'Eur',
								'DecimalPosition' => '2',
								'Language'        => 'Fr',
						),
						'Security' => array(
								'TokenId'		 => $tokenID ,
						),
						
						'Version' => '1.0',
				 ),
				'validateOrderListMessage' => $validateOrderListMessage ,
				
			);
			//set params end
			//call cd api
			$response = $soap_client->ValidateOrderList($params);
			
			//convert xml to array 
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
		}catch(Exception $e){
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('ValidateOrderList'=>$response , 'message'=>$message , 'success'=>$success );
	}//end of AccepteOrRefuseOrders
	
	static function ShippedOrderList($params){
		try{
			
			if (count($params['items']) <= 0 || !isset($params['items'])) {
				$message = "not found item! ";
				$success = false;
				return array('ValidateOrderList'=>"" , 'message'=>$message , 'success'=>$success );
			}
			
			$message = "";
			$success = true;
			
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			
			$orderid = $params['orderid']; 
			$OrderState = $params['orderstate']; 
			$TrackingNumber = $params['TrackingNumber'] ;
			$CarrierName = $params['CarrierName'] ;
			$TrackingUrl = $params['TrackingUrl'];
			foreach($params['items'] as $item){
				if (empty($item['SellerProductId'])) continue;
				if (strtoupper($item['SellerProductId']) == 'INTERETBCA') continue;
				$OrderLineListXML .= "
				<ValidateOrderLine>
					<AcceptationState>".$params['AcceptationState']."</AcceptationState>
					<ProductCondition>New</ProductCondition>
					<SellerProductId>".$item['SellerProductId']."</SellerProductId>
				</ValidateOrderLine>
				";
			}
			
			
			
		$xmldata = <<<SoapDocument
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <ValidateOrderList xmlns="http://www.priceminister.com">
            <headerMessage xmlns:a="http://schemas.datacontract.org/2004/07/Priceminister.Framework.Core.Communication.Messages" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <a:Context>
                    <a:CatalogID>1</a:CatalogID>
                    <a:CustomerPoolID>1</a:CustomerPoolID>
                    <a:SiteID>100</a:SiteID>
                </a:Context>
                <a:Localization>
                    <a:Country>Fr</a:Country>
                    <a:Currency>Eur</a:Currency>
                    <a:DecimalPosition>2</a:DecimalPosition>
                    <a:Language>Fr</a:Language>
                </a:Localization>
                <a:Security>
                    <a:DomainRightsList i:nil="true" />
                    <a:IssuerID i:nil="true" />
                    <a:SessionID i:nil="true" />
                    <a:SubjectLocality i:nil="true" />
                    <a:TokenId>$tokenID</a:TokenId>
                    <a:UserName i:nil="true" />
                </a:Security>
                <a:Version>1.0</a:Version>
            </headerMessage>
            <validateOrderListMessage xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <OrderList>
                    <ValidateOrder>
                        <CarrierName>$CarrierName</CarrierName>
                        <OrderLineList>
                            $OrderLineListXML
                        </OrderLineList>
                        <OrderNumber>$orderid</OrderNumber>
                        <OrderState>$OrderState</OrderState>
                        <TrackingNumber>$TrackingNumber</TrackingNumber>
                        <TrackingUrl>$TrackingUrl</TrackingUrl>
                    </ValidateOrder>
                </OrderList>
            </validateOrderListMessage>
        </ValidateOrderList>
    </s:Body>
</s:Envelope>
SoapDocument;
			$location = self::$PRICEMINISTER_LOCATION_URL;
			$action = self::$PRICEMINISTER_ACTION_PREFIX_URL.'ValidateOrderList';
			$response = $soap_client->__doRequest($xmldata ,$location , $action , 0);
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
		}catch(Exception $e){
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('ValidateOrderList'=>$response , 'message'=>$message , 'success'=>$success );
	}//end of ValidateOrderList
	
	
	static function GenerateDiscussionMailGuid($orderid){
		$success = true;
		$message = "";
		try
		{
			
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			
			$xmldata = <<<SoapDocument
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cdis="http://www.priceminister.com" xmlns:cdis1="http://schemas.datacontract.org/2004/07/Priceminister.Framework.Core.Communication.Messages" xmlns:sys="http://schemas.datacontract.org/2004/07/System.Device.Location" xmlns:cdis2="http://schemas.datacontract.org/2004/07/Priceminister.Service.Marketplace.API.External.Contract.Data.Mail">
   <soapenv:Header/>
   <soapenv:Body>
      <cdis:GenerateDiscussionMailGuid>
         <cdis:headerMessage>
            <cdis1:Context>
               <cdis1:CatalogID>1</cdis1:CatalogID>
               <cdis1:SiteID>100</cdis1:SiteID>
            </cdis1:Context>
            <cdis1:Localization>
               <cdis1:Country>Fr</cdis1:Country>
               <cdis1:Currency>Eur</cdis1:Currency>
               <cdis1:Language>Fr</cdis1:Language>
            </cdis1:Localization>
         
            <cdis1:Security>
               <cdis1:DomainRightsList/>
               <cdis1:IssuerID/>
               <cdis1:SessionID/>
               <cdis1:TokenId>$tokenID</cdis1:TokenId>
               <cdis1:UserName/>
            </cdis1:Security>
            <cdis1:Version>1.0</cdis1:Version>
         </cdis:headerMessage>
     
         <cdis:request>
            <cdis2:ScopusId>$orderid</cdis2:ScopusId>
         </cdis:request>
      </cdis:GenerateDiscussionMailGuid>
   </soapenv:Body>
</soapenv:Envelope>	
SoapDocument;


			$location = self::$PRICEMINISTER_LOCATION_URL;
			$action = self::$PRICEMINISTER_ACTION_PREFIX_URL.__function__;
			$response = $soap_client->__doRequest($xmldata ,$location , $action , 0);
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
			
		}catch (Exception $e)
		{
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('emailMessage'=>$response , 'message'=>$message , 'success'=>$success );
	}//end of GenerateDiscussionMailGuid
	
	
	//product
	static function GetProductList($params = array()){
		$success = true;
		$message = "";
		try
		{
			
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			$CategoryCode = '';
			if(!empty($params['CategoryCode']))
				$CategoryCode = "<CategoryCode>".$params['CategoryCode']."</CategoryCode>";
			
			$xmldata = <<<SoapDocument
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetProductList xmlns="http://www.priceminister.com">
            <headerMessage xmlns:a="http://schemas.datacontract.org/2004/07/Priceminister.Framework.Core.Communication.Messages" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <a:Context>
                    <a:CatalogID>1</a:CatalogID>
                    <a:CustomerPoolID>1</a:CustomerPoolID>
                    <a:SiteID>100</a:SiteID>
                </a:Context>
                <a:Localization>
                    <a:Country>Fr</a:Country>
                    <a:Currency>Eur</a:Currency>
                    <a:DecimalPosition>2</a:DecimalPosition>
                    <a:Language>Fr</a:Language>
                </a:Localization>
                <a:Security>
                    <a:DomainRightsList i:nil="true" />
                    <a:IssuerID i:nil="true" />
                    <a:SessionID i:nil="true" />
                    <a:SubjectLocality i:nil="true" />
                    <a:TokenId>$tokenID</a:TokenId>
                    <a:UserName i:nil="true" />
                </a:Security>
                <a:Version>1.0</a:Version>
            </headerMessage>
            <productFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                $CategoryCode
            </productFilter>
        </GetProductList>
    </s:Body>
</s:Envelope>
SoapDocument;


			$location = self::$PRICEMINISTER_LOCATION_URL;
			$action = self::$PRICEMINISTER_ACTION_PREFIX_URL.__function__;
			$response = $soap_client->__doRequest($xmldata ,$location , $action , 0);
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
			
		}catch (Exception $e)
		{
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('productlist'=>$response , 'message'=>$message , 'success'=>$success );
	}//end of GetProductList
	
	//GetOrderClaimList
	static function GetOrderClaimList($params = array()){
		$success = true;
		$message = "";
		try
		{
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			//set tokenID
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			//set params start 
			$orderfilterFieldName  = array(
				'begincreationdate' => 'BeginCreationDate' , 
				'beginmodificationdate' => 'BeginModificationDate' , 
				'endcreationdate' => 'EndCreationDate' , 
				'endmodificationdate' => 'EndModificationDate' , 
			);
			
			$orderClaimFilter['OnlyWithMessageFromCdsCustomerService']=false;
			
			foreach($params as $key => $value){
				//check date time  key name whether exists
				if (array_key_exists(strtolower($key) ,$orderfilterFieldName)){
					if ( ($orderfilterFieldName [strtolower($key) ] != "") ){
						//value not meaning 
						if (strtolower($value) == 'null' || $value==null) continue;
						//set the date time params
						$orderClaimFilter[$orderfilterFieldName [strtolower($key) ]] = $value ;
					}
						
				}
				
				if (strtolower($key) == 'state'){
					if (is_array($value)){
						foreach($value as $Astate){
							if (trim($Astate) != "")
							$state_arr[] = $Astate; 
						}
					}else{
						$state_arr = $value; 
					}
					if (count($state_arr)>0){
						$orderClaimFilter['StatusList']['DiscussionStateFilter'] = $state_arr;
					}
				}
				
				if (strtolower($key) == 'orderlist'){
					if (!empty($value))
						$orderClaimFilter['OrderNumberList'] = $value;
				}
				if (strtolower($key) == 'onlyfromcustomer'){
					$orderClaimFilter['OnlyWithMessageFromCdsCustomerService']=$value;
				}
				
			}
			
			//generate the whole params
			$params=array(
				"headerMessage"=>array(
						'Context' => array(
								'CatalogID'      => 1,
								'CustomerPoolID' => 1,
								'SiteID'         => 100,
						),
						'Localization' => array(
								'Country'         => 'Fr',
								'Currency'        => 'Eur',
								'DecimalPosition' => '2',
								'Language'        => 'Fr',
						),
						'Security' => array(
								'TokenId'          => $tokenID ,
						),
						
						'Version' => '1.0',
				 ),
				'orderClaimFilter' => $orderClaimFilter ,
				
			);
			//set params end
			//call cd api
			$response = $soap_client->GetOrderClaimList($params);
			
			//convert xml to array 
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
			
		}catch (Exception $e)
		{
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('claimList'=>$response , 'message'=>$message , 'success'=>$success , 'params'=>$params );

	}
	
	
	//GetOrderQuestionList
	static function GetOrderQuestionList($params = array()){
		$success = true;
		$message = "";
		try
		{
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			//set tokenID
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			//set params start 
			$orderfilterFieldName  = array(
				'begincreationdate' => 'BeginCreationDate' , 
				'beginmodificationdate' => 'BeginModificationDate' , 
				'endcreationdate' => 'EndCreationDate' , 
				'endmodificationdate' => 'EndModificationDate' , 
			);
			
			
			foreach($params as $key => $value){
				//check date time  key name whether exists
				if (array_key_exists(strtolower($key) ,$orderfilterFieldName)){
					if ( ($orderfilterFieldName [strtolower($key) ] != "") ){
						//value not meaning 
						if (strtolower($value) == 'null' || $value==null) continue;
						//set the date time params
						$orderQuestionFilter[$orderfilterFieldName [strtolower($key) ]] = $value ;
					}
						
				}
				
				if (strtolower($key) == 'state'){
					if (is_array($value)){
						foreach($value as $Astate){
							if (trim($Astate) != "")
							$state_arr[] = $Astate; 
						}
					}else{
						$state_arr = $value; 
					}
					if (count($state_arr)>0){
						$orderQuestionFilter['StatusList']['DiscussionStateFilter'] = $state_arr;
					}
				}
				
				if (strtolower($key) == 'orderlist'){
					if (!empty($value))
						$orderQuestionFilter['OrderNumberList'] = $value;
				}
			}
			
			//generate the whole params
			$params=array(
				"headerMessage"=>array(
						'Context' => array(
								'CatalogID'      => 1,
								'CustomerPoolID' => 1,
								'SiteID'         => 100,
						),
						'Localization' => array(
								'Country'         => 'Fr',
								'Currency'        => 'Eur',
								'DecimalPosition' => '2',
								'Language'        => 'Fr',
						),
						'Security' => array(
								'TokenId'          => $tokenID ,
						),
						
						'Version' => '1.0',
				 ),
				'orderQuestionFilter' => $orderQuestionFilter ,
				
			);
			//set params end
			//call cd api
			$response = $soap_client->GetOrderQuestionList($params);
			
			//convert xml to array 
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
			
		}catch (Exception $e)
		{
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('questionList'=>$response , 'message'=>$message , 'success'=>$success , 'params'=>$params );
	}
	
	/*
	//GenerateDiscussionMailGuid
	static function GenerateDiscussionMailGuid($params = array()){
		$success = true;
		$message = "";
		try
		{
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			//set tokenID
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			//set params start 

			foreach($params as $key => $value){
				if (strtolower($key) == 'orderid'){
					if (!empty($value))
						$request['ScopusId'] = $value;
				}
			}
			
			//generate the whole params
			$params=array(
				"headerMessage"=>array(
						'Context' => array(
								'CatalogID'      => 1,
								'CustomerPoolID' => 1,
								'SiteID'         => 100,
						),
						'Localization' => array(
								'Country'         => 'Fr',
								'Currency'        => 'Eur',
								'DecimalPosition' => '2',
								'Language'        => 'Fr',
						),
						'Security' => array(
								'TokenId'		 => $tokenID ,
						),
						
						'Version' => '1.0',
				 ),
				'request' => $request ,
				
			);
			//set params end
			//call cd api
			$response = $soap_client->GenerateDiscussionMailGuid($params);
			
			//convert xml to array 
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
			
		}catch (Exception $e)
		{
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('MailDiscussionGuid'=>$response , 'message'=>$message , 'success'=>$success , 'params'=>$params );
	}
	*/
	
	//GetDiscussionMailList
	static function GetDiscussionMailList($params = array()){
		$success = true;
		$message = "";
		try
		{
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			//set tokenID
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			//set params start 

			$request=array();
			foreach($params as $key => $value){
				if (strtolower($key) == 'discussionids'){
					if (!empty($value))
						$request['DiscussionIds'] = $value;
				}
			}
			
			//generate the whole params
			$params=array(
				"headerMessage"=>array(
						'Context' => array(
								'CatalogID'      => 1,
								'CustomerPoolID' => 1,
								'SiteID'         => 100,
						),
						'Localization' => array(
								'Country'         => 'Fr',
								'Currency'        => 'Eur',
								'DecimalPosition' => '2',
								'Language'        => 'Fr',
						),
						'Security' => array(
								'TokenId'		 => $tokenID ,
						),
						
						'Version' => '1.0',
				 ),
				'request' => $request ,
				
			);
			//set params end
			//call cd api
			$response = $soap_client->GetDiscussionMailList($params);
			
			//convert xml to array 
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
			
		}catch (Exception $e)
		{
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('DiscussionMailList'=>$response , 'message'=>$message , 'success'=>$success , 'params'=>$params );
	}
	
	
	//GetOfferList
	static function GetOfferList($params = array()){
		$success = true;
		$message = "";
		try
		{
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			//set tokenID
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			//set params start 

			$offerFilter=array();
			foreach($params as $key => $value){
				if (strtolower($key) == 'sellerproductidlist'){
					if (!empty($value))
						$offerFilter['SellerProductIdList'] = $value;
				}
			}
			
			//generate the whole params
			$params=array(
				"headerMessage"=>array(
						'Context' => array(
								'CatalogID'      => 1,
								'CustomerPoolID' => 1,
								'SiteID'         => 100,
						),
						'Localization' => array(
								'Country'         => 'Fr',
								'Currency'        => 'Eur',
								'DecimalPosition' => '2',
								'Language'        => 'Fr',
						),
						'Security' => array(
								'TokenId'		 => $tokenID ,
						),
						
						'Version' => '1.0',
				 ),
				'offerFilter' => $offerFilter ,
				
			);
			//set params end
			//call cd api
			$response = $soap_client->GetOfferList($params);
			
			//convert xml to array 
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
			
		}catch (Exception $e)
		{
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('OfferList'=>$response , 'message'=>$message , 'success'=>$success , 'params'=>$params );
	}
	
	//GetSellerInformation
	static function GetSellerInformation($params = array()){
		$success = true;
		$message = "";
		try
		{
			$soap_url = self::$PRICEMINISTER_SOAP_URL ;
			$soap_client = new SoapClient($soap_url, array("trace"=>true, 'exceptions' => true , 'encoding'=>'UTF-8'));
			//set tokenID
			$tokenID = self::$PRICEMINISTER_TOKEN_ID;
			//set params start 

			//generate the whole params
			$params=array(
				"headerMessage"=>array(
						'Context' => array(
								'CatalogID'      => 1,
								'CustomerPoolID' => 1,
								'SiteID'         => 100,
						),
						'Localization' => array(
								'Country'         => 'Fr',
								'Currency'        => 'Eur',
								'DecimalPosition' => '2',
								'Language'        => 'Fr',
						),
						'Security' => array(
								'TokenId'		 => $tokenID ,
						),
						
						'Version' => '1.0',
				 ),	
			);
			//set params end
			//call cd api
			$response = $soap_client->GetSellerInformation($params);
			
			//convert xml to array 
			if (trim($response) != "")
			$response = Utility::xml_to_array($response);
			
		}catch (Exception $e)
		{
			$success = false;
			$message = $e->faultstring;
		}
		
		return array('GetSellerInformation'=>$response , 'message'=>$message , 'success'=>$success , 'params'=>$params );
	}
	
}//end of class PriceministerAPI
