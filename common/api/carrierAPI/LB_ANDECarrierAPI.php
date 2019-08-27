<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;

class LB_ANDECarrierAPI extends BaseCarrierAPI{
    public function __construct(){}
    /**
     +----------------------------------------------------------
     * 申请订单号
     +----------------------------------------------------------
     **/
    public function getOrderNO($data){
        try{
            $PrintParam = array();
            $PrintParam = ['A4_2_EMS_BGD.frx','A4_EMS_BGD_SZ_10130656975800077212.frx','A4_EMS_BGD_NEW_10.frx','A4_2_EMS_BGD584799263377.frx','A4_2_EMS_BGD584799.frx','A4_EMS_BGD_FZ_10378135.frx','A4_MY_10382740.frx','A4_MY_10.frx','A4_EMS_BGD_SG_GH130731971168028784130755469310507249291625.frx','A4_EMS_BGD_SG_GH130731971168028784130755469310507249291625613573.frx','A4_2_EMS_BGD549799.frx','A4_NL_10183040.frx','A4_NL_10.frx','A4','A4_EUB_US987284.frx'];//这里的不是10*10的格式，因为10*10的标签太多
            
            $user=\Yii::$app->user->identity;
            if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();
            
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
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];
            $Service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
            $carrier_params = $Service->carrier_params;
            //认证参数
            $params=$account->api_params;
            $login_link='http://120.27.36.176:8082/selectAuth.htm?username='.$params['username'].'&password='.$params['password'];
            $login_message = Helper_Curl::post($login_link);
            //转换为json格式
            $change = str_replace("'", '"', $login_message);
            $respond = json_decode($change,true);
            if($respond['ack']=='true'&&!empty($respond['customer_id'])&&!empty($respond['customer_userid'])){
                $sum=count($form_data['invoice_title']);//统计商品数量
                for($i=0;$i<$sum;$i++){
                    if(!empty($form_data['invoice_pcs'][$i])){
                        $invoice_pcs=$form_data['invoice_pcs'][$i];//必填且要为数字
                    }else{
                        throw new CarrierException("数量不能为空！");
                    }

                    $TitleCn=$form_data['sku'][$i]; //sku,如果是e邮宝，e特快，e包裹则传中文品名;中文品名
                    $TitleEn=$form_data['invoice_title'][$i]; //商品名，必填
                    if(empty($TitleEn)){
                        throw new CarrierException("英文报关名不能为空！");
                    }
                    if(empty($TitleCn)){
                        throw new CarrierException("中文报关名不能为空！");
                    }
                    
                    if(mb_strlen($TitleCn,'utf-8')>64){
                        throw new CarrierException("中文报关名过长,长度不能超过64");//检查中文报关名的长度
                    }
                    
                    if(!empty($form_data['invoice_weight'][$i])){
                        $invoice_weight=$form_data['invoice_weight'][$i]/1000;//必填,单位kg
                    }else{
                        throw new CarrierException("报关重量不能为空！");
                    }
                    if(!empty($form_data['invoice_amount'][$i])){
                        $invoice_amount=$form_data['invoice_amount'][$i];//必填且要为数字
                    }else {
                        throw new CarrierException("报关价值不能为空！");
                    }
                    
                    $goods[]=[
                        'invoice_amount'=>$invoice_amount, //申报价值，必填
                        'invoice_pcs'=>$invoice_pcs, //件数，必填
                        'invoice_title'=>$TitleEn, //商品名，必填
                        'invoice_weight'=>$invoice_weight, //单件重
                        'item_id'=>'',
                        'item_transactionid'=>'',
                        'sku'=>$TitleCn, //sku,如果是e邮宝，e特快，e包裹则传中文品名;中文品名
                        'sku_code'=>$form_data['sku_code'][$i], //配货信息
                    ];
                }
                //地址
//                 $address=!empty($order->consignee_address_line1)?$order->consignee_address_line1:(!empty($order->consignee_address_line2)?$order->consignee_address_line2:$order->consignee_address_line3);
//                 $address = $order->consignee_address_line1.' '.$order->consignee_address_line2.' '.$order->consignee_address_line3;
                $addressAndPhoneParams = array(
                    'address' => array(
                        'consignee_address_line1_limit' => 500,
                    ),
                    'consignee_district' => 1,
                    'consignee_county' => 1,
                    'consignee_company' => 1,
                    'consignee_phone_limit' => 200
                );
                
                $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
                
                $param=array();
                $param = [
                    'buyerid'=>'',
                    'consignee_address'=>$addressAndPhone['address_line1'], //收件地址街道，必填
                    'consignee_city'=>$order->consignee_city,   //城市
                    'consignee_mobile'=>$order->consignee_mobile, //
                    'consignee_name'=>$order->consignee, //收件人,必填
                    'trade_type'=>'ZYXT', //必填，自身系统代码，客户自用系统
                    'consignee_postcode'=>$order->consignee_postal_code, //邮编，有邮编的国家必填
                    'consignee_state'=>$order->consignee_province, //州/省
                    'consignee_telephone'=>$addressAndPhone['phone1'], //收件电话，必填
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
                $order_respond = Helper_Curl::post('http://120.27.36.176:8082/createOrderApi.htm', array('param'=>json_encode($param)));
                $result=json_decode($order_respond,true);
                
                if( $result['ack'] == 'true' ){
                    $r = CarrierAPIHelper::orderSuccess($order,$Service,$customer_number, OdOrder::CARRIER_WAITING_PRINT ,$result['tracking_number'],['OrderSign'=>$result['order_id']]);//假如需要用到返回的对方的return_no,则要以数组的形式保存
                    
                    if(!in_array($carrier_params['format'], $PrintParam)){//由于安德10*10标签较多，所以$PrintParam是非10*10数组
                        $print_param = array();
                        $print_param['carrier_code'] = $Service->carrier_code;
                        $print_param['api_class'] = 'LB_ANDECarrierAPI';
                        $print_param['returnNo'] = $result['order_id'];
                        $print_param['carrier_params'] = $Service->carrier_params;
                        $print_param['shipping_method_code'] = $Service->shipping_method_code;
                    
                        try{
                    
                            CarrierAPIHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
                        }catch (\Exception $ex){
                        }
                    }
                    
                    return  self::getResult(0,$r,'操作成功!订单跟踪号：'.$result['tracking_number']);
                }else {
                    $error_message = urldecode($result['message']);
                    return self::getResult(1,'',$error_message);
                }
            }else{
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
        return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
    }
//     public function getTrackingNO1(){
//         $respond = Helper_Curl::post('http://120.27.36.176:8082/selectTrack.htm?documentCode=RI697871976CN');
//         if( mb_detect_encoding($respond,"UTF-8,GBK")!="UTF-8" ) {//判断是否不是UTF-8编码，如果不是UTF-8编码，则转换为UTF-8编码
//             $respond_change = iconv("gbk","utf-8",$respond);
//         } else {
//             $respond_change = $respond;
//         }
//         $result = json_decode($respond_change,true);
//         print_r($result);
//     }
    /**
     +----------------------------------------------------------
     * 打单
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
//                 $order->is_print_carrier = 1;
                $order->print_carrier_operator = $puid;
                $order->printtime = time();
                $order->save();
                
            }
            if(empty($returnNo)){
                throw new CarrierException('操作失败,订单不存在');
            }else {
                $returnNo = rtrim($returnNo,',');
            }
            if(empty($service_params['format'])){//必须填入打印格式
                throw new CarrierException("必须选择打印格式！");
            }
            //E邮宝
            if($service_params['format']=="A4"||$service_params['format']=="10*10"){//选用该打印法师时，判断是否选用E邮宝,且只有这两种可以改变打印样式，其他的打印格式放在EUB的打印方式中，还是默认的A4
                if($service->shipping_method_code == 3621 || $service->shipping_method_code == 4421 || $service->shipping_method_code == 4441){//E邮宝运输代码
                    $print_link="http://120.27.36.176:8082/getEUBPrintPath.htm?order_id=".$returnNo."&format=".$service_params['format'];
                    $respond = Helper_Curl::post($print_link);
                    preg_match('/(.*?).pdf/i', $respond, $eyb_pdf_link); //查看E邮宝调用的打印是否打印成功
                    if(empty($eyb_pdf_link)){//若成功，返回的是pdf的URL
                        return self::getResult(1,'','打印失败');
                    }else{
                        $pdf_respond = Helper_Curl::get($eyb_pdf_link[0]);
                        $pdfurl = CarrierAPIHelper::savePDF($pdf_respond,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
                        return self::getResult(0,['pdfUrl'=>$pdfurl['pdfUrl']],'连接已生成,请点击并打印');//访问URL地址
//                         return self::getResult(0,['pdfUrl'=>$eyb_pdf_link[0]],'连接已生成,请点击并打印');//访问URL地址
                    }
                }else{
                    return self::getResult(1,'','目前选用的打印方式只适合E邮宝，请重新选择适合的打印方式。');
                }
            }else{ //一般正常的打印
                
                //默认一键打印
                $format_id = "";
                $format_name = "一键打印10*10";
                $format_path = "";
                $print_type = "lab10+10";
                
                if( !empty( $service_params['format']))
                	$format_id = (string)$service_params['format'];
                
                $formatstr = $this->_getCarrierLabelTypeStr( $format_id);
                $formatstr = explode(':', $formatstr["data"]);
                if( count($formatstr) > 1)
                {
                	$format_path = $formatstr[0];
                	$print_type = $formatstr[1];
                }
                
                //$Print_type = $this->getPrintType($service_params['format']);
                $print_link="http://120.27.36.176/order/FastRpt/PDF_NEW.aspx?Format=".$format_path."&PrintType=".$print_type."&order_id=".$returnNo;
                $respond = Helper_Curl::post($print_link);
                preg_match('/href="(.*?)"/i', $respond, $pdf_link); //查看一般的打印是否打印成功
//                 var_dump($pdf_link);exit();
                if(empty($pdf_link)){
                    return self::getResult(1,'','打印失败');
                }else {
                    $url = "http://120.27.36.176".$pdf_link[1];
                    return self::getResult(0,['pdfUrl'=>$url],'连接已生成,请点击并打印');//访问URL地址
                }
            
            }
           
        }catch(CarrierException $e){
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
    
            $returnNo = $print_param['returnNo'];//获取物流商返回的order_id
            if(empty($returnNo)){
                throw new CarrierException('操作失败,订单不存在');
            }
            
            $normal_params = empty($print_param['carrier_params']) ? array() : $print_param['carrier_params']; ////获取打印方式
            
            if(empty($normal_params['format'])){//必须填入打印格式
                throw new CarrierException("必须选择打印格式！");
            }
            
            //E邮宝
            if($normal_params['format']=="A4"||$normal_params['format']=="10*10"){//选用该打印法师时，判断是否选用E邮宝,且只有这两种可以改变打印样式，其他的打印格式放在EUB的打印方式中，还是默认的A4
                if($print_param['shipping_method_code'] == 3621 || $print_param['shipping_method_code'] == 4421 || $print_param['shipping_method_code'] == 4441){//E邮宝运输代码
                    $print_link="http://120.27.36.176:8082/getEUBPrintPath.htm?order_id=".$returnNo."&format=".$normal_params['format'];
                    $respond = Helper_Curl::post($print_link);
                    preg_match('/(.*?).pdf/i', $respond, $eyb_pdf_link); //查看E邮宝调用的打印是否打印成功
                    if(empty($eyb_pdf_link)){//若成功，返回的是pdf的URL
                        return self::getResult(1,'','打印失败');
                    }else{
                        $pdf_respond = Helper_Curl::get($eyb_pdf_link[0]);
                        $pdfPath = CarrierAPIHelper::savePDF2($pdf_respond,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
                        return $pdfPath;
                        //                         return self::getResult(0,['pdfUrl'=>$eyb_pdf_link[0]],'连接已生成,请点击并打印');//访问URL地址
                    }
                }else{
                    return self::getResult(1,'','目前选用的打印方式只适合E邮宝，请重新选择适合的打印方式。');
                }
            }else{ //一般正常的打印
                
                //默认一键打印
                $format_id = "";
                $format_name = "一键打印10*10";
                $format_path = "";
                $print_type = "lab10+10";
                
                if( !empty( $normal_params['format']))
                	$format_id = (string)$normal_params['format'];
                
                $formatstr = $this->_getCarrierLabelTypeStr( $format_id);
                $formatstr = explode(':', $formatstr["data"]);
                if( count($formatstr) > 1)
                {
                	$format_path = $formatstr[0];
                	$print_type = $formatstr[1];
                }
                
                //$Print_type = $this->getPrintType($service_params['format']);
                $print_link="http://120.27.36.176/order/FastRpt/PDF_NEW.aspx?Format=".$format_path."&PrintType=".$print_type."&order_id=".$returnNo;
                $respond = Helper_Curl::post($print_link);
                preg_match('/href="(.*?)"/i', $respond, $pdf_link); //查看一般的打印是否打印成功
                //                 var_dump($pdf_link);exit();
                if(empty($pdf_link)){
                    return self::getResult(1,'','打印失败');
                }else {
                    $url = "http://120.27.36.176".$pdf_link[1];
                    $response = Helper_Curl::get($url);
                    // 	        return self::getResult(0,['pdfUrl'=>$url],'连接已生成,请点击并打印');
                    if (strlen($response)>1000){
                        $pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
                        return $pdfPath;
                    }else{
                        return ['error'=>1, 'msg'=>'打印失败,请检查订单后重试', 'filePath'=>''];
                    }
                }
            
            }
            
        }catch (CarrierException $e){
            return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
        }
    }
    
//     public function doPrint1(){
//         try{
//             //查找是否存在"print_type":"2"，一般打印都是"print_type":"1";
// //             $respond = Helper_Curl::post('http://120.27.36.176:8082/getEUBPrintPath.htm?order_id=3106047&format=10*10');
            
//             $respond = Helper_Curl::post('http://120.27.36.176/order/FastRpt/PDF_NEW.aspx?Format=lbl_EMS_BGD_NEW_10.frx&PrintType=1&order_id=3109967');
//             var_dump($respond);
//             preg_match('/href="(.*?)"/i', $respond, $pdf_link); //查看一般的打印是否打印成功
// //             preg_match('/(.*?).pdf/i', $respond, $pdf_link); //查看E邮宝调用的打印是否打印成功
//             print_r($pdf_link);
//         }catch(CarrierException $e){
//             return self::getResult(1,'',$e->msg());
//         }
//     }
    /*
     * 获取打印信息信息
     */
    public function getPrintType($key){
        //查找是否存在"print_type":"2"，一般打印都是"print_type":"1";
        $arr = [
            'A4_EMS_BGD_NEW_10.frx'=>'2',
            'A4_MY_10382740.frx'=>'2',
            'A4_MY_10.frx'=>'2',
            'A4_NL_10183040.frx'=>'2',
            'A4_NL_10.frx'=>'2',
            'A4_EMS_BGD_NEW_10708371.frx'=>'2',
        ];
        if(isset($arr[$key]))return $arr[$key];
        else return "1";
    }
    
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }
    
	/**
	 +----------------------------------------------------------
	 * 获取运输方式
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/06/01				初始化
	 +----------------------------------------------------------
	 **/
    public function getCarrierShippingServiceStr($account){
    	try{
    		$login_link="http://120.27.36.176:8082/getProductList.htm";
    		$login_message = Helper_Curl::post($login_link);
    		$login_message=mb_convert_encoding($login_message, "UTF-8", "GBK");
    		//转换为json格式
    		$change = str_replace("'", '"', $login_message);
    		$respond = json_decode($change,true);
    		 
    		if(empty($respond) || !is_array($respond) || json_last_error()!=false){
    			return self::getResult(1,'','获取运输方式失败');
    		}
    		 
    		$channelStr="";
    		foreach ($respond as $countryVal){
    			$channelStr.=$countryVal['product_id'].":".$countryVal['product_shortname'].";";
    		}
    		 
    		if(empty($channelStr)){
    			return self::getResult(1, '', '');
    		}else{
    			return self::getResult(0, $channelStr, '');
    		}
    	}catch(CarrierException $e){
    		return self::getResult(1,'',$e->msg());
    	}
    }
    
    //获取标签格式
    public function _getCarrierLabelTypeStr( $format_id = '-1')
    {
    	$header = array();
    	$header[] = 'Content-Type:text/xml;charset=utf-8';
    	$response = Helper_Curl::post( 'http://120.27.36.176:8082/selectLabelType.htm',$header);
    	if( empty($response))
    		return ['error'=>1, 'data'=>'', 'msg'=>'获取标签格式失败'];
    		
    	//解决中文乱码问题
    	$response = mb_check_encoding($response, 'UTF-8') ? $response : mb_convert_encoding($response, 'UTF-8', 'gbk');
    	$ret = json_decode($response,true);
    
    	$str = '';
    	if( $format_id != '-1')
    	{
    		foreach ($ret as $val)
    		{
    			if( $format_id == $val['format_id'])
    			{
    				$str = $val['format_path'].':'.$val['print_type'];
    				break;
    			}
    		}
    	}
    	else
    	{
    		foreach ($ret as $val)
    		{
    			$str .= $val['format_id'].':'.$val['format_name'].';';
    		}
    	}
    
    	return ['error'=>1, 'data'=>$str, 'msg'=>''];
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
    		$login_link = 'http://120.27.36.176:8082/selectAuth.htm?username='.$data['username'].'&password='.$data['password'];
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
}