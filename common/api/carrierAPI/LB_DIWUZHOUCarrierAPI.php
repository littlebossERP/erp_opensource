<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\NiuMenBaseConfig;

class LB_DIWUZHOUCarrierAPI extends BaseCarrierAPI
{
        //    public static $url = null;
        //    function __construct(){
        //        if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
        //            self::$url = 'www.dwz56.com/cgi-bin/EmsData.dll?DoApp';  //正式环境接口地址
        //        }else{self::$url = 'www.dwz56.com/cgi-bin/EmsData.dll?DoApp';}//测试环境接口地址
        //    }

        //递五洲的资源地址
        static public $url = "www.dwz56.com";

        //下面的地址用于测试钮门系统
        // 	static public $url = "114.215.188.226";

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

        //自己测试接口用
         public function testApi(){
    //        $requestName = 'EmsKindList';
    //        $icID = 43;
    //        $TimpStamp = '1463624358'.'000';
    //        $token = 'S6LneB6sZg2VYbO';
    //        $md5 = md5($icID.$TimpStamp.$token);
    //        // $request_body = json_encode(array('RequestName'=>'TimeStamp'));
    //        $request_body = json_encode(array('RequestName'=>$requestName,'icID'=>$icID,'TimeStamp'=>$TimpStamp,'MD5'=>$md5));
    //        $response = Helper_Curl::post(self::$url,$request_body);
    //        $dataArr = json_decode($response,true);
         }

}