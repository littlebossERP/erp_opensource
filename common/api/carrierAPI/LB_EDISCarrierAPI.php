<?php

namespace common\api\carrierAPI;
use common\api\ebayinterface\config;
use eagle\modules\order\models\OdOrder;
use Qiniu\Http\Client;
use common\helpers\Helper_Curl;
use Qiniu\json_decode;
use common\helpers\SubmitGate;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\util\helpers\PDFMergeHelper;
/**
+------------------------------------------------------------------------------
 * ebay订单eDIOS接口
+------------------------------------------------------------------------------
 * @category	Interface
 * @package
 * @subpackage  Exception
 * @author		lgw 
 * @version		1.0
+------------------------------------------------------------------------------
 */
class LB_EDISCarrierAPI extends BaseCarrierAPI
{
    //物流接口
    static public $wsdl = "https://api.edisebay.com/v1/api";
    //EBAY验证信息
    static public $pubinfo = array();
    
    static private $token="";
        
    //初始化
    public function __construct(){
        //取得EBAY验证信息
//         if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
//         	//正式环境token 通过getFetchToken获取
            // TODO carrier dev account @XXX@
            self::$token=" @XXX@";
            self::$wsdl = 'https://api.edisebay.com/v1/api';
//         }else{
        	//测试环境token
//             self::$token="TGT-22-PkfBGdVdcth1BhaqEHAebBrPlLDv1fd2QwmyfPuFFQuaDFLjud-sbpassport.eis.cn";
//             self::$wsdl = 'https://sandbox.edisebay.com/v1/api';
//         }

		// TODO carrier dev account @XXX@
        self::$pubinfo['devId'] = "@XXX@";
        self::$pubinfo['secret'] = "@XXX@";
        self::$pubinfo['Version'] = '1.0.0';
        
    }
    //申请
    public function getOrderNO($data){
    	try{
    		$user=\Yii::$app->user->identity;
    		if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
    		$puid = $user->getParentUid();
    		
    		//odOrder表内容
    		$order = $data['order'];
    		$o = $order->attributes;
    		
    		//重复发货 添加不同的标识码
    		$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
    		$customer_number = $data['data']['customer_number'];
    		
    		if(isset($data['data']['extra_id'])){
    			if($extra_id == ''){
    				return self::getResult(1, '', '强制发货标识码，不能为空');
    			}
    		}
    		$addressAndPhoneParams = array(
    				'address' => array(
    						'consignee_address_line1_limit' => 1000,
    						'consignee_address_line2_limit' => 1000,
    						// 							'consignee_address_line3_limit' => 100,
    				),
    				'consignee_district' => 1,
    				'consignee_county' => 1,
    				'consignee_company' => 1,
    				'consignee_phone_limit' => 100
    		);
    		
    		$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
    		
    		//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
    		$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
    		
    		//用户在确认页面提交的数据
    		$e = $data['data'];
    		//获取到所需要使用的数据
    		$info = CarrierAPIHelper::getAllInfo($order);
    		$service = $info['service'];
    		$service_carrier_params = $service->carrier_params;
    		$account = $info['account'];
    		//获取到帐号中的认证参数
    		$a = $account->api_params;
    		
    		//物品信息
    		$itemList=array();
//     		$oet = OdEbayTransaction::find()->select(['transactionid','itemid'])->where(['order_id'=>$o["order_id"]])->one();
//     		$transactionid = isset($oet->transactionid)?$oet->transactionid:'';
    		foreach ($order->items as $j=>$vitem){	
    			
    		    // dzt20190815 发现order_source_transactionid 为0 订单再次同步还是0，392381865995-0，尝试提交给物流
    			// if(!empty($vitem['order_source_itemid']) && !empty($vitem['order_source_transactionid']))
    			if(!empty($vitem['order_source_itemid']) && isset($vitem['order_source_transactionid']) && $vitem['order_source_transactionid'] <> "")
    				$orderLineItem=$vitem['order_source_itemid']."-".$vitem['order_source_transactionid'];
    			else
    				$orderLineItem="";
    			
    			$itemList[]=array(  
    			        // dzt20190815 
    					// "transactionId"=>empty($vitem['order_source_transactionid'])?"":$vitem['order_source_transactionid'],   //eBay交易号
    			        "transactionId"=>isset($vitem['order_source_transactionid'])?$vitem['order_source_transactionid']:"",   //eBay交易号
    					"sku"=>array(
    							"weight"=>$e['weight'][$j],   //重量（单位：g）
    							"price"=>$e['price'][$j],  //申报价格的单位固定为美元
    							"origin"=>"CN",   //原产地
    							"nameZh"=>$e['nameZh'][$j],   //中文申报名
    							"nameEn"=>$e['nameEn'][$j],     //英文申报名
    							"skuNumber"=>$e['skuNumber'][$j],  //SKU编号
    							"remark"=>$e['remark'][$j],  //备注
    							"liBatteryType"=>empty($e['liBatteryType'][$j])?"0":$e['liBatteryType'][$j],    //带电类型，0:无锂电池；1:内置电池；2:纯电池；3:配套电池（1.5版本）
    							"isLiBattery"=>empty($e['isLiBattery'][$j])?false:$e['isLiBattery'][$j],     //是否带锂电池（1.0版本）
    					),
    					"postedQty"=>$e['postedQty'][$j],   //寄货数量，不能为0
    					"orderLineItem"=>$orderLineItem,   //eBay交易行ID,不知道先填这个
    					"itemId"=>empty($vitem['order_source_itemid'])?'':$vitem['order_source_itemid'],   //eBay物品号
    					"buyerId"=>$o["source_buyer_user_id"],   //eBay买家ID
    			);
    		}
    		
    		//获取小老板系统上当前选择的地址信息
    		$shipFromAddressId=$consignPreferenceId="";
    		if(!empty($service_carrier_params["edisAddressoinfo"])){
    			if(!empty($service_carrier_params["edisAddressoinfo"]["edisAddress"][$o["selleruserid"]])){
    				$edisAddressoinfoArr=explode("&",$service_carrier_params["edisAddressoinfo"]["edisAddress"][$o["selleruserid"]]);
    				$shipFromAddressId=$edisAddressoinfoArr[0];
    			}
    			if(!empty($service_carrier_params["edisAddressoinfo"]["edisConsign"][$o["selleruserid"]])){
    				$edisAddressoinfoArr=explode("&",$service_carrier_params["edisAddressoinfo"]["edisConsign"][$o["selleruserid"]]);
    				$consignPreferenceId=$edisAddressoinfoArr[0];
    			}
    		}
    		//没有的时候取接口获取的第一个
    		if(empty($shipFromAddressId)){
    			$AddressPreferenceList=self::getAddressPreferenceList($o["selleruserid"]);
    			if(!empty($AddressPreferenceList["data"])){
    				$shipFromAddressId=$AddressPreferenceList["data"][0]["addressId"];
    			}
    		}
    		if(empty($consignPreferenceId)){
    			$ConsignPreferenceList=self::getConsignPreferenceList($o["selleruserid"]);
    			if(!empty($ConsignPreferenceList["data"])){
    				$consignPreferenceId=$ConsignPreferenceList["data"][0]["consignId"];
    			}
    		}
    		
    		if(empty($shipFromAddressId) || empty($consignPreferenceId))
    			return self::getResult(1,'',"请检查eDIS后台是否有绑定当前eaby账号 或 eDIS后台是否有设置地址信息和交运偏好信息");
    		
    		
    		
    		//收货地址信息
    		$shipToAddress=array(
    				"street1"=>$addressAndPhone['address_line1'],  //街道地址1
    				"province"=>$o["consignee_province"],         //省,国家为德国时可选
    				"postcode"=>$o["consignee_postal_code"],    //邮编
    				"mobile"=>$addressAndPhone['phone1'],           //移动电话
    				"countryName"=>$o["consignee_country"],    //国家名称
    				"countryCode"=>$o["consignee_country_code"],    //国家代码
    				"contact"=>$o["consignee"],       //联系人
    				"city"=>$o["consignee_city"],     //城市
    		);
    		
    		//组织data
    		$parm=array(
    				"shipToAddress"=>$shipToAddress,
    				"shipFromAddressId"=>$shipFromAddressId,       //发货地址ID
    				"serviceId"=>$service->shipping_method_code,    //物流服务ID
    				"packageWidth"=>empty($e["packageWidth"])?1:$e["packageWidth"],              //包裹宽度（cm）
    				"packageWeight"=>empty($e["packageWeight"])?1:$e["packageWeight"],           //包裹重量（g）
    				"packageLength"=>empty($e["packageLength"])?1:$e["packageLength"],          //包裹长度（cm）
    				"packageHeight"=>empty($e["packageHeight"])?1:$e["packageHeight"],        //包裹高度（cm）
    				"consignPreferenceId"=>$consignPreferenceId,      //交运偏好ID
    				"itemList"=>$itemList,
    		);
    		
    		//组织提交数据
    		$token=self::$token;
	    	if(empty($token))
	    		return self::getResult(1,'',"密钥错误");
    		 
    		$url=self::$wsdl."/AddPackage";
    		
    		$header=self::getHeader(null,null,$token);
    		
    		$request["messageId"]=self::getMessageid();
    		$request["timestamp"]=time();
    		$request["ebayId"]=$o["selleruserid"];
    		$request["data"]=$parm;

//     		print_r($request);die;
    		\Yii::info('LB_EDIS,puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
    		$response=Helper_Curl::post($url,json_encode($request), $header);
    		\Yii::info('LB_EDIS,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response,"carrier_api");
    		
    		$response_arr=json_decode($response,true);
		
    		if(isset($response_arr["status"]["resultCode"]) && $response_arr["status"]["resultCode"]==200){    			
    			$trackingNumber=$response_arr["data"]["trackingNumber"];
    			$packageId=$response_arr["data"]["packageId"];
    			
    			$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_DELIVERY,$trackingNumber,['packageId'=>$packageId]);
    			
    			//一体化
    			try{
    				$print_param = array();
    				$print_param['carrier_code'] = $service->carrier_code;
    				$print_param['api_class'] = 'LB_EDISCarrierAPI';
    				$print_param['tracking_number'] = empty($trackingNumber) ? '' : $trackingNumber;
    				$print_param['packageId'] = $packageId;
    				$print_param['carrier_params'] = $service->carrier_params;
    				$print_param['ebayId'] = $o["selleruserid"];
    				CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
    			}catch(\Exception $ex){}
    			
    			return  BaseCarrierAPI::getResult(0,$r,'操作成功!包裹号'.$packageId.'物流跟踪号:'.$trackingNumber);
    		
    		}
    		else{
    			if(isset($response_arr["status"]["message"]))
    				$result["message"]=$response_arr["status"]["message"];
    			else if(isset($response_arr["message"]))
    				$result["message"]=$response_arr["message"];
    			else
    				$result["message"]="网络错误";
    			
    			return self::getResult(1,'',$result["message"]);
    		}
    			
    	}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
    }

    //取消
    public function cancelOrderNO($data){
        return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消订单。');
        
        try{
        	$order = $data['order'];
        	$o = $order->attributes;
        	
        	//对当前条件的验证
        	$checkResult = CarrierAPIHelper::validate(0,1,$order);
        		
        	$info = CarrierAPIHelper::getAllInfo($order);
        	$account = $info['account'];
        	//获取到帐号中的认证参数
        	$a = $account->api_params;
        	
        	
        	$shipped = $checkResult['data']['shipped'];
        	
        	if(empty($shipped->return_no) || empty($shipped->return_no["packageId"]))
        		return self::getResult(1, '', "包裹ID丢失,找不到订单");
        	
        		
        	return self::DeletePackagesRequest($o["selleruserid"],array($shipped->return_no["packageId"]));
        	
        	
        }catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
        	
    }

    //交运
    public function doDispatch($data){
    	try{
			$order = $data['order'];
			$o = $order->attributes;

			//对当前条件的验证
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			//获取到帐号中的认证参数
			$a = $account->api_params;

				
			$shipped = $checkResult['data']['shipped'];
	
			if(empty($shipped->return_no) || empty($shipped->return_no["packageId"]))
				return self::getResult(1, '', "包裹ID丢失,找不到订单");
				
			
			$parm=array(
					"packageIds"=>array($shipped->return_no["packageId"]), //需要交运的包裹ID列表
			);
			
			
    		//组织提交数据
    		$token=self::$token;
	    	if(empty($token))
	    		return self::getResult(1,'',"密钥错误");
    		 
    		$url=self::$wsdl."/ConfirmPackages";
    		
    		$header=self::getHeader(null,null,$token);
    		
    		$request["messageId"]=self::getMessageid();
    		$request["timestamp"]=time();
    		$request["ebayId"]=$o["selleruserid"];
    		$request["data"]=$parm;

    		$response=Helper_Curl::post($url,json_encode($request), $header);
    		
    		$response_arr=json_decode($response,true);
    		
    		if(isset($response_arr["status"]["resultCode"]) && $response_arr["status"]["resultCode"]==200){    			
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return self::getResult(0,'', '结果：订单交运成功！跟踪号:'.$shipped["tracking_number"]);
    		
    		}
    		else{
    			if(isset($response_arr["status"]["message"]))
    				$result["message"]=$response_arr["status"]["message"];
    			else if(isset($response_arr["message"]))
    				$result["message"]=$response_arr["message"];
    			else
    				$result["message"]="网络错误";
    			
    			return self::getResult(1,'',$result["message"]);
    		}

	
    	}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
    }

    //申请跟踪号
    public function getTrackingNO($data){
        return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
    }

    //打单
    public function doPrint($data){
    	try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆'); 
			$puid = $user->getParentUid();
		
			$order = current($data);reset($data);
			$order = $order['order'];		
			
			//记录第一张订单的卖家账号，由于不同的卖家账号不能一起获取面单，要分开获取的，所以这里先记录首张订单账号
			$tmp_selleruserid = $order->selleruserid;
			
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$params = $account->api_params;
			
			$service = $info['service'];
			$pageSize = $service['carrier_params']['pageSize'];
			
			//面单商品显示内容
			$pageshow="";
			if(!isset($service['carrier_params']['pageshow_ItemID']))
				$pageshow.="itemId,";
			else if(!empty($service['carrier_params']['pageshow_ItemID']))
				$pageshow.=$service['carrier_params']['pageshow_ItemID'].",";
			
			if(!isset($service['carrier_params']['pageshow_skuNo']))
				$pageshow.="skuNo,";
			else if(!empty($service['carrier_params']['pageshow_skuNo']))
				$pageshow.=$service['carrier_params']['pageshow_skuNo'].",";
			
			if(!isset($service['carrier_params']['pageshow_nameZh']))
				$pageshow.="nameZh,";
			else if(!empty($service['carrier_params']['pageshow_nameZh']))
				$pageshow.=$service['carrier_params']['pageshow_nameZh'].",";
			
			if(!isset($service['carrier_params']['pageshow_nameEn']))
				$pageshow.="nameEn,";
			else if(!empty($service['carrier_params']['pageshow_nameEn']))
				$pageshow.=$service['carrier_params']['pageshow_nameEn'].",";
			
			if(isset($service['carrier_params']['pageshow_property']) && !empty($service['carrier_params']['pageshow_property']))
				$pageshow.=$service['carrier_params']['pageshow_property'].",";
			
			if(!isset($service['carrier_params']['pageshow_quantity']))
				$pageshow.="quantity,";
			else if(!empty($service['carrier_params']['pageshow_quantity']))
				$pageshow.=$service['carrier_params']['pageshow_quantity'].",";
			
			if(isset($service['carrier_params']['pageshow_buyerId']) && !empty($service['carrier_params']['pageshow_buyerId']))
				$pageshow.=$service['carrier_params']['pageshow_buyerId'].",";
			
			if(isset($service['carrier_params']['pageshow_sellerId']) && !empty($service['carrier_params']['pageshow_sellerId']))
				$pageshow.=$service['carrier_params']['pageshow_sellerId'].",";
				
			$pageshow=substr($pageshow,0,-1);
			
			//组织提交数据
			$token=self::$token;
			if(empty($token))
				return self::getResult(1,'',"密钥错误");
			
			$url=self::$wsdl."/GetLabel";
				
			$header=self::getHeader(null,null,$token);
				
			$request["messageId"]=self::getMessageid();
			$request["timestamp"]=time();
			$request["ebayId"]=$order["selleruserid"];
			
			foreach ($data as $k => $v) {
				$order_ = $v['order'];
				
				if($tmp_selleruserid != $order_->selleruserid){
					return self::getResult(1,'',"不能同时打印不同ebay账号的面单，请先筛选ebay账号后再打印");
				}
				
				$checkResult = CarrierAPIHelper::validate(0,1,$order_);
				$shipped = $checkResult['data']['shipped'];
				$returnNo = $shipped->return_no;
				if(empty($shipped["tracking_number"]))
					return self::getResult(1,'',"小老板订单号:".$shipped["order_id"]."跟踪号为空");
				
				$parm=array(
						"trackingNumber"=>$shipped["tracking_number"], //包裹跟踪号
						"printPreference"=>$pageshow,  //打印偏好预设，为空时打印所有列，否则按所选择需要打印的列使用逗号拼接字符串.如：（itemId,skuNo,nameZh,nameEn,property,quantity,sellerId,buyerId）
						"pageSize"=>$pageSize,    //标签格式，可用值： 0 - 适用于打印A4 格式标签,1 - 适用于打印4寸 的热敏标签纸格式标签
				);
				
				$request["data"]=$parm;
			
				$response=Helper_Curl::post($url,json_encode($request), $header);
					
				$response_arr=json_decode($response,true);
					
				if(isset($response_arr["status"]["resultCode"]) && $response_arr["status"]["resultCode"]==200){
					
					$pdfurl=base64_decode($response_arr["data"]["base64Str"]);					
					$pdfurl = CarrierAPIHelper::savePDF($pdfurl,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
					
					$filePath[]=$pdfurl["filePath"];
						
				}
				else{
					if(isset($response_arr["status"]["message"]))
						$result["message"]=$response_arr["status"]["message"];
					else if(isset($response_arr["message"]))
						$result["message"]=$response_arr["message"];
					else
						$result["message"]="网络错误";
						
					return self::getResult(1,'',$result["message"]);
				}
			}
							
			$filename=$puid.'_'.$order->order_source_order_id.'_'.$order->customer_number.'_merge_'.time().'.pdf';
			$pdfmergeResult = PDFMergeHelper::PDFMerge(CarrierAPIHelper::createCarrierLabelDir().DIRECTORY_SEPARATOR.$filename,$filePath);
			
			if($pdfmergeResult['success'] == true){
				$pdfmergeResult['filePath'] = str_replace(CarrierAPIHelper::getPdfPathString(), "", $pdfmergeResult['filePath']);
			}else{
				return self::getResult(1, '', $pdfmergeResult['message']);
			}
			
			if(!empty($pdfmergeResult['filePath'])) {
				return self::getResult(0, ['pdfUrl' => $pdfmergeResult['filePath']], '连接已生成,请点击并打印');//访问URL地址
			}
			else{
				return self::getResult(1, '', '打印失败！');
			}

    	 }catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
    }
    
    public function getCarrierLabelApiPdf( $SAA_obj, $print_param)
    {
    	try
    	{
    		$puid = $SAA_obj->uid;
    		$returnMsg = '';
    		$carrier_params = $print_param['carrier_params'];
    		
    		$pageSize = $carrier_params['pageSize'];
    			
    		//面单商品显示内容
    		$pageshow="";
    		if(!isset($carrier_params['pageshow_ItemID']))
    			$pageshow.="itemId,";
    		else if(!empty($carrier_params['pageshow_ItemID']))
    			$pageshow.=$carrier_params['pageshow_ItemID'].",";
    			
    		if(!isset($carrier_params['pageshow_skuNo']))
    			$pageshow.="skuNo,";
    		else if(!empty($carrier_params['pageshow_skuNo']))
    			$pageshow.=$carrier_params['pageshow_skuNo'].",";
    			
    		if(!isset($carrier_params['pageshow_nameZh']))
    			$pageshow.="nameZh,";
    		else if(!empty($carrier_params['pageshow_nameZh']))
    			$pageshow.=$carrier_params['pageshow_nameZh'].",";
    			
    		if(!isset($carrier_params['pageshow_nameEn']))
    			$pageshow.="nameEn,";
    		else if(!empty($carrier_params['pageshow_nameEn']))
    			$pageshow.=$carrier_params['pageshow_nameEn'].",";
    			
    		if(isset($carrier_params['pageshow_property']) && !empty($carrier_params['pageshow_property']))
    			$pageshow.=$carrier_params['pageshow_property'].",";
    			
    		if(!isset($carrier_params['pageshow_quantity']))
    			$pageshow.="quantity,";
    		else if(!empty($carrier_params['pageshow_quantity']))
    			$pageshow.=$carrier_params['pageshow_quantity'].",";
    			
    		if(isset($carrier_params['pageshow_buyerId']) && !empty($carrier_params['pageshow_buyerId']))
    			$pageshow.=$carrier_params['pageshow_buyerId'].",";
    			
    		if(isset($carrier_params['pageshow_sellerId']) && !empty($carrier_params['pageshow_sellerId']))
    			$pageshow.=$carrier_params['pageshow_sellerId'].",";
    		
    		$pageshow=substr($pageshow,0,-1);
    			
    		//组织提交数据
    		$token=self::$token;
    		if(empty($token))
    			return ['error'=>1, 'msg'=>"密钥错误", 'filePath'=>''];

    			
    		$url=self::$wsdl."/GetLabel";
    		
    		$header=self::getHeader(null,null,$token);
    		
    		$request["messageId"]=self::getMessageid();
    		$request["timestamp"]=time();
    		$request["ebayId"]=empty($print_param['ebayId'])?0:$print_param['ebayId'];
    		
    		$parm=array(
    				"trackingNumber"=>$print_param['tracking_number'], //包裹跟踪号
    				"printPreference"=>$pageshow,  //打印偏好预设，为空时打印所有列，否则按所选择需要打印的列使用逗号拼接字符串.如：（itemId,skuNo,nameZh,nameEn,property,quantity,sellerId,buyerId）
    				"pageSize"=>$pageSize,    //标签格式，可用值： 0 - 适用于打印A4 格式标签,1 - 适用于打印4寸 的热敏标签纸格式标签
    		);
    		
    		$request["data"]=$parm;
    			
    		$response=Helper_Curl::post($url,json_encode($request), $header);
    			
    		$response_arr=json_decode($response,true);
    			
    		if(isset($response_arr["status"]["resultCode"]) && $response_arr["status"]["resultCode"]==200){
    				
    			$pdfurl=base64_decode($response_arr["data"]["base64Str"]);
    		
    			$pdfPath = CarrierAPIHelper::savePDF2($pdfurl, $puid, $SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
    			return $pdfPath;
    		}
    		else{
    			if(isset($response_arr["status"]["message"]))
    				$result["message"]=$response_arr["status"]["message"];
    			else if(isset($response_arr["message"]))
    				$result["message"]=$response_arr["message"];
    			else
    				$result["message"]="网络错误";
    		
    			return ['error'=>1, 'msg'=>$result["message"], 'filePath'=>''];
    		}
    		
    	
   		}catch(\Exception $e){return ['error'=>1, 'msg'=>$e->getMessage(), 'filePath'=>''];}
    }
    
    //获取运输方式
    public function getCarrierShippingServiceStr($account){

    	try{
	    	$token=self::$token;
	    	if(empty($token))
	    		return self::getResult(1,'',"密钥错误");
	    		
	    	$url=self::$wsdl."/GetServiceList";
	    	
	    	$header=self::getHeader(null,null,$token);
	
	    	$request["messageId"]=self::getMessageid();
	    	$request["timestamp"]=time();
			
			// TODO carrier user account @XXX@
	    	$request["ebayId"]="@XXX@";//卖家eBay账户
	    	$request["data"]=array("page_number"=>"","page_size"=>"");
	
	    	$response=Helper_Curl::post($url,json_encode($request), $header);
	    	
	    	$re=json_decode($response,true);

	    	if(empty($re["status"]["resultCode"]) && $re["status"]!=200 ){
	    		return self::getResult(1,'',$re["message"]);
	    	}
	    	else if(!empty($re["status"]["resultCode"]) && $re["status"]["resultCode"]!=200){
	    		return self::getResult(1,'',$re["status"]["message"]);
	    	}
	    	
	    	$data=$re["data"]["serviceList"];
	    	
	    	$str="";
	    	foreach ($data as $keys=>$code){
	    		$str.=$code["serviceId"].":".$code["nameZh"].";";
	    	}
	    	
	    	if(empty($str)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $str, '');
			}
		
    	}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
    	
    }
    
    public static function getMessageid() {
    	$pre='DENG'.base_convert(time(),10,36);
    	return strtoupper($pre.'0'.sprintf('%015s',base_convert(mt_rand(1000000,9999999999),10,36).'0'.base_convert(mt_rand(1000000,9999999999),10,36)));
    }
    
    //获取令牌
    public function getFetchToken(){
		$result=array(
				"code"=>0,
				"data"=>"",
				"message"=>"",
		);
    	
    	$url=self::$wsdl."/FetchToken";
    	
    	$header=self::getHeader(self::$pubinfo['devId'],self::$pubinfo['secret']);

    	$response=Helper_Curl::post($url,null, $header);
    	
   		$response_arr=json_decode($response,true);

   		if(isset($response_arr["status"]["resultCode"]) && $response_arr["status"]["resultCode"]==200){
   			
   			if(empty($response_arr["token"])){
   				$result["message"]="空令牌";
   			}
   			else{
   				$result["code"]=1;
   				$result["data"]=$response_arr["token"];
   			}

   		}
   		else{
   			if(isset($response_arr["status"]["message"]))
   				$result["message"]=$response_arr["status"]["message"];
   			else if(isset($response_arr["message"]))
   				$result["message"]=$response_arr["message"];
   			else 
   				$result["message"]="网络错误";
   		}
   		
   		return $result;
    }
    
    private function toHeaderValue($value){
    	if ($value instanceof \DateTime) { // datetime in ISO8601 format
    		return $value->format(\DateTime::ATOM);
    	} else {
    		return $value;
    	}
    }
    
    //获取地址信息列表()
    public function getAddressPreferenceList($ebayId){
    	try{
    		$token=self::$token;
    		if(empty($token))
    			return self::getResult(1,'',"密钥错误");
    		 
    		$url=self::$wsdl."/GetAddressPreferenceList";
    	
    		$header=self::getHeader(null,null,$token);
    	
    		$request["messageId"]=self::getMessageid();
    		$request["timestamp"]=time();
    		$request["ebayId"]=$ebayId;
    		$request["data"]=array("page_number"=>"","page_size"=>"");

    		$response=Helper_Curl::post($url,json_encode($request), $header);
    	
    		$re=json_decode($response,true);

    		$result=array();
    		if(isset($re["status"]["resultCode"]) && $re["status"]["resultCode"]==200){
    			
    			$data=$re["data"]["addressList"];
    			
    			if(empty($data))
    				return self::getResult(1,'',"没有设置地址信息");
    			
    			foreach ($data as $code){
    				$result[]=array(
    						"addressId"=>$code["addressId"],
    						"name"=>$code["name"],
    				);
    			}
    			
    			return self::getResult(0,$result,"");
    			
    		}
    		else{
    			if(isset($re["status"]["message"]))
    				$message=$re["status"]["message"];
    			else if(isset($re["message"]))
    				$message=$re["message"];
    			else
    				$message="网络错误";
    			
    			return self::getResult(1,"",$message);
    		}
    	
    	}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
    }
    
    //获取交运偏好列表()
    public function getConsignPreferenceList($ebayId){
    	try{
    		$token=self::$token;
    		if(empty($token))
    			return self::getResult(1,'',"密钥错误");
    		 
    		$url=self::$wsdl."/GetConsignPreferenceList";
    		 
    		$header=self::getHeader(null,null,$token);
    		 
    		$request["messageId"]=self::getMessageid();
    		$request["timestamp"]=time();
    		$request["ebayId"]=$ebayId;
    		$request["data"]=array("page_number"=>"","page_size"=>"");
    		 
    		$response=Helper_Curl::post($url,json_encode($request), $header);
    		 
    		$re=json_decode($response,true);

    		$result=array();
    		if(isset($re["status"]["resultCode"]) && $re["status"]["resultCode"]==200){
    			 
    			$data=$re["data"]["consignPreferenceList"];
    			 
    			if(empty($data))
    				return self::getResult(1,'',"没有设置交运偏好信息");
    			 
    			foreach ($data as $code){
    				$result[]=array(
    						"consignId"=>$code["consignPreferenceId"],
    						"name"=>$code["name"],
    				);
    			}
    			 
    			return self::getResult(0,$result,"");
    			 
    		}
    		else{
    			if(isset($re["status"]["message"]))
    				$message=$re["status"]["message"];
    			else if(isset($re["message"]))
    				$message=$re["message"];
    			else
    				$message="网络错误";
    			 
    			return self::getResult(1,"",$message);
    		}
    		 
    	}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
    }
    
    private function getHeader($devId,$secret,$token=""){
    	
    	if(empty($token))
    		$authorization="Basic ".base64_encode($devId.":".$secret);
    	else
    		$authorization="Bearer ".$token;
    	
    	$header[] = 'Accept: application/json';
    	$header[] = 'Content-Type: application/json';
    	$header[] = "Authorization:".self::toHeaderValue($authorization);
    	
    	return $header;
    }

    //测试环境设置地址信息(测试环境不能通过系统设置，只能通过接口调用设置)
	public function setAddressPreferencesandbox($ebayId){
		try{
			$token=self::$token;
			if(empty($token))
				return self::getResult(1,'',"密钥错误");
			 
			$url=self::$wsdl."/AddAddressPreference";
			 
			$header=self::getHeader(null,null,$token);
			 
			$request["messageId"]=self::getMessageid();
			$request["timestamp"]=time();
			$request["ebayId"]=$ebayId;
			$request["data"]=array(
					"type"=>"0",
					"street1"=>"1600 Pennsylvania Street",
					"province"=>"310000",
					"name"=>"test1",
					"mobile"=>"13680456345",
					"countryCode"=>"CN",
					"contact"=>"tlanu",
					"city"=>"310100",
					"district"=>"310115",
					"postcode"=>"12343",
			);
		
			$response=Helper_Curl::post($url,json_encode($request), $header);
			 
			$re=json_decode($response,true);
		
			$result=array();
			if(isset($re["status"]["resultCode"]) && $re["status"]["resultCode"]==200){
				return self::getResult(0,$re,"");
				 
			}
			else{
				if(isset($re["status"]["message"]))
					$message=$re["status"]["message"];
				else if(isset($re["message"]))
					$message=$re["message"];
				else
					$message="网络错误";
				 
				return self::getResult(1,"",$message);
			}
			 
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

    //测试环境设置交运信息(测试环境不能通过系统设置，只能通过接口调用设置)
	public function setConsignPreferencesandbox($ebayId){
		try{
			$token=self::$token;
			if(empty($token))
				return self::getResult(1,'',"密钥错误");
	
			$url=self::$wsdl."/AddConsignPreference";
	
			$header=self::getHeader(null,null,$token);
	
			$request["messageId"]=self::getMessageid();
			$request["timestamp"]=time();
			$request["ebayId"]=$ebayId;   //da244878
			$request["data"]=array(
					"type"=>"0",
					"name"=>"test2",
					"pickupTime"=>1,
					"pickupAddress"=>array(
							"street1"=>"1600 Pennsylvania Street",
							"province"=>"310000",
							"mobile"=>"13680456345",
							"countryCode"=>"CN",
							"contact"=>"tlanu",
							"city"=>"310100",
							"district"=>"310115",
							"postcode"=>"12343",
							"name"=>"test2_stress1",
					),
			);
	
			$response=Helper_Curl::post($url,json_encode($request), $header);
	
			$re=json_decode($response,true);
	
			$result=array();
			if(isset($re["status"]["resultCode"]) && $re["status"]["resultCode"]==200){
				return self::getResult(0,$re,"");
			}
			else{
				if(isset($re["status"]["message"]))
					$message=$re["status"]["message"];
				else if(isset($re["message"]))
					$message=$re["message"];
				else
					$message="网络错误";
					
				return self::getResult(1,"",$message);
			}
	
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

	//删除包裹
	public function DeletePackagesRequest($ebayId,$packageIds=array()){
		$parm=array(
				"packageIds"=>$packageIds, //需要交运的包裹ID列表
		);
		
		//组织提交数据
		$token=self::$token;
		if(empty($token))
			return self::getResult(1,'',"密钥错误");
		
		$url=self::$wsdl."/DeletePackages";
		 
		$header=self::getHeader(null,null,$token);
		 
		$request["messageId"]=self::getMessageid();
		$request["timestamp"]=time();
		$request["ebayId"]=$ebayId;
		$request["data"]=$parm;
		 
		$response=Helper_Curl::post($url,json_encode($request), $header);
		 
		$response_arr=json_decode($response,true);
		 
		if(isset($response_arr["status"]["resultCode"]) && $response_arr["status"]["resultCode"]==200){
			return $response_arr;
			 
		}
		else{
			if(isset($response_arr["status"]["message"]))
				$result["message"]=$response_arr["status"]["message"];
			else if(isset($response_arr["message"]))
				$result["message"]=$response_arr["message"];
			else
				$result["message"]="网络错误";
			 
			return $result["message"];
		}
	}
	
	//查询包裹ID
	public function getItemPackageId($ebayId){
		try{
			$token=self::$token;
			if(empty($token))
				return self::getResult(1,'',"密钥错误");
			 
			$url=self::$wsdl."/GetItemPackageId";
			 
			$header=self::getHeader(null,null,$token);
			 
			$request["messageId"]=self::getMessageid();
			$request["timestamp"]=time();
			$request["ebayId"]=$ebayId;
			$request["data"]=array("transactionId"=>"73305481725125","itemId"=>"32276603395");
			 
			$response=Helper_Curl::post($url,json_encode($request), $header);
			 
			$re=json_decode($response,true);
		
			$result=array();
			if(isset($re["status"]["resultCode"]) && $re["status"]["resultCode"]==200){
				print_r($re);die;
		
			}
			else{
				if(isset($re["status"]["message"]))
					$message=$re["status"]["message"];
				else if(isset($re["message"]))
					$message=$re["message"];
				else
					$message="网络错误";
		
				return self::getResult(1,"",$message);
			}
			 
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}
	
    /*
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
    public function getVerifyCarrierAccountInformation($data){
    	$result = array('is_support'=>0,'error'=>1);
    
    	// 		try{
    	// 			$request = array(
    	// 					'appid'=>$data['AppId'],
    	// 					'token'=>$data['Token'],
    	// 			);
    
    	// 			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'CheckAccount');
    
    	// 			if($response['error'] == 0){
    	// 				$response = $response['data']->CheckAccountResult;
    	// 				if($response)
    		// 					$result['error'] = 0;
    		// 			}
    		// 		}catch(CarrierException $e){
    		// 		}
    
    	return $result;
    
    }
    
    
}