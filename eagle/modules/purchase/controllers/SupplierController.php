<?php
namespace eagle\modules\purchase\controllers;
use Yii;
use eagle\modules\purchase\models\purchase;
use eagle\modules\purchase\helpers\PurchaseHelper;
use eagle\modules\purchase\helpers\SupplierHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\catalog\helpers\ProductSuppliersHelper;
use eagle\modules\purchase\models\PurchaseSuggestion;
use eagle\modules\purchase\helpers\PurchaseSugHelper;

use eagle\modules\catalog\models\Brand;

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
use eagle\modules\purchase\models\Supplier;
use eagle\models\SysCountry;
use yii\grid\DataColumn;
use eagle\modules\catalog\models\ProductSuppliers;

/**
 +------------------------------------------------------------------------------
 * 供应商控制类
 +------------------------------------------------------------------------------
 * @category	Purchase
 * @package		Controller/supplier
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class SupplierController extends \eagle\components\Controller{

	/** eagle 1.0
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	/*
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}
	*/

	/** eagle 1.0
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	/*
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Supplier']))
		{
			$model->attributes=$_POST['Supplier'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->supplier_id));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}
	*/

	/** eagle 2.0
	 * Deletes a particular model.
	 * @return boolean ,the deletion result  
	 */
	
	public $enableCsrfValidation = false;//false:允许上传文件
	
	public function actionDelete()
	{	
		if(!isset($_REQUEST['supplierIds']))
			exit(json_encode(array('success' => false , 'message'=>"请指定要删除的供应商")));
		
		$result = SupplierHelper::deleteSupplier($_REQUEST['supplierIds']);
		exit( json_encode($result));
		/* eagle 1.0 :
		if (isset(Yii::app()->request->isAjaxRequest))
        {
        	exit(CJSON::encode(array('success' => $result)));
        } else
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		*/
	}

	/** eagle 2.0
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$pageSize = !empty($_REQUEST['per-page']) ? $_REQUEST['per-page'] : 20;
		
		if(!empty($_REQUEST['sort'])){
			$sort = $_REQUEST['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}
		
		if(empty($sort))$sort = 'supplier_id';
		if(empty($order))$order = 'asc';
		
		$queryString=array();
		if(isset($_REQUEST['queryKey']) && isset($_REQUEST['queryValue'])){
			$queryKey = trim($_REQUEST['queryKey']);
			if($queryKey!=='' && trim($_REQUEST['queryValue'])!=='')
				$queryString[$queryKey] = trim($_REQUEST['queryValue']);
		}
		if(isset($_REQUEST['status']) && trim($_REQUEST['status']!=='')){
			$queryString['status']=trim($_REQUEST['status']);
		}
		if(isset($_REQUEST['account_settle_mode']) && trim($_REQUEST['account_settle_mode'])!==''){
			$queryString['account_settle_mode']=trim($_REQUEST['account_settle_mode']);
		}
		
		$sortConfig = new Sort(['attributes' => ['supplier_id','name','contact_name','phone_number','mobile_number','status']]);
		if(!in_array($sort, array_keys($sortConfig->attributes))){
			$sort = '';
			$order = '';
		}
		
		$suppliersListData=SupplierHelper::ListData($sort,$order,$page,$pageSize,$queryString);
		
		$supplierStatus = SupplierHelper::getSupplierStatus();
		$accountSettleMode = SupplierHelper::getAccountSettleMode();
		
		$countrysAll = SysCountry::find()->asArray()->all();
		$countrys = array();
		foreach ($countrysAll as $country){
			$countrys[$country['country_code']]=$country['country_zh'];
		}
		return $this->render('index',array(
			'suppliersListData'=>$suppliersListData,
			'sort'=>$sortConfig,
			'supplierStatus'=>$supplierStatus,
			'accountSettleMode'=>$accountSettleMode,
			'countrys'=>$countrys,
		));
	}

	/** eagle 1.0
	 * Manages all models.
	 */
	/*
	public function actionAdmin()
	{
		$model=new Supplier('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Supplier']))
			$model->attributes=$_GET['Supplier'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}
	*/

	/** eagle 1.0
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Supplier the loaded model
	 * @throws CHttpException
	 */
	/*
	public function loadModel($id)
	{
		$model=Supplier::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}
	*/
	
	/** eagle 1.0
	 * Performs the AJAX validation.
	 * @param Supplier $model the model to be validated
	 */
	/*
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='supplier-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
	*/
	
	/** eagle 1.0
	 * 
	 */
	public function actionAjaxSuppliersChoose()
	{
/*		$dataProvider=new CActiveDataProvider('Supplier');
	//	$this->render('supplierschoose',array(
		$this->renderPartial('supplierschoose',array(
				'dataProvider'=>$dataProvider,
		));*/
	//	$this->renderPartial('chooseall', array(), false, true);
		$supplierStatusMap=SupplierHelper::getSupplierStatus();
		$this->renderPartial('choose', array("supplierStatusMap"=>$supplierStatusMap), false, true);
		
		//echo "<div>actionAjaxSuppliersChoose</div>";
	}
	
	/** eagle 1.0
	 *
	 */
	public function actionChooseListData()
	{
		$result=array();
		/*		$dataProvider=new CActiveDataProvider('Supplier');
		 //	$this->render('supplierschoose',array(
		 		$this->renderPartial('supplierschoose',array(
		 				'dataProvider'=>$dataProvider,
		 		));*/
		$result['rows']=SupplierHelper::getAllSuppliersInfo();
		$result['total'] = count($result['rows']);
	
		//echo "<div>actionAjaxSuppliersChoose</div>";
		echo CJSON::encode($result);
	}
	
	/** eagle 1.0
	 * 
	 */
	//供应商列表页面
	public function actionList()
	{
		$supplierStatusMap=SupplierHelper::getSupplierStatus();
		$this->renderPartial('list', array("supplierStatusMap"=>$supplierStatusMap), false, true);
	}
	
	/** eagle 2.0 
	 * 供应商产品列表
	 * 
	 */
	public function actionListSupplierProducts()
	{
		$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$rows = !empty($_REQUEST['rows']) ? $_REQUEST['rows'] : 5;
		$sort = 'sku';
		$order = 'asc';
		 
		if(isset($_REQUEST['supplier_id']) && is_numeric($_REQUEST['supplier_id'])) $supplier_id = $_REQUEST['supplier_id'];
		else $supplier_id=0;
		
		$keyword='';
		if(isset($_REQUEST['keyword']) && $_REQUEST['keyword']!=='')
		{
			$keyword = $_REQUEST['keyword'];
		}
		$prods = SupplierHelper::supplierProductsListData($page, $rows, $sort, $order, $supplier_id, $keyword);

		return $this->renderAjax('_supplier_prod', [
				'prods'=>$prods,
				'supplier_id'=>$supplier_id,
				'prodStatus'=>ProductHelper::getProductStatus(),
				'prodType'=>ProductHelper::getProductType(),
			]);
	}	
	
	/** eagle 2.0*/
	//查看指定供应商的页面--dialog mode
	public function actionViewDetail()
	{
		if (!isset($_GET["supplier_id"])) {
			return TranslateHelper::t('没有指定供应商id');
		}
		$supplier_id = intval($_GET["supplier_id"]);
		$supplierDetail=Supplier::findOne(['supplier_id'=>$supplier_id]);
		
		if($supplierDetail==null){
			return TranslateHelper::t('供应商id不存在，或不是有效供应商id');
		}

		$countrysAll = SysCountry::find()->asArray()->all();
		$countrys = array();
		foreach ($countrysAll as $country){
			$countrys[$country['country_code']]=$country['country_zh'];
		}
		
		return $this->renderPartial('_detail', array(
				'model'=>$supplierDetail,
				'statusMap'=>SupplierHelper::getSupplierStatus(),
				'accountSettleMode'=>SupplierHelper::getAccountSettleMode(),
				'tt'=>'view',
				'countrys'=>$countrys,	
		));
	}	
	/** eagle 2.0*/
	//编辑指定供应商的页面--dialog mode
	public function actionUpdateView()
	{
		if (!isset($_GET["supplier_id"])) {
			return TranslateHelper::t('没有指定供应商id');
		}
		$supplier_id = intval($_GET["supplier_id"]);
		$supplierDetail=Supplier::findOne(['supplier_id'=>$supplier_id]);
		
		if($supplierDetail==null){
			return TranslateHelper::t('供应商id不存在，或不是有效供应商id');
		}
	
		$countrysAll = SysCountry::find()->asArray()->all();
		$countrys = array();
		foreach ($countrysAll as $country){
			$countrys[$country['country_code']]=$country['country_zh'];
		}
		
		return $this->renderPartial('_detail', array(
				'model'=>$supplierDetail,
				'statusMap'=>SupplierHelper::getSupplierStatus(),
				'accountSettleMode'=>SupplierHelper::getAccountSettleMode(),
				'mode'=>"edit",
				'countrys'=>$countrys,
		));
	}	
	/** eagle 2.0*/
	//修改指定供应商的信息
	public function actionUpdateSupplier()
	{
		if (!isset($_POST["supplier_id"])) {
			exit( json_encode( array('success'=>false,'message'=>TranslateHelper::t('没有指定供应商id')) ) );
		}
		
		$supplierId=$_POST["supplier_id"];
		$supplierName=$_POST["name"];
		$isExist = Supplier::find()->where(['name'=>$supplierName])->andWhere(['not',['supplier_id'=>$supplierId]])->all();
		if(!empty($isExist)){
			exit( json_encode( array('success'=>false,'message'=>TranslateHelper::t('供应商名称已存在，不能重复')) ) );
		}
		
		$supplierObject=Supplier::findOne(['supplier_id'=>$supplierId]);
		
		$supplierObject->attributes=$_POST;
		$supplierObject->update_time=TimeUtil::getNow();
		
		if($supplierObject->save(false)){
			//SysLogHelper::InvokeJrn_Create('Purchase',__CLASS__, __FUNCTION__, array($_POST));
			exit (json_encode(array('success'=>true, 'message'=>TranslateHelper::t('保存成功'))));
		}else{
			$rtn_message = '';
			foreach ($supplierObject->errors as $k => $anError){
				$rtn_message .= ($rtn_message==""?"":"<br>"). $k.":".$anError[0];
			}
			exit (json_encode(array('success'=>false,'message'=>$rtn_message )));
		}
	}	
	/** eagle 2.0*/
	//新建供应商的界面
	public function actionCreateView()
	{
		$countrysAll = SysCountry::find()->asArray()->all();
		$countrys = array();
		foreach ($countrysAll as $country){
			$countrys[$country['country_code']]=$country['country_zh'];
		}
	    $model=new Supplier();
		return $this->renderPartial('_detail', [
				'model'=>$model,
				'statusMap'=>SupplierHelper::getSupplierStatus(),
				'accountSettleMode'=>SupplierHelper::getAccountSettleMode(),
				'countrys'=>$countrys,
				'tt'=>"create",
				]);
	}	
	/** eagle 2.0*/
	//新建供应商=>save
	public function actionCreate()
	{
		$supplierObject=new Supplier();
	
		$supplierObject->attributes=$_POST;
		if(empty($supplierObject->create_time))
			$supplierObject->create_time=TimeUtil::getNow();
		$supplierObject->update_time=TimeUtil::getNow();
		$supplierName = $supplierObject['attributes']['name'];	// zhl 2014-9-19 bugfix start

		//$sql = "SELECT name FROM pd_supplier WHERE binary name = '".$supplierName."' and is_disable = '0' ";//区分大小写
		$query = Supplier::findOne(['name'=>$supplierName]);
		$isExict = ($query==null)?false:true;
		if ($isExict) {
			exit( json_encode(array('success'=>false,'message'=>TranslateHelper::t('供应商已存在，请修改供应商名称'))));
		}														// zhl 2014-9-19 bugfix end
		elseif ($supplierObject->save(false)) {
			//SysLogHelper::InvokeJrn_Create('Purchase',__CLASS__, __FUNCTION__, array($_POST));
			exit( json_encode(array('success'=>true,'message'=>TranslateHelper::t('保存成功'))));
		}else{
			$rtn_message = '';
			foreach ($supplierObject->errors as $k => $anError){
				$rtn_message .= ($rtn_message==""?"":"<br>"). $k.":".$anError[0];
			}
			exit (json_encode(array('success'=>false,'message'=>$rtn_message )));
		}	
	}
	
	/** eagle 1.0*/
	/**
	 * 改变产品对应的供应商.首先访问后台获取，该货品对应的供应商的采购价
	 * @param  supplier_id,sku
	 * @return json
	 */	
	public function actionGetPurchasePriceForsku()
	{
		if (!isset($_POST["sku"]) or !isset($_POST["supplier_id"])) {
			echo  "0";
			return;
		}
		$productSupplier=ProductSuppliers::model()->find("sku=:sku and supplier_id=:supplier_id",array(":sku"=>$_POST["sku"],":supplier_id"=>$_POST["supplier_id"]));
		if ($productSupplier==null)  {
			echo  "0";
			return;
		}
		echo $productSupplier->purchase_price;
	}

	/** eagle 2.0*/
	// 停用供应商
	public function actionInactivateSupplier(){
		if(!isset($_REQUEST['supplierIds']))
			exit(json_encode(array('success' => false , 'message'=>TranslateHelper::t("请指定停用的供应商"))));

		$result = SupplierHelper::inactivateSupplier($_REQUEST['supplierIds']);
		
		exit( json_encode($result));
		/* eagle 1.0 :
		if (isset(Yii::app()->request->isAjaxRequest))
        {
        	exit(CJSON::encode(array('success' => $result)));
        } else
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		*/
	}
	/** eagle 2.0*/
	// 启用供应商
	public function actionActivateSupplier(){
		if(!isset($_REQUEST['supplierIds']))
			exit(json_encode(array('success' => false , 'message'=>TranslateHelper::t("请指定启用的供应商"))));
			
		$result = SupplierHelper::activateSupplier($_REQUEST['supplierIds']);
		
		exit( json_encode($result));
		/* eagle 1.0 :
		if (isset(Yii::app()->request->isAjaxRequest))
        {
        	exit(CJSON::encode(array('success' => $result)));
        } else
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		*/
	}

	/**
	 +----------------------------------------------------------
	 *移除供应商某产品的供应
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		liang		2015/06/12			初始化
	 +----------------------------------------------------------
	 **/
	public function actionSupplierRemoveProduct()
	{
		if(!isset($_GET['skus_encode']))
			exit(json_encode(array('success' => false , 'message'=>TranslateHelper::t("请指要停止供应的产品"))));
		if(!isset($_GET['supplier_id']))
			exit(json_encode(array('success' => false , 'message'=>TranslateHelper::t("供应商id信息丢失，请重新指定"))));
		
		$result = SupplierHelper::supplierRemoveProduct($_GET['supplier_id'],$_GET['skus_encode']);
		
		exit( json_encode($result));
	}
	
	/** eagle 2.0*/
	// 查看供应商报价
	public function actionPdSuppliers()
	{
		$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
		$page = isset($_GET['page'])?$_GET['page']:1;
		$hasPriceOnly=false;
		if(isset($_GET['hasPriceOnly']) && $_GET['hasPriceOnly']==1) $hasPriceOnly=true;
		$brand_id = isset($_GET['brand_id'])?$_GET['brand_id']:'';
		$supplier_id = isset($_GET['supplier_id'])?$_GET['supplier_id']:'';
		$keyword = isset($_GET['keyword'])?$_GET['keyword']:'';
		
		if(!empty($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else $order = 'asc';
		}else{
			$sort = 'sku';
			$order = 'asc';
		}
		
		
		$sortConfig = new Sort(['attributes' => ['sku','name','brand_id']]);
		if(!in_array($sort, array_keys($sortConfig->attributes))){
			$sort = '';
			$order = '';
		}
		$pdSuppliersListData=SupplierHelper::listProductSupplierDatas($page,$pageSize,$sort,$order,$hasPriceOnly,$brand_id,$supplier_id,$keyword);
		
		$prodBrands=array();
		$brandlist = Brand::find()->where('brand_id<>0')->asArray()->all();
		foreach($brandlist as $brand){
			$prodBrands[$brand['brand_id']] = $brand['name'];
		}
		$pdSuppliers=array();
		$SupplierList = Supplier::find()->where(['is_disable'=>0,'status'=>1])->asArray()->all();
		foreach($SupplierList as $supplier){
			$pdSuppliers[$supplier['supplier_id']] = $supplier['name'];
		}
		$typeMapping = ProductHelper::getProductType();
		
		return $this->render('pd-supplier',[
					'pdSuppliersListData' => $pdSuppliersListData,
					'sort' => $sortConfig,
					'prodBrands'=>$prodBrands,
					'pdSuppliers'=>$pdSuppliers,
					'prodType'=>$typeMapping,
				]);
	}
	/** eagle 2.0*/
	//新建供应商报价view
	public function actionCreatePdSuppliers()
	{
		$supplierData = SupplierHelper::getAllSuppliersIdName();
		
		return $this->renderAjax('_createOrEdit_pdSuppliers',[
					'supplierData'=>$supplierData,
			]);
	}
	
	/** eagle 2.0*/
	//新建供应商报价view
	public function actionSavePdSuppliers()
	{
		$result['success']=true;
		$result['message']='';
		if(!isset($_POST['supplier_id']) or !is_numeric($_POST['supplier_id']) or empty($_POST['prod'])){
			$result['success']=false;
			$result['message']=TranslateHelper::t("报价信息不全，保存终止");
		}else{
			$result = SupplierHelper::savePdSuppliers($_POST);
		}
		exit(json_encode($result));
	}
	
	/** eagle 2.0*/
	// 删除供应商报价
	public function actionRemoveProductSupplier()
	{
		if(!isset($_GET['skus_encode']))
			exit(json_encode(array('success' => false , 'message'=>TranslateHelper::t("请选择要删除的报价"))));
		if(!isset($_GET['product_supplier_ids']))
			exit(json_encode(array('success' => false , 'message'=>TranslateHelper::t("报价id信息丢失，请重新指定"))));
		
		$result = SupplierHelper::removeProductSupplier($_GET['skus_encode'],$_GET['product_supplier_ids']);
		
		exit( json_encode($result));
	}
	
	/** eagle 2.0
	 * excel批量导入报价
	 * @return		json array
	 */
	public function actionImportPdSuppliersByExcel()
	{
    	$result=array();
    	try {
    		// excel 导入
    		if (! empty($_FILES['input_import_file'])){
    			$result = ProductSuppliersHelper::importPdSuppliersByExcel($_FILES['input_import_file'],'file');
    		}else{
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('文件导入失败，请联系客服');
    		}
    
    	} catch (Exception $e) {
    		$result ['success'] = false;
    		$result ['message'] = TranslateHelper::t($e->getMessage());
    	}
		
    	if($result['success']){// mark log
	    	$uid=\Yii::$app->user->id;
	    	$message = "UATL:用户 $uid Import PdSuppliers By Excel :".$_FILES['input_import_file']['name'];
	    	SysLogHelper::SysLog_Create("Purchase",__CLASS__, __FUNCTION__,"trace",$message);
    	}
    	
    	exit(json_encode($result));
	}
	
	/** eagle 2.0
	 * excel格式复制黏贴 批量导入报价
	 * @return		json array
	 */
	public function actionImportPdSuppliersByExcelFormatText()
	{
		$result=array();

		if (! empty($_POST['psSupplierList'])){
			 
			if (empty($_POST['data_type'])){
				$result ['success'] = false;
				$result ['message'] = TranslateHelper::t('导入出现错误, 请联系技术人员修正问题');
				exit(json_encode($result));
			}else{
				if (strtolower($_POST['data_type']) == 'json' ){
					$Datas = json_decode($_POST['psSupplierList'],true);
					$psSupplierDatas = [];
					for($i=0;$i<count($Datas);$i++){
						if(trim($Datas[$i][0])=='')
							continue;
						$psSupplierDatas[]=array('sku'=>$Datas[$i][0],'supplier_name'=>$Datas[$i][1],'priority'=>$Datas[$i][2],'purchase_price'=>$Datas[$i][3]);
					}
					$result = ProductSuppliersHelper::importPdSuppliersByExcel($psSupplierDatas,'text');
				}else{
					$result ['success'] = false;
					$result ['message'] = TranslateHelper::t('导入出现错误, 请联系技术人员修正问题');
					exit(json_encode($result));
				}
			}
		}

		if($result['success']){// mark log
			$uid=\Yii::$app->user->id;
			$message = "UATL:用户 $uid Import PdSuppliers By copy excel format text";
			SysLogHelper::SysLog_Create("Purchase",__CLASS__, __FUNCTION__,"trace",$message);
		}
		 
		exit(json_encode($result));
	}
	
}




















