<?php

namespace eagle\modules\inventory\controllers;

use Yii;

use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\inventory\helpers\InventoryHelper;

use eagle\modules\inventory\models\Warehouse;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\purchase\helpers\PurchaseHelper;
use eagle\modules\purchase\helpers\SupplierHelper;

use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\GoogleHelper;
use yii\base\Action;
use yii\data\Sort;
use yii\base\ExitException;
use yii\base\Exception;

use eagle\modules\inventory\helpers\StockTakeHelper;
use eagle\widgets\ESort;
use eagle\modules\purchase\models\Purchase;
use eagle\modules\purchase\models\PurchaseItems;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\catalog\models\ProductClassification;

/**
 * InventoryController implements the CRUD actions for Inventory model.
 */
class InventoryController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false; //非网页访问方式跳过通过csrf验证的 . 如: curl 和 post man

    /**
     * get warehouse id&name info
     * @return array(
     * 				"$warehouse_id"=>"$name"
     * 			)
     */
    protected static function warehouse(){
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
    	return $warehouse;
    }

    /**
     * get active warehouse id&name info
     * @return array(
     * 				"$warehouse_id"=>"$name"
     * 			)
     */
    protected static function activeWarehouse(){
    	WarehouseHelper::createDefaultWarehouseIfNotExists();
    	$query = Warehouse::find()->where(['is_active'=>'Y']);
    	
    	//读取是否显示海外仓仓库
    	$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
    	if(empty($is_show))
    		$is_show = 0;
    	//不显示海外仓仓库
    	if($is_show == 0)
    	{
    		$query = $query->andWhere(['is_oversea' => '0']);
    	}
    	
    	//不显示已删除的仓库
    	$query = $query->andWhere("is_active != 'D' and name!='无'");
    	
    	$warehouseData = $query
    	->asArray()
    	->all();
    	 
    	$warehouse = array();
    	foreach($warehouseData as $w){
    		$warehouse[$w['warehouse_id']]=$w['name'];
    	}
    	return $warehouse;
    }
    
    /**
     * get prodStatus array
     * @return array(
     * 				"$status"=>"$statusWebDisplay"
     * 			)
     */
    protected static function prodStatus(){

    	$ProductStatus = ProductHelper::getProductStatus();
    	return $ProductStatus;
    }

    /**
     * get prodType array
     * @return array(
     * 				"$type"=>"$typeWebDisplay"
     * 			)
     */
    protected static function prodType(){
    
    	$ProductTypes = ProductHelper::getProductType();
    	return $ProductTypes;
    }
     
    /**
     * List all inventory models.
     * @return mixed
     */
    public function actionIndex()
    {   
        WarehouseHelper::createDefaultWarehouseIfNotExists();
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	$page = isset($_GET['page'])?$_GET['page']:1;
    	$completesort='';

        if(!empty($_GET['sort'])){
        	$sort = $completesort= $_GET['sort'];
        	if( '-' == substr($sort,0,1) ){
        		$sort = substr($sort,1);
        		$order = 'desc';
        	} else {
        		$order = 'asc';
        	}
        }else{
        	$sort = 'sku';
        	$order = 'asc';
        }
        
        $sortConfig = new Sort(['attributes' => ['sku','name','prod_name_ch','status','class_id','type','warehouse_id','location_grid','qty_in_stock','qty_purchased_coming','qty_ordered','qty_order_reserved','average_price', 'stock_total','safety_stock']]);
        if(!in_array($sort, array_keys($sortConfig->attributes))){
        	$sort = '';
        	$order = '';
        }
 
        $product_type = "";
        if(!empty($_GET['product_type'])) $product_type = $_GET['product_type'];
        $params = array();
        foreach ($_GET as $k=>$v){
        	if ($k == 'class_id'){
        		if (isset($v) && $v != ''){
        			$class_id_list = array();
        			$class_id_list[] = $v;
        			//查询此节点、及其下级节点
        			$node = ProductClassification::findOne(['ID' => $v]);
        			if(!empty($node)){
        				$query = ProductHelper::GetProductClassQuery();
        				$nodes = $query->where("substring(number, 1, length('".$node['number']."'))='".$node['number']."'")->asArray()->all();
        				foreach($nodes as $val){
        					$class_id_list[] = $val['ID'];
        				}
        			}
        			
        			$params[$k] = implode(',', $class_id_list);
        		}
        	}
        	else if ($k !=='sort' && $k !== 'order' && $k !=='keyword' && $k !=='per-page'  && $k !=='page' && $k !=='type' && $k!=='product_type'){
        		$params[$k] = $v; 
        	}
        }
        if(!empty($_GET['keyword'])){
            $keyword = $_GET['keyword'];
            $keyword = trim(str_ireplace(array("\r\n", "\r", "\n", "chr(10)", "chr(13)", "CHR(10)", "CHR(13)"), '', $keyword));
            
            $params['search_sku'] = $keyword;
            //查询对应的主SKU
            $root_sku = ProductApiHelper::getRootSKUByAlias($keyword);
            if(!empty($root_sku))
                $keyword = $root_sku;
        }
        else{
            $keyword = '';
        }

        if(!empty($product_type)){
        	$params['search_keyword']=$keyword;
        	$data = InventoryHelper::listBundleProductStockageData($page, $pageSize, $sort, $order, $params);
        	//exit (print_r($data));
        }else 
        	$data = InventoryHelper::listProductStockageData( $keyword,$params, $sort , $order ,$page, $pageSize );

        //筛选内容
        $search_condition['type'] = 'search contidion';
        $search_condition['keyword'] = $keyword;
        $search_condition['product_type'] = isset($_GET['product_type']) ? $_GET['product_type'] : '';
        $search_condition['params'] = $params;
        $search_condition['sort'] = $sort;
        $search_condition['order'] = $order;
        $search_condition['count'] = $data['pagination']->totalCount;
        $search_condition = json_encode($search_condition);
        $search_condition = base64_encode($search_condition);

        return $this->render('index', 
			        		['stockData' => $data, 
			        		'sort'=>$sortConfig,
			        		'warehouse'=>self::activeWarehouse(),
			        		'prodStatus'=>self::ProdStatus(),
			        		'prodTypes'=>self::prodType(),
        					'search_condition'=>$search_condition,
			        		]);
    }

    /**
     *List Inventory history 
	 *@param	$sku,$warehouseId,$stade,$edate
	 *@return	mixed
     */
    public function actionGetinventoryhistory()
    {
    	if (!isset($_REQUEST['sku']) && !isset($_REQUEST['warehouse_id'])){
    		$sku = '';
    		$warehouse_id = '';
    	}else{
    		$sku = $_REQUEST['sku'];
    		$warehouse_id = $_REQUEST['warehouse_id'];
    	}
    	\Yii::$app->request->setQueryParams($_REQUEST);
    	
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	$page = isset($_GET['page'])?$_GET['page']:1;
    	if(isset($_REQUEST['sort'])){
    		$sort = $_REQUEST['sort'];
    		if( '-' == substr($sort,0,1) ){
    			$sort = substr($sort,1);
    			$order = 'desc';
    		} else {
    			$order = 'asc';
    		}
    	}else{
    		$sort = 'create_time';
    		$order = 'desc';
    	}
    	
    	$sortConfig = new ESort(['isAjax'=>true, 'attributes' => ['create_time','change_type','stock_change_id','reason','snapshot_qty','capture_user_id']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	
    	$params=array();
    	if(isset($_REQUEST['reason']))
    		$params['reason'] = $_REQUEST['reason'];
    	if(!empty($_REQUEST['sdate']))
    		$params['sdate'] = $_REQUEST['sdate'];
    	if(!empty($_REQUEST['edate']))
    		$params['edate'] = $_REQUEST['edate'];

    	//$data =  InventoryHelper::getInventoryHistory($page, $pageSize, $sort, $order, $sku,$warehouse_id, $reason, $sdate, $edate);
    	$data = InventoryHelper::listOneProductStockChangeHistoryData($page, $pageSize, $sort, $order, $sku, $warehouse_id,$params);
    	return $this->renderAjax('_historyViews',
					    			['sku'=>$sku,
    								'warehouse_id'=>$warehouse_id,
    								'history' => $data,
    								'sort'=>$sortConfig,
					        		'warehouse'=>self::warehouse(),
					        		'stockChangeType'=>InventoryHelper::getStockChangeType(),
					        		'stockChangeReason'=>InventoryHelper::getStockChangeReason(),
					        		]);
    	
    }
    
    /**
     *List Inventory history by Condition
     *@param	$sku,$warehouseId,$stade,$edate
     *@return	mixed
     */
    public function actionGetinventoryHistoryByCondition()
    {   
		if (!isset($_GET['sku']) && !isset($_GET['warehouse_id'])){
    		$sku = '';
    		$warehouse_id = '';
    	}else{
    		$sku = $_GET['sku'];
    		$warehouse_id = $_GET['warehouse_id'];
    	}
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	$page = isset($_GET['page'])?$_GET['page']:1;
    	if(isset($_GET['sort'])){
    		$sort = $_GET['sort'];
    		if( '-' == substr($sort,0,1) ){
    			$sort = substr($sort,1);
    			$order = 'desc';
    		} else {
    			$order = 'asc';
    		}
    	}else{
    		$sort = 'create_time';
    		$order = 'asc';
    	}
    	
    	$sortConfig = new ESort(['isAjax'=>true, 'attributes' => ['create_time','change_type','stock_change_id','reason','capture_user_id']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	$params=array();
    	if(isset($_GET['reason']))
    		$params['reason'] = $_GET['reason'];
    	if(isset($__GET['sdate']))
    		$params['sdate'] = $_GET['sdate'];
    	if(isset($_GET['edate']))
    		$params['edate'] = $_GET['edate'];
    	
    	//if(isset($_GET['reason'])){
    	//	$reason = $_GET['reason'];
    	//}else $reason = '';
    	
    	//if(isset($__GET['sdate']))
    	//	$sdate = $_GET['sdate'];
    	//else $sdate = '';
    	//if(isset($_GET['edate']))
    	//	$edate = $_GET['edate'];
    	//else $edate = '';
		
    	$data =  InventoryHelper::listOneProductStockChangeHistoryData($page, $pageSize, $sort, $order, $sku,$warehouse_id, $params);
    	
    	return $this->renderPartial('_oneHistoryDetail',
    			['sku'=>$sku,
    			'warehouse_id'=>$warehouse_id,
    			'history' => $data,
    			'sort'=>$sortConfig,
    			'warehouse'=>self::warehouse(),
    			'stockChangeType'=>InventoryHelper::getStockChangeType(),
    			'stockChangeReason'=>InventoryHelper::getStockChangeReason(),
    			]);
    }
    
    /**
     * Inventory StockChange List Views
     * @param		
     * return		mixed
     */
    public  function actionStockchange()
    {
    	//处理参数
    	$stockChangeType = isset($_GET['stockChangeType'])?$_GET['stockChangeType']:'';
    	$warehouse_id = isset($_GET['warehouse_id'])?$_GET['warehouse_id']:'';
    	$sdate = isset($_GET['sdate'])?$_GET['sdate']:'';
    	$edate = isset($_GET['edate'])?$_GET['edate']:'';
    	$keyword = isset($_GET['keyword'])?$_GET['keyword']:'';
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	$page = isset($_GET['page'])?$_GET['page']:1;
    	if(isset($_GET['sort'])){
    		$sort = $_GET['sort'];
    		if( '-' == substr($sort,0,1) ){
    			$sort = substr($sort,1);
    			$order = 'desc';
    		} else {
    			$order = 'asc';
    		}
    	}else{
    		$sort = 'create_time';
    		$order = 'desc';
    	}
    	$sortConfig = new Sort(['attributes' => ['create_time','change_type','stock_change_id','reason','warehouse_id','capture_user_id','comment']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	
    	$DataList = InventoryHelper::getStockChangeDataList($stockChangeType,$warehouse_id,$keyword,$sdate,$edate,$page,$pageSize,$sort,$order);
    	
    	return $this->render('stockChange',[
    						'sort'=>$sortConfig,
    						'data'=>$DataList,
    						'warehouse'=>self::activeWarehouse(),
    						'stockChangeType'=>InventoryHelper::getStockChangeType(),
    						'stockChangeReason'=>InventoryHelper::getStockChangeReason(),
    				]);
    	
    }

    /**
     * Inventory show one StockChange Detail
     * @param		$_GET['id']			出入库单id
     * return		mixed
     */
    public  function actionShowchangedetail()
    {
    	if(!isset($_GET['id'])) $data = array();
    	else $id = base64_decode(str_replace(" ","+",$_GET['id']));
    	
    	$data = InventoryHelper::getStockChangeDetailData($id);
    	
    	return $this->renderPartial('_stockChangeDetail',[
    				'data'=>$data
    			]);
    }
    
    /**
     * Inventory StockIn views
     * @param		
     * return		mixed
     */
    public  function actionStock_in()
    {
    	return $this->render('stockChange',[
    						'warehouse'=>self::activeWarehouse(),
    						'prodStatus'=>self::ProdStatus(),
    						'stockChangeType'=>InventoryHelper::getStockChangeType(),
    						'stockChangeReason'=>InventoryHelper::getStockChangeReason(),
    				]);
    }
    
    /**
     * Inventory create new StockIn
     * @param		mixed
     * return		mixed
     */
    public  function actionCreate_stockin()
    {
    	if(!isset($_POST['stock_in_json']) || empty($_POST['stock_in_json']))
    	{	
    		$rtn = array('success'=>false,'message'=>'<b style="color:red;">'.TranslateHelper::t('无数据传输至后台，请重新填写信息！').'</b>');
    		exit(json_encode($rtn));
    	}else{
    		$result = json_decode($_POST['stock_in_json'],true);
    		
    		$rtn = InventoryHelper::createNewStockIn($result);
    		exit(json_encode($rtn));
    	}
    }
    
    /**
     * Inventory StockOut views
     * @param
     * return		mixed
     */
    public  function actionStock_out()
    {
    	return $this->render('stockChange',[
    			'warehouse'=>self::activeWarehouse(),
    			'prodStatus'=>self::ProdStatus(),
    			'stockChangeType'=>InventoryHelper::getStockChangeType(),
    			'stockChangeReason'=>InventoryHelper::getStockChangeReason(),
    			]);
    }
    
    /**
     * Inventory create new StockOut
     * @param		mixed
     * return		mixed
     */
    public  function actionCreate_stockout()
    {
    	if(!isset($_POST) || empty($_POST))
    	{
    		$rtn = array('success'=>false,'message'=>TranslateHelper::t('无数据传输至后台，请重新填写信息！'));
    		exit(json_encode($rtn));
    	}else{
    		$info = $_POST;
    		$rtn = InventoryHelper::createNewStockOut($info);
    		exit(json_encode($rtn));
    	}
    	 
    	 
    }


    public function actionGetProductInventory(){
    	if (!isset($_GET["skus"])) {
    		echo "fail! Purchaseid must be specified";
    		return;
    	}
    
    	//$skuArr=explode("",$_GET["skus"]);
    	$skuArr = $_GET["skus"];
    	$warehouseId=$_GET["warehouseId"];
    	$results = array();
    	foreach ($skuArr as $sku){
    		$sku=trim($sku);
    		if ($sku == '') continue;
    		$result = InventoryHelper::getProductInventory($sku,$warehouseId);
    		if ($result <> null)
    			$results[] = $result;
    	}
    
    	echo json_encode(array("success"=>"true","result"=>$results));
    }
/**
 * get product's stockage by all warehouse
 * for view prod's qty_in_stock when creat a stockchange 
 * @return		array
 */
    public function actionProduct_all_stockage(){
    	$result = array();
    	if (!isset($_GET['sku']) || trim($_GET['sku'])=='') {
    		exit( json_encode($result) );
    	}

    	$stockage = InventoryHelper::getProductAllInventory(trim($_GET['sku']));
    	if ($stockage <> null){
    			$result = $stockage;
    	}
    	exit( json_encode($result) );
    }

    /**********************************************************************************
     * input parm: POST:purchase_arrival_id
    *				It can be an ID, number
    * Description: This is to open a capture screen for use to capture a Purchase Stock In record,
    ***********************************************************************************/
    public function actionPurchaseStockIn($purchase_arrival_id=''){
    	//step 0, if no ids, get it from GET
    	if ($purchase_arrival_id == '')
    		$purchase_arrival_id = $_REQUEST['purchase_arrival_id'];
    
    	//Step 1: Load the purchase data
    	$sql="SELECT b.*,a.name as supplier_name ,w.name as warehouse_name ,ar.status as purchase_arrival_status
				FROM wh_warehouse w, pd_supplier a, pc_purchase b ,pc_purchase_arrivals ar
				where b.supplier_id = a.supplier_id and ar.purchase_id = b.id and
				w.warehouse_id = b.warehouse_id and purchase_arrival_id=:purchase_arrival_id";
    
    	$command = Yii::$app->get('subdb')->createCommand($sql);
    	$command->bindValue(":purchase_arrival_id", $purchase_arrival_id, \PDO::PARAM_STR);
    	$purchase_data = $command->queryRow(); //return 1 row in array format

    	//step 2: put the info to view
    	$this->renderPartial('purchase_stock_in_capture',array(
    			'purchase_arrival_id'=>$purchase_arrival_id,
    			'purchase_data' => $purchase_data,
    			'purchase_arrival_details' =>InventoryHelper::listPendingStockInDetailData($purchase_arrival_id,true) //2nd parm, if load inventory info or not, e.g. location_grid
    	) , false, true);
    }//end of action actionStockIn   
    
    /**********************************************************************************
     * input parm: POST: purchase stock in details
    *
    * Description: This is to recieve the form and update the purchase arrival stock in record, also the status
    ***********************************************************************************/
    public function actionCreatePurchaseStockIn(){
    	if(!empty($_POST)) {
    		$rtn = InventoryHelper::insertPurchaseStockIn($_POST) ;
    	}/*
    	else
    		SysLogHelper::SysLog_Create("inventory",__CLASS__, __FUNCTION__,"YS add 1","post is empty", "Error");
    	*/
    	exit( json_encode($rtn) );
    }//end of action actionStockIn
    
    public function actionStockChangeList_data(){
    	$page = !empty($_POST['page']) ? $_POST['page'] : 1;
    	$rows = !empty($_POST['rows']) ? $_POST['rows'] : 10;
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'update_time';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'desc';
    	//queryString
    	$queryString = array();
    	if(isset($_POST['warehouse_id']) && is_numeric($_POST['warehouse_id']))
    	{
    		$queryString['warehouse_id'] = $_POST['warehouse_id'];
    	}
    	if(!empty($_POST['change_type']))
    	{
    		$queryString['change_type'] = $_POST['change_type'];
    	}
    	if(!empty($_POST['search_keyword']))
    	{
    		$queryString['keyword'] = $_POST['search_keyword'];
    	}
    	if(!empty($_POST['date_from']))
    		$queryString['date_from'] = $_POST['date_from'];
    
    	if(!empty($_POST['date_to']))
    		$queryString['date_to'] = $_POST['date_to'];
    
    	exit(json_encode(
    			InventoryHelper::listStockChangeData($page, $rows, $sort, $order, $queryString)
    	));
    }

    /**
    *Inventory StockTake index/list Views
    *@return 		mixed
    */
    public function actionStocktake()
    {
    	//处理参数
    	$queryString = array();
    	if(isset($_GET['warehouse_id']) and is_numeric($_GET['warehouse_id']))
    		$queryString['warehouse_id'] = $_GET['warehouse_id'];
    	if(!empty($_GET['sdate']))
    		$queryString['date_from'] = $_GET['sdate'];
    	if(!empty($_GET['edate']))
    		$queryString['date_to'] = $_GET['edate'];
    	if(!empty($_GET['keyword']))
    		$queryString['keyword'] = $_GET['keyword'];
    	
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	$page = isset($_GET['page'])?$_GET['page']:1;
    	if(isset($_GET['sort'])){
    		$sort = $_GET['sort'];
    		if( '-' == substr($sort,0,1) ){
    			$sort = substr($sort,1);
    			$order = 'desc';
    		} else {
    			$order = 'asc';
    		}
    	}else{
    		$sort = 'create_time';
    		$order = 'asc';
    	}
    	$sortConfig = new Sort(['attributes' => ['create_time','stock_take_id','warehouse_id','capture_user_id','number_of_sku']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	 
    	$List = StockTakeHelper::listStockTakeData($page, $pageSize, $sort, $order, $queryString);
    	 
    	return $this->render('stockTake',[
    			'sort'=>$sortConfig,
    			'data'=>$List,
    			'warehouse'=>self::activeWarehouse(),
    			]);
    }

    /**
     *Inventory StockTake detail Views
     *@return 		mixed
     */
    public function actionStocktake_detail()
    {
    	if(isset($_REQUEST['id']) && trim($_REQUEST['id']!=='')){
    		$stock_tack_id = $_REQUEST['id'];
    		$detail = StockTakeHelper::getStockTakeDetailData($stock_tack_id);
    	}else{
    		$detail= array();
    	}
    	return $this->renderPartial('_stockTakeDetail',[
    					'detail'=>$detail,
    			]);
    }
    
    /**
     *Inventory StockTake 
     *Create new StockTake html
     *@return 		mixed
     */
    public function actionCreate_stocktake()
    {
    	return $this->renderAjax('_newStocktake',[
    			'warehouse'=>self::activeWarehouse(),
    			'prodStatus'=>self::ProdStatus(),
			    'prodTypes'=>self::prodType(),
    			]);
    	
    }
    
    /**
     *Inventory StockTake
     *action to save StockTake
     *@return 		array
     */
    public function actionSave_stocktake()
    {
        	if(!isset($_POST) || empty($_POST))
    	{
    		$rtn = array('success'=>false,'msg'=>TranslateHelper::t('无数据传输至后台，请重新填写信息！'));
    		exit(json_encode($rtn));
    	}else{
    		$info = $_POST;
    		try{
    			$rtn = StockTakeHelper::insertStockTake($info);
    		}catch (\Exception $e){
    			$rtn = array('success'=>false,'msg'=>$e->getMessage());
    		}
    		exit(json_encode($rtn));
    	}
    	 
    	 
    }
    /**
     *show selected prodinfo table html 
     *@return 		mixed
     */
    public function actionSelectedprod_tb_html()
    {
    	if(!isset($_POST) || empty($_POST))
    	{
    		$this->renderAjax('_prodList_table',[
								'actionId'=>'',
								'prods'=>array(),
							]);
    	}else{
    		$this->renderAjax('_prodList_table',[
								'actionId'=>'',
								'prods'=>array(),
							]);
    	}
    
    
    }

    /**
     * excel 导入 出入库  产品
     * @return		json array
     */
    public function actionImportStockChangeProdsByExcel(){
    	//$this->changeDBPuid();
    	$warehouse_id = $_REQUEST['warehouse_id'];
    	$changeType = $_REQUEST['changeType'];
    	$result=array();
    	
    	try {
    		// excel 导入
    		if (! empty($_FILES['input_import_file'])){
    			$result = InventoryHelper::importStockChangeProdsByExcel($_FILES['input_import_file'] ,$warehouse_id,$changeType);
    		}else{
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('文件导入失败，请联系客服');
    		}
    
    	} catch (Exception $e) {
    		$result ['success'] = false;
    		$result ['message'] = TranslateHelper::t($e->getMessage());
    	}
    	/*由于并非上传后直接后台insert，因此此操作不需要mark log
    	$puid1 = \Yii::$app->subdb->getCurrentPuid();
    	$message = "UATL:用户 $puid1 Import StockChange By Excel";
    	\Yii::info(['Inventory',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
    	*/
    	exit(json_encode($result,JSON_HEX_TAG));
    	 
    }//end of actionImportStockChangeProdsByExcel
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 赛兔 excel导入 出入库  产品
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/3/17				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionImportSellertoolStockChangeProdsByExcel(){
    	//$this->changeDBPuid();
    	$warehouse_id = $_REQUEST['warehouse_id'];
    	$changeType = $_REQUEST['changeType'];
    	$result=array();
    	 
    	try {
    		// excel 导入
    		if (! empty($_FILES['input_import_file'])){
    			$result = InventoryHelper::importStockChangeProdsByExcel($_FILES['input_import_file'] ,$warehouse_id,$changeType,'sellertool');
    		}else{
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('文件导入失败，请联系客服');
    		}
    
    	} catch (Exception $e) {
    		$result ['success'] = false;
    		$result ['message'] = TranslateHelper::t($e->getMessage());
    	}
    	/*由于并非上传后直接后台insert，因此此操作不需要mark log
    	 $puid1 = \Yii::$app->subdb->getCurrentPuid();
    	$message = "UATL:用户 $puid1 Import StockChange By Excel";
    	\Yii::info(['Inventory',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
    	*/
    	exit(json_encode($result,JSON_HEX_TAG));
    
    }//end of actionImportStockChangeProdsByExcel

    /**
     * 复制粘贴 excel格式文本 导入  出入库  产品
     * @return		json array
     */
    public function actionImportStockChangeProdsByExcelFormatText(){
    	//$this->changeDBPuid();
    	$warehouse_id = $_REQUEST['warehouse_id'];
    	$changeType = $_REQUEST['changeType'];
    	$result=array();
    	
    	if (! empty($_POST['prodsList'])){
    	
    		if (empty($_POST['data_type'])){
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('导入出现错误, 请联系技术人员修正问题');
    			exit(json_encode($result));
    		}else{
    			if (strtolower($_POST['data_type']) == 'json' ){
    				$Datas = json_decode($_POST['prodsList'],true);
    				$prodDatas = [];
    				for($i=0;$i<count($Datas);$i++){
    					if(trim($Datas[$i][0])=='')
    						continue;
    					$prodDatas[]=array('sku'=>$Datas[$i][0],'stockchange_qty'=>$Datas[$i][1],'location_grid'=>$Datas[$i][2]);
    				}
    				$result = InventoryHelper::importStockChangeProdsByExcelFormatText($prodDatas,$warehouse_id,$changeType);
    			}else{
    				$result ['success'] = false;
    				$result ['message'] = TranslateHelper::t('导入出现错误, 请联系技术人员修正问题');
    				exit(json_encode($result));
    			}
    		}
    	}

    	/*由于并非上传后直接后台insert，因此此操作不需要mark log
    	 $puid1 = \Yii::$app->subdb->getCurrentPuid();
    	$message = "UATL:用户 $puid1 Import StockChange By Excel";
    	\Yii::info(['Inventory',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
    	*/
    	exit(json_encode($result,JSON_HEX_TAG));
    
    }//end of actionImportStockChangeProdsByExcel

    /**
     * Lists all arrived purchase order list.
     * @return mixed
     */
    public function actionArrivedPurchaseList()
    {
    	if(!empty($_GET['sort'])){
    		$sort = $_GET['sort'];
    		if( '-' == substr($sort,0,1) ){
    			$sort = substr($sort,1);
    			$order = 'desc';
    		} else {
    			$order = 'asc';
    		}
    	}else{
    		$sort = 'create_time';
    		$order = 'desc';
    	}
    	$sortConfig = new ESort(['isAjax'=>true, 'attributes' => ['purchase_order_id','warehouse_id','create_time']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	 
    	$params = array();
    	foreach ($_GET as $k=>$v){
    		if ($k !=='sort' && $k !== 'order' ){
    			$params[$k] = $v;
    		}
    	}
    	$params['status'] = 3;//限定查询结果只能为‘全部到货’状态
    	 
    	$data = PurchaseHelper::getListDataByCondition($sort,$order,$params );
    	 
    	return $this->renderAjax('_purchase_arrived_order_list', [
    			'purchaseListData' => $data,
    			'sort'=>$sortConfig,
    			'suppliers'=>SupplierHelper::ListSupplierData(),
    			'warehouse'=>PurchaseHelper::warehouse(),
    			'purchaseStatus'=>PurchaseHelper::getPurchaseStatus(),
    			]);
    }
    
    /**
     * return an arrived purchase order detail.
     * @return mixed
     */
    public function actionArrivedPurchaseInfo($id)
    {
    	if(trim($id)=='')
    		exit ( json_encode(array('message'=>TranslateHelper::t('采购单号丢失,请重试'))) );
    	$purchase = Purchase::find()->where(['id'=>$id])->asArray()->one();
    	if($purchase == null)
    		exit ( json_encode(array('message'=>TranslateHelper::t('采购单号不存在'))) );
    	
    	$result=array();
    	$result['data']=$purchase;
    	$id = $purchase['id'];
    	$warehous_id = $purchase['warehouse_id'];
    	$items = PurchaseItems::find()->where(['purchase_id'=>$id])->asArray()->all();
    	foreach ($items as $index=>$item){
    		$sku=$item['sku'];
    		$sql = "SELECT status,photo_primary FROM pd_product WHERE sku='$sku'";
			$p = Yii::$app->get('subdb')->createCommand($sql)->queryOne();
			$items[$index]['status']=$p['status'];
			$items[$index]['photo_primary']=$p['photo_primary'];
			
			$sql = "SELECT location_grid FROM wh_product_stock WHERE sku='$sku' and warehouse_id ='$warehous_id' ";
    		$s = Yii::$app->get('subdb')->createCommand($sql)->queryOne();
    		$items[$index]['location_grid']=$s['location_grid'];
    		$items[$index]['stock_in_qty']=$items[$index]['qty'];
    	}
    	$result['items']=$items;
    	$result['message']='';
    	
    	exit (json_encode($result));
    }

    /**
     +---------------------------------------------------------------------------------------------
     *	 仓库导出直接导出
     +---------------------------------------------------------------------------------------------
     * @param      @type 导出类型
     * 			   @str  导出数据
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lgw		2017/1/4				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionStraightExportExcel(){
    	AppTrackerApiHelper::actionLog("catalog", "/inventory/inventory/straight-export-excel");
    	$product_ids = empty($_REQUEST['product_ids'])?'':trim($_REQUEST['product_ids']);
    	$type = empty($_REQUEST['type'])?0:trim($_REQUEST['type']);

    	if($type == 0){
    		$product_list = rtrim($product_ids, ',');
    	}
    	else{
	    	$product_ids = $product_ids == base64_encode(base64_decode($product_ids)) ? base64_decode($product_ids) : $product_ids;
	    	$product_list = json_decode($product_ids,true);
    	}
    	
    	
    	InventoryHelper::ExportExcelAll($product_list,true);
    }
	 /**
     +---------------------------------------------------------------------------------------------
     *	 仓库导出插入导出队列
     +---------------------------------------------------------------------------------------------
     * @param      @type 导出类型
     * 			   @str  导出数据
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lgw		2017/1/4				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionAddExportExcel(){
    	$type = empty($_POST['type']) ? '' : $_POST['type'];
    	$str = empty($_POST['str']) ? '' : $_POST['str'];

    	$product_ids = array();
    	if($type == 0){
    		$product_id_list = rtrim($str, ',');
    		$product_ids = json_encode($product_id_list);
    	}
    	else{
    		$product_ids = $str;
    	}

    	$className = addslashes('\eagle\modules\inventory\helpers\InventoryHelper');
    	$functionName = 'ExportExcelAll';
    	    	    	
    	$rtn = ExcelHelper::insertExportCrol($className, $functionName, $product_ids, 'export_inventory_list');

    	if($rtn['success'] == 1){
    		return $this->renderPartial('_downloadexcel',[
    				'pending_id'=>$rtn['pending_id'],
    				]);
    	}
    	else{
    		return $rtn['message'];
    	}
    }
    
    /**
     +----------------------------------------------------------
     * 查询已导出Excel的路劲
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lgw		  2017/01/04		初始化
     +----------------------------------------------------------
     **/
    public function actionGetExcelUrl(){
    	$pending_id = empty($_POST['pending_id']) ? '0' : $_POST['pending_id'];

    	return ExcelHelper::getExcelUrl($pending_id);
    }
    
    
    /**
     +---------------------------------------------------------------------------------------------
     * sku扫描出入库扫描界面
     +---------------------------------------------------------------------------------------------
     * log			name		date					note
     * @author		lgw 		2016/12/6				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionScanningStocklist(){
    	return $this->renderPartial('_scanningStockInlist');
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * sku扫描出入库保存到保存界面
     +---------------------------------------------------------------------------------------------
     * @param     $val	sku
     * @param     post
     +---------------------------------------------------------------------------------------------
     * @return   json_encode(['code'=>'0', 'data'=>array(id,sku,prod_name,photo_primary,type)])
     +---------------------------------------------------------------------------------------------
     * log			name		date					note
     * @author		lgw 		2016/12/6				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionSkuStockSave(){
    	$sku=empty($_REQUEST['val'])?'':$_REQUEST['val'];
    	$stockage = ProductHelper::getProductBySku($sku);
//     	print_r($stockage);die;
    	
    	$data=array(
    			'id'=>empty($stockage['product_id'])?'0':$stockage['product_id'],
    			'sku'=>empty($stockage['sku'])?'':$stockage['sku'],
    			'prod_name'=>empty($stockage['name'])?'':$stockage['name'],
    			'photo_primary'=>empty($stockage['photo_primary'])?'':$stockage['photo_primary'],
    			'type'=>empty($stockage['type'])?'':$stockage['type'],
    	);
    	return json_encode(['code'=>'0', 'data'=>$data]);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 删除库存信息
     +---------------------------------------------------------------------------------------------
     * log			name		date					note
     * @author		lrq 		2017/07/03				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionDeleteStock(){
    	if(empty($_REQUEST['stock_id_list'])){
    		return json_encode(['success' => '0', 'msg' => '不存在此库存信息！']);
    	}
    	
    	$stock_id_arr = explode(',', $_REQUEST['stock_id_list']);
    	$ret = InventoryHelper::DeleteStock($stock_id_arr);
    	
    	return json_encode($ret);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 打开库存、单价编辑信息
     +---------------------------------------------------------------------------------------------
     * log			name		date					note
     * @author		lrq 		2017/08/04				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionShowEditStock(){
        if(empty($_POST['stock_id'])){
            return '库存信息已不存在！';
        }
         
        $data = InventoryHelper::listProductStockageData('', ['prod_stock_id' => $_POST['stock_id']], '' , '' ,1, 200);
        if(empty($data['data'][0])){
            return '库存信息已不存在！';
        }
    	return $this->renderPartial('_editStock', [
    	            'stock' => $data['data'][0],
    	            'warehouse'=>self::activeWarehouse(),
    				'show_time' => time(),
    	        ]
            );
    }
    
    /**
    +---------------------------------------------------------------------------------------------
    * 保存单个库存信息
    +---------------------------------------------------------------------------------------------
    * log			name		date					note
    * @author		lrq 		2017/08/07				初始化
    +---------------------------------------------------------------------------------------------
    **/
    public function actionSaveOneStock(){
        if(empty($_POST['stock_id'])){
        	return '库存信息已不存在！';
        }
        
        $ret = InventoryHelper::SaveOneStock($_POST);
        return json_encode($ret);
    }
    
    /**
    +---------------------------------------------------------------------------------------------
    * 打开批量编辑
    +---------------------------------------------------------------------------------------------
    * log			name		date					note
    * @author		lrq 		2017/08/07				初始化
    +---------------------------------------------------------------------------------------------
    **/
    public function actionShowBatchEditStock()
    {
    	$stock_id_list = empty($_POST['stock_id_list']) ? '' : rtrim($_POST['stock_id_list'], ',');
    	$data = InventoryHelper::listProductStockageData('', ['prod_stock_id' => $stock_id_list], 'sku' , 'asc' ,1, 200);
    	 
    	return $this->renderPartial('_batchEditStock',[
    			    'list' => $data['data'],
    	            'warehouse'=>self::activeWarehouse(),
    				'show_time' => time(),
    	        ]);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 批量保存库存信息
     +---------------------------------------------------------------------------------------------
     * log			name		date					note
     * @author		lrq 		2017/08/07				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionBathSaveStock(){
    	$ret = InventoryHelper::BathSaveStock($_POST);
    	return json_encode($ret);
    }
    
    //更新在途数量
    public function actionUpdatePurchased(){
    	$sql = "Update `wh_product_stock` set qty_purchased_coming=ifnull((select sum(case when ifnull(qty,0)-ifnull(in_stock_qty,0)>0 then ifnull(qty,0)-ifnull(in_stock_qty,0) else 0 end) from pc_purchase_items where sku=wh_product_stock.sku and purchase_id in (select id from pc_purchase where status>=1 and status<5 and warehouse_id=wh_product_stock.warehouse_id)),0)";
    	$command = Yii::$app->get('subdb')->createCommand($sql);
    	$command->execute();
    }
    
    /**
     +------------------------------------------------------
     * 仓库调拨列表
     +------------------------------------------------------
     * log			name		date			note
     * @author		lrq 		2017/12/13		初始化
     +------------------------------------------------------
     **/
    public function actionStockAllocation(){
    	//处理参数
    	$queryString = array();
    	if(isset($_GET['in_warehouse_id']) and is_numeric($_GET['in_warehouse_id']))
    		$queryString['in_warehouse_id'] = $_GET['in_warehouse_id'];
    	if(isset($_GET['out_warehouse_id']) and is_numeric($_GET['out_warehouse_id']))
    		$queryString['out_warehouse_id'] = $_GET['out_warehouse_id'];
    	if(!empty($_GET['sdate']))
    		$queryString['date_from'] = $_GET['sdate'];
    	if(!empty($_GET['edate']))
    		$queryString['date_to'] = $_GET['edate'];
    	if(!empty($_GET['keyword']))
    		$queryString['keyword'] = $_GET['keyword'];
    	 
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	$page = isset($_GET['page'])?$_GET['page']:1;
    	if(isset($_GET['sort'])){
    		$sort = $_GET['sort'];
    		if( '-' == substr($sort,0,1) ){
    			$sort = substr($sort,1);
    			$order = 'desc';
    		} else {
    			$order = 'asc';
    		}
    	}else{
    		$sort = 'create_time';
    		$order = 'desc';
    	}
    	$sortConfig = new Sort(['attributes' => ['create_time','stock_allocatione_id','out_warehouse_id', 'in_warehouse_id','capture_user_id','number_of_sku']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    
    	$List = InventoryHelper::listStockAllocationData($page, $pageSize, $sort, $order, $queryString);
    
    	return $this->render('stockAllocation',[
    		'sort'=>$sortConfig,
    		'data'=>$List,
    		'warehouse'=>self::activeWarehouse(),
    	]);
    }
    
    /**
     +------------------------------------------------------
     * 仓库调拨明细
     +------------------------------------------------------
     * log			name		date			note
     * @author		lrq 		2017/12/13		初始化
     +------------------------------------------------------
     **/
    public function actionStockAllocationDetail(){
    	if(isset($_REQUEST['allocatione_id']) && trim($_REQUEST['allocatione_id']!=='')){
    		$allocatione_id = $_REQUEST['allocatione_id'];
    		$detail = InventoryHelper::getStockAllocationDetail($allocatione_id);
    	}else{
    		$detail= array();
    	}
    	return $this->renderPartial('_stockAllocationDetail',[
    		'detail'=>$detail,
    	]);
    }
    
    /**
     +------------------------------------------------------
     * 仓库调拨创建
     +------------------------------------------------------
     * log			name		date			note
     * @author		lrq 		2017/12/13		初始化
     +------------------------------------------------------
     **/
    public function actionCreateStockAllocation(){
    	return $this->renderAjax('_newStockAllocation',[
    		'warehouse'=>self::activeWarehouse(),
    		'stock_allocatione_id' => InventoryHelper::getStockAllocationId(),
    	]);
    }
    
    /**
     +------------------------------------------------------
     * 仓库调拨保存
     +------------------------------------------------------
     * log			name		date			note
     * @author		lrq 		2017/12/13		初始化
     +------------------------------------------------------
     **/
    public function actionSaveStockAllocation()
    {
    	if(!isset($_POST) || empty($_POST)){
    		return json_encode(['success'=>false, 'msg'=>'无数据传输至后台，请重新填写信息！']);
    	}
    	
    	$rtn = InventoryHelper::insertStockAllocation($_POST);
    	return json_encode($rtn);
    }
    
    /**
    +----------------------------------------------------------
    * 更新库存采购价，用商品采购价替换
    +----------------------------------------------------------
    * log			name		date			note
    * @author		lrq		  2018/05/30		初始化
    +----------------------------------------------------------
    **/
    public function actionUpdateStockPrice(){
    	InventoryHelper::UpdateStockPrice();
    }
    
    
}
