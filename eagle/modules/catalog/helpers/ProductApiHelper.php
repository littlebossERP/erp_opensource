<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */

namespace eagle\modules\catalog\helpers;

use Yii;
use eagle\modules\purchase\helpers\SupplierHelper;
use eagle\modules\catalog\models\Tag;
use eagle\modules\catalog\models\Product;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\catalog\models\ProductAliases;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\catalog\models\ProductSuppliers;
use eagle\modules\catalog\models\Brand;
use eagle\modules\catalog\models\ProductTags;
use eagle\modules\permission\helpers\UserHelper;

class ProductApiHelper{
	

	
	/**
	 +----------------------------------------------------------
	 * 根据供应商名字找出供应商编号
	 +----------------------------------------------------------
	 * @access static     ProductApiHelper::getSupplierId($supplier_name)
	 +----------------------------------------------------------
	 * @param $supplier_name		供应商名字
	 +----------------------------------------------------------
	 * @return		array
	 * 	boolean			success  执行结果
	 * 	string			message  执行失败的提示信息
	 * 	int				supplier_id 供应商编号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/03/16				初始化
	 +----------------------------------------------------------
	 **/
	static public function getSupplierId( $supplier_name  ){
		return SupplierHelper::getSupplierId($supplier_name,true);//true for AutoAdd if not existing
	}//end of getSupplierId
	
	/**
	 * +----------------------------------------------------------
	 * 保存网站与eagle中某商品的关联关系
	 *
	 * +----------------------------------------------------------
	 *
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	productid product id 对应 平台 的id
	 * @param
	 *        	sku 商品SKU
	 * @param
	 *        	type 关联商品的来源
	 *+----------------------------------------------------------
	 * @return array ( message=>'执行结果：
	 *         aliasexist 别名重复关联 ;
	 *         skuexist' productid = sku 并已创建，不需要操作 ；
	 *         sku_alias 成功创建商品和别名;
	 *         sku :成功创建商品;
	 *         alias : 成功创建别名 )
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh 	2015/04/25				初始化
	 *+----------------------------------------------------------
	 */
	static function saveRelationProduct($productid, $sku, $type = 'ebay', $pkid = null) {
		return ProductHelper::saveRelationProduct($productid, $sku, $type , $pkid);
	}//end of saveRelationProduct
	
	
	
	/**
	 * +----------------------------------------------------------
	 * 检查这个sku 是否在商品模块对应某个商品
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku 待检测的商品 SKU/alias
	 *+----------------------------------------------------------
	 * @return boolean true 为有对应的商品  , false 为没有对应 的商品
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh 	2015/04/30				初始化
	 *+----------------------------------------------------------
	 */
	static public function hasProduct($sku, $platform = '', $selleruserid = ''){
		$root_sku = ProductHelper::getRootSkuByAlias($sku, $platform, $selleruserid);
		return (! empty($root_sku));
	}//end of hasProduct

	//yzq 20170221, for performance tuning, pass in an array of many keys, and return array of results
	static public function hasProductArray($skuArr){
		$results = ProductHelper::getRootSkuByAliasArr($skuArr);
		/*result like array ('sk1'=>sk1, 'skalias1'=>sk2)
		 * */
		return $results;
	}//end of hasProduct
	
	/**
	 * +----------------------------------------------------------
	 * 根据参数sku 获取与参数 sku相关的商品信息 
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku 待检测的商品SKU/alias
	 *+----------------------------------------------------------
	 * @return  如果是变参或者Bundle商品，会有Children这个属性，下面是变参或者bundle
	 * 对应的子产品array 
		array(
	 *		Sku=>’sk1’,’name’=>’computer’,...  Type='B'/'S'/'C', 代表(’Bundle’/'Simple'/'Config'), 
	 *		Children = array (‘0’=>[sku=’’, name=’’] , ‘1’=>[sku=’’,name=’’])
	 *	)
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh 	2015/04/30				初始化
	 *+----------------------------------------------------------
	 */
	static public function getProductInfo($sku){
		return ProductHelper::getProductInfo($sku);
	}//end of getProductInfo
	/**
	 * +----------------------------------------------------------
	 * 根据参数sku 获取与参数 sku相关的商品信息主要用于发货配货，物流等
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku 待检测的商品SKU/alias
	 *        quantiey 数量
	*+----------------------------------------------------------
	 * @return  skus
	 * 对应产品array 
		array(
	 *		0=>array(Sku=>’sk1’,’name’=>’computer’,...  Type='B'/'S'/'C', 代表(’Bundle’/'Simple'/'Config'));
	 *		0=>array(Sku=>’sk1’,’name’=>’computer’,...  Type='B'/'S'/'C', 代表(’Bundle’/'Simple'/'Config'));
	 *	)
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		million 	2015/07/23				初始化
	 *+----------------------------------------------------------
	 */
	static public function getSkuInfo($sku,$quantity){
		$skus = array();
		//对于Cdiscount的不发货sku，直接返回[]
		if(in_array($sku,CdiscountOrderInterface::getNonDeliverySku()))
			return $skus;
		$info =  self::getProductInfo($sku);
		if ($info==null){//sku不存在
			$skus[] = array('sku'=>$sku,'qty'=>$quantity);
			return $skus;
		}
		
		if ( isset($info['children'] ) && count( $info['children'] ) > 0 ){
			foreach ($info['children'] as $one){
				$q = $one['qty'];
				$one['qty']= $q*$quantity;
				$skus[] = $one;
			}
		}else{
			$info['qty'] = $quantity;
			$skus[]=$info;
		}
		return $skus;
	}//end of getProductInfo
	
	/**
	 * +----------------------------------------------------------
	 * 返回用户所有产品tag
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @return  array(
	 * 				'tag_id'=>'tag_name',
	 *				'tag_id'=>'tag_name',
	 * 				'tag_id'=>'tag_name',
	 * 				)
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lzhl 	2015/07/06				初始化
	 *+----------------------------------------------------------
	 */
	static public function getAllTags(){
		$list = Tag::find()->asArray()->all();
		//加上是否有电池标签默认初始数据 million
		$result=array('battery'=>'有电池');
		foreach ($list as $aTag){
			$result[$aTag['tag_id']]=$aTag['tag_name'];
		}
		return $result;
	}//end of getAllTags
	
	/**
	 * +----------------------------------------------------------
	 * 检测指定sku产品是否带电池
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	$queryKey		string 'sku'/'product_id'
	 * 			$queryValue		string/array 单个sku或sku数组，or单个product_id或product_id数组
	 *+----------------------------------------------------------
	 * @return  array(
	 * 				queryValue1=>'Y'/'N'/'product not exists',//Y:带电池，N:不带电池，'product not exists':产品不存在
	 *				queryValue2=>'Y'/'N'/'product not exists',
	 * 				queryValue3=>'Y'/'N'/'product not exists',
	 * 				)
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lzhl 	2015/07/06				初始化
	 *+----------------------------------------------------------
	 */
	static public function checkProdcutIsHasBattery($queryKey,$queryValue){
		$result=array();
		
		$paramKey = $queryKey;
		$paramValue = array();
		if(is_array($queryValue)){
			$paramValue=$queryValue;
		}else{
			$paramValue[]=$queryValue;
		}

		foreach ($paramValue as $value){
			$query=Product::find()->select('battery')->where([$paramKey=>$value])->one();
			if($query==null){
				$result[$value]='product not exists';
			}else{
				if($query->battery=='Y'){
					$result[$value]='Y';
				}else{
					$result[$value]='N';//battery set 'N',or null
				}
			}
		}

		return $result;
	}//end of checkProdcutIsHasBattery
	/**
	 * +----------------------------------------------------------
	 * 解析sku
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	$sku		需要解析的sku
	 *+----------------------------------------------------------
	 * @return  array(
	 * 				0=>('sku','数量','是否捆绑SKU"Y/N"'),Y是捆绑sku，N普通SKU
	 * 				1=>('sku','数量','是否捆绑SKU"Y/N"'),Y是捆绑sku，N普通SKU
	 * 				)
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		million 	2015/07/18			初始化
	 *+----------------------------------------------------------
	 */
	static public function explodeSku($sku){
		global $CACHE;
		/* $skurule = array(
				'firstKey' => 'sku',
				'quantityConnector' => '*',
				'secondKey' => 'quantity',
				'skuConnector' => '+',
				'keyword' =>array(0=>''),
		); */
		//获取解析规则
		//2016-07-05  为后台检测订单加上global cache 的使用方法 start
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if (isset($CACHE[$uid]['skurule'])){
			$skurule_str = $CACHE[$uid]['skurule'];
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'skurule has cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}else{
			$skurule_str = ConfigHelper::getConfig("skurule");
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'skurule no cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
			
		}
		
		//是否开启sku 解释
		if (isset($CACHE[$uid]['analysis_rule_active'])){
			$isActive = $CACHE[$uid]['analysis_rule_active'];
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'analysis_rule_active has cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}else{
			$isActive = ConfigHelper::getConfig('configuration/productconfig/analysis_rule_active');
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'analysis_rule_active no cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}
		
		//2016-07-05  为后台检测订单加上global cache 的使用方法 end
		
		//判断是否有解析规则
    	if ($skurule_str != null && $isActive ==1){
    		$skurule = json_decode($skurule_str,true);
    		if (isset($skurule['firstKey']) && $skurule['firstKey'] == 'sku'){
    			$firstKey = 'sku';
    			$secondKey = 'quantity';
    		}elseif (isset($skurule['firstKey']) && $skurule['firstKey'] == 'quantity'){
    			$firstKey = 'quantity';
    			$secondKey = 'sku';
    		}else{
    			$firstKey = 'sku';
    			$secondKey = 'quantity';
    		}
    		
    		if (isset($skurule['quantityConnector'])&&strlen($skurule['quantityConnector'])>0){
    			$quantityConnector = $skurule['quantityConnector'];
    		}else {
    			$quantityConnector = '*';
    		}
    		if (isset($skurule['skuConnector'])&&strlen($skurule['skuConnector'])>0){
    			$skuConnector = $skurule['skuConnector'];
    		}else{
    			$quantityConnector = '+';
    		}
    		if (isset($skurule['keyword'])){
    			$keyword = $skurule['keyword'];
    		}

            if (isset($skurule['firstChar'])){
                $firstChar = $skurule['firstChar'];
            }

            if (isset($skurule['secondChar'])){
                $secondChar = $skurule['secondChar'];
            }

    		//替换前后缀为空
    		$is_bind = false;//是否捆绑sku
    		$str = $sku;
    		if (!empty($keyword)){
    			foreach($keyword as $onekeyword){
    				$str = self::__replaceKeyWord($str, $onekeyword, '');
    			};
    		}
    		//解析sku
    		//假如连接符号 为空就使用原来 的sku 为最终解释的sku
    		if (empty($skuConnector)){
    			$sku_quantity_arr = [$str];
    		}else{
    			$sku_quantity_arr = explode($skuConnector, $str);
    		}
    		
    		$data = array();
    		foreach ($sku_quantity_arr as $sku_quantity){
    			if ($firstKey =='sku'){
    				$tmp_arr = explode($quantityConnector, $sku_quantity);

                    if (count($tmp_arr)==1){
    					$simplesku=$tmp_arr[0];
    					$quantity=1;
    				}else{
    					list($simplesku,$quantity)=$tmp_arr;
                        $markArr = self::getMarkArr();  //囊括所有常用英文符号
                        foreach($markArr as $mark)
                        {
                            //判断是否含有符号的$simplesku，有则截取当中sku覆盖$simplesku;没则原样覆盖$simplesku
                            if(strpos($simplesku,$mark)!==false)
                            {
                                $simplesku_exist_other_arr = explode($mark, $simplesku);
                                $simplesku = $simplesku_exist_other_arr[1];
                            }
                            else
                            {
                                $simplesku = $simplesku;
                            }
                            //判断是否含有符号的$quantity,有则截取当中“数量”覆盖$quantity;没则原样覆盖$quantity
                            if(strpos($quantity,$mark)!==false)
                            {
                                $quantity_exist_other_arr = explode($mark, $quantity);
                                $quantity = $quantity_exist_other_arr[0];
                            }
                            else
                            {
                                $quantity = $quantity;
                            }
                        }
                        //   if(empty($quantity)){$quantity = 1;}
    				}
    			}else
                {
    				$tmp_arr = explode($quantityConnector, $sku_quantity);
    				if (count($tmp_arr)==1){
    					$simplesku=$tmp_arr[0];
    					$quantity=1;
    				}else
                    {
    					list($quantity,$simplesku)=$tmp_arr;
                        $markArr = self::getMarkArr();
                        foreach($markArr as $mark)
                        {
                            if(strpos($simplesku,$mark)!==false)
                            {
                                $simplesku_exist_other_arr = explode($mark, $simplesku);
                                $simplesku = $simplesku_exist_other_arr[0];
                            }
                            else
                            {
                                $simplesku = $simplesku;
                            }
                            if(strpos($quantity,$mark)!==false)
                            {
                                $quantity_exist_other_arr = explode($mark, $quantity);
                                $quantity = $quantity_exist_other_arr[1];
                            }
                            else
                            {
                                $quantity = $quantity;
                            }

                        }
    				}
    			}
    			//如果解析出来的sku和原始sku不相等则说明是捆绑sku
    			if ($simplesku != $sku){$is_bind=true;}
    			(integer)$quantity;
    			$data[] = array('sku'=>$simplesku,'quantity'=>$quantity,'is_bind'=>'N');
    		}
    		if ($is_bind){
    			$data[] =  array('sku'=>$sku,'quantity'=>1,'is_bind'=>'Y');
    		}
    		return $data;
    	}else{//没有设置规则则直接返回原样sku
    		return array(0=>array('sku'=>$sku,'quantity'=>1,'is_bind'=>'N'));
    	}
		
	}
	static public function __replaceKeyWord($str,$find,$replace){
		return  str_replace($find, $replace, $str);
	}

    static public function getMarkArr(){
        //一般使用到的符号集合
        $markArr=['`','~','!','@','#','$','%','^','&','*','(',')','-','_','=','+','[','{',']','}',';',':','"','\'','|',',','<','.','>','/','?'];
        return $markArr;
    }
	/**
	 * +----------------------------------------------------------
	 * 解析sku并保存成商品
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	$sku		需要解析的sku
	 * @param	$uid		用户id
	 * @param	$productInfo= array(
	 * 'sku'=>SKU,{必填}
	 * 'name'=>商品名（标题）,{必填}
	 * 'prod_name_ch'=>中文配货名（标题）,{必填}
	 * 'photo_primary'=>图片（photo_primary）,{选填}
	 * 'prod_name_en'=>英文配货名（标题）,{必填}
	 * 'declaration_ch'=>中文报关名（默认“礼品”）,{必填}
	 * 'declaration_en'=>英文报关名（默认“礼品”英文）,{必填}
	 * 'declaration_value_currency'=>申报货币(od_order_v2[currency]),{必填}
	 * 'declaration_value'=>申报价值（od_order_item_v2[price]）,{必填}
	 * 'prod_weight'=>重量（默认50克）,{必填}
	 * 'battery'=>是否含有锂电池，default：'N'{选填}
	 * 'platform'=>来源平台{必填}
	 * 'itemid'=>来源平台item id{必填}
	 * )
	 *+----------------------------------------------------------
	 * @return  array(
	 * 0=>array('sku'=>'sku','type'=>'商品类型(捆绑B或者普通S)','success'=>'保存结果true/false','message'=>'错误信息'),
	 * 1=>array('sku'=>'sku','type'=>'商品类型(捆绑B或者普通S)','success'=>'保存结果true/false','message'=>'错误信息'),
	 * )
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		million 	2015/07/18			初始化
	 *+----------------------------------------------------------
	 */
	static public function explodeSkuAndCreateProduct($sku,$uid,$productInfo){
		//解析SKU
		$data = self::explodeSku($sku);
		$childrens = array();
		$result = array();
		foreach ($data as $one){
			//echo "\n".(__function__)." ".(__line__)."\n";//test kh
			if ($one['is_bind']=='N'){//普通商品保存
				$r = self::creatSimpleProductFromOMS(
						$one['sku'], 
						$uid,
						$productInfo['name'], 
						$productInfo['prod_name_ch'], 
						$productInfo['prod_name_en'],
						$productInfo['photo_primary'],
						$productInfo['declaration_ch'], 
						$productInfo['declaration_en'], 
						$productInfo['declaration_value_currency'], 
						$productInfo['declaration_value'],
						$productInfo['prod_weight'],
						$productInfo['battery'],
						$productInfo['platform'],
						$productInfo['itemid'],
						'S',
						empty($productInfo['other_attr'])?[]:$productInfo['other_attr'],
						empty($productInfo['detail_hs_code']) ? '' : $productInfo['detail_hs_code']
				);
				//echo "\n".(__function__)." ".(__line__)."\n";//test kh
				$childrens[$one['sku']]=$one['quantity'];
				$result[] = array('sku'=>$one['sku'],'type'=>'S','success'=>$r['success'],'message'=>$r['message']);
			}elseif ($one['is_bind']=='Y'){//捆绑商品保存
				//echo "\n".(__function__)." ".(__line__)."\n";//test kh
				$r = self::creatBundleProductFromOMS(
						$one['sku'],
						$uid,
						$productInfo['name'],
						$productInfo['prod_name_ch'],
						$productInfo['prod_name_en'],
						$productInfo['photo_primary'],
						$productInfo['declaration_ch'],
						$productInfo['declaration_en'],
						$productInfo['declaration_value_currency'],
						$productInfo['declaration_value'],
						$productInfo['prod_weight'],
						$productInfo['battery'],
						$productInfo['platform'],
						$productInfo['itemid'],
						'B',
						array(),
						$childrens,
						empty($productInfo['detail_hs_code']) ? '' : $productInfo['detail_hs_code']
				);
				//echo "\n".(__function__)." ".(__line__)."\n";//test kh
				$result[] = array('sku'=>$one['sku'],'type'=>'B','success'=>$r['success'],'message'=>$r['message']);
			}
		}
		return  $result;
	}
	
	
	/**
	 * 新建普通产品到用户商品库
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	(string,required)$sku							商品sku
	 * @param	(string,required)$uid							用户id
	 * @param	(string,required)$name							商品名称
	 * @param	(string,required)$prod_name_ch					商品中文配货名
	 * @param	(string,required)$prod_name_en					商品英文配货名
	 * @param	(string)$photo_primary							商品图片
	 * @param	(string,required)$declaration_ch				商品中文报关名，default:'礼品'
	 * @param	(string,required)$declaration_en				商品英文报关名，default:'gift'
	 * @param	(string,required)$declaration_value_currency	报关币种
	 * @param	(string)$declaration_value						报关价格
	 * @param	(string)$prod_weight							商品重量，default：50
	 * @param 	(string)$battery								是否含有锂电池，default：'N'
	 * @param  	(string,required)$create_source					商品来源
	 * @param  	(string)$itemId									来源平台item id
	 * @param	(string)$status									商品状态 default OS  , '“OS”:on sale “RN”:running out “DR”:dropped “AC”：archived “RS”:re-onsale'
	 * @param	(string)$prod_width								商品宽度，default：0
	 * @param	(string)$prod_length							商品长度，default：0
	 * @param	(string)$prod_height							商品高度，default：0
	 * @param	(string)$other_attributes						其他属性(json格式传入)
	 * @param	(string)$supplier								供应商名字
	 * @param	(string)$declaration_code						报关编码
	 *+----------------------------------------------------------
	 * @return  array(
	 * 				'success'=true/false,//成功新建或者不需要新建时为true；否则为false。
	 * 				'message'=message,//成功为新建''；否则为提示，或errorMessage。
	 * 				'created'=true/false,//成功新建为true；否则为false。
	 * 			)
	 *+----------------------------------------------------------
	 * log			name	date			note
	 * @author 		lkh 	2015/08/06		初始化
	 */
	static public function createSimpleProductFromCHI($sku,$uid,$name,$prod_name_ch,$prod_name_en,$photo_primary='',
		$declaration_ch='礼品',$declaration_en='gift',$declaration_value_currency,$declaration_value,$prod_weight=50,$battery='N',
		$create_source,$itemId='',$status='OS',$prod_width=0 , $prod_length=0,$prod_height=0 , $other_attributes='' , $supplier='' , $purchase_price=0, $declaration_code='')
	{
		
		$result['success']=true;
		$result['message']='';
		$result['created']=false;
		$other_attributes_list = [];
		$attr_str = '';
		/*$journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ , array($sku, $uid, $name, $prod_name_ch, $photo_primary,
    			$declaration_ch,$declaration_en,$declaration_value_currency,$declaration_value,$prod_weight,$battery,
    			$create_source,$itemId,$status,$prod_width, $prod_length,$prod_height , $other_attributes));
	*/
		$model = Product::findOne($sku);
		if($model<>null){
			$result['message']='商品库已经存在'.$sku.'，无需新建';
		}else{
			if (! empty($other_attributes) && is_string($other_attributes)) 
				$other_attributes_list = json_decode($other_attributes,true);
			
			if (! empty($other_attributes_list) && is_array($other_attributes_list)){
				foreach ($other_attributes_list as $field_name=>$value){
					if (!empty($attr_str)) $attr_str .= ';';
					
					$attr_str .= $field_name.":".$value;
				}
				
			}
			
			$attrStr = ProductFieldHelper::uniqueProductFieldStr($attr_str);
			
			ProductFieldHelper::updateField($attr_str);//新产品更新所有属性到属性表
			
			//供应商名称转id
			$supplier_rt =  self::getSupplierId($supplier);
			if (!empty($supplier_rt['supplier_id'])) $supplier_id = $supplier_rt['supplier_id'];
			else $supplier_id = 0;
			
			$model = new Product();
			$model->sku = (string)$sku;
			$model->is_has_alias = 'N';
			$model->name = $name;
			$model->type = 'S';
			$model->status = $status;
			$model->prod_name_ch = $prod_name_ch;
			$model->prod_name_en = $prod_name_en;
			$model->declaration_ch = $declaration_ch;
			$model->declaration_en = $declaration_en;
			$model->declaration_value_currency = $declaration_value_currency;
			$model->declaration_value = $declaration_value;
			$model->declaration_code = $declaration_code;
			$model->prod_weight = (int)$prod_weight;
			$model->battery = $battery;
			$model->brand_id = 0;
			$model->is_has_tag = 'N';
			$model->purchase_by = 0;
			$model->photo_primary = $photo_primary;
			$model->comment = '自动创建'.$create_source.'商品,item_id:'.$itemId;
			$model->capture_user_id = $uid;
			$model->create_time = date('Y-m-d', time());
			$model->create_source = $create_source;
			$model->other_attributes = $attr_str;
			$model->supplier_id = (int)$supplier_id;
			$model->purchase_price = (int)$purchase_price;
			
			$supplier_list = [
				'supplier_id'=>[$supplier_id,0,0,0,0],
				'purchase_price'=>[$purchase_price,0,0,0,0],
			];
			
			ProductSuppliersHelper::updateProductSuppliers($model->sku, $model->attributes, $supplier_list);
	
			if($model->save()){
				$result['created']=true;
			}else{
				$result['success']=false;
				foreach ($model->errors as $k => $anError){
					$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
				}
				echo "<br> $sku  error message :".$result['message'];//test kh
			}
		}
	
		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
		unset($model);
		return $result;
	}
	
	static public function importProductPhotoDataFromCHI($table_list){
		
		foreach($table_list as $row){
			$photoInfo = json_decode($row['photo_info'],true);
			unset ($photo_primary);
			unset($photo_others);
			/*
			 * 第一个为小图的作为 的主图 , 原图也要保存, 其他的只要原图
			 * 当3张图片(thumb_small_url , thumb_large_url , source_url)都 一样的时候 只存一张图片 
			 */
			$photo_primary = '';
			foreach($photoInfo as $aPhoto){
				//$priority = 0;
				if (empty($photo_primary) && !empty($aPhoto['thumb_small_url']) ){
					//第一个为小图的作为 的主图
					$photo_primary  = $aPhoto['thumb_small_url'];
				}
				
				// 当source_url 和 small url 不一样 才设置为其他图片
				if (!empty($aPhoto['source_url']) &&  ($photo_primary !=  $aPhoto['source_url'])  ){
					$photo_others [] = $aPhoto['source_url'];
				}
				
			}
			
			if (!empty($photo_primary)){
				
				//其他图片 不为空的 则保存
				if (!empty($photo_others)){
					PhotoHelper::savePhotoByUrl($row['sku'], $photo_primary, $photo_others);
				}
				echo "<br> ".$row['sku'].' '.$photo_primary;
				//主图不为空 ,保存为商品的主图信息
				Product::updateAll(['photo_primary'=>$photo_primary],['sku'=>$row['sku']]);
			}
		}
	}


	/**
	 * +----------------------------------------------------------
	 * 导入商品默认（首选）采购成本及额外成本
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	array
	 *+----------------------------------------------------------
	 * @return 	操作结果Array
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2015/03/27				初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function importProductCostData($data){
		$rtn['success'] = true;
		$rtn['message'] = '';
		$errMsg = '';
		$journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ ,array($data));
		
		foreach ( $data as $index => $item ) {
			$sku = trim($item['sku']);
			//update to pd_product
			$pd = Product::findOne($sku);
			if(!empty($pd)){
				$purchase_price = floatval($item['purchase_price']);
				$additional_cost = floatval($item['additional_cost']);
				$transaction = Yii::$app->get('subdb')->beginTransaction();
				$pd->purchase_price = $purchase_price;
				$pd->additional_cost = $additional_cost;
				$pd->supplier_id = empty($pd->supplier_id) ? 0 : $pd->supplier_id;
				if(!$pd->save(false)){
					$rtn['success'] = false;
					$rtn['message'] .= '商品'.$sku.'采购价修改失败;<br>';
					$transaction->rollBack();
					$errMsg .= print_r($pd->getErrors(),true);
					SysLogHelper::SysLog_Create('Catalog',__CLASS__, __FUNCTION__,'error',print_r($pd->getErrors(),true));
					continue;
				}
				//update to pd_product_supplier when update to pd_product successed
				$pdSupplier = ProductSuppliers::find()->where(['sku'=>$sku])->orderBy("priority ASC")->limit(1)->offset(0)->One();
				if(!empty($pdSupplier)){
					//如果商品已经设置过供应商信息，则更新供应商采购价
					$pdSupplier->purchase_price = $purchase_price;
					if(!$pdSupplier->save()){
						$errMsg .= print_r($pdSupplier->getErrors(),true);
						$transaction->rollBack();
						SysLogHelper::SysLog_Create('Catalog',__CLASS__, __FUNCTION__,'error',print_r($pdSupplier->getErrors(),true));
						$rtn['success'] = false;
						$rtn['message'] .= '商品'.$sku.'首选供应商采购价修改失败;<br>';
						continue;
					}
				}
				else{
					$pdSupplier = new ProductSuppliers();
					$pdSupplier->purchase_price = $purchase_price;
					$pdSupplier->supplier_id = 0;
					$pdSupplier->priority = 0;
					$pdSupplier->sku = $sku;
					$pdSupplier->save();
				}
				$transaction->commit();
			}else{
				$rtn['message'] .= '商品'.$sku.'未在商品模块建立，跳过该修改;<br>';
			}
		}
		
		$rtn['errMsg'] = $errMsg;
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		
		return $rtn;
	}
	
	/**
	 * 通过OMS传入的产品信息新建普通产品到用户商品库
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	(string,required)$sku							商品sku
	 * @param	(string,required)$uid							用户id
	 * @param	(string,required)$name							商品名称
	 * @param	(string,required)$prod_name_ch					商品中文配货名
	 * @param	(string,required)$prod_name_en					商品英文配货名
	 * @param	(string)$photo_primary							商品图片
	 * @param	(string,required)$declaration_ch				商品中文报关名，default:'礼品'
	 * @param	(string,required)$declaration_en				商品英文报关名，default:'gift'
	 * @param	(string,required)$declaration_value_currency	报关币种
	 * @param	(string)$declaration_value						报关价格
	 * @param	(string)$prod_weight							商品重量，default：50
	 * @param 	(string)$battery								是否含有锂电池，default：'N'
	 * @param  	(string,required)$platform						来源平台
	 * @param  	(string)$itemId									来源平台item id
	 * @param	(string)$type									商品类型S=普通,B=捆绑,C=变参		
	 * @param  	(array)$other_attr								其他商品属性,e.g. array(
	 * 																'brand'=>'apple',
	 * 																'other_photos'=>array(0=>'/02.jpg',1=>'/03.jpg'.....),
	 * 																'aliases'=>array(0=>['alias_sku'=>'assku1','pack'=>'1','forsite'=>'amazon','comment'=>],1=>[]....),
	 * 																'product_field'=>array('field_name1'=>'field_value1','field_name2'=>'field_value2'....),
	 * 																'tags'=>array('tag1','tag2','tag3'.....),
	 * 															)
	 * @param	(string)$declaration_code						报关编码
	 *+----------------------------------------------------------
	 * @return  array(
	 * 				'success'=true/false,//成功新建或者不需要新建时为true；否则为false。
	 * 				'message'=message,//成功为新建''；否则为提示，或errorMessage。
	 * 				'created'=true/false,//成功新建为true；否则为false。
	 * 			)
	 *+----------------------------------------------------------
	 * log			name	date			note
	 * @author 		lzhl 	2015/07/18		初始化
	 * @editor 		lkh 	2016/07/05		全局变量
	 */
	static public function creatSimpleProductFromOMS($sku,$uid,$name,$prod_name_ch,$prod_name_en,$photo_primary='',$declaration_ch='礼品',$declaration_en='gift',$declaration_value_currency,$declaration_value,$prod_weight=50,$battery='N',$platform,$itemId='',$type='S',$other_attr=[],$declaration_code='')
	{
		global $CACHE;
		$result['success']=true;
		$result['message']='';
		$result['created']=false;
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		//$journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ , array($sku,$name,$prod_name_ch,$prod_name_en,$photo_primary,$declaration_en,$declaration_value_currency,$declaration_value,$platform,$itemId));
		$rootAlias = ProductHelper::getRootSkuByAlias($sku);
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		if(!empty($rootAlias)){
			$result['message']=$sku.'是商品'.$rootAlias.'的别名，无需新建';
			return $result;
		}
		//2016-07-05  为后台检测订单加上global cache 的使用方法 start
		if (isset($CACHE[$uid]['product'][$sku])){
			$model = $CACHE[$uid]['product'][$sku];
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'product has cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}else{
			$model = Product::findOne($sku);
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'product no cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		//2016-07-05  为后台检测订单加上global cache 的使用方法 end
		if($model<>null){
			$result['message']='商品库已经存在'.$sku.'，无需新建';
			return $result;
		}else{
			$transaction = \Yii::$app->get('subdb')->beginTransaction();
			$model = new Product();
			$model_attr = $model->getAttributes();
			
			$model->sku = (string)$sku;
			$model->is_has_alias = 'N';
			$model->name = $name;
			$model->type = $type;
			$model->status = 'OS';
			$model->prod_name_ch = $prod_name_ch;
			$model->prod_name_en = $prod_name_en;
			$model->declaration_ch = $declaration_ch;
			$model->declaration_en = $declaration_en;
			$model->declaration_value_currency = $declaration_value_currency;
			$model->declaration_value = $declaration_value;
			$model->declaration_code = $declaration_code;
			$model->prod_weight = $prod_weight;
			$model->battery = $battery;
			$model->brand_id = 0;
			$model->is_has_tag = 'N';
			$model->purchase_by = 0;
			$model->photo_primary = empty($photo_primary)?'http://v2.littleboss.com/images/batchImagesUploader/no-img.png':$photo_primary;
			$model->comment = '自动创建'.$platform.'商品,item_id:'.$itemId;
			$model->capture_user_id = $uid;
			$model->create_time = date('Y-m-d', time());
			$model->update_time = date('Y-m-d', time());
			$model->create_source = $platform;
			
			//记录在pd_product以外的商品信息
			//pd_attributes
			//pd_brand
			//pd_photo
			//pd_product_aliases
			//pd_product_field
			//pd_product_field_value
			//pd_product_suppliers	//暂时不支持
			//pd_product_tags
			//pd_tag
			//echo "\n".(__function__)." ".(__line__)."\n";//test kh
			if(!empty($other_attr)){
				foreach ($other_attr as $key=>$val){
					if(empty($val))
						continue;
					switch ($key){
						case 'brand':
							$pd_brand = $val;//$pd_brand : string;
							break;
						case 'other_photo':
							$other_photos = $val;//$other_photos : array(0=>'',1=>''.....)
							break;
						case 'aliases':
							$aliases = $val;//$aliases : array(0=>['alias_sku'=>'','pack'=>'','forsite'=>'','comment'=>],1=>[]....)
							break;
						case 'product_field':
							$product_field = $val;//$product_field : array('field_name1'=>'field_value1','field_name2'=>'field_value2'....)
							break;
						case 'suppliers':
							$supplier = $val;//oms 调用 api 生成商品时，暂时只支持一个供应商信息 	//暂时不支持生成供应商信息，避免优先级混乱
							break;
						case 'tags':
							$tags = $val;//$tags : array
							break;
						default:
							if(in_array($key, $model_attr)){
								$model->setAttributes([$key => $val]);
							}
							break;
					}
				}
			}
			//echo "\n".(__function__)." ".(__line__)."\n";//test kh
			if($model->save()){
				//写入操作日志
				UserHelper::insertUserOperationLog('catalog', "新增商品, SKU: ".$model->sku."; 名称: ".$model->name);
				
				//echo "\n".(__function__)." ".(__line__)."\n";//test kh
				$result['created']=true;
				//对其他属性进行保存
				//photo:
				if(!isset($other_photos))
					$other_photos = [];
				PhotoHelper::savePhotoByUrl($model->sku, $model->photo_primary, $other_photos);//2015-09-02,liang,insert photo info to pd_photo
				//brand:
				if(isset($pd_brand)){
					$brand_info = BrandHelper::getBrandId($pd_brand,true);
					if(!empty($brand_info['success']) && !empty($brand_info['brand_id']))
						$model->brand_id = $brand_info['brand_id'];
					else {
						$result['success']=false;
						$result['message'].='商品品牌保存失败。';
						$transaction->rollBack();
						return $result;
					}
				}
				//echo "\n".(__function__)." ".(__line__)."\n";//test kh
				//添加自身别名
				if(empty($aliases)){
					$aliases = array();
				}
				$aliases[] = [
					'alias_sku' => $model->sku,
				];
				
				//aliases:
				if(isset($aliases)){
					$aliasData = [];
					if(is_string($aliases)){
						$result['success']=false;
						$result['message'] .= '别名信息格式有误;';
					}else{
						$aliases_arr = [];
						foreach ($aliases as $i=>$info){
							if(empty($info['alias_sku']))
								continue;
							if(!in_array($info['alias_sku'],$aliases_arr))
								$aliases_arr[] = $info['alias_sku'];
							else {
								$result['success']=false;
								$result['message'].='别名'.$info['alias_sku'].'重复，保存中止。请确保本次添加的别名没有重复！';
								continue;
							}
							$aliasData[$info['alias_sku']]['pack'] = empty($info['pack'])?1:$info['pack'];
							$aliasData[$info['alias_sku']]['forsite'] = empty($info['forsite'])?'':$info['forsite'];
							$aliasData[$info['alias_sku']]['comment'] = empty($info['comment'])?'':$info['comment'];
							$aliasData[$info['alias_sku']]['platform'] = '';
							$aliasData[$info['alias_sku']]['selleruserid'] = '';
						}
					}
					//echo "\n".(__function__)." ".(__line__)."\n";//test kh
					if(!empty($aliasData)){
						$add_aliases = self::addSkuAliases($model->sku, $aliasData);
						if(isset($add_aliases['success']) && $add_aliases['success']==false){
							$result['success']=false;
							$result['message'].='商品别名保存失败。';
							$transaction->rollBack();
							return $result;
						}
						if(!empty($add_aliases['success'])){
							$model->is_has_alias = 'Y';
						}
					}
				}
				//echo "\n".(__function__)." ".(__line__)."\n";//test kh
				//product_field:
				if(isset($product_field)){
					$field_data = [];
					if(is_string($product_field)){
						$result['success']=false;
						$result['message'] .= '额外属性信息格式有误;';
						$transaction->rollBack();
						return $result;
					}else{
						$field_data = [];
						foreach ($product_field as $k=>$v){
							if(!in_array($k,array_keys($field_data)))
								$field_data[$k] = $v;
							else {
								$result['success']=false;
								$result['message'].='额外属性'.$k.'重复，保存中止。请确保本次添加的额外属性没有重复！';
								$transaction->rollBack();
								return $result;
							}
						}
					}
					if(!empty($field_data)){
						$attrStr='';
						foreach ($field_data as $k=>$v){
							if(empty($attrStr))
								$attrStr = $k.':'.$v;
							else 
								$attrStr .=';'.$k.':'.$v; 
						}
						$model->other_attributes = $attrStr;
						ProductFieldHelper::updateField($attrStr);
					}
				}
				//echo "\n".(__function__)." ".(__line__)."\n";//test kh
				//suppliers:	//暂时不支持OMS直接创建供应商信息
				/*
				if(isset($supplier)){
					//$suppliers : e.g array('name'=>'','price'=>,),
					if(!empty($supplier['name']))
					$supplier_info = $supplier_id = SupplierHelper::getSupplierId($supplier['name'],true);
					if(!empty($supplier_info['success']) && !empty($supplier_info['supplier_id']) ){
						$model->supplier_id = $supplier_info['supplier_id'];
					}else{
						$result['success']=false;
						$result['message'].='商品供应商信息保存失败，保存中止。';
						$transaction->rollBack();
						return $result;
					}
					
				}
				*/
				//echo "\n".(__function__)." ".(__line__)."\n";//test kh
				//tags:
				if(!empty($tags)){
					//$tags must an array
					$tags_ret = TagHelper::updateTag($model->sku, $tags);
					if($tags_ret>0)
						$model->is_has_tag = 'Y';
				}
				if(!$model->save()){
					$transaction->rollBack();
					$result['success']=false;
					foreach ($model->errors as $k => $anError){
						$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
					}
					return $result;
				}
			}else{
				$transaction->rollBack();
				$result['success']=false;
				foreach ($model->errors as $k => $anError){
					$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
				}
				return $result;
			}
			$transaction->commit();
		}
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
		return $result;
	}
	
	
	/**
	 * 通过OMS传入的产品信息新建捆绑产品到用户商品库
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	(string,required)$sku							商品sku
	 * @param	(string,required)$uid							用户id
	 * @param	(string,required)$name							商品名称
	 * @param	(string,required)$prod_name_ch					商品中文配货名
	 * @param	(string,required)$prod_name_en					商品英文配货名
	 * @param	(string)$photo_primary							商品图片
	 * @param	(string,required)$declaration_ch				商品中文报关名，default:'礼品'
	 * @param	(string,required)$declaration_en				商品英文报关名，default:'gift'
	 * @param	(string,required)$declaration_value_currency	报关币种
	 * @param	(string)$declaration_value						报关价格
	 * @param 	(string)$prod_weight							商品重量，default：50
	 * @param 	(string)$battery								是否含有锂电池，default：'N'
	 * @param   (string,required)$platform						来源平台
	 * @param   (string)$itemId									来源平台item id
	 * @param	(string)$type									商品类型B=捆绑，S=普通
	 * @param 	(array,required)$childrens						array(assku1=>qty1,assku2=>qty2..)
	 * @param	(string)$declaration_code						报关编码
	 *+----------------------------------------------------------
	 * @return  array(
	 * 				'success'=true/false;	//成功新建或者不需要新建时为true；否则为false
	 * 				'message'=message;		//成功为新建''；否则为提示，或errorMessage
	 * 				'created'=true/false;	//成功新建为true；否则为false
	 * 				)
	 *+----------------------------------------------------------
	 * log			name	date			note
	 * @author 		lzhl 	2015/07/18		初始化
	 */
	static public function creatBundleProductFromOMS($sku,$uid,$name,$prod_name_ch,$prod_name_en,$photo_primary='',$declaration_ch='礼品',$declaration_en='gift',$declaration_value_currency,$declaration_value,$prod_weight=50,$battery='N',$platform,$itemId='',$type='B',$other_attr=[],$childrens=[],$declaration_code='')
	{
		$result['success']=true;
		$result['message']='';
		$result['created']=false;
	
		//$journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ , array($sku,$name,$prod_name_ch,$prod_name_en,$photo_primary,$declaration_en,$declaration_value_currency,$declaration_value,$platform,$itemId));
		if(empty($childrens)){
			$result['success']=false;
			$result['message']='商品'.$sku.'没有指定子产品，新建失败';
		}else{
			$childrenModels = Product::find()->where(['in','sku',array_keys($childrens)])->asArray()->all();
			if(count($childrenModels)!==count($childrens)){
				$result['success']=false;
				$result['message']='商品'.$sku.'的子产品尚未全部保存，新建失败';
			}else{
				$result = self::creatSimpleProductFromOMS($sku,$uid,$name,$prod_name_ch,$prod_name_en,$photo_primary,$declaration_ch,$declaration_en,$declaration_value_currency,$declaration_value,$prod_weight,$battery,$platform,$itemId,$type,$other_attr,$declaration_code);
				
				if(empty($result['success']) || empty($result['created'])){
					return $result;
				}else{
					$transaction = \Yii::$app->get('subdb')->beginTransaction();
					ProductBundleRelationship::deleteAll(['bdsku'=>$sku]);

					foreach ($childrens as $asSku=>$qty){
						$relationship = new ProductBundleRelationship();
						$relationship->bdsku=(string)$sku;
						$relationship->assku=(string)$asSku;
						$relationship->qty=(int)$qty;
						$relationship->create_date = date('Y-m-d H:i:s',time());
						if(!$relationship->save()){
							$result['success']=false;
							foreach ($relationship->errors as $k => $anError){
								$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
							}
						}
					}
					if($result['success']){
						$transaction->commit();
						$result['created']=true;
					}
					else {
						$transaction->rollBack();
						return $result;
					}
				}
			}
		}
		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	切换数据库
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function changeDBPuid($puid){
		if ( empty($puid))
			return false;
	
		 
	
		return true;
	}//end of changeDBPuid
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据puid将chi的商品图片信息数据信息导入 对应 的数据库
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $puid				数据库id
	 +---------------------------------------------------------------------------------------------
	 * @return						na				无返回值
	 *
	 * @invoking					ProductApiHelper::importChiProductInfo();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/8/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function importChiProductPhoto($puid){
		//1.切换数据库
		self::changeDBPuid($puid);
		$i = 1;
		//2.获取chi的图片信息
		do {
			unset($table_list);
			$sql = "select * from chi_photos_data  where photo_info <>'[]'   and sku in (select sku from pd_product) and sku not in (select sku from pd_photo) limit 200";
			$command = \Yii::$app->get('subdb')->createCommand($sql);
			$table_list = $command->queryAll();
			$message = "$i total product =".count($table_list);
			echo $message;
			self::importProductPhotoDataFromCHI($table_list);
			$i++;
		}while (!empty($table_list) && $i<200);
		unset($table_list);
	}//importChiProductPhoto
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据puid将chi的商品别名信息数据信息导入 对应 的数据库
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $puid				数据库id
	 +---------------------------------------------------------------------------------------------
	 * @return						na				无返回值
	 *
	 * @invoking					ProductApiHelper::importChiProductInfo();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/8/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function  importChiProductAlias($puid){
		//1.切换数据库
		self::changeDBPuid($puid);
		try {
			//import alias data
			$sql = "insert into pd_product_aliases  (`sku` , `alias_sku` , `pack`, `comment`)
select sku , alias , pack , 'chi批量导入'  as comment
from wm_sku_alias where  alias not in (select alias_sku from pd_product_aliases ) ";
			$command = \Yii::$app->get('subdb')->createCommand($sql);
			$reuslt = $command->execute();
			echo "<br> step 1 update ".$reuslt;
			 
			$sql = "update pd_product_aliases set forsite = 'cdiscount' where alias_sku in (select alias from wm_sku_alias where for_sites = 'cdiscount')";
			$command = \Yii::$app->get('subdb')->createCommand($sql);
			$reuslt = $command->execute();
			echo "<br> step 2 update ".$reuslt;
			 
			 
			$sql = "update pd_product_aliases set forsite = 'amazon' where alias_sku in (select alias from wm_sku_alias where for_sites like '%amazon%')";
			$command = \Yii::$app->get('subdb')->createCommand($sql);
			$reuslt = $command->execute();
			echo "<br> step 3 update ".$reuslt;
		} catch (\Exception $e) {
			echo $e->getMessage();
		}
    	
    	
    }//ImportChiProductAlias
    
    /**
     +---------------------------------------------------------------------------------------------
     * 根据puid将chi的商品基础信息数据信息导入 对应 的数据库
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param     $puid				数据库id
     +---------------------------------------------------------------------------------------------
     * @return						na				无返回值 
     *
     * @invoking					ProductApiHelper::importChiProductInfo();
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2015/8/11				初始化
     +---------------------------------------------------------------------------------------------
     **/
	static public function importChiProductInfo($puid){
	
		//1.切换数据库
		self::changeDBPuid($puid);
		$uid = $puid;
		$i = 1;
		try {
			do{
				//性能 调试 log
				$logTimeMS1 =  TimeUtil::getCurrentTimestampMS();
				$logMemoryMS1 = (memory_get_usage()/1024/1024);
				
				unset($table_list); //release memory
				//性能 调试 log
				$logTimeMS2=TimeUtil::getCurrentTimestampMS();
				$logMemoryMS2 = (memory_get_usage()/1024/1024);
				$current_time_cost = $logTimeMS2-$logTimeMS1;
				$current_memory_cost = $logMemoryMS2-$logMemoryMS1;
				echo " \n ".__FUNCTION__.' step get all  '.$i.'.1  T=('.($current_time_cost).') and M='.($current_memory_cost).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M <br>'; //test kh
				\Yii::info((__FUNCTION__)."   ,t2_1=".($current_time_cost).",memory=".($current_memory_cost)."M ","file");
				
				//2. 获取当前 没有导入 到eagle 2  数据库的商品
				$sql = "select * from wm_basic  where  cp_number not in (select sku from pd_product) limit 500";
				$command = \Yii::$app->get('subdb')->createCommand($sql);
				$table_list = $command->queryAll();
				$message = "$i total product =".count($table_list);
				\Yii::info(['Catalog',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
				
				unset($command);//release memory
				
				//性能 调试 log
				$logTimeMS3=TimeUtil::getCurrentTimestampMS();
				$logMemoryMS3 = (memory_get_usage()/1024/1024);
				$current_time_cost = $logTimeMS3-$logTimeMS2;
				$current_memory_cost = $logMemoryMS3-$logMemoryMS2;
				echo " \n ".__FUNCTION__.' step get all  '.$i.'.2  T=('.($current_time_cost).') and M='.($current_memory_cost).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M '.'<br>'; //test kh
				\Yii::info((__FUNCTION__)."   ,t2_2=".($current_time_cost).",memory=".($current_memory_cost)."M ","file");
				
				foreach($table_list as &$row){

					//性能 调试 log
					$logTimeMS4=TimeUtil::getCurrentTimestampMS();
					$logMemoryMS4 = (memory_get_usage()/1024/1024);
					/*
					$current_time_cost = $logTimeMS3-$logTimeMS2;
					$current_memory_cost = $logMemoryMS3-$logMemoryMS2;
					echo __FUNCTION__.' step get all  '.$i.'.2  T=('.($current_time_cost).') and M='.($current_memory_cost).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M '.'<br>'; //test kh
					\Yii::info((__FUNCTION__)."   ,t2_2=".($current_time_cost).",memory=".($current_memory_cost)."M ","file");
					*/
					
					$sku = $row['cp_number'];
					$name = $row['cp_name'];
					$prod_name_ch = $row['cp_name'];
					$prod_name_en = base64_decode($row['eng_name']);
					$declaration_value_currency = 'USD';
					$declaration_value = 0;
					$create_source = 'chi';
					$battery='N';
					$prod_weight=$row['cp_weight'];
					$declaration_ch='礼品';
					$declaration_en='gift';
					$prod_width=0;
					$prod_length=0;
					$prod_height=0 ;
					$photo_primary = '';
						
					$itemId='';
					//2.a.设置状态
					//“OS”:on sale “RN”:running out “DR”:dropped “AC”：archived “RS”:re-onsale'
					$chi_status_mapping = [
					'onsale'=>'OS' ,
					'drop'=>'DR' ,
					'runout'=>'RN',
					];
						
					$status=(empty($chi_status_mapping[$row['status']])?'OS':$chi_status_mapping[$row['status']]);
						
					//2.b.设置长宽高
					if (!empty($row['cp_cubage'])){
						$cubageList = explode('*',$row['cp_cubage']);
						//1*2*3是这样的格式 才设置为长宽高
						if (count($cubageList) == 3){
							$prod_width=$cubageList[0] ;
							$prod_length=$cubageList[1];
							$prod_height=$cubageList[2] ;
						}
					}
						
					/*2.c 其他属性的设置
					 * other attributes 说明
					* cgway 		采购方法
					* cp_model 	model
					* pending 		批次
					*/
					$other_attributes_ary = [];
					$other_attributes_field = ['cgway' , 'cp_model','pending','c_generalcolor'];
						
					foreach($other_attributes_field as $field_name){
		
						if (!empty($row[$field_name]) && in_array($field_name, $other_attributes_field)){
							$other_attributes_ary [$field_name] = $row[$field_name];
						}
					}
						
					$other_attributes= json_encode($other_attributes_ary);
					$message = "save product : ".$sku;
					\Yii::info(['Catalog',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
		
					$supplier_id =  '';
		
					//2.c set 设置 other attribute
					$supplier_name = $row['supplier'];
					$purchase_price = $row['cp_jj'];
		
					//调用 接口
					$result = self::createSimpleProductFromCHI($sku, $uid, $name, $prod_name_ch,$prod_name_en, $photo_primary,
							$declaration_ch,$declaration_en,$declaration_value_currency,$declaration_value,$prod_weight,$battery,
							$create_source,$itemId,$status,$prod_width, $prod_length,$prod_height , $other_attributes , $supplier_name,$purchase_price);
					
					//性能 调试 log
					$logTimeMS5=TimeUtil::getCurrentTimestampMS();
					$logMemoryMS5 = (memory_get_usage()/1024/1024);
					$current_time_cost = $logTimeMS5-$logTimeMS4;
					$current_memory_cost = $logMemoryMS5-$logMemoryMS4;
					echo " \n ".__FUNCTION__.' step '.$i.'.3   T=('.($current_time_cost).') and M='.($current_memory_cost).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M '.'<br>'.$sku; //test kh
					\Yii::info((__FUNCTION__)."   ,t2_3=".($current_time_cost).",memory=".($current_memory_cost)."M ","file");
						
				}
				$i++;
			}while (!empty($table_list) &&  $i<200);
		
			//释放内存
			unset($table_list);
			if (isset($logMemoryMS5) && isset($logTimeMS5)){
				//性能 调试 log
				$logTimeMS6=TimeUtil::getCurrentTimestampMS();
				$logMemoryMS6 = (memory_get_usage()/1024/1024);
				$current_time_cost = $logTimeMS6-$logTimeMS5;
				$current_memory_cost = $logMemoryMS6-$logMemoryMS5;
				echo " \n ".__FUNCTION__.' step get all  '.$i.'.3  T=('.($current_time_cost).') and M='.($current_memory_cost).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M '.'<br>'; //test kh
				\Yii::info((__FUNCTION__)."   ,t2_3=".($current_time_cost).",memory=".($current_memory_cost)."M ","file");
			
			}
		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}//end of importChiProductInfo
	
	/**
	 *供其他模块修改普通商品的接口
	 *##################  重要  ##########################
	 *如果不需要update的字段，请不要传该字段，否则如果为空值，会将原有值铲掉！！
	 *##################################################
	 *如果不存在，则新建
	 *@param	string		$sku
	 *@param	array(//一个或多个
	 *@param		'alias'=>array(
	 *@param			'alias_sku'=>array(alias_sku1,alias_sku2,alias_sku3..),//别名数组
	 *@param			'pack'=>array(pack1,pack2,pack3...),//别名对应的数量数组
	 *@param			'forsite'=>array(forsite1,forsite2,forsite3...),//别名对应的平台数组
	 *@param			'comment'=>array(comment1,comment2,comment3...),//别名对应的备注数组
	 *@param		),
	 *@param		'name'=>string,****Required*****
	 *@param		'type'=>'S',
	 *@param		'status'=>'OS',
	 *@param		'prod_name_ch'=>string(length 0-255),
	 *@param		'prod_name_en'=>string(length 0-255),*****Required*****
	 *@param		'declaration_ch'=>string(length 0-100) ;if empty,use 'prod_name_ch',
	 *@param		'declaration_en'=>string(length 0-100) ;if empty,use 'prod_name_en',
	 *@param		'declaration_value_currency'=>string(like 'USD','ERU'...),
	 *@param		'declaration_value=>float(10,2),
	 *@param		'battery'=>string('Y'/'N'); DEFAULT 'N',
	 *@param		'brand_name'=>string(length 0-100),
	 *@param		'tags'=>array('tag1','tag2','tag3'.....),
	 *@param		'purchase_by=>int(someone uid),
	 *@param		'prod_weight'=>int(Unit: g);DEFAULT 0,
	 *@param		'prod_width'=>int(Unit: cm);DEFAULT 0,
	 *@param		'prod_length'=>int(Unit: cm);DEFAULT 0,
	 *@param		'prod_height'=>int(Unit: cm);DEFAULT 0,
	 *@param		'other_attributes'=>string("key1:value1;key2:value2;...keyN:valueN"),
	 *@param		'photo_primary'=>string(url);DEFAULT '',
	 *@param		'supplier_name'=>string,
	 *@param		'purchase_price'=>float(10,2);DEFAULT 0,
	 *@param		'comment'=>string ;DEFAULT '',
	 *@param		'total_stockage'=>int ; DEFAULT 0,
	 *@param		'pending_ship_qty'=>int ; DEFAULT 0,
	 *@param		'create_source'=>string(商品来源网站 like: DSP=分销,ebay,amz=亚马逊) ;DEFAULT '',
	 *@param	)
	 *$return	array		array('success'=>boolean,'message'=>''/errorMseeage)
	 */
public static function modifyProductInfo($sku,$info){
		$result['success']=true;
		$result['message']='';
		
		$transaction = \Yii::$app->get('subdb')->beginTransaction();
		try{
			$model = Product::findOne($sku);
			if($model==null){
				$model = new Product();
				$model->create_time = date('Y-m-d', time());
			}
			
			$model->update_time = date('Y-m-d', time());
			
			if(empty($sku))
				return array('success'=>false,'message'=>'SKU不能为空');
			if(empty($model->sku))
				$model->sku = (string)$sku;
			
			if(isset($info['alias'])){
				$model->is_has_alias = 'N';
				if(!empty($info['alias']))
					ProductHelper::updateSkuAliases($sku, $info['alias']);
				else 
					ProductAliases::deleteAll(['sku'=>$sku]);
			}
			
			if(isset($info['name'])){
				if(!empty($info['name'])){
					if(strlen($info['name'])>255){
						return array('success'=>false,'message'=>'名称长度不能超过255');
					}else
						$model->name = (string)$info['name'];
				}else 
					$model->name = '';
			}
			
			if(isset($info['type'])){
				$model->type = (string)$info['type'];
			}
			
			if(isset($info['status'])){
				$model->type = (string)$info['status'];
			}
			
			if(isset($info['prod_name_ch'])){
				if(!empty($info['prod_name_ch'])){
					if(strlen($info['prod_name_ch'])>255){
						return array('success'=>false,'message'=>'中文名称长度不能超过255');
					}else
						$model->prod_name_ch = (string)$info['prod_name_ch'];
				}else 
					$model->prod_name_ch = '';
			}
			
			if(isset($info['prod_name_en'])){
				if(!empty($info['prod_name_en'])){
					if(strlen($info['prod_name_en'])>255){
						return array('success'=>false,'message'=>'英文名称长度不能超过255');
					}else
						$model->prod_name_en = (string)$info['prod_name_en'];
				}else
					$model->prod_name_en='';
			}
			
			if(empty($model->prod_name_en))
				return array('success'=>false,'message'=>'必须设定英文名称');
			
			if(isset($info['declaration_ch'])){
				if(!empty($info['declaration_ch'])){
					if(strlen($info['declaration_ch'])>100){
						return array('success'=>false,'message'=>'报关中文名长度不能超过255');
					}else
						$model->declaration_ch = (string)$info['declaration_ch'];
				}else
					$model->declaration_ch = '';
			}
			
			if(isset($info['declaration_en'])){
				if(!empty($info['declaration_en'])){
					if(strlen($info['declaration_en'])>100){
						return array('success'=>false,'message'=>'报关英文名长度不能超过255');
					}else
						$model->declaration_en = (string)$info['declaration_en'];
				}else
					$model->declaration_en = '';
			}
			
			if(isset($info['declaration_value_currency'])){
				$model->declaration_value_currency = $info['declaration_value_currency'];
			}
			
			if(isset($info['declaration_value'])){
				$model->declaration_value = $info['declaration_value'];
			}
			
			if(isset($info['declaration_code'])){
				$model->declaration_code = $info['declaration_code'];
			}
			
			if(isset($info['battery']))
				$model->battery = $info['battery'];
			
			if(!empty($info['brand_name'])){
				$brand_id = BrandHelper::getBrandId($info['brand_name'],true);
				$model->brand_id = $brand_id;
			}else{
				$model->brand_id=0;
			}
			
			if(isset($info['tags'])){
				if(!empty($info['tags'])){
					TagHelper::productAddTag($sku, $info['tags']);
					$model->is_has_tag='Y';
				}else{
					ProductTags::deleteAll(['sku' => $sku]);
					$model->is_has_tag='N';
				}
			}
			
			if(isset($info['purchase_by']))
				$model->purchase_by=$info['purchase_by'];
			
			if(isset($info['purchase_link']))
				$model->purchase_link=$info['purchase_link'];
			
			if(isset($info['prod_weight']))
				$model->prod_weight=$info['prod_weight'];
			if(isset($info['prod_width']))
				$model->prod_width=$info['prod_width'];
			if(isset($info['prod_length']))
				$model->prod_length=$info['prod_length'];
			if(isset($info['prod_height']))
				$model->prod_height=$info['prod_height'];
	
			if(isset($info['other_attributes'])){
				if(!empty($info['other_attributes'])){
					$model->other_attributes = $info['other_attributes'];
					ProductHelper::updatePdAttributes($info['other_attributes']);
				}else{
					$model->other_attributes = '';
				}
			}
			
			if(isset($info['photo_primary'])){
				PhotoHelper::resetPhotoPrimary($sku,$info['photo_primary']);
				$model->photo_primary=$info['photo_primary'];
			}
			
			if(!empty($info['supplier_name']) && isset($info['purchase_price'])){
				$supplier = SupplierHelper::getSupplierId($info['supplier_name'],true);
				$supplier_id = $supplier['supplier_id'];
				$model->purchase_price = (int)$info['purchase_price'];
				if(!empty($model->supplier_id)){
					SupplierHelper::updateOrResortProductSupplier($sku, $supplier_id, $info['purchase_price'], $priority=0);
				}else{
					$productSuppliers=array(
							'supplier_id'=>array($supplier_id),
							'purchase_price'=>array($info['purchase_price']),
							'priority'=>0,
					);
					ProductSuppliersHelper::updateProductSuppliers($sku, array(), $productSuppliers);
				}	
			}
			
			if(!empty($info['comment']))
				$model->comment = $info['comment'];
			
			if(!empty($info['total_stockage']))
				$model->total_stockage = $info['total_stockage'];
			if(!empty($info['pending_ship_qty']))
				$model->pending_ship_qty = $info['pending_ship_qty'];
			
			if(!empty($info['create_source']))
				$model->create_source = $info['create_source'];
			
			$model->capture_user_id = \Yii::$app->user->id;
			$model->purchase_by = \Yii::$app->user->id;
			
			$journal_id = SysLogHelper::InvokeJrn_Create(
					"Catalog", __CLASS__, __FUNCTION__ , 
					array($sku,$model->name,$model->prod_name_ch,$model->prod_name_en,$model->photo_primary,$model->declaration_en,$model->declaration_value_currency,$model->declaration_value,$model->brand_id,$model->capture_user_id)
				);
			
			if($model->save()){
				$transaction->commit();
			}else{
				$transaction->rollBack();
				$result['success']=false;
				foreach ($model->errors as $k => $anError){
					$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
		} catch (\Exception $e) {
			$transaction->rollBack();
			$result['success']=false;
			$result['message']=$e->getMessage();
		}
		
		return $result;
	}//end of modifyProductInfo
	
	
	/**
	 * +----------------------------------------------------------
	 * 查找返回该 alias 对应的 root sku
	 * 如果该alias 自己就是 root sku，返回 自己(root sku)
	 * 如果该alias 没有对应sku，返回空白
	 * +----------------------------------------------------------
	 *
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku_alias			商品SKU或别名
	 *+----------------------------------------------------------
	 * @return root sku 或 空字符串
	 *+----------------------------------------------------------
	 * log			name		date					note
	 * @author 		lkh		2015/11/20					初始化
	 *+----------------------------------------------------------
	 *
	 */
	static public function getRootSKUByAlias($alias, $platform = '', $selleruserid = ''){
		global $CACHE;
		return ProductHelper::getRootSkuByAlias($alias, $platform, $selleruserid);
	}//end of getRootSKUByAlias
	
	/**
	 * 查找给出的sku,alias,name对应的的所有商品(条件允许为空)
	 * @access static
	 * @param		$parmas		array		
	 * @return		array		包括sku,photo_primary,name,prod_name_en信息
	 * @author 		lzhl		2015/12/28		初始化
	 */
	static public function getProductsBySkuAliasName($parmas){
		$sql = "select sku,photo_primary,name,prod_name_en from pd_product where 1 ";
		$bindParmValues=[];
		foreach ($parmas as $c=>$v){
			$c = strtolower($c);
			$v = trim($v);
			if($c=='sku' && $v!==''){
				$sql .= "and sku like :sku ";
				$bindParmValues[$c]=$v;
				continue;
			}
			if($c=='alias' && $v!==''){
				$sql .= "and sku in (select sku from pd_product_aliases where alias_sku like :alias)";
				$bindParmValues[$c]=$v;
				continue;
			}
			if($c=='name' && $v!==''){
				$sql .= "and name like :name ";
				$bindParmValues[$c]=$v;
				continue;
			}
		}
		
		$command = \Yii::$app->get('subdb')->createCommand($sql);
		foreach ($bindParmValues as $k=>$v){
			$command->bindValue(":$k", '%'.$v.'%', \PDO::PARAM_STR);
		}
		
		$rows = $command->queryAll();
		
		return $rows;
	}

	/**
	 * 只增加商品的SKU别名，不作删除旧别名操作
	 * @access static
	 * @param sku			需要更新的商品SKU
	 * @param aliasesList	SKU别名列表 	e.g.array(0=>['alias_sku'=>'alias_sku1','forsite'=>'ebay','pack'=>1,'comment'=>''],1=>[...],....)
	 * @return				array('success'=>boolean,'message'=>处理结果);
	 * @author		lzhl	2015/12/28			初始化
	 * @edithor     lrq     2017/04/17    
	 +----------------------------------------------------------
	 **/
	public static function addSkuAliases($sku, $aliasesList, $platform = '', $selleruserid = '')
	{
		global $CACHE;
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		$result=array('success'=>true,'message'=>'');
		if (!isset($aliasesList)){
			return array('success'=>false,'message'=>'没有添加的别名信息');
		}
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		//对应店铺名称
		$shopname = '';
		$uid = \Yii::$app->subdb->getCurrentPuid();//kh20170510 防止 getAllPlatformOrderSelleruseridLabelMap 没有uid 后台job 调用 报错
		$ret_plat = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($uid);
    	if(!empty($ret_plat) && !empty($ret_plat[$platform]) && !empty($ret_plat[$platform][$selleruserid])){
    		$shopname = $ret_plat[$platform][$selleruserid];
    	}
    	//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		$aliases = [];
		foreach ($aliasesList as $alias_sku=>$info){
			$key = $alias_sku.'_'.$platform.'_'.$selleruserid;
			
			if(!in_array($key,$aliases)){
				$aliases[] = $alias_sku;
			}
			else{
				$result['success']=false;
				$result['message'].='别名“'.$alias_sku.'”、平台“'.$platform.'”、店铺“'.$shopname.'”组合重复，保存中止。请确保本次添加的别名没有重复！';
				continue;
			}
			$aliasData[$alias_sku]['pack'] = $info['pack'];
			$aliasData[$alias_sku]['forsite'] = $info['forsite'];
			$aliasData[$alias_sku]['comment'] = $info['comment'];
		}
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		if(!$result['success']){
			return $result;
		}
		/*
		//2016-07-05  为后台检测订单加上global cache 的使用方法 start
		if (isset($CACHE[$uid]['alias'])){
			//@todo 这里需要认真测试 
			foreach($aliases as $_tmpALias){
				if (isset($CACHE[$uid]['alias'][$_tmpALias])){
					$productAliase[] = $CACHE[$uid]['alias'][$_tmpALias];
				}
			}
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' alias has cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}else{
			$productAliase = ProductAliases::find()->where(['alias_sku' => $aliases])->asArray()->all();
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' alias no cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}
		//2016-07-05  为后台检测订单加上global cache 的使用方法 end
		 * */
		//这里暂时不使用全局变量， 未确定 $aliasesList 的数据未必一定都在cache 中订单相关出来 的别名
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		$exist_alias_sku = [];   //存在非通用的配对关系
		$exist_common_alias_sku = [];   //存在通用的配对关系
		$productAliase = ProductAliases::find()->where(['alias_sku' => $aliases])->asArray()->all();
		foreach ($productAliase as $alias){
			if(($platform != '' || $selleruserid != '') && $alias['platform'] == $platform && $alias['selleruserid'] == $selleruserid){
				$result['success']=false;
				$result['message'].='已存在：'.$alias['sku'].'，别名'.$alias['alias_sku'].'，平台“'.$platform.'”，店铺“'.$shopname.'”，配对关系重复，保存中止。如需更新该别名与主SKU的关系，请到商品模块修改';
			}
			
			if($alias['platform'] != '' || $alias['selleruserid'] != ''){
				if(!in_array($alias['alias_sku'], $exist_alias_sku)){
					$exist_alias_sku[] = $alias['alias_sku'];
				}
			}
			else if($alias['platform'] == '' && $alias['selleruserid'] == ''){
				if(!in_array($alias['alias_sku'], $exist_common_alias_sku)){
					$exist_common_alias_sku[] = $alias['alias_sku'];
				}
			}
		}
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		if(!$result['success']){
			return $result;
		}

		$transaction = \Yii::$app->get('subdb')->beginTransaction ();
		
		$journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ ,array($sku,$aliasesList,$platform,$selleruserid));
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		foreach ($aliases as $a) {
			//当只存在通用的配对关系，则替换
			if(!in_array($a, $exist_alias_sku) && in_array($a, $exist_common_alias_sku)){
				$model = ProductAliases::findOne(['alias_sku'=>$alias['alias_sku'], 'platform'=>'', 'selleruserid'=>'']);
			}
			
			if(empty($model)){
				$model = new ProductAliases();
				$model->alias_sku = (String)$a;
				$model->pack = $aliasData[$a]['pack'];
				$model->forsite = $aliasData[$a]['forsite'];
				$model->comment = $aliasData[$a]['comment'];
			}
			
			$model->sku = $sku;
			
			//存在非通用的配对关系，则添加平台、店铺信息
			if(in_array($a, $exist_alias_sku)){
				$model->platform = $platform;
				$model->selleruserid = $selleruserid;
			}
			
	
			if (! $model->save()) {
				$transaction->rollBack();
				$message = "failure to save sku is ".$model->sku." and  alias_sku  is ".$model->alias_sku ." and pack  is ".$model->pack." and platform is ".$model->platform." and selleruserid is ".$model->selleruserid." and forsite is ".$model->forsite." and comment is ".$model->comment."! ".json_encode($model->errors);
				\Yii::info(['Catalog',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
				$result['success']=false;
				$result['message'].='保存'.$sku.'的别名'.$a.'失败，保存终止。E-OO1';
				return $result;
			}
		}
		//echo "\n".(__function__)." ".(__line__)."\n";//test kh
		$transaction->commit();
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
		return $result;
	}
	
	/**
	 * 删除单个别名配对关系
	 * @access static
	 +----------------------------------------------------------
	 * @param $root_sku		主SKU
	 * @param $sku          别名
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq 	  2017/03/27		初始化
	 +----------------------------------------------------------
	 **/
	public static function deleteSkuAliases($root_sku, $sku, $platform = '', $selleruserid = '')
	{
		$ret['success'] = 0;
		$ret['msg'] = '';
	
		if(empty($root_sku) || empty($sku)){
			return ['success' => 0, 'msg' => '参数不合法！'];
		}
		
		$journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ ,array($root_sku,$sku,$platform,$selleruserid));
		
		if(empty($platform) && empty($selleruserid)){
			$ali = ProductAliases::findone(['sku'=>$root_sku, 'alias_sku'=>$sku]);
		}
		else{
			$ali = ProductAliases::findone(['sku'=>$root_sku, 'alias_sku'=>$sku, 'platform'=>$platform, 'selleruserid'=>$selleruserid]);
		}
		if(!empty($ali)){
			$ali->delete();
		
			//判断别名是否存在商品库
			$model = Product::findone(['sku'=>$sku]);
			if(!empty($model)){
				//判断别名表是否为空，是则插入自身配对关系
				$model = ProductAliases::findone(['alias_sku'=>$sku]);
				if(empty($model)){
					$model = new ProductAliases();
					$model->sku = $sku;
					$model->alias_sku = $sku;
					$model->pack = 1;
					$model->platform = '';
					$model->selleruserid = '';
					$model->comment = '';
		
					if (! $model->save()) {
						$message .= "failure to add alone_match_alias is ".$model->sku." and  alias_sku  is ".$model->alias_sku."! ".json_encode($model->errors);
						\Yii::info(['Catalog',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
					}
				}
			}
		}
		else{
			$ret['msg'] = '配对关系不存在！';
		}
	
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $ret);
		
		$ret['success'] = 1;
		return $ret;
	}
}