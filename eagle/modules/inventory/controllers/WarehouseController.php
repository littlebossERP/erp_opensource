<?php

namespace eagle\modules\inventory\controllers;

use Yii;

use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\StandardConst;

use eagle\modules\inventory\models\Warehouse;

use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\SwiftFormat;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\util\helpers\GetControlData;
use yii\base\Action;
use yii\data\Sort;
use eagle\widgets\ESort;
use yii\base\ExitException;
use yii\base\Exception;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\inventory\models\WarehouseCoverNation;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\models\SysCountry;
use eagle\modules\inventory\models\ProductStock;
use eagle\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use yii\data\Pagination;
use eagle\modules\inventory\apihelpers\InventoryApiHelper;
use eagle\modules\inventory\helpers\InventoryHelper;

/**
+------------------------------------------------------------------------------
* 仓库盘点处理 控制类
+------------------------------------------------------------------------------
* @category		Inventory
* @package		Controller/Warehouse
* @subpackage   Exception
* @version		1.0
+------------------------------------------------------------------------------
*/

class WarehouseController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false; //非网页访问方式跳过通过csrf验证的 . 如: curl 和 post man

	/**
	 +---------------------------------------------------------------------------------------------
	 * To generate a screen for Warehouse list, without Warehouse data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/05/07		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionWarehouse_list(){	
		$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
		$page = isset($_GET['page'])?$_GET['page']:1;
		
		if(!empty($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}else{
			$sort = 'warehoue_id';
			$order = 'asc';
		}
		
		$queryString = array();
		if(!empty($_REQUEST['is_active']))
		{
			if(strtolower($_REQUEST['is_active'])!=='all' or $_REQUEST['is_active']=='' )
			$queryString['is_active'] = $_REQUEST['is_active'];
		}
		if(!empty($_REQUEST['keyword']))
		{
			$queryString['keyword'] = $_REQUEST['keyword'];
		}
		
		$sortConfig = new Sort(['attributes' => ['warehouse_id','name','is_active','address_nation','create_time']]);
		if(!in_array($sort, array_keys($sortConfig->attributes))){
			$sort = '';
			$order = '';
		}
		
		$sort = 'is_active';
		$order = 'desc';
		$warehouseList = WarehouseHelper::listWarehouseData($page, $pageSize, $sort, $order, $queryString);
		
		return $this->render('index', array(
				'warehouseList'=>$warehouseList,
				'sort'=>$sortConfig,
				'active_status'=> WarehouseHelper::getActiveStatus(),
				'isOversea'=>WarehouseHelper::getIsOversea(),
		));	 
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To load Warehouse list data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionWarehouse_list_data(){
		$page = !empty($_POST['page']) ? $_POST['page'] : 1;
		$rows = !empty($_POST['rows']) ? $_POST['rows'] : 10;
		$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'update_time';
		$order = !empty($_POST['order']) ? $_POST['order'] : 'desc';

		$queryString = array();
		if(!empty($_POST['is_active']))
		{
			$queryString['is_active'] = $_POST['is_active'];
		}
		if(!empty($_POST['search_keyword']))
		{
			$queryString['keyword'] = $_POST['search_keyword'];
		}
		exit(json_encode(
				WarehouseHelper::listWarehouseData($page, $rows, $sort, $order, $queryString)
		));
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To load detail info for a particular Warehouse
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/05/07		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetWarehouseDetail(){
		if(isset($_REQUEST['warehouse_id'])){
			$model=Warehouse::findOne($_REQUEST['warehouse_id']);
			$warehouse_id=$model->warehouse_id;
		}else{
			$model=new Warehouse();
			$warehouse_id=false;
		}
		$isEdit=false;
		if($_REQUEST['tt']=='edit')
			$isEdit=true;
		
		$countrys = WarehouseHelper::getReceivingCountryByWarehouseId($warehouse_id,$isEdit);
		$countryComboBox = WarehouseHelper::countryComboBoxData();

		return $this->renderPartial('_detail', array(
				'model'=>$model,
				'active_status'=> WarehouseHelper::getActiveStatus(),
				'isOversea'=>WarehouseHelper::getIsOversea(),
				'countrys'=>$countrys,
				'region'=>WarehouseHelper::countryRegionChName(),
				'countryComboBox'=>$countryComboBox,
				) );
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To open a capture screen for use to capture a Purchase Stock In record
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionWarehouseCapture(){
		$this->renderPartial('warehouse_capture',array(
				'warehouse_id'=>(isset($_REQUEST['id'])?$_REQUEST['id']:0),
				'activeStatusIdNameComoBox'=>WarehouseHelper::activeStatusIdNameComoBox(),
				'nationComoBox'=>GetControlData::getNationComoBox(),
				'isOverseaComoBox'=>WarehouseHelper::isOverseaComoBox()
		) , false, true);
	}//end of action
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * After opening a capture form for warehouse info, Load the warehouse detail to prefill it
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public function actionCapturePrefill_data(){
		if (isset($_REQUEST['id']))
			exit( json_encode(WarehouseHelper::getWarehouseDetailData($_REQUEST['id'])) );
		else 
			exit( json_encode("Nothing passed to Host") );
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To recieve the form and CREATE the Warehouse record / items
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCreateWarehouse(){
		if(!empty($_POST)) {
			SwiftFormat::arrayTrim($_POST);
			$rtn = WarehouseHelper::saveWarehouse($_POST) ;
			exit( json_encode($rtn) );
		}else
			exit(json_encode(array('success'=>false,'message'=>"Nothing Posted to Server")) );
	
	}//end of action
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * getOperationLog for the particular warehouse
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	warehouse_id		1
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public function actionOperationLog_Data(){
		if (isset($_REQUEST['warehouse_id'])){
			$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$rows = !empty($_REQUEST['rows']) ? $_REQUEST['rows'] : 10;
			$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : 'id';
			$order = !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'desc';
			
			$queryString = array();
			if(is_numeric($_REQUEST['warehouse_id']))
			{
				$queryString['log_key'] = $_REQUEST['warehouse_id'];
			}

			exit(json_encode(
					WarehouseHelper::listOperationLogData("warehouse",$page, $rows, $sort, $order, $queryString)
			));
		}
		else
			exit( json_encode("Nothing passed to Host") );		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据配送国家，列出可配送仓库
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2014/08/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public function actionListWarehouseCoverNation(){
		$countriesCodeName = StandardConst::$COUNTRIES_CODE_NAME_CN;
		$nationComoBox = GetControlData::getNationComoBox();
		$this->renderPartial('warehouse_cover_nation_list', array('nationComoBox'=>$nationComoBox,'countriesCodeName'=>$countriesCodeName), false, true);
	}
	
	public function actionListWarehouseCoverNationData(){
		$warehouseInfo = Warehouse::findAll();
		$warehouseIdMap = array();
		foreach ($warehouseInfo as $warehouse){
			$warehouseIdMap[$warehouse->warehouse_id] = $warehouse;
		}
		$warehouseCoverNations = WarehouseCoverNation::findAll();
		$listRowData = array();
		$countriesCodeWarehouseMap = array();
		$countriesCodeName = StandardConst::$COUNTRIES_CODE_NAME_CN;
		$listCountriesCode = array();
		//获取要显示的国家，并将数据以国家作分组
		foreach ($warehouseCoverNations as $warehouseCoverNation){
			if(!array_key_exists($warehouseCoverNation->warehouse_id, $warehouseIdMap)){//$warehouseCoverNation记录的仓库不存在，删除记录。
				$warehouseCoverNation->delete();
			}else {
				if(!in_array($warehouseCoverNation->nation , $listCountriesCode)){
					$listCountriesCode[] = $warehouseCoverNation->nation;
					$listRowData[] = array('nation_code'=> $warehouseCoverNation->nation ,'nation_name'=>$countriesCodeName[$warehouseCoverNation->nation]);
				}
				$countriesCodeWarehouseMap[$warehouseCoverNation->nation][] = $warehouseCoverNation;
			}
		}
		
		exit( json_encode(array('listRowData'=>$listRowData,'warehouseInfo'=>$warehouseInfo ,'warehouseIdMap'=>$warehouseIdMap,'countriesCodeWarehouseMap'=>$countriesCodeWarehouseMap)));
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 添加可配送仓库到配送国家
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2014/08/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public function actionAddWarehouseToNation(){
		if(!isset($_POST['targetWarehouseToNation'])){
			exit( json_encode(array("code"=>"fail","message"=>"添加失败。请添加仓库！")) );		
		}else{
			// 一个nation 对应的priority 要唯一
			$existedModel = WarehouseCoverNation::findAll(['nation' => $_POST['targetWarehouseToNation']['nation'], 'priority' => $_POST['targetWarehouseToNation']['priority']]);
			if($existedModel != null){
				SysLogHelper::SysLog_Create("Inventory",__CLASS__, __FUNCTION__,"","Failed to add warehouse id:".$_POST['targetWarehouseToNation']['warehouse_id']." for nation :".$_POST['targetWarehouseToNation']['nation']." : priority(".$_POST['targetWarehouseToNation']['priority'].") already existed!", "Error");
				exit( json_encode(array("code"=>"fail","message"=>"添加失败，请刷新页面后再添加！")) );
			}
			$warehouseCoverNation = new WarehouseCoverNation();
			$warehouseCoverNation->attributes = $_POST['targetWarehouseToNation'];
			if($warehouseCoverNation->save(false)){
				exit( json_encode(array("code"=>"success","message"=>"添加成功！")) );
			}else{
				SysLogHelper::SysLog_Create("Inventory",__CLASS__, __FUNCTION__,"","WarehouseCoverNation record failed to save record:".print_r($_POST['targetWarehouseToNation'],true), "Error");
				exit( json_encode(array("code"=>"fail","message"=>"添加失败！")) );
			}
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  删除配送国家的可配送仓库
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2014/08/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public function actionDeleteWarehouseFromNation(){
		if(!isset($_POST['id'])){
			exit( json_encode(array("code"=>"fail","message"=>"删除失败，请选择要删除的仓库！")) );
		}else{
			WarehouseCoverNation::deleteAll($_POST['id']);
			exit( json_encode(array("code"=>"success","message"=>"删除成功！")) );
		}
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 *  上移配送国家的可配送仓库，与上一个仓库交换priority
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2014/08/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public function actionUpgrateWarehouseFromNation(){
		//前台控制上传的两个record id 仓库属于同一个国家
		if(!isset($_POST['targetId']) || !isset($_POST['formerId'])){
			exit( json_encode(array("code"=>"fail","message"=>"上移失败！")) );
		}else{
			$target = WarehouseCoverNation::findOne(['nation'=>$_POST['targetId']]);
			$former = WarehouseCoverNation::findOne(['nation'=>$_POST['formerId']]);
			if($target->nation != $target->nation){
				exit( json_encode(array("code"=>"fail","message"=>"上移失败，请确保该上下移的仓库属于同一个国家！")) );
			}else{
				$formerPriority = $former->priority;
				$former->priority = $target->priority;
				$target->priority = $formerPriority;
				$transaction = Yii::$app->get('subdb')->beginTransaction();
		    	try{
		    		if($former->save(false) && $target->save(false)){
						$transaction->commit();
						exit(json_encode(array("code"=>"success","message"=>"操作成功！")) );
		    		}else{
						$transaction->rollBack();
					    SysLogHelper::SysLog_Create("Inventory",__CLASS__, __FUNCTION__,"","Fail to exchange WarehouseCoverNation id:".$_POST['targetId'].",".$_POST['formerId']." priority:".$former->errors, "Error");
					    exit( json_encode(array("code"=>"success","message"=>"操作失败！")) );
					}
		    	}catch(Exception $e){
				    $transaction->rollBack();
				    SysLogHelper::SysLog_Create("Inventory",__CLASS__, __FUNCTION__,"","Fail to exchange WarehouseCoverNation id:".$_POST['targetId'].",".$_POST['formerId']." priority:".$e->getMessage(), "Error");
				    exit( json_encode(array("code"=>"success","message"=>"操作失败！")) );
				}
			}
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  更新安全库存
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2016/09/06				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionSaveSafetyStock()
	{
	    try 
	    {
    	    $warehouseid = $_POST['warehouse_id'];
    	    $sku = $_POST['sku'];
    	    $aProductStockInfo = ProductStock::findOne(['warehouse_id'=>$warehouseid, 'sku'=>$sku]);
    	    
    	    //当不存在则新增
    	    if ($aProductStockInfo == null)
    	    {
    	    	$aProductStockInfo = new ProductStock();
    	    	$aProductStockInfo->warehouse_id =$warehouseid;
    	    	$aProductStockInfo->sku = $sku;
    	    }
    	    
    	    $aProductStockInfo->safety_stock = $_POST['safety_stock'];
    	    
    	    if ( $aProductStockInfo->save())
    	    {
    	    	return json_encode(['status'=>0, 'msg'=>'']);
    	    }
    	    else
    	    {
    	        return json_encode(['status'=>1, 'msg'=>'保存失败']);
    	    }
	    }
	    catch(\Exception $e)
	    {
	        return json_encode(['status'=>1, 'msg'=>$e->getMessage()]);
	    }
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  重新计算待发货数量
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2016/09/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionUpdateQtyOrdered()
	{
		try
		{
			InventoryHelper::UpdateUserOrdered(0);
			
			return json_encode(['status'=>0, 'msg'=>'']);
		}
		catch(\Exception $e)
		{
			return json_encode(['status'=>1, 'msg'=>$e->getMessage()]);
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  检测仓库是否存在商品库存
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2016/09/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCheckStock()
	{
		try{
			$ret['success'] = false;
			$ret['msg'] = '';
			
			if(empty($_REQUEST['warehouse_id'])){
				$ret['msg'] = '查询库存失败，参数缺省';
			}
			else{
				$warehouse_id = $_REQUEST['warehouse_id'];
				//判断是否已启用
				$warehouse = Warehouse::find()->where(['warehouse_id' => $warehouse_id])->asarray()->one();
				if(!empty($warehouse) && $warehouse['is_active'] == 'Y'){
					//判断是否存在库存、在途数
					$stock = ProductStock::find()->where(['warehouse_id' => $warehouse_id])->andWhere("qty_in_stock>0 or qty_purchased_coming>0")->one();
					if(!empty($stock)){
						$ret['success'] = true;
						$ret['msg'] = '此仓库存在库存或者在途数量';
					}
				}
			}
		}
		catch(\Exception $e){
			$ret['msg'] = $e->getMessage();
		}
		
		return json_encode($ret);
	}
	
	/**
	 * 删除自营仓库
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/09/11				初始化
	 * +-------------------------------------------------------------------------------------------
	 * @return
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionDeleteWarehouse(){
		$warehouse_id = $_POST['warehouse_id'];
	
		$ret = WarehouseHelper::DeleteWarehouse($warehouse_id);
		return json_encode($ret);
	}
}