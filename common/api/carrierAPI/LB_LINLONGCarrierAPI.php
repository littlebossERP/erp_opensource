<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\YiYuTongBaseConfig;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\models\OdOrderShipped;

class LB_LINLONGCarrierAPI extends BaseCarrierAPI
{
    static public $url1 = "http://139.199.219.182:9595";
    
    /**
     +----------------------------------------------------------
     * 申请订单号
     +----------------------------------------------------------
     * log			name		date				note
     * @author		lgw 		2017/03/31			初始化
     +----------------------------------------------------------
     **/
    public function getOrderNO($data)
    {
        $base_config_obj = new YiYuTongBaseConfig($data, self::$url1,'LB_LINLONGCarrierAPI');
        $return_info = $base_config_obj->_getOrderNO();
        return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
    }
    
    /**
     +----------------------------------------------------------
     * 取消订单号
     +----------------------------------------------------------
     * log			name		date				note
     * @author		lgw 		2017/03/31			初始化
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
     * @author		lgw		2017/03/31			           初始化
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
     * @author		lgw		2017/03/31			            初始化
     +----------------------------------------------------------
     **/
    public function getTrackingNO($data)
    {
    	$base_config_obj = new YiYuTongBaseConfig($data, self::$url1,'LB_LINLONGCarrierAPI');
    	$return_info = $base_config_obj->_getTrackingNO();
    	return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
    }
    
    /**
     +----------------------------------------------------------
     * 打单
     +----------------------------------------------------------
     * log			name		date				note
     * @author		lgw 		2017/03/31			初始化
     +----------------------------------------------------------
     **/
    public function doPrint($data)
    {
    	//某些物流会固定打印设置参数的值
    	$base_config_obj = new YiYuTongBaseConfig($data, self::$url1,'LB_LINLONGCarrierAPI');
    	$return_info = $base_config_obj->_doPrint();
    	return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
    }
    
    /**
     +----------------------------------------------------------
     * 获取运输服务
     +----------------------------------------------------------
     * log			name		date				note
     * @author		lgw 		2017/03/31			初始化
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
     +----------------------------------------------------------
     * 用于验证物流账号信息是否真实
     +----------------------------------------------------------
     * log			name		date				note
     * @author		lgw 		2017/03/31			初始化
     +----------------------------------------------------------
     **/
    public function getVerifyCarrierAccountInformation($data)
    {
    	$base_config_obj = new YiYuTongBaseConfig($data, self::$url1);
    	$return_info=$base_config_obj->_getVerifyCarrierAccountInformation($data);
    	return $return_info['data'];
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
    
}

?>