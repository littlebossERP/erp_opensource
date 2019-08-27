<?php
namespace common\api\shopeeinterface;

class ShopeeInterface_Api extends ShopeeInterface_Base{
	
    private static $shop = 'shop/';
	private static $order = 'orders/';
	private static $item = 'item/';
	private static $logistics = 'logistics/';
	
	/**
	 * 订单列表信息，根据更新时间
	 */
	function GetOrdersList($param = []){
	    return $this->request(__FUNCTION__, $param, 1, self::$order.'basics');
	}
	
	/**
	 * 订单列表信息，根据状态
	 */
	function GetOrdersByStatus($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$order.'get');
	}
	
	/**
	 * 订单详情信息
	 */
	function GetOrderDetails($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$order.'detail');
	}
	
	/**
	 * 商品详情信息
	 */
	function GetItemDetail($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$item.'get');
	}
	
	/**
	 * 获取所有支持的物流渠道
	 */
	function GetLogistics($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$logistics.'channel/get');
	}
	
	/**
	 * 获取卖家地址
	 */
	function GetAddress($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$logistics.'address/get');
	}
	
	/**
	* 标记发货
	*/
	function SetTrackingNo($param = []){
	return $this->request(__FUNCTION__, $param, 1, self::$logistics.'tracking_number/set_mass');
	}
	
	/**
	 * 获取订单发货方式
	 */
	function GetParameterForInit($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$logistics.'init_parameter/get');
	}
	
	/**
	 * 获取订单发货方式
	 */
	function GetBranch($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$logistics.'branch/get');
	}
	
	/**
	 * 线上发货
	 */
	function LogisticsInit($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$logistics.'init');
	}
	
	/**
	 * 获取跟踪号
	 */
	function GetTrackingNo($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$logistics.'tracking_number/get_mass');
	}
	
	/**
	 * 获取标签
	 */
	function GetAirwayBill($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$logistics.'airway_bill/get_mass');
	}
	
	/**
	 * Use this call to get information of shop
	 */
	function GetShopInfo($param = []){
	    return $this->request(__FUNCTION__, $param, 1, self::$shop.'get');
	}

    
}
