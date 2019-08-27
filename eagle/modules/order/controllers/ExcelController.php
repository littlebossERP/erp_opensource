<?php
namespace eagle\modules\order\controllers;

use eagle\modules\order\models\Excelmodel;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\OdOrderItem;
use yii\data\Pagination;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\models\catalog\Product;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\util\helpers\StandardConst;
use common\helpers\Helper_Array;
use eagle\modules\tracking\models\Tracking;
use Qiniu\json_decode;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\catalog\helpers\ProductHelper;


class ExcelController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	
	/**
	 +------------------------------------------------------------------------------
	 * 导出excle
	 +------------------------------------------------------------------------------
	 * @items			传入数据（根据传入的id查找数据库）
	 * @tips			传入字段
	 * @name  			文件名
	 
	 +------------------------------------------------------------------------------
	 */
	public function actionExportExcel()
	{
		
		$excelmodelid	=	$_GET['excelmodelid'];//excel模型id
		$idsarr = explode(',',$_GET['orderids']);
		$idsarr = array_filter($idsarr);
		$orderids = implode(',',$idsarr);

		$excelmodel	=	new Excelmodel();
		$excel	=	$excelmodel->findOne($excelmodelid);
		
		$orderids_arr	=	explode(',',$orderids);
		$items_arr	=explode(',',$excel['content']);//excel模型保存的字段
// 		foreach ($orderids_arr as $od){
// 			//查找商品item
// 			$items=OdOrderItem::find()->where(['=','order_id',$od])->all();
// 		}
		$res = array();
		foreach ($orderids_arr as $order_id){
			$order_obj = OdOrder::find()->where(['order_id'=>$order_id])->one();
			if (count($order_obj->items>1)){
				//订单 item 数量 大干1时
				$i = 1;
				foreach ($order_obj->items as $item_obj){
					//@todo  CD跳过NonDeliverySku的导出，如果以后其他功能导出需要，要相应修改
					if($order_obj->order_source=='cdiscount' && in_array($item_obj['sku'],CdiscountOrderInterface::getNonDeliverySku())){
						$i++;
						continue;
					}
					
					$tmp_arr = array();
					if ($i==1){
						$hasProduct = strlen($item_obj['sku']) && ProductApiHelper::hasProduct($item_obj['sku']);
						foreach ($items_arr as $column){
							//如果有设置导出商品中文名的，进行商品信息的获取
							if ($column == 'name_cn'){
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['name_cn'] = $product['prod_name_ch'];
								}else{
									$tmp_arr['name_cn'] = '';
								}
							}else if($column == 'root_sku'){
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['root_sku'] = ProductApiHelper::getRootSKUByAlias($item_obj['sku']);
								}else{
									$tmp_arr['root_sku'] = '';
								}
							}elseif($column == 'declaration_ch'){
								new Product();
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['declaration_ch'] = $product['declaration_ch'];
								}else{
									$tmp_arr['declaration_ch'] = '';
								}
							}elseif($column == 'declaration_en'){
								new Product();
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['declaration_en'] = $product['declaration_en'];
								}else{
									$tmp_arr['declaration_en'] = '';
								}
							}elseif($column == 'declaration_value'){
								new Product();
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['declaration_value'] = $product['declaration_value'];
								}else{
									$tmp_arr['declaration_value'] = '';
								}
							}elseif($column == 'declaration_value_currency'){
								new Product();
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['declaration_value_currency'] = $product['declaration_value_currency'];
								}else{
									$tmp_arr['declaration_value_currency'] = '';
								}
							}elseif($column == 'order_manual_id'){
								//不做data conversion 的情况下 沿用 之前的order_manual_id 这个 字段
								$tmp_arr[$column]=OrderTagHelper::getAllTagStrByOrderId($order_id);
							}elseif($column == 'photo_url'){
								$tmp_arr[$column]= isset($item_obj['photo_primary'])?$item_obj['photo_primary']:'';
							}
							else{
								$tmp_arr[$column]= isset($order_obj[$column])?$order_obj[$column]:(isset($item_obj[$column])?$item_obj[$column]:'');
							}
							if ($column == 'tracknum'){
								$shipped = OdOrderShipped::find()->where(['order_id'=>$order_id ])->andwhere( " ifnull(tracking_number, '') <> '' " )->orderBy('id desc')->one();
								$tmp_arr[$column] = empty($shipped)?'':$shipped->tracking_number;
								
								//判断是否为纯数字，并且长度大于7
								if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0'))
									$tmp_arr[$column] = $tmp_arr[$column].' ';
							}
							if ($column == 'consignee_country_label_cn'){
								$tmp_arr[$column] = empty(StandardConst::$COUNTRIES_CODE_NAME_CN[$order_obj['consignee_country_code']])?$order_obj['consignee_country_code']:StandardConst::$COUNTRIES_CODE_NAME_CN[$order_obj['consignee_country_code']];
							}
							
							if ($column == 'order_item_cost'){
								$orderData = (array)$order_obj->getAttributes();
								$tmpProductCost = OrderApiHelper::getOrderProductCost($orderData, $item_obj['sku']);
								$tmp_arr[$column] = $tmpProductCost['purchase_cost']+$tmpProductCost['addi_cost'];
							}
							
							if($column == 'logistic_status'){
								$tmp_arr[$column] = Tracking::getChineseStatus($order_obj[$column]);
							}
							
							
							if($column == 'order_source_transactionid' || $column == 'order_source_itemid'  || $column ==  'order_source_order_item_id'){
								//判断是否为纯数字，并且长度大于7
								if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0')){
									$tmp_arr[$column] = $item_obj[$column].' ';
								}else{
									$tmp_arr[$column] = $item_obj[$column];
								}
							}
						}
					}elseif($i>1/*&&(in_array('name_cn',$items_arr) || in_array('root_sku',$items_arr))*/){
						//部分判断条件由lzhl屏蔽@ 2015-12-14：
						//该判断会导致如果用户不要求导出name_cn和root_sku的情况下，多商品订单只会导出一条商品，其他商品为空行的bug
						$hasProduct = strlen($item_obj['sku']) && ProductApiHelper::hasProduct($item_obj['sku']);
						foreach ($items_arr as $column){
							if ($column == 'name_cn'){
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['name_cn'] = $product['prod_name_ch'];
								}else{
									$tmp_arr['name_cn'] = '';
								}
							}else if($column == 'root_sku'){
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['root_sku'] = ProductApiHelper::getRootSKUByAlias($item_obj['sku']);
								}else{
									$tmp_arr['root_sku'] = '';
								}
							}elseif ($column == 'order_item_cost'){
								$orderData = (array)$order_obj->getAttributes();
								$tmpProductCost = OrderApiHelper::getOrderProductCost($orderData, $item_obj['sku']);
								$tmp_arr[$column] = $tmpProductCost['purchase_cost']+$tmpProductCost['addi_cost'];
							}elseif($column == 'declaration_ch'){
								new Product();
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['declaration_ch'] = $product['declaration_ch'];
								}else{
									$tmp_arr['declaration_ch'] = '';
								}
							}elseif($column == 'declaration_en'){
								new Product();
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['declaration_en'] = $product['declaration_en'];
								}else{
									$tmp_arr['declaration_en'] = '';
								}
							}elseif($column == 'declaration_value'){
								new Product();
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['declaration_value'] = $product['declaration_value'];
								}else{
									$tmp_arr['declaration_value'] = '';
								}
							}elseif($column == 'declaration_value_currency'){
								new Product();
								if ($hasProduct){
									$product = ProductApiHelper::getProductInfo($item_obj['sku']);
									$tmp_arr['declaration_value_currency'] = $product['declaration_value_currency'];
								}else{
									$tmp_arr['declaration_value_currency'] = '';
								}
							}elseif($column == 'order_source_transactionid' || $column == 'order_source_itemid' || $column ==  'order_source_order_item_id'){
								//判断是否为纯数字，并且长度大于7
								if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0')){
									$tmp_arr[$column] = $item_obj[$column].' ';
								}else{
									$tmp_arr[$column] = $item_obj[$column];
								}
							}elseif($column == 'photo_url'){
								$tmp_arr[$column]= isset($item_obj['photo_primary'])?$item_obj['photo_primary']:'';
							}else{
								$tmp_arr[$column]= isset($order_obj[$column])?$order_obj[$column]:(isset($item_obj[$column])?$item_obj[$column]:'');
							}
						}
					}
					$res[] = $tmp_arr;
					$i++;
				}
			}else{
				foreach ($order_obj->items as $item_obj){
					$tmp_arr = array();
					$hasProduct = (strlen($item_obj['sku']) && ProductApiHelper::hasProduct($item_obj['sku']));
					foreach ($items_arr as $column){
						if ($column == 'name_cn'){
							if ($hasProduct){
								$product = ProductApiHelper::getProductInfo($item_obj['sku']);
								$tmp_arr['name_cn'] = $product['prod_name_ch'];
							}else{
								$tmp_arr['name_cn'] = '';
							}
						}else if($column == 'root_sku'){
							if ($hasProduct){
								$product = ProductApiHelper::getProductInfo($item_obj['sku']);
								$tmp_arr['root_sku'] = ProductApiHelper::getRootSKUByAlias($item_obj['sku']);
							}else{
								$tmp_arr['root_sku'] = '';
							}
						}elseif($column == 'photo_url'){
							$tmp_arr[$column]= isset($item_obj['photo_primary'])?$item_obj['photo_primary']:'';
						}else{
							$tmp_arr[$column]= isset($order_obj[$column])?$order_obj[$column]:(isset($item_obj[$column])?$item_obj[$column]:'');
						}
						if ($column == 'tracknum'){
							$shipped = OdOrderShipped::find()->where(['order_id'=>$order_id ])->andwhere( " ifnull(tracking_number, '') <> '' " )->orderBy('id desc')->one();
							$tmp_arr[$column] = empty($shipped)?'':$shipped->tracking_number;
							
							//判断是否为纯数字，并且长度大于7
							if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0'))
								$tmp_arr[$column] = $tmp_arr[$column].' ';
						}
						if ($column == 'consignee_country_label_cn'){
							$tmp_arr[$column] = empty(StandardConst::$COUNTRIES_CODE_NAME_CN[$order_obj['consignee_country_code']])?$order_obj['consignee_country_code']:StandardConst::$COUNTRIES_CODE_NAME_CN[$order_obj['consignee_country_code']];
						}
						
						if ($column == 'order_item_cost'){
							$orderData = (array)$order_obj->getAttributes();
							$tmpProductCost = OrderApiHelper::getOrderProductCost($orderData, $item_obj['sku']);
							$tmp_arr[$column] = $tmpProductCost['purchase_cost']+$tmpProductCost['addi_cost'];
						}
						
						if($column == 'order_source_transactionid' || $column == 'order_source_itemid'  || $column ==  'order_source_order_item_id'){
							//判断是否为纯数字，并且长度大于7
							if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0')){
								$tmp_arr[$column] = $item_obj[$column].' ';
							}else{
								$tmp_arr[$column] = $item_obj[$column];
							}
						}
					}
					$res[] = $tmp_arr;
				}
			}
		}//end of foreach
		
		foreach ($items_arr as $k=>$v){
			foreach (ExcelHelper::$content as $k1=>$v1){
				if($k1==$v){
					$items_arr[$k]=$v1;
				}
			}
		}
 		//var_dump($res);var_dump($items_arr);die;
		ExcelHelper::exportToExcel($res, $items_arr, 'order_'.date('Y-m-dHis',time()).".xls",['photo_primary'=>['width'=>100,'height'=>100]]);
	}

	
	/**
	 +------------------------------------------------------------------------------
	 * 导入excle
	 +------------------------------------------------------------------------------
	 * @items			传入数据（根据传入的id查找数据库）
	 * @tips			传入字段
	 * @name  			文件名
	 
	 +------------------------------------------------------------------------------
	 */
	public function actionExcel2Order(){
		$file	=	$_FILES['excel_order'];
		if(isset($file)&&!empty($file)){
			$result=\eagle\modules\util\helpers\ExcelHelper::excelToArray($file,\eagle\modules\util\helpers\ExcelHelper::$order_content);
			$arr	=	array();
			foreach ($result as $v){
				$orderdb = new OdOrder();
				$orderdb->attributes=$v;
	// 			$orderdb->setAttributes($v);
				//print_r($orderdb);
				$orderdb->consignee_postal_code=$v['consignee_postal_code']."";
// 				$orderdb->default_shipping_method_code=$v['default_shipping_method_code']."";
// 				$orderdb->consignee_address_line3='';
				
				$s=OrderHelper::importPlatformOrder(['1'=>$v]);
				print_r($s);die();
				$res=$orderdb->save();print_r($orderdb);die();
				if($res){
					var_dump($res);
				}
			}
		}else{
			exit(json_decode(array(
				'success'	=>	false,
				'message'	=>	'未找到excel文件'
			)));
		}
	}
	
	/**
	 * 自定义导出订单格式修改
	 * @author fanjs edit：2015-3-18
	 */
	public  function actionExcelModelList(){	
		AppTrackerApiHelper::actionLog("Oms-erp", "/excel/list");
		$models	= Excelmodel::find(['tablename'=>OdOrder::className()]);
		$pages = new Pagination(['totalCount'=>$models->count()]);		
		$models = $models->offset($pages->offset)		
		->limit($pages->limit)		
		->all();	
		return $this->render('modellist', array(
			'models'=>$models,
			'pages'=>$pages	
		));
		
		
	}
	
	/**
	 * 自定义导出范本的编辑和新增
	 * @author fanjs
	 * 
	 */
	public function actionExcelmodel_edit(){
		if (\Yii::$app->request->isPost){
			if (isset($_POST['mid'])){
				$model = Excelmodel::findOne($_POST['mid']);
			}else{
				$model = new Excelmodel();
			}
			try {
				$model->name = $_POST['modelname'];
				$model->content = implode(',',$_POST['to']);
				$model->type = 1;
				$model->belong = \Yii::$app->user->identity->getFullName();
				$model->tablename = OdOrder::tableName();
				if ($model->save()){
					return $this->redirect(['/order/excel/excel-model-list']);
				}else{
					return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>'保存失败']);
				}
			}catch (\Exception $e){
				return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>$e->getMessage()]);
			}
		}
		if ($_GET['mid']>0){
			$excelmodel = Excelmodel::findOne($_GET['mid']);
			if (empty($excelmodel)){
				return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>'未找到相应的自定义导出范本']);
			}
			if ($excelmodel->tablename != OdOrder::tableName()){
				return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>'传入的范本ID有误']);
			}
		}else{
			$excelmodel = new Excelmodel();
		}
		return $this->render('edit2',['model'=>$excelmodel]);
	}
	
	/**
	 * 删除自定义导出格式
	 * @author fanjs
	 * @return string
	 */
	public function actionExcelmodel_del(){
		if(\Yii::$app->request->isPost){
			if (!empty($_POST['mid'])){
				try {
					Excelmodel::deleteAll(['id'=>$_POST['mid']]);
					return 'success';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}
		}
	}
	
	public function items_arr(){
		return ExcelHelper::$content;
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单导出入库单格式   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/10				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExportInstockExcel (){
		//根据订单 生成入库单格式
		if(!empty($_GET['orderids'])){
			//拆分订单，除去空值
			$idsarr = array_filter(explode(',',$_GET['orderids']));
			$data_array = [];
			//获取订单数据
			$data_array = OdOrderItem::find()->select(['sku' , 'count(quantity)' ])->where(['order_id'=>$idsarr])->GroupBy(['sku'])->asArray()->all();
			//生成 excel 格式的数据
			$sheetInfo = [['data_array'=>$data_array , 'filed_array'=>['产品SKU','出库/入库数量/实际盘点数','货架位置'],'title'=>'Sheet1']];
			ExcelHelper::justExportToExcel($sheetInfo,'入库格式_'.date('Y-m-dHis',time()).".xls");
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单导出  跟踪号导入样式表格   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/10				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExportTrackNoImportExcel(){
		if(!empty($_GET['orderids'])){
			//拆分订单，除去空值 
			$idsarr = array_filter(explode(',',$_GET['orderids']));
			$data_array = [];
			//获取订单数据
			$orderList = OdOrder::find()->select(['order_id' , 'order_source_order_id' ])->where(['order_id'=>$idsarr])->asArray()->all();
			//物流号信息
			$orderShipList = OdOrderShipped::find()->where(['order_id'=>$idsarr])->asArray()->all();
			$shipRT = [];
			foreach($orderShipList as $row){
				$shipRT[$row['order_id']] = $row;
			}
			
			//生成 导出excel数据格式 
			$data_array = [];
			
			foreach($orderList as $order){
				$data_array[] = [$order['order_id'] , @$shipRT[$order['order_id']]['tracking_number'],  @$shipRT[$order['order_id']]['shipping_method_code'] ,  @$shipRT[$order['order_id']]['tracking_link'] ,$order['order_source_order_id'] ];
			}
			
			
			//生成 excel 格式的数据
			$sheetInfo = [['data_array'=>$data_array , 'filed_array'=>['orderid','tracknum','server','tracklink','platformorderid'],'title'=>'Sheet1']];
			ExcelHelper::justExportToExcel($sheetInfo,'跟踪号导入样式表格_'.date('Y-m-dHis',time()).".xls");
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单导出  SKU表格   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/10				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExportProductImportExcel(){
		if(!empty($_GET['orderids'])){
			//拆分订单，除去空值
			$idsarr = array_filter(explode(',',$_GET['orderids']));
			$data_array = [];
			//获取订单商品数据
			$itemList = OdOrderItem::find()->where(['order_id'=>$idsarr])->asArray()->all();
			//
			
			//
			$data_array = [];
			$skuList = [];
				
			foreach($itemList as $item){
				if (array_key_exists($item['sku'],$skuList)){
					continue;
				}
				$skuList[$item['sku']]=true;
				
				$order = OdOrder::findone(['order_id'=>$item['order_id']]);
				//cd平台虚拟sku不需要生成商品
				if(strtolower($order->order_source)=='cdiscount'){
					if(in_array($item['sku'], CdiscountOrderInterface::getNonDeliverySku())){
						continue;
					}
				}
				//获取产品的刊登类目，参数有平台和产品id
				$product_name = OrderApiHelper::getOrderProductCategory($item['order_source_itemid'], $order->order_source);
				
				$data_array[] = [
					'sku'=>$item['sku'],//SKU
					'product_name'=>$item['product_name'],// 商品名称
					'category_name'=>'',// 分类 * 需替换为id
					'brand_name'=>'',// 品牌 * 需替换为id
					"prod_name_ch"=>$product_name['ch'], // 中文配货名称
					"prod_name_en"=>$product_name['en'], // 英文配货名称
					"declaration_ch"=>$product_name['ch'], // 中文报关名
					"declaration_en"=>$product_name['en'], // 英文报关名
					"declaration_value"=>$item['price']*0.2,//报关价格
					"declaration_value_currency"=>$order->currency,//报关币种
					"prod_weight"=>50, // 商品重量(g)
					"prod_length"=>0, // 商品尺寸(长cm)
					"prod_width"=>0, // 商品尺寸(宽cm)
					"prod_height"=>0, // 商品尺寸(高cm)
					"supplier_name"=>0, // 首选供应商 * 需替换为id
					"purchase_price"=>0, // 采购价(CNY)
					"photo_primary"=>(empty($item['photo_primary']))?'':$item['photo_primary'], // 主图片
					// photo_others_* 需要用逗号为分隔符join(separator,array) 到 'photo_others'列里面
					"photo_others_2"=>'', // 图片2
					"photo_others_3"=>'', // 图片3
					"photo_others_4"=>'', // 图片4
					"photo_others_5"=>'', // 图片5
					"photo_others_6"=>'', // 图片6
					"status_cn"=>'', // 产品状态 * 需替换成code
					"prod_tag"=>'',//商品标签,需要转换为is_has_tag,内容保存到'pd_prodcut_tags'里面
					"alias"=>'',//商品别名,保存到alias表

				];
			}
			
			//生成 excel 格式的数据
			$sheetInfo = [['data_array'=>$data_array , 'filed_array'=>['SKU(必填)','商品名称(必填)','分类(终端分类)','品牌','中文配货名称(必填)','英文配货名称(必填)','中文报关名(必填)','英文报关名(必填)','海关申报金额','海关申报货币',	'商品重量(g)','商品尺寸(长cm)','商品尺寸(宽cm)','商品尺寸(高cm)','首选供应商','采购价(CNY)','主图片','图片2','图片3','图片4','图片5','图片6','产品状态("在售","紧缺","下架","归档","重新上架")','产品标签（多个之间用英文\'，\'分隔）','商品别名（多个之间用英文\'，\'分隔）'],'title'=>'Sheet1']];
			ExcelHelper::justExportToExcel($sheetInfo,'跟踪号导入样式表格_'.date('Y-m-dHis',time()).".xls");
		}
	}//end of actionExportProductImportExcel
	
	/**
	 +----------------------------------------------------------
	 * 订单导出  SKU表格   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/10				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExportPurchaseImportExcel(){
		if(!empty($_GET['orderids'])){
			//拆分订单，除去空值
			$idsarr = array_filter(explode(',',$_GET['orderids']));
			$data_array = [];
			//获取订单数据
			$itemlist = OdOrderItem::find()->select(['sku' , 'sumqty'=>'count(quantity)' ])->where(['order_id'=>$idsarr])->GroupBy(['sku'])->asArray()->all();
			$allStockList = [];
			//计算库存量
			foreach($itemlist as $item){
				//获取最近采购单价
				$prodInfo = ProductApiHelper::getProductInfo($item['sku']); 
				if (!empty($prodInfo['purchase_price'])) $purchase_price = $prodInfo['purchase_price'];
				else $purchase_price = 0;
				$StockList = InventoryHelper::getProductAllInventory($item['sku']);
				$allStockList[$item['sku']]=0;//保证 库存一定存在 
				foreach($StockList as $stock){
					if (isset($allStockList[$item['sku']]))
						$allStockList[$item['sku']] += $stock['qty_in_stock'];
					else 
						$allStockList[$item['sku']] = $stock['qty_in_stock'];
					
				}
				if ($allStockList[$item['sku']] < $item['sumqty']){
					$data_array[] = ['sku'=>$item['sku'] , 'qty'=>$item['sumqty'] - $allStockList[$item['sku']] ,'local'=>$purchase_price , 'stock'=>$allStockList[$item['sku']] , 'sumqty'=>$item['sumqty'] ];
				}
			}
			//生成 excel 格式的数据
			$sheetInfo = [['data_array'=>$data_array , 'filed_array'=>['产品SKU','采购数量','采购单价(人民币)','库存','订单总需求数'],'title'=>'Sheet1']];
			ExcelHelper::justExportToExcel($sheetInfo,'入库格式_'.date('Y-m-dHis',time()).".xls");
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单导出  SKU表格   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/08/13				初始化
	 +----------------------------------------------------------
	 **/
	public function actionImportManualOrder(){
		$file	=	$_FILES['import_orders'];
		
		if (empty($_REQUEST['paltform'])){
			exit('ack-failure'.'未指定平台');
		}
		
		if (empty($_REQUEST['selleruserid'])){
			exit('ack-failure'.'未指定账号');
			
		}
		
		
		if(isset($file)&&!empty($file)){
			// get current uid 
			$uid = \Yii::$app->user->id;
			$mappingArr = [
			'A'=>'order_source_order_id',
			'B'=>'sku',
			'C'=>'quantity',
			'D'=>'currency',
			'E'=>'price',
			'F'=>'consignee',
			'G'=>'consignee_address_line1',
			'H'=>'consignee_address_line2',
			'I'=>'consignee_city',
			'J'=>'consignee_province',
			'K'=>'consignee_country_code',
			'L'=>'consignee_postal_code',
			'M'=>'consignee_phone',
			'N'=>'consignee_mobile',
			'O'=>"desc",
			];
			
			$requireColumn = ['order_source_order_id'=>'订单号' ,'selleruserid'=>'店铺' , 'currency'=>'币种' , 'consignee'=>'买家姓名' , 'consignee_country_code'=>'国家' ,'consignee_address_line1'=>'地址1' , 'consignee_postal_code'=>'邮编','consignee_city'=>'城市','consignee_province'=>'省/州'];
			
			
			$result=\eagle\modules\util\helpers\ExcelHelper::excelToArray($file,$mappingArr);
			
			$limitSize = 500;
			if (count($result)>=$limitSize){
				exit('ack-failure'.'excel文件超出'.$limitSize.'行！');
			}
			
			$resultMsg = "";
			
			$orderList = [];
			$orderSourceOrderId = [];
			foreach($result as $rowKey => $row){
				if(trim($row['order_source_order_id']) == ''){
					$resultMsg .= '第'.($rowKey).'行:'.'第1列 必填<br>';
				}
				if(trim($row['sku']) == ''){
					$resultMsg .= '第'.($rowKey).'行:'.'第2列 必填<br>';
				}
				if(isset($row['quantity'])){
					if(!is_double($row['quantity'])){
						$resultMsg .= '第'.($rowKey).'行:'.'第3列 必须为数值<br>';
					}else if($row['quantity'] <= 0){
						$resultMsg .= '第'.($rowKey).'行:'.'第3列 必须大于0<br>';
					}
				}
				if(!empty($row['currency'])){
					if(\common\helpers\Helper_Currency::getCurrencyIsExist($row['currency']) == false){
						$resultMsg .= '第'.($rowKey).'行:'.'第4列 必须为币种如:(USD,EUR,CNY)<br>';
					}
				}
				if(isset($row['price'])){
					if(!is_double($row['price'])){
						$resultMsg .= '第'.($rowKey).'行:'.'第5列 必须为数值<br>';
					}else if($row['price'] <= 0){
						$resultMsg .= '第'.($rowKey).'行:'.'第5列 必须大于0<br>';
					}
				}
				if(empty($row['consignee'])){
					$resultMsg .= '第'.($rowKey).'行:'.'第6列 必填<br>';
				}
				if(empty($row['consignee_address_line1'])){
					$resultMsg .= '第'.($rowKey).'行:'.'第7列 必填<br>';
				}
				if(empty($row['consignee_city'])){
					$resultMsg .= '第'.($rowKey).'行:'.'第9列 必填<br>';
				}
				if(empty($row['consignee_province'])){
					$resultMsg .= '第'.($rowKey).'行:'.'第10列 必填<br>';
				}
				if(empty($row['consignee_country_code'])){
					$resultMsg .= '第'.($rowKey).'行:'.'第11列 必填<br>';
				}else{
					if(!isset(StandardConst::$COUNTRIES_CODE_NAME_CN[$row['consignee_country_code']])){
						$resultMsg .= '第'.($rowKey).'行:'.'第11列 必须为国家二字码<br>';
					}
				}
				if(trim($row['consignee_postal_code']) == ''){
					$resultMsg .= '第'.($rowKey).'行:'.'第12列 必填<br>';
				}
				
				if (empty($orderList[$row['order_source_order_id']])){
					$orderList[$row['order_source_order_id']] = []; //init
					$orderList[$row['order_source_order_id']] = $row;
					unset($orderList[$row['order_source_order_id']]['sku']);
					unset($orderList[$row['order_source_order_id']]['quantity']);
					unset($orderList[$row['order_source_order_id']]['price']);
					$orderList[$row['order_source_order_id']]['selleruserid'] = $_REQUEST['selleruserid'];
					$orderList[$row['order_source_order_id']]['order_capture'] = 'Y';
					$orderList[$row['order_source_order_id']]['order_status'] = OdOrder::STATUS_PAY;
					$orderList[$row['order_source_order_id']]['order_source'] = $_REQUEST['paltform'];
					$orderList[$row['order_source_order_id']]['order_source_create_time'] = time();
					$orderList[$row['order_source_order_id']]['paid_time'] = time();   //付款时间
					$orderList[$row['order_source_order_id']]['consignee_country'] = @StandardConst::$COUNTRIES_CODE_NAME_EN[$orderList[$row['order_source_order_id']]['consignee_country_code']];
					if (empty($orderList[$row['order_source_order_id']]['currency'])) $orderList[$row['order_source_order_id']]['currency'] ="USD";
					
					
					if (isset($_REQUEST['site']))
						$orderList[$row['order_source_order_id']]['order_source_site_id'] = $_REQUEST['site'];
					
					$orderSourceOrderId [] = $row['order_source_order_id'];
					
				}
				
				$item = ['order_source_order_id'=>$row['order_source_order_id'],'sku'=>$row['sku'] ,'product_name'=>$row['sku'], 'quantity'=>$row['quantity'] , 'ordered_quantity'=>$row['quantity'] , 'price'=>$row['price'] , 'order_source_itemid'=>''];
				$item['root_sku'] = ProductHelper::getRootSkuByAlias($row['sku'], $orderList[$row['order_source_order_id']]['order_source'], $orderList[$row['order_source_order_id']]['selleruserid'] );
				$productInfo = ProductHelper::getProductInfo($item['root_sku']);
				if (!empty($productInfo)){
				    $item['product_name'] =  $productInfo['name'];
				    $item['photo_primary'] =  $productInfo['photo_primary'];
				}
				
				$orderList[$row['order_source_order_id']]['items'][] = $item;
				
				if (empty($orderList[$row['order_source_order_id']]['subtotal'])) $orderList[$row['order_source_order_id']]['subtotal'] = 0;
				if (empty($orderList[$row['order_source_order_id']]['grand_total'])) $orderList[$row['order_source_order_id']]['grand_total'] = 0;
				
				$orderList[$row['order_source_order_id']]['subtotal'] += $row['quantity'] *$row['price'];
				$orderList[$row['order_source_order_id']]['grand_total'] += $row['quantity'] *$row['price'];
			}
			//模版字段对应的映射数据
			
			//先对Excel写入的数据做验证,验证不通过则全部不容许插入
			if(!empty($resultMsg)){
				exit('ack-failure'.$resultMsg);
			}
			
			//检查订单是否存在
			
			$checkRT = OdOrder::find()->select(['order_source_order_id','order_source'])->where(['order_source_order_id'=>$orderSourceOrderId])->asArray()->all();
			$existOrderList = Helper_Array::toHashmap($checkRT, 'order_source_order_id' ,'order_source' );
			
			$RTmsg = "执行结果：";
			$successCT = 0;
			$failureCT = 0;
			foreach($orderList as $order_source_order_id=>$orderData){
				if (isset($existOrderList[$order_source_order_id])){
					
					$RTmsg .="<br>【".$order_source_order_id."】已经存在，请到【".$existOrderList[$order_source_order_id]."订单】中查看";
					$failureCT++;
					continue;
				}
				$order = [$uid=>$orderData];
				$rt = OrderHelper::importPlatformOrder($order);
				
				if ($rt['success']==0){
					//$RTmsg .= "<br>【".$order_source_order_id."】导入成功！";
					$successCT++;
				}else{
					$RTmsg .= "<br>【".$order_source_order_id."】导入失败！原因：".$rt['message'];
					$failureCT++;
				}
				
			}
			
			$RTmsg .= "<br>共".count($orderList)."张订单，成功导入".$successCT."张， 导入失败".$failureCT."张";
			
			exit('ack-success'.$RTmsg);
		}else{
			exit('ack-failure'.'未找到excel文件');
			
		}
	}//end of function actionImportManualOrder

	/**
	 +----------------------------------------------------------
	 * 选择导出范本的页面
	 +----------------------------------------------------------
	 * @param str  导出数据
	 * @param  type 导出类型
	 * @param count 导出数量
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/01/20				初始化
	 +----------------------------------------------------------
	 **/
	public function actionOrderexcelmodel(){
		$str=empty($_POST['str'])?'':$_POST['str'];
		$exceltype=empty($_POST['type'])?'0':$_POST['type'];
		$ordercount=empty($_POST['count'])?'0':$_POST['count'];
		
		$model=Excelmodel::find()->asArray()->all();
		
		return $this->renderPartial('orderExcelmodel',[
				'model'=>$model,
				]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 得到模板范本
	 +----------------------------------------------------------
	 * @param 
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/01/20				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetByTypeHtml(){
		$model=Excelmodel::find()->asArray()->all();

		$html='	<select class="form-control" style="width:140px;display:inline;" onchange="changeExportTemplate(\'order\');" id="exportTemplateSelect">
						<option value="">--请选择范本--</option>';
									$html.='<option value="-1">标准范本</option>';
									//$html.='<option value="-8">入库单/库存盘点单</option>';
									//echo '<option value="-2">e邮宝范本</option>';
									//echo '<option value="-3">中邮范本</option>';
									//echo '<option value="-4">4PX标准版(含订单信息)</option>';
									//echo '<option value="-5">4PX标准版(新)</option>';
									//echo '<option value="-6">俄罗斯预报单</option>';
									//echo '<option value="-7">燕文范本</option>';
									if(!empty($model)){
										foreach ($model as $modelone){
											$html.='<option value="'.$modelone['id'].'">'.$modelone['name'].'</option>';
										}
									}						
							$html.='</select>
							';
							
		return $html;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取导出范本的字段
	 +----------------------------------------------------------
	 * @param id  范本id
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/01/20				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetOrderExcelId(){
		$id=!isset($_POST['id'])?'-1':$_POST['id'];

		$data=array(
				'code'=>1,
				'orderField'=>'',
		);

		if($id=='-1'){
			$content=ExcelHelper::$content;
			$contenttext='';
			foreach ($content as $keys=>$contentone){
				if($keys!='custom')
					$contenttext.=$keys.':'.$contentone.':,';
			}
			$contenttext=substr($contenttext, 0,-1);
			$data['code']=0;
			$data['orderField']=$contenttext;
		}
		else if($id=='-2'){
			$content=array(
					'order_id'=>'订单号',
					''=>'商品交易号',
					'sku'=>'商品SKU',
					'quantity'=>'数量',
					'consignee'=>'收件人姓名（英文）',
					'consignee_address_line1'=>'收货人地址1',
					'consignee_address_line2'=>'收货人地址2',
					'consignee_address_line3'=>'收货人地址3',
					'consignee_city'=>'收货人城市',
					'consignee_province'=>'收货人省',
					'consignee_postal_code'=>'收货人邮编',
					'consignee_country_code'=>'收货人国家',
					'consignee_phone'=>'收货人电话',
					'consignee_email'=>'收货人电子邮箱',
					''=>'自定义信息1',
					'desc'=>'订单备注',
					''=>'来源',
					''=>'寄件地址',
					''=>'发货地址',
					''=>'业务类型',
					''=>'增值服务',
			);
			$contenttext='';
			foreach ($content as $keys=>$contentone){
				$contenttext.=$keys.':'.$contentone.':,';
			}
			$contenttext=substr($contenttext, 0,-1);
			$data['code']=0;
			$data['orderField']=$contenttext;
		}
		else if($id=='-3'){
			$content=array(
					'order_id'=>'订单号',
					'tracknum'=>'运单号',
					''=>'寄达国家（中文）',
					'consignee_postal_code'=>'收件人邮编',
					''=>'寄达国家（英文）',
					'consignee_province'=>'州名',
					'consignee_city'=>'城市名',
					'address1_2'=>'收件人详细地址',
					'consignee'=>'收件人姓名',
					'consignee_phone'=>'收件人电话',
			);
			$contenttext='';
			foreach ($content as $keys=>$contentone){
				$contenttext.=$keys.':'.$contentone.':,';
			}
			$contenttext=substr($contenttext, 0,-1);
			$data['code']=0;
			$data['orderField']=$contenttext;
		}
		else if($id=='-4'){
// 			$content=array(
// 					'order_id'=>'订单号',
// 					'order_status'=>'订单状态',
// 					'consignee'=>'买家名称',
// 					'paid_time'=>'付款时间',
// 					'subtotal'=>'订单金额',
// 					''=>'产品信息',
// 					'desc'=>'订单备注',
// 					'address1_2'=>'收货地址',
// 					'consignee'=>'收货人名称',
// 					'consignee_country_code'=>'收货国家',					
// 					'consignee_province'=>'州 省',
// 					'consignee_city'=>'城市',
// 					'address1_2'=>'地址',
// 					'consignee_postal_code'=>'邮编',
// 					'consignee_phone'=>'联系电话',
// 					'consignee_mobile'=>'手机',
// 					'order_source_shipping_method'=>'买家选择物流',
// 					''=>'寄件人公司名',
// 					''=>'寄件人姓名',
// 					''=>'寄件人地址',
// 					''=>'寄件人电话',
// 					''=>'保险类型',
// 					''=>'保险价值',
// 					''=>'是否退件',
// 					'declaration_ch'=>'海关报关品名1',
// 					''=>'配货信息1',
// 					'declaration_value'=>'申报价值1',
// 					'quantity'=>'申报数量1',
// 			);
// 			$contenttext='';
// 			foreach ($content as $keys=>$contentone){
// 				$contenttext.=$keys.':'.$contentone.':,';
// 			}
// 			$contenttext=substr($contenttext, 0,-1);
// 			$data['code']=0;
// 			$data['orderField']=$contenttext;
		}
		else if($id=='-5'){
// 			$content=array(
// 					'order_id'=>'订单号',
// 					'服务商单号'=>'服务商单号',
// 					'consignee_country_code'=>'收货国家',
// 					''=>'寄件人公司名',
// 					''=>'寄件人姓名',
// 					''=>'寄件人省',
// 					''=>'寄件人城市',
// 					''=>'寄件人电话',
// 					''=>'寄件人邮编',
// 					''=>'寄件人传真',
// 					'consignee_company'=>'收件人公司名',
// 					'consignee'=>'收货人姓名',
// 					'consignee_province'=>'州 省',
// 					'consignee_city'=>'城市',
// 					'address1_2'=>'联系地址',
// 					'consignee_phone'=>'收件人电话',
// 					'consignee_postal_code'=>'收件人邮编',
// 					''=>'收件人传真',
// 					'source_buyer_user_id'=>'买家ID',
// 					''=>'交易ID',
// 					''=>'保险类型',
// 					''=>'保险价值',
// 					'desc'=>'订单备注',
// 					''=>'重量(g)',
// 					''=>'是否退件',
// 					''=>'申报类型',
// 			);
// 			$contenttext='';
// 			foreach ($content as $keys=>$contentone){
// 				$contenttext.=$keys.':'.$contentone.':,';
// 			}
// 			$contenttext=substr($contenttext, 0,-1);
// 			$data['code']=0;
// 			$data['orderField']=$contenttext;
		}
		else if($id=='-6'){
			$content=array(
					'order_id'=>'订单号',
					'tracknum'=>'快递单号',
					''=>'发货方式',
					'order_id'=>'订单号',
					'consignee'=>'收件人姓名',
					'consignee_country_label_cn'=>'收件人国名（中文）',
					'consignee_country_code'=>'收件人国家',
					'address1_2'=>'收件人地址',
					'consignee_city'=>'收件人城市',
					''=>'州编号',
					'consignee_postal_code'=>'收件人邮编',
					'consignee_phone'=>'收件人电话',
					'consignee_mobile'=>'收件人手机',
					'consignee_email'=>'收件人EMAIL',
					'consignee_company'=>'收件人公司',
					'declaration_ch'=>'中文品名',
					'declaration_en'=>'英文品名',
					'quantity'=>'件数',
					''=>'重量(g)',
					'sku_quantity'=>'多品名',
			);
			$contenttext='';
			foreach ($content as $keys=>$contentone){
				$contenttext.=$keys.':'.$contentone.':,';
			}
			$contenttext=substr($contenttext, 0,-1);
			$data['code']=0;
			$data['orderField']=$contenttext;
		}
		else if($id=='-7'){
			$content=array(
					'order_id'=>'订单号',
					'tracknum'=>'快递单号',
					''=>'发货方式',
					''=>'重量(g)',
					'declaration_value_currency'=>'币种',
					'declaration_value'=>'申报价值',
					'quantity'=>'件数',
					'declaration_ch'=>'中文品名',
					'declaration_en'=>'英文品名',
					'sku_quantity'=>'多品名',
					'desc'=>'备注',
					''=>'商品海关编码',
					'order_id'=>'订单号',
					'consignee'=>'买家名称',
					'consignee_email'=>'买家邮箱',
					'address1_2'=>'收货地址',
					'consignee'=>'收货人名称',
					'consignee_country_code'=>'收货国家',
					'consignee_province'=>'州/省',
					'consignee_city'=>'城市',
					'address1_2'=>'地址',
					'consignee_postal_code'=>'邮编',
					'sku_quantity'=>'多品名',
			);
			$contenttext='';
			foreach ($content as $keys=>$contentone){
				$contenttext.=$keys.':'.$contentone.':,';
			}
			$contenttext=substr($contenttext, 0,-1);
			$data['code']=0;
			$data['orderField']=$contenttext;
		}
		else{
			$excelmodel	=	new Excelmodel();
			$excel	=	$excelmodel->findOne($id);
			if(!empty($excel)){
				$data['code']=0;
				$data['orderField']=$excel['content'];
			}
		}
		
		return json_encode($data);
	}
	
	/**
	 +----------------------------------------------------------
	 * 导出订单(前端)
	 +----------------------------------------------------------
	 * @param str  范本数据
	 * @param type   导出类型(0勾选导出 ， 1全部导出)
	 * @param exportTemplateSelect  导出范本id
	 * @param checkkey 标准范本的字段
	 * @param isMaster 按什么来导出
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/01/20				初始化
	 +----------------------------------------------------------
	 **/
	public function actionStraightExportExcel(){
// 		AppTrackerApiHelper::actionLog("catalog", "/purchase/purchase/straight-export-excel");
		$QUERY=$_SERVER['REQUEST_URI'];
		$temparr=explode('&',$QUERY);
		if(isset($temparr[1]) && strstr($temparr[1],'str=')!=false){
			$str=substr($temparr[1], 4);
		}
		else
			$str = empty($_REQUEST['str'])?'':trim($_REQUEST['str']);
		$type = empty($_REQUEST['type'])?0:trim($_REQUEST['type']);
		$exportTemplateSelect=empty($_REQUEST['exportTemplateSelect'])?0:trim($_REQUEST['exportTemplateSelect']);
		$checkkey=empty($_REQUEST['checkkey'])?'':trim($_REQUEST['checkkey']);
		$isMaster=!isset($_REQUEST['isMaster'])?'2':$_REQUEST['isMaster'];
		
		if($type == 0){
			$order_list = rtrim($str, ',').'|'.$exportTemplateSelect.'|'.$checkkey.'|'.$isMaster;
		}
		else{
			$str = $str == base64_encode(base64_decode($str)) ? base64_decode($str) : $str;
			$order_list = json_decode($str,true);
			$order_list['exportTemplateSelect']=$exportTemplateSelect;
			$order_list['checkkey']=$checkkey;
			$order_list['isMaster']=$isMaster;
		}
		
		$uid=\Yii::$app->user->id;
		OrderHelper::ExportExcelAll($order_list,true,$uid);
	}
	
	/**
	 +----------------------------------------------------------
	 * 导出订单添加到队列
	 +----------------------------------------------------------
	 * @param str  范本数据
	 * @param type   导出类型(0勾选导出 ， 1全部导出)
	 * @param exportTemplateSelect  导出范本id
	 * @param checkkey 标准范本的字段
	 * @param isMaster 按什么来导出
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/01/20				初始化
	 +----------------------------------------------------------
	 **/
	public function actionAddExportExcel(){
		$type = empty($_POST['type']) ? '' : $_POST['type'];
		$str = empty($_POST['str']) ? '' : $_POST['str'];
		$exportTemplateSelect=empty($_REQUEST['exportTemplateSelect'])?0:trim($_REQUEST['exportTemplateSelect']);
		$checkkey=empty($_REQUEST['checkkey'])?'':trim($_REQUEST['checkkey']);
		$isMaster=!isset($_REQUEST['isMaster'])?'2':$_REQUEST['isMaster'];

		$purchase_ids = array();
		if($type == 0){
			$order_list = rtrim($str, ',').'|'.$exportTemplateSelect.'|'.$checkkey.'|'.$isMaster;
			$order_ids = json_encode($order_list);
		}
		else{
			$str = $str == base64_encode(base64_decode($str)) ? base64_decode($str) : $str;
			$order_list = json_decode($str,true);
			$order_list['exportTemplateSelect']=$exportTemplateSelect;
			$order_list['checkkey']=$checkkey;
			$order_list['isMaster']=$isMaster;
			$order_ids = base64_encode(json_encode($order_list));
		}
		
		$className = addslashes('\eagle\modules\order\helpers\OrderHelper');
		$functionName = 'ExportExcelAll';

		$rtn = ExcelHelper::insertExportCrol($className, $functionName, $order_ids, 'order_list');
	
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
	 * @param pending_id 队列id
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lgw		  2017/01/20		初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetExcelUrl(){
		$pending_id = empty($_POST['pending_id']) ? '0' : $_POST['pending_id'];
	
		return ExcelHelper::getExcelUrl($pending_id);
	}
	
	//导出货代的格式
	public function actionExportCarrierImportExcel(){
		//根据订单 生成入库单格式
		if(!empty($_GET['orderids'])){
			//拆分字符串，除去空值
			$idsarr = array_filter(explode(',',$_GET['orderids']));
			$data_array = [];
			
			$sheetInfo = [];
			
			//获取订单数据
// 			$data_array = OdOrderItem::find()->select(['sku' , 'count(quantity)' ])->where(['order_id'=>$idsarr])->GroupBy(['sku'])->asArray()->all();
			
			if(isset($_GET['type'])){
				if($_GET['type'] == 'exportEubExcel'){
					$addressAndPhoneParams = array(
			        		'address' => array(
			        				'consignee_address_line1_limit' => 254,
			        		),
			        		'consignee_district' => 1,
			        		'consignee_county' => 1,
			        		'consignee_company' => 1,
			        		'consignee_phone_limit' => 30
			        );
						
					$data_array = CarrierOpenHelper::getOrderCarrierData($idsarr, $addressAndPhoneParams);
						
// 					print_r($data_array);
// 					exit;
					
					$data_order_list = array();
					$data_order_skus = array();
					
					foreach ($data_array as $data_array_one){
						
						$tmp_skus = '';
						$tmp_qty_sum = 0;
						
						foreach ($data_array_one['items'] as $data_one_item){
							$tmp_skus .= ($tmp_skus == '') ? $data_one_item['sku'] : ("\n".$data_one_item['sku']);
							$tmp_qty_sum += $data_one_item['quantity'];
							
							$data_order_skus[$data_one_item['sku']] = array(
									$data_one_item['sku'],
									$data_one_item['declaration_nameCN'],
									$data_one_item['declaration_nameEN'],
									$data_one_item['declaration_weight'],
									$data_one_item['declaration_price'],
									'CN'
							);
						}
						
						$data_order_list[] = array(
								$data_array_one['list']['order_id'],
								'',
								$tmp_skus,
								$tmp_qty_sum,
								$data_array_one['list']['consignee'],
								$data_array_one['list']['address_line1'],
								$data_array_one['list']['address_line2'],
								$data_array_one['list']['address_line3'],
								$data_array_one['list']['consignee_city'],
								$data_array_one['list']['consignee_province'],
								$data_array_one['list']['consignee_postal_code'],
								$data_array_one['list']['consignee_country_code'],
								$data_array_one['list']['phone1'],
								$data_array_one['list']['consignee_email'],
								'',
								$data_array_one['list']['desc'],
						);
						
					}
						
					$order_list_title = ['订单号','商品交易号','商品SKU','数量','收件人姓名','收件人地址1','收件人地址2','收件人地址3','收件人城市','收件人州','收件人邮编','收件人国家','收件人电话','收件人电子邮箱','自定义信息1','备注','来源','寄件地址','发货地址','业务类型','增值服务'];
					$order_sku_title = ['SKU编号','商品中文名称','商品英文名称','单件重量（3位小数）','单件报关价格(整数)','原寄地','保存至系统SKU','税则号','销售链接','备注'];
						
					//生成 excel 格式的数据
					$sheetInfo = [['data_array'=>$data_order_list , 'filed_array'=>$order_list_title,'title'=>'订单列表'],
						['data_array'=>$data_order_skus , 'filed_array'=>$order_sku_title,'title'=>'SKU列表']
					];
				}
			}
			
			ExcelHelper::justExportToExcel($sheetInfo,'EUB格式_'.date('Y-m-dHis',time()).".xls");
		}
	}
	
}


?>

