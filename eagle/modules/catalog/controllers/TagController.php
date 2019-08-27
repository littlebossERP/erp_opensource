<?php

namespace eagle\modules\catalog\controllers;

use eagle\modules\catalog\models\Tag;
use eagle\modules\catalog\helpers\TagHelper;
use eagle\modules\catalog\models\Product;
use yii\web\NotFoundHttpException;
use yii\data\Sort;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\util\helpers\SwiftFormat;
use eagle\modules\catalog\models\ProductTags;
use yii\base\Exception;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;

class TagController extends \eagle\components\Controller{

    public function actionIndex()
    {
    	$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    	$rows = !empty($_REQUEST['per-page']) ? $_REQUEST['per-page'] : 20;
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : 'tag_id';
    	
    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}
    	$queryString = array();
    	if(!empty($_REQUEST['keyword']))
    	{
    		$queryString['keyword'] = $_REQUEST['keyword'];
    	}
    	
    	$sortConfig = new Sort(['attributes' => ['tag_id','tag_name']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	
    	$data = TagHelper::listData($page, $rows, $sort, $order, $queryString);

    	return $this->render('index', [
    				'tagData'=>$data,
    				'sort'=>$sortConfig,
    			
    			]);
    }
    
    /**
     * Displays a single tag model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($tag_id)
    {
    	
    	return $this->renderPartial('view', [
    			'model' => $this->findModel($tag_id),
    			]);
    }
    
    protected function findModel($id)
    {
    	if (($model = tag::findone($id)) !== null) {
    		return $model;
    	} else {
    		throw new NotFoundHttpException('The requested page does not exist.');
    	}
    }

    /**
     +----------------------------------------------------------
     * 修改标签 view
     +----------------------------------------------------------
     * @access		public 
     * @params		id
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2015/06/12		初始化
     +----------------------------------------------------------
     **/
    public function actionEdit($id)
    {
    	$id=trim($id);
    	if($id=='' or !is_numeric($id)){
    		exit (TranslateHelper::t("不能找到指定标签"));
    	}
    	
    	$model=Tag::findOne($id);

    	return $this->renderPartial('_create_or_update', [
    			'model' => $model,
    			'tt'=>'edit',
    			]);
    }
    
    /**
     +----------------------------------------------------------
     * 新建标签view
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2015/06/12		初始化
     +----------------------------------------------------------
     **/
    public function actionCreate()
    {
    	$model=new Tag();
    
    	return $this->renderPartial('_create_or_update', [
    			'model' => $model,
    			]);
    }
   
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
    	
    	$result=TagHelper::saveTagInfo($_POST);
    	exit( json_encode($result) );
    }
    
    /**
     +----------------------------------------------------------
     * 根据指定ID删除指定标签
     +----------------------------------------------------------
     * @access 		public
     * @param		$id 	the ID of the model to be deleted
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/01/30				初始化
     +----------------------------------------------------------
     **/
    public function actionDelete()
    {
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/catalog/tag/product-remove-tag");
    	$result['success']=true;
    	$result['message']='';
    	$id=$_POST['id'];
    	if(!is_numeric($id) or $id==''){
    		$result['success']=false;
    		$result['message']=TranslateHelper::t('无效的标签id');
    		exit(json_encode($result));
    	}
    	
    	$model=Tag::findOne($id);
    	if($model!==null){
    		if(!$model->delete()){
    			$result['success']=false;
    			foreach ($model->errors as $k => $anError){
    				$result['message'] .= $anError[0];
    			}
    			exit(json_encode($result));
    		}
    	}

    	TagHelper::productRemoveTagAfterTagDel($id);
    	exit(json_encode($result));
    }
    
    /**
     +----------------------------------------------------------
     * 根据指定ID批量删除指定标签
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2015/06/12		初始化
     +----------------------------------------------------------
     **/
    public function actionBatchDeleteTag()
    {
    	$result['success']=true;
    	$result['message']='';
    	
    	$ids=json_decode($_POST['ids'],true);

    	foreach ($ids as $id){
    		$id = trim($id);
    		if(!is_numeric($id) or $id==''){
    			$result['success']=false;
    			$result['message']=TranslateHelper::t('无效的品牌id');
    		}
    		
    		$model=Tag::findOne($id);
	    	if($model!==null){
	    		if(!$model->delete()){
	    			$result['success']=false;
	    			foreach ($model->errors as $k => $anError){
	    				$result['message'] .= $anError[0];
	    			}
	    		}
	    	}
	    	
	    	TagHelper::productRemoveTagAfterTagDel($id);
    	}

    	exit(json_encode($result));
    }
    
    /**
     +----------------------------------------------------------
     * 标签管理 根据tag_id返回产品数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name		date				note
     * @author		lzhl		2015/06/12			初始化
     +----------------------------------------------------------
     **/
    public function actionViewTagProducts()
    {
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/catalog/tag/view-tag-products");
    	$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    	$rows = !empty($_REQUEST['rows']) ? $_REQUEST['rows'] : 5;
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : 'sku';

    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}
    	$tag_name='';
    	if(isset($_REQUEST['tag_id']) && is_numeric($_REQUEST['tag_id'])){
    		$tag_id = $_REQUEST['tag_id'];
    		$tagModel=Tag::findOne($tag_id);
    		if($tagModel!==null){
    			$tag_name=$tagModel->tag_name;
    		}
    	}else{
    		$tag_id=0;
    	}
    	
    	$tagModel=Tag::findOne($tag_id);

    	$queryStr=array();
    	if(isset($_REQUEST['keyword']) && $_REQUEST['keyword']!=='')
    	{
    		$queryStr['keyword'] = $_REQUEST['keyword'];
    	}
    	$prods = TagHelper::productListData($page, $rows, $sort, $order, $tag_id, $queryStr);
    	
    	return $this->renderAjax('_tag_prod', [
    				'prods'=>$prods,
    				'tag_id'=>$tag_id,
    				'tag_name'=>$tag_name,
    				'prodStatus'=>ProductHelper::getProductStatus(),
    				'prodType'=>ProductHelper::getProductType(),
    			]);
    }
    /**
     +----------------------------------------------------------
     * 标签管理 移除产品指定标签
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name		date				note
     * @author		liang		2015/06/12			初始化
     +----------------------------------------------------------
     **/
    public function actionProductRemoveTag()
    {
    	$product_ids = !empty($_GET['product_ids']) ? $_GET['product_ids'] : "";
    	$product_id_arr=explode(",", $product_ids);

    	$skuList=array();
    	$prods = Product::find()->select('sku')->where(['in','product_id',$product_id_arr])->asArray()->all();
	    foreach ($prods as $index=>$row){
	    	$skuList[]=$row['sku'];
	    }

    	$tag_id = !empty($_GET['tag_id']) ? $_GET['tag_id'] : 0;
    	
    	$result=TagHelper::productRemoveTag($skuList, $tag_id);
    	
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
