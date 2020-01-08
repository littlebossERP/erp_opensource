<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\SubmitGate;
use common\helpers\Helper_Curl;
use common\api\carrierAPI\BaseCarrierAPI;
use Qiniu\json_decode;
use eagle\modules\carrier\helpers\CarrierException;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-05-16
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 4px海外仓物流商API
 +------------------------------------------------------------------------------
 * @category	vendors
 * @package		vendors/overseaWarehouseAPI
 * @subpackage  Exception
 * @author		qfl<772821140@qq.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_4pxOverseaWarehouseAPI extends BaseOverseaWarehouseAPI 
{
	/*
		帐号 100800
		token oDuCfVi88b40oOuMYQUOcTh2b/T+uJdDBsJ+VOrlG6Q=1
	*/
	public $url = null;
	public $operate = null;
	public function __construct(){
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' )$this->url = "http://openapi.4px.com";
		else $this->url = 'http://apisandbox.4px.com';
	}
	function getOrderNO($data)
	{
		try{
			//订单对象
			$order = $data['order'];
			//表单提交的数据
			$e = $data['data'];

			$extra_id = isset($e['extra_id'])?$e['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
			
			//对当前条件的验证
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
			if($checkResult['error'])return self::getResult(1,'',$checkResult['msg']);
			$puid = $checkResult['data']['puid'];
			
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			if(!is_array($info))return self::getResult(1,'',$info);
			$s = $info['service'];

			// $request = [
			// 	'warehouseCode'=>'UKLH'
			// ];
			// $response = $this->sendData($s->carrier_account_id,'getOrderCarrier',$request);
			// $str = '';
			// $response = $response['data'];
			// foreach ($response as $k => $v) {
			// 	// $str .= $v->carrierCode.':'.$v->carrierName.';';
			// 	$str .= "'".$v->carrierCode."'=>'".$v->carrierName."',<br/>";
			// }
			// echo $str;die;

			#######################################################################################
			// echo 's';
			// var_dump($s);
			// echo 'data';
			// var_dump($data);
			// echo 'order';
			// var_dump($order);die;
			$carrier_params = $s->carrier_params;
			//获取平台代码
			$paltformCode = $this->getPlatformCode($order->order_source);
// 			$extra_id = isset($e['extra_id'])?$e['extra_id']:'';
// 			$customer_number = CarrierAPIHelper::getCustomerNum($order,$extra_id);
			
			$request = [
				'warehouseCode'=>$s->third_party_code,//仓库代码
				'referenceCode'=>$customer_number,//订单参考号
				'carrierCode'=>$s->shipping_method_code,//渠道代码
				'insureType'=>$carrier_params['insureType'],//保险类型代码
				// 'sellCode'=>,//销售交易号
				'description'=>$e['description'],//描述
				'insureMoney'=>$e['insureMoney']?$e['insureMoney']:$carrier_params['insureMoney'],//保险金额
				'platformCode'=>$paltformCode,//平台代码
				'remoteArea'=>'Y',//偏远地区是否走货，需要附加收费
			];
			
			$consigneeStreet='';
			if(!empty($order->consignee_address_line1))
				$consigneeStreet = $order->consignee_address_line1;
			if(!empty($order->consignee_address_line2))
				$consigneeStreet .=' '. $order->consignee_address_line2;
			if(!empty($order->consignee_address_line3))
				$consigneeStreet .=' '. $order->consignee_address_line3;
			if(!empty($order->consignee_district))
				$consigneeStreet .=' '. $order->consignee_district;
			if(!empty($order->consignee_county))
				$consigneeStreet .=' '. $order->consignee_county;
			
			$tmp_phone = '';
			if((strlen($order->consignee_phone)) <= 5){
				$tmp_phone = $order->consignee_phone.$order->consignee_mobile;
			}
			if($tmp_phone == ''){
				$tmp_phone = strlen($order->consignee_phone)>4?$order->consignee_phone:$order->consignee_mobile;
			}
			
			$consignee = [
				"state"=> $order->consignee_province,//州
				"fullName"=> $order->consignee,//收件人姓名
				"email"=> $order->consignee_email,
				"countryCode"=> $order->consignee_country_code,
				"street"=> $consigneeStreet,
				"city"=> $order->consignee_city,
				"postalCode"=> $order->consignee_postal_code,
				"phone"=> $tmp_phone,
				"company"=> $order->consignee_company,
				"doorplate"=>"",
			];
			//组织商品信息
			foreach($e['DeclarePieces'] as $k=>$v){
				$items[] = [
					'sku'=>$e['sku'][$k],
					'quantity'=>$e['DeclarePieces'][$k],
				];
			}
			$request['consignee'] = $consignee;
			$request['items'] = $items;
		    #########################################################################################
		    //数据组织完毕 准备发送
		    //	\Yii::info(['lb_4pxOversea',__CLASS__,__FUNCTION__,'postDate',print_r($request,true)],"edb\global");
							
		    $response = $this->sendData($s->carrier_account_id,'createDeliveryOrder',$request);
		    //	\Yii::info(['lb_4pxOversea',__CLASS__,__FUNCTION__,'responseData',print_r($response,true)],"edb\global");
		    //在返回的数据中把data部分进行处理 将其中的errorcode替换为可读内容
		    $service_codeArr = $s->service_code;
		    if(!$response['error'] && !empty($response['data']->documentCode)){
		    	$response = $response['data'];
				$r= array(
					'order_source'=>$order->order_source,//订单来源
					'selleruserid'=>$order->selleruserid,//卖家账号
					'tracking_number'=>'',//物流号（选填）
					'tracking_link'=>$s->web,//查询网址（选填）
					'shipping_method_code'=>isset($service_codeArr[$order->order_source])?$service_codeArr[$order->order_source]:'',//平台物流服务代码
					'shipping_method_name'=>$s->service_name,//平台物流服务名
					'order_source_order_id'=>$order->order_source_order_id,//平台订单号
					'return_no'=>'',//物流系统的订单号（选填）
					'shipping_service_id'=>$order->default_shipping_method_code,//物流服务id（选填）
					'addtype'=>'物流API',//物流号来源
					'signtype'=>'all',//标记类型 all或者part（选填）
					//'description'=>'',//备注（选填）
					'customer_number' => $response->documentCode,//物流商返回的客户单号物流系统订单唯一标示
				);
				$order->customer_number = $response->documentCode;
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return self::getResult(0,$r,'出库成功,订单号:'.$response->documentCode);
			}
			else{
				//return $response;
				\Yii::info(['lb_4pxOversea,puid:'.$puid.",order_id:".$order->order_id,__CLASS__,__FUNCTION__,'postDate',print_r($request,true)],"carrier_api");
				\Yii::info(['lb_4pxOversea,puid:'.$puid.",order_id:".$order->order_id,__CLASS__,__FUNCTION__,'response',print_r($response,true)],"carrier_api");
				return self::getResult(1,'','上传失败:'.(isset($response['msg'])?$response['msg']:''));
			}
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

	function getPlatformCode($data){
		$arr = [
			'ebay'=>'E',
			'amazon'=>'A',
			'aliexpress'=>'S',
			'wish'=>'W',
			'paypal'=>'L',
			'newegg'=>'N',
		];
		if(isset($arr[$data]))return $arr[$data];
		return '';
	}

	//数据请求
	function sendData($accountid,$operate,$request, $request_type = 'order'){
		$account= SysCarrierAccount::find()->where(['id'=>$accountid,'is_used'=>1])->one();
		if(empty($account))return self::getResult(1,'','该运输服务没有分配可用的物流商帐号');
		$api_params = $account->api_params;
		//设置公共参数 customerid 和 token
		$params = '?customerId='.$api_params['customerId'].'&token='.$api_params['token'].'&T+uJdDBsJ+VOrlG6Q=1&language=en_US';
		//拼接url
		$url = $this->url.'/api/service/woms/'.$request_type.'/'.$operate.$params;
		$request = json_encode($request);
		$response = $this->sendHttpRequest($request,1,'post',$url);
		//对返回数据进行处理
		$result = $this->getOutput($response);
		return $result;
	}

	//对返回的数据进行处理
	function getOutput($data){
		$arr = json_decode($data);
		
		if(!(json_last_error() == JSON_ERROR_NONE)){
			$arr = simplexml_load_string($data);
		}
		
		if($arr->errorCode==0){
			$data = $arr->data;
			if(isset($data->ack)){
				if($data->ack=='Y'){
					//请求成功
					return self::getResult(0,$data,'');
				}
				if($data->ack=='N'){
					$description='';
					foreach($data->errors as $v){
						$code2error = $this->getCode2Err($v->code);
						if($code2error)$description .= $code2error.'<br/>';
						else if($v->codeNote)$description .= $v->codeNote.'<br/>';
						else $description .= '请求错误,请检查订单状态<br/>';
					}
					return self::getResult(1,'',$description);
				}
			}
			return self::getResult(0,$data,'');
		}else{
			return self::getResult(1,'','错误代码'.$arr->errorCode.',物流商返回数据错误'.(empty($arr->errorMsg) ? '' : $arr->errorMsg));
		}
	}
	


	//订单交运
	function doDispatch($data){
		try{
			//订单对象
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			if(!is_array($info))return self::getResult(1,'',$info);
			$s = $info['service'];
			$params = $s->carrier_params;
			$request = [
				'orderCode'=>$order->customer_number,
				'remoteArea'=>$params['remoteArea'],
			];
			###################################
			$response = $this->sendData($s->carrier_account_id,'toExamineOrder',$request);
			if(!$response['error'] && !empty($response['data']->documentCode)){
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return self::getResult(0,'', '交运订单成功！订单号:'.$order->customer_number);
			}
			if($response['error']){
				return self::getResult(1,'','交运失败:'.(isset($response['msg'])?$response['msg']:''));
				//return $response;
			}
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

	//获取跟踪号
	function getTrackingNO($data){
		try{
			//订单对象
			$order = $data['order'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			if(!is_array($info))return self::getResult(1,'',$info);
			$s = $info['service'];

			$request = [
				'orderCode'=>$order->customer_number,
			];
			###################################
			$response = $this->sendData($s->carrier_account_id,'getDeliveryOrder',$request);
			// echo '<pre>';
			// print_r($response);
			// echo '</pre>';die;
			if(!$response['error']){
				$detail = $response['data'];
				$r = [
					'status'=>$detail->status,//订单状态
					'shippingNumber'=>$detail->shippingNumber,//出货轨迹号
				];
				$Arr = [
					'O'=>'待审核',
					'R'=>'已审核',
					'P'=>'待发货',
					'S'=>'已发货',
					'X'=>'已取消',
					'D'=>'已删除',
					'F'=>'已冻结',
					'Q'=>'已签收',
					'E'=>'异常',
				];
				//将返回回来的数据保存到return_no中方便以后使用
				$shipped = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number])->one();
				$shipped->return_no = $r;
				//如果物流商有转运单号返回 则更新系统内的跟踪号
				if(isset($detail->shippingNumber)&&$detail->shippingNumber)$shipped->tracking_number = $detail->shippingNumber;
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				$success = empty($detail->shippingNumber)?'物流商暂未生成跟踪号':'获取跟踪号成功,跟踪号:'.$detail->shippingNumber;
				return self::getResult(0,'', $success.'<br/>订单状态:'.$Arr[$detail->status]);
			}
			if($response['error']){

				return $response;	
			}
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

	//打印物流单
	public function doPrint($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持打印物流单');
	}

	//用来判断是否支持打印
	public static function isPrintOk(){
		return false;
	}

	//订单取消
	function cancelOrderNO($data){
		try{
			//订单对象
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			if(!is_array($info))return self::getResult(1,'',$info);
			$s = $info['service'];
			
			$order_code = $order->customer_number;
			$request = [
				'orderCode'=>$order_code
			];
			###################################
			$response = $this->sendData($s->carrier_account_id,'cancelDeliveryOrder',$request);
			// echo '<pre>';
			// print_r($response);
			// echo '</pre>';die;
			$detail = $response['data'];
			if(!$response['error']){
				$orderShippedObj = OdOrderShipped::findOne(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number]);
				$orderShippedObj->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->customer_number = '';
				$order->save();
				return self::getResult(0,'', '成功删除订单'.$detail->documentCode);
			}
			if($response['error']){

				return $response;
			}
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

	//重新发货
	function Recreate($data){
		return self::getResult(1,'','该物流商暂不支持重新发货,如有需要,请在订单取消后,点击上传订单修改标识号重新上传');
	}

	/**
	 * 获取海外仓库存列表
	 * 
	 * @param 
	 * 			$data['accountid'] 			表示账号小老板对应的账号id
	 * 			$data['warehouse_code']		表示需要的仓库ID
	 * @return 
	 */
	function getOverseasWarehouseStockList($data){
// 		$data['accountid'] = '95';
// 		$data['warehouse_code'] = 'USLA';
		
		$request = [
		'warehouseCode'=>$data['warehouse_code']
		];
		$response = $this->sendData($data['accountid'],'getInventory',$request, 'item');
		
// 		print_r($response);

		$resultStockList = array();
		
		if($response['error'] == 0){
			foreach ($response['data'] as $valList){
				$resultStockList[$valList->sku] = array(
						'sku'=>$valList->sku,
						'productName'=>'',
						'stock_actual'=>$valList->actualQuantity,				//实际库存
						'stock_reserved'=>$valList->shippingQuantity,	//占用库存
						'stock_pipeline'=>$valList->pendingQuantity,	//在途库存
						'stock_usable'=>$valList->availableQuantity,	//可用库存
						'warehouse_code'=>$data['warehouse_code']		//仓库代码
				);
			}
		}
		
		return self::getResult(0, $resultStockList ,'');
	}


	/**
	 * 发送类
	 * 	 $sendXmlArr : 数据 数组 ,会被组织成 Xml
	 * 	 	如果 本来就是 字符串的 ,就认为本来就是  Xml
	 * 	 $returnXml : 返回 值是 数组 或 原生 xml .默认 是 数组
	 */
	function sendHttpRequest($sendXmlArr,$isreturnXml=0,$method='post',$url){
		// 1,body 部分
		if(is_array($sendXmlArr)){
			$requestBody=self::simpleArr2xml($sendXmlArr);
		}elseif(is_string($sendXmlArr)){
			$requestBody=$sendXmlArr;
		}else{
			$response = Array
				(
						'CallSuccess' => 'false',
						'Response' => Array
						(
								'Success' => 'false',
								'ReasonMessage' => '小老板内部错误，提交数据格式不对'
						)
				
				);
			return $response;
		}
		$headers = array (
				'Authorization: basic ',
				'Content-Type: application/json; charset=utf-8',
		);
		// echo '<pre>';
		// print_r($this->serverUrl);
		\Yii::info('4px_oversea,requestBody:'.$requestBody, "file");
		\Yii::info('4px_oversea,requestURL: \n URL:'.$url, "file");
		// print_r($requestBody);
		// print_r($headers);
		// echo '</pre>';
		try {

			$response = Helper_Curl::$method($url,$requestBody,$headers,false);
		}catch (\Exception $ex){
			$response = Array
			(
					'CallSuccess' => 'false',
					'Response' => Array
					(
							'Success' => 'false',
							'ReasonMessage' => $ex->getMessage()
					)
			
			);
			return $response;
		}
// 		\Yii::log(print_r($response,true));
		//返回 数组
		if($isreturnXml){
			return $response;
		}else{
			$responseobj = simplexml_load_string($response);
			//return self::obj2ar($responseobj);
		}
	}
	
	static function simpleArr2xml($arr,$header=1){
		if($header){
			$str='<?xml version="1.0" encoding="utf-8" ?>';
		}else{
			$str='';
		}
		if(is_array($arr)){
			$str.="\r\n";
			foreach($arr as $k=>$v){
				$n=$k;
				if(($b=strpos($k,' '))>0){
					$f=substr($k,0,$b);
				}else{
			        $f=$k;
				}
				if(is_array($v)&&is_numeric(implode('',array_keys($v)))){
				// 就是为 Array 为适应 Xml 的可以同时有多个键 所做的 变通
					foreach($v as $cv){
					$str.="<$n>".self::simpleArr2xml($cv,0)."</$f>\r\n";
					}
				}elseif ($v instanceof SimpleXMLElement ){
					$xml = $v->asXML();/*<?xml version="1.0"?>*/
					$xml =preg_replace('/\<\?xml(.*?)\?\>/is','',$xml);
					$str.=$xml;
				}else{
						$str.="<$n>".self::simpleArr2xml($v,0)."</$f>\r\n";
				}
			}
		}else{
			$str.=$arr;
		}
		return $str;
	}

	function getCode2Err($code){
		$arr = [
			0=>'物流商返回数据错误',
			0001=>'解析消息失败,消息信息不完整.',
			0002=>'部分数据不存在,未更新.',
			0003=>'请求数据不完整.',
			0004=>'数据不是XML格式.',
			0005=>'不支持的数据格式',
			0006=>'数据格式转换异常.',
			0007=>'数据绑定异常.',
			0008=>'数据已存在.',
			0009=>'日期格式不正确',
			0051=>'没有访问API的权限',
			0052=>'不支持的语言类型.',
			0053=>'用户信息不存在.',
			0054=>'用户名或密码不正确',
			0055=>'客户ID或TOKEN有误',
			0056=>'token已失效',
			0057=>'访问频率过高',
			0101=>'IO操作异常',
			0102=>'请求连接异常',
			0103=>'业务系统内部异常',
			0104=>'数据库操作异常',
			100101=>'SKU支持数字、字母、中横杆、下横杆、点',
			100102=>'货品长、宽、高不能为空且在0.01-9999.99范围内最多保留2位小数',
			100103=>'申报价值最大5位数字且最多保留2位小数',
			100104=>'申报名称不能为空且长度不能大于100位',
			100105=>'货品类目只能为第二级或者第三级',
			100106=>'货品编号已存在',
			100201=>'仓库不能为空',
			100202=>'仓库不能识别',
			100203=>'入库订单信息不能为空',
			100204=>'派送方式不能为空',
			100205=>'关税类型不能为空',
			100206=>'报关类型不能为空',
			100207=>'货品不存在或未通过审核',
			100208=>'组合货品，不能做入库预报',
			100209=>'货品信息不能为空',
			100210=>'货品数量不能为空',
			100211=>'货品数量必须大于0',
			100212=>'货品数量不能大于100000',
			100213=>'货品数量须为1-10000的整数',
			100214=>'箱号不能为空',
			100215=>'箱号须为1-10000的整数',
			100216=>'货品包装类型不能为空',
			100217=>'包装类型不能识别',
			100218=>'不能使用该包装类型',
			100219=>'德国仓货品包装类型不能选自带包装',
			100220=>'货物类型不能为空',
			100221=>'货物类型不能识别',
			100222=>'有效期不能为空',
			100223=>'有效期必须在当天之后',
			100224=>'有效期格式有误，例如：2004-4-1',
			100225=>'申报价值不能为空',
			100226=>'该SKU所属种类的最低申报价值为空,请联系业务员进行维护',
			100227=>'货品申报价值不能小于其所属类型的最低申报价值',
			100228=>'保险类型不能为空',
			100229=>'货品总价超过1000RMB不提供低值险服务',
			100230=>'备注信息的长度不能大于128位',
			100301=>'订单不能为空',
			100302=>'仓库不能为空',
			100303=>'仓库不能识别',
			100304=>'派送方式不能为空',
			100305=>'派送方式不能识别',
			100306=>'保险类型不能为空',
			100307=>'保险类型不能识别',
			100308=>'参考号不能为空',
			100309=>'姓名不能为空',
			100310=>'国家不能为空',
			100311=>'收件人国家不能识别',
			100312=>'街道不能为空',
			100313=>'参考号的长度不能大于20位',
			100314=>'姓名的长度不能大于128位',
			100315=>'电话的长度不能大于128位',
			100316=>'Email的长度不能大于128位',
			100317=>'洲/省的长度不能大于60位',
			100318=>'城市的长度不能大于60位',
			100319=>'街道的长度不能大于240位',
			100320=>'收件人街道头尾信息中不能包含门牌号信息',
			100321=>'公司的长度不能大于128位',
			100322=>'邮编的长度不能大于10位',
			100323=>'备注过长',
			100324=>'销售交易号的长度不能大于20位',
			100325=>'Email格式有误，请输入正确的 Email',
			100326=>'货品不能为空',
			100327=>'产品编号有误',
			100328=>'货品未通过审核',
			100329=>'数量有误，数量必须是正整数',
			100330=>'该派送方式不支持该国家',
			100331=>'库存不足',
			100332=>'派送方式不存在',
			100333=>'订单为ODA订单，则客户选择不进',
			100334=>'参考号不能重复(规则:同一销售平台下的参考号不能重复)',
			100335=>'街道不能包含P.O.BOX，PO.BOX，PO BOX(不区分大小写)，并且不能大于128位',
			100336=>'街道不能包含Packstation，Postfach(不区分大小写)，并且不能大于128位',
			100337=>'街道不能包含Postfach(不区分大小写)，并且不能大于128位',
			100338=>'州/省只能为NSW,VIC,QLD,ACT,WA,SA,TAS,NT',
			100339=>'州/省不能包含P.O.BOX，PO.BOX，PO BOX(不区分大小写)，并且不能大于64位',
			100340=>'州/省不能包含Packstation，Postfach(不区分大小写)，并且不能大于64位',
			100341=>'州/省不能包含Postfach(不区分大小写)，并且不能大于64位',
			100342=>'邮编格式不正确',
			100343=>'邮编格式只能为：00000 或者 00000-0000 (0代表数字)',
			100344=>'邮编格式只能为：0000 (0代表数字)',
			100345=>'邮编格式只能为：00000 (0代表数字)',
			100346=>'收件人名称只能由字母和空格组成',
			100347=>'门牌号不能填写',
			100348=>'门牌号不能为空',
			100349=>'公司名字不能填写',
			100350=>'城市不能包含P.O.BOX，PO.BOX，PO BOX(不区分大小写)，并且不能大于64位',
			100351=>'城市不能包含Packstation，Postfach(不区分大小写)，并且不能大于64位',
			100352=>'城市不能包含Postfach(不区分大小写)，并且不能大于64位',
			100353=>'城市的长度不能大于64位',
			100354=>'联系电话不能为空',
			100355=>'库存不足',
			100356=>'订单不能为空',
			100357=>'只能修改“异常”状态的订单',
			100358=>'街道信息只能有英文字母、数字、空格、点组成',
			100359=>'货物类型不匹配',
			100360=>'订单超重',
			100361=>'渠道不支持此订单邮编的派送服务',
			100362=>'邮编不能为空',
			100363=>'服务器异常',
			100364=>'该订单状态不支持该操作',
		];
		if(isset($arr[$code]))return $arr[$code];
		return false;
	}
	
	function getOrderNOTest()
	{
		try{
			$request = [
				'warehouseCode'=>'UKLH',//仓库代码
				'referenceCode'=>'LB0000001673',//订单参考号
				'carrierCode'=>'RMTRACK24',//渠道代码
				'insureType'=>'',//保险类型代码
				// 'sellCode'=>,//销售交易号
				'description'=>'',//描述
				'insureMoney'=>'',//保险金额
				'platformCode'=>'',//平台代码
			];
			$consignee = [
				"state"=>'',//州
				"fullName"=> 'Ashna Mangnoesing',//收件人姓名
				"email"=> '',
				"countryCode"=> 'GB',
				"street"=> 'Lavendeltuin 8',
				"city"=> 'Zoetermeer',
				"postalCode"=> 'CF40 2BY',
				"phone"=> '0641942749',
				"company"=> '',
				"doorplate"=>'',
			];
			//组织商品信息

			$items[] = [
				'sku'=>'TEST06',
				'quantity'=>1,
			];
		
			$request['consignee'] = $consignee;
			$request['items'] = $items;
	
			$operate = 'createDeliveryOrder';
			$api_params['customerId'] = '100800';//test user
			$api_params['token'] = 'oDuCfVi88b40oOuMYQUOcTh2b/T+uJdDBsJ+VOrlG6Q=1';//test token
			$params = '?customerId='.$api_params['customerId'].'&token='.$api_params['token'].'&language=en_US';
			//拼接url
			//$url = $this->url.'/api/service/woms/order/'.$operate.$params;
			$url = 'http://apisandbox.4px.com'.'/api/service/woms/order/'.$operate.$params;
			$request = json_encode($request);
			$response = $this->sendHttpRequest($request,1,'post',$url);
			//对返回数据进行处理
			$result = $this->getOutput($response);
			
			return $result;
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}
	function getInventory(){
		$operate = 'getInventory';

		$api_params['customerId'] = '100800';//test user
		$api_params['token'] = 'oDuCfVi88b40oOuMYQUOcTh2b/T+uJdDBsJ+VOrlG6Q=1';//test token
		
		$params = '?customerId='.$api_params['customerId'].'&token='.$api_params['token'].'&language=en_US';
		//拼接url
		//'http://apisandbox.4px.com'
		$url = $this->url.'/api/service/woms/item/'.$operate.$params;
		echo "<br>".$url;
		
		//$request['lstSku'] = 'PR-1288_B';
		$request['warehouseCode'] = 'UKLH';
		$request = json_encode($request);
		$response = $this->sendHttpRequest($request,1,'post',$url);

		return $response;
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
	function getVerifyCarrierAccountInformation($data){
		$result = array('is_support'=>1,'error'=>0,'msg'=>'');
	
		try{
			$operate = 'getInventory';
			
			$api_params['customerId'] = $data['customerId'];
			$api_params['token'] = $data['token'];

			$params = '?customerId='.$api_params['customerId'].'&token='.$api_params['token'].'&language=en_US';
			//拼接url
			$url = $this->url.'/api/service/woms/item/'.$operate.$params;
			//echo "<br>".$url;
			
			$request['warehouseCode'] = 'UKLH';
			$request = json_encode($request);
			$response = $this->sendHttpRequest($request,1,'post',$url);
			
			if(stripos($response,'<errorCode>0055</errorCode>')){
				$result['error'] = 1;
				$result['msg'] = '验证失败!';
			}
		}catch(CarrierException $e){
			$result['error'] = 1;
			$result['msg'] = $e->msg();
		}
	
		return $result;
	}
	
	function getCarrierCode($warehouseCode='4PHK'){
		$operate = 'getOrderCarrier';
	
		$api_params['customerId'] = '100800';//test user
		$api_params['token'] = 'oDuCfVi88b40oOuMYQUOcTh2b/T+uJdDBsJ+VOrlG6Q=1';//test token
	
		$params = '?customerId='.$api_params['customerId'].'&token='.$api_params['token'].'&language=en_US';
		//拼接url
		$url = 'http://apisandbox.4px.com/api/service/woms/order/'.$operate.$params;
		//$url = $this->url.'/api/service/woms/order/'.$operate.$params;
		echo "<br>".$url;
	
		//$request['lstSku'] = 'PR-1288_B';
		
		$request['warehouseCode'] = $warehouseCode;
		$request = json_encode($request);
		$response = $this->sendHttpRequest($request,1,'post',$url);
	
		return $response;
	}
	
}