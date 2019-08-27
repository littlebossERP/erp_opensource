<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;

class LB_XIAPUCENTURYCarrierAPI extends BaseCarrierAPI{
    static private $wsdl = '';	// 物流接口
    static private $token = '';
    private $submitGate = null;	// SoapClient实例
    
    public $companyID = null;
    public $pwd = null;
    
    public function __construct(){
        //夏浦世纪
        self::$wsdl = 'http://www.sc-hpl.com/webservice/';
		//TODO carrier dev account @XXX@
        self::$token = '@XXX@';
    }
    
    public function getCarrier(){
        $header=array();
        $header[]='Content-Type:text/xml;charset=utf-8';

        $request_xml2="<?xml version='1.0' encoding='UTF-8'?>
<GetOrderCarrierRequest xmlns='http://www.sc-hpl.com/webservices/'>
   <apiCredential>
        <authToken>".self::$token."</authToken>
   </apiCredential>
</GetOrderCarrierRequest>";
           $response = Helper_Curl::post(self::$wsdl,$request_xml2,$header);
           $xml = simplexml_load_string($response);
//            $xml_to_array = self::obj2ar($xml);
//            print_r($xml_to_array);exit();
           $string='';
           $aa = $xml->carrierList;
           foreach ($aa->carrier as $cc){
               $string.="{$cc->carrierCode}:{$cc->carrierName};";
           }
           echo $string;
//            $bb = $aa->carrier;
//            print_r($bb); 
    }
    /**
     +----------------------------------------------------------
     * 申请订单号
     +----------------------------------------------------------
     **/
    public function getOrderNO($data){
        try{
            $order = $data['order'];  //object OdOrder
            $form_data = $data['data'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0,0,$order);//第一个参数检验puid，第二个检验是否存在相关订单
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];
            $Service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
            //认证参数
            $params=$account->api_params;
            $token=$params['authToken'];
            
            $sum=count($form_data['cdValue']);//统计商品数量
            $cd_str='';
            for($i=0;$i<$sum;$i++){
                if(!empty($form_data['cdQuantity'][$i])){
                    $cdQuantity=$form_data['cdQuantity'][$i];//数量，必填且要为数字
                }else{
                    throw new CarrierException("数量不能为空！");
                }
                
                $cdTitleCn=$form_data['cdTitleCn'][$i];
                $cdTitleEn=$form_data['cdTitleEn'][$i];//英文需要限制长度不能超过40
                if(empty($cdTitleEn)){
                    throw new CarrierException("英文报关名不能为空！");
                }
                if(strlen($cdTitleEn)>40){
                    throw new CarrierException("英文报关名过长,长度不能超过40");//检查中文报关名的长度
                }
                
                if(!empty($form_data['cdWeight'][$i])){
                    $cdWeight=($form_data['cdWeight'][$i]*$cdQuantity)/1000;//必填,单位kg
                }else{
                    throw new CarrierException("报关重量不能为空！");
                }
                
                if(!empty($form_data['cdValue'][$i])){
                    $cdValue=($form_data['cdValue'][$i]*$cdQuantity);//必填且要为数字
                }else {
                    throw new CarrierException("报关价值不能为空！");
                }
                
                if(!empty($form_data['currency'])){//申报货币
                    $Currency=$form_data['currency'];
                }else{
                    $Currency="USD";
                }
                
                $addressAndPhoneParams = array(
                    'address' => array(
                        'consignee_address_line1_limit' => 119,
                        'consignee_address_line2_limit' => 59,
                        'consignee_address_line3_limit' => 59,
                    ),
                    'consignee_district' => 1,
                    'consignee_county' => 1,
                    'consignee_company' => 1,
                    'consignee_phone_limit' => 100
                );
                	
                $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
                
                //填写报关信息
                $cd_str.="<CustomsDeclaration>
<cdTitleCn>".$cdTitleCn."</cdTitleCn>
<cdTitleEn>".$cdTitleEn."</cdTitleEn>
<cdQuantity>".$cdQuantity."</cdQuantity>
<cdWeight>".$cdWeight."</cdWeight>
<cdValue>".$cdValue."</cdValue>
</CustomsDeclaration>";      
            }
            //填写订单的所有信息
            $address=!empty($order->consignee_address_line1)?$order->consignee_address_line1:(!empty($order->consignee_address_line2)?$order->consignee_address_line2:$order->consignee_address_line3);
            if(empty($address)){
                throw new CarrierException("收货地址不能为空！");
            }
                
            $header=array();
            $header[]='Content-Type:text/xml;charset=utf-8';
            $getorder_xml="<?xml version='1.0' encoding='UTF-8'?>
<CreateDeliveryOrderRequest xmlns='http://www.sc-hpl.com/webservices/'>
<apiCredential>
<authToken>".$token."</authToken>
</apiCredential>
<referenceCode>".$order->order_source_order_id."</referenceCode>
<sender></sender>
<name>".$order->consignee."</name>
<buyerid>".$order->source_buyer_user_id."</buyerid>
<carrier>".$Service->shipping_method_code."</carrier>
<country>".$order->consignee_country_code."</country>
<address1>".$addressAndPhone['address_line1']."</address1>
<address2>".$addressAndPhone['address_line2']."</address2>
<address3>".$addressAndPhone['address_line3']."</address3>
<city>".$order->consignee_city."</city>
<state>".$order->consignee_province."</state>
<postalCode>".$order->consignee_postal_code."</postalCode>
<email>".$order->consignee_email."</email>
<phone>".$addressAndPhone['phone1']."</phone>
<remark>".$form_data['remark']."</remark>
<items>
<item>
<itemCode></itemCode>
<itemTitle></itemTitle>
<itemQuantity></itemQuantity>
<itemCurrency></itemCurrency>
<itemUnitPrice></itemUnitPrice>
<itemTotalPrice></itemTotalPrice>
<itemSnapUrl></itemSnapUrl>
</item>
</items>
<CustomsDeclarations>".$cd_str."</CustomsDeclarations>
<CustomsType>".$form_data['CustomsType']."</CustomsType>
<CustomsCurrency>".$Currency."</CustomsCurrency>
</CreateDeliveryOrderRequest>";
//             echo $getorder_xml;exit();
            $response = Helper_Curl::post(self::$wsdl,$getorder_xml,$header);
            $xml = simplexml_load_string($response);//将xml转化为对象
//             print_r($xml);exit();
            if($xml->success=='TRUE'){
                $track_no=empty($xml->trackingNo)?null:$xml->trackingNo;
                $r = CarrierAPIHelper::orderSuccess($order,$Service,$xml->deliveryCode,OdOrder::CARRIER_WAITING_GETCODE ,$track_no);
                return  self::getResult(0,$r,'操作成功!订单参考号'.$xml->deliveryCode);
            }else{
                $err=$xml->errors;
                $str='';
                foreach ($err->error as $err_message){//因为此处error是一个对象，无论error是多个还是单个，都可以直接foreach
                    $code=(int)$err_message->errorCode;
                    $status=$this->getOrderStatus($code);
                    if($status){
//                         $str.= $status.'；';
                        return self::getResult(1,'','错误信息:'.$status);//1为错误信息
                    }else if(!empty($err_message->errorMessage)){//没有相关代码的错误中文信息
                        return self::getResult(1,'','错误信息:'.$err_message->errorMessage);
                    }else{
                        return self::getResult(1,'','物流商返回数据错误');//假如物流商没有返回相关的错误信息代码，可能物流商出错
                    }
                }
//                 return self::getResult(1,'','错误信息:'.$str);//一次性报所有错
            }
//             print_r($info);exit();
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }

    
    /*
     * 获取上传订单状态信息
     */
    public function getOrderStatus($key){
        $arr = [
            100=>'请求方法有误',
            101=>'凭证不能为空',
            102=>'非法凭证',
            103=>'非法渠道代码',
            104=>'订单参考号不能为空',
            500=>'非法包裹条码',
            600=>'包裹状态不为open，已不能修改',
            700=>'跟踪码用完，请联系我司添加跟踪码',
            999=>'其他错误',
            1010=>'必填字段不能为空',
            1011=>'字段长度不能超过指定长度',
        ];
        if(isset($arr[$key]))return $arr[$key];
        else return false;
    }
    
    /**
     +----------------------------------------------------------
     * 交运
     +----------------------------------------------------------
     **/
    public function doDispatch($data){
        return self::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');
    }
    /**
     +----------------------------------------------------------
     * 取消跟踪号
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2015/08/12				初始化
     +----------------------------------------------------------
     **/
    /**
     +----------------------------------------------------------
     * 申请跟踪号
     +----------------------------------------------------------
     **/
    public function getTrackingNO($data){
        try{
            $order = $data['order'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0,1,$order);
            $shipped = $checkResult['data']['shipped'];
             
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];
            //认证参数
            $params=$account->api_params;
            $token=$params['authToken'];
            
            if(empty($order->customer_number)){
                throw new CarrierException("物流商参考号获取失败！");
            }
            
            $header=array();
            $header[]='Content-Type:text/xml;charset=utf-8';
        
            $getorder_xml="<?xml version='1.0' encoding='UTF-8'?>
<GetDeliveryOrderRequest xmlns='http://www.sc-hpl.com/webservices/'>
<apiCredential>
<authToken>".$token."</authToken>
</apiCredential>
<deliveryorderList>
<deliveryorder>
<deliveryCode>".$order->customer_number."</deliveryCode>
</deliveryorder>
</deliveryorderList>
</GetDeliveryOrderRequest>";
            $response = Helper_Curl::post(self::$wsdl,$getorder_xml,$header);
            $xml = simplexml_load_string($response);
            if($xml){//假如查找失败，为空，否则有数据,
              if($xml->success=="FALSE"){//有错误信息，报错
                  $err=$xml->errors;
                  foreach ($err->error as $err_message){//因为此处error是一个对象，无论error是多个还是单个，都可以直接foreach
                      $code=(int)$err_message->errorCode;
                      $status=$this->getOrderStatus($code);
                      if($status){
//                             $str.= $status.'；';
                         return self::getResult(1,'','错误信息:'.$status);
                      }else {
                         return self::getResult(1,'','物流商返回数据错误');//假如物流商没有返回相关的错误信息代码，可能物流商出错
                      }
                  }
              }else{//另一种就是查找成功，查看是否有跟踪号
                  if(empty($xml->deliveryorderList->deliveryorder->trackingNO)){
                      return self::getResult(1,'','暂时没获取到跟踪号！');
                  }else{//物流号获取成功 
                      $shipped->tracking_number=$xml->deliveryorderList->deliveryorder->trackingNO;
                      $shipped->save();
                      $order->tracking_number = $shipped->tracking_number;
                      $order->save();
                      return  self::getResult(0,'','查询成功成功!跟踪号'.$xml->deliveryorderList->deliveryorder->trackingNO);
                  }
              }           
            }else{
                return self::getResult(1,'','没有找到相关的订单信息');//1为失败
            }
        
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }
    

    /**
     +----------------------------------------------------------
     * 打单
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2015/08/12				初始化
     +----------------------------------------------------------
     **/
    public function doPrint($data){
        try{
            $user=\Yii::$app->user->identity;
            if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();
             
            $all_message = current($data);reset($data);//打印时是逐个运输方式的多张订单传入，所以获取一次account、service的信息就可以了
            $order_object=$all_message['order'];//获取订单的对象
            //获取到所需要使用的数据
            //              print_r($all_message);
            $info = CarrierAPIHelper::getAllInfo($order_object);
            $account = $info['account'];
            $service = $info['service'];
            
            $params=$account->api_params;
            $token=$params['authToken'];
            
            $service_params=$service->carrier_params;
            //              print_r($info);
            $str_deliveryCode='';
            foreach ($data as $detail_data){//拼接打印单号
                $order = $detail_data['order'];
                if(empty($order->customer_number)){
                    throw new CarrierException("物流商参考号获取失败！");
                }
                $str_deliveryCode.="<deliveryCode>".$order->customer_number."</deliveryCode>";
            }
            
            if(empty($service_params['printType'])){//必须填入打印格式
                throw new CarrierException("必须选择打印格式！");
            }
            
            $header=array();
            $header[]='Content-Type:text/xml;charset=utf-8';
        
            $getorder_xml="<?xml version='1.0' encoding='UTF-8'?>
<GetWebPrintUrlRequest xmlns='http://www.sc-hpl.com/webservices/'>
<apiCredential>
<authToken>".$token."</authToken>
</apiCredential>
<printType>".$service_params['printType']."</printType>
<showRemark>".$service_params['showRemark']."</showRemark>
<showTitle>FALSE</showTitle>
<showSku>FALSE</showSku>
<printCustoms>".$service_params['printCustoms']."</printCustoms>
<deliveryCodeList>".$str_deliveryCode."</deliveryCodeList>
</GetWebPrintUrlRequest>";
            $response = Helper_Curl::post(self::$wsdl,$getorder_xml,$header);
            $xml = simplexml_load_string($response);
            if($xml->success=="FALSE"){//有错误信息，报错
                 $err=$xml->errors;
                 foreach ($err->error as $err_message){//因为此处error是一个对象，无论error是多个还是单个，都可以直接foreach
                     $code=(int)$err_message->errorCode;
                     $status=$this->getOrderStatus($code);
                     if($status){
//                             $str.= $status.'；';
                        return self::getResult(1,'','错误信息:'.$status);
                     }else {
                        return self::getResult(1,'','物流商返回数据错误');//假如物流商没有返回相关的错误信息代码，可能物流商出错
                     }
                 }
             }else{//即使不存在所查找的参考号，仍然会返回地址
                 if(!empty($xml->printurl)){
                     return self::getResult(0,['pdfUrl'=>$xml->printurl],'连接已生成,请点击并打印');//访问URL地址
                 }else{
                     return self::getResult(1,'','物流商返回数据错误');
                 }
             }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }
    
    

    /**
     +----------------------------------------------------------
     * 取消订单
     +----------------------------------------------------------
     **/
    public function cancelOrderNO($data){
        return self::getResult(1, '', '物流接口不支持取消物流单。');
    }
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }
    
    /**
     * 用于验证物流账号信息是否真实
     * $data 用于记录所需要的认证信息
     *
     * return array(is_support,error,msg)
     * 			is_support:表示该货代是否支持账号验证  1表示支持验证，0表示不支持验证
     * 			error:表示验证是否成功	1表示失败，0表示验证成功
     * 			msg:成功或错误详细信息
     */
    public function getVerifyCarrierAccountInformation($data){
        $result = array('is_support'=>1,'error'=>1);
    
        try{
            $header=array();
            $header[]='Content-Type:text/xml;charset=utf-8';
            
            $request_xml2="<?xml version='1.0' encoding='UTF-8'?>
<GetOrderCarrierRequest xmlns='http://www.sc-hpl.com/webservices/'>
   <apiCredential>
        <authToken>".$data['authToken']."</authToken>
   </apiCredential>
</GetOrderCarrierRequest>";
            $response = Helper_Curl::post(self::$wsdl,$request_xml2,$header);
            $xml = simplexml_load_string($response);
            $xml_to_array = self::obj2ar($xml);
            if(isset($xml_to_array['carrierList'])){
                $result['error'] = 0;
            }else if(isset($xml_to_array['success'])&&$xml_to_array['success'] == 'FALSE'){
                $result['error'] = 1;
            }
        }catch(CarrierException $e){
        }
    
        return $result;
    }
    
    public function obj2ar($obj) {
        if(is_object($obj)) {
            $obj = (array)$obj;
            $obj = self::obj2ar($obj);
        } elseif(is_array($obj)) {
            foreach($obj as $key => $value) {
                $obj[$key] = self::obj2ar($value);
            }
        }
        return $obj;
    }
    
    //获取运输方式
    public function getCarrierShippingServiceStr($account){
        try{
           $header=array();
           $header[]='Content-Type:text/xml;charset=utf-8';

        $request_xml2="<?xml version='1.0' encoding='UTF-8'?>
<GetOrderCarrierRequest xmlns='http://www.sc-hpl.com/webservices/'>
   <apiCredential>
        <authToken>".self::$token."</authToken>
   </apiCredential>
</GetOrderCarrierRequest>";
           $response = Helper_Curl::post(self::$wsdl,$request_xml2,$header);
           $xml = simplexml_load_string($response);
//            $xml_to_array = self::obj2ar($xml);
//            print_r($xml_to_array);exit();
           $channelStr="";
           $aa = $xml->carrierList;
           foreach ($aa->carrier as $cc){
               $channelStr.="{$cc->carrierCode}:{$cc->carrierName};";
           }
           
            if(empty($channelStr)){
                return self::getResult(1, '', '');
            }else{
                return self::getResult(0, $channelStr, '');
            }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }
}
