<?php
namespace eagle\modules\amazon\apihelpers;

use \Yii;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\AmzOrderDetail;
use eagle\models\AmazonOrderSubmitQueue;
use eagle\modules\amazon\models\AmazonApiCallLog;

use eagle\modules\order\models\OdOrderItem;
use eagle\models\OdOrderShipped;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\models\SaasAmazonUser;
use eagle\models\SaasAmazonUserPt;
//use eagle\models\SaasAmazonAutosync;
use eagle\models\SaasAmazonAutosyncV2;
use eagle\models\QueueSyncshipped;
//use eagle\models\AmazonTempOrderidQueue;
use eagle\models\AmzOrder;
use yii\db\Query;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\models\AmazonTempOrderidQueueHighpriority;
use eagle\models\AmazonTempOrderidQueueLowpriority;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\util\helpers\ConfigHelper;
use app\models\AmazonFbaInventoryAccounts;
use eagle\modules\inventory\helpers\FbaWarehouseHelper;
use eagle\models\AmazonReportRequset;
use eagle\modules\listing\models\AmazonTempListing;
use eagle\modules\listing\models\AmazonTempCategoryInfo;
use eagle\models\AmazonListing;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\listing\models\AmazonListingFetchAddiInfoQueue;


class AmazonApiHelper{
	private static $queueVersion = '';
	
	public static $AMAZON_MARKETPLACE_REGION_CONFIG = array(
			'A2EUQ1WTGCTBG2'=>"CA",
			'ATVPDKIKX0DER'=>"US",
			'A1PA6795UKMFR9'=>"DE",
			'A1RKKUPIHCS9HS'=>"ES",
			'A13V1IB3VIYZZH'=>"FR",
			'A21TJRUUN4KGV'=>"IN",
			'APJ6JRA9NG5V4'=>"IT",
			'A1F83G8C2ARO7P'=>"UK",
			'A1VC38T7YXB528'=>"JP",
			'AAHKV2X7AFYLW'=>"CN",
			'A1AM78C64UM0Y8'=>"MX",
			'A39IBJ37TRP1C6'=>"AU",
			'A2Q3Y263D00KWC'=>"BR",
			'A2VIGQ35RCS4UG'=>"AE",
			'A33AVAJ2PDY3EV'=>"TR",
	);
	
	public static $AMAZON_MARKETPLACE_DOMAIN_CONFIG = array(
			"CA"=>'www.amazon.ca',
			"US"=>'www.amazon.com',
			"DE"=>'www.amazon.de',
			"ES"=>'www.amazon.es',
			"FR"=>'www.amazon.fr',
			"IN"=>'www.amazon.in',
			"IT"=>'www.amazon.it',
			"UK"=>'www.amazon.co.uk',
			"GB"=>'www.amazon.co.uk',
			"JP"=>'www.amazon.co.jp',
			"CN"=>'www.amazon.cn',
			"MX"=>'www.amazon.com.mx',
			"AU"=>'www.amazon.com.au'
	);
	
	public static $AWS_DOMAIN_CONFIG = array(
	        "CA"=>'ca',
	        "US"=>'com',
	        "DE"=>'de',
	        "ES"=>'es',
	        "FR"=>'fr',
	        "IN"=>'in',
	        "IT"=>'it',
	        "UK"=>'co.uk',
	        "GB"=>'co.uk',
	        "JP"=>'co.jp',
	        "CN"=>'cn',
	        "MX"=>'com.mx',
	        "AU"=>'com.au',
	);
	
	// aws账号 self::getBrowseNode 用到
	// TODO aws account 
	public static $aws_public_key = "";
	public static $aws_private_key = "";
	
	protected static $active_users = [];
	
	public static $COUNTRYCODE_NAME_MAP=array("US"=>"美国","CA"=>"加拿大","DE"=>"德国","ES"=>"西班牙","FR"=>"法国","IN"=>"印度","IT"=>"意大利",
	"UK"=>"英国","JP"=>"日本","CN"=>"中国","MX"=>"墨西哥","AU"=>"澳大利亚","BR"=>"巴西","TR"=>"土耳其","AE"=>"阿联酋");
	
	// 20170124 标记发货时候，允许标记发货的订单item状态
    public static $CAN_SHIP_ORDERITEM_STATUS = array("Unshipped","PartiallyShipped","Shipped","InvoiceUnconfirmed");
    
    // 20170124 同步到这些状态时，订单item会被标记为不可处理发货
    public static $CANNOT_SHIP_ORDERITEM_STATUS = array("Canceled","Pending","Unfulfillable",);
	/**
	 * 返回amazon可选的物流方式
	 * @return array(array(shipping_code,shipping_value)) 或者 null.   其中null表示这个渠道是没有可选shipping code和name， 是free text
	 * 
	 * shipping_code就是 通知平台的那个运输方式对应值
	 * shipping_value就是给卖家看到的可选择的运输方式名称
	 */
	public static function getShippingCodeNameMap(){
		return array(
				"USPS"=>"USPS",
				"UPS"=>"UPS",
				"UPSMI"=>"UPSMI",
				"FedEx"=>"FedEx",
				"DHL"=>"DHL",
				"Fastway"=>"Fastway",
				"GLS"=>"GLS",
				"GO!"=>"GO!",
				"Hermes Logistik Gruppe"=>"Hermes Logistik Gruppe",
				"Royal Mail"=>"Royal Mail",
				"Parcelforce"=>"Parcelforce",
				"City Link"=>"City Link",
				"TNT"=>"TNT",
				"Target"=>"Target",
				"SagawaExpress"=>"SagawaExpress",
				"NipponExpress"=>"NipponExpress",
				"YamatoTransport"=>"YamatoTransport",
				"DHL Global Mail"=>"DHL Global Mail",
				"UPS Mail Innovations"=>"UPS Mail Innovations",
				"FedEx SmartPost"=>"FedEx SmartPost",
				"OSM"=>"OSM",
				"OnTrac"=>"OnTrac",
				"Streamlite"=>"Streamlite",
				"Newgistics"=>"Newgistics",
				"Canada Post"=>"Canada Post",
				"Blue Package"=>"Blue Package",
				"Chronopost"=>"Chronopost",
				"Deutsche Post"=>"Deutsche Post",
				"DPD"=>"DPD",
				"La Poste"=>"La Poste",
				"Parcelnet"=>"Parcelnet",
				"Poste Italiane"=>"Poste Italiane",
				"SDA"=>"SDA",
				"Smartmail"=>"Smartmail",
				"FEDEX_JP"=>"FEDEX_JP",
				"JP_EXPRESS"=>"JP_EXPRESS",
				"NITTSU"=>"NITTSU",
				"SAGAWA"=>"SAGAWA",
				"YAMATO"=>"YAMATO",
				"BlueDart"=>"BlueDart",
				"AFL/Fedex"=>"AFL/Fedex",
				"Aramex"=>"Aramex",
				"India Post"=>"India Post",
				"Professional"=>"Professional",
				"DTDC"=>"DTDC",
				"Overnite Express"=>"Overnite Express",
				"First Flight"=>"First Flight",
				"Delhivery"=>"Delhivery",
				"Lasership"=>"Lasership",
				"Yodel"=>"Yodel",
				"China Post"=>"China Post",
				"Singapore Post"=>"Singapore Post",
				"Other"=>"Other",
				// 下面那些是骗人的！！！！！！目前是用amazon的free text字段 所有没有关系
				"4PX"=>"4PX",
				"Australia Post"=>"Australia Post",
				"Austrian Post"=>"Austrian Post",
				"4PX"=>"4PX",
				"Australian Air Express"=>"Australian Air Express",
				"A1 Courier Services"=>"A1 Courier Services",				
				"An Post"=>"An Post",
				"Sweden Post"=>"Sweden Post",
				"Swiss Post"=>"Swiss Post",
				"THAILAND POST"=>"THAILAND POST",
				"Russian Post"=>"Russian Post",
				"Poste Italiane"=>"Poste Italiane",
				"posta.hu"=>"posta.hu",
				"Post NL"=>"Post NL",
				"POST ITALIANO"=>"POST ITALIANO",
				"Philpost"=>"Philpost",
				"MALAYSIA POST"=>"MALAYSIA POST",
				"Japan Post"=>"Japan Post",
				"CHUKOU1"=>"CHUKOU1",
				"CHUKOU1_EXPRESS"=>"CHUKOU1_EXPRESS"
				
		);
		
		
	}
	
	/**
	 * 返回amazon默认的物流方式shipping_code
	 * shipping_code就是 通知平台的那个运输方式对应值
	 *
	 */
	public static function getDefaultShippingCode(){
		return "Other";	
	}
		
	
	
	
	
	
 
	/******************     change order status start      **********************/
	 /**
	  * [ShipAmazonOrder 修改 Amazon Order 信息，提交发货信息，
	  * 本function只是插入到request 队列中，需要等待后台程序执行]
	  * @Author   yzq
	  * @DateTime 2014/03/20    初始化
	  * @Author   willage
	  * @DateTime 2016-09-19T09:29:10+0800
	  *
	  * 修改：@DateTime 2016-09-19T09:29:10+0800
	  * 1、增加插入字段api_type(标识第一次虚拟发货和再次虚拟发货)
	  *
	  * @param    [type]      $amazonOrderid    [amazon平台的订单id]
	  * @param    [type]      $merchantid       [amazon merchant id]
	  * @param    [type]      $marketplace      [amazon marketplace国家代码]
	  * @param    [type]      $api_type         [标识第一次虚拟发货和再次虚拟发货]
	  * @param    [type]      $items            [
	  *                             array of Items of sku and qty in this package:
	  * 							e.g.: array( array('sku'=>12313423556, 'qty'=>2) ,
	  * 										 array('sku'=>78913422432, 'qty'=>1))]
	  * @param    [type]      $freight          [物流递送公司]
	  * @param    [type]      $ship_date        [递送日期，“2013-9-9” 的格式即可，系统会自动变成 UTC 格式]
	  * @param    [type]      $order_shipped_id [标记发货结果的数据表的id]
	  * @param    string      $delivery_no      [递送物流号，如果填写了非空的值，
	  *                                          amazon 会自动把订单变成 Shipped 状态]
	  * @return				array ( 'success' => true 'message' => '' )
	  */
	public static function ShipAmazonOrder($amazonOrderid , $merchantid , $marketplace ,$api_type, $items,  $freight, $ship_date, $order_shipped_id,$delivery_no =''){
		//initilize indicators
		$success = true;
		$message = "";
		
		\Yii::info("ShipAmazonOrder 1","file");
		
// 		$amazonOrderid=$orderObject->order_source_order_id;
	
		//step 2: format the items info from the order
		$rtn_amzon_items = self::_getAmazonItemFormat($amazonOrderid, $items);
		\Yii::info("ShipAmazonOrder 2  rtn_amzon_items:".print_r($rtn_amzon_items,true),"file");
		\Yii::info("after _getAmazonItemFormat rtn_amzon_items:".print_r($rtn_amzon_items,true),"file");	
		if ($rtn_amzon_items['success']===false){
			\Yii::error("after _getAmazonItemFormat message:".$rtn_amzon_items['message'],"file");
			return array(
					'success' => true,
					'message' => $rtn_amzon_items['message']
			);
		}
		
		\Yii::info("ShipAmazonOrder 3  ","file");
		
		//step 3: insert the data into request as parameters
		$parms = array(
				'order_id' => $amazonOrderid,
				'items' => $rtn_amzon_items['items'],
				'freight' => $freight, 
				'delivery_no' => $delivery_no,
				'ship_date' => $ship_date    //+12 hours to make the date is the same all over the world
		);
		
// 		$ret=self::_insertAmazonSubmitQueue("ShipAmazonOrder",$amazonOrderid,$orderObject->selleruserid,$orderObject->order_source_site_id,$parms,array("order_shipped_id"=>$order_shipped_id));
		$ret=self::_insertAmazonSubmitQueue("ShipAmazonOrder",$amazonOrderid,$merchantid,$marketplace,$api_type,$parms,array("order_shipped_id"=>$order_shipped_id));
		if ($ret===false){
			return array(	'success' => false,		'message' => "_insertAmazonSubmitQueue save false"	);
		}
		
		//write the invoking result into journal before return
	// 	SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
	//	\Yii::info(["Amazon",__CLASS__, __FUNCTION__ , array($orderid , $items,  $freight, $delivery_no , $ship_date) , "Submitted Queue"] , 'edb\user');
		
		return array(	'success' => true,	'message' => ""	);
	
	}//end of function ChangeAmazonOrder

	/**
	 * [AmazonShipped 需要标志发货的订单从oms的队列（queue_syncshipped）
	 * 转移到amazon标志发货队列（amazon_order_submit_queue）]
	 *
	 * QueueContrller.php actionCronAutoShipped触发!!
	 *
	 * 流程：QueueContrller.php actionCronAutoShipped触发，
	 * 从oms的标志平台发货队列中提取一个记录，
	 * 插入到amazon的通知平台发货队列,从oms发货队列中删除记录，
	 * @Author   willage
	 * @DateTime
	 * @param    [type]      $queueSyncShippedObj [queue_syncshipped表的记录的activeobject]
	 *
	 * 修改：@DateTime 2016-09-19T09:22:29+0800
	 * 1、插入队列增加字段api_type(区分第一次虚拟发货和再次虚拟发货)
	 */
	public static function AmazonShipped($queueSyncShippedObj){
		\Yii::info("AmazonShipped 1","file");

		$amazonOrderid=$queueSyncShippedObj->order_source_order_id;
		$orderObject=OdOrder::find()
		->where('order_source_order_id=:order_source_order_id',[":order_source_order_id"=>$amazonOrderid])
		->andwhere(["order_source"=>"amazon"])
		->andwhere(["order_capture"=>"N"])
		->one();
		
		//1. 提取订单的相关信息
		$orderItemsArr = array();
		if(!empty($queueSyncShippedObj->params) && is_array($queueSyncShippedObj->params)){
			foreach ($queueSyncShippedObj->params as $amzOdItem){
				$odItem = OdOrderItem::find()->where(["order_source_order_id"=>$amzOdItem["order_source_order_id"] , "order_source_order_item_id"=>$amzOdItem["order_source_order_item_id"] ])->asArray()->one();
				if(empty($odItem)){// 该order_source_order_id 没有找到 相关的order item
					\Yii::error('AmazonShipped can not find the item records in $queueSyncShippedObj->params order_source_order_item_id:'.$amzOdItem["order_source_order_item_id"], 'file');
					$queueSyncShippedObj->status = 2;
					if(!$queueSyncShippedObj->save(false)){
						\Yii::error('AmazonShipped $queueSyncShippedObj->save error:'.print_r($queueSyncShippedObj->errors,true), 'file');
					}
					//return false;
					return array(false,-1);
				}else{
					if(empty($orderObject)){// 合并订单后，amazon订单对应的订单object需要通过order item找回来 
						$orderObject = OdOrder::findOne($odItem['order_id']);
					}
					$orderItemsArr[] = $odItem;
				}
					
			}
		}
		
		if ($orderObject===null){// 遍历改order_source_order_id的order item都没有找到 order item对应的 order 记录
			//	\Yii::error(["Order", __CLASS__,__FUNCTION__,"","can not find the order_source_order_id:".$amazonOrderid ] , 'edb\user');
			\Yii::error("AmazonShipped can not find the order_source_order_id:".$amazonOrderid  , 'file');
			$queueSyncShippedObj->status = 2;
			if(!$queueSyncShippedObj->save(false)){
				\Yii::error('AmazonShipped $queueSyncShippedObj->save error:'.print_r($queueSyncShippedObj->errors,true), 'file');
			}
			//return false;
			return array(false,-1);
		}
		
		if (count($orderItemsArr)==0){
			\Yii::error("AmazonShipped can not find the items for eagle order_id:".$orderObject->order_id, 'file');
			$queueSyncShippedObj->status = 2;
			if(!$queueSyncShippedObj->save(false)){
				\Yii::error('AmazonShipped $queueSyncShippedObj->save error:'.print_r($queueSyncShippedObj->errors,true), 'file');
			}
			//return false;
			return array(false,-1);
		}

		//amazon对发货时间是有要求的：  The ship-date or FulfillmentDate you provided must be between the order date  and now
		//对时间格式也是有要求的  Y-m-d\TH:i:s\Z
		// $localTime = date('Y-m-d H:i:s',time()-90);
		// $dt = new \DateTime($localTime);
		// $dt->setTimezone ( new \DateTimeZone('UTC'));
		// $shipDate=$dt->format("Y-m-d\TH:i:s\Z");		        //xsd:datetime格式
		//$shipDate=$dt->format(\DateTime::ISO8601); //lolotest 默认失败情况

		$retDeliveryTime = self::AmazonSetOrderShippingDateTime($orderObject);
		$shipDate = self::AmazonDateTimeFortmat($retDeliveryTime);
		\Yii::info("[".__FUNCTION__."]:retDeliveryTime = ".$retDeliveryTime,"file");
		\Yii::info("[".__FUNCTION__."]:shipDate = ".$shipDate,"file");
		\Yii::info("AmazonShipped 2","file");
		//exit;
		//2. 插入到amazon通知平台发货队列
		$ret=self::ShipAmazonOrder($amazonOrderid , $orderObject->selleruserid , $orderObject->order_source_site_id ,$queueSyncShippedObj->api_type ,$orderItemsArr,$queueSyncShippedObj->shipping_method_code,$shipDate,$queueSyncShippedObj->osid,$queueSyncShippedObj->tracking_number);
		if ($ret['success']===false){
			//return false;
			return array(false,-1);
		}

		//3. 更新OdOrderShipped 状态
		$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
		if (!is_null($osObj)){
			$osObj->status = 3;  //订单标记发货状态，0:未处理，1:成功，2:失败, 3:正在通知平台发货（amazon特有）
			$osObj->result = 'true';
			$osObj->errors = '';
			$osObj->updated = time();
			$osObj->lasttime = time();
			$osObj->save(false);
		}
	
		// 4.TODO 从oms发货队列中删除记录， queue_syncshipped
// 		$queueSyncShippedObj->delete();// dzt20150714 修改位为不删除发货队列 ， 留下追查痕迹
		$queueSyncShippedObj->status = 1;
		if(!$queueSyncShippedObj->save(false)){
			\Yii::error('AmazonShipped $queueSyncShippedObj->save error:'.print_r($queueSyncShippedObj->errors,true), 'file');
			//return false;
			return array(false,-1);
		}
		
	
		\Yii::info("AmazonShipped 3","file");
		//return true;
		return array(true,$retDeliveryTime);
	}
	
	
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Refund amazon Order 中的某一款或者多款产品，每款产品只能refund全部数量，不能 refund 其中的部分数量
	 * 本function只是插入到request 队列中，需要等待后台程序执行
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param order id 			Amazon Order id
	 * @param items_sku_array 	array of 要退货的产品sku， array('SK1','SK2')
	 +---------------------------------------------------------------------------------------------
	 * @return				array ( 'success' => 0 'submit_id' => message )
	 * @description			Refund amazon Order 中的某一款或者多款产品，每款产品只能refund全部数量，不能 refund 其中的部分数量
	 * 						调用本方法以后，amazon 服务器端会自动完成退款，客户无需再手动操作Amazon或者paypal退款
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/03/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function RefundAmazonOrderItem($order_id,$items_sku_array){	
		//make invoking journal
		$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array($order_id,$items_sku_array));	
		
		$order_model = OdOrder::model()->findByPk($order_id);
		//if not found such order, return -1	
		if ($order_model == null) 
			return array(
					'success' => false,
					'message' => 'AMZHP001: Not found such internal order id: '.$order_id
			);
		
		//Load the order info by amazon order id
		$amz_order_model = AmzOrder::model()->findByPk($order_model->order_source_order_id);
		if ($amz_order_model == null) 
			return array(
					'success' => false,
					'message' => "AMZHP002: Not found the amazon order info ('".$order_model->order_source_order_id."') of internal order id:$order_id"
			);
		
		//get the array format data info
		$refund_order_info = $amz_order_model->attributes;
		$refund_order_info['Items'] = array();
		
		foreach($items_sku_array as $sku){
			$anItem_model = AmzOrderDetail::model()->find(
				'AmazonOrderId=:AmazonOrderId and SellerSKU =:SellerSKU',
				array(':AmazonOrderId'=>$order_model->order_source_order_id ,
					':SellerSKU'=>$sku	));
			if ($anItem_model == null)
					return array(
					'success' => false,
					'message' => "AMZHP003: Not found items ($sku) for amazon order info ('".$order_model->order_source_order_id."') of internal order id:$order_id"
					);
			$refund_order_info['Items'][] = $anItem_model->attributes;
		}//end of each sku
		
		self::_insertAmazonSubmitQueue('RefundAmazonOrderItem',$order_model->order_source_order_id,$order_model->selleruserid,$order_model->order_source_site_id,$refund_order_info);
		
		//write the invoking result into journal before return
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");
			
		return array(
					'success' => true,
					'message' => ""
			);
	
	}//end of function refundAmazonOrderProduct
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Refund entire amazon Order
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param order id 			eagle平台的订单id
	 * 
	 +---------------------------------------------------------------------------------------------
	 * @return				array ( 'exception' => 0 'submit_id' => 8112829778 )
	 * @description			Refund entire amazon Order
	 * 						调用本方法以后，amazon 服务器端会自动完成退款，客户无需再手动操作Amazon或者paypal退款
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/03/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function CancelEntireAmazonOrder($order_id){	
		//make invoking journal
		$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array($order_id ));
	
		$amz_order_source_id = "";
		$amz_order_model = null;
		
		$order_model = OdOrder::model()->findByPk($order_id);
		//if ($order_model == null)
		//	$order_model = OdOrder::model()->find("order_source_order_id = '$order_id'");
		
		//if not found such order, return -1
		if ($order_model == null){
		
			//write the invoking result into journal before return
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, 'AMZHP021: Not found such internal order id: '.$order_id);
			
			return array(
					'success' => false,
					'message' => 'AMZHP021: Not found such internal order id: '.$order_id
			);
		 
		}//end of not found such order in eagle order
		else
			$amz_order_source_id = $order_model->order_source_order_id;
		
		//Load the order info by amazon order id
		if ($amz_order_model == null)
			$amz_order_model = AmzOrder::model()->findByPk($amz_order_source_id);
		
		if ($amz_order_model == null) {
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, "AMZHP022: Not found the amazon order info ('".$amz_order_source_id."') of internal order id:$order_id");
			return array(
					'success' => false,
					'message' => "AMZHP022: Not found the amazon order info ('".$amz_order_source_id."') of internal order id:$order_id"
			);
		}
		//get the array format data info
		$refund_order_info = $amz_order_model->attributes;
		$refund_order_info['Items'] = array();
	 
		$anItem_models = AmzOrderDetail::model()->findAll(
					'AmazonOrderId=:AmazonOrderId',
					array(':AmazonOrderId'=>$amz_order_source_id)
					);
		if ($anItem_models == null or count($anItem_models) < 1){
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, "AMZHP023: Not found the amazon order item for  ('".$amz_order_source_id."') of internal order id:$order_id");
			return array(
				'success' => false,
				'message' => "AMZHP023: Not found the amazon order item for  ('".$amz_order_source_id."') of internal order id:$order_id"
			);
		}
		
		foreach ($anItem_models as $anItem_model)
			$refund_order_info['Items'][] = $anItem_model->attributes;
	 
		self::_insertAmazonSubmitQueue('CancelEntireAmazonOrder',$amz_order_source_id,$order_model->selleruserid,$order_model->order_source_site_id,$refund_order_info);
		
		//write the invoking result into journal before return
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");
			
		return array(
				'success' => true,
				'message' => ""
		);
	}//end of function cancelEntireAmazonOrder
	
	/******************     change order status end      **********************/
	
	/******************     post inventory start      **********************/
	/**
	 +---------------------------------------------------------------------------------------------
	 * Post Amazon Product Inventory Info
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $prods 		array(  array ('sku'=>'SKES1','qty'=>100),array ('sku'=>'SKES2','qty'=>50),...
	 * 							 )
	 +---------------------------------------------------------------------------------------------
	 * @return				array ( 'exception' => 0, 'success' => true, 'submit_id'=>'A1232' )
	 * @description			Post prods qty to amazon shop. 
	 * 						出入参数支持该网点的所有 产品同时传入，那么一次提交即可。
	 * 						请注意，这是Amazon异步提交 submit feed，需要等候amazon 服务器执行，一般需要等待5 - 30 分钟才会生效。
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/03/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function postAmazonProductInventory($prods ){
		//make invoking journal
		$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array($prods ));
			
		$config=AmazonConst::$config;
		AmazonAPI::set_amazon_config($config);
		$result = AmazonAPI::post_prods_inventory($prods);
		//result containing array('exception'=>, 'submit_id'=>'A1232', 'success'=>true)
		//write the invoking result into journal before return
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
		
		return $result;
	}
	
	//获取amazon report id  
	// public static function getAmazonReportId($request_id , $merchantid , $marketplace_short){
			
	// 	// set up amazon token
	// 	$merchantIdAccountMap=SaasAmazonAutoSyncHelper::_getMerchantIdAccountInfoMap();
	// 	//echo print_r($merchantIdAccountMap,true);
	// 	$amazonAccessInfo=$merchantIdAccountMap[$merchantid];
	// 	$marketplace_short_config = array_flip(SaasAmazonAutoSyncHelper::$AMAZON_MARKETPLACE_REGION_CONFIG);
	// 	$config=array(
	// 			'merchant_id' =>$merchantid,
	// 			'marketplace_id' => $marketplace_short_config[$marketplace_short],
	// 			'access_key_id' => $amazonAccessInfo["access_key_id"],
	// 			'secret_access_key' => $amazonAccessInfo["secret_access_key"],
	//          'mws_auth_token' => $amazonAccessInfo["mws_auth_token"]
	// 	);
		
	// 	//make invoking journal
	// 	$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array($config ));
		
		
	// 	$timeout=120; //s		
	// 	$parms = array('parms' => json_encode(array('request_id' =>$request_id)), 
	// 				   'config' => json_encode($config), 
	// 				  );
		
	// 	//echo "<br> before call_amazon_api  config:".print_r($config,true)."  \n";
	// 	$retInfo = AmazonProxyConnectHelper::call_amazon_api('GetAmazonReportIds', $parms,$timeout);
	// 	//echo "<br>after call_amazon_api retinfo:".print_r($retInfo,true);
		
	// 	//write the invoking result into journal before return
	// 	SysLogHelper::InvokeJrn_UpdateResult($journal_id, $retInfo);
	// 	return $retInfo;
	// 	//echo json_encode($retInfo);
	// }//end of getAmazonReportId
	
	public static function retrieve_product_open_list_report($merchant_id,$marketplace_short){
		$action = "retrieve_prod_list";
		$report_id = self::_retrieve_common_latest_report_id('_GET_FLAT_FILE_OPEN_LISTINGS_DATA_',$merchant_id,$marketplace_short,$status='D' );
		  
	}
	
	public static function retrieve_prod_list($merchant_id,$marketplace_short){
			$action = "retrieve_prod_list";
			$report_id = self::_retrieve_latest_report_id('_GET_FLAT_FILE_OPEN_LISTINGS_DATA_',$merchant_id,$marketplace_short,$status='D' );
		  
			$result="";//TODO:call Proxy to get this report result
		
	}//end of retrieve_prod_list
	
	
	
	/*
	public static function requestAmazonReport($config=''){
		$config=AmazonConst::$config;
		AmazonAPI::set_amazon_config($config);
		$result = AmazonAPI::request_report($storeview ,  $report_type , $startdate , $enddate);
		echo json_encode($result);
	}
	
	public static function checkAmazonRequest($config=''){
		$config=AmazonConst::$config;
		AmazonAPI::set_amazon_config($config);
		$result = AmazonAPI::checkRequestResult($storeview , $report_type , $request_id);
		echo json_encode($result);
	}
	
	public static function getAmazonProductList($config=''){
		$config=AmazonConst::$config;
		AmazonAPI::set_amazon_config($config);
		$result = AmazonAPI::retrieve_prod_list($storeview,$reportid);
		echo json_encode($result);
	}
	*/
	
	
	/******************     post inventory end      **********************/
	
	/*****************      products start *****************/
	
	// public static function GetMatchingProductForId($idList,$idType,$merchant_id,$marketplace_short){
	// 	// set up amazon token
	// 	$merchantIdAccountMap=SaasAmazonAutoSyncHelper::_getMerchantIdAccountInfoMap();
	// 	//echo print_r($merchantIdAccountMap,true);
	// 	$amazonAccessInfo=$merchantIdAccountMap[$merchant_id];
	// 	$marketplace_short_config = array_flip(SaasAmazonAutoSyncHelper::$AMAZON_MARKETPLACE_REGION_CONFIG);
		
		
	// 	$config=array(
	// 			'merchant_id' =>$merchant_id,
	// 			'marketplace_id' => $marketplace_short_config[$marketplace_short],
	// 			'access_key_id' => $amazonAccessInfo["access_key_id"],
	// 			'secret_access_key' => $amazonAccessInfo["secret_access_key"],
	//          'mws_auth_token' => $amazonAccessInfo["mws_auth_token"],
	// 	);
		
	// 	//make invoking journal
	// 	$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array($config ));
		
		
	// 	$timeout=120; //s		
	// 	$parms = array('parms' => json_encode(array('idList' =>$idList , 'idType'=>$idType , 'return_type'=>'json')), 
	// 	               'config' => json_encode($config), 
	// 	              );
		
	// 	//echo "<br> before call_amazon_api  config:".print_r($config,true)."  \n";
	// 	$retInfo = AmazonProxyConnectHelper::call_amazon_api('GetMatchingProductForId', $parms,$timeout);
	// 	//echo "<br>after call_amazon_api retinfo:".print_r($retInfo,true);
		
	// 	//write the invoking result into journal before return
	// 	SysLogHelper::InvokeJrn_UpdateResult($journal_id, $retInfo);
		
	// 	return $retInfo;
	// }//end of GetMatchingProductForId
	
	
	/*****************      products end *****************/
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * request amazon report
	 * @params	$account_info	发起报告请求的amazon账号信息
	 * @params	$report_type	报告请求类型
	 * @params	$invoke_type	调用场景(B:background; W:website)
	 * @params	$start_time		报告统计起始日期
	 * @params	$end_time		报告统计结束日期
	 * 
	 * @return	array ( 'success'=>boolean, 'message'=>text )
	 * @description	发起一个amazon report请求，去获取request_id。后续可通过request_id查询report_id
	 * 
	 * @author	lzhl	2016/11/14	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function requestAmazonReport($account_info, $report_type, $invoke_type='B', $start_date='', $end_date='', $app=''){
		$ret = ['success'=>true,'message'=>''];
		$log = '\n try to requestAmazonReport,account_info='.json_encode($account_info).' ,report_type='.$report_type.'\n ';
		if(empty($account_info['uid'])){
			$ret['success']=false;
			$ret['message'].= '小老板用户ID遗漏！';
			$log.='xlb uid missed;';
		}
		if(empty($account_info['merchant_id'])){
			$ret['success']=false;
			$ret['message'].= 'amazon账号merchant_id遗漏！';
			$log.='amazon account merchant_id missed;';
		}
		if(empty($account_info['marketplace_id'])){
			$ret['success']=false;
			$ret['message'].= 'amazon账号marketplace_id遗漏！';
			$log.='amazon account marketplace_id missed;';
		}

// 		if(empty($account_info['access_key_id'])){
// 		    $ret['success']=false;
// 		    $ret['message'].= 'amazon账号access_key_id遗漏！';
// 		    $log.='amazon account access_key_id missed;';
// 		}
// 		if(empty($account_info['secret_access_key'])){
// 		    $ret['success']=false;
// 		    $ret['message'].= 'amazon账号secret_access_key遗漏！';
// 		    $log.='amazon account secret_access_key missed;';
// 		}

		// dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
		// if(empty($account_info['access_key_id']) && empty($account_info['secret_access_key']) && empty($account_info['mws_auth_token'])){
		if(empty($account_info['mws_auth_token'])){
			$ret['success']=false;
		    $ret['message'].= 'amazon账号mws_auth_token遗漏！';
		    $log.='amazon account mws_auth_token missed;';
		}
		
		$AMAZON_MARKETPLACE_REGION_CONFIG = self::$AMAZON_MARKETPLACE_REGION_CONFIG;
		if(empty($AMAZON_MARKETPLACE_REGION_CONFIG[$account_info['marketplace_id']])){
			$ret['success'] =false;
			$ret['message'].= 'amazon账号marketplace_id没有对应到站点！';
			$log.='amazon account marketplace_id have no short code mapping;';
		}else 
			$marketplace_short = $AMAZON_MARKETPLACE_REGION_CONFIG[$account_info['marketplace_id']];
		
		if($ret['success']===false){
			if($invoke_type=='B') echo $log;
			return $ret;
		}
		$action = 'RequestAmazonReport';
		$get_params = array();
		
		$config=[
			'merchant_id'=>$account_info['merchant_id'],
			'marketplace_id'=>$account_info['marketplace_id'],
	        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 			'access_key_id'=>$account_info['access_key_id'],
// 			'secret_access_key'=>$account_info['secret_access_key'],
	        'mws_auth_token'=>$account_info['mws_auth_token'],
		];
		$get_params['config'] = json_encode($config);
		
		if(empty($start_date)){
			$start_date = date('Y-m-d\TH:i:s',time()-3600*24*1 );
		}else{
			if(is_numeric($start_date)){
				$start_date = date('Y-m-d\TH:i:s',$start_date );
			}else{
				$start_date = strtotime($start_date);
				$start_date = date('Y-m-d\TH:i:s',$start_date );
			}
		}
		if(empty($end_date)){
			$end_date = date('Y-m-d\TH:i:s',time() );
		}else{
			if(is_numeric($end_date)){
				$end_date = date('Y-m-d\TH:i:s',$end_date );
			}else{
				$end_date = strtotime($end_date);
				$end_date = date('Y-m-d\TH:i:s',$end_date );
			}
		}
		$parms = [
			'report_type'=>$report_type,
			'start_date'=>$start_date,
			'end_date'=>$end_date,
		];
		$get_params['parms'] = json_encode($parms);
		
		if($invoke_type=='W'){
			$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array(
				$account_info['uid'],$account_info['merchant_id'],$account_info['marketplace_id'],$report_type));
		}
		try{
			$result = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params);
			if($invoke_type=='B'){
				echo '\n proxy return :'.print_r($result,true);
			}
			$response = empty($result['response'])?[]:$result['response'];
			if(!empty($response['success']) && !empty($response['ReportRequestId']) && !empty($response['ReportProcessingStatus']) && $response['ReportProcessingStatus']=="_SUBMITTED_")
			{
				$request = new AmazonReportRequset();
				$request->uid = $account_info['uid'];
				$request->merchant_id = $account_info['merchant_id'];
				$request->marketplace_short = $marketplace_short;
				$request->report_type = $report_type;
				$request->request_id = $response['ReportRequestId'];
				$request->process_status = 'RS';
				$request->create_time = TimeUtil::getNow();
				if(!empty($app))
					$request->app = $app;
				if(!$request->save()){
					$ret ['success'] = false;
					$ret ['message'] .= print_r($request->errors,true);
				}
				$ret['request_id'] = $request->request_id;
				$ret['report_type'] = $report_type;
			}
			else{
				$ret['success'] =false;
				$ret['message'].= '发起report请求失败！';
				$ret['request_result'] = json_encode($result);
			}
		}catch (\Exception $e) {
			$ret = ['success'=>false , 'message'=> $e->getMessage()];
		}
		
		if($invoke_type=='W' && isset($journal_id)){
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $ret);
		}
		
		return $ret;
	}//end of requestAmazonReport
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * request amazon report
	 * @params	$account_info	发起报告请求的amazon账号信息
	 * @params	$request		amazon_report_requset's model
	 * @params	$invoke_type	调用场景(B:background; W:website)
	 * @return	array ( 'success'=>boolean,'message'=>text )
	 * @description	发起一个amazon get report id请求，去获取某request_id对应的report id。后续可通过report_id查询report的内容
	 * @author	lzhl	2016/11/15	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getAmazonRequestReportId($account_info, $request, $invoke_type='B'){
		$ret = ['success'=>true,'message'=>''];
		
		$action = 'GetAmazonReportIds';
		
		try{
			$get_params = array();
			
			$config=[
				'merchant_id'=>$account_info['merchant_id'],
				'marketplace_id'=>$account_info['marketplace_id'],
		        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 				'access_key_id'=>$account_info['access_key_id'],
// 				'secret_access_key'=>$account_info['secret_access_key'],
		        'mws_auth_token'=>$account_info['mws_auth_token'],
			];
			$get_params['config'] = json_encode($config);
			
			$parms = ['request_id'=>$request->request_id];
			$get_params['parms'] = json_encode($parms);
			
			if($invoke_type=='W'){
				$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array(
						$account_info['uid'],$account_info['merchant_id'],$account_info['marketplace_id'],$request->request_id));
			}
			
			$result = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params);
				
			if($invoke_type=='B'){
				echo '\n proxy return :'.print_r($result,true);
			}
			$result = is_array($result)?$result:json_decode($result,true);
			$response = empty($result['response'])?[]:$result['response'];
			//var_dump($response);
			if(!empty($response['success']) && empty($response['message']) ){
				if( !empty($response['report'][0]['ReportId']) ){//获取到report id
					$request->process_status = 'GS';
					$request->report_id = $response['report'][0]['ReportId'];
					$request->update_time = TimeUtil::getNow();
					$request->next_get_report_id_time = $request->update_time;
					$request->get_report_id_count = $request->get_report_id_count+1;
					
					if(!$request->save()){
						$ret ['success'] = false;
						$ret ['message'] .= print_r($request->errors,true);
					}
					$ret['report_id'] = $request->report_id;
				}else{//没有获取到report id
					$ret['success'] =false;
					$ret['message'].= '获取report id失败：无report';
				}
			}
			else{
				$ret['success'] =false;
				$ret['message'].= '获取report id失败！';
				$ret['request_result'] = is_array($result)?json_encode($result):$result;
			}
			
			if($invoke_type=='W' && isset($journal_id)){
				SysLogHelper::InvokeJrn_UpdateResult($journal_id, $ret);
			}
		}catch (\Exception $e) {
			$ret = ['success'=>false , 'message'=> $e->getMessage()];
		}
		
		return $ret;
	}//end of getAmazonRequestReportId
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * request amazon report
	 * @params	$account_info	发起报告请求的amazon账号信息
	 * @params	$request		amazon_report_requset's model
	 * @params	$invoke_type	调用场景(B:background; W:website)
	 * @return	array ( 'success'=>boolean,'message'=>text )
	 * @description	发起一个amazon get report 请求，去获取report内容。
	 * @author	lzhl	2016/11/15	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getAmazonReportContent($account_info, $request, $invoke_type='B'){
		$ret = ['success'=>true,'message'=>''];
	
		$action = 'get_report_result';
		
		try{
			$get_params = array();
				
			$config=[
			'merchant_id'=>$account_info['merchant_id'],
			'marketplace_id'=>$account_info['marketplace_id'],
	        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 			'access_key_id'=>$account_info['access_key_id'],
// 			'secret_access_key'=>$account_info['secret_access_key'],
	        'mws_auth_token'=>$account_info['mws_auth_token'],
			];
			$get_params['config'] = json_encode($config);
			
			$tmp_file_name = $request->report_id.date('Y-m-d').'.csv';
			$parms = ['report_id'=>$request->report_id,'field_name'=>$tmp_file_name];
			$get_params['parms'] = json_encode($parms);
				
			if($invoke_type=='W'){
				$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array(
						$account_info['uid'],$account_info['merchant_id'],$account_info['marketplace_id'],$request->report_id));
			}
				
			$result = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params);
	
			if($invoke_type=='B'){
				echo '\n proxy return :'.print_r($result,true);
			}
			$result = is_array($result)?$result:json_decode($result,true);	
			$response = empty($result['response'])?[]:$result['response'];
			$ret['response'] = $response;
			
			if(!empty($response['success']) && !empty($response['Contents']) ){
				$request->process_status = 'RD';
				$request->update_time = TimeUtil::getNow();
				$request->next_get_report_data_time = $request->update_time;
				$request->get_report_data_count = $request->get_report_data_count+1;
				$request->report_contents = is_array($response['Contents'])?json_encode($response['Contents']):$response['Contents'];
				if(!$request->save()){
					$ret ['success'] = false;
					$ret ['message'] .= print_r($request->errors,true);
					if($invoke_type=='B')
						echo "\n".$ret ['message'] ;
				}
				//就算保存request记录失败，但获取content成功的话，依旧执行call back
				$cb_rtn = self::callbackReportData($account_info,$response['Contents'],$request->report_type,$request->app);
				if(!empty($cb_rtn['message'])){
					if(empty($cb_rtn['success'])) $ret['success'] =false;
					$ret['message'].= 'callbackReportData error:'.$cb_rtn['message'].';';
				}
			}
			else{
				$ret['success'] =false;
				$ret['message'].= '获取report contents失败！';
				$ret['request_result'] = json_encode($response);
			}
				
			if($invoke_type=='W' && isset($journal_id)){
				SysLogHelper::InvokeJrn_UpdateResult($journal_id, $ret);
			}
		}catch (\Exception $e) {
			$ret = ['success'=>false , 'message'=> $e->getMessage()];
			return $ret;
		}
	
		return $ret;
	}//end of getAmazonReportContent
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * cron request an amazon report
	 * @params	$report_type	请求的报告类型
	 * @return	array ( 'success'=>boolean 'message' =>text )
	 * @description	后台获取amazon report id队列处理
	 *
	 * @author	lzhl	2016/11/15	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronRequestAmazonReport($report_type,$app=''){
		
		global $CACHE;
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
		echo "\n background service enter, job id:".$CACHE['JOBID'];
	
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit
	
		$JOBID=$CACHE['JOBID'];
		$current_time=explode(" ",microtime());	$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
	
		$currentQueueVersion = ConfigHelper::getGlobalConfig("RequestAmazonReport/QueueVersion",'NO_CACHE');
		if (empty($currentQueueVersion))
			$currentQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$queueVersion))
			self::$queueVersion = $currentQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$queueVersion <> $currentQueueVersion){
			TrackingAgentHelper::extCallSum("",0,true);
			exit("Version new $currentQueueVersion , this job ver ".self::$queueVersion." exits for using new version $currentQueueVersion.");
		}
		
		/*++++++++++++++++++++++++++不同请求类型单独实现 start+++++++++++++++++++++++++++++++*/
		//FBA库存报告账号队列
		if($report_type=='_GET_AFN_INVENTORY_DATA_'){
			$sinceTime = date('Y-m-d H:i:s',time()-3600*12);//上次库存报告请求大于12小时前的才执行
			
			$pendingAccounts = SaasAmazonAutosyncV2::find()
				->where(['status'=>1,'process_status'=>[0,2],'type'=>'amzFbaInventory'])
				->all();
			/*
			$pendingAccounts = AmazonFbaInventoryAccounts::find()
				->where( " `is_active`=1 and `last_request_report_time`<='$sinceTime'" )
				->orderBy(" last_request_report_time ASC ")
				->all();
			*/
			if(!empty($pendingAccounts)){
				echo "\n get ".count($pendingAccounts).' accounts need to request '.$report_type.' report;\n statr to foreach pending accounts';
				foreach ($pendingAccounts as $onePending){
					$sql = "SELECT u.*, m.`marketplace_id`, m.`access_key_id`, m.`secret_access_key`,m.`mws_auth_token`
						FROM saas_amazon_user_marketplace m
						LEFT JOIN saas_amazon_user u
						ON m.`amazon_uid` = u.amazon_uid
						WHERE u.merchant_id='".$onePending->platform_user_id."' AND m.marketplace_id='".$onePending->site_id."'";
					$command = Yii::$app->db->createCommand($sql);
					$account_info = $command->queryOne();
					
					if(!empty($account_info)){
						echo "\n this pending account is :uid=".$account_info['uid']." merchant_id=".$account_info['merchant_id']." marketplace_id=".$account_info['marketplace_id'].";";
						
						$rtn = self::requestAmazonReport($account_info, $report_type,'B');
						
						echo "\n one request result:".print_r($rtn,true);
						$onePending->last_finish_time = time();
						$onePending->update_time = time();
						if(empty($rtn['success'])){
							$onePending->err_msg = $rtn['message'];
							$onePending->err_cnt = (int)$onePending->err_cnt+1;
						}else{
							$onePending->err_cnt = 0;
						}
						if(!$onePending->save()){
							echo "\n update pending account's last_request_report_time failed:".print_r($onePending->errors,true);
						}
					}else{
						$onePending->last_finish_time = time();
						$onePending->update_time = time();
						$onePending->err_msg = 'had no matching user account info';
						echo "\n  had no matching user account info! sql is: \n ".$sql;
						if(!$onePending->save()){
							echo "\n update pending account's last_request_report_time failed:".print_r($onePending->errors,true);
						}
						return false;
					}
				}
			}else{
				echo "\n no pending account to handle.";
				return true;
			}
		}
		//FBA已收到库存报告
		if($report_type=='_GET_FBA_FULFILLMENT_INVENTORY_RECEIPTS_DATA_'){
			$sinceTime = date('Y-m-d H:i:s',time()-3600*12);//上次库存报告请求大于12小时前的才执行
			$pendingAccounts = AmazonFbaInventoryAccounts::find()
				->where( " `is_active`=1 and `last_stock_in_report_time`<='$sinceTime'" )
				->orderBy(" last_stock_in_report_time ASC ")
				->all();
			if(!empty($pendingAccounts)){
				echo "\n get ".count($pendingAccounts).' accounts need to request '.$report_type.' report;\n statr to foreach pending accounts';
				foreach ($pendingAccounts as $onePending){
					$sql = "SELECT u.*, m.`marketplace_id`, m.`access_key_id`, m.`secret_access_key`, m.`mws_auth_token`
						FROM saas_amazon_user_marketplace m
						LEFT JOIN saas_amazon_user u
						ON m.`amazon_uid` = u.amazon_uid
						WHERE u.uid =".$onePending->uid." AND u.merchant_id='".$onePending->merchant_id."' AND m.marketplace_id='".$onePending->marketplace_id."'";
					$command = Yii::$app->db->createCommand($sql);
					$account_info = $command->queryOne();
						
					if(!empty($account_info)){
						echo "\n this pending account is :uid=".$account_info['uid']." merchant_id=".$account_info['merchant_id']." marketplace_id=".$account_info['marketplace_id'].";";
			
						$rtn = self::requestAmazonReport($account_info, $report_type,'B');
			
						echo "\n one request result:".print_r($rtn,true);
						if(!empty($rtn['success'])){
							$onePending->last_stock_in_report_time = TimeUtil::getNow();
							if(!$onePending->save()){
								echo "\n update pending account's last_stock_in_report_time failed:".print_r($onePending->errors,true);
							}
						}
					}else{
						echo "\n  had no matching user account info! sql is: \n ".$sql;
						return false;
					}
				}
			}else{
				echo "\n no pending account to handle.";
				return true;
			}
		}
		
		// dzt20170224 amazon active listings report 
		if($report_type=='_GET_MERCHANT_LISTINGS_DATA_' && empty($app) ){
		    $sql = "SELECT *
					FROM  `saas_amazon_user` AS su
					LEFT JOIN saas_amazon_user_marketplace AS sm ON su.amazon_uid = sm.amazon_uid
					WHERE su.is_active =1   
					";
		    
		    $command = Yii::$app->db->createCommand($sql);
		    
		    $dataReader=$command->query();
		    
		    echo PHP_EOL."start to foreach pending accounts and request ".$report_type." report;".PHP_EOL;
		    while(($row=$dataReader->read())!==false) {
		       
		        $marketplace_short = self::$AMAZON_MARKETPLACE_REGION_CONFIG[$row['marketplace_id']];
		        
		        $request = AmazonReportRequset::findOne([
		                'uid' => $row['uid'],
		                'merchant_id' => $row['merchant_id'],
		                'marketplace_short' => $marketplace_short,
		                'report_type' => $report_type,
		        		'app'=>[NULL,''],
		        ]);
		        
		        if(!empty($request)){
		            echo "this account already existed :uid=".$row['uid']." amazon_uid=".$row['amazon_uid']." merchant_id=".$row['merchant_id']." marketplace_id=".$row['marketplace_id'].";".PHP_EOL;
		            continue;
		        }
		        
		        // @todo dzt20170306 在清理 amazon_report_requset表的时候，只保留了近两天记录，导致上面判断产品是否已经拉取过的逻辑失效了。
		        // 这里要加上到各个表查merchant_id 的逻辑去判断是否要拉取该 merchant 产品report
		        
		        echo "this pending account is :uid=".$row['uid']." amazon_uid=".$row['amazon_uid']." merchant_id=".$row['merchant_id']." marketplace_id=".$row['marketplace_id'].";".PHP_EOL;
		        $rtn = self::requestAmazonReport($row, $report_type,'B');
		         
		        echo "one request result:".print_r($rtn,true).PHP_EOL;
		        if(!empty($rtn['success'])){
		            echo "request success".PHP_EOL;
		        }else{
		            echo "request fail:".$rtn['message'].PHP_EOL;
		        }
		    }
		    
		    // 要不要删除已有 listing
// 		    echo PHP_EOL."ready to delete existing listings.".PHP_EOL; 
		   
		    
		}
		//lzhl	20170224 	manual amazon active listings report
		if($report_type=='_GET_MERCHANT_LISTINGS_DATA_' && !empty($app) ){
			$sql = "SELECT * 
					FROM  `saas_amazon_user` AS su 
					LEFT JOIN saas_amazon_user_marketplace AS sm ON su.amazon_uid = sm.amazon_uid 
					LEFT JOIN saas_amazon_autosync_v2 AS sy ON su.amazon_uid = sy.eagle_platform_user_id 
					WHERE su.is_active =1 and sy.type='amzListing' and sy.status=1 and sm.marketplace_id = sy.site_id 
					and (sy.last_finish_time IS NULL or sy.last_finish_time<".(time()-3600*12)." or sy.next_execute_time IS NULL or sy.next_execute_time<".time().")
					";
		
			$command = Yii::$app->db->createCommand($sql);
		
			$dataReader=$command->query();
		
			echo PHP_EOL."start to foreach pending accounts and request ".$report_type." report, app: $app;".PHP_EOL;
			while(($row=$dataReader->read())!==false) {
				 
				$marketplace_short = self::$AMAZON_MARKETPLACE_REGION_CONFIG[$row['marketplace_id']];
		
				$request = AmazonReportRequset::find()
					->where([
						'uid' => $row['uid'],
						'merchant_id' => $row['merchant_id'],
						'marketplace_short' => $marketplace_short,
						'report_type' => $report_type,
						])
					->andWhere(" `app`<>'' and `app` IS NOT NULL")
					->one();
				//如果通过某个指定的app最近获取过report，则跳过不重复拉取
				if(!empty($request)){
					echo "this account already existed by :uid=".$row['uid']." amazon_uid=".$row['amazon_uid']." merchant_id=".$row['merchant_id']." marketplace_id=".$row['marketplace_id'].";".PHP_EOL;
					SaasAmazonAutosyncV2::updateAll(
						['status'=>0,'update_time'=>time(),'process_status'=>2],
						" `eagle_platform_user_id`=".$row['eagle_platform_user_id']." and `platform_user_id`='".$row['platform_user_id']."' and `site_id`='".$row['site_id']."' and `type`='amzListing' "
					);
					continue;
				}
		
				// 这里要加上到各个表查merchant_id 的逻辑去判断是否要拉取该 merchant 产品report
		
				echo "this pending account is :uid=".$row['uid']." amazon_uid=".$row['amazon_uid']." merchant_id=".$row['merchant_id']." marketplace_id=".$row['marketplace_id'].";".PHP_EOL;
				$rtn = self::requestAmazonReport($row, $report_type,'B','','',$app);
				 
				echo "one request result:".print_r($rtn,true).PHP_EOL;
				if(!empty($rtn['success'])){
					echo "request success".PHP_EOL;
					//由于是手动调用，因此每次申请report成功之后，都将status设置为0，直到下次调用才开启
					SaasAmazonAutosyncV2::updateAll(
						['status'=>0,'last_finish_time'=>time(),'next_execute_time'=>time()+3600*12,'update_time'=>time(),'process_status'=>2],
						" `eagle_platform_user_id`=".$row['eagle_platform_user_id']." and `platform_user_id`='".$row['platform_user_id']."' and `site_id`='".$row['site_id']."' and `type`='amzListing' "
					);
				}else{
					echo "request fail:".$rtn['message'].PHP_EOL;
					if((int)$row['err_cnt'] <10)
						SaasAmazonAutosyncV2::updateAll(
							['process_status'=>1,'update_time'=>time(),'err_cnt'=>(int)$row['err_cnt']+1,'err_msg'=>$rtn['message']],
							" `eagle_platform_user_id`=".$row['eagle_platform_user_id']." and `platform_user_id`='".$row['platform_user_id']."' and `site_id`='".$row['site_id']."' and `type`='amzListing' "
						);
					else //重试超过10次则先停半小时
						SaasAmazonAutosyncV2::updateAll(
							['process_status'=>0,'update_time'=>time(),'last_finish_time'=>time(),'next_execute_time'=>(time()+1800), 'err_cnt'=>0,'err_msg'=>$rtn['message']],
							" `eagle_platform_user_id`=".$row['eagle_platform_user_id']." and `platform_user_id`='".$row['platform_user_id']."' and `site_id`='".$row['site_id']."' and `type`='amzListing' "
						);
						
				}
			}
		
			// 要不要删除已有 listing
			// 		    echo PHP_EOL."ready to delete existing listings.".PHP_EOL;
				 
		
		}
		/*++++++++++++++++++++++++++实现处理 end+++++++++++++++++++++++++++++++*/
	}//end of cronRequestAmazonReport
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * cron get amazon report id
	 * @return	array ( 'success'=>boolean 'message' =>text )
	 * @description	后台获取amazon report id队列处理
	 *
	 * @author	lzhl	2016/11/15	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronGetAmazonRequestReportId(){
		global $CACHE;
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
		echo '\n background service enter, job id:'.$CACHE['JOBID'];
		
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit
		
		$JOBID=$CACHE['JOBID'];
		$current_time=explode(" ",microtime());	$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
		
		$currentQueueVersion = ConfigHelper::getGlobalConfig("GetAmazonRequestReportId/QueueVersion",'NO_CACHE');
		if (empty($currentQueueVersion))
			$currentQueueVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$queueVersion))
			self::$queueVersion = $currentQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$queueVersion <> $currentQueueVersion){
			TrackingAgentHelper::extCallSum("",0,true);
			exit("Version new $currentQueueVersion , this job ver ".self::$queueVersion." exits for using new version $currentQueueVersion.");
		}
		
		$oneRequest = AmazonReportRequset::find()
			->where(['process_status'=>['RS','GF']])//RS未获取,GF上次获取失败 只有这两种状态才会尝试获取
			->andWhere(' get_report_id_count<10 ')//获取重试次数超过10次就不再获取
			->orderBy("next_get_report_id_time ASC")
			->one();
		
		if(!empty($oneRequest)){
			echo "\n request id :".$oneRequest->id.' read;';
			$amzUser = SaasAmazonUser::find()->where(['uid'=>$oneRequest->uid,'merchant_id'=>$oneRequest->merchant_id])->asArray()->one();
			if(!empty($amzUser)){
				$marketplace_id = $oneRequest->marketplace_short;
				$marketplace_mapping = self::$AMAZON_MARKETPLACE_REGION_CONFIG;
				foreach ($marketplace_mapping as $marketplace=>$short_code){
					if(strtoupper($oneRequest->marketplace_short)==$short_code)
						$marketplace = $marketplace_id;
				}
				$amz_user_marketplace = SaasAmazonUserMarketplace::find()->where(['amazon_uid'=>$amzUser['amazon_uid']])->asArray()->One();
				if(!empty($amz_user_marketplace)){
					$account_info = [
						'uid'=>$oneRequest->uid,
						'merchant_id'=>$oneRequest->merchant_id,
						'marketplace_id'=>$amz_user_marketplace['marketplace_id'],
				        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 						'access_key_id'=>$amz_user_marketplace['access_key_id'],
// 						'secret_access_key'=>$amz_user_marketplace['secret_access_key'],
				        'mws_auth_token'=>$amz_user_marketplace['mws_auth_token'],
					];
					$getReportIdResponse = self::getAmazonRequestReportId($account_info, $oneRequest, 'B');
					if($getReportIdResponse['success']){
						echo "\n request :".$oneRequest->id." get report id success.";
						return true;
					}else{
						echo "\n request :".$oneRequest->id." get report id failed.".@$getReportIdResponse['message'];
						$oneRequest->process_status = 'GF';
						$oneRequest->update_time = TimeUtil::getNow();
						//下次尝试时间为：现时+重试次数*2 分钟
						$next_get_report_id_time = strtotime($oneRequest->update_time) + 60*2*$oneRequest->get_report_id_count;
						$oneRequest->next_get_report_id_time = date('Y-m-d H:i:s',$next_get_report_id_time);
						$oneRequest->get_report_id_count = $oneRequest->get_report_id_count+1;
						//获取多次都没结果，则可能是没有该站点的FBA库存，需要删除旧库存记录
						if($oneRequest->get_report_id_count >= 10){
							self::callbackReportData($account_info,[],$oneRequest->report_type);
						}
						
						if(!$oneRequest->save()){
							echo "\n save request info failed:". print_r($oneRequest->errors,true);
							return false;
						}
					}
				}else{
					echo "\n request had no matching account marketplace info.";
					return false;
				}
			}else{
				echo "\n request had no matching user account info.";
				$oneRequest->update_time = TimeUtil::getNow();
				$oneRequest->process_status = 'GF';
				$oneRequest->get_report_id_count = (int)$oneRequest->get_report_id_count + 1;
				$oneRequest->report_contents = 'request had no matching user account info';
				$oneRequest->save(false);
				return false;
			}
		}else{
			echo "\n no request to handle.";
			return 'N/A';
		}
	}//end of cronGetAmazonRequestReportId
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * cron get amazon report content
	 * @return	array ( 'success'=>boolean 'message' =>text )
	 * @description	后台获取amazon report 内容队列处理
	 *
	 * @author	lzhl	2016/11/15	初始化
	 * @author  dzt		2017/02/25  修改tag dzt20170225 获取account_info时，加上对应的marketplace_id过滤
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function crongetAmazonReportContent(){
		
		global $CACHE;
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
		echo '\n background service enter, job id:'.$CACHE['JOBID'];
	
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit
	
		$JOBID=$CACHE['JOBID'];
		$current_time=explode(" ",microtime());	$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
	
		$currentQueueVersion = ConfigHelper::getGlobalConfig("getAmazonReportContent/QueueVersion",'NO_CACHE');
		if (empty($currentQueueVersion))
			$currentQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$queueVersion))
			self::$queueVersion = $currentQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$queueVersion <> $currentQueueVersion){
			TrackingAgentHelper::extCallSum("",0,true);
			exit("Version new $currentQueueVersion , this job ver ".self::$queueVersion." exits for using new version $currentQueueVersion.");
		}
		
		$oneRequest = AmazonReportRequset::find()
			->where(['process_status'=>['GS']])//只有GS(获取report id成功)状态才会尝试获取
			->andWhere(' get_report_data_count<10 ')//获取重试次数超过10次就不再获取
			->orderBy("next_get_report_data_time ASC")
			->one();
		
		if(!empty($oneRequest)){
			echo "\n request id :".$oneRequest->id.' read;';
			$amzUser = SaasAmazonUser::find()->where(['uid'=>$oneRequest->uid,'merchant_id'=>$oneRequest->merchant_id])->asArray()->one();
			if(!empty($amzUser)){
				$marketplace_id = $oneRequest->marketplace_short;
				$marketplace_mapping = self::$AMAZON_MARKETPLACE_REGION_CONFIG;
				foreach ($marketplace_mapping as $marketplace=>$short_code){
					if(strtoupper($oneRequest->marketplace_short)==$short_code)
						$marketplace_id = $marketplace;
				}
				$amz_user_marketplace = SaasAmazonUserMarketplace::find()->where(['amazon_uid'=>$amzUser['amazon_uid'],'marketplace_id'=>$marketplace_id])->asArray()->One();// dzt20170225
				if(!empty($amz_user_marketplace)){
					$account_info = [
						'uid'=>$oneRequest->uid,
						'merchant_id'=>$oneRequest->merchant_id,
						'marketplace_id'=>$amz_user_marketplace['marketplace_id'],
				        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 						'access_key_id'=>$amz_user_marketplace['access_key_id'],
// 						'secret_access_key'=>$amz_user_marketplace['secret_access_key'],
				        'mws_auth_token'=>$amz_user_marketplace['mws_auth_token'],
					];
					$getReportResponse = self::getAmazonReportContent($account_info, $oneRequest, 'B');
					//print_r($getReportResponse);
					if($getReportResponse['success']){
						echo "\n request :".$oneRequest->id." get report content success.";
					}else{
						echo "\n request :".$oneRequest->id." get report content failed:".$getReportResponse['message'];
						//$oneRequest->process_status = 'GF';
						$oneRequest->update_time = TimeUtil::getNow();
						//下次尝试时间为：现时+重试次数*10 分钟
						$next_get_report_data_time = strtotime($oneRequest->update_time) + 60*10*$oneRequest->get_report_data_count;
						$oneRequest->next_get_report_data_time = date('Y-m-d H:i:s',$next_get_report_data_time);
						$oneRequest->get_report_data_count = $oneRequest->get_report_data_count+1;
		
						if(!$oneRequest->save()){
							echo "\n save request info failed:". print_r($oneRequest->errors,true);
							return false;
						}
					}
				}else{
					echo "\n request had no matching account marketplace info.";
					return false;
				}
			}else{
				echo "\n request had no matching user account info.";
				$oneRequest->update_time = TimeUtil::getNow();
				$oneRequest->process_status = 'GF';
				$oneRequest->get_report_data_count = (int)$oneRequest->get_report_data_count + 1;
				$oneRequest->report_contents = 'request had no matching user account info';
				$oneRequest->save(false);
				return false;
			}
		}else{
			echo "\n no request to handle.";
			return 'N/A';
		}
	}//end of cronGetAmazonRequestReportId
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取到amz report内容之后的数据处理
	 * @return	array ( 'success'=>boolean 'message' =>text )
	 * @params	$account_info	array	发起报告请求的amazon账号信息
	 * @params	$request		model	请求信息
	 * @author	lzhl	2016/11/17	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function callbackReportData($account_info, $contents,$report_type,$app=''){
		try{
			$uid = $account_info['uid'];
			if (empty($uid)){
				echo "\n account uid missed";
				return ['success'=>false,'message'=>'account uid missed'];
			}
			
			//FBA库存报告
			if($report_type=='_GET_AFN_INVENTORY_DATA_'){
				$callback_rtn = FbaWarehouseHelper::UpdateFbaSku($account_info['merchant_id'], $account_info['marketplace_id'], $contents);
				if($callback_rtn['status']!=='1')
					return ['success'=>false,'message'=>$callback_rtn['msg']];
			}
			//FBA仓库入库报告
			if($report_type=='_GET_FBA_FULFILLMENT_INVENTORY_RECEIPTS_DATA_'){
				
			}
			
			// dzt20170224 保存active listings report 的产品信息
			if($report_type=='_GET_MERCHANT_LISTINGS_DATA_' && empty($app)){
			    return self::saveReportListings($account_info, $contents);
			}
			
			//lzhl 保存active listings report 的产品信息到user库，区别于无app参数传入
			if($report_type=='_GET_MERCHANT_LISTINGS_DATA_' && !empty($app)){
				return self::saveReportListingsToUserDb($account_info, $contents);
			}
			
			return ['success'=>true,'message'=>''];
		}catch (\Exception $e) {
			return ['success'=>false,'message'=>"Exception:file:" . $e->getFile() . ",line:" . $e->getLine() . ",message:" . $e->getMessage()];
		}
	}//endof callbackReportData
	
	/**
	 *前端调用，同步amazon FBA库存
	 */
	public static function webSyncAmzFbaInventory($merchant_id,$marketplace_id){
		try{
			$result = ['success'=>true,'message'=>''];
			$sql = "SELECT u.*, m.`marketplace_id`, m.`access_key_id`, m.`secret_access_key`, m.`mws_auth_token`
							FROM saas_amazon_user_marketplace m
							LEFT JOIN saas_amazon_user u
							ON m.`amazon_uid` = u.amazon_uid
							WHERE u.merchant_id='".$merchant_id."' AND m.marketplace_id='".$marketplace_id."'";
			$command = Yii::$app->db->createCommand($sql);
			$account_info = $command->queryOne();
			
			$AutosyncV2 = SaasAmazonAutosyncV2::find()
				->where(['type'=>'amzFbaInventory','platform_user_id'=>$merchant_id,'site_id'=>$marketplace_id])
				->one();
			if(empty($AutosyncV2))
				return ['success'=>false,'message'=>'该店铺未开通FBA库存同步队列，请联系客服'];
			
			if(!empty($account_info)){
				//尝试requestReport
				// dzt20190411 问开发群的人说一天间隔可能没有报告，15天是经验值
				// 这次的问题是mws_token为空导致跳过接口调用，一天也是有的，不过这里还是改成15天稳点
			    $start_date = date('Y-m-d\TH:i:s',time() - 15*86400);
			    $end_date = date('Y-m-d\TH:i:s',time());
				
				$rtn = self::requestAmazonReport($account_info, $report_type='_GET_AFN_INVENTORY_DATA_','W', $start_date, $end_date);
				$AutosyncV2->last_finish_time = time();
				$AutosyncV2->update_time = time();
				if(!empty($rtn['success'])){
					$AutosyncV2->err_msg = $rtn['message'];
					$AutosyncV2->err_cnt = (int)$AutosyncV2->err_cnt+1;
				}else{
					$AutosyncV2->err_cnt = 0;
				}
				$AutosyncV2->save(false);
			}else{
				//$AutosyncV2->last_finish_time = time();
				$AutosyncV2->update_time = time();
				$AutosyncV2->err_cnt = (int)$AutosyncV2->err_cnt +1;
				$AutosyncV2->err_msg = 'had no matching user account info';
				$result['message'] .= 'had no matching user account info;';
				$result['success'] = false;
				if(!$AutosyncV2->save()){
					$result['message'] .= "update pending account's autosync info failed:".print_r($AutosyncV2->errors,true).';';
				}
				return $result;
			}
			
			//requestReport成功，尝试获取reportId
			if(!empty($rtn['request_id'])){
				$request = AmazonReportRequset::find()
					->where(['request_id'=>$rtn['request_id'],'report_type'=>'_GET_AFN_INVENTORY_DATA_'])
					->andWhere(['merchant_id'=>$account_info['merchant_id'],'uid'=>$account_info['uid']])
					->one();
				if(!empty($request)){
					$request_db_id = $request->id;
					sleep(15);
					$rtn2 = self::getAmazonRequestReportId($account_info, $request,'W');
				}else{
					$result['success'] = false;
					$result['message'] .= "request has not record;";
					return $result;
				}
				$request->get_report_id_count = 10;
				$request->save(false);
				//获取report id失败
				if(empty($rtn2['success'])){
					$result['success'] = false;
					$result['message'] .= $rtn2['message'];
					//获取没结果，则可能是没有该站点的FBA库存，需要删除旧库存记录
					self::callbackReportData($account_info,[],'_GET_AFN_INVENTORY_DATA_');
					$request->get_report_data_count = 10;
					$request->save(false);
					return $result;
				}
			}else{
				$result['success'] = false;
				$result['message'] .= 'request return null request id;';
				return $result;
			}
			
			//尝试获取report data
			if(!empty($rtn2['report_id'])){
				$request = AmazonReportRequset::findOne($request_db_id);
				$rtn3 = self::getAmazonReportContent($account_info, $request,'W');
				//print_r($rtn3);
				if(empty($rtn3['success'])){
					$result['success'] = false;
					$result['message'] .= 'save report data to db failed:'.$rtn3['message'].';';
					$request->get_report_data_count = 10;
					$request->save(false);
					return $result;
				}
			}else{
				$result['success'] = false;
				$result['message'] .= 'request return null report id;';
				
				//获取多次都没结果，则可能是没有该站点的FBA库存，需要删除旧库存记录
				self::callbackReportData($account_info,[],'_GET_AFN_INVENTORY_DATA_');
				
				
				return $result;
			}
			
			return $result;
		}catch(\Exception $e){
			$result['success'] = false;
			$result['message'] .= 'Exception:'.$e->getMessage().';';
			return $result;
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据report id　amazon报告内容 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $report_id			报告ID
	 * @param $merchant_id			卖家 ID（amazon 受权信息）
	 * @param $marketplace_short	marketplace 缩写代号（amazon 受权信息）
	 +---------------------------------------------------------------------------------------------
	 * @return				na
	 * @description			request amazon report
	 * 						调用本方法以后，来获取amazon 服务器端会生成相关类型报告的内容
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/11/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	// public static function get_amazon_report_data($report_id,$merchant_id,$marketplace_short){
	// 	// set up amazon token
	// 	$merchantIdAccountMap=SaasAmazonAutoSyncHelper::_getMerchantIdAccountInfoMap();
	// 	//echo print_r($merchantIdAccountMap,true);
	// 	$amazonAccessInfo=$merchantIdAccountMap[$merchant_id];
	// 	$marketplace_short_config = array_flip(SaasAmazonAutoSyncHelper::$AMAZON_MARKETPLACE_REGION_CONFIG);
		
		
	// 	$config=array(
	// 			'merchant_id' =>$merchant_id,
	// 			'marketplace_id' => $marketplace_short_config[$marketplace_short],
	// 			'access_key_id' => $amazonAccessInfo["access_key_id"],
	// 			'secret_access_key' => $amazonAccessInfo["secret_access_key"],
	//          'mws_auth_token' => $amazonAccessInfo["mws_auth_token"],
	// 	);
		
	// 	//make invoking journal
	// 	$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array($config ));
		
		
	// 	$timeout=120; //s		
	// 	$parms = array('parms' => json_encode(array('report_id' =>$report_id)), 
	// 	               'config' => json_encode($config), 
	// 	              );
		
	// 	//echo "<br> before call_amazon_api  config:".print_r($config,true)."  \n";
	// 	$retInfo = AmazonProxyConnectHelper::call_amazon_api('get_report_result', $parms,$timeout);
	// 	//echo "<br>after call_amazon_api retinfo:".print_r($retInfo,true);
		
	// 	//write the invoking result into journal before return
	// 	SysLogHelper::InvokeJrn_UpdateResult($journal_id, $retInfo);
		
	// 	return $retInfo;
	// }//end of get_amazon_report_data
	/*****************      reports end *****************/
	
	/*****************      fba start *****************/
	/*
	public static function retrieveAmazonFBAProductInventory($config=''){
		$config=AmazonConst::$config;
		AmazonAPI::set_amazon_config($config);
		$report_id = $reportid;
		$result = AmazonAPI::_retrieve_report_data($storeview,$report_id , $field_name);
		echo json_encode($result);
	}
	*/
	/*****************      fba end *****************/
	 
	
	////////////////////////////////  Private functions   /////////////////////

/**
 * [_insertAmazonSubmitQueue 把Amazon API request写入到submit queue表中]
 *
 * 把Amazon 数据 写入到 Amazon Order 表中，
 * 如果该Order已经存在表中，首先判断是否数据和 parm 中一致，如果一致，ignored 返回 true，否则，修改。
 * 同时Order Details 也会写入相应的表中，
 * 如果已存在的Order的Order Items数量和 parm 中的数据不一样，
 * 会以新的数据写入一次。并且返回的 ignored 是 false。
 *
 * @inition  @author		yzq		2014/03/20
 * @Author   willage
 * @DateTime 2016-09-05T17:49:51+0800
 *
 * 修改：@DateTime 2016-09-19T09:29:10+0800
 * 1、增加插入字段api_type(标识第一次虚拟发货和再次虚拟发货)
 *
 * @param    [type]                   $action            [description]
 * @param    [type]                   $amz_order_id      [description]
 * @param    [type]                   $merchant_id       [description]
 * @param    [type]                   $marketplace_short [description]
 * @param    [type]                   $parms_array       [description]
 * @param    [type]                   $additionInfoArr   [description]
 * @return   [type]                                      [description]
 * @return				返回数据写入的结果
 * 						array( 'success' = true,'message'='......' ,
 * 								'ignored'= false
 * 							  )
 */
	static function _insertAmazonSubmitQueue($action,$amz_order_id,$merchant_id,$marketplace_short,$api_type,$parms_array,$additionInfoArr){
	/*	$sql = " insert into amazon_order_submit_queue (order_id,merchant_id,marketplace_short,process_status,api_action,create_time,parms)
					values (:order_id,:merchant_id,:marketplace_short,0,:api_action,null,:parms) ";
		$command = Yii::app()->db->createCommand($sql);
		$command->bindValue(":api_action",($action),PDO::PARAM_STR);
		$command->bindValue(":order_id", ($amz_order_id) , PDO::PARAM_STR);
		$command->bindValue(":merchant_id",($merchant_id),PDO::PARAM_STR);
		$command->bindValue(":marketplace_short",($marketplace_short),PDO::PARAM_STR);
		$command->bindValue(":parms", (CJSON::encode($parms_array)) ,PDO::PARAM_STR);
		$result = $command->execute();*/
		\Yii::info("_insertAmazonSubmitQueue 1  ","file");

		 $amazonOrderSumbitObj=new AmazonOrderSubmitQueue();
		 $amazonOrderSumbitObj->merchant_id=$merchant_id;
		 $amazonOrderSumbitObj->marketplace_short=$marketplace_short;
		 $amazonOrderSumbitObj->api_action=$action;
		 $amazonOrderSumbitObj->parms=json_encode($parms_array);
		 $amazonOrderSumbitObj->order_id=$amz_order_id;
		 $amazonOrderSumbitObj->process_status=0;
		 $amazonOrderSumbitObj->create_time=time();
		 $amazonOrderSumbitObj->update_time=$amazonOrderSumbitObj->create_time;
		 $amazonOrderSumbitObj->addition_info=json_encode($additionInfoArr);
		 $amazonOrderSumbitObj->api_type=$api_type;//添加字段记录第一次或第二次标记发货，willage-2016/9/5

		 \Yii::info("_insertAmazonSubmitQueue 2  ","file");
		 if (!$amazonOrderSumbitObj->save()){
	// 	 	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,""," error:".print_r($amazonOrderSumbitObj->errors,true),"Error");
		 	\Yii::error("_insertAmazonSubmitQueue  ".print_r($amazonOrderSumbitObj->errors,true)  , 'file');
		 	return false;
	//	 	\Yii::error(["Amazon", __CLASS__,__FUNCTION__,""," error:".print_r($amazonOrderSumbitObj->errors,true) ] , 'edb\user');
		 }

		 return true;
	}
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Amazon API request 写入到 amazon report queue common 表中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data 		array 中包含所有amaozn order 的信息
	 +---------------------------------------------------------------------------------------------
	 * @return				返回数据写入的结果
	 * 						array( 'success' = true,'message'='......' ,
	 * 								'ignored'= false
	 * 							  )
	 * @description			
	 * 
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/11/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function insertAmazonReportQueueCommon($request_id,$report_type,$merchant_id,$marketplace_short){
		$amazon_report_quene_common_obj = new AmazonReportQueueCommon();
		
		$amazon_report_quene_common_obj->merchant_id=$merchant_id;	 
		$amazon_report_quene_common_obj->marketplace_short=$marketplace_short;	 
		$amazon_report_quene_common_obj->report_type=$report_type;	
		$amazon_report_quene_common_obj->request_id=$request_id;	 
		$amazon_report_quene_common_obj->create_time=date("Y-m-d h:i:s");
		$amazon_report_quene_common_obj->process_status='S';
		 
		 if (!$amazon_report_quene_common_obj->save()){
		 	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,""," error:".print_r($amazon_report_quene_common_obj->errors,true),"Error");
		 }
	}
	
	static function _retrieve_latest_report_request($report_type,$merchant_id,$marketplace_short,$status='S' ){
	/*status : S：发送请求未可用;D:可用，未被使用C:已使用;F:失败*/	
		$sql = "select * from amazon_report_queue where merchant_id='$merchant_id' and 
				marketplace_short = '$marketplace_short' and process_status='$status' and 
				report_type ='$report_type' order by create_time desc";
		
		$command = Yii::app()->db->createCommand($sql);
		$result = $command->query();
		if (!isset($result['report_id']) )
			$result['report_id'] = -1;
		
		return $result['report_id'];
	}//end of _retrieve_latest_report_id
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取最新的report id 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $reportType			报告类型
	 * 		  $merchant_id			卖家 ID（amazon 受权信息）
	 * 		  $marketplace_short	marketplace 缩写代号（amazon 受权信息）
	 * 		  $status	 		  	S：发送请求未可用;D:可用，未被使用C:已使用;F:失败
	 +---------------------------------------------------------------------------------------------
	 * @return				返回报告ID （string）
	 * 						eg>.  123456789
	 * @description			
	 * 
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/11/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function retrieve_common_latest_report_id($reportType , $merchant_id,$marketplace_short,$status){
		/*status : S：发送请求未可用;D:可用，未被使用C:已使用;F:失败*/	
		$sql = "select * from amazon_report_queue_common  where merchant_id='$merchant_id' and 
				marketplace_short = '$marketplace_short' and process_status='$status' and 
				report_type ='$reportType' order by create_time desc";
		
		$command = Yii::app()->db->createCommand($sql);
		$result = $command->queryRow();
		if (!isset($result['report_id']) )
			$result['report_id'] = 0;
		
		return $result['report_id'];
	}//end of _retrieve_common_latest_report_id
	 
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * to convert the eagle sku into amazon item code and qty format
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param order id 			Amazon Order id
	 * @param items				array of Items of sku and qty in this package: 
	 * 							e.g.: array( array('sku'=>SKESIPHC001, 'qty'=>2) , 
	 * 										 array('sku'=>SK32432ABC, 'qty'=>1)
	 * 									)
	 +---------------------------------------------------------------------------------------------
	 * @return					array of Items of sku and qty in this package: 
	 * 							e.g.: array( array('ItemCode'=>12313423556, 'ItemShipQty'=>2) , 
	 * 										 array('ItemCode'=>78913422432, 'ItemShipQty'=>1)
	 * 									)
	 * @description			Eagle 内部的SKU 和Amazon 的item code需要进行转换，否则amazon 不认得
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/03/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function _getAmazonItemFormat($amazon_order_id, $items){
		
	// 	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,""," _getAmazonItemFormat  amazon_order_id:$amazon_order_id","Debug");
		\Yii::info(["Amazon", __CLASS__,__FUNCTION__,""," _getAmazonItemFormat  amazon_order_id:$amazon_order_id"] , 'edb\user');
		
		
		$rtn_items = array();
		$success = true;
		$message = "";
		foreach($items as $anItem){
			// if (isset($anItem['quantity']))
			// 	$anItem['qty'] = $anItem['quantity'];
			if (isset($anItem['ordered_quantity']))
				$anItem['qty'] = $anItem['ordered_quantity'];
			
			if (isset($anItem['SKU']) and !isset($anItem['sku']))
				$anItem['sku'] = $anItem['SKU'];
	
			// 遇到有修改系统订单产品sku的情况，而这里amazon原始订单产品的sku则不会改变，这时候就会导致 下面的AMZHP006 问题
			// 由于eagle2 对接amazon发货时修改了参数来源，这里的$items 数组是 系统订单产品表数据的集合，所以这里不再通过sku 来查找原始订单产品，而是直接输出系统订单产品的原始订单产品id
// 			$anItem_model = AmzOrderDetail::find()->where(
// 					'AmazonOrderId=:AmazonOrderId and SellerSKU =:SellerSKU',
// 					array(':AmazonOrderId'=>$amazon_order_id ,
// 							':SellerSKU'=>$anItem['sku']	))->one();
			
// 			if ($anItem_model == null){
// 				$success = false;
// 				$message .=  "AMZHP006: Not found the amazon order item for '".$amazon_order_id ." : sku=>".$anItem['sku']." : OrderItemId=>".$anItem['order_source_order_item_id'].".";
// 				break;
// 			}else
// 				$rtn_items[] = array('ItemCode'=>$anItem_model->OrderItemId, 'ItemShipQty'=>$anItem['qty']);
			$rtn_items[] = array('ItemCode'=>$anItem['order_source_order_item_id'], 'ItemShipQty'=>$anItem['qty']);
			
		}//end of each sku
		
	// 	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"",$message,"Debug");
		\Yii::info(["Amazon", __CLASS__,__FUNCTION__,"",$message] , 'edb\user');
		
		return array('items'=>$rtn_items,
					 'success' => $success,
					 'message' => $message	);
	}//end of function
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * to get amazon merchant account information
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param N/A
	 +---------------------------------------------------------------------------------------------
	 * @return				array('merchant_id'=>array(merchant info))
	 * @description			获取amazon 帐号信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function getAmzAccountInfo(){
		$allAmazonAccount = SaasAmazonUser::model()->findall();
		foreach($allAmazonAccount as $aModel){
			$AmzAccount[$aModel->merchant_id] = $aModel->attributes;
		}
		return $AmzAccount;
	}//end of getAmzAccountInfo
	//TODO 该函数需要move到 platform模块
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * amazon 调用api记录
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $puid					erp 用户主账号id
	 * @param $merchantId			卖家 ID（amazon 受权信息）
	 * @param $callType				api 调用类型:如 GetAmazonSubmitFeedResult
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function recordAmazonCallApiAction( $puid , $merchantId , $callType){
		$date = date('Y-m-d', time());
		$log = AmazonApiCallLog::findOne(['puid'=>$puid , 'merchant_id'=>$merchantId , 'call_type'=>$callType , 'date'=>$date]);
		if( null == $log){
			$log = new AmazonApiCallLog();
			$log->puid = $puid;
			$log->merchant_id = $merchantId;
			$log->call_type = $callType;
			$log->date = $date;
			$log->count = 1;
		}else{
			$log->count = $log->count + 1;
		}
		
		$log->save(false);
}// end of recordAmazonCallApiAction

	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 amazon 账号已在小老板开通的 站点国家code
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $amazon_uid			amazon 账号id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2016/02/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function getMarketPlaceCountryCode($amazon_uid){
		$amazonMarketCol = SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid" => $amazon_uid))->all();
		$countryCodes = array();
		foreach($amazonMarketCol as $amzonMarketplace){
			$countryCodes[] = SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$amzonMarketplace->marketplace_id];
		}
		
		return $countryCodes;
	}// end of getMarketPlaceCountryCode
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 amazon MarketPlaceId 对应的 站点国家code
	 * @access static
	 * @param 	string	$MarketPlaceId
	 * @return	string	结果CountryCode，如果无对应结果则返回空字符
	 * @author	lzhl	2017-02-14	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getCountryCodeByMarketPlaceId($MarketPlaceId){
		if(empty(self::$AMAZON_MARKETPLACE_REGION_CONFIG[$MarketPlaceId]))
			return '';
		else
			return self::$AMAZON_MARKETPLACE_REGION_CONFIG[$MarketPlaceId];
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 amazon CountryCode 对应的 站点MarketPlaceId
	 * @access static
	 * @param 	string	$CountryCode
	 * @return	string	结果MarketPlaceId，如果无对应结果则返回空字符
	 * @author	lzhl	2017-02-14	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getMarketPlaceIdByCountryCode($CountryCode){
		$MARKETPLACE_REGION = self::$AMAZON_MARKETPLACE_REGION_CONFIG;
		$exist = array_search($CountryCode, $MARKETPLACE_REGION);
		if(!empty($exist))
			return $exist;
		else
			return '';
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 解绑 amazon 账号 ，删除相应记录.
	 * subdb: order ,order item ,order ship,原始订单2个表
	 * managedb:  3个队列表（统一发货，amazon发货，订单拉取） , 3个saas表
	 +---------------------------------------------------------------------------------------------
	 * @access 		static
	 +---------------------------------------------------------------------------------------------
	 * @param 		$merchantId		amazon 账号
	 +---------------------------------------------------------------------------------------------
	 * log			name			date					note
	 * @author		dzt				2016/03/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function unbind($merchantId){
		if(empty($merchantId))
			return array(false,"merchantId 不能为空");
		
		$SAU = SaasAmazonUser::findOne(['merchant_id'=>$merchantId]);
		
		if(empty($SAU))
			return array(false,"$merchantId 账号不存在");
		
		// 删除subdb数据
		$puid = $SAU->uid;

		try {
			
			$delOriginOrders = AmzOrder::find()->where(['merchant_id'=>$merchantId])->asArray()->all();
			foreach ($delOriginOrders as $oOrder){
				$delOOdIds[] = $oOrder['AmazonOrderId'];
			}
			
			$aoddCount = AmzOrderDetail::deleteAll(['AmazonOrderId'=>$delOOdIds]);// @todo 会不会因为sql超长报错
			$aodCount = AmzOrder::deleteAll(['merchant_id'=>$merchantId]);
			\Yii::info("unbind delete info puid:$puid,merchant_id:$merchantId: AmzOrderDetail:$aoddCount ,AmzOrder:$aodCount .","file");
				
			$orderSource = "amazon";
			$delOrders = OdOrder::find()->where(['order_source'=>$orderSource,'selleruserid'=>$merchantId])->asArray()->all();
			$delOdIds = array();
			foreach ($delOrders as $order){
				$delOdIds[] = $order['order_id'];
			}
			
			$odsCount = OdOrderShipped::deleteAll(['order_id'=>$delOdIds]);
			$odiCount = OdOrderItem::deleteAll(['order_id'=>$delOdIds]);
			$odCount = OdOrder::deleteAll(['order_id'=>$delOdIds]);
			\Yii::info("unbind delete info puid:$puid,merchant_id:$merchantId: od ship:$odsCount ,od items:$odiCount ,ods:$odCount .","file");
			
			// 删除managedb 数据
			
			$qssCount = QueueSyncshipped::deleteAll(['selleruserid'=>$merchantId]);
			\Yii::info("unbind delete info puid:$puid,merchant_id:$merchantId: QueueSyncshipped:$qssCount .","file");
			
			$odsqCount = AmazonOrderSubmitQueue::deleteAll(['merchant_id'=>$merchantId]);
			\Yii::info("unbind delete info puid:$puid,merchant_id:$merchantId: AmazonOrderSubmitQueue:$odsqCount .","file");
			
			// 多个market place会有多个 autosync,autosync 帮助删除 订单队列表数据
			// $SAAs = SaasAmazonAutosync::find()->where(['merchant_id'=>$merchantId])->asArray()->all();
			$SAAs = SaasAmazonAutosyncV2::find()->where(['platform_user_id'=>$merchantId])->asArray()->all();
			$autoSyncIds = array();
			foreach ($SAAs as $SAA){
				$autoSyncIds[] = $SAA['id'];
			}
			
			// $atodqCount = AmazonTempOrderidQueue::deleteAll(['saas_amazon_autosync_id'=>$autoSyncIds]);
			// \Yii::info("unbind delete info puid:$puid,merchant_id:$merchantId: AmazonTempOrderidQueue:$atodqCount .","file");
			$CountHP = AmazonTempOrderidQueueHighpriority::deleteAll(['saas_platform_autosync_id'=>$autoSyncIds]);
			\Yii::info("unbind delete info puid:$puid,merchant_id:$merchantId: AmazonTempOrderidQueueHighpriority:$CountHP .","file");

			$CountLP = AmazonTempOrderidQueueLowpriority::deleteAll(['saas_platform_autosync_id'=>$autoSyncIds]);
			\Yii::info("unbind delete info puid:$puid,merchant_id:$merchantId: AmazonTempOrderidQueueLowpriority:$CountLP .","file");
			
			// 删除market place
			$SAMCount = SaasAmazonUserMarketplace::deleteAll(['amazon_uid'=>$SAU->amazon_uid]);
			\Yii::info("unbind delete info puid:$puid,merchant_id:$merchantId: SaasAmazonUserMarketplace:$SAMCount .","file");
			
			// 删除autosync
			//$SAMCount = SaasAmazonAutosync::deleteAll(['merchant_id'=>$merchantId]);
			$SAMCount = SaasAmazonAutosyncV2::deleteAll(['platform_user_id'=>$merchantId]);
			\Yii::info("unbind delete info puid:$puid,platform_user_id:$merchantId: SaasAmazonUserMarketplace:$SAMCount .","file");
			
			$SAU->delete();
			//do for the permenant
			$SAUPt = SaasAmazonUserPt::findOne(['merchant_id'=>$merchantId]);
			$SAUPt->delete();
			
			return array(true,"");
		} catch (\Exception $e) {
			\Yii::error("unbind Exception:".print_r($e,true),"file");
			return array(false,$e->getMessage());
		}
		
	}// end of unbind

	/**
	 +---------------------------------------------------------------------------------------------
	 * amazon 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号表主键（必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=> array(is_active 是否启用 , last_time上次同步时间,message 信息 ,status 同步执行状态) 同步表的最新数据
	 *    //其中同步执行状态 为以下值'0'=>'等待同步','1'=>'已经有同步队列为他同步中','2'=>'同步成功','3'=>'同步失败','4'=>'同步完成',
	 * 					'message'=>执行详细结果
	 * 				    'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/12/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getLastOrderSyncDetail($account_key , $uid=0){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
	
		//where 没有 type ，目前只有抓取订单
		$SAAutosyncList  = SaasAmazonAutosyncV2::find()
							->where(['eagle_platform_user_id'=>$account_key])
							->andwhere(['type'=>'amzNewNotFba'])
							->orderBy(' last_finish_time desc ')
							->asArray()
							->all();
		$list = [];
		foreach($SAAutosyncList as $SAAutosync){
			$result = array();
			$result['is_active'] = $SAAutosync['status'];
			// amazon没有上次同步触发时间，只有下次同步允许开始时间$SAA_obj->next_execute_time 是由 执行nowtime + 800s组成
			// if(empty($SAAutosync['next_execute_time'])){
			// 	$result['last_time'] = 0;
			// }else{
			// 	$result['last_time'] = $SAAutosync['next_execute_time'] - 800;
			// }
			if(empty($SAAutosync['last_finish_time'])){
				$result['last_time'] = 0;
			}else{
				$result['last_time'] = $SAAutosync['last_finish_time'];
			}
			$result['message'] = $SAAutosync['err_msg'];
			$result['status'] = $SAAutosync['process_status'];//process_status ---0 没同步; 1 同步中; 2 完成同步; 3上一次执行有问题   刚好大致一样
			$mk_name = self::$AMAZON_MARKETPLACE_REGION_CONFIG[$SAAutosync['site_id']];
			$mk_name_cn = self::$COUNTRYCODE_NAME_MAP[$mk_name];
			$list[$mk_name_cn] = $result;
		}
		
		
		if (empty($list)){
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}else{
			return  ['success'=>true , 'message'=>'' , 'result'=>$list];
		}
	}// end of getLastOrderSyncDetail
	
	/**
	 * 手动更新amazon proxy的图片缓存
	 * @param int $order_id
	 */
	public static function updateOrderItemImage($order_id){
// 		手动更新amazon proxy的图片缓存
		$msg = [];
		$MARKETPLACE_List = array_flip(AmazonSyncFetchOrderApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG);
		
		$data = OdOrder::find()->where(['order_id'=>$order_id])->one();
		if(empty($data)){
			return ResultHelper::getResult(400, '', '找不到订单'.$order_id);
		}
		
		$MARKETPLACE = @$MARKETPLACE_List[$data->order_source_site_id];
		
		// dzt20181229 通过mws接口刷图 补上账号信息
		$amzUser = SaasAmazonUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid(),'merchant_id'=>$data->selleruserid])->asArray()->one();
		$amz_user_marketplace = SaasAmazonUserMarketplace::find()->where(['amazon_uid'=>$amzUser['amazon_uid'], 'marketplace_id'=>$MARKETPLACE])->asArray()->One();
		$config = [
	        'merchant_id'=>$amzUser['merchant_id'],
	        'marketplace_id'=>$amz_user_marketplace['marketplace_id'],
	        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 	        'access_key_id'=>$amz_user_marketplace['access_key_id'],
// 	        'secret_access_key'=>$amz_user_marketplace['secret_access_key'],
	        'mws_auth_token'=>$amz_user_marketplace['mws_auth_token'],
		];
		
		
		$items = $data->items;
		if(!empty($items)){
			foreach ($items as $item){
				$reqParams=array();
				$reqParams["MARKETPLACE"] = $MARKETPLACE;
				$reqParams["ASIN"] = $item->order_source_itemid;
				$reqParams["config"] = json_encode($config);
				$reqParams["purge"] = true;//是否刷新proxy 数据库缓存图片
				$timeout = 60;
				$retInfo=AmazonProxyConnectApiHelper::call_amazon_api("GetProductSmallImage",$reqParams,$timeout);
				if($retInfo['success']){
					if(!empty($retInfo['response']['SmallImageUrl'])){
						$db = OdOrderItem::getDb();
						try{
							$r = $db->createCommand()->update(
									OdOrderItem::tableName(),
									['photo_primary'=>$retInfo['response']['SmallImageUrl']],
									['order_source_itemid'=>$item->order_source_itemid]
							)->execute();
						}catch (\Exception $e){
							$msg[] = 'order_id:'.$order_id.' ,order_item_id:'.$item->order_item_id.' 保存到数据库时失败';
						}
					}else{
						$msg[] = 'order_id:'.$order_id.' ,order_item_id:'.$item->order_item_id.' has error .'.@$retInfo['response']['message'];
					}
				}else{
					$msg[] = 'order_id:'.$order_id.' ,order_item_id:'.$item->order_item_id.' call API error.';
				}
			}
		}else{
			return ResultHelper::getResult(400, '', '找不到任何需要更新图片的商品');
		}
		if(empty($msg)){
			return ResultHelper::getSuccess();
		}
		return ResultHelper::getFailed('',1,'操作失败：'.implode(' -- ', $msg));
	}
	
	/**
	 * 获取amazon_temp_orderid_queue中的错误数据
	 *
	 * @author dzt
	 */
// 	public static function warnGetOrderItemAbnormalStatus1($last_time){
// 		$connection = Yii::$app->db;
// 		$result = AmazonTempOrderidQueue::find()
// 		->where('process_status=1 and update_time <'.$last_time)->orWhere(['process_status'=>4])->asArray()->all();
		
// 		$msg = "";
		
// 		$syncIdAccountMap = AmazonSyncFetchOrderApiHelper::_getSyncIdAccountInfoMap();
		
// 		if (!empty($result)) {
// 			foreach ($result as $vs) {
// 			//检查是否活跃用户,在邮件主体中标记出来吧
// 				$mt = self::isActiveUser($vs['puid']) === false ? '非活跃用户' : '活跃用户';
// 				$syncId = $vs['saas_amazon_autosync_id'];
				
// 				if(empty($syncIdAccountMap[$syncId])){
// 					$merchantId = "unknown";
// 					$marketPlaceShort = "unknown";
// 				}else{
// 					$merchantId = $syncIdAccountMap[$syncId]["merchant_id"];
// 					$marketPlaceShort = SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$syncIdAccountMap[$syncId]["marketplace_id"]];
// 				}
				
								
// 				if($vs['process_status'] != 4){
// 					//如果是活跃用户,就先把status,改成0
// 					if ($mt == '活跃用户') {
// 						$order_id = $vs['order_id'];
// 						$update = $connection->createCommand("UPDATE amazon_temp_orderid_queue SET `process_status`=0,`error_count`=0 WHERE order_id='{$order_id}' and saas_amazon_autosync_id='{$syncId}'")->execute();
// 					}
// 				}
	
// 				$msg .= $mt . 'ItemError--Orderid:' . $vs['order_id'] . '--type:' . $vs['type'] . '--process_status:' . $vs['process_status'] . '--' . '--PUID:' . $vs['puid'] . ',最后更新时间-- ' . date("Y-m-d H:i:s",$vs['update_time']) . ',Amazon Merchant Id -- ' . $merchantId . ',Market Place Short --  '.$marketPlaceShort  . PHP_EOL;
// 			}
// 			echo $msg;
			
// 			$sendto_email = ["1241423221@qq.com"];//,"156038530@qq.com","619902089@qq.com"
// 			$subject = 'Amazon Order Items拉取问题';
// 			$result = LazadaApiHelper::sendEmail($sendto_email, $subject, $msg);
			
// // 			$result = true;
// // 			$mail = Yii::$app->mailer->compose();
// // 			$mail->setTo($sendto_email);
// // 			$mail->setSubject($subject);
// // 			$mail->setTextBody($msg);
// // 			$result = $mail->send();
// 			if ($result === false) {
// 				echo "发送邮件失败";
// 				yii::info("发送邮件失败", "file");
// 			} else {
// 				yii::info("发送邮件成功", "file");
// 				echo "发送邮件成功";
// 			}
// 			return true;
// 		} else {
// 			echo "没有的异常数据";
// 			yii::info("没有的异常数据", "file");
// 			return false;
// 		}
// 	}
	
	/**
	 * 获取amazon_temp_orderid_queue中的执行情况
	 *
	 * @author dzt
	 */
// 	public static function warnGetOrderItemAbnormalStatus2(){
// 		$connection = Yii::$app->db;
// 		$result = AmazonTempOrderidQueue::find()
// 		->where('(process_status=0 or process_status =3) and error_count<30')->count();
	
// 		$msg = "";
// // 		$result = 1200;
// 		if ($result > 1000) {
// 			$msg = 'Warning 当前有'.$result.'个订单待拉取，请留意拉取Job的情况';
// 			echo $msg;
			
// // 			$sendto_email = ["1241423221@qq.com","156038530@qq.com","619902089@qq.com"];
// // 			$subject = 'Amazon Order Items拉取问题';
// // 			$result = LazadaApiHelper::sendEmail($sendto_email, $subject, $msg);
				
			
// 			return array(false,$msg);
// 		} else {
// 			echo "Amazon Order Items 拉取正常 当前有$result 个订单item待拉取";
// 			yii::info("Amazon Order Items 拉取正常 当前有$result 个订单item待拉取", "file");
// 			return array(true,"Amazon Order Items 拉取正常 当前有$result 个订单item待拉取");
// 		}
// 	}
	
	protected static function isActiveUser($uid) {
		return true;
		// if (empty(self::$active_users)) {
			// self::$active_users = \eagle\modules\util\helpers\UserLastActionTimeHelper::getPuidArrByInterval(72);
		// }
	
		// if (in_array($uid, self::$active_users)) {
			// return true;
		// }
	
		// return false;
	}



/**
 * AmazonSetOrderShippingDateTime:被amazonshipping调用，用于设置标记发货时间。
 * 		amazon状态是shipped或者partial shipped的话，就用od_order_v2的发货时间再次标记
 *		amazon状态是unshipped的话，就用当前时间
 * @Author   willage
 * @DateTime 2016-06-23T17:20:56+0800
 * @param    [type] [od_order_v2数据表]
 * @return   [type] [rettime-跟time()一样]
 */
	public static function AmazonSetOrderShippingDateTime($objOrder){
		$rettime = time()-90;
		/**
		 * [amazon状态是shipped或者partial shipped的话，就用od_order_v2的发货时间再次标记]
		 * [amazon状态是unshipped的话，就用当前时间]
		 */
		if($objOrder->order_source_status == "Shipped" || $objOrder->order_source_status == "PartiallyShipped"){
			if(($objOrder->delivery_time != 0) && ($objOrder->delivery_time != null) ){
				$rettime = $objOrder->delivery_time;
				\yii::info("[".__FUNCTION__."]:Shipped set again the rettime = $rettime --delivery_time is not zero", "file");
			}else{
			/**
			 * [$rettime 比较update_time和fulfill_deadline，取较老的数据]
			 * @var [type]
			 */
			$rettime = ($objOrder->update_time < ($objOrder->fulfill_deadline - 24 * 3600))?$objOrder->update_time:$objOrder->fulfill_deadline;
			\yii::info("[".__FUNCTION__."]:Shipped set again the rettime = $rettime --delivery_time is zero", "file");

			}
		}
		return $rettime;
	}//end of AmazonSetOrderShippingDateTime

/**
 * AmazonDateTimeFortmat 用于转换成Amazon要求datetime格式Y-m-d\TH:i:s\Z (目前没有检测传入参数格式 )
 * @Author   willage
 * @DateTime 2016-06-23T18:17:53+0800
 * @param    [type] [datetime格式是时间戳]
 * @return   [type] [$fortmatdatetime格式是Y-m-d\TH:i:s\Z]
 */
	public static function AmazonDateTimeFortmat($dateTime){
		$tempTime = date('Y-m-d H:i:s',$dateTime);
		$dt = new \DateTime($tempTime);
		$dt->setTimezone ( new \DateTimeZone('UTC'));
		$fortmatdatetime=$dt->format("Y-m-d\TH:i:s\Z");
		return $fortmatdatetime;
	}

/**
 * [warnGetOrderItemHPAbnormalStatus1 description]
 * @Author   willage
 * @DateTime 2016-08-12T14:48:01+0800
 * @param    [type]                   $last_time [description]
 * @return   [type]                              [description]
 */
	public static function warnGetOrderItemAbnormalStatus1($last_time){
		$result = AmazonTempOrderidQueueHighpriority::find()
		->where('process_status=1 and update_time <'.$last_time)->orWhere(['process_status'=>4])->asArray()->all();
		$msg = "";
		$HP_array=AmazonTempOrderidQueueHighpriority::find()
			->select("saas_platform_autosync_id")
			->where('process_status=1 and update_time <'.$last_time)
			->orWhere(['process_status'=>4])
			->asArray()->column();
		if (!empty($result)) {
			$syncIdAccountMap = AmazonSyncFetchOrderApiHelper::_getSyncIdAccountInfoMap($HP_array);
			foreach ($result as $vs) {
			//检查是否活跃用户,在邮件主体中标记出来吧
				$mt = self::isActiveUser($vs['puid']) === false ? '非活跃用户' : '活跃用户';
				$syncId = $vs['saas_platform_autosync_id'];
				if(empty($syncIdAccountMap[$syncId])){
					$merchantId = "unknown";
					$marketPlaceShort = "unknown";
				}else{
					$merchantId = $syncIdAccountMap[$syncId]["merchant_id"];
					$marketPlaceShort = SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$syncIdAccountMap[$syncId]["marketplace_id"]];
				}
				if($vs['process_status'] != 4){
					$connection = Yii::$app->db_queue;
					//如果是活跃用户,就先把status,改成0
					if ($mt == '活跃用户') {
						$order_id = $vs['order_id'];
						$update = $connection->createCommand("UPDATE amazon_temp_orderid_queue_highpriority SET `process_status`=0,`error_count`=0 WHERE order_id='{$order_id}' and saas_platform_autosync_id='{$syncId}'")->execute();
					}
				}
				$msg .= $mt . 'ItemError--Orderid:' . $vs['order_id'] . '--type:' . $vs['type'] . '--process_status:' . $vs['process_status'] . '--' . '--PUID:' . $vs['puid'] . ',最后更新时间-- ' . date("Y-m-d H:i:s",$vs['update_time']) . ',Amazon Merchant Id -- ' . $merchantId . ',Market Place Short --  '.$marketPlaceShort  . PHP_EOL;
			}
			echo $msg;
			$sendto_email = ["619902089@qq.com"];//,"156038530@qq.com","1241423221@qq.com"
			$subject = 'Amazon Order Items拉取问题';
			$result = LazadaApiHelper::sendEmail($sendto_email, $subject, $msg);
			if ($result === false) {
				echo "发送邮件失败";
				yii::info("发送邮件失败", "file");
			} else {
				yii::info("发送邮件成功", "file");
				echo "发送邮件成功";
			}
			return true;
		} else {
			echo "没有的异常数据";
			yii::info("没有的异常数据", "file");
			return false;
		}
	}//
/**
 * [warnGetOrderItemAbnormalStatus2 description]
 * @Author   willage
 * @DateTime 2016-08-12T14:52:10+0800
 * @return   [type]                   [description]
 */
	public static function warnGetOrderItemAbnormalStatus2(){
		$connection = Yii::$app->db;
		$result = AmazonTempOrderidQueueHighpriority::find()
		->where('(process_status=0 or process_status =3) and error_count<30')->count();
		$msg = "";

		if ($result > 1000) {
			$msg = 'Warning 当前有'.$result.'个订单待拉取，请留意拉取Job的情况';
			echo $msg;
			return array(false,$msg);
		} else {
			echo "Amazon Order Items 拉取正常 当前有$result 个订单item待拉取";
			yii::info("Amazon Order Items 拉取正常 当前有$result 个订单item待拉取", "file");
			return array(true,"Amazon Order Items 拉取正常 当前有$result 个订单item待拉取");
		}
	}//

/**
 * [getAmzStoreName 通过卖家账号获取店铺名]
 * @Author   willage
 * @DateTime 2016-09-02T09:26:01+0800
 * @param    [type]                   $merchantid [description]
 * @return   [type]                               [description]
 */
	public static function getAmzStoreName($merchantid){
		$connection = Yii::$app->db;
		$result = SaasAmazonUser::find()->where("merchant_id='".$merchantid."'")->asArray()->one();
		//echo "result ".print_r($result,true)."\n";
		return $result['store_name'];
	}


	/**
	 +---------------------------------------------------------------------------------------------
	 * 保存 report listings
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2017/02/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function saveReportListings($account_info, $data){
        $puid = $account_info['uid'];
        
        if(!empty(self::$AMAZON_MARKETPLACE_REGION_CONFIG[$account_info['marketplace_id']])){
            $marketplace_short = self::$AMAZON_MARKETPLACE_REGION_CONFIG[$account_info['marketplace_id']];
        }else{
            $marketplace_short = $account_info['marketplace_id'];
        }
        
        $oneRequest = AmazonReportRequset::find()
        ->where(['merchant_id'=>$account_info['merchant_id'],'marketplace_short'=>$marketplace_short])
        ->orderBy("id desc")
        ->one();
        $batchNum = $oneRequest->report_id;
        
        $tempData = $data;
        if(empty($tempData[0])){
            $dataCols = array();
        }else{
            $dataCols = array_shift($tempData);
        }
        
        $dataMapCols = array();
        foreach ($tempData as $index => $val) {
            // dzt20170308 存在不少listing 信息的 data column数与 column数不一致，暂时没有办法处理重新对应上，跳过这些产品
            if(count($val) != count($dataCols)) {
                \Yii::error("saveReportListings columns not match,puid:".$puid.
                        ",marketplace_short:".$marketplace_short.",merchant_id:".$account_info['merchant_id'].
                        ",columns:".json_encode($dataCols).",data:".json_encode($val),"file");
                continue;
            }
            
            $dataMapCols[$index] = array();
            foreach($val as $cIndex=>$cVal){
                $dataMapCols[$index][$dataCols[$cIndex]] = $cVal;
            }
        }
        
        $batchInsertArr = array();
        $toAddColumns = array();
        $objSchema = AmazonTempListing::getTableSchema();
        foreach ($dataMapCols as $listing){
            $asin = (!empty($listing['asin1']))?$listing['asin1']:$listing['product-id'];
            if(empty($asin) || strpos("B", $asin) != 0){
                \Yii::error("saveReportListings asin not correctly:,puid:".$puid.
                        ",marketplace_short:".$marketplace_short.",merchant_id:".$account_info['merchant_id'].
                        ",listing info:".json_encode($listing),"file");
                continue;
            }
            
            
            $tempAddListing = array();
            $tempAddListing['puid'] = $puid;
            $tempAddListing['merchant_id'] = $account_info['merchant_id'];
            $tempAddListing['marketplace_short'] = $marketplace_short;
            $tempAddListing['title'] = $listing['item-name'];// 返回的title有省略，需要产品接口覆盖
            $tempAddListing['SKU'] = $listing['seller-sku'];
            $tempAddListing['ASIN'] = $asin;
            $tempAddListing['Quantity'] = empty($listing['quantity'])?0:$listing['quantity'];
            $tempAddListing['Price'] = $listing['price'];
            $tempAddListing['report_info'] = json_encode($listing);
            $tempAddListing['batch_num'] = $batchNum;
            $tempAddListing['create_time'] = TimeUtil::getNow();
            $tempAddListing['update_time'] = TimeUtil::getNow();
            
            $toAddListing = array();
            foreach ($objSchema->columnNames as $column){
                if(isset($tempAddListing[$column])){
                    $toAddListing[$column] = $tempAddListing[$column];
                    
                    if(empty($batchInsertArr)){// 抓取第一个批量插入的产品的时候，将要加的columns记下
                        $toAddColumns[] = $column;
                    }
                }
            }
            
            if (!empty($toAddListing))
                $batchInsertArr[] = $toAddListing;
        }
        
        $totalNum = count($tempData);
    	if(!empty($batchInsertArr) && !empty($toAddColumns)){
    	    $timeMS1 = TimeUtil::getCurrentTimestampMS();
    	    $insertNum = \Yii::$app->db_queue2->createCommand()->batchInsert(AmazonTempListing::tableName(), $toAddColumns, $batchInsertArr)->execute();
    	    $timeMS2 = TimeUtil::getCurrentTimestampMS();
    	    \Yii::info("saveReportListings after insert,puid:".$puid.",marketplace_short:".$marketplace_short.",merchant_id:".$account_info['merchant_id'].",totalNum=$totalNum,insertNum=$insertNum,t2_1=".($timeMS2-$timeMS1),"file");
    	    return  ['success'=>true , 'message'=>''];
    	}else{
    	    \Yii::info("saveReportListings batchInsertArr or toAddColumns is empty,puid:".$puid.",marketplace_short:".$marketplace_short.",merchant_id:".$account_info['merchant_id'].",totalNum=$totalNum,","file");
    	    return  ['success'=>false , 'message'=>'没有插入record，获取数据数量：'.$totalNum];
    	}
	}// end of saveReportListings


	public static function saveReportListingsToUserDb($account_info, $data){
		try{
			$puid = $account_info['uid'];
			echo "\n start to save Listings to DB for puid=$puid .";
			if(!empty(self::$AMAZON_MARKETPLACE_REGION_CONFIG[$account_info['marketplace_id']])){
				$marketplace_short = self::$AMAZON_MARKETPLACE_REGION_CONFIG[$account_info['marketplace_id']];
			}else{
				$marketplace_short = $account_info['marketplace_id'];
			}
			/*
			$oneRequest = AmazonReportRequset::find()
			->where(['merchant_id'=>$account_info['merchant_id'],'marketplace_short'=>$marketplace_short])
			->orderBy("id desc")
			->one();
			$batchNum = $oneRequest->report_id;
			*/
			$tempData = $data;
			if(empty($tempData[0])){
				$dataCols = array();
			}else{
				$dataCols = array_shift($tempData);
			}
			//print_r($dataCols);
			$dataMapCols = array();
			$asin_data_index = [];
			foreach ($tempData as $index => $val) {
				// dzt20170308 存在不少listing 信息的 data column数与 column数不一致，暂时没有办法处理重新对应上，跳过这些产品
				if(count($val) != count($dataCols)) {
					echo "\n saveReportListings columns not match,puid:".$puid.",marketplace_short:".$marketplace_short.",merchant_id:".$account_info['merchant_id'].",columns:".json_encode($dataCols).",data:".json_encode($val);
					continue;
				}
				
				$dataMapCols[$index] = array();
				foreach($val as $cIndex=>$cVal){
					$dataMapCols[$index][$dataCols[$cIndex]] = $cVal;
				}
				//如果存在重复的asin数据，则取库存较多的记录。
				if(!isset($asin_data_index[$dataMapCols[$index]['asin1']])){
					$asin_data_index[$dataMapCols[$index]['asin1']] = $index;
				}else{
					//echo "\n asin_data_index exist!";
					$preIndex = $asin_data_index[$dataMapCols[$index]['asin1']];
					$this_qty = (int)$dataMapCols[$index]['quantity'];
					$pre_qty = (int)$dataMapCols[$preIndex]['quantity'];
					if($this_qty > $pre_qty){
						$asin_data_index[$dataMapCols[$index]['asin1']] = $index;
						unset($dataMapCols[$preIndex]);
					}else{
						unset($dataMapCols[$index]);
					}
				}
			}
			//print_r($dataMapCols);
			$fetch_addi_info_queue_data = [];
			$count = 0;
			foreach ($dataMapCols as $listing){
				$tmp_queue_data = [];
				$asin = (!empty($listing['asin1']))?$listing['asin1']:'';
				if(empty($asin) || strpos("B", $asin) != 0){
					echo "\n saveReportListings asin not correctly:,puid:".$puid.",marketplace_short:".$marketplace_short.",merchant_id:".$account_info['merchant_id'].",listing info:".json_encode($listing);
					continue;
				}
				
				$exist_listing = AmazonListing::find()->where(['asin'=>$asin,'merchant_id'=>$account_info['merchant_id'],'marketplace_id'=>$account_info['marketplace_id']])->one();
				if(empty($exist_listing)){
					$exist_listing = new AmazonListing();
				}
				
				if(empty($exist_listing->merchant_id))
					$exist_listing->merchant_id = $account_info['merchant_id'];
				if(empty($exist_listing->marketplace_id))
					$exist_listing->marketplace_id = $account_info['marketplace_id'];
				if(empty($exist_listing->marketplace_short))
					$exist_listing->marketplace_short = $marketplace_short;
				
				$exist_listing->sku = $listing['seller-sku'];
				$exist_listing->condition = $listing['item-condition'];
				$exist_listing->stock = empty($listing['quantity'])?0:$listing['quantity'];
				$exist_listing->price = empty($listing['price'])?0:$listing['price'];
				$exist_listing->status = empty($listing['item-is-marketplace'])?'n':$listing['item-is-marketplace'];
				
				$exist_listing->asin = $asin;
				$exist_listing->product_id = empty($listing['product-id'])?NULL:$listing['product-id'];
				$exist_listing->title = $listing['item-name'];
				$exist_listing->description = $listing['item-description'];
				$exist_listing->report_info = json_encode($listing);
				$exist_listing->batch_num = empty($exist_listing->batch_num)?1:(int)$exist_listing->batch_num+1;
				$exist_listing->batch_num = (string)$exist_listing->batch_num;
				$exist_listing->create_time = TimeUtil::getNow();
				$exist_listing->update_time = TimeUtil::getNow();
				
				if(!$exist_listing->save()){
					echo "\n Listing : $asin save failed, message: \n ".print_r($exist_listing->errors);
					continue;
				}
				
				$tmp_queue_data['puid'] = $puid;
				$tmp_queue_data['merchant_id'] = $account_info['merchant_id'];
				$tmp_queue_data['marketplace_id'] = $account_info['marketplace_id'];
				$tmp_queue_data['asin'] = $asin;
				$tmp_queue_data['seller_sku'] = $exist_listing->sku;
				$tmp_queue_data['callback'] = '';
				$tmp_queue_data['create_time'] = $exist_listing->create_time;
				
				$fetch_addi_info_queue_data[] = $tmp_queue_data;
				$count++;
				echo "\n Listing : $asin save/update success.";
			}
			echo "\n end saving Listings for puid=$puid .";
			
			$insert_cnt = SQLHelper::groupInsertToDb('amazon_listing_fetch_addi_info_queue', $fetch_addi_info_queue_data,'db_queue2');
			if($insert_cnt!==$count){
				echo "\n insert to queue count($insert_cnt) <> insert to user db count($count)!";
			}
			$update = SaasAmazonAutosyncV2::updateAll(['status'=>1,'update_time'=>time(),'err_msg'=>'','err_cnt'=>0]," `type`='amzListingAddiInfo' and `platform_user_id`='".$account_info['merchant_id']."' and `site_id`='".$account_info['marketplace_id']."' ");
			//if(empty($update))
			//	echo "\n update saas_amazon_autosync_v2 failed!";
		}catch (\Exception $e) {
	        echo "\n ". print_r($e->getMessage());
	        echo "\n Exception save Listings to DB for puid=$puid, skip.";
	   	}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 查找保存AmazonTempListing 里面未处理的产品信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2017/02/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronGetListingsDetail(){
	    
        $currentQueueVersion = ConfigHelper::getGlobalConfig("getAmazonListingsDetail/QueueVersion",'NO_CACHE');
		if (empty($currentQueueVersion))
			$currentQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$queueVersion))
			self::$queueVersion = $currentQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$queueVersion <> $currentQueueVersion){
			exit("Version new $currentQueueVersion , this job ver ".self::$queueVersion." exits for using new version $currentQueueVersion.");
		}
	    
		$hasGotRecord=false; 
		
		$query = AmazonTempListing::find()
		->select('merchant_id,marketplace_short,batch_num')
		->where(['is_get_prod_info'=>[0,3]])// 0等待获取 1正在获取 2获取完成 3上次获取失败
// 		->andWhere(['marketplace_short'=>['US']])
// 		->andWhere('update_time < from_unixtime(1488124800)')
// 		->andWhere('puid=2561')
		->andWhere("err_times < 10");
		
		$targetListingGroups = $query->groupBy('merchant_id,marketplace_short,batch_num')->orderBy('update_time asc')->asArray()->all();
		
		echo PHP_EOL."count group ".count($targetListingGroups).' listings need to get info ;'.PHP_EOL.'start to foreach pending listings';
		foreach ($targetListingGroups as $group){
		    
		    $timeMS1=TimeUtil::getCurrentTimestampMS();
		    $nowTime = time();
		    $nowTimeStr = TimeUtil::getNow();
		    $timeMS01=TimeUtil::getCurrentTimestampMS();
		    
		    $batchNum = $group['batch_num'];
		    $merchantId = $group['merchant_id'];
		    $tempMapMarketplace = array_flip(self::$AMAZON_MARKETPLACE_REGION_CONFIG);
		    if(!empty($tempMapMarketplace[$group['marketplace_short']])){
		        $marketplace_id = $tempMapMarketplace[$group['marketplace_short']];
		    }else{
		        $marketplace_id = $group['marketplace_short'];
		    }
		    
		    $sql = "SELECT *
					FROM  `saas_amazon_user` AS su
					LEFT JOIN saas_amazon_user_marketplace AS sm ON su.amazon_uid = sm.amazon_uid
					WHERE su.merchant_id='". $group['merchant_id'] ."' and sm.marketplace_id='".$marketplace_id."' ";
		    
		    $command = Yii::$app->db->createCommand($sql);
		    $dataReader = $command->query();
		    $accCount = $dataReader->count();
		    if(empty($accCount)){
		        Yii::error("merchant_id:".$group['merchant_id'].",marketplace_id:".$marketplace_id." not exist.","file");
		        $updateInfo = array();
		        $updateInfo['is_get_prod_info'] = 4;//4 异常情况，不需要重试，等待it人工分析
		        $updateInfo['update_time'] = TimeUtil::getNow();
		        $updateInfo['err_msg'] =  "merchant_id:".$group['merchant_id'].",marketplace_id:".$marketplace_id." not exist.";
		        
		        AmazonTempListing::updateAll($updateInfo,['merchant_id'=>$merchantId,'marketplace_short'=>$group['marketplace_short'],'batch_num'=>$batchNum]);
		        continue;
		    }
		    $account_info = $dataReader->read();
		    
		    $tgListings = AmazonTempListing::find()
		    ->where('(is_get_prod_info=0 or is_get_prod_info =3) and err_times<10')
		    ->andWhere(['batch_num'=>$batchNum,'merchant_id'=>$merchantId,'marketplace_short'=>$group['marketplace_short']])
		    ->orderBy('update_time asc')->limit(5)->all();// 5个一次
		    
		    if(count($tgListings)>0){
		        $hasGotRecord=true;  // 抢到记录
		        $targetIds = array();
		        $timeMS02=TimeUtil::getCurrentTimestampMS();
		        
		        foreach($tgListings as $tgListing){
		            // 判断是否真的抢到待处理订单
		            if(!array_key_exists($tgListing->id, $targetIds)){
// 		                $tgListing->is_get_prod_info = 1;
// 		                $tgListing->update_time = $nowTimeStr;
// 		                $hasGot = $tgListing->update(false);
// 		                if($hasGot == 0) continue;
		                
		                $command = Yii::$app->db_queue2->createCommand("update amazon_temp_listing set is_get_prod_info=1,update_time='$nowTimeStr' where id =". $tgListing->id." and (is_get_prod_info=0 or is_get_prod_info=3) ") ;
		                $affectRows = $command->execute();
		                if ($affectRows <= 0)	{
		                    $timeMS03=TimeUtil::getCurrentTimestampMS();
		                    echo 'cronGetListingsDetail skip,id:'.$tgListing->id.',puid:'.$tgListing->puid.',merchant_id:'.$tgListing->merchant_id.',marketplace_short:'.$targetListing->marketplace_short.",t03_02=".($timeMS03-$timeMS02)." \n";
		                    continue; //抢不到---如果是多进程的话，有抢不到的情况
		                }
		    
		                $targetIds[$tgListing->id] = $tgListing->SKU;
		            }
		        }
		        
		        // 万一抢不到，下一组
		        if(empty($targetIds)) continue;
		         
		        echo "group:merchant_id:".$merchantId.",marketplace_short:".$group['marketplace_short'].",batch_num:".$batchNum." count:".count($targetIds)." \n";
		        Yii::info("cronGetListingsDetail -- group:merchant_id:".$merchantId.",marketplace_short:".$group['marketplace_short'].",batch_num:".$batchNum." count:".count($targetIds),"file");
		         
		        $timeMS03=TimeUtil::getCurrentTimestampMS();
		        echo "cronGetListingsDetail targetIds=".implode(',', array_keys($targetIds)).",t02_01=".($timeMS02-$timeMS01).",t03_02=".($timeMS03-$timeMS02).",t03_01=".($timeMS03-$timeMS01)." \n";
		         
		        $idList = array();
		        foreach ($targetIds as $tgListingId=>$sellerSku){
		            $idList[] = $sellerSku;
		        }
		        
		        $timeMS2=TimeUtil::getCurrentTimestampMS();
		        list($ret,$productsInfo) = self::getListingsDetail($account_info,$idList,"B");
		        $timeMS3=TimeUtil::getCurrentTimestampMS();
		         
		        echo "cronGetListingsDetail targetIds=".implode(',', array_keys($targetIds))." after getListingsDetail \n";
		       
		        if ($ret===false) {
		            foreach ($targetIds as $tgListingId=>$sellerSku){
		                $tmpTgListing = AmazonTempListing::findOne(['id'=>$tgListingId]);
		                if(empty($tmpTgListing))continue;
		                $tmpTgListing->is_get_prod_info = 3;
		                $tmpTgListing->err_times = $tmpTgListing->err_times + 1;
		                $tmpTgListing->err_msg = $productsInfo;
		                $tmpTgListing->update_time = TimeUtil::getNow();
		                $tmpTgListing->save(false);
		            }
		            
		            echo "cronGetListingsDetail targetIds=".implode(',', array_keys($targetIds))." getListingsDetail fail:$productsInfo \n";
		            continue;// 下一组
		        }
		        
		        foreach ($targetIds as $tgListingId=>$sellerSku){
					$timeMS4=TimeUtil::getCurrentTimestampMS();
					
		            $tmpTgListing = AmazonTempListing::findOne(['id'=>$tgListingId]);
		            
		            if(empty($productsInfo[$sellerSku])){
		                $tmpTgListing->is_get_prod_info = 3;
		                $tmpTgListing->err_times = $tmpTgListing->err_times + 1;
		                $tmpTgListing->err_msg = "get no product info.";
		                $tmpTgListing->update_time = TimeUtil::getNow();
		                $tmpTgListing->save(false);
		                continue;
		            }
		            
		            if(empty($productsInfo[$sellerSku]['Products'])){
		                if(!empty($productsInfo[$sellerSku]['Error'])){
		                    $tmpTgListing->err_msg = $productsInfo[$sellerSku]['Error']['Message'];
		                }else{
		                    // 有时status：Success但没有返回产品信息
		                    $tmpTgListing->err_msg = "get no product info 2."."status:".$productsInfo[$sellerSku]['status'];
		                }
		                
		                $tmpTgListing->is_get_prod_info = 3;
		                $tmpTgListing->err_times = $tmpTgListing->err_times + 1;
		                $tmpTgListing->update_time = TimeUtil::getNow();
		                $tmpTgListing->save(false);
		                continue;
		            }
		            
		            $listingInfo = $productsInfo[$sellerSku]['Products'][0];
		            
		            // http://ecx.images-amazon.com/images/I/41tA8NnAO8L._SL75_.jpg
		            $imgUrl = $listingInfo['AttributeSets']['Any'][0]['ns2_SmallImage']['ns2_URL'];
		            
		            $tmpTgListing->title = $listingInfo['AttributeSets']['Any'][0]['ns2_Title'];
		            $tmpTgListing->ASIN = $listingInfo['Identifiers']['MarketplaceASIN']['ASIN'];
		            $tmpTgListing->image_url = str_replace("75_.jpg","160_.jpg",$imgUrl);
		            $tmpTgListing->prod_info = json_encode($listingInfo);
		            
		            	
		            //5. after sync is ok,set the order item of the queue
		            $tmpTgListing->is_get_prod_info = 2;
		            $tmpTgListing->update_time = TimeUtil::getNow();
		            $tmpTgListing->err_times = 0;
		            $tmpTgListing->err_msg = "";
		            $tmpTgListing->save(false);
		            	
					$timeMS5=TimeUtil::getCurrentTimestampMS();	
					
					Yii::info("cronGetListingsDetail puid:".$tmpTgListing->puid.
		                    ",group:group:merchant_id:".$merchantId.",marketplace_short:".$group['marketplace_short'].",batch_num:".$batchNum.
		                    ",t02_01=".($timeMS02-$timeMS01).",t03_02=".($timeMS03-$timeMS02).",t03_01=".($timeMS03-$timeMS01).",t2_1=".($timeMS2-$timeMS1).
		                    ",t3_2=".($timeMS3-$timeMS2).",t4_3=".($timeMS4-$timeMS3).",t5_4=".($timeMS5-$timeMS4)
							,"file");
							
		        }// end of group orders
		    }
		    
		    
		}
		
		return $hasGotRecord;
		
	}//end of cronGetListingsDetail
	

	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 查找保存AmazonTempListing 里面未处理的产品信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2017/02/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getListingsDetail($account_info, $idList, $invoke_type='B'){
	    $ret = ['success'=>true,'message'=>''];
	    $action = 'GetMatchingProductForId2';
	    $idType = 'SellerSKU';
	    try{
	        $get_params = array();
	        $config=[
                'merchant_id'=>$account_info['merchant_id'],
                'marketplace_id'=>$account_info['marketplace_id'],
	                // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
//                 'access_key_id'=>$account_info['access_key_id'],
//                 'secret_access_key'=>$account_info['secret_access_key'],
                'mws_auth_token'=>$account_info['mws_auth_token'],
	        ];
	        $get_params['config'] = json_encode($config);
	        
	        $get_params["idType"] = $idType;// ASIN、GCID、SellerSKU、UPC、EAN、ISBN 和 JAN。
	        $get_params["idList"] = json_encode($idList);
	        $get_params["return_type"] = 'json';
	        
	        
	        if($invoke_type=='W'){
	            $journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array(
	                    $account_info['uid'],$account_info['merchant_id'],$account_info['marketplace_id'],$idList));
	        }
	        if($invoke_type=='B'){
	            echo '\n get_params return :'.print_r($get_params,true);
	        }
	        
	        $result = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params);
	        
	        if($invoke_type=='B'){
	            echo '\n proxy return :'.print_r($result,true);
	        }
	        
	        $result = is_array($result)?$result:json_decode($result,true);
	        $response = empty($result['response'])?[]:$result['response'];
	        //var_dump($response);
	        if(!empty($response['success']) && !empty($response['product']) ){
				$rtnProds = array();
	            foreach($response['product'] as $product){
					$rtnProds[$product['Id']] = $product;
				}
				
	            $ret['message'] = $rtnProds;
	        } else {
	            $ret['success'] = false;
	            $ret['message'] = $response['message'];
	        }
	         
	        if($invoke_type=='W' && isset($journal_id)){
	            SysLogHelper::InvokeJrn_UpdateResult($journal_id, $ret);
	        }
	    }catch (\Exception $e) {
	        $ret = ['success'=>false , 'message'=> $e->getMessage()];
	    }
	    
	    return array($ret['success'],$ret['message']);
	}// end of getListingsDetail
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 查找保存产品的目录信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2017/02/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function conGetAmzProdCatInfo(){
	    $currentQueueVersion = ConfigHelper::getGlobalConfig("getAmzProdCatInfo/QueueVersion",'NO_CACHE');
	    if (empty($currentQueueVersion))
	        $currentQueueVersion = 0;
	    
	    //如果自己还没有定义，去使用global config来初始化自己
	    if (empty(self::$queueVersion))
	        self::$queueVersion = $currentQueueVersion;
	    	
	    //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
	    if (self::$queueVersion <> $currentQueueVersion){
	        exit("Version new $currentQueueVersion , this job ver ".self::$queueVersion." exits for using new version $currentQueueVersion.");
	    }
	    
	    $hasGotRecord = false;
	    $totalTimeMS1 = TimeUtil::getCurrentTimestampMS();
	    
	    $query = AmazonTempListing::find()
	    ->where(['is_get_prod_info'=>[2,7]])// 2等待获取 5正在获取 6获取完成 7上次获取失败
// 	    ->andWhere("id=150")
// 	    ->andWhere(['marketplace_short'=>["DE"]])// "US","UK","FR","CA",
	    ->andWhere("err_times < 10");
	    
	    $targetListings = $query->orderBy('update_time asc')->limit(300)->all();
	    
	    $totalTimeMS2 = TimeUtil::getCurrentTimestampMS();
	    
	    echo PHP_EOL."conGetAmzProdCatInfo count  ".count($targetListings).' listings need to get cat info ;'.PHP_EOL.'start to foreach pending listings'.PHP_EOL;
	    echo "totalT2_1=".($totalTimeMS2-$totalTimeMS1)." \n";
	    Yii::info("conGetAmzProdCatInfo -- There are ".count($targetListings)." listings need to get cat info ,totalT2_1=".($totalTimeMS2-$totalTimeMS1),"file");
	    
	    foreach ($targetListings as $targetListing){
	        $timeMS01=TimeUtil::getCurrentTimestampMS();
	        $nowTimeStr = TimeUtil::getNow();
// 	        $targetListing->is_get_prod_info = 5;
// 	        $targetListing->update_time = $nowTimeStr;
// 	        $affectRows = $targetListing->update(false);
	        
	        $command = Yii::$app->db_queue2->createCommand("update amazon_temp_listing set is_get_prod_info=5,update_time='$nowTimeStr' where id =". $targetListing->id." and (is_get_prod_info=2 or is_get_prod_info=7) ") ;
	        $affectRows = $command->execute();
	        if ($affectRows <= 0)	{
	            $timeMS02=TimeUtil::getCurrentTimestampMS();
	            echo 'conGetAmzProdCatInfo skip,id:'.$targetListing->id.',puid:'.$targetListing->puid.',merchant_id:'.$targetListing->merchant_id.',marketplace_short:'.$targetListing->marketplace_short.',t02_01='.($timeMS02-$timeMS01)." \n";
	            continue; //抢不到---如果是多进程的话，有抢不到的情况
	        }
	        
	        $timeMS02=TimeUtil::getCurrentTimestampMS();
	        $targetListing = AmazonTempListing::findOne($targetListing->id);
	        $timeMS03=TimeUtil::getCurrentTimestampMS();
	        
	        echo "conGetAmzProdCatInfo start to get cat info queueid=".$targetListing->id.",merchant_id=".$targetListing->merchant_id.",".',marketplace_short:'.$targetListing->marketplace_short.',nowTime:'.TimeUtil::getNow()." \n";
	        
	        $hasGotRecord=true;  // 抢到记录
	        
	        $prodInfo = json_decode($targetListing->prod_info,true);
	        if(empty($prodInfo)){// prod_info 信息为空 或者 'null' 获取产品信息有问题，reset成重新拉取产品信息 
	            $targetListing->is_get_prod_info = 0;
	            $targetListing->err_times = 0;
	            $targetListing->err_msg = '';
	            $targetListing->update_time = TimeUtil::getNow();
	            $targetListing->save(false);
	            continue;
	        }
	        
	        $allCatStr = array();
	        $isGet = false;
	        $errMsg = '';
	        $hasNoNumeric = true;
	        
	        if(empty($prodInfo['SalesRankings'])){// 未知原因，获取产品信息时候SalesRankings信息为空
// 	            $targetListing->is_get_prod_info = 8;// 停止重试it人工分析，或者用aws再获取产品信息的时候获取目录信息
// 	            $targetListing->err_msg = "SalesRankings is empty.";
// 	            $targetListing->update_time = TimeUtil::getNow();
// 	            $targetListing->save(false);
// 	            continue;

	            $hasNoNumeric = false;
	            
	            // 使用mws 接口GetProductCategoriesForSKU 获取目录信息
	            list($ret, $catsInfo) = self::getProductCategoriesForSKU($targetListing);
	            if($ret){
	                $isGet = true;
	                $allCatStr = $catsInfo;
	            }else{
	                if($catsInfo == 'continue'){
	                    continue;
	                }
	                
	                $errMsg = $catsInfo;
	            }
	            
	        }else{
	            $salesRanks = $prodInfo['SalesRankings']['SalesRank'];
	            foreach ($salesRanks as $salesRank){
	                // 有时会有ce_display_on_website 这样的值，但是接口获取不到这些值的browse node信息
	                if(is_numeric($salesRank["ProductCategoryId"])){
	                    $hasNoNumeric = false;// 如果目录只有一个，而且非数字，应该停止重试了，或者用aws再获取产品信息的时候获取目录信息
	                    list($ret, $catStr) = self::getBrowseNode($targetListing->marketplace_short, $salesRank["ProductCategoryId"], 'B');
	                    if($ret){
	                        $isGet = true;
	                        $allCatStr[] = $catStr;
	                    }else{
	                        $errMsg = $catStr;
	                    }
	                }
	            }
	        }
	        
// 	        echo "conGetAmzProdCatInfo queueid=".$targetListing->id.",get all cats:".print_r($allCatStr)." \n";
	        
	        $timeMS04=TimeUtil::getCurrentTimestampMS();
	        
	        if($isGet){
	            $targetListing->cat_str = implode('; ', $allCatStr);
	            $targetListing->is_get_prod_info = 6;
	            $targetListing->err_times = 0;
	            $targetListing->err_msg = '';
	        }else{
	            if($hasNoNumeric){
	                // 这里也可以用self::getProductCategoriesForSKU 获取目录
// 	                $targetListing->is_get_prod_info = 8;// 停止重试it人工分析，或者用aws再获取产品信息的时候获取目录信息
// 	                $errMsg = "Can not get numeric category id.";
// 	                $targetListing->err_msg = $errMsg;
	                
	                // 使用mws 接口GetProductCategoriesForSKU 获取目录信息
	                list($ret, $catsInfo) = self::getProductCategoriesForSKU($targetListing);
	                if($ret){
                        $targetListing->cat_str = implode('; ', $catsInfo);
                        $targetListing->is_get_prod_info = 6;
                        $targetListing->err_times = 0;
                        $targetListing->err_msg = '';
	                }else{
	                    if($catsInfo == 'continue'){
	                        continue;
	                    }
	                    
	                    $targetListing->is_get_prod_info = 7;
	                    $targetListing->err_times = $targetListing->err_times + 1;
	                    $targetListing->err_msg = $catsInfo;
	                }
	                
	            }else{
	                $targetListing->is_get_prod_info = 7;
	                $targetListing->err_times = $targetListing->err_times + 1;
	                $targetListing->err_msg = $errMsg;
	            }
	        }
	        
	        $targetListing->update_time = TimeUtil::getNow();
	        $targetListing->save(false);
	        
	        $timeMS05=TimeUtil::getCurrentTimestampMS();
	        
	        echo "conGetAmzProdCatInfo get cat info done.queueid=".$targetListing->id." $errMsg,merchant_id=".$targetListing->merchant_id.",".',marketplace_short:'.$targetListing->marketplace_short.
	        ",t02_01=".($timeMS02-$timeMS01).",t03_02=".($timeMS03-$timeMS02).",t04_03=".($timeMS04-$timeMS03).
	        ",t05_04=".($timeMS05-$timeMS04).",t05_01=".($timeMS05-$timeMS01)." \n";
	        
	        Yii::info("conGetAmzProdCatInfo queueid=".$targetListing->id.",merchant_id=".$targetListing->merchant_id.",".',marketplace_short:'.$targetListing->marketplace_short.
            ",t02_01=".($timeMS02-$timeMS01).",t03_02=".($timeMS03-$timeMS02).",t04_03=".($timeMS04-$timeMS03).
            ",t05_04=".($timeMS05-$timeMS04).",t05_01=".($timeMS05-$timeMS01),"file");
	    }
	    
	    
	    return $hasGotRecord;
	}// end of conGetAmzProdCatInfo
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 查找保存产品的目录信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2017/02/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getBrowseNode($marketplace_short, $browseNodeId, $invoke_type='B'){
	    $catInfo = AmazonTempCategoryInfo::findOne(['marketplace_short'=>$marketplace_short,'BrowseNodeId'=>$browseNodeId]);
	    if(!empty($catInfo) && !empty($catInfo->cat_info)){
	        $catStr = self::getCatStr(json_decode($catInfo->cat_info));
	        return array(true,$catStr);
	    }
	    
	    try{
	        $AMAZON_REGION_MARKETPLACE_CONFIG = array_flip(self::$AMAZON_MARKETPLACE_REGION_CONFIG);
	        $marketplace_id = $AMAZON_REGION_MARKETPLACE_CONFIG[$marketplace_short];
	        $region = self::$AWS_DOMAIN_CONFIG[$marketplace_short];
            $obj = new GetBrowseNodeApiHelper(self::$aws_public_key, self::$aws_private_key, $region);
            
            if($invoke_type=='W'){
                $journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array(
                        $marketplace_short,$browseNodeId));
            }
            
            if($invoke_type=='B'){
                echo "\ngetNode:$browseNodeId,$marketplace_short,$marketplace_id,nowTime:".TimeUtil::getNow().PHP_EOL;
            }
            
            $BNode = $obj->getNode($browseNodeId);
           
            if($invoke_type=='B'){
                echo "\nget_params return :".print_r($BNode,true);
            }
	    
	        if($invoke_type=='W' && isset($journal_id)){
	            SysLogHelper::InvokeJrn_UpdateResult($journal_id, $BNode);
	        }
	        
	        if(empty($catInfo)){
	            $catInfo = new AmazonTempCategoryInfo();
	            $catInfo->BrowseNodeId = $browseNodeId;
	            $catInfo->marketplace_short = $marketplace_short;
	            $catInfo->marketplace_id = $marketplace_id;
	            $catInfo->create_time = TimeUtil::getNow();;
	        }
	        
	        if(!empty($BNode)){
	            $catInfo->cat_info = json_encode($BNode);
	            $catInfo->is_get_info = 2;
	            $catInfo->err_times = 0;
	            $catInfo->err_msg = '';
	            $catInfo->update_time = TimeUtil::getNow();;
	            $catInfo->save();
	            
	            if($invoke_type=='B'){
	                echo "\ngetNode:".$catInfo->BrowseNodeId.",".$catInfo->marketplace_short.",".$catInfo->marketplace_id.",nowTime:".TimeUtil::getNow().PHP_EOL;
	            }
	            
	            $catStr = self::getCatStr(json_decode($catInfo->cat_info));
	            return array(true,$catStr);
	        }else{
	            $catInfo->is_get_info = 3;
	            $catInfo->err_times = $catInfo->err_times + 1;
	            $catInfo->err_msg = 'Can not get BrowseNode info.';
	            $catInfo->update_time = TimeUtil::getNow();;
	            $catInfo->save();
	            
	            return array(false,"Can not get BrowseNode info.");
	        }
	        
	    }catch (\Exception $e) {
	        if($invoke_type=='B'){
	            echo "\nException file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage();
	        }else{
	            \Yii::error("Exception file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage(),"file");
	        }
	        
	        return array(false,$e->getMessage());
	    }
	    
	}// end of getBrowseNode
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 组织目录信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2017/02/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function getCatStr($bnObj){
	    $catStr = '';
	    
	    $nowNode = $bnObj->BrowseNodes->BrowseNode;
	    $catStr = $nowNode->Name;
	    $limit = 100;
	    $count = 0;
	    while(!empty($nowNode->Ancestors) && $count < $limit){
	        $count++;
	        $nowNode = $nowNode->Ancestors->BrowseNode;
	        if(!empty($nowNode->IsCategoryRoot)) continue;
	        
	        $catStr = $nowNode->Name.">".$catStr;
	    }
	    
	    return $catStr;
	}// end of getCatStr
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 查找保存产品的目录信息 by mws
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2017/03/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getProductCategoriesForSKU($targetListing, $invoke_type='B'){
	    $ret = ['success'=>true,'message'=>''];
	    
	    $AMAZON_REGION_MARKETPLACE_CONFIG = array_flip(self::$AMAZON_MARKETPLACE_REGION_CONFIG);
	    $marketplace_id = $AMAZON_REGION_MARKETPLACE_CONFIG[$targetListing->marketplace_short];
	    $sql = "SELECT *
					FROM  `saas_amazon_user` AS su
					LEFT JOIN saas_amazon_user_marketplace AS sm ON su.amazon_uid = sm.amazon_uid
					WHERE su.merchant_id='". $targetListing->merchant_id ."' and sm.marketplace_id='".$marketplace_id."' ";
	    
	    $command = Yii::$app->db->createCommand($sql);
	    $dataReader = $command->query();
	    $accCount = $dataReader->count();
	    if(empty($accCount)){
	        Yii::error("merchant_id:".$group['merchant_id'].",marketplace_id:".$marketplace_id." not exist.","file");
	        
	        $targetListing->is_get_prod_info = 8;
	        $targetListing->update_time = TimeUtil::getNow();
	        $targetListing->err_msg = "merchant_id:".$row['merchant_id'].",marketplace_id:".$marketplace_id." not exist.";
	        $targetListing->save(false);
	        
	        return array(false,"continue");
	    }
	    
	    $account_info = $dataReader->read();
	    try{
	        $action = "GetProductCategoriesForSKU";
	        $get_params = array();
	        $config=[
                'merchant_id'=>$account_info['merchant_id'],
                'marketplace_id'=>$account_info['marketplace_id'],
	                // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
//                 'access_key_id'=>$account_info['access_key_id'],
//                 'secret_access_key'=>$account_info['secret_access_key'],
                'mws_auth_token'=>$account_info['mws_auth_token'],
	        ];
	        
	        $get_params['config'] = json_encode($config);
	        if(!empty($targetListing->SKU))
	        	$get_params["SellerSKU"] = $targetListing->SKU;
	        elseif(!empty($targetListing->sku))
	        	$get_params["SellerSKU"] = $targetListing->sku;
	        else 
	        	$get_params["SellerSKU"]='';
	        $get_params["return_type"] = 'json';
	         
	        if($invoke_type=='W'){
	            $journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array(
	                    $account_info['uid'],$account_info['merchant_id'],$account_info['marketplace_id'],$targetListing->id));
	        }
	        
	        if($invoke_type=='B'){
	            echo '\n get_params return :'.print_r($get_params,true);
	        }
	         
	        $result = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params);
	         
	        if($invoke_type=='B'){
	            echo '\n proxy return :'.print_r($result,true);
	        }
	         
	        $result = is_array($result)?$result:json_decode($result,true);
	        $response = empty($result['response'])?[]:$result['response'];
	        //var_dump($response);
	        if(!empty($response['success']) && !empty($response['BrowseNodes']) ){
	            $allCatStr = array();
    	        foreach ($response["BrowseNodes"] as $node){
                    $inputNode = array();
                    $inputNode['BrowseNodes'] = array();
                    $inputNode['BrowseNodes']['BrowseNode'] = $node;
                    
                    // 这种方法返回的 root Category 由于没有字段 IsCategoryRoot 判断过滤，所以暂时没办法去除 @todo 后面可以收集root Category 的id 去避免
                    $allCatStr[] = AmazonApiHelper::getCatStr(json_decode(json_encode($inputNode)));
                }
                // 由于调用这个接口都是没有sales rank 这些listing才进来，返回就不保存到 AmazonTempCategoryInfo 里面了，保存对后面查找帮助不多。@todo 后面确实有需要再保存
                
                $ret['message'] = $allCatStr;
	        } else {
	            $ret['success'] =false;
	            $ret['message'] .= "GetProductCategoriesForSKU 失败：".$result['message'];
	        }
	    
	        if($invoke_type=='W' && isset($journal_id)){
	            SysLogHelper::InvokeJrn_UpdateResult($journal_id, $ret);
	        }
	    }catch (\Exception $e) {
	        if($invoke_type=='B'){
	            echo "\nException file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage();
	        }else{
	            \Yii::error("Exception file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage(),"file");
	        }
	        
	        $ret['success'] =false;
	        $ret['message'] = $e->getMessage();
	    }
	    
	    return array($ret['success'],$ret['message']);
	}
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 搬取amazon_temp_listing 拉取完成的产品到 显示表
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2017/03/06				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronMoveTempListingToTempListingSite(){
	    $currentQueueVersion = ConfigHelper::getGlobalConfig("getAmazonListingsDetail/QueueVersion",'NO_CACHE');
	    if (empty($currentQueueVersion))
	        $currentQueueVersion = 0;
	     
	    //如果自己还没有定义，去使用global config来初始化自己
	    if (empty(self::$queueVersion))
	        self::$queueVersion = $currentQueueVersion;
	    
	    //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
	    if (self::$queueVersion <> $currentQueueVersion){
	        exit("Version new $currentQueueVersion , this job ver ".self::$queueVersion." exits for using new version $currentQueueVersion.");
	    }
	     
	    $hasGotRecord = false;
	    
	    $totalTimeMS1 = TimeUtil::getCurrentTimestampMS();
	    
	    $tobeMove = AmazonTempListing::find()->where(['is_get_prod_info'=>6])
// 	    ->andWhere(['marketplace_short'=>"US"])// 先搬了美国的
	    ->orderBy('update_time asc')
	    ->limit(1000)
// 	    ->limit(5)
	    ->all();
	    
	    $totalTimeMS2 = TimeUtil::getCurrentTimestampMS();
	    
	    echo PHP_EOL."cronMoveTempListingToTempListingSite count  ".count($tobeMove).' listings need to move ;'.PHP_EOL.'start to foreach pending listings'.PHP_EOL;
	    echo "totalT2_1=".($totalTimeMS2-$totalTimeMS1)." \n";
	    
	    foreach ($tobeMove as $listing){
	        $newListing = false;
	        
	        switch (strtoupper($listing->marketplace_short)){
	            case 'US':
	                $newListing = new AmazonTempListingUs();
	                break;
                case 'UK':
                case 'CA':
                case 'MX':
                    $newListing = new AmazonTempListingOther();
                    break;
                case 'FR':
                case 'DE':
                case 'IT':
                case 'ES':
                    $newListing = new AmazonTempListingEu();
                    break;
	            default:
	                break;
	        }
	        
	        if(empty($newListing)){
	            echo PHP_EOL."no config site ID:".$listing->id.".";
	            $listing->update_time = TimeUtil::getNow();
	            $listing->save();
	            continue;
	        }
	        
	        $hasGotRecord = true;
	        
	        $newListing->attributes = $listing->attributes;
	        if(empty($newListing->batch_num))
	            $newListing->batch_num = $newListing->merchant_id;
	        
	        // 搬产品的时候顺便加上 抽出第一级目录逻辑
	        $catInfo = explode(">", $listing->cat_str);
	        $newListing->first_lv_cat = $catInfo[0];
	        
	        $isSave = $newListing->save();
	        if(!$isSave){
	            echo PHP_EOL."moving AmazonTempListing ID:".$listing->id.",save errors:".print_r($newListing->errors,true);
	        }else{
	            
	            $isDelete = $listing->delete();
	            if(empty($isDelete)){
	                echo PHP_EOL."AmazonTempListing ID:".$listing->id." not deleted.";
	            }else{
	                echo PHP_EOL."AmazonTempListing ID:".$listing->id." done moving to ".$listing->marketplace_short.":".$newListing->id.".";
	            }
	        }
	    }
	    
	    
	    return $hasGotRecord;
	}
	
######################################    用户 amazon listing 拉取 start // lzhl 2017-07    ######################################

	/**
	 * 查找保存 amazon_listing_fetch_addi_info_queue 里面未处理的产品信息
	 * @access static
	 * @param
	 * @return
	 * log			name		date			note
	 * @author		lzhl		2017/07/11		copy from dzt cronGetListingsDetail()
	 **/
	public static function cronGetListingsDetailForUser(){
			
		$currentQueueVersion = ConfigHelper::getGlobalConfig("cronGetListingsDetailForUser/QueueVersion",'NO_CACHE');
		if (empty($currentQueueVersion))
			$currentQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$queueVersion))
			self::$queueVersion = $currentQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$queueVersion <> $currentQueueVersion){
			exit("Version new $currentQueueVersion , this job ver ".self::$queueVersion." exits for using new version $currentQueueVersion.");
		}
	
		//select 可以进行详细信息同步的user account
		$sql = "SELECT *
				FROM  `saas_amazon_user` AS su
				LEFT JOIN saas_amazon_user_marketplace AS sm ON su.amazon_uid = sm.amazon_uid
				LEFT JOIN saas_amazon_autosync_v2 AS sy ON su.amazon_uid = sy.eagle_platform_user_id
				WHERE su.is_active =1 and sy.type='amzListingAddiInfo' and sy.status=1 and sm.marketplace_id = sy.site_id
				and (sy.last_finish_time IS NULL or sy.last_finish_time<".time()." or sy.next_execute_time IS NULL or sy.next_execute_time<".time().")
				order by sy.next_execute_time ASC
				";
		$command = Yii::$app->db->createCommand($sql);
		$dataReader=$command->query();
	
		echo "\n start to foreach pending accounts get listing addi info;";
	
		while(($row=$dataReader->read())!==false) {
			//select pending fetch listing
			$limit = 5;//拉取商品detail的api一次最多支持5个asin
			$this_process_status = 0;
			$this_status = 0;
			$get_pending_cnt = 0;
			$this_err_cnt = empty($row['err_cnt'])?0:(int)$row['err_cnt'];
			$this_err_msg = @$row['err_msg'];
				
			$puid = $row['uid'];
			$merchant_id = $row['merchant_id'];
			$marketplace_id = $row['marketplace_id'];
				
			/*避免listing太多或者响应时间长，改为每5条记录保存到user表一次*/
			//$listingDetailInfos = [];//需要update到user库的listing信息
			//$parentAsins = [];//获取到的listing信息里面，是有有listing存在parent asin，存低。
				
			while ($limit==5){
				$pendings = AmazonListingFetchAddiInfoQueue::find()->where(['puid'=>$puid,'merchant_id'=>$merchant_id,'marketplace_id'=>$marketplace_id])
				->andWhere(" `process`=0 or (`process`=1 and `process_status`=2 and `err_cnt`<10 ) ")
				->OrderBy(" `priority`,`create_time`,`err_cnt` ASC ")->limit(5)->all();
	
				$limit = count($pendings);//当条数<5时，while将会结束
	
				if(empty($pendings)){
					if(empty($get_pending_cnt)){
						echo "\n get no pending record!";
						$this_err_msg ='get no pending record!';
					}else {
						echo "\n deal records all, have no more todo;";
					}
					break;
				}
				else
					$get_pending_cnt += count($pendings);
	
				/*避免listing太多或者响应时间长，改为每5条记录保存到user表一次*/
				$listingDetailInfos = [];//需要update到user库的listing信息
				$parentAsins = [];//获取到的listing信息里面，是有有listing存在parent asin，存低。
	
				$skuList = [];
				$asinList = [];
				$requestASIN = [];
				$map_pendings = [];
				foreach ($pendings as $pending){
					$skuList[$pending->id] = $pending->seller_sku;
					$asinList[$pending->id] = $pending->asin;
					$requestASIN[] = $pending->asin;
					$map_pendings[$pending->id] = $pending;
					//lock record to process=1, clear old prod_info & err_msg
					$pending->process  = 1;
					$pending->process_status  = 0;
					$pending->prod_info  = '';
					$pending->err_msg  = '';
					$pending->save();
				}
	
				echo "\n get pending listings to fetch: ASIN(" .implode(', ', $requestASIN). ");";
				//get listing detail
				try{
					$rtn = self::getListingsInfosByPAS($row, $requestASIN,"BrowseNodes,Images,ItemAttributes","B");
		
					if(!empty($rtn['message']['Items']['Request']['Errors'])){
						$err =  "call api return some error:".print_r($rtn['message']['Items']['Request']['Errors'],true);
							
						foreach ($asinList as $rowId=>$asin){
							$this_pending = $map_pendings[$rowId];
							$this_pending->process_status = 2;
							$this_pending->err_cnt = empty($this_pending->err_cnt)?1:((int)$this_pending->err_cnt+1);
							$this_pending->err_msg = $err;
							$this_pending->update_time =TimeUtil::getNow();
							$this_pending->save();
						}
							
						echo "\n getListingsDetail fail:$err";
						$this_status = 1;
						$this_process_status = 1;
						$this_err_cnt ++;
						$this_err_msg ="getListingsDetail fail:$err";
						continue;// 下一组
					}
					//todo : 这里一定是if和else的排他？
					else{
						echo "\n format api response;";
						$details = [];
						//print_r($rtn['message']['Items']);
						if(!empty($rtn['message']['Items']['Item'])){
							if(!empty($rtn['message']['Items']['Item']['ASIN']))
								$details[$rtn['message']['Items']['Item']['ASIN']] = $rtn['message']['Items']['Item'];
							else{
								foreach ($rtn['message']['Items']['Item'] as $item){
									$details[$item['ASIN']] = $item;
								}
							}
						}
						if(empty($details)){
							$err =  "get no detail by this time";
							foreach ($asinList as $rowId=>$asin){
								$this_pending = $map_pendings[$rowId];
								$this_pending->process_status = 2;
								$this_pending->err_cnt = empty($this_pending->err_cnt)?1:((int)$this_pending->err_cnt+1);
								$this_pending->err_msg = $err;
								$this_pending->update_time =TimeUtil::getNow();
								$this_pending->save();
							}
							echo "\n $err";
							$this_status = 1;
							$this_process_status = 1;
							$this_err_cnt ++;
							$this_err_msg ="getListingsDetail fail:$err";
							continue;
						}
						
						foreach ($asinList as $rowId=>$asin){
							echo "\n deal with id:$rowId ;asin: $asin";
							$this_pending = $map_pendings[$rowId];
							$err_msg = '';
	
							if(empty($details[$asin])){
								$this_pending->process_status = 2;
								$this_pending->err_cnt = empty($this_pending->err_cnt)?1:((int)$this_pending->err_cnt+1);
								$this_pending->err_msg = 'get no product info';
								$this_pending->update_time =TimeUtil::getNow();
							}
							//存低ParentASIN备用
							if(!empty($details[$asin]['ParentASIN']) && $details[$asin]['ParentASIN']!==$asin)
								$parentAsins[] = $details[$asin]['ParentASIN'];
	
							if(empty($details[$asin]['ItemAttributes'])){
								$err_msg .= 'rowId='.$rowId.' get no product ItemAttributes info;';
							}
							if(empty($details[$asin]['BrowseNodes'])){
								$err_msg .= 'rowId='.$rowId.'get no product BrowseNodes info;';
							}
	
							$images_src = [];
								if(empty($details[$asin]['ImageSets'])){
								$err_msg .= 'rowId='.$rowId.'get no product ImageSets info;';
							}else{
								$imageSets = empty($details[$asin]['ImageSets']['ImageSet'])?[]:$details[$asin]['ImageSets']['ImageSet'];
								if(empty($imageSets)){
									echo '\n get ImageSets ,but get no image info!';
								}else{
									if(!isset($imageSets[0]))
										$imageSets = array(0=>$imageSets);
									$images_other = [];
									$images_primary = [];
									foreach ($imageSets as $imgSet){
										if(!empty($imgSet['@attributes']['Category']) && $imgSet['@attributes']['Category']=='primary')
											if(!empty($imgSet['LargeImage']['URL'])) $images_primary[] = $imgSet['LargeImage']['URL'];
										else
											if(!empty($imgSet['LargeImage']['URL'])) $images_other[] = $imgSet['LargeImage']['URL'];
									}
									$images_src = array_merge($images_primary,$images_other);
								}
							}
	
	
							if(!empty($err_msg)){
								$this_pending->process_status = 2;
								$this_pending->err_cnt = empty($this_pending->err_cnt)?1:((int)$this_pending->err_cnt+1);
								$this_pending->err_msg = $err_msg;
								$this_pending->update_time =TimeUtil::getNow();
								
								echo "\n get listing info error:$err_msg";
								$this_status = 1;
								$this_process_status = 1;
								$this_err_cnt ++;
								$this_err_msg ="getListingsDetail fail:$err_msg";
							}
							else{
								$listingInfo = $details[$asin];
								$this_pending->process = 2;
								$this_pending->process_status = 1;
								$this_pending->prod_info = json_encode($listingInfo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
								$this_pending->img_info = json_encode($images_src);
								$this_pending->err_cnt = 0;
								$this_pending->err_msg = '';
								$this_pending->update_time =TimeUtil::getNow();
							}
	
							if($this_pending->save()){
								if(!empty($listingInfo)){
									$listingDetailInfos[] = [
										'detail_info'=>$listingInfo,
										'merchant_id'=>$merchant_id,
										'marketplace_id'=>$marketplace_id,
										'seller_sku'=>@$skuList[$rowId],
										'asin'=>$asin,
										'parentAsin'=>empty($details[$asin]['ParentASIN'])?'':$details[$asin]['ParentASIN'],
									];
								}
							}else{
								echo "update pending queue failed:".print_r($this_pendin->errors);
							}
						}//end of each listing detail
					}
				}catch (\Exception $e) {
					echo "\n Exception : \n ".$e->getMessage();
					$this_status = 1;
					$this_process_status = 0;
					$this_err_msg = print_r($e->getMessage(),true);
				}
				sleep(10);
			}//end of while uid's all pending
			$parentlistingDatas = [];
			if(!empty($parentAsins)){
				$parentAsins = array_unique($parentAsins);
				foreach ($parentAsins as $parentAsin){
					$rtn = self::getListingsInfosByPAS($row, $parentAsin,"BrowseNodes,Images,ItemAttributes","B");
					if(!empty($rtn['message']['Items']['Item'])){
						$parentlistingDatas[] = [
							'detail_info'=>$rtn['message']['Items']['Item'],
							'merchant_id'=>$merchant_id,
							'marketplace_id'=>$marketplace_id,
							'seller_sku'=>@$skuList[$rowId],
							'asin'=>$parentAsin,
							'parentAsin'=>'',
							'isParent'=>true,
						];
					}else{
						echo "\n parent asin get info failed:".print_r($rtn,true);
					}
				}
			}
			//先保存商品信息
			if(!empty($parentlistingDatas)){
				$rtn = self::updateListingDetailToUserDb($puid, $parentlistingDatas);
				if(empty($rtn['success']))
					echo "\n update parent Listing Detail to user_db has error: ".$rtn['message'];
			}
			//保存一般产品 & 子产品
			if(!empty($listingDetailInfos)){
				$rtn = self::updateListingDetailToUserDb($puid, $listingDetailInfos);
				if(empty($rtn['success']))
					echo "\n update Listing Detail to user_db has error: ".$rtn['message'];
			}
		
			//update saas_amazon_autosync_v2 status
			$auto_sync_v2 = SaasAmazonAutosyncV2::find()->where(['type'=>'amzListingAddiInfo','platform_user_id'=>$merchant_id,'site_id'=>$marketplace_id])->one();
			if(!empty($auto_sync_v2)){
				$auto_sync_v2->status = $this_status;
				$auto_sync_v2->process_status  =$this_process_status;
				$auto_sync_v2->err_cnt = ($this_status==0)?0:$this_err_cnt;
				$auto_sync_v2->err_msg = ($this_status==0)?'':$this_err_msg;
				$auto_sync_v2->update_time = time();
				if($this_err_cnt>=10)
					$auto_sync_v2->status = 0;//重试次数10之后停止
				if($this_status==0){
					$auto_sync_v2->last_finish_time = time();
					$auto_sync_v2->next_execute_time = time()+3600;//理论上本同步只受限于amzListing的同步，next_execute_time随便设置为1小时候，实际不生效
					$auto_sync_v2->process_status = 2;
				}
				if($this_status==1){
					$auto_sync_v2->next_execute_time = time();
				}
				
				if(!$auto_sync_v2->save())
					echo "\n update saas_amazon_autosync_v2 failed:".print_r($auto_sync_v2->errors,true);
				else
					echo "\n auto_sync_v2 id:".$auto_sync_v2->id.", merchant: ".$merchant_id.", marketplace: ".$marketplace_id." pending fetch listing detail finished.";
			}else
				echo "\n auto_sync_v2 record not found!";
		}//end of while auto_sync record
	}//end of cronGetListingsDetailForUser
	
	
	/**
	* 查找保存产品的目录信息 by mws 适用于已知amz账号信息和seller sku情况下的多次调用
	* @param	srting	$seller_sku
	* @param	array	$account_info
	* @return	array				note
	* @author	lzhl	2017/07/13		初始化
	**/
	public static function getStoreProductCategoriesBySKU($seller_sku, $account_info){
		$ret = ['success'=>true,'message'=>''];
		try{
			$action = "GetProductCategoriesForSKU";
			$get_params = array();
			$config=[
				'merchant_id'=>$account_info['merchant_id'],
				'marketplace_id'=>$account_info['marketplace_id'],
		        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 				'access_key_id'=>$account_info['access_key_id'],
// 				'secret_access_key'=>$account_info['secret_access_key'],
		        'mws_auth_token'=>$account_info['mws_auth_token'],
			];

			$get_params['config'] = json_encode($config);
			if(!empty($seller_sku))
				$get_params["SellerSKU"] = $seller_sku;
			else
				return ['success'=>false,'message'=>"seller sku can not be empty"];
								
			$get_params["return_type"] = 'json';
			//echo '\n get_params return :'.print_r($get_params,true);
			$result = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params);

			//echo '\n proxy return :'.print_r($result,true);
				
			$result = is_array($result)?$result:json_decode($result,true);
			$response = empty($result['response'])?[]:$result['response'];
			//var_dump($response);
			if(!empty($response['success']) && !empty($response['BrowseNodes']) ){
				$allCatStr = array();
				foreach ($response["BrowseNodes"] as $node){
					$inputNode = array();
					$inputNode['BrowseNodes'] = array();
					$inputNode['BrowseNodes']['BrowseNode'] = $node;
		
					// 这种方法返回的 root Category 由于没有字段 IsCategoryRoot 判断过滤，所以暂时没办法去除 @todo 后面可以收集root Category 的id 去避免
					$allCatStr[] = AmazonApiHelper::getCatStr(json_decode(json_encode($inputNode)));
				}
				// 由于调用这个接口都是没有sales rank 这些listing才进来，返回就不保存到 AmazonTempCategoryInfo 里面了，保存对后面查找帮助不多。@todo 后面确实有需要再保存

				$ret['message'] = $allCatStr;
			} else {
				$ret['success'] =false;
				$ret['message'] .= "GetProductCategoriesForSKU 失败：".@$result['message'];
			}
		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $ret);
		}catch (\Exception $e) {
			//echo "\nException file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage();
			$ret['success'] =false;
			$ret['message'] = $e->getMessage();
		}	
		return array($ret['success'],$ret['message']);
	}//end of getStoreProductCategoriesBySKU
	

	/**
	* 查找保存 amazon_listing_fetch_addi_info_queue 里面未处理完的产品原始图片url信息
	* @access static
	* @param
	* @return
	* log			name		date			note
	* @author		lzhl		2017/07/13		初始化
	* @author		lzhl		2017/07/18		废弃
	**/
	public static function cronGetListingsImagesToUser(){
		$currentQueueVersion = ConfigHelper::getGlobalConfig("cronGetListingsImagesToUser/QueueVersion",'NO_CACHE');
		if (empty($currentQueueVersion))
			$currentQueueVersion = 0;

		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$queueVersion))
			self::$queueVersion = $currentQueueVersion;
				
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$queueVersion <> $currentQueueVersion){
			exit("Version new $currentQueueVersion , this job ver ".self::$queueVersion." exits for using new version $currentQueueVersion.");
		}
		
	
		$query_pending_fetch =
		"SELECT * FROM `amazon_listing_fetch_addi_info_queue` WHERE
		(`process`=4 or (`process`=5 and `process_status`=2 and `err_cnt`<10))
		ORDER BY `priority`,`create_time`,`puid` ASC LIMIT 100 ";
		$command = Yii::$app->db_queue2->createCommand($query_pending_fetch);
		$pendings = $command->queryAll();
		if(empty($pendings)){
			echo "\n have no pending todo;";
			return;
		}
		
		$pending_listings = [];
		$record_ids_status4 = [];
		$record_ids_status5 = [];
		foreach ($pendings as $pending){
			$puid = $pending['puid'];
			$merchant_id = $pending['merchant_id'];
			$marketplace_id = $pending['marketplace_id'];
			$pending_listings[$puid][$merchant_id][$marketplace_id][] = $pending;
			if($pending['process']==4)
				$record_ids_status4 [] = $pending['id'];
			if($pending['process']==5)
				$record_ids_status4 [] = $pending['id'];
		}
	
		//update 将要执行的pending的状态，避免其他job执到
		//status=4的，直接将status update为5，表示正在拉取
		if(!empty($record_ids_status4)){
			$lock_sql_1 ="UPDATE `amazon_listing_fetch_addi_info_queue` SET `process`=5 WHERE `id` IN (".implode(',', $record_ids_status4).")";
			Yii::$app->db_queue2->createCommand($lock_sql_1)->execute();
		}
		//status=5的，将err_cnt +1，继续尝试拉取，
		if(!empty($record_ids_status5)){
			$lock_sql_2 ="UPDATE `amazon_listing_fetch_addi_info_queue` SET `err_cnt`=`err_cnt`+1, `process_status`=0 WHERE `id` IN (".implode(',', $record_ids_status5).")";
			Yii::$app->db_queue2->createCommand($lock_sql_2)->execute();
		}
	
		foreach ($pending_listings as $puid=>&$merchant_level_data){
			$puid_update_listing_data = [];
			if(!empty($merchant_level_data)){
				foreach ($merchant_level_data as $merchant_id=>&$marketplace_level_data){
					if(!empty($marketplace_level_data)){
						foreach ($marketplace_level_data as $marketplace_id=>&$all_listing){
							//amazon账号信息
							$sql = "SELECT *
							FROM  `saas_amazon_user` AS su
							LEFT JOIN saas_amazon_user_marketplace AS sm ON su.amazon_uid = sm.amazon_uid
							WHERE su.is_active=1 and su.uid=$puid and sm.marketplace_id = '$marketplace_id' and su.merchant_id='$merchant_id' ";
							$command = Yii::$app->db->createCommand($sql);
							$accountInfo = $command->queryOne();
							if(empty($accountInfo)){
								echo "\n user amazon account info missing!";
								continue;
							}
							foreach ($all_listing as $listing){
								$imgsInfo = AmazonProductAdvertisingHelper::getItemImages($marketplace_id, $listing['asin']);
								if($imgsInfo['success']){
									if(!empty($imgsInfo['parent_asin'])){
										//如果得知这是个子商品，且还没有父商品信息记录，立即去get父产品的信息
										$parentListing = AmazonListing::find()->where(['asin'=>$imgsInfo['parent_asin']]);
									}
								}else{
									echo "\n get images failed:".print_r($imgsInfo,true);
									continue;
								}
								list($cats_ret, $catsInfo) = self::getStoreProductCategoriesBySKU($listing['seller_sku'], $accountInfo);
								if($cats_ret){
									//成功获取到catalog信息的话，需要update process、catalog_info、process_status
									$allCatStr = $catsInfo;
									$listing['catalog_info'] = implode(";", $allCatStr);
									echo "\n record id=".$listing['id']." get catalog success";
									$update_sql = "UPDATE `amazon_listing_fetch_addi_info_queue` SET
									`process`=4, `process_status`=1, `catalog_info`=:cat_str, update_time='".TimeUtil::getNow()."', `err_msg`=''
									WHERE `id`=".$listing['id'];
									$command = Yii::$app->db_queue2->createCommand($update_sql);
									$command->bindValue(":cat_str",implode(";", $allCatStr),\PDO::PARAM_STR);
									$tmp_sql = $command->getRawSql();
									$up = $command->execute();
									if($up){
										$tmp_up = [
											'merchant_id'=>$merchant_id,'marketplace_id'=>$marketplace_id,'sku'=>$listing['seller_sku'],'asin'=>$listing['asin'],'data'=>$listing
											];
										$puid_update_listing_data[] = $tmp_up;
									}else{
										echo "\n record id=".$listing['id']." update to db_queue2 failed, sql:".$tmp_sql;
									}
								}else{
									//获取失败，需要update process、process_status
									//err_cnt在之前lock_sql的时候已经+了
									echo "\n record id=".$listing['id']." get catalog info error: ".$catsInfo;
									$update_sql = "UPDATE `amazon_listing_fetch_addi_info_queue` SET
									`process_status`=2, `err_msg`=:err_msg, update_time='".TimeUtil::getNow()."'
									WHERE `id`=".$listing['id'];
									$command = Yii::$app->db_queue2->createCommand($update_sql);
									$command->bindValue(":err_msg","get catalog info error: ".$catsInfo,\PDO::PARAM_STR);
									$tmp_sql = $command->getRawSql();
									$up = $command->execute();
									if(!$up)
										echo "\n record id=".$listing['id']." update to db_queue2 failed, sql:".$tmp_sql;
								}
							}//end of each listing
						}//end of each marketplace level
					}//end of !empty marketplace level
				}//end of each merchant level
			}//end of !empty merchant level
	
			if(!empty($puid_update_listing_data)){
				echo "\n start to update catalog info to user db";
			
				foreach ($puid_update_listing_data as $data){
					if(empty($data['data']['catalog_info'])){
						echo "\n catalog_info is empty,skip to update;";
						continue;
					}
					$amazonListing = AmazonListing::find()
						->where(['merchant_id'=>$data['merchant_id'],'marketplace_id'=>$data['marketplace_id']])
						->andWhere(['asin'=>$data['asin'],'sku'=>$data['sku']])
						->one();
					if(empty($amazonListing)){
						echo "\n user listing record lost: merchant_id=".$data['merchant_id'].", marketplace_id=".$data['marketplace_id'].", asin=".$data['asin'].", sku=".$data['sku'];
						continue;
					}
			
					$amazonListing->cat_str = $data['data']['catalog_info'];
					$amazonListing->get_info_step = 2;
					$amazonListing->update_time = TimeUtil::getNow();
					if(!$amazonListing->save()){
						echo "\n update amazon_listing failed:".print_r($amazonListing->errors,true);
					}
					else
						echo "\n update success: merchant_id=".$data['merchant_id'].", marketplace_id=".$data['marketplace_id'].", asin=".$data['asin'].", sku=".$data['sku'];
				}
			}
		}//end of each puid's data
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * 通过amaozn广告接口获取asin信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl	2017/07/13		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getListingsInfosByPAS($account_info, $idList, $ResponseGroup='ItemAttributes', $invoke_type='B'){
		$ret = ['success'=>true,'message'=>''];
		$action = 'GetProductAttributesByAsin';
		$idType = 'ASIN';
		try{
			$get_params = array();
			$config=[
			'merchant_id'=>$account_info['merchant_id'],
			'marketplace_id'=>$account_info['marketplace_id'],
	        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 			'access_key_id'=>$account_info['access_key_id'],
// 			'secret_access_key'=>$account_info['secret_access_key'],
	        'mws_auth_token'=>$account_info['mws_auth_token'],
			];
			$get_params['config'] = json_encode($config);
	
			//$get_params["idType"] = $idType;// ASIN、GCID、SellerSKU、UPC、EAN、ISBN 和 JAN。
			//$params["asin"] = implode(',', $idList);
			$params["response_group"] = $ResponseGroup;
			$params["return_type"] = 'json';
			$params['operation'] = 'ItemLookup';
			$get_params['parms'] = json_encode($params);
				
			$post_params = [];
			if(is_array($idList))
				$post_params['asin'] = implode(',', $idList);
			else
				$post_params['asin'] =  $idList;
				
			if($invoke_type=='W'){
				$journal_id = SysLogHelper::InvokeJrn_Create("Amazon",__CLASS__, __FUNCTION__ , array(
						$account_info['uid'],$account_info['merchant_id'],$account_info['marketplace_id'],$idList));
			}
			if($invoke_type=='B'){
				echo "\n get_params :".print_r($get_params,true);
				echo "\n post_params :".print_r($post_params,true);
			}
	
			$result = AmazonProxyConnectApiHelper::call_amazon_api($action,$get_params,$time_out=60,$return_type='json',$post_params);
			/*
			if($invoke_type=='B'){
				echo '\n proxy return :'.print_r($result,true);
			}
			*/
			$result = is_array($result)?$result:json_decode($result,true);
			$response = empty($result['response'])?[]:$result['response'];
			$ret['message'] = $response;
		}catch (\Exception $e) {
			$ret = ['success'=>false , 'message'=> $e->getMessage()];
		}
			
		return $ret;
	}// end of getListingsDetail
	
	/**
	 * 将amaozn广告接口获取asin信息回写到user库
	 * @param
	 * @return
	 * @author		lzhl	2017/07/14		初始化
	 **/
	private static function updateListingDetailToUserDb($puid,$listingDtail){
		try{		
			$success = true;
			$message = '';
			foreach ($listingDtail as $info){
				if(empty($info['detail_info']) || empty($info['merchant_id']) || empty($info['marketplace_id']) || empty($info['asin'])){
					$message .= "\n some key val is missing,continue! info: ".print_r($info,true);
					$success = false;
					continue;
				}
	
				$listing = AmazonListing::find()
				->where(['merchant_id'=>$info['merchant_id'],'marketplace_id'=>$info['marketplace_id'],'asin'=>$info['asin']])
				->one();
				if(empty($listing) && empty($info['isParent'])){
					$message .= "\n listing not found! info: ".print_r($info,true);
					$success = false;
					continue;
				}
				elseif(empty($listing) && !empty($info['isParent'])){
					//如果是父商品且还没有记录，则新建记录
					$listing = new AmazonListing();
					$listing->merchant_id = $info['merchant_id'];
					$listing->marketplace_id = $info['marketplace_id'];
					$listing->marketplace_short = empty(self::$AMAZON_MARKETPLACE_REGION_CONFIG[$info['marketplace_id']])?$info['marketplace_id']:self::$AMAZON_MARKETPLACE_REGION_CONFIG[$info['marketplace_id']];
					$listing->asin = $info['asin'];
					$listing->status = '1';
					$listing->batch_num = '1';
					$listing->create_time = TimeUtil::getNow();
				}
	
				$detail = $info['detail_info'];
	
				$attr = $detail['ItemAttributes'];
				if(!empty($attr['Title']))
					$listing->title = $attr['Title'];
				
				//$listing->description
				//$listing->price
				//$listing->stock
				//$listing->condition
				//$listing->currency
				//$listing->status
				//$listing->images
				//$listing->catalog_number
				
				$listing->detail_page_url = $detail['DetailPageURL'];
				$listing->binding = @$attr['Binding'];
				$listing->brand = @$attr['Brand'];
	
				$listing->color = @$attr['Color'];
				$listing->size = @$attr['Size'];
				if(!empty($attr['Feature'])){
					if(is_array($attr['Feature']))
						$listing->feature = json_encode($attr['Feature']);
					else
						$listing->feature = $attr['Feature'];
				}
				$listing->ean = @$attr['EAN'];
				$listing->label = @$attr['Label'];
				$listing->publisher = @$attr['Publisher'];
				$listing->studio = @$attr['Studio'];
				$listing->manufacturer = @$attr['Manufacturer'];
				$listing->model = @$attr['Model'];
				$listing->mpn =  @$attr['MPN'];
				$listing->part_number = @$attr['PartNumber'];
				$listing->product_group = @$attr['ProductGroup'];
				$listing->product_type_name = @$attr['ProductTypeName'];
	
				$listing->get_info_step = 1;
				$listing->prod_info = json_encode($detail);
				$listing->is_get_prod_info = 1;
				$listing->err_times = 0;
				$listing->err_msg = '';
	
				//子产品特殊处理
				$is_parent = false;
				$is_child = false;
				if(!empty($info['parentAsin']) && empty($info['isParent'])){
					$is_child = true;
					$listing->parent_asin = $info['parentAsin'];
					if(isset($parent))
						unset($parent);
					if(isset($childrens))
						unset($childrens);
					$parent = AmazonListing::find()
					->where(['merchant_id'=>$info['merchant_id'],'marketplace_id'=>$info['marketplace_id'],'asin'=>$info['parentAsin']])
					->one();
					if(!empty($parent)){
						if(!empty($parent->childrens))
							$childrens = json_decode($parent->childrens,true);
						if(empty($childrens))
							$childrens = [];
						$childrens[] = $listing->asin;
						$childrens = array_unique($childrens);
						$parent->childrens = json_encode($childrens);
					}else{
						$listing->err_msg .= 'parent listing not found!';
					}
				}
				if(!empty($info['isParent']))
					$is_parent = true;
				
				if(!$is_parent && !$is_child){
					$listing->childrens = NULL;
					$listing->part_number = NULL;
				}
				if(!$is_parent){
					$listing->childrens = NULL;
				}
				if(!$is_child){
					$listing->part_number = NULL;
				}
				
				$imageSets = empty($detail['ImageSets']['ImageSet'])?[]:$detail['ImageSets']['ImageSet'];
				if(empty($imageSets)){
					if($is_child)
						$listing->err_msg .= 'get no image info!';
				}else{
					if(!$is_parent ){
						if(!isset($imageSets[0]))
							$imageSets = array(0=>$imageSets);
						$images_other = [];
						$images_primary = [];
						foreach ($imageSets as $imgSet){
							//$imgSet['LargeImage']['URL'];
							if(!empty($imgSet['@attributes']['Category']) && $imgSet['@attributes']['Category']=='primary')
								if(!empty($imgSet['LargeImage']['URL'])) $images_primary[] = $imgSet['LargeImage']['URL'];
							else
								if(!empty($imgSet['LargeImage']['URL'])) $images_other[] = $imgSet['LargeImage']['URL'];
						}
						$images_src = array_merge($images_primary,$images_other);
						if(!empty($images_src)){
							$listing->images_src = json_encode([ $listing->asin=>$images_src ]);
						}else
							$listing->images_src = '';
						if(!empty($parent)){
							$parent_images = empty($parent->images_src)?[]:json_decode($parent->images_src,true);
							if(empty($parent_images)) $parent_images = [];
							$parent_images[$listing->asin] = empty($images_src)?[]:$images_src;
							$parent->images_src = json_encode($parent_images);
						}
					}else{
						//父商品图片由子商品图片集合得出
					}
				}
				
				if(!empty($detail['BrowseNodes'])){
					$allCatStr = array();
					foreach ($detail['BrowseNodes'] as $node){
						$inputNode = array();
						$inputNode['BrowseNodes'] = array();
						$inputNode['BrowseNodes']['BrowseNode'] = $node;
	
						// 这种方法返回的 root Category 由于没有字段 IsCategoryRoot 判断过滤，所以暂时没办法去除 @todo 后面可以收集root Category 的id 去避免
						$allCatStr[] = AmazonApiHelper::getCatStr(json_decode(json_encode($inputNode)));
					}
					// 由于调用这个接口都是没有sales rank 这些listing才进来，返回就不保存到 AmazonTempCategoryInfo 里面了，保存对后面查找帮助不多。@todo 后面确实有需要再保存
					if(!empty($allCatStr))
						$listing->cat_str = implode(';', $allCatStr);
					else
						$listing->cat_str = '';
				}else{
					$listing->cat_str = '';
					$listing->err_msg .= 'listing catalog info not found!';
				}
				$listing->update_time = TimeUtil::getNow();
	
				if(!empty($parent)){
					$parent->update_time = TimeUtil::getNow();
					if(!$parent->save()){
						$message .= "parent listing update failed! info: ".print_r($parent->errors,true);
						echo "\n $message";
						$success = false;
					}
				}
				if(!$listing->save()){
					$message .= "listing update failed! info: ".print_r($listing->errors,true);
					echo "\n $message";
					$success = false;
					continue;
				}
			}		
			return ['success'=>$success,'message'=>$message];
		}catch (\Exception $e) {
			return ['success'=>false , 'message'=> $e->getMessage()];
		}
	}
	
	/**
	* 可能需要多ip来处理
	* 通过原始image url 下载图片到网络硬盘
	**/
	public static function downloadAmzListingImages(){
		$pendings = AmazonListingFetchAddiInfoQueue::find()
		->Where(" `process`=2 or (`process`=3 and `process_status`<>1 and `err_cnt`<10 ) ")
		->OrderBy(" `priority`,`puid`,`create_time`,`err_cnt` ASC ")->limit(5)->all();
	
		if(empty($pendings)){
			echo "\n no pending to do!";
			return;
		}
		
		echo "\n get ".count($pendings)." record to do;";
		
		$puid_map_pendings = [];
		foreach ($pendings as $pending){
			$puid_map_pendings[$pending->puid][] = $pending;
		}
		foreach ($puid_map_pendings as $puid=>$listngs){
			
			echo "\n deal with puid:$puid";
			
			foreach ($listngs as $listing){
				$listing->process = 3;
				$listing->process_status = 0;
				$listing->err_msg = '';
				$listing->callback = '';
				$listing->update_time = TimeUtil::getNow();
				if(!$listing->save()){
					echo "\n step 001 ==> update pending record id=".$listing->id." failed:".print_r($listing->errors,true);
					continue;
				}
		
				$images_src = empty($listing->img_info)?[]:json_decode($listing->img_info,true);
				if(empty($images_src)){
					echo "\n pending record id=".$listing->id." images src not found!";
					$listing->process_status = 2;
					$listing->err_msg = 'images src not found!';
					$listing->err_cnt = empty($listing->err_cnt)?1:((int)$listing->err_cnt+1);
					$listing->update_time = TimeUtil::getNow();
					if(!$listing->save()){
						echo "\n step 002 ==> update pending record id=".$listing->id." failed:".print_r($listing->errors,true);
					}
					continue;
				}
	
				$success_image_index = [];
				//download images to attachment
				foreach ($images_src as $index=>$img_src){
					$down = self::downloadImage($img_src);
					if($down['success']){
						$success_image_index[] = $index;
					}else{
						echo "\n ".$down['message'];
					}
					//每3秒下载一张图片，避免频率过高被amaozn band
				}
				if (count($success_image_index)!==count($images_src)){
					$listing->process_status = 2;
					$listing->err_msg = 'down count do not match images_src count';
					$listing->err_cnt = empty($pending->err_cnt)?1:((int)$pending->err_cnt+1);
				 	$listing->update_time = TimeUtil::getNow();

				 	if(!$listing->save()){
				 		echo "\n step 002 ==> update pending record id=".$listing->id." failed:".print_r($listing->errors,true);
					}
					continue;
				}else{
					$listing->process = 4;
					$listing->process_status = 1;
					$listing->err_msg = '';
					$listing->err_cnt = 0;
					$listing->update_time = TimeUtil::getNow();
					$id = $listing->id;
					$date = TimeUtil::getNowDate();
					$indexArr_str = json_encode($success_image_index);
					$listing->callback = "eagle\modules\amazon\apihelpers\AmazonApiHelper::uploadAmazonLocalFileToAliOssAndUptadeListinginfo($id, $puid, '$date', '$indexArr_str')";
								
					if(!$listing->save()){
						echo "\n step 003 ==> update pending record id=".$pending->id." failed:".print_r($listing->errors,true);
					}
				}
			}//end of each pending listing
			echo "\n puid:$puid doen!";
		}//end of each puid
	}//end of downloadAmzListingImages
	
	
	private static function downloadImage($url){
		$results['success'] = true;
		$results['message'] = '';
		if (empty($url ) ){
			$url='';
			$results['success'] = false;
			$results['message'] = 'URL is empty';
			return $results;
		}
		try{
			$url = trim($url);
			$savedLocalName = $url;
			$savedLocalName = str_replace("http://","",$savedLocalName);
			$savedLocalName = str_replace("https://","",$savedLocalName);
			$savedLocalName = str_replace("/","_",$savedLocalName);
			$savePath = self::createAmazonAttachmentImgDir();
									
			$target_file = $savePath.DIRECTORY_SEPARATOR.$savedLocalName;
			if(file_exists($target_file)){
				return ['success'=>true , 'message'=>'file allready exist','local_name'=>$savedLocalName];
			}
		
			$return_content = self::http_get_data($url);
				
			//$filename = $url;
			//get extersion of the file original
			//$pics = explode('.' , $url);
			//$num = count($pics);
			//$extName = $pics[$num-1];
				
			$fp= @fopen($target_file,"w"); //将文件绑定到流 
			fwrite($fp,$return_content); //写入文件
				
			$results['local_name'] = $savedLocalName;
			return $results;
		}catch (\Exception $e) {
			return ['success'=>false , 'message'=> $e->getMessage()];
	    }
	}
	
	/**
	 * 获取 img 保存路径
	 * @param	$is_create_dir 是否创建日期目录
	 * @return string
	 **/
	private static function createAmazonAttachmentImgDir($is_create_dir = true){
		if($is_create_dir){
			$basepath = self::getImgPathString().DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'amz_listing_img';
			//根据年月生成目录，用于以后方便管理删除文件
			$dataDir = date("Y-m-d");
	
			if(!file_exists($basepath.DIRECTORY_SEPARATOR.$dataDir)){
				mkdir($basepath.DIRECTORY_SEPARATOR.$dataDir);
				chmod($basepath.DIRECTORY_SEPARATOR.$dataDir,0777);
			}
			return $basepath.DIRECTORY_SEPARATOR.$dataDir;
		}else{
			$basepath = self::getImgPathString();
			return $basepath;
		}
	}
	
	/**
	* 获取TcPdf保存的路径
	* @return string
	**/
	private static function getImgPathString(){
		return \Yii::getAlias('@eagle/web');
	}
	
	/**
	* 获取web保存的路径
	* @return string
	**/
	private static function getWebPathString(){
		return \Yii::getAlias('@eagle/web');
	}
	
	/**
	* 获取url文件流
	**/
	private static function http_get_data($url) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
 		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
 		curl_setopt ( $ch, CURLOPT_URL, $url );
 		ob_start ();
 		curl_exec ( $ch );
 		$return_content = ob_get_contents ();
 		ob_end_clean ();

		$return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
 		return $return_content;
	}
	
	
	/**
	* 执行回调
	* @param	string	$action	//回调操作的类型：upload_images_to_AliOss上传图片到阿里服务器并返回url；#todo继补充续完善
 	* @return	boolean
 	**/
	public static function amazonListingFetchAddiInfoQueueCallBackHandler($action){
		if($action == 'upload_images_to_AliOss'){
			$pendings = AmazonListingFetchAddiInfoQueue::find()
			->Where(" `process`=4 or (`process`=5 and `process_status`<>1 and `err_cnt`<10 ) ")
			->OrderBy(" `priority`,`puid`,`create_time`,`err_cnt` ASC ")->limit(100)->all();
				 			
	 		if(empty($pendings)){
 				echo "\n no pending to do!";
 				return;
	 		}
	 		
	 		echo "\n get ".count($pendings)." to do";
	 		
	 		$puid_map_pendings = [];
	 		foreach ($pendings as $pending){
	 			$puid_map_pendings[$pending->puid][] = $pending;
	 		}
			foreach ($puid_map_pendings as $puid=>$listngs){
		 		 
		 		echo "\n deal with puid:$puid ";
		 		
				foreach ($listngs as $listng){
			 		$listng->process = 5;
			 		$listng->process_status = 0;
			 		$listng->err_msg = '';
					$listng->update_time = TimeUtil::getNow();
					if(!$listng->save()){
						echo "\n step 001 ==> update pending record id=".$listng->id." failed:".print_r($listng->errors,true);
						continue;
					}
			
					$callback = $listng->callback;
					if(empty($callback)){
						echo "\n id=".$listing->id." callbakc function Invalid !";
						$listng->process_status = 2;
						$listng->err_msg = 'callbakc function Invalid !';
						$listng->err_cnt = empty($listng->err_cnt)?1:((int)$listng->err_cnt + 1);
						$listng->update_time = TimeUtil::getNow();
					}else{
						$rtn = false;
						$msg = '';
						echo "\n " .$callback;
						eval('list($rtn,$msg)='.$callback.';');
						if(empty($rtn)){
							$listng->process_status = 2;
							$listng->err_msg = $msg;
							$listng->err_cnt = empty($listng->err_cnt)?1:((int)$listng->err_cnt + 1);
							$listng->update_time = TimeUtil::getNow();
						}else{
							$listng->process = 6;
							$listng->process_status = 1;
							$listng->err_msg = '';
							$listng->err_cnt = 0;
							$listng->update_time = TimeUtil::getNow();
						}
					}
					if(!$listng->save()){
						echo "\n step 002 ==> update pending record id=".$listng->id." failed:".print_r($listng->errors,true);
					}
				}//end of each pending listing
				echo "\n puid:$puid done!";
			}//end of each puid
		}
	}//end of amazonListingFetchAddiInfoQueueCallBackHandler
	
	/**
	 * 将本地图片上传到阿里服务器，并返回上传后的图片url
	 * @parma	int		$id:amazon_listing_fetch_addi_info_queue 对应记录id
	 * @parma	int		$puid:用户puid
	 * @parma	string	$date:日期
	 * @parma	array	$indexArr:queue表对应记录的img_info数组对应index
	 * @return	array
	 * @author	lzhl	2017-07-17
	 **/
	public static function uploadAmazonLocalFileToAliOssAndUptadeListinginfo($id,$puid,$date,$indexArr=[]){
		$pending = AmazonListingFetchAddiInfoQueue::findOne($id);
		if(empty($pending)){
			echo "\n id=$id record not found!";
			return array(false,"id=$id record not found!");
		}
		$images_src = empty($pending->img_info)?[]:json_decode($pending->img_info,true);
	
		if(empty($images_src)){
			echo "\n id=$id : images_src not found!";
			return array(false,"id=$id images_src not found!");
		}
	
		$uploaded_url = [];
		if(is_string($indexArr))
			$indexArr = json_decode($indexArr,true);
	
		foreach ($indexArr as $index){
			if(empty($images_src[$index])){
				echo "\n id=$id : images_src[$index] not found!";
				continue;
			}
			$savedLocalName = $images_src[$index];
			$savedLocalName = str_replace("http://","",$savedLocalName);
			$savedLocalName = str_replace("https://","",$savedLocalName);
			$savedLocalName = str_replace("/","_",$savedLocalName);
				
			$basepath = self::getImgPathString().DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'amz_listing_img';
			$basepath .= DIRECTORY_SEPARATOR.$date;
			$imagePath = $basepath.DIRECTORY_SEPARATOR.$savedLocalName;
			
			echo "\n imagePath: ".$imagePath;
			
			if(file_exists($imagePath)){
				list($rtn,$msg) = \eagle\modules\util\helpers\ImageHelper::uploadAmazonLocalFileToAliOss($puid, $imagePath);
				if($rtn){
					$uploaded_url[] = $msg;
				}else{
					echo "\n $msg";
				}
			}else{
				echo "\n image local not found, imagePath=$imagePath";
			}
		}
	
		if(!empty($uploaded_url)){
			$listing = AmazonListing::find()
			->where(['merchant_id'=>$pending->merchant_id,'marketplace_id'=>$pending->marketplace_id,'asin'=>$pending->asin])
			->one();
			if(empty($listing)){
				echo "\n id=$id pending record's user listing record not found!";
				return array(false,"id=$id pending record's user listing record not found!");
			}
						
			$listing->images = json_encode( [ $listing->asin=>$uploaded_url ] );
			$listing->get_info_step = 2;
			$listing->update_time = TimeUtil::getNow();
			if(!$listing->save()){
				echo "\n update id=$id pending record's user listing record failed:".print_r($listing->errors,true);
				return array(false,"update id=$id pending record's user listing record failed:".print_r($listing->errors,true));
			}
			else{
				//如果是子产品，需要update父产品的该asin对应mages
				if(!empty($listing->parent_asin)){
					$parent = AmazonListing::find()
						->where(['merchant_id'=>$listing->merchant_id,'marketplace_id'=>$listing->marketplace_id,'asin'=>$listing->parent_asin])
						->one();
					if(empty($parent))
						echo "\n listing's parent asin:".$listing->parent_asin." ,but listing not found in db;";
					else{
						$parent_imgs = empty($parent->images)?[]:json_decode($parent->images,true);
						$parent_imgs[$listing->asin] = $uploaded_url;
						$parent->images = json_encode($parent_imgs);
						$parent->update_time = TimeUtil::getNow();
						$parent->save(false);
					}
				}
	
				$pending->process = 6;
				$pending->process_status = 1;
				$pending->err_msg = '';
				$pending->err_cnt = 0;
				$pending->update_time = TimeUtil::getNow();
				if(!$pending->save()){
					echo "\n update id=$id pending record done status failed:".print_r($pending->errors,true);
					return array(false,"update id=$id pending record done status failed:".print_r($pending->errors,true));
				}
				return array(true,'');
			}
		}else{
			echo "\n id=$id pending record's uploaded none url!";
			return array(false,"id=$id pending record's uploaded none url!");
		}
	}
	
	/**
	 * 按需为用户开启amazon 在线 listing 同步记录
	 * 当该用户账号为新需求时，新建amzListing和amzListingAddiInfo记录
	 * 如果该两条记录已经存在，则更新状态和时间
	 * insert or update失败返回提示
	 * @author	lzhl	2017-07-18
	 **/
	public static function UserManualSyncActiveListing($puid, $merchant_id, $marketplace_id){
		$result = ['success'=>true,'message'=>''];
		$sql = "SELECT * FROM  `saas_amazon_user` AS su LEFT JOIN saas_amazon_user_marketplace AS sm 
		ON su.amazon_uid = sm.amazon_uid 
		WHERE su.is_active=1 and su.uid=$puid and sm.marketplace_id = '$marketplace_id' and su.merchant_id='$merchant_id' ";
		$command = Yii::$app->db->createCommand($sql);
		$accountInfo = $command->queryOne();
		
		if(empty($accountInfo)){
			return ['success'=>false,'message'=>'未绑定该Amazon店铺，或该绑定未启用'];
		}
		
		$sync_amzListingAddiInfo = SaasAmazonAutosyncV2::find()
		->where(['eagle_platform_user_id'=>$accountInfo['amazon_uid'],'platform_user_id'=>$merchant_id,'site_id'=>$marketplace_id,'type'=>'amzListingAddiInfo'])
		->one();
		
		$sync_addi_err = '';
		if(empty($sync_amzListingAddiInfo)){
			$sync_amzListingAddiInfo = new SaasAmazonAutosyncV2();
			$sync_amzListingAddiInfo->eagle_platform_user_id = $accountInfo['amazon_uid'];
			$sync_amzListingAddiInfo->platform_user_id = $merchant_id;
			$sync_amzListingAddiInfo->site_id = $marketplace_id;
			$sync_amzListingAddiInfo->status = 0;//初次调用不开启，等待amzListing执行完成之后回写状态
			$sync_amzListingAddiInfo->process_status = 0;
			$sync_amzListingAddiInfo->type = 'amzListingAddiInfo';
			$sync_amzListingAddiInfo->execution_interval = 0;
			$sync_amzListingAddiInfo->create_time = time();
			$sync_amzListingAddiInfo->err_cnt = 0;
		}else{
			if($sync_amzListingAddiInfo->status == 1 && $sync_amzListingAddiInfo->process_status<>2){
				$sync_addi_err .= '该店铺账号正在同步listing的详细信息中，请稍后再执行操作；';
			}elseif($sync_amzListingAddiInfo->status == 1 && $sync_amzListingAddiInfo->process_status==2){
				$sync_addi_err .= '该店铺账号同步listing的详细信息出错，请联系客服；';
			}elseif($sync_amzListingAddiInfo->status == 2){
				//暂时无呢种情况
				$sync_addi_err .= '该店铺账号已经永久停止listing的详细信息同步；';
			}elseif($sync_amzListingAddiInfo->status == 0){
				$sync_amzListingAddiInfo->update_time = time();
			}
		}
		if(empty($sync_addi_err)){
			if(!$sync_amzListingAddiInfo->save()){
				$result['success'] = false;
				$result['message'] .= 'UPDATE同步记录失败，CODE：100；';
				SysLogHelper::SysLog_Create('Listing',__CLASS__,__FUNCTION__,'error',print_r($sync_amzListingAddiInfo->errors));
				return $result;
			}
		}else{
			$result['success'] = false;
			$result['message'] .= $sync_addi_err;
			return $result;
		}
		//amzListingAddiInfo记录  处理成功后才继续处理 amzListing记录
		$sync_amzListing = SaasAmazonAutosyncV2::find()
		->where(['eagle_platform_user_id'=>$accountInfo['amazon_uid'],'platform_user_id'=>$merchant_id,'site_id'=>$marketplace_id,'type'=>'amzListing'])
		->one();
		
		$err = '';
		if(empty($sync_amzListing)){
			$sync_amzListing = new SaasAmazonAutosyncV2();
			$sync_amzListing->eagle_platform_user_id = $accountInfo['amazon_uid'];
			$sync_amzListing->platform_user_id = $merchant_id;
			$sync_amzListing->site_id = $marketplace_id;
			$sync_amzListing->status = 1;//初次调用立即开启
			$sync_amzListing->process_status = 0;
			$sync_amzListing->type = 'amzListing';
			$sync_amzListing->execution_interval = 0;
			$sync_amzListing->create_time = time();
			$sync_amzListing->err_cnt = 0;
		}else{
			if($sync_amzListing->status == 1 && $sync_amzListing->process_status<>2){
				$err .= '该店铺账号正在同步listing中，请勿重复操作；';
			}elseif($sync_amzListing->status == 1 && $sync_amzListing->process_status==2){
				$err .= '该店铺账号同步listing出错，请联系客服；';
			}elseif($sync_amzListing->status == 2){
				//暂时无呢种情况
				$err .= '该店铺账号已经永久停止listing同步；';
			}elseif($sync_amzListing->status == 0){
				if( !empty($sync_amzListing->next_execute_time) && $sync_amzListing->next_execute_time > time() )
					$err .= '该店铺账号预计下次同步完成时间为：'.date("Y-m-d H:i:s",$sync_amzListing->next_execute_time).'，请于该时间后再尝试；';
				elseif( !empty($sync_amzListing->last_finish_time) && $sync_amzListing->last_finish_time >= (time()-3600*12) )
				$err .= '该店铺账号近12小时内已经同步过listing，上次同步完成时间为：'.date("Y-m-d H:i:s",$sync_amzListing->last_finish_time).'，请于该时间的12小时候再尝试；';
				else {
					$sync_amzListing->next_execute_time = time();
					$sync_amzListing->status = 1;
					$sync_amzListing->process_status = 0;
					$sync_amzListing->update_time = time();
				}
			}
		}
		if(empty($err)){
			if(!$sync_amzListing->save()){
				$result['success'] = false;
				$result['message'] .= 'UPDATE同步记录失败，CODE：101；';
				SysLogHelper::SysLog_Create('Listing',__CLASS__,__FUNCTION__,'error',print_r($sync_amzListing->errors));
				return $result;
			}
		}else{
			$result['success'] = false;
			$result['message'] .= $err;
			return $result;
		}
		return $result;
	}
	
	/**
	 * 检查店铺是否可以请求listing同步
	 * @param	int		$puid
	 * @param	string	$merchant_id
	 * @param	string	$marketplace_id
	 * @return	array
	 * @author	lzhl	2017-07-18
	 **/
	public static function checkManualSyncActiveListingStatus($puid, $merchant_id, $marketplace_id){
		$result = ['success'=>true,'message'=>''];
		$sql = "SELECT * FROM  `saas_amazon_user` AS su LEFT JOIN saas_amazon_user_marketplace AS sm
		ON su.amazon_uid = sm.amazon_uid
		WHERE su.is_active=1 and su.uid=$puid and sm.marketplace_id = '$marketplace_id' and su.merchant_id='$merchant_id' ";
		$command = Yii::$app->db->createCommand($sql);
		$accountInfo = $command->queryOne();
		
		if(empty($accountInfo)){
			return ['success'=>false,'message'=>'未绑定该Amazon店铺，或该绑定未启用'];
		}
		
		$err = '';
		$sync_amzListing = SaasAmazonAutosyncV2::find()
		->where(['eagle_platform_user_id'=>$accountInfo['amazon_uid'],'platform_user_id'=>$merchant_id,'site_id'=>$marketplace_id,'type'=>'amzListing'])
		->one();
		if(empty($sync_amzListing)){
			//未开通的话，可以开通
			$result['message'] = '尚未开通listing同步，可以开通';
		}else{
			if($sync_amzListing->status == 1 && $sync_amzListing->process_status<>2){
				$err .= '该店铺账号正在同步listing中，请勿重复操作；';
			}elseif($sync_amzListing->status == 1 && $sync_amzListing->process_status==2){
				$err .= '该店铺账号同步listing出错，请联系客服；';
			}elseif($sync_amzListing->status == 2){
				//暂时无呢种情况
				$err .= '该店铺账号已经永久停止listing同步；';
			}elseif($sync_amzListing->status == 0){
				if( !empty($sync_amzListing->next_execute_time) && $sync_amzListing->next_execute_time > time() )
					$err .= '该店铺账号预计下次同步完成时间为：'.date("Y-m-d H:i:s",$sync_amzListing->next_execute_time).'，请于该时间后再尝试；';
				elseif( !empty($sync_amzListing->last_finish_time) && $sync_amzListing->last_finish_time >= (time()-3600*12) )
					$err .= '该店铺账号近12小时内已经同步过listing，上次同步完成时间为：'.date("Y-m-d H:i:s",$sync_amzListing->last_finish_time).'，请于该时间的12小时候再尝试；';
			}
		}
		if(!empty($err)){
			$result['success'] = false;
			$result['message'] .= $err;
		}
		return $result;
	}
	
	/**
	 * 检查店铺listing同步当前的进行状态
	 * @param	int		$puid
	 * @param	string	$merchant_id
	 * @param	string	$marketplace_id
	 * @return	array	array('success'=>boolean,'message'=>提示信息,'process'=>0~1,)	process:0未运行/运行完毕，1运行中，2运行失败
	 * @author	lzhl	2017-07-18
	 **/
	public static function checkActiveListingSyncProcess($puid, $merchant_id, $marketplace_id){
		$result = ['success'=>true,'message'=>'','process'=>0];
		$sql = "SELECT * FROM  `saas_amazon_user` AS su LEFT JOIN saas_amazon_user_marketplace AS sm
		ON su.amazon_uid = sm.amazon_uid
		WHERE su.is_active=1 and su.uid=$puid and sm.marketplace_id = '$marketplace_id' and su.merchant_id='$merchant_id' ";
		$command = Yii::$app->db->createCommand($sql);
		$accountInfo = $command->queryOne();
	
		if(empty($accountInfo)){
			return ['success'=>false,'message'=>'未绑定该Amazon店铺，或该绑定未启用','process'=>0,];
		}
	
		$sync_amzListing = SaasAmazonAutosyncV2::find()
		->where(['eagle_platform_user_id'=>$accountInfo['amazon_uid'],'platform_user_id'=>$merchant_id,'site_id'=>$marketplace_id,'type'=>'amzListing'])
		->one();
		if(empty($sync_amzListing)){
			return ['success'=>false,'message'=>'未进行过同步','process'=>0,];
		}else{
			$process = 0;
			$err = '';
			if($sync_amzListing->status == 1 && $sync_amzListing->process_status<>2){
				$process = 1;
				
			}elseif($sync_amzListing->status == 1 && $sync_amzListing->process_status==2){
				$process = 2;
				$err = $sync_amzListing->err_msg;
			}elseif($sync_amzListing->status == 2){
				$err = '该店铺账号已经永久停止listing同步';
			}elseif($sync_amzListing->status == 0){
				
			}
			$result['message'] = $err;
			$result['process'] = $process;
		}
		return $result;
	}
######################################    用户 amazon listing 拉取 end // lzhl 2017-07    ######################################

}//end of class
?>