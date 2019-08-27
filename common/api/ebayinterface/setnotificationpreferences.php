<?php
/**
 * 设置notification选项
 * @package interface.ebay.tradingapi
 */
class EbayInterface_SetNotificationPreferences extends EbayInterface_base{
    public $verb = 'SetNotificationPreferences';
    /**
     * 默认设置
     */
    private $setValues=array(
		'AlertEmail'=>'mailto://5610968@qq.com',
		);
	//从ebay获取相应itemid的信息
	public function setUpApplication($enableTypes=null){
	    $xmlArr=array(
	        'ApplicationDeliveryPreferences'=>array(
				'AlertEmail'=>'mailto://5610968@qq.com',
				'AlertEnable'=>'Enable',
				'ApplicationEnable'=>'Enable',
    			'ApplicationURL'=> ebayInterface_Config::getApllicationURL($this->production),				
    			'DeviceType'=>'Platform',
				'NotificationPayloadType'=>'eBLSchemaSOAP',
//				'PayloadVersion'=>
			),
	    );
	    $dom=new SimpleXMLElement('<UserDeliveryPreferenceArray></UserDeliveryPreferenceArray>');
	    $allTypes=array('EndOfAuction','AuctionCheckoutComplete','FixedPriceEndOfTransaction','CheckoutBuyerRequestsTotal','Feedback','FixedPriceTransaction','AskSellerQuestion','ItemListed','BuyerResponseDispute','BestOffer','ItemRevised','MyMessagesHighPriorityMessage','MyMessagesM2MMessage','INRBuyerOpenedDispute','INRBuyerRespondedToDispute','INRBuyerClosedDispute','Checkout','ItemSold','ItemSuspended','ItemClosed','ItemExtended','ItemRevisedAddCharity','ItemAddedToWatchList','BidPlaced','ItemRemovedFromWatchList','ItemRemovedFromBidGroup','BidReceived','ItemWon','ItemLost','ItemUnsold','FeedbackLeft','FeedbackStarChanged','BestOfferPlaced','CounterOfferReceived','BestOfferDeclined','FeedbackReceived','ItemsCanceled','ItemMarkedShipped','ItemMarkedPaid','BulkDataExchangeJobCompleted','EBPMyResponseDue','EBPOtherPartyResponseDue','EBPEscalatedCase',
	   			'EBPAppealedCase','EBPMyPaymentDue','EBPPaymentDone','EBPClosedAppeal','EBPClosedCase','EBPOnHoldCase');
	    //,'MyMessageseBayMessage'
	    if (is_null($enableTypes)){
	   		$enableTypes=array(
		    	'EndOfAuction','AuctionCheckoutComplete',
		    	'FixedPriceEndOfTransaction','FixedPriceTransaction',
		        'CheckoutBuyerRequestsTotal',
		        'ItemClosed',
		        'FeedbackReceived',
	            'MyMessagesM2MMessage',
//		        'MyMessageseBayMessage',
		        'MyMessagesHighPriorityMessage',
		        'BuyerResponseDispute',
		    	'INRBuyerOpenedDispute',
		    	'INRBuyerRespondedToDispute',
		    	'INRBuyerClosedDispute',
		    	'BestOffer','BestOfferPlaced','BestOfferDeclined',
	   			'EBPMyResponseDue','EBPOtherPartyResponseDue','EBPEscalatedCase',
	   			'EBPAppealedCase','EBPMyPaymentDue','EBPPaymentDone',
	   			'EBPClosedAppeal','EBPClosedCase','EBPOnHoldCase'
	//	        'AskSellerQuestion'
		    );
	    }
	    foreach ($allTypes as $type){
	        $enable=$dom->addChild('NotificationEnable');
	        $enable->EventType=$type;
	        $enable->EventEnable=in_array($type,$enableTypes)?'Enable':'Disable';
	    }
	    foreach ($enableTypes as $et){
	    	if (substr($et,0,3)=='EBP'){
	    		$xmlArr['UserData']['ExternalUserData']='eBP notification';
	    	}
	    }
	    $xmlArr['UserDeliveryPreferenceArray']=$dom;
	    $result=$this->setRequestMethod($this->verb)
	        ->setRequestBody($xmlArr)
	        ->sendRequest();

	    // 21416 错误
	    if (isset($result['Errors'])){
	    	$error_codes=array();
	    	if (!isset($result['Errors']['ErrorCode'])){
	    		foreach ($result['Errors'] as $error){
	    			$error_codes[]=$error['ErrorCode'];
	    		}
	    	}else {
	    		$error_codes[]=$result['Errors']['ErrorCode'];
	    	}
	    	if (in_array(21416, $error_codes)){
	    		//'MyMessageseBayMessage'
	    		$xmlArr['UserDeliveryPreferenceArray']=array();
	    		@$xmlArr['UserDeliveryPreferenceArray']['NotificationEnable']=array(
	    			'EventType'=>'MyMessageseBayMessage',
	    			'EventEnable'=>'Disable'
	    		);
	    		$this->setRequestBody($xmlArr)
	    			->sendRequest();
	    		$result=$this->setUpApplication($enableTypes);
	    	}
	    }
		return $result;
	}
}
?>