<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use \Jurosh\PDFMerge\PDFMerger;
use eagle\modules\util\helpers\PDFMergeHelper;

class LB_BANTOUYANCarrierAPI extends BaseCarrierAPI{
    
    public static $url = null;
    
    public static $debug = false;
    public function __construct(){
        
        if(isset(\Yii::$app->params["currentEnv"]) && \Yii::$app->params["currentEnv"]=='production' && !self::$debug){
            self::$url = "https://pengyan.anserx.com/pengyan";
        }else{
            self::$url = "http://api.anserx.com/pengyan";
        }
    }
    
    /**
     +----------------------------------------------------------
     * 申请订单号
     +----------------------------------------------------------
     **/
     public function getOrderNO($data){
         try{
             set_time_limit(0);
             ignore_user_abort(true);
             
             $order = $data['order'];  //object OdOrder
             $form_data = $data['data'];
             
             //重复发货 添加不同的标识码
             $extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
             $customer_number = $data['data']['customer_number'];
             
             if(isset($data['data']['extra_id'])){
                 if($extra_id == ''){
                     return self::getResult(1, '', '强制发货标识码，不能为空');
                 }
             }
             
             //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
             $checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);//第一个参数检验puid，第二个检验是否存在相关订单
             $puid = $checkResult['data']['puid'];
             
             $info = CarrierAPIHelper::getAllInfo($order);
             $account = $info['account'];
             $Service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
             //认证参数
             $account_api_params = $account->api_params;
             
             
             //检测数据完整性
             if(empty($order->consignee_address_line1) && empty($order->consignee_address_line2))
                 return ['error'=>1, 'data'=>'', 'msg'=>'地址不能为空'];
             
             if(empty($order->consignee))
                 return ['error'=>1, 'data'=>'', 'msg'=>'收件人姓名不能为空'];
             
             if(empty($order->consignee_postal_code))
                 return ['error'=>1, 'data'=>'', 'msg'=>'邮编不能为空'];
             
             if((!isset($order->consignee_phone) || $order->consignee_phone=='') && (!isset($order->consignee_mobile) || $order->consignee_mobile==''))
                 return ['error'=>1, 'data'=>'', 'msg'=>'联系方式不能为空'];
             
             if(empty($order->consignee_country_code))
                 return ['error'=>1, 'data'=>'', 'msg'=>'国家信息不能为空'];
             
             if(empty($form_data['taxPayMethod']))
                 return ['error'=>1, 'data'=>'', 'msg'=>'税收支付方式不能为空'];
             
            
             $order_message = array();
             $order_list = array();
             $order_endAddress = array();
             
             $weightAmount = 0;
             
             // 组织商品信息
             foreach ($order->items as $i=>$vitem){
                 
                 if(empty($form_data['DeclaredValue'][$i]))
                     return ['error'=>1, 'data'=>'', 'msg'=>'申报价值不能为空'];
                 
                 if(empty($form_data['DeclarePieces'][$i]))
                     return ['error'=>1, 'data'=>'', 'msg'=>'件数不能为空'];
                 
                 if(empty($form_data['DeclareWeight'][$i]))
                     return ['error'=>1, 'data'=>'', 'msg'=>'重量不能为空'];
                 
			     if(empty($form_data['goodsNameCn'][$i]))
			        return ['error'=>1, 'data'=>'', 'msg'=>'中文品名不能为空'];
			    
			     if(empty($form_data['goodsNameEn'][$i]))
			        return ['error'=>1, 'data'=>'', 'msg'=>'英文品名不能为空'];
			    
                 
//                  if(empty($form_data['Currency']))// 自定义订单参数
//                      $form_data['Currency'] = "USD";

			     if(empty($form_data['currency']))
                      $form_data['currency'] = "USD";
			     
			     
                 $weightAmount += $form_data['DeclareWeight'][$i] * $form_data['DeclarePieces'][$i];
                 $order_product_ItemList = array();
                 $order_product_ItemList['goodsNameCn'] = $form_data['goodsNameCn'][$i]; 
                 $order_product_ItemList['goodsNameEn'] = $form_data['goodsNameEn'][$i];
                 // 斑头雁那边要我们乘以数量在传值。
                 $order_product_ItemList['placeWeight'] = $form_data['DeclareWeight'][$i] * $form_data['DeclarePieces'][$i];// g
                 $order_product_ItemList['goodsValue'] = $form_data['DeclaredValue'][$i] * $form_data['DeclarePieces'][$i];
                 // $order_product_ItemList['curr'] = $form_data['Currency'];
                 $order_product_ItemList['curr'] = $form_data['currency'];
                 $order_product_ItemList['quantity'] = $form_data['DeclarePieces'][$i];
                 $order_list['goodsList'][$i] = $order_product_ItemList;
             }
             
             
             // 地址信息
             // 整理地址信息、电话信息
             $addressAndPhoneParams = array(
                 'address' => array(
                     'consignee_address_line1_limit' => 200,
                     'consignee_address_line2_limit' => 200,
                     'consignee_address_line3_limit' => 200,
                 ),
                 'consignee_district' => 1,
                 'consignee_county' => 1,
                 'consignee_company' => 1,
                 'consignee_phone_limit' => 100
             );
             
             //返回地址信息+电话信息
             $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
             
             $tmpconsignee_country_code = $order->consignee_country_code;
             if($order->consignee_country_code == 'UK'){
                 $tmpconsignee_country_code = 'GB';
             }
             
             $tmpConsigneeProvince = $order->consignee_province;
             if (empty($tmpConsigneeProvince)) {
                 if($order->consignee_country_code == 'FR')
                     $tmpConsigneeProvince = $order->consignee_city;
                 else if(!empty($order->consignee_city))
                     $tmpConsigneeProvince = $order->consignee_city;
                 else
                     return ['error'=>1, 'data'=>'', 'msg'=>'发货时收件人 州/省或城市不能为空'];
             }
             
//              "ctyCode": "US",
//              "city": "Mission",
//              "postalCode": "78574",
//              "cty": "UNITED STATES",
//              "addr": "504N Champion Ln",
//              "addrTwo": "地址 2",
//              "addrThree": "地址 3",
//              "prov": "Texas"
             $order_endAddress['ctyCode'] = $tmpconsignee_country_code;//国家简码 必填
             $order_endAddress['cty'] = $order->consignee_country;//国家名称 必填
             $order_endAddress['prov'] = $tmpConsigneeProvince;//省 必填
             $order_endAddress['city'] = $order->consignee_city;//城市 必填
             $order_endAddress['postalCode'] = $order->consignee_postal_code;//邮编 必填
             // 平台不一定返回，如何处理
             if(!empty($order->consignee_district))
                $order_endAddress['dist'] = $order->consignee_district;//地区名 文档必填，跟it确认不是必填
             $order_endAddress['addr'] = $addressAndPhone['address_line1'];//必填
             $order_endAddress['addrTwo'] = $addressAndPhone['address_line2'];//必填
             $order_endAddress['addrThree'] = $addressAndPhone['address_line3'];//必填
                     
             
//              "custOrderCode": "O1111100000",
//              "prodCode": "POOOOOOQ",
//              "platformType": "下单平台",
//              "startCustCom": "发件人公司",
//              "startCustCode": "2222221",
//              "startCustName": "Cody",
//              "startCustTel": "1566666666",
//              "startCustPhone": "15633333",
//              "endCustCom": "收货人公司",
//              "endCustCode": "Coooo1",
//              "endCustName": "meto",
//              "endCustTel": "1635555",
//              "endCustPhone": "1365555",
//              "goodsDesc": "descdd",
//              "weight": 1,
//              "length": 1,
//              "width": 1,
//              "height": 1,
//              "piece": 1,
//              "volumeWeight": 1,
//              "taxPayMethod": "1",
//              "remark": "备注",
             
             
             //总重，选填，如果sku上有单重可不填该项
             $order_list['weight'] = $weightAmount;
            
             
             $order_list['platformType'] = "XLB";// 下单平台   
             $order_list['custOrderCode'] = $customer_number; // 客户订单号
             $order_list['prodCode'] = $Service->shipping_method_code;//运输方式代码，必填
             // $order_list['goodsCatg'] = "PACKAGE";// 货物分类  必填  PACKAGE 为小 包,DOCUMENT 为 文件,PAK 为  pak 袋，默认为 PACKAGE
             $order_list['goodsCatg'] = empty($form_data['goodsCatg'])?"PACKAGE":$form_data['goodsCatg'];
             
             $order_list['endCustCom'] = $order->consignee_county;//收货公司
             $order_list['endCustName'] = $order->consignee;//收货人 必填 
             
             // 技术反馈说地址123和电话没有长度限制
             // $order_list['endCustTel'] = $addressAndPhone['phone1'];//收货人电话 ，$addressAndPhone['phone1']设置了100长度，可能已经包含了固话
             $order_list['endCustPhone'] = $addressAndPhone['phone1'];//收货人手机号 必填
             
             $order_list['taxPayMethod'] = $form_data['taxPayMethod'];//税收支付方式 必填 DDU,DDP 两种
             $order_list['remark'] = $form_data['remark'];
             $order_list['endAddress'] = $order_endAddress;
             
             
             $order_message[0] = $order_list;//可以有多个订单
             \Yii::info('LB_BANTOUYANCarrierAPI,request,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($order_message), "carrier_api");
             
             /**
[
    {
        "custOrderCode": "O1111100000",
        "prodCode": "POOOOOOQ",
        "platformType": "下单平台",
        "startCustCom": "发件人公司",
        "startCustCode": "2222221",
        "startCustName": "Cody",
        "startCustTel": "1566666666",
        "startCustPhone": "15633333",
        "endCustCom": "收货人公司",
        "endCustCode": "Coooo1",
        "endCustName": "meto",
        "endCustTel": "1635555",
        "endCustPhone": "1365555",
        "goodsDesc": "descdd",
        "weight": 1,
        "length": 1,
        "width": 1,
        "height": 1,
        "piece": 1,
        "volumeWeight": 1,
        "taxPayMethod": "1",
        "remark": "备注",
        "endAddress": {
            "ctyCode": "US",
            "city": "Mission",
            "postalCode": "78574",
            "cty": "UNITED STATES",
            "addr": "504N Champion Ln",
            "addrTwo": "地址 2",
            "addrThree": "地址 3",
            "prov": "Texas"
        },
        "startAddress": {
            "ctyCode": "C0201",
            "cty": "bies2fs",
            "provCode": "heiio",
            "houseNum": "H0002"
        },
        "goodsList": [
            {
                "quantity": 3,
                "goodsNameCn": "商品名",
                "goodsNameEn": "Girls Generation",
                "placeWeight": 10,
                "goodsValue": 30,
                "customsCode": "",
                "curr": "USD"
            }
        ]
    }
]
              **/
             
             $nowTime = time();
             $authParam = [];
             // 账号
             $authParam['custCode'] = $account_api_params['custCode'];
             
             // apiKey 为 anserx 提供的密钥
             $authParam['apiKey'] = $account_api_params['apiKey'];
             
             // t  时间戳 long
             $authParam['t'] = $nowTime;
             
             $authParam['sign'] = $this->sign($authParam);
             
              
             $url = self::$url.'/osc-openapi/api/order/batchCreate?';
             $getParams = [];
             foreach ($authParam as $name => $value) {
                 $getParams[] = rawurlencode($name) . '=' . rawurlencode($value);
             }
             $url .= implode('&', $getParams);
             
             \Yii::info("LB_BANTOUYANCarrierAPI getOrder params:".json_encode($order_message), "carrier_api");
             
             $header = array();
             $header[] = 'Content-type: application/json;charset=utf-8';
             $header[] = 'Accept: application/json';
             
             $order_respond = Helper_Curl::post2($url, json_encode($order_message), $header);
             
             \Yii::info("LB_BANTOUYANCarrierAPI getOrder result:".$order_respond.PHP_EOL."post info:".print_r(Helper_Curl::$last_post_info, true), "carrier_api");
             
//              code  响应码  Number  是
//              data  响应体数据  Object  是
//              anxOrderCode  内部订单编号  String  是
//              custOrderCode  客户订但编号  String  否
//              message  消息提示  String  是
             /**
 {
    "code": 200,
    "data": [
        {
            "anxOrderCode": "O0001",
            "custOrderCode": "CUST001",
            "waybillCode": "A000001",
            "message": "成功",
            "success": true
        }
    ],
    "exend": {},
    "msg": "成功",
    "success": false
}
              */
             
             
             // anxOrderCodes系统订单号,custOrderCode客户订单编号,waybillCode面单号/物流单号，都需要保存，打印接口需要输入waybillCode，重新获取订单信息需要anxOrderCodes
             $result = json_decode($order_respond,true);
             if(!empty($result['success']) && !empty($result['code']) && $result['code'] == 200){//验证POST数据是否成功
                 foreach ($result['data'] as $res){
                     if($res['success'] == true){
                         //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
                         $track_no = "";// 尾程单号后面获取订单接口才能获取到，即跟踪号需要获取跟踪号接口获取
                         $returnNo = [];
                         $returnNo['waybillCode'] = $res['waybillCode'];
                         $returnNo['anxOrderCode'] = $res['anxOrderCode'];
                         $track_no = $res['waybillCode'];// waybillCode是面单号，不是尾程号（我们要的跟踪号），但斑头雁这边强烈要求加进去，后面再获取跟踪号覆盖
                         $r = CarrierAPIHelper::orderSuccess($order, $Service, $res['custOrderCode'], OdOrder::CARRIER_WAITING_GETCODE, $track_no, $returnNo);
                         return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$res['custOrderCode']);
                     }else{
                         if(!empty($res['message'])){
                             throw new CarrierException($res['message']);
                         }else {
                             throw new CarrierException("上传订单失败");
                         }
                         
                     }
                 }
             }else{
                 if(!empty($result['msg'])){
                     throw new CarrierException($result['msg']);
                 }elseif(!empty($result['message'])){
                     throw new CarrierException($result['message']);
                 }else{
                     throw new CarrierException("上传数据失败");
                 }
             }
             
//              return $result;
         }catch(CarrierException $e){
             return self::getResult(1,'',$e->msg());
         }
         
     }
     
    /**
     +----------------------------------------------------------
     * 取消跟踪号
     +----------------------------------------------------------
    **/
     public function cancelOrderNO($data){
         return BaseCarrierAPI::getResult(1, '', '系统不支持取消物流单。');
     }
     
    /**
     +----------------------------------------------------------
     * 交运
     +----------------------------------------------------------
    **/
     public function doDispatch($data){
         return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');
     }
     
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
             $account_api_params = $account->api_params;
              
             $track_no=array();
             $order_id=array();
             $order_id[] = $shipped->return_no['anxOrderCode'];
             $track_no['anxOrderCodes'] = $order_id;
             
             $nowTime = time();
             $authParam = [];
             // 账号
             $authParam['custCode'] = $account_api_params['custCode'];
              
             // apiKey 为 anserx 提供的密钥
             $authParam['apiKey'] = $account_api_params['apiKey'];
              
             // t  时间戳 long
             $authParam['t'] = $nowTime;
              
             $authParam['sign'] = $this->sign($authParam);
              
             
             $url = self::$url.'/osc-openapi/api/order/queryByAnxOrderCodes?';
             $getParams = [];
             foreach ($authParam as $name => $value) {
                 $getParams[] = rawurlencode($name) . '=' . rawurlencode($value);
             }
             $url .= implode('&', $getParams);
             
             $header = array();
             $header[] = 'Content-type: application/json;charset=utf-8';
             $header[] = 'Accept: application/json';
             
             $track_no_result = Helper_Curl::post2($url, json_encode($track_no), $header);
             
             \Yii::info("LB_BANTOUYANCarrierAPI getTrackingNO result:".$track_no_result.PHP_EOL."post info:".
                     print_r(Helper_Curl::$last_post_info, true), "carrier_api");
              
             
             $result = json_decode($track_no_result, true);
             if(!empty($result['success']) && !empty($result['code']) && $result['code'] == 200){//验证POST数据是否成功
                 $track_no = null;
                 if(!empty($result['data']['list'])){
                     $trackOrder = $result['data']['list'][0]['orderInfo'];
                     $track_no = empty($trackOrder['sendCode'])?null:$trackOrder['sendCode'];
                 }
                 
                 if(!empty($track_no)){
                     $is_new = false;
                 
                     if($shipped->tracking_number != $track_no){
                         $is_new = true;
                         \eagle\modules\util\helpers\OperationLogHelper::log('order',$order->order_id,'获取跟踪号', '旧的跟踪号:'.$shipped->tracking_number.'.新的跟踪号:'.$track_no, '斑头雁');
                     }
                     
                     $old_track_no = $shipped->tracking_number;
                     $shipped->tracking_number = $track_no;
                     $shipped->save();
                     $order->tracking_number = $shipped->tracking_number;
                     $order->save();
                     
                     return BaseCarrierAPI::getResult(0, '', '获取物流号成功！物流号：'.(($is_new == true) ? '旧的跟踪号:'.$old_track_no.'.新的跟踪号:'.$track_no : $track_no));
                     
                 } else {//没有跟踪号
                    throw new CarrierException('暂时没有跟踪号');
                 }
   
             }else{
                 if(!empty($result['msg'])){
                     throw new CarrierException($result['msg']);
                 }elseif(!empty($result['message'])){
                     throw new CarrierException($result['message']);
                 }else{
                     throw new CarrierException("上传数据失败");
                 }      
             }

              
         }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
     }
    
    /**
     +----------------------------------------------------------
     * 打单
     +----------------------------------------------------------
    **/
     public function doPrint($data){
         try{
             $pdf = new PDFMerger();
             
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
//              print_r($info);

             $params = $service->carrier_params;
             $format = !empty($params['print_format'])?$params['print_format']:"LABEL_A4";
             
             $account_api_params = $account->api_params;
             
             $nowTime = time();
             $authParam = [];
             // 账号
             $authParam['custCode'] = $account_api_params['custCode'];
             
             // apiKey 为 anserx 提供的密钥
             $authParam['apiKey'] = $account_api_params['apiKey'];
             
             // t  时间戳 long
             $authParam['t'] = $nowTime;
             
             $authParam['sign'] = $this->sign($authParam);
             
             // getPrintPdf是直接返回pdf链接
             $url = self::$url.'/aopsc-openapi/api/platform/order/getPrintPdfByte?';
             $getParams = [];
             foreach ($authParam as $name => $value) {
                 $getParams[] = rawurlencode($name) . '=' . rawurlencode($value);
             }
             $url .= implode('&', $getParams);
             
             $header = array();
             $header[] = 'Content-type: application/json;charset=utf-8';
             $header[] = 'Accept: application/json';
             
//              $demoWaybillCodes = ["W675847960814161921", "W675845524804341763"];// 测试单号
             $index=0;
             foreach ($data as $detail_data){
                 $order = $detail_data['order'];
                 $checkResult = CarrierAPIHelper::validate(0,1,$order);
                 $shipped = $checkResult['data']['shipped'];
//                  waybillCodes  物流单号
//                  format  面单格式 LABEL_A4,LABEL_10_10,LABEL_10_15
//                  输入格式
//                  {"format":" LABEL_10_10","waybillCodes":["A000001","A000002"]}
                 
                 $apiParam['waybillCodes'] = [$shipped->return_no['waybillCode']];
//                  $apiParam['waybillCodes'] = [$demoWaybillCodes[$index++]];
                 
                 $apiParam['format'] = $format;
                 
                 $print_result = Helper_Curl::post2($url, json_encode($apiParam), $header);
                 
                 \Yii::info('LB_BANTOUYANCarrierAPI print result,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.
                         PHP_EOL.'param:'.json_encode($apiParam).PHP_EOL.'result:'.$print_result,"carrier_api");
                 
                 $result = json_decode($print_result,true);
                 if(!empty($result['success']) && !empty($result['code']) && $result['code'] == 200){//验证POST数据是否成功
                     $retData = json_decode($result['data'], true);
                     $print_pdf_result = base64_decode($retData[0]['byteFile']);
                     
                     if(strlen($print_pdf_result)>1000){
                         $pdfurl = CarrierAPIHelper::savePDF($print_pdf_result,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
//                          $tmpPath[] = $pdfurl['filePath'];
                         $pdf->addPDF($pdfurl['filePath'],'all');//合并相同运输运输方式的pdf流
                         $order->print_carrier_operator = $puid;
                         $order->printtime = time();
                         $order->carrier_error = '';
                         $order->save();
                     }else{
                         if(strlen($print_result)>1000){
                             $pdfurl = CarrierAPIHelper::savePDF($print_result,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
//                              $tmpPath[] = $pdfurl['filePath'];
                             $pdf->addPDF($pdfurl['filePath'],'all');//合并相同运输运输方式的pdf流
                             $order->print_carrier_operator = $puid;
                             $order->printtime = time();
                             $order->carrier_error = '';
                             $order->save();
                         }else{
                             return self::getResult(1, '', "打印失败，请联系技术人员");
                         }
                     }
                     
                 }else{
                     \Yii::info('LB_BANTOUYANCarrierAPI print error,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.
                             PHP_EOL.'param:'.json_encode($apiParam).PHP_EOL.'result:'.$print_result, "carrier_api");
                      
                     if(!empty($result['msg'])){
                         throw new CarrierException($result['msg']);
                     }elseif(!empty($result['message'])){
                         throw new CarrierException($result['message']);
                     }else{
                         throw new CarrierException("上传数据失败");
                     }
                 }
             }
             
//              $pdfmergeResult = PDFMergeHelper::PDFMerge($pdfUrl['filePath'] , $tmpPath);
//              if($pdfmergeResult['success'] == true){
//                  return ['error'=>0, 'data'=>['pdfUrl'=>$pdfmergeResult['pdfUrl']], 'msg'=>'连接已生成,请点击并打印'];
//              }else{
//                  return ['error'=>1, 'data'=>'', 'msg'=>$pdfmergeResult['message']];
//              }
             
             isset($pdfurl)?$pdf->merge('file', $pdfurl['filePath']):$pdfurl['filePath']='';//需要物理地址
             return self::getResult(0,['pdfUrl'=>$pdfurl['pdfUrl']],'连接已生成,请点击并打印');//访问URL地址

         }catch(CarrierException $e){
             return self::getResult(1,'',$e->msg());
         }
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
	        $puid = \Yii::$app->user->identity->getParentUid();
	         
	        $nowTime = time();
            // 账号
	        $authParam = [];
            $authParam['custCode'] = $data['custCode'];
            $authParam['apiKey'] = $data['apiKey'];
            $authParam['t'] = $nowTime;
            
            $authParam['sign'] = $this->sign($authParam);
            
            $header = array();
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json';
             
            $url = self::$url.'/aopsc-openapi/api/platform/order/queryProduct?';
            $getParams = [];
            foreach ($authParam as $name => $value) {
                $getParams[] = rawurlencode($name) . '=' . rawurlencode($value);
            }
            $url .= implode('&', $getParams);
            
            $serviceResultStr = Helper_Curl::get($url, array(), $header);
            \Yii::info('LB_BANTOUYANCarrierAPI getVerifyCarrierAccountInformation result:'.$serviceResultStr,"carrier_api");
            
            $serviceResult = json_decode($serviceResultStr, true);
             
            if(!empty($serviceResult['success']) && !empty($serviceResult['code']) && $serviceResult['code'] == 200){//验证POST数据是否成功
                $result['error'] = 0;
            }
            
	     }catch(CarrierException $e){
	     }
	 
	     return $result;
	 }
	 
	 //获取运输方式
    public function getCarrierShippingServiceStr($account){
        try{
            $nowTime = time();
            $account_api_params = $account->api_params;
             
            $authParam = [];
//              // 账号
             $authParam['custCode'] = $account_api_params['custCode'];
             // apiKey 为 anserx 提供的密钥
             $authParam['apiKey'] = $account_api_params['apiKey'];
             
            // t  时间戳 long
            $authParam['t'] = $nowTime;
            $authParam['sign'] = $this->sign($authParam);
            
            $url = self::$url.'/aopsc-openapi/api/platform/order/queryProduct?';
            $getParams = [];
            foreach ($authParam as $name => $value) {
                $getParams[] = rawurlencode($name) . '=' . rawurlencode($value);
            }
            $url .= implode('&', $getParams);
            
            $header = array();
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json';
            
            $result = Helper_Curl::get($url, array(), $header);
//             echo $url.PHP_EOL.print_r($result, true).PHP_EOL;
//             exit();
//             $result = '{"code":200,"msg":"操作成功","description":null,"data":[{"prodCode":"ANSERX_HYCDGH","prodName":"ANSERX_荷邮纯电挂号"},{"prodCode":"ANSERX_SF_GTI_NL_3241","prodName":"安小包_荷兰邮政挂号"},{"prodCode":"AXB_SF_TH","prodName":"安小包-顺丰特惠"},{"prodCode":"AXB_USA_Anserx","prodName":"安小包-美国小包专线（普内）"},{"prodCode":"AXB_USA_FT2421","prodName":"中美专线（普货）"},{"prodCode":"AXB_US_Anserx_ND","prodName":"安小包-美国专线（带电）"},{"prodCode":"A_US","prodName":"美国专线"},{"prodCode":"BS-ZX","prodName":"E特快-白石物流"},{"prodCode":"CSyz","prodName":"CS邮政"},{"prodCode":"MangoZh","prodName":"芒果账号"},{"prodCode":"MangoZx","prodName":"芒果专线"},{"prodCode":"TEST","prodName":"测试-（邮政）-互联通"},{"prodCode":"TEST-D","prodName":"测试产品D"},{"prodCode":"TEST-FT-001-ZX","prodName":"TEST-FT-001-ZX"},{"prodCode":"TEST-H","prodName":"测试-测试产品H"},{"prodCode":"TEST-XBCP","prodName":"小北产品"},{"prodCode":"TEST-XZXJ","prodName":"小江专线"},{"prodCode":"test-zr-2","prodName":"test-zr-2"},{"prodCode":"TEST1","prodName":"测试-（邮政）-华磊"},{"prodCode":"TEST10","prodName":"TEST10"},{"prodCode":"TEST11","prodName":"TEST11"},{"prodCode":"TEST12","prodName":"TEST12"},{"prodCode":"TEST2","prodName":"测试-E特快"},{"prodCode":"TEST4","prodName":"TEST4"},{"prodCode":"TEST5","prodName":"TEST5"},{"prodCode":"TEST6","prodName":"TEST6"},{"prodCode":"TEST7","prodName":"TEST7"},{"prodCode":"TEST8","prodName":"TEST8"},{"prodCode":"TEST9","prodName":"TEST9"},{"prodCode":"T_ZH","prodName":"业务账号产品"},{"prodCode":"T_ZX","prodName":"业务专线产品"}],"speedErrorCode":null,"exend":{},"success":true}';
            
            $resultObj = json_decode($result);
            $channelStr = "";
            if(!empty($resultObj->success) && !empty($resultObj->code) && $resultObj->code == 200){//验证POST数据是否成功
                $serviceData = $resultObj->data;
                foreach ($serviceData as $service){
                    $channelStr .= "{$service->prodCode}:{$service->prodName};";
                }
            }
            
            if(empty($channelStr)){
                return self::getResult(1, '', '');
            }else{
                return self::getResult(0, $channelStr, '');
            }
	    }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	 }
	 
	 // sign 签名 sign = MD5(custCode+apiKey+t)
	 private function sign($param){
	     return md5($param['custCode'].$param['apiKey'].$param['t']);
	 }
	 
	 
	 
	 
	 
}
