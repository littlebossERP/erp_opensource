<?php
/**
 * Created by PhpStorm.
 * User: dwg
 * Date: 2015-8-20 0020
 * Time: 17:52:20
 */
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use common\helpers\yuntu_ShippingInfo;
use Jurosh\PDFMerge\PDFMerger;

//try{
//include '../components/PDFMerger/PDFMerger.php';
//}catch(\Exception $e){
//}
class LB_YUNTUCarrierAPI extends BaseCarrierAPI
{
    public $wsdl = null; // 物流接口

    public $version = null;
    public $Authorization = null;

    static $connecttimeout=60;
    static $timeout=500;
    static $last_post_info=null;
    static $last_error =null;

    function __construct()
    {

//        $customerID = '';
//        $apiSecret = '';
//        $password = $customerID.'&'.$apiSecret;
//        $token = base64_encode($password);
//        $authorization = "Authorization: Basic ".$token;

//        $post_head = array();
//        $post_head[] = "Content-Type: application/json;charset=UTF-8";
//        $post_head[] = $authorization;
//        $post_head[] = "version: 1.0";
//        $response = Helper_Curl::get('http://api.yunexpress.com/LMS.API/api/lms/Get',null,$post_head);
//        print_r($response);   //正式环境运输方式

    }



    /**
    +----------------------------------------------------------
     * 申请订单号
    +----------------------------------------------------------
     **/
    public function getOrderNO($data)
    {
        try
        {
            $user=\Yii::$app->user->identity;
            if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();

            $order = $data['order'];//订单对象
            $data = $data['data'];   //表单提交的数据

            //重复发货 添加不同的标识码
            $extra_id = isset($data['extra_id'])?$data['extra_id']:'';
            $customer_number = $data['customer_number'];

            if(isset($data['data']['extra_id'])){
                if($extra_id == ''){
                    return self::getResult(1, '', '强制发货标识码，不能为空');
                }
            }

            //对当前条件的验证，校验是否登录，校验是否已上传过
            $checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);

            //获取到所需要使用的数据（运输服务配置信息，物流商配置信息）
            $info = CarrierAPIHelper::getAllInfo($order);
            $service = $info['service'];
            $account = $info['account'];

            //是否启用云途运单号设置（普通参数）
            $tmp_is_use_mailno = empty($service['carrier_params']['is_use_mailno']) ? 'N' : $service['carrier_params']['is_use_mailno'];

            if(empty($info['senderAddressInfo']['shippingfrom'])){
            	return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
            }
            
            $account_api_params = $account->api_params;  //获取到帐号中的认证参数
            $shippingfromaddress = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息
            $service_carrier_params = $service->carrier_params;

            $addressAndPhoneParams = array(
                'address' => array(
                    'consignee_address_line1_limit' => 100,
                    'consignee_address_line2_limit' => 100,
                    'consignee_address_line3_limit' => 100,
                ),
                'consignee_district' => 1,
                'consignee_county' => 1,
                'consignee_company' => 1,
                'consignee_phone_limit' => 16
            );
            $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

            //法国没有省份，直接用城市来替换；若城市长度大于20，则直接传‘FR’到省。
            $tmpConsigneeProvince = $order->consignee_province;
            if(empty($tmpConsigneeProvince)){
                $tmpConsigneeProvince = $order->consignee_city;
                if(strlen($tmpConsigneeProvince)>20){
                    $tmpConsigneeProvince = $order->consignee_country_code;
                }
            }


            $post_order = array();
            $post_order['OrderNumber'] = $customer_number;         // string required  客户订单号,不能重复
            $post_order['ShippingMethodCode'] = $service->shipping_method_code;  // string required  发货的方式,调用运输方式查询方法
//            $post_order['TrackingNumber'] = $order->order_source_order_id;      // 包裹跟踪号，可以不填写
            $post_order['Length'] = 1;              // 预估包裹单边长，单位cm，非必填，默认1
            $post_order['Width'] =  1;               // 预估包裹单边宽，单位cm，非必填，默认1
            $post_order['Height'] = 1;              // 预估包裹单边高，单位cm，非必填，默认1
            $post_order['PackageNumber'] = empty($data['PackageNumber'])?'1':$data['PackageNumber'];       // string required  运单包裹的件数，必须大于0的整数
            $post_order['ApplicationType'] = $data['ApplicationType'];     // 申报类型,用于打印CN22，1-Gift,2-Sameple,3-Documents,4-Others,默认4-Other
//            $post_order['IsReturn'] = $service_carrier_params['IsReturn'];            // 是否退回,包裹无人签收时是否退回，1-退回，0-不退回，默认0
//            $post_order['EnableTariffPrepay'] = $service_carrier_params['EnableTariffPrepay'];  // 关税预付服务费，1-参加关税预付，0-不参加关税预付，默认0 (渠道需开通关税预付服务)

            $post_order['IsReturn'] = intval($service_carrier_params['IsReturn']);            // 是否退回,包裹无人签收时是否退回，1-退回，0-不退回，默认0
            if(empty($post_order['IsReturn']))
            {
                $post_order['IsReturn'] = 0;
            }
            //注意“EnableTariffPrepay”字段填的值为“0”会报错[有双引号]，整数0才会成功！
            $post_order['EnableTariffPrepay'] = intval($service_carrier_params['EnableTariffPrepay']);  // 关税预付服务费，1-参加关税预付，0-不参加关税预付，默认0 (渠道需开通关税预付服务)
            if(empty($post_order['EnableTariffPrepay']))
            {
                $post_order['EnableTariffPrepay'] = 0;
            }
            //注意“InsuranceType”字段填的值为“0”会报错[有双引号]，整数0才会成功！
            $post_order['InsuranceType'] = $service_carrier_params['InsuranceType'];       // 包裹投保类型，0-不参保，1-按件，2-按比例，默认0，表示不参加运输保险，具体参考包裹运输
            if($post_order['InsuranceType']=="")
            {
                $post_order['InsuranceType'] = 0;
            }

            $post_order['InsureAmount'] = '';        // 保险的最高额度，单位RMB
            $post_order['SensitiveTypeID'] = '';     // 包裹中特殊货品类型，可调用货品类型查询服务查询，可以不填写，表示普通货品
            $post_order['SourceCode'] = 'XLB';       // 订单来源代码  "XLB"代表小老板

            if( $order->consignee_city=='' ){
                $consignee_city= $tmpConsigneeProvince;
            }else{
                $consignee_city= $order->consignee_city;
            }
            // object required  收件人信息
            $ShippingInfo = array(
                'ShippingTaxId'          =>'',  // 收件人企业税号，欧盟可以填EORI，巴西可以填CPF等，非必填
                'CountryCode'            =>$order->consignee_country_code == 'UK'?'GB':$order->consignee_country_code,  // string required  收件人所在国家，填写国际通用标准2位简码，可通过国家查询服务查询
                'ShippingFirstName'      =>$order->consignee,  // string required  收件人姓 备注（荷兰邮政小包挂号长度限制收件人姓和收件人名 总长度60）
//                'ShippingLastName'       =>$order->consignee,  // 收件人名字
                'ShippingCompany'        =>$order->consignee_company,  // 收件人公司名称
//                'ShippingAddress'        =>$order->consignee_address_line1,  // string required  收件人详情地址 备注(发中美专线长度限制35个字符、荷兰邮政小包挂号限制200，不能包含中文)
//                'ShippingAddress1'       =>$order->consignee_address_line2,  //   收件人详情地址1 备注(发中美专线长度限制35个字符)
//                'ShippingAddress2'       =>$order->consignee_address_line3,  //   收件人详情地址2 备注(发中美专线长度限制35个字符)
                'ShippingAddress'        =>$addressAndPhone['address_line1'],  // string required  收件人详情地址 备注(发中美专线长度限制35个字符、荷兰邮政小包挂号限制200，不能包含中文)
                'ShippingAddress1'       =>$addressAndPhone['address_line2'],  //   收件人详情地址1 备注(发中美专线长度限制35个字符)
                'ShippingAddress2'       =>$addressAndPhone['address_line3'],  //   收件人详情地址2 备注(发中美专线长度限制35个字符)
                'ShippingCity'           =>$consignee_city,  // string required  收件人所在城市 备注（荷兰邮政小包限制30个字符）

                'ShippingState'          =>$tmpConsigneeProvince,  // 收货人省/州 备注（荷兰邮政小包挂号非必填，但限制30个字符）

                'ShippingZip'            =>$order->consignee_postal_code,  // 收货人邮编 备注（荷兰邮政小包挂号非必填，但限制30个字符）
//                'ShippingPhone'          =>$order->consignee_phone,  // 收货人电话 备注（荷兰邮政小包挂号非必填，但限制20个字符，只能是数字）
                'ShippingPhone'          =>$addressAndPhone['phone1'],  // 收货人电话 备注（荷兰邮政小包挂号非必填，但限制20个字符，只能是数字）

            );


            $post_order['ShippingInfo'] = $ShippingInfo;

            // object not required  发件人信息
            $SenderInfo = array(
                'CountryCode'       => $shippingfromaddress['country'],     // 发件人所在国家，填写国际通用标准2位简码，可通过国家查询服务查询
                'SenderFirstName'      => $shippingfromaddress['contact'],  // 发件人姓
                'SenderLastName'      => $shippingfromaddress['contact'],   // 发件人名字
                'SenderCompany'      => $shippingfromaddress['company'],    // 发件人公司名称
                'SenderAddress'      => $shippingfromaddress['street'] ,    // 发件人详情地址
                'SenderCity'      => $shippingfromaddress['city'] ,       // 发件人所在城市
                'SenderState'      => $shippingfromaddress['province'],      // 发货人省/州
                'SenderZip'      => $shippingfromaddress['postcode'],        // 发货人邮编
                'SenderPhone'      => $shippingfromaddress['phone'],      // 发货人电话
            );
            $post_order['SenderInfo'] = $SenderInfo;


            // object/array required  海关申报信息
            $totalWeight = 0;
            $totalPrice = 0;

            $ApplicationInfos[0] = array();
            foreach($order->items as $j=>$vitem)
            {
                $ApplicationInfos[$j] = [
                    'ApplicationName'    => $data['EName'][$j] ,  // string required  包裹中货品(英文) 申报名称,必填一项,打印CN22用, 备注（荷兰邮政小包挂号限制50个字符，且只能为英文）
                    'HSCode'             => empty($data['Hscode'][$j])?'':$data['Hscode'][$j] ,  // 包裹中货品 申报编码,打印CN22用(欧洲专线必填；荷兰邮政小包挂号不必填，限制10个字符，只能为数字)
                    'Qty'                => empty($data['DeclarePieces'][$j])?$vitem->quantity:$data['DeclarePieces'][$j] ,  // string required  包裹中货品 申报数量,必填一项,打印CN22用
                    'UnitPrice'          => $data['DeclaredValue'][$j] ,  // string required  包裹中货品 申报价格,单位USD,必填一项,打印CN22用
                    'UnitWeight'         => round(($data['weight'][$j])/1000,2) ,  // string required  包裹中货品 申报重量，单位kg,打印CN22用
                    'PickingName'        => $data['Name'][$j] ,  // 包裹的申报中文名称
                    'Remark'             => $data['Remark'][$j] ,  // 用于打印配货单，（欧洲专线必填）
//                    'ProductUrl'         => $data['ProductUrl'][$j] ,  // 产品链接地址 (中欧专线申报产品链接信息)
                    'SKU'                => $vitem->sku ,  // 用于填写商品SKU
                ];
                $totalWeight += $data['weight'][$j] * $ApplicationInfos[$j]['Qty'];
            }
            $post_order['ApplicationInfos'] = $ApplicationInfos;
            $post_order['Weight'] = round($totalWeight / 1000 , 2); // float 订单重量，单位 KG，最多3位小数

            $arr[0] =$post_order;
            $json_req=json_encode($arr);


            $customerID = $account_api_params['CustomerID'];
            $apiSecret =  $account_api_params['ApiSecret'];
            $password = $customerID.'&'.$apiSecret;
            $token = base64_encode($password);
            $authorization = "Authorization: Basic ".$token;

            $post_head = array();
            $post_head[] = "Content-Type: application/json;charset=UTF-8";
            $post_head[] = $authorization;
            $post_head[] = "version: 1.0";
            
            ignore_user_abort(true);
//            http://t.tinydx.com:901/LMS.API/api/WayBill/BatchAdd   测试环境的订单申请地址
            \Yii::info('YUNTU request,puid:'.$puid.',request,orderId:'.$order->order_id.' '.$json_req,"carrier_api");
            

            $response = Helper_Curl::post('http://api.yunexpress.com/LMS.API/api/WayBill/BatchAdd',$json_req,$post_head);

//            \Yii::info(print_r($response,true),"file");   //先记下结果，方便查看和防止失去结果
//             \Yii::info('YUNTU,puid:'.$puid.',result,order_id:'.$order->order_id.' '.$response,"file");//先记下结果，方便查看和防止失去结果
            \Yii::info('YUNTU result,puid:'.$puid.',result,orderId:'.$order->order_id.' '.$response,"carrier_api");
            $responseData = json_decode($response, true);

            if(isset($responseData['Item']))
            {
                foreach ($responseData['Item'] as $item) {
                    if ($item['Status'] != 1) {
                        return self::getResult(1, '', '提交结果:' . json_encode($responseData['ResultDesc']) . ' ' . '失败原因:' . $item['Feedback']);
                    } else {

                        $print_param = array();
                        $print_param['carrier_code'] = $service->carrier_code;
                        $print_param['api_class'] = 'LB_YUNTUCarrierAPI';
                        $print_param['post_head'] = $post_head;
                        $print_param['order_number'] = $order->order_source_order_id;
                        $print_param['carrier_params'] = $service->carrier_params;

                        try{
                            CarrierAPIHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
                        }catch (\Exception $ex){
                        }

                        $agent_mailno = $tmp_is_use_mailno=='Y' ? $item['WayBillNumber'] : $item['TrackingNumber'];

                        if ($item['TrackStatus'] == 1) {
                            //（已产生或等待跟踪号时）上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态，跟踪号(选填),returnNo(选填)
                            $r = CarrierApiHelper::orderSuccess($order, $service, $item['WayBillNumber'], OdOrder::CARRIER_WAITING_PRINT, $agent_mailno);
                            return self::getResult(0, $r, "提交结果:" . $item['Feedback'] ."<br>物流跟踪号：" . $agent_mailno);
                        }
                        if ($item['TrackStatus'] == 2) {
                            //（已产生或等待跟踪号时）上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态，跟踪号(选填),returnNo(选填)
                            if($tmp_is_use_mailno=='Y'){
                                $r = CarrierApiHelper::orderSuccess($order, $service, $item['WayBillNumber'], OdOrder::CARRIER_WAITING_PRINT,$agent_mailno);
                                return self::getResult(0, $r, "暂时没跟踪号，等待物流商出仓发货后再获取跟踪号。<br> YT运单号：". $agent_mailno);
                            }else{
                                $r = CarrierApiHelper::orderSuccess($order, $service, $item['WayBillNumber'], OdOrder::CARRIER_WAITING_GETCODE);
                                return self::getResult(0, $r, "暂时没跟踪号，等待物流商出仓发货后再获取跟踪号。");
                            }
                        }
                        if($item['TrackStatus'] == 3) {
                            //（没有跟踪号时）上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态，跟踪号(选填),returnNo(选填)
                            if($tmp_is_use_mailno=='Y'){
                                $r = CarrierApiHelper::orderSuccess($order, $service, $item['WayBillNumber'], OdOrder::CARRIER_WAITING_PRINT,$agent_mailno);
                                return self::getResult(0, $r, "此运输方式不需要跟踪号。YT运单号：".$agent_mailno);
                            }else{
                                $r = CarrierApiHelper::orderSuccess($order, $service, $item['WayBillNumber'], OdOrder::CARRIER_WAITING_PRINT);
                                return self::getResult(0, $r, "此运输方式不带跟踪号！");
                            }

                        }

                    }
                }
            }
            else   //“请求无效”原因的处理
            {
                //查看运单包裹件数是否没填写（必填项）
                if($post_order['PackageNumber']=="")
                {
//                    return self::getResult(1, '', '提交结果:' . $responseData['Message'] .' 失败原因:'.$responseData['ModelState']['wayBillModels[0].PackageNumber'][0]);
                    return self::getResult(1, '', '提交结果:' . json_encode($responseData,true) .' 失败原因:'.'必填项目未填写!');
                }

                else
                {
//                    return self::getResult(1, '', '提交结果:' . $responseData['ResultDesc'] . ' ' . '失败原因:' . $responseData['Item']['Feedback']);
                    return self::getResult(1, '', ' 失败原因:' . json_encode($responseData,true));
                }
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
        try
        {
            $order = $data['order'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0,1,$order);
            $shipped = $checkResult['data']['shipped'];


            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];// object SysCarrierAccount
            $service = $info['service'];// object SysShippingService

            $tmp_is_use_mailno = empty($service['carrier_params']['is_use_mailno']) ? 'N' : $service['carrier_params']['is_use_mailno'];


            //设置好头部
            $get_head = array();
            $account_api_params = $account->api_params;//获取到帐号中的认证参数
//             $get_head[] = "Content-Type: application/json;charset=UTF-8";
//             $get_head[] = isset($account_api_params['Authorization'])?$account_api_params['Authorization']:'';
//             $get_head[] = isset($account_api_params['version'])?$account_api_params['version']:'';


            $customerID = $account_api_params['CustomerID'];
            $apiSecret =  $account_api_params['ApiSecret'];
            $password = $customerID.'&'.$apiSecret;
            $token = base64_encode($password);
            $authorization = "Authorization: Basic ".$token;

            $get_head[] = "Content-Type: application/json;charset=UTF-8";
            $get_head[] = $authorization;
            $get_head[] = "version: 1.0";


            $order = $data['order'];
            $params = '?orderId='.$order->customer_number;

            //http://t.tinydx.com:901/LMS.API/api/WayBill/GetTrackNumber  测试环境获取跟踪号地址
            $response = Helper_Curl::get('http://api.yunexpress.com/LMS.API/api/WayBill/GetTrackNumber'.$params,null,$get_head);

            \Yii::info(print_r($response,true),"carrier_api");   //先记下结果，方便查看和防止失去结果
            $responseData = json_decode($response , true);

            if (isset($responseData['Item'])) {

                foreach($responseData['Item'] as $item)
                {
                    if($tmp_is_use_mailno == 'Y'){
                        $shipped->tracking_number = $item['WayBillNumber'];
                        $shipped->save();
                        $order->tracking_number = $shipped->tracking_number;
                        $order->save();
                        return self::getResult(0, '', '提交结果:' . $responseData['ResultDesc'] . ' ' . '跟踪号[系统运单号]:' . $item['WayBillNumber']);
                    }
                    else
                    {
                        if(empty($item['TrackingNumber'])){
                            return self::getResult(1,'', '提交结果:'.$item['msg']);
                        }
                        else{
                            $shipped->tracking_number = $item['TrackingNumber'];
                            $shipped->save();
                            $order->tracking_number = $shipped->tracking_number;
                            $order->save();
                            return self::getResult(0, '', '提交结果:' . $responseData['ResultDesc'] . ' ' . '物流跟踪号:' . $item['TrackingNumber']);
                        }
                    }

                }
            }
            else{
                return self::getResult(1,'', '失败原因:'.$responseData['ResultDesc']);
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
    public function doPrint($data)
    {

        try {
            $pdf = new PDFMerger();
            $order = current($data);reset($data);
            $getAccountInfoOrder = $order['order'];
//             print_r($getAccountInfoOrder);
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(1,1,$getAccountInfoOrder);
            $shipped = $checkResult['data']['shipped'];

            $puid = $checkResult['data']['puid'];  //返回$puid = 1

            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($getAccountInfoOrder);
            $account = $info['account'];// object SysCarrierAccount
            $service = $info['service'];// object SysShippingService
            $account_api_params = $account->api_params;//获取到帐号中的认证参数
            $normalparams = $service->carrier_params;

            //设置好头部

            $customerID = $account_api_params['CustomerID'];
            $apiSecret =  $account_api_params['ApiSecret'];
            $password = $customerID.'&'.$apiSecret;
            $token = base64_encode($password);
            $authorization = "Authorization: Basic ".$token;

            $post_head = array();
            $post_head[] = "Content-Type: application/json;charset=UTF-8";
            $post_head[] = $authorization;
            $post_head[] = "version: 1.0";

            $OrderNumbers = array();// 客户订单号作提交参数

            foreach ($data as $v) {
                $oneOrder = $v['order'];
//                 $oneOrder->is_print_carrier = 1;
                $oneOrder->print_carrier_operator = $puid;
                $oneOrder->printtime = time();
                $oneOrder->save();

                if (empty($oneOrder->customer_number)) {
                    return self::getResult(1, '', 'order:' . $oneOrder->order_source_order_id . ' 缺少客户订单号,请检查订单是否上传');
                }
                $OrderNumbers[]= $oneOrder->customer_number;
            }

                $json_req=json_encode($OrderNumbers);
                //http://t.tinydx.com:860/Api/PrintUrl    标签打印的测试环境地址
                $response = Helper_Curl::post('http://api.yunexpress.com/LMS.API.Lable/Api/PrintUrl',$json_req,$post_head);
                $responseData = json_decode($response , true);
                
                \Yii::info('YUNTU doPrint,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.' '.$response,"carrier_api");
                
                if(empty($responseData['Item'])){
                    return self::getResult(1,'', $responseData['ResultDesc']);
                }
                else {
                    if(empty($responseData['Item'][0]['Url'])){
                        $error_msg = '';
                        foreach($responseData['Item'][0]['LabelPrintInfos'] as $printsinfo){
                                $error_msg .= ($printsinfo['ErrorCode']==100)?'':'客户订单号:'.$printsinfo['OrderNumber'].'出错原因：'.$printsinfo['ErrorBody'].';';
                        }
                        return self::getResult(1,'', $error_msg);
                    }
                    else{
                        //直接返回接口的pdf地址
//                         return self::getResult(0,['pdfUrl'=>$responseData['Item'][0]['Url']],'连接已生成,请点击并打印');
                        
                        $response = self::get($responseData['Item'][0]['Url'],null,null,false,null,null,true);
                        
                        if($response === false)
                        	return self::getResult(1,'','云途返回打印超时！');
                        
                        if(strlen($response)<1000){
                        	return self::getResult(1, '', $response);
                        }
                        
                        $pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$oneOrder->customer_number, 0);
                        
                        return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
                    }
                }
        }catch (CarrierException $e) {
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

            $OrderNumbers[]= $print_param['order_number'];// 注意这里文档写的参数是 refrence_no 要改成reference_no 接口才调用成功
            $json_req=json_encode($OrderNumbers);

            //http://t.tinydx.com:860/Api/PrintUrl    标签打印的测试环境地址
            $response = Helper_Curl::post('http://api.yunexpress.com/LMS.API.Lable/Api/PrintUrl',$json_req,$print_param['post_head']);
            $responseData = json_decode($response , true);


            if($responseData['Item'][0]['LabelPrintInfos'][0]['ErrorCode']==100) {
                $pdf_respond = self::get($responseData['Item'][0]['Url'],null,null,false,null,null,true);
                $pdfPath = CarrierAPIHelper::savePDF2($pdf_respond,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
                return $pdfPath;
            }
            else{
                return ['error'=>1, 'msg'=>'打印失败！错误信息：'.$responseData['Item'][0]['LabelPrintInfos'][0]['ErrorBody'], 'filePath'=>''];
            }

        }catch (CarrierException $e){
            return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
        }
    }




    //修改过的Curl的GET方法（处理了$requestFollowlocation这个参数）
    public static function get($url,$requestBody=null,$requestHeader=null,$justInit=false,$responseSaveToFileName=null,$http_version=null,$requestFollowlocation=false){
        $connection = curl_init();

        curl_setopt($connection, CURLOPT_URL,$url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        if (!is_null($requestHeader)){
            curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
        }
        if (!is_null($http_version)){
            curl_setopt($connection, CURLOPT_HTTP_VERSION, $http_version);
        }
        if (!is_null($responseSaveToFileName)){
            $fp=fopen($responseSaveToFileName,'w');
            curl_setopt($connection, CURLOPT_FILE, $fp);
        }else {
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        }
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT,self::$connecttimeout);
        curl_setopt($connection, CURLOPT_TIMEOUT,self::$timeout);
        if ($justInit){
            return $connection;
        }
        if ($requestFollowlocation){
            //启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
            curl_setopt($connection, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($connection, CURLOPT_MAXREDIRS, 3);
        }

        $response = curl_exec($connection);
        self::$last_post_info=curl_getinfo($connection);
        $error=curl_error($connection);
        curl_close($connection);
        if (!is_null($responseSaveToFileName)){
            fclose($fp);
        }
        if ($error){
            throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url.'DATA:'.$requestBody);
        }
        return $response;
    }



    /**
     * 查询物流轨迹
     * @param   $data[0]]   跟踪号/运单号/客户单号
     * @return  array
     */
    public function SyncStatus($data){

        //用其中一个客户的账号密码
		// TODO carrier user account
        $account_api_params['CustomerID']='';
        $account_api_params['ApiSecret'] = '';

        $customerID = $account_api_params['CustomerID'];
        $apiSecret =  $account_api_params['ApiSecret'];
        $password = $customerID.'&'.$apiSecret;
        $token = base64_encode($password);
        $authorization = "Authorization: Basic ".$token;

        $get_head = array();
        $get_head[] = "Content-Type: application/json;charset=UTF-8";
        $get_head[] = $authorization;
        $get_head[] = "version: 1.0";
        // $params = '?trackingNumber='.'YT1601814154800014';
        $params = '?trackingNumber='.$data[0];

        $response = Helper_Curl::get('http://api.yunexpress.com/LMS.API/api/WayBill/GetTrackingNumber'.$params,null,$get_head);
        $responseArr = json_decode($response,true);

        $result = array();
        if(!empty($responseArr['Item']))
        {
            $result['error'] = "0";
            $result['referenceNumber'] = $responseArr['Item']['WayBillNumber'];
            $result['trackingNumber'] = $responseArr['Item']['TrackingNumber'];
            $result['destinationCountryCode'] = $responseArr['Item']['CountryCode'];

            $detailsArr = $responseArr['Item']['OrderTrackingDetails'];
            $i = 0;
            foreach ($detailsArr as $details) {
                $result['trackContent'][$i]['createDate'] = '';
                $result['trackContent'][$i]['createPerson'] = '';
                $result['trackContent'][$i]['occurAddress'] = $details['ProcessLocation'];
                $result['trackContent'][$i]['occurDate'] = $details['ProcessDate'];
                $result['trackContent'][$i]['trackCode'] = '';
                $result['trackContent'][$i]['trackContent'] = $details['ProcessContent'];
                $i++;
            }
        }
        else
        {
            $result['error'] = "1";
            $result['trackContent'] = "未找到物流信息,请检查输入 跟踪号/运单号/客户单号 是否有误";
            $result['referenceNumber'] = '';
        }
        return $result;

    }



    //获取运输方式
    public function getCarrierShippingServiceStr($account){
        try{

			// TODO carrier user account @XXX@
            $customerID = '@XXX@';
            $apiSecret = '@XXX@';
            $password = $customerID.'&'.$apiSecret;
            $token = base64_encode($password);
            $authorization = "Authorization: Basic ".$token;

            $post_head = array();
            $post_head[] = "Content-Type: application/json;charset=UTF-8";
            $post_head[] = $authorization;
            $post_head[] = "version: 1.0";
            $response = Helper_Curl::get('http://api.yunexpress.com/LMS.API/api/lms/Get',null,$post_head);
            $channelArr=json_decode($response,true);

            if(empty($channelArr) || !is_array($channelArr) || json_last_error()!=false){
                return self::getResult(1,'','获取运输方式失败');
            }
            
            $channelStr="";
            foreach ($channelArr['Item'] as $countryVal){
                $channelStr.=$countryVal['Code'].":".$countryVal['FullName'].";";
            }

            if(empty($channelStr)){
                return self::getResult(1, '', '');
            }else{
                return self::getResult(0, $channelStr, '');
            }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }



    /*
     * 用来确定打印完成后 订单的下一步状态
     */
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }



//    /**
//    /**
//     * xML转为数组
//     */
//    public static function xml_to_array($xml,$main_heading = '') {
//        $deXml = simplexml_load_string($xml);
//        $deJson = json_encode($deXml);
//        $xml_array = json_decode($deJson,TRUE);
//        if (! empty($main_heading)) {
//            $returned = $xml_array[$main_heading];
//            return $returned;
//        } else {
//            return $xml_array;
//        }
//    }
}
