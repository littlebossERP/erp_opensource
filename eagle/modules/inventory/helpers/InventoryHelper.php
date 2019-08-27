<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\inventory\helpers;


use yii;

use eagle\modules\inventory\models\ProductStock;
use eagle\modules\inventory\models\StockChange;
use eagle\modules\inventory\models\StockChangeDetail;

use yii\data\Pagination;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\util\helpers\HttpHelper;

use eagle\modules\purchase\models\Purchase;
use eagle\modules\purchase\models\PurchaseItems;
use eagle\modules\purchase\models\PurchaseArrivals;
use eagle\modules\catalog\models\Product;
use eagle\modules\catalog\helpers\ProductHelper;


use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\util\helpers\TimeUtil;
use Zend\Db\Sql\Where;
use yii\db\Transaction;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\purchase\helpers\PurchaseHelper;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\inventory\models\WarehouseMatchingRule;
use Qiniu\json_decode;
use yii\db\Query;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\modules\util\helpers\ConfigHelper;
use yii\data\Sort;
use console\controllers\InventoryController;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\catalog\models\ProductClassification;
use eagle\models\UserInfo;
use eagle\models\UserBase;
use eagle\modules\permission\helpers\UserHelper;
use eagle\modules\inventory\models\StockAllocation;
use eagle\modules\inventory\models\StockAllocationDetail;
use yii\db\Command;
use eagle\modules\catalog\models\ProductSuppliers;

/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
class InventoryHelper {
//状态
	const CONST_1= 1; //Sample
	/**
	 +---------------------------------------------------------------------------------------------
	 + Below are Const definition for this module
	 +---------------------------------------------------------------------------------------------
	 **/
	protected static $STOCK_CHANGE_TYPE = array(
			"1" => "入库",
			"2" => "出库",
			"3" => "盘点",
			"4" => "删除",
	        "5" => "更新",
			"6" => "调拨",
	);
	
	protected static $STOCK_CHANGE_REASON = array(
			"101" => "采购入库",
			"102" => "样品入库",
			"103" => "回收邮包",
			"104" => "赠品入库",
			"301" => "库存盘盈",
			"201" => "订单出库",
			"202" => "样品出库",
			"203" => "重发邮包",
			"204" => "赠品出库",
			"205" => "报废出库",
			"302" => "库存盘亏",
			"300" => "库存盘点",
			"400" => "删除库存",
	        "500" => "更新库存",
			"600" => "仓库调拨",
	);
	
	private static $EXCEL_COLUMN_MAPPING = [
	"A" => "sku",
	"B" => "stockchange_qty",
	"C" => "location_grid",
	];
	
	private static $SELLERTOOL_EXCEL_COLUMN_MAPPING = [
	"A" => "location_grid",
	"C" => "sku",
	"E" => "stockchange_qty",
	
	];
	
	public static $EXPORT_EXCEL_FIELD_LABEL = [
	"sku" => "产品SKU",
	"stockchange_qty" => "出库/入库数量/实际盘点数",
	"location_grid" => "货架位置",
	];
	
	protected static $PRODUCT_STATUS = array(
			"OS" => "在售",
			"RN" => "紧缺",
			"DR" => "下架",
			"AC" => "归档",
			"RS" => "重新上架",
	);
	
	public static function getNewAutoIncrementStockChangeId($prefix){
		$sequenceId=$prefix;
		$query = StockChange::find()->select("stock_change_id")->where('stock_change_id  REGEXP \'^'.$prefix.'[0-9a-zA-Z]+$\' ');
		$query->orderBy("create_time DESC");
		$last_auto_order = $query->one();
		if($last_auto_order==null){
			$sequenceId=$sequenceId."000001";
		}else{
			$last_auto_order = $last_auto_order->stock_change_id;
			$orderNum = substr($last_auto_order, strlen($prefix));
			if(empty($orderNum)) $orderNum=0;
			$orderNum = intval($orderNum);
			$orderNum_new = $orderNum+1;
				
			if ($orderNum_new<10) 	$sequenceId=$sequenceId."00000".$orderNum_new;
			else if ($orderNum_new<100) 	$sequenceId=$sequenceId."0000".$orderNum_new;
			else if ($orderNum_new<1000) 	$sequenceId=$sequenceId."000".$orderNum_new;
			else if ($orderNum_new<10000) 	$sequenceId=$sequenceId."00".$orderNum_new;
			else if ($orderNum_new<100000) 	$sequenceId=$sequenceId."0".$$orderNum_new;
			else $sequenceId=$sequenceId.$orderNum_new;
		}
		return $sequenceId;
	}
	/**
	 +----------------------------------------------------------
	 * 获取库存操作类型
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param id			操作类型Key
	 +----------------------------------------------------------
	 * @return				类型列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lzhl	2015/03/16				初始化
	 +----------------------------------------------------------
	 **/
	public static function getStockChangeType($id='')
	{
		if (is_numeric($id) or $id=="")
			return GetControlData::getValueFromArray(self::$STOCK_CHANGE_TYPE, $id);
		else
			return GetControlData::getValueFromArray(array_flip(self::$STOCK_CHANGE_TYPE), $id);
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取库存操作原因
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param id			操作原因Key
	 +----------------------------------------------------------
	 * @return				列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lzhl	2015/03/16				初始化
	 +----------------------------------------------------------
	 **/
	public static function getStockChangeReason($id='')
	{
		if (is_numeric($id) or $id=="")
			return GetControlData::getValueFromArray(self::$STOCK_CHANGE_REASON, $id);
		else
			return GetControlData::getValueFromArray(array_flip(self::$STOCK_CHANGE_REASON), $id);
	}
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 Other Stock In Reason列表数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id				stock change id
	 +---------------------------------------------------------------------------------------------
	 * @return					array of other stock in information
	 *							if @parm id = '', return all StockChangeReason except the "采购入库"
	 *							Other wise, return the particular StockChangeReason info only
	 *							(@parm id can be either the key id or the label)
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/03/20			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOtherStockInReason($id='')
	{	if (is_numeric($id) or $id==""){
		$rtn1 = GetControlData::getValueFromArray(self::$STOCK_CHANGE_REASON, $id);
		//filter those for 出库  and 库存盘盈
		foreach ($rtn1 as $key => $aValue){
			if ($key >= 0 and $key < 199)
				$rtn[$key] = $aValue;
		}
	}else
		$rtn = GetControlData::getValueFromArray(array_flip(self::$STOCK_CHANGE_REASON), $id);
	
	return $rtn;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取Stock In Reason ComoBox 列表数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id			stock change id
	 +---------------------------------------------------------------------------------------------
	 * @return				To facility combo listing purpose in front end.
	 * 						array of Other stock In reason information
	 *                      if @parm id = '', return all StockChangeReason except the "采购入库"
	 *                      Other wise, return the particular StockChangeReason info only
	 *                      (@parm id can be either the key id or the label)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function otherStockInReasonComoBox(){
		$otherStockInReason = self::getOtherStockInReason();
		foreach ($otherStockInReason as $id => $name){
			$otherStockInReasonComoBox[] = array('id'=>$id,'name'=>$name);
		}
		return $otherStockInReasonComoBox;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取Stock Out Reason 列表数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id			stock change id
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOtherStockOutReason($id='')
	{	if (is_numeric($id) or $id==""){
		$rtn1 = GetControlData::getValueFromArray(self::$STOCK_CHANGE_REASON, $id);
		//unset those for 出库  and 库存盘盈
		foreach ($rtn1 as $key => $aValue){
			if ($key >= 200 and $key < 299)
				$rtn[$key] = $aValue;
		}
	}else
		$rtn = GetControlData::getValueFromArray(array_flip(self::$STOCK_CHANGE_REASON), $id);
	
	return $rtn;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取Stock Out Reason ComoBox 列表数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id			stock change id
	 +---------------------------------------------------------------------------------------------
	 * @return				To facility combo listing purpose in front end.
	 * 						array of Other stock Out reason information
	 *                      if @parm id = '', return all StockChangeReason except the "采购出库"
	 *                      Other wise, return the particular StockChangeReason info only
	 *                      (@parm id can be either the key id or the label)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function otherStockOutReasonComoBox(){
		$otherStockOutReason = self::getOtherStockOutReason();
		foreach ($otherStockOutReason as $id => $name){
			$otherStockOutReasonComoBox[] = array('id'=>$id,'name'=>$name);
		}
		return $otherStockOutReasonComoBox;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取Inventory的Listing
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $keyword			模糊查询的text
	 * @param     $params           需要指定刷选的fields以及值，field name要和字段名一样，值可以多个可能的，逗号隔开
	 *                              例如 array( warehouse_id=>'1',
	 *                                         status=>'1',
	 *                                         product_type=>'S',
	 *                                         product_tag =>... 
	 *                                       ) 				
	 * @param     $sort             指定排序field
	 * @param     $order            排序顺序
	 * @param     $pageSize         每页显示数量，默认是40
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					InventoryHelper::getListDataByCondition();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		lzhl		2015/3/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listProductStockageData($keyword='',$params=array(), $sort='' , $order='' ,$page=1, $pageSize = 20 )
	{
		//更新特定客户待发货数量
		self::UpdateUserOrdered();
		
		$connection = Yii::$app->get('subdb');
		$sql = "SELECT s.*, name, type,status ,prod_name_ch,brand_id,is_has_tag,photo_primary,class_id,qty_order_reserved,s.qty_in_stock*s.average_price as stock_total
				from wh_product_stock s , pd_product p where s.sku=p.sku and p.type <>'B' ";

		if(empty($sort)){
			$sort = 'qty_in_stock';
			$order = 'desc';
		}
		if($sort=='type')
			$sort='p.type';
	
		$condition='';
		//如果keyword不为空，用户录入了模糊查询
		if(!empty($keyword)){
			//去掉keyword的引号。免除SQL注入
			$keyword = str_replace("'","",$keyword);
			$keyword = str_replace('"',"",$keyword);
			$condition .= " and (s.sku like '%$keyword%' or name like '%$keyword%' ";
			
			if(!empty($params['search_sku'])){
				$condition .= " or s.sku like '%".$params['search_sku']."%' ";
				
				unset($params['search_sku']);
			}
			
			$condition .= " )";
		}
		
		//读取是否显示海外仓仓库
		$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
		if(empty($is_show))
			$is_show = 0;
		//不显示海外仓仓库
		if($is_show == 0)
		{
		    $condition .= " and (s.warehouse_id in (select warehouse_id from wh_warehouse where is_oversea=0) )";
		}
		
		//只显示启用的仓库库存信息
		$condition .= " and (s.warehouse_id in (select warehouse_id from wh_warehouse where is_active!='N' and is_active != 'D' and name!='无') )";
		
		//筛选库存量小于安全库存
		if(!empty($params['stock_status'])){
			switch ($params['stock_status']){
				case 1:
					$condition .= " and s.qty_in_stock < s.safety_stock";
					break;
				case 2:
					$condition .= " and s.qty_in_stock = 0";
					break;
				case 3:
					$condition .= " and s.qty_in_stock > 0";
					break;
				case 4:
					$condition .= " and s.qty_in_stock < 0";
					break;
				case 5:
					$condition .= " and s.qty_in_stock != 0";
					break;
				default:
					break;
			}
			
			unset($params['stock_status']);
		}
		
		//其他查询参数
		foreach ($params as $fieldName=>$val){
			if($val!==''){
				//去掉keyword的引号。免除SQL注入
				$val = str_replace("'","",$val);
				$val = str_replace('"',"",$val);
				$val_array = explode(",",$val);
				$condi_internal =" and ( 0 ";

				foreach ($val_array as $aVal){
					$condi_internal .= " or $fieldName='$aVal'";
				}
				
				$condi_internal .= ")";
				
				$condition .= $condi_internal;
			}
		}//end of each filter
		$data ['condition'] = $condition;
		
		//Pagination 会自动获取Post或者get里面的page number，自动计算offset
		$command = $connection->createCommand($sql.$condition);
		$pagination = new Pagination([
				'pageSize' => $pageSize,
				'totalCount' => count($command->queryAll()),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$data['pagination'] = $pagination;
		
		$sortStr = " order by $sort $order ";

		$offset = " limit ". $pagination->offset." , ". $pagination->limit;

		$command = $connection->createCommand($sql.$condition.$sortStr.$offset);
		$rows =  $command->queryAll();
// print_r($sql.$condition.$sortStr.$offset);die;
		if(count($rows)<1){
			$data['data']=array();
		}
		
		//查询所有分类
		$class_arr = array();
		$class_list = ProductClassification::find()->asArray()->all();
		foreach($class_list as $class){
			$class_arr[$class['ID']] = $class['name'];
		}
		
		foreach ($rows as &$row) {
			/*
			if($row['type']=="B"){
				$bundleStock = self::getBundleProductInventory($row['sku'], $row['warehouse_id']);
				$row['qty_in_stock'] = $bundleStock->qty_in_stock;
				$row['$bundleStock'] = $bundleStock->qty_purchased_coming;
			}
			*/
			$row['stock_total'] = (empty($row['qty_in_stock']) ? 0 : $row['qty_in_stock']) * (empty($row['average_price']) ? 0 : $row['average_price']);

			//分类
			$row['class_name'] = empty($class_arr[$row['class_id']]) ? '未分类' : $class_arr[$row['class_id']];
			
			$data['data'][]=$row;
		}

		return $data;
	}


	/**
	 +---------------------------------------------------------------------------------------------
	 * To list Bundle Product Stockage Data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param page			Page Number to be shown
	 * @param rows			number of rows per page
	 * @param sort          sort by which field
	 * @param order         order by which field
	 * @param queryString   array of criterias
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 +---------------------------------------------------------------------------------------------
	 * log			name			date			note
	 * @author		lzhl			2014/11/17		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listBundleProductStockageData($page, $rows, $sort, $order, $queryString){
		$result = array();
		$result['data'] = array();
		$sql = '';
		
		$query = Product::find()
							->select(['sku','name','type','status','prod_name_ch','brand_id','is_has_tag','photo_primary','class_id'])
							->where(['type'=>'B']);
		if(!empty($queryString['search_keyword']) || trim($queryString['search_keyword'])!==''){
			$keyword = trim($queryString['search_keyword']);
			//$query->andWhere(['or',["sku like '$keyword'"] , ["name like '$keyword'"] ]   );
			$query->andWhere(['or',['like','sku',$keyword] , ['like','name',$keyword] ]   );
		}
		if ( !empty($queryString['product_status']) && $queryString['product_status']!==''){
			$status = $queryString['product_status'];
			$query->andWhere(['status'=>$status]);
		}
		if ( isset($queryString['class_id']) && $queryString['class_id']!==''){
			$query->andWhere(['class_id'=>$queryString['class_id']]);
		}
		
		$pagination = new Pagination([
				'pageSize' => $rows,
				'totalCount' =>$query->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;
		
		if(in_array($sort,array('sku','name','type','status','class_id'))){
			$query->orderBy("$sort $order");
			unset($sort);	
		}
		$BundleProdSkus = $query->asArray()->all();

		if (count($BundleProdSkus)>0){
			$list = array();
			$warehouse_id = (isset($queryString['warehouse_id']) && is_numeric($queryString['warehouse_id']))? $queryString['warehouse_id'] : '';
			
			//读取是否显示海外仓仓库
			$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
			if(empty($is_show))
				$is_show = 0;
			
			//查询所有分类
			$class_arr = array();
			$class_list = ProductClassification::find()->asArray()->all();
			foreach($class_list as $class){
				$class_arr[$class['ID']] = $class['name'];
			}
			
			foreach( $BundleProdSkus as $aBundleProd){
				$B_sku = $aBundleProd['sku'];
				$Asskus = ProductBundleRelationship::find()->where(['bdsku'=>$B_sku])->asArray()->all();

				$asskuArr = array();
				$asQty = array();
				if (count($Asskus)>0){
					foreach( $Asskus as $aAs){
						$asskuArr[] = $aAs['assku'];
						if($aAs['qty'] > 0){
						    $asQty[$aAs['assku']] = $aAs['qty'];
						}
	
					}
					$selectAsStock = "SELECT t.qty_in_stock as stock, t.qty_purchased_coming as stock_coming, t.sku, t1.prod_name_ch ,t.average_price,t.warehouse_id,t.location_grid
						FROM wh_product_stock t,pd_product t1
						WHERE t1.sku=t.sku ";
					if (count($asskuArr)==1){
						$asskuList = $asskuArr[0];
						$selectAsStock .= "and t.sku = '$asskuList' ";
					}
					if (count($asskuArr)>1){
						$asskuList = implode(',',$asskuArr);
						$asskuList ="'".$asskuList."'";
						$asskuList = str_replace(",","','",$asskuList);
						$selectAsStock .= "and t.sku in ($asskuList) ";
					}
					if (is_numeric($warehouse_id))
						$selectAsStock .= "and t.warehouse_id = $warehouse_id ";
					
					//只显示启用的仓库库存信息
					$selectAsStock .= " and (t.warehouse_id in (select warehouse_id from wh_warehouse where is_active!='N' and is_active != 'D' and name!='无') )";

					$command = Yii::$app->get('subdb')->createCommand($selectAsStock);
					$StockDetail = $command->queryAll();
						
					// SysLogHelper::SysLog_Create("InventoryHelper",__CLASS__, __FUNCTION__,"",'StockDetail'.print_r($StockDetail,true), "trace");
					if (is_numeric($warehouse_id)){
						$warehouseArr[]=$warehouse_id;
					}else{
					    if($is_show == 0)
					    {
					        $query_WH=Warehouse::find()->select('warehouse_id')->where(['is_oversea' => '0'])->orderBy("warehouse_id ASC")->asArray()->all();
					    }
					    else
					    {
						    $query_WH=Warehouse::find()->select('warehouse_id')->orderBy("warehouse_id ASC")->asArray()->all();
					    }
						foreach ($query_WH as $WH){
							$warehouseArr[]=$WH['warehouse_id'];
						}
					}

					$warehouseStockage = [];
					foreach ($warehouseArr as $i=>$WH_id){
						foreach ($StockDetail as $j=>$aDetail){
							if($WH_id==$aDetail['warehouse_id']){
								$warehouseStockage[$WH_id][] = $aDetail;
							}
						}
					}
					//exit(print_r($warehouseStockage));
					foreach ($warehouseStockage as $wh_id=>$asStockage){
						$Detailrows = count($asStockage);
						if ($Detailrows < count($asskuArr)){
							//捆绑子产品在该仓库没有足够子产品记录以组成捆绑产品
							/*
							$aBundleProd['qty_in_stock'] = 0;
							$aBundleProd['qty_purchased_coming'] = 0;
							$aBundleProd['average_price'] = 'N/A';
							$result['data'][] = $aBundleProd;
							*/
							continue;
						}
						
						$totalInStock = 0;
						$totalComing = 0;
						$totalPrice = 0;
						$mix_location_grid ='';
						for ($i=0;$i<$Detailrows;$i++){
							foreach($asQty as $assku=>$qty){
								if ($asStockage[$i]['sku'] == $assku){
									$totalPrice += $qty*$asStockage[$i]['average_price'];
									// SysLogHelper::SysLog_Create("InventoryHelper",__CLASS__, __FUNCTION__,"",'StockDetail[$i]:'.print_r($StockDetail[$i],true).'qty:'.$qty, "trace");
									if ( $i==0 ){
										$totalInStock = floor($asStockage[$i]['stock'] / $qty);
										$totalComing = floor($asStockage[$i]['stock_coming'] / $qty);
									}else{
									    if($qty == 0)
									    {
									        $totalInStock = 0;
									        $totalComing = 0;
									    }
									    else 
									    {
    										if (floor($asStockage[$i]['stock'] / $qty)<$totalInStock)
    											$totalInStock = floor($asStockage[$i]['stock'] / $qty);
    										if (floor($asStockage[$i]['stock_coming'] / $qty)<$totalComing)
    											$totalComing = floor($asStockage[$i]['stock_coming'] / $qty);
									    }
									}
									if($mix_location_grid=='')
										$mix_location_grid.= htmlentities($assku) .":".(($asStockage[$i]['location_grid']=='' or $asStockage[$i]['location_grid']==null)?"未设置":$asStockage[$i]['location_grid']);
									else
										$mix_location_grid.= ";".htmlentities($assku) .":".(($asStockage[$i]['location_grid']=='' or $asStockage[$i]['location_grid']==null)?"未设置":$asStockage[$i]['location_grid']);
								}
							}
						}
						//捆绑产品基本信息
						$aBundleProd['sku'] = $B_sku;
						$aBundleProd['name'] = $aBundleProd['name'];
						$aBundleProd['type'] = $aBundleProd['type'];
						$aBundleProd['status'] = $aBundleProd['status'];
						$aBundleProd['prod_name_ch'] = $aBundleProd['prod_name_ch'];
						$aBundleProd['brand_id'] = $aBundleProd['brand_id'];
						$aBundleProd['is_has_tag'] = $aBundleProd['is_has_tag'];
						$aBundleProd['photo_primary'] = $aBundleProd['photo_primary'];
						$aBundleProd['location_grid'] = $mix_location_grid;
						$aBundleProd['safety_stock'] = '--';
						$aBundleProd['prod_stock_id'] = '0';
						//捆绑产品库存信息
						$aBundleProd['warehouse_id'] = $wh_id;
						$aBundleProd['qty_in_stock'] = $totalInStock;
						$aBundleProd['qty_purchased_coming'] = $totalComing;
						$aBundleProd['average_price'] = $totalPrice;
						$aBundleProd['stock_total'] = $totalPrice * $totalInStock;
						
						//仓库信息
						$productStock = ProductStock::findOne(['warehouse_id'=>$wh_id, 'sku'=>$B_sku]);
						if(!empty($productStock))
						{
    						$aBundleProd['qty_ordered'] = $productStock->qty_ordered;
    						$aBundleProd['qty_order_reserved'] = $productStock->qty_order_reserved;
						}
						else 
						{
						    $aBundleProd['qty_ordered'] = 0;
						    $aBundleProd['qty_order_reserved'] = 0;
						}
						
						//库存状态判断
						$stock_status = true;
						if(!empty($queryString['stock_status'])){
							switch ($queryString['stock_status']){
								case 2:
									if($totalInStock != 0){
										$stock_status = false;
									}
									break;
								case 3:
									if($totalInStock <= 0){
										$stock_status = false;
									}
									break;
								case 4:
									if($totalInStock >= 0){
										$stock_status = false;
									}
									break;
								case 5:
									if($totalInStock == 0){
										$stock_status = false;
									}
									break;
								default:
									break;
							}
						}
						
						if(!$stock_status)
							continue;
						
						//分类
						$aBundleProd['class_name'] = empty($class_arr[$aBundleProd['class_id']]) ? '未分类' : $class_arr[$aBundleProd['class_id']];
						
						$list[] = $aBundleProd;
					
					}		
				}else{
					//捆绑子产品没有子产品
					/*
					$aBundleProd['qty_in_stock'] = 0;
					$aBundleProd['qty_purchased_coming'] = 0;
					$aBundleProd['average_price'] = 'N/A';
					$result['data'][] = $aBundleProd;
					*/
					continue;
				}
			}
			
			//排序
			if(isset($sort) && isset($order)){
				$sortKeyArr = array();
				foreach ($list as $r) {
					$sortKeyArr[] = $r[$sort];
				}
				
				if($order=='desc')
					array_multisort($sortKeyArr, SORT_DESC, $list);
				else
					array_multisort($sortKeyArr, SORT_ASC, $list);
			}
			
			//分页显示
			if($page == -1){
				foreach ($list as $d){
					$result['data'][] = $d;
				}
			}
			else{
				$page = $page - 1;
				$count = count($list);
				$pagination = new Pagination([
						'page'=> $page,
						'pageSize' => $rows,
						'totalCount' => $count,
						'pageSizeLimit'=>[20,200],//每页显示条数范围
						]);
				$result['pagination'] = $pagination;
				 
				$p_s = $rows * $page ;
				$p_e = $rows * ($page + 1) - 1;
				if($p_e > $count - 1)
					$p_e = $count - 1;
				for($n = $p_s; $n <= $p_e; $n++){
					$result['data'][] = $list[$n];
				}
			}

			return $result;
		}else{
			return $result;
		}
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To list Purcahse arrivals Pendign Stock In Data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param page			Page Number to be shown
	 * @param rows			number of rows per page
	 * @param sort          sort by which field
	 * @param order         order by which field
	 * @param queryString   array of criterias
	 +---------------------------------------------------------------------------------------------
	 * @return				To List all purcahses arrival are pending stock in.
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listPendingStockInData($page, $rows, $sort, $order, $queryString){
		$criteria = new CDbCriteria();
		$criteria->limit = $rows;
		$criteria->offset = ($page-1) * $rows;
	
		$criteria->order = "$sort $order";//排序条件
		//记录总行数
		$sql = "SELECT wh.name as whname, a.*,p.purchase_order_id,p.supplier_id,p.status as purchase_status,p.delivery_method,delivery_number,p.comment as pruchase_comment
				 FROM wh_warehouse wh, pc_purchase_arrivals a,pc_purchase p where p.id = a.purchase_id and p.warehouse_id=wh.warehouse_id ";
	
			
		//using bind param
		$bindParmValues = array();
		if(!empty($queryString)){
				
			foreach($queryString as $k => $v){
				$v = trim($v);
				if ($v=='') continue;
					
				if ($k=='search_keyword'){
					$sql .= " and (a.purchase_arrival_name like '%$v%' or p.purchase_order_id like '%$v%' or p.comment like '%$v%'  or a.comment like '%$v%' )";
					$bindParmValues[$k] = "%".$v."%";
				}
				if ($k=='arrival_status'){
					$sql .= " and  a.status in ($v) ";
					//$bindParmValues[$k] = $v;
				}
				if ($k=='warehouse_id'){
					$sql .= " and  wh.warehouse_id = $v ";
					$bindParmValues[$k] = $v;
				}
			}//end of each criteria
		}//end of got query strings
	
		$sql .= " order by ".$criteria->order."
		LIMIT ".$criteria->limit."
		OFFSET ".$criteria->offset; //LIMIT 10 OFFSET 20 , e.g. page = 3
	
		$command = Yii::app()->subdb->createCommand($sql);
	
		foreach ($bindParmValues as $k=>$v){
			$command->bindValue(":$k",$v,PDO::PARAM_STR);
		}
	
		$result['rows'] = $command->queryAll();
		$result['total'] = SwiftFormat::getSqlResultTotalCount($command->getText() , $bindParmValues );
		return $result;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To list Purcahse arrivals Pendign Stock In Data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id						purchase_arrival_id
	 * @param getInventoryInfo			true if want to get location grid of the prod
	 * 									default false
	 +---------------------------------------------------------------------------------------------
	 * @return				To get the product details for the arrival record of a purchase.
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		lzhl		2015/03/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listPendingStockInDetailData($id,$getInventoryInfo = false){
		$sql = "SELECT d.*,p.photo_primary , p.name,p.category_id, p.brand_id
				FROM `pc_purchase_arrival_detail` d, pd_product p
				WHERE d.sku=p.sku and d.purchase_arrival_id=:purchase_arrival_id
				order by p.sku";
	
		$command = Yii::$app->get('subdb')->createCommand($sql);
		//bind the parameter values
		$command->bindValue(":purchase_arrival_id", $id, \PDO::PARAM_STR);
		
		$result['rows'] = $command->queryAll();
		$result['total'] = count($result['rows']);
	
		//get inventory info if needed, e.g. location_grid
		if ($getInventoryInfo and $result['total'] > 0){
			$purchase_id = PurchaseArrivals::find()->where(['PurchaseArrivals'=>$result['rows'][0]['purchase_arrival_id']])->One()->purchase_id;
			$warehouse_id = Purchase::find()->where(['purchase_id'=>$purchase_id])->One()->warehouse_id;
				
			for($i = 0; $i < $result['total']; $i++){
				$aProductStockInfo = ProductStock::find()->where([
						'warehouse_id'=>$warehouse_id,
						'sku'=>$result['rows'][$i]['sku']
						])->One();

				if ($aProductStockInfo <> null){
					$result['rows'][$i]['location_grid'] = $aProductStockInfo->location_grid;
					//对没有定义目录、品牌的产品做特殊处理
					$brand_id = $result['rows'][$i]['brand_id'];
					if (!$brand_id) $result['rows'][$i]['brand_name'] ='';
					else{
						$sqlBrand="SELECT name FROM pd_brand WHERE brand_id = $brand_id";
						$command = Yii::$app->get('subdb')->createCommand($sqlBrand);
						$resultBrand = $command->queryScalar();
						$result['rows'][$i]['brand_name'] = $resultBrand;
					}
					$category_id = $result['rows'][$i]['category_id'];
					if (!$category_id) $result['rows'][$i]['categroy_name'] ='';
					else{
						$sqlCategory="SELECT name FROM pd_category WHERE category_id = $category_id";
						$command = $command = Yii::$app->get('subdb')->createCommand($sqlCategory);
						$resultCategory = $command->queryScalar();
						$result['rows'][$i]['category_name'] =$resultCategory;
					}
				}
				else
					$result['rows'][$i]['location_grid'] = "";
			}//end of each sku
		}//end of loading inventory info
	
		return $result['rows'];
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取指定sku & warehouse_id 对应的库存操作历史
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $sku				sku
	 * @param     warehouse_id      warehouse_id
	 * @param     $sdate            开始时间
	 * @param     $edate            结束时间
	 * @param     $reason           库存操作类型
	 +---------------------------------------------------------------------------------------------
	 * @return						$history
	 *
	 * @invoking					InventoryHelper::getInventoryHistory();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		lzhl		2015/3/13				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getInventoryHistory($page=1, $pageSize=20, $sort='create_time', $order='desc', $sku, $warehouse_id, $reason, $sdate, $edate)
	{
		$data = array();
		
		$connection = Yii::$app->get('subdb');

		//记录总行数
		$sql = "SELECT c.change_type,c.reason,c.comment, c.capture_user_id, c.create_time,
				c.update_time, d.* from wh_stock_change_detail d, wh_stock_change c where
				sku='$sku' and c.stock_change_id = d.stock_change_id and warehouse_id='$warehouse_id'";
		if(!empty($sdate)){
			$sql .= " and c.create_time >= '$sdate 00:00:00' ";
		}
		if(!empty($edate)){
			$sql .= " and c.create_time <= '$edate 23:59:59' ";
		}
		
		$command = $connection->createCommand($sql);
		$totalRows = $command->queryAll();
		$totalRowCount = count($totalRows);
		$pagination = new Pagination([
				'pageSize' => $pageSize,
				'totalCount' => $totalRowCount,
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$data['pagination'] = $pagination;
		if(empty($sort))
			$sort = 'create_time';
		if(empty($order))
			$order = 'desc';
		
		$sql .= " order by $sort $order  limit ". $pagination->offset." , ". $pagination->limit;

		$command = $connection->createCommand($sql);
		//echo "test sql:<br>";
		//echo $sql;
		//echo '<br><br>';		
		$rows =  $command->queryAll();
		
		if(count($rows)<1){
			$data['data']=array();
			return $data;
		}
		foreach ($rows as $row) {
			$data['data'][]=$row;
		}
		
		//start to check the final inventory number and calculate the each phase before
		$aProductStockInfo = ProductStock::find()
			->andWhere([
				'warehouse_id'=>$warehouse_id,
				'sku'=>$sku,
				])
			->asArray()
			->one();
		if ($aProductStockInfo == null)
			$latest_stockage = 0;
		else
			$latest_stockage = $aProductStockInfo['qty_in_stock'];
		
		$ahead_stock_in_qty = 0;
		$ahead_stock_out_qty = 0;
		//when there is offset, try to get the skipped ahead records summary stockage delter
		if ($pagination->offset > 0 and $totalRowCount > 0){
			$last_index_key = $totalRows[0]['update_time'];
			$sql = "select sum(qty) from wh_stock_change_detail d, wh_stock_change c where
				sku='$sku' and c.stock_change_id = d.stock_change_id
				and update_time >$last_index_key and warehouse_id='$warehouse_id'
				and (change_type=1 or change_type=3) ";
		
			$command = Yii::app()->subdb->createCommand($sql);
			$ahead_stock_in_qty = $command->queryScalar();
		
			$sql = "select sum(qty) from wh_stock_change_detail d, wh_stock_change c where
				sku='$sku' and c.stock_change_id = d.stock_change_id
				and update_time >$last_index_key and warehouse_id='$warehouse_id'
				and (change_type=2) ";
		
			$command = Yii::app()->subdb->createCommand($sql);
			$ahead_stock_out_qty = $command->queryScalar();
		
		}
		$data['data'][0]['snapshot_qty'] = $latest_stockage - ($ahead_stock_in_qty - $ahead_stock_out_qty);

		//echo "test data final:<br>";
		//print_r($data);
		//echo '<br><br>';
		return $data;

		
		

	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To list all Stock Change Data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param page			Page Number to be shown
	 * @param rows			number of rows per page
	 * @param sort          sort by which field
	 * @param order         order by which field
	 * @param queryString   array of criterias
	 +---------------------------------------------------------------------------------------------
	 * @return				To List all stock change data.
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listStockChangeData($page, $rows, $sort, $order, $queryString){
		$criteria = StockChange::find();
		
		if(!empty($queryString)) {
			foreach($queryString as $k => $v) {
				if ($k == 'keyword'){
					$criteria->andWhere(['like','$criteria',$v]);
					$criteria->andWhere(['like','$comment',$v]);
				}
				elseif($k=='date_from') {
					$criteria->andWhere("create_time >= '$v 00:00:00'");
				}elseif($k=='date_to') {
					$criteria->andWhere("create_time <= '$v 23:59:59'");
				}else
					$criteria->andWhere([$k,$v]);
			}
		}
		//Stock Take records generated by auto, do not show
		$criteria->andWhere("change_type <> '3'");
	
		$data = $criteria
			->limit($rows)
			->offset( ($page-1) * $rows )
			->orderBy( "$sort $order")
			->asArray()
			->all();
		//记录总行数
		$result['total'] = count($data);
		$result['rows'] = GetControlData::formatModelsWithUserName($data,"capture_user_id");
		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取指定出入库记录列表
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param		$stockChangeType		sotckchange类型
	 * @param		$warehouse_id			warehouse_id
	 * @param		$sdate					开始时间
	 * @param		$edate					结束时间
	 * @param		$keyword				库存操作类型
	 * @param		$pageSize				每页显示记录shu
	 * @param		$page					当前页数
	 * @param		$sort					排序条件
	 * @param		$order					顺序
	 +---------------------------------------------------------------------------------------------
	 * @return		array()
	 *
	 * @invoking	InventoryHelper::getStockChangeDataList();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		lzhl		2015/3/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getStockChangeDataList($stockChangeType='',$warehouse_id='',$keyword='',$sdate='',$edate='',$page=1,$pageSize=20,$sort='create_time',$order='desc')
	{
		$result = array();
		$StockChange = StockChange::find();
		
		$condition = '1';
		if(!empty($stockChangeType))
			$condition .= " and change_type = '$stockChangeType' ";
		if(!empty($warehouse_id))
			$condition .= " and warehouse_id = '$warehouse_id' ";
		if(!empty($keyword))
			$condition .= " and (stock_change_id like '%$keyword%' or comment like '%$keyword%' )";
		if(!empty($sdate))
			$condition .= " and create_time >= '$sdate 00:00:00' ";
		if(!empty($edate))
			$condition .= " and create_time <= '$edate 23:59:59' ";
		
		//读取是否显示海外仓仓库
		$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
		if(empty($is_show))
			$is_show = 0;
		//不显示海外仓仓库
		if($is_show == 0)
		{
			$condition .= " and (warehouse_id in (select warehouse_id from wh_warehouse where is_oversea=0) )";
		}
		
		//只显示启用的仓库库存信息
		$condition .= " and (warehouse_id in (select warehouse_id from wh_warehouse where is_active!='N' and is_active != 'D' and name!='无') )";
		
		$StockChange->andWhere($condition);
			
		$pagination = new Pagination([
				'pageSize' => $pageSize,
				'totalCount' => $StockChange->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		
		$result ['condition'] = $condition;
		$result ['pagination'] = $pagination;
		
		
		$result['data'] = $StockChange
		->offset($pagination->offset)
		->limit($pagination->limit)
		->orderBy("$sort $order")
		->asArray()
		->all();
		
		//整理信息
		$user_name_arr = array();
		foreach($result['data'] as &$one){
			$one['user_name'] = $one['capture_user_id'];
			if(!empty($one['capture_user_id'])){
				if(array_key_exists($one['capture_user_id'], $user_name_arr)){
					$one['user_name'] = $user_name_arr[$one['capture_user_id']];
				}
				else{
					$userInfo = UserInfo::findOne(['uid' => $one['capture_user_id']]);
					if(!empty($userInfo) && !empty($userInfo['familyname'])){
						$one['user_name'] = $userInfo['familyname'];
						$user_name_arr[$one['capture_user_id']] = $userInfo['familyname'];
					}
					else{
						$user = UserBase::findOne(['uid' => $one['capture_user_id']]);
						if(!empty($user) && !empty($user['user_name'])){
							$one['user_name'] = $user['user_name'];
							$user_name_arr[$one['capture_user_id']] = $user['user_name'];
						}
					}
				}
			}
		}
		
		return $result;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To load and check whether the arrival record is of a particular status
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id			purchase arrival id
	 * @param status        target status
	 +---------------------------------------------------------------------------------------------
	 * @return				true : when record is in the pass in status
	 *                      false: when record is in another status
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function isPurchaseArrivalInStatus($id,$status){
		$rtn = true;
		$model = PurchaseArrivals::find()
		->where(['purchase_arrival_id'=>$id,'status'=>$status])
		->asArray()
		->One;
	
		if ($model == null){
			$rtn =false;
		}
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To get Stock Change Detai lData
	 +---------------------------------------------------------------------------------------------
	 * @access		static
	 +---------------------------------------------------------------------------------------------
	 * @param		id			stock change record id
	 +---------------------------------------------------------------------------------------------
	 * @return		array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/03/17		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getStockChangeDetailData($id){
		$sql = "SELECT a.*, p.photo_primary
			FROM wh_stock_change_detail a
				left join
				pd_product p
				on p.sku=a.sku
				where stock_change_id=:id";
	
		$command = Yii::$app->get('subdb')->createCommand($sql);
	
		//bind the parameter values
		$command->bindValue(":id", $id, \PDO::PARAM_STR);
	
		return $command->queryAll();
		/**
		 */
		
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To insert Purchase Stock In Record after use captured in Front End
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data						data posted with form
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * This is to do following operations:
	 * 1. insert a stock In record into wh_stock_change
	 * 2. insert this stock in sku,qty details into wh_stock_change_detail
	 * 3. update the stocked in qty,stock in ID in pc_purchase_arrival_detail
	 * 4. update status to "已经入库" for pc_purchase_arrivals
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function insertPurchaseStockIn($data){
		$rtn['message'] = "";
		$rtn['success'] = true;
		$stock_change_id = '';
		//step 0: perform validation to ensure this Arrival is not completed
		if (!isset($data['status']))
			$data['status'] = '-1';
		/*
		if ( ! self::isPurchaseArrivalInStatus($data['purchase_arrival_id'],$data['status']) ){
			$rtn['success']=false;
			$rtn['message'] .= "E_INV_00B 该到货记录状态已经被修改过。请刷新确认该到货记录: ".$data['purchase_arrival_id'];
		}
		*/
		//step 0.5, verify whether there is no qty input, when no qty input, alert
		$gotQty = false;
		for ($i = 0; $i < count($data['sku']); $i++){
			if ($data['stock_in_qty'][$i] <> "" and $data['stock_in_qty'][$i] <> 0)
				$gotQty = true;
		}//end of each sku to stock In
	
		if ($gotQty == false){
			$rtn['success']=false;
			$rtn['message'] .= "E_INV_00C 请录入需要入库的数量，请勿全部填写0";
		}
	
	
		//Step:1. insert a stock In record into wh_stock_change
		//use the purchase id + arrival id + AI to format the stock change id
		//check if this stock change id is available
		//SysLogHelper::SysLog_Create("inventory",__CLASS__, __FUNCTION__,"Step 1","insert a stock In record into wh_stock_change	", "Trace");
		if ($rtn['success']){
			$i = 1;
			$stock_change_id = join("_",array($data['purchase_order_id'], $data['purchase_arrival_id'], $i  ));
			while (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
				$i++;
				$stock_change_id = join("_",array($data['purchase_order_id'], $data['purchase_arrival_id'], $i  ));
			}
	
			$data['stock_change_id'] = $stock_change_id;
			$data['create_time'] = TimeUtil::getNow();
			$data['update_time'] = TimeUtil::getNow();
			$data['capture_user_id'] = \Yii::$app->user->id;
			$data['comment'] = "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ "."</font>".TranslateHelper::t('通过采购模块快速入库。');
			$rtn = self::insertStockInRecord($data,self::getStockChangeReason("采购入库"));
		}
	
		//Step:2 insert this stock in sku,qty details into wh_stock_change_detail
		//SysLogHelper::SysLog_Create("inventory",__CLASS__, __FUNCTION__,"Step 2","insert this stock in sku,qty details into wh_stock_change_detail", "Trace");
		if ($rtn['success']){
			//only when step 1 successes, proceed with step 2
			for ($i = 0; $i < count($data['sku']) and $rtn['success'] ; $i++){
				$rtn = self::insertStockChangeDetailRecord($data,$data['sku'][$i],$data['stock_in_qty'][$i]);
			}//end of each sku to stock In
	
		}//end of step 3 successes
	/*purchase_arrival model 未完善，此步跳过
		//Step:3 update the stocked in qty,stock in ID in pc_purchase_arrival_detail
		//SysLogHelper::SysLog_Create("inventory",__CLASS__, __FUNCTION__,"Step 3","update the stocked in qty,stock in ID in pc_purchase_arrival_detail", "Trace");
		$purchase_arrival_new_status = "";
		if ($rtn['success']){
			//Load the related purchase arrival record and try to update the qty
			for ($i = 0; $i < count($data['sku']); $i++){
				$model = PurchaseArrivalDetail::findAll([
						'purchase_arrival_id'=>$data['purchase_arrival_id'],
						'sku'=>$data['sku'][$i]
						]);
					
				if (!isset($model->stock_in_qty)) $model->stock_in_qty = 0;
					
				$model->stock_in_qty += $data['stock_in_qty'][$i];
	
				if (isset($model->stock_in_id) and trim($model->stock_in_id) <> "")
					$model->stock_in_id .= ",";
	
				$model->stock_in_id .= $data['stock_change_id'];
	
				if ( $model->save() ){//save successfull
					$rtn['success']=true;
				}else{
					$rtn['success']=false;
					foreach ($model->errors as $k => $anError){
						$rtn['message'] .= "E_Inventory_001 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
					}
				}//end of save failed
			}//end of each sku to stock In
	
	
		}//end of step 3
	*/
	/*purchase_arrival model 未完善，此步跳过
		//Step:4 update status to "已入库" for pc_purchase_arrivals
		//SysLogHelper::SysLog_Create("inventory",__CLASS__, __FUNCTION__,"Step 4","update status to '已入库' for pc_purchase_arrivals", "Trace");
		if ($rtn['success']){
	
			//Load all arrival details of this arrival record, if all are stock in / partial, upate status
			$model = PurchaseArrivalDetail::findAll(['purchase_arrival_id'=>$data['purchase_arrival_id']]);
	
			//if there is no record having qty pass > qty stock in, it is all stock in
			if ($model == null)
				$purchase_arrival_new_status = PurchaseArrivalHelper::ALL_STOCK_IN;
			else
				$purchase_arrival_new_status = PurchaseArrivalHelper::PARTIAL_STOCK_IN;
	
			//Load the related purchase arrival record and try to update the qty
			$PurchaseArrival_model = PurchaseArrivals::find()->where(['purchase_arrival_id'=>$data['purchase_arrival_id']] )->One() ;
			$PurchaseArrival_model->status = $purchase_arrival_new_status ;
			$PurchaseArrival_model->addi_info = "{}";
			if ( $PurchaseArrival_model->save() ){//save successfull
				$rtn['success']=true;
				//ENUM('purchase','stock_change','product','finance','warehouse','supplier')
				OperationLogHelper::saveOperationLog('purchase',$data['purchase_order_id'],"采购入库","到货记录".$data['purchase_arrival_id']."进行入库,状态变更为：".$purchase_arrival_new_status);
			}else{
				$rtn['success']=false;
				foreach ($model->errors as $k => $anError){
					$rtn['message'] .= "E_Inventory_002 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}//end of save failed
		}//end of step 4
	*/
		//Step 5: update 仓库信息，产品的 在库数量，在途数量 变更
		//SysLogHelper::SysLog_Create("inventory",__CLASS__, __FUNCTION__,"Step 5","update 仓库信息，产品的 在库数量，在途数量 变更", "Trace");
		if ($rtn['success']){
			//get the purcahse price for this sku in this purchase order
			$PurchaseItems = PurchaseItems::findAll(['purchase_id'=>$data['purchase_id']]);
	
			if ($PurchaseItems == null or !isset($PurchaseItems) or count($PurchaseItems) == 0)
				SysLogHelper::SysLog_Create("inventory",__CLASS__, __FUNCTION__,"Error 5.1","Failed to get purchse items for purchase:".$PurchaseItems->purchase_id, "Error");
	
			foreach ($PurchaseItems as $PurchaseItem){
				$purchase_price[$PurchaseItem->sku] = $PurchaseItem->price;
			}
	
			for ($i = 0; $i < count($data['sku']) and $rtn['success']; $i++){
				/*
				 *  * Input:
				* 	1, sku
				*  2, warehouse id
				*  3, delter quantity of product on the way. if not to change, ZERO is ok, if to do minus, -2,
				*  4, delter quantity of product into stockage. if not to change, ZERO is ok, if to do minus, -2,
				*  5, delter quantity of product ordered. if not to change, ZERO is ok, if to do minus, -2,
				*  6, delter quantity of product reserved for shipment. if not to change, ZERO is ok, if to do minus, -2,
				*  7, This time Purchase Price, CNY: e.g. ￥8.50, if leave blank or 0, it will not be calculated as purchase normal average price
				*  8, new Location Grid, if null, not to change.
				*  *  */
	
				$sku = $data['sku'][$i];
				//SysLogHelper::SysLog_Create("purchase",__CLASS__, __FUNCTION__,"","update the $sku avr price ".(isset($purchase_price[$sku])?$purchase_price[$sku]:0 )." qty ".$data['stock_in_qty'][$i], "trace");
				$rtn = self::modifyProductStockage($sku, $data['warehouse_id'],0 - $data['stock_in_qty'][$i] , $data['stock_in_qty'][$i], 0, 0,
						(isset($purchase_price[$sku])?$purchase_price[$sku]:0 )
						,$data['location_grid'][$i] 	);
			}//end of each sku captured
		}//end of step 5
	
		$rtn['stock_change_id'] = $stock_change_id;
		return $rtn;
	}//end of function insert puchase stock in

	/**
	 +---------------------------------------------------------------------------------------------
	 * To list one product stock change history data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 		page			Page Number to be shown
	 * @param 		rows			number of rows per page
	 * @param 		sort          sort by which field
	 * @param 		order         order by which field
	 * @param 		sku           sku of target prod
	 * @param 		warehouse_id  ware house id for the change history
	 +---------------------------------------------------------------------------------------------
	 * @return				To Load all stock change history record for this prod in this warehouse
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/03/17		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listOneProductStockChangeHistoryData($page, $rows, $sort, $order, $sku, $warehouse_id, $params=[])
	{
		$sql = "SELECT c.change_type,c.reason,c.comment, c.capture_user_id, c.create_time,
				c.update_time, d.* from wh_stock_change_detail d, wh_stock_change c where
				sku=:sku and c.stock_change_id = d.stock_change_id and warehouse_id=:warehouse_id";
		if(count($params) >0){
			foreach ($params as $k=>$v){
				if($k=='sdate')
					$sql.= " and create_time >= '$v 00:00:00' ";
				elseif($k=='edate')
					$sql.= " and create_time <= '$v 23:59:59' ";
				else $sql.=" and $k = '$v' ";
			}
		}

		$command = Yii::$app->get('subdb')->createCommand($sql);
		//bind the parameter values
		$bindParmValues = array();
		$bindParmValues["sku"] = $sku;
		$bindParmValues["warehouse_id"] = $warehouse_id;
	
		foreach ($bindParmValues as $k=>$v){
			$bindTarget = trim(":".$k);
			$command->bindValue($bindTarget, ($v), \PDO::PARAM_STR);
		}
		$total = count($command->queryAll());
		
		//Pagination 会自动获取Post或者get里面的page number，自动计算offset //$page,$rows参数可以不set了
		$pagination = new Pagination([
				'defaultPageSize' => 20,
				'totalCount' => $total,
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;
		
		$sql.= " order By ".$sort." ".$order.", id desc";
		$sql.= " limit ".$pagination->limit;
		$sql.= " offset ".$pagination->offset ;
		$command = Yii::$app->get('subdb')->createCommand($sql);
		$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
		$command->bindValue(":warehouse_id",$warehouse_id,\PDO::PARAM_STR);
		$result['data'] = $command->queryAll();
		
		
		//start to check the final inventory number and calculate the each phase before
		$aProductStockInfo = ProductStock::find()->where(['warehouse_id'=>$warehouse_id,'sku' =>$sku])->One();
		if ($aProductStockInfo == null)
			$latest_stockage = 0;
		else
			$latest_stockage = $aProductStockInfo->qty_in_stock;
	
		
		//when there is offset, try to get the skipped ahead records summary stockage delter
		if (count($result['data']) > 0){
			for($i=0;$i<count($result['data']);$i++){
				//$ahead_stock_in_qty = 0;
				//$ahead_stock_out_qty = 0;
				$last_index_key = $result['data'][$i]['id'];
				$sql = "select sum(qty) from wh_stock_change_detail d, wh_stock_change c where
					sku=:sku and c.stock_change_id = d.stock_change_id
					and d.id >:last_index_key and warehouse_id=:warehouse_id
					and (change_type=1 or change_type=3 or change_type=5) ";
					
				$command = Yii::$app->get('subdb')->createCommand($sql);
				$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
				$command->bindValue(":last_index_key",$last_index_key,\PDO::PARAM_STR);
				$command->bindValue(":warehouse_id",$warehouse_id,\PDO::PARAM_STR);
					
				$ahead_stock_in_qty = $command->queryScalar();
					
				$sql = "select sum(qty) from wh_stock_change_detail d, wh_stock_change c where
					sku=:sku and c.stock_change_id = d.stock_change_id
					and d.id >:last_index_key and warehouse_id=:warehouse_id
					and (change_type=2 or change_type=4) ";
					
				$command = Yii::$app->get('subdb')->createCommand($sql);
				$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
				$command->bindValue(":last_index_key",$last_index_key,\PDO::PARAM_STR);
				$command->bindValue(":warehouse_id",$warehouse_id,\PDO::PARAM_STR);
					
				$ahead_stock_out_qty = $command->queryScalar();
				$result['data'][$i]['snapshot_qty'] = $latest_stockage - ($ahead_stock_in_qty - $ahead_stock_out_qty);
				
			}
		}
		return $result;
	}
		
	/**
	 +---------------------------------------------------------------------------------------------
	 * Create product stockin record
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 		info		stockin order info array
	 +---------------------------------------------------------------------------------------------
	 * @return					array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/03/18		初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public static function createNewStockIn($info)
	{
		$edit_log = '';
		$data = array();
		$purchase_order_id=false;
		foreach ($info as $key => $value){
			if (strtolower($key) == 'stockchangetype'){
				$data['change_type'] = $value;
			}elseif ( strtolower($key) == 'stockchangereason'){
				$data['reason'] = $value;
			}
			elseif ( strtolower($key) == 'purchase_order_id'){
				$purchase_order_id=trim($value);
				if($purchase_order_id=='')
					$purchase_order_id=false;
			}
			else
				$data[$key] = $value;
		}
		//导入了采购单的情况，先检查采购单有效性
		if($purchase_order_id){
			$purchaseModel=Purchase::findOne(['purchase_order_id'=>$purchase_order_id]);
			if($purchaseModel==null){
				$rtn['success']=false;
				$rtn['message'] .= ($rtn['message']==""?"":"<br>"). "$purchase_order_id 不是一个有效的采购单。";
				return $rtn;
			}else{
				//要入库的采购单号
				$purchaseId = $purchaseModel->id;
				//采购单原wh_id，如果原仓库与入库仓库不一样，记录旧仓库后续进行处理
				$purchase_WH_id = ($purchaseModel->warehouse_id==$data['warehouse_id'])?false:$purchaseModel->warehouse_id;
				
				$edit_log .= '通过采购单: '.$purchase_order_id.', ';
			}
		}
		
		$data['capture_user_id'] = \Yii::$app->user->id;
		
		$rtn['message']="";
		//Step:1. insert a stock In record into wh_stock_change
		//check if this stock change id is available
		$stock_change_id = $data['stock_change_id'];
		$prods =  $data['prod'];
		asort($prods);
		if($purchase_order_id){
			if( isset($data['comment'])){
				$data['comment'] .= "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ ".TimeUtil::getNow()."</font><br>".TranslateHelper::t('通过仓储管理模块入库。');
			}else{
				$data['comment'] = "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ ".TimeUtil::getNow()."</font><br>".TranslateHelper::t('通过仓储管理模块入库。');
			}
		}
		
		$transaction = Yii::$app->get('subdb')->beginTransaction ();
		
		if (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
			$rtn['success']=false;
			$rtn['message'] .= ($rtn['message']==""?"":"<br>"). "已存在该入库单号 $stock_change_id ，请更改入库单号";
		}else{
			$rtn = self::insertStockInRecord($data,$data['reason']);
		}
		
		//Step:2 insert this stock in sku,qty details into wh_stock_change_detail
		if ($rtn['success']){
			//only when step 1 successes, proceed with step 2
			foreach ($prods as $aProd){
				if ($rtn['success'])
					$rtn = self::insertStockChangeDetailRecord($data,$aProd['sku'], $aProd['stock_in_qty'] );
				else{
					$transaction->rollBack();
					return $rtn;
				}
			}//end of each sku to stock In
		}//end of step 2
		
		//Step 3: update 仓库信息，产品的 在库数量，在途数量 变更
		if ($rtn['success']){
			foreach ($prods as $aProd){
				/*
				 *  * Input:
				* 	1, sku
				*  2, warehouse id
				*  3, delter quantity of product on the way. if not to change, ZERO is ok, if to do minus, -2,
				*  4, delter quantity of product into stockage. if not to change, ZERO is ok, if to do minus, -2,
				*  5, delter quantity of product ordered. if not to change, ZERO is ok, if to do minus, -2,
				*  6, delter quantity of product reserved for shipment. if not to change, ZERO is ok, if to do minus, -2,
				*  7, This time Purchase Price, CNY: e.g. ￥8.50, if leave blank or 0, it will not be calculated as purchase normal average price
				*  8, new Location Grid, if null, not to change.
				*  *  */
				if($rtn['success']){
					if($purchase_order_id){
						//入库采购单产品，并更新仓库在途数和在库数
						$itemModel = PurchaseItems::findOne(['purchase_id'=>$purchaseId,'sku'=>$aProd['sku']]);
						if($itemModel==null){
							$rtn['success']=false;
							$rtn['message'] .= ($rtn['message']==""?"":"<br>"). "采购单 $purchase_order_id 采购的产品详情有误，保存终止！";
							$transaction->rollBack();
							return $rtn;
						}else{
							$price = empty($itemModel->price)?0:$itemModel->price;
						}
						if($purchase_WH_id){
							//入库仓不同于原仓库时，扣除原仓库在途数
							$rtn = self::modifyProductStockage($aProd['sku'], $purchase_WH_id,0-$aProd['stock_in_qty'] , 0, 0, 0,0,0);
							if(!$rtn['success']){
								$rtn['success']=false;
								$rtn['message'] .= ($rtn['message']==""?"":"<br>"). "入库仓库与采购仓库不同， ".$aProd['sku']." 在新旧仓库调转过程中出现问题，入库操作失败。";
								$transaction->rollBack();
								return $rtn;
							}
						}
						$rtn = self::modifyProductStockage($aProd['sku'], $data['warehouse_id'],0-$aProd['stock_in_qty'] , $aProd['stock_in_qty'], 0, 0,$price,$aProd['location_grid']);
					}else{
						$rtn = self::modifyProductStockage($aProd['sku'], $data['warehouse_id'],0, $aProd['stock_in_qty'], 0, 0, 0, $aProd['location_grid']);
					}
				}else{
					$transaction->rollBack();
					return $rtn;
				}
			}//end of each sku captured
		}//end of step 3
		
		//Step 4: if stockin by purchaseOrder , update purchase status after stockin
		if($purchase_order_id){
			$purchase = Purchase::findOne(['purchase_order_id'=>$purchase_order_id]);
			$purchase->status = PurchaseHelper::STOCK_INED;
			$purchase->comment = "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ ".TimeUtil::getNow().TranslateHelper::t("通过仓库模块入库")."<br>".$purchase->comment;
			$purchase->save(false);
			
			//更新所有采购明细的已入库数量
			$items = PurchaseItems::findAll(['purchase_id'=>$purchase->id]);
			if(!empty($items)){
				foreach ($items as $item){
					$item->in_stock_qty = $item->qty;
					$item->save(false);
				}
			}
		}
		//end of step 4
		if ($rtn['success']){
			$transaction->commit();
			
			//写入操作日志
			UserHelper::insertUserOperationLog('inventory', "手动入库, ".$edit_log.'生成入库单: '.$stock_change_id);
		}else{
			$transaction->rollBack();
		}
		
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Create product stockOut record
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 		info		stockout order info array
	 +---------------------------------------------------------------------------------------------
	 * @return					array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/03/20		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function createNewStockOut($info)
	{
		$data = array();
			
		foreach ($info as $key => $value){
			if (strtolower($key) == 'stockchangetype'){
				$data['change_type'] = $value;
			}elseif ( strtolower($key) == 'stockchangereason'){
				$data['reason'] = $value;
			}
			else
				$data[$key] = $value;
		}
		$data['capture_user_id'] = \Yii::$app->user->id;
		
		//是否支持零库存发
		$support_zero_inventory_shipments = ConfigHelper::getConfig('support_zero_inventory_shipments')==null?'Y':ConfigHelper::getConfig('support_zero_inventory_shipments');
		if($support_zero_inventory_shipments == 'N'){
			//检测库存是否有足够库存出库
			$msg = '';
			if(!empty($data['prod'])){
				$skus = array();
				foreach ($data['prod'] as $p){
					$skus[] = strtolower($p['sku']);
				}
				
				//查询对应库存
				$stockList = array();
				$stocks = ProductStock::find()->select(['sku', 'qty_in_stock'])->where(['warehouse_id' => $data['warehouse_id'], 'sku' => $skus])->asArray()->all();
				foreach($stocks as $stock){
					$stockList[trim(strtolower($stock['sku']))] = $stock['qty_in_stock']; 
				}
				
				//判断库存
				foreach ($data['prod'] as $p){
					if(array_key_exists(strtolower($p['sku']), $stockList)){
						if(!empty($p['stock_out_qty']) && $p['stock_out_qty'] > $stockList[strtolower($p['sku'])]){
							$msg .= "  ".$p['sku']." 库存不足出库！<br>";
						}
					}
					else{
						$msg .= "  ".$p['sku']." 不存在库存，不能出库！<br>";
					}
				}
				
				if(!empty($msg)){
					$rtn['success']=false;
					$rtn['message'] = $msg;
					return $rtn;
				}
			}
		}

		$rtn['message']="";
		//Step:1. insert a stock In record into wh_stock_change
		//check if this stock change id is available
		$stock_change_id = $data['stock_change_id'];
		if (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
			$rtn['success']=false;
			$rtn['message'] .=  ($rtn['message']==""?"":"<br>"). "已存在该出库单号 $stock_change_id ，请更改出库单号";
		}else{
			$rtn = self::insertStockOutRecord($data,$data['reason']);
		}
		
		//Step:2 insert this stock in sku,qty details into wh_stock_change_detail
		if ($rtn['success']){
			//only when step 1 successes, proceed with step 2
			$prods =  $data['prod'];
		
			foreach ($prods as $aProd){
				if ($rtn['success'])
					$rtn = self::insertStockChangeDetailRecord($data,$aProd['sku'] ,$aProd['stock_out_qty'] );
			}//end of each sku to stock In
		
		}//end of step 2
		
		//Step 3: update 仓库信息，产品的 在库数量，在途数量 变更
		if ($rtn['success']){
			foreach ($prods as $aProd){
				/*
				 *  * Input:listOneProductStockChangeHistoryData
				*  1, sku
				*  2, warehouse id
				*  3, delter quantity of product on the way. if not to change, ZERO is ok, if to do minus, -2,
				*  4, delter quantity of product into stockage. if not to change, ZERO is ok, if to do minus, -2,
				*  5, delter quantity of product ordered. if not to change, ZERO is ok, if to do minus, -2,
				*  6, delter quantity of product reserved for shipment. if not to change, ZERO is ok, if to do minus, -2,*/
				self::modifyProductStockage($aProd['sku'], $data['warehouse_id'],0, 0 - $aProd['stock_out_qty'], 0, 0,0,0);
			}//end of each sku captured
		}//end of step 3
		
		//写入操作日志
		UserHelper::insertUserOperationLog('inventory', '手动出库, 生成出库单: '.$stock_change_id);
		
		return $rtn;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To modify Product Stockage info
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 	1, sku
	 *  2, warehouse id
	 *  3, delter quantity of product on the way. if not to change, ZERO is ok, if to do minus, -2,
	 *  4, delter quantity of product into stockage. if not to change, ZERO is ok, if to do minus, -2,
	 *  5, delter quantity of product ordered. if not to change, ZERO is ok, if to do minus, -2,
	 *  6, delter quantity of product reserved for shipment. if not to change, ZERO is ok, if to do minus, -2,
	 *  7, This time Purchase Price, CNY: e.g. ￥8.50, if leave blank or 0, it will not be calculated as purchase normal average price
	 *  8, new Location Grid, if null, not to change.
	 +---------------------------------------------------------------------------------------------
	 * @return
	 * 	$rtn,
	 * 		with $rtn['success']=true/false
	 * 		with $rtn['message'] = error message if any
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function modifyProductStockage($sku,$warehouse,$qty_on_the_way=0,$qty_in_stock=0,$qty_ordered=0,$qty_reserved=0, $purchase_price=0,$location_grid=null){
		$rtn['message'] = "";
		//Load the product stockage record for this sku in warehouse
		$aProductStockInfo = ProductStock::findOne(['warehouse_id'=>$warehouse ,'sku'=>$sku]);
		
		$sku = trim($sku);
		//if not existing such record for this warehouse, create one
		if ($aProductStockInfo == null){
			$aProductStockInfo = new ProductStock();
			$aProductStockInfo->warehouse_id =$warehouse;
			$aProductStockInfo->sku = $sku;
		}
	
		//calculate the total purchase cost/average cost
		if ($purchase_price <> 0 and $qty_in_stock <> 0){
			//$before_total_purchased = $aProductStockInfo->total_purchased;
			$before_total_cost = $aProductStockInfo->average_price * $aProductStockInfo->qty_in_stock;
			$now_total_cost = $before_total_cost + $purchase_price * $qty_in_stock;
			$aProductStockInfo->total_purchased += $qty_in_stock;
			if($aProductStockInfo->qty_in_stock + $qty_in_stock == 0){
				$aProductStockInfo->average_price = 0;
			}
			else{
				$aProductStockInfo->average_price = number_format((float)($now_total_cost / ($aProductStockInfo->qty_in_stock + $qty_in_stock)), 2, '.', '');
			}
		}
	
		$aProductStockInfo->qty_in_stock += $qty_in_stock;
		$aProductStockInfo->qty_order_reserved += $qty_reserved;
		$aProductStockInfo->qty_purchased_coming += $qty_on_the_way;
		$aProductStockInfo->qty_ordered += $qty_ordered;
	
		if ($location_grid <> null)
			$aProductStockInfo->location_grid = trim($location_grid);
	
		if ( $aProductStockInfo->save() ){//save successfull
			$rtn['success']=true;
		}else{
			$rtn['success']=false;
			foreach ($aProductStockInfo->errors as $k => $anError){
				$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}//end of save failed
	
	
		//finally, we need to update the total stockage for this sku product after modification
		$command = Yii::$app->get('subdb')->createCommand("update pd_product set total_stockage = (
				select sum(qty_in_stock) from wh_product_stock where sku=:sku) where sku=:sku " );
		$command->bindValue(":sku",$sku,\PDO::PARAM_STR);
		$command->execute();
	
		return $rtn;
	}
	
	public static function getProductInventory($sku,$warehouseId){
		$type=ProductHelper::getProductType($sku);
		if($type=="B"){
			return self::getBundleProductInventory($sku, $warehouseId);
		}
		return ProductStock::findOne(['warehouse_id'=>$warehouseId,'sku'=>$sku]);
	}

	public static function getProductAllInventory($sku){
		$type=ProductHelper::getProductType($sku);
		if($type=="B"){
			$result=[];
			$warehouses = ProductStock::find()->select('warehouse_id')->where(['sku'=>$sku])->asArray()->All();
			foreach($warehouses as $row){
				$rtn=self::getBundleProductInventory($sku, $row['warehouse_id']);
				$result[]=$rtn->attributes;
			}
			return $result;
		}
		return ProductStock::find()->where(['sku'=>$sku])->asArray()->All();
	}
	
	public static function getBundleProductInventory($sku,$warehouse_id){
		$result=ProductStock::findOne(['warehouse_id'=>$warehouse_id,'sku'=>$sku]);
		
		$AsProducts = ProductHelper::getBundleAsSKU($sku);
		$qty_in_stock=0;
		$qty_purchased_coming =0;
		foreach($AsProducts as $index=>$as){
			$asStock =  ProductStock::findOne(['sku'=>$as['sku'],'warehouse_id'=>$warehouse_id]);
			$as_qty_in_stock = empty($asStock->qty_in_stock)?0:$asStock->qty_in_stock;
			$as_purchased_coming = empty($asStock->qty_purchased_coming)?0:$asStock->qty_purchased_coming;
			if($index==0){
				$qty_in_stock = $as_qty_in_stock;
				$qty_purchased_coming = $as_purchased_coming;
			}else{
				$tmpQty = floor($as_qty_in_stock/$as['qty']);
				if($qty_in_stock > $tmpQty)
					$qty_in_stock=$tmpQty;
				
				$tmpComing = floor($as_purchased_coming/$as['qty']);
				if($qty_purchased_coming > $tmpComing)
					$qty_purchased_coming=$tmpComing;
			}
		}
		$result->qty_in_stock = $qty_in_stock;
		$result->qty_purchased_coming = $qty_purchased_coming;
		return $result;
	}
	
	public static function insertStockInRecord($data,$reason=0){
		$data['change_type'] = self::getStockChangeType("入库");
		return self::insertStockChangeRecord($data,$reason);
	}
	
	public static function insertStockOutRecord($data,$reason=0){
		$data['change_type'] = self::getStockChangeType("出库");
		return self::insertStockChangeRecord($data,$reason);
	}
	
	public static function insertStockTakeRecord($data,$reason=300){
		$data['change_type'] = self::getStockChangeType("盘点");
		return self::insertStockChangeRecord($data,$reason);
	}
	
	public static function insertDeleteStockRecord($data,$reason=400){
		$data['change_type'] = self::getStockChangeType("删除");
		return self::insertStockChangeRecord($data,$reason);
	}
	
	public static function insertUpdateStockRecord($data,$reason=500){
		$data['change_type'] = self::getStockChangeType("更新");
		return self::insertStockChangeRecord($data,$reason);
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To insert Other stock In records
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data						data posted with form
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * This is to insert other stock in headers and also the details
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function insertOtherStockIn($data){
		$rtn['message']="";
		//Step:1. insert a stock In record into wh_stock_change
		//check if this stock change id is available
		$stock_change_id = $data['stock_change_id'];
		$prods =  $data['prod'];
		asort($prods);
	
		if (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
			$rtn['success']=false;
			$rtn['message'] .= ($rtn['message']==""?"":"<br>"). "已存在该入库单号 $stock_change_id ，请更改入库单号";
		}else{
			$rtn = self::insertStockInRecord($data );
		}
	
		//Step:2 insert this stock in sku,qty details into wh_stock_change_detail
		if ($rtn['success']){
			//only when step 1 successes, proceed with step 2
			foreach ($prods as $aProd){
				if ($rtn['success'])
					$rtn = self::insertStockChangeDetailRecord($data,$aProd['sku'], $aProd['stock_in_qty'] );
			}//end of each sku to stock In
		}//end of step 2
	
		//Step 3: update 仓库信息，产品的 在库数量，在途数量 变更
		if ($rtn['success']){
			foreach ($prods as $aProd){
				/*
				 *  * Input:
				* 	1, sku
				*  2, warehouse id
				*  3, delter quantity of product on the way. if not to change, ZERO is ok, if to do minus, -2,
				*  4, delter quantity of product into stockage. if not to change, ZERO is ok, if to do minus, -2,
				*  5, delter quantity of product ordered. if not to change, ZERO is ok, if to do minus, -2,
				*  6, delter quantity of product reserved for shipment. if not to change, ZERO is ok, if to do minus, -2,
				*  7, This time Purchase Price, CNY: e.g. ￥8.50, if leave blank or 0, it will not be calculated as purchase normal average price
				*  8, new Location Grid, if null, not to change.
				*  *  */
				if ($rtn['success'])
					$rtn = self::modifyProductStockage($aProd['sku'], $data['warehouse_id'],0, $aProd['stock_in_qty'], 0, 0, 0, $aProd['location_grid']);
			}//end of each sku captured
		}//end of step 3
	
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To insert Stock change Record after use captured in Front End
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data						data posted with form
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * This is to insert both 出库  and 入库 records. According to the type in data posted.
	 * This function insert the change record header only
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function insertStockChangeRecord($data,$reason=0){
		$rtn['message']="";
		$model=new StockChange();
		$model->attributes=$data; //put the $data field values into model
		if(empty($model->warehouse_id))
			$model->warehouse_id=0;
		$model->capture_user_id = \Yii::$app->user->id;
		$model->create_time = TimeUtil::getNow() ;
		$model->update_time = TimeUtil::getNow() ;
	
		if ($reason<>0 and $reason<>"")
			$model->reason = $reason;
	
		if ( $model->save() ){//save successfull
			$rtn['success']=true;
			//ENUM('purchase','stock_change','product','finance','warehouse','supplier')
			//部分功能为完善，暂时不写log
			//OperationLogHelper::saveOperationLog('stock_change',$model->stock_change_id,"创建库存变化记录");
		}else{
			$rtn['success']=false;
			foreach ($model->errors as $k => $anError){
				$rtn['message'] .=($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}//end of save failed
		return $rtn;
	}
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * To insert Other stock Out records
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data						data posted with form
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * This is to insert other stock out headers and also the details
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function insertOtherStockOut($data){
		$rtn['message']="";
		//Step:1. insert a stock In record into wh_stock_change
		//check if this stock change id is available
		$stock_change_id = $data['stock_change_id'];
		if (StockChange::findOne($stock_change_id) <> null){
			$rtn['success']=false;
			$rtn['message'] .= "E_Inventory_006 ". ($rtn['message']==""?"":"<br>"). "WH001:已存在该出库单号 $stock_change_id ，请更改出库单号";
		}else{
			$rtn = self::insertStockOutRecord($data );
		}
	
		//Step:2 insert this stock in sku,qty details into wh_stock_change_detail
		if ($rtn['success']){
			//only when step 1 successes, proceed with step 2
			if (isset($data['productsinfo'])){
				$prods =  json_decode($data['productsinfo']);
				asort($prods);
			}else
				$prods =  $data['prods'];
	
			foreach ($prods as $aProd){
				if ($rtn['success'])
					$rtn = self::insertStockChangeDetailRecord($data,$aProd['sku'] ,$aProd['stock_out_qty'] );
			}//end of each sku to stock In
	
		}//end of step 2
	
		//Step 3: update 仓库信息，产品的 在库数量，在途数量 变更
		if ($rtn['success']){
			foreach ($prods as $aProd){
				/*
					*  * Input:
				*  1, sku
				*  2, warehouse id
				*  3, delter quantity of product on the way. if not to change, ZERO is ok, if to do minus, -2,
				*  4, delter quantity of product into stockage. if not to change, ZERO is ok, if to do minus, -2,
				*  5, delter quantity of product ordered. if not to change, ZERO is ok, if to do minus, -2,
				*  6, delter quantity of product reserved for shipment. if not to change, ZERO is ok, if to do minus, -2,*/
				self::modifyProductStockage($aProd['sku'], $data['warehouse_id'],0, 0 - $aProd['stock_out_qty'], 0, 0,0,0);
			}//end of each sku captured
		}//end of step 3
	
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To insert Stock change Detail Record after use captured in Front End
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data						data posted with form
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * This is to insert both 出库  and 入库 records Detail.  e.g. SKU, qty
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function insertStockChangeDetailRecord($data,$sku,$stock_in_qty){
		$rtn['message']="";
	
		if ($stock_in_qty == 0){
			$rtn['success']=true;
			return $rtn;
		}
		
		//step 1: insert the stock change detail
		$aStockChangeDetail=new StockChangeDetail();
		$aStockChangeDetail->attributes=$data; //put the $data field values into model
		$sku = trim($sku);
		$aStockChangeDetail->sku = $sku;
		$aStockChangeDetail->qty = $stock_in_qty;
	
		$prodInfo = Product::find()->where(['sku'=>$aStockChangeDetail->sku])->One();
		if ($prodInfo <> null){
			$aStockChangeDetail->prod_name = $prodInfo->name;
		}
	
		//make the prod_name no longer than 100 length
		if (strlen($aStockChangeDetail->prod_name) > 100)
			$aStockChangeDetail->prod_name = substr($aStockChangeDetail->prod_name, 0,100);
	
		if ( $aStockChangeDetail->save() ){//save successfull
			$rtn['success']=true;
		}else{
			$rtn['success']=false;
			foreach ($aStockChangeDetail->errors as $k => $anError){
				$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}//end of save failed
	
		return $rtn;
	}
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * To get All Warehouse Info
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	$all	if false,only return active warehous info
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			map.
	 *			e.g.
	 *			array(id1=>array(...),id2=>array(...))
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/12/23			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getAllWarehouseInfo($all=false, $params = array())
	{
		$query=Warehouse::find();
		if($all){
			$query->andWhere(['is_active'=>'Y']);
		}else{
			$query->orderBy('is_active desc');
		}
		if(isset($params['warehouse_id'])){
			$query->andWhere(['warehouse_id'=>$params['warehouse_id']]);
		}
		
		//不显示已删除的仓库
		$query = $query->andWhere("is_active != 'D' and name!='无'");
	
		$warehouses = $query->asArray()->All();
		$infoArr=array();
		foreach($warehouses as $warehouse)
		{
			$infoArr[$warehouse['warehouse_id']]=$warehouse;
		}
	
		return $infoArr;
	
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To get All Warehouse id and Name Map
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			map.
	 *			e.g.
	 *			array(id1=>"shanghai",id2=>"shen zhen")
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWarehouseIdNameMap(){
		$warehouseIdNameMap=array();
		$warehouseInfoArr=self::getAllWarehouseInfo();
		foreach($warehouseInfoArr as $id=>$info)	$warehouseIdNameMap[$id]=$info['name'];
		return $warehouseIdNameMap;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To get All Warehouse id and Name Combo box format
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			map.
	 *			e.g.
	 *			array( array('warehouse_id'=>-1,'name'=>"全部"),
	 *				   array (...)
	 *				)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function warehouseIdNameComoBox($withAll = true){
		$warehouseIdNameArr=array();
		$warehouseIdNameMap=self::getWarehouseIdNameMap();
		foreach($warehouseIdNameMap as $id=>$name)
		{
			$warehouseIdNameArr[]=array('warehouse_id'=>$id,'name'=>htmlspecialchars($name,ENT_QUOTES));
		}
		$warehouseIdNameComoBox=$warehouseIdNameArr;
		if ($withAll)
			$warehouseIdNameComoBox[]=array('warehouse_id'=>-1,'name'=>"全部");
		return $warehouseIdNameComoBox;
	}
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * excel 导入 出入库 产品列表
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 	array:	$ExcelFile		用户按照excel模版 格式制定的 excel数据 限xls 文件
	 * 			string:	$warehouse_id	导入文件时选择的仓库id
	 * 			string:	$chageType		出库 还是 入库
	 * 			string  $type           导入 的库存excel格式是小老板 还是其他 default ：littleboss ， 可选sellertools
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result ['success'] boolean
	 * 					$result ['message'] string  运行结果的提示信息
	 * 					$result ['tb_Html'] html字段,返回table内容
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/3/30		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function importStockChangeProdsByExcel($ExcelFile ,$warehouse_id,$changeType ,$type="littleboss"){
		//初始化 返回结果
		$result['success'] = true;
		$result['message'] = '';
	
		//获取 excel数据
		if ($type == 'sellertool' ){
			$excel_data = ExcelHelper::excelToArray($ExcelFile , self::$SELLERTOOL_EXCEL_COLUMN_MAPPING, true);
		}else{
			$excel_data = ExcelHelper::excelToArray($ExcelFile , self::$EXCEL_COLUMN_MAPPING, true);
		}
		
	
		$prod_list = [];
		$prods =[];
		//检查excel 导入的sku是否重复
		foreach($excel_data as &$aProd){
			//排除表头
			$field_labels = self::$EXPORT_EXCEL_FIELD_LABEL;
			if($aProd['sku']==$field_labels['sku'] && $aProd['stockchange_qty']==$field_labels['stockchange_qty'] && $aProd['location_grid']==$field_labels['location_grid']){
				continue;
			}
			//验证sku有效性	
			$aProd['sku'] = trim($aProd['sku']);
			$aProd ['sku'] = str_replace('\r', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace('\n', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace('\t', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace(chr(10), '', $aProd ['sku']);
			
			$product=Product::findOne(['sku'=>$aProd['sku']]);
			if(empty($product)){
			    //当商品模块不存在时，验证是否为别名，是则取其主SKU
			    $aliasku = ProductApiHelper::getRootSKUByAlias($aProd ['sku']);
			    $product=Product::findOne(['sku'=>$aliasku]);
			    if(empty($product)){
			        $result['success'] = false;
			        $result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('不存在！  ');
			        continue;
			    }
			    $aProd ['sku'] = $aliasku;
			}
			if($product->type=="C" or $product->type=="B"){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('是变参产品或者捆绑产品，不能对其做库存操作！ ');
				continue;
			}
			//验证出入库数量有效性
			if( !is_numeric($aProd['stockchange_qty']) ){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('输入的库存无效，必须为数字！  ');
				continue;
			}
			//处理重复sku
			if (! in_array($aProd['sku'], $prod_list)){
				$prod_list[] = $aProd['sku'];
				$prods[$aProd['sku']]['sku'] = $aProd['sku'];
				$prods[$aProd['sku']]['stockchange_qty'] = $aProd['stockchange_qty'];
				$prods[$aProd['sku']]['location_grid'][] = $aProd['location_grid'];
			}else{
				if($result['success'])
					$result['message'] .= $aProd['sku'] .TranslateHelper::t(" 重复录入,已合并显示!  ");
				$prods[$aProd['sku']]['stockchange_qty'] += $aProd['stockchange_qty'];
				$prods[$aProd['sku']]['location_grid'][] = $aProd['location_grid'];
				$prods[$aProd['sku']]['location_grid'] = array_unique($prods[$aProd['sku']]['location_grid']);
			}
		}
		if(count($prods)<=0){//文件导入无产品
			$result['success'] = false;
			$result['message'] .= TranslateHelper::t('导入文件无产品或产品信息有误，请检查excel文件后重新导入！ ');
			return $result;
		}
		if(!$result['success']){//导入信息有错误
			return $result;
		}else{//导入信息正常：
			//table header
			$Html = self::importDatasToHtml($prods, $warehouse_id, $changeType);
			
			$result['td_Html'] = $Html['tb_Html'];
			$result['textarea_div_html'] = $Html['textarea_div_html'];
		}
		return $result;
	}//end of importStockChangeProdsByExcel
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 复制粘贴 excel格式文本 导入  出入库  产品
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 	array:	$prodDatas		用户按照excel模版 格式制定的 excel数据 限xls 文件
	 * 			string:	$warehouse_id	导入文件时选择的仓库id
	 * 			string:	$chageType		出库 还是 入库
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result ['success'] boolean
	 * 					$result ['message'] string  运行结果的提示信息
	 * 					$result ['tb_Html'] html字段,返回table内容
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/3/30		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function importStockChangeProdsByExcelFormatText($prodDatas ,$warehouse_id,$changeType){
		//初始化 返回结果
		$result['success'] = true;
		$result['message'] = '';
	
		$prod_list = [];
		$prods =[];
		//检查excel 导入的sku是否重复
		foreach($prodDatas as &$aProd){
			//排除表头
			$field_labels = self::$EXPORT_EXCEL_FIELD_LABEL;
			if($aProd['sku']==$field_labels['sku'] && $aProd['stockchange_qty']==$field_labels['stockchange_qty'] && $aProd['location_grid']==$field_labels['location_grid']){
				continue;
			}
			//验证sku有效性
			$aProd['sku'] = trim($aProd['sku']);
			$aProd ['sku'] = str_replace('\r', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace('\n', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace('\t', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace(chr(10), '', $aProd ['sku']);
			
			if(Product::findOne(['sku'=>$aProd['sku']])==null){
			    //当商品模块不存在时，验证是否为别名，是则取其主SKU
			    $aliasku = ProductApiHelper::getRootSKUByAlias($aProd ['sku']);
			    if(Product::findOne(['sku'=>$aliasku])==null){
			    	$result['success'] = false;
    				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('不存在！ ');
    				continue;
			    }
			    $aProd ['sku'] = $aliasku;
			}
			//验证出入库数量有效性
			$product=Product::findOne(['sku'=>$aProd['sku']]);
			if($product==null){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('不存在！ ');
				continue;
			}
			if($product->type=="C" or $product->type=="B"){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('是变参产品或捆绑产品，不能对其做库存操作！ ');
				continue;
			}
			//处理重复sku
			if (! in_array($aProd['sku'], $prod_list)){
				$prod_list[] = $aProd['sku'];
				$prods[$aProd['sku']]['sku'] = $aProd['sku'];
				$prods[$aProd['sku']]['stockchange_qty'] = $aProd['stockchange_qty'];
				$prods[$aProd['sku']]['location_grid'][] = $aProd['location_grid'];
			}else{
				if($result['success'])
					$result['message'] .= $aProd['sku'] .TranslateHelper::t(" 重复录入,已合并显示!  ");
				$prods[$aProd['sku']]['stockchange_qty'] += $aProd['stockchange_qty'];
				$prods[$aProd['sku']]['location_grid'][] = $aProd['location_grid'];
				$prods[$aProd['sku']]['location_grid'] = array_unique($prods[$aProd['sku']]['location_grid']);
			}
		}
		if(count($prods)<=0){//文件导入无产品
			$result['success'] = false;
			$result['message'] .= TranslateHelper::t('粘贴的文本无产品或产品信息有误，请检查复制来源后重新导入！ ');
			return $result;
		}
		if(!$result['success']){//导入信息有错误
			return $result;
		}else{//导入信息正常：
			//table header
			$Html = self::importDatasToHtml($prods, $warehouse_id, $changeType);
				
			$result['td_Html'] = $Html['tb_Html'];
			$result['textarea_div_html'] = $Html['textarea_div_html'];
		}
		return $result;
	}//end of importStockChangeProdsByExcelFormatText
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 产品数据array写入Html，以便输出到浏览器
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 	array:	$prodDatas		产品信息及qty,货架位置信息
	 * 			string:	$warehouse_id	导入文件时选择的仓库id
	 * 			string:	$chageType		出库 还是 入库
	 +---------------------------------------------------------------------------------------------
	 * @return string	tb_Html 		html字段,返回table内容
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/3/30		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	protected static function importDatasToHtml($prodDatas,$warehouse_id,$changeType){
		if(strtolower($changeType)=='stockin'){
			$tb_Html = "<tr><th width='80px'>".TranslateHelper::t('图片')."</th>".
					"<th width='150px' style='text-align:center;'>".TranslateHelper::t('sku')."</th>".
					"<th width='250px'>".TranslateHelper::t('产品名称')."</th>".
					//"<th width='100px'>".TranslateHelper::t('状态')."</th>".
					"<th width='100px'>".TranslateHelper::t('在库数量')."</th>".
					"<th width='100px'>".TranslateHelper::t('入库数量')."</th>".
					"<th width='100px'>".TranslateHelper::t('货架位置')."</th>".
					"<th width='70px'>".TranslateHelper::t('操作')."</th>".
					"</tr>";
		}
		if(strtolower($changeType)=='stockout'){
			$tb_Html = "<tr><th width='80px'>".TranslateHelper::t('图片')."</th>".
					"<th width='150px'>".TranslateHelper::t('sku')."</th>".
					"<th width='250px'>".TranslateHelper::t('产品名称')."</th>".
					//"<th width='100px' style='vertical-align:middle;text-align:center;'>".TranslateHelper::t('状态')."</th>".
					"<th width='100px'>".TranslateHelper::t('在库数量')."</th>".
					"<th width='100px'>".TranslateHelper::t('出库数量')."</th>".
					"<th width='100px'>".TranslateHelper::t('货架位置')."</th>".
					"<th width='70px'>".TranslateHelper::t('操作')."</th>".
					"</tr>";
		}
		if(strtolower($changeType)=='stocktake'){
			$tb_Html = "<tr><th width='10%'>".TranslateHelper::t('图片')."</th>".
					"<th width='20%'>sku</th>".
					"<th width='30%'>".TranslateHelper::t('产品名称')."</th>".
					//"<th width='10%'>".TranslateHelper::t('状态')."</th>".
					"<th width='10%'>".TranslateHelper::t('应有库存')."</th>".
					"<th width='10%'>".TranslateHelper::t('实际盘点数')."</th>".
					"<th width='10%'>".TranslateHelper::t('货架位置')."</th>".
					"<th width='8%'>".TranslateHelper::t('操作')."</th>".
					"</tr>";
		}
		if(strtolower($changeType)=='stockallocation'){
			$tb_Html = "<tr><th width='10%'>".TranslateHelper::t('图片')."</th>".
					"<th width='20%'>sku</th>".
					"<th width='30%'>".TranslateHelper::t('产品名称')."</th>".
					//"<th width='10%'>".TranslateHelper::t('状态')."</th>".
			"<th width='10%'>".TranslateHelper::t('出仓库存')."</th>".
			"<th width='10%'>".TranslateHelper::t('调拨数量')."</th>".
			"<th width='10%'>".TranslateHelper::t('入仓货架')."</th>".
			"<th width='8%'>".TranslateHelper::t('操作')."</th>".
			"</tr>";
		}
		
		$textarea_div_html = "";
		
		$index=0;
		foreach ($prodDatas as $p){
			$sku = $p['sku'];
			$pInfo = Product::findOne(['sku'=>$sku]);
			$name = $pInfo['name'];
			$img = $pInfo['photo_primary'];
			$status = (empty($pInfo['status']))?"OS":$pInfo['status'];
			$statusValue = self::$PRODUCT_STATUS[$status];
			
			$class = '';
			if(!is_int($index / 2))
				$class = ' striped-row';
		
			$location_grid = '';
			if(is_array($p['location_grid'])){
				if(count($p['location_grid'])>1){
					$location_grid = implode(',', $p['location_grid']);
				}else{
					$location_grid = $p['location_grid'][0];
				}
			}
		
			$stockages = ProductStock::find()->where(['sku'=>$sku])->asArray()->all();
			$active_wh_stock = 0;
			$stockageHtml = '';
			for($i=0;$i<count($stockages);$i++){
				$stockageHtml .= 'warehouse_id_'.$stockages[$i]['warehouse_id'].'_qty="'.$stockages[$i]['qty_in_stock'].'" ';
				if($stockages[$i]['warehouse_id']==$warehouse_id)
					$active_wh_stock = $stockages[$i]['qty_in_stock'];
			}
			//table prod_tr
			if(strtolower($changeType)=='stockin' || strtolower($changeType)=='stockout'){
				if(strtolower($changeType)=='stockin'){
					$qty_column_name = 'stock_in_qty';
					$element_className = 'cancelStockInProd';
				}
				if(strtolower($changeType)=='stockout'){
					$qty_column_name = 'stock_out_qty';
					$element_className = 'cancelStockOutProd';
		
				}
					
				$tb_Html .="<tr class='prodList_tr".$class."'".$stockageHtml .">".
						"<td name='prod[".$index."][img]' value='".$img."' style='text-align:center'><img src='".$img."' style='width:80px ! important;height:80px ! important;'></td>".
						"<td name='prod[".$index."][sku]'>".$sku."</td>".
						"<td name='prod[".$index."][name]'>".$name."</td>".
						//"<td name='prod[".$index."][status]' value='".$status."'>".$statusValue."</td>".
						"<td name='prod[".$index."][qty_in_stock]' value='".$active_wh_stock."'>".$active_wh_stock."</td>".
						"<td ><input name='prod[".$index."][".$qty_column_name."]' class='form-control' value='".$p['stockchange_qty']."'></td>".
						"<td ><input name='prod[".$index."][location_grid]' class='form-control' value='".$location_grid."'></td>".
						"<td ><div><a class=\"".$element_className."\">".TranslateHelper::t('取消')."</a></div></td>".
						"</tr>";
			}
			elseif(strtolower($changeType)=='stocktake'){
				$tb_Html .="<tr class='prodList_tr'".$stockageHtml .">".
						"<td name='prod[".$index."][img]' value='".$img."' style='text-align:center'><img src='".$img."' style='width:80px ! important;height:80px ! important;'></td>".
						"<td name='prod[".$index."][sku]'>".$sku."</td>".
						"<td name='prod[".$index."][name]'>".$name."</td>".
						//"<td name='prod[".$index."][status]' value='".$status."'>".$statusValue."</td>".
						"<td name='prod[".$index."][qty_shall_be]' value='".$active_wh_stock."'>".$active_wh_stock."</td>".
						"<td ><input name='prod[".$index."][qty_actual]' class='form-control' value='".$p['stockchange_qty']."' style='width:80px'></td>".
						"<td ><input name='prod[".$index."][location_grid]' class='form-control' value='".$location_grid."' style='width:80px'></td>".
						"<td ><div><a class='cancelStockTakeProd'>".TranslateHelper::t('取消')."</a></div></td>".
						"</tr>";
			}
			elseif(strtolower($changeType)=='stockallocation'){
				$tb_Html .=
					"<tr index=".$index." class='prodList_tr'".$stockageHtml.">".
						"<td ><img src='".$img."' style='width:80px ! important;height:80px ! important'></td>".
						"<td ><input name='prod[".$index."][sku]' type='hidden' value='".$sku."'>".$sku."</td>".
						"<td >".$name."</td>".
						"<td style='' name='prod[".$index."][qty_shall_be]' >".$active_wh_stock."</td>".
						"<td ><input name='prod[".$index."][qty]' class='form-control' value='".$p['stockchange_qty']."'></td>".
						"<td ><input name='prod[".$index."][location_grid]' class='form-control' value='".$location_grid."'></td>".
						"<td ><div><a class='cancelstockAllocationProd'>".TranslateHelper::t('取消')."</a></div></td>".
					"</tr>";
			}
			$textarea_div_html .= "<textarea class='hide' name='prod[".$index."][sku]' style='display:none'>".$sku."</textarea>".
					"<textarea class='hide' name='prod[".$index."][name]' style='display:none'>".$name."</textarea>";
			$index ++;
		}
		
		return array('tb_Html'=>$tb_Html,'textarea_div_html'=>$textarea_div_html);
	}
	
	/**
	 * 获取一个sku的库存拣货信息
	 * @access 	static
	 * @param	string:	$sku			需要拣货的产品sku数组
	 * @param	string:	$warehouseId	拣货的仓库id
	 * @return	array:	$pickingInfo = array(
	 * 											'prod_stock_id'=>number,
	 * 											'warehouse_id'=>number,
	 * 											'sku'=>string,
	 * 											'location_grid'=>string,
	 * 											'qty_in_stock'=>number,
	 * 											'qty_purchased_coming'=>number,
	 * 											'qty_ordered'=>number,
	 * 											'qty_order_reserved'=>number,
	 * 											'average_price'=>number,
	 * 											'total_purchased'=>number,
	 * 											'addi_info'=>string,
	 * 											'update_time'=>string,
	 * 											)
	 * @author 	million
	 * @version 	0.1		2015.01.13
	 */
	public static function getPickingInfo($warehouseId,$sku){
		$pickingInfo=array();
		$info = ProductStock::find()
		//->select(['warehouse_id','sku','qty_in_stock','location_grid'])
		->where(['warehouse_id'=>$warehouseId,'sku'=>$sku])
		->asArray()
		->one();
		if($info!==null){
			$pickingInfo = $info;
		}
		return $pickingInfo;
	}
	
	
	/**
	 * 获取多个sku的库存配货信息
	 * @access 	static
	 * @param	array:	$skuArr			需要拣货的产品sku数组
	 * @param	string:	$warehouseId	拣货的仓库id
	 * @return	array:	$pickingInfo = array(
	 * 											'prod_stock_id'=>number,
	 * 											'warehouse_id'=>number,
	 * 											'sku'=>string,
	 * 											'location_grid'=>string,
	 * 											'qty_in_stock'=>number,
	 * 											'qty_purchased_coming'=>number,
	 * 											'qty_ordered'=>number,
	 * 											'qty_order_reserved'=>number,
	 * 											'average_price'=>number,
	 * 											'total_purchased'=>number,
	 * 											'addi_info'=>string,
	 * 											'update_time'=>string,
	 * 											)
	 * @author 	million
	 * @version 	0.1		2015.01.13
	 */
	public static function getPickingInfos($skuArr=array(),$warehouseId){
		$pickingInfo=array();
		if (count($skuArr)>0 && $warehouseId>=0){
			$infos = ProductStock::find()
			//->select(['warehouse_id','sku','qty_in_stock','location_grid'])
			->where(['warehouse_id'=>$warehouseId])
			->andwhere(['sku'=>$skuArr])
			->asArray()
			->all();
			if(!empty($infos)){
				$pickingInfo =$infos;
			}
		}
		return $pickingInfo;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To get All Warehouse id and Name Map is_oversea
	 +---------------------------------------------------------------------------------------------
	 * @param	$is_show_all	是否显示全部	-1表示全部，0表示关闭，1表示启用
	 * @param	$is_mode		显示仓库的类型	-1:显示全部，0:显示自建仓库，1:显示海外仓库
	 * @param	$is_type		是否显示海外仓仓库类型	0表示不显示类型，1表示显示类型
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			array(id1=>"shanghai",id2=>"shen zhen")
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/01/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWarehouseOrOverseaIdNameMap($is_show_all = -1,$is_mode = -1, $is_type = 0){
		if($is_show_all == 0){
			$is_show_all = 'N';
		}else if($is_show_all == 1){
			$is_show_all = 'Y';
		}else{
			$is_show_all = '';
		}
		
		$warehouseIdNameMap=array();
		$warehouseInfoArr=self::getAllWarehouseInfo();
		foreach($warehouseInfoArr as $id=>$info){
			if((!empty($is_show_all)) && ($info['is_active'] != $is_show_all)){
				continue;
			}
			
			if(($is_mode != -1) && ($info['is_oversea'] != $is_mode)){
				continue;
			}
			
			if($is_type == 1){
				$warehouseIdNameMap[$id]=array('name'=>$info['name'],'oversea_type'=>$info['oversea_type'],
						'carrier_code'=>$info['carrier_code'],'third_party_code'=>$info['third_party_code'],'is_active'=>$info['is_active']);
			}else{
				$warehouseIdNameMap[$id]=$info['name'];
			}
		}
		return $warehouseIdNameMap;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取海外仓仓库类型是api还是excel模式
	 +---------------------------------------------------------------------------------------------
	 * @param	$warehouse_id	仓库ID
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			0
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/02/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWarehouseOverseaType($warehouse_id){
		
		$warehouseInfoArr=self::getAllWarehouseInfo(false,array('warehouse_id'=>$warehouse_id));
		
		if(count($warehouseInfoArr) > 0){
			$warehouseInfo = current($warehouseInfoArr);
			reset($warehouseInfoArr);
			
			return $warehouseInfo['oversea_type'];
		}else{
			return 0;
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取海外仓excel模式的设置数据
	 +---------------------------------------------------------------------------------------------
	 * @param	$warehouse_id	仓库ID
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			0
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/02/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWarehouseOverseaExcelFormat($warehouse_id){
		$warehouseInfoArr=self::getAllWarehouseInfo(false,array('warehouse_id'=>$warehouse_id));
	
		if(count($warehouseInfoArr) > 0){
			$warehouseInfo = current($warehouseInfoArr);
			reset($warehouseInfoArr);
			
			if(!empty($warehouseInfo['excel_format']))
				$warehouseInfo['excel_format'] = json_decode($warehouseInfo['excel_format'],true);
				
			return $warehouseInfo;
		}else{
			return array();
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取单条仓库信息
	 +---------------------------------------------------------------------------------------------
	 * @param	$warehouse_id	仓库主键ID
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/01/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWarehouseInfoOneById($warehouse_id){
		$warehose = Warehouse::find()->where(['warehouse_id'=>$warehouse_id])->asArray()->one();
		
		$warehose['address_params'] = json_decode($warehose['address_params'],true);
		$warehose['addi_info'] = json_decode($warehose['addi_info'],true);
		
		return $warehose;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 仓库匹配规则信息
	 +---------------------------------------------------------------------------------------------
	 * @param	$is_show_all	是否显示全部，-1显示全部，0显示关闭，1显示开启
	 * @param	$params			用于添加额外的查询功能	可能的值:warehouse_id查询对应仓库ID存在几个匹配规则
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/01/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getAllWarehouseMatchingRuleInfo($is_show_all = -1, $params = array(), $orderByParams = array()){
		$query = WarehouseMatchingRule::find();
		if($is_show_all == 1)
			$query->andWhere(['is_active'=>1]);
		else if($is_show_all == 0)
			$query->andWhere(['is_active'=>0]);
		
		if(isset($params['warehouse_id'])){
			$query->andWhere(['warehouse_id'=>$params['warehouse_id']]);
		}
		
		if(isset($params['warehouse_arr'])){
			$query->andWhere(['in','warehouse_id',$params['warehouse_arr']]);
		}
		
		if(!empty($orderByParams)){
			$tmpOrderStr = '';
			
			foreach ($orderByParams as $orderByParamKey => $orderByParamVal){
				$tmpOrderStr .= (empty($tmpOrderStr) ? '' : ',').$orderByParamKey.' '.$orderByParamVal;
			}
			
			$query->orderBy($tmpOrderStr);
		}
	
		$warehouseRules = $query->asArray()->All();
		$infoArr=array();
		foreach($warehouseRules as $warehouseRule){
			$infoArr[$warehouseRule['id']]=$warehouseRule;
		}
	
		return $infoArr;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取仓库ID对应启用的匹配规则
	 +---------------------------------------------------------------------------------------------
	 * @param	$warehouse_id	仓库主键ID
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/01/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWarehouseMatchingRuleIdNameMapById($warehouse_id){
		$warehouseRuleIdNameMap=array();
		$params['warehouse_id'] = $warehouse_id;
		$warehouseRuleInfoArr=self::getAllWarehouseMatchingRuleInfo(1, $params);
		
		foreach($warehouseRuleInfoArr as $id=>$info){
			$warehouseRuleIdNameMap[$id]=$info['rule_name'];
		}
		
		return $warehouseRuleIdNameMap;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置仓库关闭或开启
	 +---------------------------------------------------------------------------------------------
	 * @param	$warehouse_id	仓库主键ID
	 * @param	$is_active		开启或关闭	1:开启，0:关闭
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function warehouseOnoffById($warehouse_id, $is_active){
		$warehouseObj = Warehouse::find()->where(['warehouse_id'=>$warehouse_id])->one();
		
		if($warehouseObj == null){
			return self::output(array(), 1, '非法操作.'); 
		}
		
		$warehouseObj->is_active = $is_active;
		
		if($warehouseObj->save(false)){
			return self::output(array(), 0, ((($is_active == 'Y') ? '启用' : '关闭').'成功.'));
		}else{
			return self::output(array(), 1, '保存失败.');
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 仓库地址信息保存
	 +---------------------------------------------------------------------------------------------
	 * @param	$params	信息保存数组
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/02/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function saveWarehouseInfoById($params = array()){
		if(!isset($params['warehouse_id'])){
			return self::output('', 1, '保存失败,仓库ID为空.');
		}
		
		$is_create = false;
		if($params['warehouse_id'] == -1){
			$warehouseObj = new Warehouse();
			$warehouseObj->create_time = GetControlData::getNowDateTime_str();
			$warehouseObj->capture_user_id = isset($params['puid']) ? $params['puid'] : \Yii::$app->user->id;
			
			if(isset($params['is_oversea'])){
				$warehouseObj->is_oversea = $params['is_oversea'];
			}
			
			if(isset($params['carrier_code'])){
				$warehouseObj->carrier_code = $params['carrier_code'];
			}
			
			if(isset($params['third_party_code'])){
				$warehouseObj->third_party_code = $params['third_party_code'];
			}
			
			if(isset($params['oversea_type'])){
				$warehouseObj->oversea_type = $params['oversea_type']; 
			}
			
			$is_create = true;
		}else{
			$warehouseObj = Warehouse::find()->where(['warehouse_id'=>$params['warehouse_id']])->one();
		}
		
		if($warehouseObj == null){
			return self::output('', 1, '非法操作.');
		}
		
		if($warehouseObj->isNewRecord){
			$count = Warehouse::find()->where(['name'=>$params['warehouse_name']])->count();
		}else{
			$count = Warehouse::find()->where('name = :name and warehouse_id <> :warehouse_id',[':name'=>$params['warehouse_name'],':warehouse_id'=>$params['warehouse_id']])->count();
		}
		
		if ($count>0){
			return self::output('', 1, '仓库名已存在，请勿重复！');
		}
		
		$warehouseObj->name = $params['warehouse_name'];
		$warehouseObj->address_phone = empty($params['address_phone']) ? '' : $params['address_phone'];
		$warehouseObj->address_state = empty($params['address_state']) ? '' : $params['address_state'];
		$warehouseObj->address_city = empty($params['address_city']) ? '' : $params['address_city'];
		$warehouseObj->address_postcode = empty($params['address_postcode']) ? '' : $params['address_postcode'];
		$warehouseObj->address_street = empty($params['address_street']) ? '' : $params['address_street'];
		$warehouseObj->address_params = empty($params['address_params']) ? '' : json_encode($params['address_params']);
		$warehouseObj->comment = empty($params['comment']) ? '' : $params['comment'];
		$warehouseObj->update_time = GetControlData::getNowDateTime_str();
		
		if(isset($params['is_active'])){
			if(($warehouseObj->warehouse_id == 0) && ($params['is_active'] == 'N'))
				return self::output('', 1, '系统默认仓库不能关闭！');
			
			$warehouseObj->is_active = $params['is_active'];
		}
		
		if(isset($params['warehouse_zero'])){
			$warehouseObj->is_zero_inventory = $params['warehouse_zero'];
		}
		
		if(empty($warehouseObj->addi_info)){
			$addi_info = array('address_nation'=>$params['address_nation']);
			$warehouseObj->addi_info = json_encode($addi_info);
		}else{
			$addi_info = json_decode($warehouseObj->addi_info,true);
			$addi_info['address_nation'] =$params['address_nation'];
			$warehouseObj->addi_info = json_encode($addi_info);
		}
		$countryModel = WarehouseHelper::getCountryInfoByName($params['address_nation']);
		if($countryModel==null){
			$warehouseObj->address_nation = '';
		}else{
			$warehouseObj->address_nation = $countryModel->country_code;
		}
		
		if($warehouseObj->save(false)){
			if($is_create){
				//写入操作日志
				UserHelper::insertUserOperationLog('inventory', '新增仓库, 仓库名: '.$warehouseObj->name);
			}
			
			return self::output($warehouseObj->warehouse_id, 0, '保存成功.');
		}else{
			return self::output('', 1, '保存失败.');
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 仓库地匹配项
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/02/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWarehouseMatchRuleArr(){
		$rules = [
			'items_location_country' => '物品所在国家',
			'items_location_provinces' => '物品所在地区',
// 			'items_location_city' => '物品所在城市',
			'receiving_country' => '收件人国家',
			'receiving_provinces' => '收件人州/省份',
			'receiving_city' => '收件人城市',
			'skus' => 'SKU',
			'sources' => '平台、账号、站点',
			'freight_amount' => '买家支付运费',
		];
		
		return $rules;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 仓库匹配规则信息存在分页数据
	 +---------------------------------------------------------------------------------------------
	 * @param	$defaultPageSize	默认显示15条记录
	 * @param	$params			用于添加额外的查询功能	可能的值:warehouse_id查询对应仓库ID存在几个匹配规则
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/01/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getAllWarehouseMatchingRuleList($defaultPageSize=15, $params = array()){
		$conn=\Yii::$app->subdb;
		
		$queryTmp = new Query;
		$queryTmp->select("a.id,a.rule_name,a.is_active,a.warehouse_id,b.name,b.is_oversea,b.address_street")
			->from("warehouse_matching_rule a")
			->leftJoin("wh_warehouse b", "b.warehouse_id = a.warehouse_id");
		
		if(isset($params['warehouse_id']))
			$queryTmp->andWhere(['a.warehouse_id'=>$params['warehouse_id']]);
		
		if(isset($params['warehouse_type']))
			$queryTmp->andWhere(['b.is_oversea'=>$params['warehouse_type']]);
		
		if(isset($params['warehouse_state']))
			$queryTmp->andWhere(['a.is_active'=>$params['warehouse_state']]);
		
		if(isset($params['warehouse_is_active']))
			$queryTmp->andWhere(['b.is_active'=>$params['warehouse_is_active']]);
		
		$DataCount = $queryTmp->count("1", $conn);
		
		$pagination = new Pagination([
				'defaultPageSize' => 15,
				'pageSize' => $defaultPageSize,
				'totalCount' => $DataCount,
				'pageSizeLimit'=>[15,200],//每页显示条数范围
				]);
		
		$list['pagination'] = $pagination;
		
		$sort = 'is_active';
		$order = 'desc';
		$sort_arr = array('is_active'=>'is_active desc','priority'=>'priority asc','warehouse_id'=>'a.warehouse_id asc','rule_name'=>'rule_name asc');
		unset($sort_arr[$sort]);
		$str = $sort.' '.$order.','.implode(',', $sort_arr);
		
		$queryTmp->orderBy($str);
		$queryTmp->limit($pagination->limit);
		$queryTmp->offset($pagination->offset);
		
		$list['data'] = $queryTmp->createCommand($conn)->queryAll();
		
		return $list;
	}
	
	/**
	 * 获取匹配现时的优先级
	 * @param
	 * @return 1
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/02/02				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getWarehouseMaxMatchingRulePriority(){
		$matchingRulePriority = WarehouseMatchingRule::find()->max('priority');
	
		if($matchingRulePriority == ''){
			$matchingRulePriority = 0;
		}
	
		return ++$matchingRulePriority;
	}
	
	/**
	 * 第三方仓库excel模式保存
	 * @param
	 * @return 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/05				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function saveCustomWarehouseExcelFormat($warehouse_id, $params){
		$warehouseCustom = Warehouse::find()->where(['warehouse_id'=>$warehouse_id])->one();
	
		if($warehouseCustom == null){
			return self::output(array(), 1, '非法操作，该物流商未被启用过.');
		}
	
		$warehouseCustom->excel_mode = $params['excel_mode'];
		$warehouseCustom->excel_format = json_encode($params['excel_format']);
	
		if($warehouseCustom->save()){
			return self::output(array(), 0, '保存成功.');
		}else{
			return self::output(array(), 1, '保存失败.');
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 匹配warehouse，根据参数 $order
	 * 用户可以在仓库匹配规则设置,然后返回匹配成功的仓库ID，匹配不成功返回-1
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * $order              eagle\modules\order\models\OdOrder;
	 +---------------------------------------------------------------------------------------------
	 * @return	1 int
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function matchOrdersWarehouse($order){
		$warehouseArr = self::getAllWarehouseInfo(true, array());
		
		$activeWarehouseArr = array();
		
		//当没有使用仓库时直接返回默认仓库
		if(empty($warehouseArr)){
			return 0;
		}
		
		//当只有一个仓库时直接返回默认仓库
		if(count($warehouseArr) == 1){
			return 0;
		}
		
		foreach ($warehouseArr as $warehouseKey => $warehouseVal){
			$activeWarehouseArr[] = $warehouseKey;
		}
		
		$ruleParams['warehouse_arr'] = $activeWarehouseArr;
		$ruleOrderParams['priority'] = 'asc';
		$warehouseRuleInfoArr = self::getAllWarehouseMatchingRuleInfo(1, $ruleParams, $ruleOrderParams);
		
		//没有匹配规则是默认默认仓
		if(empty($warehouseRuleInfoArr)){
			return 0;
		}
		
		foreach ($warehouseRuleInfoArr as $warehouseRuleInfo){
			$tmpRules = json_decode($warehouseRuleInfo['rules'], true);
			
			$tmpRulesTypes = $tmpRules['rules'];
			
			if (is_array($tmpRulesTypes) && (count($tmpRulesTypes) > 0)){
				$tmpIsSucceed = true;
				
				foreach ($tmpRulesTypes as $tmpRulesType){
// 					print_r($warehouseRuleInfo['warehouse_id'].":".$tmpRulesType."\n");
					
					if($tmpRulesType == 'sources'){
						//平台来源
						if(isset($tmpRules['sources']['source'])){
							if (!in_array($order->order_source, $tmpRules['sources']['source'])){
// 								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule->rule_name.':订单来源'.$order->order_source."未满足条件",\Yii::$app->user->identity->getFullName());
								$tmpIsSucceed = false;
							}
						}
						
						//站点
						if(isset($tmpRules['sources']['site'])){
							$site = $tmpRules['sources']['site'];
							if (isset($site[$order->order_source]) && count($site[$order->order_source])>0 ){
								if (!in_array($order->order_source_site_id, $site[$order->order_source])){
// 									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule->rule_name.':站点'.$order->order_source_site_id."未满足条件",\Yii::$app->user->identity->getFullName());
									$tmpIsSucceed = false;
								}
							}else{
// 								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule->rule_name.':站点'.$order->order_source_site_id."未满足条件",\Yii::$app->user->identity->getFullName());
								$tmpIsSucceed = false;
							}
						}
						
						//账号
						if(isset($tmpRules['sources']['selleruserid'])){
							$selleruserid = $tmpRules['sources']['selleruserid'];
							if (isset($selleruserid[$order->order_source]) && count($selleruserid[$order->order_source])>0 ){
								if (!in_array($order->selleruserid, $selleruserid[$order->order_source])){
// 									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule->rule_name.':卖家账号'.$order->selleruserid."未满足条件",\Yii::$app->user->identity->getFullName());
									$tmpIsSucceed = false;
								}
							}else {
// 								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule->rule_name.':卖家账号'.$order->selleruserid."未满足条件",\Yii::$app->user->identity->getFullName());
								$tmpIsSucceed = false;
							}
						}
					}else if($tmpRulesType == 'receiving_country'){
						if (!in_array($order->consignee_country_code, $tmpRules['receiving_country'])){
// 							OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule->rule_name.':收件国家'.$order->consignee_country_code."未满足条件",\Yii::$app->user->identity->getFullName());
							$tmpIsSucceed = false;
						}
					}else if($tmpRulesType == 'receiving_provinces'){
						if(!empty($tmpRules['receiving_provinces'])){
							$tmp_receiving_provinces = explode(",",$tmpRules['receiving_provinces']);
							
							if(is_array($tmp_receiving_provinces)){
								//将数组里面的英文转为大写
								$tmp_receiving_provinces = array_flip(array_change_key_case(array_flip($tmp_receiving_provinces),CASE_UPPER));
								
								if (!in_array(strtoupper($order->consignee_province), $tmp_receiving_provinces ,false)){
// 									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule->rule_name.':收件人州/省份'.$order->consignee_country_code."未满足条件",\Yii::$app->user->identity->getFullName());
									$tmpIsSucceed = false;
								}
							}
						}
					}else if($tmpRulesType == 'receiving_city'){
						if(!empty($tmpRules['receiving_city'])){
							$tmp_receiving_city = explode(",",$tmpRules['receiving_city']);
							
							if(is_array($tmp_receiving_city)){
								$tmp_receiving_city = array_flip(array_change_key_case(array_flip($tmp_receiving_city),CASE_UPPER));
								
								if (!in_array(strtoupper($order->consignee_city), $tmp_receiving_city)){
// 									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule->rule_name.':收件人城市'.$order->consignee_country_code."未满足条件",\Yii::$app->user->identity->getFullName());
									$tmpIsSucceed = false;
								}
							}
						}
					}else if($tmpRulesType == 'freight_amount'){
						$freight_min_max = $tmpRules['freight_amount'];
						if (!($order->shipping_cost >= $freight_min_max['min'] && $order->shipping_cost < $freight_min_max['max'])){
// 							OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule->rule_name.':买家支付运费'.$order->shipping_cost."未满足条件",\Yii::$app->user->identity->getFullName());
							$tmpIsSucceed = false;
						}
					}else if($tmpRulesType == 'skus'){
						if(!empty($tmpRules['skus'])){
							$tmp_skus = explode(",",$tmpRules['skus']);
							
							if(isset($tmpRules['skus']) && count($tmp_skus)>0){
								$tmp_skus = array_flip(array_change_key_case(array_flip($tmp_skus),CASE_UPPER));
								
								foreach ($order->items as $item){
									if(!in_array(strtoupper($item->sku), $tmp_skus)){
// 										OperationLogHelper::log('order',$order->order_id,'分配仓库',$rule->rule_name.':'.$typeErr."未满足条件",\Yii::$app->user->identity->getFullName());
										$tmpIsSucceed = false;
									}
								}
							}
						}
					}else if($order->order_source == 'ebay'){
						$sourceItemids = array();
						
						foreach ($order->items as $item){
							$sourceItemids[$item->order_source_itemid] = $item->order_source_itemid;
						}
						
						if(!empty($sourceItemids)){
							if($tmpRulesType == 'items_location_country'){
								if(isset($tmpRules['items_location_country']) && count($tmpRules['items_location_country'])>0){
									$tmpIsSucceed = self::ebayItemsMatchWarehouse($order, $sourceItemids, 'items_location_country', $tmpRules['items_location_country']);
								}
							}else if($tmpRulesType == 'items_location_provinces'){
								if(!empty($tmpRules['items_location_provinces'])){
									$tmp_items_location_provinces = explode(",",$tmpRules['items_location_provinces']);
									if(isset($tmpRules['items_location_provinces']) && count($tmp_items_location_provinces)>0){
										$tmp_items_location_provinces = array_flip(array_change_key_case(array_flip($tmp_items_location_provinces),CASE_UPPER));
										$tmpIsSucceed = self::ebayItemsMatchWarehouse($order, $sourceItemids, 'items_location_provinces', $tmp_items_location_provinces);
									}
								}
							}
						}
					}
					
					if($tmpIsSucceed == false) break;
				}
				
				if($tmpIsSucceed == true)
					return $warehouseRuleInfo['warehouse_id'];
			}
		}
		
		//匹配不成功是返回-1
		return -1;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay的物品所在地的匹配规则		除了这个helper用到之外，CarrierOpenHelper也需要
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * $order              	eagle\modules\order\models\OdOrder;
	 * $sourceItemids		ebay来源order_source_itemid
	 * $type				items_location_country / items_location_provinces
	 * $typeParams			
	 +---------------------------------------------------------------------------------------------
	 * @return	true / false
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function ebayItemsMatchWarehouse($order, $sourceItemids, $type, $typeParams){
		$tmpEbayItemDetails = EbayItemDetail::find()->select(['itemid','location','country'])->where(['itemid'=>$sourceItemids])->asArray()->all();
		
		$ebayItemDetails = array();
		foreach ($tmpEbayItemDetails as $tmpEbayItemDetail){
			$ebayItemDetails[$tmpEbayItemDetail['itemid']] = $tmpEbayItemDetail;
		}
		
		$typeErr = '';
		if($type == 'items_location_country'){
			$typeErr = 'ebay物品所在国家';
		}else if($type == 'items_location_provinces'){
			$typeErr = 'ebay物品所在地区';
		}
		
		if(count($ebayItemDetails) != count($sourceItemids)){
// 			OperationLogHelper::log('order',$order->order_id,'分配仓库',$rule->rule_name.':'.$typeErr."未满足条件",\Yii::$app->user->identity->getFullName());
			return false;
		}
		
		foreach ($order->items as $item){
			if(isset($ebayItemDetails[$item->order_source_itemid])){
				if($type == 'items_location_country'){
					if(!in_array($ebayItemDetails[$item->order_source_itemid]['country'], $typeParams)){
// 						OperationLogHelper::log('order',$order->order_id,'分配仓库',$rule->rule_name.':'.$typeErr."未满足条件",\Yii::$app->user->identity->getFullName());
						return false;
					}
				}else if($type == 'items_location_provinces'){
					if(!in_array(strtoupper($ebayItemDetails[$item->order_source_itemid]['location']), $typeParams)){
// 						OperationLogHelper::log('order',$order->order_id,'分配仓库',$rule->rule_name.':'.$typeErr."未满足条件",\Yii::$app->user->identity->getFullName());
						return false;
					}
				}
			}
		}
		
		return true;
	}
	
	/*
	 * 对子类公开的方法 开始======================
	*/
	protected static function output($data, $code = 0, $msg = '') {
		$output = ['response'=>['code'=>$code, 'msg'=>$msg, 'data'=>$data]];
		return $output;
	}
	
	/**
	 * 将序列化的数据反序列化输出
	 *
	 * @param	$dataArr	需要转换的数组
	 * @param	$fieldArr	需要转换的字段
	 * @return Array $dataArr
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/12/02				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	protected static function StrToUnserialize(&$dataArr, $fieldArr){
		foreach ($dataArr as &$dataOne){
			foreach ($fieldArr as $fieldOne){
				$dataOne[$fieldOne] = unserialize($dataOne[$fieldOne]);
			}
		}
	}
	/*
	 * 对子类公开的方法 结束======================
	*/
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取Inventory的Listing
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $keyword			查询的text(sku)
	 * @param     $pageSize         每页显示数量，默认是50
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		lgw 		2019/9/13				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function ExportExcel_listProductStockageData($keyword='',$sort,$order,$page=1, $pageSize = 50 )
	{
		$connection = Yii::$app->get('subdb');
		$sql = "SELECT s.*, p.name, type,status ,prod_name_ch,brand_id,is_has_tag,photo_primary,w.name as wname
				from wh_product_stock s  left join wh_warehouse w on s.warehouse_id=w.warehouse_id, pd_product p 
				where s.sku=p.sku and p.type <>'C' ";
		
		$condition='';
		//如果keyword不为空，用户录入了模糊查询
		if(!empty($keyword)){
			//去掉keyword的引号。免除SQL注入
			$keyword = str_replace("'","",$keyword);
			$keyword = str_replace('"',"",$keyword);
			
			$prod_stock_id_arr = explode(',', $keyword);
			$keyword='';
			foreach ($prod_stock_id_arr as $prod_stock_id_arrone){
				$keyword.='\''.$prod_stock_id_arrone.'\',';
			}
			$keyword=substr($keyword,0,-1);
				
			$condition .= " and (s.prod_stock_id in ($keyword))";
		}
	
		$data ['condition'] = $condition;
	
		//Pagination 会自动获取Post或者get里面的page number，自动计算offset
		$command = $connection->createCommand($sql.$condition);
		$pagination = new Pagination([
				'pageSize' => $pageSize,
				'totalCount' => count($command->queryAll()),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$data['pagination'] = $pagination;
	
		$sortStr = " order by $sort $order ";
	
		$offset = " limit ". $pagination->offset." , ". $pagination->limit;

		$command = $connection->createCommand($sql.$condition.$sortStr.$offset);
		$rows =  $command->queryAll();
	
		if(count($rows)<1){
			$data['data']=array();
		}
		foreach ($rows as &$row) {
								
			$data['data'][]=$row;
		}
	
		return $data;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 *	 仓库导出商品
	 +---------------------------------------------------------------------------------------------
	 * @param      $data 导出类型
	 * 			   $type 排队导出还是直接导出，true为直接，false为排队
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/1/4				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function ExportExcelAll($data, $type = false){
		$rtn['success'] = 1;
		$rtn['message'] = '';
		try{
			$journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ ,array($data));

			$product_ids=$data;

			//     	AppTrackerApiHelper::actionLog("purchase", "/purchase/purchase/export-execl");
			$sort=isset($product_ids['sort'])?$product_ids['sort']:'';
			$order = isset($product_ids['order'])?$product_ids['order']:'';
			$product_type=isset($product_ids['product_type'])?$product_ids['product_type']:'';
			$keyword=isset($product_ids['keyword'])?$product_ids['keyword']:'';
			$pageSize = isset($product_ids['count'])?$product_ids['count']:'70000';
			$page = -1;

			if(!isset($product_ids['type']))
				$params['prod_stock_id']=$product_ids;
			else
				$params=isset($product_ids['params'])?$product_ids['params']:'';

			if(!empty($product_type)){
				$params['search_keyword']=$keyword;
				$data = InventoryHelper::listBundleProductStockageData($page, $pageSize, $sort, $order, $params);
			}else
				$data = InventoryHelper::listProductStockageData( $keyword,$params, $sort , $order ,$page, $pageSize );

			$data=$data['data'];	
			$items_arr = ['sku'=>'sku','name'=>'产品名称','prod_name_ch'=>'中文配货名称','type'=>'产品类型','warehouse_id'=>'仓库名称','location_grid'=>'货架位置','qty_in_stock'=>'库存数量','qty_purchased_coming'=>'在途数量','qty_ordered'=>'配货待发','average_price'=>'采购价','stock_total'=>'总价','safety_stock'=>'安全库存'];
			$keys = array_keys($items_arr);
			
			if(!isset($product_ids['type'])){
				$data_tmp=$data;
				unset($data);
				$product_ids_arr=explode(',', $product_ids);
				foreach ($product_ids_arr as $productidone){
					foreach ($data_tmp as $index=>$row){
						if($row['prod_stock_id']==$productidone){
							$data[]=$row;
							break;
						}
					}
				}
			}
			
			$ProductType= ProductHelper::getProductType();	  
			WarehouseHelper::createDefaultWarehouseIfNotExists();
			$query = Warehouse::find();
			 
			//读取是否显示海外仓仓库
			$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
			if(empty($is_show))
				$is_show = 0;
			//不显示海外仓仓库
			if($is_show == 0)
			{
				$query = $query->andWhere(['is_oversea' => '0']);
			}
				
			$warehouseData = $query
			->asArray()
			->all();
			 
			$warehouse = array();
			foreach($warehouseData as $w){
				$warehouse[$w['warehouse_id']]=$w['name'];
			}

			$excel_data = [];
			//print_r($purchaseDatas);exit();
			foreach ($data as $index=>$row){
				$tmp=[];
				foreach ($keys as $key){
					if(isset($row[$key])){
						if(in_array($key,['sku']) && is_numeric($row[$key]))
							$tmp[$key]=' '.$row[$key];
						else
							$tmp[$key]=(string)$row[$key];
						 
						if($key=='warehouse_id')
							$tmp[$key]=(string)$warehouse[$row[$key]];
						
						if($key=='type')
							$tmp[$key]=(string)$ProductType[$row[$key]];
					}
				}
				$excel_data[$index] = $tmp;
				unset($tmp);
			}
			unset($product_ids);

			$rtn=ExcelHelper::exportToExcel($excel_data, $items_arr, 'inventory_'.date('Y-m-dHis',time()).".xls", [], $type);

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
	 +---------------------------------------------------------------------------------------------
	 *	 更新特定客户待发货数量
	 +---------------------------------------------------------------------------------------------
	 * @param     
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/05/10				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function UpdateUserOrdered($times = 12){
		$uid = \Yii::$app->subdb->getCurrentPuid();
		
		//12小时内不自动刷新
		$last_update_time = 0;
		$redis_key_lv1 = 'UpdateUserOrdered';
		$redis_key_lv2 = 'UpdateUserOrdered_'.$uid;
		$warn_record = RedisHelper::RedisGet($redis_key_lv1, $redis_key_lv2);
		if(!empty($warn_record)){
			$redis_val = json_decode($warn_record,true);
			if(!empty($redis_val['update_time'])){
				$last_update_time = $redis_val['update_time'];
			}
		}
		if(!empty($last_update_time) && $last_update_time + 3600 * $times > time()){
			return;
		}
		//当前更新时间存放到redis
		$redis_val['update_time'] = time();
		$ren = RedisHelper::RedisSet($redis_key_lv1, $redis_key_lv2, json_encode($redis_val));
		if(empty($ren)){
			RedisHelper::RedisSet($redis_key_lv1, $redis_key_lv2, json_encode($redis_val));
		}
		
		//清除所有待发货数量
		$sql = "update wh_product_stock set qty_ordered=0 ";
		$command = Yii::$app->get('subdb')->createCommand($sql);
		$command->execute();
			
		//只计算两个月内的订单
		$sql = "select root_sku from od_order_item_v2 where order_id in (
	            select order_id from od_order_v2 where create_time>".(time() - 3600 * 24 * 60)." and ((order_status>=200 and order_status<500) or order_status=602)  and shipping_status<>2 and (order_relation='normal' or order_relation='sm' or order_relation='ss' or order_relation='fs') and (order_source!='aliexpress' or order_source_status!='RISK_CONTROL'))
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
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	删除某些库存信息
	 +---------------------------------------------------------------------------------------------
	 * @param $stock_id_arr       array()          库存列表id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/07/03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function DeleteStock($stock_id_arr){
		$ret['success'] = 1;
		$ret['msg'] = '';
		$stock_change_id_list = array();
		
		try{
			$uid = \Yii::$app->subdb->getCurrentPuid();
			
			$stocklist = ProductStock::find()->where(['prod_stock_id' => $stock_id_arr])->asArray()->all();
			//整理信息
			$err_sku = '';
			$warehouse_stock = array();
			foreach ($stocklist as $stock){
				$warehouse_stock[$stock['warehouse_id']][] = $stock;
				
				if(!empty($stock['qty_purchased_coming']) || !empty($stock['qty_ordered'])){
					$err_sku .= $stock['sku'].', ';
				}
			}
			if($err_sku != ''){
				$ret['success'] = 0;
				$ret['msg'] = $err_sku.' 等SKU存在在途数量或者配货待发，不可删除！';
				return $ret;
			}
			
			\Yii::info((__FUNCTION__).',puid:'.$uid.',data:'.json_encode($stocklist), "file");
			
			$transaction = Yii::$app->get('subdb')->beginTransaction ();
			foreach ($warehouse_stock as $warehouse_id => $stocks){
				/*************插入出入库表头**/
				//插入表头
				$i = 1;
				$stock_change_id = 'DeleteStock_'.$stocks[0]['prod_stock_id'];
				while (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
					$i++;
					$stock_change_id = 'DeleteStock_'.$stocks[0]['prod_stock_id'].'_'.$i;
				}
				
				$stock_change_data = array(
						'stock_change_id' => $stock_change_id,
						'warehouse_id' => 	$warehouse_id,
						'comment' => "(删除库存列表时自动生成)",
						'create_time'=> TimeUtil::getNow(),
				);
				$insertDeleteStockRecord = self::insertDeleteStockRecord($stock_change_data);
				
				//if insert stock take record failed , return
				if (!$insertDeleteStockRecord['success']){
					$transaction->rollBack();
					$ret['success'] = 0;
					$ret['msg'] = $insertDeleteStockRecord['message'];
					return $ret;
				}
				
				//查询出入库变化记录
				$count = 0;
				foreach ($stocks as $stock){
					/*************插入出入库明细信息**/
					$insertDetail = self::insertStockChangeDetailRecord($stock_change_data, $stock['sku'], $stock['qty_in_stock']);
					if (!$insertDetail['success']){
						$transaction->rollBack();
						$ret['success']=false;
						$ret['message'].=$insertDetail['message'];
						return $ret;
					}
					
					/*************清除库存 **/
					$modifyStockage =self::modifyProductStockage($stock['sku'], $warehouse_id, 0, -$stock['qty_in_stock'], 0, 0, 0, '');
					if (!$modifyStockage['success']){
						$transaction->rollBack();
						$ret['success']=false;
						$ret['message'].=$modifyStockage['message'];
						return $ret;
					}
					
					if(!empty($stock['qty_in_stock'])){
						$count++;
						
						if(!in_array($stock_change_id, $stock_change_id_list)){
							$stock_change_id_list[] = $stock_change_id;
						}
					}
					
					/*************删除库存列表对应信息 **/
					ProductStock::deleteAll(['prod_stock_id' => $stock['prod_stock_id']]);
				}
				
				if($count == 0){
					//删除出入库记录表头
					StockChange::deleteAll(['stock_change_id' => $stock_change_id]);
				}
			}
		}
		catch(\Exception $ex){
			$ret['success'] = 0;
			$ret['msg'] = $ex->getMessage();
		}
		
		if(!empty($stock_change_id_list)){
			$edit_log = implode($stock_change_id_list, ', ');
			//写入操作日志
			UserHelper::insertUserOperationLog('inventory', '库存列表, 删除库存信息生成出入库记录: '.$edit_log);
		}
		
		if($ret['success']){
			$transaction->commit();
		}
		return $ret;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	保存单个库存信息
	 +---------------------------------------------------------------------------------------------
	 * @param $parma       array()
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/08/07				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function SaveOneStock($param){
		$edit_log = '';
		$key_id = '';
		try{
			$uid = \Yii::$app->subdb->getCurrentPuid();
			
			if(empty($param['show_time'])){
				return ['success' => false, 'msg' => '参数丢失，请重新打开！'];
			}
			
			$stock_qty = empty($param['stock_qty']) ? '0' : $param['stock_qty'];
			$stock_price = empty($param['stock_price']) ? '0' : $param['stock_price'];
			$safety_stock = empty($param['safety_stock']) ? '0' : $param['safety_stock'];
			
			//检测数据
			if(!is_numeric($stock_qty) || !is_numeric($safety_stock) || !is_numeric($stock_price)){
				return ['success' => false, 'msg' => '库存数 或 单价 或 安全库存，输入格式不正确！'];
			}
			$stock_qty = (int)$stock_qty;
			$safety_stock = (int)$safety_stock;
			
			\Yii::info((__FUNCTION__).',puid:'.$uid.',data:'.json_encode($param), "file");
			
		    $stock = ProductStock::findOne(['prod_stock_id' => $param['stock_id']]);
		    if(empty($stock)){
		    	return ['success' => false, 'msg' => '库存信息不存在！'];
		    }
		    //判断库存是否已变更
		    $change = Yii::$app->get('subdb')->createCommand("SELECT count(1) count FROM wh_stock_change left join wh_stock_change_detail on wh_stock_change.stock_change_id=wh_stock_change_detail.stock_change_id 
		    		where wh_stock_change.warehouse_id='".$stock->warehouse_id."' and wh_stock_change_detail.sku='".$stock->sku."' and UNIX_TIMESTAMP(wh_stock_change.create_time)>".$param['show_time'])->queryAll();
		    if(!empty($change) && !empty($change[0]['count'])){
		    	return ['success' => false, 'msg' => '库存信息已变更，不可保存，请重新打开！'];
		    }
		    
		    $transaction = Yii::$app->get('subdb')->beginTransaction ();
		    //判断库存是否有变化，有变化则生成出入库记录
		    if($stock_qty != $stock->qty_in_stock){
	    		/*************插入出入库表头**/
	    		$i = 1;
	    		$stock_change_id = 'UpdateStock_'.$stock->prod_stock_id;
	    		while (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
	    			$i++;
	    			$stock_change_id = 'UpdateStock_'.$stock->prod_stock_id.'_'.$i;
	    		}
	    	
	    		$stock_change_data = array(
	    				'stock_change_id' => $stock_change_id,
	    				'warehouse_id' => 	$stock->warehouse_id,
	    				'comment' => "(更新库存列表时自动生成)",
	    				'create_time'=> TimeUtil::getNow(),
	    		);
	    		$insertDeleteStockRecord = self::insertUpdateStockRecord($stock_change_data);
	    	
	    		//if insert stock take record failed , return
	    		if (!$insertDeleteStockRecord['success']){
	    			$transaction->rollBack();
	    			return ['success' => false, 'msg' => $insertDeleteStockRecord['message']];
	    		}
	    	
	    		
	   			/*************插入出入库明细信息**/
	   			$insertDetail = self::insertStockChangeDetailRecord($stock_change_data, $stock->sku, $stock_qty - $stock->qty_in_stock);
	   			if (!$insertDetail['success']){
	   				$transaction->rollBack();
	   				return ['success' => false, 'msg' => $insertDetail['message']];
	   			}
	   		}
	   		
	   		//判断是否编辑过数量、单价信息
	   		if($stock->qty_in_stock != $stock_qty){
	   			$edit_log .= '数量: '.$stock->qty_in_stock.' -> '.$stock_qty.', ';
	   		}
	   		if($stock->average_price != $stock_price){
	   			$edit_log .= '单价: '.$stock->average_price.' -> '.$stock_price.', ';
	   		}
	   		if($edit_log != ''){
	   			$key_id = $stock->prod_stock_id;
	   			//查询仓库名称
	   			$warehouse = Warehouse::findOne(['warehouse_id' => $stock->warehouse_id]);
	   			$edit_log = rtrim($edit_log, ', ');
	   			$edit_log = 'SKU: '.$stock->sku.', 仓库: '.$warehouse['name'].', '.$edit_log;
	   		}
	   		
	   		/*************更新库存信息**/
	   		$stock->qty_in_stock = $stock_qty;
	   		$stock->location_grid = $param['location_grid'];
	   		$stock->average_price = $stock_price;
	   		$stock->safety_stock = $safety_stock;
	   		$stock->update_time = TimeUtil::getNow();
	   		if (!$stock->save()){
	   			$transaction->rollBack();
	   			return ['success' => false, 'msg' => '更新失败！'];
	   		}
		}
   		catch(\Exception $ex){
   			return ['success' => false, 'msg' => $ex->getMessage()];
   		}
   		
   		$transaction->commit();
   		
   		if(!emptY($edit_log)){
   			//写入操作日志
   			UserHelper::insertUserOperationLog('inventory', '库存列表, 编辑, '.$edit_log, null, $key_id);
   		}
   		
	    return ['success' => true, 'msg' => ''];
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	批量保存库存信息
	 +---------------------------------------------------------------------------------------------
	 * @param $parma       array()
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/08/07				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function BathSaveStock($data){
		$transaction = Yii::$app->get('subdb')->beginTransaction ();
		$edit_log =  '';
		try{
		    $ret['success'] = true;
		    $ret['msg'] = [];
			$uid = \Yii::$app->subdb->getCurrentPuid();
			
			if(empty($data['show_time'])){
				return ['success' => false, 'msg' => '参数丢失，请重新打开！'];
			}
			$show_time = $data['show_time'];
			unset($data['show_time']);
			
			//整理信息
        	$stock_info = array();
        	foreach($data as $key => $arr){
    	        foreach($arr as $num => $val){
    	        	$stock_info[$num][$key] = $val;
    	        }
        	}
			
        	$stock_change_data_list = array();
        	foreach ($stock_info as $key => $info){
        	    $prod_stock_id = empty($info['prod_stock_id']) ? '' : $info['prod_stock_id'];
				$stock_qty = empty($info['stock_qty']) ? '0' : $info['stock_qty'];
				$stock_price = empty($info['stock_price']) ? '0' : $info['stock_price'];
				$safety_stock = empty($info['safety_stock']) ? '0' : $info['safety_stock'];
					
				//检测数据
				if(!is_numeric($stock_qty) || !is_numeric($safety_stock) || !is_numeric($stock_price)){
					$ret['success'] = false;
					$ret['msg'][$key + 1][] = '库存数 或 单价 或 安全库存，输入格式不正确！<br>';
					continue;
				}
				$stock_qty = (int)$stock_qty;
				$safety_stock = (int)$safety_stock;
				
				\Yii::info((__FUNCTION__).',puid:'.$uid.',data:'.json_encode($info), "file");
					
				$stock = ProductStock::findOne(['prod_stock_id' => $prod_stock_id]);
				if(!empty($stock)){
					//判断库存是否已变更
					$change = Yii::$app->get('subdb')->createCommand("SELECT count(1) count FROM wh_stock_change left join wh_stock_change_detail on wh_stock_change.stock_change_id=wh_stock_change_detail.stock_change_id
		    			where wh_stock_change.warehouse_id='".$stock->warehouse_id."' and wh_stock_change_detail.sku='".$stock->sku."' and UNIX_TIMESTAMP(wh_stock_change.create_time)>".$show_time)->queryAll();
					if(!empty($change) && !empty($change[0]['count'])){
						$ret['success'] = false;
						$ret['msg'][$key + 1][] = '库存信息已变更，不可保存，请重新打开！<br>';
						break;
					}
    				//判断库存是否有变化，有变化则生成出入库记录
    				if($stock_qty != $stock->qty_in_stock){
    				    if(empty($stock_change_data_list[$stock->warehouse_id])){
        					/*************插入出入库表头**/
        					$i = 1;
        					$stock_change_id = 'UpdateStock_'.$stock->prod_stock_id;
        					while (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
        						$i++;
        						$stock_change_id = 'UpdateStock_'.$stock->prod_stock_id.'_'.$i;
        					}
        		
        					$stock_change_data = array(
        							'stock_change_id' => $stock_change_id,
        							'warehouse_id' => 	$stock->warehouse_id,
        							'comment' => "(更新库存列表时自动生成)",
        							'create_time'=> TimeUtil::getNow(),
        					);
        					$insertDeleteStockRecord = self::insertUpdateStockRecord($stock_change_data);
        		
        					//if insert stock take record failed , return
        					if (!$insertDeleteStockRecord['success']){
        						$ret['success'] = false;
        						$ret['msg'][$key + 1][] = $insertDeleteStockRecord['message'].'<br>';
        						continue;
        					}
        					
        					$stock_change_data_list[$stock->warehouse_id] = $stock_change_data;
    				    }
    		
    			   
    					/*************插入出入库明细信息**/
    					$insertDetail = self::insertStockChangeDetailRecord($stock_change_data_list[$stock->warehouse_id], $stock->sku, $stock_qty - $stock->qty_in_stock);
    					if (!$insertDetail['success']){
    						$ret['success'] = false;
    						$ret['msg'][$key + 1][] = $insertDetail['message'].'<br>';
    						continue;
    					}
    				}
    				
    				//判断是否编辑过数量、单价信息
    				$log = '';
    				if($stock->qty_in_stock != $stock_qty){
    					$log .= '数量: '.$stock->qty_in_stock.' -> '.$stock_qty.', ';
    				}
    				if($stock->average_price != $stock_price){
    					$log .= '单价: '.$stock->average_price.' -> '.$stock_price.', ';
    				}
    				if($log != ''){
    					$log = rtrim($log, ', ');
    					$edit_log .= $stock->sku.', '.$log.'; ';
    				}
    		
    				/*************更新库存信息**/
    				$stock->qty_in_stock = $stock_qty;
    				$stock->location_grid = $info['location_grid'];
    				$stock->average_price = $stock_price;
    				$stock->safety_stock = $safety_stock;
    				$stock->update_time = TimeUtil::getNow();
    				if (!$stock->save()){
    					$ret['success'] = false;
    					$ret['msg'][$key + 1][] = '更新失败！<br>';
    					continue;
    				}
				}
        	}
		}
		catch(\Exception $ex){
		    $ret['success'] = false;
		    $ret['error'] = $ex->getMessage();
		}
		
		if(!$ret['success']){
		    $transaction->rollBack();
		}
		else{
		    $transaction->commit();
		    
		    if(!empty($edit_log)){
		    	//写入操作日志
		    	UserHelper::insertUserOperationLog('inventory', '库存列表, 批量编辑, '.$edit_log);
		    }
		}
		
		return $ret;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	获取采购单对应的入库信息
	 +---------------------------------------------------------------------------------------------
	 * @param  $purchase_id   采购Id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date		note
	 * @author		lrq		2017/9/22	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function GetPurchaseInStockInfo($purchase_id = ''){
		$data = array();
		$stock_change_list = StockChange::find()->where(['reason' => 101, 'source_id' => $purchase_id])->asArray()->all();
		
		if(!empty($stock_change_list)){
			$stock_change_ids = array();
			foreach($stock_change_list as $stock_change){
				$stock_change_ids[] = $stock_change['stock_change_id'];
				$data[$stock_change['stock_change_id']] = $stock_change;
			}
			
			$stock_change_detail_list = StockChangeDetail::find()->where(['stock_change_id' => $stock_change_ids])->asArray()->all();
			foreach($stock_change_detail_list as $stock_change_detail){
				if(!empty($data[$stock_change_detail['stock_change_id']])){
					$data[$stock_change_detail['stock_change_id']]['item'][] = $stock_change_detail;
					$data[$stock_change_detail['stock_change_id']]['sku_count'] = empty($data[$stock_change_detail['stock_change_id']]['sku_count']) ? 1 : $data[$stock_change_detail['stock_change_id']]['sku_count'] + 1;
					$data[$stock_change_detail['stock_change_id']]['qty_count'] = empty($data[$stock_change_detail['stock_change_id']]['qty_count']) ? $stock_change_detail['qty'] : $data[$stock_change_detail['stock_change_id']]['qty_count'] + $stock_change_detail['qty'];
				}
			}
		}
		
		return $data;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	仓库调拨列表信息
	 +---------------------------------------------------------------------------------------------
	 * @param  
	 +---------------------------------------------------------------------------------------------
	 * log			name	date		note
	 * @author		lrq		2017/12/14	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listStockAllocationData($page, $rows, $sort, $order, $queryString){
		$stockAllocation = StockAllocation::find();
	
		if(!empty($queryString)) {
			foreach($queryString as $k => $v) {
				if ($k=='keyword'){
					$stockAllocation->andWhere("comment like '%$v%' or stock_allocatione_id like '%$v%'");
				}elseif($k=='date_from') {
					$stockAllocation->andWhere("create_time >= '".date("Y-m-d", strtotime($v))."'");
				}elseif($k=='date_to') {
					$stockAllocation->andWhere("create_time <= '".date("Y-m-d", strtotime($v) + 3600 * 24)."'");
				}else
					$stockAllocation->andWhere("$k = '$v'");
			}
		}
	
		//读取是否显示海外仓仓库
		$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
		if(empty($is_show))
			$is_show = 0;
		//不显示海外仓仓库
		if($is_show == 0){
			$stockAllocation->andWhere('in_warehouse_id in (select warehouse_id from wh_warehouse where is_oversea=0) and out_warehouse_id in (select warehouse_id from wh_warehouse where is_oversea=0)');
		}
	
		$pagination = new Pagination([
				'pageSize' => $rows,
				'totalCount' => count($stockAllocation->all()),
				]);
		$result['pagination'] = $pagination;
	
		$queryRows = $stockAllocation
			->limit($rows)
			->offset( ($page-1) * $rows )
			->orderBy("$sort $order")
			->asArray()
			->all();
	
		$result['total'] = count($queryRows);
		$result['datas'] = $queryRows;
		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	仓库调拨明细
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * log			name	date		note
	 * @author		lrq		2017/12/14	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getStockAllocationDetail($allocatione_id){
		$sql = "SELECT a.*, p.photo_primary, p.name
		FROM wh_stock_allocation_detail a , pd_product p where p.sku=a.sku and allocatione_id='$allocatione_id'";
		$result =Yii::$app->get('subdb')
		->createCommand($sql)
		->queryAll();
		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	查询仓库调拨最新单号
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * log			name	date		note
	 * @author		lrq		2017/12/14	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getStockAllocationId(){
		$sequenceId = 'DB';
		$query = StockAllocation::find()->select("stock_allocatione_id")->where('stock_allocatione_id  REGEXP \'^'.$sequenceId.'[0-9a-zA-Z]+$\' ');
		$query->orderBy("create_time DESC");
		$last_auto_order = $query->one();
		if($last_auto_order==null){
			$sequenceId=$sequenceId."000001";
		}else{
			$last_auto_order = $last_auto_order->stock_allocatione_id;
			$orderNum = substr($last_auto_order, strlen($sequenceId));
			if(empty($orderNum)) $orderNum=0;
			$orderNum = intval($orderNum);
			$orderNum_new = $orderNum+1;
	
			if ($orderNum_new<10) 	$sequenceId=$sequenceId."00000".$orderNum_new;
			else if ($orderNum_new<100) 	$sequenceId=$sequenceId."0000".$orderNum_new;
			else if ($orderNum_new<1000) 	$sequenceId=$sequenceId."000".$orderNum_new;
			else if ($orderNum_new<10000) 	$sequenceId=$sequenceId."00".$orderNum_new;
			else if ($orderNum_new<100000) 	$sequenceId=$sequenceId."0".$$orderNum_new;
			else $sequenceId=$sequenceId.$orderNum_new;
		}
		return $sequenceId;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	仓库调拨保存，并库存处理
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * log			name	date		note
	 * @author		lrq		2017/12/14	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function insertStockAllocation($data){
		$rtn['success']=true;
		$rtn['msg']='';
		$stock_change_id = '';
		//判断仓库是否选择正确
		if (!isset($data['in_warehouse_id']) || Warehouse::find()->where(['warehouse_id'=>$data['in_warehouse_id']])->One() == null ){
			return ['success' => false, 'msg' => '请选择调入仓'];
		}
		if (!isset($data['out_warehouse_id']) || Warehouse::find()->where(['warehouse_id'=>$data['out_warehouse_id']])->One() == null ){
			return ['success' => false, 'msg' => '请选择调出仓'];
		}
		if($data['in_warehouse_id'] == $data['out_warehouse_id']){
			return ['success' => false, 'msg' => '调入仓 不能与 调出仓 一样！'];
		}
		if (empty($data['stock_allocatione_id'])){
			return ['success' => false, 'msg' => '请填写单号'];
		}
		if(empty($data['prod'])){
			return ['success' => false, 'msg' => '请选择商品'];
		}
		
		//查询对应调出仓的库存
		$sku_list = array();
		$sku_stock_list = array();
		foreach ($data['prod'] as $aProd){
			$sku_list[] = $aProd['sku'];
		}
		$stocks = ProductStock::find()->where(['warehouse_id' => $data['out_warehouse_id'], 'sku' => $sku_list])->asArray()->all();
		foreach($stocks as $stock){
			$sku_stock_list[strtolower($stock['sku'])] = $stock['qty_in_stock'];
		}
		//判断是否有足够库存出库
		$msg = '';
		foreach ($data['prod'] as $aProd){
			$sku = strtolower($aProd['sku']);
			if(empty($aProd['qty'])){
				$msg .= "$sku 调拨数量必须大于0！<br>";
			}
			else if(empty($sku_stock_list[$sku]) || $aProd['qty'] > $sku_stock_list[$sku]){
				$msg .= "$sku 不够库存出库！<br>";
			}
		}
		if(!empty($msg)){
			return ['success' => false, 'msg' => $msg];
		}
	
		$transaction = Yii::$app->get('subdb')->beginTransaction ();
		//保存单头信息
		$allocation = new StockAllocation();
		$allocation->stock_allocatione_id = $data['stock_allocatione_id'];
		$allocation->in_warehouse_id = $data['in_warehouse_id'];
		$allocation->out_warehouse_id = $data['out_warehouse_id'];
		$allocation->capture_user_id = \Yii::$app->user->id;
		$allocation->number_of_sku = count($data['prod']);
		$allocation->comment = $data['comment'];
		$allocation->create_time =TimeUtil::getNow();
		$allocation->update_time =TimeUtil::getNow();
		if(!$allocation->save() ){
			$rtn['success'] = false;
			foreach ($allocation->errors as $k => $anError){
				$rtn['msg'] .=($rtn['msg']==""?"":"<br>"). $k.":".$anError[0];
			}
			$transaction->rollBack();
			return $rtn;
		}
	
		//保存明细信息
		foreach ($data['prod'] as $aProd){
			$allocationdetail = new StockAllocationDetail();
			$allocationdetail->allocatione_id = $allocation->id;
			$allocationdetail->sku = $aProd['sku'];
			$allocationdetail->qty = $aProd['qty'];
			$allocationdetail->location_grid = $aProd['location_grid'];
			if (!$allocationdetail->save() ){
				$rtn['success']=false;
				foreach ($allocationdetail->errors as $k => $anError){
					$rtn['msg'] .= ($rtn['msg']==""?"":"<br>"). $k.":".$anError[0];
				}
				$transaction->rollBack();
				return $rtn;
			}
		}
	
		//插入到出入库记录
		//出库
		$i = 1;
		$stock_change_id_out = $data['stock_allocatione_id'].'_out';
		while (StockChange::find()->where(['stock_change_id'=>$stock_change_id_out])->One() <> null){
			$stock_change_id_out = join("_",array($data['stock_allocatione_id'], "out", $i));
			$i++;
		}
		$stock_change_data_out = array(
				'stock_change_id' => $stock_change_id_out,
				'warehouse_id' => 	$allocation->out_warehouse_id,
				'comment' => "(系统根据调拨结果自动生成)",
				'create_time'=> $allocation->create_time
		);
		$insertStockAllocationRecord = self::insertStockOutRecord($stock_change_data_out, 600);
		if (!$insertStockAllocationRecord['success']){
			$transaction->rollBack();
			return ['success' => false, 'msg' => $insertStockAllocationRecord['message']];
		}
		//入库
		$i = 1;
		$stock_change_id_in = $data['stock_allocatione_id'].'_in';
		while (StockChange::find()->where(['stock_change_id'=>$stock_change_id_in])->One() <> null){
			$stock_change_id_in = join("_",array($data['stock_allocatione_id'], "in", $i));
			$i++;
		}
		$stock_change_data_in = array(
				'stock_change_id' => $stock_change_id_in,
				'warehouse_id' => 	$allocation->in_warehouse_id,
				'comment' => "(系统根据调拨结果自动生成)",
				'create_time'=> $allocation->create_time
		);
		$insertStockAllocationRecord = self::insertStockInRecord($stock_change_data_in, 600);
		if (!$insertStockAllocationRecord['success']){
			$transaction->rollBack();
			return ['success' => false, 'msg' => $insertStockAllocationRecord['message']];
		}

		//插入出入库明细
		foreach ($data['prod'] as $aProd){
			//出库
			$insertDetail = self::insertStockChangeDetailRecord($stock_change_data_out, $aProd['sku'], $aProd['qty']);
			if (!$insertDetail['success']){
				$transaction->rollBack();
				return ['success' => false, 'msg' => $insertDetail['message']];
			}
			$modifyStockage =self::modifyProductStockage($aProd['sku'], $allocation->out_warehouse_id, 0, -$aProd['qty'], 0, 0, 0, '');
			if (!$modifyStockage['success']){
				$transaction->rollBack();
				return ['success' => false, 'msg' => $modifyStockage['message']];
			}
			//入库
			$insertDetail = self::insertStockChangeDetailRecord($stock_change_data_in, $aProd['sku'], $aProd['qty']);
			if (!$insertDetail['success']){
				$transaction->rollBack();
				return ['success' => false, 'msg' => $insertDetail['message']];
			}
			$modifyStockage =self::modifyProductStockage($aProd['sku'], $allocation->in_warehouse_id, 0, $aProd['qty'], 0, 0, 0, $aProd['location_grid']);
			if (!$modifyStockage['success']){
				$transaction->rollBack();
				return ['success' => false, 'msg' => $modifyStockage['message']];
			}
		}
	
	
		if($rtn['success']){
			$transaction->commit();
			
			//写入操作日志
			UserHelper::insertUserOperationLog('inventory', '录入仓库调拨, 单号'.$data['stock_allocatione_id'].' 生成出入库记录: '.$stock_change_id_in.', '.$stock_change_id_out);
		}
		return $rtn;
	}
	
	/**
	 +----------------------------------------------------------
	 * 更新库存采购价，用商品采购价替换
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2018/05/30		初始化
	 +----------------------------------------------------------
	 **/
	public static function UpdateStockPrice(){
		$stocks = ProductStock::find()->where("warehouse_id in (select warehouse_id from wh_warehouse where is_active!='N' and is_active != 'D' and name!='无')")->limit(10000)->all();
		$skus = array();
		foreach($stocks as $stock){
			$skus[] = $stock->sku;
		}
		$product_prices = array();
		$pros = ProductSuppliers::find()->where(['sku' => $skus])->orderBy("priority")->asArray()->all();
		foreach($pros as $pro){
			$sku = strtolower($pro['sku']);
			if(!array_key_exists($sku, $product_prices)){
				$product_prices[$sku] = $pro['purchase_price'];
			}
		}
		unset($pros);
		unset($skus);
		foreach($stocks as $stock){
			$sku = strtolower($stock['sku']);
			if(!empty($product_prices[$sku])){
				$stock->average_price = $product_prices[$sku];
				$stock->save(false);
			}
		}
		
	}
	
	
}



