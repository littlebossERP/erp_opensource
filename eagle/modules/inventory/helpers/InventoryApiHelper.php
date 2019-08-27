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
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\inventory\models\StockChangeDetail;
use eagle\modules\inventory\helpers\WarehouseHelper;

use yii\data\Pagination;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\util\helpers\HttpHelper;

use eagle\modules\purchase\models\Purchase;
use eagle\modules\purchase\models\PurchaseArrivals;
use eagle\modules\catalog\models\Product;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\inventory\models\OrderReserveProduct;

use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\util\helpers\TimeUtil;
use Zend\Db\Sql\Where;
use yii\db\Transaction;
use eagle\modules\util\helpers\SwiftFormat;
use eagle\modules\util\helpers\ConfigHelper;
use Zend\Http\Header\Location;
use common\helpers\Helper_Array;
use eagle\modules\inventory\models\WarehouseCoverNation;
use eagle\modules\purchase\models\PurchaseItems;
use eagle\modules\purchase\helpers\PurchaseHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;

/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
class InventoryApiHelper {
//状态
	const CONST_1= 1; //Sample
	/**
	 +---------------------------------------------------------------------------------------------
	 + Below are Const definition for this module
	 +---------------------------------------------------------------------------------------------
	 **/
	
	private static $EXCEL_COLUMN_MAPPING = [
	"A" => "sku",
	"B" => "stockchange_qty",
	"C" => "location_grid",
	];
	
	public static $EXPORT_EXCEL_FIELD_LABEL = [
	"sku" => "产品SKU",
	"stockchange_qty" => "出库/入库数量/实际盘点数",
	"location_grid" => "货架位置",
	];
	
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
	public static function getWarehouseIdNameMap($is_active=FALSE){
		WarehouseHelper::createDefaultWarehouseIfNotExists();
		$warehouseIdNameMap=array();
		$queue=Warehouse::find();
		if ($is_active){
			$queue->where(['is_active'=>'Y']);
		}
		$warehouseInfoArr=$queue->select(['warehouse_id','name'])->asArray()->all();
		$warehouseIdNameMap = Helper_Array::toHashmap($warehouseInfoArr, 'warehouse_id','name');
		return $warehouseIdNameMap;
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
	public static function getAllWarehouseInfo($all=false)
	{
		$query=Warehouse::find();
		if($all)
			$query->andWhere(['is_active'=>'Y']);
	
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
	 * 匹配warehouse，根据参数 from country 和 to country，
	 * 用户可以在仓库设置里面修改不同仓库的可送达国家
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * $to_country              必须指定国际国家代码，例如US，AU,JP,CN，系统会匹配仓库可送达地是这个地方的
	 * $from_country            可以为空，不指定,或者指定某个国家代码，
	 *                           如果指定了，系统会匹配 仓库所在地是这个地方的
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			
	 *			e.g.
	 *			array( 1,2,0) ， //0是默认仓库
	 *			如果连默认仓库都没有，返回 array ()
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getMatchWarehouseByCountries($to_country,$from_country=''){
		$ids = array();
		$warehouses = WarehouseCoverNation::find()->andWhere(['nation' => $to_country ])
						->andWhere('warehouse_id != 0')
						->orderBy('priority')
						->asArray()->all();
		foreach ($warehouses as $aWarehouse){
			$isActive = Warehouse::find()->andWhere(['warehouse_id' => $aWarehouse['warehouse_id'] , 'is_active'=>'Y' ])->asArray()->all();
			if (!empty($isActive) )
				$ids[] = $aWarehouse['warehouse_id'];
		}
		$ids[] = 0;
		return $ids;
	}

	/**
	 * To get Picking info by Skus and WarehouseId
	 * 
	 * @access static
	 * 
	 * @param 	array:	$skuArr			需要拣货的产品sku数组
	 * 			string:	$warehouseId	拣货的仓库id
	 * 
	 * @return	array:	$pickingInfo[] = array(
	 * 											'warehouse_id'=>string,
	 * 											'sku'=>string,
	 * 											'qty_in_stock'=>number,
	 * 											'location_grid'=>string,
	 * 											) 
	 * 
	 * @author		lzhl		2015/04/29				初始化
	 **/
	public static function getPickingInfo($skuArr=array(),$warehouseId){
		$pickingInfo=array();
		if (count($skuArr)>0 && $warehouseId>=0){
			foreach ($skuArr as $sku){
				$info = ProductStock::find()
							->select(['warehouse_id','sku','qty_in_stock','location_grid','qty_order_reserved'])
							->where(['warehouse_id'=>$warehouseId,'sku'=>$sku])
							->one();
				if($info!==null){
					$pickingInfo[] =['warehouse_id'=>$info['warehouse_id'],'sku'=>$info['sku'],'qty_in_stock'=>$info['qty_in_stock'],'location_grid'=>$info['location_grid'],'qty_order_reserved'=>$info['qty_order_reserved']];
				}
			}
		}
		return $pickingInfo;
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
	static public function importStockChangeProdsByExcel($ExcelFile ,$warehouse_id,$changeType){
		//初始化 返回结果
		$result['success'] = true;
		$result['message'] = '';
	
		//获取 excel数据
		$excel_data = ExcelHelper::excelToArray($ExcelFile , self::$EXCEL_COLUMN_MAPPING, true);
	
		$prod_list = [];
		$prods =[];
		//检查excel 导入的sku是否重复
		foreach($excel_data as $aProd){
			//排除表头
			$field_labels = self::$EXPORT_EXCEL_FIELD_LABEL;
			if($aProd['sku']==$field_labels['sku'] && $aProd['stockchange_qty']==$field_labels['stockchange_qty'] && $aProd['location_grid']==$field_labels['location_grid']){
				continue;
			}
			//验证sku有效性	
			$product=Product::findOne(['sku'=>$aProd['sku']]);
			if($product==null){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('不存在！  ');
				continue;
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
		foreach($prodDatas as $aProd){
			//排除表头
			$field_labels = self::$EXPORT_EXCEL_FIELD_LABEL;
			if($aProd['sku']==$field_labels['sku'] && $aProd['stockchange_qty']==$field_labels['stockchange_qty'] && $aProd['location_grid']==$field_labels['location_grid']){
				continue;
			}
			//验证sku有效性
			if(Product::findOne(['sku'=>$aProd['sku']])==null){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('不存在！ ');
				continue;
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
			$textarea_div_html .= "<textarea class='hide' name='prod[".$index."][sku]' style='display:none'>".$sku."</textarea>".
					"<textarea class='hide' name='prod[".$index."][name]' style='display:none'>".$name."</textarea>";
			$index ++;
		}
		
		return array('tb_Html'=>$tb_Html,'textarea_div_html'=>$textarea_div_html);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 创建一个新的仓库
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data			要新建或者修改的仓库信息
	 * 						array(
	 * 							"warehouse_id"=> integer（修改该仓库信息） 或者 unset 该key （创建新仓库） ,
	 *                          "name"=> 需要指定的 仓库name，或者 unset 该key （不修改） ,
	 *                          "is_active" => "N" (禁用该仓库) 或者"Y" (启用该仓库) 或者unset 该key （不修改） ，
	 * 							"address_nation"=> 需要指定的 仓库name，或者 unset 该key （不修改） ,
	 * 							"address_state"=> 需要指定的 仓库name，或者 unset 该key （不修改） ,
	 * 							"address_city"=> 需要指定的 仓库name，或者 unset 该key （不修改） ,
	 * 							"address_street"=> 需要指定的 仓库name，或者 unset 该key （不修改） ,
	 * 							"address_postcode"=> 需要指定的 仓库name，或者 unset 该key （不修改） ,
	 * 							"address_phone"=> 需要指定的 仓库name，或者 unset 该key （不修改） ,
	 * 							"comment"=> 需要指定的 仓库name，或者 unset 该key （不修改） ,
	 * 							"is_oversea"=> 是否海外仓，0=否，1=是，或者 unset 该key （不修改） ,
	 * 						)
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * 						['warehouse_id'] = warehouse Id created/mofified
	 * @invokeMethod		InventoryInterface::createWarehouse($data);
	 * @description			提供接口，生产一个新的仓库，用来给海外仓模块调用
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/08/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function createWarehouse($data){
		//make invoking journal
		//$journal_id = SysLogHelper::InvokeJrn_Create("Inventory",__CLASS__, __FUNCTION__ , array( ));
	
		SwiftFormat::arrayTrim($data);
		
		if (!isset($data['is_active']))
			$data['is_active'] = 'Y';
	
		$rtn = WarehouseHelper::saveWarehouse($data) ;
	
		//write the invoking result into journal before return
		SysLogHelper::GlobalLog_Create('createWarehouse', $rtn);
	
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取库存及成本, 指定的sku,在指定的仓库中,库存还有多少,平均采购成本是
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param sku				要查询的产品sku
	 * @param withCost			是否返回成本，默认 是
	 +---------------------------------------------------------------------------------------------
	 * @return				array(仓库1⇒array('warehouse id'=>1,数量⇒1,平均采购成本⇒2)，仓库2⇒array(数量⇒1,平均采购成本⇒2))
	 * @description			在指定的仓库中,库存还有多少,平均采购成本是.
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getProductStockageInfo($sku , $withCost= true){
		//make invoking journal
		$journal_id = SysLogHelper::InvokeJrn_Create("Inventory",__CLASS__, __FUNCTION__ , array($sku,$withCost));
	
		$rtn_productStockages = array('a'=>1, 'b'=>2);
	
		//write the invoking result into journal before return
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn_productStockages);
	
		return $rtn_productStockages;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 占用商品库存(array中的所有sku占用必须类似事务，所有成功才算成功，一个失败，占用失败)
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param order_id				订单号
	 * @param warehouse_id			仓库号
	 * @param products				产品array，每个array 元素包含 sku，数量，来源订单号的信息
	 * 								array ( array('sku'=>1 ,'qty'=>2,'order_id'=>'xxxxx'),
	 * 										array('sku'=>2 ,'qty'=>2,'order_id'=>'xxxxx')
	 * 									  )
	 +---------------------------------------------------------------------------------------------
	 * @return				array( 'success' = true,'message'='......'
	 * 							  )
	 * @description
	 * 						入参：订单号，包裹号,仓库id, array(array(sku⇒1 ,数量⇒2,来源订单号⇒ass), array(sku⇒2,数量⇒3,来源订单号⇒dada))
	 * 						(array中的所有sku占用必须类似事物，所有成功才算成功，一个失败，占用失败)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function OrderProductReserve($order_id,$warehouse_id,$products){
		//make invoking journal
		$journal_id = SysLogHelper::InvokeJrn_Create("Inventory", __CLASS__, __FUNCTION__ , array($order_id,$warehouse_id,$products));
	
		$rtn['success'] = true;
		$rtn['message'] = "";
		$reservations = array();
		
		if(empty($products)){
			$rtn['success'] = true;
			$rtn['message'] = "没有商品需要预约";
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
			return $rtn;
		}
		
		//合并相同SKU的数量
		$count = count($products);
		for($row = 0; $row < $count; $row++)
		{
		    for($n_row = $row + 1; $n_row < $count; $n_row++)
		    {
		        if($products[$row]['sku'] == $products[$n_row]['sku'] && $products[$row]['order_id'] == $products[$n_row]['order_id'])
		        {
		            $products[$row]['qty'] = $products[$row]['qty'] + $products[$n_row]['qty'];
		            array_splice($products,$n_row,1);
		            $n_row--;
		            $count--;
		        }
	        }
		}
		
		//判断是否海外仓，0否1是
		$is_oversea = 0;
		$oversea = Warehouse::findOne(['warehouse_id'=>$warehouse_id, 'is_oversea' => 1]);
		if(!empty($oversea))
		    $is_oversea = 1;
		//读取是否显示海外仓的设置，0否1是
		$is_show_overser_warehouse = ConfigHelper::getConfig('is_show_overser_warehouse')==null?'0':ConfigHelper::getConfig('is_show_overser_warehouse');
		
		//check whether all products are having sufficient stockage for reservation
		foreach ($products as $aProduct){
			//找到库存记录，如果没有则自动新建库存记录
			$productStockage = ProductStock::find()->where(['sku'=>$aProduct['sku'],'warehouse_id'=>$warehouse_id])->one();
			if ($productStockage==null){//自动建立库存数据
				$productStockage=new ProductStock();
				$productStockage->sku = $aProduct['sku'];
				$productStockage->warehouse_id = $warehouse_id;
				$productStockage->qty_order_reserved = 0;
				$productStockage->location_grid = '';
				$productStockage->addi_info = '订单分配库存时系统自动创建的库存记录';
				$productStockage->save();
			}
			
			//是否支持零库存发货配置 N代表不支持零库存发货，Y代表支持零库存发货
			$support_zero_inventory_shipments = ConfigHelper::getConfig('support_zero_inventory_shipments')==null?'Y':ConfigHelper::getConfig('support_zero_inventory_shipments');
			
			if ( ( $productStockage->qty_in_stock - $productStockage->qty_order_reserved ) < $aProduct['qty'] && $support_zero_inventory_shipments == 'N' && ($is_oversea == 0 || $is_show_overser_warehouse == 1) ){//可用库存不足   并且   不支持零库存发货
				$rtn['success'] = false;
				$rtn['message'].= "没有足够库存(需要".$aProduct['qty'].")配货 产品 SKU ".$aProduct['sku']."<br>";
				$rtn['code'] = 'EODRSV001';
			}else{
				//insert a reservation record. not update is provided
				//防止重复预约
				$aReservation = OrderReserveProduct::find()->where(['order_id'=>$order_id])->andWhere(['sku' => $aProduct['sku']])->one();
				if ($aReservation == null){
					$aReservation = new OrderReserveProduct;
					//已被订单预约总数，重复预约则不累加
					$productStockage->qty_order_reserved = $productStockage->qty_order_reserved + $aProduct['qty'];
				}
				$aReservation->reserve_time = TimeUtil::getNow() ;
				$aReservation->order_id = (string)$aProduct['order_id'];
				$aReservation->order_id = (string)$order_id;
				//$aReservation->package_id = $package_id;
				$aReservation->warehouse_id = $warehouse_id;
				$aReservation->sku = $aProduct['sku'];
				//如果同一个订单中有相同sku，累加
				if (isset($reservations[$aProduct['sku']]['qty'])){
					$reservations[$aProduct['sku']]['qty'] += $aProduct['qty'];
				}else{
					$reservations[$aProduct['sku']]['qty'] = $aProduct['qty'];
				}
				
				$reservations[$aProduct['sku']]['reserve'] = $aReservation;
				$reservations[$aProduct['sku']]['stockage'] = $productStockage;
			}
		}//end of each product passed as param
	
		//if all are having sufficient sotckage, save them one by one
		if ($rtn['success']){
			foreach ($reservations as $sku=>$aReserve){
				$aReserve['reserve']->reserved_qty = $aReserve['qty'];
				$aReserve['reserve']->save();
	
				if (! $aReserve['reserve']->save() ){//save successfull
					$rtn['success']=false;
					foreach ($aReserve['reserve']->errors as $k => $anError){
						$rtn['message'] .=  ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
						$rtn['code'] = 'E_ProdRsv001';
					}
				}
	
				if (! $aReserve['stockage']->save() ){//save successfull
					$rtn['success']=false;
					foreach ($aReserve['stockage']->errors as $k => $anError){
						$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
						$rtn['code'] = 'E_ProdRsv002';
					}
				}
			}//end of each reservation
		}
	
		//write the invoking result into journal before return
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		return $rtn;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 释放占用的库存（通过包裹号在占用表中查询，将所有该包裹占用的商品全部释放）
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param order id			关联的订单号;
	 +---------------------------------------------------------------------------------------------
	 * @return				array( 'success' = true,'message'='......'
	 * 							  )
	 * @description
	 * 						释放占用的库存（通过包裹号在占用表中查询，将所有该包裹占用的商品全部释放）
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/03/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function OrderProductReserveCancel($order_id,$module='order',$action='OMS暂停发货'){
		//make invoking journal
		$journal_id = SysLogHelper::InvokeJrn_Create("Inventory", __CLASS__, __FUNCTION__ , array($order_id ));
	
		$rtn['success'] = true;
		$rtn['message'] = "";
		$rtn['code'] = "";
		//try to load this package id reserved items
		$orderReservations = OrderReserveProduct::findAll(['order_id'=>$order_id]);
	
		//if not found any reservation, prompt error
		if (empty($orderReservations)){
			$rtn['success'] = true;//如果没有预约库存就不需要做仓库占用库存总数的处理
			$rtn['message'] = "没有分配库存,不需要释放库存";
			//$rtn['code'] = "E_ProdRsvCan001" ;
		}//end of found nothing reserved
	
		//check product stockage for this package and update the qty reserved
		if ($rtn['success']){
			if (count($orderReservations)>0){
				foreach ($orderReservations as $aReservation){
					$productStockage = ProductStock::findOne(['sku'=>$aReservation->sku,'warehouse_id'=>$aReservation->warehouse_id]);
						
					//release the reservation
					if ($productStockage<>null){
						$productStockage->qty_order_reserved = $productStockage->qty_order_reserved - $aReservation->reserved_qty;
						if (! $productStockage->save() ){//save successfull
							$rtn['success']=false;
							foreach ($productStockage->errors as $k => $anError){
								$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
								$rtn['code'] .= "E_ProdRsvCan002" ;
							}
						}//end of saving the product stockage
					}
				}//end of each reservation
				
				//delete all this package reservations
				OrderReserveProduct::deleteAll(['order_id'=>$order_id]);
				//订单操作日志
				OperationLogHelper::log($module,$order_id,$action,'释放库存',\Yii::$app->user->identity->getFullName());
			}
			
		
			//write the invoking result into journal before return
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		}
		return $rtn;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 实际出库（实际扣减库存）接口,根据包裹号在占用库存表中查询占用数量，实际提交库存
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  order_id			订单号;
	 +---------------------------------------------------------------------------------------------
	 * @return				成功/失败： array( 'success' = true,'message'='......'
	 * 							 )
	 * @description
	 * 						根据包裹号在占用库存表中查询占用数量，实际提交库存
	 * 						同时自动释放占用的库存（通过包裹号在占用表中查询，将所有该包裹占用的商品全部释放）
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/03/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function OrderProductStockOut($order_id){
		//make invoking journal
		$uid = \Yii::$app->user->id;
		$journal_id = SysLogHelper::InvokeJrn_Create("Inventory", __CLASS__, __FUNCTION__ , array($uid, $order_id ));
	
		$rtn['success'] = true;
		$rtn['message'] = "";
		$rtn['code'] = "";
	
		//try to Load the products reserved for this package id
		$orderReservations = OrderReserveProduct::findAll(['order_id'=>$order_id]);
		//if not found any reservation, prompt error
		if (empty($orderReservations)){//没有分配库存的可以立即分配库存
			$order = OdOrder::findOne(['order_id'=>$order_id]);
			
			if($order->default_warehouse_id == -1){
				$thisWHId = InventoryHelper::matchOrdersWarehouse($order);
					
				if($thisWHId != -1){
					$order->default_warehouse_id=$thisWHId;
					$order->save(false);
				}
			}
			
			if($order->default_warehouse_id == -1){
				$rtn['success'] = false;
				$rtn['message'] = '请先选择仓库';
				$rtn['code'] = "E_OPSO_009";
				return $rtn;
			}
			
			$products = array();
			$sku_arr = OrderApiHelper::getProductsByOrder($order->order_id);
			foreach ($sku_arr as $one){
				if (!empty($one['root_sku']) && $one['delivery_status'] != 'ban'){
					$products[] = array('sku'=>$one['root_sku'] ,'qty'=>$one['qty'],'order_id'=>$order->order_id);
				}
			}
			//进行分配
			$rtn2 = InventoryApiHelper::OrderProductReserve($order->order_id, $order->default_warehouse_id, $products);
			if(!$rtn2['success']){//没有预约成功报错
				$rtn['success'] = false;
				$rtn['message'] = "没有分配库存";
				$rtn['code'] = "E_OPSO_001";				
			}
			$orderReservations = OrderReserveProduct::findAll(['order_id'=>$order_id]); 
		}//end of found nothing reserved
	
		//create a stock out change record for this order package items
		if ($rtn['success'] && !empty($orderReservations)){
			$data = array();
			foreach ($orderReservations as $aReservation){
				$data['prods'][]=array(
						'sku'=>	$aReservation->sku,
						'stock_out_qty'=>	$aReservation->reserved_qty,
				);
				$data['warehouse_id'] = $aReservation->warehouse_id;
			}//	end of each reserved item
			
			if(!empty($data)){
    			//prepare info for stock out record
    			$data['stock_change_id'] = "OrderShip_".$order_id;
    			$data['reason'] = 201; //订单出库
    			$data['create_time'] = TimeUtil::getNowDate();
    				
    			//insert the stock out record with details
    			$r = InventoryHelper::insertOtherStockOut($data);
    			if ($r['success']==false){
    				if(strpos( $r['message'] , '已存在该出库单号') !==false){//对重复出库限制的临时处理  liang 11-13
    					$rtn['success'] = true;
    					$rtn['message'] = '该订单已经出过库';
    				}else {
    					$rtn['success'] = false;
    					$rtn['message'] = $r['message'];
    					$rtn['code'] = "E_OPSO_002";
    				}
    			}
			}
			else{
			    $rtn['success'] = true;
			    $rtn['message'] = '无商品出库';
			}
		}//end if previous success
	
	
		//delete all this package reservations, and restore the prod stockage reserved qty info
		if ($rtn['success']){
			$rtn = self::OrderProductReserveCancel($order_id,'delivery','确认发货完成');
			OperationLogHelper::log('delivery',$order_id,'确认发货完成','减库存',\Yii::$app->user->identity->getFullName());
		}
	
		//write the invoking result into journal before return
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
	
		return $rtn;
	}//end of function
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更改订单状态导致已出库产品重新入库  -----预留接口
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  order_id			订单号;
	 +---------------------------------------------------------------------------------------------
	 * @return				成功/失败： array( 'success' = true,'message'='......'
	 * 							 )
	 * @description
	 * 					
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author				
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function OrderProductStockOutReStockIn($order_id){
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改产品的所有有效订单，未发货，等待发货的数量
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param sku			产品编号;
	 * @param qty			数量，负数的是减去的意思;
	 +---------------------------------------------------------------------------------------------
	 * @return				成功/失败： array( 'success' = true,'message'='......'
	 * 							 )
	 * @description
	 * 						根据提交的sku以及数量，该sku的总待发货数量会相应增加
	 * 						如果数量是 负数，该sku的总待发货数量会相应减少
	 * 						请在以下情况调用此接口，增加待发货数量
	 *                      1. 创建待发货的订单的时候，
	 *                      2. 重新下单，或者已发货的订单重新设置为待发货
	 *                      3. 已存在的订单，待发货状态下添加需要发货的产品 或者 增加待发货数量
	 *                      请在以下情况调用此接口，减少待发货数量
	 *                      1. 删除，取消待发货的订单的时候，
	 *                      2. 已存在的订单，待发货状态下删除需要发货的产品 或者 减少待发货数量
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/03/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function ProductPendingShipQty($sku,$qty){
		//make invoking journal
		$journal_id = SysLogHelper::InvokeJrn_Create("Inventory", __CLASS__, __FUNCTION__ , array($sku,$qty) );
	
		$rtn['success'] = true;
		$rtn['message'] = "";
		//check whether all products are having sufficient stockage for reservation
	
		$productModel = Product::model()->findByPk($sku);
		//if not found, prompt error
		if (!isset($productModel) or $productModel == null ){
			$rtn['success'] = false;
			$rtn['message'].="Not found product info for SKU ".$sku."<br>";
		}else
		{	//update the pending ship number
			$productModel->pending_ship_qty += $qty;
			$productModel->save(false);
		}
	
		//write the invoking result into journal before return
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
	
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 告诉本Function 某些sku array 是已经
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param sku_array			待发货总数有变动的产品sku array
	 +---------------------------------------------------------------------------------------------
	 * @return				array(仓库1⇒array('warehouse id'=>1,数量⇒1,平均采购成本⇒2)，仓库2⇒array(数量⇒1,平均采购成本⇒2))
	 * @description			本Function 会认为传进来的 sku array 是待发货总数量有变化的array，然后就会去统计每个sku的该待发货总数量
	 * 						update 到 pd_product.pending_ship_qty
	 * 						为何这样做？因为订单刚到系统时候，还没有分派发货仓库，只是后待发货数量不会被添加到 product_stockage 的记录中去。
	 * 						而因为我们需要知道总的采购建议，所以需要有一个总的待发货数量，从而判断总库存够不够发。
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function refreshProductPendingShipSummary($sku_array){
		//make invoking journal
		//$journal_id = SysLogHelper::InvokeJrn_Create("Inventory",__CLASS__, __FUNCTION__ , array($sku_array ));
		if(empty($sku_array))
			return true;
		//try{
			$sql = "select sum(quantity - sent_quantity) to_send_qty from od_order_v2 od, od_order_item_v2 oditem where
			oditem.order_id= od.order_id and sku in (:skus) and order_status>=200 and
			order_status<500 and shipping_status<>2 and (order_relation='normal' or order_relation='sm') group by oditem.sku";
				
			$command = Yii::$app->get('subdb')->createCommand($sql);
			$skusStr = '"'.implode('","', $sku_array).'"';
			$command->bindValue(':skus', $skusStr, \PDO::PARAM_STR);
			$rows = $command->queryALL();
			
			$qtyInfo = [];
			if(!empty($rows)){
				foreach ($rows as $index=>$row){
					if(!empty($row['sku']))
						$qtyInfo[$row['sku']] = $row['to_send_qty'];
				}
			}
			
			if(empty($qtyInfo)){
				echo "\n no product pending ship to refresh";
				return true;
			}
			
			$updateSql = "UPDATE pd_product SET pending_ship_qty = CASE sku "; 
			foreach ($qtyInfo as $sku => $qty) {
				$updateSql .= sprintf("WHEN %s THEN %d ", $sku, $qty);
			}
			$updateSql .= "END WHERE sku IN (:skus)";
			//echo $updateSql;
			$command = Yii::$app->get('subdb')->createCommand($updateSql);
			$command->bindValue(':skus', $skusStr, \PDO::PARAM_STR);
			$command->execute();
			return true;
			//write the invoking result into journal before return
			//SysLogHelper::InvokeJrn_UpdateResult($journal_id, array($sku_array ));
		//}catch (\Exception $e) {
		//	if(is_array( $e->getMessage()))
		//		echo json_encode( $e->getMessage());
		//	else
		//	echo $e->getMessage();
		//}
		
	}
	
	/**
	+---------------------------------------------------------------------------------------------
	* 根据代发货订单来自动刷新所有产品的待发货数量，即使那些订单还没有确定哪些仓库来配送
	+---------------------------------------------------------------------------------------------
	* @access static
	+---------------------------------------------------------------------------------------------
	* @param
	+---------------------------------------------------------------------------------------------
	* @return				true
	* @description			根据代发货订单来自动刷新所有产品的待发货数量，即使那些订单还没有确定哪些仓库来配送.
	* @Invoke method       InventoryInterface::refreshAllOSOrderProductPendingShipSummary()
	+---------------------------------------------------------------------------------------------
	* log			name	date					note
	* @author		yzq		2014/08/21				初始化
	+---------------------------------------------------------------------------------------------
	**/
	public static function refreshAllOSOrderProductPendingShipSummary(){
	//make invoking journal
	//$journal_id = SysLogHelper::GlobalLog_Create("Inventory",__CLASS__, __FUNCTION__ , array());
	
	//check global last time 30min and do not do the purge too frequently.
	$purgeAll =json_decode( ConfigHelper::getConfig("PurchaseSug/purge_all_product_period",'NO_CACHE'),true );
	
	//$purgeAllProductPeriod = (isset($StockingStrategy['purge_all_product_period']))?$StockingStrategy['purge_all_product_period']:false;
	if(!$purgeAll){
			$purgeAllProductPeriod = array();
				$purgeAllProductPeriod['create_time'] = time();
				$purgeAllProductPeriod['keyid']= 'purge_all_product_period';
				$purgeAllProductPeriod['description'] = "将所有产品pending_ship_qty重置为0的时间间隔";
				$purgeAllProductPeriod['type'] = 0;
				ConfigHelper::setConfig("PurchaseSug/purge_all_product_period", json_encode($purgeAllProductPeriod));
				$newPurgeAll=true;
	}
	if(isset($purgeAll['update_time'])){
		$pastTime = time() - $purgeAll['update_time'];
		if ($pastTime>600) {
			$needPurgeAll =true;
		}
	}
	if(isset($newPurgeAll) || isset($needPurgeAll)){
			$command = Yii::$app->get('subdb')->createCommand("update pd_product set pending_ship_qty=0; " );
			$command->execute();
			$purgeAll['update_time'] = time();
			ConfigHelper::setConfig("PurchaseSug/purge_all_product_period", json_encode($purgeAll));
	}
	
	//check global last time 15min and do not do the purge too frequently.
	$refreshAll = json_decode( ConfigHelper::getConfig("PurchaseSug/refresh_all_product_period",'NO_CACHE'),true );
	if(!$refreshAll){
		$refreshAllProductPeriod = array();
		$refreshAllProductPeriod['create_time'] = time();
		$refreshAllProductPeriod['keyid']  = 'refresh_all_product_period';
		$refreshAllProductPeriod['description'] = "更新所有产品pending_ship_qty的时间间隔";
		$refreshAllProductPeriod['type'] = 0;
		$newRefreshAll=true;
	}
	if(isset($refreshAll['update_time'])){
		$pastTime = time() - $refreshAll['update_time'];
		if ($pastTime>600) {
			$needRefreshAll =true;
		}
	}
	$sku_array = array();
	if(isset($newRefreshAll) || isset($needRefreshAll)){
		$sql = "select distinct sku from od_order_v2 od, od_order_item_v2 oditem where
		oditem.order_id= od.order_id and order_status>=200 and
			order_status<500 and shipping_status<>2 and (order_relation='normal' or order_relation='sm')";
	
		$command = Yii::$app->get('subdb')->createCommand($sql);
		$rows = $command->queryAll();
		foreach ($rows as $row){
			$sku_array[] = $row['sku'];
		}
		self::refreshProductPendingShipSummary($sku_array);
		
		$refreshAll['update_time'] = time();
		ConfigHelper::setConfig("PurchaseSug/refresh_all_product_period", json_encode($refreshAll));
	}
	
	//write the invoking result into journal before return
	//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $sku_array);
	
	return true;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 用于订单检测分配仓库库位ID
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return				int
	 * @description			1.user没有开启仓库模块时直接返回0
	 * 						2.user开启后将所有仓库设置为不启用，也是返回0
	 * 						3.当有两个以上的仓库启用，返回-1表示不作分配默认仓库
	 * 						4.当只有1个仓库启用时，返回该仓库的warehouse_id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/12/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function OrderCheckWarehouseGetOneid(){
		$queue=Warehouse::find();
		
		$warehouseInfoArr=$queue->select('warehouse_id,is_active')->asArray()->all();
		
		//user没有开启仓库模块时直接返回0
		if(count($warehouseInfoArr) == 0){
			return 0;
		}
		
		$warehouseID = '';
		
		//循环判断仓库是否开启
		foreach ($warehouseInfoArr as $warehouseOne){
			if(($warehouseOne['is_active'] == 'Y') && ($warehouseID == '')){
				$warehouseID = $warehouseOne['warehouse_id'];
			}else if(($warehouseOne['is_active'] == 'Y') && ($warehouseID != '')){
				return -1;
			}
		}
		
		//user开启后将所有仓库设置为不启用,返回0
		if($warehouseID == ''){
			$warehouseID = 0;
		}
		
		return $warehouseID;
	}
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * Create product stockin record API
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 		info	e.g:array(
	 * 							'stockchangetype'=>1,//"1" => "入库","2" => "出库","3" => "盘点",
	 * 							'stockchangereason'=>101,//"101" => "采购入库","102" => "样品入库","103" => "回收邮包","104" => "赠品入库","301" => "库存盘盈","201" => "订单出库","202" => "样品出库","203" => "重发邮包","204" => "赠品出库","205" => "报废出库","302" => "库存盘亏","300" => "库存盘点",
	 * 							'purchase_order_id'=>'',//采购单id,无对应采购则为空
	 * 							'stock_change_id'=>'si0001',//出入库ID号
	 * 							'prods'=>[
	 * 								0=>['sku'=>'sku_1' , 'stock_in_qty'=>1],
	 * 								1=>[...],
	 * 								....
	 *								],
	 * 							'comment'=>'',//出入库备注
	 * 							'warehouse_id'=>1,//仓库id
	 * 
	 * 							)
	 +---------------------------------------------------------------------------------------------
	 * @return					array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2016/01/04		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function createNewStockIn($info)
	{
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
			}
		}
	
		$data['capture_user_id'] = \Yii::$app->user->id;
	
		$rtn['message']="";
		//Step:1. insert a stock In record into wh_stock_change
		//check if this stock change id is available
		$stock_change_id = $data['stock_change_id'];
		$prods =  $data['prods'];
		asort($prods);
		if($purchase_order_id){
			if( isset($data['comment'])){
				$data['comment'] .= "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ ".TimeUtil::getNow()."</font><br>".TranslateHelper::t('通过仓储管理模块接口入库。');
			}else{
				$data['comment'] = "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ ".TimeUtil::getNow()."</font><br>".TranslateHelper::t('通过仓储管理模块接口入库。');
			}
		}
		
		$journal_id = SysLogHelper::InvokeJrn_Create("Inventory", __CLASS__, __FUNCTION__ , $info );
		$transaction = Yii::$app->get('subdb')->beginTransaction ();
	
		if (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
			$rtn['success']=false;
			$rtn['message'] .= ($rtn['message']==""?"":"<br>"). "已存在该入库单号 $stock_change_id ，请更改入库单号";
		}else{
			$rtn = InventoryHelper::insertStockInRecord($data,$data['reason']);
		}
	
		//Step:2 insert this stock in sku,qty details into wh_stock_change_detail
		if ($rtn['success']){
			//only when step 1 successes, proceed with step 2
			foreach ($prods as $aProd){
				if ($rtn['success'])
					$rtn = InventoryHelper::insertStockChangeDetailRecord($data,$aProd['sku'], $aProd['stock_in_qty'] );
				else{
					$transaction->rollBack();
					SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
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
							SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
							return $rtn;
						}else{
							$price = empty($itemModel->price)?0:$itemModel->price;
						}
						if($purchase_WH_id){
							//入库仓不同于原仓库时，扣除原仓库在途数
							$rtn = InventoryHelper::modifyProductStockage($aProd['sku'], $purchase_WH_id,0-$aProd['stock_in_qty'] , 0, 0, 0,0,0);
							if(!$rtn['success']){
								$rtn['success']=false;
								$rtn['message'] .= ($rtn['message']==""?"":"<br>"). "入库仓库与采购仓库不同， ".$aProd['sku']." 在新旧仓库调转过程中出现问题，入库操作失败。";
								$transaction->rollBack();
								SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
								return $rtn;
							}
						}
						$rtn = InventoryHelper::modifyProductStockage($aProd['sku'], $data['warehouse_id'],0-$aProd['stock_in_qty'] , $aProd['stock_in_qty'], 0, 0,$price,$aProd['location_grid']);
					}else{
						$rtn = InventoryHelper::modifyProductStockage($aProd['sku'], $data['warehouse_id'],0, $aProd['stock_in_qty'], 0, 0, 0, $aProd['location_grid']);
					}
				}else{
					$transaction->rollBack();
					SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
					return $rtn;
				}
			}//end of each sku captured
		}//end of step 3
	
		//Step 4: if stockin by purchaseOrder , update purchase status after stockin
		if($purchase_order_id){
			$purchase = Purchase::findOne(['purchase_order_id'=>$purchase_order_id]);
			$purchase->status = PurchaseHelper::STOCK_INED;
			$purchase->comment = "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ ".TimeUtil::getNow().TranslateHelper::t("通过仓库模块接口入库")."<br>".$purchase->comment;
			$purchase->save(false);
		}
		//end of step 4
		if ($rtn['success']){
			$transaction->commit();
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		}else{
			$transaction->rollBack();
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		}
	
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 当不允许负库存出库时，判断订单是否不够库存出库
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  order_id			订单号;
	 +---------------------------------------------------------------------------------------------
	 * @return				 array( 'success' = true,'message'='......')
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/03/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function CheckOrderStockOut($order_id){
		try{
			$rtn['success'] = true;
			$rtn['message'] = "";
			
			//是否支持零库存发货配置 N代表不支持零库存发货，Y代表支持零库存发货
			$support_zero_inventory_shipments = ConfigHelper::getConfig('support_zero_inventory_shipments') == null ? 'Y' : ConfigHelper::getConfig('support_zero_inventory_shipments');
			
			//当支持负库存出库，则不需判断
			if($support_zero_inventory_shipments == 'N'){
				//判断是否 已经预约库存，预约了就不需继续判断
				$orderReservations = OrderReserveProduct::findAll(['order_id'=>$order_id]);
				if (empty($orderReservations)){
					$order = OdOrder::findOne(['order_id'=>$order_id]);
						
					if($order->default_warehouse_id == -1){
						$rtn['success'] = false;
						$rtn['message'] = '请先选择仓库';
						return $rtn;
					}
					
					$products = array();
					$sku_arr = OrderApiHelper::getProductsByOrder($order->order_id);
					foreach ($sku_arr as $one){
						if (!empty($one['root_sku'])){
							$sku = $one['root_sku'];
							if(array_key_exists($sku, $products)){
								$products[$sku]['qty'] = $products[$sku]['qty'] + (empty($one['qty']) ? 0 : $one['qty']);
							}
							else{
								$products[$sku] = array('sku'=>$sku ,'qty'=>$one['qty'],'order_id'=>$order->order_id);
							}
						}
					}
					
					if(empty($products)){
						$rtn['success'] = true;
						$rtn['message'] = "没有商品需要预约";
						return $rtn;
					}
					
					//判断是否海外仓，0否1是
					$is_oversea = 0;
					$oversea = Warehouse::findOne(['warehouse_id'=>$order->default_warehouse_id, 'is_oversea' => 1]);
					if(!empty($oversea))
						$is_oversea = 1;
					//读取是否显示海外仓的设置，0否1是
					$is_show_overser_warehouse = ConfigHelper::getConfig('is_show_overser_warehouse')==null?'0':ConfigHelper::getConfig('is_show_overser_warehouse');
					
					foreach ($products as $aProduct){
						//找到库存记录，如果没有则自动新建库存记录
						$productStockage = ProductStock::find()->where(['sku'=>$aProduct['sku'],'warehouse_id'=>$order->default_warehouse_id])->one();
						if ($productStockage == null){
						    $rtn['success'] = false;
		    				$rtn['message'].= "库存为零(需要".$aProduct['qty'].")配货 产品 SKU ".$aProduct['sku']."<br>";
						}	
						else if ( ( $productStockage->qty_in_stock - $productStockage->qty_order_reserved ) < $aProduct['qty'] && ($is_oversea == 0 || $is_show_overser_warehouse == 1) ){
		    				$rtn['success'] = false;
		    				$rtn['message'].= "没有足够库存(需要".$aProduct['qty'].")  产品 SKU ".$aProduct['sku']."<br>";
						}
					}
				}
			}
		}
		catch(\Exception $ex){
			$rtn['success'] = false;
			$rtn['message'] = $ex->getMessage();
		}
		
		return $rtn;
	}//end of function
}
