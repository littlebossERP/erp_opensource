<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\purchase\helpers;

use eagle\modules\purchase\models\Purchase;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\purchase\models\Supplier;
use eagle\modules\purchase\helpers\SupplierHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\inventory\models\ProductStock;
use eagle\modules\purchase\models\PurchaseSuggestion;
use eagle\modules\inventory\helpers\InventoryApiHelper;

use yii\data\Pagination;
use eagle\modules\purchase\models\PurchaseItems;
use eagle\modules\catalog\models\Product;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\ConfigHelper;
use yii\base\Exception;
use eagle\modules\util\models\OperationLog;
use yii;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use yii\db\Query;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\catalog\helpers\ProductSuppliersHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;

/**
 * BaseHelper is the base class of module BaseHelpers.
 *

 */
class PurchaseSugHelper {

	/**
	 * 根据仓库的货品的库存情况，生成货品的采购建议，目前只是支持缺货。
	 * @param $warehouseId--- as $warehouseId equals to "", means all the warehouses 
	 * Returns array({"sku"=>"234","pd_name"=>"case",..},{}....).    ------all the suggestion products data for table datagrid element
	 */
	public static function getPurchaseSuggentionData($warehouseId)
	{
		
		$resultArr=array();
		
		//$rows=Yii::app()->subdb->createCommand('SELECT ps.sku as sku,ps.prod_stock_id as prod_stock_id,wh.name as wh_name, wh.warehouse_id as wh_id,pd.photo_primary as photo_primary,  pd.name as pd_name ,ps.qty_in_stock as qty_in_stock,ps.qty_purchased_coming as qty_purchased_coming,ps.qty_ordered as qty_ordered FROM wh_product_stock ps  LEFT OUTER JOIN pd_product pd ON ( ps.sku=pd.sku ) LEFT OUTER JOIN wh_warehouse wh ON ( ps.warehouse_id=wh.warehouse_id ) ')->queryAll();
		$query=ProductStock::find()
			->Where("qty_purchased_coming+qty_in_stock<qty_ordered");
		if  ($warehouseId<>"")  {
			$query->andWhere(['warehouse_id'=>$warehouseId]);
		}
		//$criteria->join = 'LEFT JOIN pd_product pd ON t.sku=pd.sku';
			
		$productStockCollection = $query->asArray()->all();
		$warehouseIdNameMap=PurchaseHelper::getWarehouseIdNameMap();  //仓库信息
		$skuSuppliersInfo=SupplierHelper::getProductTopSupplierInfo();  // 供应商信息
		
		$resultArr=array();
		foreach($productStockCollection as $productStock){
			$row=array();
			$sku=$productStock['sku'];
			$productObject=Product::model()->findByPk($sku);
			$row['photo_primary']=$productObject['photo_primary'];
			$row['pd_name']=$productObject['name'];		
			
			$row['sku']=$sku;
			$row['prod_stock_id']=$productStock['prod_stock_id'];
			$row['wh_name']=$warehouseIdNameMap[$productStock['warehouse_id']];
			$row['qty_in_stock']=$productStock['qty_in_stock'];
			$row['qty_purchased_coming']=$productStock['qty_purchased_coming'];
			$row['qty_ordered']=$productStock['qty_ordered'];
			$row['qty_need_purchase']=$row['qty_ordered']-$row['qty_purchased_coming']-$row['qty_in_stock']; //建议采购数
		
			if (!isset($skuSuppliersInfo[$sku])) {
				//该产品没指定供应商
				$row['supplier_name']="没指定";
				$row['purchase_price']=0;
			} else 	{
				$row['supplier_name']=$skuSuppliersInfo[$sku]['name'];
				$row['purchase_price']=$skuSuppliersInfo[$sku]['purchase_price'];
			}			
			
			$resultArr[]=$row;
		}
		
		return $resultArr;
		
		
	/*	$skuSuppliersInfo=SupplierHelper::getProductTopSupplierInfo();
		foreach($rows as $row) {
			$sku=$row['sku'];
			if (!isset($skuSuppliersInfo[$sku])) {
				//该产品没指定供应商
				$row['supplier_name']="没指定";
				$row['purchase_price']=0;
			} else 	{
				$row['supplier_name']=$skuSuppliersInfo[$sku]['name'];
				$row['purchase_price']=$skuSuppliersInfo[$sku]['purchase_price'];
			}
			$row['qty_need_purchase']=$row['qty_ordered']-$row['qty_purchased_coming']-$row['qty_in_stock'];
	
			if ($row['qty_need_purchase']>0)	$resultArr[]=$row;
		}
		return $resultArr;*/
	}
	

	protected static $PurchaseSuggestionType = array(
		array('id'=>'1' , 'text'=>'缺货采购建议'), //生成的建议只是库存不足以发货的 sku 以及 需要发货的数量
	 	array('id'=>'2' , 'text'=>'缺货及备货采购'), //生成的建议是需要发货的数量加上备货数量总和
	);

	public static function getSuggestionType(){
		return self::$PurchaseSuggestionType;
	}
	/*
	 * eagle 1.0 helper
	 */
	public static function listData($page, $rows, $sort, $order, $queryString, $formatJson = true){
		$pdCriteria = new CDbCriteria();
		//这里的queryString是作用于pd_product的
		//所以这里过滤不属于pd_product的queryString
		$suggest_type = 1;
		if(isset($queryString)){
			foreach ($queryString as $index=>$queryItem){
				if($queryItem['name'] == 'suggest_type'){
					$suggest_type = $queryItem['value'];
					unset($queryString[$index]);
				}
			}
		}
		if(!empty($queryString)){
			foreach($queryString as $query){
				if ($query['condition'] == 'eq') {
					$pdCriteria->compare($query['name'], $query['value']);
				}
				elseif ($query['condition'] == 'in') {
					$pdCriteria->addInCondition($query['name'], $query['value']);
				}
				elseif ($query['condition'] == 'notIn') {
					$pdCriteria->addNotInCondition($query['name'], $query['value']);
				}
				elseif ($query['condition'] == 'like'){
					$pdCriteria->addSearchCondition($query['name'], $query['value']);
				}
				elseif ($query['condition'] == 'gt') {
					$pdCriteria->compare($query['name'], '>'.$query['value']);
				}
				elseif ($query['condition'] == 'lt') {
					$pdCriteria->compare($query['name'], '<'.$query['value']);
				}
				elseif ($query['condition'] == 'between') {
					$pdCriteria->addBetweenCondition($query['name'], $query['valueStart'], $query['valueEnd']);
				}
			}
		}
		
		// refresh 待发货数量
		InventoryApiHelper::refreshAllOSOrderProductPendingShipSummary();
		$pdCriteria->select = "photo_primary,sku,name,total_stockage,pending_ship_qty";
		$prods = Product::model()->findAll($pdCriteria);
		$productTopSupplierInfo = SupplierHelper::getProductTopSupplierInfo();
		
		// 获取所有产品的仓库的实际存量,采购在途数量,待发货量
		$allProdsTotalStockage = array();
		$prodStockageMapping = array();
		$sql = "select sku,sum(qty_in_stock) as total_stockage, sum(qty_purchased_coming) as total_purchase from wh_product_stock where qty_in_stock >= 0 group by sku ";
		$getStockageCommand = Yii::app()->subdb->createCommand($sql);
		$allProdsTotalStockage = $getStockageCommand->queryAll();
		foreach ($allProdsTotalStockage as $prodTotalStockage){
			$prodStockageMapping[$prodTotalStockage['sku']]['total_stockage'] = $prodTotalStockage['total_stockage'];
			$prodStockageMapping[$prodTotalStockage['sku']]['total_purchase'] = $prodTotalStockage['total_purchase'];
		}		
		
		//获取备货策略信息
		$StockStrategyInfo = self::getStockingStrategyInfo();
		$StockStrategyType = $StockStrategyInfo['stocking_strategy'];

		// loop 所有产品,生成采购建议
		$mapProdInfo = array();
		foreach ($prods as $index=>$pd){
			$suggestion = PurchaseSuggestion::model()->findByPk($pd->sku);
			if($suggestion == null){
				$suggestion = new PurchaseSuggestion();
				$suggestion->sku = $pd->sku;
			}
			$mapProdInfo[$pd->sku] = $pd;
			//如果当前时间 和 该 sku 的采购建议 生成时间 间隔小于 1 分钟，则skip
			if((time() - strtotime($suggestion->create_time)) > 60 || $suggestion->getIsNewRecord()){
				//建议采购发货数量
				//$suggestion->pending_purchase_ship_qty = ($pd->total_stockage - $pd->pending_ship_qty < 0) ? abs($pd->total_stockage - $pd->pending_ship_qty): 0 ;
				//2014-08-15改为：总采购待发n = 总库存+总采购在途-总待发货 ; n<0?abs(n):0
				if(isset($prodStockageMapping[$pd->sku])){
					$totalStockage = ($prodStockageMapping[$pd->sku]['total_stockage'] > 0)?$prodStockageMapping[$pd->sku]['total_stockage']:0; 
					$totalPurchase = ($prodStockageMapping[$pd->sku]['total_purchase'] > 0)?$prodStockageMapping[$pd->sku]['total_purchase']:0; 
				}else{
					$totalStockage = 0;
					$totalPurchase = 0;
				}
				$totalOrdered = ($pd->pending_ship_qty > 0)?$pd->pending_ship_qty:0;// $pd->pending_ship_qty 需要helper及时更新
				$pendingPurchaseShipQty = $totalStockage + $totalPurchase - $totalOrdered;
				$suggestion->pending_purchase_ship_qty = (($pendingPurchaseShipQty < 0)? abs($pendingPurchaseShipQty):0 );
				
				//计算建议备货数量
				if($StockStrategyType == 1 ){
					if($suggestion->pending_purchase_ship_qty != 0){
						$suggestion->pending_stock_qty = $StockStrategyInfo['normal_stock'];
					}else if(( $totalStockage + $totalPurchase - $totalOrdered) < $StockStrategyInfo['min_stock']){
						$suggestion->pending_stock_qty = $StockStrategyInfo['normal_stock'] - ( $totalStockage + $totalPurchase - $totalOrdered) ;
					} else {
						$suggestion->pending_stock_qty = 0;
					}
				}else if ($StockStrategyType == 2){
					//获取$period 内产品销售量
					$period = $StockStrategyInfo['count_sales_period'] * 24 * 3600;
					$startTime = time() - $period;
					$sql = "SELECT `sku`, sum( `quantity` ) as quantity FROM `od_order_item_v2` WHERE `sku`=:sku and `order_id` IN (SELECT `order_id` FROM  `od_order_v2` WHERE `paid_time` > :startTime AND `order_status` NOT IN ( 100, 600 ) ) group by `sku` ";
					$getSalesCommand = Yii::app()->subdb->createCommand($sql);
					//$getSalesCommand->bindParam(":sku",$pd->sku,PDO::PARAM_STR);
					$getSalesCommand->bindValue(":sku",$pd->sku,\PDO::PARAM_STR);
					$getSalesCommand->bindParam(":startTime",$startTime,\PDO::PARAM_INT);
					$periodSalesQty = 0;
					$tempResult = $getSalesCommand->queryAll();
					if(!empty($tempResult))
						$periodSalesQty = $tempResult[0]['quantity'];
					
					if($suggestion->pending_purchase_ship_qty != 0){
						$suggestion->pending_stock_qty = round($periodSalesQty * $StockStrategyInfo['stock_total_sales_percentage'] / 100);
					}else if(( $totalStockage + $totalPurchase - $totalOrdered ) < round($periodSalesQty * $StockStrategyInfo['min_total_sales_percentage'] / 100)){
						$suggestion->pending_stock_qty = round($periodSalesQty * $StockStrategyInfo['stock_total_sales_percentage'] / 100) - ( $totalStockage + $totalPurchase - $totalOrdered ) ;
					}else {
						$suggestion->pending_stock_qty = 0;
					}
				}elseif ($StockStrategyType == 0){
					$suggestion->pending_stock_qty = 0;
				}
				
				$suggestion->create_time = date('Y-m-d H:i:s', time());
				if($suggestion->pending_purchase_ship_qty || $suggestion->pending_stock_qty){
					$suggestion->save(false);
				}
				else if(!$suggestion->getIsNewRecord()){
					$suggestion->delete();
				}
			}
		}
		
		// 读取采购建议信息
		$purchaseSuggestions = array();
		$command = Yii::app()->subdb->createCommand();
		$command->from('pc_purchase_suggestion');
		
		if($suggest_type == 1){
			$command->select('*,pending_purchase_ship_qty as purchaseSug');
			$command->where(array('and','pending_purchase_ship_qty > 0',array('in','sku',array_keys($mapProdInfo))));
		}else{
			$command->select('*,(pending_purchase_ship_qty + pending_stock_qty )as purchaseSug');
			$command->where(array('and','(pending_purchase_ship_qty + pending_stock_qty ) > 0',array('in','sku',array_keys($mapProdInfo))));
		}

		$purchaseSuggestions = $command->queryAll();
		$result = array();
		foreach ($purchaseSuggestions as $index=>$ps){
			$result[$index] = $ps;
			$result[$index]['photo_primary'] = $mapProdInfo[$ps['sku']]->photo_primary;
			$result[$index]['name'] = $mapProdInfo[$ps['sku']]->name;
			if($suggest_type == 1 || empty($ps['pending_stock_qty']) ){
				$result[$index]['purchaseReasonStr'] = "缺货";
			}else if($suggest_type == 2 && empty($ps['pending_purchase_ship_qty'])){
				$result[$index]['purchaseReasonStr'] = "备货";
			}else{
				$result[$index]['purchaseReasonStr'] = "缺货及备货";
			}
			if(in_array($ps['sku'], array_keys($productTopSupplierInfo) )){
				$result[$index]['primary_supplier_name'] = $productTopSupplierInfo[$ps['sku']]['name'];
				$result[$index]['purchase_price'] = $productTopSupplierInfo[$ps['sku']]['purchase_price'];
			}else {
				$result[$index]['primary_supplier_name'] = '';
				$result[$index]['purchase_price'] = '';
			}
		}
		
		self::$purchaseSugSort = $sort;
		self::$purchaseSugOrder = $order;
		usort($result, array('PurchaseSugHelper', 'purchaseSugSort'));
			
		if ($formatJson) {
			return json_encode($result);
		} else {
			return $result;
		}
	}
	
	/*
	 * eagle 2.0 helper
	*/
	public static function listDataByCondition($params=array()){
		$suggest_type = 0;//默认查询所有建议类型
		if(isset($params['suggest_type'])){
			$suggest_type = $params['suggest_type'];
			unset($params['suggest_type']);
		}

		$pageSize = 20;
		if(isset($params['per-page'])){
			$pageSize = $params['per-page'];
			unset($params['per-page']);
		}
		if(isset($params['page'])){
			unset($params['page']);
		}
		
		
		/**
		 更新PurchaseSuggestion工作移到异步队列
		*/
		
		
		$productTopSupplierInfo = SupplierHelper::getProductTopSupplierInfo();
		
		// 读取采购建议信息
		$purchaseSuggestions = array();
		
		if($suggest_type == 1){
			$sql = PurchaseSuggestion::find()->select("*,pending_purchase_ship_qty as purchaseSug")
						->where(" pending_purchase_ship_qty > 0");
		}elseif($suggest_type == 2){
			$sql = PurchaseSuggestion::find()->select("*, pending_stock_qty as purchaseSug")
						->where("pending_stock_qty > 0");
		}else{
			$sql = PurchaseSuggestion::find()->select("*,(pending_purchase_ship_qty + pending_stock_qty )as purchaseSug")
						->where("(pending_purchase_ship_qty + pending_stock_qty ) > 0");
		}

		$result = array();
		$result['data']=array();
		$pagination = new Pagination([
				'pageSize' => $pageSize,
				'totalCount' => $sql->count(),
				'pageSizeLimit'=>[20,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;
		
		$sort = 'sku';
		$order = 'ASC';
		if(count($params)>0){
			foreach($params as $key=>$val){
				if ($key == 'sort') {
					$sort = $val;
				}
				elseif ($key == 'order') {
					$order = $val;
				}
				else{
					$sql->andWhere([$key =>$val]);
				}
			}
		}
		
		$purchaseSuggestions = $sql->orderBy("$sort $order")
			->limit($pagination->limit)
			->offset($pagination->offset)
			->asArray()
			->all();
		
		$mapProdInfo = array();
		$pd_sql = "select `sku`, `name`, `photo_primary`, `purchase_link` from `pd_product` where `sku` in (select distinct `sku` from `pc_purchase_suggestion` where `sku`<>'') ";
		$pd_info = Yii::$app->get('subdb')->createCommand($pd_sql)->queryAll();
		foreach ($pd_info as $info){
			$mapProdInfo[$info['sku']] = $info;
		}
		
		$skus = array();
		foreach($purchaseSuggestions as $one){
			$skus[] = $one['sku'];
		}
		//获取采购链接信息
		$pd_sp_list = ProductSuppliersHelper::getProductPurchaseLink($skus);
		
		foreach ($purchaseSuggestions as $index=>$ps){
			$result['data'][$index] = $ps;
			$result['data'][$index]['photo_primary'] =(!empty($mapProdInfo[$ps['sku']]))?$mapProdInfo[$ps['sku']]['photo_primary']:'';
			$result['data'][$index]['name'] =(!empty($mapProdInfo[$ps['sku']]))?$mapProdInfo[$ps['sku']]['name']:'';
			$result['data'][$index]['purchase_link'] =(!empty($mapProdInfo[$ps['sku']]))?$mapProdInfo[$ps['sku']]['purchase_link']:'';
			if(!empty($ps['pending_stock_qty']) && empty($ps['pending_purchase_ship_qty']) ){
				$result['data'][$index]['purchaseReasonStr'] = "备货";
			}elseif(empty($ps['pending_stock_qty']) && !empty($ps['pending_purchase_ship_qty']) ){
				$result['data'][$index]['purchaseReasonStr'] = "缺货";
			}else{
				$result['data'][$index]['purchaseReasonStr'] = "缺货及备货";
				if($suggest_type == 1)
					$result['data'][$index]['purchaseReasonStr'] = "缺货";
				if($suggest_type == 2){
				$result['data'][$index]['purchaseReasonStr'] = "备货(另有缺货：".$ps['pending_purchase_ship_qty'].")";
				}
			}
			if(in_array($ps['sku'], array_keys($productTopSupplierInfo) )){
				$result['data'][$index]['primary_supplier_name'] = isset($productTopSupplierInfo[$ps['sku']]['name'])?$productTopSupplierInfo[$ps['sku']]['name']:'';
				$result['data'][$index]['purchase_price'] = isset($productTopSupplierInfo[$ps['sku']]['purchase_price'])?$productTopSupplierInfo[$ps['sku']]['purchase_price']:'';
			}else {
				$result['data'][$index]['primary_supplier_name'] = '';
				$result['data'][$index]['purchase_price'] = '';
			}
			
			//采购链接信息
			$result['data'][$index]['purchase_link_list'] = '';
			if(array_key_exists($ps['sku'], $pd_sp_list)){
				$result['data'][$index]['purchase_link'] = $pd_sp_list[$ps['sku']]['purchase_link'];
				$result['data'][$index]['purchase_link_list'] = json_encode($pd_sp_list[$ps['sku']]['list']);
			}
		}

		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  读取缺货采购建议
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2016/09/06				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listDataByCondition_Q($params=array())
	{
		//更新特定客户待发货数量
		InventoryHelper::UpdateUserOrdered(0.5);
		
		$pageSize = 20;
		if(isset($params['per-page'])){
			$pageSize = $params['per-page'];
			unset($params['per-page']);
		}
		if(isset($params['page'])){
			unset($params['page']);
		}
	
		//商品供应商、报价信息
		$productTopSupplierInfo = SupplierHelper::getProductTopSupplierInfo();
	
		//仓库信息
		$warehouseInfo = PurchaseHelper::warehouse();
		
		//读取需采购的库存、商品信息，(待发货 - 在库 - 在途 + 安全库存) > 0
		$sql = ProductStock::find()->select("wh_product_stock.prod_stock_id, wh_product_stock.sku, wh_product_stock.warehouse_id, ( IFNULL(cast(wh_product_stock.`qty_ordered` as signed),0) - IFNULL(cast(wh_product_stock.`qty_in_stock` as signed),0) - IFNULL(cast(wh_product_stock.`qty_purchased_coming` as signed),0) + cast(wh_product_stock.`safety_stock` as signed)) as purchaseSug,"
		        ."pd_product.`name`, pd_product.`photo_primary`, pd_product.`purchase_link`")
		    ->innerJoin("pd_product", "wh_product_stock.sku = pd_product.sku")
			->where("( IFNULL(cast(wh_product_stock.`qty_ordered` as signed),0) - IFNULL(cast(wh_product_stock.`qty_in_stock` as signed),0) - IFNULL(cast(wh_product_stock.`qty_purchased_coming` as signed),0) + IFNULL(cast(wh_product_stock.`safety_stock` as signed),0)) > 0")
		    ->andwhere("pd_product.`type`!='B'");
		
		$sort = 'sku';
		$order = 'ASC';
		if(count($params)>0)
	    {
			foreach($params as $key=>$val)
		    {
		        if($val=='')
		        	continue;
		        switch ($key)
		        {
		        	case 'sort':
		        		$sort = $val;
		        		break;
		        	case 'order':
		        		$order = $val;
		        		break;
		        	case 'skus':
		        		$sql->andWhere(['wh_product_stock.sku'=>$val]);
		        		break;
		        	case 'page':
		        		break;
		        	case 'per-page':
		        		break;
		        	default:
		        		$sql->andWhere(['wh_product_stock.'.$key=>$val]);
		        		break;
		        }
			}
		}
		
		//读取是否显示海外仓仓库
		$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
		if(empty($is_show))
			$is_show = 0;
		//不显示海外仓仓库
		if($is_show == 0)
		{
			$sql = $sql->andWhere("wh_product_stock.warehouse_id in (select warehouse_id from wh_warehouse where is_oversea=0 and is_active='Y')");
		}
		//不显示未启用仓库
		else 
		{
		    $sql = $sql->andWhere("wh_product_stock.warehouse_id in (select warehouse_id from wh_warehouse where is_active='Y')");
		}
		
		$result = array();
		$result['data']=array();
		$pagination = new Pagination([
				'pageSize' => $pageSize,
				'totalCount' => $sql->count(),
				'pageSizeLimit'=>[20,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;
	
		$productStockInfo = $sql->orderBy("$sort $order")
			->limit($pagination->limit)
			->offset($pagination->offset)
			->asArray()
			->all();
		$skus = array();
		foreach($productStockInfo as $one){
			$skus[] = $one['sku'];
		}
		
		//获取采购链接信息
		$pd_sp_list = ProductSuppliersHelper::getProductPurchaseLink($skus);
	
		foreach ($productStockInfo as $index=>$ps){
			$result['data'][$index] = $ps;
			$result['data'][$index]['purchaseReasonStr'] = "缺货";
			//供应商、报价信息
			$result['data'][$index]['primary_supplier_name'] = !empty($productTopSupplierInfo[$ps['sku']]) ? $productTopSupplierInfo[$ps['sku']]['name'] : '';
			$result['data'][$index]['purchase_price'] = !empty($productTopSupplierInfo[$ps['sku']]) ? $productTopSupplierInfo[$ps['sku']]['purchase_price'] : '';
			//仓库信息
			$result['data'][$index]['warehouse'] = !empty($warehouseInfo[$ps['warehouse_id']]) ? $warehouseInfo[$ps['warehouse_id']] : '';
			
			//采购链接信息
			$result['data'][$index]['purchase_link_list'] = '';
			if(array_key_exists($ps['sku'], $pd_sp_list)){
				$result['data'][$index]['purchase_link'] = $pd_sp_list[$ps['sku']]['purchase_link'];
				$result['data'][$index]['purchase_link_list'] = json_encode($pd_sp_list[$ps['sku']]['list']);
			}
		}

		return $result;
	}
	
	//返回客户设置的备货策略信息
	//e.g.
//	$strategiesInfo = array("stocking_strategy"=>2,
//						"normal_stock"=>50,
//						"min_stock"=>30,
//						"count_sales_period"=>14,
//						"min_total_sales_percentage"=>50,
//						"stock_total_sales_percentage"=>60);
	public static function getStockingStrategyInfo(){
		$stockingStrategyInfo = ConfigHelper::getConfig("PurchaseSug/StockingStrategy",'NO_CACHE');
		$normalStock = 50;
		$minStock = 30;
		$countSalesPeriod = 14;
		$minTotalSalesPercentage = 20;
		$stockTotalSalesPercentage = 60;
		
		if($stockingStrategyInfo != null){
			$stockingStrategyInfo = json_decode($stockingStrategyInfo,true);
			$stockingStrategy = $stockingStrategyInfo['stocking_strategy'];
			if(isset($stockingStrategyInfo['normal_stock']))
				$normalStock = $stockingStrategyInfo['normal_stock'];
			
			if(isset($stockingStrategyInfo['min_stock']))
				$minStock = $stockingStrategyInfo['min_stock'];
			
			if(isset($stockingStrategyInfo['count_sales_period']))
				$countSalesPeriod = $stockingStrategyInfo['count_sales_period'];
			
			if(isset($stockingStrategyInfo['min_total_sales_percentage']))
				$minTotalSalesPercentage = $stockingStrategyInfo['min_total_sales_percentage'];
			
			if(isset($stockingStrategyInfo['stock_total_sales_percentage']))
				$stockTotalSalesPercentage = $stockingStrategyInfo['stock_total_sales_percentage'];	
		}else{
			$stockingStrategy = 0;
		}

		return array( 'stocking_strategy'=>$stockingStrategy,'normal_stock'=>$normalStock,'min_stock'=>$minStock,'count_sales_period'=>$countSalesPeriod,'min_total_sales_percentage'=>$minTotalSalesPercentage,'stock_total_sales_percentage'=>$stockTotalSalesPercentage );
	}


	protected static $StockingStrategyInfoDescription = array(
		'stocking_strategy'=>'备货策略类型',
		'min_stock'=>'备货策略类型1：提醒备货下限',
		'normal_stock'=>'备货策略类型1：备货上限',
		'count_sales_period'=>'备货策略类型2：统计销量天数',
		'min_total_sales_percentage'=>'备货策略类型2：提醒备货下限(占一段时期内销量的百分比)',
		'stock_total_sales_percentage'=>'备货策略类型2：备货上限(占一段时期内销量的百分比)',
	);
	// 设置用户策略信息
	public static function setStockingStrategyInfo($strategyInfo = array()){
		if(!empty($strategyInfo)){
			if(isset($strategyInfo['stocking_strategy'])){
				$stockingStrategy = $strategyInfo['stocking_strategy'];
				if($stockingStrategy == 0){
					unset($strategyInfo['min_stock']);
					unset($strategyInfo['normal_stock']);
					unset($strategyInfo['count_sales_period']);
					unset($strategyInfo['min_total_sales_percentage']);
					unset($strategyInfo['stock_total_sales_percentage']);
				}elseif ($stockingStrategy == 1){
					unset($strategyInfo['count_sales_period']);
					unset($strategyInfo['min_total_sales_percentage']);
					unset($strategyInfo['stock_total_sales_percentage']);
				}else {
					unset($strategyInfo['min_stock']);
					unset($strategyInfo['normal_stock']);
				}
				if(!self::setUserStockingStrategyConfig($strategyInfo)){
					return array("success"=>false,"message"=>"备货策略保存失败，请重试。");
				}
				// 将所有采购建议设置为dirty
				self::changePurchaseSugDirtyBySku(true,true);
				return array("success"=>true,"message"=>'');
			}else {
				return array("success"=>false,"message"=>"请选择要保存的策略");
			}
		}else{
			return array("success"=>false,"message"=>"没有要保存的策略信息");
		}
	}
	
	private static $purchaseSugSort;
	private static $purchaseSugOrder;
	private static function purchaseSugSort($a, $b) {
		if ($a[self::$purchaseSugSort] == $b[self::$purchaseSugSort]) {
			return 0;
		}
		
		if (self::$purchaseSugOrder == 'asc') {
			return $a[self::$purchaseSugSort] > $b[self::$purchaseSugSort] ? 1 : -1;
		} else {
			return $a[self::$purchaseSugSort] < $b[self::$purchaseSugSort] ? 1 : -1;
		}
	}
	
	/*eagle 1.0 function
	 * eagle 2.0 StockingStrategyInfo 储存位置变更,因此弃用
	 * 
	*/
	public static function setOneUserConfig($key, $value=null, $type=null, $description=null){
		$userconfig = UserConfig::model()->findByPk($key);
		try {
			if(!$userconfig){
				$userconfig = new UserConfig;
				$userconfig->keyid         = $key;
				$userconfig->create_time = time();
				$userconfig->update_time = time();
				$userconfig->value       = $value;
				$userconfig->type        = $type;
				$userconfig->description = $description;
				return $userconfig->save(false);
			}else if($value != $userconfig->value){
				if(isset($value)) $userconfig->value       = $value;
				if(isset($type)) $userconfig->type        = $type;
				if($description) $userconfig->description = $description;
				$userconfig->update_time = time();
				return $userconfig->save(false);
			}else 
				return true;
		} catch (Exception $e) {
			SysLogHelper::SysLog_Create("purchase",__CLASS__, __FUNCTION__,"","set stocking strategy exception: $e", "trace");
			return false;
			
		}
	}

	public static function setUserStockingStrategyConfig($description=[]){
		if(count($description)>0){
			$description['create_time']=TimeUtil::getNow();
			$description['update_time']=TimeUtil::getNow();
		}
		ConfigHelper::setConfig("PurchaseSug/StockingStrategy", json_encode($description));
		//SysLogHelper::SysLog_Create("purchase",__CLASS__, __FUNCTION__,"","set stocking strategy",json_encode($description));
		OperationLogHelper::log("purchase", $description['stocking_strategy'], "setUserStockingStrategyConfig");
		return true;
				
		
	}
	//TODO 涉及到采购建议的更新
	// 订单模块的订单生成、取消.etc,和采购模块中采购单的生成或修改备货策略,都需要将涉及到的sku的采购建议设置为dirty
	public static function changePurchaseSugDirtyBySku($sku = false , $all = false){
		if($sku){
			$attributes = array();
			$criteria = '';
            if(!$all){
            	if(is_array($sku))
            		$criteria = array('in','sku',$sku);
            	else 
            		$criteria = array('sku'=>$sku);
            }
            $attributes['create_time'] = date('Y-m-d H:i:s', time() - 3600);
            PurchaseSuggestion::updateAll($attributes, $criteria);
		}	
	}
	
	/**
	 * 后台处理Purchasesug的数据记录增删改，当获取到status为P的记录时，执行。
	 * 该uid的记录执行所有数据库操作都成功的话，删除该记录；
	 * 否者status改为F，记录log
	 */
	public static function cronCronCalculatePurchasesug(){
		$sql="SELECT * FROM `pc_suggest_queue` WHERE `status` = 'P' ORDER BY `create_time` ASC LIMIT 0 , 5";//一次计算最多5个用户，避免卡太久
		$command = \Yii::$app->get('db_queue')->createCommand($sql);
		$pendingPurchaseQueue = $command->queryAll();
		
		foreach ($pendingPurchaseQueue as $pendingPurchase){
			$pending_id = $pendingPurchase['id'];
			$uid = $pendingPurchase['puid'];
			
			echo "\n start uid :".$uid." calculate purchasesug...";
			
			try{
				$update_time = date('Y-m-d H:i:s', time());
				$d_sql = "UPDATE `pc_suggest_queue` SET `status`='S',`update_time`='$update_time' WHERE `id` = $pending_id ";
				$command = \Yii::$app->get('db_queue')->createCommand($d_sql);
				$command->execute();
					
				 
				// 普通/普通(变参子产品)
				$allProds = Product::find()->select('sku')->where("sku <> '' and (type='S' or type='L') ")->asArray()->all();
				$totalProd = count($allProds);
				
				// refresh 待发货数量
				//InventoryApiHelper::refreshAllOSOrderProductPendingShipSummary();
				
				// 获取所有产品的仓库的实际存量,采购在途数量,待发货量
				$allProdsTotalStockage = array();
				$prodStockageMapping = array();
				
				//读取是否显示海外仓仓库
				$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
				if(empty($is_show))
					$is_show = 0;
				//不显示海外仓仓库
				if($is_show == 0)
				{
					$sql_s = "select sku,sum(qty_in_stock) as total_stockage, sum(qty_purchased_coming) as total_purchase from wh_product_stock where warehouse_id in (select warehouse_id from wh_warehouse where is_active='Y' and is_oversea=0) group by sku ";
				}
				else 
				{
				    $sql_s = "select sku,sum(qty_in_stock) as total_stockage, sum(qty_purchased_coming) as total_purchase from wh_product_stock where warehouse_id in (select warehouse_id from wh_warehouse where is_active='Y') group by sku ";
				}
				$getStockageCommand = \Yii::$app->get('subdb')->createCommand($sql_s);
				$allProdsTotalStockage = $getStockageCommand->queryAll();
				foreach ($allProdsTotalStockage as $prodTotalStockage){
					$prodStockageMapping[$prodTotalStockage['sku']]['total_stockage'] = $prodTotalStockage['total_stockage'];
					$prodStockageMapping[$prodTotalStockage['sku']]['total_purchase'] = $prodTotalStockage['total_purchase'];
				}
				
				//获取备货策略信息
				$StockStrategyInfo = self::getStockingStrategyInfo();
				$StockStrategyType = $StockStrategyInfo['stocking_strategy'];
				
				$mapProdInfo = array();
				
				// loop 所有产品,生成采购建议
				//分页处理，避免爆内存
				//$query_page = 0;//生成采购建议的产品 进行中的分页
				$query_pre_page = 500;//每页处理产品数
				
				$status_OK = true;//是否可以从列队表移除
				$db_err = '';
				
				for ($i=0;$i<= ceil($totalProd/$query_pre_page);$i++){
					$query = Product::find()->where("sku <> '' and (type='S' or type='L' or type='C') ");
				
					$prods = $query
						->limit($query_pre_page)
						->offset($i*$query_pre_page)
						->asArray()->all();
					
					foreach ($prods as $index=>$pd){
						$suggestion = PurchaseSuggestion::find()->where(['sku'=>$pd['sku']])->One();
						if($suggestion == null){
						 $suggestion = new PurchaseSuggestion();
						 $suggestion->sku = $pd['sku'];
						}
						$mapProdInfo[$pd['sku']] = $pd;
						//如果当前时间 和 该 sku 的采购建议 生成时间 间隔小于 1 分钟，则skip
						if((time() - strtotime($suggestion->create_time)) > 60 || $suggestion->getIsNewRecord()){
							//建议采购发货数量
							if(isset($prodStockageMapping[$pd['sku']])){
								$totalStockage = ($prodStockageMapping[$pd['sku']]['total_stockage'] > 0)?$prodStockageMapping[$pd['sku']]['total_stockage']:0;
								$totalPurchase = ($prodStockageMapping[$pd['sku']]['total_purchase'] > 0)?$prodStockageMapping[$pd['sku']]['total_purchase']:0;
							}else{
								$totalStockage = 0;
								$totalPurchase = 0;
							}
							
							$totalOrdered = ($pd['pending_ship_qty'] > 0)?$pd['pending_ship_qty']:0;// $pd->pending_ship_qty 需要helper及时更新
							$pendingPurchaseShipQty = $totalStockage + $totalPurchase - $totalOrdered;
							$suggestion->pending_purchase_ship_qty = (($pendingPurchaseShipQty < 0)? abs($pendingPurchaseShipQty):0 );
							
							//计算建议备货数量
							if($StockStrategyType == 1 ){
								if($suggestion->pending_purchase_ship_qty != 0){
									$suggestion->pending_stock_qty = $StockStrategyInfo['normal_stock'];
								}else if(( $totalStockage + $totalPurchase - $totalOrdered) < $StockStrategyInfo['min_stock']){
									$suggestion->pending_stock_qty = $StockStrategyInfo['normal_stock'] - ( $totalStockage + $totalPurchase - $totalOrdered) ;
								} else {
							 		$suggestion->pending_stock_qty = 0;
							 	}
							}else if ($StockStrategyType == 2){
								//获取$period 内产品销售量
								$period = $StockStrategyInfo['count_sales_period'] * 24 * 3600;
								$startTime = time() - $period;
							 	$sql_o = "SELECT `root_sku`, sum( `quantity` ) as quantity FROM `od_order_item_v2` WHERE (manual_status is null or manual_status!='disable') and `root_sku`=:sku and `order_id` IN (SELECT `order_id` FROM  `od_order_v2` WHERE `paid_time` > :startTime AND `order_status` NOT IN ( 100, 600 ) ) group by `root_sku` ";
							 	$getSalesCommand = \Yii::$app->get('subdb')->createCommand($sql_o);
							 	$getSalesCommand->bindValue(":sku",$pd['sku'],\PDO::PARAM_STR);
							 	$getSalesCommand->bindParam(":startTime",$startTime,\PDO::PARAM_INT);
							 	$periodSalesQty = 0;
							 	$tempResult = $getSalesCommand->queryAll();
							 	//echo "\n ".$getSalesCommand->getRawSql();
							 	if(!empty($tempResult))
							 		$periodSalesQty = $tempResult[0]['quantity'];
							 	
							 	//判断是否捆绑商品的子产品，如果是，把捆绑商品的销售也包含进来
							 	$bundlelist = ProductBundleRelationship::find()->select(['bdsku','qty'])->where(["assku"=>$pd['sku']])->asArray()->all();
							 	if(!empty($bundlelist))
							 	{
							 	    foreach($bundlelist as $bundle)
							 	    {
							 	        $getSalesCommand = \Yii::$app->get('subdb')->createCommand($sql_o);
							 	        $getSalesCommand->bindValue(":sku",$bundle['bdsku'],\PDO::PARAM_STR);
							 	        $getSalesCommand->bindParam(":startTime",$startTime,\PDO::PARAM_INT);
							 	        $tempResult = $getSalesCommand->queryAll();
							 	        $bundle_Qty = 0;
							 	        
							 	        if(!empty($tempResult))
							 	        	$bundle_Qty = $tempResult[0]['quantity'];
							 	        
							 	        //加上捆绑商品销售的子产品数量
							 	        $periodSalesQty += $bundle_Qty * $bundle['qty'];
							 	    }
							 	}

							 	if($suggestion->pending_purchase_ship_qty != 0){
							 		$suggestion->pending_stock_qty = round($periodSalesQty * $StockStrategyInfo['stock_total_sales_percentage'] / 100);
							 	}else if(( $totalStockage + $totalPurchase - $totalOrdered ) < round($periodSalesQty * $StockStrategyInfo['min_total_sales_percentage'] / 100)){
							 		$suggestion->pending_stock_qty = round($periodSalesQty * $StockStrategyInfo['stock_total_sales_percentage'] / 100) - ( $totalStockage + $totalPurchase - $totalOrdered ) ;
							 	}else {
							 		$suggestion->pending_stock_qty = 0;
							 	}
							}elseif ($StockStrategyType == 0){
							 	$suggestion->pending_stock_qty = 0;
							}
							
							$suggestion->create_time =time();
							if(!empty($suggestion->pending_purchase_ship_qty) || !empty($suggestion->pending_stock_qty)){
						 		if(!$suggestion->save(false)){
						 			$status_OK = false;
						 			$message = $suggestion->sku."\n suggestion db error:";
						 			foreach ($suggestion->errors as $k => $anError){
						 				$message .=  $k." error:".$anError[0].";";
						 			}
						 			$db_err .= $message;
						 			echo $message;
						 			continue;
						 		}
						 	}
							else if(!$suggestion->getIsNewRecord()){
								if(!$suggestion->delete()){
									$status_OK = false;
									$message = $suggestion->sku."suggestion db error:";
						 			foreach ($suggestion->errors as $k => $anError){
						 				$message .= "<br>". $k." error:".$anError[0];
						 			}
						 			$db_err .= $message;
						 			echo $message;
						 			continue;
								}
							}
						}
					}
				}
				
			} catch (\Exception $e) {
				if(is_array($e->getMessage()))
					$errMsg = json_encode($e->getMessage());
				else 
					$errMsg = $e->getMessage();
				echo "\n cronCronCalculatePurchasesug Exception:".$errMsg;
				
				$update_time = date('Y-m-d H:i:s', time());
				$r_sql = "UPDATE `pc_suggest_queue` SET `status`='F',`update_time`='$update_time',`message`=:errMsg  WHERE `id` = $pending_id ";
				$command = \Yii::$app->get('db_queue')->createCommand($r_sql);
				$command->bindValue(":errMsg",$errMsg,\PDO::PARAM_STR);
				$command->execute();
				
				return false;
			}
			if(!empty($db_err) && !$status_OK){
				$update_time = date('Y-m-d H:i:s', time());
				$r_sql = "UPDATE `pc_suggest_queue` SET `status`='F',`update_time`='$update_time',`message`=:errMsg  WHERE `id` = $pending_id ";
				$command = \Yii::$app->get('db_queue')->createCommand($r_sql);
				$command->bindValue(":errMsg",$db_err,\PDO::PARAM_STR);
				$command->execute();
			}else{
				$r_sql = "DELETE FROM `pc_suggest_queue` WHERE `id` = $pending_id ";
				$command = \Yii::$app->get('db_queue')->createCommand($r_sql);
				$command->execute();
			}
			$info['date'] = date('Y-m-d H:i:s', time());
			$info['message'] = isset($db_err)?$db_err:''; 
			ConfigHelper::setConfig("PurchaseSug/CalculatePurchaseSugDate",json_encode($info));
			
		}
		
		return true;
	}
	
	/*获取见单采购的item list
	 * @parmas	array	$parmas		筛选条件
	 * @parmas	string	$groupBy	以od_order_item表的什么字段来区分不同的商品(由于不同平台之间相同的sku可能是出售不同的商品，且不一定所有平台都有item id，所以需要用户给定值)
	 */
	public static function getMeetOrderItems($params=[],$groupBy='sku'){
		$result=[];
		$search_sku = [];
		$correlation_sku = [];
		
		//当搜索条件包含sku时
		if(!empty($params['skus']))
		{
		    $search_sku = $params['skus'];
		    
		    //查询其root sku，包括在查询条件内
		    foreach ($params['skus'] as $sku)
		    {
		    	if(!in_array($sku, $search_sku)){
		    		$search_sku[] = $sku;
		    	}
		    	
    		    $root_sku = ProductApiHelper::getRootSKUByAlias($sku);
    		    if(!empty($root_sku) && $root_sku != $root_sku){
    		    	if(!in_array($root_sku, $search_sku)){
    		    		$search_sku[] = $root_sku;
    		    	}
    		    }
		    }
		    $correlation_sku = $search_sku;
		    
		    //其对应捆绑商品，也包含在查询内
			$bundlelist_root = ProductBundleRelationship::find()->select(['bdsku'])->where(["assku"=>$search_sku])->asArray()->all();
			foreach ($bundlelist_root as $root)
			{
			    if(!in_array($root['bdsku'], $correlation_sku)){
			    	$correlation_sku[] = $root['bdsku'];
			    }
			}
			
			//其对应子商品，也包含在查询内
			$bundlelist_root = ProductBundleRelationship::find()->select(['assku'])->where(["bdsku"=>$search_sku])->asArray()->all();
			foreach ($bundlelist_root as $root)
			{
				 if(!in_array($root['assku'], $correlation_sku)){
				    $correlation_sku[] = $root['assku'];
				 }
			}
		}
		
		//已绑定的店铺账号
		$stores = '';
		$platformAccountInfo = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
		foreach ($platformAccountInfo as $p_key=>$p_v){
			if(!empty($p_v)){
				foreach ($p_v as $s_key=>$s_v){
				    if($stores == ''){
				        $stores = "'".$s_key."'";
				    }
				    else{
				        $stores .= ",'".$s_key."'";
				    }
				}
			}
		}
		
				$query = OdOrderItem::find()->select("`sku`,`root_sku`,`product_name`,`photo_primary`,sum(`quantity`) as total_quantity, `product_attributes`")->Where(['not in','sku',CdiscountOrderInterface::getNonDeliverySku()])
		->andwhere("manual_status is null or manual_status!='disable'")
		->andwhere(["delivery_status"=>'allow']);
		foreach ($params as $key=>$value){
			if($value=='')
				continue;
			switch ($key){
				case 'start_date':
					$query->andWhere("order_id in (select `order_id` from `od_order_v2` where `order_source_create_time`>=$value)");//timestamp
					break;
				case 'end_date':
					$query->andWhere("order_id in (select `order_id` from `od_order_v2` where `order_source_create_time`<=$value)");//timestamp
					break;
				case 'order_source':
					$query->andWhere("order_id in (select `order_id` from `od_order_v2` where `order_source`='$value')");
					break;
				case 'skus':
				    // 这里orWhere 把前面的and 条件废了
					//$query->andWhere(['root_sku'=>$correlation_sku])->orWhere(['sku'=>$correlation_sku]);
				    $query->andWhere(['or', ['root_sku' => $correlation_sku], ['sku' => $correlation_sku]]);
					break;
				case 'order_status':
					break;
				case 'page':
					break;
				case 'per-page':
					break;
				default:
					$query->andWhere([$key=>$value]);
					break;
			}
		}
		//print_r($query);die;
		//print_r('ok');die;
		//订单状态筛选
	    //已付款未发货，平台状态也是未发货
	    if( !empty( $params['order_status']) && $params['order_status'] == 1)
	        $query->andWhere("order_id in (select `order_id` from `od_order_v2` where `order_status`>=200 and `order_status`<500 and shipping_status<>2)");
	    //已付款或已虚拟发货
	    else if( !empty( $params['order_status']) && $params['order_status'] == 2)
	        $query->andWhere("order_id in (select `order_id` from `od_order_v2` where `order_status`>=200 and (`order_status`<300 or (`order_status`<500 and `sync_shipped_status`='C')))");
	    //只是已付款
	    else 
	        $query->andWhere("order_id in (select `order_id` from `od_order_v2` where `order_status`>=200 and `order_status`<300)");
		
	    //正常的订单
		$query->andWhere("order_id in (select `order_id` from `od_order_v2` where (`order_relation`='normal' or `order_relation`='sm') )");

		if($stores == ''){
			$stores = "'0'";
		}
    	//只显示已绑定的账号的信息
    	$query->andWhere("order_id in (select `order_id` from `od_order_v2` where `selleruserid` in (".$stores.") )");
		
		//$query->andWhere("order_id in (select `order_id` from `od_order_v2` where `order_status`>=200 and `order_status`<300 and (`order_relation`='normal' or `order_relation`='sm') )");//只计算小老板状态为已付款的订单
		if($groupBy == "sku")
		    $query->groupBy("`sku`,`root_sku`");
		else 
		    $query->groupBy("$groupBy");
		
		 $pagination = new Pagination([
	    	'totalCount' => $query->count(),
	    	//'params'=>$_REQUEST,
	    	'pageSize'=>200,//isset($params['per-page'])?$params['per-page']:50,
	    	'pageSizeLimit'=>[20,200],//每页显示条数范围
	    ]);
		 
		$result['pagination'] = $pagination;
		 
//         $tmpQ = clone $query;
//         $tmpCommand = $tmpQ
//         ->offset($pagination->offset)
//         ->limit($pagination->limit)
//         ->createCommand();
// 		echo $tmpCommand->getRawSql().'<br />';
		 
		$data = $query
				->offset($pagination->offset)
				->limit($pagination->limit)
				->orderBy('sku')
				->asArray()->all();
		
		$skus = array();
		$order_info = array();
		
		//把所有别名都转换为root sku，并合并
		foreach ($data as $r){
		    $sku = $r['sku'];
			$rootSku = $r['root_sku'];
			
			if(empty($rootSku)){
				//判断sku是否已存在，已存在则合并
				if(in_array($sku, $order_info)){
					$order_info[$sku]['total_quantity'] = $order_info[$sku]['total_quantity'] + $r['total_quantity'];
				}
				else{
					$order_info[$sku] = $r;
				}
			}
			else{
			    //判断root sku是否已存在，已存在则合并
			    if(array_key_exists($rootSku, $order_info)){
			        $order_info[$rootSku]['total_quantity'] = $order_info[$rootSku]['total_quantity'] + $r['total_quantity'];
			    }
			    else{
    				$skus[] = $rootSku;
    				$r['sku'] = $rootSku;
    				$order_info[$rootSku] = $r;
			    }
			}
		}
		
		//商品信息
		$prods = Product::find()->where(['sku'=>$skus])->asArray()->all();
		$prod_info = [];
		
		//判断是否为捆绑商品，是则转换为子产品，并合并
		$sku_list = array();
		foreach ($prods as $p){
		    $sku = $p['sku'];
		    //判断是否捆绑商品
		    if($p['type'] == 'B' && array_key_exists($sku, $order_info)){
		        $qty = $order_info[$sku]['total_quantity'];
		        $product_attributes = $order_info[$sku]['product_attributes'];
		        
		        //捆绑商品，需要拆分为子产品信息，当查询SKU时，只查子产品只查这个SKU
		        if(!empty($search_sku) && !in_array($p['sku'], $search_sku))
		            $bundlelist = ProductBundleRelationship::find()->select(['assku','qty'])->where(["bdsku"=>$p['sku']])->andwhere(["assku"=>$search_sku])->asArray()->all();
		        else
		            $bundlelist = ProductBundleRelationship::find()->select(['assku','qty'])->where(["bdsku"=>$p['sku']])->asArray()->all();
		        
		        if(!empty($bundlelist)){
		            foreach ($bundlelist as $bundle)
		            {
		                //判断sku是否已存在
		                $assku = $bundle['assku'];
		                if(array_key_exists($assku, $order_info))
		                {
		                    //合并数量
		                    $order_info[$assku]['total_quantity'] += $bundle['qty'] * $qty;
		                }
		                else 
		                {
		                    //插入子产品信息
    		                $item = [];
    		                $item['sku'] = $assku;
    		                $item['root_sku'] = $assku;
    		                $item['total_quantity'] = $bundle['qty'] * $qty;
    		                $item['product_attributes'] = $product_attributes;
    		                $item['product_name'] = '';
    		                $item['photo_primary'] = '';
    		                //子产品信息
    		                $product_list = Product::find()->where(['sku'=>$assku])->asArray()->all();
    		                if(count($product_list) > 0)
    		                {
    		                	$item['product_name'] = $product_list[0]['name'];
    		                	$item['photo_primary'] = $product_list[0]['photo_primary'];
    		                	$prod_info[$assku] = $product_list[0];
    		                	$sku_list[] = $assku;
    		                }
    		                
    		                $order_info[$assku] = $item;
		                }
		            }
		        }
		        
		        //删除捆绑商品
		        unset($order_info[$sku]);
		    }
		    else 
		    {
		    	$sku_list[] = $sku;
			    $prod_info[$sku] = $p;
		    }
		}
		
		//获取采购链接信息
		$pd_sp_list = ProductSuppliersHelper::getProductPurchaseLink($sku_list);
		foreach ($prod_info as &$one){
			$one['purchase_link_list'] = '';
			if(array_key_exists($one['sku'], $pd_sp_list)){
				$one['purchase_link'] = $pd_sp_list[$one['sku']]['purchase_link'];
				$one['purchase_link_list'] = json_encode($pd_sp_list[$one['sku']]['list']);
			}
		}
		
		//配对商品更改商品名称
		foreach ($order_info as $k => $item){
			$rsku = $item['root_sku'];
			if(!empty($rsku) && array_key_exists($rsku, $prod_info)){
				$order_info[$k]['product_name'] = $prod_info[$rsku]['name'];
				$order_info[$k]['photo_primary'] = $prod_info[$rsku]['photo_primary'];
			}
		}
		
		$result['items'] = array();
		//所有键转换为大写
		$order_info_UPPER = array_change_key_case($order_info, CASE_UPPER);
		//按照sku重新排序
		ksort($order_info_UPPER, 2);
		foreach ($order_info_UPPER as $index => $v){
		    $result['items'][] = $v;
		}
		
		$result['prod_info'] = $prod_info;
		return $result;
	}
	
	public static function refreshSuggestion(){
		try
		{
			//清除所有待发货数量
			$sql = "update wh_product_stock set qty_ordered=0 ";
			$command = Yii::$app->get('subdb')->createCommand($sql);
			$command->execute();
				
			$sql = "select root_sku from od_order_item_v2 where order_id in (
		            select order_id from od_order_v2 where order_status>=200 and order_status<500 and shipping_status<>2 and (order_relation='normal' or order_relation='sm'))
		    		and root_sku is not null and root_sku!=''
		            group by root_sku";
		
			$command = Yii::$app->get('subdb')->createCommand($sql);
			$rows = $command->queryALL();
		
			if(!empty($rows))
			{
				$sku = array();
				foreach ($rows as $index=>$row)
				{
					if(!empty($row['root_sku']))
					{
						$sku[] = $row['root_sku'];
					}
				}
				$ret = WarehouseHelper::RefreshSomeQtyOrdered($sku);
				return json_encode($ret);
			}
		}
		catch(\Exception $e)
		{
			return json_encode(['status'=>1, 'msg'=>$e->getMessage()]);
		}
	}
	
}

?>
