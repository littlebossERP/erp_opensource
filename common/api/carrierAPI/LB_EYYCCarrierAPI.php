<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\order\models\OdOrderShipped;
use Jurosh\PDFMerge\PDFMerger;

class LB_EYYCCarrierAPI extends BaseCarrierAPI{


    public $customer_userid = null;			//登录人ID
    public $customer_id = null;				//客户ID

    public function __construct(){

    }
    
    /**
    +----------------------------------------------------------
     * 申请订单号
    +----------------------------------------------------------
     * log			name		date				note
     * @author		dwg		 2015/11/19		       初始化
    +----------------------------------------------------------
     **/
     public function getOrderNO($data){
         try{
             $order = $data['order'];  //object OdOrder
//             print_r($order);exit;
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
             $info = CarrierAPIHelper::getAllInfo($order);
             $account = $info['account'];
             $Service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码


             //认证参数
             $params=$account->api_params;
             $login_link='http://121.43.115.82:8082/selectAuth.htm?username='.$params['username'].'&password='.$params['password'];
             $login_message = Helper_Curl::post($login_link);

             //转换为json格式
             $change = str_replace("'", '"', $login_message);
             $respond = json_decode($change,true);

             if($respond['ack']=='true'&&!empty($respond['customer_id'])&&!empty($respond['customer_userid'])) {
                 $sum = count($form_data['invoice_title']);//统计商品数量
                 for ($i = 0; $i < $sum; $i++)
                 {
                     if (!empty($form_data['invoice_pcs'][$i])) {
                         $invoice_pcs = $form_data['invoice_pcs'][$i];//必填且要为数字
                     } else {
                        //   throw new CarrierException("数量不能为空！");
                         return self::getResult(1, '', '数量不能为空');
                     }
                     $TitleCn = $form_data['sku'][$i]; //sku,如果是e邮宝，e特快，e包裹则传中文品名;中文品名
                     $TitleEn = $form_data['invoice_title'][$i]; //商品名，必填
                     if (empty($TitleEn)) {
                        //  throw new CarrierException("英文报关名不能为空！");
                         return self::getResult(1, '', '英文报关名不能为空');
                     }
                     if (empty($TitleCn)) {
                        // throw new CarrierException("中文报关名不能为空！");
                         return self::getResult(1, '', '中文报关名不能为空');
                     }
                     if (mb_strlen($TitleCn, 'utf-8') > 64) {
                        //  throw new CarrierException("中文报关名过长,长度不能超过64");//检查中文报关名的长度
                         return self::getResult(1, '', '中文报关名过长,长度不能超过64');
                     }
                     if (!empty($form_data['invoice_weight'][$i])) {
                         $invoice_weight = $form_data['invoice_weight'][$i] / 1000;//必填,单位kg
                     } else {
                        //  throw new CarrierException("报关重量不能为空！");
                         return self::getResult(1, '', '报关重量不能为空');
                     }
                     if (!empty($form_data['invoice_amount'][$i])) {
                         $invoice_amount = $form_data['invoice_amount'][$i];//必填且要为数字
                     } else {
                        //  throw new CarrierException("报关价值不能为空！");
                         return self::getResult(1, '', '报关价值不能为空');
                     }

                     $goods[] =
                         [
                             'invoice_amount' => $invoice_amount, //申报价值，必填
                             'invoice_pcs' => $invoice_pcs, //件数，必填
                             'invoice_title' => $TitleEn, //商品名，必填
                             'invoice_weight' => $invoice_weight, //单件重
                             'item_id' => '',
                             'item_transactionid' => '',
                             'sku' => $TitleCn, //sku,如果是e邮宝，e特快，e包裹则传中文品名;中文品名
                             'sku_code' => $form_data['sku_code'][$i], //配货信息
                         ];
                 }
                 //地址
                 
                 $addAddressInfo = (empty($order->consignee_company)?'':';'.$order->consignee_company).(empty($order->consignee_county) ? '' : ';'.$order->consignee_county).
                 (empty($order->consignee_district) ? '' : ';'.$order->consignee_district);
                 
//                 $address = (empty($order->consignee_address_line1)?'':$order->consignee_address_line1).(empty($order->consignee_address_line2)?'':$order->consignee_address_line2).(empty($order->consignee_address_line3)?'':$order->consignee_address_line3).$addAddressInfo;//收件地址街道，必填
                 /*** start 地址1,2,3合并 **/
                 $consigneeStreet='';
                 if(!empty($order->consignee_address_line1))
                     $consigneeStreet = $order->consignee_address_line1;
                 if(!empty($order->consignee_address_line2))
                     $consigneeStreet .=' '. $order->consignee_address_line2;
                 if(!empty($order->consignee_address_line3))
                     $consigneeStreet .=' '. $order->consignee_address_line3;
                 /*** end 地址1,2,3合并 **/

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

                 //没有州/省填，则以城市为准
                 $tmpConsigneeProvince = $order->consignee_province;
                 if (empty($tmpConsigneeProvince))
                 {
                 	if($order->consignee_country_code == 'FR')
                 		$tmpConsigneeProvince = $order->consignee_city;
                 	else if(!empty($order->consignee_city))
                 		$tmpConsigneeProvince = $order->consignee_city;
                 }
                 
                 //获取联系方式
                 $phoneContact = '0000000000';
                 if( !empty($order->consignee_phone) && strlen($order->consignee_phone) > 9)
                 	$phoneContact = $order->consignee_phone;
                 else if( !empty($order->consignee_mobile) && strlen($order->consignee_mobile) > 9)
                 	$phoneContact = $order->consignee_mobile;

                 $param=array();
                 $param =
                     [
                         'buyerid'=>'',
//                         'consignee_address'=>$consigneeStreet, //收件地址街道，必填
                         'consignee_address'=>$addressAndPhone['address_line1'], //收件地址街道，必填
                         'consignee_city'=>$order->consignee_city,   //城市
//                         'consignee_mobile'=>$order->consignee_mobile, //
                         'consignee_mobile'=>$addressAndPhone['phone1'], //
                         'consignee_name'=>$order->consignee, //收件人,必填
                         'trade_type'=>'ZYXT', //必填，自身系统代码，客户自用系统
                         'consignee_postcode'=>$order->consignee_postal_code, //邮编，有邮编的国家必填
                         'consignee_state'=>$tmpConsigneeProvince, //州/省
                         'consignee_telephone'=>$phoneContact, //收件电话，必填
                         'country'=>$order->consignee_country_code, //收件国家二字代码，必填
                         'customer_id'=>$respond['customer_id'], //根据帐号，密码返回回来的识别码，客户ID，必填
                         'customer_userid'=>$respond['customer_userid'], //根据帐号，密码返回回来的识别码，登录人ID，必填
                         'order_customerinvoicecode'=>$customer_number, //原单号，必填
                         'product_id'=>$Service->shipping_method_code, //运输方式ID，必填
                         'product_imagepath'=>'', //图片地址，多图片地址用分号隔开
                     ];
                 $total_weight = 0;

                 $param['weight']=''; //总重，选填，如果sku上有单重可不填该项
                 $param['orderInvoiceParam'] = $goods;
                 $order_respond = Helper_Curl::post('http://121.43.115.82:8082/createOrderApi.htm', array('param'=>json_encode($param)));

                 if (empty($order_respond)){
                     return self::getResult(1,'','操作失败,e裕云仓返回错误');
                 }
                 //$order_response = urldecode($order_respond);
                 $result=json_decode($order_respond,true);
                 $message = urldecode( $result['message']);

//                 print_r($result);exit;

                 //分析返回结果###############################################################
                 //无异常
                 //返回的order_id为物流商内部标识，需要在打印物流单时用到，暂时保存于CarrierAPIHelper::orderSuccess的return_no参数中；
                 if( strtolower( $result['ack']) == 'true' && (strtolower($message) == 'success' || $message == '') && !empty($result['tracking_number']))
                 {
                     $r = CarrierAPIHelper::orderSuccess($order,$Service,$result['reference_number'],OdOrder::CARRIER_WAITING_PRINT,$result['tracking_number'],['OrderSign'=>$result['order_id']]);
                     return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$result['reference_number'].',运单号:'.$result['tracking_number'].'');

                 }
                 //返回异常，再判断是否属于无法立即获得运单号的运输服务(如DHL,UPS之类)
                 else
                 {
                         if( strtolower( $result['ack']) == 'true' && stripos(' '.$message, '无法获取转单号') != false)
                         {
                             $r = CarrierAPIHelper::orderSuccess($order,$Service,$result['reference_number'],OdOrder::CARRIER_WAITING_DELIVERY,'',['OrderSign'=>$result['order_id']]);
                             return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$result['reference_number'].',该货代的此种运输服务暂时没有运单号提供,请在 "物流模块->物流操作状态->待交运" 中最终确认能否获取运单号');
                         }
                         else{
    //                         return  BaseCarrierAPI::getResult(1,'','上传失败！可再次编辑后重新上传直至返货成功提示。'.'<br>错误信息：'.$result['message'].'<br>该单已经存于e裕云仓后台的“草稿单”中,客户单号为:'.$result['reference_number'].'你也可到e裕云仓后台完善订单');
                             return  BaseCarrierAPI::getResult(1,'','上传失败！错误信息：'.$message);
    
                         }
                 }
             }
             else {
                 return self::getResult(1,'','身份验证失败');
             }
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
         return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');
     }



    /**
    +----------------------------------------------------------
     * 交运
    +----------------------------------------------------------
     * log			name		date				note
     * @author		dwg		 2015/11/19			   初始化
    +----------------------------------------------------------
     **/
     public function doDispatch($data){
         try{

             //订单对象
             $order = $data['order'];

//             print_r($order->order_id);exit;
             //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
             $checkResult = CarrierAPIHelper::validate(0,1,$order);
             $shipped = $checkResult['data']['shipped'];

             //获取到所需要使用的数据
             $info = CarrierAPIHelper::getAllInfo($order);
             $account = $info['account'];

             //获取到帐号中的认证参数
             $a = $account->api_params;

             $header=array();
             $header[]='Content-Type:text/xml;charset=utf-8';
             //账号认证,获取账号信息
             $selectAuthUrl = 'http://121.43.115.82:8082/selectAuth.htm'.'?username='.$a['username'].'&password='.$a['password'];
             $auth = Helper_Curl::get($selectAuthUrl,[],$header);
             $auth = str_replace('\'', '"', $auth);
//             $auth = str_replace("'", '"', $auth);
             if(!empty($auth)){
                 $auth = json_decode($auth);
                 if(isset($auth->ack) && $auth->ack=='true'){
                     $this->customer_userid = $auth->customer_userid;
                     $this->customer_id = $auth->customer_id;
                 }
             }else
                 return self::getResult(1, '', 'e裕云仓账户验证失败,e001');

             if(empty($this->customer_userid) || empty($this->customer_id) )
                 return self::getResult(1, '', 'e裕云仓账户验证失败,e002');

             $order_customerinvoicecode = $order->customer_number;
             $postOrderUrl = 'http://121.43.115.82:8082/postOrderApi.htm'.'?customer_id='.$this->customer_id.'&order_customerinvoicecode='.$order_customerinvoicecode;

             $response = Helper_Curl::get($postOrderUrl,[],$header);
             //如果是错误信息
             if($response == 'false'){
                 return BaseCarrierAPI::getResult(1, '', '结果：交运失败');
             }else if($response == 'true'){
                 $N=OdOrderShipped::findOne(['order_id'=>$order->order_id]);
//                 print_r($N);exit;
                 if($N<>null && $N->tracking_number!==$N->customer_number && !empty($N->tracking_number)){//我们平台判断e裕云仓tracking_number是否有效：tracking_number!==customer_number
                     $tracking_number = $N->tracking_number;
                     $order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
                     $order->save();
                     return BaseCarrierAPI::getResult(0, '', '订单交运成功！已生成运单号：'.$tracking_number);
                 }else{
                     $order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
                     $order->save();
//                     return BaseCarrierAPI::getResult(0, '', '订单交运成功！该货代的此种运输服务无法立刻获取运单号,您可以打印标签交付货代后,待确认到货代发货后获取正确运单号');
                     return BaseCarrierAPI::getResult(0, '', '已验证该货代的此种运输服务没有运单号提供！');

                 }
             }
         }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
     }



    /**
    +----------------------------------------------------------
     * 申请跟踪号
    +----------------------------------------------------------
     **/
     public function getTrackingNO($data){
          return BaseCarrierAPI::getResult(1, '', '有跟踪号返回的，物流接口申请订单号时就会立即返回跟踪号，否则一直不会返回');
//         try{
////             foreach ($data as $k=>$v){
////                 $order = $v['order'];
//                $order = $data['order'];
//
//                 //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
//                 $checkResult = CarrierAPIHelper::validate(0,1,$order);
//                 $shipped = $checkResult['data']['shipped'];
//
//                 $documentCode = $order->customer_number;
//                 if(empty($documentCode))
//                     return BaseCarrierAPI::getResult(1, '', '获取物流客户参考号失败');
//
//                 $url = 'http://121.43.115.82:8082/selectTrack.htm?documentCode='.$documentCode;
//
//             $header=array();
//                 $header[]='Content-Type:text/xml;charset=utf-8';
//
//                 $response = Helper_Curl::post($url,[],$header);
////                                     print_r($response);exit;
//                 if (empty($response)){
//                     return self::getResult(1,'','操作失败,e裕云仓返回错误');
//                 }
//                 $ret = json_decode($response,true);
////                  print_r($ret);exit;
//             if($ret['ack']=='true'){
//                     if(!empty($ret['data'][0]['trackingNumber'])){
//                         $trackingNumber = $ret['data'][0]['trackingNumber'];
//                         $shipped->tracking_number = $trackingNumber;
//                         $shipped->save();
//                         $order->carrier_step = OdOrder::CARRIER_FINISHED;
//                         $order->save();
//                         return BaseCarrierAPI::getResult(0, '', '获取物流号成功！物流号：'.$trackingNumber);
//
//                     }else
//                         return BaseCarrierAPI::getResult(0, '', '没有获取到物流号');
//                 }else{
//                     return self::getResult(1,'','获取运单号失败');
//                 }
////             }
//         }
//         catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
     }




    /**
    +----------------------------------------------------------
     * 打单
    +----------------------------------------------------------
     * log			name		date				note
     * @author		dwg		 2015/11/19		       初始化
    +----------------------------------------------------------
     **/
     public function doPrint($data){
         try{
             $returnNo = '';

             $user=\Yii::$app->user->identity;
             if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
             $puid = $user->getParentUid();

             foreach ($data as $k=>$v) {//拼接订单号
                 $order = $v['order'];

                 $checkResult = CarrierAPIHelper::validate(1,1,$order);
                 $shipped = $checkResult['data']['shipped'];
                 $info = CarrierAPIHelper::getAllInfo($order);
                 $service = $info['service'];
                 $account = $info['account'];
                 $service_params = $service->carrier_params; //获取打印方式
                 if($shipped->return_no['OrderSign']){
                     $returnNo .= $shipped->return_no['OrderSign'].',';//获取物流商返回的order_id
                 }
//                  $order->is_print_carrier = 1;
                 $order->print_carrier_operator = $puid;
//                 $order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
                 $order->printtime = time();
                 $order->save();

             }
             if(empty($returnNo)){
                 throw new CarrierException('操作失败,订单不存在');
             }else {
                 $returnNo = rtrim($returnNo,',');
             }
             
             //E邮宝
             if($service_params['format']=="e1"||$service_params['format']=="e2")
             {
                 $requestBody = [];
                 $requestBody['order_id'] = $returnNo;
                 //e邮宝、e特快 A4打印，返回pdf路劲
                 if( $service_params['format'] == 'e1')
                 {
                 	$requestBody['format'] = 'A4';
                 }
                 //E邮宝10*10不干胶打印，返回pdf路径
                 else if( $service_params['format'] == 'e2')
                 {
                 	$requestBody['format'] = '10*10';
                 }
                 
                 $PDF_URL = Helper_Curl::post("http://121.43.115.82:8082/getEUBPrintPath.htm", $requestBody);
                 
                 if( !preg_match('/\.pdf$/', $PDF_URL))
                 {
                     return self::getResult(1,'','没有获取到正确的PDF连接');
                 }
                 
                 if( !empty( $PDF_URL))
                 {
                 	$responsePdf = Helper_Curl::get($PDF_URL);
                 	if( strlen( $responsePdf) < 1000)
                 	{
                 	    return self::getResult(1,'','接口返回内容不是一个有效的PDF');
                 	}
                 		
                 	$pdfUrl = CarrierAPIHelper::savePDF( $responsePdf, $puid, $account->carrier_code.'_'.$order['customer_number'], 0);
                 }
                 else
                 {
                     return self::getResult(1,'','获取用于打印的PDF的连接失败');
                 }
                 
                 if( isset( $pdfUrl))
                 {
                     return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印,订单已转到"待获取运单号"状态');
                 }
                 else
                 {
                     return self::getResult(1,'','连接生成失败');
                 }
             }else{ //一般正常的打印
                 
                 //默认一键打印
                 $format_name = "一键打印10*10";
                 $print_type = "lab10+10";
                 
                 $formatstr = $this->_getCarrierLabelTypeStr( $service_params['format']);
                 $formatstr = explode(':', $formatstr["data"]);
                 if( count($formatstr) > 1){
                 	$print_type = $formatstr[1];
                 }
                 else{
                     return self::getResult(1,'','E裕云仓更改了打印参数，需要重新到运输服务指定打印格式');
                 }
                 
                 //$Print_type = self::getPrintType($service_params['format']);
                 $print_link="http://121.43.115.82:8089/order/FastRpt/PDF_NEW.aspx?Format=".$service_params['format']."&PrintType=".$print_type."&order_id=".$returnNo;

                 $respond = Helper_Curl::post($print_link);
                 preg_match('/href="(.*?)"/i', $respond, $pdf_link); //查看一般的打印是否打印成功

                 if(empty($pdf_link)){
                     return self::getResult(1,'','打印失败:'.$respond);
                 }else {
                     $url = "http://121.43.115.82:8089".$pdf_link[1];
                     return self::getResult(0,['pdfUrl'=>$url],'连接已生成,请点击并打印');//访问URL地址
                 }
             }

         }catch(CarrierException $e){
             return self::getResult(1,'',$e->msg());
         }
     }




      //获取打印信息信息###############################################################
     public static function getPrintType($key){
        //查找是否存在"print_type":"2"，一般打印都是"print_type":"1";
        $arr = [
            'A4_EMS_BGD_PY.frx'=>'2',
            'A4_NL_10301076.frx'=>'2',
            'A4_NL_10.frx'=>'2',
        ];
        if(isset($arr[$key]))return $arr[$key];
        else return "1";
     }
     
     //获取标签格式
     public function _getCarrierLabelTypeStr( $format_path = '')
     {
     	$header = array();
     	$header[] = 'Content-Type:text/xml;charset=utf-8';
     	$response = Helper_Curl::post( 'http://121.43.115.82:8082/selectLabelType.htm',$header);
     	if( empty($response))
     		return ['error'=>1, 'data'=>'', 'msg'=>'获取标签格式失败'];
     		
     	//解决中文乱码问题
     	$response = mb_check_encoding($response, 'UTF-8') ? $response : mb_convert_encoding($response, 'UTF-8', 'gbk');
     	$ret = json_decode($response,true);
     
     	$str = '';
     	//if( $format_path != '')
     	{
     		foreach ($ret as $val)
     		{
     			if( $format_path == $val['format_path'])
     			{
     				$str = $val['format_path'].':'.$val['print_type'];
     				break;
     			}
     		}
     	}
     
     	return ['error'=>1, 'data'=>$str, 'msg'=>''];
     }


    /**
     * 用来确定打印完成后 订单的下一步状态
     */
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
     public function getVerifyCarrierAccountInformation($data)
     {
     	$result = array('is_support'=>1,'error'=>1);
     
     	try
     	{
     		$header = array();
     		$header[] = 'Content-Type:text/xml;charset=utf-8';
     		//账号认证
     		$login_link = 'http://121.43.115.82:8082/selectAuth.htm?username='.$data['username'].'&password='.$data['password'];
     		$response = Helper_Curl::get($login_link, [], $header);
     
     		if(!empty($response))
     		{
     			$response = str_replace('\'', '"', $response);
     			$response = json_decode($response);
     			if(isset($response->ack) && $response->ack == 'true')
     			{
     				$result['error'] = 0;
     			}
     		}
     	}
     	catch(CarrierException $e){}
     
     	return $result;
     }
     
     //获取运输服务
     public function getCarrierShippingServiceStr($account)
     {
     	$header = array();
     	$header[] = 'Content-Type:text/xml;charset=utf-8';
     	$response = Helper_Curl::post( 'http://121.43.115.82:8082/getProductList.htm',$header);
     	if( empty($response))
     		return ['error'=>1, 'data'=>'', 'msg'=>'获取运输服务失败'];
     		
     	//解决中文乱码问题
     	$response = mb_check_encoding($response, 'UTF-8') ? $response : mb_convert_encoding($response, 'UTF-8', 'gbk');
     	$ret = json_decode($response,true);
     
     	$str = '';
     	foreach ($ret as $val)
     	{
     		$str .= $val['product_id'].':'.$val['product_shortname'].';';
     	}
     
     	if($str == '')
     		return ['error'=>1, 'data'=>'', 'msg'=>'获取运输服务失败'];
     	else
     		return ['error'=>0, 'data'=>$str, 'msg'=>''];
     }
}