<?php
/**
 * 获得指定用户的刊登列表
 * @package interface.ebay.tradingapi
 */
class EbayInterface_getmyebayselling extends EbayInterface_base{
	public $uid=0;
    //从ebay获取相应itemid的信息
    public function api($ItemListType){
        $this->OrderRole='Seller';
        $this->OrderStatus='Active';//Completed ,Shipped
		$this->verb = 'GetMyeBaySelling';
		$xmlArr=array(
			'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
		);
		if(in_array('ActiveList',$ItemListType)){
			$xmlArr['ActiveList']=array(
				
				'Pagination'=>array(
					'EntriesPerPage'=>$this->EntriesPerPage,
					'PageNumber'=>$this->PageNumber,
				),
			);
		}
		if(in_array('SoldList',$ItemListType)){
			$xmlArr['SoldList']=array(
				'DurationInDays'=>$this->DurationInDays,
				'Pagination'=>array(
					'EntriesPerPage'=>$this->EntriesPerPage,
					'PageNumber'=>$this->PageNumber,
				),
			);
		}
		
		return $this->sendHttpRequest (array(
			'GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>$xmlArr
			));
    }
    
    /*******
     * 取用户的全部 订单 
     */	     
    public function getAll($userToken,$siteID=0){
    	//if(is_int($timefrom))
		$this->eBayAuthToken=$userToken;
		$this->CreateTimeFrom='2009-08-01 00:00:00';
		$this->CreateTimeTo=date('Y-m-d H:i:s');
		$this->EntriesPerPage=20;
		$this->PageNumber=1;
		$this->DurationInDays=2;
		
		$this->siteID=$siteID;
		
		$ItemListType=array('SoldList');
	
		$do=true;
		while($do){
	
			$result=$this->api($ItemListType);
			if($result['Ack']=='Success'){
				
			}else{
				$do=false;
			}
			// 出售 毕的 订单
			if(count($result['SoldList'])){
				// 订单  
				foreach($result['SoldList']['OrderTransactionArray']['OrderTransaction'] as $item){
					$this->saveItem($item);
				}
				$totalPage=$result['SoldList']['PaginationResult']['TotalNumberOfPages'];
				$totalEntries=$result['SoldList']['PaginationResult']['TotalNumberOfEntries'];
			}
			
			if($this->PageNumber <$totalPage-1){
				$this->PageNumber++;
			}else{
				$do=false;
			}
		}

	}
	
	//  保存到数据库中 
	// 这里只能保存 订单表中 有限的 字段 , 更具体的字段 要在 GetItemTransactions 中取得.
	function saveItem($item){
	//var_dump($item);die('dd');
		//$item=$item['Transaction'];
		///买家信息 
		$item['Buyer'];
		//lister 信息
		$item['Item']['ListingDetails']['StartTime']=date('Y-m-d H:i:s',strtotime($item['Item']['ListingDetails']['StartTime']));
		$item['Item']['ListingDetails']['EndTime']=date('Y-m-d H:i:s',strtotime($item['Item']['ListingDetails']['EndTime']));

		// 下单时间
		$createdDate=strtotime($item['CreatedDate']);
		$TransactionID=$item['TransactionID'];
		$TransactionPlatform=$item['TransactionPlatform'];
		$QuantityPurchased=$item['QuantityPurchased'];
		$SellerPaidStatus=$item['SellerPaidStatus'];
		//如果 取得的记录 为空
		if(empty($TransactionID)||empty($createdDate)){
			return false;
		}
		$et=OdEbayTransaction::model()->where('uid=? And transactionid=? And platform=?',array($this->uid,$TransactionID,$TransactionPlatform))->getOne();
		if($et->isNewRecord){
			$n=new OdEbayOrderItem(array(
				'uid'=>$this->uid,
				'transactionid'=>$TransactionID, // 交易 Id
				//'buyer'=>$item['Buyer'],
				//'item'=>$item['Item'],
				'createddate'=>$createdDate,
				//'status'=>$item['Status'],
				'quantitypurchased'=>$QuantityPurchased, //数量
				//'sellerpaidstatus'=>$SellerPaidStatus,
				'platform'=>$TransactionPlatform
			));
			$n->save();
		}
	}
	
	/***
	 * 获取销售限制信息
	* @author fanjs
	*
	*/
	public function getSellerlimit($token,$days=30){
		$this->eBayAuthToken=$token;
		$this->verb = 'GetMyeBaySelling';
		$xmlArr=array(
				'RequesterCredentials'=>array(
						'eBayAuthToken'=>$this->eBayAuthToken,
				),
				'DetailLevel'=>'ReturnSummary',
				'SellingSummary'=>array('Include'=>true),
				'SoldList'=>array('DurationInDays'=>$days),
		);
		return $this->sendHttpRequest (array(
				'GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>$xmlArr
		));
	}
}
?>