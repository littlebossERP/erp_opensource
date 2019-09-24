<?php
namespace common\api\carrierAPI;


use eagle\modules\order\models\OdOrder;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\models\SaasLazadaUser;
use common\api\lazadainterface\LazadaInterface_Helper;

class LB_SEKOCarrierAPI extends BaseCarrierAPI{
	public function __construct(){
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			 
			$order = $data['order'];
			//重复发货 添加不同的标识码
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
		
			$info = CarrierAPIHelper::getAllInfo($order);
			$Service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
		
// 			if(!empty($order->addi_info)){
// 				$tmp_addi_info = json_decode($order->addi_info, true);
// 			}else{
// 				$tmp_addi_info = array();
// 			}
			 
			//lazada接口不稳定，要做兼容
			if($order->order_source_status == 'ready_to_ship'){
				list($ret,$packageInfo) = LazadaApiHelper::getJumiaPackageInfo($order);
	
				if($ret == true){
					$r = CarrierAPIHelper::orderSuccess($order,$Service,$customer_number, OdOrder::CARRIER_WAITING_PRINT, $packageInfo['TrackingNumber'], ['PackageId'=>$packageInfo['PackageId']]);
					return  self::getResult(0,$r,'标志发货成功e2');
				}else if($ret == false){
					return self::getResult(1,'',$packageInfo);
				}else {
					return self::getResult(1,'','获取数据有误，请联系技术人员');
				}
			}
			 
// 			if(!isset($tmp_addi_info['jumia_seko_info'])){
// 				$getTrackingNo = LazadaApiHelper::packLazadaLgsOrder($order);
	
// 				if($getTrackingNo[0] == true){
// 					$tmp_addi_info['jumia_seko_info'] = array('TrackingNumber'=>$getTrackingNo[1]['TrackingNumber'], 'PackageId'=>$getTrackingNo[1]['PackageId']);
	
// 					//这里先记录下来获取到的跟踪号是什么,预防直接下一步通知平台发货失败
// 					$order->addi_info = json_encode($tmp_addi_info);
// 					$order->save();
// 				}else{
// 					return self::getResult(1, '', $getTrackingNo[1]);
// 				}
// 			}
			 
// 			if(!isset($tmp_addi_info['jumia_seko_info'])){
// 				return self::getResult(1, '', '调用Lazada接口失败,请联系小老板客服');
// 			}
			
			$ship_result = LazadaApiHelper::shipJumiaOrder($order);
			if($ship_result[0] == true){
				$r = CarrierAPIHelper::orderSuccess($order,$Service,$customer_number, OdOrder::CARRIER_WAITING_GETCODE);
				return  self::getResult(0,$r,'标志发货成功');
			}else if($ship_result[0] == false){
				return self::getResult(1,'',$ship_result[1]);
			}else{
				return self::getResult(1,'','获取数据有误，请联系技术人员');
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
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
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		try{
			$order = $data['order'];
			list($ret,$packageInfo) = LazadaApiHelper::getJumiaPackageInfo($order);
		
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
		
			if($ret == true){
				if(empty($packageInfo['TrackingNumber'])){
					return self::getResult(1,'','获取跟踪号有误！');
				}else if(empty($packageInfo['PackageId'])){
					return self::getResult(1,'','获取包裹号有误！');
				}else{
					$shipped->tracking_number = $packageInfo['TrackingNumber'];
					$shipped->return_no = ['PackageId'=>$packageInfo['PackageId']];
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					return self::getResult(0,'','获取跟踪号成功!跟踪号：'.$packageInfo['TrackingNumber']);
				}
			}else if($ret == false){
				return self::getResult(1,'',$packageInfo);
			}else{
				return self::getResult(1,'','获取数据有误，请联系技术人员');
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			$puid = $user->getParentUid();
			 
			$order = current($data);
			reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
		
			//获取站点键值
			$code2CodeMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("jumia");
		
			//记录账号和站点
			$lazada_account_site = array();
		
			foreach ($data as $key => $value){
				$order = $value['order'];
				 
				if (empty($code2CodeMap[strtolower($order->order_source_site_id)]))
					return self::getResult(1,'','订单:'.$order->order_id." 站点" . $order->order_source_site_id . "不是 jumia的站点。");
				
				
				 
				if(!isset($lazada_account_site[$order->selleruserid])){
					$lazada_account_site[$order->selleruserid] = array();
				}
				 
				if(!isset($lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)])){
					$lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)] = '';
				}
				 
				$tmp_item_ids = $lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)];
				 
				foreach($order->items as $item){
					$tmp_item_ids .= empty($tmp_item_ids) ? $item->order_source_order_item_id : ','.$item->order_source_order_item_id;
				}
				 
				$lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)] = $tmp_item_ids;
			}
		
			//记录返回的base64字符串
			$tmp_base64_str_a = array();
		
			//循环获取lazada返回的数据
			foreach ($lazada_account_site as $lazada_account_key => $lazada_account_val){
				foreach ($lazada_account_val as $lazada_site_key => $lazada_site_val){
					$SLU = SaasLazadaUser::findOne(['platform_userid' => $lazada_account_key, 'lazada_site' => $lazada_site_key]);
		
					if (empty($SLU)) {
						return self::getResult(1,'',$lazada_account_key . " 账号不存在" .' '. $lazada_site_key.'站点不存在');
					}
		
					$lazada_config = array(
							"userId" => $SLU->platform_userid,
							"apiKey" => $SLU->token,
							"countryCode" => $SLU->lazada_site
					);
		
					$lazada_appParams = array(
							'OrderItemIds' => $lazada_site_val,
					);
		
					$result = LazadaInterface_Helper::getOrderShippingLabel($lazada_config, $lazada_appParams);
		
					if ($result['success'] && $result['response']['success'] == true) { // 成功
						$tmp_base64_str_a[] = $result["response"]["body"]["Body"]['Documents']["Document"]["File"];
						
					} else {
						return self::getResult(1, '', '打印失败原因：'.$result['message']);
					}
				}
			}
		
			//最终生成的HTML
			$tmp_html = '';
		
			foreach ($tmp_base64_str_a as $tmp_base64_val){
				$tmp_html .= empty($tmp_html) ? base64_decode($tmp_base64_val) : '<hr style="page-break-after: always;border-top: 3px dashed;">'.base64_decode($tmp_base64_val);
			}
		
			//LGS 返回的是html代码所以直接输出即可
			echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body style="margin:0px;">'.$tmp_html.''.'</body>';
			exit;
		}catch(Exception $e) {
			return self::getResult(1,'',$e->getMessage());
		}
	}
	
	//获取运输方式
	public function getCarrierShippingServiceStr($account){
	    try{
	        $user=\Yii::$app->user->identity;
	        if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
	        $puid = $user->getParentUid();
	        $channelStr = '';
	        
	        $users = SaasLazadaUser::find()->where(['platform'=>'jumia','puid'=>$puid,'status'=>1])->asArray()->all();
	        if(!empty($users)){
	            $channelArray = [];
	            foreach ($users as $val){
	                if(!empty($val['shipment_providers'])){
	                    $providers = json_decode($val['shipment_providers'],true);
	                    foreach ($providers as $v){
	                        $channelArray[$v] = $v;
	                    }
	                }
	            }
	            
	            if(!empty($channelArray)){
	                foreach ($channelArray as $key => $val){
	                    $channelStr .= $key.':'.$val.';';
	                }
	            }
	        }
	        
	        if(empty($channelStr)){
	            return self::getResult(1, '', '没有找到可以匹配这个运输服务的Jumia账号，请确保Jumia账号已经绑定并且该账号已经向Jumia开通该运输方式。');
	        }else{
	            return self::getResult(0, $channelStr, '');
	        }
	    }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}
}