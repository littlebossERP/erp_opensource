<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\HuaLeiBaseConfig;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\models\OdOrderShipped;
use Jurosh\PDFMerge\PDFMerger;

class LB_WANYOUTONGCarrierAPI extends BaseCarrierAPI
{

    static private $m_selectAuthUrl;        //用户认证接口
    static private $m_createOrderUrl;       //创建物流单接口

    static public $url1 = "193.112.136.235:8082"; //提交订单地址
    static public $url2 = "193.112.136.235:8089";   //打印地址

    public function __construct() {
        self::$m_selectAuthUrl = 'http://' . self::$url1 . '/selectAuth.htm';
        self::$m_createOrderUrl = 'http://' . self::$url1 . '/createOrderApi.htm';
    }

    /**
     +----------------------------------------------------------
     * 申请订单号
     +----------------------------------------------------------
     * log			name		date				note
     * @author		dwg 		2016/11/21			初始化
     +----------------------------------------------------------
     **/
    public function getOrderNO($data)
    {
//        $base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2, 'LB_WANYOUTONGCarrierAPI');
//        $return_info = $base_config_obj->_getOrderNO();
//        return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);

        try
        {
            //验证用户登录
            $user = \Yii::$app->user->identity;
            if(empty($user))
                return ['error'=>1, 'data'=>'', 'msg'=>'用户登录信息缺失，请重新登录'];

            $puid = $user->getParentUid();
            //odOrder表内容
            $order = $data['order'];
            //表单提交的数据
            $form_data = $data['data'];

            //重复发货，添加不同的标识码
            $extra_id = isset($data['data']['extra_id']) ? $data['data']['extra_id'] : '';
            $customer_number = $data['data']['customer_number'];
            if(isset($data['data']['extra_id']))
                if($extra_id == '')
                    return ['error'=>1, 'data'=>'', 'msg'=>'强制发货标识码，不能为空'];

            //对当前条件的验证，如果订单已存在，则报错
            $checkResult = CarrierAPIHelper::validate(0, 0, $order, $extra_id, $customer_number);

            //获取物流商信息、运输方式信息等
            $info = CarrierAPIHelper::getAllInfo($order);
            $service = $info['service'];   //运输方式，表sys_shipping_service
            $service_carrier_params = $service->carrier_params;   //参数键值对
            $account = $info['account'];   //物流方式，表sys_carrier_account

            //获取物流商 账号 的认证参数
            $api_params = $account->api_params;

            $header = array();
            $header[] = 'Content-Type:text/xml;charset=utf-8';
            //账号认证
            $selectAuthUrl = self::$m_selectAuthUrl.'?username='.$api_params['username'].'&password='.$api_params['password'];
            $response = Helper_Curl::get($selectAuthUrl, '', $header);

            if(!empty($response))
            {
                $response = str_replace('\'', '"', $response);
                $response = json_decode($response);
                if(isset($response->ack) && $response->ack == 'true')
                {
                    $this->customer_userid = $response->customer_userid;
                    $this->customer_id = $response->customer_id;
                }
            }
            else
                return ['error'=>1, 'data'=>'', 'msg'=>'账户验证失败,e001'];

            if(empty($this->customer_userid) || empty($this->customer_id))
                return ['error'=>1, 'data'=>'', 'msg'=>'账户验证失败,e002'];

            //检测数据完整性
            if(empty($order->consignee_address_line1))
                return ['error'=>1, 'data'=>'', 'msg'=>'地址不能为空'];
            if(empty($order->consignee))
                return ['error'=>1, 'data'=>'', 'msg'=>'收件人姓名不能为空'];
            if(empty($order->consignee_postal_code))
                return ['error'=>1, 'data'=>'', 'msg'=>'邮编不能为空'];
            if(empty($order->consignee_phone) && empty($order->consignee_mobile))
                return ['error'=>1, 'data'=>'', 'msg'=>'联系方式不能为空'];
            if(empty($order->consignee_country_code))
                return ['error'=>1, 'data'=>'', 'msg'=>'国家信息不能为空'];

            //判断是否是E邮宝、e特快、e包裹发货
            $isEUB = false;
            if(stripos(' '.$service->shipping_method_name, 'E邮宝') != false || stripos(' '.$service->shipping_method_name, 'E特快') != false || stripos(' '.$service->shipping_method_name, 'E包裹') != false)
                $isEUB = true;
            //当是E邮宝、e特快、e包裹时，州/省不能为空，但某些平台没有州/省填，则以城市为准
            $tmpConsigneeProvince = $order->consignee_province;
            if (empty($tmpConsigneeProvince) && $isEUB)
            {
                if($order->consignee_country_code == 'FR')
                    $tmpConsigneeProvince = $order->consignee_city;
                else if(!empty($order->consignee_city))
                    $tmpConsigneeProvince = $order->consignee_city;
                else
                    return ['error'=>1, 'data'=>'', 'msg'=>'E邮宝发货时收件人 州/省或城市不能为空'];
            }

            //获取联系方式
            $phoneContact = '';
            if(empty($order->consignee_phone) && !empty($order->consignee_mobile))
                $phoneContact = $order->consignee_mobile;
            else
                $phoneContact = $order->consignee_phone;
            ///获取收件地址街道
            $consigneeStreet = ''.(empty($order->consignee_address_line1) ? '' : $order->consignee_address_line1).
                (empty($order->consignee_address_line2) ? '' : $order->consignee_address_line2).
                (empty($order->consignee_address_line3) ? '' : $order->consignee_address_line3).
                (empty($order->conosignee_company) ? '' : ';'.$order->consignee_company).
                (empty($order->consignee_county) ? '' : ';'.$order->consignee_county).
                (empty($order->consignee_district) ? '' : ';'.$order->consignee_district);
            //整理地址信息、电话信息
            $carrierAddressAndPhoneParmas = array
            (
                'consignee_phone_limit' => 30,        //电话的长度限制	这个只能看接口文档或者问对应的技术，没有长度限制请填10000
                'address' => array( 'consignee_address_line1_limit' => 10000,),
                'consignee_district' => 1,      //是否将收件人区也填入地址信息里面
                'consignee_county' => 1,        //是否将收货人镇也填入地址信息里面
                'consignee_company' => 1,       //是否将收货公司也填入地址信息里面
            );
            //返回地址信息+电话信息
            $carrierAddressAndPhoneInfo = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $carrierAddressAndPhoneParmas);
            if(!empty($carrierAddressAndPhoneInfo['address_line1']))
                $consigneeStreet = $carrierAddressAndPhoneInfo['address_line1'];
            if(!empty($carrierAddressAndPhoneInfo['phone1']))
                $phoneContact = $carrierAddressAndPhoneInfo['phone1'];

            $postdata =
                [
                    "buyerid" => $order->source_buyer_user_id,      //买家Id
                    "consignee_address" => $consigneeStreet,        //收件地址街道， 必填
                    "consignee_city" => $order->consignee_city,
                    "consignee_mobile" => $order->consignee_mobile,
                    "consignee_name" => $order->consignee,          //收件人， 必填
                    "trade_type" => 'ZYXT',
                    "consignee_postcode" => $order->consignee_postal_code,   //邮编，有邮编的国家，必填
                    "consignee_state" => $tmpConsigneeProvince,     //州、省
                    "consignee_telephone" => $phoneContact,         //收件电话， 必填
                    "country" => $order->consignee_country_code == 'UK' ? 'GB' : $order->consignee_country_code,  //收件国家二字编码，必填
                    "customer_id" => $this->customer_id,            //客户ID, 必填
                    "customer_userid" => $this->customer_userid,    //登录ID， 必填
                    "order_customerinvoicecode" => $customer_number,//原单号， 必填
                    "product_id" => $service->shipping_method_code, //运输方式ID， 必填
                    //"weight" => '',//总重，选填，如果sku上有单重可不填该项
                    //"product_imagepath" => '',//图片地址，多图片地址用分号隔开
                ];

            $weight_amount = 0;
            $orderInvoiceParam = [];

            foreach( $order->items as $k => $items)
            {
                if(empty($form_data['DeclaredValue'][$k]))
                    return ['error'=>1, 'data'=>'', 'msg'=>'申报价值不能为空'];

                if(empty($form_data['DeclarePieces'][$k]))
                    return ['error'=>1, 'data'=>'', 'msg'=>'件数不能为空'];

                //E邮宝，中文名必填
                if($isEUB && empty($form_data['CN_Name'][$k]))
                    return ['error'=>1, 'data'=>'', 'msg'=>'中文品名不能为空'];

                if(empty($form_data['EName'][$k]))
                    return ['error'=>1, 'data'=>'', 'msg'=>'英文品名不能为空'];

                $pieces = 0;
                if( isset( $form_data['DeclarePieces'][$k]) && $form_data['DeclarePieces'][$k] !== '' && is_numeric($form_data['DeclarePieces'][$k]))
                    $pieces = floatval( $form_data['DeclarePieces'][$k]);
                else
                    $pieces = $items['quantity'];

                //sku如果是e邮宝、e特快、e包囊则传中文品名
                if( $isEUB)
                    $invoice_sku = $form_data['CN_Name'][$k];
                else
                    $invoice_sku = $items['sku'];

                $orderInvoiceParam[$k] =
                    [
                        "invoice_amount" => $form_data['DeclaredValue'][$k] * $pieces,  //申报价值， 必填
                        "invoice_pcs" => $pieces,                      //件数，bitian
                        "invoice_title" => empty( $form_data['EName'][$k]) ? '' : $form_data['EName'][$k],   //品名， 必填
                        "invoice_weight" => $form_data['invoice_weight'][$k] / 1000,    //单件重(传入的单位为克，接受单位为千克)
                        "item_id" => '',
                        "item_transactionid" => '',
                        "sku" => $invoice_sku,
                        "sku_code" => $form_data['DeclareNote'][$k],   //配货信息
                    ];
                $weight_amount += $orderInvoiceParam[$k]['invoice_weight'] * $pieces;
            }
            //总重，选填，如果sku上有单重可不填该项
            if( isset( $form_data['weight']) && $form_data['weight'] !== '' && is_numeric($form_data['weight']))
                $postdata['weight'] = $form_data['weight'];
            else
                $postdata['weight'] = $weight_amount;
            $postdata['orderInvoiceParam'] = $orderInvoiceParam;

            /***********数据组织完成，准备发送**********************************************/
            $requestBody = ['param' => json_encode($postdata)];
            \Yii::info('LB_CHUANGYUCarrierAPI'.'1 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($postdata,1), "file");

            $response = Helper_Curl::post(self::$m_createOrderUrl, $requestBody);
            if( empty( $response))
                return ['error'=>1, 'data'=>'', 'msg'=>'操作失败， 返回错误'];

            \Yii::info('LB_CHUANGYUCarrierAPI'.'2 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($response,1), "file");

            //$response = urldecode( $response);
            $ret = json_decode( $response, true);
            $message = urldecode( $ret['message']);

            //分析返回结果###############################################################
            //无异常
            //返回的order_id为物流商内部标识，需要在打印物流单时用到，暂时保存于CarrierAPIHelper::orderSuccess的return_no参数中；
            if( strtolower( $ret['ack']) == 'true' && (strtolower($message) == 'success' || $message == '') && !empty($ret['tracking_number']))
            {
                $r = CarrierAPIHelper::orderSuccess( $order, $service, $ret['reference_number'], OdOrder::CARRIER_WAITING_PRINT, $ret['tracking_number'], ['delivery_orderId' => $ret['order_id']]);
                $print_param = array();
                $print_param['delivery_orderId'] = $ret['order_id'];
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_CHUANGYUCarrierAPI';
                $print_param['userToken'] = json_encode(['username' => $api_params['username'], 'password' => $api_params['password']]);
                $print_param['tracking_number'] = $ret['tracking_number'];
                $print_param['carrier_params'] = $service->carrier_params;

                try
                {
                    CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order-> order_id, $ret['reference_number'], $print_param);
                }
                catch(\Exception $ex)
                {}

                return  self::getResult(0,$r, '操作成功！客户单号为：'.$ret['reference_number'].',运单号：'.$ret['tracking_number'].'');
            }
            //返回异常， 再判断是否属于无法立即获得运单号的运输服务（如DHL,UPS之类）
            else
            {
                if( strtolower( $ret['ack']) == 'true' && stripos(' '.$message, '无法获取转单号') != false) {
                    $r = CarrierAPIHelper::orderSuccess($order, $service, $ret['reference_number'], OdOrder::CARRIER_WAITING_DELIVERY, '', ['delivery_orderId' => $ret['order_id']]);
                    return  self::getResult(0,$r, '操作成功！客户单号为：'.$ret['reference_number'].',该货代的此种运输服务无法立刻获取运单号，需要在"物流模块->物流操作状态->待交运" 中确认交运');
                }
                if(  strtolower( $ret['ack']) == 'true' && stripos(' '.$message, '未获取到跟踪号') != false) {
                    return  self::getResult(1,'', '此渠道为获取到跟踪号，请先与货代联系再提交订单；或者可以选择更换渠道进行订单提交！');
                }
                else{
                    //  return ['error'=>1, 'data'=>'', 'msg'=>'上传失败！可再次编辑后重新上传直至返货成功提示。'.'<br>错误信息：'.$message.'<br>该单已经存于后台的“草稿单”中，客户订单号为：'.$ret['reference_number'].'你也可到后台完善订单'];
                    //  return  self::getResult(1,'', '错误信息：'.empty($message)?'该渠道不支持跟踪号返回，请更换渠道上传订单':$message);
                    return  self::getResult(1,'', '错误信息：'.$message);
                }


            }
        }
        catch( CarrierException $e)
        {
            return ['error'=>1, 'data'=>'', 'msg'=>$e->msg()];
        }

    }
    
    /**
     +----------------------------------------------------------
     * 取消订单号
     +----------------------------------------------------------
     * log			name		date				note
     * @author		dwg 		2016/11/21			初始化
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
     * @author		dwg		    2016/11/21		    初始化
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
     * @author		dwg		    2016/11/21			初始化
     +----------------------------------------------------------
     **/
    public function getTrackingNO($data)
    {
    	$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2, 'LB_WANYOUTONGCarrierAPI');
    	$return_info = $base_config_obj->_getTrackingNO();
    	return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
    }
    
    /**
     +----------------------------------------------------------
     * 打单
     +----------------------------------------------------------
     * log			name		date				note
     * @author		dwg 		2016/11/21			初始化
     +----------------------------------------------------------
     **/
    public function doPrint($data)
    {
    	$base_config_obj = new HuaLeiBaseConfig($data, self::$url1, self::$url2, 'LB_WANYOUTONGCarrierAPI');
    	$return_info = $base_config_obj->_doPrint();
    	return BaseCarrierAPI::getResult($return_info['error'], $return_info['data'],$return_info['msg']);
    }
    
    /**
     +----------------------------------------------------------
     * 获取运输服务
     +----------------------------------------------------------
     * log			name		date				note
     * @author		dwg 		2016/11/21			初始化
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
     * @author		dwg 		2016/11/21			初始化
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

