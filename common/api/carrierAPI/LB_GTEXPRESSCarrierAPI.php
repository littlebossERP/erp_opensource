<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
include '../components/PDFMerger/PDFMerger.php';

class LB_GTEXPRESSCarrierAPI extends BaseCarrierAPI{
    static private $wsdl = '';	// 物流接口
    static private $userToken = '';
    static private $apiToken='';
    private $submitGate = null;	// SoapClient实例
    
    static $connecttimeout=60;
    static $timeout=500;
    static $last_post_info=null;
    static $last_error =null;
    
    public function __construct(){
    	
    	if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
    		self::$wsdl = 'http://120.24.93.23:8086/xms/services/order?wsdl';//正式环境
    	}else{
    		self::$wsdl = 'http://www.360chain.com:8086/xms/services/order?wsdl';//测试接口
    	}
      
//        self::$userToken = '8a3ddab172a74ad887be919a2aa41641';
//        self::$apiToken='76c683d2a1024d7ab2b5ba69d6931455';
       $this->client = new \SoapClient(self::$wsdl, array ('encoding' => 'UTF-8' ));
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
        	//认证参数
        	$params=$account->api_params;
        	$normal_params = $service->carrier_params;
        	$shippingfrom_address = $account->address['shippingfrom'];//获取到账户中的地址信息(shppingfrom是属于客户填“发货地址”的信息)
        	$token=$params['userToken'];
        	//print_r( $data['data']);
        	//exit();
        	
	        if (empty($service->shipping_method_code)){
	        		return self::getResult(1, '', '没有匹配到运输方式');
	        }
	        
	        if (empty($order->consignee_country_code)){
	        	return self::getResult(1, '', '目的国家不能为空');
	        }
	        
	        if (empty($order->consignee)){
	        	return self::getResult(1, '', '收件人姓名不能为空');
	        }
	        
	        if (empty($order->consignee)){
	        	return self::getResult(1, '', '收件人姓名不能为空');
	        }
	        
	        if (empty($order->consignee_city)){
	        	return self::getResult(1, '', '城市不能为空');
	        }
	         
	        if (empty($order->consignee_province)){
	        	return self::getResult(1, '', '省份不能为空');
	        }
	         
	        if (empty($order->consignee)){
	        	return self::getResult(1, '', '收件人姓名不能为空');
	        }
	        
	        if (empty($form_data['weight'])){
	        	return self::getResult(1, '', '预报重量不能为空');
	        }
        	$phoneContact = (empty($order->consignee_phone) ? $order->consignee_mobile : $order->consignee_phone);
        	$phoneTel=(empty($shippingfrom_address['phone']) ? $shippingfrom_address['mobile'] : $shippingfrom_address['phone']);
        	
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
        					'consignee_address_line1_limit' => 200,
        			),
        			'consignee_district' => 1,
        			'consignee_county' => 1,
        			'consignee_company' => 1,
        			'consignee_phone_limit' => 32
        	);
        		
        	$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
        	
        	$productList =array();
        	$weight=0;
        	$pieces=0;
         	foreach ($order->items as $k=>$v){
         		$pieces+=$form_data['DeclarePieces'][$k];
//   				$weight+=$form_data['netWeight'][$k]*$form_data['DeclarePieces'][$k]/1000;
//   				$weight=round($weight,3);
  				
        		$productList[$k]=[
        			'cnName'=>$form_data['cnName'][$k],
        			'customsNo'=>$form_data['customsNo'][$k],
        			'name'=>$form_data['Name'][$k],
        			'netWeight'=>$form_data['netWeight'][$k]/1000,
        			'pieces'=>$form_data['DeclarePieces'][$k],
        			'productMemo'=>$form_data['productMemo'][$k],
	        		'unitPrice'=>$form_data['unitPrice'][$k],   		
	        		
        		];
        		//print_r($productList);
        		if (empty($productList[$k]['name'])){
        			return self::getResult(1, '', '英文申报名不能为空');
        		}
        		
        		if(empty($productList[$k]['pieces'])){
        			return self::getResult(1,'', '件数不能为空');
        		}
        		
        		if(empty($productList[$k]['unitPrice'])){
        			return self::getResult(1,'', '单价不能为空');
        		}
        		
        		if (empty($productList[$k]['netWeight'])){
        			return self::getResult(1, '', '重量不能为空');
        		}

        	}
//  			print_r($order->items);
//  			exit;
        	$orderMain =array(
        		'cargoCode'=>$normal_params['CargoCode'],
        		'city'=>$order->consignee_city,
        		'consigneeCompanyName'=>$order->consignee_company,
        		'consigneeMobile'=>$addressAndPhone['phone2'],
        		'consigneeName'=>$order->consignee,
        		'consigneePostcode'=>$order->consignee_postal_code,
        		'consigneeTelephone'=>$addressAndPhone['phone1'],
        		
        		'declareItems'=>$productList,
        		'destinationCountryCode'=>$order->consignee_country_code,
        		'goodsCategory'=>$form_data['goodsCategory'],
        		'goodsDescription'=>$form_data['goodsDescription'],
        		'insured'=>$normal_params['insured'],
        		'memo'=>$form_data['Note'],
				'orderNo'=>$customer_number,
        		'originCountryCode'=>$shippingfrom_address['originCountryCode'],
				'pieces'=>$pieces,
        		'province'=>$shippingfrom_address['province'],
				'shipperAddress'=>$shippingfrom_address['street'],
				'shipperComanyName'=>$shippingfrom_address['company'],
        		'shipperMobile'=>$shippingfrom_address['mobile'],
				'shipperName'=>$shippingfrom_address['contact'],
				'shipperPostcode'=>$shippingfrom_address['postcode'],
				'shipperTelephone'=>$phoneTel,
				'street'=>$addressAndPhone['address_line1'],
        		'trackingNo'=>'',
				'transportWayCode'=>$service->shipping_method_code,
        		'weight'=>'',
        	);
//        	print_r($orderMain);
//         	exit();  	
	
        	$request = $orderMain;
//         	print_r($request);
// 	       	exit();
        	$response = $this->client->createAndAuditOrder($token,$request);
//         	print_r($response);
//         	exit;
        	
        	if($response->success){
        		$track_num=empty($response->trackingNo)?null:$response->trackingNo;
				
				$return_no['OrderSign']=$response->id;
        		$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_FINISHED ,$track_num,$return_no);
        		return  self::getResult(0,$r,'操作成功!订单参考号'.$customer_number);
        	}
        	else{
//         		print_r($response);
//         		exit;
        		$err=$response->error;	
        		$code=$err->errorCode;
//         			print_r($code);
//         			exit;
        		$Info=empty($err->errorInfo)?'':'错误信息:'.$err->errorInfo;
        		$solution=empty($err->solution)?'':$err->solution;
        		if(!empty($code)){
        			return self::getResult(1,'','错误代码:'.$code.'<br/>'.$Info.';'.$solution);
        		}
        		return self::getResult(1,'','物流商返回数据错误');
        		
        	}
        }
        catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }

    
    /**
      +----------------------------------------------------------
     * 获取运输方式
      +----------------------------------------------------------
     **/
    public function gettransport(){
    	$str='';
//     	$client = new \SoapClient(self::$wsdl, array ('encoding' => 'UTF-8' ));
        $order_register = $this->client->getTransportWayList($userToken);
        $str='';
        foreach ($order_register->transportWays as $transport){
        	$str.=$transport->code.':'.$transport->name.';';
        }
       return $str;
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
    	return self::getResult(1, '', '物流接口不支持申请跟踪号，上传物流单便会立即申请跟踪号。');
    }
    

    /**
     +----------------------------------------------------------
     * 打单
     +----------------------------------------------------------
     **/
    public function doPrint($data){
    	return self::getResult(1, '', '物流接口不支持打印面单，如需要请登录系统后台操作。');
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
    		$token=$params['userToken'];

            $response = $this->client->deleteOrder($token,$shipped->return_no['OrderSign']);
    		
    		if($response){//假如查找失败，为空，否则有数据,
    			if(!$response->success){//有错误信息，报错
    				$err=$response->error;	
    				$code=$err->errorCode;
	    			$Info=empty($err->errorInfo)?'':'错误信息:'.$err->errorInfo;
	        		$solution=empty($err->solution)?'':$err->solution;
	        		if(!empty($code)){
	        			return self::getResult(1,'','错误代码:'.$code.'<br/>'.$Info.';'.$solution);
	        		}
    			}else{//另一种就是查找成功，查看是否有跟踪号
//     				print_r($response);
//     				exit;
    					$shipped->delete();
		                 $order->carrier_step = OdOrder::CARRIER_CANCELED;
		                 $order->customer_number = '';
		                 $order->is_print_carrier=0;
		                 $order->save();
		                 return BaseCarrierAPI::getResult(0, '', '结果：订单已取消!时间:'.date('Y:m:d H:i:s',time()));
    				}
    		}else{
    			return self::getResult(1,'','没有找到相关的订单信息');//1为失败
    		}
    	
    	}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }
    
  
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }
   
}
