<?php
namespace eagle\modules\purchase\controllers;
use Yii;
use eagle\modules\purchase\models\purchase;
use eagle\modules\purchase\helpers\PurchaseHelper;
use eagle\modules\purchase\helpers\SupplierHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\inventory\models\ProductStock;
use eagle\modules\purchase\models\PurchaseSuggestion;
use eagle\modules\purchase\helpers\PurchaseSugHelper;

use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\purchase\models\PurchaseItems;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\TimeUtil;
use yii\base\Exception;
use yii\data\Sort;
use eagle\widgets\ESort;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\models\OdOrder;
/**
 +------------------------------------------------------------------------------
 * 采购建议控制类
 +------------------------------------------------------------------------------
 * @category	Purchase
 * @package		Controller/purchaseSug
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
*/
class PurchaseSugController extends \eagle\components\Controller{
	
	/**
	 * @return array action filters
	 */
	/*
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}
	*/
	
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	/*
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view','list','listdata','savepurchasepreviewids','modifystrategiesview','printSug','setstockstrategies'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	*/
	
	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new Purchase;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Purchase']))
		{
			$model->attributes=$_POST['Purchase'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->purchase_id));
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Purchase']))
		{
			$model->attributes=$_POST['Purchase'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->purchase_id));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * purchase sug index.
	 */
	public function actionSugindex()
	{
	    $params=[];
	    $params['skus'] = empty($_REQUEST['search_sku']) ? '' : $_REQUEST['search_sku'];
	    $params['warehouse_id'] = isset($_REQUEST['warehouse_id']) ? $_REQUEST['warehouse_id'] : '';
	    
	    if(!empty($params['skus']))
	    {
	    	$params['skus']  =str_replace('；', ';', $params['skus']);
	    	$params['skus'] = explode(';', $params['skus']);
	    }
	    $params['page'] = empty($_REQUEST['page']) ? 1 : $_REQUEST['page'];
	    $params['per-page'] = empty($_REQUEST['per-page']) ? 20 : $_REQUEST['per-page'];
		
		if(isset($_REQUEST['sort']) && !empty($_REQUEST['sort'])){
			$sort = $_REQUEST['sort'];
			if( '-' == substr($sort,0,1) ){
				$params['sort'] = substr($sort,1);
				$params['order'] = 'desc';
			} else {
				$params['sort'] = $sort;
				$params['order'] = 'asc';
			}
		}else{
			$params['sort'] = 'sku';
			$params['order'] = 'asc';
		}
			
		$sortConfig = new Sort(['attributes' => ['sku','purchaseSug','primary_supplier_name','purchase_price','warehouse_id']]);
		if(!in_array($params['sort'], array_keys($sortConfig->attributes))){
			$sort = '';
			$order = '';
		}
		
		//只读取缺货采购建议
		$datalist = PurchaseSugHelper::listDataByCondition_Q($params);
		return $this->render('sug_index',[
				'sugData'=>$datalist,
				'sort'=>$sortConfig,
				'warehouse'=>PurchaseHelper::warehouse(),
				'suppliers'=>'',
				'message'=>'',
				'calculateDate'=>'',
					]
				);
		//}
	}
	
	/**
	 * purchase sug_index_strategies.
	 */
	public function actionSugindexstrategies()
	{
	    $params = array();
		if(isset($_REQUEST)){
			foreach ($_REQUEST as $key=>$val){
				$params[$key] = $val;
			}
		}
		$params['suggest_type'] = '2';
			
		if(isset($params['sort']) && !empty($params['sort'])){
			$sort = $params['sort'];
			if( '-' == substr($sort,0,1) ){
				$params['sort'] = substr($sort,1);
				$params['order'] = 'desc';
			} else {
				$params['order'] = 'asc';
			}
		}else{
			$params['sort'] = 'sku';
			$params['order'] = 'asc';
		}
			
		$sortConfig = new Sort(['attributes' => ['sku','purchaseSug','primary_supplier_name','purchase_price']]);
		if(!in_array($params['sort'], array_keys($sortConfig->attributes))){
			$sort = '';
			$order = '';
		}
		//判断此用户核实进行过获取采购建议，如果是15分钟以内或去过，则不再重新计算。大于60分钟就重新计算
		//重新计算时，提示用户需要等待。

		$now = time();
		$hourAgo = $now-60*60;
		$needCalculate = false;
		$calculateDate='';
		
		$calculateInfo = ConfigHelper::getConfig("PurchaseSug/CalculatePurchaseSugDate",'NO_CACHE');
		if($calculateInfo != null){
			$info = json_decode($calculateInfo,true);
			if(isset($info['date'])){
				$calculateDate = $info['date'];
				if( strtotime($calculateDate) < $hourAgo )
					$needCalculate = true;
			}
			else
				$needCalculate = true;
		}else{
			$needCalculate = true;
		}

		$uid = \Yii::$app->user->id;
		if(empty($uid)){
			return $this->render('sug_index_strategies',[
					'sugData'=>[],
					'sort'=>$sortConfig,
					'warehouse'=>'',
					'suppliers'=>'',
					'message'=>'请先登录',
					'calculateDate'=>$calculateDate,
					]
			);
			
		}
		
		//需要计算建议的情况
		if($needCalculate){
			$create_time = date('Y-m-d H:i:s', time());
			$queue_sql = "select * from `pc_suggest_queue` where `puid` = $uid and `status`='P' ";
			$queue = Yii::$app->get('db_queue')->createCommand($queue_sql)->queryOne();
			if(empty($queue)){
				$sql = "INSERT INTO `pc_suggest_queue`
						( `puid`, `status`, `create_time`, `update_time`) VALUES 
						( $uid,'P','$create_time','$create_time')";
				$r = Yii::$app->get('db_queue')->createCommand($sql)->execute();
			}
			
			return $this->render('sug_index_strategies',[
					'sugData'=>[],
					'sort'=>$sortConfig,
					'warehouse'=>'',
					'suppliers'=>'',
					'message'=>'建议需要更新(上次建议早于60分钟之前)，后台正在处理，请过几分钟之后再打开此页面。需时根据您商品库的商品数量而异',
					'calculateDate'=>$calculateDate,
					]
			);
		}
		//不需要计算，直接读取数据
		else{
			$datalist = PurchaseSugHelper::listDataByCondition($params);
			return $this->render('sug_index_strategies',[
					'sugData'=>$datalist,
					'sort'=>$sortConfig,
					'warehouse'=>'',
					'suppliers'=>'',
					'message'=>'',
					'calculateDate'=>$calculateDate,
						]
					);
		}
	}

	/**
	 * purchase sug_strategies index.
	 */
	public function actionSug_strategies()
	{
	
		return $this->render('sug_strategies',[
				'data'=>'',
				'sort'=>'',
				'warehouse'=>'',
				'suppliers'=>'',
				]
		);
	}	
	
	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new Purchase('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Purchase']))
			$model->attributes=$_GET['Purchase'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}
	

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Purchase the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model = PurchaseSuggestion::find($id);
		if($model===null)
			throw new Exception(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Purchase $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='purchase-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
	
	


	/**
	 * open the purchase suggestion page
	 */	
	public function actionList()
	{
		$this->renderPartial('list', array(), false, true);
	}
	
	/**
	 * Returns all the suggestion products data for table datagrid element
	 * eagle 1.0
	 */
	public function  actionListData()
	{
//		$page = !empty($_POST['page']) ? $_POST['page'] : 1;
//		$rows = !empty($_POST['rows']) ? $_POST['rows'] : 10;
		//该页面暂时不需要做分页
		$page = false;
		$rows = false;
		$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'purchaseSug';
		$order = !empty($_POST['order']) ? $_POST['order'] : 'desc';

		//queryString
		$queryString = isset($_POST['query']) ? $_POST['query'] : array();
		
		if(!empty($_POST['category_id']) && !empty($_POST['includeChild']) && $_POST['includeChild'] == 'true')
		{
			$queryString[] =  array('name' => 'category_id', 'value' => CategoryHelper::getChildrenCategoryId($_POST['category_id']), 'condition' => 'in');
		}
		elseif (!empty($_POST['category_id'])) 
		{
			$queryString[] =  array('name' => 'category_id', 'value' => $_POST['category_id'], 'condition' => 'eq');
		}
		if (!empty($_POST['excludeSkuList'])) 
		{
			$queryString[] = array('name' => 'sku', 'value' => $_POST['excludeSkuList'], 'condition' => 'notIn');
		}
		exit(PurchaseSugHelper::listData($page, $rows, $sort, $order, $queryString));
	}
	/**
	 * get the prod_stock_id list choosen from the "采购单建议" page , and save it to the session ,so the redirected page ("新建采购单") can show the data from session.  
	 * @param $_POST['myids']------ prod_stock_id list from "wh_product_stock" table	 
	 * Returns "ok" for the frontend to check whether save or not
	 */	
	public function actionSavePurchasePreviewIds()
	{
		if (!isset($_POST['myids']) or count($_POST['myids'])==0) {
			echo json_encode(array("code"=>"fail","message"=>"提交失败。没有选中货品！"));
			return;
		}
		
		
		$prodStockIds=$_POST['myids'];		
		//先判断是否用户选择了2个或以上的仓库，是的话，就需要返回失败，并提示用户重新选择。
		/*
		 * $rows = Yii::app()->subdb->createCommand()
		->selectDistinct('warehouse_id')
		->from('wh_product_stock')
		->where(array('in', 'prod_stock_id',$prodStockIds))
		->queryAll();
		*/
		$warehouseId=-1;
		$skuList=array();
		$criteria = new CDbCriteria;
		$criteria->addInCondition('prod_stock_id', $prodStockIds);
		$criteria->select="sku,warehouse_id";
		$productStockCollection=ProductStock::model()->findAll($criteria);	
		foreach($productStockCollection as $productStock) {
			if ($warehouseId==-1) $warehouseId=$productStock->warehouse_id;
			else if ($warehouseId<>$productStock->warehouse_id) {
				//用户选择了2个或以上的仓库
				echo json_encode(array("code"=>"fail","message"=>"提交失败。选择了2个或以上的仓库，请重新选择！"));
				return;
			}
			$skuList[]=$productStock->sku;			
		}
		
		
		$skuListStr=json_encode($skuList);
		Yii::app()->session['skulist']=$skuListStr;
		Yii::app()->session['warehouseid']=$warehouseId;
		
		echo json_encode(array("code"=>"ok","message"=>""));		
	}
	
	
	public function actionModifyStrategiesView(){
		$this->renderPartial('modifyStrategies', array(), false, true);
	}
	
	
	public function actionSetStockStrategies(){
		if(!isset($_POST['stocking_strategy'])){
			exit(json_encode(array("success"=>false,"message"=>"请选择要保存的策略")));
		}
		if($_POST['stocking_strategy'] == 1){
			if(!isset($_POST['normal_stock']) || !isset($_POST['min_stock'])){
				exit(json_encode(array("success"=>false,"message"=>"请填写完整策略1信息")));
			}elseif ($_POST['normal_stock'] < $_POST['min_stock']){
				exit(json_encode(array("success"=>false,"message"=>"填写数据有误，采购上限不能小于触发采购下限")));
			}
		}
		if($_POST['stocking_strategy'] == 2){
			if(!isset($_POST['count_sales_period']) || !isset($_POST['min_total_sales_percentage']) || !isset($_POST['stock_total_sales_percentage'])){
				exit(json_encode(array("success"=>false,"message"=>"请填写完整策略2信息")));
			}elseif ($_POST['min_total_sales_percentage'] > $_POST['stock_total_sales_percentage']){
				exit(json_encode(array("success"=>false,"message"=>"填写数据有误，采购上限不能小于触发采购下限")));
			}
		}
		
		exit(json_encode(PurchaseSugHelper::setStockingStrategyInfo($_POST)));
	}
	
	public function actionPrintSug(){
		return $this->render('printSug');
	}
	
	/**
	 * Lists all Sug models.
	 */
	public function actionSugList()
	{
	
		//PurchaseSugHelper::getPurchaseSugList();
	
		//$dataProvider=new CActiveDataProvider('Purchase');
		return $this->renderPartial('_suglist',[
				'data'=>'',
					]
				);
	}
	
	/**
	 * Suggestion to PurchaseOrder Views.
	 */
	public function actionSuggestionToPurchaseOrder()
	{
		$suppliersDatas=SupplierHelper::ListSupplierData();
		foreach ($suppliersDatas as $supplier){
			$suppliers[] =  $supplier['name'] ;
		}
		
		if(isset($_REQUEST['data'])){
			$FormData = explode('-explode-', $_REQUEST['data']);
			foreach ($FormData as $index=>$str){
				$data[]=json_decode($str,true);
			}
		}
		else 
			$data = array();
		
		if(isset($_REQUEST['warehouseid']))
		    $warehouseid = $_REQUEST['warehouseid'];
		else 
		    $warehouseid = 0;
		
		return $this->renderAjax('_suggestion_to_purchase_order',[
				'data'=>$data,
				'suppliers'=>$suppliers,
				'warehouse'=>PurchaseHelper::warehouse(),
				'paymentStatus'=>PurchaseHelper::getPaymentStatus(),
				'purchaseStatus'=>PurchaseHelper::getPurchaseStatus(),
				'statusIdLabelMap' => PurchaseHelper::getStatusIdLabelMap(),
		        'warehouseid'=>$warehouseid,
				]
		);
	}
	
	/* 见单采购 列表
	 * 
	 */
	public function actionMeetOrder(){
		$groupBy = empty($_REQUEST['group_by'])?'sku':$_REQUEST['group_by'];
		$params=[];
		$params['order_source'] = empty($_REQUEST['order_source'])?'':$_REQUEST['order_source'];
		$params['start_date'] = empty($_REQUEST['start_date'])?'':strtotime($_REQUEST['start_date']);
		$params['end_date'] = empty($_REQUEST['end_date'])?'':strtotime($_REQUEST['end_date']);
		$params['skus'] = empty($_REQUEST['search_sku'])?'':$_REQUEST['search_sku'];
		$params['order_status'] = empty($_REQUEST['order_status'])?'':$_REQUEST['order_status'];
		
		if(!empty($params['skus'])){
			$params['skus']  =str_replace('；', ';', $params['skus']);//中文分号转换成英文
			$params['skus'] = explode(';', $params['skus']);
		}
		$params['page'] = empty($_REQUEST['page'])?1:$_REQUEST['page'];
		$params['per-page'] = empty($_REQUEST['per-page'])?50:$_REQUEST['per-page'];
		
		$data = PurchaseSugHelper::getMeetOrderItems($params,$groupBy);
		$platforms = OdOrder::$orderSource;
		
		$suppliers = SupplierHelper::getAllSuppliersIdName('map');
		return $this->render('meet_order',[
			'data'=>$data,
			'order_source'=>$platforms,
			'suppliers'=>$suppliers,
		]);
	}
	
	//更新缺货采购建议，20180319
	public function actionRefreshSuggestion(){
		InventoryHelper::UpdateUserOrdered(0);
		return json_encode(['success' => true]);
	}
	
	
	public function actionTest(){
		$data = PurchaseSugHelper::getMeetOrderItems();
		print_r($data);exit();
	}
}










