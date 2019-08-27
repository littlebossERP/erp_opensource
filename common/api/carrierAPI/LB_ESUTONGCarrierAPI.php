<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use Jurosh\PDFMerge\PDFMerger;
// try{
// include '../components/PDFMerger/PDFMerger.php';
// }catch(\Exception $e){	
// }

class LB_ESUTONGCarrierAPI extends BaseCarrierAPI{
    static private $wsdl = '';	// 物流接口
    static private $wsdl2 = '';
    static private $wsdl3 = '';//	PrintInterface
    static private $token = '';
    private $submitGate = null;	// SoapClient实例
    
    static $connecttimeout=60;
    static $timeout=500;
    static $last_post_info=null;
    static $last_error =null;
    
    public function __construct(){
        //俄速通
        self::$wsdl = 'http://api.ruston.cc/OrderOnline/ws/OrderOnlineService.dll?wsdl';
        self::$wsdl2='http://api.ruston.cc/OrderOnlineTool/ws/OrderOnlineToolService.dll?wsdl';
        self::$wsdl3='http://label.ruston.cc/orderprint/';
       // self::$token = '';//'8BC17B74BD800FEB35F5E7A2DB282ED8';
        $this->submitGate = new SubmitGate();
    }
    
    /**
     +----------------------------------------------------------
     * 申请订单号
     +----------------------------------------------------------
     **/
    public function getOrderNO($data){
        try{
        	$order = $data['order'];  //object OdOrder
        	
        	//重复发货 添加不同的标识码
        	$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
        	$customer_number = $data['data']['customer_number'];
        	
        	if(isset($data['data']['extra_id'])){
        		if($extra_id == ''){
        			return self::getResult(1, '', '强制发货标识码，不能为空');
        		}
        	}
        	//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
        	$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
        	 
        	$form_data = $data['data'];
        	$info = CarrierAPIHelper::getAllInfo($order);
        	$account = $info['account'];
        	$service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
        	
        	if(empty($info['senderAddressInfo']['shippingfrom'])){
        		return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
        	}
        	
        	//认证参数
        	$params=$account->api_params;
        	$normal_params = $service->carrier_params;
        	$shippingfrom_address = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息(shppingfrom是属于客户填“发货地址”的信息)
        	$token=$params['token'];
//         	print_r($shippingfrom_address);
//         	exit();
        	if (empty($order->consignee_city)){
        		return self::getResult(1, '', '城市不能为空');
        	}
        	
          	if (empty($order->consignee_province)){
        			return self::getResult(1, '', '省份不能为空');
          	}
        	
        	if (empty($order->consignee)){
        		return self::getResult(1, '', '收件人姓名不能为空');
        	}
     
        	$phoneContact = (empty($order->consignee_phone) ? $order->consignee_mobile : $order->consignee_phone);
        	$phoneTel=(empty($shippingfrom_address['phone']) ? $shippingfrom_address['mobile'] : $shippingfrom_address['phone']);
        	
        	if (empty($phoneContact)){
        		return self::getResult(1, '', '联系方式不能为空');
        	}
        	
        	if (empty($form_data['CargoCode'])){
        		return self::getResult(1, '', '货物类型不能为空');
        	}
        	
        	$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
        	(empty($order->consignee_district) ? '' : ','.$order->consignee_district);
        	
        	$address=$order->consignee_address_line1.$order->consignee_address_line2.$order->consignee_address_line3;
//        	$address=!empty($order->consignee_address_line1)?$order->consignee_address_line1:(!empty($order->consignee_address_line2)?$order->consignee_address_line2:$order->consignee_address_line3);
        	if(empty($address)){
        		throw new CarrierException("收货地址不能为空！");
        	}	
        	$address=$address.$addressInfo;
			
        	$addressAndPhoneParams = array(
        			'address' => array(
        					'consignee_address_line1_limit' => 60,
        			),
        			'consignee_district' => 1,
        			'consignee_county' => 1,
        			'consignee_company' => 1,
        			'consignee_phone_limit' => 30
        	);
        		
        	$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
        	
        	$productList =array();
        	$weight=0;
        	
         	foreach ($order->items as $k=>$v){
//        		$a[$k]=[
//         		'transactionID'=>$v->order_source_transactionid,
 //       		'customerWeight'=>$form_data['CustomerWeight'][$k],
  //      		];
  				$weight+=$form_data['CustomerWeight'][$k]*$form_data['DeclarePieces'][$k]/1000;
  				$weight=round($weight,3);
//   				return self::getResult(1, '',strlen((string)((int)($weight))));
  
  				if(strlen((string)((int)($weight)))>8){
  					$weight=0;
  					return self::getResult(1, '', '重量整数位最大为11位');
  				}
 			//print_r($weight);
        		$productList[$k]=[
        			'declareNote'=>$form_data['DeclareNote'][$k],
        			'declarePieces'=>$form_data['DeclarePieces'][$k],
	        		'declareUnitCode'=>empty($form_data['DeclareUnitCode'][$k])?'PCE':$form_data['DeclareUnitCode'][$k],
	        		'name'=>$form_data['Name'][$k],
	        		'unitPrice'=>$form_data['UnitPrice'][$k],
	        		'eName'=>$form_data['EName'][$k],
	        		
        		];
        		//print_r($productList);
        		if (empty($productList[$k]['eName'])){
        			return self::getResult(1, '', '英文申报名不能为空');
        		}
        		
        		if(empty($productList[$k]['declarePieces'])){
        			return self::getResult(1,'', '件数不能为空');
        		}
        		
        		if(empty($productList[$k]['unitPrice'])){
        			return self::getResult(1,'', '单价不能为空');
        		}
        		
        		if (empty($weight)){
        			return self::getResult(1, '', '重量不能为空');
        		}
//         		if (empty($productList[$k]['declareUnitCode'])){
//         			return self::getResult(1, '', '申报单位类型不能为空');
//         		}
        	}
        	//exit;
        	$insurType=empty($normal_params['InsurType'])?'':$normal_params['InsurType'];
        	if($insurType=='N'){
        		$insurType='';
        	}
        	if($insurType=='1P'){
        		if(empty($form_data['InsurValue'])){
        			return self::getResult(1,'','保险价值不能为空');
        		}
        	}
        	$orderMain =array(
        		'buyerId'=>$order->source_buyer_user_id,
        		'cargoCode'=>'P',
        		'city'=>$order->consignee_city,
        		'consigneeCompanyName'=>$order->consignee_company,
        		'consigneeEmail'=>$order->consignee_email,
        		'consigneeFax'=>'',
        		'consigneeName'=>$order->consignee,
        		'consigneePostCode'=>$order->consignee_postal_code,
        		'consigneeTelephone'=>$addressAndPhone['phone1'],
        		'customerWeight'=>$weight,//$a[$k]['customerWeight'],//$form_data['CustomerWeight'],
        		'declareInvoice'=>$productList,
        		'destinationCountryCode'=>$order->consignee_country_code,
        		'initialCountryCode'=>$shippingfrom_address['country'],
        		'insurType'=>$insurType,//$form_data['InsurType'],	
        		'insurValue'=>empty($form_data['InsurValue'])?'':$form_data['InsurValue'],
        		'mctCode'=>$form_data['CargoCode'],
				'orderNo'=>$customer_number,
				'orderNote'=>$form_data['Note'],
				'paymentCode'=>$normal_params['PaymentCode'],
				'pieces'=>$form_data['DeclarePieces'][$k],
				'productCode'=>$service->shipping_method_code,
				'returnSign'=>$normal_params['ReturnSign'],
				'shipperAddress'=>empty($shippingfrom_address['street_en']) ? $shippingfrom_address['street'] : $shippingfrom_address['street_en'],
				'shipperCity'=>empty($shippingfrom_address['city_en']) ? $shippingfrom_address['city'] : $shippingfrom_address['city_en'],
				'shipperComanyName'=>empty($shippingfrom_address['company_en']) ? $shippingfrom_address['company'] : $shippingfrom_address['company_en'],
				'shipperFax'=>'',
				'shipperName'=>empty($shippingfrom_address['contact_en']) ? $shippingfrom_address['contact'] : $shippingfrom_address['contact_en'],
				'shipperPostCode'=>$shippingfrom_address['postcode'],
				'shipperStateOrProvince'=>empty($shippingfrom_address['province_en']) ? $shippingfrom_address['province'] : $shippingfrom_address['province_en'],
				'shipperTelephone'=>$phoneTel,
				'stateOrProvince'=>$order->consignee_province,
				'street'=>$addressAndPhone['address_line1'],
				'trackingNumber'=>'',
				'transactionId'=>'',//$a[$k]['transactionID'],		
        	);
//        	print_r($orderMain);
//         	exit();  	
	
        	$request = array(
        			'arg0'=>$token,
        			'arg1'=>$orderMain,
        	);
        	//print_r($request);
// 	       	exit();
        	$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'createAndPreAlertOrderService');
        	//$response =  json_decode(json_encode( $response),true);
       		//if($response['error']){return $response;}
        	$response = $response['data'];
        	$response=$response->return;
        	
        	if($response->ack=='Success'){
        		$track_num=empty($response->trackingNumber)?null:$response->trackingNumber;
        		//判断是否存在能打印的运输方式里
//         		$arr=['A2','A3','A4','A5','AB','AG','A8','AH','A6','AC','AE','AF','AI','AJ'];
//         		$a=$service->shipping_method_code;
//         		$isin = in_array($a,$arr);
//         		if($isin){
//         			$r = CarrierAPIHelper::orderSuccess($order,$service,$response->referenceNumber,OdOrder::CARRIER_WAITING_GETCODE ,$track_num);
//         		}else{
//         			$r = CarrierAPIHelper::orderSuccess($order,$service,$response->referenceNumber,OdOrder::CARRIER_FINISHED ,$track_num);
//         		}
        		$r = CarrierAPIHelper::orderSuccess($order,$service,$response->referenceNumber,OdOrder::CARRIER_WAITING_GETCODE ,$track_num);
        		return  self::getResult(0,$r,'操作成功!订单参考号'.$response->referenceNumber);
        	}
        	else{
//         		print_r($response);
//         		exit;
        		$err=$response->errors;
        		if((count($err))>1){							//错误信息大于1条时
	        		foreach ($err as $err_message){
	        			$code=$err_message->code;	
	        			$cnMessage=empty($err_message->cnMessage)?'':'错误信息:'.$err_message->cnMessage;
	        			$cnAction=empty($err_message->cnAction)?'':$err_message->cnAction;
	        			$enMessage=empty($err_message->enMessage)?'':'ErrorMessage:'.$err_message->enMessage;
	        			$enAction=empty($err_message->enAction)?'':$err_message->enAction;
	        			$defineMessage=empty($err_message->defineMessage)?'':'错误信息补充说明:'.$err_message->defineMessage;
	        			if(!empty($code)){
	        				return self::getResult(1,'','错误代码:'.$code.'<br/>'.$cnMessage.$cnAction.'<br/>'.$enMessage.$enAction.'<br/>'.$defineMessage);//1为错误信息
	        			}
	        		}	//exit;
        		}else{
        			$code=$err->code;
//         			print_r($code);
//         			exit;
        			$cnMessage=empty($err->cnMessage)?'':'错误信息:'.$err->cnMessage;
        			$cnAction=empty($err->cnAction)?'':$err->cnAction;
        			$enMessage=empty($err->enMessage)?'':'ErrorMessage:'.$err->enMessage;
        			$enAction=empty($err->enAction)?'':$err->enAction;
        			$defineMessage=empty($err->defineMessage)?'':'错误信息补充说明:'.$err->defineMessage;
        			if(!empty($code)){
        				return self::getResult(1,'','错误代码:'.$code.'<br/>'.$cnMessage.$cnAction.'<br/>'.$enMessage.$enAction.'<br/>'.$defineMessage);//1为错误信息
        			}
        		}
        		return self::getResult(1,'','物流商返回数据错误');
        		
        	}
        }
        catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }

    
    /*
     * 获取上传订单状态信息
     */
    public function getOrderStatus(){
        $arr = [
            D=>'草稿',
            A=>'可用运单',
            P=>'已预报',
            V=>'已收货',
            C=>'已出货',
            E=>'已删除',
            S=>'缺货',
        ];
       return $arr;
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
        try{
//         	$cnMessage='';
//         	$enMessage='';
//         	$cnAction='';
//         	$enAction='';
//         	$defineMessage='';
            $order = $data['order'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0,1,$order);
            $shipped = $checkResult['data']['shipped'];
             
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];
            $service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
            //认证参数
            $params=$account->api_params;
            $token=$params['token'];
            
            if(empty($order->customer_number)){
                throw new CarrierException("物流商参考号获取失败！");
            }
            $request = array(
        			'arg0'=>$token,
        			'arg1'=>$order->customer_number,
        	);
            $response = $this->submitGate->mainGate(self::$wsdl2, $request, 'soap', 'findTrackingNumberService');
           $response = $response['data'];
        	$response=$response->return;
        	//print_r($response);
        	//exit();
            if($response){//假如查找失败，为空，否则有数据,
              if($response->ack=="Failure"){
              	$err=$response->errors;
        		foreach (array($err) as $err_message){
        			$code=$err_message->code;
        			$cnMessage=empty($err_message->cnMessage)?'':'错误信息:'.$err_message->cnMessage;
        			$cnAction=empty($err_message->cnAction)?'':$err_message->cnAction;
        			$enMessage=empty($err_message->enMessage)?'':'ErrorMessage:'.$err_message->enMessage;
        			$enAction=empty($err_message->enAction)?'':$err_message->enAction;
        			$defineMessage=empty($err_message->defineMessage)?'':'错误信息补充说明:'.$err_message->defineMessage;
        			if(!empty($code)){
        				return self::getResult(1,'','错误代码:'.$code.'<br/>'.$cnMessage.$cnAction.'<br/>'.$enMessage.$enAction.'<br/>'.$defineMessage);//1为错误信息
        			}
        		}
        		//exit;
            }else{//另一种就是查找成功，查看是否有跟踪号
            	$arr=['A2','A3','A4','A5','AB','AG','A8','AH','A6','AC','AE','AF','AI','AJ'];
            	$a=$service->shipping_method_code;
            	$isin = in_array($a,$arr);
                  if(empty($response->trackingNumber)){
                      return self::getResult(1,'','暂时没获取到跟踪号！');
                  }else{//物流号获取成功 
                      $shipped->tracking_number=$response->trackingNumber;
//                       print_r($shipped->tracking_number);
//                       exit;
              
                      $shipped->save();
                      $order->tracking_number = $shipped->tracking_number;
                      $order->save();
                      return  self::getResult(0,'','查询成功成功!跟踪号'.$response->trackingNumber);
                  }
              }           
            }else{
                return self::getResult(1,'','没有找到相关的订单信息');//1为失败
            }
        
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }
    

    /**
     +----------------------------------------------------------
     * 打单
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2015/08/12				初始化
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
			$token= $account_api_params['token'];
			//$this->token = $account_api_params['token'];
				
			$param = array();
			$url_params = '';
			$guahao=array();
			$g='';
			// object required 配置信息
			//print_r(count($data));
			foreach ($data as $v) {
				//print_r($data);
				//print_r(count($data));
				$listorder = array();// object/array required 订单信息
				$oneOrder = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
				$shipped = $checkResult['data']['shipped'];
				
				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}
				$ordercode=$shipped->tracking_number;
				$g.=$ordercode.(empty($ordercode)?'':',');			
			}
			//print_r($data);
			//取得打印尺寸
			$printMode = $service['carrier_params']['printMode'];
			$printWhich = $service['carrier_params']['printWhich'];
			if(count($data)>1){
				$url_params = 'multiple?token='.$token.'&orderCode='.$g.'&printMode='.(empty($printMode) ? 'v' : $printMode).'&printWhich='.(empty($printWhich) ? 'all' : $printWhich);
				$url = self::$wsdl3;
// 				print_r($url.$url_params);
// 				exit;
				//$response = Helper_Curl::post($url,$url_params,null);
				$response = Helper_Curl::post($url.$url_params,null,null,false,null);
			}
			else{
				$url_params = $token.'/'.$shipped->tracking_number.'?printMode='.(empty($printMode) ? 'v' : $printMode).'&printWhich='.(empty($printWhich) ? 'all' : $printWhich);
				$url = self::$wsdl3;
// 				print_r($url.$url_params);
//  				exit;
				$response = Helper_Curl::get($url.$url_params,null,null);
				
			}
// 			print_r($response);
// 			exit;
			if(strlen($response)<1000){
				foreach ($data as $v){
					$oneOrder = $v['order'];
					//print_r($oneOrder);
					$oneOrder->carrier_error = $response;
					$oneOrder->save();
				}
				return self::getResult(1, '', $response);
			}
			$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$oneOrder->customer_number, 0);
			$pdf->addPDF($pdfUrl['filePath'],'all');
			foreach ($data as $v){
				$oneOrder = $v['order'];
// 				$oneOrder->is_print_carrier = 1;
				$oneOrder->print_carrier_operator = $puid;
				$oneOrder->printtime = time();
				$oneOrder->save();
			}				
			//合并多个PDF  这里还需要进一步测试
			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
    }
    /**
     +----------------------------------------------------------
     * 取消订单
     +----------------------------------------------------------
     **/
    public function cancelOrderNO($data){
       // return self::getResult(1, '', '物流接口不支持取消物流单。');
    	try{
    		$order = $data['order'];
    		//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
    		$checkResult = CarrierAPIHelper::validate(0,1,$order);
    		$shipped = $checkResult['data']['shipped'];
    		
    		$info = CarrierAPIHelper::getAllInfo($order);
    		$account = $info['account'];
    		//认证参数
    		$params=$account->api_params;
    		$token=$params['token'];
    		
    		$request = array(
        			'arg0'=>$token,
        			'arg1'=>$order->customer_number,
        	);
//      		print_r($request);
//     		exit;
            $response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'removeOrderService');
    		$response = $response['data'];
        	$response=$response->return;
        //	print_r($response);
        	//exit();
    		if($response){//假如查找失败，为空，否则有数据,
    			if($response->ack=="Failure"){//有错误信息，报错
    				$err=$response->errors;	
    				$code=$err->code;
    				//print_r($err);
    				$cnMessage=empty($err->cnMessage)?'':'错误信息:'.$err->cnMessage;
        			$cnAction=empty($err->cnAction)?'':$err->cnAction;
        			$enMessage=empty($err->enMessage)?'':'ErrorMessage:'.$err->enMessage;
        			$enAction=empty($err->enAction)?'':$err->enAction;
        			$defineMessage=empty($err->defineMessage)?'':'错误信息补充说明:'.$err->defineMessage;
        			if(!empty($code)){
        				return self::getResult(1,'','错误代码:'.$code.'<br/>'.$cnMessage.$cnAction.'<br/>'.$enMessage.$enAction.'<br/>'.$defineMessage);//1为错误信息
        			}
    			}else{//另一种就是查找成功，查看是否有跟踪号
//     				print_r($response);
//     				exit;
    					$shipped->delete();
		                 $order->carrier_step = OdOrder::CARRIER_CANCELED;
		                 $order->customer_number = '';
// 		                 $order->is_print_carrier=0;
		                 $order->save();
		                 return BaseCarrierAPI::getResult(0, '', '结果：订单已取消!时间:'.date('Y:m:d H:i:s',time()));
    				}
    		}else{
    			return self::getResult(1,'','没有找到相关的订单信息');//1为失败
    		}
    	
    	}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }
    
   /* public static function TrajectoryCode(){
    	$arr = [
    	AA=>'货物到达港口',
    	AAA=>'',
    	];
    	return $arr;
    }
    */
   /* public function getOrder1(){
    	$request = array(
    			'arg0' => '8BC17B74BD800FEB35F5E7A2DB282ED8',
    			'arg1' => array('orderNo'=>'SI15120000090')
    	);
    	 
    	$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'findOrderService');
    	 
    	print_r($response);
    }
    */
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }
   
}
