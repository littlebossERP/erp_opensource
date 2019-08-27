<?php
/**
 * 据 ItemId  取得 item 下的订单
 * @package interface.ebay.tradingapi 
 */ 
class EbayInterface_GetItemTransactions extends EbayInterface_base{
	public $orderids=array();
	public $finalvaluefee_currency=null;
	public $EntriesPerPage=50;
	public $PageNumber=1;
    public function api($itemid,$ModTimeFrom='',$ModTimeTo='',$NumberOfDays='',$Platform='',$TransactionID=0){
	    $this->verb = 'GetItemTransactions';
		$xmlArr=array(
//			'DetailLevel' =>'ReturnAll',
			'IncludeContainingOrder'=>TRUE,
			'IncludeFinalValueFee' =>true,
			'ItemID'=>$itemid,
			'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
			'Pagination'=>array(
				'EntriesPerPage'=>$this->EntriesPerPage,
				'PageNumber'=>$this->PageNumber,
			),
		);
	    if($ModTimeFrom&&$ModTimeTo){
			$xmlArr['ModTimeFrom']=$ModTimeFrom;
			$xmlArr['ModTimeTo']=$ModTimeTo;
		}
	    if($NumberOfDays){
			$xmlArr['NumberOfDays']=$NumberOfDays;
		}
		if($Platform){
			$xmlArr['Platform']=$Platform;
		}
		if($TransactionID){
			$xmlArr['TransactionID']=$TransactionID;
		}

		//$s=parent::simpleArr2xml($xmlArr);
		//var_dump($s);die();
		$rXML= $this->setRequestBody($xmlArr)->sendRequest(1);
		$DOM=new DOMDocument();
		if ($DOM->loadXML($rXML) && !is_null($DOM->getElementsByTagName('FinalValueFee')->item(0))){
			$this->finalvaluefee_currency=$DOM->getElementsByTagName('FinalValueFee')->item(0)->attributes->item(0)->nodeValue;
		}
		return parent::xmlparse($rXML); 
	}
	
	public function getOne($userToken,$itemid,$siteID=0,$EntriesPerPage=20,$PageNumber=1){
		$this->eBayAuthToken=$userToken;
		$this->EntriesPerPage=$EntriesPerPage;
		$this->PageNumber=$PageNumber;
		$this->siteID=$siteID;
		return $this->api($itemid);//'110040560612'
	}
	/***
	 * 对 transaction 进行保存
	 * $items : 数组化后的  TransactionArray.
	 * $uid : 所属用户 Token 
	 * 	 
	 */
	public function save($transactionArrayParentNode,$uid,$selleruserid=null){
		if (is_null($selleruserid)){
			$selleruserid=$transactionArrayParentNode['Item']['Seller']['UserID'];
		}

       if(empty($this->eBayAuthToken) && $selleruserid){
            $Ebay_User = SaasEbayUser::model()->where('selleruserid=?',$selleruserid)->getOne();
            $this->eBayAuthToken = $Ebay_User->token;
       }
		if($transactionArrayParentNode instanceof SimpleXMLElement){
			 $transactionArrayParentNode=parent::simplexml2a($transactionArrayParentNode);
		}

		//第1步.获得并保存 Item
// 		if (isset($transactionArrayParentNode['Item']['ItemID'])){
// 			$item=$transactionArrayParentNode['Item'];
// 			$ei=new EbayInterface_getitem();
// 			$ei->save($item);
// 		}else{
// 			$item=null;
// 		}
		
		if(isset($transactionArrayParentNode['TransactionArray']['Transaction']['TransactionID'])){
			$ts[0]=$transactionArrayParentNode['TransactionArray']['Transaction'];
		}elseif(isset($transactionArrayParentNode['TransactionArray']['Transaction'])){
			$ts=$transactionArrayParentNode['TransactionArray']['Transaction'];
		}else{
			return false;
		}

		//第2步.保存transaction
		if(count($ts)) {
			foreach($ts as $k=>$t){
				if (isset($t['Item']['ItemID'])){
					$item=$t['Item'];
				}
				$n=OdEbayTransaction::model()->where('transactionid=? And itemid=?',array($t['TransactionID'],$item['ItemID']))->getOne();
				if($n->isNewRecord||$n->status_payment!=OdEbayTransaction::STATUS_PAYMENT_COMPLETE){
				    if(@isset($t['ContainingOrder']['OrderID'])){
						$ebay_orderid=$t['ContainingOrder']['OrderID'];
					}else{
						$ebay_orderid=$item['ItemID'].'-'.$t['TransactionID'];
					}
					QueueGetorderHelper::Add($ebay_orderid,$selleruserid,$Ebay_User->selleruserid);
				}
			}
		}
	}
}

if(!function_exists('println')){
	function println($str){}
}