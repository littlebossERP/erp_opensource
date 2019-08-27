<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\models\SaasLazadaUser;
use common\api\lazadainterface\LazadaInterface_Helper;
use common\api\lazadainterface\LazadaInterface_Helper_V2;

class LB_LGSCarrierAPI extends BaseCarrierAPI{
    public function __construct(){}
    /**
     +----------------------------------------------------------
     * 标志发货
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
//             $account = $info['account'];
            $Service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
            
//             if(in_array($puid, array(1, 2812, 3903, 3473, 5791, 8493))){
            if(true){
            	if(!empty($order->addi_info)){
            		$tmp_addi_info = json_decode($order->addi_info, true);
            	}else{
            		$tmp_addi_info = array();
            	}
            	
            	//lazada接口不稳定，要做兼容
            	if($order->order_source_status == 'ready_to_ship'){
            		list($ret,$packageInfo) = LazadaApiHelper::getPackageInfo($order);
            		
            		if($ret == true){
						if( stripos( $packageInfo['ShipmentProvider'],$Service->shipping_method_code)===false ){
							return self::getResult(1,'','选择的运输服务跟后台的标记发货的运输服务不一致:'.$packageInfo['ShipmentProvider'].'--'.$Service->shipping_method_code);
						}
            			//if($packageInfo['ShipmentProvider'] != $Service->shipping_method_code){
            				//return self::getResult(1,'','选择的运输服务跟后台的标记发货的运输服务不一致:'.$packageInfo['ShipmentProvider'].'--'.$Service->shipping_method_code);
            			//}
            			
            			$r = CarrierAPIHelper::orderSuccess($order,$Service,$customer_number, OdOrder::CARRIER_WAITING_PRINT, $packageInfo['TrackingNumber'], ['PackageId'=>$packageInfo['PackageId']]);
            			return  self::getResult(0,$r,'标志发货成功e2');
            		}else if($ret == false){
            			return self::getResult(1,'',$packageInfo);
            		}else {
            			return self::getResult(1,'','获取数据有误，请联系技术人员');
            		}
            	}
            	
            	if(!isset($tmp_addi_info['lazada_lgs_info'])){
            		$getTrackingNo = LazadaApiHelper::packLazadaLgsOrder($order);
            		
            		if($getTrackingNo[0] == true){
            			$tmp_addi_info['lazada_lgs_info'] = array('TrackingNumber'=>$getTrackingNo[1]['TrackingNumber'], 'PackageId'=>$getTrackingNo[1]['PackageId']);

            			//这里先记录下来获取到的跟踪号是什么,预防直接下一步通知平台发货失败
            			$order->addi_info = json_encode($tmp_addi_info);
            			$order->save();
            		}else{
            			return self::getResult(1, '', $getTrackingNo[1]);
            		}
            	}
            	
            	\Yii::info('LB_LGS,puid:'.$puid.',result,order_id:'.$order->order_id.' '.$order->addi_info,"carrier_api");
            	
            	if(!isset($tmp_addi_info['lazada_lgs_info'])){
            		return self::getResult(1, '', '调用Lazada接口失败,请联系小老板客服');
            	}
            	
            	$ship_result = LazadaApiHelper::shipLazadaLgsOrder($order);
            	if($ship_result[0] == true){
            		$r = CarrierAPIHelper::orderSuccess($order,$Service,$customer_number, OdOrder::CARRIER_WAITING_PRINT, $tmp_addi_info['lazada_lgs_info']['TrackingNumber'], ['PackageId'=>$tmp_addi_info['lazada_lgs_info']['PackageId']]);
            		return  self::getResult(0,$r,'标志发货成功');
            	}else if($ship_result[0] == false){
            		return self::getResult(1,'',$ship_result[1]);
            	}else{
            		return self::getResult(1,'','获取数据有误，请联系技术人员');
            	}
            }else{
            	if($order->order_source_status == 'ready_to_ship'){
            		$r = CarrierAPIHelper::orderSuccess($order,$Service,$customer_number, OdOrder::CARRIER_WAITING_GETCODE);
            		return  self::getResult(0,$r,'标志发货成功e1');
            	}
            	
            	$ship_result = LazadaApiHelper::shipLazadaLgsOrder($order);
            	if($ship_result[0] == true){
            		$r = CarrierAPIHelper::orderSuccess($order,$Service,$customer_number, OdOrder::CARRIER_WAITING_GETCODE);
            		return  self::getResult(0,$r,'标志发货成功');
            	}else if($ship_result[0] == false){
            		return self::getResult(1,'',$ship_result[1]);
            	}else{
            		return self::getResult(1,'','获取数据有误，请联系技术人员');
            	}
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
            list($ret,$packageInfo) = LazadaApiHelper::getPackageInfo($order);
            
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
    		
    		//打印格式
    		$carrier_params = $service->carrier_params;
//     		$tmp_print_format = empty($carrier_params['print_format']) ? '0' : $carrier_params['print_format'];
    		$tmp_print_format = 0;
    		
    		//获取站点键值
    		$code2CodeMap = array_flip(LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP);
    		
    		//记录账号和站点
    		$lazada_account_site = array();
    		
    		foreach ($data as $key => $value){
    			$order = $value['order'];
    			
    			if(empty($code2CodeMap[$order->order_source_site_id]))
    				return self::getResult(1,'','订单:'.$order->order_id." 站点" . $order->order_source_site_id . "不是 lazada的站点。");
    			
    			if(!isset($lazada_account_site[$order->selleruserid])){
    				$lazada_account_site[$order->selleruserid] = array();
    			}
    			
    			if(!isset($lazada_account_site[$order->selleruserid][$code2CodeMap[$order->order_source_site_id]])){
    				$lazada_account_site[$order->selleruserid][$code2CodeMap[$order->order_source_site_id]] = '';
    			}
    			
    			$tmp_item_ids = $lazada_account_site[$order->selleruserid][$code2CodeMap[$order->order_source_site_id]];
    			
    			foreach($order->items as $item){
    				$tmp_item_ids .= empty($tmp_item_ids) ? $item->order_source_order_item_id : ','.$item->order_source_order_item_id;
    			}
    			
    			$lazada_account_site[$order->selleruserid][$code2CodeMap[$order->order_source_site_id]] = $tmp_item_ids;
    		}
    		
    		//记录返回的base64字符串
    		$tmp_base64_str_a = array();
    		
    		//循环获取lazada返回的数据
    		foreach ($lazada_account_site as $lazada_account_key => $lazada_account_val){
    			foreach ($lazada_account_val as $lazada_site_key => $lazada_site_val){
    				$SLU = SaasLazadaUser::findOne(['platform_userid' => $lazada_account_key, 'lazada_site' => $lazada_site_key,'status' => 1]);
    				
    				if (empty($SLU)) {
    				    if($lazada_site_key == "co.id" ||$lazada_site_key == "co.th"){//兼容之前的旧帐号
    				        $newMap = ['co.id'=>'id','co.th'=>'th',];
    				        $SLU = SaasLazadaUser::findOne(['platform_userid' => $lazada_account_key, 'lazada_site' => $newMap[$lazada_site_key],'status' => 1]);
    				        if(empty($SLU)){
    				            return self::getResult(1,'',$lazada_account_key . " 账号不存在" .' '. $newMap[$lazada_site_key].'站点不存在');
    				        }
    				    }else{
    				        return self::getResult(1,'',$lazada_account_key . " 账号不存在" .' '. $lazada_site_key.'站点不存在');
    				    }
    					
    				}
    				
    				$lazada_config = array(
    						"userId" => $SLU->platform_userid,
    						"apiKey" => $SLU->token,
    						"countryCode" => $SLU->lazada_site
    				);
    				
    				$lazada_appParams = array(
    						'OrderItemIds' => $lazada_site_val,
    				);
    				
    				\Yii::info('LB_LGS,print,request,puid:'.$puid.' '.json_encode($lazada_config). ",appParams:" . json_encode($lazada_appParams), "carrier_api");
    				if(!empty($SLU->version)){//新接口
    				    $lazada_config['apiKey'] = $SLU->access_token;//新授权，用新的授权token
    				    $result = LazadaInterface_Helper_V2::getOrderShippingLabel($lazada_config, $lazada_appParams);
    				}else{//旧接口
    				    $result = LazadaInterface_Helper::getOrderShippingLabel($lazada_config, $lazada_appParams);
    				}
    				
    				\Yii::info("LB_LGS,print,result,puid:".$puid.' '. json_encode($result), "carrier_api");
    				
    				if ($result['success'] && $result['response']['success'] == true) { // 成功
//     					$tmp_text = base64_decode($result["response"]["body"]["Body"]["Documents"]["Document"]["File"]);
    				    if(!empty($SLU->version)){//新接口
    				       $tmp_base64_str_a[] = $result["response"]["body"]["document"]["file"];
    				    }else{//旧接口
    				       $tmp_base64_str_a[] = $result["response"]["body"]["Body"]["Document"]["File"];
    				    }
    					
    				} else {
    					return self::getResult(1, '', '打印失败原因：'.$result['message']);
    				}
    			}
    		}
    		
//     		unset($tmp_base64_str_a);
//     		$tmp_base64_str_a = array();
//     		$tmp_base64_str_a[] = 'PHRhYmxlIGNlbGxwYWRkaW5nPSIwIiBjZWxsc3BhY2luZz0iMCIgc3R5bGU9IndpZHRoOiA1MDBweDsgaGVpZ2h0OiA1MDBweDsgYm9yZGVyOiAycHggIzAwMCBzb2xpZDsiPgoJPHRib2R5PgoJCTx0cj4KCQkJPHRkIGNvbHNwYW49IjIiIHN0eWxlPSJ3aWR0aDogMTAwcHg7Ij48aW1nIHNyYz0iaHR0cDovL3ZpZ25ldHRlMy53aWtpYS5ub2Nvb2tpZS5uZXQvbG9nb3BlZGlhL2ltYWdlcy9mL2ZiL0xhemFkYV9sb2dvX25ldy5wbmcvcmV2aXNpb24vbGF0ZXN0L3NjYWxlLXRvLXdpZHRoLWRvd24vNjQwP2NiPTIwMTUwMTMxMjAzODI1IiB3aWR0aD0iMTAwcHg7IiAvPjwvdGQ+CgkJPC90cj4KCQk8dHI+CgkJCTx0ZCBjb2xzcGFuPSIyIiBzdHlsZT0idGV4dC1hbGlnbjogY2VudGVyOyBib3JkZXItdG9wOiAycHggIzAwMCBzb2xpZDsiPgoJCQk8cD48c3Ryb25nPkxhemFkYSBGaXJzdG1pbGUgVHJhY2tpbmcgTnVtYmVyPC9zdHJvbmc+Jm5ic3A7PC9wPgoKCQkJPHA+PGltZyBzcmM9J2RhdGE6aW1hZ2UvcG5nO2Jhc2U2NCxpVkJPUncwS0dnb0FBQUFOU1VoRVVnQUFBYWNBQUFCa0FRQUFBQUFLbjhLYkFBQUFDWEJJV1hNQUFDbUdBQUFwaGdFaTdOK01BQUFBRW5SRldIUlRiMlowZDJGeVpRQkNZWEpqYjJSbE5Fcnlqbll1QUFBQTYwbEVRVlI0Mm1QNC8vK1A4Zm5QSHc0Y09Qelo0TStmQXdjTS9wdy96TS9QWTJOajhPSEQrUU9IejM4MjRPR3g0VGRtNWo5dzJONkEzOGFlMmVELy8zOE1vN3BHZFkzcUd0VTFxbXRVMTZpdVVWMmp1a1oxamVvYTFUV3FhMVRYcUs1UlhZTkpGK2xnVkJjaFhYOC9NUDU5Zkx6UHB1TGo3LzZlbXZ6MSs0blQ5WC9kejVPVFZXWWFwWHd1cjY5ZWJsNVBwSzc4MHJ1N1MyOXZ6M3RkOXI5K3VSV1JMdnlYbnd2UmRidE12bjdWSG1KMXZZK0Y2TnJkOXI0K2F5K1J1djYrZDREb2F2ejJ2dTR0c2JwK3Yvc0xDWTNmNzk3WHZpZFMxeDhCZU1qTHkwam83eDFOdlRUUkJRQkVqUEVSZ0ExQzhBQUFBQUJKUlU1RXJrSmdnZz09JyAvPjwvcD4KCQkJPC90ZD4KCQk8L3RyPgoJCTx0cj4KCQkJPHRkIGNvbHNwYW49IjIiIHN0eWxlPSJib3JkZXItdG9wOiAgMnB4ICMwMDAgc29saWQ7Ij4KCQkJPHA+VG86IFRlcnJ5IGJpbiBsZWJpdCAgLSBNYWxheXNpYTxiciAvPgoJCQlTZXJ2aWNlOiBMYXphZGEgRmlyc3RtaWxlIEZ1bGZpbG1lbnQ8L3A+CgoJCQk8cD48YnIgLz4KCQkJU2VsbGVyIE5hbWU6IFlJU0lUVU88YnIgLz4KCQkJU2VsbGVyIEFkZHJlc3M6Jm5ic3A7PGJyIC8+CgkJCVNlbGxlciBDb250YWN0OiAwMDg2LTE1ODg5NjM1MzQ2PC9wPgoJCQk8L3RkPgoJCTwvdHI+CgkJPHRyPgoJCQk8dGQgY29sc3Bhbj0iMiIgc3R5bGU9ImJvcmRlci10b3A6ICAycHggIzAwMCBzb2xpZDsiPgoJCQk8cD7kuqflk4Hor7TmmI46PC9wPgoKCQkJPHRhYmxlPgoJCQkJPHRoZWFkPgoJCQkJCTx0cj4KCQkJCQkJPHRoPkl0ZW0gbmFtZTwvdGg+CgkJCQkJCTx0aD5JdGVtIHNrdSBzZWxsZXI8L3RoPgoJCQkJCTwvdHI+CgkJCQk8L3RoZWFkPgoJCQkJPHRib2R5PgoJCQkJCTx0cj4KCQkJCQkJPHRkPk5WQUhWQSBXNSBTcG9ydCBNUDMgTXVzaWMgUGxheWVyIFdpdGggRk08L3RkPgoJCQkJCQk8dGQ+TlZXNV9CbHVlPC90ZD4KCQkJCQk8L3RyPgoJCQkJCQoJCQkJPC90Ym9keT4KCQkJPC90YWJsZT4KCgkJCTxwPjxiciAvPgoJCQnpppblhazph4zku5PlupM6ICZuYnNwOzwvcD4KCgkJCTxwPuW5v+S4nOecgea3seWcs+W4guWuneWuieWMuuemj+awuOmVh+WhmOWwvuiNlOWbrei3ryDnm5vlkozlhbTlrp7kuJrlhazlj7gg5pe25Liw6L+Q6YCa54mp5rWBICZuYnNwOzUxODEwMzwvcD4KCQkJPC90ZD4KCQk8L3RyPgoJCTx0cj4KCQkJPHRkIHN0eWxlPSJib3JkZXItcmlnaHQ6IDFweCBzb2xpZCByZ2IoMCwgMCwgMCk7IGJvcmRlci10b3A6IDJweCBzb2xpZCByZ2IoMCwgMCwgMCk7IHdpZHRoOiA2NSU7IGhlaWdodDogODBweDsgdGV4dC1hbGlnbjogcmlnaHQ7Ij48c3BhbiBzdHlsZT0iZm9udC1zaXplOjE2cHg7Ij7muKDpgZPnvJbnoIE8L3NwYW4+PHNwYW4gc3R5bGU9ImZvbnQtc2l6ZToxOHB4OyI+ICZuYnNwOzwvc3Bhbj48L3RkPgoJCQk8dGQgc3R5bGU9InRleHQtYWxpZ246IGNlbnRlcjsgYm9yZGVyLXRvcDogIDJweCAjMDAwIHNvbGlkOyI+CgkJCTxwPjxmb250IHNpemU9IjUiPjxzdHJvbmc+TFpETVk8L3N0cm9uZz48L2ZvbnQ+PC9wPgoJCQk8L3RkPgoJCTwvdHI+CgkJPHRyPgoJCQk8dGQgY29sc3Bhbj0iMiIgc3R5bGU9ImJvcmRlci10b3A6ICAycHggIzAwMCBzb2xpZDsgdGV4dC1hbGlnbjogY2VudGVyOyI+TGF6YWRhIFBhY2thZ2UgTnVtYmVyPGJyIC8+CgkJCTxiciAvPgoJCQlNUERTLTM5NTkzNDkxOC0xNzc0PC90ZD4KCQk8L3RyPgoJPC90Ym9keT4KPC90YWJsZT4K';
    		
    		//最终生成的HTML
    		$tmp_html = '';
    		
    		foreach ($tmp_base64_str_a as $tmp_base64_val){
    			$tmp_html .= empty($tmp_html) ? base64_decode($tmp_base64_val) : '<hr style="page-break-after: always;border-top: 3px dashed;">'.base64_decode($tmp_base64_val);
    		}
    		
    		if(empty($tmp_print_format)){
	    		//LGS 返回的是html代码所以直接输出即可
	    		echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body style="margin:0px;">'.$tmp_html.''.'</body>';
	    		exit;
    		}else{
    			$tmp_html = str_replace('page-break-after: always;border-top: 3px dashed;', 'page-break-after: always;border-top: 0px dashed;', $tmp_html);
    			
    			$tmp_html = '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body style="margin:0px;">'.$tmp_html.'</body>';
    			
    			$tmp_version = time();
    			
    			if(in_array($service['shipping_method_code'], array('LGS-FM01','LGS-FM40','LGS-FM41','LGS-FM42','LGS-FM43'))){
    				$result = Helper_Curl::post("http://120.55.86.71/wkhtmltopdf.php",['html'=>$tmp_html,'uid'=>$puid,'pringType'=>"",'returnFileType'=>$tmp_version,'pageHeight'=>175,'pageWidth'=>133]);// 打A4还是热敏纸
    			}else{
    				$result = Helper_Curl::post("http://120.55.86.71/wkhtmltopdf.php",['html'=>$tmp_html,'uid'=>$puid,'pringType'=>"",'returnFileType'=>$tmp_version,'pageHeight'=>150,'pageWidth'=>119]);// 打A4还是热敏纸
    			}
    			
    			if(false !== $result){
    				$rtn = json_decode($result,true);
    				if(1 == $rtn['success']){
    					$response = Helper_Curl::get($rtn['url']);
    					$pdfUrl = \eagle\modules\carrier\helpers\CarrierAPIHelper::savePDF($response, $puid, md5("wkhtmltopdf")."_".time());
    					return self::getResult(0,['pdfUrl'=>$pdfUrl],'连接已生成,请点击并打印');
    				}else{
    					return self::getResult(1,'', '打印出错，请联系小老板客服。');
    				}
    			}else{
    				return self::getResult(1,'', '请重试，如果再有问题请联系小老板客服。');
    			}
    		}
//     		return self::getResult(0,['html_str'=>$tmp_html],'连接已生成,请点击并打印');
//     		return self::getResult(0,['pdfUrl'=>$post_result->url],'连接已生成,请点击并打印');
    	}catch(Exception $e) {
    		return self::getResult(1,'',$e->getMessage());
    	}
    }
    
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }
}

?>