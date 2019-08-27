<?php

namespace eagle\modules\report\controllers;

use eagle\modules\report\helpers\ReportHelper;
use eagle\modules\util\helpers\ExcelHelper;
use yii\data\Sort;
use eagle\widgets\ESort;

class ProductController extends \yii\web\Controller
{
	//需要用到POST方法，所以将CSRF设置为FALSE
	public $enableCsrfValidation = FALSE;
	
	public function behaviors() {
		return [
		'access' => [
		'class' => \yii\filters\AccessControl::className(),
		'rules' => [
		[
		'allow' => true,
		'roles' => ['@'],
		],
		],
		],
		'verbs' => [
		'class' => \yii\filters\VerbFilter::className(),
		'actions' => [
		'delete' => ['post'],
		],
		],
		];
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计  标签统计 view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/29				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
    public function actionProductTag(){
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'tag_id';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'asc';
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	
    	
    	if(!empty($_REQUEST['start'])){
    		$_POST['start'] = $_REQUEST['start'];
    	}
    	 
    	if(!empty($_REQUEST['end'])){
    		$_POST['end'] = $_REQUEST['end'];
    	}
    	
    	if(!empty($_REQUEST['source'])){
    		$_POST['source'] = $_REQUEST['source'];
    	}
    	
    	if(!empty($_REQUEST['site'])){
    		$_POST['site'] = $_REQUEST['site'];
    	}
    	
    	$start = !empty($_POST['start']) ? $_POST['start'] : $strDate.' 00:00:00';
    	$end = !empty($_POST['end']) ? $_POST['end'] : $strDate.' 23:59:59';
    	
    	$source = !empty($_POST['source']) ? $_POST['source'] : false;
    	$site = !empty($_POST['site']) ? $_POST['site'] : false;
    	
    	$tagSaleData = ReportHelper::getTagSaleData($start, $end, $source, $site, $sort, $order, 20);
    	
    	//获取店名数据
    	$shopArr = ReportHelper::getShop();
    	
//         return $this->render('_tag',['shopArr' => $shopArr,'tagSaleData' => $tagSaleData]);
        
    	return $this->render('../inventory/pageChange',['shopArr' => $shopArr,'tagSaleData' => $tagSaleData]);
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  标签统计 显示明细数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/30				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionGetTagSaleDetail(){
    	$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'order_id';
    	$order = !empty($_GET['order']) ? $_GET['order'] : 'asc';
    	
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	
    	$start = !empty($_GET['start']) ? $_GET['start'] : $strDate.' 00:00:00';
    	$end = !empty($_GET['end']) ? $_GET['end'] : $strDate.' 23:59:59';
    	$source = !empty($_GET['source']) ? $_GET['source'] : false;
    	$site = !empty($_GET['site']) ? $_GET['site'] : false;
    
    	$tag_id = $_GET['tag_id'];
    	$tag_name = $_GET['tag_name'];
    	
    	$tagSaleDetailData = ReportHelper::getTagSaleDetail($tag_id, $source, $site, $start, $end, $sort, $order);
    	
    	$tagSaleDetailData['tag_id'] = $tag_id;
    	$tagSaleDetailData['tag_name'] = $tag_name;
    	
    	$tagSaleDetailData['start'] = $start;
    	$tagSaleDetailData['end'] = $end;
    	$tagSaleDetailData['source'] = $source;
    	$tagSaleDetailData['site'] = $site;
    	
    	$tagSaleDetailData['isTagIDs'] = "NO";
    	
    	return $this->renderAjax("tagDetail",["tagSaleDetailData"=>$tagSaleDetailData]);
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  标签统计 导出Excel
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/30				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionExportTagExcel(){
    	$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'tag_id';
    	$order = !empty($_GET['order']) ? $_GET['order'] : 'asc';
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	 
    	$start = !empty($_GET['start']) ? $_GET['start'] : $strDate.' 00:00:00';
    	$end = !empty($_GET['end']) ? $_GET['end'] : $strDate.' 23:59:59';
    	 
    	$source = !empty($_GET['source']) ? $_GET['source'] : false;
    	$site = !empty($_GET['site']) ? $_GET['site'] : false;
    	 
    	$tagSaleData = ReportHelper::getTagSaleData($start, $end, $source, $site, $sort, $order, 10000);
    	
    	$field_label_list = [
    	"id" => "序号",
    	"tag_name" => "标签",
    	"skuSoldCount" => "有销售SKU数量",
    	"volume" => "销售商品数量",
    	"brand" => "有销售品牌数量",
    	"price"=>"销售总金额",
    	];
    	
    	// 导出 excel 的 header
    	$data_array [] = $field_label_list;
    	foreach($tagSaleData['data'] as $oneTag):
    	//$field_label_list 为需要导出的field  , array_flip后得出需要导出的field name
    	foreach(array_flip($field_label_list) as $field_name){
    		//循环所有需要导出的field 并将值 放入临时变量 row 中
    		$row[$field_name] = $oneTag[$field_name];
    		//test if it is numeric, add " " in front of it, in case excel 科学计数法
    		if (is_numeric($row[$field_name]) and strlen($row[$field_name]) > 8 )
    			$row[$field_name] = ' '.$row[$field_name];
    	}
    	$data_array [] = $row;
    	endforeach;
    	ExcelHelper::exportToExcel($data_array);
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  多标签统计 view层显示
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/30				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionProductTags(){
    	//获取店名数据
    	$shopArr = ReportHelper::getShop();
    	 
//     	return $this->render('_tags',['shopArr' => $shopArr]);
    	
    	return $this->render('../inventory/pageChange',['shopArr' => $shopArr]);
    }
    
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  多标签统计 根据查询条件返回json格式数据回界面
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/30				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionGetTagsSalesData() {
//     	$result = ReportHelper::getTagsInventory($_POST['tagIds']);
//     	exit(json_encode($result));
    	
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	$start = !empty($_POST['start']) ? $_POST['start'] : $strDate.' 00:00:00';
    	$end = !empty($_POST['end']) ? $_POST['end'] : $strDate.' 23:59:59';
    	
    	$source = !empty($_POST['source']) ? $_POST['source'] : false;
    	$site = !empty($_POST['site']) ? $_POST['site'] : false;
    	$tagNames = !empty($_POST['tagNames']) ? $_POST['tagNames'] : '';
    	
    	$result = ReportHelper::getTagsSalesData($_POST['tagIds'], strtotime($start), strtotime($end), $source, $site, $tagNames);
    	
    	exit(json_encode($result));
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  多标签统计 显示明细数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/30				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionTagsSaleDetail(){
    	$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'order_id';
    	$order = !empty($_GET['order']) ? $_GET['order'] : 'asc';
    	
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	$start = !empty($_GET['start']) ? $_GET['start'] : $strDate.' 00:00:00';
    	$end = !empty($_GET['end']) ? $_GET['end'] : $strDate.' 23:59:59';
    	$source = !empty($_GET['source']) ? $_GET['source'] : false;
    	$site = !empty($_GET['site']) ? $_GET['site'] : false;
    	
    	$tag_id = $_GET['tag_id'];
    	$tag_name = $_GET['tag_name'];
    	
    	$tagSaleDetailData = ReportHelper::getTagsSaleDetail($tag_id, $source, $site, $start, $end, $sort, $order);
    	
    	$tagSaleDetailData['tag_id'] = $tag_id;
    	$tagSaleDetailData['tag_name'] = $tag_name;
    	
    	$tagSaleDetailData['start'] = $start;
    	$tagSaleDetailData['end'] = $end;
    	$tagSaleDetailData['source'] = $source;
    	$tagSaleDetailData['site'] = $site;
    	
    	$tagSaleDetailData['isTagIDs'] = "YES";
    	
    	return $this->renderAjax("tagDetail",["tagSaleDetailData"=>$tagSaleDetailData]);
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  多标签统计 导出Excel
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/30				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionExportTagsExcel() {
    	if(empty($_POST['hid-TableTagArray'])){
    		exit;
    	}
    	 
    	$hidPostTag = json_decode($_POST['hid-TableTagArray'], true);
    	 
    	if(count($hidPostTag) == 0){
    		exit;
    	}
    	 
    	$tagTableArr = array();
    	 
    	foreach($hidPostTag as $TableTagKey => $TableTags){
    		if ($TableTagKey == 0) continue;
    	
    		$tmpKey = count($tagTableArr);
    	
    		$tagTableArr[$tmpKey]['id'] = $TableTags[0];
    		$tagTableArr[$tmpKey]['name'] = $TableTags[1];
    		$tagTableArr[$tmpKey]['skuSoldCount'] = $TableTags[2];
    		$tagTableArr[$tmpKey]['volume'] = $TableTags[3];
    		$tagTableArr[$tmpKey]['brands'] = $TableTags[4];
    		$tagTableArr[$tmpKey]['prices'] = $TableTags[5];
    	}
    	
    	$field_label_list = [
    	"id" => "序号",
    	"name" => "标签",
    	"skuSoldCount" => "有销售SKU数量",
    	"volume" => "销售商品数量",
    	"brands" => "品牌数量",
    	"prices"=>"销售总金额",
    	];
    	
    	// 导出 excel 的 header
    	$data_array [] = $field_label_list;
    	foreach($tagTableArr as $oneTag):
    	//$field_label_list 为需要导出的field  , array_flip后得出需要导出的field name
    	foreach(array_flip($field_label_list) as $field_name){
    		//循环所有需要导出的field 并将值 放入临时变量 row 中
    		$row[$field_name] = $oneTag[$field_name];
    		//test if it is numeric, add " " in front of it, in case excel 科学计数法
    		if (is_numeric($row[$field_name]) and strlen($row[$field_name]) > 8 )
    			$row[$field_name] = ' '.$row[$field_name];
    	}
    	$data_array [] = $row;
    	endforeach;
    	 
    	$result=ExcelHelper::exportToExcel($data_array);
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  品牌统计 view层显示
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/30				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionProductBrand() {
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'brand_id';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'asc';
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	
    	if(!empty($_REQUEST['start'])){
    		$_POST['start'] = $_REQUEST['start'];
    	}
    	 
    	if(!empty($_REQUEST['end'])){
    		$_POST['end'] = $_REQUEST['end'];
    	}
    	
    	if(!empty($_REQUEST['source'])){
    		$_POST['source'] = $_REQUEST['source'];
    	}
    	
    	if(!empty($_REQUEST['site'])){
    		$_POST['site'] = $_REQUEST['site'];
    	}
    	
    	$start = !empty($_POST['start']) ? $_POST['start'] : $strDate.' 00:00:00';
    	$end = !empty($_POST['end']) ? $_POST['end'] : $strDate.' 23:59:59';
    
    	$source = !empty($_POST['source']) ? $_POST['source'] : false;
    	$site = !empty($_POST['site']) ? $_POST['site'] : false;
    	
    	//获取店名数据
    	$shopArr = ReportHelper::getShop();
    	
    	$brandsDataArr = ReportHelper::getBrandsSalesData(strtotime($start), strtotime($end), $source, $site, $sort, $order, 20);

//     	return $this->render('_brand',['shopArr' => $shopArr,'brandsDataArr' => $brandsDataArr]);
    	
    	return $this->render('../inventory/pageChange',['shopArr' => $shopArr,'brandsDataArr' => $brandsDataArr]);
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  品牌统计 显示明细
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/05/04				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionGetBrandSaleDetail() {
    	$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'order_id';
    	$order = !empty($_GET['order']) ? $_GET['order'] : 'asc';
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	$start = !empty($_GET['start']) ? $_GET['start'] : $strDate.' 00:00:00';
    	$end = !empty($_GET['end']) ? $_GET['end'] : $strDate.' 23:59:59';
    	$source = !empty($_GET['source']) ? $_GET['source'] : false;
    	$site = !empty($_GET['site']) ? $_GET['site'] : false;
    	
    	$brand_id = $_GET['brand_id'];
    	
    	$brandSaleDetailData = ReportHelper::getBrandSaleDetail($brand_id, $source, $site, $start, $end, $sort, $order);
    	
    	$brandSaleDetailData['brand_id'] = $brand_id;
    	$brandSaleDetailData['brand_name'] = $_GET['brand_name'];
    	
    	$brandSaleDetailData['start'] = $start;
    	$brandSaleDetailData['end'] = $end;
    	$brandSaleDetailData['source'] = $source;
    	$brandSaleDetailData['site'] = $site;
    	
    	return $this->renderAjax("brandDetail",["brandSaleDetailData"=>$brandSaleDetailData]);
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  品牌统计 导出Excel
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/05/04				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionExportBrandExcel() {
    	$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'brand_id';
    	$order = !empty($_GET['order']) ? $_GET['order'] : 'asc';
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	
    	$start = !empty($_GET['start']) ? $_GET['start'] : $strDate.' 00:00:00';
    	$end = !empty($_GET['end']) ? $_GET['end'] : $strDate.' 23:59:59';
    	
    	$source = !empty($_GET['source']) ? $_GET['source'] : false;
    	$site = !empty($_GET['site']) ? $_GET['site'] : false;
    	
    	$brandSaleData = ReportHelper::getBrandsSalesData(strtotime($start), strtotime($end), $source, $site, $sort, $order, 10000);
    	
    	 
    	$field_label_list = [
    	"id" => "序号",
    	"name" => "品牌",
    	"skuSoldCount" => "有销售SKU数量",
    	"volume" => "销售商品数量",
    	"prices" => "销售总金额",
    	];
    	 
    	// 导出 excel 的 header
    	$data_array [] = $field_label_list;
    	foreach($brandSaleData['data'] as $oneTag):
    	//$field_label_list 为需要导出的field  , array_flip后得出需要导出的field name
    	foreach(array_flip($field_label_list) as $field_name){
    		//循环所有需要导出的field 并将值 放入临时变量 row 中
    		$row[$field_name] = $oneTag[$field_name];
    		//test if it is numeric, add " " in front of it, in case excel 科学计数法
    		if (is_numeric($row[$field_name]) and strlen($row[$field_name]) > 8 )
    			$row[$field_name] = ' '.$row[$field_name];
    	}
    	$data_array [] = $row;
    	endforeach;
    	ExcelHelper::exportToExcel($data_array);
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  商品数量及价值 view层显示
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/05/04				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionProductWorth() {
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'sku';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'asc';
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	
    	if(!empty($_REQUEST['start'])){
    		$_POST['start'] = $_REQUEST['start'];
    	}
    	
    	if(!empty($_REQUEST['end'])){
    		$_POST['end'] = $_REQUEST['end'];
    	}
    	 
    	if(!empty($_REQUEST['source'])){
    		$_POST['source'] = $_REQUEST['source'];
    	}
    	 
    	if(!empty($_REQUEST['site'])){
    		$_POST['site'] = $_REQUEST['site'];
    	}
    	
    	
    	$start = !empty($_POST['start']) ? $_POST['start'] : $strDate.' 00:00:00';
    	$end = !empty($_POST['end']) ? $_POST['end'] : $strDate.' 23:59:59';
    
    	$source = !empty($_POST['source']) ? $_POST['source'] : false;
    	$site = !empty($_POST['site']) ? $_POST['site'] : false;
    
    	$worthDataArr = ReportHelper::getProductSalesData(strtotime($start), strtotime($end), $source, $site, $sort, $order);
    	
    	//获取店名数据
    	$shopArr = ReportHelper::getShop();
    	
//     	return $this->render('_worth',['shopArr' => $shopArr,'worthDataArr' => $worthDataArr]);
    	
    	return $this->render('../inventory/pageChange',['shopArr' => $shopArr,'worthDataArr' => $worthDataArr]);
    }
    
    /**
     +----------------------------------------------------------
     * 销售商品统计  商品数量及价值 导出Excel
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/05/04				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionExportWorthExcel() {
    	$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'sku';
    	$order = !empty($_GET['order']) ? $_GET['order'] : 'asc';
    	$strDate = date('Y-m-d', strtotime('-1 day'));
    	 
    	$start = !empty($_GET['start']) ? $_GET['start'] : $strDate.' 00:00:00';
    	$end = !empty($_GET['end']) ? $_GET['end'] : $strDate.' 23:59:59';
    	 
    	$source = !empty($_GET['source']) ? $_GET['source'] : false;
    	$site = !empty($_GET['site']) ? $_GET['site'] : false;
    	 
    	$worthDataArr = ReportHelper::getProductSalesData(strtotime($start), strtotime($end), $source, $site, $sort, $order, 10000);
    	
    	
    	$field_label_list = [
    	"id" => "排名",
    	"sku" => "SKU",
    	"product_name" => "商品名称",
    	"sale" => "销售商品数量",
    	"price" => "销售总金额",
    	"ordered" => "订单数量(销售次数)",
    	];
    	
    	$index = $worthDataArr['pagination']->offset + 1;
    	
    	// 导出 excel 的 header
    	$data_array [] = $field_label_list;
    	foreach($worthDataArr['data'] as $oneTag):
    	//$field_label_list 为需要导出的field  , array_flip后得出需要导出的field name
    	foreach(array_flip($field_label_list) as $field_name){
    		if ($field_name=="id"){
    			$row['id'] = $index;
    			continue;
    		}
    		
    		//循环所有需要导出的field 并将值 放入临时变量 row 中
    		$row[$field_name] = $oneTag[$field_name];
    		//test if it is numeric, add " " in front of it, in case excel 科学计数法
    		if (is_numeric($row[$field_name]) and strlen($row[$field_name]) > 8 )
    			$row[$field_name] = ' '.$row[$field_name];
    	}
    	
    	$data_array [] = $row;
    	$index++;
    	endforeach;
    	ExcelHelper::exportToExcel($data_array);
    }

}
