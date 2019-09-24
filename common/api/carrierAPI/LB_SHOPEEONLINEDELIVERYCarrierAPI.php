<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use Jurosh\PDFMerge\PDFMerger;
use eagle\modules\util\helpers\PDFMergeHelper;
use Qiniu\json_decode;
use eagle\models\CarrierUserLabel;
use common\api\shopeeinterface\ShopeeInterface_Api;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\TimeUtil;

/**
 * Shopee线上发货
 */

class LB_SHOPEEONLINEDELIVERYCarrierAPI extends BaseCarrierAPI{
	public function __construct(){
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/08		初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			//odOrder表内容
			$order = $data['order'];
			
			if($order->order_source != 'shopee'){
				return self::getResult(1, '', '不是Shopee的订单,不允许上传。');
			}
				
			//用户在确认页面提交的数据
			$e = $data['data'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$service_carrier_params = $service->carrier_params;
			$account = $info['account'];
			
			//获取到帐号中的认证参数
			$a = $account->api_params;
			
			//判断shopee账号信息是否有效
			$shopeeUsers = \eagle\models\SaasShopeeUser::find()->where(['puid' => $puid, 'shop_id' => $order->selleruserid])->andWhere("status<>3")->one();
			if($shopeeUsers == null){
				return self::getResult(1, '', '账号绑定失效，请先绑定账号');
			}
			$api = new ShopeeInterface_Api($shopeeUsers->shop_id);
			
			//1、只有平台状态为Ready_to_ship才可使用
			if($order->order_source_status != 'READY_TO_SHIP'){
				return self::getResult(1, '', '此订单状态不是READY_TO_SHIP');
			}
			//2、验证发货流程，non_integrated、pickup、dropoff
			$ordersn = $order->order_source_order_id;
			$parameterForInit = $api->GetParameterForInit(['ordersn' => $ordersn]);
			\Yii::info('LB_SHOPEEONLINEDELIVERYCarrierAPI,puid:'.$puid.',order_id:'.$order->order_id.',1,'.$ordersn.','.json_encode($parameterForInit),"carrier_api");
			if(!empty($parameterForInit['msg'])){
				return self::getResult(1, '', $parameterForInit['msg']);
			}
			//dropoff
			$param['ordersn'] = $ordersn;
			if(isset($parameterForInit['dropoff'])){
				$param['dropoff'] = (object)[];
			}
			else if(isset($parameterForInit['non_integrated'])){
				if(empty($parameterForInit['non_integrated'])){
					$param['non_integrated'] = (object)[];
				}
				else{
					$param['non_integrated'] = (object)['tracking_no' => ''];
				}
			}
			else if(isset($parameterForInit['pickup'])){
				$param['pickup'] = (object)[];
			}
			else{
				return self::getResult(1, '', '货代返回信息异常');
			}
			\Yii::info('LB_SHOPEEONLINEDELIVERYCarrierAPI,puid:'.$puid.',order_id:'.$order->order_id.',2,'.json_encode($param),"carrier_api");
			//提交信息，触发
			$res = $api->LogisticsInit($param);
			\Yii::info('LB_SHOPEEONLINEDELIVERYCarrierAPI,puid:'.$puid.',order_id:'.$order->order_id.',3,'.json_encode($res),"carrier_api");
			//提交信息，再次调用获取物流号
			sleep(1); //延时1s
			$res = $api->LogisticsInit($param);
			\Yii::info('LB_SHOPEEONLINEDELIVERYCarrierAPI,puid:'.$puid.',order_id:'.$order->order_id.',4,'.json_encode($res),"carrier_api");
			
			if(!empty($res['msg'])){
				throw new CarrierException($res['msg']);
			}
			else if(empty($res['tracking_number'])){
				throw new CarrierException($res['货代返回信息异常']);
			}
			
			//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
			$r = CarrierAPIHelper::orderSuccess($order,$service,$ordersn,OdOrder::CARRIER_WAITING_GETCODE, $res['tracking_number']);
			
 			$print_param = array();
 			$print_param['carrier_code'] = $service->carrier_code;
 			$print_param['api_class'] = 'LB_SHOPEEONLINEDELIVERYCarrierAPI';
 			$print_param['tracking_number'] = $res['tracking_number'];
 			$print_param['shop_id'] = $order->selleruserid;
 			$print_param['order_source_order_id'] = $order->order_source_order_id;
 			try{
 				CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $ordersn, $print_param);
 			}catch (\Exception $ex){
 			}
			
			return  BaseCarrierAPI::getResult(0,$r,'操作成功!运单号: '.$res['tracking_number']);
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消跟踪号。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/08		初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
		
			$param = ['ordersn_list' => [$order->order_source_order_id]];
			$api = new ShopeeInterface_Api($order->selleruserid);
			$res = $api->GetTrackingNo(['ordersn_list' => [$order->order_source_order_id]]);
			if(!empty($res['msg'])){
				return self::getResult(1,'', $res['msg']);
			}
			if(empty($res['orders'])){
				return self::getResult(1,'', '查询不到订单信息');
			}
			if(empty($res['orders'][0]['tracking_no'])){
				return self::getResult(1,'', '暂时没有跟踪号');
			}
			$tracking_no = $res['orders'][0]['tracking_no'];
			
			$shipped->tracking_number = $tracking_no;
			$shipped->save();
			
			$order->tracking_number = $shipped->tracking_number;
			$order->save();
			
			$print_param = array();
			$print_param['carrier_code'] = 'lb_shopeeonlinedelivery';
			$print_param['api_class'] = 'LB_SHOPEEONLINEDELIVERYCarrierAPI';
			$print_param['tracking_number'] = $tracking_no;
			$print_param['selleruserid'] = $order->selleruserid;
			//$print_param['run_status'] = 0;
				
			try{
				CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->order_source_order_id, $print_param);
			}catch (\Exception $ex){}
			
			return  BaseCarrierAPI::getResult(0,'','查询成功成功!跟踪号'.$tracking_no);
				
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/08		初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
		    $t1 = TimeUtil::getCurrentTimestampMS();
		    
			$pdf = new PDFMerger();
			$user=\Yii::$app->user->identity;
			if(empty($user)) return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = current($data);reset($data);
			$order = $order['order'];
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
		
			$t2 = TimeUtil::getCurrentTimestampMS();
			$tmpPath = array();
			$order_lists = [];
			foreach ($data as $k => $v) {
				$order = $v['order'];
				//获取key值，因为单次最多20条
				$key = $order->selleruserid;
				$count = 0;
				while(true){
					if(in_array($key, $order_lists) && count($order_lists[$key]) >= 20){
						$key .= '_';
					}
					$count++;
					if($count > 5){
						break;
					}
				}
				$order_lists[$key][] = $order->order_source_order_id;
			}
			//批量获取标签信息
			foreach($order_lists as $shop_id => $order_list){
				$shop_id = rtrim($shop_id, '_');
				
				$t21 = TimeUtil::getCurrentTimestampMS();
				$api = new ShopeeInterface_Api($shop_id);
				$ret = $api->GetAirwayBill(['ordersn_list' => $order_list]);

				$t22 = TimeUtil::getCurrentTimestampMS();
				
				\Yii::info('LB_SHOPEEONLINEDELIVERYCarrierAPI doPrint test log,shop_id:'.$shop_id.',t='.($t22-$t21).',ret:'.json_encode($ret),"carrier_api");
					
				
				if(!empty($ret['result']['errors'])){
				    if(is_array($ret['result']['errors'][0])){
				        return  self::getResult(1, '', $ret['result']['errors'][0]['error_description'].' 订单返回标签失败e1！');
				    }else{
				        return  self::getResult(1, '', $ret['result']['errors'][0].' 订单返回标签失败e2！');
				    }
				}
				if(empty($ret['result']['airway_bills'])){
					return  self::getResult(1, '', '返回标签格式失败！');
				}
				foreach($ret['result']['airway_bills'] as $one){
				    $responsePdf = Helper_Curl::get($one['airway_bill']);
				    // dzt20190819 出现少于1000的pdf ，改成100
				    if( strlen( $responsePdf) < 100){
				        return  self::getResult(1, '', '接口返回内容不是一个有效的PDF！');
				    }
				    
					$pdfUrl = CarrierAPIHelper::savePDF( $responsePdf, $puid, $account->carrier_code.'_'.$order['customer_number'], 0);
					$pdf->addPDF( $pdfUrl['filePath'], 'all');
				}
			}
			
			$t3 = TimeUtil::getCurrentTimestampMS();
			\Yii::info('LB_SHOPEEONLINEDELIVERYCarrierAPI doPrint test log,t0='.($t3-$t1).',t1='.($t2-$t1).',t2='.($t3-$t2),"carrier_api");
			
			if(isset($pdfUrl)){
			    $pdf->merge('file', $pdfUrl['filePath']);
			    
			    return  self::getResult(0, ['pdfUrl'=>$pdfUrl['pdfUrl']], '连接已生成,请点击并打印！');
			}
			else{
			    return  self::getResult(1, '', '连接生成失败！');
			}
			
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
			
			$api = new ShopeeInterface_Api($print_param['shop_id']);
			$ret = $api->GetAirwayBill(['ordersn_list' => [$print_param['order_source_order_id']]]);
			
			if(!empty($ret['result']['errors'])){
				return ['error'=>1, 'msg'=>'获取标签失败', 'filePath'=>''];
			}
			if(empty($ret['result']['airway_bills']) || empty($ret['result']['airway_bills'][0]['airway_bill'])){
				return ['error'=>1, 'msg'=>'获取标签失败', 'filePath'=>''];
			}
			
			$responsePdf = Helper_Curl::get($ret['result']['airway_bills'][0]['airway_bill']);
			if( strlen( $responsePdf) < 1000){
				return ['error'=>1, 'msg'=>'接口返回内容不是一个有效的PDF！', 'filePath'=>''];
			}
			
			$pdfPath = CarrierAPIHelper::savePDF2($responsePdf,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
			return $pdfPath;
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
	
	
	
}

?>