<?php

namespace common\api\carrierAPI;

use common\api\carrierAPI\NiuMenBaseConfig;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use common\helpers\Helper_Curl;
use Jurosh\PDFMerge\PDFMerger;
use Qiniu\json_decode;


class LB_BOYANGCarrierAPI extends BaseCarrierAPI{
	
	static public $url = "114.80.116.113:80";
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/06/07				初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){		
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);	
		$return_info = $base_config_obj->_getOrderNo();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/06/07				初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_cancelOrderNO();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/06/07				初始化
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_doDispatch();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/06/07				初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_getTrackingNo();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/06/08				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$pdf = new PDFMerger();
			$user=\Yii::$app->user->identity;
			if(empty($user)){
				throw new CarrierException('用户登陆信息缺失,请重新登陆');
			}
			$puid = $user->getParentUid();
				
			$order = current($data);reset($data);
			$order = $order['order'];
				
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$a = $account->api_params;
				
			$aNoArr = '';
			foreach ($data as $k=>$v) {
				$order = $v['order'];
					
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
			
				if(empty($shipped->tracking_number)){
					$return_infos['error'] = 1;
					$return_infos['msg'] = 'order:'.$order->order_id .' 缺少追踪号';
					return $return_infos;
				}
				
				$aNoArr= $shipped->tracking_number;
				
				//获取时间戳
				$request = ["RequestName"=>"TimeStamp"];
				$response = Helper_Curl::post('http://'.self::$url.'/cgi-bin/EmsData.dll?DoApp', json_encode($request));
				$response = json_decode($response);
				$timestamp = $response->ReturnValue;
	
				$md5 = md5($account->api_params['userkey'].$timestamp.$account->api_params['token']);
									
				$request = [
					'RequestName'=>'EPostPdf',
					'icID'=>$account->api_params['userkey'],
					'TimeStamp'=>$timestamp,
					'MD5'=>$md5,
					'cqNo'=>$aNoArr,
					'iSP_Type'=>0,
				];
											
				$response = Helper_Curl::post('http://'.self::$url.'/cgi-bin/EmsData.dll?DoApi',json_encode($request));
// 				print_r($response);die;
				if(strlen($response)<1000){
					$errstr=json_decode($response);
					
					$arr = [
					-3=>'未提供跟踪号或跟踪号不是eub的单号',   //'未提供“cqNo”或不是eub的单号'
					-10=>'未准备好数据，稍后再试。eub的pdf数据通常在转单号数据提交后10秒左右准备好',
							];
					
					if(array_key_exists($errstr->ReturnValue,$arr)) 
						$return_infos['msg']= '返回打印失败,'.$arr[$errstr->ReturnValue];
					else
						$return_infos['msg'] = '返回打印失败';
					$return_infos['error'] = 1;
					return $return_infos;
				}else{
// 					$order->is_print_carrier = 1;
					$order->print_carrier_operator = $puid;
					$order->printtime = time();
					$order->save();
					
					//如果成功返回pdf 则保存到本地
					$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$order['customer_number'], 0);				
					$pdf->addPDF($pdfUrl['filePath'],'all');
				}
			}	
			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl='';
			$return_infos['msg'] = '连接已生成,请点击并打印';
			$return_infos['data'] = ['pdfUrl'=>$pdfUrl['pdfUrl']];

			return BaseCarrierAPI::getResult(0,$return_infos['data'],$return_infos['msg']);
		}catch(CarrierException $e){return BaseCarrierAPI::getResult(1,'',$e->msg());}
	}
	
	//获取运输服务
	public function getCarrierShippingServiceStr($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_getCarrierShippingServiceStr();
		return $return_info;
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
		$base_config_obj = new NiuMenBaseConfig('',self::$url);
		$return_info = $base_config_obj->_getVerifyCarrierAccountInformation($data);
		return $return_info['data'];
	}
	
}

?>