<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-3-23
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use common\helpers\Helper_Array;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use common\helpers\SubmitGate;
use eagle\modules\util\helpers\TimeUtil;
use Jurosh\PDFMerge\PDFMerger;
use yii\base\Object;

/**
 +------------------------------------------------------------------------------
 * 4px接口业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/4pxCarrierAPI
 * @author		dzt 
 * @version		2.0
 +------------------------------------------------------------------------------
 */
class LB_4PXNewCarrierAPI extends BaseCarrierAPI
{
    
    public static $appKey = null;
    public static $appSecret = null;
    public static $debug = FALSE;
    public static $apiHost = null;
    public static $publicParams = [];
    
    public function __construct(){
        // dzt20191126 目前appkey不用注册，是给的
//         AppKey(直发沙箱环境)
//         6a7b39cf-44a6-4461-8402-baf665d9eca7
//         AppSecret
//         80c96469-4dbb-4a27-9062-f8a6d524fcd0
        // 正式环境
        if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' && empty(self::$debug)){
            // TODO carrier dev account @XXX@
            self::$appKey = "@XXX@";
            self::$appSecret = "@XXX@";
            self::$apiHost = "http://open.4px.com";
            
        }else{// 沙箱环境	
            // TODO carrier dev account @XXX@
            self::$appKey = "@XXX@";
            self::$appSecret = "@XXX@";
            
            self::$apiHost = "http://open.sandbox.4px.com";
            
        }
        
        $nowTime = TimeUtil::getCurrentTimestampMS();
        
        self::$publicParams = [
                'app_key' => self::$appKey,
                'format' => "json",
                'method' => "",
                'timestamp' => $nowTime,
                'v' => "1.0",
        ];
    }
    
    
    
    
    /**
     * 申请订单号
     **/
    public function getOrderNO($data){
        
        try{
            //订单对象
            $order = $data['order'];
            	
            //重复发货 添加不同的标识码
            $extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
            $customer_number = $data['data']['customer_number'];
        
            if(isset($data['data']['extra_id'])){
                if($extra_id == ''){
                    return self::getResult(1, '', '强制发货标识码，不能为空');
                }
            }
            	
            //表单提交的数据
            $e = $data['data'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
            $puid = $checkResult['data']['puid'];
            	
            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $service = $info['service'];
            $service_params = $service->carrier_params;
            $tmp_a = $info['account'];
            $api_params = $tmp_a->api_params;
            	
            $senderAddressInfo = $info['senderAddressInfo'];
            if(empty($senderAddressInfo['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
        
			if(empty($e['total_weight_4px'])){
			    return self::getResult(1,'','请输入订单=>申报重量g ');
			}
			
			$shippingFrom = $senderAddressInfo['shippingfrom'];
			
            $addressAndPhoneParams = array(
                    'address' => array(
                            'consignee_address_line1_limit' => 100,
                            'consignee_address_line2_limit' => 100,
                    ),
                    'consignee_district' => 1,
                    'consignee_county' => 1,
                    'consignee_company' => 1,
                    'consignee_phone_limit' => 20
            );
             
            $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
        
            
            $vat_no = "";
            // dzt20200408 巴西的订单，递四方要求有税号才能上传
            if(!empty($e['vat_no'])){//vat_no	String	32	否	SDF2324	VAT税号(数字或字母)
			    $vat_no = $e['vat_no'];
			}elseif($order->consignee_country_code == "BR"){
			    return self::getResult(1,'','请输入税号');
			}
            
//             parcel_list	List		是		包裹列表
//             weight	Number	32	是		预报重量（g）
//             length	Double	32	否		包裹长（cm）
//             width	Double	32	否		包裹宽（cm）
//             height	Double	32	否		包裹高（cm）
//             parcel_value	Double	32	是	23.2301	包裹申报价值（最多4位小数）
//             currency	String	10	是	USD	币别（按照ISO标准三字码，目前只支持USD）
//             include_battery	String	3	是	Y	是否含电池（Y/N）
//             battery_type	String	32	否	966	带电类型(966:内置电池PI966；967:配套电池PI967）
//             product_list	List		是		货物列表（投保、查验、货物丢失作为参考依据）
//                 sku_code	String	64	否	iPhone5	SKU（客户自定义SKUcode）（数字或字母或空格）
//                 standard_product_barcode	String	64	否	692213214242123	商品标准条码（UPC、EAN、JAN…）
//                 product_name	String	64	是	苹果手机	商品名称
//                 product_description	String	128	是	iPhone5	商品描述
//                 product_unit_price	Double	32	是	23	商品单价（按对应币别的法定单位，最多4位小数点）
//                 currency	String	10	是	USD	币别（按照ISO标准三字码，目前只支持USD）
//                 qty
    
//             declare_product_info	List		是		海关申报列表信息(每个包裹的申报信息，方式1：填写申报产品代码和申报数量；方式2：填写其他详细申报信息)
//                 declare_product_name_cn	String	64	否	手机	申报品名(当地语言)
//                 declare_product_name_en	String	64	否	Phone	申报品名（英语）
//                 declare_product_code_qty	Number	32	是	3	申报数量
//                 unit_declare_product	String	32	否	PCS	单位（点击查看详情；默认值：PCS）
//                 declare_unit_price_export	Double	64	是	23	出口国申报单价（按对应币别的法定单位，最多4位小数点）
//                 currency_export	String	10	是	USD	币别（按照ISO标准，目前只支持USD）点击查看详情
//                 declare_unit_price_import	Double	64	是	23	进口国申报单价（按对应币别的法定单位，最多4位小数点）
//                 currency_import	String	10	是	USD	币别（按照ISO标准，目前只支持USD）
//                 brand_export	String	128	是	无	出口国品牌
//                 brand_import	String	128	是	狮子牌	进口国品牌
//                 package_remarks	String	200	否	skutest	配货字段（打印标签选择显示配货信息是将会显示：package_remarks*qty）

            $parcel_list = [];
            $product_list = [];
            $declare_product_info = [];
            
            $parcel_value = 0;
            foreach ($order->items as $k=>$vitem){
                
                $itemPriceUSD = \common\helpers\Helper_Currency::convertThisCurrencyToUSD($order->currency, $vitem->price);
                $product_list[$k] = [
                       "product_name" => $vitem->product_name,
                       "product_description" => $vitem->product_name,
                       "product_unit_price" => $itemPriceUSD,
                       "currency" => "USD",
                       "qty" => $vitem->quantity,
                ];
            
                $declare_product_info[$k]=[
                        "declare_product_name_cn" =>$e['declare_product_name_cn'][$k],
                        "declare_product_name_en" =>$e['declare_product_name_en'][$k],
                        "declare_product_code_qty" =>$e['DeclarePieces'][$k],//件数(默认: 1)
                        "unit_declare_product" => "PCS",
                        "declare_unit_price_export" => $e['DeclaredValue'][$k],
                        "currency_export"=>"USD",
                        "hscode_export" => $e['hscode_export'][$k],// dzt20200714 add
//                         "hscode_import"=>,
                        "brand_export"=>"USD",
                        "declare_unit_price_import" => $e['DeclaredValue'][$k],
                        "currency_import"=>"USD",
                        "brand_import"=>"USD",
                        "package_remarks"=>$e['package_remarks'][$k],
                        
                ];
            
                $parcel_value += $e['DeclaredValue'][$k];
            }
            
            $parcel_list['weight'] = $e['total_weight_4px'];
            $parcel_list['include_battery'] = $e['include_battery'];
            $parcel_list['parcel_value'] = $parcel_value;
            $parcel_list['currency'] = "USD";
            $parcel_list['product_list'] = $product_list;
            $parcel_list['declare_product_info'] = $declare_product_info;
            
            
            
            if($order->consignee_country_code == 'UK'){
                $country_code = 'GB';
            }else{
                $country_code = $order->consignee_country_code;
            }
            

            $logistics_service_info = [];
            $logistics_service_info['logistics_product_code'] = $service->shipping_method_code;// 物流产品代码(点击查看详情)
            
// sender	Object		是		发件人信息
// first_name	String	32	是		名/姓名
// last_name	String	32	否		姓
// company	String	64	否		公司名
// phone	String	32	否		电话（必填）
// phone2	String	32	否		电话2
// email	String	32	否		邮箱
// post_code	String	32	否		邮编
// country	String	10	是		国家（国际二字码 标准ISO 3166-2 ）
// state	String	64	否		州/省
// city	String	64	是		城市
// district	String	64	否		区、县
// street	String	64	否		街道/详细地址
// house_number	String	32	否		门牌号
            
            $sender = [];
            $sender['first_name'] = $shippingFrom['contact'];
            $sender['company'] = $shippingFrom['company'];
            $sender['phone'] = $shippingFrom['phone'];
            $sender['email'] = $shippingFrom['email'];
            $sender['post_code'] = $shippingFrom['postcode'];
            $sender['country'] = $shippingFrom['country'];
            $sender['state'] = $shippingFrom['province'];//收件人州
            $sender['city'] = $shippingFrom['city'];
            $sender['dist'] = $shippingFrom['district'];
            $sender['street'] = $shippingFrom['street'];
            
            
            $tmpConsigneeProvince = $order->consignee_province;
            if (empty($tmpConsigneeProvince)) {
                if($order->consignee_country_code == 'FR')
                    $tmpConsigneeProvince = $order->consignee_city;
                else if(!empty($order->consignee_city))
                    $tmpConsigneeProvince = $order->consignee_city;
                else
                    return ['error'=>1, 'data'=>'', 'msg'=>'发货时收件人 州/省或城市不能为空'];
            }
            
//             recipient_info	Object		是		收件人信息
//             first_name	String	32	是		名/姓名
//             last_name	String	32	否		姓
//             company	String	64	否		公司名
//             phone	String	32	是		电话（必填）
//             phone2	String	32	否		电话2
//             email	String	32	否		邮箱
//             post_code	String	32	否		邮编
//             country	String	10	是		国家（国际二字码 标准ISO 3166-2 ）
//             state	String	64	否		州/省
//             city	String	64	是		城市
//             district	String	64	否		区、县（可对应为adress 2）
//             street	String	64	是		街道/详细地址（可对应为adress 1）
//             house_number	String	32	否		门牌号
            $recipient_info = [];
            $recipient_info['first_name'] = $order->consignee;
            $recipient_info['company'] = $order->consignee_company;
            $recipient_info['phone'] = $addressAndPhone['phone1'];
            $recipient_info['email'] = \eagle\modules\carrier\helpers\CarrierOpenHelper::getOrderEmailResults($order->consignee_email);
            $recipient_info['post_code'] = $order->consignee_postal_code;
            $recipient_info['country'] = $country_code;
            $recipient_info['state'] = $tmpConsigneeProvince;//收件人州
            $recipient_info['city'] = $order->consignee_city;
            $recipient_info['dist'] = $addressAndPhone['address_line2'];;
            $recipient_info['street'] = $addressAndPhone['address_line1'];
            
            
//             deliver_type_info	Object		是		货物到仓方式信息
//             deliver_type	String	32	是	1	到仓方式（1:上门揽收；2:快递到仓；3:自送到仓；5:自送门店）
//             warehouse_code	String	32	否		收货仓库/门店代码（仓库代码）
//             pick_up_info	Object		否		上门揽收信息
//             express_to_4px_info	Object		否		快递到仓信息
//             self_send_to_4px_info	Object		否		自己送仓信息
            $deliver_type_info = [];
            $deliver_type_info['deliver_type'] = $service_params['deliver_type'];
            
            $deliver_to_recipient_info = [];
            $deliver_to_recipient_info['deliver_type'] = "HOME_DELIVERY";// 投递类型
            
            $return_info = [];// 退件信息
            $return_info['is_return_on_domestic'] = "N";// 境内异常处理策略(Y：退件；N:销毁；U:其他；) 默认值：N；
            
            $request = [
                    'ref_no'=>$customer_number,//String	64	是	c123412	参考号（客户自有系统的单号，如客户单号）
                    'business_type'=>"BDS",//业务类型(4PX内部调度所需，如需对接传值将说明，默认值：BDS。)
                    	
                    // 货物类型（1：礼品;2：文件;3：商品货样;5：其它；默认值：5）
                    'cargo_type'=>empty($service_params['cargo_type'])?5:$service_params['cargo_type'],
                    
                    // sales_platform	String	128	否	AMAZON	销售平台（点击查看详情）
                    'sales_platform'=>strtoupper($order->order_source),
                    
                    'parcel_list'=>$parcel_list,//包裹列表
                    'logistics_service_info'=>$logistics_service_info,//物流服务信息
                    'sender'=>$sender,//发件人信息
                    'recipient_info'=>$recipient_info,//收件人信息
                    'deliver_type_info'=>$deliver_type_info,//货物到仓方式信息
                    'deliver_to_recipient_info'=>$deliver_to_recipient_info,//投递信息
                    'return_info'=>$return_info,//退件信息
                    
            ];
            
            
//             is_insure	String	3	是	Y	是否投保(Y、N)

//             insurance_info	Object		是		投保信息（投保时必须填写）
//             insure_type	String	10	否	XY	保险类型（XY:4PX保价；XP:第三方保险）
//             insure_value	Double	32	否	23	保险价值

            if($service_params['insure_type'] == "N"){// dzt20200514 免得新增选项，就在insure_type 上面加一个N的值表示不投保
                $request['is_insure'] = "N";// 是否投保(Y、N)
            }else if(!empty($service_params['insure_value']) && $service_params['insure_value']>0){
                $insurance_info = [];
                $insurance_info['insure_type'] = $service_params['insure_type'];
                $insurance_info['insure_value'] = $e['insure_value'];
            
                $request['is_insure'] = "Y";// 是否投保(Y、N)
                $request['insurance_info'] = $insurance_info;
            }else{
                $request['is_insure'] = "N";// 是否投保(Y、N)
            }
            
            if(!empty($vat_no)){
                $request['vat_no'] = $vat_no;// VAT税号(数字或字母)
            }
            
            
            Helper_Array::removeEmpty($request);
            
            $accessToken = $this->checkAccessToken($tmp_a);
            
            $publicParams = self::$publicParams;
            $publicParams['method'] = "ds.xms.order.create";
            $publicParams['v'] = "1.1.0";
            
            $publicParams['sign'] =  $this->sign($publicParams, $request);
            $publicParams['access_token'] =  $accessToken;
            
            $header = [];
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json;charset=utf-8';
            
            
            $url = self::$apiHost."/router/api/service";
            $query = http_build_query($publicParams);
            $url .= "?".$query;
            
            $response = Helper_Curl::post($url, json_encode($request), $header);
//             ds_consignment_no	String		直发委托单号
//             4px_tracking_no	String		4PX跟踪号
//             ref_no	String		客户单号/客户参考号
            
            \Yii::info('LB_4PXNewCarrierAPI, puid:'.$puid.'，order_id:'.$order->order_id.' response:'.$response.PHP_EOL."request1:".json_encode($request),"carrier_api");
            $result = json_decode($response, true);
            
            //接受到返回信息 进行处理
            if(!empty($result['result']) && empty($result['errors'])){
                if(empty($result['data'])){
                    return self::getResult(1,'','上传失败,递四方返回结构错误,请联系小老板技术e1');
                }
                
                $trackingNo = "";
                $returnNo = [];
                if(!empty($service_params['is_use_4px_tracking_no']) && $service_params['is_use_4px_tracking_no'] == "Y"){
                    $trackingNo = $result['data']['4px_tracking_no'];
                }else if(!empty($result['data']['logistics_channel_no'])){
                    // dzt20200330 递四方的人说ds_consignment_no 不是物流单
                    // $trackingNo = $result['data']['ds_consignment_no'];
                    $trackingNo = $result['data']['logistics_channel_no'];// 物流渠道号码。如果结果返回为空字符，表示暂时没有物流渠道号码，请稍后主动调用查询直发委托单接口查询
                }
                
                $returnNo['4px_tracking_no'] = $result['data']['4px_tracking_no'];
                $returnNo['ds_consignment_no'] = $result['data']['ds_consignment_no'];
                $returnNo['logistics_channel_no'] = $result['data']['logistics_channel_no'];
                
                
                //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
                $r = CarrierAPIHelper::orderSuccess($order, $service, $result['data']['ref_no'], OdOrder::CARRIER_WAITING_GETCODE, $trackingNo, $returnNo);
        
                try{
                    //插入PDF队列
                    $print_param = array();
                    $print_param['carrier_code'] = $service->carrier_code;
                    $print_param['api_class'] = 'LB_4PXNewCarrierAPI';
                    $print_param['carrier_params'] = $service_params;
                    $print_param['account_api_params'] = $api_params;
                    $print_param['4px_tracking_no'] = $result['data']['4px_tracking_no'];
                    $print_param['ds_consignment_no'] = $result['data']['ds_consignment_no'];
                    $print_param['logistics_channel_no'] = $result['data']['logistics_channel_no'];
                    $print_param['run_status'] = 4;
                    CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $result['data']['ref_no'], $print_param);
                }catch (\Exception $ex){
                }
        
                return self::getResult(0,$r,'上传成功，物流号:'.$trackingNo);
            }else{
                if(!empty($result['errors'])){
                    $msg = "error_code：".$result['errors'][0]["error_code"]."，error_msg：".$result['errors'][0]["error_msg"];
                    throw new CarrierException("递四方创建订单失败e1，$msg");
                }elseif(!empty($result['msg'])){
                    throw new CarrierException("递四方创建订单失败e2，".$result['msg']);
                }else{
                    throw new CarrierException("上传数据失败");
                } 
        
            }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }
    
    /**
     * 取消订单号
     **/
    public function cancelOrderNO($data){
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];
			###################################################################################
			if($response['code'] == 0){
				$orderShippedObj = OdOrderShipped::findOne(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number]);
				$orderShippedObj->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->customer_number = '';
				$order->save();
				return self::getResult(0,'', '订单取消成功！');
			}else{
				return self::getResult(1,'',$response['msg']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }
    
    /**
     * 交运
     **/
    public function doDispatch($data){
        return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');
    }
    
    /**
     * 申请跟踪号
     **/
    public function getTrackingNO($data){
        try{
            $user=\Yii::$app->user->identity;
            if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();
        
            $order = $data['order'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0,1,$order);
            $shipped = $checkResult['data']['shipped'];
        
            
            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];
            $service = $info['service'];// 运输服务相关信息
            //获取到帐号中的认证参数
            $api_params = $account->api_params;
            $carrier_params = $service->carrier_params;
            
            $accessToken = $this->checkAccessToken($account);
            
            $publicParams = self::$publicParams;
            $publicParams['method'] = "ds.xms.order.get";
            $publicParams['v'] = "1.1.0";
            
            $request = [];
            if(!empty($shipped->customer_number)){
                $requestNo= $shipped->customer_number;
            }elseif(!empty($shipped->return_no['4px_tracking_no'])){
                $requestNo= $shipped->return_no['4px_tracking_no'];
            }elseif(!empty($shipped->tracking_number)){// 面单号测试系统获取不到，报错提示订单不存在
                $requestNo= $shipped->tracking_number;
            }
            
            if(empty($requestNo)){
                return self::getResult(1,'', 'order:'.$order->order_id .' 缺少追踪号,请检查订单是否已上传' );
            }
            
            $request['request_no'] = $requestNo;
            
            
            $publicParams['sign'] =  $this->sign($publicParams, $request);
            $publicParams['access_token'] =  $accessToken;
            
            $header = [];
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json;charset=utf-8';
            
            
            $url = self::$apiHost."/router/api/service";
            $query = http_build_query($publicParams);
            $url .= "?".$query;
            
            $response = Helper_Curl::post($url, json_encode($request), $header);
            
            \Yii::info('LB_4PXNewCarrierAPI getTrackingNO puid:'.$puid.'，order_id:'.$order->order_id.' response:'.$response.PHP_EOL."request1:".json_encode($request),"carrier_api");
            $result = json_decode($response, true);
            
            
            //接受到返回信息 进行处理
            if(!empty($result['result']) && empty($result['errors'])){
                if(empty($result['data'])){
                    throw new CarrierException('获取跟踪号失败,递四方返回结构错误,请联系小老板技术e1');
                }
            }else{
                if(!empty($result['errors'])){
                    $msg = "error_code：".$result['errors'][0]["error_code"]."，error_msg：".$result['errors'][0]["error_msg"];
                    throw new CarrierException("获取递四方订单失败e1，$msg");
                }elseif(!empty($result['msg'])){
                    throw new CarrierException("获取递四方订单失败e2，".$result['msg']);
                }else{
                    throw new CarrierException("获取跟踪号失败");
                }
            }
            
            $trackingNo = "";
            if(!empty($carrier_params['is_use_4px_tracking_no']) && $carrier_params['is_use_4px_tracking_no'] == "Y"){
                $trackingNo = $result['data'][0]['consignment_info']['4px_tracking_no'];
            }else{
//                 $trackingNo = $result['data'][0]['consignment_info']['ds_consignment_no'];// dzt20200330
                $trackingNo = $result['data'][0]['consignment_info']['logistics_channel_no'];
            }
            
            if(empty($trackingNo)){
                throw new CarrierException("暂时没有物流渠道跟踪号，请稍后再查询");
            }
            
            $is_new = false;
            if($shipped->tracking_number != $trackingNo){
                $is_new = true;
                	
                $tmp_return_no = $shipped->return_no;
                $tmp_return_no['4px_tracking_no'] = $result['data'][0]['consignment_info']['4px_tracking_no'];
                $tmp_return_no['ds_consignment_no'] = $result['data'][0]['consignment_info']['ds_consignment_no'];
                $tmp_return_no['logistics_channel_no'] = $result['data'][0]['consignment_info']['logistics_channel_no'];
                
                $old_track_no = $shipped->tracking_number;
                $shipped->return_no = $tmp_return_no;
                	
                \eagle\modules\util\helpers\OperationLogHelper::log('order',$order->order_id,'获取跟踪号', '递四方', '旧的跟踪号:'.$shipped->tracking_number.'.新的跟踪号:'.$trackingNo);
            }
            
            $shipped->tracking_number = $trackingNo;
            $shipped->save();
            $order->tracking_number = $trackingNo;
            $order->save();
            
            if(!empty($shipped->tracking_number)){
                $print_param = array();
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_4PXNewCarrierAPI';
                $print_param['carrier_params'] = $carrier_params;
                $print_param['account_api_params'] = $api_params;
                $print_param['4px_tracking_no'] = $result['data'][0]['consignment_info']['4px_tracking_no'];
                $print_param['ds_consignment_no'] = $result['data'][0]['consignment_info']['ds_consignment_no'];
                $print_param['logistics_channel_no'] = $result['data'][0]['consignment_info']['logistics_channel_no'];
                
                try{
                    CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
                }catch (\Exception $ex){return self::getResult(1,'',$e->msg());}
                
            }
            
            return BaseCarrierAPI::getResult(0, '', '获取物流号成功！物流号：'.(($is_new == true) ? '旧的跟踪号:'.$old_track_no.'.新的跟踪号:'.$trackingNo : $trackingNo));
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
        
        
    }
    
    /**
     * 打单
     **/
    public function doPrint($data){
        try{
            $pdf = new PDFMerger();
            $user=\Yii::$app->user->identity;
            if(empty($user)){
                throw new CarrierException('用户登陆信息缺失,请重新登陆');
            }
            $puid = $user->getParentUid();
    
            $order = current($data);reset($data);
            $order = $order['order'];
    
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];// object SysCarrierAccount
            $service = $info['service'];// object SysShippingService
            
            $carrier_params = $service->carrier_params;
            
            // dzt20200601 修改成批量获取pdf接口
            $request = [];
            // 返回面单的格式（PDF：返回PDF下载链接；IMG：返回IMG图片下载链接） 默认为PDF；
            if((!empty($carrier_params['response_label_format']))){
                $request['response_label_format'] = $carrier_params['response_label_format'];
            }else{
                $request['response_label_format'] = "PDF";
            }
            
            // 标签大小（label_80x90：标签纸80.5mm×90mm；a4：A4纸） 默认为label_80x90；
            if((!empty($carrier_params['label_size']))){
                $request['label_size'] = $carrier_params['label_size'];
            }else{
                $request['label_size'] = "label_80x90";
            }
            
            // 是否打印配货信息（Y：打印；N：不打印） 默认为N。 注：这里的配货信息指是否在标签上打印配货信息。若需单独打印配货单，使用create_package_label字段控制。
            if((!empty($carrier_params['is_print_pick_info']))){
                $request['is_print_pick_info'] = $carrier_params['is_print_pick_info'];
            }else{
                $request['is_print_pick_info'] = "N";
            }
            
            // 是否打印当前时间（Y：打印；N：不打印） 默认为N；
            if((!empty($carrier_params['is_print_time']))){
                $request['is_print_time'] = $carrier_params['is_print_time'];
            }else{
                $request['is_print_time'] = "N";
            }
            
            // 是否打印买家ID（Y：打印；N：不打印） 默认为N；
            if((!empty($carrier_params['is_print_buyer_id']))){
                $request['is_print_buyer_id'] = $carrier_params['is_print_buyer_id'];
            }else{
                $request['is_print_buyer_id'] = "N";
            }
            
            // 是否打印报关单（Y：打印；N：不打印） 默认为N；
            if((!empty($carrier_params['is_print_declaration_list']))){
                $request['is_print_declaration_list'] = $carrier_params['is_print_declaration_list'];
            }else{
                $request['is_print_declaration_list'] = "N";
            }
            
            // 报关单上是否打印客户预报重（Y：打印；N：不打印） 默认为N。 注：针对单独打印报关单功能；
            if((!empty($carrier_params['is_print_customer_weight']))){
                $request['is_print_customer_weight'] = $carrier_params['is_print_customer_weight'];
            }else{
                $request['is_print_customer_weight'] = "N";
            }
            
            // 是否单独打印配货单（Y：打印；N：不打印） 默认为N。
            if((!empty($carrier_params['create_package_label']))){
                $request['create_package_label'] = $carrier_params['create_package_label'];
            }else{
                $request['create_package_label'] = "N";
            }
            
            // 配货单上是否打印配货条形码（Y：打印；N：不打印） 默认为N。 注：针对单独打印配货单功能；
            if((!empty($carrier_params['is_print_pick_barcode']))){
                $request['is_print_pick_barcode'] = $carrier_params['is_print_pick_barcode'];
            }else{
                $request['is_print_pick_barcode'] = "N";
            }
            
            // dzt20200325 是否合并打印(Y：合并；N：不合并)默认为N； 注：合并打印，指若报关单和配货单打印为Y时，是否和标签合并到同一个URL进行返回
            if((!empty($carrier_params['is_print_merge']))){
                $request['is_print_merge'] = $carrier_params['is_print_merge'];
            }else{
                $request['is_print_merge'] = "N";
            }
            
            $requestNoArr = [];
            $requestNo = '';
            foreach ($data as $k=>$v) {
                $order = $v['order'];
                	
                $checkResult = CarrierAPIHelper::validate(1,1,$order);
                $shipped = $checkResult['data']['shipped'];
                	
                if(!empty($shipped->customer_number)){
                    $requestNo= $shipped->customer_number;
                }elseif(!empty($shipped->return_no['4px_tracking_no'])){
                    $requestNo= $shipped->return_no['4px_tracking_no'];
                }elseif(!empty($shipped->tracking_number)){// 面单号测试系统获取不到，报错提示订单不存在
                    $requestNo= $shipped->tracking_number;
                }
                
                if(empty($requestNo)){
                    return self::getResult(1,'', 'order:'.$order->order_id .' 缺少追踪号,请检查订单是否已上传' );
                }
                
                $requestNoArr[] = $requestNo;
                
//                 // 请求单号（支持4PX单号、客户单号和面单号）
//                 $request['request_no'] = $requestNo;
                
//                 $accessToken = $this->checkAccessToken($account);
                
//                 $publicParams = self::$publicParams;
//                 $publicParams['method'] = "ds.xms.label.get";
//                 // $publicParams['method'] = "ds.xms.label.getlist"; 批量接口
                
//                 $publicParams['v'] = "1.1.0";
                
//                 $publicParams['sign'] =  $this->sign($publicParams, $request);
//                 $publicParams['access_token'] =  $accessToken;
                
//                 $header = [];
//                 $header[] = 'Content-type: application/json;charset=utf-8';
//                 $header[] = 'Accept: application/json;charset=utf-8';
                
                
//                 $url = self::$apiHost."/router/api/service";
//                 $query = http_build_query($publicParams);
//                 $url .= "?".$query;
                
//                 $response = Helper_Curl::post($url, json_encode($request), $header);
                
//                 \Yii::info('LB_4PXNewCarrierAPI doPrint puid:'.$puid.'，order_id:'.$order->order_id.' response:'.$response.PHP_EOL."request1:".json_encode($request),"carrier_api");
//                 $result = json_decode($response, true);
                
//                 //接受到返回信息 进行处理
//                 if(!empty($result['result']) && empty($result['errors'])){
//                     if(empty($result['data'])){
//                         throw new CarrierException('打印失败,递四方返回结构错误,请联系小老板技术e1');
//                     }
//                 }else{
//                     if(!empty($result['errors'])){
//                         $msg = "error_code：".$result['errors'][0]["error_code"]."，error_msg：".$result['errors'][0]["error_msg"];
//                         throw new CarrierException("打印递四方订单失败e1，$msg");
//                     }elseif(!empty($result['msg'])){
//                         throw new CarrierException("打印递四方订单失败e2，".$result['msg']);
//                     }else{
//                         throw new CarrierException("打印订单失败");
//                     } 
//                 }
                
                
//                 $url = $result['data']['label_url_info']['logistics_label'];
//                 $print_pdf_result = Helper_Curl::get($url,null,null,false,null,null,true);
                
//                 $pdfUrl = CarrierAPIHelper::savePDF($print_pdf_result,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
//                 $pdf->addPDF($pdfUrl['filePath'],'all');//合并相同运输运输方式的pdf流
                
//                 $order->print_carrier_operator = $puid;
//                 $order->printtime = time();
//                 $order->carrier_error = '';
//                 $order->save();
                
//                 // dzt20200319 递四方pdf升级了 无法合并的样子 有批量接口，先不实现。直接返回一个pdf
//                 return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
                
            }
            
            
            // 请求单号（支持4PX单号、客户单号和面单号）
            $request['request_no'] = $requestNoArr;
            
            $accessToken = $this->checkAccessToken($account);
            
            $publicParams = self::$publicParams;
            // dzt20200601
//             $publicParams['method'] = "ds.xms.label.get";
//             $publicParams['v'] = "1.1.0";
            $publicParams['method'] = "ds.xms.label.getlist"; // 批量接口
            $publicParams['v'] = "1.0.0";
            
            
            $publicParams['sign'] =  $this->sign($publicParams, $request);
            $publicParams['access_token'] =  $accessToken;
            
            $header = [];
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json;charset=utf-8';
            
            
            $url = self::$apiHost."/router/api/service";
            $query = http_build_query($publicParams);
            $url .= "?".$query;
            
            $response = Helper_Curl::post($url, json_encode($request), $header);
            
            \Yii::info('LB_4PXNewCarrierAPI doPrint puid:'.$puid.'，order_id:'.$order->order_id.' response:'.$response.PHP_EOL."request1:$url,".json_encode($request),"carrier_api");
            $result = json_decode($response, true);
            
            //接受到返回信息 进行处理
            if(!empty($result['result']) && empty($result['errors'])){
                if(empty($result['data'])){
                    throw new CarrierException('打印失败,递四方返回结构错误,请联系小老板技术e1');
                }
            }else{
                if(!empty($result['errors'])){
                    $msg = "";
                    foreach ($result['errors'] as $error){
                        $msg .= "error_code：".$error["errorCode"]."，error_msg：".$error["errorMsg"]."<br>";
                    }
                    
                    throw new CarrierException("打印递四方订单失败e1，$msg");
                }elseif(!empty($result['msg'])){
                    throw new CarrierException("打印递四方订单失败e2，".$result['msg']);
                }else{
                    throw new CarrierException("打印订单失败");
                }
            }
            
            // dzt20200601
//             $url = $result['data']['label_url_info']['logistics_label'];

            $url = $result['data'];
            $print_pdf_result = Helper_Curl::get($url,null,null,false,null,null,true);
            
            $pdfUrl = CarrierAPIHelper::savePDF($print_pdf_result,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
            $pdf->addPDF($pdfUrl['filePath'],'all');//合并相同运输运输方式的pdf流
            
            
            foreach ($data as $k=>$v) {
                $order = $v['order'];
                $order->print_carrier_operator = $puid;
                $order->printtime = time();
                $order->carrier_error = '';
                $order->save();
            }
            
            // 不能merge 否则很可能会报错
//             isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl='';
            $return_infos['msg'] = '连接已生成,请点击并打印';
            $return_infos['data'] = ['pdfUrl'=>$pdfUrl['pdfUrl']];
    
            return BaseCarrierAPI::getResult(0,$return_infos['data'],$return_infos['msg']);
        }catch(CarrierException $e){return BaseCarrierAPI::getResult(1,'',$e->msg());}
    }
    
    /**
     * 用来确定打印完成后 订单的下一步状态
     *
     * 公共方法
     */
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }
    
    
    //获取运输服务
    public function getCarrierShippingServiceStr($account){
        try{
// //             self::$appKey = '59c1e237-d0a0-4992-a3cc-6851cfba5794';
// //             self::$appSecret = 'fa9c3811-5e76-44e9-aaf3-7775f98dfd13';
            
//             $request = [];
//             $request['transport_mode'] = "1";
            
//             // 'http://open.sandbox.4px.com/router/api/service?app_key=98b60f3f-9748-416b-8bc6-13adca5a174c&format=json&method=ds.xms.logistics_product.getlist&timestamp=1571802497465&v=1.0.0&sign=17c22c6f9ec3a56d4277fc1fce7d76a9&access_token=65d2c5e113464bca6d5dd38c44f474d0  '
// //             $testStr = 'method=ds.xms.logistics_product.getlist&app_key=59c1e237-d0a0-4992-a3cc-6851cfba5794&v=1.0.0&timestamp=1571802497465&format=json';
//             $testStr = 'app_key=98b60f3f-9748-416b-8bc6-13adca5a174c&format=json&method=ds.xms.logistics_product.getlist&timestamp=1571802497465&v=1.0.0';// &access_token=65d2c5e113464bca6d5dd38c44f474d0&sign=17c22c6f9ec3a56d4277fc1fce7d76a9
//             parse_str($testStr, $publicParams); 
//             print_r($publicParams);
//             $sign = $this->sign($publicParams, $request);
            
//             exit($sign);
            
            $accessToken = $this->checkAccessToken($account);
            
            $publicParams = self::$publicParams;
            $publicParams['method'] = "ds.xms.logistics_product.getlist";
            $publicParams['v'] = "1.0.0";
            
            $request = [];
            $request['transport_mode'] = "1";// 运输方式：1 所有方式；2 国际快递；3 国际小包；4 专线；5 联邮通；6 其他；
            
            $publicParams['sign'] =  $this->sign($publicParams, $request);
            $publicParams['access_token'] =  $accessToken;
            
            $header = [];
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json;charset=utf-8';
            
            
            $url = self::$apiHost."/router/api/service";
            $query = http_build_query($publicParams);
            $url .= "?".$query;
            
            $response = Helper_Curl::post($url, json_encode($request), $header);
//             \Yii::info("LB_4PXNewCarrierAPI getCarrierShippingServiceStr result:".$response.PHP_EOL."post params:".json_encode($request)
//                     .PHP_EOL."last_post_info:".json_encode(Helper_Curl::$last_post_info), "carrier_api");
            
//             echo $response;exit();
            
            $result = json_decode($response, true);
    		$channelStr = '';
    		foreach($result['data'] as $v){
    			$channelStr .= $v['logistics_product_code'].':'.$v['logistics_product_name_cn'].';';
    		}
    		
//     		\Yii::info("LB_4PXNewCarrierAPI getCarrierShippingServiceStr channelStr:".$channelStr, "file");
    		
    		
            if(empty($channelStr)){
                return self::getResult(1, '', '');
            }else{
                return self::getResult(0, $channelStr, '');
            }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
        
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
        $result['error'] = 0;
// 	    try{
	        
// 	        if(!empty($check_result['data'])){
// 	            $result['error'] = 0;
// 	        }
// 	    }catch(CarrierException $e){
// 	    }
	    
        return $result;
    }
    
    
    //获取API的打印面单标签
    public function getCarrierLabelApiPdf($SAA_obj, $carrier_params){
        try{
            $puid = $SAA_obj->uid;
    
            
            if(!empty($SAA_obj->customer_number)){
                $requestNo= $SAA_obj->customer_number;
            }elseif(!empty($carrier_params['4px_tracking_no'])){
                $requestNo= $carrier_params['4px_tracking_no'];
            }elseif(!empty($carrier_params['logistics_channel_no'])){
                // 面单号测试系统获取不到，报错提示订单不存在 // dzt20200330 修改ds_consignment_no => logistics_channel_no
                $requestNo= $carrier_params['logistics_channel_no'];
            }
            
            $request = [];
            
            // 请求单号（支持4PX单号、客户单号和面单号）
            $request['request_no'] = $requestNo;
            
            // 返回面单的格式（PDF：返回PDF下载链接；IMG：返回IMG图片下载链接） 默认为PDF；
            if((!empty($carrier_params['response_label_format']))){
                $request['response_label_format'] = $carrier_params['response_label_format'];
            }else{
                $request['response_label_format'] = "PDF";
            }
            
            // 标签大小（label_80x90：标签纸80.5mm×90mm；a4：A4纸） 默认为label_80x90；
            if((!empty($carrier_params['label_size']))){
                $request['label_size'] = $carrier_params['label_size'];
            }else{
                $request['label_size'] = "label_80x90";
            }
            
            // 是否打印配货信息（Y：打印；N：不打印） 默认为N。 注：这里的配货信息指是否在标签上打印配货信息。若需单独打印配货单，使用create_package_label字段控制。
            if((!empty($carrier_params['is_print_pick_info']))){
                $request['is_print_pick_info'] = $carrier_params['is_print_pick_info'];
            }else{
                $request['is_print_pick_info'] = "N";
            }
            
            // 是否打印当前时间（Y：打印；N：不打印） 默认为N；
            if((!empty($carrier_params['is_print_time']))){
                $request['is_print_time'] = $carrier_params['is_print_time'];
            }else{
                $request['is_print_time'] = "N";
            }
            
            // 是否打印买家ID（Y：打印；N：不打印） 默认为N；
            if((!empty($carrier_params['is_print_buyer_id']))){
                $request['is_print_buyer_id'] = $carrier_params['is_print_buyer_id'];
            }else{
                $request['is_print_buyer_id'] = "N";
            }
            
            // 是否打印报关单（Y：打印；N：不打印） 默认为N；
            if((!empty($carrier_params['is_print_declaration_list']))){
                $request['is_print_declaration_list'] = $carrier_params['is_print_declaration_list'];
            }else{
                $request['is_print_declaration_list'] = "N";
            }
            
            // 报关单上是否打印客户预报重（Y：打印；N：不打印） 默认为N。 注：针对单独打印报关单功能；
            if((!empty($carrier_params['is_print_customer_weight']))){
                $request['is_print_customer_weight'] = $carrier_params['is_print_customer_weight'];
            }else{
                $request['is_print_customer_weight'] = "N";
            }
            
            // 是否单独打印配货单（Y：打印；N：不打印） 默认为N。
            if((!empty($carrier_params['create_package_label']))){
                $request['create_package_label'] = $carrier_params['create_package_label'];
            }else{
                $request['create_package_label'] = "N";
            }
            
            // 配货单上是否打印配货条形码（Y：打印；N：不打印） 默认为N。 注：针对单独打印配货单功能；
            if((!empty($carrier_params['is_print_pick_barcode']))){
                $request['is_print_pick_barcode'] = $carrier_params['is_print_pick_barcode'];
            }else{
                $request['is_print_pick_barcode'] = "N";
            } 
            	
            $account = new Object();
            $account->api_params = $carrier_params['api_params'];
            
            $accessToken = $this->checkAccessToken($account);
            
            $publicParams = self::$publicParams;
            $publicParams['method'] = "ds.xms.label.get";
            $publicParams['v'] = "1.1.0";
            
            $publicParams['sign'] =  $this->sign($publicParams, $request);
            $publicParams['access_token'] =  $accessToken;
            
            $header = [];
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json;charset=utf-8';
            
            
            $url = self::$apiHost."/router/api/service";
            $query = http_build_query($publicParams);
            $url .= "?".$query;
            
            $response = Helper_Curl::post($url, json_encode($request), $header);
            
            \Yii::info('LB_4PXNewCarrierAPI getCarrierLabelApiPdf puid:'.$puid.'，order_id:'.$order->order_id.' response:'.$response.PHP_EOL."request1:$url ,".json_encode($request),"carrier_api");
            $result = json_decode($response, true);
            
            //接受到返回信息 进行处理
            if(!empty($result['result']) && empty($result['errors'])){
                if(empty($result['data'])){
                    throw new CarrierException('打印失败,递四方返回结构错误,请联系小老板技术e1');
                }
            }else{
                if(!empty($result['errors'])){
                    $msg = "error_code：".$result['errors'][0]["error_code"]."，error_msg：".$result['errors'][0]["error_msg"];
                    throw new CarrierException("打印递四方订单失败e1，$msg");
                }elseif(!empty($result['msg'])){
                    throw new CarrierException("打印递四方订单失败e2，".$result['msg']);
                }else{
                    throw new CarrierException("打印订单失败");
                }
            }
            
            
            $url = $result['data']['label_url_info']['logistics_label'];
            $print_pdf_result = Helper_Curl::get($url,null,null,false,null,null,true);
            
            $pdfPath = CarrierAPIHelper::savePDF2($print_pdf_result,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
            return $pdfPath;
            
        }catch(CarrierException $e){
            return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
        }
    }
    
    // dzt20191218 物流轨迹查询  未测试
    public function SyncStatus($data){
        $res = array();
    
        try{
            global $CACHE;
//             $pendingSubOne = isset($CACHE['subOne'])?$CACHE['subOne'] : null;
//             $ret=\Yii::$app->subdb->changeUserDataBase($pendingSubOne->puid);
//             $addinfo = json_decode($pendingSubOne->addinfo,true);
//             $order = OdOrder::find()->where(['order_source_order_id'=>$addinfo['order_id']])->one();
//             $info = CarrierAPIHelper::getAllInfo($order);
//             $account = $info['account'];
//             $accessToken = $this->checkAccessToken($account);
            
            $accessToken = "c16f08303d4d3fe508019e2769b093b5";// 
            
            $publicParams = self::$publicParams;
            $publicParams['method'] = "tr.order.tracking.get";
            $publicParams['v'] = "1.0.0";
            
            $request = [];
            $request['deliveryOrderNo'] = $data[0];
            
            $publicParams['sign'] =  $this->sign($publicParams, $request);
            $publicParams['access_token'] =  $accessToken;
            
            $header = [];
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json;charset=utf-8';
            
            $url = self::$apiHost."/router/api/service";
            $query = http_build_query($publicParams);
            $url .= "?".$query;
            
            $response = Helper_Curl::post($url, json_encode($request), $header);
            
            \Yii::info('LB_4PXNewCarrierAPI SyncStatus response:'.$response.PHP_EOL."request1:".json_encode($request),"carrier_api");
            
            echo $response;exit();
            $result = json_decode($response, true);
            
            
            
            //接受到返回信息 进行处理
            if(!empty($result['result']) && empty($result['errors'])){
                if(empty($result['data'])){
                    throw new CarrierException('物流轨迹查询失败,递四方返回结构错误,请联系小老板技术e1');
                }
            }else{
                if(!empty($result['errors'])){
                    $msg = "error_code：".$result['errors'][0]["errorCode"]."，error_msg：".$result['errors'][0]["errorMsg"];
                    throw new CarrierException("查询递四方物流轨迹失败e1，$msg");
                }elseif(!empty($result['msg'])){
                    throw new CarrierException("查询递四方物流轨迹失败e2，".$result['msg']);
                }else{
                    throw new CarrierException("物流轨迹查询失败");
                }
            }
            
            /**
             * 
            trackingList	List		物流轨迹信息集合
                businessLinkCode	String		轨迹代码
                occurDatetime	String		轨迹发生时间
                occurLocation	String		轨迹发生地
                trackingContent	String		轨迹描述
                destinationCountry
             */
            
            $resultData = $result['data'];
            $res['error']="0";
            $res['referenceNumber'] = "";
            $res['destinationCountryCode'] = "";// @todo $resultData['trackingList'][0]['destinationCountry'] 文档结构有这个，但不清楚内容。
            $res['trackingNumber'] = $resultData['deliveryOrderNo'];
            $res['trackContent']=[];
            if(!empty($resultData['trackingList'])){//判断是否有轨迹数据
                foreach($resultData['trackingList'] as $i=>$t){
                    $res['trackContent'][$i]['createDate'] = '';
                    $res['trackContent'][$i]['createPerson'] = '';
                    $res['trackContent'][$i]['occurAddress'] = empty($t['occurLocation'])?'':$t['occurLocation'];
                    $res['trackContent'][$i]['occurDate'] = empty($t['occurDatetime'])?'':$t['occurDatetime'];
                    $res['trackContent'][$i]['trackCode'] = empty($t['businessLinkCode'])?'':$t['businessLinkCode'];
                    $res['trackContent'][$i]['trackContent'] = empty($t['trackingContent'])?'':$t['trackingContent'];
                }
                $res['trackContent'] = array_reverse($res['trackContent']);//时间倒序
            }
            
            
        }catch(CarrierException $e){
            $res['error']="1";
            $res['trackContent']='查询发生错误'.$e->msg();
            $res['referenceNumber']=$data;
        }
        return $res;
    }
    
    
    /**
     * 授权跳转链接
     */
    public function getAuthUrl($redirect_uri){
//         1.取得授权码authorization_code
//         a) http请求方式：GET
//         b) http请求地址:http://open.sandbox.4px.com/authorize/get
//         c) 请求参数
//         client_id	String	是	注册App由系统生成的app_key
//         response_type	String	是	response_type=code这个是Oauth的固定写法
//         redirect_uri	String	是	授权成功后的回调地址，需要进行URL编码

        //  http://open.sandbox.4px.com/authorize/get?client_id=a3f9e3ac-7873-4686-92ef-e2cf57b5406f&response_type=code&redirect_uri=http%3A%2F%2Fwww.baidu.com
        $url = self::$apiHost."/authorize/get?client_id=".self::$appKey."&response_type=code&redirect_uri=".$redirect_uri;
        return $url;
    }
    
    /**
     * 根据authorization_code获取access_token
     */
    public function getAccessToken($redirect_uri, $authorization_code){
//         2.根据authorization_code获取access_token
        
//         a)http请求方式:POST
//         b)http请求地址:http://open.sandbox.4px.com/accessToken/get
//         c)ContentType:application/x-www-form-urlencoded
//         d)请求参数
//         名称	类型	是否必须	描述
//         client_id	String	是	注册App由系统生成的app_key
//         client_secret	String	是	注册App由系统生成的app_secret
//         grant_type	String	是	默认值:authorization_code
//         code	String	是	通过调用authorization_code接口返回的code值,一次有效
//         redirect_uri	String	是	可填写应用注册时回调地址域名。redirect_uri指的是应用发起请求时，所传的回调地址参数，在用户授权后应用会跳转至redirect_uri。要求与应用注册时填写的回调地址域名一致或顶级域名一致
        
        $header = array();
        $header[] = 'Content-Type: application/x-www-form-urlencoded';// 不生效，content type没有变化
        
//         $header[] = 'Accept: application/json';
        
        $request = [
                'client_id'=>self::$appKey,
                'client_secret'=>self::$appSecret,
                'grant_type'=>"authorization_code",
                'code'=>$authorization_code,
                'redirect_uri'=>$redirect_uri,
        ];
        	
        $url = self::$apiHost."/accessToken/get";
        $query = http_build_query($request);
//         $url .= "?".$query;
        $response = Helper_Curl::post($url, $query, $header);
        \Yii::info("LB_4PXNewCarrierAPI getAccessToken result:".$response.PHP_EOL."post params:".json_encode($request)
                .PHP_EOL."last_post_info:".json_encode(Helper_Curl::$last_post_info), "carrier_api");
        
        $result = json_decode($response, true);
        
//         expires_in	Nubmer	accessToken过期时间,单位为毫秒.
//         access_token	String	授权码,有效期7天
//         refresh_token	String	刷新码,有效期1年

        if(!empty($result['access_token'])){
            return array(true, $result);
        }else{
            return array(false, "获取4px access token失败。");
        }
        
    }

    /**
     * access_token 过期根据authorization_code获取access_token
     */
    public function refreshToken($account){
//         3.根据refresh_token换取access_token
        
//         a)如果refreshToken有效并且accessToken已经过期，那么可以使用refresh_token换取access_token，不用重新进行授权，然后访问用户隐私数据。 refresh_token过期则需要重新走获取Oauth2.0令牌流程重新授权。
//         b)http请求方式:post
//         c)http请求地址:http://open.sandbox.4px.com/accessToken/get
//         d)ContentType:application/x-www-form-urlencoded
//         e)请求参数
//         名称	类型	是否必须	描述
//         client_id	String	是	注册App由系统生成的app_key
//         client_secret	String	是	注册App由系统生成的app_secret
//         grant_type	String	是	默认值:refresh_token
//         refresh_token	String	是	上一步返回的refresh_token值
//         redirect_uri	String	是	可填写应用注册时回调地址域名。redirect_uri指的是应用发起请求时，所传的回调地址参数，在用户授权后应用会跳转至redirect_uri。要求与应用注册时填写的回调地址域名一致或顶级域名一致
    
        $account_api_params = $account->api_params;
        $refresh_token = $account_api_params['refresh_token'];
        
        $header = array();
//         $header[] = 'Content-type: application/x-www-form-urlencoded';
//         $header[] = 'Accept: application/json';
        
        $redirect_uri = "";
        
        $request = [
                'client_id'=>self::$appKey,
                'client_secret'=>self::$appSecret,
                'grant_type'=>"refresh_token",
                'refresh_token'=>$refresh_token,
                'redirect_uri'=>$redirect_uri,
        ];
        
        $url = self::$apiHost."/accessToken/get";
        $query = http_build_query($request);
        
        $response = Helper_Curl::post2($url, $query, $header);
        \Yii::info("LB_4PXNewCarrierAPI refreshToken result:".$response.PHP_EOL."post params:".json_encode($request), "carrier_api");
        
        $result = json_decode($response, true);
        
        if(!empty($result['access_token'])){
            $account_api_params['access_token'] = $result['access_token'];
            $account_api_params['refresh_token'] = $result['refresh_token'];
            $account_api_params['expires_in'] = $result['expires_in'];
            $account_api_params['access_token_timeout'] = time() + round($result['expires_in'] / 1000) - 60;// 提前60秒结束
            $account->api_params = $account_api_params;
            if ($account->save()) {
                $account->refresh();
                return array(true, "refresh token成功");
            }
            
            return array(false, "保存4px refresh token失败。");
        }else{
            
            return array(false, "获取4px refresh token失败。");
        }
        
    }
    
    /**
     * 检查access token是否过期，过期则refresh
     */
    public function checkAccessToken($account){
        $account_api_params = $account->api_params;
//         var_dump($account_api_params);
        if(!empty($account_api_params['access_token_timeout']) && $account_api_params['access_token_timeout'] < time() || true){
            list($ret, $msg) = $this->refreshToken($account);
            \Yii::info("LB_4PXNewCarrierAPI checkAccessToken msg:$msg", "carrier_api");
        }
        
        $account_api_params = $account->api_params;
//         var_dump($account_api_params);
        return $account_api_params['access_token'];
    }
    
    
    
    // 签名方法
    private function sign($publicParams, $bodyParams){
        // 按首字母升序排列, access_token不参与签名
//         app_key=16081f05-e8fc-4250-b9c4-0660d1ecbb28
//         format=json
//         method=ds.xms.order.create
//         timestamp=1532592413187
//         v=1.0
        ksort($publicParams);
        // 连接字符串(去掉所有=和&),连接参数名与参数值,并在尾加上body信息和appSecret
        // 此处假设appSecret=7eebf328-8e5a-4030-904d-ec6e89174fbc， 假设body信息(Json压缩格式)如下:{"aa":"bb"}
        // 得出app_key16081f05-e8fc-4250-b9c4-0660d1ecbb28formatjsonmethodds.xms.order.createtimestamp1532592413187v1.0{"aa":"bb"}7eebf328-8e5a-4030-904d-ec6e89174fbc
        
        $signStr = "";
        foreach ($publicParams as $paramKey => $paramVal){
            $signStr .= $paramKey.$paramVal;
        }
        
//         $signStr = "app_key".$publicParams['app_key']."format".$publicParams['format']."method".$publicParams['method']."timestamp".$publicParams['timestamp']."v".$publicParams['v'];
        
        
        
//         echo $signStr.json_encode($bodyParams).self::$appSecret.PHP_EOL;
        
        return md5($signStr.json_encode($bodyParams).self::$appSecret);
        
    }
    
    
    
    
    
}
?>
