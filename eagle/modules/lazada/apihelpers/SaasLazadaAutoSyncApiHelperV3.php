<?php

namespace eagle\modules\lazada\apihelpers;
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
use eagle\modules\listing\models\LazadaListingV2;
use eagle\modules\util\models\UserBackgroundJobControll;
use common\helpers\Helper_Array;
use eagle\modules\listing\helpers\LazadaProductStatus;
use eagle\modules\listing\helpers\LazadaCallbackHelper;

/**
 +-----------------------------------------------------------------------------------------------
 * Lazada 数据同步类 主要执行非订单类数据同步 
 * lazada 改接口后为不影响linio,需要从SaasLazadaAutoSyncApiHelper调整的接口
 * dzt 2017-01-12 
 +-----------------------------------------------------------------------------------------------
 */

class SaasLazadaAutoSyncApiHelperV3 {
	
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
		$command = $connection->createCommand("update lazada_listing_v2 set lb_status=$type,update_time=$nowTime where id=$listingId and (lb_status in (0,".LazadaProductStatus::EDITING_FAIL.",".LazadaProductStatus::EDITING_SUCCESS.") ) ") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
		// 抢到记录
		$listing =	LazadaListingV2::findOne($listingId);
		return $listing;
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
				foreach ($publishListingIds as $publishListingId){
				    $puid = Yii::$app->subdb->getCurrentPuid();
					// 也许是网络问题，所以这里失败也允许 进入发布
					$connection=Yii::$app->subdb;
					$command = $connection->createCommand("update lazada_publish_listing set status='".LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1]."' where id=". $publishListingId." and ( state='".LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT."' or state='".LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL."' ) ") ;
					$record = $command->execute();
// 					if ($record <= 0)//抢不到---如果是多进程的话，有抢不到的情况
// 						continue; 
					
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
					if(self::_checkPropertyIsEmpty('PrimaryCategory',$storeInfo,'primaryCategory',$publishListing)) continue;
					if(self::_checkPropertyIsEmpty('brand',$baseInfo,'brand',$publishListing)) continue;
					if(self::_checkPropertyIsEmpty('name',$baseInfo,'name',$publishListing)) continue;
					// 接口上看 其他两个平台都必填，lazada不是必填，但lazada卖家后台显示必填，想审核通过也是必填
					if(self::_checkPropertyIsEmpty('description',$descriptionInfo,'description',$publishListing)) continue;
					
				
					// sg my ShortDescription貌似必填
					if($publishListing->site == "my"){// my才强制
						// dzt20160520 前端去掉 让客户填写 'name_ms','description_ms'，直接再这里从 'name','description' 复制
						$baseInfo['name_ms'] = $baseInfo['name'];
						$descriptionInfo['description_ms'] = $descriptionInfo['description'];
					}
					
					$hasVariantProblem = false;
					$existingUpSkus = json_decode($publishListing->uploaded_product,true); // 已上传到Lazada后台的产品 sku
					
					if(empty($variantData)){
					    $hasVariantProblem = true;
					    self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"变参信息不存在，请选择【变体信息】里面的变参参数，再填写变参信息");
					}
					
					foreach ($variantData as $oneVariant){
						// dzt20151216 在jumia 后台操作保存过，可以不填，之前的两个平台也可以不填，但是getAttr接口看到这个字段是必填。
// 						$hasVariantProblem = self::_checkPropertyIsEmpty('Variation',$oneVariant,'Variation',$publishListing);
// 						if($hasVariantProblem) break;
						
						// 接口上看 其他两个平台都不是必填，linio是必填，但这里我们set成必填
						$hasVariantProblem = self::_checkPropertyIsEmpty('SellerSku',$oneVariant,'SellerSku',$publishListing);
						if($hasVariantProblem) break;
						
						// dzt20151113 调试productCreate Price又不是必填的了。 但是我们为了后面不再查错，所以这里强制要求填
						$hasVariantProblem = self::_checkPropertyIsEmpty('price',$oneVariant,'price',$publishListing);
						if($hasVariantProblem) break;
						
						// 接口上看 其他所有平台都不是必填，但这里我们set成必填
						$hasVariantProblem = self::_checkPropertyIsEmpty('quantity',$oneVariant,'quantity',$publishListing);
						if($hasVariantProblem) break;
					
						// 检查 Sale price must be lower than the standard price. Sale Price must be blank if standard price is blank
						
						if(isset($oneVariant['special_price']) && $oneVariant['special_price'] > $oneVariant['price'] ){
							self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Special Price must be lower than the standard price.");
							$hasVariantProblem = true;
							if($hasVariantProblem) break; 
						}

						// 有SalePrice SaleStartDate SaleEndDate 三个必须同时存在
						if(!empty($oneVariant['special_price']) || !empty($oneVariant['special_from_date']) || !empty($oneVariant['special_to_date'])){
							if(empty($oneVariant['special_price'])){
								self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"Special Price is empty.Please complete the sales information.");
								$hasVariantProblem = true;
								if($hasVariantProblem) break;
							}
								
							if(empty($oneVariant['special_from_date'])){
								self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"Start date of promotion is empty.Please complete the sales information.");
								$hasVariantProblem = true;
								if($hasVariantProblem) break;
							}	
							
							if(empty($oneVariant['special_to_date'])){
								$hasVariantProblem = self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"End date of promotion is empty.Please complete the sales information.");
								$hasVariantProblem = true;
								if($hasVariantProblem) break;
							}	
						}
						
						if(self::_checkPropertyIsEmpty('package_content',$oneVariant,'package_content',$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('package_length',$oneVariant,'package_length',$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('package_width',$oneVariant,'package_width',$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('package_height',$oneVariant,'package_height',$publishListing)) continue;
						if(self::_checkPropertyIsEmpty('package_weight',$oneVariant,'package_weight',$publishListing)) continue;
						
						if(self::_checkPropertyIsEmpty('tax_class',$oneVariant,'tax_class',$publishListing)) continue;
						
						if(empty($imageInfo['product_photos']) || empty($imageInfo['product_photos'][$oneVariant['skuSelData']])){
						    self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"SellerSku:".$oneVariant['SellerSku'].",Images cannot be empty.");
						    continue;
						}
						
						if(!empty($oneVariant['std_search_keywords'])){
						    $keyWords = explode(',', $oneVariant['std_search_keywords']);
						    Helper_Array::removeEmpty($keyWords);
						    if(count($keyWords) > 6){
						        $hasVariantProblem = self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"Search Keywords 不能超过6个。");
						        $hasVariantProblem = true;
						    }
						}
						
						if($hasVariantProblem) break;
					}
					
					if($hasVariantProblem) continue;
					
					// 2.组合产品数据内容
					$product = array();
					$attributes = array();
					$skus = array();
					// 查阅了几个马来西亚的主目录 full call api 发现就下面属性under product tag 差异主要在Attributes 和Skus tag里的内容
// 					<PrimaryCategory></PrimaryCategory>
// 					<SPUId></SPUId>
// 					<AssociatedSku></AssociatedSku>

// 					<Attributes></Attributes>
// 					<Skus></Skus>

					$notProDataProperties = array('PrimaryCategory','SPUId','AssociatedSku');
					
					$product['PrimaryCategory'] = $storeInfo['primaryCategory'];
					
// 					$product['AssociatedSku'] = "Sneakers_EU:41";// dzttest20170117 AssociatedSku
// 					$product['SPUId'] = 111;// dzttest20170117 AssociatedSku
					
					
					// dzt20161018 发现lazada api接口获取到的选项包含'&' 字符也是报错“Invalid Request Format” 所以统一 htmlentities处理
					foreach ($baseInfo as $key=>$value){
						if(!empty($value)){
						    // 考虑要不要重新获取PrimaryCategory的属性 对比填哪些是富文本
						    if('product_warranty' == $key || 'product_warranty_en' == $key){
								$value = "<![CDATA[".$value."]]>";
							}else{
								$value = is_string($value)?LazadaApiHelper::transformFeedString($value):$value;
							}
							        
							if(in_array($key, $notProDataProperties)){
								$product[$key] = $value;
							}else{
								$attributes[$key] = $value;
							}
						}
					}
					
					foreach ($descriptionInfo as $key=>$value){
						if(!empty($value)){
							if(in_array($key, $notProDataProperties)){
								$product[$key] = "<![CDATA[".$value."]]>";
							}else{
								$attributes[$key] = "<![CDATA[".$value."]]>";
							}
						}
					}
					
// 					foreach ($shippingInfo as $key=>$value){
// 						if(!empty($value)){
// 							$value = is_string($value)?LazadaApiHelper::transformFeedString($value):$value;
// 							if(in_array($key, $notProDataProperties)){
// 								$product[$key] = $value;
// 							}else{
// 								$productData[$key] = $value;
// 							}
// 						}
// 					}
					
// 					foreach ($warrantyInfo as $key=>$value){
// 						if(!empty($value)){
// 							if('linio' == $publishListing->platform && 'ProductWarranty' == $key){
// 								$value = "<![CDATA[".$value."]]>";
// 							}else{
// 								$value = is_string($value)?LazadaApiHelper::transformFeedString($value):$value;
// 							}
							
// 							if(in_array($key, $notProDataProperties)){
// 								$product[$key] = $value;
// 							}else{
// 								$productData[$key] = $value;
// 							}
// 						}
// 					}
					
					$product['Attributes'] = $attributes;// 以下是产品变参信息，先合并已经定义的信息
					foreach ($variantData as $oneVariant){
					    $sku = array();
					    $images = array();
						foreach ($oneVariant as $key=>$value){
						    if('skuSelData' == $key)continue;// 这个字段是没有的
						    
						    // dzt20170925 处理变参std_search_keywords 字段
						    if('std_search_keywords' == $key && !empty($value)){
						        $keyWords = explode(',', $value);
						        Helper_Array::removeEmpty($keyWords);
						        if(!empty($keyWords))
			                        $value = json_encode($keyWords);
						        else 
						            continue;
						            
						    }
						    
							if(!empty($value)){
							    if('seller_promotion' == $key || 'package_content' == $key){
							        $value = "<![CDATA[".$value."]]>";
							    }else{
							        $value = is_string($value)?LazadaApiHelper::transformFeedString($value):$value;
							    }
							    $sku[$key] = $value;
							}
						}
						
						// 图片重新通过异步上传
// 						foreach ($imageInfo['product_photos'][$oneVariant['skuSelData']] as $imageSrc)
// 						    $images[] = $imageSrc;
						
// 						$sku['Images'] = $images;
						$skus[] = $sku;
					}
					$product['Skus'] = $skus;
					
					// 分开 调用create 接口和 update接口产品。
					// 新接口sku 产品信息包含在Skus tag里面，不管新增还是修改，所以$existingUpSkus为空则跑create 接口
					if(!empty($existingUpSkus)){
    			        $type = "update";
    				}else{
    					$type = "create";
    				}
					
					// 新接口sku 产品信息包含在Skus tag里面，不用再另外复制产品信息for 变参sku创建
					// 新接口只接受一次创建一个产品
					$products[$type][$publishListing->lazada_uid][$publishListingId] = $product;
				}
				
				// 3.发送更新请求
				foreach ($products['update'] as $lazada_uid=>$sameAccountUpProds){
				    foreach($sameAccountUpProds as $publishListingId=>$sameAccountUpProd){
						$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
						$config=array(
								"userId"=>$tempConfig["userId"],
								"apiKey"=>$tempConfig["apiKey"],
								"countryCode"=>$tempConfig["countryCode"],
						);
							
						// 这里更新的是产品的所有信息，不像在线修改只更新部分信息
						Yii::info("productPublish before productUpdate config:".json_encode($config).",id:$publishListingId ,productInfo:".json_encode($sameAccountUpProd),"file");
						$response = LazadaInterface_Helper::productUpdate($config,array('product'=>$sameAccountUpProd));
						Yii::info("productPublish productUpdate response:".json_encode($response),"file");
						if($response['success'] != true){
						    if(!empty($response['response']['errors'])){
						        $reqParams = array();
						        $reqParams['puid'] = $puid;
						        $reqParams['publishListingId'] = $publishListingId;
						        $reqParams['errors'] = $response['response']['errors'];
						        $ret = LazadaCallbackHelper::productCreateV3($reqParams);
						        if($ret == false)
						            self::changePublishListState($publishListingId,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1],$response['message']);
						    }else{
						        if(stripos($response['message'],'Internal Application Error') !== false){// dzt20160310 对平台错误 提示客户重试减少客服量
						            $response['message'] = $response['message'].'<br>请重新发布商品';
						        }
						        self::changePublishListState($publishListingId,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1],$response['message']);
						    }
						}else{
							$sendSuccess ++;
							$reqParams = array();
							$reqParams['puid'] = $puid;
							$reqParams['publishListingId'] = $publishListingId;
							$ret = LazadaCallbackHelper::productCreateV3($reqParams);
							if($ret == false)
							    self::changePublishListState($publishListingId,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD][3],"图片上传终止了，请联系客服。");
														
						}
					}
				}
				
				// 4.发送创建请求
				foreach ($products['create'] as $lazada_uid=>$sameAccountCrtProds){
					foreach($sameAccountCrtProds as $publishListingId=>$sameAccountCrtProd){
						$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
						$config=array(
								"userId"=>$tempConfig["userId"],
								"apiKey"=>$tempConfig["apiKey"],
								"countryCode"=>$tempConfig["countryCode"],
						);
						
						Yii::info("productPublish before productCreate config:".json_encode($config).",id:$publishListingId ,productInfo:".json_encode($sameAccountCrtProd),"file");
						$response = LazadaInterface_Helper::productCreate($config,array('product'=>$sameAccountCrtProd));
						Yii::info("productPublish productCreate response:".json_encode($response),"file");
						if($response['success'] != true){
						    if(!empty($response['response']['errors'])){
						        $reqParams = array();
						        $reqParams['puid'] = $puid;
						        $reqParams['publishListingId'] = $publishListingId;
						        $reqParams['errors'] = $response['response']['errors'];
						        $ret = LazadaCallbackHelper::productCreateV3($reqParams);
						        if($ret == false)
						            self::changePublishListState($publishListingId,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1],$response['message']);
						    }else{
						        if(stripos($response['message'],'Internal Application Error') !== false){// dzt20160310 对平台错误 提示客户重试减少客服量
						            $response['message'] = $response['message'].'<br>请重新发布商品';
						        }
						        self::changePublishListState($publishListingId,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1],$response['message']);
						    }
						}else{
							$sendSuccess ++;
							$reqParams = array();
							$reqParams['puid'] = $puid;
							$reqParams['publishListingId'] = $publishListingId;
							$ret = LazadaCallbackHelper::productCreateV3($reqParams);
							if($ret == false)
							    self::changePublishListState($publishListingId,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD][3],"图片上传终止了，请联系客服。");
							
						}
					}
				}
				
			}
			
			return array(true,"提交了 ".count($publishListingIds)." 个，成功执行 ".$sendSuccess." 个。");
		} catch (\Exception $e) {
			Yii::error("productPublish Exception".print_r($e,true),"file");
			self::changePublishListState($publishListingId,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],$e->getMessage());
			return array(false , $e->getMessage());
		}
			
	}
	
	/**
	 * 产品图片上传
	 */
	public static function ImageUpload($platforms){
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
					$imageUploadTargets = LazadaPublishListing::find()
					->where("state='".LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED."' ")
					->andWhere(['platform'=>$platforms])->all();
					
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
								
							Helper_Array::removeEmpty($storeInfo,true);
							Helper_Array::removeEmpty($imageInfo,true);
							Helper_Array::removeEmpty($variantData,true);
							
							$tempConfig = $allLazadaAccountsInfoMap[$imageUploadTarget->lazada_uid];
							$config=array(
							        "userId"=>$tempConfig["userId"],
							        "apiKey"=>$tempConfig["apiKey"],
							        "countryCode"=>$tempConfig["countryCode"],
							);
							
							$product = array();
							$skus = array();
							$canSetImage = true;
							$checkQCSkus = array();
							foreach ($variantData as $oneVariant){
							    // 上传图片到ladaza出问题，跳过这个产品的所有sku 图片上传
							    if(!$canSetImage) continue;
							    
							    $checkQCSkus[] = $oneVariant['SellerSku'];
							    $sku = array();
							    $sku['SellerSku'] = $oneVariant['SellerSku'];
							    $images = array();
							    // 图片重新通过异步上传
        						foreach ($imageInfo['product_photos'][$oneVariant['skuSelData']] as $imageSrc){
        						    // 先将客户图片通过接口MigrateImage 上传到lazada ，返回lazada的图片链接
        						    Yii::info("ImageUpload puid:$puid before migrateImage url:$imageSrc","file");
        						    $migrateResponse = LazadaInterface_Helper::migrateImage($config,array('url'=>$imageSrc));
        						    Yii::info("ImageUpload puid:$puid after migrateImage response:".json_encode($migrateResponse["response"]),"file");
        						    if($migrateResponse['success'] != true){
        						        $canSetImage = false;
        						        // 目前总结到 state_code 为28 的为 curl timeout 可以接受重试
        						        if(isset($migrateResponse['state_code']) && (28 == $migrateResponse['state_code']
        						                || (isset($migrateResponse['response']['state_code']) && 28 == $migrateResponse['response']['state_code']))){// 不改变state 只记下error message
        						            self::changePublishListState(
        						                    $imageUploadTarget->id,
        						                    LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED,
        						                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4],
        						                    $migrateResponse['message']
        						            );
        						        }else{// 其他错误 mark state 为fail 不再重试
        						            if(stripos($migrateResponse['message'],'Internal Application Error') !== false){// dzt20160310 对平台错误 提示客户重试减少客服量
        						                $migrateResponse['message'] = $migrateResponse['message'].'<br>请重新发布商品';
        						            }
        						            self::changePublishListState(
        						                    $imageUploadTarget->id,
        						                    LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
        						                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4],
        						                    $migrateResponse['message']
        						            );
        						            	
        						            // 非网络原因都要减，不能重试
        						            if($recommendJobObj->execution_request >= 1){
        						                $recommendJobObj->execution_request = $recommendJobObj->execution_request - 1;
        						                $recommendJobObj->save(false);
        						            }
        						        }
        						    }else{
        						        $images[] = $migrateResponse['response']['url'];
        						    }
        						}

        						$sku['Images'] = $images;
							    $skus[] = $sku;
							}
							
							// 上传图片到ladaza出问题，跳过这个产品的所有sku 图片上传
							if(!$canSetImage) continue;
							
							$product['Skus'] = $skus;
								
// 							print_r($checkQCSkus);exit();
							Yii::info("ImageUpload puid:$puid before setImage uploads:".json_encode($product),"file");
							$response = LazadaInterface_Helper::setImage($config,array('product'=>$product));
							Yii::info("ImageUpload puid:$puid after setImage response:".json_encode($response["response"]),"file");
								
							if($response['success'] != true){
								// 目前总结到 state_code 为28 的为 curl timeout 可以接受重试
								if(isset($response['state_code']) && (28 == $response['state_code'] 
										|| (isset($response['response']['state_code']) && 28 == $response['response']['state_code']))){// 不改变state 只记下error message
									self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4],$response['message']);
								}else{// 其他错误 mark state 为fail 不再重试
									if(!empty($response['response']['errors'])){
									    $reqParams['errors'] = $response['response']['errors'];
									    $mapErrors = array();
								        foreach ($reqParams['errors'] as $error){
								            if(empty($mapErrors[$error['SellerSku']]))
								                $mapErrors[$error['SellerSku']] = "[SellerSku] => ".$error['SellerSku']."[Field] => ".$error['Field'].", [Message] => ".$error['Message'].".";
								            else
								                $mapErrors[$error['SellerSku']] .= "[Field] => ".$error['Field'].", [Message] => ".$error['Message'].".";
								        }
								        
								        $response['message'] = $response['message'].implode(';', $mapErrors);
									    if($ret == false)
									        self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4],$response['message']);
									}else{
									    if(stripos($response['message'],'Internal Application Error') !== false){// dzt20160310 对平台错误 提示客户重试减少客服量
									        $response['message'] = $response['message'].'<br>请重新发布商品';
									    }
									    self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4],$response['message']);
									}
									
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
								
								// 转换状态
								self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOADED,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOADED][0]);
								
								Yii::info("ImageUpload puid:$puid before getQcStatus uploads:".json_encode($checkQCSkus),"file");
								$qcResponse = LazadaInterface_Helper::getQcStatus($config,array('SkuSellerList'=>$checkQCSkus));
								Yii::info("ImageUpload puid:$puid after getQcStatus response:".json_encode($qcResponse["response"]),"file");
								$qcStatus = array();
								if ($qcResponse["success"] == true && !empty($qcResponse["response"]) && !empty($qcResponse["response"]["products"])) {
								    $qcStatus = $qcResponse["response"]["products"];
								    list($ret,$message) = LazadaCallbackHelper::markPendingProductV3($imageUploadTarget,$qcStatus);
								}else{
								    self::changePublishListState($imageUploadTarget->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
								            LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][9], "产品获取质检信息失败，请进入卖家平台查看详细问题。");
								}
								
							}
						}// end of foreach $imageUploadTargets
					}// end of !empty($imageUploadTargets)
					
					$recommendJobObj->status=2;
					$recommendJobObj->error_count=0;
					$recommendJobObj->error_message="";
					$recommendJobObj->last_finish_time=$nowTime;
					$recommendJobObj->update_time=$nowTime;
					$recommendJobObj->next_execution_time=$nowTime+3600;// 随便填，lazada图片上传并不看这个字段
					$recommendJobObj->save(false);
				}// end of foreach $recommendJobObjs
			} catch (\Exception $e){
				Yii::error("ImageUpload puid:$puid Exception".print_r($e,true),"file");
				if(!empty($recommendJobObj))
				self::handleBgJobError($recommendJobObj, $e->getMessage());
				if(!empty($imageUploadTarget))
					self::changePublishListState($imageUploadTarget->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][3],$e->getMessage());
			}	
		}
	}
	
	// 检查刊登产品属性是否非空
	private static function _checkPropertyIsEmpty($showKey , $info , $key , $publishListing ){
		if(empty($info[$key])){
			self::changePublishListState($publishListing->id,LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0],"$showKey cannot be empty.");
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
			Yii::info("changePublishListState update $recode recode for ".implode(",", $publishListingIds),"file");
		}else{
			$publishListing = LazadaPublishListing::findOne($publishListingIds);
			$publishListing->state = $state;
			$publishListing->status = $status;
			$publishListing->feed_info = $Info;
			if(!$publishListing->save()){
				Yii::error('changePublishListState lazada_uid:'.lazada_uid.' publishListingId:'.$publishListing->id.'  $$publishListing->save() 保存失败:'.print_r($publishListing->errors,true),"file");
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
		$targetListings = LazadaListingV2::find()->where(['id'=>$products['productIds']])->asArray()->all();
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
		
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaProductStatus::EDITING_QUANTITY);
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
			
			$skuInfo = json_decode($targetListing['Skus'],true);
			
			$update = array();
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);
			if(!isset($products['op']) || 0 == $products['op']){// 默认直接修改
				$update['Quantity'] = $products['quantity'];
			}else if(1 == $products['op']){// 按数量添加
				$update['Quantity'] = $skuInfo['quantity'] + $products['quantity'];
			}else if(3 == $products['op'] && $skuInfo['quantity'] < $products['condition_num']){// 少于某数量则添加
				$update['Quantity'] = $skuInfo['quantity'] + $products['quantity'];
			}else if(4 == $products['op'] && $skuInfo['quantity'] < $products['condition_num']){// 少于某数量则直接修改
				$update['Quantity'] = $products['quantity'];
			}
			
			// 不符合 条件修改库存的要跳过
			if(!isset($update['Quantity'])) {
				$targetListingObj->lb_status = 0;
				$targetListingObj->update_time = time();
				if(!$targetListingObj->save()){
					Yii::error("batchUpdateSaleInfo targetListingObj->save fail:".print_r($targetListingObj->errors,true),"file");
				}
				continue;
			}
			
			$updateProducts[$targetListing['lazada_uid']][] = $update;
			$prodIds[$targetListing['lazada_uid']][] = $targetListing['id'];
		}
		
		foreach ($updateProducts as $lazada_uid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
			$config=array(
				"userId"=>$tempConfig["userId"],
				"apiKey"=>$tempConfig["apiKey"],
				"countryCode"=>$tempConfig["countryCode"],
			);
			
			$product = array();
			$product['Skus'] = $sameAccountUpProds;
			Yii::info("batchUpdateQuantity before productUpdatePriceQuantity:".print_r($product,true),"file");
			$response = LazadaInterface_Helper::productUpdatePriceQuantity($config,array('product'=>$product));
			Yii::info("batchUpdateQuantity productUpdatePriceQuantity response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态
			    $mapErrors = array();
			    if(!empty($response['response']['errors'])){
			        foreach ($response['response']['errors'] as $error){
			            if(empty($error['SellerSku'])){
			                $response['message'] .= ";".$error['Message'].".";
			                continue;
			            }
			             
			            if(empty($mapErrors[$error['SellerSku']]))
			                $mapErrors[$error['SellerSku']] = $error['Message'].".";
			            else
			                $mapErrors[$error['SellerSku']] .= ";".$error['Message'].".";
			        }
			    }
			    LazadaListingV2::updateAll(['lb_status'=>LazadaProductStatus::EDITING_FAIL,'error_message'=>$response['message'],'update_time'=>time()],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
			    foreach ($mapErrors as $SellerSku=>$message){
			        LazadaListingV2::updateAll(['error_message'=>$response['message'].';'.$message],['lazada_uid'=>$lazada_uid,'SellerSku'=>$SellerSku]);
			    }
				Yii::error("batchUpdateQuantity productUpdatePriceQuantity fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				LazadaListingV2::updateAll(['lb_status'=>LazadaProductStatus::EDITING_SUCCESS,'update_time'=>time()],['id'=>$prodIds[$lazada_uid]]);
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
		$targetListings = LazadaListingV2::find()->where(['id'=>$products['productIds']])->asArray()->all();
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
		
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaProductStatus::EDITING_PRICE);
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
				
			$skuInfo = json_decode($targetListing['Skus'],true);
			
			$update = array();
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);
			if(isset($products['op']) && 1 == $products['op']){// 按金额添加
				$update['Price'] = $skuInfo['price'] + $products['price'];
			}else if(isset($products['op']) && 2 == $products['op']){// 按百分比添加
				$update['Price'] = $skuInfo['price'] + round(($products['price'] / 100 * $skuInfo['price']),2);
			}else{// 默认直接修改
				$update['Price'] = $products['price'];
			}
			
			$updateProducts[$targetListing['lazada_uid']][] = $update;
			$prodIds[$targetListing['lazada_uid']][] = $targetListing['id'];
		}
		
		foreach ($updateProducts as $lazada_uid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
				
			$product = array();
			$product['Skus'] = $sameAccountUpProds;
			Yii::info("batchUpdatePrice before productUpdatePriceQuantity:".print_r($product,true),"file");
			$response = LazadaInterface_Helper::productUpdatePriceQuantity($config,array('product'=>$product));
			Yii::info("batchUpdatePrice productUpdatePriceQuantity response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态
			    $mapErrors = array();
			    if(!empty($response['response']['errors'])){
			        foreach ($response['response']['errors'] as $error){
			            if(empty($error['SellerSku'])){
			                $response['message'] .= ";".$error['Message'].".";
			                continue;
			            }
			            
			            if(empty($mapErrors[$error['SellerSku']]))
			                $mapErrors[$error['SellerSku']] = $error['Message'].".";
			            else
			                $mapErrors[$error['SellerSku']] .= ";".$error['Message'].".";
			        }
			    }
			    LazadaListingV2::updateAll(['lb_status'=>LazadaProductStatus::EDITING_FAIL,'error_message'=>$response['message'],'update_time'=>time()],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
			    foreach ($mapErrors as $SellerSku=>$message){
			        LazadaListingV2::updateAll(['error_message'=>$response['message'].';'.$message],['lazada_uid'=>$lazada_uid,'SellerSku'=>$SellerSku]);
			    }
			    Yii::error("batchUpdatePrice productUpdatePriceQuantity fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				LazadaListingV2::updateAll(['lb_status'=>LazadaProductStatus::EDITING_SUCCESS,'update_time'=>time()],['id'=>$prodIds[$lazada_uid]]);
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
		$targetListings = LazadaListingV2::find()->where(['id'=>$products['productIds']])->asArray()->all();
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
	
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaProductStatus::EDITING_SALESINFO);
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
	
			$skuInfo = json_decode($targetListing['Skus'],true);
			
			// 填写修改信息
			$update = array();
			$update['SellerSku'] = LazadaApiHelper::transformFeedString($targetListing['SellerSku']);
			
			if(isset($products['op']) && 1 == $products['op']){// 按金额添加
				$update['SalePrice'] = $skuInfo['price'] + $products['salePrice'];
			}else if(isset($products['op']) && 2 == $products['op']){// 按百分比添加
				$update['SalePrice'] = $skuInfo['price'] + round(($products['salePrice'] / 100 * $skuInfo['price']),2);
			}else{// 默认直接修改
				$update['SalePrice'] = $products['salePrice'];
			}
			
			// 检查价格和促销价格大小
			if($update['SalePrice'] > $skuInfo['price']){
				$targetListingObj->lb_status = LazadaProductStatus::EDITING_FAIL;
				$targetListingObj->update_time = time();
				$targetListingObj->error_message = "促销价格必须小于价格";
				if(!$targetListingObj->save()){
					Yii::error("batchUpdateSaleInfo targetListingObj->save fail:".print_r($targetListingObj->errors,true),"file");
				}
				continue;
			}
			
			// @todo 检查促销时间
			$update['SaleStartDate'] = $products['saleStartDate'];
			$update['SaleEndDate'] = $products['saleEndDate'];
			$update['Price'] = $skuInfo['price'];// linio product update 促销信息要带价格 ， 但所有平台都提交price应该不影响
				
			$updateProducts[$targetListing['lazada_uid']][] = $update;
			$prodIds[$targetListing['lazada_uid']][] = $targetListing['id'];
		}
	
		foreach ($updateProducts as $lazada_uid=>$sameAccountUpProds){
			$tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
	
			$product = array();
			$product['Skus'] = $sameAccountUpProds;
			Yii::info("batchUpdateSaleInfo before productUpdatePriceQuantity:".print_r($product,true),"file");
			$response = LazadaInterface_Helper::productUpdatePriceQuantity($config,array('product'=>$product));
			Yii::info("batchUpdateSaleInfo productUpdatePriceQuantity response:".print_r($response,true),"file");
			if($response['success'] != true){
				// 转换状态 可重新编辑
			    $mapErrors = array();
			    if(!empty($response['response']['errors'])){
			        foreach ($response['response']['errors'] as $error){
			            if(empty($error['SellerSku'])){
			                $response['message'] .= ";".$error['Message'].".";
			                continue;
			            }
			             
			            if(empty($mapErrors[$error['SellerSku']]))
			                $mapErrors[$error['SellerSku']] = $error['Message'].".";
			            else
			                $mapErrors[$error['SellerSku']] .= ";".$error['Message'].".";
			        }
			    }
			    LazadaListingV2::updateAll(['lb_status'=>LazadaProductStatus::EDITING_FAIL,'error_message'=>$response['message'],'update_time'=>time()],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
			    foreach ($mapErrors as $SellerSku=>$message){
			        LazadaListingV2::updateAll(['error_message'=>$response['message'].';'.$message],['lazada_uid'=>$lazada_uid,'SellerSku'=>$SellerSku]);
			    }
				Yii::error("batchUpdateSaleInfo productUpdatePriceQuantity fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				LazadaListingV2::updateAll(['lb_status'=>LazadaProductStatus::EDITING_SUCCESS,'update_time'=>time()],['id'=>$prodIds[$lazada_uid]]);
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
		$targetListings = LazadaListingV2::find()->where(['id'=>$products])->asArray()->all();
		
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
		
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaProductStatus::PUTING_ON);
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
			$updateProducts[$targetListing['lazada_uid']][] = $update;
			$prodIds[$targetListing['lazada_uid']][] = $targetListing['id'];
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
				LazadaListingV2::updateAll(['is_editing'=>0,'error_message'=>$response['message']],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
				Yii::error("batchPutOnLine productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				LazadaListingV2::updateAll(['feed_id'=>$feedId],['id'=>$prodIds[$lazada_uid]]); // 记录feed id
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
		$targetListings = LazadaListingV2::find()->where(['id'=>$products])->asArray()->all();
		$allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
		$nowTime = time();
		
		$updateProducts = array();
		$prodIds = array();
		foreach ($targetListings as $targetListing){
			// 过滤产品状态，修改中不能进入修改
			// 进入修改中状态
			$targetListingObj = self::_lockLazadaListingRecord($targetListing['id'],LazadaProductStatus::PUTING_OFF);
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
			$updateProducts[$targetListing['lazada_uid']][] = $update;
			$prodIds[$targetListing['lazada_uid']][] = $targetListing['id'];
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
				LazadaListingV2::updateAll(['is_editing'=>0,'error_message'=>$response['message']],['id'=>$prodIds[$lazada_uid]]); // 记录错误信息
				Yii::error("batchPutOffLine productUpdate fail:".$response['message']." puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"],"file");
			}else{
				$excuteNume += count($sameAccountUpProds);
				$feedId = $response['response']['body']['Head']['RequestId'];
				LazadaListingV2::updateAll(['feed_id'=>$feedId],['id'=>$prodIds[$lazada_uid]]); // 记录feed id
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
	    if($publishListing->platform == "lazada"){
	        $count = LazadaListingV2::find()->where(['SellerSku'=>$toUpSkus,'platform'=>$publishListing->platform,'site'=>$publishListing->site])->count();
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