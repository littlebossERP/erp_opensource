<?php

namespace eagle\modules\report\controllers;

use eagle\modules\report\helpers\ReportHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\inventory\models\Warehouse;
use yii\data\Sort;
use eagle\widgets\ESort;

class InventoryController extends \yii\web\Controller
{
	//非网页访问方式跳过通过csrf验证的 . 如: curl 和 post man
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
	 * 报表统计 菜单入口
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/28				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public function actionIndex(){
		$sort=" t.tag_id ";
		$order="desc";
		 
		$allTagData = ReportHelper::getTagInventory($sort, $order, 20);
		
		return $this->render('index',['allTagData'=>$allTagData]);
		
// 		return $this->render('pageChange',['allTagData'=>$allTagData]);
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 库存统计 标签统计view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/23				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
    public function actionTag()
    {
    	$sort=" t.tag_id ";
    	$order="desc";
    	
    	$allTagData = ReportHelper::getTagInventory($sort, $order, 20);

//         return $this->render('tag',['allTagData'=>$allTagData]);
        
        return $this->render('pageChange',['allTagData'=>$allTagData]);
    }
    
    /**
     +----------------------------------------------------------
     * 库存统计 标签统计 显示明细数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/23				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionGetTagDetail(){
    	$sort="stock";
    	$order="desc";
    	
    	$tag_id = empty($_GET['tag_id']) ? 0 : $_GET['tag_id'];
    	$tag_name = empty($_GET['tag_name']) ? "" : $_GET['tag_name'];
    	
    	$tagDetailData = ReportHelper::getTagInventoryDetail($tag_id,$sort,$order);
    	
    	$tagDetailData['tag_id'] = $tag_id;
    	$tagDetailData['tag_name'] = $tag_name;
    	
    	return $this->renderAjax("tagDetail",["tagDetailData"=>$tagDetailData]);
    }
    
    /**
     +----------------------------------------------------------
     * Tag 查询记录页面的导出excel
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/23				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionExportTagExcel(){
    	$sort=" t.tag_id ";
    	$order="desc";
    	
    	$allTagData = ReportHelper::getTagInventory($sort, $order, 10000);
    	
    	$field_label_list = [
			"id" => "序号",
			"tag_name" => "标签",
			"sku" => "有库存SKU数量",
			"stock" => "商品数量",
			"brands" => "有库存品牌数量",
			"stock_value"=>"商品价值",
			];
    	
    	// 导出 excel 的 header
    	$data_array [] = $field_label_list;
    	foreach($allTagData['data'] as $oneTag):
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
     * 库存统计 多标签统计view层显示
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/23				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionTags(){
    	
//     	return $this->render('tags');
    	
    	
    	return $this->render('pageChange');
    }
    
    /**
     +----------------------------------------------------------
     * 库存统计 多标签统计 添加统计对象窗体
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/23				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionTagsAddQueryWin(){
    	$listTagArr = ReportHelper::listTagData();
    	
    	return $this->renderAjax("listTagData",["listTagArr"=>$listTagArr]);
    }
    
    /**
     +----------------------------------------------------------
     * 库存统计 多标签统计 添加对象返回标签组合数据JSON
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/24				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionGetTagsInventoryData() {
    	$result = ReportHelper::getTagsInventory($_POST['tagIds']);
    	
    	exit(json_encode($result));
    }
    
    /**
     +----------------------------------------------------------
     * 库存统计 多标签统计 显示组合标签的明细数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/24				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionTagsInventoryDetail() {
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'stock';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'desc';
    
    	$tagIDs = !empty($_GET['tag_id']) ? $_GET['tag_id'] : '';
    	$tag_name = empty($_GET['tag_name']) ? "" : $_GET['tag_name'];
    	
     	$tagDetailData = ReportHelper::getTagsInventoryDetail($tagIDs, $sort, $order);
    	
     	$tagDetailData['tag_id'] = $tagIDs;
     	$tagDetailData['tag_name'] = $tag_name;
     	
     	return $this->renderAjax("tagDetail",["tagDetailData"=>$tagDetailData]);
    }
    
    /**
     +----------------------------------------------------------
     * Tags 查询记录页面的导出excel
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/24				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionExportTagsExcel(){
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
    		$tagTableArr[$tmpKey]['tag_name'] = $TableTags[1];
    		$tagTableArr[$tmpKey]['sku'] = $TableTags[2];
    		$tagTableArr[$tmpKey]['stock'] = $TableTags[3];
    		$tagTableArr[$tmpKey]['brands'] = $TableTags[4];
    		$tagTableArr[$tmpKey]['stock_value'] = $TableTags[5];
    	}
    	 
    	$field_label_list = [
    	"id" => "序号",
    	"tag_name" => "标签组合",
    	"sku" => "有库存SKU数量",
    	"stock" => "商品数量",
    	"brands" => "品牌数量",
    	"stock_value"=>"商品价值",
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
     * 库存统计 品牌统计view层显示
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/24				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionGetBrandsData() {
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'brand_id';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'asc';
    	
    	$brandsDataArr = ReportHelper::getBrandsInventory($sort, $order, 20);
    	
//     	return $this->render('brand',['brandsData'=>$brandsDataArr]);
    	
    	
    	return $this->render('pageChange',['brandsData'=>$brandsDataArr]);
    }
    
    /**
     +----------------------------------------------------------
     * 库存统计 品牌统计  显示品牌的明细数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/27				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionGetBrandDetail() {
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'stock';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'desc';
    
    	$brand_id = !empty($_GET['brand_id']) ? $_GET['brand_id'] : 0;
    	
    	$brandDetailData = ReportHelper::getBrandInventoryDetail($brand_id, $sort, $order);
    	
    	$brandDetailData['brand_id'] = empty($_GET['brand_id']) ? '0' : $_GET['brand_id'];
    	$brandDetailData['brand_name'] = empty($_GET['brand_name']) ? '' : $_GET['brand_name'];
    	
    	return $this->renderAjax("brandDetail",["brandDetailData"=>$brandDetailData]);
    }
    
    /**
     +----------------------------------------------------------
     * 库存统计 品牌统计  导出Excel
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/27				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionExportBrandExcel() {
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'brand_id';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'asc';
    	 
    	$brandsData = ReportHelper::getBrandsInventory($sort, $order, 10000);
    	 
    	$field_label_list = [
    	"id" => "序号",
    	"name" => "品牌",
    	"sku" => "有库存SKU数量",
    	"stock" => "商品数量",
    	"stock_value" => "商品价值",
    	];
    	 
    	// 导出 excel 的 header
    	$data_array [] = $field_label_list;
    	foreach($brandsData['data'] as $oneTag):
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
     * 库存统计 商品数量及价值 view层显示
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/27				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionWorth(){
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'stock';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'desc';
    		
    	if (empty($_POST['whID']))
    		$_POST['whID'] = 0;
    	
//     	var_dump($_REQUEST);
    	
    	if(!empty($_REQUEST['wh_id'])){
    		$_POST['whID'] = $_REQUEST['wh_id'];
    	}
    	
    	$productInventory = ReportHelper::getProductInventory($_POST['whID'], $sort, $order, 20);
    	
    	//获取仓库名称
    	$warehouseArr = Warehouse::find()->asArray()->all();
    	
//     	return $this->render('worth',['warehouseArr'=>$warehouseArr,'productInventory'=>$productInventory]);
    	
    	return $this->render('pageChange',['warehouseArr'=>$warehouseArr,'productInventory'=>$productInventory]);
    }
    
    /**
     +----------------------------------------------------------
     * 库存统计 商品数量及价值  导出Excel
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2015/04/27				初始化
     +----------------------------------------------------------
     * @Param
     **/
    public function actionExportWorthExcel(){
    	$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'stock';
    	$order = !empty($_POST['order']) ? $_POST['order'] : 'desc';
    	
    	if (empty($_POST['whID']))
    		$_POST['whID'] = 0;
    	 
    	$productInventory = ReportHelper::getProductInventory($_POST['whID'], $sort, $order, 10000);
    	
    	$field_label_list = [
    	"index" => "排名",
    	"sku" => "SKU",
    	"name" => "商品名称",
    	"stock" => "商品数量",
    	"prices" => "商品价值",
    	"wh_name" => "仓库",
    	];
    	
    	// 导出 excel 的 header
    	$data_array [] = $field_label_list;
    	foreach($productInventory['data'] as $oneTag):
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

}
