<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\HuaLeiBaseConfig;

class LB_HENANYOUYOUCarrierAPI extends BaseCarrierAPI
{
	static public $url1 = "www.zzpost.net:8082"; //提交订单地址
	static public $url2 = "www.zzpost.net:80";   //打印地址
	// 	http://123.57.13.147
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2017/03/30			初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data)
	{
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2, 'LB_HENANYOUYOUCarrierAPI');
		$return_info = $base_config_obj->_getOrderNO();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}

	/**
	 +----------------------------------------------------------
	 * 取消订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2017/03/30			初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data)
	{
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2);
		$return_info = $base_config_obj->_cancelOrderNO();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}

	/**
	 +----------------------------------------------------------
	 * 交运(预报订单)
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw		2017/03/30			           初始化
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data)
	{
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2);
		$return_info = $base_config_obj->_doDispatch();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}

	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw		2017/03/30			            初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data)
	{
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2, 'LB_HENANYOUYOUCarrierAPI');
		$return_info = $base_config_obj->_getTrackingNO();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}

	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2017/03/30			初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data)
	{
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2);
		$return_info = $base_config_obj->_doPrint();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}

	/**
	 +----------------------------------------------------------
	 * 获取运输服务
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2017/03/30			初始化
	 +----------------------------------------------------------
	 **/
	public function getCarrierShippingServiceStr($data)
	{
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2);
		$return_info = $base_config_obj->_getCarrierShippingServiceStr();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}

	/**
	 +----------------------------------------------------------
	 * 用于验证物流账号信息是否真实
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2017/03/30			初始化
	 +----------------------------------------------------------
	 **/
	public function getVerifyCarrierAccountInformation($data)
	{
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2);
		return $base_config_obj->_getVerifyCarrierAccountInformation();
	}

	/*
	 * 用来确定打印完成后 订单的下一步状态
	*
	* 公共方法
	*/
	public static function getOrderNextStatus()
	{
		return OdOrder::CARRIER_FINISHED;
	}

	//获取标签格式
	public static function getCarrierLabelTypeStr($data){
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2);
		return $base_config_obj->_getCarrierLabelTypeStr();
	}

}

?>