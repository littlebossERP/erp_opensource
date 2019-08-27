<?php

namespace common\api\carrierAPI;

use common\api\carrierAPI\YiYuTongBaseConfig;

class LB_ZHITENGCarrierAPI extends BaseCarrierAPI
{
	static public $url1 = "http://www.zhiteng.biz:8888";
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2016/12/01			初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data)
	{
		$base_config_obj = new YiYuTongBaseConfig($data, self::$url1,'LB_ZHITENGCarrierAPI');
		$return_info = $base_config_obj->_getOrderNO();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2016/12/01			初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data)
	{
		$base_config_obj = new YiYuTongBaseConfig($data, self::$url1);
		$return_info = $base_config_obj->_cancelOrderNO();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运(预报订单)
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw		2016/12/01			           初始化
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data)
	{
		$base_config_obj = new YiYuTongBaseConfig($data, self::$url1);
		$return_info = $base_config_obj->_doDispatch();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw		2016/12/01			            初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data)
	{
		$base_config_obj = new YiYuTongBaseConfig($data, self::$url1,'LB_ZHITENGCarrierAPI');
		$return_info = $base_config_obj->_getTrackingNO();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2016/12/01			初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data)
	{
		//某些物流会固定打印设置参数的值
		$base_config_obj = new YiYuTongBaseConfig($data, self::$url1,'LB_ZHITENGCarrierAPI');
		$return_info = $base_config_obj->_doPrint();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取运输服务
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2016/12/01			初始化
	 +----------------------------------------------------------
	 **/
	public function getCarrierShippingServiceStr($data)
	{
		$base_config_obj = new YiYuTongBaseConfig($data, self::$url1);
		// TODO carrier user account @XXX@
		$return_info = $base_config_obj->_getCarrierShippingServiceStr('@XXX@','@XXX@');
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}
	
	/**
	 * 获取API的打印面单标签
	 * 这里需要调用接口货代的接口获取10*10面单的格式
	 *
	 * @param $SAA_obj			表carrier_user_label对象
	 * @param $print_param		相关api打印参数
	 * @return array()
	 * Array
	 (
	 [error] => 0	是否失败: 1:失败,0:成功
	 [msg] =>
	 [filePath] => D:\wamp\www\eagle2\eagle/web/tmp_api_pdf/20160316/1_4821.pdf
	 )
	 */
	public function getCarrierLabelApiPdf($SAA_obj, $print_param){
		$base_config_obj = new YiYuTongBaseConfig(array(), self::$url1);
		$return_info=$base_config_obj->_getCarrierLabelApiPdf($SAA_obj, $print_param);
		return $return_info;
	}
	
	
}