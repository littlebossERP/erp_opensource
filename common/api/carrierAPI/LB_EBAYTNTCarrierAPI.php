<?php

namespace common\api\carrierAPI;
use common\api\ebayinterface\config;
use eagle\modules\order\models\OdOrder;
/**
+------------------------------------------------------------------------------
 * ebay订单TNT接口业务逻辑类
+------------------------------------------------------------------------------
 * @category	Interface
 * @package
 * @subpackage  Exception
 * @author		chenbin <75281681@qq.com>
 * @version		1.0
+------------------------------------------------------------------------------
 */
class LB_EBAYTNTCarrierAPI extends BaseCarrierAPI
{
    //SoapClient实例
    public $soap = null;
    //物流接口
    static public $wsdl = "https://api.apacshipping.sandbox.ebay.com.hk/aspapi/v4/ApacShippingService?wsdl";
    //EBAY验证信息
    static public $pubinfo = array();
    //初始化
    public function __construct(){
        //取得EBAY验证信息
        if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
            $ebayConfig = config::$token;
            self::$wsdl = 'https://api.apacshipping.ebay.com.hk/aspapi/v4/ApacShippingService?wsdl';
        }else{
            $ebayConfig = config::$tokenSandbox;
            self::$wsdl = 'https://api.apacshipping.sandbox.ebay.com.hk/aspapi/v4/ApacShippingService?wsdl';
        }
        self::$pubinfo['APIDevUserID'] = $ebayConfig['devID'];
        self::$pubinfo['AppID'] = $ebayConfig['appID'];
        self::$pubinfo['AppCert'] = $ebayConfig['certID'];
        self::$pubinfo['Version'] = '4.0.0';
    }
    //申请
    public function getOrderNO($data){
        $base_config_obj = new EbayBaseConfig($data,self::$pubinfo,self::$wsdl,1,0);
        $return_info = $base_config_obj->_getOrderNo();
        return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
    }

    //取消
    public function cancelOrderNO($data){
        $base_config_obj = new EbayBaseConfig($data,self::$pubinfo,self::$wsdl,1,1);
        $return_info = $base_config_obj->_cancelOrderNO();
        return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
    }

    //交运
    public function doDispatch($data){
        $base_config_obj = new EbayBaseConfig($data,self::$pubinfo,self::$wsdl,1,1);
        $return_info = $base_config_obj->_doDispatch();
        return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
    }

    //申请跟踪号
    public function getTrackingNO($data){
        $base_config_obj = new EbayBaseConfig($data,self::$pubinfo,self::$wsdl,1,1,2);
        $return_info = $base_config_obj->_getTrackingNo();
        return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
    }

    //打单
    public function doPrint($data){
        $base_config_obj = new EbayBaseConfig($data,self::$pubinfo,self::$wsdl,1,1,2);
        $return_info = $base_config_obj->_doPrint();
        return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
    }

    /*
     * 用来确定打印完成后 订单的下一步状态
     */
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }
}