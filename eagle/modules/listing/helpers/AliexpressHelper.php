<?php 
	namespace eagle\modules\listing\helpers;
	// use eagle\modules\listing\helpers\CnToPinyinHelper;
	// use eagle\modules\listing\helpers\GetPingYingHelper;
	use eagle\models\AliexpressCategory;
	class AliexpressHelper{

		public static function TestCategory(){
			$Ali_Category = AliexpressCategory::find()->where([])->orderBy('pid asc')->asArray()->all();
			$result =[];
			$firstCategory = [];
			$secCategory = [];
			$thiCategory = [];
			foreach($Ali_Category as $k => $category){
				if($category['pid'] == 0){
					$firstCategory[] = $category['cateid'];
					$result[$category['cateid']] = $category;
					$Temp = AliexpressCategory::find()->where(['cateid'=>$category['cateid']])->one();
					$Temp->level = 0;
					$Temp->save();
				}
			}
			foreach($Ali_Category as $s => &$cate){
				if(in_array($cate['pid'],$firstCategory)){
					$secCategory[] = $cate['cateid'];
					$cate['level'] = 1;
					$result[$cate['cateid']] = $cate;
					$Temp = AliexpressCategory::find()->where(['cateid'=>$cate['cateid']])->one();
					$Temp->level = 1;
					$Temp->save();
				}
			}
			foreach($Ali_Category as $t => &$cat){
				if(in_array($cat['pid'],$secCategory)){
					$thiCategory[] = $cat['cateid'];
					$cat['level'] = 2;
					$result[$cat['cateid']]  = $cat;
					$Temp = AliexpressCategory::find()->where(['cateid'=>$cat['cateid']])->one();
					$Temp->level = 2;
					$Temp->save();
				}
			}
			foreach($Ali_Category as $m => &$ca){
				if(in_array($ca['pid'], $thiCategory)){
					$ca['level'] = 3;
					$result[$ca['cateid']] = $ca;
					$Temp = AliexpressCategory::find()->where(['cateid'=>$ca['cateid']])->one();
					$Temp->level = 3;
					$Temp->save();

				}
			}
			return $Ali_Category;
		}	

		public static function GetCompleteCategory(){
			$Ali_Category = self::TestCategory();
			$list = [] ;
			foreach($Ali_Category as $k => $cate){
				if(isset($Ali_Category[$cate['pid']])){
					if(!isset($list[$cate['pid']])){
						$list[$cate['pid']] = $Ali_Category[$cate['pid']];
					}
					$list[$k] = $cate;
					$list[$k]['name_zh'] = $list[$cate['pid']]['name_zh'] . ' > ' . $list[$k]['name_zh'];
					$list[$k]['name_en'] = $list[$cate['pid']]['name_en'] . ' > ' . $list[$k]['name_en'];
				}
			}
			foreach($list as $lk => $cat){
				if($cat['isleaf'] == 'false'){
					unset($list[$cat['cateid']]);
				}
			}
			return $list;
		}

		public static function GetOneLevelCategory($pid){
			return $Ali_Category = AliexpressCategory::find()->where(['pid'=>$pid])->orderBy('pid asc')->asArray()->all();
			
		}

		public static function GetCategoryDetail($categoryid){
			$cate = AliexpressCategory::find()->where(['cateid'=>$categoryid])->asArray()->one();
			$cateInfo = [];
			$result = [];
			if($cate['pid'] != 0){
				$cateInfo = self::GetCategoryDetail($cate['pid']);
				$result['nameZh'] =  $cateInfo['nameZh'].' > ' . $cate['name_zh'];
				$result['nameEn'] =  $cateInfo['nameEn']. ' > ' . $cate['name_en'];
				$result['name_zh'] = $cate['name_zh'];
				$result['name_en'] = $cate['name_en'];
				$result['pids'] = $cateInfo['pids'] . ',' . $cate['pid'];
				$result['cateIds'] = $cateInfo['cateIds'] . ',' . $cate['cateid'];
				$result['pid'] = $cate['pid'];
				$result['cateid'] =$cate['cateid'];
			}else{
				// $result['name_zh'] = $result['name_zh'] + ' > ' + $cate['name_zh'];
				$result['nameZh'] = $cate['name_zh']; 
				$result['nameEn'] = $cate['name_en'];
				$result['pids'] = $cate['pid'];
				$result['cateIds'] = $cate['cateid'];
			}
			return $result;
		}


		public static function GetCategoryList($cateName){
			$where = ['and',"isleaf = 'true'","name_en like '%{$cateName}%'"];
			$cateList = AliexpressCategory::find()->where($where)->asArray()->all();
			$result = [];
			foreach($cateList as $key => $cate){
				array_push($result,self::GetCategoryDetail($cate['cateid']));
			}
			return $result;
		}



		public static function GetProductGroupsName($selleruserid,$groups){
			$result ='';
			$product_groups = [];
			foreach($groups as $group){
				array_push($product_groups,AlipressApiHelper::getGroupInfoRow($selleruserid,$group));
			}
			foreach($product_groups as $group){
				$result .= $group['group_name'] .'、';
			}
			return trim($result,'、');
		}

		public static function getTime($time){
			return date('Y-m-d H:i:s',$time);
		}

		public static function _getProductDetail($detail){
	   		preg_match_all('/<kse:widget\sdata-widget-type="relatedProduct"\sid="(.*?)"\stitle="(.*?)".*?><\/kse:widget>/',$detail,$matches);
	   		$str = '';
	   		for($i=0;$i<count($matches[1]);$i++){
	   			//$str[$i] = '<kse:widget data-widget-type="relatedProduct" id="'.$matches[1][$i].'" title="'.$matches[2][$i].'" type="relation"></kse:widget>';
	   			$str[$i] = '<img data-kse-id="'.$matches[1][$i].'" src="/images/aliexpress/kse-default.png" title="'.$matches[2][$i].'" />';
	   		}
	   		$find = ['/<kse:widget\sdata-widget-type="relatedProduct".*?><\/kse:widget>/'];
	   		return preg_replace($find, $str,$detail);
	   	}

	   	public static function _uploadingImgToAliexpressBank($imgs,$sellerloginid){
	   		$imgList = explode(';',$imgs);
	   		$img_url = '';
	   		foreach($imgList as $img){
	   			$imgName = basename($img);
	   			$result = AlipressApiHelper::uploadImage($sellerloginid,$imgName,$img);
	   			if($result['success'] == true){
	   				$img_url .= $result['photobankUrl'] . ';';
	   			}else{
	   				$img_url = $img;
	   			}
	   		}
	   		return trim($img_url,';');
	   	}

	   	public static function AliexpressErrorMessage($code){
	   		$Message = [
	   			'07005999'=>'系统调用会员服务超时',
	   			'07005005'=>'账户不存在',
	   			'07004403'=>'currencyCode与aeopAeProductSKUs参数中的currencyCode属性设置有误',
	   			'07004404'=>'currencyCode与aeopAeProductSKUs参数中的currencyCode属性设置有误',
	   			'07002998'=>'aeopAeProductSKUs参数的格式错误',
	   			'07004999'=>'aeopAeProductPropertys参数的格式错误',
	   			'07009993'=>'系统内部异常，请联系技术支持',
	   			'07004020'=>'请卖家先申请加入海外仓白名单后再试',
	   			'07004021'=>'请卖家先加入当前发布类目的白名单',
	   			'07200021'=>'请检查当前请求中是否提供了deliveryTime参数',
	   			'07200051'=>'请检查当前请求中是否提供了freightTemplateId参数',
	   			'07200063'=>'请检查当前请求中是否提供了subject参数',
	   			'07200061'=>'请检查subject参数中是否包含了非英文参数',
	   			'07200062'=>'请检查subject参数是否包含了上述的非法字符',
	   			'07200064'=>'请检查subject参数的长度',
	   			'07200101'=>'请检查当前请求中是否提供了categoryId参数',
	   			'07205002'=>'请检查wsValidNum参数的值是否在14～30天之间',
	   			'07201001'=>'请检查当前请求中是否提供了detail参数',
	   			'07201002'=>'请检查detail参数中是否包含了非英文参数',
	   			'07201003'=>'detail参数中包含了@符号或者一些非阿里系的外链',
	   			'07201004'=>'请检查detail参数的长度',
	   			'07201021'=>'aeopAeProductPropertys参数中的attrName属性包含了一些非英文字符',
	   			'07201022'=>'aeopAeProductPropertys参数中的attrName属性包含了一些非法字符',
	   			'07201023'=>'aeopAeProductPropertys参数中的attrName属性长度超过了40个字符',
	   			'07201031'=>'aeopAeProductPropertys参数中的attrValue属性包含了一些非英文字符',
	   			'07201032'=>'aeopAeProductPropertys参数中的attrValue属性包含了一些非法字符',
	   			'07201033'=>'aeopAeProductPropertys参数中的attrValue属性长度超过了70个字符',
	   			'07202001'=>'productUnit参数未设置',
	   			'07202021'=>'grossWeight参数未设置',
	   			'07204021'=>'请检查当前请求中是否包含了imageURLs参数',
	   			'07001009'=>'请检查当前请求中是否包含了imageURLs参数',
	   			'07005003'=>'当前卖家账户被处罚，无法发布商品',
	   			'07005001'=>'当前卖家账户未通过实名认证，无法发布商品',
	   			'07004016'=>'商品必填类目属性未填',
	   			'07004007'=>'aeopAeProductPropertys参数填写错误',
	   			'07004013'=>'aeopAeProductPropertys参数填写错误',
	   			'07004014'=>'aeopAeProductPropertys参数填写错误',
	   			'07004015'=>'aeopAeProductPropertys参数填写错误',
	   			'07004019'=>'当前卖家未对商品所在类目的缴费或者续约，无法发布当前类目下的商品',
	   			'07003001'=>'当前商品的详描中存在盗图',
	   			'07001007'=>'categoryId参数所指定的类目属于非叶子类目，无法发布商品',
	   			'07001008'=>'categoryId参数所指定的类目不存在，无法发布商品',
	   			'07001032'=>'categoryId参数所指定的类目是属于一个假一赔三类目，但当前账户的卖家未加入假一赔三服务，无法发布商品',
	   			'07004017'=>'aeopAeProductPropertys参数中的所有非自定义属性加起来的长度超过了4000个字符',
	   			'07004018'=>'aeopAeProductPropertys参数中的所有自定义属性加起来的长度超过了4000个字符',
	   			'07001021'=>'bulkOrder参数和bulkDiscount参数必须同时存在或者不设置',
	   			'07001013'=>'deliveryTime参数值超过了当前类目所规定的上限',
	   			'07001014'=>'bulkOrder参数取值不合法，应该位于[1,99]之间',
	   			'07001015'=>'bulkDiscount参数取值不合法。应该位于[2,100000]之间',
	   			'07001016'=>'groupId参数所指定的产品分组不存在',
	   			'07003002'=>'detail参数中所关联的产品模块超过了2个',
	   			'07003003'=>'detail参数中所关联的产品模块中至少有一个无内容',
	   			'07001001'=>'imageURLs参数中主图的张数超过了6张',
	   			'07001017'=>'imageURLs参数中图片的格式不合法',
	   			'07001028'=>'imageURLs参数中有图片丢失',
	   			'07004001'=>'packageType参数设置为true（打包出售）。但是未提供lotNum参数或者lotNum参数的取值不在2～100000之间',
	   			'07004008'=>'isPackSell参数设置为true(支持自定义计重)。但是未提供addUnit参数或者addUnit参数的取值不在1～1000之间',
	   			'07004009'=>'isPackSell参数设置为true(支持自定义计重)。但是未提供baseUnit参数或者baseUnit参数的取值不在1～1000之间',
	   			'07004010'=>'isPacketSell参数设置为true(支持自定义计重)。但是未提供addWeight参数或者addWeight参数的取值不在0.001～500.00之间',
	   			'07004002'=>'packageHeight参数未设置或者packageHeight参数的取值不在1-700之间',
	   			'07004003'=>'packageLength参数未设置或者packageLength参数的取值不在1-700之间',
	   			'07004004'=>'packageWidth参数未设置或者packageWidth参数的取值不在1-700之间',
	   			'07004006'=>'grossWeight参数未设置或者grossWeight参数的取值不在0.001～500.00之间',
	   			'07004005'=>'产品包装尺寸的最大值(packageLength, packageWidth, packageHeight三者之间的最大值)+ 2*(packageHeight+packageLength+packageWidth - 最大值)= 2700',
	   			'07004011'=>'productUnit参数取值非法。请查看productUnit的参数说明，选择合适的单位',
	   			'07001002'=>'freightTemplateId参数设置错误',
	   			'07001003'=>'freightTemplateId参数对应的运费模版中的内容有错误',
	   			'07002018'=>'aeopAeProductSKUs参数中的SKU个数大于256个',
	   			'07001022'=>'productPrice参数取值不在1-1000000之间',
	   			'07002006'=>'aeopAeProductSKUs参数中的aeopSKUProperty数组的长度大于3',
	   			'07002001'=>'aeopAeProductSKUs参数中的skuPrice属性的取值不在1-1000000之间',
	   			'07002013'=>'aeopAeProductSKUs参数中的skuCode属性填写错误',
	   			'07002015'=>'aeopAeProductSKUs参数中的ipmSkuStock属性取值不在0~999999之间',
	   			'07002002'=>'aeopAeProductSKUs参数填写有误',
	   			'07002007'=>'aeopAeProductSKUs参数中的aeopSKUProperty属性中存在skuPropertyId:null的SKU',
	   			'07002008'=>'aeopAeProductSKUs参数中的aeopSKUProperty属性中存在propertyValueId:null的SKU',
	   			'07002011'=>'aeopAeProductSKUs参数中的aeopSKUProperty属性提供了propertyValueDefinitionName参数填写有误。只能包含20个字符以内的英文、数字',
	   			'07002012'=>'aeopAeProductSKUs参数中的aeopSKUProperty属性提供了skuImage参数有误',
	   			'07002004'=>'aeopAeProductSKUs参数中的aeopSKUProperty属性中的propertyValueId不在类目规定的候选值列表中',
	   			'07002003'=>'aeopAeProductSKUs参数中的aeopSKUProperty属性中的skuPropertyId不在类目规定的候选值列表中',
	   			'07002005'=>'aeopAeProductSKUs参数中的aeopSKUProperty属性中的SKU顺序排列有误',
	   			'07002014'=>'aeopAeProductSKUs参数中的aeopSKUProperty属性存在重复的SKU属性',
	   			'07002017'=>'aeopAeProductSKUs参数中的SKU个数与实际选择的属性积不一致',
	   			'07001043'=>'商品维度的总库存值不合法。即aeopAeProductSKUs参数中的ipmSkuStock之和不在1~999999之间',
	   			'07001034'=>'卖家的图片银行空间已满。系统无法将详描中的图片保存到图片银行',
	   			'07001035'=>'商品详描中的图片已经在图片银行中，无需重复上传。请直接引用图片银行中的图片即可',
	   			'07001036'=>'商品详描中存在一些图片。这些图片在图片银行中已被卖家删除。请删除这些图片后重新发布',
	   			'07001037'=>'商品详描中存在一些图片的大小超过了3M。导致无法上传到图片银行。请删除或者缩减这些图片的大小后再发布',
	   			'07001038'=>'商品详描中存在一些图片是一些废图片(无法读取这些图片内容)。导致无法上传到图片银行。请删除这些图片后再发布',
	   			'07001039'=>'系统上传图片到图片银行失败，导致商品发布失败',
	   			'07001040'=>'系统上传图片到图片银行失败，导致商品发布失败',
	   			'07001041'=>'系统上传图片到图片银行失败，导致商品发布失败',
	   			'07002995'=>'请检查aeopAeProductSKUs参数中所有aeopSKUProperty数组的长度是否一致',
	   			'07092001'=>'sizechartId参数非法。正确的应该是一个正整数',
	   			'07099000'=>'sizechartId参数所指定的尺码模版不存在',
	   			'07092003'=>'sizechartId所指定的服务模版不属于当前卖家',
	   			'07092004'=>'sizechartId所指定的尺码模版无类目与之匹配',
	   			'07092005'=>'sizechartId所指定的尺码模版与categoryId参数所对应的服务模版不匹配，无法设置',
	   			'07004401'=>'currencyCode与aeopAeProductSKUs参数中的currencyCode属性设置有误',
	   			'07003004'=>'处理详描外链失败',
	   			'07009998'=>'发布商品超时',
	   			'07009999'=>'系统未知异常',
	   			'07005008'=>'产品没有品牌准入权限',
	   			'07004022'=>'品牌属性不可自定义'
	   		];
	   		return isset($Message[$code]) ? $Message[$code] : $code;
	   	}
	}