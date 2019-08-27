<?php
namespace eagle\modules\carrier\controllers;

use Yii;
use yii\web\Controller;
use common\helpers\Helper_Array;
use eagle\models\SaasAliexpressUser;
use eagle\modules\delivery\helpers\DeliveryHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\carrier\apihelpers\ApiHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\models\EbayCountry;
use eagle\models\SysCountry;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\inventory\apihelpers\InventoryApiHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\QueueSyncshipped;
use eagle\modules\order\openapi\OrderApi;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\models\CarrierUserLabel;
use eagle\modules\util\helpers\PDFMergeHelper;
use common\helpers\Helper_Curl;
use eagle\models\CrCarrierTemplate;
use eagle\models\SysShippingMethod;
use eagle\models\carrier\CrTemplate;
use eagle\modules\order\models\Excelmodel;
use yii\helpers\Url;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\util\helpers\CountryHelper;
use eagle\modules\order\models\OrderRelation;
use eagle\modules\carrier\apihelpers\PrintPdfHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\RedisHelper;
use Qiniu\json_decode;
use eagle\modules\permission\helpers\UserHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\models\SaasLazadaUser;
use common\api\lazadainterface\LazadaInterface_Helper;

class CarrierprocessController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	/**
	 +----------------------------------------------------------
	 * 物流模块主页
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionIndex(){
		return $this->render('index');
	}
	/**
	 +----------------------------------------------------------
	 * 未匹配运输服务订单页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionWaitingmatch(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/Waitingmatch");
		$query_condition = $_REQUEST;
		$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		$query_condition['no_warehouse_or_shippingservice'] = '(default_warehouse_id < 0 or default_shipping_method_code = "")';//未匹配仓库和运输服务
		$search = array('is_comment_status'=>'等待您留评');
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		return $this->render('waitingmatch',
				[
				'search'=>$search,
				'carrierQtips'=>$carrierQtips,
				]+self::getlist($query_condition)
		);
	}
	/**
	 +----------------------------------------------------------
	 * api待上传页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionWaitingpost(){
		//当2016-08-23 23:45:00 自动将链接跳去发货模块,物流模块屏蔽掉
		if(time() > 1471967100)
			return $this->redirect('/delivery/order/listplanceanorder');
		
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/Waitingpost");
		$query_condition = $_REQUEST;
		$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:1;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 1;
		}
		//物流操作流程
		if(empty($_REQUEST['carrier_step'])){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'UPLOAD';
		}
		
		switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		
		
		$tmpLists = self::getlist($query_condition);
		
		//批量获取页面打开的订单的账号信息
		$tmpSearchShippingid = array();
		$carrierAccountInfo = array();
		
		//获取订单的商品详细信息
		$order_products = array();
		
		//获取API物流code
		$default_carrier_codes = array();
		$sys_carrier_params = array();
		
		foreach ($tmpLists['orders'] as $tmporders){
			if(substr($tmporders->default_carrier_code, 0, 3) == 'lb_'){
				$tmpSearchShippingid[$tmporders->default_shipping_method_code] = $tmporders->default_shipping_method_code;
		
				$default_carrier_codes[$tmporders->default_carrier_code] = $tmporders->default_carrier_code;
		
				CarrierOpenHelper::getCustomsDeclarationSumInfo($tmporders, $order_products);
			}
		}
		
		if(count($tmpSearchShippingid) > 0){
			$carrierAccountInfo = CarrierOpenHelper::getCarrierAccountInfoByShippingId(array('shippings'=>$tmpSearchShippingid));
		}
		
		if(count($default_carrier_codes) > 0){
			$sys_carrier_params = CarrierOpenHelper::getSysCarrierParams(array('carrier_codes'=>$default_carrier_codes));
		}
		
		$warehouseNameMap = \eagle\modules\inventory\helpers\InventoryApiHelper::getWarehouseIdNameMap();
		
		//组织前端HTML代码
		$orderHtml = array();
		
		foreach ($tmpLists['orders'] as $tmporders){
			if(substr($tmporders->default_carrier_code, 0, 3) == 'lb_'){
				$orderHtml[$tmporders->order_id] = CarrierOpenHelper::getOrdersCarrierInfoView($tmporders, $order_products, $sys_carrier_params, $carrierAccountInfo, $warehouseNameMap);
			}
		}
		
		return $this->render('waitingpost',
				['search'=>$search,'carrierQtips'=>$carrierQtips,'orderHtml'=>$orderHtml]+$tmpLists);
	}
	/**
	 +----------------------------------------------------------
	 * api待交运页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionWaitingdelivery(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/Waitingdelivery");
		$query_condition = $_REQUEST;
		$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:1;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 1;
		}
		//物流操作流程
		if(!isset($_REQUEST['carrier_step']) || trim($_REQUEST['carrier_step'])==''){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'DELIVERY';
		}
	switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		return $this->render('waitingpost',
				[
						'search'=>$search,
						'carrierQtips'=>$carrierQtips,
						]+self::getlist($query_condition));
	}
	/**
	 +----------------------------------------------------------
	 * api已交运页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDelivered(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/Delivered");
		$query_condition = $_REQUEST;
		$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:1;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 1;
		}
		//物流操作流程
		if(empty($_REQUEST['carrier_step'])){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'DELIVERYED';
		}
		switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		return $this->render('waitingpost',
				[
						'search'=>$search,
						'carrierQtips'=>$carrierQtips,
						]+self::getlist($query_condition));
	}
	/**
	 +----------------------------------------------------------
	 * api已完成页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionCompleted(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/Completed");
		$query_condition = $_REQUEST;
		//$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:1;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 1;
		}
		//物流操作流程
		if(!isset($_REQUEST['carrier_step']) || trim($_REQUEST['carrier_step'])==''){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'FINISHED';
		}
	switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		return $this->render('waitingpost',
				[
						'search'=>$search,
						'carrierQtips'=>$carrierQtips,
						]+self::getlist($query_condition));
	}
	/**
	 +----------------------------------------------------------
	 * excel未导出页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExcelexport(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/Excelexport");
		$query_condition = $_REQUEST;
		$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:2;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 2;
		}
		//物流操作流程
		if(!isset($_REQUEST['carrier_step']) || trim($_REQUEST['carrier_step'])==''){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'UPLOAD';
		}
	switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		return $this->render('waitingpost',
				[
						'search'=>$search,
						'carrierQtips'=>$carrierQtips,
						]+self::getlist($query_condition));
	}
	/**
	 +----------------------------------------------------------
	 * excel已导出页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExcelexported(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/Excelexported");
		$query_condition = $_REQUEST;
		$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:2;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 2;
		}
		//物流操作流程
		if(!isset($_REQUEST['carrier_step']) || trim($_REQUEST['carrier_step'])==''){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'DELIVERY';
		}
	switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		return $this->render('waitingpost',
				[
						'search'=>$search,
						'carrierQtips'=>$carrierQtips,
						]+self::getlist($query_condition));
	}
	/**
	 +----------------------------------------------------------
	 * excel已完成页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExcelcompleted(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/Excelcompleted");
		$query_condition = $_REQUEST;
		//$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:2;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 2;
		}
		//物流操作流程
		if(!isset($_REQUEST['carrier_step']) || trim($_REQUEST['carrier_step'])==''){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'FINISHED';
		}
	switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		return $this->render('waitingpost',
				[
						'search'=>$search,
						'carrierQtips'=>$carrierQtips,
						]+self::getlist($query_condition));
	}
	/**
	 +----------------------------------------------------------
	 * 分配跟踪号   未分配页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionTracknoExport(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/TracknoExport");
		$query_condition = $_REQUEST;
		$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:3;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 3;
		}
		//物流操作流程
		if(!isset($_REQUEST['carrier_step']) || trim($_REQUEST['carrier_step'])==''){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'UPLOAD';
		}
	switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		return $this->render('waitingpost',
				[
						'search'=>$search,
						'carrierQtips'=>$carrierQtips,
						]+self::getlist($query_condition));
	}
	/**
	 +----------------------------------------------------------
	 * 分配跟踪号   已分配页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionTracknoExported(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/TracknoExported");
		$query_condition = $_REQUEST;
		$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:3;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 3;
		}
		//物流操作流程
		if(!isset($_REQUEST['carrier_step']) || trim($_REQUEST['carrier_step'])==''){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'DELIVERY';
		}
	switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		return $this->render('waitingpost',
				[
						'search'=>$search,
						'carrierQtips'=>$carrierQtips,
						]+self::getlist($query_condition));
	}
	/**
	 +----------------------------------------------------------
	 * 分配跟踪号   已完成页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionTracknoCompleted(){
		AppTrackerApiHelper::actionLog("eagle_v2", "/carrier/Carrierprocess/TracknoCompleted");
		$query_condition = $_REQUEST;
		//$query_condition['order_status'] = OdOrder::STATUS_WAITSEND;//发货中
		//物流商类型
		$query_condition['carrier_type'] = $_REQUEST['carrier_type'] = isset($_REQUEST['carrier_type'])?$_REQUEST['carrier_type']:3;
		if($query_condition['carrier_type'] != 1 && $query_condition['carrier_type'] != 2 && $query_condition['carrier_type'] != 3){
			$query_condition['carrier_type'] = 3;
		}
		//物流操作流程
		if(!isset($_REQUEST['carrier_step']) || trim($_REQUEST['carrier_step'])==''){
			$query_condition['carrier_step']=$_REQUEST['carrier_step'] = 'FINISHED';
		}
	switch ($query_condition['carrier_step']){
			case 'UPLOAD':
				$query_condition['carrier_step'] = [0,4];
				break;
			case 'DELIVERY':
				$query_condition['carrier_step'] = 1;
				break;
			case 'DELIVERYED':
				$query_condition['carrier_step'] = [2,3,5];
				break;
			case 'FINISHED':
				$query_condition['carrier_step'] = 6;
				break;
		}
		$query_condition['warehouse_and_shippingservice'] = '(default_warehouse_id > -1 and default_shipping_method_code <> "")';
		$carrierQtips = CarrierOpenHelper::getCarrierQtips();
		$search = array(
				'is_comment_status'=>'等待您留评',
				'no_print_carrier'=>'未打印物流面单',
				'print_carrier'=>'已打印物流面单',
		);
		return $this->render('waitingpost',
				[
						'search'=>$search,
						'carrierQtips'=>$carrierQtips,
						]+self::getlist($query_condition));
	}
	/**
	 * 确认发货完成
	 * @description  
	 * 	step 1. 标记发货
	 *  step 2. 减库存
	 *  step 3. 更新待发货数量
	 *  step 4. 修改订单状态
	 * @return json 失败订单号
	 * auth: Mei Liang
	 */
	public function actionSetfinished(){
		if (\Yii::$app->request->isPost){
			$orderids = Yii::$app->request->post('orderids');
		}else{
			$orderids = Yii::$app->request->get('order_id');
		}
		Helper_Array::removeEmpty($orderids);
		if (count($orderids)==0){
			return json_encode(array('success'=>false,'message'=>TranslateHelper::t('未选择订单！')));
		}
		$orders=OdOrder::find()->where(['order_id'=>$orderids])->all();
		
		//是否手动填写跟踪号
		$is_manual_tracking_no = false;
		
		//运输服务code
		$tmp_shipping_method_code = array();
		
		//判断订单是否亚太平台EUB 假如订单在待交运中即不能确认发货完成
		foreach ($orders as $order){
			if($order->default_carrier_code == 'lb_epacket'){
				if($order->carrier_step == OdOrder::CARRIER_WAITING_DELIVERY){
					return json_encode(array('success'=>false,'message'=>'订单'.$order->order_id.' 亚太平台EUB需要交运后才可以确认发货完成!'));
				}
			}
			
			$tmp_shipping_method_code[] = $order->default_shipping_method_code;
			
			if($order->default_shipping_method_code == 'manual_tracking_no'){
				$is_manual_tracking_no = true;
			}
		}
		
		//获取对应的跟踪号为空，是否通知平台发货，只限于ebay,amazon Start
		$tmp_tracking_upload_config = 0;
		if(count($tmp_shipping_method_code) > 0){
			$tmp_SysShippingService = SysShippingService::find()->select(['id','tracking_upload_config'])->where(['id'=>$tmp_shipping_method_code])->asArray()->all();
			
			if(count($tmp_SysShippingService) > 0){
				foreach ($tmp_SysShippingService as $tmp_SysShippingServiceKey => $tmp_SysShippingServiceVal){
					$tmp_SysShippingService[$tmp_SysShippingServiceKey]['tracking_upload_config'] = json_decode($tmp_SysShippingServiceVal['tracking_upload_config'], true);
				}
			}
		}
		
		if($is_manual_tracking_no == false){
			if(empty($tmp_SysShippingService)){
				return json_encode(array('success'=>false,'message'=>'订单'.$order->order_id.' 运输服务选择异常,请确定运输服务是否正确选择!'));
			}
		
			$tmp_SysShippingService2 = $tmp_SysShippingService;
			unset($tmp_SysShippingService);
			$tmp_SysShippingService = array();
			foreach ($tmp_SysShippingService2 as $tmp_SysShippingService2Val){
				$tmp_SysShippingService[$tmp_SysShippingService2Val['id']] = $tmp_SysShippingService2Val['tracking_upload_config'];
			}
			
			foreach ($orders as $order){
				if(in_array($order->order_source, array('ebay','amazon'))){
					if(empty($tmp_SysShippingService[$order->default_shipping_method_code][$order->order_source])){
						$tmp_tracking_upload_config = 0;
					}else{
						$tmp_tracking_upload_config = $tmp_SysShippingService[$order->default_shipping_method_code][$order->order_source];
					}
				}
			}
		}
		//获取对应的跟踪号为空，是否通知平台发货，只限于ebay,amazon End
		
		
		// dzt20160812 for 合并订单标记发货加入原始订单
		$allOrders = array();
		$mergeFSOrderIdMap = array();
		$mergeSFOrderIdMap = array();
		$mergeOrderShipInfo = array();
		$successSignShipFMOrder = array();
		$stock_order = array();    //用于扣库存的order信息
		// @todo 获取父订单tracking 信息，标记父订单已完成
		foreach ($orders as $order){
			if('sm' == $order->order_relation){// 合并订单标记原始订单
				$orderRels = OrderRelation::findAll(['son_orderid'=>$order->order_id , 'type'=>'merge' ]);
				foreach ($orderRels as $orderRel){
					$originOrder = OdOrder::findOne($orderRel->father_orderid);
					$mergeFSOrderIdMap[$originOrder->order_id] = $order->order_id;
					$mergeSFOrderIdMap[$order->order_id][] = $originOrder->order_id;
					$allOrders[] = $originOrder;
				}
				
				
				$default_value = [
				'order_source'=>$order->order_source,
				'selleruserid'=>$order->selleruserid,
				'tracking_number'=>'',
				'tracking_link'=>'',
				'shipping_method_code'=>'',
				'shipping_method_name'=>'',
				'order_source_order_id'=>$order->order_source_order_id,
				'description'=>'',
				'signtype'=>'',
				'addtype'=>'自动标记发货',
				];
				$odship = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'status'=>0])->orderBy('id DESC')->one();
				if ($odship==null){
					$logisticInfoList=['0'=>$default_value];
				}else{
					$tmp_arr = array();
					foreach ($default_value as $k=>$v){
						$tmp_arr[$k]=$odship[$k];
					}
					$logisticInfoList=['0'=>$tmp_arr];
				}
				
				$mergeOrderShipInfo[$order->order_id] = $logisticInfoList;
			}else{
				$allOrders[] = $order;
			}
			
			$stock_order[] = $order;
		}
		$message = "";
		$checkReport = '';
		foreach ($allOrders as $order){
			$old = $order->order_status;
			
			######################################自动标记发货begin#############################################
			//不自动标记发货的平台
			$no_auto_mark_shipment = json_decode(ConfigHelper::getConfig('no_auto_mark_shipment'));
			$no_auto_mark_shipment = empty($no_auto_mark_shipment)?OdOrder::$no_autoShippingPlatform:$no_auto_mark_shipment;
			if ( ! in_array($order->order_source, $no_auto_mark_shipment) && $order->order_relation!='ss'){
				//step 1  标记发货
				try {
					//自动通知平台发货
					$is_shipped = true;
					//1订单是否已经通知平台发货
					$condition1 = false;
					if ($order->shipping_status >0  || $order->delivery_time > 0){
						$condition1 = true;
					}
					//2shipped表面status=1的数据有没有，如果有则说明已经通知平台发货
					$condition2 = false;
					$count = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'status'=>1])->count();
					if ($count>0){
						$condition2 = true;
					}
					//3标记发货队列里面有没有数据,如果有也不自动通知平台发货		这里后期添加了selleruserid控制是否唯一，暂时发现PM订单会有不同店铺存在相同订单号
					$condition3 = false;
					$count2 = QueueSyncshipped::find()->where(['order_source_order_id'=>$order->order_source_order_id,'selleruserid'=>$order->selleruserid])->count();
					if ($count2 > 0 ){
						$condition3 = true;
					}
					
					//当是速卖通订单部分发货状态，物流API未发货，则可以自动标记发货， lrq20180402
					if($order->order_source == 'aliexpress' && $order->order_source_status == 'SELLER_PART_SEND_GOODS'){
						$condition1 = false;
						$count = OdOrderShipped::find()->where(['order_id'=>$order->order_id, 'status'=>1, 'addtype' => '物流API'])->count();
						if ($count == 0){
							$condition2 = false;
						}
					}
					
					//以上三个条件只要一个成立，说明已经标记过一次
					$default_value = [
					'order_source'=>$order->order_source,
					'selleruserid'=>$order->selleruserid,
					'tracking_number'=>'',
					'tracking_link'=>'',
					'shipping_method_code'=>'',
					'shipping_method_name'=>'',
					'order_source_order_id'=>$order->order_source_order_id,
					'description'=>'',
					'signtype'=>'',
					'addtype'=>'自动标记发货',
					];
					if ($condition1 || $condition2 || $condition3){
// 						$odship = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'status'=>0])->andWhere('length(tracking_number)>0')->orderBy('id DESC')->one();
// 						if ($odship!==null){
// 							$tmp_arr = array();
// 							foreach ($default_value as $k=>$v){
// 								$tmp_arr[$k]=$odship[$k];
// 							}
// 							$logisticInfoList=['0'=>$tmp_arr];
// 						}else{
// 							//假如没有新的物流， 则需要unset $logisticInfoList 否则 saveTrackingNumber 会生成 最后一次执行成功的order_shipped 数据
// 							unset($logisticInfoList);
// 						}
						//这里假如已经标记发货过了就不用再标记发货了
						unset($logisticInfoList);
					}else{//没有标记过
						if('fm' == $order->order_relation){// dzt20160812 原始订单标记发货shiping 信息从合并订单获取
							$smOrderId =  $mergeFSOrderIdMap[$order->order_id];
							$logisticInfoList = $mergeOrderShipInfo[$smOrderId];
						}else{
							$odship = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'status'=>0])->orderBy('id DESC')->one();
							if ($odship==null){
								$logisticInfoList=['0'=>$default_value];
							}else{
								$tmp_arr = array();
								foreach ($default_value as $k=>$v){
									$tmp_arr[$k]=$odship[$k];
								}
								$logisticInfoList=['0'=>$tmp_arr];
							}
						}
					}
					if (isset($logisticInfoList)){
						
						//用于判断amazon是否自动根据小老板规则生成假的跟踪号
						$tmp_auto_trackNum = 1;
						
						// 虚拟发货 检查 
						if($order->order_source == 'cdiscount'){
							$checkRT = \eagle\modules\order\helpers\CdiscountOrderInterface::preCheckSignShippedInfo($logisticInfoList[0]['tracking_number'],$order->order_source_shipping_method, $logisticInfoList[0]['shipping_method_code'], $logisticInfoList[0]['shipping_method_name'], $logisticInfoList[0]['tracking_link']);
							if ($checkRT['success'] == false){
								$checkReport .= "<br> 平台订单".$order->order_source_order_id." 提交失败：". $checkRT['message'];
								return json_encode(array('success'=>false,'message'=>'E5 订单'.$order->order_id.'标记发货失败:'.$checkReport.'!'));
							}
						}else if(in_array($order->order_source, array('ebay', 'amazon'))){
							if(($tmp_tracking_upload_config == 0) && (empty($logisticInfoList[0]['tracking_number']))){
								return json_encode(array('success'=>false,'message'=>'E5 订单'.$order->order_id.'标记发货失败:'.' 跟踪号为空不能通知平台发货,假如需要请到运输服务设置->高级设置：设置可以为跟踪号允许为空'.'!'));
							}
							
							if(($order->order_source == 'amazon') && ($tmp_tracking_upload_config == 2) && (empty($logisticInfoList[0]['tracking_number']))){
								$tmp_auto_trackNum = 2;
							}
						}
						
						$success = OrderHelper::saveTrackingNumber($order->order_id,$logisticInfoList,0,$is_shipped, $tmp_auto_trackNum);
						if ($success){
							OrderApiHelper::unsetPlatformShippedTag($order->order_id);//去掉虚假发货标签
						}else{
							return json_encode(array('success'=>false,'message'=>'E1 订单'.$order->order_id.'标记发货失败!'));
						}
					}
				} catch (\Exception $e) {
					\Yii::error(__FUNCTION__." E2 failure to shipped order ".print_r($e->getMessage(),true),"file");
					return json_encode(array('success'=>false,'message'=>'E2 订单'.$order->order_id.'标记发货失败!'));
				}
			}
			######################################自动标记发货end#############################################
			
			//扣库存、更新配货数量，对父订单执行
			foreach($stock_order as $s_k => $s_order){
				//step 2 减库存
				$rtn = \eagle\modules\inventory\helpers\InventoryApiHelper::OrderProductStockOut($s_order->order_id);
				if (isset($rtn['success'])&&$rtn['success']==false){
					//失败
					return json_encode(array('success'=>false,'message'=>'E3 订单'.$s_order->order_id.$rtn['message']));
				}
				
				//step 3  更新待发货数量
				$warehouseid = $s_order->default_warehouse_id;
				if(empty($warehouseid))
					$warehouseid = 0;
				foreach($s_order->items as $item)
				{
				    if(!empty($item['root_sku'])){
    					//禁用的商品不需执行
    					if(empty($item['delivery_status']) || $item['delivery_status'] != 'ban'){
    						$Qty = $item['quantity'];
    						$sku = $item['root_sku'];
							$rt = \eagle\modules\inventory\apihelpers\InventoryApiHelper::updateQtyOrdered($warehouseid, $sku, -$Qty );
							if($rt['status'] == 0)
							    return json_encode(array('success'=>false,'message'=>$s_order->order_id.' 更新待发货数量失败：'.$rt['msg']));
    					}
				    }
				}
				
				unset($stock_order[$s_k]);
			}
			
			$order->order_status = OdOrder::STATUS_SHIPPED;
			$order->carrier_step = OdOrder::CARRIER_FINISHED;
			
			//step 4 修改订单状态
			$order->complete_ship_time = time();
			if(!$order->save()){
				return json_encode(array('success'=>false,'message'=>'E4 订单'.$order->order_id.'订单状态修改失败!'));
			}else{
				// dzt20160812 记录所有原始订单标记成功，方便后面根据原始订单标记情况来转换合并订单状态
				if(array_key_exists($order->order_id, $mergeFSOrderIdMap)){
					$successSignShipFMOrder[] = $order->order_id;
				}
				
				//告知dashboard面板，统计数量改变了
				OrderApiHelper::adjustStatusCount($order->order_source, $order->selleruserid, $order->order_status, $old, $order->order_id);
				
				OperationLogHelper::log('delivery', $order->order_id,'确认发货完成','确认发货完成,状态:'.OdOrder::$status[$old].'->'.OdOrder::$status[$order->order_status], \Yii::$app->user->identity->getFullName());
			}
			//操作成功返回信息
			if(in_array( $order->order_source, $no_auto_mark_shipment ) ){
// 				$message .= '<a href="'.Url::to(['/order/order/signshipped','order_id'=>$order->order_id]).'" target="_blank" class="alert-link">'.'订单'.$order->order_id.'确认发货完成操作成功！,状态:'.OdOrder::$status[$old].'->'.OdOrder::$status[$order->order_status].','.$order->order_source.'平台订单不支持自动通知平台发货，请手动通知平台发货!</a>';
				$message .= '<a class="alert-link">'.'订单'.$order->order_id.'确认发货完成操作成功！,状态:'.OdOrder::$status[$old].'->'.OdOrder::$status[$order->order_status].','.$order->order_source.'平台订单不支持自动通知平台发货，请手动通知平台发货!</a>';
			}else{
				$message .= '订单'.$order->order_id.'确认发货完成操作成功！,状态:'.OdOrder::$status[$old].'->'.OdOrder::$status[$order->order_status].';';
			}
			
			//写入操作日志
			UserHelper::insertUserOperationLog('delivery', "确认发货完成, 订单号: ".ltrim($order->order_id, '0'), null, $order->order_id);
		}//end of for each order
		
		// dzt20160812 合并订单的原始订单标记成功后，对合并订单修改状态
// 		\Yii::info(__FUNCTION__." order:".$order->order_id.":".print_r($mergeSFOrderIdMap,true),"file");
// 		\Yii::info(__FUNCTION__." order:".$order->order_id.":".print_r($mergeFSOrderIdMap,true),"file");
// 		\Yii::info(__FUNCTION__." order:".$order->order_id.":".print_r($successSignShipFMOrder,true),"file");
		if(!empty($successSignShipFMOrder) && !empty($mergeSFOrderIdMap)){
			foreach ($mergeSFOrderIdMap as $smOrderId=>$fmOrderIds){
				$allSignSuccess = true;
				foreach ($fmOrderIds as $fmOrderId){
					if(!in_array($fmOrderId, $successSignShipFMOrder)){
						$allSignSuccess = false;
					}
				}
				
				if($allSignSuccess){
					$smOrder = OdOrder::findOne($smOrderId);
					$old = $smOrder->order_status;
					$smOrder->order_status = OdOrder::STATUS_SHIPPED;
					$smOrder->carrier_step = OdOrder::CARRIER_FINISHED;
					$smOrder->complete_ship_time = time();	//确认发货完成时间  20171009 hqw
					if(!$smOrder->save()){
						return json_encode(array('success'=>false,'message'=>'E4 订单'.$smOrder->order_id.'订单状态修改失败!'));
					}else{
						OperationLogHelper::log('delivery', $smOrder->order_id,'确认发货完成','确认发货完成,状态:'.OdOrder::$status[$old].'->'.OdOrder::$status[$smOrder->order_status], \Yii::$app->user->identity->getFullName());
					}
					
					//操作成功返回信息
					if(in_array( $order->order_source, $no_auto_mark_shipment ) ){
// 						$message .= '<a href="'.Url::to(['/order/order/signshipped','order_id'=>$smOrder->order_id]).'" target="_blank" class="alert-link">'.'订单'.$smOrder->order_id.'确认发货完成操作成功！,状态:'.OdOrder::$status[$old].'->'.OdOrder::$status[$smOrder->order_status].','.$smOrder->order_source.'平台订单不支持自动通知平台发货，请手动通知平台发货!</a>';
						$message .= '<a class="alert-link">'.'订单'.$smOrder->order_id.'确认发货完成操作成功！,状态:'.OdOrder::$status[$old].'->'.OdOrder::$status[$smOrder->order_status].','.$smOrder->order_source.'平台订单不支持自动通知平台发货，请手动通知平台发货!</a>';
					}else{
						$message .= '订单'.$smOrder->order_id.'确认发货完成操作成功！,状态:'.OdOrder::$status[$old].'->'.OdOrder::$status[$smOrder->order_status];
					}
				}
			}
		}
		
		return json_encode(array('success'=>true,'message'=>$message));
	}
	/**
	 +----------------------------------------------------------
	 * 根据搜索条件，获取 
	 * 1、符合条件的订单列表
	 * 2、当前搜索条件（用以填充搜索区域）
	 * 3、显示订单需要的数据
	 * 
	 * 参数$is_query 不要查询订单集
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public static function getlist($query_condition,$config='carrier/carrierprocess',$is_query = true, $uid = 0, $puid = false){
		//控制合并订单不会显示
		$query_condition['order_relation'] = ['normal','sm','fs','ss'];
		
		//假如是OMS界面打开发货模块的话，没有选择平台做筛选，需要默认用平台来筛选
		if((!empty($query_condition['use_mode'])) && (empty($query_condition['selleruserid']))){
			$query_condition['selleruserid'] = $query_condition['use_mode'];
		}
		
		//这里因为卖家账号包括了平台属性所以这里修改了查询的逻辑
		if(!empty($query_condition['selleruserid'])){
			if(isset(OdOrder::$orderSource[$query_condition['selleruserid']])){
				$query_condition['order_source'] = $query_condition['selleruserid'];
				unset($query_condition['selleruserid']);
			}
		}
		
		//这里因为卖家账号包括了平台属性所以这里修改了查询的逻辑
		if(!empty($query_condition['selleruserid_combined'])){
			if(isset(OdOrder::$orderSource[$query_condition['selleruserid_combined']])){
				$query_condition['order_source'] = $query_condition['selleruserid_combined'];
				unset($query_condition['selleruserid_combined']);
			}
		}

		if($is_query == true){
			$tmp_selleruseridMap = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap($puid, $uid, true);//引入平台账号权限后
			$rt = \eagle\modules\order\apihelpers\OrderApiHelper::getOrderListByCondition($query_condition, $uid, array(), array('selleruserid_tmp'=>$tmp_selleruseridMap));
		}else{
			$rt = array('data'=>array(),'pagination'=>array());
		}

		$tmp_query_condition = $query_condition;
		
		if(!empty($query_condition['order_source'])){
			$query_condition['selleruserid'] = $query_condition['order_source'];
			unset($query_condition['order_source']);
		}
		
		$data = $rt['data'];
		$pagination = $rt['pagination'];
		#####################################1.高级搜索展开，收起##################################################################
		//高级搜索展开，收起
		if (isset($query_condition['showsearch'])){
			$showsearch = $query_condition['showsearch'];
		}else{
			$showsearch = 0;
		}
		######################################2.卖家账号#################################################################
		//卖家账号
		//$selleruseridMap = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap(false, true);//引入平台账号权限前
		$selleruseridMap = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true, true);//引入平台账号权限后
		
		if(!empty($selleruseridMap['wish'])){
			$tmp_wishM = $selleruseridMap['wish'];
			unset($selleruseridMap['wish']);
			
			$selleruseridMap['wish'] = \eagle\modules\platform\apihelpers\WishAccountsApiHelper::getWishAliasAccount($tmp_wishM);
		}
		
		$selleruserids=array();
		foreach ($selleruseridMap as $platform =>$value){
			if (!empty($value)){
				//这里假如是OMS界面调用的话其它平台的账号就不要显示
				if(!empty($query_condition['use_mode'])){
					if($query_condition['use_mode'] != $platform)
						continue;
				}
				$selleruserids[$platform] = $platform.'全部店铺';
				foreach ($value as $value_key => $value_one){
					$selleruserids[$value_key] = '--'.$value_one;
				}
			}
		}
		
		######################################3.精确筛选项#################################################################
		//精确筛选项
		$keys = [
				'order_source_order_id'=>'平台订单号',
				'sku'=>'平台SKU',
				'order_source_itemid'=>'平台物品号',
				'tracknum'=>'物流号',
				'source_buyer_user_id'=>'买家账号',
				'consignee'=>'买家姓名',
				'consignee_email'=>'买家Email',
// 				'delivery_id'=>'拣货单号',
				'order_id'=>'小老板单号',
				'root_sku'=>'配对SKU',
				'product_name'=>'产品标题',
				'prod_name_ch'=>'中文配货名',
		];
		########################################4.常用筛选###############################################################
		//常用筛选
		$custom_condition = ConfigHelper::getConfig($config);
		if (!empty($custom_condition) && is_string($custom_condition)){
			$custom_condition = json_decode($custom_condition,true);
		}
		if (!empty($custom_condition)){
			$sel_custom_condition = array_keys($custom_condition);
		}else{
			$sel_custom_condition =array();
		}
		###########################################5.国家############################################################
		//国家区域数组
		$countrys = CountryHelper::getRegionCountry();
		$country_mapping = [];
		##########################################6.仓库#############################################################
		//仓库,这里暂时把已经关闭的仓库也显示出来，因为存在历史订单使用了已经关闭的仓库
		$warehouseIdNameMap = InventoryHelper::getWarehouseOrOverseaIdNameMap(-1, -1);
		##########################################7.物流商#############################################################
		//获取已开启的物流商数组
		$allcarriers = [];
		if(isset($query_condition['carrier_type']) && !empty($query_condition['carrier_type'])){
			if($query_condition['carrier_type'] == 1){//货代+海外仓
				$allcarriers = CarrierApiHelper::getCarrierList(2,-1);
			}
			else if($query_condition['carrier_type'] == 2){//excel
				$allcarriers = CarrierApiHelper::getCarrierList(3,-1);
			}
			else if($query_condition['carrier_type'] == 3){//track
				$allcarriers = CarrierApiHelper::getCarrierList(4,-1);
			}
			else if($query_condition['carrier_type'] == 4){//海外
				$allcarriers = CarrierApiHelper::getCarrierList(1,-1);
			}
			else if($query_condition['carrier_type'] == 5){//货代
				$allcarriers = CarrierApiHelper::getCarrierList(0,-1);
			}
			else{
				$allcarriers = CarrierApiHelper::getCarrierList(6,-1);
			}
		}
		else{//获取物流商
			$allcarriers = CarrierApiHelper::getCarrierList(6,-1);
		}
		##########################################8.所有运输服务#############################################################
		$allshippingservices = CarrierApiHelper::getShippingServiceList(-1,-1);
		##########################################9.自定义标签#############################################################
		//tag 数据获取
		$allTagDataList = OrderTagHelper::getTagByTagID();
		$allTagList = [];
		foreach($allTagDataList as $tmpTag){
			$allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
		}
		##########################################10.自定义格式excel#############################################################
		//自定义格式excel
		$excelmodels = Helper_Array::toHashmap(Excelmodel::find()->select(['id','name'])->asArray()->all(), 'id','name');
		##########################################11.当前状态订单包含的运输服务#############################################################
		$shippingServices = [];
		//国家列表
		$countryArr = array();
		
		//当前状态订单包含的运输服务
// 		$tmp_query_condition = $query_condition;
		if(isset($tmp_query_condition['default_shipping_method_code'])){
			unset($tmp_query_condition['default_shipping_method_code']);
		}
		//将条件国家的筛选清空
		if(isset($tmp_query_condition['consignee_country_code'])){
			unset($tmp_query_condition['consignee_country_code']);
		}
		//将条件国家的筛选清空
		if(isset($tmp_query_condition['selected_country_code'])){
			unset($tmp_query_condition['selected_country_code']);
		}
		
		//根据某些字段groupBy
		$moreGroup = array('default_shipping_method_code','consignee_country_code','consignee_country');

		$shippingServicesList = OrderApiHelper::getShippingMethodCodeByCondition($tmp_query_condition, $moreGroup);
		
		foreach ($shippingServicesList as $one_ship){
			$code = @$one_ship['default_shipping_method_code'];
			$shippingServices[$code] = @$allshippingservices[$code];
			
			$countryArr[$one_ship['consignee_country_code']] = $one_ship['consignee_country'];
		}
		
		//清空为空的数据
		if(!empty($countryArr)){
			foreach ($countryArr as $key => $value) {
				if (empty($value)) {
					unset($countryArr[$key]);
				}
			}
		}
		
		//ebay 获取当前  check out 状态
		$orderCheckOutList = [];
		
		$OrderSourceOrderIdList=array();
		foreach ($data as $tmp_order){
			if($tmp_order->order_source == 'ebay'){
				$OrderSourceOrderIdList[] = $tmp_order->order_source_order_id;
			}
		}
		if(!empty($OrderSourceOrderIdList)){
			$orderCheckOutList = OrderApiHelper::getEbayCheckOutInfo($OrderSourceOrderIdList);
		}
		
		##########################################12.当前状态订单包含的运输服务#############################################################
		//可打印模式
		$printMode = [];
		if(isset($query_condition['default_shipping_method_code']) && !empty($query_condition['default_shipping_method_code'])){
			$printMode = CarrierOpenHelper::getShippingServicePrintMode($query_condition['default_shipping_method_code']);
		}
		##########################################13.开启的仓库数#############################################################
		$warehouseCount = Warehouse::find()->where('is_active = :is_active',[':is_active'=>'Y'])->count();
		return ['orders'=>$data,
				'pagination'=>$pagination,
				'showsearch'=>$showsearch,
				'selleruserids'=>$selleruserids,
				'keys'=>$keys,
				'custom_condition'=>$custom_condition,
				'sel_custom_condition'=>$sel_custom_condition,
				'countrys'=>$countrys,
				'country_mapping'=>$country_mapping,
				'warehouseIdNameMap'=>$warehouseIdNameMap,
				'allcarriers'=>$allcarriers,
				'allshippingservices'=>$allshippingservices,
				'shippingServices'=>$shippingServices,//当前状态订单包含的运输服务
				'all_tag_list'=>$allTagList,
				'excelmodels'=>$excelmodels,
				'printMode'=>$printMode,
				'query_condition'=>$query_condition,
				'warehouseCount'=>$warehouseCount,
				'countryArr'=>$countryArr,
				'orderCheckOutList'=>$orderCheckOutList
				];
	}
	/**
	 +----------------------------------------------------------
	 * api标签打印
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDoprintapi(){
		if(isset($_GET['orders'])&&!empty($_GET['orders'])){
			$orders	=	$_GET['orders'];
		}else{
			echo "错误：未传入订单号(send none order)";die;
		}
		$is_generate_pdf = empty($_GET['is_generate_pdf']) ? 0 : 1;  //是否生成pdf
		
		//获取上次操作时间 S
		$order_md5_str = md5($orders);
		
		$user=\Yii::$app->user->identity;
		$puid = $user->getParentUid();
		
		$classification = "Tracker_AppTempData";
		$key = date("Y-m-d").'_print_api_'.$puid.'_'.$order_md5_str;
		
		$lastSetCarrierTypeTime = RedisHelper::RedisGet($classification, $key);
		
		if(!empty($lastSetCarrierTypeTime) && ((time()-(int)$lastSetCarrierTypeTime) < 5)){
			$tmp_result = array();
			$tmp_result['result'] = array('error' => 1, 'data' => '', 'msg' => '操作过于频繁，请稍后再试e1');;
			$tmp_result['carrier_name'] = "";
		
			return $this->render('doprint2',['data'=>$tmp_result]);
		}
		
		RedisHelper::RedisSet($classification, $key, time());
		//获取上次操作时间 E
		
		$orders = rtrim($orders,',');
		$tmp_orderlist = OdOrder::find()->where("order_id in ({$orders})")->all();
// 		$orderlist
		
		$order_arr = explode(',', $orders);
		
		//定义排序
		$orderlist = array();
			
		foreach ($order_arr as $order_arr_one){
			foreach ($tmp_orderlist as $tmp_orderlist_one){
				if($order_arr_one == $tmp_orderlist_one['order_id']){
					$orderlist[] = $tmp_orderlist_one;
					break;
				}
			}
		}
		
		//将数据通过接口上传
		$carrier = SysCarrier::findOne($orderlist[0]['default_carrier_code']);
		if(empty($carrier)){
			echo "can't find this carrier";die;
		}
		if($carrier->carrier_type){
			$class_name = '\common\api\overseaWarehouseAPI\\'.$carrier->api_class;
		}else{
			$class_name = '\common\api\carrierAPI\\'.$carrier->api_class;
		}
		$arr = array();
		 
		//处理成接口需要的数据
		foreach($orderlist as $v){
			$arr[]['order']=$v;
		}
		 
		if($carrier->api_class == 'LB_RTBCOMPANYCarrierAPI'){
			//对接软通宝所属物流
			$interface = new $class_name($carrier->carrier_code);
		}
		else{
			$interface = new $class_name;
		}
		 
		$result['result'] = $interface->doPrint($arr);
		 
		if(isset($_GET['ems'])&&!empty($_GET['ems'])){
			$result['carrier_name'] = $_GET['ems'];
		}else{
			$result['carrier_name'] = "";
		}
		
		//添加log
		\Yii::info('actionDoprintapi,puid:'.$puid.',order_ids:'.$orders.' '.json_encode($result),"carrier_api");
		
		//只生成pdf
		if($is_generate_pdf && !empty($orderlist[0]['order_id']) && !empty($result['result']['data']['pdfUrl'])){
			$uid = \Yii::$app->user->id;
			$key = $uid.'_'.ltrim($orderlist[0]['order_id'],'0');
		
			$redis_val['url'] = $result['result']['data']['pdfUrl'];
			$redis_val['carrierName'] = $carrier->carrier_name;
			$redis_val['time'] = time();
			RedisHelper::RedisSet('CsPrintPdfUrl', $key, json_encode($redis_val));
		
			return [];
		}
		
		$this->layout = 'carrier';
		if(in_array($carrier->api_class, ['LB_IEUBNewCarrierAPI','LB_WISHYOUCarrierAPI','LB_LINLONGCarrierAPI','LB_DONGGUANEMSCarrierAPI'])){
			if($result['result']['error']){
				return $this->render('doprint2',['data'=>$result]);
			}else{
				//国际EUB访问速度不能过快，而且Headers信息不能被Frame包住
				if($carrier->api_class == 'LB_IEUBNewCarrierAPI'){
					usleep(600000);
				}else{
					usleep(300000);
				}
				$this->redirect($result['result']['data']['pdfUrl']);
			}
		}
		else if(in_array($carrier->api_class, ['LB_AIPAQICarrierAPI'])){
			if($result['result']['error']){
				return $this->render('doprint2',['data'=>$result]);
			}else{
				print_r($result['result']['data']);
			}
		}
		else if(in_array($carrier->api_class, ['LB_ANJUNCarrierAPI','LB_YITONGGUANCarrierAPI','LB_TAIJIACarrierAPI']) && !empty($result['result']['data']['type'])){
			if($result['result']['error']){
				return $this->render('doprint2',['data'=>$result]);
			}else{
				//国际EUB访问速度不能过快，而且Headers信息不能被Frame包住
				usleep(300000);
				$this->redirect($result['result']['data']['pdfUrl']);
			}
		}
		else{
			return $this->render('doprint2',['data'=>$result]);
		}
	}
	/**
	 +----------------------------------------------------------
	 * 自定义标签打印
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDoprintcustom(){
		if(isset($_GET['orders'])&&!empty($_GET['orders'])){
			$orders	=	$_GET['orders'];
		}else{
			echo "错误：未传入订单号(send none order)";die;
		}
		$is_generate_pdf = empty($_GET['is_generate_pdf']) ? 0 : 1;  //是否生成pdf
		
		$orders = rtrim($orders,',');
		$orderlist	=	OdOrder::find()->where("order_id in ({$orders})")->all();
		
		$carrierConfig = ConfigHelper::getConfig("CarrierOpenHelper/CommonCarrierConfig", 'NO_CACHE');
		$carrierConfig = json_decode($carrierConfig,true);
		
		if(!isset($carrierConfig['label_paper_size'])){
			$carrierConfig['label_paper_size'] = array('val' => '100x100','template_width' => 100,'template_height' => 100);
		}
		 
		$printType = '100x100';
		if($carrierConfig['label_paper_size']['val'] == '210x297'){
			$printType = 'A4';
		}
		 
		AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/doprint2");
		$puid = \Yii::$app->user->identity->getParentUid();
		$orders = rtrim($orders,',');
		$orderlist	=	OdOrder::find()->where("order_id in ({$orders})")->all();
		if(empty($orderlist)){
			echo "can't find this order";die;
		}
		$shippingServece_obj = SysShippingService::findOne(['id'=>$orderlist[0]['default_shipping_method_code']]);
		if(empty($shippingServece_obj)){
			echo "can't find this shippingservice";die;
		}
		
// 		if(empty($shippingServece_obj->custom_template_print)){
// 			$shippingServece_obj->custom_template_print = empty($shippingServece_obj->print_params['label_custom']) ? array() : $shippingServece_obj->print_params['label_custom'];
// 		}

		if(!empty($shippingServece_obj->print_params['label_custom'])){
			$shippingServece_obj->custom_template_print = $shippingServece_obj->print_params['label_custom'];
		}
		
		if(empty($shippingServece_obj->custom_template_print)){
			echo "请选选择打印某种地址单";die;
		}
		
		$pageHeight = 100;
		$pageWidth = 100;
		
		foreach ($shippingServece_obj->custom_template_print as $type=>$one){
			if (empty($one)){continue;}
			$tmp_template = CrTemplate::findOne($one);
			
			if(count($tmp_template) > 0){
				$pageHeight = $tmp_template['template_height'];
				$pageWidth = $tmp_template['template_width'];
			}
		}
		
// 		$this->layout = 'carrier';
		$this->layout='/mainPrint';
		$html = $this->render('doprintcustom',['data'=>$orderlist,'shippingService'=>$shippingServece_obj,'carrierConfig'=>$carrierConfig]);
		$result = Helper_Curl::post("http://120.55.86.71/wkhtmltopdf.php",['html'=>$html,'uid'=>$puid,'pringType'=>$printType,'pageHeight'=>$pageHeight,'pageWidth'=>$pageWidth]);// 打A4还是热敏纸
		if(false !== $result){
			$rtn = json_decode($result,true);
			//     			print_r($rtn) ;
			if(1 == $rtn['success']){
				$response = Helper_Curl::get($rtn['url']);
				$pdfUrl = \eagle\modules\carrier\helpers\CarrierAPIHelper::savePDF($response, $puid, md5("wkhtmltopdf")."_".time());
				
				//只保存pdf url到redis
				if($is_generate_pdf && !empty($orderlist[0]['order_id'])){
					$uid = \Yii::$app->user->id;
					$key = $uid.'_'.ltrim($orderlist[0]['order_id'],'0');
					$redis_val['url'] = $pdfUrl;
					$redis_val['carrierName'] = $shippingServece_obj->carrier_name;
					$redis_val['time'] = time();
					RedisHelper::RedisSet('CsPrintPdfUrl', $key, json_encode($redis_val));
						
					return [];
				}
				
				$this->redirect($pdfUrl);
			}else{
				return "打印出错，请联系小老板客服。";
			}
		}else{
			return "请重试，如果再有问题请联系小老板客服。";
		}
	}
	/**
	 +----------------------------------------------------------
	 * 高仿标签打印
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		zwd 		2016/03/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDoprint(){
		$do_custom_print = false;
		if(isset($_GET['orders'])&&!empty($_GET['orders'])){
			$orders	=	$_GET['orders'];
		}else{
			echo "错误：未传入订单号(send none order)";die;
		}
		 
		$carrierConfig = ConfigHelper::getConfig("CarrierOpenHelper/CommonCarrierConfig", 'NO_CACHE');
		$carrierConfig = json_decode($carrierConfig,true);
		
		if(!isset($carrierConfig['label_paper_size'])){
			$carrierConfig['label_paper_size'] = array('val' => '100x100','template_width' => 100,'template_height' => 100);
		}
		 
		$printType = '100x100';
		if($carrierConfig['label_paper_size']['val'] == '210x297'){
			$printType = 'A4';
		}
		 
		AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/doprint2");
		$puid = \Yii::$app->user->identity->getParentUid();
		$orders = rtrim($orders,',');
		$orderlist	=	OdOrder::find()->where("order_id in ({$orders})")->all();
		$shippingServece_obj = SysShippingService::findOne(['id'=>$orderlist[0]['default_shipping_method_code']]);
		if(empty($shippingServece_obj)){
			echo "can't find this shippingservice";die;
		}
		if ($shippingServece_obj->is_custom==0 && $do_custom_print==false){
			$lable_type = array('label_address'=>'地址单','label_declare'=>'报关单','label_items'=>'配货单');

			$sysShippMethods = SysShippingMethod::find()->where(['carrier_code'=>$shippingServece_obj->carrier_code,'shipping_method_code'=>$shippingServece_obj->shipping_method_code])->one();
			
			$sysShippingMethod['print_params'] = $shippingServece_obj->print_params['label_littleboss'];
			
			$print_params = array();
			 
			//这里把shipping_method_id为0的也添加进去查找，0代表通用的模板
			$print_params['shipping_method_id'] = array($sysShippMethods->id, '0');
// 			$print_params['lable_type'] = $shippingServece_obj->print_params['label_littleboss'];
			$templateArr = array();
			$tmpLabel = array();
			
			foreach ($shippingServece_obj->print_params['label_littleboss'] as $print_paramone){
				if(in_array($print_paramone, $sysShippingMethod['print_params'])){
					$print_params['lable_type'][$print_paramone] = $lable_type[$print_paramone];
					$templateArr[$print_paramone] = '';
					$tmpLabel[] = $lable_type[$print_paramone];
				}
			}
			
			//xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
// 			$print_params['shipping_method_id'][0] = 2209;
			
			$templateAll = CrCarrierTemplate::find()
			->where(['carrier_code'=>$shippingServece_obj->carrier_code,'shipping_method_id'=>$print_params['shipping_method_id'],'template_type'=>$tmpLabel,'is_use'=>1])
			->orderBy('template_type,country_codes desc,shipping_method_id desc')->all();
			
			foreach ($print_params['lable_type'] as $print_paramkey => $print_paramval){
				foreach ($templateAll as $templateAllone){
					if($print_paramval == $templateAllone['template_type']){
						if(empty($templateAllone['country_codes'])){
							$templateArr[$print_paramkey] = $templateAllone;
							break;
						}else
						if (strpos($templateAllone['country_codes'], $orderlist[0]->consignee_country_code) !== false){
							$templateArr[$print_paramkey] = $templateAllone;
							break;
						}
					}
				}
			}
			
			//当需要打印配货单时,高仿没有别的要求直接使用通用的面单
			if(isset($templateArr['label_items'])){
				if(empty($templateArr['label_items'])){
					$templateArr['label_items'] = CrCarrierTemplate::find()
					->where(['template_name'=>'ST配货单10cm×10cm','shipping_method_id'=>0,'template_type'=>'配货单','is_use'=>1])->one();
				}
			}
			
			$tmpIsCustom = true;
			foreach ($templateArr as $tmp1){
				if(empty($tmp1)){
					$tmpIsCustom = false;
				}
			}
			}
			
			if(($shippingServece_obj->print_type == 1) && (!empty($sysShippingMethod)) && (!empty($shippingServece_obj->print_params)) && ($tmpIsCustom)){
				//     			return $this->render('doprinthighcopy',['data'=>$orderlist,'shippingService'=>$shippingServece_obj,'carrierConfig'=>$carrierConfig,'print_params'=>$print_params,'templateArr'=>$templateArr]);
// 				$this->layout = 'carrier';
				$this->layout='/mainPrint';
				$html = $this->render('doprinthighcopy',['data'=>$orderlist,'shippingService'=>$shippingServece_obj,'carrierConfig'=>$carrierConfig,'print_params'=>$print_params,'templateArr'=>$templateArr]);
				$result = Helper_Curl::post("http://120.55.86.71/wkhtmltopdf.php",['html'=>$html,'uid'=>$puid,'pringType'=>$printType]);// 打A4还是热敏纸
				if(false !== $result){
					$rtn = json_decode($result,true);
					// 					echo $html;
					if(1 == $rtn['success']){
						$response = Helper_Curl::get($rtn['url']);
						$pdfUrl = \eagle\modules\carrier\helpers\CarrierAPIHelper::savePDF($response, $puid, md5("wkhtmltopdf")."_".time());
						$this->redirect($pdfUrl);
					}else{
						return "打印出错，请联系小老板客服。";
					}
				}else{
					return "请重试，如果再有问题请联系小老板客服。";
				}
			}else{
			}
	}
	/**
	 +----------------------------------------------------------
	 * 新增一个常用筛选条件   action
	 * @param $_REQUEST['configPath'] 该常用筛选，需要保存到的路径
	 * 
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd 	2016/2/22				初始化
	 +----------------------------------------------------------
	 **/
	public function actionAppendCustomCondition(){
		$config = "carrier/carrierprocess";
		if(isset($_REQUEST['configPath']) && !empty($_REQUEST['configPath']) && $_REQUEST['configPath'] != 'undefined'){
			$config = $_REQUEST['configPath'];
		}
		$conditionList = ConfigHelper::getConfig($config);
	
		if (is_string($conditionList)){
			$conditionList = json_decode($conditionList,true);
		}
	
		if (empty($conditionList)) $conditionList = [];
	
		if (array_key_exists($_REQUEST['custom_name'],$conditionList)){
			exit(json_encode(['success'=>false , 'message'=>$_REQUEST['custom_name'].'已经存在，请不要重复添加！']));
		}else{
			$params = $_REQUEST;
			unset($params['custom_name']);
			unset($params['order_id']);
			unset($params['sel_custom_condition']);
			foreach($params as $key=>$value){
				if (!empty($value))
					$conditionList[$_REQUEST['custom_name']][$key] = $value;
			}
			//$conditionList[$_REQUEST['custom_name']] = $params;
		}
	
		ConfigHelper::setConfig($config, json_encode($conditionList));
		exit(json_encode(['success'=>true , 'message'=>'']));
	}//end of actionAppendCustomCondition
	
	/**
	 +----------------------------------------------------------
	 * 一体化面单入口
	 *
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2016/03/21				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDoprintIntegrationLabel(){
		if(isset($_GET['orders'])&&!empty($_GET['orders'])){
			$orders	=	$_GET['orders'];
		}else{
			echo "错误：未传入订单号";die;
		}
		
		$timeMS1 = TimeUtil::getCurrentTimestampMS();
	
		AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/doprint-saitu");
		$puid = \Yii::$app->user->identity->getParentUid();
		$orders = rtrim($orders,',');
		$orderlists = OdOrder::find()->where("order_id in ({$orders})")->orderBy('default_carrier_code,default_shipping_method_code')->asArray()->all();
		
		$timeMS2 = TimeUtil::getCurrentTimestampMS();
		
		//是否速卖通线上发货
		$is_aliexpress_carrier = false;
		
		//这里需要把异常为1的队列状态数据清除，S
		$tmpOrderID = array();
		foreach ($orderlists as $orderlist){
			$tmpOrderID[] = $orderlist['order_id'];
			
			if($orderlist['default_carrier_code'] == 'lb_alionlinedelivery'){
				$is_aliexpress_carrier = true;
			}
		}
		if(count($tmpOrderID) > 0){
			$exResult = \eagle\modules\carrier\helpers\CarrierAPIHelper::updateAbnormalCarrierLabel($puid, $tmpOrderID);
		}
		//这里需要把异常为1的队列状态数据清除，E
		
		$timeMS3 = TimeUtil::getCurrentTimestampMS();
		
		//当用户的PDF生成有异常或未执行时，马上运行
		$orderUnCarrierLabelLists = CarrierUserLabel::find()->select(['id'])->where(['uid'=>$puid,'run_status'=>array(0,3,4)])->andWhere("order_id in ({$orders})")->asArray()->all();
		
		$timeMS4 = TimeUtil::getCurrentTimestampMS();
		
		if(count($orderUnCarrierLabelLists) > 0){
			$rtn = \eagle\modules\carrier\helpers\CarrierAPIHelper::getCarrierLabelApiAndItemsByNow($orderUnCarrierLabelLists);
		}
		
		$timeMS5 = TimeUtil::getCurrentTimestampMS();
			
		$orderCarrierLabelLists = CarrierUserLabel::find()->where(['uid'=>$puid,'run_status'=>2])->andWhere("order_id in ({$orders})")->asArray()->all();
			
		$timeMS6 = TimeUtil::getCurrentTimestampMS();
		
		$tmpPdfArr = array();
			
		foreach ($orderlists as $orderlist){
			foreach($orderCarrierLabelLists as $orderCarrierLabelList){
				if(($orderlist['order_id'] == $orderCarrierLabelList['order_id']) && ($orderlist['customer_number'] == $orderCarrierLabelList['customer_number'])){
					$tmpPdfArr[] = \eagle\modules\carrier\helpers\CarrierAPIHelper::getPdfPathString(false).$orderCarrierLabelList['merge_pdf_file_path'];
				}
			}
		}
		
		$timeMS7 = TimeUtil::getCurrentTimestampMS();
			
		$result = ['error'=>1,'data'=>'','msg'=>''];
			
		if((!empty($tmpPdfArr)) && (count($orderlists) == count($tmpPdfArr))){
			if(count($tmpPdfArr) == 1){
				$pdfmergeResult['success'] = true;
				$url = Yii::$app->request->hostinfo.str_replace(\eagle\modules\carrier\helpers\CarrierAPIHelper::getPdfPathString(false), "", $tmpPdfArr[0]);
			}else{
				$pathPDF = \eagle\modules\carrier\helpers\CarrierAPIHelper::createCarrierLabelDir();
				$tmpName = $puid.'_summerge_'.rand(10,99).time().'.pdf';
				$pdfmergeResult = PDFMergeHelper::PDFMerge($pathPDF.'/'.$tmpName , $tmpPdfArr);
					
				$url = Yii::$app->request->hostinfo.str_replace(\eagle\modules\carrier\helpers\CarrierAPIHelper::getPdfPathString(false), "", $pathPDF).'/'.$tmpName;
			}
			
			$timeMS8 = TimeUtil::getCurrentTimestampMS();
	
// 			\Yii::info('actionDoprintSaitu'.$puid.''.$url, "file");
	
			if($pdfmergeResult['success'] == true){
				$result['data'] = ['pdfUrl'=>$url];
				$result['error'] = 0;
			}else{
				$result['msg'] = $pdfmergeResult['message'];
				$result['error'] = 1;
			}
			
			$timeMS9 = TimeUtil::getCurrentTimestampMS();
			\Yii::info('ShowPrintPdf_0411:'.'time9-8:'.($timeMS9-$timeMS8).'time8-7:'.($timeMS8-$timeMS7).'time7-6:'.($timeMS7-$timeMS6)
					.'time6-5:'.($timeMS6-$timeMS5).'time5-4:'.($timeMS5-$timeMS4).'time4-3:'.($timeMS4-$timeMS3).'time3-2:'.($timeMS3-$timeMS2)
					.'time2-1:'.($timeMS2-$timeMS1), "carrier_api");
		}else{
			if($is_aliexpress_carrier == true){
				$result['msg'] = '速卖通线上发货需要先获取跟踪号才可以打印，如果获取了请等两分钟后再试!';
			}else{
				$result['msg'] = '这部分订单还没有生成完毕，请等待后台再试';
			}
		}
			
		$this->layout = 'carrier';
		return $this->render('doprint2',['data'=>array('result'=>$result,'carrier_name'=>'')]);
	}
	
	//新的一体化面单入口
	public function actionDoprintIntegrationLabel_muti(){
		$result = ['error'=>1,'data'=>'','msg'=>''];
		
		if(isset($_GET['orders'])&&!empty($_GET['orders'])){
			$orders	= $_GET['orders'];
		}else{
			echo "错误：未传入订单号";die;
		}
		
		$timeMS1 = TimeUtil::getCurrentTimestampMS();
	
		$puid = \Yii::$app->user->identity->getParentUid();
		$orders = rtrim($orders,',');
		$order_arr=explode(',', $orders);
		
		if(count($order_arr) > 0){
			$result_by_now = \eagle\modules\carrier\helpers\CarrierAPIHelper::getCarrierLabelApiAndItemsByNow_1($order_arr, $puid);
		}
		
		$tmpPdfArr = array();
		
		if($result_by_now['error'] == 0){
			foreach ($result_by_now['data'] as $result_by_now_file_path){
				$tmpPdfArr[] = \eagle\modules\carrier\helpers\CarrierAPIHelper::getPdfPathString(false).$result_by_now_file_path;
			}
		}
		
		$timeMS2 = TimeUtil::getCurrentTimestampMS();
		
// 		print_r($result_by_now);
// 		exit;
		
		if((!empty($tmpPdfArr)) && (count($order_arr) == count($tmpPdfArr))){
			if(count($tmpPdfArr) == 1){
				$pdfmergeResult['success'] = true;
				$url = Yii::$app->request->hostinfo.str_replace(\eagle\modules\carrier\helpers\CarrierAPIHelper::getPdfPathString(false), "", $tmpPdfArr[0]);
			}else{
				$pathPDF = \eagle\modules\carrier\helpers\CarrierAPIHelper::createCarrierLabelDir();
				$tmpName = $puid.'_summerge_'.rand(10,99).time().'.pdf';
				$pdfmergeResult = PDFMergeHelper::PDFMerge($pathPDF.'/'.$tmpName , $tmpPdfArr);
					
				$url = Yii::$app->request->hostinfo.str_replace(\eagle\modules\carrier\helpers\CarrierAPIHelper::getPdfPathString(false), "", $pathPDF).'/'.$tmpName;
			}
	
			if($pdfmergeResult['success'] == true){
				$result['data'] = ['pdfUrl'=>$url];
				$result['error'] = 0;
			}else{
				$result['msg'] = $pdfmergeResult['message'];
				$result['error'] = 1;
			}
		}else{
			$result['msg'] = $result_by_now['msg'];
		}
		
		$timeMS3 = TimeUtil::getCurrentTimestampMS();
		\Yii::info('pdf_print:'.' time3-2:'.($timeMS3-$timeMS2).' time2-1:'.($timeMS2-$timeMS1).' time3-1:'.($timeMS3-$timeMS1)
				.' order_count:'.(count($order_arr)).' order_json:'.(json_encode($order_arr)), "carrier_api");
		
		$this->layout = 'carrier';
		return $this->render('doprint2',['data'=>array('result'=>$result,'carrier_name'=>'')]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据用户设置的打印需求打印物流标签
	 *
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2016/06/27				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExternalDoprint(){
// 		AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrierprocess/ExternalDoprint");
		
// 		$_REQUEST['order_ids'] = '2819,2810,4197';//4197,

		//记录最近运行的订单md5值
		$order_md5_str = '';
		
		if(isset($_REQUEST['order_ids'])){
			$order_ids = explode(',',$_REQUEST['order_ids']);
// 			$order_ids = $_GET['order_ids'];

			$order_md5_str = md5($_REQUEST['order_ids']);
			
			$tmp_orderlist = OdOrder::find()->where(['in','order_id',$order_ids])->orderBy('default_shipping_method_code asc')->all();
// 			$odorders
			
			$order_arr = $order_ids;
			
			//定义排序
			$odorders = array();
				
			foreach ($order_arr as $order_arr_one){
				foreach ($tmp_orderlist as $tmp_orderlist_one){
					if($order_arr_one == $tmp_orderlist_one['order_id']){
						$odorders[] = $tmp_orderlist_one;
						break;
					}
				}
			}
			
			AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrierprocess/ExternalDoprint",array('paramstr1'=>$_REQUEST['order_ids']));
		}
		
		$is_generate_pdf = empty($_REQUEST['is_generate_pdf']) ? 0 : 1;  //是否生成pdf
		
		//获取上次操作时间 S
		$user=\Yii::$app->user->identity;
		$puid = $user->getParentUid();
		
		$classification = "Tracker_AppTempData";
		$key = date("Y-m-d").'_print_'.$puid.'_'.$order_md5_str;
		
		$lastSetCarrierTypeTime = RedisHelper::RedisGet($classification, $key);
		
		if(!empty($lastSetCarrierTypeTime) && ((time()-(int)$lastSetCarrierTypeTime) < 5)){
			$tmp_result = array();
			$tmp_result['result'] = array('error' => 1, 'data' => '', 'msg' => '操作过于频繁，请稍后再试');;
			$tmp_result['carrier_name'] = "";
			
			return $this->render('doprint2',['data'=>$tmp_result]);
		}
		
		RedisHelper::RedisSet($classification, $key, time());
		//获取上次操作时间 E
		
		$externalV = '';
		if(isset($_REQUEST['externalV'])){
			$externalV = $_REQUEST['externalV'];
		}
		
		$emslist = [];
		$is_searched = [];
		$list = [];
		
		$notMethodOrder = array();
		
		if(!empty($odorders)){
			foreach($odorders as $v){
				if(!isset($is_searched[$v->default_shipping_method_code])){
					//当没有选择过运输服务的订单直接跳过
					if(empty($v->default_shipping_method_code)){
						$notMethodOrder[] = $v->order_id;
						continue;
					}
					
					$printMode = CarrierOpenHelper::getCustomShippingServicePrintMode($v->default_shipping_method_code, $externalV);
					
					$is_searched[$v->default_shipping_method_code] = $printMode;
					
					unset($printMode);
				}
				
				//LGS默认打印高仿面单
				if($v->default_carrier_code == 'lb_LGS'){
					if(empty($is_searched[$v->default_shipping_method_code]['is_print'])){
						if(empty($is_searched[$v->default_shipping_method_code]['is_api_print']))
							$is_searched[$v->default_shipping_method_code]['is_print'] = 1;
					}
				}
				
				$priorityPrint = '';
				
				if($is_searched[$v->default_shipping_method_code]['is_custom_print'] == 1){
					$method_name = $is_searched[$v->default_shipping_method_code]['shipping_method_name'];
					$priorityPrint = 'is_custom_print';
				}else if($is_searched[$v->default_shipping_method_code]['is_xlb_print'] == 1){
					$method_name = $is_searched[$v->default_shipping_method_code]['shipping_method_name'];
					$priorityPrint = 'is_xlb_print';
				}else if($is_searched[$v->default_shipping_method_code]['is_custom_print_new'] == 1){
					$method_name = $is_searched[$v->default_shipping_method_code]['shipping_method_name'];
					$priorityPrint = 'is_custom_print_new';
				}else if($is_searched[$v->default_shipping_method_code]['is_print'] == 1){
					$method_name = $is_searched[$v->default_shipping_method_code]['shipping_method_name'].$v->consignee_country_code;
					$priorityPrint = 'is_print';
				}else{
					$method_name = $is_searched[$v->default_shipping_method_code]['shipping_method_name'];
					$priorityPrint = 'is_api_print';
					
					if($is_searched[$v->default_shipping_method_code]['is_api_print'] == 2){
						$priorityPrint = 'is_api_print_smt_2';
					}
				}
				
				$carrier_name = $is_searched[$v->default_shipping_method_code]['carrier_name'];
				//统计出该运输方式下订单数量
				isset($count_shipping_service[$method_name])?++$count_shipping_service[$method_name]:($count_shipping_service[$method_name] = 1);
				//将订单id根据运输方式分类
				if(!isset($emslist[$method_name]))$emslist[$method_name] = [];
				isset($emslist[$method_name]['order_ids'])?'':$emslist[$method_name]['order_ids'] = '';
				$emslist[$method_name]['order_ids'] .= $v->order_id.',';
				$emslist[$method_name]['display_name'] = $carrier_name.' >>> '.$method_name;
				$emslist[$method_name]['priorityPrint'] = $priorityPrint;
			}
			
			foreach($emslist as $k=>$v){
				$name = $v['display_name'].' X '.$count_shipping_service[$k];
				$list[$name] = array('order_ids'=>$v['order_ids'], 'priorityPrint'=>$v['priorityPrint']);
			}
		}
		
		$result = array();
		$result['emslist']=$list;
		$result['notMethodOrder'] = $notMethodOrder;
		
		if((count($result['emslist']) > 1) || (!empty($notMethodOrder))){
			return $this->render('createPDF2print',['data'=>$result]);
		}else if(count($result['emslist']) == 1){
			//只需生成pdf
			if($is_generate_pdf){
				$_GET['orders'] = $result['emslist'][$name]['order_ids'];
				$_GET['ems'] = $name;
				$_GET['v1'] = rand(10,99);
				$_GET['is_generate_pdf'] = $is_generate_pdf;
				
				if($result['emslist'][$name]['priorityPrint'] == 'is_custom_print')
					self::actionDoprintcustom();
				else if($result['emslist'][$name]['priorityPrint'] == 'is_print')
					self::actionDoprint();
				else if($result['emslist'][$name]['priorityPrint'] == 'is_xlb_print')
					self::actionDoprintNew();
				else if($result['emslist'][$name]['priorityPrint'] == 'is_custom_print_new')
					self::actionDoprintcustomNew();
				else if($result['emslist'][$name]['priorityPrint'] == 'is_api_print_smt_2')
					self::actionDoprintapi();
				else
					self::actionDoprintapi();
			}
			else{
				if($result['emslist'][$name]['priorityPrint'] == 'is_custom_print')
					return $this->redirect('/carrier/carrierprocess/doprintcustom?orders='.$result['emslist'][$name]['order_ids'].'&ems='.$name.'&v1='.rand(10,99));
				else if($result['emslist'][$name]['priorityPrint'] == 'is_print')
					return $this->redirect('/carrier/carrierprocess/doprint?orders='.$result['emslist'][$name]['order_ids'].'&ems='.$name.'&v1='.rand(10,99));
				else if($result['emslist'][$name]['priorityPrint'] == 'is_xlb_print')
					return $this->redirect('/carrier/carrierprocess/doprint-new?orders='.$result['emslist'][$name]['order_ids'].'&ems='.$name.'&v1='.rand(10,99));
				else if($result['emslist'][$name]['priorityPrint'] == 'is_custom_print_new')
					return $this->redirect('/carrier/carrierprocess/doprintcustom-new?orders='.$result['emslist'][$name]['order_ids'].'&ems='.$name.'&v1='.rand(10,99));
				else if($result['emslist'][$name]['priorityPrint'] == 'is_api_print_smt_2')
					return $this->redirect('/carrier/carrierprocess/doprintapi?orders='.$result['emslist'][$name]['order_ids'].'&ems='.$name.'&v1='.rand(10,99));
				else
					return $this->redirect('/carrier/carrierprocess/doprintapi?orders='.$result['emslist'][$name]['order_ids'].'&ems='.$name.'&v1='.rand(10,99));
			}
			
		}else{
			return $this->render('createPDF2print',['data'=>$result]);
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 小老板 新的高仿标签打印
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		hqw 		2016/10/22				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDoprintNew(){
		$is_generate_pdf = empty($_GET['is_generate_pdf']) ? 0 : 1;  //是否生成pdf
		
		if(isset($_GET['orders'])&&!empty($_GET['orders'])){
			$orders	=	$_GET['orders'];
		}else{
			echo "错误：未传入订单号(send none order)";die;
		}
		$orders = rtrim($orders,',');
		$orderlist	=	OdOrder::find()->where("order_id in ({$orders})")->all();
		
		if(count($orderlist) > 0){
			foreach ($orderlist as $tmp_order){
				if(($tmp_order->default_carrier_code == 'lb_seko') && (empty($tmp_order->tracking_number))){
					$tmpResult = array();
					$tmpResult['carrier_name'] = empty($_GET['ems']) ? '' : $_GET['ems'];
					$tmpResult['result'] = array('error' => 1, 'data' => '', 'msg' => 'Seko必须先有跟踪号才可以打印面单');
						
					return $this->render('doprint2',['data'=>$tmpResult]);
				}
			}
		}
		
		$result = PrintPdfHelper::getHighcopyFormatPDF($orderlist, $is_generate_pdf);
		
		if(isset($result['error'])){
			$tmpResult = array();
			$tmpResult['carrier_name'] = empty($_GET['ems']) ? '' : $_GET['ems'];
			$tmpResult['result'] = array('error' => 1, 'data' => '', 'msg' => $result['msg']);
			
			return $this->render('doprint2',['data'=>$tmpResult]);
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 小老板 新的自定义标签打印
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		hqw 		2017/01/09				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDoprintcustomNew(){
		if(isset($_GET['orders'])&&!empty($_GET['orders'])){
			$orders	=	$_GET['orders'];
		}else{
			echo "错误：未传入订单号(send none order)";die;
		}
		$is_generate_pdf = empty($_GET['is_generate_pdf']) ? 0 : 1;  //是否生成pdf
		
		$orders = rtrim($orders,',');
		$tmp_orderlist	=	OdOrder::find()->where("order_id in ({$orders})")->all();
// 		$orderlist
		
		$order_arr = explode(',', $orders);
		
		//定义排序
		$orderlist = array();
			
		foreach ($order_arr as $order_arr_one){
			foreach ($tmp_orderlist as $tmp_orderlist_one){
				if($order_arr_one == $tmp_orderlist_one['order_id']){
					$orderlist[] = $tmp_orderlist_one;
					break;
				}
			}
		}
		
		$result = PrintPdfHelper::getCustomFormatPDF($orderlist, '', array(), $is_generate_pdf);
		
		if(isset($result['error'])){
			$tmpResult = array();
			$tmpResult['carrier_name'] = empty($_GET['ems']) ? '' : $_GET['ems'];
			$tmpResult['result'] = array('error' => 1, 'data' => '', 'msg' => $result['msg']);
				
			return $this->render('doprint2',['data'=>$tmpResult]);
		}
	}
	
	//用户自定义打印拣货单功能
	public function actionThermalPickingPrint(){
		if(isset($_GET['orders'])&&!empty($_GET['orders'])){
			$orders = $_GET['orders'];
		}else{
			echo "错误：未传入订单号(send none order)";die;
		}
		
		$orders = rtrim($orders,',');
		$tmp_orderlist = OdOrder::find()->where("order_id in ({$orders})")->all();
// 		$orderlist
		
		$order_arr = explode(',', $orders);
		
		//定义排序
		$orderlist = array();
			
		foreach ($order_arr as $order_arr_one){
			foreach ($tmp_orderlist as $tmp_orderlist_one){
				if($order_arr_one == $tmp_orderlist_one['order_id']){
					$orderlist[] = $tmp_orderlist_one;
					break;
				}
			}
		}
		
		$result = PrintPdfHelper::getThermalPickingFormatPDF($orderlist);
		
		if(isset($result['error'])){
			$tmpResult = array();
			$tmpResult['carrier_name'] = empty($_GET['ems']) ? '' : $_GET['ems'];
			$tmpResult['result'] = array('error' => 1, 'data' => '', 'msg' => $result['msg']);
		
			return $this->render('doprint2',['data'=>$tmpResult]);
		}
	}
	
	//检查是否菜鸟的订单
	public function actionCheckCainiao(){
		$result = array('code'=>0, 'msg'=>'');

		$order_ids = explode(',',$_REQUEST['order_ids']);
		$shipping_list = OdOrder::find()->select('default_carrier_code,default_shipping_method_code')
			->where(['in','order_id',$order_ids])->groupBy('default_carrier_code,default_shipping_method_code')->asArray()->all();
		
		if(count($shipping_list) == 0){
			$result['msg'] = '请先选择订单';
			exit(json_encode($result));
		}
		
		$shipping_smt_list = array();
		
		foreach ($shipping_list as $shipping_list_V){
			if($shipping_list_V['default_carrier_code'] == 'lb_alionlinedelivery'){
				$shipping_smt_list[$shipping_list_V['default_shipping_method_code']] = $shipping_list_V['default_shipping_method_code'];
			}
		}
		
		if(count($shipping_smt_list) == 0){
			$result['code'] = 1;
			exit(json_encode($result));
		}
		
		$is_smt_shipping_print = false;
		
		foreach ($shipping_smt_list as $shipping_smt_list_V){
			$printMode = CarrierOpenHelper::getCustomShippingServicePrintMode($shipping_smt_list_V);
			
			$priorityPrint = '';
			
			if($printMode['is_custom_print'] == 1){
				$priorityPrint = 'is_custom_print';
			}else if($printMode['is_xlb_print'] == 1){
				$priorityPrint = 'is_xlb_print';
			}else if($printMode['is_custom_print_new'] == 1){
				$priorityPrint = 'is_custom_print_new';
			}else if($printMode['is_print'] == 1){
				$priorityPrint = 'is_print';
			}else{
				$priorityPrint = 'is_api_print';
				
				if(($printMode['is_api_print'] == 2) || ($printMode['is_api_print'] == 3)){
					$priorityPrint = 'is_api_print_smt_2';
				}
			}
			
			if($priorityPrint == 'is_api_print_smt_2'){
				$is_smt_shipping_print = true;
				if(count($shipping_list) != count($shipping_smt_list)){
					$result['msg'] = '线上发货云打印面单不能跟其它面单同时打印，需要分开渠道打印';
					exit(json_encode($result));
				}
			}
		}
		
		if($is_smt_shipping_print == false){
			$result['code'] = 1;
		}else{
			$result['code'] = 2;
		}
		
		exit(json_encode($result));
	}
	
	//获取云打印数据
	public function actionGetCloudPrintData(){
		$result = array('code'=>0, 'msg'=>'', 'data' => array());
		
		$orderIds = explode(',',$_REQUEST['orderIds']);
		$order_lists = OdOrder::find()->where(['in','order_id', $orderIds])->all();
		
		$print_order_lists = array();
		
		$print_order_data = array();
		
		if(count($order_lists) == 0){
			$result['msg'] = '请重先选择订单再打印';
			exit(json_encode($result));
		}
		
		foreach ($order_lists as $order){
			if(!isset($print_order_lists[$order['selleruserid']])){
				$print_order_lists[$order['selleruserid']] = array();
			}
			
			$checkResult = \eagle\modules\carrier\helpers\CarrierAPIHelper::validate(1, 1, $order);
			$shipped = $checkResult['data']['shipped'];
			
			if(empty($shipped->tracking_number)){
				$result['msg'] = '平台订单号：'.$order->order_source_order_id.'请先获取跟踪号再打印';
				exit(json_encode($result));
			}
			
			if($shipped->addtype != '物流API'){
				$result['msg'] = '平台订单号：'.$order->order_source_order_id.' 不是通过API上传，不能进行打印';
				exit(json_encode($result));
			}
			
			$items_arr = array();
			
			foreach ($order->items as $tmp_item){
				$tmpSku = $tmp_item->root_sku;
				$tmp_product = \eagle\modules\catalog\apihelpers\ProductApiHelper::getProductInfo($tmpSku);
				
				if($tmp_item->delivery_status == 'ban'){
					continue;
				}
				
				$tmp_product_attributes = '';
				if (!empty($tmp_item->product_attributes)){
					$tmpProdctAttrbutes = explode(' + ' ,$tmp_item->product_attributes );
					if (!empty($tmpProdctAttrbutes)){
						$tmp_product_attributes = "\n\t";
						foreach($tmpProdctAttrbutes as $_tmpAttr){
							$tmp_product_attributes .= $_tmpAttr;
						}
					}
				}
				
				$tmp_photo_primary = $tmp_item->photo_primary;
				if($tmp_photo_primary == 'http://g03.a.alicdn.com/kf/images/eng/no_photo.gif'){
					$tmp_photo_primary = '';
				}
				
				$items_arr[] = array('prod_name_ch'=>$tmp_product['prod_name_ch'].' '.$tmpSku. ' * '.$tmp_item->quantity.$tmp_product_attributes , 'image_url'=>$tmp_photo_primary);
			}
			
			try {
				$items_arr[0]['prod_name_ch'] = '小老板订单号:'.$order['order_id']."\n\t".$items_arr[0]['prod_name_ch'];
				
				if(count($items_arr) > 0){
					$tmp_count = count($items_arr)-1;
					$items_arr[$tmp_count]['prod_name_ch'] .= "\n\t".$order['desc'];
				}
			}catch(\Exception $ex){}
			
			$print_order_lists[$order['selleruserid']][] = array('tracking_number'=>$shipped->tracking_number,'items'=>$items_arr);
		}
		
		//获取打印格式
		$userShippingSevice = SysShippingService::find()->select(['carrier_code','shipping_method_code','third_party_code',
				'print_params','is_custom','shipping_method_name','carrier_name','print_type','carrier_params'])->where(['id'=>$order->default_shipping_method_code])->asArray()->one();
		
		$userShippingSevice['carrier_params'] = unserialize($userShippingSevice['carrier_params']);
		$tmp_print_type = 2;
		
		if(!empty($userShippingSevice['carrier_params']['print_format'])){
			if($userShippingSevice['carrier_params']['print_format'] == 1){
				$tmp_print_type = 2;
			}else{
				$tmp_print_type = 3;
			}
		}
		
		if(count($print_order_lists) > 0){
			foreach ($print_order_lists as $print_order_list_K => $print_order_listV){
				unset($resultGetApiPrint);
				unset($params_smt_account);
				$params_smt_account = array();
				
				if(count($print_order_listV) > 0){
					foreach ($print_order_listV as $print_order_listV_V){
						if($tmp_print_type == 3){
							unset($tmp_extendData);
							$tmp_extendData = array();

							foreach ($print_order_listV_V['items'] as $tmp_item_Val){
								$tmp_extendData[] = array('imageUrl'=>$tmp_item_Val['image_url'], 'productDescription'=>$tmp_item_Val['prod_name_ch']);
							}
							
							$tmp_extendData = json_encode($tmp_extendData);
							
							$params_smt_account[] = array('extendData'=>$tmp_extendData,'internationalLogisticsId'=>$print_order_listV_V['tracking_number']);
						}else{
							$params_smt_account[] = array('internationalLogisticsId'=>$print_order_listV_V['tracking_number']);
						}
					}
				}
				
				$params_smt_account = json_encode($params_smt_account);
				
				$params_smt = array('printDetail'=>(($tmp_print_type == 3) ? 'true' : 'false'), 'warehouseOrderQueryDTOs' => ($params_smt_account));
				
				$resultGetApiPrint = \common\api\carrierAPI\LB_ALIONLINEDELIVERYCarrierAPI::getAliexpressCloudPrintInfo($print_order_list_K, $params_smt);
				
// 				print_r($resultGetApiPrint);
// 				exit;
				
				if($resultGetApiPrint['Ack'] == false){
					$result['msg'] = $resultGetApiPrint['error'];
					exit(json_encode($result));
				}
				
				if(!isset($resultGetApiPrint['printInfo']['success'])){
					$result['msg'] = '速卖通接口异常，请联系小老板客服咨询e1_1';
					exit(json_encode($result));
				}
				
				if($resultGetApiPrint['printInfo']['success'] == false){
					$result['msg'] = $resultGetApiPrint['printInfo']['errorCode'];
					exit(json_encode($result));
				}
				
				if(!isset($resultGetApiPrint['printInfo']['aeopCloudPrintDataResponseList'])){
					$result['msg'] = '速卖通接口异常，请联系小老板客服咨询e1';
					exit(json_encode($result));
				}
				
				if(count($resultGetApiPrint['printInfo']['aeopCloudPrintDataResponseList']) == 0){
					$result['msg'] = '速卖通接口异常，请联系小老板客服咨询e2';
					exit(json_encode($result));
				}
				
				foreach ($resultGetApiPrint['printInfo']['aeopCloudPrintDataResponseList'] as $tmp_aeopCloudPrintDataResponseListOne){
					if(!isset($tmp_aeopCloudPrintDataResponseListOne['cloudPrintDataList'][0])){
						$result['msg'] = '速卖通接口异常，请联系小老板客服咨询e3';
						exit(json_encode($result));
					}
					
					foreach ($tmp_aeopCloudPrintDataResponseListOne['cloudPrintDataList'] as $tmp_cloudPrintData_Val){
						unset($tmp_printData);
						$tmp_printData = json_decode($tmp_cloudPrintData_Val['printData'], true);
						$print_order_data[] = array('orderCode'=>$tmp_aeopCloudPrintDataResponseListOne['orderCode'], 'printData'=>$tmp_printData);
// 						$tmp_printData['data']['goodsInfo'] = '自定义数据';
					}
				}
			}
		}
		
		$result['code'] = 1;
		$result['data'] = $print_order_data;
		exit(json_encode($result));
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取面单url
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2017/11/07		初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetPrintUrl(){
		$ret = CarrierOpenHelper::GetPrintUrl($_REQUEST);
		
		return json_encode($ret);
	}
	/**
	 +----------------------------------------------------------
	 * 获取jumia 官方发票
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lwj		  2018/07/12		初始化
	 +----------------------------------------------------------
	 **/
	public function actionInvoiceDoprint(){
	    if(isset($_REQUEST['order_ids'])){
	        $order_ids = explode(',',$_REQUEST['order_ids']);
	        $tmp_orderlist = OdOrder::find()->where(['in','order_id',$order_ids])->all();
	        
            if(!empty($tmp_orderlist)){
                //获取站点键值
                $code2CodeMap = ['eg' => '埃及','ci' => '科特迪瓦','ma' => '摩洛哥',];
                
                //记录账号和站点
                $lazada_account_site = array();
                
                foreach ($tmp_orderlist as $order){
                    if (empty($code2CodeMap[strtolower($order->order_source_site_id)])){
                        header("content-type:text/html;charset=utf-8");
                        echo '订单:'.$order->order_id." 站点" . $order->order_source_site_id . "不是 jumia允许打印发票的站点。";
                        exit();
                    }
                   
                    if(!isset($lazada_account_site[$order->selleruserid])){
                        $lazada_account_site[$order->selleruserid] = array();
                    }
                     
                    if(!isset($lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)])){
                        $lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)] = '';
                    }
                     
                    $tmp_item_ids = $lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)];
                     
                    foreach($order->items as $item){
                        $tmp_item_ids .= empty($tmp_item_ids) ? $item->order_source_order_item_id : ','.$item->order_source_order_item_id;
                    }
                     
                    $lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)] = $tmp_item_ids;
                }
                
                //记录返回的base64字符串
                $tmp_base64_str_a = array();
                
                //循环获取lazada返回的数据
                foreach ($lazada_account_site as $lazada_account_key => $lazada_account_val){
                    foreach ($lazada_account_val as $lazada_site_key => $lazada_site_val){
                        $SLU = SaasLazadaUser::findOne(['platform_userid' => $lazada_account_key, 'lazada_site' => $lazada_site_key]);
                
                        if (empty($SLU)) {
                            header("content-type:text/html;charset=utf-8");
                            echo $lazada_account_key . " 账号不存在" .' '. $lazada_site_key.'站点不存在';
                            exit();
                        }
                
                        $lazada_config = array(
                            "userId" => $SLU->platform_userid,
                            "apiKey" => $SLU->token,
                            "countryCode" => $SLU->lazada_site
                        );
                
                        $lazada_appParams = array(
                            'OrderItemIds' => $lazada_site_val,
                            'DocumentType' => 'invoice'
                        );
                
                        $result = LazadaInterface_Helper::getOrderShippingLabel($lazada_config, $lazada_appParams);
                        
                        if ($result['success'] && $result['response']['success'] == true) { // 成功
                            $tmp_base64_str_a[] = $result["response"]["body"]["Body"]['Documents']["Document"]["File"];
                
                        } else {
                            header("content-type:text/html;charset=utf-8");
                            echo '打印失败原因：'.$result['message'];
                            exit();
                        }
                    }
                }
                
                //最终生成的HTML
                $tmp_html = '';
                
                foreach ($tmp_base64_str_a as $tmp_base64_val){
                    $tmp_html .= empty($tmp_html) ? base64_decode($tmp_base64_val) : '<hr style="page-break-after: always;border-top: 3px dashed;">'.base64_decode($tmp_base64_val);
                }
                //LGS 返回的是html代码所以直接输出即可
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body style="margin:0px;">'.$tmp_html.''.'</body>';
                exit;
            }
	    }
	}
}
?>