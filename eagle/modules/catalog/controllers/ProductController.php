<?php

namespace eagle\modules\catalog\controllers;

use Yii;
use eagle\modules\catalog\models\Product;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\catalog\helpers\BrandHelper;
use eagle\modules\purchase\helpers\SupplierHelper;
use eagle\modules\util\helpers\TranslateHelper;
use yii\web\HttpException;
use yii\base\Exception;
use eagle\modules\catalog\helpers\TagHelper;
use eagle\modules\catalog\models\Tag;
use yii\db\Query;
use eagle\modules\catalog\helpers\PhotoHelper;
use eagle\modules\catalog\models\Brand;
use eagle\modules\catalog\models\ProductSuppliers;
use eagle\modules\catalog\models\ProductAliases;

use eagle\modules\catalog\models\ProductConfigRelationship;
use eagle\modules\catalog\models\ProductField;
use eagle\modules\catalog\models\ProductFieldValue;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\catalog\helpers\ProductFieldHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\catalog\models\ProductFanben;
use eagle\modules\catalog\models\ProductFanbenVariance;
use eagle\modules\listing\helpers\WishHelper;
use eagle\modules\util\helpers\ConfigHelper;
use Qiniu\json_decode;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\catalog\models\ProductClassification;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\permission\helpers\UserHelper;

/**
 * ProductController implements the CRUD actions for Product model.
 */
class ProductController extends \eagle\components\Controller{

    public $enableCsrfValidation = false;
    /**
     +----------------------------------------------------------
     * 产品导入
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		dzt		2014/07/18				初始化
     +----------------------------------------------------------
     **/
    public function actionExcel2Product(){
    	try {
    		
    		AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/excel2-product");
    		if (!empty ($_FILES["input_import_file"]))
    			$files = $_FILES["input_import_file"];
    		
    		$itype = empty($_POST['itype']) ? 'S' : $_POST['itype'];
    		 
    		$EXCEL_PRODUCT_ENNAME_CNNAME_MAPPING = ProductHelper::get_EXCEL_PRODUCT_ENNAME_CNNAME_MAPPING();
    		
    		//添加平台信息
    		$ret_plat = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
    		if(!empty($ret_plat)){
    			foreach ($ret_plat as $plat => $shop){
    				$EXCEL_PRODUCT_ENNAME_CNNAME_MAPPING['commission_per_'.$plat] = [
	                	'佣金比例'.$plat => "like", 
	                ];
    			}
    		}
    		
    		// excel 数据 转为 array
    		//$current_time=explode(" ",microtime());//test liang
    		//$step1_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
    		//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"action start at:".date('Y-m-d H:i:s', time())],"edb\global");//test liang

    		$productsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files, $EXCEL_PRODUCT_ENNAME_CNNAME_MAPPING, false, true );
    		//$current_time=explode(" ",microtime());//test liang
    		//$step2_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
    		//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"excelToArray used time:".($step2_time-$step1_time)],"edb\global");//test liang

    		// 导入 商品数据
    		$result = ProductHelper::importProductData($productsData, $itype);
    		
    		//$current_time=explode(" ",microtime());//test liang
    		//$step3_time=round($current_time[0]*1000+$current_time[1]*1000);//test liang
    		//\Yii::info(['catalog', __CLASS__, __FUNCTION__,'Background',"ProductHelper importProductData used time:".($step3_time-$step2_time)],"edb\global");//test liang

    	} catch (Exception $e) {
    		$result = $e->getMessage();
    		
    	}
    	
    	$rtnstr = json_encode($result);
    	exit($rtnstr);
    }
    
    /**
     +----------------------------------------------------------
     * seller tools 产品导入
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/03/16				初始化
     +----------------------------------------------------------
     **/
    public function actionSellertoolExcel2Product(){
    	try {
    	
    		AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/excel2-product");
    		if (!empty ($_FILES["input_import_file"]))
    			$files = $_FILES["input_import_file"];
    		 
    		$EXCEL_PRODUCT_COLUMN_MAPPING = ProductHelper::get_SELLERTOOL_EXCEL_PRODUCT_COLUMN_MAPPING();
    		// excel 数据 转为 array
    	
    		$productsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files, $EXCEL_PRODUCT_COLUMN_MAPPING );
    		//自动 补全需要的信息
    		foreach($productsData as &$row){
    		
    			$row["prod_name_ch"] = $row['declaration_ch'];// 中文配货名称
    			$row["prod_name_en"] = $row['declaration_en'];// 英文配货名称
    		}
    		
    		
    		// 导入 商品数据
    		$result = ProductHelper::importProductData($productsData);
    	
    	
    	} catch (Exception $e) {
    		$result = $e->getMessage();
    	
    	}
    	 
    	$rtnstr = json_encode($result);
    	exit($rtnstr);
    }//end of actionSellerToolExcel2Product
    
    /**
     +----------------------------------------------------------
     * seller tools 产品导入
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/03/16				初始化
     +----------------------------------------------------------
     **/
    public function actionSellertoolExcel2BundleProduct(){
    	try {
    		$result =['error'=>''];
    		AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/excel2-product");
    		if (!empty ($_FILES["input_import_file"]))
    			$files = $_FILES["input_import_file"];
    		$uid = \Yii::$app->subdb->getCurrentPuid();
    		$EXCEL_PRODUCT_COLUMN_MAPPING = ProductHelper::get_SELLERTOOL_EXCEL_BUNDLE_PRODUCT_COLUMN_MAPPING();
    		// excel 数据 转为 array
    		 
    		$productsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files, $EXCEL_PRODUCT_COLUMN_MAPPING );
    		
    		//读取全局报关信息
    		$tmpCommonDeclaredInfo = \eagle\modules\carrier\helpers\CarrierOpenHelper::getCommonDeclaredInfoByDefault();
    		
    		//自动 补全需要的信息
    		foreach($productsData as &$row){
    	
    			$row['children'] = ProductHelper::explodeSELLTOOLBundleProduct($row['bundlesku']);
    			$platform = 'excel';
    			$photo_primary='';
    			$declaration_ch=$tmpCommonDeclaredInfo['ch_name'];
    			$declaration_en=$tmpCommonDeclaredInfo['en_name'];
    			// 导入 商品数据
    			$rt = ProductApiHelper::creatBundleProductFromOMS($row['sku'], $uid, $row['sku'], $row['sku'], $row['sku'],$photo_primary,$declaration_ch,$declaration_en, 'USD', $tmpCommonDeclaredInfo['declared_value'],$tmpCommonDeclaredInfo['declared_weight'] , 'N', $platform , '','B',[],$row['children'],$tmpCommonDeclaredInfo['detail_hs_code']);
    			if ($rt['success'] == false){
    				$result ['error'] .=  $row['sku'].'导入失败：'.$rt['message'];
    			}
    		}
    	
    	
    		
    		
    		 
    		 
    	} catch (Exception $e) {
    		$result = $e->getMessage();
    		 
    	}
    	
    	$rtnstr = json_encode($result);
    	
    	exit($rtnstr);
    }//end of actionSellertoolExcel2BundleProduct
    
    /*****************************************************************************************************************************/
    


    /**
     * Finds the Product model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Product the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Product::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    /************************ product action start ***************************/
    /**
     * product index 页面数据获取
     */
    public function actionIndex(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/index");
    	\Yii::warning("actionIndex params:".print_r(\Yii::$app->params,true));
    
    	if(!empty($_GET['sort'])){
    		$sort = $_GET['sort'];
    		if( '-' == substr($sort,0,1) ){
    			$sort = substr($sort,1);
    			$order = 'desc';
    		} else {
    			$order = 'asc';
    		}
    	}
    	 
    	if(empty($sort))$sort = 'sku';
    	if(empty($order))$order = 'asc';
    
    	if (isset($_GET['txt_search'])){
    		$txt_search = trim(str_ireplace(array("\r\n", "\r", "\n", "chr(10)", "chr(13)", "CHR(10)", "CHR(13)"), '', $_GET['txt_search']));
    		if ($txt_search!="" ){
    			if (!empty($_GET['search_type'])){
    				if($_GET['search_type'] == 'sku'){
    					$txt_search2 = str_replace("'", "", $txt_search);
    					$condition [] = ['or'=>['like','sku', $txt_search2]];
    					$condition [] = ['or'=>'sku in (select `sku` from `pd_product_aliases` where `alias_sku`=\''.$txt_search2.'\')'];
    					
    					//支持批量搜索
    					$search_skus = explode(';', $txt_search);
    					foreach ($search_skus as &$one){
    						$one = trim($one);
    					}
    					$condition [] = ['or' => ['in', 'sku', $search_skus]];
    				}
    				else{
    					$condition [] = ['or'=>['like',$_GET['search_type'], $txt_search]];
    				}
    			}
    			else{
	    			$condition [] = ['or'=>['like','sku', $txt_search]];
	    			$condition [] = ['or'=>['like','name', $txt_search]];
	    			$condition [] = ['or'=>['like','prod_name_ch', $txt_search]];
	    			$condition [] = ['or'=>['like','prod_name_en', $txt_search]];
	    			$condition [] = ['or'=>['like','declaration_ch', $txt_search]];
	    			$condition [] = ['or'=>['like','declaration_en', $txt_search]];
	    			
	    			$txt_search2 = str_replace("'", "", $txt_search);
	    			$condition [] = ['or'=>'sku in (select `sku` from `pd_product_aliases` where `alias_sku`=\''.$txt_search2.'\')'];
	    			//$condition [] = ['or'=>'sku=(select `cfsku` from `pd_product_config_relationship` where `assku`=\''.$txt_search.'\')'];
    			}
    		}
    
    	}
    	 
    	if (isset($_GET['txt_tag'])){
    		if (trim($_GET['txt_tag'])!="" && $_GET['txt_tag'] != "all"){
    			//增加 tag 查询条件
    			$condition [] = ['and'=>['in', 'sku', (new Query())->select('sku')->from('pd_product_tags')->where(['tag_id' => $_GET['txt_tag']])]];
    		}
    	}
    	 
    	if (isset($_GET['txt_brand'])){
    		if (trim($_GET['txt_brand'])!="" && $_GET['txt_brand'] != "all"){
    			//增加 brand 查询条件
    			$condition [] = ['and'=>['brand_id'=> $_GET['txt_brand'] ]];
    		}
    	}
    	 
    	if (isset($_GET['txt_supplier'])){
    		if (trim($_GET['txt_supplier'])!="" && $_GET['txt_supplier'] != "all"){
    			//增加 supplier 查询条件
    			if(is_numeric($_GET['txt_supplier']) && !empty($_GET['txt_supplier'])){
    				$condition [] = ['and'=>['in', 'sku', (new Query())->select('sku')->from('pd_product_suppliers')->where(['supplier_id' => $_GET['txt_supplier']])]];
    			}
    			if(empty($_GET['txt_supplier'])){
    				$condition [] = ['and'=>[
    					'or',['sku'=>(new Query())->select('sku')->from('pd_product_suppliers')->where(['supplier_id' => $_GET['txt_supplier']])],['supplier_id'=>0]
    				]];
    			}
    		}
    	}
    	 
    	if (isset($_GET['txt_status'])){
    		if (trim($_GET['txt_status'])!="" && $_GET['txt_status'] != "all"){
    			//增加 status 查询条件
    			$condition [] = ['and'=>['status'=>$_GET['txt_status'] ]];
    		}
    	}
    	if (isset($_GET['product_type'])){
    		if (trim($_GET['product_type']) != "" && $_GET['product_type'] != "all"){
    			//增加 type 查询条件
    			if($_GET['product_type'] == 'C')
    				$condition [] = ['and'=>['type'=>['C', 'L'] ]];
    			else 
    				$condition [] = ['and'=>['type'=>$_GET['product_type'] ]];
    		}
    	}
    	if (isset($_GET['class_id']) && $_GET['class_id'] != ''){
    		if($_GET['class_id'] == '0'){
    			//未分类
    			$condition [] = ['and' => 'class_id not in (select ID from pd_product_classification)'];
    		}
    		else{
	    	    $class_id_list = array();
	    	    $class_id_list[] = $_GET['class_id'];
	    		//查询此节点、及其下级节点
	    		$node = ProductClassification::findOne(['ID' => $_GET['class_id']]);
	    		if(!empty($node)){
	    			$query = ProductHelper::GetProductClassQuery();
	    			$nodes = $query->andWhere("substring(number, 1, length('".$node['number']."'))='".$node['number']."'")->asArray()->all();
	    		    foreach($nodes as $val){
	    		        $class_id_list[] = $val['ID'];
	    		    }
	    		}
	    		$condition [] = ['and'=>['class_id'=>$class_id_list ]];
    		}
    	}
    	 
    	if (empty($condition)) $condition = [];
    	 
    
    	$data = ProductHelper::getProductlist($condition , $sort , $order);
    	
    	//筛选内容
    	$search_condition['type'] = 'search contidion';
    	$search_condition['txt_search'] = isset($_GET['txt_search']) ? $_GET['txt_search'] : '';
    	$search_condition['txt_tag'] = isset($_GET['txt_tag']) ? $_GET['txt_tag'] : '';
    	$search_condition['txt_brand'] = isset($_GET['txt_brand']) ? $_GET['txt_brand'] : '';
    	$search_condition['txt_supplier'] = isset($_GET['txt_supplier']) ? $_GET['txt_supplier'] : '';
    	$search_condition['txt_status'] = isset($_GET['txt_status']) ? $_GET['txt_status'] : '';
    	$search_condition['product_type'] = isset($_GET['product_type']) ? $_GET['product_type'] : '';
    	$search_condition['sort'] = $sort;
    	$search_condition['order'] = $order;
    	$search_condition['search_type'] = isset($_GET['search_type']) ? $_GET['search_type'] : '';
    	$search_condition['class_id'] = isset($_GET['class_id']) ? $_GET['class_id'] : '';
    	$search_condition = json_encode($search_condition);
    	$search_condition = base64_encode($search_condition);
    	 
    	//获取状态映射数组
    	//$model = new Product();
    	//$wholeStatusMapping = $model->getStatusLabel('',true);
    	$statusMapping = ProductHelper::getProductStatus();
    	$typeMapping = ProductHelper::getProductType();
    	 
    	$brandData = BrandHelper::ListBrandData();
    	$tagData = Tag::find(['1'=>'1'])->asArray()->all();
    	 
    	// user pd data
    	//供应商数据
    	$supplierData = SupplierHelper::ListSupplierData();
    	//产品属性数据 //取频率最高的100个
    	$prodFieldNames=ProductFieldHelper::getFieldNames('use_freq','DESC',100);
    	$prodFieldata=[];
    	foreach ($prodFieldNames as $id=>$name){
    		$valueByName=ProductFieldValue::find()->where(['field_id'=>$id])->asArray()->all();
    		foreach ($valueByName as $v){
    			$prodFieldata[$name][]=$v['value'];
    		}
    	}
    	//品牌数据
    	$brandData = BrandHelper::ListBrandData();
    	//标签数据
    	$tagData = Tag::find(['1'=>'1'])->asArray()->all();
    	//分类信息
    	$query = ProductHelper::GetProductClassQuery();
    	$classData = $query->asArray()->all();
    	// user pd data end
    	
    	//判断子账号是否有编辑权限
    	$is_catalog_edit = true;
    	$is_catalog_export = true;
    	$is_catalog_delete = true;
    	$isMainAccount = UserApiHelper::isMainAccount();
    	if(!$isMainAccount){
    		//查询版本号
    		$version = UserApiHelper::GetPermissionVersion();
    		if(!empty($version)){
	    		$ischeck = UserApiHelper::checkModulePermission('catalog_edit');
	    		if(!$ischeck){
	    			$is_catalog_edit = false;
	    			if(empty($version) || $version < 1.4){
	    				$is_catalog_delete = false;
	    			}
	    		}
    		}
    		if(!empty($version) && $version > 1.3){
    			$ischeck = UserApiHelper::checkModuleChliPermission('catalog_export');
    			if(!$ischeck){
    				$is_catalog_export = false;
    			}
    			$ischeck = UserApiHelper::checkModuleChliPermission('catalog_delete');
    			if(!$ischeck){
    				$is_catalog_delete = false;
    			}
    		}
    	}
    	
    	//分类统计数
    	$classCount = ProductHelper::getProductClassCount();
    	 
    	return $this->render('index', [
    			'productData' => $data ,
    			'statusMapping'=>$statusMapping ,
    			'brandData'=>$brandData,
    			'tagData'=>$tagData,
    			'supplierData'=>$supplierData,
    			'typeMapping'=>$typeMapping,
    			'prodFieldata'=>$prodFieldata,
    	        'search_condition'=>$search_condition,
    			'classCount' => $classCount,
    			'class_html' => ProductHelper::GetProductClass(0, $classCount),
    	        'classData' => $classData,
    			'is_catalog_edit' => $is_catalog_edit,
    			'is_catalog_export' => $is_catalog_export,
    			'is_catalog_delete' => $is_catalog_delete,
    		]);
    
    }//end of actionList
    
    
    /**
     * product list 页面数据获取
     */
    public function actionList(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/list");
    	\Yii::warning("actionList params:".print_r(\Yii::$app->params,true));
    	 
    	return self::actionIndex();
    }//end of actionList
    
    /**
     * 生成  查看商品 的HTML 
     * for action view only！
     * 由于create,edit,copy，view等弹窗想要再弹窗操作显示产品详情(多重view)，js控件会对元素id选择混乱导致显示错误。
     * 因此重做一个view界面，图片，别名，属性，标签等都不通过js生成html,避免上述问题
     * @return Ambigous <string, string>
     */
    public function actionViewProduct(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/view-product");
    	$PdAttr = [];
    	$taglist = [];
    	$pdsupplierlist = [];
    	$photos = [];
    	$aliasList = [];

    	if (!empty($_GET['sku']) ){
    		$sku = base64_decode($_GET['sku']) ;
    		$model = Product::findOne(['sku'=>$sku]);
    		if (!empty($model->other_attributes)){
    			$PdAttr =ProductHelper::PordAttrconvertStringToArray($model->other_attributes);
    		}
    		$tmpRt = TagHelper::getOneProductTags($sku);
    		$taglist = $tmpRt['tags'];

    		$sql = "select a.purchase_price , a.purchase_link, b.name from pd_product_suppliers a , pd_supplier b where a.supplier_id = b.supplier_id and a.sku =:sku order by priority asc ";
    		$command = Yii::$app->get('subdb')->createCommand($sql);
    		// Bind the parameter
    		$command->bindValue(':sku' , $sku);
    		$pdsupplierlist = $command->queryAll();
    		$photos = PhotoHelper::getPhotosBySku($sku, 'OR');
    
    		//brand id 转换成brand name
    		if (!empty($model->brand_id)){
    			$BrandData = Brand::findOne($model->brand_id);
    			if (! empty($BrandData['name']))
    				$model->brand_id = $BrandData['name'];
    		}

    		//get product alias
    		$aliasList = ProductAliases::find()
    			->andWhere(['sku'=>$sku])->andWhere("sku!=alias_sku or (sku=alias_sku and (platform!='' or selleruserid!=''))")
    			->asArray()
    			->All();
    	}
    	 
    	if (empty($model) ){
    		$model = new Product();
    	}
    	 
    	//变参or捆绑子产品信息
    	$children = [];
    	$configFieldIds = '';
    	$configField = [];
    	if(isset($sku) && $model->type=='C'){//查看变参产品
    		$relationship = ProductConfigRelationship::find()->where(['cfsku' => $sku ])->asArray()->all();
    		if($relationship!==null){
    			foreach ($relationship as $index=>$relation){
    				$assku=$relation['assku'];
    				$configFieldIds = $relation['config_field_ids'];
    				$child = Product::find()->where(['sku'=>$assku])->asArray()->all();
    				if(!empty($child)){
    					$child = $child[0];
    					$child['attrArr'] = array();
    					$other_attr = $child['other_attributes'];
    					$attr_pair  = explode(";", $other_attr);
    					foreach ($attr_pair as $i=>$pair){
    						$a_attr = explode(":", $pair);
    						if(!empty($a_attr[0]))
    							$child['attrArr'][$a_attr[0]] = isset($a_attr[1])?$a_attr[1]:'';
    					}
    					$children[] = $child;
    				}
    			}
    		}
    	}
    	if(isset($sku) && $model->type=='B'){//查看捆绑产品
    		$relationship = ProductBundleRelationship::find()->where(['bdsku' => $sku ])->asArray()->all();
    		if($relationship!==null){
    			foreach ($relationship as $index=>$relation){
    				$assku=$relation['assku'];
    				$bundle_qty = $relation['qty'];
    				$child = Product::find()->where(['sku'=>$assku])->asArray()->all();
    				if(!empty($child)){
    					$child = $child[0];
    					$child['bundle_qty']=$bundle_qty;
    					$children[] = $child;
    				}
    			}
    		}
    	}

    	$fleldIds = explode(",", $configFieldIds);
    	if(!empty($fleldIds)){
    		$Fields = ProductField::find()->select('field_name')->where(['in','id',$fleldIds])->asArray()->all();
    		foreach ($Fields as $field){
    			$configField[]=$field['field_name'];
    		}
    	}
    	
    	//整理别名显示的店铺名称
    	$ret_plat = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
    	foreach ($aliasList as $k => $alias){
    		$aliasList[$k]['shopname'] = '';
    		if(!empty($alias['selleruserid'])){
    			$aliasList[$k]['shopname'] = '未指定';
    			if(!empty($alias['platform'])){
    				$platform = $alias['platform'];
    				$selleruserid = $alias['selleruserid'];
    				if(!empty($ret_plat[$platform]) && !empty($ret_plat[$platform][$selleruserid])){
    					$aliasList[$k]['shopname'] = $ret_plat[$platform][$selleruserid];
    				}
    			}
    		}
    	}
    	
    	//分类信息
    	$class_name = '未分类';
    	$query = ProductHelper::GetProductClassQuery();
    	$classData = $query->where(['ID' => $model->class_id])->asArray()->one();
    	if(!empty($classData)){
    	    $class_name = $classData['name'];
    	}

    	if($model->type == 'C'){
    		return $this->renderPartial('_view_prod',[
    				'model'=>$model ,
    				'PdAttr'=>$PdAttr ,
    				'taglist'=>$taglist,
    				'pdsupplierlist'=>$pdsupplierlist,
    				'photos'=>$photos ,
    				'children'=>$children,
    				'configField'=>$configField,
    				'productStatus'=>ProductHelper::getProductStatus(),
    				'aliaslist'=>$aliasList,
    		        'class_name' => $class_name,
    				]);
    	}
    	elseif($model->type == 'B'){
    		return $this->renderPartial('_view_prod',[
    				'model'=>$model ,
    				'PdAttr'=>$PdAttr ,
    				'taglist'=>$taglist,
    				'pdsupplierlist'=>$pdsupplierlist,
    				'photos'=>$photos ,
    				'children'=>$children,
    				'productStatus'=>ProductHelper::getProductStatus(),
    				'aliaslist'=>$aliasList,
    		        'class_name' => $class_name,
    				]);
    	}else{
    		return $this->renderPartial('_view_prod',[
    				'model'=>$model ,
    				'PdAttr'=>$PdAttr ,
    				'taglist'=>$taglist,
    				'pdsupplierlist'=>$pdsupplierlist,
    				'photos'=>$photos ,
    				'children'=>$children,
    				'configField'=>$configField,
    				'productStatus'=>ProductHelper::getProductStatus(),
    				'aliaslist'=>$aliasList,
    		        'class_name' => $class_name,
    				]);
    	}
    }
    
    /**
     * 生成  添加商品 /修改商品 的HTML
     * @return Ambigous <string, string>
     */
    public function actionGet_create_product_form(){
    	// tt = edit && sku != ""
    	$PdAttr = [];
    	$taglist = [];
    	$pdsupplierlist = [];
    	$photos = [];
    	$aliasList = [];

    	$fromSku='';//由普通商品转成config或bundle时，记录原sku
    	
    	if (!empty($_GET['tt']) && (!empty($_GET['sku']) or !empty($_GET['fromSku'])) ){
    		
    		AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/get_create_product_form");
    		
    		$sku = (!empty($_GET['sku'])) ? base64_decode($_GET['sku']) : base64_decode($_GET['fromSku']);
    		$fromSku=$sku;
    		$model = Product::findOne(['sku'=>$sku]);
    		if (!empty($model->other_attributes)){
    			$PdAttr =ProductHelper::PordAttrconvertStringToArray($model->other_attributes);
    		}
    		$tmpRt = TagHelper::getOneProductTags($sku);
    		$taglist = $tmpRt['tags'];
    		
    		//去掉keyword的引号。免除SQL注入
    		//$sku = str_replace("'","",$sku);
    		
    		
    		
    		//$sql = "select * from pd_tag a  where EXISTS (select 1 from pd_product_tags b  WHERE  a.tag_id = b.tag_id and b.sku = '".$sku."' )";
    		//$taglist = Yii::$app->get('subdb')->createCommand($sql)->queryAll();
    		//ProductSuppliers::find()
    		$sql = "select a.purchase_price, a.purchase_link, b.name from pd_product_suppliers a , pd_supplier b where a.supplier_id = b.supplier_id and a.sku =:sku order by priority asc ";
    		$command = Yii::$app->get('subdb')->createCommand($sql);
    		// Bind the parameter
    		$command->bindValue(':sku' , $sku);
    		$pdsupplierlist = $command->queryAll();
    		$photos = PhotoHelper::getPhotosBySku($sku, 'OR');
    		
    		//brand id 转换成brand name
    		if (!empty($model->brand_id)){
    			$BrandData = Brand::findOne($model->brand_id);
    			if (! empty($BrandData['name']))
    				$model->brand_id = $BrandData['name'];
    		}
    		
    		
    		//get product alias 
    		$aliasList = ProductAliases::find()
    		->andWhere(['sku'=>$sku])->andWhere("sku!=alias_sku or (sku=alias_sku and (platform!='' or selleruserid!=''))")
    		->asArray()
    		->All();
    		
    	}
    	
    	if (empty($model) ){
    		$model = new Product();
    	}
    	
		//变参or捆绑子产品信息
    	$children = [];
    	$configFieldIds = '';
    	$configField = [];
    	if(empty($_GET['fromSku']) && $model->type=='C'){//新建、修改、查看变参产品
	    	$relationship = ProductConfigRelationship::find()->where(['cfsku' => $sku ])->asArray()->all();
	    	if($relationship!==null){
	    		foreach ($relationship as $index=>$relation){
	    			$assku=$relation['assku'];
	    			$configFieldIds = $relation['config_field_ids'];
	    			$child = Product::find()->where(['sku'=>$assku])->asArray()->all();
	    			//$child = $child[0];
					if(!empty($child)){
						$child = $child[0];
						//print_r($child);
		    			$child['attrArr'] = array();
		    			$other_attr = $child['other_attributes'];
		    			$attr_pair  = explode(";", $other_attr);
		    			foreach ($attr_pair as $i=>$pair){
		    				$a_attr = explode(":", $pair);
		    				if(!empty($a_attr[0]))
		    					$child['attrArr'][$a_attr[0]] = isset($a_attr[1])?$a_attr[1]:'';
		    			}
		    			$children[] = $child;
					}
	    		}
	    	}
    	}
    	if(!empty($_GET['fromSku']) && $model->type=='S' && $_GET['type']=='C'){//由普通商品转成config
    		$child = Product::find()->where(['sku'=>$sku])->asArray()->all();
    		//$child = $child[0];
    		if(!empty($child)){
    			$child = $child[0];
    			//print_r($child);
    			$child['attrArr'] = array();
    			$other_attr = $child['other_attributes'];
    			$attr_pair  = explode(";", $other_attr);
    			foreach ($attr_pair as $i=>$pair){
    				$a_attr = explode(":", $pair);
    				if(!empty($a_attr[0]))
    					$child['attrArr'][$a_attr[0]] = isset($a_attr[1])?$a_attr[1]:'';
    			}
    			$children[] = $child;
    		}
    	}
    	if(empty($_GET['fromSku']) && $model->type=='B'){//新建、修改、查看捆绑产品
    		$relationship = ProductBundleRelationship::find()->where(['bdsku' => $sku ])->asArray()->all();
    		if($relationship!==null){
    			foreach ($relationship as $index=>$relation){
    				$assku=$relation['assku'];
    				$bundle_qty = $relation['qty'];
    				$child = Product::find()->where(['sku'=>$assku])->asArray()->all();
    				if(!empty($child)){
    					$child = $child[0];
    					$child['bundle_qty']=$bundle_qty;
    					$children[] = $child;
    				}
    			}
    		}
    	}
    	if(!empty($_GET['fromSku']) && ($model->type=='S' or $model->type=='L') && $_GET['type']=='B'){//由普通商品转成bundle
    		$child = Product::find()->where(['sku'=>$sku])->asArray()->all();
    		if(!empty($child)){
    			$child = $child[0];
    			$child['bundle_qty']=1;
    			$children[] = $child;
    		}
    	}
    	$fleldIds = explode(",", $configFieldIds);
    	if(!empty($fleldIds)){
    		$Fields = ProductField::find()->select('field_name')->where(['in','id',$fleldIds])->asArray()->all();
    		foreach ($Fields as $field){
    			$configField[]=$field['field_name'];
    		}
    	}
    	
    	
    	$brandData = BrandHelper::ListBrandData();
    	$supplierData = SupplierHelper::ListSupplierData();
    	
    	//平台、店铺
    	$platformAccount['所有平台'] = [
    			'0' => '所有店铺',
    		];
    	//$ret_plat = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
    	$ret_plat = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);
    	if(!empty($ret_plat)){
	    	foreach ($ret_plat as $plat => $shop){
	    		if(!empty($shop)){
	    			$platformAccount[$plat] = [
		    			'0' => '所有店铺',
		    		];
	    			foreach ($shop as $k => $v){
	    				$platformAccount[$plat][$k] = $v;
	    			}
	    		}
	    	}
    	}
    	//整理别名显示的店铺名称
    	foreach ($aliasList as $k => $alias){
    		$aliasList[$k]['shopname'] = '';
    		if(!empty($alias['selleruserid'])){
    			$aliasList[$k]['shopname'] = '未指定';
    			if(!empty($alias['platform'])){
	    			$platform = $alias['platform'];
	    			$selleruserid = $alias['selleruserid'];
	    			if(!empty($platformAccount[$platform]) && !empty($platformAccount[$platform][$selleruserid])){
	    				$aliasList[$k]['shopname'] = $platformAccount[$platform][$selleruserid];
	    			}
    			}
    		}
    	}
    	
    	//分类信息
    	$class_name = '未分类';
    	$query = ProductHelper::GetProductClassQuery();
    	$classData = $query->asArray()->all();
    	foreach($classData as $class){
    	    if($model->class_id == $class['ID']){
    	        $class_name = $class['name'];
    	        break;
    	    }
    	}
    	
    	if($model->type == 'C'){
    		return $this->renderPartial('_create_or_edit_prod',[
    				'model'=>$model ,
    				'PdAttr'=>$PdAttr ,
    				'taglist'=>$taglist,
    				'pdsupplierlist'=>$pdsupplierlist,
    				'photos'=>$photos ,
    				'children'=>$children,
    				'configField'=>$configField,
    				'productStatus'=>ProductHelper::getProductStatus(),
    				'aliaslist'=>$aliasList,
    				
    				'userBrandData'=>$brandData,
    				'userSupplierData'=>$supplierData,
    				
    				'platformAccount'=>$platformAccount,
    		        'classData' => $classData,
    		        'class_name' => $class_name,
    				]);
    	}
		elseif($model->type == 'B'){
    		return $this->renderPartial('_create_or_edit_prod',[
    				'model'=>$model ,
    				'PdAttr'=>$PdAttr ,
    				'taglist'=>$taglist,
    				'pdsupplierlist'=>$pdsupplierlist,
    				'photos'=>$photos ,
    				'children'=>$children,
    				'productStatus'=>ProductHelper::getProductStatus(),
    				'aliaslist'=>$aliasList,
    				
    				'userBrandData'=>$brandData,
    				'userSupplierData'=>$supplierData,
    				
    				'platformAccount'=>$platformAccount,
    		        'classData' => $classData,
    				'class_name' => $class_name,
    				]);
    	}else{
	    	return $this->renderPartial('_create_or_edit_prod',[
	    			'model'=>$model , 
	    			'PdAttr'=>$PdAttr , 
	    			'taglist'=>$taglist,
	    			'pdsupplierlist'=>$pdsupplierlist,
	    			'photos'=>$photos ,
	    			'fromSku'=>$fromSku,
	    			'children'=>$children,
	    			'configField'=>$configField,
	    			'productStatus'=>ProductHelper::getProductStatus(),
	    			'aliaslist'=>$aliasList,
	    			
	    			'userBrandData'=>$brandData,
	    			'userSupplierData'=>$supplierData,
	    			
	    			'platformAccount'=>$platformAccount,
	    	        'classData' => $classData,
	    			'class_name' => $class_name,
	    			]);
    	}
/*原阿华view
    	return $this->renderPartial('_form',[
    			'model'=>$model , 
    			'PdAttr'=>$PdAttr , 
    			'taglist'=>$taglist,
    			'pdsupplierlist'=>$pdsupplierlist,
    			'photos'=>$photos , 
    			'aliaslist'=>$aliasList,
    			]);
    	
*/
    }//end of actionGet_create_product_form
    
    /**
     * 生成  添加商品 /修改商品 的HTML
     * @return Ambigous <string, string>
     */
    /*
    public function actionGet_create_config_product_form(){
   		$PdAttr = [];
    	$taglist = [];
    	$pdsupplierlist = [];
    	$photos = [];
    	$children=[];
    	$fromSku='';
    	if (isset($_GET['tt']) && !empty($_GET['fromSku'])){
    		$sku = base64_decode($_GET['fromSku']);
    		$fromSku=$sku;
    		$model = Product::findOne(['sku'=>$sku]);
    		if (!empty($model->other_attributes)){
    			$PdAttr =ProductHelper::PordAttrconvertStringToArray($model->other_attributes);
    		}
    		$tmpRt = TagHelper::getOneProductTags($sku);
    		$taglist = $tmpRt['tags'];
    		
    		//去掉keyword的引号。免除SQL注入
    		//$sku = str_replace("'","",$sku);
    		
    		
    		
    		//$sql = "select * from pd_tag a  where EXISTS (select 1 from pd_product_tags b  WHERE  a.tag_id = b.tag_id and b.sku = '".$sku."' )";
    		//$taglist = Yii::$app->get('subdb')->createCommand($sql)->queryAll();
    		//ProductSuppliers::find()
    		$sql = "select a.purchase_price , b.name from pd_product_suppliers a , pd_supplier b where a.supplier_id = b.supplier_id and a.sku =:sku order by priority asc ";
    		$command = Yii::$app->get('subdb')->createCommand($sql);
    		// Bind the parameter
    		$command->bindValue(':sku' , $sku);
    		$pdsupplierlist = $command->queryAll();
    		$photos = PhotoHelper::getPhotosBySku($sku, 'OR');
    		
    		//brand id 转换成brand name
    		if (!empty($model->brand_id)){
    			$BrandData = Brand::findOne($model->brand_id);
    			if (! empty($BrandData['name']))
    				$model->brand_id = $BrandData['name'];
    		}
    		$child = Product::find()->where(['sku'=>$sku])->asArray()->all();
    		//$child = $child[0];
    		if(!empty($child)){
    			$child = $child[0];
    			//print_r($child);
    			$child['attrArr'] = array();
    			$other_attr = $child['other_attributes'];
    			$attr_pair  = explode(";", $other_attr);
    			foreach ($attr_pair as $i=>$pair){
    				$a_attr = explode(":", $pair);
    				if(!empty($a_attr[0]))
    					$child['attrArr'][$a_attr[0]] = isset($a_attr[1])?$a_attr[1]:'';
    			}
    			$children[] = $child;
    		}
    	}
    	
    	if (empty($model) ){
    		$model = new Product();
    	}
    	$configField = [];
    	$prod_attr_has=[];
    	foreach ($PdAttr as $Attr){
    		$prod_attr_has[]=$Attr['key'];
    	}
    	$queryParams = array(array('condition'=>'in','name'=>'field_name','value'=>$prod_attr_has ));
    	$queryRtn = ProductFieldHelper::listData($page=1, $rows=3, $sort='use_freq', $order='ASC', $queryParams);
    	if (count($queryRtn['rows'])>0){
	    	foreach ($queryRtn['rows'] as $row){
	    		$configField[]=$row['field_name'];
	    	}
    	}
    	return $this->renderPartial('_create_or_edit_prod',[
					'model'=>$model ,
    				'PdAttr'=>$PdAttr ,
    				'taglist'=>$taglist,
    				'pdsupplierlist'=>$pdsupplierlist,
    				'photos'=>$photos ,
    				'configField' => $configField,
    				'children'=>$children,
    				'fromSku'=>$fromSku,
    				'productStatus'=>ProductHelper::getProductStatus(),
    			]);
    	 
    }//end of actionGet_create_product_form
    */
    
    
    /**
     * 保存产品 
     */
    public function actionSave_product(){
    	if(isset($_POST['Product'])){
    		AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/save_product");
    		
    		if(!empty($_POST['Product']['sku'])){
    			$_POST['Product']['sku'] = trim($_POST['Product']['sku']);
    		}
    		
    		$isUpdate = false;
    		if (!empty($_POST['tt']) && !empty($_POST['Product']['sku'])){
    			$model = Product::findOne(['sku'=>$_POST['Product']['sku']]);
    			$isUpdate = ($_POST['tt']=='edit');
    			
    			//新增SKU时，已作为别名时，则不可新增
    			if(!$isUpdate){
	    			$rootSku = ProductHelper::getRootSkuByAlias($_POST['Product']['sku']);
	    			if(!empty($rootSku) && $rootSku!==$_POST['Product']['sku']){
	    				exit(json_encode (array('错误' => "sku:".$_POST['Product']['sku'].TranslateHelper::t('是商品SKU：').$rootSku." 的别名，不能使用。" )) );
	    			}
    			}
    		}
    		
    		if (empty($model)){
    			$model = new Product();
    		}else{
    			if(!$isUpdate)
    				exit(json_encode (array('错误' => "sku:".$_POST['Product']['sku'].TranslateHelper::t('已经存在') )) );
    		}
    		
    		//后台将品牌名称转换成品牌编号
    		if (isset($_POST['Product']['brand_id']) && $_POST['Product']['brand_id']!==''){
    			$getBrandIDResult = BrandHelper::getBrandId($_POST['Product']['brand_id'],true);
    			if ($getBrandIDResult['success'])
    			$_POST['Product']['brand_id'] = $getBrandIDResult['brand_id'];
    		}
    		
    		//后台将供应商名称转换成供应商编号
    		if (isset($_POST['ProductSuppliers']['supplier_id']) ){
    			foreach($_POST['ProductSuppliers']['supplier_id'] as &$row){
    				$row = trim($row);
    				if($row=='') continue;
    				$getSupplierIDResult = SupplierHelper::getSupplierId($row,true);
    				if ($getSupplierIDResult['success'])
    					$row= $getSupplierIDResult['supplier_id'];
    			}
    		}
    		
    		//去除空标签
    		$unemptyTagName=[];
    		if (isset($_POST['Tag']['tag_name'])){
    			foreach($_POST['Tag']['tag_name'] as &$row){
    				if (trim($row)!=='')
    					$unemptyTagName[]=$row;
    			}
    		}
			if(empty($unemptyTagName))
				$_POST['Tag']=[];
			else
				$_POST['Tag']['tag_name']=$unemptyTagName;

    		exit(json_encode(ProductHelper::saveProduct($model, $_POST,$isUpdate)));
    	}
    	
    }//end of actionSave_product
    
    public function actionTest(){
    	$sku = 'OP9000000000865';
    	$root=ProductHelper::getRootSkuByAlias($sku);
    	exit($root);
    	/*
    	$model = new Product();
    	
    	return ;
    	$query = Product::find();
    	$query->andWhere(['status'=>$_GET['status']]);
    	$query->andWhere(" status = '".$_GET['status']."'");
    	$rt = $query->all();
    	echo json_encode($rt);
    	
    	return ;
    	
    	if (isset($_POST['ProductSuppliers']['supplier_id'])){
    		foreach($_POST['ProductSuppliers']['supplier_id'] as &$row){
    			$getSupplierIDResult = SupplierHelper::getSupplierId($row,true);
    			if ($getSupplierIDResult['success'] && $getSupplierIDResult['supplier_id']!==0)
    				$row= $getSupplierIDResult['supplier_id'];
    		}
    	}
    	echo print_r($_POST['ProductSuppliers']);
    	
    	RETURN ;
    	$getBrandIDResult = BrandHelper::getBrandId($_POST['brand_id'],true);
    	if ($getBrandIDResult['success'])
    		$_POST['brand_id'] = $getBrandIDResult['brand_id'];
    	echo $_POST['brand_id'];
    	*/
    }
    
    /**
     +----------------------------------------------------------
     * 删除指定SKU的商品
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/01/30				初始化
     +----------------------------------------------------------
     **/
    public function actionDelete()
    {
    	
    	$result ['success'] = true;
    	$result ['message'] = '';
    	
    	if( \Yii::$app->request->isAjax ){
    		AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/delete");
    		if (!empty ($_POST['sku'])){
    			//防止 sku 含单 双引号
    			$sku = base64_decode($_POST['sku']);
    			
    			//判断是否存在未完成订单已配对
    			$order_count = \eagle\modules\order\helpers\OrderGetDataHelper::getOrderCountByRootSKU($sku);
    			if(!empty($order_count) && $order_count > 0){
    				$result ['success'] = false;
    				$result ['message'] = TranslateHelper::t('此商品已被被订单进行了配对，请解除配对后再进行删除！');
    			}
    			else{
    				$rtn = ProductHelper::deleteProduct($sku);
    				if(!$rtn){
    					$result['success']= false;
    					$result['message'].= $sku.TranslateHelper::t(' 删除商品失败')."<br>";
    				}
    				else{
    				    //写入操作日志
    				    UserHelper::insertUserOperationLog('catalog', "删除商品, SKU: $sku");
    				}
    			}
    		}else{
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('请选择删除的商品');
    		}
    		exit(json_encode($result));
    	}else{
    		$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    	}
    	
    	/*
    	//检查sku是否处于业务流程中
    	$criteria = new CDbCriteria;
    	$criteria->compare('sku', $id);
    	$isInOrder = count(OdOrderItem::model()->findAll($criteria));
    	$isInPurchase = count(PurchaseItems::model()->findAll($criteria));
    	$isInStock = count(ProductStock::model()->findAll($criteria));
    	if ( $isInOrder>0 || $isInPurchase>0 || $isInStock>0 ) {
    		exit(CJSON::encode(array('problem' => 'in business')));
    	}
    	
    	if (isset(Yii::app()->request->isAjaxRequest))
    	{
    		exit(json_encode(array('success' => $result)));
    	} else
    		$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    	*/
    }
    
    /**
     +----------------------------------------------------------
     * 批量删除指定SKU的商品
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name		date				note
     * @author		lzhl		2015/05/29			初始化
     +----------------------------------------------------------
     **/
    public function actionBatchDeleteProduct()
    {
    	AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/batch-delete-product");
    	$result['success'] = true;
    	$result['message'] = '';
    	 

    	if (!empty ($_POST['skulist'])){
    	    $delete_log = '';
    		$skuList=json_decode($_POST['skulist'],true);
    		foreach ($skuList as $sku){
    			//防止 sku 含单 双引号
    			$sku = base64_decode($sku);
    			
    			//判断是否存在未完成订单已配对
    			$order_count = \eagle\modules\order\helpers\OrderGetDataHelper::getOrderCountByRootSKU($sku);
    			if(!empty($order_count) && $order_count > 0){
    				$result ['success'] = false;
    				$result ['message'] .= $sku.TranslateHelper::t(' 已被被订单进行了配对，请解除配对后再进行删除！')."<br>";
    			}
    			else{
    				$rtn = ProductHelper::deleteProduct($sku);
    				if(!$rtn){
    					$result['success']= false;
    					$result['message'].= $sku.TranslateHelper::t(' 删除失败')."<br>";
    				}
    				else{
    				    $delete_log .= $sku.", ";
    				}
    			}
    		}
    		
    		//写入操作日志
    		UserHelper::insertUserOperationLog('catalog', "批量删除商品, SKU: $delete_log");
    		
    	}else{
    		$result['success'] = false;
    		$result['message'] = TranslateHelper::t('请选择删除的商品');
    	}
    	exit(json_encode($result));

    }
    /**
     +----------------------------------------------------------
     * AJAX修改产品状态
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/05/06				初始化
     +----------------------------------------------------------
     **/
    public function actionUpdateStatus(){
    	
    	if (Yii::$app->request->isPost)
    	{
    		if (!empty($_POST['sku'])){
    			//防止 sku 含单 双引号
    			$sku = base64_decode($_POST['sku']);
    			$criteria = ['sku'=>$sku];
    			$attributes = array('status' => $_POST['status'], 'update_time' => date('Y-m-d H:i:s', time()));
    			$rtn = Product::updateAll($attributes, $criteria);
    			if ($rtn >0){
    				$result ['success'] =true;
    			}else{
    				$result ['success'] =false;
    			}
    			
    			exit(json_encode($result));
    		}else{
    			exit(json_encode($result));
    		}
    		
    	}
    	else
    		throw new HttpException(400, 'Invalid request. Please do not repeat this request again.');
    	
    	
    }
    
    /**
     +----------------------------------------------------------
     * AJAX批量修改产品状态
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2014/05/06				初始化
     +----------------------------------------------------------
     **/
    public function actionBatchUpdateStatus(){
    	if( \Yii::$app->request->isAjax ){
    		if (!empty ($_POST['skulist'])){
    			//传递的参数为数组 直接使用
    			if (is_array($_POST['skulist'])){
    				$skulist = $_POST['skulist'];
    			}elseif(is_string($_POST['skulist'])){
    				//传递的参数为字符 json decode 后使用
    				$skulist = json_decode($_POST['skulist']);
    			}
    			
    			if (!empty($skulist)){
    				//统计update 结果数据
    				$ct = ['success'=>0 , 'failure'=>0];
    				
    				//更新商品状态
    				foreach($skulist as $sku ){
    					$sku = base64_decode($sku);
    					$criteria = ['sku'=>$sku];
    					$attributes = array('status' => $_POST['status'], 'update_time' => date('Y-m-d H:i:s', time()));
    					$rtn = Product::updateAll($attributes, $criteria);
    					if ($rtn >0){
    						$ct ['success'] ++;
    					}else{
    						$ct ['failure'] ++;
    					}
    				}
    				
    				$result ['success'] = ($ct ['success']>0);
    				$result ['message'] = TranslateHelper::t('本次批量更新'.count($skulist).'个商品, 其实更新成功'.$ct ['success'].'个,更新失败'.$ct ['failure'].'个' );
    				
    			}else{
    				$result ['success'] = false;
    				$result ['message'] = TranslateHelper::t('请选择批量更新的商品');
    			}
    		}else{
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('请选择批量更新的商品');
    		}
    		exit(json_encode($result));
    	}else{
    		$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    	}
    }//end of actionBatchUpdateStatus
    
    /**
     +----------------------------------------------------------
     * AJAX 获取商品导入页面
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		ouss	2014/05/06				初始化
     +----------------------------------------------------------
     **/
    public function actionSelect_product(){    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/select_product");
    	/*
    	$page = 1;
    	$rows = 5;
    	$sort = "sku";
    	$order = "asc";
    	$queryString = "";
    	$productList = ProductHelper::listData($page, $rows, $sort, $order, $queryString);
    	*/
    	\Yii::$app->request->setQueryParams($_REQUEST);
    	if(!empty($_GET['sort'])){
    		$sort = $_GET['sort'];
    		if( '-' == substr($sort,0,1) ){
    			$sort = substr($sort,1);
    			$order = 'desc';
    		} else {
    			$order = 'asc';
    		}
    	}
    	 
    	if(empty($sort))$sort = 'sku';
    	if(empty($order))$order = 'asc';
    	
    	if (isset($_GET['txt_search'])){
    		$txt_search = trim(str_ireplace(array("\r\n", "\r", "\n", "chr(10)", "chr(13)", "CHR(10)", "CHR(13)"), '', $_GET['txt_search']));
    		if($txt_search!==''){
    			$condition [] =['orlikelist'=>$txt_search];
    		}
    	}
    	if (empty($condition)) $condition = [];
    	
    	//2015-05-13 select product 只显示  普通(S)  和 子产品 (L)
    	$condition [] = ['and'=>['type'=>['S','L']]];
    	
    	if (isset($_GET['excludeSkuList'])){
    		$condition [] = ['and'=>['not in','sku', $_GET['excludeSkuList']]];
    	}
    	
    	if (isset($_GET['class_id']) && $_GET['class_id'] != ''){
    		$class_id_list = array();
    		$class_id_list[] = $_GET['class_id'];
    		//查询此节点、及其下级节点
    		$node = ProductClassification::findOne(['ID' => $_GET['class_id']]);
    		if(!empty($node)){
    			$query = ProductHelper::GetProductClassQuery();
    			$nodes = $query->where("substring(number, 1, length('".$node['number']."'))='".$node['number']."'")->asArray()->all();
    			foreach($nodes as $val){
    				$class_id_list[] = $val['ID'];
    			}
    		}
    		
    		$condition [] = ['and'=>['class_id'=>$class_id_list ]];
    	}
    	
    	if(!empty($_GET['per-page'])) $pageSize = $_GET['per-page'];
    	else $pageSize = 5;
    	//var_dump($_REQUEST);
    	$data =ProductHelper::getProductlist($condition , $sort , $order , $pageSize, false, true);
    	 
    	/*
    	//获取状态映射数组
    	$model = new Product();
    	$wholeStatusMapping = $model->getStatusLabel('',true);
    	*/
    	//分类信息
    	$query = ProductHelper::GetProductClassQuery();
    	$classData = $query->asArray()->all();
    	
    	echo $this->renderAjax('_select_product.php' , [
    			'productData'=>$data ,
    			'wholeStatusMapping'=>ProductHelper::getProductStatus(),
    			'classData' => $classData,
    			]);
    	
    	
    	
    	//exit(json_encode($productList));
    }//end of actionSelect_product
    
    /**
     +----------------------------------------------------------
     * AJAX 获取商品信息
     * 如果不存在该sku商品，返回空的result['info']
     +----------------------------------------------------------
     * @access 	public
     * @param	$_GET
     * @return	result[
     * 					'existing'=>boolean,
     * 					'message'=>string,
     * 					'info'=>array()
     * 					]
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2015/05/06		初始化
     +----------------------------------------------------------
     **/
    public function actionCheckSkuExisting(){
    	$result['existing'] = false;
    	$result['info'] = [];
    	$result['message']='';
    	if(isset($_GET['sku']) and !empty($_GET['sku'])){
    		$model = Product::findOne(['sku'=>$_GET['sku'] ]);
    		if ($model!==null){
    			$result['existing'] = true;
    			$result['info'] = $model->getAttributes();

    			$attrStr = $model->other_attributes;
    			//for configProd check
    			if($attrStr==null or empty($attrStr)){
    				$result['info']['attrArr']=[];
    			}else{
    				$result['info']['attrArr']=[];
    				$attr_pair  = explode(";", $attrStr);
    				foreach ($attr_pair as $i=>$pair){
    					$a_attr = explode(":", $pair);
    					if(!empty($a_attr[0])){
    						if(isset($a_attr[1]))
    							$result['info']['attrArr'][$a_attr[0]] = $a_attr[1];
    					}
    				}
    			}
    			
    		}
    		else{
    			$result['message']='sku not existing';
    		}
    	}else{
    		$result['message']='sku'.TranslateHelper::t('缺失');
    	}
    	
    	exit ( json_encode($result) );
    }
    
    /**
     +----------------------------------------------------------
     * AJAX 检测别名是否是已存在的商品sku
     +----------------------------------------------------------
     * @access 	public
     * @param	$_GET
     * @return	result[
     * 					'existing'=>boolean,		//全部别名不为已存在商品时，返回false，其余返回true
     * 					'message'=>string,
     * 					'existingList'=>array()		//返回已存在的sku
     * 					]
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2015/09/06		初始化
     +----------------------------------------------------------
     **/
    public function actionCheckAliasIsExistingSku(){
    	$result['existing'] = false;
    	$result['existingList'] = [];
    	$result['message']='';
    	$skus='';
    	if(isset($_GET['sku']) and !empty($_GET['sku']))
    		$skus = $_GET['sku'];
    	if(isset($_GET['skus']) and !empty($_GET['skus']))
    		$skus = $_GET['skus'];
    	
    	$skus = explode('@@&@@', $skus);
    	
    	if(!empty($skus)){
    		$models = Product::find()->where(['sku'=>$skus ])->all();
    		if (!empty($models)){
    			$result['existing'] = true;
    			foreach ($models as $model){
    				$result['existingList'][$model->sku] = 1;
    			}
    		}
    		else{
    			$result['message']='all skus not existing';
    		}
    	}else{
    		$result['message']='skus'.TranslateHelper::t('缺失');
    	}
    	 
    	exit ( json_encode($result) );
    }
    
    public function actionImportChiProductAlias(){
    	//import alias data
    	$sql = "insert into pd_product_aliases  (`sku` , `alias_sku` , `pack`, `comment`)
select sku , alias , pack , 'chi批量导入'  as comment
from wm_sku_alias where  alias not in (select alias_sku from pd_product_aliases ) ";
    	$command = Yii::$app->get('subdb')->createCommand($sql);
    	$reuslt = $command->execute();
    	echo "<br> step 1 update ".$reuslt;
    	
    	$sql = "update pd_product_aliases set forsite = 'cdiscount' where alias_sku in (select alias from wm_sku_alias where for_sites = 'cdiscount')";
    	$command = Yii::$app->get('subdb')->createCommand($sql);
    	$reuslt = $command->execute();
    	echo "<br> step 2 update ".$reuslt;
    	
    	
    	$sql = "update pd_product_aliases set forsite = 'amazon' where alias_sku in (select alias from wm_sku_alias where for_sites like '%amazon%')";
    	$command = Yii::$app->get('subdb')->createCommand($sql);
    	$reuslt = $command->execute();
    	echo "<br> step 3 update ".$reuslt;
    	
    }
    
    public function actionImportChiProductPhoto(){
    	
    	//获取chi的图片信息
    	$sql = "select * from chi_photos_data  where photo_info <>'[]'   and sku in (select sku from pd_product) and sku not in (select sku from pd_photo) limit 200";
    	$command = Yii::$app->get('subdb')->createCommand($sql);
    	$table_list = $command->queryAll();
    	$message = " total product =".count($table_list);
    	echo $message;
    	ProductApiHelper::importProductPhotoDataFromCHI($table_list);
    }
    
    public function actionImportChiProduct(){
    	/*
    	 * 
    	 */
    	
    	set_time_limit(0);
    	$uid = 1;
    	$i = 1;
    	try {
    		$sub_db = Yii::$app->get('subdb');
    		do{
    			unset($table_list);
    			$sql = "select * from wm_basic  where  cp_number not in (select sku from pd_product) limit 500";
    			$command = $sub_db->createCommand($sql);
    			$table_list = $command->queryAll();
    			$message = "$i total product =".count($table_list);
    			\Yii::info(['Catalog',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
    			unset($command);
    			foreach($table_list as &$row){
    				 
    				$sku = $row['cp_number'];
    				$name = $row['cp_name'];
    				$prod_name_ch = $row['cp_name'];
    				$prod_name_en = base64_decode($row['eng_name']);
    				$declaration_value_currency = 'USD';
    				$declaration_value = 0;
    				$create_source = 'chi';
    				$battery='N';
    				$prod_weight=$row['cp_weight'];
    				$declaration_ch='礼品';
    				$declaration_en='gift';
    				$prod_width=0;
    				$prod_length=0;
    				$prod_height=0 ;
    				$photo_primary = '';
    				 
    				$itemId='';
    				//设置状态
    				//“OS”:on sale “RN”:running out “DR”:dropped “AC”：archived “RS”:re-onsale'
    				$chi_status_mapping = [
    				'onsale'=>'OS' ,
    				'drop'=>'DR' ,
    				'runout'=>'RN',
    				];
    				 
    				 
    				$status=(empty($chi_status_mapping[$row['status']])?'OS':$chi_status_mapping[$row['status']]);
    				 
    				//设置长宽高
    				if (!empty($row['cp_cubage'])){
    					$cubageList = explode('*',$row['cp_cubage']);
    					//1*2*3是这样的格式 才设置为长宽高
    					if (count($cubageList) == 3){
    						$prod_width=$cubageList[0] ;
    						$prod_length=$cubageList[1];
    						$prod_height=$cubageList[2] ;
    					}
    				}
    				 
    				 
    				/*
    				 * other attributes 说明
    				* cgway 		采购方法
    				* cp_model 	model
    				* pending 		批次
    				*/
    				$other_attributes_ary = [];
    				$other_attributes_field = ['cgway' , 'cp_model','pending','c_generalcolor'];
    				 
    				foreach($other_attributes_field as $field_name){
    		
    					if (!empty($row[$field_name]) && in_array($field_name, $other_attributes_field)){
    						$other_attributes_ary [$field_name] = $row[$field_name];
    					}
    				}
    				
    				 
    				$other_attributes= json_encode($other_attributes_ary);
    				$message = "save product : ".$sku;
    				\Yii::info(['Catalog',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
    				
    				$supplier_id =  '';
    				
    				//set 设置 other attribute
    				$supplier_name = $row['supplier'];
    				$purchase_price = $row['cp_jj'];
    				echo $supplier_name." and ".$purchase_price;
    				
    				$result = ProductApiHelper::createSimpleProductFromCHI($sku, $uid, $name, $prod_name_ch,$prod_name_en, $photo_primary,
    				$declaration_ch,$declaration_en,$declaration_value_currency,$declaration_value,$prod_weight,$battery,
    				$create_source,$itemId,$status,$prod_width, $prod_length,$prod_height , $other_attributes , $supplier_name,$purchase_price);
    				 
    			}
    			$i++;
    		}while (!empty($table_list) &&  $i<200);
    		
    		
    		
    	} catch (Exception $e) {
    		echo $e->getMessage();
    	}
    	
    	
    	
    }
    
    
    //展示品牌商品范本的列表
    public function actionFanbenList(){
    	$fanbens = ProductFanben::find()->all();
    	return $this->render('fanbenlist',['fanbens'=>$fanbens]);
    }
    
    //认领到平台范本
    public function actionRenlingfanben(){
    	if(\Yii::$app->request->isPost){
    		$fanben = ProductFanben::findOne($_POST['ids']);
    		$fanben = ProductFanben::find()->where(['id'=>$_POST['ids']])->asArray()->one();
    		$fanbenV = ProductFanbenVariance::find()->where(['fanben_id'=>$_POST['ids']])->asArray()->all();
    		unset($fanben['id']);unset($fanben['productid']);
    		$fanben['tags']='no';
    		$fanben['site_id']=5;
    		foreach ($fanbenV as $k=>$v){
    			unset($v['id']);
    			unset($v['fanben_id']);
    			$fanben['variance'][$k]=$v;
    		}
    		
    		try{
    			$product = WishHelper::WishFanbenSave($fanben);
    			$result = [
    					'success'=>true,
    					'message'=>'OK',
    					'fanben_id'=>$product->id
    					];
    		}catch(\Exception $e){
    			$result = [
    					'success'=>false,
    					'message'=>$e->getMessage(),
    					'file'=>$e->getFile(),
    					'line'=>$e->getLine(),
    					'code'=>$e->getCode(),
    					'postData'=>$fanben
    					];
    		};
    		echo json_encode($result);
    	}
    }
    
    /**
     +----------------------------------------------------------
     * 商品条码打印
     +----------------------------------------------------------
     * @param	$_GET
     * @param	skulist  json格式，array("0"=>array(sku,商品名,数量))
     * @param	height   高
     * @param	width    宽
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lgw		  2016/11/28		初始化
     +----------------------------------------------------------
     **/
    public function actionSkuBarcodePrint(){ 	
    	$skulist_json=empty($_GET['skulist'])?'':$_GET['skulist'];
    	$skulist=json_decode($skulist_json);
    	$height=empty($_GET['height'])?'20':$_GET['height'];
    	$width=empty($_GET['width'])?'40':$_GET['width'];
    	
    	$r = ConfigHelper::setConfig("Sku_Barcode", json_encode(array("skubarcode"=>array("height"=>$height,"width"=>$width))));
    	
    	if(!empty($skulist)){
    		//新建
    		$pdf = new \TCPDF("L", "mm", array($height,$width), true, 'UTF-8', false);
    		 
    		//设置页边距
    		$pdf->SetMargins(0, 0, 0);
    		$pdf->setCellPaddings(0, 0, 0, 0);
    		 
    		//删除预定义的打印 页眉/页尾
    		$pdf->setPrintHeader(false);
    		$pdf->setPrintFooter(false);
    		 
    		//设置不自动换页,和底部间距为0
    		$pdf->SetAutoPageBreak(true, 0);
    		
    		$pdf->setCellHeightRatio(1.1); //字体行距
    		
    		foreach ($skulist as $skulistone){
    			$sku =isset($skulistone[0])?$skulistone[0]:'';
    			$skuname=isset($skulistone[1])?$skulistone[1]:'';
    			$qty=isset($skulistone[2])?$skulistone[2]:'';
//     			print_r($sku);die;
    		    			
    			for($i=0;$i<$qty;$i++){
	    			$pdf->AddPage();
	    			
	    			//条码大小
	    			$style = array(
	    					'position' => '',
	    					'align' => 'C',
	    					'stretch' => false,
	    					'fitwidth' => true,
	    					'cellfitalign' => 'C',
	    					'border' => false,
	    					'hpadding' => 'auto',
	    					'vpadding' => 'auto',
	    					'fgcolor' => array(0,0,0),
	    					'bgcolor' => false, //array(255,255,255),
	    					'text' => true,
	    					'font' => 'helvetica',
	    					'fontsize' => 10,
	    					'stretchtext' =>0
	    			);
	    			//根据高度调
	    			if($height==30)
	    				$bcodeheight=20;
	    			else if($height==25)
	    				$bcodeheight=17;
	    			else if($height==20)
	    				$bcodeheight=17;
	    			else 
	    				$bcodeheight=19;
	    			//根据宽度调    			
	    			if($width==100){
	    				$txthigh=$bcodeheight+2.5;
	    				$bcodeheight+=4;
	    			}
	    			else if($width==80){
	    				$txthigh=$bcodeheight+2.5;
	    				$bcodeheight+=4;
	    			}
	    			else if($width==60){
	    				$txthigh=$bcodeheight-0.5;
	    				$bcodeheight+=1;
	    			}
	    			else if($width==50){
	    				$txthigh=$bcodeheight-1.5;
	    			}
	    			else if($width==40)
	    				$txthigh=$bcodeheight-1.5;
	    			else
	    				$txthigh=$bcodeheight;
	    			
	    			$pdf->write1DBarcode($sku, 'C128', 0, 0, $width, $bcodeheight, 9, $style, 'N');
	    			
	    			$pdf->SetFont('msyh', '', 8);
	    			$pdf->writeHTMLCell($width-4, 0, 2,$txthigh, '('.$skuname.')', 0, 1, 0, true, 'C', true);
    			}
    		}
    		$pdf->Output('print.pdf', 'I');
    	}
    }
    
    /**
     +----------------------------------------------------------
     * 商品条码打印页面
     +----------------------------------------------------------
     * @param	skulist  json格式，array("0"=>array(sku,商品名,数量))
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lgw		  2016/11/28		初始化
     +----------------------------------------------------------
     **/
    public function actionSkubarcode(){
    	$skulist_json=empty($_POST['skulist'])?'':$_POST['skulist'];
    	$skulist=json_decode($skulist_json);
    	
    	//读取上次打印的格式
    	$config_json = ConfigHelper::getConfig("Sku_Barcode", "NO_CACHE");
    	$config=json_decode($config_json,true);   //强制json转数组
    	if(!isset($config['skubarcode']))
    		$config=array("skubarcode"=>array("height"=>0,"width"=>0));
    	
    	//转换SKU
    	$skus = array();
    	$asskus = array();
    	foreach($skulist as $l){
    	    $skus[] = base64_decode($l[0]);
    	}
    	//变参商品对应的变参子产品
    	$realArr = array();
    	$relationship = ProductConfigRelationship::find()->where(['cfsku'=>$skus])->asArray()->all();
    	foreach($relationship as $r){
    		$realArr[$r['cfsku']][] = $r['assku'];
    		$asskus[] = $r['assku'];
    	}
    	//变参子产品对应的产品信息
    	$proArr = array();
    	$pro = Product::find()->select(['sku', 'name'])->where(['sku'=>$asskus])->asArray()->all();
    	foreach ($pro as $p){
    	    $proArr[$p['sku']] = $p;
    	}
    	//把变参商品转换为变参子产品
    	$codeInfo = array();
    	foreach($skulist as $l){
    	    $sku = base64_decode($l[0]);
    		if(!empty($realArr[$sku])){
    		    foreach ($realArr[$sku] as $r){
    		        if(!empty($proArr[$r])){
    		            $codeInfo[] = ['0' => $proArr[$r]['sku'], '1' => $proArr[$r]['name']];
    		        }
    		    }
    		}
    		else{
    		    $codeInfo[] = ['0' => $sku, '1' => $l[1]];
    		}
    	}
    	
    	return $this->renderPartial('_skubarcode',[
    			'skulist'=>$codeInfo,
    			'config'=>$config,
    			]);
    }
    
    /**
     +----------------------------------------------------------
     * 插入导出Excel队列
     +----------------------------------------------------------
     * @param	
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2016/12/14		初始化
     +----------------------------------------------------------
     **/
    public function actionExportExcel(){
        $type = empty($_POST['type']) ? '' : $_POST['type'];
        $str = empty($_POST['str']) ? '' : $_POST['str'];
        
        //$rtn = ProductHelper::ExportProductExcel($product_id_list);
        
        $product_ids = array();
        if($type == 0){
        	$product_id_list = rtrim($str, ',');
        	$product_ids = json_encode((object)explode(',', $product_id_list));
        }
        else{
            $product_ids = $str;
        }
        
        $className = addslashes('\eagle\modules\catalog\helpers\ProductHelper');
        $functionName = 'ExportProductExcel';
        
        $rtn = ExcelHelper::insertExportCrol($className, $functionName, $product_ids, 'export_product_list');
        
        if($rtn['success'] == 1){
            return $this->renderPartial('down_load_excel',[
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
     * @author		lrq		  2016/12/14		初始化
     +----------------------------------------------------------
     **/
    public function actionGetExcelUrl(){
    	$pending_id = empty($_POST['pending_id']) ? '0' : $_POST['pending_id'];
    	
    	return ExcelHelper::getExcelUrl($pending_id);
    }
    
    /**
     +----------------------------------------------------------
     * 前端直接导出Excel
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2016/12/22		初始化
     +----------------------------------------------------------
     **/
    public function actionStraightExportExcel(){
    	AppTrackerApiHelper::actionLog("catalog", "/catalog/product/straight-export-excel");
    	$product_ids = empty($_REQUEST['product_ids'])?'':trim($_REQUEST['product_ids']);
    	$type = empty($_REQUEST['type'])?0:trim($_REQUEST['type']);
    	
    	if($type == 1){
    	    $product_ids = $product_ids == base64_encode(base64_decode($product_ids)) ? base64_decode($product_ids) : $product_ids;
    	    $product_list = json_decode($product_ids);
    	}
    	else{
    	    $product_ids = rtrim($product_ids, ',');
    	    $product_list = explode(',', $product_ids);
    	}
    	
    	ProductHelper::ExportProductExcel($product_list, true);
    }
    
    /**
     +----------------------------------------------------------
     * AJAX 检测平台、店铺、别名组合是否已存在
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		2017/04/14		初始化
     +----------------------------------------------------------
     **/
    public function actionCheckMatchExisting(){
    	$ret['success'] = false;
    	$ret['matchExisting'] = array();
    	$aliaslist = empty($_GET['aliaslist']) ? '' : $_GET['aliaslist'];
    	$sku = empty($_GET['sku']) ? '' : $_GET['sku'];
    	
    	if($aliaslist != ''){
	    	$alias = explode('@@&@@', $aliaslist);
	    	foreach ($alias as $one){
	    		$alias_sku = '';
	    		$platform = '';
	    		$selleruserid = '';
	    		$shopname = '';
	    		$arr = explode('@@#@@', $one);
	    		for($n = 0; $n < count($arr); $n++){
	    			if($n == 0)
	    				$alias_sku = trim($arr[0]);
	    			else if($n == 1)
	    				$platform = trim($arr[1]);
	    			else if($n == 2)
	    				$selleruserid = trim($arr[2]);
	    			else if($n == 3)
	    				$shopname = trim($arr[3]);
	    		}
	    		
	    		$models = ProductAliases::find()->where("sku!='".$sku."'")->andwhere(['alias_sku'=>$alias_sku, 'platform'=>$platform, 'selleruserid'=>$selleruserid])->asarray()->one();
	    		if(!empty($models)){
	    			$ret['matchExisting'][] = [
	    				'sku' => $models['sku'],
	    				'alias_sku' => $alias_sku, 
	    				'platform' => $platform, 
	    				'shopname' => $shopname == '所有店铺' ? '' : $shopname,
	    			];
	    		}
	    	}
	    	if(!empty($ret['matchExisting'])){
	    		$ret['success'] = true;
	    	}
	    	
	    	exit ( json_encode($ret) );
    	}
    }
    
    //打开合并商品窗口
    public function actionShowMergeProductBox(){
    	return $this->renderPartial('showmergeproductbox');
    }
    
    //合并商品
    public function actionMergeProduct(){
        if(empty($_POST['merge_sku']) || empty($_POST['be_sku_list'])){
            return json_encode(['success' => false, 'msg' => '参数缺省！']);
        }
        $merge_sku = trim($_POST['merge_sku']);
        $be_sku_arr = explode('@#@', rtrim($_POST['be_sku_list'], '@#@'));
        
        $ret = ProductHelper::MergeProduct($merge_sku, $be_sku_arr);
        return json_encode($ret);
    }
    
    //检测SKU是否可可并
    public function actionCheckBeMergeSku(){
    	if(empty($_POST['sku']) ){
    		return json_encode(['success' => false, 'msg' => '被合并SKU不能为空！']);
    	}
    	
    	$ret['success'] = true;
    	$ret['msg'] = '';
    	
    	$sku = trim($_POST['sku']);
         //判断是否属于商品库
        $model = Product::find()->where(['sku' => $sku])->andWhere("type='S'||type='L'")->one();
        if(empty($model)){
        	$ret['success'] = false;
        	$ret['msg'] .= "被合并SKU：<span style='color: red;'>$sku </span>不属于商品库的普通商品或者变参子产品<br>";
        }
    
    	return json_encode($ret);
    }
    
    public function actionText2(){
    	$alias_sku = empty($_REQUEST['alias_sku']) ? '' :$_REQUEST['alias_sku'];
    	$platform = empty($_REQUEST['platform']) ? '' :$_REQUEST['platform'];
    	$selleruserid = empty($_REQUEST['selleruserid']) ? '' :$_REQUEST['selleruserid'];
    	
    	$root_sku = ProductHelper::getRootSkuByAlias($alias_sku, $platform, $selleruserid);
    	print_r($root_sku);
    }
    
    public function actionProductClassifica(){
    	//获取商品分类
    	$html=ProductHelper::GetProductClass(1);
    	 
    	return $this->render('product-classification',['html'=>$html]);
    }
    
    public function actionAddClassifica(){
        $node_number = empty($_REQUEST['node_number']) ? '' : $_REQUEST['node_number'];
        $ret = ProductHelper::AddClassifica($node_number);
        return json_encode($ret);
    }
    
    public function actionEditClassifica(){
    	if(empty($_REQUEST['node_id'])){
    		return json_encode(['success' => false, 'msg' => '类别信息缺失！']);
    	}
    	$name = empty($_REQUEST['name']) ? '' : trim($_REQUEST['name']);
    	if(empty($name)){
    		return json_encode(['success' => false, 'msg' => '类别名称不能为空！']);
    	}
    	
    	$ret = ProductHelper::EditClassifica($_REQUEST['node_id'], $name);
    	return json_encode($ret);
    }
    
    public function actionDeleteClassifica(){
    	if(empty($_REQUEST['node_id'])){
    		return json_encode(['success' => false, 'msg' => '类别信息缺失！']);
    	}
    	 
    	$ret = ProductHelper::DeleteClassifica($_REQUEST['node_id']);
    	return json_encode($ret);
    }
    
    public function actionChangeClassifica(){
    	if(!isset($_POST['class_id']) || $_POST['class_id'] == ''){
    		return json_encode(['success' => false, 'msg' => '类别信息缺失！']);
    	}
    	if(empty($_POST['skulist'])){
    		return json_encode(['success' => false, 'msg' => '不存在需要移入的订单！']);
    	}
    
    	$ret = ProductHelper::ChangeClassifica($_REQUEST['class_id'], $_POST['skulist']);
    	return json_encode($ret);
    }
    
    //打开批量编辑商品界面
    public function actionShowBathEditDialog(){
    	$edit_type = empty($_GET['edit_type']) ? 'basic' : $_GET['edit_type'];
    	$product_id_list = empty($_GET['product_id_list']) ? array() : $_GET['product_id_list'];
    	$data = ProductHelper::GetBathEditInfo($edit_type, $product_id_list);
    	
    	//平台
    	$platforms = [];
    	$ret_plat = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
    	if(!empty($ret_plat)){
    		foreach ($ret_plat as $plat => $shop){
    			if(!empty($shop)){
	    		    if(!in_array($plat, ['wish', 'ebay', 'cdiscount'])){
	    		        $platforms[] = $plat;
	    		    }
    			}
    		}
    	}
    	 
    	return $this->renderPartial('_bath_edit',[
    			'edit_info' => $data['edit_info'],
    			'edit_col_name' => $data['edit_col_name'],
    			'edit_type' => $edit_type,
    			'spec_edit_dialog' => ['declaration_ch', 'declaration_en', 'name', 'prod_name_ch', 'prod_name_en'],
    	        'platforms' => $platforms,
    		]);
    }
    
    //保存批量编辑的商品
    public function actionSaveBathEdit(){
    	$ret = ProductHelper::SaveBathEdit($_POST);
    	
    	return json_encode($ret);
    }
    
    //刷新统计数
    public function actionRefreshProductClassCount(){
    	ProductHelper::getProductClassCount(true);
    }
}



