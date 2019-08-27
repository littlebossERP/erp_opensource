<?php

namespace eagle\modules\lazada\apihelpers;
use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use \Yii;
use common\api\lazadainterface\LazadaInterface_Helper;
use eagle\models\LazadaCategories;
use eagle\models\LazadaCategoryAttr;
use eagle\models\SaasLazadaAutosync;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\models\SaasLazadaUser;
use eagle\modules\listing\models\LazadaPublishListing;
use eagle\modules\listing\helpers\LazadaFeedHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\models\LazadaBrand;
use eagle\modules\message\helpers\MessageBGJHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\util\models\UserBackgroundJobControll;
use common\helpers\Helper_Array;
use eagle\modules\listing\helpers\LazadaAutoFetchListingHelper;

/**
 +------------------------------------------------------------------------------
 * Lazada 数据同步类 主要执行非订单类数据同步
 +------------------------------------------------------------------------------
 */

class SaasLazadaAutoSyncApiHelper {
	
	public static $cronJobId=0;
	private static $lazadaAutoSyncVersion = null;

	/**
	 * @return the $cronJobId
	*/
	public static function getCronJobId() {
		return self::$cronJobId;
	}
	
	/**
	 * @param number $cronJobId
	 */
	public static function setCronJobId($cronJobId) {
		self::$cronJobId = $cronJobId;
	}
	
	/**
	 * 先判断是否真的抢到待处理账号
	 * @param  $autosyncId  -- saas_lazada_autosync表的id
	 * @return null或者$SAA_obj , null表示抢不到记录
	 */
	private static function _lockLazadaAutosyncRecord($autosyncId){
		$nowTime=time();
		$connection=Yii::$app->db;
		$command = $connection->createCommand("update saas_lazada_autosync set status=1,last_finish_time=$nowTime where id =". $autosyncId." and status<>1 ") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
		// 抢到记录
		$SAA_obj = 	SaasLazadaAutosync::findOne($autosyncId);
		return $SAA_obj;
	}
	
	/**
	 * 先判断在线商品是否修改中
	 * @param  $listingId  -- saas_lazada_autosync表的id
	 * @return null或者$SAA_obj , null表示抢不到记录
	 */
	private static function _lockLazadaListingRecord($listingId,$type){
		$nowTime=time();
		$connection=Yii::$app->get("subdb");
		$command = $connection->createCommand("update lazada_listing set is_editing=$type,update_time=$nowTime where id=$listingId and is_editing=0 ") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
		// 抢到记录
		$listing =	LazadaListing::findOne($listingId);
		return $listing;
	}
	private static function _lockLazadaListingRecordV2($listingId,$type){
		$old=LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING)->findAndModify(array("_id"=>$listingId),array('$set'=>array("isEditing"=>$type)));
		if($old["isEditing"]==$type){
			return null;
		}
		return $old;
	}
	/**
	 * 该进程判断是否需要退出
	 * 通过配置全局配置数据表ut_global_config_data的Order/lazadaGetOrderVersion 对应数值
	 *
	 * @return  true or false
	 */
	private static function checkNeedExitNot(){
		$lazadaAutoSyncVersionFromConfig = ConfigHelper::getGlobalConfig("Order/lazadaAutoSyncVersion",'NO_CACHE');
		if (empty($lazadaAutoSyncVersionFromConfig))  {
			//数据表没有定义该字段，不退出。
			//	self::$lazadaGetOrderListVersion ="v0";
			return false;
		}
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (self::$lazadaAutoSyncVersion===null)	self::$lazadaAutoSyncVersion = $lazadaAutoSyncVersionFromConfig;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$lazadaAutoSyncVersion <> $lazadaAutoSyncVersionFromConfig){
			echo "Version new $lazadaAutoSyncVersionFromConfig , this job ver ".self::$lazadaAutoSyncVersion." exits \n";
			return true;
		}
		return false;
	}
	
	/**
	* 获取所有lazada用户的api访问信息。 email,token,销售站点
	 */
	private static function getAllLazadaAccountInfoMap(){
		$lazadauserMap = array();
	
		$lazadaUsers = SaasLazadaUser::find()->where('status<>3')->all();
		foreach($lazadaUsers as $lazadaUser){
			$lazadauserMap[$lazadaUser->lazada_uid]=array(
					"userId"=>$lazadaUser->platform_userid,
					"apiKey"=>$lazadaUser->token,
					"platform"=>$lazadaUser->platform,
					"countryCode"=>$lazadaUser->lazada_site,
					"puid"=>$lazadaUser->puid,
			);
		}
	
		return $lazadauserMap;
	}
	
	/**
	 * 从proxy中获取目录树
	 * TODO 可以根据 json结构
	 * {
     *    "categoryId": "8291",
     *    "categoryName": "Vehicles and Motorcycles",
     *    "isLeaf": false,
     *    "level": 1,
     *    "parentCategoryId": 0,
     *    "site": "pe"
     * }
     * 更改lazada_categories 表的结构，添加字段，这样也适合前端展示，
     * 如果更改结构的话，最好把lazada_categories的引擎改为InnoDB
     * 
	 * @param array $config
	 * @param boolean $loadCache 
	 */
	public static function getCategoryTree($config,$loadCache=true){
		// 目录先记录在数据库中，后面再考虑可能搬到redis里面
		$categoriesJsonStrObj = LazadaCategories::findOne(['site'=>$config['countryCode']]);
		if(empty($categoriesJsonStrObj) || empty($categoriesJsonStrObj->categories) 
		        || $categoriesJsonStrObj->categories == "[]" ||$loadCache == false){
			$response = LazadaInterface_Helper::getCategoryTree($config);
			
			if($response['success'] != true){
				return array(false , $response['message']);
			}
			
			$categoryTree = $response['response']['categories'];
			$categories = array();
			foreach ($categoryTree as $category){
			    // dzt20161215 lazada 更新接口后，使用不同解释目录的function
    			if (array_key_exists($config['countryCode'], LazadaApiHelper::getLazadaCountryCodeSiteMapping())) {
    			    LazadaApiHelper::getCategoryInfoV3($category , 1 , 0 , $config['countryCode'] , $categories);
    			} else {
    			    LazadaApiHelper::getCategoryInfo($category , 1 , 0 , $config['countryCode'] , $categories);
    			}
			}
		
			$categoriesJsonStr = json_encode($categories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
			
			$nowTime = time();
			if(empty($categoriesJsonStrObj)){
				$categoriesJsonStrObj = new LazadaCategories();
				$categoriesJsonStrObj->create_time = $nowTime;
			}
				
			$categoriesJsonStrObj->site = $config['countryCode'];
			$categoriesJsonStrObj->categories = $categoriesJsonStr;
			$categoriesJsonStrObj->update_time = $nowTime;
			if(!$categoriesJsonStrObj->save()){
				\Yii::error("getCategoryTree error:".print_r($categoriesJsonStrObj->errors,true),"file");
				return array(false , "获取目录失败");
			}
		}else{
			$categoriesJsonStr = $categoriesJsonStrObj->categories;
			$categories = json_decode($categoriesJsonStr , true);
		}
		
		if(empty($categories)){
			return array(false , "获取目录失败");
		}
		
		return array(true , $categories);
	}
	
	/**
	 * 从proxy中获品牌 ， 从印尼和马来西亚的销售站点来看，品牌不是共用的。
	 * @param array $config
	 */
	public static function getBrands($config,$searchName="",$searchMode="like",$purge=false){
		// 目录先记录在数据库中，后面再考虑可能搬到redis里面
		// 本来是想和目录树和目录属性一样，一个站点一个记录，记录所有品牌，但这次 由于sql太大 还是要拆分来完成了
		$brands = LazadaBrand::find()->where(['site'=>$config['countryCode']])->asArray()->all();
		$filterBrands = array();
		if(empty($brands) || $purge){
			$response = LazadaInterface_Helper::getBrands($config);
			if($response['success'] != true){
				return array(false , $response['message']);
			}

			if($purge == true){
				LazadaBrand::deleteAll(['site'=>$config['countryCode']]);
			}
			
			$objSchema = LazadaBrand::getTableSchema();
			
			$brands = $response['response']['brands'];
			// echo "count".count($brands)."\n";
			$batchInsertArr = array();
			foreach ($brands as &$brand){
			    $brand['site'] = $config['countryCode'];
			  	$toAddListing = array();
				foreach ($objSchema->columnNames as $column){
					if(isset($brand[$column])){
						$toAddListing[$column] = $brand[$column];
					} else {// null 值
						$toAddListing[$column] = '';
					}
				}
				
				$batchInsertArr[] = $toAddListing; 
			}
			
			// echo "count".count($batchInsertArr)."\n";
			
			try{
				// dzt20170218 未知原因导致 没有 GlobalIdentifier 的 即使定义为'' 但依然无法插入 ， 改用yii 接口
				// SQLHelper::groupInsertToDb("lazada_brand", $brands, 'db');

				$insertNum = \Yii::$app->db->createCommand()->batchInsert(LazadaBrand::tableName(), $objSchema->columnNames, $batchInsertArr)->execute();
			
				LazadaBrand::updateAll(['update_time'=>time()],['site'=>$config['countryCode']]);
			}catch(\Exception $ex){
			    \Yii::error('getBrands groupInsertToDb config:'.print_r($config,true).' exception:file:'.$ex->getFile().',line:'.$ex->getLine().',message:'.$ex->getMessage(),"file");
				return array(false , "更新错误。".$ex->getMessage());
			}
			
		}
		
		foreach ($brands as $oneBrand){
			if(!empty($searchName)){
				if("like" == $searchMode){// 类似模糊搜索 结果最多20个
					if(count($filterBrands) <= 20 && false !== stripos($oneBrand['Name'], $searchName)){// 如果有搜索 限制搜索结果为20个
						$filterBrands[] = $oneBrand['Name'];
					}
				}else{// 完全匹配
					if(strcmp($oneBrand['Name'],$searchName) == 0){
						$filterBrands[] = $oneBrand['Name'];
						return array(true , $filterBrands);
					}
				}
				
			}else{
				$filterBrands[] = $oneBrand['Name'];
			}
		}
		
		if(empty($filterBrands)){
			return array(false , "获取品牌失败");
		}
		
		return array(true , $filterBrands);
	}
	
	/**
	 * 从proxy中获取目录属性
	 * @param array $config
	 * @param int $primaryCategory 请求目录
	 */
	public static function getCategoryAttributes($config,$primaryCategory=-1,$loadCache=true){
		$categoryAttrsJsonStrObj = LazadaCategoryAttr::findOne(['site'=>$config['countryCode'] , 'categoryid'=>$primaryCategory]);
		$attributes = array();
		if(empty($categoryAttrsJsonStrObj) || $loadCache == false){
			$response = LazadaInterface_Helper::getCategoryAttributes($config,array('PrimaryCategory'=>$primaryCategory));
			if($response['success'] != true){
				return array(false , $response['message']);
			}
			$attributes = $response['response']['attributes'];
			$nowTime = time();
			if(empty($categoryAttrsJsonStrObj)){
				$categoryAttrsJsonStrObj = new LazadaCategoryAttr();
				$categoryAttrsJsonStrObj->create_time = $nowTime;
			}
			
			$categoryAttrsJsonStrObj->site = $config['countryCode'];
			$categoryAttrsJsonStrObj->categoryid = $primaryCategory;
			$categoryAttrsJsonStrObj->attributes = json_encode($attributes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
			$categoryAttrsJsonStrObj->update_time = $nowTime;
			if(!$categoryAttrsJsonStrObj->save()){
				\Yii::error("getCategoryAttributes error:".print_r($categoryAttrsJsonStrObj->errors,true),"file");
				return array(false , "获取目录属性失败");
			}
		}else{
			$attributes = json_decode($categoryAttrsJsonStrObj->attributes , true);
		}

		if(empty($attributes)){
			return array(false , "获取目录属性失败");
		}
		
		return array(true , $attributes);
	}
	
	/**
	 * 产品发布
	 * 
	 * @param array $publishListingIds 待发布产品的id
	 */
	public static function productPublish($publishListingIds=array()){
		try {
			$sendSuccess = 0;
			if(!empty($publishListingIds)){
				$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
				$products = array('create'=>array(),'update'=>array());// 根据账号分组的 待上传产品信息数组
				$prodIds = array('create'=>array(),'update'=>array());// 根据账号分组的 待上传产品 的 刊登产品Id
				foreach ($publishListingIds as $publishListingId){
					// 也许是网络问题，所以这里失败也允许 进入发布
					$connection=Yii::$app->subdb;
					$command = $connection->createCommand("update lazada_publish_listing set status='".LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1]."' where id=". $publishListingId." and ( state='".LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT."' or state='".LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL."' ) ") ;
					$record = $command->execute();
					if ($record <= 0)//抢不到---如果是多进程的话，有抢不到的情况
						continue; 
					
					// 抢到记录
					$publishListing = LazadaPublishListing::findOne($publishListingId);
					$storeInfo = json_decode($publishListing->store_info,true);
					$baseInfo = json_decode($publishListing->base_info,true);
					$imageInfo = json_decode($publishListing->image_info,true);
					$descriptionInfo = json_decode($publishListing->description_info,true);
					$shippingInfo = json_decode($publishListing->shipping_info,true);
					$warrantyInfo = json_decode($publishListing->warranty_info,true);
					$variantData = json_decode($publishListing->variant_info,true);
					
					// 清空空值的key,以及trim 所有value
					Helper_Array::removeEmpty($storeInfo,true);
					Helper_Array::removeEmpty($baseInfo,true);
					Helper_Array::removeEmpty($imageInfo,true);
					Helper_Array::removeEmpty($descriptionInfo,true);
					Helper_Array::removeEmpty($shippingInfo,true);
					Helper_Array::removeEmpty($warrantyInfo,true);
					Helper_Array::removeEmpty($variantData,true);
					
					// 1.必要属性检查
					if(self::_checkPropertyIsEmpty('PrimaryCategory',$storeInfo['primaryCategory'],$publishListing)) continue;
					if(self::_checkPropertyIsEmpty('Brand',$baseInfo['Brand'],$publishListing)) continue;
					if(self::_checkPropertyIsEmpty('Name',$baseInfo['Name'],$publishListing)) continue;
					// 接口上看 其他两个平台都必填，lazada不是必填，但lazada卖家后台显示必填，想审核通过也是必填
					if(self::_checkPropertyIsEmpty('Description',$descriptionInfo['Description'],$publishListing)) continue;
					
					// 平台必填区别 
					if('lazada' == $publishListing->platform){
						if(self::_checkPropertyIsEmpty('PackageContent',$descriptionInfo['PackageContent'],$publishListing)) continue;
						
						if(self::_checkPropertyIsEmpty('PackageLength',$shippingInfo['PackageLength'],$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('PackageWidth',$shippingInfo['PackageWidth'],$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('PackageHeight',$shippingInfo['PackageHeight'],$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('PackageWeight',$shippingInfo['PackageWeight'],$publishListing)) continue;
						// sg my ShortDescription貌似必填
						if($publishListing->site == "my"){// my才强制
							// dzt20160520 前端去掉 让客户填写 'NameMs','DescriptionMs'，直接再这里从 'Name','Description' 复制
							$baseInfo['NameMs'] = $baseInfo['Name'];
							$descriptionInfo['DescriptionMs'] = $descriptionInfo['Description'];
							
							// dzt20160226 马来西亚也去掉必填
// 							if(self::_checkPropertyIsEmpty('MaxDeliveryTime',$shippingInfo['MaxDeliveryTime'],$publishListing)) continue;
// 							if(self::_checkPropertyIsEmpty('MinDeliveryTime',$shippingInfo['MinDeliveryTime'],$publishListing)) continue;
							
							if(self::_checkPropertyIsEmpty('TaxClass',$baseInfo['TaxClass'],$publishListing)) continue;
						}
					}else if('linio' == $publishListing->platform){
// 						if(self::_checkPropertyIsEmpty('DeliveryTimeSupplier',$baseInfo['DeliveryTimeSupplier'],$publishListing)) continue;// dzt20151222 comment： 墨西哥要求必填，但 哥伦比亚和智利的目录没有这个属性
						
						if(self::_checkPropertyIsEmpty('PackageLength',$shippingInfo['PackageLength'],$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('PackageWidth',$shippingInfo['PackageWidth'],$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('PackageHeight',$shippingInfo['PackageHeight'],$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('PackageWeight',$shippingInfo['PackageWeight'],$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('TaxClass',$baseInfo['TaxClass'],$publishListing)) continue;
					}else if('jumia' == $publishListing->platform){
						if(self::_checkPropertyIsEmpty('ShortDescription',$descriptionInfo['ShortDescription'],$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('ProductWeight',$shippingInfo['ProductWeight'],$publishListing)) continue;
					}
					
					// 但是我们为了后面不再查错，所以这里强制要求先上传图片
					if(empty($imageInfo['Product_photo_primary']) && empty($imageInfo['Product_photo_others'])){
						self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"Images cannot be empty.");
						continue;	
					}
					
					$hasVariantProblem = false;
					$parentSku = "";
					$existingUpSkus = json_decode($publishListing->uploaded_product,true); // 已上传到Lazada后台的产品 sku
					
					foreach ($variantData as $oneVariant){
						// dzt20151216 在jumia 后台操作保存过，可以不填，之前的两个平台也可以不填，但是getAttr接口看到这个字段是必填。
// 						$hasVariantProblem = self::_checkPropertyIsEmpty('Variation',$oneVariant['Variation'],$publishListing);
// 						if($hasVariantProblem) break;
						
						// 接口上看 其他两个平台都不是必填，linio是必填，但这里我们set成必填
						$hasVariantProblem = self::_checkPropertyIsEmpty('SellerSku',$oneVariant['SellerSku'],$publishListing);
						if($hasVariantProblem) break;
						
						// dzt20151113 调试productCreate Price又不是必填的了。 但是我们为了后面不再查错，所以这里强制要求填
						$hasVariantProblem = self::_checkPropertyIsEmpty('Price',$oneVariant['Price'],$publishListing);
						if($hasVariantProblem) break;
						
						// 接口上看 其他所有平台都不是必填，但这里我们set成必填
						$hasVariantProblem = self::_checkPropertyIsEmpty('Quantity',$oneVariant['Quantity'],$publishListing);
						if($hasVariantProblem) break;
					
						// 检查 Sale price must be lower than the standard price. Sale Price must be blank if standard price is blank
						
						if(isset($oneVariant['SalePrice']) && $oneVariant['SalePrice'] > $oneVariant['Price'] ){
							self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Sale price must be lower than the standard price.");
							$hasVariantProblem = true;
							if($hasVariantProblem) break; 
						}

						// 有SalePrice SaleStartDate SaleEndDate 三个必须同时存在,否则 linio 报Internal Application Error错
						if(!empty($oneVariant['SalePrice']) || !empty($oneVariant['SaleStartDate']) || !empty($oneVariant['SaleEndDate'])){
							if(empty($oneVariant['SalePrice'])){
								self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"SalePrice is empty.Please complete the sales information.");
								$hasVariantProblem = true;
								if($hasVariantProblem) break;
							}
								
							if(empty($oneVariant['SaleStartDate'])){
								self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"SaleStartDate is empty.Please complete the sales information.");
								$hasVariantProblem = true;
								if($hasVariantProblem) break;
							}	
							
							if(empty($oneVariant['SaleEndDate'])){
								$hasVariantProblem = self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"SaleEndDate is empty.Please complete the sales information.");
								$hasVariantProblem = true;
								if($hasVariantProblem) break;
							}	
						}
						
						// 平台必填区别
						if('linio' == $publishListing->platform){
							$hasVariantProblem = self::_checkPropertyIsEmpty('ProductId',$oneVariant['ProductId'],$publishListing);
						}
						if($hasVariantProblem) break;
						
						if(count($variantData) > 1 && empty($parentSku)){
							$parentSku = $oneVariant['SellerSku'];
						}
					}
					
					if($hasVariantProblem) continue;
					
					// dzt20160413 有在线产品 A ，准备刊登产品 父产品和A的sku 一样，另外的子产品不存在重复
					// 这样刊登的时候会出现，父产品刊登失败提示,sku已存在，但另外的子产品就加到已存在产品的子产品里面
					// 所以这里暂时先加一个检查父产品是否再我们的在线商品里面。如果有就停止这个产品的刊登。
					// @todo 后面应该改成先让父产品刊登，后面再一起提交子产品。这样才稳妥一点
					if(!empty($parentSku) && !empty($existingUpSkus) && !in_array($parentSku, $existingUpSkus)){
						$isExist = LazadaListing::findOne(['SellerSku'=>$parentSku,'lazada_uid_id'=>$publishListing->lazada_uid]);
						if(!empty($isExist)){
							self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"Field SellerSku with value '".$parentSku."' has a problem: You already have another product with the SKU '".$parentSku."'");
							continue;
						}
					}
					
					// 2.组合产品数据内容
					$product = array();
					$productData = array();
					// 查阅了几个马来西亚的主目录 full call api 发现都是21 个属性under product tag 差异主要在ProductData tag里的内容
					// 查阅墨西哥的主目录 full call api发现都是21 个属性under product tag 
// 					<Brand></Brand>
// 					<Description></Description>
// 					<Name></Name>
// 					<Price>61.95</Price>
// 					<PrimaryCategory></PrimaryCategory>
// 					<SellerSku></SellerSku>
// 					<TaxClass></TaxClass>
// 					<Categories>5, 94, 1075</Categories>
// 					<Condition>New, Refurbish</Condition>
// 					<CountryCity></CountryCity>
// 					<ParentSku>ABC-1000-202</ParentSku>
// 					<ProductGroup></ProductGroup>
// 					<ProductId>978-3-16-148410-0</ProductId>
// 					<Quantity>50</Quantity>
// 					<SaleEndDate>2000-05-21</SaleEndDate>
// 					<SalePrice>233.45</SalePrice>
// 					<SaleStartDate>2013-05-21</SaleStartDate>
// 					<ShipmentType></ShipmentType>
// 					<Status></Status>
// 					<Variation>Earphone fluffy blue</Variation>
		
// 					<BrowseNodes></BrowseNodes>// jumia 的, linio，lazada 没有
// 					<ProductData></ProductData>
					$notProDataProperties = array('Brand','Description','Name','Price','PrimaryCategory','SellerSku','TaxClass','Categories','Condition',
					'CountryCity','ParentSku','ProductGroup','ProductId','Quantity','SaleEndDate','SalePrice','SaleStartDate','ShipmentType',
					'Status','Variation','BrowseNodes');
					
					$product['PrimaryCategory'] = $storeInfo['primaryCategory'];
					if(!empty($storeInfo['categories']) && 'lazada' <> $publishListing->platform && 'jumia' <> $publishListing->platform)// dzt20151225 lazada后台没得填这选项，去掉
						$product['Categories'] = implode(",", $storeInfo['categories']);
					
					// dzt20161018 发现lazada api接口获取到的选项包含'&' 字符也是报错“Invalid Request Format” 所以统一 htmlentities处理
					foreach ($baseInfo as $key=>$value){
						if(!empty($value)){
							//dzt20151203 linio 由于get attr api 返回该属性为 input 输入，但接口只接受 boolean ，改为checkbox 形式后 ， checkbox的value设置也不是boolean值，所以界面填了YES，这里要装换。
							if('linio' == $publishListing->platform && $key == 'EligibleFreeShipping' && strtolower($value) == 'yes'){
								$value = true;
							}
							
							if('jumia' == $publishListing->platform && $key == 'JumiaLocal' && strtolower($value) == 'yes'){
							    $value = true;
							}
							
							if('jumia' == $publishListing->platform && $key == 'BrowseNodes'){// @todo 貌似linio 也是这样的， 待测试
								$browserNodes = array();
								foreach ($value as $groupNodes){
									$browserNodes[] = $groupNodes[0];
								}
								$value = implode(',', $browserNodes);
							}
							
							$value = is_string($value)?LazadaApiHelper::transformFeedString($value):$value;
							if(in_array($key, $notProDataProperties)){
								$product[$key] = $value;
							}else{
								$productData[$key] = $value;
							}
						}
					}
					
					foreach ($descriptionInfo as $key=>$value){
						if(!empty($value)){
							if(in_array($key, $notProDataProperties)){
								$product[$key] = "<![CDATA[".$value."]]>";
							}else{
								$productData[$key] = "<![CDATA[".$value."]]>";
							}
						}
					}
					
					foreach ($shippingInfo as $key=>$value){
						if(!empty($value)){
							$value = is_string($value)?LazadaApiHelper::transformFeedString($value):$value;
							if(in_array($key, $notProDataProperties)){
								$product[$key] = $value;
							}else{
								$productData[$key] = $value;
							}
						}
					}
					
					foreach ($warrantyInfo as $key=>$value){
						if(!empty($value)){
							if('linio' == $publishListing->platform && 'ProductWarranty' == $key){
								$value = "<![CDATA[".$value."]]>";
							}else{
								$value = is_string($value)?LazadaApiHelper::transformFeedString($value):$value;
							}
							
							if(in_array($key, $notProDataProperties)){
								$product[$key] = $value;
							}else{
								$productData[$key] = $value;
							}
						}
					}
					
					$product['ProductData'] = $productData;// 以下是产品变参信息，先合并已经定义的信息
					
					// 初始化准备上提交产品下标，以便用于变参属性写入
					if(empty($products['create'][$publishListing->lazada_uid])){
						$initProdCrtIndex = 0;
						$products['create'][$publishListing->lazada_uid] = array();
					}else{
						$initProdCrtIndex = count($products['create'][$publishListing->lazada_uid]);
					}
					
					if(empty($products['update'][$publishListing->lazada_uid])){
						$initProdUpIndex = 0;
						$products['update'][$publishListing->lazada_uid] = array();
					}else{
						$initProdUpIndex = count($products['update'][$publishListing->lazada_uid]);
					}
					
					foreach ($variantData as $oneVariant){
						// 分开 调用create 接口和 update接口产品。如添加变参产品时，父产品只修改变参值，但是子产品需要创建。又或者是批量发布时候包含不同状态产品。
						if(!empty($existingUpSkus) && in_array($oneVariant['SellerSku'], $existingUpSkus)){
							$type = "update";
							$initProductIndex = $initProdUpIndex++;
						}else{
							$type = "create";
							$initProductIndex = $initProdCrtIndex++;
						}
						
						$products[$type][$publishListing->lazada_uid][$initProductIndex] = $product;// 如果是变参产品 复制已经定义的变参产品信息
						
						if (!empty($parentSku))
//                         if (!empty($parentSku)&&$initProductIndex != 0)
							$products[$type][$publishListing->lazada_uid][$initProductIndex]['ParentSku'] = LazadaApiHelper::transformFeedString($parentSku);
						
						foreach ($oneVariant as $key=>$value){
							$key = ucfirst($key);// 界面组织来的属性 首字母变成小写了，这里首字母要转成大写 传给proxy
							if(!empty($value)){
								if(in_array($key, $notProDataProperties)){
									$value = LazadaApiHelper::transformFeedString($value);
									
									// seller后台 full api 看到ProductGroup这个属性，但是get attr 接口已经获取不到这个属性了
									if('ProductGroup' == $key)continue;
									$products[$type][$publishListing->lazada_uid][$initProductIndex][$key] = $value;
								}else{
									$products[$type][$publishListing->lazada_uid][$initProductIndex]['ProductData'][$key] = $value;
								}
							}
						}
						
						if(!isset($publishListingId,$prodIds[$type][$publishListing->lazada_uid]) || !in_array($publishListingId,$prodIds[$type][$publishListing->lazada_uid]))
							$prodIds[$type][$publishListing->lazada_uid][] = $publishListingId;
					}
				}
				
				// 3.发送更新请求
				foreach ($products['update'] as $lazada_uid=>$sameAccountUpProds){
					if(!empty($sameAccountUpProds)){
						$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
						$config=array(
								"userId"=>$tempConfig["userId"],
								"apiKey"=>$tempConfig["apiKey"],
								"countryCode"=>$tempConfig["countryCode"],
						);
							
						// 这里更新的是产品的所有信息，不像在线修改只更新部分信息
						Yii::info("productPublish before updateFeed config:".json_encode($config)." products:".json_encode($sameAccountUpProds),"file");
						$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
						Yii::info("productPublish updateFeed response:".json_encode($response),"file");
						if($response['success'] != true){
							if(stripos($response['message'],'Internal Application Error') !== false){// dzt20160310 对平台错误 提示客户重试减少客服量
								$response['message'] = $response['message'].'<br>请重新发布商品';
							}
							self::changePublishListState($prodIds['update'][$lazada_uid],LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1],$response['message']);
						}else{
							$sendSuccess += count($sameAccountUpProds);
							$feedId = $response['response']['body']['Head']['RequestId'];
							LazadaPublishListing::updateAll(['feed_id'=>$feedId],['id'=>$prodIds['update'][$lazada_uid]]); // 记录feed id
							// 转换状态
							self::changePublishListState($prodIds['update'][$lazada_uid],LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD][0]);
							$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_CREATE);
							if($insertFeedResult){
								Yii::info("productPublish insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
							}else{
								Yii::error("productPublish insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
							}
						}
					}
				}
				
				// 4.发送创建请求
				foreach ($products['create'] as $lazada_uid=>$sameAccountCrtProds){
					if(!empty($sameAccountCrtProds)){
						$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
						$config=array(
								"userId"=>$tempConfig["userId"],
								"apiKey"=>$tempConfig["apiKey"],
								"countryCode"=>$tempConfig["countryCode"],
						);
						
						Yii::info("productPublish before createFeed puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." config:".json_encode($config)." products:".json_encode($sameAccountCrtProds),"file");
						$response = LazadaInterface_Helper::productCreate($config,array('products'=>$sameAccountCrtProds));
						Yii::info("productPublish createFeed puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." response:".json_encode($response),"file");
						if($response['success'] != true){
							if(stripos($response['message'],'Internal Application Error') !== false){// dzt20160310 对平台错误 提示客户重试减少客服量
								$response['message'] = $response['message'].'<br>请重新发布商品';
							}
							self::changePublishListState($prodIds['create'][$lazada_uid],LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1],$response['message']);
						}else{
							$sendSuccess += count($sameAccountCrtProds);
							$feedId = $response['response']['body']['Head']['RequestId'];
							LazadaPublishListing::updateAll(['feed_id'=>$feedId],['id'=>$prodIds['create'][$lazada_uid]]); // 记录feed id
							// 转换状态
							self::changePublishListState($prodIds['create'][$lazada_uid],LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD][0]);
							$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_CREATE);
							if($insertFeedResult){
								Yii::info("productPublish insertFeed ".LazadaFeedHelper::PRODUCT_CREATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
							}else{
								Yii::error("productPublish insertFeed ".LazadaFeedHelper::PRODUCT_CREATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
							}
						}
					}
				}
				
			}
			
			return array(true,"提交了 ".count($publishListingIds)." 个，成功执行 ".$sendSuccess." 个。");
		} catch (\Exception $e) {
			Yii::error("productPublish Exception".json_encode($e),"file");
			self::changePublishListState($publishListingId,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],$e->getMessage());
			return array(false , $e->getMessage());
		}
			
	}
	
	/**
	 * 产品图片上传
	 */
	public static function ImageUpload(){
		$nowTime=time();
		echo "++++++++++++ImageUpload time:$nowTime \n";
		
		//由于 feed check 和image upload 由不同的 Background job执行，status 不能共同修改，所以新增了execution_request 来让feed check 通知图片上传要触发任务执行
		$recommendJobObjs = UserBackgroundJobControll::find()
		->where('is_active = "Y" AND status <>1 AND job_name="'.LazadaApiHelper::IMAGE_UPLOAD_BGJ_NAME.'" AND error_count<5 AND execution_request>0')
		->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。
		
		if(!empty($recommendJobObjs)){
			$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
			try {
				foreach($recommendJobObjs as $recommendJobObj) {
					//1. 先判断是否可以正常抢到该记录
					$recommendJobObj->status=1;
					$affectRows = $recommendJobObj->update(false);
					if ($affectRows <= 0)	continue; //抢不到
					
					$recommendJobObj=UserBackgroundJobControll::findOne($recommendJobObj->id);
					$puid=$recommendJobObj->puid;					
					 
					// 重新去掉 fail 的 确实有fail的情况不适合自动重新上传图片
					$imageUploadTargets = LazadaPublishListing::find()->where("state='".LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED."' ")->all();
					if(!empty($imageUploadTargets)){
						echo "puid:$puid count".count($imageUploadTargets)."\n";
						foreach ($imageUploadTargets as $imageUploadTarget){
							$imageUploadTarget->status = LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED][1];
							if ($imageUploadTarget->update() == false)//抢不到---如果是多进程的话，有抢不到的情况
								continue;
								
							echo "++++++++ LazadaPublishListing id:".$imageUploadTarget->id."\n";
								
							$storeInfo = json_decode($imageUploadTarget->store_info,true);
							$imageInfo = json_decode($imageUploadTarget->image_info,true);
							$variantData = json_decode($imageUploadTarget->variant_info,true);
								
							// 如果产品进入这里 ， 则需要检查productPublish 里面检查图片的逻辑或者 查看详细状态，是否有被编辑过的痕迹。
							if(empty($imageInfo['Product_photo_primary']) && empty($imageInfo['Product_photo_others'])){
								self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][3],"Images cannot be empty.");
								continue;
							}
								
							$tempConfig = $allLazadaAccountsInfoMap[$imageUploadTarget->lazada_uid];
							$config=array(
									"userId"=>$tempConfig["userId"],
									"apiKey"=>$tempConfig["apiKey"],
									"countryCode"=>$tempConfig["countryCode"],
							);
								
							$uploads = array();
							$uploads['SellerSku'] = LazadaApiHelper::transformFeedString($variantData[0]['SellerSku']);
							$uploads['Images'] = array();
							if(!empty($imageInfo['Product_photo_primary'])){
								$uploads['Images'][] = $imageInfo['Product_photo_primary'];
							}
								
							if(!empty($imageInfo['Product_photo_others'])){
								$others = explode("@,@", $imageInfo['Product_photo_others']);
								$uploads['Images'] = array_merge($uploads['Images'],$others);
								$uploads['Images'] = array_filter($uploads['Images']);
							}
							
							Yii::info("ImageUpload before productImage uploads:".print_r($uploads,true),"file");
							$response = LazadaInterface_Helper::productImage($config,$uploads);
							Yii::info("ImageUpload after productImage response:".print_r($response["response"],true),"file");
								
							if($response['success'] != true){
								// 目前总结到 state_code 为28 的为 curl timeout 可以接受重试
								if(isset($response['state_code']) && (28 == $response['state_code'] 
										|| (isset($response['response']['state_code']) && 28 == $response['response']['state_code']))){// 不改变state 只记下error message
									self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4],$response['message']);
								}else{// 其他错误 mark state 为fail 不再重试
									if(stripos($response['message'],'Internal Application Error') !== false){// dzt20160310 对平台错误 提示客户重试减少客服量
										$response['message'] = $response['message'].'<br>请重新发布商品';
									}
									self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4],$response['message']);
									
									// 非网络原因都要减，不能重试
									if($recommendJobObj->execution_request >= 1){
										$recommendJobObj->execution_request = $recommendJobObj->execution_request - 1;
										$recommendJobObj->save(false);
									}
								}
							}else{
								// 但要尽量确保这个不会有负数，确保当有 可以执行图片上传的obj时不会因为这里而导致执行不了
								if($recommendJobObj->execution_request >= 1){
									$recommendJobObj->execution_request = $recommendJobObj->execution_request - 1;
									$recommendJobObj->save(false);
								}
								
								$feedId = $response['response']['body']['Head']['RequestId'];
								LazadaPublishListing::updateAll(['feed_id'=>$feedId],['id'=>$imageUploadTarget->id]); // 记录feed id
								// 转换状态
								self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOAD,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOAD][0]);
								Yii::info("Log productCreate response:".print_r($response['response'],true),"file");
								$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $imageUploadTarget->lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD);
								if($insertFeedResult){
									Yii::info("ImageUpload insertFeed ".LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD." success. puid:".$tempConfig['puid']." lazada_uid:".$imageUploadTarget->lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
								}else{
									Yii::error("ImageUpload insertFeed ".LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD." fail. puid:".$tempConfig['puid']." lazada_uid:".$imageUploadTarget->lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
								}
							}
						}// end of foreach $imageUploadTargets
					}// end of !empty($imageUploadTargets)
					
					$recommendJobObj->status=2;
					$recommendJobObj->error_count=0;
					$recommendJobObj->error_message="";
					$recommendJobObj->last_finish_time=$nowTime;
					$recommendJobObj->update_time=$nowTime;
					$recommendJobObj->next_execution_time=$nowTime+24*3600;//24个小时后重试
					$recommendJobObj->save(false);
				}// end of foreach $recommendJobObjs
			} catch (\Exception $e){
				Yii::error("ImageUpload Exception".print_r($e,true),"file");
				if(!empty($recommendJobObj))
				self::handleBgJobError($recommendJobObj, $e->getMessage());
				if(!empty($imageUploadTarget))
					self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][3],$e->getMessage());
			}	
		}
	}
	
	// 检查刊登产品属性是否非空
	private static function _checkPropertyIsEmpty($key , $value , $publishListing ){
		if(empty($value)){
			self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"$key cannot be empty.");
			return true;
		}
		return false;
	}
	
	// 更改刊登产品状态
	// @param $publishListingIds LazadaPublishListing id 或者 id数组
	public static function changePublishListState($publishListingIds,$state,$status,$Info=""){
		if(is_array($publishListingIds)){
			$update = array();
			$update['state'] = $state;
			$update['status'] = $status;
			$update['feed_info'] = $Info;
			$recode = LazadaPublishListing::updateAll($update,['id'=>$publishListingIds]);
			Yii::info("update $recode recode for ".implode(",", $publishListingIds),"file");
		}else{
			$publishListing = LazadaPublishListing::findOne($publishListingIds);
			$publishListing->state = $state;
			$publishListing->status = $status;
			$publishListing->feed_info = $Info;
			if(!$publishListing->save()){
				Yii::error('lazada_uid:'.lazada_uid.' publishListingId:'.$publishListing->id.'  $$publishListing->save() 保存失败:'.print_r($publishListing->errors,true),"file");
			}
		}
		
	}
	
	/**
	 * 批量修改产品库存 
	 * 
	 * @param array $products=array('productIds'=>array(111 ,222,333),'op'=>0, 'quantity'=>50 , 'condition_num'=>20); 
	 * // op 0,1分别代表 直接修改库存和按数量添加，默认直接修改 ; 3库存少于某值  增加多少 ,4库存少于某值  直接修改库存为某值
	 * // condition_num op 为3或4是 的过滤条件
	 */
	public static function batchUpdateQuantity($products){
		if(empty($products['productIds'])){
			return array(false , "请选择修改的产品");
		}
		
		if(!isset($products['quantity'])){ // 可以为0
			return array(false , "请填写修改库存的数量");
		}
		
		if(isset($products['op']) && ($products['op'] == 3 || $products['op'] == 4) && !isset($products['condition_num'])){ 
			return array(false , "请填写过滤库存的数量");
		}
		
		$requestNum = count($products['productIds']);
		$excuteNume = 0;
		$targetListings = LazadaListing::find()->where(['id'=>$products['productIds']])->asArray()->all();
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
		
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaAutoFetchListingHelper::EDITING_QUANTITY);
			if ($targetListingObj == null)// 产品已经修改中
				continue;
			
			// $operation_log 以json数组形式记录操作
			// operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
			$operation_log = json_decode($targetListingObj->operation_log);
			
			if(isset($products['op']) && ($products['op'] == 3 || $products['op'] == 4) && isset($products['condition_num'])){
				$operation_log[] = array('edit_time'=>$nowTime,'type'=>'quantity','value'=>$products['quantity'],'op'=>$products['op'],'condition_num'=>$products['condition_num']);
			}else{
				$operation_log[] = array('edit_time'=>$nowTime,'type'=>'quantity','value'=>$products['quantity'],'op'=>$products['op']);
			}
			
			$targetListingObj->operation_log = json_encode($operation_log);
			if(!$targetListingObj->save()){
				Yii::error("batchUpdateQuantity targetListingObj->save fail:".print_r($targetListingObj->errors,true),"file");
			}
			
			$update = array();
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);
			if(!isset($products['op']) || 0 == $products['op']){// 默认直接修改
				$update['Quantity'] = $products['quantity'];
			}else if(1 == $products['op']){// 按数量添加
				$update['Quantity'] = $targetListing['Quantity'] + $products['quantity'];
			}else if(3 == $products['op'] && $targetListing['Quantity'] < $products['condition_num']){// 少于某数量则添加
				$update['Quantity'] = $targetListing['Quantity'] + $products['quantity'];
			}else if(4 == $products['op'] && $targetListing['Quantity'] < $products['condition_num']){// 少于某数量则直接修改
				$update['Quantity'] = $products['quantity'];
			}
			$updateProducts[$targetListing['lazada_uid_id']][] = $update;
			$prodIds[$targetListing['lazada_uid_id']][] = $targetListing['id'];
		}
		
		foreach ($updateProducts as $lazada_uid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
			$config=array(
				"userId"=>$tempConfig["userId"],
				"apiKey"=>$tempConfig["apiKey"],
				"countryCode"=>$tempConfig["countryCode"],
			);
			
			$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
			Yii::info("batchUpdateQuantity productUpdate response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态
				LazadaListing::updateAll(['is_editing'=>0,'error_message'=>$response['message']],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
				Yii::error("batchUpdateQuantity productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				LazadaListing::updateAll(['feed_id'=>$feedId],['id'=>$prodIds[$lazada_uid]]); // 记录feed id
				$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
				if($insertFeedResult){
					Yii::info("batchUpdateQuantity insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}else{
					Yii::error("batchUpdateQuantity insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}
			}
		}
		
		return array(true,"提交了$requestNum 个，成功执行$excuteNume 个。");
	}
	public static function batchUpdateQuantityV2($products){
		if(empty($products['productIds'])){
			return array(false , "请选择修改的产品");
		}

		if(!isset($products['quantity'])){ // 可以为0
			return array(false , "请填写修改库存的数量");
		}

		if(isset($products['op']) && ($products['op'] == 3 || $products['op'] == 4) && !isset($products['condition_num'])){
			return array(false , "请填写过滤库存的数量");
		}

		$requestNum = count($products['productIds']);
		$excuteNume = 0;
		$lazadaListingManager=LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING);
		$targetListings = $lazadaListingManager->find(array('_id'=>array('$in'=>$products['productIds'])));
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();

		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecordV2($targetListing['_id'],LazadaAutoFetchListingHelper::EDITING_QUANTITY);
			if ($targetListingObj == null)// 产品已经修改中
				continue;

			// $operation_log 以json数组形式记录操作
			// operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
			$operation_log = $targetListingObj['operationLog'];

			if(isset($products['op']) && ($products['op'] == 3 || $products['op'] == 4) && isset($products['condition_num'])){
				$operation_log[] = array('edit_time'=>$nowTime,'type'=>'quantity','value'=>$products['quantity'],'op'=>$products['op'],'condition_num'=>$products['condition_num']);
			}else{
				$operation_log[] = array('edit_time'=>$nowTime,'type'=>'quantity','value'=>$products['quantity'],'op'=>$products['op']);
			}
			$lazadaListingManager->findAndModify(array("_id"=>$targetListing["_id"]),array('$set'=>array("operationLog"=>$operation_log)));
			$update = array();
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);
			if(!isset($products['op']) || 0 == $products['op']){// 默认直接修改
				$update['Quantity'] = $products['quantity'];
			}else if(1 == $products['op']){// 按数量添加
				$update['Quantity'] = $targetListing['Quantity'] + $products['quantity'];
			}else if(3 == $products['op'] && $targetListing['Quantity'] < $products['condition_num']){// 少于某数量则添加
				$update['Quantity'] = $targetListing['Quantity'] + $products['quantity'];
			}else if(4 == $products['op'] && $targetListing['Quantity'] < $products['condition_num']){// 少于某数量则直接修改
				$update['Quantity'] = $products['quantity'];
			}
			$updateProducts[$targetListing['lazada_uid_id']][] = $update;
			$prodIds[$targetListing['lazada_uid_id']][] = $targetListing['id'];
		}

		foreach ($updateProducts as $lazadaUid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazadaUid];
			$config=array(
				"userId"=>$tempConfig["userId"],
				"apiKey"=>$tempConfig["apiKey"],
				"countryCode"=>$tempConfig["countryCode"],
			);

			$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
			Yii::info("batchUpdateQuantity productUpdate response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态
				$lazadaListingManager->update(array("_id"=>array('$in'=>$prodIds[$lazadaUid])),array('$set'=>array("isEditing"=>0,"errorMsg"=>$response['message'])),array("multiple"=>true));
				Yii::error("batchUpdateQuantity productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				$lazadaListingManager->update(array("_id"=>array('$in'=>$prodIds[$lazadaUid])),array('$set'=>array("feedId"=>$feedId)),array("multiple"=>true));
				$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazadaUid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
				if($insertFeedResult){
					Yii::info("batchUpdateQuantity insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}else{
					Yii::error("batchUpdateQuantity insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}
			}
		}

		return array(true,"提交了$requestNum 个，成功执行$excuteNume 个。");
	}

	/**
	 * 批量修改产品产品价格
	 * @param array $products=array('productIds'=>array(111 ,222,333), 'op'=>0,price'=>50 ); // op 0,1,2 分别代表 直接修改价格，按金额添加和按百分比添加
	 */
	public static function batchUpdatePrice($products){
		if(empty($products['productIds'])){
			return array(false , "请选择修改的产品");
		}
		
		if(!isset($products['price'])){ // 可以为0
			return array(false , "请填写修改价格");
		}
		
		$requestNum = count($products['productIds']);
		$excuteNume = 0;
		$targetListings = LazadaListing::find()->where(['id'=>$products['productIds']])->asArray()->all();
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
		
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaAutoFetchListingHelper::EDITING_PRICE);
			if ($targetListingObj == null)// 产品已经修改中
				continue;
			
			// $operation_log 以json数组形式记录操作
			// operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
			$operation_log = json_decode($targetListingObj->operation_log);
				
			$operation_log[] = array('edit_time'=>$nowTime,'type'=>'price','value'=>$products['price'],'op'=>$products['op']);
			$targetListingObj->operation_log = json_encode($operation_log);
			if(!$targetListingObj->save()){
				Yii::error("batchUpdatePrice targetListingObj->save fail:".print_r($targetListingObj->errors,true),"file");
			}
				
			$update = array();
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);
			if(isset($products['op']) && 1 == $products['op']){// 按金额添加
				$update['Price'] = $targetListing['Price'] + $products['price'];
			}else if(isset($products['op']) && 2 == $products['op']){// 按百分比添加
				$update['Price'] = $targetListing['Price'] + round(($products['price'] / 100 * $targetListing['Price']),2);
			}else{// 默认直接修改
				$update['Price'] = $products['price'];
			}
			
			$updateProducts[$targetListing['lazada_uid_id']][] = $update;
			$prodIds[$targetListing['lazada_uid_id']][] = $targetListing['id'];
		}
		
		foreach ($updateProducts as $lazada_uid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
				
			$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
			Yii::info("batchUpdatePrice productUpdate response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态
				LazadaListing::updateAll(['is_editing'=>0,'error_message'=>$response['message']],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
				Yii::error("batchUpdatePrice productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				LazadaListing::updateAll(['feed_id'=>$feedId],['id'=>$prodIds[$lazada_uid]]); // 记录feed id
				$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
				if($insertFeedResult){
					Yii::info("batchUpdatePrice insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}else{
					Yii::error("batchUpdatePrice insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}
			}
		}
		
		return array(true,"提交了$requestNum 个，成功执行$excuteNume 个。");
	}
	public static function batchUpdatePriceV2($products){
		if(empty($products['productIds'])){
			return array(false , "请选择修改的产品");
		}

		if(!isset($products['price'])){ // 可以为0
			return array(false , "请填写修改价格");
		}

		$requestNum = count($products['productIds']);
		$excuteNume = 0;
		$lazadaListingManager=LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING);
		$objIds=array();
		foreach ($products['productIds'] as $objIdStr){
			$objIds[]=new \MongoId($objIdStr);
		}

		$targetListings = $lazadaListingManager->find(array('_id'=>array('$in'=>$objIds)));
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecordV2($targetListing['_id'],LazadaAutoFetchListingHelper::EDITING_PRICE);
			if ($targetListingObj == null)// 产品已经修改中
				continue;

			// $operation_log 以json数组形式记录操作
			// operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
			$operation_log = $targetListingObj['operationLog'];

			$operation_log[] = array('edit_time'=>$nowTime,'type'=>'price','value'=>$products['price'],'op'=>$products['op']);
			$targetListingObj['operationLog'] = $operation_log;
			$lazadaListingManager->findAndModify(array("_id"=>$targetListing["_id"]),array('$set'=>array("operationLog"=>$operation_log)));

			$update = array();
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['sellerSku']);
			if(isset($products['op']) && 1 == $products['op']){// 按金额添加
				$update['Price'] = $targetListing['product']['Price'] + $products['price'];
			}else if(isset($products['op']) && 2 == $products['op']){// 按百分比添加
				$update['Price'] = $targetListing['product']['Price'] + round(($products['price'] / 100 * $targetListing['Price']),2);
			}else{// 默认直接修改
				$update['Price'] = $products['price'];
			}

			$updateProducts[$targetListing['lazadaUid']][] = $update;
			$prodIds[$targetListing['lazadaUid']][] = $targetListing['_id'];
		}

		foreach ($updateProducts as $lazadaUid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazadaUid];
			$config=array(
				"userId"=>$tempConfig["userId"],
				"apiKey"=>$tempConfig["apiKey"],
				"countryCode"=>$tempConfig["countryCode"],
			);

			$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
			Yii::info("batchUpdatePrice productUpdate response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态
				$lazadaListingManager->update(array("_id"=>array('$in'=>$prodIds[$lazadaUid])),array('$set'=>array("isEditing"=>0,"errorMsg"=>$response['message'])),array("multiple"=>true));
				Yii::error("batchUpdatePrice productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				$lazadaListingManager->update(array("_id"=>array('$in'=>$prodIds[$lazadaUid])),array('$set'=>array("feedId"=>$feedId)),array("multiple"=>true));
				$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazadaUid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
				if($insertFeedResult){
					Yii::info("batchUpdatePrice insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}else{
					Yii::error("batchUpdatePrice insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}
			}
		}

		return array(true,"提交了$requestNum 个，成功执行$excuteNume 个。");
	}

	/**
	 * 批量修改产品促销信息
	 * @param array $products=array('productIds'=>array(111 ,222,333),'op'=>0,
	 * 'salePrice'=>198,'saleStartDate'=>'2015-11-11','saleEndDate'=>'2015-12-12');// op 0,1,2 分别代表 直接修改促销价，按金额添加和按百分比添加
	 */
	public static function batchUpdateSaleInfo($products){
		if(empty($products['productIds'])){
			return array(false , "请选择修改的产品");
		}
	
		if(!isset($products['salePrice'])){ // 可以为0
			return array(false , "请填写促销价格");
		}
		
		if(empty($products['saleStartDate'])){
			return array(false , "请选择促销开始时间");
		}
		
		if(empty($products['saleEndDate'])){
			return array(false , "请选择促销结束时间");
		}
		
		if(strtotime($products['saleStartDate']) > strtotime($products['saleEndDate'])){
			return array(false , "促销结束时间必须晚于开始时间");
		}
		
		$requestNum = count($products['productIds']);
		$excuteNume = 0;
		$targetListings = LazadaListing::find()->where(['id'=>$products['productIds']])->asArray()->all();
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
	
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaAutoFetchListingHelper::EDITING_SALESINFO);
			if ($targetListingObj == null)// 产品已经修改中
				continue;
			
			// $operation_log 以json数组形式记录操作
			// operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
			$operation_log = json_decode($targetListingObj->operation_log);
	
			$operation_log[] = array('edit_time'=>$nowTime,'type'=>'sale','value'=>$products['saleStartDate'].','.$products['saleEndDate'].','.$products['salePrice'],'op'=>$products['op']);
			$targetListingObj->operation_log = json_encode($operation_log);
			
			if(!$targetListingObj->save()){
				Yii::error("batchUpdateSaleInfo targetListingObj->save fail:".print_r($targetListingObj->errors,true),"file");
			}
	
			// 填写修改信息
			$update = array();
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);
			
			if(isset($products['op']) && 1 == $products['op']){// 按金额添加
				$update['SalePrice'] = $targetListing['SalePrice'] + $products['salePrice'];
			}else if(isset($products['op']) && 2 == $products['op']){// 按百分比添加
				$update['SalePrice'] = $targetListing['SalePrice'] + round(($products['salePrice'] / 100 * $targetListing['Price']),2);
			}else{// 默认直接修改
				$update['SalePrice'] = $products['salePrice'];
			}
			
			// 检查价格和促销价格大小
			if($update['SalePrice'] > $targetListingObj->Price){
				$targetListingObj->is_editing = 0;
				$targetListingObj->error_message = "Sale price must be lower than the standard price. Sale Price must be blank if standard price is blank";
				if(!$targetListingObj->save()){
					Yii::error("batchUpdateSaleInfo targetListingObj->save fail:".print_r($targetListingObj->errors,true),"file");
				}
				continue;
			}
			
			// @todo 检查促销时间
			$update['SaleStartDate'] = $products['saleStartDate'];
			$update['SaleEndDate'] = $products['saleEndDate'];
			$update['Price'] = $targetListing['Price'];// linio product update 促销信息要带价格 ， 但所有平台都提交price应该不影响
				
			$updateProducts[$targetListing['lazada_uid_id']][] = $update;
			$prodIds[$targetListing['lazada_uid_id']][] = $targetListing['id'];
		}
	
		foreach ($updateProducts as $lazada_uid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
	
			$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
			Yii::info("batchUpdateSaleInfo productUpdate response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态 可重新编辑
				LazadaListing::updateAll(['is_editing'=>0,'error_message'=>$response['message']],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
				Yii::error("batchUpdateSaleInfo productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				LazadaListing::updateAll(['feed_id'=>$feedId],['id'=>$prodIds[$lazada_uid]]); // 记录feed id
				$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
				if($insertFeedResult){
					Yii::info("batchUpdateSaleInfo insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}else{
					Yii::error("batchUpdateSaleInfo insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}
			}
		}
		
		return array(true,"提交了$requestNum 个，成功执行$excuteNume 个。");
	}
	public static function batchUpdateSaleInfoV2($products){
		if(empty($products['productIds'])){
			return array(false , "请选择修改的产品");
		}

		if(!isset($products['salePrice'])){ // 可以为0
			return array(false , "请填写促销价格");
		}

		if(empty($products['saleStartDate'])){
			return array(false , "请选择促销开始时间");
		}

		if(empty($products['saleEndDate'])){
			return array(false , "请选择促销结束时间");
		}

		if(strtotime($products['saleStartDate']) > strtotime($products['saleEndDate'])){
			return array(false , "促销结束时间必须晚于开始时间");
		}

		$requestNum = count($products['productIds']);
		$excuteNume = 0;
		$lazadaListingManager=LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING);
		$targetListings=$lazadaListingManager->find(array("_id"=>array('$in'=>$products)));
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();

		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecordV2($targetListing['_id'],LazadaAutoFetchListingHelper::EDITING_SALESINFO);
			if ($targetListingObj == null)// 产品已经修改中
				continue;

			// $operation_log 以json数组形式记录操作
			// operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录

			$operation_log = $targetListingObj["operationLog"];
			$operation_log[] = array('edit_time'=>$nowTime,'type'=>'sale','value'=>$products['saleStartDate'].','.$products['saleEndDate'].','.$products['salePrice'],'op'=>$products['op']);
			$lazadaListingManager->findAndModify(array("_id"=>$targetListing["_id"]),array('$set'=>array("operationLog"=>$operation_log)));


			// 填写修改信息
			$update = array();
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);

			if(isset($products['op']) && 1 == $products['op']){// 按金额添加
				$update['SalePrice'] = $targetListing['SalePrice'] + $products['salePrice'];
			}else if(isset($products['op']) && 2 == $products['op']){// 按百分比添加
				$update['SalePrice'] = $targetListing['SalePrice'] + round(($products['salePrice'] / 100 * $targetListing['Price']),2);
			}else{// 默认直接修改
				$update['SalePrice'] = $products['salePrice'];
			}

			// 检查价格和促销价格大小
			if($update['SalePrice'] > $targetListingObj->Price){
				$lazadaListingManager->findAndModify(array("_id"=>$targetListing["_id"]),array('$set'=>
					array("isEditing"=>0,"errorMsg"=>"Sale price must be lower than the standard price. Sale Price must be blank if standard price is blank")));
				continue;
			}

			// @todo 检查促销时间
			$update['SaleStartDate'] = $products['saleStartDate'];
			$update['SaleEndDate'] = $products['saleEndDate'];
			$update['Price'] = $targetListing['Price'];// linio product update 促销信息要带价格 ， 但所有平台都提交price应该不影响

			$updateProducts[$targetListing['lazada_uid_id']][] = $update;
			$prodIds[$targetListing['lazada_uid_id']][] = $targetListing['id'];
		}

		foreach ($updateProducts as $lazadaUid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazadaUid];
			$config=array(
				"userId"=>$tempConfig["userId"],
				"apiKey"=>$tempConfig["apiKey"],
				"countryCode"=>$tempConfig["countryCode"],
			);

			$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
			Yii::info("batchUpdateSaleInfo productUpdate response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态 可重新编辑
				$lazadaListingManager->update(array("_id"=>array('$in'=>$prodIds[$lazadaUid])),array('$set'=>array("isEditing"=>0,"errorMsg"=>$response['message'])),array("multiple"=>true));
				Yii::error("batchUpdateSaleInfo productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				$lazadaListingManager->update(array("_id"=>array('$in'=>$prodIds[$lazadaUid])),array('$set'=>array("feedId"=>$feedId)),array("multiple"=>true));
				$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazadaUid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
				if($insertFeedResult){
					Yii::info("batchUpdateSaleInfo insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}else{
					Yii::error("batchUpdateSaleInfo insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}
			}
		}

		return array(true,"提交了$requestNum 个，成功执行$excuteNume 个。");
	}

	/**
	 * 批量上架
	 * @param array $products=array(111 ,222,333);
	 */
	public static function batchPutOnLine($products){
		if(empty($products)){
			return array(false , "请选择修改的产品");
		}
		
		$requestNum = count($products);
		$excuteNume = 0;
		$targetListings = LazadaListing::find()->where(['id'=>$products])->asArray()->all();
		
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
		
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaAutoFetchListingHelper::PUTING_ON);
			if ($targetListingObj == null)// 产品已经修改中
				continue;
		
			// $operation_log 以json数组形式记录操作
			// operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
			$operation_log = json_decode($targetListingObj->operation_log);
		
			$operation_log[] = array('edit_time'=>$nowTime,'type'=>'put','value'=>'on');
			$targetListingObj->operation_log = json_encode($operation_log);
				
			if(!$targetListingObj->save()){
				Yii::error("batchPutOnLine targetListingObj->save fail:".print_r($targetListingObj->errors,true),"file");
			}
		
			// 填写修改信息
			$update = array();
			$update['Status'] = "active";
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);
			$updateProducts[$targetListing['lazada_uid_id']][] = $update;
			$prodIds[$targetListing['lazada_uid_id']][] = $targetListing['id'];
		}
		
		foreach ($updateProducts as $lazada_uid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
		
			$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
			Yii::info("batchPutOnLine productUpdate response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态 可重新编辑
				LazadaListing::updateAll(['is_editing'=>0,'error_message'=>$response['message']],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
				Yii::error("batchPutOnLine productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				LazadaListing::updateAll(['feed_id'=>$feedId],['id'=>$prodIds[$lazada_uid]]); // 记录feed id
				$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
				if($insertFeedResult){
					Yii::info("batchPutOnLine insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}else{
					Yii::error("batchPutOnLine insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}
			}
		}
		
		return array(true,"提交了$requestNum 个，成功执行$excuteNume 个。");
	}
	
	/**
	 * 批量下架
	 * @param array $products=array(111 ,222,333);
	 */
	public static function batchPutOffLine($products){
		if(empty($products)){
			return array(false , "请选择修改的产品");
		}
		
		$requestNum = count($products);
		$excuteNume = 0;
		$targetListings = LazadaListing::find()->where(['id'=>$products])->asArray()->all();
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
		
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaAutoFetchListingHelper::PUTING_OFF);
			if ($targetListingObj == null)// 产品已经修改中
				continue;
			
			// $operation_log 以json数组形式记录操作
			// operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
			$operation_log = json_decode($targetListingObj->operation_log);
		
			$operation_log[] = array('edit_time'=>$nowTime,'type'=>'put','value'=>'off');
			$targetListingObj->operation_log = json_encode($operation_log);
				
			if(!$targetListingObj->save()){
				Yii::error("batchPutOffLine targetListingObj->save fail:".print_r($targetListingObj->getErrors(),true),"file");
			}
		
			// 填写修改信息
			$update = array();
			$update['Status'] = "inactive";
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);
			$updateProducts[$targetListing['lazada_uid_id']][] = $update;
			$prodIds[$targetListing['lazada_uid_id']][] = $targetListing['id'];
		}
		
		foreach ($updateProducts as $lazada_uid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
		
			$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
			Yii::info("batchPutOffLine productUpdate response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态 可重新编辑
				LazadaListing::updateAll(['is_editing'=>0,'error_message'=>$response['message']],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
				Yii::error("batchPutOffLine productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				LazadaListing::updateAll(['feed_id'=>$feedId],['id'=>$prodIds[$lazada_uid]]); // 记录feed id
				$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
				if($insertFeedResult){
					Yii::info("batchPutOffLine insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}else{
					Yii::error("batchPutOffLine insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}
			}
		}
		
		return array(true,"提交了$requestNum 个，成功执行$excuteNume 个。");
	}
	public static function batchPutOffLineV2($products){
		if(empty($products)){
			return array(false , "请选择修改的产品");
		}

		$requestNum = count($products);
		$excuteNume = 0;
		$lazadaListingManager=LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING);
		$targetListings=$lazadaListingManager->find(array("_id"=>array('$in'=>$products)));
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();

		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecordV2($targetListing['id'],LazadaAutoFetchListingHelper::PUTING_OFF);
			if ($targetListingObj == null)// 产品已经修改中
				continue;

			// $operation_log 以json数组形式记录操作
			// operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
			$operation_log = $targetListingObj["operationLog"];

			$operation_log[] = array('edit_time'=>$nowTime,'type'=>'put','value'=>'off');
			$lazadaListingManager->findAndModify(array("_id"=>$targetListing["_id"]),array('$set'=>array("operationLog"=>$operation_log)));

			// 填写修改信息
			$update = array();
			$update['Status'] = "inactive";
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['sellerSku']);
			$updateProducts[$targetListing['lazadaUid']][] = $update;
			$prodIds[$targetListing['lazadaUid']][] = $targetListing['_id'];
		}

		foreach ($updateProducts as $lazadaUid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazadaUid];
			$config=array(
				"userId"=>$tempConfig["userId"],
				"apiKey"=>$tempConfig["apiKey"],
				"countryCode"=>$tempConfig["countryCode"],
			);

			$response = LazadaInterface_Helper::productUpdate($config,array('products'=>$sameAccountUpProds));
			Yii::info("batchPutOffLine productUpdate response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态 可重新编辑
				$lazadaListingManager->update(array("_id"=>array('$in'=>$prodIds[$lazadaUid])),array('$set'=>array("isEditing"=>0,"errorMsg"=>$response['message'])),array("multiple"=>true));
				Yii::error("batchPutOffLine productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				$lazadaListingManager->update(array("_id"=>array('$in'=>$prodIds[$lazadaUid])),array('$set'=>array("feedId"=>$feedId)),array("multiple"=>true));
				$insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazadaUid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
				if($insertFeedResult){
					Yii::info("batchPutOffLine insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}else{
					Yii::error("batchPutOffLine insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazadaUid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
				}
			}
		}
		return array(true,"提交了$requestNum 个，成功执行$excuteNume 个。");
	}

	// 复制自TrackingRecommendProductHelper ，next_execution_time 目前不判断，但保留设置
	private static function handleBgJobError($recommendJobObj,$errorMessage){
		$nowTime=time();
		$recommendJobObj->status=3;
		$recommendJobObj->error_count=$recommendJobObj->error_count+1;
		$recommendJobObj->error_message=$errorMessage;
		$recommendJobObj->last_finish_time=$nowTime;
		$recommendJobObj->update_time=$nowTime;
		$recommendJobObj->next_execution_time=$nowTime+30*60;//半个小时后重试
		$recommendJobObj->save(false);
		return true;
	}
	
	// 确认产品已经上传，此操作之后，再发布产品会覆盖已存在的产品数据
	// 目前操作直接将所有变参设置成已发布，后面可以优化只设置部分变参
	public static function confirmUploadedProduct($publishListing){
	    $toUpSkus = array();
	    $variantInfo = json_decode($publishListing->variant_info, true);
	    foreach ($variantInfo as $variant){
	        $toUpSkus[] = $variant['SellerSku'];
	    }
	    
	    // 从在线表查看sku是否都已经发布
	    if($publishListing->platform == "linio" || $publishListing->platform == "jumia"){
	        $count = LazadaListing::find()->where(['SellerSku'=>$toUpSkus,'platform'=>$publishListing->platform,'site'=>$publishListing->site])->count();
	        if(empty($count)){
	            return array(false,"操作失败：系统检测产品未发布");
	        }
	    }
	    
        $publishListing->uploaded_product = json_encode($toUpSkus, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        
        if(!$publishListing->save(false)){
            return array(false,"保存操作失败。");
        }else{
            return array(true,"操作成功。");
        }
	}
	
	
	
	
}

?>