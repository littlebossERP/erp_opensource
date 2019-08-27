<?php
namespace common\api\al1688interface;

class Al1688Interface_Api extends Al1688Interface_Auth{
	
	private static $trade = 'com.alibaba.trade/';
	private static $logistics = 'com.alibaba.logistics/';
	private static $product = 'com.alibaba.product/';
	
	/**
	 * 订单列表查看(买家视角)
	 */
	function getBuyerOrderList($param = []){
	    return $this->request(__FUNCTION__, $param, 1, self::$trade.'alibaba.trade.getBuyerOrderList');
	}
	
	/**
	 * 订单详情查看(买家视角)
	 */
	function getBuyerView($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$trade.'alibaba.trade.get.buyerView');
	}
	
	/**
	 * 我的发货地址列表
	 */
	function getSendGoodsAddressList($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$logistics.'alibaba.logistics.mySendGoodsAddress.list.get');
	}
	
	/**
	 * 根据旺铺域名获取旺铺的userId跟公司名称
	 */
	function getUserInfoMember($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$trade.'alibaba.member.getUserInfo');
	}
	
	/**
	 * 获取非授权用户的简单商品信息
	 */
	function getProduct($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$product.'alibaba.agent.product.simple.get');
	}
	
	/**
	 * 跨境场景获取商品列表
	 */
	function getProductList($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$product.'alibaba.cross.productList');
	}
	
	/**
	 * 买家获取保存的收货地址信息列表
	 */
	function getReceiveAddress($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$trade.'alibaba.trade.receiveAddress.get');
	}
	
	/**
	 * 快速创建1688订单
	 */
	function fastCreateOrder($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$trade.'alibaba.trade.fastCreateOrder');
	}
	
	/**
	 * 获取支付宝支付链接
	 */
	function getPayUrl($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$trade.'alibaba.alipay.url.get');
	}
	
	/**
	 * 获取交易订单的物流信息(买家视角) 
	 */
	function getLogisticsInfosBuyer($param = []){
		return $this->request(__FUNCTION__, $param, 1, self::$logistics.'alibaba.trade.getLogisticsInfos.buyerView');
	}

    
}
