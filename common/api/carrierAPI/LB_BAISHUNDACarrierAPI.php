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
//try{
//    //include '../components/PDFMerger/PDFMerger.php';
//    include dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'PDFMerger'.DIRECTORY_SEPARATOR.'PDFMerger.php';
//}catch(\Exception $e){
//}

/**
+------------------------------------------------------------------------------
 * 飞特接口业务逻辑类
+------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/4pxCarrierAPI
 * @subpackage  Exception
 * @author		dwg
 * @version		1.0
+------------------------------------------------------------------------------
 */
class LB_BAISHUNDACarrierAPI extends BaseCarrierAPI
{

    public $soapClient_order = null; // SoapClient_order实例
    public $soapClient_label = null; // SoapClient_label实例

    public $wsdl_order = null; // 物流订单接口
    public $wsdl_label = null; // 物流标签打印接口

    public $UserName = null;  //用户名
    public $PassWord = null;     //密码


    function __construct()
    {
        if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
            $this->wsdl_order='http://wcf.bsdexp.com/OrderService.svc?wsdl';    //正式百顺达订单接口地址
            $this->wsdl_label='http://wcf.bsdexp.com/WebInterface/LabelInterface/LabelInterface.asmx?wsdl';  ////正式百顺达标签打印接口地址
        }else
        {
            $this->wsdl_order='http://wcf.bsdexp.com/OrderService.svc?wsdl';  //测试接口地址和正式地址一样
            $this->wsdl_label='http://wcf.bsdexp.com/WebInterface/LabelInterface/LabelInterface.asmx?wsdl';

        }


        if(is_null($this->soapClient_order)||!is_object($this->soapClient_order)){
            try {
                //该物流要使用默认的soap1.1版本才请求成功，否则soap1.2版本请求不成功！
                $this->soapClient_order = new \SoapClient($this->wsdl_order,array('soap_version' => SOAP_1_1));
//           var_dump($this->soapClient->__getFunctions());
            }catch (Exception $e){
                return self::getResult(1,'','网络连接故障'.$e->getMessage());
            }
        }

        if(is_null($this->soapClient_label)||!is_object($this->soapClient_label)){
            try {
                //该物流要使用默认的soap1.1版本才请求成功，否则soap1.2版本请求不成功！
                $this->soapClient_label = new \SoapClient($this->wsdl_label,array('soap_version' => SOAP_1_1));
           //var_dump($this->soapClient_label->__getFunctions());die;
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
         try{
             $PrintParam = ['Label_10_10_Address','Label_10_10_PeiHuoDan','Label_10_10_PHD','SwedeLabel_10_10','SwedenLabel_10_10_PHD','NormalSwedenLabel_10_10','NormalSwedenLabel_10_10_PHD','Label_GlobalMail_10_10','Label_GlobalMail_10_10_SKU','Label_GlobalMail_10_10_PHD','Forecase_Label_10_10'];  //10*10打印格式
             $user=\Yii::$app->user->identity;
             $puid = $user->getParentUid();

             $order = $pdata['order'];// object OdOrder 订单对象
         	
         	//重复发货 添加不同的标识码
         	$extra_id = isset($pdata['data']['extra_id'])?$pdata['data']['extra_id']:'';
         	$customer_number = $pdata['data']['customer_number'];
         		
         	if(isset($pdata['data']['extra_id'])){
         		if($extra_id == ''){
         			return self::getResult(1, '', '强制发货标识码，不能为空');
         		}
         	}
         	
            $data = $pdata['data'];  // 表单提交的数据
             

             //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
             $checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
             $shipped = $checkResult['data']['shipped']; // object OdOrderShipped

             //获取到所需要使用的数据
             $info = CarrierAPIHelper::getAllInfo($order);
             $account = $info['account'];// object SysCarrierAccount
             $service = $info['service'];// object SysShippingService

             if(empty($info['senderAddressInfo']['shippingfrom'])){
             	return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
             }

             $account_api_params = $account->api_params;//获取到帐号中的认证参数
             $shippingfrom_address = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息(shppingfrom是属于物流商填“收货地址”的信息)

             //设置用户名，密码
             $this->UserName = isset($account_api_params['UserName'])? $account_api_params['UserName']:'';
             $this->PassWord = isset($account_api_params['PassWord'])? $account_api_params['PassWord']:'';

             $addressAndPhoneParams = array(
                 'address' => array(
                     'consignee_address_line1_limit' => 100,
                     'consignee_address_line2_limit' => 100,
                     'consignee_address_line3_limit' => 100,
                 ),
                 'consignee_district' => 1,
                 'consignee_county' => 1,
                 'consignee_company' => 1,
                 'consignee_phone_limit' => 100
             );
             $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);


             //订单参数
             $OrderRequest = array(
                 'rreference'    => $customer_number,
                 'sname'         => $shippingfrom_address['contact'],
                 'saddr1'        => $shippingfrom_address['street'],
                 'saddr2'        => $shippingfrom_address['street'],
                 'saddr3'        => $shippingfrom_address['street'],
                 'sphone'        => $shippingfrom_address['phone'],
                 'scity'         => $shippingfrom_address['city'],
                 'spostcode'     => $shippingfrom_address['postcode'],
                 'sprovince'     => $shippingfrom_address['province'],
                 'sccode'        => $shippingfrom_address['country'],
                 'rname'         => $order->consignee,
//                 'raddr1'        => $order->consignee_address_line1,
//                 'raddr2'        => $order->consignee_address_line2,
//                 'raddr3'        => $order->consignee_address_line3,
                 'raddr1'        => $addressAndPhone['address_line1'],
                 'raddr2'        => $addressAndPhone['address_line2'],
                 'raddr3'        => $addressAndPhone['address_line3'],
//                 'rphone'        => $order->consignee_phone,
                 'rphone'        =>$addressAndPhone['phone1'],
                 'rpostcode'     => $order->consignee_postal_code,
                 'rprovince'     => $order->consignee_province,
                 'rccode'        => $order->consignee_country_code,
                 'rcity'         => $order->consignee_city,
                 'ctype'         => 'O' ,    //收件人货物类别,物流商IT：现在暂时只提供“其他“->”O“的选项
                 'occode'        => empty($data['occode']) ? 'CHINA' : $data['occode'],
                 'rcontent'      => empty($data['rcontent']) ? '' : $data['rcontent'],
//                 'pquantity'     => 100,   //总件数模拟
//                 'totalweight'   => 100,   //总重量模拟
//                 'ptvalu'        => 100,   //总申报价值模拟
                 'pcurrencycode' => empty($data['pcurrencycode'])? 'USD' : $data['pcurrencycode'],
                 'cmemo'         => empty($data['cmemo'])? '' : $data['cmemo'],
                 'electrle'      => empty($data['electrle'])? '' : $data['electrle'],
                 'servercode'    => $service->shipping_method_code,

             );

             //配货信息
             $pquantity = 0;
             $totalWeight = 0;
             $ptvalu = 0;
             $OrderDetail = array();
             foreach($order->items as $j=>$vitem)
             {
                 $OrderDetail[$j] = [
                     'MaterialRefNo'      => empty($data['MaterialRefNo'][$j]) ? '' : $data['MaterialRefNo'][$j],
                     'MaterialQuantity'   => empty($data['MaterialQuantity'][$j]) ? 1 : $data['MaterialQuantity'][$j],  //数量
                     'Price'              => empty($data['DeclaredValue'][$j]) ? 1:$data['DeclaredValue'][$j] ,     //单位价值(单价)(USD)
                     'Weight'             => empty($data['Weight'][$j]) ? 1 : round(($data['Weight'][$j])/1000,2),        //产品的重量填入的是(G)，需要转换为KG，因为提交需默认KG
                     'CnName'             => empty($data['Name'][$j]) ? '': $data['Name'][$j],
                     'EnName'             => empty($data['EName'][$j]) ? '': $data['EName'][$j],
                     'CustomcCode'        => empty($data['CustomcCode'][$j]) ? '': $data['CustomcCode'][$j],
                     'ProducingArea'      => empty($data['ProducingArea'][$j]) ? '': $data['ProducingArea'][$j],
                 ];

                 $pquantity += $OrderDetail[$j]['MaterialQuantity'] ;   //总件数
                 $totalWeight += $data['Weight'][$j] * $OrderDetail[$j]['MaterialQuantity'];  //总重量
                 $ptvalu += $data['DeclaredValue'][$j] * $OrderDetail[$j]['MaterialQuantity'];  //总价格
             }

             $OrderRequest['OrderDetails'] = $OrderDetail;

             //总件数，总重量，总价格的订单赋值
             $OrderRequest['pquantity'] = $pquantity;
             $OrderRequest['totalweight'] = round($totalWeight, 2);
             $OrderRequest['ptvalu'] = round($ptvalu , 2);

             $orderRequests['OrderRequest']=$OrderRequest;

             $response = $this->soapClient_order->ShipService(array('UserName'=>$this->UserName,'PassWord'=>$this->PassWord,'orderRequests'=>$orderRequests));

             \Yii::info(print_r($response,true),"file");// 先记下结果，记下refrence_no，这个返回应该与上面提交refrence_no一样。

             $responseStatus = $response->ShipServiceResult->ack;   //返回的成功/失败状态

             if($responseStatus != 1){

                 $responseError = $response->ShipServiceResult->errors->error;      //提交失败后返回的错误数组/对象(单个错误信息为数组，多个错误信息为对象)
                 $error_message = '';   //返回的错误信息

                 if(is_array($responseError))
                 {
                      foreach($responseError as $obj)
                      {
                         $error_message .=  $obj->Message.'。';
                      }
                     return self::getResult(1, '', '错误信息：'.$error_message );
                 }
                 else if(is_object($responseError))
                 {
                     return self::getResult(1, '', '错误信息：'.$responseError->Message );

                 }

             }else
             {
                 $responseRight_tracking_number = $response->ShipServiceResult->shipmentInfos->ShipmentInfo->pno;   //提交成功后返回的物流跟踪号

                 //上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态（浩远直接返回物流号但不一定是真的，所以跳到第2步），跟踪号(选填),returnNo(选填)
                 $r = CarrierApiHelper::orderSuccess( $order , $service , $customer_number , OdOrder::CARRIER_WAITING_PRINT , $responseRight_tracking_number );

                 $carrier_params = $service->carrier_params;

                 if(in_array($carrier_params['labelType'], $PrintParam)){
                     $print_param = array();
                     $print_param['carrier_code'] = $service->carrier_code;
                     $print_param['api_class'] = 'LB_BAISHUNDACarrierAPI';
                     $print_param['tracking_number'] = $responseRight_tracking_number;
                     $print_param['carrier_params'] = $service->carrier_params;

                     try{
                         CarrierAPIHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
                     }catch (\Exception $ex){
                     }
                 }

                 return  self::getResult(0,$r, "操作成功!  物流跟踪号：".$responseRight_tracking_number);
             }

             //必须要return 东西，才不会报错： undfined index:shipping_method_name ;

         }
         catch (CarrierException $e){
             return self::getResult(1,'',"file:".$e->getFile()." line:".$e->getLine()." ".$e->msg());
         }
     }
    /**
    +----------------------------------------------------------
     * 取消跟踪号
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
         return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号成功后就会返回跟踪号');

     }

    /**
    +----------------------------------------------------------
     * 打单
    +----------------------------------------------------------
    $normalparams  普通参数
    $tracking_number  跟踪号
     **/
     public function doPrint($data)
     {
         try {

             $pdf = new PDFMerger();

             $user=\Yii::$app->user->identity;

             if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
             $puid = $user->getParentUid();

             $all_message = current($data);reset($data);//打印时是逐个运输方式的多张订单传入，所以获取一次account、service的信息就可以了

             $order_object=$all_message['order'];//获取订单的对象
             //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
             $checkResult = CarrierAPIHelper::validate(1,1,$order_object);

             /**获取相应跟踪号*/
             $shipped = $checkResult['data']['shipped'];

             $tracking_number = $shipped->tracking_number; //获取跟踪号

             //获取到所需要使用的数据
             $info = CarrierAPIHelper::getAllInfo($order_object);
             $account = $info['account'];
             $service = $info['service'];

             $account_api_params = $account->api_params;//获取到帐号中的认证参数
             $normalparams = $service->carrier_params; ////获取打印方式

            //设置用户名，密码
             $this->UserName = isset($account_api_params['UserName'])? $account_api_params['UserName']:'';
             $this->PassWord = isset($account_api_params['PassWord'])? $account_api_params['PassWord']:'';

             $OrderInfos = array();
             foreach ($data as $v) {

                 $oneOrder = $v['order'];

//                 检查打印类型是否为空（必填）
                 if (empty($normalparams['labelType'])) {
                     return self::getResult(1, '', '订单id：' . $oneOrder->order_id . '。物流商：' . $service->carrier_name . '。运输方式：' . $service->shipping_method_name . '必须选择打印类型！请往该运输服务管理选择打印类型！');
                 }

//                 if(empty($normalparams['labelType'])){//必须填入打印格式
//                     throw new CarrierException("必须选择打印类型！");
//                 }


                 //检查是否存在跟踪号（必填）
                 if (empty($tracking_number)) {
                     return self::getResult(1, '', '订单id：' . $oneOrder->order_id . ' 缺少物流跟踪号,请检查订单是否上传');
                 }

                 $labelType = $normalparams['labelType'];
//                 $OrderInfos[] = array('OrderInfo'=>array('TrackingNo'=>$tracking_number));
//                 $OrderInfos ['OrderInfo'][] = array('TrackingNo'=>$tracking_number);
                 $OrderInfos[] = array("TrackingNo" => $tracking_number);

                 // $OrderInfos['OrderInfo']['TrackingNo'] = $tracking_number;
             }
                 $response = $this->soapClient_label->GetParcelLabel(array('labelType'=>$labelType , 'OrderInfos'=>$OrderInfos));
                 $responseData = json_decode($response->GetParcelLabelResult , true);

                 if (!empty($responseData['ErrorInfo'])) {
                     return self::getResult(1,'', $responseData['ErrorInfo']);
                 }
                 else
                 {
                     $pdfUrl=CarrierAPIHelper::savePDF(base64_decode($responseData['PDF']),$puid,$account->carrier_code.'_'.$oneOrder->customer_number,0);
                     $pdf->addPDF($pdfUrl['filePath'],'all');//合并相同运输运输方式的pdf流
//                      $oneOrder->is_print_carrier = 1;
                     $oneOrder->print_carrier_operator = $puid;
                     $oneOrder->printtime = time();
                     $oneOrder->carrier_error = '';
                     $oneOrder->save();
//                     print_r($a);
//                     exit;
                 }
//                 $pdf->addPDF(XXX.pdf,'all');
//                 $pdf->addPDF($pdfUrl['filePath'],'all');
//             }
//             合并多个PDF  这里还需要进一步测试
//             isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
             return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');

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
        try {
            $puid = $SAA_obj->uid;

            if(empty($print_param['tracking_number'])){
                return ['error'=>1, 'msg'=>'缺少物流跟踪号,请检查订单是否上传', 'filePath'=>''];
            }

            $OrderInfos[] = array("TrackingNo" => $print_param['tracking_number']);

            $normal_params = empty($print_param['carrier_params']) ? array() : $print_param['carrier_params']; ////获取打印方式
            $labelType = empty($normal_params['labelType']) ? 'SwedenLabel_10_10_PHD':$normal_params['labelType'];

            $response = $this->soapClient_label->GetParcelLabel(array('labelType'=>$labelType , 'OrderInfos'=>$OrderInfos));
            $responseData = json_decode($response->GetParcelLabelResult , true);

            if (!empty($responseData['ErrorInfo'])) {
                return ['error'=>1, 'msg'=>'打印失败！错误信息：'.$responseData['ErrorInfo'], 'filePath'=>''];
            }
            else
            {
                $pdfPath = CarrierAPIHelper::savePDF2(base64_decode($responseData['PDF']),$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
                return $pdfPath;
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


    // 获取运输方式
    public function getCarrierShippingServiceStr($account){
        try {
            $soap = new \SoapClient("http://wcf.bsdexp.com/OrderService.svc?wsdl");
            $response = $soap->GetProductService();
            $channelArr = $response->GetProductServiceResult->ProductList->string;

            if(empty($channelArr) || !is_array($channelArr) || json_last_error()!=false){
                return self::getResult(1,'','获取运输方式失败');
            }

            $channelStr="";
            foreach ($channelArr as $countryVal){
                $channelStr.=$countryVal.":".$countryVal.";";
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

    // 获取标签打印列表
    public function getLabelType(){
        try {

            $soap = new \SoapClient("http://wcf.bsdexp.com/WebInterface/LabelInterface/LabelInterface.asmx?wsdl");
            $resp = $soap->GetLabelType();
            $result_json = $resp->GetLabelTypeResult;
            $resultArr = json_decode($result_json,true);
            foreach($resultArr as $result)
            {
                print_r($result['TypeEnName'].':'.$result['TypeCnName'].';');
            }

        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }

    }
}



?>