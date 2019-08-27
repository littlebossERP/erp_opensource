<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_xml;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrderShipped;
use common\helpers\Helper_Curl;
use common\helpers\SubmitGate;
use common\api\carrierAPI\BaseCarrierAPI;

class LB_QINGNIAOOverseaWarehouseAPI extends BaseOverseaWarehouseAPI
{
	private $appkey = '';
	private $appSecret = '';
	
	public function __construct(){
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2017/02/08			初始化
	 +----------------------------------------------------------
	 **/
	function getOrderNO($data){
		try{			
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
			$carrier_params = $service->carrier_params;
			
			$this->appkey = $api_params['appkey'];
			$this->appSecret = $api_params['appSecret'];
			
			///获取收件地址街道
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
						
			$weight="";
			$itemList=array();
			foreach ($order->items as $j=>$vitem){
				$productId=0;
				$productList=self::getOverseasWarehouseStock($account);
				if($productList['error']){return self::getResult(1,'',$productList['msg']);}
				$productList=$productList['data']['productList'];

				foreach ($productList as $productListone){	
					if($productListone['warehouseCode']==$service->third_party_code && $productListone['productSKU']==$form_data['sku'][$j]){
						$productId=$productListone['productId'];
						break;
					}
				}
				
				if($productId==0)
					return self::getResult(1,'',$form_data['sku'][$j].'在仓库中不存在或没有库存');
				$itemList[]=array(
						'productId'=>$productId,   //库存商品ID
						'quantity'=>$form_data['quantity'][$j],       //发货数量（<=可发货数量）
				);
			}

			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}

			$request[]=array(
						'warehouseCode'=>$service->third_party_code,  //仓库代码
						'channelCode'=>$service->shipping_method_code,     //发货渠道code
						'remark'=>$form_data['remark'],    //备注
						'recipient'=>array(
								'contactName'=>$order->consignee,  //收件人姓名
								'city'=>$order->consignee_city,     //城市
								'state'=>$tmpConsigneeProvince,       //省
								'county'=>'',
								'country'=>$order->consignee_country_code,       //国家简码
								'countryCode'=>$order->consignee_country_code,       //国家简码(新)
								'zipCode'=>$order->consignee_postal_code,       //邮编
								'zip4'=>'',
								'telephone'=>$addressAndPhone['phone1'],  //电话
								'street'=>$addressAndPhone['address_line1'],    //详细地址1
								'street1'=>$addressAndPhone['address_line2'],     //详细地址2
						),
						'itemList'=>$itemList,
			);
			$request=array(
					'instructionList'=>$request,
			);

			$request=json_encode($request);
			\Yii::info('LB_QINGNIAOOversea,puid:'.$puid.',request,order_id:'.$order->order_id.' '.$request,"carrier_api");
			$channelArr=$this->runxml($request, $this->appkey,$this->appSecret, $service->third_party_code, "createorder",'POST');

			if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
			$channelArr=$channelArr['data'];

			if(isset($channelArr['errCode']) && $channelArr['errCode']!='200')
				return self::getResult(1,'',$channelArr['errMsg']);
			
			if(!empty($channelArr['productInstructionList'])){
				$instructionNumber=$channelArr['productInstructionList'][0]['instructionNumber'];
				$instructionId=$channelArr['productInstructionList'][0]['instructionId'];
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,null,['OrderSign'=>$instructionId]);
				return self::getResult(0,$r,'操作成功,订单号:'.$instructionNumber.'. 请在已交运界面获取跟踪号');
			}
			else
				return self::getResult(1,'',$channelArr['errMsg']);

		}
		catch(CarrierException $e)
		{
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单交运
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2017/02/08			初始化
	 +----------------------------------------------------------
	 **/
	function doDispatch($data){
		return self::getResult(1,'','物流接口不支持交运');
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取跟踪号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2017/02/08			初始化
	 +----------------------------------------------------------
	 **/
	function getTrackingNO($data){
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$api_params = $account->api_params;
			$this->appkey = $api_params['appkey'];
			$this->appSecret = $api_params['appSecret'];
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
			$request=array(
					'instructionIds'=>array(
							$shipped['return_no']['OrderSign'],
					),
			);
			$request=json_encode($request);

			$channelArr=$this->runxml($request, $this->appkey,$this->appSecret,'', "gettracknumber",'POST');

			if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
			$channelArr=$channelArr['data'];

			if(isset($channelArr['errCode']) && $channelArr['errCode']!='200')
				return self::getResult(1,'',$channelArr['errMsg']);
			
			if(!empty($channelArr['reponseJsons'])){
				$reponseJsons=$channelArr['reponseJsons'][0];
				$truckNumber=$reponseJsons['truckNumber'];
				if(empty($truckNumber))
					return self::getResult(1,'','跟踪号没有返回');
				
				$shipped->tracking_number = $truckNumber;
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				
				return self::getResult(0, '', '获取成功！跟踪号：'.$truckNumber);
			}
			else{
				return self::getResult(1,'',$channelArr['errorMsg']);
			}
			
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
	 * @author		lgw 	  2017/02/08			初始化
	 +----------------------------------------------------------
	 **/
	function cancelOrderNO($data){
		return self::getResult(1,'','物流接口不支持取消已确定的物流单。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单打印
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2017/02/08			初始化
	 +----------------------------------------------------------
	 **/
	function doPrint($data){
		return self::getResult(1,'','该物流商不支持打印订单');
	}
	
	//获取仓库
	function getDelivery($appkey,$appSecret){
		try{
			$request='';
			
			// TODO carrier user account @XXX@
			$appkey=empty($appkey)?'@XXX@':$appkey;
			$appSecret=empty($appSecret)?'@XXX@':$appSecret;
				
			$channelArr=$this->runxml($request,$appkey,$appSecret,'', "getwarehouses",'GET');
				
			if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
			$channelArr=$channelArr['data'];
			if(isset($channelArr['errCode']) && $channelArr['errCode']!='200')
				return self::getResult(1,'',$channelArr['errMsg']);
				
			$result=$channelArr['warehouseList'];
			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}
		catch(CarrierException $e)
		{
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/*
	 * 获取各仓库运输服务
	*/
	function getDeliveryService($appkey,$appSecret,$third_party_code=null){
		try{
			$request='';
			
			// TODO carrier user account @XXX@
			$appkey=empty($appkey)?'@XXX@':$appkey;
			$appSecret=empty($appSecret)?'@XXX@':$appSecret;
			
			$channelArr=$this->runxml($request,$appkey,$appSecret,$third_party_code, "getchannels",'GET');
			
			if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
			$channelArr=$channelArr['data'];
	
			if(isset($channelArr['errCode']) && $channelArr['errCode']!='200')
				return self::getResult(1,'',$channelArr['errMsg']);

			$result=$channelArr['channelsList'];
			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}
		catch(CarrierException $e)
		{
			return self::getResult(1,'',$e->msg());
		}
	}
	
	//拼接请求报文xml格式字符串
	function arrayTOxml($lastDataArr,$appSecret, $lang = 'zh-CN'){
		$xml = $lastDataArr;//请求报文 xml
		$datetime=self::getLaFormatTime('Y-m-d H:m:s',time());
		$digest=strtolower(\md5($lastDataArr.$appSecret.$datetime));  //加密

		return $digest;
	}
	
	//获取地址
	function checkAPIUrl($jiekou){
		$url='';
// 		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			$url = 'http://www.mofangyc.com:8101/wgs/v1/overseas/'.$jiekou;   //正式环境
// 		}
// 		else
// 			$url = '115.159.227.235:8101/wgs/v1/overseas/'.$jiekou;   //测试环境

		return $url;
	}
	
	//时区转换
	function getLaFormatTime($format,$timestamp,$DateTimeZone='Asia/Shanghai'){
		$dt=new \DateTime();
		$dt->setTimestamp($timestamp);
		$dt->setTimezone(new \DateTimeZone($DateTimeZone));
		$d=$dt->format($format);
		return $d;
	}
	
	//组织数据提交
	function runxml($request,$appKey,$appSecret,$third_party_code,$jiekou,$curl='post'){
		$xml=$this->arrayTOxml($request,$appSecret);
		if(empty($xml))
			return self::getResult(1,'','无法获取数据签名');
			
		$url=$this->checkAPIUrl($jiekou);
		
		if(is_null($url))
			return self::getResult(1,'','无法获取物流地址');
		
		$header[] = "appKey:".$appKey;
		$header[] = "signature:".$xml;
		$header[] = "requestDate: ".self::getLaFormatTime('Y-m-d H:m:s',time());
		if($jiekou=='getchannels' && !empty($third_party_code))
			$header[]="warehouseCode:".$third_party_code;
		
		if(strtolower($curl)=='get'){
			$response=Helper_Curl::get($url,null,$header);
		}
		else{//print_r($header);print_r($request);die;
			$response=Helper_Curl::post($url,$request,$header);
		}	

		$channelArr=json_decode($response, true);

		$errarr=Array
		(
				'error'=> 0,
				'data'=> $channelArr,
				'msg'=> '',
		);
		
		if(isset($channelArr['errCode']) && $channelArr['errCode']!='200'){
			$errarr['error']=1;
			$errarr['msg']=$channelArr['errCode'].$channelArr['errMsg'];
		}
		
		return $errarr;
	}
	
	//获取库存列表
	function getOverseasWarehouseStock($account){		
		try{
			$request='';
			
			$api_params = $account->api_params;
			$this->appkey = $api_params['appkey'];
			$this->appSecret = $api_params['appSecret'];
				
			$channelArr=$this->runxml($request,$this->appkey,$this->appSecret,'', "inventorylist",'GET');

			if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
			$channelArr=$channelArr['data'];
						
			if(isset($channelArr['errCode']) && $channelArr['errCode']!='200')
				return self::getResult(1,'',$channelArr['errMsg']);
			
			if(isset($channelArr['productList']) && !empty($channelArr['productList']))
				$result=$channelArr;
			else
				return self::getResult(1,'','获取库存列表失败');
			
			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}
		catch(CarrierException $e)
		{
			return self::getResult(1,'',$e->msg());
		}
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
// 		$data['warehouse_code'] = 'HWC';

		$this->appkey=$data['api_params']['appkey'];
		$this->appSecret=$data['api_params']['appSecret'];
		
 
// 		$this->appkey='@XXX@';
// 		$this->appSecret='@XXX@';
		
		//定义翻页结构
		$postdata = array();
		$postdata['pageSize'] = 100;
		
		//定义第几页开始
		$pageInt = 0;
		//默认最大页数为1
		$pageMaxInt = 1;
		
		$resultStockList = array();
		
		//循环翻页效果
		while ($pageInt < $pageMaxInt){
			$pageInt++;
									
			$response = $this->runxml('',$this->appkey,$this->appSecret,'', "inventorylist",'GET');

			try{
				if(($response['error'] == 0) && isset($response['data']['productList']) && !empty($response['data']['productList'])){				
					if($response['data']['totalCount'] > 0){
						//确定总页数
						$pageMaxInt = (int)($response['data']['totalCount'] / $postdata['pageSize']);
						
						if(($response['data']['totalCount'] % $postdata['pageSize']) > 0){
							$pageMaxInt++;
						}					
						
						unset($tmpStorageList);
						
						$tmpStorageList = $response['data']['productList'];
						
						foreach ($tmpStorageList as $valList){		
							if($data['warehouse_code'] == $valList['warehouseCode']){
								$resultStockList[$valList['productSKU']] = array(
										'sku'=>$valList['productSKU'],
										'productName'=>$valList['productName'],
										'stock_actual'=>$valList['quantity'],				//实际库存
										'stock_reserved'=>0,	//占用库存
										'stock_pipeline'=>0,	//在途库存
										'stock_usable'=>$valList['quantity'],	//可用库存
										'warehouse_code'=>$valList['warehouseCode'],		//仓库代码
								);
							}				
						}
					}
					else
						$pageMaxInt = 1;
				}
				else
					$pageMaxInt = 1;		
			}catch (\Exception $ex){
					
			}
		}

		return self::getResult(0, $resultStockList ,'');
	}
	
}
?>