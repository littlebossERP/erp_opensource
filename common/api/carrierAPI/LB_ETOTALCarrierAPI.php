<?php
namespace common\api\carrierAPI;


use common\api\carrierAPI\HuaLeiBaseConfig;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;

class LB_ETOTALCarrierAPI extends BaseCarrierAPI
{
	static public $url1 = "ot.e-total.com:8082"; //提交订单地址
	static public $url2 = "39.108.122.80:8089";   //打印地址
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2017/10/27			初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data)
	{
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2, 'LB_ETOTALCarrierAPI');
		$return_info = $base_config_obj->_getOrderNO();
		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2017/10/27			初始化
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
	 * @author		hqw 		2017/10/27			初始化
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
	 * @author		hqw		2017/10/27			            初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data)
	{
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
// 		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2, 'LB_ETOTALCarrierAPI');
// 		$return_info = $base_config_obj->_getTrackingNO();
// 		return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 		2017/10/27			初始化
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
	 * @author		hqw 		2017/10/27			初始化
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
	 * @author		hqw 		2017/10/27			初始化
	 +----------------------------------------------------------
	 **/
	public function getVerifyCarrierAccountInformation($data)
	{
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2);
		return $base_config_obj->_getVerifyCarrierAccountInformation();
	}
	
	//获取标签格式
	public static function getCarrierLabelTypeStr($data){
		$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2);
		return $base_config_obj->_getCarrierLabelTypeStr();
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
		$base_config_obj = new HuaLeiBaseConfig(array(), self::$url1, self::$url2);
		$return_info=$base_config_obj->_getCarrierLabelApiPdf($SAA_obj, $print_param);
		return $return_info;
	}
	
}

?>