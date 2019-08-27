<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_xml;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrderShipped;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lrq
+----------------------------------------------------------------------
| Create Date: 2016-07-11
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 顺丰海外仓物流商API
 +------------------------------------------------------------------------------
 * @category	vendors
 * @package		vendors/overseaWarehouseAPI
 * @subpackage  Exception
 * @author		lrq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_SFOverseaWarehouseAPI extends BaseOverseaWarehouseAPI
{
	static private $customer_id = '';
	static private $auth = '';
	static private $key = '';
	static private $wsdl = '';
	private $soapClient = null;

	public function __construct()
	{
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' )
		    self::$wsdl = 'http://oms-wh.sf-express.com/oms/ws/';
		else
		    self::$wsdl = 'http://ibu-oms-core.sit.sf-express.com:8080/oms/ws/';
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lrq 	  2016/07/11			初始化
	 +----------------------------------------------------------
	 **/
	function getOrderNO($data)
	{
	    try
	    {
    	    //odOrder表内容
    	    $order = $data['order'];
    	    $customer_number = $data['data']['customer_number'];
    	    $extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
    	    //用户在确认页面提交的数据
    	    $form_data = $data['data'];
    	    
    	    //对当前条件的验证，如果订单已存在，则报错，并返回当前用户Puid
    	    $checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
    	    $puid = $checkResult['data']['puid'];
    	    
    	    //获取物流商信息、运输方式信息等
    	    $info = CarrierAPIHelper::getAllInfo($order);
    	    $service = $info['service'];
    	    $account = $info['account'];
    	    $api_params = $account->api_params;
    	    
            ///获取收件地址街道
            $consigneeStreet = ''.(empty($order->consignee_address_line1) ? '' : $order->consignee_address_line1).
                (empty($order->consignee_address_line2) ? '' : $order->consignee_address_line2).
                (empty($order->consignee_address_line3) ? '' : $order->consignee_address_line3).
                (empty($order->conosignee_company) ? '' : ';'.$order->consignee_company).
                (empty($order->consignee_county) ? '' : ';'.$order->consignee_county).
                (empty($order->consignee_district) ? '' : ';'.$order->consignee_district);
            
            //平台代码
            $PlatfromcodeInfo = $this->getPlatfromcode();
            $platformCode = empty($PlatfromcodeInfo[$order->order_source]) ? 'O' : $PlatfromcodeInfo[$order->order_source];
            
            $phone = empty($order->consignee_mobile) ? $order->consignee_phone : $order->consignee_mobile;
            //当是eBay订单时，由于eBay不会把电话返回到小老板，导致电话为空，所以默认为8个0
            if(isset($order->order_source) && $order->order_source == 'ebay' && empty($phone))
            	$phone = '00000000';
            
            //当国家为美国时，州/省、城市必填
            if(isset($order->consignee_country_code) && $order->consignee_country_code == 'US')
            {
                if(empty($order->consignee_city))
                    return self::getResult(1,'','国家为美国时，城市不能为空');
                if(empty($order->consignee_province))
                	return self::getResult(1,'','国家为美国时，州/省不能为空');
            }
          
            //整理数据
            $postdata = array();
            $postdata = 
            [
                'order' => 
                [
                    'refCode' => $customer_number,//参考号
                    'countryCode' => $order->consignee_country_code,//收件人国家, 国际标准二字码或三字
                    'whCodeDest' => $service->third_party_code,//目的仓库编码
                    'shipChnlSeller' => $service->shipping_method_code,//派送方式编码
                    'platformCode' => $platformCode,//平台代码
                    'codePlatform' => $order->order_source_order_id,//平台参考号码，一般保存交易号码等唯一索引号码； 销售交易号
                ],
                'OrderConsignee' =>
                [
                    'consigneeFullname' => $order->consignee,//收件人全名
                    'companyName' => $order->consignee_company,//公司名称
                    'consigneeCity' => $order->consignee_city,//所在城市，国家为美国时，需要必录
                    'consigneeState' => $order->consignee_province,//所在省，州，国家为美国时，需要必录
                    'consigneeRegion' => $order->consignee_district,//所在地区
                    'consigneeStreet' => $consigneeStreet,//所在街道 
                    'consigneePostcode' => $order->consignee_postal_code,//邮编
                    'consigneeContactno' => $phone,//联系人手机号
                    'consigneeEmail' => $order->consignee_email,//邮箱
                ],
            ];
            
            //相同的海外仓SKU不能重复
            $Items = array();
            foreach ($order->items as $j=>$vitem)
            {
                //检测数据完整性
                if($form_data['DeclaredValue'][$j] < 0.01)
                	return ['error'=>1, 'data'=>'', 'msg'=>'申报价值必须大于0.01'];
                
                $oversea_sku = $form_data['oversea_sku'][$j];
                if(array_key_exists($oversea_sku, $Items)){
                    $Items[$oversea_sku]['itemQuantity'] = $Items[$oversea_sku]['itemQuantity'] + (empty($vitem->quantity) ? 0 : $vitem->quantity);
                }
                else{
                	$Items[$oversea_sku]=
                	[
                    	'itemCode'=>$oversea_sku,//货品编码， 或者配对的海外仓SKU
                    	'itemQuantity'=>empty($vitem->quantity) ? 0 : $vitem->quantity,//货品数量
                    	'declareValue'=>$form_data['DeclaredValue'][$j],//申报价值， 必须大于 0， 最小值为 0. 01
                    	'proAmount'=>"0",//优惠金额
                	];
                }
            }
            foreach ($Items as $vi){
                $postdata['Items'][] = $vi;
            }
            ###########################################################################
            //数据组织完毕 准备发送
            \Yii::info('LB_SFOverseaWarehouseAPI1,add,puid:'.$puid.'  '.print_r($postdata,1), "file");
            $response = $this->SendRequest('add', $postdata, null, $api_params);
            
            if($response['ack']=='ERR')
            {
            	$str ='';
            	foreach($response['code'] as $v)
            		$str .= $v.'<br/>';
            	
            	\Yii::info('LB_SFOverseaWarehouseAPI2,add, puid:'.$puid.'  '.print_r($response,1), "file");
            	throw new CarrierException($str);
            }
            else if($response['ack']=='OK')
            {
            	//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填,要传数组，传字符串无效)
            	$r = CarrierAPIHelper::orderSuccess($order,$service,$response['dohcode'],OdOrder::CARRIER_WAITING_DELIVERY);
            	return self::getResult(0,$r,'出库成功,出库单号:'.$response['dohcode']);
            }
        }
        catch(CarrierException $e)
        {
            return self::getResult(1,'',$e->msg());
        }
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单交运/审核订单
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lrq 	  2016/07/11			初始化
	 +----------------------------------------------------------
	 **/
	function doDispatch($data)
	{
	    try
	    {
	    	//odOrder表内容
	    	$order = $data['order'];
	    	$customer_number = $order->customer_number;
	    		
	    	//对当前条件的验证，如果订单不存在，则报错，并返回当前用户Puid
	    	$checkResult = CarrierAPIHelper::validate(1,1,$order);
	    	$puid = $checkResult['data']['puid'];
	    
	    	$postdata['dohCode'] = $customer_number;
	    
	    	$response = $this->SendRequest('release', $postdata, $order);
	    	
	    	if($response['ack']=='ERR')
	    	{
	    		$str ='';
	    		foreach($response['code'] as $v)
	    			$str .= $v.'<br/>';
	    		 
	    		\Yii::info('LB_SFOverseaWarehouseAPI4,release,puid:'.$puid.'  '.print_r($response,1), "file");
	    		throw new CarrierException($str);
	    	}
	    	if($response['ack']=='OK')
	    	{
	    		//订单交运 没有跟踪号返回
				$order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
				$order->save();
				return self::getResult(0,'', '结果：订单交运成功！');
	    	}
	    	return self::getResult(0,'', '网络异常，请稍后再试！error002');
	    }
	    catch(CarrierException $e)
	    {
	    	return self::getResult(1,'',$e->msg());
	    }
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取跟踪号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lrq 	  2016/07/11			初始化
	 +----------------------------------------------------------
	 **/
	function getTrackingNO($data)
	{
	    try
	    {
	    	//odOrder表内容
	    	$order = $data['order'];
	    	$customer_number = $order->customer_number;
	    	 
	    	//对当前条件的验证，如果订单不存在，则报错，并返回当前用户Puid
	    	$checkResult = CarrierAPIHelper::validate(1,1,$order);
	    	$puid = $checkResult['data']['puid'];
	    	 
	    	$postdata['dohCode'] = $customer_number;
	    	
	    	//发送请求
	    	$response = $this->SendRequest('query', $postdata, $order);
	    	
	    	if($response['ack']=='ERR')
	    	{
	    		$str ='';
	    		foreach($response['code'] as $v)
	    			$str .= $v.'<br/>';
	    
	    		\Yii::info('LB_SFOverseaWarehouseAPI,query,puid:'.$puid.'  '.print_r($response,1), "file");
	    		throw new CarrierException($str);
	    	}
	    	if($response['ack']=='OK')
	    	{
	    	    $mailno_str = empty($response['shippingCode']) ? '' : '<br/>服务商单号（物流跟踪号）:'.$response['shippingCode'];
	    	    $sfCode = empty($response['sfCode']) ? '' : "<br/>运单号（顺丰单号）：".$response['sfCode'];
	    	    $success = $sfCode.'<br/>订单状态:'.$response['orderStatusCode'].$mailno_str;
	    	    
	    	    if(!empty($response['shippingCode']))
	    	    {
					$shipped = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number])->one();
					if(empty($shipped)){
						return self::getResult(1, '', '网络异常，请稍后再试！error005');
					}
					$shipped->tracking_number = $response['shippingCode'];
					$shipped->save();
 					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					return self::getResult(0,'', $success);
	    	    }
	    	    
				//订单交运 没有跟踪号返回
				return self::getResult(1,'', "顺丰包裹 {$order->customer_number} 暂未物流跟踪号返回，请等待或联系出口易客服！".$success);
	    	}
	    	return self::getResult(0,'', '网络异常，请稍后再试！error004');
	    }
	    catch(CarrierException $e)
	    {
	    	return self::getResult(1,'',$e->msg());
	    }
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单取消
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lrq 	  2016/07/11			初始化
	 +----------------------------------------------------------
	 **/
	function cancelOrderNO($data)
	{
	    try
	    {
	    	//odOrder表内容
	    	$order = $data['order'];
	    	$customer_number = $order->customer_number;
	    	 
	    	//对当前条件的验证，如果订单不存在，则报错，并返回当前用户Puid
	    	$checkResult = CarrierAPIHelper::validate(1,1,$order);
	    	$puid = $checkResult['data']['puid'];
	    	$shipped = $checkResult['data']['shipped'];
	    	 
	    	$postdata['dohCode'] = $customer_number;
	    	 
	    	$response = $this->SendRequest('cancel', $postdata, $order);

	    	if($response['ack']=='ERR')
	    	{
	    		$str ='';
	    		foreach($response['code'] as $v)
	    			$str .= $v.'<br/>';
	    
	    		\Yii::info('LB_SFOverseaWarehouseAPI,cancel,puid:'.$puid.'  '.print_r($response,1), "file");
	    		throw new CarrierException($str);
	    	}
	    	if($response['ack']=='OK')
	    	{
	    		$shipped->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->save();
				return self::getResult(0, '', '订单已取消!时间:'.date('Ymd His',time()));
	    	}
	    }
	    catch(CarrierException $e)
	    {
	    	return self::getResult(1,'',$e->msg());
	    }
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单打印
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lrq 	  2016/07/11			初始化
	 +----------------------------------------------------------
	 **/
    function doPrint($data){
		foreach($data as $v){
			$order = $v['order'];
			$order->carrier_error = '物流接口不支持打印物流单';
			$order->save();
			$result[] = [
				'isInterfaceError'=>1,
				'物流接口不支持打印物流单',
			];
		}
		return self::getResult(1,'','该物流商不支持打印订单');
	}
	
	//用来判断是否支持打印
	public static function isPrintOk(){
		return false;
	}
	
	//重新发货
	function Recreate($data){
		return self::getResult(1,'','该物流商不支持重新发货');
	}
	
	//发送请求
	function SendRequest($method,$postdata, $order = null, $api_params = null, $serviceType = 'orderService') 
	{
		try
		{
		    //获取物流商信息、运输方式信息等
		    if(empty($api_params))
		    {
		        $info = CarrierAPIHelper::getAllInfo($order);
		        $account = $info['account'];
		        $api_params = $account->api_params;
		    }
		    //获取到帐号中的认证参数
		    self::$customer_id = $api_params['customer_id'];
		    self::$auth = $api_params['auth'];
		    self::$key = $api_params['key'];
		    if(empty(self::$customer_id) || empty(self::$auth) || empty(self::$key))throw new CarrierException('该订单所选运输方式没有分配可用物流商帐号');
		    
		    //整理请求报文xml
		    $params = $this->arrayTOxml($method, $postdata, 'zh-CN', $serviceType);
		    
		    set_time_limit(100);
		    ignore_user_abort(true);
		    $this->soapClient = new \SoapClient(self::$wsdl.$serviceType.'?wsdl', array(true));	
			$result = $this->soapClient->$method($params);
			$result = json_decode(json_encode($result), true);
			
			\Yii::info('LB_SFOverseaWarehouseAPI puid:1  '.'  '.print_r($params,1).'  '.$method, "file");
			
			\Yii::info('LB_SFOverseaWarehouseAPI7 customer_id:'.self::$customer_id.'  '.print_r($result,1).'  '.$method, "file");
			
			if($serviceType == 'inventoryService'){
				return $result['return'];
			}
			$arr = self::outputStruct($result['return']);
			return $arr;
		} 
	    catch(CarrierException $e)
	    {
	    	return self::getResult(1,'',$e->msg());
	    }
	}
	
	//处理输出数据
	public static function outputStruct($str) { 
		$dom = new \DOMDocument();
		$dom->loadXML($str);
		$result = array();
		
		$isOk = $dom->getElementsByTagName("Head")->item(0)->nodeValue;
	
		if($isOk=='OK')
		{
			$err = $dom->getElementsByTagName("Body")->item(0)->childNodes->item(0);
			foreach($err->attributes as $k=>$v)
			{
				$result[$k] = $v->nodeValue;
			};
			$result['ack'] = 'OK';
	
		}
		else if($isOk == 'ERROR')
		{
			$err = $dom->getElementsByTagName("ERROR")->item(0);
			$code = $err->getAttribute('code');
			$codeArr = explode(',', $code);
			$errorInfo = self::code2error();
			if(!empty($codeArr) && count($codeArr)>0){
				foreach($codeArr as $error){
					$result['code'][] = empty($errorInfo[$error]) ? $error : $errorInfo[$error];//返回错误是否在我们定义好的错误数组中，否则直接返回$error
				}
			}
			$result['ack'] = 'ERR';
		}
		return $result;
	
	}
	
	//拼接请求报文xml格式字符串
	function arrayTOxml($method, $lastDataArr, $lang = 'zh-CN', $serviceType = 'orderService')
	{
		$str1 = '<Request lang="'.$lang.'"><Head>';
		$str1 .= '<customer>'.self::$customer_id.'</customer>';
		$str1 .= '<auth>'.self::$auth.'</auth>';
		
		$body = '';
		if($serviceType == 'inventoryService'){
			$apiParams = '';
			
			foreach ($lastDataArr as $lastDataKey => $lastDataVal){
				//因为不清楚有几种类型，暂时写死。以后确定后可以再扩展
				$tmpValType = gettype($lastDataVal);
				if($tmpValType == 'integer'){
					$tmpValType = 'int';
				}else{
					$tmpValType = 'string';
				}
				
				$apiParams .= '<'.$lastDataKey.' type="'.$tmpValType.'" filter="EQ">'.$lastDataVal.'</'.$lastDataKey.'>';
				
				$body = '<Param>'.$apiParams.'</Param>';
			}
		}else {
			if($method == 'add')
			{
			    $orderParams = '';
	    		foreach($lastDataArr['order'] as $k=>$v){
	    			$orderParams .= ' '.$k.'="'.$v.'"';
	    		}
	    		
	    		$OrderConsignee = '<OrderConsignee';
	    		foreach($lastDataArr['OrderConsignee'] as $k=>$v){
	    			$OrderConsignee .= ' '.$k.'="'.$v.'"';
	    		}
	    		$OrderConsignee .= '/>';
	    		
	    		$OrderLines = '<OrderLines>';
	    		foreach($lastDataArr['Items'] as $value){
	    			$OrderLine = '';
	    			foreach ($value as $k => $v) {
	    				$OrderLine .= ' '.$k.'="'.$v.'"';
	    			}
	    			$OrderLines .= '<OrderLine'.$OrderLine.'/>';
	    		}
	    		$OrderLines .= '</OrderLines>';
	    		$body = '<Order'.$orderParams.'>'.$OrderConsignee.$OrderLines.'</Order>';
			}
			else if($method == 'query')
			{
				$body = '<Param><dohCode type="string" filter="EQ">'.$lastDataArr['dohCode'].'</dohCode></Param>';
			}
			else
			{
			    $body = '<Param><dohCode type="string">'.$lastDataArr['dohCode'].'</dohCode></Param>';
			}
		}
		
		//body内容
		$xml = '<Body>'.$body.'</Body>';
		//token生成
		$md5Data = strtoupper(md5($xml.self::$key));
		$str1 .= '<token>'.$md5Data.'</token></Head>';
		$result = $str1.$xml.'</Request>';
		return ['xml'=>$result];
	
	}
	
	//平台代码
	function getPlatfromcode(){
		$arr = [
		'amazon'=>'A',
		'ebay'=>'E',
		'aliexpress'=>'S',
		'wish'=>'W',
		];
		return $arr;
	}
	
	//状态码
	public static function code2error()
	{
		$arr = 
		[
    		1001=>'客户授权码错误或不存在',
            1002=>'客户接入编码错误',
            1003=>'客户请求报文数字签名不一致',
            1101=>'日期格式不对',
            1102=>'XML解析错误，如必要的参数缺失，数据类型错误等',
            1103=>'整数格式不对',
            1201=>'当前记录必须为待审核状态',
            1202=>'不允许批量创建',
            1301=>'开始日期比结束日期大',
            1302=>'查询条件日期跨度超过7天',
            1303=>'查询开始日期与结束日期必须闭合',
            1304=>'查询条件日期超过当前日期',
            1305=>'查询条件不能全为空',
            1306=>'查询条件不存在，比如SKU写为sku',
            1307=>'查询条件类型错误，比如string写为int',
            1308=>'查询条件过滤规则错误，比如EQ写为LIKE',
            1309=>'分页大小不能超过100',
            1401=>'库存不足，订单审核失败',
            2001=>'参考号不能为空',
            2002=>'国家编码不能为空',
            2003=>'目的仓库编码不能为空',
            2004=>'派送方式编码不能为空',
            2005=>'收件人姓名不能为空',
            2006=>'收件人省市区代码不能为空',
            2007=>'街道不能为空',
            2008=>'邮编不能为空',
            2010=>'件数不能为空',
            2011=>'运单号不能为空',
            2014=>'申报价值不能为空',
            2016=>'发货人名称不能为空',
            2017=>'发货人所在国家（地区）代码不能为空',
            2018=>'发货人地址不能为空',
            2019=>'发货人电话 不能为空',
            2020=>'订单人证件类型不能为空',
            2021=>'订单人证件号码不能为空',
            2022=>'订单人电话不能为空',
            2023=>'订单人名称不能为空',
            2024=>'订单创建时间不能为空',
            2025=>'订单编号不能为空',
            2026=>'订单商品总额币制不能为空',
            2027=>'进出口标示不能为空',
            2028=>'收件人地址不能为空',
            2029=>'收件人电话不能为空',
            2030=>'发件人名称不能为空',
            2031=>'税款不能为空',
            2032=>'税款币值不能为空',
            2033=>'运费不能为空',
            2034=>'运费币值不能为空',
            2035=>'保价费不能为空',
            2036=>'保价费币值不能为空',
            2037=>'商品单价不能为空',
            2038=>'商品数量不能为空',
            2039=>'商总单价不能为空',
            2040=>'平台商品货号不能为空',
            2041=>'收件人手机号不能为空',
            2101=>'国家编码长度必须为2位或3位',
            2102=>'平台代码长度必须为1位',
            2103=>'派送方式编码长度不能超过为20位',
            2104=>'参考号长度不能超过30位',
            2105=>'目的仓库编码长度不能超过30位',
            2106=>'销售交易号长度不能超过30位',
            2107=>'备注长度不能超过255位',
            2108=>'收件人姓名长度不能超过100位',
            2109=>'公司名称不能超过128位',
            2110=>'城市名称长度不能超过100位',
            2111=>'省名称长度不能超过100位',
            2112=>'县名称不能超过64位',
            2113=>'街道名称不能超过80位',
            2114=>'门牌号不能超过20位',
            2115=>'邮编长度不能超过20位',
            2116=>'收件人电话长度不能超过16位',
            2117=>'邮箱名称不能超过80位',
            2119=>'证件号长度不能超过30位',
            2120=>'地区名称长度不能超过30位',
            2121=>'发件人名称长度不能超过100位',
            2122=>'发货人所在国家（地区）代码长度不能超过3位',
            2123=>'发货人地址长度不能超过255位',
            2124=>'发货人电话 长度不能超过16位',
            2125=>'发货人省市区代码长度不能超过30位',
            2126=>'消费者电商平台用户名长度不能超过200位',
            2127=>'订单人证件类型长度不能超过2位',
            2128=>'订单人证件号码长度不能超过30位',
            2129=>'订单人电话长度不能超过16位',
            2130=>'订单人名称长度不能超过30位',
            2131=>'订单人邮箱长度不能超过50位',
            2132=>'物流公司名称 长度不能超过20位',
            2133=>'主要商品信息，货物名称长度不能超过1000位',
            2134=>'支付单号长度不能超过30位',
            2135=>'支付方式代码长度不能超过3位',
            2136=>'是否组装长度不能超过2位',
            2137=>'保险方式编码长度不能超过1位',
            2138=>'收件人省市区代码长度不能超过30位',
            2139=>'订单编号长度不能超过30位',
            2140=>'订单商品总额币制长度不能超过3位',
            2141=>'进出口标示长度不能超过1位',
            2142=>'运单号长度不能超过30位',
            2143=>'区长度不能超过100位',
            2144=>'收件人地址长度不能超过255位',
            2145=>'收件人证件号码长度不能超过30位',
            2146=>'发货人地址长度不能超过255位',
            2147=>'税款币值长度不能超过3位',
            2148=>'运费币值长度不能超过3位',
            2149=>'保价费币值长度不能超过3位',
            2150=>'购物网站平台代码长度不能超过30位',
            2151=>'平台商品货号长度不能超过20位',
            2201=>'参考号必须为数字、下划线和字母组成',
            2202=>'货品数量必须为正整数/货品数量不能超过9999999',
            2203=>'申报价值必须大于零/申报价值小数点前最多允许8位，小数点后面最多允许2位',
            2204=>'优惠金额超出范围/优惠金额不能小于零/优惠金额不能为空',
            2205=>'优惠金额合计小数点前最多允许8位，小数点后面最多允许2位/优惠金额合计不能小于零',
            2206=>'买家实付金额必须大于零/买家实付金额小数点前最多允许8位，小数点后面最多允许2位',
            2207=>'订单总金额必须大于零/订单总金额小数点前最多允许8位，小数点后面最多允许2位',
            2208=>'税款不能小于零/税款超出范围',
            2209=>'运费超出范围/运费不能小于零',
            2210=>'保价费不能小于零/保价费超出范围',
            2211=>'件数必须为正整数',
            2212=>'件数最大为999999999',
            2213=>'商品单价超出范围/商品单价必须大于零',
            2214=>'商品数量必须大于零/商品数量超出范围',
            2215=>'商品总价超出范围/商品总价必须大于零',
            2216=>'收件人电话必须由数字、空格或中横线组成',
            2302=>'仓库编码不存在',
            2303=>'SKU编码不存在',
            2304=>'订单参考号重复',
            2305=>'派送方式不合法或不存在',
            2306=>'平台代码不存在',
            2307=>'国家编码不存在',
            2309=>'非待审核状态数据，不允许审核',
            2310=>'非待审核状态数据，不允许取消',
            2311=>'订单记录不存在',
            2312=>'SKU非已审核状态',
            2313=>'派送方式最小货品重量要求不符',
            2314=>'派送方式最大货品重量要求不符',
            2315=>'派送方式仅支持一票一件文件类型的货品',
            2316=>'派送方式不支持当前地区',
            2317=>'未开通该仓库',
            2318=>'VATCODE不正确',
            2319=>'省市区代码不合法',
            2320=>'支付时间不能早于创建时间',
            2321=>'保险方式编码不合法',
            2322=>'证件类型编码不合法',
            2323=>'支付方式不合法',
            2324=>'组合装选值不合法',
            2325=>'订单行数超过最大值',
            2326=>'DHL订单参考号超出最大长度',
            2327=>'派送方式与渠道无对应关系',
            2328=>'DHL订单参考号重复',
            2329=>'特定渠道客户必须配置BSP信息',
            2601=>'邮编是无效的美国邮编',
            2602=>'订单国家为美国,州不能为空',
            2603=>'订单国家为美国,城市不能为空',
            2604=>'E代理-邮政经济渠道挂号方式，收件人电话不能为空',
            2605=>'E代理-邮政经济渠道平邮，收件人电话不能为空',
            2606=>'皇家邮政1级平邮大信封服务派送方式,城市不能为空',
            2607=>'皇家邮政1级挂号大信封服务派送方式,城市不能为空',
            2608=>'皇家邮政2级平邮包裹服务派送方式,城市不能为空',
            2609=>'皇家邮政Tracked 48服务派送方式,城市不能为空',
            2610=>'皇家邮政Tracked 48签名服务派送方式,城市不能为空',
            2611=>'皇家邮政Tracked Packet服务派送方式,城市不能为空',
            2612=>'皇家邮政Tracked Packet签名服务派送方式,城市不能为空',
            2613=>'DPD英国派送服务派送方式,城市不能为空',
            2614=>'皇家邮政欧盟平邮大信封服务派送方式,城市不能为空',
            2615=>'皇家邮政欧盟挂号大信封服务派送方式,城市不能为空',
            2616=>'皇家邮政世界平邮大信封服务派送方式,城市不能为空',
            2617=>'皇家邮政世界挂号大信封服务派送方式,城市不能为空',
            2618=>'皇家邮政欧盟平邮包裹服务派送方式,城市不能为空',
            2619=>'皇家邮政欧盟挂号包裹服务派送方式,城市不能为空',
            2620=>'皇家邮政世界平邮包裹服务派送方式,城市不能为空',
            2621=>'皇家邮政世界挂号包裹服务派送方式,城市不能为空',
            2622=>'DPD欧盟小包服务派送方式,城市不能为空',
            2623=>'DPD欧盟大包服务派送方式,城市不能为空',
            2624=>'[标准快递渠道]派送方式国家为俄罗斯最大货品重量要求不超过20KG',
            2625=>'美国UPS服务，收件人电话必填且由数字或中横线组成',
		];
		return $arr;
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
	    	$postdata['dohCode'] = '00001';
	    	$response = $this->SendRequest('cancel', $postdata, null, $data);

	    	if($response['ack'] == 'ERR' && $response['code'][0] == '订单记录不存在')
	    	    $result['error'] = 0;
		}
		catch(CarrierException $e){}
	
		return $result;
	}
	
	/**
	 * 获取海外仓库存列表
	 * 
	 * @param 
	 * 			$data['accountid'] 			表示账号小老板对应的账号id
	 * 			$data['warehouse_code']		表示需要的仓库ID
	 * @return 
	 */
	function getOverseasWarehouseStockList($data = array()){
// 		$data['accountid'] = '';
// 		$data['warehouse_code'] = '';

		//海外仓:在文件中添加对应的【物流商代码.php】文件 直接读取该文件生成物流运输方式
		require_once(dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR."eagle".DIRECTORY_SEPARATOR."web".DIRECTORY_SEPARATOR."docs".DIRECTORY_SEPARATOR.'lb_sfOversea.php');
		
		if(!isset($warehouse[$data['warehouse_code']])){
			return self::getResult(1, array() , '没有该仓库Code');
		}
		
		//定义翻页结构
		$postdata = array();
		$postdata['pageSize'] = 100;
		
		//认证信息
		$api_params['customer_id'] = $data['api_params']['customer_id'];
		$api_params['auth'] = $data['api_params']['auth'];
		$api_params['key'] = $data['api_params']['key'];
		
// 		self::$wsdl = 'http://ibu-oms-core.sit.sf-express.com:8080/oms/ws/';
		
		//定义第几页开始
		$pageInt = 0;
		//默认最大页数为1
		$pageMaxInt = 1;
		
		$resultStockList = array();
		
		while ($pageInt < $pageMaxInt){
			$pageInt++;
			
			$postdata['currentPage'] = $pageInt;
			
			$response = $this->SendRequest('query', $postdata, null, $api_params, 'inventoryService');
			
			try{
				$xml_array = simplexml_load_string($response);
				
				if($xml_array->Head == 'OK'){
					if(isset($xml_array->Body->Stock->total)){
						//确定总页数
						$pageMaxInt = (int)($xml_array->Body->Stock->total / $postdata['pageSize']);
							
						if(($xml_array->Body->Stock->total % $postdata['pageSize']) > 0){
							$pageMaxInt++;
						}
						
						$tmpStockLine = $xml_array->Body->Stock->StockLines;
						
						foreach ($tmpStockLine->StockLine as $tmpStockLineVal){
							$valList = array();
							
							foreach ($tmpStockLineVal->attributes() as $tmpAttrKey => $tmpAttrVal){
// 								echo $tmpAttrVal[0];
								$valList[$tmpAttrKey] = (string)$tmpAttrVal[0];
							}
							
							//只返回该仓库的库存列表
							if($warehouse[$data['warehouse_code']] == $valList['whName']){
								$resultStockList[$valList['SKU']] = array(
										'sku'=>$valList['SKU'],
										'productName'=>$valList['imName'],
										'stock_actual'=>$valList['warehouseQty'],				//实际库存
										'stock_reserved'=>$valList['qutQty'],	//占用库存
										'stock_pipeline'=>$valList['qtyIn'],	//在途库存
										'stock_usable'=>$valList['avallableQty'],	//可用库存
										'warehouse_code'=>$valList['whName']		//仓库代码
								);
							}
						}
					}else{
						$pageMaxInt = 1;
					}
				}else{
					$pageMaxInt = 1;
				}
			}catch (\Exception $ex){
					
			}
		}

		return self::getResult(0, $resultStockList ,'');
	}
	
}
?>