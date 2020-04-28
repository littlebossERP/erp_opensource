<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use Jurosh\PDFMerge\PDFMerger;
use Qiniu\json_decode;
use eagle\modules\util\helpers\PDFMergeHelper;
use yii;

//try{
//    include '../components/PDFMerger/PDFMerger.php';
//}catch(\Exception $e){
//}

class LB_UBICarrierAPI extends BaseCarrierAPI
{
    public static $url = null;

    function __construct()
    {
    //        测试环境：
    //        url: http://qa-cn.etowertech.com
    //        token：test5AdbzO5OEeOpvgAVXUFE0A
    //        Key: 79db9e5OEeOpvgAVXUFWSD

    //        正式环境：
    //        http://etower.walltechsystem.cn/


        if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
            self::$url = 'http://etower.walltechsystem.cn';  //正式环境接口地址
//            self::$url = 'http://qa-cn.etowertech.com';//测试环境接口地址

        }else{
            self::$url = 'http://qa-cn.etowertech.com';//测试环境接口地址
        }
        
        
    }



    /**
    +----------------------------------------------------------
     * 申请订单号
    +----------------------------------------------------------
     **/
    public function getOrderNO($pdata)
    {
        try
        {
            $user=\Yii::$app->user->identity;
            if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();

            $data = $pdata['data'];  // 表单提交的数据
            $order = $pdata['order'];// object OdOrder 订单对象

            //重复发货 添加不同的标识码
            $extra_id = isset($pdata['data']['extra_id'])?$pdata['data']['extra_id']:'';
            $customer_number =$pdata['data']['customer_number'];

            if(isset($pdata['data']['extra_id'])){
                if($extra_id == ''){
                    return self::getResult(1, '', '强制发货标识码，不能为空');
                }
            }

            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
            $shipped = $checkResult['data']['shipped']; // object OdOrderShipped

            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];// 客户相关信息
            $service = $info['service'];// 运输服务相关信息

            //  if(empty($info['senderAddressInfo']['shippingfrom'])){
            //  return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
            //  }

            $account_api_params = $account->api_params;//获取到帐号中的认证参数
            $shippingfrom_address = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息(shppingfrom是属于客户填“发货地址”的信息)
            $service_carrier_params = $service->carrier_params;//运输服务相关参数

            $addressAndPhoneParams = array(
                'address' => array(
                    'consignee_address_line1_limit' => 40,
                    'consignee_address_line2_limit' => 60,
                    'consignee_address_line3_limit' => 40,
                ),
                'consignee_district' => 1,
                'consignee_county' => 1,
                'consignee_company' => 1,
                'consignee_phone_limit' => 20
            );
            $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

            $post_order = array(
            'referenceNo' => $order->order_source_order_id,//客户端订单唯一标识
            'recipientName' => $order->consignee,//收货人姓名
            'recipientCompany' => $order->consignee_company,//收货人单位
            'email' => $order->consignee_email,//收货人电子邮箱
            'phone' => $order->consignee_phone,//收货人电话
            'addressLine1' => $addressAndPhone['address_line1'],//收货人地址第一行
            'addressLine2' => $addressAndPhone['address_line2'],//收货人地址第二行
            'addressLine3' => $addressAndPhone['address_line3'],//收货人地址第三行
            'city' => $order->consignee_city,//收货人城市
            'state' => $order->consignee_province,//收货人州
            'postcode' => $order->consignee_postal_code,//收货人邮编
            'country' => $order->consignee_country_code,//收货人国家
            'volume' => empty($data['volume']) ? 1 : $data['volume'],//包裹体积(cubic meter)
            'length' => empty($data['length']) ? 1 : $data['length'],//包裹长度(cm)
            'width' => empty($data['width']) ? 1 : $data['width'],//包裹宽度(cm)
            'height' => empty($data['height']) ? 1 : $data['height'],//包裹高度(cm)
            'invoiceCurrency' => empty($data['invoiceCurrency']) ? $order->currency : $data['invoiceCurrency'],//币种
            // 'batteryType' => '',//电池类型
            // 'batteryPacking' => '',//电池包装
            // 'dangerousGoods' => '',//有无任何危险品
            'description' => empty($data['descriptions']) ? '' : $data['descriptions'],//商品描述	//可以英文数字，但不能纯数字。
//            'instruction' => empty($data['instruction']) ? '' : $data['instruction'],//派送说明
            'serviceCode' => $service->shipping_method_code,//服务类型
            'serviceOption' => empty($data['serviceOption']) ? '' : $data['serviceOption'],//服务类型
            // 'facility' => '',//发货人站点代码
            // 'platform' => '',//电商平台代码
            'sku' => $data['package_sku'],//包裹SKU
            );

            $totalPrice = 0;  //订单总物品价格
            $totalWeight = 0;   //订单重量
            //配货信息
            $orderItems = array();
            foreach($order->items as $j=>$vitem) {

                if(empty($data['sku'][$j])){
                    return self::getResult(1, '', '错误信息：商品SKU必填！');
                }
                if(empty($data['description'][$j])){
                    return self::getResult(1, '', '错误信息：英文报关名必填！');
                }
//                 if(empty($data['originCountry'][$j])){
//                     return self::getResult(1, '', '错误信息：商品产地必填！');
//                 }
                if(empty($data['unitValue'][$j])){
                    return self::getResult(1, '', '错误信息：商品单价必填！');
                }
                if(empty($data['itemCount'][$j])){
                    return self::getResult(1, '', '错误信息：商品数量必填！');
                }
                if(empty($data['weight'][$j])){
                    return self::getResult(1, '', '错误信息：商品重量必填！');
                }
                if(empty($data['nativeDescription'][$j]) || (!preg_match("/[\x7f-\xff]/", $data['nativeDescription'][$j]))){
                    return self::getResult(1, '', '错误信息：中文报关名必填，且必须含有中文！');
                }

                $orderItems[$j] = [
                    'itemNo' => empty($data['sku'][$j]) ? rand() : $data['sku'][$j],       //编号【随便填写一个值便可以】
                    'sku' => empty($data['sku'][$j]) ? '' : $data['sku'][$j],    //SKU
                    'description' => empty($data['description'][$j]) ? '' : $data['description'][$j],    //描述
                    'hsCode' => empty($data['hsCode'][$j]) ? '' : $data['hsCode'][$j],//HS 编码
                    'originCountry' => empty($data['originCountry'][$j]) ? 'CN' : $data['originCountry'][$j],   //产地
                    'unitValue' => empty($data['unitValue'][$j]) ? '' : $data['unitValue'][$j],//单价
                    'productURL' => empty($data['productURL'][$j]) ? '' : $data['productURL'][$j],//物品描述链接
                    'itemCount' => empty($data['itemCount'][$j]) ? '' : $data['itemCount'][$j],//数量
                    'weight' => empty($data['weight'][$j]) ? '' : ($data['weight'][$j])/1000,//重量
                    'nativeDescription' => empty($data['nativeDescription'][$j]) ? '' : $data['nativeDescription'][$j],//物品描述
                ];

                $totalPrice += $orderItems[$j]['unitValue'] * $orderItems[$j]['itemCount'];   //总价格
                $totalWeight += $orderItems[$j]['weight'] * $orderItems[$j]['itemCount'];  //总重量

            }
            $post_order['invoiceValue'] = $totalPrice;//商品价值（总价格）
            $post_order['weight'] = $totalWeight;//包裹总重量（kg）
            $post_order['orderItems'] = $orderItems;//配货信息

            $request_order[] = $post_order;

            $access_token = $account_api_params['access_token'];
            $secret_key = $account_api_params['secret_key'];

            $method = '/services/integration/shipper/orders';
            $request_body = json_encode($request_order);
            $request_headers = self::build_headers($access_token,$secret_key,'POST',self::$url.$method);

            $response = Helper_Curl::post(self::$url.$method,$request_body,$request_headers);


            \Yii::info('UBI,puid:'.$puid.',request,order_id:'.$order->order_id.' '.$request_body,"carrier_api");
            \Yii::info('UBI,puid:'.$puid.',response,order_id:'.$order->order_id.' '.print_r($response,true),"carrier_api");

//             \Yii::info(print_r($response,true),"file");   //先记下结果，方便查看和防止失去结果
            $responseData = json_decode($response, true);
//            print_r($responseData);exit;
            if(!empty($responseData[0]['status']) && $responseData[0]['status']== 'Succeeded'){

                //上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态（浩远直接返回物流号但不一定是真的，所以跳到第2步），跟踪号(选填),returnNo(选填)
                $r = CarrierApiHelper::orderSuccess( $order , $service , $customer_number , OdOrder::CARRIER_WAITING_GETCODE , $responseData[0]['trackingNo']);

                /** start 配货单合并地址单*/
                $print_param = array();
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_UBICarrierAPI';
                $print_param['access_token'] = $access_token;
                $print_param['secret_key'] = $secret_key;
                $print_param['tracking_number'] = $responseData[0]['trackingNo'];
                $print_param['order_source_order_id'] = $order->order_source_order_id;
                $print_param['carrier_params'] = $service->carrier_params;

                try{
                    CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
                }catch (\Exception $ex){
                }
                /** end 配货单合并地址单*/

                return  self::getResult(0,$r, "操作成功! UBI订单号：".$responseData[0]['orderId'] ."<br>物流跟踪号：".$responseData[0]['trackingNo']);
            }
            else{
                /** start 是否存在定义好的错误数组中。存在，则返回优化后message；否则，返回原始message*/

                $error_code = empty($responseData[0]['code'])?$responseData['errorCode']:$responseData[0]['code'];
                foreach(self::code2error() as $code=>$message) {
                    if($code == $error_code) {
                        $error_message = $message;
                        break;
                    } else{
                        $error_message = empty($responseData[0]['message'])?$responseData['message']:$responseData[0]['message'];
                    }
                }

                /** end 是否存在定义好的错误数组中。存在，则返回优化后message；否则，返回原始message*/
                return self::getResult(1, '', $error_code.'<br>错误信息：'.$error_message);
            }
        }
        catch (CarrierException $e){
            return self::getResult(1,'',"file:".$e->getFile()." line:".$e->getLine()." ".$e->msg());
        }
    }

    /**
    常出现的错误code对应的message
     */
    public static function code2error(){
    	//如果上传订单时选的渠道是UBI运输服务，并且提示服务编码非法。可能客户没有开通这个服务，需要向物流客服咨询
    	
        $error_arr = [
            /** start 返回错误code对应的message含糊不清，且文档没说明。自己新添加的*/
            100004=>'物流商服务器正忙，请联系相关技术人员',
            190007=>'产地国家代码必填，且正确填写该产地国家代码',
            190033=>'物品重量的总和必须低于包裹总重量',
            /** end 返回错误code对应的message含糊不清，且文档没说明。自己新添加的*/
            100001=>'数据格式错误',
            100002=>'认证失败',
            100003=>'包含非法字符',
            100005=>'渠道不合理',
            100006=>'发件人出货点错误',
            100007=>'没有可用的渠道',
            100008=>'没有配置渠道信息',
            100009=>'订单不存在',
            100010=>'订单不能删除',
            100011=>'您没有权限处理订单{0}',
            100012=>'您没有权限重发订单{0}',
            100013=>'服务编码非法',
            100014=>'跟踪号不唯一',
            100015=>'您无权限上传跟踪号',
            100016=>'订单未打印标签',
            100017=>'包含非法字符',

            110001=>'未提供订单客户端编号',
            110002=>'订单客户端编号过长',
            110003=>'订单客户端编号重复',
            110004=>'收货人姓名未填',
            110005=>'收货人姓名过长',
            110006=>'收货人电话过长',
            110007=>'收货人电邮过长',
            110008=>'电商平台代码过长',
            110009=>'收货人公司过长',
            110010=>'收货人电话未填',
            110011=>'收货人Email未填',
            110012=>'收货人电话格式不正确',
            110013=>'收货人Email格式不正确',
            110014=>'收货人姓名包含非法字符(只允许英文,俄文)',
            110016=>'收货人Tax ID未填',
            110017=>'收货人Tax ID过长',
            110018=>'收货人Tax ID非法',
            110019=>'税号不符合规则',
            110020=>'发货人电话长度超过 {0}',
            120001=>'收货人地址第一行必填',
            120002=>'收货人地址第一行过长',
            120003=>'收货人地址第二行过长',
            120004=>'收货人地址第三行过长',
            120005=>'收货人城市必填',
            120006=>'收货人城市过长',
            120007=>'收货人城市与邮编不符',
            120008=>'收货人地址州名错误',
            120009=>'收货人地址州名必填',
            120010=>'收货人州名与邮编不符',
            120011=>'收货人邮编必填',
            120012=>'收货人邮编错误',
            120013=>'收货人国家错误',
            120014=>'收货人地址第三行缺少',
            120015=>'收货人地址第三行长度超过 {0}',
            120016=>'收货人地址第三行无效',
            120017=>'收货人城市非法',
            120018=>'收货人地址第三行或城市无效',
            120019=>'收货人地址州过长',
            120020=>'收货人邮编过长',
            120021=>'国家长度超过 {0}',
            120022=>'国家不能为空',
            120023=>'国家与服务类型不匹配',
            120024=>'服务选项无效',
            120025=>'邮政编码必须是数字类型',
            120032=>'收货人国家编码必须为 {0} 个字符',
            120033=>'收货人城市邮编长度范围({0}~{1})个字符',
            120034=>'收货人州名必须为 {0} 个字符',
            120035=>'收货人邮编错误',
            120036=>'发货人地址 1 不能超过{0}个字符',
            120037=>'发货人地址 2 不能超过{0}个字符',
            120038=>'发货人地址 3 不能超过{0}个字符',
            120039=>'发货人城市不能超过{0}个字符',
            120040=>'发货人州名不能超过{0}个字符',
            120041=>'发货人邮编不能超过{0}个字符',
            120042=>'发货人国家不能超过{0}个字符',
            130001=>'商品描述必填',
            130002=>'商品描述过长',
            130003=>'包裏重量必填',
            130004=>'包裹总重量超过最大限制',//原message含糊不清('重量超过服务标准'),自己修改过此message
            130005=>'重量低于服务标准',
            130006=>'包裹总价值超过最大限制',//原message含糊不清('商品价值超过服务标准'),自己修改过此message
            130007=>'packingList过长',
            130008=>'币种错误',
            130009=>'商品价值必填',
            130010=>'商品价值过低',
            130011=>'长度超出服务标准{0} {1}',
            130012=>'宽度超出服务标准{0} {1}',
            130013=>'高度超出服务标准{0} {1}',
            130014=>'周长超出服务标准{0} {1}',
            130015=>'体积超出服务标准{0} {1}',
            130016=>'周长低于最小限制{0} {1}',
            130017=>'体积低于最小限制{0} {1}',
            130018=>'长＋宽＋高超出服务标准{0} {1}',
            130019=>'本地描述不能为空',
            130020=>'本地描述不能超过 {0} 个字符',
            130021=>'本地描述必须包含中文字符',
            130022=>'货物描述不能超过 {0} 个字符',
            140001=>'条码在系统不存在',
            140002=>'邮寄件未预报',
            140003=>'邮件超重',
            140004=>'请更换标签',
            140005=>'请扣件',
            150001=>'退件地址第一行必填',
            150002=>'退件地址第一行过长',
            150003=>'退件地址第二行过长',
            150004=>'退件地址第三行过长',
            150005=>'退件城市必填',
            150006=>'退件地址城市过长',
            150007=>'退件地址城市与邮编不符',
            150008=>'退件地址州/省错误',
            150009=>'退件地址州/省缺失',
            150010=>'退件地址州/省与邮编不符',
            150011=>'退件地址邮编缺失',
            150012=>'退件地址邮编不正确',
            150013=>'退件地址国家不正确',
            150101=>'退件人名字过长',

            190001=>'Item必填',
            190002=>'Item数量不合法',
            190003=>'ItemNo必填',
            190004=>'ItemSKU 必填',
            190005=>'Item描述必填',
            190006=>'Item单价必填',
            190008=>'ItemSKU 过长',
            190009=>'Item描述过长',
            190010=>'Item {0} 的 Origin 不能超过 {1} 个字符',
            190018=>'Item HSC0DE 过长',
            190019=>'Item total value 与 Order value 不一至乂',
            190020=>'Item 物品描述(中文)不能为空',
            190021=>'Item {0} 的物品描述(中文)不能超过 {1} 个字符',
            190022=>'Item 物品描述(中文)必须包含中文字符',
            190023=>'Item HSCode 必填',
            190024=>'Item {0} 的 HS Code 不能超过 {1} 个字符',
            190025=>'Item HS ode 必须是数字',
            190026=>'Item HSCode 不合法',
            190028=>'Item {0} 的物品描述(英)不能超过 {1} 个字符',
            190029=>'Item {0} 的物品数量不能超过 {1}',
            190030=>'Item重量必填',
            190031=>'Item {0} 的重量低于最小限制 {1} {2}',
            190032=>'物品原产地国家无效',
            190034=>'物品链接不能为空',
            190035=>'Item {0} 的产品链接长度不能超过 {1} 个字符',
            400001=>'多个piece的收件人名字不同',
            400002=>'多个piece的收件人地址不同',
            400003=>'多个piece的发件人地址不同',
            400004=>'第一个piece的订单编号不存在',

        ];
        return $error_arr;
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

            //设置好头部
            $account_api_params = $account->api_params;//获取到帐号中的认证参数

            $access_token = $account_api_params['access_token'];
            $secret_key = $account_api_params['secret_key'];

            $method = '/services/integration/shipper/manifests';
            $request_body = json_encode(array($order->order_source_order_id));
            $request_headers = self::build_headers($access_token,$secret_key,'POST',self::$url.$method);

            $response = Helper_Curl::post(self::$url.$method,$request_body,$request_headers);
            $responseData = json_decode($response , true);

            if (!empty($responseData[0]))
            {
                $shipped->tracking_number = $responseData[0];
                $shipped->save();
                $order->tracking_number = $shipped->tracking_number;
                $order->save();
                return self::getResult(0, '', '查询成功！物流跟踪号:'.$responseData[0]);
            }
            else {
                return self::getResult(1,'', '暂时没有跟踪号');
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
//             $order = $data[0]['order'];

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
            $normal_params = $service->carrier_params; ////获取打印方式

            $access_token = $account_api_params['access_token'];
            $secret_key = $account_api_params['secret_key'];

            $labelType=4;      
            $packinglist=false;
            if(!empty($service['carrier_params']['labelType']))
            	$labelType = $service['carrier_params']['labelType'];
            if(!empty($service['carrier_params']['packinglist']))
            	$packinglist = $service['carrier_params']['packinglist'];
            
            $orderIds=array();
            foreach ($data as $k=>$v) {
            	$order = $v['order'];
            	$orderIds[]=$order->order_source_order_id;
            	$order->print_carrier_operator = $puid;
            	$order->printtime = time();
            	$order->save();
            }
                      
            $request=array(
            		"orderIds"=>$orderIds,
            		"labelType"=>$labelType,
            		"packinglist"=>$packinglist,
            		"merged"=>false,
            );
            
            $method = '/services/shipper/labels';
            $request_body = json_encode($request);

            //test
//             $method = '/services/integration/shipper/labels';
//             $request_body = json_encode(array($order->order_source_order_id));
            
//             $access_token="pcls0smu_IjmZchTTOYar4";
//             $secret_key="@XXX@";
//             $method = '/services/integration/shipper/labels';
//             $request_body = json_encode(array("5abe15521010797f0c1e338b"));
//             self::$url="http://etower.walltechsystem.cn";
//             $labelType=4;
//             $packinglist=false;
//             if(!empty($service['carrier_params']['labelType']))
//             	$labelType = $service['carrier_params']['labelType'];
//             if(!empty($service['carrier_params']['packinglist']))
//             	$packinglist = $service['carrier_params']['packinglist'];
            
//             $request=array(
//             		"orderIds"=>array("5abe15521010797f0c1e338b","5acb01d0b31f3a623c8a108a"),
//             		"labelType"=>$labelType,
//             		"packinglist"=>$packinglist,
//             		"merged"=>false,
//             );

//             $method = '/services/shipper/labels';
//             $request_body = json_encode($request);
            //test-end


            $request_headers = self::build_headers($access_token,$secret_key,'POST',self::$url.$method,'application/json');

            \Yii::info('LB_UBI,puid:'.$puid.',request,order_id:'.$order->order_id.' '.$request_body.' headers:'.json_encode($request_headers),"carrier_api");
            $response = Helper_Curl::post(self::$url.$method,$request_body,$request_headers);
            
            
            \Yii::info('LB_UBI_doprint,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response,"carrier_api");
            
            
            if(empty($response))
            	return self::getResult(1, '', '获取打印地址失败e1');
            
            $response_arr=json_decode($response,true);

            if($response_arr['status']=="Failed" || $response_arr['status'] == "Failure"){
            	return self::getResult(1, '', $response_arr['errors'][0]['message']);
            }
            
            foreach ($response_arr['data'] as $keys=>$code){
            	$pdfurl=isset($response_arr['data'][$keys]['labelContent'])?$response_arr['data'][$keys]['labelContent']:"";
            	
            	if(empty($pdfurl))
            		return self::getResult(1, '', '获取打印地址失败e2');
            	
            	$pdfurl=base64_decode($pdfurl);
            	
            	$pdfurl = CarrierAPIHelper::savePDF($pdfurl,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
           	
            	$filePath[]=$pdfurl["filePath"];
            	
            }

            $filename=$puid.'_'.$order->order_source_order_id.'_'.$order->customer_number.'_merge_'.time().'.pdf';
           	$pdfmergeResult = PDFMergeHelper::PDFMerge(CarrierAPIHelper::createCarrierLabelDir().DIRECTORY_SEPARATOR.$filename,$filePath);
       	
           	if($pdfmergeResult['success'] == true){
           		$pdfmergeResult['filePath'] = str_replace(CarrierAPIHelper::getPdfPathString(), "", $pdfmergeResult['filePath']);
           	}else{
           		return self::getResult(1, '', $pdfmergeResult['message']);
           	}
           	

            if(!empty($pdfmergeResult['filePath'])) {
                return self::getResult(0, ['pdfUrl' => $pdfmergeResult['filePath']], '连接已生成,请点击并打印');//访问URL地址
            }
            else{
                return self::getResult(1, '', '打印失败！');
            }
        }
        catch (CarrierException $e)
        {
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

            if(empty($print_param['tracking_number'])){
                return ['error'=>1, 'msg'=>'缺少物流跟踪号,请检查订单是否上传', 'filePath'=>''];
            }

            $access_token = $print_param['access_token'];
            $secret_key = $print_param['secret_key'];
            
            $labelType=4;
            $packinglist=false;
            if(!empty($print_param['labelType']))
            	$labelType = $print_param['labelType'];
            if(!empty($print_param['packinglist']))
            	$packinglist = $print_param['packinglist'];
            
            $request=array(
            		"orderIds"=>array($print_param['order_source_order_id']),
            		"labelType"=>$labelType,
            		"packinglist"=>$packinglist,
            		"merged"=>false,
            );

            $method = '/services/shipper/labels';
            $request_body = json_encode($request);
//             $method = '/services/integration/shipper/labels';
//             $request_body = json_encode(array($print_param['order_source_order_id']));
            $request_headers = self::build_headers($access_token,$secret_key,'POST',self::$url.$method,'application/json');

            $response = Helper_Curl::post(self::$url.$method,$request_body,$request_headers);

            if(!empty($response)){
                $pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
                return $pdfPath;
            }
            else{
                return ['error'=>1, 'msg'=>'打印失败！'];
            }

        }catch (CarrierException $e){
            return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
        }
    }




    //获取运输方式
    public static function getCarrierShippingServiceStr($account){
        try{


            //该物流限制了不同客户显示不同的渠道，要获取到所有渠道，需要联系UBI客服申请账号，获取所有渠道
			// TODO carrier user account @XXX@
            $access_token = '@XXX@';
            $secret_key = '@XXX@';
             
            $method = '/services/shipper/service-catalog';
            

            $request_headers = self::build_headers($access_token,$secret_key,'GET',self::$url.$method,'application/json');
            $response = Helper_Curl::get(self::$url.$method,null,$request_headers);

            $channelArr=json_decode($response,true);

            if(empty($channelArr) || !is_array($channelArr) || json_last_error()!=false){
                return self::getResult(1,'','获取运输方式失败');
            }

            $channelStr="";
            foreach ($channelArr['data'] as $countryNum=>$countryItem){
                    $channelStr.=$countryItem['serviceCode'].":".$countryItem['serviceName'].";";
            }

            if(empty($channelStr)){
                return self::getResult(1, '', '');
            }else{
                return self::getResult(0, $channelStr, '');
            }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }



    /**
    每个接口方法提交数据需要的头部信息
     */
    public static function build_headers($access_token,$secret_key,$method, $path, $acceptType='application/json'){
        $walltech_date=date(DATE_RSS);
        $auth = $method."\n".$walltech_date."\n".$path;
        $hash=base64_encode(hash_hmac('sha1', $auth, $secret_key, true));
       
        //echo $walltech_date."<br>".$auth."<br>".$hash."<br>";
        return array(	'Content-Type: application/json',
            'Accept: '.$acceptType,
            'X-WallTech-Date: '.$walltech_date,
            'Authorization: WallTech '.$access_token.':'.$hash);
    }


    /*
     * 用来确定打印完成后 订单的下一步状态
     */
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }



}
