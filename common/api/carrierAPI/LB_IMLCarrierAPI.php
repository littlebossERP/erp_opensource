<?php

namespace common\api\carrierAPI;

use common\api\carrierAPI\NiuMenBaseConfig;


class LB_IMLCarrierAPI extends BaseCarrierAPI{


	static public $url = "47.89.44.92";
	

	//申请
	public function getOrderNO($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_getOrderNo();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}
	
	//取消
	public function cancelOrderNO($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_cancelOrderNO();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}
	
	//交运
	public function doDispatch($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_doDispatch();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}
	
	//申请跟踪号
	public function getTrackingNO($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_getTrackingNo();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}
	
	//打单
	public function doPrint($data){
		//print_r (self::getAjxEmsQueryPDFLabel($data));exit;
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_doPrint();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}
	
	//获取运输服务
	public function getCarrierShippingServiceStr($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_getCarrierShippingServiceStr();
		return $return_info;
	}

	public function getVerifyCarrierAccountInformation($data){
		$base_config_obj = new NiuMenBaseConfig('',self::$url);
		$return_info = $base_config_obj->_getVerifyCarrierAccountInformation($data);
		return $return_info['data'];
	}

	//获取打印标签
	public function getAjxEmsQueryPDFLabel($data){
		$base_config_obj = new NiuMenBaseConfig('',self::$url);
		$return_info = $base_config_obj->getAjxEmsQueryPDFLabel();
		return $return_info;
	}
}



?>