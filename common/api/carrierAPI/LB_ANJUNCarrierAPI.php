<?php

namespace common\api\carrierAPI;
use yii;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\dhgate\apihelpers\SaasDhgateAutoSyncApiHelper;
use Qiniu\json_decode;
// try{
// 	include dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'PDFMerger'.DIRECTORY_SEPARATOR.'PDFMerger.php';
// }catch(\Exception $e){	
// }

class LB_ANJUNCarrierAPI extends BaseCarrierAPI
{
	static private $url_aj = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	static $connecttimeout=60;
	static $timeout=500;
	static $last_post_info=null;
	static $last_error =null;
	
	public $username = null;
	public $password = null;
	
	public function __construct(){
		//安骏没有测试环境
		self::$url_aj = 'http://aj.hushengkj.com/';
		
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/12				初始化
	 +----------------------------------------------------------
	 **/
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
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
				
			//用户在确认页面提交的数据
			$e = $data['data'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$service_carrier_params = $service->carrier_params;
			$account = $info['account'];
			
			//判断是否手动报关
			$is_CustomsFormSpan = false;
			if(!empty($e['is_CustomsFormSpan']) && $e['is_CustomsFormSpan'] == 'on'){
				$is_CustomsFormSpan = true;
			}
			
			if(!isset($e['pickingInfo'])){
				$tmpAnjunpickingInfo_mode = '';
				if(!empty($service_carrier_params['pickingInfo_mode_service'])){
					if($service_carrier_params['pickingInfo_mode_service'] != 'ALL')
						$tmpAnjunpickingInfo_mode = $service_carrier_params['pickingInfo_mode_service'];
				}
					
				if(empty($tmpAnjunpickingInfo_mode)){
					if(!empty($account['api_params']['pickingInfo_mode'])){
						$tmpAnjunpickingInfo_mode = $account['api_params']['pickingInfo_mode'];
					}
				}
				
				$e['pickingInfo'] = '';
				
				$declarationInfo = json_decode($e['tmpHiddenData'],true);
				
				if(json_last_error() == JSON_ERROR_NONE){
				}else{
					$declarationInfo = json_decode(base64_decode($e['tmpHiddenData']),true);
				}
				
				if(!empty($declarationInfo['products'])){
					if(!empty($tmpAnjunpickingInfo_mode)){
						if($tmpAnjunpickingInfo_mode == 'sku'){
							foreach($declarationInfo['products'] as $product){
								$e['pickingInfo'] .= $product['sku'].'*'.$product['quantity'].';';
							}
							$e['pickingInfo']=substr($e['pickingInfo'],0,-1);
						}else if($tmpAnjunpickingInfo_mode == 'orderid'){
							$e['pickingInfo'] = $order->order_id;
						}else if($tmpAnjunpickingInfo_mode == 'sku_prod_name'){
							foreach($declarationInfo['products'] as $product){
								$e['pickingInfo'] .= $product['sku'].' '.$product['prod_name_ch'].'*'.$product['quantity'].';';
							}
							$e['pickingInfo']=substr($e['pickingInfo'],0,-1);
						}else if($tmpAnjunpickingInfo_mode == 'chName_shu'){
							foreach($declarationInfo['products'] as $j => $product){
								if($is_CustomsFormSpan){
									$e['pickingInfo'] .= $e['chName'][$j].'*'.(empty($e['qty'][$j]) ? 0 : $e['qty'][$j]).';';
									break;
								}
								else{
									$e['pickingInfo'] .= $e['chName'][$j].'*'.$product['quantity'].';';
								}
							}
							$e['pickingInfo']=substr($e['pickingInfo'],0,-1);
						}
					}
				}
			}
				
			//获取到帐号中的认证参数
			$a = $account->api_params;
				
			$this->username = $a['username'];
			$this->password = $a['password'];
			
			$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
			(empty($order->consignee_district) ? '' : ','.$order->consignee_district);
			
			$address=$order->consignee_address_line1.$order->consignee_address_line2.$order->consignee_address_line3;
			if (empty($address)){
				return self::getResult(1, '', '地址不能为空');
			}
			$address=$address.$addressInfo;
// 			if (empty($order->consignee_province)){
// 				return self::getResult(1, '', '省份不能为空');
// 			}

			//当省份为空，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($tmpConsigneeProvince)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			if (empty($order->consignee_city)){
				return self::getResult(1, '', '城市不能为空');
			}
			
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
			
// 			if (empty($order->consignee_postal_code)){
// 				return self::getResult(1, '', '邮编不能为空');
// 			}
			
			$phoneContact = (empty($order->consignee_phone) ? $order->consignee_mobile : $order->consignee_phone);
			
 			/*if (empty($phoneContact))
 				return self::getResult(1, '', '收件人手机不能为空');*/
			
// 			//重复发货 添加不同的标识码
// 			$extra_id = isset($data['extra_id'])?$data['extra_id']:'';
// 			$customer_number = CarrierAPIHelper::getCustomerNum($order,$extra_id);
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 126,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 60
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$tmpAddressMode1 = '';
			if(!empty($order->consignee_company)){
				$tmpAddressMode1 = $order->consignee_company.';';
			}
			if(!empty($order->consignee_address_line1))
				$tmpAddressMode1 .= $order->consignee_address_line1;
			if(!empty($order->consignee_address_line2))
				$tmpAddressMode1 .=' '. $order->consignee_address_line2;
			if(!empty($order->consignee_address_line3))
				$tmpAddressMode1 .= ' '.$order->consignee_address_line3;
			if(!empty($order->consignee_district)){
				$tmpAddressMode1 .=';'. $order->consignee_district;
			}
			if(!empty($order->consignee_county)){
				$tmpAddressMode1 .=','. $order->consignee_county;
			}
			
			//法国专线，收件地址不能有非英文的信息
			if($service->shipping_method_code == 120){
				$tmpAddressMode1 = str_replace(',', ' ', $tmpAddressMode1);
				$tmpAddressMode1 = str_replace(';', ' ', $tmpAddressMode1);
			}
			
			$orderMain = array(
					'username'=>$this->username,	//用户名
					'password'=>$this->password,	//密码 md5 32位数小写
					'fuwu'=>$service->shipping_method_code,	//渠道数字
					'danhao'=>$customer_number,	//订单号
					'guahao'=>'',	//挂号 （如需申请单号请留空）
					'contact'=>$order->consignee,	//收件人名称
					'gs'=>$order->consignee_company,	//收件人公司
					'tel'=>$phoneContact,	//收件人电话,2)$order->consignee_mobile:收件人手机
					'sj'=>$order->consignee_mobile,	//收件人电话,2)$order->consignee_mobile:收件人手机
					'yb'=>$order->consignee_postal_code,	//邮编
					'country'=>($order->consignee_country_code == 'UK' ? 'GB' : $order->consignee_country_code),	//目的地国家 建议二字代码，UK需要转换为GB
					'state'=>$tmpConsigneeProvince,	//省份
					'cs'=>$order->consignee_city,	//城市
					'tto'=>$tmpAddressMode1,	//收件人地址
					'zzhong'=>0,	//包裹总重量 单位(克G)
					'title2'=>$e['pickingInfo'],	//配货信息（用来显示你的配货信息，或者其他货品信息）
					'zprice'=>0,	//货品总价值USD可以跟下面的价值相同
					'title'=>'',	//货品名称
					'shu'=>0,	//货品数量
					'zhong'=>0,	//货品单重量 (G克)
					'price'=>0,	//货品单价值USD
// 					'fk'=>'',	//付款时间
					'dp'=>'',	//所属店铺
					'bei'=>isset($e['remark']) ? $e['remark'] : '',	//备注
					'api'=>'littleboss.com',
			);
			
			//记录SKU添加了几次
			$manyTimes = 1;
			
			// object/array required  海关申报信息
			if(!empty($declarationInfo['products'])){
				foreach ($declarationInfo['products'] as $j => $vitem){
					if($is_CustomsFormSpan)
						$quantity = empty($e['qty'][$j]) ? 0 : $e['qty'][$j];
					else
						$quantity = $vitem['quantity'];
					
					if(empty($orderMain['title']) || strlen($orderMain['title'] . $e['enName'][$j]) < 33){
						$orderMain['title'] .= $e['enName'][$j].',';
					}
					$orderMain['shu'] += $quantity;
					$orderMain['zhong'] += (empty($e['declaredWeight'][$j]) ? 0 : $e['declaredWeight'][$j]);
					$orderMain['price'] += (empty($e['declaredValue'][$j]) ? 0 : $e['declaredValue'][$j]);
					
					$orderMain['zprice'] += $quantity * (empty($e['declaredValue'][$j]) ? 0 : $e['declaredValue'][$j]);
					$orderMain['zzhong'] += $quantity * (empty($e['declaredWeight'][$j]) ? 0 : $e['declaredWeight'][$j]);
					
					if($is_CustomsFormSpan)
						break;
				}
				
				foreach ($declarationInfo['products'] as $j => $vitem){
					if(!(empty($vitem['sku']))){
						$orderMain['sku'.$manyTimes] = $vitem['sku'];
						$orderMain['skushu'.$manyTimes] = $vitem['quantity'];
							
						$manyTimes++;
					}
				}
			}
				
			$orderMain['title']=substr($orderMain['title'],0,-1);
			
			if (empty($orderMain['shu'])){
				return self::getResult(1, '', '数量不能为0');
			}
			
			if (empty($orderMain['zhong'])){
				return self::getResult(1, '', '重量不能为0');
			}
			
			if (empty($orderMain['price'])){
				return self::getResult(1, '', '金额不能为0');
			}
			
			if (empty($orderMain['title'])){
				return self::getResult(1, '', '报关名不能为空');
			}
			
// 			print_r($orderMain);
// 			exit;
				
			//多条商品信息列表
			$request = $orderMain;
				
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info('LB_ANJUNCarrierAPI1 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($request,1), "file");
			
			$url = self::$url_aj.'napi.asp';
			
			$getKeyValue = array();
			foreach($request as $key=>$value){
				$getKeyValue[] =  "$key=".urlencode(trim($value));
			}
			$url_params = '?'.implode("&", $getKeyValue);
			
			$response = $this->submitGate->mainGate($url, $url_params, 'curl', 'GET', 40);
			
			\Yii::info('lb_anjun,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
			
			//安骏的访问速度不能过快，这里限制为200000微秒
			usleep(200000);
			
// 			return print_r($response);
// 			exit;
			
// 			if($response['error']){return $response;}
			$response = $response['data'];
			
// 			$isSuccess = $this->getCallCarrierApiIsSuccess($response);
// 			if ($isSuccess){
// 				return self::getResult(1,'',$isSuccess);
// 			}

			$tmpArr = explode(",",$response);
			
// 			if(count($tmpArr) !== 2){
// 				throw new CarrierException($response);
// 			}

			if(count($tmpArr) < 2){
			    if(empty($response))
				    throw new CarrierException('安骏API返回空白错误！');
			    else 
			        throw new CarrierException($response);
			}
			
		    $status = 0;    //判断是否为提交成功，0否1是
			if($tmpArr[0] == 'false'){
				$tmpError = str_replace("false,", "", $response);
				
				if(is_numeric($tmpError)){
				    //当错误为订单号重复时，再重新获取跟踪号判断订单是否上传成功，当上传成功，继续标记已提交
				    if($tmpError == '005'){
				        try{
				        	$params = '&danhao='.$customer_number;
				        
				        	$response_2 = Helper_Curl::get(self::$url_aj.'/api2.asp?username='.$this->username.'&password='.$this->password.$params);
				        	
				        	\Yii::info('LB_ANJUNCarrierAPI3 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($response_2,1), "file");
				        
				        	$tmpArr_2 = explode(",",$response_2);
				        	if(count($tmpArr_2) == 2)
				        	{
				        		if(($tmpArr_2[0] == $customer_number) && (strlen($tmpArr_2[1]) >= 5) && (strlen($tmpArr_2[1]) <= 32))
				        		{
				        			$tmpArr[2] = $tmpArr_2[1];
				        			$status = 1;
				        		}
				        	}
				        }
				        catch (CarrierException $e)
				        {
				        	return self::getResult(1,'',$e->msg());
				        }
				    }
				    
				    if( $status != 1)
				    {
    					$tmpError1 = $this->getAnJunErrorMsgByCode($tmpError);
    					if (!empty($tmpError1))
    					{
    						if($tmpError == '006')
    							$tmpError1 = '或者安骏系统挂号不足，请等待我司补充';
    						
    						throw new CarrierException($tmpError1);
    					}
				    }
				}
				else if($tmpError == ''){
					$tmpError = '安骏API返回空白错误！';
				}
				else{
				    $err = $tmpError;
				    $tmpArr2 = explode(",",$tmpError);
				    if(count($tmpArr2) > 1){
				        if($tmpArr2[0] == "007"){
				            $tmpError = str_replace("007,", "", $tmpError);
				            $tmpArr2 = explode(",",$tmpError);
				            if(!empty($tmpArr2[1])){
    				            $tmpArr2 = explode(":",$tmpArr2[1]);
    				            
    				            if(!empty($tmpArr2[1])){
    				                $tmpError = $tmpArr2[1];
    				            }
				            }
				            
				            //当是%u编码时，则解码
				            if(substr_count($tmpError, '%u') > 5){
				            	$tmpError = self::CusEncode($tmpError);
				            }
				        }
				        else if(is_numeric($tmpArr2[0]) && !is_numeric($tmpArr2[1])){
				            $tmpError = $tmpArr2[0];
				        }
				        else{
				            $tmpError = $tmpArr2[1];
				        }
				        
				        if(is_numeric($tmpError)){
			        		$tmpError1 = $this->getAnJunErrorMsgByCode($tmpError);
			        		if (!empty($tmpError1)){
			        			throw new CarrierException($tmpError1);
			        		}
			        		else{
			        		    throw new CarrierException($err);
			        		}
				        }
				    }
				}
				
				if( $status != 1)
				    throw new CarrierException($tmpError);
			}
			
			//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
			$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$tmpArr[2],'');
			
			$print_param = array();
			$print_param['carrier_code'] = $service->carrier_code;
			$print_param['api_class'] = 'LB_ANJUNCarrierAPI';
			$print_param['username'] = $this->username;
			$print_param['password'] = $this->password;
			$print_param['tracking_number'] = $tmpArr[2];
			$print_param['run_status'] = 4;
			
			try{
				CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
			}catch (\Exception $ex){
			}
			
			return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$tmpArr[2]);
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/12				初始化
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
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	**/
	public function getTrackingNO($data){
// 		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
		
		try{
            $order = $data['order'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0,1,$order);
            $shipped = $checkResult['data']['shipped'];

            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];// object SysCarrierAccount
            $service = $info['service'];// object SysShippingService

            $this->username = $account->api_params['username'];
            $this->password = $account->api_params['password'];

            $order = $data['order'];
            $params = '&danhao='.$order->customer_number;
            
            $response = Helper_Curl::get(self::$url_aj.'/api2.asp?username='.$this->username.'&password='.$this->password.$params);
            
            $tmpArr = explode(",",$response);

            if(count($tmpArr) != 2){
            	return self::getResult(1,'', $response);
            }else{
            	if(($tmpArr[0] == $order->customer_number) && (strlen($tmpArr[1]) >= 5) && (strlen($tmpArr[1]) <= 32)){
					$shipped->tracking_number = $tmpArr[1];
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					return self::getResult(0, '',  '物流跟踪号:' . $tmpArr[1]);
            	}else{
            		return self::getResult(1,'', $response);
            	}
            }
        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }
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
// 			$pdf = new \PDFMerger();
				
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
				
			$this->username = $account_api_params['username'];
			$this->password = $account_api_params['password'];
				
			$param = array();
			$url_params = '';
			$guahao=array();
			$g='';
			// object required 配置信息
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
				$guahao=$shipped->tracking_number;
				//print_r($guahao);
				$g.=$guahao.',';			
			}
			//print_r($g);
			//取得打印尺寸
			$printFormat = $service['carrier_params']['printFormat'];
			$url_params = '?username='.trim($this->username).'&password='.$this->password.'&e='.(empty($printFormat) ? '1' : $printFormat).'&guahao='.$g;//$shipped->tracking_number;
			// 				$url_params = '?username='.$this->username.'&password='.$this->password.'&guahao='.'xxxx';
			//$url_params .= ','.$shipped->tracking_number;
			$url = self::$url_aj.'api5.asp';
			
			//当是E邮宝渠道时，直接返回url
			if( strpos($service->shipping_method_name, 'E邮宝') !== false)
			{
			    $pdf_url = Helper_Curl::get($url.$url_params);
			    $start = strpos($pdf_url, 'http://');
			    $end = strpos($pdf_url, '">here');
			    if($start > 0 && $end > $start){
			        $pdf_url = substr($pdf_url, $start, $end - $start);
			    }
			    else{
			        return self::getResult(1,'','安骏返回错误连接！');
			    }
			    
			    return self::getResult(0,['pdfUrl'=>$pdf_url, 'type'=>'1'],'连接已生成,请点击并打印');
			}
			else 
			{
    			$response = self::get($url.$url_params,null,null,false,null,null,true);
    			
    			if($response === false)
    			    return self::getResult(1,'','安骏返回打印超时！');
    			
    			if(strlen($response)<1000){
    				foreach ($data as $v){
    					$oneOrder = $v['order'];
    					$oneOrder->carrier_error = $response;
    					$oneOrder->save();
    				}
    				return self::getResult(1, '', $response);
    			}
    			$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$oneOrder->customer_number, 0);
    // 			$pdf->addPDF($pdfUrl['filePath'],'all');
    			/*foreach ($data as $v){
    				$oneOrder = $v['order'];
    				$oneOrder->is_print_carrier = 1;
    				$oneOrder->print_carrier_operator = $puid;
    				$oneOrder->printtime = time();
    				$oneOrder->save();
    			}*/
    			
    			//合并多个PDF  这里还需要进一步测试
    // 			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
    			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取渠道信息列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/27				初始化
	 +----------------------------------------------------------
	 **/
	public function getChannelList(){
		$url = self::$url_aj.'api4.asp?username='.$this->username.'&password='.$this->password;
		$response = Helper_Curl::get($url);
		$response = explode("<br>",$response);
		$result = '';
		
		for($i=1; $i <= count($response); $i++){
			if (!empty($response[$i])){
				$tmpArr = explode("-",$response[$i]);
				$result .= $tmpArr[1].':'.$tmpArr[0].';';
			}
		}
		
		return $result;
	}
	
	/**
	 * 用来获取运输方式
	 *
	 * @author		hqw		2015/12/21				初始化
	 * 公共方法
	 **/
	public function getCarrierShippingServiceStr($account)
	{
		$url = self::$url_aj.'api4.asp?username=SZLJC&password=c3e85ac6f5fdb0a76c91e49a8d49c468';
		$response = Helper_Curl::get($url);
		
		$response = explode("<br>",$response);
		$result = '';
		
		for($i=1; $i <= count($response); $i++){
			if (!empty($response[$i])){
				$tmpArr = explode("-",$response[$i]);
				$result .= $tmpArr[1].':'.$tmpArr[0].';';
			}
		}
		
		if(empty($result)){
			return self::getResult(1, '', '');
		}else{
			return self::getResult(0, $result, '');
		}
	}
	
	/*
	 * 获取调用的api是否成功
	*/
	public function getCallCarrierApiIsSuccess($key){
		$arr = [
			'001'=>'帐号错误',
			'002'=>'密码错误',
			'003'=>'帐号被锁定',
			'004'=>'登录失败',
			'005'=>'订单号重复',
			'006'=>'提交的挂号在系统已经存在',
			'007'=>'挂号分配失败，请重新提交分配',
			'008'=>'系统挂号不足，请等待我司补充',
			'009'=>'渠道不存在或者已经关闭',
			'010'=>'预报提交失败，请再次尝试提交',
			'011'=>'挂号已预报，请勿重复预报。',
			'012'=>'订单号不存在',
			'013'=>'登录失败',
		];
		if(array_key_exists($key,$arr))return $arr[$key];
		else return false;
	}
	
	/**
	 * 发起请求
	 *
	 * @param string $url
	 * @param string $requestBody
	 * @param string $requestHeader
	 * @param bool $justInit	是否只是初始化，用于并发请求
	 * @param string $responseSaveToFileName	结果保存到文件，函数只返回true|false
	 * @param bool $requestFollowlocation	启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
	 * @return bool|string
	 */
	public static function get($url,$requestBody=null,$requestHeader=null,$justInit=false,$responseSaveToFileName=null,$http_version=null,$requestFollowlocation=false){
		$connection = curl_init();
	
		curl_setopt($connection, CURLOPT_URL,$url);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		if (!is_null($requestHeader)){
			curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
		}
		if (!is_null($http_version)){
			curl_setopt($connection, CURLOPT_HTTP_VERSION, $http_version);
		}
		if (!is_null($responseSaveToFileName)){
			$fp=fopen($responseSaveToFileName,'w');
			curl_setopt($connection, CURLOPT_FILE, $fp);
		}else {
			curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		}

		curl_setopt($connection, CURLOPT_CONNECTTIMEOUT,self::$connecttimeout);
		curl_setopt($connection, CURLOPT_TIMEOUT,10);
		if ($justInit){
			return $connection;
		}
		if ($requestFollowlocation){
			//启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
			curl_setopt($connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($connection, CURLOPT_MAXREDIRS, 3);
		}
	
		$response = curl_exec($connection);
 		self::$last_post_info=curl_getinfo($connection);
		$error=curl_error($connection);
		curl_close($connection);
		if (!is_null($responseSaveToFileName)){
			fclose($fp);
		}
		
		if ($error || self::$last_post_info['http_code'] != '200')
		{
		    \Yii::info('LB_ANJUNCarrierAPI4 http_code：'.self::$last_post_info['http_code'].',err:'.$error, "file");
		    return false;
			//throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url.'DATA:'.$requestBody);
		}
		return $response;
	}
	
	/*
	 * 用来确定打印完成后 订单的下一步状态
	 * 
	 * 公共方法
	*/
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
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
	
			//设置用户密钥Token
			$this->username = trim($print_param['username']);
			$this->password = $print_param['password'];
	
			$url_params = '?username='.$this->username.'&password='.$this->password.'&e=1&guahao='.$print_param['tracking_number'];
			
			$url = self::$url_aj.'api5.asp';
			$response = self::get($url.$url_params,null,null,false,null,null,true);
			
			if($response === false)
			    return ['error'=>1, 'msg'=>'安骏返回打印超时！', 'filePath'=>''];
			
			if(strlen($response)<1000){
				return ['error'=>1, 'msg'=>'打印失败！错误信息：'.print_r($response,true), 'filePath'=>''];
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
	public function getVerifyCarrierAccountInformation($data)
	{
		$result = array('is_support'=>1,'error'=>1);
	
		try
		{
            $response = Helper_Curl::get(self::$url_aj.'/api2.asp?username='.$data['username'].'&password='.$data['password'].'&danhao=00001');
			if(!empty($response))
			{
				if($response != 'ERROR:001' && $response != 'ERROR:002' && $response != 'ERROR:003' && $response != 'ERROR:004')
					$result['error'] = 0;
			}
		}
		catch(CarrierException $e){}
	
		return $result;
	}
	
	//根据安骏的错误code来获取对应的错误说明
	public function getAnJunErrorMsgByCode($error_code){
		$error_arr = array(
				'6101'=>'请求数据缺少必选项',			//荷兰E挂号/美国小包错误报告
				'6102'=>'寄件方公司名称为空',
				'6103'=>'寄方联系人为空',
				'6106'=>'寄件方详细地址为空',
				'6107'=>'到件方公司名称为空',
				'6108'=>'到件方联系人为空',
				'6111'=>'到件方地址为空',
				'6112'=>'到件方国家不能为空',
				'6114'=>'必须提供客户订单号',
				'6115'=>'到件方所属城市名称不能为空',
				'6116'=>'到件方所在县/区不能为空',
				'6117'=>'到件方详细地址不能为空',
				'6118'=>'订单号不能为空',
				'6119'=>'到件方联系电话不能为空',
				'6120'=>'快递类型不能为空',
				'6121'=>'寄件方联系电话不能为空',
				'6122'=>'筛单类别不合法',
				'6123'=>'运单号不能为空',
				'6124'=>'付款方式不能为空',
				'6125'=>'需生成电子运单,货物名称等不能为空',
				'6126'=>'月结卡号不合法',
				'6127'=>'增值服务名不能为空',
				'6128'=>'增值服务名不合法',
				'6129'=>'付款方式不正确',
				'6130'=>'体积参数不合法',
				'6131'=>'订单操作标识不合法',
				'6132'=>'路由查询方式不合法',
				'6133'=>'路由查询类别不合法',
				'6134'=>'未传入筛单数据',
				'6135'=>'未传入订单信息',
				'6136'=>'未传入订单确认信息',
				'6137'=>'未传入请求路由信息',
				'6138'=>'代收货款金额传入错误',
				'6139'=>'代收货款金额小于0错误',
				'6140'=>'代收月结卡号不能为空',
				'6141'=>'无效月结卡号,未配置代收货款上限',
				'6142'=>'超过代收货款费用限制',
				'6143'=>'是否自取件只能为1或2',
				'6144'=>'是否转寄件只能为1或2',
				'6145'=>'是否上门收款只能为1或2',
				'6146'=>'回单类型错误',
				'6150'=>'订单不存在',
				'8000'=>'报文参数不合法',
				'8001'=>'IP未授权',
				'8002'=>'服务（功能）未授权',
				'8003'=>'查询单号超过最大限制',
				'8004'=>'路由查询条数超限制',
				'8005'=>'查询次数超限制',
				'8006'=>'已下单，无法接收订单确认请求',
				'8007'=>'此订单已经确认，无法接收订单确认请求',
				'8008'=>'此订单人工筛单还未确认，无法接收订单确认请求',
				'8009'=>'此订单不可收派,无法接收订单确认请求。',
				'8010'=>'此订单未筛单,无法接收订单确认请求。',
				'8011'=>'不存在该接入编码与运单号绑定关系',
				'8012'=>'不存在该接入编码与订单号绑定关系',
				'8013'=>'未传入查询单号',
				'8014'=>'校验码错误',
				'8015'=>'未传入运单号信息',
				'8016'=>'重复下单',
				'8017'=>'订单号与运单号不匹配',
				'8018'=>'未获取到订单信息',
				'8019'=>'订单已确认',
				'8020'=>'不存在该订单跟运单绑定关系',
				'8021'=>'接入编码为空',
				'8022'=>'校验码为空',
				'8023'=>'服务名为空',
				'8024'=>'未下单',
				'8025'=>'未传入服务或不提供该服务',
				'8026'=>'不存在的客户',
				'8027'=>'不存在的业务模板',
				'8028'=>'客户未配置此业务',
				'8029'=>'客户未配置默认模板',
				'8030'=>'未找到这个时间的合法模板',
				'8031'=>'数据错误，未找到模板',
				'8032'=>'数据错误，未找到业务配置',
				'8033'=>'数据错误，未找到业务属性',
				'8034'=>'重复注册人工筛单结果推送',
				'8035'=>'生成电子运单，必须存在运单号',
				'8036'=>'注册路由推送必须存在运单号',
				'8037'=>'已消单',
				'8038'=>'业务类型错误',
				'8039'=>'寄方地址错误',
				'8040'=>'到方地址错误',
				'8041'=>'寄件时间格式错误',
				'8042'=>'客户账号异常，请联系客服人员！',
				'8043'=>'该账号已被锁定，请联系客服人员！',
				'8044'=>'此订单已经处理中，无法接收订单修改请求',
				'4001'=>'系统发生数据错误或运行时异常',
				'4002'=>'报文解析错误',
				'9000'=>'身份验证失败',
				'9001'=>'客户订单号超过长度限制',
				'9002'=>'客户订单号存在重复',
				'9003'=>'客户订单号格式错误，只能包含数字和字母',
				'9004'=>'运输方式不能为空',
				'9005'=>'运输方式错误',
				'9006'=>'目的国家不能为空',
				'9007'=>'目的国家错误，请填写国家二字码',
				'9008'=>'收件人公司名超过长度限制',
				'9009'=>'收件人姓名不能为空',
				'9010'=>'收件人姓名超过长度限制',
				'9011'=>'收件人州或省超过长度限制',
				'9012'=>'收件人城市超过长度限制',
				'9013'=>'联系地址不能为空',
				'9014'=>'联系地址超过长度限制',
				'9015'=>'收件人手机号码超过长度限制',
				'9016'=>'收件人邮编超过长度限制',
				'9017'=>'收件人邮编只能是英文和数字',
				'9018'=>'重量数字格式不准确',
				'9019'=>'重量必须大于0',
				'9020'=>'重量超过长度限制',
				'9021'=>'是否退件填写错误，只能填写Y或N',
				'9022'=>'海关申报信息不能为空',
				'9023'=>'英文申报品名不能为空',
				'9024'=>'英文申报品名超过长度限制',
				'9025'=>'英文申报品名只能为英文、数字、空格、（）、()、，、,%',
				'9026'=>'申报价值必须大于0',
				'9027'=>'申报价值必须为正数',
				'9028'=>'申报价值超过长度限制',
				'9029'=>'申报品数量必须为正整数',
				'9030'=>'申报品数量超过长度限制',
				'9031'=>'中文申报品名超过长度限制',
				'9032'=>'中文申报品名必须为中文',
				'9033'=>'海关货物编号超过长度限制',
				'9034'=>'海关货物编号只能为数字',
				'9035'=>'收件人手机号码格式不正确',
				'9036'=>'服务商单号或顺丰单号已用完，请联系客服人员',
				'9037'=>'寄件人姓名超过长度限制',
				'9038'=>'寄件人公司名超过长度限制',
				'9039'=>'寄件人省超过长度限制',
				'9040'=>'寄件人城市超过长度限制',
				'9041'=>'寄件人地址超过长度限制',
				'9042'=>'寄件人手机号码超过长度限制',
				'9043'=>'寄件人手机号码格式不准确',
				'9044'=>'寄件人邮编超过长度限制',
				'9045'=>'寄件人邮编只能是英文和数字',
				'9046'=>'不支持批量操作',
				'9047'=>'批量交易记录数超过限制',
				'9048'=>'此订单已确认，不能再操作',
				'9049'=>'此订单已收货，不能再操作',
				'9050'=>'此订单已出货，不能再操作',
				'9051'=>'此订单已取消，不能再操作',
				'9052'=>'收件人电话超过长度限制',
				'9053'=>'收件人电话格式不正确',
				'9054'=>'寄件人电话超过长度限制',
				'9055'=>'寄件人电话格式不正确',
				'9056'=>'货物件数必须为正整数',
				'9057'=>'货物件数超过长度限制',
				'9058'=>'寄件人国家错误，请填写国家二字码，默认为CN',
				'9059'=>'货物单位超过长度限制，默认为PCE',
				'9060'=>'货物单位重量格式不正确',
				'9061'=>'货物单位重量超过长度限制',
				'9062'=>'该运输方式暂时不支持此国家的派送，请选择其他派送方式',
				'9063'=>'当前运输方式暂时不支持该国家此邮编的派送，请选择其他派送方式！',
				'9064'=>'该运输方式必须输入邮编',
				'9065'=>'寄件人国家国家不能为空',
				'9066'=>'寄件人公司名不能为空',
				'9067'=>'寄件人公司名不能包含中文',
				'9068'=>'寄件人姓名不能为空',
				'9069'=>'寄件人姓名不能包含中文',
				'9070'=>'寄件人城市不能为空',
				'9071'=>'寄件人城市不能包含中文',
				'9072'=>'寄件人地址不能为空',
				'9073'=>'寄件人地址不能包含中文',
				'9074'=>'寄件人邮编不能为空',
				'9075'=>'寄件人邮编不能包含中文',
				'9076'=>'收件人公司名不能为空',
				'9077'=>'收件人公司名不能包含中文',
				'9078'=>'收件人城市不能为空',
				'9079'=>'收件人城市不能包含中文',
				'9080'=>'查询类别不正确，合法值为：1（运单号），2（订单号）',
				'9081'=>'查询号不能不能为空。',
				'9082'=>'查询方法错误，合法值为：1（标准查询）',
				'9083'=>'查询号不能超过10个。注：多个单号，以逗号分隔。',
				'9084'=>'收件人电话不能为空',
				'9085'=>'收件人姓名不能包含中文',
				'9086'=>'英文申报品名必须为英文',
				'9087'=>'收件人手机不能包含中文',
				'9088'=>'收件人电话不能包含中文',
				'9089'=>'寄件人电话不能包含中文',
				'9090'=>'寄件人手机不能包含中文',
				'9091'=>'海关货物编号不能为空',
				'9092'=>'联系地址不能包含中文',
				'9093'=>'当总申报价值超过75欧元时【收件人邮箱】不能为空',
				'9094'=>'收件人邮箱超过长度限制',
				'9095'=>'收件人邮箱格式不正确',
				'9096'=>'寄件人省不能包含中文',
				'9097'=>'收件人州或省超不能包含中文',
				'9098'=>'收件人邮编不能包含中文',
				'9099'=>'英文申报品名根据服务商要求，申报品名包含 disc、speaker、powerbank、battery',
				'9100'=>'英文申报品名根据服务商要求，申报品名包含 disc、speaker、powerbank、battery',
				'9101'=>'magne禁止运输，请选择其他运输方式！寄件人省不能为空收件人州或省不能为空',
				'9102'=>'收件人邮编只能为数字',
				'9103'=>'收件人邮编只能为4个字节',
				'9104'=>'【收件人邮编】,【收件人城市】,【州╲省】不匹配',
				'9105'=>'申报价值大于200美元时，【海关货物编号】不能为空！',
				'9106'=>'收件人州或省不正确',
				'9107'=>'寄件人邮编只能包含数字',
				'9108'=>'收件人邮编格式不正确',
				'9109'=>'【州╲省】美国境外岛屿、区域不支持派送！',
				'9110'=>'【州╲省】APO/FPO军事区域不支持派送！',
				'9111'=>'客户EPR不存在！',
				'9112'=>'【配货备注】长度超过限制！',
				'9113'=>'【配货名称】不能包含中文！',
				'9114'=>'【配货名称】长度超过限制！',
				'9115'=>'【包裹长（CM）】数字格式不正确！',
				'9116'=>'【包裹长（CM）】不能超过4位！',
				'9117'=>'【包裹长（CM）】必须大于0！',
				'9118'=>'【包裹宽（CM）】数字格式不正确！',
				'9119'=>'【包裹宽（CM）】不能超过4位！',
				'9120'=>'【包裹宽（CM）】必须大于0！',
				'9121'=>'【包裹高（CM）】数字格式不正确！',
				'9122'=>'【包裹高（CM）】不能超过4位！',
				'9123'=>'【包裹高（CM）】必须大于0！',
				'9124'=>'【收件人身份证号/护照号】只能为数字和字母！',
				'9125'=>'【收件人身份证号/护照号】长度不能超过18个字符！',
				'9126'=>'【VAT税号】只能为数字和字母！',
				'9127'=>'【VAT税号】长度不能超过20个字符！',
				'9128'=>'【是否电池】填写错误，只能填写Y或N！',
				'9129'=>'寄件人公司名不能包含,或"',
				'9130'=>'寄件人姓名不能包含,或"',
				'9131'=>'寄件人省不能包含,或"',
				'9132'=>'寄件人城市不能包含,或"',
				'9133'=>'寄件人地址不能包含,或"',
				'9134'=>'寄件人电话不能包含,或"',
				'9135'=>'寄件人手机号码不能包含,或"',
				'9136'=>'收件人公司名不能包含,或"',
				'9137'=>'收件人姓名不能包含,或"',
				'9138'=>'收件人城市不能包含,或"',
				'9139'=>'联系地址不能包含,或"',
				'9140'=>'收件人电话不能包含,或"',
				'9141'=>'收件人手机不能包含,或"',
				'9142'=>'英文申报品名不能包含,或"',
				'9144'=>'收件人州或省只能是英文字符',
				'9145'=>'寄件人电话不能为空',
				'9146'=>'重量不能为空',
				'9147'=>'收件人电话不能为空',
				'9150'=>'【商品网址链接】长度超过限制！',
				'9151'=>'【平台网址】长度超过限制！',
				'9152'=>'【店铺名称】长度超过限制！',
				'9153'=>'【商品网址链接】不能为空！',
				'9154'=>'【平台网址】不能为空！',
				'9155'=>'【店铺名称】不能为空！',
				'9156'=>'【收件人城市】,【国家】不匹配！',
				'9157'=>'【目的地区域】不提供派送服务！',
				'9158'=>'【寄件人公司名】不能为纯数字/纯符号！',
				'9159'=>'【寄件人姓名】只能为字母,不能有其他符号！',
				'9160'=>'【寄件人省】只能为字母,不能有其他符号！',
				'9161'=>'【寄件人城市】只能为字母,不能有其他符号！',
				'9162'=>'【寄件人地址】不能为纯数字/纯符号！',
				'9163'=>'【收件人州或省】只能为州代码，只能为字母！',
				'9164'=>'【英文申报品名】必须是英文或英文+数字组合，不能为纯数字/符号！',
				'9165'=>'很抱歉,您的账号缺少【月结卡号】,不能执行下单操作,请联系客服人员！',
				'9166'=>'请使用老产品代码下单！',
				'9167'=>'请使用新产品代码下单！',
				'9168'=>'很抱歉,您的账号缺少【接入编码】,不能执行下单操作,请联系客服人员！',
				'9169'=>'温馨提示：根据服务商要求，【寄件人姓名】不能使用数字、代号作为寄件人姓名，请规范填报的寄件人姓名',
				'9170'=>'请先登陆顺丰国际网站进行电子签约，谢谢！',
				'9171'=>'收件人邮编不能为空！',
				'9172'=>'收件人邮编只能是英文字母和数字！',
				'9173'=>'【寄件人手机】格式不正确，只能包含阿拉伯数字、-',
				'9174'=>'【寄件人电话】格式不正确，只能包含阿拉伯数字、-',
				'9175'=>'【英文申报品名】只能包含阿拉伯数字、英文字母、（、）、(、)、，、,、-、_、%',
				'9176'=>'【收件人手机】长度不能小于6位',
				'9177'=>'【收件人手机】格式不正确，只能包含阿拉伯数字、英文字母、+、-、（）、()',
				'9178'=>'【收件人电话】长度不能小于6位',
				'9179'=>'【收件人电话】格式不正确，只能包含阿拉伯数字、英文字母、+、-、（）、()',
				'9180'=>'登陆后台管理系统签订折扣协议！',
				
				'-1'=>'签名不正确',					//加邮宝错误报告
				'-2'=>'提交数据data必填',
				'-3'=>'appId必填',
				'-4'=>'数据器故障',
				'-5'=>'程序错误请联系我们',
				'1001'=>'数值错误',
				'100002'=>'包含不合法字符',
				'110001'=>'未提供订单客户端编号',
				'110002'=>'订单客户端编号过长',
				'110003'=>'订单客户端编号重复',
				'110004'=>'收货人姓名未填',
				'110005'=>'收货人姓名过长',
				'110006'=>'收货人电话过长',
				'110007'=>'收货人电邮过长',
				'110008'=>'电商平台代码过长',
				'110009'=>'收货人公司过长',
				'110010'=>'出货点代码缺失或错误',
				'120001'=>'收货人地址第一行必填',
				'120002'=>'收货人地址第一行必填',
				'120003'=>'收货人地址第二行过长',
				'120004'=>'收货人地址第三行过长',
				'120005'=>'收货人城市必填',
				'120006'=>'收货人城市过长',
				'120007'=>'收货人城市与邮编不符',
				'120008'=>'收货人地址州名错误',
				'120009'=>'收货人地址州名必填',
				'120010'=>'收货人州名与邮编不符',
				'120011'=>'收货人邮编必填',
				'120012'=>'收货人邮编错误',
				'120013'=>'收货人国家错误',
				'130001'=>'商品描述必填',
				'130002'=>'商品描述过长',
				'130003'=>'重量超过服务标准',
				'130004'=>'重量低于服务标准',
				'130006'=>'商品价值超过服务标准',
				'130007'=>'SKU#过长',
				'130008'=>'币种错误',
				'130009'=>'商品价值必填',
				'190001'=>'商品列表丢失或者格式错误',
				'190002'=>'商品数量必填',
				'190010'=>'产地过长',
				
				'001'=>'帐号错误',		//E系统错误代码说明
				'002'=>'密码错误',
				'003'=>'帐号被锁定',
				'004'=>'登录失败',
				'005'=>'订单号重复',
				'006'=>'跟踪号重复',
				'007'=>'挂号分配失败,重新申请或者联系我司客服',
				'008'=>'条码已用完，请等待我司补充',
				'009'=>'渠道不存在或者已经关闭',
				'010'=>'预报提交失败，请再次尝试提交',
				'011'=>'挂号已预报，请勿重复预报。',
				'012'=>'订单号不存在',
				'013'=>'请检查本地COOKIES环境,如无问题请多试几次',
				'014'=>'系统数据库故障,请等待',
				'015'=>'订单数据有特殊字符：#&\'之类的',
				'016'=>'订单号在打包系统已存在',
				'017'=>'荷兰E申请失败,请在E系统查看错误报告',
				'018'=>'E邮宝接口错误,请IT人员更换应用池/或者检查一下订单是否有特殊字符',
				'020'=>'欧邮通英国只能走英国',
				'021'=>'英国邮编格式有误,邮编第二或第三之间有空格',
				'022'=>'E邮宝国家不支持派送',
				'023'=>'E系统内部错误',
				'024'=>'打包系统无法连接,订单写入失败.',
				'025'=>'E邮宝跟踪号生成有误,请等待修复',
				'026'=>'法国专线写入数据失败,请联系IT',
				'027'=>'预报数据失败',
				'028'=>'MS数据库连接失败,请联系IT',
				'029'=>'当前渠道不支持该国家派送',
				'030'=>'法国海外地区,不支持派送',
				'040'=>'单号不能为空',
				'041'=>'收件人不能为空',
				'042'=>'收件人电话不能为空',
				'043'=>'邮编不能为空',
				'044'=>'国家不能为空',
				'045'=>'省份不能为空',
				'046'=>'城市不能为空',
				'047'=>'地址不能为空',
				'048'=>'重量不能为空',
				'049'=>'价值不能为空',
				'050'=>'数量不能为空',
				'051'=>'英文品名不能为空',
				'052'=>'重量必须为数字且必须大于零',
				'053'=>'中邮预报是吧,检查特殊符号或',
				'054'=>'申报价值超值，会产生关税',
				'055'=>'仓储系统连接失败',
				'055'=>'订单号在仓储系统已存在',
				
				
				'10000'=>'JSON格式错误,请检查。JSON格式错误,请检查',		//德国英国澳大利亚专线错误报告
				'10001'=>'原单号异常，重复。请检查原单号',
				'10002'=>'转单号异常，重复。请检查转单号',
				'10003'=>'国家不存在。请查看国家代码号',
				'10004'=>'运输方式不存在。请查看运输方式代码号',
				'10005'=>'结算方式不存在。请查看结算方式代码号',
				'10006'=>'包裹类型不存在。请查看包裹类型代码号',
				'10007'=>'收件人为空。请填写收件人信息',
				'10008'=>'地址1不存在。请填写地址1信息',
				'10009'=>'邮编为空。请查看收件邮编',
				'10010'=>'特殊运输方式原单号必须为空。请检查原单号',
				'10011'=>'特殊运输方式转单号必须为空。请检查转单号',
				'10012'=>'特殊运输方式省必填。请检查收件人省/州',
				'10013'=>'特殊运输方式邮编与与省州不对应。请检查邮编',
				'10014'=>'特殊运输方式国家不匹配。请检查国家',
				'10015'=>'特殊运输方式收件人必须为英文并且小于35长度。请检查收件人',
				'10016'=>'特殊运输方式地址1必须为英文并且小于40。请检查地址1',
				'10017'=>'特殊运输方式地址2必须为英文并且小于60。请检查地址2',
				'10018'=>'特殊运输方式地址3必须为英文并且小于40。请检查地址3',
				'10019'=>'特殊运输方式公司必须为英文并且小于40。请检查公司名',
				'20000'=>'JSON格式错误,请检查。JSON格式错误,请检查',
				'20001'=>'订单唯一号异常或者订单状态异常。请检查订单唯一号或者拉取订单查看订单状态',
				'20002'=>'物品明细名字为空。请检查物品明细名字',
				'20003'=>'价格为空。请查看物品价格',
				'20004'=>'价格单位为空。请查看物品价格单位代码号',
				'20005'=>'颜色异常。请查看颜色代码号',
				'20006'=>'尺寸异常。请查看尺寸代码号',
				'20007'=>'重量为空。请填写重量',
				'20008'=>'重量单位异常。请查看重量单位代码号',
				'20009'=>'所属平台异常。请查看所属平台代码号',
				'20010'=>'特殊运输方式重量必须小于30kg。请查看重量',
				'20011'=>'特殊运输方式物品名字必须为英文并且长度小于50。请检查物品名字',
				'30000'=>'JSON格式错误,请检查。JSON格式错误,请检查',
				'30001'=>'订单唯一号异常或者订单状态异常 。请检查订单唯一号或者拉取订单查看订单状态',
				'30002'=>'申报明细名字为空。请检查申报明细名字',
				'30003'=>'申报价格为空。请查看申报价格',
				'30004'=>'申报数量为空。请查看申报数量',
				'30005'=>'申报数量单位异常。请查看申报数量单位代码号',
				'30006'=>'申报重量为空。请查看申报重量',
				'30007'=>'中文报关名称为空。请查看中文报关名称',
				'30008'=>'英文报关名称为空。请填写英文报关名称',
				'30009'=>'原产地为空。请填写原产地',
				'30010'=>'原产地名称为空。请填写原产地名称',
				'40000'=>'JSON格式错误,请检查。JSON格式错误,请检查',
				'40001'=>'订单唯一号异常或者订单状态异常 。请检查订单唯一号或者拉取订单查看订单状态',
				'40002'=>'特殊运输方式订单邮编为空。请检查邮编',
				'40003'=>'特殊运输方式州省为空。请检查州省',
				'40004'=>'特殊运输方式州省与邮编不匹配。请检查邮编和州省',
				'40005'=>'订单未生成单号。系统错误或者联系客服',
				'50000'=>'JSON格式错误,请检查。JSON格式错误,请检查',
				'50001'=>'订单唯一号异常或者订单状态异常 。请检查订单唯一号或者拉取订单查看订单状态',
				'50002'=>'此订单原单号为空,无法预报。请检查此订单，生成原单号请调用：生成原单号接口',
				
				'B0300'=>'订单信息导入成功',				//中快E速宝错误代码
				'B0301'=>'客户身份认证Token验证未通过',
				'B0302'=>'挂件订单类型标识非4',
				'B0303'=>'订单内件数超过100个',
				'B0304'=>'订单内件数为0个',
				'B0305'=>'物流名称不允许为空',
				'B0306'=>'根据token获取custId值为空',
				'B0307'=>'跟踪号不允许为空',
				'B0308'=>'跟踪号格式不正确',
				'B0309'=>'跟踪号系统已存在',
				'B0310'=>'客户身份识别Token不允许为空',
				'B0311'=>'订单类型不允许为空',
				'B0312'=>'物流产品代码不允许为空',
				'B0313'=>'订单号不允许为空',
				'B0314'=>'邮件重量不允许为空',
				'B0315'=>'发件人邮编不允许为空',
				'B0316'=>'发件人名称不允许为空',
				'B0317'=>'发件人地址不允许为空',
				'B0318'=>'发件人电话不允许为空',
				'B0319'=>'发件人移动电话不允许为空',
				'B0320'=>'发件人英文省名不允许为空',
				'B0321'=>'发件人城市名称英文不允许为空',
				'B0322'=>'收件人邮编不允许为空',
				'B0323'=>'收件人名称不允许为空',
				'B0325'=>'收件人地址不允许为空',
				'B0326'=>'收件人电话不允许为空',
				'B0327'=>'收件人移动电话不允许为空',
				'B0328'=>'收件人城市不允许为空',
				'B0329'=>'收件人州不允许为空',
				'B0330'=>'收件人国家中文名不允许为空',
				'B0331'=>'收件人英文国家名不允许为空',
				'B0332'=>'商品SKU编号不允许为空',
				'B0333'=>'原寄地不允许为空',
				'B0334'=>'发件人省名必须是英文',
				'B0335'=>'发件人城市名称必须是英文',
				'B0336'=>'收件人名称必须是英文',
				'B0337'=>'收件人地址必须是英文',
				'B0338'=>'收件人城市必须是英文',
				'B0339'=>'收件人州名必须是英文',
				'B0340'=>'收件人英文国家名必须是英文',
				'B0341'=>'商品英文名称必须是英文',
				'B0342'=>'订单业务类型错误',
				'B0343'=>'邮件重量必须是正整数',
				'B0344'=>'内件数量必须是正整数',
				'B0345'=>'内件重量（克）必须是正整数',
				'B0346'=>'当前物流产品跟踪号不允许为空',
				'B0348'=>'报关价格必须是数字',
				'B0349'=>'收件人电话和移动电话不能同时为空',
				'B0350'=>'收件人国家中文名对应国家代码不存在',
				'B0351'=>'当前客户的订单号系统已经存在',
				'B0352'=>'写入数据库失败',
				'B0353'=>'接口数据格式异常',
				'B0354'=>'推送物流产品代码系统不存在',
				'B0355'=>'国家产品类型限重未配置或未通邮，请联系速递业务人员确认',
				'B0356'=>'邮件重量超过产品限重',
				'B0357'=>'API根据产品类型申请条码返回空',
				'B0358'=>'该产品类型的号码池资源不足',
				'B0360'=>'订单类型最大长度不能超过1',
				'B0361'=>'物流产品代码最大长度不能超过2',
				'B0362'=>'订单号最大长度不能超过50',
				'B0363'=>'邮件重量最大长度不能超过8',
				'B0364'=>'最大长度不能超过120',
				'B0365'=>'发件人邮编最大长度不能超过10',
				'B0366'=>'发件人名称最大长度不能超过50',
				'B0367'=>'发件人地址最大长度不能超过200',
				'B0368'=>'发件人电话最大长度不能超过20',
				'B0369'=>'发件人移动电话最大长度不能超过20',
				'B0370'=>'发件人英文省名最大长度不能超过100',
				'B0371'=>'发件人英文城市名称最大长度不能超过100',
				'B0372'=>'收件人邮编最大长度不能超过16',
				'B0373'=>'收件人名称英文最大长度不能超过100',
				'B0374'=>'收件人地址英文最大长度不能超过200',
				'B0375'=>'收件人电话最大长度不能超过20',
				'B0376'=>'收件人移动电话最大长度不能超过20',
				'B0377'=>'收件人城市英文最大长度不能超过100',
				'B0378'=>'收件人州英文最大长度不能超过100',
				'B0379'=>'收件人电子邮箱最大长度不能超过64',
				'B0380'=>'收件人国家中文名最大长度不能超过32',
				'B0381'=>'收件人英文国家名最大长度不能超过64',
				'B0382'=>'商品SKU编号最大长度不能超过32',
				'B0383'=>'商品中文名称最大长度不能超过100',
				'B0384'=>'商品英文名称最大长度不能超过100',
				'B0385'=>'商品数量最大长度不能超过4',
				'B0386'=>'商品重量最大长度不能超过6',
				'B0387'=>'报关价格最大长度不能超过8',
				'B0388'=>'原寄地最大长度不能超过30',
				'B0389'=>'跟踪单号最大长度不能超过20',
				'B0390'=>'海关编码最大长度不能超过10',
				'B0391'=>'订单来源最大长度不能超过1',
				'B0392'=>'备注信息最大长度不能超过32',
				'B0393'=>'内件类型不允许为空',
				'B0394'=>'内件类型最大长度不能超过1',
				'B0395'=>'内件成分说明最大长度不能超过60',
				'B0396'=>'商品SKU编号最大长度不能超过100',
		);
		
		//如果为空直接返回空字符
		if(empty($error_arr[$error_code])){
			return '';
		}else{
			return $error_arr[$error_code];
		}
	}
	
	//对于%u编码类型进行解码
	public function CusEncode($str)
	{
		$ret = '';
		$len = strlen($str);
		for ($i = 0; $i < $len; $i++) {
			if ($str[$i] == '%' && $str[$i+1] == 'u') {
				$val = hexdec(substr($str, $i+2, 4));
				if ($val < 0x7f) 
					$ret .= chr($val);
				else if($val < 0x800) 
					$ret .= chr(0xc0|($val>>6)).chr(0x80|($val&0x3f));
				else 
					$ret .= chr(0xe0|($val>>12)).chr(0x80|(($val>>6)&0x3f)).chr(0x80|($val&0x3f));
				
				$i += 5;
			} 
			else if ($str[$i] == '%') {
				$ret .= urldecode(substr($str, $i, 3));
				$i += 2;
			} 
			else $ret .= $str[$i];
		}
		return $ret;
	}
}

?>