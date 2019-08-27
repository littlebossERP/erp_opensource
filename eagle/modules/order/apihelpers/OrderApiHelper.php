<?php
namespace eagle\modules\order\apihelpers;
use eagle\modules\order\models\OdOrder;
use eagle\modules\catalog\apihelpers\ProductApiHelper;
use eagle\modules\order\helpers\CdiscountOrderInterface;

use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\order\helpers\AliexpressOrderHelper;
use eagle\modules\order\helpers\OrderHelper;
use yii\data\Pagination;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\models\AliexpressListingDetail;
use eagle\models\AliexpressCategory;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\models\EbayCategory;
use common\api\trans\transBAIDU;
use eagle\modules\util\helpers\SysLogHelper;
use common\helpers\Helper_Array;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\catalog\models\Product;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\order\helpers\OrderListV3Helper;
/**
 +------------------------------------------------------------------------------
 * 订单模块对外接口业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class OrderApiHelper{
	static $OrderSyncShipStatus = ['P'=>'待提交','S'=>'提交中','F'=>'提交失败','C'=>'提交成功'];
	
	/**
	 * 获取一个订单的商品信息
	 * @access 	static
	 * @param	integer:	$orderId					小老板单号
	 * @param	boolean:	$isGetPickingInfo			是否获取sku的拣货信息  false:不需要，true:需要
	 * @param	integer:	$warehouseId				拣货的仓库id
	 * @param	boolean:	$isSplitBundle				是否拆分捆绑商品 默认true:拆分
	 * @return	skus
	 * 对应产品array
	 array(
	 *		0=>array(Sku=>’sk1’,’name’=>’computer’,...  Type='B'/'S'/'C', 代表(’Bundle’/'Simple'/'Config'),'qty'=>1);
	 *		1=>array(Sku=>’sk1’,’name’=>’computer’,...  Type='B'/'S'/'C', 代表(’Bundle’/'Simple'/'Config'),'qty'=>2);
	 *	)
	 * @author 	million
	 * @version 	0.1		2015.01.15
	 */
	public static function getProductsByOrder($orderId,$isSplitBundle=true,$isGetPickingInfo=false,$warehouseId=0,$getTrackingNumber = false,$getDescription = false, $productOnly = false){
		$productInfos = array();
		if($productOnly == true){
			$order = \eagle\modules\delivery\helpers\DeliveryHelper::getDeliveryOrder($orderId);
		}else{
			$order = OdOrder::findOne(['order_id'=>$orderId]);
		}
		if($getTrackingNumber || $getDescription){
			$shiped_info = OdOrderShipped::findOne(['customer_number'=>$order->customer_number,'order_id'=>$orderId]);
		}

		if ($order !== null){
			foreach ($order->items as $item){
				//cd的虚拟sku做特殊过滤处理
				if(strtolower($order->order_source)=='cdiscount'){
					if(in_array($item->sku, CdiscountOrderInterface::getNonDeliverySku())){
						continue;
					}
				}
				//当SKU不存在时，以product_name替代
				$sku = '';
				if($item->sku == null || $item->sku == '')
				    $sku = $item->product_name;
				else 
				    $sku = $item->sku;
				//以配对SKU查询商品信息
				$root_sku = empty($item->root_sku) ? '' : $item->root_sku;
				//获取sku详细数据
				$skuInfo_arr = ProductApiHelper::getSkuInfo($root_sku, $item->quantity , $isSplitBundle , $isGetPickingInfo , $warehouseId);
				if (!empty($skuInfo_arr)){
					foreach ($skuInfo_arr as $one){
						//sku去空格
						$one['sku'] = trim($one['sku']);
						$one['root_sku'] = trim($one['sku']);
						$one['order_source_order_id'] = $item->order_source_order_id;//平台来源订单号
						$one['order_item_id'] = $item->order_item_id;//od_order_item_v2 主键
						$one['order_source_itemid'] = $item->order_source_itemid;//平台在线商品id
						$one['photo_primary'] = empty($one['photo_primary'])?$item->photo_primary:$one['photo_primary'];//图片
						$one['product_attributes'] = $item->product_attributes;//属性
						$one['product_name'] = empty($one['prod_name_ch'])?$item->product_name:$one['prod_name_ch'];//标题或者配货名
						$one['order_item_sku'] = trim($item->sku);//平台拉取下来的SKU
						$one['order_item_quantity'] = $item->ordered_quantity;//平台拉取下来的数量
						$one['location_grid'] = empty($one['location_grid']) ? '' : $one['location_grid'];
						$one['delivery_status'] = $item->delivery_status;//是否能发货
						
						if($getTrackingNumber) $one['tracking_number'] = @$shiped_info->tracking_number;
						if($getDescription) $one['description'] = @$shiped_info->description;
						
						$productInfos[] = $one;
					}
					
				}else{//商品库中不存在的sku
					$sku = trim($sku);
					$productInfos[] = [
							'order_source_order_id' => $item->order_source_order_id,//平台来源订单号
							'order_item_id' => $item->order_item_id,//od_order_item_v2 主键
							'order_source_itemid' => $item->order_source_itemid,//平台在线商品id
							'photo_primary' =>$item->photo_primary,//图片
							'product_attributes' => $item->product_attributes,//属性
							'product_name' => $item->product_name,//标题或者配货名
							'order_item_sku' =>$sku,//平台拉取下来的SKU
							'order_item_quantity' => $item->ordered_quantity,//平台拉取下来的数量
							'sku' => empty($sku)?$item->order_source_itemid:$sku,//实际拣货sku
							'qty' => $item->quantity,//实际拣货数量
							'purchase_price' => 0,//参考售价，可以用来计算毛利
							'location_grid'=>'',
							'delivery_status' => $item->delivery_status,//是否能发货
							'root_sku' => '',
					]
					+(($getTrackingNumber)?['tracking_number'=>@$shiped_info->tracking_number]:[])
					+(($getDescription)?['description'=>@$shiped_info->description]:[])
					;
				}
			}
		}
		return $productInfos;
	}
	
	public static function adjustStatusCount($platform,$seller_id, $newStatus,$oldStatus,$orderid=''){
		//通知dashboard计数器需要知道
		$puid=\Yii::$app->subdb->getCurrentPuid();
		 
		$comment = "This seller $seller_id :order $orderid status from $oldStatus changed to $newStatus ";
		//echo "\n $comment \n";	//liang test
		DashBoardStatisticHelper::OMSStatusAdd2($puid, $platform, $seller_id, $newStatus, 1,$comment);
		DashBoardStatisticHelper::OMSStatusAdd2($puid, $platform, $seller_id, $oldStatus,-1,$comment);
		DashBoardHelper::checkOrderStatusRedisCompareWithDbCount($puid, $platform, $oldStatus, $newStatus, "function adjustStatusCount");
	}
	
	
	/**
	 * 保存一个订单的包含的所有商品
	 * @access 	static
	 * @param	integer:	$orderId					小老板单号
	 * @return	boolean
	 * @author 	million
	 * @version 	0.1		2015.01.15
	 */
	public static function saveOrderGoods($orderId){
		$skuInfo_arr = self::getProductsByOrder($orderId);
		return OrderHelper::saveOrderGoods($orderId, $skuInfo_arr);
	}
	
	
	/**
	 * 暂停发货
	 * @access 	static
	 * @param	array:	$orderIdList					小老板单号数组
	 * @param	string: $module							模块
	 * @param	string:	$action 						执行动作
	 * @return	boolean
	 * @author 	million
	 * @version 	0.1		2015.01.15
	 */
	public static function suspendOrders($orderIdList ,$module='order',$action='OMS暂停发货'){
		return OrderHelper::suspendOrders($orderIdList,$module,$action);
	}
	
	/**
	 * 标记缺货
	 * @access 	static
	 * @param	array:	$orderIdList					小老板单号数组
	 * @param	string: $module							模块
	 * @param	string:	$action 						执行动作
	 * @return	boolean
	 * @author 	million
	 * @version 	0.1		2015.01.15
	 */
	public static function setOrderOutOfStock($orderIdList ,$module='order',$action='OMS标记缺货'){
		return OrderHelper::setOrderOutOfStock($orderIdList,$module,$action);
	}
	
	/**
	 * 打回已付款
	 * @access 	static
	 * @author 	hqw
	 */
	public static function repulsePaidOrders($orderIdList ,$module='order',$action='打回已付款'){
		return OrderHelper::repulsePaidOrders($orderIdList,$module,$action);
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 添加订单备注
	 * +---------------------------------------------------------------------------------------------
	 * @access static
	 * +---------------------------------------------------------------------------------------------
	 * @param 	
	 * 			$orderIdList				array		小老板单号数组
	 *        	$module						string		模块名
	 *        	$action						string		执行动作
	 *+---------------------------------------------------------------------------------------------
	 * @return 	array
	 * 			success					执行成功数
	 * 			failure					执行失败数
	 * 			message					执行失败相关信息
	 * +---------------------------------------------------------------------------------------------
	 * @invoking	OrderApiHelper::setOrderShipped([1,2,3],'order');
	 * +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2016/01/25				初始化
	 * +---------------------------------------------------------------------------------------------
	 */
	public static function setOrderShipped ($orderIdList ,$module='order',$action='OMS提交发货'){
		return OrderHelper::setOrderShipped($orderIdList,$module,$action);
	}//end of setOrderShipped
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 速卖通 延长收货时间 接口
	 * +---------------------------------------------------------------------------------------------
	 * @access static
	 * +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_id					string		平台订单id
	 * 			$extendDay					int			请求延长的具体天数
	 *        	$module						string		模块名
	 *        	$action						string		执行动作
	 *+---------------------------------------------------------------------------------------------
	 * @return 	array
	 * 			success					执行成功数
	 * 			failure					执行失败数
	 * 			message					执行失败相关信息
	 * +---------------------------------------------------------------------------------------------
	 * @invoking	OrderApiHelper::setOrderShipped([1,2,3],'order');
	 * +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2016/01/25				初始化
	 * +---------------------------------------------------------------------------------------------
	 */
	public static function extendAliexpressOrderBuyerAcceptGoodsTime($order_id , $extendDay ,$module='order',$action='OMS提交发货' ){
		$row = OdOrder::find()->select(['order_id'])->where(['order_source_order_id'=>$order_id , 'order_source'=>'aliexpress'])->asArray()->one();
		if (!empty($row)){
			return AliexpressOrderHelper::ExtendsBuyerAcceptGoodsTime($order_id, $extenddays,$module , $action);
		}else{
			return ['success'=>false ,'memo'=>'', 'errorCode'=>'','message'=>'平台订单号'.$order_id.'找不到！'];
		}
		
	}//end of extendAliexpressOrderBuyerAcceptGoodsTime
	
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 重写订单备注
	 * +---------------------------------------------------------------------------------------------
	 * @access static
	 *+---------------------------------------------------------------------------------------------
	 * @param 	
	 * 			$accountID				string		平台账号
	 *        	$platform				string		平台
	 *        	$platform_order_id		string		平台订单号
	 *        	$memo					string		备注信息
	 *        	$module					string		模块名
	 *        	$action					string		执行动作
	 *+---------------------------------------------------------------------------------------------
	 * @return array
	 * 			success					boolean		true 成功，false失败
	 * 			message					string		失败的原因
	 * +---------------------------------------------------------------------------------------------
	 * @invoking	OrderApiHelper::overwriteOrderDescription('aaa','aliexpress','213564','add memo data'，'Tracking',);
	 *+---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2016/01/05				初始化
	 *+---------------------------------------------------------------------------------------------
	 */
	public static function overwriteOrderDescription($accountID, $platform, $platform_order_id, $memo , $module='order',$action='OMS修改备注' ){
		return OrderHelper::overwriteOrderDescription($accountID, $platform, $platform_order_id, $memo ,$module,  $action);
	} 
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重新发货， 已出库订单补发
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$orderIdList			order id 的数组
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				success			int 	成功的条数
	 * 				failure			int		失败的条数
	 * 				message			string	失败原因
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function reOrder($orderIdList ,$module='order',$action='OMS重新发货'){
		return OrderHelper::reorder($orderIdList ,$module,$action );
	} //end of reOrder
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单 信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$query_condition			查询订单条件
	 * 			[
	 * 			order_status					string  optional	订单状态
	 * 			exception_status				string  optional	异常状态
	 * 			saas_platform_user_id			string  optional	saas库平台用户卖家账号id(ebay或者amazon卖家表中)
	 * 			consignee_country_code			string  optional	收件国家
	 * 			default_carrier_code			string  optional	物流商
	 * 			default_shipping_method_code 	string  optional	运输服务
	 * 			custom_tag						array	optional	自定义标签e.g. ['pay_memo'=>1 , ...] 查看 OrderTagHelper::$OrderSysTagMapping
	 * 			reorder_type					string  optional	重新发货类型 查看order moldel 相关值
	 * 			keys							string  optional	精准或模糊查询 的字段名						searchval与fuzzy一起使用
	 * 			searchval						string  optional	精准或模糊查询 的 值						keys与fuzzy一起使用
	 * 			fuzzy 							string  optional	精准或模糊查询 的 开关 1为模糊 ，0或者 空为精确		keys与searchval一起使用
	 * 			order_evaluation				string  optional	评价
	 * 			order_source					string  optional	平台			e.g. ebay , aliexpress
	 * 			tracker_status					string  optional	tracker状态 
	 * 			timetype						string  optional	查询的时间字类型  e.g. soldtime, paidtime , printtime , shiptime
	 * 			date_from						string	optional	起始时间						与timetype 一起使用
	 * 			date_to							string	optional	结束时间						与timetype 一起使用
	 * 			customsort						string	optional	排序字段
	 * 			order							string	optional	升降序
	 * 			item_qty						int		optional	商品数量						与 item_qty_compare_operators 一起使用
	 * 			item_qty_compare_operators		string  optional	商品数量比较运算符   e.g. > ,= , < 	与 item_qty 一起使用
	 * 
	 * 			default_warehouse_id			int		optional	仓库
	 * 			carrier_step					int		optional	物流商下单状态
	 * 			carrier_type					string	optional	物流类型	1:API	2:excel	3:跟踪号
	 * 			per-page						int		optional	每页多少行
	 * 			distribution_inventory_status	int		optional	
	 * ]
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				success			int 	成功的条数
	 * 				failure			int		失败的条数
	 * 				message			string	失败原因
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 * $uid 用于导出excel时判断是否子账号
	 * $page_related_params  后端调用时控制分页的页数
	 **/
	static public function getOrderListByCondition($query_condition, $uid = 0, $page_related_params = array(), $other_params_orders = array()){
		/*
		 * $saas_platform_user_id , $order_status , $delivery_status ,$default_warehouse_id , $exception_status , $carrier_step , $is_print_distribution , 
			$default_carrier_code , $consignee_country_code ,$default_shipping_method_code ,$custom_tag , $reorder_type , $order_evaluation , $order_source
		 * */
		Helper_Array::removeEmpty($query_condition);
		$params = self::_formatQueryOrderCondition($query_condition, $uid, $other_params_orders);

		foreach($params as $key=>$value){
			${$key} = $value;
		}
		if (isset($showItem) ==false)
			$showItem = 'productOnly';
		
		
		$query = null;
		return OrderHelper::getOrderListByCondition($like_params, $eq_params, $date_params, $in_params, $other_params, $sort, $order,$pageSize,false,$showItem , $query, $page_related_params);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * oms list页面 公共查询订单数据的接口 （ebay oms 使用分页模式 ， aliexpress使用的引用 模式）
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $condition			array 查询条件 request
	 * 			$addi_condition				额外的查询条件（非request的参数）
	 * 			$query						待选值， order ActiveRecord 引用变量，当有这个的时候 ，只会在这个ActiveRecord 上增加查询操作， 不会进行查询，当这为空时才会进行查询
	 * 			$pageSize					不可以与$query 共用 ， 每页的展示的数据
	 * 			$noPagination				不可以与$query 共用 ， 是否使用分页控件
	 * 			$showItem					不可以与$query 共用 ， 只是屏蔽商品之外 的信息
	 +---------------------------------------------------------------------------------------------
	 * @return						array 
	 *
	 * @invoking					OrderApiHelper::getOrderListByConditionOMS($request_condition);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/8/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderListByConditionOMS($request_condition , $addi_condition=[],&$query=null,  $pageSize=50 ,$noPagination = false,$showItem='productOnly',$page_related_params=array()){
		Helper_Array::removeEmpty($request_condition);
	
		$rt = OrderGetDataHelper::formatQueryOrderConditionOMS($request_condition , $addi_condition);
		//like params , eq_params 等变量赋值
		foreach($rt as $key=>$value){
			${$key} = $value;
		}

		$rt = OrderGetDataHelper::getOrderListByCondition($like_params, $eq_params, $date_params, $in_params, $other_params, $sort, $order , $pageSize ,$noPagination ,$showItem , $query,$page_related_params);

		if (!empty($showsearch)){
			$rt['showsearch'] = $showsearch;
		}
		return $rt;
	}//end of function getOrderListByConditionOMS
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单 信息条件格式转换
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$query_condition			查询订单条件
	 * 			[
	 * 			order_status					string  optional	订单状态
	 * 			exception_status				string  optional	异常状态
	 * 			saas_platform_user_id			string  optional	saas库平台用户卖家账号id(ebay或者amazon卖家表中)
	 * 			consignee_country_code			string  optional	收件国家
	 * 			default_carrier_code			string  optional	物流商
	 * 			default_shipping_method_code 	string  optional	运输服务
	 * 			custom_tag						array	optional	自定义标签e.g. ['pay_memo'=>1 , ...] 查看 OrderTagHelper::$OrderSysTagMapping
	 * 			reorder_type					string  optional	重新发货类型 查看order moldel 相关值
	 * 			keys							string  optional	精准或模糊查询 的字段名						searchval与fuzzy一起使用
	 * 			searchval						string  optional	精准或模糊查询 的 值						keys与fuzzy一起使用
	 * 			fuzzy 							string  optional	精准或模糊查询 的 开关 1为模糊 ，0或者 空为精确		keys与searchval一起使用
	 * 			order_evaluation				string  optional	评价
	 * 			order_source					string  optional	平台			e.g. ebay , aliexpress
	 * 			tracker_status					string  optional	tracker状态
	 * 			timetype						string  optional	查询的时间字类型  e.g. soldtime, paidtime , printtime , shiptime
	 * 			date_from						string	optional	起始时间						与timetype 一起使用
	 * 			date_to							string	optional	结束时间						与timetype 一起使用
	 * 			customsort						string	optional	排序字段
	 * 			order							string	optional	升降序
	 * 			item_qty						int		optional	商品数量						与 item_qty_compare_operators 一起使用
	 * 			item_qty_compare_operators		string  optional	商品数量比较运算符   e.g. > ,= , < 	与 item_qty 一起使用
	 *
	 * 			default_warehouse_id			int		optional	仓库
	 * 			carrier_step					int		optional	物流商下单状态
	 * 			carrier_type					string	optional	物流类型	1:API	2:excel	3:跟踪号
	 * 			per-page						int		optional	每页多少行
	 * 			distribution_inventory_status	int		optional
	 * 			tracknum						string	optional	根据跟踪号查询
	 * 			ismultipleProduct				string	optional	是否多品订单
	 * ]
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				['other_params'=>$other_params , 'eq_params'=>$eq_params , 'date_params'=>$date_params , 'like_params'=>$like_params , 'in_params'=>$in_params , 'sort'=>$sort, 'order'=>$order,'pageSize'=>$pageSize];
	 * 
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/23				初始化
	 +---------------------------------------------------------------------------------------------
	 * $uid 用于导出excel时判断是否子账号
	 **/
	static public function _formatQueryOrderCondition($query_condition, $uid = 0, $other_params_orders = array()){

		foreach($query_condition as $key=>$value){
			if(!empty($value) || trim($value) != ''){
				${$key} = $value;
			}
		}
		
		$like_params = [];
		$eq_params = [];
		$in_params = [];
		$other_params = [];
		$date_params = [];
		
		$eq_params['isshow'] = "Y"; //隐藏部分订单
		
		//是否有跟踪号
		if(isset($isExistTrackingNO)){
			$other_params['isExistTrackingNO'] = $isExistTrackingNO;
		}
		
		//单品/多品
		if (isset($ismultipleProduct)){
			$eq_params['ismultipleProduct'] = $ismultipleProduct;
		}
		
		//虚拟发货状态
		if(isset($order_sync_ship_status)){
			$eq_params['sync_shipped_status'] = $order_sync_ship_status;
		}
		
		//跟踪号
		if(isset($tracknum)){
			$other_params['tracknum'] = $tracknum;
		}
		
		//合订订单
		if(isset($order_relation)){
			$eq_params['order_relation'] = $order_relation;
		}else{
			$in_params['order_relation'] = ['normal','sm','ss','fs'];
		}
		
		//拣货状态,理论上没有用
		//delivery_status
		if(isset($delivery_status)){
			$eq_params['delivery_status'] = $delivery_status;
		}
		
		//待打单状态,沿用之前的拣货字段
		//delivery_status
		if(isset($delivery_status_in)){
			$in_params['delivery_status'] = $delivery_status_in;
		}
		
		//分配库存状态
		if(isset($distribution_inventory_status)){
			$eq_params['distribution_inventory_status'] = $distribution_inventory_status;
		}
		//物流商类型
		if (isset($carrier_type)){
			$other_params['carrier_type'] = $carrier_type;
		}
		//仓库
		if (isset($default_warehouse_id)){
			if($default_warehouse_id != 'picking_mode_0')
				$eq_params['default_warehouse_id'] = $default_warehouse_id;
		}
		//物流商下单状态
		if (isset($carrier_step)){
			$eq_params['carrier_step'] = $carrier_step;
		}
		//订单状态
		if (!empty($order_status)){
			$eq_params['order_status'] = $order_status;
		}
		//异常状态
		if (isset($exception_status)){
			$eq_params['exception_status'] = $exception_status;
		}
		
		//卖家账号
		if (!empty($saas_platform_user_id)){
			$eq_params['saas_platform_user_id'] = $saas_platform_user_id;
				
		}
		//收件国家
		if (!empty($consignee_country_code)){
			$in_params['consignee_country_code'] = $consignee_country_code;
		}
		//收件国家New
		if (!empty($selected_country_code)){
			$in_params['consignee_country_code'] = $selected_country_code;
		}
		//物流商
		if (!empty($default_carrier_code)){
			$eq_params['default_carrier_code'] = $default_carrier_code;
		}
		//未指定仓库和运输服务
		if(isset($no_warehouse_or_shippingservice)){
			$other_params['no_warehouse_or_shippingservice'] = $no_warehouse_or_shippingservice;
		}
		//已近指定仓库和运输服务
		if (isset($warehouse_and_shippingservice)){
			$other_params['warehouse_and_shippingservice'] = $warehouse_and_shippingservice;
		}
		//运输服务
		if (!empty($default_shipping_method_code)){
			$eq_params['default_shipping_method_code'] = $default_shipping_method_code;
		}
		//是否生成拣货单
		if (isset($is_print_picking)){
			$eq_params['is_print_picking'] = $is_print_picking;
		}
		//是否打印配货单
		if (isset($is_print_distribution)){
			$eq_params['is_print_distribution'] = $is_print_distribution;
		}
		//自定义标签
		if (!empty($custom_tag)){
			$other_params['custom_tag'] = $custom_tag;
		}
		
		//重新发货类型
		if (!empty($reorder_type)){
			if ($reorder_type != ' all'){
				$eq_params ['reorder_type'] = $reorder_type;
			}else{
				$other_params['reorder_type'] = $reorder_type;
			}
		}
		
		//评价
		if (!empty($order_evaluation)){
			$eq_params ['order_evaluation'] = $order_evaluation;
		}
		
		

		//##########################权限 start #####################
			###最好在外面做了权限控制,那样就不需要进入此helper浪费性能
		$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid; //如果为测试用的账号就不受平台绑定限制
		//平台
		if (!empty($order_source)){
			$eq_params ['order_source'] = $order_source;
			//卖家账号
			if (!empty($selleruserid)){
				//有传入sellerid时，认为是有该权限的
				$eq_params ['selleruserid'] = $selleruserid;
			}else{
				if(!in_array(\Yii::$app->subdb->getCurrentPuid(),$test_userid['yifeng'])){
					//没有传入sellerid时，需要查询所有 有权限的 店铺
					$authorize_account = PlatformAccountApi::getPlatformAuthorizeAccounts($order_source);
					if(empty($authorize_account))
						$eq_params ['selleruserid'] = '@@不可能的##';//没有有权限店铺时,设置为一个不可能的值,使查询没有结果
					else
						$eq_params ['selleruserid'] = array_keys($authorize_account);
				}
			}
		}elseif(!empty($selleruserid)){
			$eq_params ['selleruserid'] = $selleruserid;
		}else{
			$authorize_platform_accounts = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false,false,false);
			Helper_Array::removeEmpty($authorize_platform_accounts);
			$tmp_authorize_platform_accounts = [];
			foreach ($authorize_platform_accounts as $platform=>$a){
				if(!empty($a))
					$tmp_authorize_platform_accounts[$platform] = $a;
			}
			//print_r($tmp_authorize_platform_accounts);
			if(!in_array(\Yii::$app->subdb->getCurrentPuid(),$test_userid['yifeng'])){
				if(empty($tmp_authorize_platform_accounts)){
					//没有有权限的平台和店铺时,设置为一个不可能的值,使查询没有结果
					$eq_params ['order_source'] = '@@不可能的##';
					$eq_params ['selleruserid'] = '@@不可能的##';
				}else{
					$tmp_authorize_params = [];
					$tmp_authorize_params[] = 'or';
					foreach ($tmp_authorize_platform_accounts as $platform=>$accounts){
						if(!empty($accounts))
							$tmp_authorize_params[] = ['order_source'=>$platform,'selleruserid'=>array_keys($accounts)];
					}
				}
				if(!empty($tmp_authorize_params))
					$other_params['authorize_seller_arr'] = $tmp_authorize_params;
			}
		}
		
		//##########################权限 end #######################
		
		//新的店铺筛选  S

		if (!empty($selleruserid_combined)){
			$tmp_selleruserid_combined = $selleruserid_combined;

			if((strlen($tmp_selleruserid_combined) > 8) && (substr($tmp_selleruserid_combined, 0, 4) == 'com-') && (substr($tmp_selleruserid_combined, -4) == '-com')){
				$tmp_selleruserid_combined = substr($tmp_selleruserid_combined, 4);
				$tmp_selleruserid_combined = substr($tmp_selleruserid_combined, 0, strlen($tmp_selleruserid_combined)-4);
				
				if(!isset($use_mode)){
					$use_mode = '';
				}
					
// 				$uid
// 				use_mode
				$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'delivery','platform'=>$use_mode,'com_name'=>$tmp_selleruserid_combined), $uid);
	
				if(count($pcCombination) > 0){
					$tmp_pcCombination = array();
					$selleruserid_tmp = isset($other_params_orders['selleruserid_tmp']) ? $other_params_orders['selleruserid_tmp'] : array();
					$tmp_selleruserids = array();
					if(count($selleruserid_tmp) > 0){
						foreach ($selleruserid_tmp as $selleruserid_tmp_Val){
							if(count($selleruserid_tmp_Val) > 0){
								foreach ($selleruserid_tmp_Val as $selleruserid_tmp_Val_K => $selleruserid_tmp_Val_V){
									$tmp_selleruserids[$selleruserid_tmp_Val_K] = $selleruserid_tmp_Val_K;
								}
							}
						}
					}
					
					foreach ($pcCombination as $pcCombination_K => $pcCombination_V){
						if(!isset($tmp_selleruserids[$pcCombination_V])){
						}else{
							$tmp_pcCombination[$pcCombination_V] = $pcCombination_V;
						}
					}
						
					if(count($tmp_pcCombination) > 0){
						$in_params['selleruserid'] = $tmp_pcCombination;
					}else{
						//不成功时直接查找xxxxx
						$in_params['selleruserid'] = 'xxxxx';
					}
				}else{
					//不成功时直接查找xxxxx
					$in_params['selleruserid'] = 'xxxxx';
				}
			}else{
				$in_params['selleruserid'] = $tmp_selleruserid_combined;
			}
		}
		//新的店铺筛选  E
		
		
		
		//tracker状态
		if (!empty($tracker_status)){
			$eq_params ['tracker_status'] = $tracker_status;
		}
		//筛选打印类型
		if(!empty($carrierPrintType)){
			switch ($carrierPrintType){
				case 'no_print_distribution'://未打印配货单
					$eq_params['is_print_distribution'] = 0;
					break;
				case 'print_distribution'://已打印配货单
					$eq_params['is_print_distribution'] = 1;
					break;
				case 'no_print_carrier'://未打印物流单
					$eq_params['is_print_carrier'] = 0;
					break;
				case 'print_carrier'://已打印物流单
					$eq_params['is_print_carrier'] = 1;
					break;
				default:
					break;
			}
		}
		
		if (!empty($fuhe)){
			switch ($fuhe){
				case 'is_comment_status':
					$other_params['is_comment_status'] = $fuhe;
					break;
				case 'no_warehouse':
					$other_params['no_warehouse'] = $fuhe;
					break;
				case 'no_shippingservice':
					$other_params['no_shippingservice'] = $fuhe;
					break;
				case 'no_print_distribution'://未打印配货单
					$eq_params['is_print_distribution'] = 0;
					break;
				case 'print_distribution'://已打印配货单
					$eq_params['is_print_distribution'] = 1;
					break;
				case 'no_print_carrier'://未打印物流单
					$eq_params['is_print_carrier'] = 0;
					break;
				case 'print_carrier'://已打印物流单
					$eq_params['is_print_carrier'] = 1;
					break;
				case 'no_delivery_status_search':
					$eq_params['delivery_status'] = array(0,1);
					break;
				case 'delivery_status_search':
					$eq_params['delivery_status'] = array(2,3,6);
					break;
				default:
					break;
			}
		}
		//时间
		if (!empty($timetype) && (!empty($date_from) || !empty($date_to) )){
				
			switch ($timetype){
				case 'soldtime':
					$tmp='order_source_create_time';
					//$orderstr.='order_source_create_time';
					break;
				case 'paidtime':
					$tmp='paid_time';
					//$orderstr.='paid_time';
					break;
				case 'printtime':
					$tmp='printtime';
					//$orderstr.='printtime';
					break;
				case 'shiptime':
					$tmp='complete_ship_time';
					//$orderstr.='delivery_time';
					break;
				default:
					$tmp='order_source_create_time';
					//$orderstr.= 'order_source_create_time';
					break;
			}
			$date_params ['field_name'] = $tmp;
			if (!empty($date_from))
				$date_params ['date_from'] = $date_from;
				
			if (!empty($date_to))
				$date_params ['date_to'] = $date_to;
		}
		//排序字段
		if (!empty($customsort)){
			switch ($customsort){
				case 'soldtime':
					$sort='order_source_create_time';
					break;
				case 'paidtime':
					$sort='paid_time';
					break;
				case 'printtime':
					$sort='printtime';
					break;
				case 'shiptime':
					$sort='delivery_time';
					break;
				case 'order_id':
					$sort='order_id';
					break;
				case 'grand_total':
					$sort='grand_total';
					break;
				case 'country_sort':
					$sort='consignee_country';
					break;
				case 'deadlinetime':
					$sort='fulfill_deadline';
					break;
				case 'fulfill_deadline':
					$sort='fulfill_deadline';
					break;
				case 'first_sku':
					$sort='first_sku';
					break;
				default:
					$sort='order_source_create_time';
					break;
			}
			if (!empty($ordersorttype)){
				$order  = 'asc';
			}else{
				$order  = 'desc';
			}
		}else {
			$date_params['sort']  = 'order_source_create_time';
			if (!empty($ordersorttype)){
				$order  = 'asc';
			}else{
				$order  = 'desc';
			}
		}
		//系统标签
		$sysTagList = [];
		foreach(OrderTagHelper::$OrderSysTagMapping as $tag_code=>$label){
			//1.勾选了系统标签；
			if (!empty(${$tag_code}) ){
				//生成 tag 标签的数组
				$sysTagList[] = $tag_code;
			}
		}
		
		if (!empty($sysTagList)){
			$other_params ['sysTagList'] = $sysTagList;
		}
		
		//searchval fuzzy
		$checkOrderColList = ['order_id','source_buyer_user_id','consignee','consignee_email','customer_number'];
		
		if (!empty($searchval) && !empty($keys)){
			if (is_string($searchval)){
				$searchval = str_replace('；', ';', $searchval);
				
				//有空格就替换成分号
				if (in_array($keys, ['order_id','ebay_orderid','srn','tracknum'])){
					$searchval = str_replace(' ', ';', $searchval);
				}
				
				if (stripos("123".$searchval,';')>0){
					//echo "<br>*************<br>rt ".stripos("123".$searchval,';'); 
					$searchval = explode(';', $searchval);
				}
				
			}
			if (is_array($searchval)){
				Helper_Array::removeEmpty($searchval);
			}else{
				$searchval = trim($searchval);
			}
			
			if (!empty($fuzzy)){
				// 模糊搜索
				if (in_array($keys, $checkOrderColList)){
					$like_params [$keys] = $searchval;
				}else{
					$other_params ['fuzzy'] = $fuzzy;
					$other_params[$keys] = $searchval;
				}
			}else{
				// 精确搜索
				if (in_array($keys, $checkOrderColList)){
					$eq_params [$keys] = $searchval;
				}else{
					$other_params[$keys] = $searchval;
				}
			}
		}
		
		//item qty
		if (isset($item_qty) && !empty($item_qty_compare_operators)){
			$other_params ['item_qty'] = $item_qty;
			$other_params ['item_qty_compare_operators'] = $item_qty_compare_operators;
		}
		
		//子账号 权限控制 start
		if($uid == 0){
			$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		}else{
			if($uid == \Yii::$app->subdb->getCurrentPuid()){
				$isParent = true;
			}else{
				$isParent = false;
			}
		}
		
		//true 为主账号， 不需要增加平台过滤 ， false 为子账号， 需要增加权限控制
		if ($isParent ==false){
			$UserAuthorizePlatform = \eagle\modules\permission\apihelpers\UserApiHelper::getUserAuthorizePlatform();
			if (! in_array('all',$UserAuthorizePlatform)){
				$in_params['order_source'] = $UserAuthorizePlatform;
			}
		}
		//子账号 权限控制 end
		//$pageSize = 50;
		//if(isset($query_condition['per-page'])){
		//	$pageSize = $query_condition['per-page'];
		//}
		//默认打开的列表记录数为上次用户选择的page size 数	//lzhl	2016-11-30
		$page_url = '/'.\Yii::$app->controller->module->id.'/'.\Yii::$app->controller->id.'/'.\Yii::$app->controller->action->id;
		$last_page_size = ConfigHelper::getPageLastOpenedSize($page_url);
		if(empty($last_page_size))
			$last_page_size = 20;//默认显示值
		if(empty($query_condition['per-page']) && empty($query_condition['page']))
			$pageSize = $last_page_size;
		elseif(empty($query_condition['per-page']) && !empty($query_condition['page'])){
			$pageSize = 20;
		}elseif(!empty($query_condition['per-page'])) 
			$pageSize = $query_condition['per-page'];

		ConfigHelper::setPageLastOpenedSize($page_url, $pageSize);

		if (empty($order)) $order = 'desc';
		if (empty($sort)) $sort = 'order_source_create_time';

		return ['other_params'=>$other_params , 'eq_params'=>$eq_params , 'date_params'=>$date_params , 'like_params'=>$like_params , 'in_params'=>$in_params , 'sort'=>$sort, 'order'=>$order,'pageSize'=>$pageSize];
	}//end of _formatQueryOrderCondition
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单 信息条件 下的物流 方式统计数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$query_condition			查询订单条件
	 * 			[
	 * 			order_status					string  optional	订单状态
	 * 			exception_status				string  optional	异常状态
	 * 			saas_platform_user_id			string  optional	saas库平台用户卖家账号id(ebay或者amazon卖家表中)
	 * 			consignee_country_code			string  optional	收件国家
	 * 			default_carrier_code			string  optional	物流商
	 * 			default_shipping_method_code 	string  optional	运输服务
	 * 			custom_tag						array	optional	自定义标签e.g. ['pay_memo'=>1 , ...] 查看 OrderTagHelper::$OrderSysTagMapping
	 * 			reorder_type					string  optional	重新发货类型 查看order moldel 相关值
	 * 			keys							string  optional	精准或模糊查询 的字段名						searchval与fuzzy一起使用
	 * 			searchval						string  optional	精准或模糊查询 的 值						keys与fuzzy一起使用
	 * 			fuzzy 							string  optional	精准或模糊查询 的 开关 1为模糊 ，0或者 空为精确		keys与searchval一起使用
	 * 			order_evaluation				string  optional	评价
	 * 			order_source					string  optional	平台			e.g. ebay , aliexpress
	 * 			tracker_status					string  optional	tracker状态
	 * 			timetype						string  optional	查询的时间字类型  e.g. soldtime, paidtime , printtime , shiptime
	 * 			date_from						string	optional	起始时间						与timetype 一起使用
	 * 			date_to							string	optional	结束时间						与timetype 一起使用
	 * 			customsort						string	optional	排序字段
	 * 			order							string	optional	升降序
	 * 			item_qty						int		optional	商品数量						与 item_qty_compare_operators 一起使用
	 * 			item_qty_compare_operators		string  optional	商品数量比较运算符   e.g. > ,= , < 	与 item_qty 一起使用
	 *
	 * 			default_warehouse_id			int		optional	仓库
	 * 			carrier_step					int		optional	物流商下单状态
	 * 			carrier_type					string	optional	物流类型	1:API	2:excel	3:跟踪号
	 * 			distribution_inventory_status	int		optional
	 * ]
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				Array ( [0] => Array ( [default_shipping_method_code] => 1133 [ct] => 7 ) [1] => Array ( [default_shipping_method_code] => 166 [ct] => 1 ) )
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getShippingMethodCodeByCondition($query_condition, $moreGroup = array('default_shipping_method_code')){
		$params = self::_formatQueryOrderCondition($query_condition);
		foreach($params as $key=>$value){
			${$key} = $value;
		}
		
		return OrderHelper::getShipmethodGroupWithOrder($like_params, $eq_params, $date_params, $in_params, $other_params, $moreGroup);
	}//end of getShippingMethodCodeByCondition
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置订单有新留言的标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_id			小老板 订单号	000001
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				success			boolean 	true 为成功， failure失败
	 * 				message			string		失败原因
	 * 				code			int			200为正常， 400 为异常
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setHasNewMessageTag($order_source_order_id , $platform){
		$list = OdOrder::find()->select(['order_id'])->where(['order_source_order_id'=>$order_source_order_id , 'order_source'=>$platform])->asArray()->all();
		$rt = ['success'=>true , 'message'=>'' , 'code'=>200];
		foreach ($list as $row){
			$tmp  = OrderTagHelper::setOrderSysTag($row['order_id'], 'new_msg_tag');
			if ($tmp['success'] == false){
				$rt['message'] .= $row['order_id']." ".$tmp['message'];
				$rt['success'] = $tmp['success'];
			}
			if ($tmp['code'] == 400) $rt['code'] = 400;
		}
		return $rt;
	}//end of setHasNewMessageTag
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除订单有新留言的标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_id			小老板 订单号	000001
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				success			boolean 	true 为成功， failure失败
	 * 				message			string		失败原因
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function unsetNewMessageTag($order_source_order_id , $platform){
		$list = OdOrder::find()->select(['order_id'])->where(['order_source_order_id'=>$order_source_order_id , 'order_source'=>$platform])->asArray()->all();
		$rt = ['success'=>true , 'message'=>'' ];
		foreach ($list as $row){
			$tmp  = OrderTagHelper::DelOrderSysTag($row['order_id'], 'new_msg_tag');
			if ($tmp['success'] == false){
				$rt['message'] .= $row['order_id']." ".$tmp['message'];
				$rt['success'] = $tmp['success'];
			}
			
		}
		return $rt;
	}//end of unsetNewMessageTag
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置不合并发货的标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_id			小老板 订单号	000001
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				success			boolean 	true 为成功， failure失败
	 * 				message			string		失败原因
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setskipMergeTag($order_id){
		return OrderTagHelper::setOrderSysTag($order_id, 'skip_merge');
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除不合并发货的标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_id			小老板 订单号	000001
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				success			boolean 	true 为成功， failure失败
	 * 				message			string		失败原因
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function unsetskipMergeTag($order_id){
		return OrderTagHelper::DelOrderSysTag($order_id, 'skip_merge');
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 是否虚假发货的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$order_id				string			小老板订单号
	 +---------------------------------------------------------------------------------------------
	 * @return 								boolean			true为虚假发货订单， false 为不是虚假发货订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function isEagleUnshipOrder($order_id){
		return OrderTagHelper::isEagleUnshipOrder($order_id);
	}//end of isEagleUnshipOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 是否不合并发货的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$order_id				string			小老板订单号
	 *				$tag_code				string 			系统标签code
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *				success					boolean			执行结果true为成功， false 为失败
	 *				message					string			执行失败的提示
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function isSkipMergeOrder($order_id){
		
		return OrderTagHelper::isSkipMergeOrder($order_id);
	}//end of isSkipMergeOrder
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单商品的类目中英文做报关名使用
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$itemid				string		产品id
	 +---------------------------------------------------------------------------------------------
	 * @return 								array('ch'=>'手机','en'=>'phone')
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		million		2016/03/08			初始化
	 * @author		lkh			2016/07/05			全局缓存
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderProductCategory($itemid,$platform){
		global $CACHE;
		$r = array();
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if (strlen($itemid)&&strlen($platform)){
			switch ($platform){
				case 'aliexpress'://速卖通类目本身有翻译，所有不需要用百度翻译接口
					//2016-07-05  为后台检测订单加上global cache 的使用方法 start
					// 假如缓存没有的情况下也查一下数据库
					if (isset($CACHE[$uid]['listing'][$platform][$itemid])){
						//$item = (empty($CACHE[$uid]['listing']['aliexpress'][$itemid])?[]:$CACHE[$uid]['listing']['aliexpress'][$itemid]);
						$item = $CACHE[$uid]['listing']['aliexpress'][$itemid];
						if (is_array($item)) $item = (Object) $item;
						
						//log 日志 ， 调试相关信息start 
						$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' listing has cache';
						\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
						//log 日志 ， 调试相关信息end
					}else{
						$item = AliexpressListingDetail::findOne(['productid'=>$itemid]);
						//log 日志 ， 调试相关信息start
						$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' listing no cache';
						\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
						//log 日志 ， 调试相关信息end
					}
					
					//2016-07-05  为后台检测订单加上global cache 的使用方法 end
					
					if (empty($item)){
						$r=array('ch'=>'礼品','en'=>'gift');
					}else{
						//2016-07-05  为后台检测订单加上global cache 的使用方法 start
						// 假如缓存没有的情况下也查一下数据库
						if (isset($CACHE[$uid]['Category'][$platform][$item->categoryid])){
							$category = $CACHE[$uid]['Category'][$platform][$item->categoryid];
							if (is_array($category)) $category = (Object) $category;
							
							//log 日志 ， 调试相关信息start
							$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' category has cache';
							\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
							//log 日志 ， 调试相关信息end
						}else{
							$category = AliexpressCategory::findOne(['cateid'=>$item->categoryid]);
							
							//log 日志 ， 调试相关信息start
							$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' category no cache';
							\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
							//log 日志 ， 调试相关信息end
						}
						
						//2016-07-05  为后台检测订单加上global cache 的使用方法 end
						if (!empty($category)){
							$r=array(
									'ch'=>strlen($category->name_zh)?$category->name_zh:'礼物',
									'en'=>strlen($category->name_en)?$category->name_en:'gift'
							);
						}else {
							$r=array('ch'=>'礼品','en'=>'gift');
						}
						
					}
					break;
				case 'ebay':
					//2016-07-05  为后台检测订单加上global cache 的使用方法 start
					// 假如缓存没有的情况下也查一下数据库
					if (isset($CACHE[$uid]['listing'][$platform][$itemid])){
						$item = $CACHE[$uid]['listing'][$platform][$itemid];
						if (is_array($item)) $item = (Object) $item;
						
						//log 日志 ， 调试相关信息start
						$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' listing has cache';
						\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
						//log 日志 ， 调试相关信息end
					}else{
						$item = EbayItemDetail::findOne(['itemid'=>$itemid]);
						$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' listing no cache';
						\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
					}
					//2016-07-05  为后台检测订单加上global cache 的使用方法 end
					
					if (empty($item)){
						$r=array('ch'=>'礼品','en'=>'gift');
					}else{
						//2016-07-05  为后台检测订单加上global cache 的使用方法 start
						// 假如缓存没有的情况下也查一下数据库
						if (isset($CACHE[$uid]['Category'][$platform][$item->primarycategory])){
							$category = $CACHE[$uid]['Category'][$platform][$item->primarycategory];
							if (is_array($category)) $category = (Object) $category;
							
							//log 日志 ， 调试相关信息start
							$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' category has cache';
							\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
							//log 日志 ， 调试相关信息end
						}else{
							$category = EbayCategory::findOne(['categoryid'=>$item->primarycategory]);
							
							//log 日志 ， 调试相关信息start
							$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' '.$platform.' category no cache';
							\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
							//log 日志 ， 调试相关信息end
						}
						//2016-07-05  为后台检测订单加上global cache 的使用方法 end
						
						if (!empty($category)){
							/**
							 * @todo 可将翻译反倒类目表节省百度接口呼叫次数
							 */
							//翻译ebay类目
							$translate = transBAIDU::translate($category->name, 'auto', 'zh');
							if (isset($translate['response']['code']) && $translate['response']['code']==0){
								$name_zh = isset($translate['response']['data'])?$translate['response']['data']:'礼物';
							}else{
								$name_zh = '礼物';
							}
							$r=array(
									'ch'=>$name_zh,
									'en'=>strlen($category->name)?$category->name:'gift'
							);
						}else {
							$r=array(
									'ch'=>'礼品',
									'en'=>'gift'
							);
						}
					
					}
					break;
				default:
					$r=array('ch'=>'礼品','en'=>'gift');
					break;
			}
		}else{
			$r=array('ch'=>'礼品','en'=>'gift');
		}
		return $r;
	}//end of isEagleUnshipOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 显示 解绑 账号
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $platform			string 	平台 e.g. all , aliexpress
	 +---------------------------------------------------------------------------------------------
	 * @return						array	['aliexpress'=>['account1','account2']]
	 *
	 * @invoking					OrderApiHelper::listUnbindingAcount('aliexpress');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function listUnbindingAcount($platform){
		return OrderHelper::listUnbindingAcount($platform);
	}//end of listUnbindingAcount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据标记发货结果， 自动 设置订单标记发货状态 ， 当有任何一条标记发货成功的记录， 该订单的物流发货状态都为1
	 * shipping status 1 为同步成功  0为未同步
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order_source_order_id			string 	平台订单号（ps 不是小老板 订单号）
	 * @param	  $success							boolean  标记发货是否成功 true 为成功， false 为失败
	 +---------------------------------------------------------------------------------------------
	 * @return						nil
	 *
	 * @invoking					OrderHelper::setOrderShippingStatus($order_source_order_id ,$success);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderShippingStatus($order_source_order_id ,$success,$delivery_time=-1){
		//20160901 废弃 改用 setOrderSyncShippedStatus
		return true;
		return OrderHelper::setOrderShippingStatus($order_source_order_id, $success,$delivery_time);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更新订单的物流状态接口
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $accountID			string		账号信息
	 * 			  $platform				string		平台
	 * 			  $track_no				string		物流单号
	 * 			  $status				string		物流状态
	 +---------------------------------------------------------------------------------------------
	 * @return						string  html 代码
	 *
	 * @invoking					OrderHelper::setOrderTrackerStatus(100);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderTrackerStatus($accountID , $platform , $track_no , $status ){
		return OrderHelper::setOrderTrackerStatus($accountID, $platform, $track_no, $status);
	}//end of setOrderTrackerStatus
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除订单虚假发货的标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_id			小老板 订单号	000001
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				success			boolean 	true 为成功， failure失败
	 * 				message			string		失败原因
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function unsetPlatformShippedTag($order_id){
		return OrderTagHelper::DelOrderSysTag($order_id, 'sys_unshipped_tag');
	}
	
	/**
	 * 设置od_order_shipped_v2表的字段值
	 * @param  $order_id	订单id
	 * @param  $track_no	物流号
	 * @param  $data		[字段名1=>字段值1,...]
	 * @return array
	 *  @author	lzhl	2016/02/27	初始化
	 */
	public static function setOrderShippedInfo($order_id,$track_no,$data=[]){
		$rtn['success'] = true;
		$rtn['message'] = "";
		$OdShippeds = OdOrderShipped::find()->where(['tracking_number'=>$track_no,'order_id'=>$order_id])->all();
	
		$journal_id = SysLogHelper::InvokeJrn_Create("Order",__CLASS__, __FUNCTION__ , array($order_id,$track_no,$data));
		$errMsg='';
		if(!empty($OdShippeds)){
			foreach ($OdShippeds as $odShipped){
				foreach ($data as $attr=>$val){
					if(isset($odShipped->$attr))
						$odShipped->$attr = $val;
				}
				$odShipped->updated = time();
	
				if(!$odShipped->save()){
					$rtn['success']= false;
					$rtn['message'].= "物流号$track_no更新失败";
					$errMsg .= print_r($odShipped->getErrors());
				}
			}
		}else{
			$rtn['success'] = false;
			$rtn['message'] = "没有需要更新的物流号!";
		}
		if(!empty($errMsg))
			$rtn['errMsg']= $errMsg;
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
	
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取ebay 结算信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_source_order_id			ebay 交易号	
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				0=>[order_source_order_id , checkoutstatus]			
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/08/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getEbayCheckOutInfo($OrderSourceOrderIdList){
		//获取当前  check out 状态
		$orderCheckOutList = [];
		foreach (\eagle\modules\order\models\OdEbayOrder::find()->where(['ebay_orderid'=>$OrderSourceOrderIdList])->select(['ebay_orderid','checkoutstatus'])->asArray()->each(1) as $check){
			$orderCheckOutList[$check['ebay_orderid']] = $check;
		}
		return $orderCheckOutList;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单 标记已完成 封装
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	array:	$orderIdList					小老板单号数组
	 * @param	string: $module							模块
	 * @param	string:	$action 						执行动作
	 +---------------------------------------------------------------------------------------------
	 * @return	无
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/08/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function completeOrder($orderIdList ,$module='order',$action='标记已完成'){
		return OrderHelper::completeOrder($orderIdList ,$module,$action);
	}
	
	
	//for test getOrderProductCost
	static public function getOrderProductCostByOrderId($orderId,$sku){
		$orderData = OdOrder::find()->where(['order_id'=>$orderId])->asArray()->one();
		return self::getOrderProductCost($orderData, $sku);
	}//end of getOrderProductCostByOrderId
	
	/*
	 * 通过订单id和sku获取该对应商品的成本
	 * 如果订单统计过利润，则获取统计利润时的成本，否则获取商品模块采购价
	 * @param	array		$orderData		订单信息
	 * @param	string		$sku			sku//使用订单的sku，不是root。
	 * @return	array		[purchase_cost,addi_cost],如果没有相关值，则对应字段为''
	 * @author	lzhl		2016/08/26		初始化
	 */
	static public function getOrderProductCost($orderData,$sku){
		$profit_cost = false;//是否有记录过计算利润时的成本
		//统计过成本，则用统计过的记录成本
		if(!empty($orderData['profit']) || $orderData['profit']=='0' || $orderData['profit']=='0.00'){
			if(!empty($orderData['addi_info'])){
				$addi_info = json_decode($orderData['addi_info'],true);
				if(!empty($addi_info['product_cost'])){
					//print_r($addi_info['product_cost']);
					//product_cost的记录方式为string，像：
					//[<br> sku:(采购价x+额外成本y)*qty]*(item count)
					$cost_arr_tmp = explode('<br>', $addi_info['product_cost']);
					foreach ($cost_arr_tmp as $tmp){
						$tmp = str_replace('&nbsp;', '', $tmp);
						//print_r($tmp);
						$re_sku='';//记录中的sku
						$tmp_info = explode('：', $tmp);
						$re_sku = !empty($tmp_info[0])?$tmp_info[0]:'';
						if(empty($re_sku) || $re_sku!==$sku)
							continue;
						if(empty($tmp_info[1]))
							continue;
						
						//$tmp_info[1] like : (采购价11+额外0)*1
						$tmp_str = str_replace(['(采购价','+额外',')*'], ['',',',','], $tmp_info[1]);
						//print_r($tmp_str);
						$cost_arr = explode(',', $tmp_str);
						//print_r($addi_info['product_cost']);
						if(count($cost_arr)>=2){
							$purchase_cost = floatval($cost_arr[0]);
							$addi_cost = empty($cost_arr[2])?0:floatval($cost_arr[1]);//只有两组数值时，为成本和qty，没有addi_cost
							return ['purchase_cost'=>$purchase_cost,'addi_cost'=>$addi_cost];
						}
					}
				}
			}
		}
		//未统计过利润，或利润成本记录缺失，则使用商品库
		if(!$profit_cost){
			$root_sku = ProductHelper::getRootSkuByAlias($sku);
			if(empty($root_sku))
				return ['purchase_cost'=>'','addi_cost'=>''];
			else{
				$pd = Product::findOne($root_sku);
				if(empty($pd))
					return ['purchase_cost'=>'','addi_cost'=>''];
				else{
					if(empty($pd->additional_cost))
						$addi_cost = 0;
					else 
						$addi_cost = $pd->additional_cost;
					
					if(empty($pd->purchase_price))
						$purchase_cost = 0;
					else 
						$purchase_cost = $pd->purchase_price;
				}
				return ['purchase_cost'=>floatval($purchase_cost),'addi_cost'=>floatval($addi_cost)];
			}
		}
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取补发订单当前是第几张补发
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	array:	$orderId					小老板单号
	 +---------------------------------------------------------------------------------------------
	 * @return	int 发货次数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/09/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	 static public function getReOrderSequenceNumber($orderID){
		return OrderGetDataHelper::getReOrderSequenceNumber($orderID);
	 }//end of function  getReOrderSequenceNumber
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据虚拟发货结果， 1.自动 设置订单标记发货状态 ；2. 更新更新dashboard 数据  3.合并订单处理
	 * 
	 * 合并订单 虚拟发货结果处理原则 ：
	 * 	优先级：未提交 > 提交失败 > 提交中 > 提交成功
	 * 	有一张订单未提交，则合并订单为未提交
	 * 	再检测有一张订单失败， 合并的订单也是失败
	 * 	再检测有一张订单提交中， 合并的订单也是提交中
	 * 	所有订单是成功，才能算是成功
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderId						string 	小老板订单号 
	 * @param	  $syncShippedStatus			string  标记发货是否成功'P'为待提交,'S'为提交中,'C'为提交成功（小老板）,'Y'为提交成功（非小老板） ,'F'为提交失败 , 
	 * @param	  $delivery_time				int  	发货时间-1为默认值 ， 会使用服务器时间 ， 时间戳表示 使用该参数时间（暂时主要 用于amazon）
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack				接口调用是否成功 true 为正常， false 为异常
	 * 									message			失败，异常的原因 
	 * 									code			成功2000，失败，异常的（40000+） 
	 * 									data			接口返回的数据
	 *
	 * @invoking					OrderApiHelper::setOrderSyncShippedStatus('1' ,'P',-1);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/9/03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderSyncShippedStatus($orderId , $syncShippedStatus,$delivery_time=-1){
		return OrderBackgroundHelper::setOrderSyncShippedStatus($orderId, $syncShippedStatus,$delivery_time);
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 为采购模块自动增强过滤无效条件的接口
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 								$query						order::find()
	 +---------------------------------------------------------------------------------------------
	 * @return						$query
	 * @invoking					OrderApiHelper::formatQueryOrderConditionPurchase($query);
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/09/21				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function formatQueryOrderConditionPurchase(&$query){
		return OrderGetDataHelper::formatQueryOrderConditionPurchase($query);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单设置 为提交成功 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 								$orderIdList						小老板订单号
	 * 								$module								模块
	 * 								$action								操作
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack								执行结果是还成功
	 * 									message							执行的相关信息
	 * 									code							执行的代码
	 * 									data							执行结果返回的数据
	 * @invoking					OrderApiHelper::setOrderSyncShipStatusComplete($orderIdList);
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/09/22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderSyncShipStatusComplete($orderIdList,$module='order',$action='标记提交完成'){
		return OrderBackgroundHelper::setOrderSyncShipStatusComplete($orderIdList,$module , $action);
	}//end of function setOrderSyncShipStatusComplete
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单设置 为验证通过 ，  ebay 订单为paypal地址已同步
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 								$orderIdList						小老板订单号
	 * 								$module								模块
	 * 								$action								操作
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack								执行结果是还成功
	 * 									message							执行的相关信息
	 * 									code							执行的代码
	 * 									data							执行结果返回的数据
	 * @invoking					OrderApiHelper::setOrderVerifyPass($orderIdList);
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderVerifyPass($orderIdList ,$module='order',$action='通过验证'){
		return OrderBackgroundHelper::setOrderVerifyPass($orderIdList,$module,$action);
	}//end of function setOrderVerifyPass
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单利润信息  封装
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	array:	$params					查询条件
	 * @param	int:	$is_sum					是否合计信息，0否1是
	 +---------------------------------------------------------------------------------------------
	 * @return	无
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2016/09/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderStatisticsInfo($params, $is_sum){
		return OrderGetDataHelper::getOrderStatisticsInfo($params, $is_sum);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取客选物流
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	array:	$orderIdList					小老板单号数组
	 * @param	string: $module							模块
	 * @param	string:	$action 						执行动作
	 +---------------------------------------------------------------------------------------------
	 * @return	
	 * 			ack								执行结果
	 * 			code							执行代号
	 * 			message							执行信息
	 * 			data							执行返回内容
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/08/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getCustomerShippingMethod($orderId ){
		if ($orderId instanceof OdOrder){
			$order = $orderId;
		}else{
			if (is_string($orderId) ||is_int($orderId))
				$order = OdOrder::find()->where(['order_id'=>$orderId])->asArray()->one();
			else 
				return ['ack'=>'failure', 'code'=>'4000' , 'message'=>'参数不正确' ,'data'=>[]];
		}
		
		
		switch ($order['order_source']){
			case 'aliexpress':
				if (!empty($order['addi_info'])){
					$addi_info_arr= json_decode($order['addi_info'],true);
				}
				
				if (!empty($addi_info_arr['shipping_service'])){
					$customerShippingMethod = $addi_info_arr['shipping_service'];
				}else{
					$customerShippingMethod = [];
				}
				break;
			case 'cdiscount':
				$rt = \eagle\modules\carrier\helpers\CarrierHelper::getCdiscountBuyerShippingServices();
				if (array_key_exists($order['order_source_shipping_method'] ,$rt)){
					$customerShippingMethod =[ $rt[$order['order_source_shipping_method']]];
				}else{
					if (!empty($order['order_source_shipping_method'])){
						$customerShippingMethod =[$order['order_source_shipping_method']] ;
					}else{
						$customerShippingMethod = [];
					}
				}
				//$customerShippingMethod = [\eagle\modules\carrier\helpers\CarrierHelper::getCdiscountBuyerShippingServices()[$order['order_source_shipping_method']]];
				break;
			default:
				if (!empty($order['order_source_shipping_method'])){
					$customerShippingMethod =[$order['order_source_shipping_method']] ;
				}else{
					$customerShippingMethod = [];
				}
				
				break;
		}
		return ['ack'=>'success', 'code'=>'200' , 'message'=>'', 'data'=>$customerShippingMethod];
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置指定账号的订单显示与隐藏
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	string:	$platform				订单的平台
	 * @param	string:	$selleruserid			订单的账号
	 * @param	string:	$isShow					Y为显示 ， N为隐藏
	 * @param	string:	$siteid					站点id（目前lazada专用）
	 +---------------------------------------------------------------------------------------------
	 * @return	无
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/09/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function resetOrderVisibleByAccount($platform , $selleruserid, $isShow='Y',$siteid=''){
		//增加一个解绑log记录解绑订单是否正常
		\Yii::info("uid:".\Yii::$app->subdb->getCurrentPuid()." platform:".$platform." selleruserid:".$selleruserid." isshow:".$isShow." siteid:".$siteid,"file");
		if (!empty($siteid)){
			//lazada 可以只解绑一个站点的， 不能按账号级别来修改
			return OdOrder::updateAll(['isshow'=>$isShow],['selleruserid'=>$selleruserid , 'order_source'=>$platform, 'order_source_site_id'=>$siteid]);
		}else{
			return OdOrder::updateAll(['isshow'=>$isShow],['selleruserid'=>$selleruserid , 'order_source'=>$platform]);
		}
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 缺货、暂停发货订单，恢复原来发货状态
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/09/25				初始化
	 +----------------------------------------------------------
	 **/
	public static function recoveryOrders($orderIdList ,$module='order',$action='恢复发货'){
		return OrderHelper::recoveryOrders($orderIdList,$module,$action);
	}
	
}