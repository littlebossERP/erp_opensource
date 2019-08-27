<?php
namespace common\api\wishinterface;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\listing\helpers\WishHelper;

class WishInterface_Helper {
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回wish可选的物流方式
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
	 * @invoking					WishInterface_Helper::getShippingCodeNameMap();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getShippingCodeNameMap(){
		return WishHelper::getWishShippingMethodMapping();
	}//end of getShippingCodeNameMap
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * wish 订单发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $wish_token				wish token 受权信息
	 * @param     $params           		wish 订单发货需要 的信息 
	 * 											json 版本: {"order_id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""}
	 * @param	  $timeout					等待wish proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					WishInterface_Helper::_ShippedOrder($wish_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function ShippedOrder($wish_token , $params = [] , $timeout=120) {
		$api_name = "fulfillOrderById";
		return self::_callWishOrderApi($api_name, $wish_token,$params);
	}//end of ShippedOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改,更新wish 订单发货信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $wish_token				wish token 受权信息
	 * @param     $params           		wish 订单发货需要 的信息
	 * 											json 版本: {"order_id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""}
	 * @param	  $timeout					等待wish proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					WishInterface_Helper::_modifiedOrderShippingInfo($wish_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function updateTrackingInfo($wish_token , $params = [] , $timeout=120){
		$api_name = "updateTrackingInfoById";
		return self::_callWishOrderApi($api_name, $wish_token,$params);
	}//end of updateTrackingInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改,更新wish 订单发货信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $wish_token				wish token 受权信息
	 * @param     $params           		wish 订单发货需要 的信息     
	 * 											json 版本: {"order_id":"54bdae5ebdd1090960a1e0d1","reason_code":"","reason_note":""}
	 * 
	 * Refund Reason Codes -- All Orders
	 *	-1	其他
	 *	18	误下单了
	 *	20	配送时间过长
	 *	22	商品不合适
	 *	23	收到错误的商品
	 *	24	商品为假冒伪劣品
	 *	25	商品已损坏
	 *	26	商品与描述不符
	 *	27	商品与清单不符
	 *	30	产品被配送至错误的地址
	 *	31	用户提供了错误的地址
	 *	32	Item was returned to sender
	 *	33	Incomplete Order
	 *	34	店铺无法履行订单
	 *	1001	Received the wrong color
	 *	1002	Item is of poor quality
	 *	1004	Product listing is missing information
	 *	1005	Item did not meet expectations
	 *	1006	Package was empty
	 *	Refund Reason Codes -- Unfulfilled Orders Only
	 *	1	店铺无法履行订单
	 *
	 * @param	  $timeout					等待wish proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					WishInterface_Helper::refundOrder($wish_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function refundOrder($wish_token , $params = [] , $timeout=120){
		$api_name = "refundOrderById";
		return self::_callWishOrderApi($api_name, $wish_token,$params);
	}//end of refundOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 调用 wish proxy 的接口完全wish api的调用  
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	  $api_name					调用wish api 的类型 (fulfillOrderById , refundOrderById , updateTrackingInfoById)共3种
	 * @param     $wish_token				wish token 受权信息
	 * @param     $params           		wish 订单发货需要 的信息
	 * 											json 版本: {"order_id":"54bdae5ebdd1090960a1e0d1","tracking_provider":"Singapo SPack IMAIR","tracking_number":"","ship_note":""}
	 * @param	  $timeout					等待wish proxy 返回的结果 超时限制 单位(秒)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					WishInterface_Helper::_modifiedOrderShippingInfo($wish_token, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _callWishOrderApi($api_name, $wish_token , $params = [] , $timeout=120){
		/*
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS1 = (memory_get_usage()/1024/1024);
		\Yii::info("wish shipped order token =".$wish_token." params=".$params." now memory=".($logMemoryMS1)."M ","file");
		*/
		if (is_array($params))
			$params =json_encode($params);
		
		$reqParams['parms']  = $params;
		$reqParams['token'] = $wish_token;
		
		$retInfo=WishProxyConnectHelper::call_WISH_api($api_name,$reqParams , [] , $timeout );
		if (is_array($retInfo))
			$retInfo = json_encode($retInfo);
		/*
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS2 = (memory_get_usage()/1024/1024);
		\Yii::info("wish shipped token =".$wish_token." params=".$params." rtinfo=".$retInfo.",t2_1=".($logTimeMS2-$logTimeMS1).",memory=".($logMemoryMS2-$logMemoryMS1)."M ","file");
		*/
		return $retInfo;
	}//end of _callWishOrderApi
	
	
	
}