<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\order\helpers\RumallOrderHelper;
use eagle\models\SaasRumallUser;
use Jurosh\PDFMerge\PDFMerger;

class LB_RUMALLSFGUOJICarrierAPI extends BaseCarrierAPI{
    public $baseUrl;
    public function __construct(){
        if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
            $this->baseUrl = 'http://rumall.com:8080/rumall/exportWaybill';
        }else {
            $this->baseUrl = 'http://47.88.2.1:8080/rumall/exportWaybill';
        }
    }
    /**
     +----------------------------------------------------------
     * 标志发货
     +----------------------------------------------------------
     **/
    public function getOrderNO($data){
        try{
            $order = $data['order'];
            $form_data = $data['data'];
            
            //重复发货 添加不同的标识码
            $extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
            $customer_number = $data['data']['customer_number'];
            
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
            
            if(empty($form_data['DeclaredValue1'])||empty($form_data['DeclaredValue2'])){
                return self::getResult(1,'','申报价值都不能为空！');
            }else{
                $params['DeclaredValue1'] = $form_data['DeclaredValue1'];
                $params['DeclaredValue2'] = $form_data['DeclaredValue2'];
                $params['customer_orderid'] = $customer_number;
            }
            $info = CarrierAPIHelper::getAllInfo($order);
            $Service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
            
            $ship_result = RumallOrderHelper::shipOrder($Service->shipping_method_code,$order,$params);
            if(!$ship_result['success']){
                if($ship_result['message'] == 'rumall ship 接口未知错误'){
                    $str = json_encode($ship_result['data']);
                    \Yii::info('RumallSF,result,order_id:'.$order->order_id.' '.$str,"carrier_api");
                }
                return self::getResult(1,'',$ship_result['message']);
            }else if($ship_result['success'] == true){
                $returnData = $ship_result['data'];
                $r = CarrierAPIHelper::orderSuccess($order,$Service, $returnData['OrderNum'], OdOrder::CARRIER_WAITING_PRINT,$returnData['Mailno'],['AgentMailno'=>$returnData['AgentMailno']]);
                return  self::getResult(0,$r,'标志发货成功');
            }else{
                return self::getResult(1,'','获取数据有误，请联系技术人员');
            }
        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }
    }
    /**
     +----------------------------------------------------------
     * 取消跟踪号
     +----------------------------------------------------------
     **/
    public function cancelOrderNO($data){
        return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');
    }
    /**
     +----------------------------------------------------------
     * 交运
     +----------------------------------------------------------
     **/
    public function doDispatch($data){
        return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单');
    }
    
    /**
     +----------------------------------------------------------
     * 申请跟踪号
     +----------------------------------------------------------
     **/
    public function getTrackingNO($data){
        return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单');
    }
    /**
     +----------------------------------------------------------
     * 打单
     +----------------------------------------------------------
     **/
    public function doPrint($data){
        try{
            $pdf = new PDFMerger();
            $print_url = $this->baseUrl;
            $user=\Yii::$app->user->identity;
            $puid = $user->getParentUid();
            foreach ($data as $k=>$v){
                $order = $v['order'];
                $rumallUser = SaasRumallUser::find()->where(['uid'=>$puid,'company_code'=>$order->selleruserid])->one();
                if(empty($rumallUser)){
                    return self::getResult(1,'','打印时报，没有找到相关用户信息！');
                }
                $print_request_xml="xml=<ExportWaybill>
<Checkword>".$rumallUser->token."</Checkword>
<CompanyCode>".$rumallUser->company_code."</CompanyCode>
<OrderId>".$order->order_source_order_id."</OrderId>
</ExportWaybill>";
            
                $print_result = Helper_Curl::post($print_url,$print_request_xml);
            
                if(strlen($print_result)>1000){
                    $pdfurl = CarrierAPIHelper::savePDF($print_result,$puid,$order->order_source_order_id.'_'.time(),0);
                    $pdf->addPDF($pdfurl['filePath'],'all');//合并相同运输运输方式的pdf流
//                     $order->is_print_carrier = 1;
                    $order->print_carrier_operator = $puid;
                    $order->printtime = time();
                    $order->carrier_error = '';
                    $order->save();
                }else{
                    return self::getResult(1, '', "打印失败，请联系技术人员");
                }
            }
            isset($pdfurl)?$pdf->merge('file', $pdfurl['filePath']):$pdfurl['filePath']='';//需要物理地址
            return self::getResult(0,['pdfUrl'=>$pdfurl['pdfUrl']],'连接已生成,请点击并打印');//访问URL地址
        }catch(CarrierException $e) {
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
        $result = array('is_support'=>0,'error'=>0);
        return $result;
    }
    
    //获取运输方式
    public function getCarrierShippingServiceStr($account){
        return self::getResult(1, '', '');
    }
}

?>
