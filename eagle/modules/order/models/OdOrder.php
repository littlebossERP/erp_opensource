<?php

namespace eagle\modules\order\models;

use Yii;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\carrier\models\SysShippingService;
use eagle\models\QueueSyncshipped;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\order\helpers\CdiscountOrderInterface;

/**
 * This is the model class for table "od_order_v2".
 *
 * @property string $order_id
 * @property integer $order_status
 * @property integer $pay_status
 * @property string $order_source_status
 * @property string $order_manual_id
 * @property integer $is_manual_order
 * @property integer $shipping_status
 * @property integer $exception_status
 * @property string $weird_status
 * @property string $order_source
 * @property string $order_type
 * @property string $order_source_order_id
 * @property string $order_source_site_id
 * @property string $selleruserid
 * @property string $saas_platform_user_id
 * @property string $order_source_srn
 * @property string $customer_id
 * @property string $source_buyer_user_id
 * @property string $order_source_shipping_method
 * @property string $order_source_create_time
 * @property string $subtotal
 * @property string $shipping_cost
 * @property string $antcipated_shipping_cost
 * @property string $actual_shipping_cost
 * @property string $discount_amount
 * @property string $commission_total
 * @property string $grand_total
 * @property string $returned_total
 * @property string $price_adjustment
 * @property string $currency
 * @property string $consignee
 * @property string $consignee_postal_code
 * @property string $consignee_phone
 * @property string $consignee_mobile
 * @property string $consignee_fax
 * @property string $consignee_email
 * @property string $consignee_company
 * @property string $consignee_country
 * @property string $consignee_country_code
 * @property string $consignee_city
 * @property string $consignee_province
 * @property string $consignee_district
 * @property string $consignee_county
 * @property string $consignee_address_line1
 * @property string $consignee_address_line2
 * @property string $consignee_address_line3
 * @property integer $default_warehouse_id
 * @property string $default_carrier_code
 * @property string $default_shipping_method_code
 * @property string $paid_time
 * @property string $delivery_time
 * @property string $create_time
 * @property integer $update_time
 * @property string $user_message
 * @property integer $carrier_type
 * @property integer $hassendinvoice
 * @property string $seller_commenttype
 * @property string $seller_commenttext
 * @property integer $status_dispute
 * @property integer $is_feedback
 * @property integer $rule_id
 * @property string $customer_number
 * @property integer $carrier_step
 * @property integer $is_print_picking
 * @property integer $print_picking_operator
 * @property integer $print_picking_time
 * @property integer $is_print_distribution
 * @property integer $print_distribution_operator
 * @property integer $print_distribution_time
 * @property integer $is_print_carrier
 * @property integer $print_carrier_operator
 * @property integer $printtime
 * @property integer $delivery_status
 * @property string $delivery_id
 * @property string $desc
 * @property string $carrier_error
 * @property integer $is_comment_status
 * @property integer $is_comment_ignore
 * @property string $issuestatus
 * @property string $payment_type
 * @property string $logistic_status
 * @property string $logistic_last_event_time
 * @property integer $fulfill_deadline
 * @property string $profit
 * @property string $logistics_cost
 * @property string $logistics_weight
 * @property string $addi_info
 * @property integer $distribution_inventory_status
 * @property string $reorder_type
 * @property integer $purchase_status
 * @property string $pay_order_type
 * @property integer $order_evaluation
 * @property string $tracker_status
 * @property string $origin_shipment_detail
 * @property string $shipping_notified
 * @property string $pending_fetch_notified
 * @property string $delivery_failed_notified
 * @property string $rejected_notified
 * @property string $received_notified
 * @property string $order_ship_time
 */
class OdOrder extends \yii\db\ActiveRecord
{
###############物流商种类#############################################################################################
	static $carrier_type = [
		'1'=>'api',
		'2'=>'excel',
		'3'=>'trackno',
	];
	static $delivery_carrier_step = [
		'0'=>[0,4],//UPLOAD
		'1'=>1,//DELIVERY
		'2'=>[2,3,5],//DELIVERYED
		'6'=>6,//waitFINISHED
	];
	static $carrierprocess_carrier_step = [
		'api'=>[
			'UPLOAD'=>[
				'name'=>'待上传',
				'value'=>[0,4]
			],
			'DELIVERY'=>[
				'name'=>'待交运',
				'value'=>1
			],
			'DELIVERYED'=>[
				'name'=>'已交运',
				'value'=>[2,3,5]
			],
			'FINISHED'=>[
				'name'=>'已完成',
				'value'=>6
			],
		],
		'excel'=>[
			'EXPORT'=>[
				'name'=>'未导出',
				'value'=>[0,4]
			],
			'EXPORTED'=>[
				'name'=>'已导出',
				'value'=>[1,3]
			],
			'FINISHED'=>[
				'name'=>'已完成',
				'value'=>6
			],
		],
		'trackno'=>[
			'EXPORT'=>[
				'name'=>'未分配',
				'value'=>[0,4]
			],
			'EXPORTED'=>[
				'name'=>'已分配',
				'value'=>[1,3]
			],
			'FINISHED'=>[
				'name'=>'已完成',
				'value'=>6
			],
		],
	];
###############物流商下单状态#############################################################################################
	const ORDER_WAITPOST = 0;
	const ORDER_WAITDELIVERY = 1;
	const ORDER_FINISHED = 6;
###############小老板订单流程状态#############################################################################################
	const STATUS_WAITACCEPT = '50';//待接受
	const STATUS_NOPAY = '100';//未付款
	const STATUS_PAY = '200';//已付款
	const STATUS_WAITSEND = '300';//发货中
	const STATUS_SHIPPING = '400';//已发货
	const STATUS_SHIPPED = '500';//已完成
	const STATUS_CANCEL = '600';//已取消
	const STATUS_SUSPEND = '601';//暂停订单
	const STATUS_OUTOFSTOCK = '602';//缺货订单
	const STATUS_REFUND = '603';//退款订单
	const STATUS_RETURN = '604';//退货订单
	const STATUS_CLAIM = '605';//理赔订单
	const STATUS_ABANDONED = '699';//废弃订单
	static $status=[
			'100'=>'未付款',
			'200'=>'已付款',
			'300'=>'发货中',
			'400'=>'已发货',
			'500'=>'已完成',
			'600'=>'已取消',
			'601'=>'暂停',
			'602'=>'缺货',
	        '50'=>'未接受',
// 			'603'=>'退款',
// 			'604'=>'退货',
// 			'699'=>'废弃',
			];
	
###############异常状态#############################################################################################
	const EXCEP_HASMESSAGE = '201';
	const EXCEP_HASNOSHIPMETHOD = '202';
	const EXCEP_NODEFAULTWAREHOUSE = '203';
	const EXCEP_PAYPALWRONG = '221';
	const EXCEP_SKUNOTMATCH = '210';
	const EXCEP_NOSTOCK = '222';
	const EXCEP_WAITMERGE = '223';
	const EXCEP_WAITSEND = '299';
//	const EXCEP_MATCHSHIPPINGSERVICE = '230';
	static $exceptionstatus = [
			'201' => '有留言',
			'202' => '未匹配到物流',
			'203'=>'未分配仓库',
			'221'=>'paypal账号不符',
			'210' => 'SKU不存在',
			'222'=>'库存不足',
			'223'=>'待合并',
			'299'=>'可发货'
//			215 => '报关信息不全',
// 			220 => 'paypal/ebay金额不对',
// 			225 => 'paypal/ebay地址不对',
//			230 => '未匹配物流',
		];
###############速卖通平台订单状态#############################################################################################
	static $aliexpressStatus=[
			''=>'',
			'PLACE_ORDER_SUCCESS'=>'等待买家付款',
			'IN_CANCEL'=>'买家申请取消',
			'WAIT_SELLER_SEND_GOODS'=>'等待您发货',
			'SELLER_PART_SEND_GOODS'=>'部分发货',
			'WAIT_BUYER_ACCEPT_GOODS'=>'等待买家收货',
			'FUND_PROCESSING'=>'等待退放款',
			'FINISH'=>'已结束',
			'IN_ISSUE'=>'含纠纷',
			'IN_FROZEN'=>'冻结中',
			'WAIT_SELLER_EXAMINE_MONEY'=>'等待您确认金额',
			'RISK_CONTROL'=>'处于风控中',
			];
###############付款状态#############################################################################################	
	static $paystatus=[
		0=>'未付款',
		1=>'已付款',
		2=>'支付中',
		3=>'已退款'
	];
###############付款订单类型#############################################################################################	
	const PAY_PENDING = 'pending'; // 待检测
	const PAY_REORDER = 'reorder';    //重新发货
	const PAY_EXCEPTION = 'exception';  //有异常
	const PAY_CAN_SHIP = 'ship';  //可发货
	const PAY_NOSKU = 'exception';  //有异常
	const PAY_MERGED = 'merged';  //可发货
	
	static $payOrderType = [
		self::PAY_PENDING => '待检测' , 
		self::PAY_REORDER => '重新发货' ,
		self::PAY_EXCEPTION => '有异常' ,
		self::PAY_CAN_SHIP => '可发货' ,
		
	];
###############付款订单类型#############################################################################################
	const ORDER_VERIFY_PENDING = 'pending'; // 待验证
	const ORDER_VERIFY_VERIFIED = 'verified'; // 已验证
###############发货流程状态#############################################################################################	
	const DELIVERY_DISTRIBUTION_INVENTORY = '0';
	const DELIVERY_PLANCEANORDER = '1';
	const DELIVERY_PICKING = '2';
	const DELIVERY_DISTRIBUTION = '3';
	//4、5留给核对商品和包装两个流程
	const DELIVERY_OUTWAREHOUSE = '6';
	/* static $deliveryStatus=[
			0=>'等待打印拣货单',
			1=>'拣货中',
			2=>'拣货完成',
			3=>'配货完成',
			]; */
	static $deliveryStatus=[
			'0'=>'物流商下单',		//原来的的状态为分配库存，2.1c版本这个状态不要，又不想影响其他逻辑，提交发货/提交物流/暂停发货的这个状态还是为0，只是为了做兼容
			'1'=>'物流商下单',
			'2'=>'打包出库',			//拣货
			'3'=>'打包出库',		//分拣配货
			'6'=>'打包出库',		//出库			//变为待打印，兼容之前的的数据
			];
###############发货流程状态显示#############################################################################################
	static $showdeliveryStatus=[
	0=>'待处理',
	1=>'发货中',
	2=>'发货中',
	3=>'配货完成',
	];
###############物流商下单操作状态#############################################################################################
	public static $carrier_step = array(
			0=>'待上传至物流商',
			1=>'待交运',
			2=>'待获取物流号',
			3=>'待打印物流单',
			4=>'重新上传',
			5=>'E邮宝重新发货',
			6=>'物流已完成',
	);
    const CARRIER_WAITING_UPLOAD = 0;
    const CARRIER_WAITING_DELIVERY = 1;
    const CARRIER_WAITING_GETCODE = 2;
    const CARRIER_WAITING_PRINT = 3;
    const CARRIER_CANCELED = 4;
    const CARRIER_WAITING_RECREATE = 5;
    const CARRIER_FINISHED = 6;

###############重新发货订单类型#############################################################################################	
	static $reorderType = [
		'suspend_shipment'=>'暂停发货订单重发',
		'out_of_stock'=>'缺货订单重发',
		'after_shipment'=>'已出库订单补发',
		'cancel_order'=>'已取消订单重发',
		'abandoned_order'=>'废弃订单回收重发',
	];
###############订单采购状态#############################################################################################	
	CONST PURCHASE_NORMAL = 0;
	CONST PURCHASE_PENDING = 1;
	CONST PURCHASE_HANDLE = 2;
	CONST PURCHASE_ON_THE_WAY = 3;
	CONST PURCHASE_IN_STOCK = 4;
	static $purchaseStatus = [
			self::PURCHASE_NORMAL =>'正常',
			self::PURCHASE_PENDING =>'待采购',
			self::PURCHASE_HANDLE=>'采购中',
			//self::PURCHASE_ON_THE_WAY=>'在途中',
			self::PURCHASE_IN_STOCK=>'采购入库',
			
	];
	
###############订单评价 #############################################################################################	 
	const ORDER_EVALUATION_GOOD = 1;
	const ORDER_EVALUATION_MEDIUM = 2;
	const ORDER_EVALUATION_BAD = 3;
	
	static $orderEvaluation = [
		self::ORDER_EVALUATION_GOOD => '好评',
		self::ORDER_EVALUATION_MEDIUM => '中评',
		self::ORDER_EVALUATION_BAD => '差评',
	];
	
###############退货状态 #############################################################################################	
	CONST ORDER_RETURN_NORMAL = 0;
	CONST ORDER_RETURN_PENDING = 1;
	CONST ORDER_RETURN_HANDLE = 2;
	CONST ORDER_RETURN_FINISH = 3;
	
	static $orderReturn = [
		//self::ORDER_RETURN_NORMAL =>'正常',
		self::ORDER_RETURN_PENDING =>'待退货',
		self::ORDER_RETURN_HANDLE =>'退货中',
		self::ORDER_RETURN_FINISH =>'退货完成',
		
	];
	
	
###############退款状态 #############################################################################################	
	CONST ORDER_REFUND_NORMAL = 0;
	CONST ORDER_REFUND_PENDING = 1;
	CONST ORDER_REFUND_HANDLE = 2;
	CONST ORDER_REFUND_FINISH = 3;
	
	static $orderRefund = [
		//self::ORDER_REFUND_NORMAL =>'正常',
		self::ORDER_REFUND_PENDING =>'待退款',
		self::ORDER_REFUND_HANDLE =>'退款中',
		self::ORDER_REFUND_FINISH =>'退款完成',
	];
	
###############库存分配状态#############################################################################################
	CONST DISTRIBUTION_INVENTORY_NO = 2;
	CONST DISTRIBUTION_INVENTORY_OUTOFSTOCK = 3;
	CONST DISTRIBUTION_INVENTORY_ALREADY = 4;
	
	static $distributionInventoryStatus = [
			self::DISTRIBUTION_INVENTORY_NO =>'待分配',
			self::DISTRIBUTION_INVENTORY_OUTOFSTOCK =>'缺货',
			self::DISTRIBUTION_INVENTORY_ALREADY =>'已分配',
			];

###############平台发货状态#######################################################################################
    CONST NO_INFORM_DELIVERY = 0;   //未通知平台发货
    CONST PROCESS_INFORM_DELIVERY = 2;  //通知平台发货中
    CONST ALREADY_INFORM_DELIVERY = 1;  //已通知平台发货

    static $shippingStatus = [
        self::NO_INFORM_DELIVERY =>'未通知平台发货',
        self::PROCESS_INFORM_DELIVERY =>'通知平台发货中',
        self::ALREADY_INFORM_DELIVERY =>'已通知平台发货',
    ];


###############是否存在跟踪号订单类型#######################################################################################
    CONST NO_TRACKING_NUMBER_ORDER = 0;
    CONST EXSIT_TRACKING_NUMBER_ORDER = 1;

    static $isexsittrackingnumberOrder = [
        self::NO_TRACKING_NUMBER_ORDER =>'无跟踪号的订单',
        self::EXSIT_TRACKING_NUMBER_ORDER =>'有跟踪号的订单',
    ];
	

###############订单平台来源#############################################################################################
	CONST ORDER_SOURCE_EBAY = 'ebay';
	CONST ORDER_SOURCE_AMAZON = 'amazon';
	CONST ORDER_SOURCE_ALIEXPRESS = 'aliexpress';
	CONST ORDER_SOURCE_WISH = 'wish';
	CONST ORDER_SOURCE_DHGAGE = 'dhgate';
	CONST ORDER_SOURCE_CDISCOUNT = 'cdiscount';
	CONST ORDER_SOURCE_LAZADA = 'lazada';
	CONST ORDER_SOURCE_LINIO = 'linio';
	CONST ORDER_SOURCE_JUMIA = 'jumia';
	CONST ORDER_SOURCE_ENSOGO = 'ensogo';
	CONST ORDER_SOURCE_BONANZA = 'bonanza';
	CONST ORDER_SOURCE_PRICEMINISTER = 'priceminister';
	static $orderSource = [
			//'ensogo'=>'Ensogo',
			'ebay'=>'eBay',
			'amazon'=>'Amazon',
			'aliexpress'=>'Aliexpress',
			'wish'=>'Wish',
			'dhgate'=>'DHGate',
			'cdiscount'=>'Cdiscount',
			'lazada'=>'Lazada',
			'linio'=>'Linio',
			'jumia'=>'Jumia',
			'priceminister'=>'Priceminister',
	        'bonanza'=>'bonanza',
// 	        'rumall'=>'Rumall',
	        'newegg'=>'Newegg',
	        'customized'=>'自定义店铺',
	        'shopee'=>'Shopee',
			];
	
	private $orderShippedCache= 'NA';
	private $orderItemsCache= 'NA';
###############支持自动标记发货的平台#############################################################################################
	static $autoShippingPlatform=array( 'ebay','amazon','aliexpress','wish','dhgate','cdiscount','priceminister','linio' );
	static $no_autoShippingPlatform=array( 'lazada','jumia','ensogo','bonanza','rumall','shopee' );
###############时间搜索#############################################################################################
	static $timetype=['soldtime'=>'下单时间','paidtime'=>'付款时间','printtime'=>'打单时间','shiptime'=>'发货时间'];
###############排序#############################################################################################
	static $customsort = ['soldtime'=>'下单时间','paidtime'=>'付款时间','printtime'=>'打单时间','fulfill_deadline'=>'发货期限','shiptime'=>'发货时间','order_id'=>'小老板单号','grand_total'=>'金额','country_sort'=>'国家','first_sku'=>'sku'];
##########################################小老板固定格式excel#############################################################
	//小老板固定格式excel
	static $exportOperationList = [
	'export_instock'=>'入库单/库存盘点单',
	'ExportPurchaseImportExcel'=>'采购清单',
	'ExportProductImportExcel'=>'SKU表格',
	'ExportTrackNoImportExcel'=>'跟踪号导入样式表格',
	'exportEubExcel'=>'E邮宝模板',
	];
	
	
	/**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_order_v2';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_status', 'order_manual_id', 'pay_status', 'shipping_status', 'is_manual_order', 'saas_platform_user_id', 'order_source_srn', 'customer_id', 'order_source_create_time', 'default_warehouse_id', 'paid_time', 'delivery_time', 'create_time', 'update_time', 'carrier_type', 'hassendinvoice','printtime'], 'integer'],
            [['subtotal', 'shipping_cost', 'discount_amount', 'grand_total', 'returned_total', 'price_adjustment', 'profit', 'logistics_cost', 'logistics_weight'], 'number'],
            [['order_source','order_source_status', 'order_source_order_id', 'order_source_site_id', 'selleruserid',  'order_source_shipping_method' ,'default_carrier_code', 'default_shipping_method_code'], 'string', 'max' => 50],
            [['currency'], 'string', 'max' => 3],
            [['payment_type'], 'string', 'max' => 50],
            [['consignee_country_code'], 'string', 'max' => 2],
            [['source_buyer_user_id','user_message', 'seller_commenttext' , 'consignee', 'consignee_postal_code', 'consignee_phone', 'consignee_email', 'consignee_company', 'consignee_country', 'consignee_city', 'consignee_province', 'consignee_district', 'consignee_county', 'consignee_address_line1', 'consignee_address_line2', 'consignee_address_line3'], 'string', 'max' => 255],
            [['seller_commenttype'], 'string', 'max' => 32],
            [['weird_status'],'string', 'max' => 10],
            [['consignee_mobile'],'string', 'max' => 20],
            [['addi_info'], 'string'],
            [['shipping_notified', 'pending_fetch_notified', 'delivery_failed_notified', 'rejected_notified', 'received_notified'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'order_status' => 'Order Status',
            'pay_status' => 'Pay Status',
            'order_source_status' => 'Order Source Status',
            'order_manual_id' => 'Order Manual ID',
            'is_manual_order' => 'Is Manual Order',
            'shipping_status' => 'Shipping Status',
            'exception_status' => 'Exception Status',
            'weird_status' => 'Weird Status',
            'order_source' => 'Order Source',
            'order_type' => 'Order Type',
            'order_source_order_id' => 'Order Source Order ID',
            'order_source_site_id' => 'Order Source Site ID',
            'selleruserid' => 'Selleruserid',
            'saas_platform_user_id' => 'Saas Platform User ID',
            'order_source_srn' => 'Order Source Srn',
            'customer_id' => 'Customer ID',
            'source_buyer_user_id' => 'Source Buyer User ID',
            'order_source_shipping_method' => 'Order Source Shipping Method',
            'order_source_create_time' => 'Order Source Create Time',
            'subtotal' => 'Subtotal',
            'shipping_cost' => 'Shipping Cost',
            'antcipated_shipping_cost' => 'Antcipated Shipping Cost',
            'actual_shipping_cost' => 'Actual Shipping Cost',
            'discount_amount' => 'Discount Amount',
            'commission_total' => 'Commission Total',
            'grand_total' => 'Grand Total',
            'returned_total' => 'Returned Total',
            'price_adjustment' => 'Price Adjustment',
            'currency' => 'Currency',
            'consignee' => 'Consignee',
            'consignee_postal_code' => 'Consignee Postal Code',
            'consignee_phone' => 'Consignee Phone',
            'consignee_mobile' => 'Consignee Mobile',
            'consignee_fax' => 'Consignee Fax',
            'consignee_email' => 'Consignee Email',
            'consignee_company' => 'Consignee Company',
            'consignee_country' => 'Consignee Country',
            'consignee_country_code' => 'Consignee Country Code',
            'consignee_city' => 'Consignee City',
            'consignee_province' => 'Consignee Province',
            'consignee_district' => 'Consignee District',
            'consignee_county' => 'Consignee County',
            'consignee_address_line1' => 'Consignee Address Line1',
            'consignee_address_line2' => 'Consignee Address Line2',
            'consignee_address_line3' => 'Consignee Address Line3',
            'default_warehouse_id' => 'Default Warehouse ID',
            'default_carrier_code' => 'Default Carrier Code',
            'default_shipping_method_code' => 'Default Shipping Method Code',
            'paid_time' => 'Paid Time',
            'delivery_time' => 'Delivery Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'user_message' => 'User Message',
            'carrier_type' => 'Carrier Type',
            'hassendinvoice' => 'Hassendinvoice',
            'seller_commenttype' => 'Seller Commenttype',
            'seller_commenttext' => 'Seller Commenttext',
            'status_dispute' => 'Status Dispute',
            'is_feedback' => 'Is Feedback',
            'rule_id' => 'Rule ID',
            'customer_number' => 'Customer Number',
            'carrier_step' => 'Carrier Step',
            'is_print_picking' => 'Is Print Picking',
            'print_picking_operator' => 'Print Picking Operator',
            'print_picking_time' => 'Print Picking Time',
            'is_print_distribution' => 'Is Print Distribution',
            'print_distribution_operator' => 'Print Distribution Operator',
            'print_distribution_time' => 'Print Distribution Time',
            'is_print_carrier' => 'Is Print Carrier',
            'print_carrier_operator' => 'Print Carrier Operator',
            'printtime' => 'Printtime',
            'delivery_status' => 'Delivery Status',
            'delivery_id' => 'Delivery ID',
            'desc' => 'Desc',
            'carrier_error' => 'Carrier Error',
            'is_comment_status' => 'Is Comment Status',
            'is_comment_ignore' => 'Is Comment Ignore',
            'issuestatus' => 'Issuestatus',
            'payment_type' => 'Payment Type',
            'logistic_status' => 'Logistic Status',
            'logistic_last_event_time' => 'Logistic Last Event Time',
            'fulfill_deadline' => 'Fulfill Deadline',
            'profit' => 'Profit',
            'logistics_cost' => 'Logistics Cost',
            'logistics_weight' => 'Logistics Weight',
            'addi_info' => 'Addi Info',
            'distribution_inventory_status' => 'Distribution Inventory Status',
            'reorder_type' => 'Reorder Type',
            'purchase_status' => 'Purchase Status',
            'pay_order_type' => 'Pay Order Type',
            'order_evaluation' => 'Order Evaluation',
            'tracker_status' => 'Tracker Status',
            'origin_shipment_detail' => 'Origin Shipment Detail',
            'shipping_notified' => 'Shipping Notified',
            'pending_fetch_notified' => 'Pending Fetch Notified',
			'delivery_failed_notified' => 'Delivery Failed Notified',
            'rejected_notified' => 'Rejected Notified',
            'received_notified' => 'Received Notified',
            'order_ship_time' => 'Order Ship Time',
        ];
    }
    
    
    /**
     +---------------------------------------------------------------------------------------------
     * 返回不同的版本的order status 主要用于 兼容不到版本的order status
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     * 			$version		string			获取指定版本的status
     +---------------------------------------------------------------------------------------------
     * @return	array $status
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/02/16				初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getOrderStatus($version=''){
    	switch ($version){
    		case 'oms21':
    			$OrderStatus = self::$status;
    			unset($OrderStatus['50']);
    			unset($OrderStatus['400']);
    			return $OrderStatus;
    		case 'oms21_bonanza':
    			$OrderStatus = self::$status;
    			unset($OrderStatus['400']);
    			return $OrderStatus;
    		default:
    			return self::$status;
    	}
    	return self::$status;
    }//end of getOrderStatus
    
    /**
     * @return array relational rules.
     */
    public function getItems(){
    	return $this->hasMany(OdOrderItem::className(),['order_id'=>'order_id']);
    }
    
    public function setItems($items){
    	$this->items = $items;
    }
    
    public function setItemsPT($items){
    	$this->orderItemsCache = $items;
    }
    
    public function getItemsPT(){
    	if ($this->orderItemsCache <> 'NA')
    		return $this->orderItemsCache;
    
    	return $this->items;
    }
    
    
    public function getGoods(){
    	return $this->hasMany(OdOrderGoods::className(),['order_id'=>'order_id']);
    }
    
    public function getTrackinfos(){
    	return $this->hasMany(OdOrderShipped::className(),['order_id'=>'order_id']);
    }
    
    public function getTrackinfosPT(){
    	if ($this->orderShippedCache <> 'NA')
    		return $this->orderShippedCache;
    		
    	return $this->trackinfos;
    }
    
    public function setTrackinfosPT($items){
    	$this->orderShippedCache = $items;
    	 
    }
    
    //标记队列信息
    public function getQueueships(){
    	return $this->hasMany(QueueSyncshipped::className(),['order_source_order_id'=>'order_source_order_id']);
    }
    
    /**
     * 检测订单的异常状态
     * $name:操作的人名
     * $reset:是否强制检测,目前手动检测的情况是强制进行检测的
     */
    public function checkorderstatus($name=NULL,$reset=0){
    	if (is_null($name)){
    		$name=\Yii::$app->user->identity->getFullName();
    	}
    	//只针对已付款的订单进行检测
    	if($this->order_status != '200'){
    		return false;
    	}
    	$IsCanShip = '';
    	//待合并检测
    	//$isSkipMerge = OrderApiHelper::isSkipMergeOrder($this->order_id);
    	//20160401 由于部分客户不填写地址1的情况 下 不能检查到待合并的订单 ， 所以改为3个地址一起检测
    	if($this->order_relation!='fs' && $this->order_relation!='ss'){
	    	if(strlen($this->consignee) && strlen($this->consignee_address_line1.$this->consignee_address_line2.$this->consignee_address_line3) && (in_array($this->order_source,['lazada' ,'linio' , 'jumia'])==false)){
	    		//检测到有待合并订单时（非不合并订单）
	    		$orders = OrderHelper::listWaitMergeOrder($this);
	    		//$orders = self::find()->where('selleruserid = :s and source_buyer_user_id = :sbui and consignee = :c and consignee_address_line1 = :cal and order_status = :os',[':s'=>$this->selleruserid,':sbui'=>$this->source_buyer_user_id,':c'=>$this->consignee,':cal'=>$this->consignee_address_line1,':os'=>self::STATUS_PAY])->all();
	    		//$isSkipMerge = OrderApiHelper::isSkipMergeOrder($this->order_id);
	    		if (count($orders)>0){
	    			$this->pay_order_type = self::PAY_CAN_SHIP;
	    			$this->exception_status = self::EXCEP_WAITMERGE;
	    			OperationLogHelper::log('order',$this->order_id,'检测订单','添加异常标签:['.self::$exceptionstatus[self::EXCEP_WAITMERGE].']',$name);
	    			//假如对应的订单在可发货状态需要移动到异常中， 写操作log
	    			$tmpRt = OrderHelper::setRelateOrderWaitSend($this->order_id, $orders ,'order' , 'OMS检测订单',$name);
	    			$IsCanShip = $this->exception_status;
	    			//return true;
	    		}
	    	}
    	}
    	
    	if(($this->order_relation=='fs' || $this->order_relation=='ss') && $this->exception_status == self::EXCEP_WAITMERGE){
    		$this->exception_status = 0;
    	}
    	
    	//sku是否存在
    	/* 20170331kh start 屏蔽无关代码
    	$skus=OrderHelper::getorderskuswithbundle($this->order_id);
    	20170331kh end 屏蔽无关代码*/
    	
    	if (ConfigHelper::getConfig('order/sku_toproduct')) {
    		//开启自动生成商品模式
    		OrderHelper::_autoCompleteProductInfo($this->order_id,'order','检测订单',$name);
    	}
    	
    	
    	/* 20170331kh start 屏蔽无关代码
    	// 		if (ConfigHelper::getConfig('order/check_sku')==1)
    	{
    		if (count($skus)>0){
    			foreach ($skus as $sku=>$v){
    				//cd 的特殊sku不检查是否存在
    				if(in_array($sku,CdiscountOrderInterface::getNonDeliverySku()))
    					continue;
    				
    				if (!ProductApiHelper::hasProduct($sku)){
    					//$this->pay_order_type = self::PAY_EXCEPTION;
    					
    					if ($this->exception_status != self::EXCEP_WAITMERGE || empty($IsCanShip)){
    						$this->exception_status = self::EXCEP_SKUNOTMATCH;
    						$this->pay_order_type = self::PAY_CAN_SHIP;
    					}
    					$IsCanShip = $this->exception_status;
    					//OperationLogHelper::log('order',$this->order_id,'检测订单','添加异常标签:['.self::$exceptionstatus[self::EXCEP_SKUNOTMATCH].']',$name);
    					//return true;
    				}
    			}
    		}
    	}
    	20170331kh end 屏蔽无关代码*/
    	
    	// 如果没有仓库，先匹配到默认仓库,给OdOrder赋值仓库ID，并业务记录操作
    	/*  匹配运输服务 已经匹配 仓库 这里不再重复匹配 start 
    	if ($this->default_warehouse_id ==-1 || $reset==1){
    		//有两个仓库可以选择时候必须手工选择仓库
    		//$thisWHId = InventoryApiHelper::OrderCheckWarehouseGetOneid();旧的
    		$thisWHId = InventoryHelper::matchOrdersWarehouse($this);
    		
    		if ($thisWHId==-1){
    			//$this->pay_order_type = self::PAY_EXCEPTION;
				//$this->exception_status = self::EXCEP_NODEFAULTWAREHOUSE;
				//OperationLogHelper::log('order',$this->order_id,'检测订单','添加异常标签:['.self::$exceptionstatus[self::EXCEP_NODEFAULTWAREHOUSE].']',$name);
				//return true;
			}
    			
    		$this->default_warehouse_id=$thisWHId;
    	}
    	匹配运输服务 已经匹配 仓库 这里不再重复匹配 end  */
		//检测是否有留言,
		//2015-4-27  注释，留言改为提示标签，而非异常处理
// 		if (strlen($this->user_message)>0){
// 			$this->exception_status = self::EXCEP_HASMESSAGE;
// 			OperationLogHelper::log('order',$this->order_id,'检测订单','添加异常标签:['.self::$exceptionstatus[self::EXCEP_HASMESSAGE].']',\Yii::$app->user->identity->getFullName());
// 			return true;
// 		}

    	
    	/* 20170331kh start 屏蔽无关代码
    	 
    	//商品库存是否足够
    	$support_zero_inventory_shipments = ConfigHelper::getConfig('support_zero_inventory_shipments')==null?'Y':ConfigHelper::getConfig('support_zero_inventory_shipments');
    	if ($support_zero_inventory_shipments=='N')
    	{
    		$sku_key=array_keys($skus);
    		$stocks = InventoryApiHelper::getPickingInfo($sku_key,$this->default_warehouse_id);
    		//所有商品都不存在
    		$NotexistProduct = [];
    		foreach ($stocks as $stock){
    			if (($stock['qty_in_stock']-$stock['qty_order_reserved'])<$skus[$stock['sku']]){
    				//$this->pay_order_type = self::PAY_EXCEPTION;
    				//$this->exception_status = self::EXCEP_NOSTOCK;
    				//OperationLogHelper::log('order',$this->order_id,'检测订单','添加异常标签:['.self::$exceptionstatus[self::EXCEP_NOSTOCK].']',$name);
    				//return true;
    			}
    		}
    		//没有库存的商品也打上库存不足的标签 , 这个流程oms2.1才检测
    		if (count($sku_key) != count($stocks)){
    			//$this->pay_order_type = self::PAY_EXCEPTION;
    			//$this->exception_status = self::EXCEP_NOSTOCK;
    			//OperationLogHelper::log('order',$this->order_id,'检测订单','添加异常标签:['.self::$exceptionstatus[self::EXCEP_NOSTOCK].']',$name);
    			//return true;
    		}
    		
    	}
		20170331kh end 屏蔽无关代码*/
		//匹配运输服务
// 		if (ConfigHelper::getConfig('order/check_wuliu')==1||is_null(ConfigHelper::getConfig('order/check_wuliu')))
		{
			try {
				if (CarrierApiHelper::matchShippingService($this,$reset, $name)){
					$newAttr = array(
							'default_carrier_code' => $this->default_carrier_code,
							'default_shipping_method_code' => $this->default_shipping_method_code,
							'default_warehouse_id' => $this->default_warehouse_id,
							'rule_id' => $this->rule_id
					);
				
					$updateOrderResult = \eagle\modules\order\helpers\OrderUpdateHelper::updateOrder($this->order_id, $newAttr, false , $name, '检测订单', 'order');
				
					$serviceName = SysShippingService::findOne($this->default_shipping_method_code)->service_name;
					OperationLogHelper::log('order',$this->order_id,'检测订单','匹配运输服务:['.$this->default_shipping_method_code.'-'.$serviceName.']',$name);
				}else{
					if ($this->default_shipping_method_code==''){
						//$this->pay_order_type = self::PAY_EXCEPTION;
						//$this->exception_status = self::EXCEP_HASNOSHIPMETHOD;
						//OperationLogHelper::log('order',$this->order_id,'检测订单','添加异常标签:['.self::$exceptionstatus[self::EXCEP_HASNOSHIPMETHOD].']',$name);
						//return true;
					}
				}
			} catch (\Exception $e) {
				echo "Error Message:".$e->getMessage()." Error no:".$e->getLine();
			}
			
		}
		//paypal账号检测
		//待刊登模块开发完成后，再跟item刊登时的paypal账号做比较
		
		//通过 检测可以发货的订单
		if (empty($IsCanShip)){
			$this->exception_status = self::EXCEP_WAITSEND;
			$this->pay_order_type = self::PAY_CAN_SHIP;
		}
		
		OperationLogHelper::log('order',$this->order_id,'检测订单','修改异常标签:['.self::$exceptionstatus[self::EXCEP_WAITSEND].']',$name);
// 		if($this->order_status == self::STATUS_PAY){
// 			$this->order_status = self::STATUS_WAITSEND;
// 		}
//		OperationLogHelper::log('order',$this->order_id,'检测订单','检测通过,订单移动到:['.self::$status[self::STATUS_WAITSEND].']',$name);
		//20170908kh 客户反应因为 从发货中把订单打回到已付款，再重新检测订单 。redis待合并数没有更新 ，暂时在检测订单清redis
		\eagle\modules\tracking\helpers\TrackingHelper::delTrackerTempDataToRedis('left_menu_statistics');
		return true;
    }
    
    /**
     * 保存之后触发事件
     */
//     public function beforeSave($insert){
//     	if (!$insert){
// 	    	if(parent::beforeSave($insert)){
// 		    	$this->checkorderstatus();
// 		    	return true;
// 	    	}else{
// 	    		return false;
// 	    	}
//     	}
//     }
}
