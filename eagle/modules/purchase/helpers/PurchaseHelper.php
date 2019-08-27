<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\purchase\helpers;

use eagle\modules\purchase\models\Purchase;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\purchase\models\Supplier;
use eagle\modules\purchase\helpers\SupplierHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\inventory\models\ProductStock;

use yii;
use yii\data\Pagination;
use eagle\modules\purchase\models\PurchaseItems;
use eagle\modules\catalog\models\Product;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\catalog\models\ProductSuppliers;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use yii\data\Sort;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\inventory\models\StockChange;
use eagle\modules\permission\helpers\UserHelper;
use eagle\modules\platform\apihelpers\Al1688AccountsApiHelper;
use common\api\al1688interface\Al1688Interface_Api;
use eagle\modules\purchase\models\Pc1688Listing;
use eagle\models\Saas1688User;
use eagle\modules\app\apihelpers\AppApiHelper;

/**
 * BaseHelper is the base class of module BaseHelpers.
 *

 */
class PurchaseHelper {
	
//采购单的付款状态
	const PURCHASE_UNPAID= 1; //等待付款
	const PURCHASE_PAID  = 2; // 已经付款
	const PURCHASE_NOT_REQUIRED  = 3; //采购计划不需要付款，没付款而且被取消的采购单
	
	//采购单的状态	
	const WAIT_FOR_ARRIVAL  = 1; //审批通过,由采购计划正式转成采购单，等待到货
	const WAIT_FOR_PARTIAL_ARRIVAL  = 2;
	const ALL_ARRIVED  = 3;
	const PARTIAL_ARRIVED_CANCEL_LEFT  = 4;
	const STOCK_INED  = 5;//已入库
	const CANCELED  = 6;
	const DRAFT=7; //采购计划草稿,没提审
	const PLAN_WAIT_FOR_APPROVAL=8; //采购计划,已经提审,等待审批
	const REJECT_WAIT_FOR_MODIFY=9; //计划审批失败，等待修改重新提审
	const PLAN_CANCELED=10;
	const CANCELED_INSTOCK  = 11;

	public static $PURCHASE_STATUS = array(
			self::WAIT_FOR_ARRIVAL=>'等待到货',
			//self::WAIT_FOR_PARTIAL_ARRIVAL=>'部分到货',
			self::ALL_ARRIVED=>'全部到货',
			self::PARTIAL_ARRIVED_CANCEL_LEFT=>'部分入库',
			self::STOCK_INED=>'全部入库',
			self::CANCELED=>'已取消',
			//self::DRAFT=>'未提审',
			//self::PLAN_WAIT_FOR_APPROVAL=>'已提审待批',
			//self::REJECT_WAIT_FOR_MODIFY=>'审批未获通过',
			//self::PLAN_CANCELED=>'审批已取消',
			self::CANCELED_INSTOCK=>'中止入库',
	);
	
	protected static $PAYMENT_STATUS = array(
			"1" => "未付款",
			"2" => "已付款",
			"3" => "不需要付款",
	);
	
	protected static $warehouseInfo=array();
	
	public static function testconsole(){
		echo "purchaseHelper testconsole \n";
	}
	
	private static $EXCEL_COLUMN_MAPPING = [
	"A" => "sku",
	"B" => "qty",
	"C" => "price",
	"D" => "remark",
	];
	
	private static $EXPORT_EXCEL_FIELD_LABEL_PURCHASE = [
	"sku" => "产品SKU",
	"qty" => "采购数量",
	"price" => "采购单价(人民币)",
	"remark" => "备注",
	];
	
	public static $EXPORT_EXCEL_FIELD_LABEL = [
	"sku" => "产品SKU",
	"qty" => "采购数量",
	"price" => "采购单价(人民币)",
	];
	
	//采购相关的所有状态信息。             type ---order 表示采购单；plan表示采购计划
	//label 是用于采购list界面的状态列显示
	// operation list界面mouseover的操作选项
	//key 主要是为了前端js来判断当前订单的状态
	public static function getAllStatusInfo() {		
		$statusInfo=array(
		    	self::WAIT_FOR_ARRIVAL=>array("operation"=>"view,edit,cancel,arrival,print","type"=>"order","label"=>"等待到货","key"=>"WAIT_FOR_ARRIVAL"),
				//self::WAIT_FOR_PARTIAL_ARRIVAL=>array("operation"=>"view,edit,cancel_left,arrival,arrival_record,print","type"=>"order","label"=>"部分到货等待剩余","key"=>"WAIT_FOR_PARTIAL_ARRIVAL"),
				self::ALL_ARRIVED =>array("operation"=>"view,arrival_record,print","type"=>"order","label"=>"全部到货","key"=>"ALL_ARRIVED"),
				//self::PARTIAL_ARRIVED_CANCEL_LEFT=>array("operation"=>"view,arrival_record,print","type"=>"order","label"=>"部分到货不等剩余","key"=>"PARTIAL_ARRIVED_CANCEL_LEFT"),
				self::STOCK_INED=>array("operation"=>"view,edit,print","type"=>"order","label"=>"已入库","key"=>"STOCK_INED"),
				self::CANCELED=>array("operation"=>"view","type"=>"order","label"=>"已作废","key"=>"CANCELED"),
				//self::DRAFT=>array("operation"=>"view,edit,commit_review,cancel_plan","type"=>"plan","label"=>"计划草稿","key"=>"DRAFT"),
				//self::PLAN_WAIT_FOR_APPROVAL=>array("operation"=>"view,review,cancel_plan","type"=>"plan","label"=>"等待审批","key"=>"PLAN_WAIT_FOR_APPROVAL"),
				//self::REJECT_WAIT_FOR_MODIFY=>array("operation"=>"view,edit,cancel_plan","type"=>"plan","label"=>"等待修改重新提审","key"=>"REJECT_WAIT_FOR_MODIFY"),				
				//self::PLAN_CANCELED=>array("operation"=>"view","type"=>"plan","label"=>"计划已作废","key"=>"PLAN_CANCELED"),
		);
		return $statusInfo;
		
	}

	public static function getStatusIdLabelMap(){
		$statusInfo=self::getAllStatusInfo();
		$statusIdLabelMap=array();
		foreach($statusInfo as $statusId =>$info){
			$statusIdLabelMap[$statusId]=$info["label"];
		}
		return $statusIdLabelMap;
	}
	
	/**
	 * 获取订单来源列表
	 */
	static public function getOrderSource() {
		return array(
			1 => 'igv',
			2 => 'ebay',
			3 => 'wap',
			4 => 'ios',
			5 => 'Android'
		);
	}
	
	/**
	 * 获取订单状态列表
	 */
	static public function getOrderStatusList() {
		return array(
			1 => '处理中',
			2 => '已完成',
			3 => '取消作废',
		);
	}
	
	/**
	 * 获取付款状态列表
	 */
	static public function getPayStatusList() {
		return array(
			1 => '未付款',
			2 => '等待付款确认',
			3 => '已付款未处理',
			4 => '付款审核中',
			5 => '付款已审核',
			6 => 'chargeback',
			7 => '退款处理中',
			8 => '全额退款',
			9 => '部分退款'
		);
	}
	
	/**
	 * 获取递送状态列表
	 */
	static public function getDeliveryStatusList() {
		return array(
			1 => '未递送',
			2 => '部分递送',
			3 => '完全递送'
		);
	}
	
	/**
	 * 获取订单商品处理状态列表
	 */
	static public function getOperatorStatusList() {
		return array(
			0 => '待完善信息',
			1 => '加急递送',
			2 => '正常递送',
			4 => '需审核',
			8 => '审核中',
			16 => '递送完成'
		);
	}
	/**
	 * 1688订单状态
	 */
	static public function get1688OrderStatusList() {
		return array(
			'cancel' => '交易取消',
			'waitbuyerpay' => '待付款',
			'waitsellersend' => '等待卖家发货',
			'waitbuyerreceive' => '等待买家收货',
			'success' => '交易成功',
		);
	}
	/**
	 * 1688物流状态
	 */
	static public function get1688LogisticsStatusList() {
		return array(
			'WAITACCEPT' => '未受理',
			'CANCEL' => '已撤销',
			'ACCEPT' => '已受理',
			'TRANSPORT' => '运输中',
			'NOGET' => '揽件失败',
			'SIGN' => '已签收',
			'UNSIGN' => '签收异常',
		);
	}
	/**
	 * get warehouse id&name info
	 * @return array(
	 * 				"$warehouse_id"=>"$name"
	 * 			)
	 */
	public static function warehouse(){
	    //读取是否显示海外仓仓库
	    $is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
	    if(empty($is_show))
	    	$is_show = 0;
	     
	    //不显示海外仓仓库
	    if($is_show == 0)
	    {
	    	$warehouseData = Warehouse::find()->where(['is_active'=>'Y'])->andwhere("is_oversea=0")->asArray()->all();
	    }
	    else 
	    {
		    $warehouseData = Warehouse::find()->where(['is_active'=>'Y'])->andwhere("name!='无'")->asArray()->all();
	    }
	    
		$warehouse = array();
		foreach($warehouseData as $w){
			$warehouse[$w['warehouse_id']]=$w['name'];
		}
		return $warehouse;
	}

	/**
	 * 订单信息列表
	 */
	static public function getListDataByCondition( $sort , $order , $condition = array()){
		$query = Purchase::find();

		foreach($condition as $key => $val){
			if(!is_array($val)){
				$val = trim($val);
			}
			if(empty($val)){
				continue;
			}
			switch($key){
				case 'search_type':
				case 'page':
				case 'per-page':
					break;
				case 'sdate':
					$query->andWhere(['>=', 'create_time', $condition['sdate']]);
					break;
				case 'edate':
					$query->andWhere(['<=', 'create_time', date('Y-m-d', strtotime('+1 day', strtotime($condition['edate'])))]);
					break;
				case 'warehouse_id':
					$query->andWhere(['warehouse_id' => $condition['warehouse_id']]);
					break;
				case 'status':
					$query->andWhere(['status' => $condition['status']]);
					break;
				case 'supplier_id':
					$query->andWhere(['supplier_id' => $condition['supplier_id']]);
					break;
				case 'payment_status':
					$query->andWhere(['payment_status' => $condition['payment_status']]);
					break;
				case 'keyword':
					if(!empty($condition['search_type']) && $condition['search_type'] == 'tru_no')
						$query->andWhere(['like', 'delivery_number', $condition['keyword']]);
					else if(!empty($condition['search_type']) && $condition['search_type'] == 'sku')
						$query->andWhere("id in (select purchase_id from pc_purchase_items where sku like '%".$condition['keyword']."%')");
					else 
						$query->andWhere(['like', 'purchase_order_id', $condition['keyword']]);
					break;
				default:
					$query->andWhere([$key => $val]);
					break;		
			}
		}
		//只显示启用的仓库的信息
		$query->andWhere("warehouse_id is null or warehouse_id in (select warehouse_id from wh_warehouse where is_active!='N' and is_active != 'D' and name!='无') ");
		
		$pagination = new Pagination([
			'defaultPageSize' => 20,
			'totalCount' => $query->count(),
			'pageSizeLimit'=>[5,200],
		]);
		
		$data['pagination'] = $pagination;
		if(empty($sort)){
			$sort = 'purchase_order_id';
			$order = 'desc';
		}
		$data['list'] = $query
			->offset($pagination->offset)
			->limit($pagination->limit)
			->orderBy($sort.' '.$order)
			->asArray()
			->all();
		
		//统计Item信息
		$purchase_ids = array();
		foreach($data['list'] as $one){
			$purchase_ids[] = $one['id'];
		}
		//各订单SKU种类数、采购数量
		$skus = array();
		$pur_skus = array();
		$pur_items = array();
		$pur_sku_queue = PurchaseItems::find()->select("purchase_id, count(sku) as sku_count, sum(qty) as qty_count, sku")->where(['purchase_id' => $purchase_ids])->orderBy('purchase_item_id')->groupBy('purchase_id')->asArray()->all();
		foreach($pur_sku_queue as $one){
			$pur_items[$one['purchase_id']] = [
				'sku_count' => $one['sku_count'],
				'qty_count' => $one['qty_count'],
				'sku' => $one['sku'],
			];
			$skus[] = $one['sku'];
			$pur_skus[$one['sku']][] = $one['purchase_id'];
		}
		//商品对应的图片
		$products = Product::find()->select(['sku', 'photo_primary'])->where(['sku' => $skus])->asArray()->all();
		foreach($products as $product){
			if(array_key_exists($product['sku'], $pur_skus)){
				foreach($pur_skus[$product['sku']] as $purchase_id){
					$pur_items[$purchase_id]['photo_primary'] = $product['photo_primary'];
				}
			}
		}
		
		//整理信息
		//供应商
		$data['suppliers'] = SupplierHelper::ListSupplierData();
		//仓库
		$data['warehouse'] = PurchaseHelper::warehouse();
		//付款状态
		$data['paymentStatus'] = PurchaseHelper::getPaymentStatus();
		//物流方式
		$shippings = array();
		$shippingModels = PurchaseShippingHelper::getAllShippingModes();
		foreach ($shippingModels as $shipping){
			$shippings[$shipping['shipping_id']] = $shipping['shipping_name'];
		}
		//1688订单状态
		$orderStatus1688List = self::get1688OrderStatusList();
		//1688物流状态
		$logisticsStatusList1688 = self::get1688LogisticsStatusList();
		foreach ($data['list'] as &$one) {
			$one['status_val'] = $one['status'];
			$one['status'] = empty(self::$PURCHASE_STATUS[$one['status']]) ? '' : self::$PURCHASE_STATUS[$one['status']];
			$one['supplier_name'] = empty($data['suppliers'][$one['supplier_id']]) ? '' : $data['suppliers'][$one['supplier_id']]['name'];
			$one['warehouse_name'] = empty($data['warehouse'][$one['warehouse_id']]) ? '' : $data['warehouse'][$one['warehouse_id']];
			$one['payment_name'] = empty($data['paymentStatus'][$one['payment_status']]) ? '' : $data['paymentStatus'][$one['payment_status']];
			$one['delivery_method_name'] = (empty($one['delivery_method']) || empty($shippings[$one['delivery_method']])) ? '' : $shippings[$one['delivery_method']];
			$one['create_time'] = empty($one['create_time']) ? '' : date('Y-m-d', strtotime($one['create_time']));
			$one['expected_arrival_date'] = empty($one['expected_arrival_date']) ? '' : date('Y-m-d', strtotime($one['expected_arrival_date']));
			$one['sku_count'] = empty($pur_items[$one['id']]) ? 0 : $pur_items[$one['id']]['sku_count'];
			$one['qty_count'] = empty($pur_items[$one['id']]) ? 0 : $pur_items[$one['id']]['qty_count'];
			$one['photo_primary'] = empty($pur_items[$one['id']]['photo_primary']) ? '' : $pur_items[$one['id']]['photo_primary'];
			$one['pay_status_name_1688'] = empty($orderStatus1688List[$one['status_1688']]) ? $one['status_1688'] : $orderStatus1688List[$one['status_1688']];
			$one['logistics_status_name_1688'] = empty($logisticsStatusList1688[$one['logistics_status']]) ? $one['logistics_status'] : $logisticsStatusList1688[$one['logistics_status']];
		}
		/*$data['pagination'] = $pagination;
		$data['orderSource'] = self::getOrderSource();
		$data['orderStatus'] = self::getOrderStatusList();
		$data['payStatus'] = self::getPayStatusList();
		$data['deliveryStatus'] = self::getDeliveryStatusList();
		$data['operatorStatus'] = self::getOperatorStatusList();
		foreach ($data['data'] as $key => $val) {
			$data['data'][$key]['order_status'] = $val['order_status'] ? $data['orderStatus'][$val['order_status']] : '未处理';
			$data['data'][$key]['pay_status'] = $val['pay_status'] ? $data['payStatus'][$val['pay_status']] : '未处理';
			$data['data'][$key]['delivery_status'] = $val['delivery_status'] ? $data['deliveryStatus'][$val['delivery_status']] : '未处理';
			foreach ($data['operatorStatus'] as $ops => $os) {
				if ($val['operator_status'] & $ops) {
				 	$operator_status[$key][] = $os;
				}
			}
			$data['data'][$key]['operator_status'] = empty($operator_status[$key]) ? '未处理' : implode(',', $operator_status[$key]);
			$data['data'][$key]['is_tag'] = OrdOrderHasTag::findAll(['order_id' => $val['order_id']]) ? true : false;
		}*/
		return $data;
	}
	
	static public function savePurchase($data){
		$allStatusInfo=self::getAllStatusInfo();
		$labelStatusMap=array();
		foreach($allStatusInfo as $statusId=>$addInfo){
			$labelStatusMap[$addInfo["label"]]=$statusId;
		}
		$data["payment_status"] = $labelStatusMap[$data["payment_status"]];
		$data["amount"] = $data["amount_subtotal"] + $data["delivery_fee"];// - refund 
		if (!isset($data["delivery_method"]) || trim($data['delivery_method'])=='')  $data["delivery_method"]=0;
		else{
			$data["delivery_method"]=PurchaseShippingHelper::getShippingModeIdByName( $data["delivery_method"]);
		}
		if(!empty($data['purchase_order_id'])){
			$model = Purchase::findOne(['purchase_order_id'=>$data['purchase_order_id']]);
			$model->setAttributes($data);
		}else{
			$model = new Purchase();
			$model->purchase_order_id="pu0001";
			$model->warehouse_id=$data['warehouse_id'];
			$model->status=1;
		}
		$model->save();
		var_dump($model);
	}
	
	public static function getStatusAllInfo($status_id='',$type="order")
	{
		$PURCHASE_STATUS=array();
	
		$allStatusInfo=self::getAllStatusInfo();
		$statusOperationMap=array();
		foreach($allStatusInfo as $statusId=>$addInfo){
			if ($type=="all")	$PURCHASE_STATUS[$statusId]=$addInfo;
			else if ($type==$addInfo["type"]){
				$PURCHASE_STATUS[$statusId]=$addInfo;
			}
		}
			
		//when there is status id passed, return all possible values
		if ($status_id == '')
			$return_value = $PURCHASE_STATUS;
		else{//when there is status_id passed, return the exactly one
			if (!isset($PURCHASE_STATUS["$status_id"]))
				$return_value = "(未定义)";
			else
				$return_value = $PURCHASE_STATUS["$status_id"];
		}
		return 	$return_value;
	}

	/**
	 * 保存Purchase（create,update）
	 * @param 		$model
	 * @param 		$data
	 * @reuturn 	array
	 */
	public static function updatePurchaseOrder($model,$data)
	{
		$edit_log = '';
		$message = '';
		if (!isset($data["type"])) $type="order"; else $type=$data["type"]; // 采购单或者计划---- order or plan
		if (!isset($data['ignoreUnactiveSupplier'])) $ignoreUnactiveSupplierFlag=0; //是否允许采购计划或采购单指向unactive的供应商
		else $ignoreUnactiveSupplierFlag=$data['ignoreUnactiveSupplier'];
	
		if ( !isset($data['warehouse_id']) or empty($data['warehouse_id']) ) $data['warehouse_id']=0;
		if(empty($data['warehouse_id']))
		    $new_wh_id = 0;
		else
		    $new_wh_id=(int)$data['warehouse_id'];//新仓库ID
		$purchaseFormNo=$data['purchase_order_id']; //gongying34000003
		$purchaseSourceNo=$data['purchase_source_id']; //gongying34000003
		$purchaseSupplierName=$data['supplier_name'];
		if(Supplier::findOne(['name'=>$purchaseSupplierName])<>null){
			$purchaseSupplierId=Supplier::findOne(['name'=>$purchaseSupplierName])->supplier_id;
		}else{
			$purchaseSupplierId= SupplierHelper::getSupplierId($purchaseSupplierName,true);
		}
	
		//$paymentStatus=PurchaseHelper::PURCHASE_PAID;//暂时设置所有采购单付款状态为已付款
		//$data['payment_status']=$paymentStatus;
	
		//check if the purchaseFormNo exists or not
		if ($type=="order") {
			$Purchase = Purchase::findOne(['purchase_order_id'=>$purchaseFormNo]);
			if (empty($Purchase)){
				return array('success'=>false,'message'=>TranslateHelper::t("采购单号 不经存在，请刷新数据！"));
			}
		}
		if(isset($data['prod']))
			$productsInfo=$data['prod'];
		
		if(isset($productsInfo)){
			$skuList=array();
			foreach($productsInfo as $productInfo)	{
				$skuList[]=(string)trim($productInfo['sku']);
			}
		
			if ($ignoreUnactiveSupplierFlag==0){
				list($unactiveSupplierIds,$unactiveSupplierNames)=SupplierHelper::checkSupplierNameArrStatus(array($purchaseSupplierName));
					
				if (count($unactiveSupplierIds)>=1) {
					//存在已经停用的供应商。
					$message .=TranslateHelper::t("存在已经停用的供应商:").implode(",",$unactiveSupplierNames);
					return array('success'=>false,'message'=>$message);
				}
			}
			
			//在创建采购单的时候，有的产品可能不是属于指定的仓库，那么需要先在数据库中建立产品和该仓库的关系
			if ($type=="order")	self::_preHandleNewStockProd($skuList,$new_wh_id);
		} 
		$prodSupplier = Supplier::findOne(['name'=>$purchaseSupplierName]);
		$data['supplier_id']=($prodSupplier<>null)?$prodSupplier->supplier_id : SupplierHelper::getSupplierId($purchaseSupplierName,true);
	
		$amountSubtotal=0;	  // without  deliveryFee
		if(isset($productsInfo)){
			foreach($productsInfo as $productInfo)	{
				$amountSubtotal=$amountSubtotal+$productInfo['amount'];
			}
		}
		$data=array_filter($data);
		if (!isset($data["delivery_fee"]) || trim($data["delivery_fee"])=='')  $data["delivery_fee"]=0;
		if (!isset($data["delivery_method"]) || trim($data['delivery_method'])=='')  $data["delivery_method"]=0;
		else{
			$data["delivery_method"]=PurchaseShippingHelper::getShippingModeIdByName( $data["delivery_method"]);
		}
	
		$data['amount_subtotal'] = $amountSubtotal;
		$data['amount'] = $amountSubtotal+$data["delivery_fee"];
		$data['update_time'] = TimeUtil::getNow();

		$comment = (isset($data['comment']))?trim($data['comment']):'';
		if ($comment<>"") {
			$comment = "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ ".TimeUtil::getNow().TranslateHelper::t("添加备注：")."<br>".$comment."</font><br>".$model->comment;
			$data['comment']=$comment;
		}else {
			$data['comment']=$model->comment;
		}
		/*
		if(isset($data['pay_date'])) $data['pay_date'] = trim($data['pay_date']);
		else $data['pay_date']='';
		if($data['payment_status']!==2) $data['pay_date'] ='';
		*/
		$old_wh_id = (empty($model->warehouse_id))?0:$model->warehouse_id;
		//$new_wh_id = $data['warehouse_id'];
		$model->setAttributes($data);
		$model->warehouse_id=$new_wh_id;

		if (!$model->save()) {
			//SysLogHelper::SysLog_Create("Purchase", __CLASS__,__FUNCTION__,""," purchaseObject->save() ".print_r($purchaseObject->errors,true) ,"Error");
			//purchaseObject->errors 貌似只能print_r的方式输出
			foreach ($model->errors as $k => $anError){
				$message .= "<br>". $k.":".$anError[0];
			}
			return array('success'=>false,'message'=>TranslateHelper::t("采购单更新失败！ Error:updatepurchase001.").$message);
		}
		
		//用户修改了仓库
		if($old_wh_id!==$new_wh_id){
			$old_wh_name = '';
			$new_wh_name = '';
			$warehouses = Warehouse::find()->where(['warehouse_id' => [$old_wh_id, $new_wh_id]])->asArray()->all();
			foreach ($warehouses as $warehouse){
				if($warehouse['warehouse_id'] == $old_wh_id){
					$old_wh_name = $warehouse['name'];
				}
				if($warehouse['warehouse_id'] == $new_wh_id){
					$new_wh_name = $warehouse['name'];
				}
			}
			$edit_log .= '仓库从" '.$old_wh_name.' "改为" '.$new_wh_name.' ", ';
		}
		
		$purchaseId=$model->id;
		/*平台采购单由用户自定义，此步跳过
		$purchaseObject->purchase_order_id=self::formatSequenceId($purchaseId,"PO");
		$purchaseObject->save(); // 设置采购订单号  PO00003,   由自动生成的id加上前缀 。所以对model需要先保存再update
		*/
		if(isset($productsInfo)){
			foreach($productsInfo as &$productInfo) {
				$productInfo['sku']=(string)trim($productInfo['sku']);
				$itemModel=PurchaseItems::findOne(['purchase_id'=>$purchaseId,'sku'=>$productInfo['sku']]);
				if(empty($itemModel)){
					$itemModel = new PurchaseItems();
					$oldQty=0;
				}else{
					$oldQty=$itemModel->qty;
				}
				$itemModel->purchase_id=$purchaseId;
				$itemModel->amount=$productInfo['amount'];
				$itemModel->sku=$productInfo['sku'];
				$itemModel->qty=$productInfo['qty'];
				$itemModel->supplier_id=$purchaseSupplierId; //之前的版本每个产品都可以指定不同的供应商，这里为了兼容，也指定下。
				$itemModel->supplier_name=$purchaseSupplierName;
				$itemModel->price=empty($itemModel->qty) ? 0 : round($itemModel->amount/$itemModel->qty,2);
				$itemModel->name=$productInfo['name'];
				$itemModel->remark=$productInfo['remark'];
				if ($itemModel->save()) {
					if ($type=="order"){
						//更新产品在仓库中的在途数量
						if($old_wh_id!==$new_wh_id){//用户修改了仓库
							InventoryHelper::modifyProductStockage($productInfo['sku'],$old_wh_id,-$oldQty,0,0,0,$itemModel->price,0);
							InventoryHelper::modifyProductStockage($productInfo['sku'],$new_wh_id,$productInfo['qty'],0,0,0,$itemModel->price,0);
						}else{
							InventoryHelper::modifyProductStockage($productInfo['sku'],$new_wh_id,$productInfo['qty']-$oldQty,0,0,0,$itemModel->price,0);
						}
						
						//记录操作日志
						if($productInfo['qty'] != $oldQty){
							$edit_log .= '商品" '.$productInfo['sku'].' "数量从" '.$oldQty.' "改为" '.$productInfo['qty'].' "; ';
						}
						
						//更新产品的供应商信息---supplierid和价格
						//SupplierHelper::updateProductSupplierInfo($productInfo['sku'], $supplierId, $purchaseItemObject->price);
						SupplierHelper::updateProductSupplierInfo($productInfo['sku'], $purchaseSupplierId, $itemModel->price);
							
						//将该产品的采购建议设置为dirty
						/*待处理
						PurchaseSugHelper::changePurchaseSugDirtyBySku($productInfo['sku']);
						*/
					}
				}else {
					foreach ($itemModel->errors as $k => $anError){
						$message .= "<br>". $k.":".$anError[0];
					}
					return array('success'=>false,'message'=>TranslateHelper::t("采购单明细更新失败！ Error:savepurchaseitems001.").$message);
					//SysLogHelper::SysLog_Create("Purchase", __CLASS__,__FUNCTION__,""," purchaseItemObject->save".print_r($purchaseItemObject->errors,true) ,"Error");
				}
			}
		}
		
		//写入操作日志
		if(!empty($edit_log)){
			$edit_log = '修改采购单, '.$model->purchase_order_id.', '.$edit_log;
			UserHelper::insertUserOperationLog('purchase', $edit_log, null, $model->id);
		}
		
		//记录新建采购单的操作
		if ($type=="plan") OperationLogHelper::log("purchase", $model->purchase_order_id, "修改采购计划","",\Yii::$app->user->identity->getFullName()."@".TimeUtil::getNow());
		else {
			OperationLogHelper::log("purchase", $model->purchase_order_id, "修改采购单","",\Yii::$app->user->identity->getFullName()."@".TimeUtil::getNow());
			/*等待财务模块对接
			//对于已经付款的采购单进行财务相关记录,同时会插入付款的操作日志---------20140711所有的新增采购单都作为没付款处理
			if ($purchaseFormInfoReq['payment_status']==PurchaseHelper::PURCHASE_PAID){
				$data=array();
				$data["related_purchase_id"]=$purchaseObject->id;
				$data["pay_date"]=$purchaseObject->create_time;
				$data["type"]='P';
				$data["currency"]='CNY';
				$data["amount"]=$purchaseObject->amount;
				$data["pay_to_person"]=$purchaseObject->supplier_name;
				SysLogHelper::SysLog_Create("Purchase", __CLASS__,__FUNCTION__,"","FinanceHelper::createTransactionRecord(data) ---- ".CJSON::encode($data) ,"Debug");
				FinanceHelper::createTransactionRecord($data);
			}*/
		}

		return array('success'=>true,'message'=>"");
	
	}
	
	/**
	 * 返回采购状态id和名称的对应关系
	 * @param $status_id
	 * @param $type  ---order,plan,all。这里order表示只涉及采购单相关状态信息，plan表示只涉及采购计划相关的状态信息，all表示所有信息
	 * @reuturn  array(status_id=>label,....)
	 */
	public static function getPurchaseStatus($status_id='',$type="order")
	{
		$PURCHASE_STATUS=array();
	
		if($status_id<>'')  $type="all";
	
		$allStatusInfo=self::getAllStatusInfo();
		$statusOperationMap=array();
		foreach($allStatusInfo as $statusId=>$addInfo){
			if ($type=="all")	$PURCHASE_STATUS[$statusId]=$addInfo["label"];
			else if ($type==$addInfo["type"]){
				$PURCHASE_STATUS[$statusId]=$addInfo["label"];
			}
		}
			
		//when there is status id passed, return all possible values
		if ($status_id == '')
			$return_value = $PURCHASE_STATUS;
		else{//when there is status_id passed, return the exactly one
			if (!isset($PURCHASE_STATUS["$status_id"]))
				$return_value = "(未定义)";
			else
				$return_value = $PURCHASE_STATUS["$status_id"];
		}
		return 	$return_value;
	}
	
	/**
	 * 获取状态和可操作行为的对应关系
	 * @param $status
	 * @reuturn
	 * $status为''时候，返回所有对应关系；否则返回该状态对应的可操作行为的名称
	 */
	public static function getStatusOperation($status='') {
		/*	$statusOperationMap=array(
		 self::WAIT_FOR_ARRIVAL=>"view,edit,cancel,arrival,print",
				self::WAIT_FOR_PARTIAL_ARRIVAL=>"view,edit,cancel_left,arrival,arrival_record,print",
				self::ALL_ARRIVED =>"view,arrival_record,print",
				self::PARTIAL_ARRIVED_CANCEL_LEFT=>"view,arrival_record,print",
				self::CANCELED=>"view",
				self::DRAFT=>"view,edit",
				self::PLAN_WAIT_FOR_APPROVAL=>"view",
				self::REJECT_WAIT_FOR_MODIFY=>"view,edit",
	
		);*/
		$allStatusInfo=self::getAllStatusInfo();
		$statusOperationMap=array();
		foreach($allStatusInfo as $statusId=>$addInfo){
			$statusOperationMap[$statusId]=$addInfo["operation"];
		}
	
		if ($status=="")	{	return $statusOperationMap;	}
	
		return $statusOperationMap[$status];
	}
	
	/**
	 * 获取所有货款状态id和名称的对应关系
	 * @param $status_id
	 * @reuturn
	 *   $status_id为''时候，返回所有状态；否则返回，该id对应的状态名称
	 */
	public static function getPaymentStatus($status_id='')
	{	//when there is status id passed, return all possible values
	if ($status_id == '')
		$return_value = self::$PAYMENT_STATUS;
	else{//when there is status_id passed, return the exactly one
		if (!isset(self::$PAYMENT_STATUS["$status_id"]))
			$return_value = "(未定义)";
		else
			$return_value = self::$PAYMENT_STATUS["$status_id"];
	}
	return 	$return_value;
	}
	
	/**
	 * 获取所有仓库的信息
	 * @param $warehouseId,$skuList
	 * @reuturn map.  array(id1=>array("name"=>"shanghai"),id2=>)
	 */
	public static function getAllWarehouseInfo()
	{
		if (count(self::$warehouseInfo)>0) return self::$warehouseInfo;
		$warehouses=Warehouse::find()->asArray()->all();
		foreach($warehouses as $warehouse)
		{
			$infoArr=array();
			$infoArr["name"]=$warehouse['name'];
			self::$warehouseInfo[$warehouse['warehouse_id']]=$infoArr;
		}
	
		return self::$warehouseInfo;
	}
	/**
	 * 获取所有仓库的id和name的map
	 * @param $warehouseId,$skuList
	 * @reuturn map.  array(id1=>name1,id2=>name2.....)
	 */
	public static function getWarehouseIdNameMap()
	{
		$warehouseIdNameMap=array();
		$warehouseInfoArr=self::getAllWarehouseInfo();
		foreach($warehouseInfoArr as $id=>$info)	$warehouseIdNameMap[$id]=$info['name'];
		return $warehouseIdNameMap;
	}
	
	
	/**
	 * 从"采购建议"进入"新建采购单"页面,需要获取已选择产品的信息.   这里会exclude那些不属于指定$warehouseId的sku！！！！！！
	 * @param $warehouseId,$skuList
	 * @return more information for those products in array
	 */
	public static function getPurchaseFormInfoFromSku($warehouseId,$skuList=array())
	{
		if (count($skuList)==0)  return false;
	
		$retArr=array();
		$productTopSupplierInfo=SupplierHelper::getProductTopSupplierInfo();
		$allWarehouseInfo=self::getAllWarehouseInfo();
	
		//$rows=Yii::app()->subdb->createCommand('SELECT ps.sku as sku,wh.name as name,ps.purchase_price as purchase_price FROM wh_product_stock where  ')->queryAll();
		//$rows = Yii::app()->subdb->createCommand()->select('*')->from('wh_product_stock')->where(array('in', 'prod_stock_id', $idsArr))->queryAll();

		$productStockCollection=ProductStock::find()->select("sku,prod_stock_id,qty_ordered,qty_in_stock,qty_purchased_coming,warehouse_id")
			->where(['in','sku', $skuList])
			->andWhere(['warehouse_id'=>$warehouseId])
			->asArray()
			->all();

		// Yii::log("getPurchaseFormInfoFromSku productStockCollection:".print_r($productStockCollection,true),"info","");
		foreach($productStockCollection as $productStock){
			//Yii::log("getPurchaseFormInfoFromSku attributes:".print_r($productStock->attributes,true),"info","");
			 
			$tempArr=$productStock->attributes;
			 
			$productObject=Product::model()->findByPk($productStock['sku']);
			$tempArr['photo_primary']=$productObject->photo_primary;
			$tempArr['name']=$productObject->name;
			 
			if (!isset($productTopSupplierInfo[$productStock['sku']])) $supplierInfo=array("name"=>"notchosen","purchase_price"=>"0","supplier_id"=>"-1");
			else $supplierInfo=$productTopSupplierInfo[$productStock['sku']];
			$tempArr['purchase_price']=$supplierInfo['purchase_price'];
			$tempArr['latest_price']=$supplierInfo['purchase_price']; //供应商对应的最新报价
			 
			$needPurchaseNumber=$productStock['qty_ordered']-$productStock['qty_in_stock']-$productStock['qty_purchased_coming'];
			if ($needPurchaseNumber<0) $needPurchaseNumber=0;
			$tempArr['number']=$needPurchaseNumber;
			$tempArr['row_total']=$tempArr['purchase_price']*$tempArr['number'];
	
			$tempArr['supplier_name']=$supplierInfo['name'];
			$tempArr['supplier_id']=$supplierInfo['supplier_id'];
			 
			$tempArr['warehouse_name']=$allWarehouseInfo[$warehouseId]['name'];
			//$tempArr['warehouse_id']=$warehouseId;
			 
			$retArr[]=$tempArr;
		}
		return $retArr;
	}
	/*
	// id 为 3 转化为  PO00003
	public static function formatSequenceId($id,$prefix) {
		$sequenceId=$prefix;
		if ($id<10) 	$sequenceId=$sequenceId."00000".$id;
		else if ($id<100) 	$sequenceId=$sequenceId."0000".$id;
		else if ($id<1000) 	$sequenceId=$sequenceId."000".$id;
		else if ($id<10000) 	$sequenceId=$sequenceId."00".$id;
		else if ($id<100000) 	$sequenceId=$sequenceId."0".$id;
	
		return $sequenceId;
	}
	*/
	/**自动生成编号最新的采购单
	 * 例如：
	 * 		如果上次自动生成的采购单，id=3,purchase_order_id=PO000003;
	 * 		自动生成的采购单期望为  PO000004
	 */
	public static function formatSequenceId($prefix) {
		$sequenceId=$prefix;
		$query = Purchase::find()->select("purchase_order_id")->where("purchase_order_id  REGEXP '^PO[0-9a-zA-Z]+$' ");
		$query->orderBy("id DESC");
		$last_auto_order = $query->one();
		if(empty($last_auto_order)){
			$sequenceId=$sequenceId."000001";
		}else{
			$last_auto_order = $last_auto_order->purchase_order_id;
			$orderNum = substr($last_auto_order, 2);
			if(empty($orderNum)) $orderNum=0;
			$orderNum = intval($orderNum);
			$orderNum_new = $orderNum+1;
			
			if ($orderNum_new<10) 	$sequenceId=$sequenceId."00000".$orderNum_new;
			else if ($orderNum_new<100) 	$sequenceId=$sequenceId."0000".$orderNum_new;
			else if ($orderNum_new<1000) 	$sequenceId=$sequenceId."000".$orderNum_new;
			else if ($orderNum_new<10000) 	$sequenceId=$sequenceId."00".$orderNum_new;
			else if ($orderNum_new<100000) 	$sequenceId=$sequenceId."0".$$orderNum_new;
			else $sequenceId=$sequenceId.$orderNum_new;
		}
		return $sequenceId;
	}
	
	/**
	 * needCommit-- 修改并提审  0 or 1
	 */
	public static function updateOnePurchasePlanForm($purchaseFormInfoReq,$needCommit=0) {
	
		Yii::log("updateOnePurchasePlanForm ","info","");
	
		$purchaseId=$purchaseFormInfoReq["id"];
		$purchaseObject=Purchase::model()->findByPk($purchaseId);
	
		$productsInfoJson=$purchaseFormInfoReq['productsinfo'];
		Yii::log("updateOnePurchasePlanForm productinfo:".$productsInfoJson,"info","");
		$productsInfo=json_decode($productsInfoJson);
	
		//1. delete all the items for purchaseid
		Yii::app()->subdb->createCommand("delete  from pc_purchase_items where purchase_id=:purchase_id")
		->bindParam(":purchase_id",$purchaseId,\PDO::PARAM_STR)->execute();
	
		//2.update purchase header database table
		$amountSubtotal=0;	  // without  deliveryFee
		foreach($productsInfo as $productInfo)	{
			$amountSubtotal=$amountSubtotal+$productInfo['row_total'];
		}
	
		$purchaseObject->amount_subtotal=$amountSubtotal;
		$purchaseObject->amount=$amountSubtotal;
		$purchaseObject->create_time=$purchaseFormInfoReq["create_time"];
	
	
		//change the info of the plan
		if ($needCommit==1){
			$purchaseObject->status=self::PLAN_WAIT_FOR_APPROVAL;
			$purchaseObject->reject_reason = '';
		}
		//SysLogHelper::SysLog_Create("Purchase", __CLASS__,__FUNCTION__,""," plan purchaseObject->save  :".CJSON::encode($purchaseObject->attributes) ,"Error");
	
		//Yii::log("updateOnePurchasePlanForm "." plan purchaseObject->save  :".CJSON::encode($purchaseObject->attributes) ,"info","");
		if (!$purchaseObject->save()){
			return array(false,"采购单计划失败！ Error:updatepurchaseplan001 -- 单号:".$purchaseObject->purchase_order_id);
		}
	
		//3. insert purchase items
		$purchaseSupplierId=$purchaseObject->supplier_id;
		$purchaseSupplierName=$purchaseObject->supplier_name;
		foreach($productsInfo as &$productInfo) {
			$productInfo['sku']=(string)trim($productInfo['sku']);
			//	Yii::log("+++++ productInfo +++++" ,"info","");
			$purchaseItemObject=new PurchaseItems;
			$purchaseItemObject->purchase_id=$purchaseId;
			$purchaseItemObject->amount=$productInfo['row_total'];
			$purchaseItemObject->sku=$productInfo['sku'];
			$purchaseItemObject->qty=$productInfo['number'];
			$purchaseItemObject->supplier_id=$purchaseSupplierId; //之前的版本每个产品都可以指定不同的供应商，这里为了兼容，也指定下。
			$purchaseItemObject->supplier_name=$purchaseSupplierName;
			$purchaseItemObject->price=round($purchaseItemObject->amount/$purchaseItemObject->qty,2);
			$purchaseItemObject->name=Product::model()->findByPk($productInfo['sku'])->name;
				
			//	Yii::log("purchaseItemObject->save   ---  purchaseItemObject:".CJSON::encode($purchaseItemObject->attributes)." namelen:".strlen(Product::model()->findByPk($productInfo['sku'])->name) ,"info","");
				
			if ($purchaseItemObject->save()) {
			}else {
				//Yii::log("purchaseItemObject->save   fail :".$purchaseItemObject->getErrors() ,"info","");
				//SysLogHelper::SysLog_Create("Purchase", __CLASS__,__FUNCTION__,""," purchaseItemObject->save fail  ---  purchaseItemObject:".CJSON::encode($purchaseItemObject->attributes) ,"Error");
				return array(false,"采购计划保存失败！ Error:updatepurchaseplan002 -- 单号:".$purchaseObject->purchase_order_id);
			}
		}
	
		//记录修改采购计划的操作
		OperationLogHelper::saveOperationLog("purchase", $purchaseObject->purchase_order_id, "修改采购计划");
	
		return array(true,"");
			
	}
	
	/**
	 * 新建1张采购单
	 * @param $purchaseFormInfoReq----
	 * 	$purchaseObject->warehouse_id=$warehouseId;
	 $purchaseObject->purchase_id=$purchaseFormNo;
	 $purchaseObject->supplier_id=$supplierId;
	 $purchaseObject->delivery_fee=$deliveryFee;
	 $purchaseObject->delivery_method=$deliveryMethod;
	 $purchaseObject->delivery_number=$deliveryNumber;
	 $purchaseObject->expected_arrival_date=$expectedDeliveryDate;
	 $purchaseObject->payment_status=$paymentStatus;
	 $purchaseObject->create_time=TimeUtil::getNow();
	 * @return true or false
	 */
	//	public static function generateOnePurchaseForm($warehouseId,$purchaseFormNo,$paymentStatus,$supplierId,$deliveryFee,$deliveryMethod,$deliveryNumber,$expectedDeliveryDate,$productsInfo,$comment="")
	public static function generateOnePurchaseForm($purchaseFormInfoReq,$productsInfo) {
	
		if (!isset($purchaseFormInfoReq["type"])) $type="order"; else $type=$purchaseFormInfoReq["type"]; // 采购单或者计划---- order or plan
		if (!isset($purchaseFormInfoReq["iscommit-check"])) $commitCheck=0; else $commitCheck=$purchaseFormInfoReq["iscommit-check"]; // 保存的时候是否同时提交审核 0 or 1
	
		$purchaseSupplierName=$purchaseFormInfoReq['supplier_name'];
		if(Supplier::find()->where(['name'=>$purchaseSupplierName])->one()<>null){
			$purchaseSupplierId=Supplier::find()->where(['name'=>$purchaseSupplierName])->one()->supplier_id;
		}else{
			$purchaseSupplierId=0;	//等待新建supplier接口，这里hardcode 0；
		}
		$amountSubtotal=0;	  // without  deliveryFee
		foreach($productsInfo as $productInfo)	{
			$amountSubtotal=$amountSubtotal+$productInfo['amount'];
		}
	
		$purchaseSourceId=$purchaseFormInfoReq["purchase_source_id"]; //供应商单号
		$warehouseId=$purchaseFormInfoReq["warehouse_id"];
	
		$purchaseObject=new Purchase;
		$purchaseFormInfoReq=array_filter($purchaseFormInfoReq);
		if (!isset($purchaseFormInfoReq["delivery_fee"]) || trim($purchaseFormInfoReq["delivery_fee"])=='')  $purchaseFormInfoReq["delivery_fee"]=0;
		if (!isset($purchaseFormInfoReq["delivery_method"]) || trim($purchaseFormInfoReq['delivery_method'])=='')  $purchaseFormInfoReq["delivery_method"]=0;
		$purchaseFormInfoReq["supplier_id"]=$purchaseSupplierId;
		
		if(empty($purchaseFormInfoReq['warehouse_id']))
		    $purchaseFormInfoReq['warehouse_id'] = $warehouseId;
	
		if ($type=="plan") {
			$purchaseObject->payment_status=self::PURCHASE_NOT_REQUIRED;//采购计划不需要付款
			if ($commitCheck==0) $purchaseObject->status=self::DRAFT; else $purchaseObject->status=self::PLAN_WAIT_FOR_APPROVAL;
				
		} else $purchaseObject->status=self::WAIT_FOR_ARRIVAL;
		
		if(empty($purchaseFormInfoReq['create_time']))
			$purchaseFormInfoReq['create_time'] = TimeUtil::getNow();


		if(empty($purchaseFormInfoReq['capture_user_name']))
			$purchaseFormInfoReq['capture_user_name']= \Yii::$app->user->identity->getFullName();
		if(isset($purchaseFormInfoReq['pay_date'])) $purchaseFormInfoReq['pay_date'] = trim($purchaseFormInfoReq['pay_date']);
		else $purchaseFormInfoReq['pay_date']='';
		if($purchaseFormInfoReq['payment_status']!==2) $purchaseFormInfoReq['pay_date'] ='';
		
		//$purchaseObject->setAttributes($purchaseFormInfoReq);
		$purchaseObject->attributes=$purchaseFormInfoReq;
	
		//$purchaseObject->capture_user_id=Yii::app()->muser->getId();
		$purchaseObject->supplier_name=$purchaseSupplierName;
		$purchaseObject->amount_subtotal=$amountSubtotal;		// without  deliveryFee
		$purchaseObject->amount=$amountSubtotal+$purchaseFormInfoReq["delivery_fee"];
		$purchaseObject->update_time=$purchaseObject->create_time;
		$purchaseObject->is_arrive_goods='N';
		$purchaseObject->is_pending_check='N';
		$purchaseObject->is_refunded='N';
	
	
		$comment=(isset($purchaseObject->comment))?trim($purchaseObject->comment):'';
		if ($comment<>"") {
			$comment = "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ ".TimeUtil::getNow()."</font><br>".$comment;
			$purchaseObject->comment=$comment;
		} 	else {
			$purchaseObject->comment="";
		}
		$message ='';
		if (!$purchaseObject->save()) {
			//SysLogHelper::SysLog_Create("Purchase", __CLASS__,__FUNCTION__,""," purchaseObject->save() ".print_r($purchaseObject->errors,true) ,"Error");
			//purchaseObject->errors 貌似只能print_r的方式输出
			foreach ($purchaseObject->errors as $k => $anError){
				$message .= "<br>". $k.":".$anError[0];
			}
			return array('success'=>false,'message'=>TranslateHelper::t("采购单保存失败！ Error:savepurchase001.").$message);
		}
		
		//写入操作日志
		UserHelper::insertUserOperationLog('purchase', "新建采购单, ".$purchaseObject->purchase_order_id, null, $purchaseObject->id);
	
		$purchaseId=$purchaseObject->id;
		/*平台采购单由用户自定义，此步跳过
		$purchaseObject->purchase_order_id=self::formatSequenceId($purchaseId,"PO");
		$purchaseObject->save(); // 设置采购订单号  PO00003,   由自动生成的id加上前缀 。所以对model需要先保存再update
		*/
		foreach($productsInfo as &$productInfo) {
			$productInfo['sku']=(string)trim($productInfo['sku']);
			$purchaseItemObject=new PurchaseItems;
			$purchaseItemObject->purchase_id=$purchaseId;
			$purchaseItemObject->amount=$productInfo['amount'];
			$purchaseItemObject->sku=$productInfo['sku'];
			$purchaseItemObject->qty=$productInfo['qty'];
			/*$supplierId=$productInfo['supplier_id'];
				$purchaseItemObject->supplier_id=$supplierId;
			$purchaseItemObject->supplier_name=Supplier::model()->findByPk($supplierId)->name; */
			$purchaseItemObject->supplier_id=$purchaseSupplierId; //之前的版本每个产品都可以指定不同的供应商，这里为了兼容，也指定下。
			$purchaseItemObject->supplier_name=$purchaseSupplierName;
			$purchaseItemObject->price=empty($purchaseItemObject->qty) ? 0 : round($purchaseItemObject->amount/$purchaseItemObject->qty,2);
			$purchaseItemObject->name=$productInfo['name'];
			$purchaseItemObject->remark=$productInfo['remark'];
			if ($purchaseItemObject->save()) {
				if ($type=="order"){
					//更新产品在仓库中的在途数量
					InventoryHelper::modifyProductStockage($productInfo['sku'],$warehouseId,$productInfo['qty'],0,0,0,$purchaseItemObject->price,0);
					//更新产品的供应商信息---supplierid和价格
					//SupplierHelper::updateProductSupplierInfo($productInfo['sku'], $supplierId, $purchaseItemObject->price);
					SupplierHelper::updateProductSupplierInfo($productInfo['sku'], $purchaseSupplierId, $purchaseItemObject->price);
						
					//将该产品的采购建议设置为dirty
					/*待处理
					PurchaseSugHelper::changePurchaseSugDirtyBySku($productInfo['sku']);
					*/
				}
			}else {
				foreach ($purchaseItemObject->errors as $k => $anError){
					$message .= "<br>". $k.":".$anError[0];
				}
				return array('success'=>false,'message'=>TranslateHelper::t("采购单明细保存失败！ Error:savepurchaseitems001.").$message);
				//SysLogHelper::SysLog_Create("Purchase", __CLASS__,__FUNCTION__,""," purchaseItemObject->save".print_r($purchaseItemObject->errors,true) ,"Error");
			}
		}
	
		//记录新建采购单的操作
		if ($type=="plan") OperationLogHelper::log("purchase", $purchaseObject->purchase_order_id, "新增采购计划","",\Yii::$app->user->identity->getFullName()."@".TimeUtil::getNow());
		else {
			OperationLogHelper::log("purchase", $purchaseObject->purchase_order_id, "新增采购单","",\Yii::$app->user->identity->getFullName()."@".TimeUtil::getNow());
			/*等待财务模块对接
			//对于已经付款的采购单进行财务相关记录,同时会插入付款的操作日志---------20140711所有的新增采购单都作为没付款处理
			if ($purchaseFormInfoReq['payment_status']==PurchaseHelper::PURCHASE_PAID){
				$data=array();
				$data["related_purchase_id"]=$purchaseObject->id;
				$data["pay_date"]=$purchaseObject->create_time;
				$data["type"]='P';
				$data["currency"]='CNY';
				$data["amount"]=$purchaseObject->amount;
				$data["pay_to_person"]=$purchaseObject->supplier_name;
				SysLogHelper::SysLog_Create("Purchase", __CLASS__,__FUNCTION__,"","FinanceHelper::createTransactionRecord(data) ---- ".CJSON::encode($data) ,"Debug");
				FinanceHelper::createTransactionRecord($data);
			}*/
		}
		return array('success'=>true,'message'=>"");
	}
	
	/**
	 * 采购计划审核通过之后，变成采购单
	 */
	public static function planTranferToPurchase($purchaseId){
		$purchaseObject=Purchase::model()->findByPk($purchaseId);
		$purchaseObject->status=PurchaseHelper::WAIT_FOR_ARRIVAL;
		$purchaseObject->payment_status=PurchaseHelper::PURCHASE_UNPAID;
	
		if (!$purchaseObject->save()){
			//SysLogHelper::SysLog_Create("Purchase", __CLASS__,__FUNCTION__,""," purchaseObject->save() ".print_r($purchaseObject->errors,true) ,"Error");
			return false;
		}
		$purchaseItems=PurchaseItems::model()->findAll("purchase_id=:purchase_id",	array(':purchase_id'=>$purchaseId) );
		if ($purchaseItems<>null) {
			foreach($purchaseItems as $purchaseItemObject){
				//更新产品在仓库中的在途数量
				InventoryHelper::modifyProductStockage($purchaseItemObject->sku,$purchaseObject->warehouse_id,$purchaseItemObject->qty,0,0,0,$purchaseItemObject->price,0);
				//更新产品的供应商信息---supplierid和价格
				//SupplierHelper::updateProductSupplierInfo($productInfo['sku'], $supplierId, $purchaseItemObject->price);
				SupplierHelper::updateProductSupplierInfo($purchaseItemObject->sku,$purchaseItemObject->supplier_id, $purchaseItemObject->price);
	
			}
		}
		OperationLogHelper::log("purchase", $purchaseObject->purchase_order_id, "采购计划审核通过","",\Yii::$app->user->identity->getFullName()."@".TimeUtil::getNow());
		return true;
			
	}
	
	
	/**
	 * 判断是否所有$skuList的sku都属于该warehouseid,返回不属于该参考的sku集合
	 * @param $skuList,$warehouseId
	 * @return
	 */
	public static function checkSkuFromWarehouse($skuList,$warehouseId)
	{
		/*
		$rows = Yii::$app->get('subdb')->createCommand()
		->select('sku')
		->from('wh_product_stock u')
		->where(array('and', 'warehouse_id='.$warehouseId, array('in', 'sku',$skuList)))
		->queryAll();
		*/
		$rows=ProductStock::find()->where(['warehouse_id'=>$warehouseId])->andWhere(['in','sku',$skuList])->all();
	
		$retSkuList=array();
		foreach($rows as $row) $retSkuList[] = strtoupper(trim($row['sku']));
		foreach($skuList as &$sku) $sku = strtoupper(trim($sku));
		//$queryStr='SELECT sku FROM pc_purchase WHERE warehouse_id='.$warehouseId.' AND sku IN('.$skuListStr.')';
	
		$diffSkuArr=array_diff($skuList,$retSkuList);
		//foreach($diffArr as $sku)  Yii::log("checkSkuFromWarehouse diffArrsku:".$sku,"info","");
		return $diffSkuArr;
		//	$rows=Yii::app()->subdb->createCommand()->queryAll();
	}
	
	/**
	 * 在创建采购单的时候，有的产品可能不是属于指定的仓库，那么需要先在数据库中建立产品和该仓库的关系
	 * @param
	 * @return
	 */
	public static function _preHandleNewStockProd($skuList,$warehouseId)	{
		$newSkuList=self::checkSkuFromWarehouse($skuList,$warehouseId);
		if (count($newSkuList)==0) return;
	
		foreach($newSkuList as $sku) {
			$productStock=new ProductStock;
			$productStock->sku=$sku;
			$productStock->warehouse_id=$warehouseId;
			$productStock->qty_in_stock=0;
			$productStock->qty_purchased_coming=0;
			$productStock->qty_ordered=0;
			$productStock->qty_order_reserved=0;
			$productStock->save(false);
		}
	
	}
	
	/**
	 * Create purchase or purchase plan form according to the requested data
	 * @param 	$purchaseFormInfoReq-----
	 * type:
	 * warehouseid:1,purchaseid:0002  采购单号,shippingmethod:2,shippingcost:34,paymentpaid:on,
	 * productsinfo:[{"sku":"sku0000001","prod_stock_id":1,"purchase_price":"5.00","number":4,"row_total":20,"supplier_name":"华强北品胜","supplier_id":1,"warehouse_name":"shanghai"},..]
	 * @return true or false
	 */
	public static function generatePurchaseForm($purchaseFormInfoReq) {
	
		if (!isset($purchaseFormInfoReq["type"])) $type="order"; else $type=$purchaseFormInfoReq["type"]; // 采购单或者计划---- order or plan
		if (!isset($purchaseFormInfoReq['ignoreUnactiveSupplier'])) $ignoreUnactiveSupplierFlag=0; //是否允许采购计划或采购单指向unactive的供应商
		else $ignoreUnactiveSupplierFlag=$purchaseFormInfoReq['ignoreUnactiveSupplier'];
		
		$warehouseId=empty($purchaseFormInfoReq['warehouse_id'])?0:$purchaseFormInfoReq['warehouse_id'];
		$purchaseFormNo=$purchaseFormInfoReq['purchase_order_id']; //gongying34000003
		$purchaseSourceNo=$purchaseFormInfoReq['purchase_source_id']; //gongying34000003
		$purchaseSupplierNmae=$purchaseFormInfoReq['supplier_name'];
	
		$purchaseFormInfoReq['comment']=trim($purchaseFormInfoReq['comment']);
		//$paymentStatus=PurchaseHelper::PURCHASE_PAID;//暂时设置所有采购单付款状态为已付款
		
		if (!isset($purchaseFormInfoReq["delivery_method"]) || trim($purchaseFormInfoReq['delivery_method'])=='')  $purchaseFormInfoReq["delivery_method"]=0;
		else{
			$purchaseFormInfoReq["delivery_method"]=PurchaseShippingHelper::getShippingModeIdByName( $purchaseFormInfoReq["delivery_method"]);
		}
	
		//check if the purchaseFormNo exists or not
	if ($type=="order") {
			if($purchaseFormNo<>"" && $purchaseSourceNo<>"")
				$Purchase = Purchase::find()->where(['purchase_order_id'=>$purchaseFormNo])->orwhere(['purchase_source_id'=>$purchaseSourceNo])->one();
			elseif($purchaseFormNo<>"")
				$Purchase = Purchase::findOne(['purchase_order_id'=>$purchaseFormNo]);
			elseif($purchaseSourceNo<>"")
				$Purchase = Purchase::findOne(['purchase_source_id'=>$purchaseSourceNo]);
			if ($Purchase<>null){
				return array('success'=>false,'message'=>TranslateHelper::t("供应商单号 or 平台采购单号 已经存在，请修改！"));
			}
		}

		//$purchaseFormInfoReq['payment_status']=$paymentStatus;
	
		/*数据格式变更
		$productsInfoJson=$purchaseFormInfoReq['productsinfo'];
		$productsInfo=CJSON::decode($productsInfoJson);
		*/
		$productsInfo=$purchaseFormInfoReq['prod'];
		
		$skuList=array();
		foreach($productsInfo as $productInfo)	{
			$skuList[]=(string)trim($productInfo['sku']);
		}
	
		if ($ignoreUnactiveSupplierFlag==0){
			list($unactiveSupplierIds,$unactiveSupplierNames)=SupplierHelper::checkSupplierNameArrStatus(array($purchaseSupplierNmae));
				
			if (count($unactiveSupplierIds)>=1) {
				//存在已经停用的供应商,需要用户在前台confirm是继续创建还是换供应商。
				$message=TranslateHelper::t("存在已经停用的供应商:").implode(",",$unactiveSupplierNames);
				return array('success'=>false,'message'=>$message);
			}
		}
	
		//在创建采购单的时候，有的产品可能不是属于指定的仓库，那么需要先在数据库中建立产品和该仓库的关系
		if ($type=="order")	self::_preHandleNewStockProd($skuList,$warehouseId);
		 
		return self::generateOnePurchaseForm($purchaseFormInfoReq,$productsInfo);
	
		 
		/*
			if (count($supplieridsArr)==1)	{
		Yii::log("generatePurchaseForm count(supplieridsArr)==1","info","");
		//If （产品属于同一个供应商） then  生成一张采购单。
		$purchaseFormInfoReq['supplier_id']=$supplieridsArr[0];
		return self::generateOnePurchaseForm($purchaseFormInfoReq,$productsInfo);
		}else {
		//    If   (选择已经付款)  then 提示用户，该采购单的总体供应商是。。。然后 生成一张采购单
		//    Else  提示用户，“由于财务需要，系统会拆分采购单。。。”，用户选择“ok”or“cancel”
		if ($paymentStatus==PurchaseHelper::PURCHASE_PAID) {
		//$totalSupplierId=$purchaseFormInfoReq['totalsupplierid'];
		//self::generateOnePurchaseForm($warehouseId,$purchaseFormNo,$paymentStatus,$totalSupplierId,$deliveryFee,$deliveryMethod,$deliveryNumber,$expectedDeliveryDate,$productsInfo,$comment);
		return self::generateOnePurchaseForm($purchaseFormInfoReq,$productsInfo);
		 
		}
		 
		//split to mulitple purchase forms according to different suppliers
		$index=0;
		foreach($supplieridProductinfoMap  as $supplierId=>$productsInfoArr)  {
		$index++;
		$newPurchaseFormNo=$purchaseFormNo."-".$index;
		$purchaseFormInfoReq['purchase_id']=$newPurchaseFormNo;
		$purchaseFormInfoReq['supplier_id']=$supplierId;
		$purchaseFormInfoReq['delivery_fee']=0;
		//如果是多个供应商，而且没付款，系统会拆成多个采购单，在这种情况下，用户输入的物流金额和物流号码会无效。
		//self::generateOnePurchaseForm($warehouseId,$newPurchaseFormNo,$paymentStatus,$supplierId,0,$deliveryMethod,"",$expectedDeliveryDate,$productsInfoArr,$comment);
		list($ret,$message)=self::generateOnePurchaseForm($purchaseFormInfoReq,$productsInfoArr);
		if ($ret===false)  return array(false,$message);
		 
		}
		}
		return array(true,"");*/
	}
	
	/**
	 * get all the purchase records
	 * @param
	 * @return
	 */
	public static function getPurchaseFormList()
	{
		$resultArr=array();
	
		$rows=Yii::app()->subdb->createCommand('SELECT * from pc_purchase ')->queryAll();
	
		//$skuSuppliersInfo=SupplierHelper::getProductTopSupplierInfo();
		foreach($rows as $row){
			$resultArr[]=$row;
		}
		return $resultArr;
	}
	
	
	
	/**
	 * 为查看某张采购单信息提供数据.  这里由于需要展示每个货品对应的待到货数量，所以需要查看到货记录中的数据。
	 * @param 	$purchaseId
	 * @return $purchaseObject,$itemsRows
	 */
	public static function getDetailForPurchaseid($purchaseId)
	{
		//$purchaseObject=Purchase::model()->findByPk($purchaseId);
		//	$row=Yii::app()->subdb->createCommand('SELECT pc.purchase_id as purchase_id ,pc.capture_user_id as capture_user_id,pc.comment as comment,pc.amount as amount,pc.create_time as create_time ,expected_arrival_date,delivery_method,delivery_fee,delivery_number,pc.supplier_name as supplier_name,wh.name as warehouse_name,pc.status as status,pc.payment_status as payment_status '.
		//			' from pc_purchase pc, wh_warehouse as wh where pc.warehouse_id=wh.warehouse_id and pc.purchase_id="'.$purchaseId.'"')->queryRow();
		$purchaseObject=Purchase::model()->findByPk($purchaseId);
		 
	
		//$row['status_label']=PurchaseHelper::getPurchaseStatus($row['status']);
		//$rows=Yii::app()->subdb->createCommand('SELECT pi.sku as sku,pi.name as name,qty,price,amount,photo_primary from pc_purchase_items pi,pd_product pd where pi.sku=pd.sku and purchase_id="'.$purchaseId.'"')->queryAll();
		$rows=Yii::app()->subdb->createCommand('SELECT pi.sku as sku,pi.name as name,qty,price,amount,photo_primary,pi.supplier_name as supplier_name from pc_purchase_items pi,pd_product pd where pi.sku=pd.sku and purchase_id=:purchase_id')
		->bindParam(":purchase_id",$purchaseId,\PDO::PARAM_STR)->queryAll();
		 
		 
		$itemsRows=array();
		//获取sku对应的待到货数量
		$skuQtyMap=PurchaseArrivalHelper::getWaitingArrivalGoods($purchaseId,true,false);
		foreach($rows as $row){
			$sku=$row['sku'];
			if (!isset($skuQtyMap[$sku])) {
				//none of arrival info
				$row['qty_waiting']=$row['qty'];
				$itemsRows[]=$row;
				continue;
			}
				
			//采购数量 小于等于 待收货总数量 （别名情况下）待收货数量 =  待收货总数量 - 采购数量 的原则分配下去
			if ($row['qty'] <= $skuQtyMap[$sku]){
				$row['qty_waiting']=$row['qty'] ;
				$skuQtyMap[$sku] -= $row['qty'];
			}else{
				$row['qty_waiting']=$skuQtyMap[$sku];
				// $skuQtyMap 最后清零 ， 防止（别名情况下）待收货数量数量显示错误
				$skuQtyMap[$sku] = 0;
	
			}
				
			$itemsRows[]=$row;
		}
	
		return array($purchaseObject,$itemsRows);
	}
	
	
	/**
	 * 为查看某张采购单包含的产品信息提供数据.
	 * @param 		$purchase_id
	 * @return		$items
	 */
	 public static function getPurchaseOrderDetail($purchase_id)
	 {
	 	$order = Purchase::findOne(['id' => $purchase_id]);
		$items = PurchaseItems::find()->where(['purchase_id'=>$purchase_id])->asArray()->all();
		
		for ($i=0;$i<count($items);$i++){
			//图片信息
			$prodinfo = Product::findOne($items[$i]['sku']);
			$items[$i]['img'] = empty($prodinfo->photo_primary)?'':$prodinfo->photo_primary;
			//采购链接
			$pd_sp = ProductSuppliers::findOne(['supplier_id' => $order->supplier_id, 'sku' => $items[$i]['sku']]);
			if(!empty($pd_sp) && !empty($pd_sp['purchase_link'])){
				$items[$i]['purchase_link'] = $pd_sp['purchase_link'];
			}else{
				$items[$i]['purchase_link'] = '';
			}
		}
		return $items;
	}
	
	/**
	 * Given sku list and supplierid,getting more products info
	 * @param sku array
	 * @return $retArr ------  array( array("purchase_price"=>3,"row_total"=>3,"photo_primary"=>......),array(.... )...)
	 */
	public static function getProductsInfoFromSkuArr($getProdInfo,$supplierId) {
		if (count($getProdInfo)==0)  return false;
	
		$retArr=array();
		$productTopSupplierInfo=SupplierHelper::getProductTopSupplierInfo();
	
		foreach($getProdInfo as $prodInfo)
		{
			//sku,采购价格,数量，供应商
			$tempArr=array();
			$tempArr['sku']=$prodInfo['sku'];
			if (!isset($productTopSupplierInfo[$prodInfo['sku']])) $supplierInfo=array("name"=>"notchosen","purchase_price"=>"0","latest_price"=>"0","supplier_id"=>"-1");
			else $supplierInfo=$productTopSupplierInfo[$prodInfo['sku']];
	
			$tempArr['purchase_price']=$supplierInfo['purchase_price'];
			$tempArr['latest_price']=$supplierInfo['purchase_price'];
			//获取该产品对应的所有供应商的报价信息
			$tempArr['suppliers_info']=SupplierHelper::getSuppliersInfoForOneProduct($prodInfo['sku']);
			foreach($tempArr['suppliers_info'] as $tmpSupplierInfo){
				if ($tmpSupplierInfo["supplier_id"]==$supplierId){
					$tempArr['purchase_price']=$tmpSupplierInfo['purchase_price'];
					$tempArr['latest_price']=$tmpSupplierInfo['purchase_price'];
					break;
				}
			}
	
			$tempArr['number'] = (isset($prodInfo['number'])?$prodInfo['number']:1);
			$tempArr['row_total'] = $tempArr['number'] * $tempArr['purchase_price'];
			$tempArr['supplier_name']=$supplierInfo['name'];
			$tempArr['supplier_id']=$supplierInfo['supplier_id'];
				
			$product=Product::model()->findByPk($prodInfo['sku']);
			$tempArr['photo_primary']=$product->photo_primary;
			$tempArr['name']=$product->name;
				
			$retArr[]=$tempArr;
		}
		return $retArr;
	}
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * excel 导入 出入库 产品列表
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 	array:	$ExcelFile		用户按照excel模版 格式制定的 excel数据 限xls 文件
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result ['success'] boolean
	 * 					$result ['message'] string  运行结果的提示信息
	 * 					$result ['tb_Html'] html字段,返回table内容
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/3/30		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function importPurchaseProdsByExcel($ExcelFile, $suppliername = ''){
		//初始化 返回结果
		$result['success'] = true;
		$result['message'] = '';
	
		//获取 excel数据
		$excel_data = ExcelHelper::excelToArray($ExcelFile , self::$EXCEL_COLUMN_MAPPING, true);
	
		$prod_list = [];
		$prods =[];
		//检查excel 导入的sku是否重复
		foreach($excel_data as &$aProd){
			//排除表头
			$field_labels = self::$EXPORT_EXCEL_FIELD_LABEL;
			if($aProd['sku']==$field_labels['sku'] && $aProd['qty']==$field_labels['qty'] && $aProd['price']==$field_labels['price']){
				continue;
			}
			//验证sku有效性
			//SKU去掉结尾的空格和换行符
			$aProd['sku'] = trim($aProd['sku']);
			$aProd ['sku'] = str_replace('\r', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace('\n', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace('\t', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace(chr(10), '', $aProd ['sku']);
			
			$product=Product::findOne(['sku'=>$aProd['sku']]);
			if(empty($product)){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('不存在！');
				continue;
			}
			if($product->type=="C" or $product->type=="B"){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('是变参产品或捆绑产品，不能对其做库存操作！');
				continue;
			}
			//验证采购数量有效性和单价
			if( !is_numeric($aProd['qty']) ){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('输入的采购数无效！必须为数字');
				continue;
			}
			if( !is_numeric($aProd['price']) )
			{
			    if($aProd['price'] == '' && $suppliername != '')
			    {
			        //供应商
			        $supplier = Supplier::findOne(['name'=>$suppliername]);
			        if(!empty($supplier))
			        {
			            //获取报价信息
			        	$supplierModels = ProductSuppliers::findone(['sku'=>$aProd['sku'], 'supplier_id'=>$supplier->supplier_id]);
			        	if(!empty($supplierModels))
			        	{
			        		$aProd['price'] = $supplierModels->purchase_price;
			        	}
			        }
			    }
			    
			    if( !is_numeric($aProd['price']) )
			    {
    				$aProd['price'] = '0';
			    }
			}
			//处理重复sku
			if (! in_array($aProd['sku'], $prod_list)){
				$prod_list[] = $aProd['sku'];
				$prods[$aProd['sku']]['sku'] = $aProd['sku'];
				$prods[$aProd['sku']]['qty'] = $aProd['qty'];
				$prods[$aProd['sku']]['price'] = $aProd['price'];
				$prods[$aProd['sku']]['remark'] = $aProd['remark'];
			}else{
				if($result['success'])
					$result['message'] .= $aProd['sku'] .TranslateHelper::t(" 重复录入,已合并显示! ");
				$prods[$aProd['sku']]['qty'] += $aProd['qty'];
				$prods[$aProd['sku']]['price'] = $aProd['price'];
				if(!empty($prods[$aProd['sku']]['remark'])){
				    if(!empty($aProd['remark'])){
				        $prods[$aProd['sku']]['remark'] = $prods[$aProd['sku']]['remark'].'；'.$aProd['remark'];
				    }
				}
				else{
				    $prods[$aProd['sku']]['remark'] = $aProd['remark'];
				}
			}
		}
		if(count($prods)<=0){//文件导入无产品
			$result['success'] = false;
			$result['message'] .= TranslateHelper::t('导入文件无产品或产品信息有误！请检查excel文件后重新导入！');
			return $result;
		}
		if(!$result['success']){//导入信息有错误
			return $result;
		}else{//导入信息正常：
			//table header
			$tb_Html = self::importDatasToHtml($prods);
				
			$result['td_Html'] = $tb_Html;
			$result['taxtarea_div_html'] = '';
		}
		return $result;
	}//end of importStockChangeProdsByExcel
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 复制粘贴 excel格式文本 导入 采购  产品
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 	array:	$prodDatas		用户按照excel模版 格式制定的 excel数据 限xls 文件
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result ['success'] boolean
	 * 					$result ['message'] string  运行结果的提示信息
	 * 					$result ['tb_Html'] html字段,返回table内容
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/3/30		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function importPurchaseProdsByExcelFormatText($prodDatas){
		//初始化 返回结果
		$result['success'] = true;
		$result['message'] = '';
	
		$prod_list = [];
		$prods =[];
		//检查excel 导入的sku是否重复
		foreach($prodDatas as &$aProd){
			//排除表头
			$field_labels = self::$EXPORT_EXCEL_FIELD_LABEL_PURCHASE;
			if($aProd['sku']==$field_labels['sku'] && $aProd['qty']==$field_labels['qty'] && $aProd['price']==$field_labels['price']){
				continue;
			}
			//验证sku有效性
			$product=Product::findOne(['sku'=>$aProd['sku']]);
			$aProd['sku'] = trim($aProd['sku']);
			$aProd ['sku'] = str_replace('\r', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace('\n', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace('\t', '', $aProd ['sku']);
			$aProd ['sku'] = str_replace(chr(10), '', $aProd ['sku']);
			
			if(empty($product)){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('不存在！ ');
				continue;
			}
			if($product->type=="C" or $product->type=="B"){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('是变参产品或捆绑产品，不能对其做库存操作！ ');
				continue;
			}
			
			//验证采购数量有效性和单价
			if( !is_numeric($aProd['qty']) ){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('输入的采购数无效，必须为数字！ ');
				continue;
			}
			if( !is_numeric($aProd['price']) ){
				$result['success'] = false;
				$result['message'] .= TranslateHelper::t('产品:').$aProd['sku'].TranslateHelper::t('输入的单价无效，必须为数字！ ');
				continue;
			}
			//处理重复sku
			if (! in_array($aProd['sku'], $prod_list)){
				$prod_list[] = $aProd['sku'];
				$prods[$aProd['sku']]['sku'] = $aProd['sku'];
				$prods[$aProd['sku']]['qty'] = $aProd['qty'];
				$prods[$aProd['sku']]['price'] = $aProd['price'];
				$prods[$aProd['sku']]['remark'] = $aProd['remark'];
			}else{
				if($result['success'])
					$result['message'] .= $aProd['sku'] .TranslateHelper::t(" 重复录入,已合并显示!  ");
				$prods[$aProd['sku']]['qty'] += $aProd['qty'];
				$prods[$aProd['sku']]['price'] = $aProd['price'];
				if(!empty($prods[$aProd['sku']]['remark'])){
					if(!empty($aProd['remark'])){
						$prods[$aProd['sku']]['remark'] = $prods[$aProd['sku']]['remark'].'；'.$aProd['remark'];
					}
				}
				else{
					$prods[$aProd['sku']]['remark'] = $aProd['remark'];
				}
			}
		}
		if(count($prods)<=0){//文件导入无产品
			$result['success'] = false;
			$result['message'] .= TranslateHelper::t('粘贴的文本无产品或产品信息有误！请检查复制来源后重新导入！ ');
			return $result;
		}
		if(!$result['success']){//导入信息有错误
			return $result;
		}else{//导入信息正常：
			//table header
			$tb_Html = self::importDatasToHtml($prods);
	
			$result['td_Html'] = $tb_Html;
			$result['taxtarea_div_html'] = '';
		}
		return $result;
	}//end of importStockChangeProdsByExcelFormatText
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 产品数据array写入Html，以便输出到浏览器
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 	array:	$prodDatas		产品信息及qty,货架位置信息
	 +---------------------------------------------------------------------------------------------
	 * @return string	tb_Html 		html字段,返回table内容
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/3/30		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	protected static function importDatasToHtml($prodDatas){
		$tb_Html = "<tr><td width='50px'>".TranslateHelper::t('图片')."</td>".
				"<td width='150px'>".TranslateHelper::t('货品sku')."</td>".
				"<td width='250px'>".TranslateHelper::t('货品名称')."</td>".
				"<td width='100px'>".TranslateHelper::t('采购数量')."</td>".
				"<td width='80px'>".TranslateHelper::t('单价(人民币)')."</td>".
				"<td width='100px'>".TranslateHelper::t('总成本(人民币)')."</td>".
				"<td width='100px'>".TranslateHelper::t('备注')."</td>".
				"<td width='80px'>".TranslateHelper::t('操作')."</td>".
				"</tr>";
		$index=0;
		foreach ($prodDatas as $p){
			$sku = $p['sku'];
			$pInfo = Product::findOne(['sku'=>$sku]);
			$name = $pInfo['name'];
			$img = $pInfo['photo_primary'];
			$purchase_link = $pInfo['purchase_link'];
			
			$class = '';
			if(!is_int($index / 2))
				$class = ' striped-row';
			
			$tb_Html .= "<tr class='prod_".$sku." prodList_tr".$class."'>".
					"<td name='prod[".$index."][img]' value='".$img."'><img src='".$img."' style='width:50px ! important;height:50px ! important'></td>".
					"<td name='prod[".$index."][sku]' value='".$sku."'>";
			
			if(!empty($purchase_link))
			    $tb_Html .= "<a href='".$purchase_link."' target='_blank'>".$sku."</a><input type='hidden' name='prod[".$index."][sku]' value='".$sku."' style='display:none'></td>";
			else 
			    $tb_Html .= $sku."<input type='hidden' name='prod[".$index."][sku]' value='".$sku."' style='display:none'></td>";
			
			$tb_Html .= "<td name='prod[".$index."][name]' value='".$name."'>".$name."<input type='hidden' name='prod[".$index."][name]' value='".$name."' style='display:none'></td>".
					"<td ><input type='text' name='prod[".$index."][qty]' value='".$p['qty']."' required='required' index='".$index."' class='eagle-form-control'></td>".
					"<td ><input type='text' name='prod[".$index."][price]' class='form-control' value='".$p['price']."' required='required' index='".$index."' class='eagle-form-control'></td>".
					"<td ><input type='text' name='prod[".$index."][amount]' class='form-control' value='".$p['qty']*$p['price']."' readonly class='eagle-form-control'></td>".
					"<td ><input type='text' name='prod[".$index."][remark]' class='form-control' value='".$p['remark']."' index='".$index."' class='eagle-form-control'></td>".
					"<td ><div><a class='cancelPurchaseProd'>".TranslateHelper::t('取消')."</a></div></td>".
					"</tr>";

			$index ++;
		}
		return $tb_Html;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据采购状态获取采购单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 	status str		Purchas status
	 +---------------------------------------------------------------------------------------------
	 * @return 	mixed
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/4/23		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	protected static function getPurchaseOrderByStatus($status){
		
	}
	
	public static function generateExcelData($purchase_ids){
		$purchaseDatas = [];
		$purchases = Purchase::find()->where(['id'=>$purchase_ids])->asArray()->all();
		$purchase_details = PurchaseItems::find()->where(['purchase_id'=>$purchase_ids])->asArray()->all();
		$details = [];
		foreach ($purchase_details as $d){
			$details[$d['purchase_id']][] = $d;
		}
		$purchase_status_mapping = self::$PURCHASE_STATUS;
		foreach ($purchases as $p){
			if(empty($details[$p['id']]))
				continue;
			foreach ($details[$p['id']] as $i=>$detail){
				$tmp =[];
				$tmp['purchase_order_id'] = $p['purchase_order_id'];
				$tmp['status'] = empty($purchase_status_mapping[$p['status']])?$p['status']:$purchase_status_mapping[$p['status']];
				$tmp['create_time'] = strtotime($p['create_time']);
				$tmp['supplier_name'] = $p['supplier_name'];
				$tmp['sku'] = $detail['sku'];
				if(strpos($detail['name'], "<input ") != false)
				    $tmp['name'] = substr($detail['name'], 0, strpos($detail['name'], "<input "));
				else 
				    $tmp['name'] = $detail['name'];
				$tmp['qty'] = $detail['qty'];
				$tmp['price'] = $detail['price'];
				$tmp['amount_subtotal'] = $tmp['qty']*$tmp['price'];
				$tmp['remark'] = $detail['remark'];
				$purchaseDatas[] = $tmp;
			}
		}
		return $purchaseDatas;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 *	 查询采购单
	 +---------------------------------------------------------------------------------------------
	 * @param      $keyword 需要模糊搜索的条件
	 * 			   $sort 排序字段
	 * 				$order 升序降序
	 * 				$parm 条件
	 * 				$pageSize 最多导出行数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/1/4				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function generateExcelDatalist($keyword, $sort , $order , $parm = array(),$pageSize=20){
		$connection = Yii::$app->get('subdb');
		$sql="SELECT b.id,purchase_order_id,status,create_time,a.supplier_name,sku,name,qty,in_stock_qty,price,a.amount,remark,warehouse_id,a.supplier_id,
					payment_status FROM `pc_purchase_items` a left join pc_purchase b on a.`purchase_id`=b.id";
		$condition_all=' where 1=1 ';
		
		if(!empty($keyword)){
			//去掉keyword的引号。免除SQL注入
			$keyword = str_replace("'","",$keyword);
			$keyword = str_replace('"',"",$keyword);
			
			if(!empty($parm['search_type']) && $parm['search_type'] == 'tru_no')
				$sql .= " and (b.delivery_number like '%$keyword%')";
			else if(!empty($parm['search_type']) && $parm['search_type'] == 'sku')
				$sql .= " and (a.purchase_id in (select purchase_id from pc_purchase_items where sku like '%$keyword%'))";
			else
				$sql .= " and (b.purchase_order_id like '%$keyword%')";
		}
		
		if(array_key_exists('search_type', $parm)){
			unset($parm['search_type']);
		}
	
		foreach ($parm as $key=>$parmone){
			if($parmone!==''){
				$parmone = str_replace("'","",$parmone);
				$parmone = str_replace('"',"",$parmone);
				$val_array = explode(",",$parmone);
				$condi_internal =" and ( 0 ";
				foreach ($val_array as $aVal){
					if($key=='sdate')
						$condi_internal .= " or create_time>'$aVal'";
					else if($key=='edate')
						$condi_internal .= " or create_time<'$aVal'";
					else
						$condi_internal .= " or $key='$aVal'";
				}
		
				$condi_internal .= ")";
		
				$condition_all .= $condi_internal;
			}
		}

		// Pagination 插件自动获取url 上page和per-page（Pagination默认参数，可以修改）这两个name的参数
		// 然后获得$pagination->limit 即 $pageSize 和 $pagination->offset 即 $page 校正过后的值。
		// 为配合分页功能，请尽量不要自己定义 “每页多少行()” 和 “第几页” 这两参数。
		// 如果硬是自己定义了,就在Pagination初始化时覆盖'pageParam'和'pageSizeParam'为你使用的参数也可。否则分页功能生成的链接会出现异常。
// 		$query = $connection->createCommand($sql.$condition_all);
// 		$pagination = new Pagination([
// 				'defaultPageSize' => $pageSize,//当per-page获取不到值时的默认值20
// 				'totalCount' => count($query->queryAll()),
// 				'pageSizeLimit'=>[5,200],// 每页显示条数的范围，默认 1-50。所以如果不修改这里，不管per-page返回什么每页最多显示50条记录。
// 				]);

// 		$data['pagination'] = $pagination;
		if(empty($sort)){
			$sort = 'b.purchase_order_id';
			$order = 'desc';
		}
		
		$sortStr = " order by $sort $order,a.purchase_item_id asc";
// 		$offset = " limit ". $pagination->offset." , ". $pagination->limit;
// 		print_r($sql.$condition_all.$sortStr);die;
		$command = $connection->createCommand($sql.$condition_all.$sortStr);
		$rows =  $command->queryAll();

		if(count($rows)<1){
			$data['data']=array();
		}
		foreach ($rows as &$row) {			
			if(!empty($row['purchase_order_id']))
				$data['data'][]=$row;
		}

		return $data;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 *	 采购导出采购单
	 +---------------------------------------------------------------------------------------------
	 * @param      $data 导出类型
	 * 			   $type 排队导出还是直接导出，true为直接，false为排队
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/1/4				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function ExportExcelAll($data, $type = false){
		$rtn['success'] = 1;
		$rtn['message'] = '';
		try{
			$journal_id = SysLogHelper::InvokeJrn_Create("Catalog", __CLASS__, __FUNCTION__ ,array($data));
			$purchase_ids=$data;

			$sort=isset($purchase_ids['sort'])?('b.'.$purchase_ids['sort']):'';
			$order = isset($purchase_ids['order'])?$purchase_ids['order']:'';
			$keyword=isset($purchase_ids['keyword'])?$purchase_ids['keyword']:'';
			if(isset($purchase_ids['type'])){
				$params=isset($purchase_ids['params'])?$purchase_ids['params']:'';
				foreach ($params as $k=>$v){
					if ($k =='keyword' || $k =='per-page' || $k =='page'){
						unset($params[$k]);
					}
				}
			}
			else{
				$params['b.id']=$purchase_ids;
			}

			$pageSize=isset($purchase_ids['count'])?$purchase_ids['count']:'70000';
			$data = PurchaseHelper::generateExcelDatalist($keyword,$sort,$order,$params,$pageSize);
			$data=$data['data'];

			$items_arr = ['purchase_order_id'=>'采购单号','sku'=>'sku','name'=>'商品名称','qty'=>'采购数量','in_stock_qty'=>'已入库数量','price'=>'采购单价','amount'=>'采购成本','status'=>'采购状态','create_time'=>'采购时间','supplier_name'=>'供应商','remark'=>'备注'];
			$keys = array_keys($items_arr);

			if(!isset($purchase_ids['type'])){
				$data_tmp=$data;
				unset($data);
				$purchase_ids_arr=explode(',', $purchase_ids);
				foreach ($purchase_ids_arr as $productidone){
					foreach ($data_tmp as $index=>$row){
						if($row['id']==$productidone){
							$data[]=$row;
						}
					}
				}
			}

			$purchase_status_mapping = self::$PURCHASE_STATUS;

			$excel_data = [];
			foreach ($data as $index=>$row){
				$tmp=[];
				foreach ($keys as $key){
					if(isset($row[$key])){
						if(in_array($key,['purchase_order_id','sku','name']) && is_numeric($row[$key]))
							$tmp[$key]=' '.$row[$key];
						else
							$tmp[$key]=(string)$row[$key];
						
						if($key=='status')
							$tmp[$key]=(string)(empty($purchase_status_mapping[$row[$key]])?$row[$key]:$purchase_status_mapping[$row[$key]]);
						
						if($key=='create_time')
							$tmp[$key]=(string)strtotime($row[$key]);;
					}
				}
				$excel_data[$index] = $tmp;
				unset($tmp);
			}			
			unset($product_ids);
			$rtn=ExcelHelper::exportToExcel($excel_data, $items_arr, 'purchase_'.date('Y-m-dHis',time()).".xls",[], $type);

			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
			$rtn['count'] = count($excel_data);
			unset($excel_data);
		}
		catch (\Exception $e) {
			$rtn['success'] = 0;
			$rtn['message'] = '导出失败：'.$e->getMessage();
		}

		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	采购分批入库，保存
	 +---------------------------------------------------------------------------------------------
	 * @param      $data 导出类型
	 * 			   $type 排队导出还是直接导出，true为直接，false为排队
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/1/4				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function SaveInStock($data){
		if(empty($data['purchase_id']) || empty($data['prod'])){
			return ['success' => false, 'msg' => '采购单Id缺失'];
		}
		
		$prods = array();
		foreach($data['prod'] as $prod){
			if(!empty($prod['sku']) && !empty($prod['in_qty'])){
				$prods[$prod['sku']] = $prod;
			}
		}
		if(empty($prods)){
			return ['success' => false, 'msg' => '请填写入库数量'];
		}
		
		$model = purchase::findOne(['id'=>$data['purchase_id']]);
		if(empty($model)){
			return ['success' => false, 'msg' => '采购单缺失'];
		}
		$model->warehouse_id = empty($model->warehouse_id) ? 0 : $model->warehouse_id;
	
		$items = PurchaseItems::findAll(['purchase_id'=>$model->id]);
		if(empty($items)){
			return ['success' => false, 'msg' => '采购明细信息缺失'];
		}
		
		//判断是否允许多收
		$support_purchase_out = ConfigHelper::getConfig('support_purchase_out');
		
		$purchase_price = array();
		foreach ($items as $item){
			$purchase_price[$item->sku] = $item->price;
			
			if(!empty($support_purchase_out) && $support_purchase_out == 'N'){
				$sku = $item->sku;
				if(array_key_exists($sku, $prods)){
					if($item->qty < $item->in_stock_qty + $prods[$sku]['in_qty']){
						return ['success' => false, 'msg' => '商品: '.$sku.' 入库数量不能超过采购数量'];
					}
				}
			}
		}
		
		//查询出入库单号
		$i = 1;
		$stock_change_id = join("_",array($model->purchase_order_id, $i));
		while (StockChange::find()->where(['stock_change_id'=>$stock_change_id])->One() <> null){
			$i++;
			$stock_change_id = join("_",array($model->purchase_order_id, $i));
		}
		$transaction = Yii::$app->get('subdb')->beginTransaction();
		//新建出入库单，台头
		$stock_change['stock_change_id'] = $stock_change_id;
		$stock_change['create_time'] = TimeUtil::getNow();
		$stock_change['update_time'] = TimeUtil::getNow();
		$stock_change['capture_user_id'] = \Yii::$app->user->id;
		$stock_change['source_id'] = $model->id;
		$stock_change['warehouse_id'] = $model->warehouse_id;
		$stock_change['comment'] = "<font color=blue>".\Yii::$app->user->identity->getFullName()." @ "."</font>".TranslateHelper::t('通过采购模块快速入库。');
		$rtn = InventoryHelper::insertStockInRecord($stock_change, InventoryHelper::getStockChangeReason("采购入库"));
		if(!$rtn['success']){
			$transaction->rollBack();
			return ['success' => false, 'msg' => '新增出入库信息失败：'.$rtn['message']];
		}
		$surplus_pro = 0; //剩余入库商品
	    foreach ($items as $item){
	    	$sku = $item->sku;
	    	if(array_key_exists($sku, $prods)){
    	    	//Step:1***************插入到出入库明细
    	    	$rtn = InventoryHelper::insertStockChangeDetailRecord($stock_change, $prods[$sku]['sku'], $prods[$sku]['in_qty']);
    	    	if(!$rtn['success']){
    	    		$transaction->rollBack();
    	    		return ['success' => false, 'msg' => '新增出入库明细信息失败：'.$rtn['message']];
    	    	}
    	    		
    	    	//Step:2***************库存数量、在途数量变更
    	    	$query_location_grid = ProductStock::findOne(['sku' => $sku , 'warehouse_id'=>$model->warehouse_id]);
    	    	$location_grid='';
    	    	if(!empty($query_location_grid)){
    	    		$location_grid = $query_location_grid->location_grid;
    	    	}
    	    	//更新的在途数量
    	    	$qty_on_the_way = $prods[$sku]['in_qty'];
    	    	if($item->qty - $item->in_stock_qty < $prods[$sku]['in_qty']){
    	    	    if($item->qty - $item->in_stock_qty > 0){
    	    	        $qty_on_the_way = $item->qty - $item->in_stock_qty;
    	    	    }
    	    	    else{
    	    	        $qty_on_the_way = 0;
    	    	    }
    	    	}
    	    	$rtn = InventoryHelper::modifyProductStockage($sku, $model->warehouse_id, 0 - $qty_on_the_way, $prods[$sku]['in_qty'], 0, 0,
    	    			(isset($purchase_price[$sku]) ? $purchase_price[$sku] : 0), $location_grid);
    	    	if(!$rtn['success']){
    	    		$transaction->rollBack();
    	    		return ['success' => false, 'msg' => '更新库存信息信息失败：'.$rtn['message']];
    	    	}
    	    	
    	    	//Step:3***************更新采购单的入库数量
    	    	$item->in_stock_qty = $item->in_stock_qty + $prods[$sku]['in_qty'];
    	    	if(!$item->save()){
    	    		$transaction->rollBack();
    	    		return ['success' => false, 'msg' => '更新采购入库信息失败！'];
    	    	}
	    	}
	    	
	    	if($item->qty > $item->in_stock_qty){
	    		$surplus_pro++;
	    	}
	    }
	    
	    //写入操作日志
	    $edit_log = "采购入库，采购单 ".$model->purchase_order_id.' 生成出入库记录 '.$stock_change_id;
	    UserHelper::insertUserOperationLog('purchase', $edit_log);
		
	    $model->in_stock_comment = empty($data['purchase_comment']) ? '' : $data['purchase_comment'];
	    $model->status = 5;
	    if($surplus_pro > 0){
	    	$model->status = 4;
	    	$model->is_arrive_goods ='Y';
	    }
		if(!$model->save()){
			$transaction->rollBack();
			return ['success' => false, 'msg' => '更新采购信息失败！'];
		}
		OperationLogHelper::log("purchase", $model->purchase_order_id, "采购入库，更新状态","",\Yii::$app->user->identity->getFullName()."@".TimeUtil::getNow());
		
		$transaction->commit();
		
		return ['success' => true, 'msg' => ''];
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	查询订单的明细信息
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/09/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function GetPurchaseOrderInfo($order_ids){
		$info = array();
		//采购单头信息
		$warehouse_ids = array();
		$purchaseList = Purchase::find()->where(['id' => $order_ids])->asArray()->all();
		foreach($purchaseList as $pur){
			$info[$pur['id']] = [
				'Id' => $pur['id'],
				'purchase_order_id' => $pur['purchase_order_id'],
				'supplier_name' => $pur['supplier_name'],
				'status' => empty(self::$PURCHASE_STATUS[$pur['status']]) ? '' : self::$PURCHASE_STATUS[$pur['status']],
				'create_time' => $pur['create_time'],
				'comment' => $pur['comment'],
				'capture_user_name' => $pur['capture_user_name'],
				'warehouse_id' => $pur['warehouse_id'],
				'warehouse_name' => '',
			];
			
			$warehouse_ids[] = $pur['warehouse_id'];
		}
		
		//仓库信息
		$warehouse_arr = array();
		$warehouseList = Warehouse::find()->where(['warehouse_id' => $warehouse_ids])->asArray()->all();
		foreach($warehouseList as $warehouse){
			$warehouse_arr[$warehouse['warehouse_id']] = $warehouse['name'];
		}
		
		//采购商品信息
		$skus = array();
		$purchaseItems = PurchaseItems::find()->where(['purchase_id' => $order_ids])->asArray()->all();
		foreach($purchaseItems as $item){
			$info[$item['purchase_id']]['item'][] = [
				'sku' => $item['sku'],
				'price' => $item['price'],
				'qty' => $item['qty'],
				'amount' => $item['amount'],
				'in_stock_qty' => $item['in_stock_qty'],
				'remark' => $item['remark'],
				'name' => '',
				'photo_primary' => '',
			];
			
			$skus[] = $item['sku'];
		}
		//商品明细信息
		$pro_arr = array();
		$productList = Product::find()->select(['sku', 'name', 'photo_primary'])->where(['sku' => $skus])->asArray()->all();
		foreach($productList as $pro){
			$pro_arr[$pro['sku']] = $pro;
		}
		
		foreach($info as &$pur){
			if(!empty($pur['item'])){
				foreach($pur['item'] as &$item){
					if(array_key_exists($item['sku'], $pro_arr)){
						$item['name'] = $pro_arr[$item['sku']]['name'];
						$item['photo_primary'] = $pro_arr[$item['sku']]['photo_primary'];
					}
				}
			}
			
			if(array_key_exists($pur['warehouse_id'], $warehouse_arr)){
				$pur['warehouse_name'] = $warehouse_arr[$pur['warehouse_id']];
			}
		}
		
		return $info;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	打印采购单，生成pdf
	 +---------------------------------------------------------------------------------------------
	 * @param	
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/09/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function printToPDF($orderlist){
		$result = array('error'=>false, 'msg'=>'');
		
		//查询需打印的订单信息
		$info = self::GetPurchaseOrderInfo($orderlist);
		
		$format = 'A4';
		//新建
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $format, true, 'UTF-8', false);
		
		//设置页边距
		$pdf->SetMargins(0, 0, 0);
		
		//删除预定义的打印 页眉/页尾
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		
		//设置不自动换页,和底部间距为0
		$pdf->SetAutoPageBreak(false, 0);
		
		//设置字体行距
		$pdf->setCellHeightRatio(1);
		
		foreach($info as $pur){
			//新增一页
			$pdf->AddPage();
			
			self::getCnPost_US(7, 7 , $pdf, $pur, [], [], 0, []);
		}
		
		if($result['error'] == false){
			$pdf->Output('print.pdf', 'I');
		}else{
			return $result;
		}
	}
	
	public static function getCnPost_US($tmpX, $tmpY , &$pdf, $pur, $shippingService, $format, $lableCount, $otherParams)
	{
		//******************字体   start********************
		$font_msyh = 'msyh';      //微软雅黑
		$font_Arial = 'Arial';    //Arial
		$font_Arialbd = 'Arialbd';    //Arialbd
		//******************字体   end**********************
		
		$pageX = 210 - $tmpX;
		$pageY = 297 - $tmpY;
		$border_width = 0.3;
		
		$pdf->SetFont($font_msyh, '', 20);
		$pdf->writeHTMLCell(190, 0, $tmpX, $tmpY, '采购单', 0, 1, 0, true, 'C', true);
		$tmpY += 10;
		//单号、日期
		$pdf->SetFont($font_msyh, '', 12);
		$pdf->writeHTMLCell(30, 0, $tmpX, $tmpY, '采购单号：', 0, 1, 0, true, '', true);
		$pdf->writeHTMLCell(20, 0, $tmpX + 140, $tmpY, '日期：', 0, 1, 0, true, '', true);
		$pdf->SetFont($font_msyh, '', 10);
		$pdf->writeHTMLCell(110, 0, $tmpX + 22, $tmpY + 1, $pur['purchase_order_id'], 0, 1, 0, true, '', true);
		$pdf->writeHTMLCell(50, 0, $tmpX + 152, $tmpY + 1, $pur['create_time'], 0, 1, 0, true, '', true);
		$tmpY += 8;
		//其它台头信息table
		$width = 8;
		//top边线条
		$pdf->Line($tmpX, $tmpY, $pageX, $tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//left边线条
		$pdf->Line($tmpX, $tmpY, $tmpX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line($pageX, $tmpY, $pageX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//供应商
		$pdf->writeHTMLCell(25, 0, $tmpX, $tmpY + 1, '供应商：', 0, 1, 0, true, 'C', true);
		$pdf->Line($tmpX + 25, $tmpY, $tmpX + 25, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$pdf->writeHTMLCell(75, 0, $tmpX + 27, $tmpY + 1, $pur['supplier_name'], 0, 1, 0, true, '', true);
		$pdf->Line($tmpX + 100, $tmpY, $tmpX + 100, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//仓库
		$pdf->writeHTMLCell(25, 0, $tmpX + 100, $tmpY + 1, '仓库：', 0, 1, 0, true, 'C', true);
		$pdf->Line($tmpX + 125, $tmpY, $tmpX + 125, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$pdf->writeHTMLCell(75, 0, $tmpX + 127, $tmpY + 1, $pur['warehouse_name'], 0, 1, 0, true, '', true);
		$tmpY += $width;
		//top边线条
		$pdf->Line($tmpX, $tmpY, $pageX, $tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//left边线条
		$pdf->Line($tmpX, $tmpY, $tmpX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line($pageX, $tmpY, $pageX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//状态
		$pdf->writeHTMLCell(25, 0, $tmpX, $tmpY + 1, '状态：', 0, 1, 0, true, 'C', true);
		$pdf->Line($tmpX + 25, $tmpY, $tmpX + 25, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$pdf->writeHTMLCell(75, 0, $tmpX + 27, $tmpY + 1, $pur['status'], 0, 1, 0, true, '', true);
		$pdf->Line($tmpX + 100, $tmpY, $tmpX + 100, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//采购员
		$pdf->writeHTMLCell(25, 0, $tmpX + 100, $tmpY + 1, '采购员：', 0, 1, 0, true, 'C', true);
		$pdf->Line($tmpX + 125, $tmpY, $tmpX + 125, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$pdf->writeHTMLCell(75, 0, $tmpX + 127, $tmpY + 1, $pur['capture_user_name'], 0, 1, 0, true, '', true);
		$tmpY += $width;
		//top边线条
		$pdf->Line($tmpX, $tmpY, $pageX, $tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//备注
		$pdf->writeHTMLCell(25, 0, $tmpX, $tmpY + 1, '备注：', 0, 1, 0, true, 'C', true);
		$pdf->writeHTMLCell(175, 0, $tmpX + 27, $tmpY + 1, $pur['comment'], 0, 1, 0, true, '', true);
		$old_tmpY = $tmpY;
		$tmpY = $pdf->getY() + 1;
		if($tmpY < $old_tmpY + $width){
			$tmpY = $old_tmpY + $width;
		} 
		$pdf->Line($tmpX + 25, $old_tmpY, $tmpX + 25, $tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//left边线条
		$pdf->Line($tmpX, $old_tmpY, $tmpX, $tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line($pageX, $old_tmpY, $pageX, $tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//$tmpY += $width;
		//bottom边线条
		$pdf->Line($tmpX, $tmpY, $pageX, $tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$tmpY += 5;
		
		//***************商品明细  列名 start******************
		$pdf->SetFont($font_msyh, '', 12);
		$width = 8;
		//left边线条
		$pdf->Line($tmpX, $tmpY, $tmpX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line($tmpX, $tmpY, $pageX, $tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line($pageX, $tmpY, $pageX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line($tmpX, $width+$tmpY, $pageX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//序号
		$pdf->Line($tmpX + 12, $tmpY, $tmpX + 12, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$pdf->writeHTMLCell(12, 0, $tmpX, $tmpY + 1, '序号', 0, 1, 0, true, 'C', true);
		//商品信息
		$pdf->Line($tmpX + 100, $tmpY, $tmpX + 100, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$pdf->writeHTMLCell(100, 0, $tmpX + 12, $tmpY + 1, '商品信息', 0, 1, 0, true, 'C', true);
		//采购量
		$pdf->Line($tmpX + 125, $tmpY, $tmpX + 125, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$pdf->writeHTMLCell(25, 0, $tmpX + 100, $tmpY + 1, '采购量', 0, 1, 0, true, 'C', true);
		//到货量
		$pdf->Line($tmpX + 150, $tmpY, $tmpX + 150, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$pdf->writeHTMLCell(25, 0, $tmpX + 125, $tmpY + 1, '到货量', 0, 1, 0, true, 'C', true);
		//单价
		$pdf->Line($tmpX + 175, $tmpY, $tmpX + 175, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		$pdf->writeHTMLCell(25, 0, $tmpX + 150, $tmpY + 1, '单价', 0, 1, 0, true, 'C', true);
		//成本
		$pdf->writeHTMLCell(21, 0, $tmpX + 175, $tmpY + 1, '成本', 0, 1, 0, true, 'C', true);
		//***************商品明细  列名 end******************
		
		//***************商品明细   start******************
		$pdf->SetFont($font_msyh, '', 10);
		$tmpY = $tmpY + $width;
		$width = 16;
		$allow_count = floor(($pageY - $tmpY) / $width);
		$count = 1;
		foreach($pur['item'] as $key => $item){
			//left边线条
			$pdf->Line($tmpX, $tmpY, $tmpX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//right边线条
			$pdf->Line($pageX, $tmpY, $pageX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//bottom边线条
			$pdf->Line($tmpX, $width+$tmpY, $pageX, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//序号
			$pdf->writeHTMLCell(12, 0, $tmpX, $tmpY + 5, ($key + 1), 0, 1, 0, true, 'C', true);
			$pdf->Line($tmpX + 12, $tmpY, $tmpX + 12, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//商品信息
			$pdf->writeHTMLCell(0, 0, $tmpX + 13, $tmpY + 1, '<img src="'.$item['photo_primary'].'"  width="13mm" height="13mm" >', 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(70, 0, $tmpX + 30, $tmpY + 1, 'SKU: '.$item['sku'], 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(70, 0, $tmpX + 30, $tmpY + 5, $item['name'], 0, 1, 0, true, '', true);
			$pdf->Line($tmpX + 100, $tmpY, $tmpX + 100, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//采购量
			$pdf->writeHTMLCell(25, 0, $tmpX + 100, $tmpY + 5, $item['qty'], 0, 1, 0, true, 'C', true);
			$pdf->Line($tmpX + 125, $tmpY, $tmpX + 125, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//到货量
			$pdf->writeHTMLCell(25, 0, $tmpX + 125, $tmpY + 5, $item['in_stock_qty'], 0, 1, 0, true, 'C', true);
			$pdf->Line($tmpX + 150, $tmpY, $tmpX + 150, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//单价
			$pdf->writeHTMLCell(25, 0, $tmpX + 150, $tmpY + 5, $item['price'], 0, 1, 0, true, 'C', true);
			$pdf->Line($tmpX + 175, $tmpY, $tmpX + 175, $width+$tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//成本
			$pdf->writeHTMLCell(21, 0, $tmpX + 175, $tmpY + 5, $item['amount'], 0, 1, 0, true, 'C', true);
			
			if($key + 1 != count($pur['item']) && $count >= $allow_count - 1){
				//下一页
				$pdf->addPage();
				$tmpY = 7;
				$count = 1;
				$allow_count = floor($pageY / $width);
				
				$pdf->Line($tmpX, $tmpY, $pageX, $tmpY, $style=array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			}
			else{
				$tmpY = $tmpY + $width;
				$count++;
			}
		}
		//***************商品明细   end******************
		
	
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取采购明细商品信息
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/12		初始化
	 +----------------------------------------------------------
	 **/
	public static function GetPurchaseItems($purchase_ids){
		$purchase_ids = rtrim($purchase_ids, ',');
		$purchase_id_arr = explode(',', $purchase_ids);
		$items = array();
		$skus = array();
		//采购明细
		$item_queue = PurchaseItems::find()->select(['purchase_id', 'purchase_item_id', 'sku', 'name', 'qty', 'price'])->where(['purchase_id' => $purchase_id_arr])->asArray()->all();
		foreach($item_queue as $item){
			$skus[] = $item['sku'];
		}
		//对应商品信息
		$products = array();
		$product_queue = Product::find()->select(['sku', 'name', 'photo_primary'])->where(['sku' => $skus])->asArray()->all();
		foreach($product_queue as $product){
			$products[strtolower($product['sku'])] = $product;
		}
		//整理信息
		foreach($item_queue as $item){
			$item['photo_primary'] = '';
			if(array_key_exists(strtolower($item['sku']), $products)){
				$item['photo_primary'] = $products[strtolower($item['sku'])]['photo_primary'];
				$item['name'] = $products[strtolower($item['sku'])]['name'];
			}
			$items[$item['purchase_id']][] = $item;
		}
		
		return $items;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取创建1688订单的采购信息
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/13		初始化
	 +----------------------------------------------------------
	 **/
	public static function GetCreate1688Info($purchase_id){
		$data = array();
		
		$purchase = Purchase::findOne(['id' => $purchase_id]);
		if(empty($purchase)){
			return [];
		}
		$data['purchase'] = [
			'purchase_id' => $purchase->id,
			'supplier_id' => $purchase->supplier_id,
		];
		//获取对应供应商信息
		$supplier = Supplier::findOne(['supplier_id' => $purchase->supplier_id]);
		if(!empty($supplier)){
		    $data['purchase']['name'] = $supplier->name;
		    $data['purchase']['al1688_user_id'] = $supplier->al1688_user_id;
		    $data['purchase']['al1688_company_name'] = $supplier->al1688_company_name;
		    $data['purchase']['al1688_url'] = $supplier->al1688_url;
		}
		$data['items'] = array();
		$items = self::GetPurchaseItems($purchase_id);
		if(!empty($items[$purchase_id])){
			$data['items'] = $items[$purchase_id];
		}
		//获取对应的1688商品配对信息
		$skus = array();
		$key_item = array();
		foreach($data['items'] as $key => $item){
			$skus[] = $item['sku'];
			$key_item[strtolower($item['sku'])] = $key;
		}
		$pc1688 = Pc1688Listing::find()->where(['sku' => $skus])->asArray()->all();
		foreach($pc1688 as $one){
			$sku = strtolower($one['sku']);
			if(array_key_exists($sku, $key_item)){
				$data['items'][$key_item[$sku]]['matching_1688'] = $one;
			}
		}
		
		//1688账号信息
		$data['al_1688_users'] = Al1688AccountsApiHelper::get1688UserInfo();
		
		list($url,$label) = AppApiHelper::getPlatformMenuData();
		//平台绑定连接
		$data['binding_url'] = $url;
		
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取1688商品
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/16		初始化
	 +----------------------------------------------------------
	 **/
	public static function Get1688Product($aliId, $pro_url){
		try{
			//分析url，获取商品Id
			$str = substr($pro_url, 0, strrpos($pro_url, '.html?'));
			$product_id = substr($str, strrpos($str, '/') + 1);
			if(empty($product_id)){
				return ['success' => false, 'msg' => '不是有效的产品链接！'];
			}
			//获取商品信息
			$api = new Al1688Interface_Api();
			$access_token = $api->getAccessToken ($aliId);
			if(empty($access_token)){
				return ['success' => false, 'msg' => '1688授权信息已失效，请重新授权！'];
			}
			$api->access_token = $access_token;
			$ret = $api->getProduct(['productID' => $product_id, 'webSite' => '1688']);
			if(!empty($ret['errMsg'])){
				return ['success' => false, 'msg' => $ret['errMsg']];
			}
			if(empty($ret['productInfo'])){
				return ['success' => false, 'msg' => '返回信息失败，productInfo不存在！'];
			}
			//整理商品信息
			$img_url_top = 'https://cbu01.alicdn.com/';
			$productInfo =  $ret['productInfo'];
			$product = [
				'product_id' => $productInfo['productID'],
				'name' => $productInfo['subject'],
				'image_url' => empty($productInfo['image']['images'][0]) ? '' : $img_url_top.$productInfo['image']['images'][0],
				'pro_link' => $pro_url,
			];
			
			//属性详情
			if(!empty($productInfo['skuInfos'])){
    			foreach($productInfo['skuInfos'] as $skuinfo){
    			    $attributes = '';
    			    $sku_image_url = '';
    			    foreach($skuinfo['attributes'] as $attribute){
    			        $attributes .= empty($attributes) ? $attribute['attributeValue'] : ' + '.$attribute['attributeValue'];
    			        if(!empty($attribute['skuImageUrl'])){
    			            $sku_image_url = $attribute['skuImageUrl'];
    			        }
    			    }
    			    $product['skus'][] = [
    			        'sku' => $skuinfo['skuId'],
    			        'price' => empty($skuinfo['price']) ? 0 : $skuinfo['price'],
    			        'attributes' => $attributes,
    			        'sku_image_url' => empty($sku_image_url) ? $product['image_url'] : $img_url_top.$sku_image_url,
    			        'spec_id' => $skuinfo['specId'],
    			    ];
    			}
			}
			return ['success' => true, 'product' => $product];
		}
		catch(\Exception $ex){
			return ['success' => false, 'msg' => $ex->getMessage()];
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 保存本地sku 与 1688商品配对
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/16		初始化
	 +----------------------------------------------------------
	 **/
	public static function Save1688Matching($data){
		if(empty($data['product_id']) || empty($data['sku_1688']) || empty($data['sku_image_url']) || empty($data['name'])){
			return ['success' => false, 'msg' => '1688商品信息缺失！'];
		}
		$purchase_item_id = empty($data['purchase_item_id']) ? '' : $data['purchase_item_id'];
		$product_id = empty($data['product_id']) ? '' : $data['product_id'];
		$sku_1688 = empty($data['sku_1688']) ? '' : $data['sku_1688'];
		$sku_image_url = empty($data['sku_image_url']) ? '' : $data['sku_image_url'];
		$name = empty($data['name']) ? '' : $data['name'];
		$pro_link = empty($data['pro_link']) ? '' : $data['pro_link'];
		$attributes = empty($data['attributes']) ? '' : $data['attributes'];
		$spec_id = empty($data['spec_id']) ? '' : $data['spec_id'];
		//获取采购信息
		$item = PurchaseItems::findOne(['purchase_item_id' => $purchase_item_id]);
		if(empty($item)){
			return ['success' => false, 'msg' => '采购明细信息缺失！'];
		}
		//清除本地SKU旧配对关系
		$row = Pc1688Listing::findOne(['sku' => $item->sku]);
		if(!empty($row)){
		    $row->sku = $row->sku_1688;
		    $row->save(false);
		}
		//保存配对关系
		$pro = Pc1688Listing::findOne(['product_id' => $product_id, 'sku_1688' => $sku_1688]);
		if(empty($pro)){
			$pro = new Pc1688Listing();
			$pro->product_id = $product_id;
		}
		$pro->sku_1688 = $sku_1688;
		$pro->name = $name;
		$pro->image_url = $sku_image_url;
		$pro->pro_link = $pro_link;
		$pro->sku = $item->sku;
		$pro->attributes = $attributes;
		$pro->spec_id = $spec_id;
		if(!$pro->save(false)){
			return ['success' => false, 'msg' => '更新配对关系失败'];
		}
		return ['success' => true, 'msg' => ''];
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据url获取1688店铺信息，并关联到供应商上
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/16		初始化
	 +----------------------------------------------------------
	 **/
	public static function Get1688Supplier($aliId, $supplier_id, $supplier_url){
		try{
			//查询本地供应商信息
			$supplier = Supplier::findOne(['supplier_id' => $supplier_id]);
			if(empty($supplier)){
				return ['success' => false, 'msg' => '请选择供应商！'];
			}
			//分析url，获取店铺域名
			if(strpos($supplier_url, '?') > 0){
			    $supplier_url = substr($supplier_url, 0, strpos($supplier_url, '?'));
			}
			if(strpos($supplier_url, '//') > 0){
				$supplier_url = substr($supplier_url, strpos($supplier_url, '//') + 2);
			}
			if(strpos($supplier_url, '/') > 0){
				$supplier_url = substr($supplier_url, 0, strpos($supplier_url, '/'));
			}
			if(empty($supplier_url)){
				return ['success' => false, 'msg' => '不是有效的店铺链接！'];
			}
			//获取1688店铺信息
			$api = new Al1688Interface_Api();
			$access_token = $api->getAccessToken ($aliId);
			if(empty($access_token)){
				return ['success' => false, 'msg' => '1688授权信息已失效，请重新授权！'];
			}
			$api->access_token = $access_token;
			$ret = $api->getUserInfoMember(['domin' => $supplier_url]);
			if(!empty($ret['errorInfo'])){
				return ['success' => false, 'msg' => '请输入有效的店铺链接'];
			}
			if(empty($ret['userId'])){
				return ['success' => false, 'msg' => '返回信息失败，店铺信息不存在！'];
			}
			//店铺信息与本地供应商关联起来
			$supplier_url = 'https://'.$supplier_url;
			$supplier->al1688_user_id = $ret['userId'];
			$supplier->al1688_company_name = $ret['companyName'];
			$supplier->al1688_url = $supplier_url;
			if(!$supplier->save(false)){
			    return ['success' => false, 'msg' => '保存店铺信息失败！'];
			}
				
			return ['success' => true, 'userId' => $ret['userId'], 'companyName' => $ret['companyName'], 'al1688_url' => $supplier_url];
		}
		catch(\Exception $ex){
			return ['success' => false, 'msg' => $ex->getMessage()];
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 创建1688订单
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/17		初始化
	 +----------------------------------------------------------
	 **/
	public static function Create1688Purchase($data){
		if(empty($data['select_1688_user'])){
			return ['success' => false, 'msg' => '请选择1688账号！'];
		}
		//采购订单
		$purchase_id = empty($data['purchase_id']) ? '' : $data['purchase_id']; 
		$purchase = Purchase::findOne(['id' => $purchase_id]);
		if(empty($purchase)){
			return ['success' => false, 'msg' => '采购订单信息丢失！'];
		}
		//商品信息
		PurchaseItems::updateAll(['product_id' => '', 'spec_id' => '', 'qty_1688' => 0], ['purchase_id' => $purchase_id]);
		$cargoParamList = array();
		foreach($data['prod'] as $prod){
			if(empty($prod['product_id'])){
				return ['success' => false, 'msg' => '“'. $prod['sku'] .'” 等SKU，请先关联1688商品！'];
			}
			$cargoParamList[] = [
				'offerId' => $prod['product_id'],
				'specId' => $prod['spec_id'],
				'quantity' => $prod['qty'],
			];
			
			PurchaseItems::updateAll(['product_id' => $prod['product_id'], 'spec_id' => $prod['spec_id'], 'qty_1688' => $prod['qty']], ['purchase_item_id' => $prod['purchase_item_id']]);
		}
		//收货地址
		if(!isset($data['select_receive_add']) || $data['select_receive_add'] == ''){
			return ['success' => false, 'msg' => '请选择收货地址！'];
		}
		$user = Saas1688User::findOne(['aliId' => $data['select_1688_user']]);
		if(empty($user)){
			return ['success' => false, 'msg' => '1688账号信息丢失！'];
		}
		$add = array();
		if(!empty($user->addi_info)){
			$addi_info = json_decode($user->addi_info, true);
			if(!empty($addi_info) && !empty($addi_info['rec_address']) && array_key_exists($data['select_receive_add'], $addi_info['rec_address'])){
				$add = $addi_info['rec_address'][$data['select_receive_add']];
			}
		} 
		if(empty($add)){
			return ['success' => false, 'msg' => '收货地址信息丢失！'];
		}
		if(isset($add['cityText'])){
    		$addressParam = [
    			'fullName' => $add['fullName'],
    			'mobile' => $add['mobile'],
    			'phone' => $add['phone'],
    			'postCode' => $add['postCode'],
    			'cityText' => $add['cityText'],
    			'provinceText' => $add['provinceText'],
    			'areaText' => $add['areaText'],
    			'townText' => $add['townText'],
    			'address' => $add['address'],
    		];
		}
		else{
		    $addressParam = [
		        'addressId' => $add['id'],
		    ];
		}
		$param = [
			'flow' => empty($data['flow']) ? 'general' : $data['flow'],
			'message' => empty($data['message']) ? '' : $data['message'],
			'addressParam' => json_encode($addressParam),
			'cargoParamList' => json_encode($cargoParamList),
		];
		
		
		$api = new Al1688Interface_Api();
		$access_token = $api->getAccessToken ($data['select_1688_user']);
		if(empty($access_token)){
			return ['success' => false, 'msg' => '1688授权信息已失效，请重新授权！'];
		}
		$api->access_token = $access_token;
		$ret = $api->fastCreateOrder($param);
		if(!empty($ret['error_message'])){
			return ['success' => false, 'msg' => $ret['error_message']];
		}
		
		if($ret['success']){
			$result = $ret['result'];
			//保存1688订单信息
			if(!empty($result['orderId']) && !empty($result['totalSuccessAmount'])){
				//获取支付链接
				$pay_ret = $api->getPayUrl(['orderIdList' => json_encode([$result['orderId']])]);
				if(!empty($pay_ret['payUrl'])){
					$purchase->pay_url = $pay_ret['payUrl'];
				}
				
				$purchase->order_id_1688 = $result['orderId'];
				$purchase->amount_1688 = $result['totalSuccessAmount'] / 100;
				$purchase->status_1688 = 'waitbuyerpay';
				if(!$purchase->save(false)){
					return ['success' => false, 'msg' => '保存1688信息到本地失败！'];
				}
			}
			else{
				return ['success' => false, 'msg' => '1688返回信息有误！'];
			}
			$msg = '';
			if(!empty($result['failedOfferList'])){
				if(!empty($result['failedOfferList']['errorMessage'])){
					return ['success' => false, 'msg' => '部分商品下单失败：'.$result['failedOfferList']['errorMessage']];
				}
				return ['success' => false, 'msg' => '部分商品下单失败！'];
			}
			return ['success' => true, 'order_id_1688' => $result['orderId'], 'msg' => ''];
		}
		else{
			return ['success' => false, 'msg' => $ret['message']];
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取1688账号历史收货地址
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/18		初始化
	 +----------------------------------------------------------
	 **/
	public static function Get1688Add($aliId){
		$api = new Al1688Interface_Api();
		$access_token = $api->getAccessToken($aliId);
		if(empty($access_token)){
			return ['success' => false, 'msg' => '1688授权信息已失效，请重新授权！'];
		}
		$api->access_token = $access_token;
		$param = [
			'page' => 1,
			'pageSize' => 20,
			'createStartTime' => date('YmdHiZ', time() - 3600 * 24 * 5).'+0800',
			'createEndTime' => date('YmdHiZ', time()).'+0800',
			'needBuyerAddressAndPhone' => 'true',
		];
		$ret = $api->getReceiveAddress();
		if(!empty($ret['error_message'])){
			return ['success' => false, 'msg' => $ret['error_message']];
		}
		if(!empty($ret['errorMessage'])){
			return ['success' => false, 'msg' => $ret['errorMessage']];
		}
		$adds = array();
		$add_keys = array();
		if(!empty($ret['result']['receiveAddressItems'])){
			foreach($ret['result']['receiveAddressItems'] as $one){
				$one['key'] = $one['address'].
				    ' 「'.$one['fullName'].'」收'.
				    ' '.(empty($one['mobilePhone']) ? $one['phone'] : $one['mobilePhone']);
				$adds[] = $one;
				$add_keys[] = $one['key'];
			}
			//保存地址到user表
			if(!empty($adds)){
				$user = Saas1688User::findOne(['aliId' => $aliId]);
				if(empty($user)){
					return ['success' => false, 'msg' => '1688账号信息丢失！'];
				}
				$addi_info = array();
				if(!empty($user->addi_info)){
					$addi_info = json_decode($user->addi_info, true);
					if(empty($addi_info)){
						$addi_info = array();
					}
				}
				if(!empty($addi_info['rec_address'])){
					//清除非本地设置的地址
					foreach($addi_info['rec_address'] as $key => $add){
						if($key != 'common'){
							unset($addi_info['rec_address'][$key]);
						}
					}
				}
				else{
					$addi_info['rec_address'] = array();
				}
				foreach($adds as $key => $add){
					$addi_info['rec_address'][$key] = $add;
				}
				$user->addi_info = json_encode($addi_info);
				$user->save(false);
				
				return ['success' => true, 'msg' => ''];
			}
		}
		
		return ['success' => false, 'msg' => '获取失败！'];
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取1688账号收货地址
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/18		初始化
	 +----------------------------------------------------------
	 **/
	public static function GetReceiveAdd($aliId){
		$user = Saas1688User::findOne(['aliId' => $aliId]);
		if(empty($user)){
			return ['success' => false, 'msg' => '1688账号信息丢失！'];
		}
		$addi_info = array();
		if(!empty($user->addi_info)){
			$addi_info = json_decode($user->addi_info, true);
			if(!empty($addi_info) && !empty($addi_info['rec_address'])){
			    $ret_adds = array();
			    foreach($addi_info['rec_address'] as $key => $add){
			    	$ret_adds[$key] = $add;
			    }
			    return ['success' => true, 'adds' => $ret_adds];
			}
		}
		
		return ['success' => false, 'msg' => '获取地址失败！'];
	}
	
	/**
	 +----------------------------------------------------------
	 * 更新1688订单信息
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/17		初始化
	 +----------------------------------------------------------
	 **/
	public static function Update1688OrderInfo(){
		//循环1688账号，更新1688订单信息
		$uid = \Yii::$app->subdb->getCurrentPuid();
		$users = Saas1688User::find()->select(['aliId'])->where(['is_active' => 1, 'uid' => $uid])->asArray()->all();
		foreach($users as $user){
		    $api = new Al1688Interface_Api();
		    $access_token = $api->getAccessToken($user['aliId']);
		    if(empty($access_token)){
		    	return ['success' => false, 'msg' => '1688授权信息已失效，请重新授权！'];
		    }
		    $api->access_token = $access_token;
		    $param = [
    		    'page' => 1,
    		    'pageSize' => 20,
    		    'modifyStartTime' => date('YmdHiZ', time() - 3600 * 24 * 5).'+0800',
    		    'modifyEndTime' => date('YmdHiZ', time() + 3600 * 24).'+0800',
		    ];
		    $count = 0;
		    while(1){
		    	//控制循环次数
		    	$count++;
		    	if($count > 10){
		    		break;
		    	}
		    	
		        $ret = $api->getBuyerOrderList($param);
		        if(empty($ret['result'])){
		            break;
		        }
		        foreach($ret['result'] as $order){
		            if(!empty($order['baseInfo'])){
		                //判断订单是否存在系统中
		                $order_id = $order['baseInfo']['idOfStr'];
		                $purchase = Purchase::findOne(['order_id_1688' => $order_id]);
		                if(!empty($purchase)){
		                    //获取物流信息
		                    $ret = $api->getLogisticsInfosBuyer(['webSite' => '1688', 'orderId' => $order_id]);
		                    if(!empty($ret['result'])){
		                    	foreach($ret['result'] as $result){
		                    		if(!empty($result['logisticsBillNo'])){
		                    			$purchase->logistics_billNo = $result['logisticsBillNo'];
		                    		}
		                    		if(!empty($result['logisticsCompanyName'])){
		                    			$purchase->logistics_company_name = $result['logisticsCompanyName'];
		                    		}
		                    		if(!empty($result['status'])){
		                    			$purchase->logistics_status = $result['status'];
		                    		}
		                    		break;
		                    	}
		                    }
		                    //更新订单信息
		                    $purchase->status_1688 = $order['baseInfo']['status'];
		                    $purchase->amount_1688 = $order['baseInfo']['totalAmount'];
		                    $purchase->save(false);
		                }
		                
		            }
		        }
		        
		        $param['page']++;
		    }
		    
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 设置本地收货地址
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/19		初始化
	 +----------------------------------------------------------
	 **/
	public static function SaveRecAdd($data){
		if(!empty($data['set_rec_add_aliId']) && !empty($data['fullName'])){
			$user = Saas1688User::findOne(['aliId' => $data['set_rec_add_aliId']]);
			if(empty($user)){
				return ['success' => false, 'msg' => '1688账号信息丢失！'];
			}
			$addi_info = array();
			if(!empty($user->addi_info)){
				$addi_info = json_decode($user->addi_info, true);
				if(empty($addi_info) || empty($addi_info['rec_address'])){
					$addi_info['rec_address'] = array();
				}
			}
			else{
				$addi_info['rec_address'] = array();
			}
			$add = [
				'fullName' => empty($data['fullName']) ? '' : $data['fullName'],
				'mobile' => empty($data['mobile']) ? '' : $data['mobile'],
				'phone' => empty($data['phone']) ? '' : $data['phone'],
				'postCode' => empty($data['postCode']) ? '' : $data['postCode'],
				'cityText' => empty($data['city']) ? '' : $data['city'],
				'provinceText' => empty($data['province']) ? '' : $data['province'],
				'areaText' => empty($data['area']) ? '' : $data['area'],
				'townText' => empty($data['town']) ? '' : $data['town'],
				'address' => empty($data['address']) ? '' : $data['address'],
				'districtCode' => '',
			];
			$add['key'] = $add['provinceText'].' '.$add['cityText'].' '.$add['areaText'].' '.$add['address'].' 「'.$add['fullName'].'」收'.' '.$add['mobile'];
			
			$addi_info['rec_address']['common'] = $add;
			$user->addi_info = json_encode($addi_info);
			$user->save(false);
			return ['success' => true, 'msg' => ''];
		}
		
		return ['success' => false, 'msg' => '信息不完整！'];
	}
	
	/**
	 +----------------------------------------------------------
	 * 绑定1688订单到采购单上
	 +----------------------------------------------------------
	 * log			name	    date			note
	 * @author		lrq 		2018/04/18		初始化
	 +----------------------------------------------------------
	 **/
	public static function Binding1688Order($aliId, $purchase_id, $order_id){
		$user = Saas1688User::findOne(['aliId' => $aliId]);
		if(empty($user)){
			return ['success' => false, 'msg' => '1688账号信息丢失！'];
		}
		$purchase = Purchase::findOne(['id' => $purchase_id]);
		if(empty($user)){
			return ['success' => false, 'msg' => '采购订单信息丢失！'];
		}
		
		//获取1688订单信息
		$order_id = trim($order_id);
		$api = new Al1688Interface_Api();
		$access_token = $api->getAccessToken($aliId);
		if(empty($access_token)){
			return ['success' => false, 'msg' => '1688授权信息已失效，请重新授权！'];
		}
		$api->access_token = $access_token;
		$order = $api->getBuyerView(['webSite' => '1688', 'orderId' => $order_id]);
		if(empty($order['result']) || empty($order['result']['baseInfo'])){
			return ['success' => false, 'msg' => '1688订单号不存在！'];
		}
		//获取物流信息
		$ret = $api->getLogisticsInfosBuyer(['webSite' => '1688', 'orderId' => $order_id]);
		if(!empty($ret['result'])){
			foreach($ret['result'] as $result){
				if(!empty($result['logisticsBillNo'])){
					$purchase->logistics_billNo = $result['logisticsBillNo'];
				}
				if(!empty($result['logisticsCompanyName'])){
					$purchase->logistics_company_name = $result['logisticsCompanyName'];
				}
				if(!empty($result['status'])){
					$purchase->logistics_status = $result['status'];
				}
				break;
			}
		}
		//更新订单信息
		$purchase->order_id_1688 = $order_id;
		$purchase->status_1688 = $order['result']['baseInfo']['status'];
		$purchase->amount_1688 = $order['result']['baseInfo']['totalAmount'];
		if(!$purchase->save(false)){
			return ['success' => false, 'msg' => '保存失败！'];
		}
		
		return ['success' => true, 'msg' => '关联成功！'];
	}
	
}



