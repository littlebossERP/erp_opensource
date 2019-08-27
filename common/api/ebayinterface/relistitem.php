<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use SimpleXMLElement;
use eagle\modules\listing\models\EbayItem;
use eagle\models\SaasEbayUser;
use common\helpers\Helper_xml;
/**
 * Relist刊登
 * @package interface.ebay.tradingapi
 */
class relistitem extends base{
    public $verb = 'RelistItem';
    
    public function api($itemid,$item,$quantity){
        $xmlArr=array(
				'Item'=>array(
				    'ItemID'=>$itemid,
					'UUID'=>$item['uuid'],
				),
		);
        $item=EbayItem::find()->where(['itemid'=>$itemid])->one();
        if ($item->listingtype=='FixedPriceItem' || count($item->detail->variation)>0){
			if ($quantity>1){
				$xmlArr['Item']['Quantity']=$quantity;
				$this->verb='RelistFixedPriceItem';
			}
        }
        
// 		$this->siteID=Ebay_Site::find('site = ?',$item['site'])->getOne()->siteid;
		
		$xml=$this->setRequestBody($xmlArr)->sendRequest(1);//->sendHttpRequest($xmlArr,1);
		$resultObj=new SimpleXMLElement($xml);
		$result=parent::simplexml2a($resultObj);
		$result['ebayfee']=self::readListingfee($resultObj);
        //对结果进行存储item表的处理
        return $result;
    }
    
    function doapi(EbayItem $item,$timerid=null){
		$token = SaasEbayUser::find()->where(['selleruserid'=>$item->selleruserid])->one()->token;
		return $this->api($item->itemid,$token,$item,$timerid);
    }
    

     function readListingfee($response){
		if(isset($response->Fees->Fee)){
			foreach($response->Fees->children() as $fee){
				if($fee->Name=='ListingFee'){
					return array(
						'fee'=>(string)$fee->Fee,
						'currency'=>(string)$fee->Fee->attributes()->currencyID
					);
					break;
				}
			}
		}
		return false;
	}
	
	/**
     * 
     * @author lx 20141230
     */
    function apirelist($itemid,$token){
    		$xmlArrInventoryStatus['Item']=array(
    			'ItemID'	=>	$itemid
    		);
    		
    		$xmlArr=array(
    			'RelistItemRequest  xmlns="urn:ebay:apis:eBLBaseComponents"'=>array(
    					'RequesterCredentials'=>array(
    							'eBayAuthToken'=>$token,
    					),
    					'Item'=>array(
    						'ItemID'	=>	$itemid
    					),
    			)
    	);
    	$result=$this->sendHttpRequest($xmlArr);
    	return $result;
    }
    
    /**
     * api 操作.
     *   $values=array(); //要修改的 Item 字段.
     	$itemid=null; //要修改的 ItemId.
     */
    function apiCore($itemid,$values){
    	//对进行修改的数据进行预处理
    	$xmlItem=$this->checkApiCoreItem($itemid,$values);
    	$xmlItem['ItemID']=$itemid;
    	$xmlArr=array(
    			'Item'=>$xmlItem,
    			//'ListingDuration'=>$this->eBayAuthToken,
    	);
    	if (isset($xmlItem['PictureErrors'])){
    		$PictureErrors = $xmlItem['PictureErrors'];
    		unset($xmlItem['PictureErrors']);
    	}
    	//得到 上传结果
    	$responseXml=$this->setRequestBody($xmlArr)->sendRequest(1);
    	$responseSXml=simplexml_load_string($responseXml);
    	$responseArr=parent::simplexml2a($responseSXml);
    	//取得总费用
    	//上传图片错误
    	if (isset($PictureErrors)){
    		$responseArr['PictureErrors'] = $PictureErrors;
    	}
    	$responseArr['ebayfee']=self::readListingfee($responseSXml);
    	return $responseArr;
    }
    
    /**
     * 检测  Item的修改 字段
     */
    function checkApiCoreItem($itemid,$values){
    	$xmlItem=array();
    	if(count($values)==0){
    		return $xmlItem;
    	}
        $itemT=EbayItem::find()->where(['itemid'=>$itemid])->one();
        $eu= SaasEbayUser::find()->where(['selleruserid'=>$itemT->selleruserid])->one();
    	$logostr = '<div style="margin-top:15px;margin-bottom:15px;"><center><a href="http://www.littleboss.com" target="_new"><img border=0 width=88 height=33 src="http://www.littleboss.com/images/logo_2.png"></a></center></div>';
    	// set values
    	foreach($values as $k=>$v){
    		if (is_string($v) &&  strlen($v)==0){
    			continue;
    		}
    		switch($k){
    			######################平台与细节#################################
    			case 'primarycategory':$xmlItem['PrimaryCategory']=array('CategoryID'=>$v);break;
    			case 'secondarycategory':$xmlItem['SecondaryCategory']=array('CategoryID'=>$v);break;
    			case 'conditionid':$xmlItem['ConditionID']=$v;break;
    			case 'specific':
    				foreach ($v as $Name=>$Value){
    					$xmlItem['ItemSpecifics']['NameValueList'][]=array('Name'=>'<![CDATA['.$Name.']]>','Value'=>'<![CDATA['.$Value.']]>');
    				}
    				break;
    				#######################标题与价格################################
    			case 'itemtitle':$xmlItem['Title']='<![CDATA['.$v.']]>';break;
    			case 'subtitle':$xmlItem['SubTitle']='<![CDATA['.$v.']]>';break;
    			case 'sku':$xmlItem['SKU']='<![CDATA['.$v.']]>';break;
    			case 'quantity':$xmlItem['Quantity']=intval($v);break;
    			case 'lotsize':
    				if($v>0){
    					$xmlItem['LotSize']=$v;
    				}
    				break;
    			case 'listingduration':$xmlItem['ListingDuration']=$v;break;
    			case 'startprice':$xmlItem['StartPrice']=$v;break;
    			case 'buyitnowprice':$xmlItem['BuyItNowPrice']=$v;break;
    			case 'bestoffer':$xmlItem['BestOfferDetails']['BestOfferEnabled']=$v==1?"True":"False";break;
    			case 'bestofferprice':
    				if ($v>0&&$xmlItem['BestOfferDetails']['BestOfferEnabled']){
    					$xmlItem['ListingDetails']['BestOfferAutoAcceptPrice']=$v;
    				}
    				break;
    			case 'minibestofferprice':
    				if ($v>0&&$xmlItem['BestOfferDetails']['BestOfferEnabled']){
    					$xmlItem['ListingDetails']['MinimumBestOfferPrice']=$v;
    				}
    				break;
    			case 'privatelisting':
    				$xmlItem['PrivateListing']=$v;
    				break;
    				#######################图片与描述################################
    			case 'imgurl' :
    				//图片处理，多图自动上传到eBay
    				if (count($v)>1){
    					$img_arr=array();
    					foreach ($v as $localimgurl){
    						if (strpos($localimgurl, 'ebayimg.com')===false){
    							$xmlItem['PictureDetails']['PhotoDisplay ']='VendorHostedPictureShow';
    							$pictureManager=new UploadSiteHostedPictures();
    							$pictureManager->siteID=$this->siteID;
                                $pictureManager->resetConfig($eu->listing_devAccountID);
    							$pictureManager->eBayAuthToken=$this->eBayAuthToken;
    							$url=$pictureManager->upload($localimgurl);
    							if (isset($url['SiteHostedPictureDetails']['FullURL'])){
    								$url=$url['SiteHostedPictureDetails']['FullURL'];
    							}else{
    								$xmlItem['PictureErrors'][]='图片【'.$localimgurl.'】上传失败! 错误信息【'.$url['Errors']['LongMessage'].'】<br/>';
    								continue;
    							}
    							$img_arr[]='<![CDATA['.$url.']]>';
    						}else{
    							$img_arr[]='<![CDATA['.$localimgurl.']]>';
    						}
    					}
    					$xmlItem['PictureDetails']['PictureURL']=$img_arr;
    				}else{
    					$url='';
    					$url=$v[0];
    					if (strlen($url)){
    						$xmlItem['PictureDetails']['PictureURL']='<![CDATA['.$url.']]>';
    					}
    				}
    				break;
    			case 'itemdescription' :$xmlItem['Description']='<![CDATA['.$v.$logostr.']]>';break;
    			#######################物流设置################################
    			case 'shippingdetails':
    				//$v['ShippingType']='Flat';
    				//对shippingDeatils的处理,未选择物流的不进行request
    				//多重物流进行优先级设置
    				$d=$e=1;
    				if(isset($v['ShippingServiceOptions'])){
    					if (isset($v['ShippingServiceOptions']['ShippingService'])){
    						$v['ShippingServiceOptions']=array($v['ShippingServiceOptions']);
    					}
    					foreach ($v['ShippingServiceOptions'] as $sk=>$sv){
    						if (strlen($sv['ShippingService'])){
    							$v['ShippingServiceOptions'][$sk]['ShippingServicePriority']=$d++;
    						}else{
    							unset($v['ShippingServiceOptions'][$sk]);
    						}
    						if (isset($sv['ExpeditedService'])){
    							unset($v['ShippingServiceOptions'][$sk]['ExpeditedService']);
    						}
    						if (isset($sv['ShippingTimeMin'])){
    							unset($v['ShippingServiceOptions'][$sk]['ShippingTimeMin']);
    						}
    						if (isset($sv['ShippingTimeMax'])){
    							unset($v['ShippingServiceOptions'][$sk]['ShippingTimeMax']);
    						}
                            //willage-2017-08-30 ShippingSurcharge为0,清除
    						if ((isset($sv['ShippingSurcharge'])&&!$sv['ShippingSurcharge']>0) ||$sv['ShippingSurcharge']=='0'||$sv['ShippingSurcharge']=='0.00'||is_null(@$sv['ShippingSurcharge'])){
    							unset($v['ShippingServiceOptions'][$sk]['ShippingSurcharge']);
    						}
    					}
    				}
    				if(isset($v['InternationalShippingServiceOption'])){
    					if (isset($v['InternationalShippingServiceOption']['ShippingService'])){
    						$v['InternationalShippingServiceOption']=array($v['InternationalShippingServiceOption']);
    					}
    					foreach ($v['InternationalShippingServiceOption'] as $sk=>$sv){
    						if (strlen($sv['ShippingService'])){
    							$v['InternationalShippingServiceOption'][$sk]['ShippingServicePriority']=$e++;
    						}else{
    							unset($v['InternationalShippingServiceOption'][$sk]);
    						}
    						if (isset($sv['ExpeditedService'])){
    							unset($v['InternationalShippingServiceOption'][$sk]['ExpeditedService']);
    						}
    						if (isset($sv['ShippingTimeMin'])){
    							unset($v['InternationalShippingServiceOption'][$sk]['ShippingTimeMin']);
    						}
    						if (isset($sv['ShippingTimeMax'])){
    							unset($v['InternationalShippingServiceOption'][$sk]['ShippingTimeMax']);
    						}
    					}
    				}
    				//如果物流类型非calculate，删除post过来的calculate字段值
    				if ($v['shippingdomtype'] == 'Flat' && $v['shippinginttype'] == 'Flat'){
    					unset($v['CalculatedShippingRate']);
    				}
    				//如果没有设置消费税，则删除默认的消费税数据
    				if (isset($v['SalesTax'])&&($v['SalesTax']['SalesTaxPercent'] == '0.0' || strlen($v['SalesTax']['SalesTaxPercent'])==0)){
    					unset($v['SalesTax']);
    				}
    				$xmlItem['ShippingDetails']=$v;
    				break;
    			case 'dispatchtime':$xmlItem['DispatchTimeMax']=$v;break;
    			#######################收款与退货################################
    			case 'paymentmethods':$xmlItem['PaymentMethods']=$v;break;
    			case 'autopay':$xmlItem['AutoPay']=$v==1?"True":"False";break;
    			case 'return_policy':$xmlItem['ReturnPolicy']=$v;break;
    			case 'country':$xmlItem['Country']=$v;break;
    			case 'location':$xmlItem['Location']=$v;break;
    			case 'postalcode':$xmlItem['PostalCode']=$v;break;
    			#######################买家要求################################
    			case 'buyerrequirementdetails':$xmlItem['BuyerRequirementDetails'] = $v;break;
    			#######################增强设置################################
    			case 'gallery':$xmlItem['PictureDetails']['GalleryType']=$v;break;
    			case 'listingenhancement':$xmlItem['ListingEnhancement']=$v;break;
    			case 'hitcounter':$xmlItem['HitCounter']=$v;break;
    			#######################账号相关################################
    			case 'paypal':$xmlItem['PayPalEmailAddress']=$v;break;
    			case 'storecategoryid':
    				if ($v>0){
    					$xmlItem['Storefront']['StoreCategoryID']=$v;
    				}
    				break;
    			case 'storecategory2id':
    				if ($v>0){
    					$xmlItem['Storefront']['StoreCategory2ID']=$v;
    				}
    				break;
    				########################多属性暂时不能修改todo###############################
    			case 'variation':
    				//如果有细节的，进行细节内容上传
    				if (is_array($v['Variation'])){
    					//xml 处理
    					$variations=new \SimpleXMLElement('<Variations></Variations>');
    					$_vss=$variations->addChild('VariationSpecificsSet');
    					if (isset($v['VariationSpecificsSet']['NameValueList']['Name'])){
    						$temp_arr = $v['VariationSpecificsSet']['NameValueList'];
    						unset($v['VariationSpecificsSet']['NameValueList']);
    						$v['VariationSpecificsSet']['NameValueList'][0]=$temp_arr;
    					}
    					foreach ($v['VariationSpecificsSet']['NameValueList'] as $nvl){
    						$_nvl=$_vss->addChild('NameValueList');
    						$_name=$_nvl->addChild('Name');
    						Helper_xml::addCData($_name,$nvl['Name']);
    						if (!is_array($nvl['Value'])){
    							$temp_arr=array(0=>$nvl['Value']);
    							$nvl['Value']=$temp_arr;
    						}
    						foreach ($nvl['Value'] as $value){
    							$_value=$_nvl->addChild('Value');
    							Helper_xml::addCData($_value,$value);
    						}
    					}
    					//属性处理
    					$i=0;
    					foreach ($v['Variation'] as $variationone){
    						$_v=$variations->addChild('Variation');
    						$_v->addChild('StartPrice',$variationone['StartPrice']);
    						$_v->addChild('Quantity',$variationone['Quantity']);
    						$_v->addChild('SKU',$variationone['SKU']);
    						$_vs=$_v->addChild('VariationSpecifics');
    						if (isset($variationone['VariationSpecifics']['NameValueList']['Value'])){
    							$temp_arr = $variationone['VariationSpecifics']['NameValueList'];
    							unset($variationone['VariationSpecifics']['NameValueList']);
    							$variationone['VariationSpecifics']['NameValueList'][0]=$temp_arr;
    						}
    						foreach ($variationone['VariationSpecifics']['NameValueList'] as $nvl){
    							$_nvl=$_vs->addChild('NameValueList');
    							$_name=$_nvl->addChild('Name');
    							Helper_xml::addCData($_name,$nvl['Name']);
    							$_value=$_nvl->addChild('Value');
    							Helper_xml::addCData($_value,$nvl['Value']);
    						}
    						$i++;
    					}
    					//图片处理
    					if (!empty($v['Pictures'])){
    						//图片绑定属性
    						$pnode=$variations->addChild('Pictures');
    						$pnode->addChild('VariationSpecificName',$v['Pictures']['VariationSpecificName']);
    						foreach ($v['Pictures']['VariationSpecificPictureSet'] as $picture){
    							$vspnode=$pnode->addChild('VariationSpecificPictureSet');
    
    							$_vsv_node=$vspnode->addChild('VariationSpecificValue');
    							Helper_xml::addCData($_vsv_node,$picture['VariationSpecificValue']);
    								
    							if (!is_array($picture['PictureURL'])){
    								$temp_arr=array(0=>$picture['PictureURL']);
    								$picture['PictureURL']=$temp_arr;
    							}
    							foreach ($picture['PictureURL'] as $url){
    								if (strlen($url)){
    									$vspnode->addChild('PictureURL',$url);
    								}
    							}
    
    						}
    					}
    				}
    				$xmlItem['Variations']=$variations;
    				break;
    		}
    		 
    	}
    	//去掉不修改的数据
    	if (isset($xmlItem['ShippingDetails']['SellerExcludeShipToLocationsPreference'])){
    		unset($xmlItem['ShippingDetails']['SellerExcludeShipToLocationsPreference']);
    	}
    	return $xmlItem;
    }
}
