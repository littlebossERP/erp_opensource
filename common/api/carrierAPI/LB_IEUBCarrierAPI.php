<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.littleboss.com All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: rice
+----------------------------------------------------------------------
| Create Date: 2015-08-06
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;

use common\helpers\Helper_Curl;
use common\helpers\Helper_xml;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
include '../components/PDFMerger/PDFMerger.php';

/**
 +------------------------------------------------------------------------------
 * 国际E邮宝接口业务逻辑类
 +--------------E----------------------------------------------------------------
 * @subpackage  Exception
 * @author		rice
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_IEUBCarrierAPI extends BaseCarrierAPI {

    protected static $token;

	public function __construct(){}

    /**
    +----------------------------------------------------------
     * 申请订单号
    +----------------------------------------------------------
     **/
    public function getOrderNO($data) {
    	return self::getResult(1, '', '该国际E邮宝已经停止使用，请开启国际E邮宝(新)');

        //订单对象
        $order_data = $data['order'];
        //表单提交的数据
        $form_data = $data['data'];
        
        $addressAndPhoneParams = array(
        		'address' => array(
        				'consignee_address_line1_limit' => 254,
        		),
        		'consignee_district' => 1,
        		'consignee_county' => 1,
        		'consignee_company' => 1,
        		'consignee_phone_limit' => 30
        );
        
        $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order_data, $addressAndPhoneParams);
        	
        //重复发货 添加不同的标识码
        $extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
        $customer_number = $data['data']['customer_number'];
        	
        if(isset($data['data']['extra_id'])){
        	if($extra_id == ''){
        		return self::getResult(1, '', '强制发货标识码，不能为空');
        	}
        }

        //对当前条件的验证，校验是否登录，校验是否已上传过
        $checkResult = CarrierAPIHelper::validate(1, 0, $order_data,$extra_id,$customer_number);

        //获取到所需要使用的数据（运输服务配置信息，物流商配置信息）
        $info = CarrierAPIHelper::getAllInfo($order_data);
        $s = $info['service'];
        $service_params = $s->carrier_params;
        $account = $info['account'];

        //获取到帐号中的认证参数
        $account_api_params = $account->api_params;
        //获取到账户中的地址信息
        $account_address = $account->address;

        //将用户token记录下来
        self::$token = $account_api_params['authenticate'];


        $post_data = ['orders'=>[]];

        $now = date('Y-m-d\Th:i:s');
//        var_dump($account_address);
////        var_dump($order_data);
//        exit;
        //基本信息
        $post_order = [];
        $post_order['orderid'] = $customer_number;
        $post_order['operationtype'] = $s->shipping_method_code;     //0:e邮宝 1:e包裹 2:e特快
        $post_order['producttype'] = ($s->shipping_method_code == 1) ? 2 : 0;         //1:国际包裹投递 2:国际包裹签收（e包裹此字段必填）
        $post_order['customercode'] = (strlen($order_data->source_buyer_user_id)<4) ? $order_data->consignee : $order_data->source_buyer_user_id;
        $post_order['vipcode'] = $account_api_params['vipcode'];
        $post_order['clcttype'] = ($service_params['clcttype'] === '0') ? 0 : 1;                                         //1:上门揽收 0:用户自送
        $post_order['pod'] = 'false';
        $post_order['untread'] = $service_params['untread']?$service_params['untread']:'Returned';                 //退回类型 Returned-退回 Abandoned-丢弃 AND I ASSURE TO PAY THE CHARGE OF RETURN-保证支付退回的邮
        $post_order['volweight'] = intval($form_data['length']*$form_data['width']*$form_data['height']/6000);
        $post_order['startdate'] = $now;
        $post_order['enddate'] = $now;
        $post_order['remark'] = $order_data->desc;
        $post_order['barcode'] = '';
        $post_order['printcode'] = $service_params['printcode']?$service_params['printcode']:'00';             //'00'-A4 '01'-4*4 '03'-4*6

        //法国没有省份，直接用城市来替换
        $tmpConsigneeProvince = $order_data->consignee_province;
        if(($order_data->consignee_country_code == 'FR') && empty($order_data->consignee_province)){
        	$tmpConsigneeProvince = $order_data->consignee_city;
        }
        
        //收件人信息
        $post_receiver = [];
        $post_receiver['name'] = $order_data->consignee;
        $post_receiver['postcode'] = $order_data->consignee_postal_code;
        $post_receiver['phone'] = $order_data->consignee_phone;
        $post_receiver['mobile'] = $order_data->consignee_mobile;
        $post_receiver['country'] = $order_data->consignee_country_code;
        $post_receiver['province'] = $tmpConsigneeProvince;
        $post_receiver['city'] = $order_data->consignee_city;
        $post_receiver['county'] = $order_data->consignee_district.$order_data->consignee_county;
        $post_receiver['company'] = $order_data->consignee_company;
        $post_receiver['street'] = $addressAndPhone['address_line1'];
        $post_receiver['email'] = $order_data->consignee_email;
        $post_order['receiver'] = $post_receiver;

        $post_data['orders']['order'] = $post_order;

        //发件人信息
        $sender = [];
        $sender['name'] = $account_address['shippingfrom']['contact'];
        $sender['postcode'] = $account_address['shippingfrom']['postcode'];
        $sender['phone'] = $account_address['shippingfrom']['phone'];
        $sender['mobile'] = $account_address['shippingfrom']['mobile'];
        $sender['country'] = $account_address['shippingfrom']['country'];
        $sender['province'] = $account_address['shippingfrom']['province'];
        $sender['city'] = $account_address['shippingfrom']['city'];
        $sender['county'] = $account_address['shippingfrom']['district'];
        $sender['company'] = $account_address['shippingfrom']['company'];
        $sender['street'] = $account_address['shippingfrom']['street'];
        $sender['email'] = $account_address['shippingfrom']['email'];
        $post_data['orders']['order']['sender'] = $sender;

        //揽货信息
        $collect = [];
        $collect['name'] = $account_address['pickupaddress']['contact'];
        $collect['postcode'] = $account_address['pickupaddress']['postcode'];
        $collect['phone'] = $account_address['pickupaddress']['phone'];
        $collect['mobile'] = $account_address['pickupaddress']['mobile'];
        $collect['country'] = $account_address['pickupaddress']['country'];
        $collect['province'] = $account_address['pickupaddress']['province'];
        $collect['city'] = $account_address['pickupaddress']['city'];
        $collect['county'] = $account_address['pickupaddress']['district'];
        $collect['company'] = $account_address['pickupaddress']['company'];
        $collect['street'] = $account_address['pickupaddress']['street'];
        $collect['email'] = $account_address['pickupaddress']['email'];
        $post_data['orders']['order']['collect'] = $collect;

        $post_items = [];
        //var_dump($order_data->items);exit;
        foreach($order_data->items as $index => $item) {
            $product = [];
            $product['cnname'] = $form_data['cnname'][$index];
            $product['enname'] = $form_data['enname'][$index];
            $product['count'] = $form_data['count'][$index];
            $product['unit'] = 'PCE';
            $product['weight'] = number_format($form_data['weight'][$index]/1000, 3, '.', '');
            $product['delcarevalue'] = number_format($form_data['delcarevalue'][$index] * $form_data['count'][$index], 2, '.', '');
            $product['origin'] = 'CN';
            $product['description'] = $form_data['cnname'][$index];
            $post_items[] = $product;
        }
        $post_data['orders']['order']['items']['item'] = $post_items;
        
//         print_r($post_data);
//         exit;

        $post_xml = Helper_xml::array2xml($post_data, true);
        $post_head = array('version:international_eub_us_1.1', 'authenticate:'.self::$token, 'Content-Type:text/xml;charset=UTF-8');
        $post_result = Helper_Curl::post('http://www.ems.com.cn/partner/api/public/p/order', $post_xml, $post_head);
        
        $tmp_result = $post_result;
        $user=\Yii::$app->user->identity;
        $puid = $user->getParentUid();
        \Yii::info('IEUB,puid:'.$puid.$tmp_result, "file");
        
        //$post_result = Helper_Curl::post('http://www.ems.com.cn/partner/api/public/p/validate', $post_xml, $post_head);
        $result = simplexml_load_string($post_result);
        if(property_exists($result, 'status')) {
            if((string)$result->status === 'error') {
                return self::getResult(1, '', (string)$result->description);
            }else if((string)$result->status === 'success') {
                return self::getResult(1, '', (string)$result->description);
            }
        }else if(property_exists($result, 'mailnum')) {
            //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
            $r = CarrierAPIHelper::orderSuccess($order_data, $s, (string)$result->mailnum, OdOrder::CARRIER_WAITING_PRINT, (string)$result->mailnum);
            return  self::getResult(0,$r, "操作成功!客户单号为：".(string)$result->mailnum);
        }

        return self::getResult(1, '', '参数异常');
    }


	/**
	+----------------------------------------------------------
	 * 取消跟踪号
	+----------------------------------------------------------
	 **/
	public function cancelOrderNO($data) {

        OdOrder::updateAll(array('carrier_step' => OdOrder::CARRIER_WAITING_PRINT), 'order_id in ('.$data['order']['order_id'].')');
        return self::getResult(1, '', '结果：该物流商API不支持取消订单功能');
        //$mail_num = 'LN156947362CN';
        //$post_head = array('version:international_eub_us_1.1', 'authenticate:hpx2013_cc9098c0c1093a3e9324bfc83e5383f3');
        //var_dump(Helper_Curl::delete("http://www.ems.com.cn/partner/api/public/p/order/{$mail_num}", null, $post_head));
	}



	/**
	+----------------------------------------------------------
	 * 交运
	+----------------------------------------------------------
	 **/
	public function doDispatch($data) {
        OdOrder::updateAll(array('carrier_step' => OdOrder::CARRIER_WAITING_PRINT), 'order_id in ('.$data['order']['order_id'].')');
        return self::getResult(1, '', '结果：该物流商API不支持交运订单功能');
    }


	/**
	+----------------------------------------------------------
	 * 申请跟踪号
	+----------------------------------------------------------
	 **/
	public function getTrackingNO($data) {

    }


	/**
	+----------------------------------------------------------
	 * 打单
	+----------------------------------------------------------
	 **/
    public function doPrint($data) {
        try{
            $user=\Yii::$app->user->identity;
            $puid = $user->getParentUid();
            $pdf = new \PDFMerger();
            
            $order = current($data);reset($data);
            $order = $order['order'];
            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];
            $service = $info['service'];
            
            //获取到帐号中的认证参数
            $account_api_params = $account->api_params;
            $carrier_params = $service->carrier_params;
            
            $post_data['orders']['printcode'] = empty($carrier_params['printcode']) ? '00' : $carrier_params['printcode'];
            $post_data['orders']['filetype'] = '0';
            
            $result = [];
            foreach ($data as $key => $value) {
                $order = $value['order'];
                
                $post_data['orders']['order'][] = array('mailnum'=>$order->customer_number);
                
                //添加订单标签打印时间
//                 $order->is_print_carrier = 1;
                $order->print_carrier_operator = $puid;
                $order->printtime = time();
                $order->save();
            }
            
            $post_xml = Helper_xml::array2xml($post_data, false);
            
            $authenticate = $account_api_params['authenticate'];
            $post_head = array('version:international_eub_us_1.1', 'authenticate:'.$authenticate, 'Content-Type:text/xml;charset=UTF-8');
            $post_result = Helper_Curl::post('http://www.ems.com.cn/partner/api/public/p/print/downloadLabels', $post_xml, $post_head);
             
            $post_result = simplexml_load_string($post_result);
            
            if(!isset($post_result->status)){
            	return self::getResult(1,'','线下E邮宝api返回错误');
            }
            
            if($post_result->status == 'error'){
            	return self::getResult(1,'',$post_result->description);
            }
            
            $response = Helper_Curl::get($post_result->url);
            
            if(strlen($response)<1000){
            	return self::getResult(1, '', 'E邮宝返回失败，请稍后重试');
            }
            
            $pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_', 0);
            $pdf->addPDF($pdfUrl['filePath'],'all');
            
            isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
            return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
            
            return self::getResult(0,['pdfUrl'=>$post_result->url],'连接已生成,请点击并打印');
        }catch(Exception $e) {
            return self::getResult(1,'',$e->getMessage());
        }
    }

    /*
     * 用来确定打印完成后 订单的下一步状态
    *
    * 公共方法
    */
    public static function getOrderNextStatus(){
    	return OdOrder::CARRIER_FINISHED;
    }

    public function getMailInfo($order_data) {
    	$return_data = array('error'=>1,'msg'=>'','data_info'=>array());
    	
    	//获取到所需要使用的数据（运输服务配置信息，物流商配置信息）
    	$info = CarrierAPIHelper::getAllInfo($order_data);
    	$s = $info['service'];
    	$service_params = $s->carrier_params;
    	$account = $info['account'];
    	
    	//获取到帐号中的认证参数
    	$account_api_params = $account->api_params;
    	
        $post_data = '';
        $post_head = array('version:international_eub_us_1.1', 'authenticate:'.$account_api_params['authenticate']);
        $result = Helper_Curl::get('http://www.ems.com.cn/partner/api/public/p/order/'.$order_data->customer_number, null, $post_head);
        
        $result = simplexml_load_string($result);
        
        $result = Helper_xml::simplexml2a($result);
        
        if(isset($result['status'])){
        	if($result['status'] == 'error'){
        		$return_data['msg'] = empty($result['description']) ? '接口返回失败e_1' : $result['description'];
        		return $return_data;
        	}
        }
        
        if(!isset($result['order'])){
        	$return_data['msg'] = empty($result['description']) ? '接口返回失败e_2' : $result['description'];
        	return $return_data;
        }
        
        $return_data['error'] = 0;
        $return_data['data_info'] = $result['order'];
        
        return $return_data;
    }
}
