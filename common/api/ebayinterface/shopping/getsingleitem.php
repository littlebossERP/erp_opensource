<?php
namespace common\api\ebayinterface\shopping;

use common\api\ebayinterface\shopping\base;
use eagle\modules\listing\models\EbayItem;
use eagle\models\SaasEbayUser;
use common\helpers\Helper_Array;
use eagle\modules\listing\models\EbayItemDetail;
class getsingleitem extends base
{
    public $callname = 'GetSingleItem';
    public $responseFieldName='Item';
    public $includeSelector='Details,Description,TextDescription,ShippingCosts,ItemSpecifics,Variations';
    public $siteid='0';
    /**
     * 通过ItemID 获得
     * @param array $itemids
     */
    function apiItem($itemid){
    	$xmlArr=array('ItemID'=>$itemid);
    	
    	return $this->api($xmlArr);
    }
    function api($params){
    	if ($this->includeSelector){
    		$params['IncludeSelector']=$this->includeSelector;
    	}
    	$xmlArr=$params;
    	return $this->request($xmlArr);
    }
    
    /**
     * 抓取ebayItem的汽配信息
     * @author fanjs
     */
    function getitemfitment($itemid){
    	$this->includeSelector = 'Compatibility,Details';
    	$xmlArr=array('ItemID'=>$itemid);
    	
    	return $this->api($xmlArr);
    }
    
    /**
     * 将通过 api取得的值 保存到数据库中
     * $item : 数组化后的 getItem 的 Item
     * $_sitemap:getsellerlist中获取的itemid与site的映射，处理customcode问题
     * @return EbayItem|false
     */
    public function save($item,$eu,$_sitemap){
    	if(empty($item['ItemID'])){
    		return false;
    	}
    	$Eitem = EbayItem::find()->where(['itemid'=>$item['ItemID']])->one();
    	if (empty($Eitem)){
    		$Eitem=new EbayItem();
    	}
    	if ($Eitem->isNewRecord){
    		$Ebay_User=SaasEbayUser::find()->where(['selleruserid'=>$eu->selleruserid])->one();
    		$Eitem->uid=$Ebay_User->uid;
    	}
    	//Item 保存 修改
    	$Eitem_v=array(
    			'itemid'=>$item['ItemID'],
    			'selleruserid'=>$eu->selleruserid,
    			'currentprice'=>@$item['CurrentPrice']['Value'],
    			'currency'=>@$item['CurrentPrice']['CurrencyID'],
    			'quantitysold'=>@$item['QuantitySold'],
    			'listingstatus'=>$item['ListingStatus'],
    			'viewitemurl'=>$item['ViewItemURLForNaturalSearch'],
    			'site'=>@$item['Site'],
    			'sku'=>trim(@$item['SKU']),
    			'watchcount'=>@$item['WatchCount'],
    	);
    	//处理site字段的customcode问题
    	if($Eitem_v['site'] == 'CustomCode' && isset($_sitemap[$item['ItemID']]) && $_sitemap[$item['ItemID']] !='CustomCode'){
    		$Eitem_v['site'] = $_sitemap[$item['ItemID']];
    	}
    	if($Eitem_v['site'] == 'CustomCode' && strlen($Eitem_v['currency'])){
    		$site_map = [
    				'GBP'=>'UK',
    				'USD'=>'US',
    				'EUR'=>'Germany',
    				'CAD'=>'Canada',
    				'AUD'=>'Australia',
    				];
    		if (in_array($Eitem_v['currency'], ['GBP','USD','EUR','CAD','AUD'])){
    			$Eitem_v['site'] = $site_map[$Eitem_v['currency']];
    		}
    	}
        if (!empty($item['Storefront']['StoreCategoryID'])) {
            $Eitem_v['storecategoryid']=$item['Storefront']['StoreCategoryID'];
        }
        if (!empty($item['PrimaryCategory']['CategoryID'])) {
             $Eitem_v['primarycategory']=$item['PrimaryCategory']['CategoryID'];
        }
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
    	if (!empty($item['CurrentPrice']['Currency'])){
    		$Eitem_v['currency']=$item['CurrentPrice']['Currency'];
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
    	$Eitem_v['starttime']=strtotime($item['StartTime']);
	    $Eitem_v['endtime']=strtotime($item['EndTime']);
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
    	$Eitem->save(false);
    
    
    	\Yii::info('start save item_detail');
    	$Eitemdetail = EbayItemDetail::find()->where(['itemid'=>$item['ItemID']])->one();
    	if (empty($Eitemdetail)){
    		$Eitemdetail = new EbayItemDetail();
    	}
    	$Eitemdetail->itemid =$Eitem->itemid;
    	$Eitemdetail->setAttributes(array(
    			'paymentmethods'=>is_array($item['PaymentMethods'])?$item['PaymentMethods']:array($item['PaymentMethods']),
    			'itemdescription'=>$item['Description'],
    			'primarycategory'=>$item['PrimaryCategoryID'],
    	));
    	if (!empty($item['ReturnPolicy'])){
    		$Eitemdetail->returnpolicy=$item['ReturnPolicy'];
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
    	if (!empty($item['BusinessSellerDetails']['VATDetails']['VATPercent']) ){
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
    	if (!empty($item['PictureURL'])){
    		$pic=$item['PictureURL'];
    		if (is_array($pic)){
    			$Eitemdetail->imgurl=$pic;
    		}else{
    			$Eitemdetail->imgurl=array($pic);
    		}
    			
    		//为item记录mainimg字段
    		$Eitem->mainimg=$Eitemdetail->imgurl['0'];
    		$Eitem->save(false);
    	}
    	// 子标题
    	if(!empty($item['SubTitle'])){
    		$Eitemdetail->subtitle=$item['SubTitle'];
    	}
    	//分类
    	if(!empty($item['SecondaryCategoryID'])){
    		$Eitemdetail->secondarycategory=$item['SecondaryCategoryID'];
    	}
    
    	$Eitemdetail->save(false);
    	return $Eitem;
    }
}