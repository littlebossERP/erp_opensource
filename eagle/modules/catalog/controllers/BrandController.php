<?php

namespace eagle\modules\catalog\controllers;

use eagle\modules\catalog\models\Brand;
use eagle\modules\catalog\helpers\BrandHelper;
use eagle\modules\catalog\models\Product;
use yii\web\NotFoundHttpException;
use yii\data\Sort;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\catalog\helpers\ProductHelper;

class BrandController extends \eagle\components\Controller{

    public function actionIndex()
    {
    	$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    	$rows = !empty($_REQUEST['per-page']) ? $_REQUEST['per-page'] : 20;
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : 'brand_id';
    	
    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}

    	//queryString
    	$queryString = array();
    	if(!empty($_REQUEST['keyword']))
    	{
    		$queryString['keyword'] = $_REQUEST['keyword'];
    	}
    	
    	$sortConfig = new Sort(['attributes' => ['brand_id','name','capture_user_name','create_time','update_time']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	
    	$data = BrandHelper::listData($page, $rows, $sort, $order, $queryString);

    	return $this->render('index', [
    				'brandData'=>$data,
    				'sort'=>$sortConfig,
    			]);
    }
    
    /**
     * Displays a single Product model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
    	
    	return $this->renderPartial('view', [
    			'model' => $this->findModel($id),
    			]);
    }
    
    /**
     * Finds the Product model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Product the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
    	if (($model = Brand::findone($id)) !== null) {
    		return $model;
    	} else {
    		throw new NotFoundHttpException('The requested page does not exist.');
    	}
    }

    /**
     +----------------------------------------------------------
     * 修改品牌 view
     +----------------------------------------------------------
     * @access		public 
     * @params		id
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2015/06/05		初始化
     +----------------------------------------------------------
     **/
    public function actionEdit($id)
    {
    	$id=trim($id);
    	if($id=='' or !is_numeric($id)){
    		exit (TranslateHelper::t("不能找到指定品牌"));
    	}
    	
    	$model=Brand::findOne($id);

    	return $this->renderPartial('_create_or_update', [
    			'model' => $model,
    			'tt'=>'edit',
    			]);
    }
    
    /**
     +----------------------------------------------------------
     * 新建品牌view
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2015/06/05		初始化
     +----------------------------------------------------------
     **/
    public function actionCreate()
    {
    	$model=new Brand;
    
    	return $this->renderPartial('_create_or_update', [
    			'model' => $model,
    			]);
    }
    
    /**
     +----------------------------------------------------------
     * AJAX更新品牌模型
     +----------------------------------------------------------
     * @access public
     * @param integer $id the ID of the model to be updated
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/01/30				初始化
     +----------------------------------------------------------
     **/
    /*
    public function actionUpdate($id)
    {
    	$model=$this->loadModel($id);
    
    	// Uncomment the following line if AJAX validation is needed
    	// $this->performAjaxValidation($model);
    
    	if(isset($_POST['Brand']))
    	{
    		if ($model->name != $_POST['Brand']['name'] && BrandHelper::checkBrandExist('name', $_POST['Brand']['name'])){
    			exit(CJSON::encode(array('品牌名称' => array('该品牌名称已经存在！'))));
    		}
    		$model->attributes=$_POST['Brand'];
    		$model->update_time = date('Y-m-d H:i:s', time());
    		$model->capture_user_id = Yii::app()->muser->getId();
    		if( $model->save()){
    			exit(CJSON::encode(true));
    		}
    		else {
    			exit(CJSON::encode($model->getErrors()));
    		}
    	}
    
    	exit(CJSON::encode(array('错误' => array('提交信息不完整！'))));
    }
    */
    /**
     +----------------------------------------------------------
     * 保存品牌(create or update)
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2015/06/05		初始化
     +----------------------------------------------------------
     **/
    public function actionSave()
    {
    	if(!isset($_POST) or empty($_POST)){
    		exit( json_encode( array('success' =>false,'message'=>'提交信息不完整！') ) );
    	}
    	
    	$result=BrandHelper::saveBrandInfo($_POST);
    	exit( json_encode($result) );
    }
    
    /**
     +----------------------------------------------------------
     * 根据指定ID删除指定品牌
     +----------------------------------------------------------
     * @access public
     * @param integer $id the ID of the model to be deleted
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/01/30				初始化
     +----------------------------------------------------------
     **/
    public function actionDelete()
    {
    	$result['success']=true;
    	$result['message']='';
    	$id=$_POST['id'];
    	if(!is_numeric($id) or $id==''){
    		$result['success']=false;
    		$result['message']=TranslateHelper::t('无效的品牌id');
    		exit(json_encode($result));
    	}
    	$model = $this->findModel($id);
    	if(!$model->delete()){
    		$result['success']=false;
    		foreach ($model->errors as $k => $anError){
    			$result['message'] .= $anError[0];
    		}
    		exit(json_encode($result));
    	}
    	BrandHelper::productRemoveBrandAfterBrandDel($id);
    	exit(json_encode($result));
    }
    
    /**
     +----------------------------------------------------------
     * 根据指定ID批量删除指定品牌
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2015/06/05		初始化
     +----------------------------------------------------------
     **/
    public function actionBatchDeleteBrand()
    {
    	$result['success']=true;
    	$result['message']='';
    	
    	$ids=json_decode($_POST['ids'],true);

    	foreach ($ids as $id){
    		$id = trim($id);
    		if(!is_numeric($id) or $id==''){
    			$result['success']=false;
    			$result['message']=TranslateHelper::t('无效的品牌id');
    			exit(json_encode($result));
    		}
    		
    		$model = Brand::findOne($id);
    		if(!$model->delete()){
    			$result['success']=false;
    			foreach ($model->errors as $k => $anError){
    				$result['message'] .= $anError[0];
    			}
    		}else{
    			BrandHelper::productRemoveBrandAfterBrandDel($id);
    		}
    	}

    	exit(json_encode($result));
    }
    /**
     +----------------------------------------------------------
     * 批量删除指定ID列表的品牌
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/01/30				初始化
     +----------------------------------------------------------
     **/
    /*
    public function actionDelSelect()
    {
    	if (Yii::app()->request->isPostRequest) {
    		$criteria = new CDbCriteria;
    		$criteria->addInCondition('brand_id', $_POST['id']);
    		$model = new Brand();
    		$result = $model->deleteAll($criteria);
    
    		if (isset(Yii::app()->request->isAjaxRequest)) {
    			if ($result) {
    				exit(CJSON::encode(array('success' => true)));
    			} else {
    				exit(CJSON::encode($model->getErrors()));
    			}
    		} else
    			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    	}
    	else
    		throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }
    */
    /**
     +----------------------------------------------------------
     * 品牌管理ajax数据返回
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/01/30				初始化
     +----------------------------------------------------------
     **/
    public function actionData()
    {
    	$page = !empty($_POST['page']) ? $_POST['page'] : 1;
    	$rows = !empty($_POST['rows']) ? $_POST['rows'] : 20;
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'brand_id';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'asc';
    	//queryString
    	$queryString = array();
    	if(!empty($_POST['name']))
    	{
    		$queryString['name'] = $_POST['name'];
    	}
    
    	exit(BrandHelper::listData($page, $rows, $sort, $order, $queryString));
    }
    
    /**
     +----------------------------------------------------------
     * 品牌管理 根据brand_id返回产品数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		liang	2014/08/06				初始化
     +----------------------------------------------------------
     **/
    public function actionViewBrandProducts()
    {
    	$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    	$rows = !empty($_REQUEST['rows']) ? $_REQUEST['rows'] : 5;
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : 'sku';

    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}
    	
    	if(isset($_REQUEST['brand_id']) && is_numeric($_REQUEST['brand_id'])) $brand_id = $_REQUEST['brand_id'];
    	else $brind_id=0;

    	$keyword='';
    	if(isset($_REQUEST['keyword']) && $_REQUEST['keyword']!=='')
    	{
    		$keyword = $_REQUEST['keyword'];
    	}
    	$prods = BrandHelper::productListData($page, $rows, $sort, $order, $brand_id, $keyword);
    	
    	return $this->renderAjax('_brand_prod', [
    				'prods'=>$prods,
    				'brand_id'=>$brand_id,
    				'prodStatus'=>ProductHelper::getProductStatus(),
    				'prodType'=>ProductHelper::getProductType(),
    			]);
    }
    /**
     +----------------------------------------------------------
     * 品牌管理 移除产品当前品牌
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name		date				note
     * @author		liang		2015/06/08			初始化
     +----------------------------------------------------------
     **/
    public function actionProductRemoveBrand()
    {
    	$product_ids = !empty($_GET['product_ids']) ? $_GET['product_ids'] : "";
    	$product_id_arr=explode(",", $product_ids);
    	
    	$result['success']=true;
    	$result['message']='';
    	
    	foreach ($product_id_arr as $product_id){
    		$product_id = trim($product_id);
    		if( !empty($product_id) && is_numeric($product_id) ) {
    			$model=Product::findOne(['product_id'=>$product_id]);
    			if($model!==null){
    				$model->brand_id= 0;
    				$model->update_time = date('Y-m-d H:i:s', time());
    				if( !$model->save()){
    					$result['success']=false;
    					$result['message'].=$model->getErrors();
    				}
    			}
    			else {
    				$result['success']=false;
    				$result['message'].=TranslateHelper::t('找不到指定产品。');
    			}
    		}
    	}
    	exit(json_encode($result));
    }
    
    /**
     +----------------------------------------------------------
     * AJAX验证数据模型
     +----------------------------------------------------------
     * @access protected
     * @param Brand $model the model to be validated
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/01/30				初始化
     +----------------------------------------------------------
     **/
    /*
    protected function performAjaxValidation($model)
    {
    	if(isset($_POST['ajax']) && $_POST['ajax']==='brand-form')
    	{
    		echo CActiveForm::validate($model);
    		Yii::app()->end();
    	}
    }
    */
}
