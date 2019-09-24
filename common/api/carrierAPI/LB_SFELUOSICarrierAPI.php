<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
// try{
// 	include dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'PDFMerger'.DIRECTORY_SEPARATOR.'PDFMerger.php';
// }catch(\Exception $e){	
// }



/**
+------------------------------------------------------------------------------
 * 顺丰俄罗斯接口业务逻辑类
+------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/SFELUOSICarrierAPI
 * @subpackage  Exception
 * @author		dwg
 * @version		1.0
+------------------------------------------------------------------------------
 */
class LB_SFELUOSICarrierAPI extends BaseCarrierAPI
{
    public $soapClient = null;             // SoapClient实例
    public $wsdl = null;                   // 物流接口
    public $clientCode = null;             //接入编码
    public $checkword = null;              //密钥（检验码）
    public $custid = null;                 //客户月结卡号

    static $connecttimeout=60;
    static $timeout=500;
    static $last_post_info=null;
    static $last_error =null;

    public $autoFetchTrackingNo = null;   //运输方式中的自动提取运单号
    public $trackingNoRuleMemo = null;    //运输方式中的运单号编码规则描述
    public $trackingNoRuleRegex = null;   //运输方式中的跟踪单号编码规则（采用正则表达式）
    public $trackingNo = null;            //用于订单提交的服务商跟踪号码

//    public $carrierOrderId = null;        //用于提供给标签打印的物流商订单号


    function __construct(){
        if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
            $this->wsdl='http://sfapi.trackmeeasy.com/ruserver/webservice/sfexpressService?wsdl';//正式环境接口地址
        }else{
            $this->wsdl='http://218.17.248.244:11080/bsp-oisp/ws/sfexpressService?wsdl';//测试环境接口地址
        }

        if(is_null($this->soapClient)||!is_object($this->soapClient)){
            try {
                $this->soapClient = new \SoapClient($this->wsdl,array('soap_version' => SOAP_1_1));
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
    public function getOrderNO($pdata){

        try{
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

            // dzt20190923 客户要求添加
            $tmp_is_use_mailno = empty($service['carrier_params']['is_use_mailno']) ? '' : $service['carrier_params']['is_use_mailno'];
            
            if(empty($info['senderAddressInfo']['shippingfrom'])){
            	return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
            }
            if (empty($data['d_deliverycode'])){
                //return self::getResult(1, '', '收件方国家必填');
            }

            $account_api_params = $account->api_params;//获取到帐号中的认证参数
            $shippingfrom_address = $info['senderAddressInfo']['shippingfrom'];//获取“发货地址"的信息)

            // dzt20190820 顺丰客户发货常用地址不在这里获取
//             $config = CarrierOpenHelper::getCommonCarrierConfig();
//             $highCopyPrint_senderAddress = $config['address']['shippingfrom_en'];
            
            $highCopyPrint_senderAddress = $shippingfrom_address;

            //认证参数
            $this->clientCode = isset($account_api_params['clientCode']) ? trim($account_api_params['clientCode']):'';//接入編碼
            $this->checkword = isset($account_api_params['checkword']) ? trim($account_api_params['checkword']):'';//密鈅（检验码）
            $this->custid = isset($account_api_params['custid']) ? trim($account_api_params['custid']):'';//客户月结卡号


            $addressAndPhoneParams = array(
                'address' => array(
                    'consignee_address_line1_limit' => 200,
                    'consignee_address_line2_limit' => 1,
                    'consignee_address_line3_limit' => 1,
                ),
                'consignee_district' => 60,
                'consignee_county' => 60,
                'consignee_company' => 100,
                'consignee_phone_limit' => 100
            );
            $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

            //配货信息
            $cargoItems = array();
            $totalWeight = 0;  //订单总重量
            foreach($order->items as $j=>$vitem)
            {
                if(empty($data['name'][$j])){
                    return self::getResult(1, '', '错误信息：货物英文名称必填！');
                }
                if(empty($data['count'][$j])){
                    return self::getResult(1, '', '错误信息：货物数量必填！');
                }
                if(empty($data['weight'][$j])){
                    return self::getResult(1, '', '错误信息：货物单位重量(g)必填！');
                }
                if(empty($data['amount'][$j])){
                    return self::getResult(1, '', '错误信息：货物单价必填！');
                }
                if(empty($data['currency'][$j])){
                    return self::getResult(1, '', '错误信息：货物单价的币别必填！');
                }

                $cargoItems[$j] = [
                    'name'          => empty($data['name'][$j]) ? '' : $data['name'][$j],       //货物名称（英文）
                    'count'        => empty($data['count'][$j]) ? '': $data['count'][$j],    //货物数量
                    'unit'        => 'piece' ,    //货物单位（英文）如：piece
                    'weight'     => empty($data['weight'][$j]) ? '' : round(($data['weight'][$j])/1000,2),//货物单位重量
                    'amount'     => empty($data['amount'][$j]) ? '': $data['amount'][$j],   //货物单价
                    'currency'   => empty($data['currency'][$j]) ? '': $data['currency'][$j],//货物单价的币别：USD: 美元
                    'source_area'     => 'CN',//原产地国简码（CN）
                    'cname'      =>empty($data['cname'][$j]) ? '' : $data['cname'][$j],
                ];

                $totalWeight += round(($data['weight'][$j])/1000,2) * $cargoItems[$j]['count'];  //总重量
            }

            //配货信息凑成XML
            $cargo = '';
            foreach($cargoItems as $value){
                $cargoParams = '';
                foreach ($value as $k => $v) {
                    $cargoParams .= ' '.$k.'="'.$v.'"';
                }
                $cargo .= '<Cargo '.$cargoParams.'></Cargo>';
            }

            //订单信息

            /**start 该物流商支持配送的国家简码对应的城市代码*/
            $cCode_to_dCode = array('RU'=>'MOW', 'LT'=>'VNO', 'LV'=>'RIX', 'EE'=>'TLL', 'SE'=>'ARN', 'FI'=>'HEL', 'BY'=>'MSQ', 'UA'=>'KBP', 'PL'=>'WAW','DE'=>'FRA','AU'=>'SYD','CH'=>'BRN','GB'=>'LHR','ID'=>'JKT','IE'=>'DUB','IN'=>'DEL','JP'=>'TYO','KR'=>'ICN','MY'=>'KUL','NO'=>'OSL','NZ'=>'WLG','SG'=>'SIN','TH'=>'BKK','BE'=>'BRU','FR'=>'CDG','AT'=>'AUT','LU'=>'LUX','NL'=>'NLD');
            foreach($cCode_to_dCode as $cCode => $dCode){
                if($order->consignee_country_code == $cCode){
                    $d_deliverycode = $dCode;
                    break;
                }
                else{$d_deliverycode = '';}
            }
            if(empty($d_deliverycode)){
                return self::getResult(1, '', '该物流商不支持该收件国家的配送！现在选的是:'.$order->consignee_country_code);
            }
            /**end 该物流商支持配送的国家简码对应的城市代码*/

            $d_company = empty($order->consignee_company)?$order->consignee:$order->consignee_company;
            $declared_value_currency = empty($data['declared_value_currency'])?'USD':$data['declared_value_currency'];
            $d_county = empty($order->consignee_county)?'':$order->consignee_county;
            $remark = empty($service['carrier_params']['remark'])?'':($service['carrier_params']['remark']);
            if( $remark=='no electric' ){
                $remark= 1;
            }else{
                $remark= 0;
            }

            $request_xml = '<Request service="OrderService" lang="zh-CN">
                                <Head>'.$this->clientCode.'</Head>
                                <Body>
                                    <Order
                                    orderid="'.$order->order_source_order_id.'"
                                    express_type="'.$service['shipping_method_code'].'"
                                    j_company="'.$highCopyPrint_senderAddress['company'].'"
                                    j_contact="'.$highCopyPrint_senderAddress['contact'].'"
                                    j_tel="'.$highCopyPrint_senderAddress['phone'].'"
                                    j_mobile="'.$highCopyPrint_senderAddress['mobile'].'"
                                    j_address="'.$highCopyPrint_senderAddress['street'].'"
                                    d_company="'.$d_company.'"
                                    d_contact="'.$order->consignee.'"
                                    d_tel="'.$order->consignee_phone.'"
                                    d_mobile="'.$order->consignee_mobile.'"
                                    d_address="'.$addressAndPhone['address_line1'].'"
                                    parcel_quantity="1"
                                    pay_method="1"
                                    j_province="'.$highCopyPrint_senderAddress['province'].'"
                                    j_city="'.$highCopyPrint_senderAddress['city'].'"
                                    d_province="'.$order->consignee_province.'"
                                    d_city="'.$order->consignee_city.'"
                                    declared_value="'.$data['declared_value'].'"
                                    declared_value_currency="'.$declared_value_currency.'"
                                    custid="'.$this->custid.'"
                                    j_country="'.$highCopyPrint_senderAddress['country'].'"
                                    j_county="'.$highCopyPrint_senderAddress['district'].'"
                                    j_shippercode="'.$highCopyPrint_senderAddress['country'].'"
                                    j_post_code="'.$highCopyPrint_senderAddress['postcode'].'"
                                    d_country="'.$order->consignee_country_code.'"
                                    d_county="'.$d_county.'"
                                    d_deliverycode="'.$d_deliverycode.'"
                                    d_post_code="'.$order->consignee_postal_code.'"
                                    cargo_total_weight="'.$totalWeight.'"
                                    isBat="'.$remark.'">
                                    '.$cargo.'
                                    </Order>
                                </Body>
                            </Request>';

            $verifyCode = base64_encode(md5($request_xml.$this->checkword,true));
            $responseXML = $this->soapClient->sfKtsService($request_xml,$verifyCode);
            $xml_to_str = simplexml_load_string($responseXML);
            $responseArr = json_decode(json_encode($xml_to_str),TRUE);

            \Yii::info("LB_SFELUOSICarrierAPI sfKtsService result:".print_r($responseXML,true), "carrier_api");// 先记下结果，记下refrence_no，这个返回应该与上面提交refrence_no一样。
            $track_num_message = '';
            $tracking_number = '';
            if($responseArr['Head'] == 'OK'){
                // dzt20190923 客户要求添加“提交平台用顺丰单号”
                $response = $responseArr['Body'][0]['@attributes'];
                
                //判断跟踪号是用服务商单号，还是顺丰单号
                $tracking_number = $tmp_is_use_mailno=='Y' ? $response['mailno'] : $response['agent_mailno'];
                
                //当没有跟踪号返回时，以顺丰单号为跟踪号
                $tracking_number = empty($tracking_number) ? $response['mailno'] : $tracking_number;
                    $track_num_message = '<br>服务商跟踪号：'.$tracking_number;
                
//                 if(isset($responseArr['Body'][0]['@attributes']['agent_mailno']) && !empty($responseArr['Body'][0]['@attributes']['agent_mailno'])){
//                     $tracking_number = $responseArr['Body'][0]['@attributes']['agent_mailno'];
//                     $track_num_message = '<br>服务商跟踪号：'.$tracking_number;
//                 }
                // else{$tracking_number = $responseArr['Body'][0]['@attributes']['mailno'];}

                $r = CarrierApiHelper::orderSuccess( $order , $service , $responseArr['Body'][0]['@attributes']['mailno'] , OdOrder::CARRIER_WAITING_PRINT , $tracking_number);
                return  self::getResult(0,$r, "操作成功! 顺丰单号：".$responseArr['Body'][0]['@attributes']['mailno'] .$track_num_message);
            }
            else{
                return self::getResult(1, '', '操作失败！错误信息：'.$responseArr['ERROR']);
            }
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
    public function cancelOrderNO($data){
        return self::getResult(1, '', '物流接口不支持取消物流单。');
    }

    /**
    +----------------------------------------------------------
     * 交运
    +----------------------------------------------------------
     **/
    public function doDispatch($data){
        return self::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');
    }

    /**
    +----------------------------------------------------------
     * 申请跟踪号
    +----------------------------------------------------------
     **/
    public function getTrackingNO($data){
        return self::getResult(1,'','该物流商不支持获取跟踪号');
    }

    /**
    +----------------------------------------------------------
     * 打单
    +----------------------------------------------------------
     **/
    public function doPrint($data){

        try {
            $user = \Yii::$app->user->identity;
            if (empty($user)) return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();

            //订单对象
            $order = current($data);reset($data);
            $order = $order['order'];

            $customer_number= array();
            $orderid= array();
            foreach( $data as $vd ){
                $order = $vd['order'];
                //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
                $checkResult = CarrierAPIHelper::validate(1, 1, $order);
                $shipped = $checkResult['data']['shipped'];

                $customer_number[]= $shipped['customer_number'];
                $orderid[]= $shipped['order_source_order_id'];
            }

            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];// 客户相关信息
            $account_api_params = $account->api_params;//获取到帐号中的认证参数
            $checkword = isset($account_api_params['checkword']) ? trim($account_api_params['checkword']):'';//密鈅（检验码）
            $username = isset($account_api_params['clientCode']) ? trim($account_api_params['clientCode']):'';//接入編碼

            $md5 = md5($username . $checkword, true);
            $signature = base64_encode($md5);

            $post= array();
            $post['orderid']= implode( ',',$orderid );
            $post['mailno']= implode( ',',$customer_number );
            $post['onepdf']= 'true';//false
            $post['jianhuodan']= 'false';
            $post['username']= $username;
            $post['signature'] =$signature;

            $url= 'http://sfapi.trackmeeasy.com/ruserver/api/getLabelUrl.action?'.http_build_query($post);
            $res= Helper_Curl::get( $url,null );
            $arr= json_decode($res,true);
            if( isset($arr['url']) ){
                return self::getResult(0,['pdfUrl'=>$arr['url']],'连接已生成,请点击并打印');
            }else{
                return ['error'=>1, 'filePath'=>'', 'msg'=>'打印失败！错误信息：接口返回内容不是一个有效的PDF'];
            }

        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }

    }


    /*
     * 用来确定打印完成后 订单的下一步状态
     * 公共方法
     */
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }

}
