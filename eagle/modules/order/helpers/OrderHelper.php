<?php
namespace eagle\modules\order\helpers;
use Exception;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\order\helpers\QueueShippedHelper;
use common\helpers\Helper_Array;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use common\api\ebayinterface\shopping\getsingleitem;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\message\helpers\TrackingMsgHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\models\OdOrderOld;
use eagle\modules\util\helpers\PlatformUtil;
use eagle\models\SaasCdiscountUser;
use eagle\modules\delivery\apihelpers\DeliveryApiHelper;
use eagle\modules\order\models\OdOrderGoods;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\models\OrderSystagsMapping;
use eagle\models\QueueManualOrderSync;
use common\api\trans\transBAIDU_2;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\order\models\OrderRelation;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\models\SaasPriceministerUser;
use console\helpers\QueueGetorderHelper;

use eagle\modules\order\models\Excelmodel;
use eagle\models\catalog\Product;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\carrier\controllers\CarrierprocessController;
use Qiniu\json_decode;
use eagle\models\SysCountry;
use eagle\modules\carrier\helpers\CarrierDeclaredHelper;
use eagle;
use Stripe\Order;
use eagle\modules\util\helpers\MailHelper;
use eagle\modules\permission\helpers\UserHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
/**
 +------------------------------------------------------------------------------
 * 订单模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class OrderHelper {
	public static $ManualOrderFrequencyCountryPath = 'manual order frequency country';
	
	public static $OrderCommonJSVersion = '1.224';
	public static $ImportPlatformOrderVersion = '1.146';
	
	// 等待虚拟发货的状态
	public static $waitSignShipStaus = [
		'aliexpress'=>['WAIT_SELLER_SEND_GOODS','SELLER_PART_SEND_GOODS'],
		'cdiscount '=>['WaitingForShipmentAcceptation'],
		'priceminister'=> ['current'],
		'lazada'=>['pending'],
		'jumia'=>['pending'],
		'linio'=>['pending'],
		'wish'=>['APPROVED'],
		'bonanza'=>['Complete,Active'],
		'dhgate'=>['103001','105003'],
	];
	
	// 允许
	public static $CanShipOrderItemStatus = [
		'lazada'=>['pending'],
		'priceminister'=> ['COMMITTED','ACCEPTED'],
		'cdiscount '=>['AcceptedBySeller'],
		'newegg'=>['Unshipped'],
	];
	
	
	public static $OMSMenuCachePath = [
		'aliexpress'=>'Order/aliexpressOMSMenuData',
	];
	
	//oms 2.1 通用操作
	public static $BaseOperationList = [
		'addMemo'=>'添加备注',
		'addPointOrigin'=>'添加发货地',
		//'sendEmail'=>'发送邮件',
		//'refreshOrder'=>'删除并重新同步订单',
		'signcomplete'=>'标记为已完成',//此功能为了兼容2.0面的还在发货中的订单实际已经发货的订单进行批量移动到已完成
		'setSyncShipComplete'=>'标记为提交成功',
		//'abandonOrder'=>'废弃订单',
		//'addCustomTag'=>'添加修改自定义标签',
	];
	
	//oms2.1 通用批量 操作
	public static $BaseBatchOperationList = [
		''=>'批量操作',
		//'exportExcel'=>'导出订单',
	];
	//oms2.1 通用单独 操作
	public static $BaseSingleOperationList = [
		''=>'操作',
		'editOrder'=>'编辑订单',
		'history'=>'查看订单操作日志',
		//'replyMessage'=>'回复留言',
	];
	
	//订单默认值
	public static $order_demo=
	[
			'order_status'=>'',//'订单流程状态:100:未付款,200:已付款,400:待发货,500:已发货,600:已取消',
			'order_manual_id'=>0,//自定义标签
			'pay_status'=>0,//付款状态
			'shipping_status'=>0,//仓库是否发货
			'is_manual_order'=>0,//是否挂起
			'order_source'=>'',//ebay,amazon,aliexpress,wish,custom
			'order_type'=>'',//订单类型如amazon FBA订单
			'order_source_order_id'=>'',//订单来源平台订单号
			'order_source_site_id'=>'',//订单来源平台下的站点：如eaby下的US站点
			'selleruserid'=>'',//订单来源平台卖家用户名(下单时候的用户名)
			'saas_platform_user_id'=>0,//saas库平台用户卖家账号id(ebay或者amazon卖家表中)
			'order_source_srn'=>0,//'od_ebay_order表salesrecordnum'
			'customer_id'=>0,//'od_customer 表id'
			'source_buyer_user_id'=>'',//来源买家用户名
			'order_source_shipping_method'=>'',//平台下单时用户选择的运输服务
			'order_source_create_time'=>0,//订单在来源平台的下单时间
			'subtotal'=>'0.00',//产品总价格
			'shipping_cost'=>'0.00',//运费
			'discount_amount'=>'0.00',//折扣
			'grand_total'=>'0.00',//合计金额(产品总价格 + 运费 - 折扣 = 合计金额)
			'returned_total'=>'0.00',//退款总金额
			'price_adjustment'=>'0.00',//价格手动调整（下单后人工调整）
			'currency'=>'',//货币
			'consignee'=>'',//收货人
			'consignee_postal_code'=>'',//收货人邮编
			'consignee_phone'=>'',//收货人电话
			'consignee_email'=>'',//收货人Email
			'consignee_company'=>'',//收货人公司
			'consignee_country'=>'',//收货人国家名
			'consignee_country_code'=>'',//收货人国家代码
			'consignee_city'=>'',//收货人城市
			'consignee_province'=>'',//收货人省
			'consignee_district'=>'',//收货人区
			'consignee_county'=>'',//收货人镇
			'consignee_address_line1'=>'',//地址1
			'consignee_address_line2'=>'',//地址2
			'consignee_address_line3'=>'',//地址3
			'default_warehouse_id'=>0,//默认的仓库id
			'default_carrier_code'=>'',//默认物流商代码
			'default_shipping_method_code'=>'',//默认运输服务code，匹配出的物流服务ID
			'paid_time'=>0,//订单付款时间
			'payment_type'=>'',//订单付款时间
			'delivery_time'=>0,//订单发货时间
			'user_message'=>'',//用户留言
			'carrier_type'=>0,//0:货代  1:海外仓
			'hassendinvoice'=>0,//是否有发送ebay账单
			'seller_commenttype'=>'',//卖家评价类型
			'seller_commenttext'=>'',//卖家评价留言
			'status_dispute'=>0,//是否有发起ebay催款，0没有
			'is_feedback'=>0,//是否评价
			'order_source_status'=>'',//订单来源平台订单状态
			'items'=>[],//商品信息
			'orderShipped'=>[],//标发货信息
	];
	public static $order_item_demo=
	[
			'order_source_order_id'=>'',//平台订单号
			'order_source_srn'=>0,//'od_ebay_transaction表salesrecordnum'
			'order_source_transactionid'=>0,//订单来源交易号或子订单号
			'order_source_itemid'=>0,//产品ID listing的唯一标示
			'order_source_order_item_id'=>'',//对应平台原始订单商品表主键
			'sku'=>'',//'商品编码'，一定要保存平台上订单的原始sku
			'product_attributes'=>'',//商品属性
			'product_name'=>'',//下单时标题
			'product_unit'=>'',//单位
			'lot_num'=>1,//单位数量
			'goods_prepare_time'=>1,//备货时间
			'product_url'=>'',//商品url
			'photo_primary'=>'',//商品主图冗余
			'shipping_price'=>'0.00',//运费
			'shipping_discount'=>'0.00',//运费折扣
			'price'=>'0.00',//下单时价格
			'promotion_discount'=>'0.00',//促销折扣
			'ordered_quantity'=>0,//下单时候的数量
			'quantity'=>0,//需发货的商品数量,原则上和下单时候的数量相等，订单可能会修改发货数量，发货的时候按照quantity发货
			'sent_quantity'=>0,//已发货数量,暂时没用
			'packed_quantity'=>0,//打包数量,暂时没用
			'returned_quantity'=>0,//退货数量,暂时没用
			'invoice_requirement'=>'',//发票要求,可不填
			'buyer_selected_invoice_category'=>'',//发票类型,可不填
			'invoice_title'=>'',//发票抬头,可不填
			'invoice_information'=>'',//发票内容,可不填
			'desc'=>'',//订单商品备注,
			'platform_sku'=>'',//平台上订单的原始sku,可不填
			'is_bundle'=>0,//是否捆绑商品
			'bdsku'=>'',//捆绑sku，可不填
			
	];
	public static $order_shipped_demo=
	[		
			'order_source_order_id'=>'',//平台订单号
			'order_source'=>'',//订单来源
			'selleruserid'=>'',//卖家账号
		 	'tracking_number'=>'',
			'tracking_link'=>'',
			'shipping_method_name'=>'',//平台物流服务名
			'addtype'=>'平台API',//物流号来源
	];
	//不同语言称谓映射
	public static $CivilityLangMapping=
	[
	'FR'=>['MR'=>'M.','MRS'=>'Mme','MISS'=>'Mlle'],
	'EN'=>['MR'=>'Mr.','MRS'=>'MRs.','MISS'=>'Miss'],
	'DE'=>['MR'=>'Mr.','MRS'=>'Mrs.','MISS'=>'Miss'],
	'RU'=>['MR'=>'Г - н','MRS'=>'Миссис','MISS'=>'Мисс'],
	'ES'=>['MR'=>'El Sr.','MRS'=>'La Sra.','MISS'=>'La Srta.'],
	'CN'=>['MR'=>'先生','MRS'=>'女士','MISS'=>'小姐'],
	];
	
	//支持发票功能的平台
	//@todo	新增支持时手动添加
	public static $invoice_platform=[
		'cdiscount','amazon','priceminister',
	];
	
	//根据订单目的地国家，变更收件人称谓为对应国家语言的称谓
	public static function getToNationLangCivility($country_code,$civility){
		$toNationLang=TrackingMsgHelper::getToNationLanguage($country_code);
	
		if(preg_match("/^(MR|MR\.)\s/i",$civility)){
			if(isset(self::$CivilityLangMapping[$toNationLang]['MR']))
				$targetCivility = self::$CivilityLangMapping[$toNationLang]['MR'];
			else
				$targetCivility='MR';
			$civility=preg_replace("/^(MR|MR\.)/i",$targetCivility,$civility,1);
		}elseif(preg_match("/^(MRS|MRS\.)\s/i",$civility)){
			if(isset(self::$CivilityLangMapping[$toNationLang]['MRS']))
				$targetCivility = self::$CivilityLangMapping[$toNationLang]['MRS'];
			else
				$targetCivility='MRS';
			$civility=preg_replace("/^(MRS|MRS\.)/i",$targetCivility,$civility,1);
		}elseif(preg_match("/^MISS\s/i",$civility)){
			if(isset(self::$CivilityLangMapping[$toNationLang]['MISS']))
				$targetCivility = self::$CivilityLangMapping[$toNationLang]['MISS'];
			else
				$targetCivility='MISS';
			$civility=preg_replace("/^MISS/i",$targetCivility,$civility,1);
		}
	
		return $civility;
	}
	/**
	 +----------------------------------------------------------
	 * 平台订单导入订单表方法
	 *  $order =['用户id'=>[
	 *  'order_status'=>'',//'订单流程状态:100:未付款,200:已付款,400:待发货,500:已发货,600:已取消',
		'order_manual_id'=>0,//自定义标签
		'pay_status'=>0,//付款状态
		'shipping_status'=>0,//仓库是否发货
		'is_manual_order'=>0,//是否挂起
		'order_source'=>'',//ebay,amazon,aliexpress,wish,custom
		'order_type'=>'',//订单类型如amazon FBA订单
		'order_source_order_id'=>'',//订单来源平台订单号
		'order_source_site_id'=>'',//订单来源平台下的站点：如eaby下的US站点
		'selleruserid'=>'',//订单来源平台卖家用户名(下单时候的用户名)
		'saas_platform_user_id'=>0,//saas库平台用户卖家账号id(ebay或者amazon卖家表中)
		'order_source_srn'=>0,//'od_ebay_order表salesrecordnum'
		'customer_id'=>0,//'od_customer 表id'
		'source_buyer_user_id'=>'',//来源买家用户名
		'order_source_shipping_method'=>'',//平台下单时用户选择的运输服务
		'order_source_create_time'=>0,//订单在来源平台的下单时间
		'subtotal'=>'0.00',//产品总价格
		'shipping_cost'=>'0.00',//运费
		'discount_amount'=>'0.00',//折扣
		'grand_total'=>'0.00',//合计金额(产品总价格 + 运费 - 折扣 = 合计金额)
		'returned_total'=>'0.00',//退款总金额
		'price_adjustment'=>'0.00',//价格手动调整（下单后人工调整）
		'currency'=>'',//货币
		'consignee'=>'',//收货人
		'consignee_postal_code'=>'',//收货人邮编
		'consignee_phone'=>'',//收货人电话
		'consignee_email'=>'',//收货人Email
		'consignee_company'=>'',//收货人公司
		'consignee_country'=>'',//收货人国家名
		'consignee_country_code'=>'',//收货人国家代码
		'consignee_city'=>'',//收货人城市
		'consignee_province'=>'',//收货人省
		'consignee_district'=>'',//收货人区
		'consignee_county'=>'',//收货人镇
		'consignee_address_line1'=>'',//地址1
		'consignee_address_line2'=>'',//地址2
		'consignee_address_line3'=>'',//地址3
		'default_warehouse_id'=>0,//默认的仓库id
		'default_carrier_code'=>'',//默认物流商代码
		'default_shipping_method_code'=>'',//默认运输服务code，匹配出的物流服务ID
		'paid_time'=>0,//订单付款时间
		'delivery_time'=>0,//订单发货时间
		'user_message'=>'',//用户留言
		'carrier_type'=>0,//0:货代  1:海外仓
		'hassendinvoice'=>0,//是否有发送ebay账单
		'seller_commenttype'=>'',//卖家评价类型
		'seller_commenttext'=>'',//卖家评价留言
		'status_dispute'=>0,//是否有发起ebay催款，0没有
		'is_feedback'=>0,//是否评价
		'order_source_status'=>'',//订单来源平台订单状态
		'items'=>[0=>array(),1=>array()],//商品信息,array格式如$order_item_demo
		'orderShipped'=>[0=>array(),1=>array()],//标发货信息array格式如$order_shipped_demo
		]]
		
	 * @param $eagleOrderId ------ 指定eagle的order id来插入订单。	
	 * @return				订单id
	 **/
	public static function importPlatformOrder($order,$eagleOrderId=-1) {
		$result = array();
		if (count($order) > 0) {
			foreach ($order as $uid => $or) {
				$logTimeMS1=TimeUtil::getCurrentTimestampMS();
				if(empty($uid) || $uid == 0) {
					$result['success'] = 1;
					$result['message'] = 'E001传入uid有误' ;
					return $result;
				}
				 
 
				
				$os = (Array)$or;
				//liang 2015-08-18 -s
				if (isset($os['consignee_country_code'])){
					$os['consignee']=self::getToNationLangCivility($os['consignee_country_code'], $os['consignee']);
				}
				
				//liang 2015-08-18 -e 50为bonanza未接受状态
				if (in_array($os['order_status'], array(50, 100, 200,300, 400, 500, 600))) {
					if(empty($os) || empty($os['order_source']) || empty($os['order_source_order_id'])) {
						$result['success'] = 1;
						$result['message'] = 'E003订单信息不完整';
						return $result;
					}
					
					if (in_array(strtolower($os['order_source']) , ['aliexpress'])){
						// 观察速卖通更新失败问题
						\Yii::info("importPlatformOrder_call uid=$uid ,order_source_order_id=".$os['order_source_order_id'].",order ".((count($order)>1)?'multiple':'single')."_count = ".count($order),"file");	
					}
					
					//lolo20150916根据订单平台创建时间来判断是新订单还是旧订单
					//对于旧的订单跳过 部分逻辑  ----  转商品，客服模块 buyer订单信息统计，订单自动检查，保存物流号
					//timestamp 1431187200---2015-5-10 0:0:0,5月10号前的算旧订单
					$orderCreateTime=$os['order_source_create_time'];
					$orderCreateTimeStr="-";
					$isRecentOrder=true;
					$OLDORDERTIMESTAMP=1431187200;  //timestamp 1431187200---2015-5-10 0:0:0
										
					if ($orderCreateTime>0 and  $orderCreateTime<$OLDORDERTIMESTAMP)  $isRecentOrder=false;
					if ($orderCreateTime>0) $orderCreateTimeStr=date("Y-m-d H:i:s",$orderCreateTime);
					
					// order_capture 存在 并且 等于Y的时候认为 是手工订单
					if (isset($os['order_capture']) && $os['order_capture'] =='Y'){
						$orderCapture = 'Y';
					}else{
						$orderCapture = 'N';
					}
					$od_query = OdOrder::find()
						->where("`order_source` = :os AND `order_source_order_id` = :osoi",[':os'=>$os['order_source'],':osoi'=>$os['order_source_order_id']]);
					//现在发现某些平台可能存在重复的order_source_order_id，因此还需要做seller判断	2016-08-23 lzhl
					if(!empty($os['selleruserid']))
						$od_query->andWhere(['selleruserid'=>$os['selleruserid']]);
					
					//20161220 lazada 中不同站点有相同 的订单号
					if(!empty($os['order_source_site_id']))
						$od_query->andWhere(['order_source_site_id'=>$os['order_source_site_id']]);					
					
					$odOrder = $od_query->andWhere(['order_capture'=>$orderCapture])->one(); //避免手工订单与普通订单 order source order id 混淆
					
					//echo $od_query->andWhere(['order_capture'=>$orderCapture])->createCommand()->getRawSql();
					
					
					//lolo20150931 order-archive begin。   对旧的订单只做插入！！！！！！！！！！！！！
						
					if ($isRecentOrder===false){ //旧订单
						$orderSource=$os['order_source'];
						if ($isRecentOrder) $isRecentOrderInt=1; else $isRecentOrderInt=0;
						
						if (!empty($odOrder)){ //订单在od_order_v2，直接返回(旧订单太旧，就算在od_order_v2也不做update)
							\Yii::info("importPlatformOrder_  old_skip isRecentOd=$isRecentOrderInt,odStatus=".$os['order_status'].",odSrc=$orderSource,puid=$uid","file");								
							$result['success'] = 0;
							$result['message'] = 'E009同步成功';
							return $result;								
						}
						$odOrderOld =OdOrderOld::find()->where("`order_source` = :os AND `order_source_order_id` = :osoi",[':os'=>$os['order_source'],':osoi'=>$os['order_source_order_id']])->one();
						if (!empty($odOrderOld)){//订单在od_order_old_v2，直接返回
							\Yii::info("importPlatformOrder_ old_skip2 isRecentOd=$isRecentOrderInt,odStatus=".$os['order_status'].",odSrc=$orderSource,puid=$uid","file");								
							$result['success'] = 0;
							$result['message'] = 'E009同步成功';
							return $result;								
						}
					}
					//lolo20150931 order-archive end
					
					
					$logTimeMS2=TimeUtil::getCurrentTimestampMS();
					
					
					try {
						if (!empty($odOrder)){
							//旧订单 是否有存在虚拟发货记录 ， 有则忽略（S,F,C）
							if (! in_array($odOrder->sync_shipped_status, ['S','F','C'])){
								//2017-11-27 更新订单时，getCurrentSyncShipStaus  固定OdOrder::STATUS_PAY 是因为有虚拟发货的时候， 这个小老板状态就是不准
								if (isset($os['order_source_status'])){
									list($tmpAck , $tmpMsg , $tmpCode  , $TmpSyncShipStatus) = array_values(self::getCurrentSyncShipStaus($odOrder, OdOrder::STATUS_PAY, $os['order_source_status']));
									
								}else{
									list($tmpAck , $tmpMsg , $tmpCode  , $TmpSyncShipStatus) = array_values(self::getCurrentSyncShipStaus($odOrder, OdOrder::STATUS_PAY, $odOrder->order_source_status));
								}
								$syncShipStatusMsg = "\n set syncshipstatus uid=$uid ,order_source_order_id=".$odOrder->order_source_order_id." order_souce=".$odOrder->order_source.",old order_source_status=".$odOrder->order_source_status.",new order_source_status=".@$os['order_source_status'].",old sync_shipped_status=".$odOrder->sync_shipped_status.",new sync_shipped_status=".@$TmpSyncShipStatus."\n";
							}else{
								$syncShipStatusMsg = "\n no change syncshipstatus uid=$uid ,order_source_order_id=".$odOrder->order_source_order_id." order_souce=".$odOrder->order_source.",old order_source_status=".$odOrder->order_source_status.",new order_source_status=".@$os['order_source_status'].",old sync_shipped_status=".$odOrder->sync_shipped_status.",new sync_shipped_status=".@$TmpSyncShipStatus."\n";
							}
							echo $syncShipStatusMsg;
						}else{
							//新订单
							list($tmpAck , $tmpMsg , $tmpCode  , $TmpSyncShipStatus) = array_values(self::getCurrentSyncShipStaus($os, @$os['order_status'], @$os['order_source_status']));
						}
						
						
						if (isset($tmpAck) ){
							if ($tmpAck == false){
								\Yii::error("sync_ship_status uid=$uid ,order_source_order_id=".$os['order_source_order_id'].",error: ".@$tmpMsg." ,code:".@$tmpCode,"file");
							}
							
						}
						if (!empty($TmpSyncShipStatus)){
							
							$os['sync_shipped_status'] = $TmpSyncShipStatus;
						}
							
					} catch (\Exception $e) {
						\Yii::error("sync_ship_status v1.0 uid=$uid ,order_source_order_id=".$os['order_source_order_id'].",error: ".$e->getMessage()." Line no:".$e->getLine()." tmpMsg:".@$tmpMsg.((!empty($os))?" os:".json_encode($os):''),"file");
						//.((!empty($os))?" os:".json_encode($os):'')
					}
					
					$logTimeMS2A=TimeUtil::getCurrentTimestampMS();
					try {
						//kh20161213 ebay 新订单  检查是否需要同步paypal 地址
						if (in_array($os['order_source'],['ebay'])){
							//
							$isVerifyOrder = QueueGetorderHelper::getOnePaypalAddressOverWrite($os['order_source_order_id'], $os['selleruserid']);
							echo "\n **** verify($isVerifyOrder) ****";
							if (!empty($odOrder)){
								//更新订单时候不管了，留待 paypal 同步队列修改
							}else{
								
								if ($isVerifyOrder=="Y"){
									//开通了paypal 地址为准的订单设置 订单验证为pending
									$os['order_verify'] = OdOrder::ORDER_VERIFY_PENDING;
								}else{
									$os['order_verify'] = '';
								}
								echo "\n order_verify:".$os['order_verify'];
							}
						}
						
						
					} catch (\Exception $e) {
						\Yii::error("order verify v1.0 uid=$uid ,order_source_order_id=".$os['order_source_order_id'].",error: ".$e->getMessage()." Line no:".$e->getLine()." tmpMsg:".@$tmpMsg.((!empty($os))?" os:".json_encode($os):''),"file");
					}
					
					$logTimeMS2B=TimeUtil::getCurrentTimestampMS();
					
					//生成 md5
					try {
						$itemsMD5String = OrderUpdateHelper::createItemMD5($os['order_source'], $os['items']);
							
						if (!empty($itemsMD5String)){
							$os['items_md5'] = $itemsMD5String;
						}
					} catch (\Exception $e) {
						\Yii::error("uid=$uid ,order_source_order_id=".$os['order_source_order_id']." importOrder_noitem_error E026 message:".$e->getMessage()." line no:".$e->getLine(),"file");
					}
					
					$logTimeMS2C=TimeUtil::getCurrentTimestampMS();
					
					//#############################  20170119kh start  设置 first sku  , 多品  与商品的来源   #############################
					try {
						$ignoreSKUList = CdiscountOrderInterface::getNonDeliverySku();
						$itemCount = 0;
						if( empty($odOrder)) {
							//新订单直接 赋值
							if (isset($os['items'])){
								foreach($os['items'] as &$row){
									if ( in_array($row['sku'],$ignoreSKUList ) ==false){
										// 设置  first sku
										if (empty($os['first_sku'])){
											$os['first_sku'] = $row['sku'];
										}
											
										$itemCount++;
									}
								
									//为了简化手工订单等的录入  新增订单记录，否则可能会改写 商品来源的值
									if ($orderCapture =='Y'){
										$row['item_source'] = 'local';
									}else{
										$row['item_source'] = 'platform';
									}
									
									//插入订单检查是否有平台 item状态， 没有 的就按照小老板订单 状态
									if (isset($row['platform_status']) == false){
										$row['platform_status'] = $os['order_status'];
									}
								}//end of each item
							}//end of validate $os['items'] 
							
							
						}else{
							//旧订单 检查first sku ,多品标志是否需要更新
							if (isset($os['items'])){
								//新订单直接 赋值
								foreach($os['items'] as &$row){
									if ( in_array($row['sku'],$ignoreSKUList ) ==false){
										// 设置  first sku
										if (empty($os['first_sku'])){
											$os['first_sku'] = $row['sku'];
										}
								
										$itemCount++;
									}
									
								}//end of each item 
							}//end of validate $os['items'] 
						}//end of update order 
						 
						
						//设置   多品标记
						if ($itemCount> 1){
							$os['ismultipleProduct'] = "Y";
						}else{
							$os['ismultipleProduct'] = "N";
						}
						
					} catch (Exception $e) {
						\Yii::error("uid=$uid ,order_source_order_id=".$os['order_source_order_id']." importOrder_iteminfo_error E027 message:".$e->getMessage()." line no:".$e->getLine(),"file");
					}
					//#############################  20170119kh end  设置 first sku  , 多品  与商品的来源    #############################
					
					$logTimeMS2D=TimeUtil::getCurrentTimestampMS();
					
					$operationType="";
					if(! empty($odOrder)) {
						//############################ 更新 订单 逻辑 部分    ############################
						$operationType="update";
						$oldSyncShipStatus = $odOrder->sync_shipped_status;
						$oldOrderStatus = $odOrder->order_status;
						$oldItemMd5 = $odOrder->items_md5;
						if (isset($os['sync_shipped_status'])){
							if (in_array($os['sync_shipped_status'],['S','F','C'])){
								/* 20160927
								 * 这三种状态应该是虚拟发货流程上设置， 同步订单不设置这三个值
								* 同步 订单能设置 为P(未提交),N（无须提交）,Y（提交成功非小老板）
								*/
								$os['sync_shipped_status'] = $odOrder->sync_shipped_status;
							}
						}
						
						//echo 'update ebay order';
						$os['order_id'] = $odOrder->order_id;
						
						$res = self::doUpdate($os,$odOrder->order_status);
						
						$logTimeMS3=TimeUtil::getCurrentTimestampMS();
						
					//	\Yii::info("importPlatformOrder_update  eOrderId=$eagleOrderId,t2_1=".($logTimeMS2-$logTimeMS1).
						//		",t3_2=".($logTimeMS3-$logTimeMS2).",puid=$uid","file");
						$logTimeMS4=$logTimeMS3;//为了log记录方便。
			 
						if ( empty($res)) {
							$result['success'] = 1;
							$result['message'] = 'E004更新订单信息失败';
							return $result;
						}
						//md5值只有在有item 状态的订单才会有， 没有的都为空
						//echo "<br> item count:".count($odOrder->items)."<br>";
						if ( $res == 'noChange' && count($odOrder->items)>0) {
							$result['success'] = 0;
							$result['message'] = '订单信息已经更新，请不要重复调用！';
							
							if ($isRecentOrder && ($orderCapture =='N')){
								if (!empty($os['order_id'])){
									self::saveTrackingNumber($os['order_id'],@$os['orderShipped'],1,false);
								}
								
							}
							
							$GlobalCacheLogMsg = "\n final  ".@$os['order_source']."_importPlatformOrder_noChange ,puid=".@$uid." ,order_id=".@$os['order_id'].",order_type=".@$os['order_type'].",order_source_order_id=".@$os['order_source_order_id'].",odStatus=".@$os['order_status']." , order_source_status=".@$os['order_source_status']." insertOrderAutoCheckQueue v".self::$ImportPlatformOrderVersion;
							echo $GlobalCacheLogMsg;
							return $result;
						}
						
						/*
						 * 2017-01-24
						* 订单引入 了商品级别的状态， 这就是表示 ， 不能只根据订单状态来决定待发货数量，允许发货状态
						* 为兼容商品状态变了， 订单状态不变的情况
						* 引入了分两套流程处理
						* 1.有商品状态 的平台 将会根据商品状态来控制待发货数量，允许发货状态
						* 2.没有 商品状态 的平台 会依旧使用 订单状态来控制  待发货数量，允许发货状态
						* 控制 是否有商品 OrderUpdateHelper::$updateItemAttr 这个 变量存在就是表示有商品
						*
						*/
						try {
							//假如是订单状态级别的订单 更新 订单在状态时 需要及时更新其商品状态， 否则待发货数量，与物流状态会不准
							if (isset(OrderUpdateHelper::$updateItemAttr[$odOrder->order_source]) == false){
								//没有 商品状态的订单需要在订单状态更新时， 更新商品状态
								//FBA,FBC 不更新 item
								if (!in_array(strtoupper($odOrder->order_type), ['AFN' , 'FBC'])){
									// 订单状态发生了变化
									if ($oldOrderStatus != $os['order_status']){
										if ($odOrder->order_relation =='fm'){
											// 原始订单 不更新 待发货数量 ， 合并订单才更新 合并待发货数量
											$updateItemAddInfo = ['isUpdatePendingQty'=>false];
										}else{
											//正常订单 更新 待发货数量
											$updateItemAddInfo = [];
										}
										
										foreach($odOrder->items as $tmpUpdateItem){
											$tmpUpdateItem['platform_status'] = $os['order_status'];
											$tmpRt = OrderUpdateHelper::updateItem($odOrder->order_source, $odOrder->order_id, $tmpUpdateItem, $updateItemAddInfo,  'System','同步订单','order');
											if ($odOrder->order_relation =='fm'){
												//更新 相关的合并订单的信息
												OrderUpdateHelper::updateRelationOrderItem($odOrder->order_id, $tmpUpdateItem);
											}
										}//end of each item
										
									}// end of status change
								}//end of validate order_type
								
							} //end of 
							
						} catch (\Exception $e) {
							$catchErrorMsg = "uid=$uid ,order_source_order_id=".$os['order_source_order_id']." importOrder_order_status_set_item_status_error E029 message:".$e->getMessage()." line no:".$e->getLine();
							echo $catchErrorMsg;
							\Yii::error($catchErrorMsg,"file");
						}
						
						/*
						 * 检查订单状态是否需要更新待发货数量  
						 * 1.未付款=>已付款
						 * 2.已付款=>已完成
						 * 3.已付款=>已取消
						 */
						try {
							
							//echo "<br> order_status :".$odOrder->order_status;
							//状态变化了才执行更新待发货数量 ，
							if (($oldOrderStatus != $os['order_status']) && ($os['order_status'] > $oldOrderStatus)){
								$OriginStatus = $oldOrderStatus;
								$CurrentStatus = $os['order_status'];
								$OriginWarehouseID = $odOrder->default_warehouse_id; // 当前订单的仓库id
								$CurrentWarehouseID = $odOrder->default_warehouse_id;// 当前订单的仓库id
								$callbackErrorMsg = '';
								//已付款变成 不需要发货状态 时需要更新待发货数量
								foreach($odOrder->items as $tmpitem){
									//获取 item 的属性
									$item = $tmpitem->getAttributes();
								
									//list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
									$rootSKU = $tmpitem->root_sku;
									if (!empty($rootSKU)){
										//默认值
										$OriginQty = $item['quantity'];
										$CurrentQty = $item['quantity'];
										
										//已付款,发货中=》取消	, 暂停 ， 已完成 ， 由需要算发货变成不需要算发货
										$zeroStatusList = [OdOrder::STATUS_CANCEL , OdOrder::STATUS_SUSPEND , OdOrder::STATUS_SHIPPED , OdOrder::STATUS_NOPAY ];
										
										if ( in_array($OriginStatus, [OdOrder::STATUS_PAY,OdOrder::STATUS_WAITSEND])  &&  in_array($CurrentStatus,$zeroStatusList))	{
											$OriginQty = $item['quantity'];
											$CurrentQty = 0;
										}
										
										// 取消	, 暂停 ， 已完成 =》 已付款   由不算发货变成需要算发货
										if ($CurrentStatus == OdOrder::STATUS_PAY &&  in_array($OriginStatus,$zeroStatusList)){
											$OriginQty = 0;
											$CurrentQty = $item['quantity'];
										}
										
										//echo "$rootSKU, $OriginWarehouseID, $CurrentWarehouseID, $OriginQty, $CurrentQty";
										$tmpRT = OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, $OriginWarehouseID, $CurrentWarehouseID, $OriginQty, $CurrentQty);
										if ($tmpRT['ack']==false){
											$callbackErrorMsg .= $tmpRT['message'];
										}
									}
										
									
								}
									
								if (!empty($callbackErrorMsg))
									echo "uid=$uid ,order_source_order_id=".$os['order_source_order_id']." updateUnshippedQtyOMS_Error:" .$callbackErrorMsg;
							}
							
							
							
						} catch (\Exception $e) {
							$catchErrorMsg = "uid=$uid ,order_source_order_id=".$os['order_source_order_id']." importOrder_change_pending_qty_error E028 message:".$e->getMessage()." line no:".$e->getLine();
							echo $catchErrorMsg;
							\Yii::error($catchErrorMsg,"file");
						}
						
						//########################################   item 级别的更新       ########################################
						try {
							//$itemsMD5String = OrderUpdateHelper::createItemMD5($os['order_source'], $os['items']);
							if (!empty($itemsMD5String)){
								//$os['items_md5'] = $itemsMD5String;
								//比较  item的md5 是否有变化
								if ($oldItemMd5 != $itemsMD5String){
									echo "<br> md5 has change  <br>".$oldItemMd5."<br> ".$itemsMD5String."<br>";
									$odOrder->items_md5 = $itemsMD5String;
									$odOrder->save();
									// 原始订单 不更新 待发货数量 ， 合并订单才更新 合并待发货数量
									if ($odOrder->order_relation =='fm'){
										$updateItemAddInfo = ['isUpdatePendingQty'=>false];
									}else{
										$updateItemAddInfo = [];
									}
									
									foreach($os['items'] as $tmpUpdateItem){
										$tmpRt = OrderUpdateHelper::updateItem($odOrder->order_source, $odOrder->order_id, $tmpUpdateItem, $updateItemAddInfo,  'System','同步订单','order');
										
										//@todo 检查一下合并订单的回写机制
										/*
										 * lazada , cd, pm, newegg 订单状态变为cancel ， 对应的item 状态也会变成cancel ， 所以可以直接根据item状态来控制合并订单的状态
										 */
										if ($odOrder->order_relation =='fm'){
											//更新 相关的合并订单的信息
											echo "<br> fm order ";
											OrderUpdateHelper::updateRelationOrderItem($odOrder->order_id, $tmpUpdateItem);
										}
									}
								}else{
									echo "<br> md5 no change  <br>";
								}
							}
						} catch (\Exception $e) {
							$catchErrorMsg = "uid=$uid ,order_source_order_id=".$os['order_source_order_id']." importOrder_md5_error E027 message:".$e->getMessage()." line no:".$e->getLine();
							echo $catchErrorMsg;
							\Yii::error($catchErrorMsg,"file");
						}
						
						//########################################   ebay 没有拉到item bug  重拉    ########################################
						try {
							//暂时 ebay 没有 item
							if (! in_array($odOrder->order_source, ['ebay'])){
								if(! empty($os['items']) && ! empty($os['order_id']) && count($odOrder->items)==0) {
									echo "uid=$uid ,order_source_order_id=".$os['order_source_order_id']." no item then reset it! ";
									foreach($os['items'] as $k => $v) {
										$os['items'][$k] = (Array)$v;
										$os['items'][$k]['order_id'] = $os['order_id'];
										$os['items'][$k]['create_time'] = time();
										$os['items'][$k]['update_time'] = time();
									}
									
									//是否更新待发货数量逻辑
									$doPIp_updatePendingQty = false;
									if(isset($os['order_type'])){
										if (!in_array(strtoupper($os['order_type']), ['AFN' , 'FBC'])){
											$doPIp_updatePendingQty = true;
										}else{
											$doPIp_updatePendingQty = false;
										}
									}else{
										$doPIp_updatePendingQty = true;
									}
									
									if ($doPIp_updatePendingQty){
										if (in_array($os['order_status'], [OdOrder::STATUS_PAY])){
											$doPIp_updatePendingQty = true;
										}else{
											$doPIp_updatePendingQty = false;
										}
									}
									$pro_res = self::doProductImport($os['items'], $os['order_id'], $os['order_source'],$doPIp_updatePendingQty,$os['order_status'] , $os['selleruserid'] );
									if (! $pro_res) {
										//SysLogHelper::SysLog_Create("order",__CLASS__, __FUNCTION__,"Error 5.1","Import order, preservation order goods failure,odOrderItemParams:".print_r($os['items'],true)."error:".$pro_res, "Error");
										$result['success'] = 1;
										$result['message'] = 'E022写入订单商品信息失败';
										echo $result['message'];
										//return $result;
									}
								}//end of items 和 order_id
							}
						} catch (\Exception $e) {
							\Yii::error("uid=$uid ,order_source_order_id=".$os['order_source_order_id']." importOrder_noitem_error E022 message:".$e->getMessage()." line no:".$e->getLine(),"file");
						}
						
						//############################ 更新 订单 逻辑 部分   ############################
					} else {
						//############################ 新增 订单 逻辑 部分   ############################
						try {
								$oldSyncShipStatus = '';
								//kh20151219  wish ， cdisoucnt 合并之后， 旧订单不再同步 下来
								$isMergeOrder = false;
								/*
								 * $CheckMergePlatform
								* ebay   		本来做了特殊化处理 故不列入$CheckMergePlatform
								* aliexpress   商品使用order_source_itemid （product_id）
								* wish		    order_source_itemid 由于 订单只有一个	商品直接使用order_source_itemid （product_id）
								* cdiscount    负责人确定 这个可以 使用 （order_source_itemid）
								* amazon       order_source_order_item_id （交易号） 因为 前缀有可能 为0 会自动省略所以用 order_source_itemid（asin）代替
								* dhgate       没有交易号直接 用 order_source_itemid
								* lazada ，linio ，jumia 平台不允许合并 , 不过相关负责 人要求加入防止merge后出来的订单
								*	ensogo      可以合并订单
								*  priceminister  可以合并订单
								* */
								$CheckMergePlatform = ['wish','cdiscount'  ,'aliexpress', 'amazon' , 'dhgate' , 'ensogo' , 'priceminister'];
								if(in_array($os['order_source'], $CheckMergePlatform)){
									//检查 是否合并过的订单
									if (! empty($os['items'])){
										foreach($os['items'] as $tmpItem){
											$itemCount = OdOrderItem::find()->where(['order_source_itemid'=>$tmpItem['order_source_itemid'] , 'order_source_order_id'=>$os['order_source_order_id']])->count();
											//echo "<br>".$tmpItem['order_source_itemid'].' and '.$os['order_source_order_id'].' = '.$itemCount;
											if ($itemCount>0){
												//并过的订单
												$isMergeOrder = true;
												echo "\n ".$os['order_source_order_id']." already merged , not import again";
												break;
											}
										}
								
										if ($isMergeOrder == false){
											//不是合并订单才生成订单头
											//new order
											$operationType="insert";
											//echo 'insert ebay order';
											
											$importRT = self::doInsert($os,$eagleOrderId);
											if (!empty($importRT['success'])){
												$os['order_id'] = $importRT['order_id'];
								
											}else{
												$result['success'] = 1;
												$result['message'] = 'E015'.$importRT['message'];
												return $result;
											}
											//$os['order_id'] = self::doInsert($os,$eagleOrderId);
											//echo "\n ".$os['order_source_order_id'].' insert it ';
										}else{
											echo "\n ".$os['order_source_order_id'].' skip it ';
											$operationType="skip";
										}
									}else{
										return ['success'=>1 ,  'message'=>'E12订单详情为空！'];
									}
								
								}else{
									//kh20151219 原来的共用逻辑 start
									$operationType="insert";
									/*
									try {
										$itemsMD5String = OrderUpdateHelper::createItemMD5($os['order_source'], $os['items']);
											
										if (!empty($itemsMD5String)){
											$os['items_md5'] = $itemsMD5String;
										}
									} catch (\Exception $e) {
										\Yii::error("uid=$uid ,order_source_order_id=".$os['order_source_order_id']." importOrder_noitem_error E025 message:".$e->getMessage()." line no:".$e->getLine(),"file");
									}
									*/
									//echo 'insert ebay order';
									$importRT = self::doInsert($os,$eagleOrderId);
									if (!empty($importRT['success'])){
										$os['order_id'] = $importRT['order_id'];
										echo "\n ".$importRT['order_id'].' status='.$os['order_status'];
											
									}else{
										$result['success'] = 1;
										$result['message'] = 'E016'.$importRT['message'];
										return $result;
									}
									//echo 'insert ebay order';
									//$os['order_id'] = self::doInsert($os,$eagleOrderId);
									//kh20151219 原来的共用逻辑 end；
								}
								
								
								$logTimeMS3=TimeUtil::getCurrentTimestampMS();
								
								
								//对于旧的订单跳过 部分逻辑  ----  转商品，客服模块 buyer订单信息统计，订单自动检查，保存物流号
								if ($isRecentOrder && (! $isMergeOrder)){
									//FR120 yzq 2015-8-13, 添加逻辑，在InsertOrder的时候，通知客服模块，更新这个客户的累计数量。
									echo "YS2.0 FR120, start to insert customer info for order ".$os['order_source_order_id']."\n";
									$oneOrderParam =array_merge(OrderHelper::$order_demo,$os);
										
									$params = array(
											'platform_source' => $oneOrderParam['order_source'],
											'seller_id' => $oneOrderParam['selleruserid'], //卖家Id
											'customer_id' => $oneOrderParam['source_buyer_user_id'], //买家唯一id
											'customer_nickname' => $oneOrderParam['consignee'] , //买家呢称
											'order_id' => $oneOrderParam['order_source_order_id'], //平台来源订单单号
											'nation_code' => $oneOrderParam['consignee_country_code'], //国家代码，如CN，US
											'email' => $oneOrderParam['consignee_email'], //email address
											'order_time' => date("Y-m-d H:i:s" ,$oneOrderParam['order_source_create_time']) , //最后一个订单日期
											'currency' => $oneOrderParam['currency'], //货币
											'amount' => $oneOrderParam['grand_total'], //消费金额
									);
										
									if (!empty($oneOrderParam['consignee'])){
										$rtn1 = MessageApiHelper::customerUpdateOrderInfo( $params );
								
										if (empty($rtn1['success']) or !$rtn1['success']) {
											$result['success'] = 1;
											$result['message'] = empty($rtn1['success'])?"E-FR120":$rtn1['success'];
											echo "YS2.0err FR120, error to insert customer info for order ".$os['order_source_order_id'].$result['message']."\n";
											//return $result;
										}
									}
								
								}
								$logTimeMS4=TimeUtil::getCurrentTimestampMS();
								
								//end of FR120
								echo "start 273"."\n";
								
								if(in_array($os['order_source'], $CheckMergePlatform) && $isMergeOrder ){
									//cd wish 合并的订单只更新item数据
									//kh20151219 合并订单后， 新的订单不导入  start
									if(! empty($os['items']) ) {
										$odOrderItem = new OdOrderItem();
										$modelAttr = $odOrderItem->attributes();
										foreach($os['items'] as $k => $v) {
											$thisItem = OdOrderItem::find()->where(['order_source_itemid'=>$v['order_source_itemid'], 'order_source_order_id'=>$os['order_source_order_id']])->one();
								
											if (empty($thisItem)) return ['success'=>1 ,  'message'=>'E11同步合并订单失败'];
											foreach($v as $sk => $sv) {
												//过滤参数
												if(in_array($sk, $modelAttr)) {
													$thisItem->$sk = $sv;
												}
											}
											$thisItem->update_time = time();
											/*
											 $os['items'][$k] = (Array)$v;
											$os['items'][$k]['order_id'] = $os['order_id'];
											$os['items'][$k]['create_time'] = time();
											$os['items'][$k]['update_time'] = time();
											*/
											if (! $thisItem->save(false)) {
												//SysLogHelper::SysLog_Create("order",__CLASS__, __FUNCTION__,"Error 5.1","Import order, preservation order goods failure,odOrderItemParams:".print_r($os['items'],true)."error:".$pro_res, "Error");
												echo "Import order goods failure,odOrderItemParams:".print_r($v,true)."error:".print_r($thisItem->getErrors(),true);
												$result['success'] = 1;
												$result['message'] = 'E008写入订单商品信息失败';
												return $result;
											}
											echo '\n orderid:'.$os['order_source_order_id'].' itemid:'.$v['order_source_itemid'].' update success';
										}//end of update each items
									}
									$result['success'] = 0;
									$result['message'] = 'E009同步成功';
									return $result;
									//kh20151219 合并订单后， 新的订单不导入  end
								}elseif ($os['order_id'] == 0) {
									$result['success'] = 1;
									$result['message'] = 'E005写入订单信息失败';
									return $result;
								}echo "start 282"."\n";
								
								if(!empty($os['order_id'])) {
										
									#########################ebay begin######################
									if ($os['order_source']=='ebay' && ($orderCapture =='N') && (1==0)){
										/*
									try {
										echo "start 287"."\n";
										//echo 'update order_id of ebay transactions';
									
										OdEbayTransaction::updateAll(['order_id'=>$os['order_id']],['platform'=>'eBay','orderid'=>$os['order_source_order_id']]);
									}catch (Exception $ex){
									//								SysLogHelper::SysLog_Create("order",__CLASS__, __FUNCTION__,"Error 5.1","Import order, preservation order goods failure,odOrderItemParams:".print_r($os['items'],true)."error:".$pro_res, "Error");
										$result['success'] = 1;
										$result['message'] = 'E006写入transction信息失败';
										return $result;
									} 
									
									try {
										$tmpTR =OrderBackgroundHelper::refreshEbayOrderItem($uid, $os['order_id'], $os['order_source_site_id'], $os['order_status']);
										if (isset($tmpTR['success']) && $tmpTR['success']==1){
											return $tmpTR;
										}
									}catch (\Exception $ex){
									//								SysLogHelper::SysLog_Create("order",__CLASS__, __FUNCTION__,"Error 5.1","Import order, preservation order goods failure,odOrderItemParams:".print_r($os['items'],true)."error:".$pro_res, "Error");
										$result['success'] = 1;
										$result['message'] = 'E006写入item信息失败'.$ex->getMessage()." line no:".$ex->getLine();
										return $result;
									} 
									*/
								}// end of ebay order logic 
								else
								{ // other platform logic 
									
								################################其他平台#######################################
								if(! empty($os['items']) && ! empty($os['order_id'])) {
								foreach($os['items'] as $k => $v) {
										$os['items'][$k] = (Array)$v;
										$os['items'][$k]['order_id'] = $os['order_id'];
										$os['items'][$k]['create_time'] = time();
										$os['items'][$k]['update_time'] = time();
										
										try {
											//假如是订单状态级别的订单 更新 订单在状态时 需要及时更新其商品状态， 否则待发货数量，与物流状态会不准
											if (isset(OrderUpdateHelper::$updateItemAttr[$os['order_source']]) == false){
												//没有 商品状态的订单需要在订单状态更新时， 更新商品状态
												if (isset($os['items'][$k]['platform_status']) == false){
													$os['items'][$k]['platform_status'] = $os['order_status'];
												}
												
												//FBA,FBC 不更新 item
												if (!in_array(strtoupper(@$os['order_type']), ['AFN' , 'FBC'])){
													$os['items'][$k]['delivery_status'] = OrderUpdateHelper::getOrderDeliveryStatus($os['order_source'], @$os['items'][$k]['platform_status']);
												}//end of validate order_type
												else{
													$os['items'][$k]['delivery_status'] = 'ban';//FBA, FBC 直接ban
												}
											} //end of
										} catch (\Exception $e) {
											
										}
										
										
								}
									
								//是否更新待发货数量逻辑
								$doPIp_updatePendingQty = false;
								if(isset($os['order_type'])){
									if (!in_array(strtoupper($os['order_type']), ['AFN' , 'FBC'])){
										$doPIp_updatePendingQty = true;
									}else{
										$doPIp_updatePendingQty = false;
									}
								}else{
									$doPIp_updatePendingQty = true;
								}
								
								if ($doPIp_updatePendingQty){
									if (in_array($os['order_status'], [OdOrder::STATUS_PAY])){
										$doPIp_updatePendingQty = true;
									}else{
										$doPIp_updatePendingQty = false;
									}
								}
								
								$pro_res = self::doProductImport($os['items'], $os['order_id'], $os['order_source'],$doPIp_updatePendingQty,$os['order_status'] , $os['selleruserid']);
								if (! $pro_res) {
									//SysLogHelper::SysLog_Create("order",__CLASS__, __FUNCTION__,"Error 5.1","Import order, preservation order goods failure,odOrderItemParams:".print_r($os['items'],true)."error:".$pro_res, "Error");
										$result['success'] = 1;
										$result['message'] = 'E008写入订单商品信息失败';
												return $result;
									}
									}//end of items 和 order_id
									}
							}
						} catch (\Exception $ex) {
							echo "\n".(__function__).' failure to insert, Error Message:' . $ex->getMessage () ." File:".$ex->getFile()." Line no ".$ex->getLine(). "\n";
							$result['success'] = 1;
							$result['message'] = 'E021  插入订单异常！file: '.$ex->getFile().'line no:'.$ex->getLine()." and message:".$ex->getMessage();
							//return $result;
						}
						
						
					}//end of update order insert 
					
					$logTimeMS5=TimeUtil::getCurrentTimestampMS();
					//保存物流信息，跟踪号
					//echo 'start save tracking number';
					//print_r($os['orderShipped']);
					echo "start 448"."\n";
					//对于旧的订单跳过 部分逻辑  ----  转商品，客服模块 buyer订单信息统计，订单自动检查，保存物流号 , 手工订单录入 不会录入跟踪号
					if ($isRecentOrder && ($orderCapture =='N')){
						if (!empty($os['order_id'])){
							self::saveTrackingNumber($os['order_id'],$os['orderShipped'],1,false);
						}	
					    
					}
					
					$logTimeMS6=TimeUtil::getCurrentTimestampMS();
					

					
				//	$odOrder = empty($odOrder) ?OdOrder::find()->where("`order_source` = :os AND `order_source_order_id` = :osoi",[':os'=>$os['order_source'],':osoi'=>$os['order_source_order_id']])->one(): ''; 
					$logTimeMS7=TimeUtil::getCurrentTimestampMS();
					
					//对于旧的订单跳过 部分逻辑  ----  转商品，客服模块 buyer订单信息统计，订单自动检查，保存物流号
				/*	if ($isRecentOrder){							
						if (! empty($odOrder->order_id)) {
							
							//echo 'start save customer info';
						 
	// 						$source_buyer_user_id = empty($os['source_buyer_user_id']) ? '' : $os['source_buyer_user_id'];
	// 						OdCustomerHelper::saveCustomer($source_buyer_user_id, $os['selleruserid'], $odOrder);//存入客户信息
                       	// 忽略用户的设置，订单拉取不做自动检查
							if (isset(\Yii::$app->params["fetch_order_need_autocheck"]) && \Yii::$app->params["fetch_order_need_autocheck"] == 1){
							//	$odOrder->checkorderstatus('System');
							//	$odOrder->save(false);
							}
						}
					}*/
					
					$logTimeMS8=TimeUtil::getCurrentTimestampMS();
					$orderSource=$os['order_source'];
					if ($isRecentOrder) $isRecentOrderInt=1; else $isRecentOrderInt=0;  
					
						
					
					echo "save end"."\n";
					$result['success'] = 0;
					$result['message'] = 'E009同步成功';
					
					try {
						// 增加系统标签 start
						//有付款备注 start
						if (!empty($os['user_message'])){
							$sysTagRt = OrderTagHelper::setOrderSysTag($os['order_id'], 'pay_memo');
								
							if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400'){
								echo '\n'.$os['order_id'].' insert pay_memo failure :'.$sysTagRt['message'];
							}
						}
							
						//有订单备注 start
						if (! empty($os['desc'])){
							$sysTagRt = OrderTagHelper::setOrderSysTag($os['order_id'], 'order_memo');
								
							if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400'){
								echo '\n'.$os['order_id'].' insert order_memo failure :'.$sysTagRt['message'];
							}
						}
						//增加系统标签 end
					} catch (\Exception $ex) {
						echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
						$result['success'] = 1;
						$result['message'] = 'E018  系统标签增加异常！ line no:'.$ex->getLine()." and message:".$ex->getMessage();
						//return $result;
					}
					
					
					$logTimeMS9=TimeUtil::getCurrentTimestampMS();
					
					//kh 2016-06-30 加入订单自动检测队列 start
					try {
						$isInsertCheckQueue = '';
						//目前  ebay， 速卖通  和  bonanza 三个平台 未付款的订单也会同步下来， 所以 更新变成 已付款 的订单再进行自动 检测队列的插入
						if (in_array($os['order_status'],[OdOrder::STATUS_PAY]) && in_array(strtolower($os['order_source']) , ['aliexpress','bonanza','ebay']) ){
							//已付款订单类型  为空或者 待检测才 插入队列 ， 已经检测过的则不再插入队列
							if ((!empty($odOrder->pay_order_type) && $odOrder->pay_order_type == OdOrder::PAY_PENDING) || empty($odOrder->pay_order_type)){
								//速卖通 风控中的订单不自动检测 ， bonanaza没有 这个状态所以 一定会插入
								if (@$os['order_source_status'] != 'RISK_CONTROL'){
									$rt = \eagle\modules\order\helpers\OrderBackgroundHelper::insertOrderAutoCheckQueue($uid, $os['order_id']);
									//重复插入情况也不算失败
									if (!empty($rt['code']) && $rt['code'] != 'E001'){
										echo " \n ".$rt['message'];
										$result['success'] = 1;
										$result['message'] = 'E012自动检测队列加入失败!'.$rt['message'];
										echo " \n insertOrderAutoCheckQueue error message:".$result['message'];
										$isInsertCheckQueue ="noInsertQueue5";
										return $result;
									}elseif($rt['success']){
										$isInsertCheckQueue = "insertCheckQueue_update";
									}else{
										$isInsertCheckQueue .= "insertCheckQueue_error messeage=".@$rt['message'];
									}
								}else{
									$isInsertCheckQueue ="noInsertQueue3";
								}
							}else{
								$isInsertCheckQueue ="noInsertQueue4";
							}
							$isInsertCheckQueue .= " pay_order_type=".@$odOrder->pay_order_type." order_source_status=".@$os['order_source_status'];
							
						}else{
							//其他平台 则是以$operationType insert ， 订单状态 为已付款的订单才插入自动检测队列
							if( in_array($os['order_status'],[OdOrder::STATUS_PAY]) && strtolower($operationType) =="insert"){
								//AMAZON 的fba（afn） 和 cdiscount 的 fbc 不作自动检测 其他平台 order_type 不为afn 与fbc
								if (!in_array(strtoupper($os['order_type']), ['AFN' , 'FBC'])){
									$rt = \eagle\modules\order\helpers\OrderBackgroundHelper::insertOrderAutoCheckQueue($uid, $os['order_id']);
									//重复插入情况也不算失败
									if (!empty($rt['code']) && $rt['code'] != 'E001'){
										echo " \n ".$rt['message'];
										$result['success'] = 1;
										$result['message'] = 'E012自动检测队列加入失败!'.$rt['message'];
										echo " \n insertOrderAutoCheckQueue error message:".$result['message'];
										$isInsertCheckQueue ="noInsertQueue7 ";
										return $result;
									}elseif($rt['success']){
										$isInsertCheckQueue = "insertCheckQueue_insert";
									}
								}else{
									$isInsertCheckQueue ="noInsertQueue1";
								}
							}else{
								// $operationType == update and 订单状态不是已付款的订单则不插入自动检测队列
								$isInsertCheckQueue ="noInsertQueue2";
							}
						}
					} catch (\Exception $ex) {
						$result['success'] = 1;
						$result['message'] = 'E013 自动检测队列加入失败！ line no:'.$ex->getLine()." and message:".$ex->getMessage();
						$isInsertCheckQueue ="noInsertQueue6 ".$result['message'];
						//return $result;
					}
					//kh 2016-06-30 加入订单自动检测队列 end
					
					$logTimeMS10=TimeUtil::getCurrentTimestampMS();
					//log 日志 ， 调试相关信息start
					//check $operationType
					$GlobalCacheLogMsg = "\n final ".@$os['order_source']."_importPlatformOrder_".@$operationType." ,puid=".@$uid." ,order_id=".@$os['order_id'].",order_type=".@$os['order_type'].",order_source_order_id=".@$os['order_source_order_id'].",odStatus=".@$os['order_status']." ,insertOrderHealthCheck=$isInsertCheckQueue  insertOrderAutoCheckQueue v".self::$ImportPlatformOrderVersion;
					echo $GlobalCacheLogMsg;
					\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
					//log 日志 ， 调试相关信息end
					
					try {
						
						$list_clear_platform = [];
						$list_clear_platform[] = $os['order_source'];
						//left menu 清除redis
						if (!empty($list_clear_platform) && !empty($uid) && in_array($os['order_source'],['ebay'])){
							OrderHelper::clearLeftMenuCache($list_clear_platform,$uid);
							echo "\n uid=$uid clean redis ".$os['order_source'];
							
						}
					} catch (\Exception $ex) {
						echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
						$result['success'] = 1;
						$result['message'] = 'E019 左侧菜单redis异常！ line no:'.$ex->getLine()." and message:".$ex->getMessage();
						//return $result;
					}
					$logTimeMS11=TimeUtil::getCurrentTimestampMS();
					
					//检查合并订单平台状态等相关是否需要重写
					try {
						//更新 订单的情况下， 假如更新合并订单的原始订单， 则需要检查合并出来 的订单是否需要更新相关状态
						if (!empty($operationType) && $operationType =='update' && $odOrder->order_relation =='fm' ){
							// 订单状态变化
							if ($oldOrderStatus != @$os['order_source_status']){
								// 订单平台状态刷新
								list($ShouldUpdateRetaion , $updateOrderList)  = OrderHelper::isUpdateMergedOrder($odOrder->order_id, ['order_source_status']);
								
								if ($ShouldUpdateRetaion ){
									//需要则update
									$effect = OdOrder::updateAll(['order_source_status'=>@$os['order_source_status']],['order_id'=>$updateOrderList]);
									echo "\n uid=$uid update order_source_status relation order orderid=".json_encode($updateOrderList)." order_source_status =".@$os['order_source_status']." and effect = ".$effect;
								}else{
									//不需要 update
										echo "\n $uid update order_source_status relation order ".print_r($updateOrderList,true);
								}
							}
							
							if (isset($os['sync_shipped_status'])){
										
								//订单虚拟发货状态刷新
								OrderBackgroundHelper::setRelationOrderSyncShippedStatus($order , $os['sync_shipped_status'] , $oldSyncShipStatus);
							}
							
							
							/*
							list($ShouldUpdateRetaion , $updateOrderList)  = OrderHelper::isUpdateMergedOrder($odOrder->order_id, ['sync_shipped_status']);
								
							if ($ShouldUpdateRetaion ){
								//需要则update
								$effect = OdOrder::updateAll(['sync_shipped_status'=>$os['sync_shipped_status']],['order_id'=>$updateOrderList]);
								echo "\n uid=$uid update sync_shipped_status relation order orderid=".json_encode($updateOrderList)." order_source_status =".$os['order_source_status']." and effect = ".$effect;
							}else{
								//不需要 update
									echo "\n $uid update sync_shipped_status relation order ".print_r($updateOrderList,true);
							}
							*/
						}
						
					} catch (\Exception $ex) {
						echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
						$result['success'] = 1;
						$result['message'] = 'E020  更新合并订单异常！ line no:'.$ex->getLine()." and message:".$ex->getMessage();
						//return $result;
						
					}
					$logTimeMS12=TimeUtil::getCurrentTimestampMS();
					
					\Yii::info("version=".self::$ImportPlatformOrderVersion." importPlatformOrder_$operationType odSrc=$orderSource, isRecentOd=$isRecentOrderInt,odStatus=".$os['order_status'].",oct=$orderCreateTimeStr,t2_1=".(@$logTimeMS2-@$logTimeMS1).
							",t2_2A=".(@$logTimeMS2A-@$logTimeMS2).",t2A_2B=".(@$logTimeMS2B-@$logTimeMS2A).
							",t2C_2B=".(@$logTimeMS2C-@$logTimeMS2B).",t2D_2C=".(@$logTimeMS2D-@$logTimeMS2C).
							",t3_2D=".(@$logTimeMS3-@$logTimeMS2D).
							",t3_2=".(@$logTimeMS3-@$logTimeMS2).",t4_3=".(@$logTimeMS4-@$logTimeMS3).
							",t5_4=".(@$logTimeMS5-@$logTimeMS4).",t6_5=".(@$logTimeMS6-@$logTimeMS5).
							",t7_6=".(@$logTimeMS7-@$logTimeMS6).",t8_7=".(@$logTimeMS8-@$logTimeMS7).
							",t8_1=".(@$logTimeMS8-@$logTimeMS1)." t9_8=".(@$logTimeMS9-@$logTimeMS8).
							",t10_9=".(@$logTimeMS10-@$logTimeMS9).",t11_10=".(@$logTimeMS11-@$logTimeMS10).
							",t12_11=".(@$logTimeMS12-@$logTimeMS11).",t12_1=".(@$logTimeMS12-@$logTimeMS1).
							",puid=$uid","file");
					
					return $result;
				} else {
					 
					$result['success'] = 1;
					$result['message'] = 'E010订单状态不在操作范围内';
					return $result;
				}
			}//end of each sync orders 
		} else {
			
			$result['success'] = 1;
			$result['message'] = 'E011参数缺失';
			return $result;
		}
	}
	/**
	 +--------------------------------------------------------------------------------
	 * 插入一条订单数据
	 +--------------------------------------------------------------------------------
	 * @access static
	 +--------------------------------------------------------------------------------
	 * @param params		插入的数据（包括定位记录所需的order_id）
	 * 
	 +--------------------------------------------------------------------------------
	 * @return	
	 * 				success							调用是否成功
	 * 				message							保存失败的相关信息
	 * 			 	order_id						保存后的 id
	 +--------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 * @editer		lkh		2015/12/31				改变了返回结构，保存失败会返回错误信息
	 +--------------------------------------------------------------------------------
	**/
	public static function doInsert($params,$eagleOrderId=-1) {
		try{
			
			$logTimeMS1=TimeUtil::getCurrentTimestampMS();
			
			$odOrder = new OdOrder();
			$id = 0;
			$attrs = $odOrder->attributes();
			unset($params['order_id']);
			foreach($params as $k => $v) {
				if(in_array($k,$attrs)) {
					$odOrder->$k = $v;
				}
			}
			$odOrder->create_time = time();
			$odOrder->update_time = $odOrder->create_time;
			
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			
			//匹配仓库 默认进来的新订单仓库id为0
			$odOrder->default_warehouse_id=0;
			//oms2.1 已付款 的需要设置为待检测 同时 pay_order_type没有给值
			if ($odOrder->order_status == 200 && empty($odOrder->pay_order_type)){
				$odOrder->pay_order_type = OdOrder::PAY_PENDING;
			}
			/* if (empty($odOrder->consignee_country_code)){
				$odOrder->default_warehouse_id=0;
			}else{
				$warehouse = InventoryApiHelper::getMatchWarehouseByCountries($odOrder->consignee_country_code);
				if (isset($warehouse[0])){
					$odOrder->default_warehouse_id=$warehouse[0];
				}else{
					$odOrder->default_warehouse_id = 0;
				}
				
			} */
			
			$logTimeMS3=TimeUtil::getCurrentTimestampMS();
			
			if ($eagleOrderId>-1) $odOrder->order_id=$eagleOrderId; //lolo20150601 指定orderId进行插入
	 		if($odOrder->save(false)){
	 			$logTimeMS3A=TimeUtil::getCurrentTimestampMS();
	 			
				$id = $odOrder->order_id;
				OperationLogHelper::log('order', (int)$odOrder->order_id, '创建订单', '客户成功从订单模块新建订单','System');
				$result = ['success'=>true , 'message'=>'' , 'order_id'=>$id];
				
				$logTimeMS3B=TimeUtil::getCurrentTimestampMS();
				
				//通知dashboard计数器需要知道
				$puid=\Yii::$app->subdb->getCurrentPuid();
				//DashBoardStatisticHelper::OMSStatusAdd($puid,$odOrder->order_source, $odOrder->order_status,1,"create new order");
				DashBoardStatisticHelper::OMSStatusAdd2($puid, $odOrder->order_source, $odOrder->selleruserid, $odOrder->order_status,1,"create new order");
				$logTimeMS3C=TimeUtil::getCurrentTimestampMS();
				DashBoardHelper::checkOrderStatusRedisCompareWithDbCount($puid, $odOrder->order_source, $odOrder->order_status, $odOrder->order_status, "create new order");
				$logTimeMS3D=TimeUtil::getCurrentTimestampMS();
				//通知 dashboard IT 这个平台加了一个新单
				DashBoardHelper::AddJobDataCount(strtoupper($odOrder->order_source)."_ORDER_FETCH");
				$logTimeMS3E=TimeUtil::getCurrentTimestampMS();
				//如果是合并出来的新订单，则不算销量统计
				if ($odOrder->order_relation <> 'sm' && ((int)$odOrder->order_status >= 200 && (int)$odOrder->order_status < 600) )
					DashBoardStatisticHelper::SalesStatisticAdd($odOrder->order_source_create_time, $odOrder->order_source, $odOrder->order_type, $odOrder->selleruserid, $odOrder->currency, $odOrder->grand_total, 1, 0, false, $odOrder->order_status);
				$logTimeMS3F=TimeUtil::getCurrentTimestampMS();
			}else {
				$comment = "保存OdOrder:$odOrder->order_id 失败".json_encode($odOrder->getErrors());
				\Yii::info('OrderHelper_doInsert Background insert db error: '.$comment ,"file");
				$logTimeMS3A=TimeUtil::getCurrentTimestampMS();
				$logTimeMS3B=$logTimeMS3A;
				$logTimeMS3C=$logTimeMS3A;
				$logTimeMS3D=$logTimeMS3A;
				$logTimeMS3E=$logTimeMS3A;
				$logTimeMS3F=$logTimeMS3A;
				$result = ['success'=>false , 'message'=>$comment , 'order_id'=>0];
			}
			
			$logTimeMS4=TimeUtil::getCurrentTimestampMS();
			
		} catch(\Exception $e) {
			\Yii::info("OrderHelper_doInsert Background Exception error1: ".json_encode($e->getMessage()).",订单data:".json_encode($params)." line no:".$e->getLine(),"file");
			$result = ['success'=>false , 'message'=>json_encode($e->getMessage()) , 'order_id'=>0];
		}
		
		try {
			$puid = \Yii::$app->subdb->getCurrentPuid();
			RedisHelper::delOrderCache( $puid , strtolower($odOrder->order_source) );
			$logTimeMS5=TimeUtil::getCurrentTimestampMS();
			//耗时超过500毫秒 记录下来
			if (($logTimeMS5-$logTimeMS1) > 500){
				\Yii::info("version=".self::$ImportPlatformOrderVersion." ,OrderHelper_doInsert order_id=".$odOrder->order_id." ,t2_1=".(@$logTimeMS2-@$logTimeMS1).
						",t3_2=".(@$logTimeMS3-@$logTimeMS2).
						",t3A_3=".(@$logTimeMS3A-@$logTimeMS3).",t3B_3A=".(@$logTimeMS3B-@$logTimeMS3A).
						",t3C_3B=".(@$logTimeMS3C-@$logTimeMS3B).",t3D_3C=".(@$logTimeMS3D-@$logTimeMS3C).
						",t3E_2D=".(@$logTimeMS3E-@$logTimeMS3D).",t3F_3E=".(@$logTimeMS3F-@$logTimeMS3E).
						",t4_3=".(@$logTimeMS4-@$logTimeMS3).
						",t5_4=".(@$logTimeMS5-@$logTimeMS4).",t5_1=".(@$logTimeMS5-@$logTimeMS1).
						",puid=$puid","file");
			}
		} catch (\Exception $e) {
			\Yii::info("OrderHelper_doInsert Background Exception error2: ".json_encode($e->getMessage()).",订单data:".json_encode($params)." line no:".$e->getLine(),"file");
		}
		
		 
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 更新多条订单数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $attributes		更新的数据（包括定位记录所需的order_id）
	**/
	public static function doUpdate($attributes,$orderStatus) {
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$order_id = $attributes['order_id'];
		unset($attributes['order_id']);
		/****  可以修改字段的中文解释 ， 写日志时用上，假如有新增修改字段请在补上中文解释  ****/
		$label_cn = [
			'order_status'=>'订单流程状态',//订单流程状态
			'pay_status'=>'付款状态',//付款状态
			'shipping_status'=>'平台发货状态',//平台发货状态
			'order_source_create_time'=>'订单在来源平台的下单时间',//订单在来源平台的下单时间
			'paid_time'=>'订单付款时间',//订单付款时间
			'delivery_time'=>'订单发货时间',//订单发货时间
			'user_message'=>'用户留言',//用户留言
			'seller_commenttype'=>'卖家评价类型',//卖家评价类型
			'seller_commenttext'=>'卖家评价留言',//卖家评价留言
			'order_source_status'=>'订单来源平台订单状态',//订单来源平台订单状态
			'consignee'=>'收货人',
			'consignee_city'=>'收货人城市',
			'consignee_province'=>'收货人省',
			'consignee_country_code'=>'收货人国家代码',
			'consignee_country'=>'收货人国家名',
			'consignee_postal_code'=>'收货人邮编',
			'consignee_phone'=>'收货人电话',
			'consignee_email'=>'收货人Email',
			'consignee_address_line1'=>'收货人地址1',
			'consignee_address_line2'=>'收货人地址2',
			'consignee_address_line3'=>'收货人地址3',
			'consignee_company'=>'收货人公司',
			'consignee_district'=>'收货人区',
			'consignee_county'=>'收货人镇',
			'consignee_mobile'=>'收货人手机',
			'order_source_shipping_method'=>'平台下单时用户选择的运输服务',
			'source_buyer_user_id'=>'平台买家账号',
			'subtotal'=>'产品总价格',
			'shipping_cost'=>'运费',
			'discount_amount'=>'折扣',
			'commission_total'=>'订单平台佣金',
			'grand_total'=>'合计金额',
			'returned_total'=>'退款总金额',
			'last_modify_time'=>'平台最后修改时间',
			'sync_shipped_status'=>'虚拟发货同步状态',
			'paypal_fee'=>'PP费',
			'items_md5'=>'商品信息快照',
			'first_sku'=>'订单首个sku',
			'ismultipleProduct'=>'多品标志',
			'transaction_key'=>'汇款号码',
		];
		
		//可以修改的字段，如果订单做过修改，那么同步的时候会被覆盖，如地址等
		$attrs = 
		[
		'order_status',//'订单流程状态:100:未付款,200:已付款,400:待发货,500:已发货,600:已取消',
		'pay_status',//付款状态
		'shipping_status',//平台发货状态
		'order_source_create_time',//订单在来源平台的下单时间
		'paid_time',//订单付款时间
		'delivery_time',//订单发货时间
		'user_message',//用户留言
		'seller_commenttype',//卖家评价类型
		'seller_commenttext',//卖家评价留言
		'order_source_status',//订单来源平台订单状态
		'last_modify_time',
		'sync_shipped_status', // 虚拟发货同步状态
		'items_md5',
		'source_buyer_user_id',//'平台买家账号'
		'transaction_key',//汇款号码
		];
		
		// dzt20160729 for 订单合并项检测
		$mergeAttrs = [
		'selleruserid' , 
		'consignee' , 
		'consignee_address_line1' , 
		'consignee_address_line2' , 
		'consignee_address_line3' ,
		'order_status' ,// 合并订单允许小老板状态更新，这里加入这个字段主要是为了判断原始订单若出现取消订单等状态，则拆分订单
		'source_buyer_user_id'
		];
		
		
		$odorder = OdOrder::findOne($order_id);
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		//ebay特殊处理
		if (strlen($odorder->consignee)==0&&strlen($odorder->consignee_country)==0
				&&strlen($odorder->consignee_city)==0&&strlen($odorder->consignee_address_line1)==0 && $odorder->order_source =='ebay' ){
			$attrs =
			[
				'order_status',//'订单流程状态:100:未付款,200:已付款,400:待发货,500:已发货,600:已取消',
				'pay_status',//付款状态
				'shipping_status',//平台发货状态
				'order_source_create_time',//订单在来源平台的下单时间
				'paid_time',//订单付款时间
				'delivery_time',//订单发货时间
				'user_message',//用户留言
				'seller_commenttype',//卖家评价类型
				'seller_commenttext',//卖家评价留言
				'order_source_status',//订单来源平台订单状态
				'consignee',
				'consignee_city',
				'consignee_province',
				'consignee_country_code',
				'consignee_country',
				'consignee_postal_code',
				'consignee_phone',
				'consignee_email',
				'consignee_address_line1',
				'consignee_address_line2',
				'consignee_company',
				'sync_shipped_status', // 虚拟发货同步状态
			];
		}
		
		
		if ($odorder->order_source =='ebay'  && in_array('subtotal', $attrs) ==false   ){
			//20161122kh 由于客户发现ebay金额不一样，但未能定位是api还是bug导致， 所以先允许更新ebay的金额
			$tmpEbayAttr = [
			'subtotal',
			'shipping_cost',
			'discount_amount',
			'commission_total',
			'grand_total',
			'returned_total',
			'paypal_fee',
			];
			$attrs = array_merge($attrs , $tmpEbayAttr);
		}
		//cd订单特殊处理
		if ((empty($odorder->consignee) || empty($odorder->consignee_country) || empty($odorder->consignee_city) || 
				empty($odorder->consignee_address_line1) || empty($odorder->consignee_country_code) || 
				empty($odorder->consignee_phone) || empty($odorder->consignee_company) || empty($odorder->consignee_district) || 
				empty($odorder->consignee_county) || empty($odorder->consignee_mobile)) &&
				$odorder->order_source =='cdiscount' ){
			$attrs =
			[
			'order_status',//'订单流程状态:100:未付款,200:已付款,400:待发货,500:已发货,600:已取消',
			'pay_status',//付款状态
			'shipping_status',//平台发货状态
			'order_source_create_time',//订单在来源平台的下单时间
			'paid_time',//订单付款时间
			'delivery_time',//订单发货时间
			'user_message',//用户留言
			'seller_commenttype',//卖家评价类型
			'seller_commenttext',//卖家评价留言
			'order_source_status',//订单来源平台订单状态
			'order_source_shipping_method',
			'last_modify_time',
			'sync_shipped_status', // 虚拟发货同步状态
			//'subtotal',
			//'shipping_cost',
			//'discount_amount',
			//'commission_total',
			//'grand_total',
			//'returned_total',
			];
			if(empty($odorder->origin_shipment_detail)){
				$attrs += [
				'consignee',
				'consignee_city',
				'consignee_province',
				'consignee_country_code',
				'consignee_country',
				'consignee_postal_code',
				'consignee_phone',
				'consignee_email',
				'consignee_address_line1',
				'consignee_address_line2',
				'consignee_company',
				'consignee_district',
				'consignee_county',
				'consignee_mobile',
				];
			}
		}
		
		// dzt20190826 客户反映从8月20号之后的所有金额方面的数字都不准，发现wish有返回货币字段，而我们默认设置了USD
		// 所以对原来同步回来不一样的currency进行覆盖
		if($odorder->order_source =='wish' && $odorder->currency != $attributes['currency']){
		    $attrs = array_merge($attrs, ['currency']);
		}
		
		if($odorder->order_source =='priceminister'){
			$attrs =
			[
				'order_status',//'订单流程状态:100:未付款,200:已付款,400:待发货,500:已发货,600:已取消',
				'order_source_status',
				'pay_status',//付款状态
				'shipping_status',//平台发货状态
				'order_source_create_time',//订单在来源平台的下单时间
				'paid_time',//订单付款时间
				'delivery_time',//订单发货时间
				'user_message',//用户留言
				'seller_commenttype',//卖家评价类型
				'seller_commenttext',//卖家评价留言
				'order_source_shipping_method',
				'sync_shipped_status', // 虚拟发货同步状态
				//'subtotal',
				//'shipping_cost',
				//'discount_amount',
				//'commission_total',
				//'grand_total',
				//'returned_total',
			];
			if(empty($odorder->origin_shipment_detail)){
				$attrs += [
				'consignee',
				'consignee_city',
				'consignee_province',
				'consignee_country_code',
				'consignee_country',
				'consignee_postal_code',
				'consignee_phone',
				'consignee_email',
				'consignee_address_line1',
				'consignee_address_line2',
				'consignee_company',
				'consignee_district',
				'consignee_county',
				'consignee_mobile',
				];
			}
		}
		
		$newOrderStatus = $attributes['order_status']; // dzt20160824 for 合并订单判读是否要拆分订单用
		
		$originAttrs = $attrs;// dzt20160826 非合并订单的合并订单字段不再强制更新，但为了避免影响本来就要更新这些字段，所以要保留一个。
		$attrs = array_merge($attrs , $mergeAttrs);
		foreach($attributes as $k => $v) {
			if(!in_array($k,$attrs)) {
				unset($attributes[$k]);
			}
			//系统同步时订单状态不能逆向修改数据，待发货状态不能修改
			if ($k == 'order_status'){
				if ($attributes['order_status']<$orderStatus || $orderStatus == 300 ){
					unset($attributes[$k]);
				}else if ($orderStatus == OdOrder::STATUS_PAY && OrderApiHelper::isEagleUnshipOrder($order_id)){
					//在系统中已付款 +存在系统虚假发货标签的订单不同步 订单状态 
					unset($attributes[$k]);
				}
			}
		}
		
		if($odorder->order_source !=='priceminister')
			unset($attributes['order_source_create_time'],$attributes['create_time']);
		
		$logTimeMS3=TimeUtil::getCurrentTimestampMS();
		
		
		//kh20160511 所有需要写日志的$attributes 必须写在这里上面
		//kh20160511 把最终的attributes 与model 比较， 假如发生修改则写log
		$updateLog = "";
		
		//如果平台状态原来是风控中或是等待买家付款的，现在变成 已完成的话，需要 改为小老板最终状态为已取消
		if ($odorder->order_source =='aliexpress' and 
			($odorder->order_source_status =='RISK_CONTROL' || $odorder->order_source_status =='PLACE_ORDER_SUCCESS') and 
			$attributes['order_source_status']=='FINISH'){
			$attributes['order_status'] = 600; //not 500 - 已完成
		}
		
		//如果平台状态原来是买家申请取消的和小老板状态为已取消，现在变成等待您发货的话，需要 改为小老板最终状态为已付款
		if ($odorder->order_source =='aliexpress' and
		$odorder->order_source_status =='IN_CANCEL' and
		$odorder->order_status== OdOrder::STATUS_CANCEL and
		$attributes['order_source_status']=='WAIT_SELLER_SEND_GOODS'){
			$attributes['order_status'] = OdOrder::STATUS_PAY; //
		}
		
		foreach($attributes as $k => $v) {
			if ($odorder->$k != $v){
			    // 合并订单合并项出现改变，自动拆分 
			    // 20170413 应要求，大小写不一致不认为是改变
				if('fm' == $odorder->order_relation && in_array($k, $mergeAttrs) && strtolower($odorder->$k) != strtolower($v)){
					if($k != 'order_status' || $newOrderStatus == 600){// 同步传入 order_status 为 600 已取消，则拆分
						/*20170209 start 增加了订单商品状态后不需要拆分订单    
						$orderRel = OrderRelation::findOne(['father_orderid'=>$odorder->order_id]);
						try {
							$thisFieldName =(!empty($label_cn[$k]))?  $label_cn[$k]:$k;
							$rollbackMsg = " 订单拆分原因：".$thisFieldName." ".$odorder->$k."改为".$v;
							self::RollbackmergeOrder([$orderRel->son_orderid],$rollbackMsg);
						} catch (\Exception $e) {
							\yii::error((__FUNCTION__).' error message : '.$e->getMessage()." Line no:".$e->getLine().' failure to rollback order puid='.$puid.' order_source_order_id='.$odorder->order_source_order_id ." order_id=".$odorder->order_id, 'file');
						}
						20170413 end 增加了订单商品状态后不需要拆分订单*/
						
					}
				}
				
				if(in_array($k, $mergeAttrs) && !in_array($k, $originAttrs)){// dzt20160826 合并订单字段不再强制更新，之前为了检查订单应否拆分导致全部都更新了这些字段
					unset($attributes[$k]);
					continue;
				}
				
				if (!empty($updateLog)) $updateLog .=",";
				$thisFieldName =(!empty($label_cn[$k]))?  $label_cn[$k]:$k;
				if ($k == 'order_status'){
					$updateLog .= $thisFieldName."由[".OdOrder::$status[$odorder->$k]."]同步为[".OdOrder::$status[$v]."]";
					try {
						$puid = \Yii::$app->subdb->getCurrentPuid();
						//告知dashboard面板，统计数量改变了
						OrderApiHelper::adjustStatusCount($odorder->order_source, $odorder->selleruserid, $v, $odorder->order_status,$order_id);
						RedisHelper::delOrderCache( \Yii::$app->subdb->getCurrentPuid() , strtolower($odorder->order_source) );
					} catch (\Exception $e) {
						\yii::error((__FUNCTION__).' error message : '.$e->getMessage()." Line no:".$e->getLine().' failure to change redis puid='.$puid.' order_source_order_id='.$odorder->order_source_order_id ." order_id=".$odorder->order_id, 'file');
					}
					
				}else
				if (in_array($k , ['order_source_create_time' , 'paid_time','delivery_time'])){
					$updateLog .= $thisFieldName."由[".date("Y-m-d H:i:s",$odorder->$k)."]同步为[".date("Y-m-d H:i:s",$v)."]";
				}else{
					$updateLog .= $thisFieldName."由[".$odorder->$k."]同步为[".$v."]";
				}
			}
		}
		
		if(isset($attributes['order_status'])){
			try {
				$puid = \Yii::$app->subdb->getCurrentPuid();
				//如果update属性中有order_status且从未付款更新为已付款后的状态，则调用DashBoard统计+
				if ($odorder->order_relation <> 'sm' && (int)$odorder->order_status<200 && ((int)$attributes['order_status'] >= 200 && (int)$attributes['order_status'] < 600) )
					DashBoardStatisticHelper::SalesStatisticAdd($odorder->order_source_create_time, $odorder->order_source, $odorder->order_type, $odorder->selleruserid, $odorder->currency, $odorder->grand_total, 1, 0, false, $odorder->order_status);
				//如果update属性中有order_status且从已付款后的状态更新为取消，则调用DashBoard统计-
				if ($odorder->order_relation <> 'sm' && (int)$odorder->order_status>=200 && (int)$odorder->order_status<600 && (int)$attributes['order_status']>=600 )
					DashBoardStatisticHelper::SalesStatisticAdd($odorder->order_source_create_time, $odorder->order_source, $odorder->order_type, $odorder->selleruserid, $odorder->currency, 0-$odorder->grand_total, 1, 0, false, $odorder->order_status );
				
				RedisHelper::delOrderCache( $puid , strtolower($odorder->order_source) );
			} catch (\Exception $e) {
				\yii::error((__FUNCTION__).' error message : '.$e->getMessage()." Line no:".$e->getLine().' failure to change redis puid='.$puid.' order_source_order_id='.$odorder->order_source_order_id ." order_id=".$odorder->order_id, 'file');
			}
			
		}
		
		if (!empty($updateLog)){
			OperationLogHelper::log('order',$order_id,'同步订单','修改订单信息:'.$updateLog, 'System');
		}else{
			//订单数据没有改变， 不调用更新代码
			return "noChange";
			//\Yii::error("OrderHelper_doUpdate order_id=$order_id and order_source_order_id =".$odorder->order_source_order_id." no change no log： data:".json_encode($attributes),"file");
		}
		//kh20160511 所有不需要写日志的$attributes 必须写在这里下面
		$attributes['update_time'] = time(); // update time 不需要写系统日志 
		$effect = intval(OdOrder::updateAll($attributes, "order_id IN ($order_id) "));
		if (empty($effect)){
			\Yii::error("OrderHelper_doUpdate order_id=$order_id and order_source_order_id =".$odorder->order_source_order_id."  update record($effect) log：".$updateLog."data:".json_encode($attributes),"file");
		}
		//\Yii::info("OrderHelper_doUpdate order_id=$order_id and order_source_order_id =".$odorder->order_source_order_id."  update record($effect) log：".$updateLog."data:".json_encode($attributes),"file");
		return $effect;
	}

	/**
	 +----------------------------------------------------------
	 * 平台导入订单商品数据（多行）
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param list				商品信息数组
	 * @param orderId			订单id
	 * @param order_source		订单平台 
	 * @param updatePendingQty	是否更新待发货数量
	 +----------------------------------------------------------
	 * @return				平台导入影响行数
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		qill    2014/12/03				初始化
	 * @editer		lkh		2016-12-15				增加了 是否更新待发货数量的逻辑
	 +----------------------------------------------------------
	 **/
	public static function doProductImport($list, $orderId, $order_source='' , $updatePendingQty=true, $currentOrderSourceStatus='' ,$selleruserid='' ) {
		//print_r($list);//liang test
		$count = 0;
		$time = time();
		foreach($list as $k => $v) {
			$odOrderItem = new OdOrderItem();
			$modelAttr = $odOrderItem->attributes();
			foreach($v as $sk => $sv) {
				//过滤参数
				if(in_array($sk, $modelAttr)) {
					// 目前只对速卖通空sku 作处理
					if ($sk =='sku' ){
						if (!empty($sv)){
							$odOrderItem->$sk = $sv;
						}else{
							$suffix = '';
							try {
								//目前速卖通与ebay 使用product attribute 来生成 sku 其他平台 都默认使用 order_source_itemid
								if (in_array($order_source , ['aliexpress' , 'ebay'])){
									if (isset($v['product_attributes'])){
										$itemAttrList = [];
										$suffix = '';
										$tmpStep = 'a0';
										if ( stripos('1'.$order_source,'aliexpress' )){
											$currentAttr =  explode('+' ,$v['product_attributes'] );
											//以attribute label 的升序排列
											ksort($currentAttr);
											$tmpStep = 'a0-1';
											foreach($currentAttr as $tmp){
												$itemOneAttr = explode(':' ,$tmp);
												if (isset($itemOneAttr[1])){
													$suffix .= @$itemOneAttr[1];
													$tmpStep = 'a1';
												}else{
													$tmpStep = 'a2';
												}
											}
										}elseif ( stripos('1'.$order_source,'ebay' )){
											$currentAttr = json_decode($v['product_attributes'],true);
											//以attribute label 的升序排列
											ksort($currentAttr);
											$tmpStep = 'a0-2';
											foreach($currentAttr as $tmp){
												if (is_string($tmp)){
													$suffix .= @$tmp;
													$tmpStep = 'a4';
												}elseif (is_array($tmp)){
													foreach($tmp as $_subTmp){
														$suffix .= @$_subTmp;
														$tmpStep = 'a5';
													}
												}
													
											}
										}
									}else{
										$suffix = '';
										$tmpStep = 'a3';
									}
								}
								
								if (in_array($order_source,['lazada','linio','jumia','priceminister'])){
									$odOrderItem->sku = $v['order_source_order_item_id'].$suffix;
								}else{
									$odOrderItem->sku = $v['order_source_itemid'].$suffix;
								}
								
								$odOrderItem->is_sys_create_sku = 'Y';
								$msg = (__FUNCTION__)." v1.3 platform=".$order_source.'  empty sku - syscreateSKU='.$odOrderItem->sku.'  suffix='.$suffix.' and step='.$tmpStep.' : item data='.json_encode(@$v['product_attributes']);
								//\yii::info($msg, 'file');
							} catch (\Exception $e) {
								\yii::error((__FUNCTION__).' error message : '.$e->getMessage()." Line no:".$e->getLine().' sku empty ', 'file');
								
							}
							
						}
					}else{
						$odOrderItem->$sk = $sv;
						//ebay特殊参数处理,product_attributes过长的话，就保存到add_info
						if($sk == 'product_attributes' && !empty($sv) && $order_source == 'ebay' && strlen($sv) > 100 ){
						    $odOrderItem->addi_info = json_encode(['product_attributes' => $sv]);
						}
					}
					
				}
				//特殊参数处理
				else if($sk == 'sendGoodsOperator' && $order_source == 'aliexpress' && !empty($sv)){
					$odOrderItem->addi_info = json_encode(['sendGoodsOperator' => $sv]);
				}
			}
			
			$odOrderItem->delivery_status = OrderUpdateHelper::getOrderDeliveryStatus($order_source, $odOrderItem->platform_status ,$currentOrderSourceStatus,'enable');
			
			/* 20170220kh 否则用户不看清楚状态保存会设置为禁用   日后再优化
			 * //假如 是 ban的状态， 手工状态也设置 为禁用
			if ($odOrderItem->delivery_status == 'ban'){
				$odOrderItem->manual_status = 'disable';
			}
			*/
			
			if($odOrderItem->save(false)){
				$count += 1;
				try {
					
					//新订单自动配对
					list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($odOrderItem, false , $order_source , $selleruserid));
					//新订单保存root sku
					if (!empty($rootSKU)){
						$odOrderItem->root_sku = $rootSKU;
						$odOrderItem->save(false);
					}
					//有root sku 的情况 下才更新 待发货数量
					if ($updatePendingQty && $odOrderItem->delivery_status == 'allow' && !empty($rootSKU)){
						$errorMsg = '';
						//新订单  默认仓库为0 原始数量为0 ，新数量为订单的数量
						list($ack , $code , $message  )  = array_values(OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, 0, 0, 0, $odOrderItem->quantity));
						//echo "$rootSKU , $ack , $code , $message ";
						if (empty($ack)) $errorMsg .= " order_source_order_id=".$odOrderItem->order_source_order_id." Error Message:".$message ;
						if (!empty($errorMsg)) echo $errorMsg;
					}
					
				} catch (\Exception $e) {
					\yii::error('error message : '.$e->getMessage()." Line no:".$e->getLine().' and item data: '.json_encode($odOrderItem->attributes), 'file');
				}
				
			}else{
				\yii::info('error message : '.json_encode($odOrderItem->errors).' and item data: '.json_encode($odOrderItem->attributes), 'file');
				//print_r($odOrderItem);
			}
		}
		return intval($count);
	}
	
	/**
	 * OdOrder导入物流单号
	 * @author fanjs
	 */
	public static function importtracknumfromexcel($file , $platform="", $autoship='',$autoComplete='')
	{
		$EXCEL_COLUMN_MAPPING = [
    		"A" => "orderid",//小老板订单号
    		"B" => "tracknum",//物流号
    		"C" => "server",//物流服务名
    		"D" => "tracklink",//追踪网址
    		"E"=>"platformorderid",//平台订单号
		];
		
		$data = ExcelHelper::excelToArray($file,$EXCEL_COLUMN_MAPPING,true);
		if (count($data))
		{
			$message = '';
			$orderinfo = '';
			$CompleteOrderIdList = [];
			foreach ($data as $d)
			{
				if (!empty($d['platformorderid']))
				{
					$order = OdOrder::find()->where(['order_source_order_id'=>$d['platformorderid']])->one();
					$orderinfo = '平台订单号 '.$d['platformorderid'];
				}
				else
				{
					$order = OdOrder::findOne($d['orderid']);
					$orderinfo = '小老板订单号 '.$d['orderid'];
				}
				
				if (empty($order)||$order->isNewRecord)
				{
					$message.=$orderinfo.' 未找到<br>';
					continue;
				}
				else if ($order->carrier_step == OdOrder::CARRIER_FINISHED)
				{
					$message.=$orderinfo.' 已完成<br>';
					continue;
				}
				
				//当是合并前原始订单，则找到对应合并订单，再处理
				if($order->order_relation == 'fm'){
				    $father_orderids = OrderRelation::find()->where(['father_orderid'=>$order->order_id , 'type'=>'merge' ])->one();
				    if(!empty($father_orderids)){
				        $forder = OdOrder::find()->where(['order_id'=>$father_orderids->son_orderid])->one();
				        if(!empty($forder)){
				            $order = $forder;
				            $d['orderid'] = $order->order_id;
				        }
				    }
				}
				
				$customerNumber = \eagle\modules\carrier\helpers\CarrierAPIHelper::getCustomerNum2($order);
				
				//需要标记发货
				if (!empty($autoship)){
					$tmpStatus = 0;
					$isShip = true;
					$suffix = '标记发货';
					
				}else{
					$tmpStatus = 0;
					$isShip = false;
					$suffix = '';
				}
				
				$logisticInfoList=[
					'0'=>[
						'order_source'=>$order->order_source,
						'selleruserid'=>$order->selleruserid,
						'tracking_number'=>$d['tracknum'],
						'tracking_link'=>$d['tracklink'],
						'shipping_method_code'=>$d['server'],
						'shipping_method_name'=>$d['server'],//平台物流服务名
						'customer_number'=>$customerNumber,
						'shipping_service_id'=>$order->default_shipping_method_code,
						'order_source_order_id'=>$order->order_source_order_id,
						'addtype'=>'Excel导入'.$suffix,
					]
				];
				
				
				
				//保存物流信息、物流号等
				if(!OrderHelper::saveTrackingNumber($order->order_id, $logisticInfoList,$tmpStatus,$isShip))
				{
					$message.='订单'.$d['orderid'].'插入失败<br>';
				}
				else
				{
					if (!empty($autoComplete)){
						$CompleteOrderIdList[] = $order->order_id;
					}
    				$order->carrier_error = '';
    				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
    				$order->customer_number =$customerNumber;
    				$order->save();
    				OperationLogHelper::log('delivery', $order->order_id,'导入跟踪号','跟踪号：'.$d['tracknum'],\Yii::$app->user->identity->getFullName());
    				
    				
    				
    			}
			}
			if (!empty($CompleteOrderIdList)){
				OrderApiHelper::completeOrder($CompleteOrderIdList);
			}
			
			//输出错误
			if (strlen($message)>5)
				return json_encode(['ack'=>'failure','message'=>$message]);
			else
			    return json_encode(['ack'=>'success']);
		}
		else
		{
			return json_encode(['ack'=>'failure','message'=>'导入的excel没有订单']);
		}
	}
	
	/**
	 * 保存物流信息，物流号等
	 * million 2015-03-21
	 * $orderId 小老板订单号
	 * $logisticInfoList = array(0=>array(
									'order_source'=>'ebay',//订单来源
									'selleruserid'=>'seller',//卖家账号
									'tracking_number'=>'',//物流号（选填）
									'tracking_link'=>'',//查询网址（选填）
									'shipping_method_code'=>'',//平台物流服务代码
									'shipping_method_name'=>'',//平台物流服务名
									'order_source_order_id'=>'',//平台订单号
									'return_no'=>'',//物流系统的订单号（选填）
									'shipping_service_id'=>'',//物流服务id（选填）
									'addtype'=>'',//物流号来源
									'signtype'=>'',//标记类型 all或者part（选填）
									'description'=>'',//备注（选填）
									)
	 * 							)
	 * $status 标记状态 如果是平台同步下来的物流信息说明已经标记过$status=1
	 * $is_shipped 是否自动标记发货
	 * $tmp_auto_trackNum 用于amazon平台是否自动生成小老板格式跟踪号
	 * return 布尔值
	 */
	public static function saveTrackingNumber($orderId,$logisticInfoList,$status = 0,$is_shipped=FALSE, $tmp_auto_trackNum = 0){
		if (count($logisticInfoList)>0){
			$tracking_numbers = array();
			$order = OdOrder::findOne($orderId);//20160830 提取出来
			foreach ($logisticInfoList as $o){
				############################设默认值######################################
				if ($status == 0){
					//判断是否有传入标记发货接口值，如果没有则赋默认值
					if (isset($o['shipping_method_code']) && strlen($o['shipping_method_code'])>0){
						//$order = OdOrder::findOne($orderId);//20160830 提取出来
						//todo 此处以后可以建立映射关系
					}else{
						//$order = OdOrder::findOne($orderId);//20160830 提取出来
						if ($order===null){
							return false;
						}
						if (strlen($order->default_shipping_method_code)>0){
							$o['shipping_method_code']=CarrierApiHelper::getServiceCode($order->default_shipping_method_code,$order->order_source);
						}else{
							$o['shipping_method_code']=CarrierApiHelper::getDefaultServiceCode($order->order_source);
						}
					}
					//修改shipping_method_name,原来是中文
					if (isset($o['shipping_method_code']) && strlen($o['shipping_method_code'])>0){
						$name = CarrierApiHelper::getServiceNameEn($o['shipping_method_code'], $order->order_source);
						if ($name == $o['shipping_method_code']){
							$o['shipping_method_name'] = strlen($o['shipping_method_name'])>0?$o['shipping_method_name']:$name;
						}else{
							$o['shipping_method_name'] = $name;
						}
					}
					if (empty($o['tracking_link'])){
						//$o['tracking_link'] = 'http://www.17track.net';
					}else{
						if(!preg_match('/^(http|https):\/\//i', $o['tracking_link']))
							$o['tracking_link'] = 'http://'.$o['tracking_link'];
					}
					if (!(isset($o['signtype']) && strlen($o['signtype'])>0)){
						$o['signtype'] = 'all';
					}
				}
				##########################################################
				$shipped_tracking_number = trim(@$o['tracking_number']);
				//保存物流号
				
				$N=OdOrderShipped::findOne(['order_id'=>$orderId,'tracking_number'=>$shipped_tracking_number]);
				//平邮是没有物流号的
				if (strlen($shipped_tracking_number)>0){
					$tracking_numbers[] = '"'.$shipped_tracking_number.'"';
				}
				
				if(empty($N)){
					//新插入 到 订单标发货表中
					$N=new OdOrderShipped();
					$o['order_id']=$orderId;
					$o['status']=$status;
					Helper_Array::removeEmpty($o);
					$attrs = $N->attributes();
					foreach ($o as $k=>$v){
						if (in_array($k, $attrs)){
							$N->$k = $v;
						}
					}
					$N->created = time();
				}else{
					Helper_Array::removeEmpty($o);
					$attrs = $N->attributes();
					if ($N->status !=1){//dzt20151020 for: eagle发货失败导致 status设置为0，再导致的订单更新物流信息时OdOrderShipped的status不能更新为1 。 修改后订单更新status 可以设置为1 ，让tracker抓取到。
						$N->status=$status;
					}
					
					foreach ($o as $k=>$v){
						if ($k=='addtype') continue; //kh20160111  不更新重写 物流号来源 ，假若重写， 所有 来源物流号来源都会变成平台 api
						
						if (in_array($k, $attrs)){
							$N->$k = $v;
						}
					}
					$N->updated = time();
				}
				
				if ($N->save() ){
					//新添加的代码,现在跟踪号在od_order_v2也有字段记录
					$order->tracking_number = $N->tracking_number;
					$order->save();
					
					// order_capture = Y为手工订单， 不再标记发货
					if($N->status == 0 && $is_shipped && ($order->order_capture !='Y')){
						//取出订单下说有的子订单
						//$order = OdOrder::findOne($orderId);//20160830 提取出来
						$items = OdOrderItem::find()->where(['order_id'=>$orderId])->select(['order_source_order_item_id','order_source_order_id','order_source_transactionid','order_source_itemid','platform_status'])->asArray()->all();
						try {
							$success_count = OdOrderShipped::find()->where(['order_id'=>$orderId , 'status'=>'1'])->count();
							if (empty($success_count)){
								$api_type = '1';
							}else{
								$api_type = '2';
							}
							
							//当是速卖通订单部分发货状态，物流API未发货，则算第一次发货， lrq20180402
							if($order->order_source == 'aliexpress' && $order->order_source_status == 'SELLER_PART_SEND_GOODS'){
								$count = OdOrderShipped::find()->where(['order_id'=>$order->order_id, 'status'=>1, 'addtype' => '物流API'])->count();
								if ($count == 0){
									$api_type = '1';
								}
							}
							
							//echo "api :".$api_type;
							$reasonMsg = "重新虚拟发货，所以取消这个标志发货的记录";
							QueueShippedHelper::cancelOrderSyncShippedQueue( $order->order_source_order_id, $order->order_source, $order->selleruserid, $reasonMsg , $N->id);
						} catch (\Exception $ex) {
							echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n"; 
						}
						
						$success = false;//是否插入队列成功
						if (count($items)){
							$arr = [];
							foreach ($items as $item){
								if ($N->order_source =='ebay'){
									$arr[$item['order_source_order_id']][]=OdEbayTransaction::find()->where(['id'=>$item['order_source_order_item_id']])->select(['transactionid','itemid'])->asArray()->one();
								}else{
									$arr[$item['order_source_order_id']][]=$item;
								}
							}
							foreach ($arr as $order_source_order_id=>$one){
								$params = array();
								if ($N->order_source =='ebay'){//ebay是按照子订单来标记发货的
									$params = $one;
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
								}else if ($N->order_source =='amazon'){
									$amazon_tracking_number = trim(@$o['tracking_number']);
									if (strlen($amazon_tracking_number) == 0){
										//假如用户在发货习惯设置=>不自动生成虚拟跟踪号  设置不自动生成跟踪号的话，这里就不会自动生成小老板的跟踪号
										$no_auto_mark_tracking_num = json_decode(ConfigHelper::getConfig('no_auto_mark_tracking_num'));
										$no_auto_mark_tracking_num = empty($no_auto_mark_tracking_num) ? array() : $no_auto_mark_tracking_num;
										$is_auto_tracking_num = true;
										
										if($tmp_auto_trackNum == 0){
											if(!empty($no_auto_mark_tracking_num)){
												foreach ($no_auto_mark_tracking_num as $tmp_val){
													if($tmp_val == 'amazon'){
														$is_auto_tracking_num = false;
													}
												}
											}
										}else if($tmp_auto_trackNum == 1){
											$is_auto_tracking_num = false;
										}
										
										if($is_auto_tracking_num == true){
											// amazon 发货插入发货队列前 如果客户没有填入跟踪号 ，则由系统生成。
											// 规则为XLB + 11位订单号 ，由于这是假的跟踪号，所以不考虑两个客户假的跟踪号一样的问题。
											// 小老板订单id 11位 UNSIGNED ZEROFILL
											$tmpOrderId = str_pad ($orderId , 11 ,"0" ,STR_PAD_LEFT);
											$amazon_tracking_number = "XLB".$tmpOrderId;
											$N->tracking_number = $amazon_tracking_number;
											$N->save();
										}
									}
									
	// 								$params = AmazonDeliveryApiHelper::getParams($orderId,$logisticInfoList);
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$one,$api_type);
								}else if ($N->order_source =='aliexpress'){//速卖通是按照主订单来标记发货
									//速卖通标记发货 tracker number 必须要填
									if (strlen($shipped_tracking_number)>0){
										$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
									}else{
										$success = false;
										return false;
									}
									
								}else if  ($N->order_source =='wish'){//wish是按照主订单来标记发货
									$addInfo = json_decode($order->addi_info,true);
									if( !empty( $addInfo ) ){
										if( isset( $addInfo['order_point_origin'] ) ){
											$params['order_point_origin']= $addInfo['order_point_origin'];
										}
									}
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);

								}else if  ($N->order_source =='dhgate'){//敦煌是按照主订单来标记发货
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
								}else if  ($N->order_source =='cdiscount'){//CD是按照主订单来标记发货
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
								}else if  ($N->order_source =='lazada' || $N->order_source =='linio' || $N->order_source =='jumia'){//lazada需要同一张订单的订单item id来标记发货，与amazon类似
									// 由于系统的lazada账号是通过lazada用户邮箱+站点来确认唯一的，为了方便查找lazada账号，这里记下订单站点id。
									// 确保合并订单时，lazada不同站点订单不能合并，否则查找lazada账号会找错账号。
									$params = array('items'=>$one,'sourceSite'=>$order->order_source_site_id);
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
								}elseif($N->order_source =='ensogo'){//ensogo是按照主订单来标记发货
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
								}
								elseif($N->order_source =='priceminister'){//插入priceminister标记发货队列
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
								}elseif($N->order_source =='bonanza'){//插入bonanza标记发货队列
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
								}elseif($N->order_source =='rumall'){
								    $success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
								}
								elseif($N->order_source =='newegg'){
									$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
								}else if ($N->order_source =='shopee'){//shopee是按照主订单来标记发货
									if (strlen($shipped_tracking_number)>0){
										$success = QueueShippedHelper::insertQueue($orderId,$order_source_order_id,$N->id,$params,$api_type);
									}else{
										$success = false;
										return false;
									}
										
								}
							}
						}
						if ($success){//成功插入，shipping_status=2表示通知平台发货中
							if(!empty($o['addtype']) && $o['addtype']=='手动标记发货')
								OperationLogHelper::log('order', $orderId,'手动标记发货','进入标记发货队列 [运单号]='.@$o['tracking_number'] ." [查询网址]=".@$o['tracking_link']." [运输服务]=".$o['shipping_method_code']."(".@$o['shipping_method_name'].")"." [发货留言]=".@$o['description'], \Yii::$app->user->identity->getFullName());
							else{
								OperationLogHelper::log('order', $orderId,'自动标记发货','进入标记发货队列 [运单号]='.@$o['tracking_number'] ." [查询网址]=".@$o['tracking_link']." [运输服务]=".$o['shipping_method_code']."(".@$o['shipping_method_name'].")"." [发货留言]=".@$o['description'], \Yii::$app->user->identity->getFullName());
							}
							$order->shipping_status=2;
							if(empty($order->order_ship_time))
								$order->order_ship_time=TimeUtil::getNow();
							
							
							/*
							 * 正常订单 插入队列，则列为提交中 (S)
							 * 原始订单插入队列， 则原始订单与合并订单也变成提交中
							 */
							OrderApiHelper::setOrderSyncShippedStatus($order, 'S');
							
							$order->save();
							
							
							//标记成功， 就检查虚假发货
							$thisRt = self::MarkOnlyPlatformShipped($order);
							// if('fm' == $order->order_relation){// dzt20160824 检查合并订单是否需要标上虚假发货tag ， 等待确认虚假发货之后才考虑要不要放出来
								// list($isMark,$msg) = self::isMarkOnlyPlatformShippedForSMOrder();
								// if($isMark){
									// $smOrder = OdOrder::findOne($msg);
									// self::MarkOnlyPlatformShipped($smOrder);
								// }
							// }
						}
					}
				}else{
					SysLogHelper::SysLog_Create('Order',__CLASS__,__FUNCTION__,'error',print_r($N->getErrors(),true));
					return false;
				}
			}
			
			if ($status == 1){//平台API同步时才更改
				if (count($tracking_numbers)==0){
					OdOrderShipped::updateAll(array('status' => 4,'sync_to_tracker'=>'N'), '`order_id` = '.$orderId.' AND `tracking_number` != "" AND `status` = 1');
				}else{
					OdOrderShipped::updateAll(array('status' => 4,'sync_to_tracker'=>'N'), '`order_id` = '.$orderId.' AND `tracking_number` != "" AND `status` = 1'.' AND `tracking_number` NOT IN ('.implode(',', $tracking_numbers).')');
					OdOrderShipped::updateAll(array('status' => 1), '`order_id` = '.$orderId.' AND `customer_number` IN ('.implode(',', $tracking_numbers).')');
				}
			}
			return true;
		}else{
			return false;
		}
	
	}
	
	/**
	 * 获取订单的sku数据
	 * @author fanjs
	 * $orderid 传入的od_order 的id
	 * return array sku的数组
	 */
	public static function getorderskus($orderid){
		if (empty($orderid))return false;
		$odorder = OdOrder::findOne($orderid);
		$items = $odorder->items;
		$sku=[];
		foreach ($items as $i){
			if (!is_null($i->sku)){
				array_push($sku,$i->sku);
			}
		}
		return $sku;
	}
	
	/**
	 * 获取订单的sku数据,支持捆绑商品
	 * @author fanjs
	 * $orderid 传入的od_order 的id
	 * return array sku的数组
	 */
	public static function getorderskuswithbundle($orderid){
		global $CACHE;
		if (empty($orderid))return false;
		
		//2016-07-01  为后台检测订单加上global cache 的使用方法 start
		$uid = \Yii::$app->subdb->getCurrentPuid();
		//var_dump($CACHE[$uid]);
		if (isset($CACHE[$uid]['orderItem'][$orderid])){
			$items = $CACHE[$uid]['orderItem'][$orderid];
			foreach ($items as &$i){
				if (is_array($i)){
					$i = (Object)$i;
				}
			}
			//var_dump($items);
			//echo "cache";
		}else{
			$odorder = OdOrder::findOne($orderid);
			$items = $odorder->items;
			//echo "no cache";//testkh 
		}
		//2016-07-01  为后台检测订单加上global cache 的使用方法 end
		
		$sku=[];
		foreach ($items as $i){
			
			if (!is_null($i->sku)&&strlen($i->sku)){
				$skus = ProductApiHelper::getSkuInfo($i->sku, $i->quantity);
				foreach ($skus as $one){
					//$products[] = array('sku'=>$one['sku'] ,'qty'=>$one['qty'],'order_id'=>$order->order_id);
					//array_push($sku,$one['sku']);
					$sku[$one['sku']]=$one['qty'];
				}
			}else{
				//sku 为空， 使用有prodct name 为sku
				$skus = ProductApiHelper::getSkuInfo($i->product_name, $i->quantity);
				foreach ($skus as $one){
					//$products[] = array('sku'=>$one['sku'] ,'qty'=>$one['qty'],'order_id'=>$order->order_id);
					//array_push($sku,$one['sku']);
					$sku[$one['sku']]=$one['qty'];
				}
			}
		}
		return $sku;
	}
	
	/**
	 * 更改订单状态
	 * @param OdOrder $M
	 * @param string $type
	 * @return boolean
	 * @author fanjs
	 */
	public static function setStatus(&$M,$type){
		if(!($M instanceof OdOrder)){
			if(is_array($M) && $M['order_id']){
				$M=OdOrder::model()->where('order_id=?',$M['order_id'])->getOne();
				if($M->isNewRecord){
					return false;
				}
			}else{
				return false;
			}
		}
	
		switch($type){
			case 'backmoney':
				//退款 状态 ,
				$M->pay_status=3;
				//只有 当 订单是未处理的状态时 才可以  变为 退款
				if($M->order_status== 100|| $M->order_status ==200 ||$M->order_status ==300){
					$M->order_status=600;
				}
				$M->save();
				break;
			case 'payed':
				//已经付款状态
				if($M->order_status==100){
					$M->order_status=200;
				}
		}
		
		$puid = \Yii::$app->subdb->getCurrentPuid();
		RedisHelper::delOrderCache( $puid , strtolower($M->order_source) );
		
		return true;
	}
		/*
	* @param 手动同步订单（Aliexpress已做，其他暂时没有涉及）
	* @return				订单id
	* @author yuehtian 2015-9-16
	**/
	public static function importPlatformOrderManual($order,$eagleOrderId=-1) {
		return "康华话obsolete  2016-8-31";  //阿华话obsolete  2016-8-31
	}
	
	/*
	 * 获取开启了发票功能的平台列表
	 */
	public static function getInvoicePlatforms(){
		return self::$invoice_platform;
	}
	
	/*
	 * 保存卖家设置的发票公司、地址信息
	 */
	public static function setSellerInvoiceInfos($uid,$info,$act="add"){
		$rtn['success']=true;
		$rtn['message']="";
		
		try{
			if($act=="add"){
				$sql = "INSERT INTO `od_seller_invoice_info`
						(`puid`, `company`, `vat`, `tax_rate`, `tax_formula`, `address`, `phone`, `email`, `stores`, `autographurl`, `type`) VALUES 
						($uid,:company,:vat,:tax_rate,:tax_formula,:address,:phone,:email,:stores,:autographurl,:type)";	
			}
			if($act=="edit" && !empty($info['id']) ){
				$sql = "UPDATE `od_seller_invoice_info` SET 
						`company`=:company,`vat`=:vat,`tax_rate`=:tax_rate,`tax_formula`=:tax_formula,`address`=:address,`phone`=:phone,`email`=:email,`stores`=:stores,`autographurl`=:autographurl,`type`=:type  
						WHERE `id`=".$info['id'];
			}
			
			$command = \Yii::$app->db->createCommand($sql);
			$command->bindValue(':company', $info['company'], \PDO::PARAM_STR);
			$command->bindValue(':vat', $info['vat'], \PDO::PARAM_STR);
			$command->bindValue(':tax_rate', $info['tax_rate'], \PDO::PARAM_INT);
			$command->bindValue(':tax_formula', $info['tax_formula'], \PDO::PARAM_INT);
			$command->bindValue(':address', $info['address'], \PDO::PARAM_STR);
			$command->bindValue(':phone', $info['phone'], \PDO::PARAM_STR);
			$command->bindValue(':email', $info['email'], \PDO::PARAM_STR);
			$command->bindValue(':stores', json_encode($info['stores']), \PDO::PARAM_STR);
			$command->bindValue(':type', $info['invoice_type'], \PDO::PARAM_STR);
			
			if(!empty($info['autographurl']))
				$command->bindValue(':autographurl', $info['autographurl'], \PDO::PARAM_STR);
			else 
				$command->bindValue(':autographurl', '', \PDO::PARAM_STR);
			$result = $command->execute();
			if(!$result){
				$rtn['success']=false;
				$rtn['message']="数据保存失败！";
			}
			
		}catch(\Exception $e){
			$rtn['success']=true;
			$rtn['message'] = $e->getMessage();
		}
		return $rtn;
	}
	
	/*
	 * 获取用户设置的卖家发票信息
	 */
	public static function getSellerInvoiceInfos($puid,$platform='',$site='',$seller='',$type = ''){
		/*
		 * stores:[
		 * 	platform1=>[
		 * 		site1=>[name1,name2..],
		 * 		site2=>[name3,name4..],
		 * 		....],
		 * platform2=>[...],
		 * ....
		 * ]
		 */
	   
		$query = 'SELECT * FROM `od_seller_invoice_info` WHERE `puid`='.$puid;
		if($type != '')
		    $query .= ' and type="'.$type.'"';
		$command = \Yii::$app->db->createCommand($query);
		$records = $command->queryAll();
		
		$sellerInvoiceInfos = [];
		if($type == 'G' || empty($platform)){//不指定platform的情况，一般是获取list
			foreach ($records as $r){
				$r['stores'] = json_decode($r['stores'],true);
				$sellerInvoiceInfos[] = $r;
			}
		}
		else{//指定了platform的情况，一般是生成发票调用
			foreach ($records as $r){
				$r['stores'] = json_decode($r['stores'],true);
				if(!empty($r['stores'])){
					if(!empty($site) && !empty($seller)){
						if(!empty($r['stores'][$platform][$site])){
							foreach ($r['stores'][$platform][$site] as $store){
								if($seller==$store)
									$sellerInvoiceInfos = $r;
							}
						}
					}
				}
			}
		}
		
		return $sellerInvoiceInfos;
	}
	
	/**
	 * 组织用于订单发票打印的html
	 */
	public static function pdf_order_invoice($orderId, $type = '', $puid=0){
		
		$order = OdOrder::findOne($orderId);
		if($order==null)
			return	array('success'=>false,'message'=>'没有对应订单信息');
		
		//需要用到的订单信息
		$Print_List['init']=[];
		
		$platform = $order->order_source;
		$site = $order->order_source_site_id;
		
		$billingInfo = empty($order->billing_info)?[]:json_decode($order->billing_info,true);
		switch ($platform){
			case 'cdiscount':
				if(empty($billingInfo)){
					$billingInfo = CdiscountOrderInterface::getBillingInfoByOrder($order->order_source_order_id);
					$Print_List['init']['billaddress'] =$billingInfo['address'];
				}else{
					$Print_List['init']['billaddress'] = @$billingInfo['address_line1'].'<br>'.@$billingInfo['address_line2'];
				}
				
				$Print_List['init']['billpostcode']=$billingInfo['post_code'];
				$Print_List['init']['billphone']=$billingInfo['phone'];
				$Print_List['init']['billcompany']=empty($billingInfo['company'])?'':$billingInfo['company'];
				$Print_List['init']['billcity']=$billingInfo['city'];
				$Print_List['init']['billcountry']=$billingInfo['country'];
				$Print_List['init']['billname']=$billingInfo['name'];
				break;
			//Amazon、PM无bill信息
			default:
				$Print_List['init']['billaddress'] = empty($billingInfo['address_line1'])?'':$billingInfo['address_line1'];
				$Print_List['init']['billaddress'] .= empty($billingInfo['address_line2'])?'':'<br>'.$billingInfo['address_line2'];
				$Print_List['init']['billpostcode'] = empty($billingInfo['post_code'])?'':$billingInfo['post_code'];
				$Print_List['init']['billphone'] = empty($billingInfo['phone'])?'':$billingInfo['phone'];
				$Print_List['init']['billcompany'] = empty($billingInfo['company'])?'':$billingInfo['company'];
				$Print_List['init']['billcity'] = empty($billingInfo['city'])?'':$billingInfo['city'];
				$Print_List['init']['billcountry'] = empty($billingInfo['country'])?'':$billingInfo['country'];
				$Print_List['init']['billname'] = empty($billingInfo['name'])?'':$billingInfo['name'];
				break;
		}
		
		
		$Print_List['init']['orderId'] = $order->order_source_order_id;
		$Print_List['init']['customerName']=$order->consignee;
		$Print_List['init']['address']=
			$order->consignee_address_line1.(empty($order->consignee_address_line2)?'':' '.$order->consignee_address_line2)
			.(!empty($order->consignee_district)?' '.$order->consignee_district:'')
			.(!empty($order->consignee_county)?' '.$order->consignee_county:'')
			.(!empty($order->consignee_city)?' , '.$order->consignee_city:'')
			.(!empty($order->consignee_province)?' , '.$order->consignee_province:'')
			.' , '.$order->consignee_country;
		$Print_List['init']['shipping_city'] = !empty($order->consignee_city)?$order->consignee_city:'';
		$Print_List['init']['shipping_country'] = !empty($order->consignee_country)?$order->consignee_country:'';
		$Print_List['init']['postcode']=$order->consignee_postal_code;
		$Print_List['init']['selleruserid']=$order->selleruserid;
		$currency = $order->currency;
		$Print_List['init']['phoneNo1']=empty($order->consignee_mobile)?$order->consignee_phone:$order->consignee_mobile;
		$subtotal = empty($order->subtotal)?0:$order->subtotal;
		$Print_List['init']['subtotal_fm']=PlatformUtil::formatCurrencyPrice($currency, $subtotal);
		$shipping_cost = empty($order->shipping_cost)?0:$order->shipping_cost;
		$Print_List['init']['shippingamt_fm']=PlatformUtil::formatCurrencyPrice($currency, $shipping_cost);
		//cd平台没有记录折扣
		//$Print_List['init']['discountamt_fm']=!empty($order->discount_amount)?PlatformUtil::formatCurrencyPrice($currency, $order->discount_amount):PlatformUtil::formatCurrencyPrice($currency,0);
		$invoice_grand_total = $subtotal ;
		$Print_List['init']['grandtotal_fm']=PlatformUtil::formatCurrencyPrice($currency, $invoice_grand_total+$shipping_cost);
		
		//卖家公司、地址信息
		if(empty($puid))
			$puid = \Yii::$app->user->identity->getParentUid();
		$sellerInvoiceInfo = OrderHelper::getSellerInvoiceInfos($puid,$platform,$site,$order->selleruserid,$type);
		//税率,税费
		//(税前商品价+税前运费)+VAT=总价       $invoice_grand_total = ($subtotal + $shipping_cost)*(1-$tax_rate)+($subtotal + $shipping_cost)*$tax_rate
		//tax_formula 公式——1：不含税价格=总价/（ 1+增值税率）；2：不含税价格=总价*(1-增值税率)
		$tax_rate = empty($sellerInvoiceInfo['tax_rate'])?0:(int)$sellerInvoiceInfo['tax_rate']/100;
		
		$tax_formula = 1;
		if(!empty($sellerInvoiceInfo['tax_formula']) && $sellerInvoiceInfo['tax_formula']==2)
			$tax_formula = 2;
		
		if($tax_formula==1){
			$total_without_tax_fm = round($invoice_grand_total/(1+$tax_rate),2);
			$Print_List['init']['tax_fm'] = PlatformUtil::formatCurrencyPrice($currency, $total_without_tax_fm*$tax_rate);
			$Print_List['init']['total_without_tax_fm'] = PlatformUtil::formatCurrencyPrice($currency, $total_without_tax_fm);
			$Print_List['init']['tax_rate'] = empty($sellerInvoiceInfo['tax_rate'])?'0%':$sellerInvoiceInfo['tax_rate'].'%';
		}
		if($tax_formula==2){
			$total_without_tax_fm = round($invoice_grand_total*(1-$tax_rate),2);
			$Print_List['init']['tax_fm'] = PlatformUtil::formatCurrencyPrice($currency, $invoice_grand_total*$tax_rate);
			$Print_List['init']['total_without_tax_fm'] = PlatformUtil::formatCurrencyPrice($currency, $total_without_tax_fm);
			$Print_List['init']['tax_rate'] = empty($sellerInvoiceInfo['tax_rate'])?'0%':$sellerInvoiceInfo['tax_rate'].'%';
		}
		
		//需要用到的订单商品信息
		$sumqty = 0;
		$sumtotal = 0;
		$Print_List['details'] = [];
		foreach ($order->items as $item){
			$row=[];
			$row['sku'] = $item->sku;
			$row['name'] = $item->product_name;
			$row['qty'] = $item->quantity;
			//高青发票金额：英文符号+数字
			if($type == 'G')
			{
			    $row['priceorig_fm'] = !empty($item->price) ? $currency.' '.$item->price : $currency.' 0';
			    $row['value_fm'] = $currency.' '.sprintf("%.2f", $item->price*$item->quantity);
			}
			else
			{
				if($tax_formula==1){
			    	$row['priceorig_fm'] = !empty($item->price)?PlatformUtil::formatCurrencyPrice($currency,$item->price):PlatformUtil::formatCurrencyPrice($currency,0);
			    	$row['value_fm'] = PlatformUtil::formatCurrencyPrice($currency,$item->price*$item->quantity);
				}elseif($tax_formula==2){
			    	$row['priceorig_fm'] = !empty($item->price)?PlatformUtil::formatCurrencyPrice($currency, round($item->price,2) * (1-$tax_rate) ):PlatformUtil::formatCurrencyPrice($currency,0);
			    	$row['value_fm'] = PlatformUtil::formatCurrencyPrice($currency, round($item->price,2) * (int)$item->quantity * (1-$tax_rate) );
				}
			}
			$Print_List['details'][] = $row;
			
			//合计
			if(!empty($item->quantity))
				$sumqty += $item->quantity;
			if(!empty($item->quantity) && !empty($item->price))
				$sumtotal += $item->price*$item->quantity;
		}
		
		if($type == 'G')
		{
    		//合计数量、金额
    		$Print_List['order']['sumqty'] = $sumqty;
    		$Print_List['order']['sumtotal'] = $currency.' '.sprintf("%.2f", $sumtotal);
    		//总金额转换英文
    		$val = '';
    		if ($currency == 'USD')
    			$val = "UNITED STATES DOLLAR ";
    		else if ($currency == 'EUR')
    			$val = "ESTIMATED ULTIMATE RECOVERY ";
    		else if ($currency == 'CNY')
    			$val = "CHINESE YUAN ";
    		else if ($currency == 'GBP')
    			$val = "GREAT BRITAIN POUND ";
    		else if ($currency == 'AUD')
    			$val = "AUSTRALIAN DOLLARS ";
    		
    		$api = new transBAIDU_2();
    		$result = $api->translate($sumtotal, 'zh', 'en');
    		if(empty($result['data']))
    		    $Print_List['order']['EnSumtotal'] = $val.'ZERO';
    		else
    		    $Print_List['order']['EnSumtotal'] = $val.strtoupper($result['data']);
		}
		
		//获取订单目的国语言code
		$consignee_country_code = $order->consignee_country_code;
		if(empty($consignee_country_code))
			$consignee_country_code = $order->order_source_site_id;
		 
		$lang = TrackingMsgHelper::getToNationLanguage($consignee_country_code);
		//获取订单目的国语言code end
		
		//@todo 目前只有法文，德文，英文三种语言，其他语言需要后续完善
		/*
		 * $Print_List['other']		array		订单的平台信息 或 对应语言信息
		 * 		array(
		 * 			Logo_Url,//订单平台logo
		 * 			orderno,//翻译-订单号
		 * 			orderdate,//翻译-下单日期
		 * 			dd,//翻译-日期格式
		 * 			PaymentAddress,//翻译-地址
		 * 			postcode,//翻译-邮编
		 * 			telephone,//翻译-电话
		 * 			sku,//翻译-sku
		 * 			productname,//翻译-商品名
		 * 			price,//翻译-单价
		 * 			qty,//翻译-数量
		 * 			subtotal,//翻译-商品总价
		 * 			shippingamt,//翻译-运费
		 * 			discountamt,//翻译-优惠
		 * 			grandtotal,//翻译-总价
		 * 			refunded,//翻译-退款
		 * 		)
		 * 
		 * $Print_List['init']		array		订单原始信息
		 */
		$Lang_ary = array('invoice' , 'orderno' , 'orderdate' , 'dd' , 'PaymentAddress' , 'ShippingAddress' ,
				'postcode' , 'telephone' , 'sku' , 'productname' , 'qty' , 'price' , 'subtotal' , 'shippingamt' ,
				'discountamt' , 'grandtotal','refunded','nettotal','VAT');
		foreach ($Lang_ary as $l_a)
			$OtherList [$l_a]  = PlatformUtil::getLangStr($l_a, $lang);
		
		//组织logo的url
		//@todo 如果该站点对应logo还没有保存到本地，需要先保存logo到本地
		$OtherList ['Logo_Url'] = PlatformUtil::getLogo($platform,$site);
		
		//组织时间格式
		//@todo 如果该站点对应语言 日期特殊格式 还没有设定，需要到TimeUtil::getDateStr先配置，否则为英文格式
		$dd = $order->order_source_create_time;
		if(strlen($dd)==10)
		{
			if($type == 'G')
			{
				$dd = date("Y/m/d",$dd);
				$OtherList ['dd'] = $dd;
			}
			else 
			{
				$dd = date("Y-m-d h-i-s",$dd);
				$OtherList ['dd'] = TimeUtil::getDateStr ( $dd, $lang );
			}
		}
		else 
			$OtherList ['dd']='--';
		
		$Print_List['other'] = $OtherList;
		
		$html_head =
			'<style>
			table{width:100%;padding-top:20px;}
			.pnt_col_head {font-weight:bold;color:white;}
			.items th,.items td{border:1px solid #ddd;border-collapse:collapse;padding:0px;margin:0px;}
			.gaoqing_items th{border:1px solid;padding:0 0 0 5px;margin:0px; text-align:center}
			.gaoqing_items td{border:1px solid;padding:0 0 0 5px;margin:0px; height:30px; text-align:center}
			</style>';
		
		if($type == 'G')
			$html_body = self::gaoqing_invoice_format($Print_List, $platform, $site, $sellerInvoiceInfo);
		else{
			switch ($platform){
				case 'cdiscount':
					$html_body = self::cdiscount_invoice_format($Print_List, $platform, $site, $sellerInvoiceInfo);
					break;
				case 'priceminister':
					$html_body = self::cdiscount_invoice_format($Print_List, $platform, $site, $sellerInvoiceInfo);
					break;
				default:
					$html_body = self::default_invoice_format($Print_List, $platform, $site, $sellerInvoiceInfo);
					break;
			}
		}

		$html = $html_head.$html_body;

		return ($html);
	}
	
	/* 订单默认发票模板
	 * 使用平台：amazon、
	 */
	private static function default_invoice_format($invoiceData,$platform, $site, $sellerInvoiceInfo){
		$html_body = '<page orientation="p">';

		$html_body.=
		'<table STYLE="position:relative;page-break-after:always;">
			<col style="width: 49%">
			<col style="width: 49%">
				<tr>
					<td colspan="2" style="position:relative;width:100%;vertical-align: middle;">
						<div style="float:left;position: absolute;top:20px;"><img width="159" src="'.$invoiceData['other']['Logo_Url'].'" border="0"></div>';
		
		//获取平台站点全称
		//@todo 如果未设置，需要到PlatformUtil::getPlatformSiteInfo 里面设置 ， 需要不断完善
		$platform_info = PlatformUtil::getPlatformSiteInfo($platform, $site);
		if(!empty($platform_info['name']))
			$platform_name = $platform_info['name'];
		else
			$platform_name = $platform.' '.$site;
		
		$html_body.= "<div style='text-align:center;'><span style='padding-top:5px; Font-size:35px'>";
		$html_body.=$platform_name;
		$html_body.= "</span></div>";
		
		$html_body.='</td>
				</tr>';
		$html_body.=
				'<tr>
					<td colspan="2" style="text-align:center">
				  		<h2 style="padding-top:15px; Font-size:14px;color: #317eac;">'.$invoiceData['other']['invoice'].'</h2>
				  	</td>
				</tr>';
		if(!empty($sellerInvoiceInfo['company']))
			$html_body.='<tr><td></td><td style="text-align:right;white-space:pre-wrap">'.$sellerInvoiceInfo['company'].'</td></tr>';
		if(!empty($sellerInvoiceInfo['vat']))
			$html_body.='<tr><td></td><td style="text-align:right;white-space:pre-wrap">VAT : '.$sellerInvoiceInfo['vat'].'</td></tr>';
		if(!empty($sellerInvoiceInfo['address']))
			$html_body.='<tr><td></td><td style="text-align:right;white-space:pre-wrap">Address : '.$sellerInvoiceInfo['address'].'</td></tr>';
		
		$html_body.='<tr>
					<td>'.$invoiceData['other']['orderno']." : ".$invoiceData['init']['orderId'].'</td><td style="text-align:right">'.
					(empty($sellerInvoiceInfo['phone'])?'':'Telphone : '.$sellerInvoiceInfo['phone']).'</td>
				</tr>
				<tr>
				 	<td>'.$invoiceData['other']['orderdate']." : ".$invoiceData['other']['dd'].'</td><td style="text-align:right">'.
				 	(empty($sellerInvoiceInfo['email'])?'':'E-mail : '.$sellerInvoiceInfo['email']).
				'</td></tr>
			</table>';
		
		$html_body.=
		'<table>'.
		'<col style="width: 100%">'.
		'<tr height="27" style="background: #777;"  class="pnt_col_head">'.
		(($invoiceData['init']['billaddress'] !=="")?
				'<td>'.$invoiceData['other']['PaymentAddress'].': </td>':
				'<td>'.$invoiceData['other']['ShippingAddress'].': </td>').
			'</tr>
			<tr height="27">'.
					'<td>'.$invoiceData['init']['customerName'].'</td>
			</tr>
			<tr height="27">'.
					(($invoiceData['init']['billaddress'] !=="")?
							'<td>'.$invoiceData['init']['billaddress'].'</td>':
							'<td>'.$invoiceData['init']['address'].'</td>').
							'</tr>
			<tr height="27">'.
					(($invoiceData['init']['billaddress'] !=="" && !empty($invoiceData['init']['billpostcode']))?
							'<td>'.$invoiceData['other']['postcode'].":".$invoiceData['init']['billpostcode'].'</td>':
							'<td>'.$invoiceData['other']['postcode'].":".$invoiceData['init']['postcode'].'</td>').
							'</tr>
			<tr height="27">'.
					(($invoiceData['init']['billaddress'] !=="" && !empty($invoiceData['init']['billphone']))?
							'<td>'.$invoiceData['other']['telephone'].":".$invoiceData['init']['billphone'].'</td>':
							'<td>'.$invoiceData['other']['telephone'].":".$invoiceData['init']['phoneNo1'].'</td>').
							'</tr>
		</table>';
		
		$html_body .=
		'<table>
			<col style="width: 20%">
    		<col style="width: 45%">
		    <col style="width: 10%">
		    <col style="width: 10%">
		    <col style="width: 15%">
			<tr height="27" style="background:#777;" class="pnt_col_head">
				<td style="white-space:pre-wrap">'.$invoiceData['other']['sku'].'</td>
				<td style="white-space:pre-wrap">'.$invoiceData['other']['productname'].'</td>
				<td>'.$invoiceData['other']['price'].'</td>
				<td>'.$invoiceData['other']['qty'].'</td>
				<td style="white-space:nowrap;">'.$invoiceData['other']['subtotal'].'</td>
			</tr>';
		
		foreach ($invoiceData['details'] as $row){
			$html_body .=
			'<tr height="27">
				<td style="white-space:pre-wrap">'.$row['sku'].'</td>
				<td style="white-space:pre-wrap">'.$row['name'].'</td>
				<td style="white-space:nowrap;text-align:right">'.$row['priceorig_fm'].'</td>
				<td style="text-align:right">'.$row['qty'].'</td>
				<td style="white-space:nowrap;text-align:right">'.$row['value_fm'].'</td>
			</tr>';
		}
		
		
		if(!empty($sellerInvoiceInfo['tax_rate']) && !empty($invoiceData['init']['tax_fm']) && !empty($invoiceData['init']['total_without_tax_fm'])){
			$html_body.=
			'<tr height="27">
				<td colspan=4 style="text-align:right;">'.$invoiceData['other']['nettotal'].'</td><td style="text-align:right;">'.$invoiceData['init']['total_without_tax_fm'].'</td>
			</tr>
			<tr height="27">
				<td colspan=4 style="text-align:right;">'.$invoiceData['other']['VAT'].'('.$invoiceData['init']['tax_rate'].')</td><td style="text-align:right;">'.$invoiceData['init']['tax_fm'].'</td>
			</tr>';
		}else{
			$html_body .=
			'<tr height="27">
				<td colspan=4 style="text-align:right">'.$invoiceData['other']['subtotal'].':</td>
				<td style="white-space:nowrap;text-align:right">'.$invoiceData['init']['subtotal_fm'].'</td>
			</tr>';
		}
		$html_body.=
			'<tr height="27">
				<td colspan=4 style="text-align:right">'.$invoiceData['other']['shippingamt'].':</td>
				<td style="white-space:nowrap;text-align:right">'.$invoiceData['init']['shippingamt_fm'].'</td>
			</tr>'.
					/*不显示折扣
					 '<tr height="27">
		<td colspan=4 style="text-align:right">'.$Print_List['other']['discountamt'].':</td>
		<td style="white-space:nowrap;text-align:right">- '.$Print_List['init']['discountamt_fm'].'</td>
		</tr>'.
		*/
		'<tr height="27">
				<td colspan=4 style="text-align:right; font-weight: bold">'.$invoiceData['other']['grandtotal'].':</td>
				<td style="white-space:nowrap;text-align:right; font-weight: bold">'.$invoiceData['init']['grandtotal_fm'].'</td>
			</tr>';
		
		/*VAT显示方案1
		if(!empty($sellerInvoiceInfo['tax_rate']) && !empty($invoiceData['init']['tax_fm']) ){
			$html_body.='<tr><td colspan=5></td>&nbsp;</tr><tr><td colspan=5></td>&nbsp;</tr>
			<tr>
				<td colspan=4 style="text-align:right;">VAT</td><td style="text-align:right;">'.$invoiceData['init']['tax_fm'].'</td>
			</tr>
			<tr>
				<td colspan=4 style="text-align:right;">VAT Not Include</td><td style="text-align:right;">'.$invoiceData['init']['total_without_tax_fm'].'</td>
			</tr>
			<tr>
				<td colspan=4 style="text-align:right;font-weight: bold">Total</td><td style="text-align:right;font-weight: bold">'.$invoiceData['init']['grandtotal_fm'].'</td>
			</tr>';
		}
		*/
		
		$html_body .= '</table>';
		$html_body .= '</page>';
		
		/*eagle暂时没有记录refund详细信息
		 // @todo 日后有需要再完善
		if (count($Print_List['refunds']) > 0){
		$html_body.=
		'<br>'.$Print_List['other']['refunded'].'<br>
		<table>
		<col style="width:20%">
		<col style="width:45%">
		<col style="width:10%">
		<col style="width:10%">
		<col style="width:15%">
		<tr height="27" style="background:#777;" class="pnt_col_head">
		<td>'.$Print_List['other']['sku'].'</td>
		<td>'.$Print_List['other']['productname'].'</td>
		<td>'.$Print_List['other']['price'].'</td>
		<td>'.$Print_List['other']['qty'].'</td>
		<td>'.$Print_List['other']['subtotal'].'</td>
		</tr>';
		foreach ($Print_List['refunds'] as $row){
		$html_body.=
		'<tr height="27">
		<td>'.$row['cp_number'].'</td>
		<td>'.$row['name2'].'</td>
		<td style="text-align:right">'.$row['priceorig_fm'].'</td>
		<td style="text-align:right">'.$row['qty'].'</td>
		<td style="text-align:right">'.$row['value_fm'].'</td>
		</tr>';
		}
		$html_body.='</table>';
		}
		*/
		return $html_body;
	}
	
	/* Cdiscount、Priceminister订单发票模板
	 * 目前只有cdiscount、priceminister使用
	 */
	private static function cdiscount_invoice_format($invoiceData,$platform, $site, $sellerInvoiceInfo){
		//tax_formula 公式——1：不含税价格=总价/（ 1+增值税率）；2：不含税价格=总价*(1-增值税率)
		if(!empty($sellerInvoiceInfo['tax_formula']) && $sellerInvoiceInfo['tax_formula']==2)
			$tax_formula = 2;
		else 
			$tax_formula = 1;
		
		$html_body = '<page orientation="p">';
		$html_body.=
		'<div style="width: 98%;padding-top:10px;">
			<img width="159" src="'.$invoiceData['other']['Logo_Url'].'" border="0">
		</div>'.
		'<table style="padding-top:0px;">
			<col style="width: 50%">
    		<col style="width: 50%">';
		//$puid = \Yii::$app->user->identity->getParentUid();
		//$sellerInvoiceInfo = OrderHelper::getSellerInvoiceInfos($puid,$platform,$site,$seller);
		$store_name = !empty($sellerInvoiceInfo['company'])?$sellerInvoiceInfo['company']:'';
		if(empty($store_name)){
			if(strtolower($platform)=='cdiscount'){
				$store = SaasCdiscountUser::find()->andwhere(['username'=>$invoiceData['init']['selleruserid']])->one();
				$store_name = !empty($store->shopname)?$store->shopname:'';
			}
			if(strtolower($platform)=='priceminister'){
				$store = SaasPriceministerUser::find()->andwhere(['username'=>$invoiceData['init']['selleruserid']])->one();
				$store_name = !empty($store->shopname)?$store->shopname:'';
			}
		}
		if( !empty($invoiceData['init']['billname']) && !empty($invoiceData['init']['billaddress']) ){
			$invoice_name = $invoiceData['init']['billname'];
			$invoice_address = $invoiceData['init']['billaddress'];
			$invoice_city = empty($invoiceData['init']['billcity'])?$invoiceData['init']['shipping_city']:$invoiceData['init']['billcity'];
			$invoice_country = empty($invoiceData['init']['billcountry'])?$invoiceData['init']['shipping_country']:$invoiceData['init']['billcountry'];
			$invoice_postcode = empty($invoiceData['init']['billpostcode'])?$invoiceData['init']['consignee_postal_code']:$invoiceData['init']['billpostcode'];
		}else{
			$invoice_name = '';
			$invoice_address = '';
			$invoice_city = '';
			$invoice_country = '';
			$invoice_postcode = '';
			if(strtolower($platform)=='priceminister'){
				$invoice_name = $invoiceData['init']['customerName'];
				$invoice_address = $invoiceData['init']['address'];
				$invoice_city = $invoiceData['init']['shipping_city'];
				$invoice_country = $invoiceData['init']['shipping_country'];
				$invoice_postcode = $invoiceData['init']['postcode'];
			}
		}
		
		/*
		 * if(!empty($sellerInvoiceInfo['company']))
			$html_body.='<tr><td></td><td style="text-align:right;white-space:pre-wrap">'.$sellerInvoiceInfo['company'].'</td></tr>';
		if(!empty($sellerInvoiceInfo['vat']))
			$html_body.='<tr><td></td><td style="text-align:right;white-space:pre-wrap">VAT : '.$sellerInvoiceInfo['vat'].'</td></tr>';
		if(!empty($sellerInvoiceInfo['address']))
			$html_body.='<tr><td></td><td style="text-align:right;white-space:pre-wrap">Address : '.$sellerInvoiceInfo['address'].'</td></tr>';
		
		$html_body.='<tr>
					<td>'.$invoiceData['other']['orderno']." : ".$invoiceData['init']['orderId'].'</td><td style="text-align:right">'.
					(empty($sellerInvoiceInfo['phone'])?'':'Telphone : '.$sellerInvoiceInfo['phone']).'</td>
				</tr>
				<tr>
				 	<td>'.$invoiceData['other']['orderdate']." : ".$invoiceData['other']['dd'].'</td><td style="text-align:right">'.
				 	(empty($sellerInvoiceInfo['email'])?'':'E-mail : '.$sellerInvoiceInfo['email']).
				'</td></tr>
			</table>';
		 */
		
		
		$html_body.=
			'<tr height="27">
				<td colspan=2 style="text-align:center">
				  	<h2 style="padding-top:15px; Font-size:14px;color: #317eac;">'.$invoiceData['other']['invoice'].'</h2>
				</td>
			</tr>
			<tr height="27">
				<td colspan=2 style="text-align:left">Les marchandises de la société : '.$store_name.'</td>	
			</tr>'.
			(empty($sellerInvoiceInfo['address'])?'':'<tr height="27"><td colspan=2 style="text-align:left">Adresse : '.$sellerInvoiceInfo['address'].'</td></tr>').
			(empty($sellerInvoiceInfo['phone'])?'':'<tr height="27"><td colspan=2 style="text-align:left">Téléphone : '.$sellerInvoiceInfo['phone'].'</td></tr>').'
			<tr height="27"><td colspan=2>&nbsp;</td></tr>
			<tr height="27">
				<td width="50%"></td>
				<td width="50%">Facturé à <br>&nbsp;&nbsp;&nbsp;&nbsp;'.$invoice_name.'</td>
			</tr>'.
			(!empty($invoiceData['init']['company'])?
			'<tr height="27">
				<td width="50%"></td>
				<td width="50%">Nom de Société : <br>'.$invoiceData['init']['company'].'</td>
			</tr>':'').
			'<tr height="27">
				<td width="50%"></td>
				<td width="50%" style="white-space: pre-wrap">Numéro et Nom de rue :<br>&nbsp;&nbsp;&nbsp;&nbsp;'.$invoice_address.'</td>
			</tr>'.
			(!empty($invoice_postcode)?
			'<tr height="27">
				<td width="50%"></td>
				<td width="50%">Code Postal : '.$invoice_postcode.'</td>
			</tr>':'').
			(!empty($invoice_city)?
			'<tr height="27">
				<td width="50%"></td>
				<td width="50%">Ville : '.$invoice_city.'</td>
			</tr>':'').
			(!empty($invoice_country)?
			'<tr height="27">
				<td width="50%"></td>
				<td width="50%">Pays : '.$invoice_country.'</td>
			</tr>':'')
			.'<tr>
				<td width="50%">Date : '.$invoiceData['other']['dd'].'</td>
				<td width="50%"></td>
			</tr>
			<tr>
				<td width="50%">Numéro de facture : '.$invoiceData['init']['orderId'].'</td>
				<td width="50%"></td>
			</tr>
		</table>';
		
		$html_body .=
		'<table class="items" cellspacing="0" style="empty-cells:show;border-collapse:collapse;">
			<col style="width: 35%">
    		<col style="width: 15%">
		    <col style="width: 25%">
		    <col style="width: 25%">
			<tr height="27" style="background:#777;" class="pnt_col_head">
				<td>Désignation</td>
				<td>Quantité</td>
				<td>Prix unitaire HT'.($tax_formula==1?'(incluant TVA)':'').'</td>
				<td>Prix total HT'.($tax_formula==1?'(incluant TVA)':'').'</td>
			</tr>';
		
		foreach ($invoiceData['details'] as $row){
			$html_body .=
			'<tr height="27">
				<td>'.$row['name'].'</td>
				<td style="text-align:left">'.$row['qty'].'</td>
				<td style="white-space:nowrap;text-align:left">'.$row['priceorig_fm'].'</td>
				<td style="white-space:nowrap;text-align:left">'.$row['value_fm'].'</td>
			</tr>';
		}
		//运费
		if($invoiceData['init']['shippingamt_fm'] !=='0.00 €')
			$html_body .='<tr height="27">
				<td colspan=2>&nbsp;</td><td></td><td></td>
			</tr>
			<tr height="27">
				<td colspan=2 style="text-align:right">Frais de livraison:</td>
				<td></td>
				<td style="white-space:nowrap;text-align:left">'.$invoiceData['init']['shippingamt_fm'].'</td>
			</tr>';
		$html_body .=
		'<tr height="27">
				<td colspan=2 style="text-align:right">Total Hors Taxe:</td>
				<td></td>
				<td style="white-space:nowrap;text-align:left">'.$invoiceData['init']['total_without_tax_fm'].'</td>
			</tr>
			<tr height="27">
				<td colspan=2>&nbsp;</td><td></td><td></td>
			</tr>
			<tr>
				<td colspan=2 style="text-align:right">TVA('.$invoiceData['init']['tax_rate'].'):</td>
				<td></td>
				<td style="white-space:nowrap;text-align:left">'.$invoiceData['init']['tax_fm'].'</td>
			</tr>';
		/*不显示折扣
		'<tr height="27">
			<td colspan=4 style="text-align:right">'.$Print_List['other']['discountamt'].':</td>
			<td style="white-space:nowrap;text-align:right">- '.$Print_List['init']['discountamt_fm'].'</td>
		</tr>'.
		*/
		/*
		'<tr height="27">
			<td colspan=2 style="text-align:right">TVA non applicable:</td>
			<td></td>
			<td>0.00 €</td>
		</tr>'*/
		$html_body .='<tr height="27">
			<td colspan=2>&nbsp;</td><td></td><td></td>
		</tr>
		<tr height="27">
				<td colspan=2 style="text-align:right; font-weight: bold">Total en euros:</td>
				<td></td>
				<td style="white-space:nowrap;text-align:left; font-weight: bold">'.$invoiceData['init']['grandtotal_fm'].'</td>
			</tr>
		</table>';
		$html_body .= '</page>';
		/*eagle暂时没有记录refund详细信息
		 // @todo 日后有需要再完善
		if (count($Print_List['refunds']) > 0){
		$html_body.=
		'<br>'.$Print_List['other']['refunded'].'<br>
		<table>
		<col style="width:20%">
		<col style="width:45%">
		<col style="width:10%">
		<col style="width:10%">
		<col style="width:15%">
		<tr height="27" style="background:#777;" class="pnt_col_head">
		<td>'.$Print_List['other']['sku'].'</td>
		<td>'.$Print_List['other']['productname'].'</td>
		<td>'.$Print_List['other']['price'].'</td>
		<td>'.$Print_List['other']['qty'].'</td>
		<td>'.$Print_List['other']['subtotal'].'</td>
		</tr>';
		foreach ($Print_List['refunds'] as $row){
		$html_body.=
		'<tr height="27">
		<td>'.$row['cp_number'].'</td>
		<td>'.$row['name2'].'</td>
		<td style="text-align:right">'.$row['priceorig_fm'].'</td>
		<td style="text-align:right">'.$row['qty'].'</td>
		<td style="text-align:right">'.$row['value_fm'].'</td>
		</tr>';
		}
		$html_body.='</table>';
		}
		*/
		return $html_body;
	}
	
	/* 高青订单发票模板
	*/
	private static function gaoqing_invoice_format($invoiceData,$platform, $site, $sellerInvoiceInfo){
		$html_body = '<body>';
		$company = empty($sellerInvoiceInfo[0]['company'])?'':$sellerInvoiceInfo[0]['company'];
		$address = empty($sellerInvoiceInfo[0]['address'])?'':$sellerInvoiceInfo[0]['address'];
		$phone = empty($sellerInvoiceInfo[0]['phone'])?'':'TEL: '.$sellerInvoiceInfo[0]['phone'].' ';
		$autographurl = empty($sellerInvoiceInfo[0]['autographurl'])?'':''.$sellerInvoiceInfo[0]['autographurl'];
		
		$html_body.=
		'<table style="border:1px solid;padding:0px 10px;margin:0px;"><tr><td>
			'.$invoiceData['init']['orderId'].'
		</td></tr></table>'.
		'<p align="center" style="font-size:20; font-weight:bold;">'.$company.'</p>'.
		'<table style="padding:0;">
			<col style="width: 30%">
			<col style="width: 40%">
			<col style="width: 30%">
			<tr>
				<td></td>
				<td align="center">'.$address.'</td>
				<td></td>
			</tr></table>'.
		'<p align="center" style="line-height:15px; margin:0px;">'.$phone.'</p>'.
		'<table>
			<col style="width: 10%">
			<col style="width: 70%">
			<col style="width: 20%">
			<tr>
				<td>ATTN:</td>
				<td>'.$invoiceData['init']['customerName'].'</td>
				<td>DATE: '.$invoiceData['other']['dd'].'</td>
			</tr>
			<tr>
				<td>ADD:</td>
				<td>'.$invoiceData['init']['address'].'</td>
			</tr>
			<tr>
				<td>TEL:</td>
				<td>'.$invoiceData['init']['phoneNo1'].'</td>
			</tr>
		</table>'.
		'<p align="center" style="font-weight:bold; line-height:15px; margin:20px 0 0 0; font-size:20; ">INVOICE</p>'.
		'<p align="center" style="margin-bottom:0px; font-weight:bold;">************************</p>';
		
		$html_body .=
		'<table class="gaoqing_items" cellspacing="0" style="border-collapse:collapse;">
			<col style="width: 20%">
    		<col style="width: 30%">
		    <col style="width: 20%">
		    <col style="width: 10%">
			<col style="width: 20%">
			<tr height="27">
				<td>ITEM#</td>
				<td>DESCRIPTION</td>
				<td>FOB TAIWAN <br> UNIT PRICE</td>
				<td>QTY <br> ORDERED</td>
				<td>TOTAL <br> AMOUNT</td>
			</tr>';
		
		foreach ($invoiceData['details'] as $row){
			$html_body .=
			'<tr height="27">
				<td>'.$row['sku'].'</td>
				<td>'.$row['name'].'</td>
				<td>'.$row['priceorig_fm'].'</td>
				<td>'.$row['qty'].'</td>
				<td>'.$row['value_fm'].'</td>
			</tr>';
		}
		
		$count = count($invoiceData['details']);
		if($count < 8)
		{
			$count = 8 - $count;
			if($count > 1)
			{
				$html_body .=
				'<tr>
					<td colspan=3 style="height:'.($count * 30).'px"></td>
					<td></td>
					<td></td>
				</tr>';
			}
		}
	
		$html_body .=
		'<tr>
			<td colspan=3 style="text-align:left;">TOTAL AMOUNT</td>
			<td>'.$invoiceData['order']['sumqty'].'</td>
			<td>'.$invoiceData['order']['sumtotal'].'</td>
		</tr>
		<tr>
			<td colspan=5 style="height:70px; line-height:25px;">SAY TOTAL '.$invoiceData['order']['EnSumtotal'].' ONLY <br> GIFT OF NO COMMERCIAL VALUE.</td>
		</tr>
		</table>
		<p style="text-align:right; margin-button:0;">For And on behalf of</p>
		<p style="text-align:right; margin-top:0;">'.$company.'</p>';
		if(!empty($autographurl))
		{
			$html_body .=
    		'<table style="padding:0;">
    			<col style="width: 80%">
    			<col style="width: 20%">
    			<tr>
    				<td></td>
    				<td style="text-align:right; border-bottom:2px solid;">
    					<img width="150" src="'.$autographurl.'" border="0">
    				</td>
    		</tr></table>';
		}
		
		$html_body .='</body>';
		return $html_body;
	}
	
	/**
	 * 当订单做某些操作时(提交发货，提交物流，确认发货完成，标记发货，移动状态等)，清除 Weird Status
	 * @param	string	$orderid	小老板order id
	 * @return	boolean
	 * @author	lzhl	2015-12-26	init
	 */
	public static function cancelOrderWeirdStatus($orderid){
		$od = OdOrder::findOne($orderid);
		if($od==null)
			return false;
		else{
			$od->weird_status = '';
			$od->save(false);
			return true;
		}
	}
	

	/**
	 * 订单添加备注,返回修改后的备注内容
	 * @param	$module
	 * @param	$order_id
	 * @param	$desc
	 * @return	new desc
	 * @author luzhiliang	2016-01-09
	 */
	public static function addOrderDesc($module,$order_id,$desc){
		$order_id = trim($order_id);
		$desc = trim($desc);
		if(!empty($order_id) && !empty($desc)){
			$order = OdOrder::findOne($order_id);
			if (!empty($order)){
				$olddesc = $order->desc;
				$dataTime=TimeUtil::getNow();
				$capture = \Yii::$app->user->identity->getFullName();
				$order->desc =$dataTime."@".$capture.":<br>".$desc."<br>". $olddesc;
				if($order->save()){
					OperationLogHelper::log($module,$order->order_id,'添加备注','添加备注: ('.$olddesc.'+'.$desc .')',$capture);
					$ret_array = array (
							'result' => true,
							'message' => '修改成功',
							'desc'=>$order->desc,
					);
				}else{
					$ret_array = array (
							'result' => false,
							'message' => '修改失败，'.print_r($order->getErrors()),
							'desc'=>'',
					);
				}
			}else{
				$ret_array = array (
						'result' => false,
						'message' => '修改失败，找不到该订单',
						'desc'=>'',
				);
			}
		}else{
			$ret_array = array (
					'result' => false,
					'message' => '修改失败，缺少订单id或添加的备注为空',
					'desc'=>'',
			);
		}
		return $ret_array;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 检查订单是否虚假发货， 是否将其保存为虚假发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 				当前状态不 是已发货的情况下， 都标记为虚假发货
	 +---------------------------------------------------------------------------------------------
	 * @param     $order_id				订单ID 或者 订单model对象   string(或者object)
	 +---------------------------------------------------------------------------------------------
	 * @return						['success'=>boolean ,'message'=>string 执行结果]
	 *
	 * @invoking					OrderHelper::MarkOnlyPlatformShipped();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function MarkOnlyPlatformShipped(&$order_id){
		if (empty($order_id)) return ['success'=>false , 'message'=>'E2.参数错误！'];
		
		//check 
		if(!($order_id instanceof OdOrder)){
			if(is_array($order_id) && $order_id['order_id']){
				$model=OdOrder::model()->where(['order_id'=>$order_id['order_id']])->One();
				if($model->isNewRecord){
					return ['success'=>false , 'message'=>'E1.订单ID：'.$order_id.'为无效订单'];
				}
			}elseif (is_string($order_id) || is_numeric($order_id)){
				$model = OdOrder::find()->where(['order_id'=>$order_id])->one();
			}else{
				return ['success'=>false , 'message'=>'E2.参数错误'];;
			}
		}else{
			$model = $order_id;
		}
		
		if (!empty($model)){
			//order status 不为 已完成 都是要打上虚假发货的标签
			if (! in_array($model->order_status , [OdOrder::STATUS_SHIPPED]) ){
				return OrderTagHelper::setOrderSysTag($model->order_id, 'sys_unshipped_tag');
				
			}else{
				return ['success'=>true , 'message'=>''];
			}
		}else{
			// order id 无效
			return ['success'=>false , 'message'=>'E2.订单ID：'.$order_id.'为无效订单'];
		}
		
	}//end of MarkOnlyPlatformShipped
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 取消一张订单功能 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 				 1.发货中的订单必须暂停发货才能取消
	 * 				 2.已经取消的订单请不要重复取消   
	 +---------------------------------------------------------------------------------------------
	 * @param     $order_id				订单ID 或者 订单model对象   string(或者object)
	 +---------------------------------------------------------------------------------------------
	 * @return						['success'=>boolean ,'message'=>string 执行结果]
	 *
	 * @invoking					OrderHelper::CancelOneOrder($order_id);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/07				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function CancelOneOrder($order_id , $action="取消订单" , $module = 'order'){
		if (empty($order_id)) return ['success'=>false , 'message'=>'E1.参数错误！'];
		$list_clear_platform = []; //需要清除redisear_platform
		//validate param 
		if(!($order_id instanceof OdOrder)){
			if(is_array($order_id) && $order_id['order_id']){
				$model=OdOrder::model()->where(['order_id'=>$order_id['order_id']])->getOne();
				if($model->isNewRecord){
					return ['success'=>false , 'message'=>'E2.订单ID：'.$order_id.'为无效订单'];
				}
			}elseif (is_string($order_id) || is_numeric($order_id)){
				$model = OdOrder::find()->where(['order_id'=>$order_id])->one();
			}else{
				return ['success'=>false , 'message'=>'E3.参数错误'];;
			}
		}else{
			$model = $order_id;
		}
		
		if (!empty($model)){
			//1.检查 订单状态 不能为发货中 ， 发货中的订单必须暂停发货才能取消     2.已经取消的订单请不要重复取消 
			if (! in_array($model->order_status , [OdOrder::STATUS_CANCEL,OdOrder::STATUS_WAITSEND])){
				$newAttr = [
					'order_status'=>OdOrder::STATUS_CANCEL  , 
					'sync_shipped_status'=>'N',
				];
				$fullName = \Yii::$app->user->identity->getFullName();
				
				$rt = OrderUpdateHelper::updateOrder($model, $newAttr,false ,$fullName  , $action , $module );
				$rt['success'] = $rt['ack'];
				
				//增加清除 平台redis
				if (!in_array($model->order_source, $list_clear_platform)){
					$list_clear_platform[] = $model->order_source;
				}
				
				//left menu 清除redis
				if (!empty($list_clear_platform)){
					OrderHelper::clearLeftMenuCache($list_clear_platform);
				}
				
				return ['success'=>true ,'message'=>''];
				/*
				$rt = OdOrder::updateAll(['order_status'=>OdOrder::STATUS_CANCEL], '  order_id = "'.$model->order_id.'" and order_status not in ("'.OdOrder::STATUS_CANCEL.'","'.OdOrder::STATUS_WAITSEND.'") ' );
				
				if (!empty($rt)){
					//告知dashboard面板，统计数量改变了
					OrderApiHelper::adjustStatusCount($model->order_source, $model->selleruserid, OdOrder::STATUS_CANCEL, $model->order_status,$model->order_id);
					
					$msg = '';
					//订单虚拟发货状态刷新
					//N 为无须执行
					$tmpRt = OrderBackgroundHelper::setOrderSyncShippedStatus($model, 'N');
					if ($tmpRt['ack']==false){
						$msg = $tmpRt['message'];
					}
					
					return ['success'=>true ,'message'=>$msg];
					
					
				}else{
					// order id 的订单状态不为取消
					return ['success'=>false , 'message'=>'E4.订单ID：'.$order_id.' 订单状态已经被修改，请再次确定后再取消！'];
				}
				*/
			}else{
				// order id 的订单状态不为取消
				return ['success'=>false , 'message'=>'E5.订单ID：'.$order_id.' 订单状态是'.Odorder::$status[$model->order_status].'，不能取消！'];
			}
		}else{
			// order id 无效
			return ['success'=>false , 'message'=>'E6.订单ID：'.$order_id.'为无效订单'];
		}
	}//end of CancelOneOrder
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 废弃订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 				 1.发货中的订单必须暂停发货才能废弃
	 * 				 2.已经废弃的订单请不要重复废弃
	 +---------------------------------------------------------------------------------------------
	 * @param     $order_id				订单ID 或者 订单model对象   string(或者object)
	 +---------------------------------------------------------------------------------------------
	 * @return						['success'=>boolean ,'message'=>string 执行结果]
	 *
	 * @invoking					OrderHelper::CancelOneOrder($order_id);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/07				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function AbandonOrder($order_id){
		if (empty($order_id)) return ['success'=>false , 'message'=>'E1.参数错误！'];
		
		//validate param
		if(!($order_id instanceof OdOrder)){
			if(is_array($order_id) && $order_id['order_id']){
				$model=OdOrder::model()->where(['order_id'=>$order_id['order_id']])->getOne();
				if($model->isNewRecord){
					return ['success'=>false , 'message'=>'E2.订单ID：'.$order_id.'为无效订单'];
				}
			}elseif (is_string($order_id) || is_numeric($order_id)){
				$model = OdOrder::find()->where(['order_id'=>$order_id])->one();
			}else{
				return ['success'=>false , 'message'=>'E3.参数错误'];;
			}
		}else{
			$model = $order_id;
		}
		
		if (!empty($model)){
			//1.检查 订单状态 不能为发货中 ， 发货中的订单必须暂停发货才能取消     2.已经取消的订单请不要重复取消
			if (! in_array($model->order_status , [OdOrder::STATUS_ABANDONED,OdOrder::STATUS_WAITSEND])){
				$rt = OdOrder::updateAll(['order_status'=>OdOrder::STATUS_CANCEL], '  order_id = "'.$model->order_id.'" and order_status not in ("'.OdOrder::STATUS_ABANDONED.'","'.OdOrder::STATUS_WAITSEND.'") ' );
		
				if (!empty($rt)){
					//告知dashboard面板，统计数量改变了
					OrderApiHelper::adjustStatusCount($model->order_source,$model->selleruserid, OdOrder::STATUS_CANCEL, $model->order_status,$model->order_id);
					RedisHelper::delOrderCache( \Yii::$app->subdb->getCurrentPuid() , strtolower($model->order_source) );
					return ['success'=>true ,''];
				}else{
					// order id 的订单状态不为取消
					return ['success'=>false , 'message'=>'E4.订单ID：'.$order_id.' 订单状态已经被修改，请再次确定后再取消！'];
				}
		
			}else{
				// order id 的订单状态不为取消
				return ['success'=>false , 'message'=>'E5.订单ID：'.$order_id.' 订单状态是'.Odorder::$status[$model->order_status].'，不能取消！'];
			}
		}else{
			// order id 无效
			return ['success'=>false , 'message'=>'E6.订单ID：'.$order_id.'为无效订单'];
		}
	}//end of AbandonOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置重新发货 的标志
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 				 1.Suspend shipment 暂停发货    code : suspend_shipment
	 * 				 2.Out of Stock 缺货   code : out_of_stock
	 * 				 3.After shipment 已出库  code : after_shipment
	 * 				 4.Cancel order 已取消 code : cancel_order
	 * 				 5.Abandoned order 废弃订单回收  code : abandoned_order
	 +---------------------------------------------------------------------------------------------
	 * @param     $order_id				订单ID 数组
	 +---------------------------------------------------------------------------------------------
	 * @return						['success'=>boolean ,'message'=>string 执行结果]
	 *
	 * @invoking					OrderHelper::setReOrderType($order_id,$type);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setReOrderType($order_id,$type){
		if (empty($order_id)|| empty($type) ) return ['success'=>false , 'message'=>'E1.参数错误！'];
		
		//验证 type 是否有效
		if (! array_key_exists($type, OdOrder::$reorderType)) return ['success'=>false , 'message'=>'E2.'.$type.'不是系统识别的种类'];
		
		//validate param 
		if(!($order_id instanceof OdOrder)){
			if(is_array($order_id) && $order_id['order_id']){
				$model=OdOrder::model()->where(['order_id'=>$order_id['order_id']])->getOne();
				if($model->isNewRecord){
					return ['success'=>false , 'message'=>'E2.订单ID：'.$order_id.'为无效订单'];
				}
			}elseif (is_string($order_id) || is_numeric($order_id)){
				$model = OdOrder::find()->where(['order_id'=>$order_id])->one();
			}else{
				return ['success'=>false , 'message'=>'E3.参数错误'];;
			}
		}else{
			$model = $order_id;
		}
		
		if (!empty($model)){
			$model->reorder_type = $type;
			if (! $model->save()){
				return ['success'=>false , 'message'=>'E4 保存失败：'.json_encode($model->errors)];
			}else{
				return ['success'=>true , 'message'=>''];
			}
		}else{
			// order id 无效
			return ['success'=>false , 'message'=>'E5.订单ID：'.$order_id.'为无效订单'];
		}
	}//end of CancelOrders
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 生成 删除后刷新订单用的同步 队列
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param na
	 +---------------------------------------------------------------------------------------------
	 * @return				na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/10				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function refreshOrderAfterDelete($uid ,  $order_id , $platform ){
		// 检查 参数
		if (empty($uid) ){
			return ['success'=>false , 'message'=>'找不到用户信息！'];
		}
		
		if ( empty($order_id)){
			return ['success'=>false , 'message'=>'订单ID 不能为空！'];
		}
		
		if (empty($platform)){
			return ['success'=>false , 'message'=>'平台参数不能为空！'];
		}
		
		//根据不同的参数， 作出不同的处理
		switch ($platform){
			case 'aliexpress':
				
				break;
		}
	}//end of refreshOrderAfterDelete
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 生成 删除后刷新订单用的同步 队列
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 * 			$uid					用户uid
	 * 			$order_id				订单编号(小老板 的订单号)
	 * 			$platform				平台
	 +---------------------------------------------------------------------------------------------
	 * @return				na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/10				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function requestRefreshOrderQueue($uid ,  $order_id , $platform  ){
		if (empty($uid)) return ['success'=>false , 'message'=>'uid 不能为空'];
		if (empty($platform)) return ['success'=>false , 'message'=>'平台  不能为空'];
		if (empty($order_id)) return ['success'=>false , 'message'=>'order_id 不能为空'];
	
	
		$orderModel = OdOrder::findOne(['order_source'=>$platform , 'order_id'=> $order_id]);
		
		if (empty($orderModel)) return ['success'=>false , 'message'=>$order_id.'已经删除了！'];
		
		$que = QueueManualOrderSync::find()->where(['puid'=>$uid , 'sellerloginid'=>$orderModel->selleruserid , 'order_id'=>$orderModel->order_source_order_id])->andWhere(['in','status',['S','P']])->one();
		//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync number =".count($que )."!","file");
		
		try {
			if (empty($que)){
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 1   !","file");
				$model = new QueueManualOrderSync();
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 2   !","file");
				$model->puid = $uid;
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 3   (".$model->puid.")!","file");
				$model->sellerloginid = $orderModel->selleruserid  ;
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 4   (".$model->sellerloginid.")!","file");
				$model->order_id = $orderModel->order_source_order_id;
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 5   (".$model->order_id.")!","file");
				$model->status = 'P';// pending
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 6   (".$model->status.")!","file");
				$model->create_time = date('Y-m-d H:i:s');
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 7   (".$model->create_time.")!","file");
				$model->update_time = date('Y-m-d H:i:s');
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 8   (".$model->update_time.")!","file");
				$model->priority = 3 ;
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 9  (".$model->priority.") !","file");
				$model->platform = $platform;
				//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync set value 10  (".$model->platform.") !","file");
				if (! $model->save()){
					//\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync save failure ".print_r($model,true)." !","file");
					//\Yii::error((__FUNCTION__)." khtest11 : ".print_r($model,true).'！',"file");//testkh
					return ['success'=>false , 'message'=>empty($model->errors)?"":json_encode($model->errors)];
				}else{
					\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync save ok !","file");
					return ['success'=>true , 'message'=>''];
				}
			
			}else{
				return ['success'=>false , 'message'=>'请求太频密'];
			}
		} catch (\Exception $e) {
			
			\Yii::info("\n".(__FUNCTION__)." $uid QueueManualOrderSync line ".$e->getLine()."E: ".print_r($e->getMessage(),true)." !","file");
			return ['success'=>false ,'message'=>$e->getMessage()];
		}
		
	
	}//end of requestRefreshOrderQueue
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计Left menu 上的order 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform					平台
	 * @param $params					自定义查询条件  ['selleruserid'=>['seller123'] , .....]
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMenuStatisticData($platform , $params= []){
		
		$OrderQuery = OdOrder::find();
		if (!empty($platform)){
			$OrderQuery->andWhere(['order_source'=>$platform]);
		}
		if (!empty($params)){
			$OrderQuery->andWhere($params);
			
		}

		// for 过滤合并订单 
		$OrderQuery->andWhere(['order_relation'=>['normal','sm','ss','fs']]);
		
		$OrderQuery->andWhere(['isshow'=>'Y']);
		
		$QueryConditionList = [
			//订单数量统计
			OdOrder::STATUS_NOPAY=>['order_status'=>OdOrder::STATUS_NOPAY ],
			OdOrder::STATUS_PAY=>['order_status'=>OdOrder::STATUS_PAY ],
			OdOrder::STATUS_WAITSEND=>['order_status'=>OdOrder::STATUS_WAITSEND ],
			OdOrder::STATUS_SUSPEND=>['order_status'=>OdOrder::STATUS_SUSPEND ],
			OdOrder::STATUS_OUTOFSTOCK=>['order_status'=>OdOrder::STATUS_OUTOFSTOCK ],
			OdOrder::STATUS_REFUND=>['order_status'=>OdOrder::STATUS_REFUND ],
			OdOrder::STATUS_RETURN=>['order_status'=>OdOrder::STATUS_RETURN ],
			OdOrder::STATUS_ABANDONED=>['order_status'=>OdOrder::STATUS_ABANDONED ],
			
			'all'=>[],
			'guaqi'=>['is_manual_order'=>'1' ],
			'split'=>['order_relation'=>['fs','ss']],
			//已付款订单类型
			OdOrder::PAY_PENDING=>['order_status'=>OdOrder::STATUS_PAY ,'pay_order_type'=>[OdOrder::PAY_PENDING,null]],
			OdOrder::PAY_REORDER=>['order_status'=>OdOrder::STATUS_PAY ,'pay_order_type'=>OdOrder::PAY_REORDER],
			OdOrder::PAY_EXCEPTION=>['order_status'=>OdOrder::STATUS_PAY ,'pay_order_type'=>OdOrder::PAY_EXCEPTION],
			OdOrder::PAY_CAN_SHIP=>['order_status'=>OdOrder::STATUS_PAY ,'pay_order_type'=>OdOrder::PAY_CAN_SHIP],
			OdOrder::PAY_MERGED=>['order_status'=>OdOrder::STATUS_PAY , 'order_relation'=>"sm"],
			
			OdOrder::STATUS_SHIPPED=>['order_status'=>OdOrder::STATUS_SHIPPED],
			OdOrder::STATUS_SHIPPING=>['order_status'=>OdOrder::STATUS_SHIPPING],
			OdOrder::STATUS_CANCEL=>['order_status'=>OdOrder::STATUS_CANCEL],
			 
			//异常订单
			OdOrder::EXCEP_WAITSEND=>['order_status'=>OdOrder::STATUS_PAY  , 'exception_status'=>OdOrder::EXCEP_WAITSEND],
			OdOrder::EXCEP_NODEFAULTWAREHOUSE=>['order_status'=>OdOrder::STATUS_PAY , 'exception_status'=>OdOrder::EXCEP_NODEFAULTWAREHOUSE],
			OdOrder::EXCEP_HASNOSHIPMETHOD=>['order_status'=>OdOrder::STATUS_PAY , 'exception_status'=>OdOrder::EXCEP_HASNOSHIPMETHOD],
			OdOrder::EXCEP_PAYPALWRONG=>['order_status'=>OdOrder::STATUS_PAY , 'exception_status'=>OdOrder::EXCEP_PAYPALWRONG],
			OdOrder::EXCEP_SKUNOTMATCH=>['order_status'=>OdOrder::STATUS_PAY , 'exception_status'=>OdOrder::EXCEP_SKUNOTMATCH],
			OdOrder::EXCEP_NOSTOCK=>['order_status'=>OdOrder::STATUS_PAY, 'exception_status'=>OdOrder::EXCEP_NOSTOCK ],
			OdOrder::EXCEP_WAITMERGE=>['order_status'=>OdOrder::STATUS_PAY , 'exception_status'=>OdOrder::EXCEP_WAITMERGE, 'order_relation'=>["sm",'normal']],
		];
		$counter = [];
		
		// dzt20190419 idx_all_list是给部分多订单客户打开订单首页添加的index
		$indexArr = \Yii::$app->subdb->createCommand("show index from `od_order_v2`")->queryAll();
		$indexArrMap = Helper_Array::toHashmap($indexArr, "Key_name");
		
		$puid = \Yii::$app->subdb->getCurrentPuid();
		foreach($QueryConditionList as $key =>$QueryCondition){
			$cloneQuery = clone $OrderQuery;
			//待合并订单剔除风控中
			if ($key == OdOrder::EXCEP_WAITMERGE){
				$cloneQuery->andWhere($QueryCondition)->andWhere("  IFNULL( order_source_status,  '' ) <> 'RISK_CONTROL' ");
				//echo "<br>".$cloneQuery->createCommand()->getRawSql();
			}
			else if ($key == 'split'){
				$cloneQuery->andWhere($QueryCondition)->andWhere(['order_status'=>OdOrder::STATUS_PAY]);
			}
			else{
				$cloneQuery->andWhere($QueryCondition)->andWhere(['order_relation'=>['normal','sm','fs','ss']]);
			}
			
			if (!empty($indexArrMap['idx_all_list'])){// 分页耗时比获取数据要多
			    $t1 = \eagle\modules\util\helpers\TimeUtil::getCurrentTimestampMS();
			    $tmpCountSql = $cloneQuery->createCommand()->getRawSql();// 只是要count 去掉order by
			    $tmpCountSql = str_ireplace("SELECT * FROM", "SELECT count(*) FROM ", $tmpCountSql);
			    $tmpCountSql = str_ireplace("where", " use INDEX (`idx_all_list`) where ", $tmpCountSql);
			    $subdbConn=\yii::$app->subdb;
			    $pageCount = $subdbConn->createCommand($tmpCountSql)->queryScalar();
			    $counter[$key] = $pageCount;
			    $t2 = \eagle\modules\util\helpers\TimeUtil::getCurrentTimestampMS();
// 			    \Yii::info('class:'.__CLASS__.',function:'.__FUNCTION__.", mark pageCount:$pageCount,t1=".($t2 - $t1), "file");

			}else{
			    $counter[$key] = $cloneQuery->count();
			}
			
// 			echo "<br>".$cloneQuery->createCommand()->getRawSql();
		}
		return $counter;
		
	}//end of getMenuStatisticData
	
	public function getTopMenuStatisticData($platform , $params= []){
		$OrderQuery = OdOrder::find();
		if (!empty($platform)){
			$OrderQuery->andWhere(['order_source'=>$platform]);
		}
		if (!empty($params)){
			$OrderQuery->andWhere($params);
				
		}
		
		$QueryConditionList = [
		//订单数量统计
		
		OdOrder::STATUS_PAY=>['order_status'=>OdOrder::STATUS_PAY ],
		
		//已付款订单类型
		OdOrder::PAY_PENDING=>['order_status'=>OdOrder::STATUS_PAY ,'pay_order_type'=>[OdOrder::PAY_PENDING,null]],
		OdOrder::PAY_REORDER=>['order_status'=>OdOrder::STATUS_PAY ,'pay_order_type'=>OdOrder::PAY_REORDER],
		OdOrder::PAY_EXCEPTION=>['order_status'=>OdOrder::STATUS_PAY ,'pay_order_type'=>OdOrder::PAY_EXCEPTION],
		OdOrder::PAY_CAN_SHIP=>['order_status'=>OdOrder::STATUS_PAY ,'pay_order_type'=>OdOrder::PAY_CAN_SHIP],
		];
		$counter = [];
		return $counter;
	}//end of function getTopMenuStatisticData
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据订单的流程生成 操作列表数组
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 	
	 * 			$code					当前操作的订单流程关键值
	 * 			$type					s = single 单独操作 ， b = batch 批量操作
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getCurrentOperationList($code , $type="s"){
		$baseOperationList = [];
		switch ($code){
			case OdOrder::STATUS_NOPAY:
				//未付款订单处理
				$operationList = [
					'signpayed'=>'确认到款',
					'cancelorder'=>'取消订单',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				
				break;
				
			/****************************** 已付款订单处理  start *********************************/
			case OdOrder::STATUS_PAY:
				//待检测
				$operationList = [
				'checkorder'=>'检测订单',
				'suspendDelivery'=>'暂停发货',
				//'refundOrder'=>'退款',
				'signshipped'=>'虚拟发货(标记发货)',
				'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				'delete_manual_order'=>'删除手工订单',
				'split_order'=>'拆分订单',
				'split_order_cancel'=>'取消拆分订单',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
				
			case 'reo':
				//重新发货
				$operationList = [
				'checkorder'=>'检测订单',
				'suspendDelivery'=>'暂停发货',
				//'refundOrder'=>'退款',
				'signshipped'=>'虚拟发货(标记发货)',
				//'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
				
			case OdOrder::EXCEP_SKUNOTMATCH:
				//SKU不存在
				$operationList = [
				'generateProduct'=>'生成商品',
				'suspendDelivery'=>'暂停发货',
				//'refundOrder'=>'退款',
				//'signshipped'=>'标记发货',
				//'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				'checkorder'=>'检测订单',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
			
			case OdOrder::EXCEP_NODEFAULTWAREHOUSE:
				//未分配仓库
				$operationList = [
				//'setWarehouse'=>'分配仓库',
				'suspendDelivery'=>'暂停发货',
				//'refundOrder'=>'退款',
				//'signshipped'=>'标记发货',
				//'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				'checkorder'=>'检测订单',
				'changeWHSM'=>'修改仓库和运输服务',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
			
			case OdOrder::EXCEP_NOSTOCK:
				//库存不足
				$operationList = [
				'stockManage'=>'库存处理',
				//'transferStorage'=>'转仓处理',
				'outOfStock'=>'标记缺货',
				'suspendDelivery'=>'暂停发货',
				//'refundOrder'=>'退款',
				//'signshipped'=>'标记发货',
				//'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				'checkorder'=>'检测订单',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
			
			case OdOrder::EXCEP_HASNOSHIPMETHOD:
				//未分配运输服务
				$operationList = [
				//'setShipmethod'=>'分配运输服务',
				'suspendDelivery'=>'暂停发货',
				//'refundOrder'=>'退款',
				//'signshipped'=>'标记发货',
				//'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				'checkorder'=>'检测订单',
				'changeWHSM'=>'修改仓库和运输服务',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
				
			case OdOrder::EXCEP_WAITMERGE:
				//待合并
				$operationList = [
				'mergeorder'=>'合并订单',
				'skipMerge'=>'不合并发货',
				'suspendDelivery'=>'暂停发货',
				//'refundOrder'=>'退款',
				//'signshipped'=>'标记发货',
				//'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				'checkorder'=>'检测订单',
				
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
			
			case OdOrder::EXCEP_WAITSEND:
				//可发货
				$operationList = [
				'signwaitsend'=>'提交发货',
				'suspendDelivery'=>'暂停发货',
				//'refundOrder'=>'退款',
				//'signshipped'=>'标记发货',
				//'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				'changeWHSM'=>'修改仓库和运输服务',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
			
		/****************************** 已付款订单处理  end *********************************/
		/****************************** 发货处理  start *********************************/
			case OdOrder::STATUS_WAITSEND:
				//发货处理
				$operationList = [
				//'signshipped'=>'标记发货',
				'suspendDelivery'=>'暂停发货',
				'outOfStock'=>'标记缺货',
				//'refundOrder'=>'退款',
				//'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				'changeWHSM'=>'修改仓库和运输服务',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
		/****************************** 发货处理    end  *********************************/
		
		/****************************** 等待买家收货  start *********************************/
				//去除这个流程
			/* case OdOrder::STATUS_SHIPPING:
				//等待买家收货
				$operationList = [
				'reorder'=>'已出库订单补发',
				'suspendDelivery'=>'暂停发货',
				'returnGoods'=>'退货',
				'refundOrder'=>'退款',
				'givefeedback'=>'给买家好评',
				//'signshipped'=>'标记发货',
				
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break; */
			
		/****************************** 等待买家收货    end  *********************************/
			
		/****************************** 已完成   start  *********************************/
			case OdOrder::STATUS_SHIPPED:
				//已完成
				$operationList = [
				'reorder'=>'已出库订单补发',
				//'returnGoods'=>'退货',
				//'refundOrder'=>'退款',
				//'givefeedback'=>'给买家好评',
				'signshipped'=>'虚拟发货(标记发货)',
				'ExternalDoprint'=>'打印面单',
				'cancelorder'=>'取消订单', //20170308  文与增强要求增加
				];
				
				if (isset(self::$BaseOperationList['signcomplete'])) {
					unset(self::$BaseOperationList['signcomplete']);
				}
				
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
		/****************************** 已完成    end  *********************************/
		/****************************** 已取消    start  *********************************/
			case OdOrder::STATUS_CANCEL:
				//已取消
				$operationList = [
				'reorder'=>'重新发货',
				'delete_manual_order'=>'删除手工订单',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
		/****************************** 已取消    end  *********************************/
		/****************************** 暂停发货    start  *********************************/
			case OdOrder::STATUS_SUSPEND:
				//暂停发货
				$operationList = [
				'reorder'=>'重新发货(返回已付款)',
				//'refundOrder'=>'退款',
				//'givefeedback'=>'给买家好评',
				'cancelorder'=>'取消订单',
				'signshipped'=>'虚拟发货(标记发货)',
				'recovery' => '恢复发货(返回原状态)',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
		/****************************** 暂停发货    end  *********************************/
		/****************************** 缺货订单    start  *********************************/
			case OdOrder::STATUS_OUTOFSTOCK:
				//缺货订单 
				$operationList = [
				//'createPurchase'=>'生成采购单',
				//'inStock'=>'采购到货',
				'reorder'=>'重新发货(返回已付款)',
				//'refundOrder'=>'退款',
				'cancelorder'=>'取消订单',
				//'givefeedback'=>'给买家好评',
				'signshipped'=>'虚拟发货(标记发货)',
				'recovery' => '恢复发货(返回原状态)',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
		/****************************** 缺货订单    end  *********************************/
		
		/****************************** 退货订单    start  *********************************/
			case OdOrder::STATUS_RETURN:
				//退货订单 
				$operationList = [
				'createReturn'=>'生成退货单',
				//'refundOrder'=>'退款',
				'cancelorder'=>'取消订单',
				'completeReturn'=>'退货完成',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
				
		/****************************** 退货订单    end  *********************************/
		/****************************** 退款订单    start  *********************************/
			case OdOrder::STATUS_REFUND:
				//退款订单
				$operationList = [
				'createRefund'=>'生成退款单',
				'returnGoods'=>'退货',
				'cancelorder'=>'取消订单',
				'completeReturn'=>'退货完成',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
		/****************************** 退款订单    end  *********************************/
		/****************************** 废弃订单    start  *********************************/
			case OdOrder::STATUS_ABANDONED:
				//退款订单
				$operationList = [
				'abandonOrder'=>'彻底删除',
				'recoveryOrder'=>'回收',
				];
				if ($type == 's'){
					return self::$BaseSingleOperationList +  self::$BaseOperationList+ $operationList;
				}else{
					return self::$BaseBatchOperationList +  self::$BaseOperationList+ $operationList;
				}
				break;
		/****************************** 废弃订单    end  *********************************/
			default:
				$tmp_BaseOperationList = self::$BaseOperationList;
				
				if($code == ''){
					$tmp_is_show = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowMenuAllOtherOperation();
					if($tmp_is_show == false){
						unset($tmp_BaseOperationList['signcomplete']);
						unset($tmp_BaseOperationList['setSyncShipComplete']);
					}
				}
				
				if ($type == 's'){
					return self::$BaseSingleOperationList +  $tmp_BaseOperationList;
				}else{
					return self::$BaseBatchOperationList +  $tmp_BaseOperationList;
				}
		}
	}//end of getCurrentOperationList
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单暂停发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$orderIdList			order id 的数组
	 +---------------------------------------------------------------------------------------------
	 * @return				na
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 	      已付款:
	 * 			1.将“已付款”状态改成“暂停发货”，此处暂停发货不需要释放占用库存
	 * 
	 *    发货中 : 
	 *    		1.将“发货中”状态改成“暂停发货”。此处当订单已经分配了库存，那么暂停的时候需要将占用的库存释放掉。
	 *    		2.如果已经生成了拣货单，那么需要将拣货单中的这个订单删除。
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function suspendOrders($orderIdList ,$module='order',$action='暂停发货'){
		$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
		$rtn['success'] = true;
		$rtn['message'] = "";
		$rtn['code'] = "";
		//var_dump($OrderList);die;
		$list_clear_platform = []; //需要清除redis
		$edit_log = array();
		foreach($OrderList as $oneOrder){
			/*************保存旧状态信息 start  lrq20170926*******************/
			$old_status_info = array();
			$old_status_info['order_status'] = $oneOrder->order_status;
			//发货
			$old_status_info['delivery_status'] = $oneOrder->delivery_status;
			$old_status_info['delivery_id'] = $oneOrder->delivery_id;
			$old_status_info['distribution_inventory_status'] = $oneOrder->distribution_inventory_status;
			//拣货
			$old_status_info['is_print_picking'] = $oneOrder->is_print_picking;
			$old_status_info['print_picking_operator'] = $oneOrder->print_picking_operator;
			$old_status_info['print_picking_time'] = $oneOrder->print_picking_time;
			//配货
			$old_status_info['is_print_distribution'] = $oneOrder->is_print_distribution;
			$old_status_info['print_distribution_operator'] = $oneOrder->print_distribution_operator;
			$old_status_info['print_distribution_time'] = $oneOrder->print_distribution_time;
			//物流
			$old_status_info['is_print_carrier'] = $oneOrder->is_print_carrier;
			$old_status_info['print_carrier_operator'] = $oneOrder->print_carrier_operator;
			$old_status_info['printtime'] = $oneOrder->printtime;
			
			try {
				if (!empty($oneOrder->addi_info)){
					$addInfo = json_decode($oneOrder->addi_info,true);
				}else{
					$addInfo = [];
				}
				
				$addInfo['old_status_info'] = $old_status_info;
				$oneOrder->addi_info = json_encode($addInfo);
				if(!$oneOrder->save()){
					$rtn['success'] = false;
					$rtn['message'] = '暂停发货失败！保存状态信息失败！';
				}
				
			} catch (\Exception $e) {
				$rtn['success'] = false;
				$rtn['message'] = '暂停发货失败！'.$e->getMessage();
			}
			/*************保存旧状态信息 end*******************/
			
			//1.取消成功的预约
			if ($rtn['success']){
				$r1 = InventoryApiHelper::OrderProductReserveCancel($oneOrder->order_id,$module,$action);
				if ($r1['success'] == false){
					$rtn = $r1;
				}
			}
			
			//2.删除拣货单
			if ($rtn['success']){
				$r2 = DeliveryApiHelper::cancelOrderDeliveryMapping($oneOrder->order_id,$module,$action);
				if ($r2['success'] == false){
					$rtn = $r2;
				}
			}
			
			if ($rtn['success']){
				
				
				$data = [
				'order_status'=>OdOrder::STATUS_SUSPEND,
				//发货
				'delivery_status'=>'0',
				'delivery_id'=>'0',
				'distribution_inventory_status'=>'0',
				//拣货
				'is_print_picking'=>'0',
				'print_picking_operator'=>'0',
				'print_picking_time'=>'0',
				//配货
				'is_print_distribution'=>'0',
				'print_distribution_operator'=>'0',
				'print_distribution_time'=>'0',
					
				//物流
				'is_print_carrier'=>'0',
				'print_carrier_operator'=>'0',
				'printtime'=>'0',
				];
				$fullName = \Yii::$app->user->identity->getFullName();
				$updateRT = OrderUpdateHelper::updateOrder($oneOrder, $data,false,$fullName , $action , $module);
				
				if ($updateRT['ack']){
					//增加清除 平台redis
					if (!in_array($oneOrder->order_source, $list_clear_platform)){
						$list_clear_platform[] = $oneOrder->order_source;
					}
					
					$edit_log[] = $oneOrder->order_id;
					
				}else{
					$rtn['message'] .= $oneOrder->order_id."暂停失败！";
				}
				if (!empty($updateRT['message'])){
					\Yii::error((__FUNCTION__)." order_id=".$oneOrder->order_id." order_source_order_id=".$oneOrder->order_source_order_id."  ".$updateRT['message'],"file");
					
				}
				
				//3修改订单状态
				//  已付款 与发货 的 通用操作
				//兼容oms2.1
				/*
				if (self::isProcess21($oneOrder->order_source)){
					$oldOrderStatus = $oneOrder->order_status;
					$oneOrder->order_status = OdOrder::STATUS_SUSPEND;
				}else{
					$oldOrderStatus = $oneOrder->order_status;
					$oneOrder->order_status = OdOrder::STATUS_PAY;
					$oneOrder->is_manual_order = 1;
				}
				
				
				$oneOrder->delivery_status = 0;
				$oneOrder->delivery_id = 0;
				$oneOrder->distribution_inventory_status = 0;
				//拣货
				$oneOrder->is_print_picking = 0;
				$oneOrder->print_picking_operator = 0;
				$oneOrder->print_picking_time = 0;
				//配货
				$oneOrder->is_print_distribution = 0;
				$oneOrder->print_distribution_operator = 0;
				$oneOrder->print_distribution_time = 0;
				//物流
				$oneOrder->is_print_carrier = 0;
				$oneOrder->print_carrier_operator = 0;
				$oneOrder->printtime = 0;
				*/
				/*  重置下列数据
				 * delivery_status  0
				* delivery_id
				* distribution_inventory_status
				* is_print_picking
				* print_picking_operator
				* print_picking_time
				* is_print_distribution
				* print_distribution_operator
				* print_distribution_time
				* is_print_carrier
				* print_carrier_operator
				* printtime
				* */
				
				
				
				/*
				if ($oneOrder->save()){
					if (self::isProcess21($oneOrder->order_source)){
						//订单操作日志
						OperationLogHelper::log($module,$oneOrder->order_id,$action,'修改订单状态:'.OdOrder::$status[$oldOrderStatus].'至'.OdOrder::$status[$oneOrder->order_status],\Yii::$app->user->identity->getFullName());
					}else{
						//订单操作日志
						OperationLogHelper::log($module,$oneOrder->order_id,$action,'修改订单状态:'.OdOrder::$status[$oldOrderStatus].'至'.OdOrder::$status[$oneOrder->order_status],\Yii::$app->user->identity->getFullName());
							
						OperationLogHelper::log($module,$oneOrder->order_id,$action,'挂起订单',\Yii::$app->user->identity->getFullName());
					}
					
					//告知dashboard面板，统计数量改变了
					OrderApiHelper::adjustStatusCount($oneOrder->order_source, $oneOrder->selleruserid, $oneOrder->order_status, $oldOrderStatus,$oneOrder->order_id);
						RedisHelper::delOrderCache( \Yii::$app->subdb->getCurrentPuid() , strtolower($oneOrder->order_source) );
					//增加清除 平台redis
					if (!in_array($oneOrder->order_source, $list_clear_platform)){
						$list_clear_platform[] = $oneOrder->order_source;
					}
						
				}else{
					//保存失败
					\Yii::info((__FUNCTION__)." ".json_encode($oneOrder->errors),"file");
					$msg .= $oneOrder->order_id."暂停失败！";
					$rtn['message'] = $msg;
				}
				*/
			}
			
		}//end of each order 
		
		if(!empty($edit_log)){
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "暂停发货, 订单号: ".implode(', ', $edit_log));
		}
		
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
		}
		return $rtn;
	}//end of suspendOrders
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单 标记缺货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$orderIdList			order id 的数组
	 +---------------------------------------------------------------------------------------------
	 * @return				na
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 	      已付款:
	 * 			1.将“已付款”状态改成“暂停发货”，此处暂停发货不需要释放占用库存
	 *
	 *    发货中 :
	 *    		1.将“发货中”状态改成“缺货”, 采购状态改成“待采购”。此处当订单已经分配了库存，那么那么同时需要将占用的库存释放掉。
	 *    		2.如果已经生成了拣货单，那么需要将拣货单中的这个订单删除。
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderOutOfStock($orderIdList,$module='order',$action='标记缺货'){
		$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
		$rtn['success'] = true;
		$rtn['message'] = "";
		$rtn['code'] = "";
		$list_clear_platform = []; //需要清除redis
		$edit_log = array();
		foreach($OrderList as $oneOrder){
			/*************保存旧状态信息 start lrq20170926*******************/
			$old_status_info = array();
			$old_status_info['order_status'] = $oneOrder->order_status;
			$old_status_info['purchase_status'] = $oneOrder->purchase_status;
			//发货
			$old_status_info['delivery_status'] = $oneOrder->delivery_status;
			$old_status_info['delivery_id'] = $oneOrder->delivery_id;
			$old_status_info['distribution_inventory_status'] = $oneOrder->distribution_inventory_status;
			//拣货
			$old_status_info['is_print_picking'] = $oneOrder->is_print_picking;
			$old_status_info['print_picking_operator'] = $oneOrder->print_picking_operator;
			$old_status_info['print_picking_time'] = $oneOrder->print_picking_time;
			//配货
			$old_status_info['is_print_distribution'] = $oneOrder->is_print_distribution;
			$old_status_info['print_distribution_operator'] = $oneOrder->print_distribution_operator;
			$old_status_info['print_distribution_time'] = $oneOrder->print_distribution_time;
			//物流
			$old_status_info['is_print_carrier'] = $oneOrder->is_print_carrier;
			$old_status_info['print_carrier_operator'] = $oneOrder->print_carrier_operator;
			$old_status_info['printtime'] = $oneOrder->printtime;
				
			try {
				if (!empty($oneOrder->addi_info)){
					$addInfo = json_decode($oneOrder->addi_info,true);
				}else{
					$addInfo = [];
				}
			
				$addInfo['old_status_info'] = $old_status_info;
				$oneOrder->addi_info = json_encode($addInfo);
				if(!$oneOrder->save()){
					$rtn['success'] = false;
					$rtn['message'] = '暂停发货失败！保存状态信息失败！';
				}
			
			} catch (\Exception $e) {
				$rtn['success'] = false;
				$rtn['message'] = '暂停发货失败！'.$e->getMessage();
			}
			/*************保存旧状态信息 end*******************/
			
			//1.取消成功的预约
			if ($rtn['success']){
				$r1 = InventoryApiHelper::OrderProductReserveCancel($oneOrder->order_id,$module,$action);
				if ($r1['success'] == false){
					$rtn = $r1;
				}
			}
				
			//2.删除拣货单
			if ($rtn['success']){
				$r2 = DeliveryApiHelper::cancelOrderDeliveryMapping($oneOrder->order_id);
				if ($r2['success'] == false){
					$rtn = $r2;
				}
			}
			
			if ($rtn['success']){
				//  已付款 与发货 的 通用操作
				//兼容oms2.1
				if (self::isProcess21($oneOrder->order_source)){
					$oldOrderStatus = $oneOrder->order_status;
					$oneOrder->order_status = OdOrder::STATUS_OUTOFSTOCK;   // 改成“缺货”
					$oneOrder->purchase_status = OdOrder::PURCHASE_PENDING; //改成“待采购”
				}else{
					$oldOrderStatus = $oneOrder->order_status;
					$oneOrder->order_status = OdOrder::STATUS_PAY;
					$oneOrder->is_manual_order = 1;
				}
				/*  重置下列数据
				 * delivery_status  0
				* delivery_id
				* distribution_inventory_status
				* is_print_picking
				* print_picking_operator
				* print_picking_time
				* is_print_distribution
				* print_distribution_operator
				* print_distribution_time
				* is_print_carrier
				* print_carrier_operator
				* printtime
				* */
				$oneOrder->delivery_status = 0;
				$oneOrder->delivery_id = 0;
				$oneOrder->distribution_inventory_status = 0;
				//拣货
				$oneOrder->is_print_picking = 0;
				$oneOrder->print_picking_operator = 0;
				$oneOrder->print_picking_time = 0;
				//配货
				$oneOrder->is_print_distribution = 0;
				$oneOrder->print_distribution_operator = 0;
				$oneOrder->print_distribution_time = 0;
				//物流
				$oneOrder->is_print_carrier = 0;
				$oneOrder->print_carrier_operator = 0;
				$oneOrder->printtime = 0;
				if ($oneOrder->save()){
					//订单操作日志
					if (self::isProcess21($oneOrder->order_source)){
						//订单操作日志
						OperationLogHelper::log($module,$oneOrder->order_id,$action,'修改订单状态:'.OdOrder::$status[$oldOrderStatus].'至'.OdOrder::$status[$oneOrder->order_status],\Yii::$app->user->identity->getFullName());
					}else{
						//订单操作日志
						OperationLogHelper::log($module,$oneOrder->order_id,$action,'修改订单状态:'.OdOrder::$status[$oldOrderStatus].'至'.OdOrder::$status[$oneOrder->order_status],\Yii::$app->user->identity->getFullName());
							
						OperationLogHelper::log($module,$oneOrder->order_id,$action,'挂起订单',\Yii::$app->user->identity->getFullName());
					}
					
					$edit_log[] = $oneOrder->order_id;
					
					//告知dashboard面板，统计数量改变了
					OrderApiHelper::adjustStatusCount($oneOrder->order_source, $oneOrder->selleruserid, $oneOrder->order_status, $oldOrderStatus,$oneOrder->order_id);
					RedisHelper::delOrderCache( \Yii::$app->subdb->getCurrentPuid() , strtolower($oneOrder->order_source) );
					//增加清除 平台redis
					if (!in_array($oneOrder->order_source, $list_clear_platform)){
						$list_clear_platform[] = $oneOrder->order_source;
					}
					
				}else{
					//保存失败
					\Yii::info((__FUNCTION__)." ".json_encode($oneOrder->errors),"file");
					$msg .= $oneOrder->order_id."标记缺货失败！";
				}
			}
				
		}//end of each order
		
		if(!empty($edit_log)){
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "标记缺货, 订单号: ".implode(', ', $edit_log));
		}
		
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
		}
		
		return $rtn;
	}//end of setOrderOutOfStock
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 不合并订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$orderIdList			order id 的数组
	 * 			$autoSkipMergeRelate	当关联出来的订单 剩下一张  ， 是否自动 不合并订单的
	 +---------------------------------------------------------------------------------------------
	 * @return				na
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 	      已付款:
	 * 			1.将“已付款”状态改成“暂停发货”，此处暂停发货不需要释放占用库存
	 *
	 *    发货中 :
	 *    		1.将“发货中”状态改成“缺货”, 采购状态改成“待采购”。此处当订单已经分配了库存，那么那么同时需要将占用的库存释放掉。
	 *    		2.如果已经生成了拣货单，那么需要将拣货单中的这个订单删除。
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function skipMergeOrder($orderIdList, $autoSkipMergeRelate= true , $extend_msg=''){
		$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
		$msg = "";
		$hitOrder = [];
		$Datas=[];
		$list_clear_platform = []; //需要清除redisear_platform
		foreach($OrderList as $oneOrder){
			//  已付款 与发货 的 通用操作
			//$oldExceptionStatus = $oneOrder->exception_status;
			//$oneOrder->exception_status = OdOrder::EXCEP_WAITSEND;
			//$oneOrder->pay_order_type = OdOrder::PAY_CAN_SHIP;
			$thisCondition = ['order_id'=>$oneOrder->order_id , 'tag_code'=>'skip_merge'];
			$sysTag = OrderSystagsMapping::findOne($thisCondition);
			if (empty($sysTag)){
				$model = new OrderSystagsMapping();
				$model->order_id = $oneOrder->order_id ;
				$model->tag_code = 'skip_merge';
				$model->save();
			}
			
			if ($autoSkipMergeRelate ==true){
				$tmpWaitSkipMerge = self::listWaitMergeOrder($oneOrder);
					
				//不合并订单假如剩下一张对应订单， 就检测一下对应订单的状态， 满足条件自己移到可发货， 否则留下来让用户自己处理
				if (count($tmpWaitSkipMerge) ==1){
					$tmp_msg =  '订单 '.$oneOrder->order_id.'选择了不合并发货，该订单出自动不合并发货处理 ，';
					//剩下一张订单时， 不需要重复是否需要合并剩下的订单
					self::skipMergeOrder($tmpWaitSkipMerge,false,$tmp_msg);
				}
			}
			$oneOrder->checkorderstatus('system');
			
			if ($oneOrder->save()){
				//保存成功
				
				//订单操作日志
				OperationLogHelper::log('order',$oneOrder->order_id,'不合并订单发货',$extend_msg.'增加了不合并发货的系统标签',\Yii::$app->user->identity->getFullName());
				if (!in_array($oneOrder->order_source, $list_clear_platform)){
					$list_clear_platform[] = $oneOrder->order_source;
				}
			}else{
				//保存失败
				\Yii::info((__FUNCTION__)." ".json_encode($oneOrder->errors),"file");
				$msg .= $oneOrder->order_id."不合并订单发货失败！";
			}
		
		}//end of each order
		//补上相关的系统标签
		/*
		$table_name = 'od_order_systags_mapping';
		SQLHelper::groupInsertToDb($table_name, $Datas);
		*/
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
		}
		return $msg;
	}//end of skipMergeOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重新发货， 已出库订单补发
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$orderIdList			order id 的数组
	 * 			$module					模块名
	 * 			$action					执行操作
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				success			int 	成功的条数
	 * 				failure			int		失败的条数 
	 * 				message			string	失败原因
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 	   		复制出一个新订单，订单恢复到已付款订单刚进入到小老板系统的样子，
	 * 			同时给复制出来订单加上“已出库订单补发”的重发类型标签，原始订单不需要，
	 * 			并且记录下是从哪个订单复制出来的，这个写在复制出来的订单的备注上，原始订单不需要。
	 * 			这两个订单都需要加订单操作日志
	 * 		
	 * 
	 * 复制订单主要 要注意的字段
	 * 1、已付款订单类型
	 * 2、订单状态
	 * 3、库存分配状态
	 * 4、物流商操作状态 （这个可能暂时不需要改变，有可能重用）
	 * 5、配货打印状态，打印人，和打印时间
	 * 6、物流单打印状态，打印人，打印时间
	 * 7、出库时间，出库人
	 * 			
	 * 		暂停发货重发
	 * 			不需要 把 订单复制 一次， 只需要 把订单状态 改过来就可以 了	
	 * 
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function reorder($orderIdList ,$module='order',$action='重新发货'){
		$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
		$noFoundOrderCount = count($orderIdList)-count($OrderList);
		if ($noFoundOrderCount>0){
			$msg = "有".$noFoundOrderCount."无效订单 \n";
		}else{
			$msg = "";
		}
		$result = ['success'=>0 , 'failure'=>count($orderIdList)-count($OrderList), 'message'=>$msg];
		$list_clear_platform = []; //需要清除redisear_platform
		$edit_log = array();
		foreach($OrderList as $oneOrder){
			if (in_array($oneOrder['order_status'],[OdOrder::STATUS_NOPAY,OdOrder::STATUS_PAY , OdOrder::STATUS_REFUND , OdOrder::STATUS_RETURN])){
				//未付款订单， 和已付款 , 退货， 退款这几个状态不能重发货
				\Yii::info((__FUNCTION__)." : ".$oneOrder['order_id'].' error message = '.OdOrder::$status[$oneOrder['order_status']].'不能重发订单 ！',"file");
				$msg .= $oneOrder['order_id']." ".$action."失败！失败原因：".OdOrder::$status[$oneOrder['order_status']].'不能重发订单 ！ \n';
				$result['failure']++; // 统计失败订单数量
				continue;
			}
			
			if (in_array($oneOrder['order_status'], [OdOrder::STATUS_SUSPEND , OdOrder::STATUS_PAY , OdOrder::STATUS_OUTOFSTOCK , OdOrder::STATUS_ABANDONED , OdOrder::STATUS_CANCEL]) ){
				//暂停发货的订单
				//$tmpRT = OdOrder::updateAll(['reorder_type'=>'suspend_shipment' , 'pay_order_type'=>OdOrder::PAY_REORDER],['order_id'=>$oneOrder['order_id']]);
				
				/* 重新发货标签
				 'suspend_shipment'=>'暂停发货订单重发',
				'out_of_stock'=>'缺货订单重发',
				'after_shipment'=>'已出库订单补发',
				'cancel_order'=>'已取消订单重发',
				'abandoned_order'=>'废弃订单回收重发',
				*/
				/*20160928start
				if ($oneOrder['order_status'] == OdOrder::STATUS_CANCEL){
					$oneOrder->reorder_type = 'cancel_order'; //已取消订单重发
				}else if ($oneOrder['order_status'] == OdOrder::STATUS_SUSPEND){
					$oneOrder->reorder_type = 'suspend_shipment'; //暂停发货订单重发
				}else if ($oneOrder['order_status'] == OdOrder::STATUS_ABANDONED){
					$oneOrder->reorder_type = 'abandoned_order';//废弃订单回收重发
				}else if ($oneOrder['order_status'] == OdOrder::STATUS_SHIPPED){
					$oneOrder->reorder_type = 'after_shipment'; //已出库订单补发
				}elseif ($oneOrder['order_status'] == OdOrder::STATUS_OUTOFSTOCK || $oneOrder['exception_status'] == OdOrder::EXCEP_NOSTOCK){
					//条件 1 为erp2.1 的流程，  条件2为原来 的流程， 两者一者符合都可以设置为缺货后重发
					$oneOrder->reorder_type = 'out_of_stock'; //缺货订单重发
				}
				
				$oldStatus = $oneOrder['order_status'];
				
				$oneOrder->pay_order_type = OdOrder::PAY_REORDER;
				$oneOrder->order_status = OdOrder::STATUS_PAY;
				//订单异常状态
				$oneOrder->exception_status = 0;
				20160928end*/
				$oldSyncShipStatus = $oneOrder->sync_shipped_status;
				list($tmpAck , $tmpMsg , $tmpCode  , $TmpSyncShipStatus) = array_values(self::getCurrentSyncShipStaus($oneOrder, OdOrder::STATUS_PAY, $oneOrder->order_source_status));
				$oneOrder->sync_shipped_status = $TmpSyncShipStatus;
				
				$data = [
					'order_status'=>OdOrder::STATUS_PAY,
					'pay_order_type'=>OdOrder::PAY_REORDER , 
					'exception_status'=>0,
					'sync_shipped_status'=>$TmpSyncShipStatus,
				];
				
				if ($oneOrder->reorder_type  == OdOrder::STATUS_CANCEL){
					$data['reorder_type'] = 'cancel_order'; //已取消订单重发
				}else if ($oneOrder->reorder_type  == OdOrder::STATUS_SUSPEND){
					$data['reorder_type'] = 'suspend_shipment'; //暂停发货订单重发
				}else if ($oneOrder->reorder_type  == OdOrder::STATUS_ABANDONED){
					$data['reorder_type'] = 'abandoned_order';//废弃订单回收重发
				}else if ($oneOrder->reorder_type  == OdOrder::STATUS_SHIPPED){
					$data['reorder_type'] = 'after_shipment'; //已出库订单补发
				}elseif ($oneOrder->reorder_type  == OdOrder::STATUS_OUTOFSTOCK || $oneOrder->exception_status == OdOrder::EXCEP_NOSTOCK){
					//条件 1 为erp2.1 的流程，  条件2为原来 的流程， 两者一者符合都可以设置为缺货后重发
					$data['reorder_type'] = 'out_of_stock'; //缺货订单重发
				}
				$fullName = \Yii::$app->user->identity->getFullName();
				$updateRT = OrderUpdateHelper::updateOrder($oneOrder, $data, false,$fullName,$action,$module );
				if ($updateRT['ack']){
					//再检查订单 ， 是否能与当前已付款的订单合并
					$oneOrder->checkorderstatus();
					$oneOrder->save();
				/*20160928start
				if ($oneOrder->save()){
				
					OperationLogHelper::log($module,$oneOrder['order_id'],$action,OdOrder::$status[$oneOrder['order_status']].'订单重新发货',\Yii::$app->user->identity->getFullName());
					
					//告知dashboard面板，统计数量改变了
					OrderApiHelper::adjustStatusCount($oneOrder->order_source, $oneOrder->selleruserid, $oneOrder->order_status, $oldStatus,$oneOrder->order_id);
					RedisHelper::delOrderCache( \Yii::$app->subdb->getCurrentPuid() , strtolower($oneOrder->order_source) );
					20160928start*/
					$result['success']++; // 统计成功订单数量
					$edit_log[] = $oneOrder['order_id'];
					//增加清除 平台redis
					if (!in_array($oneOrder['order_source'], $list_clear_platform)){
						$list_clear_platform[] = $oneOrder['order_source'];
					}
					
					/*20160928start
					//订单虚拟发货状态刷新
					OrderBackgroundHelper::setRelationOrderSyncShippedStatus($oneOrder , $oneOrder->sync_shipped_status , $oldSyncShipStatus);
					20160928start*/
				}else{
					$result['failure']++; // 统计失败订单数量
					\Yii::error((__FUNCTION__)." : ".$oneOrder['order_id'].' error message = '.$oneOrder['order_id'].'  '.OdOrder::$status[$oneOrder['order_status']].'订单重新发货失败！',"file");
					$msg .= $oneOrder['order_id'].' 暂停发货订单重新发货失败！ \n';
				}
				
				
			}else{
				//非暂停发货的订单
				//  复制出一个新订单
				$newOrder = $oneOrder->attributes;
					
				//释放小老板订单号
				unset($newOrder['order_id']);
				//订单类型 = 已付款
				$newOrder['pay_order_type'] = OdOrder::PAY_REORDER;
					
				//订单状态
				$newOrder['order_status'] = OdOrder::STATUS_PAY;
				//订单异常状态
				$oneOrder->exception_status = 0;
					
				//库存分配状态
				$newOrder['distribution_inventory_status'] = 2;
					
				//配货打印状态，打印人，和打印时间
				unset($newOrder['is_print_distribution']);     //配货打印状态
				unset($newOrder['print_distribution_operator']); //打印人
				unset($newOrder['print_distribution_time']);//打印时间
					
				//物流单打印状态，打印人，打印时间
				unset($newOrder['is_print_carrier']);		//物流单打印状态
				unset($newOrder['print_carrier_operator']);		//打印人
				unset($newOrder['printtime']);				//打印时间
				
				
				//2016-09-14 已出库订单视为 手工订单
				$newOrder['order_capture'] = 'Y';
				$newOrder['sync_shipped_status'] = 'N';// 无须虚拟发货的状态
				$newOrder['order_relation'] = 'normal'; // 变成正常订单
				
				//补发的订单都需要重置运输服务 20170904hqw
				$newOrder['default_carrier_code'] = '';
				$newOrder['default_shipping_method_code'] = '';
				
				
				//20160928 补发订单时间利润统计相关的金额重置为0
				$newOrder['subtotal'] = 0;			//订单商品金额
				$newOrder['grand_total'] = 0;		// 订单总金额
				$newOrder['commission_total'] = 0; 	// 平台佣金
				$newOrder['logistics_cost'] = 0; 	// 物流成本
				
				$newOrder['profit'] = null; 	//利润 2017-12-27
				
				if (strtoupper($oneOrder['order_type']) =='AFN'){
					$newOrder['order_type'] = 'MFN';
				}elseif (strtoupper($oneOrder['order_type']) =='FBC'){
					$newOrder['order_type'] = '';
				}
				
				//出库时间，出库人 @todo
					
				//时间 重置
				$newOrder['create_time'] = time();
				$newOrder['update_time'] = time();
					
				/* 重新发货标签
				 'suspend_shipment'=>'暂停发货订单重发',
				'out_of_stock'=>'缺货订单重发',
				'after_shipment'=>'已出库订单补发',
				'cancel_order'=>'已取消订单重发',
				'abandoned_order'=>'废弃订单回收重发',
				*/
				if ($oneOrder['order_status'] == OdOrder::STATUS_CANCEL){
					$newOrder['reorder_type'] = 'cancel_order'; //已取消订单重发
				}else if ($oneOrder['order_status'] == OdOrder::STATUS_SUSPEND){
					$newOrder['reorder_type'] = 'suspend_shipment'; //暂停发货订单重发
				}else if ($oneOrder['order_status'] == OdOrder::STATUS_ABANDONED){
					$newOrder['reorder_type'] = 'abandoned_order';//废弃订单回收重发
				}else if ($oneOrder['order_status'] == OdOrder::STATUS_SHIPPED){
					$newOrder['reorder_type'] = 'after_shipment'; //已出库订单补发
				}elseif ($oneOrder['order_status'] == OdOrder::STATUS_OUTOFSTOCK || $oneOrder['exception_status'] == OdOrder::EXCEP_NOSTOCK){
					//条件 1 为erp2.1 的流程，  条件2为原来 的流程， 两者一者符合都可以设置为缺货后重发
					$newOrder['reorder_type'] = 'out_of_stock'; //缺货订单重发
				}
					
				$rt = self::doInsert($newOrder);
					
				if (!empty($rt['success'])){
					$order_id = str_pad( $rt['order_id'],11,'0',STR_PAD_LEFT);
					//保存成功  , 保存item 数据
					$itemList = [];
				
				
					//获取订单 item信息
					$itemList = OdOrderItem::find()->where(['order_id'=>$oneOrder['order_id']])->asArray()->all();
				
					//重写订单 item信息
					foreach($itemList as &$thisItem){
						unset($thisItem['order_item_id']);//primary key release
						$thisItem['order_id'] = $order_id;//order id overwrite
						$thisItem['item_source'] = 'local';//item source overwrite
						$thisItem['delivery_status'] = 'allow';//delivery status overwrite
						$thisItem['manual_status'] = 'enable';//manual status overwrite
						
						$thisItem['create_time'] = time();
						$thisItem['update_time'] = time();
					}
				
					if (!empty($itemList)){
						//是否更新待发货数量逻辑
						/* 20170222kh 补发后订单全部变成手工订单都需要计算库存
						$doPIp_updatePendingQty = false;
						
						if(isset($oneOrder['order_type'])){
							if (!in_array(strtoupper($oneOrder['order_type']), ['AFN' , 'FBC'])){
								$doPIp_updatePendingQty = true;
							}else{
								$doPIp_updatePendingQty = false;
							}
						}else{
							$doPIp_updatePendingQty = true;
						}
						*/
						$doPIp_updatePendingQty = true;
						if ($doPIp_updatePendingQty){
							if (in_array($oneOrder['order_status'], [OdOrder::STATUS_PAY])){
								$doPIp_updatePendingQty = true;
							}else{
								$doPIp_updatePendingQty = false;
							}
						}
						$effect = self::doProductImport($itemList , $order_id, $oneOrder['order_source'],$doPIp_updatePendingQty,$oneOrder['order_status'],$oneOrder['selleruserid']);
							
						if ($effect == count($itemList)){
							//重置 新的订单发货流程
							DeliveryApiHelper::resetOrder([$rt['order_id']]);
							
							//旧订单生成  行为日志
							OperationLogHelper::log($module,$oneOrder['order_id'],$action,'订单重新发货，生成新的小老板单号为:'.$order_id,\Yii::$app->user->identity->getFullName());
							//新订单生成  行为日志
				
							OperationLogHelper::log($module,$order_id,$action,'订单重新发货，来源的小老板单号为:'.$oneOrder['order_id'],\Yii::$app->user->identity->getFullName());
				
							$result['success']++; // 统计成功订单数量
							
							//写入操作日志，补发订单
							UserHelper::insertUserOperationLog('order', "已出库订单补发, 来源订单: ".$oneOrder['order_id'].", 生成新订单: ".$order_id);
							
							//增加清除 平台redis 
							if (!in_array($oneOrder['order_source'], $list_clear_platform)){
								$list_clear_platform[] = $oneOrder['order_source'];
							}
						}else{
							\Yii::error((__FUNCTION__)." : ".$order_id.'保存相关商品失败 ！ ',"file");
							$msg .=$order_id."保存相关商品失败！\n";
							$result['failure']++; // 统计失败订单数量
							if (empty($effect)){
								//假如 没有任何商品都保存成功则删除订单头
								OdOrder::deleteAll(['order_id'=>$rt['order_id']]); //考虑性能问题， 还是不采用事务方式
							}
				
						}
					}else {
						\Yii::error((__FUNCTION__)." : ".$order_id.' 找不到相关商品 ！ ',"file");
						$msg .=$order_id."找不到相关商品";
						$result['failure']++; // 统计失败订单数量
						OdOrder::deleteAll(['order_id'=>$rt['order_id']]); //考虑性能问题， 还是不采用事务方式
					}
				
				}else{
					//保存失败
					\Yii::error((__FUNCTION__)." : ".$oneOrder['order_id'].' error message = '.$rt['message'],"file");
					$msg .= $oneOrder['order_id']." ".$action."失败！失败原因：".$rt['message'].'\n';
					$result['failure']++; // 统计失败订单数量
				
				}
			}
			
			
		
		}//end of each order
		
		if(!empty($edit_log)){
			//写入操作日志，重新发货的订单
			UserHelper::insertUserOperationLog('order', "重新发货, 订单号: ".implode(', ', $edit_log));
		}
		
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
		}
		return $result;
	}//end of reorder
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 复制订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$orderIdList			order id 的数组
	 * 			$module					模块名
	 * 			$action					执行操作
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				success			int 	成功的条数
	 * 				failure			int		失败的条数
	 * 				message			string	失败原因
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 	   		复制出一个新订单，订单恢复到已付款订单刚进入到小老板系统的样子，
	 * 			同时给复制出来订单加上“已出库订单补发”的重发类型标签，原始订单不需要，
	 * 			并且记录下是从哪个订单复制出来的，这个写在复制出来的订单的备注上，原始订单不需要。
	 * 			这两个订单都需要加订单操作日志
	 *
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/08/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function copyOrder($orderIdList ,$module='order',$action='复制订单'){
		$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
		$noFoundOrderCount = count($orderIdList)-count($OrderList);
		if ($noFoundOrderCount>0){
			$msg = "有".$noFoundOrderCount."无效订单 \n";
		}else{
			$msg = "";
		}
		$result = ['success'=>0 , 'failure'=>count($orderIdList)-count($OrderList), 'message'=>$msg];
		foreach($OrderList as $oneOrder){
			self::_copyOrderProcess($oneOrder,$result,$module,$action);
		}
		return $result;
	}//end of copyOrder
	
	static private function _copyOrderProcess(&$oneOrder,&$result,$module,$action){
		//  复制出一个新订单
		$newOrder = $oneOrder->attributes;
			
		//释放小老板订单号
		unset($newOrder['order_id']);
		//订单类型 = 已付款
		$newOrder['pay_order_type'] = OdOrder::PAY_REORDER;
			
		//订单状态
		$newOrder['order_status'] = OdOrder::STATUS_PAY;
		//订单异常状态
		$oneOrder->exception_status = 0;
			
		//库存分配状态
		$newOrder['distribution_inventory_status'] = 2;
			
		//配货打印状态，打印人，和打印时间
		unset($newOrder['is_print_distribution']);     //配货打印状态
		unset($newOrder['print_distribution_operator']); //打印人
		unset($newOrder['print_distribution_time']);//打印时间
			
		//物流单打印状态，打印人，打印时间
		unset($newOrder['is_print_carrier']);		//物流单打印状态
		unset($newOrder['print_carrier_operator']);		//打印人
		unset($newOrder['printtime']);				//打印时间
		
		
		//2016-09-14 已出库订单视为 手工订单
		$newOrder['order_capture'] = 'Y';
		$newOrder['sync_shipped_status'] = 'N';// 无须虚拟发货的状态
		$newOrder['order_relation'] = 'normal'; // 变成正常订单
		
		
		//20160928 补发订单时间利润统计相关的金额重置为0
		$newOrder['subtotal'] = 0;			//订单商品金额
		$newOrder['grand_total'] = 0;		// 订单总金额
		$newOrder['commission_total'] = 0; 	// 平台佣金
		$newOrder['logistics_cost'] = 0; 	// 物流成本
		
		$newOrder['profit'] = null;     //利润 2017-12-27
		
		
		if (strtoupper($oneOrder['order_type']) =='AFN'){
			$newOrder['order_type'] = 'MFN';
		}elseif (strtoupper($oneOrder['order_type']) =='FBC'){
			$newOrder['order_type'] = '';
		}
		
		//出库时间，出库人 @todo
			
		//时间 重置
		$newOrder['create_time'] = time();
		$newOrder['update_time'] = time();
			
		/* 重新发货标签
		 'suspend_shipment'=>'暂停发货订单重发',
		'out_of_stock'=>'缺货订单重发',
		'after_shipment'=>'已出库订单补发',
		'cancel_order'=>'已取消订单重发',
		'abandoned_order'=>'废弃订单回收重发',
		*/
		if ($oneOrder['order_status'] == OdOrder::STATUS_CANCEL){
			$newOrder['reorder_type'] = 'cancel_order'; //已取消订单重发
		}else if ($oneOrder['order_status'] == OdOrder::STATUS_SUSPEND){
			$newOrder['reorder_type'] = 'suspend_shipment'; //暂停发货订单重发
		}else if ($oneOrder['order_status'] == OdOrder::STATUS_ABANDONED){
			$newOrder['reorder_type'] = 'abandoned_order';//废弃订单回收重发
		}else if ($oneOrder['order_status'] == OdOrder::STATUS_SHIPPED){
			$newOrder['reorder_type'] = 'after_shipment'; //已出库订单补发
		}elseif ($oneOrder['order_status'] == OdOrder::STATUS_OUTOFSTOCK || $oneOrder['exception_status'] == OdOrder::EXCEP_NOSTOCK){
			//条件 1 为erp2.1 的流程，  条件2为原来 的流程， 两者一者符合都可以设置为缺货后重发
			$newOrder['reorder_type'] = 'out_of_stock'; //缺货订单重发
		}
			
		$rt = self::doInsert($newOrder);
			
		if (!empty($rt['success'])){
			$order_id = str_pad( $rt['order_id'],11,'0',STR_PAD_LEFT);
			//保存成功  , 保存item 数据
			$itemList = [];
		
		
			//获取订单 item信息
			$itemList = OdOrderItem::find()->where(['order_id'=>$oneOrder['order_id']])->asArray()->all();
		
			//重写订单 item信息
			foreach($itemList as &$thisItem){
				unset($thisItem['order_item_id']);//primary key release
				$thisItem['order_id'] = $order_id;//order id overwrite
				$thisItem['item_source'] = 'local';//item source overwrite
				$thisItem['delivery_status'] = 'allow';//delivery status overwrite
				$thisItem['manual_status'] = 'enable';//manual status overwrite
		
				$thisItem['create_time'] = time();
				$thisItem['update_time'] = time();
			}
		
			if (!empty($itemList)){
				//是否更新待发货数量逻辑
				/* 20170222kh 补发后订单全部变成手工订单都需要计算库存
				 $doPIp_updatePendingQty = false;
		
				if(isset($oneOrder['order_type'])){
				if (!in_array(strtoupper($oneOrder['order_type']), ['AFN' , 'FBC'])){
				$doPIp_updatePendingQty = true;
				}else{
				$doPIp_updatePendingQty = false;
				}
				}else{
				$doPIp_updatePendingQty = true;
				}
				*/
				$doPIp_updatePendingQty = true;
				if ($doPIp_updatePendingQty){
					if (in_array($oneOrder['order_status'], [OdOrder::STATUS_PAY])){
						$doPIp_updatePendingQty = true;
					}else{
						$doPIp_updatePendingQty = false;
					}
				}
				$effect = self::doProductImport($itemList , $order_id, $oneOrder['order_source'],$doPIp_updatePendingQty,$oneOrder['order_status'],$oneOrder['selleruserid']);
					
				if ($effect == count($itemList)){
					//重置 新的订单发货流程
					DeliveryApiHelper::resetOrder([$rt['order_id']]);
						
					//旧订单生成  行为日志
					OperationLogHelper::log($module,$oneOrder['order_id'],$action,'订单重新发货，生成新的小老板单号为:'.$order_id,\Yii::$app->user->identity->getFullName());
					//新订单生成  行为日志
		
					OperationLogHelper::log($module,$order_id,$action,'订单重新发货，来源的小老板单号为:'.$oneOrder['order_id'],\Yii::$app->user->identity->getFullName());
		
					$result['success']++; // 统计成功订单数量
					
				}else{
					\Yii::error((__FUNCTION__)." : ".$order_id.'保存相关商品失败 ！ ',"file");
					$msg .=$order_id."保存相关商品失败！\n";
					$result['failure']++; // 统计失败订单数量
					if (empty($effect)){
						//假如 没有任何商品都保存成功则删除订单头
						OdOrder::deleteAll(['order_id'=>$rt['order_id']]); //考虑性能问题， 还是不采用事务方式
					}
		
				}
			}else {
				\Yii::error((__FUNCTION__)." : ".$order_id.' 找不到相关商品 ！ ',"file");
				$msg .=$order_id."找不到相关商品";
				$result['failure']++; // 统计失败订单数量
				OdOrder::deleteAll(['order_id'=>$rt['order_id']]); //考虑性能问题， 还是不采用事务方式
			}
		
		}else{
			//保存失败
			\Yii::error((__FUNCTION__)." : ".$oneOrder['order_id'].' error message = '.$rt['message'],"file");
			$msg .= $oneOrder['order_id']." ".$action."失败！失败原因：".$rt['message'].'\n';
			$result['failure']++; // 统计失败订单数量
		
		}
		return $result;
	}
	
	static public function setOriginShipmentDetail(&$OrderModel){
		//validate order model is active or not 
		if(!($OrderModel instanceof OdOrder)){
			return ['success'=>false , 'message'=>'E1.参数错误'];;
		}
		if (empty($OrderModel->origin_shipment_detail)){
			//没有原始订单收件人数据时候需要保存一份
			$shipment_info = [
			'consignee'=>$OrderModel->consignee,
			'consignee_postal_code'=>$OrderModel->consignee_postal_code,
			'consignee_phone'=>$OrderModel->consignee_phone,
			'consignee_mobile'=>$OrderModel->consignee_mobile,
			'consignee_fax'=>$OrderModel->consignee_fax,
			'consignee_email'=>$OrderModel->consignee_email,
			'consignee_company'=>$OrderModel->consignee_company,
			'consignee_country'=>$OrderModel->consignee_country,
			'consignee_country_code'=>$OrderModel->consignee_country_code,
			'consignee_city'=>$OrderModel->consignee_city,
			'consignee_province'=>$OrderModel->consignee_province,
			'consignee_district'=>$OrderModel->consignee_district,
			'consignee_county'=>$OrderModel->consignee_county,
			'consignee_address_line1'=>$OrderModel->consignee_address_line1,
			'consignee_address_line2'=>$OrderModel->consignee_address_line2,
			'consignee_address_line3'=>$OrderModel->consignee_address_line3,
			];
			
			$OrderModel->origin_shipment_detail = json_encode($shipment_info);
			
			return ['success'=>true , 'message'=>'操作成功'];
			
		}else{
			return ['success'=>true , 'message'=>'原始收件人信息已经存在'];
		}
	}
	
	/**
	 * 订单所包含商品做冗余处理，用户统计，搜索，拣货，配货等
	 * @access public
	 * @param integer:$order_id		小老板单号
	 * @param array:$skuinfos		sku信息数组
	 * @return 布尔值
	 * @author		million
	 * @version		0.1		2015.01.13
	 **/
	static public function saveOrderGoods($order_id,$skuinfos){
		OdOrderGoods::deleteAll(['order_id'=>$order_id]);
		$order = OdOrder::findOne(['order_id'=>$order_id]);
		$return = true;
		foreach ($skuinfos as $skuinfo){
			$orderGoods = new OdOrderGoods();
			$orderGoods->order_source = $order->order_source;
			$orderGoods->order_id = $order->order_id;
			$orderGoods->order_source_order_id = $skuinfo['order_source_order_id'];
			$orderGoods->order_item_id = $skuinfo['order_item_id'];
			
			$orderGoods->order_source_itemid = $skuinfo['order_source_itemid'];
			$orderGoods->photo_primary = $skuinfo['photo_primary'];
			$orderGoods->product_attributes = $skuinfo['product_attributes'];
			$orderGoods->product_name = $skuinfo['product_name'];
			
			$orderGoods->order_item_sku = $skuinfo['order_item_sku'];
			$orderGoods->order_item_quantity = $skuinfo['order_item_quantity'];
			$orderGoods->sku = $skuinfo['sku'];
			$orderGoods->quantity = $skuinfo['qty'];
			$orderGoods->price = $skuinfo['purchase_price'];
			$orderGoods->sold_time = date("Y-m-d H:i:s",$order->order_source_create_time);
			$orderGoods->paid_time = date("Y-m-d H:i:s",$order->paid_time);
			$orderGoods->selleruserid = $order->selleruserid;
			$orderGoods->source_buyer_user_id = $order->source_buyer_user_id;
			if (!$orderGoods->save()){
				$return = false;
				break;
			}
		}
		//如果有一条保存失败则删除残留数据
		if (!$return){
			OdOrderGoods::deleteAll(['order_id'=>$order_id]);
		}
		return $return;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 批量提交发货 ， 只对订单已付款的订单进行提交发货 操作
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$orderIdList			order id 的数组
	 * 			$module					模块名
	 * 			$action					动作名
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 			success					成功数
	 * 			failure					失败数
	 * 			message					失败相关信息
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 			
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderShipped($orderIdList,$module='order',$action='提交发货'){
		try {
			$execList =['success'=>0,'failure'=>0 ,'message'=>'']; 
			$list_clear_platform = []; //需要清除redis
			$shipandcreateSKU=ConfigHelper::getConfig('order/shipandcreateSKU');
			if (is_null($shipandcreateSKU)){
				$shipandcreateSKU=0;
			}
			$edit_log = array();
			foreach ($orderIdList as $orderid){
				$order = OdOrder::findOne($orderid);
				
				if (count($order->items)==0){
					$execList['failure']++;
					$execList['message'] .= $order->order_id.' 订单异常：没有商品！ <br>';
					continue;
				}
				
				if (self::isProcess21($order->order_source)){
					if (empty($order->default_shipping_method_code) ||empty($order->default_carrier_code)  ){
						$execList['failure']++;
						$execList['message'] .= $order->order_id.' 未选择运输服务！ <br>';
						continue;
					}else if($order->default_warehouse_id == '-1'){
						$execList['failure']++;
						$execList['message'] .= $order->order_id.' 运输服务失效请重新指定！ <br>';
						continue;
					}
				}
				
				// ebay 非手工订单需要检查 paypal 地址是否同步  ， paypay 地址已经验证
				if (in_array($order->order_source,['ebay']) && $order->order_capture == 'N'  && $order->order_verify ==  OdOrder::ORDER_VERIFY_PENDING){
					$execList['failure']++;
					$execList['message'] .= $order->order_id.' paypal 地址还没有 更新，请更新后再提交发货！ <br>';
					continue;
				}
				
				$OriginalStatus = $order->order_status;
				
				if ($order->order_status==OdOrder::STATUS_PAY){
					if (!empty($shipandcreateSKU)){
						$checkProductExist = self::_autoCompleteProductInfo($orderid,$module,$action);
						if ($checkProductExist['success'] == false){
							$execList['failure']++;
							$execList['message'] .= $order->order_id.' '.$checkProductExist['message'].' <br>';
							continue;
						}
					}
					
					$oldExceptionStatus = $order->exception_status;
					
					$order->order_status=OdOrder::STATUS_WAITSEND;
					$order->delivery_status = 0;
					$order->distribution_inventory_status = 2;
					$order->exception_status = OdOrder::EXCEP_WAITSEND;//提交发货后将异常状态改成可发货，忽略异常发货
					
					//这里添加假如之前提交物流或者发货的话，需要重新将物流状态清空，因为复制订单，拆分订单会有BUG，因为把该字段也复制了。
					//由于有重新上传的功能所以这里还是不要改变原来的状态
// 					if($order->carrier_step != OdOrder::CARRIER_WAITING_UPLOAD){
// 						$order->carrier_step = OdOrder::CARRIER_CANCELED;
// 					}
					
					//操作超时状态处理  liang 2015-12-26
					$addtionLog = '';
					if(!empty($order->weird_status)){
						$addtionLog = ',同时移除操作超时标签';
						$order->weird_status='';
					}//操作超时状态处理  end
					if ($order->save()){
						//保存成功！
						//写行为日志
						OperationLogHelper::log($module,$orderid,$action,'修改订单状态:已付款至发货中'.$addtionLog, \Yii::$app->user->identity->getFullName());
						
						$edit_log[] = $order->order_id;
						
						//告知dashboard面板，统计数量改变了
						OrderApiHelper::adjustStatusCount($order->order_source, $order->selleruserid, $order->order_status, $OriginalStatus,$orderid);
						RedisHelper::delOrderCache( \Yii::$app->subdb->getCurrentPuid() , strtolower($order->order_source) );
						
						//保存当前 的报关信息
						$itemDeclareInfo = \eagle\modules\carrier\helpers\CarrierDeclaredHelper::getOrderDeclaredInfoByOrder($order);
						
						foreach($itemDeclareInfo as $Order_itemid=>$declareInfo){
							$tmpItem = OdOrderItem::findOne($Order_itemid);
							//没有 报关信息才保存 当前使用的报关信息
							$tmpDeclareation = json_decode($tmpItem->declaration,true);
							if (@$tmpDeclareation['isChange'] !='Y'){
								$NameCNList = $declareInfo['declaration']['nameCN'];
								$NameENList = $declareInfo['declaration']['nameEN'];
								$PriceList = $declareInfo['declaration']['price'];
								$WeightList = $declareInfo['declaration']['weight'];
								$detailHsCodeList = $declareInfo['declaration']['code'];
								$ischange = 'N';
									
								$result=OrderUpdateHelper::setOrderItemDeclaration($tmpItem,$NameCNList,$NameENList,$PriceList,$WeightList,$detailHsCodeList,$ischange);
								if(isset($result['ack']) && $result['ack']==false){
									\Yii::error((__FUNCTION__)." uid=".\Yii::$app->subdb->getCurrentPuid()." orderid=".$orderid." error message:".$result['message'],'file');
								}
							}
							
						}
						
						
						OrderUpdateHelper::resetOrderItemDeliveryStatus($order);
						
						//重置first_sku
						OrderUpdateHelper::resetFirstSku($order->order_id);
						
						//调用 接口 
						$apiReturn = OrderApiHelper::saveOrderGoods($order->order_id);
						if (empty($apiReturn)){
							\Yii::error(["Order",__CLASS__,__FUNCTION__,$order->order_id.": saveOrderGoods return false "],'edb\global');
							$execList['failure']++;
							$execList['message'] .= $order->order_id.'发生内部错误 <br>';
						}
						$execList['success']++;
						$execList['message'] .= $order->order_id.'提交发货成功 <br>';
						
						//增加清除 平台redis 
						if (!in_array($order->order_source, $list_clear_platform)){
							$list_clear_platform[] = $order->order_source;
						}
						
						//移除 虚假发货的标签
						//OrderTagHelper::DelOrderSysTag($order->order_id, 'sys_unshipped_tag');
						
						
						//已付款订单能与其他合并的情况， 假如移入已完成需要为其他订单检测一下剩下的订单数量 是否为一， 假如为一的情况 ， 要把剩下待合并订单标志去掉
						if ($oldExceptionStatus == OdOrder::EXCEP_WAITMERGE  ){
							$canMergeOrderList = self::listWaitMergeOrder($order);
								
							//剩下的订单为1 ， 清除待合并的标志
							if (count($canMergeOrderList) == 1){
								OdOrder::updateAll(['exception_status'=>OdOrder::EXCEP_WAITSEND], ['order_id'=>$canMergeOrderList , 'order_status'=>OdOrder::STATUS_PAY]);
								OperationLogHelper::log($module,$canMergeOrderList[0],$action,$order->order_id.'已付款至发货中,所以取消合并标志', \Yii::$app->user->identity->getFullName());
								RedisHelper::delOrderCache( \Yii::$app->subdb->getCurrentPuid() , strtolower($order->order_source) );
							}
						}
					}else{
						//保存失败！
						\Yii::error(["Order",__CLASS__,__FUNCTION__,$order->order_id.": ".json_encode($order->errors)],'edb\global');
						$execList['failure']++;
						$execList['message'] .= $order->order_id.'提交发货失败 <br>';
					}
						
				}else{
					$execList['failure']++;
					$execList['message'] .= $order->order_id.' 只能在已付款状态下才能提交发货 <br>';
				}
			}
		}catch (\Exception $e){
			$execList['message'] = $e->getMessage();
		}
		
		if(!empty($edit_log)){
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "移入发货中, 订单号: ".implode(', ', $edit_log));
		}
		
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
		}
		
		return $execList;
	}//end of setOrderShipped
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 添加订单备注
	 * +---------------------------------------------------------------------------------------------
	 * @access static
	 *+---------------------------------------------------------------------------------------------
	 * @param 	$moduleName 			string		模块名
	 * 			$accountID				string		平台账号
	 *        	$platform				string		平台
	 *        	$platform_order_id		string		平台订单号
	 *        	$memo					string		备注信息
	 *+---------------------------------------------------------------------------------------------
	 * @return array
	 * 			success					boolean		true 成功，false失败
	 * 			message					string		失败的原因
	 * +---------------------------------------------------------------------------------------------
	 * @invoking	OrderApiHelper::addOrderMemo('Tracking','aaa','aliexpress','213564','add memo data');
	 *+---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2016/01/05				初始化
	 *+---------------------------------------------------------------------------------------------
	 */
	static public function overwriteOrderDescription( $accountID, $platform, $platform_order_id, $memo , $moduleName, $action) {
		//检查传入的参数数量是否正确
		$ParamMapping = ['模块名','平台账号','平台','平台订单号','备注信息']; //参数名字的顺序， 主要 用于提示信息
		$ParamList =  func_get_args();
		foreach ($ParamList as $key=>$aParam){
			if (empty($aParam)) return ['success'=>false , 'message'=>(empty($ParamMapping[$key])?"E001.参数格式出现异常":'E002.'.$ParamMapping[$key].'不能为空！')];
		}
	
		$OrderModel = OdOrder::find ()->where ( ['order_source_order_id' => $platform_order_id] )->one ();
	
		if (empty($OrderModel)){
			return ['success'=>false , 'message'=>'E003找不到订单：'.$platform_order_id];
		}
	
		return self::addOrderDescByModel($OrderModel, $memo, $moduleName, $action);
		/*
		$olddesc = $OrderModel->desc;
		$OrderModel->desc = $memo;
		if ($OrderModel->save ( false )) {
			OperationLogHelper::log ( $moduleName, $OrderModel->order_id, $action, '修改备注: (' . $olddesc.'->' . $OrderModel->desc . ')', \Yii::$app->user->identity->getFullName () );
			if (!empty($$OrderModel->desc)){
				//订单备注不为空 则增加系统标签
				OrderTagHelper::setOrderSysTag($OrderModel->order_id, 'order_memo');
			}else{
				//订单备注不为空 则删除系统标签
				OrderTagHelper::DelOrderSysTag($OrderModel->order_id, 'order_memo');
			}
			return ['success'=>true , 'message'=>''];
		} else {
			$err_msg .= $OrderModel->order_source_order_id . " 添加备注失败！";
			return ['success'=>false , 'message'=>'E004'.$err_msg.json_encode($orderModel->errors)];
		}
		*/
	}//end of overwriteOrderDescription
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 添加订单备注
	 * +---------------------------------------------------------------------------------------------
	 * @access static
	 *+---------------------------------------------------------------------------------------------
	 * @param 	
	 * 			$OrderModel				object		order model 
	 * 			$action					string		操作名
	 * 			$moduleName 			string		模块名
	 *        	$memo					string		备注信息
	 *+---------------------------------------------------------------------------------------------
	 * @return array
	 * 			success					boolean		true 成功，false失败
	 * 			message					string		失败的原因
	 * +---------------------------------------------------------------------------------------------
	 * @invoking	OrderHelper::addOrderDescByModel($orderModel,'aaa','order','添加订单备注');
	 *+---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2016/03/28				初始化
	 *+---------------------------------------------------------------------------------------------
	 */
	static public function addOrderDescByModel($OrderModel , $memo, $moduleName , $action){
		
		$olddesc = $OrderModel->desc;
		$OrderModel->desc = $memo;
		if ($OrderModel->save ( false )) {
			OperationLogHelper::log ( $moduleName, $OrderModel->order_id, $action, '修改备注: (' . $olddesc.'->' . $OrderModel->desc . ')', \Yii::$app->user->identity->getFullName () );
			if (!empty($OrderModel->desc)){
				//订单备注不为空 则增加系统标签
				OrderTagHelper::setOrderSysTag($OrderModel->order_id, 'order_memo');
			}else{
				//订单备注不为空 则删除系统标签
				OrderTagHelper::DelOrderSysTag($OrderModel->order_id, 'order_memo');
			}
			return ['success'=>true , 'message'=>''];
		} else {
			$err_msg .= $OrderModel->order_source_order_id . " 添加备注失败！";
			return ['success'=>false , 'message'=>'E004'.$err_msg.json_encode($orderModel->errors)];
		}
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
	 * @author		lkh		2015/11/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderTrackerStatus($accountID , $platform , $track_no , $status ){
		//检查传入的参数数量是否正确
		$ParamMapping = ['平台账号','平台','物流号','物流状态']; //参数名字的顺序， 主要 用于提示信息
		$ParamList =  func_get_args();
		foreach ($ParamList as $key=>$aParam){
			if (empty($aParam)) return ['success'=>false , 'message'=>(empty($ParamMapping[$key])?"E001.参数格式出现异常":'E002.'.$ParamMapping[$key].'不能为空！')];
		}
	
		$OdShipModel = OdOrderShipped::find()->where(['selleruserid'=>$accountID , 'order_source'=>$platform , 'tracking_number'=>$track_no])->one();
	
		if (empty($OdShipModel)){
			return ['success'=>false , 'message'=>'E003.找不到对应的物流记录'.$track_no];
		}else{
			$OdShipModel->tracker_status =$status;
			if ($OdShipModel->save()){
				//success
				//sync order tracker status
				//假如 这个 是最新的物流号则更新订单的状态 ， 假如 不是的话， 则不需要更新
				$ct = OdOrderShipped::find()->where(['selleruserid'=>$accountID , 'order_source'=>$platform , 'order_id'=>$OdShipModel->order_id])->andwhere(['>', 'id', $OdShipModel->id])->count();
				if (empty($ct)){
					//如果是0或者 空， 找不到的情况 ， 表示这是最新的物流号， 需要更新订单物流状态
					$orderModel = OdOrder::findone($OdShipModel->order_id);
					if (!empty($orderModel)){
						//最新的物流号需要同步 订单的物流状态
						$orderModel->tracker_status = $status;
						if ( $orderModel->save()){
							//sync success
							return ['success'=>true ,'message'=>'同步成功'];
						}else{
							//sync failure
							return ['success'=>false , 'message'=>'E004: failure to save platform ='.$platform.', accountID='.$accountID.'  tracker status='.$track_no." , new status=".$status.' due to '.json_encode($orderModel->errors)];
						}
					}else{
						return ['success'=>false , 'message'=>'E005: not found order id='.$OdShipModel->order_id .',platform ='.$platform.', accountID='.$accountID.'  tracker status='.$track_no." , new status=".$status.''];
					}
				}else{
					//不是最新的物流号不需要同步 订单的物流状态
					return ['success'=>true ,'message'=>'不需要同步'];//
				}
	
			}else{
				return ['success'=>false , 'message'=>'E006: failure to save platform ='.$platform.', accountID='.$accountID.'  tracker status='.$track_no." , new status=".$status.' due to '.json_encode($OdShipModel->errors)];
			}
		}
	
	} // end of setOrderTrackerStatus
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单的信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $like_params			like 相关的查询条件
	 * @param     $eq_params           	equal 相关的查询条件 需要指定刷选的fields以及值，field name要和字段名一样，值可以多个可能的，逗号隔开
	 *                              例如 array( order_status=>'200',
	 *                                       )
	 * @param     $date_params		   	时间范围的查询条件
	 * @param     $in_params		    in 相关的查询条件
	 * @param     $other_params		    other 就是一些子查询之类或者特殊的条件           
	 * @param     $sort            		 指定排序field
	 * @param     $order            	排序顺序
	 * @param     $pageSize         	每页显示数量，默认是50
	 * @param	  $showItem				productOnly 只显示item中真实的商品 , all  所有item
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					OrderHelper::getOrderListByCondition();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/2/19				初始化
	 +---------------------------------------------------------------------------------------------
	 * $page_related_params  后端调用时控制分页的页数
	 **/
	public static function getOrderListByCondition($like_params,$eq_params, $date_params , $in_params, $other_params, $sort , $order , $pageSize=50 ,$noPagination = false,$showItem='productOnly' , &$query=null, $page_related_params = array() ){
		return OrderGetDataHelper::getOrderListByCondition($like_params, $eq_params, $date_params, $in_params, $other_params, $sort, $order,$pageSize, $noPagination , $showItem , $query, $page_related_params);
	}//end of getOrderListByCondition
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取当前 查询条件下的订单的物流汇总信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $like_params			like 相关的查询条件
	 * @param     $eq_params           	equal 相关的查询条件 需要指定刷选的fields以及值，field name要和字段名一样，值可以多个可能的，逗号隔开
	 *                              例如 array( order_status=>'200',
	 *                                       )
	 * @param     $date_params		   	时间范围的查询条件
	 * @param     $in_params		    in 相关的查询条件
	 * @param     $other_params		    other 就是一些子查询之类或者特殊的条件
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					OrderHelper::getShipmethodGroupWithOrder($like_params,$eq_params, $date_params , $in_params, $other_params);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getShipmethodGroupWithOrder($like_params,$eq_params, $date_params , $in_params, $other_params, $moreGroup){
		$query = OdOrder::find();
		$query->select($moreGroup+['count(1) as `ct`']);
		//与 getOrderListByCondition 共用 查询条件的封装函数
		self::_setOrderCondition($query, $like_params,$eq_params, $date_params , $in_params, $other_params);
		$data = $query->groupBy($moreGroup)->asArray()->all();
		return $data;
		
	}//end of getShipmethodGroupWithOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装设置 获取订单的信息的查询条件
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $like_params			like 相关的查询条件
	 * @param     $eq_params           	equal 相关的查询条件 需要指定刷选的fields以及值，field name要和字段名一样，值可以多个可能的，逗号隔开
	 *                              例如 array( order_status=>'200',
	 *                                       )
	 * @param     $date_params		   	时间范围的查询条件
	 * @param     $in_params		    in 相关的查询条件
	 * @param     $other_params		    other 就是一些子查询之类或者特殊的条件
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					OrderHelper::getOrderListByCondition();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function _setOrderCondition(&$query , $like_params,$eq_params, $date_params , $in_params, $other_params){
		return OrderGetDataHelper::setOrderCondition($query, $like_params, $eq_params, $date_params, $in_params, $other_params);
	}//end of function _setOrderCondition
		
		
	/**
	 +---------------------------------------------------------------------------------------------
	 * 自动 补全缺失 商品信息  为兼容oms2.1 的异常机制， 为oms2.0增加的一个data conversion 机制
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderId			string 	订单号
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					OrderHelper::_autoCompleteProductInfo('0001253');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/2/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function _autoCompleteProductInfo($orderId,$module='order',$action='提交物流',$FullName=''){
		global $CACHE;
		//check the product exist or not 
		$uid = \Yii::$app->subdb->getCurrentPuid();
		$isModel = false;
		//2016-07-04  为后台检测订单加上global cache 的使用方法 start
		if (isset($CACHE[$uid]['orderItem'][(int)$orderId])){
			$itemList = $CACHE[$uid]['orderItem'][(int)$orderId];
			
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' orderItem has cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}else{
			$itemList = OdOrderItem::findAll(['order_id'=>$orderId]);
			$isModel = true;
			//log 日志 ， 调试相关信息start
			$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' orderItem no cache';
			\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
			//log 日志 ， 调试相关信息end
		}
		
		//2016-07-04  为后台检测订单加上global cache 的使用方法 end
		$notExistList = [];
		
		$rt = ['success'=>true , 'message'=>''];
		foreach($itemList as $item){
			$result = self::generateProductByOrderItem($item, '',$module , $action , $FullName);
			if ($result['success'] == false){
				$rt['success'] = $result['success'];
				$rt['message'] .= $result['message'];
			}
		}//end of each item
		
		
		if ($isModel){
			$OrderInfo = [];
			//生成 后检查 rootsku 是否有保存
			foreach($itemList as $item){
				if (empty($item->root_sku)){
					if (isset($OrderInfo[$item->order_id]) ==false){
						$orderModel = OdOrder::findOne($item->order_id);
						$OrderInfo[$item->order_id]['platform'] = $orderModel->order_source;
						$OrderInfo[$item->order_id]['selleruserid'] = $orderModel->selleruserid;
					}
					
					$platform = $OrderInfo[$item->order_id]['platform'];
					$selleruserid = $OrderInfo[$item->order_id]['selleruserid'];
					//获取root sku
					$item->root_sku = \eagle\modules\catalog\apihelpers\ProductApiHelper::getRootSKUByAlias($item->sku,$platform, $selleruserid);
					if (!empty($item->root_sku)){
						$item->save();
					}
				}
			}
			
		}
		return $rt;
	}//end of _autoCompleteProductInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 订单商品 数据 生成 商品库数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $item			order item model 	订单商品model
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					OrderHelper::generateProductByOrderItem($item);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/8/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function generateProductByOrderItem(&$item , $sku='' , $module='order' , $action='生成sku' , $FullName=''){
		global $CACHE;
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if (empty($sku)){
			$sku = trim($item['sku']);
			//是否 别名 变量  =否
			$isAlias = false;
		}else{
			//是否 别名 变量    =是
			$isAlias = true;  
		}
		
		
		//去掉SKU中的换行符和tab
		$sku = str_replace('\r', '', $sku);
		$sku = str_replace('\n', '', $sku);
		$sku = str_replace('\t', '', $sku);
		$sku = str_replace(chr(10), '', $sku);
		
		if (strlen($sku) == 0){
			//continue;//假如sku 为空， 则以product name 为sku
			$sku = trim($item['product_name']);
		}
		
		$orderId = $item['order_id'];
		
		$hasProduct = \eagle\modules\catalog\apihelpers\ProductApiHelper::hasProduct($sku);
		$rt = ['success'=>true , 'message'=>''];
		if (empty($hasProduct)){
			
			//2016-07-04  为后台检测订单加上global cache 的使用方法 start
			if (isset($CACHE[$uid]['order'][(int)$orderId])){
				$order = $CACHE[$uid]['order'][(int)$orderId];
					
				//array 转object
				if (is_array($order))
					$order = (Object) $order;
					
				//log 日志 ， 调试相关信息start
				$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' order has cache';
				\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
				//log 日志 ， 调试相关信息end
			}else{
				$order = OdOrder::findone(['order_id'=>$orderId]);
					
				//log 日志 ， 调试相关信息start
				$GlobalCacheLogMsg = "\n puid=$uid ".(__function__).' order no cache';
				\eagle\modules\order\helpers\OrderBackgroundHelper::OrderCheckGlobalCacheLog($GlobalCacheLogMsg);
				//log 日志 ， 调试相关信息end
			}
		
			//2016-07-04  为后台检测订单加上global cache 的使用方法 end
			//cd平台虚拟sku不需要生成商品
			if(strtolower($order->order_source)=='cdiscount'){
				if(in_array($sku, CdiscountOrderInterface::getNonDeliverySku())){
					return $rt;
				}
			}
			
			if ($isAlias && trim($item['sku']) != $sku) {
				//是否 别名 变量
				$alias = ['alias_sku'=>trim($item['sku']),'pack'=>'1','forsite'=>$order->order_source,'comment'=>''];
			}
			//获取产品的刊登类目，参数有平台和产品id
			$product_name = OrderApiHelper::getOrderProductCategory($item['order_source_itemid'], $order->order_source);
		
			//读取常用报关信息
			$tmpCommonDeclaredInfo = \eagle\modules\carrier\helpers\CarrierOpenHelper::getCommonDeclaredInfoByDefault($uid);
		
			if(!empty($tmpCommonDeclaredInfo['id'])){
				$product_name['ch'] = $tmpCommonDeclaredInfo['ch_name'];
				$product_name['en'] = $tmpCommonDeclaredInfo['en_name'];
				$product_name['declared_value'] = $tmpCommonDeclaredInfo['declared_value'];
				$product_name['declared_weight'] = $tmpCommonDeclaredInfo['declared_weight'];
				$product_name['detail_hs_code'] = $tmpCommonDeclaredInfo['detail_hs_code'];
			}else{
				$product_name['declared_value'] = $item['price']*0.2;
				$product_name['declared_weight'] = 50;
				$product_name['detail_hs_code'] = '';
			}
		
			//组织商品数据
			$productInfo = [];
			$productInfo['name'] = $item['product_name'];
			$productInfo['prod_name_ch']=$item['product_name'];//类目中文翻译
			$productInfo['prod_name_en']=$item['product_name'];//类目
			$productInfo['photo_primary']=(empty($item['photo_primary']))?'http://v2.littleboss.com/images/batchImagesUploader/no-img.png':$item['photo_primary'];
			$productInfo['declaration_ch']=$product_name['ch'];//类目中文翻译
			$productInfo['declaration_en']=$product_name['en'];//类目
			$productInfo['declaration_value_currency']=$order->currency;
			$productInfo['declaration_value']=$product_name['declared_value'];
			$productInfo['prod_weight']=$product_name['declared_weight'];//克
			$productInfo['battery']='N';
			$productInfo['platform']=$order->order_source;
			$productInfo['itemid']=$item['order_source_itemid'];
			$productInfo['detail_hs_code']=$product_name['detail_hs_code'];
			$productInfo['other_attr'] = empty($alias) ?[]: ['aliases'=>[$alias]];
			
			//生成商品
			$result = ProductApiHelper::explodeSkuAndCreateProduct($sku, $uid, $productInfo);
			$skus = Helper_Array::getCols($result, 'sku');
			foreach ($result as $one){
				if ($one['success']==false){
					$rt['success'] = false;
					$rt['message'] .= $one['message'].'\n';
				}
			}
		
		
			if ($rt['success'] == true){
				if (!empty($FullName)){
					OperationLogHelper::log($module,$order->order_id,$action,'系统自动生成商品:'.implode(',', $skus).'，如有需要到商品库修改对应信息',$FullName);
				}else{
					OperationLogHelper::log($module,$order->order_id,$action,'系统自动生成商品:'.implode(',', $skus).'，如有需要到商品库修改对应信息',\Yii::$app->user->identity->getFullName());
				}
				
				OrderUpdateHelper::saveItemRootSKU($item, $sku,true , $FullName,$action,$module);
			}
			
		}//end of create prouct
		else{
			//sku 已经存在， 只需要绑定别名
			$trim_sku = trim($item['sku']);
			$isEmptySku = empty($trim_sku);
			if($sku != trim($item['sku'])  &&  ($isEmptySku==false)  ){
				$order = OdOrder::findone(['order_id'=>$orderId]);
				if(strtolower($order->order_source)=='cdiscount'){
					if(in_array($sku, CdiscountOrderInterface::getNonDeliverySku())){
						return $rt;
					}
				}
				$alias = [trim($item['sku'])=>['alias_sku'=>trim($item['sku']),'pack'=>'1','forsite'=>$order->order_source,'comment'=>'']];
				$rt = ProductApiHelper::addSkuAliases($sku, $alias ,$order->order_source , $order->selleruserid );
				WarehouseHelper::RefreshOneQtyOrdered($sku);
			}
		}
		return $rt;
	}//end of generateProductByOrderItem
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 判断该平台 是否2.1的流程
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $platform			string 	平台
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean	true  为2.1 ， false 为2.0
	 *
	 * @invoking					OrderHelper::isProcess21('ebay');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/10				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function isProcess21($platform){
// 		return in_array($platform, ['aliexpress','cdiscount', 'priceminister' , 'bonanza', 'amazon', 'lazada', 'linio', 'jumia','ebay', 'dhgate','wish']);
	   return true;
	}//end of isProcess21
	
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
	 * @invoking					OrderHelper::listUnbindingAcount('aliexpress');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function listUnbindingAcount($platform = 'all'){
		$unbingdingAccount = [];
		if (in_array($platform, ['all','aliexpress'])){
			$aliUnbinding = AliexpressOrderHelper::listUnbindingAcount();
			$unbingdingAccount['aliexpress'] = $aliUnbinding;
		}
		return $unbingdingAccount;
	}//end of listUnbindingAcount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计 已付款状态下， 是否有可合并的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			model 	订单model 
	 * @param	  $isAll			boolean 是否获取所有可以合并的已付款订单 true 为所有 （含不合并发货的订单） ， false 为不含 不合并发货的订单
	 +---------------------------------------------------------------------------------------------
	 * @return						array	['001','002']
	 *
	 * @invoking					OrderHelper::listWaitMergeOrder($order);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/18				初始化
	 * @author		dzt		2016/7/25				添加过滤合并原始订单,屏蔽不合并订单过滤
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function listWaitMergeOrder($order , $isAll = false){
		if(!($order instanceof OdOrder)){
			if(is_array($order) && $order['order_id']){
				$order=OdOrder::find()->where(['order_id'=>$order['order_id']])->one();
				if($order->isNewRecord){
					return ['success'=>false , 'message'=>'E1.'.$order['order_id'].'找不到订单'];
				}
			}if(is_string($order) || is_int($order)){
				$order=OdOrder::find()->where(['order_id'=>$order])->one();
				if($order->isNewRecord){
					return ['success'=>false , 'message'=>'E1.'.$order['order_id'].'找不到订单'];
				}
			}else{
				return ['success'=>false , 'message'=>'E2.参数不对'];
			}
		}
		$OrderList = OdOrder::find()->select(['order_id'])->where(['selleruserid'=>$order->selleruserid , 'consignee'=>$order->consignee , 
				'consignee_address_line1'=>$order->consignee_address_line1 ,
				'consignee_address_line2'=>$order->consignee_address_line2 ,
				'consignee_address_line3'=>$order->consignee_address_line3 ,'order_status'=>OdOrder::STATUS_PAY ,
				'source_buyer_user_id'=>$order->source_buyer_user_id ])
			->andWhere(['not',['order_id'=>$order->order_id]])->andWhere(['not',['order_relation'=>'fm']])->asArray()->all();
		/*
		echo OdOrder::find()->select(['order_id'])->where(['selleruserid'=>$order->selleruserid , 'consignee'=>$order->consignee , 
				'consignee_address_line1'=>$order->consignee_address_line1 ,
				'consignee_address_line2'=>$order->consignee_address_line2 ,
				'consignee_address_line3'=>$order->consignee_address_line3 ,'order_status'=>OdOrder::STATUS_PAY ,
				'source_buyer_user_id'=>$order->source_buyer_user_id ])
			->andWhere(['not',['order_id'=>$order->order_id]])->andWhere(['not',['order_relation'=>'fm']])->createCommand()->getRawSql();
		*/
		$rt = [];
		foreach($OrderList as $row){
			if (empty($isAll)){
				//检查是否有打上不合并发货的订单
				//$tmp = OrderApiHelper::isSkipMergeOrder($row['order_id']);
				//if (empty($tmp))
					$rt[] = $row['order_id'];
			}else{
				$rt[] = $row['order_id'];
			}
			
		}
		return $rt;
	}//end of listWaitMergeOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置  已付款状态下的订单称到  待合并的状态
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $targetOrderId			string 	小老板订单号
	 * @param	  $orderIdList				array  关联的小老板号
	 +---------------------------------------------------------------------------------------------
	 * @return						array	['001','002']
	 *
	 * @invoking					OrderHelper::listWaitMergeOrder($order);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setRelateOrderWaitSend($targetOrderId, $orderIdList , $module='order' , $action='检测订单' ,$name=''){
		if (empty($name))
			$name=\Yii::$app->user->identity->getFullName();
		$rt = ['success'=>true , 'order_id_list'=>[]];
		$relateOrderList = OdOrder::find()->where(['order_id'=>$orderIdList  ,'order_status'=>OdOrder::STATUS_PAY])->all();
		foreach($relateOrderList as $order){
			$order->pay_order_type = OdOrder::PAY_EXCEPTION;
			$order->exception_status = OdOrder::EXCEP_WAITMERGE;
			if ($order->save()){
				//成功写用户日志log
				OperationLogHelper::log($module,$order->order_id,$action,'检查到该订单可以与'.$targetOrderId.' 合并 ， 添加异常标签:['.OdOrder::$exceptionstatus[OdOrder::EXCEP_WAITMERGE].']',$name);
			}else{
				$rt['success'] = false;
				$rt['order_id_list'][] = $order->order_id;
				//出错 写数据库log
				\Yii::error(["Order",__CLASS__,__FUNCTION__,"failure to set :".$order->order_id.' relate to '.$targetOrderId.'error msg : '.json_encode($order->errors)],'edb\global');
			}
		}
		
		return $rt;
	}//end of setRelateOrderWaitSend
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据标记发货结果， 自动 设置订单标记发货状态 ， 当有任何一条标记发货成功的记录， 该订单的物流发货状态都为1
	 * shipping status 1 为同步成功  0为未同步 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order_source_order_id			string 	平台订单号（ps 不是小老板 订单号）
	 * @param	  $success							boolean  标记发货是否成功 true 为成功， false 为失败
	 * @param     delivery_time						-1 or time  平台发货时间 -1表示 取当前的时间， 否则取 此变量值
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean
	 *
	 * @invoking					OrderHelper::setOrderShippingStatus($order_source_order_id ,$success);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderShippingStatus($order_source_order_id ,$success , $delivery_time=-1){
		$osObj = OdOrder::findOne(['order_source_order_id'=> $order_source_order_id]);
		if (!is_null($osObj)){
			if ($success){
				$osObj->shipping_status=1;
				if ($delivery_time == -1){
					$osObj->delivery_time = time();
				}else{
					$osObj->delivery_time = $delivery_time;
				}
			}else{
				if ($osObj->delivery_time >0){
					$osObj->shipping_status=1;
				}else{
					$osObj->shipping_status=0;
				}
			}
			return $osObj->save();
		}
		return false;
	}//end of setOrderShippingStatus

	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据订单的流程生成 操作列表数组
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_status			订单状态
	 * 			$type					s = single 单独操作 ， b = batch 批量操作
	 * 			$code					当前操作的订单流程关键值
	 * 			$platform				平台专有操作
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		million		2016/05/13				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getCurrentOperationListNew($order_status,$type="s",$code=null,$platform=null){
		//$delivery_picking_mode 是否使用拣货模式 0:表示不使用,1:表示使用
// 		$delivery_picking_mode = ConfigHelper::getConfig('delivery_picking_mode');
// 		$delivery_picking_mode = empty($delivery_picking_mode) ? 0 : $delivery_picking_mode;
		
		//通用操作
		$BaseOperationList = [
				//'addMemo'=>'添加备注',
				//'generateProduct'=>'生成商品',//后期将去除，用导出表格的方式，让用户获得订单商品数据，修改之后导入商品模块
				//'stockManage'=>'库存处理',//后期将去除，用导出表格的方式，让用户获得订单商品数据，修改之后导入商品模块
				];
		//通用批量操作
		$BaseBatchOperationList = [
				''=>'批量操作',
				];
		//通用单独操作
		$BaseSingleOperationList = [
				''=>'操作',
				'editOrder'=>'编辑订单',
				'history'=>'查看订单操作日志',
				];
		#####################还没实现测操作start#####################################################
		//'abandonOrder'=>'废弃订单',
		//'addCustomTag'=>'添加修改自定义标签',
		//'sendEmail'=>'发送邮件',
		//'transferStorage'=>'转仓处理',
		//'createPurchase'=>'生成采购单',
		//'inStock'=>'采购到货',
		//'refundOrder'=>'退款',
		//'returnGoods'=>'退货',
		//'createReturn'=>'生成退货单',
		//'completeReturn'=>'退货完成',
		//'createRefund'=>'生成退款单',
		//'completeReturn'=>'退款完成',
		//'abandonOrder'=>'彻底删除',
		//'recoveryOrder'=>'回收',
		//'replyMessage'=>'回复留言',
		#####################操作集合#####################################################
		$allActions = [
		'uploadSubmitNew'=>'上传',
		'uploadAndDispatch'=>'上传且交运',
		'editCustomsInfo'=>'修改报关信息',
		'dodispatch'=>'交运',
		'getTrackNo'=>'获取跟踪号',
		'moveToUpload'=>'重新上传',
		'moveToUpload2'=>'重新导出',
		'setTrackNum'=>'分配跟踪号',
		'moveToPacking'=>'移到打包出库',
// 		'buildDeliveryId'=>'生成拣货单',
		//'todistribution'=>'移到分拣配货',
		'signpayed'=>'确认到款',
		'checkorder'=>'检查订单',
		'changeWHSM'=>'指定运输服务',
		'changeWHSM2'=>'更改运输服务',
		'autocarrierservice'=>'匹配发货仓库和运输服务',
		'reautocarrierservice'=>'重新匹配运输服务',
		'mergeorder'=>'合并订单',
		'signwaitsend'=>'提交物流或发货',
		'reorder'=>'重新发货',
		//'reorder2'=>'已出库订单补发',
		'printDistribution'=>'打印配货单',
// 		'completepicking'=>'完成拣货',
// 		'moveStatusToOut'=>'配货完成',
		'suspendDelivery'=>'暂停发货',
		'outOfStock'=>'标记缺货',
		'repulse_paid'=>'打回已付款',
		'signshipped'=>'虚拟发货',//或虚假发货	/通知平台发货
		'givefeedback'=>'给买家好评',
		'cancelorder'=>'取消订单',
		//'skipMerge'=>'不合并发货',//后期将去除，只需要提交物流或发货到发货之后就不用合并订单了
		'signcomplete'=>'标记为已完成',//此功能为了兼容2.0面的还在发货中的订单实际已经发货的订单进行批量移动到已完成
		'setFinished'=>'确认发货完成',
		
		//'9'=>'重新分配跟踪号',
		
		'moveToUpload3'=>'重新分配',
		'printGaoqingInvoice'=>'打印高青发票',
		'addMemo'=>'添加备注',
		'addProductUrl'=>'添加商品链接（wish邮）'
		];
		######################################速卖通特有操作##################################################
		$aliexpress = [
		'extendsBuyerAcceptGoodsTime'=>'延长买家收货时间',//速卖通特有
		'refreshOrder'=>'删除并重新同步订单',//速卖通特有
		];
		switch ($order_status){
			/****************************** 未付款start *********************************/
			case OdOrder::STATUS_NOPAY:
				$do = array('确认到款','取消订单');
				break;
				/****************************** 已付款start *********************************/
			case OdOrder::STATUS_PAY:
				$do = array('检查订单','取消订单','指定运输服务','重新匹配运输服务','合并订单','提交物流或发货','暂停发货','标记缺货','虚拟发货','给买家好评','标记为已完成');
				break;
				/****************************** 发货 start *********************************/
			case OdOrder::STATUS_WAITSEND:
				switch ($code){
					case '指定发货仓库和运输服务':
						$do = array('标记为已完成','暂停发货','标记缺货');
						break;
					case '接口上传':
						$do = array('确认发货完成','上传','更改运输服务','移到打包出库','重新匹配运输服务','暂停发货','标记缺货','打回已付款','打印配货单','添加备注','标记为已完成','添加商品链接（wish邮）');
						break;
					case '接口交运':
						$do = array('确认发货完成','获取跟踪号','更改运输服务','移到打包出库','暂停发货','标记缺货','打回已付款','打印配货单','添加备注','标记为已完成','添加商品链接（wish邮）');
						break;
					case '接口已交运':
						$do = array('重新上传','更改运输服务','移到打包出库','暂停发货','标记缺货','打回已付款','打印配货单','添加备注','标记为已完成','添加商品链接(测试中)');
						break;
					case '接口已完成':
						$do = array('重新上传','获取跟踪号','更改运输服务','标记为已完成','打回已付款');
						break;
					case 'excel未导出':
						$do = array('确认发货完成','更改运输服务','移到打包出库','重新匹配运输服务','暂停发货','标记缺货','标记为已完成','打回已付款','添加备注','添加商品链接（wish邮）');
						break;
					case 'excel已导出':
						$do = array('重新导出','确认发货完成','移到打包出库','更改运输服务','重新匹配运输服务','暂停发货','标记缺货','标记为已完成','打回已付款','添加备注','添加商品链接（wish邮）');
						break;
					case 'excel已完成':
						$do = array('重新导出','更改运输服务','重新匹配运输服务','标记为已完成','打回已付款','添加备注','添加商品链接(测试中)');
						break;
					case '未分配':
						$do = array('确认发货完成','更改运输服务','重新匹配运输服务','移到打包出库','暂停发货','标记缺货','标记为已完成','添加备注','打回已付款','添加商品链接（wish邮）');
						break;
					case '已分配':
						$do = array('确认发货完成','移到打包出库','更改运输服务','重新匹配运输服务','暂停发货','标记缺货','重新分配','打印高青发票','标记为已完成','添加备注','打回已付款','添加商品链接（wish邮）');
						break;
					case '已完成':
						$do = array('重新分配','更改运输服务','重新匹配运输服务','打回已付款','添加备注','添加商品链接（wish邮）');
						break;
					case '未拣货':
						$do = array('打印配货单','确认发货完成','完成拣货','暂停发货','标记缺货','打回已付款');
						break;
					case '拣货':
						$do = array('打印配货单','确认发货完成','暂停发货','标记缺货','打回已付款');
						break;
					case '分拣配货':
						$do = array('打印配货单','确认发货完成','配货完成','暂停发货','标记缺货','打回已付款');
						break;
					default:
						$do = array('打印配货单','确认发货完成','暂停发货','标记缺货','打回已付款');
						break;
				}
				$do = array_merge($do,array('虚拟发货'));//$do + array('通知平台发货','暂停发货','标记缺货');//要加的动作'标记为已完成','取消订单','通知平台发货','给买家好评'
				break;
				/****************************** 已完成   start  *********************************/
			case OdOrder::STATUS_SHIPPED:
				switch ($code){
					case '发货已完成':
						$do = array('重新发货','虚拟发货','打印配货单','获取跟踪号');
						break;
					default:
						$do = array('重新发货','虚拟发货','给买家好评','获取跟踪号','打印配货单');
						break;
				}
				break;
				/****************************** 已取消    start  *********************************/
			case OdOrder::STATUS_CANCEL:
				$do = array('重新发货');
				break;
				/****************************** 暂停发货    start  *********************************/
			case OdOrder::STATUS_SUSPEND:
				$do = array('重新发货','取消订单','虚拟发货','给买家好评');
				break;
				/****************************** 缺货    start  *********************************/
			case OdOrder::STATUS_OUTOFSTOCK:
				$do = array('重新发货','取消订单','虚拟发货','给买家好评');
				break;
			default:
				break;
		}
		//注销操作项
		foreach ($allActions as $key=>$value){
			if (!in_array($value, $do)){
				unset($allActions[$key]);
			}
		}
		
		//当使用拣货模式时才显示拣货功能
// 		if($delivery_picking_mode == 0){
// 			unset($allActions['moveToPacking']);
// 			unset($allActions['buildDeliveryId']);
// 			unset($allActions['completepicking']);
// 			unset($allActions['moveStatusToOut']);
// 		}
		
		//流程特有的操作
		switch ($platform){
			/****************************** 未付款start *********************************/
			case 'aliexpress':
				$allActions += $aliexpress;
				break;
			default:
				break;
		}
		//批量还是单独操作
		if ($type == 's'){
			return $BaseSingleOperationList + $BaseOperationList + $allActions;
		}else{
			return $BaseBatchOperationList + $BaseOperationList + $allActions;
		}
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 合并订单
	 * 1.只能合并已付款订单
	 * 2.根据需要合并的订单新成一张新的订单   原始订单order_relation 为fm father merge ；
	 * 3.新建的订单  order_relation 为 sm son merge  并汇总金额，商品。
	 * 4.在od_order_relation  中增加父子订单的关系 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderIdList			array	 	小老板 订单号
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 *
	 * @invoking					OrderHelper::mergeOrder($orderIdList );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function mergeOrder($orderIdList,$module='order' , $action='合并订单'){
		//1.只能合并已付款订单等相关
		$orderIdList = array_unique($orderIdList);
		$orders = OdOrder::find()->where(['order_id'=>$orderIdList])->andWhere(['in','order_status',OdOrder::STATUS_PAY])->all();
		$error = "";
		$_tmporder = $orders[0];
		$ismerge = true;//判断选择的订单是否符合合并，买家名，地址等是否一样
		$toWipeMergedSonOrders = array(); // 已合并订单合 按原始订单合并后要删除
		
		if (count($orders)<2||count($orders)!=count($orderIdList)){
			$ismerge = false;
			$error='合并订单数必须大于2,或传入的订单数ID与系统获取的订单数不符';
		}
		
		$list_clear_platform = []; //需要清除redisear_platform
		if (!empty($orders)){
			foreach($orders as $order){
			
				if ($order->order_status != OdOrder::STATUS_PAY){
					$error .= $order->order_id."订单状态为".OdOrder::$status[$order->order_status]."不能合并！";
					$ismerge = false;
					break;
				}
				
				if ($order->order_relation == "fm"){
				    $error .= $order->order_id."订单已被合并，不能再合并！";
				    $ismerge = false;
					break;
				}
			
				if ($order->selleruserid != $_tmporder->selleruserid
				||$order->source_buyer_user_id != $_tmporder->source_buyer_user_id
				||strtolower($order->consignee) != strtolower($_tmporder->consignee)
				||$order->consignee_address_line1 != $_tmporder->consignee_address_line1){
					$ismerge = false;
					$error='合并订单的收件人等信息必须一致';
					break;
				}
				if ($order->order_source != $_tmporder->order_source||
				$order->currency != $_tmporder->currency){
					$ismerge = false;
					$error='合并订单的平台及币种必须一致';
					break;
				}
			
			}
			if (empty($ismerge)){
				return ['success'=>false , 'message'=>$error];
			}
			else{
				//开始合并订单
				//获取 旧订单所有的数据
				$newOrder = $_tmporder->attributes;
			
				unset($newOrder['order_id']);
				//时间 重置
				$newOrder['create_time'] = time();
				$newOrder['update_time'] = time();
				$newOrder['order_relation'] = 'sm'; // son merge
				$newOrder['exception_status'] = 0;
				
				//更新新订单总金额  ， 运费 
				$shipping_cost = 0;
				$subtotal=0;
				$grand_total=0;
				$commission_total = 0;
				
				$originOrderIdList = array();
				$toWipeMergedSonOrderIds = array();
				foreach($orders as $order){
					$shipping_cost+=$order->shipping_cost;
					$subtotal+=$order->subtotal;
					$grand_total+=$order->grand_total;
					$commission_total+=$order->commission_total;
					
					//已合并订单再合并时 绑定关系的是通过原始订单绑定
					if('sm' == $order->order_relation){
						$father_orderids = OrderRelation::findAll(['son_orderid'=>$order->order_id , 'type'=>'merge' ]);
						foreach ($father_orderids as $father_orderid){
						    if(!in_array($father_orderid->father_orderid, $originOrderIdList))// dzt20190403 add if
							$originOrderIdList[] = $father_orderid->father_orderid;
						}
						
						$toWipeMergedSonOrderIds[] = $order->order_id;
					}else{
					    if(!in_array($order->order_id, $originOrderIdList))// dzt20190403 add if
						$originOrderIdList[] = $order->order_id;
					} 
					
					//取最小的剩余发货时间
					if($newOrder['fulfill_deadline'] > $order->fulfill_deadline){
						$newOrder['fulfill_deadline'] = $order->fulfill_deadline;
					}
				}
				
				$newOrder['shipping_cost'] = $shipping_cost;
				$newOrder['subtotal'] = $subtotal;
				$newOrder['grand_total'] = $grand_total;
				$newOrder['commission_total'] = $commission_total;
				
				$newOrder['ismultipleProduct'] = 'Y'; //多品标志
					
				$rt = self::doInsert($newOrder);
				if (!empty($rt['success'])){// 补0， 否则写入operation log后读取有问题
					$son_order_id = str_pad( $rt['order_id'],11,'0',STR_PAD_LEFT);
				}
			
				//获取订单 item信息
				// dzt20190403 合成订单子产品以原始订单子产品为基准，不以选择待合并的订单为基准，去除可能出现的重复
				// $itemList = OdOrderItem::find()->where(['order_id'=>$orderIdList])->asArray()->all();
				$itemList = OdOrderItem::find()->where(['order_id'=>$originOrderIdList])->asArray()->all();
			
				//重写订单 item信息
				foreach($itemList as &$thisItem){
					unset($thisItem['order_item_id']);//primary key release
					$thisItem['order_id'] = $son_order_id;//order id overwrite
			
					$thisItem['create_time'] = time();
					$thisItem['update_time'] = time();
				}
			
				if (!empty($itemList)){
					$order_id = $son_order_id;
					//是否更新待发货数量逻辑
					$doPIp_updatePendingQty = false;
					if(isset($newOrder['order_type'])){
						if (!in_array(strtoupper($newOrder['order_type']), ['AFN' , 'FBC'])){
							$doPIp_updatePendingQty = true;
						}else{
							$doPIp_updatePendingQty = false;
						}
					}else{
						$doPIp_updatePendingQty = true;
					}
					
					if ($doPIp_updatePendingQty){
						if (in_array($newOrder['order_status'], [OdOrder::STATUS_PAY])){
							$doPIp_updatePendingQty = true;
						}else{
							$doPIp_updatePendingQty = false;
						}
					}
					$effect = self::doProductImport($itemList , $order_id, $newOrder['order_source'],$doPIp_updatePendingQty,$newOrder['order_status'],$newOrder['selleruserid']);
						
					if ($effect == count($itemList)){
							
						//旧订单生成  行为日志
						foreach($originOrderIdList as $tmpOrderId){
							OperationLogHelper::log($module,(int)$tmpOrderId,$action,'合并订单，生成新的小老板单号为:'.$order_id,\Yii::$app->user->identity->getFullName());
						}
						
						//新订单生成  行为日志
						OperationLogHelper::log($module,(int)$order_id,$action,'合并订单， 被操作合并的订单:'.implode(', ', $orderIdList).'， 来源的小老板单号为:'.implode(',', $originOrderIdList),\Yii::$app->user->identity->getFullName());
						
						//写入操作日志
						UserHelper::insertUserOperationLog('order', "合并订单, 新订单: ".$order_id.", 被操作合并的订单:".implode(', ', $orderIdList).", 来源订单: ".implode(', ', $originOrderIdList));
							
						//$result['success']++; // 统计成功订单数量
						
						//设置原始订单 order relation 为fm
						$effectedRows = OdOrder::updateAll(['order_relation'=>'fm'],['order_id'=>$originOrderIdList]);
						
						//通知dashboard 减去 n 个被合并订单的 左侧菜单计数器，销量统计则不需要减掉，同理，当doInsert new order，如果是合并的订单，则销量统计不加上去
						$puid=\Yii::$app->subdb->getCurrentPuid();
						//DashBoardStatisticHelper::OMSStatusAdd2($puid, $_tmporder->order_source, $_tmporder->order_status, 0 - $effectedRows,"This n orders has been Merged, so minus their head count ");
						DashBoardStatisticHelper::OMSStatusAdd2($puid, $_tmporder->order_source, $_tmporder->selleruserid, $_tmporder->order_status, 0 - $effectedRows,"This n orders has been Merged, so minus their head count ");
						DashBoardHelper::checkOrderStatusRedisCompareWithDbCount($puid, $_tmporder->order_source, $_tmporder->order_status, $_tmporder->order_status, "orders Merged");
						
						//保存 order relation 的关系
						$fatherIdList = $originOrderIdList;
						$sonIdList = [$son_order_id];
						OrderHelper::saveOrderRelation($fatherIdList  , $sonIdList , 'merge');
						
						// 已合并订单再合并完成后，抹掉原来的合并订单数据
						foreach ($toWipeMergedSonOrderIds as $toWipeMergedSonOrderId){
							//删除对应订单关联
							$rt = OrderRelation::deleteAll(['son_orderid'=>$toWipeMergedSonOrderId , 'type'=>'merge']);
							
							//删除系统标签
							OrderTagHelper::DelOrderSysTag($toWipeMergedSonOrderId, 'all');
							
							//删除合并的合并出来新订单
							OdOrderItem::deleteAll(['order_id'=>$toWipeMergedSonOrderId]);
							OdOrder::deleteAll(['order_id'=>$toWipeMergedSonOrderId]);
						}
						
						// 立即检查订单，以便再合并
						$newOrder = OdOrder::findOne($son_order_id);
						$newOrder->checkorderstatus("System");
						
						//合并订单后清空运输服务
						$newOrder->default_carrier_code = '';
						$newOrder->default_shipping_method_code = '';
						
						$newOrder->save();
						
						//更新虚拟发货队列
						try {
							QueueShippedHelper::setOrderSyncShippedQueueOrderRelation($originOrderIdList, $newOrder->order_source, $newOrder->selleruserid, 'fm');
						} catch (\Exception $e) {
							$error .= $e->getMessage () ;
						}
						
						//增加清除 平台redis
						if (!in_array($newOrder->order_source, $list_clear_platform)){
							$list_clear_platform[] = $newOrder->order_source;
						}
					}else{
						\Yii::error((__FUNCTION__)." : ".$order_id.'保存相关商品失败 ！ ',"file");
						$msg .=$order_id."保存相关商品失败！\n";
						//$result['failure']++; // 统计失败订单数量
						if (empty($effect)){
							//假如 没有任何商品都保存成功则删除订单头
							OdOrder::deleteAll(['order_id'=>$order_id]); //考虑性能问题， 还是不采用事务方式
						}
						$error .= $msg;
					}
				}//end of item list
				
				//left menu 清除redis
				if (!empty($list_clear_platform)){
					OrderHelper::clearLeftMenuCache($list_clear_platform);
				}
				if (!empty($error)){
					return ['success'=>false , 'message'=>$error];
				}else{
					return ['success'=>true , 'message'=>''];
				}
				
			}
		}else{
			return ['success'=>false , 'message'=>'找不到有效的订单'];
		}
		//
	}//end of mergeOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 取消 合并订单
	 * 1.只能合并已付款订单
	 * 2.根据需要合并的订单新成一张新的订单   原始订单order_relation 为fm father merge ；
	 * 3.新建的订单  order_relation 为 sm son merge  并汇总金额，商品。
	 * 4.在od_order_relation  中增加父子订单的关系
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderIdList			array	 	小老板 订单号
	 * @param	  $resonMsg				string		
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 *
	 * @invoking					OrderHelper::mergeOrder($orderIdList );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function RollbackmergeOrder($orderIdList, $resonMsg=''){
		$orderList = OdOrder::find()->where(['order_id'=>$orderIdList])->asArray()->all();
		$error_msg = '';
		
		$module='order';
		$action='拆分订单';
		if(empty($orderList)){
			$error_msg .= '订单：'.implode(',', $orderIdList).'不存在';
		}
		$list_clear_platform = []; //需要清除redisear_platform
		foreach($orderList as $order){
			//是否合并过订单
			if ($order['order_relation'] =='sm'){
				//删除合并的合并出来新订单
				OdOrderItem::deleteAll(['order_id'=>$order['order_id']]);
				
				$effectedRows = OdOrder::deleteAll(['order_id'=>$order['order_id']]);
				
				//通知dashboard 减去 n 个被合并订单的 左侧菜单计数器，销量统计则不需要减掉，同理，当doInsert new order，如果是合并的订单，则销量统计不加上去
				$puid=\Yii::$app->subdb->getCurrentPuid();
				//DashBoardStatisticHelper::OMSStatusAdd2($puid, $order['order_source'], $order['order_status'], 0 - $effectedRows,"rollback Merged, so minus the father order");
				DashBoardStatisticHelper::OMSStatusAdd2($puid, $order['order_source'], $order['selleruserid'], $order['order_status'], 0 - $effectedRows,"rollback Merged, so minus the father order");
				DashBoardHelper::checkOrderStatusRedisCompareWithDbCount($puid, $order['order_source'], $order['order_status'], $order['order_status'], "orders rollback Merged");
				
				//删除标记发货队列
				/*
				 $currentOrderSourceIdList = [];
				$OriginList = OdOrder::find()->where(['order_id'=>$currentFatherOrderId])->asArray()->all();
				foreach($OriginList as $row){
				$currentOrderSourceIdList[]= $row['order_source_order_id'];
				}
				QueueSyncshipped::deleteAll(['order_source_order_id'=>$currentOrderSourceIdList]);
				*/
				
				//删除系统标签
				OrderTagHelper::DelOrderSysTag($order['order_id'], 'all');
				
				//合并后的原始订单类型改为正常
				$currentRelation = OrderRelation::find()->where(['son_orderid'=>$order['order_id'] , 'type'=>'merge'])->asArray()->all();
				$currentFatherOrderId = [];
				
				foreach($currentRelation as $row){
					$row['father_orderid'] = str_pad($row['father_orderid'],11,'0',STR_PAD_LEFT);
					$currentFatherOrderId[] = $row['father_orderid'];
					if(empty(\Yii::$app->user)){
						OperationLogHelper::log($module,$row['father_orderid'],$action,'从小老板单号:'.$order['order_id']."中拆分订单。".$resonMsg,"System");
					}else{
						OperationLogHelper::log($module,$row['father_orderid'],$action,'从小老板单号:'.$order['order_id']."中拆分订单。".$resonMsg,\Yii::$app->user->identity->getFullName());
					}
					
				}
				
				$rt = OdOrder::updateAll(['order_relation'=>'normal','exception_status'=>OdOrder::EXCEP_WAITMERGE,'order_status'=>OdOrder::STATUS_SUSPEND],['order_id'=>$currentFatherOrderId]);
				//通知dashboard 减去 n 个被合并订单的 左侧菜单计数器，销量统计则不需要减掉，同理，当doInsert new order，如果是合并的订单，则销量统计不加上去
				$puid=\Yii::$app->subdb->getCurrentPuid();
				//DashBoardStatisticHelper::OMSStatusAdd2($puid, $order['order_source'], $order['order_status'],  $rt,"rollback Merged, so add the children orders head count");
				DashBoardStatisticHelper::OMSStatusAdd2($puid, $order['order_source'], $order['selleruserid'], $order['order_status'],  $rt,"rollback Merged, so add the children orders head count");
				DashBoardHelper::checkOrderStatusRedisCompareWithDbCount($puid, $order['order_source'], $order['order_status'], OdOrder::STATUS_SUSPEND, "orders rollback Merged");
				RedisHelper::delOrderCache( \Yii::$app->subdb->getCurrentPuid() , strtolower($order['order_source']) );
				
				if ($rt != count($currentFatherOrderId)){
					$errorMsg = (__FUNCTION__)." : ".json_encode($currentFatherOrderId).' 关联状态更改失败 ！目标更新='.count($currentFatherOrderId).'实际更新= '.$rt;
					\Yii::error($errorMsg,"file");
					$error_msg .= $order['order_id']."取消合并操作发生内部错误E003<br>";
				}
				//删除对应订单关联
				$rt = OrderRelation::deleteAll(['son_orderid'=>$order['order_id'] , 'type'=>'merge']);
				if ($rt != count($currentFatherOrderId)){
					$errorMsg = (__FUNCTION__)." : ".json_encode($currentFatherOrderId).' 清除关联表异常 ！目标删除='.count($currentFatherOrderId).'实际删除= '.$rt;
					\Yii::error($errorMsg,"file");
					$error_msg .= $order['order_id']."取消合并操作发生内部错误E004<br>";
				}
				
				//更新虚拟发货队列
				try {
					QueueShippedHelper::setOrderSyncShippedQueueOrderRelation($currentFatherOrderId, $order['order_source'], $order['selleruserid'], 'normal');
				} catch (\Exception $e) {
					$error_msg .= $e->getMessage () ;
				}
				
				if(!empty($currentFatherOrderId)){
					//写入操作日志
					UserHelper::insertUserOperationLog('order', "取消合并, 合并订单: ".$order['order_id'].", 拆分为: ".implode(', ', $currentFatherOrderId));
				}
				
				//增加清除 平台redis
				if (!in_array($order['order_source'], $list_clear_platform)){
					$list_clear_platform[] = $order['order_source'];
				}
				
			}else{
				//不是合并订单不能操作
				$error_msg .= $order['order_id'].'不能取消合并操作';
			}
			
		}//end of each order 
		
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
		}
		if (!empty($error_msg)){
			return ['success'=>false,'message'=>$error_msg];
		}else{
			return ['success'=>true,'message'=>''];
		}
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 
	 * 1.只能合并已付款订单
	 * 2.根据需要合并的订单新成一张新的订单   原始订单order_relation 为fm father merge ；
	 * 3.新建的订单  order_relation 为 sm son merge  并汇总金额，商品。
	 * 4.在od_order_relation  中增加父子订单的关系 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $fatherIdList			array	 	小老板 订单号 合并的 , 拆分， 原始订单都放在father的位置
	 * @param     $sonIdList			array	 	小老板 订单号 合并的 , 拆分， 生成 的订单号都放在son的位置
	 * @param     $type					array	 	小老板 订单号merge为合并,split拆分
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 *
	 * @invoking					OrderHelper::saveOrderRelation($fatherIdList  , $sonIdList , $type);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveOrderRelation($fatherIdList , $sonIdList , $type){
		foreach($fatherIdList as $foId){
			foreach($sonIdList as $soId){
				$hasRealtion = OrderRelation::findAll(['father_orderid'=>$foId , 'son_orderid'=>$soId , 'type'=>$type ]);
				if (empty($hasRealtion)){
					$model = new OrderRelation();
					$model->father_orderid = $foId;
					$model->son_orderid = $soId;
					$model->type = $type;
					
					if (!$model->save()){
						$errors = $model->getErrors();
						if (!empty($errors)) $errors_msg = json_encode($errors);
						else $errors_msg = '';
						\Yii::error((__FUNCTION__)." : $type father_order_id = ".$foId.'and son_order_id='.$soId.' error:'.$errors_msg,"file");
					}
				}
			}
		}
	}//end of saveOrderRelation
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据订单号获取 订单详情
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$OrderIdList			小老板订单id 号数组
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 订单详情
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/06/06				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderItemByOrderIdList($OrderIdList){
		$rt = OdOrderItem::find()->where(['order_id'=>$OrderIdList])->asArray()->all();
		$items = []; 
		foreach($rt as $row){
			$items[$row['order_id']][] = $row;
		}
		return $items;
	}//end of getOrderItemByOrderIdList
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 清除各平台的redis
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$platformList			需要清除redis的平台 
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 订单详情
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/08/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function clearLeftMenuCache($platformList=[] , $uid=''){
		if ($uid ==='' || empty($uid)){
			$uid = \Yii::$app->subdb->getCurrentPuid();
		}
		
		foreach($platformList as $platform){
			RedisHelper::getOrderCache($uid,$platform);
			switch ($platform){
				case 'ebay':
					$path = \eagle\modules\order\helpers\EbayOrderHelper::$MenuCachePath;
					$path .="_".$uid;
					ConfigHelper::setGlobalConfig($path, '');
					break;
			}
		}
	}//end of clearLeftMenuCache

	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据合并订单获取原始订单发货信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $orderId		小老板订单id 
	 +---------------------------------------------------------------------------------------------
	 * @return array  	order ship 发货信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2016/07/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMergeOrderShippingInfo($orderId){
		$smOrder = OdOrder::findOne($orderId);
		if(empty($smOrder))
			return array();
		
		$fmOrderIdList = array();
		$father_orderids = OrderRelation::findAll(['son_orderid'=>$smOrder->order_id , 'type'=>'merge' ]);
		foreach ($father_orderids as $father_orderid){
			$fmOrderIdList[] = $father_orderid->father_orderid;
		}
		
		if(empty($fmOrderIdList)){
			return array();
		}
		
		$orderShipInfo = array();
		$fmOrders = OdOrder::find()->where(['order_id'=>$fmOrderIdList])->all();
		foreach ($fmOrders as $fmOrder){
			$a= $fmOrder->getTrackinfosPT();
			foreach ($a  as $ot){
				$trackingNo = $ot->tracking_number;
				if(empty($trackingNo)){
					$trackingNo = $fmOrder->order_id;
				}
				
				if(array_key_exists($trackingNo, $orderShipInfo)){// 除非是报错，不然合并相同trackingNo的od ship信息
					if(!empty($ot->errors)){
						$orderShipInfo[$trackingNo."_".$fmOrder->order_id] = $ot;
					}
				}else{
					$orderShipInfo[$trackingNo] = $ot;
				}
			}
		}
		
		$b= $smOrder->getTrackinfosPT();
		// dzt20160908 客户 物流重新上传 ，补上显示合并订单新加的 od_order_ship 信息
		foreach ($b as $ot){
			$trackingNo = $ot->tracking_number;
			if(empty($trackingNo)){
				$trackingNo = $smOrder->order_id;
			}
		
			if(array_key_exists($trackingNo, $orderShipInfo)){// 除非是报错，不然合并相同trackingNo的od ship信息
				if(!empty($ot->errors)){
					$orderShipInfo[$trackingNo."_".$smOrder->order_id] = $ot;
				}
			}else{
				$orderShipInfo[$trackingNo] = $ot;
			}
		}
				
		return $orderShipInfo;
		
	}//end of getMergeOrderShippingInfo
	
	/**
	 +----------------------------------------------------------
	 * 标记发货中的订单未已完成，此功能主要是把实际已近发货但是订单还在发货中的订单批量修改订单状态，没有在小老板系统确认发货的订单会需要此功能
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		million 	2016/3/18				初始化
	 +----------------------------------------------------------
	 **/
	static public function completeOrder($orderids,$module,$action){
		$orders=OdOrder::find()->where(['order_id'=>$orderids])->all();
		$list_clear_platform = []; //需要清除redis
		$edit_log = '';
		foreach ($orders as $order){
			$data = ['order_status' => OdOrder::STATUS_SHIPPED ];
			
			if ($order->exception_status == OdOrder::EXCEP_WAITMERGE){
				$data['exception_status'] = OdOrder::EXCEP_WAITSEND;
			}
			$fullName = \Yii::$app->user->identity->getFullName();
			$rt = OrderUpdateHelper::updateOrder($order, $data,false,$fullName  , $action , $module );
			
			/*20160928start
			$old = $order->order_status;
			$order->order_status = OdOrder::STATUS_SHIPPED;
			$oldExceptionStatus = $order->exception_status;
			
			if ($order->exception_status == OdOrder::EXCEP_WAITMERGE){
				$order->exception_status = OdOrder::EXCEP_WAITSEND;
			}
			if(!$order->save()){
			20160928end */
			
			if( $rt['ack']==false){
				return json_encode(array('result'=>false,'message'=>'订单'.$order->order_id.'订单状态修改失败!'));
			}else{
				/*20160928start
				OperationLogHelper::log($module, $order->order_id,$action,'小老板订单状态:'.OdOrder::$status[$old].'->'.OdOrder::$status[$order->order_status], \Yii::$app->user->identity->getFullName());
				OrderApiHelper::unsetPlatformShippedTag($order->order_id);//去掉虚假发货标签
				
				//告知dashboard面板，统计数量改变了
				OrderApiHelper::adjustStatusCount($order->order_source, $order->selleruserid, $order->order_status, $old,$order->order_id);
				RedisHelper::delOrderCache( \Yii::$app->subdb->getCurrentPuid() , strtolower($oneOrder->order_source) );
				
				//释放库存
				InventoryApiHelper::OrderProductReserveCancel($order->order_id,'order',$action='标记已完成');
				
				//已付款订单能与其他合并的情况， 假如移入已完成需要为其他订单检测一下剩下的订单数量 是否为一， 假如为一的情况 ， 要把剩下待合并订单标志去掉
				if ($oldExceptionStatus == OdOrder::EXCEP_WAITMERGE && $old== OdOrder::STATUS_PAY ){
					$canMergeOrderList = self::listWaitMergeOrder($order);
				
					//剩下的订单为1 ， 清除待合并的标志
					if (count($canMergeOrderList) == 1){
						OdOrder::updateAll(['exception_status'=>OdOrder::EXCEP_WAITSEND], ['order_id'=>$canMergeOrderList , 'order_status'=>OdOrder::STATUS_PAY]);
						OperationLogHelper::log($module,$canMergeOrderList[0],$action,$order->order_id.'已付款至已完成,所以取消合并标志', \Yii::$app->user->identity->getFullName());
					}
				}
				20160928end */
				//增加清除 平台redis
				if (!in_array($order->order_source, $list_clear_platform)){
					$list_clear_platform[] = $order->order_source;
				}
				
				$edit_log .= ltrim($order->order_id, '0').', ';
			}
		}
		if(!empty($edit_log)){
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "标记为已完成, 订单号: ".rtrim($edit_log, ', '));
		}
		
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
		}
	}//end of function completeOrder
	
	
	/**
	+---------------------------------------------------------------------------------------------
	* 原始订单更新后，通过这个原始订单判断是否更新合并订单 的原始订单信息
	+---------------------------------------------------------------------------------------------
	* @access static
	+---------------------------------------------------------------------------------------------
	* @param  $orderId	 	被触发完更新的小老板 订单号
	* @param  $attributes	 小老板 订单熟悉数组 如：array('order_source_status','order_status')
	+---------------------------------------------------------------------------------------------
	* @return array multitype:boolean string
	*
	* @invoking	OrderHelper::isUpdateMergedOrder(123);
	*
	+---------------------------------------------------------------------------------------------
	* log			name	date					note
	* @author		dzt		2016-08-23				初始化
	+---------------------------------------------------------------------------------------------
	**/
	static public function isUpdateMergedOrder($orderId,$attributes){
		if(empty($orderId)){
			return array(false,'orderId:'.$orderId.' is empty.');
		}
		
		if(empty($attributes)){
			return array(false,'attributes cannot be empty.orderId:'.$orderId);
		}
		
		$targetOrder = OdOrder::findOne($orderId);
		if(empty($targetOrder)){
			return array(false,'orderId:'.$orderId.' order is not existed.');
		}
		
		$targetRel = OrderRelation::findOne(['father_orderid'=>$orderId , 'type'=>'merge' ]);
		if(empty($targetRel)){
			return array(false,'orderId:'.$orderId.' has no merge OrderRelation');
		}
		
		$orderRels = OrderRelation::findAll(['son_orderid'=>$targetRel->son_orderid , 'type'=>'merge' ]);
		
		$shouldUpdate = true;
		$msg = array();
		foreach ($orderRels as $orderRel){// 检查当所有其他原始订单的信息都被更新到一致时，才修改合并订单对应信息
			if($targetRel->father_orderid != $orderRel->father_orderid){
				$relOrder = OdOrder::findOne($orderRel->father_orderid);
				foreach($attributes as $attribute) {
					if(!empty($relOrder) && $relOrder->$attribute != $targetOrder->$attribute){
						$shouldUpdate = false;
						$msg[] = 'not all origin order update new '.$attribute.':'.$targetOrder->$attribute;
					}
				}
			}
		}
		
		if($shouldUpdate){
			return array(true,$targetRel->son_orderid);
		}else{
			return array(false,implode(';', $msg));
		}
	}//end of isUpdateMergedOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 判断是否表示合并订单 为已虚拟发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $orderId	 	被触发虚拟发货的小老板 订单号
	 +---------------------------------------------------------------------------------------------
	 * @return array [boolean , errorMsg | son_orderid]
	 * 
	 * @invoking	OrderHelper::isMarkOnlyPlatformShippedForSMOrder(123);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2016-08-24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function isMarkOnlyPlatformShippedForSMOrder($orderId){
		if(empty($orderId)){
			return array(false,'orderId:'.$orderId.' is empty.');
		}
		
		$targetOrder = OdOrder::findOne($orderId);
		if(empty($targetOrder)){
			return array(false,'orderId:'.$orderId.' order is not existed.');
		}
		
		$targetRel = OrderRelation::findOne(['father_orderid'=>$orderId , 'type'=>'merge' ]);
		if(empty($targetRel)){
			return array(false,'orderId:'.$orderId.' has no merge OrderRelation');
		}
		
		$orderRels = OrderRelation::findAll(['son_orderid'=>$targetRel->son_orderid , 'type'=>'merge' ]);
		$shouldUpdate = true;
		$msg = array();
		foreach ($orderRels as $orderRel){
			if($shouldUpdate && $targetRel->father_orderid != $orderRel->father_orderid){
				$shouldUpdate = OrderApiHelper::isEagleUnshipOrder($orderRel->father_orderid);
				if(!$shouldUpdate)
					$msg[] = 'orderId:'.$orderRel->father_orderid.' has not mark';
			}
		}
		
		if($shouldUpdate){
			return array(true,$targetRel->son_orderid);
		}else{
			return array(false,implode(';', $msg));
		}
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 逻辑 ：获取 仓库和 运输服务 信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param		na
	 +---------------------------------------------------------------------------------------------
	 * @return array 
	 * 					仓库信息
	 * 					运输服务信息
	 * 					第三方仓库信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/08/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getWarehouseAndShipmentMethodData(){
		$warehouseList = InventoryApiHelper::getWarehouseIdNameMap(true);
		$shipmethodList = [];
		if(!empty($warehouseList)){
			foreach ($warehouseList as $k=>$name){
// 				$shippingMethodInfo = CarrierOpenHelper::getShippingMethodNameInfo(array('proprietary_warehouse'=>$k), -1);
// 				$shippingMethodInfo = $shippingMethodInfo['data'];
// 				if(!empty($shippingMethodInfo)){
// 					foreach ($shippingMethodInfo as $id=>$ship){
// 						$shipmethodList[$id] = $ship['service_name'];
// 					}
// 				}
				$shipmethodList = CarrierOpenHelper::getShippingServiceIdNameMapByWarehouseId($k);
				
				break;
			}
		}
		
		//检查仓库是自营还是第三方
		$allWHList = InventoryApiHelper::getAllWarehouseInfo();
		$locList = [];
		foreach($allWHList as $whRow){
			$locList[$whRow['warehouse_id']] = $whRow['is_oversea'];
		}
		
		return [$shipmethodList , $warehouseList  , $locList];
	}//end of function getWarehouseAndShipmentMethodData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 判断当前订单的虚拟发货状态 
	 * 		ps：a.rumall  不需要标记发货的  b. 手工 订单  不需要标记发货的
	 * 1.有虚拟发货记录，最后 一条为虚拟发货失败则为[提交失败]
	 * 2.有虚拟发货记录，最后一条为虚拟发货成功则为[提交成功（小老板）]
	 * 3.没有 虚拟发货记录，订单状态为发货之后 ，[提交成功（非小老板）]
	 * 4.没有 虚拟发货记录，订单状态为已付款，平台状态为可发货 [等待提交]
	 * 5.没有 虚拟发货记录，订单状态为已付款，平台状态为未付款等状态 [无需提交]
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $order	 		order model  存在 订单传入 order model ， 不存在 订单传入null
	 +---------------------------------------------------------------------------------------------
	 * @return string  
	 * 						Y 	[提交成功（非小老板）]
	 * 						C	[提交成功（小老板）]
	 *  					F	[提交失败]
	 * 						P	[等待提交]
	 *						N	[无需提交]
	 *						S	[提交中]
	 * 
	 *
	 * @invoking	OrderHelper::getCurrentSyncShipStaus($order , '200');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016-08-30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getCurrentSyncShipStaus($order ,  $OrderStatus  , $platformStatus ){
		try {
			$syncShipStatus = '';
			if ($order instanceof OdOrder){
				$model = $order;
			}else if (is_array($order)){
				//当order id 为空时为 新订单
				$model = (Object) $order; // 类型转换
			}else if (is_string($order) || is_int($order)){
				$model = Odorder::findOne($order);
			}else{
				return ['ack'=>true , 'message'=>'参数无效', 'code'=>'4001' , 'data'=>$syncShipStatus];
			}
			
			if ($model->order_source == 'rumall'){
				//rumall 不需要标记发货的
				return ['ack'=>true , 'message'=>'' , 'code'=>'2000' , 'data'=>'N'];
			}
			
			if (isset($model->order_capture)){
				//当数据转对象时， 不一定有order_capture这个 字段， 所以这里可能会报错
				if ( $model->order_capture == 'Y'){
					//手工 订单  不需要标记发货的
					return ['ack'=>true , 'message'=>'' , 'code'=>'2000' , 'data'=>'N'];
				}
			}
			
			//
			if (!empty($model->order_id)){
				//有标记发货的日志才认为虚拟发货了
				$sql = " SELECT log_key as order_id   FROM `operation_log_v2` WHERE `log_operation` LIKE '%标记发货%' and log_key = '".$model->order_id."' ";
				$tmpRT = \Yii::$app->subdb->createCommand($sql)->queryAll();
				if (!empty($tmpRT)){
					$lastOne = OdOrderShipped::find()->where(['order_id'=>$model->order_id])->orderBy('id desc')->one();
					if (!empty($lastOne)){
						//echo OdOrderShipped::find()->where(['order_id'=>$model->order_id])->orderBy('id desc')->createCommand()->getRawSql();
						if ($lastOne->status == '1'){
							//2.有虚拟发货记录，最后一条为虚拟发货成功则为[提交成功（小老板）]
							$syncShipStatus = 'C'; //[提交成功（小老板）]
						}else if ($lastOne->status == '2'){
							//1.有虚拟发货记录，最后 一条为虚拟发货失败则为[提交失败]
							$syncShipStatus = 'F'; //[提交失败]
						}else if ($lastOne->status == '0'){
							$syncShipStatus = 'S';//[提交中]
						}
						//echo "  status: ".$lastOne->status;
					}
				}
			}
			// 无订单号或者无 虚拟发货记录皆为 没有 虚拟发货记录
			if (empty($model->order_id) || empty($lastOne)){
				//echo " OrderStatus ".$OrderStatus." platformStatus ".$platformStatus;
				if ($OrderStatus >= OdOrder::STATUS_WAITSEND){
					//3.没有 虚拟发货记录，订单状态为发货之后 ，[提交成功（非小老板）]
					$syncShipStatus = 'Y'; //[提交成功（非小老板）]
				}
					
				if ($OrderStatus == Odorder::STATUS_PAY){
					//已经指定好了状态的
					if (isset(self::$waitSignShipStaus[$model->order_source])){
						if (is_array(self::$waitSignShipStaus[$model->order_source])){
							if (in_array($platformStatus , self::$waitSignShipStaus[$model->order_source])){
								//4.没有 虚拟发货记录，订单状态为已付款，平台状态为可发货 [等待提交]
								$syncShipStatus = 'P'; //[等待提交]
							}else{
								//已付款，但不是可发货的状态， 都设置为 [无需提交]
								$cannotshipStatusArr = OrderUpdateHelper::getCanNotShipOrderItemStatus($model->order_source);
								if (empty($cannotshipStatusArr)){
									//还有取消订单发货，所以不能都设置为Y
									$syncShipStatus = 'N'; //[无需提交]
								}else{
									//细分虚拟发货状态
									if (in_array($platformStatus , $cannotshipStatusArr)){
										$syncShipStatus = 'N'; //[无需提交]
									}else{
										$syncShipStatus = 'Y'; //[提交成功（非小老板）]
									}
								}
								
							}
						}
					}else{
						$syncShipStatus = 'P'; //[等待提交]
					}
				}
					
				if ($OrderStatus == OdOrder::STATUS_NOPAY){
					//5.没有 虚拟发货记录，订单状态为未付款， [无需提交]
					$syncShipStatus = 'N'; //[无需提交]
				}
			}
			return ['ack'=>true , 'message'=>'' , 'code'=>'2000' , 'data'=>$syncShipStatus];
		} catch (\Exception $e) {
			\Yii::error((__function__)." : ".$e->getMessage() ." line no:".$e->getLine(),"file");
			return ['ack'=>true , 'message'=>$e->getMessage() ." line no:".$e->getLine() , 'code'=>'4002' , 'data'=>$syncShipStatus];
		}
	}//end of function getCurrentSyncShipStaus
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除 手工订单
	 * 1.只能删除手工订单,并且order_relation状态为normal状态的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderIdList			array	 	小老板 订单号
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/12/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function deleteManualOrder($orderIdList){
		$orderList = OdOrder::find()->where(['order_id'=>$orderIdList])->asArray()->all();
		$error_msg = '';
		
		$module='order';
		$action='删除手工订单';
		if(empty($orderList)){
			$error_msg .= '订单：'.implode(',', $orderIdList).'不存在';
		}
		
		$edit_log = array();
		foreach($orderList as $order){
			if(($order['order_status'] == 500) && ($order['order_capture'] == 'Y')){
				//已完成的手工订单不能进行删除
				$error_msg .= $order['order_id'].'已完成的手工订单不能进行删除';
				continue;
			}
			
			//只有手工订单才可以删除
			if($order['order_capture'] == 'Y'){
				if($order['order_relation'] == 'normal' || $order['order_relation'] == 'ss'){
					if($order['order_status'] != OdOrder::STATUS_CANCEL){
						$tmpCancelOrder = self::CancelOneOrder($order['order_id'], '删除手工订单');
					}else{
						$tmpCancelOrder = array();
						$tmpCancelOrder['success'] = true;
					}
					
					if($tmpCancelOrder['success'] == false){
						$error_msg .= $order['order_id'].':'.$tmpCancelOrder['message'];
					}else{
						//删除手工订单
						OdOrderItem::deleteAll(['order_id'=>$order['order_id']]);
						$effectedRows = OdOrder::deleteAll(['order_id'=>$order['order_id']]);
						
						$edit_log[] = $order['order_id'];
							
						//通知dashboard 减去 n 个被合并订单的 左侧菜单计数器，销量统计则不需要减掉，同理，当doInsert new order，如果是合并的订单，则销量统计不加上去
						$puid=\Yii::$app->subdb->getCurrentPuid();
						DashBoardStatisticHelper::SalesStatisticAdd($order['order_source_create_time'], $order['order_source'], $order['order_type'], $order['selleruserid'], $order['currency'], 0 - $order['grand_total'], 0 - $effectedRows, 0, false, $order['order_status']);
						//DashBoardStatisticHelper::OMSStatusAdd($puid, $order['order_source'], $order['order_status'], 0 - $effectedRows,"rollback Merged, so minus the father order");
						DashBoardStatisticHelper::OMSStatusAdd2($puid, $order['order_source'],$order['selleruserid'], $order['order_status'], 0 - $effectedRows,"rollback Merged, so minus the father order");
							
						//删除系统标签
						OrderTagHelper::DelOrderSysTag($order['order_id'], 'all');
					}
				}else{
					//order_relation状态为normal时才可以删除
					$error_msg .= $order['order_id'].'不能直接删除合并的订单,需要先将合并的订单拆分后对原手工订单进行删除';
				}
			}else{
				//不是手工订单不能删除
				$error_msg .= $order['order_id'].'不能删除非手工订单';
			}
		}
		
		if(!empty($edit_log)){
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "删除手工订单, 订单号: ".implode(', ', $edit_log));
		}
		
		if (!empty($error_msg)){
			return ['success'=>false,'message'=>$error_msg];
		}else{
			return ['success'=>true,'message'=>''];
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 增加指定订单的addi_info信息 
	 * ps 手工订单不能使用这个函数
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $OrderSourceOrderID	string	 	平台订单号
	 * @param     $selleruserid			string		卖家账号
	 * @param     $platform				string	 	订单平台 如ebay， aliexpress
	 * @param     $currentInfo			array	 	addi_info新增的内容或者是修改的内容
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									是否成功
	 * 									相关信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/02/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function updateOrderAddiInfoByOrderID($OrderSourceOrderID, $selleruserid , $platform , $currentInfo){
		try {
			if (empty($OrderSourceOrderID)){
				return [false , '平台订单号的参数无效'];
			}
			
			if (empty($selleruserid)){
				return [false , '卖家账号的参数无效'];
			}
			
			if (empty($platform)){
				return [false , '平台的参数无效'];
			}
			
			if (empty($currentInfo)){
				return [false , 'addiinfo的参数无效'];
			}
			$isForce = false ;
			$fullName = 'System';
			$action='同步订单' ;
			$module ='order';
			
			$order = OdOrder::find()->where(['order_source_order_id'=>$OrderSourceOrderID , 'selleruserid'=>$selleruserid , 'order_source'=>$platform])->one();
			if(empty($order)){
				return [false , '找不到订单相关订单'];
			}
			if (!empty($order->addi_info)){
				$addInfo = json_decode($order->addi_info,true);
			}else{
				$addInfo = [];
			}
			
			foreach($currentInfo as $key=>$value){
				$addInfo[$key] = $value;
			}
			
			if (!empty($addInfo)){
				$newAttr = ['addi_info'=>json_encode($addInfo)];
				$rt = OrderUpdateHelper::updateOrder($order, $newAttr,$isForce,$fullName,$action,$module);
				return [$rt['ack'] , $rt['message']];
			}else{
				return [false , 'addiinfo的参数异常'];
			}
		} catch (\Exception $e) {
			return [false, $e->getMessage()];
		}
		
	}//end of function updateAddiInfoByOrderID

	/**
	 +----------------------------------------------------------
	 * 导出订单 
	 +----------------------------------------------------------
	 * @param $data 导出订单数据+导出范本id+标准范本的字段+按什么来导出
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lgw		  2017/01/04		初始化
	 +----------------------------------------------------------
	 **/
	public static function ExportExcelAll($data, $type = false,$uid){
		$rtn['success'] = 1;
		$rtn['message'] = '';
		try{
			$data_lists=$data;
			$printMaxCount=10000; //最多能导出多少条订单
			$orderids_arr=array();
			if(is_array($data_lists)){
				//全部导出
				$excelmodelid=empty($data_lists['exportTemplateSelect'])?0:$data_lists['exportTemplateSelect']; //模板id
				$checkkey=empty($data_lists['checkkey'])?'':$data_lists['checkkey'];  //默认模板时的字段
				$isMaster=!isset($data_lists['isMaster'])?'2':$data_lists['isMaster'];      //按什么导出
		
				if(!isset($data_lists['order_source']['order_source'])){
					exit('数据源标识为空');
				}
		
				$data_lists['params']['per-page']=50000;
				$data_lists['params']['noPagination']=true;
				$data_lists['params']['max_size']=50000;
				
				//特殊条件处理
				$wheretxt=json_encode($data_lists['where']);
				if(!empty($data_lists['params'])){
					foreach ($data_lists['params'] as $specialkeys=>$specialcode){
						$ry=strpos($specialkeys,':');
						if($ry===0){ //首位置
							$wheretxt=str_replace('= '.$specialkeys.'"','=\''.$specialcode.'\'"',$wheretxt);
						}
					}
					$temparr=json_decode($wheretxt,true);
					$data_lists['where']=$temparr;
				}
		
				if($data_lists['order_source']['order_source']=='delivery'){		//物流界面
					//控制合并订单不会显示
					$data_lists['REQUEST']['order_relation'] = ['normal','sm'];
						
					//假如是OMS界面打开发货模块的话，没有选择平台做筛选，需要默认用平台来筛选
					if((!empty($data_lists['REQUEST']['use_mode'])) && (empty($data_lists['REQUEST']['selleruserid']))){
						$data_lists['REQUEST']['selleruserid'] = $data_lists['REQUEST']['use_mode'];
					}
						
					//这里因为卖家账号包括了平台属性所以这里修改了查询的逻辑
					if(!empty($data_lists['REQUEST']['selleruserid'])){
						if(isset(OdOrder::$orderSource[$data_lists['REQUEST']['selleruserid']])){
							$data_lists['REQUEST']['order_source'] = $data_lists['REQUEST']['selleruserid'];
							unset($data_lists['REQUEST']['selleruserid']);
						}
					}
						
					// 					$tmpLists = CarrierprocessController::getlist($data_lists['REQUEST'],'delivery/order');
					$tmpLists=\eagle\modules\order\apihelpers\OrderApiHelper::getOrderListByCondition($data_lists['REQUEST'],$uid,$data_lists['params']);
					foreach ($tmpLists['data'] as $modelsone){
						$orderids_arr[]=$modelsone->order_id;
					}
					unset($tmpLists);
				}
				else{	 //oms界面
					if($data_lists['order_source']['order_source']=='ebay'){
						$dataorder=null;
						$omsRT = OrderApiHelper::getOrderListByConditionOMS($data_lists['REQUEST'],$data_lists['order_source'],$dataorder,null,true,'all',$data_lists['params']);
						$models=$omsRT['data'];
					}
					else{
						$dataorder=OdOrder::find();
						$dataorder=$dataorder->where($data_lists['where']);
						$dataorder=$dataorder->orderBy($data_lists['orderBy']);
						$omsRT = OrderApiHelper::getOrderListByConditionOMS($data_lists['REQUEST'],$data_lists['order_source'],$dataorder,null,true,'all',$data_lists['params']);
						// 						$dataorder->params=$data_lists['params'];
						$models = $dataorder->all();
					}
						
					foreach ($models as $modelsone){
						$orderids_arr[]=$modelsone->order_id;
					}
					unset($dataorder);
					unset($models);
				}
			}
			else{
				//勾选导出
				$temparr=explode('|',$data_lists);
				$excelmodelid=empty($temparr[1])?0:$temparr[1]; //模板id
				$checkkey=empty($temparr[2])?'':$temparr[2];  //默认模板时的字段
				$isMaster=!isset($temparr[3])?'2':$temparr[3];      //按什么导出
				$idsarr=empty($temparr[0])?'':$temparr[0];    //订单id
				unset($temparr);
				$orderids_arr	=	explode(',',$idsarr);
			}
				
			//限制导出订单数量
			$orderids_arr=array_slice($orderids_arr,0,$printMaxCount);
			unset($data_lists);
		
			$content=ExcelHelper::$content;
			if($excelmodelid<='-1'){
				//范本为标准范本时取$content数组
				$checkkeyarr=explode(',', $checkkey);
				foreach ($content as $contentkey=>$contentone){
					if(in_array($contentkey,$checkkeyarr))
						$items_list_arr[]=$contentkey.':'.$contentone;
				}
			}
			else{
				$excelmodel	=	new Excelmodel();
				$excel	=	$excelmodel->findOne($excelmodelid);
				$items_list_arr =   explode(',',$excel['content']);//excel模型保存的字段
			}
				
			$custom_context=array();//自定义字段
			//导出哪些字段
			$items_arr=array();
			foreach ($items_list_arr as $items_list_arrone){
				$items_list_arrone_arr=explode(':',$items_list_arrone);
				//$items_list_arrone_arr 0:字段
				//						 1:字段自定义名
				//                       2:自定义字段的值
				if(strstr($items_list_arrone_arr[0], '-custom-')){//自定义字段作特殊处理
					$custom_context[$items_list_arrone_arr[0]]=$items_list_arrone_arr[2];
				}
				if(isset($content[$items_list_arrone_arr[0]]) || strstr($items_list_arrone_arr[0],'-custom-')!=false){
					$items_arr[]=$items_list_arrone_arr[0];
		
					if(empty($items_list_arrone_arr[1])) //保存数组最后根据字段查找相应的自定义名
						$items_customname_arr[$items_list_arrone_arr[0]]=$content[$items_list_arrone_arr[0]];
					else{//新版本走这里，旧版本不走
						$items_customname_arr[$items_list_arrone_arr[0]]=$items_list_arrone_arr[1];
					}
				}
			}
			unset($items_list_arr);
			unset($content);
				
			$photo_primary=['photo_primary'=>['width'=>100,'height'=>100]];
			$res = array();
			$enter_key="\r\n"; //换行
			if(!empty($orderids_arr)){
				$order_obj = OdOrder::find()->where(['order_id'=>$orderids_arr])->all();

				foreach($orderids_arr as $orderids_arrone){ //start of foreach1
					foreach ($order_obj as $order_objone){ //start of foreach2
						if($order_objone->order_id==$orderids_arrone){
							if(count($order_objone->items)<1){
								//没有订单明细信息时==============================================================================================
								$tmp_arr = array();
								foreach ($items_arr as $column){
									if($column == 'order_manual_id'){
										//不做data conversion 的情况下 沿用 之前的order_manual_id 这个 字段
										$tmp_arr[$column]=OrderTagHelper::getAllTagStrByOrderId($order_objone->order_id);
									}
									elseif($column == 'address1_2'){
										$ad1= isset($order_objone->consignee_address_line1)?$order_objone->consignee_address_line1:'';
										$ad2= isset($order_objone->consignee_address_line2)?$order_objone->consignee_address_line2:'';
										$tmp_arr[$column]=$ad1.' '.$ad2;
									}
									elseif($column == 'addressdetail'){
										$ad1= isset($order_objone->consignee_address_line1)?$order_objone->consignee_address_line1:'';
										$ad2= isset($order_objone->consignee_address_line2)?$order_objone->consignee_address_line2:'';
										$ad3= isset($order_objone->consignee_address_line3)?$order_objone->consignee_address_line3:'';
										$adcity= isset($order_objone->consignee_city)?$order_objone->consignee_city:'';
										$adprovince= isset($order_objone->consignee_province)?$order_objone->consignee_province:'';
										$addistrict= isset($order_objone->consignee_district)?$order_objone->consignee_district:'';
										$adcounty= isset($order_objone->consignee_county)?$order_objone->consignee_county:'';
										$adcountry= isset($order_objone->consignee_country)?$order_objone->consignee_country:'';
										$adpostal= isset($order_objone->consignee_postal_code)?$order_objone->consignee_postal_code:'';
										$tmp_arr[$column]=$ad1.' '.$ad2.' '.$ad3.' '.$adcity.' '.$adprovince.' '.$addistrict.' '.$adcounty.' '.$adcountry.' '.$adpostal;
									}
									elseif ($column == 'tracknum'){
										$shipped = OdOrderShipped::find()->where(['order_id'=>$order_objone->order_id])->andwhere( " ifnull(tracking_number, '') <> '' " )->orderBy('id desc')->one();
										$tmp_arr[$column] = empty($shipped)?'':$shipped->tracking_number;
		
										//判断是否为纯数字，并且长度大于7
										if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0'))
											$tmp_arr[$column] = $tmp_arr[$column].' ';
									}
									elseif ($column == 'consignee_country_label_cn'){
										$tmp_arr[$column] = empty(StandardConst::$COUNTRIES_CODE_NAME_CN[$order_objone->consignee_country_code])?$order_objone->consignee_country_code:StandardConst::$COUNTRIES_CODE_NAME_CN[$order_objone->consignee_country_code];
									}
									elseif($column == 'logistic_status'){
										$tmp_arr[$column] = Tracking::getChineseStatus($order_objone->$column);
									}
									elseif(strstr($column,'-custom-')){
										$tmp_arr[$column] = $custom_context[$column];
									}
									elseif($column == 'order_source_order_id'){
										if($order_objone->order_source=='ebay' && $order_objone->order_capture=='N')
											$tmp_arr[$column] = $order_objone->order_source_srn;
										else
											$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:'';
									}
									else if($column == 'order_source_shipping_method'){
										if($order_objone->order_source=='aliexpress'){
											$txt_temp=json_decode($order_objone->addi_info,true);
											if(!isset($txt_temp['shipping_service']))
												$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]);
											else{
													
											}
										}
										else
											$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:'';
									}
									else if($column == 'user_content'){
										$rt=MessageApiHelper::getOrderLastMessage($order_objone->order_source_order_id);
										$tmp_arr[$column]=htmlspecialchars_decode($rt['content'],ENT_QUOTES);
									}
									else if($column == 'plat_form_account'){
										$re=PlatformAccountApi::getPlatformAllAccount('',$order_objone->order_source,true)["data"];
										$tmp_arr[$column]=(empty($re[$order_objone->selleruserid])?'':$re[$order_objone->selleruserid]);
									}
									else{
										$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:'';
									}
								}
								$res[] = $tmp_arr;
								//END没有订单明细信息时==============================================================================================
							}
							else{
								if($isMaster!=2){ //按订单导出
									$tmp_arr = array();
									$tmp_arr_deleteener_column=array();
								}
								$declaration_list=CarrierDeclaredHelper::getOrderDeclaredInfoByOrder($order_objone);
								foreach ($order_objone->items as $item_obj){ //start of foreach3
									//@todo  CD跳过NonDeliverySku的导出，如果以后其他功能导出需要，要相应修改,CD订单拉取下来的时候，会有一些买家后端看不到的item，这部分item操作的时候要对买家隐藏
									if($order_objone->order_source=='cdiscount' && in_array($item_obj['sku'],CdiscountOrderInterface::getNonDeliverySku())){
										continue;
									}
									//不导出禁用的商品
									$disable=0;
									if(strtolower($item_obj['manual_status'])=='disable'){
										$disable=1;
									}
									if($isMaster==2) //按商品导出
										$tmp_arr = array();
// 									$hasProduct = strlen($item_obj['root_sku']) && ProductApiHelper::hasProduct($item_obj['root_sku']);
									$product = ProductApiHelper::getProductInfo($item_obj['root_sku']);
									if(empty($product))
										$hasProduct=0;
									else
										$hasProduct=1;
									if($isMaster!=2){
										//按订单导出==============================================================================================
										$photo_primary='';
										foreach ($items_arr as $column){
											//如果有设置导出商品中文名的，进行商品信息的获取
											if ($column == 'name_cn'){
												if($disable==0){
													if ($hasProduct){
														$tmp_arr['name_cn'] = (empty($tmp_arr['name_cn'])?'':$tmp_arr['name_cn']).$product['prod_name_ch'].$enter_key;
													}else{
														$tmp_arr['name_cn'] = (empty($tmp_arr['name_cn'])?'':$tmp_arr['name_cn']).$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr['name_cn'] = (empty($tmp_arr['name_cn'])?'':$tmp_arr['name_cn']).$enter_key;
											}else if ($column == 'name_en'){
												if($disable==0){
													if ($hasProduct){
														$tmp_arr['name_en'] = (empty($tmp_arr['name_en'])?'':$tmp_arr['name_en']).$product['prod_name_en'].$enter_key;
													}else{
														$tmp_arr['name_en'] = (empty($tmp_arr['name_en'])?'':$tmp_arr['name_en']).$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr['name_en'] = (empty($tmp_arr['name_en'])?'':$tmp_arr['name_en']).$enter_key;
											}else if ($column == 'prod_weight'){
												if($disable==0){
													if ($hasProduct){
														$tmp_arr['prod_weight'] = (empty($tmp_arr['prod_weight'])?'':$tmp_arr['prod_weight']).$product['prod_weight'].$enter_key;
													}else{
														$tmp_arr['prod_weight'] = (empty($tmp_arr['prod_weight'])?'':$tmp_arr['prod_weight']).$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr['name_en'] = (empty($tmp_arr['name_en'])?'':$tmp_arr['name_en']).$enter_key;
											}else if ($column == 'name'){
												if($disable==0){
													if ($hasProduct){
														$tmp_arr['name'] = (empty($tmp_arr['name'])?'':$tmp_arr['name']).$product['name'].$enter_key;
													}else{
														$tmp_arr['name'] = (empty($tmp_arr['name'])?'':$tmp_arr['name']).$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr['name'] = (empty($tmp_arr['name'])?'':$tmp_arr['name']).$enter_key;
											}else if($column == 'root_sku'){
												if($disable==0){
													if ($hasProduct){
														$tmp_arr['root_sku'] = (empty($tmp_arr['root_sku'])?'':$tmp_arr['root_sku']).$item_obj['root_sku'].$enter_key;
													}else{
														$tmp_arr['root_sku'] = (empty($tmp_arr['root_sku'])?'':$tmp_arr['root_sku']).$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr['root_sku'] = (empty($tmp_arr['root_sku'])?'':$tmp_arr['root_sku']).$enter_key;
											}elseif($column == 'declaration_ch'){
												if($disable==0){
													if (!$declaration_list[$item_obj['order_item_id']]['not_declaration']){
														$tmp_arr['declaration_ch'] = (empty($tmp_arr['declaration_ch'])?'':$tmp_arr['declaration_ch']).$declaration_list[$item_obj['order_item_id']]['declaration']['nameCN'].$enter_key;
													}else{
														$tmp_arr['declaration_ch'] = (empty($tmp_arr['declaration_ch'])?'':$tmp_arr['declaration_ch']).$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr['declaration_ch'] = (empty($tmp_arr['declaration_ch'])?'':$tmp_arr['declaration_ch']).$enter_key;
											}elseif($column == 'declaration_en'){
												if($disable==0){
													if (!$declaration_list[$item_obj['order_item_id']]['not_declaration']){
														$tmp_arr['declaration_en'] = (empty($tmp_arr['declaration_en'])?'':$tmp_arr['declaration_en']).$declaration_list[$item_obj['order_item_id']]['declaration']['nameEN'].$enter_key;
													}else{
														$tmp_arr['declaration_en'] = (empty($tmp_arr['declaration_en'])?'':$tmp_arr['declaration_en']).$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr['declaration_en'] = (empty($tmp_arr['declaration_en'])?'':$tmp_arr['declaration_en']).$enter_key;
											}elseif($column == 'declaration_value'){
												if($disable==0){
													if (!$declaration_list[$item_obj['order_item_id']]['not_declaration']){
														$tmp_arr['declaration_value'] = (empty($tmp_arr['declaration_value'])?'':$tmp_arr['declaration_value']).$declaration_list[$item_obj['order_item_id']]['declaration']['price'].$enter_key;
													}else{
														$tmp_arr['declaration_value'] = (empty($tmp_arr['declaration_value'])?'':$tmp_arr['declaration_value']).$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr['declaration_value'] = (empty($tmp_arr['declaration_value'])?'':$tmp_arr['declaration_value']).$enter_key;
											}elseif($column == 'declaration_value_currency'){
												if($disable==0){
													if ($hasProduct){
														$tmp_arr['declaration_value_currency'] = (empty($tmp_arr['declaration_value_currency'])?'':$tmp_arr['declaration_value_currency']).$product['declaration_value_currency'].$enter_key;
													}else{
														$tmp_arr['declaration_value_currency'] = (empty($tmp_arr['declaration_value_currency'])?'':$tmp_arr['declaration_value_currency']).$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr['declaration_value_currency'] = (empty($tmp_arr['declaration_value_currency'])?'':$tmp_arr['declaration_value_currency']).$enter_key;
											}elseif($column == 'price' || $column == 'sku' || $column == 'quantity' || $column == 'product_name' || $column == 'seller_weight' || $column == 'default_warehouse_id'){
												if($disable==0){
													$temp= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
													$tmp_arr[$column] = (empty($tmp_arr[$column])?'':$tmp_arr[$column]).$temp.$enter_key;
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr[$column] = (empty($tmp_arr[$column])?'':$tmp_arr[$column]).$enter_key;
											}
											elseif($column == 'order_manual_id'){
												//不做data conversion 的情况下 沿用 之前的order_manual_id 这个 字段
												$tmp_arr[$column]=OrderTagHelper::getAllTagStrByOrderId($order_objone->order_id);
											}elseif($column == 'photo_url'){
												if($disable==0){
													$tmp_arr[$column]= (empty($tmp_arr[$column])?'':$tmp_arr[$column]).(isset($item_obj['photo_primary'])?$item_obj['photo_primary']:'').$enter_key;
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr[$column] = (empty($tmp_arr[$column])?'':$tmp_arr[$column]).$enter_key;
											}
											elseif($column == 'address1_2'){
												$ad1= isset($order_objone->consignee_address_line1)?$order_objone->consignee_address_line1:'';
												$ad2= isset($order_objone->consignee_address_line2)?$order_objone->consignee_address_line2:'';
												$tmp_arr[$column]=$ad1.' '.$ad2;
											}
											elseif($column == 'addressdetail'){
												$ad1= isset($order_objone->consignee_address_line1)?$order_objone->consignee_address_line1:'';
												$ad2= isset($order_objone->consignee_address_line2)?$order_objone->consignee_address_line2:'';
												$ad3= isset($order_objone->consignee_address_line3)?$order_objone->consignee_address_line3:'';
												$adcity= isset($order_objone->consignee_city)?$order_objone->consignee_city:'';
												$adprovince= isset($order_objone->consignee_province)?$order_objone->consignee_province:'';
												$addistrict= isset($order_objone->consignee_district)?$order_objone->consignee_district:'';
												$adcounty= isset($order_objone->consignee_county)?$order_objone->consignee_county:'';
												$adcountry= isset($order_objone->consignee_country)?$order_objone->consignee_country:'';
												$adpostal= isset($order_objone->consignee_postal_code)?$order_objone->consignee_postal_code:'';
												$tmp_arr[$column]=$ad1.' '.$ad2.' '.$ad3.' '.$adcity.' '.$adprovince.' '.$addistrict.' '.$adcounty.' '.$adcountry.' '.$adpostal;
											}
											elseif($column == 'sku_quantity'){
												if($disable==0){
													$sku= isset($order_objone->sku)?$order_objone->sku:(isset($item_obj['sku'])?$item_obj['sku']:'');
													$qty= isset($order_objone->quantity)?$order_objone->quantity:(isset($item_obj['quantity'])?$item_obj['quantity']:'0');
													$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]).$sku.'*'.$qty.$enter_key;
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]).$enter_key;
											}
											elseif ($column == 'tracknum'){
												$shipped = OdOrderShipped::find()->where(['order_id'=>$order_objone->order_id])->andwhere( " ifnull(tracking_number, '') <> '' " )->orderBy('id desc')->one();
												$tmp_arr[$column] = empty($shipped)?'':$shipped->tracking_number;
		
												//判断是否为纯数字，并且长度大于7
												if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0'))
													$tmp_arr[$column] = $tmp_arr[$column].' ';
											}
											elseif ($column == 'consignee_country_label_cn'){
												$tmp_arr[$column] = empty(StandardConst::$COUNTRIES_CODE_NAME_CN[$order_objone->consignee_country_code])?$order_objone->consignee_country_code:StandardConst::$COUNTRIES_CODE_NAME_CN[$order_objone->consignee_country_code];
											}
											elseif ($column == 'order_item_cost'){
												if($disable==0){
													$orderData = (array)$order_objone->getAttributes();
													$tmpProductCost = OrderApiHelper::getOrderProductCost($orderData, $item_obj['sku']);
													$temp = $tmpProductCost['purchase_cost']+$tmpProductCost['addi_cost'];
													$tmp_arr[$column] = (!isset($tmp_arr[$column])?'':$tmp_arr[$column].$enter_key).$temp;
												}
												else
													$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]).$enter_key;
											}
											elseif($column == 'logistic_status'){
												$tmp_arr[$column] = Tracking::getChineseStatus($order_objone->$column);
											}
											elseif($column == 'order_source_transactionid' || $column == 'order_source_itemid'  || $column ==  'order_source_order_item_id'){
												if($disable==0){
													//判断是否为纯数字，并且长度大于7
													if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0')){
														$tmp_arr[$column] = (empty($tmp_arr[$column])?'':$tmp_arr[$column]).$item_obj[$column].' '.$enter_key;
													}else{
														$tmp_arr[$column] = (empty($tmp_arr[$column])?'':$tmp_arr[$column]).$item_obj[$column].$enter_key;
													}
													$tmp_arr_deleteener_column[]=$column;
												}
												else
													$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]).$enter_key;
											}
											elseif(strstr($column,'-custom-')){
												$tmp_arr[$column] = $custom_context[$column];
											}
											elseif($column == 'photo_primary'){//图片多于一张时用链接代替
												if($disable==0){
													if(count($order_objone->items)>1){
														$tmp_arr[$column] = (empty($tmp_arr[$column])?'':$tmp_arr[$column]).$item_obj->photo_primary.$enter_key;
														$tmp_arr_deleteener_column[]=$column;
													}
													else{
														$photo_primary=['photo_primary'=>['width'=>100,'height'=>100]];
														$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
													}
												}
												else
													$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]).$enter_key;
											}
											elseif($column == 'order_source_order_id'){
												if($order_objone->order_source=='ebay' && $order_objone->order_capture=='N')
													$tmp_arr[$column] = $order_objone->order_source_srn;
												else
													$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
											}
											else if($column == 'order_source_shipping_method'){
												if($order_objone->order_source=='aliexpress' && $disable==0){
													$txt_temp=json_decode($order_objone->addi_info,true);
													if(!isset($txt_temp['shipping_service']))
														$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]);
													else{
														$shipping_service=$txt_temp['shipping_service'];
														if(isset($shipping_service[$item_obj['order_source_itemid']]))
															$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column].$enter_key).$shipping_service[$item_obj['order_source_itemid']];
														else
															$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]);
													}
												}
												else
													$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
											}
											else if($column == 'attributes'){
												$product_attributes_arr=eagle\modules\order\helpers\OrderListV3Helper::getProductAttributesByPlatformItem($order_objone->order_source, $item_obj['product_attributes']);
												$product_attributes='';
												if(!empty($product_attributes_arr)){
													foreach ($product_attributes_arr as $tmp_product_attributes){
														$product_attributes.=$tmp_product_attributes.';';
													}
												}
												$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]).$product_attributes.$enter_key;
											}
											else if($column == 'user_content'){
												$rt=MessageApiHelper::getOrderLastMessage($order_objone->order_source_order_id);
												$tmp_arr[$column]=htmlspecialchars_decode($rt['content'],ENT_QUOTES);
											}
											else if($column == 'plat_form_account'){
												$re=PlatformAccountApi::getPlatformAllAccount('',$order_objone->order_source,true)["data"];
												$tmp_arr[$column]=(empty($re[$order_objone->selleruserid])?'':$re[$order_objone->selleruserid]);
											}
											else{
												$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
											}
										}
										//END按订单导出==============================================================================================
									}
									else{
										//按商品导出==============================================================================================
										foreach ($items_arr as $column){
											//如果有设置导出商品中文名的，进行商品信息的获取
											if ($column == 'name_cn'){
												if ($hasProduct && $disable==0){
													$tmp_arr['name_cn'] = $product['prod_name_ch'];
												}else{
													$tmp_arr['name_cn'] = '';
												}
											}else if ($column == 'name_en'){
												if ($hasProduct && $disable==0){
													$tmp_arr['name_en'] = $product['prod_name_en'];
												}else{
													$tmp_arr['name_en'] = '';
												}
											}else if ($column == 'prod_weight'){
												if ($hasProduct && $disable==0){
													$tmp_arr['prod_weight'] = $product['prod_weight'];
												}else{
													$tmp_arr['prod_weight'] = '';
												}
											}else if ($column == 'name'){
												if ($hasProduct && $disable==0){
													$tmp_arr['name'] = $product['name'];
												}else{
													$tmp_arr['name'] = '';
												}
											}else if($column == 'root_sku'){
												if ($hasProduct && $disable==0){
													$tmp_arr['root_sku'] = $item_obj['root_sku'];
												}else{
													$tmp_arr['root_sku'] = '';
												}
											}elseif($column == 'declaration_ch'){
												if (!$declaration_list[$item_obj['order_item_id']]['not_declaration'] && $disable==0){
													$tmp_arr['declaration_ch'] = $declaration_list[$item_obj['order_item_id']]['declaration']['nameCN'];
												}else{
													$tmp_arr['declaration_ch'] = '';
												}
											}elseif($column == 'declaration_en'){
												if (!$declaration_list[$item_obj['order_item_id']]['not_declaration'] && $disable==0){
													$tmp_arr['declaration_en'] = $declaration_list[$item_obj['order_item_id']]['declaration']['nameEN'];
												}else{
													$tmp_arr['declaration_en'] = '';
												}
											}elseif($column == 'declaration_value'){
												if (!$declaration_list[$item_obj['order_item_id']]['not_declaration'] && $disable==0){
													$tmp_arr['declaration_value'] = $declaration_list[$item_obj['order_item_id']]['declaration']['price'];
												}else{
													$tmp_arr['declaration_value'] = '';
												}
											}elseif($column == 'declaration_value_currency'){
												if ($hasProduct && $disable==0){
													$tmp_arr['declaration_value_currency'] = $product['declaration_value_currency'];
												}else{
													$tmp_arr['declaration_value_currency'] = '';
												}
											}elseif($column == 'order_manual_id'){
												//不做data conversion 的情况下 沿用 之前的order_manual_id 这个 字段
												$tmp_arr[$column]=OrderTagHelper::getAllTagStrByOrderId($order_objone->order_id);
											}elseif($column == 'photo_url'){
												if($disable==0)
													$tmp_arr[$column]= isset($item_obj['photo_primary'])?$item_obj['photo_primary']:'';
												else
													$tmp_arr[$column]='';
											}
											elseif($column == 'address1_2'){
												$ad1= isset($order_objone->consignee_address_line1)?$order_objone->consignee_address_line1:(isset($item_obj['consignee_address_line1'])?$item_obj['consignee_address_line1']:'');
												$ad2= isset($order_objone->consignee_address_line2)?$order_objone->consignee_address_line2:(isset($item_obj['consignee_address_line2'])?$item_obj['consignee_address_line2']:'');
												$tmp_arr[$column]=$ad1.' '.$ad2;
											}
											elseif($column == 'addressdetail'){
												$ad1= isset($order_objone->consignee_address_line1)?$order_objone->consignee_address_line1:(isset($item_obj['consignee_address_line1'])?$item_obj['consignee_address_line1']:'');
												$ad2= isset($order_objone->consignee_address_line2)?$order_objone->consignee_address_line2:(isset($item_obj['consignee_address_line2'])?$item_obj['consignee_address_line2']:'');
												$ad3= isset($order_objone->consignee_address_line3)?$order_objone->consignee_address_line3:(isset($item_obj['consignee_address_line3'])?$item_obj['consignee_address_line3']:'');
												$adcity= isset($order_objone->consignee_city)?$order_objone->consignee_city:(isset($item_obj['consignee_city'])?$item_obj['consignee_city']:'');
												$adprovince= isset($order_objone->consignee_province)?$order_objone->consignee_province:(isset($item_obj['consignee_province'])?$item_obj['consignee_province']:'');
												$addistrict= isset($order_objone->consignee_district)?$order_objone->consignee_district:(isset($item_obj['consignee_district'])?$item_obj['consignee_district']:'');
												$adcounty= isset($order_objone->consignee_county)?$order_objone->consignee_county:(isset($item_obj['consignee_county'])?$item_obj['consignee_county']:'');
												$adcountry= isset($order_objone->consignee_country)?$order_objone->consignee_country:(isset($item_obj['consignee_country'])?$item_obj['consignee_country']:'');
												$adpostal= isset($order_objone->consignee_postal_code)?$order_objone->consignee_postal_code:(isset($item_obj['consignee_postal_code'])?$item_obj['consignee_postal_code']:'');
												$tmp_arr[$column]=$ad1.' '.$ad2.' '.$ad3.' '.$adcity.' '.$adprovince.' '.$addistrict.' '.$adcounty.' '.$adcountry.' '.$adpostal;
											}
											elseif($column == 'sku_quantity'){
												if($disable==0){
													$sku= isset($order_objone->sku)?$order_objone->sku:(isset($item_obj['sku'])?$item_obj['sku']:'');
													$qty= isset($order_objone->quantity)?$order_objone->quantity:(isset($item_obj['quantity'])?$item_obj['quantity']:'0');
													$tmp_arr[$column]=$sku.'*'.$qty;
												}
												else
													$tmp_arr[$column]='';
											}
											elseif ($column == 'tracknum'){
												$shipped = OdOrderShipped::find()->where(['order_id'=>$order_objone->order_id])->andwhere( " ifnull(tracking_number, '') <> '' " )->orderBy('id desc')->one();
												$tmp_arr[$column] = empty($shipped)?'':$shipped->tracking_number;
		
												//判断是否为纯数字，并且长度大于7
												if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0'))
													$tmp_arr[$column] = $tmp_arr[$column].' ';
											}
											elseif ($column == 'consignee_country_label_cn'){
												$tmp_arr[$column] = empty(StandardConst::$COUNTRIES_CODE_NAME_CN[$order_objone->consignee_country_code])?$order_objone->consignee_country_code:StandardConst::$COUNTRIES_CODE_NAME_CN[$order_objone->consignee_country_code];
											}
											elseif ($column == 'order_item_cost'){
												if($disable==0){
													$orderData = (array)$order_objone->getAttributes();
													$tmpProductCost = OrderApiHelper::getOrderProductCost($orderData, $item_obj['sku']);
													$tmp_arr[$column] = $tmpProductCost['purchase_cost']+$tmpProductCost['addi_cost'];
												}
												else
													$tmp_arr[$column]='';
											}
											elseif($column == 'logistic_status'){
												$tmp_arr[$column] = Tracking::getChineseStatus($order_objone->$column);
											}
											elseif($column == 'order_source_transactionid' || $column == 'order_source_itemid'  || $column ==  'order_source_order_item_id'){
												if($disable==0){
													//判断是否为纯数字，并且长度大于7
													if(!empty($tmp_arr[$column]) && is_numeric($tmp_arr[$column]) && (strlen($tmp_arr[$column]) > 6 || substr($tmp_arr[$column],0,1) == '0')){
														$tmp_arr[$column] = $item_obj[$column].' ';
													}else{
														$tmp_arr[$column] = $item_obj[$column];
													}
												}
												else
													$tmp_arr[$column]='';
											}
											elseif(strstr($column,'-custom-')){
												$tmp_arr[$column] = $custom_context[$column];
											}
											elseif($column == 'order_source_order_id'){
												if($order_objone->order_source=='ebay' && $order_objone->order_capture=='N')
													$tmp_arr[$column] = $order_objone->order_source_srn;
												else
													$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
											}
											else if($column == 'order_source_shipping_method'){
												if($order_objone->order_source=='aliexpress' && $disable==0){
													$txt_temp=json_decode($order_objone->addi_info,true);
													if(!isset($txt_temp['shipping_service']))
														$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]);
													else{
														$shipping_service=$txt_temp['shipping_service'];
														if(isset($shipping_service[$item_obj['order_source_itemid']]))
															$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column].$enter_key).$shipping_service[$item_obj['order_source_itemid']];
														else
															$tmp_arr[$column]=(empty($tmp_arr[$column])?'':$tmp_arr[$column]);
													}
												}
												else
													$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
											}
											elseif($column == 'price' || $column == 'sku' || $column == 'quantity' || $column == 'product_name' || $column == 'seller_weight' || $column == 'default_warehouse_id'){
												if($disable==0)
													$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
												else
													$tmp_arr[$column] = '';
											}
											elseif($column == 'photo_primary'){
												if($disable==0)
													$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
												else
													$tmp_arr[$column]='';
											}
											else if($column == 'attributes'){
												$product_attributes_arr=eagle\modules\order\helpers\OrderListV3Helper::getProductAttributesByPlatformItem($order_objone->order_source, $item_obj['product_attributes']);
												$product_attributes='';
												if(!empty($product_attributes_arr)){
													foreach ($product_attributes_arr as $tmp_product_attributes){
														$product_attributes.=$tmp_product_attributes.';';
													}
												}
												$tmp_arr[$column]=$product_attributes;
											}
											else if($column == 'user_content'){
												$rt=MessageApiHelper::getOrderLastMessage($order_objone->order_source_order_id);
												$tmp_arr[$column]=htmlspecialchars_decode($rt['content'],ENT_QUOTES);
											}
											else if($column == 'plat_form_account'){
												$re=PlatformAccountApi::getPlatformAllAccount('',$order_objone->order_source,true)["data"];
												$tmp_arr[$column]=(empty($re[$order_objone->selleruserid])?'':$re[$order_objone->selleruserid]);
											}
											else{
												$tmp_arr[$column]= isset($order_objone->$column)?$order_objone->$column:(isset($item_obj[$column])?$item_obj[$column]:'');
											}
										}
										//END按商品导出==============================================================================================
									}
									if($isMaster==2){
										$res[] = $tmp_arr;
									}
									unset($hasProduct);
									unset($product);
								}//end of foreach3
								if($isMaster!=2){
									foreach ($tmp_arr_deleteener_column as $tmp_arr_deleteener_columnone){
										$tmp_arr[$tmp_arr_deleteener_columnone]=trim($tmp_arr[$tmp_arr_deleteener_columnone]);
									}
									unset($tmp_arr_deleteener_column);
									if(!empty($tmp_arr)){
										$res[] = $tmp_arr;
										$print_state=0;
									}
								}
							}
							break;
						}
					}//end of foreach2
				}//end of foreach1
				unset($order_obj);
			}
			unset($orderids_arr);
			foreach ($items_arr as $k=>$v){
				foreach ($items_customname_arr as $k1=>$v1){
					if($k1==$v){
						$items_arr[$k]=$v1;
					}
				}
			}
// 								var_dump($res);var_dump($items_arr);var_dump($items_customname_arr);die;
			$rtn=ExcelHelper::exportToExcel($res, $items_arr, 'order_'.date('Y-m-dHis',time()).".xls",$photo_primary,$type,['setWrapText'=>true, 'setWidth'=>20, 'setHorizontal'=>true]);
			$rtn['count'] = count($res);
		
			if($rtn['success'] == 0)
				exit($rtn['message']);
		}
		catch (\Exception $e) {
			$rtn['success'] = 0;
			$rtn['message'] = '导出失败：'.$e->getMessage();
			exit($e->getMessage());
		}
		return $rtn;
	}
	
	public static function bulkLoadOrderItemsToOrderModel(&$orderModels){
		//step 1 先拿到这些order 的order id，然后批量查询
		$orderIds =[];
		foreach ($orderModels as &$anOrderModel){
			$orderIds[] = $anOrderModel->order_id;
			$allItems[(string)$anOrderModel->order_id] = [];
		}//end of each order model
		
		//step 2：一次过 query这些order id 的对应items
		$orderItemModels=OdOrderItem::find()->where(['order_id'=> $orderIds])->all();
		
		//step 3：把不同的order items 放到不同的order models 下面
		foreach ($orderItemModels as $anItem){
			$allItems[(string)$anItem->order_id][] = $anItem;
		}
		
		foreach ($orderModels as &$anOrderModel){
			$anOrderModel->setItemsPT($allItems[(string)$anOrderModel->order_id]);
		}//end of each order model
		
	}
	
	
	public static function bulkLoadOrderShippedModel(&$orderModels){
		//step 1 先拿到这些order 的order id，然后批量查询
		$orderIds =[];
		foreach ($orderModels as &$anOrderModel){
			$orderIds[] = $anOrderModel->order_id;
			$allItems[(int)$anOrderModel->order_id] = [];
		}//end of each order model
	
		$orderItemModels=OdOrderShipped::find()->where(['order_id'=> $orderIds])->all();
	
		//吧不同的order items 放到不同的order models 下面
	
		foreach ($orderItemModels as $anItem){
			$allItems[(int)$anItem->order_id][] = $anItem;
		}
	
		foreach ($orderModels as &$anOrderModel){
			$anOrderModel->setTrackinfosPT($allItems[(int)$anOrderModel->order_id]);
		}//end of each order model
	
	}
	
	
	/*Author YZQ
	 * date 2017-2-21
	 * Order list performance turning， use redis to save the result.
	 * if order got status changed, this result will be purged and re-calculate again.
	 * */
	public static function getCountryAndRegion(){
		global $hitCache;
		$hitCache = "NoHit";
		$cachedArrAll = array();
		$countrys = array();
		$gotCache = RedisHelper::getOrderCache(0,'system',"country") ;
		if (!empty($gotCache)){
			$cachedArrAll = json_decode($gotCache,true);
			$countrys = $cachedArrAll;
			$hitCache= "Hit";
		}
		
		if ($hitCache <>"Hit"){
			$query = SysCountry::find();
			$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
			$countrys =[];
			foreach ($regions as $region){
				$arr['name']= $region['region'];
				$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
				$countrys[]= $arr;
			}
			//save the redis cache for next time use
			RedisHelper::setOrderCache(0,'system',"country",json_encode($countrys)) ;
		}
		return $countrys;
	}
	
	
	public static function getPlatformOrderCountries($puid=0,$platform='amazon',$selleruserid=array() ,$status=''){
		global $hitCache;
		//load from redis cache first, if not, calculate and cache it
		$hitCache = "NoHit";
		$cachedArrAll = array();
		$countryArr = [];
		$gotCache = RedisHelper::getOrderCache($puid,$platform,"nations") ;
		if (empty($status))
			$subKey='';
		else
			$subKey=$status;
		
		if (!empty($gotCache)){
			$cachedArrAll = json_decode($gotCache,true);
			if (isset($cachedArrAll[$subKey])){
				$countryArr = $cachedArrAll[$subKey];
				$hitCache= "Hit";
			}
		}
		 
		if ($hitCache <>"Hit"){
			$countryArr = array();
			$query = OdOrder::find()->select('consignee_country,consignee_country_code')->distinct()//consignee_country_code
			->where(['order_source' => $platform])->andWhere(['selleruserid'=>array_keys($selleruserid)])
			;
			if (!empty($status)){
				$tmpCountryArr = $query->andWhere(['order_status'=>$status])->asArray()->all();
			}else{
				$tmpCountryArr = $query->asArray()->all();
			}
			$countryArr = Helper_Array::toHashmap($tmpCountryArr,'consignee_country_code','consignee_country');
			foreach ($countryArr as $key => $value) {
				if (empty($value)) {
					unset($countryArr[$key]);
				}
			}
		
			//save the redis cache for next time use
			$cachedArrAll[$subKey] = $countryArr;
			RedisHelper::setOrderCache($puid,$platform,"nations",json_encode($cachedArrAll)) ;
		}
		
		return $countryArr;
	}
	
	//拆分订单
	public static function splitOrderReorder($orderIdList ,$module='order',$action='拆分订单',$splotOrderDelList='',$splotOrderqtyList=''){
		$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
		$noFoundOrderCount = count($orderIdList)-count($OrderList);
		if ($noFoundOrderCount>0){
			$msg = "有".$noFoundOrderCount."无效订单 \n";
		}else{
			$msg = "";
		}
		$result = ['success'=>0 , 'failure'=>count($orderIdList)-count($OrderList), 'message'=>$msg];
		$list_clear_platform = []; //需要清除redisear_platform
		\Yii::info('splitOrderReorder,puid:'.\Yii::$app->subdb->getCurrentPuid().',data:'.json_encode($splotOrderDelList).'', "file");
		foreach($OrderList as &$oneOrder){
			$orderid_len11=preg_replace('/^0+/','',$oneOrder->order_id);
// 			while(strlen($orderid_len11)<11){
// 				$orderid_len11='0'.$orderid_len11;
// 			}

			$currentOrder=$splotOrderDelList[$orderid_len11];
			$olditemList_Relation_sonid=array();
			foreach ($currentOrder as $keys=>$currentOrderone){
				$qtysum=0;
				foreach ($currentOrderone as $currentOrderoneone){
					$qtysum+=$currentOrderoneone['quantity'];
				}					
				if(empty($qtysum))
					continue;
				
				
				//  复制出一个新订单
				$newOrder = $oneOrder->attributes;
					
				//释放小老板订单号
				unset($newOrder['order_id']);
				//订单类型 = 已付款
				$newOrder['pay_order_type'] = OdOrder::PAY_REORDER;
					
				//订单状态
				$newOrder['order_status'] = OdOrder::STATUS_PAY;
				//订单异常状态
				$oneOrder->exception_status = 0;
				$newOrder['exception_status'] = 0;
					
				//库存分配状态
				$newOrder['distribution_inventory_status'] = 2;
					
				//配货打印状态，打印人，和打印时间
				unset($newOrder['is_print_distribution']);     //配货打印状态
				unset($newOrder['print_distribution_operator']); //打印人
				unset($newOrder['print_distribution_time']);//打印时间
					
				//物流单打印状态，打印人，打印时间
				unset($newOrder['is_print_carrier']);		//物流单打印状态
				unset($newOrder['print_carrier_operator']);		//打印人
				unset($newOrder['printtime']);				//打印时间
		
		
				//2016-09-14 已出库订单视为 手工订单
				$newOrder['order_capture'] = 'Y';
				$newOrder['sync_shipped_status'] = 'N';// 无须虚拟发货的状态
				$newOrder['order_relation'] = 'normal'; // 变成正常订单
		
		
				//20160928 补发订单时间利润统计相关的金额重置为0
				$newOrder['subtotal'] = 0;			//订单商品金额
				$newOrder['grand_total'] = 0;		// 订单总金额
				$newOrder['commission_total'] = 0; 	// 平台佣金
				$newOrder['logistics_cost'] = 0; 	// 物流成本
		
				if (strtoupper($oneOrder['order_type']) =='AFN'){
					$newOrder['order_type'] = 'MFN';
				}elseif (strtoupper($oneOrder['order_type']) =='FBC'){
					$newOrder['order_type'] = '';
				}
		
				//出库时间，出库人 @todo
					
				//时间 重置
				$newOrder['create_time'] = time();
				$newOrder['update_time'] = time();
					
				/* 重新发货标签
				 'suspend_shipment'=>'暂停发货订单重发',
				'out_of_stock'=>'缺货订单重发',
				'after_shipment'=>'已出库订单补发',
				'cancel_order'=>'已取消订单重发',
				'abandoned_order'=>'废弃订单回收重发',
				*/
				if ($oneOrder['order_status'] == OdOrder::STATUS_CANCEL){
					$newOrder['reorder_type'] = 'cancel_order'; //已取消订单重发
				}else if ($oneOrder['order_status'] == OdOrder::STATUS_SUSPEND){
					$newOrder['reorder_type'] = 'suspend_shipment'; //暂停发货订单重发
				}else if ($oneOrder['order_status'] == OdOrder::STATUS_ABANDONED){
					$newOrder['reorder_type'] = 'abandoned_order';//废弃订单回收重发
				}else if ($oneOrder['order_status'] == OdOrder::STATUS_SHIPPED){
					$newOrder['reorder_type'] = 'after_shipment'; //已出库订单补发
				}elseif ($oneOrder['order_status'] == OdOrder::STATUS_OUTOFSTOCK || $oneOrder['exception_status'] == OdOrder::EXCEP_NOSTOCK){
					//条件 1 为erp2.1 的流程，  条件2为原来 的流程， 两者一者符合都可以设置为缺货后重发
					$newOrder['reorder_type'] = 'out_of_stock'; //缺货订单重发
				}
					
				//新订单号
				$rt = self::doInsert($newOrder);

				if (!empty($rt['success'])){
					$order_id = str_pad( $rt['order_id'],11,'0',STR_PAD_LEFT);
					//更改order_relation状态
					$neworderList = OdOrder::find()->where(['order_id'=>$order_id])->all();
					foreach($neworderList as &$neworderListone){
						$neworderListone->order_relation='ss';
						$neworderListone->save();
					}
					
					$oneOrder->order_relation='fs';
					$oneOrder->save();
// 					print_r($neworderList);die;
					
					//保存成功  , 保存item 数据
					$itemList = [];
		
		
					//获取订单 item信息
					$itemList = OdOrderItem::find()->where(['order_id'=>$oneOrder['order_id']])->asArray()->all();
		
					//重写订单 item信息
					foreach($itemList as &$thisItem){
						//unset($thisItem['order_item_id']);//primary key release
						$thisItem['order_id'] = $order_id;//order id overwrite
						$thisItem['item_source'] = 'local';//item source overwrite
						$thisItem['delivery_status'] = 'allow';//delivery status overwrite
						$thisItem['manual_status'] = 'enable';//manual status overwrite
		
						$thisItem['create_time'] = time();
						$thisItem['update_time'] = time();
					}
		
					if (!empty($itemList)){
						//是否更新待发货数量逻辑
						/* 20170222kh 补发后订单全部变成手工订单都需要计算库存
						 $doPIp_updatePendingQty = false;
		
						if(isset($oneOrder['order_type'])){
						if (!in_array(strtoupper($oneOrder['order_type']), ['AFN' , 'FBC'])){
						$doPIp_updatePendingQty = true;
						}else{
						$doPIp_updatePendingQty = false;
						}
						}else{
						$doPIp_updatePendingQty = true;
						}
						*/
						$doPIp_updatePendingQty = true;
						if ($doPIp_updatePendingQty){
							if (in_array($oneOrder['order_status'], [OdOrder::STATUS_PAY])){
								$doPIp_updatePendingQty = true;
							}else{
								$doPIp_updatePendingQty = false;
							}
						}
						
						//拆分订单
						$itemList_temp=$itemList;
						$newitemList_temp=array();
						foreach ($itemList_temp as $itemList_temp_keys=> &$itemList_tempone){
							foreach ($currentOrderone as $item_id => $currentOrderoneone){
								if($itemList_tempone['order_item_id']==$item_id && $itemList_tempone['sku']==$currentOrderoneone['sku'] && !empty($currentOrderoneone['sku']) && $currentOrderoneone['quantity']>0){
									unset($itemList_tempone['order_item_id']);//primary key release
									
									$itemList_tempone['quantity']=$currentOrderoneone['quantity'];  //需发货数量
									$itemList_tempone['ordered_quantity']=$currentOrderoneone['quantity'];   //下单时数量
									$newitemList_temp[]=$itemList_tempone;
									
									break;
								}
							}
						}
						$itemList=$newitemList_temp;
						$effect = self::doProductImport($itemList , $order_id, $oneOrder['order_source'],$doPIp_updatePendingQty,$oneOrder['order_status'],$oneOrder['selleruserid']);
							
						if ($effect == count($itemList)){
							//重置 新的订单发货流程
							DeliveryApiHelper::resetOrder([$rt['order_id']]);
															
							//旧订单生成  行为日志
							OperationLogHelper::log($module,$oneOrder['order_id'],$action,'拆分订单，生成新的小老板单号为:'.$order_id,\Yii::$app->user->identity->getFullName());
							//新订单生成  行为日志
		
							OperationLogHelper::log($module,$order_id,$action,'拆分订单，来源的小老板单号为:'.$oneOrder['order_id'],\Yii::$app->user->identity->getFullName());
		
							$result['success']++; // 统计成功订单数量
								
							//增加清除 平台redis
							if (!in_array($oneOrder['order_source'], $list_clear_platform)){
								$list_clear_platform[] = $oneOrder['order_source'];
							}
							
							$olditemList_Relation_sonid[]=$order_id;
							
						}else{
							\Yii::error((__FUNCTION__)." : ".$order_id.'保存相关商品失败 ！ ',"file");
							$msg .=$order_id."保存相关商品失败！\n";
							$result['failure']++; // 统计失败订单数量
							if (empty($effect)){
								//假如 没有任何商品都保存成功则删除订单头
								OdOrder::deleteAll(['order_id'=>$rt['order_id']]); //考虑性能问题， 还是不采用事务方式
							}	
						}
					}else {
						\Yii::error((__FUNCTION__)." : ".$order_id.' 找不到相关商品 ！ ',"file");
						$msg .=$order_id."找不到相关商品";
						$result['failure']++; // 统计失败订单数量
						OdOrder::deleteAll(['order_id'=>$rt['order_id']]); //考虑性能问题， 还是不采用事务方式
					}
		
				}else{
					//保存失败
					\Yii::error((__FUNCTION__)." : ".$oneOrder['order_id'].' error message = '.$rt['message'],"file");
					$msg .= $oneOrder['order_id']." ".$action."失败！失败原因：".$rt['message'].'\n';
					$result['failure']++; // 统计失败订单数量
		
				}
				
			}
						
			//插入od_order_relation
			self::saveOrderRelation([$oneOrder['order_id']],$olditemList_Relation_sonid,'split');
			
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "拆分订单, 来源订单: ".$oneOrder['order_id'].", 生成新的订单: ".implode(', ', $olditemList_Relation_sonid));

			//修改原订单的数量
			$olditemList = OdOrderItem::find()->where(['order_id'=>$oneOrder['order_id']])->all();
			foreach ($olditemList as &$olditemListone){
				$orderitemid_len11=preg_replace('/^0+/','',$olditemListone->order_id);
// 				while(strlen($orderitemid_len11)<11){
// 					$orderitemid_len11='0'.$orderitemid_len11;
// 				}
				
				$splotOrderqtyList_temp=$splotOrderqtyList[$orderitemid_len11];			
				foreach ($splotOrderqtyList_temp as $keys=>$splotOrderqtyList_tempone){
					if($keys==$olditemListone->order_item_id){
						$olditemListone->quantity=$splotOrderqtyList_tempone['qty'];
						if(empty($splotOrderqtyList_tempone['qty'])){
							$olditemListone->manual_status='disable';
							$olditemListone->delivery_status='ban';
						}
						$olditemListone->save();
					}
				}
			}
		
		}//end of each order

		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
			foreach ($list_clear_platform as $platform){
				//echo "$platform is reset !";
				RedisHelper::delOrderCache(\Yii::$app->subdb->getCurrentPuid(),$platform,'Menu StatisticData');
			}
		}
		
		return $result;
	}
	
	//取消拆分订单
	public static function splitOrderReorderCancel($orderid){
		$OrderRelation=OrderRelation::find()->select(['father_orderid'])->where(['father_orderid'=>$orderid,'type'=>'split'])->orWhere(['son_orderid'=>$orderid,'type'=>'split'])->asArray()->one();
			
		$result=['code'=>0,'message'=>''];
		$msg='';
		$list_clear_platform = []; //需要清除redisear_platform
					
		if(!empty($OrderRelation)){
			$obOrderRelation=OrderRelation::find()->where(['father_orderid'=>$OrderRelation['father_orderid'],'type'=>'split'])->asArray()->all();
				
			//返回数量给主订单
			$orderitem=OdOrderItem::find()->where(['order_id'=>$obOrderRelation[0]['father_orderid']])->all();
			$order=OdOrder::find()->where(['order_id'=>$obOrderRelation[0]['father_orderid']])->all();
			$orderIdList=array();
			foreach ($obOrderRelation as $obOrderRelationone){
				$orderitem_son=OdOrderItem::find()->where(['order_id'=>$obOrderRelationone['son_orderid']])->all();
				$orderIdList[]=$obOrderRelationone['son_orderid'];
				foreach ($orderitem_son as $orderitem_sonone){
					foreach ($orderitem as &$orderitemone){
						if($orderitemone->sku==$orderitem_sonone->sku && $orderitemone->ordered_quantity > $orderitemone->quantity){
							
							$cancelstatus=OrderUpdateHelper::getCanNotShipOrderItemStatus($order[0]->order_source);
							//订单商品是取消状态的不启用
							if(!in_array($orderitem_sonone->platform_status,$cancelstatus)){
								$orderitemone->quantity+=$orderitem_sonone->ordered_quantity;
								$orderitemone->manual_status='enable';
								$orderitemone->delivery_status='allow';
								$orderitemone->save();
								
								break;
							}
						}
					}
				}
			}
				
			//更改主订单状态
			foreach ($order as &$orderone){
				$orderone->order_relation='normal';
				$orderone->save();
				//增加清除 平台redis
				if (!in_array($orderone->order_source, $list_clear_platform)){
					$list_clear_platform[] = $orderone->order_source;
				}
				OperationLogHelper::log('order',$orderone->order_id,'取消拆分订单','取消拆分订单，小老板单号为:'.$orderone->order_id.';子订单号为:'.json_encode($orderIdList),\Yii::$app->user->identity->getFullName());
			}
				
			//删除子订单
			$rt = OrderHelper::deleteManualOrder($orderIdList);
			if($rt['success'] == false){
				$msg.=$rt['message'];
				$result['code']=1;
			}
				
			//删除关联订单
			foreach ($orderIdList as $orderIdListone){
				$rt = OrderRelation::deleteAll(['son_orderid'=>$orderIdListone , 'type'=>'split']);
			}
			
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "取消拆分订单, 小老板单号: ".$orderone->order_id.", 子订单号: ".implode(', ', $orderIdList));
			
		}
		else{
			$result['code']=1;
			$msg.='找不到关联的表;';
		}
		
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
			foreach ($list_clear_platform as $platform){
				//echo "$platform is reset !";
				RedisHelper::delOrderCache(\Yii::$app->subdb->getCurrentPuid(),$platform,'Menu StatisticData');
			}
		}
			
		return $result;
	}

	//整理订单集合中，所有root_sku的图片
	public static function GetRootSkuImage($orders){
		if(empty($orders)){
			return array();
		}
		
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$selleruserid_site_setting_arr = array();
		$order_rootsku_product_image = array();
		foreach($orders as $order){
		    // dzt20190710 for 导入订单显示匹配到产品的产品图片
			if($order->order_source != 'linio' || $order->order_capture == "Y")
				continue;
			
			//判断是否开启了用linio订单图片用商品库图片替换
			$is_oms_use_product_image = false;
			$key = $order->selleruserid.'_'.$order->order_source_site_id;
			if(array_key_exists($key, $selleruserid_site_setting_arr)){
				$is_oms_use_product_image = $selleruserid_site_setting_arr[$key];
			}
			else{
				$is_oms_use_product_image = eagle\modules\lazada\apihelpers\LazadaApiHelper::checkIfLinioUserOmsUseProductImage($puid, $order->selleruserid, $order->order_source_site_id);
					
				$selleruserid_site_setting_arr[$key] = $is_oms_use_product_image;
			}
			
			if($is_oms_use_product_image){
				$root_sku_arr = array();
				foreach ($order->items as $key => $item){
					if(!empty($item->root_sku)){
						$root_sku_arr[] = $item->root_sku;
					}
				}
			
				if(!empty($root_sku_arr)){
					$pros = Product::find()->select(['sku', 'photo_primary'])->where(['sku' => $root_sku_arr])->asArray()->all();
					if(!empty($pros)){
						foreach($pros as $pro){
							if(!empty($pro['photo_primary'])){
								$order_rootsku_product_image[$order->order_id][$pro['sku']] = $pro['photo_primary'];
							}
						}
					}
				}
			}
		}
		return $order_rootsku_product_image;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单打回已付款
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$orderIdList			order id 的数组
	 +---------------------------------------------------------------------------------------------
	 * @return				na
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 	      已付款:
	 * 			1.将“已付款”状态改成“暂停发货”，此处暂停发货不需要释放占用库存
	 *
	 *    发货中 :
	 *    		1.将“发货中”状态改成“暂停发货”。此处当订单已经分配了库存，那么暂停的时候需要将占用的库存释放掉。
	 *    		2.如果已经生成了拣货单，那么需要将拣货单中的这个订单删除。
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2017/08/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function repulsePaidOrders($orderIdList ,$module='order',$action='打回已付款'){
		$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
		$rtn['success'] = true;
		$rtn['message'] = "";
		$rtn['code'] = "";
		//var_dump($OrderList);die;
		$list_clear_platform = []; //需要清除redis
		$edit_log = array();
		foreach($OrderList as $oneOrder){
			//1.取消成功的预约
			$r1 = InventoryApiHelper::OrderProductReserveCancel($oneOrder->order_id,$module,$action);
			if ($r1['success'] == false){
				$rtn = $r1;
			}
				
			//2.删除拣货单
			$r2 = DeliveryApiHelper::cancelOrderDeliveryMapping($oneOrder->order_id,$module,$action);
			if ($r2['success'] == false){
				$rtn = $r2;
			}
			
			if ($rtn['success']){
				$data = [
				'order_status'=>OdOrder::STATUS_PAY,
				//发货
				'delivery_status'=>'0',
				'delivery_id'=>'0',
				'distribution_inventory_status'=>'0',
				//拣货
				'is_print_picking'=>'0',
				'print_picking_time'=>'0',
				//配货
				'is_print_distribution'=>'0',
				'print_distribution_time'=>'0',
				//物流
				'is_print_carrier'=>'0',
				'print_carrier_operator'=>'0',
				'printtime'=>'0',
				'tracking_number'=>'',
				'carrier_step'=>OdOrder::CARRIER_CANCELED,//重新上传
				];
				$fullName = \Yii::$app->user->identity->getFullName();
				$updateRT = OrderUpdateHelper::updateOrder($oneOrder, $data,false,$fullName , $action , $module);
	
				if ($updateRT['ack']){
					//增加清除 平台redis
					if (!in_array($oneOrder->order_source, $list_clear_platform)){
						$list_clear_platform[] = $oneOrder->order_source;
					}
						
					$edit_log[] = $oneOrder->order_id;
				}else{
					$rtn['message'] .= $oneOrder->order_id."暂停失败！";
				}
				if (!empty($updateRT['message'])){
					\Yii::error((__FUNCTION__)." order_id=".$oneOrder->order_id." order_source_order_id=".$oneOrder->order_source_order_id."  ".$updateRT['message'],"file");
						
				}
			}
				
		}//end of each order
	
		if(!empty($edit_log)){
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "打回已付款, 订单号: ".implode(', ', $edit_log));
		}
		
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
		}
		return $rtn;
	}//end of suspendOrders
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 缺货、暂停发货订单，恢复原来发货状态
	 +---------------------------------------------------------------------------------------------
	 * @access	static
	 +---------------------------------------------------------------------------------------------
	 * @param	$orderIdList			order id 的数组
	 +---------------------------------------------------------------------------------------------
	 * @return				
	 +---------------------------------------------------------------------------------------------
	 * @description	根据标记缺货、暂停发货时，记录下来的状态，重新恢复原来的状态，预约过库存的需重新预约库存
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/09/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function recoveryOrders($orderIdList ,$module='order',$action='恢复发货'){
		$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
		$rtn['success'] = true;
		$rtn['message'] = "";
		$rtn['code'] = "";
		
		$list_clear_platform = []; //需要清除redis
		$edit_log = array();
		foreach($OrderList as $oneOrder){
			$status = true;
			//从addi_info找回之前记录的状态信息
			$old_status_info = array();
			if(!empty($oneOrder->addi_info)){
				if (!empty($oneOrder->addi_info)){
					$addInfo = json_decode($oneOrder->addi_info,true);
					if(!empty($addInfo['old_status_info'])){
						$old_status_info = $addInfo['old_status_info'];
					}
				}
			}
			
			if(!empty($old_status_info)){
				//判断是否需要重新预约库存
				if(!empty($old_status_info['delivery_status']) && $old_status_info['delivery_status'] == OdOrder::DELIVERY_PICKING){
				    $skuInfo_arr = OrderApiHelper::getProductsByOrder($oneOrder->order_id);
				    $products=[];
				    //处理订单的预约sku信息
				    foreach ($skuInfo_arr as $one){
				    	if (!empty($one['root_sku']) && strlen($one['root_sku'])>0){
				    		$products[] = array('sku'=>$one['root_sku'] ,'qty'=>$one['qty'],'order_id'=>$oneOrder->order_id);
				    	}
				    }
				    if(!empty($products)){
	    			    //重新预约库存
	    			    $rtn2 = InventoryApiHelper::OrderProductReserve($oneOrder->order_id, $oneOrder->default_warehouse_id, $products);
	    			    if ($rtn2['success'] !== true){
	    			    	$status = false;
	    			    	$rtn['message'] .= $oneOrder->order_id."恢复发货失败：".TranslateHelper::t($rtn2['message'])."<br>";
	    			    }
				    }
				}
			    
				if($status){
				    $fullName = \Yii::$app->user->identity->getFullName();
				    $updateRT = OrderUpdateHelper::updateOrder($oneOrder, $old_status_info, false, $fullName, $action, $module);
				    
				    if ($updateRT['ack']){
				    	//增加清除 平台redis
				    	if (!in_array($oneOrder->order_source, $list_clear_platform)){
				    		$list_clear_platform[] = $oneOrder->order_source;
				    	}
				    	
				    	$edit_log[] = $oneOrder->order_id;
				    
				    }else{
				    	$rtn['message'] .= $oneOrder->order_id."恢复发货失败！<br>";
				    }
				    if (!empty($updateRT['message'])){
				    	\Yii::error((__FUNCTION__)." order_id=".$oneOrder->order_id." order_source_order_id=".$oneOrder->order_source_order_id."  ".$updateRT['message'],"file");
				    
				    }
				}
			}
		}//end of each order
		
		if(!empty($rtn['message'])){
			$rtn['success'] = false;
		}
		
		if(!empty($edit_log)){
			//写入操作日志
			UserHelper::insertUserOperationLog('order', "恢复发货, 订单号: ".implode(', ', $edit_log));
		}
	
		//left menu 清除redis
		if (!empty($list_clear_platform)){
			OrderHelper::clearLeftMenuCache($list_clear_platform);
		}
		
		return $rtn;
	}//end of recoveryOrders
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 增加指定订单Item的addi_info信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $OrderSourceOrderID	string	 	平台订单号
	 * @param     $selleruserid			string		卖家账号
	 * @param     $source_itemid		string		线上itemID
	 * @param     $platform				string	 	订单平台 如ebay， aliexpress
	 * @param     $currentInfo			array	 	addi_info新增的内容或者是修改的内容
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									是否成功
	 * 									相关信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/10/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function updateOrderItemAddiInfoByOrderID($OrderSourceOrderID, $selleruserid, $source_itemid, $platform , $currentInfo){
		try {
			if (empty($OrderSourceOrderID)){
				return [false , '平台订单号的参数无效'];
			}
				
			if (empty($selleruserid)){
				return [false , '卖家账号的参数无效'];
			}
				
			if (empty($source_itemid)){
				return [false , 'itemID的参数无效'];
			}
			
			if (empty($platform)){
				return [false , '平台的参数无效'];
			}
				
			if (empty($currentInfo)){
				return [false , 'addiinfo的参数无效'];
			}
			$isForce = false ;
			$fullName = 'System';
			$action='同步订单' ;
			$module ='order';
				
			$order = OdOrder::find()->where(['order_source_order_id'=>$OrderSourceOrderID , 'selleruserid'=>$selleruserid , 'order_source'=>$platform])->one();
			if(empty($order)){
				return [false , '找不到相关订单'];
			}
			$item = OdOrderItem::find()->where(['order_id'=>$order->order_id, 'order_source_itemid'=>$source_itemid])->one();
			if(empty($item)){
				return [false , '找不到相关订单明细'];
			}
			if (!empty($item->addi_info)){
				$addInfo = json_decode($item->addi_info,true);
			}else{
				$addInfo = [];
			}
				
			foreach($currentInfo as $key=>$value){
				$addInfo[$key] = $value;
			}
				
			if (!empty($addInfo)){
				$item->addi_info = json_encode($addInfo);
				//$rt = OrderUpdateHelper::updateOrder($order, $newAttr,$isForce,$fullName,$action,$module);
				$item->save();
				return [true , ''];
			}else{
				return [false , 'addiinfo的参数异常'];
			}
		} catch (\Exception $e) {
			return [false, $e->getMessage()];
		}
	
	}//end of function updateAddiInfoByOrderID
}
