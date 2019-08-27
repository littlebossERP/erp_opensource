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
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use Qiniu\json_decode;

//try{
//    include '../components/PDFMerger/PDFMerger.php';
//}catch(\Exception $e){
//}

/**
+------------------------------------------------------------------------------
 * 飞特接口业务逻辑类
+------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/4pxCarrierAPI
 * @subpackage  Exception
 * @author		qfl
 * @version		1.0
+------------------------------------------------------------------------------
 */
class LB_FEITECarrierAPI extends BaseCarrierAPI
{

    public $soapClient = null; // SoapClient实例
    public $wsdl = null; // 物流接口

    public $Token = null;     //令牌[需要联系飞特技术提供平台标识]
    public $UAccount = null;  //物流账号
    public $Password = null;  //密码（MD5加密大写32位）
    
	// TODO carrier dev account @XXX@
    public $access_token="@XXX@";

    function __construct()
    {
//        $login = 10002;   //飞特测试客户ID
//        $Sign = '10002##21218cca77804d2ba1922c33e0151105##1'   //服务接入标识
//
//        $password = 888888;
//        $token = '10002##'.md5($password).'##1';
//        print_r($token);

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
            $OrderUp['Token'] = '93B4DD39-A98D-35D5-AFC6-78F7F09B99D1';//小老板所以客户统一用这个token
            $OrderUp['UAccount'] = isset($account_api_params['UAccount']) ? $account_api_params['UAccount'] : '';
            $OrderUp['Password'] = isset($account_api_params['Password'])?$account_api_params['Password']:'';

            /**start 客户打印备注（只有这个能显示在打印面单里）*/
            $orderPrintRemark = '';
            if ($order->desc == $data['printRemark']) {
                $orderPrintRemark = $order->desc;
            }
            else {
                $orderPrintRemark  =$data['printRemark'];
            }
            /**end 客户打印备注（只有这个能显示在打印面单里）*/


            /**start 传给物流商的销售平台标识 SalesPlatformFlag 字段*/
            $SalesPlatformFlag = '';
            $order_source = $order->order_source;
            if($order_source =='ebay'){
                $SalesPlatformFlag = 1;
            }
            elseif($order_source =='amazon'){
                $SalesPlatformFlag = 2;
            }
            elseif($order_source =='aliexpress'){
                $SalesPlatformFlag = 3;
            }
            elseif($order_source =='wish'){
                $SalesPlatformFlag = 4;
            }
            else{
                $SalesPlatformFlag = 0;
            }
            /**end 传给物流商的销售平台标识 SalesPlatformFlag 字段*/





            $addressAndPhoneParams = array(
                'address' => array(
                    'consignee_address_line1_limit' => 50,
                    'consignee_address_line2_limit' => 50,
                    'consignee_address_line3_limit' => 100,
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


            //订单基础参数
            $OrderInfo = array(
                'Address1'             =>$tmpAddress,  //地址1
                'Address2'             =>$addressAndPhone['address_line2'],//地址2
                'ApiOrderId'           =>$customer_number,  //第三方平台订单号(平台唯一)【客户订单号】
                //'BuyerId'            =>'',  //买家id
                'City'                 =>$order->consignee_city,  //城市
                'CiId'                 =>$order->consignee_country_code == 'UK' ? 'GB' : $order->consignee_country_code,  //国家简码
                'County'               =>$order->consignee_province,  //州/省
                //'CCode'              =>'',  //货币代码
                'Email'                =>$order->consignee_email,  //收货人Email
                'OnlineShippingType'   =>empty($data['OnlineShippingType'])?'':$data['OnlineShippingType'],  //线上货运方式名称(走线上渠道时必填)
                'OnlineShopName'       =>empty($data['OnlineShopName'])?'':$data['OnlineShopName'],  //线上店铺名(走线上渠道时必填)
                'PackType'             =>empty($data['PackType'])?3:$data['PackType'],  //包装类型（0：无，1：信封，2：文件，3：包裹）(默认包裹)
                'Phone'                =>$addressAndPhone['phone1'],  //收件人电话或手机【获取跟踪号时，某些渠道会要求有电话数据，有电话数据请尽量上传】
                'PtId'                 =>$service->shipping_method_code,  //货运方式(邮递方式简码)
                'ReceiverName'         =>$order->consignee,  //收件人姓名
                'Remark'               =>$orderPrintRemark,  //备注【能出现在面单中】
                'SalesPlatformFlag'    =>$SalesPlatformFlag,  //销售平台标识[0=默认(不分);1=ebay;2=amazon(亚马逊);3=aliexpress(速卖通);4=wish](ebay,amazon,aliexpress,wish平台订单必填，其余可不填)
                'SyncPlatformFlag'     =>'scb.logistics.littleboss',  //订单同步平台标识(一般指第三方平台标识，格式类似：scb.logistics.flyt，具体可询问飞特技术人员)
                //'TraceId'            =>'',  //跟踪号
                //'UAccount'           =>'',  //物流账号
                'Zip'                  =>$order->consignee_postal_code,  //邮编
                //'TaxNumber'            =>'',  //税号
                'MultiPackageQuantity' =>empty($data['MultiPackageQuantity'])?1:$data['MultiPackageQuantity'],  //一票多件件数(FBA必填),默认1
                'ExtendData1'          =>'',  //备用扩展数据
                'ExtendData2'          =>'',  //备用扩展数据
                'ExtendData3'          =>'',  //备用扩展数据
                'ExtendData4'          =>'',  //备用扩展数据
                'ExtendData5'          =>'',  //备用扩展数据
            );

            //产品明细
            $OrderDetail = array();
            foreach($order->items as $j=>$vitem)
            {
                $OrderDetail[$j] = [

                //'Color'                    =>'',  //颜色
                //'ColorCode'                =>'',  //颜色代码：#000000
                //'Freight'                  =>'',  //运费
                'ItemId'                   =>$data['ProductItemId'][$j], //物品id[ebay等销售平台订单必填]
                'ItemName'                 =>$data['ItemEName'][$j],  //物品英文名称
                'ItemTransactionId'        =>$data['ItemTransactionId'][$j],  //物品交易号[ebay订单必填]
                'OriginalPlatformOrderId'  =>$customer_number,  //销售平台订单号[销售平台订单必填]
                'Quantities'               =>empty($data['DeclarePieces'][$j])?$vitem->quantity:$data['DeclarePieces'][$j],//产品数量
                'Price'                    =>$vitem->price,//产品平台销售单价
                //'Remark'                 =>'',//备注
                //'SalePrice'              =>'',  //销售价格
                'Sku'                    =>$data['Sku'][$j],  //货号(SKU)
                ];
            }
            $OrderInfo['OrderDetailList'] = $OrderDetail;


            //产品报关明细
            $HaikwanDetail = array();
            foreach($order->items as $j=>$vitem)
            {
                $declaration= json_decode($vitem['declaration'],true);
                $hwcode= isset( $declaration['code'] )?$declaration['code']:'';
                $HaikwanDetail[$j] = [
                    'HwCode'=>$hwcode,
                    //'HwCode'            =>'',//海关编码
                    'ItemCnName'          =>$data['Name'][$j],//物品中文名称
                    'ItemEnName'          =>$data['EName'][$j],//物品英文名称
                    'ItemId'              =>$data['ProductItemId'][$j] ,//物品ID（平台物品标示，平台必填）
                    'ProducingArea'       =>empty($data['ProducingArea'][$j])?'CN':$data['ProducingArea'][$j] ,//原产地（默认值：CN）
                    'Quantities'          =>empty($data['DeclarePieces'][$j])?$vitem->quantity:$data['DeclarePieces'][$j],//物品数量
                    //'RealPrice'         =>empty($data['RealPrice'][$j])?0:$data['RealPrice'][$j],//真实价格
                    //'Remark'            =>$vitem->remark,//备注
                    'Sku'                 =>$data['Sku'][$j],//产品编号(SKU)
                    'UnitPrice'           =>$data['DeclaredValue'][$j],//产品报关单价
                    'Weight'              =>round(($data['weight'][$j])/1000,2),//重量(kg)
                    'BtId'                =>empty($data['BtId'][$j])?'':$data['BtId'][$j],//电池类型(带电池货物必填，非电池类可否为空)
                    'FbaNumber'           =>empty($data['FbaNumber'][$j])?'':$data['FbaNumber'][$j],//亚马逊FBA分箱号(邮递方式为fba时,该字段为[必填])
                    'CCode'               =>empty($data['CurrencyType'][$j])?'USD':$data['CurrencyType'][$j],//货币代码(默认为USD美元)
                    'Model'               =>empty($data['Model'][$j])?'':$data['Model'][$j],//型号(FBA必填)
                    'Brand'               =>empty($data['Brand'][$j])?'':$data['Brand'][$j],//品牌(FBA必填)
                    'Purpose'             =>empty($data['Purpose'][$j])?'':$data['Purpose'][$j],//用途(FBA必填)
                    'Material'            =>empty($data['Material'][$j])?'':$data['Material'][$j],//材质(FBA必填)
                ];
            }
            $OrderInfo['HaikwanDetialList'] = $HaikwanDetail;


            $OrderUp['OrderList'] = array($OrderInfo);
            $OrderUpJson = json_encode($OrderUp);

            $post_head = array();
            $post_head[] = "Content-Type: application/json;charset=UTF-8";
            $response = Helper_Curl::post('http://exorderwebapi.flytcloud.com/api/OrderSyn/ErpUploadOrder',$OrderUpJson,$post_head);

            \Yii::info(print_r($response,true),"file");

            $responseArr = json_decode($response,true);

            //上传成功
            if($responseArr['Success'] ==1 || $responseArr['Success'] ==true){

                $print_param = array();
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_FEITECarrierAPI';
                $print_param['order_number'] = $responseArr['ErpSuccessOrders'][0]['OrderId'];
                $print_param['carrier_params'] = $service->carrier_params;

                try{
                    CarrierAPIHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $responseArr['ErpSuccessOrders'][0]['OrderId'], $print_param);
                }catch (\Exception $ex){
                }


                //有跟踪号返回
                if(!empty($responseArr['ErpSuccessOrders'][0]['TraceId'])){
                    $r = CarrierApiHelper::orderSuccess($order,$service,$responseArr['ErpSuccessOrders'][0]['OrderId'],OdOrder::CARRIER_WAITING_PRINT,$responseArr['ErpSuccessOrders'][0]['TraceId']);
                    return  self::getResult(0,$r, "上传成功！".$responseArr['ErpSuccessOrders'][0]['Remark']."<br/>飞特订单号：".$responseArr['ErpSuccessOrders'][0]['OrderId']."<br/>物流跟踪号：".$responseArr['ErpSuccessOrders'][0]['TraceId']);
                }
                //暂时没跟踪号返回
                else{
                    $r = CarrierApiHelper::orderSuccess($order,$service,$responseArr['ErpSuccessOrders'][0]['OrderId'],OdOrder::CARRIER_WAITING_GETCODE);
                    return  self::getResult(0,$r, "上传成功！".$responseArr['ErpSuccessOrders'][0]['Remark']."<br/>飞特订单号：".$responseArr['ErpSuccessOrders'][0]['OrderId']);
                }
            }
            //上传失败
            else{
                $failMessage = empty($responseArr['ErpFailOrders'])?'':$responseArr['ErpFailOrders'][0]['Remark'];
                return self::getResult(1, '', "上传结果:" .$responseArr['Remark'].$failMessage);
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

            //物流商系统订单号作为了客户参考号
            $carrierOrderId = $order->customer_number;

            $TrackingNOUp['Token'] = '93B4DD39-A98D-35D5-AFC6-78F7F09B99D1';
            $TrackingNOUp['OrderIds'] = array($carrierOrderId);

            $TrackingNOUpJson = json_encode($TrackingNOUp);

            $post_head = array();
            $post_head[] = "Content-Type: application/json;charset=UTF-8";
            $response = Helper_Curl::post('http://exorderwebapi.flytcloud.com/api/OrderSyn/ErpQueryTraceId',$TrackingNOUpJson,$post_head);

            $responseArr = json_decode($response,true);

            //获取成功
            if($responseArr['Success'] == 1 || $responseArr['Success'] == true){
                if(!empty($responseArr['ErpTraceIds'][0]['TraceId'])){
                    $shipped->tracking_number = $responseArr['ErpTraceIds'][0]['TraceId'];
                    $shipped->save();
                    $order->tracking_number = $shipped->tracking_number;
                    $order->save();
                    return self::getResult(0, '', '查询结果：'.$responseArr['ErpTraceIds'][0]['Remark'].'<br/>物流跟踪号：'.$responseArr['ErpTraceIds'][0]['TraceId']);
                }else{
                    $order->save();
                    return self::getResult(0, '', '  查询结果：' . $responseArr['ErpTraceIds'][0]['Remark']);
                }
            }
            //获取失败
            else{
                $failMessage = empty($responseArr['ErpTraceIds'][0]['Remark'])?'':$responseArr['ErpTraceIds'][0]['Remark'];
                return self::getResult(1, '', '查询失败：'.$responseArr['Remark'].$failMessage);
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

			
            //面单授权token 暂时写死 30天过期
//             $access_token = $this->access_token;
            $SysCarrierAddiInfo=CarrierAPIHelper::getSysCarrierAddiInfo('lb_feite',array("access_token"))["date"]["access_token"];
            $access_token = empty($SysCarrierAddiInfo)?$this->access_token:$SysCarrierAddiInfo;

            /** end   token过期之后需要重新获取，所以现在做法是每次打印面单前都先获取token一次*/

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
                $printRequestData['OrderIdlst'][]= $order_object->customer_number;// 注意这里文档写的参数是 refrence_no 要改成reference_no 接口才调用成功
            }

            $printRequestData['Format'] = 0;    //面单格式（0：标签纸10x10，1：A4纸），api文档目前统一为0
            $printRequestData['IsPrintSkuInfo'] = true;
            $printRequestData['IsShowSelfCode'] = true;

            $printReqBody = json_encode($printRequestData,true);
            $printReqUrl = 'http://exapi.flytcloud.com/api/label/LabelProvider/GetLabelBatchExt';
            $printHead[] = "Content-Type: application/json;charset=UTF-8";
            $printHead[] = "token: ".$access_token;

            $printRes = Helper_Curl::post($printReqUrl,$printReqBody,$printHead);
            $printResArr = json_decode($printRes,true);

            if($printResArr['Status'] == 1){
                $pdfurl = CarrierAPIHelper::savePDF(base64_decode($printResArr['Data']['Label']),$puid,$order_object->order_source_order_id.'_'.$account->carrier_code,0);
                return self::getResult(0,['pdfUrl'=>$pdfurl['pdfUrl']],'连接已生成,请点击并打印');//访问URL地址
            }
            else{
                return self::getResult(1,'','失败原因：'.$printResArr['ErrMsg']);
            }
        }catch (CarrierException $e) {
            return self::getResult(1,'',$e->msg());
        }


    }




    // 获取运输方式
    public function getCarrierShippingServiceStr($account){
        try {            	
        	$result = CarrierAPIHelper::getSysCarrierAddiInfo('lb_feite');
        	if($result["code"]){
        		$addi_infos=$result["date"]['addi_infos'];
        		$addi_infos_arr=json_decode($addi_infos,true);        		
        		if(empty($addi_infos_arr) || empty($addi_infos_arr["expires_in_time"]) || strtotime(date("Y-m-d H:i:s"))>$addi_infos_arr["expires_in_time"]){
        			//重新授权
        			$AccessToken_arr=self::getAccessToken();
        		
        			$addi_update_arr=$AccessToken_arr;
        		
        			$nextuptime=strtotime(date("Y-m-d H:i:s"))+$addi_update_arr["expires_in"]-86400;
        			$addi_update_arr["expires_in_time"]=$nextuptime;
        		
        			$re=CarrierAPIHelper::setSysCarrierAddiInfo("lb_feite",$addi_update_arr);        		
        		}
        	}
        	

            $response = Helper_Curl::get('http://exorderwebapi.flytcloud.com/BaseInfo/GetPostTypes');
            $channelArr = json_decode($response,true);

            if(empty($channelArr['datas']) || !is_array($channelArr['datas']) || json_last_error()!=false){
                return self::getResult(1,'','获取运输方式失败');
            }

            $channelStr="";
            foreach ($channelArr['datas'] as $countryVal){
                $channelStr.=$countryVal['code'].":".$countryVal['posttypeName'].";";
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

 

            //面单授权token 暂时写死 30天过期
//             $access_token = $this->access_token;
            $SysCarrierAddiInfo=CarrierAPIHelper::getSysCarrierAddiInfo('lb_feite',array("access_token"))["date"]["access_token"];
            $access_token = empty($SysCarrierAddiInfo)?$this->access_token:$SysCarrierAddiInfo;
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


    //飞特获取token，暂时写死，半个月左右过期
    public static function getAccessToken(){

        $authRequestData = array();
        $authRequestData['grant_type'] = 'password';
        //所有客户统一使用这个username和password【这是飞特分配给用户的】
		// TODO carrier dev account @XXX@
        $authRequestData['username'] = '@XXX@';
        $authRequestData['password'] = md5('@XXX@');
        //$authRequestData['scope'] = '';

        $authReqBody = json_encode($authRequestData,true);
        $authHead[] = "Content-Type: application/json;charset=UTF-8";

        $authRes = Helper_Curl::post('http://exapi.flytcloud.com/api/auth/Authorization/GetAccessToken',$authReqBody,$authHead);
        $authResArr = json_decode($authRes,true);
//         print_r($authResArr);

        return $authResArr;
    }

    /*
  * 用来确定打印完成后 订单的下一步状态
  */
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }

    /**
     * xML转为数组
     */
    public static function xml_to_array($xml,$main_heading = '') {
        $deXml = simplexml_load_string($xml);
        $deJson = json_encode($deXml);
        $xml_array = json_decode($deJson,TRUE);
        if (! empty($main_heading)) {
            $returned = $xml_array[$main_heading];
            return $returned;
        } else {
            return $xml_array;
        }
    }




    /**
     * 数组转为xML
     */
    public static function arraytoxml($arr, $root = false, $header = true) {
        if (! function_exists('is_hash')) {
            function is_hash($var) {
                return ( is_array($var) && array_keys($var) !== range(0, count($var)-1) );
            }
        }
        if (! function_exists('normalize_array2xml')) {
            function normalize_array2xml($arr, $level = 0) {
                if (is_object($arr)) $arr = get_object_vars($arr);

                for ($i = 0, $tabs = ''; $i < $level; $i++) $tabs .= "\t";
                $output = array();

                foreach($arr as $k => $v) {
                    if (is_null($v)) {
                        continue;
                    }
                    elseif (is_bool($v)) {
                        $value = ($v === true ? 'TRUE' : 'FALSE');
                        $output[] = $tabs . sprintf('<%1$s>%2$s</%1$s>', $k, $value);
                    }
                    elseif ($v === '') {
                        $output[] = $tabs . sprintf('<%1$s/>', $k);
                    }
                    elseif (is_scalar($v)) {
                        $value = $v;
                        $output[] = $tabs . sprintf('<%1$s>%2$s</%1$s>', $k, htmlspecialchars($value));
                    }
                    elseif (is_hash($v)) {
                        $value = normalize_array2xml($v, $level + 1);
                        $output[] = $tabs . sprintf('<%1$s>%2$s</%1$s>', $k, "\n{$value}\n{$tabs}");
                    }
                    elseif (is_array($v)) {
                        foreach($v as $w) {
                            $output[] = normalize_array2xml(array($k => $w), $level);
                        }
                    }
                }
                return implode("\n", $output);
            }
        }

        if ($root) {
            $arr = array((string) $root => $arr);
        }
        $xml = normalize_array2xml($arr);
        if ($header) {
            $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n" . $xml;
        }

        return $xml;
    }

}



?>