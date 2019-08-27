<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\order\helpers;

use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
use yii\helpers\Url;
use eagle\models\SaasEbayUser;
use common\api\ebayinterface\shopping\getsingleitem;
use eagle\models\db_queue2\EbayItemPhotoQueue;
use eagle\modules\order\models\OdOrderItem;
use common\api\ebayinterface\getorders;

class EbayOrderHelper{
	
	static public $ebayCondition=[
	'haspayed'=>'eBay已付款',
	'hasnotpayed'=>'eBay未付款',
	'pending'=>'付款处理中',
	'hassend'=>'eBay已发货',
	'payednotsend'=>'已付款未发货',
	//'hasmessage'=>'有eBay留言',
	'hasinvoice'=>'已发送账单',
	];
	
	static public $ebayCommentType=[
		'Positive'=>'好评',
		'Neutral'=>'中评',
		'Negative'=>'差评',
		'null'=>'无评价',
	];
	
	static public $MenuCachePath = "Order/ebay-list-menu-statistic-data-memery";
	
	static public function getLeftMenuTree(){
		$OdLtMessageMenu = OrderTrackingMessageHelper::getOdLtMessageMenuByPlatform('ebay');
		$menu = [ 
				TranslateHelper::t ( 'ebay业务待处理' ) => [ 
						'icon' => 'icon-stroe',
						'items' => [ 
								TranslateHelper::t ( '所有订单' ) => [ 
										'url' => '../ebay-order/list?menu_select=all' 
								] 
						] 
				],
				
				TranslateHelper::t ( '订单业务流程' ) => [ 
						'icon' => 'icon-stroe',
						'items' => [ 
								TranslateHelper::t ( '未付款' ) => [ 
										'url' => '../ebay-order/list?order_status=' . OdOrder::STATUS_NOPAY 
								],
								TranslateHelper::t ( '已付款' ) => [ 
										'url' => '../ebay-order/list?order_status=' . OdOrder::STATUS_PAY . '&pay_order_type=all' 
								],
								TranslateHelper::t ( '发货中' ) => [ 
										'url' => '#',
										'items' => $deliveryMenu = \eagle\modules\delivery\helpers\DeliveryHelper::getDeliveryMenuByPlatform ( 'ebay' ) 
								],
								TranslateHelper::t ( '已完成' ) => [ 
										'url' => '../ebay-order/list?order_status=' . OdOrder::STATUS_SHIPPED 
								],
								TranslateHelper::t ( '已取消' ) => [ 
										'url' => '../ebay-order/list?order_status=' . OdOrder::STATUS_CANCEL 
								],
								TranslateHelper::t ( '暂停发货' ) => [ 
										'url' => '../ebay-order/list?order_status=' . OdOrder::STATUS_SUSPEND,
								],
								TranslateHelper::t ( '缺货' ) => [ 
										'url' => '../ebay-order/list?order_status=' . OdOrder::STATUS_OUTOFSTOCK 
								] 
						] 
				],
				
		        // 二次营销需要重新找其他邮件服务
// 				TranslateHelper::t('通知及营销')=>[
// 					'icon'=>'icon-stroe',
// 					'items'=>$OdLtMessageMenu,
//         		],
		];
		
		return $menu;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取ebay 订单统计  数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $status					指定状态筛选 （可选）
	 * @param $lasttime 				指定时间筛选 （可选）
	 +---------------------------------------------------------------------------------------------
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMenuStatisticData($params=[],$platform='ebay'){
		$uid = \Yii::$app->subdb->getCurrentPuid();
		$path = self::$MenuCachePath;
		$path .="_".$uid;
		$counter_cache = ConfigHelper::getGlobalConfig($path);
		if (!empty($counter_cache)){
			//return json_decode($counter_cache,true);
		}
		$counter = OrderHelper::getMenuStatisticData($platform,$params);
		
		$counter_cache = json_encode($counter);
		ConfigHelper::setGlobalConfig($path, $counter_cache);
		return $counter;
	}//end of function getMenuStatisticData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取ebay item 相片url
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $itemid							ebay item id  （必选）
	 * @param $product_attributes 				商品属性 （必选）
	 * @param $puid 							用户uid （必选）
	 +---------------------------------------------------------------------------------------------
	 * @return  string photo url
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getItemPhoto($itemid , $product_attributes, $puid){
		try {
			//->andWhere(['<=','expire_time' ,date("Y-m-d H:i:s",time()) ]) 目前暂时无过期时间
			$ebayItemPhoto = EbayItemPhotoQueue::find()->where(['itemid'=>$itemid , 'product_attributes'=>$product_attributes , 'puid'=>$puid ])->one();
			//print_r($ebayItemPhoto);
			
			if (empty($ebayItemPhoto)){
				$ebayItemPhoto = new EbayItemPhotoQueue();
				$ebayItemPhoto->itemid = (String)$itemid;
				$ebayItemPhoto->product_attributes = $product_attributes;
				
				$ebayItemPhoto->puid = $puid;
				$ebayItemPhoto->create_time = date("Y-m-d H:i:s",time());
				$ebayItemPhoto->status = 'P';
				$ebayItemPhoto->retry_count = 0;
				$ebayItemPhoto->photo_url = '';
				
				if ($ebayItemPhoto->save()){
					
				}else{
					echo json_encode($ebayItemPhoto->errors);
				}
			}
			
			return $ebayItemPhoto->photo_url;
		} catch (\Exception $e) {
			$errorMsg = "uid:$puid ".(__FUNCTION__)." ".$e->getMessage()." Line no:".$e->getLine();
			echo $errorMsg;
			\Yii::error($errorMsg,"file");
			return '';
		}
		
		
	}//end of function getItemPhoto
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更新订单商品图片
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $itemid							ebay item id  （必选）
	 * @param $product_attributes 				商品属性 （必选）
	 * @param $puid 							用户uid （必选）
	 * @param $photoUrl 						商品相片图片 （必选）
	 +---------------------------------------------------------------------------------------------
	 * @return array (true ,    //true 成功 false 失败
	 * 				'message' , //执行详细结果
	 * 				  	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function updateEbayOrderItemPhotoUrl($itemid , $product_attributes, $puid , $photoUrl,$isForce=true){
		try {
 
			//echo "\n sql=".OdOrderItem::find()->where(['photo_primary'=>'' , 'ifnull(product_attributes,"")'=>$product_attributes , 'order_source_itemid'=>$itemid ])->createCommand()->getRawSql();
			if ($isForce){
				$rt = OdOrderItem::updateAll(['photo_primary'=>$photoUrl],[ 'ifnull(product_attributes,"")'=>$product_attributes , 'order_source_itemid'=>$itemid ]);
			}else{
				$rt = OdOrderItem::updateAll(['photo_primary'=>$photoUrl],['ifnull(photo_primary,"")'=>'' , 'ifnull(product_attributes,"")'=>$product_attributes , 'order_source_itemid'=>$itemid ]);
			}
			
			return [true,'成功更新了'.$rt.'条订单记录'];
		} catch (\Exception $e) {
			$errorMsg = "uid:$puid ".(__FUNCTION__)." ".$e->getMessage()." Line no:".$e->getLine();
			\Yii::error($errorMsg,"file");
			return [false ,$errorMsg ];
		}
		
	}//end of function updateEbayOrderItemPhotoUrl
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更新ebay 无item订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $puid							puid  （必选）
	 +---------------------------------------------------------------------------------------------
	 * @return int  重拉订单条数 
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/04/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function retrieveOrderItem($puid){
		$total = 0;
		$sql =" select * from od_order_v2 where order_source = 'ebay'  and order_id not in (select order_id from od_order_item_v2) ";
		$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
		if (!empty($rts)){
			$updateSql = "update  od_ebay_transaction  a , od_order_v2 b  set a.order_id = b.order_id where a.orderid  = b.order_source_order_id and a.order_id is null and b.order_id is not null";
			$updateRT = \Yii::$app->subdb->createCommand($updateSql)->execute();
			//echo "\n no order id fix effect=".$updateRT;
			
			$updateSql = "update  od_paypal_transaction  a , od_order_v2 b  set a.order_id = b.order_id where a.ebay_orderid  = b.order_source_order_id and a.order_id is null and b.order_id is not null";
			$updateRT = \Yii::$app->subdb->createCommand($updateSql)->execute();
		
			foreach($rts as $rt){
				//echo "\n ".$puid.", ".$rt['order_id'].", ".$rt['order_source_site_id'].", ".$rt['order_status'];
				OrderBackgroundHelper::refreshEbayOrderItem($puid, $rt['order_id'], $rt['order_source_site_id'], $rt['order_status'] , $rt['order_source'] , $rt['selleruserid']);
				$total++;
			}
		}else{
			//echo " \n puid=".$puid.' is normal ';
		}
		return $total;
	}
	
	/**
	 * 自查订单地址是否异常
	 */
	static public function checkOrderAddress($selleruserid , $platformOrderID){
		
		try {
			$sql = "SELECT b.order_id ,   b.consignee_address_line1 , a.ship_street1 ,FROM_UNIXTIME(b.create_time,'%Y-%m-%d %H:%i:%s') , b.order_status  FROM `od_ebay_order` a , od_order_v2 b WHERE a.`ebay_orderid`  = b.order_source_order_id and b.consignee_address_line1 <> a.ship_street1 and b.origin_shipment_detail is null and b.order_source_order_id='$platformOrderID'";
			$rt = \Yii::$app->subdb->createCommand($sql)->queryAll();
			if (!empty($rt)){
				//地址异常
				$orderid = $rt[0]['order_id'];
			}else{
				return [false , '地址正常'];
			}
			
			return ['order_id'=>$orderid];
			
			if(empty($orderid)) return [false,'找不到订单号'];
			if($selleruserid){
				$eu=SaasEbayUser::findOne(['selleruserid'=>$selleruserid]);
			}else{
				return [false,'ebay账号'];
				//die('No selleruserid Input .');
			}
			
			$api=new getorders();
			$api->resetConfig($eu->DevAcccountID);
			$api->eBayAuthToken=$eu->token;
			$api->_before_request_xmlarray['OrderID']=$orderid;
			$r=$api->api();
			/*
			echo "<pre>";
			print_r(@$r['OrderArray']['Order']['ShippingAddress']);
			echo "</pre>";
			*/
			if (isset($r['OrderArray']['Order']['ShippingAddress'])){
				//$orderModel = OdOrder::find()->where(['order_source_order_id'=>$orderid ])->andWhere(['order_capture'=>'N'])->One();
				$orderModel = $_GET['erp_order_id'];
				echo "orderid = ".$orderModel->order_id;
				$addressArr = $r['OrderArray']['Order']['ShippingAddress'];
				$paypalAddress = [
				'consignee'=>$addressArr['Name'],
				//'consignee_email'=>$PT->email,
				'consignee_country'=>empty($addressArr['CountryName'])?$addressArr['Country']:$addressArr['CountryName'],
				'consignee_country_code'=>$addressArr['Country'],
				'consignee_province'=>$addressArr['StateOrProvince'],
				'consignee_city'=>$addressArr['CityName'],
				'consignee_address_line1'=>$addressArr['Street1'],
				'consignee_address_line2'=>$addressArr['Street2'],
				'consignee_postal_code'=>$addressArr['PostalCode'],
					
				];
				$updateRt = \eagle\modules\order\helpers\OrderUpdateHelper::updateOrder($orderModel, $paypalAddress , false , 'System','地址同步','order');
					
				return [true,$updateRt];
			}
		} catch (\Exception $e) {
			$message = __function__.' error message='.$e->getMessage()." line no=".$e->getLine();
			\Yii::error($message,'file');
			return [false,$message];
			
		}
		
		
	}
}