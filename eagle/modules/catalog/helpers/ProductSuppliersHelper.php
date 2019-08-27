<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */

namespace eagle\modules\catalog\helpers;
use eagle\modules\purchase\models\Supplier;
use eagle\modules\purchase\helpers\SupplierHelper;
use yii;
use yii\data\Pagination;
use eagle\modules\catalog\models\Product;
use eagle\modules\catalog\models\ProductSuppliers;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\TranslateHelper;


class ProductSuppliersHelper{
	
	private static $EXCEL_COLUMN_MAPPING = [
	"A" => "sku",
	"B" => "supplier_name",
	"C" => "priority",
	"D" => "purchase_price",
	];
	
	public static $EXPORT_EXCEL_FIELD_LABEL = [
	"sku" => "产品SKU",
	"supplier_name" => "供应商",
	"priority" => "优先级",
	"priority" => "采购单价(RMB)",
	];
	
	static public function ListSupplierData($include_disable=false){
		$exists = Supplier::find()->andWhere(['supplier_id' => 0])->one();
		if (empty($exists)){
			SupplierHelper::createDefaultSupplier();
		}
		if($include_disable)
			$SupplierList = Supplier::find()->asArray()->all();
		else
			$SupplierList = Supplier::find()->where(['is_disable'=>0,'status'=>1])->asArray()->all();
		
		$result = [];
		foreach($SupplierList as $supplier){
			$result[$supplier['supplier_id']] = $supplier;
		}
		return $result;
	}//end of ListSupplierData
	
	
	
	/**
	 +----------------------------------------------------------
	 * 获取商品报价列表数据
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
	public static function getProductData($page, $rows, $sort, $order, $queryString) {
		$productDatas = ProductHelper::listData($page, $rows, $sort, $order, $queryString, false);
		$skuList = array();
		foreach ($productDatas['rows'] as $product) {
			$skuList[] = $product['sku'];
		}
	
		$criteria = new CDbCriteria();
		$criteria->addInCondition('sku', $skuList);
		$criteria->order = 'priority asc';//排序条件
		$productDatas['prices'] = ProductSuppliers::model()->findAll($criteria);
	
		return CJSON::encode($productDatas);
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据供应商获取商品报价列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param page			当前页
	 * @param rows			每页行数
	 * @param queryString	其他条件
	 +----------------------------------------------------------
	 * @return				商品数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	public static function getProductSuppliersData($page, $rows, $queryString) {
		$command = Yii::app()->subdb->createCommand()
		->select('t.*,t1.photo_primary,t1.name,t1.category_id')
		->from('pd_product_suppliers t')
		->join('pd_product t1', 't1.sku = t.sku');
		if(!empty($queryString))
		{
			foreach($queryString as $k => $v)
			{
				$command->where("t.$k='$v'");
			}
		}
		$command->limit($rows, ($page-1) * $rows);
		$command->order('t.sku asc');
	
		return CJSON::encode($command->queryAll());
	}
	
	/**
	 +----------------------------------------------------------
	 * 添加供应商报价
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param datas			报价数据
	 +----------------------------------------------------------
	 * @return				添加结果
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	public static function addProductSuppliers($datas) {
		$resulut = array( 'success' => array(), 'error' => array());
		foreach ($datas as $item){
				
			$supplier = Supplier::model()->findByPk($item['supplier_id']);
			if($supplier == null || $supplier->is_disable == 1){
				$resulut['error'][] = $item['sku'].'添加报价失败，供应商不存在，请更换供应商';
				continue;
			}else if($supplier->status == 2){
				$resulut['error'][] = $item['sku']."添加报价失败，供应商已停用，请更换供应商";
				continue;
			}
				
			
			$model = ProductSuppliers::model()->findByAttributes(array('sku' => $item['sku'], 'priority' => $item['priority']));
			$bySupplierModel = ProductSuppliers::model()->findByAttributes(array('sku' => $item['sku'], 'supplier_id' => $item['supplier_id']));
			if ($bySupplierModel != null) {
				if ($bySupplierModel->priority != $item['priority']) {
					if ($bySupplierModel->priority == 0) {
						$product = Product::model()->findByAttributes(array('sku' => $item['sku']));
						$product->supplier_id = null;
						$product->purchase_price = null;
						$product->save();
					}
					$bySupplierModel->delete();
				}
			}
			// 停止维护pd_product 表supplier_id,purchase_price
			//			if ($item['priority'] == 0) {
			//				$product = Product::model()->findByAttributes(array('sku' => $item['sku']));
			//				$product->supplier_id = $item['supplier_id'];
			//				$product->purchase_price = $item['purchase_price'];
			//				$product->save();
			//			}
			if ($model == null)
			{
				$model = new ProductSuppliers();
				$model->sku = $item['sku'];
				$model->priority = $item['priority'];
			}
			$model->supplier_id = $item['supplier_id'];
			$model->purchase_price = $item['purchase_price'];
	
			if ($model->save()) {
				$resulut['success'][] = $item['sku'];
			}else {
				$resulut['error'][] = $item['sku'];
			}
		}
		return $resulut;
	}
	
	/**
	 +----------------------------------------------------------
	 * 更新商品的供应商列表
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param sku				需更新的商品SKU
	 * @param product			商品的数组模型
	 * @param productSuppliers	备选供应商列表
	 +----------------------------------------------------------
	 * @return				无
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	public static function updateProductSuppliers($sku ,$product, $productSuppliers) {
		// 不再从商品数据表中的supplier_id和purchase_price 修改/读取首选供应商信息
		/*
		 * $priority0 = ProductSuppliers::model()->findByAttributes(array('sku' => $sku, 'priority' => 0)); if (!empty($product['supplier_id'])) {//当供应商specified，但是价格没有specified 的时候，仍然保存这个供应关系，只是价格为 0，等待更新 if (empty($product['purchase_price'])) $product['purchase_price'] = 0; if ($priority0 == null) { $priority0 = new ProductSuppliers(); $priority0->sku = $sku; $priority0->priority = 0; } $priority0->supplier_id = $product['supplier_id']; $priority0->purchase_price = $product['purchase_price']; $priority0->save(); } else { if ($priority0 != null) { $priority0->delete(); } }
		 */
		$purchase_link = '';
		for($i = 0; $i <= 4; $i ++) {
			
			$priority = ProductSuppliers::findOne(['sku' => $sku,'priority' => $i ]);
			
			if (isset ( $productSuppliers ['supplier_id'] [$i] ) && is_numeric ( $productSuppliers ['supplier_id'] [$i] )) {
				//若果supplier id=0而没有输入purchase_price，则认为非用户设定，忽略
				if( $productSuppliers['supplier_id'][$i]==0 &&  $productSuppliers['purchase_price'][$i]=='' && $productSuppliers['purchase_link'][$i]=='')
					continue;
				
				// 保存时必须确保供应商为有效供应商
				$supplier = Supplier::findOne ( $productSuppliers ['supplier_id'] [$i] );
				if ($supplier != null && $supplier->is_disable == 0 && $supplier->status == 1) {
					// 当供应商specified，但是价格没有specified 的时候，仍然保存这个供应关系，只是价格为 0，等待更新
					if (empty ( $productSuppliers ['purchase_price'] [$i] ))
						$productSuppliers ['purchase_price'] [$i] = 0;
						
						// pd_product_suppliers 表中,sku与supplier_id的组合也是唯一的,所以save()前,先确保record的唯一
					$bySupplierModel = ProductSuppliers::findOne ( array (
							'sku' => $sku,
							'supplier_id' => $productSuppliers ['supplier_id'] [$i] 
					) );
					if ($bySupplierModel != null) {
						if ($bySupplierModel->priority != $i) {
							$bySupplierModel->delete ();
						}
					}
					//SysLogHelper::SysLog_Create ( "Catalog", __CLASS__, __FUNCTION__, "", "print ProductSuppliers 2:" . print_r ( $productSuppliers, true ), "trace" );
					if ($priority == null) {
						$priority = new ProductSuppliers();
						$priority->sku = $sku;
						$priority->priority = $i;
					}
					$priority->supplier_id = $productSuppliers ['supplier_id'] [$i];
					$priority->purchase_price = $productSuppliers ['purchase_price'] [$i];
					$priority->purchase_link = $productSuppliers ['purchase_link'] [$i];
					$priority->save ();
					if(!isset($topSupplierId)){
						$topSupplierId=$priority->supplier_id;
						$purchase_price = $priority->purchase_price;
						$purchase_link = $priority->purchase_link;
					}
					if(empty($purchase_link)){
						$purchase_link = $priority->purchase_link;
					}
				}
			} else {
				if ($priority != null) {
					$priority->delete ();
				}
			}
		}
		
		if(!isset($topSupplierId)){
			$topSupplierId=0;
			$purchase_price = 0;
		}
		return ['supplier_id'=>$topSupplierId,'purchase_price'=>$purchase_price, 'purchase_link'=>$purchase_link ];
	}
	
	/**
	 * 导入商品报价helper
	 * @param 	array		$excel_data		
	 * @param	string		$dataSrc		('file' or 'text')
	 * @return 	array		操作结果
	 */
	public static function importPdSuppliersByExcel($ExcelData,$dataSrc='file'){
		//获取 excel数据
		if($dataSrc=='file')
			$excel_data = ExcelHelper::excelToArray($ExcelData , self::$EXCEL_COLUMN_MAPPING, true);
		if($dataSrc=='text')
			$excel_data = $ExcelData;
		$groupBySku = [];
		
		$result['success'] = true;
		$result['message'] = '';
		$replaced_record = array();//报价覆盖记录
		//检查excel 导入的数据
		foreach($excel_data as $rowIndex=>$aPdSupplier){
			//排除表头
			$field_labels = self::$EXPORT_EXCEL_FIELD_LABEL;
			if($aPdSupplier['sku']==$field_labels['sku'] && $aPdSupplier['supplier_name']==$field_labels['supplier_name'] && $aPdSupplier['priority']==$field_labels['priority']){
				continue;
			}
			//验证sku有效性
			$product=Product::findOne(['sku'=>$aPdSupplier['sku']]);
			if($product==null){
				$result['success'] = false;
				$result['message'] .= "<br>行".$rowIndex.":".TranslateHelper::t('产品:').$aPdSupplier['sku'].TranslateHelper::t('不存在！');
				continue;
			}
			if($product->type=="C" or $product->type=="B"){
				$result['success'] = false;
				$result['message'] .= "<br>行".$rowIndex.":".TranslateHelper::t('产品:').$aPdSupplier['sku'].TranslateHelper::t('是变参产品或捆绑产品，只能对普通产品记录报价！');
				continue;
			}
			//验证优先级和单价有效性和
			if( !is_numeric($aPdSupplier['priority']) ){
				$result['success'] = false;
				$result['message'] .= "<br>行".$rowIndex.":".TranslateHelper::t('产品:').$aPdSupplier['sku'].TranslateHelper::t('输入的优先级无效！必须为0-4的整数数字');
				continue;
			}
			if( !is_numeric($aPdSupplier['purchase_price']) ){
				$result['success'] = false;
				$result['message'] .= "<br>行".$rowIndex.":".TranslateHelper::t('产品:').$aPdSupplier['sku'].TranslateHelper::t('输入的单价无效！必须为数字');
				continue;
			}
			//处理重复
			if(!isset($groupBySku[$aPdSupplier['sku']])){
				$groupBySku[$aPdSupplier['sku']][$aPdSupplier['priority']] = $aPdSupplier;
				$groupBySku[$aPdSupplier['sku']][$aPdSupplier['priority']]['rowIndex'] = $rowIndex;
			}
			else{
				foreach ($groupBySku[$aPdSupplier['sku']] as $priority=>&$detail){
					if($aPdSupplier['priority']==$priority){
						$replaced_record[$aPdSupplier['sku']][] ="行".$rowIndex.">行".$detail['rowIndex'];//记录哪行覆盖哪行
						$detail = $aPdSupplier;
						$detail['rowIndex'] = $rowIndex;
						break;
					}else{
						if($aPdSupplier['supplier_name']==$detail['supplier_name']){
							$replaced_record[$aPdSupplier['sku']][] ="行".$rowIndex.">行".$detail['rowIndex'];//记录哪行覆盖哪行
							unset($detail);
							$groupBySku[$aPdSupplier['sku']][$aPdSupplier['priority']]=$aPdSupplier;
							$groupBySku[$aPdSupplier['sku']][$aPdSupplier['priority']]['rowIndex']=$rowIndex;
							break;
						}else{
							$groupBySku[$aPdSupplier['sku']][$aPdSupplier['priority']]=$aPdSupplier;
							$groupBySku[$aPdSupplier['sku']][$aPdSupplier['priority']]['rowIndex']=$rowIndex;
						}
					}
				}
			}//end of replaced;
		}//loop excel data end;
		
		if($result['success']){//全部验证通过后，进入保存流程
			global $CACHE;
			foreach ($groupBySku as $sku=>$datas){
				foreach ($datas as $priority=>$info){
					//供应商名称转换成id
					if(isset($CACHE['pdSupplier_supplierNmaes'][$info['supplier_name']]))
						$supplier_id = $CACHE['pdSupplier_supplierNmaes'][$info['supplier_name']];
					else{
						$supplier = Supplier::findOne(['name'=>$info['supplier_name']]);
						if (!empty($supplier)){
							$supplier_id = $supplier->supplier_id;
						}else{
							$supplier = new Supplier();
							$supplier->name = $info['supplier_name'];
							$supplier->create_time = date("Y-m-d H:i:s");
							$supplier->capture_user_id = Yii::$app->user->id;
							$supplier->status = 1;
							$supplier->comment = TranslateHelper::t('由excel导入报价时快速新建');
							$supplier->save(false);
					
							$supplier_id = $supplier->supplier_id;
						}
						$CACHE['pdSupplier_supplierNmaes'][$info['supplier_name']]=$supplier_id;
					}
					
					$pdSupplierModel = ProductSuppliers::findOne(['sku'=>$info['sku'],'priority'=>$info['priority']]);
					if($pdSupplierModel==null){
						$pdSupplierModel = new ProductSuppliers();
						$pdSupplierModel->sku=(string)$info['sku'];
						$pdSupplierModel->supplier_id=$supplier_id;
						$pdSupplierModel->priority=$info['priority'];
						$pdSupplierModel->purchase_price=$info['purchase_price'];
					}else{
						$pdSupplierModel->supplier_id=$supplier_id;
						$pdSupplierModel->purchase_price=$info['purchase_price'];
					}
					
					if($pdSupplierModel->save()){
						if(isset($replaced_record[$info['sku']]))
							$result['message'] .= "<br>".$info['sku'].TranslateHelper::t('覆盖记录：').implode(',',$replaced_record[$info['sku']]);
					}
					else{
						$result['message'] .= "<br>".$info['rowIndex'].TranslateHelper::t('保存失败:')."<br>";
						foreach ($pdSupplierModel->errors as $k => $anError){
							$result['message'] .= $k.":".$anError[0];
						}
					}
				}
			}
			return $result;
		}else{
			return $result;
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取sku对应的采购链接信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param skus		array		SKU组
	 +----------------------------------------------------------
	 * @return				无
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2017/12/19		初始化
	 +----------------------------------------------------------
	 **/
	public static function getProductPurchaseLink($skus){
		$pd_sp_list = array();
		try{
			$supplier_ids = array();
			$pds = ProductSuppliers::find()->where(['sku' => $skus])->orderBy("priority")->asArray()->all();
			foreach($pds as $pd){
				$supplier_ids[] = $pd['supplier_id'];
				
				$pd_sp_list[$pd['sku']]['purchase_link'] = '';
				$pd_sp_list[$pd['sku']]['list'] = array();
			}
			
			//查询对应供应商信息
			$supplier_lis = array();
			$suppliers = Supplier::find()->where(['supplier_id' => $supplier_ids]);
			foreach($suppliers->each() as $supplier){
				$supplier_lis[$supplier->supplier_id] = $supplier->name;
			}
			
			foreach($pds as $pd){
				if(!empty($pd['purchase_link']) && trim($pd['purchase_link']) != ''){
					if(array_key_exists($pd['supplier_id'], $supplier_lis)){
						$pd_sp_list[$pd['sku']]['list'][$pd['product_supplier_id']] = [
							'supplier_id' => $pd['supplier_id'],
							'purchase_link' => $pd['purchase_link'],
							'supplier_name' => $supplier_lis[$pd['supplier_id']],
						];
					}
					if(empty($pd_sp_list[$pd['sku']]['purchase_link'])){
						$pd_sp_list[$pd['sku']]['purchase_link'] = $pd['purchase_link'];
					}
				}
			}
			
			//当sku对应的采购链接少于2个时，采购链接列表清空
			foreach($pd_sp_list as &$one){
				if(count($one['list']) < 2){
					$one['list'] = array();
				}
			}
		}
		catch(\Exception $ex){
		}
		
		return $pd_sp_list;
	}
	
	//旧版单个采购链接，转移到供应给级别的采购链接组
	public static function transferPurchaseLink(){
		try{
			$count = 0;
			$pds = Product::find()->where("purchase_link != '' && purchase_link is not null");
			foreach($pds->each() as $pd){
				$pd_sp = ProductSuppliers::find()->where(['sku' => $pd->sku])->orderBy("priority")->one();
				if(empty($pd_sp)){
					$pd_sp = new ProductSuppliers();
					$pd_sp->sku = $pd->sku;
					$pd_sp->supplier_id = 0;
					$pd_sp->priority = 0;
					$pd_sp->purchase_price = 0;
					
					//商品表采购商默认
					$pd->supplier_id = 0;
					$pd->save();
				}
				$pd_sp->purchase_link = $pd->purchase_link;
				$pd_sp->save();
				$count++;
			}
			
			echo 'productCount: '.$pds->count().', updateCount: '.$count." \n";
			if($pds->count() > 0){
				echo 'exists info'." \n";
			}
			if($pds->count() != $count){
				echo 'count diff'." \n";
			}
		}
		catch(\Exception $ex){
			echo $ex->getMessage()." \n";
		}
	}
	
	
}//end of ProductSuppliersHelper