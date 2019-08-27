<?php
namespace common\api\carrierAPI;

use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use Qiniu\json_decode;



class YiYuTongBaseConfig
{
	public $token="";
	public $clientid="";
	public $data_info=null;
	public $wsdlg=null;
	public $wsdl=null;
	public $class_name=null;
	
	public $return_info = [
	'error' => 0,
	'data' => '',
	'msg' => ''
			];
	
	public function __construct($data,$url,$classn=''){
		$this->data_info=$data;
		$this->wsdlg=$url;
		$this->wsdl=$url.'/services/WebCOrderServlet?Wsdl';
		$this->client = new \SoapClient($this->wsdl,array ('encoding' => 'UTF-8' ));
		$this->class_name = $classn;
	}
	
	public function returnResult($return,$error=0,$data='',$msg=''){
		$return['error']=$error;
		$return['data']=$data;
		$return['msg']=$msg;
		return $return;
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/02/17				初始化
	 +----------------------------------------------------------
	 **/
	public function _getOrderNO(){
		$data=$this->data_info;
		$return_infos = $this->return_info;
		try{
			//验证用户登录
			$user = \Yii::$app->user->identity;
			if(empty($user))
				return ['error'=>1, 'data'=>'', 'msg'=>'用户登录信息缺失，请重新登录'];
			
			$puid = $user->getParentUid();
			
			$order = $data['order'];  
			
			//重复发货 添加不同的标识码
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
			
			if(isset($data['data']['extra_id'])){
				if($extra_id == ''){
					return $this->returnResult($return_infos,1,'','强制发货标识码，不能为空');
				}
			}
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
				
			$e = $data['data'];
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];

			//认证参数
			$params=$account->api_params;
			$this->clientid=$params['clientid'];
			$this->token=$params['token'];
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 1000,
							'consignee_address_line2_limit' => 1000,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			if (empty($order->consignee)){
				return $this->returnResult($return_infos,1,'','收件人名称不能为空');
			}
			if (empty($addressAndPhone['address_line1'])){
				return $this->returnResult($return_infos,1,'','地址不能为空');
			}
			if(strlen($addressAndPhone['address_line1'])>200 || strlen($addressAndPhone['address_line2'])>200)
				return $this->returnResult($return_infos,1,'','地址不能长于200个字符');
			if (empty($addressAndPhone['phone1'])){
				return $this->returnResult($return_infos,1,'','收件人电话不能为空');
			}
			
			//没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
				
			$total_weight=0;
			$DeclareInvoice="<DeclareInvoiceArray>";
			foreach ($order->items as $k=>$v){
				if (empty($e['Name'][$k])){
					return $this->returnResult($return_infos,1,'','申报品名不能为空');
				}
				if (empty($e['DeclarePieces'][$k])){
					return $this->returnResult($return_infos,1,'','申报数量不能为空');
				}
				if (empty($e['DeclaredValue'][$k])){
					return $this->returnResult($return_infos,1,'','申报价值不能为空');
				}

				$DeclareInvoice.="<DeclareInvoice>".
						"<itemcont>".$e['Name'][$k]."</itemcont>". //申报品名
						"<itemcustoms>".$e['EName'][$k]."</itemcustoms>".	 //英文申报品名
						"<itempeihuo>".$e['itempeihuo'][$k]."</itempeihuo>".   //配货信息
						"<itemvalue>".floatval($e['DeclaredValue'][$k])*floatval($e['DeclarePieces'][$k])."</itemvalue>".          //申报价值
						"<itemnum>".$e['DeclarePieces'][$k]."</itemnum>".         //申报数量
						"<itemprodno>".(empty($e['Hscode'][$k]) ? '' : $e['Hscode'][$k])."</itemprodno>".     //海关货物编号
						"<itemphnote></itemphnote>".  //配货备注
						"<itemunit></itemunit>".      //单位
						"<itemsbprice>".$e['DeclaredValue'][$k]."</itemsbprice>".     //申报单价
						"<itemweight1>".($e['weight'][$k]/1000*$e['DeclarePieces'][$k])."</itemweight1>".    //产品重量
						"<itemimgurl></itemimgurl>".   //产品链接图片
						"</DeclareInvoice>";
				
				$total_weight=$total_weight+(floatval($e['weight'][$k]/1000*$e['DeclarePieces'][$k]));
			}
			$DeclareInvoice.="</DeclareInvoiceArray>";
			
			$country_code=$order->consignee_country_code;
			if($country_code=="UK")
				$country_code="GB";

			if($service->shipping_method_code=="SGEMS"){  //新加波电话格式有限制
				$rectel=empty($order->consignee_phone)?$order->consignee_mobile:$order->consignee_phone;
			}
			else{
				$rectel=$addressAndPhone['phone1'];
			}

			$request="<CreateAndPreAlertOrderService>".
					"<authtoken>".$this->token."</authtoken>".
					"<clientid>".$this->clientid."</clientid>".
					"<CreateAndPreAlertOrderRequestArray>".
					"<CreateAndPreAlertOrderRequest>".
					"<billid></billid>".
					"<refernumb>".$customer_number."</refernumb>".        //客户订单号
					"<channelid>".$service->shipping_method_code."</channelid>".        //运输方式
					"<recname>".$order->consignee."</recname>".          //收件人名称
					"<recaddr></recaddr>".      //收件人地址
					"<recaddr1>".$addressAndPhone['address_line1']."</recaddr1>".         //收件人地址 1
					"<recaddr2>".$addressAndPhone['address_line2']."</recaddr2>".          //收件人地址 2
					"<rectel>".$rectel."</rectel>".         //收件人电话
					"<recmobile>".$order->consignee_mobile."</recmobile>".         //收件人手机
					"<reccorp>".$order->consignee_company."</reccorp>".        //收件人公司名称
					"<recprovince>".$tmpConsigneeProvince."</recprovince>".     //收件人省(州)
					"<reccity>".$order->consignee_city."</reccity>".          //收件人城市
					"<recpost>".$order->consignee_postal_code."</recpost>".         //收件人邮编
					"<recemail>".$order->consignee_email."</recemail>".          //收件人邮箱
					"<country>".$country_code."</country>".        //收件人国家
					"<weight>".$total_weight."</weight>".        //总重量
					$DeclareInvoice.
					"</CreateAndPreAlertOrderRequest>".
					"</CreateAndPreAlertOrderRequestArray>".
					"</CreateAndPreAlertOrderService>";
// 			print_r($request);die;
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info($this->class_name.',puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			$response=$this->client->addYBCorder($request);
			
			if($this->class_name == 'LB_ZHITENGCarrierAPI'){
				\Yii::info($this->class_name.',puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response,"carrier_api");
				
				$response = self::xml2array($response, 1, '');
				
// 				print_r($response);
// 				exit;
				
				$result = array("Ack"=>"", "OrderItem"=>["billid"=>'', "corpbillid"=>'', "refernumb"=>''], 'Error'=>'');

				if(isset($response['CreateOrderService']['Ack'])){
					if($response['CreateOrderService']['Ack']['value'] == '执行成功'){
						$result['Ack'] = '执行成功';
						$result['OrderItem']['billid'] = isset($response['CreateOrderService']['billid']['value']) ? $response['CreateOrderService']['billid']['value'] : '';
						$result['OrderItem']['corpbillid'] = isset($response['CreateOrderService']['corpbillid']['value']) ? $response['CreateOrderService']['corpbillid']['value'] : '';
						$result['OrderItem']['refernumb'] = isset($response['CreateOrderService']['refernumb']['value']) ? $response['CreateOrderService']['refernumb']['value'] : '';
					}
				}else if(isset($response['CreateOrderService']['CreateOrderServiceResponseArray']['CreateOrderServiceResponse'])){
					$response = $response['CreateOrderService']['CreateOrderServiceResponseArray']['CreateOrderServiceResponse'];
					
					if($response['Ack']['value'] == '执行成功'){
						$result['Ack'] = '执行成功';
						$result['OrderItem']['billid'] = isset($response['billid']['value']) ? $response['billid']['value'] : '';
						$result['OrderItem']['corpbillid'] = isset($response['corpbillid']['value']) ? $response['corpbillid']['value'] : '';
						$result['OrderItem']['refernumb'] = isset($response['refernumb']['value']) ? $response['refernumb']['value'] : '';
					}else{
						$result['Ack'] = $response['Ack'];
						$result['Error'] = $response['Ack']['value'];
					}
				}
				
				if($result['Ack'] == '执行成功'){
					$t_number="";
					if(!empty($result['OrderItem']['billid'])){
						$t_number=$result['OrderItem']['billid'];
					}
					$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$t_number,['OrderSign'=>$result['OrderItem']['corpbillid']]);
				
					$print_param = array();
					$print_param['delivery_orderId'] = $result['OrderItem']['corpbillid'];
					$print_param['carrier_code'] = $service->carrier_code;
					$print_param['api_class'] = $this->class_name;
					$print_param['userToken'] = json_encode(['clientid' => $params['clientid'], 'token' => $params['token']]);
					$print_param['tracking_number'] = $t_number;
					$print_param['carrier_params'] = $service->carrier_params;
					$print_param['shipping_method_code'] = $service->shipping_method_code;
				
					try{
						CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order-> order_id, $result['OrderItem']['refernumb'], $print_param);
					}catch(\Exception $ex){}
				
					return $this->returnResult($return_infos,0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$t_number);
				}else{
					return $this->returnResult($return_infos,1,'', $result['Error']);
				}
			}else{
				\Yii::info($this->class_name.',puid:'.$puid.',response,order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
				
				try{
					$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
				
					if(empty($channelArr) || !is_array($channelArr) || json_last_error()!=false){
						return $this->returnResult($return_infos,1,'','获取订单链接失败');
					}
					$result=$channelArr['CreateOrderServiceResponseArray']['CreateOrderServiceResponse'];
				}
				catch(\Exception $err){
					$length=strpos($response,"</Ack>")-strpos($response,"<Ack>")-5;
					$Ack=substr($response,strpos($response,"<Ack>")+5,$length);
					if(strstr($Ack,"执行成功")!=false){
						//手动组装数据出来
						$length=strpos($response,"</billid>")-strpos($response,"<billid>")-8;
						$billid=substr($response,strpos($response,"<billid>")+8,$length);
							
						$length=strpos($response,"</corpbillid>")-strpos($response,"<corpbillid>")-12;
						$corpbillid=substr($response,strpos($response,"<corpbillid>")+12,$length);
				
						$length=strpos($response,"</refernumb>")-strpos($response,"<refernumb>")-11;
						$refernumb=substr($response,strpos($response,"<refernumb>")+11,$length);
				
						$result=[
						"Ack"=>"执行成功！",
						"OrderItem"=>[
						"billid"=>$billid,
						"corpbillid"=>$corpbillid,
						"refernumb"=>$refernumb,
						],
						];
					}else{
						$length=strpos($response,"</Error>")-strpos($response,"<Error>")-7;
						$Error=substr($response,strpos($response,"<Error>")+7,$length);
						return $this->returnResult($return_infos,1,'',$Error);
					}
				}
				
				if(strstr($result['Ack'],"执行成功")!=false){
					$t_number="";
					if(!empty($result['OrderItem']['billid'])){
						$t_number=$result['OrderItem']['billid'];
					}
					$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$t_number,['OrderSign'=>$result['OrderItem']['corpbillid']]);
				
					$print_param = array();
					$print_param['delivery_orderId'] = $result['OrderItem']['corpbillid'];
					$print_param['carrier_code'] = $service->carrier_code;
					$print_param['api_class'] = $this->class_name;
					$print_param['userToken'] = json_encode(['clientid' => $params['clientid'], 'token' => $params['token']]);
					$print_param['tracking_number'] = $t_number;
					$print_param['carrier_params'] = $service->carrier_params;
					$print_param['shipping_method_code'] = $service->shipping_method_code;
				
					try{
						CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order-> order_id, $result['OrderItem']['refernumb'], $print_param);
					}catch(\Exception $ex){}
				
					return $this->returnResult($return_infos,0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$t_number);
				}else{
					return $this->returnResult($return_infos,1,'',$result['Error']);
				}
			}
		}catch(CarrierException $e){
			return $this->returnResult($return_infos,1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 **/
	public function _cancelOrderNO(){
		$return_infos = $this->return_info;
		return $this->returnResult($return_infos,1,'','物流接口不支持取消已确定的物流单。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 **/
	public function _doDispatch(){
		$return_infos = $this->return_info;
		return $this->returnResult($return_infos,1,'','物流接口不支持交运。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/29				初始化
	 +----------------------------------------------------------
	 **/
	public function _getTrackingNO(){
		$data=$this->data_info;
		$return_infos = $this->return_info;
		$flag=0;  //0获取跟踪号，1重新申请跟踪号
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return $this->returnResult($return_infos,1,'','用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = $data['order'];
	
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];// object SysCarrierAccount
			$service = $info['service'];// object SysShippingService
			$a = $account->api_params;
			//认证参数
			$this->clientid=$a['clientid'];
			$this->token=$a['token'];
			
			$tmpBarcodeStr="";
					
			$checkResult = CarrierAPIHelper::validate(1,1,$order);
			$shipped = $checkResult['data']['shipped'];
			
			if(empty($shipped['return_no']['OrderSign'])){
				return $this->returnResult($return_infos,1,'','order:'.$order->order_id .' 缺少公司单号,请检查订单是否已上传');
			}
					
			if($flag==1)
				$tmpBarcodeStr.="<ChannelAndCorpBillidArray><corpbillid>".$shipped['return_no']['OrderSign']."</corpbillid><channelid>".$service->shipping_method_code."</channelid></ChannelAndCorpBillidArray>";
			else
				$tmpBarcodeStr.="<GetBillidArray><corpbillid>".$shipped['return_no']['OrderSign']."</corpbillid></GetBillidArray>";
				
			if($flag==1){
				$request="<GetBillidServiceRequest>".
						"<authtoken>".$this->token."</authtoken>".
						"<clientid>".$this->clientid."</clientid>".
						$tmpBarcodeStr.
						"</GetBillidServiceRequest>";
				$response=$this->client->getReOrderBillid($request);
			}
			else{
				$request="<GetBillidServiceRequest>".
						"<authtoken>".$this->token."</authtoken>".
						"<clientid>".$this->clientid."</clientid>".
						$tmpBarcodeStr.
						"</GetBillidServiceRequest>";
				$response=$this->client->getBillidByCorpBillid($request);
			}
			
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);

			if($channelArr['Ack']=="true"){
				if(!empty($channelArr['ReBillidArr']['billid'])){
					$shipped->tracking_number = $channelArr['ReBillidArr']['billid'];
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
										
					return $this->returnResult($return_infos,0,'','获取物流号成功！物流号：'.$channelArr['ReBillidArr']['billid']);
				}
				else 
					return $this->returnResult($return_infos,1,'','还没有返回物流号');
			}
			else{
				return $this->returnResult($return_infos,1,'',$channelArr['Errror']);
			}
		}catch(CarrierException $e){return $this->returnResult($return_infos,1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/02/17				初始化
	 +----------------------------------------------------------
	 **/
	public function _doPrint(){
		
		$data=$this->data_info;
		$return_infos = $this->return_info;
		try{
			$order = current($data);reset($data);
			$order = $order['order'];
			$user=\Yii::$app->user->identity;
			if(empty($user))return $this->returnResult($return_infos,1,'','用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
				
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			$this->token=$account_api_params['token'];
			$this->clientid=$account_api_params['clientid'];			
			
			//获取打印格式,某些物流会固定打印设置参数的值
			$format="100-100";
			$contentAddress="0";
			$contentDeclare="0";
			$contentItem="0";
			$ifprinttime="0";
			if(!empty($carrier_params['format'])){
				$format=$carrier_params['format'];
			}
			if(!empty($carrier_params['contentAddress'])){
				$contentAddress=$carrier_params['contentAddress'];
			}
			if(!empty($carrier_params['contentDeclare'])){
				$contentDeclare=$carrier_params['contentDeclare'];
			}
			if(!empty($carrier_params['contentItem'])){
				$contentItem=$carrier_params['contentItem'];
			}
			if(!empty($carrier_params['ifprinttime'])){
				$ifprinttime=$carrier_params['ifprinttime'];
			}			
			$content="";
			if($contentAddress!="0")
				$content.=$contentAddress.",";
			if($contentDeclare!="0")
				$content.=$contentDeclare.",";
			if($contentItem!="0")
				$content.=$contentItem.",";
					
			if(!empty($content))
				$content=substr($content,0,-1);
			
			$tmpBarcodeStr="";
			foreach ($data as $k=>$v) {
				$order = $v['order'];
			
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
	
				if(empty($shipped['return_no']['OrderSign'])){
					return $this->returnResult($return_infos,1,'','order:'.$order->order_id .' 缺少公司单号,请检查订单是否已上传');
				}
			
				$tmpBarcodeStr.=$shipped['return_no']['OrderSign'].",";
			}

			$tesuchuli=0;   //是否特殊处理
			if($service['carrier_code']=="lb_taijia"){
				//泰嘉的打印接口，相同的打印方式可能对接了不同系统的打印格式，所以需要获取打印格式判断一下当前选择的是否可以打印
				$newparm=array(
						"token"=>$this->token,
						"clientid"=>$this->clientid,
						"channelCode"=>$service['shipping_method_code'],
				);
				
				$new_format_arr=self::getPageLabelByChannelCode($newparm);

				if($new_format_arr['code'])
					return $this->returnResult($return_infos,1,'',$new_format_arr['message']);
				if(!array_key_exists($format,$new_format_arr['data'])){
					foreach ($new_format_arr['data'] as $keys=>$code){
						if(strstr(strtoupper($code),"A4")!=false && $format=="00"){
							$tesuchuli=1;
							$format=$keys;
							break;
						}
						else if(strstr(strtoupper($code),"热敏")!=false && $format=="01"){
							$tesuchuli=1;
							$format=$keys;
							break;
						}
					}
				}
			}

			$request="<GeOrdertPrintServiceRequest>".
					"<authtoken>".$this->token."</authtoken>".
					"<clientid>".$this->clientid."</clientid>".
					"<corpbillid>".$tmpBarcodeStr."</corpbillid>".
					"<url>".$this->wsdlg."/</url>".
					"<paper>".$format."</paper>".
					"<content>".$content."</content>".
					"<ifprinttime>".$ifprinttime."</ifprinttime>".
					"</GeOrdertPrintServiceRequest>";
			
			

			$response=$this->client->getOrderPrintLabel($request);

			try{
				if($tesuchuli==1){
					$length=strpos($response,"</printurl>")-strpos($response,"<printurl>")-10;
					$channelArr['printurl']=substr($response,strpos($response,"<printurl>")+10,$length);
					$channelArr['Ack']=true;
				}
				else
					$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
			}catch (\Exception $ex){
				return $this->returnResult($return_infos,1,'','获取打印链接失败e1，请尝试选择其它打印格式。');
			}

			if(empty($channelArr) || !is_array($channelArr) || json_last_error()!=false){
				return $this->returnResult($return_infos,1,'','获取打印链接失败e2，请尝试选择其它打印格式。');
			}
			
			if($channelArr['Ack']=='true'){
				if($tesuchuli==0){
// 					if(!preg_match('/\.pdf$/', $channelArr['printurl'])){
					if(empty($channelArr['printurl'])){
						return $this->returnResult($return_infos,1,'','连接生成失败,原因:没有获取到正确的PDF连接');
					}
				}

				if( strpos(strtolower($service->shipping_method_name), 'e邮宝') !== false && (in_array($order->default_carrier_code, array('lb_taijia'))))
				{
					return $this->returnResult($return_infos,0,['pdfUrl'=>$channelArr['printurl'],'type'=>'1'],'');
				}
				else{
					return $this->returnResult($return_infos,0,['pdfUrl'=>$channelArr['printurl']],'');
				}
				
			}
			else{
				return $this->returnResult($return_infos,1,'',$channelArr['Errror']."，请尝试选择其它打印格式。");
			}
		}catch(\Exception $e){return $this->returnResult($return_infos,1,'',"请尝试选择其它打印格式   message:".$e->getMessage());}
	}
	
	//获取运输方式
	public function _getCarrierShippingServiceStr($empty_token='',$empty_clienti=''){
		$account=$this->data_info;      //$account
		$return_infos = $this->return_info;
		try{
			$request="<CreateAndPreAlertOrderService>".
					"<authtoken>".(empty($account->api_params['token'])?$empty_token:$account->api_params['token'])."</authtoken>".
					"<clientid>".(empty($account->api_params['clientid'])?$empty_clienti:$account->api_params['clientid'])."</clientid>".
					"</CreateAndPreAlertOrderService>";
			
			$response=$this->client->getChannel($request);
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);

			if(empty($channelArr) || !is_array($channelArr) || json_last_error()!=false){
				return $this->returnResult($return_infos,1,'','获取运输方式失败');
			}
			if(!isset($channelArr['channelid'])){
				if(isset($channelArr['CreateOrderServiceResponseArray']['CreateOrderServiceResponse']['Ack']))
					return $this->returnResult($return_infos,1,'',$channelArr['CreateOrderServiceResponseArray']['CreateOrderServiceResponse']['Ack']);
				else
					return $this->returnResult($return_infos,1,'','获取运输方式失败');
			}

			$channelStr="";
			foreach ($channelArr['channelid'] as $j=>$countryVal){
				$channelStr.=$channelArr['channelid'][$j].":".$channelArr['channelname'][$j].";";
			}

			if(empty($channelStr)){
				return $this->returnResult($return_infos,1,'','');
			}else{
				return $this->returnResult($return_infos,0,$channelStr,'');
			}
			
		}catch(CarrierException $e){return $this->returnResult($return_infos,1,'',$e->msg());}
	}
	
	//获取渠道支持的打印格式
	public function getPageLabelByChannelCode($parm){
		$data=array(
				"code"=>0,
				"message"=>"",
				"data"=>"",
		);
		
		$arr=array(
				"token"=>empty($parm)?'u2551c13o76OMwwfSQEV':$parm['token'],
				"clientid"=>empty($parm)?'KH024008':$parm['clientid'],
				"channelCode"=>empty($parm)?'SZEUB':$parm['channelCode'],   //渠道
		);
		$response=$this->client->getPageLabelByChannelCode(json_encode($arr));
		$channelArr=json_decode($response,true);

		if(empty($channelArr)){
			$data['code']=1;
			$data['message']="打印失败，没有匹配的打印格式";
		}
		else if(empty($channelArr['StatusCode'])){
			$data['code']=1;
			$data['message']=$channelArr['Message'];
		}
		else{
			foreach ($channelArr['pagerList'] as $code){
				$datacode[$code['pagerCode']]=$code['pagerName'];
			}
			$data['data']=$datacode;
		}
		
		return $data;
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
	public function _getVerifyCarrierAccountInformation($data){
		$result = array('is_support'=>1,'error'=>1);
		$return_infos = $this->return_info;
	
		try{
			$request="<CreateAndPreAlertOrderService>".
					"<authtoken>".$data['token']."</authtoken>".
					"<clientid>".$data['clientid']."</clientid>".
					"</CreateAndPreAlertOrderService>";
	
			$response=$this->client->getChannel($request);
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);

			if(isset($channelArr['channelid']))
				$result['error'] = 0;
			
		}catch(CarrierException $e){
		}

		return $this->returnResult($return_infos,0,$result,'');
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
	public function _getCarrierLabelApiPdf($SAA_obj, $print_param){
// 		print_r($print_param);

		$tmp_authParams = json_decode($print_param['userToken'], true);
		$tmpBarcodeStr = $print_param['delivery_orderId'];
		
		//获取打印格式,某些物流会固定打印设置参数的值
		$format="100-100";
		$contentAddress="0";
		$contentDeclare="0";
		$contentItem="0";
		$ifprinttime="0";
		if(!empty($print_param['carrier_params']['format'])){
			$format=$print_param['carrier_params']['format'];
		}
		if(!empty($print_param['carrier_params']['contentAddress'])){
			$contentAddress=$print_param['carrier_params']['contentAddress'];
		}
		if(!empty($print_param['carrier_params']['contentDeclare'])){
			$contentDeclare=$print_param['carrier_params']['contentDeclare'];
		}
		if(!empty($print_param['carrier_params']['contentItem'])){
			$contentItem=$print_param['carrier_params']['contentItem'];
		}
		if(!empty($print_param['carrier_params']['ifprinttime'])){
			$ifprinttime=$print_param['carrier_params']['ifprinttime'];
		}
		$content="";
		if($contentAddress!="0")
			$content.=$contentAddress.",";
		if($contentDeclare!="0")
			$content.=$contentDeclare.",";
		if($contentItem!="0")
			$content.=$contentItem.",";
		if(!empty($content))
			$content=substr($content,0,-1);

		
		$tesuchuli=0;   //是否特殊处理
		if($print_param['carrier_code']=="lb_taijia"){
			//泰嘉的打印接口，相同的打印方式可能对接了不同系统的打印格式，所以需要获取打印格式判断一下当前选择的是否可以打印
			$userToken=json_decode($print_param['userToken'],true);

			$newparm=array(
					"token"=>$tmp_authParams['token'],
					"clientid"=>$tmp_authParams['clientid'],
					"channelCode"=>$print_param['shipping_method_code'],
			);
			$new_format_arr=self::getPageLabelByChannelCode($newparm);
			if($new_format_arr['code'])
				return ['error'=>1, 'msg'=>$new_format_arr['message'], 'filePath'=>''];
			if(!array_key_exists($format,$new_format_arr['data'])){
				foreach ($new_format_arr['data'] as $keys=>$code){
					if(strstr(strtoupper($code),"A4")!=false && $format=="00"){
						$tesuchuli=1;
						$format=$keys;
						break;
					}
					else if(strstr(strtoupper($code),"热敏")!=false && $format=="01"){
						$tesuchuli=1;
						$format=$keys;
						break;
					}
				}
			}
		}
		
		
		$request="<GeOrdertPrintServiceRequest>".
				"<authtoken>".$tmp_authParams['token']."</authtoken>".
				"<clientid>".$tmp_authParams['clientid']."</clientid>".
				"<corpbillid>".$tmpBarcodeStr."</corpbillid>".
				"<url>".$this->wsdlg."/</url>".
				"<paper>".$format."</paper>".
				"<content>".$content."</content>".
				"<ifprinttime>".$ifprinttime."</ifprinttime>".
				"</GeOrdertPrintServiceRequest>";
		
		$response=$this->client->getOrderPrintLabel($request);		
		
		try{
			if($tesuchuli==1){
				$length=strpos($response,"</printurl>")-strpos($response,"<printurl>")-10;
				$channelArr['printurl']=substr($response,strpos($response,"<printurl>")+10,$length);
				$channelArr['Ack']=true;
				
			}
			else
				$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
		}catch (\Exception $ex){
			return ['error'=>1, 'msg'=>'获取打印链接失败e1，请尝试选择其它打印格式。', 'filePath'=>''];
		}
		
		
		if(empty($channelArr) || !is_array($channelArr) || json_last_error()!=false){
			return ['error'=>1, 'msg'=>'获取打印链接失败', 'filePath'=>''];
		}
		
		if($channelArr['Ack']=='true'){
			if($tesuchuli==0){
				if(empty($channelArr['printurl'])){
					return ['error'=>1, 'filePath'=>'', 'msg'=>'连接生成失败！错误信息：接口返回内容不是一个有效的PDF'];
				}
			}
					
			$responsePdf = Helper_Curl::get($channelArr['printurl']);
			if(strlen($responsePdf) < 1000)
				return ['error'=>1, 'filePath'=>'', 'msg'=>'打印失败！错误信息：接口返回内容不是一个有效的PDF'];
			 
			$pdfPath = CarrierAPIHelper::savePDF2($responsePdf, $SAA_obj->uid, $SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
			return $pdfPath;
		}else{
			return ['error'=>1, 'msg'=>$channelArr['Errror']."，请尝试选择其它打印格式。", 'filePath'=>''];
		}
	}
	
	//参考 https://blog.csdn.net/jianai0602/article/details/77802107
	public static function xml2array($contents, $get_attributes=1, $priority = 'tag')
	{
		if(!$contents) return array();
	
		if(!function_exists('xml_parser_create')) {
			//print "'xml_parser_create()' function not found!";
			return array();
		}
	
		//Get the XML parser of PHP - PHP must have this module for the parser to work
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($contents), $xml_values);
		xml_parser_free($parser);
	
		if(!$xml_values) return;//Hmm...
	
		//Initializations
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();
	
		$current = &$xml_array; //Refference
	
		//Go through the tags.
		$repeated_tag_index = array();//Multiple tags with same name will be turned into an array
		foreach($xml_values as $data) {
			unset($attributes,$value);//Remove existing values, or there will be trouble
	
			//This command will extract these variables into the foreach scope
			// tag(string), type(string), level(int), attributes(array).
			extract($data);//We could use the array by itself, but this cooler.
	
			$result = array();
			$attributes_data = array();
	
			if(isset($value)) {
				if($priority == 'tag') $result = $value;
				else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
			}
	
			//Set the attributes too.
			if(isset($attributes) and $get_attributes) {
				foreach($attributes as $attr => $val) {
					if($priority == 'tag') $attributes_data[$attr] = $val;
					else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
				}
			}
	
			//See tag status and do the needed.
			if($type == "open") {//The starting of the tag '<tag>'
				$parent[$level-1] = &$current;
				if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
					$current[$tag] = $result;
					if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
					$repeated_tag_index[$tag.'_'.$level] = 1;
	
					$current = &$current[$tag];
	
				} else { //There was another element with the same tag name
	
					if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
						$repeated_tag_index[$tag.'_'.$level]++;
					} else {//This section will make the value an array if multiple tags with the same name appear together
						$current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
						$repeated_tag_index[$tag.'_'.$level] = 2;
	
						if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
							$current[$tag]['0_attr'] = $current[$tag.'_attr'];
							unset($current[$tag.'_attr']);
						}
	
					}
					$last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
					$current = &$current[$tag][$last_item_index];
				}
	
			} elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
				//See if the key is already taken.
				if(!isset($current[$tag])) { //New Key
					$current[$tag] = $result;
					$repeated_tag_index[$tag.'_'.$level] = 1;
					if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;
	
				} else { //If taken, put all things inside a list(array)
					if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
	
						// ...push the new element into that array.
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
	
						if($priority == 'tag' and $get_attributes and $attributes_data) {
							$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag.'_'.$level]++;
	
					} else { //If it is not an array...
						$current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
						$repeated_tag_index[$tag.'_'.$level] = 1;
						if($priority == 'tag' and $get_attributes) {
							if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
	
								$current[$tag]['0_attr'] = $current[$tag.'_attr'];
								unset($current[$tag.'_attr']);
							}
	
							if($attributes_data) {
								$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
					}
				}
	
			} elseif($type == 'close') { //End of tag '</tag>'
				$current = &$parent[$level-1];
			}
		}
	
		return($xml_array);
	}
}?>
