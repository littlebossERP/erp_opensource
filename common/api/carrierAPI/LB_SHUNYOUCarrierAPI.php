<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\ConfigHelper;
use Jurosh\PDFMerge\PDFMerger;
use eagle\modules\util\helpers\RedisHelper;

//try{
//    include '../components/PDFMerger/PDFMerger.php';
//}catch(\Exception $e){
//}

/**
+------------------------------------------------------------------------------
 * 飞特接口业务逻辑类
+------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/shunyouCarrierAPI
 * @subpackage  Exception
 * @author		qfl
 * @version		1.0
+------------------------------------------------------------------------------
 */
class LB_SHUNYOUCarrierAPI extends BaseCarrierAPI
{

    public $soapClient = null; // SoapClient实例
    public $wsdl = null; // 物流接口

    public $Token = null;     //令牌[需要联系飞特技术提供平台标识]
    public $UAccount = null;  //物流账号
    public $Password = null;  //密码（MD5加密大写32位）

    function __construct(){
    }



    /**
    +----------------------------------------------------------
     * 申请订单号
    +----------------------------------------------------------
     **/
    public function getOrderNO($pdata)
    {
        try {

            $user=\Yii::$app->user->identity;
            if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();

            $data = $pdata['data'];  // 表单提交的数据
            $order = $pdata['order'];// object OdOrder 订单对象

            //重复发货 添加不同的标识码
            $extra_id = isset($pdata['data']['extra_id'])?$pdata['data']['extra_id']:'';
            $customer_number = $pdata['data']['customer_number'];

            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0, 0, $order,$extra_id,$customer_number);
            $shipped = $checkResult['data']['shipped']; // object OdOrderShipped

            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];// object SysCarrierAccount
            $service = $info['service'];// object SysShippingService

            $account_api_params = $account->api_params;//获取到帐号中的认证参数
            //$shippingfromaddress = $account->address['shippingfrom'];//获取到账户中的地址信息(shppingfrom是属于物流商填“收货地址”的信息)

            $normalparams = $service->carrier_params;

            $OrderUp = array();  //根节点
            $OrderUp['apiLogUsertoken'] = isset($account_api_params['apiLogUsertoken']) ? $account_api_params['apiLogUsertoken'] : '';
            $OrderUp['apiDevUserToken'] = isset($account_api_params['apiDevUserToken'])?$account_api_params['apiDevUserToken']:'';

//            /**start 客户打印备注（只有这个能显示在打印面单里）*/
//            $orderPrintRemark = '';
//            if ($order->desc == $data['printRemark']) {
//                $orderPrintRemark = $order->desc;
//            }
//            else {
//                $orderPrintRemark  =$data['printRemark'];
//            }
//            /**end 客户打印备注（只有这个能显示在打印面单里）*/


//            /**start 传给物流商的销售平台标识 SalesPlatformFlag 字段*/
//            $SalesPlatformFlag = '';
//            $order_source = $order->order_source;
//            if($order_source =='ebay'){
//                $SalesPlatformFlag = 1;
//            }
//            elseif($order_source =='amazon'){
//                $SalesPlatformFlag = 2;
//            }
//            elseif($order_source =='aliexpress'){
//                $SalesPlatformFlag = 3;
//            }
//            elseif($order_source =='wish'){
//                $SalesPlatformFlag = 4;
//            }
//            else{
//                $SalesPlatformFlag = 0;
//            }
//            /**end 传给物流商的销售平台标识 SalesPlatformFlag 字段*/





            $addressAndPhoneParams = array(
                'address' => array(
                    'consignee_address_line1_limit' => 200,
                ),
                'consignee_district' => 1,
                'consignee_county' => 1,
                'consignee_company' => 1,
                'consignee_phone_limit' => 20
            );
            $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

            $tmpAddress = '';
            if(!empty($order->consignee_company)){
                $tmpAddress = $order->consignee_company.';';
            }
            if(!empty($order->consignee_address_line1))
                $tmpAddress .= $order->consignee_address_line1;
            if(!empty($order->consignee_address_line2))
                $tmpAddress .=' '. $order->consignee_address_line2;
            if(!empty($order->consignee_address_line3))
                $tmpAddress .= ' '.$order->consignee_address_line3;
            if(!empty($order->consignee_district)){
                $tmpAddress .=';'. $order->consignee_district;
            }
            if(!empty($order->consignee_county)){
                $tmpAddress .=','. $order->consignee_county;
            }


            if($data['predictionWeight'] == '') {
                return self::getResult(1, '', '错误信息：包裹总重量必填！');
            }


            //订单基础参数
            $packageList = array(
                'customerOrderNo'             =>$order->order_source_order_id,  //客户订单号
                'customerReferenceNo'             =>$customer_number,  //客户参考号
//                'trackingNumber'             =>'',  //承运人追踪号码，通常由顺友提供，已从顺友获取批量备用追踪号的客户可以直接提供，顺友系统会校验其有效性，无效将返回错误信息。
                'shippingMethodCode'             =>$service->shipping_method_code,  //邮寄方式编码
//                'packageSalesAmount'             =>'',  //包裹总价值
//                'packageLength'             =>'',  //包裹长度（单位：cm）
//                'packageWidth'             =>'',  //包裹宽度（单位：cm）
//                'packageHeight'             =>'',  //包裹高度（单位：cm）
                'predictionWeight'             =>empty($data['predictionWeight'])?'':$data['predictionWeight'],  //包裹总重量（单位：kg）
                'recipientName'             =>$order->consignee,  //收件人姓名
                'recipientCountryCode'             =>$order->consignee_country_code,  //收件人国家二字代码
                'recipientPostCode'             =>$order->consignee_postal_code,  //收件人邮编
                'recipientState'             =>$order->consignee_province,  //收件人省州
                'recipientCity'             =>$order->consignee_city,  //收件人城市
                'recipientStreet'             =>$tmpAddress,  //收件人街道
                'recipientPhone'             =>$order->consignee_phone,  //收件人电话
                'recipientMobile'             =>$order->consignee_mobile,  //收件人手机
                'recipientEmail'             =>$order->consignee_email,  //收件人邮箱
                'insuranceFlag'             =>empty($data['insuranceFlag'])?0:$data['insuranceFlag'],  //是否投保 0：不投保 1：投保 如果选择了投保，那么投保总价值为海关申报总价值。如果当前邮寄方式不支持投保，本参数将被忽略。
//                'packageAttributes'             =>'',  //包裹属性，例如：“011”、“210”。如果包裹没有任何属性请填入000 或者不填。

            );

            //产品报关明细
            $productList = array();
            foreach($order->items as $j=>$vitem)
            {
                $productList[$j] = [
                    'productSku'                    =>$data['Sku'][$j],  //产品SKU
                    'declareEnName'                    =>$data['EName'][$j],  //申报英文名称
                    'declareCnName'                    =>$data['Name'][$j],  //申报中文名称
                    'quantity'                    =>empty($data['DeclarePieces'][$j])?$vitem->quantity:$data['DeclarePieces'][$j],  //产品数量数值必须为正整数
                    'declarePrice'                    =>$data['DeclaredValue'][$j],  //海关申报单价（币种：USD），数值必须大于0
                    'hsCode'                    =>empty($data['hsCode'][$j]) ? '' : $data['hsCode'][$j],  //海关编码
                ];
            }



            $packageList['productList'] = $productList;
            $orderdata['packageList'] = array($packageList);
            $OrderUp['data'] = $orderdata;

            $OrderUpJson = json_encode($OrderUp);

            $post_head = array();
            $post_head[] = "Content-Type: application/json;charset=UTF-8";
            $OrderUpUrl = 'http://api.sypost.com/logistics/createAndConfirmPackages';
            $response = Helper_Curl::post($OrderUpUrl,$OrderUpJson,$post_head);

            \Yii::info(print_r($response,true),"file");

            $responseArr = json_decode($response,true);

            //访问接口成功
            if($responseArr['ack'] =='success'){
                //上传订单成功
                if($responseArr['data']['resultList'][0]['processStatus'] == 'success'){
                    //立马返回跟踪号
                    if(!empty($responseArr['data']['resultList'][0]['trackingNumber'])){
                        $r = CarrierApiHelper::orderSuccess($order,$service,$responseArr['data']['resultList'][0]['customerOrderNo'],OdOrder::CARRIER_WAITING_PRINT,$responseArr['data']['resultList'][0]['trackingNumber']);
                        return  self::getResult(0,$r, "上传成功！客户订单号：".$responseArr['data']['resultList'][0]['customerOrderNo']."<br/>物流跟踪号：".$responseArr['data']['resultList'][0]['trackingNumber']);
                    }
                    //过段时间才会返回跟踪号
                    else{
                        $r = CarrierApiHelper::orderSuccess($order,$service,$responseArr['data']['resultList'][0]['customerOrderNo'],OdOrder::CARRIER_WAITING_GETCODE);
                        return  self::getResult(0,$r, "上传成功！客户订单号：".$responseArr['data']['resultList'][0]['customerOrderNo']."<br/>暂时没有物流跟踪号返回，请稍后再进行获取跟踪号");
                    }
                }
                ////上传订单失败
                else{
                    return self::getResult(1, '', "上传失败，错误代码：" .$responseArr['data']['resultList'][0]['errorList'][0]['errorCode'].' 错误原因：'.$responseArr['data']['resultList'][0]['errorList'][0]['errorMsg']);
                }
            }
            //访问接口失败
            else{
                return self::getResult(1, '', "上传失败，错误代码：" .$responseArr['errorCode'].' 错误原因：'.$responseArr['errorMsg']);
            }
        }
        catch (CarrierException $e){
            return self::getResult(1,'',"file:".$e->getFile()." line:".$e->getLine()." ".$e->msg());
        }
    }


    /**
    +----------------------------------------------------------
     * 取消订单
    +----------------------------------------------------------
     **/
    public function cancelOrderNO($data)
    {
        return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');

    }
    /**
    +----------------------------------------------------------
     * 交运
    +----------------------------------------------------------
     **/
    public function doDispatch($data)
    {
        return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');

    }

    /**
    +----------------------------------------------------------
     * 申请跟踪号
    +----------------------------------------------------------
     **/
    public function getTrackingNO($data)
    {
        try {

            $order = $data['order'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0, 1, $order);
            $shipped = $checkResult['data']['shipped'];

            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];// object SysCarrierAccount
            $service = $info['service'];// object SysShippingService

            $account_api_params = $account->api_params;//获取到帐号中的认证参数


            $RequestUp = array();  //根节点
            $RequestUp['apiLogUsertoken'] = isset($account_api_params['apiLogUsertoken']) ? $account_api_params['apiLogUsertoken'] : '';
            $RequestUp['apiDevUserToken'] = isset($account_api_params['apiDevUserToken'])?$account_api_params['apiDevUserToken']:'';

            $customerNoList['customerNoList'] = array($order->order_source_order_id);
            $RequestUp['data'] = $customerNoList;


            $RequestUpJson = json_encode($RequestUp);

            $Request_head = array();
            $Request_head[] = "Content-Type: application/json;charset=UTF-8";
            $RequestUpUrl = 'http://api.sypost.com/logistics/getPackagesTrackingNumber';
            $response = Helper_Curl::post($RequestUpUrl,$RequestUpJson,$Request_head);

            $responseArr = json_decode($response,true);

            if($responseArr['ack'] == 'success'){
                if(!empty($responseArr['data']['resultList'][0]['trackingNumber'])){
                    $shipped->tracking_number = $responseArr['data']['resultList'][0]['trackingNumber'];
                    $shipped->save();
                    $order->tracking_number = $shipped->tracking_number;
                    $order->save();
                    return self::getResult(0, '', '物流跟踪号：'.$responseArr['data']['resultList'][0]['trackingNumber']);

                }else{
                    $order->save();
                    return self::getResult(0, '', '  查询结果：暂时未产生跟踪号，请稍后再试！');
                }
            }else{
                return self::getResult(1, '', '查询失败：失败代码'.$responseArr['errorCode'].'失败原因：'.$responseArr['errorMsg']);

            }
        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }

    }

    /**
    +----------------------------------------------------------
     * 打单
    +----------------------------------------------------------
     **/
    public function doPrint($data){

        try {
            $pdf = new PDFMerger();

            $user=\Yii::$app->user->identity;

            if(empty($user))return  self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();

            $all_message = current($data);reset($data);//打印时是逐个运输方式的多张订单传入，所以获取一次account、service的信息就可以了
            $order_object=$all_message['order'];//获取订单的对象

            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(1,1,$order_object);

            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order_object);
            $account = $info['account'];
            $service = $info['service'];

            $account_api_params = $account->api_params;//获取到帐号中的认证参数

            $printRequestData = array();

            foreach ($data as $v) {
                $order_object = $v['order'];

                // $order_object->is_print_carrier = 1;
                $order_object->print_carrier_operator = $puid;
                $order_object->printtime = time();
                $order_object->save();

                if (empty($order_object->customer_number)) {
                    return self::getResult(1, '', '客户订单号:' . $order_object->order_source_order_id . ' 缺少物流商系统订单号,请检查订单是否上传');
                }
                $printRequestData['data']['customerNoList'][]= $order_object->order_source_order_id;
            }

            $printRequestData['apiLogUsertoken'] = isset($account_api_params['apiLogUsertoken']) ? $account_api_params['apiLogUsertoken'] : '';
            $printRequestData['apiDevUserToken'] = isset($account_api_params['apiDevUserToken'])?$account_api_params['apiDevUserToken']:'';
            $printRequestData['data']['packMethod'] = '0';
            $printRequestData['data']['dataFormat'] = '1';

            $printReqBody = json_encode($printRequestData);

            $printHead = array();
            $printHead[] = "Content-Type: application/json;charset=UTF-8";
            $printReqUrl = 'http://api.sypost.com/logistics/getPackagesLabel';
            $response = Helper_Curl::post($printReqUrl,$printReqBody,$printHead);

            $responseArr = json_decode($response,true);

            if($responseArr['ack'] == 'success'){
                if($responseArr['data']['resultList'][0]['processStatus'] == 'success'){
//                    $pdfurl = CarrierAPIHelper::savePDF($responseArr['data']['labelPath'],$puid,$order_object->order_source_order_id.'_'.$account->carrier_code,0);
                    return self::getResult(0,['pdfUrl'=>$responseArr['data']['labelPath']],'连接已生成,请点击并打印');//访问URL地址
                }else{
                    return self::getResult(1,'','打印失败，失败代码：'.$responseArr['data']['resultList'][0]['errorCode'].'失败原因：'.$responseArr['data']['resultList'][0]['errorMsg']);
                }

            }else{
                return self::getResult(1,'','上传失败，请联系客服查询面单接口！');
            }

        }catch (CarrierException $e) {
            return self::getResult(1,'',$e->msg());
        }


    }




    // 获取运输方式
    public static function getCarrierShippingServiceStr($account){
        try {

			// TODO carrier user account @XXX@
            $apiLogUsertoken = '@XXX@';
            $apiDevUserToken = '@XXX@';

            $datadetail = (object)array();
            $RequestData = array(
                'apiLogUsertoken' =>$apiLogUsertoken,
                'apiDevUserToken' =>$apiDevUserToken,
                'data' =>$datadetail,
            );

            $printReqBody = json_encode($RequestData,true);
            $printReqUrl = 'http://api.sypost.com/logistics/findShippingMethods';
            $printHead[] = "Content-Type: application/json;charset=UTF-8";
            $response = Helper_Curl::post($printReqUrl,$printReqBody,$printHead);

            $channelArr = json_decode($response,true);

            if(empty($channelArr['data']['resultList']) || !is_array($channelArr['data']['resultList']) || json_last_error()!=false){
                return self::getResult(1,'','获取运输方式失败');
            }

            $channelStr="";
            foreach ($channelArr['data']['resultList'] as $countryVal){
                $channelStr.=$countryVal['shippingMethodCode'].":".$countryVal['shippingMethodCnName'].";";
            }

            if(empty($channelStr)){
                return self::getResult(1, '', '');
            }else{
                return self::getResult(0, $channelStr, '');
            }

        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }
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
        try{
            $puid = $SAA_obj->uid;


            //获取redis
//            try{
//            	$get_check_token = ConfigHelper::getGlobalConfig('carrierAPI/LB_FEITECarrierAPI', "NO_CACHE");
//                \Yii::info('lb_feite_pdf_try,puid:'.$puid.'，order_id:'.$SAA_obj->order_id.' '.$get_check_token,"carrier_api");
//
//            }catch (\Exception $ex_redis){
//            	unset($get_check_token);
//            	RedisHelper::RedisDelete('global_config','carrierAPI/LB_FEITECarrierAPI');
//                \Yii::info('lb_feite_pdf_catch,puid:'.$puid.'，order_id:'.$SAA_obj->order_id.' ',"carrier_api");
//
//            }

            //第一次访问
//            if(empty($get_check_token)){
//                $authRequestData = array();
//                $authRequestData['grant_type'] = 'password';
//                //所有客户统一使用这个username和password【这是飞特分配给我们小老板的】
//                $authRequestData['username'] = 'xlberp';
//                $authRequestData['password'] = md5('184Scg_#263cv');
//                //$authRequestData['scope'] = '';
//
//                $authReqBody = json_encode($authRequestData,true);
//                $authHead[] = "Content-Type: application/json;charset=UTF-8";
//
//                $authRes = Helper_Curl::post('http://exapi.flytcloud.com/api/auth/Authorization/GetAccessToken',$authReqBody,$authHead);
//                $authResArr = json_decode($authRes,true);
//
//                $access_token = $authResArr['access_token'];
//
//                $set_check_token = array('get_token_time'=>time(),'access_token'=>$authResArr['access_token']);
//                \Yii::info('lb_feite_pdf_fristtime,puid:'.$puid.'，order_id:'.$SAA_obj->order_id.' '.json_encode($set_check_token),"carrier_api");
//                ConfigHelper::setGlobalConfig('carrierAPI/LB_FEITECarrierAPI', json_encode($set_check_token)); //放进redis
//
//            }else{
//                $get_check_token_arr = json_decode($get_check_token,true);
//
//                $now_time = time();
//
//                //设置token每过15天更新一次
//                if(($now_time-$get_check_token_arr['get_token_time']) >1296000){
//                    $authRequestData = array();
//                    $authRequestData['grant_type'] = 'password';
//                    //所有客户统一使用这个username和password【这是飞特分配给我们小老板的】
//                    $authRequestData['username'] = 'xlberp';
//                    $authRequestData['password'] = md5('184Scg_#263cv');
//                    //$authRequestData['scope'] = '';
//
//                    $authReqBody = json_encode($authRequestData,true);
//                    $authHead[] = "Content-Type: application/json;charset=UTF-8";
//
//                    $authRes = Helper_Curl::post('http://exapi.flytcloud.com/api/auth/Authorization/GetAccessToken',$authReqBody,$authHead);
//                    $authResArr = json_decode($authRes,true);
//
//                    $access_token = $authResArr['access_token'];
//
//                    $set_check_token = array('get_token_time'=>time(),'access_token'=>$authResArr['access_token']);
//                    \Yii::info('lb_feite_pdf_over15day,puid:'.$puid.'，order_id:'.$SAA_obj->order_id.' '.json_encode($set_check_token),"carrier_api");
//                    ConfigHelper::setGlobalConfig('carrierAPI/LB_FEITECarrierAPI', json_encode($set_check_token));//放进redis
//
//                }else{
//                    $access_token = $get_check_token_arr['access_token'];
//                    \Yii::info('lb_feite_pdf_within15day,puid:'.$puid.'，order_id:'.$SAA_obj->order_id.' '.json_encode($get_check_token_arr),"carrier_api");
//                }
//            }



            //面单授权token 暂时写死 30天过期
            $access_token = 'MjAxNzEwMjUyMjMwNTY0MzFGOTI2Q0VCMTYzMjdCMUU0QUMxQTUxREZFMTBDNjUwMQ==';
//            $now_time = time();

//            if(!empty($authResArr['error_description'])){
//                return self::getResult(1,'','面单授权失败，失败原因：'.$authResArr['error_description']);
//            }

            if (empty($print_param['order_number'])) {
                return self::getResult(1, '','缺少物流商系统订单号,请检查订单是否上传');
            }
            $printRequestData['OrderIdlst'][]= $print_param['order_number'];
            $printRequestData['Format'] = 0;    //面单格式（0：标签纸10x10，1：A4纸），api文档目前统一为0
            $printRequestData['IsPrintSkuInfo'] = true;
            $printRequestData['IsShowSelfCode'] = true;

            $printReqBody = json_encode($printRequestData,true);
            $printReqUrl = 'http://exapi.flytcloud.com/api/label/LabelProvider/GetLabelBatchExt';
            $printHead[] = "Content-Type: application/json;charset=UTF-8";
//            $printHead[] = "token: ".$access_token;
            $printHead[] = "token: ".$access_token;


            $printRes = Helper_Curl::post($printReqUrl,$printReqBody,$printHead);
            $printResArr = json_decode($printRes,true);

            if($printResArr['Status'] == 1){
                $pdfPath = CarrierAPIHelper::savePDF2(base64_decode($printResArr['Data']['Label']),$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
                return $pdfPath;
            }
            else{
                return ['error'=>1, 'msg'=>'打印失败！错误信息：'.$printResArr['ErrMsg'], 'filePath'=>''];
            }

        }catch (CarrierException $e){
            return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
        }
    }




    /*
  * 用来确定打印完成后 订单的下一步状态
  */
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }



}



?>