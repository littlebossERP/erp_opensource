<?php
namespace common\api\cdiscountinterface;

use \Yii;
use eagle\modules\listing\helpers\CdiscountProxyConnectHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\listing\helpers\WishHelper;
use eagle\models\QueueSyncshipped;
use eagle\models\OdOrderShipped;
use eagle\modules\order\models\OdOrder;
use eagle\models\SaasCdiscountUser;
use eagle\modules\util\helpers\RedisHelper;

class CdiscountInterface_Helper {
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回cdiscount可选的物流方式
	 * description
	 * shipping_code就是 通知平台的那个运输方式对应值
	 * shipping_value就是给卖家看到的可选择的运输方式名称
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						@return array(array(shipping_code=>shipping_value)) 或者 null.   其中null表示这个渠道是没有可选shipping code和name， 是free text
	 *
	 * @invoking					CdiscountInterface_Helper::getShippingCodeNameMap();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/7/12		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getShippingCodeNameMap(){
		$ShipMapping  = array(
				'AUNAP'=>'AustraliaPost',
				'AUNIP'=>'AustraliaPost',
				'AURAP'=>'AustraliaPost',
				'AUREP'=>'AustraliaPost',
				'AURIP'=>'AustraliaPost',
				'AURLP'=>'AustraliaPost',
				'AURLT'=>'AustraliaPost',
				'AURPP'=>'AustraliaPost',
				'AURSP'=>'AustraliaPost',
				'au_post_eParcel'=>'AustraliaPost',
				'au_post_express'=>'AustraliaPost',
				'au_post_large_letter_no_registration'=>'AustraliaPost',
				'au_post_large_letter_with_registration'=>'AustraliaPost',
				'au_post_parcel_post_no_registration'=>'AustraliaPost',
				'au_post_parcel_post_with_registration'=>'AustraliaPost',
				'au_post_small_letter_no_registration'=>'AustraliaPost',
				'au_post_small_letter_with_registration'=>'AustraliaPost',
				'BEO'=>'Belpost',
				'BEP'=>'Belpost',
				'BGD'=>'Belpost',
				'CAF'=>'Chukou1',
				'CAN'=>'Chukou1',
				'CAP'=>'ChinaAirPost',
				'CAT'=>'Chukou1',
				'CEE'=>'Chukou1',
				'CEF'=>'Chukou1',
				'CEN'=>'Chukou1',
				'CET'=>'Chukou1',
				'CFE'=>'Chukou1',
				'CGN'=>'Chukou1',
				'CGT'=>'Chukou1',
				'CHINA EMS'=>'EMS',
				'CIE'=>'Chukou1',
				'CJP'=>'Chukou1',
				'CLS'=>'ChinaAirPost',
				'CLY'=>'ChinaAirPost',
				'CLZ'=>'ChinaAirPost',
				'CND'=>'DHL',
				'CNE'=>'Chukou1',
				'CNI'=>'ChinaAirPost',
				'CNPOST'=>'ChinaAirPost',
				'CRA'=>'ChinaAirPost',
				'CRB'=>'ChinaAirPost',
				'CRE'=>'RussianPost',
				'CRI'=>'ChinaAirPost',
				'CRN'=>'ChinaAirPost',
				'CRP'=>'ChinaAirPost',
				'CRS'=>'Chukou1',
				'CRU'=>'Chukou1',
				'CUE'=>'Chukou1',
				'CUN'=>'Chukou1',
				'CUT'=>'Chukou1',
				'DENDE'=>'DeutschePost',
				'DENDS'=>'DeutschePost',
				'DENID'=>'DeutschePost',
				'DERDS'=>'DeutschePost',
				'DERID'=>'DeutschePost',
				'DERIS'=>'DeutschePost',
				'DERIT'=>'DeutschePost',
				'DERLS'=>'DeutschePost',
				'DERLT'=>'DeutschePost',
				'DGM'=>'DeutschePost',
				'dgm_expedited_service'=>'Chukou1',
				'dgm_ground_service'=>'Chukou1',
				'DHL'=>'DHL',
				'domestic_parcel_tracked_1000148'=>'BPost',
				'domestic_parcel_tracked_1000149'=>'BPost',
				'dpd_domestic_normal_parcels_1000211'=>'DPDUK',
				'dpd_domestic_small_parcels_1000212'=>'DPDUK',
				'dpd_international_parcels_1000213'=>'DPDUK',
				'dsa_large_letter_untracked_service'=>'BPost',
				'dsa_small_letter_untracked_service'=>'BPost',
				'EMI'=>'EMS',
				'EMP'=>'EMS',
				'EMS'=>'EMS',
				'ESRIS'=>'EMS',
				'ESRLI'=>'Chukou1',
				'ESRLM'=>'Chukou1',
				'ESRLP'=>'Chukou1',
				'EUB'=>'ChinaAirPost',
				'EUF'=>'ChinaAirPost',
				'EUI'=>'ChinaAirPost',
				'EUU'=>'USPS',
				'FEDEX '=>'FedEx',
				'HBM'=>'HongKongPost',
				'HK DHL'=>'DHL',
				'HK EMS'=>'EMS',
				'HKE'=>'EMS',
				'HKFEDIE'=>'FedEx',
				'HKFEDIP'=>'FedEx',
				'HNXBGH'=>'ChinaAirPost',
				'HTM'=>'HongKongPost',
				'international_parcels_tracked'=>'BPostInternational',
				'international_parcels_untracked_flats'=>'BPostInternational',
				'international_parcels_untracked_letters'=>'BPostInternational',
				'international_parcels_untracked_Packets'=>'BPostInternational',
				'LARLP'=>'AsendiaUSA',
				'LARLS'=>'AsendiaUSA',
				'LARPP'=>'AsendiaUSA',
				'LARSS'=>'AsendiaUSA',
				'LYT GH'=>'PX4',
				'MEP'=>'Aramex',
				'MORLP'=>'RussianPost',
				'NJFRE'=>'AsendiaUSA',
				'NJNIU'=>'AsendiaUSA',
				'NJNLE'=>'AsendiaUSA',
				'NJNLL'=>'AsendiaUSA',
				'NJNUS'=>'AsendiaUSA',
				'NJRIS'=>'AsendiaUSA',
				'NJRLE'=>'AsendiaUSA',
				'NJRLP'=>'AsendiaUSA',
				'NJRLS'=>'AsendiaUSA',
				'NJRPP'=>'AsendiaUSA',
				'NJRSS'=>'AsendiaUSA',
				'NJRUS'=>'AsendiaUSA',
				'NLR'=>'PostNL',
				'notracked_europe_service_1000229'=>'DeutschePost',
				'notracked_large_letter_1000228'=>'DeutschePost',
				'notracked_large_letter_1000230'=>'BPost',
				'notracked_small_letter_1000231'=>'BPost',
				'notracked_small_letter_1000308'=>'DeutschePost',
				'RIRIS'=>'RussianPost',
				'royal_mail_1_class_tracked'=>'RoyalMail',
				'royal_mail_1_class_tracked_signed'=>'RoyalMail',
				'royal_mail_2_class_tracked'=>'RoyalMail',
				'royal_mail_2_class_tracked_signed'=>'RoyalMail',
				'SAP'=>'Chukou1',
				'SGO'=>'SingaporePost',
				'SGP'=>'SingaporePost',
				'SHANGH  IM'=>'ChinaAirPost',
				'Singapo SPack IMAIR'=>'SingaporePost',
				'TNT'=>'TNT',
				'TNT'=>'TNT',
				'toll_iepc'=>'TollGlobalExpress',
				'toll_priority'=>'TollPriority',
				'TONLE'=>'CanadaPost',
				'TORLE'=>'CanadaPost',
				'TORLP'=>'CanadaPost',
				'TORLS'=>'CanadaPost',
				'TORPE'=>'CanadaPost',
				'UEE'=>'AsendiaUSA',
				'UKNIR'=>'RoyalMail',
				'UKNR2'=>'RoyalMail',
				'UKNRM'=>'RoyalMail',
				'UKNRT'=>'RoyalMail',
				'UKPOD'=>'RoyalMail',
				'UKRIO'=>'RoyalMail',
				'UKRIP'=>'RoyalMail',
				'UKRIR'=>'RoyalMail',
				'UKRIS'=>'RoyalMail',
				'UKRLE'=>'RoyalMail',
				'UKRLF'=>'RoyalMail',
				'UKRLH'=>'RoyalMail',
				'UKRLO'=>'RoyalMail',
				'UKRLS'=>'RoyalMail',
				'UKRLT'=>'RoyalMail',
				'UKRNX'=>'RoyalMail',
				'UKRR2'=>'RoyalMail',
				'UKRRM'=>'RoyalMail',
				'UPS'=>'UPS',
				'UPS Export HK'=>'UPS',
				'ups_3_day_select_residential_service'=>'UPS',
				'ups_ground_service'=>'UPS',
				'ups_next_day_air_saver_service'=>'UPS',
				'ups_surepost_service'=>'UPS',
				'USFRE'=>'AsendiaUSA',
				'USNIU'=>'AsendiaUSA',
				'USNLE'=>'AsendiaUSA',
				'USNLL'=>'AsendiaUSA',
				'USNUS'=>'AsendiaUSA',
				'usps_first_class_mail_tracked_service'=>'USPS',
				'usps_priority_mail_parcels_tracked_service'=>'USPS',
				'USRIS'=>'AsendiaUSA',
				'USRLE'=>'AsendiaUSA',
				'USRLP'=>'AsendiaUSA',
				'USRLS'=>'AsendiaUSA',
				'USRPP'=>'AsendiaUSA',
				'USRUS'=>'AsendiaUSA',
				'yanwen_101'=>'Belpost',
				'yanwen_102'=>'Belpost',
				'yanwen_103'=>'DeutschePost',
				'yanwen_104'=>'DeutschePost',
				'yanwen_105'=>'ChinaAirPost',
				'yanwen_106'=>'SingaporePost',
				'yanwen_107'=>'HongKongPost',
				'yanwen_112'=>'ChinaAirPost',
				'yanwen_113'=>'ChinaAirPost',
				'yanwen_118'=>'EMS',
				'yanwen_12'=>'ChinaAirPost',
				'yanwen_120'=>'ChinaAirPost',
				'yanwen_121'=>'ChinaAirPost',
				'yanwen_122'=>'EMS',
				'yanwen_131'=>'RoyalMail',
				'yanwen_132'=>'RoyalMail',
				'yanwen_133'=>'RoyalMail',
				'yanwen_134'=>'RoyalMail',
				'yanwen_135'=>'RoyalMail',
				'yanwen_136'=>'RoyalMail',
				'yanwen_137'=>'RoyalMail',
				'yanwen_138'=>'RoyalMail',
				'yanwen_139'=>'FedEx',
				'yanwen_14'=>'SingaporePost',
				'yanwen_140'=>'Yanwen',
				'yanwen_141'=>'Yanwen',
				'yanwen_143'=>'ChinaAirPost',
				'yanwen_144'=>'PostNL',
				'yanwen_145'=>'FedEx',
				'yanwen_146'=>'SwissPost',
				'yanwen_147'=>'SwissPost',
				'yanwen_148'=>'SwissPost',
				'yanwen_149'=>'SwissPost',
				'yanwen_150'=>'SwissPost',
				'yanwen_151'=>'SwissPost',
				'yanwen_152'=>'SwedenPosten',
				'yanwen_153'=>'SwedenPosten',
				'yanwen_154'=>'ChinaAirPost',
				'yanwen_155'=>'ChinaAirPost',
				'yanwen_156'=>'ChinaAirPost',
				'yanwen_158'=>'ChinaAirPost',
				'yanwen_159'=>'Yanwen',
				'yanwen_160'=>'MalaysiaPost',
				'yanwen_161'=>'MalaysiaPost',
				'yanwen_163'=>'ChinaAirPost',
				'yanwen_164'=>'ChinaAirPost',
				'yanwen_165'=>'ChinaAirPost',
				'yanwen_166'=>'ChinaAirPost',
				'yanwen_167'=>'ChinaAirPost',
				'yanwen_168'=>'ChinaAirPost',
				'yanwen_169'=>'ChinaAirPost',
				'yanwen_170'=>'ChinaAirPost',
				'yanwen_171'=>'EMS',
				'yanwen_172'=>'ChinaAirPost',
				'yanwen_173'=>'EMS',
				'yanwen_174'=>'PostNL',
				'yanwen_175'=>'LithuaniaPost',
				'yanwen_176'=>'LithuaniaPost',
				'yanwen_177'=>'LithuaniaPost',
				'yanwen_178'=>'LithuaniaPost',
				'yanwen_179'=>'SwedenPosten',
				'yanwen_180'=>'SwedenPosten',
				'yanwen_24'=>'HongKongPost',
				'yanwen_25'=>'Yanwen',
				'yanwen_3'=>'EMS',
				'yanwen_30'=>'EMS',
				'yanwen_31'=>'YODEL',
				'yanwen_32'=>'YODEL',
				'yanwen_34'=>'Yanwen',
				'yanwen_36'=>'Yanwen',
				'yanwen_45'=>'DHL',
				'yanwen_5'=>'DHL',
				'yanwen_6'=>'UPS',
				'yanwen_7'=>'TNT',
		);
		return $ShipMapping;
	}//end of getShippingCodeNameMap
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Cdiscount 订单发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $cdiscount_token				cdiscount token 受权信息
	 * @param     $params           		cdiscount 订单发货需要 的信息 
	 * 											json 版本: {"order_id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""}
	 * @param	  $timeout					等待cdiscount proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					CdiscountInterface_Helper::_ShippedOrder($cdiscount_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl	2015/7/12		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function ShippedOrder($cdiscount_token , $params = [] , $timeout=120) {
		$api_name = "shipCDiscountOrder";
		return self::_callCdiscountOrderApi($api_name, $cdiscount_token,$params);
	}//end of ShippedOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改,更新Cdiscount 订单发货信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $cdiscount_token				cdiscount token 受权信息
	 * @param     $params           		cdiscount 订单发货需要 的信息
	 * 											json 版本: {"order_id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""}
	 * @param	  $timeout					等待cdiscount proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					CdiscountInterface_Helper::_modifiedOrderShippingInfo($cdiscount_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl	2015/7/12		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function updateTrackingInfo($cdiscount_token , $params = [] , $timeout=120){
		$api_name = "shipCDiscountOrder";
		return self::_callCdiscountOrderApi($api_name, $cdiscount_token,$params);
	}//end of updateTrackingInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改,更新cdiscount 订单发货信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $cdiscount_token				cdiscount token 受权信息
	 * @param     $params           		cdiscount 订单发货需要 的信息     
	 * 											json 版本: {"order_id":"54bdae5ebdd1090960a1e0d1","reason_code":"","reason_note":""}
	 * 
	 * Refund Reason Codes -- All Orders
	 * 
	 *
	 * @param	  $timeout					等待cdiscount proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					CdiscountInterface_Helper::refundOrder($cdiscount_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function refundOrder($cdiscount_token , $params = [] , $timeout=120){
		$api_name = "refundOrderById";
		return self::_callCdiscountOrderApi($api_name, $cdiscount_token,$params);
	}//end of refundOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 调用 cdiscount proxy 的接口完全cdiscount api的调用  
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	  $api_name					调用cdiscount api 的类型 (GetOrderList , CreateRefundVoucherAfterShipment , ValidateOrderList)共3种
	 * @param     $wish_token				cdiscount token 受权信息
	 * @param     $params           		cdiscount 订单发货需要 的信息
	 * 										json 版本: {"order_id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""}
	 * @param	  $timeout					等待cdiscount proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					CdiscountInterface_Helper::_modifiedOrderShippingInfo($wish_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/7/12		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _callCdiscountOrderApi($api_name, $cdiscount_token , $params = [] , $timeout=120){
		/*
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS1 = (memory_get_usage()/1024/1024);
		\Yii::info("Cdiscount $api_name order token =".$cdiscount_token." params=".$params." now memory=".($logMemoryMS1)."M ","file");
		*/
		
		$get_param['query_params'] = json_encode($params);
		$get_param['config'] = json_encode(array('tokenid'=>$cdiscount_token));

		$retInfo=CdiscountProxyConnectHelper::call_Cdiscount_api($api_name,$get_param , [] , $timeout );
		/*
		if (is_array($retInfo))
			$retInfo = json_encode($retInfo);
		*/
			
		/*
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS2 = (memory_get_usage()/1024/1024);
		\Yii::info("Cdiscount $api_name token =".$cdiscount_token." params=".$params." rtinfo=".$retInfo.",t2_1=".($logTimeMS2-$logTimeMS1).",memory=".($logMemoryMS2-$logMemoryMS1)."M ","file");
		*/
		return $retInfo;
	}//end of _callCdiscountOrderApi
	
	
	public static function hcCdiscountOrderSingShipped(){
		$distinctUsers = SaasCdiscountUser::find()->distinct(true)->select("uid")->where(['is_active'=>1])->asArray()->all();
		foreach ($distinctUsers as $row){
			self::_hcCdiscountOrderSingShipped($row['uid']);
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 对CD订单的标记发货结果进行自动检测  
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	  $uid
	 * @param     $startTime
	 * @param     $endTime
	 * @param	  
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2016/3/22		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	 private static function _hcCdiscountOrderSingShipped($uid){
	 	 
		//$journal_id = SysLogHelper::InvokeJrn_Create("Order", __CLASS__, __FUNCTION__ ,array(  ));
		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, array(  ));
		
		//$lastTime = \Yii::$app->redis->hget('CdiscountOms_OrderHcTime',"user_$uid.endTime");
		$lastTime = RedisHelper::RedisGet('CdiscountOms_OrderHcTime',"user_$uid.endTime");
		if(empty($lastTime))
			$startTime = time()-3600*24*7;
		else{
			$startTime = strtotime($lastTime)-1800;
		}
		$endTime = $startTime + 3600;

		try {
			//时间段内物流操作完成的订单
			$order_shipped = OdOrderShipped::find()->where(['order_source'=>'cdiscount','status'=>1])->andWhere(" `updated` >= $startTime and `updated` <= $endTime ")->orderBy(" id ASC ")->all();
			$order_shipped_no = [];
			$order_shipped_Models = [];
			echo "\n $uid get order shipped count:".count($order_shipped) ;
			foreach ($order_shipped as $shipped){
				$order_shipped_Models[$shipped->order_source_order_id] = $shipped;
				if(!in_array($shipped->order_source_order_id, $order_shipped_no))
					$order_shipped_no[] = $shipped->order_source_order_id;
			}
			//同时，存在于queue_syncshipped表,状态为标记成功的订单
			$orders_in_queue_syncshipped = QueueSyncshipped::find()
				->where(['uid'=>$uid,'order_source'=>'cdiscount','order_source_order_id'=>$order_shipped_no,'status'=>1])->orderBy(" id ASC ")->all();
			
			$order_sing_shipped_complet = [];
			$orders_in_queue_syncshipped_Models = [];
			echo "\n $uid get orders in queue_syncshipped count:".count($orders_in_queue_syncshipped) ;
			foreach ($orders_in_queue_syncshipped as $sing_shipped){
				$orders_in_queue_syncshipped_Models[$sing_shipped->order_source_order_id] = $sing_shipped;
				if(!in_array($sing_shipped->order_source_order_id, $order_sing_shipped_complet))
					$order_sing_shipped_complet[] = $sing_shipped->order_source_order_id;
			}
			
			$hc_orders = OdOrder::find()->where(['order_source_order_id'=>$order_sing_shipped_complet,'order_source_status'=>'WaitingForShipmentAcceptation'])->all();
			echo "\n $uid get hc orders count:".count($hc_orders) ;
			foreach ($hc_orders as $order){
				$sing_shipped_retry = 0;
				//预留重试上限：等order表的addi_info
				/*
				 $addi_info = $order->addi_info;
				$addi_info = json_decode($addi_info,true);
				if(empty($addi_info)){
				$addi_info = ['sing_shipped_retry'=>1];
				}else{
				if(empty($addi_info['sing_shipped_retry']))
					$addi_info['sing_shipped_retry'] = 1;
				else
					$addi_info['sing_shipped_retry'] += 1;
					$sing_shipped_retry = $addi_info['sing_shipped_retry'];
				}
				$order->addi_info = json_encode($addi_info);
				if($addi_info['sing_shipped_retry'] >= 5)
					$order->weird_status = 'ssmf';
				$order->save(fasle);
				*/
				$queueSyncShipped = isset($orders_in_queue_syncshipped_Models[$order->order_source_order_id])?$orders_in_queue_syncshipped_Models[$order->order_source_order_id]:[];
				if(!empty($queueSyncShipped) && $queueSyncShipped->status==1){
					$queueSyncShipped->status = 0;
					$queueSyncShipped->save(false);
				}
				$order_track = isset($order_shipped_Models[$order->order_source_order_id])?$order_shipped_Models[$order->order_source_order_id]:[];
				if(!empty($order_track) && $order_track->status==1){
					$order_track->status = 0;
					$order_track->result = null;
					$order_track->updated = time();
					if($sing_shipped_retry>=5)
						$order_track->errors = '后台重试标记发货5次仍然失败！你可能需要到CD后台手动标记发货。';
					$order_track->save(false);
				}
			}
			
			//$set_redis = \Yii::$app->redis->hset('CdiscountOms_OrderHcTime',"user_$uid.endTime",date("Y-m-d H:i:s",$endTime));
			$set_redis = RedisHelper::RedisSet('CdiscountOms_OrderHcTime',"user_$uid.endTime",date("Y-m-d H:i:s",$endTime));
			
		}catch (\Exception $e) {
			echo $e->getMessage();
		}
	}
	
}