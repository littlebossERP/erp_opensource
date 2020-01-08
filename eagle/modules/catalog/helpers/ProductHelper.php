<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\catalog\helpers;
use yii;
use eagle\modules\util\helpers\GetControlData;
use yii\base\Exception;
use yii\data\Pagination;

use eagle\modules\catalog\models\Product;
use eagle\modules\catalog\models\ProductAliases;
use eagle\modules\catalog\models\ProductClassification;
use eagle\modules\catalog\models\Tag;
use eagle\modules\catalog\models\Attributes;
use eagle\modules\catalog\models\ProductTags;
use eagle\modules\catalog\models\ProductSuppliers;
use eagle\modules\catalog\models\ProductConfigRelationship;
use eagle\modules\catalog\models\ProductField;
use eagle\modules\catalog\models\ProductFieldValue;
use eagle\modules\catalog\helpers\ProductFieldHelper;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\catalog\models\Brand;
use eagle\modules\purchase\models\Supplier;
use eagle\modules\inventory\models\ProductStock;
use eagle\modules\inventory\helpers\StockTakeHelper;
use eagle\modules\purchase\helpers\SupplierHelper;

use eagle\modules\listing\models\AmazonItem;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\listing\models\WishFanben;
use eagle\models\SaasWishUser;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\catalog\models\Photo;
use yii\grid\DataColumn;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\util\helpers\ExcelHelper;
use yii\db\Query;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\util\helpers\ConfigHelper;
use Qiniu\json_decode;
use eagle\modules\permission\helpers\UserHelper;
use eagle\modules\util\helpers\RedisHelper;

/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
class ProductHelper {
	
	protected static $PRODUCT_TYPE = array(
			"S" => "普通",
			"C" => "变参",
			"B" => "捆绑",
			"L" => "普通(变参子产品)"
	);
	
	protected static $EDIT_PRODUCT_LOG_COL = array(
			'name' => '商品名称',
			'prod_name_ch' => '中文配货名',
			'prod_name_en' => '英文配货名',
			'declaration_ch' => '中文报关名',
			'declaration_en' => '英文报关名',
			'declaration_value' => '报关价值',
			'declaration_code' => '报关编码',
			'prod_weight' => '重量',
	);
	
	/**
	 +----------------------------------------------------------
	 * 获取商品类型列表
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param id			商品类型Key
	 +----------------------------------------------------------
	 * @return				商品类型列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	**/
	public static function getProductType($id='')
	{
		if (is_numeric($id) or $id=="")
			return GetControlData::getValueFromArray(self::$PRODUCT_TYPE, $id);
		else
			return GetControlData::getValueFromArray(array_flip(self::$PRODUCT_TYPE), $id);
	}

	protected static $PRODUCT_STATUS = array(
			"OS" => "在售",
			"RN" => "紧缺",
			"DR" => "下架",
			"AC" => "归档",
			"RS" => "重新上架",
	);
	
	/**
	 +----------------------------------------------------------
	 * 获取商品状态列表
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param id			商品状态Key
	 +----------------------------------------------------------
	 * @return				商品状态列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	**/
	public static function getProductStatus($id='')
	{
		if (is_numeric($id) or $id=="")
			return GetControlData::getValueFromArray(self::$PRODUCT_STATUS, $id);
		else
			return GetControlData::getValueFromArray(array_flip(self::$PRODUCT_STATUS), $id);
	}

	/**
	 +----------------------------------------------------------
	 * 获取商品列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $condition	其他条件 [['like'=>value] , ['in'=>value]], ['or'=>value]]
	 * @param $sort			排序字段
	 * @param $order		排序类似 asc/desc
	 * @param $defaultPageSize	            默认每页行数
	 * @param $isOnlyPro			是否查询所有商品列表信息
	 * @param $isShowL			是否显示变参子产品
	 * @param $page			页码
	 +----------------------------------------------------------
	 * @return				商品数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/03/09				初始化
	 +----------------------------------------------------------
	 **/
	static public function getProductlist($condition , $sort , $order,$defaultPageSize=20, $isOnlyPro = false, $isShowL = false, $page = null){
		$query = Product::find();
		
		if (! empty($condition)){
			foreach($condition as $tmp_condition){
				if (isset($tmp_condition['or']))
					$query->orWhere($tmp_condition['or']);
				
				if (isset($tmp_condition['and']))
					$query->andWhere($tmp_condition['and']);
				
				if (isset($tmp_condition['orlikelist'])){
					//批量查询，各条件之间用;分割
					$condition_str = str_replace('；', ';', $tmp_condition['orlikelist']);
					$condition_list = explode(';', $condition_str);
					if(count($condition_list)>1){
							$param1='';
							foreach ($condition_list as $c){
								if(trim($c)!==''){
									if($param1=='')
										$param1.=trim($c);
									else 
										$param1.='\',\''.trim($c);	
								}
							}
							if($param1!=='')
								$param1='\''.$param1.'\'';
							$query->andWhere(" sku in ($param1) ");
					}else{
						$query->andWhere("sku like '%".$tmp_condition['orlikelist']."%'   or name like '%".$tmp_condition['orlikelist']."%'  ");
    						/*or prod_name_ch like '%".$tmp_condition['orlikelist']."%'   or prod_name_en like '%".$tmp_condition['orlikelist']."%'   
    						or declaration_ch like '%".$tmp_condition['orlikelist']."%'   or declaration_en like '%".$tmp_condition['orlikelist']."%' ");*/
					}
				}
			}
			
			if(!$isShowL){
				//查询对应的变参父商品
				$relationship = ProductConfigRelationship::find()->select('cfsku')->where(['in', 'assku', Product::find()->select('sku')->where($query->where)->andwhere("type='L'")])->asArray()->all();
				if(!empty($relationship)){
					$cfsku = array();
					foreach($relationship as $v){
						$cfsku[] = $v['cfsku'];
					}
						
					if(!empty($cfsku)){
						$query->orWhere(['sku' => $cfsku]);
					}
				}
			}
		}
		
		//查询非变参子产品SKU
		if(!$isShowL){
		    $query->andwhere("type!='L'");
		}
		
		if($isOnlyPro){
		    $Size = 3000;
		    $count = $query->count();
		    //当信息太多时，分批读取
		    if($count < $Size){
		        $list = $query->orderBy('pd_product.'.$sort.' '.$order)
		        ->asArray()
		        ->all();
		    }
		    else{
		    	$start_page = 0;
		        $list = array();
		        $batch = $count / $Size + 1;
		        
		        if(\Yii::$app->subdb->getCurrentPuid() == 13672){
		        	$start_page = 16;
		        	//$batch = 16;
		        }
		        
		        for($n = $start_page; $n < $batch; $n++){
		            $pagination = new Pagination([
		                    'page' => $n,
		            		'pageSize' => $Size,
		            		'totalCount'=> $count,
		            		'pageSizeLimit'=>[5,$Size],
		            		]);
		            
		            $r = $query->orderBy('pd_product.'.$sort.' '.$order)
		            ->offset($pagination->offset)
		            ->limit($pagination->limit)
		            ->asArray()
		            ->all();
		            
		            $list = array_merge($list, $r);
		            unset($r);
		        }
		    }
		}
		else{
		    $pagination = new Pagination([
        		'defaultPageSize' => $defaultPageSize,
        		'totalCount'=> $query->count(),
        		'pageSizeLimit'=>[5,200],
        		]);
		    
		    if(isset($page)){
		    	$pagination->page = $page;
		    }
		    
		    $data['count'] = $query->count();
		    $data['pagination'] = $pagination;
		    $list = $query->orderBy('pd_product.'.$sort.' '.$order)
		    //->joinWith(['ordPay' => function ($query) {}])
		    ->offset($pagination->offset)
		    ->limit($pagination->limit)
		    ->asArray()
		    ->all();
		}
		
		$skus = array();
		$stock_skus = array();
		foreach ($list as $l){
		    $skus[] = $l['sku'];
		    $stock_skus[] = $l['sku'];
		}
		
		//变参子产品
		$realArr = array();
		$asskus = array();
		$relationship = ProductConfigRelationship::find()->where(['cfsku'=>$skus])->asArray()->all();
		foreach($relationship as $r){
		    $realArr[$r['cfsku']][] = $r['assku'];
		    $asskus[] = $r['assku'];
		}
		$rel_pro = array();
		$pro = Product::find()->where(['sku'=>$asskus, 'type'=>'L'])->asArray()->All();
		foreach($pro as $p){
			$rel_pro[$p['sku']] = $p;
			$stock_skus[] = $p['sku'];
		}
		
		$data['data'] = array();
		foreach ($list as $p){
		    $data['data'][] = $p;
		    
		    //判断是否为变参商品，是则取其变参子产品
		    if($p['type'] == 'C'){
		        $place = count($data['data'])-1;
		        if(!empty($realArr[$p['sku']])){
		            $relationship_count = 0;
    		        foreach ($realArr[$p['sku']] as $r){
    		            if(!empty($rel_pro[$r])){
    		               $data['data'][] = $rel_pro[$r];
    		               $stock_skus[] = $r;
    		               $relationship_count++;
    		            }
    		        }
    		        $data['data'][$place]['relationship_count'] = $relationship_count + 1;
		        }
		        else{
		            $data['data'][$place]['relationship_count'] = 0;
		        }
		    }
		}
		
		unset($pro);
		unset($relationship);
		unset($list);
		
		//获取采购链接信息
		$pd_sp_list = ProductSuppliersHelper::getProductPurchaseLink($stock_skus);
		foreach ($data['data'] as &$one){
			//采购链接信息
			$one['purchase_link_list'] = '';
			if(array_key_exists($one['sku'], $pd_sp_list)){
				$one['purchase_link'] = $pd_sp_list[$one['sku']]['purchase_link'];
				$one['purchase_link_list'] = json_encode($pd_sp_list[$one['sku']]['list']);
			}
		}
		
		if(!$isOnlyPro){
    		//$pagination->totalCount = $query->count();
    		$brandList = BrandHelper::ListBrandData();
    		
    		//echo print_r($query->createCommand()->getRawSql(),true);//test kh
    		
    		$supplierList = ProductSuppliersHelper::ListSupplierData();
    		
    		//读取是否显示海外仓仓库
    		$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
    		if(empty($is_show))
    			$is_show = 0;
    		//不显示海外仓仓库
    		if($is_show == 0){
    			$warehouseList = Warehouse::find()->select(['warehouse_id', 'name'])->where(['is_active' => 'Y', 'is_oversea' => '0'])->asArray()->all();
    		}
    		else{
    			$warehouseList = Warehouse::find()->select(['warehouse_id', 'name'])->where(['is_active' => 'Y'])->asArray()->all();
    		}
    		//查询已开启的仓库
    		$warehouse = array();
    		$warehouseids = array();
    		if(!empty($warehouseList)){
    			foreach($warehouseList as $val){
    				$warehouse[$val['warehouse_id']] = $val['name'];
    				$warehouseids[] = $val['warehouse_id'];
    			}
    		}
    		
    		//查询对应库存信息
    		$stock_arr = array();
    		$stock = ProductStock::find()->select(['sku', 'warehouse_id', 'qty_in_stock'])->where(['sku' => $stock_skus, 'warehouse_id' => $warehouseids])->andWhere('qty_in_stock>0')->asArray()->all();
    		foreach($stock as $val){
    			if(array_key_exists($val['warehouse_id'], $warehouse)){
	    			$stock_arr[$val['sku']][] = [
	    				'warehouse' => $warehouse[$val['warehouse_id']],
	    				'qty_in_stock' => $val['qty_in_stock'],
	    			];
    			}
    		}
    		
    		if (empty($brandList[0]['name'])){
    			$brandList[0]['name'] = '未选择';
    		}
    		
    		if (empty($supplierList[0]['name'])){
    			$supplierList[0]['name'] = '未选择';
    		}
    		
    		//查询所有分类
    		$class_arr = array();
    		$class_list = ProductClassification::find()->asArray()->all();
    		foreach($class_list as $class){
    			$class_arr[$class['ID']] = $class['name'];
    		}
    		
    		foreach ($data['data'] as $key => $val) {
    			if (!empty($brandList[$val['brand_id']]['name'])){
    				$data['data'][$key]['brand_id']=$brandList[$val['brand_id']]['name'];
    			}
    			
    			if (!empty($supplierList[$val['supplier_id']]['name'])){
    				$data['data'][$key]['supplier_id']=$supplierList[$val['supplier_id']]['name'];
    			}
    			
    			//get product alias list
    			//$alias = ProductAliases::findall(['sku' => $val['sku']]);
    			
    			$data['data'][$key]['aliaslist'] = [];
    			
    			//get product tag list
    			//$tags = []; 
    			
    			$tmpRt = TagHelper::getOneProductTags($val['sku']);
    			$tags = $tmpRt['tags'];
    			/*
    			$tags = Tag::find()
    			->andWhere(['in' , 'tag_id',(new Query())->select(['tag_id'])->from('pd_product_tags')->where(['sku'=>$val['sku']])])
    			->asArray()
    			->All();
    			*/
    			//var_dump($tags);
    			//$tags = Yii::$app->get('subdb')->createCommand("SELECT * FROM `pd_tag` where `tag_id` in (SELECT `tag_id` FROM `pd_product_tags` where sku ='".$val['sku']."')")->queryAll();
    			$data['data'][$key]['taglist'] = $tags;
    			
    			//属性
    			$other_attributes = $data['data'][$key]['other_attributes'];
    			if(!empty($other_attributes)){
    			    $data['data'][$key]['other_attributes_arr'] = explode(';', $other_attributes);
    			}
    			else{
    			    $data['data'][$key]['other_attributes_arr'] = array();
    			}
    			
    			$data['data'][$key]['stock'] = '';
    			if($val['type'] == 'C' || $val['type'] == 'B'){
    				$data['data'][$key]['purchase_price'] = '';
    			}
    			else{
    				$data['data'][$key]['purchase_price'] = empty($data['data'][$key]['purchase_price']) ? 0 : (float)$data['data'][$key]['purchase_price'];
    				
    				//匹配库存信息
    				if(array_key_exists($val['sku'], $stock_arr)){
    					foreach ($stock_arr[$val['sku']] as $stock){
    						$data['data'][$key]['stock'] .= $stock['warehouse'].': '.$stock['qty_in_stock'].'<br>';
    					}
    					$data['data'][$key]['stock'] = rtrim($data['data'][$key]['stock'], '<br>');
    				}
    			}
    			
    			//分类
    			$data['data'][$key]['class_name'] = empty($class_arr[$data['data'][$key]['class_id']]) ? '未分类' : $class_arr[$data['data'][$key]['class_id']];
    		}
    		
    		//捆绑子产品信息
    		$data['bundleArr'] = array();
    		$asskus = array();
    		$proArr = array();
    		$relationship = ProductBundleRelationship::find()->where(['bdsku'=>$skus])->asArray()->all();
    		foreach ($relationship as $r){
    		    $asskus[] = $r['assku'];
    		}
    		$pro = Product::find()->select(['sku', 'name', 'photo_primary'])->where(['sku'=>$asskus])->asArray()->All();
    		foreach ($pro as $p){
    		    $proArr[$p['sku']] = $p;
    		}
    		foreach ($relationship as $r){
    		    if(!empty($proArr[$r['assku']])){
    		        $p = $proArr[$r['assku']];
    		        $data['bundleArr'][] = [
    		            'bdsku' => $r['bdsku'],
    		            'sku' => $p['sku'],
    		            'name' => $p['name'],
    		            'photo_primary' => $p['photo_primary'],
    		            'qty' => $r['qty'],
    		        ];
    		    }
    		}
		}
		return $data;
	}//end of getProductlist
	
	
	/**
	 +----------------------------------------------------------
	 * 保存商品信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param model		商品模型
	 * @param values	商品属性信息
	 * @param isUpdate	是更新还是新建
	 +----------------------------------------------------------
	 * @return				成功则返回true,否则返回错误信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	static public function saveProduct($model, $values, $isUpdate = false){
		ProductAliases::deleteAll(" `sku` NOT IN (SELECT `sku` FROM `pd_product` WHERE 1)");
		//检测新增的alias是否已存在
		if (isset($_POST['ProductAliases']['AliasStatus'])  ){
			$AddAliasList = [];
			foreach ($_POST['ProductAliases']['AliasStatus'] as $k => $alias_status){
				if($alias_status == 'add'){
					if(!empty($_POST['ProductAliases']['alias_sku'][$k])){
						$AddAliasList[] = $_POST['ProductAliases']['alias_sku'][$k];
					}
				}
			}
			 
			/*$result = self::checkProductAlias($model->sku, $AddAliasList);
			if ($result['status'] == 'failure'){
				return array('错误' => $result['message']);
			}*/
			/*
			//支持alias覆盖现有产品后启用下段
			if ($result['status'] == 'confirm'){
				//return array('错误' => $result['message']);
				if(isset($result['redundant'])){
					$updateAliasRelated=$result['redundant'];
				}
			}
			*/
		}else{
			
				
		}
		
		if(isset($values['Product']['sku'])){
			$values['Product']['sku']= trim($values['Product']['sku']);
		}
		if (empty($values['Product']['sku']) or (strpos($values['Product']['sku'], "\t") )!==false or (strpos($values['Product']['sku'], "\r"))!==false or (strpos($values['Product']['sku'], "\n"))!==false){
			$result['message'] = 'SKU 不能为空，不能包含制表tab符号以及换行符号';
			return array('错误' => $result['message']);
		}
		 
		//默认值支持 brand id 为空，也就是0
		if (!isset($values['Product']['brand_id']) or $values['Product']['brand_id']==null or $values['Product']['brand_id']=='')
			$values['Product']['brand_id'] = 0;
		
		//prod_weight 空的情况下 , 设置默认值 
		if (empty($values['Product']['prod_weight'])) $values['Product']['prod_weight'] = 0;
		//prod_width 空的情况下 , 设置默认值
		if (empty($values['Product']['prod_width'])) $values['Product']['prod_width'] = 0;
		//prod_length 空的情况下 , 设置默认值
		if (empty($values['Product']['prod_length'])) $values['Product']['prod_length'] = 0;
		//prod_weight 空的情况下 , 设置默认值
		if (empty($values['Product']['prod_height'])) $values['Product']['prod_height'] = 0;
		//declaration_value 空的情况下 , 设置默认值
		if (empty($values['Product']['declaration_value'])) $values['Product']['declaration_value'] = 0;
		
		//purchase_price 空的情况下 , 设置默认值
		if (empty($values['Product']['purchase_price'])) $values['Product']['purchase_price'] = 0;
		
		//商品类型默认为S 普通商品
		if (empty($values['Product']['type'])) $values['Product']['type'] = 'S';
		//商品状态默认为OS 在售
		if (empty($values['Product']['status'])) $values['Product']['status'] = 'OS';
		
		$model->total_stockage =0;
		$model->pending_ship_qty =0;
		/*
		 “dsp”:分销平台创建的产品
		“ebay”：从ebay listing转化为商品库
		“amazon”：从amazon listing转化成商品库
		空白 或者 “manual”：手工创建的商品
		“excel”：从excel 批量导入的商品
		* */
		if ( empty($values['Product']['create_source'])){
			$values['Product']['create_source'] = 'manual';
		}
		
		if( !in_array( 'battery', $model->attributes() )){
			if( isset($values['Product']['battery']) )
				unset($values['Product']['battery']);
		}
		$model->attributes = $values['Product'];
		
		$transaction = Yii::$app->get('subdb')->beginTransaction();
		
		//$current_time=explode(" ",microtime());//test liang
		//$step1_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
		//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 01 beginTransaction at time ".date('Y-m-d H:i:s', time()) ],"edb\global");//test liang
		
		try
		{
			if($isUpdate)
			{
				$model->update_time = date('Y-m-d H:i:s', time());
			}
			else
			{
				$model->create_time = date('Y-m-d H:i:s', time());
				$model->update_time = date('Y-m-d H:i:s', time());
				
				//添加自身的别名关系
				$values['ProductAliases']['alias_sku'][] = $model->sku;
				$values['ProductAliases']['pack'][] = '1';
				$values['ProductAliases']['platform'][] = '';
				$values['ProductAliases']['selleruserid'][] = '';
				$values['ProductAliases']['comment'][] = '';
				$values['ProductAliases']['AliasStatus'][] = 'add';
			}

			if ($model->purchase_by == null) {
				$model->purchase_by = 0;
			}
			
			if (isset($values['edit_class_id'])) {
				$model->class_id = $values['edit_class_id'];
			}
			
			//$isChange = false;
			if (!empty($values['ProductAliases']))
			{
				//判断是否新增或者删除别名
				/*if(!empty($values['ProductAliases']['AliasStatus'])){
					foreach ($values['ProductAliases']['AliasStatus'] as $p){
						if($p == 'add' || $p == 'del'){
							$isChange = true;
							break;
						}
					}
				}*/
			    
				//$update_result = self::updateSkuAliases($model->sku, $values['ProductAliases']);
				//if ($update_result == -1) throw new Exception("保存别名失败!");
				//throw new Exception("保存别名失败!");
				$model->is_has_alias = self::updateSkuAliases($model->sku, $values['ProductAliases'])  ? 'Y' : 'N';
			}
			else
			{
				$model->is_has_alias = 'N';
				//cleare aliases
				self::deleteAllAliases($model->sku);
				 
			}
			
			//$current_time=explode(" ",microtime());//test liang
			//$step2_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
			//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 02 end of update Sku alias , used time:".($step2_time-$step1_time) ],"edb\global");//test liang
			
			if (!empty($values['Tag']))
			{
				$model->is_has_tag = TagHelper::updateTag($model->sku, $values['Tag']['tag_name']) ? 'Y' : 'N';
			}
			else
			{
				$model->is_has_tag = 'N';
				ProductTags::deleteAll(['sku'=>$model->sku]);
			}
			
			//$current_time=explode(" ",microtime());//test liang
			//$step3_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
			//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 03 end of update Tags , used time:".($step3_time-$step2_time) ],"edb\global");//test liang
			
			
			if (!empty($values['Product']['other_attributes']))
			{
				ProductFieldHelper::updateField($values['Product']['other_attributes']);
				$model->other_attributes = $values['Product']['other_attributes'];
			}
			
			//$current_time=explode(" ",microtime());//test liang
			//$step4_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
			//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 04 end of update fields , used time:".($step4_time-$step3_time) ],"edb\global");//test liang
			
			
			$photo_primary = empty($values['Product']['photo_primary']) ? '' : $values['Product']['photo_primary'];
			$photo_others = empty($values['Product']['photo_others']) ? array() : explode('@,@', $values['Product']['photo_others']);
			PhotoHelper::savePhotoByUrl($model->sku, $photo_primary, $photo_others);
			
			//$current_time=explode(" ",microtime());//test liang
			//$step5_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
			//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 05 end of update photos , used time:".($step5_time-$step4_time) ],"edb\global");//test liang
			
			
			if (isset($values['ProductSuppliers'])) {
				$ProductSuppliersInfo = ProductSuppliersHelper::updateProductSuppliers($model->sku, $values['Product'], $values['ProductSuppliers']);
				$model->supplier_id = $ProductSuppliersInfo['supplier_id'];
				$model->purchase_price = $ProductSuppliersInfo['purchase_price'];
				//$model->purchase_link = $ProductSuppliersInfo['purchase_link'];
			}
			
			//整理平台佣金比例设置
			if(!empty($model->addi_info)){
			    $addi_info = json_decode($model->addi_info, true);
			}
			else {
			    $addi_info = [];
			}
			$addi_info['commission_per'] = [];
			if(isset($values['commission_platform']) && isset($values['commission_value'])){
				foreach($values['commission_platform'] as $key => $plat){
				    if(isset($values['commission_value'][$key])){
				        $addi_info['commission_per'][$plat] = $values['commission_value'][$key];
				    }
				}
			}
			$model->addi_info = json_encode($addi_info);
			
			//$current_time=explode(" ",microtime());//test liang
			//$step6_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
			//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 06 end of update suppliers , used time:".($step6_time-$step5_time) ],"edb\global");//test liang
			
			
			$model->capture_user_id = \Yii::$app->user->id;
			
			$edit_log = '';
			$log_key_id = '';
			$is_change_class = false;    //是否变更分类
			//记录修改日志
			$old_product = Product::findOne(['product_id' => $model->product_id]);
			if(empty($old_product)){
				$edit_log = '新增商品, SKU: '.$model->sku.'; 名称: '.$model->name;
				$is_change_class = true;
			}
			else{
				foreach (self::$EDIT_PRODUCT_LOG_COL as $col_k => $col_n){
					if($model->$col_k != $old_product->$col_k){
						if(empty($edit_log)){
							$edit_log = '修改商品, SKU: '.$model->sku;
							$log_key_id = $model->product_id;
						}
						$edit_log .= ', '.$col_n.'从"'.$old_product->$col_k.'"改为"'.$model->$col_k.'"';
					}
				}
				
				if($old_product->class_id != $model->class_id){
					$is_change_class = true;
				}
			}
			
			if( $model->save())
			{
				if(!empty($edit_log)){
					//保存操作日志
					UserHelper::insertUserOperationLog('catalog', $edit_log, null, $log_key_id);
				}
				
				//$current_time=explode(" ",microtime());//test liang
				//$step7_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
				//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 07 end of model save, used time:".($step7_time-$step6_time) ],"edb\global");//test liang
				
				/*屏蔽合并商品功能
				if (isset($_POST['ProductAliases']['alias_sku'])  ){
					
					$merge_alias_list = Product::findall($PDAliasList);
						
					foreach($merge_alias_list as $one_merge_alias){
						//update alias related data
						$updateAliasRelated = self::updateAliasRelatedData($model->sku, $one_merge_alias->sku);
						//print_r($updateAliasRelated);
					}
				}*/
				
				//当新增或者更改别名时时，更新对应待发货数量
				/*if($isUpdate == false || $isChange == true)
				{
				    WarehouseHelper::RefreshOneQtyOrdered($model->sku);
				}*/
				
				$current_time=explode(" ",microtime());//test liang
				$step8_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
				//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 08 end of update Alias Related, used time:".($step8_time-$step7_time) ],"edb\global");//test liang
			
				
				//处理,保存config子产品
				if($values['Product']['type']=='C') {
					if(empty($values['children']['sku'])) {
						$transaction->rollBack();
						return array('错误' => TranslateHelper::t('子产品信息缺失') );
					}
					else{
						if(empty($values['not_delete_cli']) || $values['not_delete_cli'] === false){
							//删除所有旧关系，并将旧子产品转换成普通类型。防止新旧子产品列表不同导致关系混乱。
							self::removeConfigRelationship($model->sku, 'cfsku');
						}
						
						foreach ($values['children']['sku'] as $index=>$child_sku){
							$childModel = Product::findOne(['sku'=>$child_sku]);
							if ($childModel ==null){
								$childModel = new Product();
								$childModel->attributes = $model->attributes;
								//print_r($model->attributes);
								//print_r($childModel->attributes);
								//exit();
								$childModel->type = 'L';
								$childModel->sku = $child_sku;
								$childModel->supplier_id = ($model->supplier_id !=null)?$model->supplier_id:0;
								$childModel->photo_primary = $values['children']['photo_primary'][$index];
								PhotoHelper::resetPhotoPrimary($childModel->sku, $childModel->photo_primary);
								
								$childModel->comment = ($model->comment !=null)?$model->comment:'';
								$childModel->check_standard = ($model->check_standard !=null)?$model->check_standard:'';
								
								if (isset($values['ProductSuppliers'])) {
									$ProductSuppliersInfo = ProductSuppliersHelper::updateProductSuppliers($childModel->sku, $values['Product'], $values['ProductSuppliers']);
									$childModel->supplier_id = $ProductSuppliersInfo['supplier_id'];
									$childModel->purchase_price = $ProductSuppliersInfo['purchase_price'];
								}
								//子产品额外属性
								$relationAttrsIds = '';
								$attrStr = $values['Product']['other_attributes'];
								if(!empty($values['children']['config_field_1'])){
									if($attrStr=='')
										$attrStr.=$values['children']['config_field_1'].":".$values['children']['config_field_value_1'][$index];
									else 
										$attrStr.= ";".$values['children']['config_field_1'].":".$values['children']['config_field_value_1'][$index];
									$relationAttrsIds .= ProductFieldHelper::getProductFieldIdByName($values['children']['config_field_1']);
								}
								if(!empty($values['children']['config_field_2'])){
									$attrStr.= ";".$values['children']['config_field_2'].":".$values['children']['config_field_value_2'][$index];
									$relationAttrsIds .=','. ProductFieldHelper::getProductFieldIdByName($values['children']['config_field_2']);
								}
								if(!empty($values['children']['config_field_3'])){
									$attrStr.= ";".$values['children']['config_field_3'].":".$values['children']['config_field_value_3'][$index];
									$relationAttrsIds .=','. ProductFieldHelper::getProductFieldIdByName($values['children']['config_field_3']);
								}
								$attrStr = ProductFieldHelper::uniqueProductFieldStr($attrStr);
								$childModel->other_attributes = $attrStr;
								ProductFieldHelper::updateField($attrStr);//新产品更新所有属性到属性表
								
								if (!empty($values['Tag']))
									$childModel->is_has_tag = TagHelper::updateTag($childModel->sku, $values['Tag']['tag_name']) ? 'Y' : 'N';
								else
									$childModel->is_has_tag = 'N';
								
								//添加自身的别名关系
								$l_alias = array();
								$l_alias['alias_sku'][] = $child_sku;
								$l_alias['pack'][] = '1';
								$l_alias['platform'][] = '';
								$l_alias['selleruserid'][] = '';
								$l_alias['comment'][] = '';
								$l_alias['AliasStatus'][] = 'add';
								$childModel->is_has_alias = self::updateSkuAliases($child_sku, $l_alias)  ? 'Y' : 'N';
								$childModel->class_id = empty($model->class_id) ? '0' : $model->class_id;
							}
							else{
								$childModel->type = 'L';
								$childModel->update_time = date('Y-m-d H:i:s', time());
								$childModel->photo_primary = $values['children']['photo_primary'][$index];
								PhotoHelper::resetPhotoPrimary($childModel->sku, $values['children']['photo_primary'][$index],'OR');//重置图片库里面的Primary
								
								if (!empty($values['Tag']))
									$childModel->is_has_tag = TagHelper::updateTag($childModel->sku, $values['Tag']['tag_name']) ? 'Y' : 'N';
								
								if (isset($values['ProductSuppliers'])) {
									$ProductSuppliersInfo = ProductSuppliersHelper::updateProductSuppliers($childModel->sku, $values['Product'], $values['ProductSuppliers']);
									$childModel->supplier_id = $ProductSuppliersInfo['supplier_id'];
									$childModel->purchase_price = $ProductSuppliersInfo['purchase_price'];
								}
								//更新已存在的子产品额外属性
								$relationAttrsIds='';
								$attrStr = $values['Product']['other_attributes'];
								if(!empty($values['children']['config_field_1'])){
									if($attrStr=='')
										$attrStr.=$values['children']['config_field_1'].":".$values['children']['config_field_value_1'][$index];
									else 
										$attrStr.= ";".$values['children']['config_field_1'].":".$values['children']['config_field_value_1'][$index];
									$relationAttrsIds .=','. ProductFieldHelper::getProductFieldIdByName($values['children']['config_field_1']);
								}
								if(!empty($values['children']['config_field_2'])){
									if($attrStr=='')
										$attrStr.=$values['children']['config_field_2'].":".$values['children']['config_field_value_2'][$index];
									else
										$attrStr.= ";".$values['children']['config_field_2'].":".$values['children']['config_field_value_2'][$index];
									$relationAttrsIds .=','. ProductFieldHelper::getProductFieldIdByName($values['children']['config_field_2']);
								}
								if(!empty($values['children']['config_field_3'])){
									if($attrStr=='')
										$attrStr.=$values['children']['config_field_3'].":".$values['children']['config_field_value_3'][$index];
									else
										$attrStr.= ";".$values['children']['config_field_3'].":".$values['children']['config_field_value_3'][$index];
									$relationAttrsIds .=','. ProductFieldHelper::getProductFieldIdByName($values['children']['config_field_3']);
								}
								$oldAttr = $childModel->other_attributes;
								if($oldAttr==null or $oldAttr=='')
									$oldAttr='';
								else 
									$oldAttr .= $oldAttr.";";
								$uniqueAttrStr= ProductFieldHelper::uniqueProductFieldStr($oldAttr.$attrStr);
								$childModel->other_attributes = $uniqueAttrStr;
								ProductFieldHelper::updateField($attrStr);//旧产品仅更新新属性到属性表
								$childModel->class_id = empty($model->class_id) ? '0' : $model->class_id;
							}
							if(!$childModel->save())
							{
								$transaction->rollBack();
								//echo print_r($childModel->getErrors(),true);
								return $childModel->getErrors();
							}else{//子产品保存成功时，更新变参关系
							    //记录操作日志--增加商品
							    if(empty($childModel->product_id)){
							        UserHelper::insertUserOperationLog('catalog', "新增商品, SKU: ".$childModel->sku."; 名称: ".$childModel->name);
							    }
							    
								$relationship = ProductConfigRelationship::findOne(['assku' => $childModel->sku ]);
								if ($relationship==null) $relationship = new ProductConfigRelationship;
								$relationship->cfsku = $model->sku;
								$relationship->assku = $childModel->sku;
								$relationship->config_field_ids = empty($relationAttrsIds) ? '1' : $relationAttrsIds;
								$relationship->create_date = date('Y-m-d H:i:s', time());

								if (!($relationship->save())){
									$transaction->rollBack();
									//echo print_r($relationship->getErrors(),true);
									return $relationship->getErrors();
								}
							}
						}
					}
				}
				
				//$current_time=explode(" ",microtime());//test liang
				//$step9_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
				//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 09 end of save config, used time:".($step9_time-$step8_time) ],"edb\global");//test liang
			
				
				//处理,保存Bundle子产品
				if($values['Product']['type']=='B') {
					if(empty($values['children'])) {
						$transaction->rollBack();
						return array('错误' => TranslateHelper::t('子产品信息缺失') );
					}
					else{
						//删除所有旧关系，并将旧子产品转换成普通类型。防止新旧子产品列表不同导致关系混乱。
						self::removeBundleRelationship($model->sku, 'bdsku');
				
						foreach ($values['children']['sku'] as $index=>$child_sku){
							$childModel = Product::findOne(['sku'=>$child_sku]);
							if ($childModel ==null){
								$transaction->rollBack();
								return array('错误' => TranslateHelper::t('子产品不存在') );
							}
							else{
								$childModel->update_time = date('Y-m-d H:i:s', time());
							}
							if(!$childModel->save())
							{
								$transaction->rollBack();
								echo print_r($childModel->getErrors(),true);
								return $childModel->getErrors();
							}else{//子产品保存成功时，更新捆绑关系
								$relationship = ProductBundleRelationship::findOne(['bdsku'=>$model->sku,'assku' => $childModel->sku ]);
								if ($relationship==null) $relationship = new ProductBundleRelationship;
								$relationship->bdsku = $model->sku;
								$relationship->assku = $childModel->sku;
								$relationship->qty = $values['children']['bundle_qty'][$index];
								$relationship->create_date = date('Y-m-d H:i:s', time());
								if (!($relationship->save())){
									$transaction->rollBack();
									echo print_r($relationship->getErrors(),true);
									return $relationship->getErrors();
								}
							}
						}
					}
				}
				
				//$current_time=explode(" ",microtime());//test liang
				//$step10_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
				//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 10 end of save bundle, used time:".($step10_time-$step9_time) ],"edb\global");//test liang
			
				
				$transaction->commit();
				
				//变更分类
				if($is_change_class){
					self::getProductClassCount(true);
				}
				
				//$current_time=explode(" ",microtime());//test liang
				//$step11_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
				//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"LiangTest 11 end of transaction commit, used time:".($step11_time-$step10_time) ],"edb\global");//test liang
			
				return true;
				
			}
			else {
				$transaction->rollBack();
				//echo print_r($model->getErrors(),true);
				return $model->getErrors();
				
			}
		}
		catch(Exception $e)
		{
			$transaction->rollBack();
			return array('错误' => $e->getMessage());
		}
	}//end of saveProduct
	
	/**
	 +----------------------------------------------------------
	 * 更新商品的SKU别名
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param sku			需要更新的商品SKU
	 * @param aliasesList		SKU别名列表
	 +----------------------------------------------------------
	 * @return				商品所有的SKU别名条数
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	**/
	public static function updateSkuAliases($sku, $aliasesList) 
	{
		if (!isset($aliasesList)){
			$aliasesList = array();
			return false;
		}
		
		$del_aliases = array();  //删除的别名
		$add_aliases = array();  //新增的别名
		foreach ($aliasesList['AliasStatus'] as $k => $aliastatus){
			if($aliastatus == 'add'){
				$add_aliases[] = [
					'alias_sku' => $aliasesList['alias_sku'][$k],
					'pack' => $aliasesList['pack'][$k],
					'platform' => empty($aliasesList['platform'][$k]) ? '' : $aliasesList['platform'][$k],
					'selleruserid' => empty($aliasesList['selleruserid'][$k]) ? '' : $aliasesList['selleruserid'][$k],
					'comment' => $aliasesList['comment'][$k],
				];
			}
			else if($aliastatus == 'del'){
				$del_aliases[] = [
					'alias_sku' => $aliasesList['alias_sku'][$k],
					'platform' => empty($aliasesList['platform'][$k]) ? '' : $aliasesList['platform'][$k],
					'selleruserid' => empty($aliasesList['selleruserid'][$k]) ? '' : $aliasesList['selleruserid'][$k],
				];
			}
		}
		
		//删除配对关系
		foreach ($del_aliases as $alias) {
			$ali = ProductAliases::findone(['sku'=>$sku, 'alias_sku'=>$alias['alias_sku'], 'platform'=>$alias['platform'], 'selleruserid'=>$alias['selleruserid']]);
			if(!empty($ali)){
				$ali->delete();
				
				$message = "failure to delete sku is ".$sku." and  alias_sku  is ".$alias['alias_sku'] ." and platform  is ".$alias['platform']." and selleruserid is ".$alias['selleruserid']." ! ";
				\Yii::info(['Catalog',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
				
				//判断别名是否存在商品库
				$model = Product::findone(['sku'=>$alias['alias_sku']]);
				if(!empty($model)){
					//判断别名表是否为空，是则插入自身配对关系
					$model = ProductAliases::findone(['alias_sku'=>$alias['alias_sku']]);
					if(empty($model)){
						$model = new ProductAliases();
						$model->sku = $alias['alias_sku'];
						$model->alias_sku = $alias['alias_sku'];
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
		}
		//新增配对关系
		foreach ($add_aliases as $alias) {
			$message = '';
			$model = ProductAliases::findone(['alias_sku'=>$alias['alias_sku'], 'platform'=>$alias['platform'], 'selleruserid'=>$alias['selleruserid']]);
			if(empty($model)){
				$model = new ProductAliases();
			}
			else{
				$message = "failure to before of aliasinfo is ".$model->sku." and  alias_sku  is ".$model->alias_sku ." and pack  is ".$model->pack." and platform is ".$model->platform." and selleruserid is ".$model->selleruserid." and comment is ".$model->comment."! ";
			}
			
			$model->sku = $sku;
			$model->alias_sku = $alias['alias_sku'];
			$model->pack = $alias['pack'];
			$model->platform = $alias['platform'];
			$model->selleruserid = $alias['selleruserid'];
			$model->comment = $alias['comment'];
			
			if (! $model->save()) {
				//SysLogHelper::SysLog_Create("Catalog",__CLASS__, __FUNCTION__,"","failure to save sku is ".$model->sku." and  alias_sku  is ".$model->alias_sku ." and pack  is ".$model->pack." and forsite is ".$model->forsite." and comment is ".$model->comment."! ", "trace");
				
				$message .= "failure to save sku is ".$model->sku." and  alias_sku  is ".$model->alias_sku ." and pack  is ".$model->pack." and platform is ".$model->platform." and selleruserid is ".$model->selleruserid." and comment is ".$model->comment."! ".json_encode($model->errors);
				\Yii::info(['Catalog',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
			}
		}
		
		return ProductAliases::findAll(['sku' => $sku])>0;
	}
	
	/*屏蔽，已存在重复，catalog\helpers\ProductApiHelper::addSkuAliases
	/**
	 * 只增加商品的SKU别名，不作删除旧别名操作
	 * @access static
	 * @param sku			需要更新的商品SKU
	 * @param aliasesList	SKU别名列表 	e.g.array(0=>['alias_sku'=>'alias_sku1','forsite'=>'ebay','pack'=>1,'comment'=>''],1=>[...],....)
	 * @return				array('success'=>boolean,'message'=>处理结果);
	 * @author		lzhl	2015/12/28			初始化
	 +----------------------------------------------------------
	 /
	public static function addSkuAliases($sku, $aliasesList)
	{
		$result=array('success'=>true,'message'=>'');
		if (!isset($aliasesList)){
			return array('success'=>false,'message'=>'没有添加的别名信息');
		}
		$aliases = [];
		foreach ($aliasesList as $i=>$info){
			if(!in_array($info['alias_sku'],$aliases))
				$aliases[] = $info['alias_sku'];
			else {
				$result['success']=false;
				$result['message'].='别名'.$info['alias_sku'].'重复，保存中止。请确保本次添加的别名没有重复！';
				continue;
			}
			$aliasData[$info['alias_sku']]['pack'] = $info['pack'];
			$aliasData[$info['alias_sku']]['forsite'] = $info['forsite'];
			$aliasData[$info['alias_sku']]['comment'] = $info['comment'];
		}
		if(!$result['success']){
			return $result;
		}
		
		$productAliase = ProductAliases::findAll(['sku' => $sku]);
		$existingAlias = [];
		foreach ($productAliase as $p) {
			$existingAlias[] = $p->alias_sku;
		}
		
		foreach ($aliases as $a){
			if(in_array($a,$existingAlias)){
				$result['success']=false;
				$result['message'].='别名'.$a.'与'.$sku.'已有的别名重复，保存中止。如需更新该别名与主SKU的关系，请到商品模块修改';
			}
		}
		if(!$result['success']){
			return $result;
		}

		$transaction = Yii::$app->get('subdb')->beginTransaction ();
		foreach ($aliases as $a) {
			$model = new ProductAliases();
			$model->sku = $sku;
			$model->alias_sku = $a;
			$model->pack = $aliasData[$a]['pack'];
			$model->forsite = $aliasData[$a]['forsite'];
			$model->comment = $aliasData[$a]['comment'];
				
			if (! $model->save()) {
				$transaction->rollBack();
				$message = "failure to save sku is ".$model->sku." and  alias_sku  is ".$model->alias_sku ." and pack  is ".$model->pack." and forsite is ".$model->forsite." and comment is ".$model->comment."! ".json_encode($model->errors);
				\Yii::info(['Catalog',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
				$result['success']=false;
				$result['message'].='保存'.$sku.'的别名'.$a.'失败，保存终止。E-OO1';
				return $result;
			}
		}
		$transaction->commit();
		return $result;
	}
	*/
    
    /**
	 +----------------------------------------------------------
	 * 更新商品的辅佐属性集数据表
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param strAttr		属性集的字符串列表
	 +----------------------------------------------------------
	 * @return				无
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	**/
    public static function updatePdAttributes($strAttr)
    {
    	$attrList = explode(';', $strAttr);
    	//if (count($attrList) > 0) {
	    	foreach ($attrList as $attr) 
	    	{
	    		$tmpKv = explode(':', $attr);
	    		
	    		//$attrObj = Attributes::model()->findByAttributes(array('name' => $tmpKv[0]));
	    		$attrObj = Attributes::findOne(['name'=>$tmpKv[0]]);
	    		if ($attrObj == null)
	    		{
	    			$attrObj = new  Attributes();
	    			$attrObj->name = $tmpKv[0];
	    			$attrObj->values = json_encode(array(array('v' => $tmpKv[1], 't' => 1)));
	    			$attrObj->use_count = 1;
	    			$attrObj->save();
	    		}
	    		else 
	    		{
	    			$attrObj->use_count += 1;
	    			$values = json_decode($attrObj->values, true);
	    			$isExist = false;
	
	    			for ($i = 0; $i < count($values); $i++) {
	    				if ($values[$i]['v'] == $tmpKv[1]) 
	    				{
	    					$values[$i]['t'] = $values[$i]['t'] + 1;
	    					$isExist = true;
	    				};
	    			}
	    			if (!$isExist) 
	    			{
	    				$values[] = array('v' => $tmpKv[1], 't' => 1);
	    			}
	    			$valuesCount = count($values);
	    			if ($valuesCount > 20)
	    			{
	    				$minItemIx = 0;
	    				$minT = $values[$minItemIx]['t'];
	    				for ($i = 0; $i < $valuesCount; $i++)
	    				{
	    					if($values[$i]['t'] < $minT)
	    					{
	    						$minT = $values[$i]['t'];
	    						$minItemIx = $i;
	    					}
	    				}
	    				unset($values[$minItemIx]);
	    			}
	    			
	    			$attrObj->values = json_encode($values);
	    			$attrObj->save();
	    		}
	    	}
    	//}
    }
    
    /**
     +----------------------------------------------------------
     * 更新所有
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @param sku			商品SKU
     * @param alias sku     商品别名
     +----------------------------------------------------------
     * @return			na
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh	2014/09/28				初始化
     +----------------------------------------------------------
     **/
    public static function updateAliasRelatedData($sku, $alias_sku){
    	// set up ignore update alias those table
    	//echo "<br/>enter updateAliasRelatedData<br/>";//liang test
    	$all_have_change_table = array(
    			array('TABLE_NAME'=>'dely_delivery_item','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'od_delivery_order','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'od_order_item','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'od_order_item_v2','COLUMN_NAME'=>'root_sku'),
    			array('TABLE_NAME'=>'pc_purchase_arrival_detail','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pc_purchase_arrival_reject_detail','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pc_purchase_items','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pc_purchase_suggestion','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pd_photo','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pd_product','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pd_product_aliases','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pd_product_suppliers','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pd_product_tags','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'wh_order_reserve_product','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'wh_oversea_warehouse_stock','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'wh_product_stock','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pd_product_config_relationship','COLUMN_NAME'=>'sku'),
    			array('TABLE_NAME'=>'pd_product_bundle_relationship','COLUMN_NAME'=>'sku'),
    	);
    	$need_update_list = array(
    			'dely_delivery_item',
    			'od_delivery_order' ,
    			'od_order_item' ,
    			'od_order_item_v2' ,
    			'pc_purchase_arrival_detail' ,
    			'pc_purchase_arrival_reject_detail',
    			'pc_purchase_items',
    			'pc_purchase_suggestion',
    			'pd_photo',
    			'pd_product',
    			'pd_product_aliases',
    			'pd_product_suppliers',
    			'pd_product_tags',
    			'wh_order_reserve_product',
    			'wh_oversea_warehouse_stock', 
    			'wh_product_stock',
    	        'pd_product_config_relationship',
    	        'pd_product_bundle_relationship',
    	);
    	
    	$pd_product_column_CN_mapping=array(
    			'name'=>'名称',
    			'type'=>'商品类型',
    			'status'=>'状态',
    			'prod_name_ch'=>'商品中文名',
    			'prod_name_en'=>'商品英文名',
    			'declaration_ch'=>'商品中文描述',
    			'declaration_en'=>'商品英文描述',
    			'declaration_value_currency'=>'报关币种',
    			'declaration_value'=>'报关价格',
    			'battery'=>'是否带电池',
    			'brand_id'=>'品牌id',
    			'purchase_by'=>'采购人id',
    			'prod_weight'=>'商品重量',
    			'prod_width'=>'商品宽度',
    			'prod_length'=>'商品长度',
    			'prod_height'=>'商品高度',
    			'other_attributes'=>'商品属性',
    			'photo_primary'=>'商品主图',
    			'supplier_id'=>'首选供应商id',
    			'purchase_price'=>'采购价',
    			'check_standard'=>'质检信息',
    			'comment'=>'商品备注',
    			'capture_user_id'=>'录入人id',
    			'create_time'=>'创建时间',
    			'update_time'=>'修改时间',
    			'total_stockage'=>'总库存',
    			'pending_ship_qty'=>'在途数',
    			'create_source'=>'来源',

    	);
    	 
    	$delete_list = array(
    			'pc_purchase_suggestion' ,
    			'pd_product' ,
    	        'pd_product_config_relationship' ,
    	        'pd_product_bundle_relationship' ,
    	);
    	 
    	$remove_redundant_before_update_list = array(
    			'pd_product_suppliers' ,
    			'pd_product_tags',
    			'pd_photo',
    	);
    	 
    	$remove_redundant_before_update_table_field_relation = array(
    			'pd_product_suppliers'=>'supplier_id' ,
    			'pd_product_tags'=>'tag_id',
    			'pd_photo'=>'photo_url',
    	);
    	 
    	$reorder_list = array(
    			'pd_photo' ,
    			'pd_product_suppliers' ,
    			 
    	);
    	 
    	$reorder_label_cn = array(
    			'pd_photo'=>"图片" ,
    			'pd_product_suppliers'=>"供应商" ,
    	);
    	
    	try {
    		$isChange = false;
    		
    		/*此段疑似会造成user表锁死，需要屏蔽掉，table_list暂时hardcode
    		$puid=\Yii::$app->user->identity->getParentUid();
    		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' )
    			$userBase = 'user_'.$puid;//production ;
    		else 
    			$userBase = 'user2_'.$puid;//test ;
    		//find out all contain sku table  ".Yii::app()->muser->getPuid()."
    		$sql = "select k.TABLE_NAME , k.COLUMN_NAME
			from INFORMATION_SCHEMA.columns c
			left join INFORMATION_SCHEMA.KEY_COLUMN_USAGE k on c.TABLE_NAME = k.TABLE_NAME and c.TABLE_SCHEMA = k.TABLE_SCHEMA and k.CONSTRAINT_NAME = 'PRIMARY'
			where c.COLUMN_NAME = 'sku' and c.TABLE_SCHEMA = '".$userBase."' ";
    		$command = Yii::$app->get('subdb')->createCommand($sql);
    		$table_list = $command->queryAll();
			*/
    		//init $result_all this variable mark update result
    		$result_all = array();
    		$journal_id_list = array();
    		//loop each table start
			$table_list = $all_have_change_table;
    		foreach($table_list as $table_row){
    			
    			// if table name in ignore update list then skip it
    			if (!in_array($table_row['TABLE_NAME'], $need_update_list)){
    				continue;
    			}
    			
    			//update alias's attr to root if root's attr is null
    			if(strtolower($table_row['TABLE_NAME']) == "pd_product"){
    				$root_model = Product::findOne($sku);
    				$root_attrs=[];
    				if($root_model<>null)
    					$root_attrs = $root_model->attributes;
    				
    				$alias_model = Product::findOne($alias_sku);
    				$alias_attrs=[];
    				if($alias_model<>null)
    					$alias_attrs = $alias_model->attributes;
    				$modelChanged=false;
    				$changedKey = [];
    				$changedVal = [];
    				$addComment = [];
    				foreach ($root_attrs as $key=>&$value){
    					if(empty($value) && !empty($alias_attrs[$key])){
    						$value = $alias_attrs[$key];
    						$chagedKey[]=$key;
    						$chagedVal[]=$alias_attrs[$key];
    						$keyCN = $key;
    						if(isset($pd_product_column_CN_mapping[$key]))
    							$keyCN = $pd_product_column_CN_mapping[$key];
    						$addComment[]=$keyCN.'=>'.$alias_attrs[$key];
    						$modelChanged =true;
    					}
    				}
    				if($modelChanged){
    					$root_model->attributes = $root_attrs;
    					$root_model->comment = $root_model->comment . "//由别名合并获得属性：".implode(',', $addComment);
    					$root_model->save();
    					/*为提高性能暂时屏蔽
    					$jrn_message = "合并产品别名".$alias_sku." 的 ( ".implode(',', $changedKey).") 项，值分别为 ( ".implode(',', $changedVal).") 到主sku：$sku ";
    					//write update journaled
    					$journal_id = SysLogHelper::InvokeJrn_Create("Catalog",__CLASS__, __FUNCTION__ , array('attrs' ,   $sku , $alias_sku ,$table_row['TABLE_NAME'] ,'update' , json_encode($changedKey) ,  json_encode($changedVal) , $jrn_message));
    					//mark joural id
    					$journal_id_list[] = $journal_id;
    					*/
    				}
    				
    			}
    			
    			//wh_product_stock record should merge before delete
    			if (strtolower($table_row['TABLE_NAME']) == "wh_product_stock" ){
    				//find all product stock through this alias
    				$alias_stock_list = ProductStock::find()->where(['sku'=>$alias_sku])->all();
    
    				//merge product stock start
    				foreach($alias_stock_list as $alias_stock_detail){
    					$alias_stock_detail->qty_purchased_coming = empty($alias_stock_detail->qty_purchased_coming) ? 0 : $alias_stock_detail->qty_purchased_coming;
    					$alias_stock_detail->qty_ordered = empty($alias_stock_detail->qty_ordered) ? 0 : $alias_stock_detail->qty_ordered;
    					$alias_stock_detail->qty_order_reserved = empty($alias_stock_detail->qty_order_reserved) ? 0 : $alias_stock_detail->qty_order_reserved;
    					$alias_stock_detail->qty_in_stock = empty($alias_stock_detail->qty_in_stock) ? 0 : $alias_stock_detail->qty_in_stock;
    					
    					//check the root sku stock whether exist
    					$root_stock = ProductStock::find()->where(
    							'warehouse_id=:warehouse_id and sku =:sku',
    							array(':warehouse_id'=>$alias_stock_detail->warehouse_id ,
    									':sku'=>$sku))->one();
    						
    					if (count($root_stock) > 0 ){
    						//if exist then create a stock take
    						
    						//combine two gird
    						$root_sku_grid=explode(',' , $root_stock->location_grid);
    						$alias_sku_grid=explode(',' , $alias_stock_detail->location_grid);
    						$combine_grid = $root_sku_grid;
    						foreach ($alias_sku_grid as $gird){
    							if(!in_array($gird, $root_sku_grid))
    								$combine[]=$gird;
    						}
    						$combine_grid=implode(',', $combine_grid);
    						$stock_take_data = array();
    						$product_info = array(
    							'sku'=>$sku ,
    							'qty_actual'=>$root_stock->qty_in_stock + $alias_stock_detail->qty_in_stock,
    							'location_grid'=>$combine_grid ,
    						);
    						$stock_take_data['prod'][] = $product_info;//$stock_take_data['prod']为一个二维数组
    						$stock_take_data['warehouse_id'] = $root_stock->warehouse_id;
    						$stock_take_data['create_time'] = date('Y-m-d', time());
    						$stock_take_data['comment'] = "$alias_sku 更改为 $sku 的别名，库存合并(系统自动生成)";
    						StockTakeHelper::insertStockTake($stock_take_data);
    						//modify purchase coming qty and order pending qty
    						$sql = "update wh_product_stock set qty_purchased_coming = qty_purchased_coming+".$alias_stock_detail->qty_purchased_coming." ,
    						qty_ordered = qty_ordered +".$alias_stock_detail->qty_ordered."  ,
    						qty_order_reserved = qty_order_reserved +".$alias_stock_detail->qty_order_reserved."
    						where sku =:sku and warehouse_id =:warehouse_id ";
    
    						$command = Yii::$app->get('subdb')->createCommand($sql);
    						$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
    						$command->bindValue(":warehouse_id",$root_stock->warehouse_id,\PDO::PARAM_INT);
    						$update_result = $command->execute();
    						
    						/*为提高性能暂时屏蔽
    						$jrn_message = "合并产品别名".$sku." 的  ".$root_stock->warehouse_id." 号仓库,库存 增加 ".$alias_stock_detail->qty_in_stock.",采购在在途数  增加".$alias_stock_detail->qty_purchased_coming.",待发货数增加  ".$alias_stock_detail->qty_ordered." , 订单预约数增加".$alias_stock_detail->qty_order_reserved.",影响".$update_result."记录";
    						//write update journaled
    						$journal_id = SysLogHelper::InvokeJrn_Create("Catalog",__CLASS__, __FUNCTION__ , array(json_encode($sql) ,   $sku , $alias_sku ,$table_row['TABLE_NAME'] ,'update' , $table_row['COLUMN_NAME'] ,  $update_result , $jrn_message));
    						//mark joural id
    						$journal_id_list[] = $journal_id;
    						*/
    
    						//delete redundant record
    						$sql ="delete from wh_product_stock where sku =:sku and warehouse_id =:warehouse_id " ;
    						$command = Yii::$app->get('subdb')->createCommand($sql);
    						$command->bindValue(":sku",$alias_sku,\PDO::PARAM_STR);
    						$command->bindValue(":warehouse_id",$alias_stock_detail->warehouse_id,\PDO::PARAM_INT);
    						$delete_result = $command->execute();
    						
    						/*
    						$jrn_message = "合并产品别名".$sku." 删除产品".$alias_sku."库存 , 影响".$delete_result."条记录"  ;
    						//write update journaled
    						$journal_id = SysLogHelper::InvokeJrn_Create("Catalog",__CLASS__, __FUNCTION__ , array(json_encode($alias_stock_detail) ,   $sku , $alias_sku ,$table_row['TABLE_NAME'] ,'delete' , $table_row['COLUMN_NAME'] ,  $delete_result,$jrn_message));
    						//mark joural id
    						$journal_id_list[] = $journal_id;
    						*/
    						$isChange = true;
    
    					}else{
    						//if not exist then update stock
    						$sql = "update wh_product_stock set sku =:sku where sku =:alias_sku and warehouse_id =:warehouse_id ";
    						$command = Yii::$app->get('subdb')->createCommand($sql);
    						$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
    						$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    						$command->bindValue(":warehouse_id",$alias_stock_detail->warehouse_id,\PDO::PARAM_INT);
    						$update_result = $command->execute();
    
    						/*为提高性能暂时屏蔽
    						//write update journaled
    						$journal_id = SysLogHelper::InvokeJrn_Create("Catalog",__CLASS__, __FUNCTION__ , array(json_encode($sql) ,   $sku , $alias_sku ,$table_row['TABLE_NAME'] ,'update' , $table_row['COLUMN_NAME'] ,  $update_result));
    						//mark joural id
    						$journal_id_list[] = $journal_id;
    						*/
    						$isChange = true;
    					}
    						
    				}//end of merge product loop
    
    				if (! empty($journal_id_list))
    					$result_all[$table_row['TABLE_NAME']] = json_encode($journal_id_list);
    
    				//merge product stock end
    				 
    			}else if (strtolower($table_row['TABLE_NAME']) == "od_order_item_v2" ){
    				$sql = "select ".$table_row['COLUMN_NAME']." from  ".$table_row['TABLE_NAME']." where root_sku = :alias_sku ";
    				
    				$command = Yii::$app->get('subdb')->createCommand($sql);
    				$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    				$root_query_result = $command->queryAll();
    				
    				//Existing data which need updated
    				if (count($root_query_result)>0){
    					//change alias to sku
    					$sql = "update  ".$table_row['TABLE_NAME']." set root_sku = :sku  where root_sku = :alias_sku ";
    					$command = Yii::$app->get('subdb')->createCommand($sql);
    					$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
    					$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    					$update_result = $command->execute();
    					$isChange = true;
    				}
    			}else if (in_array($table_row['TABLE_NAME'], $delete_list)){
    				//**********************  Process delete start **********************//
    				$col_name = 'sku';
    			    if (strtolower($table_row['TABLE_NAME']) == "pd_product_config_relationship" || strtolower($table_row['TABLE_NAME']) == "pd_product_bundle_relationship" ){
    				    $col_name = 'assku';
    			    }
    			    
    			    $sql = "select * from  ".$table_row['TABLE_NAME']." where ".$col_name." =:alias_sku ";
    
    				$command = Yii::$app->get('subdb')->createCommand($sql);
    				$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    				$delete_data = $command->queryAll();
    				
    				if (count($delete_data)>0){
    					$sql = "delete from ".$table_row['TABLE_NAME']." where ".$col_name." =:alias_sku ";
    					$command = Yii::$app->get('subdb')->createCommand($sql);
    					$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    					$delete_result = $command->execute();
    					
    					/*为提高性能暂时屏蔽
    					$jrn_message =  "合并产品别名".$sku."： 删除产品".$alias_sku."记录, 影响".$delete_result."条记录";
    					//write update journaled
    					$journal_id = SysLogHelper::InvokeJrn_Create("Catalog",__CLASS__, __FUNCTION__ , array(json_encode($delete_data) ,   $sku , $alias_sku ,$table_row['TABLE_NAME'] ,'delete' , $table_row['COLUMN_NAME'] ,  $delete_result,$jrn_message));
    					 
    					//mark joural id
    					$result_all[$table_row['TABLE_NAME']] = $journal_id;
    					*/
    					$isChange = true;
    				}
    				//**********************  Process delete  end  **********************//
    				 
    			}else{
    				//**********************  Process update start **********************//
    
    				//init pk_list
    				$pk_list = array();
    
    				if (in_array($table_row['TABLE_NAME'], $remove_redundant_before_update_list)){
    					//remove redundant data before update
    					 
    					$sql = "select * from ".$table_row['TABLE_NAME']." where sku =:alias_sku and
	    				".$remove_redundant_before_update_table_field_relation[$table_row['TABLE_NAME']]." in (
	    				select ".$remove_redundant_before_update_table_field_relation[$table_row['TABLE_NAME']]." from ".$table_row['TABLE_NAME']."
	    				where sku =:sku )";
    					$command = Yii::$app->get('subdb')->createCommand($sql);
    					$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    					$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
    					$delete_data = $command->queryAll();
    					
    					if (count($delete_data)>0){
    						foreach ($delete_data as $a_del_data){
    							$del_pk_list[] = $a_del_data[$remove_redundant_before_update_table_field_relation[$table_row['TABLE_NAME']]];
    						}
    						$sql = "delete from ".$table_row['TABLE_NAME']." where sku =:alias_sku and
		    				".$remove_redundant_before_update_table_field_relation[$table_row['TABLE_NAME']]." in ('".implode("','",$del_pk_list)."'
		    				)";
    						 
    						$command = Yii::$app->get('subdb')->createCommand($sql);
    						$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    						$delete_result = $command->execute();
    
    						/*为提高性能暂时屏蔽
    						$jrn_message =  "合并产品别名".$sku."： 删除重复".$alias_sku."记录, 影响".$delete_result."条记录";
    						//write update journaled
    						$journal_id = SysLogHelper::InvokeJrn_Create("Catalog",__CLASS__, __FUNCTION__ , array(json_encode($delete_data) ,   $sku , $alias_sku ,$table_row['TABLE_NAME'] ,'delete' , $table_row['COLUMN_NAME'] ,  $delete_result,$jrn_message));
    						*/
    					}
    					
    					if (in_array($table_row['TABLE_NAME'], $reorder_list)){
    						//reset photo sort
    						$sql = "select max(priority)+1 from ".$table_row['TABLE_NAME']." where sku = :sku ";
    						$command = Yii::$app->get('subdb')->createCommand($sql);
    						$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
    						$max_value = $command->queryScalar();
    						if ($max_value > 0 ){
    							$sql = "update ".$table_row['TABLE_NAME']." set priority = priority + $max_value where sku = :alias_sku  ";
    							$command = Yii::$app->get('subdb')->createCommand($sql);
    							$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    							$update_result = $command->execute();
    							/*为提高性能暂时屏蔽
    							$jrn_message =  "合并产品别名  将产品".$alias_sku." ".$reorder_label_cn[$table_row['TABLE_NAME']]."顺序重新排序".$update_result."条记录";
    							//write update journaled
    							$journal_id = SysLogHelper::InvokeJrn_Create("Catalog",__CLASS__, __FUNCTION__ , array($pk_list , $sku,  $alias_sku , $table_row['TABLE_NAME'], 'update' , $table_row['COLUMN_NAME'] ,$update_result,$jrn_message));
    							*/
    						}
    					}
    				}
    
    				//find all pk value
    				$sql = "select ".$table_row['COLUMN_NAME']." from  ".$table_row['TABLE_NAME']." where sku = :alias_sku ";
    
    				$command = Yii::$app->get('subdb')->createCommand($sql);
    				$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    				$pk_query_result = $command->queryAll();

    				//Existing data which need updated
    				if (count($pk_query_result)>0){
    					foreach($pk_query_result as $a_pk){
    						$pk_list[] = $a_pk[$table_row['COLUMN_NAME']];
    					}
    					//change alias to sku
    					$joinstr = implode("','",$pk_list );
    					$sql = "update  ".$table_row['TABLE_NAME']." set sku = :sku  where sku = :alias_sku and ".$table_row['COLUMN_NAME']." in ('".$joinstr."') ";
    					$command = Yii::$app->get('subdb')->createCommand($sql);
    					$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
    					$command->bindValue(":alias_sku",$alias_sku,\PDO::PARAM_STR);
    					$update_result = $command->execute();
    					
    					/*为提高性能暂时屏蔽
    					$jrn_message =  "合并产品别名  将产品".$alias_sku."改成 ".$sku." , 影响".$update_result."条记录";
    					//write update journaled
    					$journal_id = SysLogHelper::InvokeJrn_Create("Catalog",__CLASS__, __FUNCTION__ , array($pk_list , $sku,  $alias_sku , $table_row['TABLE_NAME'], 'update' , $table_row['COLUMN_NAME'] ,$update_result,$jrn_message));
    					 
    					//mark joural id
    					$result_all[$table_row['TABLE_NAME']] = $journal_id;
    					*/
    					$isChange = true;
    				}else{
    					// none of data should be update
    					$result_all[$table_row['TABLE_NAME']] = "";
    				}
    				//**********************   Process update end    **********************//
    			}//end of delete process
    			
    		}//loop each table end
    		if ($isChange){
    			/*为提高性能暂时屏蔽
    			$journal_id = SysLogHelper::InvokeJrn_Create("Catalog",__CLASS__, __FUNCTION__ , array($result_all));
    			SysLogHelper::SysLog_Create("Catalog",__CLASS__, __FUNCTION__,"","$journal_id : Update Alias Related data ! ", "trace");
    			*/
    		}

    	} catch (Exception $e) {
    		return array('错误' => array($e->getMessage()));
    	}
    	 
    }//end of updateAliasRelatedData
    
    
    /**
     * +----------------------------------------------------------
     * 删除某商品的所有别名
     * +----------------------------------------------------------
     * @access static
     * +----------------------------------------------------------
     * @param sku 			商品SKU
     * +----------------------------------------------------------
     * @return			boolean
     * +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh	  2014/10/23				初始化
     * +----------------------------------------------------------
     */
    static function deleteAllAliases($sku){
    	$aliasesList = ProductAliases::findall(['sku'=>$sku]);
    	if ( count($aliasesList )> 0){
    		foreach($aliasesList as $a){
    			$a->delete();
    		}
    		return true;
    	}else{
    		return true;
    	}
    }//end of deleteAllAliases
    
    public static function getFormOpt(){
    
    	$criteria = new CDbCriteria();
    	$criteria->order = "level asc";
    	$categorys = Category::model()->findAll($criteria);
    	$tags = Tag::model()->findAll();
    	$brands = Brand::model()->findAll();
    
    	$result = array();
    	$result['categorys'] = $categorys;
    	$result['tags'] = $tags;
    	$result['brands'] = $brands;
    	$result['suppliers'] = ProductHelper::getProductSuppliers();
    	$result['productTopSupplierInfo'] = SupplierHelper::getProductTopSupplierInfo();
    	$result['productType'] = ProductHelper::getProductType();
    	$result['productStatus'] = ProductHelper::getProductStatus();
    	$result['currency'] = CommonHelper::getCurrencyList();
    
    	return json_encode($result);
    }
    
    /**
     +----------------------------------------------------------
     * 获取商品列表数据
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @param page			当前页
     * @param rows			每页行数
     * @param sort			排序字段
     * @param order			排序类似 asc/desc
     * @param queryString	其他条件
     +----------------------------------------------------------
     * @return				商品数据列表
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/02/27				初始化
     +----------------------------------------------------------
     **/
    public static function listData($page, $rows, $sort, $order, $queryString, $formatJson = true)
    {
    	$AndSql = ""; // init
    	$selectType ='';
    	if(!empty($queryString))
    	{
    		foreach($queryString as $query)
    		{
    			if($query['name']=='type'&& $query['value']=='all'){
    				$selectType ='all';
    				continue;
    			}
    			if ($query['condition'] == 'eq')
    			{
    				$AndSql .= " and ".$query['name']." ='".$query['value']."'";
    			}
    			elseif ($query['condition'] == 'in')
    			{
    				if ($query['name'] == 'alias_sku'){
    					$AndSql .= " and sku in (select sku from pd_product_aliases where alias_sku like '%".$query['value']."%') ";
    				}else{
    					$AndSql .= " and ".$query['name']." in (".$query['value'].")";
    				}
    					
    			}
    			elseif ($query['condition'] == 'notIn')
    			{
    				$AndSql .= " and ".$query['name']." not in (select sku from pd_product_aliases where alias_sku like '%".$query['value']."%') ";
    			}
    			elseif ($query['condition'] == 'like')
    			{
    				$AndSql .= " and ".$query['name']." like '%".$query['value']."%' ";
    			}
    			elseif ($query['condition'] == 'gt')
    			{
    				$AndSql .= " and ".$query['name']." >  '".$query['value']."' ";
    			}
    			elseif ($query['condition'] == 'lt')
    			{
    				$AndSql .= " and ".$query['name']." <  '".$query['value']."' ";
    			}
    			elseif ($query['condition'] == 'between')
    			{
    				$AndSql .= " and ".$query['name']." between  '".$query['valueStart']."' and '".$query['valueEnd']."' ";
    			}
    		}
    	}
    
    	$sql = "select * from pd_product where 1 = 1 ";
    	$command = Yii::$app->get('subdb')->createCommand("select count(1) ct from ($sql $AndSql) a ");
    
    	$result['total'] = $command->queryScalar();
    	//$command->limit = $rows;
    	//$command->offset = ($page-1) * $rows;
    	//$command->order = "$sort $order";//排序条件
    	$sql .=$AndSql;
    	if($selectType ==''){
    		$sql .=" and type in ('S','L')";//限制查询类型
    	}
    	$sql .=" order by $sort $order ";
    	$sql .=" limit ".($page-1) * $rows." , ".$page * $rows;
    	$command = Yii::$app->get('subdb')->createCommand($sql);
    
    	$result['rows'] = $command->queryAll();
    
    	if ($formatJson) {
    		return json_encode ( $result );
		} else {
			return $result;
		}
	}
	
	/**
	 * +----------------------------------------------------------
	 * 删除商品信息
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	model		商品模型
	 *+----------------------------------------------------------
	 * @return 成功则返回true,否则返回错误信息 +----------------------------------------------------------
	 *         log			name	date					note
	 * @author ouss	2014/02/27				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function deleteProduct($sku) {
		$transaction = Yii::$app->get('subdb')->beginTransaction ();
		try {
			$del_condition = ['sku'=> $sku ];
			// del product_aliases
			ProductAliases::deleteAll($del_condition);
			
			// del product_tags
			ProductTags::deleteAll($del_condition);
			
			// del product_suppliers
			ProductSuppliers::deleteAll ( $del_condition );
			
			// del product photo
			// Photo::model()->deleteAll($criteria);
			
			// del config_relationship & change children's type
			self::removeConfigRelationship($sku, 'cfsku');
			self::removeConfigRelationship($sku, 'assku');

			/*
			$sql = "select cfsku, GROUP_CONCAT(assku) asskulist from pd_product_config_relationship where cfsku ='$sku' group by cfsku ";
			$command = Yii::$app->get('subdb')->createCommand ($sql);
			$C_relationship = $command->queryAll ();
			if (count ( $C_relationship ) > 0) {
				
				foreach ( $C_relationship as $agroup ) {
					ProductConfigRelationship::deleteAll( array ('cfsku' => $agroup ['cfsku']) );
					$asskuArray = explode ( ',', $agroup ['asskulist'] );
					if (! $asskuArray)
						$asskuArray = array (
								$agroup ['asskulist'] 
						);
					foreach ( $asskuArray as $assku ) {
						$childrenmodel = Product::findOne(['sku'=>$assku]) ;
						if ($childrenmodel !== null) {
							$childrenmodel->type = 'S';
							$childrenmodel->save ();
						}
					}
				}
			}
			*/
			
			// del bundle_relationship
			
			self::removeBundleRelationship($sku, 'bdsku');
			self::removeBundleRelationship($sku, 'assku');
			/*
			$sql = "select bdsku from pd_product_bundle_relationship where bdsku = '$sku'";
			$command = Yii::$app->get('subdb')->createCommand ($sql);
			$bundleFarter = $command->queryAll ();
			if (count ( $bundleFarter ) > 0) {
				foreach ( $bundleFarter as $agroup ) {
					ProductBundleRelationship::deleteAll ( array (
							'bdsku' => $agroup ['bdsku'] 
					) );
				}
			} // end of (if sku is bundle farter_sku)
			$sql = "select assku from pd_product_bundle_relationship where assku='$sku' ";
			$command = Yii::$app->get('subdb')->createCommand ($sql);
			$bundleChildren = $command->queryAll ();
			if (count ( $bundleChildren ) > 0) {
				foreach ( $bundleChildren as $agroup ) {
					ProductBundleRelationship::deleteAll (  array (
							'assku' => $agroup ['assku'] 
					) );
				}
			} // end of (if sku is bundle child_sku)
			*/
			  // del bundle_relationship end
			  
			// del product
			Product::deleteAll ( $del_condition );
			$transaction->commit ();
			
			return true;
		} catch ( Exception $e ) {
			$transaction->rollBack ();
			return false;
		}
	}
	
	/**
	 * +----------------------------------------------------------
	 * 获取商品模型
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku		商品SKU
	 *+----------------------------------------------------------
	 * @return 商品模型 +----------------------------------------------------------
	 *         log			name	date					note
	 * @author ouss	2014/02/27				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function getProductBySku($sku) {
		
		
		return Product::findOne(['sku'=>$sku]);
	}
	
	/**
	 * +----------------------------------------------------------
	 * 获取商品的导出数据
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @return 商品模型数组 
	 * +----------------------------------------------------------
	 *         log			name	date					note
	 * @author ouss	2014/02/27				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function getExportProductData($skuList) {
		$products = array ();
		if (count ( $skuList ) > 0) {
			$criteria = new CDbCriteria ();
			$criteria->addInCondition ( 'sku', $skuList );
			$products = Product::findAll ( $criteria );
		} else {
			$products = Product::findAll ();
		}
		$productArray = array ();
		foreach ( $products as $item ) {
			$sku = $item->sku;
			$alias = ProductAliases::findAllByAttributes ( array (
					'sku' => $sku 
			) );
			$tags = Tag::findAllBySql ( "SELECT * FROM `pd_tag` where `tag_id` in (SELECT `tag_id` FROM `pd_product_tags` where sku ='$sku')" );
			$productSuppliers = ProductSuppliers::findAllByAttributes ( array (
					'sku' => $sku 
			) );
			$photos = PhotoHelper::getPhotosBySku ( $sku, 'OR' );
			$product = $item->attributes;
			$aliasArray = array ();
			foreach ( $alias as $a ) {
				$aliasArray [] = $a->alias_sku;
			}
			$tagArray = array ();
			foreach ( $tags as $t ) {
				$tagArray [] = $t->tag_name;
			}
			$product ['alias'] = implode ( ',', $aliasArray );
			$product ['tags'] = implode ( ',', $tagArray );
			$product ['suppliers'] = json_encode ( $productSuppliers );
			$product ['photos'] = implode ( ',', $photos );
			$productArray [] = $product;
		}
		
		return $productArray;
	}
	protected static $EXCEL_PRODUCT_COLUMN_MAPPING = array (
			"A" => "sku", //SKU
			"B" => "name", // 商品名称
			"C" => "category_name", // 分类 * 需替换为id
			"D" => "brand_name", // 品牌 * 需替换为id
			"E" => "prod_name_ch", // 中文配货名称
			"F" => "prod_name_en", // 英文配货名称
			"G" => "declaration_ch", // 中文报关名
			"H" => "declaration_en", // 英文报关名
			"I" => "declaration_value",//报关价格
			"J" => "declaration_value_currency",//报关币种
			"K" => "prod_weight", // 商品重量(g)
			"L" => "prod_length", // 商品尺寸(长cm)
			"M" => "prod_width", // 商品尺寸(宽cm)
			"N" => "prod_height", // 商品尺寸(高cm)
			"O" => "supplier_name", // 首选供应商 * 需替换为id
			"P" => "purchase_price", // 采购价(CNY)
			"Q" => "photo_primary", // 主图片
			// photo_others_* 需要用逗号为分隔符join(separator,array) 到 'photo_others'列里面
			"R" => "photo_others_2", // 图片2
			"S" => "photo_others_3", // 图片3
			"T" => "photo_others_4", // 图片4
			"U" => "photo_others_5", // 图片5
			"V" => "photo_others_6", // 图片6
			"W" => "status_cn", // 产品状态 * 需替换成code
			"X" => "prod_tag",//商品标签,需要转换为is_has_tag,内容保存到'pd_prodcut_tags'里面
			"Y" => "alias",//商品别名,保存到alias表
			"Z" => "declaration_code",//报关码
			"AA"=> "purchase_link",//采购链接
	
	);
	
	protected static $SELLERTOOL_EXCEL_PRODUCT_COLUMN_MAPPING = array (
			"A" => "sku", //SKU
			"B" => "name", // 商品名称
			"C" => "prod_weight", // 商品重量(g)
			"D" => "prod_length", // 商品尺寸(长cm)
			"E" => "prod_width", // 商品尺寸(宽cm)
			"F" => "prod_height", // 商品尺寸(高cm)
			"G" => "declaration_ch", // 中文报关名
			"H" => "declaration_en", // 英文报关名
			
			
			"I" => "declaration_value",//报关价格
			"J" => "purchase_price", // 采购价(CNY)
			"K" => "prod_tag",//商品标签,需要转换为is_has_tag,内容保存到'pd_prodcut_tags'里面
			"L" => "photo_primary", // 主图片
			"Y" => "alias",//商品别名,保存到alias表
			
	);
	
	
	protected static $SELLERTOOL_EXCEL_BUNDLE_PRODUCT_COLUMN_MAPPING = array (
			"A" => "sku", //SKU
			"B" => "bundlesku", // 商品名称
	);
	
	protected static $EXCEL_PRODUCT_ENNAME_CNNAME_MAPPING = array (
	        //equal：完全相等，like：模糊字段
			"sku" => [
	                "sku"=>"equal", 
	                "SKU(必填)"=>"equal"], //SKU
			"name" => [
	                "商品名称"=>"like"], // 商品名称
			/*"category_name" => [
	                "分类"=>"like"], // 分类 * 需替换为id */
			"brand_name" => [
	                "品牌"=>"like"], // 品牌 * 需替换为id
			"prod_name_ch" => [
	                "中文配货名称"=>"like"], // 中文配货名称
			"prod_name_en" => [
	                "英文配货名称"=>"like"], // 英文配货名称
			"declaration_ch" => [
	                "中文报关名"=>"like"], // 中文报关名
			"declaration_en" => [
	                "英文报关名"=>"like"], // 英文报关名
			"declaration_value" => [
	                "申报金额"=>"like",
	                "报关价格"=>"like"],//报关价格
			"declaration_value_currency" => [
	                "申报货币"=>"like",
	                "报关币种"=>"like"],//报关币种
			"prod_weight" => [
	                "重量"=>"like"], // 商品重量(g)
			"prod_length" => [
	                "长"=>"like"], // 商品尺寸(长cm)
			"prod_width" => [
	                "宽"=>"like"], // 商品尺寸(宽cm)
			"prod_height" => [
	                "高"=>"like"], // 商品尺寸(高cm)
			"supplier_name" => [
	                "供应商"=>"like"], // 首选供应商 * 需替换为id
			"purchase_price" => [
	                "采购价"=>"like"], // 采购价(CNY)
			"photo_primary" => [
	                "主图片"=>"like"], // 主图片
			// photo_others_* 需要用逗号为分隔符join(separator,array) 到 'photo_others'列里面
			"photo_others_2" => [
	                "图片2"=>"like"], // 图片2
			"photo_others_3" => [
	                "图片3"=>"like"], // 图片3
			"photo_others_4" => [
	                "图片4"=>"like"], // 图片4
			"photo_others_5" => [
	                "图片5"=>"like"], // 图片5
			"photo_others_6" => [
	                "图片6"=>"like"], // 图片6
			"status_cn" => [
	                "产品状态"=>"like"], // 产品状态 * 需替换成code
			"prod_tag" => [
	                "标签"=>"like"],//商品标签,需要转换为is_has_tag,内容保存到'pd_prodcut_tags'里面
			"alias" => [
	                "别名"=>"like"],//商品别名,保存到alias表
			"declaration_code" => [
	                "报关编码"=>"like",
	                "报关码"=>"like"],//报关码
			"purchase_link"=> [
	                "采购链接"=>"like"],//采购链接
	        "assku_list"=> [
	                "捆绑子SKU"=>"like"],//捆绑子SKU,保存到bundle表
	        "father_sku"=> [
	            "父商品sku"=>"like"],//变参父商品SKU
	        "attribute1"=> [
	            "属性1名称"=>"like",
	            "属性名称"=>"like"],//变参SKU
	        "value1"=> [
	            "属性1取值"=>"like",
	            "属性取值"=>"like"],//变参SKU
	        "attribute2"=> [
	            "属性2名称"=>"like"],//变参SKU
	        "value2"=> [
	            "属性2取值"=>"like"],//变参SKU
	        "attribute3"=> [
	            "属性3名称"=>"like"],//变参SKU
	        "value3"=> [
	            "属性3取值"=>"like"],//变参SKU
			"class_name"=> [
				"分类"=>"like"],//分类 * 需替换为id
	
	);
	
	/**
	 * +----------------------------------------------------------
	 * 导入商品的映射关系 
	 * +----------------------------------------------------------
	 *
	 * @access static
	 *+----------------------------------------------------------
	 * @param	na
	 *+----------------------------------------------------------
	 * @return 产品导入的映射关系 
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2015/03/27				初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function get_EXCEL_PRODUCT_COLUMN_MAPPING(){
		return self::$EXCEL_PRODUCT_COLUMN_MAPPING;
	}//end of get_EXCEL_PRODUCT_COLUMN_MAPPING
	
	/**
	 * +----------------------------------------------------------
	 * 导入商品的映射关系
	 * +----------------------------------------------------------
	 *
	 * @access static
	 *+----------------------------------------------------------
	 * @param	
	 *+----------------------------------------------------------
	 * @return 产品导入的映射关系
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lrq		2016/12/22				初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function get_EXCEL_PRODUCT_ENNAME_CNNAME_MAPPING(){
		return self::$EXCEL_PRODUCT_ENNAME_CNNAME_MAPPING;
	}//end of get_EXCEL_PRODUCT_ENNAME_CNNAME_MAPPING
	
	/**
	 * +----------------------------------------------------------
	 * 赛兔商品导入的映射关系
	 * +----------------------------------------------------------
	 *
	 * @access static
	 *+----------------------------------------------------------
	 * @param	na
	 *+----------------------------------------------------------
	 * @return 产品导入的映射关系
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2016/03/16				初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function get_SELLERTOOL_EXCEL_PRODUCT_COLUMN_MAPPING(){
		return self::$SELLERTOOL_EXCEL_PRODUCT_COLUMN_MAPPING;
	}//end of get_EXCEL_PRODUCT_COLUMN_MAPPING
	
	/**
	 * +----------------------------------------------------------
	 * 赛兔虚拟商品导入的映射关系
	 * +----------------------------------------------------------
	 *
	 * @access static
	 *+----------------------------------------------------------
	 * @param	na
	 *+----------------------------------------------------------
	 * @return 产品导入的映射关系
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2016/03/16				初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function get_SELLERTOOL_EXCEL_BUNDLE_PRODUCT_COLUMN_MAPPING(){
		return self::$SELLERTOOL_EXCEL_BUNDLE_PRODUCT_COLUMN_MAPPING;
	}//end of get_EXCEL_PRODUCT_COLUMN_MAPPING
	
	/**
	 * +----------------------------------------------------------
	 * 赛兔虚拟商品sku 解释方法
	 * +----------------------------------------------------------
	 *
	 * @access static
	 *+----------------------------------------------------------
	 * @param	na
	 *+----------------------------------------------------------
	 * @return 产品导入的映射关系
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2016/03/16				初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function explodeSELLTOOLBundleProduct($bundleSKU){
		$sku_qty_list = explode('+', $bundleSKU);
		$rt = [];
		foreach($sku_qty_list as $sku_qty){
			
			$prodInfo = explode("*", $sku_qty);
			$rt[trim($prodInfo[0])] = trim($prodInfo[1]);
			
			
			
		}
		return $rt;
	}//end of explodeSELLTOOLBundleProduct
	
	/**
	 * +----------------------------------------------------------
	 * 导入商品的数据
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	file		上传文件句柄
	 *+----------------------------------------------------------
	 * @return 产品导入结果信息数组 
	 * +----------------------------------------------------------
	 *         log			name	date					note
	 * @author ouss	2014/02/27				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function importProductData($productsData, $itype='S') {
		global $CACHE;
		$result = array ();
		$result['error']='';
		$successInsertQty = 0;
		$successUpdateQty = 0;
		$failQty = 0;
		$allQty = 0;
		$edit_log = '';
		$add_log = '';
		//$productsData = ExcelHelper::importProductExcel ( $file, self::$EXCEL_PRODUCT_COLUMN_MAPPING, true );
	
		//清空无效别名映射
		ProductAliases::deleteAll(" `sku` NOT IN (SELECT `sku` FROM `pd_product` WHERE 1)");
		if (is_array ( $productsData )) {
				
			$allBrandInfo = Brand::findAll(['1'=>'1']);
			$brandNameIdMapping = array ();
			$allBrandName = array ();
				
			foreach ( $allBrandInfo as $brandInfo ) {
				$allBrandName [] = $brandInfo ['name'];
				$brandNameIdMapping [$brandInfo ['name']] = $brandInfo ['brand_id'];
			}
			
			$pdAlias = ProductAliases::find()->select("alias_sku")->where("sku!=alias_sku")->asArray()->All();
			$aliasInDb = array();
			foreach ($pdAlias as $r){
				$aliasInDb[]=$r['alias_sku'];
			}
			
			//本次导入的列
			$exportCol = array();
			if(!empty($productsData)){
			    foreach ($productsData as $p){
    			    $exportCol = array_keys($p);
    			    break;
			    }
			}
			
			$importAlias = array();//录入的alias，不重复数组
			$sameAlias = array();//excel中存在的重复alias
			$existingAlias = array();//db中已存在的alias
			$alias_EQ_importSku = array();//本次导入的alias存在导入中已存在的sku
			$alias_EQ_existingSku = array();//本次导入的alias存在db中已存在的sku

			// 导入前先检查是否存在相同 sku 的 record ，如果存在，中止导入
			$allProdSku = array ();      //导入的所有商品SKU
			$allProdSkuUp = array ();    //导入的所有商品SKU，转换为大写
			$excel_alias = array();      //excel内的别名 
			$insertSkuList=array();
			$sameSkuInfo = array ();
			$aliasOk = true;
			$notExistAssku = false;    //导入捆绑商品时，判断是否不存在assku_list列
			$is_not_sku = false;       //判断是否不存在列sku列
			$not_Exist_attribute = false;    //导入变参商品时，判断是否不存在属性、值列
			$not_Exist_father_sku = false;    //导入变参商品时，判断是否不存在父商品SKU列
			
			//必须存在sku列
			if(!in_array('sku',$exportCol)){
				$aliasOk = false;
				$is_not_sku = true;
			}
			else{
    			foreach ( $productsData as $key => &$item ) {
    				//去掉SKU中的换行符和tab
    				$item ['sku'] = str_replace('\r', '', $item ['sku']);
    				$item ['sku'] = str_replace('\n', '', $item ['sku']);
    				$item ['sku'] = str_replace('\t', '', $item ['sku']);
    				$item ['sku'] = str_replace(chr(10), '', $item ['sku']);
    				//去掉SKU中的前后空格
    				$item ['sku'] = trim($item ['sku']);
    				
    				if ($item ['sku'] != '') {
    					if (in_array ( strtoupper($item ['sku']), $allProdSkuUp )) {
    						$sameSkuInfo [$item ['sku']] [] = $key;
    					}
    					else {
    						$allProdSku [] = $item ['sku'];
    						$allProdSkuUp[] = strtoupper($item ['sku']);
    					}
    				}
    				
    				//当导入存在alias，即别名时
    				if(in_array('alias',$exportCol)){
        				if(trim($item['alias'])!=''){
        					$item['alias'] = trim($item['alias']);
        					$aa = explode(',', $item['alias']);
        					
        					//去除此SKU已存在的别名
        					$alias = array();
        					$palias = ProductAliases::find()->select(['alias_sku'])->where(['sku'=>$item ['sku']])->asArray()->All();
        					foreach ($palias as $a){
        					    $alias[] = $a['alias_sku'];
        					}
        					foreach ($aa as $k => $a){
        					    $a = trim($a);
        					    
        					    if(empty($a)){
        					        unset($aa[$k]);
        					    }
        					    if (in_array ( $a, $alias )) {
        					        unset($aa[$k]);
        					    }
        					}
        					
        					foreach ($aa as $a){
        					    $a = trim($a);
        					    
        						if (in_array ( $a, $importAlias )) {
        							$sameAlias [$a] [] = $key;
        							$aliasOk = false;
        						}
        						else {
        							$importAlias [] = $a;
        						}
        						if(in_array ( $a, $aliasInDb )){
        							$aliasOk = false;
        							$existingAlias[$a][]=$key;
        						}
        						
        						$excel_alias[] = $a;
        					}
        					if($aliasOk)
        						$productsData[$key]['alias'] = $aa;
        				}
    				}
    			}
    			
    			//当导入存在alias，即别名时
    			if(in_array('alias',$exportCol)){
        			foreach ($importAlias as $alias){
        				if(in_array($alias,$allProdSku ) || in_array(strtoupper($alias),$allProdSkuUp )){
        					$alias_EQ_importSku[] = $alias;
        					$aliasOk = false;
        				}
        			}
        			
        			$aliasIsSku = Product::find()->where(['in','sku',$importAlias])->asArray()->all();
        			if(!empty($aliasIsSku) ){
        				foreach ($aliasIsSku as $k=>$pd){
        					$alias_EQ_existingSku[]=$pd['sku'];
        					$aliasOk = false;
        				}
        			}
    			}
    	
    			$brandAll=Brand::find()->select(['brand_id','name'])->where('brand_id<>0')->asArray()->all();
    			foreach ($brandAll as $aBrand){
    				$CACHE['brandInfo'][$aBrand['name']]=$aBrand['brand_id'];
    			}
    	
    			$supplierAll=Supplier::find()->select(['supplier_id','name'])->where('supplier_id<>0')->asArray()->all();
    			foreach ($supplierAll as $aSupplier){
    				$CACHE['supplierInfo'][$aSupplier['name']]=$aSupplier['supplier_id'];
    			}
    			
    			//当导入捆绑商品时，必须有assku_list列，即捆绑子SKU列表
    			if($itype == 'B'){
    				if(!in_array('assku_list',$exportCol)){
    				    $aliasOk = false;
    				    $notExistAssku = true;
    				}
    			}
    			
    			//当导入变参商品时，必须有属性、值列
    			if($itype == 'L'){
    				if(!in_array('attribute1',$exportCol) || !in_array('value1',$exportCol)){
    					$aliasOk = false;
    					$not_Exist_attribute = true;
    				}
    				else if(!in_array('father_sku',$exportCol)){
    					$aliasOk = false;
    					$not_Exist_father_sku = true;
    				}
    				
    				//查询属性列表
    				$field_list = array();
    				$productField = ProductField::find()->select(['id', 'field_name'])->asArray()->all();
    				foreach ($productField as $p){
    				    if(!array_key_exists(strtolower($p['field_name']), $field_list)){
    				        $field_list[strtolower($p['field_name'])] = $p['id'];
    				    }
    				}
    			}
			}
			
			if (empty ( $sameSkuInfo ) && $aliasOk) {
				$prodsDatas['supplierInfo'] = array();
				$prodsDatas['info'] = array();
				$prodsDatas['tags'] = array();
				$prodsDatas['photos'] = array();
				$prodsDatas['prod_tag'] = array();
				$prodsDatas['alias'] = array();
				if(isset($item)) unset($item);
				foreach ( $productsData as $key => $item ) {
				    $allQty++;
					//$current_time=explode(" ",microtime());//test liang
					//$step3_a_1_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
					//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"prodHelper foreach validate used time:".($step2_time-$step1_time)],"edb\global");//test liang
	
					$validate = true;
						
					if (empty ( $item ['sku'] )) {
						$result [$key] [] = " 产品 sku 未填写 ";
						$validate = false;
					} else {
						// 检查 sku 格式
						/* 2015-5-19 khcomment start sku 允许特殊字符
							$pattern = '/^[0-9A-Za-z_-]+$/';
						if (! preg_match ( $pattern, $item ['sku'] )) {
						$validate = false;
						$result [$key] [] = ' SKU必须为英文,数字横线或下划线 ';
						} else
							2015-5-19 khcomment end */
						if (mb_strlen ( $item ['sku'], 'utf-8' ) > 255) {
							$result [$key] [] = " SKU太长 (最大值为 255 个字符) ";
							$validate = false;
						}
					}
						
					//导入的SKU已存在别名
					if (empty ( $item ['sku'] )) {
						$result [$key] [] = " 产品 sku 未填写 ";
						$result [$key] ['insert'] = false;
						$failQty++;
						continue;
					}
						
					if(in_array ($item ['sku'], $aliasInDb )){
						$result [$key] [] = " 此SKU为商品库已存在的别名 ";
						$result [$key] ['insert'] = false;
						$failQty++;
						continue;
					}
					
					$tmpP = self::getProductBySku ( $item ['sku'] );
					//$isUpdate = true;
					if (! $tmpP) {
						//$tmpP = new Product ();
						$isUpdate = false;
					} else {
						//$result [$key] [] = " 产品'" . $item ['sku'] . "'已存在 ";
						$isUpdate = true;
					}
					if(isset($tmpProData)) unset($tmpProData);
					$tmpProData = array ();
					
					//新增时，才插入类型
					if(!$isUpdate){
					    $item ['type'] = $itype;
					}
					// 目前默认设置产品类型为'普通'
					//$item ['type'] = array_search ( '普通', self::getProductType () );
						
					if (!empty($item ['status_cn']))
						$item ['status'] = array_search ( $item ['status_cn'], self::getProductStatus () );
					else
						$item ['status'] = array_search ( '在售', self::getProductStatus () );
					unset($item['status_cn']);
					
					if(in_array('prod_weight',$exportCol)){
					    $item ['prod_weight'] = empty($item['prod_weight'])? 0 : round ( floatval($item ['prod_weight']) );
					}
					if(in_array('prod_length',$exportCol)){
					    $item ['prod_length'] = empty($item['prod_length'])? 0 : round ( floatval($item ['prod_length']) );
					}
					if(in_array('prod_width',$exportCol)){
					    $item ['prod_width'] = empty($item['prod_width'])? 0 : round ( floatval($item ['prod_width']) );
					}
					if(in_array('prod_height',$exportCol)){
					    $item ['prod_height'] = empty($item['prod_height'])? 0 : round ( floatval($item ['prod_height']) );
					}
						
					// 产品名称，新增时或有更新此列
					if(!$isUpdate || in_array('name',$exportCol)){
    					if (! isset ( $item ['name'] ) || ! $item ['name']) {
    						$result [$key] [] = " 产品名称未填写 ";
    						$validate = false;
    					}
    					else if (mb_strlen ( $item ['name'], 'utf-8' ) > 250) {
    						$result [$key] [] = " 产品名称太长 (最大值为 250 个字符) ";
    						$validate = false;
    					}
					}
					
					// 中文配货名称，新增时或有更新此列
					if(!$isUpdate || in_array('prod_name_ch',$exportCol)){
    					if (! isset ( $item ['prod_name_ch'] ) || ! $item ['prod_name_ch']) {
    						$result [$key] [] = " 中文配货名称未填写 ";
    						$validate = false;
    					} 
    					else if (mb_strlen ( $item ['prod_name_ch'], 'utf-8' ) > 250) {
    						$result [$key] [] = " 中文配货名称太长 (最大值为 250 个字符) ";
    						$validate = false;
    					}
					}
					
					// 英文配货名称，新增时或有更新此列
					if(!$isUpdate || in_array('prod_name_en',$exportCol)){
    					if (! isset ( $item ['prod_name_en'] ) || ! $item ['prod_name_en']) {
    						$result [$key] [] = " 英文配货名称未填写 ";
    						$validate = false;
    					} 
    					else if (mb_strlen ( $item ['prod_name_en'], 'utf-8' ) > 250) {
    						$result [$key] [] = " 英文配货名称太长 (最大值为 250 个字符) ";
    						$validate = false;
    					}
					}
						
					// 中文报关名称，新增时或有更新此列
					if(!$isUpdate || in_array('declaration_ch',$exportCol)){
    					if (! isset ( $item ['declaration_ch'] ) || ! $item ['declaration_ch']) {
    						$result [$key] [] = " 中文报关名称未填写 ";
    						$validate = false;
    					} else if (mb_strlen ( $item ['declaration_ch'], 'utf-8' ) > 100) {
    						$result [$key] [] = " 中文报关名称太长 (最大值为 100 个字符) ";
    						$validate = false;
    					}
					}
					
					// 英文报关名称，新增时或有更新此列
					if(!$isUpdate || in_array('declaration_en',$exportCol)){
    					if (! isset ( $item ['declaration_en'] ) || ! $item ['declaration_en']) {
    						$result [$key] [] = " 英文报关名称未填写 ";
    						$validate = false;
    					} else if (mb_strlen ( $item ['declaration_en'], 'utf-8' ) > 100) {
    						$result [$key] [] = " 英文报关名称太长 (最大值为 100 个字符) ";
    						$validate = false;
    					}
					}
					
					if(!empty($tmpP['addi_info'])){
						$addi_info = json_decode($tmpP['addi_info'], true);
					}
					else {
						$addi_info = [];
					}
					if(empty($addi_info['commission_per'])){
						$addi_info['commission_per'] = [];
					}
					//设置佣金比例,addi_info
					foreach($item as $col => $col_val){
						if(strpos($col, 'commission_per_') !== false){
							$platname = str_replace('commission_per_', '', $col);
							if(!empty($col_val)){
								$addi_info['commission_per'][$platname] = $col_val;
							}
							else{
								unset($addi_info['commission_per'][$platname]);
							}
							
							unset($item[$col]);
						}
					}
					if(!empty($addi_info['commission_per'])){
						$item['addi_info'] = json_encode($addi_info);
					}
					else{
						$item['addi_info'] = '';
					}
					
					//当导入捆绑商品时
					if($itype == 'B'){
					    //当为更新时，非捆绑商品不可更新
					    if($isUpdate && $tmpP['type'] != 'B'){
					        $result [$key] [] = " ".$item['sku']."，此为非捆绑商品，不可更新 ";
					        $validate = false;
					    }
					    else{
    					    //assku_list列，非空
    					    if( empty($item['assku_list'])){
    					        $result [$key] [] = " 捆绑子SKU不能为 空 或 0 ";
    					        $validate = false;
    					    }
    					    else{
    					        $skus = array();
    					        $assku_list = array();
    					        $item['assku_list'] = rtrim($item['assku_list'],';');
    					        $arr = explode(';', $item['assku_list']);
    					        foreach ($arr as $a){
    					            if(!empty($a)){
        					            $val = explode('=', $a);
        					            $assku['bdsku'] = $item['sku'];
        					            $assku['assku'] = trim($val[0]);
        					            if(count($val)>1 && is_numeric(trim($val[1]))){
        					                $assku['qty'] = trim($val[1]);
        					            }
        					            else{
        					                $assku['qty'] = 1;
        					            }
        					            
        					            $skus[] = $assku['assku'];
        					            $assku_list[] = $assku;
    					            }
    					        }
    					        
    					        //判断哪些不属于商品库
    					        $not_exist_sku_str = '';
    					        $exist_skus = array();
    					        $pro = Product::find()->select('sku')->where(['sku'=>$skus, 'type'=>['S', 'L']])->asArray()->all();
    					        foreach ($pro as $p){
    					            $exist_skus[] = $p['sku'];
    					        }
    					        foreach ($skus as $s){
    					            if(!in_array($s, $exist_skus)){
    					                $not_exist_sku_str .= $s.'、';
    					            }
    					        }
    					        $not_exist_sku_str = rtrim($not_exist_sku_str, '、');
    					        if(!empty($not_exist_sku_str)){
    					            $result [$key] [] = " 捆绑子SKU”".$not_exist_sku_str."“不属于商品库的普通商品 或 变参子产品 ! ";
    					            $validate = false;
    					        }
    					        else{
    					            //排除此捆绑商品已存在的捆绑子SKU
    					            $bd_exist_sku = array();
    					            $bundle = ProductBundleRelationship::find()->select('assku')->where(['bdsku'=>$item['sku'], 'assku'=>$skus])->asArray()->all();
    					            foreach ($bundle as $b){
    					            	$bd_exist_sku[] = $b['assku'];
    					            }
    					            foreach ($assku_list as $s){
    					            	if(in_array($s['assku'], $exist_skus) && !in_array($s['assku'], $bd_exist_sku)){
    					            		$prodsDatas['assku_list'][] = $s;
    					            	}
    					            }
    					        }
    					    }
					    }
					}
					if(in_array('assku_list',$exportCol)){
						unset($item['assku_list']);
					}
					
					//当导入变参商品时
					$father_sku = '';
					$attribute_list = array();
					$config = array();
					if($itype == 'L'){
					    //当为更新时，非变参子商品不可更新
					    if($isUpdate && $tmpP['type'] != 'L'){
					    	$result [$key] [] = " ".$item['sku']."，此为非变参子商品，不可更新 ";
					    	$validate = false;
					    }
					    else{
					        //去掉SKU中的换行符和tab
					        $item ['father_sku'] = str_replace('\r', '', $item ['father_sku']);
					        $item ['father_sku'] = str_replace('\n', '', $item ['father_sku']);
					        $item ['father_sku'] = str_replace('\t', '', $item ['father_sku']);
					        $item ['father_sku'] = str_replace(chr(10), '', $item ['father_sku']);
					        //去掉SKU中的前后空格
					        $item ['father_sku'] = trim($item ['father_sku']);
					        
    					    if( empty($item['father_sku'])){
    					    	$result [$key] [] = " 父商品SKU不能为 空 或 0 ";
    					    	$validate = false;
    					    }
    					    if( strtoupper($item['sku']) == strtoupper($item ['father_sku'])){
    					    	$result [$key] [] = " 父商品跟子商品SKU不能相同 ";
    					    	$validate = false;
    					    }
    					    if( empty($item['attribute1']) || !isset($item['value1'])){
    					    	$result [$key] [] = " 属性1名称、属性1值不能为空 ";
    					    	$validate = false;
    					    }
    					    if(in_array ($item ['father_sku'], $aliasInDb )){
    					    	$result [$key] [] = " 此父商品SKU为商品库已存在的别名 ";
    					    	$validate = false;
    					    }
    					    if(in_array ($item ['father_sku'], $excel_alias )){
    					    	$result [$key] [] = " 此父商品SKU为Excel已存在的别名 ";
    					    	$validate = false;
    					    }
    					    
    					    $other_attributes = $item['attribute1'] .':'. $item['value1'];
    					    $attribute_list[] = $item['attribute1'];
    					    if( !empty($item['attribute2']) && isset($item['value2'])){
    					    	if($other_attributes != '')
    					    		$other_attributes = $other_attributes .';';
    					    	$other_attributes .= $item['attribute2'] .':'. $item['value2'];
    					    	$attribute_list[] = $item['attribute2'];
    					    }
    					    if( !empty($item['attribute3']) && isset($item['value3'])){
    					    	if($other_attributes != '')
    					    		$other_attributes = $other_attributes .';';
    					    	$other_attributes .= $item['attribute3'] .':'. $item['value3'];
    					    	$attribute_list[] = $item['attribute3'];
    					    }
    					    $item['other_attributes'] = $other_attributes;
    					    
    					    //只有插入时，才处理父商品相关信息
    					    if(!$isUpdate){
        						//判断父商品SKU是否存在，不存在则插入
        						$father = self::getProductBySku ($item['father_sku']);
        						if(empty($father)){
        						    $father_sku = $item['father_sku'];
        						}
    						
        						if(!empty($father['type']) && $father['type'] != 'C'){
        							$result [$key] [] = " ".$father['sku']."，此父商品为非变参商品，不可作为父商品 ";
        							$validate = false;
        						}
        						
    							//变参父商品、子产品配对
    							$config['cfsku'] = $item['father_sku'];
    							$config['assku'] = $item['sku'];
    							$config['create_date'] = date('Y-m-d H:i:s', time());
    							$config['config_field_ids'] = '';
    								
    							//判断此父商品是否已存在子商品，存在则插入属性名称组config_field_ids不变
    							$product_config = ProductConfigRelationship::find()->where(['cfsku'=>$item['father_sku']])->asArray()->one();
    							if(!empty($product_config)){
    								$config['config_field_ids'] = $product_config['config_field_ids'];
    							}
    							else{
    								//查询属性
    								foreach ($attribute_list as $a){
    									if(array_key_exists(strtolower($a), $field_list)){
    										$config['config_field_ids'] = empty($config['config_field_ids']) ? $field_list[$a] : $config['config_field_ids'].','.$field_list[$a];
    									}
    									else{
    										$fieldModel = new ProductField();
    										$fieldModel->field_name = $a;
    										$fieldModel->use_freq = 1;
    										$fieldModel->save(false);
    											
    										$field = ProductField::findOne(['field_name'=>$a]);
    										if(!empty($field)){
        										$field_list[$a] = $field->id;
        										$config['config_field_ids'] = empty($config['config_field_ids']) ? $field->id : $config['config_field_ids'].','.$field->id;
    										}
    									}
    								}
    							}
    					    }
					    }
					}
					
					if(in_array('attribute1',$exportCol)){
						unset($item['attribute1']);
					}
					if(in_array('value1',$exportCol)){
						unset($item['value1']);
					}
					if(in_array('attribute2',$exportCol)){
						unset($item['attribute2']);
					}
					if(in_array('value2',$exportCol)){
						unset($item['value2']);
					}
					if(in_array('attribute3',$exportCol)){
						unset($item['attribute3']);
					}
					if(in_array('value3',$exportCol)){
						unset($item['value3']);
					}
					if(in_array('father_sku',$exportCol)){
						unset($item['father_sku']);
					}
						
					// 20141124 添加逻辑： 不存在的分类,自动创建为level: 1 ,parent 0 的分类 , 不存在的品牌也自动创建
					// 分类
					/*
						if (isset ( $item ['category_name'] ) && $item ['category_name']) {
					$productCategory = Category::find ( 'name=:name', array (
							':name' => $item ['category_name']
					) );
					if (! $productCategory) {
					$productCategory = new Category ();
					$productCategory->name = $item ['category_name'];
					$productCategory->level = 1;
					$productCategory->parent_id = 0;
					$productCategory->comment = "导入产品创建";
					$productCategory->create_time = date ( 'Y-m-d H:i:s', time () );
					$productCategory-> = Yii::app ()->muser->getId ();
					if ($productCategory->save ()) {
					$item ['category_id'] = $productCategory->category_id;
					} else {
					$result [$key] [] = " 分类 '" . $item ['category_name'] . "' 创建失败  ";
					$validate = false;
					}
					} else if ($productCategory->has_children) {
					$result [$key] [] = " 分类 '" . $item ['category_name'] . "' 不是最末端的分类 ";
					$validate = false;
					} else {
					$item ['category_id'] = $productCategory->category_id;
					}
					}
					*/
					unset($item['category_name']);
					// 品牌
					if(in_array('brand_name',$exportCol)){
    					if (isset ( $item ['brand_name'] ) && trim($item ['brand_name'])!=='') {
    						$item ['brand_name'] = trim($item ['brand_name']);
    	
    						/*
    							if (in_array ( $item ['brand_name'], $allBrandName ))
    							$item ['brand_id'] = $brandNameIdMapping [$item ['brand_name']];
    						else {
    						$brand = new Brand ();
    						$brand->name = $item ['brand_name'];
    						$brand->comment = "导入产品创建";
    						$tmpTime = date ( 'Y-m-d H:i:s', time () );
    						$brand->create_time = $tmpTime;
    						$brand->update_time = $tmpTime;
    						$brand->capture_user_id = Yii::$app->user->id;
    							
    						if ($brand->save ()) {
    						$item ['brand_id'] = $brand->brand_id;
    						} else {
    						$result [$key] [] = " 品牌'" . $item ['brand_name'] . "' 创建失败 ";
    						$validate = false;
    						}
    						}
    						*/
    							
    						if(isset($CACHE['brandInfo'][$item ['brand_name']])){
    							$item['brand_id']=$CACHE['brandInfo'][$item ['brand_name']];
    						}
    						else{
    							$tmpBrand = BrandHelper::getBrandId($item ['brand_name'],true);
    							$item ['brand_id'] = $tmpBrand['brand_id'];
    							$CACHE['brandInfo'][$item ['brand_name']]=$item ['brand_id'];
    						}
    					}else
    						$item['brand_id']=0;
    					unset($item['brand_name']);
					}
					
					//首选供应商信息
					if(in_array('supplier_name',$exportCol)){
    					// 假如存在同名的可用供应商,这里是通过find方法自己筛选获取最终结果的
    					if (isset ( $item ['supplier_name'] ) && trim($item ['supplier_name'])!=='') {
    						$item ['supplier_name']=trim($item ['supplier_name']);
    						/*
    							$criteria = new CDbCriteria ();
    						$criteria->compare ( 'name', $item ['supplier_name'] );
    						$criteria->compare ( 'is_disable', 0 );
    						$productSupplier = Supplier::find()
    						->andwhere( ['name' =>$item ['supplier_name'] ] )
    						->andwhere(['is_disable'=> 0])
    						->asArray()->all();
    						if ($productSupplier == null || $productSupplier->is_disable == 1) {
    						$result [$key] [] = " 供应商'" . $item ['supplier_name'] . "' 不存在 ";
    						$validate = false;
    						} else if ($productSupplier->status == 2) {
    						$result [$key] [] = " 供应商'" . $item ['supplier_name'] . "' 已停用 ";
    						$validate = false;
    						} else {
    						$item ['supplier_id'] = $productSupplier->supplier_id;
    						}
    						*/
    						if(isset($CACHE['supplierInfo'][$item ['supplier_name']])){
    							$item['supplier_id']=$CACHE['supplierInfo'][$item ['supplier_name']];
    						}
    						else{
    							$tmpSupplier = SupplierHelper::getSupplierId($item ['supplier_name'],true);
    							$item ['supplier_id'] = $tmpSupplier['supplier_id'];
    							$CACHE['supplierInfo'][$item ['supplier_name']]=$item ['supplier_id'];
    						}
    					}else
    						$item['supplier_id']=0;
    					unset($item['supplier_name']);
					}
					
					// 标签
					if(in_array('prod_tag',$exportCol)){
    					if (isset ( $item ['prod_tag'] ) && $item ['prod_tag']) {
    						$tags = explode ( ",", $item ['prod_tag'] );
    						$tmpTag=array();
    						foreach ( $tags as $t=>$tag ) {
    							if (mb_strlen ( $tag, 'utf-8' ) > 100) {
    								$result [$key] [] = " 标签太长 (最大值为 100 个字符) ";
    								$validate = false;
    								unset($tmpTag);
    								break;
    							} else {
    								if(trim($tag)!==''){
    									$tmpTag[$t]['tag_name']= trim($tag);
    									$tmpTag[$t]['sku'] = $item ['sku'];
    									$prodsDatas['tags'][]=trim($tag);
    								}
    							}
    						}
    					}
    					unset($item ['prod_tag']);
    					if(in_array('is_has_tag',$exportCol)){
        					if(empty($tmpTag)){
        						$item ['is_has_tag']='N';
        					}else{
        						$item ['is_has_tag']='Y';
        					}
    					}
					}
					
					//分类
					if(in_array('class_name', $exportCol)){
						if(!empty($item['class_name'])){
							$class_arr = explode(",", rtrim($item['class_name'], ","));
							$parent_number = '';
							$class_id = 0;
							$count = 0;
							foreach($class_arr as $class_name){
								$class_name = trim($class_name);
								$node = ProductClassification::findOne(['name' => $class_name, 'parent_number' => $parent_number]);
								if(!empty($node)){
									$parent_number = $node->number;
									$class_id = $node->ID;
									$count++;
								}
								else{
									break;
								}
							}
							
							if(count($class_arr) == $count){
								$item['class_id'] = $class_id;
							}
							else{
								$item['class_id'] = 0;
							}
						}
						else{
							$item['class_id'] = 0;
						}
						
						unset($item['class_name']);
					}
					
					//别名
					//当导入存在alias，即别名时
					if(in_array('alias',$exportCol)){
    					if(isset($item['alias'])){
    						if(is_array($item['alias'])){
    							foreach ($item['alias'] as $a){
    								$prodsDatas['alias'][]=array(
    										'sku'=>$item['sku'],
    										'alias_sku'=>$a,
    										'platform'=>'',
    										'selleruserid'=>'',
    										'comment'=>'由excle导入创建',
    								);
    							}
    							
    							//新增时，插入自身
    							if(!$isUpdate){
    								$prodsDatas['alias'][]=array(
    										'sku'=>$item['sku'],
    										'alias_sku'=>$item['sku'],
    										'platform'=>'',
    										'selleruserid'=>'',
    										'comment'=>'由excle导入创建',
    								);
    							}
    						}
    						unset($item['alias']);
    					}
					}
					
					$photos = array ();
	
					//新增时，所有图片都可插入
					if(!$isUpdate){
    					if (isset ( $item ['photo_primary'] ) && $item ['photo_primary'])
    						$photos [0] = $item ['photo_primary'];
    					for($i = 2; $i <= 6; $i ++) {
    						if (! empty ( $item ['photo_others_' . $i] )) {
    							if (! in_array ( $item ['photo_others_' . $i], $photos ))
    								$photos [$i] = $item ['photo_others_' . $i];
    							else {
    								$result [$key] [] = '产品图片' . $i . '重复';
    								$validate = false;
    							}
    						}
    						unset($item ['photo_others_' . $i]);
    					}
					}
					//修改时，主图可变更
					else{
					    if (isset ( $item ['photo_primary'] ) && $item ['photo_primary']){
					        $photos [0] = $item ['photo_primary'];
					        $Photolist = Photo::find()->where(['sku'=>$item['sku']])->asArray()->All();
					        foreach ($Photolist as $ph){
					            if(trim($ph['photo_url']) != trim($item ['photo_primary'])){
					                $photos [] = $ph['photo_url'];
					            }
					        }
					    }
					}
						
					//$item ['photo_others'] = '';
						
					if (! empty ( $photos )) {
						$checkPhoto = true;
						foreach ( $photos as $photoIndex => $photo ) {
							// 正则检查链接合法性, 目前只是检查链接的最基本格式，没有检查链接是否有效
							$pattern = '/^(http)|(https):\/\//';
							if (! preg_match ( $pattern, $photo ) && strpos($photo, '/images/') !== 0) {
								$checkPhoto = false;
								$result [$key] [] = '产品' . (($photoIndex == 0) ? '主图片' : '图片' . $photoIndex) . '地址无效';
							}
						}
						if ($checkPhoto){
							//排重和检查有效性后，photo_others数组需要排除主图
							//if(isset($photos[0])){
							//	unset($photos[0]);
							//}
								
							//$item ['photo_others'] = implode ( '@,@', $photos );
						}
						else
							$validate = false;
					}
						
					if (! $validate) {
						if (! empty ( $item ['sku'] )) {
							$result [$key] ['sku'] = $item ['sku'];
						}
						$result [$key] ['insert'] = false;
						$failQty++;
						continue;
					}else{
						$result [$key] ['insert'] = true;
						$insertSkuList[] = $item ['sku'];
					}
					
					if(!$isUpdate){
					    $item ['create_source'] = 'excel';
					}
						
					$tmpProData= $item;
					$result [$key] ['sku'] = $item ['sku'];
					
					//首选供应商对应的采购价
					if(in_array('supplier_name',$exportCol)){
    					if (isset ( $item ['purchase_price'] ) && $item ['purchase_price'] != '') {
    						$item ['purchase_price'] = (empty ( $item ['purchase_price'] ) ? 0 : floatval($item ['purchase_price']));
    						$tmpProData['purchase_price'] = $item ['purchase_price']+0;
    					}else{
    						$tmpProData['purchase_price']=0;
    					}
    					if(isset($tmpProData['supplier_id'])){
    						$prodsDatas['supplierInfo'][]=array(
    								'sku'=>$tmpProData['sku'],
    								'supplier_id'=>$tmpProData['supplier_id'],
    								'priority'=>0,
    								'purchase_price'=>$tmpProData['purchase_price'],
    								'purchase_link' => empty($tmpProData ['purchase_link']) ? '' : trim($tmpProData ['purchase_link']),
    						);
    					}
					}
					
					if(isset($tmpProData['purchase_link'])){
						unset($tmpProData['purchase_link']);
					}
						
					//$current_time=explode(" ",microtime());//test liang
					//$step3_a_2_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
					//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"prodHelper foreach validate used time:".($step3_a_2_time-$step3_a_1_time)],"edb\global");//test liang
						
					//$tmpP->attributes=$tmpProData;
					//$tmpP->create_time=date('Y-m-d H:i:s', time());
					//$tmpP->update_time=date('Y-m-d H:i:s', time());
					//$tmpP->purchase_by=\Yii::$app->user->id;
					//$tmpP->capture_user_id=\Yii::$app->user->id;
					//$tmpP->total_stockage=0;
					//$tmpP->pending_ship_qty=0;
						
					$tmpProData['purchase_by']=\Yii::$app->user->id;
					$tmpProData['capture_user_id']=\Yii::$app->user->id;
					
					//当导入变参商品时
					if($itype == 'L' && !$isUpdate){
					    //插入父商品
					    if(!empty($father_sku) && !in_array($father_sku, $allProdSku)){
					        $tmpProData_father = $tmpProData;
					        $tmpProData_father['sku'] = $father_sku;
					        $tmpProData_father['type'] = 'C';
					        $tmpProData_father ['status'] = $tmpProData['status'];
					        $tmpProData_father['create_time'] = date('Y-m-d H:i:s', time());
					        $tmpProData_father['update_time'] = date('Y-m-d H:i:s', time());
					        $tmpProData_father['total_stockage'] = 0;
					        $tmpProData_father['pending_ship_qty'] = 0;
					        $tmpProData_father['other_attributes'] = '';
					        if(in_array('is_has_tag',$exportCol)){
					            $tmpProData_father['is_has_tag'] = 'N';
					        }
					        
					        $prodsDatas['info'][] = $tmpProData_father;
					        $insertSkuList[] = $father_sku;
					        
					        //父商品图片
					        if(!empty($photos)){
    			        		foreach ($photos as $pIndex=>$pUrl){
    			        			if($pIndex==0 or $pIndex==1)
    			        				$priority = $pIndex;
    			        			else
    			        				$priority = $pIndex-1;
    			        				
    			        			$tmpPhoto['sku'] = $father_sku;
    			        			$tmpPhoto['priority'] =$priority;
    			        			$tmpPhoto['photo_scale'] ='OR';
    			        			$tmpPhoto['photo_url'] =$pUrl;
    			        				
    			        			$prodsDatas['photos'][]=$tmpPhoto;
    					        }
					        }
					        
					        $allProdSku[] = $father_sku;
					    }
					    
					    //插入变参商品配对信息
						$prodsDatas['config_list'][] = $config;
					}
						
					if(!$isUpdate){
						//新增
						$tmpProData['create_time']=date('Y-m-d H:i:s', time());
						$tmpProData['update_time']=date('Y-m-d H:i:s', time());
						$tmpProData['total_stockage']=0;
						$tmpProData['pending_ship_qty']=0;
						$prodsDatas['info'][]=$tmpProData;
						
						$add_log .= $tmpProData['sku'].", ";
					}else{
					    $old_product = Product::findOne(['product_id' => $tmpP->product_id]);
					    
						$tmpProData['update_time']=date('Y-m-d H:i:s', time());
						$tmpP->attributes = $tmpProData;
						$tmpP->save(false);
						$result [$key] [] = '产品已'.$item ['sku'].'存在，本次为更新操作';
						$successUpdateQty++;
						
						//记录修改日志
					    $log = '';
						if(!empty($old_product)){
							foreach (self::$EDIT_PRODUCT_LOG_COL as $col_k => $col_n){
								if($tmpP->$col_k != $old_product->$col_k){
									if(empty($log)){
										$log = $tmpP->sku;
									}
									$log .= ', '.$col_n.'从"'.$old_product->$col_k.'"改为"'.$tmpP->$col_k.'"';
								}
							}
							if(!empty($log)){
							    $edit_log .= $log."; ";
							}
						}
					}
					/*
						if( $tmpP->save() ){
					$result[$key]['insert']=true;
					}else{
					$result[$key]['insert']=false;
					foreach ($tmpP->errors as $k => $anError){
					$result[$key][] = $anError[0];
					}
					}
					*/
						
					if (!empty($tmpTag)){
						foreach ($tmpTag as $pt)
							$prodsDatas['prod_tag'][]=$pt;
						unset($tmpTag);
					}
					
					if(!empty($photos)){
						foreach ($photos as $pIndex=>$pUrl){
							if($pIndex==0 or $pIndex==1)
								$priority = $pIndex;
							else
								$priority = $pIndex-1;
								
							$tmpPhoto['sku'] = $item['sku'];
							$tmpPhoto['priority'] =$priority;
							$tmpPhoto['photo_scale'] ='OR';
							$tmpPhoto['photo_url'] =$pUrl;
								
							$prodsDatas['photos'][]=$tmpPhoto;
						}
					}
					/*
						if($result[$key]['insert']){
					if (!empty($tmpTag))
						TagHelper::updateTag($tmpP->sku, $tmpTag) ? 'Y' : 'N';
	
					$photo_primary=empty($item ['photo_primary'])?'':$item ['photo_primary'];
					if(empty($photos)) $photos=array();
	
					PhotoHelper::savePhotoByUrl($tmpP->sku, $photo_primary, $photos);
	
					}
						
						
					//$result [$key] ['insert'] = self::saveProduct ( $tmpP, $tmpProData, $isUpdate );
						
					$current_time=explode(" ",microtime());//test liang
					$step3_a_4_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
					\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"prodHelper foreach other_save used time:".($step3_a_4_time-$step3_a_3_time)],"edb\global");//test liang
					*/
				}
				//transaction
				//print_r($prodsDatas);die;
				$transaction = Yii::$app->get('subdb')->beginTransaction();
				try{
					SQLHelper::groupInsertToDb('pd_product', $prodsDatas['info']);
					$successInsertQty = count($prodsDatas['info']);

					//导入列中存在供应商列
					if(in_array('supplier_name',$exportCol)){
					    if(!empty($prodsDatas['supplierInfo'])){
        					ProductSuppliers::deleteAll(['sku'=>$insertSkuList, 'priority'=>'0']);
        					foreach ($prodsDatas['supplierInfo'] as $supplierInfo){
        					    ProductSuppliers::deleteAll(['sku'=>$supplierInfo['sku'], 'supplier_id'=>$supplierInfo['supplier_id']]);
        					}
        					SQLHelper::groupInsertToDb('pd_product_suppliers',  $prodsDatas['supplierInfo']);
					    }
					}
		
					//导入列中存在标签列
					if(in_array('prod_tag',$exportCol)){
    					$tagExist = Tag::find()->select(['tag_name'])->where("tag_id<>0")->asArray()->all();
    					$tagExistArray=array();
    					foreach ($tagExist as $tE){
    						$tagExistArray[]=$tE['tag_name'];
    					}
    					$allTagsPost = array_unique($prodsDatas['tags']);
    					$tagNeedToInster = array_diff($allTagsPost, $tagExistArray);
    					$tagNeedToInsterData=array();
    					foreach ($tagNeedToInster as $tag){
    						$tagNeedToInsterData[]=array('tag_name'=>$tag);
    					}
    		
    					SQLHelper::groupInsertToDb('pd_tag', $tagNeedToInsterData);
    		
    					$tagModels=Tag::find()->where(['in', 'tag_name', $allTagsPost])->asArray()->all();
    					$CACHE['tag']=$tagModels;
		
    					ProductTags::deleteAll(['sku'=>$insertSkuList]);
    					if(!empty($prodsDatas['prod_tag'])){
    						$tmpProdTags = array();
    						foreach ($prodsDatas['prod_tag'] as $prodTag){
    							foreach ($CACHE['tag'] as $index=>$tagInfo){
    								if($prodTag['tag_name']==$tagInfo['tag_name']){
    									$tmpProdTags[]=array('tag_id'=>$tagInfo['tag_id'] , 'sku'=>$prodTag['sku']);
    									break;
    								}
    							}
    						}
    						$prodsDatas['prod_tag'] = $tmpProdTags;
    						if(!empty($prodsDatas['prod_tag']))
    							SQLHelper::groupInsertToDb('pd_product_tags', $prodsDatas['prod_tag']);
    					}
					}
		
					if(!empty($prodsDatas['photos'])){
    					Photo::deleteAll(['sku'=>$insertSkuList]);
    					SQLHelper::groupInsertToDb('pd_photo', $prodsDatas['photos']);
					}
					
					SQLHelper::groupInsertToDb('pd_product_aliases', $prodsDatas['alias']);
					
					//导入捆绑子SKU
					if($itype == 'B' && !empty($prodsDatas['assku_list'])){
					    SQLHelper::groupInsertToDb('pd_product_bundle_relationship', $prodsDatas['assku_list']);
					}
					
					//导入变参商品关系
					if($itype == 'L' && !empty($prodsDatas['config_list'])){
						SQLHelper::groupInsertToDb('pd_product_config_relationship', $prodsDatas['config_list']);
					}
					
					$transaction->commit();
					
					//更新分类统计数量
					self::getProductClassCount(true);
					
					//记录操作日志
					$logs = '';
					if(!empty($add_log)){
					    $logs .= "新增: ".$add_log;
					}
					if(!empty($edit_log)){
						$logs .= "修改: ".$edit_log;
					}
					
					if(!empty($logs)){
					    $logs = "导入商品, 成功新增: ".$successInsertQty.", 更新: ".$successUpdateQty.", 失败: ".$failQty."。".$logs;
					    //print_r($logs);die;
					    if(strlen($logs) > 480){
					        $logs = substr($logs, 0, 480).'......';
					    }
    					//写入操作日志
    					UserHelper::insertUserOperationLog('catalog', $logs);
					}
					
				}
				catch (\Exception $e) {
				    //print_r($e->getMessage());die;
    				$result['error'] .= '操作失败，保存数据发生错误。重试依然出现此提示请联系客服。';
    				$transaction->rollBack();
    				SysLogHelper::SysLog_Create('Catalog',__CLASS__, __FUNCTION__,'error',$e->getMessage());
    				
    				$uid = \Yii::$app->subdb->getCurrentPuid();
    				\Yii::info('Catalog, puid: '. $uid .', importProductData, '.$e->getMessage().PHP_EOL."trace:".$e->getTraceAsString(), "file");
    			}
			} 
			else {
				$result ['error'] = "导入中止,";
				if($is_not_sku)
				    $result ['error'] .="导入商品时，必须存在列‘SKU’<br>";
				if(!empty($sameSkuInfo))
					$result ['error'] .="上传产品中存在相同的 sku:" . implode ( ',', array_keys ( $sameSkuInfo ) )."<br>";
				if(!empty($sameAlias))
					$result ['error'] .="上传产品中存在相同的 sku别名:" . implode ( ',', array_keys ( $sameAlias ) )."<br>";
				if(!empty($existingAlias))
					$result ['error'] .="上传产品中存在已被使用的别名:" . implode ( ',', array_keys ( $existingAlias ) )."<br>";
				
				if(!empty($alias_EQ_existingSku))
					$result ['error'] .="上传产品的别名中存在与已有商品sku有重复，excel导入不支持别名覆盖已有商品:" . implode ( ',', $alias_EQ_existingSku )."<br>";
				
				if(!empty($alias_EQ_importSku))
					$result ['error'] .="上传产品的别名中存在与本次上传的商品sku有重复，excel导入不支持别名覆盖已有商品:" . implode ( ',', $alias_EQ_importSku )."<br>";
				
				if($notExistAssku)
				    $result ['error'] .="导入捆绑商品时，必须存在列‘捆绑子SKU’<br>";
				if($not_Exist_attribute)
				    $result ['error'] .="导入变参商品时，必须存在列‘属性1名称’和‘属性1取值’<br>";
				if($not_Exist_father_sku)
				    $result ['error'] .="导入变参商品时，必须存在列‘父商品sku’<br>";
				
			}
		} else {
			$result ['error'] = $productsData;
		}
		if (isset ( $result ['error'] ) && $result ['error']) {
			//SysLogHelper::SysLog_Create ( "Catalog", __CLASS__, __FUNCTION__, "", $result ['error'], "error" );
			$moduleName = "Catalog";
			$message = $result ['error'];
			\Yii::error([$moduleName,__CLASS__,__FUNCTION__,$message],"edb\user");
		} else {
			//SysLogHelper::SysLog_Create ( "Catalog", __CLASS__, __FUNCTION__, "", "import success : $successNum , fail : $failNum ", "trace" );
				
			$moduleName = "Catalog";
			$message = "import success : ".($successInsertQty+$successUpdateQty)." , fail : $failQty  ";
			\Yii::info([$moduleName,__CLASS__,__FUNCTION__,$message],"edb\user");
		}
		
		$result['allQty'] = $allQty;
		$result['successInsertQty'] = $successInsertQty;
		$result['successUpdateQty'] = $successUpdateQty;
		$result['failQty'] = $failQty;
		
		return $result;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 获取供应商数据
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @return 供应商数据 +----------------------------------------------------------
	 *         log			name	date					note
	 * @author ouss	2014/02/27				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function getProductSuppliers() {
		$criteria = new CDbCriteria ();
		$criteria->select = 'supplier_id,name';
		$criteria->compare ( 'is_disable', 0 );
		$criteria->compare ( 'status', 1 );
		$suppliers = Supplier::findAll ( $criteria );
		$suppliersArray = array ();
		foreach ( $suppliers as $s ) {
			$suppliersArray [$s->supplier_id] = $s->name;
		}
		return $suppliersArray;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 根据参数检查产品别名是否有效
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku			商品SKU
	 * @param
	 *        	PDAliasList	别名数组
	 *+----------------------------------------------------------
	 * @return 别名验证结果
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2014/09/26				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function checkProductAlias($sku, $PDAliasList) {
		// get alias data
		//$pdCriteria = new CDbCriteria ();
		//$pdCriteria->addInCondition ( 'alias_sku', $PDAliasList );
		$pdCriteria = ['alias_sku'=>$PDAliasList] ;
		$aliasList = ProductAliases::find()->where($pdCriteria)->all();
		$result ['status'] = "success";
		$result ['message'] = "";
		// validate alias whether active
		foreach ( $aliasList as $anAlias ) {
			if ($anAlias->sku != $sku) {
				$result ['status'] = "failure";
				$result ['message'] .= "别名[" . $anAlias->alias_sku . "]已经被商品[" . $anAlias->sku . "]使用! <br>";
			}
		}
		
		// check alias whether a product sku
		if ($result ['status'] == "success") {
			unset ( $pdCriteria );
			//$pdCriteria = new CDbCriteria ();
			//$pdCriteria->@author Administrator

			/*
			$pdCriteria = ['sku'=>$PDAliasList];
			$product_list = Product::findAll ( $pdCriteria );
			foreach ( $product_list as $a_product ) {
				if ($result ['status'] != 'confirm') {
					$result ['status'] = 'confirm';
				}
				$result['redundant'][]=$a_product->sku;
				$result ['message'] .= "别名[" . $a_product->sku . "]是有效的产品  <br>";
			}*/
			
			/*kh20150504start eagle 2.0 暂时不提供合并的操作, 先屏蔽相关的前台提示 
			if (strlen ( $result ['message'] ) > 0) {
				
				$result ['message'] .= "是否将以上产品合并到 $sku ？<br> 注意此操作会将上述产品出现的历史记录一并更新且操作不可反悔！是否继续？";
			}
			kh20150504end eagle 2.0 暂时不提供合并的操作, 先屏蔽相关的前台提示 */
		}
		
		return $result;
	} // end of checkProductAlias
	
	/**
	 * +----------------------------------------------------------
	 * 根据参数查找最新的订单
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku			商品SKU
	 *+----------------------------------------------------------
	 * @return 订单编号 +----------------------------------------------------------
	 *         log			name	date					note
	 * @author lkh	2014/09/28				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function getLastOrderID($sku) {
		// $sku = '14052201_Y';//test
		$MyorderItem = OdOrderItem::find ( 'sku=:key', array (
				':key' => $sku 
		) );
		if (isset ( $MyorderItem->order_id ))
			return $MyorderItem->order_id;
		else
			return "";
	} // end of getLastOrderID
	
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
	 * log			name	date					note
	 * @author 		lkh		2014/10/09				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function getRootSkuByAlias($sku_alias, $platform = '', $selleruserid = '') {
		global $CACHE;
		$sku_alias = trim ( $sku_alias );
		$platform = empty($platform) ? '' : $platform;
		$selleruserid = empty($selleruserid) ? '' : $selleruserid;
		
		//2016-07-01  为后台检测订单加上global cache 的使用方法 start
		$uid = \Yii::$app->subdb->getCurrentPuid();
// 		var_dump($CACHE[$uid]);
// 		exit();

		$alias_key = $sku_alias.$platform.$selleruserid;
		if (isset($CACHE[(string)$uid]['alias'])){
			//有alias 缓存 ， 读取缓存数据
			if (!empty($CACHE[(string)$uid]['alias'][$alias_key])){
				$result = $CACHE[(string)$uid]['alias'][$alias_key];
			}
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'alias has cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}
		
		if(empty($result)){
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'alias no cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
			
			$aliasList = ProductAliases::find()->select(['sku', 'platform', 'selleruserid'])->where(['alias_sku' => $sku_alias])->orderby('platform desc, selleruserid desc')->asarray()->all();
			if(!empty($aliasList)){
				//当配对关系只有一条时，默认匹配
				if(count($aliasList) == 1){
					return $aliasList[0]['sku'];
				}
				else{
					foreach ($aliasList as $alias){
						//先取平台、店铺都匹配的项
						if($alias['platform'] == $platform && $alias['selleruserid'] == $selleruserid){
							return $alias['sku'];
						}
						//再取平台匹配，店铺通用的项
						else if($alias['platform'] == $platform && empty($alias['selleruserid'])){
							return $alias['sku'];
						}
						//再取平台通用、店铺通用的项
						else if(empty($alias['platform']) && empty($alias['selleruserid'])){
							return $alias['sku'];
						}
					}
				}
			}
			else{
				//判断是否只存在商品库
				$pro = Product::findOne(['sku' => $sku_alias]);
				if(!empty($pro)){
					return $sku_alias;
				}
			}
		}
		
		//只查别名表，不查商品库
		/*if (empty($result)){
			//没有alias 的缓存 读取product的缓存确定
			if (isset($CACHE[(string)$uid]['product'])){
				
				if (!empty($CACHE[(string)$uid]['product'][$sku_alias])){
					$result = $CACHE[(string)$uid]['product'][$sku_alias];
				}
				
				//log 日志 ， 调试相关信息start
				$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'product has cache';
				\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
				//log 日志 ， 调试相关信息end
			}else{
				// check this sku whether root sku
				$result = Product::findOne (  array (
						'sku' => $sku_alias
				) );
				
				//log 日志 ， 调试相关信息start
				$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).'product no cache';
				\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
				//log 日志 ， 调试相关信息end
			}
		}*/
		
		//2016-07-04  为后台检测订单加上global cache 的使用方法 start
		
		if (empty ( $result )) {
			return "";
		} else {
			if (is_array($result)){
				$result = (Object)$result;
			}
			return $result->sku;
		}
		
	} // end of getRootSkuByAlias

	//yzq 20170221 performance tuning
	public static function getRootSkuByAliasArr($sku_aliasArr) {
		global $CACHE;
		//$sku_alias = trim ( $sku_alias );
	
		 
		// this sku not root sku
		$result1 = ProductAliases::findAll ( array (
				'alias_sku' => $sku_aliasArr
		) );

		
		$result2 = Product::findAll(  array (
				'sku' => $sku_aliasArr
			) );		
		
	
		$allResult = [];
		foreach ($result1 as $aResult){
			$allResult[strtolower($aResult->alias_sku)] = $aResult->sku;
		}
		
		foreach ($result2 as $aResult){
			if (!isset($allResult[$aResult->sku]))
			$allResult[strtolower($aResult->sku)] = $aResult->sku;
		}
	
		return $allResult; 
	
	} // end of getRootSkuByAliasArr
	
	
	/**
	 * +----------------------------------------------------------
	 * 返回该sku for 这个 site 的alias
	 * 如果这个site 不存在 或者 为空白，返回所有 alias model
	 * 如果这个site 不为空并且存在，只返回该 site 的alias (也有可能是多个，视乎数据)
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku 商品SKU或别名
	 * @param
	 *        	site 商品 对应的网站
	 *+----------------------------------------------------------
	 * @return na +----------------------------------------------------------
	 *         log			name	date					note
	 * @author lkh	2014/10/08				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function getAliasForSku($sku, $site = '') {
		// init
		$all_alias = array ();
		$match_alias = array ();
		
		// get all data through this sku
		$product_alias_list = ProductAliases::FindAll ( 'sku=:sku', array (
				':sku' => $sku 
		) );
		
		foreach ( $product_alias_list as $an_alias ) {
			$all_alias [] = $an_alias->alias_sku;
			
			if (strtolower ( $an_alias->forsite ) == strtolower ( $site )) {
				$match_alias [] = $an_alias->alias_sku;
			}
		}
		
		if (empty ( $site ) || empty ( $match_alias )) {
			// if site is empty then return all alias
			return $all_alias;
		} else {
			// if site is not empty then return this site alias
			return $match_alias;
		}
	} // end of getAliasForSku
	
	/**
	 * 查找与该sku关联的所有root和其他alias 
	 * 如果这个sku不存在，则返回空字符串
	 * 否则，返回数组
	 * @access		static
	 * @param		sku			商品SKU或别名
	 * @return		'' or array(rootsku=>[type=>'root'],alias_sku1=>['type'=>'alia','forsite'=>'ebay'],alias_sku2=>['type'=>'alia','forsite'=>'amazon'],...)
	 * @author		lzhl	2016/8/19	初始化
	 */
	public static function getAllAliasRelationBySku($sku){
		$relation = [];
		$rootSku = self::getRootSkuByAlias($sku);
		
		if(empty($rootSku))//无root sku,且商品库没有改商品
			return $relation;
		else //有root sku,或自己是root sku且在商品库
			$relation[$rootSku] = ['type'=>'root'];
		
		if(!empty($rootSku)){
			$product_alias_list = ProductAliases::find()->where(['sku'=>$rootSku])->asArray()->all();
			foreach ($product_alias_list as $an_alias){
				$relation[$an_alias['alias_sku']] = ['type'=>'alia','forsite'=>$an_alias['forsite']];
			}
		}
		
		return $relation;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 获取某个sku的产品类型
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku_or_alias 商品SKU或别名
	 *+----------------------------------------------------------
	 * @return string “C”：变参父产品
	 *         “L”：变参子产品
	 *         “S”: 普通产品
	 *         “B”: 捆绑产品
	 *         "": 找不到对应商品的类型
	 *+----------------------------------------------------------
	 *         log			name	date					note
	 * @author lkh 2014/10/17				初始化
	 *+----------------------------------------------------------
	 */
	public static function getProductTypebySKU($sku_or_alias) {
		// step 1 get root sku
		$root_sku = self::getRootSkuByAlias ( $sku_or_alias );
		if (empty ( $root_sku )) {
			return "";
		} else {
			// get product type
			$result = Product::find ( 'sku =:sku', array (
					':sku' => $root_sku 
			) );
			if (empty ( $result )) {
				return "";
			} else {
				return $result->type;
			}
		}
	} // end of getProductType
	
	/**
	 * +----------------------------------------------------------
	 * 获取产品的父级变参产品 sku
	 * 如果此产品不是root sku
	 * 查找返回该 alias 对应的 root sku，
	 * 返回该 root sku 的pd_product_config_relationship 表中的 父sku。
	 * 如果该产品是 普通产品，则返回自己sku
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku 商品SKU
	 *+----------------------------------------------------------
	 * @return sku 或 空字符串
	 *+----------------------------------------------------------
	 *         log			name	date					note
	 * @author lkh 2014/10/17				初始化
	 *+----------------------------------------------------------
	 */
	public static function getConfigFatherSKU($sku) {
		$product_type = self::getProductTypebySKU ( $sku );
		if (strtolower ( $product_type ) == "l") {
			// only child product should find father sku
			$relation_ship = ProductConfigRelationship::find ( 'assku = :sku', array (
					':sku' => $sku 
			) );
			if (empty ( $relation_ship )) {
				return $sku;
			} else {
				return $relation_ship->cfsku;
			}
		} else {
			return $sku;
		}
	} // end of getConfigFatherSKU
	
	/**
	 * +----------------------------------------------------------
	 * 获取变参父产品的子产品 sku，例如 返回 变参父产品“AAA” 的变参子产品sku 是 array(“AAA_1”,”AAA_2”)
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku 商品SKU
	 *+----------------------------------------------------------
	 * @return array sku数组
	 *+----------------------------------------------------------
	 *         log			name	date					note
	 * @author lkh 2014/10/17				初始化
	 *+----------------------------------------------------------
	 */
	public static function getConfigAsSKU($sku) {
		// find father sku
		$father_sku = self::getConfigFatherSKU ( $sku );
		// get all relation ship data
		$relation_ship_list = ProductConfigRelationship::findall ( 'cfsku = :sku', array (
				':sku' => $father_sku 
		) );
		if (empty ( $relation_ship_list )) {
			return array (
					$sku 
			);
		} else {
			foreach ( $relation_ship_list as $a_relation ) {
				$reuslt [] = $a_relation->assku;
			}
			return $reuslt;
		}
	} // end of getConfigAsSKU
	
	/**
	 * +----------------------------------------------------------
	 * 获取变参信息
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	names field name
	 * @param
	 *        	action 请求操作(new:新增子产品 add_old:旧产品作为子产品)
	 * @param
	 *        	sku 产品的sku
	 *+----------------------------------------------------------
	 * @return array 
	 * +----------------------------------------------------------
	 * log		name	date					note
	 * @author 	lzhl	2014/10/22				初始化
	 *+----------------------------------------------------------
	 */
	public static function getConfigureField($names = false, $rows = 1, $action, $sku = false) {
		$result ['rows'] = array ();
		if ($action == 'new') {
			$AttrResult = self::getCreateingLProductAttributes ( $names, $attr_str = false, $rows );
			if (! count ( $AttrResult ['configurAttrs'] ) > 0) {
				$result ['rows'] [0] ['Attributes'] = array ();
				$result ['total'] = 1;
				$result ['rows'] [0] ['productStatus'] = 'OS';
				$result ['rows'] [0] ['sku_type'] = 'new';
				$result ['configurAttrs'] = $AttrResult ['configurAttrs'];
				foreach ( $AttrResult ['attrs'] as $key => $value ) {
					// $result['rows'][0][$key] = $value;
					$result ['rows'] [0] ['attr_names'] [] = $key;
				}
			} else {
				$result ['total'] = 1;
				$result ['rows'] [0] ['productStatus'] = 'OS';
				$result ['rows'] [0] ['sku_type'] = 'new';
				$result ['configurAttrs'] = $AttrResult ['configurAttrs'];
				foreach ( $AttrResult ['attrs'] as $key => $value ) {
					// $result['rows'][0]["$key"] = $value;
					$result ['rows'] [0] ['attr_names'] [] = $key;
				}
			}
			$countAttr = count ( $result ['configurAttrs'] );
			if ($countAttr < 3) {
				for($i = 0; $i < 3 - $countAttr; $i ++) {
					$result ['configurAttrs'] [] = array (
							'attr_id' => '',
							'name' => '',
							'values' => array () 
					);
				}
			}
			$result ['rows'] [0] ['sku'] = '';
			if (! empty ( $sku )) {
				$model = Product::findByPk ( $sku );
				if ($model !== null) {
					$result ['productType'] = $model->type;
					$result ['rows'] [0] ['sku'] = $sku;
				} else
					$result ['productType'] = '';
			} else
				$result ['productType'] = '';
			return ($result);
		}
		if ($action == 'add_old' && $sku != '') {
			$model = Product::findByPk ( $sku );
			if ($model == null) {
				$result = self::getConfigureField ( $names, $rows, $action = 'new', $sku );
			} else {
				$skuResult = $model->sku;
				$weightResult = $model->prod_weight;
				$imgResult = $model->photo_primary;
				$statusResult = $model->status;
				$attrsResult = $model->other_attributes;
				if ($attrsResult == '')
					$attrsResult = 'null';
				
				$field = array ();
				$AttrResult = self::getCreateingLProductAttributes ( $names = false, $attrsResult, $rows );
				
				$result ['configurAttrs'] = $AttrResult ['configurAttrs'];
				$result ['rows'] [0] ['sku'] = array (
						'sku' => $skuResult,
						'img' => $imgResult,
						'weight' => $weightResult,
						'Attributes' => $field,
						'productStatus' => $statusResult,
						'sku_type' => 'add_old' 
				);
				$result ['rows'] [0] ['sku'] = $skuResult;
				$result ['rows'] [0] ['img'] = $imgResult;
				$result ['rows'] [0] ['weight'] = $weightResult;
				$result ['rows'] [0] ['productStatus'] = $statusResult;
				$result ['rows'] [0] ['sku_type'] = 'add_old';
				foreach ( $AttrResult ['attrs'] as $key => $value ) {
					$result ['rows'] [0] ["$key"] = $value;
					$result ['rows'] [0] ['attr_names'] [] = $key;
				}
				$result ['productType'] = $model->type;
			}
			$countAttr = count ( $result ['configurAttrs'] );
			if ($countAttr < 3) {
				for($i = 0; $i < 3 - $countAttr; $i ++) {
					$result ['configurAttrs'] [] = array (
							'attr_id' => '',
							'name' => '',
							'values' => array () 
					);
				}
			}
			return $result;
		}
	}
	
	/**
	 * +----------------------------------------------------------
	 * 获取新建变参产品时的用于编辑的attributes的name和所有value
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	names attributes name string like('color,size,brand....')
	 * @param
	 *        	attr_str attributes name:value string('A:a;B:b,C:c....')
	 * @param
	 *        	rows 指定查询的attributes数目
	 *+----------------------------------------------------------
	 * @return array 
	 * +----------------------------------------------------------
	 *	log			name	date					note
	 * @author 		lzhl 	2014/11/3				初始化
	 *+----------------------------------------------------------
	 */
	public static function getCreateingLProductAttributes($names = false, $attr_str = false, $rows) {
		$result = array ();
		$result ['configurAttrs'] = array ();
		$result ['attrs'] = array ();
		if (! $names && $attr_str) {
			if ($attr_str != '') {
				$attr_str = str_replace ( "；", ";", $attr_str );
				$attr_str = str_replace ( "：", ":", $attr_str );
				$attrsArr = explode ( ';', $attr_str );
				for($i = 0; $i < count ( $attrsArr ); $i ++) {
					$attrStr = explode ( ':', $attrsArr [$i] );
					$nameArr [] = $attrStr [0];
					$result ['attrs'] [$attrStr [0]] = $attrStr [1];
				}
				
				$attrIds = Yii::$app->get('subdb')->createCommand ()->select ( 'id,field_name' )->from ( 'pd_product_field' )->where ( array (
						'in',
						'field_name',
						$nameArr 
				) )->order ( 'use_freq DESC' );
				$attrIds = $attrIds->queryAll ();
				$c = 1;
				if (count ( $attrIds ) > 0) {
					foreach ( $attrIds as $aId ) {
						
						// for($i=0;$i<count($attrsArr);$i++){
						$attrIndex = 'Attributes' + $c;
						// $attrStr = explode(':',$attrsArr[$i]);
						// $field["$attrStr[0]"] = array($attrStr[1]);
						
						$fieldValues = Yii::$app->get('subdb')->createCommand ()->select ( 'value,use_freq' )->from ( 'pd_product_field_value' )->where ( "field_id = $aId[id]" )->order ( 'use_freq DESC' );
						$fieldValues = $fieldValues->queryAll ();
						
						if ($fieldValues) {
							foreach ( $fieldValues as $aValue ) {
								$field [$c - 1] [] = array (
										'v' => $aValue ['value'],
										't' => $aValue ['use_freq'] 
								);
							}
							$result ['configurAttrs'] [] = array (
									'attr_id' => $aId ['id'],
									'name' => $aId ['field_name'],
									'values' => $field [$c - 1] 
							);
						} else
							$result ['configurAttrs'] [] = array (
									'attr_id' => $aId ['id'],
									'name' => $aId ['field_name'],
									'values' => array () 
							);
						$c ++;
						// }
					}
				}
			}
			return $result;
		}
		if (! $attr_str) {
			$sql = "SELECT field_name , id FROM pd_product_field ";
			if ($names) {
				$nameStr = "'" . $names . "'";
				$nameStr = str_replace ( ',', "','", $nameStr );
				$sql .= "WHERE field_name in ($nameStr) ";
			}
			$sql .= "ORDER BY use_freq DESC ";
			$sql .= "LIMIT $rows ";
			$command = Yii::$app->get('subdb')->createCommand ( $sql );
			// SysLogHelper::SysLog_Create("product",__CLASS__, __FUNCTION__,"", $command->getText(), "trace");
			$fields = $command->queryAll ();
			if (count ( $fields ) > 0) {
				$c = 1;
				foreach ( $fields as $afield ) {
					$afieldname = $afield ['field_name'];
					$afieldid = $afield ['id'];
					$fieldValues = Yii::$app->get('subdb')->createCommand ()->select ( 'value,use_freq' )->from ( 'pd_product_field_value' )->where ( "field_id = '$afieldid'" )->order ( 'use_freq DESC' );
					$fieldValues = $fieldValues->queryAll ();
					$attrIndex = 'Attributes' + $c;
					if ($fieldValues) {
						foreach ( $fieldValues as $aValue ) {
							$field [$c - 1] [] = array (
									'v' => $aValue ['value'],
									't' => $aValue ['use_freq'] 
							);
						}
						$result ['attrs'] [$afield ['field_name']] = $fieldValues [0] ['value'];
						$result ['configurAttrs'] [] = array (
								'attr_id' => $afield ['id'],
								'name' => $afield ['field_name'],
								'values' => $field [$c - 1] 
						);
					} else
						$result ['configurAttrs'] [] = array (
								'attr_id' => $afield ['id'],
								'name' => $afield ['field_name'],
								'values' => array () 
						);
					$c ++;
				}
			}
			return $result;
		}
	}
	/**
	 * +----------------------------------------------------------
	 * 保存/更新产品时更新属性集数据表
	 *+----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	arrAttr		属性字符串数组
	 *+----------------------------------------------------------
	 * @return 无
	 *+----------------------------------------------------------
	 *	log			name	date					note
	 * @author 		lzhl	2014/11/6				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function updateAttributes($arrAttr) {
		foreach ( $arrAttr as $anAttr ) {
			$KV = explode ( ":", $anAttr );
			$K = $KV [0];
			$V = $KV [1];
			if ($K == '' || $V == '')
				continue;
			else {
				$fieldModel = ProductField::findByAttributes ( array (
						'field_name' => $K 
				) );
				if ($fieldModel == null) {
					$fieldModel = new ProductField ();
					$fieldModel->use_freq = 1;
					$fieldModel->field_name = $K;
					$fieldModel->field_name_eng = 'null'; // 没有相关输入数据，暂时null处理
					$fieldModel->field_name_frc = 'null'; // 没有相关输入数据，暂时null处理
					$fieldModel->field_name_ger = 'null'; // 没有相关输入数据，暂时null处理
					$fieldModel->save ();
				} else {
					$fieldId = $fieldModel->id;
					//$use_freq = $fieldModel->use_freq;
					//$fieldModel->use_freq = $use_freq + 1;
					$fieldModel->field_name_eng = 'null'; // 没有相关输入数据，暂时null处理
					$fieldModel->field_name_frc = 'null'; // 没有相关输入数据，暂时null处理
					$fieldModel->field_name_ger = 'null'; // 没有相关输入数据，暂时null处理
					$fieldModel->save ();
				}
				
				$valueModel = ProductFieldValue::findByAttributes ( array (
						'field_id' => $fieldId,
						'value' => $V 
				) );
				if ($valueModel == null) {
					$valueModel = new ProductFieldValue ();
					$valueModel->field_id = $fieldId;
					$valueModel->value = $V;
					$valueModel->use_freq = 1;
					$valueModel->save ();
				} else {
					//$use_freq = $valueModel->use_freq;
					//$valueModel->use_freq = $use_freq + 1;
					$valueModel->save ();
				}
			}
		}
	}
	
	/**
	 * +----------------------------------------------------------
	 * 保存捆绑产品
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	model		商品模型
	 * @param
	 *        	values	商品属性信息
	 * @param
	 *        	isUpdate	是更新还是新建
	 *+----------------------------------------------------------
	 * @return 返回结果或错误信息 
	 * +----------------------------------------------------------
	 *log			name	date					note
	 * @author 		lzhl	2014/11/12				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function saveBundleProduct($model, $values, $isUpdate = false) {
		self::saveProduct ( $model, $values );
		while ( true ) {
			$bdsku = $values ['Product'] ['sku'];
			$bundleRelationshipStr = trim ( $values ['Product'] ['bundle'] ['relationship'] );
			$bundleRelationshipList = explode ( "&", $bundleRelationshipStr );
			for($i = 0; $i < count ( $bundleRelationshipList ); $i ++) {
				$assku = '';
				$qty = '';
				if ($bundleRelationshipList [$i] == '')
					continue;
				else {
					$RelationFields = explode ( ";", $bundleRelationshipList [$i] );
					for($j = 0; $j < count ( $RelationFields ); $j ++) {
						$FieldKV = explode ( ":", $RelationFields [$j] );
						for($k = 0; $k < count ( $FieldKV ); $k ++) {
							if ($FieldKV [0] == 'sku')
								$assku = $FieldKV [1];
							if ($FieldKV [0] == 'qty')
								$qty = $FieldKV [1];
						}
					}
				}
				$command = Yii::$app->get('subdb')->createCommand ()->insert ( 'pd_product_bundle_relationship', array (
						'bdsku' => $bdsku,
						'assku' => $assku,
						'qty' => $qty,
						'create_date' => date ( 'Y-m-d H:i:s', time () ) 
				) );
				if (! $command)
					return array (
							'错误' => 'relationship create error' 
					);
				else
					continue;
			}
			break;
		}
		return true;
	}
	
	/**
	 * +----------------------------------------------------------
	 * update捆绑产品
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	model		商品模型
	 * @param
	 *        	values	商品属性信息
	 * @param
	 *        	isUpdate	是更新还是新建
	 *+----------------------------------------------------------
	 * @return 返回结果或错误信息 
	 *+----------------------------------------------------------
	 *	log			name	date					note
	 * @author 		lzhl	2014/11/12				初始化
	 *+----------------------------------------------------------
	 *        
	 */
	public static function updateBundleProduct($model, $values, $isUpdate = false) {
		self::saveProduct ( $model, $values, true );
		while ( true ) {
			$farter_sku = $values ['Product'] ['sku'];
			$childrenSkus_had = ProductHelper::getBundleAsSKU ( $farter_sku );
			// SysLogHelper::SysLog_Create("product",__CLASS__, __FUNCTION__,"", print_r($childrenSkus_had,true), "trace");
			$childrenSkuArr = array ();
			foreach ( $childrenSkus_had as $achild ) {
				$childrenSkuArr [] = $achild ['sku'];
			}
			$childrenSkus_had = $childrenSkuArr;
			$asskus_arr = array ();
			$bundleRelationshipStr = trim ( $values ['Product'] ['bundle'] ['relationship'] );
			$bundleRelationshipList = explode ( "&", $bundleRelationshipStr );
			for($i = 0; $i < count ( $bundleRelationshipList ); $i ++) {
				$assku = '';
				$qty = '';
				if ($bundleRelationshipList [$i] == '')
					continue;
				else {
					$RelationFields = explode ( ";", $bundleRelationshipList [$i] );
					for($j = 0; $j < count ( $RelationFields ); $j ++) {
						$FieldKV = explode ( ":", $RelationFields [$j] );
						for($k = 0; $k < count ( $FieldKV ); $k ++) {
							if ($FieldKV [0] == 'sku') {
								$assku = $FieldKV [1];
								$asskus_arr [] = $assku;
							}
							if ($FieldKV [0] == 'qty')
								$qty = $FieldKV [1];
						}
					}
				}
				$HadRelationship = ProductBundleRelationship::findByAttributes ( array (
						'assku' => $assku,
						'bdsku' => $farter_sku 
				) );
				if ($HadRelationship == null) {
					$command = Yii::$app->get('subdb')->createCommand ()->insert ( 'pd_product_bundle_relationship', array (
							'bdsku' => $farter_sku,
							'assku' => $assku,
							'qty' => $qty,
							'create_date' => date ( 'Y-m-d H:i:s', time () ) 
					) );
					if (! $command)
						return array (
								'错误' => 'relationship create error' 
						);
					else
						continue;
				} else {
					$HadRelationship->qty = $qty;
					$HadRelationship->save ();
				}
			}
			$needToDels = array_diff ( $childrenSkus_had, $asskus_arr );
			if (count ( $needToDels ) > 0) {
				foreach ( $needToDels as $delProd ) {
					$relationship = ProductBundleRelationship::findByAttributes ( array (
							'assku' => $delProd,
							'bdsku' => $farter_sku 
					) );
					$relationship->delete ();
				}
			}
			break;
		}
		return true;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 获取产品参与的捆绑产品 sku
	 * 返回该 sku 的pd_product_bundle_relationship 表中的 所有父sku
	 * 如果该产品非捆绑子产品，则返回空
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku			商品SKU
	 *+----------------------------------------------------------
	 * @return sku数组 或 空字符串
	 *+----------------------------------------------------------
	 *	log			name		date				note
	 * @author 		lzhl 		2014/11/12			初始化
	 *+----------------------------------------------------------
	 */
	public static function getBundleProductSKUs($sku) {
		$result = array ();
		$bundle_ships = ProductBundleRelationship::find ( 'assku = :sku', array (
				':sku' => $sku 
		) );
		if (empty ( $bundle_ships )) {
			return $result;
		} else {
			foreach ( $bundle_ships as $aship ) {
				$result [] = $aship->bdsku;
			}
		}
		return $result;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 获取捆绑父产品的子产品 sku和对应qty
	 * 如果该产品非捆绑产品，则返回空数组
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku			商品SKU
	 *+----------------------------------------------------------
	 * @return array sku数组
	 *+----------------------------------------------------------
	 *         log			name			date			note
	 * @author lzhl 2014/11/12			初始化
	 *+----------------------------------------------------------
	 */
	public static function getBundleAsSKU($sku) {
		$result = array ();
		$bundle_ships = ProductBundleRelationship::findAll ( 'bdsku = :sku', array (
				':sku' => $sku 
		) );
		if (empty ( $bundle_ships )) {
			return $result;
		} else {
			foreach ( $bundle_ships as $aship ) {
				$result [] = array (
						'sku' => $aship->assku,
						'qty' => $aship->qty 
				);
			}
		}
		return $result;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 保存网站与eagle中某商品的关联关系
	 * 
	 * 假如 接入新平台 需要 在C , F 处 增加对应平台的处理 
	 *
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	productid product id
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
	 * @author 		lkh 	2014/10/23				初始化
	 *+----------------------------------------------------------
	 */
	static function saveRelationProduct($productid, $sku, $type = 'ebay', $pkid = null) {
		$productid = trim ( $productid );
		$sku = trim ( $sku );
		$type = strtolower($type);
		//A check up this product id whether active
		$alias_root_sku = self::getRootSkuByAlias ( $productid );
		
		//B if this product id is active , then skip it
		if (! empty ( $alias_root_sku ))
			return array (
					'message' => 'aliasexist' 
			);
			
			//C check up this sku whether active
		$root_sku = self::getRootSkuByAlias ( $sku );
		$model = new Product ();
		
		if (empty ( $root_sku )) {
			//C here sku is not active , create this product
			if ($type == 'ebay') {
				
				//C1-1 get product data
				$get_product_data = EbayItem::find ( 'itemid=:itemid', array (
						':itemid' => $productid 
				) );
				
				//C1-2 create product
				$product_info ['Product'] ['sku'] = $sku;
				$product_info ['Product'] ['name'] = $get_product_data->itemtitle;
				// $product_info['Product']['prod_name_en'] = $get_product_data->itemtitle;
				// $product_info['Product']['prod_name_ch'] = $sku;
				// $product_info['Product']['declaration_ch'] = $sku;
				
				$product_info ['Product'] ['photo_primary'] = $get_product_data->mainimg;
				if ($root_sku != $productid) {
					$product_info ['ProductAliases'] ['alias_sku'] [] = $productid;
					$product_info ['ProductAliases'] ['pack'] [] = 1;
					$product_info ['ProductAliases'] ['forsite'] [] = '';
					$product_info ['ProductAliases'] ['comment'] [] = 'ebay:' . $get_product_data->selleruserid;
					$result ['message'] = 'sku_alias';
				} else {
					$result ['message'] = 'alias';
				}
			} else if ($type == 'amazon') {
				
				if (! empty ( $pkid )) {
					//C2-1-a get product data
					$get_product_data = AmazonItem::find( 'id=:itemid', array (
							':itemid' => $pkid 
					) );
				} else {
					//C2-1-b get product data
					$get_product_data = AmazonItem::find ( 'ASIN=:itemid', array (
							':itemid' => $productid 
					) );
				}
				
				//C2-2 create product
				$product_info ['Product'] ['sku'] = $sku;
				
				$product_info ['Product'] ['name'] = ((strlen ( $get_product_data->Title ) > 255) ? substr ( $get_product_data->Title, 0, 255 ) : $get_product_data->Title);
				
				if (! empty ( $get_product_data->SmallImage )) {
					$smallimage = json_decode ( $get_product_data->SmallImage, true );
					if (! empty ( $smallimage ['ns2_URL'] )) {
						$SmallImageUrl = $smallimage ['ns2_URL'];
					} else {
						$SmallImageUrl = '';
					}
				}
				
				$product_info ['Product'] ['photo_primary'] = $SmallImageUrl;
				if ($root_sku != $productid) {
					if ($sku == $get_product_data->ASIN) {
						$result ['message'] = 'sku';
					} else {
						$product_info ['ProductAliases'] ['alias_sku'] [] = $productid;
						$product_info ['ProductAliases'] ['pack'] [] = 1;
						$product_info ['ProductAliases'] ['forsite'] [] = '';
						$product_info ['ProductAliases'] ['comment'] [] = 'amazon';
						$result ['message'] = 'sku_alias';
					}
				} else {
					$result ['message'] = 'alias';
				}
			}elseif ($type == 'wish'){
				//wish  @todo 
				//C3-1 start to get product data
				//C3-1-a get variance data 
				$wish_variance_data = WishFanbenVariance::find()
				->andWhere(['variance_product_id'=>$productid])
				->asArray()
				->One();
				
				$wish_fanben_data = WishFanben::find()
				->andWhere(['parent_sku'=>$wish_variance_data['parent_sku']])
				->asArray()
				->One();
				//C3-1-b get fanben data
				//C3-1 end of get product data
				
				//C3-2 create product
				$product_info ['Product'] ['sku'] = $sku;
				
				$product_info ['Product'] ['name'] = ((strlen ( $wish_fanben_data['name'] ) > 255) ? substr ( $wish_fanben_data['name'], 0, 255 ) : $wish_fanben_data['name']);
				
				$product_info ['Product']['photo_others'] = '';
				
				$product_info ['Product'] ['photo_primary'] = $wish_fanben_data['main_image'];
				
				if (strtoupper($wish_variance_data['enable']) == 'Y')
					$product_info ['Product']['status'] = 'OS';
				else
					$product_info ['Product']['status'] = 'DR';
				
				//check the other photo 
				for($i=1;$i<=10;$i++){
					// extra image not empty , then set product photo others 
					if (!empty($wish_fanben_data['extra_image_'.$i])){
						$product_info ['Product']['photo_others'] .= empty($product_info ['Product']['photo_others'] )?"":"@,@";
						$product_info ['Product']['photo_others'] .= $wish_fanben_data['extra_image_'.$i];
					}
				}
				
				$product_info ['Product']['photo_others'] = [];
				
				if ($root_sku != $productid) {
					$product_info ['ProductAliases'] ['alias_sku'] [] = $productid;
					$product_info ['ProductAliases'] ['pack'] [] = 1;
					$product_info ['ProductAliases'] ['forsite'] [] = '';
					
					$store_name = SaasWishUser::find()
					->select(['store_name'])
					->Where(['site_id'=>$wish_fanben_data ['site_id']])
					->asArray()
					->One();
					$product_info ['ProductAliases']['comment'] = 'wish:'.(empty($store_name['store_name'])?"":$store_name['store_name']);
					$result ['message'] = 'sku_alias';
				} else {
					$result ['message'] = 'alias';
				}
				
			}
			
			
			$product_info ['Product'] ['create_source'] = $type; // set up create_source
			$product_info ['Product'] ['type'] = 'S';
			self::saveProduct ( $model, $product_info );
			return $result;
		} else {
			//D if root sku equal producted then skip it .
			if ($root_sku == $productid)
				return array (
						'status' => false,
						'message' => 'skuexist' 
				);
				
				//E here sku is active , then add alias
			$has_alias = ProductAliases::find ( 'alias_sku = :alias_sku', array (
					':alias_sku' => $productid 
			) );
			
			if (empty ( $has_alias )) {
				
				//F get product data
				if ($type == 'ebay') {
					
					$get_product_data = EbayItem::find ( 'itemid=:itemid', array (
							':itemid' => $productid 
					) );
					$aliasInfo ['comment'] = 'ebay:' . $get_product_data->selleruserid;
				} elseif ($type == 'amazon') {
					$get_product_data = AmazonItem::find ( 'product_id=:itemid', array (
							':itemid' => $productid 
					) );
					$aliasInfo ['comment'] = 'amazon';
				}elseif($type == 'wish'){
					//@todo
					$site_id = Yii::$app->get('subdb')->createCommand("select site_id from wish_fanben_variance v , wish_fanben f   where f.parent_sku = v.parent_sku and   v.variance_product_id = '".$productid."'")->queryScalar();
					$store_name = SaasWishUser::find()
					->select(['store_name'])
					->Where(['site_id'=>$site_id])
					->asArray()
					->One();
					$aliasInfo ['comment'] = 'wish:'.(empty($store_name['store_name'])?"":$store_name['store_name']);
				}
				
				$aliasInfo ['alias'] = $productid;
				$aliasInfo ['pack'] = 1;
				$aliasInfo ['forsite'] = '';
				
				self::addonealias ( $root_sku, $aliasInfo );
				$result ['message'] = 'alias';
				return $result;
			} else {
				return array (
						'status' => false,
						'message' => 'aliasexist' 
				);
			}
		}
	} // end of saveRelationProduct
	
	/**
	 * +----------------------------------------------------------
	 * 新增一个商品别名，并检查新增别名是否商品sku ， 是则合并
	 *
	 * +----------------------------------------------------------
	 * 
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku 商品SKU
	 * @param
	 *        	aliasInfo		商品别名 信息 array(alias=>商品别名 ，pack =>数量，forsite=>来源网站，comment=>备注)
	 *+----------------------------------------------------------
	 * @return na 
	 * +----------------------------------------------------------
	 *	log			name	date					note
	 * @author 		lkh 	2014/10/28				初始化
	 *+----------------------------------------------------------
	 */
	static function addonealias($root_sku, $aliasInfo) {
		$model = new ProductAliases ();
		$model->sku = $root_sku;
		$model->alias_sku = $aliasInfo ['alias'];
		$model->pack = $aliasInfo ['pack'];
		$model->forsite = $aliasInfo ['forsite'];
		$model->comment = $aliasInfo ['comment'];
		
		if ($model->save ()) {
			// check the product id whether product sku
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( "sku='" . $aliasInfo ['alias'] . "'" );
			$merge_alias_list = Product::findall ( $criteria );
			
			foreach ( $merge_alias_list as $one_merge_alias ) {
				// update alias related data
				self::updateAliasRelatedData ( $model->sku, $one_merge_alias->sku );
			}
			return array (
					'status' => true,
					'message' => '创建成功');
    					}else{
    						return array('status'=>false,'message'=>'创建失败');
    					}
	}//end of addonealias
	
	/**
	 * +----------------------------------------------------------
	 * product other attribute string 转成 array
	 *
	 * +----------------------------------------------------------
	 *
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku 商品SKU
	 * @param
	 *        	aliasInfo		商品别名 信息 array(alias=>商品别名 ，pack =>数量，forsite=>来源网站，comment=>备注)
	 *+----------------------------------------------------------
	 * @return na
	 * +----------------------------------------------------------
	 *	log			name	date					note
	 * @author 		lkh 	2015/03/14				初始化
	 *+----------------------------------------------------------
	 */
	static public function PordAttrconvertStringToArray($strAttr){
		$attrList = explode(';', $strAttr);
		//if (count($attrList) > 0) {
		foreach ($attrList as $attr)
		{
			$tmpKv = explode(':', $attr);
			$ArrAttr [] = array_combine(['key', 'value'] , $tmpKv);
		}
		return $ArrAttr;
	}//end of PordAttrconvertStringToArray
	
	/**
	 * +----------------------------------------------------------
	 * 根据参数sku 获取与参数 sku相关的商品信息
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 *        	sku 待检测的商品sku
	 *+----------------------------------------------------------
	 * @return array(
	 *	Sku=>’sk1’,’name’=>’computer’,...  Type=’Bundle’, 
	 *	Children = ‘0’=>[sku=’’, name=’’] , ‘1’=>[sku=’’,name=’’]
	 *	)
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh 	2015/04/30				初始化
	 *+----------------------------------------------------------
	 */
	static public function getProductInfo($sku){
		global $CACHE;
		//$root_sku = self::getRootSkuByAlias($sku);
		$root_sku = $sku;
		// get product info 
		//2016-07-04  为后台检测订单加上global cache 的使用方法 start
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if (isset($CACHE[$uid]['product'][$root_sku])){
			$prodInfo = $CACHE[$uid]['product'][$root_sku];
			
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' product has cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}else{
			$prodInfo = Product::find()->andWhere(['sku'=>$root_sku])->asArray()->One();
			
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' product no cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
			
		}
		
		//2016-07-04  为后台检测订单加上global cache 的使用方法 end
		switch (strtoupper($prodInfo['type'])){
			case "B" : 
				// bundle product , then get it children
				$prodInfo['children'] = [];
				//2016-07-04  为后台检测订单加上global cache 的使用方法 start
				if (isset($CACHE[$uid]['bundleRelation'])){
					$childrens = $CACHE[$uid]['bundleRelation'][$prodInfo['sku']];
					
					//log 日志 ， 调试相关信息start
					$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' bundleRelation has cache';
					\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
					//log 日志 ， 调试相关信息end
				}else{
					$childrens = ProductBundleRelationship::find()->where(['bdsku'=>$prodInfo['sku']])->asArray()->all();
					
					//log 日志 ， 调试相关信息start
					$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' bundleRelation no cache';
					\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
					//log 日志 ， 调试相关信息end
				}
				//2016-07-04  为后台检测订单加上global cache 的使用方法 end
				
				foreach ($childrens as $child){
					//2016-07-04  为后台检测订单加上global cache 的使用方法 start
					if (isset($CACHE[$uid]['bundleRelation'])){
						$childInfo = $CACHE[$uid]['product'][$child['assku']];
						
						//log 日志 ， 调试相关信息start
						$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' child product has cache';
						\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
						//log 日志 ， 调试相关信息end
					}else{
						$childInfo = Product::find()->where(['sku'=>$child['assku']])->asArray()->one();
						
						//log 日志 ， 调试相关信息start
						$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' child product no cache';
						\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
						//log 日志 ， 调试相关信息end
					}
					//2016-07-04  为后台检测订单加上global cache 的使用方法 end
					if(empty($childInfo))
						break;
					$row=$childInfo;
					$row['qty']=$child['qty'];
					$prodInfo['children'][]=$row;
				}
				break;
			case "C" :
				// configure product , then get it configure filed
				$prodInfo['children'] = [];
				//2016-07-04  为后台检测订单加上global cache 的使用方法 start
				if (isset($CACHE[$uid]['configRelation'])){
					$childrens = $CACHE[$uid]['configRelation'][$prodInfo['sku']];
					
					//log 日志 ， 调试相关信息start
					$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' configRelation has cache';
					\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
					//log 日志 ， 调试相关信息end
				}else{
					$childrens = ProductConfigRelationship::find()->where(['cfsku'=>$prodInfo['sku']])->asArray()->all();
					
					//log 日志 ， 调试相关信息start
					$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' configRelation no cache';
					\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
					//log 日志 ， 调试相关信息end
				}
				//2016-07-04  为后台检测订单加上global cache 的使用方法 end
				
				foreach ($childrens as $child){
					//2016-07-04  为后台检测订单加上global cache 的使用方法 start
					if (isset($CACHE[$uid]['configRelation'])){
						$childInfo = $CACHE[$uid]['product'][$child['assku']];
					}else{
						$childInfo = Product::find()->where(['sku'=>$child['assku']])->asArray()->one();
					}
					//2016-07-04  为后台检测订单加上global cache 的使用方法 end
					
					if(empty($childInfo))
						break;
					$row=$childInfo;
					$row['qty']=1;
					$prodInfo['children'][]=$row;
				}
				break;
			default:
				//here normal product , nothing to do 
				//$prodInfo['type_label'] = 'normal';
		}
		return $prodInfo;
	}//end of getProductInfo
	
	
	/**
	 * +----------------------------------------------------------
	 * 根据参数检查产品别名是否有效
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param
	 * 			$sku		待检测的商品sku
	 *        	$aliasList 	待检测的 alias 
	 *+----------------------------------------------------------
	 * @return array(
	 *	'success'=>’true’,’message’=>’没有找到别名’
	 *	)
	 *	@success boolean 检查是否通过  true 为通过  false 不通过 
	 *	@message string   当alias 不通过 时候 的原因
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh 	2015/05/05				初始化
	 *+----------------------------------------------------------
	 */
	static public function checkAlias($sku, $aliasList){
		try {
			// 判断 sku 是否有效
			if (!empty($sku)){
				// 判断 alias 是否有效
				if (isset($aliasList)   ){
					if (is_array($aliasList)){
						// 批量 检测 alias
						foreach($aliasList as $oneAlias){
							$PDAliasList = $oneAlias;
							$result = self::checkProductAlias($sku, $PDAliasList);
							if ($result ['status'] == "failure"){
								$result ['success'] = false;
								break;
							}
						}
						 
					}else{
						// 单个 检测 alias
						$PDAliasList = $aliasList;
						$result = self::checkProductAlias($sku, $PDAliasList);
					}
						
				}else{
					// alias 无效
					$result = array(
							'success'=>false ,
							'message'=>'没有找到别名' ,
					);
				}
		
			}else{
				//sku 无效
				$result = array(
						'success'=>false ,
						'message'=>'没有指定商品' ,
				);
			}
			 
		} catch (Exception $e) {
			// 未知错误
			$result = array(
					'success'=>false ,
					'message'=>$e->getMessage() ,
			);
		}
		return $result;
	}// end of checkAlias
	
	/**
	 * 移除指定sku相关的config关系
	 * @access static
	 * @param	$sku	待处理的商品sku
	 *        	$type 	'cfsku'->父sku ；'assku'->子sku
	 * @return  true
	 * log		name	date		note
	 * @author 	lzhl 	2015/05/05	初始化
	 */
	public static function removeConfigRelationship($sku,$type){
		if($type =='cfsku'){
			//将其所有子产品转换成普通类型商品
			$relationship = ProductConfigRelationship::findAll(['cfsku'=>$sku]);
			foreach ($relationship as $relation){
				$Child = Product::findOne(['sku'=>$relation->assku]);
				if(!empty($Child)){
					$Child->type='S';
					$Child->save(false);
				}
			}
			//删除变参关系
			ProductConfigRelationship::deleteAll(['cfsku'=>$sku]);
		}
		if($type =='assku'){
			ProductConfigRelationship::deleteAll(['assku'=>$sku]);
		}
	}
	
	/**
	 * 移除指定sku相关的bundle关系
	 * @access static
	 * @param	$sku	待处理的商品sku
	 *        	$type 	'cfsku'->父sku ；'assku'->子sku
	 * @return  true
	 * log		name	date			note
	 * @author 	lzhl 	2015/05/013		初始化
	 */
	public static function removeBundleRelationship($sku,$type){
		if($type =='bdsku'){
			//将其所有子产品转换成普通类型商品
			$relationship = ProductBundleRelationship::findAll(['bdsku'=>$sku]);
			foreach ($relationship as $relation){
				$Child = Product::findOne(['sku'=>$relation->assku]);
				if(!empty($Child)){
    				$Child->type='S';
    				$Child->save(false);
				}
			}
			//删除捆绑关系
			ProductBundleRelationship::deleteAll(['bdsku'=>$sku]);
		}
		if($type =='assku'){
			ProductBundleRelationship::deleteAll(['assku'=>$sku]);
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
	 * +----------------------------------------------------------
	 * 商品导出Excel
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	$data    导出的数据集Id或者筛选信息
	 * @param	$type    是否前端导出
	 *+----------------------------------------------------------
	 * @return 	
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lrq		2016/12/14				初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function ExportProductExcel($data, $type = false){
		$rtn['success'] = 1;
		$rtn['message'] = '';
		
		try{
		    $products = array();
    		$journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ ,array($data));
    		
    		$product_ids = array();
    		if(!empty($data)){
    		    foreach($data as $v){
    		        $product_ids[] = $v;
    		    }
    		}
    		
    		if(count($product_ids) == 11 && $product_ids[0] == 'search contidion'){
    		    
    		    $condition = array();
    		    if (isset($product_ids[1])){
    		        $val = $product_ids[1];
    		    	if (trim($val)!="" ){
    		    		$val = trim($val);
    		    		if (!empty($product_ids[9])){
    		    			if($product_ids[9] == 'sku'){
    		    				$condition [] = ['or'=>['like','sku', $val]];
    		    				$condition [] = ['or'=>'sku in (select `sku` from `pd_product_aliases` where `alias_sku`=\''.$val.'\')'];
    		    			}
    		    			else{
    		    				$condition [] = ['or'=>['like',$product_ids[9], $val]];
    		    			}
    		    		}
    		    		else{
	    		    		$condition [] = ['or'=>['like','sku', $val]];
	    		    		$condition [] = ['or'=>['like','name', $val]];
	    		    		$condition [] = ['or'=>['like','prod_name_ch', $val]];
	    		    		$condition [] = ['or'=>['like','prod_name_en', $val]];
	    		    		$condition [] = ['or'=>['like','declaration_ch', $val]];
	    		    		$condition [] = ['or'=>['like','declaration_en', $val]];
	    		    		$condition [] = ['or'=>'sku in (select `sku` from `pd_product_aliases` where `alias_sku`=\''.$val.'\')'];
	    		    		//$condition [] = ['or'=>'sku=(select `cfsku` from `pd_product_config_relationship` where `assku`=\''.$val.'\')'];
    		    		}
    		    	}
    		    }
    		    
    		    if (isset($product_ids[2])){
    		        $val = $product_ids[2];
    		    	if (trim($val)!="" && $val != "all"){
    		    		//增加 tag 查询条件
    		    		$condition [] = ['and'=>['in', 'sku', (new Query())->select('sku')->from('pd_product_tags')->where(['tag_id' => $val])]];
    		    	}
    		    }
    		    
    		    if (isset($product_ids[3])){
    		        $val = $product_ids[3];
    		    	if (trim($val)!="" && $val != "all"){
    		    		//增加 brand 查询条件
    		    		$condition [] = ['and'=>['brand_id'=> $val ]];
    		    	}
    		    }
    		    
    		    if (isset($product_ids[4])){
    		        $val = $product_ids[4];
    		    	if (trim($val)!="" && $val != "all"){
    		    		//增加 supplier 查询条件
    		    		if(is_numeric($val) && !empty($val)){
    		    			$condition [] = ['and'=>['in', 'sku', (new Query())->select('sku')->from('pd_product_suppliers')->where(['supplier_id' => $val])]];
    		    		}
    		    		if(empty($val)){
    		    			$condition [] = ['and'=>[
    		    			'or',['sku'=>(new Query())->select('sku')->from('pd_product_suppliers')->where(['supplier_id' => $val])],['supplier_id'=>0]
    		    					]];
    		    		}
    		    	}
    		    }
    		    
    	    	if (isset($product_ids[5])){
    	    	    $val = $product_ids[5];
    		    	if (trim($val)!="" && $val != "all"){
    		    	//增加 status 查询条件
    		    	$condition [] = ['and'=>['status'=>$val ]];
    		    	}
    	    	}
    	    	if (isset($product_ids[6])){
    	    	    $val = $product_ids[6];
    		    	if (trim($val) != "" && $val != "all"){
        			//增加 type 查询条件
    		        	$condition [] = ['and'=>['type'=>$val ]];
    		    	}
    	    	}
    	    	if (isset($product_ids[10]) && $product_ids[10] != ''){
    	    		//增加 class_id 查询条件
    	    		$condition [] = ['and'=>['class_id'=>$product_ids[10] ]];
    	    	}
    		    
    	    	$data = ProductHelper::getProductlist($condition, $product_ids[7], $product_ids[8], 20, true);
    		    $product_ids = array();
    		    foreach ($data['data'] as $d){
    		        //只显示变参子产品，不显示变参商品
    		        if($d['type'] == 'C')
    		            continue;
    		        
    		        $product_ids[] = $d['product_id'];
    		    	$products[$d['product_id']] = $d;
    		    }
    		    unset($data);
    		}
    		else{
    		    $skus = array();
    		    $pro_sku = array();
    		    $condition = array();
    		    foreach($product_ids as $id){
    		    	$condition[] = ['or'=>"product_id=$id"];
    		    }
    		    $data = ProductHelper::getProductlist($condition, 'sku', 'asc', 20, true);
        		foreach ($data['data'] as $d){
        		    $products[$d['product_id']] = $d;
        		    $skus[] = $d['sku'];
        		    $pro_sku[$d['sku']] = $d;
        		}
        		unset($data);
        		
        		//变参子产品
        		$realArr = array();
        		$relationship = ProductConfigRelationship::find()->where(['cfsku'=>$skus])->asArray()->all();
        		foreach($relationship as $r){
        			$realArr[$r['cfsku']][] = $r['assku'];
        		}
        		
        		//如果是变参商品，则显示变参子产品，不显示变参商品
        		$ids = $product_ids;
        		$product_ids = array();
        		foreach ($ids as $id){
        		    if(!empty($products[$id])){
        		        $p = $products[$id];
        		        if($p['type'] == 'C' && !empty($realArr[$p['sku']])){
        		            foreach ($realArr[$p['sku']] as $r){
        		                if(!empty($pro_sku[$r])){
        		                    $product_ids[] = $pro_sku[$r]['product_id'];
        		                }
        		            }
        		        }
        		        else{
        		            $product_ids[] = $id;
        		        }
        		    }
        		}
        		unset($pro_sku);
    		}
    		
    		$items_arr = ['sku'=>'SKU', 'name'=>'商品名称', 'class_name'=>'分类', 'brand_name'=>'品牌', 'prod_name_ch'=>'中文配货名称', 'prod_name_en'=>'英文配货名称', 'declaration_ch'=>'中文报关名', 'declaration_en'=>'英文报关名', 'declaration_value'=>'申报金额', 'declaration_value_currency'=>'申报货币', 'prod_weight'=>'重量', 'prod_length'=>'长(cm)', 'prod_width'=>'宽(cm)', 
    		                'prod_height'=>'高(cm)', 'other_attributes'=>'属性', 'supplier_name'=>'首选供应商', 'purchase_price'=>'采购价(CNY)', 'photo_primary'=>'主图片', 'photo_2'=>'图片2', 'photo_3'=>'图片3', 'photo_4'=>'图片4', 'photo_5'=>'图片5', 'tags'=>'产品标签', 'alias_sku'=>'商品别名', 'declaration_code'=>'报关编码', 'purchase_link'=>'采购链接', 'comment'=>'备注'];
    		
    		$excel_file_name = array();
    		$excel_data = array();
    		$skus = array();
    		$keys = array_keys($items_arr);
    		
    		//sku集合
    		$sku_list = array();
    		foreach ($products as $val){
    		    $sku_list[] = $val['sku'];
    		}
    		//品牌
    		$brandList = BrandHelper::ListBrandData();
    		//供应商
    		$supplierList = ProductSuppliersHelper::ListSupplierData();
    		//标签
    		$tags = array();
    		$tagArr = Tag::find()->asArray()->All();
    		foreach ($tagArr as $t){
    		    $tags[$t['tag_id']] = $t['tag_name'];
    		}
    		//所有分类信息
    		$class_id_arr = array();
    		$class_number_arr = array();
    		$classlist = ProductClassification::find()->asArray()->All();
    		foreach ($classlist as $class){
    			$class_id_arr[$class['ID']] = $class['number'];
    			$class_number_arr[$class['number']] = $class;
    		}
    		
    		$ptagList = array();
    		$pro_tagArr = ProductTags::find()->select(['tag_id', 'sku'])->Where(['sku'=>$sku_list])->asArray()->All();
    		foreach ($pro_tagArr as $t){
    		    if(!empty($tags[$t['tag_id']])){
    		        $sku = strtolower($t['sku']);
    		        $name = $tags[$t['tag_id']];
    		        if(empty($ptagList[$sku]) || !in_array($name, $ptagList[$sku])){
    		            $ptagList[$sku][] = $name;
    		        }
    		    }
    		}
    		
    		//别名
    		$aliasList = array();
    		$aliasArr = ProductAliases::find()->select(['sku','alias_sku'])
    			->Where(['sku'=>$sku_list])
    			->andWhere("sku!=alias_sku")
    			->asArray()->All();
    		foreach ($aliasArr as $a){
    			$aliasList[strtolower($a['sku'])][] = $a['alias_sku'];
    		}
    		
    		//图2图3
    		$photoList = array();
    		$photoArr = Photo::find()->select(['sku','photo_url'])
	    		->Where(['sku'=>$sku_list])
	    		->andWhere("priority!=0")
	    		->orderBy("priority")
	    		->asArray()->All();
    		foreach ($photoArr as $p){
    			$photoList[strtolower($p['sku'])][] = $p['photo_url'];
    		}
    		
    		foreach ($product_ids as $index => $id){
    		    $p = $products[$id];
    		    $sku_low = strtolower($p['sku']);
    		    
    		    if(in_array($sku_low, $skus))
    		        continue;
    		    
    		    $tmp = [];
    		    foreach ($keys as $key){
    		        if(isset($p[$key])){
    		            if(in_array($key, ['sku'])){
    		                $tmp[$key] = ' '.$p[$key];
    		            }
    		            else{
    		                $tmp[$key] = $p[$key];
    		            }
    		        }
    		        else{
    		            $tmp[$key] = ' ';
    		        }
    		        
    		        if($index == 0){
    		        	$excel_file_name[] = $items_arr[$key];
    		        }
    		    }
    		    
    		    //系统自带no-img.png，不需导出
    		    if(!empty($tmp['photo_primary']) && str_replace("no-img.png", "", $tmp['photo_primary']) != $tmp['photo_primary']){
    		        $tmp['photo_primary'] = '';
    		    }
    		    
    		    //标签
    		    if (!empty($ptagList[$sku_low])){
    		        foreach ($ptagList[$sku_low] as $v){
    		            $tmp['tags'] = $tmp['tags'] == ' ' ? $v :$tmp['tags'].','.$v; 
    		        }
    		    }
    		    
    		    //首选供应商
    		    if (!empty($supplierList[$p['supplier_id']]['name'])){
    		    	$tmp['supplier_name'] = $supplierList[$p['supplier_id']]['name'];
    		    }
    		    
    		    //品牌
    		    if (!empty($brandList[$p['brand_id']]['name'])){
    		    	$tmp['brand_name'] = $brandList[$p['brand_id']]['name'];
    		    }
    		    
    		    //别名
    		    if (!empty($aliasList[$sku_low])){
    		    	foreach ($aliasList[$sku_low] as $v){
    		    		$tmp['alias_sku'] = $tmp['alias_sku'] == ' ' ? $v :$tmp['alias_sku'].','.$v;
    		    	}
    		    }
    		    //分类
    		    if (!empty($p['class_id']) && array_key_exists($p['class_id'], $class_id_arr)){
    		    	$number = $class_id_arr[$p['class_id']];
    		    	$name_str = '';
    		    	//查询类别集合名称
    		    	for($n = 1; $n < 6; $n++){
    		    		if(array_key_exists($number, $class_number_arr)){
    		    			$name_str = $class_number_arr[$number]['name'].','.$name_str;
    		    			$number = $class_number_arr[$number]['parent_number'];
    		    			
    		    			if(empty($number)){
    		    				break;
    		    			}
    		    		}
    		    		else{
    		    			break;
    		    		}
    		    	}
    		    	
	    		    $tmp['class_name'] = rtrim($name_str, ",");
    		    }
    		    
    		    //图2图3图4
    		    $tmp['photo_2'] = empty($photoList[$sku_low][0]) ? '' : $photoList[$sku_low][0];
    		    $tmp['photo_3'] = empty($photoList[$sku_low][1]) ? '' : $photoList[$sku_low][1];
    		    $tmp['photo_4'] = empty($photoList[$sku_low][2]) ? '' : $photoList[$sku_low][2];
    		    $tmp['photo_5'] = empty($photoList[$sku_low][3]) ? '' : $photoList[$sku_low][3];
    		    
    		    $skus[] = $sku_low;
    		    $excel_data[$index] = $tmp;
    		    
    		    unset($p);
    		    unset($tmp);
    		}
    		unset($product_ids);
    		
    		\Yii::info('ExportProductExcel, puid: '.\Yii::$app->subdb->getCurrentPuid().', count: '. count($excel_data), "file");
    		
    		//$sheetInfo = [['data_array'=>$excel_data , 'filed_array'=>$excel_file_name,'title'=>'Sheet1']];
    		//$rtn = ExcelHelper::exportToExcel($excel_data, $excel_file_name,'productL_'.date('Y-m-dHis',time()).".xls", ['photo_primary'=>['width'=>50,'height'=>50]],$type);
    		$rtn = ExcelHelper::exportToExcel($excel_data, $excel_file_name,'productL_'.date('Y-m-dHis',time()).".xls", [], $type);
    		
    		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
    		$rtn['count'] = count($excel_data);
    		unset($excel_data);
		}
		catch (\Exception $e) {
			$rtn['success'] = 0;
			$rtn['message'] = '导出失败：'.$e->getMessage();
		}
		
		return $rtn;
	}
	/**
	 * +----------------------------------------------------------
	 * 合并商品
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	$merge_sku    string    目的主SKU
	 * @param	$be_sku_arr   array     被合并SKU组
	 *+----------------------------------------------------------
	 * @return
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lrq		2017/05/02				初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function MergeProduct($merge_sku, $be_sku_arr){
	    $ret['success'] = true;
	    $ret['msg'] = '';
	    $edit_log = '';
	    $log_key_id = '';
	    
	    if(in_array(trim($merge_sku), $be_sku_arr)){
	    	$ret['success'] = false;
	    	$ret['msg'] .= "合并SKU不能存在被合并内！";
	    	return $ret;
	    }
	    //判断是否属于商品库的普通商品
	    $model = Product::find()->where(['sku' => $merge_sku])->andWhere("type='S'||type='L'")->one();
	    if(!empty($model)){
	    	$exist_sku = array();
    	    foreach($be_sku_arr as $sku){
    	    	if(in_array($sku, $exist_sku)){
    	    		continue;
    	    	}
    	        //判断是否属于商品库
    	        $model = Product::find()->where(['sku' => $sku])->andWhere("type='S'||type='L'")->one();
    	        if(!empty($model)){
    	            $journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ ,['merge_sku' => $merge_sku, 'sku' => $sku]);
    	            
        	        $re = self::updateAliasRelatedData($merge_sku, $sku);
        	        
        	        if(!empty($re) && !empty($re['错误'][0])){
        	            $ret['success'] = false;
        	            $ret['msg'] .= "被合并SKU：<span style='color: red;'>$sku </span>合并失败：".$re['错误'][0]."<br>";
        	            SysLogHelper::InvokeJrn_UpdateResult($journal_id, $ret);
        	        }
        	        else{
        	            SysLogHelper::InvokeJrn_UpdateResult($journal_id, ['success' => 1, 'msg' => '']);
        	            
        	            //记录修改日志
        	            $edit_log .= $sku.", ";
        	        }
    	        }
    	        else{
    	        	$ret['success'] = false;
    	        	$ret['msg'] .= "被合并SKU：<span style='color: red;'>$sku </span>不属于商品库的普通商品或者变参子产品<br>";
    	        }
    	        $exist_sku[] = $sku;
    	    }
    	    
    	    if(!empty($edit_log)){
    	    	//写入操作日志
    	    	$edit_log = "合并商品, 目的SKU: $merge_sku, 被合并SKU: $edit_log";
    	    	$log_key_id = $model->product_id;
    	    	UserHelper::insertUserOperationLog('catalog', $edit_log, null, $log_key_id);
    	    }
	    }
	    else{
	        $ret['success'] = false;
	        $ret['msg'] = "目的本地SKU：<span style='color: red;'>$merge_sku </span>不属于商品库的普通商品或者变参子产品";
	    }
	    
	    return $ret;
	}
	
	//获取商品分类信息
	public static function GetProductClass($type = 0, $classCount = array()){
	    //只显示三级
	    $query = self::GetProductClassQuery();
	    $class = $query->asArray()->all();
	    $class_arr = self::GetChliNode('', $class);
		
		//整理节点html
		if($type == 1){
			$html = self::ProductClassificaSettingHtml($class_arr);
		}
		else{
			$html = self::ProductClassificaHtml($class_arr, $classCount);
		}
		//print_r($html);die;
		return $html;
	}
	
	public static function GetProductClassQuery(){
		$query = ProductClassification::find()->where('length(number)<=6')->orderBy('number');
		return $query;
	}
	
	public static function GetChliNode($number, $list){
	    $node = [];
	    foreach($list as $key => $arr){
	        $parent_number = empty($arr['parent_number']) ? '' : $arr['parent_number'];
	        if($parent_number == $number){
	            $chil_node = self::GetChliNode($arr['number'], $list);
	            $node[] = [
	            	'node_id' => $arr['ID'],
    	            'number' => $arr['number'],
    	            'name' => $arr['name'],
    	            'chil_node' => $chil_node,
	            ];
	            unset($list[$key]);
	        }
	    }
	    return $node;
	}
	
	//商品分类html
	public static function ProductClassificaHtml($node, $classCount){
		$node_html = '';
		foreach($node as $key => $val){
			$count = '';
			if(!empty($classCount)){
				$count = empty($classCount[$val['number']]) ? ' (0)' : ' ('.$classCount[$val['number']].')';
			}
			//判断是否存在下级，存在则添加下级相关html
			$chli_html = '';
			$open_html = '';
			if(!empty($val['chil_node'])){
				$chli_html = self::ProductClassificaHtml($val['chil_node'], $classCount);
				
				if(!empty($chli_html)){
					$chli_html = '<ul data-cid="0" style="display: block;">'.$chli_html.'</ul>';
					$open_html = '<span class="gly glyphicon pull-left glyphicon-triangle-bottom" data-isleaf="open"></span>';
				}
			}
			
			$choose_html = (!empty($_REQUEST['class_id']) && $_REQUEST['class_id'] == $val['node_id']) ? 'choose_class' : '';
			$node_html .= 
				'<li>
					<div class="'.$choose_html.'">
						'.$open_html.'
						<label>
							<span class="chooseTreeName" onclick="null" class_id="'.$val['node_id'].'">'.$val['name'].$count.'</span>
						</label>
					</div>
					'.$chli_html.'
				</li>';
		}
		return $node_html;
	}
	
	//商品分类设置html
	public static function ProductClassificaSettingHtml($node){
		$node_html = '';
		foreach($node as $key => $val){
			//判断是否存在下级，存在则添加下级相关html
			$chli_html = '';
			$open_html = '';
			if(!empty($val['chil_node'])){
				$chli_html = self::ProductClassificaSettingHtml($val['chil_node']);
		
				if(!empty($chli_html)){
					$chli_html = '<ul style="display:block;margin-left:10px;margin-top:0px;">'.$chli_html.'</ul>';
					$open_html = '<span class="gly1 glyphicon-triangle-bottom class_tree_swith" data-isleaf="open"></span>';
				}
			}
			
			//当超过两级时，不显示增加按钮
			$add_html = strlen($val['number']) > 5 ? '' : '<span class="button class_add glyphicon glyphicon-plus" title="添加分类" ></span>';
			
			$node_html .=
			'<li node_number="'.$val['number'].'" node_id="'.$val['node_id'].'" >
				'.$open_html.'
				<a target="_blank" style="">
					<span class="class_name">'.$val['name'].'</span>
					<span class="button class_remove glyphicon glyphicon-remove" title="删除分类" ></span>
					<span class="button class_edit glyphicon glyphicon-edit" title="更改分类名" ></span>
					'.$add_html.'
				</a>
				'.$chli_html.'
			</li>';
		}
		return $node_html;
	}
	
	public static function AddClassifica($father_number){
	    try{
    	    //查询同父亲下的子节点最大编码
    	    $query = ProductClassification::find();
    	    if(empty($father_number)){
    	        $query->where("parent_number='' or parent_number is null");
    	    }
    	    else{
    	        $query->where(['parent_number' => $father_number]);
    	    }
    	    $max_node = $query->orderBy("number desc")->asArray()->one();
    	    if(!empty($max_node)){
    	        $number = $max_node['parent_number'].sprintf("%02d", (substr($max_node['number'], strlen($max_node['number']) - 2)) + 1);
    	    }
    	    else{
    	        $number = $father_number.'01';
    	    }
    	    
    	    //检测编码是否已存在，已存在则类型，最多检测5次
    	    for($n = 0; $n < 5; $n++){
        	    $old_node = ProductClassification::findOne(['number' => $number]);
        	    if(!empty($old_node)){
        	        $number = $old_node['parent_number'].sprintf("%02d", (substr($old_node['number'], strlen($old_node['number']) - 2)) + 10);
        	    }
        	    else{
        	        break;
        	    }
    	    }
    	    
    	    $new_node = new ProductClassification();
    	    $new_node->number = $number;
    	    $new_node->parent_number = $father_number;
    	    $new_node->name = '新分类';
    	    if(!$new_node->save()){
    	        return ['success' => false, 'msg' => '新增分类失败！e1'];
    	    }
    	    return ['success' => true, 'msg' => '', 'number' => $number, 'node_id' => $new_node['ID']];
	    }
	    catch(\Exception $ex){
	        return ['success' => false, 'msg' => '新增分类别失败！e2'];
	    }
	}
	
	public static function EditClassifica($node_id, $name){
		$node = ProductClassification::findOne(['ID' => $node_id]);
		if(!empty($node)){
			$node->name = $name;
			if(!$node->save()){
				return ['success' => false, 'msg' => '修改分类失败！e1'];
			}
			return ['success' => true, 'msg' => ''];
		}
		else{
			return ['success' => false, 'msg' => '找不到对应分类！'];
		}
	}
	
	public static function DeleteClassifica($node_id){
		try{
			$node = ProductClassification::findOne(['ID' => $node_id]);
			if(!empty($node)){
				//查询自身、及其所有下级
				$nodes = ProductClassification::find()->where("substring(number, 1, length('".$node['number']."'))='".$node['number']."'")->asArray()->all();
				$id_arr = array();
				foreach($nodes as $val){
					$id_arr[] = $val['ID'];
				}
				//查询自身对应的上级分类Id
				$parent_class_id = 0;
				$parent_node = ProductClassification::findOne(['number' => $node['parent_number']]);
				if(!empty($parent_node)){
					$parent_class_id = $parent_node['ID'];
				}
				if(!empty($id_arr)){
					//把对应这些分类的商品移入未分类
					Product::updateAll(['class_id' => $parent_class_id], ['class_id' => $id_arr]);
					//删除对应分类
					ProductClassification::deleteAll(['ID' => $id_arr]);
					
					//更新分类统计数量
					self::getProductClassCount(true);
				}
				return ['success' => true, 'msg' => ''];
			}
			else{
				return ['success' => false, 'msg' => '找不到对应分类！'];
			}
		}
		catch(\Exception $ex){
			return ['success' => false, 'msg' => '删除分类别失败！'];
		}
	}
	
	public static function ChangeClassifica($class_id, $skulist){
	    $skulist = json_decode($skulist);
	    foreach($skulist as $key => $sku){
	        $skulist[$key] = base64_decode($sku);
	    }
	    
	    //查询对应所有变参子产品
	    $configR = ProductConfigRelationship::find()->where(['cfsku' => $skulist])->asArray()->all();
	    foreach($configR as $sku){
	        $skulist[] = $sku['assku'];
	    }
	    
	    //把对应这些分类的商品移入未分类
	    Product::updateAll(['class_id' => $class_id], ['sku' => $skulist]);
	    
	    //更新分类统计数量
	    self::getProductClassCount(true);
	    
	    //写入操作日志
	    $str = self::getProductClassAllLevel($class_id);
	    $edit_log = '移动分类, 以下商品移到到分类 "'.$str.' ", SKU: '.implode($skulist, ", ");
	    UserHelper::insertUserOperationLog('catalog', $edit_log);
	    
	    return ['success' => true, 'msg' => ''];
	}
	
	protected static $BATH_EDIT_PRODUCT_DECLARATION = array(
			"declaration_ch" => "中文报关名",
			"declaration_en" => "英文报关名",
			"declaration_value_currency" => "申报货币",
			"declaration_value" => "申报金额",
			"battery" => "是否含电池",
			'declaration_code' => "报关编码",
	);
	
	protected static $BATH_EDIT_PRODUCT_BASIC = array(
			"name" => "商品名称",
			"prod_name_ch" => "中文配货名",
			"prod_name_en" => "英文配货名",
			"prod_weight" => "重量 (g)",
	        "commission_per" => "佣金比例",
	);
	
	/**
	 * +----------------------------------------------------------
	 * 获取批量编辑的商品相关信息
	 *+----------------------------------------------------------
	 * @param	$edit_type         string    编辑类型
	 * @param	$product_id_list   array     product_id集合
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lrq		2017/10/31				初始化
	 *+----------------------------------------------------------
	 */
	public static function GetBathEditInfo($edit_type, $product_id_list){
		$pro_list = array();
		$skus = [];
		$sku_list = array();
		
		//输出的列信息
		$col_name = ['product_id', 'sku', 'name', 'photo_primary', 'purchase_link', 'addi_info'];
		$edit_col_name = self::$BATH_EDIT_PRODUCT_BASIC;
		if($edit_type == 'declaration'){
			$edit_col_name = self::$BATH_EDIT_PRODUCT_DECLARATION;
		}
		foreach ($edit_col_name as $col => $val){
			if(!in_array($col, $col_name) && !in_array($col, ['commission_per'])){
				$col_name[] = $col;
			}
		}
		
		$pros = Product::find()->select($col_name)
			->where(['product_id' => $product_id_list])->asArray()->all();
		foreach($pros as $pro){
			$pro_list[$pro['sku']] = $pro;
			$skus[$pro['product_id']] = $pro['sku'];
			$sku_list[] = $pro['sku'];
		}
		
		//查询是否存在变参商品
		$relationship_list = array();
		$relationship = ProductConfigRelationship::find(['cfsku' => $skus])->asArray()->all();
		if(!empty($relationship)){
			$assku = [];
			foreach($relationship as $one){
				$assku[] = $one['assku'];
				$sku_list[] = $one['assku'];
				$relationship_list[$one['cfsku']][] = $one['assku'];
			}
			
			$pros = Product::find()->select($col_name)
				->where(['sku' => $assku])->asArray()->all();
			foreach($pros as $pro){
				$pro_list[$pro['sku']] = $pro;
			}
		}
		
		//获取采购链接信息
		$pd_sp_list = ProductSuppliersHelper::getProductPurchaseLink($sku_list);
		foreach ($pro_list as &$one){
			$one['purchase_link_list'] = '';
			if(array_key_exists($one['sku'], $pd_sp_list)){
				$one['purchase_link'] = $pd_sp_list[$one['sku']]['purchase_link'];
				$one['purchase_link_list'] = json_encode($pd_sp_list[$one['sku']]['list']);
			}
		}
		
		//整理输出的需编辑信息
		$edit_info = array();
		foreach($product_id_list as $product_id){
			if(array_key_exists($product_id, $skus)){
				$sku = $skus[$product_id];
				
				//当是变参父商品时，转换为对应子产品
				if(array_key_exists($sku, $relationship_list)){
					foreach($relationship_list[$sku] as $assku){
						if(array_key_exists($assku, $pro_list)){
							$edit_info[] = $pro_list[$assku];
						}
					}
				}
				else if(array_key_exists($sku, $pro_list)){
					$edit_info[] = $pro_list[$sku];
				}
			}
			
			//最多只能200条
			if(count($edit_info) > 200){
			    break;
			}
		}
		
		$data['edit_info'] = $edit_info;
		$data['edit_col_name'] = $edit_col_name;
		
		return $data;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 保存批量编辑的商品
	 * +----------------------------------------------------------
	 * log			name	date			note
	 * @author 		lrq		2017/10/31		初始化
	 *+----------------------------------------------------------
	 */
	public static function SaveBathEdit($data){
		$ret['success'] = true;
		$ret['msg'] = '';
		if(empty($data) || empty($data['item'])){
			return ['success' => false, 'error' => '没有可编辑的商品信息'];
		}
		$pro_info = $data['item'];
		
		//更新字段信息
		$col_list = self::$BATH_EDIT_PRODUCT_BASIC + self::$BATH_EDIT_PRODUCT_DECLARATION;
		
		$err_list = array();
		$successQty = 0;
		$failQty = 0;
		$edit_log = '';
		foreach($pro_info as $one){
			$update_sku = '';
			$err_msg = array();
			try{
				if(!empty($one['product_id'])){
					$pro = Product::findOne(['product_id' => $one['product_id']]);
					if(!empty($pro)){
						$update_sku = $pro->sku;
						$is_update = true;
						
						foreach($one as $col => $val){
						    $val = trim($val);
							if(in_array($col, ['product_id'])){
								continue;
							}
							if(!array_key_exists($col, $col_list)){
								continue;
							}
							
							//默认值
							if(empty($val)){
								switch($col){
									case 'prod_weight':
									case 'commission_per':
									case 'declaration_value':
										$val = 0;
										break;
									case 'battery':
										$val = 'N';
										break;
									case 'declaration_value_currency':
										$val = 'USD';
										break;
									default:
										break;
								}
							}
							
							//验证信息
							if(empty($val) && in_array($col, ['declaration_ch', 'declaration_en', 'name', 'prod_name_ch', 'prod_name_en'])){
								$is_update = false;
								$err_msg[] = $col_list[$col]." 不能为空！";
								continue;
							}
							if(in_array($col, ['prod_weight', 'commission_per']) && (!is_numeric($val) || floor($val) != $val)){
								$is_update = false;
								$err_msg[] = $col_list[$col]." 必须为整数！";
								continue;
							}
							if(in_array($col, ['declaration_value']) && !is_numeric($val)){
								$is_update = false;
								$err_msg[] = $col_list[$col]." 必须为数字！";
								continue;
							}
							
							switch($col){
								case 'commission_per':
									if(!empty($data['bath_edit_commission_platform'])){
										$platform = $data['bath_edit_commission_platform'];
										$addi_info = array();
										if(!empty($pro->addi_info)){
											$addi_info = json_decode($pro->addi_info, true);
											if(empty($addi_info)){
												$addi_info = array();
											}
										}
										if(empty($val)){
											unset($addi_info['commission_per'][$platform]);
										}
										else{
											$addi_info['commission_per'][$platform] = $val;
										}
										
										$pro->addi_info = json_encode($addi_info);
									}
									break;
								default:
									$pro->$col = $val;
									break;
							}
						}
						
						$old_product = Product::findOne(['product_id' => $pro->product_id]);
						if($is_update){
							if(!$pro->save()){
								$err_msg[] = print_r($pro->errors,true);
							}
							else{
    							//记录修改日志
        					    $log = '';
        						if(!empty($old_product)){
        							foreach (self::$EDIT_PRODUCT_LOG_COL as $col_k => $col_n){
        								if($pro->$col_k != $old_product->$col_k){
        									if(empty($log)){
        										$log = $pro->sku;
        									}
        									$log .= ', '.$col_n.'从"'.$old_product->$col_k.'"改为"'.$pro->$col_k.'"';
        								}
        							}
        							if(!empty($log)){
        							    $edit_log .= $log."; ";
        							}
        						}
							}
						}
					}
				}
			}
			catch(\Exception $ex){
				$err_msg[] = $ex->getMessage();
			}
			
			if(!empty($err_msg)){
			    $failQty++;
				$err_list[] = [
    				'sku' => $update_sku,
    				'list' => $err_msg,
				];
			}
			else{
			    $successQty++;
			}
		}
		
		if(!empty($edit_log)){
			$edit_log = "批量编辑信息: ".$edit_log;
			//print_r($logs);die;
			if(strlen($edit_log) > 480){
				$edit_log = substr($edit_log, 0, 480).'......';
			}
			//print_r($edit_log);die;
			//写入操作日志
			UserHelper::insertUserOperationLog('catalog', $edit_log);
		}
		
		$ret['successQty'] = $successQty;
		$ret['failQty'] = $failQty;
		$ret['msg'] = $err_list;
		return $ret;
	}
	
	public static function getProductClassAllLevel($class_id){
		$name_str = '';
		//所有分类信息
		$class_id_arr = array();
		$class_number_arr = array();
		$classlist = ProductClassification::find()->asArray()->All();
		foreach ($classlist as $class){
			$class_id_arr[$class['ID']] = $class['number'];
			$class_number_arr[$class['number']] = $class;
		}
		
		if (!empty($class_id) && array_key_exists($class_id, $class_id_arr)){
			$number = $class_id_arr[$class_id];
			//查询类别集合名称
			for($n = 1; $n < 6; $n++){
				if(array_key_exists($number, $class_number_arr)){
					$name_str = $class_number_arr[$number]['name'].','.$name_str;
					$number = $class_number_arr[$number]['parent_number'];
					 
					if(empty($number)){
						break;
					}
				}
				else{
					break;
				}
			}
		}
		
		return rtrim($name_str, ",");
	}
	
	/**
	 * +----------------------------------------------------------
	 * 获取分类对应的数量
	 * +----------------------------------------------------------
	 * log			name	date			note
	 * @author 		lrq		2017/12/25		初始化
	 *+----------------------------------------------------------
	 */
	public static function getProductClassCount($is_refresh = false){
		$class_count_list = array();
		try{
			//从redis读取上次计算的信息
			$puid = \Yii::$app->user->identity->getParentUid();
			$redis_key_lv1 = 'ProductClassCount';
			$redis_key_lv2 = $puid;
			if(!$is_refresh){
				$warn_record = RedisHelper::RedisGet($redis_key_lv1, $redis_key_lv2);
				if(!empty($warn_record)){
					$redis_val = json_decode($warn_record,true);
				}
				//当天已计算过，则不重新计算，直接返回
				if(!empty($redis_val) && !empty($redis_val['create_time']) && time() - $redis_val['create_time'] < 3600 * 12){
					return $redis_val;
				}
			}
			//****************重新计算**********
			//所有分类信息
			$class_id_arr = array();
			$classlist = self::GetProductClassQuery()->asArray()->all();
			foreach ($classlist as $class){
				$class_id_arr[$class['ID']] = $class['number'];
			}
			//获取个子分类对应的商品数量
			$pd_count_list = Yii::$app->get('subdb')->createCommand("SELECT class_id, count(1) count FROM `pd_product` WHERE type!='L' group by class_id")->queryAll();
			$class_count_list['all'] = 0;
			foreach($pd_count_list as $one){
				if(array_key_exists($one['class_id'], $class_id_arr)){
					$num = $class_id_arr[$one['class_id']];
					$class_count_list[$num] = $one['count'];
					//统计到父级别
					$count = 1;
					while(strlen($num) > 2){
						$num = substr($num, 0, strlen($num) - 2);
						$class_count_list[$num] = empty($class_count_list[$num]) ? $one['count'] : $class_count_list[$num] + $one['count'];
						
						$count++;
						if($count > 3){
							$num = '';
						}
					}
				}
				else{
					$class_count_list[0] = empty($class_count_list[0]) ? $one['count'] : $class_count_list[0] + $one['count'];
				}
				
				$class_count_list['all'] += $one['count'];
			}
			
			//保存到redis
			$class_count_list['create_time'] = time();
			$ret = RedisHelper::RedisSet($redis_key_lv1, $redis_key_lv2, json_encode($class_count_list));
		}
		catch(\Exception $ex){
			
		}
		
		return $class_count_list;
	}
	
}//end of ProductHelper





