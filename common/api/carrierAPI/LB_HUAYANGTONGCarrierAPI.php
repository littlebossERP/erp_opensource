<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use \Jurosh\PDFMerge\PDFMerger;
use eagle\modules\util\helpers\PDFMergeHelper;

class LB_HUAYANGTONGCarrierAPI extends BaseCarrierAPI{
    
    public static $url = null;
    
    public static $debug = false;
    public function __construct(){
        
        if(isset(\Yii::$app->params["currentEnv"]) && \Yii::$app->params["currentEnv"]=='production' && !self::$debug){
            self::$url = "http://www.cglhyt.com";
        }else{
            self::$url = "http://www.cglhyt.com";
        }
    }
    
    /**
     +----------------------------------------------------------
     * 申请订单号
     +----------------------------------------------------------
     **/
     public function getOrderNO($data){
         try{
             set_time_limit(0);
             ignore_user_abort(true);
             
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
             $puid = $checkResult['data']['puid'];
             
             $info = CarrierAPIHelper::getAllInfo($order);
             $account = $info['account'];
             $Service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
             //认证参数
             $account_api_params = $account->api_params;
             
             //检测数据完整性
             if(empty($order->consignee_address_line1) && empty($order->consignee_address_line2))
                 return ['error'=>1, 'data'=>'', 'msg'=>'地址不能为空'];
             
             if(empty($order->consignee))
                 return ['error'=>1, 'data'=>'', 'msg'=>'收件人姓名不能为空'];
             
             if(empty($order->consignee_postal_code))
                 return ['error'=>1, 'data'=>'', 'msg'=>'邮编不能为空'];
             
             if((!isset($order->consignee_phone) || $order->consignee_phone=='') && (!isset($order->consignee_mobile) || $order->consignee_mobile==''))
                 return ['error'=>1, 'data'=>'', 'msg'=>'联系方式不能为空'];
             
             if(empty($order->consignee_country_code))
                 return ['error'=>1, 'data'=>'', 'msg'=>'国家信息不能为空'];
             
             if(empty($form_data['currency']))
                 $form_data['currency'] = "USD";
             
             $order_message = array();
             $order_list = array();
             $order_customs = array();
             
             $weightAmount = 0;
             
             // 组织商品信息
             foreach ($order->items as $i=>$vitem){
                 
                 if(empty($form_data['DeclaredValue'][$i]))
                     return ['error'=>1, 'data'=>'', 'msg'=>'申报价值不能为空'];
                 
                 if(empty($form_data['DeclarePieces'][$i]))
                     return ['error'=>1, 'data'=>'', 'msg'=>'件数不能为空'];
                 
                 if(empty($form_data['DeclareWeight'][$i]))
                     return ['error'=>1, 'data'=>'', 'msg'=>'申报重量不能为空'];
                 
			     if(empty($form_data['DescriptionCn'][$i]))
			        return ['error'=>1, 'data'=>'', 'msg'=>'申报中文不能为空'];
			    
			     if(empty($form_data['DescriptionEn'][$i]))
			        return ['error'=>1, 'data'=>'', 'msg'=>'申报英文不能为空'];
			    
                 
// 			     "OrderItemList": [{
// 			     "Sku": "", //产品 SKU
// 			     "Quantity": "" //产品数量
// 			     }],
                 
                 $order_product_ItemList = array();
                 $order_product_ItemList['Sku'] = $vitem->sku; 
                 $order_product_ItemList['Quantity'] = $form_data['DeclarePieces'][$i];
                 $order_list['OrderItemList'][$i] = $order_product_ItemList;
                 
//                  "CustomsItemList": [{
//                  "Quantity": "", //申报数量
//                  "DescriptionEn": "", //申报英文
//                  "DescriptionCn": "", //申报中文
//                  "Weight": "", //申报重量 必须为数字 单位：kg
//                  "Value": "" //申报价值 必须为数字
//                  }]
                 $order_CustomerItemList = array();
                 $order_CustomerItemList['Quantity'] = $order_product_ItemList['Quantity'];
                 $order_CustomerItemList['DescriptionEn'] = $form_data['DescriptionEn'][$i];
                 $order_CustomerItemList['DescriptionCn'] = $form_data['DescriptionCn'][$i];
                 $order_CustomerItemList['Weight'] = ($form_data['DeclareWeight'][$i] * $order_CustomerItemList['Quantity']) / 1000;
                 $order_CustomerItemList['Value'] = $form_data['DeclaredValue'][$i] * $order_CustomerItemList['Quantity'];
                 
                 $order_customs['CustomsItemList'][$i] = $order_CustomerItemList;//多个商品就多个报关
                 
             }
             
//              "OrderCustoms": {
//              "Currency": "" //报关币种
//              "CustomsType": "" //报关类型 G：礼物、D：文件、S：商业样本、O：其他
//              "CustomsItemList": [{
//              }]
//              }
             
             $order_customs['Currency'] = $form_data['currency'];
             $order_customs['CustomsType'] = $form_data['CustomsType'];
             
             // 地址信息
             // 整理地址信息、电话信息
             // 地址不超过255字符
             // 电话，城市，州不超过50
             // 收件人不超过100
             $addressAndPhoneParams = array(
                 'address' => array(
                     'consignee_address_line1_limit' => 200,
                     'consignee_address_line2_limit' => 200,
                 ),
                 'consignee_district' => 1,
                 'consignee_county' => 1,
                 'consignee_company' => 1,
                 'consignee_phone_limit' => 50
             );
             
             //返回地址信息+电话信息
             $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
             
             $tmpconsignee_country_code = $order->consignee_country_code;
             if($order->consignee_country_code == 'UK'){
                 $tmpconsignee_country_code = 'GB';
             }
             
             $tmpConsigneeProvince = $order->consignee_province;
             if (empty($tmpConsigneeProvince)) {
                 if($order->consignee_country_code == 'FR')
                     $tmpConsigneeProvince = $order->consignee_city;
                 else if(!empty($order->consignee_city))
                     $tmpConsigneeProvince = $order->consignee_city;
                 else
                     return ['error'=>1, 'data'=>'', 'msg'=>'发货时收件人 州/省或城市不能为空'];
             }
             
             
//              "OrderNumber": "", //订单编号
//              "ProductCode": "" //产品代码
//              "Consignee": "", //收件人名
//              "Address1": "", //地址第一行
//              "Address2": "", //地址第二行
//              "City": "", //城市
//              "State": "", //州
//              "CountryCode": "", // 国家简码
//              "PhoneNumber": "", //电话
//              "Zip": "", //邮编
//              "Email": "", //电子邮箱
//              "Remark": "", //订单备注

             $order_list['OrderNumber'] = $customer_number;
             $order_list['ProductCode'] = $Service->shipping_method_code;
             $order_list['Consignee'] = $order->consignee;
             
             $order_list['Address1'] = $addressAndPhone['address_line1'];
             $order_list['Address2'] = $addressAndPhone['address_line2'];
             
             $order_list['City'] = $order->consignee_city;
             $order_list['State'] = $tmpConsigneeProvince;
             $order_list['CountryCode'] = $tmpconsignee_country_code; 
             $order_list['Zip'] = $order->consignee_postal_code; 
             
             //收货人电话 ，$addressAndPhone['phone1']设置了100长度，可能已经包含了固话
             $order_list['PhoneNumber'] = $addressAndPhone['phone1'];
             $order_list['Email'] = $order->consignee_email;
             $order_list['Remark'] = $form_data['remark'];
             
             $order_list['OrderCustoms'] = $order_customs;
             
             $order_message[0] = $order_list;//可以有多个订单
             
             $account_api_params = $account->api_params;
             $param = [];
             // 账号ApiToken
             $param['ApiToken'] = $account_api_params['ApiToken'];
             $param['OrdersList'] = $order_message;
             
             \Yii::info('LB_HUAYANGTONGCarrierAPI,request,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($param), "carrier_api");
             
             /**
{
"ApiToken": "", //客户api秘钥
"OrdersList": [{
		"OrderNumber": "", //订单编号
		"ProductCode": "" //产品代码
		"Consignee": "", //收件人名
		"Address1": "", //地址第一行
		"Address2": "", //地址第二行
		"City": "", //城市
		"State": "", //州
		"CountryCode": "", // 国家简码
		"PhoneNumber": "", //电话
		"Zip": "", //邮编
		"Email": "", //电子邮箱
		"Remark": "", //订单备注
		"OrderItemList": [{
			"Sku": "", //产品 SKU
			"Quantity": "" //产品数量
		}],
		"OrderCustoms": {
			"Currency": "" //报关币种
			"CustomsType": "" //报关类型 G：礼物、D：文件、S：商业样本、O：其他 
			"CustomsItemList": [{
				"Quantity": "", //申报数量
				"DescriptionEn": "", //申报英文
				"DescriptionCn": "", //申报中文
				"Weight": "", //申报重量 必须为数字 单位：kg
				"Value": "" //申报价值 必须为数字
			}]
		}
	}]
}
              **/
             
             $url = self::$url.'/CustomerApi/createdOrder';
             \Yii::info("LB_HUAYANGTONGCarrierAPI getOrder params:".json_encode($param), "carrier_api");
             
             $header = array();
             $header[] = 'Content-type: application/json;charset=utf-8';
             $header[] = 'Accept: application/json';
             
             $order_respond = Helper_Curl::post2($url, json_encode($param), $header);
             
             \Yii::info("LB_HUAYANGTONGCarrierAPI getOrder result:".$order_respond.PHP_EOL."post info:".print_r(Helper_Curl::$last_post_info, true), "carrier_api");
             
             /**
返回数据
{
	"Status": "success", // 是否成功
	"ErrorMessage": "", // 错误信息
	"Result": 
		[	{
				"OrderNumber": "", // 订单编号
				"Status": " success",
				"TrackingNo": "", //跟踪号码
				"Error": "" // 处理失败原因
			},
			{
				"OrderNumber": "",
				"Status": "success",
				"TrackingNo": "",
				"Error": ""
			}
		]
}
              */
             
             
             $result = json_decode($order_respond, true);
             if(!empty($result['Status']) && $result['Status'] == "success"){//验证POST数据是否成功
                 foreach ($result['Result'] as $res){
                     if($res['Status'] == "success"){
                         //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
                         $track_no=empty($res['TrackingNo'])?null:$res['TrackingNo'];
                         $r = CarrierAPIHelper::orderSuccess($order, $Service, $res['OrderNumber'], OdOrder::CARRIER_WAITING_GETCODE, $track_no);
                         return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$res['OrderNumber']);
                     }else{
                         if(!empty($res['Error'])){
                             throw new CarrierException($res['Error']);
                         }else {
                             throw new CarrierException("上传订单失败");
                         }
                     }
                 }
             }else{
                 if(!empty($result['ErrorMessage'])){
                     throw new CarrierException($result['ErrorMessage']);
                 }else{
                     throw new CarrierException("上传数据失败");
                 }
             }
             
//              return $result;
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
         return BaseCarrierAPI::getResult(1, '', '系统不支持取消物流单。');
     }
     
    /**
     +----------------------------------------------------------
     * 交运
     +----------------------------------------------------------
    **/
     public function doDispatch($data){
         return BaseCarrierAPI::getResult(1, '', '该物流商接口不支持交运物流单，上传物流单便会立即交运。');
     }
     
    /**
     +----------------------------------------------------------
     * 申请跟踪号
     +----------------------------------------------------------
    **/
     public function getTrackingNO($data){
         return BaseCarrierAPI::getResult(1,'','该物流商接口不支持获取跟踪号，上传物流单便会立即获取到跟踪号。');
     }
    
    /**
     +----------------------------------------------------------
     * 打单
     +----------------------------------------------------------
    **/
     public function doPrint($data){
         try{
             $pdf = new PDFMerger();
             
             $user=\Yii::$app->user->identity;
             if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
             $puid = $user->getParentUid();
             
             $all_message = current($data);reset($data);//打印时是逐个运输方式的多张订单传入，所以获取一次account、service的信息就可以了
             $order_object=$all_message['order'];//获取订单的对象
             //获取到所需要使用的数据
//              print_r($all_message);
             $info = CarrierAPIHelper::getAllInfo($order_object);
             $account = $info['account'];
             $service = $info['service'];
//              print_r($info);

             
             $account_api_params = $account->api_params;
             $param = [];
             // 账号ApiToken
             $param['ApiToken'] = $account_api_params['ApiToken'];
             
             // 返回pdf文件流
             $url = self::$url.'/CustomerApi/printLabel';
             
             $header = array();
             $header[] = 'Content-type: application/json;charset=utf-8';
             $header[] = 'Accept: application/json';
             
             $index=0;
             foreach ($data as $detail_data){
                 $order = $detail_data['order'];
                 
                 $param['OrdersList'] = [];
                 $param['OrdersList'][] =  ['OrderNumber'=>$order->customer_number];
                 
				 /**
                 {
					"ApiToken": "",//客户api秘钥
					"OrdersList": [ //需要打印的订单 ID
					{
						"OrderNumber":  " //订单ID
					},
					{
						"OrderNumber": "" //订单ID
					}]
				 }
				 */
                 
                 $print_result = Helper_Curl::post2($url, json_encode($param), $header);
                 
                 \Yii::info('LB_HUAYANGTONGCarrierAPI print result,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.
                         PHP_EOL.'param:'.json_encode($param).PHP_EOL.'result:'.$print_result,"carrier_api");
                 
                 /**
                 {
                     "Status":"fail",
                     "ErrorMessage":"ErrorMessage",
                 }
                 */
                 $result = json_decode($print_result, true);
                 
                 // 成功直接返回文件流，否则是下面的Json
                 if(empty($result) || empty($result['Status'])){//验证POST数据是否成功
                     $print_pdf_result = base64_decode($print_result);
                     
                     if(strlen($print_pdf_result)>1000){
                         $pdfurl = CarrierAPIHelper::savePDF($print_pdf_result,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
//                          $tmpPath[] = $pdfurl['filePath'];
                         $pdf->addPDF($pdfurl['filePath'],'all');//合并相同运输运输方式的pdf流
                         $order->print_carrier_operator = $puid;
                         $order->printtime = time();
                         $order->carrier_error = '';
                         $order->save();
                     }else{
                         if(strlen($print_result)>1000){
                             $pdfurl = CarrierAPIHelper::savePDF($print_result,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
//                              $tmpPath[] = $pdfurl['filePath'];
                             $pdf->addPDF($pdfurl['filePath'],'all');//合并相同运输运输方式的pdf流
                             $order->print_carrier_operator = $puid;
                             $order->printtime = time();
                             $order->carrier_error = '';
                             $order->save();
                         }else{
                             return self::getResult(1, '', "打印失败，请联系技术人员");
                         }
                     }
                     
                 }else{
                     \Yii::info('LB_HUAYANGTONGCarrierAPI print error,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.
                             PHP_EOL.'param:'.json_encode($param).PHP_EOL.'result:'.$print_result,"carrier_api");
                      
                     if(!empty($result['ErrorMessage'])){
                         throw new CarrierException($result['ErrorMessage']);
                     }else{
                         throw new CarrierException("上传数据失败");
                     }
                 }
             }
             
//              $pdfmergeResult = PDFMergeHelper::PDFMerge($pdfUrl['filePath'] , $tmpPath);
//              if($pdfmergeResult['success'] == true){
//                  return ['error'=>0, 'data'=>['pdfUrl'=>$pdfmergeResult['pdfUrl']], 'msg'=>'连接已生成,请点击并打印'];
//              }else{
//                  return ['error'=>1, 'data'=>'', 'msg'=>$pdfmergeResult['message']];
//              }
             
             isset($pdfurl)?$pdf->merge('file', $pdfurl['filePath']):$pdfurl['filePath']='';//需要物理地址
             return self::getResult(0,['pdfUrl'=>$pdfurl['pdfUrl']],'连接已生成,请点击并打印');//访问URL地址

         }catch(CarrierException $e){
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
	     $result = array('is_support'=>1,'error'=>1);
	 
	     try{
	        $puid = \Yii::$app->user->identity->getParentUid();
	        $param = [];
             // 账号ApiToken
            $param['ApiToken'] = $data['ApiToken'];
            
            $header = array();
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json';
            
            $url = self::$url.'/CustomerApi/getChannelCodeList';
            
            $serviceResultStr = Helper_Curl::post2($url, json_encode($param), $header);
//             \Yii::info('LB_HUAYANGTONGCarrierAPI getVerifyCarrierAccountInformation,param:'.json_encode($param).PHP_EOL.'result:'.$serviceResultStr, "carrier_api");
            
            $serviceResult = json_decode($serviceResultStr, true);
             
            if(!empty($serviceResult['Status']) && $serviceResult['Status'] == "success"){//验证POST数据是否成功
                $result['error'] = 0;
            }
            
	     }catch(CarrierException $e){
	     }
	 
	     return $result;
	 }
	 
	 //获取运输方式
    public function getCarrierShippingServiceStr($account){
        try{
            $account_api_params = $account->api_params;
             
            $param = [];
             // 账号ApiToken
            $param['ApiToken'] = $account_api_params['ApiToken'];
             
            
            $header = array();
            $header[] = 'Content-type: application/json;charset=utf-8';
            $header[] = 'Accept: application/json';
            
            $url = self::$url.'/CustomerApi/getChannelCodeList';
            
            $result = Helper_Curl::post2($url, json_encode($param), $header);
//             echo $url.PHP_EOL.print_r($result, true).PHP_EOL;
//             exit();
//             $result = '{"code":200,"msg":"操作成功","description":null,"data":[{"prodCode":"ANSERX_HYCDGH","prodName":"ANSERX_荷邮纯电挂号"},{"prodCode":"ANSERX_SF_GTI_NL_3241","prodName":"安小包_荷兰邮政挂号"},{"prodCode":"AXB_SF_TH","prodName":"安小包-顺丰特惠"},{"prodCode":"AXB_USA_Anserx","prodName":"安小包-美国小包专线（普内）"},{"prodCode":"AXB_USA_FT2421","prodName":"中美专线（普货）"},{"prodCode":"AXB_US_Anserx_ND","prodName":"安小包-美国专线（带电）"},{"prodCode":"A_US","prodName":"美国专线"},{"prodCode":"BS-ZX","prodName":"E特快-白石物流"},{"prodCode":"CSyz","prodName":"CS邮政"},{"prodCode":"MangoZh","prodName":"芒果账号"},{"prodCode":"MangoZx","prodName":"芒果专线"},{"prodCode":"TEST","prodName":"测试-（邮政）-互联通"},{"prodCode":"TEST-D","prodName":"测试产品D"},{"prodCode":"TEST-FT-001-ZX","prodName":"TEST-FT-001-ZX"},{"prodCode":"TEST-H","prodName":"测试-测试产品H"},{"prodCode":"TEST-XBCP","prodName":"小北产品"},{"prodCode":"TEST-XZXJ","prodName":"小江专线"},{"prodCode":"test-zr-2","prodName":"test-zr-2"},{"prodCode":"TEST1","prodName":"测试-（邮政）-华磊"},{"prodCode":"TEST10","prodName":"TEST10"},{"prodCode":"TEST11","prodName":"TEST11"},{"prodCode":"TEST12","prodName":"TEST12"},{"prodCode":"TEST2","prodName":"测试-E特快"},{"prodCode":"TEST4","prodName":"TEST4"},{"prodCode":"TEST5","prodName":"TEST5"},{"prodCode":"TEST6","prodName":"TEST6"},{"prodCode":"TEST7","prodName":"TEST7"},{"prodCode":"TEST8","prodName":"TEST8"},{"prodCode":"TEST9","prodName":"TEST9"},{"prodCode":"T_ZH","prodName":"业务账号产品"},{"prodCode":"T_ZX","prodName":"业务专线产品"}],"speedErrorCode":null,"exend":{},"success":true}';
            /**
             * 
             * {
"Status": "success", //是否成功
"ErrorMessage": "", //错误信息
"Result": //产品列表
	[	{
			"Code": "001", // 产品编码
			"Name": "", // 产品中文
		},
		{
			"Code": "001", // 产品编码
			"Name": "", // 产品中文
		}
	]
}
             * 
             */
            
            $resultObj = json_decode($result);
            $channelStr = "";
            if(!empty($resultObj->Status) && $resultObj->Status == "success"){//验证POST数据是否成功
                $serviceData = $resultObj->Result;
                foreach ($serviceData as $service){
                    $channelStr .= "{$service->Code}:{$service->Name};";
                }
            }
            
            if(empty($channelStr)){
                return self::getResult(1, '', '');
            }else{
                return self::getResult(0, $channelStr, '');
            }
	    }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	 }
	 
	 //获取运输方式
	 public function getCarrierShippingService(){
	     try{
	         $param = [];
	         // 账号ApiToken
	         
// 	         $param['ApiToken'] = "8ad7256d5ff0afc7260c7f8d640879d5";
	         $param['ApiToken'] = "c76803465c5841a5107d28afd433e492";// dzt20191104 华洋通通知更新token
	          
	         $header = array();
	         $header[] = 'Content-type: application/json;charset=utf-8';
	         $header[] = 'Accept: application/json';
	 
	         $url = self::$url.'/CustomerApi/getChannelCodeList';
	 
	         $result = Helper_Curl::post2($url, json_encode($param), $header);
	         
	         $resultObj = json_decode($result);
	         $channelStr = "";
	         if(!empty($resultObj->Status) && $resultObj->Status == "success"){//验证POST数据是否成功
	             $serviceData = $resultObj->Result;
	             foreach ($serviceData as $service){
	                 $channelStr .= "{$service->Code}:{$service->Name};";
	             }
	         }
	 
	         if(empty($channelStr)){
	             return self::getResult(1, '', '');
	         }else{
	             return self::getResult(0, $channelStr, '');
	         }
	     }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	 }
	 
	 
	 // sign 签名 sign = MD5(custCode+apiKey+t)
	 private function sign($param){
	     return md5($param['custCode'].$param['apiKey'].$param['t']);
	 }
	 
	 
	 
	 
	 
}
