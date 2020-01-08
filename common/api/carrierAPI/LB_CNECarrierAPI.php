<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-05-18
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\Helper_Curl;
use eagle\models\SysCountry;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierException;

/**
 +------------------------------------------------------------------------------
 * CNE物流接口对接
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		common\api\carrierAPI
 * @subpackage  Exception
 * @author		qfl <772821140@qq.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_CNECarrierAPI extends BaseCarrierAPI
{
	public static $requestName = null ;
	public static $timestamp = null;
	//*测试ID:71，密钥：12345ABCDE
	//接口API为：“http://api.cne.com/cgi-bin/EmsData.dll?DoApi”
	//TimeStamp：时间戳，1970.1.1 0:0:0开始到请求时刻的毫秒数(UTC),13位整数
	//MD5：数字签名，=MD5(icID+TimeStamp+客户密钥)
    //*比如客户密钥为“12345ABCDE”,如上例程为：MD5("71140553442112312345ABCDE")="efd49dfb22292d6c42c0941f08cc4717"
	public function __construct(){
		//获取时间戳
		$request = ["RequestName"=>"TimeStamp"];
		$response = Helper_Curl::post('http://api.cne.com/cgi-bin/EmsData.dll?DoApi',json_encode($request));
		$response = json_decode($response);
		self::$timestamp = $response->ReturnValue;
	}

	//获取运输方式代码
	function getCarrierService(){
		$request = [
			'RequestName'=>'EmsKindList',
			'icID'=>71,
			'TimeStamp'=>self::$timestamp,
		];
		
		
		$md5 = md5($request['icID'].$request['TimeStamp'].'c3c04c24d13d0267043128103ceedb3f');
		$request['MD5'] = $md5;
		
		$headers = [];
		$headers[] = 'Content-type: application/json;charset=utf-8';
		$headers[] = 'Accept: application/json';
		echo json_encode($request).PHP_EOL;
		$response = Helper_Curl::post('http://api.cne.com/cgi-bin/EmsData.dll?DoApi',json_encode($request), $headers);
		var_dump($response);
		exit;
		$response = json_decode($response);
		$str = '';
		foreach($response->List as $v){
			$str .= $v->oName.':'.$v->oName.';';
		}
		echo $str;die;
	}
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param $data		包裹及物流商相关参数(多个包裹)
	 +----------------------------------------------------------
	 * @return	apiResult 
	 * array(
	 *	'key为shipping_method' => array(
	 *		'key为delivery_id' => self::getResult(0, '此处为获取的tracking_no', ''),
	 *		'key为delivery_id' => self::getResult(非0的errorCode, '', '错误信息')
	 *	)
	 * )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		qfl		2015/05/18				初始化
	 +----------------------------------------------------------
	**/
	public function getOrderNO($data){
		try{
		    $user=\Yii::$app->user->identity;
		    if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
		    $puid = $user->getParentUid();
			// $this->getCarrierService();//获取运输方式的代码** 有需要的时候再打开
			// $this->getLabelStyle();//获取打印标签样式代码** 有需要的时候再打开

			//odOrder表内容
		    $PrintParam = array();
		    $PrintParam = ['label10x10_1','label10x10_0'];//10*10打印格式
		    //订单对应平台， 必填项
		    $orderPlatform = array();
		    $orderPlatform = ['WISH','EBAY','ALIEXPRESS','AMAZON','DHGATE','JD','CDISCOUNT','LAZADA','TOPHATTER','JOOM','SHOPEE','MAGENTO','SHOPIFY','1688'];
			$order = $data['order'];
			
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

			//获取到所需要使用的数据
			$e  = $data['data'];
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			
			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			
			$a = $account->api_params;
			###################################################################################
			$carrier_params = $service->carrier_params;
			$address = $info['senderAddressInfo'];
			$shippingFrom = $address['shippingfrom'];
			//获取目的地中文名
			$country_cn = SysCountry::findOne($order->consignee_country_code);
			$cDes = $country_cn?$country_cn->country_zh:'';
			
// 			$extra_id = isset($data['extra_id'])?$data['extra_id']:'';
// 			$customer_number = CarrierAPIHelper::getCustomerNum($order,$extra_id);
				
			//法国没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
			    $tmpConsigneeProvince = $order->consignee_city;
			}
			
			//amazon 日本站订单城市为空时直接用省份代替
			$tmp_consignee_city = $order->consignee_city;
			if(empty($tmp_consignee_city) && ($order->consignee_country_code == 'JP')){
				$tmp_consignee_city = $tmpConsigneeProvince;
			}
			
			$addressAndPhoneParams = array(
			    'address' => array(
			        'consignee_address_line1_limit' => 254,
			    ),
			    'consignee_district' => 1,
			    'consignee_county' => 1,
			    'consignee_company' => 0,
			    'consignee_phone_limit' => 63
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$request = [
				'iID'=>0,//0 新增 1 修改 必须为第一个
				'nItemType'=>1,//快件类型 0 文件 1 包裹 2防水袋
			    //客户参考号
			    'cRNo'=>$customer_number,
			    'cDes'=>$cDes,//目的地 中文
			    'cEmsKind'=>$service->shipping_method_code,
			    'fWeight'=>'',
				// 'cEmsKind'=>$carrier_params['shippingService'],//快递类型
				// 'fAmount'=>,//快递费

				//收件人信息
				'cReceiver'=>$order->consignee,
			    'cRPhone'=>$addressAndPhone['phone1'],
			    'cREMail'=>$order->consignee_email,
			    'cRPostcode'=>$order->consignee_postal_code,
			    'cRCountry'=>$order->consignee_country,
			    'cRProvince'=>$tmpConsigneeProvince,
			    'cRCity'=>$tmp_consignee_city,
			    'cRAddr'=>$addressAndPhone['address_line1'],//收件人地址
				'cRUnit'=>$order->consignee_company,
			    
			    'nPayWay'=>$carrier_params['nPayWay'],//付款方式
			    
				'cAddrFrom'=>in_array(strtoupper($order->order_source), $orderPlatform)?strtoupper($order->order_source):'ORTHER',//订单对应平台， 必填项
				//发件人信息
				'cSender'=>$shippingFrom['contact'],
				'cSUnit'=>$shippingFrom['company'],
				'cSAddr'=>$shippingFrom['district'].$shippingFrom['street'],
				'cSCity'=>$shippingFrom['city'],
				'cSPostcode'=>$shippingFrom['postcode'],
				'cSProvince'=>$shippingFrom['province'],
				'cSCountry'=>$shippingFrom['country'],
				'cSPhone'=>strlen($shippingFrom['phone'])>4?$shippingFrom['phone']:$shippingFrom['mobile'],
				'cSEMail'=>$shippingFrom['email'],

				'cMemo'=>$e['memo'],//备注信息
				'cReserve'=>'小老板ERP',

				'cMoney'=>'USD',//货币代码，0-3字符。
// 			    'cMoney'=>empty($e['Currency'])?'USD':$e['Currency'],
				'fIValue'=>$e['fIValue'],//物品投保价，2位小数。

				'cOrigin'=>'CN',//原产地国家代码，0-3字符。
			];
			$total_weight = 0;
			$total_price = 0;
			foreach($order->items as $k=>$v){
			    if(mb_strlen($e['Name'][$k],'utf-8')>31){
			        return self::getResult(1,'','中文报关名不能超过31');
			    }
			    if(empty($e['Name'][$k])){
			        return self::getResult(1,'','中文报关名不能为空');
			    }
			    if(strlen($e['EName'][$k])>63){
			        return self::getResult(1,'','英文报关名不能超过63');
			    }
			    if(empty($e['EName'][$k])){
			        return self::getResult(1,'','英文报关名不能为空');
			    }
			    if(empty($e['DeclaredValue'][$k])){
			        return self::getResult(1,'','报关价值不能空');
			    }
			    if(empty($e['DeclarePieces'][$k])){
			        return self::getResult(1,'','商品数量不能空');
			    }if(empty($e['weight'][$k])){
			        return self::getResult(1,'','报关重量不能为空');
			    }
				$goods[] = [
					'cxGoods'=>$e['EName'][$k].'-'.$e['Name'][$k],//物品描述,0-63字符。必须。
					'ixQuantity'=>intval($e['DeclarePieces'][$k]),//物品数量。必须。
// 					'fxPrice'=>intval($e['DeclaredValue'][$k]),//物品单价，2位小数。
				    'fxPrice'=>number_format((float)$e['DeclaredValue'][$k],2),//物品单价，2位小数。
					'cxGoodsA'=>$e['EName'][$k],//物品英文描述,0-63字符。
					'cxGCodeA'=>$v->sku,//物品SKU,0-63字符。
					//新增字段
					'cxPhoto'=>!empty($e['photoLink'][$k])?$e['photoLink'][$k]:'',//产品图片链接
				    'cxLink'=>!empty($e['productLink'][$k])?$e['productLink'][$k]:'',//产品链接
				    'cxTrueprice'=>!empty($e['realtPrice'][$k])?$e['realtPrice'][$k]:'',//真实价格
				    'cxMoney'=>$order->currency,//成交币种
				    'cxOrigin'=>!empty($e['originNation'][$k])?$e['originNation'][$k]:'CN',//原产地国家代码
				];
				$total_weight += $e['weight'][$k]*intval($e['DeclarePieces'][$k]);
				$total_price += $e['DeclaredValue'][$k]*intval($e['DeclarePieces'][$k]);
			}
			$request['GoodsList'] = $goods;
			$request['fWeight'] = $total_weight/1000;//重量 公斤
			$request['fDValue'] = $total_price;
			$lastRequest[] = $request;
			###################################################################################
			self::$requestName = 'PreInputSet';
			$response = self::getResponse(
			    $puid,
				$a['userkey'],
				$a['token'],
				'http://api.cne.com/cgi-bin/EmsData.dll?DoApi',
				$lastRequest,
				$service->shipping_method_code
			);
			
			if(!isset($response->OK)){
			    if(isset($response->cMess)&&!empty($response->cMess)){
			        throw new CarrierException($response->cMess);
			    }else if(isset($response->ReturnValue)){
    		         $err_message = $this->getReturnValue($response->ReturnValue);
    			     throw new CarrierException($err_message);
			    }else{
			         throw new CarrierException("操作失败:请检查订单数据是否完整");
			    }
			}else{
				//成功
				$errlist = $response->ErrList;
				$detail = $errlist[0];
                if($detail->iID > 0 &&empty($detail->cMess)){
                    //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
                    $r = CarrierAPIHelper::orderSuccess($order,$service,$detail->cNo,OdOrder::CARRIER_WAITING_PRINT,$detail->cNo);
                    
//                     if(in_array($carrier_params['labelstyle'], $PrintParam)){
                        $print_param = array();
                        $print_param['carrier_code'] = $service->carrier_code;
                        $print_param['api_class'] = 'LB_CNECarrierAPI';
                        $print_param['userkey'] = $a['userkey'];
                        $print_param['token'] = $a['token'];
                        $print_param['cNum'] = $detail->cNo;
                        $print_param['carrier_params'] = $service->carrier_params;
                    
                        try{
                    
                            CarrierAPIHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $detail->cNo, $print_param);
                        }catch (\Exception $ex){
                        }
//                     }
                    
                    return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$detail->cNo);
                }else{
                    //失败
                    return self::getResult(1,'','操作失败:'.$detail->cMess);
                }
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	public static function getLabelStyle(){
		//组织请求数据
		$signature = md5('71'.self::$timestamp.'c3c04c24d13d0267043128103ceedb3f');
		$url = 'http://label.cne.com/cnePrint/getType?timestamp='.self::$timestamp.'&icID=71&signature='.$signature;

		$headers = [];
		$headers[] = 'Content-type: application/json;charset=utf-8';
		$headers[] = 'Accept: application/json';
		
		$response = Helper_Curl::get($url, array(), $headers);
		$response = json_decode($response);
		$str = '';
		foreach($response as $v){
			$str .= $v->value.':'.$v->name.';';
		}
		echo $str;die;
	}



	/*
	 * 发送请求数据 获得返回结果进行处理
	 */
	public static function getResponse($puid,$id,$token,$url,$data,$cEmsKind=null){
		//组织请求数据
		$request = [
			'RequestName'=>self::$requestName,
			'icID'=>$id,
			'TimeStamp'=>self::$timestamp,
		    'MD5'=>'',
			'RecList'=>$data,
		];
		$md5 = md5($request['icID'].$request['TimeStamp'].$token);
		$request['MD5'] = $md5;
// 		if($cEmsKind)$request['cEmsKind'] = $cEmsKind;
		// var_dump($request);die;
		$header=array();
		$header[]='Content-Type:application/json;charset=utf-8';
		
		$response = Helper_Curl::post($url, json_encode($request), $header);
		\Yii::info('CNE,puid:'.$puid.',result,userKey:'.$id.' '.json_encode($request),"carrier_api");
		\Yii::info('CNE response,puid:'.$puid.',result,userKey:'.$id.' '.$response,"carrier_api");
		$response = json_decode($response);
		return $response;
	}

	/**
	 * 取消跟踪号
	 */
	public function cancelOrderNO($data){
		return self::getResult(1, '', '结果：该物流商API不支持取消订单功能');
	}

	/**
	 * 交运
	 */
	public function doDispatch($data){
		return self::getResult(1, '', '结果：该物流商API不支持交运订单功能');
	}

	/*
	 * 获取跟踪号
	 */
	public function getTrackingNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = $data['order'];
			$params = '&cno='.$order->customer_number;
			$url = 'http://track.api.cne.com/cgi-bin/GInfo.dll?EmsTrackState'.$params;
			$response = Helper_Curl::get($url);
			$status = $this->getOrderStatus($response);
			if($status){
				if(\Yii::$app->session['super_login'] == 1){
					$info = CarrierAPIHelper::getAllInfo($order);
					$service = $info['service'];
					$account = $info['account'];
					$a = $account->api_params;
					
					$print_param = array();
					$print_param['carrier_code'] = $service->carrier_code;
					$print_param['api_class'] = 'LB_CNECarrierAPI';
					$print_param['userkey'] = $a['userkey'];
					$print_param['token'] = $a['token'];
					$print_param['cNum'] = $order->customer_number;
					$print_param['carrier_params'] = $service->carrier_params;
					
					try{
						CarrierAPIHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
					}catch (\Exception $ex){
					}
				}
// 				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return self::getResult(0,'','订单状态:'.$status);
			}
			else {
				return self::getResult(1,'','物流商返回数据错误');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	/*
	 * 获取订单状态信息
	 */
	public function getOrderStatus($key){
		$arr = [
			-9=>'数据库内部错误',
			-5=>'服务器版本类型不支持',
			-4=>'系统未获有效注册',
			-3=>'运单号长度不对(8-30)',
			-2=>'没有查询结果',
			0=>'未发送',
			1=>'已发送',
			2=>'转运中',
			3=>'送达',
			4=>'超时',
			5=>'扣关',
			6=>'地址错误',
			7=>'快件丢失',
			8=>'退件',
			9=>'其它异常',
			10=>'销毁',
		];
		
		if(empty($key)){
			return false;
		}
		
		if (!is_numeric($key)){
			return false;
		}
		
		if(array_key_exists($key,$arr))return $arr[$key];
		else return false;
	}
	/*
	 * 获取查看轨迹返回状态信息
	 */
	public function getTrackStatus($key){
	    $arr = [
	        -9=>'系统错误',
	        -102=>'运单不存在'
	    ];
	    if(array_key_exists($key,$arr))return $arr[$key];
	    else return "物流商返回数据错误";
	}
	/*
	 * 获取创建订单的状态信息
	 */
	public function getReturnValue($key){
	    $arr = [
	        -9=>'API接口程序错误，通常为数据库查询失败',
	        -8=>'未获授权',
	        -7=>'安全校验失败，不是配置的IP或数字签名错误',
	        -4=>'请求不支持，版本错误或请求未实现',
	        -3=>'未提供必须的请求参数，操作失败',
	        -2=>'记录不存在，操作失败',
	        -1=>'唯一性字段值重复，操作失败',
	        -710=>'认证信息错误（icID 错误，未提供或小于1）',
	        -711=>'认证信息错误（icID 错误，客户不存在）',
	        -720=>'认证信息错误（时间戳错误，超出了同步阈值）',
	        -730=>'认证信息错误（数字签名错误，长度不是32字符）',
	        -731=>'认证信息错误（数字签名错误，不匹配）'
	    ];
	    if(array_key_exists($key,$arr))return $arr[$key];
	    else return "操作失败:物流商未知错误";
	}
	//用来判断是否支持打印
	public static function isPrintOk(){
		return true;
	}

	/**
	 * 打单
	 */
	public function doPrint($data){
		try{
			$cNums = '';
			foreach ($data as $k=>$v) {
				$order = $v['order'];

				$user=\Yii::$app->user->identity;
				if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
				$puid = $user->getParentUid();

				if($k==0){
					$info = CarrierAPIHelper::getAllInfo($order);
					$service = $info['service'];
					$account = $info['account'];
					$a = $account->api_params;
					$carrier_params = $service->carrier_params;
				}
				if($order->customer_number)$cNums .= $order->customer_number.',';
				//CNE无法判断是否打印成功 所以只要调用 默认认为成功 保存信息 进行下一步
// 				$order->is_print_carrier = 1;
				$order->print_carrier_operator = $puid;
				$order->printtime = time();
				$order->save();
			}
			if(empty($cNums))throw new CarrierException('操作失败,订单不存在');
			$cNums = rtrim($cNums,',');
			$md5 = md5($a['userkey'].$cNums.$a['token']);
			$labelstyle = empty($carrier_params['labelstyle'])?'LabelA46_1':$carrier_params['labelstyle'];
			$url = 'http://label.cne.com/CnePrint?icID='.$a['userkey'].'&cNos='.$cNums.'&ptemp='.$labelstyle.'&signature='.$md5;
			###############################################################################
			//CNE的订单打印比较特殊 打开他们的下载页面就可以
// 			return self::getResult(0,['pdfUrl'=>$url],'连接已生成,请点击并打印');
			
			$response = Helper_Curl::get($url);
			if($response === false)
				return self::getResult(1,'','CNE返回打印超时！');
			
			if(strlen($response)<1000){
				return self::getResult(1, '', $response);
			}
			
			$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$order->customer_number, 0);
			
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			
			
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
	
	        $cNums = '';
	        
	        $cNums = $print_param['cNum'];
	        if(empty($cNums))throw new CarrierException('操作失败,订单不存在');
	        
	        $md5 = md5($print_param['userkey'].$cNums.$print_param['token']);
	        
	        $normal_params = empty($print_param['carrier_params']) ? array() : $print_param['carrier_params']; ////获取打印方式
	        
	        $labelstyle = empty($normal_params['labelstyle'])?'label10x10_1':$normal_params['labelstyle'];
	        
	        if($labelstyle == 'labelA46_1'){
	        	$labelstyle = 'label10x10_1';
	        }
	        
	        if($labelstyle == 'labelA46_0'){
	        	$labelstyle = 'label10x10_0';
	        }
	        
	        $url = 'http://label.cne.com/CnePrint?icID='.$print_param['userkey'].'&cNos='.$cNums.'&ptemp='.$labelstyle.'&signature='.$md5;
	        ###############################################################################
	        //CNE的订单打印比较特殊 打开他们的下载页面就可以
	        $response = Helper_Curl::get($url);
	        if (strlen($response)>1000){
	            $pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
	            return $pdfPath;
	        }else{
	            return ['error'=>1, 'msg'=>'打印失败,请检查订单后重试', 'filePath'=>''];
	        }
	         
	    }catch (CarrierException $e){
	        return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
	    }
	}

	/**
	 * 专用的xml转数组
	 * @param 	string 	CNE物流号
	 * @return  array
	 */
	function xml_to_array( $xml )
	{
	    $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
	    if(preg_match_all($reg, $xml, $matches))
	    {
	        $count = count($matches[0]);
	        $arr = array();
	        for($i = 0; $i < $count; $i++)
	        {
	            $key= $matches[1][$i];
	            $val = self::xml_to_array( $matches[2][$i] );  // 递归
	            if(array_key_exists($key, $arr))
	            {
	                if(is_array($arr[$key]))
	                {
	                    if(!array_key_exists(0,$arr[$key]))
	                    {
	                        $arr[$key] = array($arr[$key]);
	                    }
	                }else{
	                    $arr[$key] = array($arr[$key]);
	                }
	                $arr[$key][] = $val;
	            }else{
	                $arr[$key] = $val;
	            }
	        }
	        return $arr;
	    }else{
	        return $xml;
	    }
	}
	/**
	 * 查询物流轨迹
	 * @param 	string 	CNE物流号
	 * @return  array
	 */
	public function SyncStatus($data){
	    $res = array();
	
	    $url = 'http://track.api.cne.com/cgi-bin/GInfo.dll?EmsApiTrack&cno='.$data[0];
	    $response = Helper_Curl::get($url);
	    $xml_array = self::xml_to_array($response);
	    if (!is_array($xml_array)) {
	        $res['error'] = "1";
	        $res['trackContent'] = $this->getTrackStatus($xml_array);
	        $res['referenceNumber']=$data;
	    }else{
	        if(!empty($xml_array['EMS_INFO'])&&!empty($xml_array['TRACK_DATA'])){
	            $res['error']="0";
	            $res['referenceNumber'] = $xml_array['EMS_INFO']['REFER_NBR'];
	            $res['destinationCountryCode'] = $xml_array['EMS_INFO']['DES'];
	            $res['trackingNumber'] = $xml_array['EMS_INFO']['TRCKING_NBR'];
	            $res['trackContent']=[];
	            if(!empty($xml_array['TRACK_DATA']['DATETIME'])){//判断是否有轨迹数据
	                if(is_array($xml_array['TRACK_DATA']['DATETIME'])){//多个轨迹
	                    $trackInfo = $xml_array['TRACK_DATA']['DATETIME'];
	                    foreach($trackInfo as $i=>$t){
	                        $res['trackContent'][$i]['createDate'] = '';
	                        $res['trackContent'][$i]['createPerson'] = '';
	                        $res['trackContent'][$i]['occurAddress'] = empty($xml_array['TRACK_DATA']['PLACE'][$i])?'':$xml_array['TRACK_DATA']['PLACE'][$i];
	                        $res['trackContent'][$i]['occurDate'] = empty($t)?'':$t;
	                        $res['trackContent'][$i]['trackCode'] = '';
	                        $res['trackContent'][$i]['trackContent'] = empty($xml_array['TRACK_DATA']['INFO'][$i])?'':$xml_array['TRACK_DATA']['INFO'][$i];
	                    }
	                    $res['trackContent'] = array_reverse($res['trackContent']);//时间倒序
	                }else{//单个轨迹
	                    $res['trackContent'][0]['createDate'] = '';
	                    $res['trackContent'][0]['createPerson'] = '';
	                    $res['trackContent'][0]['occurAddress'] = empty($xml_array['TRACK_DATA']['PLACE'])?'':$xml_array['TRACK_DATA']['PLACE'];
	                    $res['trackContent'][0]['occurDate'] = empty($xml_array['TRACK_DATA']['DATETIME'])?'':$xml_array['TRACK_DATA']['DATETIME'];
	                    $res['trackContent'][0]['trackCode'] = '';
	                    $res['trackContent'][0]['trackContent'] = empty($xml_array['TRACK_DATA']['INFO'])?'':$xml_array['TRACK_DATA']['INFO'];
	                }
	                
	            }
	        }else{
	            $res['error'] = "1";
	            $res['trackContent'] = "查询发生错误";
    	        $res['referenceNumber'] = $data;
	        }
            
	       
	    }
	    return $res;
	}
	/*
	 * 用来确定打印完成后 订单的下一步状态
	 */
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}

	//订单重新发货
	public function Recreate($data){return self::getResult(1,'', '该物流商不支持此操作');}
	
	//获取运输方式
	public function getCarrierShippingServiceStr($account){
	    try{
	        $request = [
	            'RequestName'=>'EmsKindList',
	            'icID'=>71,
	            'TimeStamp'=>self::$timestamp,
	        ];
	        
	        $md5 = md5($request['icID'].$request['TimeStamp'].'c3c04c24d13d0267043128103ceedb3f');
	        $request['MD5'] = $md5;
	        
	        $headers = [];
	        $headers[] = 'Content-type: application/json;charset=utf-8';
            $headers[] = 'Accept: application/json';
            
	        $response = Helper_Curl::post('http://api.cne.com/cgi-bin/EmsData.dll?DoApi',json_encode($request), $headers);
	        $response = json_decode($response);
	        
	        $channelStr = "";
	        
	        // dzt20191022 cne获取服务接口不稳定，暂时放弃开启/保存 运输方式时候检测运输方式是否存在的逻辑。等cne那边稳定再重启逻辑
	        return self::getResult(0, $channelStr, '');
	        
	        foreach($response->List as $v){
	            $channelStr .= $v->oName.':'.$v->oName.';';
	        }
	        
	        if(empty($channelStr)){
	            return self::getResult(1, '', '');
	        }else{
	            return self::getResult(0, $channelStr, '');
	        }
	    }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
}
