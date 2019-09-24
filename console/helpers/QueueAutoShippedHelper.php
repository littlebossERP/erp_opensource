<?php
namespace console\helpers;

use \Yii;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\SaasEbayUser;
use common\api\ebayinterface\completesale;
use eagle\modules\order\models\OdEbayOrder;
use common\api\wishinterface\WishInterface_Helper;
use common\api\dhgateinterface\Dhgateinterface_Api;
use eagle\models\SaasDhgateUser;
use eagle\models\SaasWishUser;
use common\api\cdiscountinterface\CdiscountInterface_Helper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\order\models\CdiscountOrderDetail;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use common\api\lazadainterface\LazadaInterface_Helper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\models\SaasEnsogoUser;
use common\api\ensogointerface\EnsogoInterface_Helper;
use eagle\models\SaasPriceministerUser;
use eagle\modules\order\helpers\PriceministerOrderInterface;
use eagle\modules\order\models\PriceministerOrderDetail;
use eagle\models\SaasBonanzaUser;
use eagle\modules\order\models\BonanzaOrderDetail;
use eagle\modules\order\helpers\BonanzaOrderInterface;
use eagle\models\QueueSyncshipped;
use eagle\models\SaasNeweggUser;
use common\api\newegginterface\NeweggInterface_Helper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;
use common\api\lazadainterface\LazadaInterface_Helper_V2;
use common\api\shopeeinterface\ShopeeInterface_Api;
use eagle\models\SaasShopeeUser;
use eagle\modules\order\helpers\OrderHelper;

/**
 +------------------------------------------------------------------------------
 * Aliexpress 数据同步类
 +------------------------------------------------------------------------------
 */
class QueueAutoShippedHelper {
	private static $AutoShipQueueVersion = '';
	
	public static function autoShipV2($platform){
		global $CACHE;
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
	
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
	
		$current_time=explode(" ",microtime());
		$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
	
		try {
			$connection=\Yii::$app->db;
			$command=$connection->createCommand('select `id`,`selleruserid`,`order_source`,`order_source_order_id` from  `queue_syncshipped` where `status` = 0 and order_source  in ("'.$platform.'")  order by `created` ASC limit 100 ');
			$rows=$command->queryAll();
			foreach($rows  as $row) {
	
				//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
				$currentAutoShipQueueVersion = ConfigHelper::getGlobalConfig("Order/AutoShipQueueVersion",'NO_CACHE');
				if (empty($currentAutoShipQueueVersion))
					$currentAutoShipQueueVersion = 0;
	
				//如果自己还没有定义，去使用global config来初始化自己
				if (empty(self::$AutoShipQueueVersion))
					self::$AutoShipQueueVersion = $currentAutoShipQueueVersion;
					
				//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
				if (self::$AutoShipQueueVersion <> $currentAutoShipQueueVersion){
					$msg = "Version new $currentAutoShipQueueVersion , this job ver ".self::$AutoShipQueueVersion." exits for using new version $currentAutoShipQueueVersion.";
	
					exit($msg);
				}
				echo " order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				$QssObj = QueueSyncshipped::findOne($row['id']);
				$success = false;
				$delivery_time = -1;
				echo "\n platform=".$platform;
				return ['success'=>true , 'message'=>'n/a'];
				switch ($row['order_source']){
					case 'ebay':
						QueueAutoShippedHelper::EbayShipped($QssObj);
						break;
					case 'amazon':
// 						AmazonApiHelper::AmazonShipped($QssObj);
						list($success,$delivery_time)=AmazonApiHelper::AmazonShipped($QssObj);
						break;
					case 'aliexpress':
						$success = QueueAutoShippedHelper::AliexpressShipped($QssObj);
						break;
					case 'wish':
						$success = QueueAutoShippedHelper::WishShipped($QssObj);
						break;
					case 'dhgate':
						$success = QueueAutoShippedHelper::DhgateShipped($QssObj);
						break;
					case 'cdiscount':
						QueueAutoShippedHelper::CdiscountShipped($QssObj);
						break;
					case 'lazada':
						$success = QueueAutoShippedHelper::LazadaShipped($QssObj);
						break;
					case 'linio':
						$success = QueueAutoShippedHelper::LinioShipped($QssObj);
						break;
					case 'jumia':
						$success = QueueAutoShippedHelper::JumiaShipped($QssObj);
						break;
					case 'ensogo':
						$success = QueueAutoShippedHelper::EnsogoShipped($QssObj);
						break;
					case 'newegg':
						$success = QueueAutoShippedHelper::NeweggShipped($QssObj);
						break;
					default:break;
				}
	
	
				if ($success){
					$syncShippedStatus = "C";
					$ActivePlatformList = ['aliexpress', 'wish' , 'dhgate','lazada' , 'linio' , 'jumia' , 'jumia','newegg'];
				}else{
					$syncShippedStatus = "F";
					//amazon 插入队列 也需要写上失败
					$ActivePlatformList = ['aliexpress', 'wish' , 'dhgate','lazada' , 'linio' , 'jumia' , 'jumia' , 'amazon','newegg'];
					echo "failure to sync ship then status set F  order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				}
				//amazon 为异步标记， 所以不在这里回写
				if (in_array($row['order_source'] ,$ActivePlatformList)){
					// 更新订单 虚拟发货 状态 start
					$syncRT = OrderApiHelper::setOrderSyncShippedStatus(OdOrderShipped::findOne($QssObj->osid)->order_id, $syncShippedStatus, $delivery_time);
					// 更新订单 虚拟发货 状态 end
					echo $row['order_source_order_id']." sync status= $syncShippedStatus ".print_r($syncRT,1);
				}
	
			}//end of foreach
			return ['success'=>true , 'message'=>'n/a'];
		} catch ( \Exception $ex ) {
			echo 'Error File:' . $ex->getFile().' , Error Line:' . $ex->getLine() .' , Error Message:' . $ex->getMessage () . "\n";
			return ['success'=>false , 'message'=>'Error File:' . $ex->getFile().' , Error Line:' . $ex->getLine() .' , Error Message:' . $ex->getMessage () . "\n"];
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 指定平台 进行虚拟发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/05/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function autoShip($platform){
		try {
			$connection=\Yii::$app->db;
			$command=$connection->createCommand('select `id`,`selleruserid`,`order_source`,`order_source_order_id` from  `queue_syncshipped` where `status` = 0 and order_source  in ("'.$platform.'")  order by `created` ASC');
			$dataReader=$command->query();
			while(($row=$dataReader->read())!==false) {
				echo " order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				$QssObj = QueueSyncshipped::findOne($row['id']);
				$success = false;
				$delivery_time = -1;
				//return ;
				switch ($row['order_source']){
					case 'ebay':
						QueueAutoShippedHelper::EbayShipped($QssObj);
						break;
					case 'amazon':
						//AmazonApiHelper::AmazonShipped($QssObj);
						list($success,$delivery_time)=AmazonApiHelper::AmazonShipped($QssObj);
						break;
					case 'aliexpress':
						$success = QueueAutoShippedHelper::AliexpressShipped($QssObj);
						break;
					case 'wish':
						$success = QueueAutoShippedHelper::WishShipped($QssObj);
						break;
					case 'dhgate':
						$success = QueueAutoShippedHelper::DhgateShipped($QssObj);
						break;
					case 'cdiscount':
						QueueAutoShippedHelper::CdiscountShipped($QssObj);
						break;
					case 'lazada':
						$success = QueueAutoShippedHelper::LazadaShipped($QssObj);
						break;
					case 'linio':
						$success = QueueAutoShippedHelper::LinioShipped($QssObj);
						break;
					case 'jumia':
						$success = QueueAutoShippedHelper::JumiaShipped($QssObj);
						break;
					case 'ensogo':
						$success = QueueAutoShippedHelper::EnsogoShipped($QssObj);
						break;
					case 'newegg':
						$success = QueueAutoShippedHelper::NeweggShipped($QssObj);
						break;
					default:break;
				}
		
		
				if ($success){
					$syncShippedStatus = "C";
					$ActivePlatformList = ['aliexpress', 'wish' , 'dhgate','lazada' , 'linio' , 'jumia' , 'jumia','newegg'];
				}else{
					$syncShippedStatus = "F";
					//amazon 插入队列 也需要写上失败
					$ActivePlatformList = ['aliexpress', 'wish' , 'dhgate','lazada' , 'linio' , 'jumia' , 'jumia' , 'amazon','newegg'];
					echo "failure to sync ship then status set F  order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				}
				//amazon 为异步标记， 所以不在这里回写
				if (in_array($row['order_source'] ,$ActivePlatformList)){
					// 更新订单 虚拟发货 状态 start
					$syncRT = OrderApiHelper::setOrderSyncShippedStatus(OdOrderShipped::findOne($QssObj->osid)->order_id, $syncShippedStatus, $delivery_time);
					// 更新订单 虚拟发货 状态 end
					echo $row['order_source_order_id']." sync status= $syncShippedStatus ".print_r($syncRT,1);
				}
		
			}
		} catch ( \Exception $ex ) {
			echo 'Error File:' . $ex->getFile().' , Error Line:' . $ex->getLine() .' , Error Message:' . $ex->getMessage () . "\n";
		}
	}//end of function autoShip
	
	/**
	 * 速卖通标记发货
	 * @author million 2015-04-23
	 * 88028624@qq.com
	 * 
	 */
	public static function AliexpressShipped($queueSyncShippedObj){
		//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
		$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($queueSyncShippedObj->selleruserid);
		if($is_aliexpress_v2){
			$result = self::AliexpressShippedV2($queueSyncShippedObj);
			return $result;
		}
		//****************判断此账号信息是否v2版    end*************
		
		// 检查授权是否过期或者是否授权,返回true，false
		$a = AliexpressInterface_Auth::checkToken ( $queueSyncShippedObj->selleruserid );
		// 同步成功保存数据到订单表
		if ($a) {
			$api = new AliexpressInterface_Api ();
			$access_token = $api->getAccessToken ( $queueSyncShippedObj->selleruserid );
			$api->access_token = $access_token;
			
			if (isset($queueSyncShippedObj->api_type) && ($queueSyncShippedObj->api_type >1) ){
				$oldServiceName = '';
				$oldLogisticsNo = '';
				//修改发货通知
				if (true){
					$OldShip = OdOrderShipped::find()->where(['order_source_order_id'=>$queueSyncShippedObj->order_source_order_id , 'status'=>'1'])->orderBy('created desc')->one();
					$oldServiceName = $OldShip->shipping_method_code;
					$oldLogisticsNo = $OldShip->tracking_number;
				}
				$param = array(
						'oldServiceName' => $oldServiceName,
						'oldLogisticsNo' => $oldLogisticsNo,
						'newServiceName' => $queueSyncShippedObj->shipping_method_code,
						'newLogisticsNo' => $queueSyncShippedObj->tracking_number,
						'description'=>$queueSyncShippedObj->description,
						'sendType'=>$queueSyncShippedObj->signtype,
						'outRef'=>$queueSyncShippedObj->order_source_order_id,
						'trackingWebsite'=>$queueSyncShippedObj->tracking_link
				);
				echo "\n modify selleruserid :".$queueSyncShippedObj->selleruserid."order source id:".$queueSyncShippedObj->order_source_order_id." param:".print_r($param,true);
				$result = $api->sellerModifiedShipment($param);
			}else{
				//填写发货通知
				// 接口传入参数
				$param = array (
						'serviceName' => $queueSyncShippedObj->shipping_method_code,//平台运输服务代码
						'logisticsNo' => $queueSyncShippedObj->tracking_number,//物流号
						'description' => $queueSyncShippedObj->description,//备注 速卖通只能是英文
						'sendType' => $queueSyncShippedObj->signtype,//发货类型 目前就速卖通用到
						'outRef' => $queueSyncShippedObj->order_source_order_id,//平台订单号
						'trackingWebsite' => $queueSyncShippedObj->tracking_link//物流查询网址
				);
					
				echo "\n ship selleruserid :".$queueSyncShippedObj->selleruserid."order source id:".$queueSyncShippedObj->order_source_order_id." param:".print_r($param,true);
				// 调用接口获取订单列表
				$result = $api->sellerShipment ( $param );
			}
			
			
			print_r($result);
			if (isset($result ['success']) && $result ['success']==true) { // 成功
				if (true){
					$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
					if (!is_null($osObj)){
						$osObj->status = 1;
						$osObj->result = 'true';
						$osObj->errors = '';
						$osObj->updated = time();
						$osObj->lasttime = time();
						$osObj->save();
					}
					$queueSyncShippedObj->delete ();
				}
				return true;
			} else {
				if (true){
					$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
					if (!is_null($osObj)){
						$osObj->status = 2;
						$osObj->result = 'false';
						$osObj->errors = $result['error_message'];
						$osObj->updated = time();
						$osObj->lasttime = time();
						$osObj->save();
					}
					echo "\n order source id:".$queueSyncShippedObj->order_source_order_id." error=".$result['error_message']." queue".print_r($queueSyncShippedObj,true);
					$queueSyncShippedObj->delete ();
				}
				return false;
			}
		} else {
			echo $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
					if (!is_null($osObj)){
						$osObj->status = 2;
						$osObj->result = 'false';
						$osObj->errors = $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!';
						$osObj->updated = time();
						$osObj->lasttime = time();
						$osObj->save();
					}
					$queueSyncShippedObj->delete ();
			}
			return false;
		}
	}
	
	public static function AliexpressShippedV2($queueSyncShippedObj){
		// 检查授权是否过期或者是否授权,返回true，false
		$a = AliexpressInterface_Helper_Qimen::checkToken ( $queueSyncShippedObj->selleruserid );
		// 同步成功保存数据到订单表
		if ($a) {
			$api = new AliexpressInterface_Api_Qimen();
				
			if (isset($queueSyncShippedObj->api_type) && ($queueSyncShippedObj->api_type >1) ){
				$oldServiceName = '';
				$oldLogisticsNo = '';
				//修改发货通知
				if (true){
					$OldShip = OdOrderShipped::find()->where(['order_source_order_id'=>$queueSyncShippedObj->order_source_order_id , 'status'=>'1'])->orderBy('created desc')->one();
					$oldServiceName = $OldShip->shipping_method_code;
					$oldLogisticsNo = $OldShip->tracking_number;
				}
				$param = [
					'id' => $queueSyncShippedObj->selleruserid,
					'old_service_name' => $oldServiceName,
					'old_logistics_no' => $oldLogisticsNo,
					'new_service_name' => $queueSyncShippedObj->shipping_method_code,
					'new_logistics_no' => $queueSyncShippedObj->tracking_number,
					'description'=>$queueSyncShippedObj->description,
					'send_type'=>$queueSyncShippedObj->signtype,
					'out_ref'=>$queueSyncShippedObj->order_source_order_id,
					'tracking_website'=>$queueSyncShippedObj->tracking_link
				];
				echo "\n modify selleruserid :".$queueSyncShippedObj->selleruserid."order source id:".$queueSyncShippedObj->order_source_order_id." param:".print_r($param,true);
				$result = $api->sellermodifiedshipmentfortop($param);
			}else{
				//填写发货通知
				// 接口传入参数
				$param = [
					'id' => $queueSyncShippedObj->selleruserid,
					'service_name' => $queueSyncShippedObj->shipping_method_code,//平台运输服务代码
					'logistics_no' => $queueSyncShippedObj->tracking_number,//物流号
					'description' => $queueSyncShippedObj->description,//备注 速卖通只能是英文
					'send_type' => $queueSyncShippedObj->signtype,//发货类型 目前就速卖通用到
					'out_ref' => $queueSyncShippedObj->order_source_order_id,//平台订单号
					'tracking_website' => $queueSyncShippedObj->tracking_link//物流查询网址
				];
					
				echo "\n ship selleruserid :".$queueSyncShippedObj->selleruserid."order source id:".$queueSyncShippedObj->order_source_order_id." param:".print_r($param,true);
				// 调用接口获取订单列表
				$result = $api->sellershipmentfortop ( $param );
			}
				
				
			print_r($result);
			if (isset($result ['result_success']) && $result ['result_success']==true) { // 成功
				if (true){
					$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
					if (!is_null($osObj)){
						$osObj->status = 1;
						$osObj->result = 'true';
						$osObj->errors = '';
						$osObj->updated = time();
						$osObj->lasttime = time();
						$osObj->save();
					}
					$queueSyncShippedObj->delete ();
				}
				return true;
			} else {
				if (true){
					$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
					if (!is_null($osObj)){
						$osObj->status = 2;
						$osObj->result = 'false';
						$osObj->errors = $result['error_message'];
						$osObj->updated = time();
						$osObj->lasttime = time();
						$osObj->save();
					}
					echo "\n order source id:".$queueSyncShippedObj->order_source_order_id." error=".$result['error_message']." queue".print_r($queueSyncShippedObj,true);
					$queueSyncShippedObj->delete ();
				}
				return false;
			}
		} else {
			echo $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				$queueSyncShippedObj->delete ();
			}
			return false;
		}
	}
	
	
	/**
	 * eBay标记发货
	 * @author fanjs
	 */
	public static function EbayShipped($queueSyncShippedObj){
		global $LOG_CONTENT;
		$ebayuser = SaasEbayUser::findOne(['selleruserid'=>$queueSyncShippedObj->selleruserid]);
		if (!empty($ebayuser)) {
			 
			$api = new completesale();
			$api->resetConfig($ebayuser->DevAcccountID);
			$api->eBayAuthToken = $ebayuser->token;
//			$odebayorder = OdEbayOrder::findOne(['ebay_orderid'=>$queueSyncShippedObj->order_source_order_id]);
//			$api->setOrder($odebayorder);
			foreach ($queueSyncShippedObj->params as $v){
				$api->ItemID=$v['itemid'];
				$api->TransactionID=$v['transactionid'];
			// 调用接口获取订单列表
				$LOG_CONTENT['puid'] = $queueSyncShippedObj->uid;
				$LOG_CONTENT['trackno'] = $queueSyncShippedObj->tracking_number;
				$LOG_CONTENT['ship_method_code'] = $queueSyncShippedObj->shipping_method_code;
				$LOG_CONTENT['itemid'] =$v['itemid'];
				$LOG_CONTENT['transactionid'] =$v['transactionid'];
			$result = $api->shipped($queueSyncShippedObj->tracking_number,$queueSyncShippedObj->shipping_method_code,@$queueSyncShippedObj->description,null);
			print_r($result);	
			}	
			if ($api->responseIsSuccess()) { // 成功
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 1;
					$osObj->result = 'true';
					$osObj->errors = '';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				$queueSyncShippedObj->status=1;
				$queueSyncShippedObj->save (false);
				return true;
			} else {
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = isset($result['Errors']['LongMessage'])?$result['Errors']['LongMessage']:$result['Errors']['0']['LongMessage'];
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				$queueSyncShippedObj->status=2;
				$queueSyncShippedObj->save (false);
				return false;
			}
		} else {
			echo $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . ' has not found!';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				$queueSyncShippedObj->status=2;
				$queueSyncShippedObj->save (false);
			}
			return false;
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Wish 发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model 对象 
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function WishShipped($queueSyncShippedObj){
		$user_p= $queueSyncShippedObj->params;
		if( isset( $user_p['order_point_origin'] ) ){
			$order_point_origin= $user_p['order_point_origin'];
		}else{
			$order_point_origin= '';
		}
		$wishUser = SaasWishUser::findOne(['store_name'=>$queueSyncShippedObj->selleruserid , 'uid'=>$queueSyncShippedObj->uid]);
		//找出 wish 账号相关信息
		if (!empty($wishUser)) {
			 
			//"order_id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""
			$params = [	
					'order_id'	=>$queueSyncShippedObj->order_source_order_id ,  //平台订单号
					"tracking_provider"  => $queueSyncShippedObj->shipping_method_code, //平台运输服务代码
					"tracking_number"	=> $queueSyncShippedObj->tracking_number,//物流号
					"ship_note"	=> $queueSyncShippedObj->description, //备注
					'origin_country_code'=>$order_point_origin
			];
			echo 'uid='.$queueSyncShippedObj->uid." ";//test kh
			
			//找出 发货执行成功记录的数量
			$ShippedCount = OdOrderShipped::find()->where(['order_source_order_id'=>$queueSyncShippedObj->order_source_order_id , 'result'=>'true'])->count();
			echo "ship Count = $ShippedCount <br> ";//test kh
			
			if ($ShippedCount>0){
				//发货执行成功记录的数量 大于0时表示该订单已经发货了 , 本次调用api是为了修改物流信息
				$result = WishInterface_Helper::updateTrackingInfo($wishUser->token , $params);
			}else{
				//发货执行成功记录的数量 等 于0时表示该订单未发货 , 本次调用api是为了订单发货
				$result = WishInterface_Helper::ShippedOrder($wishUser->token , $params);
			}
			
			if (is_string($result))
				$result = json_decode($result,true);
			
			//当proxy返回结果为已经已经发货  并刚刚 调用 的api 为 发货的情况下改用 修改物流信息 更新一次
			if (!empty($result['proxyResponse']['message']) ){
				echo " check again <br>";//test kh
				//返回 结果中 告知 已经发货了 则使用修改订单发货信息接口
				if (stripos($result['proxyResponse']['message'], 'has been fulfilled')){
					echo 'update again';//test kh
					$result = WishInterface_Helper::updateTrackingInfo($wishUser->token , $params);
					
					if (is_string($result))
						$result = json_decode($result,true);
				}
				
				if (stripos($result['proxyResponse']['message'], 'in SHIPPED state')  || stripos($result['proxyResponse']['message'], 'current state is: APPROVED') 
				|| stripos($result['proxyResponse']['message'], 'cannot be updated') ){
					//返回 结果中 告知 未发货  则使用订单发货接口
					echo 'shiporder again';//test kh
					$result = WishInterface_Helper::ShippedOrder($wishUser->token , $params);
						
					if (is_string($result))
						$result = json_decode($result,true);
				}
			}else{
				echo "no message <br>";//test kh
			}
			
			echo "\n v3  ";//test kh
			var_dump($result);
			echo "\n param:".json_encode($params);
			if (!empty($result['success']) && !empty($result['proxyResponse']['success']) && !empty($result['proxyResponse']['wishReturn']['data']['success'])) { 
				// 调用wish proxy 成功 同时 wish proxy 返回成功
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 1;
					$osObj->result = 'true';
					$osObj->errors = '';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//testkh20150709 wish 调试发货暂时不删除数据 
				$queueSyncShippedObj->status = 1;//成功发货
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
				echo "\n sucess ";
				return true;
			} else {
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = json_encode($result);
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//testkh20150709 wish 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 2;//发货失败
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
				echo "\n failure ";
				echo "\n  condion1 ".print_r((!empty($result['success'])),1) ;
				echo "\n  condion2 ".print_r((!empty($result['proxyResponse']['success'])),1) ;
				echo "\n  condion3 ".print_r((!empty($result['proxyResponse']['wishReturn']['data']['success'])),1) ;
				return false;
			}
		} else {
			echo $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . ' has not found!';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//testkh20150709 wish 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 2;//发货失败
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
			}
			return false;
		}
	}//end of WishShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 敦煌标记发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model QueueSyncshipped 对象
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/06/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function DhgateShipped($queueSyncShippedObj){
		$SDU = SaasDhgateUser::findOne(['sellerloginid'=>$queueSyncShippedObj->selleruserid]);
		if(empty($SDU)){
			echo $queueSyncShippedObj->selleruserid . ' 账号不存在' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . ' 账号不存在';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
					\Yii::error('DhgateShipped uid:'.$queueSyncShippedObj->uid .','. $queueSyncShippedObj->selleruserid . ' 账号不存在',"file");
				}
				$queueSyncShippedObj->status = 2;//发货失败
				$queueSyncShippedObj->save();
// 				$queueSyncShippedObj->delete (); // dzt20151020 不删除发货队列记录,for bug 追踪
			}
		}
		  
		$api = new Dhgateinterface_Api();
		
		$deliveryState = 1;
		if('part' == $queueSyncShippedObj->signtype){// eagle 记录下来的全部发货为 all ,部分发货为 part
			$deliveryState = 2;
		}
		
		// 接口传入参数
		$appParams = array (
				'deliveryNo' => $queueSyncShippedObj->tracking_number, // 运单号。对应UPS的运单号；示例值：1Z68A9X70467731838
				'deliveryRemark' => $queueSyncShippedObj->description, // 运单备注。备注信息，不允许有中文，备注长度不大于1000，可选
				'deliveryState' => $deliveryState, // 发货状态。1、全部发货，2、部分发货
				'orderNo' => $queueSyncShippedObj->order_source_order_id, // 订单号。不要和订单ID混淆，订单号没有十六进制字符；示例值：1330312162
				'shippingType' => $queueSyncShippedObj->shipping_method_code, //运输方式。运输方式可以通过接口dh.shipping.types.get获取；示例值：UPS
		);
		// 调用接口获取订单列表
		$result = $api->shipOrder($SDU->dhgate_uid, $appParams);
		
		if ($result ['success'] && $result ['response']['result'] == 'SUCCESS') { // 成功
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
				$osObj->status = 1;
				$osObj->result = 'true';
				$osObj->errors = '';
				$osObj->updated = time();
				$osObj->lasttime = time();
				$osObj->save();
			}
			$queueSyncShippedObj->status = 1;//成功发货
			$queueSyncShippedObj->save();
// 			$queueSyncShippedObj->delete ();// dzt20151020 不删除发货队列记录,for bug 追踪
			return true;
		} else {
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
				$osObj->status = 2;
				$osObj->result = 'false';
				$osObj->errors = $result['error_message'];
				$osObj->updated = time();
				$osObj->lasttime = time();
				$osObj->save();
			}
			\Yii::error('DhgateShipped uid:'.$queueSyncShippedObj->uid .','. $result['error_message'],"file");
			$queueSyncShippedObj->status = 2;//发货失败
			$queueSyncShippedObj->save();
// 			$queueSyncShippedObj->delete ();// dzt20151020 不删除发货队列记录,for bug 追踪
			return false;
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Cdiscount 发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model 对象
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/7/13		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function CdiscountShipped($queueSyncShippedObj){
		$journal_id = SysLogHelper::InvokeJrn_Create("Delivery", __CLASS__, __FUNCTION__ ,array($queueSyncShippedObj));
		$cdiscountUser=SaasCdiscountUser::find()->where(['uid'=>$queueSyncShippedObj->uid,'username'=>$queueSyncShippedObj->selleruserid])->one();
		//找出 wish 账号相关信息
		if (!empty($cdiscountUser)) {
			 
			
			$items=[];
			//改为查SRC Order Detail表，避免别名问题
			$orderDetails = CdiscountOrderDetail::find()->where(['ordernumber'=>$queueSyncShippedObj->order_source_order_id])->asArray()->all();
			$nonDeliverySku = CdiscountOrderInterface::getNonDeliverySku();
			$sellerproductid = [];
			foreach ($orderDetails as $v){
				if(in_array($v['sellerproductid'], $sellerproductid))
					continue;
				if(!in_array(strtoupper($v['sellerproductid']),$nonDeliverySku) && !empty($v['sellerproductid'])){
					$items[]=array("SellerProductId"=>$v['sellerproductid']);
					$sellerproductid[]=$v['sellerproductid'];
				}
			}
			
			$params = [
				"orderid"=>$queueSyncShippedObj->order_source_order_id ,  //平台订单号
				"CarrierName" => $queueSyncShippedObj->shipping_method_code, //平台运输服务代码
				"TrackingNumber"=> $queueSyncShippedObj->tracking_number,//物流号
				"TrackingUrl"=> $queueSyncShippedObj->tracking_link,
				"items"=>$items,//产品明细
			];
			//echo "liang1 <br> ";//test liang
				
			//找出 发货执行成功记录的数量
			$ShippedObj = OdOrderShipped::find()->where(['order_source_order_id'=>$queueSyncShippedObj->order_source_order_id , 'result'=>'true','status'=>1])->One();
				
			if (!empty($ShippedObj)){
				//echo "liang1-a <br> ";//test liang
				//发货执行成功记录的数量 大于0时表示该订单已经发货了 , CD不支持修改物流信息
				$ShippedObj->errors = '已经标记发货了，请勿重复标记';
				$ShippedObj->updated = time();
				$ShippedObj->lasttime = time();
				$ShippedObj->save();

				$queueSyncShippedObj->status = 1;//成功发货
				$queueSyncShippedObj->save();
				SysLogHelper::InvokeJrn_UpdateResult($journal_id,array('success'=>true,'message'=>'已经标记发货了，请勿重复标记'));
				return true;
			}else{
				//echo "liang1-b <br> ";//test liang
				//发货执行成功记录的数量 等 于0时表示该订单未发货 , 本次调用api是为了订单发货
				$result = CdiscountInterface_Helper::ShippedOrder($cdiscountUser->token , $params);
				
				if (is_string($result))
					$result = json_decode($result,true);
				
				if(empty($result['success'])){
					//重试一次
					$result = CdiscountInterface_Helper::ShippedOrder($cdiscountUser->token , $params);
				}
			}
				
			if (is_string($result))
				$result = json_decode($result,true);

			/*
			//当proxy返回结果为已经已经发货  并刚刚 调用 的api 为 发货的情况下改用 修改物流信息 更新一次
			if (!empty($result['proxyResponse']['message']) && $ShippedObj<>0){
				//echo " check again <br>";//test liang
				//返回 结果中 告知 已经发货了 则使用修改订单发货信息接口
				if (stripos($result['proxyResponse']['message'], 'has been fulfilled')){
					//echo 'update again';//test liang
					$result = CdiscountInterface_Helper::updateTrackingInfo($cdiscountUser->token , $params);
									
					if (is_string($result))
						$result = json_decode($result,true);
				}
			}else{
				//echo "no message <br>";//test liang
			}
			*/
				
			//echo "liang2 <br> ";//test liang
			//print_r($result);
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
			$errorMsg = '';
			$errorLog='';
			//本地错误信息
			
			//重试一次依然失败
			if(empty($result['success'])){
				//echo "liang2-a <br> ";//test liang
				$errorMsg = "LocalError:".empty($result['message'])?'error message lost...':$result['message'];
				$errorLog = $errorMsg;
				//\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
			}
			//proxy错误信息
			if(empty($result['proxyResponse']['success'])){
				//echo "liang2-b <br> ";//test liang
				$errorMsg .= '代理连接失败！';
				$errorLog = $errorMsg.'  '.empty($result['proxyResponse']['message'])?'proxy error message lost...':$result['proxyResponse']['message'];
				//\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
			}
			
			//一种失败情况为返回proxyResponse['success']=true,但是proxyResponse['ValidateOrderList']=null的情况
			//可能是网络问题导致的
			if(empty($result['proxyResponse']['ValidateOrderList'])){
				//echo "liang2-c <br> ";//test liang
				$errorLog .= "API-orderError:API return null!";
				$errorMsg .= "标记失败：与CD后台数据接收不稳定，请重试几次或稍后再试。";
			}
			
			if(!empty($result['proxyResponse']['ValidateOrderList']['s_Body']['s_Fault']['faultstring'])){
				//echo "liang2-d <br> ";//test liang
				if(stripos($result['proxyResponse']['ValidateOrderList']['s_Body']['s_Fault']['faultstring'], "unable to process the request due to an internal error")){
					$errorLog .= $result['proxyResponse']['ValidateOrderList']['s_Body']['s_Fault']['faultstring'];
					$errorMsg .="标记失败：CD接口接受参数时返回报错，可能参数中存在特殊符号。";
				}else{
					$errorLog .= $result['proxyResponse']['ValidateOrderList']['s_Body']['s_Fault']['faultstring'];
					$errorMsg .= "标记失败：".$result['proxyResponse']['ValidateOrderList']['s_Body']['s_Fault']['faultstring'];
				}
			}
			
			//echo "liang3 <br> ";//test liang
			//api返回的错误信息-order层面
			if(!empty($result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['Errors']['Error'])){
				$order_err_msg = $result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['Errors']['Error']['Message'];
				
				//echo "liang3-a <br> ";//test liang
				$errorMsg .= "API-orderError:".$order_err_msg;
				$errorLog = $errorMsg.(!empty($result['url'])?'  '.$result['url']:'');
				//\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
				//CD后台已经为发货状态
				if(stripos($errorMsg, "Le passage à l'état Shipped est impossible à ce stade de la commande")){
					$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
					//echo "liang3-a-1 \n ".print_r($osObj);//test liang
					if (!empty($osObj)){
						$osObj->status = 1;
						$osObj->result = 'true';
						$osObj->errors = '该订单的后台状态已经不再刻意标记发货了。';
						$osObj->updated = time();
						$osObj->lasttime = time();
						$osObj->save();
					}
					//echo "liang3-a-2 \n ".print_r($queueSyncShippedObj);//test liang
					//testkh20150709 cdiscount 调试发货暂时不删除数据
					$queueSyncShippedObj->status = 1;//成功发货
					$queueSyncShippedObj->save();
					return true;
				}
			}
			//api返回的错误信息-item层面
			if(!empty($result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['ValidateOrderLineResults']['ValidateOrderLineResult']['Errors']['Error'])){
				//echo "liang3-b <br> ";//test liang
				if(stripos($result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['ValidateOrderLineResults']['ValidateOrderLineResult']['Errors']['Error']['Message'], "Sequence contains no elements")){
					$errorMsg .="标记失败：与CD后台数据接收不稳定，请重试几次。";
				}
				else
					$errorMsg .= "API-orderItemError:".$result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['ValidateOrderLineResults']['ValidateOrderLineResult']['Errors']['Error']['Message'];
				$errorLog = $errorMsg.(!empty($result['url'])?'  '.$result['url']:'');
				//\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
			}
				
			//echo "liang4 <br> ";//test liang
			if (!empty($result['success']) && !empty($result['proxyResponse']['success']) && empty($errorMsg)) {
				// 调用cdiscount proxy 成功 同时 cdiscount proxy 返回成功
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				//echo "liang4-a \n ".print_r($osObj);//test liang
				if (!empty($osObj)){
					$osObj->status = 1;
					$osObj->result = 'true';
					$osObj->errors = '';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//testkh20150709 cdiscount 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 1;//成功发货
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
				return true;
			} else {
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if((int)$queueSyncShippedObj->id==1445918 || (int)$queueSyncShippedObj->id==1445919){
					echo "liang4-a \n ".$errorMsg;
					echo "liang4-b \n ".print_r($osObj);//test liang
				}
				
				if (!empty($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $errorMsg;
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//echo "liang4-b-2 \n ".print_r($queueSyncShippedObj);//test liang
				//testkh20150709 wish 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 2;//发货失败
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
				if(!empty($errorLog))
					SysLogHelper::SysLog_Create("Cdiscount", __CLASS__,__FUNCTION__,"",$errorLog);
				
				return false;
			}
		} else {
			//echo "liang5 <br> ";//test liang
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, array('success'=>false,'message'=>'Unauthorized or expired!'));
			echo $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!empty($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . ' has not found!';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//testkh20150709 wish 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 2;//发货失败
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
			}
			return false;
		}
	}//end of CdiscountShipped
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Lazada标记发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model QueueSyncshipped 对象
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/09/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function LazadaShipped($queueSyncShippedObj){
		$params = $queueSyncShippedObj->params;
		$nowTime = time();
		
		 
		if(!isset($params['sourceSite'])){// lazada需要按 order item 来发货，所以item为空这里控制不允许发货。
		    $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
		    if (!is_null($osObj)){
		    	$osObj->status = 2;
		        $osObj->result = 'false';
		        $osObj->errors = '发货队列的params没有包含sourceSite';
		        $osObj->updated = $nowTime;
		        $osObj->lasttime = $nowTime;
		        $osObj->save();
		    }
		    // 调试发货暂时不删数据
		    $queueSyncShippedObj->status = 2;// 发货失败
		    $queueSyncShippedObj->save();
		    return false;
		}		
		
		$codeNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping();
		$code2CodeMap = array_flip( LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP);
		
		$SLU = SaasLazadaUser::findOne(['platform_userid'=>$queueSyncShippedObj->selleruserid,'lazada_site'=>$code2CodeMap[$params['sourceSite']],'status'=>1]);
		
		// dzt20190426 cb支持
		if (empty($SLU)) {
		    $SLU = SaasLazadaUser::findOne(['platform_userid' => $queueSyncShippedObj->selleruserid, 'lazada_site' => 'cb' ,'status'=>1]);
			if(!empty($SLU)){
                $lazadaSites = json_decode($SLU->country_user_info, true);
                $lazadaSitesMap = Helper_Array::toHashmap($lazadaSites, 'country');
                if(empty($lazadaSitesMap[strtolower($params['sourceSite'])]))
                    $SLU = null;
            }
		}
		
		if(empty($SLU)){
		    if($params['sourceSite'] == 'ID' || $params['sourceSite'] == 'TH'){//由于新接口，国家代码变为两位，需要兼容旧接口，
		        $newMap = ['ID'=>'id','TH'=>'th'];
		        $SLU = SaasLazadaUser::findOne(['platform_userid'=>$queueSyncShippedObj->selleruserid,'lazada_site'=>$newMap[$params['sourceSite']],'status'=>1]);
		    }
		}
		if(empty($SLU)){
			echo $queueSyncShippedObj->selleruserid . '站点:'.$codeNameMap[$code2CodeMap[$params['sourceSite']]].' 账号不存在' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . '站点:'.$codeNameMap[$code2CodeMap[$params['sourceSite']]].' 账号不存在';
					$osObj->updated = $nowTime;
					$osObj->lasttime = $nowTime;
					$osObj->save();
				}
				// 调试发货暂时不删数据
				$queueSyncShippedObj->status = 2;// 发货失败
				$queueSyncShippedObj->save();
			}
			return false;
		}
	
		if(empty($params['items'])){// lazada需要按 order item 来发货，所以item为空这里控制不允许发货。
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
				$osObj->status = 2;
				$osObj->result = 'false';
				$osObj->errors = '发货队列的发货items为空';
				$osObj->updated = $nowTime;
				$osObj->lasttime = $nowTime;
				$osObj->save();
			}
			// 调试发货暂时不删数据
			$queueSyncShippedObj->status = 2;// 发货失败
			$queueSyncShippedObj->save();
			return false;
		}
		
		// dzt20161222 不标记不适合标记发货的item
		$ignoreItems = array();
		$OrderItemIds = array();
		foreach ($params['items'] as $item){
		    if(!empty($item['platform_status'])
		            && !in_array($item['platform_status'], LazadaApiHelper::$CAN_SHIP_ORDERITEM_STATUS)){
		        $ignoreItems[] = $item['order_source_order_item_id'].'=>'.$item['platform_status'];
		    }else{
		        $OrderItemIds[] = $item['order_source_order_item_id'];
		    }
		}
		
		if(empty($OrderItemIds)){// XXX 或者没有要上传产品的不报错直接当成已经上传成功？
		    $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
		    if (!is_null($osObj)){
		        $osObj->status = 2;
		        $osObj->result = 'false';
		        $osObj->errors = "订单".$queueSyncShippedObj->order_source_order_id." 没有可发货的订单item。忽略的item及其状态为：".implode(',', $ignoreItems);
		        $osObj->updated = $nowTime;
		        $osObj->lasttime = $nowTime;
		        $osObj->save();
		    }
		    // 调试发货暂时不删数据
		    $queueSyncShippedObj->status = 2;// 发货失败
		    $queueSyncShippedObj->save();
		    return false;
		}
		
		$config = array(
			"userId"=>$SLU->platform_userid,
			"apiKey"=>$SLU->token,
			"countryCode"=>strtolower($params['sourceSite']),
		);
		
		// 接口传入参数
		$appParams = array (
			'OrderItemIds' => implode(',', $OrderItemIds), // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
			'DeliveryType' => "dropship", // 目前不清楚其他类型的 DeliveryType，先hardcode 为dropship。  DeliveryType:One of the following: 'dropship' - The seller will send out the package on his own; 'pickup' - Shop should pick up the item from the seller (cross-docking); 'send_to_warehouse' - The seller will send the item to the warehouse (crossdocking).
			'ShippingProvider' => $queueSyncShippedObj->shipping_method_code, // 
			'TrackingNumber' => $queueSyncShippedObj->tracking_number, // 运单号。对应UPS的运单号；示例值：1Z68A9X70467731838
		);
		
		// 调用接口获取订单列表
		if(!empty($ignoreItems)){
		    \Yii::info("LazadaShipped uid:".$queueSyncShippedObj->uid.",order:".$queueSyncShippedObj->order_source_order_id.",ignore items：".implode(',', $ignoreItems) , "file");
		}
		\Yii::info("LazadaShipped ready to proxy.config:".json_encode($config)." appParams:".json_encode($appParams) , "file");
		if(!empty($SLU->version)){//新接口发货
		    $config['apiKey'] = $SLU->access_token;//新接口授权，用新的token
		    $result = LazadaInterface_Helper_V2::shipOrder($config,$appParams);
		}else{//旧接口发货
		    $result = LazadaInterface_Helper::shipOrder($config,$appParams);
		}
		\Yii::info("LazadaShipped rerun from proxy.result:".json_encode($result) , "file");
// 		print_r($result);
		if ($result ['success'] && $result['response']['success'] == true) { // 成功
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
				$osObj->status = 1;
				$osObj->result = 'true';
				$osObj->errors = '';
				$osObj->updated = $nowTime;
				$osObj->lasttime = $nowTime;
				$osObj->save();
			}
			// 调试发货暂时不删数据
			$queueSyncShippedObj->status = 1;// 发货失败
			$queueSyncShippedObj->save();
			return true;
		} else {
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
				$osObj->status = 2;
				$osObj->result = 'false';
				$osObj->errors = $result['message'];// $result['response']['success'] == false 的话，$rtn['response']['message'] 也被记录在$result['message']里面
				$osObj->updated = $nowTime;
				$osObj->lasttime = $nowTime;
				$osObj->save();
			}
			// 调试发货暂时不删数据
			$queueSyncShippedObj->status = 2;// 发货失败
			$queueSyncShippedObj->save();
			return false;
		}
	}// end of LazadaShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Linio标记发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model QueueSyncshipped 对象
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/11/05				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function LinioShipped($queueSyncShippedObj){
		$params = $queueSyncShippedObj->params;
		$nowTime = time();
		
	 
		if(!isset($params['sourceSite'])){// lazada需要按 order item 来发货，所以item为空这里控制不允许发货。
		    $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
		    if (!is_null($osObj)){
		    	$osObj->status = 2;
		        $osObj->result = 'false';
		        $osObj->errors = '发货队列的params没有包含sourceSite';
		        $osObj->updated = $nowTime;
		        $osObj->lasttime = $nowTime;
		        $osObj->save();
		    }
		    // 调试发货暂时不删数据
		    $queueSyncShippedObj->status = 2;// 发货失败
		    $queueSyncShippedObj->save();
		    return false;
		}		
		
		$codeNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
		$SLU = SaasLazadaUser::findOne(['platform_userid'=>$queueSyncShippedObj->selleruserid,'lazada_site'=>strtolower($params['sourceSite'])]);
		if(empty($SLU)){
			echo $queueSyncShippedObj->selleruserid . '站点:'.$codeNameMap[strtolower($params['sourceSite'])].' 账号不存在' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . '站点:'.$codeNameMap[strtolower($params['sourceSite'])].' 账号不存在';
					$osObj->updated = $nowTime;
					$osObj->lasttime = $nowTime;
					$osObj->save();
				}
				// 调试发货暂时不删数据
				$queueSyncShippedObj->status = 2;// 发货失败
				$queueSyncShippedObj->save();
			}
			return false;
		}
	
		if(empty($params['items'])){// lazada需要按 order item 来发货，所以item为空这里控制不允许发货。
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
				$osObj->status = 2;
				$osObj->result = 'false';
				$osObj->errors = '发货队列的发货items为空';
				$osObj->updated = $nowTime;
				$osObj->lasttime = $nowTime;
				$osObj->save();
			}
			// 调试发货暂时不删数据
			$queueSyncShippedObj->status = 2;// 发货失败
			$queueSyncShippedObj->save();
			return false;
		}
	
	    // dzt20161222 不标记不适合标记发货的item
		$ignoreItems = array();
		$OrderItemIds = array();
		foreach ($params['items'] as $item){
		    // @todo dzt20170210 订单同步出现问题，导致有订单item的platform_status 为200 ，这两天先屏蔽这个过滤，后面等状态恢复再开启
// 		    if(!empty($item['platform_status'])
// 		            && !in_array($item['platform_status'], LazadaApiHelper::$CAN_SHIP_ORDERITEM_STATUS)){
// 		        $ignoreItems[] = $item['order_source_order_item_id'].'=>'.$item['platform_status'];
// 		    }else{
		        $OrderItemIds[] = $item['order_source_order_item_id'];
// 		    }
		}
		
		if(empty($OrderItemIds)){// XXX 或者没有要上传产品的不报错直接当成已经上传成功？
		    $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
		    if (!is_null($osObj)){
		        $osObj->status = 2;
		        $osObj->result = 'false';
		        $osObj->errors = "订单".$queueSyncShippedObj->order_source_order_id." 没有可发货的订单item。忽略的item及其状态为：".implode(',', $ignoreItems);
		        $osObj->updated = $nowTime;
		        $osObj->lasttime = $nowTime;
		        $osObj->save();
		    }
		    // 调试发货暂时不删数据
		    $queueSyncShippedObj->status = 2;// 发货失败
		    $queueSyncShippedObj->save();
		    return false;
		}
	
		$config = array(
				"userId"=>$SLU->platform_userid,
				"apiKey"=>$SLU->token,
				"countryCode"=>$SLU->lazada_site
		);
	
		// 接口传入参数
		$appParams = array (
				'OrderItemIds' => implode(',', $OrderItemIds), // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
				'DeliveryType' => "dropship", // 目前不清楚其他类型的 DeliveryType，先hardcode 为dropship。  DeliveryType:One of the following: 'dropship' - The seller will send out the package on his own; 'pickup' - Shop should pick up the item from the seller (cross-docking); 'send_to_warehouse' - The seller will send the item to the warehouse (crossdocking).
				'ShippingProvider' => $queueSyncShippedObj->shipping_method_code, //
				'TrackingNumber' => $queueSyncShippedObj->tracking_number, // 运单号。对应UPS的运单号；示例值：1Z68A9X70467731838
		);
		
		// 调用接口获取订单列表
		if(!empty($ignoreItems)){
		    \Yii::info("LinioShipped uid:".$queueSyncShippedObj->uid.",order:".$queueSyncShippedObj->order_source_order_id.",ignore items：".implode(',', $ignoreItems) , "file");
		}
		\Yii::info("LinioShipped uid:".$queueSyncShippedObj->uid.",order:".$queueSyncShippedObj->order_source_order_id." ready to proxy.config:".json_encode($config)." appParams:".json_encode($appParams) , "file");
		$result = LazadaInterface_Helper::shipOrder($config,$appParams);
		\Yii::info("LinioShipped uid:".$queueSyncShippedObj->uid.",order:".$queueSyncShippedObj->order_source_order_id." rerun from proxy.result:".json_encode($result) , "file");
// 		print_r($result);
		if ($result ['success'] && $result['response']['success'] == true) { // 成功
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
			$osObj->status = 1;
			$osObj->result = 'true';
			$osObj->errors = '';
			$osObj->updated = $nowTime;
			$osObj->lasttime = $nowTime;
			$osObj->save();
			}
			// 调试发货暂时不删数据
			$queueSyncShippedObj->status = 1;// 发货失败
			$queueSyncShippedObj->save();
			return true;
	} else {
		$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
		if (!is_null($osObj)){
		$osObj->status = 2;
		$osObj->result = 'false';
		$osObj->errors = $result['message'];// $result['response']['success'] == false 的话，$rtn['response']['message'] 也被记录在$result['message']里面
		$osObj->updated = $nowTime;
		$osObj->lasttime = $nowTime;
		$osObj->save();
		}
		// 调试发货暂时不删数据
		$queueSyncShippedObj->status = 2;// 发货失败
		$queueSyncShippedObj->save();
		return false;
		}
	}// end of LinioShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Jumia标记发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model QueueSyncshipped 对象
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/11/05				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function JumiaShipped($queueSyncShippedObj){
		$params = $queueSyncShippedObj->params;
		$nowTime = time();
		 
		
		if(!isset($params['sourceSite'])){// lazada需要按 order item 来发货，所以item为空这里控制不允许发货。
		    $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
		    if (!is_null($osObj)){
		    	$osObj->status = 2;
		        $osObj->result = 'false';
		        $osObj->errors = '发货队列的params没有包含sourceSite';
		        $osObj->updated = $nowTime;
		        $osObj->lasttime = $nowTime;
		        $osObj->save();
		    }
		    // 调试发货暂时不删数据
		    $queueSyncShippedObj->status = 2;// 发货失败
		    $queueSyncShippedObj->save();
		    return false;
		}		
		$codeNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
	
		$SLU = SaasLazadaUser::findOne(['platform_userid'=>$queueSyncShippedObj->selleruserid,'lazada_site'=>strtolower($params['sourceSite'])]);
		if(empty($SLU)){
			echo $queueSyncShippedObj->selleruserid . '站点:'.$codeNameMap[$params['sourceSite']].' 账号不存在' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . '站点:'.$codeNameMap[strtolower($params['sourceSite'])].' 账号不存在';
					$osObj->updated = $nowTime;
					$osObj->lasttime = $nowTime;
					$osObj->save();
				}
				// 调试发货暂时不删数据
				$queueSyncShippedObj->status = 2;// 发货失败
				$queueSyncShippedObj->save();
			}
			return false;
		}
	
		if(empty($params['items'])){// lazada需要按 order item 来发货，所以item为空这里控制不允许发货。
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
				$osObj->status = 2;
				$osObj->result = 'false';
				$osObj->errors = '发货队列的发货items为空';
				$osObj->updated = $nowTime;
				$osObj->lasttime = $nowTime;
				$osObj->save();
			}
			// 调试发货暂时不删数据
			$queueSyncShippedObj->status = 2;// 发货失败
			$queueSyncShippedObj->save();
			return false;
		}
	
		// dzt20161222 不标记不适合标记发货的item
		$ignoreItems = array();
		$OrderItemIds = array();
		foreach ($params['items'] as $item){
		    if(!empty($item['platform_status'])
		            && !in_array($item['platform_status'], LazadaApiHelper::$CAN_SHIP_ORDERITEM_STATUS)){
		        $ignoreItems[] = $item['order_source_order_item_id'].'=>'.$item['platform_status'];
		    }else{
		        $OrderItemIds[] = $item['order_source_order_item_id'];
		    }
		}
		
		if(empty($OrderItemIds)){// XXX 或者没有要上传产品的不报错直接当成已经上传成功？
		    $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
		    if (!is_null($osObj)){
		        $osObj->status = 2;
		        $osObj->result = 'false';
		        $osObj->errors = "订单".$queueSyncShippedObj->order_source_order_id." 没有可发货的订单item。忽略的item及其状态为：".implode(',', $ignoreItems);
		        $osObj->updated = $nowTime;
		        $osObj->lasttime = $nowTime;
		        $osObj->save();
		    }
		    // 调试发货暂时不删数据
		    $queueSyncShippedObj->status = 2;// 发货失败
		    $queueSyncShippedObj->save();
		    return false;
		}
		
	
		$config = array(
				"userId"=>$SLU->platform_userid,
				"apiKey"=>$SLU->token,
				"countryCode"=>$SLU->lazada_site
		);
	
		// 接口传入参数
		$appParams = array (
				'OrderItemIds' => implode(',', $OrderItemIds), // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
				'DeliveryType' => "dropship", // 目前不清楚其他类型的 DeliveryType，先hardcode 为dropship。  DeliveryType:One of the following: 'dropship' - The seller will send out the package on his own; 'pickup' - Shop should pick up the item from the seller (cross-docking); 'send_to_warehouse' - The seller will send the item to the warehouse (crossdocking).
				'ShippingProvider' => $queueSyncShippedObj->shipping_method_code, //
				'TrackingNumber' => $queueSyncShippedObj->tracking_number, // 运单号。对应UPS的运单号；示例值：1Z68A9X70467731838
		);
		
		// 调用接口获取订单列表
		if(!empty($ignoreItems)){
		    \Yii::info("JumiaShipped uid:".$queueSyncShippedObj->uid.",order:".$queueSyncShippedObj->order_source_order_id.",ignore items：".implode(',', $ignoreItems) , "file");
		}
		\Yii::info("JumiaShipped ready to proxy.config:".json_encode($config)." appParams:".json_encode($appParams) , "file");
		$result = LazadaInterface_Helper::shipOrder($config,$appParams);
		\Yii::info("JumiaShipped rerun from proxy.result:".json_encode($result) , "file");
// 		print_r($result);
		if ($result ['success'] && $result['response']['success'] == true) { // 成功
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
			$osObj->status = 1;
			$osObj->result = 'true';
			$osObj->errors = '';
			$osObj->updated = $nowTime;
			$osObj->lasttime = $nowTime;
			$osObj->save();
			}
			// 调试发货暂时不删数据
			$queueSyncShippedObj->status = 1;// 发货失败
			$queueSyncShippedObj->save();
			return true;
	} else {
		$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
		if (!is_null($osObj)){
		$osObj->status = 2;
		$osObj->result = 'false';
		$osObj->errors = $result['message'];// $result['response']['success'] == false 的话，$rtn['response']['message'] 也被记录在$result['message']里面
		$osObj->updated = $nowTime;
		$osObj->lasttime = $nowTime;
		$osObj->save();
		}
		// 调试发货暂时不删数据
		$queueSyncShippedObj->status = 2;// 发货失败
		$queueSyncShippedObj->save();
		return false;
		}
	}// end of JumiaShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Ensogo 发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model 对象
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function EnsogoShipped($queueSyncShippedObj){
		$ensogoUser = SaasEnsogoUser::findOne(['store_name'=>$queueSyncShippedObj->selleruserid]);
		//找出 ensogo 账号相关信息
		if (!empty($ensogoUser)) {
			 
			//"order_id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""
			$params = [
			'id'	=>$queueSyncShippedObj->order_source_order_id ,  //平台订单号
			"shipping_provider"  => $queueSyncShippedObj->shipping_method_code, //平台运输服务代码
			"tracking_number"	=> $queueSyncShippedObj->tracking_number,//物流号
			"ship_note"	=> $queueSyncShippedObj->description, //备注
			];
			echo "kh1 <br> ";//test kh
				
			//找出 发货执行成功记录的数量
			$ShippedCount = OdOrderShipped::find()->where(['order_source_order_id'=>$queueSyncShippedObj->order_source_order_id , 'result'=>'true'])->count();
			echo "ship Count = $ShippedCount <br> ";//test kh
			//echo "token = ".$ensogoUser->token." <br> ";//test kh
			if ($ShippedCount>0){
				//发货执行成功记录的数量 大于0时表示该订单已经发货了 , 本次调用api是为了修改物流信息
				$result = EnsogoInterface_Helper::updateTrackingInfo($ensogoUser->token , $params);
			}else{
			//发货执行成功记录的数量 等 于0时表示该订单未发货 , 本次调用api是为了订单发货
				$result = EnsogoInterface_Helper::ShippedOrder($ensogoUser->token , $params);
			}
				
			if (is_string($result))
				$result = json_decode($result,true);
					
				//当proxy返回结果为已经已经发货  并刚刚 调用 的api 为 发货的情况下改用 修改物流信息 更新一次
			if (!empty($result['proxyResponse']['data']['type'])  && ($result['proxyResponse']['data']['type'] == 'failed_to_save') ){
				echo " check again <br>";//test kh
				if ( $ShippedCount>0){ //第一次使用哪个api的控制条件
					//第一次 使用了更新物流信息 接口 返回 结果中 告知 未发货  则使用订单发货接口
					echo 'shiporder again';//test kh
					$result = EnsogoInterface_Helper::ShippedOrder($ensogoUser->token , $params);
						
					if (is_string($result))
						$result = json_decode($result,true);
				}else{
					//第一次 使用了 发货 接口  返回 结果中 告知 已经发货了 则使用修改订单发货信息接口
					echo 'update again';//test kh
					$result = EnsogoInterface_Helper::updateTrackingInfo($ensogoUser->token , $params);
					
					if (is_string($result))
						$result = json_decode($result,true);
				}
				
			}else{
				echo "no message <br>";//test kh
			}
		
			echo "kh2 <br> ";//test kh
			print_r($result);
			if (!empty($result['success']) && !empty($result['proxyResponse']['success']) ) {
				//@todo what case?
				if(!empty($result['proxyResponse']['ensogoReturn']['success'])){
					// 调用ensogo proxy 成功 同时 ensogo proxy 返回成功
					$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
					if (!is_null($osObj)){
						$osObj->status = 1;
						$osObj->result = 'true';
						$osObj->errors = '';
						$osObj->return_no = '';
						$osObj->updated = time();
						$osObj->lasttime = time();
						$osObj->save();
					}
				}
				//ShippedOrder , updateTrackingInfo case
				if(!empty($result['proxyResponse']['data']['data']) && empty($result['proxyResponse']['data']['code'])){
					$request_id = '';
					if(!empty($result['proxyResponse']['data']['data']['request_id'])){
						$request_id = $result['proxyResponse']['data']['data']['request_id'];
					}
					
					$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
					if (!is_null($osObj)){
						$osObj->status = 1;
						$osObj->result = 'true';
						$osObj->errors = '';
						$osObj->return_no = '';
						$osObj->updated = time();
						$osObj->lasttime = time();
						$osObj->save();
					}
					
					/*@todo  异步查询request结果
					//更新到od_order_shipped_v2,状态为3等待同步处理结果
					$connection=\Yii::$app->subdb;
					$command=$connection->createCommand(
						"UPDATE `od_order_shipped_v2` 
						SET `status`=3,`errors`='等待平台处理结果',`updated`=".time().",`lasttime`=".time().",`return_no`='$request_id' 
						WHERE `id`= $queueSyncShippedObj->osid");
					$dataReader=$command->execute();
					//记录到异步查询request_id队列
					$connection_r=\Yii::$app->db;
					$command_r=$connection_r->createCommand("INSERT INTO `ensogo_request_status`
						(`uid`, `table_name`, `table_id`, `request_id`, `create`, `update`) 
						VALUES ($queueSyncShippedObj->uid,'od_order_shipped_v2',$queueSyncShippedObj->osid,'$request_id','".date("Y-m-d H:m:s")."','".date("Y-m-d H:m:s")."')");
					$dataInsert=$command_r->execute();
					
					*/
				}
				//testkh20150709 ensogo 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 1;//成功发货
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
				return true;
			}else{
					$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					
					$osObj->result = 'false';
					$osObj->errors = json_encode($result);
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//testkh20150709 ensogo 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 2;//发货失败
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
				return false;
			}
		} else {
			echo $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . ' has not found!';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
			//testkh20150709 ensogo 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 2;//发货失败
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
			}
			return false;
		}
	}//end of EnsogoShipped
	 


	/**
	 +---------------------------------------------------------------------------------------------
	 * Priceminister 发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model 对象
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2016/04/06		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function PriceministerShipped($queueSyncShippedObj){
		$journal_id = SysLogHelper::InvokeJrn_Create("Delivery", __CLASS__, __FUNCTION__ ,array($queueSyncShippedObj));
		$priceministerUser=SaasPriceministerUser::find()->where(['uid'=>$queueSyncShippedObj->uid,'username'=>$queueSyncShippedObj->selleruserid])->one();
		//找出 Priceminister 账号相关信息
		$rtn['success'] = true;
		$rtn['message'] = '';
		$errorMsg = '';
		if (!empty($priceministerUser)) {
			 
			$items=[];
			$orderDetails = OdOrderItem::find()->where(['order_source_order_id'=>$queueSyncShippedObj->order_source_order_id])
				->andWhere(" (platform_status in ('COMMITTED','ACCEPTED','') or platform_status is NULL ) or (delivery_status='allow' ) " )
				->andWhere(" order_id in (select `order_id` from `od_order_v2` where `order_source_order_id`='".$queueSyncShippedObj->order_source_order_id."' and selleruserid='".$queueSyncShippedObj->selleruserid."')")
				->asArray()->all();
			
			foreach ($orderDetails as $v){
				if( !empty($v['source_item_id']) && !in_array($v['source_item_id'], $items)){
					$items[]=$v['source_item_id'];
				}
			}
			//echo "liang1 <br> ";//test liang
			$pm_api = new PriceministerOrderInterface();
			$pm_api->setStoreNamePwd($priceministerUser->username, $priceministerUser->token);
			$carrier = $queueSyncShippedObj->shipping_method_code;
			$track_no = $queueSyncShippedObj->tracking_number;
			$track_url = $queueSyncShippedObj->tracking_link;
			if(empty($track_url))
			$track_url = 'http://www.17track.net/fr/';
			foreach ($items as $item){
				$api_rtn = $pm_api->SetTrackNumber($item, $carrier, $track_no, $track_url);
				if(empty($api_rtn['success'])){//重试一次
					$api_rtn = $pm_api->SetTrackNumber($item, $carrier, $track_no, $track_url);
				}
				//重试一次依然失败
				if(empty($api_rtn['success'])){
					$rtn['success'] = false;
					$rtn['message'] .= $api_rtn['message'];
					$errorMsg .= $api_rtn['message'];
				}
			}
	
			//找出 发货执行成功记录的数量
			//$ShippedObj = OdOrderShipped::find()->where(['order_source_order_id'=>$queueSyncShippedObj->order_source_order_id , 'result'=>'true','status'=>1])->One();
			//echo "ship Count = $ShippedCount <br> ";//test liang
	
			/*
				if ($ShippedObj<>null){
				//发货执行成功记录的数量 大于0时表示该订单已经发货了 , CD不支持修改物流信息
				$ShippedObj->errors = '已经标记发货了，请勿重复标记';
				$ShippedObj->updated = time();
				$ShippedObj->lasttime = time();
				$ShippedObj->save();
		
				$queueSyncShippedObj->status = 1;//成功发货
				$queueSyncShippedObj->save();
				SysLogHelper::InvokeJrn_UpdateResult($journal_id,array('success'=>true,'message'=>'已经标记发货了，请勿重复标记'));
				return true;
			}else{
				//发货执行成功记录的数量 等 于0时表示该订单未发货 , 本次调用api是为了订单发货
				$result = CdiscountInterface_Helper::ShippedOrder($cdiscountUser->token , $params);
		
				if (is_string($result))
					$result = json_decode($result,true);
		
				if(empty($result['success'])){
					//重试一次
					$result = CdiscountInterface_Helper::ShippedOrder($cdiscountUser->token , $params);
				}
			}
	
			if (is_string($result))
				$result = json_decode($result,true);
			*/
	
			//echo "liang2 <br> ";//test liang
			//var_dump($result);
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
			//本地错误信息
	
	 	
			/*
			//proxy错误信息
			if(empty($result['proxyResponse']['success'])){
				$errorMsg .= '代理连接失败！';
				$errorLog = $errorMsg.'  '.empty($result['proxyResponse']['message'])?'proxy error message lost...':$result['proxyResponse']['message'];
				//\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
			}
			*/
			/*
			//api返回的错误信息-order层面
			if(!empty($result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['Errors']['Error'])){
				$order_err_msg = $result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['Errors']['Error']['Message'];
				$errorMsg .= "API-orderError:".$order_err_msg;
				$errorLog = $errorMsg.(!empty($result['url'])?'  '.$result['url']:'');
				//\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
				//CD后台已经为发货状态
				if(stripos($errorMsg, "Le passage à l'état Shipped est impossible à ce stade de la commande")){
					$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
					if (!is_null($osObj)){
						$osObj->status = 1;
						$osObj->result = 'true';
						$osObj->errors = '该订单已经在其他地方标记发货过了。';
						$osObj->updated = time();
						$osObj->lasttime = time();
						$osObj->save();
					}
					//testkh20150709 cdiscount 调试发货暂时不删除数据
					$queueSyncShippedObj->status = 1;//成功发货
					$queueSyncShippedObj->save();
					return true;
				}
			}
			*/
			/*
			//api返回的错误信息-item层面
			if(!empty($result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['ValidateOrderLineResults']['ValidateOrderLineResult']['Errors']['Error'])){
				$errorMsg .= "API-orderItemError:".$result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['ValidateOrderLineResults']['ValidateOrderLineResult']['Errors']['Error']['Message'];
				$errorLog = $errorMsg.(!empty($result['url'])?'  '.$result['url']:'');
				//\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
			}
			*/
			if (!empty($rtn['success']) && empty($errorMsg)) {
				// 调用 proxy 成功 同时  proxy 返回成功
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 1;
					$osObj->result = 'true';
					$osObj->errors = '';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//testkh20150709 cdiscount 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 1;//成功发货
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
				return true;
			} else {
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $errorMsg;
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//testkh20150709 wish 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 2;//发货失败
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
				SysLogHelper::SysLog_Create("priceminister", __CLASS__,__FUNCTION__,"",empty($rtn['message'])?'':$rtn['message']);
	
				return false;
			}
		} else {
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, array('success'=>false,'message'=>'Unauthorized or expired!'));
			echo $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!' . "\n";
			if (true){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . ' has not found!';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				//testkh20150709 wish 调试发货暂时不删除数据
				$queueSyncShippedObj->status = 2;//发货失败
				$queueSyncShippedObj->save();
				//$queueSyncShippedObj->delete ();
			}
			return false;
		}
	}//end of PriceministerShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Bonanza 发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model 对象
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			lwj		2016/4/28		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function BonanzaShipped($queueSyncShippedObj){
	    $journal_id = SysLogHelper::InvokeJrn_Create("Delivery", __CLASS__, __FUNCTION__ ,array($queueSyncShippedObj));
	    $bonanzaUser=SaasBonanzaUser::find()->where(['uid'=>$queueSyncShippedObj->uid,'store_name'=>$queueSyncShippedObj->selleruserid])->one();
	    //找出 账号相关信息
	    if (!empty($bonanzaUser)) {
	        
	        	
	        $items=[];
	        //改为查SRC Order Detail表，避免别名问题
	        $orderDetails = BonanzaOrderDetail::find()->where(['orderID'=>$queueSyncShippedObj->order_source_order_id])->asArray()->all();
 
	        	
	        $params = [
	            "orderid"=>$queueSyncShippedObj->order_source_order_id ,  //平台订单号
	            "CarrierName" => $queueSyncShippedObj->shipping_method_code, //平台运输服务代码
	            "TrackingNumber"=> $queueSyncShippedObj->tracking_number,//物流号
	            "TrackingUrl"=> $queueSyncShippedObj->tracking_link,
	            "items"=>$items,//产品明细
	        ];
	        //echo "liang1 <br> ";//test liang
	
	        //找出 发货执行成功记录的数量
	        $ShippedObj = OdOrderShipped::find()->where(['order_source_order_id'=>$queueSyncShippedObj->order_source_order_id , 'result'=>'true','status'=>1])->One();
	        //echo "ship Count = $ShippedCount <br> ";//test liang
	
	        if ($ShippedObj<>null){
	            //发货执行成功记录的数量 大于0时表示该订单已经发货了 
	            $ShippedObj->errors = '已经标记发货了，请勿重复标记';
	            $ShippedObj->updated = time();
	            $ShippedObj->lasttime = time();
	            $ShippedObj->save();
	
	            $queueSyncShippedObj->status = 1;//成功发货
	            $queueSyncShippedObj->save();
	            SysLogHelper::InvokeJrn_UpdateResult($journal_id,array('success'=>true,'message'=>'已经标记发货了，请勿重复标记'));
	            return true;
	        }else{
	            BonanzaOrderInterface::setBonanzaToken($bonanzaUser->token);//必须设置token
	            //发货执行成功记录的数量 等 于0时表示该订单未发货 , 本次调用api是为了订单发货
	            $result = BonanzaOrderInterface::shipOrder($params['orderid'],$params['CarrierName'],$params['TrackingNumber'],'');
	
	            if (is_string($result))
	                $result = json_decode($result,true);
	
	            if(empty($result['success'])){
	                //重试一次
	               $result = BonanzaOrderInterface::shipOrder($params['orderid'],$params['CarrierName'],$params['TrackingNumber'],'');
	            }
	        }
	
	        if (is_string($result))
	            $result = json_decode($result,true);
	
	        /*
	         //当proxy返回结果为已经已经发货  并刚刚 调用 的api 为 发货的情况下改用 修改物流信息 更新一次
	         if (!empty($result['proxyResponse']['message']) && $ShippedObj<>0){
	         //echo " check again <br>";//test liang
	         //返回 结果中 告知 已经发货了 则使用修改订单发货信息接口
	         if (stripos($result['proxyResponse']['message'], 'has been fulfilled')){
	         //echo 'update again';//test liang
	         $result = BonanzaInterface_Helper::updateTrackingInfo($bonanzaUser->token , $params);
	         	
	         if (is_string($result))
	             $result = json_decode($result,true);
	             }
	             }else{
	             //echo "no message <br>";//test liang
	             }
	        */
	
	        //echo "liang2 <br> ";//test liang
	        //var_dump($result);
	        SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
	        $errorMsg = '';
	        $errorLog='';
	        //本地错误信息
	        	
	        //重试一次依然失败
	        if(empty($result['success'])){
	            $errorMsg = "LocalError:".empty($result['message'])?'error message lost...':$result['message'];
	            $errorLog = $errorMsg;
	            //\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
	        }
	        //proxy错误信息
// 	        if(empty($result['proxyResponse']['success'])){
// 	            $errorMsg .= '代理连接失败！';
// 	            $errorLog = $errorMsg.'  '.empty($result['proxyResponse']['message'])?'proxy error message lost...':$result['proxyResponse']['message'];
// 	            //\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
// 	        }
	        //api返回的错误信息-order层面
// 	        if(!empty($result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['Errors']['Error'])){
// 	            $order_err_msg = $result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['Errors']['Error']['Message'];
// 	            $errorMsg .= "API-orderError:".$order_err_msg;
// 	            $errorLog = $errorMsg.(!empty($result['url'])?'  '.$result['url']:'');
// 	            //\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
// 	            //CD后台已经为发货状态
// 	            if(stripos($errorMsg, "Le passage à l'état Shipped est impossible à ce stade de la commande")){
// 	                $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
// 	                if (!is_null($osObj)){
// 	                    $osObj->status = 1;
// 	                    $osObj->result = 'true';
// 	                    $osObj->errors = '该订单已经在其他地方标记发货过了。';
// 	                    $osObj->updated = time();
// 	                    $osObj->lasttime = time();
// 	                    $osObj->save();
// 	                }
// 	                //testkh20150709 bonanza 调试发货暂时不删除数据
// 	                $queueSyncShippedObj->status = 1;//成功发货
// 	                $queueSyncShippedObj->save();
// 	                return true;
// 	            }
// 	        }
	        //api返回的错误信息-item层面
// 	        if(!empty($result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['ValidateOrderLineResults']['ValidateOrderLineResult']['Errors']['Error'])){
// 	            $errorMsg .= "API-orderItemError:".$result['proxyResponse']['ValidateOrderList']['s_Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult']['ValidateOrderLineResults']['ValidateOrderLineResult']['Errors']['Error']['Message'];
// 	            $errorLog = $errorMsg.(!empty($result['url'])?'  '.$result['url']:'');
// 	            //\Yii::info(['QueueAutoShipped',__CLASS__,__FUNCTION__,'Background',$errorLog],"file");
// 	        }
	
	        if (!empty($result['success']) && empty($errorMsg)) {
	            // 调用bonanza proxy 成功 同时 bonanza proxy 返回成功
	            $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
	            if (!is_null($osObj)){
	                $osObj->status = 1;
	                $osObj->result = 'true';
	                $osObj->errors = '';
	                $osObj->updated = time();
	                $osObj->lasttime = time();
	                $osObj->save();
	            }
	            //testkh20150709 bonanza 调试发货暂时不删除数据
	            $queueSyncShippedObj->status = 1;//成功发货
	            $queueSyncShippedObj->save();
	            //$queueSyncShippedObj->delete ();
	            return true;
	        } else {
	            $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
	            if (!is_null($osObj)){
	                $osObj->status = 2;
	                $osObj->result = 'false';
	                $osObj->errors = $errorMsg;
	                $osObj->updated = time();
	                $osObj->lasttime = time();
	                $osObj->save();
	            }
	            //testkh20150709 wish 调试发货暂时不删除数据
	            $queueSyncShippedObj->status = 2;//发货失败
	            $queueSyncShippedObj->save();
	            //$queueSyncShippedObj->delete ();
	            if(!empty($errorLog))
	                SysLogHelper::SysLog_Create("Bonanza", __CLASS__,__FUNCTION__,"",$errorLog);
	
	            return false;
	        }
	    } else {
	        SysLogHelper::InvokeJrn_UpdateResult($journal_id, array('success'=>false,'message'=>'Unauthorized or expired!'));
	        echo $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!' . "\n";
	        if (true){
	            $osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
	            if (!is_null($osObj)){
	                $osObj->status = 2;
	                $osObj->result = 'false';
	                $osObj->errors = $queueSyncShippedObj->selleruserid . ' has not found!';
	                $osObj->updated = time();
	                $osObj->lasttime = time();
	                $osObj->save();
	            }
	            //testkh20150709 wish 调试发货暂时不删除数据
	            $queueSyncShippedObj->status = 2;//发货失败
	            $queueSyncShippedObj->save();
	            //$queueSyncShippedObj->delete ();
	        }
	        return false;
	    }
	}//end of BonanzaShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Newegg标记发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param			$queueSyncShippedObj		队列model QueueSyncshipped 对象
	 +---------------------------------------------------------------------------------------------
	 * @return						boolean 		true 为成功, false 为失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		winton		2016/07/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function NeweggShipped($queueSyncShippedObj){
		try{
			$journal_id = SysLogHelper::InvokeJrn_Create("Delivery", __CLASS__, __FUNCTION__ ,array($queueSyncShippedObj));
			
			//$params = $queueSyncShippedObj->params;
			
			$neweggUser = SaasNeweggUser::find()->where(['uid'=>$queueSyncShippedObj->uid, 'SellerID'=>$queueSyncShippedObj->selleruserid])->one();
			 
			if(empty($neweggUser)){// 不存在的账号。
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->result = 'false';
					$osObj->errors = '不存在的账号'.$queueSyncShippedObj->selleruserid;
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				// 调试发货暂时不删数据
				$queueSyncShippedObj->status = 2;// 发货失败
				$queueSyncShippedObj->save();
				return false;
			}
			
			// 这个搜索条件会命中手工订单，导致获取错信息，其实按道理item信息最好在插入ship表时候加入，newegg没有按其他平台流程做。
			// $order = OdOrder::find()->where(['order_source_order_id'=>$queueSyncShippedObj->order_source_order_id,'order_source'=>'newegg'])->one();
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			if (!is_null($osObj)){
			    $order = OdOrder::findOne($osObj->order_id);
			}else{
			    echo PHP_EOL.'OdOrderShipped not exist id:'.$queueSyncShippedObj->osid.PHP_EOL;
			    // 调试发货暂时不删数据
			    $queueSyncShippedObj->status = 2;// 发货失败
			    $queueSyncShippedObj->save();
			    return false;
			}
			
			if(empty($order)){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->result = 'false';
					$osObj->errors = '订单不存在';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				// 调试发货暂时不删数据
				$queueSyncShippedObj->status = 2;// 发货失败
				$queueSyncShippedObj->save();
				return false;
			}
			$items = OdOrderItem::find()->where(['order_id'=>$order->order_id])->all();
			if(empty($items)){// 不存在item
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->result = 'false';
					$osObj->errors = '找不到对应的item'.$queueSyncShippedObj->selleruserid;
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				// 调试发货暂时不删数据
				$queueSyncShippedObj->status = 2;// 发货失败
				$queueSyncShippedObj->save();
				return false;
			}
			
			
			//组织数据
			$token = [
				'SellerID' => $neweggUser->SellerID,
				'Authorization' => $neweggUser->Authorization,
				'SecretKey' => $neweggUser->SecretKey
			];
			
			$c = 0;
			foreach ($items as $i){
				$itmesList[$c]['SellerPartNumber'] = $i->sku;
				$itmesList[$c]['ShippedQty'] = $i->quantity;
				$c++;
			}
			
			$params = [
				'orderNumber' => $queueSyncShippedObj->order_source_order_id,
				'tracking_number' => $queueSyncShippedObj->tracking_number,
				'shipping_method_code' => $queueSyncShippedObj->shipping_method_code,
				'items' => $itmesList,
				
			];
			
			//调用接口进行标记发货
			$ret = NeweggInterface_Helper::shipOrder($token, $params);
			
			print_r($ret);
			
			//根据接口返回结果进行处理
			if(isset($ret['IsSuccess']) && $ret['IsSuccess'] &&
			 isset($ret['PackageProcessingSummary']['TotalPackageCount']) && isset($ret['PackageProcessingSummary']['SuccessCount'])
			&& $ret['PackageProcessingSummary']['SuccessCount'] == $ret['PackageProcessingSummary']['TotalPackageCount']){
				
				//$order = OdOrder::findOne()->where(['order_id'=>$params['order_id']]);
				if(isset($ret['Result']['OrderStatus'])){
					$order->order_source_status = $ret['Result']['OrderStatus'];
				}
				
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 1;
					$osObj->result = 'true';
					$osObj->errors = '';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				// 调试发货暂时不删数据
				$queueSyncShippedObj->status = 1;// 发货成功
				$queueSyncShippedObj->save();
				return true;
			} else {
				$errors = '';
				if(isset($ret['Result']['Shipment']['PackageList'])){
					if( isset( $ret['Result']['Shipment']['PackageList']['ProcessResult'] ) ){
						$errors = $ret['Result']['Shipment']['PackageList']['ProcessResult'];
					}
					else{
						foreach ($ret['Result']['Shipment']['PackageList'] as $PackageList){
							$errors .= @$PackageList['ProcessResult'];
						}
					}
				}
				
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = !empty($errors)? $errors : @$ret[0]['Message'];
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				// 调试发货暂时不删数据
				$queueSyncShippedObj->status = 2;// 发货失败
				$queueSyncShippedObj->status = 2;// 发货失败
				$queueSyncShippedObj->save();
				return false;
			}
		}catch(\Exception $e){
			echo "\n NeweggShipped Exception:".$e->getMessage();
			return false;
		}
	}// end of NeweggShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 标记发货队列健康检查 。
	 * 		原则： 半小时内还没有标记（status = 0 ）的数据视为异常
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na			
	 +---------------------------------------------------------------------------------------------
	 * @return						array 
	 * 									unhealth	异常数据数量
	 * 									message		执行结果
	 *
	 * @invoking					QueueAutoShippedHelper::AutoShipHealthCheck();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function AutoShipHealthCheck(){
		try {
			$deadline = strtotime("-30 minute");
			$model = QueueSyncshipped::find()->select(['order_source','qty'=>'count(1)'])->where(['status'=>0])->andWhere("order_source!='shopee'")->andwhere(['<=','created',$deadline])->groupBy(['order_source']);
			$unHealthList = $model->asArray()->all();
			//echo $model->createCommand()->getRawSql();
			
			$msg = "";
			foreach($unHealthList as $row){
				$msg .= "<br>".$row['order_source']."(".$row['qty'].")";
			}
			if (!empty($unHealthList) ){
				//unhealth 
				$mail = Yii::$app->mailer->compose();
				$mail->setTo("akirametero@vip.qq.com");
				$mail->setSubject('标示发货异常:' . date("Y-m-d H:i:s"));
				$mail->setTextBody($msg);
				$result = $mail->send();
				if ($result === false) {
					echo "\n faliure to send email";
					yii::info((__function__)."发送邮件失败", "file");
				} else {
					yii::info((__function__)."发送邮件成功", "file");
					echo "\n success to send email";
				}
			}else{
				// normal
				$msg = "\n".(__function__)." queue is ok";
				echo $msg;
				yii::info($msg, "file");
				
			}
			
			return ['unhealth'=>count($unHealthList),'message'=>$msg];
		} catch (\Exception $e) {
			//echo $e->getMessage();
			$errorMsg = (__function__)." Exception Error LineNo(".$e->getline().") msg:".$e->getMessage();
			echo $errorMsg;
			yii::info($errorMsg, "file");
			return ['unhealth'=>0,'message'=>$errorMsg];
		}
	}//end of AutoShipHealthCheck
	
		
	/**
	 * Shopee标记发货
	 * shopee 基本是通过线上物流发货即可标记平台发货，但不排除还有路径进入这里，所以完善一下
	 * @author lrq2180507
	 */
	public static function ShopeeShipped($queueSyncShippedObj){
		
		$user = SaasShopeeUser::find()->where(['shop_id' => $queueSyncShippedObj->selleruserid])->andWhere("status<>3")->one();
		if (!empty($user)) {
			if(!Yii::$app->subdb->changeUserDataBase ($queueSyncShippedObj->uid)){
				echo 'database check failure';
				return false;
			}
			
			$api = new ShopeeInterface_Api();
			$api->shop_id = $user->shop_id;
			$api->partner_id = $user->partner_id;
			$api->secret_key = $user->secret_key;
			
			$puid = $user->puid;
			echo 'ShopeeShipped, shop_id='.$user->shop_id.', order_id='.$queueSyncShippedObj->order_source_order_id.', tracking_number='.$queueSyncShippedObj->tracking_number.PHP_EOL;
			
			$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
			$order = OdOrder::findOne($osObj->order_id);
			//1、只有平台状态为Ready_to_ship才可使用
			if($order->order_source_status != 'READY_TO_SHIP'){
			    if (!empty($osObj)){
			        $osObj->status = 2;
			        $osObj->result = 'false';
			        $osObj->errors = '此订单状态不是READY_TO_SHIP';
			        $osObj->updated = time();
			        $osObj->lasttime = time();
			        $osObj->save();
			    }
			    $queueSyncShippedObj->status=2;
			    $queueSyncShippedObj->save (false);
			    return false;
			}
			
			//2、验证发货流程，non_integrated、pickup、dropoff
			$ordersn = $order->order_source_order_id;
			$parameterForInit = $api->GetParameterForInit(['ordersn' => $ordersn]);
			\Yii::info('ShopeeShipped,puid:'.$puid.',order_id:'.$order->order_id.',1,'.$ordersn.','.json_encode($parameterForInit),"file");
			if(!empty($parameterForInit['msg'])){
			    if (!empty($osObj)){
			        $osObj->status = 2;
			        $osObj->result = 'false';
			        $osObj->errors = "GetParameterForInit err:".$parameterForInit['msg'];
			        $osObj->updated = time();
			        $osObj->lasttime = time();
			        $osObj->save();
			    }
			    $queueSyncShippedObj->status=2;
			    $queueSyncShippedObj->save (false);
				return false;
			}
			//dropoff
			$param['ordersn'] = $ordersn;
			if(isset($parameterForInit['dropoff'])){
			    $param['dropoff'] = (object)[];
			}
			else if(isset($parameterForInit['non_integrated'])){
			    if(empty($parameterForInit['non_integrated'])){
			        $param['non_integrated'] = (object)[];
			    }
			    else{
			        $param['non_integrated'] = (object)['tracking_no' => ''];
			    }
			}
			else if(isset($parameterForInit['pickup'])){
			    $param['pickup'] = (object)[];
			}
			else{
			    if (!empty($osObj)){
			        $osObj->status = 2;
			        $osObj->result = 'false';
			        $osObj->errors = "GetParameterForInit err:接口返回信息异常";
			        $osObj->updated = time();
			        $osObj->lasttime = time();
			        $osObj->save();
			    }
			    $queueSyncShippedObj->status=2;
			    $queueSyncShippedObj->save (false);
			    return false;
			}
			\Yii::info('ShopeeShipped,puid:'.$puid.',order_id:'.$order->order_id.',2,'.json_encode($param),"file");
			//提交信息，触发
			$res = $api->LogisticsInit($param);
			\Yii::info('ShopeeShipped,puid:'.$puid.',order_id:'.$order->order_id.',3,'.json_encode($res),"file");
			//提交信息，再次调用获取物流号
			sleep(1); //延时1s
			$res = $api->LogisticsInit($param);
			\Yii::info('ShopeeShipped,puid:'.$puid.',order_id:'.$order->order_id.',4,'.json_encode($res),"file");
				
			
			if(!empty($res['msg'])){
			    if (!empty($osObj)){
			        $osObj->status = 2;
			        $osObj->result = 'false';
			        $osObj->errors = "LogisticsInit err:".$res['msg'];
			        $osObj->updated = time();
			        $osObj->lasttime = time();
			        $osObj->save();
			    }
			    $queueSyncShippedObj->status=2;
			    $queueSyncShippedObj->save (false);
			    return false;
			}
			else if(empty($res['tracking_number'])){
			    if (!empty($osObj)){
			        $osObj->status = 2;
			        $osObj->result = 'false';
			        $osObj->errors = "LogisticsInit err:接口返回信息异常";
			        $osObj->updated = time();
			        $osObj->lasttime = time();
			        $osObj->save();
			    }
			    $queueSyncShippedObj->status=2;
			    $queueSyncShippedObj->save (false);
			    return false;
			}
		
			// 成功
			if (!is_null($osObj)){
			    $osObj->status = 1;
			    $osObj->result = 'true';
			    $osObj->errors = '';
			    $osObj->updated = time();
			    $osObj->lasttime = time();
			    $osObj->save();
			}
			$queueSyncShippedObj->status=1;
			$queueSyncShippedObj->save (false);
			return true;
			 
		} else {
			echo $queueSyncShippedObj->selleruserid . ' Unauthorized or expired!' . "\n";
			if (Yii::$app->subdb->changeUserDataBase ( $queueSyncShippedObj->uid )){
				$osObj = OdOrderShipped::findOne($queueSyncShippedObj->osid);
				if (!is_null($osObj)){
					$osObj->status = 2;
					$osObj->result = 'false';
					$osObj->errors = $queueSyncShippedObj->selleruserid . ' has not found!';
					$osObj->updated = time();
					$osObj->lasttime = time();
					$osObj->save();
				}
				$queueSyncShippedObj->status=2;
				$queueSyncShippedObj->save (false);
			}
			return false;
		}
	}
	
	
}
