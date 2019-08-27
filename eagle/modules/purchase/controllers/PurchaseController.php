<?php

namespace eagle\modules\purchase\controllers;

use Yii;
use eagle\modules\purchase\models\Purchase;
use eagle\modules\purchase\helpers\PurchaseHelper;
use eagle\modules\purchase\helpers\SupplierHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\inventory\models\ProductStock;

use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\Sort;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\purchase\models\PurchaseItems;
use eagle\modules\util\helpers\OperationLogHelper;
use yii\base\Exception;
use eagle\modules\util\helpers\TimeUtil;
use eagle\widgets\ESort;
use eagle\modules\purchase\models\Supplier;
use eagle\modules\catalog\models\ProductSuppliers;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\catalog\apihelpers\ProductApiHelper;
use eagle\modules\permission\helpers\UserHelper;

/**
 * PurchaseController implements the CRUD actions for purchase model.
 */
class PurchaseController extends \eagle\components\Controller
{
 
	public $enableCsrfValidation = FALSE;
	
    /**
     * Lists all purchase models.
     * @return mixed
     */
    public function actionIndex()
    {
		//$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	//$page = isset($_GET['page'])?$_GET['page']:1;
    	
    	//新建默认仓库。//如果用户没进入仓库模块直接建采购单的话，由于没有默认仓会导致出错，因此打开采购页面的时候也建立默认仓
    	WarehouseHelper::createDefaultWarehouseIfNotExists();
    	$completesort='';
    	
        if(!empty($_GET['sort'])){
        	$sort = $completesort=$_GET['sort'];
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
        
        $sortConfig = new Sort(['attributes' => ['purchase_order_id','warehouse_id','supplier_id','amount','create_time','status','payment_status']]);
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

        $data = PurchaseHelper::getListDataByCondition($sort,$order,$params );

        //筛选内容
        $search_condition['type'] = 'search contidion';
        $search_condition['keyword'] = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $search_condition['sort'] = $sort;
        $search_condition['order'] = $order;
        $search_condition['params'] = $params;
        $search_condition['count'] = $data['pagination']->totalCount;
        $search_condition = json_encode($search_condition);
        $search_condition = base64_encode($search_condition);

        return $this->render('index', $data + [
        	'sort'=>$sortConfig,
        	'purchaseStatus'=>PurchaseHelper::$PURCHASE_STATUS,
        	'search_condition'=>$search_condition,
        ]);
    }
    
    public function actionIndexOld()
    {
    	//$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	//$page = isset($_GET['page'])?$_GET['page']:1;
    	 
    	//新建默认仓库。//如果用户没进入仓库模块直接建采购单的话，由于没有默认仓会导致出错，因此打开采购页面的时候也建立默认仓
    	WarehouseHelper::createDefaultWarehouseIfNotExists();
    	$completesort='';
    	 
    	if(!empty($_GET['sort'])){
    		$sort = $completesort=$_GET['sort'];
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
    
    	$sortConfig = new Sort(['attributes' => ['purchase_order_id','warehouse_id','supplier_id','amount','create_time','status','payment_status']]);
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
    
    	$data = PurchaseHelper::getListDataByCondition($sort,$order,$params );
    
    	//筛选内容
    	$search_condition['type'] = 'search contidion';
    	$search_condition['keyword'] = isset($_GET['keyword']) ? $_GET['keyword'] : '';
    	$search_condition['sort'] = $sort;
    	$search_condition['order'] = $order;
    	$search_condition['params'] = $params;
    	$search_condition['count'] = $data['pagination']->totalCount;
    	$search_condition = json_encode($search_condition);
    	$search_condition = base64_encode($search_condition);
    
    	return $this->render('index_old', $data + [
        	'sort'=>$sortConfig,
        	'purchaseStatus'=>PurchaseHelper::$PURCHASE_STATUS,
        	'search_condition'=>$search_condition,
        ]);
    }
  
    /**
     * Displays a single purchase model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {

    	if (($model = Purchase::findOne(['id'=>$id])) == null) {
    		throw new NotFoundHttpException("The requested purchase order id:$id does not exist.");
    	}
    	$detail = array();
    	if($model<>null){
    		$detail = PurchaseHelper::getPurchaseOrderDetail($model->id);
    	}
    	
    	return $this->renderAjax('view_order', [
    		'model' => $model,
    		'detail'=> $detail,
    		'mode' => 'view',
    		'warehouse'=>PurchaseHelper::warehouse(),
    		'paymentStatus'=>PurchaseHelper::getPaymentStatus(),
    		'inStockInfo' => InventoryHelper::GetPurchaseInStockInfo($model->id),
    		'purchaseStatus'=>PurchaseHelper::$PURCHASE_STATUS,
    	]);
    }
    
    /**
     * Displays a single purchase model.
     * @param integer $id
     * @return mixed
     */
    public function actionEdit($id)
    {
    	if (($model = purchase::findOne(['id'=>$id])) == null) {
    		throw new NotFoundHttpException("The requested purchase order id:$id does not exist.");
    	}
    	$detail = array();
    	if($model<>null){
    		$detail = PurchaseHelper::getPurchaseOrderDetail($model->id);
    	}
    	$suppliersDatas=SupplierHelper::ListSupplierData();
    	foreach ($suppliersDatas as $supplier){
    		$suppliers[] =  $supplier['name'] ;
    	}
    	return $this->renderAjax('viewOrUpdateOrder', [
    		'model' => $model,
    		'detail'=> $detail,
    		'mode' => 'edit',
    		'paymentStatus'=>PurchaseHelper::getPaymentStatus(),
    		'statusIdLabelMap' => PurchaseHelper::getStatusIdLabelMap(),
    		'suppliers'=>$suppliers,
    		'warehouse'=>PurchaseHelper::warehouse(),
    		//'paymentStatus'=>PurchaseHelper::getPaymentStatus(),
    		//'purchaseStatus'=>PurchaseHelper::getPurchaseStatus(),
    	]);
    }

    /**
     * Creates a new purchase model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
    	if(isset($_REQUEST['purchase_order_id']) && trim($_REQUEST['purchase_order_id'])!=='')
    		$model = purchase::findOne( $_REQUEST['purchase_order_id'] );
        else{
        	$model = new purchase();
        	$model->purchase_order_id = PurchaseHelper::formatSequenceId("PO");
        }
        /*
        AppTrackerApiHelper::log("purchase");
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }*/
        $suppliersDatas=SupplierHelper::ListSupplierData();
        foreach ($suppliersDatas as $supplier){
        	$suppliers[] =  $supplier['name'] ;
        }
    	return $this->renderAjax('create', [
    						'action'=>'create',
    						'model' => $model,
    						'suppliers'=>$suppliers,
    						'warehouse'=>PurchaseHelper::warehouse(),
    						'paymentStatus'=>PurchaseHelper::getPaymentStatus(),
    						'purchaseStatus'=>PurchaseHelper::getPurchaseStatus(),
    						'statusIdLabelMap' => PurchaseHelper::getStatusIdLabelMap(),
    						]);
    }
    
    /**
     * Creates a new purchase model.
     * 
     * @return array
     */
    public function actionSave()
    {
        $result=PurchaseHelper::generatePurchaseForm($_POST);
        //$result=PurchaseHelper::savePurchaseOrder($model, $_POST);
        exit( json_encode( $result ) );
    }    

    /**
     * Updates an existing purchase model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = purchase::findOne(['id'=>$id]);
		if($model==null)
			exit( json_encode( array('success'=>false,'message'=>'<b class="red-tips">'.TranslateHelper::t('采购单详情丢失，请重新打开页面后重试！').'</b>') ) );
        $result=PurchaseHelper::updatePurchaseOrder($model, $_POST);
        exit( json_encode( $result ) );
    }

    /**
     * Deletes a purchase model.
     * @param integer $purchase_order_id
     * @return array
     */
    /*
    public function actionDelete($purchase_order_id)
    {
    	$purchase_order_id = trim($purchase_order_id);
   		$model=purchase::findOne(['purchase_order_id'=>$purchase_order_id]);
   		if($model==null)
   			exit (json_encode(array('success'=>false,'message'=>TranslateHelper::t('采购单').$purchase_order_id.TranslateHelper::t('不存在。'))));
		else 
			$purchase_id = $model->id;
		if($model->status > 1){
			exit (json_encode(array('success'=>false,'message'=>TranslateHelper::t('采购单').$purchase_order_id.TranslateHelper::t('供应商已发货，不能直接删除。'))));
		}
        if($model->delete()){
        	PurchaseItems::deleteAll(['purchase_id'=>$purchase_id])->deleteAll();
        	exit (json_encode(array('success'=>true,'message'=>TranslateHelper::t('采购单删除成功'))));
        }
    }
    */
    /**
     * Cancels an existing purchase model.
     * @param integer $purchase_order_id
     * @return array
     */
    public function actionCancel($ids)
    {
    	$ids = trim($ids);
    	$purchase_ids_arr = explode(',', $ids);
    	$result=array('success'=>true,'message'=>'');
    	
    	$edit_log = '';
    	foreach ($purchase_ids_arr as $purchase_id){
    		if (trim($purchase_id)=='')
    			continue;
			
	        $model=purchase::findOne(['id'=>$purchase_id]);
	        if($model==null)
	        	$result['message'] .= '<br>id为：'.$purchase_id.':'.TranslateHelper::t('的采购单已经不存在');
	        $status = $model->status;
	        if($model->status <= PurchaseHelper::WAIT_FOR_ARRIVAL){
				$model->status = PurchaseHelper::CANCELED;
	        }
	        else{
	        	$model->status = PurchaseHelper::CANCELED_INSTOCK;
	        }
			try{
				OperationLogHelper::log("purchase", $model->purchase_order_id, "准备取消采购单","",\Yii::$app->user->identity->getFullName());
		        if($model->save()){
		        	$edit_log .= $model->purchase_order_id.", ";
		        	$status = $model->status;
		        	OperationLogHelper::log("purchase", $model->purchase_order_id, "取消采购单","",\Yii::$app->user->identity->getFullName());
		        	$purchaseId=$model->id;
		        	$warehouse_id=empty($model->warehouse_id)?0:$model->warehouse_id;
		        	$itemModels=PurchaseItems::findAll(['purchase_id'=>$purchaseId]);
		        	if($itemModels!==null){
						foreach ($itemModels as $item){
							$sku= $item->sku;
							$qty= ($item->qty - $item->in_stock_qty) > 0 ? ($item->qty - $item->in_stock_qty) : 0;
							InventoryHelper::modifyProductStockage($sku,$warehouse_id,-$qty,0,0,0,$item->price,0);
						}
		        	}
		        	OperationLogHelper::log("purchase", $model->purchase_order_id, "在途数量回归成功","",\Yii::$app->user->identity->getFullName());
		        }
		        else{
		        	\Yii::info("purchase: ".$model->purchase_order_id.' err:'.json_encode($model->errors), "file");
		        	
		        	OperationLogHelper::log("purchase", $model->purchase_order_id, "取消采购单失败","",\Yii::$app->user->identity->getFullName());
		        	//取消失败做处理
		        	$model->status = $status;
		        	$model->save();
		        }
    		}
    		catch(\Exception $ex){
    			\Yii::info("purchase: ".$model->purchase_order_id.' err:'.$ex->getMessage(), "file");
    			
    			OperationLogHelper::log("purchase", $model->purchase_order_id, "取消采购单失败",$ex->getMessage(),\Yii::$app->user->identity->getFullName());
    			//取消失败做处理
    			$model->status = $status;
    			$model->save();
    		}
        }
        if($result['message']=='')
        	$result['message'] = TranslateHelper::t('采购单取消成功');
        
        if(!empty($edit_log)){
        	//写入操作日志
        	UserHelper::insertUserOperationLog('purchase', "取消采购单, ".$edit_log);
        }
        
        exit (json_encode($result));
    }
    

    /**
     * 取消剩余到货
     * @param _GET["purchaseid"]
     * @return "ok" or "fail"
     */
    public function actionCancelLeftGoods()
    {
    	if (!isset($_GET["purchaseid"]))  {
    		echo json_encode(array("code"=>"fail","message"=>"操作失败！需要指定采购单号！"));
    		return;
    	}
    
    
    	$purchaseId=$_GET["purchaseid"];
    	$purchaseObject=Purchase::model()->findByPk($purchaseId);
    	$warehouseId=$purchaseObject->warehouse_id;
    
    	//validation check
    	//$operationStr=PurchaseHelper::getOperationForOnePurchase($purchaseObject->status, $purchaseObject->payment_status);
    	$operationStr=PurchaseHelper::getStatusOperation($purchaseObject->status);
    	$operationArr=explode(",", $operationStr);
    	if (!in_array("cancel_left",$operationArr))  {
    		echo json_encode(array("code"=>"fail","message"=>"操作失败！该采购单号目前的状态不能进行作废操作！"));
    		return;
    	}
    
    	$purchaseObject->status=PurchaseHelper::PARTIAL_ARRIVED_CANCEL_LEFT;
    	if ($purchaseObject->save(false)) {
    		//更新产品在仓库中的在途数量
    		$skuQtyMap=PurchaseArrivalHelper::getWaitingArrivalGoods($purchaseId,false,false);
    		foreach($skuQtyMap as $sku=>$qty){
    			if ($qty==0)  continue;
    				
    			InventoryHelper::modifyProductStockage($sku,$warehouseId,-$qty,0,0,0,0,0);
    		}
    
    		//记录"取消剩余到货"的操作
    		OperationLogHelper::log("purchase", $purchaseId, "取消剩余到货");
    			
    		echo json_encode(array("code"=>"ok","message"=>""));
    		return;
    	}
    	echo json_encode(array("code"=>"fail","message"=>"操作失败！Error:cancelleftpurchase001!"));
    }
    
    
    

    /**
     * Finds the purchase model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return purchase the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = purchase::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    public function actionAlertdemo()
    {
        if(!empty($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}
		
		$pageSize = empty($_GET['per-page']) ? 20 : $_GET['per-page'];
		if(!isset($sort))$sort = 'id';
		if(!isset($order))$order = 'asc';
		
        $data = PurchaseHelper::getListDataByCondition( $sort , $order , $pageSize);
        return $this->render('alertdemo', ['purchaseData' => $data]);
    }
    

    /**
     * excel 导入 采购  产品
     * @return		json array
     */
    public function actionImportPurchaseProdsByExcel(){
    	$result=array();
    	 
    	try {
    	    $suppliername = '';
    	    if( !empty($_POST['suppliername']))
    	        $suppliername = $_POST['suppliername'];
    		// excel 导入
    		if (! empty($_FILES['input_import_file'])){
    			$result = PurchaseHelper::importPurchaseProdsByExcel($_FILES['input_import_file'], $suppliername);
    		}else{
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('文件导入失败，请联系客服');
    		}
    
    	} catch (Exception $e) {
    		$result ['success'] = false;
    		$result ['message'] = TranslateHelper::t($e->getMessage());
    	}
    	
    	exit(json_encode($result,JSON_HEX_TAG));
    }
    
    /**
     * 复制粘贴 excel格式文本 导入 采购 产品
     * @return		json array
     */
    public function actionImportPurchaseProdsByExcelFormatText(){
    	$result=array();
    	 
    	if (! empty($_POST['prodsList'])){
    		if (empty($_POST['data_type'])){
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('导入出现错误, 请联系技术人员修正问题');
    			exit(json_encode($result));
    		}else{
    		    $suppliername = '';
    		    if( !empty($_POST['suppliername']))
    		    	$suppliername = $_POST['suppliername'];
    		    
    			if (strtolower($_POST['data_type']) == 'json' ){
    				$Datas = json_decode($_POST['prodsList'],true);
    				$prodDatas = [];
    				for($i=0;$i<count($Datas);$i++){
    					if(trim($Datas[$i][0])=='')
    						continue;
    					
    					$price = '0';
    					if(count($Datas[$i]) > 2 && !empty($Datas[$i][2]))
    					{
    					    $price = $Datas[$i][2];
    					}
    					else if($suppliername != '')
					    {
					    	//供应商
					    	$supplier = Supplier::findOne(['name'=>$suppliername]);
					    	if(!empty($supplier))
					    	{
					    		//获取报价信息
					    		$supplierModels = ProductSuppliers::findone(['sku'=>$Datas[$i][0], 'supplier_id'=>$supplier->supplier_id]);
					    		if(!empty($supplierModels))
					    		{
					    			$price = $supplierModels->purchase_price;
					    		}
					    	}
					    }
    					$prodDatas[]=array('sku'=>$Datas[$i][0],'qty'=>$Datas[$i][1],'price'=>$price,'remark'=>$Datas[$i][3]);
    				}
    				$result = PurchaseHelper::importPurchaseProdsByExcelFormatText($prodDatas);
    			}else{
    				$result ['success'] = false;
    				$result ['message'] = TranslateHelper::t('导入出现错误, 请联系技术人员修正问题');
    				exit(json_encode($result));
    			}
    		}
    	}
    
    	exit(json_encode($result,JSON_HEX_TAG));
    }
    
    /**
     * 由采购模块快速入库采购到货的产品
     * 不考虑质检、部分到货情况，只考虑一次全部到货
     * @param		$purchase_order_no
     * @return		json array
     */
    public function actionQuickPurchsaeStockIn($ids){
    
    	$ids=trim($ids);
    	$purchase_ids_arr = explode(',', $ids);
    	$result=array('success'=>true,'message'=>'');
    	$edit_log = '';
    	
    	foreach ($purchase_ids_arr as $purchase_id){
    		if (trim($purchase_id)=='')
    			continue;
    		
    		$data=[];
	    	$model = purchase::findOne(['id'=>$purchase_id]);
	    	if($model==null){
	    		$result['message'] .= '<br>id为:'.$purchase_id.':'.TranslateHelper::t('的采购单已经不存在');
	    		continue;
	    	}
	    	
	    	$items = PurchaseItems::findAll(['purchase_id'=>$model->id]);
	    	if($items==null or count($items)==0){
	    		$result['message'] .= '<br>'.$model->purchase_order_id.':'.TranslateHelper::t('采购单产品明细缺失');
	    		continue;
	    	}
	    	
	    	$data['purchase_id']=$model->id;
	    	$data['purchase_order_id']=$model->purchase_order_id;
	    	$data['purchase_arrival_id']=1;//purchase_arrival功能未完善，暂时hardcode为1
	    	$data['warehouse_id']=empty($model->warehouse_id)?0:$model->warehouse_id;
	    	$data['status']=$model->status;
	    	$data['create_time']=$model->create_time;
	    	$data['source_id']=$model->id;
	    	foreach ($items as $item){
	    		$data['sku'][]=$item->sku;
	    		$data['stock_in_qty'][]=($item->qty - $item->in_stock_qty) > 0 ? ($item->qty - $item->in_stock_qty) : 0;
	    		$query_location_grid=ProductStock::findOne(['sku'=>$item->sku , 'warehouse_id'=>$model->warehouse_id]);
	    		$location_grid='';
	    		if(!$query_location_grid==null)
	    			$location_grid=$query_location_grid->location_grid;
	    		if($location_grid==null or empty($location_grid))
	    			$location_grid =='';
	    		$data['location_grid'][]=$location_grid;
	    	}
	    	
	    	$rtn = InventoryHelper::insertPurchaseStockIn($data);
	    	if($rtn['success']){
	    		if(!empty($rtn['stock_change_id'])){
	    			$edit_log .= "采购单 ".$model->purchase_order_id.' 生成出入库记录 '.$rtn['stock_change_id'].'; ';
	    		}
	    		
	    		$model->status = 5;
	    		$model->is_arrive_goods ='Y';
	    		$model->save();
	    		
	    		//更新所有采购明细的已入库数量
	    		foreach ($items as $item){
	    			$item->in_stock_qty = $item->qty;
	    			$item->save(false);
	    		}
	    		
	    		OperationLogHelper::log("purchase", $model->purchase_order_id, "采购入库，更新状态","",\Yii::$app->user->identity->getFullName()."@".TimeUtil::getNow());
	    	}
    	}
    	
    	//写入操作日志
    	if(!empty($edit_log)){
	    	$edit_log = "采购入库，".$edit_log;
	    	UserHelper::insertUserOperationLog('purchase', $edit_log);
    	}
    	
    	exit (json_encode($result));
    }
    
    /**
     * ajax返回sku产品的供应商信息
     * @param		$sku
     * @return		json array
     */
    public function actionGetSkuSupplierInfo(){
    	$sku = trim($_GET['sku']);
    	$ret['status'] = 0;
    	$ret['purchase_link'] = '';
    	$info = [];
    	
    	if($sku!=='')
    	{
    	    //报价信息
    		$supplierModels=ProductSuppliers::findAll(['sku'=>$sku]);
    		if($supplierModels!==null){
	    		foreach ($supplierModels as $index=>$model){
	    			$info[$index]['sku']=$model->sku;
	    			$info[$index]['supplier_id']=$model->supplier_id;
	    			
	    			$supplierInfo = Supplier::findOne(['supplier_id'=>$model->supplier_id]);
	    			$supplier_name = $supplierInfo->name;
	    			$info[$index]['supplier_name']=$supplier_name;
	    			
	    			$info[$index]['priority']=$model->priority;
	    			$info[$index]['purchase_price']=$model->purchase_price;
	    			$info[$index]['purchase_link']=empty($model->purchase_link) ? '' : $model->purchase_link;
	    		}
    		}
    		
    		$ret['supplierInfo'] = $info;
    		
    		//采购地址
    		/*$sproduct = ProductApiHelper::getProductInfo($sku);
    		if(!empty($sproduct))
    		{
    			if(!empty($sproduct['purchase_link']))
    				$ret['purchase_link'] = $sproduct['purchase_link'];
    		}*/
    	}
    	
    	$ret['status'] = 1;
    	return json_encode($ret);
    }
    
    /*
     * 导出选中采购单
     */
    public function actionExportExcel(){
    	AppTrackerApiHelper::actionLog("purchase", "/purchase/purchase/export-execl");
    	$purchase_ids = empty($_REQUEST['purchase_ids'])?'':trim($_REQUEST['purchase_ids']);
    	if(empty($purchase_ids))
    		exit('采购单号为空，操作终止！');
    	$purchase_ids = explode(',', $purchase_ids);
    	$items_arr = ['purchase_order_id'=>'采购单号','sku'=>'sku','name'=>'商品名称','qty'=>'采购数量','price'=>'采购单价','amount_subtotal'=>'采购成本','status'=>'采购状态','create_time'=>'采购时间','supplier_name'=>'供应商','remark'=>'备注'];
    	$keys = array_keys($items_arr);
    	$purchaseDatas = PurchaseHelper::generateExcelData($purchase_ids);
    	$excel_data = [];
    	//print_r($purchaseDatas);exit();
    	foreach ($purchaseDatas as $index=>$row){
    		$tmp=[];
    		foreach ($keys as $key){
    			if(isset($row[$key])){
    				if(in_array($key,['purchase_order_id','sku','name']) && is_numeric($row[$key]))
    					$tmp[$key]=' '.$row[$key];
    				else
    					$tmp[$key]=(string)$row[$key];
    			}
    		}
    		$excel_data[$index] = $tmp;
    	}
    	ExcelHelper::exportToExcel($excel_data, $items_arr, 'purchase_'.date('Y-m-dHis',time()).".xls");
    }
    /**
     +---------------------------------------------------------------------------------------------
     *	 采购直接导出采购单
     +---------------------------------------------------------------------------------------------
     * @param      @type 导出类型
     * 			   @str  导出数据
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lgw		2017/1/4				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionStraightExportExcel(){
    	AppTrackerApiHelper::actionLog("catalog", "/purchase/purchase/straight-export-excel");
    	$purchase_ids = empty($_REQUEST['purchase_ids'])?'':trim($_REQUEST['purchase_ids']);
    	$type = empty($_REQUEST['type'])?0:trim($_REQUEST['type']);
    
    	if($type == 0){
    		$purchase_list = rtrim($purchase_ids, ',');
    	}
    	else{
    		$purchase_ids = $purchase_ids == base64_encode(base64_decode($purchase_ids)) ? base64_decode($purchase_ids) : $purchase_ids;
    		$purchase_list = json_decode($purchase_ids,true);
    	}

    	PurchaseHelper::ExportExcelAll($purchase_list,true);
    }
    /**
     +---------------------------------------------------------------------------------------------
     *	 采购导出插入导出队列
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
    	 
    	$purchase_ids = array();
    	if($type == 0){
    		$purchase_id_list = rtrim($str, ',');
    		$purchase_ids = json_encode($purchase_id_list);
    	}
    	else{
    		$purchase_ids = $str;
    	}
    
    	$className = addslashes('\eagle\modules\purchase\helpers\PurchaseHelper');
    	$functionName = 'ExportExcelAll';
    	 
    	$rtn = ExcelHelper::insertExportCrol($className, $functionName, $purchase_ids, 'export_purchase_list');
    
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
     +----------------------------------------------------------
     * 显示采购分批入库界面
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/09/20		初始化
     +----------------------------------------------------------
     **/
    public function actionStockInDialog()
    {
    	$id = empty($_REQUEST['id']) ? '0' : $_REQUEST['id'];
    	$model = purchase::findOne(['id'=>$id]);
    	if(empty($model)){
    		return '采购单不存在！';
    	}
    	
    	$detail = PurchaseHelper::getPurchaseOrderDetail($model->id);
    	$suppliersDatas=SupplierHelper::ListSupplierData();
    	foreach ($suppliersDatas as $supplier){
    		$suppliers[] =  $supplier['name'] ;
    	}
    	return $this->renderPartial('inStock', [
    			'model' => $model,
    			'detail'=> $detail,
    			'mode' => 'edit',
    			'paymentStatus'=>PurchaseHelper::getPaymentStatus(),
    			'purchaseStatus'=>PurchaseHelper::$PURCHASE_STATUS,
    			'suppliers'=>$suppliers,
    			'warehouse'=>PurchaseHelper::warehouse(),
    		]);
    }
    
    /**
     +----------------------------------------------------------
     * 采购分批入库保存
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/09/20		初始化
     +----------------------------------------------------------
     **/
    public function actionSaveInStock(){
    	$ret = PurchaseHelper::SaveInStock($_POST);
    	return json_encode($ret);
    }
    
    /**
     +----------------------------------------------------------
     * 打印采购单
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	    date					note
     * @author		lrq 		2017/09/27				初始化
     +----------------------------------------------------------
     **/
    public function actionPrint(){
    	if(isset($_GET['order_ids'])&&!empty($_GET['order_ids'])){
    		$orders	=	$_GET['order_ids'];
    	}else{
    		echo "错误：未传入订单号(send none order)";die;
    	}
    	$orders = rtrim($orders,',');
    	$orderlist = explode(',', $orders);
    	
    	$result = PurchaseHelper::printToPDF($orderlist);
    	if(isset($result['error'])){
    		$tmpResult = array();
    		$tmpResult['carrier_name'] = empty($_GET['ems']) ? '' : $_GET['ems'];
    		$tmpResult['result'] = array('error' => 1, 'data' => '', 'msg' => $result['msg']);
    			
    		return $this->render('doprint2',['data'=>$tmpResult]);
    	}
    }
    
    /**
     +----------------------------------------------------------
     * 获取采购明细商品信息
     +----------------------------------------------------------
     * log			name	    date			note
     * @author		lrq 		2018/04/12		初始化
     +----------------------------------------------------------
     **/
    public function actionGetPurchaseItems(){
    	if(empty($_POST['purchase_ids'])){
    		return json_encode(['success' => false, 'msg' => '参数丢失！']);
    	}
    	
    	$list = PurchaseHelper::GetPurchaseItems($_POST['purchase_ids']);
    	return json_encode(['success' => true, 'list' => $list]);
    }
    
    /**
     +----------------------------------------------------------
     * 显示创建1688订单界面
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/13		初始化
     +----------------------------------------------------------
     **/
    public function actionShow1688Dialog(){
    	if(empty($_GET['purchase_id'])){
    		return '参数缺失！';
    	}
    	
    	$list = PurchaseHelper::GetCreate1688Info($_GET['purchase_id']);
    	
    	return $this->renderPartial('create_1688', $list);
    }
    
    /**
     +----------------------------------------------------------
     * 根据url获取1688商品
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/16		初始化
     +----------------------------------------------------------
     **/
    public function actionGet1688Product(){
    	if(empty($_POST['pro_url'])){
    		return json_encode(['success' => false, 'msg' => '请填写1688商品链接！']);
    	}
    	if(empty($_POST['aliId'])){
    		return json_encode(['success' => false, 'msg' => '请选择1688账号！']);
    	}
    	$ret = PurchaseHelper::Get1688Product($_POST['aliId'], $_POST['pro_url']);
    	return json_encode($ret); 
    }
    
    /**
     +----------------------------------------------------------
     * 保存本地sku 与 1688商品配对
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/16		初始化
     +----------------------------------------------------------
     **/
    public function actionSave1688Matching(){
    	$ret = PurchaseHelper::Save1688Matching($_POST);
    	return json_encode($ret);
    }
    
    /**
     +----------------------------------------------------------
     * 创建1688订单
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/17		初始化
     +----------------------------------------------------------
     **/
    public function actionCreate1688Purchase(){
    	$ret = PurchaseHelper::Create1688Purchase($_POST);
    	return json_encode($ret);
    }
    
    /**
     +----------------------------------------------------------
     * 根据url获取1688店铺信息，并关联到供应商上
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/16		初始化
     +----------------------------------------------------------
     **/
    public function actionGet1688Supplier(){
    	if(empty($_POST['aliId'])){
    		return json_encode(['success' => false, 'msg' => '请选择1688账号！']);
    	}
    	if(empty($_POST['supplier_url'])){
    		return json_encode(['success' => false, 'msg' => '请填写1688店铺链接！']);
    	}
    	if(!isset($_POST['supplier_id'])){
    		return json_encode(['success' => false, 'msg' => '请选择供应商！']);
    	}
    	$ret = PurchaseHelper::Get1688Supplier($_POST['aliId'], $_POST['supplier_id'], $_POST['supplier_url']);
    	return json_encode($ret);
    }
    
    /**
     +----------------------------------------------------------
     * 更新1688订单信息
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/16		初始化
     +----------------------------------------------------------
     **/
    public function actionUpdate1688OrderInfo(){
    	PurchaseHelper::Update1688OrderInfo();
    }
    
    /**
     +----------------------------------------------------------
     * 获取1688账号历史收货地址
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/18		初始化
     +----------------------------------------------------------
     **/
    public function actionGet1688Add(){
    	if(empty($_POST['aliId'])){
    		return json_encode(['success' => false, 'msg' => '请选择1688账号！']);
    	}
    	
    	$ret = PurchaseHelper::Get1688Add($_POST['aliId']);
    	return json_encode($ret);
    }
    
    /**
     +----------------------------------------------------------
     * 获取1688账号收货地址
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/18		初始化
     +----------------------------------------------------------
     **/
    public function actionGetReceiveAdd(){
    	if(empty($_POST['aliId'])){
    		return json_encode(['success' => false, 'msg' => '请选择1688账号！']);
    	}
    	 
    	$ret = PurchaseHelper::GetReceiveAdd($_POST['aliId']);
    	return json_encode($ret);
    }
    
    /**
     +----------------------------------------------------------
     * 设置本地收货地址
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/19		初始化
     +----------------------------------------------------------
     **/
    public function actionSaveRecAdd(){
    	$ret = PurchaseHelper::SaveRecAdd($_POST);
    	return json_encode($ret);
    }
    
    /**
     +----------------------------------------------------------
     * 绑定1688订单到采购单上
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2018/04/18		初始化
     +----------------------------------------------------------
     **/
    public function actionBinding1688Order(){
    	if(empty($_POST['aliId'])){
    		return json_encode(['success' => false, 'msg' => '请选择1688账号！']);
    	}
    	if(empty($_POST['purchase_id'])){
    		return json_encode(['success' => false, 'msg' => '采购订单信息丢失！']);
    	}
    	if(empty($_POST['order_id'])){
    		return json_encode(['success' => false, 'msg' => '请填写1688订单号！']);
    	}
    
    	$ret = PurchaseHelper::Binding1688Order($_POST['aliId'], $_POST['purchase_id'], $_POST['order_id']);
    	return json_encode($ret);
    }
    
}
