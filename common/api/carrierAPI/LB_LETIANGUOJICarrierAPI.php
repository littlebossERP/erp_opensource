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
 * 乐天国际接口业务逻辑类
+------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/4pxCarrierAPI
 * @subpackage  Exception
 * @author		qfl
 * @version		1.0
+------------------------------------------------------------------------------
 */
class LB_LETIANGUOJICarrierAPI extends BaseCarrierAPI
{

    public $soapClient = null; // SoapClient实例
    public $wsdl = null; // 物流接口

    public $appToken = null;
    public $appKey = null;

    function __construct(){
        if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
            $this->wsdl='http://119.28.14.27:8871/webservice/PublicService.asmx?WSDL';
        }else{
            $this->wsdl='http://119.28.14.27:8871/webservice/PublicService.asmx?WSDL';//测试接口
        }

        if(is_null($this->soapClient)||!is_object($this->soapClient)){
            try {
                $this->soapClient = new \SoapClient($this->wsdl,array('soap_version' => SOAP_1_2));
            }catch (Exception $e){
                return self::getResult(1,'','网络连接故障'.$e->getMessage());
            }
        }
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

            if(empty($info['senderAddressInfo']['shippingfrom'])){
                return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
            }

            $account_api_params = $account->api_params;//获取到帐号中的认证参数
            $shippingfromaddress = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息

            $normalparams = $service->carrier_params;

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
                'reference_no'          =>$customer_number,  //客户参考号
                'shipping_method'       =>$service->shipping_method_code,  //运输方式代码
                'order_weight'          =>empty($data['order_weight'])?'0.2':sprintf("%.1f",$data['order_weight']),  //订单重量，单位KG，默认为0.2
                'order_pieces'          =>empty($data['order_pieces'])?'1':$data['order_pieces'],  //外包装件数,默认1
            );


            //发件人信息
            $shipper = array(
                'shipper_name'            =>$shippingfromaddress['contact'],  //发件人姓名
                'shipper_countrycode'     =>$shippingfromaddress['country'],  //发件人国家二字码
                'shipper_street'          =>$shippingfromaddress['street'],  //发件人地址
                'shipper_telephone'       =>$shippingfromaddress['phone'],  //发件人电话
                'shipper_city'            =>$shippingfromaddress['city'],  //发件人城市
                'shipper_province'        =>$shippingfromaddress['province'],  //发件人省
                'shipper_mobile'          =>$shippingfromaddress['mobile'],  //发件人手机
            );
            $OrderInfo['shipper'] = $shipper;


            //收件人信息
            $consignee = array(
                'consignee_name'            =>$order->consignee,  //收件人姓名
                'consignee_countrycode'     =>$order->consignee_country_code,  //收件人国家代码
                'consignee_street'          =>$tmpAddress,  //收件人地址
                'consignee_telephone'       =>$order->consignee_phone,  //收件人电话
                'consignee_province'        =>$order->consignee_province,  //收件人省
                'consignee_city'            =>$order->consignee_city,  //收件人城市
                'consignee_mobile'          =>$order->consignee_mobile,  //收件人手机
                'consignee_email'           =>$order->consignee_email,  //收件人邮箱
                'consignee_postcode'        =>$order->consignee_postal_code,  //收件人邮编
            );
            $OrderInfo['consignee'] = $consignee;


            //产品明细
            $invoice = array();
            foreach($order->items as $j=>$vitem)
            {
                $invoice[$j] = [
                    'sku'                    =>$data['Sku'][$j],  //货号(SKU)
                    'invoice_cnname'         =>$data['Name'][$j],  //中文品名
                    'invoice_enname'         =>$data['EName'][$j],  //英文品名
                    'invoice_quantity'       =>$data['DeclarePieces'][$j],  //数量
                    'invoice_unitcharge'     =>$data['DeclaredValue'][$j],  //单价
                    'hs_code'                =>$data['hs_code'][$j],  //海关协制编号
                ];
            }
            $OrderInfo['invoice'] = $invoice;
            $this->appToken = isset($account_api_params['appToken'])?$account_api_params['appToken']:'';
            $this->appKey = isset($account_api_params['appKey'])?$account_api_params['appKey']:'';

            $response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"createorder", 'paramsJson'=>json_encode($OrderInfo)));

            \Yii::info(print_r($response,true),"file");

            $responseData = json_decode($response->ServiceEntranceResult , true);

            if($responseData['success'] != 1){
                return self::getResult(1, '', '<br>中文结果：'. $responseData['cnmessage'] .' '. '<br>英文结果：'.$responseData['enmessage'] );
            }else{
                //上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态（浩远直接返回物流号但不一定是真的，所以跳到第2步），跟踪号(选填),returnNo(选填)
                $r = CarrierApiHelper::orderSuccess( $order , $service , $responseData['data']['refrence_no'] , OdOrder::CARRIER_WAITING_GETCODE , $responseData['data']['shipping_method_no'] );

                /** start 配货单合并地址单*/
                $print_param = array();
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_LETIANGUOJICarrierAPI';
                $print_param['appToken'] = $this->appToken;
                $print_param['appKey'] = $this->appKey;
                $print_param['refrence_no'] = $responseData['data']['refrence_no'];
                $print_param['carrier_params'] = $service->carrier_params;

                try{
                    CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
                }catch (\Exception $ex){
                }
                /** end 配货单合并地址单*/

                return  self::getResult(0,$r, "物流跟踪号：".$responseData['data']['shipping_method_no']);
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
        return BaseCarrierAPI::getResult(1, '', '物流接口不支持获取跟踪号，上传物流单便会返回跟踪号。');
//        try {
//
//            $order = $data['order'];
//            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
//            $checkResult = CarrierAPIHelper::validate(0,1,$order);
//            $shipped = $checkResult['data']['shipped'];
//
//            //获取到所需要使用的数据
//            $info = CarrierAPIHelper::getAllInfo($order);
//            $account = $info['account'];// object SysCarrierAccount
//            $service = $info['service'];// object SysShippingService
//
//            $account_api_params = $account->api_params;//获取到帐号中的认证参数
//            $this->appToken = isset($account_api_params['appToken'])?$account_api_params['appToken']:'';
//            $this->appKey = isset($account_api_params['appKey'])?$account_api_params['appKey']:'';
//
//            $param = array('reference_no'=>$order->customer_number);
//
//            $response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"gettrackingnumber", 'paramsJson'=>json_encode($param)));
//            $responseData = json_decode($response->ServiceEntranceResult , true);
//
//            if ($responseData['success'] != 1) {
//                return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
//            }else{
//                $shipped->tracking_number = $responseData['data']['shipping_method_no'];
//                $shipped->save();
//                $order->tracking_number = $shipped->tracking_number;
//                $order->save();
//
//                return self::getResult( 0 , $responseData['data'] ,  $responseData['cnmessage'] .' 跟踪号：'.$responseData['data']['shipping_method_no']);
//            }
//
//        }catch (CarrierException $e){
//            return self::getResult(1,'',$e->msg());
//        }

    }

    /**
    +----------------------------------------------------------
     * 打单
    +----------------------------------------------------------
     **/
    public function doPrint($data){

        try {
            $pdf = new PDFMerger();

            $order = current($data);reset($data);
            $getAccountInfoOrder = $order['order'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(1,1,$getAccountInfoOrder);
            $shipped = $checkResult['data']['shipped'];
            $puid = $checkResult['data']['puid'];

            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($getAccountInfoOrder);
            $account = $info['account'];// object SysCarrierAccount
            $service = $info['service'];// object SysShippingService

            $account_api_params = $account->api_params;//获取到帐号中的认证参数
            $normalparams = $service->carrier_params;

            $this->appToken = isset($account_api_params['appToken'])?$account_api_params['appToken']:'';
            $this->appKey = isset($account_api_params['appKey'])?$account_api_params['appKey']:'';

            $param = array();
            $additional_info =  array(
                'lable_print_invoiceinfo'				=> empty($normalparams['lable_print_invoiceinfo'])?'N':$normalparams['lable_print_invoiceinfo'] , // string 标签上是否打印配货信息
                'lable_print_buyerid'					=> empty($normalparams['lable_print_buyerid'])?'N':$normalparams['lable_print_buyerid'], // string 标签上是否打印买家 ID
                'lable_print_datetime'					=> empty($normalparams['lable_print_datetime'])?'N':$normalparams['lable_print_datetime'], // string 标签上是否打印日期
                'customsdeclaration_print_actualweight'	=> empty($normalparams['customsdeclaration_print_actualweight'])?'N':$normalparams['customsdeclaration_print_actualweight'], // string 报关单上是否打印实际重量
            );

            // object required 配置信息
            // 这里默认填了lable_paper_type=>2,lable_content_type=>1 以防卖家没有在 运输服务里面选择。
            $configInfo = array(
                'lable_file_type'	=> '2' , // string 标签文件类型  1:image 图片 , 2: pdf 目前测试场隐藏了lable_file_type 没得选文件类型，默认填了pdf 其他物流商都是处理pdf的
                'lable_paper_type'	=> empty($normalparams['lable_paper_type'])?'2':$normalparams['lable_paper_type'] , // string 纸张类型  1:label 标签纸 , 2:a4 A4纸
                'lable_content_type'=> empty($normalparams['lable_content_type'])?'1':$normalparams['lable_content_type'] , // string 标签内容类型代码  打印选项: 1:标签,2:报关单,3:配货单,4:标签+报关单,5:标签+配货单,6:标签+报关单+配货单
                'additional_info'	=> $additional_info , // object 附加配置信息
            );

            $param['configInfo'] = $configInfo;

            foreach ($data as $v) {
                $listorder = array();// object/array required 订单信息
                $oneOrder = $v['order'];
                $oneOrder->print_carrier_operator = $puid;
                $oneOrder->printtime = time();
                $oneOrder->save();

                if(empty($oneOrder->customer_number)){
                    return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少客户参考号,请检查订单是否上传' );
                }
                $listorder[]['reference_no'] = $oneOrder->customer_number;// 注意这里文档写的参数是 refrence_no 要改成reference_no 接口才调用成功
// 				$listorder[]['reference_no'] = '68216610157812';
// 				$listorder[]['reference_no'] = '68215411321284';
// 				$listorder[]['reference_no'] = '68220849553138';
// 				$listorder[]['reference_no'] = '68215539530355';

                $param['listorder'] = $listorder;

// 				print_r($param);
                $response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"getlabel", 'paramsJson'=>json_encode($param)));
                $responseData = json_decode($response->ServiceEntranceResult , true);

                if ($responseData['success'] != 1) {
                    return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
                }else{
                    foreach ($responseData['data'] as $oneResult){
                        $responsePdf = Helper_Curl::get($oneResult['lable_file']);

                        if(strlen($responsePdf)<1000){
                            $oneOrder->carrier_error = $response;
                            $oneOrder->save();
                            return self::getResult(1, '', $response);
                        }
                        return self::getResult(0,['pdfUrl'=>$oneResult['lable_file']],'连接已生成,请点击并打印');

                    }
                }
            }
//
//            //合并多个PDF  这里还需要进一步测试
//            isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
//            return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');

        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }


    }




    // 获取运输方式
    public function getCarrierShippingServiceStr($account){
        try {

            $reqUrl = 'http://119.28.14.27:8871/webservice/PublicService.asmx/ServiceEntrance';
            // TODO carrier user account @XXX@
			$reqBody = json_encode(array('appToken'=>'@XXX@','appKey'=>'@XXX@','serviceMethod'=>'getshippingmethod','paramsJson'=>''));
            $reqHeader[] = 'Content-Type: application/json;charset=UTF-8';
            $response = Helper_Curl::post($reqUrl,$reqBody,$reqHeader);

            $Arr = json_decode($response,true);
            $channelArr = json_decode($Arr['d']);

            if(empty($channelArr->data) || !is_array($channelArr->data) || json_last_error()!=false){
                return self::getResult(1,'','获取运输方式失败');
            }

            $channelStr="";
            foreach ($channelArr->data as $countryVal){
                $channelStr.=$countryVal->code.":".$countryVal->cnname.";";
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

            if(empty($print_param['refrence_no'])){
                return ['error'=>1, 'msg'=>'缺少客户参考号,请检查订单是否上传', 'filePath'=>''];
            }

            $normal_params = empty($print_param['carrier_params']) ? array() : $print_param['carrier_params']; ////获取打印方式

            $param = array();
            $additional_info =  array(
                'lable_print_invoiceinfo'				=> empty($normal_params['lable_print_invoiceinfo'])?'N':$normal_params['lable_print_invoiceinfo'] , // string 标签上是否打印配货信息
                'lable_print_buyerid'					=> empty($normal_params['lable_print_buyerid'])?'N':$normal_params['lable_print_buyerid'], // string 标签上是否打印买家 ID
                'lable_print_datetime'					=> empty($normal_params['lable_print_datetime'])?'N':$normal_params['lable_print_datetime'], // string 标签上是否打印日期
                'customsdeclaration_print_actualweight'	=> empty($normal_params['customsdeclaration_print_actualweight'])?'N':$normal_params['customsdeclaration_print_actualweight'], // string 报关单上是否打印实际重量
            );

            // object required 配置信息
            // 这里默认填了lable_paper_type=>2,lable_content_type=>1 以防卖家没有在 运输服务里面选择。
            $configInfo = array(
                'lable_file_type'	=> '2' , // string 标签文件类型  1:image 图片 , 2: pdf 目前测试场隐藏了lable_file_type 没得选文件类型，默认填了pdf 其他物流商都是处理pdf的
                'lable_paper_type'	=> empty($normal_params['lable_paper_type'])?'2':$normal_params['lable_paper_type'] , // string 纸张类型  1:label 标签纸 , 2:a4 A4纸
                'lable_content_type'=> empty($normal_params['lable_content_type'])?'1':$normal_params['lable_content_type'] , // string 标签内容类型代码  打印选项: 1:标签,2:报关单,3:配货单,4:标签+报关单,5:标签+配货单,6:标签+报关单+配货单
                'additional_info'	=> $additional_info , // object 附加配置信息
            );

            $param['configInfo'] = $configInfo;


            $listorder = array();// object/array required 订单信息
            $listorder[]['reference_no'] = $print_param['refrence_no'];// 注意这里文档写的参数是 refrence_no 要改成reference_no 接口才调用成功
            $param['listorder'] = $listorder;

            $response = $this->soapClient->ServiceEntrance(array('appToken'=>$print_param['appToken'] , 'appKey'=>$print_param['appKey'] , 'serviceMethod'=>"getlabel", 'paramsJson'=>json_encode($param)));
            $responseData = json_decode($response->ServiceEntranceResult , true);

            if ($responseData['success'] != 1) {
                return ['error'=>1, 'msg'=>'打印失败！'];
            }else{
                foreach ($responseData['data'] as $oneResult){
                    $responsePdf = Helper_Curl::get($oneResult['lable_file']);
                    if(strlen($responsePdf)<1000){
                        return ['error'=>1, 'msg'=>'打印失败！'.$response];
                    }
                    $pdfPath = CarrierAPIHelper::savePDF2($responsePdf,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
                    return $pdfPath;
                }
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