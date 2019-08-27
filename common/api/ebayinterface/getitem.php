<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use eagle\modules\listing\models\EbayItem;
use eagle\models\SaasEbayUser;
use common\helpers\Helper_Array;
use eagle\modules\listing\models\EbayItemDetail;
/**
 * 获取指定Item的详细信息
 * @package interface.ebay.tradingapi
 */
class getitem extends base{
	public $verb='GetItem';
    //从ebay获取相应itemid的信息
    public function api($itemid,$outputSelector=null,$DetailLevel='ItemReturnDescription'){
        $this->verb = 'GetItem';
        $xmlArr=array(
			'IncludeWatchCount'=>'true',
        	'IncludeItemSpecifics'=>'true',
//        	'IncludeItemCompatibilityList'=>true,
			'ItemID'=>$itemid,
		);
        if (strlen($DetailLevel)){
        	$xmlArr['DetailLevel']=$DetailLevel;
        }
        
        if (!is_null($outputSelector)){
        	$xmlArr['OutputSelector']=$outputSelector;
        }
        
		if(isset($this->_before_request_xmlarray['OutputSelector'])){
            unset($xmlArr['DetailLevel']);
            $xmlArr['OutputSelector']=$this->_before_request_xmlarray['OutputSelector'];
        }
        
        $this->setRequestBody($xmlArr);
		$result=$this->sendRequest(0,120);
		if(!$this->responseIsFailure()){
			return $result['Item'];
		}else{
			if($result['Ack']=='Failure'&&$result['Errors']['ErrorCode']==17){
				// Item cannot be accessed.
			}
			return false;
		}
	}
	
	public function _getOne($userToken,$itemid,$siteID=0){
		$this->eBayAuthToken=$userToken;
		$this->siteID=$siteID;
		$this->_loadconfig();
		return $this->api($itemid);
	}
	
	public function getOne($userToken,$itemid,$siteID=0){
		$this->eBayAuthToken=$userToken;
		$this->siteID=$siteID;
		$this->_before_request_xmlarray['OutputSelector']=array(
			'Item.ItemID',
			'Item.Site',
			'Item.Seller.UserID',
			'Item.StartPrice',
			'Item.BuyItNowPrice',
			'Item.Currency',
			'Item.Country',
			'Item.Location',
			'Item.ListingType',
			'Item.Quantity',
			'Item.CategoryID',   
			'Item.SecondaryCategory',
			'Item.Title',
			'Item.DispatchTimeMax',
			'Item.PictureDetails.PictureURL',
			'Item.ShippingDetails',
			'Item.PaymentMethods',
			'Item.Variations',
			'Item.ListingDuration',
			'Item.LotSize',
			'Item.SellingStatus',
			'Item.ListingDetails',  
			'Item.BestOfferDetails.BestOfferEnabled',
			'Item.Storefront.StoreCategoryID',
			'Item.Storefront.StoreCategory2ID',
			'Item.PictureDetails.GalleryType',
			'Item.ConditionID',
			'Item.WatchCount',
			'Item.HitCounter',
			'Item.SKU',
		);
		
		return $this->api($itemid);
	}
	
	
	public function getDesc(){
		
		
		
	}
	/**
	 * 将通过 api取得的值 保存到数据库中 
	 * $item : 数组化后的 getItem 的 Item  
	 * @return EbayItem|false
	 */
	public function save($item,$do=null,$uid=null){
		if(isset($item['Item']['ItemID'])){
			$item=$item['Item'];
		}
		if(empty($item['ItemID'])){
			return false;
		}
		$Eitem = EbayItem::find()->where(['itemid'=>$item['ItemID']])->one();
		if (empty($Eitem)){
			$Eitem=new EbayItem();
		}
		if ($Eitem->isNewRecord){
			$Ebay_User=SaasEbayUser::find()->where(['selleruserid'=>$item['Seller']['UserID']])->one();
			if (!empty($Ebay_User)){
				$Eitem->uid=$Ebay_User->uid;
			}elseif (!is_null($uid)){
				$Eitem->uid=$uid;
			}else {
				return false;
			}
		}
		//Item 保存 修改 
		$Eitem_v=array(
			'itemid'=>$item['ItemID'],
			'selleruserid'=>$item['Seller']['UserID'],
			'currentprice'=>@$item['SellingStatus']['CurrentPrice'],
			'quantitysold'=>@$item['SellingStatus']['QuantitySold'],
			'listingstatus'=>@$item['SellingStatus']['ListingStatus'],
			'viewitemurl'=>@$item['ListingDetails']['ViewItemURL'],
			'site'=>@$item['Site'],
			'sku'=>trim(@$item['SKU']),
			'watchcount'=>@$item['WatchCount'],
		);
		if (!empty($item['Title'])){
			$Eitem_v['itemtitle']=$item['Title'];
		}
		if (!empty($item['Quantity'])){
			$Eitem_v['quantity']=$item['Quantity'];
		}
		if (!empty($item['ListingType'])){
			$Eitem_v['listingtype']=$item['ListingType'];
		}
		if (!empty($item['ListingDuration'])){
			$Eitem_v['listingduration']=$item['ListingDuration'];
		}
		if (!empty($item['Currency'])){
			$Eitem_v['currency']=$item['Currency'];
		}
		if (!empty($item['BuyItNowPrice'])){
			$Eitem_v['buyitnowprice']=$item['BuyItNowPrice'];
		}
		if (!empty($item['StartPrice'])){
			$Eitem_v['startprice']=$item['StartPrice'];
		}
		if(!empty($item['PayPalEmailAddress']))		
		{
			$Eitem_v['paypal'] = $item['PayPalEmailAddress'];
		}
		if(@$item['OutOfStockControl']==true)
		{
			$Eitem_v['outofstockcontrol'] = 1;
		}
		if (isset($item['ListingDetails'])){
			$Eitem_v['starttime']=strtotime(@$item['ListingDetails']['StartTime']);
			$Eitem_v['endtime']=strtotime(@$item['ListingDetails']['EndTime']);
		}
		if(!empty($item['DispatchTimeMax']))
		{
			$Eitem_v['dispatchtime'] = $item['DispatchTimeMax'];
		}
		
		Helper_Array::removeEmpty($Eitem_v);
    	$Eitem->setAttributes($Eitem_v);
		// Variation 的 sku .
		if(!empty($item['Variations'])){
			$Eitem->isvariation=1;
			$variation=$item['Variations']['Variation'];
			if (isset($variation['StartPrice'])){
				$variation=array($variation);
			}
			$item['Variations']['Variation']=$variation;
		}
		//20161214-没有传模板ID,它是request,不用false会报错
		$Eitem->save(false);
		
		
		\Yii::info('start save item_detail',"file");
		$Eitemdetail=EbayItemDetail::find()->where(['itemid'=>$item['ItemID']])->one();
		if (empty($Eitemdetail)){
			$Eitemdetail = new EbayItemDetail();
		}
		$Eitemdetail->itemid =$Eitem->itemid;
		$Eitemdetail->setAttributes(array(
			'returnpolicy'=>@$item['ReturnPolicy'],
			'itemdescription'=>@$item['Description'],
			'primarycategory'=>@$item['PrimaryCategory']['CategoryID'],
		));
		if (!empty($item['PaymentMethods'])){
			$Eitemdetail->paymentmethods=is_array($item['PaymentMethods'])?$item['PaymentMethods']:array($item['PaymentMethods']);
		}
		if (!empty($item['SellingStatus'])){
			$Eitemdetail->sellingstatus=$item['SellingStatus'];
		}
		if (!empty($item['ItemSpecifics'])){
			$Eitemdetail->itemspecifics=$item['ItemSpecifics'];
		}
		if (!empty($item['ListingEnhancement'])){
			$Eitemdetail->listingenhancement=$item['ListingEnhancement'];
		}
		if (!empty($item['BuyerRequirementDetails'])){
			$Eitemdetail->buyerrequirementdetails=$item['BuyerRequirementDetails'];
		}
		if (!empty($item['Variations'])){
			$Eitemdetail->variation=$item['Variations'];
		}
		if (!empty($item['ConditionID'])){
			$Eitemdetail->conditionid=$item['ConditionID'];
		}
		if (!empty($item['HitCounter'])){
			$Eitemdetail->hitcounter=$item['HitCounter'];
		}
		if (!empty($item['Location'])){
			$Eitemdetail->location=$item['Location'];
			if (is_array($item['Location'])){
				$Eitemdetail->location=reset($item['Location']);
			}
		}
		if (!empty($item['Country'])){
			$Eitemdetail->country=$item['Country'];
		}
		if (!empty($item['PictureDetails']['GalleryType'])){
			$Eitemdetail->gallery=$item['PictureDetails']['GalleryType'];
		}
		if (!empty($item['Storefront']['StoreCategory2ID'])){
			$Eitemdetail->storecategory2id=$item['Storefront']['StoreCategory2ID'];
		}
		if (!empty($item['Storefront']['StoreCategoryID'])){
			$Eitemdetail->storecategoryid=$item['Storefront']['StoreCategoryID'];
		}
		if(!empty($item['AutoPay']))
		{
			$Eitemdetail->autopay = $item['AutoPay'];
		}
		if(!empty($item['PrivateListing']))
		{
			$Eitemdetail->privatelisting = $item['PrivateListing'];
		}
		// BestOffer Details
		if(isset($item['BestOfferDetails']) && isset($item['BestOfferDetails']['BestOfferEnabled'])){
			if(strtolower($item['BestOfferDetails']['BestOfferEnabled'])=='true'){
				$Eitemdetail->bestoffer=1;
			}else{
				$Eitemdetail->bestoffer=0;
			}
			if (isset($item['ListingDetails']['BestOfferAutoAcceptPrice'])){
				$Eitemdetail->bestofferprice=$item['ListingDetails']['BestOfferAutoAcceptPrice'];
			}
		}
		if (!empty($item['LotSize'])){
			$Eitemdetail->lotsize=$item['LotSize'];
		}
		if (!empty($item['PostalCode'])){
			$Eitemdetail->postalcode=$item['PostalCode'];
		}
		//VATpercent
		if (!empty($item['VATDetails']['VATPercent']) ){
			$Eitemdetail->vatpercent=$item['VATDetails']['VATPercent'];
		}
		if (!empty($item['SellingStatus'])){
			$sellingstatus=$item['SellingStatus'];
	   		@Helper_Array::removeEmpty($item['SellingStatus']);
	   		$Eitemdetail->sellingstatus=@array_merge(
				@array_intersect_key($item['SellingStatus'],$sellingstatus),
				@array_diff_key($sellingstatus,$item['SellingStatus'])
			);
		}
		if (!empty($item['ShippingDetails'])){
			$Eitemdetail->shippingdetails=$item['ShippingDetails'];
		}
		if (!empty($item['PictureDetails']['PictureURL'])){
			$pic=$item['PictureDetails']['PictureURL'];
			if (isset($item['PictureDetails']['ExternalPictureURL'])){
				$pic=$item['PictureDetails']['ExternalPictureURL'];
			}
			if (is_array($pic)){
				$Eitemdetail->imgurl=$pic;
			}else{
				$Eitemdetail->imgurl=array($pic);
			}
			
			//为item记录mainimg字段
			$Eitem->mainimg=$Eitemdetail->imgurl['0'];
			$Eitem->save();
		}
		// 子标题
		if(!empty($item['SubTitle'])){
			$Eitemdetail->subtitle=$item['SubTitle'];
		}
		//分类
		if(!empty($item['SecondaryCategory']['CategoryID'])){
			$Eitemdetail->secondarycategory=$item['SecondaryCategory']['CategoryID'];
		}
		
		$Eitemdetail->save(false);
		return $Eitem;
	}
}