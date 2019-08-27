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
use eagle\models\carrier\CarrierEubSelectcode;
// try{
// 	include dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'PDFMerger'.DIRECTORY_SEPARATOR.'PDFMerger.php';
// }catch(\Exception $e){	
// }

/**
 +------------------------------------------------------------------------------
 * 国际E邮宝接口业务逻辑类
 +--------------E----------------------------------------------------------------
 * @subpackage  Exception
 * @author		rice
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_IEUBNewCarrierAPI extends BaseCarrierAPI {

    protected static $token;
    protected static $tmp_debugging;

	public function __construct(){
		self::$tmp_debugging = false;
	}

    /**
    +----------------------------------------------------------
     * 申请订单号
    +----------------------------------------------------------
     **/
    public function getOrderNO($data) {
    	if(self::$tmp_debugging){
    		return self::getResult(1, '', '上传失败，国际EUB服务器返回错误，请稍后再试v!');
    	}
    	
    	$user=\Yii::$app->user->identity;
    	if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
    	$puid = $user->getParentUid();
    	
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
        
        if(empty($info['senderAddressInfo'])){
        	return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
        }

        //获取到帐号中的认证参数
        $account_api_params = $account->api_params;
        //获取到账户中的地址信息
        $account_address = $info['senderAddressInfo'];

        //将用户token记录下来
        self::$token = trim($account_api_params['authenticate']);

        if(!isset($form_data['desc'])){
        	$form_data['desc'] = $order_data->desc;
        }
        
        if(!isset($form_data['sku1'])){
        	$form_data['sku1'] = $order_data->desc;
        }
        
        if(!isset($form_data['length'])){
        	$form_data['length'] = '';
        	$form_data['width'] = '';
        	$form_data['height'] = '';
        }

        $post_data = ['orders'=>[]];

        $now = date('Y-m-d\Th:i:s');
//        var_dump($account_address);
////        var_dump($order_data);
//        exit;

        //客户编码
        $tmp_customercode = empty($account_api_params['customercode']) ? '' : $account_api_params['customercode'];
        if(empty($tmp_customercode)){
        	$tmp_customercode = (mb_strlen($order_data->source_buyer_user_id,'utf-8')<5) ? ((mb_strlen($order_data->consignee,'utf-8')<5) ? '<![CDATA['.$order_data->consignee.']]>'.'   ' : '<![CDATA['.$order_data->consignee.']]>') : '<![CDATA['.$order_data->source_buyer_user_id.']]>';
        }
        
        //退回类型
        $tmp_untread = $service_params['untread']?$service_params['untread']:'Returned';
//         $tmp_untread = 'Returned after 30 days';
        
        //基本信息
        $post_order = [];
        $post_order['orderid'] = $customer_number;
        $post_order['operationtype'] = $s->shipping_method_code;     //0:e邮宝 1:e包裹 2:e特快
        $post_order['producttype'] = ($s->shipping_method_code == 1) ? 2 : 0;         //1:国际包裹投递 2:国际包裹签收（e包裹此字段必填）
        $post_order['customercode'] = $tmp_customercode;
        $post_order['vipcode'] = $account_api_params['vipcode'];
        $post_order['clcttype'] = ($service_params['clcttype'] === '0') ? 0 : 1;                                         //1:上门揽收 0:用户自送
        $post_order['pod'] = 'false';
        $post_order['untread'] = $tmp_untread;                 //退回类型 Returned-退回 Abandoned-丢弃 AND I ASSURE TO PAY THE CHARGE OF RETURN-保证支付退回的邮
        $post_order['volweight'] = intval($form_data['length']*$form_data['width']*$form_data['height']/6000);
        $post_order['startdate'] = $now;
        $post_order['enddate'] = $now;
        $post_order['remark'] = '<![CDATA['.$form_data['desc'].']]>';
        $post_order['sku1'] = empty($form_data['sku1']) ? '' : '<![CDATA['.$form_data['sku1'].']]>';
        $post_order['barcode'] = '';
        $post_order['printcode'] = $service_params['printcode']?$service_params['printcode']:'00';             //'00'-A4 '01'-4*4 '03'-4*6

        //法国没有省份，直接用城市来替换
        $tmpConsigneeProvince = $order_data->consignee_province;
        if(empty($tmpConsigneeProvince)){
        	$tmpConsigneeProvince = $order_data->consignee_city;
        }
        
        //amazon 日本站订单城市为空时直接用省份代替
        $tmp_consignee_city = $order_data->consignee_city;
        if(empty($tmp_consignee_city) && ($order_data->consignee_country_code == 'JP')){
        	$tmp_consignee_city = $tmpConsigneeProvince;
        }
        
        if(empty($tmp_consignee_city)){
        	return self::getResult(1,'','收件人城市不能为空。');
        }
        
//         //加认证参数设置确定省份为空时是否使用城市来代替
//         if(empty($tmpConsigneeProvince) && (!empty($account_api_params['provinces_empty_use_city']))){  //省份为空时使用城市
//         	if($account_api_params['provinces_empty_use_city'] == 'Y'){ 
//         		$tmpConsigneeProvince = $order_data->consignee_city;
//         	}
//         }
        
        if(empty($tmpConsigneeProvince)){
        	return self::getResult(1,'','收件人省份不能为空。');
        }
        
        $tmp_mobile = $order_data->consignee_mobile;
        if(empty($tmp_mobile) && ($order_data->consignee_country_code == 'UA')){
        	$tmp_mobile =  $addressAndPhone['phone1'];
        }
        
        //收件人信息
        $post_receiver = [];
        $post_receiver['name'] = '<![CDATA['.$order_data->consignee.']]>';
        $post_receiver['postcode'] = trim($order_data->consignee_postal_code);
//         $post_receiver['phone'] = $order_data->consignee_phone;
        $post_receiver['phone'] = $addressAndPhone['phone1'];
        $post_receiver['mobile'] = $tmp_mobile;
        $post_receiver['country'] = ($order_data->consignee_country_code == 'UK')?'GB':$order_data->consignee_country_code;
        $post_receiver['province'] = '<![CDATA['.$tmpConsigneeProvince.']]>';
        $post_receiver['city'] = $tmp_consignee_city;
        $post_receiver['county'] = $order_data->consignee_district.$order_data->consignee_county;
        $post_receiver['company'] = '<![CDATA['.$order_data->consignee_company.']]>';
        $post_receiver['street'] = '<![CDATA['.$addressAndPhone['address_line1'].']]>';
        $post_receiver['email'] = '<![CDATA['.$order_data->consignee_email.']]>';
        $post_order['receiver'] = $post_receiver;

        $post_data['orders']['order'] = $post_order;

        //发件人信息
        $sender = [];
        $sender['name'] = empty($account_address['shippingfrom']['contact_en']) ? '<![CDATA['.$account_address['shippingfrom']['contact'].']]>' : '<![CDATA['.$account_address['shippingfrom']['contact_en'].']]>';
        $sender['postcode'] = '<![CDATA['.$account_address['shippingfrom']['postcode'].']]>';
        $sender['phone'] = '<![CDATA['.$account_address['shippingfrom']['phone'].']]>';
        $sender['mobile'] = '<![CDATA['.$account_address['shippingfrom']['mobile'].']]>';
        $sender['country'] = '<![CDATA['.$account_address['shippingfrom']['country'].']]>';
        $sender['province'] = '<![CDATA['.$account_address['shippingfrom']['province'].']]>';
        $sender['city'] = '<![CDATA['.$account_address['shippingfrom']['city'].']]>';
        $sender['county'] = '<![CDATA['.$account_address['shippingfrom']['district'].']]>';
        $sender['company'] = empty($account_address['shippingfrom']['company_en']) ? '<![CDATA['.$account_address['shippingfrom']['company'].']]>' : '<![CDATA['.$account_address['shippingfrom']['company_en'].']]>';
        $sender['street'] = empty($account_address['shippingfrom']['street_en']) ? '<![CDATA['.$account_address['shippingfrom']['street'].']]>' : '<![CDATA['.$account_address['shippingfrom']['street_en'].']]>';
        $sender['email'] = $account_address['shippingfrom']['email'];
        $post_data['orders']['order']['sender'] = $sender;

        //揽货信息
        $collect = [];
        $collect['name'] = '<![CDATA['.$account_address['pickupaddress']['contact'].']]>';
        $collect['postcode'] = '<![CDATA['.$account_address['pickupaddress']['postcode'].']]>';
        $collect['phone'] = '<![CDATA['.$account_address['pickupaddress']['phone'].']]>';
        $collect['mobile'] = '<![CDATA['.$account_address['pickupaddress']['mobile'].']]>';
        $collect['country'] = '<![CDATA['.$account_address['pickupaddress']['country'].']]>';
        $collect['province'] = '<![CDATA['.$account_address['pickupaddress']['province'].']]>';
        $collect['city'] = '<![CDATA['.$account_address['pickupaddress']['city'].']]>';
        $collect['county'] = '<![CDATA['.$account_address['pickupaddress']['district'].']]>';
        $collect['company'] = '<![CDATA['.$account_address['pickupaddress']['company'].']]>';
        $collect['street'] = '<![CDATA['.$account_address['pickupaddress']['street'].']]>';
        $collect['email'] = '<![CDATA['.$account_address['pickupaddress']['email'].']]>';
        $post_data['orders']['order']['collect'] = $collect;

        $post_items = [];
        //var_dump($order_data->items);exit;
        foreach($order_data->items as $index => $item) {
        	//数量为0的不上传给货代
        	if(empty($item->quantity)){
        		continue;
        	}
        	
            $product = [];
            $product['cnname'] = '<![CDATA['.$form_data['chName'][$index].']]>';
            $product['enname'] = '<![CDATA['.$form_data['enName'][$index].']]>';
            $product['count'] = $item->quantity;
            $product['unit'] = (empty($form_data['declaredUnit'][$index]) ? '' : $form_data['declaredUnit'][$index]);	//接口升级，之前默认赋值为PCE，但是现在可以默认为空，先试试 20170817 hqw
            $product['weight'] = number_format($form_data['declaredWeight'][$index]/1000, 3, '.', '');
            $product['delcarevalue'] = number_format($form_data['declaredValue'][$index] * $item->quantity, 2, '.', '');
            $product['origin'] = 'CN';
            $product['description'] = '<![CDATA['.$form_data['chName'][$index].']]>';
            $post_items[] = $product;
        }
        $post_data['orders']['order']['items']['item'] = $post_items;
        
//         print_r($post_data);die;
//         exit;

        $post_xml = Helper_xml::array2xml($post_data, true);
        $post_head = array('version:international_eub_us_1.1', 'authenticate:'.self::$token, 'Content-Type:text/xml;charset=UTF-8');
        if( $puid=='19941' ){
            //print_r ($post_head);exit;
        }
        $post_result = Helper_Curl::post('http://shipping.ems.com.cn/partner/api/public/p/order', $post_xml, $post_head);
        
        
        $tmp_result = $post_result;
        $user=\Yii::$app->user->identity;
        $puid = $user->getParentUid();
        
        \Yii::info('IEUB,puid:'.$puid.',request,order_id:'.$order_data->order_id.' '.$post_xml,"carrier_api");
        \Yii::info('IEUB,puid:'.$puid.',result,order_id:'.$order_data->order_id.' '.$tmp_result,"carrier_api");
        
        //$post_result = Helper_Curl::post('http://shipping2.ems.com.cn/partner/api/public/p/validate', $post_xml, $post_head);
        
        try {
        	$result = simplexml_load_string($post_result);
        }catch (\Exception $ex){
			return self::getResult(1, '', '上传失败，国际EUB服务器返回错误，请稍后再试!');
		}
        
        if(property_exists($result, 'status')) {
            if((string)$result->status === 'error') {
                return self::getResult(1, '', (string)$result->description);
            }else if((string)$result->status === 'success') {
                return self::getResult(1, '', (string)$result->description);
            }
        }else if(property_exists($result, 'mailnum')) {
            //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
            $r = CarrierAPIHelper::orderSuccess($order_data, $s, (string)$result->mailnum, OdOrder::CARRIER_WAITING_PRINT, (string)$result->mailnum);
            
            //组织数据start，供getCarrierLabelApiPdf使用
            $print_param = array();
            $print_param['carrier_code'] = $s->carrier_code;
            $print_param['api_class'] = 'LB_IEUBNewCarrierAPI';
            $print_param['authenticate'] = trim($account_api_params['authenticate']);
            $print_param['mailnum'] = (string)$result->mailnum;
            
            try{
            	CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order_data->order_id, (string)$result->mailnum, $print_param);
            }catch (\Exception $ex){
            }
			//组织数据end            
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

//         OdOrder::updateAll(array('carrier_step' => OdOrder::CARRIER_WAITING_PRINT), 'order_id in ('.$data['order']['order_id'].')');
        return self::getResult(1, '', '结果：该物流商API不支持取消订单功能');
        //$mail_num = 'LN156947362CN';
        //$post_head = array('version:international_eub_us_1.1', 'authenticate:hpx2013_cc9098c0c1093a3e9324bfc83e5383f3');
        //var_dump(Helper_Curl::delete("http://shipping2.ems.com.cn/partner/api/public/p/order/{$mail_num}", null, $post_head));
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
    	if(self::$tmp_debugging){
    		return self::getResult(1, '', '打印失败，国际EUB服务器返回错误，请稍后再试v!');
    	}
    	
        try{
            $user=\Yii::$app->user->identity;
            $puid = $user->getParentUid();
//             $pdf = new \PDFMerger();
            
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
            
            $authenticate = trim($account_api_params['authenticate']);
            $post_head = array('version:international_eub_us_1.1', 'authenticate:'.$authenticate, 'Content-Type:text/xml;charset=UTF-8');
            $post_result = Helper_Curl::post('http://shipping.ems.com.cn/partner/api/public/p/print/downloadLabels', $post_xml, $post_head);
           	$tmp_post_result = $post_result;
             
            try {
            	$post_result = simplexml_load_string($post_result);
            }catch (\Exception $ex){
            	\Yii::info('IEUB,print,puid:'.$puid.',result:'.' '.$tmp_post_result,"carrier_api");
            	return self::getResult(1, '', '打印失败，国际EUB服务器返回错误，请稍后再试!');
            }
            
            if(!isset($post_result->status)){
            	return self::getResult(1,'','线下E邮宝api返回错误');
            }
            
            if($post_result->status == 'error'){
            	return self::getResult(1,'',$post_result->description);
            }
            
            if($post_result->status == 'fail'){
            	return self::getResult(1,'',$post_result->description);
            }
            
//             $response = Helper_Curl::get($post_result->url);
            
//             if(strlen($response)<1000){
//             	return self::getResult(1, '', 'E邮宝返回失败，请稍后重试');
//             }
            
//             $pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_', 0);
//             $pdf->addPDF($pdfUrl['filePath'],'all');
            
//             isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
//             return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
            
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
    	
//     	if(self::$tmp_debugging){
    		$return_data['msg'] = '异常';
        	return $return_data;
//     	}
    
    	//获取到所需要使用的数据（运输服务配置信息，物流商配置信息）
    	$info = CarrierAPIHelper::getAllInfo($order_data);
    	$s = $info['service'];
    	$service_params = $s->carrier_params;
    	$account = $info['account'];
    	
    	//获取到帐号中的认证参数
    	$account_api_params = $account->api_params;
    	
        $post_data = '';
        $post_head = array('version:international_eub_us_1.1', 'authenticate:'.trim($account_api_params['authenticate']));
        $result = Helper_Curl::get('http://shipping.ems.com.cn/partner/api/public/p/order/'.$order_data->customer_number, null, $post_head);

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
//     	if(self::$tmp_debugging){
//     		return ['error'=>1, 'msg'=>'打印失败，国际EUB服务器返回错误，请稍后再试v!', 'filePath'=>''];
//     	}
    	
    	try {
    		$puid = $SAA_obj->uid;
    
    		$post_data['orders']['printcode'] = '01';//'00'=>A4,'01'=>4*4,'03'=>4*6,
    		$post_data['orders']['filetype'] = '0';
    
    		$result = [];
    		$post_data['orders']['order'][] = array('mailnum'=>$SAA_obj->customer_number);
    
    		$post_xml = Helper_xml::array2xml($post_data, false);
    
    		$authenticate = trim($print_param['authenticate']);
    		$post_head = array('version:international_eub_us_1.1', 'authenticate:'.$authenticate, 'Content-Type:text/xml;charset=UTF-8');
    		$post_result = Helper_Curl::post('http://shipping.ems.com.cn/partner/api/public/p/print/downloadLabels', $post_xml, $post_head);
    		 
    		try {
    			$post_result = simplexml_load_string($post_result);
    		}catch (\Exception $ex){
    			return ['error'=>1, 'msg'=>'打印失败，国际EUB服务器返回错误，请稍后再试!', 'filePath'=>''];
    		}
    
    		if(!isset($post_result->status)){
    			return ['error'=>1, 'msg'=>'线下E邮宝api返回错误', 'filePath'=>''];
    		}
    
    		if($post_result->status == 'error'){
    			return ['error'=>1, 'msg'=>$post_result->description, 'filePath'=>''];
    		}
    
    		//国际EUB访问速度不能过快，而且Headers信息不能被Frame包住
    		usleep(300000);
    		$response = Helper_Curl::get($post_result->url);
    
    		if(strlen($response)<1000){
    			return ['error'=>1, 'msg'=>'E邮宝返回失败，请稍后重试', 'filePath'=>''];
    		}
    		$pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
    
    		return $pdfPath;
    
    	}catch (CarrierException $e){
    		return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
    	}
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
//     public function getVerifyCarrierAccountInformation($data){
//     	$result = array('is_support'=>0,'error'=>1);
//     	return $result;
//     	try{
    			
//     		$post_head = array('version:international_eub_us_1.1', 'authenticate:'.trim($data['authenticate']));
//     		$res = Helper_Curl::get('http://shipping.ems.com.cn/partner/api/public/p/area/cn/province/list', null, $post_head);
    		
//     		$res = simplexml_load_string($res);
    		
//     		if(!isset($res->status) || $res->status != 'error'){
//     			$result['error'] = 0;
//     		}
//     	}catch(CarrierException $e){
//     	}
    
//     	return $result;
//     }
    
    
    /**
     * @param $authenticate				认证参数
     * @param $shipping_method_code		对应业务类型  0表示E邮宝，1表示E包裹
     * @param $postcode					邮编
     * @param $country					寄达国家（ 填国家简码）
     */
    public static function getCountrySelectCode($shipping_method_code, $postcode, $country, $authenticate = 'pdfTest_dhfjh98983948jdf78475fj65375fjdhfj'){
    	$result_code = array('error'=>false, 'codenum'=>'', 'msg'=>'');
    	
    	if(self::$tmp_debugging){
    		$result_code['error'] = true;
    		$result_code['msg'] = '网络访问异常';
    		return $result_code;
    	}
    	
    	$postcode = trim($postcode);
    	
    	//先尝试获取数据库的code 每隔1个小时更新一次
    	$eubSelectcode = CarrierEubSelectcode::find()->where(['producttype'=>$shipping_method_code, 'postcode'=>$postcode, 'country'=>$country])->one();
    	
    	//是否需要获取新的分拣码
    	$is_update_code = false;
    	
    	if($eubSelectcode == null){
    		$eubSelectcode = new CarrierEubSelectcode();
    		$eubSelectcode->bef_update_date = time();
    		$eubSelectcode->aft_update_date = time();
    		
    		$eubSelectcode->producttype = $shipping_method_code;
    		$eubSelectcode->postcode = $postcode;
    		$eubSelectcode->country = $country;
    		
    		$is_update_code = true;
    	}else{
    		if($eubSelectcode->aft_update_date+3600 <= time()){
    			$is_update_code = true;
    			$eubSelectcode->bef_update_date = $eubSelectcode->aft_update_date;
    			$eubSelectcode->aft_update_date = time();
    		}
    	}
    	
    	if($is_update_code == true){
	    	$post_data = array(
	    			'producttype'=>$shipping_method_code,
	    			'postcode'=>$postcode,	//邮编
	    			'country'=>$country	//寄达国家（ 填国家简码）
	    	);
	    	
	    	$post_xml = Helper_xml::array2xml($post_data, false);
	    	
	    	$post_xml = '<code xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.$post_xml.'</code>';
	    	
    		$post_head = array('version:international_eub_us_1.1', 'authenticate:'.$authenticate, 'Content-Type:text/xml;charset=UTF-8');
    		$result_xml = Helper_Curl::post('http://shipping.ems.com.cn/partner/api/public/p/selectcode', $post_xml, $post_head);
    		
    		if(Helper_Curl::$last_post_info['http_code'] == 200){
    			try {
    				$result = simplexml_load_string($result_xml);
    				
    				if($result->status == 'success'){
    					$tmp_codenum = (string)$result->codenum;
    					
    					if($tmp_codenum == 'FYYXGA'){
    						$tmp_codenum = '';
    					}
    					$eubSelectcode->codenum = $tmp_codenum;
    				}
    			}catch (\Exception $ex){
    				$result_code['error'] = true;
    				$result_code['msg'] = $ex->getMessage();
    				return $result_code;
    			}
    			
    			$eubSelectcode->save(false);
    		}else{
    			$result_code['error'] = true;
    			$result_code['msg'] = '网络访问异常'.Helper_Curl::$last_post_info['http_code'];
    			return $result_code;
    		}
    	}
    	
    	$result_code['codenum'] = $eubSelectcode->codenum;
    	return $result_code;
    }
    
    public static $eub_unit = array('台'=>'台','座'=>'座','辆'=>'辆','艘'=>'艘','架'=>'架','套'=>'套','个'=>'个','只'=>'只','头'=>'头','张'=>'张','件'=>'件','支'=>'支','枝'=>'枝','根'=>'根','条'=>'条','把'=>'把','块'=>'块','卷'=>'卷','副'=>'副','片'=>'片','组'=>'组','份'=>'份','幅'=>'幅','双'=>'双','对'=>'对','棵'=>'棵','株'=>'株','井'=>'井','米'=>'米','盘'=>'盘','平方米'=>'平方米','立方米'=>'立方米','筒'=>'筒','千克'=>'千克','克'=>'克','盆'=>'盆','万个'=>'万个','具'=>'具','百副'=>'百副','百支'=>'百支','百把'=>'百把','百个'=>'百个','百片'=>'百片','刀'=>'刀','疋'=>'疋','公担'=>'公担','扇'=>'扇','百枝'=>'百枝','千只'=>'千只','千块'=>'千块',
		'千盒'=>'千盒','千枝'=>'千枝','千个'=>'千个','亿支'=>'亿支','亿个'=>'亿个','万套'=>'万套','千张'=>'千张','万张'=>'万张','千伏安'=>'千伏安','千瓦'=>'千瓦','千瓦时'=>'千瓦时','千升'=>'千升','英尺'=>'英尺','吨'=>'吨','长吨'=>'长吨','短吨'=>'短吨','司马担'=>'司马担','司马斤'=>'司马斤','斤'=>'斤','磅'=>'磅','担'=>'担','英担'=>'英担','短担'=>'短担','两'=>'两','市担'=>'市担','盎司'=>'盎司','克拉'=>'克拉','市尺'=>'市尺','码'=>'码','英寸'=>'英寸','寸'=>'寸','升'=>'升','毫升'=>'毫升','英加仑'=>'英加仑','美加仑'=>'美加仑','立方英尺'=>'立方英尺','立方尺'=>'立方尺','平方码'=>'平方码','平方英尺'=>'平方英尺','平方尺'=>'平方尺','英制马力'=>'英制马力','公制马力'=>'公制马力','令'=>'令','箱'=>'箱','批'=>'批','罐'=>'罐','桶'=>'桶','扎'=>'扎','包'=>'包','箩'=>'箩','打'=>'打','筐'=>'筐','罗'=>'罗','匹'=>'匹','册'=>'册','本'=>'本','发'=>'发','枚'=>'枚','捆'=>'捆','袋'=>'袋','粒'=>'粒','盒'=>'盒','合'=>'合','瓶'=>'瓶','千支'=>'千支','万双'=>'万双','万粒'=>'万粒','千粒'=>'千粒','千米'=>'千米','千英尺'=>'千英尺','百万贝可'=>'百万贝可','部'=>'部');
}
