<?php
namespace common\api\ensogointerface;

use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\listing\helpers\EnsogoHelper;

class EnsogoInterface_Helper {
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回ensogo可选的物流方式
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
	 * @invoking					EnsogoInterface_Helper::getShippingCodeNameMap();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getShippingCodeNameMap(){
		return EnsogoHelper::getEnsogoShippingMethodMapping();
	}//end of getShippingCodeNameMap
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ensogo 订单发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $ensogo_token				ensogo token 受权信息
	 * @param     $params           		ensogo 订单发货需要 的信息 
	 * 											json 版本: {"id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""}
	 * @param	  $timeout					等待ensogo proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					EnsogoInterface_Helper::_ShippedOrder($ensogo_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function ShippedOrder($ensogo_token , $params = [] , $timeout=120) {
		$api_name = "getFulfillOrder";
		return self::_callEnsogoOrderApi($api_name, $ensogo_token,$params);
	}//end of ShippedOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改,更新ensogo 订单发货信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $ensogo_token				ensogo token 受权信息
	 * @param     $params           		ensogo 订单发货需要 的信息
	 * 											json 版本: {"id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""}
	 * @param	  $timeout					等待ensogo proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					EnsogoInterface_Helper::_modifiedOrderShippingInfo($ensogo_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function updateTrackingInfo($ensogo_token , $params = [] , $timeout=120){
		$api_name = "getChangeTrackOrder";
		return self::_callEnsogoOrderApi($api_name, $ensogo_token,$params);
	}//end of updateTrackingInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改,更新ensogo 订单发货信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $ensogo_token				ensogo token 受权信息
	 * @param     $params           		ensogo 订单发货需要 的信息     
	 * 											json 版本: {"id":"54bdae5ebdd1090960a1e0d1","reason_code":"","reason_note":""}
	 * 
	 * Refund Reason Codes -- All Orders
	 *	1	Delayed Delivery
	 *	2	Courier company issue
	 *	3	Faulty Product
	 *	4	Out of stock
	 *	5	Merchant ceased operations
	 *	6	Payment error
	 *	7	Booking issues
	 *	8	Unsatisfactory service
	 *	9	Change in terms
	 *	10	Fraud
	 *	11	Others
	 *
	 * @param	  $timeout					等待ensogo proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					EnsogoInterface_Helper::refundOrder($ensogo_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function refundOrder($ensogo_token , $params = [] , $timeout=120){
		$api_name = "refundOrderById";
		return self::_callEnsogoOrderApi($api_name, $ensogo_token,$params);
	}//end of refundOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 调用 ensogo proxy 的接口完全ensogo api的调用  
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	  $api_name					调用ensogo api 的类型 (getFulfillOrder , getRefundOrder , getChangeTrackOrder)共3种
	 * @param     $ensogo_token				ensogo token 受权信息
	 * @param     $params           		ensogo 订单发货需要 的信息
	 * 											json 版本: {"id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""}
	 * @param	  $timeout					等待ensogo proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					EnsogoInterface_Helper::_modifiedOrderShippingInfo($ensogo_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _callEnsogoOrderApi($api_name, $ensogo_token , $params = [] , $timeout=120){
		/*
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS1 = (memory_get_usage()/1024/1024);
		\Yii::info("ensogo shipped order token =".$ensogo_token." params=".$params." now memory=".($logMemoryMS1)."M ","file");
		*/
		
		$reqParams = $params;
		$reqParams['access_token'] = $ensogo_token;
		
		$retInfo=EnsogoProxyConnectHelper::call_ENSOGO_api($api_name,$reqParams , [] , $timeout );
		if (is_array($retInfo))
			$retInfo = json_encode($retInfo);
		/*
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS2 = (memory_get_usage()/1024/1024);
		\Yii::info("ensogo shipped token =".$ensogo_token." params=".$params." rtinfo=".$retInfo.",t2_1=".($logTimeMS2-$logTimeMS1).",memory=".($logMemoryMS2-$logMemoryMS1)."M ","file");
		*/
		return $retInfo;
	}//end of _callEnsogoOrderApi
	
	
	
}