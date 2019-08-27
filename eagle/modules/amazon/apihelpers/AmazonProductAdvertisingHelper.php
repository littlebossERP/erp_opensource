<?php
namespace eagle\modules\amazon\apihelpers;

/**
 +---------------------------------------------------------------------------------------------
 * Amazon AWS Product Advertising API helper
 +---------------------------------------------------------------------------------------------
 * log			name		date			note
 * @author		lzhl		2017/03/15		初始化
 +---------------------------------------------------------------------------------------------
 **/

use yii;
use yii\base\Exception;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\TimeUtil;

class AmazonProductAdvertisingHelper{
	
	/**
	 * <<<<API Reference	http://docs.aws.amazon.com/zh_cn/AWSECommerceService/latest/DG/CHAP_ApiReference.html>>>>
	 * The following operations are available in the Product Advertising API.
	 * 		Search:	ItemSearch;
	 *		
	 *		Lookup:	BrowseNodeLookup;
	 *				ItemLookup;
	 *				SimilarityLookup;
	 *				
	 *		Cart:	CartAdd;
	 *				CartClear;
	 *				CartCreate;
	 *				CartGet;
	 *				CartModify;
	 */
	
	
	public static $AMAZON_SES_PROXY_URL= 'http://198.11.178.150/amazon_proxy_server_liang/ApiEntry.php';
	
	/**
	 +----------------------------------------------------------
	 * 通过marketplace id 和asin 获取asin对应的bestseller信息
	 * @param	string	$marketplace_id		amazon marketplace id
	 * @param	string	$Asin				amazon product asin list, if multiple asins, implod by ','
	 * @return	array
	 * @author	lzhl	2017-3-15	初始化
	 +----------------------------------------------------------
	 **/
	public static function getFullOffer($marketplace_id,$Asin){
		$Asin = trim($Asin);
		if(empty($Asin))
			return ['success'=>false,'message'=>'asin can not empty'];
		
		$action = 'GetProductTopOfferByAsin';
		$config = [
			'marketplace_id'=>$marketplace_id,
		];
		$get_params['config'] = json_encode($config);
		
		$parms = [
	    	'response_group'=>'OfferFull',
	    	'operation'=>'ItemLookup',
    	];
    	$get_params['parms'] = json_encode($parms);
    	
		$post_params = [];
		$post_params['asin'] = $Asin;
		
		try{
			$rtn = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params,$time_out=60,$return_type='json',$post_params);
		}catch(\Exception $e) {
			return ['success'=>false,'message'=>'调用API发送Exception：'.print_r($e->getMessage()) ];
		}
		//exit(json_encode($rtn));//liang test
		
		$tmp_response_data = [];
		if(!empty($rtn['success']) && !empty($rtn['response'])){
			$response = $rtn['response'];
			if(!empty($response['Items']['Item'])){
				if(isset($response['Items']['Item']['ASIN'])){
					$offerInfos = $response['Items']['Item'];
					//single ASIN return
					$tmp_asin_data = [];
					$top_seller = empty($offerInfos['Offers']['Offer']['Merchant']['Name'])? '' : $offerInfos['Offers']['Offer']['Merchant']['Name'];
					$top_seller_price = empty($offerInfos['Offers']['Offer']['OfferListing']['Price']['Amount'])? '' : ((int)$offerInfos['Offers']['Offer']['OfferListing']['Price']['Amount'] / 100);
					$CurrencyCode =  empty($offerInfos['Offers']['Offer']['OfferListing']['Price']['CurrencyCode'])? '' : $offerInfos['Offers']['Offer']['OfferListing']['Price']['CurrencyCode'];
					
					$tmp_asin_data['seller']  = $top_seller;
					$tmp_asin_data['price']  = $top_seller_price;
					$tmp_asin_data['currency']  = $CurrencyCode;
					
					$tmp_response_data[$response['Items']['Item']['ASIN']] = $tmp_asin_data;
				}else{
					//multiple ASIN return
					foreach ($response['Items']['Item'] as $item){
						//single ASIN return
						$tmp_asin_data = [];
						$top_seller = empty($item['Offers']['Offer']['Merchant']['Name'])? '' : $item['Offers']['Offer']['Merchant']['Name'];
						$top_seller_price = empty($item['Offers']['Offer']['OfferListing']['Price']['Amount'])? '' : ((int)$item['Offers']['Offer']['OfferListing']['Price']['Amount'] / 100);
						$CurrencyCode =  empty($item['Offers']['Offer']['OfferListing']['Price']['CurrencyCode'])? '' : $item['Offers']['Offer']['OfferListing']['Price']['CurrencyCode'];
							
						$tmp_asin_data['seller']  = $top_seller;
						$tmp_asin_data['price']  = $top_seller_price;
						$tmp_asin_data['currency']  = $CurrencyCode;
							
						$tmp_response_data[$item['ASIN']] = $tmp_asin_data;
					}
				}

				return ['success'=>true,'message'=>'','data'=>$tmp_response_data ];
			}else{
				//todo error msg
				if(isset($response['Items']['Request']['Errors']))
					return ['success'=>false,'message'=>'api获取信息是失败：'.json_encode($response['Items']['Request']['Errors']) ];
				else 
					return ['success'=>false,'message'=>'api获取信息是失败,errorMsg recorded.' ];
			}
		}else{
			return ['success'=>false,'message'=>'链接proxy失败：'.@$rtn['message'] ];
		}
	}
	
	
	public static function getItemAttributes($marketplace_id,$Asin){//or MerchantItemAttributes?
		$Asin = trim($Asin);
		if(empty($Asin))
			return ['success'=>false,'message'=>'asin can not empty'];
		
		$action = 'GetProductAttributesByAsin';
		$config = [
			'marketplace_id'=>$marketplace_id,
		];
		$get_params['config'] = json_encode($config);
		
		$parms = [
			'response_group'=>'ItemAttributes',
			'operation'=>'ItemLookup',
		];
		$get_params['parms'] = json_encode($parms);
		 
		$post_params = [];
		$post_params['asin'] = $Asin;
		
		try{
			$rtn = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params,$time_out=60,$return_type='json',$post_params);
		}catch(\Exception $e) {
			return ['success'=>false,'message'=>'调用API发送Exception：'.print_r($e->getMessage()) ];
		}
		//exit(json_encode($rtn));//liang test
		
		$tmp_response_data = [];
		if(!empty($rtn['success']) && !empty($rtn['response'])){
			$response = $rtn['response'];
			if(!empty($response['Items']['Item'])){
				if(isset($response['Items']['Item']['ASIN'])){
					$ItemInfos = $response['Items']['Item'];
					//single ASIN return
					$tmp_asin_data = [];
					$tmp_asin_data['asin'] = $ItemInfos['ASIN'];
						$tmp_asin_data['detail_page_url'] = empty($ItemInfos['DetailPageURL'])? '' : $ItemInfos['DetailPageURL'];
						$tmp_asin_data['binding'] = empty($ItemInfos['ItemAttributes']['Binding'])? '' : $ItemInfos['ItemAttributes']['Binding'];
						$tmp_asin_data['brand'] = empty($ItemInfos['ItemAttributes']['Brand'])? '' : $ItemInfos['ItemAttributes']['Brand'];
						$tmp_asin_data['catalog_number'] = empty($ItemInfos['ItemAttributes']['CatalogNumberList']['CatalogNumberListElement'])? '' : $ItemInfos['ItemAttributes']['CatalogNumberList']['CatalogNumberListElement'];
						//color 和 size 和后台显示的 系类商品属性 有不同，可能不能展示于前端给用户筛选
						$tmp_asin_data['color'] = empty($ItemInfos['ItemAttributes']['Color'])? '' : $ItemInfos['ItemAttributes']['Color'];
						$tmp_asin_data['size'] = empty($ItemInfos['ItemAttributes']['Size'])? '' : $ItemInfos['ItemAttributes']['Size'];
						
						$tmp_asin_data['ean'] = empty($ItemInfos['ItemAttributes']['EAN'])? '' : $ItemInfos['ItemAttributes']['EAN'];
						$feature = empty($ItemInfos['ItemAttributes']['Feature'])? '' : $ItemInfos['ItemAttributes']['Feature'];
						if(is_array($feature))
							$feature = json_encode($feature);
						$tmp_asin_data['feature'] = $feature;
						$tmp_asin_data['label'] = empty($ItemInfos['ItemAttributes']['Label'])? '' : $ItemInfos['ItemAttributes']['Label'];
						$tmp_asin_data['publisher'] = empty($ItemInfos['ItemAttributes']['Publisher'])? '' : $ItemInfos['ItemAttributes']['Publisher'];
						$tmp_asin_data['studio'] = empty($ItemInfos['ItemAttributes']['Studio'])? '' : $ItemInfos['ItemAttributes']['Studio'];
						$tmp_asin_data['manufacturer'] = empty($ItemInfos['ItemAttributes']['Manufacturer'])? '' : $ItemInfos['ItemAttributes']['Manufacturer'];
						$tmp_asin_data['model'] = empty($ItemInfos['ItemAttributes']['Model'])? '' : $ItemInfos['ItemAttributes']['Model'];
						$tmp_asin_data['mpn'] = empty($ItemInfos['ItemAttributes']['MPN'])? '' : $ItemInfos['ItemAttributes']['MPN'];
						$tmp_asin_data['part_number'] = empty($ItemInfos['ItemAttributes']['PartNumber'])? '' : $ItemInfos['ItemAttributes']['PartNumber'];
						$tmp_asin_data['product_group'] = empty($ItemInfos['ItemAttributes']['ProductGroup'])? '' : $ItemInfos['ItemAttributes']['ProductGroup'];
						$tmp_asin_data['product_type_name'] = empty($ItemInfos['ItemAttributes']['ProductTypeName'])? '' : $ItemInfos['ItemAttributes']['ProductTypeName'];
						$tmp_asin_data['title'] = empty($ItemInfos['ItemAttributes']['Title'])? '' : $ItemInfos['ItemAttributes']['Title'];
						$tmp_asin_data['upc'] = empty($ItemInfos['ItemAttributes']['UPC'])? '' : $ItemInfos['ItemAttributes']['UPC'];
						//$Price = '';用户的价格数据应该通过mws或者查询top offer为自己时才更新
						$tmp_asin_data['currency'] = empty($ItemInfos['ItemAttributes']['ListPrice']['CurrencyCode'])? '' : $ItemInfos['ItemAttributes']['ListPrice']['CurrencyCode'];
						
						$tmp_response_data[$ItemInfos['ASIN']] = $tmp_asin_data;
				}else{
					//multiple ASIN return
					foreach ($response['Items']['Item'] as $item){
						$tmp_asin_data = [];
						$tmp_asin_data['asin'] = $item['ASIN'];
						$tmp_asin_data['detail_page_url'] = empty($item['DetailPageURL'])? '' : $item['DetailPageURL'];
						$tmp_asin_data['binding'] = empty($item['ItemAttributes']['Binding'])? '' : $item['ItemAttributes']['Binding'];
						$tmp_asin_data['brand'] = empty($item['ItemAttributes']['Brand'])? '' : $item['ItemAttributes']['Brand'];
						$tmp_asin_data['catalog_number'] = empty($item['ItemAttributes']['CatalogNumberList']['CatalogNumberListElement'])? '' : $item['ItemAttributes']['CatalogNumberList']['CatalogNumberListElement'];
						//color 和 size 和后台显示的 系类商品属性 有不同，可能不能展示于前端给用户筛选
						$tmp_asin_data['color'] = empty($item['ItemAttributes']['Color'])? '' : $item['ItemAttributes']['Color'];
						$tmp_asin_data['size'] = empty($item['ItemAttributes']['Size'])? '' : $item['ItemAttributes']['Size'];
						
						$tmp_asin_data['ean'] = empty($item['ItemAttributes']['EAN'])? '' : $item['ItemAttributes']['EAN'];
						$feature = empty($item['ItemAttributes']['Feature'])? '' : $item['ItemAttributes']['Feature'];
						if(is_array($feature))
							$feature = json_encode($feature);
						$tmp_asin_data['feature'] = $feature;
						$tmp_asin_data['label'] = empty($item['ItemAttributes']['Label'])? '' : $item['ItemAttributes']['Label'];
						$tmp_asin_data['publisher'] = empty($item['ItemAttributes']['Publisher'])? '' : $item['ItemAttributes']['Publisher'];
						$tmp_asin_data['studio'] = empty($item['ItemAttributes']['Studio'])? '' : $item['ItemAttributes']['Studio'];
						$tmp_asin_data['manufacturer'] = empty($item['ItemAttributes']['Manufacturer'])? '' : $item['ItemAttributes']['Manufacturer'];
						$tmp_asin_data['model'] = empty($item['ItemAttributes']['Model'])? '' : $item['ItemAttributes']['Model'];
						$tmp_asin_data['mpn'] = empty($item['ItemAttributes']['MPN'])? '' : $item['ItemAttributes']['MPN'];
						$tmp_asin_data['part_number'] = empty($item['ItemAttributes']['PartNumber'])? '' : $item['ItemAttributes']['PartNumber'];
						$tmp_asin_data['product_group'] = empty($item['ItemAttributes']['ProductGroup'])? '' : $item['ItemAttributes']['ProductGroup'];
						$tmp_asin_data['product_type_name'] = empty($item['ItemAttributes']['ProductTypeName'])? '' : $item['ItemAttributes']['ProductTypeName'];
						$tmp_asin_data['title'] = empty($item['ItemAttributes']['Title'])? '' : $item['ItemAttributes']['Title'];
						$tmp_asin_data['upc'] = empty($item['ItemAttributes']['UPC'])? '' : $item['ItemAttributes']['UPC'];
						//$Price = '';用户的价格数据应该通过mws或者查询top offer为自己时才更新
						$tmp_asin_data['currency'] = empty($item['ItemAttributes']['ListPrice']['CurrencyCode'])? '' : $item['ItemAttributes']['ListPrice']['CurrencyCode'];
						
						$tmp_response_data[$item['ASIN']] = $tmp_asin_data;
					}
				}
		
				return ['success'=>true,'message'=>'','data'=>$tmp_response_data ];
			}else{
				//todo error msg
				if(isset($response['Items']['Request']['Errors']))
					return ['success'=>false,'message'=>'api获取信息是失败：'.json_encode($response['Items']['Request']['Errors']) ];
				else
					return ['success'=>false,'message'=>'api获取信息是失败,errorMsg recorded.' ];
			}
		}else{
			return ['success'=>false,'message'=>'链接proxy失败：'.@$rtn['message'] ];
		}
	}
	
	/**
	 * 获取asin的图片信息，如果是个子商品，顺便返回parent asin
	 * @param string $marketplace_id
	 * @param string $Asin
	 * @return array
	 * @author lzhl 2017-07-13 初始化
	 */
	public static function getItemImages($marketplace_id,$Asin){//or MerchantItemAttributes?
		$Asin = trim($Asin);
		if(empty($Asin))
			return ['success'=>false,'message'=>'asin can not empty'];
	
		$action = 'GetProductAttributesByAsin';
		$config = [
			'marketplace_id'=>$marketplace_id,
		];
		$get_params['config'] = json_encode($config);
	
		$parms = [
			'response_group'=>'Images,ItemAttributes,Variations',
			'operation'=>'ItemLookup',
		];
		$get_params['parms'] = json_encode($parms);
			
		$post_params = [];
		$post_params['asin'] = $Asin;
		
		try{
			$rtn = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params,$time_out=60,$return_type='json',$post_params);
		}catch(\Exception $e) {
			return ['success'=>false,'message'=>'调用API发送Exception：'.print_r($e->getMessage()) ];
		}
		exit(json_encode($rtn));//liang test
	
		//print_r($rtn);
		$images = [];
		$parentAsin = '';
		if(!empty($rtn['success']) && !empty($rtn['response'])){
			if(!empty($rtn['response']['Items']['Item'])){
				//单产品
				if(!empty($rtn['response']['Items']['Item']['ASIN'])){
					$ImageSets = empty($rtn['response']['Items']['Item']['ImageSets']['ImageSet'])?[]:$rtn['response']['Items']['Item']['ImageSets']['ImageSet'];
					foreach ($ImageSets as $ImageSet){
						if(!empty($ImageSet['LargeImage']['URL'])){
							$images[] = $ImageSet['LargeImage']['URL'];
						}
					}
					//是否是子产品
					if(!empty($rtn['response']['Items']['Item']['ParentASIN']) && $rtn['response']['Items']['Item']['ASIN']!==$rtn['response']['Items']['Item']['ParentASIN']){
						$parentAsin = $rtn['response']['Items']['Item']['ParentASIN'];
					}
				}
				//多产品(本function非单产品查询，理论上不会出现这种格式)
				elseif(!empty($rtn['response']['Items']['Item'][0])){
					$ImageSets = empty($rtn['response']['Items']['Item'][0]['ImageSets']['ImageSet'])?[]:$rtn['response']['Items']['Item'][0]['ImageSets']['ImageSet'];
					foreach ($ImageSets as $ImageSet){
						if(!empty($ImageSet['LargeImage']['URL'])){
							$images[] = $ImageSet['LargeImage']['URL'];
						}
					}
					//是否是子产品
					if(!empty($rtn['response']['Items']['Item'][0]['ParentASIN']) && $rtn['response']['Items']['Item'][0]['ASIN']!==$rtn['response']['Items']['Item'][0]['ParentASIN']){
						$parentAsin = $rtn['response']['Items']['Item'][0]['ParentASIN'];
					}
				}
				else{
					//非预期格式
					return ['success'=>false,'message'=>'api return failed: '.print_r($rtn['response'],true)];
				}
				
				return ['success'=>true,'message'=>'','images'=>$images,'parent_asin'=>@$parentAsin];
				
			}else{
				if(!empty($rtn['response']['Items']['Request']['Errors']['Error']['Message']))
					return ['success'=>false,'message'=>$rtn['response']['Items']['Request']['Errors']['Error']['Message'] ];
				else 
					return ['success'=>false,'message'=>'api return failed: '.print_r($rtn['response'],true)];
			}
		}else{
			return ['success'=>false,'message'=>'链接proxy失败：'.@$rtn['message'] ];
		}
	}
	
	public static function updateOfferInfo(){
		
	}
	
	public static function updateAmazonListingAttrs(){
		
	}
	
	public static function updateAmazonListingImages(){
	
	}
	
	public static function importAsinForStore(){
		
	}
	
}//end of class
?>