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
use eagle\modules\inventory\models\StockTake;
use eagle\modules\inventory\models\StockTakeDetail;
use eagle\modules\inventory\models\Warehouse;

use yii\data\Pagination;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\util\helpers\HttpHelper;

use eagle\modules\catalog\models\Product;

use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\inventory\models\StockChangeDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\permission\helpers\UserHelper;

/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
/**
 +------------------------------------------------------------------------------
 * 仓储模块 - 盘点相关 业务逻辑类
 +------------------------------------------------------------------------------
 * @category	StockTake
 * @package		Controller/StockTake
 * @subpackage  Exception
 * @version		1.0
 +------------------------------------------------------------------------------
 */

class StockTakeHelper
{
	/**
	 +---------------------------------------------------------------------------------------------
	 * To list all Stock Take Data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param page			Page Number to be shown
	 * @param rows			number of rows per page
	 * @param sort          sort by which field
	 * @param order         order by which field
	 * @param queryString   array of criterias
	 +---------------------------------------------------------------------------------------------
	 * @return				To List all stock take data.
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/3/23		copy form Eagle ver1.0
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listStockTakeData($page, $rows, $sort, $order, $queryString){
		$stockTake = StockTake::find();
		
		//$criteria = new CDbCriteria();
		if(!empty($queryString)) {
			foreach($queryString as $k => $v) {
				if ($k=='keyword'){
					$stockTake->andWhere("comment like '%$v%'");
				}elseif($k=='date_from') {
					$stockTake->andWhere("create_time >= '$v'");
				}elseif($k=='date_to') {
					$stockTake->andWhere("create_time <= '$v'");
				}else
					$stockTake->andWhere("$k = '$v'");
			}
		}
		
		//读取是否显示海外仓仓库
		$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
		if(empty($is_show))
			$is_show = 0;
		//不显示海外仓仓库
		if($is_show == 0)
			$stockTake = $stockTake->andWhere('warehouse_id in (select warehouse_id from wh_warehouse where is_oversea=0)');
		
		$pagination = new Pagination([
				'pageSize' => $rows,
				'totalCount' => count($stockTake->all()),
				]);
		$result['pagination'] = $pagination;
		
		$queryRows = $stockTake
					->limit($rows)
					->offset( ($page-1) * $rows )
					->orderBy("$sort $order")
					->asArray()
					->all();

		$result['total'] = count($queryRows);
		$result['datas'] = $queryRows;
		//$result['rows'] = GetControlData::formatModelsWithUserName(StockTake::model()->findAll($criteria),"capture_user_id");
		return $result;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To get Stock Take Detai lData
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id						stock take record id
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			array of sql query all with all info
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getStockTakeDetailData($id){
		$sql = "SELECT a.*, p.photo_primary
		FROM wh_stock_take_detail a , pd_product p where p.sku=a.sku and stock_take_id='$id'";
		$result =Yii::$app->get('subdb')
		->createCommand($sql)
		->queryAll();
		return $result;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To insert Stock Take records
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data						data posted with form
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * This is to insert Stock Take headers and also the details
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function insertStockTake($data){
		$rtn['success']=true;
		$rtn['message']='';
		$stock_change_id = '';
		//step 0: general business Level Validation
		if (!isset($data['warehouse_id']) || Warehouse::find()->where(['warehouse_id'=>$data['warehouse_id']])->One() == null ){
			$rtn['success']=false;
			$rtn['message'] =  (empty($rtn['message'])?"":"<br>"). "请选择仓库";
			return $rtn;
		}

		//step 1, insert record into stock take table
		$prods = $data['prod'];
		asort($prods);

		$transaction = Yii::$app->get('subdb')->beginTransaction ();
		
		$rtn['message']="";
		$aStockTake=new StockTake();
		$aStockTake->attributes=$data; //put the $data field values into aStockTake
		$aStockTake->capture_user_id = \Yii::$app->user->id;
		$aStockTake->number_of_sku = count($prods);
		$aStockTake->create_time =TimeUtil::getNow();
		$aStockTake->update_time =TimeUtil::getNow();

		if ( $aStockTake->save() ){//save successfull
			//ENUM('purchase','stock_change','product','finance','warehouse','supplier')
			//模块不齐全，暂时不启用写log
			OperationLogHelper::log('stock_take',$aStockTake->stock_take_id,"创建库存盘点记录");
		}else{
			$rtn['success']=false;
			foreach ($aStockTake->errors as $k => $anError){
				$rtn['message'] .=($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
			$transaction->rollBack();
			return $rtn;
		}//end of save failed
		//end of step 1
		
		//Step:2 insert this stock in sku,qty details into wh_stock_change_detail //only when step 1 successes, proceed with step 2
		foreach ($prods as $aProd){
			$insertDetailRtn = self::insertStockTakeDetailRecord($aStockTake->stock_take_id, $aStockTake->warehouse_id ,$aProd['sku'], $aProd['qty_actual'],$aProd['location_grid']  );
			if(!empty($insertDetailRtn['rtn']['success']))
				$stockTakeDetail_models[$aProd['sku']] = $insertDetailRtn['data'];
			else{
				$rtn['success']=false;
				$rtn['message'].=$insertDetailRtn['rtn']['message'];
				$transaction->rollBack();
				return $rtn;
			}
		}//end of each sku to stock In
		//end of step 2

		//Step 3: for all stock take items, insert stock Change record and update 仓库信息，产品的 在库数量，变更
		if ($rtn['success']){
			//step 3.1, insert stock change record first
			//use the "StockTake_" + stock_take_id  + AI to format the stock change id
			//check if this stock change id is available
			$i = 1;
			$stock_change_id = join("_",array("StockTake",$aStockTake->stock_take_id, $i  ));
			while (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
				$i++;
				$stock_change_id = join("_",array("StockTake",$aStockTake->stock_take_id, $i  ));
			}
			$stock_change_data = array(
					'stock_change_id' => $stock_change_id,
					'warehouse_id' => 	$aStockTake->warehouse_id,
					'comment' => "(系统根据盘点结果自动生成)",
					'create_time'=> $aStockTake->create_time
			);
			$insertStockTakeRecord = InventoryHelper::insertStockTakeRecord($stock_change_data);

			//if insert stock take record failed , return
			if (!$insertStockTakeRecord['success']){
				$transaction->rollBack();
				$rtn['success']=false;
				$rtn['message'].=$insertStockTakeRecord['message'];
				return $rtn;
			}
				
			foreach ($stockTakeDetail_models as $aStockTakeDetail_model){
				//step 3.2: insert a stock change detail
				$insertDetail = InventoryHelper::insertStockChangeDetailRecord($stock_change_data,$aStockTakeDetail_model->sku ,$aStockTakeDetail_model->qty_reported);
				if (!$insertDetail['success']){
					$transaction->rollBack();
					$rtn['success']=false;
					$rtn['message'].=$insertDetail['message'];
					return $rtn;
				}
					
				//step 3.3: update the 仓库信息，产品的 在库数量，变更
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
				$modifyStockage =InventoryHelper::modifyProductStockage($aStockTakeDetail_model->sku,$aStockTake->warehouse_id  ,0, $aStockTakeDetail_model->qty_reported, 0, 0, 0, $aStockTakeDetail_model->location_grid );
				if (!$modifyStockage['success']){
					$transaction->rollBack();
					$rtn['success']=false;
					$rtn['message'].=$modifyStockage['message'];
					return $rtn;
				}
			}//end of each sku captured

		}//end of step 3
		
		if($rtn['success']){
			$transaction->commit();
			
			if(!empty($stock_change_id)){
				//写入操作日志
				UserHelper::insertUserOperationLog('inventory', '录入盘点单, 生成出入库记录: '.$stock_change_id);
			}
		}
		return $rtn;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To insert Stock take Detail Record after use captured in Front End
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			stock take id
	 * 			warehouse id
	 *			sku
	 *			qty actual
	 *			location grid
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * This is to insert stock take Detail.  e.g. SKU, qty
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function insertStockTakeDetailRecord($stock_take_id,$warehouse_id,$sku,$qty_actual,$location_grid){
		$rtn['message']="";
		$aStockTakeDetail_model = new StockTakeDetail();
		$aStockTakeDetail_model->stock_take_id = $stock_take_id;
		//$aStockTakeDetail_model->warehouse_id = $warehouse_id;
		$sku = trim($sku);
		$aStockTakeDetail_model->sku = $sku;
		$aStockTakeDetail_model->qty_actual = $qty_actual;
		$aStockTakeDetail_model->location_grid = $location_grid;

		//format the qty shall be by checking the current stockage in the warehouse
		$prod_inventory_model = InventoryHelper::getProductInventory($sku, $warehouse_id);
		if ($prod_inventory_model == null)
			$aStockTakeDetail_model->qty_shall_be = 0;
		else
			$aStockTakeDetail_model->qty_shall_be = $prod_inventory_model->qty_in_stock;

		//format the $product_name
		$prod_model = Product::find()->where(['sku'=>$sku])->One();
		if ($prod_model == null)
			$aStockTakeDetail_model->product_name = "(Not found this SKU)";
		else
			$aStockTakeDetail_model->product_name = $prod_model->name;

		$aStockTakeDetail_model->qty_reported = $aStockTakeDetail_model->qty_actual - $aStockTakeDetail_model->qty_shall_be;

		if ( $aStockTakeDetail_model->save() ){//save successfull
			$rtn['success']=true;
		}else{
			$rtn['success']=false;
			foreach ($aStockTakeDetail_model->errors as $k => $anError){
				$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}

		return array('rtn'=>$rtn,'data'=>$aStockTakeDetail_model);
	}

}


?>